<?php
if (!defined('ABSPATH')) { exit; }

/** Teacher registration for non-knowledge-test categories and private school rosters. */
class ZMP_Category_Registrations {
    const SHORTCODE = 'zmp_category_entries';
    private static $archive_temp_files = array();

    public static function init() {
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render_portal'));
        add_action('init', array(__CLASS__, 'handle_request'), 1);
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'zmp_category_entries';
        $collate = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            group_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_school_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(150) NOT NULL,
            region VARCHAR(30) DEFAULT '' NOT NULL,
            participant_name VARCHAR(255) NOT NULL,
            grade SMALLINT NOT NULL,
            participant_type VARCHAR(20) NOT NULL,
            payer_type VARCHAR(20) NOT NULL DEFAULT 'teacher',
            fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            roster_filename VARCHAR(100) DEFAULT '' NOT NULL,
            matrix_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            piece_one_title VARCHAR(255) NOT NULL DEFAULT '',
            piece_one_author VARCHAR(190) NOT NULL DEFAULT '',
            piece_two_title VARCHAR(255) NOT NULL DEFAULT '',
            piece_two_author VARCHAR(190) NOT NULL DEFAULT '',
            participant_fingerprint CHAR(64) DEFAULT NULL,
            roster_drive_file_id VARCHAR(190) DEFAULT '' NOT NULL,
            roster_storage_backend VARCHAR(16) DEFAULT 'local' NOT NULL,
            roster_original_name VARCHAR(255) DEFAULT '' NOT NULL,
            roster_sha256 CHAR(64) DEFAULT '' NOT NULL,
            registration_key CHAR(64) NOT NULL,
            status VARCHAR(20) DEFAULT 'submitted' NOT NULL,
            moderator_note TEXT NOT NULL,
            reviewed_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY registration_key (registration_key),
            UNIQUE KEY participant_fingerprint (participant_fingerprint),
            KEY season_category (season_id,category_id),
            KEY mentor_season (mentor_id,season_id),
            KEY group_id (group_id),
            KEY status (status)
        ) $collate;");
    }

    public static function handle_request() {
        if (isset($_GET['zmp_roster_download'])) { self::download_roster(); }
        if (self::posted_text('zmp_category_entry_action') !== 'submit') { return; }
        if (!is_user_logged_in() || !self::can_register()) { wp_die('Потребна е овластена сметка на наставник.', 'Нема пристап', array('response'=>403)); }
        if (!wp_verify_nonce(self::posted_text('_wpnonce'), 'zmp_submit_category_entry')) {
            wp_die('Безбедносната проверка не помина. Освежете ја страницата и обидете се повторно.', 'Неважечка проверка', array('response'=>403));
        }
        $user = wp_get_current_user();
        global $wpdb;
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d", $user->ID));
        if (!$mentor) { self::redirect('mentor_profile_incomplete'); }
        ZMP_Mentor_Schools::ensure_primary($mentor);
        $school_id = self::posted_uint('mentor_school_id');
        $school = $school_id ? ZMP_Mentor_Schools::owned_school((int)$mentor->id, $school_id) : null;
        if (!$school_id) { $owned_schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id); if (count((array)$owned_schools) === 1) { $school = $owned_schools[0]; } }
        if (!$school) { self::redirect('school_required'); }
        $season = self::active_season();
        if (!$season) { self::redirect('no_active_season'); }
        $category_id = self::posted_uint('category_id');
        $group_id = self::posted_uint('group_id');
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id,c.category_code,c.name_mk,c.participant_type,m.id AS mapping_id
             FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories m ON m.category_id=c.id AND m.season_id=%d
             WHERE c.id=%d AND c.is_active=1 AND m.is_enabled=1 AND c.category_code<>'knowledge_test'", $season->id, $category_id
        ));
        if ($category && !ZMP_Registration_Windows::allows((int)$season->id, $category_id)) { self::redirect('registration_closed'); }
        $group = $category ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_groups WHERE id=%d AND category_id=%d AND is_active=1", $group_id,$category_id)) : null;
        $grade = self::posted_uint('grade');
        $name = trim(self::posted_text('participant_name'));
        $piece_one_title=trim(self::posted_text('piece_one_title'));$piece_one_author=trim(self::posted_text('piece_one_author'));$piece_two_title=trim(self::posted_text('piece_two_title'));$piece_two_author=trim(self::posted_text('piece_two_author'));
        if (!$category || !$group || $grade < (int)$group->grade_start || $grade > (int)$group->grade_end || $name==='' || !in_array($category->participant_type,array('individual','group'),true) || $piece_one_title==='' || $piece_one_author==='' || ($category->participant_type==='group' && ($piece_two_title==='' || $piece_two_author===''))) { self::redirect('invalid_entry'); }
        // Rows created before school-aware registration used a key without mentor_school_id.
        // Keep those rows protected against duplicate submissions from the same school.
        $legacy_key = self::make_legacy_registration_key((int)$season->id,$category_id,$group_id,(int)$mentor->id,$name);
        $legacy_duplicate = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}zmp_category_entries
             WHERE registration_key=%s AND season_id=%d AND category_id=%d AND group_id=%d AND mentor_id=%d
               AND (mentor_school_id=%d OR (mentor_school_id=0 AND school_name=%s AND municipality=%s))
             LIMIT 1",
            $legacy_key,(int)$season->id,$category_id,$group_id,(int)$mentor->id,(int)$school->id,
            sanitize_text_field($school->school_name),sanitize_text_field($school->municipality)
        ));
        if ($legacy_duplicate || self::identity_exists((int)$season->id,$category_id,$group_id,(int)$school->id,$name)) { self::redirect('duplicate_entry'); }
        $regional_mode = ZMP_YouTube::regional_delivery_mode((int)$season->id,$category_id,(string)$school->region); if($regional_mode==='unconfigured')self::redirect('youtube_mode_unavailable'); $youtube_draft = $regional_mode==='online';
        if ($youtube_draft && !ZMP_YouTube::is_connected()) { self::redirect('youtube_not_connected'); }
        $matrix_id=self::posted_uint('matrix_id');
        if($category->participant_type==='individual' && !ZMP_Matrix_Catalog::valid_matrix($matrix_id,$category_id)){self::redirect('matrix_required');}
        $documents = ZMP_Registration_Requirements::receive_entry_documents((int)$season->id,$category_id,(int)$school->id);
        if (is_wp_error($documents)) { self::redirect($documents->get_error_code()); }
        $payer_type=self::posted_text('payer_type');if(!in_array($payer_type,array('teacher','school'),true)){ZMP_Registration_Requirements::remove_entry_document_files(array_values(array_filter(array($documents['application']??null,$documents['proof']??null,$documents['consent']??null,$documents['sheet_music']??null))));self::redirect('invalid_entry');}
        $roster = array('filename'=>'','drive_file_id'=>'','storage_backend'=>'local','original_name'=>'','sha256'=>'','path'=>'');
        if ($category->participant_type==='group') {
            $roster = self::store_roster('student_roster',(int)$season->id,$category_id);
            if (is_wp_error($roster)) { ZMP_Registration_Requirements::remove_entry_document_files(array_values(array_filter(array($documents['application']??null,$documents['proof']??null,$documents['consent']??null,$documents['sheet_music']??null)))); self::redirect($roster->get_error_code()); }
        }
        $data = array(
            'season_id'=>(int)$season->id,'category_id'=>$category_id,'group_id'=>$group_id,'mentor_id'=>(int)$mentor->id,'wp_user_id'=>(int)$user->ID,'mentor_school_id'=>(int)$school->id,
            'school_name'=>sanitize_text_field($school->school_name),'municipality'=>sanitize_text_field($school->municipality),'region'=>$school->region,'participant_name'=>$name,'grade'=>$grade,
            'participant_type'=>$category->participant_type,'matrix_id'=>$matrix_id,'piece_one_title'=>$piece_one_title,'piece_one_author'=>$piece_one_author,'piece_two_title'=>$piece_two_title,'piece_two_author'=>$piece_two_author,'participant_fingerprint'=>self::participant_fingerprint((int)$season->id,$category_id,$group_id,(int)$school->id,$name),'payer_type'=>$payer_type,'fee_amount'=>(float)$documents['fee_amount'],'roster_filename'=>$roster['filename'],'roster_drive_file_id'=>$roster['drive_file_id'],'roster_storage_backend'=>$roster['storage_backend'],'roster_original_name'=>$roster['original_name'],'roster_sha256'=>$roster['sha256'],
            'registration_key'=>self::make_registration_key((int)$season->id,$category_id,$group_id,(int)$mentor->id,$name,(int)$school->id),
            'status'=>$youtube_draft?'draft':'submitted',
        );
        if (!$wpdb->insert($wpdb->prefix.'zmp_category_entries',$data)) {
            if (!empty($roster['drive_file_id'])) { ZMP_Google_Drive::delete_private_file($roster['drive_file_id']); }
            ZMP_Registration_Requirements::remove_entry_document_files(array_values(array_filter(array($documents['application']??null,$documents['proof']??null,$documents['consent']??null,$documents['sheet_music']??null))));
            self::redirect(stripos((string)$wpdb->last_error,'Duplicate')!==false?'duplicate_entry':'save_error');
        }
        if (!ZMP_Registration_Requirements::save_entry_documents((int)$season->id,(int)$wpdb->insert_id,$documents,$category->participant_type==='group'?$roster:null)) {
            $wpdb->delete($wpdb->prefix.'zmp_category_entries',array('id'=>(int)$wpdb->insert_id));
            if(!empty($roster['drive_file_id']))ZMP_Google_Drive::delete_private_file($roster['drive_file_id']);
            self::redirect('save_error');
        }
        if ($youtube_draft) { self::redirect('entry_draft'); }
        ZMP_Communications::notify_teacher((int)$user->ID,'Примена пријава: '.$category->name_mk,'Пријавата за учесникот/ансамблот „'.$name.'“ е примена за '.$season->title.'.',$season->academic_year,$category_id,'');
        self::redirect('entry_submitted');
    }

    public static function render_portal($atts = array()) {
        if (!is_user_logged_in() || !self::can_register()) { return '<div class="zmp-alert">Овој дел е достапен за најавени наставници.</div>'; }
        $atts = shortcode_atts(array('registration_hub'=>'0','entries_only'=>'0'), (array)$atts, self::SHORTCODE);
        $registration_hub = $atts['registration_hub'] === '1';
        $entries_only = $atts['entries_only'] === '1';
        $season = self::active_season();
        if (!$season) { return '<div class="zmp-alert">Нема активна натпреварувачка година.</div>'; }
        global $wpdb;
        $user_id = get_current_user_id();
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d",$user_id));
        if (!$mentor) { return '<div class="zmp-alert">Прво пополнете го профилот на наставникот.</div>'; }
        ZMP_Mentor_Schools::ensure_primary($mentor);
        $schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id);
        if (!$schools) { return '<div class="zmp-alert">Додајте најмалку едно училиште во табот „Профил и регион“ пред пријавување.</div>'; }
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id,c.name_mk,c.category_code,c.participant_type,g.id AS group_id,g.name_mk AS group_name,g.grade_start,g.grade_end,g.school_track
             FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1
             JOIN {$wpdb->prefix}zmp_category_groups g ON g.category_id=c.id AND g.is_active=1
             WHERE c.is_active=1 AND c.category_code<>'knowledge_test' ORDER BY c.sort_order,g.sort_order", $season->id
        ));
        $categories = array_values(array_filter((array)$categories, function($row) use ($season) { return ZMP_Registration_Windows::allows((int)$season->id, (int)$row->id); }));
        $entry_region = isset($_GET['zmp_entry_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_entry_region'])) ? sanitize_key(wp_unslash($_GET['zmp_entry_region'])) : '';
        $entry_municipality = sanitize_text_field(wp_unslash($_GET['zmp_entry_municipality'] ?? ''));
        $entry_school = sanitize_text_field(wp_unslash($_GET['zmp_entry_school'] ?? ''));
        $entries_sql = "SELECT e.*,c.name_mk AS category_name,c.category_code,g.name_mk AS group_name FROM {$wpdb->prefix}zmp_category_entries e
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id
             WHERE e.mentor_id=%d AND e.season_id=%d";
        $entries_args = array((int)$mentor->id,(int)$season->id);
        if ($entry_region !== '') { $entries_sql .= ' AND e.region=%s'; $entries_args[] = $entry_region; }
        if ($entry_municipality !== '') { $entries_sql .= ' AND e.municipality=%s'; $entries_args[] = $entry_municipality; }
        if ($entry_school !== '') { $entries_sql .= ' AND e.school_name LIKE %s'; $entries_args[] = '%' . $wpdb->esc_like($entry_school) . '%'; }
        $entries = $wpdb->get_results($wpdb->prepare($entries_sql . ' ORDER BY e.created_at DESC,e.id DESC',$entries_args));
        ob_start(); self::render_notice(); ?>
        <?php if (!$entries_only): ?>
        <section class="zmp-card zmp-category-registration"><h3>Пријави учесник или состав за <?php echo esc_html($season->title); ?></h3>
        <?php if (!$categories): ?><p>Моментално нема категории со отворен рок за пријавување.</p><?php else: ?>
        <form method="post" enctype="multipart/form-data" class="zmp-form">
            <?php wp_nonce_field('zmp_submit_category_entry'); ?><input type="hidden" name="zmp_category_entry_action" value="submit">
            <label <?php echo $registration_hub ? 'hidden aria-hidden="true"' : ''; ?>>Училиште *<select name="mentor_school_id" id="zmp-entry-school-id" required><option value="">Изберете училиште</option><?php foreach($schools as $school): ?><option value="<?php echo (int)$school->id; ?>"><?php echo esc_html($school->school_name.' · '.$school->municipality.' · '.ZMP_Regions::label($school->region)); ?></option><?php endforeach; ?></select></label>
            <label <?php echo $registration_hub ? 'hidden aria-hidden="true"' : ''; ?>>Категорија *<select name="category_id" id="zmp-entry-category" required><option value="">Изберете</option>
            <?php $seen=array(); foreach($categories as $item): if(isset($seen[$item->id]))continue; $seen[$item->id]=true; ?><option value="<?php echo (int)$item->id; ?>" data-type="<?php echo esc_attr($item->participant_type); ?>" data-code="<?php echo esc_attr($item->category_code); ?>"><?php echo esc_html($item->name_mk); ?></option><?php endforeach; ?></select></label>
            <label>Група *<select name="group_id" id="zmp-entry-group" required><option value="">Изберете</option>
            <?php foreach($categories as $item): ?><option value="<?php echo (int)$item->group_id; ?>" data-category="<?php echo (int)$item->id; ?>" data-start="<?php echo (int)$item->grade_start; ?>" data-end="<?php echo (int)$item->grade_end; ?>" hidden><?php echo esc_html($item->group_name.($item->school_track?' — '.$item->school_track:'')); ?></option><?php endforeach; ?></select></label>
            <label>Одделение / година *<select name="grade" id="zmp-entry-grade" required><option value="">Прво изберете група</option></select></label>
            <label><span id="zmp-entry-name-label">Име на учесник / состав *</span><input type="text" name="participant_name" maxlength="255" required></label>
            <div class="zmp-form-row"><label><span id="zmp-piece-one-label">Наслов на песна *</span><input name="piece_one_title" maxlength="255" required></label><label>Автор / изведувач *<input name="piece_one_author" maxlength="190" required></label></div><div class="zmp-form-row" id="zmp-piece-two" hidden><label><span id="zmp-piece-two-label">Изборна композиција *</span><input name="piece_two_title" maxlength="255" disabled></label><label>Автор / изведувач 2 *<input name="piece_two_author" maxlength="190" disabled></label></div><p id="zmp-piece-help">За вокална интерпретација внесете една песна.</p><div id="zmp-sheet-music-wrap" hidden><label>Партитура (PDF/JPG/PNG; по избор)<input type="file" name="sheet_music" accept=".pdf,.jpg,.jpeg,.png" disabled></label><p>Партитура се прикачува само за училишен хор или училишен оркестар.</p></div><script>(function(){var c=document.getElementById("zmp-entry-category"),w=document.getElementById("zmp-piece-two"),h=document.getElementById("zmp-piece-help"),s=document.getElementById("zmp-sheet-music-wrap"),a=document.querySelector("[name=piece_two_title]"),b=document.querySelector("[name=piece_two_author]"),file=document.querySelector("[name=sheet_music]"),one=document.getElementById("zmp-piece-one-label"),two=document.getElementById("zmp-piece-two-label");function sync(){var o=c&&c.options[c.selectedIndex],code=o?o.dataset.code:"",group=o&&o.dataset.type==="group",band=code==="school_bands",scores=code==="school_choir"||code==="school_orchestra";if(w)w.hidden=!group;if(a){a.disabled=!group;a.required=!!group;}if(b){b.disabled=!group;b.required=!!group;}if(one)one.textContent=band?"Изборна композиција 1 *":(scores?"Задолжителна композиција *":"Наслов на песна *");if(two)two.textContent=band?"Изборна композиција 2 *":"Изборна композиција *";if(h)h.textContent=band?"За училишен бенд внесете две изборни композиции.":(scores?"За училишен хор и оркестар внесете задолжителна и изборна композиција.":"За вокална интерпретација внесете една песна.");if(s)s.hidden=!scores;if(file)file.disabled=!scores;}if(c){c.addEventListener("change",sync);sync();}})();</script>
            <label>Кој ја плаќа котизацијата<select name="payer_type" required><option value="teacher">Наставникот ја уплатува котизацијата</option><option value="school">Училиштето плаќа со фактура</option></select></label>
            <div id="zmp-entry-documents"><label>Потпишан и архивиран формулар за пријава (PDF/JPG/PNG, најмногу 15 MB) *<input type="file" name="signed_application" accept=".pdf,.jpg,.jpeg,.png" required></label><label>Потврда за платена котизација (PDF/JPG/PNG, најмногу 15 MB) *<input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" required></label><p>Износот за избраната категорија се уредува годишно од модераторите. Прво испратете го барањето за уплата или фактурата.</p></div>
            <div id="zmp-entry-roster-wrap" hidden><label>Училишен список * (PDF, DOCX, XLSX или CSV; најмногу 10 MB)<input type="file" name="student_roster" accept=".pdf,.docx,.xlsx,.csv"></label>
            <p>Прикачете список на ученици архивиран во училиштето. Фајлот се чува во приватна папка надвор од веб-директориумот.</p></div>
            <label>Потпишана согласност за фотографирање, снимање и објавување материјал само за потребите на државните натпревари на здружението (PDF/JPG/PNG, до 15 MB) *<input type="file" name="media_consent" accept=".pdf,.jpg,.jpeg,.png" required></label>
            <?php echo ZMP_Matrix_Catalog::render_picker(); ?>
            <button class="zmp-btn zmp-btn-success" type="submit">Поднеси пријава</button>
        </form><?php endif; ?></section>
        <?php
        $teacher_billing_controls = ZMP_Registration_Requirements::render_teacher_controls($season,$categories);
        if ($registration_hub && $teacher_billing_controls !== ''):
        ?>
        <section class="zmp-registration-billing" aria-labelledby="zmp-registration-billing-heading">
            <h3 id="zmp-registration-billing-heading">Котизација</h3>
            <?php echo $teacher_billing_controls; ?>
        </section>
        <?php else: echo $teacher_billing_controls; endif; ?>
        <?php endif; ?>
        <?php if (!$registration_hub || $entries_only): ?>
        <section class="zmp-card"><h3>Мои пријави — <?php echo esc_html($season->title); ?></h3>
        <form method="get" class="zmp-filter-bar"><?php if(isset($_GET['zmp_portal_tab'])): ?><input type="hidden" name="zmp_portal_tab" value="nastavnici"><?php endif; ?><label>Регион <select name="zmp_entry_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($entry_region,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_entry_municipality" list="zmp-entry-municipality-options" value="<?php echo esc_attr($entry_municipality); ?>"></label><datalist id="zmp-entry-municipality-options"><?php foreach(ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist><label>Училиште <input name="zmp_entry_school" value="<?php echo esc_attr($entry_school); ?>"></label><button class="zmp-btn zmp-btn-secondary">Филтрирај пријави</button></form>
        <?php if(!$entries): ?><p>Сè уште нема пријави.</p><?php else: ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Група</th><th>Учесник / состав</th><th>Одделение / година</th><th>Училиште</th><th>Регион</th><th>Општина</th><th>Список</th><th>Видео</th><th>Статус</th><th>Дипломи</th><th>Поднесено</th></tr></thead><tbody>
        <?php foreach($entries as $entry): ?><tr><td><?php echo esc_html($entry->category_name); ?></td><td><?php echo esc_html($entry->group_name); ?></td><td><?php echo esc_html($entry->participant_name); ?></td><td><?php echo (int)$entry->grade; ?></td><td><?php echo esc_html($entry->school_name); ?></td><td><?php echo esc_html(ZMP_Regions::label($entry->region)); ?></td><td><?php echo esc_html($entry->municipality); ?></td><td><?php if($entry->roster_filename||$entry->roster_drive_file_id): ?><a href="<?php echo esc_url(self::roster_url($entry)); ?>">Преземи список</a><?php else: ?>—<?php endif; ?><?php echo ZMP_Registration_Requirements::render_entry_document_links($entry->id); ?><?php echo class_exists('ZMP_Document_Review')?ZMP_Document_Review::render_teacher_repair((int)$entry->id):''; ?></td><td><?php self::render_video_upload($entry); ?></td><td><?php echo esc_html(self::status_label($entry->status)); ?><?php if(!empty($entry->moderator_note)): ?><br><small><?php echo nl2br(esc_html($entry->moderator_note)); ?></small><?php endif; ?></td><td><?php echo ZMP_Governance::render_entry_certificates((int)$entry->id); ?></td><td><?php echo esc_html($entry->created_at); ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?></section>
        <?php endif; ?>
        <script>(function(){var c=document.getElementById('zmp-entry-category'),g=document.getElementById('zmp-entry-group'),gr=document.getElementById('zmp-entry-grade'),w=document.getElementById('zmp-entry-roster-wrap'),f=w&&w.querySelector('input'),l=document.getElementById('zmp-entry-name-label');if(!c||!g||!gr)return;function refresh(){var id=c.value,type=c.options[c.selectedIndex]?c.options[c.selectedIndex].dataset.type:'';Array.from(g.options).forEach(function(o){if(o.value){o.hidden=o.dataset.category!==id;o.disabled=o.hidden;}});if(g.selectedOptions[0]&&g.selectedOptions[0].hidden)g.value='';var o=g.selectedOptions[0],a=o?parseInt(o.dataset.start,10):0,b=o?parseInt(o.dataset.end,10):0;gr.innerHTML='<option value="">Изберете</option>';for(var n=a;n&&n<=b;n++){var x=document.createElement('option');x.value=n;x.textContent=n;gr.appendChild(x);}if(w)w.hidden=type!=='group';if(f)f.required=type==='group';if(l)l.textContent=type==='group'?'Име на хор / оркестар / бенд *':'Име и презиме на учесникот *';}c.addEventListener('change',refresh);g.addEventListener('change',refresh);refresh();})();</script>
        <?php return ob_get_clean();
    }

    /** Display private Drive video uploads and phase-specific upload controls for the owner's entry. */
    private static function render_video_upload($entry) {
        $mode=ZMP_YouTube::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region);
        if(in_array($mode,array('online','physical','unconfigured'),true)) { echo ZMP_YouTube::render_entry_controls($entry); return; }
        if(($entry->status??'')==='draft') { echo ZMP_YouTube::render_physical_draft_finalize($entry); return; }
        echo '<small>Видеа не се прикачуваат на Drive; нов YouTube upload е достапен само за регионалното натпреварувачко ниво.</small>';
    }
    public static function make_registration_key($season,$category,$group,$mentor,$name,$school_id=0) {
        $clean=trim(preg_replace('/\s+/u',' ',$name));
        $clean=function_exists('mb_strtolower')?mb_strtolower($clean,'UTF-8'):strtolower($clean);
        return hash('sha256',implode('|',array((int)$season,(int)$category,(int)$group,(int)$mentor,(int)$school_id,$clean)));
    }

    /** Build the pre-.31 duplicate key so upgraded rows remain protected. */
    public static function make_legacy_registration_key($season,$category,$group,$mentor,$name) {
        $clean=trim(preg_replace('/\s+/u',' ',$name));
        $clean=function_exists('mb_strtolower')?mb_strtolower($clean,'UTF-8'):strtolower($clean);
        return hash('sha256',implode('|',array((int)$season,(int)$category,(int)$group,(int)$mentor,$clean)));
    }
    public static function active_season() {
        global $wpdb; return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE status='active' ORDER BY id DESC LIMIT 1");
    }

    /** Count actual registrations for the phase-policy resolver. */
    public static function count_for_category($season_id,$category_id) {
        global $wpdb;
        $season=$wpdb->get_row($wpdb->prepare("SELECT legacy_competition_id FROM {$wpdb->prefix}zmp_seasons WHERE id=%d",absint($season_id)));
        if(!$season)return 0;
        $code=$wpdb->get_var($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",absint($category_id)));
        if($code==='knowledge_test') {
            if(!(int)$season->legacy_competition_id)return 0;
            return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_students WHERE competition_id=%d",(int)$season->legacy_competition_id));
        }
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_entries WHERE season_id=%d AND category_id=%d",absint($season_id),absint($category_id)));
    }

    /** Return private roster references for a year, suitable for verified archive/reset operations. */
    public static function roster_rows_for_season($season_id=0) {
        global $wpdb;
        $table=$wpdb->prefix.'zmp_category_entries';
        if($season_id)return $wpdb->get_results($wpdb->prepare("SELECT id,season_id,roster_filename,roster_drive_file_id,roster_storage_backend,roster_original_name,roster_sha256 FROM $table WHERE season_id=%d AND (roster_filename<>'' OR roster_drive_file_id<>'')",absint($season_id)),ARRAY_A);
        return $wpdb->get_results("SELECT id,season_id,roster_filename,roster_drive_file_id,roster_storage_backend,roster_original_name,roster_sha256 FROM $table WHERE roster_filename<>'' OR roster_drive_file_id<>''",ARRAY_A);
    }

    public static function migrate_legacy_rosters($limit=10) {
        global $wpdb; $limit=max(1,min(20,absint($limit))); $dir=self::private_directory();
        if(is_wp_error($dir))return array('moved'=>0,'failed'=>1,'remaining'=>-1);
        $table=$wpdb->prefix.'zmp_category_entries';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT e.*,c.category_code FROM $table e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id WHERE e.roster_storage_backend='local' AND e.roster_filename<>'' ORDER BY e.id LIMIT %d",$limit),ARRAY_A);
        $remaining=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE roster_storage_backend='local' AND roster_filename<>''");$moved=0;$failed=0;
        foreach((array)$rows as $row){$filename=(string)$row['roster_filename'];if(!preg_match('/^[a-f0-9]{48}\\.(pdf|docx|xlsx|csv)$/',$filename)){$failed++;continue;}$path=trailingslashit($dir).$filename;$hash=is_file($path)?hash_file('sha256',$path):false;if(!is_string($hash)||!hash_equals((string)$row['roster_sha256'],$hash)){$failed++;continue;}
            $mime=function_exists('mime_content_type')?mime_content_type($path):'application/octet-stream';$drive=ZMP_Google_Drive::upload_private_document($path,(int)filesize($path),$mime?:'application/octet-stream',$row['roster_original_name'],$row['season_id'],$row['category_id'],'student-rosters');if(is_wp_error($drive)){$failed++;continue;}
            $tmp=ZMP_Google_Drive::download_private_file_to_temp($drive);$verified=!is_wp_error($tmp)&&hash_equals((string)$row['roster_sha256'],hash_file('sha256',$tmp));if(is_string($tmp)&&is_file($tmp))@unlink($tmp);if(!$verified){ZMP_Google_Drive::delete_private_file($drive);$failed++;continue;}
            $saved=$wpdb->update($table,array('roster_filename'=>'','roster_drive_file_id'=>$drive,'roster_storage_backend'=>'drive'),array('id'=>(int)$row['id']));if($saved===false){ZMP_Google_Drive::delete_private_file($drive);$failed++;continue;}if(!wp_delete_file($path)){$failed++;continue;}$moved++;
        }
        return array('moved'=>$moved,'failed'=>$failed,'remaining'=>max(0,$remaining-$moved));
    }

    /** Add verified uploaded lists to the protected yearly ZIP archive. */
    public static function add_rosters_to_archive($archive,$season_id) {
        global $wpdb;$rows=self::roster_rows_for_season($season_id);if(!$rows)return true;
        foreach($rows as $row){
            // .42 represents a roster as a reviewed entry document; avoid writing a second copy.
            if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_entry_documents WHERE entry_id=%d AND document_type='participant_roster'",(int)$row['id']))>0)continue;
            $temporary=false;
            if(($row['roster_storage_backend']??'local')==='drive'&&!empty($row['roster_drive_file_id'])){$path=ZMP_Google_Drive::download_private_file_to_temp($row['roster_drive_file_id']);if(is_wp_error($path))return false;$temporary=true;}
            else{$filename=(string)$row['roster_filename'];if(!preg_match('/^[a-f0-9]{48}\\.(pdf|docx|xlsx|csv)$/',$filename))return false;$dir=self::private_directory();if(is_wp_error($dir))return false;$path=trailingslashit($dir).$filename;}
            $hash=is_file($path)?hash_file('sha256',$path):false;
            if(!is_string($hash)||!hash_equals((string)$row['roster_sha256'],$hash)){if($temporary)@unlink($path);return false;}
            $original=sanitize_file_name((string)$row['roster_original_name']);if($original==='')$original='roster.'.pathinfo((string)$row['roster_filename'],PATHINFO_EXTENSION);
            $archive_path='rosters/entry-'.$row['id'].'/'.$original;
            try{$added=$archive->addFile($path,$archive_path);}catch(Throwable $error){if($temporary)@unlink($path);return false;}
            if($temporary)self::$archive_temp_files[]=$path;
            if($archive instanceof ZipArchive && !$added)return false;
        }
        return true;
    }

    public static function cleanup_archive_temp_files() { foreach(self::$archive_temp_files as $path) if(is_string($path)&&is_file($path)) @unlink($path); self::$archive_temp_files=array(); }

    /** Stream a roster after the caller has checked the entry-document owner/reviewer scope. */
    public static function stream_roster_document($document) {
        global $wpdb;$entry=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d",absint($document->entry_id)));
        if(!$entry||!hash_equals((string)$entry->roster_sha256,(string)$document->sha256))wp_die('Списокот не помина проверка на интегритет.','Недостапно',array('response'=>404));
        $temporary=false;
        if(($document->storage_backend??'local')==='drive'&&!empty($document->drive_file_id)){$path=ZMP_Google_Drive::download_private_file_to_temp($document->drive_file_id);if(is_wp_error($path))wp_die('Приватниот список не може да се преземе од Drive.','Недостапно',array('response'=>503));$temporary=true;}
        else{$dir=self::private_directory();$filename=(string)$document->file_key;if(is_wp_error($dir)||!preg_match('/^[a-f0-9]{48}\.(pdf|docx|xlsx|csv)$/',$filename))wp_die('Списокот не е достапен.','Недостапно',array('response'=>404));$path=trailingslashit($dir).$filename;}
        $hash=is_file($path)?hash_file('sha256',$path):false;if(!is_string($hash)||!hash_equals((string)$document->sha256,$hash)){if($temporary)@unlink($path);wp_die('Списокот не помина проверка на интегритет.','Недостапно',array('response'=>404));}
        nocache_headers();header('X-Content-Type-Options: nosniff');header('Content-Type: '.sanitize_mime_type($document->mime_type));header('Content-Length: '.filesize($path));$inline=in_array(strtolower((string)$document->mime_type),array('application/pdf','image/jpeg','image/png'),true);header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.rawurlencode($document->original_name).'"');readfile($path);if($temporary)@unlink($path);exit;
    }

    /** Include current or superseded roster files in the protected archive. */
    public static function add_roster_document_to_archive($archive,$row,$archive_path) {
        $temporary=false;
        if(($row['storage_backend']??'local')==='drive'&&!empty($row['drive_file_id'])){$path=ZMP_Google_Drive::download_private_file_to_temp($row['drive_file_id']);if(is_wp_error($path))return false;$temporary=true;}
        else{$filename=(string)($row['file_key']??'');if(!preg_match('/^[a-f0-9]{48}\.(pdf|docx|xlsx|csv)$/',$filename))return false;$dir=self::private_directory();if(is_wp_error($dir))return false;$path=trailingslashit($dir).$filename;}
        $hash=is_file($path)?hash_file('sha256',$path):false;if(!is_string($hash)||!hash_equals((string)$row['sha256'],$hash)){if($temporary)@unlink($path);return false;}
        try{$added=$archive->addFile($path,$archive_path);}catch(Throwable $error){if($temporary)@unlink($path);return false;}if($temporary)self::$archive_temp_files[]=$path;return !($archive instanceof ZipArchive)||$added;
    }

    /** Remove only validated, database-referenced roster files after reset commits. */
    public static function remove_roster_files(array $rows) {
        if(!$rows)return true;$ok=true;
        foreach($rows as $row){$v=function($k)use($row){return is_array($row)?($row[$k]??''):($row->$k??'');};if($v('roster_storage_backend')==='drive'&&$v('roster_drive_file_id')!==''){$deleted=ZMP_Google_Drive::delete_private_file($v('roster_drive_file_id'));if(is_wp_error($deleted))$ok=false;continue;}$filename=(string)$v('roster_filename');if(!preg_match('/^[a-f0-9]{48}\\.(pdf|docx|xlsx|csv)$/',$filename)){$ok=false;continue;}$dir=self::private_directory();if(is_wp_error($dir)){$ok=false;continue;}$path=trailingslashit($dir).$filename;if(is_file($path)&&!wp_delete_file($path))$ok=false;}
        return $ok;
    }

    private static function store_roster($field,$season_id,$category_id) {
        if(empty($_FILES[$field]) || !isset($_FILES[$field]['error']) || (int)$_FILES[$field]['error']!==UPLOAD_ERR_OK) return new WP_Error('roster_required','Прикачете училишен список.');
        $f=$_FILES[$field]; if(!is_uploaded_file($f['tmp_name']) || (int)$f['size']<1 || (int)$f['size']>10*MB_IN_BYTES) return new WP_Error('roster_invalid','Фајлот е невалиден или е поголем од 10 MB.');
        $original=sanitize_file_name(wp_unslash($f['name'])); $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
        if(!in_array($ext,array('pdf','docx','xlsx','csv'),true) || !self::valid_signature($f['tmp_name'],$ext)) return new WP_Error('roster_type_invalid','Дозволени се валидни PDF, DOCX, XLSX или CSV документи.');
        $mime=function_exists('mime_content_type')?mime_content_type($f['tmp_name']):'application/octet-stream';
        $drive_id=ZMP_Google_Drive::upload_private_document($f['tmp_name'],(int)$f['size'],$mime?:'application/octet-stream',$original,$season_id,$category_id,'student-rosters');
        if(is_wp_error($drive_id))return new WP_Error('roster_store_failed','Не успеав да го зачувам списокот во приватниот Drive.');
        return array('filename'=>'','drive_file_id'=>$drive_id,'storage_backend'=>'drive','original_name'=>$original,'mime_type'=>$mime?:'application/octet-stream','file_size'=>(int)$f['size'],'sha256'=>hash_file('sha256',$f['tmp_name']),'path'=>'');
    }

    private static function valid_signature($path,$ext) {
        $h=@fopen($path,'rb'); if(!$h)return false; $head=fread($h,8); fclose($h);
        if($ext==='pdf')return strncmp($head,'%PDF-',5)===0;
        if($ext==='csv')return strpos($head,"\0")===false;
        return strncmp($head,"PK\x03\x04",4)===0;
    }

    private static function private_directory() {
        $docroot=!empty($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:ABSPATH;
        $root=realpath($docroot); $parent=$root?realpath(dirname($root)):false;
        if(!$root||!$parent||self::path_is_inside($parent,$root))return new WP_Error('roster_storage_unsafe','Безбедна приватна папка не е достапна.');
        $dir=trailingslashit($parent).'zmp-private-rosters-'.substr(hash('sha256',home_url('/')),0,12);
        if(!is_dir($dir)&&!wp_mkdir_p($dir))return new WP_Error('roster_storage_failed','Не може да се создаде приватната архива.');
        $real=realpath($dir); if(!$real||self::path_is_inside($real,$root))return new WP_Error('roster_storage_unsafe','Архивата не е надвор од веб-папката.');
        @chmod($real,0700); return $real;
    }

    private static function path_is_inside($path,$root) {
        $path=rtrim(str_replace('\\','/',$path),'/').'/';
        $root=rtrim(str_replace('\\','/',$root),'/').'/';
        if(DIRECTORY_SEPARATOR==='\\'){$path=strtolower($path);$root=strtolower($root);}
        return strpos($path,$root)===0;
    }

    private static function download_roster() {
        if(!is_user_logged_in()||(!self::can_register()&&!current_user_can('zmp_moderator_cap')))wp_die('Немате пристап.','Нема пристап',array('response'=>403));
        $id=isset($_GET['zmp_roster_download'])&&is_scalar($_GET['zmp_roster_download'])?absint(wp_unslash($_GET['zmp_roster_download'])):0;
        $nonce=isset($_GET['_wpnonce'])&&is_scalar($_GET['_wpnonce'])?sanitize_text_field(wp_unslash((string)$_GET['_wpnonce'])):'';
        if(!$id||!wp_verify_nonce($nonce,'zmp_download_roster_'.$id))wp_die('Неважечка безбедносна проверка.','Нема пристап',array('response'=>403));
        global $wpdb; $owner=(current_user_can('manage_options')||current_user_can('zmp_moderator_cap'))?'':$wpdb->prepare(' AND e.wp_user_id=%d',get_current_user_id());
        $entry=$wpdb->get_row($wpdb->prepare("SELECT e.* FROM {$wpdb->prefix}zmp_category_entries e WHERE e.id=%d$owner",$id));
        if(!$entry||(!$entry->roster_filename&&!$entry->roster_drive_file_id))wp_die('Списокот не е пронајден.','Нема пристап',array('response'=>404));
        $temporary=false;
        if(($entry->roster_storage_backend??'local')==='drive'&&!empty($entry->roster_drive_file_id)){$path=ZMP_Google_Drive::download_private_file_to_temp($entry->roster_drive_file_id);if(is_wp_error($path))wp_die('Приватниот список не може да се преземе од Drive.','Недостапно',array('response'=>503));$temporary=true;}
        else{$dir=self::private_directory(); $path=is_wp_error($dir)?'':trailingslashit($dir).$entry->roster_filename;}
        $actual_hash=$path&&is_file($path)?hash_file('sha256',$path):false;
        if(!is_string($actual_hash)||!hash_equals($entry->roster_sha256,$actual_hash)){if($temporary)@unlink($path);wp_die('Приватниот документ не е достапен.','Грешка',array('response'=>404));}
        nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Type: '.(function_exists('mime_content_type')?mime_content_type($path):'application/octet-stream'));
        header('Content-Length: '.filesize($path)); header('Content-Disposition: attachment; filename="'.rawurlencode($entry->roster_original_name).'"'); readfile($path); if($temporary)@unlink($path); exit;
    }

    private static function roster_url($entry) { return wp_nonce_url(add_query_arg('zmp_roster_download',(int)$entry->id,get_permalink()),'zmp_download_roster_'.(int)$entry->id); }
    private static function render_notice() {
        $status = isset($_GET['zmp_video_status']) && is_scalar($_GET['zmp_video_status']) ? sanitize_key(wp_unslash($_GET['zmp_video_status'])) : '';
        $messages = array(
            'video_uploaded' => 'Видеото е зачувано приватно во Drive.',
            'video_phase_invalid' => 'Избраното натпреварувачко ниво не е активно.',
            'video_too_large' => 'Видеото го надминува дозволениот лимит на серверот.',
            'video_invalid' => 'Прикачете поддржано и исправно видео.',
            'drive_upload_failed' => 'Drive не го прифати видеото. Проверете ја OAuth врската и обидете се повторно.',
            'video_database_failed' => 'Видеото е прикачено, но евиденцијата не е зачувана. Контактирајте администратор.',
        );
        if ($status && isset($messages[$status])) {
            echo '<p class="zmp-alert" role="status">' . esc_html($messages[$status]) . '</p>';
        }
        $m=array('entry_submitted'=>array('success','Пријавата е успешно поднесена.'),'entry_draft'=>array('warning','Пријавата е зачувана како нацрт. Прикачете ги потребните регионални видеа, почекајте YouTube да ја заврши обработката и потоа потврдете го поднесувањето.'),'youtube_not_connected'=>array('warning','Регионалното натпреварувачко ниво е онлајн, но YouTube каналот сè уште не е поврзан од администратор.'),'youtube_scope_invalid'=>array('error','Пријавата, избраното натпреварувачко ниво или позицијата на видеото не се достапни.'),'youtube_mode_unavailable'=>array('warning','Не може безбедно да се провери начинот на регионалното натпреварување. Пријавата не е поднесена; обидете се повторно или контактирајте модератор.'),'youtube_stage_closed'=>array('warning','Регионалната етапа е затворена; поставувањето видеа повеќе не е достапно.'),'youtube_upload_invalid'=>array('error','Видео фајлот е невалиден, неподдржан или го надминува лимитот на серверот.'),'youtube_upload_failed'=>array('error','YouTube не го заврши upload-от. Повторете со истиот видео фајл за продолжување.'),'youtube_upload_active'=>array('warning','Видеото сè уште се прикачува. Почекајте да заврши пред да започнете нов обид.'),'youtube_upload_uncertain'=>array('error','Статусот на upload сесијата не е сигурен. Не се создава нов upload за да се избегне дупликат; контактирајте модератор.'),'youtube_optional_video_skip_required'=>array('warning','Започнатото видео мора да биде довршено или експлицитно прескокнато пред поднесување.'),'youtube_database_failed'=>array('error','Сесијата за upload не можеше безбедно да се зачува. Обидете се повторно.'),'youtube_video_ready'=>array('success','Видеото е потврдено како Unlisted и обработката е завршена.'),'youtube_processing'=>array('warning','Видеото е прикачено како Unlisted, но YouTube сè уште го обработува. Проверете повторно пред да ја поднесете пријавата.'),'youtube_privacy_invalid'=>array('error','YouTube не потврди дека видеото е Unlisted. Пријавата останува нацрт; контактирајте модератор.'),'youtube_verify_failed'=>array('error','Статусот на видеото не може да се провери кај YouTube. Пријавата останува нацрт.'),'youtube_missing'=>array('error','Недостига едно или повеќе потребни видеа.'),'youtube_finalize_failed'=>array('error','Пријавата не можеше да се поднесе. Освежете ја страницата и проверете го статусот.'),'youtube_replace_not_supported'=>array('warning','Позицијата веќе има успешно прикачено видео. За замена побарајте помош од модератор за да не остане стар Unlisted фајл на каналот.'),'registration_closed'=>array('warning','Рокот за оваа категорија е затворен или сè уште не е отворен.'),'duplicate_entry'=>array('warning','Веќе постои пријава со ист учесник/состав за оваа категорија, група, училиште и година.'),'matrix_required'=>array('warning','За вокален интерпретатор изберете постојна матрица од каталогот.'),'invalid_entry'=>array('error','Проверете ги категоријата, групата, одделението и името.'),'roster_required'=>array('error','За групна категорија мора да прикачите училишен список.'),'roster_invalid'=>array('error','Фајлот е невалиден или поголем од 10 MB.'),'roster_type_invalid'=>array('error','Форматот не е поддржан или фајлот е оштетен.'),'roster_storage_unsafe'=>array('error','Приватната архива не е безбедно поставена.'),'roster_storage_failed'=>array('error','Не може да се подготви приватната архива.'),'roster_store_failed'=>array('error','Списокот не е зачуван; пријавата не е поднесена.'),'save_error'=>array('error','Пријавата не е зачувана. Контактирајте администратор.'),'mentor_profile_incomplete'=>array('warning','Потребни се училиште и општина во профилот.'),'school_required'=>array('warning','Изберете училиште од вашиот профил.'),'no_active_season'=>array('warning','Нема активна натпреварувачка година.'));
        $m['replacement_uploaded']=array('success','Исправениот документ е прикачен и чека повторна проверка.');
        $m['replacement_failed']=array('error','Прикачете исправен PDF/JPG/PNG документ до 15 MB. Пријавата останува непроменета.');
        $m['youtube_processing_failed']=array('error','YouTube јави дека обработката на видеото не успеала. Пријавата останува нацрт; контактирајте модератор и не прикачувајте дупликат.');
        $m['youtube_documents_missing']=array('error','Недостига потпишан формулар, доказ за уплата или задолжителната согласност. Пријавата останува нацрт. Контактирајте модератор.');
        $code=sanitize_key(wp_unslash($_GET['zmp_youtube_status']??$_GET['zmp_entry_status']??'')); if(isset($m[$code]))echo '<div class="zmp-alert zmp-alert-'.esc_attr($m[$code][0]).'" role="status">'.esc_html($m[$code][1]).'</div>';
    }
    private static function identity_exists($season,$category,$group,$school,$name){global $wpdb;return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_category_entries WHERE season_id=%d AND category_id=%d AND group_id=%d AND (mentor_school_id=%d OR (mentor_school_id=0 AND school_name=(SELECT school_name FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d LIMIT 1))) AND LOWER(TRIM(participant_name))=LOWER(TRIM(%s)) LIMIT 1",$season,$category,$group,$school,$school,$name));}
    private static function participant_fingerprint($season,$category,$group,$school,$name){$normalized=function_exists('mb_strtolower')?mb_strtolower(trim(preg_replace('/\s+/u',' ',$name)),'UTF-8'):strtolower(trim(preg_replace('/\s+/',' ',$name)));return hash('sha256',implode('|',array($season,$category,$group,$school,$normalized)));}
    private static function can_register() { $u=wp_get_current_user(); return current_user_can('manage_options')||current_user_can('zmp_teacher_cap')||current_user_can('view_zmp_teacher_dashboard')||in_array('zmp_teacher',(array)$u->roles,true); }
    private static function posted_text($key) { return isset($_POST[$key])&&is_scalar($_POST[$key])?sanitize_text_field(wp_unslash((string)$_POST[$key])):''; }
    private static function posted_uint($key) {
        if(!isset($_POST[$key])||!is_scalar($_POST[$key]))return 0;
        $raw=(string)wp_unslash($_POST[$key]);
        return preg_match('/^\d+$/',$raw)?absint($raw):0;
    }
    private static function status_label($s) { $labels=array('draft'=>'Нацрт — чека видео upload','submitted'=>'Поднесена','under_review'=>'Во проверка','needs_correction'=>'Потребна е исправка','complete'=>'Документите се целосни — чека конечна проверка','approved'=>'Одобрена','rejected'=>'Одбиена');return $labels[$s]??'Поднесена'; }
    private static function redirect($status) { if(class_exists('ZMP_Debug')){$level=in_array($status,array('save_error','entry_invalid','invalid_entry','mentor_profile_incomplete','no_active_season'),true)?'warning':'info';ZMP_Debug::log($level,'Category registration workflow finished.',array('status'=>sanitize_key($status)),'category_registration'); } $url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg(array('tab'=>'categories','zmp_entry_status'=>sanitize_key($status)),remove_query_arg(array('zmp_entry_status','zmp_youtube_status','zmp_roster_download','_wpnonce'),$url)));exit; }
}
