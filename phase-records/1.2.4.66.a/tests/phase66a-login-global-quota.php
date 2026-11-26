<?php
/**
 * Expected-RED acceptance fixture for the aggregate 500/300-second anonymous login quota.
 * Run only after the exact .66.a schema-v3 candidate is synced to its synthetic site.
 * It seeds 500 uniquely keyed, fixture-owned login issuance rows, performs one real
 * nopriv options request, and removes only those exact seeded hashes in finally.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')) {
    fwrite(STDERR, "BLOCKED: active .66.a passkey components unavailable\n"); exit(2);
}
global $wpdb;
$table = $wpdb->prefix . 'zmp_passkey_challenges';
$marker = get_option('zmp_passkey_schema_version');
$peer_column = $wpdb->get_row($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", 'peer_hash'), ARRAY_A);
if ($marker !== '3' || !is_array($peer_column)
    || strtolower((string) ($peer_column['Type'] ?? '')) !== 'char(64)'
    || (string) ($peer_column['Null'] ?? '') !== 'NO'
    || (string) ($peer_column['Default'] ?? null) !== '') {
    fwrite(STDERR, "BLOCKED: requires schema-v3 peer_hash CHAR(64) NOT NULL DEFAULT empty; no rows changed\n"); exit(2);
}
if (!ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: active schema is not compatible; no rows changed\n"); exit(2);
}
$checks = array(); $failures = array(); $owned_hashes = array();
$user_table = $wpdb->users; $credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$before_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$before_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$before_challenges = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$user_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_before = $protected_user ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only'
)) : 0;
$credentials_before = hash('sha256', serialize((array) $wpdb->get_results(
    "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A
)));
$original_user = (int) get_current_user_id(); $original_post = $_POST;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$assert = static function ($ok, $name) use (&$checks, &$failures) { $checks[] = $name; if (!$ok) $failures[] = $name; };
$parts = wp_parse_url(home_url('/')); $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
$host = strtolower(rtrim((string) ($parts['host'] ?? '127.0.0.1'), '.')); $port = isset($parts['port']) ? (int) $parts['port'] : 80;
$default_port = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
$origin = $scheme . '://' . $host . ($port !== null && !$default_port ? ':' . $port : '');
$remote_ip = '127.0.0.201';
$generic = static function ($body, $status) {
    $json = json_decode($body, true);
    return $status === 400 && is_array($json) && array_keys($json) === array('success','data')
        && ($json['success'] ?? null) === false
        && ($json['data']['message'] ?? null) === __('Passkey sign-in could not be completed. Try again or use your password.', 'zmp-competitions');
};
$seeded = 0; $response_body = ''; $response_status = null;
try {
    wp_set_current_user(0);
    $live_before = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE ceremony=%s AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 300 SECOND) AND expires_at > UTC_TIMESTAMP()",
        'login'
    ));
    if ($live_before !== 0 || $before_challenges !== 0) {
        throw new RuntimeException('synthetic challenge baseline is not empty; refusing quota fixture');
    }
    $context = hash('sha256', "zmp-passkey-login-origin-v1\n" . $origin . "\n" . $host);
    $created = gmdate('Y-m-d H:i:s'); $expires = gmdate('Y-m-d H:i:s', time() + 300);
    for ($i = 0; $i < 500; $i++) {
        $challenge_hash = hash('sha256', random_bytes(32));
        $synthetic_ip = '10.246.' . (int) floor($i / 250) . '.' . (string) (($i % 250) + 1);
        $packed = @inet_pton($synthetic_ip);
        if (!is_string($packed)) throw new RuntimeException('synthetic IP fixture generation failed');
        $peer_hash = hash_hmac('sha256', inet_ntop($packed), wp_salt('auth'));
        $inserted = $wpdb->insert($table, array(
            'challenge_hash' => $challenge_hash, 'user_id' => 0, 'session_hash' => '', 'ceremony' => 'login',
            'action' => '', 'resource_id' => 0, 'decision' => '', 'context_hash' => $context,
            'peer_hash' => $peer_hash, 'created_at' => $created, 'expires_at' => $expires, 'consumed_at' => null,
        ), array('%s','%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s'));
        if ($inserted !== 1) throw new RuntimeException('synthetic quota row insertion failed');
        $owned_hashes[] = $challenge_hash;
        $seeded++;
    }
    $assert($seeded === 500, 'exactly 500 fixture-owned live login rows were seeded');
    $_SERVER['HTTP_HOST'] = $host . ':' . $port; $_SERVER['REQUEST_SCHEME'] = $scheme;
    $_SERVER['SERVER_PORT'] = (string) $port; if ($scheme === 'https') $_SERVER['HTTPS'] = 'on'; else unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN'] = $origin; $_SERVER['HTTP_REFERER'] = $origin . '/wp-admin/admin-ajax.php';
    $_SERVER['REMOTE_ADDR'] = $remote_ip; $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array('action' => 'zmp_passkey_login_options');
    $status_cb = static function ($header, $code) use (&$response_status) { $response_status = is_numeric($code) ? (int) $code : null; return $header; };
    $die_cb = static function () { return static function ($message = '', $title = '', $args = array()) {
        throw new RuntimeException('synthetic ajax response captured');
    }; };
    $ajax_cb = static function () { return true; };
    add_filter('status_header', $status_cb, 10, 2); add_filter('wp_die_ajax_handler', $die_cb); add_filter('wp_die_json_handler', $die_cb); add_filter('wp_doing_ajax', $ajax_cb);
    ob_start(); try { do_action('wp_ajax_nopriv_zmp_passkey_login_options'); } catch (Throwable $ignored) { }
    $response_body = (string) ob_get_clean();
    remove_filter('status_header', $status_cb, 10); remove_filter('wp_die_ajax_handler', $die_cb); remove_filter('wp_die_json_handler', $die_cb); remove_filter('wp_doing_ajax', $ajax_cb);
    $response_json = json_decode($response_body, true);
    $response_challenge_id = $response_json['data']['challenge_id'] ?? null;
    if (($response_json['success'] ?? null) === true && is_string($response_challenge_id) && $response_challenge_id !== '') {
        $owned_hashes[] = hash('sha256', $response_challenge_id);
    }
    $after_seed_and_request = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $assert($generic($response_body, $response_status), '501st aggregate issuance receives fixed generic HTTP 400 denial');
    $assert($after_seed_and_request === 500, 'aggregate denial inserts no 501st challenge row');
    $assert(!is_user_logged_in() && (int) get_current_user_id() === 0, 'anonymous quota denial creates no WordPress session');
    fwrite(STDOUT, 'OBSERVED seeded_rows=' . $seeded . '; options_status=' . (string) $response_status
        . '; challenge_rows_after_request=' . $after_seed_and_request . "\n");
} catch (Throwable $error) {
    $failures[] = 'fixture execution failed or was safely refused';
    fwrite(STDERR, "BLOCKED/FAIL: aggregate quota fixture stopped before acceptance result\n");
} finally {
    if ($owned_hashes) {
        $placeholders = implode(',', array_fill(0, count($owned_hashes), '%s'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE challenge_hash IN ({$placeholders})", $owned_hashes));
    }
    wp_set_current_user($original_user); $_POST = $original_post;
    foreach ($server_before as $key => $state) { if ($state[0]) $_SERVER[$key] = $state[1]; else unset($_SERVER[$key]); }
}
$after_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$after_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$after_challenges = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$protected_after = $protected_user ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only'
)) : 0;
$credentials_after = hash('sha256', serialize((array) $wpdb->get_results(
    "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A
)));
$cleanup = $after_users === $before_users && $after_credentials === $before_credentials && $after_challenges === $before_challenges
    && $protected_after === $protected_before && hash_equals($credentials_before, $credentials_after);
$checks[] = 'cleanup restores users, all credential material, challenge count and protected UI row';
if (!$cleanup) $failures[] = 'cleanup baseline differs';
if ($failures) { fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): aggregate login quota\n"); foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . "\n"); fwrite(STDOUT, 'CLEANUP ' . ($cleanup ? 'PASS' : 'FAIL') . "\n"); exit(1); }
fwrite(STDOUT, 'PASS (' . count($checks) . "): aggregate login quota rejects issuance 501\nCLEANUP PASS\n"); exit(0);
