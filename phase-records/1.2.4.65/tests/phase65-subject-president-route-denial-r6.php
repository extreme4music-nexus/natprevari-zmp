<?php
/**
 * Verify the same synthetic Teacher session on submit, President queue GET,
 * and nonce-valid decision denial routes.
 * Run only via WP-CLI on the fresh isolated .65 site at 127.0.0.1:8097.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_test' || !isset($wpdb) || $wpdb->prefix !== 'z65s_'
    || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65'
    || realpath(ABSPATH) !== '/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6') {
    fwrite(STDERR, "BLOCKED: exact disposable .65 WP-CLI site required.\n"); exit(2);
}
$site = wp_parse_url(home_url('/'));
$admin = wp_parse_url(admin_url('/'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8097 || !is_array($admin)
    || ($admin['scheme'] ?? '') !== 'http' || ($admin['host'] ?? '') !== '127.0.0.1'
    || (int) ($admin['port'] ?? 0) !== 8097) {
    fwrite(STDERR, "BLOCKED: exact loopback .65 site required.\n"); exit(2);
}
foreach (array(
    'ZMP_Mentor_Subject_Proposals'=>'8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69',
    'ZMP_President_Subject_Approvals'=>'720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4',
) as $class=>$expected_hash) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: subject route service unavailable.\n"); exit(2); }
    $file = (new ReflectionClass($class))->getFileName();
    if (!is_string($file) || !is_file($file) || strtoupper(hash_file('sha256', $file)) !== $expected_hash) {
        fwrite(STDERR, "BLOCKED: loaded route source differs from reviewed .65 source.\n"); exit(2);
    }
}
if (!class_exists('ZMP_Debug')) { fwrite(STDERR, "BLOCKED: diagnostics service unavailable.\n"); exit(2); }
require_once ABSPATH . 'wp-admin/includes/user.php';
$admins = get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$users = count_users();
if (count((array) $admins) !== 1 || (int) ($users['total_users'] ?? -1) !== 1
    || !ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: fresh synthetic administrator/schema baseline required.\n"); exit(2);
}
$tables = array(
    'proposal'=>$wpdb->prefix . 'zmp_subject_proposals',
    'event'=>$wpdb->prefix . 'zmp_subject_proposal_events',
    'catalog'=>$wpdb->prefix . 'zmp_subject_catalog',
    'alias'=>$wpdb->prefix . 'zmp_subject_aliases',
);
$snapshot = static function ($table) use ($wpdb) {
    if (!preg_match('/\A[A-Za-z0-9_]+\z/D', $table)) { return false; }
    $rows = $wpdb->get_results('SELECT * FROM `' . esc_sql($table) . '`', ARRAY_A);
    if ($wpdb->last_error !== '' || !is_array($rows)) { return false; }
    $encoded = array();
    foreach ($rows as $row) { $encoded[] = wp_json_encode($row); }
    if (in_array(false, $encoded, true)) { return false; }
    sort($encoded, SORT_STRING);
    return hash('sha256', implode("\n", $encoded));
};
$baseline = array($wpdb->users=>$snapshot($wpdb->users), $wpdb->usermeta=>$snapshot($wpdb->usermeta));
foreach ($tables as $table) {
    $baseline[$table] = $snapshot($table);
    $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($table) . '`');
    if ($baseline[$table] === false || $wpdb->last_error !== '' || $count !== 0) {
        fwrite(STDERR, "BLOCKED: synthetic subject tables must be empty and readable.\n"); exit(2);
    }
}
foreach ($baseline as $value) {
    if ($value === false) { fwrite(STDERR, "BLOCKED: baseline snapshot unavailable.\n"); exit(2); }
}
$diag_options = array(ZMP_Debug::OPTION, ZMP_Debug::CAPTURE_OPTION);
$diag_before = array();
foreach ($diag_options as $name) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $name), ARRAY_A);
    if ($wpdb->last_error !== '') { fwrite(STDERR, "BLOCKED: diagnostic baseline unavailable.\n"); exit(2); }
    $diag_before[$name] = is_array($row) ? $row : null;
}
$debug_events_before = array();
if (is_array($diag_before[ZMP_Debug::OPTION] ?? null)) {
    $debug_events_before = maybe_unserialize($diag_before[ZMP_Debug::OPTION]['option_value']);
    if (!is_array($debug_events_before) || count($debug_events_before) > ZMP_Debug::MAX_EVENTS - 3) {
        fwrite(STDERR, "BLOCKED: diagnostics baseline is invalid or lacks capacity for the exact route event suffix.\n"); exit(2);
    }
}
$admin_id = (int) $admins[0];
$initial_user = get_current_user_id();
$initial_cookie = $_COOKIE;
$teacher_id = 0;
$teacher_login = '';
$teacher_email = '';
$subject_name = '';
$proposal_id = 0;
$token_manager = null;
$session_token = '';
$http_guard = null;
$outbound_guard_blocked = false;
$http_guard_removed = false;
$request_started = false;
$diagnostics_restored = true;
$baseline_restored = false;
$debug_expected_suffix = null;
$debug_suffix_hash = null;
$debug_capture_valid = false;
$debug_route_suffix = null;
$checks = array();
$failures = array();
$exception_class = '';
$assert = static function ($name, $passed) use (&$checks, &$failures) {
    $checks[$name] = $passed ? 'PASS' : 'FAIL';
    if (!$passed) { $failures[] = $name; }
};
$http_guard = static function ($preempt, $args, $url) use (&$outbound_guard_blocked) {
    $parsed = wp_parse_url((string) $url);
    if (is_array($parsed) && ($parsed['scheme'] ?? '') === 'http' && ($parsed['host'] ?? '') === '127.0.0.1'
        && (int) ($parsed['port'] ?? 0) === 8097
        && in_array(($parsed['path'] ?? ''), array('/wp-admin/profile.php','/wp-admin/admin.php','/wp-admin/admin-post.php'), true)) { return $preempt; }
    $outbound_guard_blocked = true;
    return new WP_Error('zmp65_external_http_blocked');
};
try {
    if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON !== true) {
        throw new RuntimeException('stage_wp_cron_disabled_required');
    }
    $core_update = get_site_transient('update_core');
    $core_update_ready = is_object($core_update)
        && isset($core_update->last_checked, $core_update->version_checked, $core_update->updates)
        && is_int($core_update->last_checked) && $core_update->last_checked <= time()
        && $core_update->last_checked >= time() - HOUR_IN_SECONDS
        && $core_update->version_checked === get_bloginfo('version')
        && is_array($core_update->updates);
    if (!$core_update_ready) { throw new RuntimeException('stage_fresh_update_core_transient_required'); }
    $suffix = strtolower(wp_generate_password(16, false, false));
    $teacher_login = 'zmp65_route_denied_' . $suffix;
    $teacher_email = $teacher_login . '@example.invalid';
    $subject_name = 'Synthetic route denial ' . strtoupper(substr($suffix, 0, 8));
    $inserted = wp_insert_user(array(
        'user_login'=>$teacher_login,'user_pass'=>wp_generate_password(40, true, true),
        'user_email'=>$teacher_email,'display_name'=>'Synthetic Route Denial Teacher','role'=>'zmp_teacher',
    ));
    if (is_wp_error($inserted) || (int) $inserted < 1) { throw new RuntimeException('synthetic role setup failed'); }
    $teacher_id = (int) $inserted;
    $teacher = get_userdata($teacher_id);
    if (!$teacher || $teacher->user_email !== $teacher_email || array_values((array) $teacher->roles) !== array('zmp_teacher')) {
        throw new RuntimeException('synthetic role identity mismatch');
    }
    $token_manager = WP_Session_Tokens::get_instance($teacher_id);
    $expires = time() + 300;
    $session_token = $token_manager->create($expires);
    if (!is_string($session_token) || $session_token === '') { throw new RuntimeException('local session setup failed'); }
    $auth_cookie = wp_generate_auth_cookie($teacher_id, $expires, 'auth', $session_token);
    $logged_cookie = wp_generate_auth_cookie($teacher_id, $expires, 'logged_in', $session_token);
    if (!is_string($auth_cookie) || $auth_cookie === '' || !is_string($logged_cookie) || $logged_cookie === '') {
        throw new RuntimeException('same-session auth cookie creation failed');
    }
    $_COOKIE[AUTH_COOKIE] = $auth_cookie;
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    wp_set_current_user($teacher_id);
    $cookie_header = AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie;
    $headers = array('Cookie'=>$cookie_header, 'Referer'=>home_url('/'));
    $teacher_nonce = wp_create_nonce('zmp_submit_subject_proposal');
    $decision_nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!is_string($teacher_nonce) || $teacher_nonce === '' || !is_string($decision_nonce) || $decision_nonce === ''
        || !wp_verify_nonce($decision_nonce, ZMP_President_Subject_Approvals::NONCE)) {
        throw new RuntimeException('same-cookie route nonce setup failed');
    }
    add_filter('pre_http_request', $http_guard, 10, 3);
    $request_started = true;

    // Positive control: the exact cookie and nonce must submit as this Teacher.
    $submit = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout'=>10,'redirection'=>0,'blocking'=>true,'headers'=>$headers,
        'body'=>array('action'=>'zmp_submit_subject_proposal','_wpnonce'=>$teacher_nonce,'zmp_subject_name'=>$subject_name),
    ));
    if (is_wp_error($submit)) { throw new RuntimeException('authenticated Teacher submit control request failed'); }
    $submit_status_code = (int) wp_remote_retrieve_response_code($submit);
    $submit_location = (string) wp_remote_retrieve_header($submit, 'location');
    $submit_parts = $submit_location !== '' ? wp_parse_url($submit_location) : array();
    $submit_query = array();
    if (is_array($submit_parts) && is_string($submit_parts['query'] ?? null)) { parse_str($submit_parts['query'], $submit_query); }
    $raw_submit_status = is_scalar($submit_query['zmp_subject_status'] ?? null) ? sanitize_key((string) $submit_query['zmp_subject_status']) : '';
    $submit_status = in_array($raw_submit_status, array('submitted','forbidden','invalid_subject','schema_not_ready','proposal_pending','proposal_already_decided','database_error'), true)
        ? $raw_submit_status : 'unrecognized';
    echo 'TEACHER_SUBMIT_HTTP_STATUS ' . $submit_status_code . "\n";
    echo 'TEACHER_SUBMIT_STATUS_CLASS ' . $submit_status . "\n";
    $assert('same_cookie_teacher_submit_returns_submitted', $submit_status_code === 302 && $submit_status === 'submitted');

    $owned_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id,proposed_name,status,proposer_id,decided_by,canonical_subject_id FROM {$tables['proposal']} WHERE proposed_name=%s AND proposer_id=%d",
        $subject_name, $teacher_id
    ), ARRAY_A);
    $assert('teacher_submit_created_one_pending_owned_proposal', $wpdb->last_error === '' && is_array($owned_rows)
        && count($owned_rows) === 1 && $owned_rows[0]['status'] === 'pending'
        && (int) $owned_rows[0]['proposer_id'] === $teacher_id && (int) $owned_rows[0]['decided_by'] === 0
        && (int) $owned_rows[0]['canonical_subject_id'] === 0);
    if (is_array($owned_rows) && count($owned_rows) === 1) { $proposal_id = (int) $owned_rows[0]['id']; }

    // Validate only the allowlisted, sanitized authentication and submit diagnostics.
    wp_cache_delete(ZMP_Debug::OPTION, 'options');
    $diag_after_submit_row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", ZMP_Debug::OPTION), ARRAY_A);
    $diag_after_submit = is_array($diag_after_submit_row) ? maybe_unserialize($diag_after_submit_row['option_value']) : false;
    $diag_after_submit_valid = is_array($diag_after_submit) && count($diag_after_submit) === count($debug_events_before) + 2
        && array_slice($diag_after_submit, 0, count($debug_events_before)) === $debug_events_before;
    $auth_event = $diag_after_submit_valid ? $diag_after_submit[count($debug_events_before)] : array();
    $submitted_event = $diag_after_submit_valid ? $diag_after_submit[count($debug_events_before) + 1] : array();
    $auth_context = is_array($auth_event['context'] ?? null) ? $auth_event['context'] : array();
    $submit_context = is_array($submitted_event['context'] ?? null) ? $submitted_event['context'] : array();
    $safe_context_keys = array('form_action','post_fields_count','logged_in','role_count','flow_id','actor_user_id','proposal_id','outcome');
    $auth_context_keys_ok = !array_diff(array_keys($auth_context), $safe_context_keys);
    $submit_context_keys_ok = !array_diff(array_keys($submit_context), $safe_context_keys);
    $auth_context_ok = $auth_context_keys_ok && ($auth_context['form_action'] ?? null) === 'zmp_submit_subject_proposal'
        && (int) ($auth_context['post_fields_count'] ?? 0) === 3 && (int) ($auth_context['logged_in'] ?? 0) === 1
        && (int) ($auth_context['role_count'] ?? 0) === 1 && preg_match('/\A[a-f0-9-]{20,64}\z/iD', (string) ($auth_context['flow_id'] ?? '')) === 1;
    $auth_event_ok = ($auth_event['message'] ?? null) === 'ZMP test request reached WordPress init.'
        && ($auth_event['level'] ?? null) === 'info' && ($auth_event['test_type'] ?? null) === 'teacher_subject_proposals'
        && (int) ($auth_event['user_id'] ?? 0) === $teacher_id && ($auth_event['request'] ?? null) === '/wp-admin/admin-post.php'
        && $auth_context_ok;
    $submit_event_ok = ($submitted_event['message'] ?? null) === 'Subject proposal submitted.'
        && ($submitted_event['level'] ?? null) === 'info' && ($submitted_event['test_type'] ?? null) === 'teacher_subject_proposals'
        && (int) ($submitted_event['user_id'] ?? 0) === $teacher_id && ($submitted_event['request'] ?? null) === '/wp-admin/admin-post.php'
        && $submit_context_keys_ok && (int) ($submit_context['actor_user_id'] ?? 0) === $teacher_id
        && (int) ($submit_context['proposal_id'] ?? 0) === $proposal_id && ($submit_context['outcome'] ?? null) === 'pending';
    $assert('same_cookie_init_diagnostic_proves_logged_in_teacher_role', $auth_event_ok);
    $assert('submit_diagnostic_is_allowlisted_teacher_event', $submit_event_ok);
    $assert('submit_diagnostics_option_row_readable', $wpdb->last_error === '' && is_array($diag_after_submit_row)
        && is_array($diag_after_submit) && $diag_after_submit_valid);
    unset($submit, $submit_location, $submit_parts, $submit_query, $raw_submit_status, $teacher_nonce);

    $proposal_snapshot_after_submit = $snapshot($tables['proposal']);
    $event_snapshot_after_submit = $snapshot($tables['event']);
    $catalog_snapshot_after_submit = $snapshot($tables['catalog']);
    $alias_snapshot_after_submit = $snapshot($tables['alias']);
    $assert('post_submit_subject_snapshots_available', $proposal_snapshot_after_submit !== false
        && $event_snapshot_after_submit !== false && $catalog_snapshot_after_submit !== false && $alias_snapshot_after_submit !== false);

    $page = wp_remote_get(add_query_arg('page', ZMP_President_Subject_Approvals::PAGE, admin_url('admin.php')),
        array('timeout'=>10,'redirection'=>0,'blocking'=>true,'headers'=>$headers));
    if (is_wp_error($page)) { throw new RuntimeException('direct route request failed'); }
    $page_status = (int) wp_remote_retrieve_response_code($page);
    $queue_location = (string) wp_remote_retrieve_header($page, 'location');
    $queue_location_parts = $queue_location !== '' ? wp_parse_url($queue_location) : array();
    $queue_location_path = is_array($queue_location_parts) ? (string) ($queue_location_parts['path'] ?? '') : '';
    if ($queue_location === '') { $queue_location_class = 'none'; }
    elseif ($queue_location_path === '/wp-login.php') { $queue_location_class = 'login'; }
    elseif ($queue_location_path === '/wp-admin/profile.php') { $queue_location_class = 'profile'; }
    elseif (strpos($queue_location_path, '/wp-admin/') === 0) { $queue_location_class = 'admin'; }
    else { $queue_location_class = 'other'; }
    echo 'DIRECT_QUEUE_HTTP_STATUS ' . $page_status . "\n";
    echo 'DIRECT_QUEUE_LOCATION_CLASS ' . $queue_location_class . "\n";
    $page_body = (string) wp_remote_retrieve_body($page);
    $assert('teacher_direct_queue_returns_403_without_redirect', $page_status === 403 && $queue_location_class === 'none');
    $assert('direct_queue_does_not_disclose_subject_or_decision_controls',
        strpos($page_body, $subject_name) === false && strpos($page_body, 'approve_new') === false
        && strpos($page_body, 'approve_alias') === false && strpos($page_body, 'name="decision"') === false
        && strpos($page_body, 'name="proposal_id"') === false
        && strpos($page_body, 'zmp_president_subject_decision') === false);

    $decision = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout'=>10,'redirection'=>0,'blocking'=>true,'headers'=>$headers,
        'body'=>array('action'=>ZMP_President_Subject_Approvals::ACTION,
            '_wpnonce'=>$decision_nonce,'proposal_id'=>$proposal_id,'decision'=>'reject','canonical_subject_id'=>0),
    ));
    if (is_wp_error($decision)) { throw new RuntimeException('direct decision request failed'); }
    $decision_status = (int) wp_remote_retrieve_response_code($decision);
    $decision_location = (string) wp_remote_retrieve_header($decision, 'location');
    $decision_body = (string) wp_remote_retrieve_body($decision);
    echo 'TEACHER_DECISION_HTTP_STATUS ' . $decision_status . "\n";
    echo 'TEACHER_DECISION_LOCATION_CLASS ' . ($decision_location === '' ? 'none' : 'present') . "\n";
    $assert('teacher_valid_nonce_decision_route_returns_403', $decision_status === 403 && $decision_location === '');
    $assert('decision_denial_is_generic', strpos($decision_body, 'Немате дозвола') !== false && strpos($decision_body, $subject_name) === false);

    // Require exactly the two authenticated submit events and one generic
    // President decision-denied event, preserving any unknown suffix on failure.
    wp_cache_delete(ZMP_Debug::OPTION, 'options');
    $diag_after_routes_row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", ZMP_Debug::OPTION), ARRAY_A);
    $diag_after_routes = is_array($diag_after_routes_row) ? maybe_unserialize($diag_after_routes_row['option_value']) : false;
    $diag_suffix_ok = $wpdb->last_error === '' && is_array($diag_after_routes) && count($diag_after_routes) === count($debug_events_before) + 4
        && array_slice($diag_after_routes, 0, count($debug_events_before), true) === $debug_events_before;
    $decision_init_event = $diag_suffix_ok ? $diag_after_routes[count($debug_events_before) + 2] : array();
    $decision_init_context = is_array($decision_init_event['context'] ?? null) ? $decision_init_event['context'] : array();
    $decision_init_context_keys_ok = !array_diff(array_keys($decision_init_context), array('form_action','post_fields_count','logged_in','role_count','flow_id'));
    $decision_init_ok = $diag_suffix_ok && ($decision_init_event['message'] ?? null) === 'ZMP test request reached WordPress init.'
        && ($decision_init_event['level'] ?? null) === 'info' && ($decision_init_event['test_type'] ?? null) === 'teacher_subject_proposals'
        && (int) ($decision_init_event['user_id'] ?? 0) === $teacher_id
        && ($decision_init_event['request'] ?? null) === '/wp-admin/admin-post.php'
        && $decision_init_context_keys_ok
        && ($decision_init_context['form_action'] ?? null) === ZMP_President_Subject_Approvals::ACTION
        && (int) ($decision_init_context['post_fields_count'] ?? 0) === 5
        && (int) ($decision_init_context['logged_in'] ?? 0) === 1
        && (int) ($decision_init_context['role_count'] ?? 0) === 1
        && preg_match('/\A[a-f0-9-]{20,64}\z/iD', (string) ($decision_init_context['flow_id'] ?? '')) === 1;
    $denied_event = $diag_suffix_ok ? $diag_after_routes[count($debug_events_before) + 3] : array();
    $denied_context = is_array($denied_event['context'] ?? null) ? $denied_event['context'] : array();
    $denied_context_keys_ok = !array_diff(array_keys($denied_context), array('error_code','flow_id'));
    $denied_event_ok = $diag_suffix_ok && ($denied_event['message'] ?? null) === 'President subject decision denied.'
        && ($denied_event['level'] ?? null) === 'warning' && ($denied_event['test_type'] ?? null) === 'teacher_subject_proposals'
        && (int) ($denied_event['user_id'] ?? 0) === $teacher_id
        && ($denied_event['request'] ?? null) === '/wp-admin/admin-post.php'
        && $denied_context_keys_ok && ($denied_context['error_code'] ?? null) === 'forbidden'
        && preg_match('/\A[a-f0-9-]{20,64}\z/iD', (string) ($denied_context['flow_id'] ?? '')) === 1;
    $debug_route_suffix = $diag_suffix_ok
        ? array_slice($diag_after_routes, count($debug_events_before), null, true) : null;
    $debug_expected_suffix = $debug_route_suffix;
    $debug_capture_valid = $diag_suffix_ok && $auth_event_ok && $submit_event_ok && $decision_init_ok && $denied_event_ok
        && array_values(array_slice($diag_after_routes, count($debug_events_before), 4, true))
            === array($auth_event, $submitted_event, $decision_init_event, $denied_event);
    if ($debug_capture_valid) { $debug_suffix_hash = hash('sha256', maybe_serialize($debug_route_suffix)); }
    echo 'DECISION_DIAGNOSTIC_CLASS ' . ($denied_event_ok ? 'teacher_forbidden' : 'unrecognized') . "\n";
    echo 'DECISION_INIT_AUTH_CLASS ' . ($decision_init_ok ? 'teacher_logged_in_one_role' : 'unrecognized') . "\n";
    $assert('same_cookie_decision_init_diagnostic_confirms_teacher_auth', $wpdb->last_error === '' && $decision_init_ok);
    $assert('decision_diagnostic_confirms_same_teacher_denied', $wpdb->last_error === '' && $denied_event_ok);

    $row = $wpdb->get_row($wpdb->prepare("SELECT status,proposer_id,decided_by FROM {$tables['proposal']} WHERE id=%d", $proposal_id), ARRAY_A);
    $event_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['event']} WHERE proposal_id=%d", $proposal_id));
    $assert('teacher_valid_nonce_decision_does_not_mutate_proposal_event_or_catalog', $wpdb->last_error === '' && is_array($row)
        && $row['status'] === 'pending' && (int) $row['proposer_id'] === $teacher_id
        && (int) $row['decided_by'] === 0 && $event_count === 1
        && $snapshot($tables['proposal']) === $proposal_snapshot_after_submit
        && $snapshot($tables['event']) === $event_snapshot_after_submit
        && $snapshot($tables['catalog']) === $catalog_snapshot_after_submit
        && $snapshot($tables['alias']) === $alias_snapshot_after_submit);
    $assert('catalog_and_alias_tables_remain_empty',
        (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['catalog']}") === 0
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['alias']}") === 0 && $wpdb->last_error === '');
} catch (Throwable $error) {
    $exception_class = get_class($error);
} finally {
    if ($http_guard) { remove_filter('pre_http_request', $http_guard, 10); }
    $http_guard_removed = true;
    $_COOKIE = $initial_cookie;
    wp_set_current_user($initial_user);
    if ($teacher_id < 1 && $teacher_login !== '') {
        $candidate = get_user_by('login', $teacher_login);
        if ($candidate && $candidate->user_email === $teacher_email && in_array('zmp_teacher', (array) $candidate->roles, true)) {
            $teacher_id = (int) $candidate->ID;
        }
    }
    if ($token_manager && $session_token !== '') { $token_manager->destroy($session_token); }
    $cleanup_ok = true;
    if ($teacher_id > 0) {
        // Locate by this fixture's exact synthetic identity, even if submit()
        // returned an error after one of its inserts. Never sweep by user alone.
        $owned_proposals = $wpdb->get_results($wpdb->prepare(
            "SELECT id,proposed_name,status,proposer_id,decided_by,canonical_subject_id FROM {$tables['proposal']} WHERE proposed_name=%s AND proposer_id=%d",
            $subject_name, $teacher_id
        ), ARRAY_A);
        if ($wpdb->last_error !== '' || !is_array($owned_proposals) || count($owned_proposals) > 1) {
            $cleanup_ok = false;
        } elseif (count($owned_proposals) === 1) {
            $owned_proposal = $owned_proposals[0];
            $owned_id = (int) ($owned_proposal['id'] ?? 0);
            $owned_events = $owned_id > 0 ? $wpdb->get_results($wpdb->prepare(
                "SELECT id,event_type,actor_id,event_data FROM {$tables['event']} WHERE proposal_id=%d",
                $owned_id
            ), ARRAY_A) : false;
            $event_allowed = is_array($owned_events) && count($owned_events) <= 1;
            if (is_array($owned_events) && count($owned_events) === 1) {
                $event_data = json_decode((string) $owned_events[0]['event_data'], true);
                $event_allowed = $owned_events[0]['event_type'] === 'submitted'
                    && (int) $owned_events[0]['actor_id'] === $teacher_id
                    && is_array($event_data) && ($event_data['proposed_name'] ?? '') === $subject_name;
            }
            $proposal_allowed = $owned_id > 0 && $owned_proposal['proposed_name'] === $subject_name
                && $owned_proposal['status'] === 'pending' && (int) $owned_proposal['proposer_id'] === $teacher_id
                && (int) $owned_proposal['decided_by'] === 0 && (int) $owned_proposal['canonical_subject_id'] === 0;
            if ($wpdb->last_error !== '' || !$proposal_allowed || !$event_allowed) {
                $cleanup_ok = false;
            } else {
                if (count($owned_events) === 1) {
                    $deleted_event = $wpdb->delete($tables['event'], array('id'=>(int) $owned_events[0]['id'],'proposal_id'=>$owned_id), array('%d','%d'));
                    $cleanup_ok = $deleted_event === 1 && $cleanup_ok;
                }
                $deleted_proposal = $wpdb->delete($tables['proposal'], array('id'=>$owned_id,'proposed_name'=>$subject_name,'proposer_id'=>$teacher_id,'status'=>'pending'), array('%d','%s','%d','%s'));
                $cleanup_ok = $deleted_proposal === 1 && $cleanup_ok;
            }
        } else {
            // submit() can leave an event only if its proposal write was
            // interrupted. Remove only an exact synthetic submission event
            // whose referenced proposal is absent.
            $possible_events = $wpdb->get_results($wpdb->prepare(
                "SELECT id,proposal_id,event_type,actor_id,event_data FROM {$tables['event']} WHERE actor_id=%d AND event_type=%s",
                $teacher_id, 'submitted'
            ), ARRAY_A);
            $matching_events = array();
            if (!is_array($possible_events) || $wpdb->last_error !== '') {
                $cleanup_ok = false;
            } else {
                foreach ($possible_events as $event) {
                    $event_data = json_decode((string) ($event['event_data'] ?? ''), true);
                    if (is_array($event_data) && ($event_data['proposed_name'] ?? '') === $subject_name) {
                        $matching_events[] = $event;
                    }
                }
                if (count($matching_events) > 1) { $cleanup_ok = false; }
                elseif (count($matching_events) === 1) {
                    $orphan = $matching_events[0];
                    $linked = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tables['proposal']} WHERE id=%d",
                        (int) $orphan['proposal_id']
                    ));
                    if ($wpdb->last_error !== '' || $linked !== 0) { $cleanup_ok = false; }
                    else {
                        $deleted_orphan = $wpdb->delete($tables['event'], array(
                            'id'=>(int) $orphan['id'],'proposal_id'=>(int) $orphan['proposal_id'],
                            'actor_id'=>$teacher_id,'event_type'=>'submitted',
                        ), array('%d','%d','%d','%s'));
                        $cleanup_ok = $deleted_orphan === 1 && $cleanup_ok;
                    }
                }
            }
        }
        $teacher = get_userdata($teacher_id);
        if ($teacher) {
            if ($teacher->user_email === $teacher_email && array_values((array) $teacher->roles) === array('zmp_teacher')) {
                $cleanup_ok = wp_delete_user($teacher_id) && $cleanup_ok;
            } else { $cleanup_ok = false; }
        }
    }
    unset($cookie_header, $auth_cookie, $logged_cookie, $teacher_nonce, $decision_nonce, $headers);
    $debug_cleanup_ok = false;
    $debug_option_before = $diag_before[ZMP_Debug::OPTION] ?? null;
    $current_debug_row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        ZMP_Debug::OPTION
    ), ARRAY_A);
    if ($wpdb->last_error === '' && !$request_started) {
        // Before any route request, diagnostics must still be byte-for-byte unchanged.
        $debug_cleanup_ok = $current_debug_row === $debug_option_before;
    } elseif ($wpdb->last_error === '' && $debug_capture_valid && is_array($current_debug_row)
        && is_array($debug_expected_suffix) && is_string($debug_suffix_hash)) {
        $current_debug_events = maybe_unserialize($current_debug_row['option_value']);
        $current_debug_prefix = is_array($current_debug_events)
            ? array_slice($current_debug_events, 0, count($debug_events_before), true) : false;
        $current_debug_suffix = is_array($current_debug_events)
            ? array_slice($current_debug_events, count($debug_events_before), null, true) : false;
        $owned_debug_suffix = is_array($current_debug_events)
            && $current_debug_prefix === $debug_events_before
            && is_array($current_debug_suffix)
            && $current_debug_suffix === $debug_expected_suffix
            && hash_equals($debug_suffix_hash, hash('sha256', maybe_serialize($current_debug_suffix)));
        if ($owned_debug_suffix) {
            if (is_array($debug_option_before)) {
                $debug_restore = $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND option_value=%s AND autoload=%s",
                    $debug_option_before['option_value'], $debug_option_before['autoload'],
                    ZMP_Debug::OPTION, $current_debug_row['option_value'], $current_debug_row['autoload']
                ));
            } else {
                $debug_restore = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s AND autoload=%s",
                    ZMP_Debug::OPTION, $current_debug_row['option_value'], $current_debug_row['autoload']
                ));
            }
            if ($debug_restore === 1) {
                wp_cache_delete(ZMP_Debug::OPTION, 'options');
                wp_cache_delete('alloptions', 'options');
                wp_cache_delete('notoptions', 'options');
                $restored_debug_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
                    ZMP_Debug::OPTION
                ), ARRAY_A);
                $debug_cleanup_ok = $wpdb->last_error === ''
                    && (is_array($debug_option_before) ? $restored_debug_row === $debug_option_before : $restored_debug_row === null);
            }
        }
    }
    // Capture state is not written by these routes. Preserve any unexpected change.
    $capture_option_before = $diag_before[ZMP_Debug::CAPTURE_OPTION] ?? null;
    $current_capture_row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        ZMP_Debug::CAPTURE_OPTION
    ), ARRAY_A);
    $capture_unchanged = $wpdb->last_error === '' && $current_capture_row === $capture_option_before;
    $diagnostics_restored = $debug_cleanup_ok && $capture_unchanged;
    wp_cache_delete(ZMP_Debug::CAPTURE_OPTION, 'options');
    wp_cache_delete('alloptions', 'options');
    wp_cache_delete('notoptions', 'options');
    $baseline_restored = true;
    foreach ($baseline as $table=>$expected) {
        if ($snapshot($table) !== $expected) { $baseline_restored = false; }
    }
    $cleanup_ok = $cleanup_ok && $diagnostics_restored && $baseline_restored && $http_guard_removed;
}
if ($exception_class !== '') { fwrite(STDERR, "FAIL: controlled route attempt ended with {$exception_class}; no retry.\n"); $failures[] = 'route_execution_completed'; }
echo 'OUTBOUND_GUARD_BLOCKED ' . ($outbound_guard_blocked ? 'yes' : 'no') . "\n";
$checks['request_started'] = $request_started ? 'PASS' : 'FAIL';
if (!$request_started) { $failures[] = 'request_started'; }
$checks['diagnostic_options_exactly_restored'] = $diagnostics_restored ? 'PASS' : 'FAIL';
if (!$diagnostics_restored) { $failures[] = 'diagnostic_options_exactly_restored'; }
$checks['baseline_tables_exactly_restored'] = $baseline_restored ? 'PASS' : 'FAIL';
if (!$baseline_restored) { $failures[] = 'baseline_tables_exactly_restored'; }
$checks['cleanup_and_exact_baseline_restoration'] = $cleanup_ok ? 'PASS' : 'FAIL';
if (!$cleanup_ok) { $failures[] = 'cleanup_and_exact_baseline_restoration'; }
foreach ($checks as $name=>$status) { echo $status . ' ' . $name . "\n"; }
echo 'SUMMARY ' . count(array_filter($checks, static function ($status) { return $status === 'PASS'; })) . '/' . count($checks) . " PASS\n";
exit($failures ? 1 : 0);
