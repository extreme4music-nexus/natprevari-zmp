<?php
if (!defined('ABSPATH')) { exit; }

/** Private, assignment-scoped role rooms. No administrator or President bypass exists. */
class ZMP_Role_Messenger {
    const NONCE = 'zmp_role_messenger';
    const MAX_ATTACHMENT_BYTES = 5 * MB_IN_BYTES;
    const SCHEMA_RETRY_OPTION = 'zmp_role_messenger_schema_retry';
    const SCHEMA_RETRY_STORAGE_FAILED = 'storage_failure';
    const RETENTION_INITIALIZATION_VERSION_OPTION = 'zmp_role_retention_initialization_version';
    private static $schema_pending_unpersisted = false;
    private static $schema_retry_storage_unconfirmed = false;
    private static $schema_compatible_cache = null;
    private static $schema_compatible_prefix = null;
    private static $audit_compatible_cache = null;
    private static $audit_compatible_prefix = null;

    private static function log_scoped_threads_repair_issue($table, $issue, $expected, $actual, $level = 'error') {
        if (!class_exists('ZMP_Debug')) { return; }
        ZMP_Debug::log($level, 'Messenger schema migration failure detail.', array(
            'schema_component' => 'messenger',
            'schema_table' => (string) $table,
            'schema_object' => 'scoped_threads',
            'schema_issue' => (string) $issue,
            'schema_expected' => (string) $expected,
            'schema_actual' => (string) $actual,
        ), 'communications');
    }

