<?php
if (!defined('ABSPATH')) { exit; }

/** Region exceptions are correspondence requests scoped to one teacher-owned school. */
class ZMP_Region_Requests {
    const NONCE = 'zmp_region_request';

    // Run after the plugin's init-time migration bridge (priority 10).
    public static function init() { add_action('init', array(__CLASS__, 'handle_post'), 20); }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_region_change_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_school_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            previous_region VARCHAR(30) NOT NULL,
            requested_region VARCHAR(30) NOT NULL,
            reason TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            decided_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            decision_note TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            decided_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_status (user_id,status),
            KEY school_status (mentor_school_id,status),
            KEY status_created (status,created_at)
        ) {$wpdb->get_charset_collate()};");
        $table = $wpdb->prefix . 'zmp_region_change_requests';
        return self::schema_is_ready($table);
    }

    /** Fail closed unless every required request column and index exists with the expected shape. */
    private static function schema_is_ready($table) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string)$found !== (string)$table) { return false; }
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . esc_sql($table) . "`");
        if (!is_array($columns)) { return false; }
        $required_columns = array('id','season_id','user_id','mentor_id','mentor_school_id','previous_region','requested_region','reason','status','decided_by','decision_note','created_at','decided_at');
        $column_names = array();
        foreach ($columns as $column) {
            $name = is_object($column) ? ($column->Field ?? '') : ($column['Field'] ?? '');
            if ($name !== '') { $column_names[(string)$name] = true; }
        }
        foreach ($required_columns as $name) { if (empty($column_names[$name])) { return false; } }
        $index_rows = $wpdb->get_results("SHOW INDEX FROM `" . esc_sql($table) . "`");
        if (!is_array($index_rows)) { return false; }
        $indexes = array();
        foreach ($index_rows as $row) {
            $get = static function($key) use ($row) { return is_object($row) ? ($row->$key ?? null) : ($row[$key] ?? null); };
            $key = (string)$get('Key_name'); $seq = (int)$get('Seq_in_index');
            if ($key === '' || $seq < 1) { continue; }
            $indexes[$key]['columns'][$seq] = (string)$get('Column_name');
            $indexes[$key]['unique'] = ((int)$get('Non_unique') === 0);
        }
        $required_indexes = array('PRIMARY'=>array(array('id'),true),'user_status'=>array(array('user_id','status'),false),'school_status'=>array(array('mentor_school_id','status'),false),'status_created'=>array(array('status','created_at'),false));
        foreach ($required_indexes as $name => $expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns']) !== $expected[0] || $indexes[$name]['unique'] !== $expected[1]) { return false; }
        }
        return true;
    }

    public static function handle_post() {
        $action = sanitize_key(wp_unslash($_POST['zmp_region_request_action'] ?? ''));
        if (!$action) { return; }
        if (!is_user_logged_in() || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), self::NONCE)) {
            wp_die('Безбедносната проверка не помина.', '', array('response'=>403));
        }
        if ($action === 'request') { self::handle_request(); return; }
        if ($action === 'decide') { self::handle_decision(); return; }
        self::redirect('invalid_request');
    }

    private static function handle_request() {
        global $wpdb;
        if (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard') && !current_user_can('manage_options')) {
            wp_die('Само наставник може да побара промена.', '', array('response'=>403));
        }
        $user_id = get_current_user_id();
        $mentor = self::mentor_for_user($user_id);
        $school_id = absint($_POST['mentor_school_id'] ?? 0);
        $school = $mentor && $school_id ? ZMP_Mentor_Schools::owned_school((int)$mentor->id, $school_id) : null;
        $region = sanitize_key(wp_unslash($_POST['requested_region'] ?? ''));
        $reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
        if (!$mentor || !$school || (int)$school->mentor_id !== (int)$mentor->id || !ZMP_Regions::valid_region($region) || self::text_length($reason) < 20) {
            self::redirect('invalid_request');
        }
        if ($region === $school->region) { self::redirect('same_region'); }
        $pending = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_region_change_requests WHERE user_id=%d AND mentor_school_id=%d AND status='pending'",
            $user_id,
            $school_id
        ));
        if ($pending) { self::redirect('pending_exists'); }
        $moderators = get_users(array('role'=>'zmp_moderator','fields'=>array('ID')));
        if (!$moderators) { self::redirect('message_failed'); }
        $season = ZMP_Category_Registrations::active_season();
        if ($wpdb->query('START TRANSACTION') === false) { self::redirect('save_error'); }
        $ok = $wpdb->insert($wpdb->prefix.'zmp_region_change_requests', array(
            'season_id'=>$season ? (int)$season->id : 0,
            'user_id'=>$user_id,
            'mentor_id'=>(int)$mentor->id,
            'mentor_school_id'=>$school_id,
            'previous_region'=>sanitize_key($school->region),
            'requested_region'=>$region,
            'reason'=>$reason,
            'status'=>'pending',
            'decision_note'=>'',
            'created_at'=>current_time('mysql'),
        ));
        if (!$ok) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        $request_id = (int) $wpdb->insert_id;
        foreach ($moderators as $moderator) {
            $message = $reason . "\n\nУчилиште: " . $school->school_name . "\nОпштина: " . $school->municipality . "\nБаран регион: " . ZMP_Regions::label($region);
            if (!$wpdb->insert($wpdb->prefix.'zmp_internal_messages', array(
                'sender_id'=>$user_id,
                'sender_role'=>'zmp_teacher',
                'recipient_id'=>(int)$moderator->ID,
                'competition_id'=>0,
                'subject'=>'Барање за промена на регион за училиште #'.$school_id.' (барање #'.$request_id.')',
                'message'=>$message,
                'status'=>'unread',
                'created_at'=>current_time('mysql'),
            ))) { $wpdb->query('ROLLBACK'); self::redirect('message_failed'); }
        }
        if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        self::redirect('request_sent');
    }

    private static function handle_decision() {
        global $wpdb;
        if (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options')) {
            wp_die('Само модератор може да одлучи.', '', array('response'=>403));
        }
        $id = absint($_POST['request_id'] ?? 0);
        $decision = sanitize_key(wp_unslash($_POST['decision'] ?? ''));
        $note = sanitize_textarea_field(wp_unslash($_POST['decision_note'] ?? ''));
        if (!$id || !in_array($decision, array('approved','rejected'), true) || self::text_length($note) < 20) { self::redirect('invalid_decision'); }
        if ($wpdb->query('START TRANSACTION') === false) { self::redirect('save_error'); }
        $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_region_change_requests WHERE id=%d AND status='pending' FOR UPDATE", $id));
        // Historical mentor-wide rows (school id 0) remain visible but cannot be reused
        // as an instruction to change every school under the teacher.
        if (!$req || (int)$req->mentor_school_id < 1) { $wpdb->query('ROLLBACK'); self::redirect('legacy_scope'); }
        $mentor = self::mentor_for_user((int)$req->user_id);
        $school = ZMP_Mentor_Schools::owned_school((int)$req->mentor_id, (int)$req->mentor_school_id);
        if (!$mentor || (int)$mentor->id !== (int)$req->mentor_id || !$school || (int)$school->mentor_id !== (int)$req->mentor_id || !ZMP_Regions::valid_region($req->requested_region)) {
            $wpdb->query('ROLLBACK');
            self::redirect('school_unavailable');
        }
        $saved = $wpdb->update($wpdb->prefix.'zmp_region_change_requests', array(
            'status'=>$decision,
            'decided_by'=>get_current_user_id(),
            'decision_note'=>$note,
            'decided_at'=>current_time('mysql'),
        ), array('id'=>$id,'status'=>'pending'));
        if ($saved !== 1) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if ($decision === 'approved') {
            $applied = ZMP_Mentor_Schools::apply_moderator_region_override((int)$req->mentor_id, (int)$req->mentor_school_id, $req->requested_region, false);
            if (is_wp_error($applied)) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        }
        $notice = ($decision==='approved' ? 'Барањето за училиште „'.$school->school_name.'“ е одобрено. ' : 'Барањето за училиште „'.$school->school_name.'“ е одбиено. ') . $note;
        if (!$wpdb->insert($wpdb->prefix.'zmp_internal_messages', array(
            'sender_id'=>get_current_user_id(),
            'sender_role'=>'zmp_moderator',
            'recipient_id'=>(int)$req->user_id,
            'competition_id'=>0,
            'subject'=>'Одлука за регионално барање #'.$id,
            'message'=>$notice,
            'status'=>'unread',
            'created_at'=>current_time('mysql'),
        ))) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
        if ($decision === 'approved' && (int)$school->is_primary === 1) {
            update_user_meta((int)$req->user_id, 'zmp_region', $req->requested_region);
        }
        self::redirect('decision_saved');
    }

    public static function render_teacher_panel() {
        if (!is_user_logged_in() || (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard') && !current_user_can('manage_options'))) { return ''; }
        global $wpdb;
        $mentor = self::mentor_for_user(get_current_user_id());
        if (!$mentor) { return ''; }
        ZMP_Mentor_Schools::ensure_primary($mentor);
        $schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id);
        if (!$schools) { return ''; }
        $status = sanitize_key(wp_unslash($_GET['zmp_region_status'] ?? ''));
        $messages = array(
            'request_sent'=>'Барањето е заведено и испратено до модераторите преку допис.',
            'pending_exists'=>'Веќе има барање што чека одлука за ова училиште.',
            'same_region'=>'Избраниот регион веќе е поставен за ова училиште.',
            'decision_saved'=>'Одлуката е евидентирана; резултатот е испратен преку допис.',
            'invalid_request'=>'Изберете свое активно училиште и внесете важечки регион и образложение.',
            'message_failed'=>'Барањето не можеше да се испрати до модераторите.',
            'invalid_decision'=>'Внесете одлука и образложение.',
            'school_unavailable'=>'Училиштето повеќе не е достапно за оваа одлука.',
            'legacy_scope'=>'Старото барање нема целно училиште; направете ново училишно барање.',
            'save_error'=>'Неуспешно зачувување.',
        );
        ob_start(); ?>
        <section class="zmp-card"><h3>Регион по училиште</h3>
        <?php if(isset($messages[$status])): ?><p class="zmp-alert"><?php echo esc_html($messages[$status]); ?></p><?php endif; ?>
        <p>Регионот за секое училиште се одредува од општината. Исклучок се бара и одобрува одделно за конкретно училиште. Одобрениот регион важи за идните пријави од тоа училиште; постојните пријави и резултати ги задржуваат зачуваните податоци.</p>
        <?php foreach ($schools as $school):
            $pending = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_region_change_requests WHERE user_id=%d AND mentor_school_id=%d AND status='pending'", get_current_user_id(), (int)$school->id));
        ?>
            <article class="zmp-card"><h4><?php echo esc_html($school->school_name); ?></h4><p><?php echo esc_html($school->municipality); ?> · Тековен регион: <strong><?php echo esc_html(ZMP_Regions::label($school->region)); ?></strong><?php if (!empty($school->region_is_override)): ?> · Одобрен исклучок<?php endif; ?></p>
            <?php if ($pending): ?><p class="zmp-alert">Барањето за ова училиште чека одлука.</p><?php else: ?>
                <form method="post" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="zmp_region_request_action" value="request"><input type="hidden" name="mentor_school_id" value="<?php echo (int)$school->id; ?>">
                <label>Баран регион<select name="requested_region" required><?php foreach(ZMP_Regions::regions() as $key=>$row): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($school->region,$key); ?>><?php echo esc_html($row['label']); ?></option><?php endforeach; ?></select></label>
                <label>Образложение (најмалку 20 знаци)<textarea name="reason" minlength="20" required></textarea></label><button class="button">Испрати барање преку допис</button></form>
            <?php endif; ?></article>
        <?php endforeach; ?>
        <?php
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT r.requested_region,r.status,r.reason,r.decision_note,r.created_at,r.decided_at,r.mentor_school_id,s.school_name
             FROM {$wpdb->prefix}zmp_region_change_requests r LEFT JOIN {$wpdb->prefix}zmp_mentor_schools s ON s.id=r.mentor_school_id AND s.mentor_id=r.mentor_id
             WHERE r.user_id=%d ORDER BY r.created_at DESC LIMIT 20",
            get_current_user_id()
        ));
        if ($history): ?><h4>Историја на барања и одлуки</h4><ul><?php foreach($history as $item): $label=array('pending'=>'Чека одлука','approved'=>'Одобрено','rejected'=>'Одбиено')[$item->status]??$item->status; ?><li><?php echo esc_html(((int)$item->mentor_school_id===0?'Претходно барање без училишна врска':($item->school_name?:'Училиште #'.(int)$item->mentor_school_id)).' · '.ZMP_Regions::label($item->requested_region).' · '.$label.' · '.$item->created_at); ?><?php if($item->decision_note): ?><br><?php echo nl2br(esc_html($item->decision_note)); ?><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?></section>
        <?php return ob_get_clean();
    }

    public static function render_moderator_panel() {
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) { return ''; }
        global $wpdb;
        $rows = $wpdb->get_results("SELECT r.*,m.full_name,s.school_name,s.municipality,u.user_email FROM {$wpdb->prefix}zmp_region_change_requests r JOIN {$wpdb->prefix}zmp_mentors m ON m.id=r.mentor_id LEFT JOIN {$wpdb->prefix}zmp_mentor_schools s ON s.id=r.mentor_school_id AND s.mentor_id=r.mentor_id LEFT JOIN {$wpdb->users} u ON u.ID=r.user_id WHERE r.status='pending' ORDER BY r.created_at");
        ob_start(); ?><section class="zmp-card"><h3>Барања за промена на регион</h3><?php if (!$rows): ?><p>Нема барања што чекаат одлука.</p><?php else: foreach($rows as $row): ?>
            <article class="zmp-card"><p><strong><?php echo esc_html($row->full_name); ?></strong> · <?php if ((int)$row->mentor_school_id > 0 && $row->school_name): ?><?php echo esc_html($row->school_name.' — '.$row->municipality); ?><?php else: ?>Претходно профилско барање без избрано училиште<?php endif; ?> · <?php echo esc_html(ZMP_Regions::label($row->previous_region).' → '.ZMP_Regions::label($row->requested_region)); ?></p><p><?php echo nl2br(esc_html($row->reason)); ?></p>
            <?php if ((int)$row->mentor_school_id < 1 || !$row->school_name): ?><p class="zmp-alert">Историскиот запис нема училишен опсег и не може да одобри или измени училишта.</p><?php else: ?>
                <form method="post" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="zmp_region_request_action" value="decide"><input type="hidden" name="request_id" value="<?php echo (int)$row->id; ?>"><label>Образложение на одлуката (најмалку 20 знаци)<textarea name="decision_note" minlength="20" required></textarea></label><button name="decision" value="approved" class="button button-primary">Одобри за ова училиште</button><button name="decision" value="rejected" class="button">Одбиј</button></form>
            <?php endif; ?></article><?php endforeach; endif; ?></section><?php return ob_get_clean();
    }

    private static function mentor_for_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d LIMIT 1", absint($user_id)));
    }

    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string)$value, 'UTF-8'); }
        if (preg_match_all('/./us', (string)$value, $matches)) { return count($matches[0]); }
        return strlen((string)$value);
    }

    private static function redirect($status) {
        wp_safe_redirect(add_query_arg('zmp_region_status', sanitize_key($status), wp_get_referer() ?: home_url('/')));
        exit;
    }
}



