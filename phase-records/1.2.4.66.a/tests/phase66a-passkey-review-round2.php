<?php
/**
 * Narrow Task 3 review regressions for the live .66.a synthetic WordPress site.
 *
 * Run with:
 * wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file \
 *   /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php
 *
 * All credentials, account data, and request origins are synthetic. The existing
 * UI-review credential is observed only to confirm it remains untouched.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')
    || !class_exists('ZMP_Passkey_Profile')) {
    fwrite(STDERR, "BLOCKED: active .66.a passkey components are unavailable\n");
    exit(2);
}

require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$checks = array();
$failures = array();
$assert = static function ($condition, $name) use (&$checks, &$failures) {
    $checks[] = $name;
    if (!$condition) {
        $failures[] = $name;
    }
};

$user_table = $wpdb->users;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$baseline_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$baseline_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$ui_user_id = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$user_table} WHERE user_login = %s", 'zmp66a_admin'));
$ui_fixture_before = $ui_user_id > 0 ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id = %d AND label = %s",
    $ui_user_id,
    'Synthetic UI review only'
)) : 0;

$created_user_id = 0;
$password = wp_generate_password(48, true, true);
$session_token = '';
$credential_ids = array();
$old_user_id = get_current_user_id();
$old_cookie_present = isset($_COOKIE[LOGGED_IN_COOKIE]);
$old_cookie = $old_cookie_present ? $_COOKIE[LOGGED_IN_COOKIE] : null;
$old_post = $_POST;
$server_keys = array('REQUEST_METHOD', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_PORT', 'HTTPS', 'HTTP_ORIGIN', 'HTTP_REFERER', 'REQUEST_SCHEME', 'REMOTE_ADDR');
$server_before = array();
foreach ($server_keys as $key) {
    $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
}
$request_outputs = array();
$request_diagnostics = array();
$test_exception = false;

$record_for = static function ($credential_id) {
    return \Webauthn\CredentialRecord::create(
        $credential_id,
        \Webauthn\PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
        array('internal'),
        'none',
        \Webauthn\TrustPath\EmptyTrustPath::create(),
        \Symfony\Component\Uid\Uuid::v4(),
        random_bytes(64),
        random_bytes(32),
        0
    );
};
$has_credential = static function ($credential_id) use ($wpdb, $credential_table) {
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$credential_table} WHERE credential_id_hash = %s",
        hash('sha256', $credential_id)
    )) === 1;
};
$login_as = static function ($user_id, &$session_token) {
    $session_token = WP_Session_Tokens::get_instance((int) $user_id)->create(time() + HOUR_IN_SECONDS);
    $cookie = wp_generate_auth_cookie((int) $user_id, time() + HOUR_IN_SECONDS, 'logged_in', $session_token);
    $_COOKIE[LOGGED_IN_COOKIE] = $cookie;
    wp_set_current_user((int) $user_id);
};
$request_remove = static function ($host, $port, $scheme, $remote_addr, $credential_id, $request_password) use (&$request_outputs, &$request_diagnostics, &$created_user_id, $server_keys) {
    $saved_server = array();
    foreach ($server_keys as $key) {
        $saved_server[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
    }
    $saved_post = $_POST;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_HOST'] = $host;
    $_SERVER['SERVER_NAME'] = explode(':', $host)[0];
    $_SERVER['SERVER_PORT'] = (string) $port;
    $_SERVER['REQUEST_SCHEME'] = $scheme;
    if ($scheme === 'https') {
        $_SERVER['HTTPS'] = 'on';
    } else {
        unset($_SERVER['HTTPS']);
    }
    $origin = $scheme . '://' . $host;
    $_SERVER['HTTP_ORIGIN'] = $origin;
    $_SERVER['HTTP_REFERER'] = $origin . '/wp-admin/profile.php';
    $_SERVER['REMOTE_ADDR'] = $remote_addr;
    $_POST = array(
        'action' => 'zmp_passkey_remove',
        '_zmp_passkey_remove_nonce' => wp_create_nonce('zmp_passkey_remove'),
        'credential_id' => rtrim(strtr(base64_encode($credential_id), '+/', '-_'), '='),
        'current_password' => $request_password,
    );
    $diagnostic = array(
        'secure_context' => ZMP_Passkeys::is_secure_request_context(),
        'logged_in' => is_user_logged_in(),
        'current_user_matches_fixture' => (int) get_current_user_id() === (int) $created_user_id,
        'can_read' => current_user_can('read'),
        'request_method_post' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST',
        'posted_action_matches' => sanitize_key(wp_unslash($_POST['action'] ?? '')) === 'zmp_passkey_remove',
        'nonce_valid' => wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['_zmp_passkey_remove_nonce'] ?? '')),
            'zmp_passkey_remove'
        ) !== false,
    );
    $status_code = null;
    $status_filter = static function ($status_header, $code) use (&$status_code) {
        $status_code = is_numeric($code) ? (int) $code : null;
        return $status_header;
    };
    $wp_die_response = null;
    $die_handler = static function () use (&$wp_die_response) {
        return static function ($message = '', $title = '', $args = array()) use (&$wp_die_response) {
            $response = is_array($args) && isset($args['response']) && is_numeric($args['response'])
                ? (int) $args['response']
                : null;
            $wp_die_response = $response;
            throw new RuntimeException('synthetic request completed');
        };
    };
    $doing_ajax = static function () { return true; };
    add_filter('wp_doing_ajax', $doing_ajax);
    add_filter('wp_die_ajax_handler', $die_handler);
    add_filter('wp_die_json_handler', $die_handler);
    add_filter('status_header', $status_filter, 10, 2);
    ob_start();
    try {
        do_action('admin_post_zmp_passkey_remove');
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'synthetic request completed') {
            $request_outputs[] = '';
        }
    } catch (Throwable $error) {
        $request_outputs[] = '';
    } finally {
        $request_outputs[] = (string) ob_get_clean();
        $diagnostic['http_status'] = $status_code;
        $diagnostic['wp_die_response'] = $wp_die_response;
        $request_diagnostics[] = $diagnostic;
        remove_filter('wp_doing_ajax', $doing_ajax);
        remove_filter('wp_die_ajax_handler', $die_handler);
        remove_filter('wp_die_json_handler', $die_handler);
        remove_filter('status_header', $status_filter, 10);
        foreach ($saved_server as $key => $state) {
            if ($state[0]) {
                $_SERVER[$key] = $state[1];
            } else {
                unset($_SERVER[$key]);
            }
        }
        $_POST = $saved_post;
    }
};

try {
    $new_user = wp_insert_user(array(
        'user_login' => 'zmp66a_r2_' . bin2hex(random_bytes(8)),
        'user_email' => 'zmp66a_r2_' . bin2hex(random_bytes(8)) . '@example.invalid',
        'user_pass' => $password,
        'role' => 'subscriber',
    ));
    if (is_wp_error($new_user) || !$new_user) {
        throw new RuntimeException('synthetic account setup failed');
    }
    $created_user_id = (int) $new_user;
    $login_as($created_user_id, $session_token);

    // Render the actual profile hook used inside WordPress's ordinary profile form.
    $profile_credential = 'zmp66a-r2-profile-' . bin2hex(random_bytes(16));
    $credential_ids[] = $profile_credential;
    $assert(ZMP_Passkey_Store::save_credential($created_user_id, $record_for($profile_credential), 'synthetic profile field check') === true,
        'synthetic profile credential fixture created');
    ob_start();
    ZMP_Passkey_Profile::render_own_profile(get_userdata($created_user_id));
    $profile_markup = (string) ob_get_clean();
    $input_markup = '';
    if (preg_match('/<input\b(?=[^>]*\bid=["\']zmp-passkey-current-password["\'])[^>]*>/i', $profile_markup, $match)) {
        $input_markup = $match[0];
    }
    $assert($input_markup !== '', 'current-password control is present in the rendered profile hook');
    $assert($input_markup !== '' && preg_match('/\brequired(?:\s|=|>)/i', $input_markup) !== 1,
        'blank current-password control does not block ordinary profile-form submission');

    // Exercise WordPress's ordinary self-profile update with the optional field blank.
    $_POST = array(
        'user_id' => $created_user_id,
        '_wpnonce' => wp_create_nonce('update-user_' . $created_user_id),
        'action' => 'update',
        'user_login' => get_userdata($created_user_id)->user_login,
        'email' => get_userdata($created_user_id)->user_email,
        'nickname' => 'Synthetic profile check',
        'display_name' => 'Synthetic profile update',
        'first_name' => '',
        'last_name' => '',
        'description' => 'Synthetic blank-password profile update.',
        'user_url' => '',
        'admin_color' => 'fresh',
        'rich_editing' => 'true',
        'comment_shortcuts' => 'false',
        'syntax_highlighting' => 'true',
        'locale' => 'site-default',
        'current_password' => '',
    );
    $ordinary_update = edit_user($created_user_id);
    $updated_user = get_userdata($created_user_id);
    $assert(!is_wp_error($ordinary_update) && (int) $updated_user->ID === $created_user_id
        && $updated_user->display_name === 'Synthetic profile update',
        'WordPress accepts an ordinary profile update when current_password is blank');

    // HTTPS is accepted only for a configured HTTPS site origin. This synthetic
    // site defines WP_HOME as HTTP loopback, so that positive HTTPS case cannot be
    // safely synthesized here without changing persistent site configuration.
    $local_site_parts = wp_parse_url(home_url('/'));
    $local_port = isset($local_site_parts['port']) ? (int) $local_site_parts['port'] : 80;
    $origin_cases = array(
        array('http', 'passkey-review.example.invalid:80', 80, '203.0.113.10', false, 'insecure non-loopback HTTP removal is rejected'),
        array('http', 'localhost:' . $local_port, $local_port, '127.0.0.1', true, 'localhost HTTP removal is permitted for a loopback client'),
        array('http', '127.0.0.1:' . $local_port, $local_port, '127.0.0.1', true, '127.0.0.1 HTTP removal is permitted for a loopback client'),
        array('http', 'localhost:' . $local_port, $local_port, '203.0.113.10', false, 'loopback HTTP host is rejected for a non-loopback client'),
        array('https', 'passkey-review.example.invalid:8443', 8443, '127.0.0.1', false, 'HTTPS alternate origin differing from configured WP_HOME is rejected'),
    );
    foreach ($origin_cases as $case) {
        list($scheme, $host, $port, $remote_addr, $expected_allowed, $label) = $case;
        $credential_id = 'zmp66a-r2-origin-' . bin2hex(random_bytes(16));
        $credential_ids[] = $credential_id;
        $saved = ZMP_Passkey_Store::save_credential($created_user_id, $record_for($credential_id), 'synthetic origin check');
        $assert($saved === true, 'synthetic removal credential fixture created for origin case');
        if ($saved !== true) {
            continue;
        }
        $output_start = count($request_outputs);
        $request_remove($host, $port, $scheme, $remote_addr, $credential_id, $password);
        $response = isset($request_outputs[$output_start]) ? json_decode($request_outputs[$output_start], true) : null;
        $removed = !$has_credential($credential_id);
        $response_success = is_array($response) && isset($response['success']) && $response['success'] === true;
        $credential_state = $removed ? 'deleted' : 'retained';
        $request_diagnostic_index = count($request_diagnostics) - 1;
        if ($request_diagnostic_index >= 0) {
            $request_diagnostics[$request_diagnostic_index]['json_success'] = $response_success;
            $request_diagnostics[$request_diagnostic_index]['credential_state'] = $credential_state;
        }
        if (!$expected_allowed) {
            $request_outputs[] = $label . ': response=' . ($response_success ? 'accepted' : 'rejected') . '; credential=' . $credential_state;
        }
        $case_passed = $response_success === $expected_allowed && $removed === $expected_allowed;
        $assert($case_passed, $label);
        if ($expected_allowed && !$case_passed && $request_diagnostic_index >= 0) {
            $request_outputs[] = $label . ': diagnostic=' . wp_json_encode($request_diagnostics[$request_diagnostic_index]);
        }
    }
} catch (Throwable $error) {
    // Keep exception details and all synthetic credential material out of output.
    $test_exception = true;
    $failures[] = 'unexpected exception during focused passkey review checks';
}

// Restore owned rows and request/session state even after a failed assertion.
foreach (array_unique($credential_ids) as $credential_id) {
    $wpdb->delete($credential_table, array('credential_id_hash' => hash('sha256', $credential_id)), array('%s'));
}
if ($session_token !== '' && $created_user_id > 0) {
    WP_Session_Tokens::get_instance($created_user_id)->destroy($session_token);
}
if ($created_user_id > 0) {
    wp_delete_user($created_user_id);
}
wp_set_current_user((int) $old_user_id);
if ($old_cookie_present) {
    $_COOKIE[LOGGED_IN_COOKIE] = $old_cookie;
} else {
    unset($_COOKIE[LOGGED_IN_COOKIE]);
}
$_POST = $old_post;
foreach ($server_before as $key => $state) {
    if ($state[0]) {
        $_SERVER[$key] = $state[1];
    } else {
        unset($_SERVER[$key]);
    }
}

$after_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$after_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$ui_fixture_after = $ui_user_id > 0 ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id = %d AND label = %s",
    $ui_user_id,
    'Synthetic UI review only'
)) : 0;
$assert($after_users === $baseline_users, 'synthetic test account is removed');
$assert($after_credentials === $baseline_credentials, 'synthetic credential fixtures are removed');
$assert($ui_fixture_before === $ui_fixture_after, 'tracked UI-review credential row is unchanged');
$assert(!$test_exception, 'focused runtime behavior checks completed without exceptions');

if ($failures) {
    echo 'FAIL (' . count($failures) . '/' . count($checks) . '): ' . implode('; ', $failures) . "\n";
    foreach ($request_outputs as $request_output) {
        if (strpos($request_output, 'response=') !== false || strpos($request_output, 'diagnostic=') !== false) {
            echo 'Origin observation: ' . $request_output . "\n";
        }
    }
    exit(1);
}
echo 'PASS (' . count($checks) . '): focused Task 3 profile and removal-origin behavior' . "\n";
