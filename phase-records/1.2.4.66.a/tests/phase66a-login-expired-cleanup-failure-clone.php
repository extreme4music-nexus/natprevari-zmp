<?php
/**
 * Focused fail-closed check for expired passkey challenge cleanup failure.
 * Intercepts only the exact expired-row DELETE and substitutes one invalid SQL
 * statement. Run only on the phase-owned synthetic site with an empty challenge table.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')) {
    fwrite(STDERR, "BLOCKED: active .66.a passkey components unavailable\n"); exit(2);
}
$expected_fixture = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-login-expired-cleanup-failure-clone.php';
$invoked_fixture = isset($args[0]) ? (string) $args[0] : '';
if (rtrim((string) realpath(ABSPATH), '/') !== '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site'
    || DB_NAME !== 'zmp66a_isolated' || DB_HOST !== 'localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock'
    || $GLOBALS['wpdb']->prefix !== 'z66a_'
    || $invoked_fixture !== $expected_fixture
    || realpath($invoked_fixture) !== $expected_fixture
    || get_option('zmp_passkey_schema_version') !== '3'
    || !ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: fixture requires the exact synthetic .66.a schema-v3 site; no rows changed\n"); exit(2);
}

global $wpdb;
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$user_table = $wpdb->users;
$count = static function ($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function () use ($wpdb, $credential_table) {
    return hash('sha256', serialize((array) $wpdb->get_results(
        "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A
    )));
};
$baseline_users = $count($user_table);
$baseline_credentials = $count($credential_table);
$baseline_challenges = $count($challenge_table);
$baseline_credential_fingerprint = $credential_fingerprint();
$protected_user = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT ID FROM {$user_table} WHERE user_login=%s", 'zmp66a_admin'
));
$protected_before = $protected_user ? (array) $wpdb->get_col($wpdb->prepare(
    "SELECT credential_id_hash FROM {$credential_table} WHERE user_id=%d AND label=%s ORDER BY id",
    $protected_user, 'Synthetic UI review only'
)) : array();
if ($baseline_users !== 1 || $baseline_credentials !== 1 || $baseline_challenges !== 0
    || count($protected_before) !== 1) {
    fwrite(STDERR, "BLOCKED: synthetic baseline is not the expected one-user/one-credential/zero-challenge state\n"); exit(2);
}

$checks = array(); $failures = array(); $owned_hash = ''; $row_inserted = false;
$filter_added = false; $status = null; $body = ''; $intercepted = 0;
$original_user = (int) get_current_user_id(); $original_post = $_POST;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$old_suppression = (bool) $wpdb->suppress_errors;
$old_last_query = $wpdb->last_query; $old_last_error = $wpdb->last_error;
$assert = static function ($condition, $label) use (&$checks, &$failures) { $checks[] = $label; if (!$condition) $failures[] = $label; };
$parts = wp_parse_url(home_url('/'));
$scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
$host = strtolower(rtrim((string) ($parts['host'] ?? '127.0.0.1'), '.'));
$port = isset($parts['port']) ? (int) $parts['port'] : (($scheme === 'https') ? 443 : 80);
$default_port = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
$origin = $scheme . '://' . $host . ($default_port ? '' : ':' . $port);
$ip = '127.0.0.203'; $packed_ip = @inet_pton($ip);
$peer_hash = is_string($packed_ip) ? hash_hmac('sha256', inet_ntop($packed_ip), wp_salt('auth')) : '';
$challenge_id = hash('sha256', random_bytes(32));
$context_hash = hash('sha256', "zmp-passkey-login-origin-v1\n" . $origin . "\n" . $host);
$cleanup_sql = 'DELETE FROM ' . $challenge_table . ' WHERE expires_at <= UTC_TIMESTAMP()';
$query_filter = static function ($query) use ($cleanup_sql, &$intercepted) {
    if (is_string($query) && $query === $cleanup_sql) {
        $intercepted++;
        return 'ZMP66A_TEST_FORCE_EXPIRED_CLEANUP_FAILURE';
    }
    return $query;
};
$generic_failure = static function ($captured_body, $captured_status) {
    $json = json_decode($captured_body, true);
    return $captured_status === 400 && is_array($json)
        && array_keys($json) === array('success', 'data')
        && ($json['success'] ?? null) === false
        && ($json['data']['message'] ?? null) === __('Passkey sign-in could not be completed. Try again or use your password.', 'zmp-competitions');
};

try {
    $inserted = $wpdb->insert($challenge_table, array(
        'challenge_hash' => $challenge_id,
        'user_id' => 0,
        'session_hash' => '',
        'ceremony' => 'login',
        'action' => '',
        'resource_id' => 0,
        'decision' => '',
        'context_hash' => $context_hash,
        'peer_hash' => $peer_hash,
        'created_at' => gmdate('Y-m-d H:i:s', time() - 600),
        'expires_at' => gmdate('Y-m-d H:i:s', time() - 300),
        'consumed_at' => null,
    ), array('%s','%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s'));
    if ($inserted !== 1) throw new RuntimeException('could not create the exact expired synthetic row');
    $row_inserted = true;
    $owned_hash = $challenge_id;

    wp_set_current_user(0);
    $_SERVER['HTTP_HOST'] = $host . ':' . $port;
    $_SERVER['REQUEST_SCHEME'] = $scheme;
    $_SERVER['SERVER_PORT'] = (string) $port;
    if ($scheme === 'https') $_SERVER['HTTPS'] = 'on'; else unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN'] = $origin;
    $_SERVER['HTTP_REFERER'] = $origin . '/wp-admin/admin-ajax.php';
    $_SERVER['REMOTE_ADDR'] = $ip;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = array('action' => 'zmp_passkey_login_options');

    $status_filter = static function ($header, $code) use (&$status) { $status = is_numeric($code) ? (int) $code : null; return $header; };
    $die_filter = static function () { return static function ($message = '', $title = '', $args = array()) {
        throw new RuntimeException('synthetic ajax response captured');
    }; };
    $ajax_filter = static function () { return true; };
    add_filter('query', $query_filter, PHP_INT_MAX);
    $filter_added = true;
    add_filter('status_header', $status_filter, 10, 2);
    add_filter('wp_die_ajax_handler', $die_filter);
    add_filter('wp_die_json_handler', $die_filter);
    add_filter('wp_doing_ajax', $ajax_filter);
    $wpdb->suppress_errors(true);
    ob_start();
    try { do_action('wp_ajax_nopriv_zmp_passkey_login_options'); } catch (Throwable $ignored) { }
    $body = (string) ob_get_clean();
    remove_filter('status_header', $status_filter, 10);
    remove_filter('wp_die_ajax_handler', $die_filter);
    remove_filter('wp_die_json_handler', $die_filter);
    remove_filter('wp_doing_ajax', $ajax_filter);

    $row_count_after = $count($challenge_table);
    $assert($intercepted === 1, 'only the exact expired-row cleanup query was intercepted with invalid SQL');
    $assert($generic_failure($body, $status), 'cleanup failure returns the fixed generic HTTP 400 response');
    $assert($row_count_after === 1, 'cleanup failure inserts no new login challenge');
    $assert(!is_user_logged_in() && (int) get_current_user_id() === 0, 'cleanup failure creates no WordPress session');
    fwrite(STDOUT, 'OBSERVED cleanup_interceptions=' . $intercepted . '; generic_http_400=' . ($generic_failure($body, $status) ? 'true' : 'false')
        . '; challenge_rows_after_request=' . $row_count_after . "\n");
} catch (Throwable $error) {
    $failures[] = 'fixture execution failed before the cleanup acceptance result';
    fwrite(STDERR, "BLOCKED/FAIL: expired cleanup failure behavior check stopped\n");
} finally {
    if (isset($status_filter)) remove_filter('status_header', $status_filter, 10);
    if (isset($die_filter)) { remove_filter('wp_die_ajax_handler', $die_filter); remove_filter('wp_die_json_handler', $die_filter); }
    if (isset($ajax_filter)) remove_filter('wp_doing_ajax', $ajax_filter);
    if ($filter_added) { remove_filter('query', $query_filter, PHP_INT_MAX); $filter_added = false; }
    $wpdb->suppress_errors($old_suppression);
    if ($row_inserted && $owned_hash !== '') {
        $wpdb->delete($challenge_table, array('challenge_hash' => $challenge_id), array('%s'));
    }
    wp_set_current_user($original_user);
    $_POST = $original_post;
    foreach ($server_before as $key => $state) { if ($state[0]) $_SERVER[$key] = $state[1]; else unset($_SERVER[$key]); }
    $wpdb->last_query = $old_last_query;
    $wpdb->last_error = $old_last_error;
}

$after_users = $count($user_table);
$after_credentials = $count($credential_table);
$after_challenges = $count($challenge_table);
$after_protected = $protected_user ? (array) $wpdb->get_col($wpdb->prepare(
    "SELECT credential_id_hash FROM {$credential_table} WHERE user_id=%d AND label=%s ORDER BY id",
    $protected_user, 'Synthetic UI review only'
)) : array();
$cleanup = $after_users === $baseline_users && $after_credentials === $baseline_credentials
    && $after_challenges === $baseline_challenges && hash_equals($baseline_credential_fingerprint, $credential_fingerprint())
    && $after_protected === $protected_before && !$filter_added && !has_filter('query', $query_filter);
$checks[] = 'cleanup restores all baseline counts/fingerprints and removes the exact query filter';
if (!$cleanup) $failures[] = 'fixture cleanup changed a protected baseline';
if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): expired challenge cleanup failure\n");
    foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . "\n");
    fwrite(STDOUT, 'CLEANUP ' . ($cleanup ? 'PASS' : 'FAIL') . "\n"); exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): expired cleanup failure fails closed\nCLEANUP PASS\n"); exit(0);
