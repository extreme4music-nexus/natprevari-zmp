<?php
/**
 * Verify that the synthetic Teacher cookie authenticates to their own profile,
 * then cannot read or decide through the President subject-approval routes.
 * Run only via WP-CLI on the isolated .65 clone at 127.0.0.1:8100.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_catalog_test' || !isset($wpdb) || $wpdb->prefix !== 'z65_'
    || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65'
    || realpath(ABSPATH) !== '/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site') {
    fwrite(STDERR, "BLOCKED: exact disposable .65 WP-CLI site required.\n"); exit(2);
}
$site = wp_parse_url(home_url('/'));
$admin = wp_parse_url(admin_url('/'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8100 || !is_array($admin)
    || ($admin['scheme'] ?? '') !== 'http' || ($admin['host'] ?? '') !== '127.0.0.1'
    || (int) ($admin['port'] ?? 0) !== 8100) {
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
$http_guard_removed = false;
$request_started = false;
$diagnostics_restored = true;
$baseline_restored = false;
$checks = array();
$failures = array();
$exception_class = '';
$assert = static function ($name, $passed) use (&$checks, &$failures) {
    $checks[$name] = $passed ? 'PASS' : 'FAIL';
    if (!$passed) { $failures[] = $name; }
};
$http_guard = static function ($preempt, $args, $url) {
    $parsed = wp_parse_url((string) $url);
    if (is_array($parsed) && ($parsed['scheme'] ?? '') === 'http' && ($parsed['host'] ?? '') === '127.0.0.1'
        && (int) ($parsed['port'] ?? 0) === 8100
        && in_array(($parsed['path'] ?? ''), array('/wp-admin/profile.php','/wp-admin/admin.php','/wp-admin/admin-post.php'), true)) { return $preempt; }
    return new WP_Error('zmp65_external_http_blocked');
};
try {
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
    $cookie_name = LOGGED_IN_COOKIE;
    $cookie = wp_generate_auth_cookie($teacher_id, $expires, 'logged_in', $session_token);
    $_COOKIE[$cookie_name] = $cookie;
    wp_set_current_user($teacher_id);
    $nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!is_string($nonce) || $nonce === '') { throw new RuntimeException('role-bound nonce setup failed'); }
    $submitted = ZMP_Mentor_Subject_Proposals::submit($teacher_id, $subject_name);
    if (is_wp_error($submitted) || (int) $submitted < 1) { throw new RuntimeException('pending synthetic fixture proposal failed'); }
    $proposal_id = (int) $submitted;

    $headers = array('Cookie'=>$cookie_name . '=' . $cookie, 'Referer'=>home_url('/'));
    add_filter('pre_http_request', $http_guard, 10, 3);
    $request_started = true;

    // Positive control: a recognized Teacher session can render its own profile.
    $profile = wp_remote_get(admin_url('profile.php'),
        array('timeout'=>10,'redirection'=>0,'blocking'=>true,'headers'=>$headers));
    if (is_wp_error($profile)) { throw new RuntimeException('authenticated profile control request failed'); }
    $profile_status = (int) wp_remote_retrieve_response_code($profile);
    $profile_body = (string) wp_remote_retrieve_body($profile);
    echo 'AUTH_CONTROL_HTTP_STATUS ' . $profile_status . "\n";
    $assert('teacher_cookie_authenticates_to_own_profile', $profile_status === 200
        && strpos($profile_body, 'Synthetic Route Denial Teacher') !== false);

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
    $assert('teacher_direct_queue_url_returns_403', $page_status === 403);
    $assert('direct_queue_does_not_disclose_subject_or_decision_controls',
        strpos($page_body, $subject_name) === false && strpos($page_body, 'approve_new') === false
        && strpos($page_body, 'approve_alias') === false && strpos($page_body, 'name="decision"') === false);

    $decision = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout'=>10,'redirection'=>0,'blocking'=>true,'headers'=>$headers,
        'body'=>array('action'=>ZMP_President_Subject_Approvals::ACTION,
            '_wpnonce'=>$nonce,'proposal_id'=>$proposal_id,'decision'=>'reject','canonical_subject_id'=>0),
    ));
    if (is_wp_error($decision)) { throw new RuntimeException('direct decision request failed'); }
    $decision_status = (int) wp_remote_retrieve_response_code($decision);
    $decision_location = (string) wp_remote_retrieve_header($decision, 'location');
    $decision_body = (string) wp_remote_retrieve_body($decision);
    $assert('teacher_valid_nonce_decision_route_returns_403', $decision_status === 403 && $decision_location === '');
    $assert('decision_denial_is_generic', strpos($decision_body, 'Немате дозвола') !== false && strpos($decision_body, $subject_name) === false);

    $row = $wpdb->get_row($wpdb->prepare("SELECT status,proposer_id,decided_by FROM {$tables['proposal']} WHERE id=%d", $proposal_id), ARRAY_A);
    $event_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['event']} WHERE proposal_id=%d", $proposal_id));
    $assert('proposal_remains_pending_with_submission_event_only', $wpdb->last_error === '' && is_array($row)
        && $row['status'] === 'pending' && (int) $row['proposer_id'] === $teacher_id
        && (int) $row['decided_by'] === 0 && $event_count === 1);
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
    foreach ($diag_before as $name=>$before) {
        if (is_array($before)) {
            $restore = $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value),autoload=VALUES(autoload)", $name, $before['option_value'], $before['autoload']));
        } else { $restore = $wpdb->delete($wpdb->options, array('option_name'=>$name), array('%s')); }
        wp_cache_delete($name, 'options');
        $after = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $name), ARRAY_A);
        $diagnostics_restored = $diagnostics_restored && $restore !== false
            && (is_array($before) ? $after === $before : !is_array($after));
    }
    wp_cache_delete('alloptions', 'options');
    $baseline_restored = true;
    foreach ($baseline as $table=>$expected) {
        if ($snapshot($table) !== $expected) { $baseline_restored = false; }
    }
    $cleanup_ok = $cleanup_ok && $diagnostics_restored && $baseline_restored && $http_guard_removed;
}
if ($exception_class !== '') { fwrite(STDERR, "FAIL: controlled route attempt ended with {$exception_class}; no retry.\n"); $failures[] = 'route_execution_completed'; }
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
