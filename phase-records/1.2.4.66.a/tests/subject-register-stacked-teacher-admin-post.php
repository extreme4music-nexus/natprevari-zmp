<?php
/**
 * Controlled, synthetic-only diagnostic of the first Teacher subject proposal
 * submit route. Run only on a fresh zmp65_subject_test
 * disposable database after exact-hash review. It restores captured synthetic
 * rows, users, session metadata, and diagnostics option state in finally.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_subject_test'
    || !isset($wpdb) || $wpdb->prefix !== 'z66a_') {
    fwrite(STDERR, "BLOCKED: expected fresh disposable zmp65_subject_test / z66a_ site.\n"); exit(2);
}
$site = wp_parse_url(home_url('/'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8100) {
    fwrite(STDERR, "BLOCKED: site URL is not authorized loopback 127.0.0.1:8100.\n"); exit(2);
}
$admin_post_endpoint = wp_parse_url(admin_url('admin-post.php'));
if (!is_array($admin_post_endpoint) || ($admin_post_endpoint['scheme'] ?? '') !== 'http'
    || ($admin_post_endpoint['host'] ?? '') !== '127.0.0.1'
    || (int) ($admin_post_endpoint['port'] ?? 0) !== 8100
    || ($admin_post_endpoint['path'] ?? '') !== '/wp-admin/admin-post.php'
    || isset($admin_post_endpoint['user']) || isset($admin_post_endpoint['pass'])
    || isset($admin_post_endpoint['query']) || isset($admin_post_endpoint['fragment'])) {
    fwrite(STDERR, "BLOCKED: admin-post endpoint is not the authorized loopback route.\n"); exit(2);
}
foreach (array('ZMP_Mentor', 'ZMP_Mentor_Subject_Proposals') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required plugin route/service is unavailable.\n"); exit(2); }
}

$users_before = count_users();
$admins = get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
if ((int) ($users_before['total_users'] ?? -1) !== 1 || count((array) $admins) !== 1) {
    fwrite(STDERR, "BLOCKED: expected a fresh WordPress install with one synthetic administrator and no other users.\n"); exit(2);
}
$admin_id = (int) $admins[0];
wp_set_current_user($admin_id);
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$event_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$catalog_table = $wpdb->prefix . 'zmp_subject_catalog';
$alias_table = $wpdb->prefix . 'zmp_subject_aliases';
$mentor_table = $wpdb->prefix . 'zmp_mentors';
$mentor_profile_table = $wpdb->prefix . 'zmp_mentor_school_profiles';
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$assignment_table = $wpdb->prefix . 'zmp_mentor_school_subjects';
$grade_table = $wpdb->prefix . 'zmp_mentor_school_subject_grades';
$school_contact_table = $wpdb->prefix . 'zmp_school_contacts';
$mandate_table = ZMP_President_Mandate::table_name();
$mandate_audit_table = ZMP_President_Mandate::audit_table_name();
$tracked_tables = array($wpdb->users,$wpdb->usermeta,$mentor_table,$mentor_profile_table,$school_table,$assignment_table,$grade_table,$school_contact_table,$proposal_table,$event_table,$catalog_table,$alias_table,$mandate_table,$mandate_audit_table);
$state_snapshot = static function ($table) use ($wpdb) {
    if (!is_string($table) || !preg_match('/\A[A-Za-z0-9_]+\z/D', $table)) { return false; }
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    if ((string) $found !== $table) { return false; }
    $rows = $wpdb->get_results('SELECT * FROM `' . esc_sql($table) . '`', ARRAY_A);
    if (!is_array($rows) || $wpdb->last_error !== '') { return false; }
    $encoded = array(); foreach ($rows as $row) { $encoded[] = wp_json_encode($row); }
    if (in_array(false, $encoded, true)) { return false; }
    sort($encoded, SORT_STRING); return hash('sha256', implode("\n", $encoded));
};
$state_before = array();
foreach ($tracked_tables as $table) { $state_before[$table] = $state_snapshot($table); if ($state_before[$table] === false) { fwrite(STDERR, "BLOCKED: required baseline tables are unavailable.\n"); exit(2); } }
$empty_before_write_tables = array($mentor_table,$mentor_profile_table,$school_table,$assignment_table,$grade_table,$school_contact_table,$proposal_table,$event_table,$catalog_table,$alias_table,$mandate_table,$mandate_audit_table);
foreach ($empty_before_write_tables as $table) {
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`");
    if ($wpdb->last_error !== '' || $count !== 0) {
        fwrite(STDERR, "BLOCKED: exact route fixture requires empty feature tables; no synthetic writes were made.\n"); exit(2);
    }
}
$debug_option = ZMP_Debug::OPTION;
$debug_option_before = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $debug_option), ARRAY_A);
if ($wpdb->last_error !== '') { fwrite(STDERR, "BLOCKED: diagnostics option baseline is unavailable.\n"); exit(2); }
$debug_option_was_present = $debug_option_before !== null;
if ($debug_option_was_present && (!is_array($debug_option_before)
    || !array_key_exists('option_value', $debug_option_before)
    || !array_key_exists('autoload', $debug_option_before))) {
    fwrite(STDERR, "BLOCKED: diagnostics option baseline row is invalid.\n"); exit(2);
}
$debug_events_before = array();
if ($debug_option_was_present) {
    $debug_events_before = maybe_unserialize($debug_option_before['option_value']);
    if (!is_array($debug_events_before) || count($debug_events_before) > ZMP_Debug::MAX_EVENTS - 2) {
        fwrite(STDERR, "BLOCKED: diagnostics option baseline is invalid or lacks capacity for first-submit route diagnostics.\n"); exit(2);
    }
}
$initial_cookie = $_COOKIE; $initial_user_id = get_current_user_id();
$created_user_ids = array(); $inserted_mentors = array();
$teacher_id = 0; $second_teacher_id = 0; $president_id = 0; $appointment_id = 0;
$proposal_id = 0; $canonical_id = 0; $canonical_owned = false; $sessions = array(); $transport_ambiguous = false;
$http_responses = array(); $request_started = false; $route_response_received = false; $transport_error_diagnostics = array();
$fixture_started = false; $cleanup_ok = true; $guard_removed = true; $marker_absent = true; $marker_triggered = false; $mu_removed = true;
$guard = null; $marker = null;
$route_helpers_ready = false; $debug_route_actions = array(); $debug_cleanup_ok = true;
$route_substeps_run = array(); $debug_expected_suffix = null; $debug_suffix_hash = null; $debug_capture_valid = false;
$teacher_response_summary = null; $marker_kinds_triggered = array();

if (!current_user_can('manage_options') || !class_exists('ZMP_Debug')) {
    fwrite(STDERR, "BLOCKED: synthetic administrator or diagnostics service is unavailable.\n"); exit(2);
}
try {
$route_substeps_run[] = 'wp_cron_preflight_started';
if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON !== true) {
    $route_substeps_run[] = 'wp_cron_preflight_failed';
    throw new RuntimeException('stage_wp_cron_disabled_required');
}
$route_substeps_run[] = 'wp_cron_preflight_passed';
$fixture_suffix = strtolower(wp_generate_password(16, false, false));
$phrase = 'Synthetic Teacher Subject ' . $fixture_suffix;
$normalized_subject_name = trim(preg_replace('/\s+/u', ' ', sanitize_text_field($phrase)));
$normalized_subject_name = remove_accents($normalized_subject_name);
$normalized_subject_name = function_exists('mb_strtolower')
    ? mb_strtolower($normalized_subject_name, 'UTF-8')
    : strtolower($normalized_subject_name);
$normalized_subject_name = preg_replace('/\s+/u', ' ', trim($normalized_subject_name));
if (!is_string($normalized_subject_name) || $normalized_subject_name === '') {
    throw new RuntimeException('stage_subject_fixture_normalization_failed');
}
$subject_key = hash('sha256', $normalized_subject_name);
unset($normalized_subject_name);
$teacher_login = 'zmp65_subject_teacher_' . $fixture_suffix;
$make_user = static function ($login, $role) use (&$created_user_ids) {
    $user_id = wp_insert_user(array(
        'user_login'=>$login,
        'user_pass'=>wp_generate_password(32, true, true),
        'user_email'=>$login . '@example.invalid',
        'display_name'=>'Synthetic ' . $login,
        'role'=>$role,
    ));
    if (is_wp_error($user_id) || (int) $user_id < 1) {
        throw new RuntimeException('synthetic test user creation failed');
    }
    if ($role === 'zmp_teacher') {
        $synthetic_user = new WP_User((int) $user_id);
        $synthetic_user->add_role('subscriber');
        $synthetic_user->add_role('zmp_moderator');
        $synthetic_user->add_role('zmp_commission');
        $synthetic_user->add_role('zmp_testator');
        $synthetic_user->add_role('zmp_organizer');
    }
    $created_user_ids[] = (int) $user_id;
    return (int) $user_id;
};
$teacher_id = $make_user($teacher_login, 'zmp_teacher');
$inserted_mentors = array();
 $mentor_user = get_userdata($teacher_id);
if (!$mentor_user || $wpdb->insert($wpdb->prefix . 'zmp_mentors', array(
    'wp_user_id'=>$teacher_id,
    'full_name'=>$mentor_user ? $mentor_user->display_name : 'Synthetic Teacher',
    'email'=>$mentor_user ? $mentor_user->user_email : 'synthetic@example.invalid',
    'phone'=>'',
    'school_name'=>'Synthetic Subject Test School',
    'municipality'=>'Скопје',
    'region'=>'Скопски',
    'login_token'=>wp_generate_password(32, false),
)) !== 1) {
    throw new RuntimeException('synthetic teacher profile fixture could not be created');
}
$inserted_mentors[] = (int) $wpdb->insert_id;
$teacher = get_user_by('login', $teacher_login);
if (!$teacher || (int) $teacher->ID !== $teacher_id
    || !ZMP_Mentor_School_Profile::is_teacher($teacher_id)) {
    throw new RuntimeException('synthetic Teacher fixture verification failed');
}
$profile_original_user = get_current_user_id();
wp_set_current_user($teacher_id);
$teacher_engagements = ZMP_Mentor_School_Profile::get_engagements($teacher_id);
wp_set_current_user($profile_original_user);

$catalog_rows_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$catalog_table}");
$alias_rows_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$alias_table}");
if ($wpdb->last_error !== '' || $catalog_rows_before !== 0 || $alias_rows_before !== 0) {
    throw new RuntimeException('first-catalog test requires an empty subject catalog and alias table');
}

$catalog_snapshot = static function () use ($wpdb, $catalog_table) {
    $rows = $wpdb->get_results("SELECT * FROM {$catalog_table} ORDER BY id", ARRAY_A);
    return $wpdb->last_error === '' && is_array($rows) ? hash('sha256', wp_json_encode($rows)) : false;
};
$alias_snapshot = static function () use ($wpdb, $alias_table) {
    $rows = $wpdb->get_results("SELECT * FROM {$alias_table} ORDER BY id", ARRAY_A);
    return $wpdb->last_error === '' && is_array($rows) ? hash('sha256', wp_json_encode($rows)) : false;
};
$proposal_ids_snapshot = static function () use ($wpdb, $proposal_table) {
    $rows = $wpdb->get_results("SELECT * FROM {$proposal_table} ORDER BY id", ARRAY_A);
    return $wpdb->last_error === '' && is_array($rows) ? hash('sha256', wp_json_encode($rows)) : false;
};
$event_ids_snapshot = static function () use ($wpdb, $event_table) {
    $rows = $wpdb->get_results("SELECT * FROM {$event_table} ORDER BY id", ARRAY_A);
    return $wpdb->last_error === '' && is_array($rows) ? hash('sha256', wp_json_encode($rows)) : false;
};
$catalog_before = $catalog_snapshot();
$aliases_before = $alias_snapshot();
$proposals_before = $proposal_ids_snapshot();
$events_before = $event_ids_snapshot();
if ($catalog_before === false || $aliases_before === false || $proposals_before === false || $events_before === false) {
    throw new RuntimeException('pre-test database snapshots could not be read');
}
$content_real = realpath(WP_CONTENT_DIR);
if (!$content_real) {
    throw new RuntimeException('wp-content canonical path is unavailable');
}
$mu_dir = rtrim($content_real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mu-plugins';
$run_token = bin2hex(random_bytes(16));
$guard = $mu_dir . '/zmp65-subject-route-' . $run_token . '.php';
$marker = $mu_dir . '/.zmp65-subject-route-hit-' . $run_token . '.json';
$marker_failure_stage = '';
$mu_created = false;
$owned_guard_identity = null;
$owned_marker_identity = null;
$is_regular_identity = static function ($path, $handle = null) {
    if (is_link($path)) { return false; }
    $path_stat = @lstat($path);
    if (!is_array($path_stat) || (($path_stat['mode'] & 0170000) !== 0100000)) { return false; }
    if (is_resource($handle)) {
        $handle_stat = @fstat($handle);
        if (!is_array($handle_stat) || (($handle_stat['mode'] & 0170000) !== 0100000)
            || (int) $handle_stat['dev'] !== (int) $path_stat['dev'] || (int) $handle_stat['ino'] !== (int) $path_stat['ino']) { return false; }
    }
    return array('dev'=>(int) $path_stat['dev'],'ino'=>(int) $path_stat['ino']);
};
$same_identity = static function ($actual, $expected) {
    return is_array($actual) && is_array($expected)
        && (int) ($actual['dev'] ?? -1) === (int) ($expected['dev'] ?? -2)
        && (int) ($actual['ino'] ?? -1) === (int) ($expected['ino'] ?? -2);
};
$write_exclusive_owned = static function ($path, $contents) use ($is_regular_identity, $same_identity) {
    if (file_exists($path) || is_link($path) || @lstat($path) !== false) { return false; }
    $handle = @fopen($path, 'x+b');
    if (!is_resource($handle)) { return false; }
    if (!@flock($handle, LOCK_EX)) { fclose($handle); return false; }
    $identity = $is_regular_identity($path, $handle);
    $ok = is_array($identity); $offset = 0; $length = strlen($contents);
    while ($ok && $offset < $length) {
        if (!$same_identity($is_regular_identity($path, $handle), $identity)) { $ok = false; break; }
        $written = @fwrite($handle, substr($contents, $offset));
        if (!is_int($written) || $written < 1) { $ok = false; break; }
        $offset += $written;
    }
    if ($ok && !$same_identity($is_regular_identity($path, $handle), $identity)) { $ok = false; }
    if ($ok && !@fflush($handle)) { $ok = false; }
    if ($ok && !$same_identity($is_regular_identity($path, $handle), $identity)) { $ok = false; }
    if ($ok && @fseek($handle, 0) !== 0) { $ok = false; }
    $readback = $ok && $same_identity($is_regular_identity($path, $handle), $identity) ? @stream_get_contents($handle) : false;
    $ok = $ok && is_string($readback) && hash_equals(hash('sha256', $contents), hash('sha256', $readback))
        && $same_identity($is_regular_identity($path, $handle), $identity);
    @flock($handle, LOCK_UN);
    fclose($handle);
    return $ok ? $identity : false;
};
$verify_owned_text = static function ($path, $expected_identity, $expected_token, $expected_hash) use ($is_regular_identity, $same_identity) {
    if (is_link($path)) { return false; }
    $handle = @fopen($path, 'rb');
    if (!is_resource($handle)) { return false; }
    $identity = $is_regular_identity($path, $handle);
    if (!$same_identity($identity, $expected_identity)) { fclose($handle); return false; }
    if (!@flock($handle, LOCK_SH)) { fclose($handle); return false; }
    if (!$same_identity($is_regular_identity($path, $handle), $expected_identity)) { @flock($handle, LOCK_UN); fclose($handle); return false; }
    $contents = @stream_get_contents($handle);
    $after = $is_regular_identity($path, $handle);
    @flock($handle, LOCK_UN);
    fclose($handle);
    return is_string($contents) && $same_identity($after, $expected_identity)
        && hash_equals((string) $expected_hash, hash('sha256', $contents))
        && strpos($contents, (string) $expected_token) !== false;
};
$unlink_owned_text = static function ($path, $identity, $token, $expected_hash) use ($is_regular_identity, $same_identity) {
    if (!is_array($identity) || is_link($path)) { return false; }
    $handle = @fopen($path, 'rb');
    if (!is_resource($handle)) { return false; }
    $opened = $is_regular_identity($path, $handle);
    if (!$same_identity($opened, $identity) || !@flock($handle, LOCK_EX)) { fclose($handle); return false; }
    $locked = $is_regular_identity($path, $handle);
    $contents = $same_identity($locked, $identity) ? @stream_get_contents($handle) : false;
    $after_read = $is_regular_identity($path, $handle);
    $valid = is_string($contents) && $same_identity($after_read, $identity)
        && hash_equals((string) $expected_hash, hash('sha256', $contents))
        && strpos($contents, (string) $token) !== false;
    $removed = $valid && $same_identity($is_regular_identity($path, $handle), $identity) && @unlink($path);
    $absent = $removed && !file_exists($path) && !is_link($path) && @lstat($path) === false;
    @flock($handle, LOCK_UN); fclose($handle);
    return $absent;
};
$read_owned_json = static function ($path, $expected_token, $token_key) use ($is_regular_identity, $same_identity) {
    if (is_link($path)) { return false; }
    $handle = @fopen($path, 'rb');
    if (!is_resource($handle)) { return false; }
    $identity = $is_regular_identity($path, $handle);
    if (!is_array($identity)) { fclose($handle); return false; }
    if (!@flock($handle, LOCK_SH)) { fclose($handle); return false; }
    if (!$same_identity($is_regular_identity($path, $handle), $identity)) { @flock($handle, LOCK_UN); fclose($handle); return false; }
    $contents = @stream_get_contents($handle);
    $after = $is_regular_identity($path, $handle);
    @flock($handle, LOCK_UN);
    fclose($handle);
    $data = is_string($contents) ? json_decode($contents, true) : null;
    if (!is_array($data) || !$same_identity($after, $identity)
        || !hash_equals((string) $expected_token, (string) ($data[$token_key] ?? ''))
        || (int) ($data['file_dev'] ?? -1) !== (int) $identity['dev']
        || (int) ($data['file_ino'] ?? -1) !== (int) $identity['ino']) { return false; }
    return array('data'=>$data,'identity'=>$identity);
};
$unlink_owned = static function ($path, $identity, $expected_token, $token_key) use ($is_regular_identity, $same_identity) {
    if (!is_array($identity) || is_link($path)) { return false; }
    $handle = @fopen($path, 'rb');
    if (!is_resource($handle)) { return false; }
    $opened = $is_regular_identity($path, $handle);
    if (!$same_identity($opened, $identity) || !@flock($handle, LOCK_EX)) { fclose($handle); return false; }
    $locked = $is_regular_identity($path, $handle);
    $contents = $same_identity($locked, $identity) ? @stream_get_contents($handle) : false;
    $after_read = $is_regular_identity($path, $handle);
    $data = is_string($contents) ? json_decode($contents, true) : null;
    $valid = is_array($data) && $same_identity($after_read, $identity)
        && hash_equals((string) $expected_token, (string) ($data[$token_key] ?? ''))
        && (int) ($data['file_dev'] ?? -1) === (int) $identity['dev']
        && (int) ($data['file_ino'] ?? -1) === (int) $identity['ino'];
    $removed = $valid && $same_identity($is_regular_identity($path, $handle), $identity) && @unlink($path);
    $absent = $removed && !file_exists($path) && !is_link($path) && @lstat($path) === false;
    @flock($handle, LOCK_UN); fclose($handle);
    return $absent;
};
$guard_source = <<<'PHP'
<?php
$run_token = __ZMP65_RUN_TOKEN__;
$marker_path = __ZMP65_MARKER_PATH__;
// Keep this isolated route test from triggering WordPress core self-update HTTP checks.
add_action('admin_init', static function () { remove_action('admin_init', '_maybe_update_core'); }, 0);
add_filter('pre_site_transient_update_core', static function ($current) {
    $current = is_object($current) ? $current : new stdClass();
    $current->last_checked = time();
    $current->version_checked = wp_get_wp_version();
    $current->updates = array();
    return $current;
}, PHP_INT_MAX);
$marker_failure_stage = '';
$write_marker = static function ($kind) use ($run_token, $marker_path, &$marker_failure_stage) {
    $marker_failure_stage = '';
    $fail = static function ($stage) use (&$marker_failure_stage) { $marker_failure_stage = $stage; return false; };
    if (!in_array($kind, array('mail','network'), true)) { return $fail('invalid_kind'); }
    $validate_existing = static function () use ($run_token, $marker_path, $kind, $fail) {
        if (is_link($marker_path)) { return $fail('existing_symlink'); }
        $path_stat = @lstat($marker_path);
        if (!is_array($path_stat) || (($path_stat['mode'] & 0170000) !== 0100000)) { return $fail('existing_not_regular'); }
        $handle = @fopen($marker_path, 'rb');
        if (!is_resource($handle)) { return $fail('existing_open_failed'); }
        if (!@flock($handle, LOCK_EX)) { fclose($handle); return $fail('existing_lock_failed'); }
        try {
            $locked_path = @lstat($marker_path); $handle_stat = @fstat($handle);
            if (is_link($marker_path) || !is_array($locked_path) || !is_array($handle_stat)
                || (($locked_path['mode'] & 0170000) !== 0100000) || (($handle_stat['mode'] & 0170000) !== 0100000)
                || (int) $locked_path['dev'] !== (int) $handle_stat['dev']
                || (int) $locked_path['ino'] !== (int) $handle_stat['ino']
                || (int) $locked_path['dev'] !== (int) $path_stat['dev']
                || (int) $locked_path['ino'] !== (int) $path_stat['ino']) { return $fail('existing_identity_changed'); }
            if ((int) ($handle_stat['size'] ?? 0) < 2 || (int) ($handle_stat['size'] ?? 0) > 4096) { return $fail('existing_size_invalid'); }
            $contents = @stream_get_contents($handle, 4097);
            $after_path = @lstat($marker_path); $after_handle = @fstat($handle);
            if (!is_string($contents) || strlen($contents) > 4096 || is_link($marker_path)
                || !is_array($after_path) || !is_array($after_handle)
                || (($after_path['mode'] & 0170000) !== 0100000) || (($after_handle['mode'] & 0170000) !== 0100000)
                || (int) $after_path['dev'] !== (int) $after_handle['dev']
                || (int) $after_path['ino'] !== (int) $after_handle['ino']
                || (int) $after_path['dev'] !== (int) $path_stat['dev']
                || (int) $after_path['ino'] !== (int) $path_stat['ino']) { return $fail('existing_readback_changed'); }
            $data = json_decode($contents, true);
            if (!is_array($data) || array_keys($data) !== array('run_token','kind','file_dev','file_ino')
                || !is_string($data['run_token']) || !hash_equals((string) $run_token, $data['run_token'])
                || $data['kind'] !== $kind || !is_int($data['file_dev']) || !is_int($data['file_ino'])
                || $data['file_dev'] !== (int) $path_stat['dev'] || $data['file_ino'] !== (int) $path_stat['ino']) {
                return $fail('existing_marker_mismatch');
            }
            return true;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    };
    if (file_exists($marker_path) || is_link($marker_path) || @lstat($marker_path) !== false) {
        return $validate_existing();
    }
    $handle = @fopen($marker_path, 'x+b');
    if (!is_resource($handle)) {
        if (file_exists($marker_path) || is_link($marker_path) || @lstat($marker_path) !== false) { return $validate_existing(); }
        return $fail('create_open_failed');
    }
    if (!@flock($handle, LOCK_EX)) { fclose($handle); return $fail('create_lock_failed'); }
    try {
        $path_stat = @lstat($marker_path); $handle_stat = @fstat($handle);
        $same = !is_link($marker_path) && is_array($path_stat) && is_array($handle_stat)
            && (($path_stat['mode'] & 0170000) === 0100000) && (($handle_stat['mode'] & 0170000) === 0100000)
            && (int) $path_stat['dev'] === (int) $handle_stat['dev'] && (int) $path_stat['ino'] === (int) $handle_stat['ino'];
        if (!$same) { return $fail('create_identity_mismatch'); }
        $payload = wp_json_encode(array('run_token'=>$run_token,'kind'=>$kind,'file_dev'=>(int) $handle_stat['dev'],'file_ino'=>(int) $handle_stat['ino']));
        $before = @lstat($marker_path);
        if (!is_string($payload) || !is_array($before) || is_link($marker_path) || (int) $before['dev'] !== (int) $handle_stat['dev'] || (int) $before['ino'] !== (int) $handle_stat['ino']) { return $fail('create_payload_or_identity_invalid'); }
        $written = @fwrite($handle, $payload);
        $after_write_path = @lstat($marker_path); $after_write_handle = @fstat($handle);
        $same_after_write = is_int($written) && $written === strlen($payload) && !is_link($marker_path)
            && is_array($after_write_path) && is_array($after_write_handle)
            && (int) $after_write_path['dev'] === (int) $after_write_handle['dev'] && (int) $after_write_path['ino'] === (int) $after_write_handle['ino']
            && (int) $after_write_path['dev'] === (int) $handle_stat['dev'] && (int) $after_write_path['ino'] === (int) $handle_stat['ino'];
        if (!$same_after_write) { return $fail('create_write_mismatch'); }
        $before_flush_path = @lstat($marker_path); $before_flush_handle = @fstat($handle);
        if (!is_array($before_flush_path) || !is_array($before_flush_handle) || is_link($marker_path)
            || (int) $before_flush_path['dev'] !== (int) $before_flush_handle['dev'] || (int) $before_flush_path['ino'] !== (int) $before_flush_handle['ino']
            || (int) $before_flush_path['dev'] !== (int) $handle_stat['dev'] || (int) $before_flush_path['ino'] !== (int) $handle_stat['ino']
            || !@fflush($handle)) { return $fail('create_flush_failed'); }
        $after_flush_path = @lstat($marker_path); $after_flush_handle = @fstat($handle);
        if (!is_array($after_flush_path) || !is_array($after_flush_handle) || is_link($marker_path)
            || (int) $after_flush_path['dev'] !== (int) $after_flush_handle['dev'] || (int) $after_flush_path['ino'] !== (int) $after_flush_handle['ino']
            || (int) $after_flush_path['dev'] !== (int) $handle_stat['dev'] || (int) $after_flush_path['ino'] !== (int) $handle_stat['ino']
            || @fseek($handle, 0) !== 0) { return $fail('create_seek_failed'); }
        $readback = @stream_get_contents($handle); $read_path = @lstat($marker_path); $read_handle = @fstat($handle);
        $valid = is_string($readback) && hash_equals(hash('sha256', $payload), hash('sha256', $readback))
            && is_array($read_path) && is_array($read_handle) && !is_link($marker_path)
            && (int) $read_path['dev'] === (int) $read_handle['dev'] && (int) $read_path['ino'] === (int) $read_handle['ino']
            && (int) $read_path['dev'] === (int) $handle_stat['dev'] && (int) $read_path['ino'] === (int) $handle_stat['ino'];
        if (!$valid) { return $fail('create_readback_failed'); }
        return true;
    } finally {
        @flock($handle, LOCK_UN);
        fclose($handle);
    }
};
add_filter('pre_wp_mail', static function ($short_circuit, $atts) use ($write_marker, &$marker_failure_stage) {
    if (!$write_marker('mail')) { throw new RuntimeException('mail guard marker failed at stage ' . $marker_failure_stage); }
    return true;
}, PHP_INT_MAX, 2);
add_filter('pre_http_request', static function ($pre, $args, $url) use ($write_marker, &$marker_failure_stage) {
    $u=wp_parse_url($url); $path=isset($u['path'])?rtrim($u['path'],'/'):'';
    if ($u && ($u['host']??'')==='127.0.0.1' && (int)($u['port']??0)===8100 && $path==='/wp-admin/admin-post.php') { return $pre; }
    if (!$write_marker('network')) { throw new RuntimeException('network guard marker failed at stage ' . $marker_failure_stage); }
    return new WP_Error('zmp_test_network_blocked','Outbound HTTP is blocked by the test guard.');
}, PHP_INT_MAX, 3);
PHP;
$guard_source = str_replace(
    array('__ZMP65_RUN_TOKEN__','__ZMP65_MARKER_PATH__'),
    array(var_export($run_token, true),var_export($marker, true)),
    $guard_source
);
if (strpos($guard_source, '__ZMP65_RUN_TOKEN__') !== false
    || strpos($guard_source, '__ZMP65_MARKER_PATH__') !== false) {
    throw new RuntimeException('canonical trace path could not be injected');
}
$guard_hash = hash('sha256', $guard_source);
if (!$content_real || is_link($mu_dir) || (file_exists($mu_dir) && !is_dir($mu_dir))
    || (is_dir($mu_dir) && (!$mu_real = realpath($mu_dir)
        || strpos(wp_normalize_path($mu_real), wp_normalize_path($content_real) . '/') !== 0))
    || file_exists($guard) || is_link($guard) || @lstat($guard) !== false
    || file_exists($marker) || is_link($marker) || @lstat($marker) !== false) {
    throw new RuntimeException('temporary guard path is unavailable or occupied');
}

$checks = array(); $failures = array();
$assert = static function ($name, $ok) use (&$checks, &$failures) { $checks[$name] = $ok ? 'PASS' : 'FAIL'; if (!$ok) { $failures[] = $name; } };
    $assert('subscriber_teacher_staff_stack_school_profile_service_available', !is_wp_error($teacher_engagements) && is_array($teacher_engagements));
$proposal_id = 0; $canonical_id = 0; $canonical_owned = false; $sessions = array(); $transport_ambiguous = false;
$fixture_started = false; $cleanup_ok = false; $guard_removed = false; $marker_absent = false; $marker_triggered = false; $mu_removed = true;
$blocked_before_route = false;
$http_responses = array(); $request_started = false; $route_response_received = false; $transport_error_diagnostics = array();
$expiration = time() + 300; $cookie_name = defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : '';
$new_cookie = static function ($user_id) use (&$sessions, $expiration, $cookie_name) {
    $manager = WP_Session_Tokens::get_instance((int) $user_id);
    $token = $manager->create($expiration);
    if (!is_string($token) || $token === '') { throw new RuntimeException('session token creation failed'); }
    $sessions[] = array('manager'=>$manager,'token'=>$token);
    if ($cookie_name === '') { throw new RuntimeException('logged-in cookie name unavailable'); }
    $cookie = wp_generate_auth_cookie((int) $user_id, $expiration, 'logged_in', $token);
    if ($cookie === '') { throw new RuntimeException('auth cookie generation failed'); }
    return $cookie;
};
$nonce_for = static function ($user_id, $cookie, $action) use ($cookie_name) {
    $_COOKIE[$cookie_name] = $cookie; wp_set_current_user((int) $user_id); return wp_create_nonce($action);
};
$post_route = static function ($action, $body, $cookie = '') use (&$request_started, &$route_response_received, &$route_substeps_run, &$http_responses, &$transport_ambiguous, &$transport_error_diagnostics, $cookie_name) {
    $headers = array('Referer'=>home_url('/teacher-register'));
    if ($cookie !== '') { $headers['Cookie'] = $cookie_name . '=' . $cookie; }
    $request_started = true;
    $route_substeps_run[] = 'teacher_submit_post_started';
    $response = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout'=>15, 'redirection'=>0, 'headers'=>$headers,
        'body'=>array_merge(array('action'=>$action), $body),
    ));
    if (is_wp_error($response)) {
        $raw_code = $response->get_error_code();
        $allowed_transport_codes = array('http_request_failed','http_request_not_executed','http_request_blocked','zmp_test_network_blocked');
        $safe_code = is_string($raw_code) && in_array($raw_code, $allowed_transport_codes, true) ? $raw_code : 'unclassified';
        $transport_error_diagnostics[] = array('error_code'=>$safe_code);
        $transport_ambiguous = true;
        unset($response, $headers, $body, $cookie);
        throw new RuntimeException('local admin-post transport failed; stopped before another route request');
    }
    $response_code = (int) wp_remote_retrieve_response_code($response);
    $location = (string) wp_remote_retrieve_header($response, 'location');
    $location_parts = wp_parse_url($location);
    $location_query = array();
    if (is_array($location_parts) && isset($location_parts['query']) && is_string($location_parts['query'])) {
        parse_str($location_parts['query'], $location_query);
    }
    $raw_status = $location_query['zmp_subject_status'] ?? null;
    $raw_status = is_scalar($raw_status) ? sanitize_key((string) $raw_status) : '';
    $allowed_statuses = array('submitted','forbidden','invalid_subject','schema_not_ready','proposal_pending','proposal_already_decided','database_error');
    $status = in_array($raw_status, $allowed_statuses, true) ? $raw_status : 'unrecognized';
    $safe_response = array('response_code'=>$response_code,'zmp_subject_status'=>$status);
    $http_responses[] = $safe_response;
    $route_response_received = true;
    $route_substeps_run[] = 'teacher_submit_response_received';
    $route_substeps_run[] = $status === 'unrecognized' ? 'teacher_submit_status_unrecognized' : 'teacher_submit_status_allowlisted';
    unset($response, $headers, $body, $cookie, $location, $location_parts, $location_query, $raw_status, $allowed_statuses);
    return $safe_response;
};
$allowed_post_url = admin_url('admin-post.php');
add_filter('pre_http_request', static function ($pre, $args, $url) use ($allowed_post_url) {
    if (is_string($url) && hash_equals($allowed_post_url, $url)
        && strtoupper((string) ($args['method'] ?? 'GET')) === 'POST') { return $pre; }
    return new WP_Error('zmp_test_network_blocked', 'Outbound HTTP is blocked by the isolated route test.');
}, PHP_INT_MAX, 3);
$find_fixture = static function () use ($wpdb, $proposal_table, $subject_key, $teacher_id) {
    return $wpdb->get_results($wpdb->prepare("SELECT id,status,proposer_id,proposal_key FROM {$proposal_table} WHERE proposal_key=%s AND proposer_id=%d", $subject_key, $teacher_id));
};
$proposal_events = static function ($id) use ($wpdb, $event_table) {
    return $wpdb->get_results($wpdb->prepare("SELECT id,proposal_id,event_type,actor_id,event_at FROM {$event_table} WHERE proposal_id=%d ORDER BY id", (int) $id), ARRAY_A);
};
$response_code = static function ($response) { return is_array($response) ? (int) ($response['response_code'] ?? 0) : 0; };
$safe_debug_tuple = static function ($event, $teacher_user_id) {
    $known = array(
        'ZMP test request reached WordPress init.'=>array('info','none'),
        'Subject proposal submission denied.'=>array('warning','forbidden'),
        'Subject proposal validation failed.'=>array('warning','invalid_subject'),
        'Subject proposal submitted.'=>array('info','none'),
        'Subject proposal schema is not ready.'=>array('error','schema_not_ready'),
    );
    $message = is_array($event) && isset($event['message']) && is_string($event['message']) && isset($known[$event['message']])
        ? $event['message'] : 'unclassified';
    $category = is_array($event) && ($event['test_type'] ?? null) === 'teacher_subject_proposals'
        ? 'teacher_subject_proposals' : 'unclassified';
    $context = is_array($event) && is_array($event['context'] ?? null) ? $event['context'] : array();
    $raw_error_code = $context['error_code'] ?? 'none';
    $error_codes = array('none','forbidden','invalid_subject','schema_not_ready');
    $error_code = is_string($raw_error_code) && in_array($raw_error_code, $error_codes, true) ? $raw_error_code : 'unclassified';
    $tuple = array('message'=>$message,'category'=>$category,'error_code'=>$error_code);
    $valid = is_array($event) && array_keys($event) === array('time','level','test_type','session_id','message','user_id','request','context')
        && is_string($event['time'] ?? null) && strlen($event['time']) === 19
        && is_string($event['session_id'] ?? null) && is_string($event['message'] ?? null)
        && isset($known[$message]) && $category === 'teacher_subject_proposals'
        && (int) ($event['user_id'] ?? 0) === (int) $teacher_user_id
        && ($event['request'] ?? null) === '/wp-admin/admin-post.php'
        && is_array($event['context'] ?? null)
        && preg_match('/\A[a-f0-9-]{20,64}\z/iD', (string) ($context['flow_id'] ?? '')) === 1
        && ($event['level'] ?? null) === $known[$message][0]
        && $error_code === $known[$message][1];
    foreach (array_keys($context) as $context_key) {
        if (!in_array($context_key, array('form_action','post_fields_count','logged_in','role_count','flow_id','proposal_id','actor_user_id','outcome','error_code'), true)) {
            $valid = false;
        }
    }
    if ($message === 'ZMP test request reached WordPress init.') {
        $valid = $valid && ($context['form_action'] ?? null) === 'zmp_submit_subject_proposal'
            && (int) ($context['post_fields_count'] ?? 0) === 3
            && (int) ($context['logged_in'] ?? 0) === 1 && (int) ($context['role_count'] ?? 0) === 6;
    } elseif ($message === 'Subject proposal submitted.') {
        $valid = $valid && (int) ($context['actor_user_id'] ?? 0) === (int) $teacher_user_id
            && (int) ($context['proposal_id'] ?? 0) > 0 && ($context['outcome'] ?? null) === 'pending';
    } elseif ($message === 'Subject proposal schema is not ready.') {
        $valid = $valid && ($context['outcome'] ?? null) === 'fail_closed';
    }
    return array('tuple'=>$tuple,'valid'=>(bool) $valid);
};
$route_helpers_ready = true;

    if (!is_dir($mu_dir)) { if (!mkdir($mu_dir, 0700)) { throw new RuntimeException('cannot create temporary MU plugin directory'); } $mu_created = true; }
    $mu_real = realpath($mu_dir);
    if (!$mu_real || wp_normalize_path($mu_real) !== wp_normalize_path($mu_dir)
        || strpos(wp_normalize_path($mu_real), wp_normalize_path($content_real) . '/') !== 0) { throw new RuntimeException('MU plugin directory escaped its canonical wp-content trace path'); }
    $owned_guard_identity = $write_exclusive_owned($guard, $guard_source);
    if (!is_array($owned_guard_identity)
        || !$verify_owned_text($guard, $owned_guard_identity, $run_token, $guard_hash)) { throw new RuntimeException('temporary guard exclusive create/identity/hash verification failed'); }

    $route_substeps_run[] = 'schema_preflight_started';
    if (!class_exists('ZMP_Mentor_Subject_Proposals') || !is_callable(array('ZMP_Mentor_Subject_Proposals','schema_is_compatible'))) {
        $route_substeps_run[] = 'schema_preflight_unavailable';
        throw new RuntimeException('stage_schema_preflight_unavailable');
    }
    $schema_compatible = ZMP_Mentor_Subject_Proposals::schema_is_compatible();
    $route_substeps_run[] = $schema_compatible ? 'schema_preflight_passed' : 'schema_preflight_failed';
    if (!$schema_compatible) { throw new RuntimeException('stage_schema_preflight_incompatible'); }
    $assert('subject_proposal_schema_preflight', true);
    $teacher_cookie = $new_cookie($teacher_id);
    $teacher_nonce = $nonce_for($teacher_id, $teacher_cookie, 'zmp_submit_subject_proposal');
    $fixture_started = true;
    try {
        $submit = $post_route('zmp_submit_subject_proposal', array('_wpnonce'=>$teacher_nonce, 'zmp_subject_name'=>$phrase), $teacher_cookie);
    } finally {
        unset($teacher_cookie, $teacher_nonce);
    }
    unset($teacher_cookie, $teacher_nonce);
    $teacher_response_summary = $submit;
    $route_substeps_run[] = 'teacher_submit_response_sanitized';
    $rows = $find_fixture();
    $assert('teacher_submit_http_302', $response_code($submit) === 302);
    $assert('teacher_submit_redirect_status_enum', ($submit['zmp_subject_status'] ?? '') === 'submitted');
    $assert('teacher_submit_persisted_unique_pending', is_array($rows) && count($rows) === 1
        && $rows[0]->status === 'pending' && (int) $rows[0]->proposer_id === $teacher_id
        && hash_equals($subject_key, (string) $rows[0]->proposal_key));
    if (is_array($rows) && count($rows) === 1) { $proposal_id = (int) $rows[0]->id; }
    $submitted_events = $proposal_id > 0 ? $proposal_events($proposal_id) : array();
    $assert('teacher_submit_event_actor_and_timestamp', $proposal_id > 0 && count((array) $submitted_events) === 1
        && $submitted_events[0]['event_type'] === 'submitted'
        && (int) $submitted_events[0]['actor_id'] === $teacher_id
        && $submitted_events[0]['event_at'] !== '');
    $route_substeps_run[] = 'teacher_proposal_rows_inspected';

    $debug_init = array('message'=>'ZMP test request reached WordPress init.','category'=>'teacher_subject_proposals','error_code'=>'none');
    $debug_action_by_status = array(
        'submitted'=>array('message'=>'Subject proposal submitted.','category'=>'teacher_subject_proposals','error_code'=>'none'),
        'forbidden'=>array('message'=>'Subject proposal submission denied.','category'=>'teacher_subject_proposals','error_code'=>'forbidden'),
        'invalid_subject'=>array('message'=>'Subject proposal validation failed.','category'=>'teacher_subject_proposals','error_code'=>'invalid_subject'),
        'schema_not_ready'=>array('message'=>'Subject proposal schema is not ready.','category'=>'teacher_subject_proposals','error_code'=>'schema_not_ready'),
    );
    $status = (string) ($submit['zmp_subject_status'] ?? 'unrecognized');
    if (in_array($status, array('submitted','forbidden','invalid_subject','schema_not_ready','proposal_pending','proposal_already_decided','database_error'), true)) {
        $debug_expected_suffix = array($debug_init);
        if (isset($debug_action_by_status[$status])) { $debug_expected_suffix[] = $debug_action_by_status[$status]; }
    }
    $current_debug_row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $debug_option
    ), ARRAY_A);
    $current_debug_events = is_array($current_debug_row) ? maybe_unserialize($current_debug_row['option_value']) : null;
    $current_debug_prefix = is_array($current_debug_events)
        ? array_slice($current_debug_events, 0, count($debug_events_before), true) : false;
    $current_debug_suffix = is_array($current_debug_events)
        ? array_slice($current_debug_events, count($debug_events_before), null, true) : false;
    $debug_route_actions = array();
    $debug_suffix_valid = $wpdb->last_error === '' && $current_debug_prefix === $debug_events_before
        && is_array($current_debug_suffix) && is_array($debug_expected_suffix)
        && count($current_debug_suffix) === count($debug_expected_suffix);
    foreach (array_values((array) $current_debug_suffix) as $event) {
        $event_summary = $safe_debug_tuple($event, $teacher_id);
        $debug_route_actions[] = $event_summary['tuple'];
        if (!$event_summary['valid']) { $debug_suffix_valid = false; }
    }
    $debug_capture_valid = $debug_suffix_valid && $debug_route_actions === $debug_expected_suffix;
    if ($debug_capture_valid) {
        $debug_suffix_hash = hash('sha256', maybe_serialize($current_debug_suffix));
        $route_substeps_run[] = 'allowlisted_debug_suffix_captured';
    } else {
        $route_substeps_run[] = 'diagnostics_suffix_unrecognized_preserved';
    }
    unset($rows, $submitted_events, $current_debug_events, $current_debug_prefix, $current_debug_suffix, $current_debug_row);
} catch (Throwable $error) {
    if (!$fixture_started) {
        $blocked_before_route = true;
        $checks['route_setup_precondition'] = 'BLOCKED';
        $raw_stage = $error->getMessage();
        $safe_stage = is_string($raw_stage) && preg_match('/\Astage_[a-z0-9_]{1,64}\z/D', $raw_stage) ? $raw_stage : 'stage_fixture_precondition';
        fwrite(STDERR, 'BLOCKED: controlled route setup stopped before the first route request; stage=' . $safe_stage . "\n");
    } else {
        $failures[] = 'runtime_exception';
        fwrite(STDERR, "FAIL: controlled first-submit route stopped at stage=route_runtime_exception\n");
    }
} finally {
    if ($route_helpers_ready) {
    if ($transport_ambiguous) {
        // Bound the late-commit readback; never issue a second request or treat ambiguity as PASS.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $rows = $find_fixture();
            if ($wpdb->last_error !== '' || (is_array($rows) && count($rows) > 0)) { break; }
            usleep(100000);
        }
    }
    $rows = $find_fixture();
    $cleanup_safe = is_array($rows) && count($rows) <= 1 && $wpdb->last_error === '';
    if ($cleanup_safe && count($rows) === 1) {
        $id = (int) $rows[0]->id;
        $wpdb->delete($event_table, array('proposal_id'=>$id), array('%d'));
        $wpdb->delete($proposal_table, array('id'=>$id,'proposal_key'=>$subject_key,'proposer_id'=>$teacher_id), array('%d','%s','%d'));
        $cleanup_safe = $wpdb->last_error === ''
            && (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_table} WHERE id=%d", $id)) === 0
            && (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$event_table} WHERE proposal_id=%d", $id)) === 0;
    }
    $canonical_cleanup_rows = $wpdb->get_results($wpdb->prepare("SELECT id,subject_name,subject_key,status,is_active,created_by,approved_by FROM {$catalog_table} WHERE subject_key=%s", $subject_key), ARRAY_A);
    if ($canonical_owned && is_array($canonical_cleanup_rows) && count($canonical_cleanup_rows) === 1
        && (int) $canonical_cleanup_rows[0]['id'] === $canonical_id && $canonical_cleanup_rows[0]['subject_name'] === $phrase
        && $canonical_cleanup_rows[0]['subject_key'] === $subject_key && $canonical_cleanup_rows[0]['status'] === 'approved'
        && (int) $canonical_cleanup_rows[0]['is_active'] === 1 && (int) $canonical_cleanup_rows[0]['created_by'] === $president_id
        && (int) $canonical_cleanup_rows[0]['approved_by'] === $president_id) {
        $wpdb->delete($catalog_table, array('id'=>$canonical_id,'subject_key'=>$subject_key,'created_by'=>$president_id,'approved_by'=>$president_id), array('%d','%s','%d','%d'));
    } elseif ($canonical_owned || (is_array($canonical_cleanup_rows) && count($canonical_cleanup_rows) > 0)) {
        // An uncertain create outcome is never permission to delete a nonmatching canonical row.
        $cleanup_safe = false;
    }
    $canonical_absent = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$catalog_table} WHERE subject_key=%s", $subject_key)) === 0;
    $cleanup_safe = $cleanup_safe && $wpdb->last_error === ''
        && $canonical_absent
        && $catalog_snapshot() === $catalog_before && $alias_snapshot() === $aliases_before
        && $proposal_ids_snapshot() === $proposals_before && $event_ids_snapshot() === $events_before;
    foreach ($sessions as $session) {
        $session['manager']->destroy($session['token']);
        if ($session['manager']->verify($session['token'])) { $cleanup_safe = false; }
    }
    if (is_array($owned_guard_identity)) {
        $guard_removed = $unlink_owned_text($guard, $owned_guard_identity, $run_token, $guard_hash);
    } else {
        $guard_removed = !file_exists($guard) && !is_link($guard) && @lstat($guard) === false;
    }
    if (file_exists($marker) || is_link($marker) || @lstat($marker) !== false) {
        $owned_marker = $read_owned_json($marker, $run_token, 'run_token');
        if (is_array($owned_marker) && in_array((string) ($owned_marker['data']['kind'] ?? ''), array('mail','network'), true)) {
            $marker_triggered = true;
            $marker_kinds_triggered[] = (string) $owned_marker['data']['kind'];
            $owned_marker_identity = $owned_marker['identity'];
            $marker_absent = $unlink_owned($marker, $owned_marker_identity, $run_token, 'run_token');
        } else {
            $marker_absent = false;
        }
    } else {
        $marker_absent = true;
    }
    if ($transport_ambiguous) {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $late_rows = $find_fixture();
            if ($wpdb->last_error !== '' || (is_array($late_rows) && count($late_rows) > 0)) { $cleanup_safe = false; break; }
            usleep(100000);
        }
        $late_rows = $find_fixture();
        if ($wpdb->last_error !== '' || !is_array($late_rows) || count($late_rows) !== 0) { $cleanup_safe = false; }
    }
    if ($mu_created && is_dir($mu_dir)) {
        $current_mu_real = realpath($mu_dir);
        if (is_link($mu_dir) || !$current_mu_real || wp_normalize_path($current_mu_real) !== wp_normalize_path($mu_dir)) {
            $mu_removed = false;
        } else {
            $mu_removed = rmdir($mu_dir) && !is_dir($mu_dir) && !is_link($mu_dir);
        }
    }

    } else {
        // The post-write setup can fail before route cleanup helpers exist.
        // At that point no route request, guard write, or proposal/catalog write has started.
        $cleanup_safe = true;
        $guard_removed = !is_string($guard) || (!file_exists($guard) && !is_link($guard) && @lstat($guard) === false);
        $marker_absent = !is_string($marker) || (!file_exists($marker) && !is_link($marker) && @lstat($marker) === false);
        $mu_removed = !$mu_created || !is_dir($mu_dir);
    }

    // Remove only rows created by this exact fixture. The fresh-site precondition
    // and pre-run snapshots make the synthetic user/mentor/mandate IDs attributable.
    foreach ($inserted_mentors as $mentor_id) {
        $mentor_row = $wpdb->get_row($wpdb->prepare(
            "SELECT id,wp_user_id FROM {$mentor_table} WHERE id=%d",
            (int) $mentor_id
        ), ARRAY_A);
        if ($mentor_row) {
            if (!in_array((int) $mentor_row['wp_user_id'], $created_user_ids, true)
                || $wpdb->delete($mentor_table, array('id'=>(int) $mentor_id, 'wp_user_id'=>(int) $mentor_row['wp_user_id']), array('%d','%d')) !== 1) {
                $cleanup_safe = false;
            }
        } elseif ($wpdb->last_error !== '') {
            $cleanup_safe = false;
        }
    }

    if ($president_id > 0) {
        $owned_mandates = $wpdb->get_results($wpdb->prepare(
            "SELECT id,user_id,appointed_by,board_decision FROM {$mandate_table} WHERE user_id=%d AND appointed_by=%d AND board_decision=%s",
            $president_id,
            $admin_id,
            'Synthetic first-catalog route test fixture'
        ), ARRAY_A);
        if ($wpdb->last_error !== '' || !is_array($owned_mandates) || count($owned_mandates) > 1) {
            $cleanup_safe = false;
        } elseif (count($owned_mandates) === 1) {
            $owned_mandate_id = (int) $owned_mandates[0]['id'];
            $owned_events = $wpdb->get_results($wpdb->prepare(
                "SELECT id,mandate_id,user_id,event_type,actor_user_id FROM {$mandate_audit_table} WHERE mandate_id=%d",
                $owned_mandate_id
            ), ARRAY_A);
            $events_owned = is_array($owned_events) && count($owned_events) === 1
                && (int) $owned_events[0]['mandate_id'] === $owned_mandate_id
                && (int) $owned_events[0]['user_id'] === $president_id
                && (int) $owned_events[0]['actor_user_id'] === $admin_id
                && $owned_events[0]['event_type'] === 'appointed';
            if (!$events_owned) {
                $cleanup_safe = false;
            } else {
                $audit_deleted = $wpdb->delete($mandate_audit_table, array(
                    'id'=>(int) $owned_events[0]['id'], 'mandate_id'=>$owned_mandate_id,
                    'user_id'=>$president_id, 'event_type'=>'appointed', 'actor_user_id'=>$admin_id,
                ), array('%d','%d','%d','%s','%d'));
                $mandate_deleted = $wpdb->delete($mandate_table, array(
                    'id'=>$owned_mandate_id, 'user_id'=>$president_id, 'appointed_by'=>$admin_id,
                    'board_decision'=>'Synthetic first-catalog route test fixture',
                ), array('%d','%d','%d','%s'));
                if ($audit_deleted !== 1 || $mandate_deleted !== 1) { $cleanup_safe = false; }
            }
        }
    }

    if (!function_exists('wp_delete_user')) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
    foreach (array_reverse($created_user_ids) as $created_user_id) {
        if (get_userdata((int) $created_user_id)) {
            wp_delete_user((int) $created_user_id);
            if (get_userdata((int) $created_user_id)) { $cleanup_safe = false; }
        }
    }
    wp_set_current_user($initial_user_id);
    $_COOKIE = $initial_cookie;
    foreach ($state_before as $table => $baseline_hash) {
        if ($state_snapshot($table) !== $baseline_hash) { $cleanup_safe = false; }
    }

    // Restore only an exact diagnostics suffix from the completed first-submit
    // route prefix. Unknown, incomplete, or changed suffixes remain untouched.
    $debug_cleanup_ok = false;
    $debug_option_row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $debug_option
    ), ARRAY_A);
    if ($wpdb->last_error === '' && !$request_started) {
        $debug_cleanup_ok = $debug_option_was_present
            ? ($debug_option_row === $debug_option_before)
            : ($debug_option_row === null);
    } elseif ($wpdb->last_error === '' && $route_response_received && $debug_capture_valid
        && is_array($debug_expected_suffix) && is_array($debug_option_row)) {
        $debug_current_events = maybe_unserialize($debug_option_row['option_value']);
        $debug_current_prefix = is_array($debug_current_events)
            ? array_slice($debug_current_events, 0, count($debug_events_before), true) : false;
        $debug_current_suffix = is_array($debug_current_events)
            ? array_slice($debug_current_events, count($debug_events_before), null, true) : false;
        $debug_current_tuples = array(); $debug_current_suffix_valid = true;
        foreach (array_values((array) $debug_current_suffix) as $debug_event) {
            $event_summary = $safe_debug_tuple($debug_event, $teacher_id);
            $debug_current_tuples[] = $event_summary['tuple'];
            if (!$event_summary['valid']) { $debug_current_suffix_valid = false; }
        }
        $debug_owned_suffix = is_array($debug_current_events)
            && $debug_current_prefix === $debug_events_before
            && is_array($debug_current_suffix)
            && $debug_current_tuples === $debug_expected_suffix
            && $debug_current_suffix_valid
            && is_string($debug_suffix_hash)
            && hash_equals($debug_suffix_hash, hash('sha256', maybe_serialize($debug_current_suffix)));
        if ($debug_owned_suffix) {
            if ($debug_option_was_present) {
                $restored_debug_option = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND option_value=%s AND autoload=%s",
                    $debug_option_before['option_value'],
                    $debug_option_before['autoload'],
                    $debug_option,
                    $debug_option_row['option_value'],
                    $debug_option_row['autoload']
                ));
            } else {
                $restored_debug_option = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s AND autoload=%s",
                    $debug_option,
                    $debug_option_row['option_value'],
                    $debug_option_row['autoload']
                ));
            }
            if ($restored_debug_option === 1) {
                wp_cache_delete($debug_option, 'options');
                wp_cache_delete('alloptions', 'options');
                wp_cache_delete('notoptions', 'options');
                $restored_debug_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
                    $debug_option
                ), ARRAY_A);
                $debug_cleanup_ok = $wpdb->last_error === '' && ($debug_option_was_present
                    ? ($restored_debug_row === $debug_option_before)
                    : ($restored_debug_row === null));
            }
        }
    }
    if (!$debug_cleanup_ok) { $cleanup_safe = false; }

    $cleanup_ok = $cleanup_safe && $guard_removed && $marker_absent && $mu_removed;
}

$checks['exact_cleanup_and_baseline_restoration'] = $cleanup_ok ? 'PASS' : 'FAIL';
if (!$cleanup_ok) { $failures[] = 'exact_cleanup_and_baseline_restoration'; }
$checks['mail_and_outbound_network_guard_not_triggered'] = !$marker_triggered ? 'PASS' : 'FAIL';
if ($marker_triggered) { $failures[] = 'mail_or_network_guard_triggered'; }
if (is_array($teacher_response_summary)) {
    echo 'TEACHER_RESPONSE code=' . (int) ($teacher_response_summary['response_code'] ?? 0)
        . ' zmp_subject_status=' . (string) ($teacher_response_summary['zmp_subject_status'] ?? 'unrecognized') . "\n";
}
foreach ($route_substeps_run as $route_substep) { echo 'ROUTE_SUBSTEP ' . $route_substep . "\n"; }
foreach ($debug_route_actions as $debug_tuple) {
    if (is_array($debug_tuple) && array_keys($debug_tuple) === array('message','category','error_code')) {
        $encoded_tuple = wp_json_encode($debug_tuple);
        if (is_string($encoded_tuple)) { echo 'DEBUG_TUPLE ' . $encoded_tuple . "\n"; }
    }
}
foreach (array_values(array_unique($marker_kinds_triggered)) as $marker_kind) {
    if (in_array($marker_kind, array('mail','network'), true)) { echo 'GUARD_MARKER_KIND ' . $marker_kind . "\n"; }
}
if ($transport_ambiguous) {
    foreach ($transport_error_diagnostics as $diagnostic) {
        fwrite(STDERR, 'TRANSPORT_ERROR_CODE=' . (string) $diagnostic['error_code'] . "\n");
        fwrite(STDERR, 'TRANSPORT_CURL_ERRNO=' . (is_int($diagnostic['curl_errno']) ? (string) $diagnostic['curl_errno'] : 'NOT_AVAILABLE') . "\n");
    }
    fwrite(STDERR, "INDETERMINATE: a local admin-post transport response was ambiguous; route cleanup preserved unknown diagnostics.\n");
    exit(3);
}
foreach ($checks as $name=>$status) { echo $status . ' ' . $name . "\n"; }
echo 'SUMMARY ' . count(array_filter($checks, static function ($status) { return $status === 'PASS'; })) . '/' . count($checks) . " PASS\n";
if ($blocked_before_route) { exit(2); }
exit($failures ? 1 : 0);

