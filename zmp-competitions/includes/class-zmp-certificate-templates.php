<?php
if (!defined('ABSPATH')) { exit; }

/** Private, moderator-managed DOCX diploma templates, scoped by year and category. */
class ZMP_Certificate_Templates {
    const NONCE = 'zmp_certificate_template';
    const FACSIMILE_TOKEN = '__ZMP_FACSIMILE__';
    const MAX_BYTES = 10485760;

    public static function init() {
        add_action('init', array(__CLASS__, 'handle_post'), 25);
        add_action('admin_post_zmp_download_certificate_template', array(__CLASS__, 'download_template'));
        add_action('admin_post_zmp_download_knowledge_certificate', array(__CLASS__, 'download_knowledge_certificate'));
    }

    private static function can_manage() {
        return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('zmp_moderator_cap') || current_user_can('view_zmp_moderator_dashboard'));
    }

    public static function handle_post() {
        if (empty($_POST['zmp_certificate_template_action'])) { return; }
        if (!self::can_manage()) { wp_die('Немате дозвола за управување со шаблони за дипломи.', '', array('response'=>403)); }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), self::NONCE)) { wp_die('Безбедносната проверка не помина.', '', array('response'=>403)); }
        $action = sanitize_key(wp_unslash($_POST['zmp_certificate_template_action']));
        if ($action === 'save') { self::save_template(); }
        if ($action === 'delete') { self::delete_template(); }
        wp_die('Непозната акција.', '', array('response'=>400));
    }

    private static function template_is_issued($drive_file_id) {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_certificate_issuances WHERE template_drive_file_id=%s", (string)$drive_file_id));
    }
    private static function save_template() {
        global $wpdb;
        $season_id = absint($_POST['season_id'] ?? 0);
        $season = $wpdb->get_row($wpdb->prepare("SELECT id,academic_year FROM {$wpdb->prefix}zmp_seasons WHERE id=%d", $season_id));
        $ids = array_values(array_unique(array_filter(array_map('absint', (array)wp_unslash($_POST['category_ids'] ?? array())))));
        if (!$season || !$ids) { self::redirect('invalid_scope'); }
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $valid = $wpdb->get_col($wpdb->prepare("SELECT c.id FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 AND c.id IN ($placeholders)", array_merge(array($season_id), $ids)));
        $valid = array_map('intval', (array)$valid);
        sort($valid); $sorted = $ids; sort($sorted);
        if ($valid !== $sorted) { self::redirect('invalid_scope'); }
        $file = $_FILES['template_file'] ?? null;
        if (!$file || !isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK || (int)$file['size'] < 1 || (int)$file['size'] > self::MAX_BYTES) { self::redirect('invalid_file'); }
        $original = sanitize_file_name(wp_unslash($file['name'] ?? 'diploma.docx'));
        if (strtolower(pathinfo($original, PATHINFO_EXTENSION)) !== 'docx' || !self::is_docx($file['tmp_name']) || !self::validate_template($file['tmp_name'])) { self::redirect('invalid_file'); }
        $category_names = $wpdb->get_results($wpdb->prepare("SELECT id,name_mk FROM {$wpdb->prefix}zmp_categories WHERE id IN ($placeholders)", $ids));
        $first_category = $category_names ? (int)$category_names[0]->id : 0;
        $drive_id = ZMP_Google_Drive::upload_private_document($file['tmp_name'], (int)$file['size'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $original, $season_id, $first_category, 'certificate-templates');
        if (is_wp_error($drive_id) || !$drive_id) { self::redirect('drive_error'); }

        $key = bin2hex(random_bytes(16));
        $wpdb->query('START TRANSACTION');
        $ok = true;
        foreach ($ids as $category_id) {
            $deleted = $wpdb->delete($wpdb->prefix.'zmp_certificate_templates', array('season_id'=>$season_id, 'category_id'=>$category_id), array('%d','%d'));
            if ($deleted === false) { $ok = false; break; }
            $inserted = $wpdb->insert($wpdb->prefix.'zmp_certificate_templates', array('template_key'=>$key,'season_id'=>$season_id,'category_id'=>$category_id,'drive_file_id'=>$drive_id,'original_filename'=>$original,'uploaded_by'=>get_current_user_id(),'created_at'=>current_time('mysql')), array('%s','%d','%d','%s','%s','%d','%s'));
            if (!$inserted) { $ok = false; break; }
        }
        if ($ok) { $wpdb->query('COMMIT'); }
        else { $wpdb->query('ROLLBACK'); ZMP_Google_Drive::delete_private_file($drive_id); self::redirect('save_error'); }
        self::audit($season_id, $ids, 'certificate_template_saved', array('template_key'=>$key,'category_ids'=>$ids,'original_filename'=>$original));
        self::redirect('saved');
    }

    private static function delete_template() {
        global $wpdb;
        $key = sanitize_text_field(wp_unslash($_POST['template_key'] ?? ''));
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id,season_id,category_id,drive_file_id FROM {$wpdb->prefix}zmp_certificate_templates WHERE template_key=%s", $key));
        if (!$key || !$rows) { self::redirect('not_found'); }
        $wpdb->query('START TRANSACTION');
        $deleted = $wpdb->delete($wpdb->prefix.'zmp_certificate_templates', array('template_key'=>$key), array('%s'));
        if ($deleted === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        $wpdb->query('COMMIT');
        // Keep the private Drive source as an archive; an issuance may be rendering concurrently.
        $ids = array_map(static function($row) { return (int)$row->category_id; }, $rows);
        self::audit((int)$rows[0]->season_id, $ids, 'certificate_template_deleted', array('template_key'=>$key));
        self::redirect('deleted');
    }

    public static function render_panel() {
        if (!self::can_manage()) { return ''; }
        global $wpdb;
        $seasons = $wpdb->get_results("SELECT id,academic_year,title,status FROM {$wpdb->prefix}zmp_seasons ORDER BY academic_year DESC,id DESC");
        $selected = absint($_GET['zmp_certificate_season'] ?? ($seasons[0]->id ?? 0));
        $season = null; foreach ((array)$seasons as $candidate) { if ((int)$candidate->id === $selected) { $season = $candidate; break; } }
        if (!$season && $seasons) { $season = $seasons[0]; $selected = (int)$season->id; }
        $status = sanitize_key(wp_unslash($_GET['zmp_certificate_status'] ?? ''));
        $messages = array('saved'=>'Word шаблонот е зачуван и приватно поставен.','deleted'=>'Шаблонот е тргнат од активната поставка; приватната Drive копија е задржана во архивата.','cleanup_warning'=>'Шаблонот е сменет во системот, но старата датотека не можеше да се избрише од Drive. Проверете ја приватната папка.','invalid_scope'=>'Изберете важечка година и една или повеќе активни категории.','invalid_file'=>'Прикачете валиден .docx документ до 10 MB и користете само поддржани ознаки од списокот.','drive_error'=>'Word шаблонот не можеше приватно да се зачува во Google Drive. Проверете ја Drive врската.','save_error'=>'Промената не е зачувана. Обидете се повторно.','not_found'=>'Шаблонот не е пронајден.');
        ob_start(); ?>
        <section class="zmp-card zmp-certificate-template-panel"><h3>Шаблони за дипломи</h3>
            <p>Поставете Word документ (.docx). Во самиот документ поставете ги ознаките таму каде што сакате да се испечатат податоците. Фајлот се чува во приватниот Google Drive. Еден шаблон може да важи за повеќе категории во избраната година.</p>
            <p><b>Достапни ознаки:</b> <code>&lt;&lt;Име&gt;&gt;</code>, <code>&lt;&lt;Презиме&gt;&gt;</code>, <code>&lt;&lt;Учесник&gt;&gt;</code>, <code>&lt;&lt;Награда&gt;&gt;</code>, <code>&lt;&lt;Поени&gt;&gt;</code>, <code>&lt;&lt;Категорија&gt;&gt;</code>, <code>&lt;&lt;Група&gt;&gt;</code>, <code>&lt;&lt;Натпреварувачко ниво&gt;&gt;</code>, <code>&lt;&lt;Фаза&gt;&gt;</code> (стара ознака за компатибилност со шаблони), <code>&lt;&lt;Година&gt;&gt;</code>, <code>&lt;&lt;Училиште&gt;&gt;</code>, <code>&lt;&lt;Натпревар&gt;&gt;</code>, <code>&lt;&lt;Датум&gt;&gt;</code>, <code>&lt;&lt;Факсимил&gt;&gt;</code> (задолжително; сликата се вметнува точно на ова место). Поддржани се и англиски ознаки: <code>&lt;&lt;first_name&gt;&gt;</code>, <code>&lt;&lt;last_name&gt;&gt;</code>, <code>&lt;&lt;award&gt;&gt;</code>, <code>&lt;&lt;points&gt;&gt;</code>, <code>&lt;&lt;category&gt;&gt;</code>, <code>&lt;&lt;group&gt;&gt;</code>, <code>&lt;&lt;competition_level&gt;&gt;</code>, <code>&lt;&lt;phase&gt;&gt;</code> (legacy), <code>&lt;&lt;year&gt;&gt;</code>, <code>&lt;&lt;school&gt;&gt;</code>, <code>&lt;&lt;competition&gt;&gt;</code>, <code>&lt;&lt;date&gt;&gt;</code>, <code>&lt;&lt;facsimile&gt;&gt;</code>.</p>
            <?php if (isset($messages[$status])): ?><p class="zmp-alert"><?php echo esc_html($messages[$status]); ?></p><?php endif; ?>
            <?php if (!$seasons): ?><p>Нема внесена натпреварувачка година.</p><?php else: ?>
            <form method="get" class="zmp-form"><label>Натпреварувачка година <select name="zmp_certificate_season" onchange="this.form.submit()"><?php foreach ($seasons as $s): ?><option value="<?php echo (int)$s->id; ?>" <?php selected($selected,(int)$s->id); ?>><?php echo esc_html($s->academic_year.' — '.$s->title); ?></option><?php endforeach; ?></select></label><?php foreach ((array)$_GET as $k=>$v) { if (in_array((string)$k,array('zmp_certificate_season','zmp_certificate_status'),true)||!is_scalar($v)) continue; ?><input type="hidden" name="<?php echo esc_attr(sanitize_key($k)); ?>" value="<?php echo esc_attr(sanitize_text_field(wp_unslash((string)$v))); ?>"><?php } ?></form>
            <form method="post" enctype="multipart/form-data" class="zmp-form zmp-certificate-template-upload"><input type="hidden" name="zmp_certificate_template_action" value="save"><input type="hidden" name="season_id" value="<?php echo (int)$selected; ?>"><?php wp_nonce_field(self::NONCE); ?><fieldset><legend>Категории</legend><?php $cats=$wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 ORDER BY c.sort_order,c.id",$selected)); foreach ((array)$cats as $cat): ?><label style="display:block"><input type="checkbox" name="category_ids[]" value="<?php echo (int)$cat->id; ?>"> <?php echo esc_html($cat->name_mk); ?></label><?php endforeach; ?></fieldset><label>Word шаблон (.docx, максимум 10 MB) <input type="file" name="template_file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required></label><button class="button button-primary">Постави / замени шаблон</button></form>
            <?php $rows=$wpdb->get_results($wpdb->prepare("SELECT t.template_key,t.drive_file_id,t.original_filename,t.created_at,GROUP_CONCAT(c.name_mk ORDER BY c.sort_order SEPARATOR ', ') AS categories FROM {$wpdb->prefix}zmp_certificate_templates t JOIN {$wpdb->prefix}zmp_categories c ON c.id=t.category_id WHERE t.season_id=%d GROUP BY t.template_key,t.drive_file_id,t.original_filename,t.created_at ORDER BY t.created_at DESC",$selected)); ?>
            <?php if ($rows): ?><h4>Поставени шаблони за <?php echo esc_html($season->academic_year); ?></h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категории</th><th>Документ</th><th>Поставен</th><th>Дејство</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?php echo esc_html($row->categories); ?></td><td><?php echo esc_html($row->original_filename); ?></td><td><?php echo esc_html($row->created_at); ?></td><td><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_download_certificate_template&template_key='.rawurlencode($row->template_key)), 'zmp_certificate_template_download_'.$row->template_key)); ?>">Преземи шаблон</a><form method="post" style="display:inline" onsubmit="return confirm('Да се отстрани овој Word шаблон?');"><input type="hidden" name="zmp_certificate_template_action" value="delete"><input type="hidden" name="template_key" value="<?php echo esc_attr($row->template_key); ?>"><?php wp_nonce_field(self::NONCE); ?><button class="button">Отстрани</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Нема поставени Word шаблони за оваа година.</p><?php endif; ?>
            <?php endif; ?>
        </section><?php return ob_get_clean();
    }

    public static function download_template() {
        if (!self::can_manage()) { wp_die('Немате дозвола.', '', array('response'=>403)); }
        $key = sanitize_text_field(wp_unslash($_GET['template_key'] ?? ''));
        check_admin_referer('zmp_certificate_template_download_'.$key);
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT drive_file_id,original_filename FROM {$wpdb->prefix}zmp_certificate_templates WHERE template_key=%s LIMIT 1", $key));
        if (!$row) { wp_die('Шаблонот не е пронајден.', '', array('response'=>404)); }
        $tmp = ZMP_Google_Drive::download_private_file_to_temp($row->drive_file_id);
        if (is_wp_error($tmp)) { wp_die('Преземањето од приватниот Drive не успеа.', '', array('response'=>502)); }
        nocache_headers(); header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document'); header('Content-Disposition: attachment; filename="'.sanitize_file_name($row->original_filename).'"'); header('Content-Length: '.filesize($tmp)); readfile($tmp); @unlink($tmp); exit;
    }

    public static function find_for_result($season_id, $category_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT id,template_key,drive_file_id,original_filename FROM {$wpdb->prefix}zmp_certificate_templates WHERE season_id=%d AND category_id=%d", absint($season_id), absint($category_id)));
    }

    /** Include the original private Word template in the verified season archive. */
    public static function add_templates_to_archive($archive, $season_id) {
        global $wpdb;
        $rows=$wpdb->get_results($wpdb->prepare("SELECT t.category_id,t.drive_file_id,t.original_filename,s.academic_year FROM {$wpdb->prefix}zmp_certificate_templates t JOIN {$wpdb->prefix}zmp_seasons s ON s.id=t.season_id WHERE t.season_id=%d ORDER BY t.category_id",absint($season_id)));
        foreach ((array)$rows as $row) {
            $tmp=ZMP_Google_Drive::download_private_file_to_temp($row->drive_file_id);
            if (is_wp_error($tmp)) { return false; }
            $path='certificate-templates/'.sanitize_file_name($row->academic_year).'/category-'.(int)$row->category_id.'/'.sanitize_file_name($row->original_filename);
            $added=$archive->addFile($tmp,$path); @unlink($tmp);
            if (!$added) { return false; }
        }
        return true;
    }

    /** Remove private template files before the explicit destructive testing reset. */
    public static function delete_all_for_reset() {
        if (!current_user_can('manage_options')) { return false; }
        global $wpdb;
        $ids=$wpdb->get_col("SELECT DISTINCT drive_file_id FROM {$wpdb->prefix}zmp_certificate_templates");
        foreach ((array)$ids as $id) { $result=ZMP_Google_Drive::delete_private_file($id); if (is_wp_error($result)) { return false; } }
        return true;
    }

    /** Link a mentor to the personalized diploma for a finalized knowledge-test phase. */
    public static function render_knowledge_link($phase_result_id) {
        if (!is_user_logged_in()) { return ''; }
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT p.id,p.competition_id,p.phase,p.mentor_user_id,p.award_level,s.id AS season_id,c.id AS category_id FROM {$wpdb->prefix}zmp_phase_results p JOIN {$wpdb->prefix}zmp_seasons s ON s.academic_year=p.academic_year JOIN {$wpdb->prefix}zmp_categories c ON c.category_code='knowledge_test' WHERE p.id=%d", absint($phase_result_id)));
        if (!$row || !self::award_label($row->award_level) || ((int)$row->mentor_user_id !== get_current_user_id() && !self::can_manage() && !current_user_can('zmp_commission_cap'))) { return ''; }
        if (!self::knowledge_result_is_finalized((int)$row->competition_id, (string)$row->phase)) { return ''; }
        if ($row && class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror() && !ZMP_Category_Jury::has_assignment((int)$row->season_id,(int)$row->category_id,get_current_user_id())) { return ''; }
        if (!self::find_for_result((int)$row->season_id,(int)$row->category_id)) { return ''; }
        $url=wp_nonce_url(admin_url('admin-post.php?action=zmp_download_knowledge_certificate&result_id='.(int)$row->id),'zmp_knowledge_certificate_'.(int)$row->id);
        return '<br><a href="'.esc_url($url).'">Преземи диплома (.pdf)</a>';
    }

    public static function download_knowledge_certificate() {
        if (!is_user_logged_in()) { wp_die('Потребна е најава.', '', array('response'=>403)); }
        $id=absint($_GET['result_id']??0); check_admin_referer('zmp_knowledge_certificate_'.$id); global $wpdb;
        $row=$wpdb->get_row($wpdb->prepare("SELECT p.id,p.competition_id,p.mentor_user_id AS wp_user_id,p.student_name AS participant_name,p.first_name,p.last_name,p.school_name,p.phase,p.award_level AS award_key,p.total_points AS final_points,p.completed_at AS decided_at,p.academic_year,s.id AS season_id,c.id AS category_id,c.name_mk AS category_name,c.participant_type,co.title AS competition_title FROM {$wpdb->prefix}zmp_phase_results p JOIN {$wpdb->prefix}zmp_seasons s ON s.academic_year=p.academic_year JOIN {$wpdb->prefix}zmp_categories c ON c.category_code='knowledge_test' LEFT JOIN {$wpdb->prefix}zmp_competitions co ON co.id=p.competition_id WHERE p.id=%d",$id));
        if (!$row || !self::award_label($row->award_key)) { wp_die('Потврден резултат со награда не е пронајден.', '', array('response'=>404)); }
        if (!self::knowledge_result_is_finalized((int)$row->competition_id, (string)$row->phase)) { wp_die('Резултатот сè уште не е потврден со затворање на натпреварувачкото ниво.', '', array('response'=>403)); }
        if ((int)$row->wp_user_id !== get_current_user_id() && !self::can_manage() && !current_user_can('zmp_commission_cap')) { wp_die('Немате дозвола за оваа диплома.', '', array('response'=>403)); }
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror() && !ZMP_Category_Jury::has_assignment((int)$row->season_id,(int)$row->category_id,get_current_user_id())) { wp_die('Немате доделба за оваа категорија и година.', '', array('response'=>403)); }
        if (!self::stream_for_result($id,$row)) { wp_die('За оваа година сè уште нема Word шаблон за тестот на знаење.', '', array('response'=>404)); }
    }

    /** Knowledge-test awards become final only after the commission closes that phase. */
    public static function knowledge_result_is_finalized($competition_id, $phase) {
        global $wpdb;
        if ((int)$competition_id < 1 || !in_array((string)$phase, array('school','municipal','regional','state'), true)) { return false; }
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s AND status='closed'",
            (int)$competition_id, (string)$phase
        ));
    }
    private static function result_values($row) {
        $participant = trim((string)($row->participant_name ?? ''));
        $parts = preg_split('/\\s+/u', $participant, 2, PREG_SPLIT_NO_EMPTY);
        $group = (string)($row->group_name ?? '');
        $is_group = isset($row->participant_type) && $row->participant_type === 'group';
        $name = !empty($row->first_name) ? (string)$row->first_name : ($is_group ? $participant : (string)($parts[0] ?? ''));
        $surname = !empty($row->last_name) ? (string)$row->last_name : ($is_group ? '' : (string)($parts[1] ?? ''));
        $date = !empty($row->decided_at) ? mysql2date('d.m.Y', $row->decided_at) : '';
        $award = self::award_label($row->award_key ?? $row->award_level ?? '');
        $points = number_format((float)($row->final_points ?? $row->total_points ?? 0), 2, ',', '');
        $category = (string)($row->category_name ?? '');
        $phase = self::phase_label($row->phase ?? '');
        $year = (string)($row->academic_year ?? '');
        $school = (string)($row->school_name ?? '');
        $competition = (string)($row->competition_title ?? 'Државни натпревари на ЗМП');
        return array(
            'име'=>$name,'презиме'=>$surname,'учесник'=>$participant,'награда'=>$award,'поени'=>$points,
            'категорија'=>$category,'група'=>$group,'натпреварувачко ниво'=>$phase,'фаза'=>$phase,'година'=>$year,'училиште'=>$school,'натпревар'=>$competition,'датум'=>$date,
            'first_name'=>$name,'last_name'=>$surname,'participant'=>$participant,'award'=>$award,'points'=>number_format((float)($row->final_points ?? $row->total_points ?? 0),2,'.',''),
            'category'=>$category,'group'=>$group,'phase'=>$phase,'year'=>$year,'school'=>$school,'competition'=>$competition,'date'=>$date,
        );
    }

    /** Replace approved text and preserve the facsimile marker position for inline PDF insertion. */
    public static function personalize_docx_file($source, $destination, $values, $facsimile_path) {
        if (!self::is_docx($source) || !is_readable($facsimile_path) || !copy($source, $destination)) { return false; }
        $values['facsimile'] = self::FACSIMILE_TOKEN;
        if (!self::replace_docx($destination, $values) || self::docx_text_occurrences($destination, self::FACSIMILE_TOKEN) !== 1) { @unlink($destination); return false; }
        $extension = strtolower(pathinfo($facsimile_path, PATHINFO_EXTENSION));
        if (!in_array($extension, array('png','jpg','jpeg'), true)) { @unlink($destination); return false; }
        $image_path = $destination.'.facsimile.'.$extension;
        if (!copy($facsimile_path, $image_path)) { @unlink($destination); @unlink($image_path); return false; }
        @chmod($image_path, 0600);
        $image = @getimagesize($image_path);
        if (!$image || !in_array($image['mime'] ?? '', array('image/png','image/jpeg'), true)) { @unlink($destination); @unlink($image_path); return false; }
        return true;
    }

    /** Convert the personalized DOCX to a bounded PDF with remote/PHP execution disabled. */
    public static function render_pdf_file($docx, $pdf) {
        $autoload = dirname(__DIR__).'/vendor/autoload.php';
        if (!is_file($autoload)) { return false; }
        require_once $autoload;
        if (!class_exists('PhpOffice\\PhpWord\IOFactory') || !class_exists('Dompdf\Dompdf') || !class_exists('Dompdf\\Options')) { return false; }
        $workspace = realpath(dirname($docx));
        if (!$workspace || !is_file($docx) || !is_writable($workspace)) { return false; }
        $html = $workspace.'/render-'.bin2hex(random_bytes(8)).'.html';
        // PHPWord 1.4 emits E_DEPRECATED under PHP 8.5; suppress only its vendor notices to avoid corrupting a PDF response.
        set_error_handler(static function($severity, $message, $file) {
            $path = str_replace('\\', '/', (string)$file);
            if ($severity === E_DEPRECATED && strpos($path, '/vendor/phpoffice/phpword/') !== false) { return true; }
            return false;
        }, E_DEPRECATED);
        try {
            $word = \PhpOffice\PhpWord\IOFactory::load($docx);
            $sections = $word->getSections();
            $orientation = 'portrait';
            if ($sections && method_exists($sections[0], 'getStyle')) {
                $style = $sections[0]->getStyle();
                if ($style && method_exists($style, 'getOrientation') && strtolower((string)$style->getOrientation()) === 'landscape') { $orientation = 'landscape'; }
            }
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($word, 'HTML');
            $writer->save($html);
            $html_content = file_get_contents($html);
            $facsimile_files = glob($docx.'.facsimile.*') ?: array();
            if (!is_string($html_content) || count($facsimile_files) !== 1 || substr_count($html_content, self::FACSIMILE_TOKEN) !== 1) { @unlink($html); return false; }
            $facsimile_real = realpath($facsimile_files[0]);
            $workspace_prefix = rtrim(str_replace('\\', '/', $workspace), '/').'/';
            $facsimile_normalized = $facsimile_real ? str_replace('\\', '/', $facsimile_real) : '';
            $facsimile_info = $facsimile_real ? @getimagesize($facsimile_real) : false;
            if (!$facsimile_real || strpos($facsimile_normalized, $workspace_prefix) !== 0 || !$facsimile_info || !in_array($facsimile_info['mime'] ?? '', array('image/png','image/jpeg'), true)) { @unlink($html); return false; }
            $image_tag = '<img src="'.htmlspecialchars(basename($facsimile_real), ENT_QUOTES, 'UTF-8').'" alt="Факсимил" style="display:inline-block;width:180px;height:auto;vertical-align:middle" />';
            $html_content = str_replace(self::FACSIMILE_TOKEN, $image_tag, $html_content);
            if (file_put_contents($html, $html_content, LOCK_EX) !== strlen($html_content)) { @unlink($html); return false; }
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('chroot', $workspace);
            $options->set('tempDir', $workspace);
            $options->set('fontCache', $workspace);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->setBasePath($workspace.'/');
            $dompdf->loadHtml($html_content, 'UTF-8');
            $dompdf->setPaper('A4', $orientation);
            $dompdf->render();
            $bytes = $dompdf->output();
            if (!is_string($bytes) || strlen($bytes) < 500 || strpos($bytes, '%PDF-') !== 0 || strlen($bytes) > 25*1024*1024) { @unlink($html); return false; }
            $written = file_put_contents($pdf, $bytes, LOCK_EX);
            @unlink($html);
            return $written === strlen($bytes);
        } catch (Throwable $e) { @unlink($html); return false; } finally { restore_error_handler(); }
    }

    private static function remove_workdir($directory) {
        $real = realpath($directory);
        $base = realpath(sys_get_temp_dir());
        if (!$real || !$base || strpos($real, rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) !== 0 || strpos(basename($real), 'zmp-certificate-') !== 0) { return; }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) { $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname()); }
        @rmdir($real);
    }

    /** Return an immutable issued PDF, generating and privately archiving it exactly once if needed. */
    private static function issued_pdf_file($result_type, $result_id, $row) {
        global $wpdb;
        $result_type = $result_type === 'knowledge' ? 'knowledge' : 'category';
        $result_id = absint($result_id);
        if (!$result_id || !isset($row->season_id, $row->category_id) || !self::award_label($row->award_key ?? $row->award_level ?? '')) { return new WP_Error('certificate_result_invalid'); }
        if ($result_type === 'knowledge' && !self::knowledge_result_is_finalized((int)($row->competition_id ?? 0), (string)($row->phase ?? ''))) { return new WP_Error('knowledge_result_unconfirmed'); }
        $values = self::result_values($row);
        $rendered_data_hash = hash('sha256', wp_json_encode($values, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        if (!$rendered_data_hash) { return new WP_Error('certificate_result_invalid'); }
        $table = $wpdb->prefix.'zmp_certificate_issuances';
        $lock = 'zmpc_'.substr(hash('sha256', home_url('/').'|'.$result_type.'|'.$result_id), 0, 58);
        if ((string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,10)', $lock)) !== '1') { return new WP_Error('certificate_busy'); }
        $workspace = ''; $source = ''; $local_pdf = ''; $uploaded_id = ''; $template_snapshot_id = ''; $issue_saved = false; $facsimile_use_lock = false;
        try {
            $issue = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE result_type=%s AND result_id=%d AND rendered_data_sha256=%s ORDER BY id DESC LIMIT 1", $result_type, $result_id, $rendered_data_hash));
            if ($issue) {
                $existing = ZMP_Google_Drive::download_private_file_to_temp($issue->pdf_drive_file_id);
                if (is_wp_error($existing)) { return new WP_Error('certificate_archive_unavailable'); }
                $hash = is_file($existing) ? hash_file('sha256', $existing) : false;
                if (!$hash || !hash_equals((string)$issue->pdf_sha256, (string)$hash)) { @unlink($existing); return new WP_Error('certificate_archive_integrity'); }
                return $existing;
            }
            $template = self::find_for_result((int)$row->season_id, (int)$row->category_id);
            if (!$template || empty($template->template_key)) { return new WP_Error('certificate_template_missing'); }
            if (!class_exists('ZMP_Registration_Requirements') || !method_exists('ZMP_Registration_Requirements', 'get_admin_facsimile')) { return new WP_Error('certificate_facsimile_missing'); }
            $facsimile = ZMP_Registration_Requirements::get_admin_facsimile();
            if (is_wp_error($facsimile)) { return new WP_Error('certificate_facsimile_missing'); }
            if (!is_array($facsimile) || empty($facsimile['id']) || empty($facsimile['sha256'])
                || !class_exists('ZMP_President_Facsimile_Certificates')
                || !is_callable(array('ZMP_President_Facsimile_Certificates', 'active_certificate_allows_use'))
                || !ZMP_President_Facsimile_Certificates::active_certificate_allows_use(
                    ZMP_President_Facsimile_Certificates::CERTIFICATE_USE,
                    absint($facsimile['id']),
                    (string) $facsimile['sha256']
                )) {
                return new WP_Error('certificate_facsimile_certificate_invalid');
            }
            $source = ZMP_Google_Drive::download_private_file_to_temp($template->drive_file_id);
            if (is_wp_error($source)) { return new WP_Error('certificate_template_unavailable'); }
            $template_hash = hash_file('sha256', $source);
            if (!$template_hash) { return new WP_Error('certificate_template_integrity'); }
            $template_snapshot_id = ZMP_Google_Drive::upload_private_document($source, (int)filesize($source), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'zmp-template-snapshot-'.$result_type.'-'.$result_id.'-'.substr($template_hash,0,12).'.docx', (int)$row->season_id, (int)$row->category_id, 'certificate-template-snapshots');
            if (is_wp_error($template_snapshot_id) || !$template_snapshot_id) { $template_snapshot_id = ''; return new WP_Error('certificate_template_snapshot_failed'); }
            $workspace = sys_get_temp_dir().'/zmp-certificate-'.bin2hex(random_bytes(12));
            if (!@mkdir($workspace, 0700) || !@chmod($workspace, 0700)) { return new WP_Error('certificate_temp_unavailable'); }
            $docx = $workspace.'/template.docx'; $personalized = $workspace.'/issued.docx'; $rendered = $workspace.'/issued.pdf';
            $facsimile_copy = $workspace.'/facsimile.'.($facsimile['mime']==='image/png'?'png':'jpg');
            if (!copy($source, $docx) || !copy($facsimile['path'], $facsimile_copy)) { return new WP_Error('certificate_temp_copy_failed'); }
            if (!self::personalize_docx_file($docx, $personalized, $values, $facsimile_copy) || !self::render_pdf_file($personalized, $rendered)) { return new WP_Error('certificate_render_failed'); }
            $pdf_hash = hash_file('sha256', $rendered); $pdf_size = (int)filesize($rendered);
            if (!$pdf_hash || $pdf_size < 500 || $pdf_size > 25*1024*1024) { return new WP_Error('certificate_pdf_invalid'); }
            // Serialize final authorization and issuance persistence with revocation and facsimile replacement.
            // Rendering happens before this lock; re-read and compare the facsimile before any PDF is archived.
            if (!class_exists('ZMP_Registration_Requirements')
                || !is_callable(array('ZMP_Registration_Requirements', 'acquire_facsimile_write_lock'))
                || !is_callable(array('ZMP_Registration_Requirements', 'release_facsimile_write_lock'))
                || !ZMP_Registration_Requirements::acquire_facsimile_write_lock()) {
                return new WP_Error('certificate_busy');
            }
            $facsimile_use_lock = true;
            $current_facsimile = ZMP_Registration_Requirements::get_admin_facsimile();
            if (is_wp_error($current_facsimile) || !is_array($current_facsimile)
                || absint($current_facsimile['id'] ?? 0) !== absint($facsimile['id'])
                || !hash_equals((string)$facsimile['sha256'], (string)($current_facsimile['sha256'] ?? ''))
                || !ZMP_President_Facsimile_Certificates::active_certificate_allows_use(
                    ZMP_President_Facsimile_Certificates::CERTIFICATE_USE,
                    absint($current_facsimile['id']),
                    (string)$current_facsimile['sha256']
                )) {
                return new WP_Error('certificate_facsimile_certificate_invalid');
            }
            $uploaded_id = ZMP_Google_Drive::upload_private_document($rendered, $pdf_size, 'application/pdf', 'zmp-certificate-'.$result_type.'-'.$result_id.'-'.substr($rendered_data_hash,0,12).'.pdf', (int)$row->season_id, (int)$row->category_id, 'certificates');
            if (is_wp_error($uploaded_id) || !$uploaded_id) { $uploaded_id = ''; return new WP_Error('certificate_archive_upload_failed'); }
            $inserted = $wpdb->insert($table, array(
                'result_type'=>$result_type,'result_id'=>$result_id,'season_id'=>(int)$row->season_id,'category_id'=>(int)$row->category_id,
                'entry_id'=>(int)($row->entry_id ?? 0),'phase'=>sanitize_key($row->phase ?? ''),'award_key'=>sanitize_key($row->award_key ?? $row->award_level ?? ''),
                'template_key'=>(string)$template->template_key,'template_drive_file_id'=>(string)$template->drive_file_id,
                'template_snapshot_drive_file_id'=>(string)$template_snapshot_id,'template_sha256'=>$template_hash,
                'facsimile_id'=>(int)$facsimile['id'],'facsimile_sha256'=>(string)$facsimile['sha256'],'pdf_drive_file_id'=>(string)$uploaded_id,
                'pdf_sha256'=>$pdf_hash,'rendered_data_sha256'=>$rendered_data_hash,'issued_by'=>get_current_user_id(),'created_at'=>current_time('mysql'),
            ));
            if (!$inserted) {
                $existing_issue = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE result_type=%s AND result_id=%d AND rendered_data_sha256=%s ORDER BY id DESC LIMIT 1", $result_type, $result_id, $rendered_data_hash));
                ZMP_Google_Drive::delete_private_file($uploaded_id); $uploaded_id = '';
                ZMP_Google_Drive::delete_private_file($template_snapshot_id); $template_snapshot_id = '';
                if (!$existing_issue) { return new WP_Error('certificate_issue_record_failed'); }
                $existing = ZMP_Google_Drive::download_private_file_to_temp($existing_issue->pdf_drive_file_id);
                if (is_wp_error($existing) || !is_file($existing) || !hash_equals((string)$existing_issue->pdf_sha256, (string)hash_file('sha256', $existing))) { if (!is_wp_error($existing)) { @unlink($existing); } return new WP_Error('certificate_archive_integrity'); }
                return $existing;
            }
            $issue_saved = true;
            $local_pdf = tempnam(sys_get_temp_dir(), 'zmp-certificate-pdf-');
            if (!$local_pdf || !copy($rendered, $local_pdf)) { if ($local_pdf) { @unlink($local_pdf); } $local_pdf=''; return new WP_Error('certificate_stream_copy_failed'); }
            return $local_pdf;
        } catch (Throwable $e) {
            return new WP_Error('certificate_generation_failed');
        } finally {
            if ($facsimile_use_lock) { ZMP_Registration_Requirements::release_facsimile_write_lock(); }
            if (!$issue_saved) {
                if ($uploaded_id) { ZMP_Google_Drive::delete_private_file($uploaded_id); }
                if ($template_snapshot_id) { ZMP_Google_Drive::delete_private_file($template_snapshot_id); }
            }
            if ($source && !is_wp_error($source)) { @unlink($source); }
            if ($workspace) { self::remove_workdir($workspace); }
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }
    public static function get_issued_pdf_file($result_type, $result_id, $row) {
        return self::issued_pdf_file($result_type, $result_id, $row);
    }
    public static function stream_pdf_for_result($result_type, $result_id, $row) {
        $pdf = self::issued_pdf_file($result_type, $result_id, $row);
        if (is_wp_error($pdf)) {
            $messages = array('certificate_template_missing'=>'За оваа категорија и година нема Word шаблон за диплома.','certificate_facsimile_missing'=>'Нема активен, проверен факсимил.','certificate_facsimile_certificate_invalid'=>'Нема важечки претседателски сертификат за употреба на тековниот факсимил.','certificate_busy'=>'Дипломата веќе се изработува; обидете се повторно.','certificate_archive_unavailable'=>'Архивираната PDF диплома не може да се преземе од приватниот Drive.','certificate_archive_integrity'=>'Архивираната PDF диплома не ја помина проверката на интегритет.','certificate_archive_upload_failed'=>'PDF дипломата не можеше да се зачува во приватниот Drive.','knowledge_result_unconfirmed'=>'Резултатот сè уште не е потврден со затворање на натпреварувачкото ниво.','certificate_render_failed'=>'DOCX шаблонот не можеше безбедно да се претвори во PDF.');
            wp_die(esc_html($messages[$pdf->get_error_code()] ?? 'Дипломата не можеше да се изработи.'), '', array('response'=>503));
        }
        if (!is_file($pdf) || !is_readable($pdf)) { wp_die('PDF дипломата не е достапна.', '', array('response'=>503)); }
        nocache_headers(); header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="zmp-diploma-'.absint($result_id).'.pdf"'); header('X-Content-Type-Options: nosniff'); header('Content-Length: '.filesize($pdf)); readfile($pdf); @unlink($pdf); exit;
    }

    public static function stream_for_result($result_id, $row) {
        return self::stream_pdf_for_result('knowledge', $result_id, $row);
    }
    /** Replace placeholders in each Word XML text run, including markers split across runs. */
    public static function replace_xml_placeholders($xml, $replacements) {
        $dom = new DOMDocument(); $dom->preserveWhiteSpace = true;
        if (!@$dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) { return false; }
        $xpath = new DOMXPath($dom); $xpath->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($xpath->query('//w:p') as $paragraph) {
            $nodes = $xpath->query('.//w:t', $paragraph);
            if (!$nodes->length) { continue; }
            $values = array(); foreach ($nodes as $node) { $values[] = $node->nodeValue; }
            while (true) {
                $whole = implode('', $values);
                if (!preg_match('/<<\s*([^<>]{1,40}?)\s*>>/u', $whole, $match, PREG_OFFSET_CAPTURE)) { break; }
                $raw_key = trim($match[1][0]); $key = self::normalize_token($raw_key);
                if (!array_key_exists($key, $replacements)) { return false; }
                $offset = $match[0][1]; $length = strlen($match[0][0]); $cursor = 0; $start = $end = null; $start_pos = $end_pos = 0;
                foreach ($values as $i=>$value) {
                    $next = $cursor + strlen($value);
                    if ($start === null && $offset >= $cursor && $offset < $next) { $start=$i; $start_pos=$offset-$cursor; }
                    if ($start !== null && ($offset+$length) <= $next) { $end=$i; $end_pos=$offset+$length-$cursor; break; }
                    $cursor = $next;
                }
                if ($start === null || $end === null) { return false; }
                $replacement = (string)$replacements[$key];
                if ($start === $end) { $values[$start] = substr($values[$start],0,$start_pos).$replacement.substr($values[$end],$end_pos); }
                else { $values[$start] = substr($values[$start],0,$start_pos).$replacement; for ($i=$start+1;$i<$end;$i++) { $values[$i]=''; } $values[$end] = substr($values[$end],$end_pos); }
                foreach ($nodes as $i=>$node) {
                    while ($node->firstChild) { $node->removeChild($node->firstChild); }
                    $node->appendChild($dom->createTextNode($values[$i]));
                }
            }
        }
        return $dom->saveXML();
    }

    public static function replace_docx($path, $replacements) {
        $entries = array();
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive(); if ($zip->open($path) !== true) { return false; }
            for ($i=0;$i<$zip->numFiles;$i++) { $name=$zip->getNameIndex($i); if (preg_match('~^word/(document|header\d*|footer\d*)\.xml$~',$name)) { $xml=$zip->getFromIndex($i); $changed=self::replace_xml_placeholders($xml,$replacements); if ($changed===false) { $zip->close(); return false; } $entries[$name]=$changed; } }
            if (!isset($entries['word/document.xml'])) { $zip->close(); return false; }
            foreach ($entries as $name=>$xml) { if (!$zip->addFromString($name,$xml)) { $zip->close(); return false; } }
            return $zip->close();
        }
        if (!class_exists('PharData')) { return false; }
        try {
            $archive = new PharData($path);
            foreach (array('word/document.xml') as $name) { if (!isset($archive[$name])) { return false; } }
            $prefix = 'phar://' . str_replace('\\','/', $archive->getPath()) . '/';
            foreach (new RecursiveIteratorIterator($archive) as $file) {
                $name = str_replace('\\','/', $file->getPathName());
                if (strpos($name, $prefix) === 0) { $name = substr($name, strlen($prefix)); }
                $name = ltrim($name, '/');
                if (preg_match('~^word/(document|header\d*|footer\d*)\.xml$~',$name)) {
                    $changed=self::replace_xml_placeholders($file->getContent(),$replacements);
                    if ($changed===false) { return false; }
                    $entries[$name]=$changed;
                }
            }
            foreach ($entries as $name=>$xml) { $archive[$name]=$xml; }
            unset($archive);
            return isset($entries['word/document.xml']);
        } catch (Throwable $e) { return false; }
    }

    private static function is_docx($path) {
        if (!is_file($path) || !is_readable($path)) { return false; }
        if (class_exists('ZipArchive')) { $zip=new ZipArchive(); if ($zip->open($path,ZipArchive::RDONLY)!==true) return false; $ok=$zip->locateName('[Content_Types].xml')!==false && $zip->locateName('word/document.xml')!==false && $zip->numFiles<=500; $total=0; if($ok){for($i=0;$i<$zip->numFiles;$i++){ $stat=$zip->statIndex($i); $total+=(int)($stat['size']??0); if($total>20971520){$ok=false;break;} }} $zip->close(); return $ok; }
        if (!class_exists('PharData')) return false;
        try { $archive=new PharData($path); if(!isset($archive['[Content_Types].xml'])||!isset($archive['word/document.xml']))return false; $count=0;$total=0;foreach($archive as $entry){$count++;$total+=(int)$entry->getSize();if($count>500||$total>20971520)return false;}return true; } catch (Throwable $e) { return false; }
    }

    /** Validate the package and reject unsupported placeholder names before saving it. */
    private static function validate_template($source) {
        $base=tempnam(sys_get_temp_dir(),'zmp-template-check-'); if(!$base)return false;
        $copy=$base.'.docx'; @unlink($base);
        if(!copy($source,$copy)) { @unlink($copy); return false; }
        $keys=array('име','презиме','учесник','награда','поени','категорија','група','натпреварувачко ниво','фаза','година','училиште','натпревар','датум','first_name','last_name','participant','award','points','category','group','phase','year','school','competition','date','facsimile');
        $values=array_fill_keys($keys,'ТЕСТ');
        $values['facsimile'] = self::FACSIMILE_TOKEN;
        $valid=self::replace_docx($copy,$values) && self::docx_text_occurrences($copy,self::FACSIMILE_TOKEN)===1;
        @unlink($copy);
        return $valid;
    }
    private static function docx_text_occurrences($path, $needle) {
        if (!class_exists('ZipArchive')) { return 0; }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) { return 0; }
        $count = 0;
        for ($i=0; $i<$zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('~^word/(document|header\d*|footer\d*)\.xml$~', (string)$name)) { continue; }
            $xml = $zip->getFromIndex($i);
            if (is_string($xml)) { $count += substr_count($xml, (string)$needle); }
        }
        $zip->close();
        return $count;
    }

    private static function normalize_token($token) {
        $token = trim($token);
        // Do not depend on mbstring: many shared WordPress hosts omit it.
        $token = strtr($token, array('А'=>'а','Б'=>'б','В'=>'в','Г'=>'г','Д'=>'д','Ѓ'=>'ѓ','Е'=>'е','Ж'=>'ж','З'=>'з','Ѕ'=>'ѕ','И'=>'и','Ј'=>'ј','К'=>'к','Л'=>'л','Љ'=>'љ','М'=>'м','Н'=>'н','Њ'=>'њ','О'=>'о','П'=>'п','Р'=>'р','С'=>'с','Т'=>'т','Ќ'=>'ќ','У'=>'у','Ф'=>'ф','Х'=>'х','Ц'=>'ц','Ч'=>'ч','Џ'=>'џ','Ш'=>'ш'));
        $token = strtolower($token);
        $aliases = array('име'=>'име','име на ученик'=>'име','first_name'=>'first_name','презиме'=>'презиме','last_name'=>'last_name','учесник'=>'учесник','participant'=>'participant','награда'=>'награда','award'=>'award','поени'=>'поени','бодови'=>'поени','points'=>'points','категорија'=>'категорија','category'=>'category','група'=>'група','group'=>'group','натпреварувачко ниво'=>'фаза','competition_level'=>'phase','фаза'=>'фаза','phase'=>'phase','година'=>'година','year'=>'year','училиште'=>'училиште','school'=>'school','натпревар'=>'натпревар','competition'=>'competition','датум'=>'датум','date'=>'date','факсимил'=>'facsimile','потпис и печат'=>'facsimile','facsimile'=>'facsimile');
        return $aliases[$token] ?? $token;
    }

    private static function award_label($key) { $map=array('first'=>'Прва награда','first_prize'=>'Прва награда','second'=>'Втора награда','second_prize'=>'Втора награда','third'=>'Трета награда','third_prize'=>'Трета награда','honorable'=>'Пофалница','participation'=>'Диплома за учество'); return $map[$key] ?? ''; }
    private static function phase_label($key) { $map=array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво'); return $map[$key] ?? sanitize_text_field($key); }
    private static function audit($season_id, $category_ids, $action, $details) {
        global $wpdb;
        foreach ((array)$category_ids as $category_id) { $wpdb->insert($wpdb->prefix.'zmp_operations_audit',array('season_id'=>(int)$season_id,'category_id'=>(int)$category_id,'phase'=>'','actor_user_id'=>get_current_user_id(),'action'=>$action,'object_type'=>'certificate_template','object_id'=>0,'details'=>wp_json_encode($details),'created_at'=>current_time('mysql'))); }
    }
    private static function redirect($status) { $url=wp_get_referer()?:home_url('/'); wp_safe_redirect(add_query_arg('zmp_certificate_status',sanitize_key($status),$url)); exit; }
}
