<?php
/**
 * Behavior check: a step-up proof is tied to the exact alias target as well
 * as the proposal, decision, user, session, and action.
 *
 * Run only in the phase .66.a synthetic site:
 * wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkey_Store')) {
    fwrite(STDERR, "BLOCKED: run in the active .66.a synthetic WordPress site\n");
    exit(2);
}
if (!ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: passkey storage schema is not installed\n");
    exit(2);
}

global $wpdb;
$checks = array();
$failures = array();
$tokens = array();
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$before_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
$before_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$before_challenges = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$assert = static function ($condition, $name) use (&$checks, &$failures) {
    $checks[] = $name;
    if (!$condition) {
        $failures[] = $name;
    }
};
$reject_without_insert = static function ($binding, $name) use ($wpdb, $challenge_table, $assert, &$tokens) {
    $before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    ob_start();
    $result = ZMP_Passkey_Store::create_challenge($binding);
    $output = (string) ob_get_clean();
    if (is_string($result) && $result !== '') {
        $tokens[] = $result;
    }
    $after = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $assert($result === null && $before === $after && $output === '', $name);
};
$same_binding = static function ($actual, $expected) {
    if (!is_array($actual) || !is_array($expected)) { return false; }
    ksort($actual); ksort($expected);
    return $actual === $expected;
};

try {
    $admin = get_user_by('login', 'zmp66a_admin');
    if (!$admin instanceof WP_User) {
        throw new RuntimeException('synthetic administrator fixture unavailable');
    }

    $suffix = bin2hex(random_bytes(8));
    $base = array(
        'user_id' => (int) $admin->ID,
        'session_hash' => hash('sha256', 'phase66a-step-up-context-' . $suffix),
        'ceremony' => 'step_up',
        'action' => 'president_subject_decision',
        'resource_id' => random_int(1000000000, 1900000000),
        'decision' => 'approve_alias',
        'peer_hash' => '',
    );
    $canonical_context = static function ($canonical_subject_id) use ($base) {
        return hash('sha256', wp_json_encode(array(
            'action' => $base['action'],
            'proposal_id' => $base['resource_id'],
            'decision' => $base['decision'],
            'canonical_subject_id' => $canonical_subject_id,
        )));
    };
    $expected = $base;
    $expected['context_hash'] = $canonical_context(24681357);
    $changed = $expected;
    $changed['context_hash'] = $canonical_context(24681358);

    $registration_with_context = array(
        'user_id' => (int) $admin->ID,
        'session_hash' => $base['session_hash'],
        'ceremony' => 'registration',
        'action' => '',
        'resource_id' => 0,
        'decision' => '',
        'context_hash' => hash('sha256', 'unexpected-registration-context-' . $suffix),
    );
    $reject_without_insert($registration_with_context, 'registration rejects context_hash without DB insertion');

    $login_origin_parts = wp_parse_url(home_url('/'));
    $login_scheme = strtolower((string) ($login_origin_parts['scheme'] ?? 'http'));
    $login_rp_id = strtolower(rtrim((string) ($login_origin_parts['host'] ?? ''), '.'));
    $login_port = isset($login_origin_parts['port']) ? (int) $login_origin_parts['port'] : null;
    $login_default_port = ($login_scheme === 'http' && $login_port === 80) || ($login_scheme === 'https' && $login_port === 443);
    $login_origin = $login_scheme . '://' . $login_rp_id . ($login_port !== null && !$login_default_port ? ':' . $login_port : '');
    $peer_ip = '127.0.0.122';
    $peer_packed = @inet_pton($peer_ip);
    $peer_hash = is_string($peer_packed) ? hash_hmac('sha256', inet_ntop($peer_packed), wp_salt('auth')) : '';
    $login_with_context = array(
        'user_id' => 0,
        'session_hash' => '',
        'ceremony' => 'login',
        'action' => '',
        'resource_id' => 0,
        'decision' => '',
        'context_hash' => hash('sha256', "zmp-passkey-login-origin-v1\n" . $login_origin . "\n" . $login_rp_id),
        'peer_hash' => $peer_hash,
    );
    $login_token = ZMP_Passkey_Store::create_challenge($login_with_context);
    if (is_string($login_token) && $login_token !== '') {
        $tokens[] = $login_token;
        $login_finish_binding = $login_with_context;
        unset($login_finish_binding['peer_hash']);
        $login_consumed = ZMP_Passkey_Store::consume_challenge($login_token, $login_finish_binding);
        $assert(is_array($login_consumed) && ($login_consumed['context_hash'] ?? '') === $login_with_context['context_hash']
            && ($login_consumed['peer_hash'] ?? '') === '' && ($login_consumed['ceremony'] ?? '') === 'login',
            'anonymous login consumes its origin digest while peer HMAC remains issuance-only');
    } else {
        $assert(false, 'anonymous login accepts the canonical origin/RP context digest');
    }

    $token = ZMP_Passkey_Store::create_challenge($expected);
    if (is_string($token) && $token !== '') {
        $tokens[] = $token;
        $assert(true, 'step-up challenge accepts a canonical action-context hash');
        $wrong_target = ZMP_Passkey_Store::consume_challenge($token, $changed);
        $assert($wrong_target === null, 'challenge rejects a different canonical subject without consuming the original');
        $exact = ZMP_Passkey_Store::consume_challenge($token, $expected);
        $assert($same_binding($exact, $expected), 'challenge accepts the exact canonical subject binding after mismatch denial');
        $replay = ZMP_Passkey_Store::consume_challenge($token, $expected);
        $assert($replay === null, 'exact-context step-up challenge remains one-use');
    } else {
        $assert(false, 'step-up challenge accepts a canonical action-context hash');
    }
} catch (Throwable $error) {
    // Report only the fixture failure category; never emit account/session/challenge values.
    $failures[] = 'fixture execution error: ' . get_class($error);
} finally {
    foreach (array_unique($tokens) as $token) {
        $wpdb->delete(
            $challenge_table,
            array('challenge_hash' => hash('sha256', $token)),
            array('%s')
        );
    }
}

$after_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
$after_credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
$after_challenges = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
$cleanup_ok = $after_users === $before_users
    && $after_credentials === $before_credentials
    && $after_challenges === $before_challenges;
$checks[] = 'fixture preserves user/credential/challenge baseline';
if (!$cleanup_ok) {
    $failures[] = 'fixture changed user/credential/challenge baseline';
}

if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): step-up context binding\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- ' . $failure . "\n");
    }
    fwrite(STDOUT, 'CHECKS ' . count($checks) . '; CLEANUP ' . ($cleanup_ok ? 'PASS' : 'FAIL') . "\n");
    exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): step-up action context binding and one-use consumption\n");
fwrite(STDOUT, "CLEANUP PASS: user/credential/challenge baselines preserved\n");
exit(0);


