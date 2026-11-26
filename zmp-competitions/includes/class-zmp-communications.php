<?php
if (!defined('ABSPATH')) { exit; }

/** Moderator authored announcements, result releases, and teacher notifications. */
class ZMP_Communications {
    const NONCE = 'zmp_communications';

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $announcements = $wpdb->prefix . 'zmp_announcements';
        $notifications = $wpdb->prefix . 'zmp_notifications';
        dbDelta("CREATE TABLE $announcements (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            kind VARCHAR(20) NOT NULL DEFAULT 'announcement',
            academic_year VARCHAR(20) NOT NULL DEFAULT '',
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            phase VARCHAR(20) NOT NULL DEFAULT '',
            title VARCHAR(190) NOT NULL,
            body LONGTEXT NOT NULL,
            wp_post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY scope (kind,status,academic_year,category_id,phase),
            KEY published_at (published_at)
        ) $collate;");
        dbDelta("CREATE TABLE $notifications (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            announcement_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_user_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'unread',
            notified_at DATETIME NOT NULL,
            read_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY recipient_announcement (announcement_id,recipient_user_id),
            KEY recipient_status (recipient_user_id,status)
        ) $collate;");
    }

    public static function init() {
        add_shortcode('zmp_teacher_notifications', array(__CLASS__, 'render_teacher_inbox'));
        add_action('admin_post_zmp_publish_announcement', array(__CLASS__, 'handle_publish'));
        add_action('admin_post_zmp_publish_draft', array(__CLASS__, 'handle_publish_draft'));
        add_action('admin_post_zmp_mark_notification_read', array(__CLASS__, 'handle_mark_read'));
    }

    /** Moderator publication console embedded in the moderator competition portal. */
    public static function render_moderator_panel() {
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) {
            return '';
        }
        global $wpdb;
        $years = $wpdb->get_results("SELECT academic_year FROM {$wpdb->prefix}zmp_competitions WHERE academic_year<>'' UNION SELECT academic_year FROM {$wpdb->prefix}zmp_seasons WHERE academic_year<>'' ORDER BY academic_year DESC");
        $categories = $wpdb->get_results("SELECT id,name_mk FROM {$wpdb->prefix}zmp_categories WHERE is_active=1 ORDER BY sort_order,id");
        $recent = $wpdb->get_results("SELECT a.*,u.display_name FROM {$wpdb->prefix}zmp_announcements a LEFT JOIN {$wpdb->users} u ON u.ID=a.created_by ORDER BY a.created_at DESC LIMIT 20");
        $editing_id = absint($_GET['zmp_announcement_edit'] ?? 0);
        $editing = $editing_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_announcements WHERE id=%d AND status='draft'",$editing_id)) : null;
        ob_start(); ?>
        <section class="zmp-card zmp-publication-console">
            <h3>Објави, резултати и известувања</h3>
            <p>Модераторите ја организираат комуникацијата. Конечните резултати може да се објават само откако комисијата ќе ги потврди за избраното натпреварувачко ниво.</p>
            <?php if (!empty($_GET['zmp_comms_status'])): ?><p class="zmp-alert"><?php echo esc_html(self::status_message(sanitize_key(wp_unslash($_GET['zmp_comms_status'])))); ?></p><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form">
                <?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_publish_announcement"><input type="hidden" name="announcement_id" value="<?php echo $editing ? (int)$editing->id : 0; ?>">
                <h4><?php echo $editing ? 'Уреди нацрт' : 'Нова објава'; ?></h4>
                <label>Вид на објава<select name="kind"><option value="announcement" <?php selected($editing ? $editing->kind : 'announcement','announcement'); ?>>Организациско известување</option><option value="results" <?php selected($editing ? $editing->kind : 'announcement','results'); ?>>Објавување резултати</option></select></label>
                <label>Учебна година<select name="academic_year" required><option value="">Избери година</option><?php foreach ($years as $year): ?><option value="<?php echo esc_attr($year->academic_year); ?>" <?php selected($editing ? $editing->academic_year : '',$year->academic_year); ?>><?php echo esc_html($year->academic_year); ?></option><?php endforeach; ?></select></label>
                <label>Категорија<select name="category_id"><option value="0" <?php selected((int)($editing->category_id ?? 0),0); ?>>Тест на знаење / општо</option><?php foreach ($categories as $category): ?><option value="<?php echo (int)$category->id; ?>" <?php selected((int)($editing->category_id ?? 0),(int)$category->id); ?>><?php echo esc_html($category->name_mk); ?></option><?php endforeach; ?></select></label>
                <label>Натпреварувачко ниво<select name="phase"><option value="" <?php selected($editing ? $editing->phase : '', ''); ?>>Сите / општа објава</option><option value="school" <?php selected($editing ? $editing->phase : '', 'school'); ?>>Училишно натпреварувачко ниво</option><option value="municipal" <?php selected($editing ? $editing->phase : '', 'municipal'); ?>>Општинско натпреварувачко ниво</option><option value="regional" <?php selected($editing ? $editing->phase : '', 'regional'); ?>>Регионално натпреварувачко ниво</option><option value="state" <?php selected($editing ? $editing->phase : '', 'state'); ?>>Државно натпреварувачко ниво</option></select></label>
                <label>Наслов<input name="title" maxlength="190" value="<?php echo esc_attr($editing ? $editing->title : ''); ?>" required></label>
                <label>Текст<textarea name="body" rows="5" maxlength="12000" required><?php echo esc_textarea($editing ? $editing->body : ''); ?></textarea></label>
                <button class="button" type="submit" name="publish_status" value="draft">Зачувај нацрт</button>
                <button class="zmp-btn zmp-btn-primary" type="submit" name="publish_status" value="published">Објави и извести ги наставниците</button>
            </form>
            <h4>Последни објави</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Датум</th><th>Вид</th><th>Наслов</th><th>Година/натпреварувачко ниво</th><th>Објавил</th></tr></thead><tbody>
            <?php if (!$recent): ?><tr><td colspan="5">Нема објави.</td></tr><?php else: foreach ($recent as $item): ?><tr><td><?php echo esc_html($item->created_at); ?></td><td><?php echo $item->kind === 'results' ? 'Резултати' : 'Известување'; ?> / <?php echo $item->status === 'draft' ? 'Нацрт' : 'Објавено'; ?></td><td><?php echo esc_html($item->title); ?></td><td><?php echo esc_html($item->academic_year . ($item->phase ? ' / ' . self::phase_label($item->phase) : '')); ?></td><td><?php echo esc_html($item->display_name); ?><?php if ($item->status === 'draft'): ?><a class="button" href="<?php echo esc_url(add_query_arg('zmp_announcement_edit',(int)$item->id)); ?>">Уреди нацрт</a><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_publish_draft"><input type="hidden" name="announcement_id" value="<?php echo (int)$item->id; ?>"><button class="button">Објави нацрт</button></form><?php endif; ?></td></tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section><?php return ob_get_clean();
    }

    /** In-page announcement stream and per-user read receipts for mentors. */
    public static function render_teacher_inbox() {
        if (!is_user_logged_in() || (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard') && !current_user_can('manage_options'))) {
            return '<div class="zmp-card">Најавете се како наставник за да ги видите известувањата.</div>';
        }
        global $wpdb; $user_id = get_current_user_id(); $table = $wpdb->prefix . 'zmp_notifications';
        $items = $wpdb->get_results($wpdb->prepare("SELECT n.id AS notification_id,n.status AS notification_status,a.* FROM $table n JOIN {$wpdb->prefix}zmp_announcements a ON a.id=n.announcement_id WHERE n.recipient_user_id=%d ORDER BY n.notified_at DESC LIMIT 50", $user_id));
        ob_start(); ?><section class="zmp-card zmp-teacher-notifications"><h3>Известувања <span class="zmp-badge"><?php echo (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE recipient_user_id=%d AND status='unread'", $user_id)); ?></span></h3>
        <?php if (!$items): ?><p>Нема нови известувања.</p><?php else: foreach ($items as $item): ?><article class="zmp-notification" style="border-left:4px solid <?php echo $item->notification_status === 'unread' ? '#2563eb' : '#cbd5e1'; ?>;padding:12px;margin:10px 0;background:#f8fafc"><h4><?php echo esc_html($item->title); ?><?php if ($item->kind === 'results'): ?> — Резултати<?php endif; ?></h4><small><?php echo esc_html($item->academic_year . ($item->phase ? ' / ' . self::phase_label($item->phase) : '') . ' / ' . $item->published_at); ?></small><div><?php echo wpautop(esc_html($item->body)); ?></div>
        <?php if ($item->notification_status === 'unread'): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_mark_notification_read"><input type="hidden" name="notification_id" value="<?php echo (int)$item->notification_id; ?>"><button class="button">Означи како прочитано</button></form><?php endif; ?></article><?php endforeach; endif; ?></section><?php return ob_get_clean();
    }

    /** Latest public announcements and published result notices for the landing page. */
    public static function render_public_announcements() {
        global $wpdb;
        $items = $wpdb->get_results("SELECT kind,academic_year,phase,title,body,published_at FROM {$wpdb->prefix}zmp_announcements WHERE status='published' AND kind IN ('announcement','results') ORDER BY published_at DESC,id DESC LIMIT 10");
        ob_start(); ?><section class="zmp-card zmp-public-announcements"><h2>Известувања и објави</h2><?php if (!$items): ?><p>Во моментов нема објавени известувања.</p><?php else: foreach ($items as $item): ?><article style="border-bottom:1px solid #e2e8f0;padding:10px 0"><h3><?php echo esc_html($item->title); ?><?php if ($item->kind === 'results'): ?> — Потврдени резултати<?php endif; ?></h3><small><?php echo esc_html($item->academic_year . ($item->phase ? ' / ' . self::phase_label($item->phase) : '') . ' / ' . $item->published_at); ?></small><div><?php echo wpautop(esc_html($item->body)); ?></div></article><?php endforeach; endif; ?></section><?php return ob_get_clean();
    }

    /** Render only published posts from the two explicitly allowed public categories. */
    public static function render_public_wordpress_category($category_name) {
        $allowed = array('Известувања' => array('izvestuvanja', 'izvestuvanje'), 'Конкурси' => array('konkursi', 'konkurs'));
        if (!isset($allowed[$category_name])) { return ''; }
        $term_ids = array();
        $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $name = trim(wp_strip_all_tags((string)$term->name));
                $slug = sanitize_title((string)$term->slug);
                if ($name === $category_name || in_array($slug, $allowed[$category_name], true)) { $term_ids[] = (int)$term->term_id; }
            }
        }
        $query = null;
        if ($term_ids) {
            $query = new WP_Query(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>15,'ignore_sticky_posts'=>true,'no_found_rows'=>true,'category__in'=>$term_ids,'orderby'=>'date','order'=>'DESC'));
        }
        ob_start(); ?>
        <section class="zmp-card zmp-public-category" aria-labelledby="zmp-public-category-title">
            <h2 id="zmp-public-category-title"><?php echo esc_html($category_name); ?></h2>
            <?php if (!$query || !$query->have_posts()) : ?><p>Во моментов нема објави во оваа категорија.</p>
            <?php else : while ($query->have_posts()) : $query->the_post(); ?>
                <article class="zmp-public-category__item">
                    <h3><a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a></h3>
                    <time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time>
                    <div><?php echo wp_kses_post(wpautop(get_the_excerpt() ?: wp_trim_words(wp_strip_all_tags(get_the_content()), 40))); ?></div>
                    <a href="<?php echo esc_url(get_permalink()); ?>">Прочитај повеќе</a>
                </article>
            <?php endwhile; wp_reset_postdata(); endif; ?>
        </section>
        <?php return ob_get_clean();
    }

    /** Store a private application confirmation in the mentor's notification inbox. */
    public static function notify_teacher($user_id, $title, $body, $academic_year, $category_id = 0, $phase = '') {
        global $wpdb;
        $user_id = absint($user_id); $title = sanitize_text_field($title); $body = sanitize_textarea_field($body);
        if (!$user_id || !$title || !$body) { return false; }
        $wpdb->query('START TRANSACTION');
        $ok = $wpdb->insert($wpdb->prefix.'zmp_announcements', array('kind'=>'application','academic_year'=>sanitize_text_field($academic_year),'category_id'=>absint($category_id),'phase'=>sanitize_key($phase),'title'=>$title,'body'=>$body,'status'=>'published','created_by'=>0,'published_at'=>current_time('mysql'),'created_at'=>current_time('mysql')));
        if (!$ok) { $wpdb->query('ROLLBACK'); return false; }
        $announcement_id = (int)$wpdb->insert_id;
        $notification_ok = $wpdb->insert($wpdb->prefix.'zmp_notifications',array('announcement_id'=>$announcement_id,'recipient_user_id'=>$user_id,'status'=>'unread','notified_at'=>current_time('mysql')));
        if (!$notification_ok) { $wpdb->query('ROLLBACK'); return false; }
        $wpdb->query('COMMIT'); return true;
    }

    public static function handle_publish() {
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) { wp_die('Немате дозвола за објавување.', '', array('response'=>403)); }
        check_admin_referer(self::NONCE); global $wpdb;
        $announcement_id = absint($_POST['announcement_id'] ?? 0);
        $kind = sanitize_key(wp_unslash($_POST['kind'] ?? 'announcement'));
        $publish_status = sanitize_key(wp_unslash($_POST['publish_status'] ?? 'published'));
        $year = sanitize_text_field(wp_unslash($_POST['academic_year'] ?? ''));
        $category_id = absint($_POST['category_id'] ?? 0); $phase = sanitize_key(wp_unslash($_POST['phase'] ?? ''));
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? '')); $body = sanitize_textarea_field(wp_unslash($_POST['body'] ?? ''));
        if (!in_array($kind, array('announcement','results'), true) || !in_array($publish_status,array('draft','published'),true) || !$year || mb_strlen($year)>20 || !$title || mb_strlen($title)>190 || !$body || mb_strlen($body)>12000 || ($phase && !in_array($phase, array('school','municipal','regional','state'), true)) || !self::valid_announcement_scope($year,$category_id)) { self::redirect('invalid'); }
        if ($announcement_id && !(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_announcements WHERE id=%d AND status='draft'",$announcement_id))) { self::redirect('invalid'); }
        if ($kind === 'results' && $publish_status === 'published' && (!$phase || !self::results_are_confirmed($year, $category_id, $phase))) { self::redirect('results_not_confirmed'); }
        $wpdb->query('START TRANSACTION');
        if ($announcement_id) {
            $ok = $wpdb->update($wpdb->prefix.'zmp_announcements',array('kind'=>$kind,'academic_year'=>$year,'category_id'=>$category_id,'phase'=>$phase,'title'=>$title,'body'=>$body,'status'=>$publish_status,'published_at'=>$publish_status === 'published' ? current_time('mysql') : null),array('id'=>$announcement_id,'status'=>'draft'));
        } else {
            $ok = $wpdb->insert($wpdb->prefix.'zmp_announcements', array('kind'=>$kind,'academic_year'=>$year,'category_id'=>$category_id,'phase'=>$phase,'title'=>$title,'body'=>$body,'status'=>$publish_status,'created_by'=>get_current_user_id(),'published_at'=>$publish_status === 'published' ? current_time('mysql') : null,'created_at'=>current_time('mysql')));
            $announcement_id = (int)$wpdb->insert_id;
        }
        if ($ok === false || !$announcement_id) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if ($publish_status === 'published') {
            foreach (self::recipients($year, $category_id, $phase, $kind) as $recipient_id) {
                $wpdb->insert($wpdb->prefix.'zmp_notifications', array('announcement_id'=>$announcement_id,'recipient_user_id'=>$recipient_id,'status'=>'unread','notified_at'=>current_time('mysql')));
            }
        }
        if ($wpdb->last_error) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if ($publish_status === 'published' && !self::sync_wordpress_post($announcement_id, $title, $body, $kind, $year, $category_id, $phase)) { $wpdb->query('ROLLBACK'); self::redirect('post_create_error'); }
        $wpdb->query('COMMIT'); self::redirect($publish_status === 'draft' ? 'draft_saved' : 'published');
    }

    /** Publish a stored moderator draft once its result scope has passed commission checks. */
    public static function handle_publish_draft() {
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) { wp_die('Немате дозвола за објавување.', '', array('response'=>403)); }
        check_admin_referer(self::NONCE); global $wpdb; $id = absint($_POST['announcement_id'] ?? 0);
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_announcements WHERE id=%d AND status='draft'",$id));
        if (!$item) { self::redirect('invalid'); }
        if ($item->kind === 'results' && !self::results_are_confirmed($item->academic_year,(int)$item->category_id,$item->phase)) { self::redirect('results_not_confirmed'); }
        $wpdb->query('START TRANSACTION');
        $updated = $wpdb->update($wpdb->prefix.'zmp_announcements',array('status'=>'published','published_at'=>current_time('mysql')),array('id'=>$id,'status'=>'draft'),array('%s','%s'),array('%d','%s'));
        if ($updated !== 1) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        foreach (self::recipients($item->academic_year,(int)$item->category_id,$item->phase,$item->kind) as $recipient_id) {
            $wpdb->insert($wpdb->prefix.'zmp_notifications',array('announcement_id'=>$id,'recipient_user_id'=>$recipient_id,'status'=>'unread','notified_at'=>current_time('mysql')));
        }
        if ($wpdb->last_error) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if (!self::sync_wordpress_post($id, $item->title, $item->body, $item->kind, $item->academic_year, (int)$item->category_id, $item->phase)) { $wpdb->query('ROLLBACK'); self::redirect('post_create_error'); }
        $wpdb->query('COMMIT'); self::redirect('published');
    }

    public static function handle_mark_read() {
        if (!is_user_logged_in()) { wp_die('Најавете се.', '', array('response'=>403)); }
        check_admin_referer(self::NONCE); global $wpdb; $id = absint($_POST['notification_id'] ?? 0);
        $wpdb->update($wpdb->prefix.'zmp_notifications', array('status'=>'read','read_at'=>current_time('mysql')), array('id'=>$id,'recipient_user_id'=>get_current_user_id(),'status'=>'unread'), array('%s','%s'), array('%d','%d','%s'));
        self::redirect('read');
    }

    private static function results_are_confirmed($year, $category_id, $phase) {
        global $wpdb;
        $year=sanitize_text_field($year);$phase=sanitize_key($phase);
        if($category_id>0){
            $season_ids=array_map('intval',(array)$wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT s.id FROM {$wpdb->prefix}zmp_seasons s JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.season_id=s.id AND sc.category_id=%d AND sc.is_enabled=1 JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id AND st.phase=%s AND st.is_enabled=1 WHERE s.academic_year=%s",
                absint($category_id),$phase,$year
            )));
            // A year/category announcement has no season ID. Fail closed if its public scope is ambiguous.
            if(count($season_ids)!==1||!class_exists('ZMP_Category_Jury'))return false;
            $season_id=$season_ids[0];
            $eligible_entries=ZMP_Category_Jury::stage_eligible_entries($season_id,absint($category_id),$phase);
            if($eligible_entries===false||!$eligible_entries)return false;
            $closed=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_stage_closures WHERE season_id=%d AND category_id=%d AND phase=%s",$season_id,absint($category_id),$phase));
            if(!$closed)return false;
            $expected_ids=array();
            foreach($eligible_entries as $entry){
                if(!ZMP_Category_Jury::stage_entry_is_disqualified($season_id,absint($category_id),(int)$entry->id,$phase))$expected_ids[]=(int)$entry->id;
            }
            if(!$expected_ids)return false;
            $result_rows=(array)$wpdb->get_results($wpdb->prepare(
                "SELECT f.season_id,f.entry_id,f.status FROM {$wpdb->prefix}zmp_category_final_results f JOIN {$wpdb->prefix}zmp_seasons s ON s.id=f.season_id WHERE s.academic_year=%s AND f.category_id=%d AND f.phase=%s",
                $year,absint($category_id),$phase
            ));
            $actual_ids=array();
            foreach($result_rows as $row){
                if((int)$row->season_id!==$season_id||$row->status!=='confirmed')return false;
                $actual_ids[]=(int)$row->entry_id;
            }
            sort($expected_ids,SORT_NUMERIC);sort($actual_ids,SORT_NUMERIC);
            $pending=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE season_id=%d AND category_id=%d AND phase=%s AND status='pending'",$season_id,absint($category_id),$phase));
            return $actual_ids===$expected_ids&&$pending===0;
        }
        $competition_id=self::closed_legacy_competition_id($year,$phase);
        if(!$competition_id)return false;
        $results=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s AND phase=%s",$competition_id,$year,$phase));
        return $results>0;
    }

    /** Resolve a year only when exactly one legacy competition owns it. */
    public static function legacy_competition_id_for_year($year) {
        global $wpdb;
        $ids=array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE academic_year=%s ORDER BY id",sanitize_text_field($year))));
        return count($ids)===1?$ids[0]:0;
    }

    /** Resolve the only competition for a year, and require that its exact phase is closed. */
    public static function closed_legacy_competition_id($year,$phase) {
        global $wpdb;
        $competition_id=self::legacy_competition_id_for_year($year);
        if(!$competition_id)return 0;
        $closed=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s AND status='closed'",$competition_id,sanitize_key($phase)));
        return $closed?$competition_id:0;
    }
    /** Publish announcements as ordinary WordPress posts while retaining their portal record. */
    private static function sync_wordpress_post($announcement_id, $title, $body, $kind, $year, $category_id, $phase) {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_announcements';
        $linked = absint($wpdb->get_var($wpdb->prepare("SELECT wp_post_id FROM $table WHERE id=%d", $announcement_id)));
        $category = $category_id ? $wpdb->get_var($wpdb->prepare("SELECT name_mk FROM {$wpdb->prefix}zmp_categories WHERE id=%d", $category_id)) : '';
        $suffix = trim(implode(' · ', array_filter(array($year, $category, $phase ? self::phase_label($phase) : ''))));
        $postarr = array('post_title'=>$title, 'post_content'=>wpautop(esc_html($body)), 'post_status'=>'publish', 'post_type'=>'post', 'post_author'=>get_current_user_id());
        $updating = $linked && get_post($linked);
        if ($updating) { $postarr['ID'] = $linked; $post_id = wp_update_post(wp_slash($postarr), true); }
        else { $post_id = wp_insert_post(wp_slash($postarr), true); }
        if (is_wp_error($post_id) || !$post_id) { return false; }
        update_post_meta((int)$post_id, '_zmp_announcement_id', (int)$announcement_id);
        update_post_meta((int)$post_id, '_zmp_announcement_scope', $suffix);
        $saved = $wpdb->update($table, array('wp_post_id'=>(int)$post_id), array('id'=>(int)$announcement_id), array('%d'), array('%d'));
        if ($saved === false && !$updating) { wp_delete_post((int)$post_id, true); }
        return $saved !== false;
    }

    private static function valid_announcement_scope($year,$category_id) {
        global $wpdb;
        $year_exists=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE academic_year=%s",$year))
            +(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_seasons WHERE academic_year=%s",$year));
        if(!$year_exists)return false;
        return $category_id===0 || (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE id=%d AND is_active=1",$category_id));
    }

    /** True only after an explicitly published result release exists for this exact scope. */
    public static function is_result_published($year, $category_id, $phase) {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_announcements WHERE kind='results' AND status='published' AND academic_year=%s AND category_id=%d AND phase=%s LIMIT 1", $year, absint($category_id), sanitize_key($phase)));
    }

    private static function recipients($year, $category_id, $phase, $kind) {
        global $wpdb;
        if ($category_id > 0) {
            return array_map('intval', (array)$wpdb->get_col($wpdb->prepare("SELECT DISTINCT e.wp_user_id FROM {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_seasons s ON s.id=e.season_id WHERE s.academic_year=%s AND e.category_id=%d AND e.wp_user_id>0", $year,$category_id)));
        }
        if ($kind === 'results' && $phase) {
            $competition_id=self::closed_legacy_competition_id($year,$phase);
            if(!$competition_id)return array();
            return array_map('intval', (array)$wpdb->get_col($wpdb->prepare("SELECT DISTINCT mentor_user_id FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s AND phase=%s AND mentor_user_id>0",$competition_id,$year,$phase)));
        }
        return array_map('intval', (array)$wpdb->get_col("SELECT DISTINCT wp_user_id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id>0"));
    }

    private static function redirect($status) { $url = wp_get_referer() ?: home_url('/'); wp_safe_redirect(add_query_arg('zmp_comms_status', sanitize_key($status), $url)); exit; }
    private static function phase_label($phase) { return array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво')[$phase] ?? $phase; }
    private static function status_message($status) { return array('published'=>'Објавата е објавена како WordPress пост и известувањата се евидентирани.','draft_saved'=>'Нацртот е зачуван без испраќање известувања.','read'=>'Известувањето е означено како прочитано.','invalid'=>'Проверете ги задолжителните полиња.','results_not_confirmed'=>'Резултатите не можат да се објават пред затворање и потврда на натпреварувачкото ниво од комисијата.','post_create_error'=>'WordPress постот не можеше да се создаде; проверете ги дозволите и обидете се повторно.','save_error'=>'Неуспешно зачувување. Обидете се повторно.')[$status] ?? ''; }
}
