<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Appointment, access-window and append-only audit handling for the President.
 *
 * The stored appointment is authoritative. A role alone never grants President
 * access; the appointment must also be active for the current site-local date.
 */
class ZMP_President_Mandate {
    const SCHEMA_VERSION = '2';
    const CRON_HOOK = 'zmp_president_mandate_daily_sync';
    const HANDOVER_NONCE_FIELD = 'zmp_president_handover_nonce';

    public static function init() {
        add_action(self::CRON_HOOK, array(__CLASS__, 'sync_expirations_cron'));
        add_action('admin_post_zmp_president_handover_extend', array(__CLASS__, 'handle_handover_extension'));
        add_action('init', array(__CLASS__, 'maybe_install'), 1);
        add_action('init', array(__CLASS__, 'sync_expirations_request'), 12);
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function sync_expirations_request() {
        if (!self::can_manage()) { return 0; }
        return self::sync_expirations();
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) { wp_unschedule_event($timestamp, self::CRON_HOOK); }
    }

    public static function sync_expirations_cron() {
        return self::sync_expirations();
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_president_mandates';
    }

    public static function audit_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_president_mandate_audit';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $mandates = self::table_name();
        $audit = self::audit_table_name();

        $sql_mandates = "CREATE TABLE {$mandates} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            board_decision text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            appointed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            appointed_at datetime NOT NULL,
            closed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            closed_at datetime DEFAULT NULL,
            close_reason text NOT NULL,
            predecessor_mandate_id bigint(20) unsigned NOT NULL DEFAULT 0,
            predecessor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            handover_deadline date DEFAULT NULL,
            handover_extension_months int(10) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status_dates (status,start_date,end_date),
            KEY predecessor_user (predecessor_user_id),
            KEY handover_deadline (handover_deadline)
        ) {$charset_collate} ENGINE=InnoDB;";
        $sql_audit = "CREATE TABLE {$audit} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mandate_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            event_type varchar(32) NOT NULL,
            actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reason text NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY mandate_id (mandate_id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate} ENGINE=InnoDB;";

        dbDelta($sql_mandates);
        dbDelta($sql_audit);
        if (self::tables_exist()) {
            update_option('zmp_president_mandate_schema_version', self::SCHEMA_VERSION, false);
            return true;
        }
        return false;
    }

    public static function maybe_install() {
        if (get_option('zmp_president_mandate_schema_version') === self::SCHEMA_VERSION && self::tables_exist()) {
            return true;
        }
        return self::install();
    }

    private static function tables_exist() {
        global $wpdb;
        foreach (array(self::table_name(), self::audit_table_name()) as $table) {
            $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
            if ($found !== $table) { return false; }
        }
        return true;
    }

    private static function error($code, $message) {
        return new WP_Error($code, $message);
    }

    private static function can_manage() {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    private static function valid_date($value) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) { return false; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
        $errors = DateTimeImmutable::getLastErrors();
        return $date instanceof DateTimeImmutable
            && (!$errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']))
            && $date->format('Y-m-d') === $value;
    }

    private static function today() {
        return current_time('Y-m-d');
    }

    /** Return the inclusive final day of a four-calendar-year mandate. */
    public static function term_end_date($start_date) {
        if (!self::valid_date($start_date)) {
            return self::error('mandate_invalid_start_date', 'Почетниот датум не е валиден.');
        }
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $start_date, wp_timezone());
        return $start->add(new DateInterval('P4Y'))->modify('-1 day')->format('Y-m-d');
    }

    /** Add calendar months without overflowing short months; retain the start-day anchor. */
    private static function add_calendar_months($date_string, $months, $anchor_day = 0) {
        if (!self::valid_date($date_string) || !is_numeric($months)) { return false; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $date_string, wp_timezone());
        $month_index = ((int) $date->format('Y') * 12) + ((int) $date->format('n') - 1) + (int) $months;
        $year = (int) floor($month_index / 12);
        $month = ($month_index % 12) + 1;
        $first = $date->setDate($year, $month, 1);
        $last_day = (int) $first->format('t');
        $day = $anchor_day > 0 ? $anchor_day : (int) $date->format('j');
        return $first->setDate($year, $month, min($day, $last_day))->format('Y-m-d');
    }

    private static function initial_handover_deadline($start_date) {
        return self::add_calendar_months($start_date, 3);
    }

    /** Return the former President's sole pending handover-extension context. */
    public static function handover_extension_context() {
        global $wpdb;
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('read') || current_user_can('manage_options') || !self::tables_exist()) { return false; }
        $today = self::today();
        $context = $wpdb->get_row($wpdb->prepare(
            'SELECT id AS successor_mandate_id,user_id AS successor_user_id,predecessor_mandate_id,predecessor_user_id,handover_deadline,handover_extension_months,start_date FROM ' . self::table_name() . " WHERE predecessor_user_id=%d AND status IN ('active','scheduled') AND handover_deadline>%s ORDER BY start_date ASC,id ASC LIMIT 1",
            $user_id,
            $today
        ));
        if (!$context || (int) $context->predecessor_user_id !== (int) $user_id || !self::valid_date((string) $context->handover_deadline)) { return false; }
        return (object) array(
            'successor_mandate_id' => (int) $context->successor_mandate_id,
            'successor_user_id' => (int) $context->successor_user_id,
            'predecessor_mandate_id' => (int) $context->predecessor_mandate_id,
            'deadline' => (string) $context->handover_deadline,
            'extension_months' => (int) $context->handover_extension_months,
            'start_date' => (string) $context->start_date,
        );
    }

    /** Extend this logged-in former President's linked successor deadline by one month. */
    public static function extend_handover_deadline($reason) {
        if (!self::handover_extension_context()) { return self::error('handover_extension_forbidden', 'Немате отворено предавање што може да се продолжи.'); }
        return self::with_transition_lock(function () use ($reason) {
            return self::extend_handover_deadline_locked($reason);
        });
    }

    private static function extend_handover_deadline_locked($reason) {
        global $wpdb;
        $context = self::handover_extension_context();
        if (!$context) { return self::error('handover_extension_forbidden', 'Немате отворено предавање што може да се продолжи.'); }
        if (!is_string($reason)) { return self::error('handover_extension_reason_required', 'Внесете причина за продолжувањето.'); }
        $reason = trim(sanitize_textarea_field($reason));
        if ($reason === '' || strlen($reason) > 2000) { return self::error('handover_extension_reason_required', 'Внесете кратка причина за продолжувањето.'); }
        $today = self::today();
        if ($context->deadline <= $today) { return self::error('handover_extension_expired', 'Рокот за предавање веќе истекол.'); }
        if (!self::transaction_start()) { return self::error('handover_extension_write_failed', 'Не може безбедно да се започне продолжувањето.'); }
        $new_deadline = self::add_calendar_months($context->start_date, 3 + $context->extension_months + 1);
        if (!$new_deadline) { self::transaction_finish(false); return self::error('handover_extension_write_failed', 'Не може да се пресмета новиот рок.'); }
        $updated = $wpdb->update(self::table_name(), array(
            'handover_deadline' => $new_deadline,
            'handover_extension_months' => $context->extension_months + 1,
        ), array(
            'id' => $context->successor_mandate_id,
            'predecessor_user_id' => get_current_user_id(),
            'handover_deadline' => $context->deadline,
            'handover_extension_months' => $context->extension_months,
        ), array('%s','%d'), array('%d','%d','%s','%d'));
        if (1 !== $updated) {
            self::transaction_finish(false);
            return self::error('handover_extension_write_failed', 'Рокот е сменет или не може да се зачува. Освежете ја страницата.');
        }
        $event = self::append_event(
            $context->successor_mandate_id,
            $context->successor_user_id,
            'handover_deadline_extended',
            $reason,
            array('previous_deadline' => $context->deadline, 'new_deadline' => $new_deadline, 'extension_months' => $context->extension_months + 1),
            get_current_user_id()
        );
        if (false === $event) {
            self::transaction_finish(false);
            return self::error('handover_extension_audit_failed', 'Продолжувањето не е зачувано во ревизиската евиденција.');
        }
        if (!self::transaction_finish(true)) { return self::error('handover_extension_commit_uncertain', 'Проверете ја ревизиската евиденција пред повторно да испратите барање.'); }
        return $new_deadline;
    }

    public static function handle_handover_extension() {
        $page = admin_url('admin.php?page=zmp-president-handover-extension');
        $notice = 'forbidden';
        $nonce = isset($_POST[self::HANDOVER_NONCE_FIELD]) ? sanitize_text_field(wp_unslash($_POST[self::HANDOVER_NONCE_FIELD])) : '';
        if (is_user_logged_in() && current_user_can('read') && wp_verify_nonce($nonce, 'zmp_president_handover_extend')) {
            $result = self::extend_handover_deadline(wp_unslash($_POST['handover_reason'] ?? ''));
            $notice = is_wp_error($result) ? 'error_' . sanitize_key($result->get_error_code()) : 'extended';
        }
        wp_safe_redirect(add_query_arg('zmp_handover_notice', $notice, $page));
        exit;
    }

    private static function transaction_start() {
        global $wpdb;
        return false !== $wpdb->query('START TRANSACTION');
    }

    private static function transaction_finish($commit) {
        global $wpdb;
        if ($commit) {
            return false !== $wpdb->query('COMMIT');
        }
        $wpdb->query('ROLLBACK');
        return false;
    }

    private static function transition_lock_name() {
        global $wpdb;
        $database = isset($wpdb->dbname) ? (string) $wpdb->dbname : '';
        return 'zmp_pm_' . substr(md5($database . '|' . $wpdb->prefix), 0, 32);
    }

    private static function acquire_transition_lock() {
        global $wpdb;
        $lock_name = self::transition_lock_name();
        return '1' === (string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock_name));
    }

    private static function release_transition_lock() {
        global $wpdb;
        $lock_name = self::transition_lock_name();
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
    }

    private static function with_transition_lock($callback) {
        // Keep mandate state changes in the same critical section as certificate
        // authorization and PDF persistence. The shared facsimile lock is always
        // acquired first so every path uses one lock order.
        if (!class_exists('ZMP_Registration_Requirements')
            || !is_callable(array('ZMP_Registration_Requirements', 'acquire_facsimile_write_lock'))
            || !is_callable(array('ZMP_Registration_Requirements', 'release_facsimile_write_lock'))
            || !ZMP_Registration_Requirements::acquire_facsimile_write_lock()) {
            return self::error('mandate_transition_busy', 'Друга промена на мандат е во тек.');
        }

        $transition_lock_acquired = false;
        try {
            if (!self::acquire_transition_lock()) {
                return self::error('mandate_transition_busy', 'Друга промена на мандат е во тек.');
            }
            $transition_lock_acquired = true;
            return call_user_func($callback);
        } finally {
            if ($transition_lock_acquired) {
                self::release_transition_lock();
            }
            ZMP_Registration_Requirements::release_facsimile_write_lock();
        }
    }

    /** Serialize a protected governance-document read with mandate transitions. */
    public static function with_governance_document_read_lock($callback) {
        if (!is_callable($callback)) { return new WP_Error('governance_read_callback_invalid', 'Барањето не може да се изврши.'); }
        return self::with_transition_lock($callback);
    }

    private static function append_event($mandate_id, $user_id, $event_type, $reason = '', $event_data = array(), $actor_id = null) {
        global $wpdb;
        $data = wp_json_encode($event_data);
        if (!is_string($data)) { $data = '{}'; }
        $actor_id = null === $actor_id ? get_current_user_id() : (int) $actor_id;
        $inserted = $wpdb->insert(
            self::audit_table_name(),
            array(
                'mandate_id' => (int) $mandate_id,
                'user_id' => (int) $user_id,
                'event_type' => sanitize_key($event_type),
                'actor_user_id' => $actor_id,
                'reason' => sanitize_textarea_field((string) $reason),
                'event_data' => $data,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s')
        );
        return false === $inserted ? false : (int) $wpdb->insert_id;
    }

    /** Append a bounded governance event to the existing mandate audit ledger. */
    public static function record_governance_event($mandate_id, $user_id, $event_type, $event_data = array()) {
        $allowed = array('election_document_uploaded', 'election_document_archived', 'election_documents_auto_archived', 'election_document_encrypted', 'obligation_published', 'obligation_acknowledged');
        if (!in_array((string) $event_type, $allowed, true) || !is_array($event_data)) { return false; }
        $actor_id = get_current_user_id();
        if ('obligation_acknowledged' === $event_type) {
            $actor = $actor_id ? get_user_by('id', $actor_id) : false;
            if (!$actor instanceof WP_User || (int) $user_id !== (int) $actor_id
                || !ZMP_Mentor_School_Profile::has_role_stack((int) $actor->ID, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
                || !user_can($actor, 'view_zmp_president_dashboard')) { return false; }
            global $wpdb;
            $today = current_time('Y-m-d');
            $active_id = $wpdb->get_var($wpdb->prepare(
                'SELECT id FROM ' . self::table_name() . " WHERE id=%d AND user_id=%d AND status='active' AND start_date<=%s AND end_date>=%s LIMIT 1",
                absint($mandate_id), (int) $actor_id, $today, $today
            ));
            if ((int) $active_id !== absint($mandate_id)) { return false; }
        } else {
            $system_archive = in_array($event_type, array('election_documents_auto_archived', 'election_document_encrypted'), true)
                && function_exists('wp_doing_cron') && wp_doing_cron();
            if (!is_user_logged_in() && !$system_archive) { return false; }
            if (!current_user_can('manage_options') && !$system_archive) { return false; }
            if ('obligation_published' === $event_type) {
                if (absint($mandate_id) !== 0 || absint($user_id) !== 0) { return false; }
            } else {
                global $wpdb;
                if ('election_documents_auto_archived' === $event_type) {
                    if (!isset($event_data['document_id'], $event_data['successor_mandate_id'], $event_data['effective_date'])
                        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $event_data['effective_date'])) { return false; }
                    $previous_user = $wpdb->get_var($wpdb->prepare('SELECT user_id FROM ' . self::table_name() . ' WHERE id=%d', absint($mandate_id)));
                    $successor = $wpdb->get_var($wpdb->prepare(
                        'SELECT id FROM ' . self::table_name() . ' WHERE id=%d AND predecessor_mandate_id=%d AND handover_deadline<=%s',
                        absint($event_data['successor_mandate_id']), absint($mandate_id), (string) $event_data['effective_date']
                    ));
                    $document = $wpdb->get_var($wpdb->prepare(
                        'SELECT id FROM ' . $wpdb->prefix . 'zmp_president_documents WHERE id=%d AND mandate_id=%d AND president_user_id=%d AND status=%s AND encryption_version=%d',
                        absint($event_data['document_id']), absint($mandate_id), absint($user_id), 'archived', 1
                    ));
                    if (!$previous_user || (int) $previous_user !== absint($user_id) || !$successor || !$document) { return false; }
                } elseif ('election_document_encrypted' === $event_type) {
                    $previous_user = $wpdb->get_var($wpdb->prepare('SELECT user_id FROM ' . self::table_name() . ' WHERE id=%d', absint($mandate_id)));
                    $document = $wpdb->get_var($wpdb->prepare(
                        'SELECT id FROM ' . $wpdb->prefix . 'zmp_president_documents WHERE id=%d AND mandate_id=%d AND president_user_id=%d AND status=%s AND encryption_version=%d',
                        absint($event_data['document_id'] ?? 0), absint($mandate_id), absint($user_id), 'archived', 1
                    ));
                    if (!$previous_user || (int) $previous_user !== absint($user_id) || !$document) { return false; }
                } else {
                    $linked_user = $wpdb->get_var($wpdb->prepare('SELECT user_id FROM ' . self::table_name() . ' WHERE id=%d', absint($mandate_id)));
                    if (!$linked_user || (int) $linked_user !== absint($user_id)) { return false; }
                }
            }
        }
        return false !== self::append_event(absint($mandate_id), absint($user_id), $event_type, '', $event_data);
    }

    private static function grant_president_role($user, $effective_date = null) {
        if ($user instanceof WP_User) {
            $user = get_user_by('id', (int) $user->ID);
        }
        if (!($user instanceof WP_User) || !self::eligible_appointee($user)) {
            return false;
        }
        return function_exists('zmp_competitions_grant_president_role')
            && zmp_competitions_grant_president_role($user, null === $effective_date ? self::today() : $effective_date);
    }

    private static function eligible_appointee($user) {
        if (!($user instanceof WP_User) || in_array('administrator', (array) $user->roles, true) || $user->has_cap('manage_options')) { return false; }
        $roles = array_values((array) $user->roles);
        $allowed_roles = array('subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer');
        if (!in_array('subscriber', $roles, true) || array_diff($roles, $allowed_roles)) { return false; }
        $cap_roles = array_keys((array) $user->caps);
        sort($roles); sort($cap_roles);
        if ($roles !== $cap_roles) { return false; }
        foreach ($roles as $role) { if (empty($user->caps[$role])) { return false; } }
        return true;
    }
    private static function remove_president_role($user_id) {
        $user = get_user_by('id', (int) $user_id);
        if (!($user instanceof WP_User)) { return false; }
        if (in_array('zmp_president', (array) $user->roles, true)) {
            $user->remove_role('zmp_president');
            if (empty($user->roles)) {
                $user->add_role('subscriber');
            }
        }
        $user->remove_cap('view_zmp_president_dashboard');
        clean_user_cache((int) $user_id);
        $fresh = get_user_by('id', (int) $user_id);
        return $fresh instanceof WP_User
            && !in_array('zmp_president', (array) $fresh->roles, true)
            && !user_can($fresh, 'view_zmp_president_dashboard');
    }

    /** Create a new appointment, replacing a current mandate only for a same-day transition. */
    public static function appoint($user_id, $start_date, $end_date, $board_decision) {
        if (!self::can_manage()) { return self::error('appointment_requires_admin', 'Само администратор може да назначи Претседател.'); }
        return self::with_transition_lock(function () use ($user_id, $start_date, $end_date, $board_decision) {
            return self::appoint_locked($user_id, $start_date, $end_date, $board_decision);
        });
    }

    private static function appoint_locked($user_id, $start_date, $end_date, $board_decision) {
        global $wpdb;
        if (!self::can_manage()) { return self::error('appointment_requires_admin', 'Само администратор може да назначи Претседател.'); }
        if (!self::tables_exist()) { return self::error('mandate_schema_not_ready', 'Евиденцијата за мандати не е подготвена.'); }
        if (!self::valid_date($start_date)) { return self::error('mandate_invalid_start_date', 'Почетниот датум не е валиден.'); }
        if (!self::valid_date($end_date)) { return self::error('mandate_invalid_end_date', 'Крајниот датум не е валиден.'); }
        $required_end_date = self::term_end_date($start_date);
        if (is_wp_error($required_end_date) || !hash_equals((string) $required_end_date, (string) $end_date)) {
            return self::error('mandate_invalid_term_duration', 'Мандатот мора да трае точно четири календарски години.');
        }
        if ($end_date < $start_date) { return self::error('mandate_invalid_date_range', 'Датумите на мандатот не се валиден период.'); }
        if ($end_date < self::today()) { return self::error('mandate_term_already_expired', 'Не може да се назначи мандат чиј период веќе е завршен.'); }
        if (!is_string($board_decision)) { return self::error('mandate_board_decision_required', 'Внесете ја одлуката на Управниот одбор.'); }
        $decision = trim(sanitize_textarea_field($board_decision));
        if ('' === $decision) { return self::error('mandate_board_decision_required', 'Внесете ја одлуката на Управниот одбор.'); }
        if (strlen($decision) > 4000) { return self::error('mandate_board_decision_required', 'Одлуката е предолга.'); }

        if (!is_scalar($user_id) || !is_numeric($user_id) || (int) $user_id < 1) {
            return self::error('mandate_invalid_user', 'Изберете постоечки WordPress корисник.');
        }
        $user = get_user_by('id', absint($user_id));
        if (!($user instanceof WP_User)) { return self::error('mandate_invalid_user', 'Изберете постоечки WordPress корисник.'); }
        if (in_array('administrator', (array) $user->roles, true) || $user->has_cap('manage_options')) {
            return self::error('president_account_admin', 'Претседателот мора да користи посебна сметка без администраторски права.');
        }
        if (!self::eligible_appointee($user)) {
            return self::error('president_account_ineligible', 'Изберете посебна сметка со единствена улога Претплатник и без дополнителни права.');
        }
        if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да започне евиденцијата за мандатот.'); }

        $today = self::today();
        $overlapping = $wpdb->get_results($wpdb->prepare(
            'SELECT id,user_id,start_date,end_date,status FROM ' . self::table_name() . " WHERE status IN ('active','scheduled') AND start_date <= %s AND end_date >= %s ORDER BY start_date ASC",
            $end_date,
            $start_date
        ));
        if (!is_array($overlapping)) { self::transaction_finish(false); return self::error('mandate_db_write_failed', 'Не може да се провери периодот на мандатот.'); }
        $current = array();
        foreach ($overlapping as $row) {
            $incumbent_active_today = 'active' === $row->status && $row->start_date <= $today && $row->end_date >= $today;
            $successor_active_today = $start_date <= $today && $end_date >= $today;
            if ($incumbent_active_today && $successor_active_today) {
                $current[] = $row;
                continue;
            }
            self::transaction_finish(false);
            return self::error('mandate_term_overlap', 'Периодот се преклопува со друг активен или закажан мандат.');
        }

        if (in_array('zmp_president', (array) $user->roles, true)) {
            $is_current_incumbent = (bool) $wpdb->get_var($wpdb->prepare(
                'SELECT id FROM ' . self::table_name() . " WHERE user_id = %d AND status = 'active' AND start_date <= %s AND end_date >= %s LIMIT 1",
                (int) $user->ID,
                $today,
                $today
            ));
            if (!$is_current_incumbent) {
                self::transaction_finish(false);
                return self::error('president_account_ineligible', 'Постоечката претседателска сметка може да се избере само за обновување или пренос на тековен мандат.');
            }
        }

        foreach ($current as $incumbent) {
            $updated = $wpdb->update(self::table_name(), array(
                'status' => 'replaced',
                'closed_by' => get_current_user_id(),
                'closed_at' => current_time('mysql'),
                'close_reason' => 'Replaced by an appointment effective today.',
            ), array('id' => (int) $incumbent->id, 'status' => 'active'), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
            if (1 !== $updated || false === self::append_event(
                (int) $incumbent->id,
                (int) $incumbent->user_id,
                'replaced',
                'The active mandate was replaced by a new appointment effective today.',
                array(
                    'previous_start_date' => $incumbent->start_date,
                    'previous_end_date' => $incumbent->end_date,
                    'successor_start_date' => $start_date,
                    'successor_end_date' => $end_date,
                    'effective_on' => $today,
                )
            )) {
                self::transaction_finish(false);
                foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
                return self::error('mandate_db_write_failed', 'Промената на мандатот не е зачувана.');
            }
        }

        $predecessor = null;
        if ($current) {
            $predecessor = reset($current);
        } else {
            // A valid scheduled term can immediately precede a later appointment.
            // Do not link a scheduled term that expired without ever becoming effective.
            $predecessor = $wpdb->get_row($wpdb->prepare(
                'SELECT id,user_id,start_date,end_date,status FROM ' . self::table_name() . " WHERE end_date<%s AND (status IN ('active','replaced','scheduled') OR (status='expired' AND close_reason<>'Scheduled term ended before activation.')) ORDER BY end_date DESC,id DESC LIMIT 1",
                $start_date
            ));
        }
        $predecessor_mandate_id = $predecessor ? absint($predecessor->id) : 0;
        $predecessor_user_id = $predecessor ? absint($predecessor->user_id) : 0;
        $handover_deadline = $predecessor ? self::initial_handover_deadline($start_date) : null;
        $initial_status = $start_date <= $today && $end_date >= $today ? 'active' : 'scheduled';
        $inserted = $wpdb->insert(self::table_name(), array(
            'user_id' => (int) $user->ID,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'board_decision' => $decision,
            'status' => $initial_status,
            'appointed_by' => get_current_user_id(),
            'appointed_at' => current_time('mysql'),
            'closed_by' => 0,
            'closed_at' => null,
            'close_reason' => '',
            'predecessor_mandate_id' => $predecessor_mandate_id,
            'predecessor_user_id' => $predecessor_user_id,
            'handover_deadline' => $handover_deadline,
            'handover_extension_months' => 0,
        ), array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%d'));
        $mandate_id = (int) $wpdb->insert_id;
        if (false === $inserted || $mandate_id < 1) {
            self::transaction_finish(false);
            foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
            return self::error('mandate_db_write_failed', 'Назначувањето не е зачувано.');
        }
        if (false === self::append_event($mandate_id, (int) $user->ID, 'appointed', '', array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'predecessor_mandate_id' => $predecessor_mandate_id,
            'predecessor_user_id' => $predecessor_user_id,
            'handover_deadline' => $handover_deadline,
        ))) {
            self::transaction_finish(false);
            foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
            return self::error('mandate_db_write_failed', 'Ревизиската евиденција не е зачувана.');
        }

        foreach ($current as $incumbent) {
            if (!self::remove_president_role((int) $incumbent->user_id)) {
                self::transaction_finish(false);
                foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
                return self::error('mandate_role_assignment_failed', 'Не може безбедно да се пренесе претседателскиот пристап.');
            }
        }
        if ('active' === $initial_status && !self::grant_president_role($user, $today)) {
            self::transaction_finish(false);
            clean_user_cache((int) $user->ID);
            foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
            return self::error('mandate_role_assignment_failed', 'Не може безбедно да се додели претседателската улога.');
        }
        if (!self::transaction_finish(true)) {
            clean_user_cache((int) $user->ID);
            foreach ($current as $cache_row) { clean_user_cache((int) $cache_row->user_id); }
            return self::error('mandate_db_write_failed', 'Промените на мандатот не се потврдени во базата.');
        }
        return $mandate_id;
    }

    public static function revoke($mandate_id, $reason) {
        if (!self::can_manage()) { return self::error('mandate_revoke_requires_admin', 'Само администратор може да отповика мандат.'); }
        return self::with_transition_lock(function () use ($mandate_id, $reason) {
            return self::revoke_locked($mandate_id, $reason);
        });
    }

    private static function revoke_locked($mandate_id, $reason) {
        global $wpdb;
        if (!self::can_manage()) { return self::error('mandate_revoke_requires_admin', 'Само администратор може да отповика мандат.'); }
        if (!self::tables_exist()) { return self::error('mandate_schema_not_ready', 'Евиденцијата за мандати не е подготвена.'); }
        if (!is_string($reason)) { return self::error('mandate_reason_required', 'Внесете причина за отповикувањето.'); }
        $reason = trim(sanitize_textarea_field($reason));
        if ('' === $reason) { return self::error('mandate_reason_required', 'Внесете причина за отповикувањето.'); }
        if (!is_scalar($mandate_id) || !is_numeric($mandate_id) || (int) $mandate_id < 1) {
            return self::error('mandate_not_found', 'Активниот мандат не е пронајден.');
        }
        $mandate = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table_name() . ' WHERE id = %d', absint($mandate_id)));
        if (!$mandate || !in_array($mandate->status, array('active', 'scheduled'), true)) {
            return self::error('mandate_not_found', 'Активниот мандат не е пронајден.');
        }
        if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да започне отповикувањето.'); }
        $updated = $wpdb->update(self::table_name(), array(
            'status' => 'revoked',
            'closed_by' => get_current_user_id(),
            'closed_at' => current_time('mysql'),
            'close_reason' => $reason,
        ), array('id' => (int) $mandate->id, 'status' => $mandate->status), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
        if (1 !== $updated || false === self::append_event((int) $mandate->id, (int) $mandate->user_id, 'revoked', $reason)) {
            self::transaction_finish(false);
            return self::error('mandate_db_write_failed', 'Отповикувањето не е запишано.');
        }
        if ('active' === $mandate->status && !self::remove_president_role((int) $mandate->user_id)) {
            self::transaction_finish(false);
            clean_user_cache((int) $mandate->user_id);
            return self::error('mandate_role_assignment_failed', 'Не може безбедно да се отстрани претседателскиот пристап.');
        }
        if (!self::transaction_finish(true)) {
            clean_user_cache((int) $mandate->user_id);
            return self::error('mandate_db_write_failed', 'Отповикувањето не е потврдено во базата.');
        }
        return true;
    }

    public static function current_mandate() {
        global $wpdb;
        if (!self::can_manage() || !self::tables_exist()) { return null; }
        $today = self::today();
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . " WHERE status = 'active' AND start_date <= %s AND end_date >= %s ORDER BY start_date DESC,id DESC LIMIT 1",
            $today,
            $today
        ));
    }

    /** Return future appointments that administrators may review or cancel. */
    public static function scheduled_mandates() {
        global $wpdb;
        if (!self::can_manage()) { return self::error('appointment_requires_admin', 'Закажаните мандати се достапни само за администратор.'); }
        if (!self::tables_exist()) { return self::error('mandate_schema_not_ready', 'Евиденцијата за мандати не е подготвена.'); }
        $rows = $wpdb->get_results('SELECT id,user_id,start_date,end_date,board_decision,status,appointed_by,appointed_at FROM ' . self::table_name() . " WHERE status = 'scheduled' ORDER BY start_date ASC,id ASC");
        return is_array($rows) ? $rows : self::error('mandate_db_write_failed', 'Не може да се вчитаат закажаните мандати.');
    }

    public static function history($user_id = 0) {
        global $wpdb;
        if (!self::can_manage()) { return self::error('appointment_requires_admin', 'Ревизиската историја е достапна само за администратор.'); }
        if (!self::tables_exist()) { return self::error('mandate_schema_not_ready', 'Евиденцијата за мандати не е подготвена.'); }
        $sql = 'SELECT a.id AS audit_id,a.mandate_id,a.user_id,a.event_type,a.actor_user_id,a.reason,a.event_data,a.created_at,m.start_date,m.end_date,m.board_decision,m.status FROM ' . self::audit_table_name() . ' a LEFT JOIN ' . self::table_name() . ' m ON m.id = a.mandate_id';
        if (!is_scalar($user_id) || !is_numeric($user_id)) {
            return self::error('mandate_invalid_user', 'Корисникот за историјата не е валиден.');
        }
        if ((int) $user_id > 0) {
            $sql .= $wpdb->prepare(' WHERE a.user_id = %d', absint($user_id));
        }
        $sql .= ' ORDER BY a.id DESC LIMIT 500';
        $rows = $wpdb->get_results($sql);
        return is_array($rows) ? $rows : self::error('mandate_db_write_failed', 'Не може да се вчита ревизиската историја.');
    }

    public static function user_has_active_mandate($user_id) {
        global $wpdb;
        $user_id = absint($user_id);
        if ($user_id < 1 || !self::tables_exist()) { return false; }
        $today = self::today();
        $mandate_id = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . self::table_name() . " WHERE user_id = %d AND status = 'active' AND start_date <= %s AND end_date >= %s ORDER BY start_date DESC,id DESC LIMIT 1",
            $user_id,
            $today,
            $today
        ));
        return (int) $mandate_id > 0;
    }

    /** Apply due starts/expiries; the optional date exists for deterministic synthetic checks. */
    public static function sync_expirations($today = null) {
        $is_cron = function_exists('wp_doing_cron') && wp_doing_cron();
        if (!self::can_manage() && !$is_cron) {
            return self::error('appointment_requires_admin', 'Само администратор може да синхронизира мандати.');
        }
        return self::with_transition_lock(function () use ($today) {
            return self::sync_expirations_locked($today);
        });
    }

    private static function sync_expirations_locked($today = null) {
        global $wpdb;
        $today = null === $today ? self::today() : $today;
        if (!self::valid_date($today)) { return self::error('mandate_invalid_start_date', 'Датумот за синхронизација не е валиден.'); }
        if (!self::tables_exist()) { return self::error('mandate_schema_not_ready', 'Евиденцијата за мандати не е подготвена.'); }

        $expired = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table_name() . " WHERE status = 'active' AND end_date < %s", $today));
        if (!is_array($expired)) { return self::error('mandate_db_write_failed', 'Не може да се проверат истечените мандати.'); }
        foreach ($expired as $mandate) {
            if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да се запише истекот.'); }
            $updated = $wpdb->update(self::table_name(), array('status' => 'expired', 'closed_by' => 0, 'closed_at' => current_time('mysql'), 'close_reason' => 'Mandate term ended.'), array('id' => (int) $mandate->id, 'status' => 'active'), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
            $audited = 1 === $updated && false !== self::append_event((int) $mandate->id, (int) $mandate->user_id, 'expired', 'Mandate term ended.', array('effective_date' => $today), 0);
            $removed = self::remove_president_role((int) $mandate->user_id);
            if (!$audited || !$removed || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                clean_user_cache((int) $mandate->user_id);
                return self::error('mandate_db_write_failed', 'Истекот на мандатот не е целосно запишан.');
            }
        }

        // Expire scheduled appointments whose whole term passed without ever
        // granting the President role.
        $missed = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table_name() . " WHERE status = 'scheduled' AND end_date < %s ORDER BY id ASC", $today));
        if (!is_array($missed)) { return self::error('mandate_db_write_failed', 'Не може да се проверат пропуштените закажани мандати.'); }
        foreach ($missed as $mandate) {
            if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да се затвори пропуштениот мандат.'); }
            $updated = $wpdb->update(self::table_name(), array('status' => 'expired', 'closed_by' => 0, 'closed_at' => current_time('mysql'), 'close_reason' => 'Scheduled term ended before activation.'), array('id' => (int) $mandate->id, 'status' => 'scheduled'), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
            $ok = 1 === $updated && false !== self::append_event((int) $mandate->id, (int) $mandate->user_id, 'expired', 'Scheduled term ended before activation.', array('effective_date' => $today), 0);
            if (!$ok || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                return self::error('mandate_db_write_failed', 'Не може да се запише истекот на пропуштениот мандат.');
            }
        }

        $scheduled = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table_name() . " WHERE status = 'scheduled' AND start_date <= %s AND end_date >= %s ORDER BY start_date ASC,id ASC", $today, $today));
        if (!is_array($scheduled)) { return self::error('mandate_db_write_failed', 'Не може да се проверат закажаните мандати.'); }
        foreach ($scheduled as $mandate) {
            $user = get_user_by('id', (int) $mandate->user_id);
            if (!($user instanceof WP_User) || !self::eligible_appointee($user)) {
                if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да се затвори невалидниот мандат.'); }
                $updated = $wpdb->update(self::table_name(), array('status' => 'cancelled', 'closed_by' => 0, 'closed_at' => current_time('mysql'), 'close_reason' => 'Appointee account is no longer eligible.'), array('id' => (int) $mandate->id, 'status' => 'scheduled'), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
                $ok = 1 === $updated && false !== self::append_event((int) $mandate->id, (int) $mandate->user_id, 'activation_failed', 'The appointee account is no longer eligible.', array('effective_date' => $today), 0);
                if (!$ok || !self::transaction_finish(true)) { self::transaction_finish(false); return self::error('mandate_db_write_failed', 'Не може да се запише невалидниот мандат.'); }
                continue;
            }
            if (!self::transaction_start()) { return self::error('mandate_db_write_failed', 'Не може безбедно да започне мандатот.'); }
            $updated = $wpdb->update(self::table_name(), array('status' => 'active'), array('id' => (int) $mandate->id, 'status' => 'scheduled'), array('%s'), array('%d', '%s'));
            $role_ok = self::grant_president_role($user, $today);
            $audited = 1 === $updated && $role_ok && false !== self::append_event((int) $mandate->id, (int) $mandate->user_id, 'started', '', array('effective_date' => $today), 0);
            if (!$audited || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                clean_user_cache((int) $user->ID);
                return self::error('mandate_role_assignment_failed', 'Не може безбедно да се активира закажаниот мандат.');
            }
        }
        $archived = 0;
        if (class_exists('ZMP_President_Governance') && is_callable(array('ZMP_President_Governance', 'archive_due_predecessor_documents'))) {
            $archived = ZMP_President_Governance::archive_due_predecessor_documents($today);
            if (is_wp_error($archived)) { return $archived; }
        }
        return count($expired) + count($missed) + count($scheduled) + (int) $archived;
    }
}
