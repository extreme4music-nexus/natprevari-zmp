<?php
/**
 * Task 3 registration/management review-fix checks for the .66.a synthetic site.
 * Run only with:
 * wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php
 *
 * No UI/browser flow or handcrafted attestation is exercised. All users,
 * mandates, sessions, credential rows and user metadata are synthetic and removed.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')
    || !class_exists('ZMP_President_Mandate')) {
    fwrite(STDERR, "BLOCKED: active .66.a WordPress registration components are unavailable\n");
    exit(2);
}

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
$usermeta_table = $wpdb->usermeta;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$mandate_table = ZMP_President_Mandate::table_name();
$baseline_user_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$baseline_credential_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$baseline_challenge_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$baseline_mandate_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mandate_table}");
$baseline_handle_meta_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$usermeta_table} WHERE meta_key = %s",
    'zmp_passkey_user_handle'
));
$baseline_role_present = get_role('zmp_president') instanceof WP_Role;
$test_role_created = false;
if (!$baseline_role_present) {
    add_role('zmp_president', 'Synthetic President', array('read' => true));
    $test_role_created = get_role('zmp_president') instanceof WP_Role;
}
if (!$baseline_role_present && !$test_role_created) {
    fwrite(STDERR, "BLOCKED: synthetic President role fixture could not be initialized\n");
    exit(2);
}

$users = array();
$passwords = array();
$session_tokens = array();
$mandate_ids = array();
$challenge_tokens = array();
$credential_ids = array();
$handle_meta_ids = array();
$original_current_user_id = get_current_user_id();
$had_login_cookie = isset($_COOKIE[LOGGED_IN_COOKIE]);
$original_login_cookie = $had_login_cookie ? $_COOKIE[LOGGED_IN_COOKIE] : null;
$original_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
$had_request_method = array_key_exists('REQUEST_METHOD', $_SERVER);
$original_post = $_POST;
$debug_events_before = get_option('zmp_debug_events', array());
$debug_events_before = is_array($debug_events_before) ? $debug_events_before : array();
$debug_log_path = WP_CONTENT_DIR . '/debug.log';
$debug_log_size = is_file($debug_log_path) ? (int) filesize($debug_log_path) : 0;
$captured_output = array();
$captured_exceptions = array();

$create_user = static function ($label) use (&$users, &$passwords) {
    $suffix = bin2hex(random_bytes(8));
    $password = wp_generate_password(48, true, true);
    $user_id = wp_insert_user(array(
        'user_login' => 'zmp66a_t3_' . $label . '_' . $suffix,
        'user_email' => 'zmp66a_t3_' . $suffix . '@example.invalid',
        'user_pass' => $password,
        'role' => 'zmp_president',
    ));
    if (is_wp_error($user_id) || !$user_id) {
        throw new RuntimeException('synthetic user setup failed');
    }
    $users[] = (int) $user_id;
    $passwords[(int) $user_id] = $password;
    return (int) $user_id;
};
$add_mandate = static function ($user_id, $status, $start_date, $end_date) use ($wpdb, $mandate_table, &$mandate_ids) {
    $inserted = $wpdb->insert(
        $mandate_table,
        array(
            'user_id' => (int) $user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'board_decision' => 'Synthetic local passkey test fixture.',
            'status' => $status,
            'appointed_by' => 0,
            'appointed_at' => current_time('mysql'),
            'close_reason' => '',
        ),
        array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    if ($inserted !== 1) {
        throw new RuntimeException('synthetic mandate setup failed');
    }
    $mandate_ids[] = (int) $wpdb->insert_id;
    return (int) $wpdb->insert_id;
};
$login_as = static function ($user_id) use (&$session_tokens) {
    $user_id = (int) $user_id;
    if (!isset($session_tokens[$user_id])) {
        $session_tokens[$user_id] = WP_Session_Tokens::get_instance($user_id)->create(time() + HOUR_IN_SECONDS);
    }
    $cookie = wp_generate_auth_cookie(
        $user_id,
        time() + HOUR_IN_SECONDS,
        'logged_in',
        $session_tokens[$user_id]
    );
    $_COOKIE[LOGGED_IN_COOKIE] = $cookie;
    wp_set_current_user($user_id);
};
$call_options = static function ($user_id, $password) use (&$challenge_tokens, &$captured_output, &$captured_exceptions) {
    ob_start();
    try {
        $result = ZMP_Passkeys::registration_options((int) $user_id, (string) $password);
    } catch (Throwable $error) {
        $result = null;
        $captured_exceptions[] = 'registration options threw';
    }
    $captured_output[] = (string) ob_get_clean();
    if (is_array($result) && isset($result['challenge_id']) && is_string($result['challenge_id'])) {
        $challenge_tokens[] = $result['challenge_id'];
    }
    return $result;
};
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
$credential_exists = static function ($credential_id) use ($wpdb, $credential_table) {
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$credential_table} WHERE credential_id_hash = %s",
        hash('sha256', $credential_id)
    )) === 1;
};

try {
    $today = current_time('Y-m-d');
    $future_start = gmdate('Y-m-d', strtotime($today . ' +2 days'));
    $future_end = gmdate('Y-m-d', strtotime($today . ' +365 days'));
    $active_end = gmdate('Y-m-d', strtotime($today . ' +365 days'));

    $scheduled_president = $create_user('scheduled');
    $mandateless_president = $create_user('mandateless');
    $active_president = $create_user('active');
    $active_president_two = $create_user('active_two');

    $scheduled_mandate_id = $add_mandate($scheduled_president, 'scheduled', $future_start, $future_end);
    $active_mandate_id = $add_mandate($active_president, 'active', $today, $active_end);
    $active_mandate_two_id = $add_mandate($active_president_two, 'active', $today, $active_end);
    $assert((array) get_userdata($scheduled_president)->roles === array('zmp_president'), 'scheduled fixture has President role');
    $assert((array) get_userdata($mandateless_president)->roles === array('zmp_president'), 'mandate-less fixture has President role');
    $assert(ZMP_President_Mandate::user_has_active_mandate($scheduled_president) === false, 'scheduled fixture has no active mandate');
    $assert(ZMP_President_Mandate::user_has_active_mandate($mandateless_president) === false, 'mandate-less fixture has no active mandate');
    $assert(ZMP_President_Mandate::user_has_active_mandate($active_president) === true, 'active fixture has an active mandate');
    $assert(ZMP_President_Mandate::user_has_active_mandate($active_president_two) === true, 'second active fixture has an active mandate');

    $login_as($scheduled_president);
    $scheduled_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $scheduled_result = $call_options($scheduled_president, $passwords[$scheduled_president]);
    $scheduled_after = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $assert(is_wp_error($scheduled_result), 'registration options reject a President whose mandate is not active');
    $assert($scheduled_after === $scheduled_before, 'inactive-President denial creates no registration challenge');

    $login_as($mandateless_president);
    $mandateless_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $mandateless_result = $call_options($mandateless_president, $passwords[$mandateless_president]);
    $mandateless_after = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $assert(is_wp_error($mandateless_result), 'registration options reject a President with no mandate');
    $assert($mandateless_after === $mandateless_before, 'mandate-less denial creates no registration challenge');

    // Preseed duplicate identical metadata rows to model concurrent initialization's race winner.
    $canonical_handle = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    foreach (array(1, 2) as $_) {
        $inserted = $wpdb->insert(
            $usermeta_table,
            array('user_id' => $active_president, 'meta_key' => ZMP_Passkeys::USER_HANDLE_META, 'meta_value' => $canonical_handle),
            array('%d', '%s', '%s')
        );
        if ($inserted !== 1) {
            throw new RuntimeException('synthetic user-handle setup failed');
        }
        $handle_meta_ids[] = (int) $wpdb->insert_id;
    }

    $login_as($active_president);
    $active_roles_before = get_userdata($active_president)->roles;
    $active_caps_before = get_userdata($active_president)->caps;
    $active_options_one = $call_options($active_president, $passwords[$active_president]);
    $active_options_two = $call_options($active_president, $passwords[$active_president]);
    $active_options_valid = is_array($active_options_one) && is_array($active_options_two)
        && isset($active_options_one['options']['user']['id'])
        && isset($active_options_two['options']['user']['id']);
    $assert($active_options_valid, 'active President can start self-service registration after current-password check');
    $assert(
        $active_options_valid
            && hash_equals((string) $active_options_one['options']['user']['id'], (string) $active_options_two['options']['user']['id']),
        'duplicate concurrent-like handle metadata yields a stable canonical WebAuthn user handle'
    );
    $handle_rows_after = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_value FROM {$usermeta_table} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id ASC",
        $active_president,
        ZMP_Passkeys::USER_HANDLE_META
    ));
    $assert(
        count($handle_rows_after) === 2
            && $handle_rows_after[0] === $canonical_handle
            && $handle_rows_after[1] === $canonical_handle,
        'repeated registration options do not add further user-handle metadata'
    );
    $active_user_after = get_userdata($active_president);
    $assert(
        $active_user_after->roles === $active_roles_before && $active_user_after->caps === $active_caps_before,
        'passkey enrollment options do not change current roles or capabilities'
    );

    // Interleave a competing handle row after the unique SELECT passed but before the original INSERT executes.
    $race_user_id = $active_president_two;
    $race_winner_handle = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $race_interleaved = false;
    $race_query_seen = false;
    $race_insert_in_progress = false;
    $race_query_filter = static function ($query) use (
        $wpdb,
        $usermeta_table,
        $race_user_id,
        $race_winner_handle,
        &$race_interleaved,
        &$race_query_seen,
        &$race_insert_in_progress
    ) {
        $target_insert = is_string($query)
            && stripos($query, 'INSERT INTO') === 0
            && stripos($query, $usermeta_table) !== false
            && stripos($query, ZMP_Passkeys::USER_HANDLE_META) !== false
            && stripos($query, (string) $race_user_id) !== false;
        if ($target_insert && !$race_query_seen && !$race_insert_in_progress) {
            // The original unique SELECT has passed. Insert the competitor immediately before its INSERT.
            $race_query_seen = true;
            $race_insert_in_progress = true;
            $inserted = $wpdb->insert(
                $usermeta_table,
                array(
                    'user_id' => (int) $race_user_id,
                    'meta_key' => ZMP_Passkeys::USER_HANDLE_META,
                    'meta_value' => $race_winner_handle,
                ),
                array('%d', '%s', '%s')
            );
            $race_insert_in_progress = false;
            $race_interleaved = $inserted === 1;
        }
        return $query;
    };
    $login_as($race_user_id);
    add_filter('query', $race_query_filter, 10, 1);
    try {
        $race_options = $call_options($race_user_id, $passwords[$race_user_id]);
    } finally {
        remove_filter('query', $race_query_filter, 10);
    }
    $race_options_valid = is_array($race_options) && isset($race_options['options']['user']['id']);
    $assert($race_query_seen && $race_interleaved, 'competing handle row is inserted after unique check and before original INSERT');
    $assert($race_options_valid, 'interleaved registration options are returned');
    $race_handle_rows = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_value FROM {$usermeta_table} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id ASC",
        $race_user_id,
        ZMP_Passkeys::USER_HANDLE_META
    ));
    $race_local_candidate = isset($race_handle_rows[1]) ? (string) $race_handle_rows[1] : '';
    $assert(
        count($race_handle_rows) === 2
            && $race_handle_rows[0] === $race_winner_handle
            && preg_match('/\\A[A-Za-z0-9_-]{43}\\z/D', $race_local_candidate) === 1,
        'race leaves the competing canonical row first and the request candidate second'
    );
    $assert(
        $race_options_valid
            && hash_equals($race_winner_handle, (string) $race_options['options']['user']['id'])
            && hash_equals('zmp-' . $race_winner_handle, (string) $race_options['options']['user']['name'])
            && !hash_equals($race_local_candidate, (string) $race_options['options']['user']['id']),
        'registration options use the deterministic canonical stored handle, not the request-local candidate'
    );

    // Create two synthetic credential records to exercise removal guards and success.
    $service_credential_id = 'zmp66a-remove-service-' . bin2hex(random_bytes(16));
    $post_credential_id = 'zmp66a-remove-post-' . bin2hex(random_bytes(16));
    $credential_ids[] = $service_credential_id;
    $credential_ids[] = $post_credential_id;
    $assert(
        ZMP_Passkey_Store::save_credential($active_president, $record_for($service_credential_id), 'synthetic remove service') === true,
        'service-removal credential fixture is created'
    );
    $assert(
        ZMP_Passkey_Store::save_credential($active_president, $record_for($post_credential_id), 'synthetic remove request') === true,
        'request-removal credential fixture is created'
    );

    $remove_service_exists = method_exists('ZMP_Passkeys', 'remove_credential');
    $assert($remove_service_exists, 'self-service credential-removal API exists');
    if ($remove_service_exists) {
        $login_as($active_president);
        $wrong_password_marker = 'invalid-remove-password-' . bin2hex(random_bytes(12));
        $wrong_password_result = ZMP_Passkeys::remove_credential(
            $active_president,
            $service_credential_id,
            $wrong_password_marker
        );
        $assert(is_wp_error($wrong_password_result), 'service removal requires current-password reauthentication');
        $assert($credential_exists($service_credential_id), 'wrong-password removal leaves credential intact');

        $login_as($mandateless_president);
        $wrong_owner_result = ZMP_Passkeys::remove_credential(
            $mandateless_president,
            $service_credential_id,
            $passwords[$mandateless_president]
        );
        $assert(is_wp_error($wrong_owner_result), 'service removal rejects a different credential owner');
        $assert($credential_exists($service_credential_id), 'wrong-owner removal leaves credential intact');

        // A demoted owner remains able to revoke their own passkey.
        $wpdb->update(
            $mandate_table,
            array('status' => 'revoked'),
            array('id' => $active_mandate_id, 'user_id' => $active_president),
            array('%s'),
            array('%d', '%d')
        );
        get_userdata($active_president)->set_role('subscriber');
        $login_as($active_president);
        $valid_remove_result = ZMP_Passkeys::remove_credential(
            $active_president,
            $service_credential_id,
            $passwords[$active_president]
        );
        $assert($valid_remove_result === true, 'owner can remove passkey after current-password reauth and mandate demotion');
        $assert(!$credential_exists($service_credential_id), 'successful service removal deletes only the owner credential');
    }

    $remove_action_registered = has_action('admin_post_zmp_passkey_remove') !== false;
    $assert($remove_action_registered, 'profile removal POST action is registered');
    if ($remove_action_registered) {
        $request_handler = static function ($user_id, $credential_id, $password, $nonce) use (&$captured_output, &$captured_exceptions) {
            $request_server_keys = array('REQUEST_METHOD', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_PORT', 'HTTPS', 'REQUEST_SCHEME', 'HTTP_ORIGIN', 'HTTP_REFERER');
            $old_server = array();
            foreach ($request_server_keys as $key) {
                $old_server[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
            }
            $old_post = $_POST;
            $_SERVER['REQUEST_METHOD'] = 'POST';
            // The handler now validates the browser's secure request origin. Model the
            // authorized site's real HTTP loopback origin for this synthetic POST.
            $_SERVER['HTTP_HOST'] = '127.0.0.1:8101';
            $_SERVER['SERVER_NAME'] = '127.0.0.1';
            $_SERVER['SERVER_PORT'] = '8101';
            $_SERVER['REQUEST_SCHEME'] = 'http';
            unset($_SERVER['HTTPS']);
            $_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:8101';
            $_SERVER['HTTP_REFERER'] = 'http://127.0.0.1:8101/wp-admin/profile.php';
            $_POST = array(
                'action' => 'zmp_passkey_remove',
                '_zmp_passkey_remove_nonce' => $nonce,
                'credential_id' => $credential_id,
                'current_password' => $password,
            );
            $die_handler = static function () {
                return static function ($message = '', $title = '', $args = array()) {
                    throw new RuntimeException('synthetic request completed');
                };
            };
            $doing_ajax = static function () { return true; };
            add_filter('wp_doing_ajax', $doing_ajax);
            add_filter('wp_die_ajax_handler', $die_handler);
            add_filter('wp_die_json_handler', $die_handler);
            ob_start();
            try {
                do_action('admin_post_zmp_passkey_remove');
            } catch (RuntimeException $error) {
                if ($error->getMessage() !== 'synthetic request completed') {
                    $captured_exceptions[] = 'remove request threw';
                }
            } catch (Throwable $error) {
                $captured_exceptions[] = 'remove request threw';
            }
            $captured_output[] = (string) ob_get_clean();
            remove_filter('wp_doing_ajax', $doing_ajax);
            remove_filter('wp_die_ajax_handler', $die_handler);
            remove_filter('wp_die_json_handler', $die_handler);
            foreach ($old_server as $key => $state) {
                if ($state[0]) {
                    $_SERVER[$key] = $state[1];
                } else {
                    unset($_SERVER[$key]);
                }
            }
            $_POST = $old_post;
        };

        $login_as($active_president);
        $request_handler($active_president, $post_credential_id, $passwords[$active_president], 'invalid-nonce-' . bin2hex(random_bytes(8)));
        $assert($credential_exists($post_credential_id), 'invalid removal nonce leaves credential intact');

        $valid_nonce = wp_create_nonce('zmp_passkey_remove');
        $wrong_password_marker = 'invalid-request-password-' . bin2hex(random_bytes(12));
        $request_handler($active_president, $post_credential_id, $wrong_password_marker, $valid_nonce);
        $assert($credential_exists($post_credential_id), 'valid nonce with wrong password leaves credential intact');

        // The target was demoted above; valid owner nonce/password must still permit removal.
        $request_handler(
            $active_president,
            $post_credential_id,
            $passwords[$active_president],
            wp_create_nonce('zmp_passkey_remove')
        );
        $assert(!$credential_exists($post_credential_id), 'valid removal nonce and current password delete the demoted owner credential');
    }

    $assert(ZMP_President_Mandate::user_has_active_mandate($active_president_two) === true, 'unrelated active mandate remains untouched');
} catch (Throwable $error) {
    // Keep exception details and synthetic values out of the report.
    $failures[] = 'unexpected exception during Task 3 synthetic behavior checks';
    $captured_exceptions[] = 'test fixture or product method threw';
} finally {
    foreach (array_unique($challenge_tokens) as $challenge_id) {
        $wpdb->delete(
            $challenge_table,
            array('challenge_hash' => hash('sha256', $challenge_id)),
            array('%s')
        );
    }
    foreach (array_unique($credential_ids) as $credential_id) {
        $wpdb->delete(
            $credential_table,
            array('credential_id_hash' => hash('sha256', $credential_id)),
            array('%s')
        );
    }
    foreach ($handle_meta_ids as $meta_id) {
        $wpdb->delete($usermeta_table, array('umeta_id' => $meta_id), array('%d'));
    }
    foreach ($mandate_ids as $mandate_id) {
        $wpdb->delete($mandate_table, array('id' => $mandate_id), array('%d'));
    }
    foreach ($session_tokens as $user_id => $token) {
        WP_Session_Tokens::get_instance((int) $user_id)->destroy($token);
    }
    foreach ($users as $user_id) {
        wp_delete_user((int) $user_id);
    }
    if ($test_role_created) {
        remove_role('zmp_president');
    }
    wp_set_current_user((int) $original_current_user_id);
    if ($had_login_cookie) {
        $_COOKIE[LOGGED_IN_COOKIE] = $original_login_cookie;
    } else {
        unset($_COOKIE[LOGGED_IN_COOKIE]);
    }
    if ($had_request_method) {
        $_SERVER['REQUEST_METHOD'] = $original_request_method;
    } else {
        unset($_SERVER['REQUEST_METHOD']);
    }
    $_POST = $original_post;
}

$after_user_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
$after_credential_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$after_challenge_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$after_mandate_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mandate_table}");
$after_handle_meta_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$usermeta_table} WHERE meta_key = %s",
    'zmp_passkey_user_handle'
));
$assert($after_user_count === $baseline_user_count, 'all synthetic test users are removed');
$assert($after_credential_count === $baseline_credential_count, 'all synthetic credentials are removed');
$assert($after_challenge_count === $baseline_challenge_count, 'all registration challenges are removed');
$assert($after_mandate_count === $baseline_mandate_count, 'all synthetic mandate rows are removed');
$assert($after_handle_meta_count === $baseline_handle_meta_count, 'all synthetic passkey user-handle metadata rows are removed');

$sensitive_text = implode("\n", $captured_output) . "\n" . implode("\n", $captured_exceptions);
foreach ($passwords as $password) {
    $assert(strpos($sensitive_text, $password) === false, 'captured output does not expose synthetic passwords');
}
foreach ($credential_ids as $credential_id) {
    $assert(strpos($sensitive_text, $credential_id) === false, 'captured output does not expose credential identifiers');
}
$debug_events_after = get_option('zmp_debug_events', array());
$debug_events_after = is_array($debug_events_after) ? $debug_events_after : array();
$debug_delta = array_slice($debug_events_after, count($debug_events_before));
$debug_delta_text = wp_json_encode($debug_delta);
foreach ($passwords as $password) {
    $assert(strpos((string) $debug_delta_text, $password) === false, 'new diagnostic events contain no synthetic passwords');
}
foreach ($credential_ids as $credential_id) {
    $assert(strpos((string) $debug_delta_text, $credential_id) === false, 'new diagnostic events contain no credential identifiers');
}
if (is_file($debug_log_path)) {
    $current_log = (string) file_get_contents($debug_log_path);
    $log_delta = substr($current_log, min($debug_log_size, strlen($current_log)));
    foreach ($passwords as $password) {
        $assert(strpos($log_delta, $password) === false, 'new debug-log entries contain no synthetic passwords');
    }
    foreach ($credential_ids as $credential_id) {
        $assert(strpos($log_delta, $credential_id) === false, 'new debug-log entries contain no credential identifiers');
    }
}

if ($failures) {
    echo 'FAIL (' . count($failures) . '/' . count($checks) . '): ' . implode('; ', $failures) . "\n";
    exit(1);
}
echo 'PASS (' . count($checks) . '): Task 3 registration and passkey management behaviors' . "\n";