    /** Replace the obsolete same-name room index before dbDelta tries to add it. */
    private static function repair_legacy_scoped_threads_index($table) {
        global $wpdb;
        $expected_table = (string) $wpdb->prefix . 'zmp_role_messages';
        $table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
        if ($table === '' || $table !== $expected_table) {
            self::log_scoped_threads_repair_issue($expected_table, 'migration_preflight_failed', 'exact Messenger messages table', 'table_name_rejected');
            return false;
        }
        $escaped_table = str_replace('`', '``', $table);
        $wpdb->last_error = '';
        $exists = $wpdb->get_var($wpdb->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table));
        if ($wpdb->last_error !== '') {
            self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'read existing index metadata', 'table_lookup_failed');
            return false;
        }
        if ((string) $exists !== $table) { return true; }

        $wpdb->last_error = '';
        $rows = $wpdb->get_results("SHOW INDEX FROM `{$escaped_table}`", ARRAY_A);
        if ($wpdb->last_error !== '' || !is_array($rows)) {
            self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'read existing index metadata', 'index_metadata_unavailable');
            return false;
        }
        $columns = array();
        foreach ($rows as $row) {
            if (!isset($row['Key_name'], $row['Column_name'], $row['Seq_in_index'], $row['Non_unique'])) {
                self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'complete index metadata', 'index_metadata_incomplete');
                return false;
            }
            if (strtolower((string) $row['Key_name']) !== 'scoped_threads') { continue; }
            $sequence = (int) $row['Seq_in_index'];
            if ($sequence < 1 || isset($columns[$sequence])) {
                self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'one ordered index definition', 'duplicate_or_invalid_index_sequence');
                return false;
            }
            $columns[$sequence] = array(
                'column' => (string) $row['Column_name'],
                'non_unique' => (int) $row['Non_unique'],
                'sub_part' => isset($row['Sub_part']) ? $row['Sub_part'] : null,
            );
        }
        if (!$columns) { return true; }
        ksort($columns);
        $actual = array_values($columns);
        foreach ($actual as $index_column) {
            if ($index_column['non_unique'] !== 1) {
                self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'nonunique scoped_threads index', 'unexpected_unique_index');
                return false;
            }
        }
        $expected = array('scope_type', 'season_id', 'category_id', 'region_key', 'thread_id', 'id');
        $compatible = count($actual) === count($expected);
        foreach ($expected as $offset => $column) {
            if (!isset($actual[$offset]) || $actual[$offset]['column'] !== $column || $actual[$offset]['non_unique'] !== 1 || $actual[$offset]['sub_part'] !== null) {
                $compatible = false;
                break;
            }
        }
        if ($compatible) { return true; }

        // The legacy index omits region_key; dbDelta otherwise issues ADD KEY with the same name.
        $wpdb->last_error = '';
        $dropped = $wpdb->query("ALTER TABLE `{$escaped_table}` DROP INDEX `scoped_threads`");
        if ($dropped === false) {
            self::log_scoped_threads_repair_issue($table, 'migration_preflight_failed', 'obsolete nonunique index removed before replacement', 'index_drop_failed');
            return false;
        }
        if (class_exists('ZMP_Debug')) {
            ZMP_Debug::log('warning', 'Legacy Messenger room index replaced before schema migration.', array(
                'schema_component' => 'messenger',
                'schema_table' => $table,
                'schema_object' => 'scoped_threads',
                'schema_issue' => 'legacy_index_replaced',
                'schema_expected' => implode(',', $expected),
                'schema_actual' => implode(',', array_column($actual, 'column')),
            ), 'communications');
        }
        self::$schema_compatible_cache = null;
        return true;
    }

    public static function install() {
        global $wpdb, $EZSQL_ERROR;
        if (!self::mark_schema_retry_pending()) { return false; }
        // A schema install/retry must rerun the idempotent retention backfill
        // before ordinary requests can skip initialization.
        delete_option(self::RETENTION_INITIALIZATION_VERSION_OPTION);
        self::$schema_compatible_cache = null;
        self::$audit_compatible_cache = null;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        if (!is_array($EZSQL_ERROR)) { $EZSQL_ERROR = array(); }
        $error_offset = count($EZSQL_ERROR);
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;
        if (!self::repair_legacy_scoped_threads_index($p . 'zmp_role_messages')) {
            self::$schema_compatible_cache = null;
            return false;
        }
        dbDelta("CREATE TABLE {$p}zmp_role_messages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            sender_id BIGINT(20) UNSIGNED NOT NULL,
            subject VARCHAR(190) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY scoped_threads (scope_type,season_id,category_id,region_key,thread_id,id),
            KEY sender_time (sender_id,created_at),
            KEY retention_expiry (scope_type,expires_at,id)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_message_reads (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY message_user (message_id,user_id),
            KEY user_read (user_id,read_at)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_message_notifications (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT(20) UNSIGNED NOT NULL,
            thread_id BIGINT(20) UNSIGNED NOT NULL,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            recipient_user_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'unread',
            created_at DATETIME NOT NULL,
            read_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY message_recipient (message_id,recipient_user_id),
            KEY recipient_scope (recipient_user_id,scope_type,season_id,category_id,region_key,status)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_message_attachments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT(20) UNSIGNED NOT NULL,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            file_key VARCHAR(64) NOT NULL,
            store_hash CHAR(12) NOT NULL DEFAULT '',
            original_name VARCHAR(190) NOT NULL,
            mime_type VARCHAR(80) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY message_file (message_id),
            KEY scoped_file (scope_type,season_id,category_id,region_key)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_admin_message_review_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            requester_user_id BIGINT(20) UNSIGNED NOT NULL,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            thread_id BIGINT(20) UNSIGNED NOT NULL,
            reason VARCHAR(1000) NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            reviewed_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            reviewed_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY status_created (status,created_at),
            KEY scoped_thread (scope_type,season_id,category_id,region_key,thread_id)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_admin_message_review_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            request_id BIGINT(20) UNSIGNED NOT NULL,
            thread_id BIGINT(20) UNSIGNED NOT NULL,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            action VARCHAR(24) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY request_action (request_id,action),
            KEY actor_time (actor_user_id,created_at)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_review_notices (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id BIGINT(20) UNSIGNED NOT NULL,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            thread_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_user_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY audit_recipient (audit_id,recipient_user_id),
            KEY recipient_room (recipient_user_id,scope_type,season_id,category_id,region_key,read_at)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_retention_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            scope_type VARCHAR(20) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            region_key VARCHAR(30) NOT NULL DEFAULT '',
            thread_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            trigger_type VARCHAR(32) NOT NULL,
        deadline_at DATETIME NULL DEFAULT NULL,
            message_count INT UNSIGNED NOT NULL DEFAULT 0,
            attachment_count INT UNSIGNED NOT NULL DEFAULT 0,
            reason_sha256 CHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY scope_time (scope_type,season_id,category_id,region_key,created_at)
        ) $charset ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$p}zmp_role_retention_queue (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT(20) UNSIGNED NOT NULL,
            file_key VARCHAR(64) NOT NULL,
            store_hash CHAR(12) NOT NULL,
            sha256 CHAR(64) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'pending',
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error_code VARCHAR(32) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY file_key (file_key),
            KEY status_id (status,id)
        ) $charset ENGINE=InnoDB;");
        // dbDelta may continue after a failed statement and later queries reset
        // last_error. A missing table's initial DESCRIBE is expected on install;
        // every other DB error is a failed migration even if a later query works.
        if (!is_array($EZSQL_ERROR) || count($EZSQL_ERROR) < $error_offset) { return false; }
        foreach (array_slice($EZSQL_ERROR, $error_offset) as $error) {
            if (!isset($error['query']) || !preg_match('/^\s*DESCRIBE\s/i', (string) $error['query'])) {
                self::$schema_compatible_cache = null;
                return false;
            }
        }
        self::$schema_compatible_cache = null;
        self::$audit_compatible_cache = null;
        return self::schema_is_compatible();
    }

    /** Read schema-control options from the DB rather than object cache. */
    public static function schema_option_read($name, &$read_ok) {
        global $wpdb;
        $read_ok = false;
        if (!in_array($name, array(self::SCHEMA_RETRY_OPTION, 'zmp_db_version', 'zmp_role_messenger_schema_retry_after', self::RETENTION_INITIALIZATION_VERSION_OPTION), true)) { return null; }
        $table = str_replace('`', '``', $wpdb->options);
        $wpdb->last_error = '';
        $value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM `{$table}` WHERE option_name=%s LIMIT 1", $name));
        $read_ok = $wpdb->last_error === '';
        return $value;
    }

    /** Parse the alternate cooldown stored in the schema marker after both retry-option writes fail. */
    private static function retry_marker_fallback($marker) {
        if (!is_string($marker) || strpos($marker, 'retry_storage_failure') !== 0) { return null; }
        $parts = explode('|', $marker, 3);
        if (count($parts) !== 3 || $parts[0] !== 'retry_storage_failure' || !ctype_digit($parts[1])) { return false; }
        $previous_marker = base64_decode($parts[2], true);
        if (!is_string($previous_marker)) { return false; }
        return array('deadline' => (int) $parts[1] + 300, 'previous_marker' => $previous_marker);
    }

    private static function retry_marker_fallback_value($previous_marker) {
        return 'retry_storage_failure|' . time() . '|' . base64_encode((string) $previous_marker);
    }

    public static function schema_marker_is_current() {
        $read_ok = false;
        $marker = self::schema_option_read('zmp_db_version', $read_ok);
        return $read_ok && $marker === ZMP_SCHEMA_VERSION;
    }

    /** A durable intent prevents retention from using a partially migrated schema. */
    public static function mark_schema_retry_pending() {
        $read_ok = false;
        $pending = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $read_ok);
        // Preserve a persisted storage-failure sentinel until a fresh cooldown
        // has been established; overwriting it would allow the next request to
        // reuse an already-expired retry_after value.
        if (!$read_ok || !in_array($pending, array('1', self::SCHEMA_RETRY_STORAGE_FAILED), true)) {
            update_option(self::SCHEMA_RETRY_OPTION, '1', false);
            $pending = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $read_ok);
        }
        self::$schema_pending_unpersisted = !$read_ok || !in_array($pending, array('1', self::SCHEMA_RETRY_STORAGE_FAILED), true);
        self::$schema_retry_storage_unconfirmed = false;
        if (self::$schema_pending_unpersisted) {
            $marker_read_ok = false;
            $previous = self::schema_option_read('zmp_db_version', $marker_read_ok);
            if (!$marker_read_ok) {
                self::$schema_retry_storage_unconfirmed = true;
            } elseif ($previous === ZMP_SCHEMA_VERSION) {
                // A current marker would otherwise skip retry on the next request.
                // Delete only that marker; never claim an older schema version.
                delete_option('zmp_db_version');
                $after_read_ok = false;
                $after = self::schema_option_read('zmp_db_version', $after_read_ok);
                self::$schema_retry_storage_unconfirmed = !$after_read_ok || $after !== null;
            }
        }
        return !self::$schema_pending_unpersisted;
    }

    /**
     * Persist retry intent and establish the first cooldown when a runtime
     * readiness check discovers schema drift. Repeated failing requests must
     * not keep moving an already-active deadline forward.
     */
    public static function mark_schema_failure_pending() {
        if (!self::mark_schema_retry_pending()) { return false; }
        $read_ok = false;
        $retry_after = self::schema_option_read('zmp_role_messenger_schema_retry_after', $read_ok);
        if (!$read_ok) { return false; }
        if (is_scalar($retry_after) && ctype_digit((string) $retry_after)) {
            $deadline = (int) $retry_after;
            $now = time();
            if ($deadline > $now && $deadline <= $now + 300) { return true; }
        }
        return self::defer_schema_retry();
    }

    public static function schema_retry_storage_unconfirmed() {
        return self::$schema_retry_storage_unconfirmed;
    }

    public static function schema_retry_pending() {
        $read_ok = false;
        $pending = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $read_ok);
        $marker_read_ok = false;
        $marker = self::schema_option_read('zmp_db_version', $marker_read_ok);
        $marker_fallback = self::retry_marker_fallback($marker);
        return self::$schema_pending_unpersisted || !$read_ok || $pending !== null || !$marker_read_ok || $marker_fallback !== null;
    }

    /** Throttle automatic schema retries; activation remains an explicit immediate retry. */
    public static function schema_retry_allowed() {
        $read_ok = false;
        $retry_after = self::schema_option_read('zmp_role_messenger_schema_retry_after', $read_ok);
        if (!$read_ok) { return false; }
        $pending_read_ok = false;
        $pending = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $pending_read_ok);
        if (!$pending_read_ok) { return false; }
        $marker_read_ok = false;
        $marker = self::schema_option_read('zmp_db_version', $marker_read_ok);
        if (!$marker_read_ok) { return false; }
        $marker_fallback = self::retry_marker_fallback($marker);
        if (false === $marker_fallback) { return false; }
        if (is_array($marker_fallback)) {
            $now = time();
            $deadline = (int) $marker_fallback['deadline'];
            // As with ordinary retry_after values, recover from a clock jump or corrupt far-future fallback.
            if ($deadline > $now + 300) { $deadline = $now; }
            if (is_scalar($retry_after) && ctype_digit((string) $retry_after)) {
                $retry_deadline = (int) $retry_after;
                if ($retry_deadline > $now && $retry_deadline <= $now + 300) { $deadline = max($deadline, $retry_deadline); }
            }
            if ($deadline > $now) { return false; }
        }
        if ($pending === self::SCHEMA_RETRY_STORAGE_FAILED) {
            // The prior request could not persist its cooldown. On recovery,
            // establish a fresh deadline and defer migration for this request.
            if (!is_scalar($retry_after) || !ctype_digit((string) $retry_after) || (int) $retry_after <= time()) {
                if (!self::defer_schema_retry()) { return false; }
            }
            update_option(self::SCHEMA_RETRY_OPTION, '1', false);
            $normalized = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $pending_read_ok);
            self::$schema_pending_unpersisted = !$pending_read_ok || $normalized !== '1';
            return false;
        }
        if ($retry_after === null || !ctype_digit((string) $retry_after)) { return true; }
        $now = time();
        $deadline = (int) $retry_after;
        // Corruption or a backward clock change must not postpone recovery indefinitely.
        return $deadline <= $now || $deadline > $now + 300;
    }

    public static function defer_schema_retry() {
        $retry_after = (string) (time() + 300);
        $fallback_saved = false;
        update_option('zmp_role_messenger_schema_retry_after', $retry_after, false);
        $read_ok = false;
        $saved = self::schema_option_read('zmp_role_messenger_schema_retry_after', $read_ok) === $retry_after && $read_ok;
        if (!$saved) {
            // Keep a durable signal in the pending option so a later request
            // cannot mistake an old expired timestamp for a new retry window.
            update_option(self::SCHEMA_RETRY_OPTION, self::SCHEMA_RETRY_STORAGE_FAILED, false);
            $pending_read_ok = false;
            $pending = self::schema_option_read(self::SCHEMA_RETRY_OPTION, $pending_read_ok);
            self::$schema_pending_unpersisted = !$pending_read_ok || $pending !== self::SCHEMA_RETRY_STORAGE_FAILED;
            self::$schema_retry_storage_unconfirmed = self::$schema_pending_unpersisted;
            if (self::$schema_pending_unpersisted) {
                $marker_read_ok = false;
                $previous_marker = self::schema_option_read('zmp_db_version', $marker_read_ok);
                if ($marker_read_ok) {
                    $fallback = self::retry_marker_fallback_value($previous_marker);
                    update_option('zmp_db_version', $fallback, false);
                    $fallback_read_ok = false;
                    $saved_fallback = self::schema_option_read('zmp_db_version', $fallback_read_ok);
                    $fallback_saved = $fallback_read_ok && $saved_fallback === $fallback;
                    self::$schema_pending_unpersisted = !$fallback_saved;
                    self::$schema_retry_storage_unconfirmed = self::$schema_pending_unpersisted;
                }
            }
        }
        return $saved || $fallback_saved;
    }

    public static function schema_migration_lock_name() {
        global $wpdb;
        return 'zmp_schema_' . substr(hash('sha256', DB_NAME . ':' . $wpdb->prefix), 0, 32);
    }

    public static function acquire_schema_migration_lock() {
        global $wpdb;
        $value = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)', self::schema_migration_lock_name()));
        return $wpdb->last_error === '' && (string) $value === '1';
    }

    public static function release_schema_migration_lock() {
        global $wpdb;
        $value = $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', self::schema_migration_lock_name()));
        return $wpdb->last_error === '' && (string) $value === '1';
    }

    /** Verify the installed Messenger schema, including the unique-key semantics. */
    public static function schema_is_compatible($refresh = false) {
        global $wpdb;
        if (!$refresh && self::$schema_compatible_cache !== null && self::$schema_compatible_prefix === $wpdb->prefix) { return self::$schema_compatible_cache; }
        self::$schema_compatible_prefix = $wpdb->prefix;
        $contracts = array(
            'zmp_role_messages' => array('id,thread_id,scope_type,season_id,category_id,region_key,sender_id,subject,message,created_at,expires_at', array('primary'=>array(1,'id'),'scoped_threads'=>array(0,'scope_type,season_id,category_id,region_key,thread_id,id'),'sender_time'=>array(0,'sender_id,created_at'),'retention_expiry'=>array(0,'scope_type,expires_at,id'))),
            'zmp_role_message_reads' => array('id,message_id,user_id,read_at', array('primary'=>array(1,'id'),'message_user'=>array(1,'message_id,user_id'),'user_read'=>array(0,'user_id,read_at'))),
            'zmp_role_message_notifications' => array('id,message_id,thread_id,scope_type,season_id,category_id,region_key,recipient_user_id,status,created_at,read_at', array('primary'=>array(1,'id'),'message_recipient'=>array(1,'message_id,recipient_user_id'),'recipient_scope'=>array(0,'recipient_user_id,scope_type,season_id,category_id,region_key,status'))),
            'zmp_role_message_attachments' => array('id,message_id,scope_type,season_id,category_id,region_key,file_key,store_hash,original_name,mime_type,file_size,sha256,created_at', array('primary'=>array(1,'id'),'message_file'=>array(1,'message_id'),'scoped_file'=>array(0,'scope_type,season_id,category_id,region_key'))),
            'zmp_admin_message_review_requests' => array('id,requester_user_id,scope_type,season_id,category_id,region_key,thread_id,reason,status,created_at,reviewed_by,reviewed_at', array('primary'=>array(1,'id'),'status_created'=>array(0,'status,created_at'),'scoped_thread'=>array(0,'scope_type,season_id,category_id,region_key,thread_id'))),
            'zmp_admin_message_review_audit' => array('id,actor_user_id,request_id,thread_id,scope_type,season_id,category_id,region_key,action,created_at', array('primary'=>array(1,'id'),'request_action'=>array(0,'request_id,action'),'actor_time'=>array(0,'actor_user_id,created_at'))),
            'zmp_role_review_notices' => array('id,audit_id,scope_type,season_id,category_id,region_key,thread_id,recipient_user_id,created_at,read_at', array('primary'=>array(1,'id'),'audit_recipient'=>array(1,'audit_id,recipient_user_id'),'recipient_room'=>array(0,'recipient_user_id,scope_type,season_id,category_id,region_key,read_at'))),
            'zmp_role_retention_audit' => array('id,actor_user_id,scope_type,season_id,category_id,region_key,thread_id,trigger_type,deadline_at,message_count,attachment_count,reason_sha256,created_at', array('primary'=>array(1,'id'),'scope_time'=>array(0,'scope_type,season_id,category_id,region_key,created_at'))),
            'zmp_role_retention_queue' => array('id,message_id,file_key,store_hash,sha256,file_size,status,attempts,last_error_code,created_at', array('primary'=>array(1,'id'),'file_key'=>array(1,'file_key'),'status_id'=>array(0,'status,id'))),
        );
        $names = array();
        foreach (array_keys($contracts) as $suffix) { $names[] = $wpdb->prefix . $suffix; }
        $placeholders = implode(',', array_fill(0, count($names), '%s'));
        // One metadata round trip covers tables, columns and indexes. Every
        // retention operation requests a fresh snapshot while holding the lock.
        $query = "SELECT 'table' AS kind, TABLE_NAME AS table_name, ENGINE AS item_name, TABLE_TYPE AS info, NULL AS seq, NULL AS non_unique, NULL AS sub_part, NULL AS is_nullable, NULL AS extra FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})"
            . " UNION ALL SELECT 'column', TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, NULL, NULL, NULL, IS_NULLABLE, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})"
            . " UNION ALL SELECT 'index', TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE, SUB_PART, NULL, NULL FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})";
        $wpdb->last_error = '';
        $rows = $wpdb->get_results($wpdb->prepare($query, array_merge($names, $names, $names)), ARRAY_A);
        if ($wpdb->last_error !== '' || !is_array($rows)) { return self::$schema_compatible_cache = false; }
        $tables = array();
        $columns = array();
        $indexes = array();
        foreach ($rows as $row) {
            if (!isset($row['kind'], $row['table_name'], $row['item_name'])) { return self::$schema_compatible_cache = false; }
            $table = (string) $row['table_name'];
            if ($row['kind'] === 'table') { $tables[$table] = array('engine'=>$row['item_name'],'table_type'=>$row['info']); continue; }
            if ($row['kind'] === 'column') { $columns[$table][(string) $row['item_name']] = array('column_type'=>$row['info'],'is_nullable'=>$row['is_nullable'],'extra'=>$row['extra']); continue; }
            if ($row['kind'] !== 'index' || !isset($row['seq'], $row['non_unique'])) { return self::$schema_compatible_cache = false; }
            $name = strtolower((string) $row['item_name']); $seq = (int) $row['seq'];
            if ($seq < 1 || isset($indexes[$table][$name]['columns'][$seq]) || !isset($row['info']) || $row['sub_part'] !== null) { $indexes[$table][$name]['invalid'] = true; continue; }
            $unique = (int) $row['non_unique'] === 0;
            if (isset($indexes[$table][$name]['unique']) && $indexes[$table][$name]['unique'] !== $unique) { return self::$schema_compatible_cache = false; }
            $indexes[$table][$name]['unique'] = $unique;
            $indexes[$table][$name]['columns'][$seq] = (string) $row['info'];
        }
        foreach ($contracts as $suffix => $contract) {
            $table = $wpdb->prefix . $suffix;
            if (!isset($tables[$table]) || strcasecmp((string) $tables[$table]['engine'], 'InnoDB') !== 0 || $tables[$table]['table_type'] !== 'BASE TABLE') { return self::$schema_compatible_cache = false; }
            foreach (explode(',', $contract[0]) as $column) {
                if (!isset($columns[$table][$column]) || !self::schema_column_is_compatible($suffix, $column, $columns[$table][$column])) { return self::$schema_compatible_cache = false; }
            }
            foreach ($contract[1] as $name => $expected) {
                if (!isset($indexes[$table][$name]['unique']) || !empty($indexes[$table][$name]['invalid']) || $indexes[$table][$name]['unique'] !== (bool) $expected[0]) { return self::$schema_compatible_cache = false; }
                ksort($indexes[$table][$name]['columns']);
                if (array_keys($indexes[$table][$name]['columns']) !== range(1, count($indexes[$table][$name]['columns'])) || array_values($indexes[$table][$name]['columns']) !== explode(',', $expected[1])) { return self::$schema_compatible_cache = false; }
            }
        }
        // The audit column's width was checked against the same fresh metadata.
        self::$audit_compatible_prefix = $wpdb->prefix;
        self::$audit_compatible_cache = true;
        return self::$schema_compatible_cache = self::retention_audit_schema_is_compatible();
    }

    /** Require the storage capacity and nullability used by Messenger writes. */
    private static function schema_column_is_compatible($table, $column, $row) {
        if (!isset($row['column_type'], $row['is_nullable'], $row['extra'])) { return false; }
        $type = strtolower(trim((string) $row['column_type']));
        $nullable = in_array($column, array('expires_at', 'reviewed_at', 'deadline_at'), true) || ($column === 'read_at' && $table !== 'zmp_role_message_reads');
        if (($row['is_nullable'] === 'YES') !== $nullable) { return false; }
        if (preg_match('/\b(?:virtual|stored) generated\b/i', (string) $row['extra'])) { return false; }
        if ($column === 'id' && stripos((string) $row['extra'], 'auto_increment') === false) { return false; }
        $bigints = array('id','thread_id','season_id','category_id','sender_id','message_id','user_id','recipient_user_id','file_size','requester_user_id','reviewed_by','actor_user_id','request_id','audit_id');
        if (in_array($column, $bigints, true)) { return preg_match('/^bigint(?:\(\d+\))? unsigned$/', $type) === 1; }
        if (in_array($column, array('message_count','attachment_count','attempts'), true)) { return preg_match('/^(?:int|integer)(?:\(\d+\))? unsigned$/', $type) === 1; }
        if (in_array($column, array('created_at','expires_at','read_at','reviewed_at','deadline_at'), true)) { return in_array($type, array('datetime','datetime(0)'), true); }
        if ($column === 'message') { return in_array($type, array('text','mediumtext','longtext'), true); }
        $varchar_widths = array('scope_type'=>20,'region_key'=>30,'subject'=>190,'file_key'=>64,'original_name'=>190,'mime_type'=>80,'reason'=>1000,'action'=>24,'trigger_type'=>32,'last_error_code'=>32);
        if ($column === 'status') { $varchar_widths['status'] = $table === 'zmp_admin_message_review_requests' ? 16 : 12; }
        if (isset($varchar_widths[$column])) { return preg_match('/^varchar\((\d+)\)$/', $type, $match) === 1 && (int) $match[1] >= $varchar_widths[$column]; }
        $char_widths = array('store_hash'=>12,'sha256'=>64,'reason_sha256'=>64);
        if (isset($char_widths[$column])) { return preg_match('/^char\((\d+)\)$/', $type, $match) === 1 && (int) $match[1] === $char_widths[$column]; }
        return false;
    }

    /** Confirm the retention audit column can store every current trigger value. */
    public static function retention_audit_schema_is_compatible($refresh = false) {
        global $wpdb;
        if (!$refresh && self::$audit_compatible_cache !== null && self::$audit_compatible_prefix === $wpdb->prefix) { return self::$audit_compatible_cache; }
        self::$audit_compatible_prefix = $wpdb->prefix;
        $table = str_replace('`', '``', $wpdb->prefix . 'zmp_role_retention_audit');
        $column = $wpdb->get_row($wpdb->prepare("SHOW COLUMNS FROM `{$table}` WHERE Field = %s", 'trigger_type'));
        if ($wpdb->last_error !== '' || !$column || !isset($column->Field, $column->Type) || $column->Field !== 'trigger_type') {
            return self::$audit_compatible_cache = false;
        }
        return self::$audit_compatible_cache = preg_match('/^varchar\((\d+)\)$/i', trim($column->Type), $matches) === 1 && (int) $matches[1] >= 32;
    }

    public static function init() {
        add_action('admin_post_zmp_role_message', array(__CLASS__, 'handle_post'));
        add_action('admin_post_zmp_role_message_attachment', array(__CLASS__, 'handle_download'));
        add_action('admin_post_zmp_role_message_export', array(__CLASS__, 'handle_export'));
        add_action('admin_post_zmp_admin_message_review_request', array(__CLASS__, 'handle_request_admin_message_access'));
        add_action('admin_post_zmp_admin_message_review_read', array(__CLASS__, 'handle_admin_message_review_read'));
        add_action('admin_post_zmp_role_review_notice_ack', array(__CLASS__, 'handle_review_notice_ack'));
        add_action('admin_post_zmp_role_retention_deadline', array(__CLASS__, 'handle_retention_deadline'));
        add_action('admin_post_zmp_role_retention_retry', array(__CLASS__, 'handle_retention_retry'));
        add_action('zmp_role_message_retention', array(__CLASS__, 'run_retention'));
        add_action('zmp_role_message_retention_catchup', array(__CLASS__, 'run_retention'));
        add_action('init', array(__CLASS__, 'initialize_retention'), 20);
        add_action('admin_notices', array(__CLASS__, 'render_retention_health_notice'));
        add_action('admin_menu', array(__CLASS__, 'register_message_review_menu'));
    }

    private static function add_months($date, $months) {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) { return false; }
        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, wp_timezone());
        if (!$time || $time->format('Y-m-d H:i:s') !== $date) { return false; }
        return $time->modify('+' . absint($months) . ' months')->format('Y-m-d H:i:s');
    }

    private static function subtract_months($date, $months) {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) { return false; }
        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, wp_timezone());
        if (!$time || $time->format('Y-m-d H:i:s') !== $date) { return false; }
        // PHP's month subtraction can overflow a leap day into March, making
        // the cutoff later than the true anniversary and deleting early.
        $day = (int) $time->format('j');
        $target = $time->setDate((int) $time->format('Y'), (int) $time->format('n'), 1)
            ->modify('-' . absint($months) . ' months');
        return $target->setDate((int) $target->format('Y'), (int) $target->format('n'), min($day, (int) $target->format('t')))->format('Y-m-d H:i:s');
    }

    /** The caller holds the shared schema/retention lock through all subsequent writes. */
    private static function retention_schema_ready_locked() {
        if (!self::schema_marker_is_current()) {
            if (!self::schema_retry_pending()) { self::mark_schema_retry_pending(); }
            return false;
        }
        if (self::schema_retry_pending()) { return false; }
        if (!self::schema_is_compatible(true)) {
            // Persist the five-minute retry deadline when the full schema check
            // discovers a mismatch outside the upgrade dispatcher.
            self::mark_schema_failure_pending();
            return false;
        }
        return true;
    }

    /** Capture the original unscoped Moderator cohort exactly once after the .53 tables exist. */
    public static function initialize_retention() {
        if (self::retention_initialization_is_complete()) { return true; }
        // The main initializer owns upgrade/retry dispatch and its cooldown.
        // Never force a retention schema scan here while that path is pending.
        if (!self::schema_marker_is_current() || self::schema_retry_pending()) { return false; }
        if (!self::acquire_schema_migration_lock()) { return false; }
        $ready = false;
        $released = false;
        try { $ready = self::initialize_retention_locked(); }
        finally {
            $released = self::release_schema_migration_lock();
            if (!$released) { self::mark_schema_retry_pending(); }
        }
        return $ready && $released;
    }

    /** Healthy requests skip repeated metadata scans and idempotent backfills. */
    private static function retention_initialization_is_complete() {
        if (!self::schema_marker_is_current() || self::schema_retry_pending()) { return false; }
        $initialized_read_ok = false;
        if (self::schema_option_read(self::RETENTION_INITIALIZATION_VERSION_OPTION, $initialized_read_ok) !== ZMP_SCHEMA_VERSION || !$initialized_read_ok) { return false; }
        $policy = get_option('zmp_role_retention_policy', false);
        if (!is_array($policy) || empty($policy['activated_at']) || !isset($policy['legacy_max_id'])
            || !self::add_months($policy['activated_at'], 24)) { return false; }
        return (bool) wp_next_scheduled('zmp_role_message_retention');
    }

    private static function initialize_retention_locked() {
        global $wpdb;
        if (!self::retention_schema_ready_locked()) { return false; }
        $table = $wpdb->prefix . 'zmp_role_messages';
        $policy = get_option('zmp_role_retention_policy', false);
        if (!$policy) {
            $max_id = (int) $wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$table} WHERE scope_type='moderator'");
            if ($wpdb->last_error !== '') { return false; }
            $candidate = array('activated_at' => current_time('mysql'), 'legacy_max_id' => $max_id);
            add_option('zmp_role_retention_policy', $candidate, '', false);
            $policy = get_option('zmp_role_retention_policy', false);
        }
        if (!is_array($policy) || empty($policy['activated_at']) || !isset($policy['legacy_max_id'])) { return false; }
        $legacy_expiry = self::add_months($policy['activated_at'], 24);
        if (!$legacy_expiry) { return false; }
        // Idempotent repair after interrupted upgrades; newer messages retain their own 24-month clock.
        $backfilled = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET expires_at=CASE WHEN id<=%d THEN %s ELSE DATE_ADD(created_at, INTERVAL 24 MONTH) END WHERE scope_type='moderator' AND expires_at IS NULL",
            absint($policy['legacy_max_id']), $legacy_expiry
        ));
        if ($backfilled === false) { update_option('zmp_role_retention_last_status', 'expiry_backfill_failed', false); return false; }
        $bucket = substr(hash('sha256', home_url('/')), 0, 12);
        $attachment_table = $wpdb->prefix . 'zmp_role_message_attachments';
        if ($wpdb->query($wpdb->prepare("UPDATE {$attachment_table} SET store_hash=%s WHERE store_hash=''", $bucket)) === false) {
            update_option('zmp_role_retention_last_status', 'storage_backfill_failed', false);
            return false;
        }
        if (!wp_next_scheduled('zmp_role_message_retention')) {
            if (!wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zmp_role_message_retention')) {
                update_option('zmp_role_retention_last_status', 'cron_schedule_failed', false);
                return false;
            }
        }
        update_option(self::RETENTION_INITIALIZATION_VERSION_OPTION, ZMP_SCHEMA_VERSION, false);
        $initialized_read_ok = false;
        $initialized_version = self::schema_option_read(self::RETENTION_INITIALIZATION_VERSION_OPTION, $initialized_read_ok);
        return $initialized_read_ok && $initialized_version === ZMP_SCHEMA_VERSION;
    }

    /** A Moderator may only shorten the deadline of an exact Moderator thread. */
    public static function handle_retention_deadline() {
        if (!self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap')) { wp_die('Немате право да управувате со рокот.', '', array('response'=>403)); }
        $thread_id = absint($_POST['thread_id'] ?? 0);
        check_admin_referer('zmp_role_retention_deadline_' . $thread_id);
        $raw_reason = wp_unslash($_POST['reason'] ?? '');
        $reason = is_scalar($raw_reason) ? sanitize_textarea_field((string) $raw_reason) : '';
        $reason_length = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if (!$thread_id) { wp_die('Разговорот не е достапен.', '', array('response'=>400)); }
        if ($reason_length < 20 || $reason_length > 1000) { wp_safe_redirect(add_query_arg('zmp_retention_error', 'reason', self::safe_return_url())); exit; }
        $now = current_time('mysql');
        $delete_now = isset($_POST['delete_now']) && (string) wp_unslash($_POST['delete_now']) === '1';
        $raw_deadline = isset($_POST['deadline']) && is_scalar($_POST['deadline']) ? wp_unslash($_POST['deadline']) : '';
        $chosen = $delete_now ? $now : '';
        $chosen_datetime = null;
        if (!$delete_now && is_string($raw_deadline)) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw_deadline, wp_timezone());
            if ($parsed && $parsed->format('Y-m-d\TH:i') === $raw_deadline) { $chosen = $parsed->format('Y-m-d H:i:s'); $chosen_datetime = $parsed; }
        }
        if (!$chosen) { wp_safe_redirect(add_query_arg('zmp_retention_error', 'deadline', self::safe_return_url())); exit; }
        if (!$delete_now && $chosen_datetime <= new DateTimeImmutable('now', wp_timezone())) { wp_safe_redirect(add_query_arg('zmp_retention_error', 'deadline_past', self::safe_return_url())); exit; }
        if (!self::acquire_schema_migration_lock()) { wp_die('Чистењето на пораки моментално не е достапно.', '', array('response'=>503)); }
        $result = array();
        $released = false;
        try { $result = self::set_retention_deadline_locked($thread_id, $chosen, $delete_now, $reason, $now); }
        catch (Throwable $error) {
            global $wpdb;
            try { $wpdb->query('ROLLBACK'); } catch (Throwable $ignored) { /* Preserve the fixed failure response. */ }
            $result = array('error'=>'Рокот моментално не може безбедно да се промени.','response'=>503);
        }
        finally {
            $released = self::release_schema_migration_lock();
            if (!$released) { self::mark_schema_retry_pending(); }
        }
        if (!$released) { wp_die('Рокот можеби е променет. Проверете го разговорот пред повторен обид.', '', array('response'=>409)); }
        if (isset($result['redirect_error'])) { wp_safe_redirect(add_query_arg('zmp_retention_error', $result['redirect_error'], self::safe_return_url())); exit; }
        if (isset($result['error'])) { wp_die($result['error'], '', array('response'=>$result['response'])); }
        if (empty($result['success'])) { wp_die('Рокот моментално не може безбедно да се промени.', '', array('response'=>503)); }
        if ($chosen <= $now) { self::run_retention(); }
        wp_safe_redirect(add_query_arg('zmp_retention_updated', '1', self::safe_return_url()));
        exit;
    }

    private static function set_retention_deadline_locked($thread_id, $chosen, $delete_now, $reason, $now) {
        global $wpdb;
        if (!self::retention_schema_ready_locked()) { return array('error'=>'Чистењето на пораки моментално не е достапно.','response'=>503); }
        $messages = $wpdb->prefix . 'zmp_role_messages';
        $audit = $wpdb->prefix . 'zmp_role_retention_audit';
        if (!self::transaction_tables_available() || $wpdb->query('START TRANSACTION') === false) { return array('error'=>'Рокот моментално не може безбедно да се промени.','response'=>503); }
        $locked_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$messages} WHERE scope_type='moderator' AND season_id=0 AND category_id=0 AND region_key='' AND thread_id=%d ORDER BY id FOR UPDATE", $thread_id));
        if ($wpdb->last_error !== '' || !is_array($locked_ids) || !$locked_ids) { $wpdb->query('ROLLBACK'); return array('error'=>'Разговорот не е достапен.','response'=>404); }
        $changed = $wpdb->query($wpdb->prepare("UPDATE {$messages} SET expires_at=%s WHERE scope_type='moderator' AND season_id=0 AND category_id=0 AND region_key='' AND thread_id=%d AND (expires_at IS NULL OR expires_at>%s)", $chosen, $thread_id, $chosen));
        if ($changed === 0 && !$delete_now) { $wpdb->query('ROLLBACK'); return array('redirect_error'=>'deadline_not_shorter'); }
        if ($changed === false) {
            $wpdb->query('ROLLBACK');
            return array('error'=>'Рокот не беше променет. Код: deadline_update_failed.','response'=>409);
        }
        $audit_result = $wpdb->insert($audit, array('actor_user_id'=>get_current_user_id(),'scope_type'=>'moderator','season_id'=>0,'category_id'=>0,'region_key'=>'','thread_id'=>$thread_id,'trigger_type'=>$delete_now ? 'early_delete_requested' : 'deadline_set','deadline_at'=>$chosen,'message_count'=>$changed,'attachment_count'=>0,'reason_sha256'=>hash('sha256',$reason),'created_at'=>$now), array('%d','%s','%d','%d','%s','%d','%s','%s','%d','%d','%s','%s'));
        if (!$audit_result) {
            $wpdb->query('ROLLBACK');
            return array('error'=>'Рокот не беше променет. Код: deadline_audit_insert_failed.','response'=>409);
        }
        $commit_result = $wpdb->query('COMMIT');
        if ($commit_result === false) {
            $wpdb->query('ROLLBACK');
            return array('error'=>'Не можевме да го потврдиме исходот на операцијата. Код: deadline_commit_failed. Проверете го разговорот пред повторен обид.','response'=>409);
        }
        return array('success'=>true);
    }

    public static function render_retention_health_notice() {
        if (!current_user_can('manage_options') || self::is_president()) { return; }
        if (self::schema_retry_pending() || !self::schema_marker_is_current()) {
            echo '<div class="notice notice-error"><p>ЗМП: шемата за приватни пораки бара поправка; автоматското чистење е запрено. Побарајте администратор на базата да ги провери табелите, типовите, NULL/NOT NULL и индексите на Messenger. По поправката автоматскиот обид се повторува по истекот на рокот од пет минути, при следното барање; повторна активација веднаш го започнува обидот.</p></div>';
            return;
        }
        global $wpdb;
        $blocked = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_role_retention_queue WHERE status='blocked'");
        $status = (string) get_option('zmp_role_retention_last_status', '');
        if (!$blocked && $status === '') { return; }
        echo '<div class="notice notice-warning"><p>ЗМП: чистењето на приватните пораки бара проверка. Блокирани прилози: ' . (int) $blocked . '. Статус: ' . esc_html($status ?: 'проверете го приватното складиште') . '.</p>';
        if ($blocked) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="zmp_role_retention_retry">';
            wp_nonce_field('zmp_role_retention_retry');
            echo '<button type="submit" class="button">Повторно провери ги блокираните прилози</button></form>';
        }
        echo '</div>';
    }

    public static function handle_retention_retry() {
        if (!current_user_can('manage_options') || self::is_president()) { wp_die('Немате право на оваа проверка.', '', array('response'=>403)); }
        check_admin_referer('zmp_role_retention_retry');
        if (!self::acquire_schema_migration_lock()) { wp_die('Чистењето на пораки моментално не е достапно.', '', array('response'=>503)); }
        global $wpdb;
        $updated = false;
        $released = false;
        try {
            if (self::retention_schema_ready_locked()) {
                $updated = $wpdb->query("UPDATE {$wpdb->prefix}zmp_role_retention_queue SET status='pending',last_error_code='' WHERE status='blocked' LIMIT 100") !== false;
            }
        } finally {
            $released = self::release_schema_migration_lock();
            if (!$released) { self::mark_schema_retry_pending(); }
        }
        if (!$released || !$updated) { wp_die('Чистењето на пораки моментално не е достапно.', '', array('response'=>503)); }
        self::run_retention();
        wp_safe_redirect(admin_url('admin.php?page=zmp-competitions'));
        exit;
    }

    /** WordPress Cron is best-effort; each invocation is bounded and resumable. */
    public static function run_retention() {
        global $wpdb;
        if (!self::acquire_schema_migration_lock()) { return; }
        try {
            if (!self::initialize_retention_locked() || !self::transaction_tables_available()) { return; }
            $now = current_time('mysql');
            $cutoff = self::subtract_months($now, 12);
            if (!$cutoff) { return; }
            $last_count = 0;
            $purge_failed = false;
            for ($batch = 0; $batch < 10; $batch++) {
                $last_count = self::purge_due_batch($now, $cutoff);
                if ($last_count < 0) { $purge_failed = true; update_option('zmp_role_retention_last_status', 'purge_failed', false); break; }
                if ($last_count < 100) { break; }
            }
            $queue_pending = self::process_retention_queue();
            if (!$purge_failed && $queue_pending >= 0 && ($last_count === 100 || $queue_pending > 0) && !wp_next_scheduled('zmp_role_message_retention_catchup')) {
                wp_schedule_single_event(time() + 5 * MINUTE_IN_SECONDS, 'zmp_role_message_retention_catchup');
            }
            if (!$purge_failed && $queue_pending === 0 && !(int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_role_retention_queue WHERE status='blocked'")) {
                delete_option('zmp_role_retention_last_status');
            }
        } finally {
            if (!self::release_schema_migration_lock()) { self::mark_schema_retry_pending(); }
        }
    }

    /** Delete only expired message rows; retain a retry record before unlinking private files. */
    private static function purge_due_batch($now, $season_cutoff) {
        global $wpdb;
        $p = $wpdb->prefix;
        if ($wpdb->query('START TRANSACTION') === false) { return -1; }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id,m.thread_id,m.scope_type,m.season_id,m.category_id,m.region_key
             FROM {$p}zmp_role_messages m
             LEFT JOIN {$p}zmp_seasons s ON s.id=m.season_id
             LEFT JOIN {$p}zmp_competitions c ON c.id=s.legacy_competition_id
             WHERE (m.scope_type='moderator' AND m.season_id=0 AND m.expires_at IS NOT NULL AND m.expires_at<=%s)
                OR (m.scope_type IN ('tester','organizer','commission') AND m.season_id>0
                    AND c.workflow_status='completed' AND c.finalized_at IS NOT NULL AND c.finalized_at<=%s)
             ORDER BY m.id LIMIT 100 FOR UPDATE",
            $now, $season_cutoff
        ));
        if ($wpdb->last_error !== '' || !is_array($rows)) { $wpdb->query('ROLLBACK'); return -1; }
        if (!$rows) { $wpdb->query('COMMIT'); return 0; }
        $ids = array_map(static function($row) { return (int) $row->id; }, $rows);
        $in = implode(',', array_fill(0, count($ids), '%d'));
        $files = $wpdb->get_results($wpdb->prepare("SELECT message_id,file_key,store_hash,sha256,file_size FROM {$p}zmp_role_message_attachments WHERE message_id IN ({$in}) FOR UPDATE", $ids));
        if ($wpdb->last_error !== '' || !is_array($files)) { $wpdb->query('ROLLBACK'); return -1; }
        $groups = array();
        $threads = array();
        foreach ($rows as $row) {
            $key = implode('|', array($row->scope_type, $row->season_id, $row->category_id, $row->region_key));
            if (!isset($groups[$key])) { $groups[$key] = array('row'=>$row,'messages'=>0,'attachments'=>0); }
            $groups[$key]['messages']++;
            $thread_key = $key . '|' . $row->thread_id;
            $threads[$thread_key] = $row;
        }
        foreach ($files as $file) {
            $owner = null;
            foreach ($rows as $row) { if ((int) $row->id === (int) $file->message_id) { $owner = $row; break; } }
            if (!$owner || !preg_match('/^[a-f0-9]{48}\.(pdf|jpg|jpeg|png)$/', (string) $file->file_key) || !preg_match('/^[a-f0-9]{12}$/', (string) $file->store_hash) || !preg_match('/^[a-f0-9]{64}$/', (string) $file->sha256)) { $wpdb->query('ROLLBACK'); return -1; }
            $key = implode('|', array($owner->scope_type, $owner->season_id, $owner->category_id, $owner->region_key));
            $groups[$key]['attachments']++;
            if (!$wpdb->insert($p . 'zmp_role_retention_queue', array('message_id'=>(int)$file->message_id,'file_key'=>(string)$file->file_key,'store_hash'=>(string)$file->store_hash,'sha256'=>(string)$file->sha256,'file_size'=>(int)$file->file_size,'status'=>'pending','created_at'=>$now), array('%d','%s','%s','%s','%d','%s','%s'))) { $wpdb->query('ROLLBACK'); return -1; }
        }
        foreach (array('zmp_role_message_reads','zmp_role_message_notifications','zmp_role_message_attachments') as $suffix) {
            if ($wpdb->query($wpdb->prepare("DELETE FROM {$p}{$suffix} WHERE message_id IN ({$in})", $ids)) === false) { $wpdb->query('ROLLBACK'); return -1; }
        }
        if ($wpdb->query($wpdb->prepare("DELETE FROM {$p}zmp_role_messages WHERE id IN ({$in})", $ids)) !== count($ids)) { $wpdb->query('ROLLBACK'); return -1; }
        // A review request may contain a free-text reason; remove it when its thread is empty.
        foreach ($threads as $thread) {
            $tuple = array((int)$thread->thread_id,(string)$thread->scope_type,(int)$thread->season_id,(int)$thread->category_id,(string)$thread->region_key);
            $left = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}zmp_role_messages WHERE thread_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s LIMIT 1", $tuple));
            if ($wpdb->last_error !== '') { $wpdb->query('ROLLBACK'); return -1; }
            if ($left) { continue; }
            foreach (array('zmp_admin_message_review_requests','zmp_role_review_notices') as $suffix) {
                if ($wpdb->query($wpdb->prepare("DELETE FROM {$p}{$suffix} WHERE thread_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s", $tuple)) === false) { $wpdb->query('ROLLBACK'); return -1; }
            }
        }
        foreach ($groups as $group) {
            $row = $group['row'];
            if (!$wpdb->insert($p . 'zmp_role_retention_audit', array('actor_user_id'=>0,'scope_type'=>$row->scope_type,'season_id'=>(int)$row->season_id,'category_id'=>(int)$row->category_id,'region_key'=>(string)$row->region_key,'thread_id'=>0,'trigger_type'=>'expired','message_count'=>$group['messages'],'attachment_count'=>$group['attachments'],'reason_sha256'=>'','created_at'=>$now), array('%d','%s','%d','%d','%s','%d','%s','%d','%d','%s','%s'))) { $wpdb->query('ROLLBACK'); return -1; }
        }
        if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); return -1; }
        return count($ids);
    }

    /** File removal is separate from the DB transaction and can safely resume. */
    private static function process_retention_queue() {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_role_retention_queue';
        $files = $wpdb->get_results("SELECT id,file_key,store_hash,sha256,file_size,attempts,status FROM {$table} WHERE status IN ('pending','deleting') ORDER BY id LIMIT 100");
        if ($wpdb->last_error !== '' || !is_array($files)) { update_option('zmp_role_retention_last_status', 'queue_read_failed', false); return -1; }
        foreach ($files as $file) {
            $id = (int) $file->id;
            $key = (string) $file->file_key;
            $status = (string) $file->status;
            if (!preg_match('/^[a-f0-9]{48}\.(pdf|jpg|jpeg|png)$/', $key)) { self::mark_retention_file_blocked($id, 'invalid_key'); continue; }
            $dir = self::store_directory((string) $file->store_hash, false);
            if (is_wp_error($dir)) { self::mark_retention_file_blocked($id, 'bucket_unavailable'); continue; }
            $path = trailingslashit($dir) . $key;
            if (is_link($path)) { self::mark_retention_file_blocked($id, 'symlink'); continue; }
            // The durable intent is written before unlink(). If the process
            // stopped after unlink but before the DB acknowledgement, a missing
            // file is safe to acknowledge only for that explicit state.
            if (!file_exists($path)) {
                if ($status !== 'deleting') { self::mark_retention_file_blocked($id, 'file_missing'); continue; }
                if ($wpdb->delete($table, array('id'=>$id), array('%d')) !== 1) {
                    $wpdb->update($table, array('last_error_code'=>'queue_ack_failed'), array('id'=>$id,'status'=>'deleting'), array('%s'), array('%d','%s'));
                    update_option('zmp_role_retention_last_status', 'queue_ack_failed', false);
                }
                continue;
            }
            $real = realpath($path);
            if (!$real || $real !== $path || !is_file($real) || self::path_is_inside($real, ABSPATH) || (int)filesize($real) !== (int)$file->file_size || !hash_equals((string)$file->sha256, (string)hash_file('sha256',$real))) {
                self::mark_retention_file_blocked($id, 'integrity'); continue;
            }
            if ($status === 'pending') {
                $intent = $wpdb->update($table, array('status'=>'deleting','last_error_code'=>''), array('id'=>$id,'status'=>'pending'), array('%s','%s'), array('%d','%s'));
                if ($intent !== 1) {
                    update_option('zmp_role_retention_last_status', 'queue_intent_write_failed', false);
                    continue;
                }
            } elseif ($status !== 'deleting') {
                self::mark_retention_file_blocked($id, 'invalid_status'); continue;
            }
            if (!@unlink($real)) {
                if ((int)$file->attempts >= 2) { self::mark_retention_file_blocked($id, 'unlink_failed'); }
                elseif ($wpdb->query($wpdb->prepare("UPDATE {$table} SET attempts=attempts+1,last_error_code='unlink_failed' WHERE id=%d AND status='deleting'", $id)) === false) { update_option('zmp_role_retention_last_status', 'queue_attempt_update_failed', false); }
                continue;
            }
            if ($wpdb->delete($table, array('id'=>$id), array('%d')) !== 1) {
                $wpdb->update($table, array('last_error_code'=>'queue_ack_failed'), array('id'=>$id,'status'=>'deleting'), array('%s'), array('%d','%s'));
                update_option('zmp_role_retention_last_status', 'queue_ack_failed', false);
            }
        }
        $remaining = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status IN ('pending','deleting')");
        if ($wpdb->last_error !== '') { update_option('zmp_role_retention_last_status', 'queue_count_failed', false); return -1; }
        if ((int)$remaining === 0 && in_array((string)get_option('zmp_role_retention_last_status', ''), array('queue_ack_failed','queue_intent_write_failed','queue_attempt_update_failed','queue_status_update_failed'), true)) {
            delete_option('zmp_role_retention_last_status');
        }
        return (int) $remaining;
    }

    private static function mark_retention_file_blocked($id, $code) {
        global $wpdb;
        if ($wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_role_retention_queue SET status='blocked',attempts=attempts+1,last_error_code=%s WHERE id=%d", sanitize_key($code), absint($id))) === false) {
            update_option('zmp_role_retention_last_status', 'queue_status_update_failed', false);
            return false;
        }
        return true;
    }

    private static function is_president($user_id = 0) {
        $user_id = absint($user_id ?: get_current_user_id());
        $user = $user_id ? get_userdata($user_id) : false;
        // The administrator role also receives this dashboard capability; only the dedicated role identifies the President.
        return !$user || in_array('zmp_president', (array) $user->roles, true);
    }

    private static function can_request_room_review($scope, $season_id, $category_id, $region_key) {
        if (!is_user_logged_in() || current_user_can('manage_options') || self::is_president()) { return false; }
        if (self::can_access_room($scope, $season_id, $category_id, $region_key)) { return true; }
        // A Moderator can request review of a specifically identified thread;
        // this does not authorize reading or listing that room's content.
        return self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap');
    }

    public static function register_message_review_menu() {
        if (current_user_can('manage_options') && !self::is_president()) {
            add_submenu_page('zmp-competitions', 'Барања за преглед на дописи', 'Преглед на дописи', 'manage_options', 'zmp-message-reviews', array(__CLASS__, 'render_admin_review_page'));
        }
    }

    private static function active_season_id() {
        $season = class_exists('ZMP_Category_Registrations') ? ZMP_Category_Registrations::active_season() : null;
        return $season ? absint($season->id) : 0;
    }

    private static function user_has_role_and_cap($role, $cap) {
        if (!is_user_logged_in()) { return false; }
        $user = wp_get_current_user();
        if (current_user_can('manage_options') || in_array('zmp_president', (array) $user->roles, true) || current_user_can('view_zmp_president_dashboard')) { return false; }
        return in_array($role, (array) $user->roles, true) && current_user_can($cap);
    }

    private static function room_scope($scope) {
        if ($scope === 'moderator') { return array(0, 0); }
        if (in_array($scope, array('tester', 'organizer', 'commission'), true)) {
            return array(self::active_season_id(), 0);
        }
        return array(-1, -1);
    }

    private static function has_assignment($table, $where, $values) {
        global $wpdb;
        $table = $wpdb->prefix . $table;
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $wpdb->get_var($wpdb->prepare($sql, $values)) > 0;
    }

    private static function is_assigned_juror($season_id, $category_id, $region_key = '') {
        if (!self::user_has_role_and_cap('zmp_commission', 'zmp_commission_cap') || !$season_id || !$category_id) { return false; }
        $uid = get_current_user_id();
        if ($region_key === '') { return self::has_assignment('zmp_jury_assignments', 'season_id=%d AND category_id=%d AND juror_user_id=%d', array($season_id, $category_id, $uid)); }
        // Regional assignments are independent of national appointments, but
        // must follow the canonical migration and valid-region guard.
        return class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_regional_juror_for($season_id, $category_id, $region_key, $uid);
    }

    private static function is_assigned_tester($season_id) {
        if (!self::user_has_role_and_cap('zmp_testator', 'zmp_testator_cap') || !$season_id) { return false; }
        return self::has_assignment('zmp_stage_testator_assignments', 'season_id=%d AND testator_user_id=%d', array($season_id, get_current_user_id()));
    }

    private static function is_assigned_organizer($season_id) {
        if (!self::user_has_role_and_cap('zmp_organizer', 'zmp_organizer_cap') || !$season_id) { return false; }
        return self::has_assignment('zmp_organizer_assignments', 'season_id=%d AND organizer_user_id=%d', array($season_id, get_current_user_id()));
    }

    /** All read, write, search, export, notification and download paths call this membership check. */
    public static function can_access_room($scope, $season_id = 0, $category_id = 0, $region_key = '') {
        if (!is_user_logged_in()) { return false; }
        $scope = sanitize_key((string) $scope);
        $expected = self::room_scope($scope);
        $season_id = absint($season_id);
        $category_id = absint($category_id);
        $region_key = $scope === 'commission' ? sanitize_key((string) $region_key) : '';
        if ($expected[0] < 0 || $season_id !== (int) $expected[0]) { return false; }
        if ($scope === 'moderator') { return $category_id === 0 && self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap'); }
        if ($scope === 'tester') { return $category_id === 0 && self::is_assigned_tester($season_id); }
        if ($scope === 'organizer') { return $category_id === 0 && self::is_assigned_organizer($season_id); }
        if ($scope === 'commission') { return self::is_assigned_juror($season_id, $category_id, $region_key); }
        return false;
    }

    public static function can_access_current_role_room($scope) {
        $scope = sanitize_key((string) $scope);
        if (!in_array($scope, array('moderator', 'tester', 'organizer'), true)) { return false; }
        $tuple = self::room_scope($scope);
        return $tuple[0] >= 0 && self::can_access_room($scope, (int) $tuple[0], 0);
    }

    public static function has_current_commission_room() {
        $season_id = self::active_season_id();
        return $season_id > 0 && self::user_has_role_and_cap('zmp_commission', 'zmp_commission_cap') && !empty(self::assigned_commission_rooms($season_id));
    }

    /** Explain why a role messenger tab is unavailable without exposing room data. */
    public static function unavailable_notice($scope) {
        $messages = array(
            'moderator' => 'Месинџерот за модератори е достапен само за сметки со модераторска улога и соодветно овластување.',
            'tester' => 'Месинџерот за тестатори ќе се појави по доделување тестаторско место за активната натпреварувачка година.',
            'organizer' => 'Месинџерот за организатори ќе се појави по доделување категорија за активната натпреварувачка година.',
            'commission' => 'Месинџерот за комисија ќе се појави по назначување во жири-категорија за активната натпреварувачка година.',
        );
        $scope = sanitize_key((string) $scope);
        if (!isset($messages[$scope])) { return ''; }
        $contact = $scope === 'moderator' ? 'Ако очекувате пристап, контактирајте го администраторот.' : 'Ако очекувате пристап, контактирајте го модераторот.';
        return '<section class="zmp-card zmp-role-messenger-notice" role="status"><p>' . esc_html($messages[$scope]) . '</p><p>' . esc_html($contact) . '</p></section>';
    }

    private static function member_ids($scope, $season_id, $category_id, $region_key = '') {
        global $wpdb;
        $ids = array();
        $wpdb->last_error = '';
        if ($scope === 'moderator') {
            $users = get_users(array('role' => 'zmp_moderator', 'fields' => 'ID'));
            if ($wpdb->last_error !== '' || !is_array($users)) { return false; }
            foreach ((array) $users as $id) { $ids[] = (int) $id; }
        } elseif ($scope === 'tester') {
            $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT testator_user_id FROM {$wpdb->prefix}zmp_stage_testator_assignments WHERE season_id=%d", $season_id));
        } elseif ($scope === 'organizer') {
            $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT organizer_user_id FROM {$wpdb->prefix}zmp_organizer_assignments WHERE season_id=%d", $season_id));
        } elseif ($scope === 'commission') {
            if ($region_key === '') { $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT juror_user_id FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND category_id=%d", $season_id, $category_id)); }
            elseif (class_exists('ZMP_Category_Jury') && class_exists('ZMP_Regions') && get_option('zmp_regional_jury_migration_49_2') === 'complete' && ZMP_Regions::valid_region($region_key)) { $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT juror_user_id FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d AND category_id=%d AND region=%s", $season_id, $category_id, $region_key)); }
            else { return false; }
        } else { return false; }
        if ($wpdb->last_error !== '' || !is_array($ids)) { return false; }
        $valid = array();
        foreach (array_unique(array_map('absint', (array) $ids)) as $id) {
            $user = get_userdata($id);
            if ($wpdb->last_error !== '') { return false; }
            if (!$user) { continue; }
            $admin = user_can($id, 'manage_options');
            $president_cap = user_can($id, 'view_zmp_president_dashboard');
            if ($wpdb->last_error !== '') { return false; }
            if ($admin || in_array('zmp_president', (array) $user->roles, true) || $president_cap) { continue; }
            $required = array('moderator' => array('zmp_moderator','zmp_moderator_cap'), 'tester' => array('zmp_testator','zmp_testator_cap'), 'organizer' => array('zmp_organizer','zmp_organizer_cap'), 'commission' => array('zmp_commission','zmp_commission_cap'))[$scope] ?? array('', '');
            $has_cap = user_can($id, $required[1]);
            if ($wpdb->last_error !== '') { return false; }
            if (!in_array($required[0], (array) $user->roles, true) || !$has_cap) { continue; }
            if ($scope === 'commission' && $region_key !== '') {
                $regional = ZMP_Category_Jury::is_regional_juror_for($season_id, $category_id, $region_key, $id);
                if ($wpdb->last_error !== '' || get_option('zmp_regional_jury_migration_49_2') !== 'complete') { return false; }
                if (!$regional) { continue; }
            }
            $valid[] = $id;
        }
        return $valid;
    }

    public static function unread_count($scope) {
        if (!is_user_logged_in()) { return 0; }
        $scope = sanitize_key((string) $scope);
        $scope_tuple = self::room_scope($scope);
        $season_id = (int) $scope_tuple[0];
        if ($season_id < 0) { return 0; }
        if ($scope === 'commission') {
            if (!$season_id || !self::user_has_role_and_cap('zmp_commission', 'zmp_commission_cap')) { return 0; }
            $sum = 0;
            foreach (self::assigned_commission_rooms($season_id) as $room) { $sum += self::unread_for_tuple($scope, $season_id, $room->category_id, $room->region_key); }
            return $sum;
        }
        if (!self::can_access_room($scope, $season_id, 0)) { return 0; }
        return self::unread_for_tuple($scope, $season_id, 0);
    }

    private static function unread_for_tuple($scope, $season_id, $category_id, $region_key = '') {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_role_message_notifications WHERE recipient_user_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s AND status='unread'",
            get_current_user_id(), $scope, $season_id, $category_id, $region_key
        ));
    }

    private static function assigned_categories($season_id) {
        global $wpdb;
        $uid = get_current_user_id();
        $ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT category_id FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND juror_user_id=%d", $season_id, $uid));
        $regional = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT category_id FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d AND juror_user_id=%d", $season_id, $uid));
        return array_values(array_unique(array_map('absint', array_merge((array) $ids, (array) $regional))));
    }

    private static function assigned_commission_rooms($season_id) {
        global $wpdb;
        $uid = get_current_user_id();
        $national = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT c.id AS category_id,c.name_mk,'' AS region_key FROM {$wpdb->prefix}zmp_jury_assignments a JOIN {$wpdb->prefix}zmp_categories c ON c.id=a.category_id WHERE a.season_id=%d AND a.juror_user_id=%d", $season_id, $uid));
        $regional = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT c.id AS category_id,c.name_mk,r.region AS region_key FROM {$wpdb->prefix}zmp_jury_regional_assignments r JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id WHERE r.season_id=%d AND r.juror_user_id=%d", $season_id, $uid));
        $rooms = array();
        $eligible_regional = array();
        foreach ((array) $regional as $room) {
            if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_regional_juror_for($season_id, (int) $room->category_id, (string) $room->region_key, $uid)) { $eligible_regional[] = $room; }
        }
        foreach (array_merge((array) $national, $eligible_regional) as $room) {
            $room->category_id = (int) $room->category_id;
            $room->region_key = (string) $room->region_key;
            $room->label = $room->name_mk;
            if ($room->region_key !== '') {
                $region_label = class_exists('ZMP_Regions') ? ZMP_Regions::label($room->region_key) : $room->region_key;
                $room->label .= ' · ' . $region_label;
            } else { $room->label .= ' · Национална комисија'; }
            $rooms[$room->category_id . ':' . $room->region_key] = $room;
        }
        uasort($rooms, static function($a, $b) { return strcasecmp($a->label, $b->label); });
        return array_values($rooms);
    }

    private static function mark_room_read($scope, $season_id, $category_id, $region_key = '', array $displayed_message_ids = array()) {
        $displayed_message_ids = array_values(array_unique(array_filter(array_map('absint', $displayed_message_ids))));
        if (!$displayed_message_ids) { return; }
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_role_message_notifications';
        $messages = $wpdb->prefix . 'zmp_role_messages';
        $reads = $wpdb->prefix . 'zmp_role_message_reads';
        $id_placeholders = implode(',', array_fill(0, count($displayed_message_ids), '%d'));
        $read_args = array_merge(array(get_current_user_id(), current_time('mysql'), $scope, $season_id, $category_id, $region_key, get_current_user_id()), $displayed_message_ids);
        $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$reads} (message_id,user_id,read_at) SELECT id,%d,%s FROM {$messages} WHERE scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s AND sender_id<>%d AND id IN ({$id_placeholders})", $read_args));
        $notification_args = array_merge(array(current_time('mysql'), get_current_user_id(), $scope, $season_id, $category_id, $region_key), $displayed_message_ids);
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET status='read',read_at=%s WHERE recipient_user_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s AND status='unread' AND message_id IN ({$id_placeholders})", $notification_args));
    }

    private static function store_directory($bucket = null, $create = true) {
        $docroot = isset($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT']) ? trim($_SERVER['DOCUMENT_ROOT']) : '';
        if ($docroot === '') { return new WP_Error('messenger_document_root_unknown'); }
        $root = realpath($docroot);
        if (!$root || !is_dir($root)) { return new WP_Error('messenger_document_root_unknown'); }
        $parent = $root ? realpath(dirname($root)) : false;
        if (!$root || !$parent || self::path_is_inside($parent, $root)) { return new WP_Error('messenger_storage_unsafe'); }
        $bucket = $bucket === null ? substr(hash('sha256', home_url('/')), 0, 12) : (string) $bucket;
        if (!preg_match('/^[a-f0-9]{12}$/', $bucket)) { return new WP_Error('messenger_storage_unsafe'); }
        $dir = trailingslashit($parent) . 'zmp-private-role-messages-' . $bucket;
        if (is_link($dir)) { return new WP_Error('messenger_storage_unsafe'); }
        $created = false;
        if (!is_dir($dir)) {
            if (!$create) { return new WP_Error('messenger_storage_missing'); }
            if (!@mkdir($dir, 0700)) { return new WP_Error('messenger_storage_unavailable'); }
            $created = true;
        }
        $real = realpath($dir);
        $expected = realpath($parent) . DIRECTORY_SEPARATOR . basename($dir);
        if (!$real || $real !== $expected || self::path_is_inside($real, $root)) { return new WP_Error('messenger_storage_unsafe'); }
        if (!@chmod($real, 0700)) {
            if ($created) { @rmdir($real); }
            return new WP_Error('messenger_storage_permissions');
        }
        $mode = @fileperms($real);
        if ($mode === false || ($mode & 0777) !== 0700) {
            if ($created) { @rmdir($real); }
            return new WP_Error('messenger_storage_permissions');
        }
        return $real;
    }

    private static function path_is_inside($path, $root) {
        $path = rtrim(str_replace('\\', '/', (string) $path), '/') . '/';
        $root = rtrim(str_replace('\\', '/', (string) $root), '/') . '/';
        if (DIRECTORY_SEPARATOR === '\\') { $path = strtolower($path); $root = strtolower($root); }
        return strpos($path, $root) === 0;
    }

    private static function prepare_attachment($field) {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }
        $file = $_FILES[$field];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { return new WP_Error('attachment_upload_invalid'); }
        $raw_size = $file['size'] ?? null;
        $raw_name = $file['name'] ?? null;
        $actual_size = filesize($file['tmp_name']);
        if (!is_scalar($raw_size) || !is_scalar($raw_name) || !is_int($actual_size) || $actual_size < 1 || $actual_size > self::MAX_ATTACHMENT_BYTES || (int) $raw_size !== $actual_size) { return new WP_Error('attachment_size_invalid'); }
        $size = $actual_size;
        $name = sanitize_file_name((string) wp_unslash($raw_name));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $signatures = array(
            'pdf' => array('application/pdf', '%PDF-'),
            'png' => array('image/png', hex2bin('89504e470d0a1a0a')),
            'jpg' => array('image/jpeg', hex2bin('ffd8ff')),
            'jpeg' => array('image/jpeg', hex2bin('ffd8ff')),
        );
        if (!isset($signatures[$ext])) { return new WP_Error('attachment_type_invalid'); }
        $head = file_get_contents($file['tmp_name'], false, null, 0, strlen($signatures[$ext][1]));
        if (!is_string($head) || !hash_equals($signatures[$ext][1], $head)) { return new WP_Error('attachment_type_invalid'); }
        $file['safe_name'] = $name ?: 'attachment.' . $ext;
        $file['safe_mime'] = $signatures[$ext][0];
        $file['safe_ext'] = $ext;
        $file['safe_size'] = $size;
        $file['safe_hash'] = hash_file('sha256', $file['tmp_name']);
        if (!is_string($file['safe_hash']) || !preg_match('/^[a-f0-9]{64}$/', $file['safe_hash'])) { return new WP_Error('attachment_integrity_failed'); }
        return $file;
    }

    private static function persist_attachment($file, $message_id, $scope, $season_id, $category_id, $region_key) {
        if (!$file) { return true; }
        $dir = self::store_directory();
        if (is_wp_error($dir)) { return $dir; }
        try { $key = bin2hex(random_bytes(24)) . '.' . $file['safe_ext']; }
        catch (Exception $e) { return new WP_Error('attachment_key_failed'); }
        $path = trailingslashit($dir) . $key;
        $destination = @fopen($path, 'x+b');
        if (!$destination) { return new WP_Error('attachment_store_failed'); }
        $source = @fopen($file['tmp_name'], 'rb');
        $copied = $source ? stream_copy_to_stream($source, $destination) : false;
        if (is_resource($source)) { fclose($source); }
        fclose($destination);
        if ($copied !== (int) $file['safe_size']) { @unlink($path); return new WP_Error('attachment_store_failed'); }
        if (!@chmod($path, 0600)) { @unlink($path); return new WP_Error('attachment_permissions_failed'); }
        $mode = @fileperms($path);
        if ($mode === false || ($mode & 0777) !== 0600) { @unlink($path); return new WP_Error('attachment_permissions_failed'); }
        if (!is_file($path) || !hash_equals($file['safe_hash'], hash_file('sha256', $path))) { @unlink($path); return new WP_Error('attachment_integrity_failed'); }
        global $wpdb;
        $ok = $wpdb->insert($wpdb->prefix . 'zmp_role_message_attachments', array(
            'message_id' => $message_id, 'scope_type' => $scope, 'season_id' => $season_id, 'category_id' => $category_id, 'region_key' => $region_key,
            'file_key' => $key, 'store_hash' => substr(hash('sha256', home_url('/')), 0, 12), 'original_name' => $file['safe_name'], 'mime_type' => $file['safe_mime'],
            'file_size' => $file['safe_size'], 'sha256' => $file['safe_hash'], 'created_at' => current_time('mysql'),
        ), array('%d','%s','%d','%d','%s','%s','%s','%s','%s','%d','%s','%s'));
        if (!$ok) { @unlink($path); return new WP_Error('attachment_metadata_failed'); }
        return array('key' => $key, 'path' => $path);
    }

    private static function notify_room_members($message_id, $thread_id, $scope, $season_id, $category_id, $region_key, array $recipients) {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_role_message_notifications';
        $sender = get_current_user_id();
        foreach ($recipients as $recipient) {
            if ((int) $recipient === (int) $sender) { continue; }
            $ok = $wpdb->insert($table, array('message_id'=>$message_id,'thread_id'=>$thread_id,'scope_type'=>$scope,'season_id'=>$season_id,'category_id'=>$category_id,'region_key'=>$region_key,'recipient_user_id'=>$recipient,'status'=>'unread','created_at'=>current_time('mysql')), array('%d','%d','%s','%d','%d','%s','%d','%s','%s'));
            if (!$ok) { return false; }
        }
        return true;
    }

    private static function safe_return_url() {
        $raw = isset($_POST['return_url']) && is_scalar($_POST['return_url']) ? esc_url_raw(wp_unslash($_POST['return_url'])) : '';
        return wp_validate_redirect($raw, home_url('/'));
    }

    private static function transaction_tables_available() {
        global $wpdb;
        $tables = array('zmp_role_messages', 'zmp_role_message_reads', 'zmp_role_message_notifications', 'zmp_role_message_attachments', 'zmp_admin_message_review_requests', 'zmp_admin_message_review_audit', 'zmp_role_review_notices', 'zmp_role_retention_audit', 'zmp_role_retention_queue');
        foreach ($tables as $suffix) {
            $table = $wpdb->prefix . $suffix;
            $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS WHERE Name = %s', $table));
            if (!$status || empty($status->Engine) || strcasecmp((string) $status->Engine, 'InnoDB') !== 0) { return false; }
        }
        return true;
    }

    private static function cleanup_new_attachment_after_rollback($attachment_result, $message_id) {
        if (!is_array($attachment_result) || empty($attachment_result['path']) || !is_file($attachment_result['path'])) { return; }
        global $wpdb;
        $wpdb->last_error = '';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_role_message_attachments WHERE message_id=%d AND file_key=%s", (int) $message_id, (string) ($attachment_result['key'] ?? '')));
        $query_succeeded = $wpdb->last_error === '' && is_scalar($count) && ctype_digit((string) $count);
        if ($query_succeeded && (int) $count === 0) { @unlink($attachment_result['path']); }
    }

    public static function handle_post() {
        if (!isset($_POST['zmp_role_message_action'])) { return; }
        if (!is_user_logged_in()) { wp_die('Најавете се за да испратите порака.', '', array('response'=>403)); }
        check_admin_referer(self::NONCE);
        $scope = sanitize_key(wp_unslash($_POST['scope_type'] ?? ''));
        $tuple = self::room_scope($scope);
        if ($tuple[0] < 0 || ($scope !== 'moderator' && !$tuple[0])) { wp_die('Нема активна натпреварувачка година.', '', array('response'=>400)); }
        $season_id = (int) $tuple[0];
        $category_id = $scope === 'commission' ? absint($_POST['category_id'] ?? 0) : 0;
        $raw_region = $scope === 'commission' ? wp_unslash($_POST['region_key'] ?? '') : '';
        $region_key = is_scalar($raw_region) ? sanitize_key((string) $raw_region) : '';
        if (!self::can_access_room($scope, $season_id, $category_id, $region_key)) { wp_die('Немате пристап до овој разговор.', '', array('response'=>403)); }
        global $wpdb;
        $messages = $wpdb->prefix . 'zmp_role_messages';
        $thread_id = absint($_POST['thread_id'] ?? 0);
        $raw_message = wp_unslash($_POST['message'] ?? '');
        $message = is_scalar($raw_message) ? sanitize_textarea_field((string) $raw_message) : '';
        $raw_subject = wp_unslash($_POST['subject'] ?? '');
        $subject = is_scalar($raw_subject) ? sanitize_text_field((string) $raw_subject) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
        if ($message === '' || $length > 5000 || (!$thread_id && ($subject === '' || strlen($subject) > 190))) { wp_die('Внесете наслов и порака (најмногу 5.000 знаци).', '', array('response'=>400)); }
        $upload = self::prepare_attachment('attachment');
        if (is_wp_error($upload)) { wp_die('Прилогот не е прифатен. Користете PDF, JPG или PNG до 5 MB.', '', array('response'=>400)); }
        if ($thread_id) {
            $root = $wpdb->get_row($wpdb->prepare("SELECT id,scope_type,season_id,category_id,region_key FROM {$messages} WHERE thread_id=%d ORDER BY id LIMIT 1", $thread_id));
            if (!$root || $root->scope_type !== $scope || (int) $root->season_id !== $season_id || (int) $root->category_id !== $category_id || (string) $root->region_key !== $region_key) { wp_die('Разговорот не постои во овој простор.', '', array('response'=>403)); }
            $subject = '';
        }
        if (!self::transaction_tables_available()) { wp_die('Интерниот простор моментално не може безбедно да зачува пораки.', '', array('response'=>503)); }
        if ($wpdb->query('START TRANSACTION') === false) { wp_die('Интерниот простор моментално не може безбедно да зачува пораки.', '', array('response'=>503)); }
        $attachment_result = true;
        $created_at = current_time('mysql');
        $expires_at = $scope === 'moderator' ? self::add_months($created_at, 24) : null;
        if ($scope === 'moderator' && !$expires_at) { $wpdb->query('ROLLBACK'); wp_die('Рокот за чување не може да се пресмета.', '', array('response'=>503)); }
        if ($scope === 'moderator' && $thread_id) {
            $prior_expiry = $wpdb->get_var($wpdb->prepare("SELECT expires_at FROM {$messages} WHERE scope_type='moderator' AND season_id=0 AND category_id=0 AND region_key='' AND thread_id=%d ORDER BY expires_at LIMIT 1 FOR UPDATE", $thread_id));
            if ($wpdb->last_error !== '' || !$prior_expiry) { $wpdb->query('ROLLBACK'); wp_die('Разговорот или неговиот рок се променил. Освежете ја страницата.', '', array('response'=>409)); }
            if ($prior_expiry < $expires_at) { $expires_at = $prior_expiry; }
        }
        // Resolve every recipient before writing the message or private file.
        // An unconfirmed assignment query must fail the whole transaction.
        $recipients = self::member_ids($scope, $season_id, $category_id, $region_key);
        if ($recipients === false) { $wpdb->query('ROLLBACK'); wp_die('Не може безбедно да се утврдат примачите.', '', array('response'=>500)); }
        $ok = $wpdb->insert($messages, array('thread_id'=>$thread_id,'scope_type'=>$scope,'season_id'=>$season_id,'category_id'=>$category_id,'region_key'=>$region_key,'sender_id'=>get_current_user_id(),'subject'=>$subject,'message'=>$message,'created_at'=>$created_at,'expires_at'=>$expires_at), array('%d','%s','%d','%d','%s','%d','%s','%s','%s','%s'));
        if (!$ok) { $wpdb->query('ROLLBACK'); wp_die('Пораката не можеше да се зачува.', '', array('response'=>500)); }
        $id = (int) $wpdb->insert_id;
        $root_id = $thread_id ?: $id;
        if (!$thread_id && $wpdb->update($messages, array('thread_id'=>$id), array('id'=>$id), array('%d'), array('%d')) === false) { $wpdb->query('ROLLBACK'); wp_die('Разговорот не можеше да се зачува.', '', array('response'=>500)); }
        $attachment_result = self::persist_attachment($upload, $id, $scope, $season_id, $category_id, $region_key);
        if (is_wp_error($attachment_result) || !self::notify_room_members($id, $root_id, $scope, $season_id, $category_id, $region_key, $recipients)) {
            $wpdb->query('ROLLBACK');
            self::cleanup_new_attachment_after_rollback($attachment_result, $id);
            wp_die('Пораката или нејзиниот прилог не можеа безбедно да се зачуваат.', '', array('response'=>500));
        }
        if ($wpdb->query('COMMIT') === false) {
            $wpdb->query('ROLLBACK');
            self::cleanup_new_attachment_after_rollback($attachment_result, $id);
            wp_die('Испраќањето не можеше да се потврди. Проверете го просторот пред повторен обид.', '', array('response'=>503));
        }
        $return = remove_query_arg(array('zmp_role_message_sent','zmp_message_category','zmp_moderator_tab','zmp_testator_tab','zmp_organizer_tab','zmp_commission_tab'), self::safe_return_url());
        $args = array('zmp_role_message_sent' => '1');
        $tab_key = array('moderator'=>'zmp_moderator_tab','tester'=>'zmp_testator_tab','organizer'=>'zmp_organizer_tab','commission'=>'zmp_commission_tab')[$scope];
        $args[$tab_key] = 'messenger';
        if ($scope === 'commission') { $args['zmp_message_category'] = $category_id; $args['zmp_message_region'] = $region_key; }
        wp_safe_redirect(add_query_arg($args, $return));
        exit;
    }

    private static function reject_review_request($moderator_form, $code, $message, $status) {
        if ($moderator_form) {
            $return = remove_query_arg(array('zmp_review_request_error', 'zmp_admin_review_request'), self::safe_return_url());
            $return = add_query_arg('zmp_moderator_tab', 'messenger', $return);
            $return = add_query_arg('zmp_review_request_error', $code, $return);
            $return = explode('#', $return, 2)[0] . '#zmp-moderator-review-request';
            wp_safe_redirect($return);
            exit;
        }
        wp_die($message, '', array('response'=>$status));
    }

    public static function handle_request_admin_message_access() {
        if (!is_user_logged_in()) { wp_die('Најавете се за да побарате преглед.', '', array('response'=>403)); }
        $scope = isset($_POST['scope_type']) && is_scalar($_POST['scope_type']) ? sanitize_key(wp_unslash($_POST['scope_type'])) : '';
        $season_id = isset($_POST['season_id']) && is_scalar($_POST['season_id']) ? absint(wp_unslash($_POST['season_id'])) : 0;
        $category_id = isset($_POST['category_id']) && is_scalar($_POST['category_id']) ? absint(wp_unslash($_POST['category_id'])) : 0;
        $raw_region = $_POST['region_key'] ?? '';
        $region_key = is_scalar($raw_region) ? sanitize_key(wp_unslash((string) $raw_region)) : '';
        $thread_id = isset($_POST['thread_id']) && is_scalar($_POST['thread_id']) ? absint(wp_unslash($_POST['thread_id'])) : 0;
        $moderator_form_nonce = self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap')
            && isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'zmp_admin_message_review_request_moderator');
        if (!$moderator_form_nonce) { check_admin_referer('zmp_admin_message_review_request_' . $thread_id); }
        $scope_tuple = self::room_scope($scope);
        if (!in_array($scope, array('moderator','tester','organizer','commission'), true) || (int) $scope_tuple[0] !== $season_id || !self::can_request_room_review($scope, $season_id, $category_id, $region_key)) {
            self::reject_review_request($moderator_form_nonce, 'invalid', 'Немате право да побарате преглед на овој простор.', 403);
        }
        $raw_reason = $_POST['reason'] ?? '';
        $reason = is_scalar($raw_reason) ? trim(sanitize_textarea_field(wp_unslash((string) $raw_reason))) : '';
        $reason_length = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($thread_id < 1 || $reason_length < 20 || $reason_length > 1000) { self::reject_review_request($moderator_form_nonce, 'reason', 'Внесете причина од 20 до 1.000 знаци.', 400); }
        global $wpdb;
        $root = $wpdb->get_row($wpdb->prepare(
            "SELECT id,scope_type,season_id,category_id,region_key FROM {$wpdb->prefix}zmp_role_messages WHERE thread_id=%d ORDER BY id LIMIT 1",
            $thread_id
        ));
        if (!$root || $root->scope_type !== $scope || (int) $root->season_id !== $season_id || (int) $root->category_id !== $category_id || (string) $root->region_key !== $region_key) {
            self::reject_review_request($moderator_form_nonce, 'invalid', 'Разговорот не постои во избраниот простор.', 403);
        }
        $table = $wpdb->prefix . 'zmp_admin_message_review_requests';
        $existing = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE requester_user_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s AND thread_id=%d AND status='pending' LIMIT 1", get_current_user_id(), $scope, $season_id, $category_id, $region_key, $thread_id));
        if ($existing) { self::reject_review_request($moderator_form_nonce, 'duplicate', 'Веќе има отворено барање за овој разговор.', 409); }
        $inserted = $wpdb->insert($table, array(
            'requester_user_id'=>get_current_user_id(), 'scope_type'=>$scope, 'season_id'=>$season_id,
            'category_id'=>$category_id, 'region_key'=>$region_key, 'thread_id'=>$thread_id,
            'reason'=>$reason, 'status'=>'pending', 'created_at'=>current_time('mysql'),
        ), array('%d','%s','%d','%d','%s','%d','%s','%s','%s'));
        if (!$inserted) { wp_die('Барањето не можеше да се зачува.', '', array('response'=>500)); }
        $tab_args = array('moderator'=>'zmp_moderator_tab','tester'=>'zmp_testator_tab','organizer'=>'zmp_organizer_tab','commission'=>'zmp_commission_tab');
        $return = add_query_arg($tab_args[$moderator_form_nonce ? 'moderator' : $scope], 'messenger', self::safe_return_url());
        $return = remove_query_arg(array('zmp_admin_review_request'), $return);
        wp_safe_redirect(add_query_arg('zmp_admin_review_request', 'sent', $return));
        exit;
    }

    private static function write_admin_review_audit($request, $actor_user_id, $action) {
        global $wpdb;
        $inserted = $wpdb->insert($wpdb->prefix . 'zmp_admin_message_review_audit', array(
            'actor_user_id'=>absint($actor_user_id), 'request_id'=>(int) $request->id, 'thread_id'=>(int) $request->thread_id,
            'scope_type'=>(string) $request->scope_type, 'season_id'=>(int) $request->season_id,
            'category_id'=>(int) $request->category_id, 'region_key'=>(string) $request->region_key,
            'action'=>sanitize_key($action), 'created_at'=>current_time('mysql'),
        ), array('%d','%d','%d','%s','%d','%d','%s','%s','%s'));
        return $inserted ? (int) $wpdb->insert_id : false;
    }

    private static function notify_thread_participants($audit_id, $request) {
        global $wpdb;
        $senders = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT sender_id FROM {$wpdb->prefix}zmp_role_messages WHERE thread_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s LIMIT 1001",
            (int) $request->thread_id, (string) $request->scope_type, (int) $request->season_id, (int) $request->category_id, (string) $request->region_key
        ));
        if ($wpdb->last_error !== '' || !is_array($senders)) { return false; }
        $recipients = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT n.recipient_user_id FROM {$wpdb->prefix}zmp_role_message_notifications n JOIN {$wpdb->prefix}zmp_role_messages m ON m.id=n.message_id AND m.thread_id=n.thread_id AND m.scope_type=n.scope_type AND m.season_id=n.season_id AND m.category_id=n.category_id AND m.region_key=n.region_key WHERE n.thread_id=%d AND n.scope_type=%s AND n.season_id=%d AND n.category_id=%d AND n.region_key=%s LIMIT 1001",
            (int) $request->thread_id, (string) $request->scope_type, (int) $request->season_id, (int) $request->category_id, (string) $request->region_key
        ));
        if ($wpdb->last_error !== '' || !is_array($recipients)) { return false; }
        if (count((array) $senders) > 1000 || count((array) $recipients) > 1000) { return false; }
        $senders = array_unique(array_map('absint', array_merge((array) $senders, (array) $recipients)));
        if (count($senders) > 1000) { return false; }
        $table = $wpdb->prefix . 'zmp_role_review_notices';
        foreach ($senders as $recipient) {
            if (!$recipient) { continue; }
            $user = get_userdata($recipient);
            if ($wpdb->last_error !== '') { return false; }
            if (!$user) {
                // A deleted account is no longer a notification target.
                continue;
            }
            if (in_array('zmp_president', (array) $user->roles, true)) { continue; }
            $inserted = $wpdb->insert($table, array(
                'audit_id'=>(int) $audit_id, 'scope_type'=>(string) $request->scope_type, 'season_id'=>(int) $request->season_id,
                'category_id'=>(int) $request->category_id, 'region_key'=>(string) $request->region_key,
                'thread_id'=>(int) $request->thread_id, 'recipient_user_id'=>(int) $recipient, 'created_at'=>current_time('mysql'),
            ), array('%d','%s','%d','%d','%s','%d','%d','%s'));
            if (!$inserted) { return false; }
        }
        return true;
    }

    public static function handle_admin_message_review_read() {
        if (!current_user_can('manage_options') || self::is_president()) { wp_die('Немате право да прегледувате барања.', '', array('response'=>403)); }
        $request_id = isset($_POST['request_id']) && is_scalar($_POST['request_id']) ? absint(wp_unslash($_POST['request_id'])) : 0;
        check_admin_referer('zmp_admin_message_review_read_' . $request_id);
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_admin_message_review_requests WHERE id=%d AND status='pending'", $request_id));
        if (!$request) { wp_die('Барањето не е достапно или веќе е прегледано.', '', array('response'=>404)); }
        $thread_id = (int) $request->thread_id;
        $scope_type = (string) $request->scope_type;
        $season_id = (int) $request->season_id;
        $category_id = (int) $request->category_id;
        $region_key = (string) $request->region_key;
        $tuple_sql = "thread_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s";
        $tuple_values = array($thread_id, $scope_type, $season_id, $category_id, $region_key);
        $total_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_role_messages WHERE $tuple_sql", $tuple_values));
        if ($wpdb->last_error) { wp_die('Бројот на пораки не може да се потврди.', '', array('response'=>503)); }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id,m.thread_id,m.sender_id,m.subject,m.message,m.created_at,u.display_name FROM {$wpdb->prefix}zmp_role_messages m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.thread_id=%d AND m.scope_type=%s AND m.season_id=%d AND m.category_id=%d AND m.region_key=%s ORDER BY m.id DESC LIMIT 200",
            $tuple_values
        ));
        if (!$rows) { wp_die('Разговорот повеќе не содржи достапни пораки.', '', array('response'=>404)); }
        $rows = array_reverse($rows);
        $omitted_count = max(0, $total_count - count($rows));
        $omitted_range = null;
        if ($omitted_count > 0) {
            $omitted_range = $wpdb->get_row($wpdb->prepare("SELECT MIN(id) AS first_id,MAX(id) AS last_id FROM {$wpdb->prefix}zmp_role_messages WHERE $tuple_sql AND id < %d", array_merge($tuple_values, array((int) $rows[0]->id))));
            if ($wpdb->last_error || !$omitted_range) { wp_die('Опсегот на изоставените пораки не може да се потврди.', '', array('response'=>503)); }
        }
        if (!self::transaction_tables_available() || $wpdb->query('START TRANSACTION') === false) { wp_die('Ревизорскиот преглед не може безбедно да се зачува.', '', array('response'=>503)); }
        $audit_id = self::write_admin_review_audit($request, get_current_user_id(), 'thread_reviewed');
        $notice_ok = $audit_id && self::notify_thread_participants($audit_id, $request);
        $updated = $notice_ok ? $wpdb->update($wpdb->prefix . 'zmp_admin_message_review_requests', array('status'=>'reviewed','reviewed_by'=>get_current_user_id(),'reviewed_at'=>current_time('mysql')), array('id'=>$request_id,'status'=>'pending'), array('%s','%d','%s'), array('%d','%s')) : false;
        if ($updated !== 1 || $wpdb->query('COMMIT') === false) {
            $wpdb->query('ROLLBACK');
            wp_die('Прегледот или известувањето не можеа безбедно да се зачуваат.', '', array('response'=>503));
        }
        nocache_headers();
        $html = '<div class="wrap"><h1>Ограничен преглед на разговор</h1><p>Прегледан е само побараниот разговор; содржината не е изменета. Прилозите не се прикажуваат во овој преглед. Прикажани се најновите до 200 пораки.</p>';
        if ($omitted_count > 0) { $html .= '<div class="notice notice-warning"><p>Постари пораки се изоставени поради лимитот: ' . (int) $omitted_count . ' пораки' . ($omitted_range ? ' (ID ' . (int) $omitted_range->first_id . '–' . (int) $omitted_range->last_id . ')' : '') . '. Овој екран не ги прегледал.</p></div>'; }
        $html .= '<h2>Причина за барањето</h2><p>' . esc_html((string) $request->reason) . '</p><div class="zmp-table-responsive"><table class="widefat striped"><thead><tr><th>Испраќач</th><th>Време</th><th>Порака</th></tr></thead><tbody>';
        foreach ($rows as $row) { $html .= '<tr><td>' . esc_html($row->display_name ?: 'Корисник') . '</td><td>' . esc_html($row->created_at) . '</td><td>' . nl2br(esc_html($row->message)) . '</td></tr>'; }
        $html .= '</tbody></table></div><p><a class="button" href="' . esc_url(admin_url('admin.php?page=zmp-message-reviews')) . '">Назад кон барањата</a></p></div>';
        wp_die($html, 'Преглед на дописи', array('response'=>200, 'back_link'=>false));
    }

    public static function render_admin_review_page() {
        if (!current_user_can('manage_options') || self::is_president()) { wp_die('Немате право да го отворите овој екран.', '', array('response'=>403)); }
        global $wpdb;
        $pending = $wpdb->get_results("SELECT r.*,u.display_name FROM {$wpdb->prefix}zmp_admin_message_review_requests r LEFT JOIN {$wpdb->users} u ON u.ID=r.requester_user_id WHERE r.status='pending' ORDER BY r.created_at ASC LIMIT 200");
        echo '<div class="wrap"><h1>Барања за преглед на дописи</h1><p>Прикажан е само конкретниот разговор за кој член на собата или назначен модератор поднел образложено барање. Прегледот е само за читање и се евидентира.</p>';
        if (!$pending) { echo '<p>Нема отворени барања.</p></div>'; return; }
        echo '<div class="zmp-table-responsive"><table class="widefat striped"><thead><tr><th>Барател</th><th>Простор</th><th>Година / категорија / регион</th><th>Разговор</th><th>Причина</th><th>Поднесено</th><th></th></tr></thead><tbody>';
        foreach ($pending as $request) {
            echo '<tr><td>' . esc_html($request->display_name ?: 'Корисник') . '</td><td>' . esc_html($request->scope_type) . '</td><td>' . (int) $request->season_id . ' / ' . (int) $request->category_id . ' / ' . esc_html($request->region_key ?: '—') . '</td><td>' . (int) $request->thread_id . '</td><td>' . esc_html($request->reason) . '</td><td>' . esc_html($request->created_at) . '</td><td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="zmp_admin_message_review_read"><input type="hidden" name="request_id" value="' . (int) $request->id . '">' . wp_nonce_field('zmp_admin_message_review_read_' . (int) $request->id, '_wpnonce', true, false) . '<button class="button button-primary">Прегледај го разговорот</button></form></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    public static function handle_review_notice_ack() {
        if (!is_user_logged_in()) { wp_die('Најавете се за да го означите известувањето.', '', array('response'=>403)); }
        $notice_id = isset($_POST['notice_id']) && is_scalar($_POST['notice_id']) ? absint(wp_unslash($_POST['notice_id'])) : 0;
        check_admin_referer('zmp_role_review_notice_ack_' . $notice_id);
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_role_review_notices';
        $notice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND recipient_user_id=%d AND read_at IS NULL", $notice_id, get_current_user_id()));
        if (!$notice || !self::can_access_room($notice->scope_type, (int) $notice->season_id, (int) $notice->category_id, (string) $notice->region_key)) { wp_die('Немате пристап до известувањето.', '', array('response'=>403)); }
        $updated = $wpdb->update($table, array('read_at'=>current_time('mysql')), array('id'=>$notice_id,'recipient_user_id'=>get_current_user_id(),'read_at'=>null), array('%s'), array('%d','%d','%s'));
        if ($updated !== 1) { wp_die('Известувањето не можеше да се означи како прочитано.', '', array('response'=>500)); }
        $return = remove_query_arg(array('zmp_review_notice'), self::safe_return_url());
        wp_safe_redirect(add_query_arg('zmp_review_notice', 'read', $return));
        exit;
    }

    public static function render($scope, $active_messenger_tab = false) {
        $scope = sanitize_key((string) $scope);
        if (!in_array($scope, array('moderator','tester','organizer','commission'), true)) { return ''; }
        $tuple = self::room_scope($scope);
        if ($tuple[0] < 0 || !self::can_access_room($scope, (int) $tuple[0], 0) && $scope !== 'commission') { return ''; }
        global $wpdb;
        $season_id = (int) $tuple[0];
        $rooms = $scope === 'commission' ? self::assigned_commission_rooms($season_id) : array();
        $requested_category = isset($_GET['zmp_message_category']) && is_scalar($_GET['zmp_message_category']) ? absint(wp_unslash($_GET['zmp_message_category'])) : 0;
        $category_id = $scope === 'commission' ? $requested_category : 0;
        $requested_region = isset($_GET['zmp_message_region']) && is_scalar($_GET['zmp_message_region']) ? sanitize_key(wp_unslash($_GET['zmp_message_region'])) : '';
        $has_requested_room = false;
        if ($scope === 'commission' && isset($_GET['zmp_room']) && is_scalar($_GET['zmp_room'])) {
            $room_parts = explode(':', sanitize_text_field(wp_unslash($_GET['zmp_room'])), 2);
            if (count($room_parts) === 2) { $category_id = absint($room_parts[0]); $requested_region = sanitize_key($room_parts[1]); $has_requested_room = true; }
        }
        $valid_room_keys = array_map(static function($room) { return $room->category_id . ':' . $room->region_key; }, $rooms);
        $valid_ids = array_map(static function($room) { return (int) $room->category_id; }, $rooms);
        if ($scope === 'commission' && !$requested_category && !$has_requested_room) { $category_id = (int) ($rooms[0]->category_id ?? 0); $requested_region = (string) ($rooms[0]->region_key ?? ''); }
        $room_key = $category_id . ':' . $requested_region;
        if ($scope === 'commission' && (!in_array($room_key, $valid_room_keys, true) || !self::can_access_room($scope, $season_id, $category_id, $requested_region))) { $category_id = 0; $requested_region = ''; }
        if ($scope === 'commission' && !$category_id) { return '<section class="zmp-card"><h3>Интерна комуникација на комисијата</h3><p>Немате активно назначување во категорија за оваа година.</p></section>'; }
        if (!self::can_access_room($scope, $season_id, $category_id, $requested_region)) { return ''; }
        $query = isset($_GET['zmp_messenger_search']) && is_scalar($_GET['zmp_messenger_search']) ? sanitize_text_field(wp_unslash($_GET['zmp_messenger_search'])) : '';
        $query = function_exists('mb_substr') ? mb_substr($query, 0, 100) : substr($query, 0, 100);
        $sql = "SELECT m.*,u.display_name,a.id AS attachment_id,a.original_name AS attachment_name FROM {$wpdb->prefix}zmp_role_messages m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id LEFT JOIN {$wpdb->prefix}zmp_role_message_attachments a ON a.message_id=m.id AND a.scope_type=m.scope_type AND a.season_id=m.season_id AND a.category_id=m.category_id AND a.region_key=m.region_key WHERE m.scope_type=%s AND m.season_id=%d AND m.category_id=%d AND m.region_key=%s";
        $args = array($scope, $season_id, $category_id, $requested_region);
        if ($query !== '') { $like = '%' . $wpdb->esc_like($query) . '%'; $sql .= ' AND (m.subject LIKE %s OR m.message LIKE %s)'; $args[] = $like; $args[] = $like; }
        $cursor_thread = isset($_GET['zmp_message_cursor_thread']) && is_scalar($_GET['zmp_message_cursor_thread']) ? absint(wp_unslash($_GET['zmp_message_cursor_thread'])) : 0;
        $cursor_id = isset($_GET['zmp_message_cursor_id']) && is_scalar($_GET['zmp_message_cursor_id']) ? absint(wp_unslash($_GET['zmp_message_cursor_id'])) : 0;
        if (!$cursor_thread || !$cursor_id) { $cursor_thread = 0; $cursor_id = 0; }
        if ($cursor_thread) {
            $sql .= ' AND (m.thread_id < %d OR (m.thread_id = %d AND m.id > %d))';
            array_push($args, $cursor_thread, $cursor_thread, $cursor_id);
        }
        // One lookahead row establishes whether the next bounded page exists.
        $sql .= ' ORDER BY m.thread_id DESC,m.id ASC LIMIT 101';
        $messages = (array) $wpdb->get_results($wpdb->prepare($sql, $args));
        $has_next_page = count($messages) > 100;
        if ($has_next_page) { array_pop($messages); }
        if ($active_messenger_tab) { self::mark_room_read($scope, $season_id, $category_id, $requested_region, array_map(static function($item) { return (int) $item->id; }, (array) $messages)); }
        $threads = array();
        foreach ((array) $messages as $item) { $threads[(int) $item->thread_id][] = $item; }
        $review_notices = $wpdb->get_results($wpdb->prepare("SELECT id,thread_id,created_at FROM {$wpdb->prefix}zmp_role_review_notices WHERE recipient_user_id=%d AND scope_type=%s AND season_id=%d AND category_id=%d AND region_key=%s AND read_at IS NULL ORDER BY created_at DESC LIMIT 20", get_current_user_id(), $scope, $season_id, $category_id, $requested_region));
        $page_url = get_permalink() ?: home_url('/');
        if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) { $page_url = add_query_arg('zmp_portal_tab', sanitize_key(wp_unslash($_GET['zmp_portal_tab'])), $page_url); }
        $tab_keys = array('moderator'=>'zmp_moderator_tab','tester'=>'zmp_testator_tab','organizer'=>'zmp_organizer_tab','commission'=>'zmp_commission_tab');
        $page_url = add_query_arg($tab_keys[$scope], 'messenger', $page_url);
        if ($scope === 'commission') { $page_url = add_query_arg('zmp_room', $category_id . ':' . $requested_region, $page_url); }
        if ($query !== '') { $page_url = add_query_arg('zmp_messenger_search', $query, $page_url); }
        $first_page_url = remove_query_arg(array('zmp_message_cursor_thread', 'zmp_message_cursor_id'), $page_url);
        if ($cursor_thread) { $page_url = add_query_arg(array('zmp_message_cursor_thread'=>$cursor_thread, 'zmp_message_cursor_id'=>$cursor_id), $page_url); }
        $next_page_url = '';
        if ($has_next_page && $messages) {
            $last_message = end($messages);
            $next_page_url = add_query_arg(array('zmp_message_cursor_thread'=>(int) $last_message->thread_id, 'zmp_message_cursor_id'=>(int) $last_message->id), $first_page_url);
        }
        $labels = array('moderator'=>'модераторите','tester'=>'тестаторите','organizer'=>'организаторите','commission'=>'комисијата');
        $review_request_error = isset($_GET['zmp_review_request_error']) && is_scalar($_GET['zmp_review_request_error']) ? sanitize_key(wp_unslash($_GET['zmp_review_request_error'])) : '';
        $review_request_errors = array('invalid'=>'Проверете ги внесените податоци за просторот и разговорот.', 'reason'=>'Внесете број на разговор и причина од 20 до 1.000 знаци.', 'duplicate'=>'Веќе имате отворено барање за овој разговор.');
        if (!isset($review_request_errors[$review_request_error])) { $review_request_error = ''; }
        ob_start(); ?>
        <section class="zmp-card zmp-role-messenger" aria-label="Интерен простор за <?php echo esc_attr($labels[$scope]); ?>">
            <h3>Интерен простор за <?php echo esc_html($labels[$scope]); ?></h3>
            <p><?php if ($scope === 'moderator'): ?>Достапен е само за сметки со модераторска улога и соодветно овластување.<?php else: ?>Достапен е само за корисници со соодветна улога и тековно назначување.<?php endif; ?> Пораките и прилозите не се испраќаат по е-пошта. За просторите врзани за година, чувањето трае 12 месеци по нејзиното официјално затворање. Модераторските пораки се чуваат најмногу 24 месеци според посебното правило.</p>
            <?php if ($scope === 'moderator' && self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap')): ?><details id="zmp-moderator-review-request" <?php echo $review_request_error !== '' ? 'open' : ''; ?>><summary>Побарај преглед на конкретен разговор</summary><?php if ($review_request_error !== ''): ?><p class="zmp-alert zmp-alert-warning" role="alert"><?php echo esc_html($review_request_errors[$review_request_error]); ?></p><?php endif; ?><p>Внесете ги точните податоци добиени за разговорот. За модераторскиот простор годината и категоријата се 0; за другите простори внесете ја активната година, а категорија и регион само за комисија. Ова барање не отвора пристап до пораките.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><?php wp_nonce_field('zmp_admin_message_review_request_moderator'); ?><input type="hidden" name="action" value="zmp_admin_message_review_request"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><label>Простор <select name="scope_type" required><option value="moderator">Модератори</option><option value="tester">Тестатори</option><option value="organizer">Организатори</option><option value="commission">Комисија</option></select></label><label>Идентификатор на година <input type="number" name="season_id" min="0" required></label><label>Идентификатор на категорија <input type="number" name="category_id" min="0" required></label><label>Ознака на регион (празно за национален простор) <input type="text" name="region_key" maxlength="30"></label><label>Идентификатор на разговор <input type="number" name="thread_id" min="1" required></label><label>Причина (20–1.000 знаци) <textarea name="reason" minlength="20" maxlength="1000" required></textarea></label><button type="submit" class="zmp-btn zmp-btn-secondary">Испрати барање</button></form></details><?php endif; ?>
            <?php if (!empty($_GET['zmp_role_message_sent'])): ?><p class="zmp-alert zmp-alert-success" role="status">Пораката е зачувана.</p><?php endif; ?>
            <?php if (!empty($_GET['zmp_retention_updated'])): ?><p class="zmp-alert zmp-alert-success" role="status">Барањето за порано бришење е зачувано и ревизиски е запишано.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_retention_error']) && is_scalar($_GET['zmp_retention_error']) && sanitize_key(wp_unslash($_GET['zmp_retention_error'])) === 'deadline'): ?><p class="zmp-alert zmp-alert-warning" role="alert">Внесете валиден датум и време за порано бришење, или изберете „Побарај бришење веднаш“.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_retention_error']) && is_scalar($_GET['zmp_retention_error']) && sanitize_key(wp_unslash($_GET['zmp_retention_error'])) === 'deadline_past'): ?><p class="zmp-alert zmp-alert-warning" role="alert">Избраниот датум веќе поминал. За моментално започнување на бришењето изберете „Побарај бришење веднаш“.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_retention_error']) && is_scalar($_GET['zmp_retention_error']) && sanitize_key(wp_unslash($_GET['zmp_retention_error'])) === 'deadline_not_shorter'): ?><p class="zmp-alert zmp-alert-warning" role="alert">Избраниот рок не скратува ниту еден постоен рок. Изберете порано време.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_retention_error']) && is_scalar($_GET['zmp_retention_error']) && sanitize_key(wp_unslash($_GET['zmp_retention_error'])) === 'reason'): ?><p class="zmp-alert zmp-alert-warning" role="alert">Образложението мора да содржи од 20 до 1.000 знаци.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_admin_review_request']) && is_scalar($_GET['zmp_admin_review_request']) && sanitize_key(wp_unslash($_GET['zmp_admin_review_request'])) === 'sent'): ?><p class="zmp-alert zmp-alert-success" role="status">Барањето за ограничен преглед е испратено.</p><?php endif; ?>
            <?php if (isset($_GET['zmp_review_notice']) && is_scalar($_GET['zmp_review_notice']) && sanitize_key(wp_unslash($_GET['zmp_review_notice'])) === 'read'): ?><p class="zmp-alert zmp-alert-success" role="status">Известувањето е означено како прочитано.</p><?php endif; ?>
            <?php if ($review_notices): ?><aside class="zmp-alert zmp-alert-warning" role="status" aria-label="Известувања за преглед на разговори"><strong>Известувања за ограничен преглед</strong><?php foreach ($review_notices as $notice): ?><div class="zmp-review-notice"><p>Администратор прегледал разговор по образложено барање · <?php echo esc_html($notice->created_at); ?></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-inline-form"><input type="hidden" name="action" value="zmp_role_review_notice_ack"><input type="hidden" name="notice_id" value="<?php echo (int) $notice->id; ?>"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><?php wp_nonce_field('zmp_role_review_notice_ack_' . (int) $notice->id); ?><button type="submit" class="zmp-btn zmp-btn-secondary">Означи како прочитано</button></form></div><?php endforeach; ?></aside><?php endif; ?>
            <?php if ($scope === 'commission'): ?><form method="get" class="zmp-filter-bar"><?php if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])): ?><input type="hidden" name="zmp_portal_tab" value="<?php echo esc_attr(sanitize_key(wp_unslash($_GET['zmp_portal_tab']))); ?>"><?php endif; ?><input type="hidden" name="zmp_commission_tab" value="messenger"><label>Комисија <select name="zmp_room"><?php foreach ($rooms as $room): ?><option value="<?php echo (int) $room->category_id . ':' . esc_attr($room->region_key); ?>" <?php selected($room_key, $room->category_id . ':' . $room->region_key); ?>><?php echo esc_html($room->label); ?></option><?php endforeach; ?></select></label><button class="zmp-btn zmp-btn-secondary">Отвори</button></form><?php endif; ?>
            <form method="get" class="zmp-filter-bar"><?php if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])): ?><input type="hidden" name="zmp_portal_tab" value="<?php echo esc_attr(sanitize_key(wp_unslash($_GET['zmp_portal_tab']))); ?>"><?php endif; ?><input type="hidden" name="<?php echo esc_attr(array('moderator'=>'zmp_moderator_tab','tester'=>'zmp_testator_tab','organizer'=>'zmp_organizer_tab','commission'=>'zmp_commission_tab')[$scope]); ?>" value="messenger"><?php if ($scope === 'commission'): ?><input type="hidden" name="zmp_message_category" value="<?php echo (int) $category_id; ?>"><input type="hidden" name="zmp_message_region" value="<?php echo esc_attr($requested_region); ?>"><?php endif; ?><label>Пребарај пораки <input type="search" name="zmp_messenger_search" maxlength="100" value="<?php echo esc_attr($query); ?>"></label><button class="zmp-btn zmp-btn-secondary">Пребарај</button></form>
            <p><a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'zmp_role_message_export','scope_type'=>$scope,'category_id'=>$category_id,'region_key'=>$requested_region), admin_url('admin-post.php')), self::NONCE . '_export')); ?>">Извези пораки во CSV</a></p>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form zmp-role-messenger__compose"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_role_message"><input type="hidden" name="zmp_role_message_action" value="send"><input type="hidden" name="scope_type" value="<?php echo esc_attr($scope); ?>"><input type="hidden" name="category_id" value="<?php echo (int) $category_id; ?>"><input type="hidden" name="region_key" value="<?php echo esc_attr($requested_region); ?>"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><label>Наслов на разговор <input name="subject" maxlength="190" required></label><label>Порака <textarea name="message" maxlength="5000" rows="3" required></textarea></label><label>Приватен прилог (PDF, JPG или PNG; најмногу 5 MB) <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></label><button type="submit" class="zmp-btn zmp-btn-primary">Започни разговор</button></form>
            <?php if (!$threads): ?><p>Нема пораки што одговараат на пребарувањето.</p><?php endif; ?>
            <?php foreach ($threads as $thread_id => $items): ?><article class="zmp-card zmp-role-messenger__thread"><h4><?php echo esc_html($items[0]->subject ?: 'Разговор #' . $thread_id); ?> <small>· <?php echo esc_html($items[0]->created_at); ?></small></h4>
                <?php foreach ($items as $item): ?><div class="zmp-role-messenger__message"><strong><?php echo esc_html($item->display_name ?: 'Корисник'); ?></strong> <time><?php echo esc_html($item->created_at); ?></time><p><?php echo nl2br(esc_html($item->message)); ?></p><?php if (!empty($item->attachment_id)): ?><p><a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'zmp_role_message_attachment','id'=>(int) $item->attachment_id), admin_url('admin-post.php')), 'zmp_role_message_attachment_' . (int) $item->attachment_id)); ?>">Преземи приватен прилог: <?php echo esc_html($item->attachment_name); ?></a></p><?php endif; ?></div><?php endforeach; ?>
                <details><summary>Одговори</summary><form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><input type="hidden" name="action" value="zmp_role_message"><input type="hidden" name="zmp_role_message_action" value="send"><input type="hidden" name="scope_type" value="<?php echo esc_attr($scope); ?>"><input type="hidden" name="category_id" value="<?php echo (int) $category_id; ?>"><input type="hidden" name="region_key" value="<?php echo esc_attr($requested_region); ?>"><input type="hidden" name="thread_id" value="<?php echo (int) $thread_id; ?>"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><?php wp_nonce_field(self::NONCE); ?><label>Одговор <textarea name="message" maxlength="5000" required></textarea></label><label>Приватен прилог (PDF, JPG или PNG; најмногу 5 MB) <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></label><button type="submit" class="zmp-btn zmp-btn-secondary">Испрати одговор</button></form></details>
            <?php if ($scope === 'moderator' && self::user_has_role_and_cap('zmp_moderator', 'zmp_moderator_cap')): ?><details><summary>Постави порано бришење</summary><p>Рокот може само да се скрати. Барањето за моментално бришење е неповратно; содржината и приватните прилози се отстрануваат преку ограничена редица, а ревизиски запис останува.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><input type="hidden" name="action" value="zmp_role_retention_deadline"><input type="hidden" name="thread_id" value="<?php echo (int) $thread_id; ?>"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><?php wp_nonce_field('zmp_role_retention_deadline_' . (int) $thread_id); ?><label>Порано избран краен рок <input type="datetime-local" name="deadline" required></label><label>Образложение (20–1.000 знаци) <textarea name="reason" minlength="20" maxlength="1000" required></textarea></label><button type="submit" class="zmp-btn zmp-btn-secondary">Зачувај порано бришење</button> <button type="submit" name="delete_now" value="1" class="zmp-btn zmp-btn-secondary" formnovalidate>Побарај бришење веднаш</button></form></details><?php endif; ?>
                <?php if (self::can_request_room_review($scope, $season_id, $category_id, $requested_region)): ?><details><summary>Побарај ограничен администраторски преглед</summary><p>Барањето се однесува само на овој разговор. Причината ќе ја види администраторот; по преглед, учесниците во разговорот добиваат известување.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><input type="hidden" name="action" value="zmp_admin_message_review_request"><input type="hidden" name="scope_type" value="<?php echo esc_attr($scope); ?>"><input type="hidden" name="season_id" value="<?php echo (int) $season_id; ?>"><input type="hidden" name="category_id" value="<?php echo (int) $category_id; ?>"><input type="hidden" name="region_key" value="<?php echo esc_attr($requested_region); ?>"><input type="hidden" name="thread_id" value="<?php echo (int) $thread_id; ?>"><input type="hidden" name="return_url" value="<?php echo esc_url($page_url); ?>"><?php wp_nonce_field('zmp_admin_message_review_request_' . (int) $thread_id); ?><label>Причина (20–1.000 знаци) <textarea name="reason" minlength="20" maxlength="1000" required></textarea></label><button type="submit" class="zmp-btn zmp-btn-secondary">Испрати барање</button></form></details><?php endif; ?>
            </article><?php endforeach; ?>
            <?php if ($cursor_thread || $next_page_url): ?><nav aria-label="Страници со пораки" class="zmp-filter-bar"><?php if ($cursor_thread): ?><a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url($first_page_url); ?>">Почеток на пораките</a><?php endif; ?><?php if ($next_page_url): ?><a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url($next_page_url); ?>">Следни пораки</a><?php endif; ?></nav><?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }

    private static function attachment_row($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT a.* FROM {$wpdb->prefix}zmp_role_message_attachments a JOIN {$wpdb->prefix}zmp_role_messages m ON m.id=a.message_id AND m.scope_type=a.scope_type AND m.season_id=a.season_id AND m.category_id=a.category_id AND m.region_key=a.region_key WHERE a.id=%d", absint($id)));
    }

    public static function handle_download() {
        if (!is_user_logged_in()) { wp_die('Најавете се за преземање.', '', array('response'=>403)); }
        $id = isset($_GET['id']) && is_scalar($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        check_admin_referer('zmp_role_message_attachment_' . $id);
        $row = self::attachment_row($id);
        if (!$row || !self::can_access_room($row->scope_type, (int) $row->season_id, (int) $row->category_id, (string) $row->region_key)) { wp_die('Немате пристап до прилогот.', '', array('response'=>403)); }
        if (!preg_match('/^[a-f0-9]{48}\.(pdf|jpg|jpeg|png)$/', (string) $row->file_key)) { wp_die('Прилогот не е достапен.', '', array('response'=>404)); }
        $dir = self::store_directory((string) $row->store_hash, false);
        $path = is_wp_error($dir) ? '' : trailingslashit($dir) . $row->file_key;
        $real = $path ? realpath($path) : false;
        if (!$real || self::path_is_inside($real, ABSPATH) || !is_file($real) || (int) filesize($real) !== (int) $row->file_size || !hash_equals((string) $row->sha256, (string) hash_file('sha256', $real))) { wp_die('Прилогот не помина проверка на приватност или интегритет.', '', array('response'=>404)); }
        nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Type: ' . sanitize_mime_type($row->mime_type)); header('Content-Length: ' . filesize($real)); header('Content-Disposition: attachment; filename="' . rawurlencode($row->original_name) . '"'); readfile($real); exit;
    }

    private static function csv_cell($value) {
        $value = (string) $value;
        if (class_exists('ZMP_Csv_Safety') && is_callable(array('ZMP_Csv_Safety','safe_cell'))) { return ZMP_Csv_Safety::safe_cell($value); }
        if (preg_match('/^[\x00-\x20]*[=+@-]/u', $value)) { $value = "'" . $value; }
        return $value;
    }

    public static function handle_export() {
        if (!is_user_logged_in()) { wp_die('Најавете се за извоз.', '', array('response'=>403)); }
        check_admin_referer(self::NONCE . '_export');
        $scope = isset($_GET['scope_type']) && is_scalar($_GET['scope_type']) ? sanitize_key(wp_unslash($_GET['scope_type'])) : '';
        $tuple = self::room_scope($scope);
        if ($tuple[0] < 0 || ($scope !== 'moderator' && !$tuple[0])) { wp_die('Нема активен простор.', '', array('response'=>400)); }
        $season_id = (int) $tuple[0];
        $category_id = $scope === 'commission' && isset($_GET['category_id']) ? absint(wp_unslash($_GET['category_id'])) : 0;
        $raw_region = $scope === 'commission' ? wp_unslash($_GET['region_key'] ?? '') : '';
        $region_key = is_scalar($raw_region) ? sanitize_key((string) $raw_region) : '';
        if (!self::can_access_room($scope, $season_id, $category_id, $region_key)) { wp_die('Немате пристап до овој извоз.', '', array('response'=>403)); }
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT m.id,m.thread_id,m.sender_id,u.display_name,m.subject,m.message,m.created_at FROM {$wpdb->prefix}zmp_role_messages m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.scope_type=%s AND m.season_id=%d AND m.category_id=%d AND m.region_key=%s ORDER BY m.thread_id,m.id LIMIT 10000", $scope, $season_id, $category_id, $region_key), ARRAY_A);
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="zmp-messenger-' . sanitize_file_name($scope) . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('message_id','thread_id','sender_id','sender','subject','message','created_at'));
        foreach ((array) $rows as $row) { fputcsv($out, class_exists('ZMP_Csv_Safety') ? ZMP_Csv_Safety::safe_row(array_values($row)) : array_map(array(__CLASS__, 'csv_cell'), array_values($row))); }
        fclose($out); exit;
    }
}



