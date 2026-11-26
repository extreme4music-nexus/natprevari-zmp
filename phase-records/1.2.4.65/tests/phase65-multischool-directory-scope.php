<?php
/**
 * One bounded synthetic verification of multi-school directory scoping,
 * enabled-field privacy, and the below-Teacher role denial.
 * Execute only after independent exact-hash review, against the approved
 * disposable .65 WordPress site. No account/profile/request rows are changed.
 */
if (!defined('ABSPATH')) {
    fwrite(STDERR, "wordpress_bootstrap_required\n");
    exit(2);
}
global $wpdb;
$assertions = array();
$blocked = false;
$assert = static function ($condition, $label) use (&$assertions) {
    $assertions[$label] = $condition ? 'PASS' : 'FAIL';
};
$block = static function ($label) use (&$assertions, &$blocked) {
    $assertions[$label] = 'BLOCKED';
    $blocked = true;
};
$emit_blocked = static function ($source_hash = '') use (&$assertions) {
    echo wp_json_encode(array(
        'overall'=>'BLOCKED',
        'assertions'=>$assertions,
        'source_sha256'=>$source_hash,
        'profile_field_values_emitted'=>false,
        'personal_names_emitted'=>false,
        'email_values_emitted'=>false,
        'email_send_attempts'=>0,
    )) . PHP_EOL;
    exit(4);
};

if (($wpdb->dbname ?? '') !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "wrong_synthetic_database
");
    exit(2);
}
$active_plugins = array_values((array)get_option('active_plugins', array()));
$expected_plugin = 'zmp-competitions/zmp-competitions.php';
$plugin_set_ok = count($active_plugins) === 1 && $active_plugins[0] === $expected_plugin;
if (!$plugin_set_ok) {
    $block('only_current_zmp_candidate_plugin_is_active');
    $emit_blocked();
}
$assert(true, 'only_current_zmp_candidate_plugin_is_active');

