<?php
/**
 * Anonymous discoverable passkey login option behavior checks for .66.a.
 *
 * Run only on the phase-owned synthetic local site:
 * wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-login.php
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')) {
    fwrite(STDERR, "BLOCKED: active .66.a passkey components are unavailable\n");
    exit(2);
}
if (!ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: passkey storage schema is not installed\n");
    exit(2);
}

global $wpdb;
$checks = array();
$failures = array();
$challenge_ids = array();
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$user_table = $wpdb->users;
$before_user_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$before_credential_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$before_challenge_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$ui_user_id = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT ID FROM {$user_table} WHERE user_login = %s",
    'zmp66a_admin'
));
$before_protected_ui_count = $ui_user_id > 0 ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id = %d AND label = %s",
    $ui_user_id,
    'Synthetic UI review only'
)) : 0;
$original_user_id = get_current_user_id();
$peer_ip = '127.0.0.121';
$packed_peer_ip = @inet_pton($peer_ip);
$expected_peer_hash = is_string($packed_peer_ip) ? hash_hmac('sha256', inet_ntop($packed_peer_ip), wp_salt('auth')) : '';
$prior_remote_addr = array_key_exists('REMOTE_ADDR', $_SERVER) ? array(true, $_SERVER['REMOTE_ADDR']) : array(false, null);

$assert = static function ($condition, $name) use (&$checks, &$failures) {
    $checks[] = $name;
    if (!$condition) {
        $failures[] = $name;
    }
};
$contains_account_data_key = null;
$contains_account_data_key = static function ($value) use (&$contains_account_data_key) {
    $private_keys = array('account', 'email', 'username', 'userid', 'userhandle', 'displayname');
    if (is_array($value) || is_object($value)) {
        foreach ((array) $value as $key => $item) {
            $normalized_key = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $key));
            if (in_array($normalized_key, $private_keys, true)
                || $normalized_key === 'user_id'
                || $contains_account_data_key($item)) {
                return true;
            }
        }
    }
    return false;
};
$valid_challenge = static function ($value) {
    if (!is_string($value) || !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value)) {
        return false;
    }
    $decoded = base64_decode(strtr($value, '-_', '+/') . '=', true);
    return is_string($decoded) && strlen($decoded) === 32;
};
$login_context_hash = static function () {
    $parts = wp_parse_url(home_url('/'));
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
    $port = isset($parts['port']) ? (int) $parts['port'] : null;
    $effective_default = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
    $origin = $scheme . '://' . $host . ($port !== null && !$effective_default ? ':' . $port : '');
    return hash('sha256', "zmp-passkey-login-origin-v1\n" . $origin . "\n" . $host);
};
$challenge_row_is_anonymous_and_unused = static function ($challenge_id) use ($wpdb, $challenge_table, $login_context_hash, $expected_peer_hash) {
    if (!is_string($challenge_id) || $challenge_id === '') {
        return false;
    }
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id,session_hash,ceremony,action,resource_id,decision,context_hash,peer_hash,consumed_at
            FROM {$challenge_table} WHERE challenge_hash = %s LIMIT 1",
        hash('sha256', $challenge_id)
    ), ARRAY_A);
    return is_array($row)
        && (int) ($row['user_id'] ?? -1) === 0
        && (string) ($row['session_hash'] ?? '') === ''
        && (string) ($row['ceremony'] ?? '') === 'login'
        && (string) ($row['action'] ?? '') === ''
        && (int) ($row['resource_id'] ?? -1) === 0
        && (string) ($row['decision'] ?? '') === ''
        && (string) ($row['context_hash'] ?? '') === $login_context_hash()
        && $expected_peer_hash !== ''
        && hash_equals($expected_peer_hash, (string) ($row['peer_hash'] ?? ''))
        && ($row['consumed_at'] ?? null) === null;
};
$options_are_safe_and_discoverable = static function ($result) use ($valid_challenge, $contains_account_data_key) {
    if (!is_array($result) || !isset($result['challenge_id'], $result['options'])
        || !is_array($result['options'])) {
        return false;
    }
    $options = $result['options'];
    $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $rp_id = isset($options['rpId']) && is_string($options['rpId']) ? strtolower($options['rpId']) : '';
    $allow_credentials_empty = !array_key_exists('allowCredentials', $options)
        || (is_array($options['allowCredentials']) && count($options['allowCredentials']) === 0);
    return $valid_challenge($result['challenge_id'])
        && isset($options['challenge'])
        && $valid_challenge($options['challenge'])
        && hash_equals($result['challenge_id'], $options['challenge'])
        && $rp_id !== ''
        && hash_equals($home_host, $rp_id)
        && ($options['userVerification'] ?? null) === 'required'
        && $allow_credentials_empty
        && !$contains_account_data_key($result);
};

try {
    wp_set_current_user(0);
    $_SERVER['REMOTE_ADDR'] = $peer_ip;
    $anonymous = !is_user_logged_in() && (int) get_current_user_id() === 0;
    $assert($anonymous, 'test starts as anonymous with no WordPress user identity');

    $has_api = method_exists('ZMP_Passkeys', 'login_options');
    $assert($has_api, 'anonymous login_options API is implemented');
    $first = null;
    $second = null;

    if ($has_api) {
        $method = new ReflectionMethod('ZMP_Passkeys', 'login_options');
        $assert($method->getNumberOfParameters() === 0, 'login_options accepts no username or account parameter');
        if ($method->getNumberOfRequiredParameters() === 0 && $method->getNumberOfParameters() === 0) {
            $first = ZMP_Passkeys::login_options();
            if (!is_wp_error($first)) {
                $first_id = $first['challenge_id'] ?? null;
                if (is_string($first_id) && $first_id !== '') { $challenge_ids[] = $first_id; }
                $assert($options_are_safe_and_discoverable($first), 'login options use valid RP ID/challenge, required user verification and no credential allowlist/account data');
                $assert($challenge_row_is_anonymous_and_unused($first_id), 'first challenge is anonymous, login-scoped, origin/RP-bound and unconsumed');
            } else {
                $assert(false, 'login options return discoverable credential request options');
                $assert(false, 'first challenge is anonymous, login-scoped, origin/RP-bound and unconsumed');
            }

            $second = ZMP_Passkeys::login_options();
            if (!is_wp_error($second)) {
                $second_id = $second['challenge_id'] ?? null;
                if (is_string($second_id) && $second_id !== '') { $challenge_ids[] = $second_id; }
                $assert($options_are_safe_and_discoverable($second), 'second login options are also discoverable and disclose no account data');
                $assert(is_string($first_id ?? null) && is_string($second_id)
                    && !hash_equals($first_id, $second_id), 'each login-options request receives a distinct challenge');
                $assert($challenge_row_is_anonymous_and_unused($second_id), 'second challenge is anonymous, login-scoped, origin/RP-bound and unconsumed');
            } else {
                $assert(false, 'second login-options request returns discoverable options');
                $assert(false, 'each login-options request receives a distinct challenge');
                $assert(false, 'second challenge is anonymous, login-scoped, origin/RP-bound and unconsumed');
            }
        }
    }

    if (!$has_api) {
        $after_unavailable_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
        $assert($after_unavailable_count === $before_challenge_count, 'missing login-options API creates no challenge row');
        $assert($first === null && $second === null, 'missing login-options API returns no account-specific result');
    }
} catch (Throwable $error) {
    $failures[] = 'fixture execution error: ' . get_class($error);
} finally {
    foreach (array_unique($challenge_ids) as $challenge_id) {
        $wpdb->delete(
            $challenge_table,
            array('challenge_hash' => hash('sha256', $challenge_id)),
            array('%s')
        );
    }
    wp_set_current_user((int) $original_user_id);
    if ($prior_remote_addr[0]) { $_SERVER['REMOTE_ADDR'] = $prior_remote_addr[1]; } else { unset($_SERVER['REMOTE_ADDR']); }
}

$after_user_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$after_credential_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$after_challenge_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$after_protected_ui_count = $ui_user_id > 0 ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$credential_table} WHERE user_id = %d AND label = %s",
    $ui_user_id,
    'Synthetic UI review only'
)) : 0;
$baseline_ok = $after_user_count === $before_user_count
    && $after_credential_count === $before_credential_count
    && $after_challenge_count === $before_challenge_count
    && $after_protected_ui_count === $before_protected_ui_count;
$checks[] = 'fixture cleanup preserves user, credential, challenge and protected UI baselines';
if (!$baseline_ok) {
    $failures[] = 'fixture cleanup changed a synthetic baseline';
}

if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): anonymous passkey login options\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    fwrite(STDOUT, 'CLEANUP ' . ($baseline_ok ? 'PASS' : 'FAIL') . "\n");
    exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): anonymous passkey login options and one-use challenge\n");
fwrite(STDOUT, "CLEANUP PASS: synthetic baselines and protected UI row unchanged\n");
exit(0);


