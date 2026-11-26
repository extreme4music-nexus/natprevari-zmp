<?php
if (!defined('ABSPATH')) { exit; }

/** Additive persistence for teacher subject proposals, audit events and canonical aliases. */
class ZMP_Mentor_Subject_Proposals {
    private static $schema_compatibility_cache = null;

    private static function proposal_table() { global $wpdb; return $wpdb->prefix . 'zmp_subject_proposals'; }
    private static function event_table() { global $wpdb; return $wpdb->prefix . 'zmp_subject_proposal_events'; }
    private static function alias_table() { global $wpdb; return $wpdb->prefix . 'zmp_subject_aliases'; }

    /** Create all proposal tables and fail closed unless every required column and index exists. */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE " . self::proposal_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proposed_name VARCHAR(190) NOT NULL,
            proposal_key CHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            proposer_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            decided_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            decided_at DATETIME NULL,
            canonical_subject_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY proposal_key (proposal_key),
            KEY status_created (status,created_at),
            KEY proposer_status (proposer_id,status),
            KEY canonical_subject_id (canonical_subject_id)
        ) $collate;");
        dbDelta("CREATE TABLE " . self::event_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proposal_id BIGINT(20) UNSIGNED NOT NULL,
            event_type VARCHAR(30) NOT NULL,
            actor_id BIGINT(20) UNSIGNED NOT NULL,
            event_at DATETIME NOT NULL,
            event_data LONGTEXT NOT NULL,
            PRIMARY KEY  (id),
            KEY proposal_event_time (proposal_id,event_at),
            KEY actor_event_time (actor_id,event_at),
            KEY event_type (event_type)
        ) $collate;");
        dbDelta("CREATE TABLE " . self::alias_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            alias_name VARCHAR(190) NOT NULL,
            alias_key CHAR(64) NOT NULL,
            canonical_subject_id BIGINT(20) UNSIGNED NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY alias_key (alias_key),
            KEY canonical_subject_id (canonical_subject_id)
        ) $collate;");

        self::$schema_compatibility_cache = null;
        return self::schema_is_compatible();
    }

    /** Create or reuse a teacher's pending proposal and its submission audit event. */
    public static function submit($user_id, $name) {
        global $wpdb;
        $user_id = self::positive_id($user_id);
        $user = $user_id ? get_userdata($user_id) : false;
        if (!$user_id || $user_id !== get_current_user_id() || !is_user_logged_in()
            || !$user || !ZMP_Mentor_School_Profile::is_teacher($user_id)) {
            self::debug_event('warning', 'Subject proposal submission denied.', array('error_code'=>'forbidden'));
            return self::error('forbidden', 'Само најавен наставник може да предложи предмет.');
        }
        $normalized = self::normalize_name($name);
        if (is_wp_error($normalized)) { self::debug_event('warning', 'Subject proposal validation failed.', array('error_code'=>$normalized->get_error_code())); return $normalized; }
        list($clean_name, $key) = $normalized;

        if (!self::schema_is_compatible()) { return self::schema_not_ready('submit'); }

        if (!self::transaction_start()) { return self::error('database_error', 'Предлогот не може безбедно да започне.'); }
        $existing = $wpdb->get_row($wpdb->prepare(
            'SELECT id,status,proposer_id FROM ' . self::proposal_table() . ' WHERE proposal_key=%s LIMIT 1 FOR UPDATE',
            $key
        ));
        if ($existing === null && !empty($wpdb->last_error)) {
            self::transaction_finish(false);
            return self::schema_not_ready('submit');
        }
        if ($existing) {
            self::transaction_finish(false);
            if ($existing->status === 'pending') {
                if ((int) $existing->proposer_id === $user_id) { return (int) $existing->id; }
                return self::error('proposal_pending', 'Овој предмет веќе чека одлука.');
            }
            return self::error('proposal_already_decided', 'Овој предмет веќе е разгледан.');
        }

        $created_at = current_time('mysql');
        $inserted = $wpdb->insert(self::proposal_table(), array(
            'proposed_name'=>$clean_name,
            'proposal_key'=>$key,
            'status'=>'pending',
            'proposer_id'=>$user_id,
            'created_at'=>$created_at,
            'decided_by'=>0,
            'decided_at'=>null,
            'canonical_subject_id'=>0,
        ));
        if (!$inserted) {
            // A concurrent insert may have won the normalized-key unique index.
            $winner = $wpdb->get_row($wpdb->prepare(
                'SELECT id,status,proposer_id FROM ' . self::proposal_table() . ' WHERE proposal_key=%s LIMIT 1 FOR UPDATE',
                $key
            ));
            self::transaction_finish(false);
            if ($winner === null && !empty($wpdb->last_error)) { return self::schema_not_ready('submit'); }
            if ($winner && $winner->status === 'pending') {
                if ((int) $winner->proposer_id === $user_id) { return (int) $winner->id; }
                return self::error('proposal_pending', 'Овој предмет веќе чека одлука.');
            }
            return self::error('database_error', 'Предлогот не можеше да се зачува.');
        }
        $proposal_id = (int) $wpdb->insert_id;
        if ($proposal_id < 1 || !self::append_event($proposal_id, 'submitted', $user_id, $created_at, array('proposed_name'=>$clean_name))) {
            self::transaction_finish(false);
            return self::error('database_error', 'Предлогот не можеше да се евидентира.');
        }
        if (!self::transaction_finish(true)) { return self::error('database_error', 'Предлогот не можеше да се потврди.'); }
        self::debug_event('info', 'Subject proposal submitted.', array('proposal_id'=>$proposal_id,'actor_user_id'=>$user_id,'outcome'=>'pending'));
        return $proposal_id;
    }

    /** Decide a pending proposal using an exact active President and append an audit event. */
    public static function decide($president_id, $proposal_id, $decision, $canonical_subject_id = 0) {
        global $wpdb;
        $president_id = self::positive_id($president_id);
        if (!$president_id || $president_id !== get_current_user_id() || !is_user_logged_in() || !self::is_active_president($president_id)) {
            self::debug_event('warning', 'Subject proposal decision denied.', array('error_code'=>'forbidden'));
            return self::error('forbidden', 'Само актуелниот претседател со активен мандат може да одлучува.');
        }
        $proposal_id = self::positive_id($proposal_id);
        if (!$proposal_id || !is_string($decision) || !in_array($decision, array('approve_new','approve_alias','reject'), true)) {
            return self::error('invalid_decision', 'Одлуката или предлогот не е валиден.');
        }
        $canonical_subject_id = self::positive_id($canonical_subject_id, true);
        if ($decision === 'approve_alias' && !$canonical_subject_id) {
            return self::error('canonical_subject_required', 'За синоним изберете одобрен предмет.');
        }
        if ($decision !== 'approve_alias' && $canonical_subject_id) {
            return self::error('invalid_canonical_subject', 'Оваа одлука не прифаќа избран предмет.');
        }
        if (!self::schema_is_compatible()) { return self::schema_not_ready('decide'); }
        if (!self::transaction_start()) { return self::error('database_error', 'Одлуката не може безбедно да започне.'); }

        $proposal = $wpdb->get_row($wpdb->prepare(
            'SELECT id,proposed_name,proposal_key,status FROM ' . self::proposal_table() . ' WHERE id=%d LIMIT 1 FOR UPDATE',
            $proposal_id
        ));
        if ($proposal === null && !empty($wpdb->last_error)) {
            self::transaction_finish(false);
            return self::schema_not_ready('decide');
        }
        if (!$proposal) {
            self::transaction_finish(false);
            return self::error('proposal_not_found', 'Предлогот не постои.');
        }
        if ($proposal->status !== 'pending') {
            self::transaction_finish(false);
            return self::error('proposal_already_decided', 'Предлогот веќе е разгледан.');
        }

        $canonical_id = 0;
        $event_type = 'rejected';
        if ($decision === 'approve_new') {
            $has_match = self::has_normalized_match($proposal->proposal_key);
            if (is_wp_error($has_match)) {
                self::transaction_finish(false);
                return $has_match;
            }
            if ($has_match) {
                self::transaction_finish(false);
                return self::error('subject_duplicate', 'Нормализираното име веќе постои како предмет или синоним; изберете синоним или одбијте го предлогот.');
            }
            $canonical_id = ZMP_Mentor_Subjects::create_approved_subject_for_president($proposal->proposed_name, $president_id);
            if (is_wp_error($canonical_id)) {
                self::transaction_finish(false);
                self::debug_event('warning', 'Subject creation failed during proposal approval.', array('proposal_id'=>$proposal_id,'actor_user_id'=>$president_id,'error_code'=>$canonical_id->get_error_code()));
                return $canonical_id;
            }
            $event_type = 'approved_new';
        } elseif ($decision === 'approve_alias') {
            $linked = self::link_alias($proposal, $president_id, $canonical_subject_id);
            if (is_wp_error($linked)) {
                self::transaction_finish(false);
                return $linked;
            }
            $canonical_id = $linked;
            $event_type = 'approved_alias';
        }

        $status = $decision === 'reject' ? 'rejected' : 'approved';
        $decided_at = current_time('mysql');
        $updated = $wpdb->update(self::proposal_table(), array(
            'status'=>$status,
            'decided_by'=>$president_id,
            'decided_at'=>$decided_at,
            'canonical_subject_id'=>$canonical_id,
        ), array('id'=>$proposal_id,'status'=>'pending'));
        if ($updated !== 1 || !self::append_event($proposal_id, $event_type, $president_id, $decided_at, array('canonical_subject_id'=>$canonical_id))) {
            self::transaction_finish(false);
            return self::error('database_error', 'Одлуката не можеше да се евидентира.');
        }
        if (!self::transaction_finish(true)) { return self::error('database_error', 'Одлуката не можеше да се потврди.'); }
        self::debug_event('info', 'Subject proposal decision committed.', array('proposal_id'=>$proposal_id,'actor_user_id'=>$president_id,'decision'=>$decision,'canonical_subject_id'=>$canonical_id,'outcome'=>'committed'));
        return $canonical_id;
    }

    /** Return the number of proposals still awaiting a President decision. */
    public static function pending_count() {
        global $wpdb;
        if (!self::schema_is_compatible()) { return self::schema_not_ready('pending_count'); }
        $count = $wpdb->get_var("SELECT COUNT(*) FROM " . self::proposal_table() . " WHERE status='pending'");
        if ($count === null || $count === false) { return self::schema_not_ready('pending_count'); }
        return max(0, (int) $count);
    }

    /** Return the canonical and alias rows matching a normalized proposal name for President review. */
    public static function find_normalized_matches($name) {
        global $wpdb;
        if (!self::schema_is_compatible()) { return self::schema_not_ready('find_matches'); }
        $normalized = self::normalize_name($name);
        if (is_wp_error($normalized)) { return array('canonical'=>array(),'aliases'=>array()); }
        $key = $normalized[1];
        $canonical = $wpdb->get_results($wpdb->prepare(
            "SELECT id,subject_name,status,is_active FROM {$wpdb->prefix}zmp_subject_catalog WHERE subject_key=%s ORDER BY id",
            $key
        ));
        $aliases = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id,a.alias_name,a.canonical_subject_id,c.subject_name AS canonical_name,c.status AS canonical_status,c.is_active AS canonical_active FROM " . self::alias_table() . " a LEFT JOIN {$wpdb->prefix}zmp_subject_catalog c ON c.id=a.canonical_subject_id WHERE a.alias_key=%s ORDER BY a.id",
            $key
        ));
        if (!is_array($canonical) || !is_array($aliases)) { return self::schema_not_ready('find_matches'); }
        return array(
            'canonical'=>$canonical,
            'aliases'=>$aliases,
        );
    }

    /** Lock normalized-key rows/ranges so an existing canonical or alias blocks approve-as-new. */
    private static function has_normalized_match($key) {
        global $wpdb;
        $canonical = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}zmp_subject_catalog WHERE subject_key=%s LIMIT 1 FOR UPDATE",
            $key
        ));
        if ($canonical === null && !empty($wpdb->last_error)) { return self::schema_not_ready('has_normalized_match'); }
        if ($canonical) { return true; }
        $alias = $wpdb->get_row($wpdb->prepare(
            'SELECT id FROM ' . self::alias_table() . ' WHERE alias_key=%s LIMIT 1 FOR UPDATE',
            $key
        ));
        if ($alias === null && !empty($wpdb->last_error)) { return self::schema_not_ready('has_normalized_match'); }
        return (bool) $alias;
    }

    private static function link_alias($proposal, $president_id, $canonical_subject_id) {
        global $wpdb;
        $canonical = $wpdb->get_row($wpdb->prepare(
            "SELECT id,subject_key FROM {$wpdb->prefix}zmp_subject_catalog WHERE id=%d AND status='approved' AND is_active=1 LIMIT 1",
            $canonical_subject_id
        ));
        if ($canonical === null && !empty($wpdb->last_error)) { return self::schema_not_ready('link_alias'); }
        if (!$canonical) { return self::error('canonical_subject_invalid', 'Избраниот предмет не е во одобрениот каталог.'); }
        if ((string) $canonical->subject_key === (string) $proposal->proposal_key) {
            return self::error('alias_equals_canonical', 'Името на предлогот веќе е канонскиот предмет.');
        }
        $key_collision = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}zmp_subject_catalog WHERE subject_key=%s AND id<>%d LIMIT 1",
            $proposal->proposal_key,
            $canonical_subject_id
        ));
        if ($key_collision === null && !empty($wpdb->last_error)) { return self::schema_not_ready('link_alias'); }
        if ($key_collision) { return self::error('alias_conflict', 'Името веќе постои како канонски предмет.'); }

        $existing = $wpdb->get_row($wpdb->prepare(
            'SELECT id,canonical_subject_id FROM ' . self::alias_table() . ' WHERE alias_key=%s LIMIT 1 FOR UPDATE',
            $proposal->proposal_key
        ));
        if ($existing === null && !empty($wpdb->last_error)) { return self::schema_not_ready('link_alias'); }
        if ($existing) {
            return (int) $existing->canonical_subject_id === $canonical_subject_id
                ? $canonical_subject_id
                : self::error('alias_conflict', 'Синонимот веќе е поврзан со друг предмет.');
        }
        $inserted = $wpdb->insert(self::alias_table(), array(
            'alias_name'=>$proposal->proposed_name,
            'alias_key'=>$proposal->proposal_key,
            'canonical_subject_id'=>$canonical_subject_id,
            'created_by'=>$president_id,
            'created_at'=>current_time('mysql'),
        ));
        if (!$inserted) {
            $winner = $wpdb->get_row($wpdb->prepare(
                'SELECT id,canonical_subject_id FROM ' . self::alias_table() . ' WHERE alias_key=%s LIMIT 1 FOR UPDATE',
                $proposal->proposal_key
            ));
            if ($winner === null && !empty($wpdb->last_error)) { return self::schema_not_ready('link_alias'); }
            if ($winner && (int) $winner->canonical_subject_id === $canonical_subject_id) { return $canonical_subject_id; }
            return self::error('alias_conflict', 'Синонимот не може да се поврзе со избраниот предмет.');
        }
        return $canonical_subject_id;
    }

    private static function append_event($proposal_id, $event_type, $actor_id, $event_at, $event_data) {
        global $wpdb;
        $encoded = function_exists('wp_json_encode') ? wp_json_encode($event_data) : json_encode($event_data);
        if (!is_string($encoded)) { return false; }
        return (bool) $wpdb->insert(self::event_table(), array(
            'proposal_id'=>(int) $proposal_id,
            'event_type'=>$event_type,
            'actor_id'=>(int) $actor_id,
            'event_at'=>$event_at,
            'event_data'=>$encoded,
        ));
    }

    private static function is_active_president($user_id) {
        $user = get_userdata($user_id);
        return $user && ZMP_Mentor_School_Profile::has_role_stack($user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && !current_user_can('manage_options')
            && class_exists('ZMP_President_Mandate')
            && is_callable(array('ZMP_President_Mandate','user_has_active_mandate'))
            && ZMP_President_Mandate::user_has_active_mandate($user_id);
    }

    private static function normalize_name($name) {
        if (!is_string($name)) { return self::error('invalid_subject', 'Името на предметот е невалидно.'); }
        $name = trim(preg_replace('/\s+/u', ' ', sanitize_text_field($name)));
        if ($name === '' || self::text_length($name) > 190) { return self::error('invalid_subject', 'Името на предметот е задолжително и мора да биде најмногу 190 знаци.'); }
        $normalized = remove_accents($name);
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));
        if (!is_string($normalized) || $normalized === '') { return self::error('invalid_subject', 'Името на предметот е невалидно.'); }
        return array($name, hash('sha256', $normalized));
    }

    private static function positive_id($value, $allow_zero = false) {
        if ($allow_zero && ($value === 0 || $value === '0')) { return 0; }
        if (!is_int($value) && (!is_string($value) || !preg_match('/^[1-9][0-9]*$/', $value))) { return 0; }
        $number = (int) $value;
        return $number > 0 && (string) $number === (string) $value ? $number : 0;
    }

    private static function transaction_start() {
        global $wpdb;
        return $wpdb->query('START TRANSACTION') !== false;
    }

    private static function transaction_finish($commit) {
        global $wpdb;
        if (!$commit) { $wpdb->query('ROLLBACK'); return true; }
        if ($wpdb->query('COMMIT') !== false) { return true; }
        $wpdb->query('ROLLBACK');
        return false;
    }

    private static function error($code, $message) { return new WP_Error($code, $message); }

    /** Fail closed with a generic state and fixed, privacy-safe diagnostic fields. */
    private static function schema_not_ready($operation) {
        $allowed_operations = array('submit','decide','pending_count','find_matches','has_normalized_match');
        $operation = in_array($operation, $allowed_operations, true) ? $operation : 'unknown';
        self::debug_event('error', 'Subject proposal schema is not ready.', array(
            'outcome'=>'fail_closed',
            'error_code'=>'schema_not_ready',
        ));
        return self::error('schema_not_ready', 'Регистарот на предмети моментално не е достапен поради проблем со шемата.');
    }

    /** Emit only high-level, privacy-safe subject-proposal diagnostics. */
    private static function debug_event($level, $message, $context = array()) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level, $message, $context, 'teacher_subject_proposals'); }
    }

    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string) $value, 'UTF-8'); }
        if (preg_match_all('/./us', (string) $value, $matches)) { return count($matches[0]); }
        return strlen((string) $value);
    }

    /** Readiness contract used by the shared schema marker commit gate. */
    public static function schema_is_compatible() {
        if (self::$schema_compatibility_cache !== null) { return self::$schema_compatibility_cache; }

        self::$schema_compatibility_cache = self::table_schema_is_ready(self::proposal_table(),
            array(
                'id'=>array('bigint unsigned',false,null,true),
                'proposed_name'=>array('varchar(190)',false,null,false),
                'proposal_key'=>array('char(64)',false,null,false),
                'status'=>array('varchar(20)',false,'pending',false),
                'proposer_id'=>array('bigint unsigned',false,null,false),
                'created_at'=>array('datetime',false,null,false),
                'decided_by'=>array('bigint unsigned',false,'0',false),
                'decided_at'=>array('datetime',true,null,false),
                'canonical_subject_id'=>array('bigint unsigned',false,'0',false),
            ),
            array('PRIMARY'=>array(array('id'),true),'proposal_key'=>array(array('proposal_key'),true),'status_created'=>array(array('status','created_at'),false),'proposer_status'=>array(array('proposer_id','status'),false),'canonical_subject_id'=>array(array('canonical_subject_id'),false)))
            && self::table_schema_is_ready(self::event_table(),
            array(
                'id'=>array('bigint unsigned',false,null,true),
                'proposal_id'=>array('bigint unsigned',false,null,false),
                'event_type'=>array('varchar(30)',false,null,false),
                'actor_id'=>array('bigint unsigned',false,null,false),
                'event_at'=>array('datetime',false,null,false),
                'event_data'=>array('longtext',false,null,false),
            ),
            array('PRIMARY'=>array(array('id'),true),'proposal_event_time'=>array(array('proposal_id','event_at'),false),'actor_event_time'=>array(array('actor_id','event_at'),false),'event_type'=>array(array('event_type'),false)))
            && self::table_schema_is_ready(self::alias_table(),
            array(
                'id'=>array('bigint unsigned',false,null,true),
                'alias_name'=>array('varchar(190)',false,null,false),
                'alias_key'=>array('char(64)',false,null,false),
                'canonical_subject_id'=>array('bigint unsigned',false,null,false),
                'created_by'=>array('bigint unsigned',false,null,false),
                'created_at'=>array('datetime',false,null,false),
            ),
            array('PRIMARY'=>array(array('id'),true),'alias_key'=>array(array('alias_key'),true),'canonical_subject_id'=>array(array('canonical_subject_id'),false)))
            && self::catalog_schema_is_compatible();
        return self::$schema_compatibility_cache;
    }

    /** Validate the canonical columns and unique keys used by proposal matching and approval. */
    private static function catalog_schema_is_compatible() {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_subject_catalog';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string) $found !== $table) { return false; }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . esc_sql($table) . '`');
        if (!is_array($columns)) { return false; }
        $required = array(
            'id'=>array('bigint unsigned',false,true),
            'subject_name'=>array('varchar(190)',false,false),
            'subject_key'=>array('char(64)',false,false),
            'status'=>array('varchar(20)',false,false),
            // normalize_column_type() deliberately removes integer display widths.
            'is_active'=>array('tinyint',false,false),
            'created_by'=>array('bigint unsigned',false,false),
            'approved_by'=>array('bigint unsigned',false,false),
            'approved_at'=>array('datetime',true,false),
            'created_at'=>array('datetime',false,false),
            'updated_at'=>array('datetime',false,false),
        );
        $found_columns = array();
        foreach ($columns as $column) {
            $get = static function($key) use ($column) { return is_object($column) ? ($column->$key ?? null) : ($column[$key] ?? null); };
            $name = (string) $get('Field');
            if ($name !== '') {
                $found_columns[$name] = array(
                    'type'=>self::normalize_column_type((string) $get('Type')),
                    'nullable'=>strtoupper((string) $get('Null')) === 'YES',
                    'auto_increment'=>stripos((string) $get('Extra'), 'auto_increment') !== false,
                );
            }
        }
        foreach ($required as $name=>$expected) {
            if (!isset($found_columns[$name])
                || $found_columns[$name]['type'] !== $expected[0]
                || $found_columns[$name]['nullable'] !== $expected[1]
                || $found_columns[$name]['auto_increment'] !== $expected[2]) { return false; }
        }

        $index_rows = $wpdb->get_results('SHOW INDEX FROM `' . esc_sql($table) . '`');
        if (!is_array($index_rows)) { return false; }
        $indexes = array();
        foreach ($index_rows as $row) {
            $get = static function($key) use ($row) { return is_object($row) ? ($row->$key ?? null) : ($row[$key] ?? null); };
            $name = (string) $get('Key_name');
            $sequence = (int) $get('Seq_in_index');
            if ($name === '' || $sequence < 1) { continue; }
            $indexes[$name]['columns'][$sequence] = (string) $get('Column_name');
            $indexes[$name]['unique'] = ((int) $get('Non_unique') === 0);
        }
        foreach (array(
            'PRIMARY'=>array(array('id'),true),
            'subject_key'=>array(array('subject_key'),true),
            'subject_status_active'=>array(array('status','is_active'),false),
        ) as $name=>$expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns']) !== $expected[0] || $indexes[$name]['unique'] !== $expected[1]) { return false; }
        }
        return true;
    }

    private static function table_schema_is_ready($table, $required_columns, $required_indexes) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string) $found !== (string) $table) { return false; }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . esc_sql($table) . '`');
        if (!is_array($columns)) { return false; }
        $found_columns = array();
        foreach ($columns as $column) {
            $get = static function($key) use ($column) { return is_object($column) ? ($column->$key ?? null) : ($column[$key] ?? null); };
            $name = (string) $get('Field');
            $default = $get('Default');
            if ($name !== '') { $found_columns[$name] = array(
                'type'=>self::normalize_column_type((string) $get('Type')),
                'nullable'=>strtoupper((string) $get('Null')) === 'YES',
                'default'=>$default === null ? null : (string) $default,
                'auto_increment'=>stripos((string) $get('Extra'), 'auto_increment') !== false,
            ); }
        }
        foreach ($required_columns as $name => $expected) {
            if (!isset($found_columns[$name])
                || $found_columns[$name]['type'] !== $expected[0]
                || $found_columns[$name]['nullable'] !== $expected[1]
                || $found_columns[$name]['default'] !== ($expected[2] === null ? null : (string) $expected[2])
                || $found_columns[$name]['auto_increment'] !== $expected[3]) { return false; }
        }

        $index_rows = $wpdb->get_results('SHOW INDEX FROM `' . esc_sql($table) . '`');
        if (!is_array($index_rows)) { return false; }
        $indexes = array();
        foreach ($index_rows as $row) {
            $get = static function($key) use ($row) { return is_object($row) ? ($row->$key ?? null) : ($row[$key] ?? null); };
            $key = (string) $get('Key_name');
            $sequence = (int) $get('Seq_in_index');
            if ($key === '' || $sequence < 1) { continue; }
            $indexes[$key]['columns'][$sequence] = (string) $get('Column_name');
            $indexes[$key]['unique'] = ((int) $get('Non_unique') === 0);
        }
        foreach ($required_indexes as $name => $expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns']) !== $expected[0] || $indexes[$name]['unique'] !== $expected[1]) { return false; }
        }
        return true;
    }

    /** Normalize integer display widths, which differ between MySQL and MariaDB versions. */
    private static function normalize_column_type($type) {
        $type = strtolower(trim(preg_replace('/\s+/', ' ', (string) $type)));
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)(?:\([0-9]+\))?( unsigned)?$/', $type, $matches)) {
            $base = $matches[1] === 'integer' ? 'int' : $matches[1];
            return $base . (!empty($matches[2]) ? ' unsigned' : '');
        }
        return $type;
    }
}




