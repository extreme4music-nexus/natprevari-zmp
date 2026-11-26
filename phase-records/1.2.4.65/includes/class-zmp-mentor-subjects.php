<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Canonical approved subjects and teacher-owned school/subject/grade assignments.
 * Subject proposals, approval UI and search are intentionally owned by phase .63.
 */
class ZMP_Mentor_Subjects {
    private static function catalog_table() { global $wpdb; return $wpdb->prefix . 'zmp_subject_catalog'; }
    private static function assignment_table() { global $wpdb; return $wpdb->prefix . 'zmp_mentor_school_subjects'; }
    private static function grade_table() { global $wpdb; return $wpdb->prefix . 'zmp_mentor_school_subject_grades'; }

    /** Additive, idempotent catalog and assignment schema plus legacy primary-school reconciliation. */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        if (!self::normalize_catalog_timestamps()) { return false; }
        dbDelta("CREATE TABLE " . self::catalog_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subject_name VARCHAR(190) NOT NULL,
            subject_key CHAR(64) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending' NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            approved_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            approved_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY subject_key (subject_key),
            KEY subject_status_active (status,is_active)
        ) $collate;");
        if (!self::enforce_catalog_timestamp_nullability()) { return false; }
        dbDelta("CREATE TABLE " . self::assignment_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            subject_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY school_subject (school_id,subject_id),
            KEY mentor_school (mentor_id,school_id),
            KEY subject_id (subject_id)
        ) $collate;");
        dbDelta("CREATE TABLE " . self::grade_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            assignment_id BIGINT(20) UNSIGNED NOT NULL,
            grade_key CHAR(64) NOT NULL,
            grade_label VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY assignment_grade (assignment_id,grade_key),
            KEY grade_key (grade_key)
        ) $collate;");

        if (!self::schema_is_ready()) { return false; }

