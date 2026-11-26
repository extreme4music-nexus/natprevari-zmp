<?php
/**
 * Credential/challenge storage behavior checks for ZMP 1.2.4.66.a.
 * Run in the local synthetic site only:
 * wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-store.php
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkey_Store')) {
    fwrite(STDERR, "BLOCKED: run with the active .66.a plugin in the synthetic WordPress site\n");
    exit(2);
}
if (!ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: passkey storage schema is not installed\n");
    exit(2);
}

global $wpdb;
$checks = array();
$failures = array();
$challenge_tokens = array();
$credential_hashes = array();
$synthetic_user_id = 0;
$failure_trigger_name = '';
$failure_trigger_created = false;
$assert = static function ($condition, $name) use (&$checks, &$failures) {
    $checks[] = $name;
    if (!$condition) {
        $failures[] = $name;
    }
};
$same_binding = static function ($actual, $expected) {
    if (!is_array($actual) || !is_array($expected)) { return false; }
    ksort($actual); ksort($expected);
    return $actual === $expected;
};
$random_id = static function () {
    return random_int(1000000000, 1900000000);
};
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$protected_ui_rows = static function () use ($wpdb, $credential_table) {
    return (array) $wpdb->get_col($wpdb->prepare(
        "SELECT CONCAT(id, ':', credential_id_hash) FROM {$credential_table} WHERE label = %s ORDER BY id",
        'Synthetic UI review only'
    ));
};
$baseline_credential_rows = array();
$baseline_protected_ui_rows = array();
$challenge_count = static function () use ($wpdb, $challenge_table) {
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
};
$reject_without_insert = static function ($binding, $name) use ($wpdb, $challenge_count, $assert, &$challenge_tokens) {
    $before = $challenge_count();
    ob_start();
    $result = ZMP_Passkey_Store::create_challenge($binding);
    $output = (string) ob_get_clean();
    if (is_string($result) && $result !== '') {
        $challenge_tokens[] = $result;
    }
    $after = $challenge_count();
    $assert($result === null && $before === $after && $output === '', $name);
};
$run_silently = static function ($callback) use ($wpdb) {
    $old_level = ob_get_level();
    $old_suppression = $wpdb->suppress_errors;
    $old_query = $wpdb->last_query;
    $old_error = $wpdb->last_error;
    $wpdb->suppress_errors(true);
    ob_start();
    try {
        return $callback();
    } finally {
        while (ob_get_level() > $old_level) {
            ob_end_clean();
        }
        $wpdb->suppress_errors($old_suppression);
        $wpdb->last_query = $old_query;
        $wpdb->last_error = $old_error;
    }
};

try {
    $admin = get_user_by('login', 'zmp66a_admin');
    if (!$admin instanceof WP_User) {
        throw new RuntimeException('synthetic administrator fixture unavailable');
    }
    $baseline_credential_rows = (array) $wpdb->get_col(
        "SELECT CONCAT(id, ':', credential_id_hash) FROM {$credential_table} ORDER BY id"
    );
    $baseline_protected_ui_rows = $protected_ui_rows();

    require_once ABSPATH . 'wp-admin/includes/user.php';
    $suffix = bin2hex(random_bytes(8));
    $synthetic_user_id = wp_insert_user(array(
        'user_login' => 'zmp66a_store_' . $suffix,
        'user_email' => 'zmp66a_store_' . $suffix . '@example.invalid',
        'user_pass' => wp_generate_password(64, true, true),
        'role' => 'subscriber',
    ));
    if (is_wp_error($synthetic_user_id) || !$synthetic_user_id) {
        $synthetic_user_id = 0;
        throw new RuntimeException('synthetic secondary-user fixture could not be created');
    }

    $credential_id = 'zmp66a-' . rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    $credential_hashes[] = hash('sha256', $credential_id);
    $public_key = random_bytes(64);
    $user_handle = random_bytes(32);
    $record = \Webauthn\CredentialRecord::create(
        $credential_id,
        \Webauthn\PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
        array('internal'),
        'none',
        \Webauthn\TrustPath\EmptyTrustPath::create(),
        \Symfony\Component\Uid\Uuid::v4(),
        $public_key,
        $user_handle,
        0
    );

    $saved = ZMP_Passkey_Store::save_credential((int) $admin->ID, $record, 'synthetic storage check');
    $assert($saved === true, 'credential save succeeds for its exact WordPress user');

    $stored = ZMP_Passkey_Store::find_credential($credential_id);
    $assert(
        $stored instanceof \Webauthn\CredentialRecord
            && hash_equals($credential_id, $stored->publicKeyCredentialId)
            && hash_equals($public_key, $stored->credentialPublicKey)
            && hash_equals($user_handle, $stored->userHandle)
            && $stored->counter === 0,
        'credential round-trips through the WebAuthn serializer'
    );
    $stored_user_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$credential_table} WHERE credential_id_hash = %s LIMIT 1",
        hash('sha256', $credential_id)
    ));
    $assert($stored_user_id === (int) $admin->ID, 'credential DB row is bound to the exact WordPress user');

    $debug_log = WP_CONTENT_DIR . '/debug.log';
    $debug_log_start = is_file($debug_log) ? (int) filesize($debug_log) : 0;
    $previous_suppress_errors = (bool) $wpdb->suppress_errors;
    ob_start();
    $duplicate = ZMP_Passkey_Store::save_credential((int) $synthetic_user_id, $record, 'duplicate synthetic record');
    $duplicate_output = (string) ob_get_clean();
    $assert($duplicate === false, 'duplicate credential ID is rejected for a second user');
    $assert((bool) $wpdb->suppress_errors === $previous_suppress_errors, 'duplicate handling restores the prior database error-suppression state');
    $public_key_marker = rtrim(strtr(base64_encode($public_key), '+/', '-_'), '=');
    $user_handle_marker = rtrim(strtr(base64_encode($user_handle), '+/', '-_'), '=');
    $no_credential_leak = strpos($duplicate_output, $credential_id) === false
        && strpos($duplicate_output, $public_key_marker) === false
        && strpos($duplicate_output, $user_handle_marker) === false
        && strpos((string) $wpdb->last_error, $credential_id) === false
        && strpos((string) $wpdb->last_error, $public_key_marker) === false
        && strpos((string) $wpdb->last_error, $user_handle_marker) === false;
    if (is_file($debug_log)) {
        $log_content = (string) file_get_contents($debug_log);
        $log_delta = substr($log_content, min($debug_log_start, strlen($log_content)));
        $no_credential_leak = $no_credential_leak
            && strpos($log_delta, $credential_id) === false
            && strpos($log_delta, $public_key_marker) === false
            && strpos($log_delta, $user_handle_marker) === false;
    }
    $assert($no_credential_leak, 'duplicate failure does not emit credential payload material to output or logs');

    $wrong_owner_delete = ZMP_Passkey_Store::delete_credential((int) $synthetic_user_id, $credential_id);
    $assert($wrong_owner_delete === false, 'a different user cannot delete the credential');
    $assert(ZMP_Passkey_Store::find_credential($credential_id) instanceof \Webauthn\CredentialRecord, 'wrong-owner delete leaves credential intact');

    $owner_delete = ZMP_Passkey_Store::delete_credential((int) $admin->ID, $credential_id);
    $assert($owner_delete === true, 'the owning user can delete the credential');
    $assert(ZMP_Passkey_Store::find_credential($credential_id) === null, 'deleted credential is no longer retrievable');

    $base_binding = array(
        'user_id' => (int) $admin->ID,
        'session_hash' => hash('sha256', 'phase66a-session-' . $suffix),
        'ceremony' => 'step_up',
        'action' => 'president_subject_decision',
        'resource_id' => $random_id(),
        'decision' => 'approve_new',
        'context_hash' => hash('sha256', 'phase66a-base-context-' . $suffix),
        'peer_hash' => '',
    );

    $changed_bindings = array(
        'user_id' => (int) $synthetic_user_id,
        'session_hash' => hash('sha256', 'phase66a-other-session-' . $suffix),
        'ceremony' => 'login',
        'action' => 'different_action',
        'resource_id' => $base_binding['resource_id'] + 1,
        'decision' => 'reject',
    );
    foreach ($changed_bindings as $field => $changed_value) {
        $token = ZMP_Passkey_Store::create_challenge($base_binding);
        if (!is_string($token) || $token === '') {
            $assert(false, 'challenge creation supports binding mismatch checks');
            continue;
        }
        $challenge_tokens[] = $token;
        $expected = $base_binding;
        $expected[$field] = $changed_value;
        $accepted = ZMP_Passkey_Store::consume_challenge($token, $expected);
        $assert($accepted === null, 'challenge rejects changed ' . $field);
    }

    $matching_binding = $base_binding;
    $matching_binding['resource_id'] = $random_id();
    $matching_token = ZMP_Passkey_Store::create_challenge($matching_binding);
    if (is_string($matching_token) && $matching_token !== '') {
        $challenge_tokens[] = $matching_token;
        $raw_stored = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$challenge_table} WHERE challenge_hash = %s LIMIT 1",
            hash('sha256', $matching_token)
        ), ARRAY_A);
        $raw_absent = is_array($raw_stored)
            && hash_equals((string) ($raw_stored['challenge_hash'] ?? ''), hash('sha256', $matching_token))
            && strpos(wp_json_encode($raw_stored), $matching_token) === false;
        $assert($raw_absent, 'database stores only the challenge hash, not the raw challenge');
        $consumed = ZMP_Passkey_Store::consume_challenge($matching_token, $matching_binding);
        $assert($same_binding($consumed, $matching_binding), 'exact challenge binding consumes successfully');
        $assert(ZMP_Passkey_Store::consume_challenge($matching_token, $matching_binding) === null, 'challenge cannot be consumed twice');
    } else {
        $assert(false, 'matching challenge is created');
    }

    $expired_binding = $base_binding;
    $expired_binding['resource_id'] = $random_id();
    $expired_token = ZMP_Passkey_Store::create_challenge($expired_binding);
    if (is_string($expired_token) && $expired_token !== '') {
        $challenge_tokens[] = $expired_token;
        $wpdb->update(
            $challenge_table,
            array('expires_at' => gmdate('Y-m-d H:i:s', time() - 60)),
            array('challenge_hash' => hash('sha256', $expired_token)),
            array('%s'),
            array('%s')
        );
        $assert(ZMP_Passkey_Store::consume_challenge($expired_token, $expired_binding) === null, 'expired challenge is rejected');
    } else {
        $assert(false, 'expired-challenge fixture is created');
    }

    // Exercise the actual insert-error fallback with a temporary, value-scoped trigger.
    $failure_trigger_name = 'zmp66a_passkey_fail_' . bin2hex(random_bytes(5));
    $trigger_sql = "CREATE TRIGGER {$failure_trigger_name} BEFORE INSERT ON {$credential_table} "
        . "FOR EACH ROW BEGIN IF NEW.label = 'phase66a_race_error' THEN "
        . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'synthetic passkey insert failure'; END IF; END";
    $trigger_suppression = $wpdb->suppress_errors(true);
    $trigger_result = $wpdb->query($trigger_sql);
    $trigger_create_error = (string) $wpdb->last_error;
    $wpdb->suppress_errors($trigger_suppression);
    if ($trigger_result === false) {
        $failures[] = 'BLOCKED: temporary BEFORE INSERT trigger could not be created: ' . sanitize_text_field($trigger_create_error);
    } else {
        $failure_trigger_created = true;
        $error_credential_id = 'zmp66a-error-' . rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $credential_hashes[] = hash('sha256', $error_credential_id);
        $error_public_key = random_bytes(64);
        $error_user_handle = random_bytes(32);
        $error_record = \Webauthn\CredentialRecord::create(
            $error_credential_id,
            \Webauthn\PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            array('internal'),
            'none',
            \Webauthn\TrustPath\EmptyTrustPath::create(),
            \Symfony\Component\Uid\Uuid::v4(),
            $error_public_key,
            $error_user_handle,
            0
        );
        $error_public_key_marker = rtrim(strtr(base64_encode($error_public_key), '+/', '-_'), '=');
        $error_user_handle_marker = rtrim(strtr(base64_encode($error_user_handle), '+/', '-_'), '=');
        $error_log_path = WP_CONTENT_DIR . '/debug.log';
        $error_log_size = is_file($error_log_path) ? (int) filesize($error_log_path) : 0;
        $suppression_before_insert_error = (bool) $wpdb->suppress_errors;
        $ezsql_before = isset($GLOBALS['EZSQL_ERROR']) && is_array($GLOBALS['EZSQL_ERROR'])
            ? count($GLOBALS['EZSQL_ERROR'])
            : 0;
        ob_start();
        $insert_error_result = ZMP_Passkey_Store::save_credential(
            (int) $admin->ID,
            $error_record,
            'phase66a_race_error'
        );
        $insert_error_output = (string) ob_get_clean();
        $assert($insert_error_result === false, 'database insert failure returns false');
        $assert(
            (bool) $wpdb->suppress_errors === $suppression_before_insert_error,
            'database error-suppression state is restored after insert failure'
        );
        $ezsql_after = isset($GLOBALS['EZSQL_ERROR']) && is_array($GLOBALS['EZSQL_ERROR'])
            ? count($GLOBALS['EZSQL_ERROR'])
            : 0;
        $assert($ezsql_after === $ezsql_before, 'insert failure adds no EZSQL_ERROR entry');
        $assert($wpdb->last_query === '' && $wpdb->last_error === '', 'insert failure clears WPDB last query and error');
        $assert(
            $insert_error_output === ''
                && strpos($insert_error_output, $error_credential_id) === false
                && strpos($insert_error_output, $error_public_key_marker) === false
                && strpos($insert_error_output, $error_user_handle_marker) === false,
            'insert failure emits no credential data to output'
        );
        if (is_file($error_log_path)) {
            $log_content = (string) file_get_contents($error_log_path);
            $new_log = substr($log_content, min($error_log_size, strlen($log_content)));
            $assert(
                strpos($new_log, $error_credential_id) === false
                    && strpos($new_log, $error_public_key_marker) === false
                    && strpos($new_log, $error_user_handle_marker) === false,
                'insert failure emits no credential data to the WordPress log'
            );
        }
        $error_row_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$credential_table} WHERE credential_id_hash = %s",
            hash('sha256', $error_credential_id)
        ));
        $assert($error_row_count === 0, 'failed insert leaves no credential row');
    }

    // Ceremony-specific binding rules must reject malformed input before writing a challenge row.
    $registration = array(
        'user_id' => (int) $admin->ID,
        'session_hash' => hash('sha256', 'phase66a-registration-' . $suffix),
        'ceremony' => 'registration',
        'action' => '',
        'resource_id' => 0,
        'decision' => '',
    );
    $registration_missing_user = $registration;
    unset($registration_missing_user['user_id']);
    $reject_without_insert($registration_missing_user, 'registration rejects missing user ID without DB insertion');
    $registration_zero_user = $registration;
    $registration_zero_user['user_id'] = 0;
    $reject_without_insert($registration_zero_user, 'registration rejects zero user ID without DB insertion');
    $registration_missing_session = $registration;
    unset($registration_missing_session['session_hash']);
    $reject_without_insert($registration_missing_session, 'registration rejects missing session hash without DB insertion');
    $registration_invalid_session = $registration;
    $registration_invalid_session['session_hash'] = 'invalid';
    $reject_without_insert($registration_invalid_session, 'registration rejects invalid session hash without DB insertion');

    // Anonymous login is tied to the canonical configured origin and RP ID.
    $login_origin_parts = wp_parse_url(home_url('/'));
    $login_scheme = strtolower((string) ($login_origin_parts['scheme'] ?? 'http'));
    $login_rp_id = strtolower(rtrim((string) ($login_origin_parts['host'] ?? ''), '.'));
    $login_port = isset($login_origin_parts['port']) ? (int) $login_origin_parts['port'] : null;
    $login_default_port = ($login_scheme === 'http' && $login_port === 80) || ($login_scheme === 'https' && $login_port === 443);
    $login_origin = $login_scheme . '://' . $login_rp_id . ($login_port !== null && !$login_default_port ? ':' . $login_port : '');
    $login_test_ip = '127.0.0.123';
    $login_test_packed = @inet_pton($login_test_ip);
    $login_test_peer_hash = is_string($login_test_packed)
        ? hash_hmac('sha256', inet_ntop($login_test_packed), wp_salt('auth')) : '';
    $anonymous_login = array(
        'user_id' => 0,
        'session_hash' => '',
        'ceremony' => 'login',
        'action' => '',
        'resource_id' => 0,
        'decision' => '',
        'context_hash' => hash('sha256', "zmp-passkey-login-origin-v1\n" . $login_origin . "\n" . $login_rp_id),
        'peer_hash' => $login_test_peer_hash,
    );
    $login_token = ZMP_Passkey_Store::create_challenge($anonymous_login);
    if (is_string($login_token) && $login_token !== '') {
        $challenge_tokens[] = $login_token;
        $wrong_login_context = $anonymous_login;
        $wrong_login_context['context_hash'] = hash('sha256', "zmp-passkey-login-origin-v1\nhttp://localhost:8101\nlocalhost");
        $assert(
            ZMP_Passkey_Store::consume_challenge($login_token, $wrong_login_context) === null,
            'anonymous login rejects another origin/RP context without consuming the challenge'
        );
        $login_finish_binding = $anonymous_login;
        unset($login_finish_binding['peer_hash']);
        $login_consumed = ZMP_Passkey_Store::consume_challenge($login_token, $login_finish_binding);
        $assert(
            is_array($login_consumed) && ($login_consumed['context_hash'] ?? '') === $anonymous_login['context_hash']
                && ($login_consumed['peer_hash'] ?? '') === '',
            'anonymous login accepts the exact userless origin/RP binding after mismatch denial'
        );
    } else {
        $assert(false, 'anonymous login accepts the exact userless origin/RP binding after mismatch denial');
    }
    $login_missing_context = $anonymous_login;
    unset($login_missing_context['context_hash']);
    $reject_without_insert($login_missing_context, 'anonymous login rejects a missing origin/RP digest without DB insertion');
    $login_invalid_context = $anonymous_login;
    $login_invalid_context['context_hash'] = 'not-a-sha256-digest';
    $reject_without_insert($login_invalid_context, 'anonymous login rejects a malformed origin/RP digest without DB insertion');
    $login_missing_peer = $anonymous_login;
    unset($login_missing_peer['peer_hash']);
    $reject_without_insert($login_missing_peer, 'anonymous login issuance rejects a missing keyed peer hash without DB insertion');
    $login_invalid_peer = $anonymous_login;
    $login_invalid_peer['peer_hash'] = 'not-a-keyed-peer-hash';
    $reject_without_insert($login_invalid_peer, 'anonymous login issuance rejects a malformed keyed peer hash without DB insertion');
    $login_bad_user = $anonymous_login;
    $login_bad_user['user_id'] = (int) $admin->ID;
    $reject_without_insert($login_bad_user, 'anonymous login rejects a nonzero user ID without DB insertion');
    $login_bad_session = $anonymous_login;
    $login_bad_session['session_hash'] = hash('sha256', 'phase66a-login-session-' . $suffix);
    $reject_without_insert($login_bad_session, 'anonymous login rejects a nonempty session hash without DB insertion');
    $login_bad_action = $anonymous_login;
    $login_bad_action['action'] = 'unexpected_action';
    $reject_without_insert($login_bad_action, 'anonymous login rejects a nonempty action without DB insertion');
    $login_bad_resource = $anonymous_login;
    $login_bad_resource['resource_id'] = 1;
    $reject_without_insert($login_bad_resource, 'anonymous login rejects a nonzero resource ID without DB insertion');
    $login_bad_decision = $anonymous_login;
    $login_bad_decision['decision'] = 'approve_new';
    $reject_without_insert($login_bad_decision, 'anonymous login rejects a nonempty decision without DB insertion');

    // Step-up ceremonies require every binding field.
    $step_up = array(
        'user_id' => (int) $admin->ID,
        'session_hash' => hash('sha256', 'phase66a-step-up-' . $suffix),
        'ceremony' => 'step_up',
        'action' => 'president_subject_decision',
        'resource_id' => $random_id(),
        'decision' => 'approve_new',
        'context_hash' => hash('sha256', 'phase66a-step-context-' . $suffix),
        'peer_hash' => '',
    );
    $step_missing_user = $step_up;
    unset($step_missing_user['user_id']);
    $reject_without_insert($step_missing_user, 'step-up rejects missing user ID without DB insertion');
    $step_zero_user = $step_up;
    $step_zero_user['user_id'] = 0;
    $reject_without_insert($step_zero_user, 'step-up rejects zero user ID without DB insertion');
    $step_missing_session = $step_up;
    unset($step_missing_session['session_hash']);
    $reject_without_insert($step_missing_session, 'step-up rejects missing session hash without DB insertion');
    $step_empty_session = $step_up;
    $step_empty_session['session_hash'] = '';
    $reject_without_insert($step_empty_session, 'step-up rejects empty session hash without DB insertion');
    $step_invalid_session = $step_up;
    $step_invalid_session['session_hash'] = 'invalid';
    $reject_without_insert($step_invalid_session, 'step-up rejects invalid session hash without DB insertion');
    $step_missing_action = $step_up;
    unset($step_missing_action['action']);
    $reject_without_insert($step_missing_action, 'step-up rejects missing action without DB insertion');
    $step_empty_action = $step_up;
    $step_empty_action['action'] = '';
    $reject_without_insert($step_empty_action, 'step-up rejects empty action without DB insertion');
    $step_missing_resource = $step_up;
    unset($step_missing_resource['resource_id']);
    $reject_without_insert($step_missing_resource, 'step-up rejects missing resource ID without DB insertion');
    $step_zero_resource = $step_up;
    $step_zero_resource['resource_id'] = 0;
    $reject_without_insert($step_zero_resource, 'step-up rejects zero resource ID without DB insertion');
    $step_missing_decision = $step_up;
    unset($step_missing_decision['decision']);
    $reject_without_insert($step_missing_decision, 'step-up rejects missing decision without DB insertion');
    $step_empty_decision = $step_up;
    $step_empty_decision['decision'] = '';
    $reject_without_insert($step_empty_decision, 'step-up rejects empty decision without DB insertion');
    $step_token = ZMP_Passkey_Store::create_challenge($step_up);
    if (is_string($step_token) && $step_token !== '') {
        $challenge_tokens[] = $step_token;
        $assert(
            $same_binding(ZMP_Passkey_Store::consume_challenge($step_token, $step_up), $step_up),
            'fully specified step-up binding is accepted'
        );
    } else {
        $assert(false, 'fully specified step-up binding is accepted');
    }

    // Verify the schema marker cannot mask a missing required column or unique index.
    foreach (array_unique($challenge_tokens) as $token) {
        $wpdb->delete($challenge_table, array('challenge_hash' => hash('sha256', $token)), array('%s'));
    }
    $challenge_tokens = array();
    $current_credential_rows = (array) $wpdb->get_col(
        "SELECT CONCAT(id, ':', credential_id_hash) FROM {$credential_table} ORDER BY id"
    );
    $schema_tables_empty = $challenge_count() === 0
        && $current_credential_rows === $baseline_credential_rows
        && $protected_ui_rows() === $baseline_protected_ui_rows;
    $assert($schema_tables_empty, 'schema fixture has no test rows and preserves credential baseline before controlled alteration');
    if ($schema_tables_empty) {
        $peer_column = $wpdb->get_row($wpdb->prepare("SHOW COLUMNS FROM {$challenge_table} LIKE %s", 'peer_hash'), ARRAY_A);
        $peer_indexes = $wpdb->get_results("SHOW INDEX FROM {$challenge_table}", ARRAY_A);
        $peer_index_parts = array();
        foreach ((array) $peer_indexes as $index_row) {
            if (($index_row['Key_name'] ?? '') === 'ceremony_peer_expiry') {
                $peer_index_parts[(int) ($index_row['Seq_in_index'] ?? 0)] = (string) ($index_row['Column_name'] ?? '');
            }
        }
        ksort($peer_index_parts);
        $assert(get_option('zmp_passkey_schema_version') === '3', 'schema v2-to-v3 migration advances the passkey marker to v3');
        $assert(is_array($peer_column) && strtolower((string) ($peer_column['Type'] ?? '')) === 'char(64)'
            && (string) ($peer_column['Null'] ?? '') === 'NO' && (string) ($peer_column['Default'] ?? null) === '',
            'schema v3 peer_hash is exactly CHAR(64) NOT NULL DEFAULT empty');
        $assert($peer_index_parts === array(1 => 'ceremony', 2 => 'peer_hash', 3 => 'expires_at'),
            'schema v3 has the ordered ceremony_peer_expiry composite index');

        // Round-4 P2: the marker cannot make an invalid peer_hash definition compatible.
        foreach (array(
            'wrong type' => "char(32) NOT NULL DEFAULT ''",
            'nullable' => "char(64) NULL DEFAULT ''",
            'wrong default' => "char(64) NOT NULL DEFAULT 'x'",
        ) as $shape_name => $bad_shape) {
            $shape_changed = false;
            try {
                $altered = $run_silently(static function () use ($wpdb, $challenge_table, $bad_shape) {
                    return $wpdb->query("ALTER TABLE {$challenge_table} MODIFY peer_hash {$bad_shape}");
                });
                if ($altered === false) {
                    $failures[] = 'BLOCKED: cannot create peer_hash ' . $shape_name . ' schema fixture';
                } else {
                    $shape_changed = true;
                    $assert(ZMP_Passkey_Store::schema_is_compatible() === false,
                        'schema compatibility rejects peer_hash with ' . $shape_name);
                }
            } finally {
                if ($shape_changed) {
                    $restored = $run_silently(static function () use ($wpdb, $challenge_table) {
                        return $wpdb->query("ALTER TABLE {$challenge_table} MODIFY peer_hash char(64) NOT NULL DEFAULT ''");
                    });
                    $assert($restored !== false && ZMP_Passkey_Store::schema_is_compatible(),
                        'peer_hash shape restored after ' . $shape_name . ' schema fixture');
                }
            }
        }

        $column_dropped = false;
        try {
            $drop_column = $run_silently(static function () use ($wpdb, $challenge_table) {
                return $wpdb->query("ALTER TABLE {$challenge_table} DROP COLUMN decision");
            });
            if ($drop_column === false) {
                $failures[] = 'BLOCKED: cannot create empty-table missing-column fixture';
            } else {
                $column_dropped = true;
                $assert(
                    ZMP_Passkey_Store::schema_is_compatible() === false,
                    'schema compatibility detects missing required challenge column despite marker'
                );
                $column_repaired = $run_silently(static function () {
                    return ZMP_Passkey_Store::install_schema();
                });
                $decision_column = $wpdb->get_row(
                    $wpdb->prepare("SHOW COLUMNS FROM {$challenge_table} LIKE %s", 'decision'),
                    ARRAY_A
                );
                $assert(
                    $column_repaired === true && is_array($decision_column)
                        && ZMP_Passkey_Store::schema_is_compatible(),
                    'install_schema repairs missing challenge column before compatible'
                );
            }
        } finally {
            if ($column_dropped) {
                $column_exists = $wpdb->get_var(
                    $wpdb->prepare("SHOW COLUMNS FROM {$challenge_table} LIKE %s", 'decision')
                ) === 'decision';
                if (!$column_exists) {
                    $run_silently(static function () { return ZMP_Passkey_Store::install_schema(); });
                }
                $column_exists = $wpdb->get_var(
                    $wpdb->prepare("SHOW COLUMNS FROM {$challenge_table} LIKE %s", 'decision')
                ) === 'decision';
                if (!$column_exists) {
                    $run_silently(static function () use ($wpdb, $challenge_table) {
                        return $wpdb->query("ALTER TABLE {$challenge_table} ADD COLUMN decision varchar(24) NOT NULL DEFAULT '' AFTER resource_id");
                    });
                }
                $assert(
                    $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$challenge_table} LIKE %s", 'decision')) === 'decision',
                    'challenge column fixture is restored'
                );
            }
        }

        $index_dropped = false;
        try {
            $drop_index = $run_silently(static function () use ($wpdb, $credential_table) {
                return $wpdb->query("ALTER TABLE {$credential_table} DROP INDEX credential_id_hash");
            });
            if ($drop_index === false) {
                $failures[] = 'BLOCKED: cannot create empty-table missing-index fixture';
            } else {
                $index_dropped = true;
                $assert(
                    ZMP_Passkey_Store::schema_is_compatible() === false,
                    'schema compatibility detects missing credential unique index despite marker'
                );
                $index_repaired = $run_silently(static function () {
                    return ZMP_Passkey_Store::install_schema();
                });
                $indexes = $wpdb->get_results("SHOW INDEX FROM {$credential_table}", ARRAY_A);
                $unique_hash_index = false;
                foreach ((array) $indexes as $index) {
                    if (($index['Key_name'] ?? '') === 'credential_id_hash'
                        && (int) ($index['Non_unique'] ?? 1) === 0
                        && ($index['Column_name'] ?? '') === 'credential_id_hash') {
                        $unique_hash_index = true;
                    }
                }
                $assert(
                    $index_repaired === true && $unique_hash_index
                        && ZMP_Passkey_Store::schema_is_compatible(),
                    'install_schema repairs missing unique credential index before compatible'
                );
            }
        } finally {
            if ($index_dropped) {
                $indexes = $wpdb->get_results("SHOW INDEX FROM {$credential_table}", ARRAY_A);
                $unique_hash_index = false;
                foreach ((array) $indexes as $index) {
                    if (($index['Key_name'] ?? '') === 'credential_id_hash'
                        && (int) ($index['Non_unique'] ?? 1) === 0
                        && ($index['Column_name'] ?? '') === 'credential_id_hash') {
                        $unique_hash_index = true;
                    }
                }
                if (!$unique_hash_index) {
                    $run_silently(static function () { return ZMP_Passkey_Store::install_schema(); });
                    $indexes = $wpdb->get_results("SHOW INDEX FROM {$credential_table}", ARRAY_A);
                    foreach ((array) $indexes as $index) {
                        if (($index['Key_name'] ?? '') === 'credential_id_hash'
                            && (int) ($index['Non_unique'] ?? 1) === 0
                            && ($index['Column_name'] ?? '') === 'credential_id_hash') {
                            $unique_hash_index = true;
                        }
                    }
                }
                if (!$unique_hash_index) {
                    $run_silently(static function () use ($wpdb, $credential_table) {
                        return $wpdb->query("ALTER TABLE {$credential_table} ADD UNIQUE KEY credential_id_hash (credential_id_hash)");
                    });
                }
                $indexes = $wpdb->get_results("SHOW INDEX FROM {$credential_table}", ARRAY_A);
                $unique_hash_index = false;
                foreach ((array) $indexes as $index) {
                    if (($index['Key_name'] ?? '') === 'credential_id_hash'
                        && (int) ($index['Non_unique'] ?? 1) === 0
                        && ($index['Column_name'] ?? '') === 'credential_id_hash') {
                        $unique_hash_index = true;
                    }
                }
                $assert($unique_hash_index, 'credential unique-index fixture is restored');
            }
        }
    }

    $sensitive_marker = 'phase66a-sensitive-' . $suffix;
    $invalid_binding = $base_binding;
    $invalid_binding['decision'] = $sensitive_marker . '!invalid';
    ob_start();
    $invalid_result = ZMP_Passkey_Store::create_challenge($invalid_binding);
    $captured_output = (string) ob_get_clean();
    $assert($invalid_result === null, 'invalid challenge binding fails closed');
    $assert(strpos($captured_output, $sensitive_marker) === false, 'validation output does not expose sensitive input');
    $assert(strpos((string) $wpdb->last_error, $sensitive_marker) === false, 'database error text does not expose sensitive input');
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    if (is_file($debug_log)) {
        $log = (string) file_get_contents($debug_log);
        $assert(strpos($log, $sensitive_marker) === false, 'WordPress debug log does not expose sensitive input');
    } else {
        $assert(true, 'storage validation emits no WordPress debug log entry');
    }
} catch (Throwable $error) {
    // Emit only a fixed diagnostic; exception details could contain synthetic values.
    $failures[] = 'unexpected exception during storage behavior checks';
    fwrite(STDERR, "FAIL: unexpected exception during storage behavior checks\n");
} finally {
    if ($failure_trigger_created && $failure_trigger_name !== '') {
        $drop_trigger_result = $wpdb->query("DROP TRIGGER IF EXISTS {$failure_trigger_name}");
        if ($drop_trigger_result === false) {
            $failures[] = 'temporary database trigger cleanup failed';
        } else {
            $failure_trigger_created = false;
            $trigger_count = (int) $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = %s',
                $failure_trigger_name
            ));
            $assert($trigger_count === 0, 'temporary database trigger is removed during cleanup');
        }
    }
    foreach (array_unique($challenge_tokens) as $token) {
        $wpdb->delete($challenge_table, array('challenge_hash' => hash('sha256', $token)), array('%s'));
    }
    foreach (array_unique($credential_hashes) as $hash) {
        $wpdb->delete($credential_table, array('credential_id_hash' => $hash), array('%s'));
    }
    $final_schema_install = $run_silently(static function () {
        return ZMP_Passkey_Store::install_schema();
    });
    $assert(
        $final_schema_install === true && ZMP_Passkey_Store::schema_is_compatible(),
        'schema is restored and compatible after fixture cleanup'
    );
    $final_credential_rows = (array) $wpdb->get_col(
        "SELECT CONCAT(id, ':', credential_id_hash) FROM {$credential_table} ORDER BY id"
    );
    $assert(
        $challenge_count() === 0 && $final_credential_rows === $baseline_credential_rows
            && $protected_ui_rows() === $baseline_protected_ui_rows,
        'passkey test rows are removed and protected UI credential baseline remains unchanged'
    );
    $remaining_challenges = 0;
    foreach (array_unique(array_map(static function ($token) { return hash('sha256', $token); }, $challenge_tokens)) as $hash) {
        $remaining_challenges += (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$challenge_table} WHERE challenge_hash = %s",
            $hash
        ));
    }
    $remaining_credentials = 0;
    foreach (array_unique($credential_hashes) as $hash) {
        $remaining_credentials += (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$credential_table} WHERE credential_id_hash = %s",
            $hash
        ));
    }
    $assert($remaining_challenges === 0, 'test challenge rows are removed during cleanup');
    $assert($remaining_credentials === 0, 'test credential rows are removed during cleanup');
    if ($synthetic_user_id > 0) {
        wp_delete_user((int) $synthetic_user_id);
        $assert(!get_userdata((int) $synthetic_user_id), 'temporary synthetic user is removed during cleanup');
    }
}

if ($failures) {
    echo 'FAIL (' . count($failures) . '/' . count($checks) . '): ' . implode('; ', $failures) . "\n";
    exit(1);
}
echo 'PASS (' . count($checks) . '): credential and one-use challenge storage behavior' . "\n";
