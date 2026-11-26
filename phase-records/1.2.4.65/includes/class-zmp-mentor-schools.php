<?php
if (!defined('ABSPATH')) { exit; }

/** Teacher-owned schools. Competition entries keep immutable school snapshots. */
class ZMP_Mentor_Schools {
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'zmp_mentor_schools';
        $collate = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(150) NOT NULL,
            region VARCHAR(30) NOT NULL,
            region_is_override TINYINT(1) NOT NULL DEFAULT 0,
            school_key CHAR(64) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0 NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            engagement_epoch BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY mentor_school (mentor_id,school_key),
            KEY mentor_active (mentor_id,is_active)
        ) $collate;");
        if (!self::schema_is_ready($table)) { self::debug_event('error','School engagement schema is not ready.',array('outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return false; }
        // Additive, repeatable migration: carry each existing primary school forward.
        $wpdb->last_error = '';
        $mentors = $wpdb->get_results("SELECT id,school_name,municipality,region FROM {$wpdb->prefix}zmp_mentors ORDER BY id");
        if (!is_array($mentors) || $wpdb->last_error !== '') { return false; }
        foreach ($mentors as $mentor) {
            $primary_id = self::ensure_primary($mentor);
            if (!$primary_id || !self::legacy_primary_is_ready($mentor, $primary_id)) { return false; }
        }
        $ready=self::schema_is_ready($table);
        self::debug_event($ready?'info':'error',$ready?'School engagement migration is ready.':'School engagement migration is not ready.',array('outcome'=>$ready?'ready':'fail_closed','error_code'=>$ready?'':'schema_not_ready'));
        return $ready;
    }

    /** Confirm that legacy-school reconciliation persisted the correct active primary row. */
    private static function legacy_primary_is_ready($mentor, $school_id) {
        global $wpdb;
        if (!$mentor || empty($mentor->id) || empty($mentor->school_name) || empty($mentor->municipality)) { return false; }
        $expected_key = self::school_key($mentor->school_name, $mentor->municipality);
        $wpdb->last_error = '';
        $school = $wpdb->get_row($wpdb->prepare(
            "SELECT id,school_name,municipality,region,region_is_override,school_key,is_primary,is_active FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d AND mentor_id=%d",
            (int)$school_id, (int)$mentor->id
        ));
        if (!$school || $wpdb->last_error !== ''
            || (int)$school->id !== (int)$school_id
            || (string)$school->school_key !== $expected_key
            || self::school_key($school->school_name, $school->municipality) !== $expected_key
            || (int)$school->is_primary !== 1 || (int)$school->is_active !== 1
            || !ZMP_Regions::valid_region($school->region)) { return false; }

        // A moderator-approved exception is valid only when explicitly marked.
        // Ordinary schools must still match the server-derived municipality region.
        if ((int)$school->region_is_override === 1) { return true; }
        $derived_region = ZMP_Regions::region_for_municipality($mentor->municipality);
        return $derived_region && ZMP_Regions::valid_region($derived_region)
            && (string)$school->region === (string)$derived_region;
    }

    /** Fail closed unless every required school column and index exists with the expected shape. */
    private static function schema_is_ready($table) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string)$found !== (string)$table) { return false; }
        $columns = $wpdb->get_results("SHOW COLUMNS FROM `" . esc_sql($table) . "`");
        if (!is_array($columns)) { return false; }
        $required_columns = array('id','mentor_id','school_name','municipality','region','region_is_override','school_key','is_primary','is_active','engagement_epoch','created_at','updated_at');
        $column_names = array();
        $column_shapes = array();
        foreach ($columns as $column) {
            $name = is_object($column) ? ($column->Field ?? '') : ($column['Field'] ?? '');
            if ($name !== '') {
                $column_names[(string)$name] = true;
                $column_shapes[(string)$name] = array(
                    'type'=>ZMP_Database::normalize_schema_type(is_object($column) ? ($column->Type ?? '') : ($column['Type'] ?? '')),
                    'null'=>strtoupper((string)(is_object($column) ? ($column->Null ?? '') : ($column['Null'] ?? ''))),
                    'default'=>(string)(is_object($column) ? ($column->Default ?? '') : ($column['Default'] ?? '')),
                );
            }
        }
        foreach ($required_columns as $name) { if (empty($column_names[$name])) { return false; } }
        if (($column_shapes['engagement_epoch']['type'] ?? '') !== ZMP_Database::normalize_schema_type('bigint(20) unsigned')
            || ($column_shapes['engagement_epoch']['null'] ?? '') !== 'NO'
            || ($column_shapes['engagement_epoch']['default'] ?? '') !== '1') { return false; }
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
        $required_indexes = array('PRIMARY'=>array(array('id'),true),'mentor_school'=>array(array('mentor_id','school_key'),true),'mentor_active'=>array(array('mentor_id','is_active'),false));
        foreach ($required_indexes as $name => $expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns']) !== $expected[0] || $indexes[$name]['unique'] !== $expected[1]) { return false; }
        }
        return true;
    }

    /** Public fail-closed readiness check used by dependent school-profile services. */
    public static function schema_is_compatible() {
        global $wpdb;
        $ready = self::schema_is_ready($wpdb->prefix . 'zmp_mentor_schools');
        if (!$ready && class_exists('ZMP_Role_Messenger') && is_callable(array('ZMP_Role_Messenger', 'mark_schema_failure_pending'))) {
            ZMP_Role_Messenger::mark_schema_failure_pending();
        }
        return $ready;
    }

    public static function school_key($school_name, $municipality) {
        $normalize = static function ($value) {
            $value = remove_accents(trim((string)$value));
            $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
            return preg_replace('/\s+/u', ' ', $value);
        };
        return hash('sha256', $normalize($school_name) . "\0" . $normalize($municipality));
    }

    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string)$value, 'UTF-8'); }
        if (preg_match_all('/./us', (string)$value, $matches)) { return count($matches[0]); }
        return strlen((string)$value);
    }

    public static function add_school($mentor_id, $school_name, $municipality, $is_primary = false) {
        global $wpdb;
        $mentor_id = absint($mentor_id);
        $school_name = sanitize_text_field($school_name);
        $municipality = sanitize_text_field($municipality);
        if (!$mentor_id || self::text_length($school_name) < 3 || self::text_length($school_name) > 255 || self::text_length($municipality) < 2 || self::text_length($municipality) > 150) {
            return new WP_Error('invalid_school', 'Внесете важечко име на училиште и општина.');
        }
        $region = ZMP_Regions::region_for_municipality($municipality);
        if (!$region || !ZMP_Regions::valid_region($region)) {
            return new WP_Error('unknown_municipality', 'Не може автоматски да се утврди регионот за оваа општина. Проверете го внесот.');
        }
        $table = $wpdb->prefix . 'zmp_mentor_schools';
        // Use the public readiness gate so a real school-save failure also
        // schedules the shared five-minute schema retry after drift is found.
        if (!self::schema_is_compatible()) {
            self::debug_event('error','School engagement save blocked by schema readiness.',array('outcome'=>'fail_closed','error_code'=>'schema_not_ready'));
            return new WP_Error('school_schema_not_ready', 'Училишниот регистар не е подготвен за безбедно ажурирање.');
        }
        $key = self::school_key($school_name, $municipality);
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE mentor_id=%d AND school_key=%s", $mentor_id, $key));
        if ($is_primary) { $wpdb->update($table, array('is_primary'=>0), array('mentor_id'=>$mentor_id)); }
        if ($existing) {
            $preserve_override = !empty($existing->region_is_override) && ZMP_Regions::valid_region($existing->region);
            // This single row-locked statement increments the epoch exactly once
            // when an inactive engagement is reactivated, even under concurrency.
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET engagement_epoch=engagement_epoch+IF(is_active=0,1,0),is_active=1,is_primary=%d,region=%s,region_is_override=%d,updated_at=%s WHERE id=%d AND mentor_id=%d",
                $is_primary ? 1 : (int)$existing->is_primary,
                $preserve_override ? $existing->region : $region,
                $preserve_override ? 1 : 0,
                current_time('mysql'),
                (int)$existing->id,
                $mentor_id
            ));
            if ($updated === false) { return new WP_Error('school_save_failed', 'Училиштето не е зачувано. Обидете се повторно.'); }
            self::debug_event('info','School engagement activated.',array('school_id'=>(int)$existing->id,'engagement_epoch'=>(int)$existing->engagement_epoch+((int)$existing->is_active===1?0:1),'outcome'=>(int)$existing->is_active===1?'active':'reactivated'));
            return (int)$existing->id;
        }
        $ok = $wpdb->insert($table, array(
            'mentor_id'=>$mentor_id,'school_name'=>$school_name,'municipality'=>$municipality,
            'region'=>$region,'region_is_override'=>0,'school_key'=>$key,'is_primary'=>$is_primary ? 1 : 0,'is_active'=>1,
            'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql'),
        ));
        if (!$ok) {
            $existing_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE mentor_id=%d AND school_key=%s", $mentor_id, $key));
            if ($existing_id) { return $existing_id; }
            return new WP_Error('school_save_failed', 'Училиштето не е зачувано. Обидете се повторно.');
        }
        self::debug_event('info','School engagement created.',array('school_id'=>(int)$wpdb->insert_id,'engagement_epoch'=>1,'outcome'=>'created'));
        return (int)$wpdb->insert_id;
    }

    public static function ensure_primary($mentor) {
        if (!$mentor || empty($mentor->id) || empty($mentor->school_name) || empty($mentor->municipality)) { return 0; }
        global $wpdb;
        $key = self::school_key($mentor->school_name, $mentor->municipality);
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id,region,region_is_override,is_primary,is_active FROM {$wpdb->prefix}zmp_mentor_schools WHERE mentor_id=%d AND school_key=%s", (int)$mentor->id, $key));
        $expected_region = ZMP_Regions::region_for_municipality($mentor->municipality);
        if ($existing && (int)$existing->is_primary === 1 && (int)$existing->is_active === 1) {
            if (!empty($existing->region_is_override) && ZMP_Regions::valid_region($existing->region)) {
                $wpdb->update($wpdb->prefix.'zmp_mentors', array('region'=>$existing->region), array('id'=>(int)$mentor->id));
                return (int)$existing->id;
            }
            if ((string)$existing->region === (string)$expected_region) { return (int)$existing->id; }
        }
        $id = self::add_school((int)$mentor->id, $mentor->school_name, $mentor->municipality, true);
        if (is_wp_error($id)) { return 0; }
        $school = $wpdb->get_row($wpdb->prepare("SELECT region_is_override FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d AND mentor_id=%d", $id, (int)$mentor->id));
        $region = $expected_region;
        if ($region && (!$school || empty($school->region_is_override))) {
            $wpdb->update($wpdb->prefix.'zmp_mentor_schools', array('region'=>$region,'region_is_override'=>0), array('id'=>$id,'mentor_id'=>(int)$mentor->id));
        }
        return (int)$id;
    }

    /** Apply a moderator-approved region exception to exactly one owned school. */
    public static function apply_moderator_region_override($mentor_id, $school_id, $region, $sync_user_meta = true) {
        global $wpdb;
        if (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Само модератор може да одобри регионален исклучок.');
        }
        $mentor_id = absint($mentor_id);
        $school_id = absint($school_id);
        $region = sanitize_key((string) $region);
        if (!$mentor_id || !$school_id || !ZMP_Regions::valid_region($region)) {
            return new WP_Error('invalid_region_override', 'Училиштето или регионот е невалиден.');
        }
        $school = self::owned_school($mentor_id, $school_id);
        if (!$school) { return new WP_Error('school_not_owned', 'Училиштето не е активно или не припаѓа на наставникот.'); }
        $saved = $wpdb->update($wpdb->prefix . 'zmp_mentor_schools', array(
            'region'=>$region,
            'region_is_override'=>1,
            'updated_at'=>current_time('mysql'),
        ), array('id'=>$school_id,'mentor_id'=>$mentor_id,'is_active'=>1));
        if ($saved === false) { return new WP_Error('database_error', 'Регионалниот исклучок не можеше да се зачува.'); }
        if ((int) $school->is_primary === 1) {
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}zmp_mentors WHERE id=%d", $mentor_id));
            if ($mentor && $wpdb->update($wpdb->prefix . 'zmp_mentors', array('region'=>$region), array('id'=>$mentor_id)) === false) {
                return new WP_Error('database_error', 'Профилот на примарното училиште не можеше да се синхронизира.');
            }
            if ($sync_user_meta && $mentor && (int) $mentor->wp_user_id > 0) { update_user_meta((int) $mentor->wp_user_id, 'zmp_region', $region); }
        }
        return true;
    }
    public static function for_mentor($mentor_id, $active_only = true) {
        global $wpdb;
        $where = $active_only ? ' AND is_active=1' : '';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentor_schools WHERE mentor_id=%d$where ORDER BY is_primary DESC,school_name,id", absint($mentor_id)));
    }

    public static function owned_school($mentor_id, $school_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentor_schools WHERE mentor_id=%d AND id=%d AND is_active=1", absint($mentor_id), absint($school_id)));
    }
    private static function debug_event($level,$message,$context) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level,$message,$context,'school_profile'); }
    }
}







