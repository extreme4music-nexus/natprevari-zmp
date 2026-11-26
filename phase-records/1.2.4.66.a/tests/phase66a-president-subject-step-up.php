<?php
/**
 * Task 5 TDD RED: a valid route nonce without WebAuthn step-up must not decide a proposal.
 * Synthetic-only fixture. The test creates two users, one mandate and one proposal, posts once
 * to the active local admin-post endpoint, then removes only its owned data.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_President_Subject_Approvals')
    || !class_exists('ZMP_President_Mandate') || !class_exists('ZMP_Mentor_Subject_Proposals')) {
    fwrite(STDERR, "BLOCKED: .66.a Task 5 route/services unavailable\n"); exit(2);
}
global $wpdb;
$users_table = $wpdb->users;
$usermeta_table = $wpdb->usermeta;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$proposal_events_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$mandate_table = ZMP_President_Mandate::table_name();
$mandate_audit_table = ZMP_President_Mandate::audit_table_name();
$role_option = $wpdb->get_blog_prefix() . 'user_roles';
$checks = array(); $failures = array(); $stage = 'preflight';
$teacher_id = 0; $president_id = 0; $mandate_id = 0; $proposal_id = 0; $proposal_key = '';
$auth_cookie = ''; $logged_cookie = ''; $session_token = '';
$original_user = (int) get_current_user_id(); $post_before = $_POST; $request_before = $_REQUEST; $cookie_before = $_COOKIE;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD','PHP_SELF','SCRIPT_NAME');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$count = static function($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function() use ($wpdb, $credential_table) {
    $rows = $wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A);
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$users_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_digest = static function() use ($wpdb, $credential_table, $protected_user) {
    if (!$protected_user) return '';
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} WHERE user_id=%d AND label=%s LIMIT 1",
        $protected_user, 'Synthetic UI review only'
    ), ARRAY_A);
    return is_array($row) ? hash('sha256', serialize($row)) : '';
};
$baseline = array(
    'users' => $count($users_table), 'credentials' => $count($credential_table), 'challenges' => $count($challenge_table),
    'proposals' => $count($proposal_table), 'proposal_events' => $count($proposal_events_table),
    'mandates' => $count($mandate_table), 'mandate_audit' => $count($mandate_audit_table),
    'credential_fp' => $credential_fingerprint(), 'protected_digest' => $protected_digest(),
    'role_option_hash' => hash('sha256', serialize(get_option($role_option, array()))),
);
$assert = static function($ok, $label) use (&$checks, &$failures) { $checks[] = $label; if (!$ok) $failures[] = $label; };
$baseline_expected = $baseline['users'] === 1 && $baseline['credentials'] === 1 && $baseline['challenges'] === 0
    && $baseline['proposals'] === 0 && $baseline['proposal_events'] === 0
    && $baseline['mandates'] === 0 && $baseline['mandate_audit'] === 0
    && $protected_user > 0 && $baseline['protected_digest'] !== '';
$active_plugins = get_option('active_plugins', array());
$today = current_time('Y-m-d');
$term_end = ZMP_President_Mandate::term_end_date($today);
$overlap_count = is_string($term_end) ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$mandate_table} WHERE status IN ('active','scheduled') AND start_date<=%s AND end_date>=%s",
    $term_end, $today
)) : -1;
$president_count = (int) $wpdb->get_var($wpdb->prepare(
    'SELECT COUNT(*) FROM ' . $usermeta_table . ' WHERE meta_key=%s AND meta_value LIKE %s',
    $wpdb->prefix . 'capabilities', '%zmp_president%'
));
$pending_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$proposal_table} WHERE status='pending'");
$schema_ok = ZMP_Mentor_Subject_Proposals::schema_is_compatible()
    && ZMP_President_Mandate::table_name() === $mandate_table;
$preflight_ok = defined('DB_NAME') && DB_NAME === 'zmp66a_isolated' && $wpdb->prefix === 'z66a_'
    && is_array($active_plugins) && $active_plugins === array('zmp-competitions/zmp-competitions.php')
    && $schema_ok && $baseline_expected && $overlap_count === 0 && $president_count === 0 && $pending_count === 0
    && function_exists('wp_remote_post') && defined('AUTH_COOKIE') && defined('LOGGED_IN_COOKIE');
if (!$preflight_ok) {
    fwrite(STDERR, "BLOCKED: strict synthetic-site baseline/route preflight changed; no fixture writes performed\n"); exit(2);
}

// Read-only snapshots of debug options; only test-owned appended entries may be removed.
$missing_sentinel = new stdClass();
$debug_events_before = get_option('zmp_debug_events', $missing_sentinel);
$debug_capture_before = get_option('zmp_debug_capture', $missing_sentinel);
$debug_events_before_hash = hash('sha256', serialize($debug_events_before));
$role_option_before_hash = $baseline['role_option_hash'];
$debug_cleanup_ok = true;
$route_observed = false; $route_status = null; $route_location = '';

try {
    $stage = 'create synthetic users';
    $suffix = bin2hex(random_bytes(8));
    $teacher = wp_insert_user(array(
        'user_login' => 'zmp66a_t5_teacher_' . $suffix,
        'user_pass' => bin2hex(random_bytes(24)),
        'user_email' => 'zmp66a-t5-teacher-' . $suffix . '@example.invalid',
        'role' => 'zmp_teacher',
    ));
    if (is_wp_error($teacher) || !is_int($teacher) || $teacher < 1) throw new RuntimeException('synthetic teacher creation failed');
    $teacher_id = $teacher;
    $president = wp_insert_user(array(
        'user_login' => 'zmp66a_t5_president_' . $suffix,
        'user_pass' => bin2hex(random_bytes(24)),
        'user_email' => 'zmp66a-t5-president-' . $suffix . '@example.invalid',
        'role' => 'subscriber',
    ));
    if (is_wp_error($president) || !is_int($president) || $president < 1) throw new RuntimeException('synthetic President creation failed');
    $president_id = $president;
    $assert(true, 'two uniquely named synthetic users created with registered Teacher/subscriber roles');

    $stage = 'create one synthetic proposal through service';
    wp_set_current_user($teacher_id);
    $proposal_name = 'Synthetic Task5 step-up RED ' . $suffix;
    $submitted = ZMP_Mentor_Subject_Proposals::submit($teacher_id, $proposal_name);
    if (is_wp_error($submitted) || !is_int($submitted) || $submitted < 1) throw new RuntimeException('synthetic proposal service submission failed');
    $proposal_id = $submitted;
    $proposal_row = $wpdb->get_row($wpdb->prepare("SELECT proposal_key,status FROM {$proposal_table} WHERE id=%d", $proposal_id), ARRAY_A);
    if (!is_array($proposal_row) || ($proposal_row['status'] ?? '') !== 'pending' || !is_string($proposal_row['proposal_key'] ?? null)) {
        throw new RuntimeException('synthetic proposal baseline row not found');
    }
    $proposal_key = (string) $proposal_row['proposal_key'];
    $assert(true, 'proposal service created one pending fixture-owned proposal');

    $stage = 'appoint synthetic President through service';
    $admin = get_user_by('login', 'zmp66a_admin');
    if (!$admin instanceof WP_User || !user_can($admin, 'manage_options')) throw new RuntimeException('synthetic administrator actor unavailable');
    wp_set_current_user((int) $admin->ID);
    $appointed = ZMP_President_Mandate::appoint($president_id, $today, $term_end, 'Synthetic Task5 step-up test appointment');
    if (is_wp_error($appointed) || !is_int($appointed) || $appointed < 1) throw new RuntimeException('synthetic mandate appointment failed');
    $mandate_id = $appointed;
    clean_user_cache($president_id);
    $president_user = get_user_by('id', $president_id);
    if (!$president_user instanceof WP_User || array_values((array) $president_user->roles) !== array('zmp_president')
        || !user_can($president_user, 'view_zmp_president_dashboard') || user_can($president_user, 'manage_options')
        || !ZMP_President_Mandate::user_has_active_mandate($president_id)) {
        throw new RuntimeException('service did not establish the exact active synthetic President boundary');
    }
    $assert(true, 'mandate service created active President role/capability without administrator rights');

    $stage = 'prepare one-use synthetic President session and nonce';
    wp_set_current_user($president_id);
    $auth_cookie_cb = static function($cookie) use (&$auth_cookie) { if (is_string($cookie)) $auth_cookie = $cookie; };
    $logged_cookie_cb = static function($cookie) use (&$logged_cookie) { if (is_string($cookie)) $logged_cookie = $cookie; };
    add_action('set_auth_cookie', $auth_cookie_cb, 10, 6);
    add_action('set_logged_in_cookie', $logged_cookie_cb, 10, 6);
    wp_set_auth_cookie($president_id, false, false);
    remove_action('set_auth_cookie', $auth_cookie_cb, 10);
    remove_action('set_logged_in_cookie', $logged_cookie_cb, 10);
    $parsed_session = $logged_cookie !== '' ? wp_parse_auth_cookie($logged_cookie, 'logged_in') : false;
    $session_token = is_array($parsed_session) ? (string) ($parsed_session['token'] ?? '') : '';
    if ($auth_cookie === '' || $logged_cookie === '' || $session_token === ''
        || !WP_Session_Tokens::get_instance($president_id)->verify($session_token)) {
        throw new RuntimeException('synthetic President session cookie/token setup failed');
    }
    $_COOKIE[AUTH_COOKIE] = $auth_cookie;
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    $nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!is_string($nonce) || !wp_verify_nonce($nonce, ZMP_President_Subject_Approvals::NONCE)) {
        throw new RuntimeException('synthetic President route nonce setup failed');
    }
    $route_url = admin_url('admin-post.php');
    $route_parts = wp_parse_url($route_url);
    if (!is_array($route_parts) || ($route_parts['scheme'] ?? '') !== 'http'
        || ($route_parts['host'] ?? '') !== '127.0.0.1' || (int) ($route_parts['port'] ?? 0) !== 8101
        || ($route_parts['path'] ?? '') !== '/wp-admin/admin-post.php') {
        throw new RuntimeException('route target was not the authorized local loopback endpoint');
    }
    $assert(true, 'synthetic President session token and valid action nonce prepared for loopback route');

    $stage = 'invoke actual local admin-post reject route without step-up proof';
    $response = wp_remote_post($route_url, array(
        'timeout' => 12,
        'redirection' => 0,
        'sslverify' => false,
        'headers' => array(
            'Cookie' => AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie,
        ),
        'body' => array(
            'action' => ZMP_President_Subject_Approvals::ACTION,
            '_wpnonce' => $nonce,
            'proposal_id' => (string) $proposal_id,
            'decision' => 'reject',
        ),
    ));
    if (is_wp_error($response)) throw new RuntimeException('loopback admin-post request failed');
    $route_status = (int) wp_remote_retrieve_response_code($response);
    $route_location = (string) wp_remote_retrieve_header($response, 'location');
    $route_observed = $route_status === 302 && strpos($route_location, 'zmp_subject_decision_status=rejected') !== false;
    $assert($route_observed, 'actual authenticated admin-post route returned the normal reject redirect');

    $stage = 'verify pre-step-up decision write and expected RED';
    $decision_row = $wpdb->get_row($wpdb->prepare(
        "SELECT status,decided_by FROM {$proposal_table} WHERE id=%d AND proposal_key=%s",
        $proposal_id, $proposal_key
    ), ARRAY_A);
    $rejected_events = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",
        $proposal_id, $president_id, 'rejected'
    ));
    $current_pending = is_array($decision_row) && ($decision_row['status'] ?? '') === 'pending';
    $no_rejection_event = $rejected_events === 0;
    $assert($current_pending && $no_rejection_event, 'MISSING STEP-UP PROOF must leave proposal pending and append no rejected event');
    $observed_write = is_array($decision_row) && ($decision_row['status'] ?? '') === 'rejected'
        && (int) ($decision_row['decided_by'] ?? 0) === $president_id && $rejected_events === 1;
} catch (Throwable $error) {
    $failures[] = 'fixture stopped during ' . $stage . ' (' . get_class($error) . ')';
} finally {
    // Revoke only this fixture's token before removing its user.
    if ($president_id > 0 && $session_token !== '') {
        WP_Session_Tokens::get_instance($president_id)->destroy($session_token);
    }
    // Delete only event/proposal rows tied to the exact synthetic proposal.
    if ($proposal_id > 0) {
        $wpdb->delete($proposal_events_table, array('proposal_id' => $proposal_id), array('%d'));
        $where = array('id' => $proposal_id);
        $formats = array('%d');
        if ($proposal_key !== '') { $where['proposal_key'] = $proposal_key; $formats[] = '%s'; }
        $wpdb->delete($proposal_table, $where, $formats);
    }
    // Delete only audit and mandate rows created for this exact appointment.
    if ($mandate_id > 0) {
        $wpdb->delete($mandate_audit_table, array('mandate_id' => $mandate_id), array('%d'));
        $wpdb->delete($mandate_table, array('id' => $mandate_id, 'user_id' => $president_id), array('%d','%d'));
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach (array($teacher_id, $president_id) as $owned_user_id) {
        if ($owned_user_id > 0 && get_userdata($owned_user_id)) wp_delete_user($owned_user_id);
        if ($owned_user_id > 0) clean_user_cache($owned_user_id);
    }

    // Remove only diagnostic events tied to the newly created users/proposal; retain unrelated events.
    wp_cache_delete('zmp_debug_events', 'options'); wp_cache_delete('alloptions', 'options');
    $events_after = get_option('zmp_debug_events', $missing_sentinel);
    if ($events_after !== $missing_sentinel && is_array($events_after)) {
        $filtered = array();
        foreach ($events_after as $event) {
            $context = is_array($event) && is_array($event['context'] ?? null) ? $event['context'] : array();
            $event_user = is_array($event) ? (int) ($event['user_id'] ?? 0) : 0;
            $event_proposal = (int) ($context['proposal_id'] ?? 0);
            $owned_event = ($proposal_id > 0 && $event_proposal === $proposal_id)
                || ($event_user > 0 && in_array($event_user, array($teacher_id, $president_id), true));
            if (!$owned_event) $filtered[] = $event;
        }
        if (count($filtered) !== count($events_after)) {
            if ($filtered) update_option('zmp_debug_events', array_values($filtered), false);
            elseif ($debug_events_before === $missing_sentinel) delete_option('zmp_debug_events');
            else update_option('zmp_debug_events', $debug_events_before, false);
        }
    }
    wp_cache_delete('zmp_debug_capture', 'options'); wp_cache_delete('alloptions', 'options');
    $debug_capture_after = get_option('zmp_debug_capture', $missing_sentinel);
    if (serialize($debug_capture_after) !== serialize($debug_capture_before)) {
        if ($debug_capture_before === $missing_sentinel) delete_option('zmp_debug_capture');
        else update_option('zmp_debug_capture', $debug_capture_before, false);
    }
    wp_cache_delete('zmp_debug_events', 'options'); wp_cache_delete('alloptions', 'options');
    $debug_events_final = get_option('zmp_debug_events', $missing_sentinel);
    $debug_cleanup_ok = hash_equals($debug_events_before_hash, hash('sha256', serialize($debug_events_final)));
    if (!$debug_cleanup_ok) $failures[] = 'debug events did not return to exact pre-fixture value';

    wp_set_current_user($original_user);
    $_POST = $post_before; $_REQUEST = $request_before; $_COOKIE = $cookie_before;
    foreach ($server_before as $key => $saved) { if ($saved[0]) $_SERVER[$key] = $saved[1]; else unset($_SERVER[$key]); }
}

$final = array(
    'users' => $count($users_table), 'credentials' => $count($credential_table), 'challenges' => $count($challenge_table),
    'proposals' => $count($proposal_table), 'proposal_events' => $count($proposal_events_table),
    'mandates' => $count($mandate_table), 'mandate_audit' => $count($mandate_audit_table),
    'credential_fp' => $credential_fingerprint(), 'protected_digest' => $protected_digest(),
    'role_option_hash' => hash('sha256', serialize(get_option($role_option, array()))),
);
$baseline_ok = $final['users'] === $baseline['users'] && $final['credentials'] === $baseline['credentials']
    && $final['challenges'] === $baseline['challenges'] && $final['proposals'] === $baseline['proposals']
    && $final['proposal_events'] === $baseline['proposal_events'] && $final['mandates'] === $baseline['mandates']
    && $final['mandate_audit'] === $baseline['mandate_audit']
    && hash_equals($baseline['credential_fp'], $final['credential_fp'])
    && hash_equals($baseline['protected_digest'], $final['protected_digest'])
    && hash_equals($role_option_before_hash, $final['role_option_hash']) && $debug_cleanup_ok
    && (int) get_current_user_id() === $original_user;
if (!$baseline_ok) $failures[] = 'exact synthetic database/role/debug/current-user baseline not restored';

if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): Task5 step-up requirement\n");
    foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . "\n");
    if ($observed_write) fwrite(STDOUT, "EXPECTED RED OBSERVED: valid nonce and active President rejected the proposal without step-up proof; response was normal route redirect and one rejected event was written.\n");
    fwrite(STDOUT, 'CLEANUP ' . ($baseline_ok ? 'PASS' : 'FAIL') . ": users/credentials/challenges/proposals/events/mandates/audit/debug/roles restored\n");
    exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): missing step-up proof denied without decision write\n");
fwrite(STDOUT, 'CLEANUP PASS: exact synthetic baseline and protected credential preserved' . "\n");
exit(0);