if (!defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65'
    || !defined('ZMP_PLUGIN_DIR')
    || !class_exists('ZMP_Mentor_School_Profile')
    || !class_exists('ZMP_Employment_Directory')
    || !class_exists('ZMP_Debug')) {
    fwrite(STDERR, "wrong_synthetic_runtime
");
    exit(3);
}
$expected_source_hash = '8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B';
$source_path = ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-school-profile.php';
$actual_source_hash = is_file($source_path) ? strtoupper((string)hash_file('sha256', $source_path)) : '';
if ($actual_source_hash !== $expected_source_hash) {
    $block('installed_scope_fix_hash_matches_reviewed_candidate');
    $emit_blocked($actual_source_hash);
}
$assert(true, 'installed_scope_fix_hash_matches_reviewed_candidate');

$teacher_id = 127;
$teacher = get_userdata($teacher_id);
$teacher_identity_ok = $teacher
    && (int)$teacher->ID === $teacher_id
    && (string)$teacher->user_login === 'zmp65multischooltest_20261105'
    && array_values((array)$teacher->roles) === array('zmp_teacher')
    && preg_match('/^[^@\s]+@example\.invalid$/i', (string)$teacher->user_email) === 1
    && (string)$teacher->display_name === 'Synthetic Multi School Teacher';
if (!$teacher_identity_ok) {
    $block('exact_synthetic_teacher_identity_precondition');
    $emit_blocked($actual_source_hash);
}
$assert(true, 'exact_synthetic_teacher_identity_precondition');

$subscriber_id = 111;
$subscriber = get_userdata($subscriber_id);
$subscriber_identity_ok = $subscriber
    && (int)$subscriber->ID === $subscriber_id
    && (string)$subscriber->user_login === 'zmp65_uipresident_20261103_224731_1bb27f'
    && array_values((array)$subscriber->roles) === array('subscriber')
    && preg_match('/^[^@\s]+@example\.invalid$/i', (string)$subscriber->user_email) === 1;
if (!$subscriber_identity_ok) {
    $block('exact_existing_synthetic_subscriber_identity_precondition');
    $emit_blocked($actual_source_hash);
}
$assert(true, 'exact_existing_synthetic_subscriber_identity_precondition');

$school_ids = array(17, 18);
$previous_user_id = get_current_user_id();
$option_names = array(
    ZMP_Debug::OPTION,
    'zmp_role_messenger_schema_retry',
    'zmp_role_messenger_schema_retry_after',
    'zmp_db_version',
);
$option_snapshot = array();
$snapshot_ok = true;
foreach ($option_names as $option_name) {
    $wpdb->last_error = '';
    $option_snapshot[$option_name] = $wpdb->get_row(
        $wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", $option_name),
        ARRAY_A
    );
    $snapshot_ok = $snapshot_ok && $wpdb->last_error === '';
}
if (!$snapshot_ok) {
    fwrite(STDERR, "safe_option_snapshot_failed\n");
    exit(4);
}

$restore_options = static function () use ($wpdb, $option_names, $option_snapshot) {
    $ok = true;
    foreach ($option_names as $option_name) {
        $old = $option_snapshot[$option_name];
        $wpdb->last_error = '';
        $current = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", $option_name));
        if ($wpdb->last_error !== '') { $ok = false; continue; }
        if (is_array($old)) {
            if ($current === null) {
                $ok = $ok && $wpdb->insert($wpdb->options, array(
                    'option_name'=>$option_name,
                    'option_value'=>$old['option_value'],
                    'autoload'=>$old['autoload'],
                ), array('%s','%s','%s')) !== false && $wpdb->last_error === '';
            } else {
                $ok = $ok && $wpdb->update($wpdb->options, array(
                    'option_value'=>$old['option_value'],
                    'autoload'=>$old['autoload'],
                ), array('option_name'=>$option_name), array('%s','%s'), array('%s')) !== false && $wpdb->last_error === '';
            }
        } elseif ($current !== null) {
            $ok = $ok && $wpdb->delete($wpdb->options, array('option_name'=>$option_name), array('%s')) !== false && $wpdb->last_error === '';
        }
        wp_cache_delete($option_name, 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
        $wpdb->last_error = '';
        $now = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", $option_name), ARRAY_A);
        if ($wpdb->last_error !== '') { $ok = false; continue; }
        if (is_array($old)) {
            $ok = $ok && is_array($now)
                && (string)$now['option_value'] === (string)$old['option_value']
                && (string)$now['autoload'] === (string)$old['autoload'];
        } else {
            $ok = $ok && $now === null;
        }
    }
    return $ok;
};

$allowed_keys = array();
$school_key_by_id = array();
try {
    wp_set_current_user($teacher_id);
    $teacher = get_userdata($teacher_id);
    $mentor = ZMP_Mentor_School_Profile::mentor_for_user($teacher_id);
    $assert($teacher && array_values((array)$teacher->roles) === array('zmp_teacher') && $mentor, 'synthetic_teacher_owner_precondition');
    $assert(ZMP_Mentor_School_Profile::schema_is_compatible(), 'ready_school_profile_schema');
    if (!$teacher || !$mentor || !ZMP_Mentor_School_Profile::schema_is_compatible()) {
        $block('directory_checks_not_run_without_teacher_and_ready_schema');
    } else {
        $engagements = ZMP_Mentor_School_Profile::get_engagements($teacher_id);
        $engagement_ids = array();
        if (is_array($engagements)) {
            foreach ($engagements as $engagement) {
                $engagement_ids[] = (int)($engagement['school_id'] ?? 0);
            }
        }
        sort($engagement_ids);
        $expected_ids = $school_ids;
        sort($expected_ids);
        $assert($engagement_ids === $expected_ids, 'teacher_has_the_two_expected_active_synthetic_school_engagements');

        $id_marks = implode(',', array_fill(0, count($school_ids), '%d'));
        $school_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id,school_key FROM {$wpdb->prefix}zmp_mentor_schools WHERE is_active=1 AND id IN ($id_marks)",
            $school_ids
        ), ARRAY_A);
        if (!is_array($school_rows) || $wpdb->last_error !== '' || count($school_rows) !== 2) {
            $block('active_school_key_baseline_available');
        } else {
            foreach ($school_rows as $row) {
                $school_key_by_id[(int)$row['id']] = (string)$row['school_key'];
                $allowed_keys[(string)$row['school_key']] = (string)$row['school_key'];
            }
            $assert(count($allowed_keys) === 2, 'two_distinct_school_keys_are_configured');
        }

        $school_profiles = array();
        foreach ($school_ids as $school_id) {
            $profile = ZMP_Mentor_School_Profile::get_profile($teacher_id, $school_id);
            if (is_wp_error($profile)) { $profile = null; }
            $school_profiles[$school_id] = $profile;
        }
        $a = $school_profiles[17];
        $b = $school_profiles[18];
        $assert(is_array($a) && $a['employment_term'] === 'fixed_term' && $a['workload'] === 'full'
            && (int)$a['employment_term_visible'] === 1 && (int)$a['workload_visible'] === 0,
            'school_17_saved_values_and_term_visibility_are_independent');
        $assert(is_array($b) && $b['employment_term'] === 'indefinite' && $b['workload'] === 'part'
            && (int)$b['retired'] === 1 && (int)$b['retired_visible'] === 1
            && (int)$b['employment_term_visible'] === 0 && (int)$b['workload_visible'] === 0,
            'school_18_saved_values_and_retired_visibility_are_independent');

        $all_rows = ZMP_Mentor_School_Profile::get_employment_directory($teacher_id, 0);
        $assert(is_array($all_rows), 'all_authorized_school_directory_query_succeeds');
        $all_rows_in_scope = is_array($all_rows);
        $school_ids_to_map = array();
        foreach ((array)$all_rows as $row) {
            $school_ids_to_map[] = (int)($row['school_id'] ?? 0);
            $all_rows_in_scope = $all_rows_in_scope && !array_key_exists('official_email', $row);
        }
        $school_ids_to_map = array_values(array_unique(array_filter($school_ids_to_map)));
        if ($school_ids_to_map) {
            $marks = implode(',', array_fill(0, count($school_ids_to_map), '%d'));
            $mapped = $wpdb->get_results($wpdb->prepare(
                "SELECT id,school_key FROM {$wpdb->prefix}zmp_mentor_schools WHERE is_active=1 AND id IN ($marks)",
                $school_ids_to_map
            ), ARRAY_A);
            if (!is_array($mapped) || $wpdb->last_error !== '') {
                $all_rows_in_scope = false;
            } else {
                foreach ($mapped as $row) {
                    $school_key_by_id[(int)$row['id']] = (string)$row['school_key'];
                }
                foreach ((array)$all_rows as $row) {
                    $row_school_id = (int)($row['school_id'] ?? 0);
                    $row_key = $school_key_by_id[$row_school_id] ?? '';
                    $all_rows_in_scope = $all_rows_in_scope && $row_key !== '' && isset($allowed_keys[$row_key]);
                }
            }
        }
        $assert($all_rows_in_scope, 'all_school_directory_rows_stay_within_authorized_keys_and_never_include_email');

        $own_by_key = array();
        foreach ((array)$all_rows as $row) {
            if ((int)($row['mentor_id'] ?? 0) === (int)$mentor->id) {
                $key = $school_key_by_id[(int)($row['school_id'] ?? 0)] ?? '';
                if ($key !== '') { $own_by_key[$key] = $row; }
            }
        }
        $school17_key = $school_key_by_id[17] ?? '';
        $school18_key = $school_key_by_id[18] ?? '';
        $own17 = $own_by_key[$school17_key] ?? null;
        $own18 = $own_by_key[$school18_key] ?? null;
        $assert(is_array($own17) && ($own17['employment_term'] ?? null) === 'fixed_term'
            && !array_key_exists('workload', $own17) && !array_key_exists('retired', $own17)
            && !array_key_exists('official_email', $own17),
            'school_17_directory_returns_only_enabled_employment_term');
        $assert(is_array($own18) && (int)($own18['retired'] ?? -1) === 1
            && !array_key_exists('employment_term', $own18) && !array_key_exists('workload', $own18)
            && !array_key_exists('official_email', $own18),
            'school_18_directory_returns_only_enabled_retired_status');

        foreach ($school_ids as $school_id) {
            $explicit_rows = ZMP_Mentor_School_Profile::get_employment_directory($teacher_id, $school_id);
            $explicit_ok = is_array($explicit_rows);
            foreach ((array)$explicit_rows as $row) {
                $row_school_id = (int)($row['school_id'] ?? 0);
                $row_key = $school_key_by_id[$row_school_id] ?? '';
                $expected_key = $school_key_by_id[$school_id] ?? '';
                $explicit_ok = $explicit_ok && $expected_key !== '' && $row_key === $expected_key
                    && !array_key_exists('official_email', $row);
            }
            $assert($explicit_ok, 'explicit_allowed_school_' . $school_id . '_stays_in_scope_and_omits_email');
        }

        if (count($allowed_keys) === 2) {
            $key_marks = implode(',', array_fill(0, count($allowed_keys), '%s'));
            $other_school = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}zmp_mentor_schools WHERE is_active=1 AND school_key NOT IN ($key_marks) ORDER BY id LIMIT 1",
                array_values($allowed_keys)
            ));
            if ($other_school) {
                $other_result = ZMP_Mentor_School_Profile::get_employment_directory($teacher_id, (int)$other_school);
                $assert(is_wp_error($other_result) && $other_result->get_error_code() === 'school_not_owned',
                    'explicit_unconnected_active_school_is_denied');
            } else {
                $block('explicit_unconnected_active_school_is_denied_no_synthetic_fixture_available');
            }
        } else {
            $block('explicit_unconnected_active_school_is_denied_school_scope_unavailable');
        }

        // The exact synthetic subscriber identity was verified before impersonation.
        $subscriber_id = 111;
        {
            wp_set_current_user($subscriber_id);
            $denied = ZMP_Mentor_School_Profile::get_employment_directory($subscriber_id, 0);
            $assert(is_wp_error($denied) && $denied->get_error_code() === 'forbidden',
                'existing_below_teacher_synthetic_subscriber_is_denied');
            wp_set_current_user($teacher_id);
        }

        wp_set_current_user($teacher_id);
        $html = ZMP_Employment_Directory::render_shortcode();
        $assert(is_string($html) && $html !== '', 'actual_employment_directory_shortcode_returns_html');
        if (is_string($html)) {
            $email_pattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
            $assert(!preg_match($email_pattern, $html), 'directory_shortcode_html_contains_no_email_address');
            $own_block = static function ($html, $school_name, $display_name) {
                $group_marker = '<section class="zmp-employment-directory__school"><h3>' . esc_html($school_name);
                $group_start = strpos($html, $group_marker);
                if ($group_start === false) { return ''; }
                $person_marker = '<h4>' . esc_html($display_name) . '</h4>';
                $person_pos = strpos($html, $person_marker, $group_start);
                if ($person_pos === false) { return ''; }
                $prefix = substr($html, $group_start, $person_pos - $group_start);
                $relative_start = strrpos($prefix, '<li class="zmp-employment-directory__person">');
                $end = strpos($html, '</li>', $person_pos);
                if ($relative_start === false || $end === false) { return ''; }
                $start = $group_start + $relative_start;
                return substr($html, $start, $end + 5 - $start);
            };
            $school17 = $wpdb->get_var($wpdb->prepare("SELECT school_name FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d", 17));
            $school18 = $wpdb->get_var($wpdb->prepare("SELECT school_name FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d", 18));
            $own17_html = $own_block($html, (string)$school17, (string)$teacher->display_name);
            $own18_html = $own_block($html, (string)$school18, (string)$teacher->display_name);
            $assert($own17_html !== '' && strpos($own17_html, 'Работен однос') !== false
                && strpos($own17_html, 'Работен фонд') === false && strpos($own17_html, 'Пензиониран статус') === false,
                'school_17_shortcode_html_omits_disabled_fields');
            $assert($own18_html !== '' && strpos($own18_html, 'Пензиониран статус') !== false
                && strpos($own18_html, 'Работен однос') === false && strpos($own18_html, 'Работен фонд') === false,
                'school_18_shortcode_html_omits_disabled_fields');
        }
    }
} catch (Throwable $e) {
    $assert(false, 'unexpected_runtime_exception_' . sanitize_key(get_class($e)));
} finally {
    wp_set_current_user($previous_user_id);
    $restore_ok = $restore_options();
    $assert($restore_ok, 'debug_and_schema_options_restored_exactly');
}

$db_error_clear = $wpdb->last_error === '';
$assert($db_error_clear, 'wpdb_has_no_error');
$failed = in_array('FAIL', $assertions, true);
$has_blocked = in_array('BLOCKED', $assertions, true);
$overall = $failed ? 'FAIL' : ($has_blocked ? 'BLOCKED' : 'PASS');
echo wp_json_encode(array(
    'overall'=>$overall,
    'assertions'=>$assertions,
    'source_sha256'=>$actual_source_hash,
    'database'=>'zmp65_isolated',
    'profile_field_values_emitted'=>false,
    'personal_names_emitted'=>false,
    'email_values_emitted'=>false,
    'email_send_attempts'=>0,
)) . PHP_EOL;
exit($overall === 'PASS' ? 0 : ($overall === 'BLOCKED' ? 4 : 1));
