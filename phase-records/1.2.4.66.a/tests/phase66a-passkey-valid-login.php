<?php
/**
 * One end-to-end synthetic discoverable passkey assertion for Task 4.
 * The ephemeral private key, signed payload and session cookie remain in memory.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: .66.a passkey API/schema unavailable\n"); exit(2);
}
global $wpdb;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$users_table = $wpdb->users;
$checks = array(); $failures = array(); $owned_challenge_ids = array();
$test_user_id = 0; $credential_id = ''; $private_key = null; $captured_cookie = '';
$user_before = get_current_user_id(); $post_before = $_POST; $cookie_before = $_COOKIE;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$count = static function($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function() use ($wpdb, $credential_table) {
    $rows = $wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A);
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};
$before = array($count($users_table), $count($credential_table), $count($challenge_table), $credential_fingerprint());
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$users_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_row_before = $protected_user ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only')) : 0;
$assert = static function($ok, $label) use (&$checks, &$failures) { $checks[] = $label; if (!$ok) $failures[] = $label; };
$b64url = static function($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); };
$site_parts = wp_parse_url(home_url('/'));
$site_scheme = strtolower((string) ($site_parts['scheme'] ?? 'http'));
$site_host = strtolower((string) ($site_parts['host'] ?? '127.0.0.1'));
$site_port = isset($site_parts['port']) ? (int) $site_parts['port'] : ($site_scheme === 'https' ? 443 : 80);
$invoke = static function($action, array $post, $remote_ip, $origin) use (&$captured_cookie, $site_scheme, $site_host, $site_port) {
    $scheme = $site_scheme; $host = $site_host; $port = $site_port;
    $_SERVER['HTTP_HOST'] = $host . ':' . $port;
    $_SERVER['REQUEST_SCHEME'] = $scheme;
    $_SERVER['SERVER_PORT'] = (string) $port;
    if ($scheme === 'https') $_SERVER['HTTPS'] = 'on'; else unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN'] = $origin;
    $_SERVER['HTTP_REFERER'] = home_url('/');
    $_SERVER['REMOTE_ADDR'] = $remote_ip;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = array_merge(array('action' => $action), $post);
    $status = null; $die_status = null;
    $status_cb = static function($header, $code) use (&$status) { $status = is_numeric($code) ? (int) $code : null; return $header; };
    $die_cb = static function() use (&$die_status) {
        return static function($message = '', $title = '', $args = array()) use (&$die_status) {
            $die_status = is_array($args) && isset($args['response']) ? (int) $args['response'] : null;
            throw new RuntimeException('fixture-json-response-captured');
        };
    };
    $cookie_cb = static function($cookie) use (&$captured_cookie) { $captured_cookie = is_string($cookie) ? $cookie : ''; };
    $ajax_cb = static function() { return true; };
    add_filter('wp_doing_ajax', $ajax_cb);
    add_filter('wp_die_ajax_handler', $die_cb);
    add_filter('wp_die_json_handler', $die_cb);
    add_filter('status_header', $status_cb, 10, 2);
    add_action('set_logged_in_cookie', $cookie_cb, 10, 6);
    ob_start();
    try { do_action($action === 'zmp_passkey_login_options' ? 'wp_ajax_nopriv_zmp_passkey_login_options' : 'wp_ajax_nopriv_zmp_passkey_login_finish'); }
    catch (Throwable $error) { if ($error->getMessage() !== 'fixture-json-response-captured') throw $error; }
    $body = (string) ob_get_clean();
    remove_filter('wp_doing_ajax', $ajax_cb);
    remove_filter('wp_die_ajax_handler', $die_cb);
    remove_filter('wp_die_json_handler', $die_cb);
    remove_filter('status_header', $status_cb, 10);
    remove_action('set_logged_in_cookie', $cookie_cb, 10);
    return array('json' => json_decode($body, true), 'status' => $status, 'die_status' => $die_status);
};
try {
    if (!function_exists('openssl_pkey_new') || !class_exists('CBOR\\Encoder') || !class_exists('CBOR\\MapObject')) {
        $assert(false, 'OpenSSL and pinned COSE/CBOR library are available');
    } else {
        $assert(true, 'OpenSSL and pinned COSE/CBOR library are available');
        wp_set_current_user(0);
        $assert(!is_user_logged_in() && (int) get_current_user_id() === 0, 'flow begins unauthenticated');
        $suffix = bin2hex(random_bytes(6));
        $created_user = wp_insert_user(array(
            'user_login' => 'zmp66a_valid_passkey_' . $suffix,
            'user_pass' => bin2hex(random_bytes(24)),
            'user_email' => 'zmp66a-' . $suffix . '@example.invalid',
            'role' => 'subscriber',
        ));
        if (is_wp_error($created_user) || !is_int($created_user) || $created_user < 1) {
            $assert(false, 'synthetic user created');
        } else {
            $test_user_id = $created_user;
            $assert(true, 'synthetic user created');
            $user_handle = random_bytes(32);
            update_user_meta($test_user_id, ZMP_Passkeys::USER_HANDLE_META, $b64url($user_handle));
            $assert(hash_equals($b64url($user_handle), (string) get_user_meta($test_user_id, ZMP_Passkeys::USER_HANDLE_META, true)), 'synthetic user has an opaque canonical WebAuthn handle');

            $private_key = openssl_pkey_new(array('private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'));
            $key_details = $private_key ? openssl_pkey_get_details($private_key) : false;
            $assert(is_array($key_details) && isset($key_details['ec']['x'], $key_details['ec']['y']) && strlen($key_details['ec']['x']) === 32 && strlen($key_details['ec']['y']) === 32, 'ephemeral P-256 key generated in process memory');
            if (is_array($key_details) && isset($key_details['ec']['x'], $key_details['ec']['y'])) {
                $cose = CBOR\MapObject::create();
                $cose->add(CBOR\UnsignedIntegerObject::create(1), CBOR\UnsignedIntegerObject::create(2));
                $cose->add(CBOR\UnsignedIntegerObject::create(3), CBOR\NegativeIntegerObject::create(-7));
                $cose->add(CBOR\NegativeIntegerObject::create(-1), CBOR\UnsignedIntegerObject::create(1));
                $cose->add(CBOR\NegativeIntegerObject::create(-2), CBOR\ByteStringObject::create($key_details['ec']['x']));
                $cose->add(CBOR\NegativeIntegerObject::create(-3), CBOR\ByteStringObject::create($key_details['ec']['y']));
                $cose_public_key = (new CBOR\Encoder())->encode($cose);
                $credential_id = random_bytes(32);
                $record = Webauthn\CredentialRecord::create(
                    $credential_id,
                    'public-key',
                    array(),
                    'none',
                    Webauthn\TrustPath\EmptyTrustPath::create(),
                    Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000'),
                    $cose_public_key,
                    $user_handle,
                    0
                );
                $saved = ZMP_Passkey_Store::save_credential($test_user_id, $record, 'Synthetic valid assertion');
                $assert($saved, 'synthetic discoverable credential record stored through passkey store');

                $peer = '127.0.' . random_int(10, 240) . '.' . random_int(10, 240);
                $origin = $site_scheme . '://' . $site_host . ($site_port !== ($site_scheme === 'https' ? 443 : 80) ? ':' . $site_port : '');
                $options_response = $invoke('zmp_passkey_login_options', array(), $peer, $origin);
                $options_data = $options_response['json']['data'] ?? array();
                $challenge_id = is_array($options_data) ? ($options_data['challenge_id'] ?? '') : '';
                if (is_string($challenge_id) && $challenge_id !== '') $owned_challenge_ids[] = $challenge_id;
                $assert(is_array($options_response['json']) && ($options_response['json']['success'] ?? null) === true && is_string($challenge_id) && $challenge_id !== '', 'actual anonymous options endpoint issues one discoverable login challenge');
                $options = is_array($options_data) ? ($options_data['options'] ?? array()) : array();
                $assert(is_array($options) && ($options['challenge'] ?? null) === $challenge_id && ($options['rpId'] ?? null) === $site_host && ($options['userVerification'] ?? null) === 'required' && empty($options['allowCredentials']), 'issued options bind challenge/RP and require user verification without account allowlist');

                if (is_string($challenge_id) && $challenge_id !== '') {
                    $client_data = wp_json_encode(array('type' => 'webauthn.get', 'challenge' => $challenge_id, 'origin' => $origin, 'crossOrigin' => false), JSON_UNESCAPED_SLASHES);
                    $authenticator_data = hash('sha256', $site_host, true) . chr(Webauthn\AuthenticatorData::FLAG_UP | Webauthn\AuthenticatorData::FLAG_UV) . pack('N', 1);
                    $signature = '';
                    $signed = is_string($client_data) && openssl_sign($authenticator_data . hash('sha256', $client_data, true), $signature, $private_key, OPENSSL_ALGO_SHA256);
                    $assert($signed && $signature !== '', 'assertion signature created over WebAuthn signed data in memory');
                    if ($signed && $signature !== '') {
                        $credential_json = wp_json_encode(array(
                            'id' => $b64url($credential_id),
                            'rawId' => $b64url($credential_id),
                            'type' => 'public-key',
                            'response' => array(
                                'clientDataJSON' => $b64url($client_data),
                                'authenticatorData' => $b64url($authenticator_data),
                                'signature' => $b64url($signature),
                                'userHandle' => $b64url($user_handle),
                            ),
                            'clientExtensionResults' => new stdClass(),
                        ), JSON_UNESCAPED_SLASHES);
                        $finish_response = $invoke('zmp_passkey_login_finish', array('challenge_id' => $challenge_id, 'credential' => $credential_json), $peer, $origin);
                        $assert(is_array($finish_response['json']) && ($finish_response['json']['success'] ?? null) === true, 'actual anonymous assertion finish endpoint accepts valid cryptographic assertion');
                        $assert((int) get_current_user_id() === $test_user_id && is_user_logged_in(), 'valid assertion establishes session for exactly the credential owner');
                        $assert($captured_cookie !== '', 'WordPress emitted a logged-in session cookie');
                        $parsed_cookie = $captured_cookie !== '' ? wp_parse_auth_cookie($captured_cookie, 'logged_in') : false;
                        $session_token = is_array($parsed_cookie) ? ($parsed_cookie['token'] ?? '') : '';
                        $session_valid = is_string($session_token) && $session_token !== ''
                            && WP_Session_Tokens::get_instance($test_user_id)->verify($session_token);
                        $assert($session_valid, 'emitted session token is persisted and valid for the synthetic account');
                        $stored_record = ZMP_Passkey_Store::find_credential($credential_id);
                        $assert($stored_record instanceof Webauthn\CredentialRecord && $stored_record->counter === 1, 'validated assertion counter persisted as one');
                        $challenge_row = $wpdb->get_row($wpdb->prepare("SELECT consumed_at FROM {$challenge_table} WHERE challenge_hash=%s", hash('sha256', $challenge_id)), ARRAY_A);
                        $assert(is_array($challenge_row) && !empty($challenge_row['consumed_at']), 'successful finish atomically consumes the one-use challenge');
                        if (defined('LOGGED_IN_COOKIE')) $_COOKIE[LOGGED_IN_COOKIE] = $captured_cookie;
                        wp_logout();
                        $logout_ok = !is_user_logged_in() && (int) get_current_user_id() === 0
                            && (!$session_valid || !WP_Session_Tokens::get_instance($test_user_id)->verify($session_token));
                        $assert($logout_ok, 'logout clears current session and revokes its synthetic session token');
                    }
                }
            }
        }
    }
} catch (Throwable $error) {
    $failures[] = 'fixture execution raised ' . get_class($error);
} finally {
    if (is_resource($private_key) || $private_key instanceof OpenSSLAsymmetricKey) { openssl_pkey_free($private_key); }
    if ($test_user_id > 0 && is_user_logged_in() && (int) get_current_user_id() === $test_user_id) {
        if ($captured_cookie !== '' && defined('LOGGED_IN_COOKIE')) $_COOKIE[LOGGED_IN_COOKIE] = $captured_cookie;
        wp_logout();
    }
    foreach (array_unique($owned_challenge_ids) as $challenge_id) {
        $wpdb->delete($challenge_table, array('challenge_hash' => hash('sha256', $challenge_id)), array('%s'));
    }
    if ($credential_id !== '') {
        $wpdb->delete($credential_table, array('credential_id_hash' => hash('sha256', $credential_id), 'user_id' => $test_user_id), array('%s', '%d'));
    }
    if ($test_user_id > 0 && get_userdata($test_user_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($test_user_id);
    }
    wp_set_current_user((int) $user_before);
    $_POST = $post_before; $_COOKIE = $cookie_before;
    foreach ($server_before as $key => $saved) { if ($saved[0]) $_SERVER[$key] = $saved[1]; else unset($_SERVER[$key]); }
}
$after = array($count($users_table), $count($credential_table), $count($challenge_table), $credential_fingerprint());
$protected_row_after = $protected_user ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only')) : 0;
$baseline_ok = $after[0] === $before[0] && $after[1] === $before[1] && $after[2] === $before[2]
    && hash_equals($before[3], $after[3]) && $protected_row_before === $protected_row_after;
$checks[] = 'cleanup returns users/credentials/challenges to baseline and preserves protected UI credential';
if (!$baseline_ok) $failures[] = 'synthetic baseline or protected credential changed';
if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): valid passkey login\n");
    foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . "\n");
    fwrite(STDOUT, 'CLEANUP ' . ($baseline_ok ? 'PASS' : 'FAIL') . "\nPRIVATE MATERIAL / ASSERTION / COOKIE: never printed\n");
    exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): valid passkey login, session and counter\n");
fwrite(STDOUT, 'CLEANUP PASS: user/credential/challenge counts and protected credential fingerprint returned to baseline; logout revoked session' . "\n");
fwrite(STDOUT, "PRIVATE MATERIAL / ASSERTION / COOKIE: never printed\n");
exit(0);