        // ZMP_Mentor_Schools owns normalization and region derivation. Reconcile legacy
        // single-school mentor rows without touching any student/application snapshots.
        if (class_exists('ZMP_Mentor_Schools')) {
            $mentors = $wpdb->get_results("SELECT id,school_name,municipality,region FROM {$wpdb->prefix}zmp_mentors ORDER BY id");
            foreach ((array) $mentors as $mentor) { ZMP_Mentor_Schools::ensure_primary($mentor); }
        }
        return self::schema_is_ready();
    }

    /** Preserve catalog rows while satisfying the non-null timestamp contract before dbDelta alters columns. */
    private static function normalize_catalog_timestamps() {
        global $wpdb;
        $table = self::catalog_table();
        $wpdb->last_error = '';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string) $found !== (string) $table) { return $wpdb->last_error === ''; }

        $columns = $wpdb->get_col('SHOW COLUMNS FROM `' . esc_sql($table) . '`', 0);
        if (!is_array($columns) || $wpdb->last_error !== '') { return false; }
        foreach (array('created_at', 'updated_at') as $column) {
            if (!in_array($column, $columns, true)) { continue; }
            $result = $wpdb->query('UPDATE `' . esc_sql($table) . '` SET `' . $column . '`=CURRENT_TIMESTAMP WHERE `' . $column . '` IS NULL');
            if ($result === false || $wpdb->last_error !== '') { return false; }
        }
        return true;
    }

    /** Explicitly enforce NOT NULL after backfilling legacy NULLs; dbDelta may leave them nullable. */
    private static function enforce_catalog_timestamp_nullability() {
        global $wpdb;
        $table = self::catalog_table();
        $safe_table = esc_sql($table);
        $wpdb->last_error = '';
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . $safe_table . '`');
        if (!is_array($columns) || $wpdb->last_error !== '') { return false; }

        $nullable = array();
        foreach ($columns as $column) {
            $get = static function($key) use ($column) { return is_object($column) ? ($column->$key ?? null) : ($column[$key] ?? null); };
            $name = (string) $get('Field');
            if (in_array($name, array('created_at', 'updated_at'), true)) {
                $nullable[$name] = strtoupper((string) $get('Null')) === 'YES';
            }
        }
        if (!array_key_exists('created_at', $nullable) || !array_key_exists('updated_at', $nullable)) { return false; }
        if (!$nullable['created_at'] && !$nullable['updated_at']) { return true; }

        $wpdb->last_error = '';
        $result = $wpdb->query('ALTER TABLE `' . $safe_table . '` '
            . 'MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
            . 'MODIFY COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        if ($result === false || $wpdb->last_error !== '') { return false; }

        $wpdb->last_error = '';
        $verified = $wpdb->get_results('SHOW COLUMNS FROM `' . $safe_table . '`');
        if (!is_array($verified) || $wpdb->last_error !== '') { return false; }
        $not_null = array();
        foreach ($verified as $column) {
            $get = static function($key) use ($column) { return is_object($column) ? ($column->$key ?? null) : ($column[$key] ?? null); };
            $name = (string) $get('Field');
            if (in_array($name, array('created_at', 'updated_at'), true)) {
                $not_null[$name] = strtoupper((string) $get('Null')) === 'NO';
            }
        }
        return !empty($not_null['created_at']) && !empty($not_null['updated_at']);
    }

    /** Fail closed unless every required column and index is present with its required shape. */
    private static function schema_is_ready() {
        return self::table_schema_is_ready(self::catalog_table(),
            array('id','subject_name','subject_key','status','is_active','created_by','approved_by','approved_at','created_at','updated_at'),
            array('PRIMARY'=>array(array('id'),true),'subject_key'=>array(array('subject_key'),true),'subject_status_active'=>array(array('status','is_active'),false)))
            && self::table_schema_is_ready(self::assignment_table(),
            array('id','mentor_id','school_id','subject_id','created_at','updated_at'),
            array('PRIMARY'=>array(array('id'),true),'school_subject'=>array(array('school_id','subject_id'),true),'mentor_school'=>array(array('mentor_id','school_id'),false),'subject_id'=>array(array('subject_id'),false)))
            && self::table_schema_is_ready(self::grade_table(),
            array('id','assignment_id','grade_key','grade_label','created_at'),
            array('PRIMARY'=>array(array('id'),true),'assignment_grade'=>array(array('assignment_id','grade_key'),true),'grade_key'=>array(array('grade_key'),false)));
    }

    private static function table_schema_is_ready($table, $required_columns, $required_indexes) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string)$found !== (string)$table) { return false; }
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . esc_sql($table) . "`");
        if (!is_array($columns)) { return false; }
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
        foreach ($required_indexes as $name => $expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns']) !== $expected[0] || $indexes[$name]['unique'] !== $expected[1]) { return false; }
        }
        return true;
    }

    /** Register the secured teacher portal POST endpoint; WordPress profile saves are handled in ZMP_Support. */
    public static function register_hooks() {
        add_action('admin_post_zmp_save_teacher_assignments', array(__CLASS__, 'handle_admin_post'));
    }

    /** Portal endpoint: a teacher can post only for the currently authenticated account. */
    public static function handle_admin_post() {
        if (!is_user_logged_in() || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'zmp_save_teacher_assignments')) {
            wp_die('Безбедносната проверка не помина.', '', array('response'=>403));
        }
        $user_id = get_current_user_id();
        $payload = isset($_POST['assignments']) && is_array($_POST['assignments']) ? wp_unslash($_POST['assignments']) : array();
        if (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard') && !current_user_can('manage_options')) {
            wp_die('Немате дозвола да ги менувате наставничките предмети.', '', array('response'=>403));
        }
        $result = self::save_teacher_assignments($user_id, $payload);
        if (is_wp_error($result) && $result->get_error_code() === 'forbidden') {
            wp_die('Немате дозвола да ги менувате наставничките предмети.', '', array('response'=>403));
        }
        $status = is_wp_error($result)
            ? ($result->get_error_code() === 'schema_not_ready'
                ? 'schema_not_ready'
                : (in_array($result->get_error_code(), array('assignments_required','invalid_assignments','school_not_owned','grades_required','invalid_grade','duplicate_grade','subject_not_approved','school_assignments_required','teacher_profile_required'), true) ? 'assignments_invalid' : 'assignment_save_failed'))
            : 'assignments_saved';
        $return = add_query_arg('tab', 'schools', wp_get_referer() ?: home_url('/'));
        wp_safe_redirect(add_query_arg('msg', $status, $return));
        exit;
    }

    /** List only canonical subjects explicitly approved and enabled. */
    public static function approved_subjects() {
        global $wpdb;
        $wpdb->last_error = '';
        $rows = $wpdb->get_results("SELECT id,subject_name FROM " . self::catalog_table() . " WHERE status='approved' AND is_active=1 ORDER BY subject_name,id");
        if (!is_array($rows) || $wpdb->last_error !== '') {
            return self::catalog_not_ready();
        }
        return $rows;
    }

    /** Return a safe error distinct from a valid, empty catalog. */
    private static function catalog_not_ready() {
        if (class_exists('ZMP_Debug') && is_callable(array('ZMP_Debug', 'log'))) {
            ZMP_Debug::log('error', 'Approved subject catalog is unavailable.', array(
                'outcome'=>'fail_closed',
                'error_code'=>'schema_not_ready',
            ), 'teacher_subject_proposals');
        }
        return new WP_Error('schema_not_ready', __('Регистарот на предмети моментално не е достапен поради проблем со шемата.', 'zmp-competitions'));
    }

    /**
     * Read a teacher's assignments, keyed as school_id => subject_id => [grade/year labels].
     * Admins may inspect a selected profile; teachers may read only their own profile.
     */
    public static function teacher_assignments($user_id) {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id || !self::can_access_teacher($user_id)) { return array(); }
        $mentor = self::mentor_for_user($user_id);
        if (!$mentor) { return array(); }
        self::ensure_legacy_primary($mentor);
        $sql = $wpdb->prepare(
            "SELECT a.school_id,a.subject_id,g.grade_label
             FROM " . self::assignment_table() . " a
             JOIN " . self::catalog_table() . " c ON c.id=a.subject_id AND c.status='approved' AND c.is_active=1
             LEFT JOIN " . self::grade_table() . " g ON g.assignment_id=a.id
             JOIN {$wpdb->prefix}zmp_mentor_schools s ON s.id=a.school_id AND s.mentor_id=a.mentor_id AND s.is_active=1
             WHERE a.mentor_id=%d ORDER BY a.school_id,c.subject_name,g.grade_key",
            (int) $mentor->id
        );
        $rows = $wpdb->get_results($sql);
        $result = array();
        foreach ((array) $rows as $row) {
            $school_id = (int) $row->school_id;
            $subject_id = (int) $row->subject_id;
            if (!isset($result[$school_id])) { $result[$school_id] = array(); }
            if (!isset($result[$school_id][$subject_id])) { $result[$school_id][$subject_id] = array(); }
            if ($row->grade_label !== null) { $result[$school_id][$subject_id][] = (string) $row->grade_label; }
        }
        return $result;
    }

    /**
     * Atomically replace assignments only for listed, active schools owned by $user_id.
     * Payload contract: school_id => subject_id => array of grade/year strings.
     * Returns true on success or WP_Error on invalid, unauthorized, or failed writes.
     */
    public static function save_teacher_assignments($user_id, $payload, $within_transaction = false) {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id || !self::can_access_teacher($user_id, true)) {
            return new WP_Error('forbidden', 'Немате дозвола да ги менувате наставничките предмети.');
        }
        $mentor = self::mentor_for_user($user_id);
        if (!$mentor) { return new WP_Error('teacher_profile_required', 'Прво зачувајте го профилот на наставникот.'); }
        // Resolve catalog availability before legacy-primary reconciliation can
        // write a school row. A valid empty catalog remains a normal result.
        $approved_catalog = self::approved_subjects();
        if (is_wp_error($approved_catalog)) { return $approved_catalog; }
        $approved_subject_ids = array();
        foreach ($approved_catalog as $approved_subject) {
            $approved_id = is_object($approved_subject) ? absint($approved_subject->id ?? 0) : absint($approved_subject['id'] ?? 0);
            if ($approved_id) { $approved_subject_ids[$approved_id] = true; }
        }
        self::ensure_legacy_primary($mentor);
        if (!is_array($payload) || !$payload || count($payload) > 50) {
            return new WP_Error('assignments_required', 'Додајте предмет и најмалку едно одделение/година за секое училиште.');
        }

        $clean = array();
        $subject_ids = array();
        foreach ($payload as $school_key => $subjects) {
            $school_id = self::positive_integer_key($school_key);
            if (!$school_id || !is_array($subjects) || !$subjects || count($subjects) > 50) {
                return new WP_Error('invalid_assignments', 'Проверете ги училиштата и предметите во профилот.');
            }
            $school = ZMP_Mentor_Schools::owned_school((int) $mentor->id, $school_id);
            if (!$school || !ZMP_Regions::valid_region($school->region) || (empty($school->region_is_override) && ZMP_Regions::region_for_municipality($school->municipality) !== $school->region)) {
                return new WP_Error('school_not_owned', 'Избраното училиште не е активно или не припаѓа на вашиот профил.');
            }
            foreach ($subjects as $subject_key => $grades) {
                $subject_id = self::positive_integer_key($subject_key);
                if (!$subject_id || !is_array($grades) || !$grades || count($grades) > 20) {
                    return new WP_Error('grades_required', 'За секој предмет изберете најмалку едно одделение/година.');
                }
                $grade_map = array();
                foreach ($grades as $grade_value) {
                    if (!is_scalar($grade_value)) { return new WP_Error('invalid_grade', 'Одделението/годината е невалидна.'); }
                    $label = sanitize_key((string) $grade_value);
                    if (!preg_match('/^(grade_[1-9]|year_[1-4])$/', $label)) {
                        return new WP_Error('invalid_grade', 'Изберете важечко одделение или година.');
                    }
                    $key = self::grade_key($label);
                    if (isset($grade_map[$key])) { return new WP_Error('duplicate_grade', 'Одделението/годината е внесено повеќе пати.'); }
                    $grade_map[$key] = $label;
                }
                $clean[$school_id][$subject_id] = $grade_map;
                $subject_ids[$subject_id] = $subject_id;
            }
        }
        // Require a complete assignment collection for every active school owned by this teacher.
        $owned_schools = ZMP_Mentor_Schools::for_mentor((int) $mentor->id);
        $owned_ids = array_map('intval', array_column((array) $owned_schools, 'id'));
        sort($owned_ids);
        $submitted_ids = array_map('intval', array_keys($clean));
        sort($submitted_ids);
        if (!$owned_ids || $owned_ids !== $submitted_ids) {
            return new WP_Error('school_assignments_required', 'Зачувајте предмет и најмалку едно одделение/година за секое активно училиште.');
        }
        $subject_ids = array_values($subject_ids);
        if (!$subject_ids) { return new WP_Error('assignments_required', 'Изберете најмалку еден одобрен предмет.'); }
        $approved_count = 0;
        foreach ($subject_ids as $subject_id) {
            if (isset($approved_subject_ids[(int)$subject_id])) { $approved_count++; }
        }
        if ($approved_count !== count($subject_ids)) {
            return new WP_Error('subject_not_approved', 'Еден или повеќе предмети не се во одобрениот каталог.');
        }

        if (!$within_transaction && $wpdb->query('START TRANSACTION') === false) { return new WP_Error('database_error', 'Промените не можеа безбедно да започнат.'); }
        foreach ($clean as $school_id => $subjects) {
            $assignment_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM " . self::assignment_table() . ' WHERE mentor_id=%d AND school_id=%d',
                (int) $mentor->id,
                (int) $school_id
            ));
            if ($assignment_ids) {
                $ids = array_map('intval', (array) $assignment_ids);
                $in = implode(',', $ids);
                if ($wpdb->query("DELETE FROM " . self::grade_table() . " WHERE assignment_id IN ($in)") === false || $wpdb->query("DELETE FROM " . self::assignment_table() . " WHERE id IN ($in) AND mentor_id=" . (int) $mentor->id) === false) {
                    if (!$within_transaction) { $wpdb->query('ROLLBACK'); }
                    return new WP_Error('database_error', 'Промените не можеа да се зачуваат.');
                }
            }
            foreach ($subjects as $subject_id => $grade_map) {
                $ok = $wpdb->insert(self::assignment_table(), array(
                    'mentor_id' => (int) $mentor->id,
                    'school_id' => (int) $school_id,
                    'subject_id' => (int) $subject_id,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ));
                if (!$ok) {
                    if (!$within_transaction) { $wpdb->query('ROLLBACK'); }
                    return new WP_Error('database_error', 'Предметите не можеа да се зачуваат.');
                }
                $assignment_id = (int) $wpdb->insert_id;
                foreach ($grade_map as $grade_key => $grade_label) {
                    if (!$wpdb->insert(self::grade_table(), array(
                        'assignment_id' => $assignment_id,
                        'grade_key' => $grade_key,
                        'grade_label' => $grade_label,
                        'created_at' => current_time('mysql'),
                    ))) {
                        if (!$within_transaction) { $wpdb->query('ROLLBACK'); }
                        return new WP_Error('database_error', 'Одделенијата/годините не можеа да се зачуваат.');
                    }
                }
            }
        }
        if (!$within_transaction && $wpdb->query('COMMIT') === false) {
            if (!$within_transaction) { $wpdb->query('ROLLBACK'); }
            return new WP_Error('database_error', 'Промените не можеа да се потврдат.');
        }
        return true;
    }

    /**
     * Canonical catalog seam for .63 after its approved workflow records a decision.
     * No teacher-facing call can insert or approve a subject in .62.
     */
    public static function ensure_approved_subject($subject_name, $actor_user_id = 0) {
        $actor_user_id = $actor_user_id ? absint($actor_user_id) : get_current_user_id();
        if (!$actor_user_id || $actor_user_id !== get_current_user_id() || !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Само овластен администратор може да подготви одобрен предмет.');
        }
        return self::save_approved_subject($subject_name, $actor_user_id);
    }

    /** Decision-service seam; a site administrator or forged role alone cannot approve. */
    public static function ensure_approved_subject_for_president($subject_name, $actor_user_id) {
        // Retain the exact active-President actor check through the strict create-only
        // seam. Existing normalized rows require a proposal decision (alias or reject)
        // and must never be silently reactivated here.
        return self::create_approved_subject_for_president($subject_name, $actor_user_id);
    }

    /** Insert a new President-approved canonical row; a unique-key winner is a duplicate, not reuse. */
    public static function create_approved_subject_for_president($subject_name, $actor_user_id) {
        global $wpdb;
        $actor_user_id = absint($actor_user_id);
        $user = $actor_user_id ? get_userdata($actor_user_id) : false;
        $roles = $user && isset($user->roles) ? array_values((array) $user->roles) : array();
        if (!$actor_user_id || $actor_user_id !== get_current_user_id() || !is_user_logged_in()
            || !ZMP_Mentor_School_Profile::has_role_stack($actor_user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer')) || current_user_can('manage_options')
            || !class_exists('ZMP_President_Mandate')
            || !is_callable(array('ZMP_President_Mandate','user_has_active_mandate'))
            || !ZMP_President_Mandate::user_has_active_mandate($actor_user_id)) {
            return new WP_Error('forbidden', 'Само актуелниот претседател со активен мандат може да одобри предмет.');
        }
        $name = sanitize_text_field((string) $subject_name);
        $name = trim(preg_replace('/\\s+/u', ' ', $name));
        if ($name === '' || self::text_length($name) > 190) { return new WP_Error('invalid_subject', 'Името на предметот е задолжително и мора да биде најмногу 190 знаци.'); }
        $key = self::subject_key($name);
        if (!$key) { return new WP_Error('invalid_subject', 'Името на предметот е невалидно.'); }
        $table = self::catalog_table();
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE subject_key=%s LIMIT 1 FOR UPDATE", $key));
        if ($existing) { return new WP_Error('subject_duplicate', 'Нормализираното име веќе постои во каталогот.'); }
        $now = current_time('mysql');
        $ok = $wpdb->insert($table, array('subject_name'=>$name,'subject_key'=>$key,'status'=>'approved','is_active'=>1,'created_by'=>$actor_user_id,'approved_by'=>$actor_user_id,'approved_at'=>$now,'created_at'=>$now,'updated_at'=>$now));
        if (!$ok) {
            $winner = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE subject_key=%s LIMIT 1 FOR UPDATE", $key));
            return $winner
                ? new WP_Error('subject_duplicate', 'Нормализираното име веќе постои во каталогот.')
                : new WP_Error('database_error', 'Предметот не можеше да се зачува.');
        }
        return (int) $wpdb->insert_id;
    }

    private static function save_approved_subject($subject_name, $actor_user_id) {
        global $wpdb;
        $name = sanitize_text_field((string) $subject_name);
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        if ($name === '' || self::text_length($name) > 190) { return new WP_Error('invalid_subject', 'Името на предметот е задолжително и мора да биде најмногу 190 знаци.'); }
        $key = self::subject_key($name);
        if (!$key) { return new WP_Error('invalid_subject', 'Името на предметот е невалидно.'); }
        $table = self::catalog_table();
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE subject_key=%s LIMIT 1 FOR UPDATE", $key));
        if ($existing) {
            $updated = $wpdb->update($table, array('subject_name'=>$name,'status'=>'approved','is_active'=>1,'approved_by'=>$actor_user_id,'approved_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')), array('id'=>(int)$existing->id));
            return $updated === false ? new WP_Error('database_error', 'Предметот не можеше да се одобри.') : (int)$existing->id;
        }
        $ok = $wpdb->insert($table, array('subject_name'=>$name,'subject_key'=>$key,'status'=>'approved','is_active'=>1,'created_by'=>$actor_user_id,'approved_by'=>$actor_user_id,'approved_at'=>current_time('mysql'),'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')));
        if (!$ok) {
            $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE subject_key=%s AND status='approved' AND is_active=1 LIMIT 1 FOR UPDATE", $key));
            return $id ?: new WP_Error('database_error', 'Предметот не можеше да се зачува.');
        }
        return (int) $wpdb->insert_id;
    }

    private static function can_access_teacher($user_id, $write = false) {
        $user_id = absint($user_id);
        if (!$user_id || !is_user_logged_in()) { return false; }
        $same_user = (int) get_current_user_id() === $user_id;
        if (!$same_user) { return current_user_can('manage_options') || current_user_can('edit_user', $user_id); }
        if (current_user_can('manage_options')) { return true; }
        $user = get_userdata($user_id);
        if (!$user) { return false; }
        $teacher = in_array('zmp_teacher', (array) $user->roles, true)
            || user_can($user_id, 'zmp_teacher_cap')
            || user_can($user_id, 'view_zmp_teacher_dashboard');
        return $teacher && (!$write || (current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard') || in_array('zmp_teacher', (array) $user->roles, true)));
    }
    private static function mentor_for_user($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d LIMIT 1", absint($user_id)));
    }

    private static function ensure_legacy_primary($mentor) {
        if ($mentor && class_exists('ZMP_Mentor_Schools') && !empty($mentor->school_name) && !empty($mentor->municipality)) {
            return ZMP_Mentor_Schools::ensure_primary($mentor);
        }
        return 0;
    }

    private static function positive_integer_key($value) {
        if (!is_int($value) && (!is_string($value) || !preg_match('/^[1-9][0-9]*$/', $value))) { return 0; }
        $number = (int) $value;
        return $number > 0 && (string) $number === (string) $value ? $number : 0;
    }

    private static function subject_key($name) {
        $normalized = remove_accents(trim((string) $name));
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);
        return $normalized !== '' ? hash('sha256', $normalized) : '';
    }

    private static function grade_key($label) {
        $normalized = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $label), 'UTF-8') : strtolower(trim((string) $label));
        return hash('sha256', preg_replace('/\s+/u', ' ', $normalized));
    }

    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string) $value, 'UTF-8'); }
        if (preg_match_all('/./us', (string) $value, $matches)) { return count($matches[0]); }
        return strlen((string) $value);
    }
}














