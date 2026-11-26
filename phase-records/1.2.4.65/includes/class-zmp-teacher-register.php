<?php
if (!defined('ABSPATH')) { exit; }

/** Private directory of approved professional music-teacher assignments. */
class ZMP_Teacher_Register {
    const MAX_FILTER_LENGTH = 100;
    const MAX_PAGE_SIZE = 100;
    const CSV_BATCH_SIZE = 100;

    /** Private register access requires an allowed plugin role; Subscriber may coexist with that role. */
    public static function can_view_current_user() {
        if (current_user_can('manage_options')) { return true; }
        $user_id = get_current_user_id();
        if (ZMP_Mentor_School_Profile::is_teacher($user_id)
            || ZMP_Mentor_School_Profile::has_role_stack($user_id, 'zmp_moderator')) { return true; }
        if (ZMP_Mentor_School_Profile::has_role_stack($user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))) {
            return class_exists('ZMP_President_Mandate')
                && ZMP_President_Mandate::user_has_active_mandate($user_id);
        }
        return false;
    }
    /** Return a restricted, paginated list of approved teacher assignments. */
    public static function search(array $filters = array(), $page = 1, $per_page = 20) {
        global $wpdb;
        if (!self::can_view_current_user()) {
            self::debug_event('warning', 'Teacher register search denied.', array('error_code'=>'forbidden'));
            return new WP_Error('forbidden', 'Немате дозвола за пристап до регистарот.');
        }

        $page = self::bounded_int($page, 1, 100000);
        $per_page = self::bounded_int($per_page, 20, self::MAX_PAGE_SIZE);
        $where = self::filter_where($filters);
        $tables = self::tables();

        $count_sql = "SELECT COUNT(*) FROM (
            SELECT m.id AS mentor_id,s.id AS school_id,c.id AS subject_id
            FROM {$tables['mentors']} m
            JOIN {$tables['schools']} s ON s.mentor_id=m.id AND s.is_active=1
            JOIN {$tables['assignments']} a ON a.mentor_id=m.id AND a.school_id=s.id
            JOIN {$tables['subjects']} c ON c.id=a.subject_id AND c.status='approved' AND c.is_active=1
            {$where['sql']}
            GROUP BY m.id,s.id,c.id
        ) zmp_register_rows";
        $total_result = $wpdb->get_var(self::prepare($count_sql, $where['args']));
        if (!empty($wpdb->last_error)) {
            self::debug_event('error', 'Teacher register search query failed.', array('error_code'=>'database_query_failed'));
            return new WP_Error('database_error', 'Регистарот не може да се пребара.');
        }
        $total = (int) $total_result;
        $pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
        if ($pages > 0) { $page = min($page, $pages); }
        $offset = ($page - 1) * $per_page;

        $sql = self::projection_sql($where['sql'], 'LIMIT %d OFFSET %d');
        $args = $where['args'];
        $args[] = $per_page;
        $args[] = $offset;
        $rows = $wpdb->get_results(self::prepare($sql, $args));
        if (!is_array($rows) || !empty($wpdb->last_error)) {
            self::debug_event('error', 'Teacher register search query failed.', array('error_code'=>'database_query_failed'));
            return new WP_Error('database_error', 'Регистарот не може да се пребара.');
        }
        $items = array();
        foreach ($rows as $row) {
            $items[] = self::project_row($row);
        }
        self::debug_event('info', 'Teacher register search completed.', array('page'=>$page,'page_size'=>$per_page,'result_count'=>count($items),'total'=>$total));
        return array('items' => $items, 'total' => $total, 'pages' => $pages, 'current_page' => $page);
    }

    /** Emit every matching row in bounded keyset batches through wpdb. */
    public static function export_csv(array $filters = array()) {
        global $wpdb;
        if (!self::can_view_current_user()) {
            self::debug_event('warning', 'Teacher register CSV export denied.', array('error_code'=>'forbidden'));
            return new WP_Error('forbidden', 'Немате дозвола за извоз на регистарот.');
        }
        $where = self::filter_where($filters);
        // Each wpdb query is bounded. The keyset uses the stable grouped row key
        // (mentor, school, subject), avoiding offset drift and retaining all rows
        // when the underlying data is unchanged. Batches are not one transaction
        // snapshot: concurrent inserts/updates/deletes can affect later batches.
        // php://temp spills to disk after its 2 MiB in-memory threshold so the full
        // CSV is staged before output without retaining all rows in PHP memory.
        $stream = fopen('php://temp/maxmemory:2097152', 'w+b');
        if (!$stream) {
            self::debug_event('error', 'Teacher register CSV staging failed.', array('batch_count'=>0,'row_count'=>0,'error_code'=>'staging_write_failed'));
            return new WP_Error('export_failed', 'Извозот не може да се подготви.');
        }
        if (fputcsv($stream, array('name','subject','grades','school','municipality','region')) === false) {
            fclose($stream);
            self::debug_event('error', 'Teacher register CSV staging failed.', array('batch_count'=>0,'row_count'=>0,'error_code'=>'staging_write_failed'));
            return new WP_Error('export_failed', 'Извозот не може да се подготви.');
        }

        $cursor = null;
        $batch_count = 0;
        $row_count = 0;
        while (true) {
            $sql = self::projection_sql($where['sql'], 'LIMIT %d', true, $cursor);
            $args = $where['args'];
            if ($cursor !== null) {
                $args = array_merge($args, array(
                    $cursor[0],
                    $cursor[0], $cursor[1],
                    $cursor[0], $cursor[1], $cursor[2],
                ));
            }
            $args[] = self::CSV_BATCH_SIZE;
            $rows = $wpdb->get_results(self::prepare($sql, $args));
            if (!is_array($rows) || !empty($wpdb->last_error)) {
                fclose($stream);
                self::debug_event('error', 'Teacher register CSV batch query failed.', array('batch_count'=>$batch_count,'error_code'=>'database_query_failed'));
                return new WP_Error('export_failed', 'Извозот не може да се подготви.');
            }

            if (!$rows) { break; }
            $batch_count++;
            foreach ($rows as $row) {
                $next_cursor = array(
                    (int) ($row->_cursor_mentor_id ?? 0),
                    (int) ($row->_cursor_school_id ?? 0),
                    (int) ($row->_cursor_subject_id ?? 0),
                );
                if ($next_cursor[0] < 1 || $next_cursor[1] < 1 || $next_cursor[2] < 1
                    || ($cursor !== null && self::compare_cursor($next_cursor, $cursor) <= 0)) {
                    fclose($stream);
                    self::debug_event('error', 'Teacher register CSV staging failed.', array('batch_count'=>$batch_count,'row_count'=>$row_count,'error_code'=>'staging_write_failed'));
                    return new WP_Error('export_failed', 'Извозот не може да се подготви.');
                }
                $cells = array_map(array(__CLASS__, 'csv_cell'), array_values(self::project_row($row)));
                if (fputcsv($stream, $cells) === false) {
                    fclose($stream);
                    self::debug_event('error', 'Teacher register CSV staging failed.', array('batch_count'=>$batch_count,'row_count'=>$row_count,'error_code'=>'staging_write_failed'));
                    return new WP_Error('export_failed', 'Извозот не може да се подготви.');
                }
                $cursor = $next_cursor;
                $row_count++;
            }

            if (count($rows) < self::CSV_BATCH_SIZE) { break; }
        }

        if (!rewind($stream)) {
            fclose($stream);
            self::debug_event('error', 'Teacher register CSV staging failed.', array('batch_count'=>$batch_count,'row_count'=>$row_count,'error_code'=>'staging_write_failed'));
            return new WP_Error('export_failed', 'Извозот не може да се подготви.');
        }
        $sent = fpassthru($stream);
        fclose($stream);
        if ($sent === false) {
            self::debug_event('error', 'Teacher register CSV response failed.', array('batch_count'=>$batch_count,'row_count'=>$row_count,'error_code'=>'response_failed'));
            return new WP_Error('export_failed', 'Извозот не може да се испрати.');
        }
        self::debug_event('info', 'Teacher register CSV export completed.', array('batch_count'=>$batch_count,'row_count'=>$row_count));
        return true;
    }

    /** Emit only high-level, privacy-safe teacher-register diagnostics. */
    private static function debug_event($level, $message, $context = array()) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level, $message, $context, 'teacher_register'); }
    }

    private static function tables() {
        global $wpdb;
        return array(
            'mentors' => $wpdb->prefix . 'zmp_mentors',
            'schools' => $wpdb->prefix . 'zmp_mentor_schools',
            'assignments' => $wpdb->prefix . 'zmp_mentor_school_subjects',
            'subjects' => $wpdb->prefix . 'zmp_subject_catalog',
            'grades' => $wpdb->prefix . 'zmp_mentor_school_subject_grades',
        );
    }

    /** Shared approved-field query for page search and bounded CSV batches. */
    private static function projection_sql($where_sql, $pagination = '', $include_cursor = false, $cursor = null) {
        global $wpdb;
        $tables = self::tables();
        $cursor_select = $include_cursor
            ? ',m.id AS _cursor_mentor_id,s.id AS _cursor_school_id,c.id AS _cursor_subject_id'
            : '';
        $cursor_having = $cursor !== null
            ? 'HAVING (m.id > %d) OR (m.id = %d AND s.id > %d) OR (m.id = %d AND s.id = %d AND c.id > %d)'
            : '';
        return "SELECT m.full_name AS name,c.subject_name AS subject,
                       GROUP_CONCAT(DISTINCT g.grade_label ORDER BY g.grade_label SEPARATOR ', ') AS grades,
                       s.school_name AS school,s.municipality AS municipality,s.region AS region{$cursor_select}
                FROM {$tables['mentors']} m
                JOIN {$tables['schools']} s ON s.mentor_id=m.id AND s.is_active=1
                JOIN {$tables['assignments']} a ON a.mentor_id=m.id AND a.school_id=s.id
                JOIN {$tables['subjects']} c ON c.id=a.subject_id AND c.status='approved' AND c.is_active=1
                LEFT JOIN {$tables['grades']} g ON g.assignment_id=a.id
                {$where_sql}
                GROUP BY m.id,s.id,c.id,m.full_name,c.subject_name,s.school_name,s.municipality,s.region
                {$cursor_having}
                ORDER BY m.id,s.id,c.id {$pagination}";
    }

    /** Compare the stable grouped-row cursor tuple lexicographically. */
    private static function compare_cursor(array $left, array $right) {
        for ($index = 0; $index < 3; $index++) {
            if ($left[$index] !== $right[$index]) { return $left[$index] <=> $right[$index]; }
        }
        return 0;
    }

    /** Strip database internals from both search and export result rows. */
    private static function project_row($row) {
        return array(
            'name' => (string) ($row->name ?? ''),
            'subject' => (string) ($row->subject ?? ''),
            'grades' => self::localize_grade_labels((string) ($row->grades ?? '')),
            'school' => (string) ($row->school ?? ''),
            'municipality' => (string) ($row->municipality ?? ''),
            'region' => (string) ($row->region ?? ''),
        );
    }

    /** Localize only the projected display value; leave stored grade keys and unknown legacy labels intact. */
    private static function localize_grade_labels($labels) {
        $translate = static function($value) {
            return function_exists('__') ? __($value, 'zmp-competitions') : $value;
        };
        $mapping = array(
            'grade_1'=>$translate('I одделение'),
            'grade_2'=>$translate('II одделение'),
            'grade_3'=>$translate('III одделение'),
            'grade_4'=>$translate('IV одделение'),
            'grade_5'=>$translate('V одделение'),
            'grade_6'=>$translate('VI одделение'),
            'grade_7'=>$translate('VII одделение'),
            'grade_8'=>$translate('VIII одделение'),
            'grade_9'=>$translate('IX одделение'),
            'year_1'=>$translate('I година'),
            'year_2'=>$translate('II година'),
            'year_3'=>$translate('III година'),
            'year_4'=>$translate('IV година'),
        );
        $values = explode(', ', (string) $labels);
        foreach ($values as &$value) {
            if (isset($mapping[$value])) { $value = $mapping[$value]; }
        }
        unset($value);
        return implode(', ', $values);
    }

    /** Build a single prepared WHERE fragment shared by count and result queries. */
    private static function filter_where(array $filters) {
        $columns = array(
            'name' => 'm.full_name',
            'subject' => 'c.subject_name',
            'school' => 's.school_name',
            'municipality' => 's.municipality',
            'region' => 's.region',
        );
        $parts = array();
        $args = array();
        foreach ($columns as $key => $column) {
            if (!isset($filters[$key]) || !is_scalar($filters[$key])) { continue; }
            $value = sanitize_text_field((string) $filters[$key]);
            if (function_exists('mb_substr')) { $value = mb_substr($value, 0, self::MAX_FILTER_LENGTH, 'UTF-8'); }
            else { $value = substr($value, 0, self::MAX_FILTER_LENGTH); }
            if ($value === '') { continue; }
            $parts[] = $column . ' LIKE %s';
            $args[] = '%' . self::escape_like($value) . '%';
        }
        return array('sql' => $parts ? 'WHERE ' . implode(' AND ', $parts) : '', 'args' => $args);
    }

    private static function escape_like($value) {
        global $wpdb;
        return $wpdb->esc_like($value);
    }

    private static function prepare($sql, array $args) {
        global $wpdb;
        return $args ? call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args)) : $sql;
    }

    private static function csv_cell($value) {
        $value = (string) $value;
        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) ? "'" . $value : $value;
    }

    private static function bounded_int($value, $default, $maximum) {
        if (!is_scalar($value) || !is_numeric($value)) { return $default; }
        return max(1, min((int) $maximum, absint($value)));
    }
}


