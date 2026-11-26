<?php
/**
 * One synthetic, loopback-only HTTP POST for the school-email request route.
 * Run with: wp eval-file tests/phase65-school-email-admin-post-smoke.php --allow-root
 * The script creates one request through admin-post.php and removes only that
 * request and its event rows, plus the temporary authentication session token.
 */

global $wpdb;
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_isolated'
    || !isset($wpdb) || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "BLOCKED: expected disposable zmp65_isolated / z65_ site.\n");
    exit(2);
}

$site = wp_parse_url(home_url('/'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http'
    || ($site['host'] ?? '') !== '127.0.0.1' || (int) ($site['port'] ?? 0) !== 8094) {
    fwrite(STDERR, "BLOCKED: site URL is not the authorized loopback endpoint.\n");
    exit(2);
}

if (!class_exists('ZMP_School_Email_Access') || !class_exists('ZMP_Mentor_School_Profile')
    || !class_exists('ZMP_Mentor_Schools')) {
    fwrite(STDERR, "BLOCKED: required plugin services are unavailable.\n");
    exit(2);
}

$requester_id = 31;
$owner_id = 30;
$requester_mentor = ZMP_Mentor_School_Profile::mentor_for_user($requester_id);
$owner_mentor = ZMP_Mentor_School_Profile::mentor_for_user($owner_id);
if (!$requester_mentor || !$owner_mentor
    || !ZMP_Mentor_School_Profile::is_teacher($requester_id)
    || !ZMP_Mentor_School_Profile::is_teacher($owner_id)) {
    fwrite(STDERR, "BLOCKED: expected synthetic Teacher fixtures are unavailable.\n");
    exit(2);
}

$requester_schools = $wpdb->get_results($wpdb->prepare(
    'SELECT id,school_key FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND is_active=1',
    (int) $requester_mentor->id
));
$owner_schools = $wpdb->get_results($wpdb->prepare(
    'SELECT id,school_key FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND is_active=1',
    (int) $owner_mentor->id
));
$shared = null;
foreach ((array) $requester_schools as $requester_school) {
    foreach ((array) $owner_schools as $owner_school) {
        if (hash_equals((string) $requester_school->school_key, (string) $owner_school->school_key)) {
            $shared = array('requester' => $requester_school, 'owner' => $owner_school);
            break 2;
        }
    }
}
if (!$shared) {
    fwrite(STDERR, "BLOCKED: no shared active synthetic school fixture.\n");
    exit(2);
}

$request_table = $wpdb->prefix . 'zmp_school_email_requests';
$event_table = $wpdb->prefix . 'zmp_school_email_request_events';
$existing = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$request_table} WHERE requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND status IN ('pending','approved')",
    $requester_id,
    $owner_id,
    (int) $shared['owner']->id
));
if ($wpdb->last_error !== '' || $existing !== 0) {
    fwrite(STDERR, "BLOCKED: reverse-direction request already exists or DB read failed.\n");
    exit(2);
}
$request_id_before = (int) $wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$request_table}");
if ($wpdb->last_error !== '') {
    fwrite(STDERR, "BLOCKED: request-table baseline could not be read.\n");
    exit(2);
}
$pending_key = hash('sha256', $requester_id . ':' . $owner_id . ':' . (int) $shared['owner']->id);
$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
$mu_guard = $mu_dir . '/zmp65-no-mail-route-test.php';
$mail_marker = $mu_dir . '/.zmp65-no-mail-route-test-hit';
$content_real = realpath(WP_CONTENT_DIR);
$mu_dir_created = false;
$mu_dir_removed = true;
$mu_guard_source = "<?php\nadd_filter('pre_wp_mail', static function (\$short_circuit, \$atts) {\n    \$marker = __DIR__ . '/.zmp65-no-mail-route-test-hit';\n    if (file_put_contents(\$marker, 'blocked', LOCK_EX) !== 7) {\n        if (!headers_sent()) { header('X-ZMP-Mail-Guard: marker-write-failed'); }\n        throw new RuntimeException('test mail guard marker write failed');\n    }\n    return false;\n}, PHP_INT_MAX, 2);\n";
$mu_guard_hash = hash('sha256', $mu_guard_source);
if (!$content_real || is_link($mu_dir) || (file_exists($mu_dir) && !is_dir($mu_dir))
    || (is_dir($mu_dir) && (!$mu_dir_real = realpath($mu_dir)
        || strpos(wp_normalize_path($mu_dir_real), wp_normalize_path($content_real) . '/') !== 0))
    || file_exists($mu_guard) || file_exists($mail_marker)) {
    fwrite(STDERR, "BLOCKED: temporary mail guard path is unavailable or already occupied.\n");
    exit(2);
}
$token_expiration = time() + 300;
$session_manager = WP_Session_Tokens::get_instance($requester_id);
$session_token = $session_manager->create($token_expiration);
if (!is_string($session_token) || $session_token === '') {
    fwrite(STDERR, "FAIL: temporary authentication session could not be created.\n");
    exit(1);
}
if (!is_dir($mu_dir)) {
    if (!mkdir($mu_dir, 0700)) {
        $session_manager->destroy($session_token);
        $session_cleanup_ok = !$session_manager->verify($session_token);
        fwrite(STDERR, 'BLOCKED: temporary mail guard directory could not be created; session cleanup '
            . ($session_cleanup_ok ? 'verified' : 'FAILED') . ".\n");
        exit(2);
    }
    $mu_dir_created = true;
    $mu_dir_real = realpath($mu_dir);
    if (!$mu_dir_real || !$content_real
        || strpos(wp_normalize_path($mu_dir_real), wp_normalize_path($content_real) . '/') !== 0) {
        $mu_dir_removed = rmdir($mu_dir) && !file_exists($mu_dir);
        $session_manager->destroy($session_token);
        $session_cleanup_ok = !$session_manager->verify($session_token);
        fwrite(STDERR, 'BLOCKED: temporary mail guard directory resolved outside wp-content; directory cleanup '
            . ($mu_dir_removed ? 'verified' : 'FAILED') . '; session cleanup '
            . ($session_cleanup_ok ? 'verified' : 'FAILED') . ".\n");
        exit(2);
    }
}
if (file_put_contents($mail_marker, 'not-called', LOCK_EX) !== 10
    || file_get_contents($mail_marker) !== 'not-called') {
    $marker_cleanup_ok = !file_exists($mail_marker);
    if (file_exists($mail_marker) && file_get_contents($mail_marker) === 'not-called') {
        $marker_cleanup_ok = unlink($mail_marker) && !file_exists($mail_marker);
    }
    if ($mu_dir_created) {
        $mu_dir_removed = rmdir($mu_dir) && !file_exists($mu_dir);
    }
    $session_manager->destroy($session_token);
    $session_cleanup_ok = !$session_manager->verify($session_token);
    fwrite(STDERR, 'BLOCKED: temporary mail guard marker cannot be initialized safely; session cleanup '
        . ($session_cleanup_ok ? 'verified' : 'FAILED') . '; marker cleanup '
        . ($marker_cleanup_ok ? 'verified' : 'FAILED') . '; directory cleanup '
        . ($mu_dir_removed ? 'verified' : 'FAILED') . ".\n");
    exit(2);
}

