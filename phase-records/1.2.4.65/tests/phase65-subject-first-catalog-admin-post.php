<?php
/**
 * One synthetic first-catalog Teacher subject proposal through the real local
 * admin-post route. Run only on a fresh disposable .65 site/database after
 * exact-hash review. This candidate is not self-authorizing: do not run it on
 * the active phase site or any non-loopback URL.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_subject_test'
    || !isset($wpdb) || $wpdb->prefix !== 'z65s_') {
    fwrite(STDERR, "BLOCKED: expected disposable zmp65_subject_test / z65s_ site.\n"); exit(2);
}
$site = wp_parse_url(home_url('/'));
$endpoint = wp_parse_url(admin_url('admin-post.php'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8097 || !is_array($endpoint)
    || ($endpoint['scheme'] ?? '') !== 'http' || ($endpoint['host'] ?? '') !== '127.0.0.1'
    || (int) ($endpoint['port'] ?? 0) !== 8097 || ($endpoint['path'] ?? '') !== '/wp-admin/admin-post.php'
    || isset($endpoint['user']) || isset($endpoint['pass']) || isset($endpoint['query']) || isset($endpoint['fragment'])) {
    fwrite(STDERR, "BLOCKED: expected exact loopback site and admin-post route.\n"); exit(2);
}
if (!class_exists('ZMP_Mentor_Subject_Proposals') || !class_exists('ZMP_Mentor') || !class_exists('ZMP_Debug')) {
    fwrite(STDERR, "BLOCKED: required .65 proposal route/services are unavailable.\n"); exit(2);
}
require_once ABSPATH . 'wp-admin/includes/user.php';
$administrators = get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$counts = count_users();
if (!is_array($administrators) || count($administrators) !== 1
    || (int) ($counts['total_users'] ?? -1) !== 1) {
    fwrite(STDERR, "BLOCKED: expected a fresh site with one synthetic administrator.\n"); exit(2);
}
$admin_id = (int) $administrators[0];
wp_set_current_user($admin_id);
if (!current_user_can('manage_options') || !ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: administrator or proposal schema precondition is not ready.\n"); exit(2);
}

$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$event_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$catalog_table = $wpdb->prefix . 'zmp_subject_catalog';
$alias_table = $wpdb->prefix . 'zmp_subject_aliases';
$feature_tables = array($proposal_table,$event_table,$catalog_table,$alias_table);
$snapshot_table = static function ($table) use ($wpdb) {
    if (!is_string($table) || !preg_match('/\A[A-Za-z0-9_]+\z/D', $table)) { return false; }
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    if ((string) $exists !== $table) { return false; }
    $rows = $wpdb->get_results('SELECT * FROM `' . esc_sql($table) . '`', ARRAY_A);
    if (!is_array($rows) || $wpdb->last_error !== '') { return false; }
    $encoded = array();
    foreach ($rows as $row) { $encoded[] = wp_json_encode($row); }
    if (in_array(false, $encoded, true)) { return false; }
    sort($encoded, SORT_STRING);
    return hash('sha256', implode("\n", $encoded));
};
$baseline = array(
    $wpdb->users => $snapshot_table($wpdb->users),
    $wpdb->usermeta => $snapshot_table($wpdb->usermeta),
);
foreach ($feature_tables as $table) { $baseline[$table] = $snapshot_table($table); }
foreach ($baseline as $hash) {
    if ($hash === false) { fwrite(STDERR, "BLOCKED: required baseline table could not be snapshotted.\n"); exit(2); }
}
foreach ($feature_tables as $table) {
    $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($table) . '`');
    if ($wpdb->last_error !== '' || $count !== 0) {
        fwrite(STDERR, "BLOCKED: first-catalog route test requires empty proposal, event, catalog and alias tables.\n"); exit(2);
    }
}
$diagnostic_options = array(ZMP_Debug::OPTION, ZMP_Debug::CAPTURE_OPTION);
$diagnostic_baseline = array();
foreach ($diagnostic_options as $option_name) {
    $option_row = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $option_name
    ), ARRAY_A);
    if ($wpdb->last_error !== '') { fwrite(STDERR, "BLOCKED: diagnostics baseline could not be read.\n"); exit(2); }
    $diagnostic_baseline[$option_name] = is_array($option_row) ? $option_row : null;
}
$initial_cookie = $_COOKIE;
$initial_user_id = get_current_user_id();

$teacher_id = 0;
$teacher_login = '';
$teacher_email = '';
$session_token = '';
$session_manager = null;
$proposal_id = 0;
$synthetic_name = '';
$captured_exception = '';
$checks = array();
$failures = array();
$request_attempted = false;
$network_guard_removed = false;
$cleanup_ok = false;
$proposal_key = '';
$allow_local_route = static function ($pre, $args, $url) {
    $parsed = wp_parse_url((string) $url);
    if (is_array($parsed) && ($parsed['scheme'] ?? '') === 'http'
        && ($parsed['host'] ?? '') === '127.0.0.1' && (int) ($parsed['port'] ?? 0) === 8097
        && ($parsed['path'] ?? '') === '/wp-admin/admin-post.php') { return $pre; }
    return new WP_Error('zmp65_external_http_blocked');
};
$assert = static function ($name, $ok) use (&$checks, &$failures) {
    $checks[$name] = $ok ? 'PASS' : 'FAIL';
    if (!$ok) { $failures[] = $name; }
};
$response = null;

try {
    $suffix = strtolower(wp_generate_password(18, false, false));
    $teacher_login = 'zmp65_first_catalog_' . $suffix;
    $teacher_email = $teacher_login . '@example.invalid';
    $synthetic_name = 'Synthetic Subject ' . strtoupper(substr($suffix, 0, 8));

    $inserted = wp_insert_user(array(
        'user_login'=>$teacher_login,
        'user_pass'=>wp_generate_password(40, true, true),
        'user_email'=>$teacher_email,
        'display_name'=>'Synthetic Subject Proposal Teacher',
        'role'=>'zmp_teacher',
    ));
    if (is_wp_error($inserted) || (int) $inserted < 1) {
        throw new RuntimeException('synthetic Teacher account creation failed');
    }
    $teacher_id = (int) $inserted;
    $teacher = get_userdata($teacher_id);
    if (!$teacher || $teacher->user_login !== $teacher_login || $teacher->user_email !== $teacher_email
        || array_values((array) $teacher->roles) !== array('zmp_teacher')) {
        throw new RuntimeException('synthetic Teacher account identity check failed');
    }

    $expires = time() + 300;
    $session_manager = WP_Session_Tokens::get_instance($teacher_id);
    $session_token = $session_manager->create($expires);
    if (!is_string($session_token) || $session_token === '') {
        throw new RuntimeException('synthetic route session could not be created');
    }
    $cookie_name = LOGGED_IN_COOKIE;
    $cookie = wp_generate_auth_cookie($teacher_id, $expires, 'logged_in', $session_token);
    $_COOKIE[$cookie_name] = $cookie;
    wp_set_current_user($teacher_id);
    $nonce = wp_create_nonce('zmp_submit_subject_proposal');
    if (!is_string($nonce) || $nonce === '') { throw new RuntimeException('route nonce could not be created'); }

    add_filter('pre_http_request', $allow_local_route, 10, 3);
    $request_attempted = true;
    $response = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout'=>10,
        'redirection'=>0,
        'blocking'=>true,
        'sslverify'=>false,
        'headers'=>array(
            'Cookie'=>$cookie_name . '=' . $cookie,
            'Referer'=>home_url('/'),
            'Content-Type'=>'application/x-www-form-urlencoded; charset=UTF-8',
        ),
        'body'=>array(
            'action'=>'zmp_submit_subject_proposal',
            '_wpnonce'=>$nonce,
            'zmp_subject_name'=>$synthetic_name,
        ),
    ));
    if (is_wp_error($response)) {
        throw new RuntimeException('local admin-post request returned a transport error');
    }
    $status = (int) wp_remote_retrieve_response_code($response);
    $location = (string) wp_remote_retrieve_header($response, 'location');
    $location_args = array();
    $location_url = wp_parse_url($location);
    if (is_array($location_url) && isset($location_url['query'])) { parse_str($location_url['query'], $location_args); }
    $assert('actual_teacher_admin_post_redirects_302', $status === 302);
    $assert('redirect_reports_submitted_without_subject_text',
        ($location_args['zmp_subject_status'] ?? '') === 'submitted' && strpos($location, $synthetic_name) === false);

    $proposal = $wpdb->get_row($wpdb->prepare(
        "SELECT id,proposed_name,proposal_key,status,proposer_id,decided_by,canonical_subject_id FROM {$proposal_table} WHERE proposer_id=%d AND proposed_name=%s",
        $teacher_id,
        $synthetic_name
    ), ARRAY_A);
    $proposal_id = is_array($proposal) ? (int) ($proposal['id'] ?? 0) : 0;
    $assert('one_pending_proposal_persisted_for_synthetic_teacher',
        $wpdb->last_error === '' && is_array($proposal) && $proposal_id > 0
        && $proposal['proposed_name'] === $synthetic_name && $proposal['status'] === 'pending'
        && (int) $proposal['proposer_id'] === $teacher_id && (int) $proposal['decided_by'] === 0
        && (int) $proposal['canonical_subject_id'] === 0);
    $proposal_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$proposal_table}");
    $events = $proposal_id > 0 ? $wpdb->get_results($wpdb->prepare(
        "SELECT event_type,actor_id,event_data FROM {$event_table} WHERE proposal_id=%d ORDER BY id",
        $proposal_id
    ), ARRAY_A) : array();
    $event_data = is_array($events) && count($events) === 1 ? json_decode((string) $events[0]['event_data'], true) : null;
    $assert('single_submitted_event_matches_teacher_and_proposal',
        $wpdb->last_error === '' && $proposal_total === 1 && is_array($events) && count($events) === 1
        && $events[0]['event_type'] === 'submitted' && (int) $events[0]['actor_id'] === $teacher_id
        && is_array($event_data) && ($event_data['proposed_name'] ?? '') === $synthetic_name);
    $catalog_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$catalog_table}");
    $alias_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$alias_table}");
    $assert('first_catalog_stays_empty_until_president_decision',
        $wpdb->last_error === '' && $catalog_count === 0 && $alias_count === 0);
} catch (Throwable $error) {
    // Keep diagnostics generic: never echo user, proposal, cookie, nonce or SQL values.
    $captured_exception = get_class($error);
}

finally {
    remove_filter('pre_http_request', $allow_local_route, 10);
    $network_guard_removed = true;
    $_COOKIE = $initial_cookie;
    wp_set_current_user($initial_user_id);

    if ($teacher_id < 1 && $teacher_login !== '') {
        $partial = get_user_by('login', $teacher_login);
        if ($partial && $partial->user_email === $teacher_email
            && in_array('zmp_teacher', (array) $partial->roles, true)) { $teacher_id = (int) $partial->ID; }
    }
    if ($session_manager && $session_token !== '') { $session_manager->destroy($session_token); }

    $cleanup_rows_safe = true;
    if ($teacher_id > 0) {
        $owned_proposals = $wpdb->get_results($wpdb->prepare(
            "SELECT id,proposed_name,status,proposer_id FROM {$proposal_table} WHERE proposer_id=%d",
            $teacher_id
        ), ARRAY_A);
        if ($wpdb->last_error !== '' || !is_array($owned_proposals) || count($owned_proposals) > 1) {
            $cleanup_rows_safe = false;
        } else {
            foreach ($owned_proposals as $owned) {
                $owned_id = (int) $owned['id'];
                $owned_events = $wpdb->get_results($wpdb->prepare(
                    "SELECT id,event_type,actor_id,event_data FROM {$event_table} WHERE proposal_id=%d",
                    $owned_id
                ), ARRAY_A);
                $safe_proposal = $owned_id > 0 && $owned['proposed_name'] === $synthetic_name
                    && $owned['status'] === 'pending' && (int) $owned['proposer_id'] === $teacher_id;
                $safe_events = is_array($owned_events) && count($owned_events) === 1
                    && $owned_events[0]['event_type'] === 'submitted'
                    && (int) $owned_events[0]['actor_id'] === $teacher_id;
                if (!$safe_proposal || !$safe_events || $wpdb->last_error !== '') {
                    $cleanup_rows_safe = false;
                    continue;
                }
                $event_id = (int) $owned_events[0]['id'];
                $deleted_event = $wpdb->delete($event_table, array('id'=>$event_id,'proposal_id'=>$owned_id), array('%d','%d'));
                $deleted_proposal = $wpdb->delete($proposal_table, array('id'=>$owned_id,'proposer_id'=>$teacher_id), array('%d','%d'));
                if ($deleted_event !== 1 || $deleted_proposal !== 1) { $cleanup_rows_safe = false; }
            }
        }
        if ($cleanup_rows_safe) {
            $teacher = get_userdata($teacher_id);
            if ($teacher && $teacher->user_email === $teacher_email
                && array_values((array) $teacher->roles) === array('zmp_teacher')) {
                if (!wp_delete_user($teacher_id)) { $cleanup_rows_safe = false; }
            } elseif ($teacher) {
                $cleanup_rows_safe = false;
            }
        }
    }

    // Restore diagnostics event/capture rows exactly; SQL preserves serialized bytes and autoload state.
    $debug_restored = true;
    foreach ($diagnostic_baseline as $option_name=>$before) {
        if (is_array($before)) {
            $restored = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value),autoload=VALUES(autoload)",
                $option_name,
                $before['option_value'],
                $before['autoload']
            ));
        } else {
            $restored = $wpdb->delete($wpdb->options, array('option_name'=>$option_name), array('%s'));
        }
        wp_cache_delete($option_name, 'options');
        $after = $wpdb->get_row($wpdb->prepare(
            "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
            $option_name
        ), ARRAY_A);
        $debug_restored = $debug_restored && $restored !== false
            && (is_array($before) ? is_array($after) && $after === $before : !is_array($after));
    }
    wp_cache_delete('alloptions', 'options');

    $baseline_restored = true;
    foreach ($baseline as $table=>$expected) {
        if ($snapshot_table($table) !== $expected) { $baseline_restored = false; }
    }
    $cleanup_ok = $cleanup_rows_safe && $debug_restored && $baseline_restored && $network_guard_removed;
}

if ($captured_exception !== '') {
    fwrite(STDERR, "FAIL: local subject proposal route attempt ended with " . $captured_exception . "; no request retry was performed.\n");
    $failures[] = 'route_attempt_completed';
}
if (!$request_attempted) { $failures[] = 'route_request_started'; }
$checks['exact_cleanup_and_baseline_restoration'] = $cleanup_ok ? 'PASS' : 'FAIL';
if (!$cleanup_ok) { $failures[] = 'exact_cleanup_and_baseline_restoration'; }
$checks['external_http_guard_removed'] = $network_guard_removed ? 'PASS' : 'FAIL';
if (!$network_guard_removed) { $failures[] = 'external_http_guard_removed'; }
foreach ($checks as $name=>$status) { echo $status . ' ' . $name . "\n"; }
echo 'SUMMARY ' . count(array_filter($checks, static function ($status) { return $status === 'PASS'; }))
    . '/' . count($checks) . " PASS\n";
exit($failures ? 1 : 0);
