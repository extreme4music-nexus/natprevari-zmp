<?php
if (!defined('ABSPATH')) { exit; }

/** Operational scheduling, assignment, and readiness tracking for moderators. */
class ZMP_Moderator_Operations {
    const NONCE_ACTION = 'zmp_moderator_operations';

    public static function init() {
        add_action('init', array(__CLASS__, 'handle_request'), 2);
    }

    /** Additive operational tables; no commission decisions are stored here. */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;
        dbDelta("CREATE TABLE {$p}zmp_stage_events (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            starts_at DATETIME DEFAULT NULL,
            ends_at DATETIME DEFAULT NULL,
            delivery_mode VARCHAR(20) NOT NULL DEFAULT 'physical',
            location VARCHAR(255) DEFAULT '' NOT NULL,
            online_details TEXT NOT NULL,
            moderator_notes TEXT NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category_phase (season_id,category_id,phase),
            KEY phase (phase)
        ) $collate;");
        dbDelta("CREATE TABLE {$p}zmp_stage_regional_events (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL DEFAULT 'regional',
            region VARCHAR(30) NOT NULL,
            starts_at DATETIME DEFAULT NULL,
            ends_at DATETIME DEFAULT NULL,
            delivery_mode VARCHAR(20) NOT NULL DEFAULT 'physical',
            location VARCHAR(255) DEFAULT '' NOT NULL,
            online_details TEXT NOT NULL,
            moderator_notes TEXT NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY season_category_phase_region (season_id,category_id,phase,region),
            KEY region (region)
        ) $collate;");
        dbDelta("CREATE TABLE {$p}zmp_stage_testator_assignments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            testator_user_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_by BIGINT(20) UNSIGNED NOT NULL,
            notes TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY duty_scope_user (season_id,category_id,phase,testator_user_id),
            KEY testator_user_id (testator_user_id)
        ) $collate;");
        dbDelta("CREATE TABLE {$p}zmp_stage_tasks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            task_title VARCHAR(255) NOT NULL,
            task_status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_by BIGINT(20) UNSIGNED NOT NULL,
            completed_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY scope_status (season_id,category_id,phase,task_status)
        ) $collate;");
        dbDelta("CREATE TABLE {$p}zmp_operations_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(40) NOT NULL,
            object_type VARCHAR(30) NOT NULL,
            object_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            details LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY scope_time (season_id,category_id,phase,created_at)
        ) $collate;");
    }

    private static function authorized() {
        return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('zmp_moderator_cap'));
    }

    /** Handle organization-only edits, protected by capability, nonce, and active scope checks. */
    public static function handle_request() {
        if (empty($_POST['zmp_operations_action'])) { return; }
        if (!self::authorized()) { wp_die('Немате овластување за организациски измени.', 'Нема пристап', array('response'=>403)); }
        check_admin_referer(self::NONCE_ACTION, 'zmp_operations_nonce');
        $action_raw = wp_unslash($_POST['zmp_operations_action']);
        $action = is_scalar($action_raw) ? sanitize_key((string)$action_raw) : '';
        $scope = self::posted_scope();
        if (!$scope) { wp_die('Натпреварувачкото ниво не е достапно за избраната категорија.', 'Невалидно натпреварувачко ниво', array('response'=>400)); }
        global $wpdb;
        $user_id = get_current_user_id();
        $now = current_time('mysql');

        if ($action === 'save_event') {
            $event_region = sanitize_key(self::posted_text('region'));
            if ($scope['phase'] === 'regional' && !ZMP_Regions::valid_region($event_region)) { self::redirect($scope, 'regional_region_required'); }
            $start_raw = self::posted_text('starts_at');
            $end_raw = self::posted_text('ends_at');
            $start = self::posted_datetime('starts_at');
            $end = self::posted_datetime('ends_at');
            if (($start_raw !== '' && !$start) || ($end_raw !== '' && !$end)) { self::redirect($scope, 'invalid_time'); }
            if ($start && $end && strtotime($end) < strtotime($start)) { self::redirect($scope, 'invalid_time'); }
            $mode_raw = self::posted_text('delivery_mode');
            $regional_music = $scope['phase'] === 'regional' && $scope['category']->category_code !== 'knowledge_test';
            if ($regional_music && !in_array($mode_raw, array('physical','online'), true)) { self::redirect($scope, 'regional_music_modes_only'); }
            $mode = $regional_music ? $mode_raw : (in_array($mode_raw, array('physical','online','hybrid'), true) ? $mode_raw : $scope['stage']->delivery_mode);
            if ($scope['phase'] === 'state') { $mode = 'physical'; }
            $location = self::posted_text('location');
            $online = self::posted_textarea('online_details');
            $notes = self::posted_textarea('moderator_notes');
            if ($mode === 'physical' && $location === '') { self::redirect($scope, 'location_required'); }
            if ($mode === 'online' && $online === '') { self::redirect($scope, 'online_details_required'); }
            if ($mode === 'hybrid' && ($location === '' || $online === '')) { self::redirect($scope, 'hybrid_details_required'); }
            if (!$start && $end) { self::redirect($scope, 'invalid_time'); }
            $table = $wpdb->prefix . ($scope['phase'] === 'regional' ? 'zmp_stage_regional_events' : 'zmp_stage_events');
            if ($scope['phase'] === 'regional') {
                $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE season_id=%d AND category_id=%d AND phase=%s AND region=%s", $scope['season_id'], $scope['category_id'], $scope['phase'], $event_region));
            } else {
                $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE season_id=%d AND category_id=%d AND phase=%s", $scope['season_id'], $scope['category_id'], $scope['phase']));
            }
            $data = array('starts_at'=>$start ?: null,'ends_at'=>$end ?: null,'delivery_mode'=>$mode,'location'=>$location,'online_details'=>$online,'moderator_notes'=>$notes,'updated_by'=>$user_id,'updated_at'=>$now);
            if ($existing) { $saved = $wpdb->update($table, $data, array('id'=>(int)$existing)); $object_id=(int)$existing; }
            else { $data += array('season_id'=>$scope['season_id'],'category_id'=>$scope['category_id'],'phase'=>$scope['phase'],'created_at'=>$now); if($scope['phase']==='regional')$data['region']=$event_region; $saved = $wpdb->insert($table, $data); $object_id=(int)$wpdb->insert_id; }
            if ($saved !== false) { self::audit($scope,$user_id,'save_event','stage_event',$object_id,array('delivery_mode'=>$mode,'has_schedule'=>(bool)$start,'has_location'=>(bool)$location,'has_online_details'=>(bool)$online)); }
            self::redirect($scope, $saved === false ? 'save_error' : 'event_saved');
        }

        if ($action === 'assign_testator') {
            $testator_id = absint($_POST['testator_user_id'] ?? 0);
            $testator = $testator_id ? get_userdata($testator_id) : false;
            if (!$testator || (!in_array('zmp_testator', (array)$testator->roles, true) && !user_can($testator_id, 'zmp_testator_cap'))) { self::redirect($scope, 'invalid_testator'); }
            $notes = self::posted_textarea('duty_notes');
            $table = $wpdb->prefix . 'zmp_stage_testator_assignments';
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE season_id=%d AND category_id=%d AND phase=%s AND testator_user_id=%d", $scope['season_id'], $scope['category_id'], $scope['phase'], $testator_id));
            if ($existing) { $saved = $wpdb->update($table, array('notes'=>$notes,'assigned_by'=>$user_id), array('id'=>(int)$existing)); }
            else { $saved = $wpdb->insert($table, array('season_id'=>$scope['season_id'],'category_id'=>$scope['category_id'],'phase'=>$scope['phase'],'testator_user_id'=>$testator_id,'assigned_by'=>$user_id,'notes'=>$notes)); }
            if ($saved !== false) { self::audit($scope,$user_id,'assign_testator','testator_duty',(int)($existing ?: $wpdb->insert_id),array('testator_user_id'=>$testator_id)); }
            self::redirect($scope, $saved === false ? 'save_error' : 'testator_assigned');
        }

        if ($action === 'remove_testator') {
            $assignment_id=absint($_POST['assignment_id']??0);$table=$wpdb->prefix.'zmp_stage_testator_assignments';
            $assignment=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND season_id=%d AND category_id=%d AND phase=%s",$assignment_id,$scope['season_id'],$scope['category_id'],$scope['phase']));
            if(!$assignment)self::redirect($scope,'assignment_missing');
            $saved=$wpdb->delete($table,array('id'=>$assignment_id));
            if($saved){self::audit($scope,$user_id,'remove_testator','testator_duty',$assignment_id,array('testator_user_id'=>(int)$assignment->testator_user_id));}
            self::redirect($scope,$saved?'testator_removed':'save_error');
        }

        if ($action === 'add_task') {
            $title = self::posted_text('task_title');
            if ($title === '') { self::redirect($scope, 'task_required'); }
            $saved = $wpdb->insert($wpdb->prefix . 'zmp_stage_tasks', array('season_id'=>$scope['season_id'],'category_id'=>$scope['category_id'],'phase'=>$scope['phase'],'task_title'=>$title,'created_by'=>$user_id));
            if ($saved) { self::audit($scope,$user_id,'add_task','task',(int)$wpdb->insert_id,array('task_title'=>$title)); }
            self::redirect($scope, $saved ? 'task_added' : 'save_error');
        }

        if ($action === 'toggle_task') {
            $task_id = absint($_POST['task_id'] ?? 0);
            $task_table = $wpdb->prefix . 'zmp_stage_tasks';
            $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $task_table WHERE id=%d AND season_id=%d AND category_id=%d AND phase=%s", $task_id, $scope['season_id'], $scope['category_id'], $scope['phase']));
            if (!$task) { self::redirect($scope, 'task_missing'); }
            $is_open = $task->task_status === 'open';
            $saved = $wpdb->update($task_table, array('task_status'=>$is_open ? 'complete' : 'open','completed_by'=>$is_open ? $user_id : 0,'completed_at'=>$is_open ? $now : null), array('id'=>$task_id));
            if ($saved !== false) { self::audit($scope,$user_id,$is_open?'complete_task':'reopen_task','task',$task_id,array('from'=>$task->task_status,'to'=>$is_open?'complete':'open')); }
            self::redirect($scope, $saved === false ? 'save_error' : 'task_updated');
        }

        if ($action === 'review_entry') {
            $entry_id = absint($_POST['entry_id'] ?? 0);
            $status = self::posted_text('entry_status');
            $allowed = array('submitted','under_review','needs_correction','complete');
            $note = self::posted_textarea('moderator_note');
            if (!in_array($status,$allowed,true) || ($status==='needs_correction' && trim($note)==='')) { self::redirect($scope, 'entry_review_invalid'); }
            $entries_table=$wpdb->prefix.'zmp_category_entries';
            $entry=$wpdb->get_row($wpdb->prepare("SELECT * FROM $entries_table WHERE id=%d AND season_id=%d AND category_id=%d",$entry_id,$scope['season_id'],$scope['category_id']));
            if (!$entry) { self::redirect($scope, 'entry_missing'); }
            if ($entry->status==='draft' && class_exists('ZMP_YouTube')) { $draft_mode=ZMP_YouTube::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region); if(in_array($draft_mode,array('unconfigured','none'),true))self::redirect($scope,'entry_delivery_mode_unavailable'); if($draft_mode==='online')self::redirect($scope,'entry_video_gate'); }
            $saved=$wpdb->update($entries_table,array('status'=>$status,'moderator_note'=>$note,'reviewed_by'=>$user_id,'reviewed_at'=>$now),array('id'=>$entry_id));
            if ($saved!==false) { self::audit($scope,$user_id,'review_registration','category_entry',$entry_id,array('from'=>$entry->status,'to'=>$status,'has_note'=>(bool)$note)); }
            self::redirect($scope,$saved===false?'save_error':'entry_reviewed');
        }
        self::redirect($scope, 'unknown_action');
    }

    /** Render the moderator's operational console inside the existing portal. */
    public static function render_panel() {
        if (!self::authorized()) { return ''; }
        global $wpdb;
        $seasons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_seasons ORDER BY academic_year DESC");
        if (!$seasons) { return '<section class="zmp-card"><h3>Организација на натпреварувачки нивоа</h3><p>Прво креирајте натпреварувачка година.</p></section>'; }
        $season_id = isset($_GET['zmp_ops_season']) ? absint($_GET['zmp_ops_season']) : 0;
        $season = null;
        foreach ($seasons as $candidate) { if ((int)$candidate->id === $season_id) { $season = $candidate; break; } }
        if (!$season) { foreach ($seasons as $candidate) { if ($candidate->status === 'active') { $season = $candidate; break; } } }
        if (!$season) { $season = $seasons[0]; }
        $category_id = isset($_GET['zmp_ops_category']) ? absint($_GET['zmp_ops_category']) : 0;
        $categories = $wpdb->get_results($wpdb->prepare("SELECT c.*,sc.id AS mapping_id,sc.is_enabled AS category_enabled FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 ORDER BY c.sort_order", $season->id));
        $category = null;
        foreach ($categories as $candidate) { if ((int)$candidate->id === $category_id) { $category = $candidate; break; } }
        if (!$category && $categories) { $category = $categories[0]; }
        if (!$category) { return '<section class="zmp-card"><h3>Организација на натпреварувачки нивоа</h3><p>Нема активни категории за оваа година.</p></section>'; }
        $count = ZMP_Category_Registrations::count_for_category((int)$season->id, (int)$category->id);
        $stages = ZMP_Competition_Structure::evaluate_phases((int)$season->id, (int)$category->id, $count);
        $phase = isset($_GET['zmp_ops_phase']) ? sanitize_key(wp_unslash($_GET['zmp_ops_phase'])) : '';
        if (!isset($stages[$phase]) || empty($stages[$phase]['available'])) { $phase = ''; foreach ($stages as $key=>$value) { if (!empty($value['available'])) { $phase=$key; break; } } }
        if (!$phase) { return '<section class="zmp-card"><h3>Организација на натпреварувачки нивоа</h3><p>Избраната категорија нема достапни натпреварувачки нивоа.</p></section>'; }
        $stage = $stages[$phase];
        $entry_region = isset($_GET['zmp_ops_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_ops_region'])) ? sanitize_key(wp_unslash($_GET['zmp_ops_region'])) : '';
        $event = null;
        if ($phase === 'regional' && $entry_region !== '') {
            $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_stage_regional_events WHERE season_id=%d AND category_id=%d AND phase=%s AND region=%s", $season->id, $category->id, $phase, $entry_region));
        } elseif ($phase !== 'regional') {
            $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_stage_events WHERE season_id=%d AND category_id=%d AND phase=%s", $season->id, $category->id, $phase));
        }
        $display_delivery_mode = $event ? (string) $event->delivery_mode : (string) $stage['delivery_mode'];
        if ($phase === 'regional' && $category->category_code !== 'knowledge_test' && !$event) { $display_delivery_mode = ''; }
        $delivery_mode_label = $display_delivery_mode === 'physical' ? 'Физички' : ($display_delivery_mode === 'online' ? 'Онлајн' : ($display_delivery_mode === 'hybrid' ? 'Физички или онлајн' : 'Не е поставено'));
        $duties = $wpdb->get_results($wpdb->prepare("SELECT d.*,u.display_name FROM {$wpdb->prefix}zmp_stage_testator_assignments d LEFT JOIN {$wpdb->users} u ON u.ID=d.testator_user_id WHERE d.season_id=%d AND d.category_id=%d AND d.phase=%s ORDER BY u.display_name", $season->id, $category->id, $phase));
        $tasks = $wpdb->get_results($wpdb->prepare("SELECT t.*,u.display_name AS completed_name FROM {$wpdb->prefix}zmp_stage_tasks t LEFT JOIN {$wpdb->users} u ON u.ID=t.completed_by WHERE t.season_id=%d AND t.category_id=%d AND t.phase=%s ORDER BY t.task_status,t.id", $season->id, $category->id, $phase));
        $audit = $wpdb->get_results($wpdb->prepare("SELECT a.*,u.display_name FROM {$wpdb->prefix}zmp_operations_audit a LEFT JOIN {$wpdb->users} u ON u.ID=a.actor_user_id WHERE a.season_id=%d AND a.category_id=%d AND a.phase=%s ORDER BY a.id DESC LIMIT 30",$season->id,$category->id,$phase));
        $entry_municipality = sanitize_text_field(wp_unslash($_GET['zmp_ops_municipality'] ?? ''));
        $entry_school = sanitize_text_field(wp_unslash($_GET['zmp_ops_school'] ?? ''));
        $entry_sql = "SELECT e.*,g.name_mk AS group_name FROM {$wpdb->prefix}zmp_category_entries e LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE e.season_id=%d AND e.category_id=%d";
        $entry_args = array((int)$season->id,(int)$category->id);
        if ($entry_region !== '') { $entry_sql .= ' AND e.region=%s'; $entry_args[]=$entry_region; }
        if ($entry_municipality !== '') { $entry_sql .= ' AND e.municipality=%s'; $entry_args[]=$entry_municipality; }
        if ($entry_school !== '') { $entry_sql .= ' AND e.school_name LIKE %s'; $entry_args[]='%' . $wpdb->esc_like($entry_school) . '%'; }
        $entries = $wpdb->get_results($wpdb->prepare($entry_sql.' ORDER BY g.sort_order,e.school_name,e.participant_name', $entry_args));
        $testators = get_users(array('role'=>'zmp_testator','orderby'=>'display_name','order'=>'ASC','fields'=>array('ID','display_name')));
        $notice = isset($_GET['zmp_ops_notice']) ? sanitize_key(wp_unslash($_GET['zmp_ops_notice'])) : '';
        $messages = array('regional_music_modes_only'=>'За регионален натпревар во музичките категории изберете „Онлајн“ или „Физички“ посебно за овој регион.','event_saved'=>'Распоредот и условите се зачувани.','testator_assigned'=>'Тестаторот е доделен.','testator_removed'=>'Тестаторот е отстранет од ова натпреварувачко ниво.','assignment_missing'=>'Задолжението не е пронајдено.','task_added'=>'Организациската задача е додадена.','task_updated'=>'Статусот на задачата е ажуриран.','entry_reviewed'=>'Статусот на пријавата е ажуриран.','entry_review_invalid'=>'За пријава со потребна исправка внесете образложение.','entry_video_gate'=>'Нацрт пријавата не може да се означи како поднесена од модератор. Наставникот мора прво да ги заврши и потврди задолжителните YouTube видеа.','entry_delivery_mode_unavailable'=>'Регионалното натпреварувачко ниво не е активно или не може безбедно да се провери. Нацртот не може да се поднесе додека модератор не ја потврди поставката.','entry_missing'=>'Пријавата не е пронајдена во избраната категорија.','location_required'=>'За физичко натпреварувачко ниво внесете локација.','online_details_required'=>'За онлајн натпреварувачко ниво внесете детали за пристап.','hybrid_details_required'=>'За натпреварувачко ниво со комбиниран начин на одржување внесете локација и онлајн детали.','invalid_time'=>'Внесете валиден распоред, а времето на завршување мора да биде по почетокот.','invalid_testator'=>'Изберете корисник со улога тестатор.','task_required'=>'Внесете наслов на задачата.','task_missing'=>'Задачата не е пронајдена во ова натпреварувачко ниво.','save_error'=>'Промената не можеше да се зачува.');
        ob_start(); ?>
        <section class="zmp-card zmp-operations"><h3>Организација на натпреварот</h3>
        <p>Овој дел е за распоред, локација, тестатори и подготвеност. Не внесува и не менува комисиски оценки или конечни одлуки.</p>
        <?php if (isset($messages[$notice])): ?><div class="notice <?php echo in_array($notice,array('save_error','invalid_time','location_required','online_details_required','invalid_testator','task_required','task_missing'),true)?'notice-error':'notice-success'; ?>"><p><?php echo esc_html($messages[$notice]); ?></p></div><?php endif; ?>
        <form method="get" class="zmp-operations-filter"><?php foreach((array)$_GET as $query_key=>$query_value): if(in_array((string)$query_key,array('zmp_ops_season','zmp_ops_category','zmp_ops_phase','zmp_ops_notice','zmp_ops_region','zmp_ops_municipality','zmp_ops_school'),true)||!is_scalar($query_value))continue; ?><input type="hidden" name="<?php echo esc_attr(sanitize_key($query_key)); ?>" value="<?php echo esc_attr(sanitize_text_field(wp_unslash((string)$query_value))); ?>"><?php endforeach; ?><label>Година <select name="zmp_ops_season" onchange="this.form.submit()"><?php foreach($seasons as $row): ?><option value="<?php echo (int)$row->id; ?>" <?php selected((int)$season->id,(int)$row->id); ?>><?php echo esc_html($row->academic_year.' — '.$row->title); ?></option><?php endforeach; ?></select></label>
        <label>Категорија <select name="zmp_ops_category" onchange="this.form.submit()"><?php foreach($categories as $row): ?><option value="<?php echo (int)$row->id; ?>" <?php selected((int)$category->id,(int)$row->id); ?>><?php echo esc_html($row->name_mk); ?></option><?php endforeach; ?></select></label>
        <label>Натпреварувачко ниво <select name="zmp_ops_phase" onchange="this.form.submit()"><?php foreach($stages as $key=>$row): if(empty($row['available']))continue; ?><option value="<?php echo esc_attr($key); ?>" <?php selected($phase,$key); ?>><?php echo esc_html(self::phase_label($key)); ?></option><?php endforeach; ?></select></label>
        <label>Регион <select name="zmp_ops_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($entry_region,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_ops_municipality" value="<?php echo esc_attr($entry_municipality); ?>"></label><label>Училиште <input name="zmp_ops_school" value="<?php echo esc_attr($entry_school); ?>"></label><button class="button">Филтрирај пријави</button></form>
        <p><strong>Пријавени учесници/состави:</strong> <?php echo (int)$count; ?> · <strong>Начин:</strong> <?php echo esc_html($delivery_mode_label); ?><?php if(!empty($stage['required'])): ?> · задолжително натпреварувачко ниво<?php endif; ?></p>
        <h4>Распоред и услови<?php if($phase==='regional' && $entry_region!==''): ?> — <?php echo esc_html(ZMP_Regions::label($entry_region)); ?><?php endif; ?></h4>
        <?php if($phase==='regional' && $entry_region===''): ?><p>Изберете регион во филтерот погоре за да внесете или измените распоред за тој регион. Регионалното натпреварувачко ниво има посебен распоред за секој од осумте региони.</p><?php else: ?><form method="post" class="zmp-form"><?php self::hidden_scope($season,$category,$phase); ?><?php if($phase==='regional'): ?><input type="hidden" name="region" value="<?php echo esc_attr($entry_region); ?>"><?php endif; ?><input type="hidden" name="zmp_operations_action" value="save_event">
        <label>Почеток <input type="datetime-local" name="starts_at" value="<?php echo esc_attr(self::datetime_local($event ? $event->starts_at : '')); ?>"></label><label>Крај <input type="datetime-local" name="ends_at" value="<?php echo esc_attr(self::datetime_local($event ? $event->ends_at : '')); ?>"></label>
        <label>Начин <select name="delivery_mode" <?php disabled($phase==='state'); ?> <?php echo ($phase==='regional' && $category->category_code!=='knowledge_test')?'required':''; ?>><?php if($phase==='regional' && $category->category_code!=='knowledge_test'): ?><option value="">Изберете начин</option><option value="physical" <?php selected($event?$event->delivery_mode:'','physical'); ?>>Физички</option><option value="online" <?php selected($event?$event->delivery_mode:'','online'); ?>>Онлајн</option><?php else: ?><option value="physical" <?php selected($event?$event->delivery_mode:$stage['delivery_mode'],'physical'); ?>>Физички</option><option value="online" <?php selected($event?$event->delivery_mode:$stage['delivery_mode'],'online'); ?>>Онлајн</option><option value="hybrid" <?php selected($event?$event->delivery_mode:$stage['delivery_mode'],'hybrid'); ?>>Комбинирано</option><?php endif; ?></select><?php if($phase==='state'): ?><input type="hidden" name="delivery_mode" value="physical"><small>Државниот натпревар е секогаш физички.</small><?php endif; ?></label>
        <label>Локација <input name="location" maxlength="255" value="<?php echo esc_attr($event?$event->location:''); ?>" placeholder="Сала, училиште, адреса"></label><label>Онлајн детали / линк <textarea name="online_details" rows="2"><?php echo esc_textarea($event?$event->online_details:''); ?></textarea></label><label>Организациски белешки <textarea name="moderator_notes" rows="2"><?php echo esc_textarea($event?$event->moderator_notes:''); ?></textarea></label><button class="zmp-btn zmp-btn-primary">Зачувај распоред</button></form><?php endif; ?>
        <h4>Тестатори за натпреварувачкото ниво</h4><?php if($testators): ?><form method="post" class="zmp-form"><?php self::hidden_scope($season,$category,$phase); ?><input type="hidden" name="zmp_operations_action" value="assign_testator"><label>Тестатор <select name="testator_user_id" required><option value="">Изберете тестатор</option><?php foreach($testators as $t): ?><option value="<?php echo (int)$t->ID; ?>"><?php echo esc_html($t->display_name); ?></option><?php endforeach; ?></select></label><label>Белешка <input name="duty_notes" maxlength="1000"></label><button class="zmp-btn zmp-btn-primary">Додели</button></form><?php else: ?><p>Нема регистрирани корисници со улога тестатор.</p><?php endif; ?>
        <?php if($duties): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Тестатор</th><th>Белешки</th><th>Акција</th></tr></thead><tbody><?php foreach($duties as $d): ?><tr><td><?php echo esc_html($d->display_name ?: 'Корисник #'.$d->testator_user_id); ?></td><td><?php echo esc_html($d->notes); ?></td><td><form method="post" onsubmit="return confirm('Да се отстрани тестаторот од ова натпреварувачко ниво?')"><?php self::hidden_scope($season,$category,$phase); ?><input type="hidden" name="zmp_operations_action" value="remove_testator"><input type="hidden" name="assignment_id" value="<?php echo (int)$d->id; ?>"><button class="button">Отстрани</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Нема доделени тестатори за ова натпреварувачко ниво.</p><?php endif; ?>
        <h4>Организациски задачи</h4><form method="post" class="zmp-form"><?php self::hidden_scope($season,$category,$phase); ?><input type="hidden" name="zmp_operations_action" value="add_task"><label>Нова задача <input name="task_title" maxlength="255" required></label><button class="zmp-btn zmp-btn-primary">Додај задача</button></form>
        <?php if($tasks): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Задача</th><th>Статус</th><th>Извршено од</th><th>Акција</th></tr></thead><tbody><?php foreach($tasks as $task): ?><tr><td><?php echo esc_html($task->task_title); ?></td><td><?php echo $task->task_status==='complete'?'Завршена':'Отворена'; ?></td><td><?php echo esc_html($task->completed_name ?: '—'); ?></td><td><form method="post"><?php self::hidden_scope($season,$category,$phase); ?><input type="hidden" name="zmp_operations_action" value="toggle_task"><input type="hidden" name="task_id" value="<?php echo (int)$task->id; ?>"><button class="button"><?php echo $task->task_status==='complete'?'Повтори':'Означи завршена'; ?></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Нема внесени организациски задачи.</p><?php endif; ?>
        <h4>Историја на организациски измени</h4><?php if($audit): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Време</th><th>Корисник</th><th>Дејство</th><th>Детали</th></tr></thead><tbody><?php foreach($audit as $row): ?><tr><td><?php echo esc_html($row->created_at); ?></td><td><?php echo esc_html($row->display_name ?: 'Корисник #'.$row->actor_user_id); ?></td><td><?php echo esc_html($row->action); ?></td><td><code><?php echo esc_html($row->details); ?></code></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Нема евидентирани измени.</p><?php endif; ?>
        <h4>Пријави и проверка на комплетност</h4><?php if($entries): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Учесник / состав</th><th>Група</th><th>Училиште</th><th>Општина</th><th>Регион</th><th>Наставник</th><th>Список</th><th>Статус и организациска белешка</th></tr></thead><tbody><?php foreach($entries as $entry): ?><tr><td><?php echo esc_html($entry->participant_name); ?></td><td><?php echo esc_html($entry->group_name); ?></td><td><?php echo esc_html($entry->school_name); ?></td><td><?php echo esc_html($entry->municipality); ?></td><td><?php echo esc_html(ZMP_Regions::label($entry->region)); ?></td><td><?php echo esc_html(get_the_author_meta('display_name',(int)$entry->wp_user_id)); ?></td><td><?php if($entry->roster_filename): ?><a href="<?php echo esc_url(wp_nonce_url(add_query_arg('zmp_roster_download',(int)$entry->id,get_permalink()),'zmp_download_roster_'.(int)$entry->id)); ?>">Преземи список</a><?php else: ?>Не е приложен<?php endif; ?><?php echo ZMP_Registration_Requirements::render_entry_document_links((int)$entry->id); ?></td><td><form method="post"><?php self::hidden_scope($season,$category,$phase); ?><input type="hidden" name="zmp_operations_action" value="review_entry"><input type="hidden" name="entry_id" value="<?php echo (int)$entry->id; ?>"><select name="entry_status"><option value="submitted" <?php selected($entry->status,'submitted'); ?>>Поднесена</option><option value="under_review" <?php selected($entry->status,'under_review'); ?>>Во проверка</option><option value="needs_correction" <?php selected($entry->status,'needs_correction'); ?>>Потребна е исправка</option><option value="complete" <?php selected($entry->status,'complete'); ?>>Документите се целосни</option></select><textarea name="moderator_note" rows="2" maxlength="2000" placeholder="Образложение; задолжително ако барате исправка"><?php echo esc_textarea($entry->moderator_note??''); ?></textarea><button class="button">Зачувај статус</button><?php if(!empty($entry->reviewed_at)): ?><small>Проверено: <?php echo esc_html($entry->reviewed_at); ?></small><?php endif; ?></form></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Нема пријави во избраната категорија.</p><?php endif; ?>
        </section><?php return ob_get_clean();
    }

    private static function posted_scope() {
        global $wpdb;
        $season_id = absint($_POST['season_id'] ?? 0); $category_id = absint($_POST['category_id'] ?? 0); $phase = self::posted_text('phase');
        $season = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d",$season_id));
        if (!$season || $season->status==='completed') return false;
        $category = $wpdb->get_row($wpdb->prepare("SELECT c.id,c.category_code,sc.id AS mapping_id FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 WHERE c.id=%d",$season_id,$category_id));
        if (!$category) return false;
        $count=ZMP_Category_Registrations::count_for_category($season_id,$category_id); $stages=ZMP_Competition_Structure::evaluate_phases($season_id,$category_id,$count);
        if (!isset($stages[$phase])||empty($stages[$phase]['available'])) return false;
        return array('season_id'=>$season_id,'category_id'=>$category_id,'phase'=>$phase,'season'=>$season,'category'=>$category,'stage'=>$stages[$phase]);
    }

    private static function audit($scope,$actor,$action,$object_type,$object_id,$details) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'zmp_operations_audit',array(
            'season_id'=>$scope['season_id'],'category_id'=>$scope['category_id'],'phase'=>$scope['phase'],
            'actor_user_id'=>$actor,'action'=>$action,'object_type'=>$object_type,'object_id'=>$object_id,
            'details'=>wp_json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'created_at'=>current_time('mysql'),
        ));
    }

    private static function hidden_scope($season,$category,$phase) {
        wp_nonce_field(self::NONCE_ACTION,'zmp_operations_nonce');
        echo '<input type="hidden" name="season_id" value="'.(int)$season->id.'"><input type="hidden" name="category_id" value="'.(int)$category->id.'"><input type="hidden" name="phase" value="'.esc_attr($phase).'">';
    }
    private static function posted_text($key) { $raw=isset($_POST[$key])?wp_unslash($_POST[$key]):''; return is_scalar($raw)?sanitize_text_field((string)$raw):''; }
    private static function posted_textarea($key) { $raw=isset($_POST[$key])?wp_unslash($_POST[$key]):''; return is_scalar($raw)?sanitize_textarea_field((string)$raw):''; }
    private static function posted_datetime($key) {
        $raw=self::posted_text($key); if($raw==='')return '';
        $date=DateTime::createFromFormat('!Y-m-d\\TH:i',$raw,wp_timezone());
        $errors=DateTime::getLastErrors();
        if(!$date||($errors&&(!empty($errors['warning_count'])||!empty($errors['error_count']))))return '';
        return $date->format('Y-m-d H:i:s');
    }
    private static function datetime_local($value) { if(!$value||$value==='0000-00-00 00:00:00')return ''; $time=strtotime($value); return $time?wp_date('Y-m-d\TH:i',$time):''; }
    private static function phase_label($phase) { $labels=array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво'); return $labels[$phase]??$phase; }
    private static function redirect($scope, $notice) {
        $referer = wp_get_referer();
        if (!$referer) {
            $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
                ? wp_unslash($_SERVER['REQUEST_URI'])
                : '';
            if ($request_uri !== '' && $request_uri[0] === '/' && substr($request_uri, 0, 2) !== '//' &&
                strpos($request_uri, '\\') === false && !preg_match('/[\r\n\x00-\x1F\x7F]/', $request_uri)) {
                $candidate = home_url($request_uri);
                $validated = wp_validate_redirect($candidate, '');
                if ($validated !== '') {
                    $referer = $validated;
                }
            }
        }
        if (!$referer) {
            $referer = home_url('/');
        }

        $args = array(
            'zmp_ops_season' => $scope['season_id'],
            'zmp_ops_category' => $scope['category_id'],
            'zmp_ops_phase' => $scope['phase'],
            'zmp_ops_notice' => $notice,
        );
        $region = isset($_GET['zmp_ops_region']) ? sanitize_key(wp_unslash($_GET['zmp_ops_region'])) : '';
        if ($region !== '' && ZMP_Regions::valid_region($region)) {
            $args['zmp_ops_region'] = $region;
        }

        wp_safe_redirect(add_query_arg($args, $referer));
        exit;
    }
}