$created_request_id = 0;
$route_ok = false;
$cleanup_ok = false;
$guard_removed = false;
$marker_removed = false;
$session_removed = false;
$mail_blocked = false;
$transport_ambiguous = false;
$route_code = 0;
try {
    $cookie_value = wp_generate_auth_cookie($requester_id, $token_expiration, 'logged_in', $session_token);
    $cookie_name = defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : '';
    if ($cookie_name === '' || $cookie_value === '') {
        throw new RuntimeException('temporary authentication cookie generation failed');
    }

    $_COOKIE[$cookie_name] = $cookie_value;
    wp_set_current_user($requester_id);
    $nonce = wp_create_nonce('zmp_school_email_request_submit');
    $endpoint = admin_url('admin-post.php');
    $parsed_endpoint = wp_parse_url($endpoint);
    if (!is_array($parsed_endpoint) || ($parsed_endpoint['host'] ?? '') !== '127.0.0.1'
        || (int) ($parsed_endpoint['port'] ?? 0) !== 8094
        || ($parsed_endpoint['path'] ?? '') !== '/wp-admin/admin-post.php') {
        throw new RuntimeException('admin-post endpoint is outside the authorized loopback route');
    }

    if (file_put_contents($mu_guard, $mu_guard_source, LOCK_EX) !== strlen($mu_guard_source)
        || !hash_equals($mu_guard_hash, (string) hash_file('sha256', $mu_guard))) {
        throw new RuntimeException('temporary mail guard could not be installed safely');
    }

    $response = wp_remote_post($endpoint, array(
        'timeout' => 15,
        'redirection' => 0,
        'headers' => array('Cookie' => $cookie_name . '=' . $cookie_value),
        'body' => array(
            'action' => ZMP_School_Email_Access::SUBMIT_ACTION,
            'owner_user_id' => $owner_id,
            'school_id' => (int) $shared['requester']->id,
            'reason' => 'Synthetic route acceptance check for a school email request.',
            '_wpnonce' => $nonce,
        ),
    ));
    if (is_wp_error($response)) {
        $transport_ambiguous = true;
        fwrite(STDERR, 'INDETERMINATE: loopback HTTP transport returned ' . $response->get_error_code() . "; bounded DB readback will run, but this result cannot PASS.\n");
    }
    $created_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id,status FROM {$request_table} WHERE id>%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND pending_key=%s ORDER BY id",
        $request_id_before,
        $requester_id,
        $owner_id,
        (int) $shared['owner']->id,
        $pending_key
    ));
    if (is_array($created_rows) && count($created_rows) === 1) {
        $created_request_id = (int) $created_rows[0]->id;
    }
    if (is_wp_error($response)) {
        throw new RuntimeException('loopback HTTP request failed: ' . $response->get_error_code());
    }
    $route_code = (int) wp_remote_retrieve_response_code($response);
    if ($wpdb->last_error !== '' || count((array) $created_rows) !== 1
        || ($created_rows[0]->status ?? '') !== 'pending' || $route_code !== 302) {
        throw new RuntimeException('route did not create the expected pending request and redirect');
    }

    $submitted_events = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d AND event_type='submitted' AND actor_user_id=%d",
        $created_request_id,
        $requester_id
    ));
    if ($wpdb->last_error !== '' || $submitted_events !== 1) {
        throw new RuntimeException('expected exactly one submitted event for the request');
    }

    $route_ok = true;
} catch (Throwable $error) {
    fwrite(STDERR, ($transport_ambiguous ? 'INDETERMINATE: ' : 'FAIL: ')
        . sanitize_text_field($error->getMessage()) . "\n");
} finally {
    if (file_exists($mu_guard) && hash_equals($mu_guard_hash, (string) hash_file('sha256', $mu_guard))) {
        $guard_removed = unlink($mu_guard) && !file_exists($mu_guard);
    } elseif (!file_exists($mu_guard)) {
        $guard_removed = true;
    }
    if (file_exists($mail_marker)) {
        $marker_state = file_get_contents($mail_marker);
        $mail_blocked = $marker_state === 'blocked';
        if (in_array($marker_state, array('not-called', 'blocked'), true)) {
            $marker_removed = unlink($mail_marker) && !file_exists($mail_marker);
        }
    }
    if ($mu_dir_created) {
        $mu_dir_removed = rmdir($mu_dir) && !file_exists($mu_dir);
    }

    $find_created_rows = static function () use ($wpdb, $request_table, $request_id_before, $requester_id, $owner_id, $shared, $pending_key) {
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$request_table} WHERE id>%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND pending_key=%s ORDER BY id",
            $request_id_before,
            $requester_id,
            $owner_id,
            (int) $shared['owner']->id,
            $pending_key
        ));
    };
    $created_rows = $find_created_rows();
    if ($transport_ambiguous && $wpdb->last_error === '' && is_array($created_rows) && count($created_rows) === 0) {
        for ($readback = 0; $readback < 4; $readback++) {
            usleep(250000);
            $created_rows = $find_created_rows();
            if ($wpdb->last_error !== '' || !is_array($created_rows) || count($created_rows) > 0) { break; }
        }
    }
    if ($wpdb->last_error === '' && is_array($created_rows) && count($created_rows) === 1) {
        $created_request_id = (int) $created_rows[0]->id;
        $wpdb->delete($event_table, array('request_id' => $created_request_id), array('%d'));
        $wpdb->delete($request_table, array(
            'id' => $created_request_id,
            'requester_user_id' => $requester_id,
            'owner_user_id' => $owner_id,
            'school_id' => (int) $shared['owner']->id,
            'pending_key' => $pending_key,
        ), array('%d', '%d', '%d', '%d', '%s'));
        $remaining = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$request_table} WHERE id=%d AND pending_key=%s",
            $created_request_id,
            $pending_key
        ));
        $remaining_events = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d",
            $created_request_id
        ));
        $cleanup_ok = $wpdb->last_error === '' && $remaining === 0 && $remaining_events === 0;
    } elseif (is_array($created_rows) && count($created_rows) === 0 && $wpdb->last_error === '') {
        $cleanup_ok = !$transport_ambiguous;
    }
    $session_manager->destroy($session_token);
    $session_removed = !$session_manager->verify($session_token);
}

if ($route_ok && $cleanup_ok && $guard_removed && $marker_removed && $session_removed && $mu_dir_removed && !$mail_blocked) {
    echo 'PASS: loopback admin-post route returned ' . $route_code
        . '; pending request and one submitted event verified; exact request/event, mail guard, and temporary session cleanup verified; mail was not invoked.' . "\n";
    exit(0);
}
if ($mail_blocked) {
    fwrite(STDERR, "FAIL: a mail call was intercepted and blocked by the test guard.\n");
}
if (!$guard_removed || !$marker_removed || !$cleanup_ok || !$session_removed || !$mu_dir_removed) {
    fwrite(STDERR, "CLEANUP: exact request/session/mail-guard cleanup could not be fully verified.\n");
}
exit(1);
