<?php
/**
 * Current-candidate same-challenge concurrency fixture for .66.a.
 * Preparation only: run only on the exact isolated clone after independent review.
 * It uses two WP-CLI workers and a clone-local file gate immediately before consume_challenge().
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDOUT, "BLOCKED: WP-CLI only; writes=0\n");
    exit(2);
}

global $wpdb;
$expected_site = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site';
$expected_fixture = $expected_site . '/tests/phase66a-passkey-concurrent-consume.php';
$expected_origin = 'http://127.0.0.1:8102';
$expected_run_root = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run';
$expected_db = 'zmp66a_isolated';
$expected_prefix = 'z66a_';
$expected_db_host = 'localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock';
$expected_plugin = 'zmp-competitions/zmp-competitions.php';
$expected_hashes = array(
    'zmp-competitions.php' => 'c0f6cd135f7fbefb09847fa047f76828736c30c0fa802dad3f06fdd9c3a4989f',
    'includes/class-zmp-passkeys.php' => 'acee4133dee295e2bdd401ef22b7cd304626a53f0f09d698027c69e34dcdc2ae',
    'includes/class-zmp-passkey-store.php' => 'f9038de6499b844529118fe98ab8bd32fe30414bd1061bc35650f638308d02db',
);
$plugin_dir = WP_PLUGIN_DIR . '/zmp-competitions';
$old_suppression = $wpdb->suppress_errors(true);
$args_for_eval = isset($args) && is_array($args) ? $args : array();
$invocation_fixture = (($args_for_eval[0] ?? '') === 'worker')
    ? (string) ($args_for_eval[3] ?? '')
    : (string) ($args_for_eval[0] ?? '');
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$user_table = $wpdb->users;

$emit = static function ($line) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    fwrite(STDOUT, $line . "\n");
};

$credential_fingerprint = static function () use ($credential_table) {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash",
        ARRAY_A
    );
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};

$binding_for_user = static function ($user_id, $route_nonce = null) {
    $action = 'president_subject_decision';
    $resource_id = 1;
    $decision = 'reject';
    $canonical_subject_id = 0;
    $session_hash = hash('sha256', 'zmp-phase66a-concurrent-step-up-session-v1');
    $original_user = (int) get_current_user_id();
    wp_set_current_user((int) $user_id);
    if ($route_nonce === null) {
        $route_nonce = wp_create_nonce('zmp_president_subject_decision');
    }
    $nonce_valid = is_string($route_nonce) && $route_nonce !== ''
        && wp_verify_nonce($route_nonce, 'zmp_president_subject_decision') !== false;
    wp_set_current_user($original_user);
    if (!$nonce_valid) {
        return array();
    }
    $origin_parts = wp_parse_url(home_url('/'));
    if (!is_array($origin_parts) || empty($origin_parts['scheme']) || empty($origin_parts['host'])) {
        return array();
    }
    $scheme = strtolower((string) $origin_parts['scheme']);
    $host = strtolower(rtrim((string) $origin_parts['host'], '.'));
    $default_port = $scheme === 'https' ? 443 : 80;
    $port = isset($origin_parts['port']) ? (int) $origin_parts['port'] : $default_port;
    if (!in_array($scheme, array('http', 'https'), true) || $host !== '127.0.0.1'
        || $port !== 8102) {
        return array();
    }
    $canonical_origin = $scheme . '://' . $host . ($port !== $default_port ? ':' . $port : '');
    $context = "zmp-passkey-step-up-president-subject-v1\n"
        . $canonical_origin . "\n" . $host . "\n" . (int) $user_id . "\n" . $session_hash . "\n"
        . $action . "\n" . $resource_id . "\n" . $decision . "\n" . $canonical_subject_id . "\n"
        . hash('sha256', $route_nonce);
    return array(
        'binding' => array(
            'user_id' => (int) $user_id,
            'session_hash' => $session_hash,
            'ceremony' => 'step_up',
            'action' => $action,
            'resource_id' => $resource_id,
            'decision' => $decision,
            'context_hash' => hash('sha256', $context),
        'peer_hash' => '',
        ),
        'route_nonce' => $route_nonce,
    );
};
$preflight = static function ($expected_challenge_count, $expected_credential_fingerprint = null, $challenge_id = null, $expected_binding = null) use (
    $expected_site,
    $expected_fixture,
    $expected_origin,
    $expected_db,
    $expected_prefix,
    $expected_db_host,
    $expected_plugin,
    $expected_hashes,
    $plugin_dir,
    $invocation_fixture,
    $challenge_table,
    $credential_table,
    $user_table,
    $credential_fingerprint,
    $binding_for_user
) {
    global $wpdb;
    if (!defined('DB_NAME') || DB_NAME !== $expected_db
        || !defined('DB_HOST') || DB_HOST !== $expected_db_host
        || $wpdb->prefix !== $expected_prefix
        || rtrim((string) @realpath(ABSPATH), '/') !== $expected_site
        || rtrim((string) @realpath($plugin_dir), '/') !== $expected_site . '/wp-content/plugins/zmp-competitions'
        || rtrim(home_url('/'), '/') !== $expected_origin
        || rtrim(site_url('/'), '/') !== $expected_origin
        || rtrim((string) @realpath($invocation_fixture), '/') !== $expected_fixture) {
        return false;
    }
    $active = get_option('active_plugins', array());
    if (!is_array($active) || $active !== array($expected_plugin)
        || get_option('zmp_passkey_schema_version') !== '3'
        || !class_exists('ZMP_Passkey_Store')
        || !ZMP_Passkey_Store::schema_is_compatible()) {
        return false;
    }
    foreach ($expected_hashes as $relative => $expected_hash) {
        $path = $plugin_dir . '/' . $relative;
        $actual = is_file($path) ? hash_file('sha256', $path) : false;
        if (!is_string($actual) || !hash_equals($expected_hash, strtolower($actual))) {
            return false;
        }
    }
    $users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$user_table}");
    $credentials = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$credential_table}");
    $challenges = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $owner_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$user_table} WHERE user_login=%s",
        'zmp66a_admin'
    ));
    $protected_count = $owner_id ? (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s",
        $owner_id,
        'Synthetic UI review only'
    )) : 0;
    if ($users !== 1 || $credentials !== 1 || $challenges !== $expected_challenge_count
        || $protected_count !== 1) {
        return false;
    }
    if ($expected_credential_fingerprint !== null
        && !hash_equals($expected_credential_fingerprint, $credential_fingerprint())) {
        return false;
    }
    if ($expected_challenge_count === 1) {
        if (!is_string($challenge_id) || !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $challenge_id)
            || !is_array($expected_binding)) {
            return false;
        }
        $challenge_row = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id,session_hash,ceremony,action,resource_id,decision,context_hash,peer_hash,consumed_at,expires_at FROM {$challenge_table} WHERE challenge_hash=%s",
            hash('sha256', $challenge_id)
        ), ARRAY_A);
        if (!is_array($challenge_row)
            || (int) $challenge_row['user_id'] !== $expected_binding['user_id']
            || $challenge_row['session_hash'] !== $expected_binding['session_hash']
            || $challenge_row['ceremony'] !== 'step_up'
            || $challenge_row['action'] !== 'president_subject_decision'
            || (int) $challenge_row['resource_id'] !== 1
            || $challenge_row['decision'] !== 'reject'
            || $challenge_row['context_hash'] !== $expected_binding['context_hash']
            || $challenge_row['peer_hash'] !== ''
            || $challenge_row['consumed_at'] !== null
            || strtotime((string) $challenge_row['expires_at'] . ' UTC') <= time()) {
            return false;
        }
    }
    return $owner_id;
};

$write_private_json = static function ($path, array $data) {
    $tmp = $path . '.tmp';
    $json = wp_json_encode($data, JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || @file_put_contents($tmp, $json, LOCK_EX) === false
        || !@chmod($tmp, 0600) || !@rename($tmp, $path)) {
        if (is_file($tmp)) {
            @unlink($tmp);
        }
        return false;
    }
    return true;
};

$remove_owned_state = static function ($state_dir, array $known_files) {
    $ok = true;
    foreach ($known_files as $name) {
        $path = $state_dir . '/' . $name;
        if (is_file($path) && !@unlink($path)) {
            $ok = false;
        }
    }
    if (is_dir($state_dir)) {
        $items = @scandir($state_dir);
        if (!is_array($items) || array_diff($items, array('.', '..')) || !@rmdir($state_dir)) {
            $ok = false;
        }
    }
    return $ok;
};

if (($args_for_eval[0] ?? '') === 'worker') {
    global $wpdb;
    $worker_id = (string) ($args_for_eval[1] ?? '');
    $state_dir_arg = (string) ($args_for_eval[2] ?? '');
    $worker_fixture = (string) ($args_for_eval[3] ?? '');
    $state_dir = @realpath($state_dir_arg);
    $run_root = @realpath($expected_run_root);
    if (!in_array($worker_id, array('1', '2'), true)
        || !is_string($state_dir) || !is_string($run_root)
        || !preg_match('/\Aphase66a-consume-[a-f0-9]{16}\z/D', basename($state_dir))
        || strpos($state_dir, $run_root . DIRECTORY_SEPARATOR) !== 0
        || rtrim((string) @realpath($worker_fixture), '/') !== $expected_fixture) {
        $emit("BLOCKED: worker scope invalid; called=false");
        exit(2);
    }
    $baseline_path = $state_dir . '/credential-baseline';
    $challenge_path = $state_dir . '/challenge.bin';
    $route_nonce_path = $state_dir . '/route-nonce.bin';
    $baseline_fingerprint = is_file($baseline_path) ? trim((string) @file_get_contents($baseline_path)) : '';
    $challenge_id = is_file($challenge_path) ? trim((string) @file_get_contents($challenge_path)) : '';
    $route_nonce = is_file($route_nonce_path) ? trim((string) @file_get_contents($route_nonce_path)) : '';
    $owner_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$user_table} WHERE user_login=%s",
        'zmp66a_admin'
    ));
    $binding_data = $owner_id ? $binding_for_user($owner_id, $route_nonce) : array();
    $binding = $binding_data['binding'] ?? array();
    if (!$owner_id || !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $challenge_id)
        || !$binding || !$preflight(1, $baseline_fingerprint, $challenge_id, $binding)) {
        $write_private_json($state_dir . '/result-' . $worker_id . '.json', array(
            'preflight' => false, 'ready' => false, 'called' => false, 'consumed' => false,
        ));
        $emit("BLOCKED: worker preflight failed; called=false");
        exit(2);
    }
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id,session_hash,ceremony,action,resource_id,decision,context_hash,peer_hash,consumed_at,expires_at FROM {$challenge_table} WHERE challenge_hash=%s",
        hash('sha256', $challenge_id)
    ), ARRAY_A);
    if (!is_array($row) || (int) $row['user_id'] !== $binding['user_id']
        || $row['session_hash'] !== $binding['session_hash']
        || $row['ceremony'] !== 'step_up' || $row['action'] !== 'president_subject_decision'
        || (int) $row['resource_id'] !== 1 || $row['decision'] !== 'reject'
        || $row['context_hash'] !== $binding['context_hash']
        || $row['peer_hash'] !== ''
        || $row['consumed_at'] !== null || strtotime((string) $row['expires_at'] . ' UTC') <= time()) {
        $write_private_json($state_dir . '/result-' . $worker_id . '.json', array(
            'preflight' => false, 'ready' => false, 'called' => false, 'consumed' => false,
        ));
        $emit("BLOCKED: challenge preflight failed; called=false");
        exit(2);
    }
    $ready_path = $state_dir . '/ready-' . $worker_id;
    $ready_tmp = $ready_path . '.tmp';
    if (@file_put_contents($ready_tmp, 'ready', LOCK_EX) !== 5
        || !@chmod($ready_tmp, 0600) || !@rename($ready_tmp, $ready_path)) {
        @unlink($ready_tmp);
        $emit("BLOCKED: barrier setup failed; called=false");
        exit(2);
    }
    $release_path = $state_dir . '/release';
    $deadline = microtime(true) + 30.0;
    while (!is_file($release_path) && microtime(true) < $deadline) {
        usleep(1000);
    }
    if (!is_file($release_path) || trim((string) @file_get_contents($release_path)) !== 'go') {
        $write_private_json($state_dir . '/result-' . $worker_id . '.json', array(
            'preflight' => true, 'ready' => true, 'called' => false, 'consumed' => false,
        ));
        $emit("BLOCKED: barrier timeout; called=false");
        exit(2);
    }
    $consumed = is_array(ZMP_Passkey_Store::consume_challenge($challenge_id, $binding));
    $result_written = $write_private_json($state_dir . '/result-' . $worker_id . '.json', array(
        'preflight' => true, 'ready' => true, 'called' => true, 'consumed' => $consumed,
    ));
    $emit($result_written ? 'WORKER: preflight=true; called=true' : 'WORKER: result_saved=false');
    exit($result_written ? 0 : 2);
}

$preflight_owner = $preflight(0);
if (!$preflight_owner) {
    $emit('BLOCKED: exact clone/candidate/baseline preflight failed; writes=0');
    exit(2);
}

$run_root_real = @realpath($expected_run_root);
if (!is_string($run_root_real) || $run_root_real !== $expected_run_root
    || !is_executable('/usr/local/bin/wp') || !function_exists('proc_open')
    || !function_exists('proc_close') || !function_exists('proc_terminate')) {
    $emit('BLOCKED: local worker runtime preflight failed; writes=0');
    exit(2);
}

$baseline_fingerprint = $credential_fingerprint();
$binding_data = $binding_for_user($preflight_owner);
$binding = $binding_data['binding'] ?? array();
$route_nonce = $binding_data['route_nonce'] ?? '';
if (!$binding || !is_string($route_nonce) || $route_nonce === '') {
    $emit('BLOCKED: step-up binding context unavailable; writes=0');
    exit(2);
}
$state_dir = $expected_run_root . '/phase66a-consume-' . bin2hex(random_bytes(8));
$state_created = @mkdir($state_dir, 0700);
if (!$state_created) {
    $emit('BLOCKED: private barrier directory unavailable; writes=0');
    exit(2);
}
if (!@chmod($state_dir, 0700)) {
    @rmdir($state_dir);
    $emit('BLOCKED: private barrier directory unavailable; writes=0');
    exit(2);
}
$challenge_id = '';
$worker_handles = array();
$challenge_created = false;
$primary_ok = false;
$baseline_ok = false;
$cleanup_ok = false;
$integrity_ok = false;
$artifacts_removed = false;
$worker_exit_ok = true;
$successes = 0;
$rejections = 0;

try {
    if (@file_put_contents($state_dir . '/credential-baseline', $baseline_fingerprint, LOCK_EX) === false
        || !@chmod($state_dir . '/credential-baseline', 0600)) {
        throw new RuntimeException('private-baseline-write-failed');
    }
    if (@file_put_contents($state_dir . '/route-nonce.bin', $route_nonce, LOCK_EX) === false
        || !@chmod($state_dir . '/route-nonce.bin', 0600)) {
        throw new RuntimeException('private-route-context-write-failed');
    }
    $challenge_id = ZMP_Passkey_Store::create_challenge($binding);
    if (!is_string($challenge_id) || !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $challenge_id)) {
        throw new RuntimeException('synthetic-challenge-create-failed');
    }
    $challenge_created = true;
    if (@file_put_contents($state_dir . '/challenge.bin', $challenge_id, LOCK_EX) === false
        || !@chmod($state_dir . '/challenge.bin', 0600)) {
        throw new RuntimeException('private-challenge-handoff-failed');
    }
    if ((int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$challenge_table}") !== 1) {
        throw new RuntimeException('challenge-baseline-not-exclusive');
    }
    if ($preflight(1, $baseline_fingerprint, $challenge_id, $binding) !== $preflight_owner) {
        throw new RuntimeException('step-up-challenge-binding-preflight-failed');
    }

    $descriptors = array(
        0 => array('file', '/dev/null', 'r'),
        1 => array('file', '/dev/null', 'w'),
        2 => array('file', '/dev/null', 'w'),
    );
    for ($worker_id = 1; $worker_id <= 2; $worker_id++) {
        $command = array(
            '/usr/local/bin/wp',
            '--path=' . $expected_site,
            'eval-file',
            $expected_fixture,
            'worker',
            (string) $worker_id,
            $state_dir,
            $expected_fixture,
        );
        $pipes = array();
        $process = @proc_open($command, $descriptors, $pipes, $expected_site, null, array('bypass_shell' => true));
        if (!is_resource($process)) {
            throw new RuntimeException('worker-start-failed');
        }
        $worker_handles[$worker_id] = $process;
    }

    $ready_deadline = microtime(true) + 15.0;
    while (microtime(true) < $ready_deadline
        && (!is_file($state_dir . '/ready-1') || !is_file($state_dir . '/ready-2'))) {
        usleep(1000);
    }
    if (!is_file($state_dir . '/ready-1') || !is_file($state_dir . '/ready-2')) {
        throw new RuntimeException('both-workers-not-ready');
    }
    if (@file_put_contents($state_dir . '/release.tmp', 'go', LOCK_EX) !== 2
        || !@chmod($state_dir . '/release.tmp', 0600)
        || !@rename($state_dir . '/release.tmp', $state_dir . '/release')) {
        throw new RuntimeException('barrier-release-failed');
    }

    $result_deadline = microtime(true) + 20.0;
    while (microtime(true) < $result_deadline
        && (!is_file($state_dir . '/result-1.json') || !is_file($state_dir . '/result-2.json'))) {
        usleep(1000);
    }
    if (!is_file($state_dir . '/result-1.json') || !is_file($state_dir . '/result-2.json')) {
        throw new RuntimeException('worker-result-timeout');
    }

    $results = array();
    for ($worker_id = 1; $worker_id <= 2; $worker_id++) {
        $decoded = json_decode((string) @file_get_contents($state_dir . '/result-' . $worker_id . '.json'), true);
        if (!is_array($decoded) || ($decoded['preflight'] ?? false) !== true
            || ($decoded['ready'] ?? false) !== true || ($decoded['called'] ?? false) !== true
            || !is_bool($decoded['consumed'] ?? null)) {
            throw new RuntimeException('worker-result-invalid');
        }
        $results[] = $decoded['consumed'];
    }
    $successes = count(array_filter($results, static function ($value) { return $value === true; }));
    $rejections = count(array_filter($results, static function ($value) { return $value === false; }));
    $row = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare(
        "SELECT consumed_at FROM {$challenge_table} WHERE challenge_hash=%s",
        hash('sha256', $challenge_id)
    ), ARRAY_A);
    $primary_ok = $successes === 1 && $rejections === 1 && is_array($row) && $row['consumed_at'] !== null;
} catch (Throwable $error) {
    $primary_ok = false;
} finally {
    foreach ($worker_handles as $worker_id => $process) {
        if (!is_resource($process)) {
            continue;
        }
        if (!is_file($state_dir . '/result-' . $worker_id . '.json')) {
            @proc_terminate($process);
        }
        if (@proc_close($process) !== 0) {
            $worker_exit_ok = false;
        }
    }
    if ($challenge_created && $challenge_id !== '') {
        $deleted = $GLOBALS['wpdb']->delete(
            $challenge_table,
            array('challenge_hash' => hash('sha256', $challenge_id)),
            array('%s')
        );
        $challenge_deleted = $deleted === 1;
    } else {
        $challenge_deleted = true;
    }
    $final_owner = $preflight(0, $baseline_fingerprint);
    $users_after = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$user_table}");
    $credentials_after = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$credential_table}");
    $challenges_after = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$challenge_table}");
    $protected_after = $final_owner ? (int) $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare(
        "SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s",
        $final_owner,
        'Synthetic UI review only'
    )) : 0;
    $baseline_ok = $challenge_deleted && $final_owner !== false
        && $users_after === 1 && $credentials_after === 1 && $challenges_after === 0
        && $protected_after === 1 && hash_equals($baseline_fingerprint, $credential_fingerprint());
    $integrity = array(
        '/usr/local/bin/wp',
        '--path=' . $expected_site,
        'db',
        'check',
    );
    $integrity_process = @proc_open($integrity, array(
        0 => array('file', '/dev/null', 'r'),
        1 => array('file', '/dev/null', 'w'),
        2 => array('file', '/dev/null', 'w'),
    ), $integrity_pipes, $expected_site, null, array('bypass_shell' => true));
    if (is_resource($integrity_process)) {
        $integrity_ok = proc_close($integrity_process) === 0;
    }
    $known_files = array(
        'credential-baseline', 'credential-baseline.tmp', 'challenge.bin', 'challenge.bin.tmp',
        'route-nonce.bin', 'route-nonce.bin.tmp', 'release',
        'ready-1', 'ready-1.tmp', 'ready-2', 'ready-2.tmp', 'release.tmp', 'result-1.json', 'result-1.json.tmp', 'result-2.json', 'result-2.json.tmp',
    );
    $artifacts_removed = $remove_owned_state($state_dir, $known_files);
    $cleanup_ok = $baseline_ok && $artifacts_removed;
    $wpdb->suppress_errors($old_suppression);
}

if ($primary_ok && $worker_exit_ok && $cleanup_ok && $integrity_ok) {
    $emit('PASS: same-challenge consume; workers=2; successes=1; rejections=1');
    $emit('CLEANUP PASS: baseline=true; protected_credential=true; artifacts_removed=true; db_integrity=true');
    exit(0);
}
$emit('FAIL/BLOCKED: same-challenge consume acceptance not established; workers=2; successes=' . $successes . '; rejections=' . $rejections);
    $emit('CLEANUP: baseline=' . ($baseline_ok ? 'true' : 'false') . '; artifacts_removed=' . ($artifacts_removed ? 'true' : 'false') . '; db_integrity=' . ($integrity_ok ? 'true' : 'false'));
exit(1);








