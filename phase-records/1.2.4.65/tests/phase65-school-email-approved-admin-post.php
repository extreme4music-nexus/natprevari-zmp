<?php
/**
 * One synthetic local test of the active-President school-email approval POST.
 * Requires exact-hash Code Review before staging or execution on zmp65_isolated.
 * The email uses reserved example.test; there is no disclosure or mail send.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_isolated'
    || !isset($wpdb) || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "BLOCKED: expected disposable zmp65_isolated / z65_ site.\n"); exit(2);
}
$site = wp_parse_url(home_url('/'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8094) {
    fwrite(STDERR, "BLOCKED: site URL is not authorized loopback 127.0.0.1:8094.\n"); exit(2);
}
foreach (array('ZMP_School_Email_Access','ZMP_Mentor_School_Profile','ZMP_Mentor_Schools','ZMP_President_Mandate','ZMP_Debug') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required plugin service is unavailable.\n"); exit(2); }
}

$requester_id = 31;
$owner_id = 30;
$president_id = 25;
$requester_user = get_userdata($requester_id);
$owner_user = get_userdata($owner_id);
$president_user = get_userdata($president_id);
if (!$requester_user || !ZMP_Mentor_School_Profile::is_teacher($requester_id)
    || !$owner_user || !ZMP_Mentor_School_Profile::is_teacher($owner_id)
    || !$president_user || array_values((array) $president_user->roles) !== array('zmp_president')
    || !user_can($president_id, 'view_zmp_president_dashboard')
    || !ZMP_President_Mandate::user_has_active_mandate($president_id)) {
    fwrite(STDERR, "BLOCKED: expected synthetic Teachers and active synthetic President unavailable.\n"); exit(2);
}
$requester_mentor = ZMP_Mentor_School_Profile::mentor_for_user($requester_id);
$owner_mentor = ZMP_Mentor_School_Profile::mentor_for_user($owner_id);
if (!$requester_mentor || !$owner_mentor) { fwrite(STDERR, "BLOCKED: synthetic Teacher mentor records unavailable.\n"); exit(2); }
$requester_schools = $wpdb->get_results($wpdb->prepare(
    'SELECT id,school_key,engagement_epoch FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND is_active=1',
    (int) $requester_mentor->id
));
$owner_schools = $wpdb->get_results($wpdb->prepare(
    'SELECT id,school_key,engagement_epoch FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND is_active=1',
    (int) $owner_mentor->id
));
$shared = null;
foreach ((array) $requester_schools as $requester_school) {
    foreach ((array) $owner_schools as $owner_school) {
        if (hash_equals((string) $requester_school->school_key, (string) $owner_school->school_key)) {
            $shared = array('requester'=>$requester_school,'owner'=>$owner_school); break 2;
        }
    }
}
if ($wpdb->last_error !== '' || !$shared || (int) $shared['requester']->engagement_epoch < 1 || (int) $shared['owner']->engagement_epoch < 1) {
    fwrite(STDERR, "BLOCKED: Teachers 31 and 30 do not share a ready active school/engagement.\n"); exit(2);
}

$request_table = $wpdb->prefix . 'zmp_school_email_requests';
$event_table = $wpdb->prefix . 'zmp_school_email_request_events';
$profile_table = ZMP_Mentor_School_Profile::table_name();
$pair_before = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$request_table} WHERE requester_user_id=%d AND owner_user_id=%d",
    $requester_id, $owner_id
));
$request_max_before = (int) $wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$request_table}");
$request_snapshot = $wpdb->get_results("SELECT * FROM {$request_table} ORDER BY id", ARRAY_A);
$event_snapshot = $wpdb->get_results("SELECT * FROM {$event_table} ORDER BY id", ARRAY_A);
$profile_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$profile_table} WHERE mentor_id=%d AND school_id=%d ORDER BY id",
    (int) $owner_mentor->id, (int) $shared['owner']->id
), ARRAY_A);
if ($wpdb->last_error !== '' || $pair_before !== 0 || !is_array($request_snapshot) || !is_array($event_snapshot)
    || !is_array($profile_rows) || count($profile_rows) !== 1) {
    fwrite(STDERR, "BLOCKED: request pair must be absent and exactly one owner profile must exist.\n"); exit(2);
}
$profile_before = $profile_rows[0];
$profile_id = (int) ($profile_before['id'] ?? 0);
$baseline_email = (string) ($profile_before['official_email'] ?? '');
$baseline_epoch = (int) ($profile_before['official_email_epoch'] ?? 0);
if ($profile_id < 1 || $baseline_epoch < 1 || !array_key_exists('official_email', $profile_before)) {
    fwrite(STDERR, "BLOCKED: owner email profile is not ready for a reversible synthetic fixture.\n"); exit(2);
}
$request_snapshot_hash = hash('sha256', wp_json_encode($request_snapshot));
$event_snapshot_hash = hash('sha256', wp_json_encode($event_snapshot));
$profile_snapshot_hash = hash('sha256', wp_json_encode($profile_before));

$debug_option = ZMP_Debug::OPTION;
wp_cache_delete($debug_option, 'options');
wp_cache_delete('alloptions', 'options');
$debug_option_rows = $wpdb->get_results($wpdb->prepare(
    "SELECT option_id,option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
    $debug_option
), ARRAY_A);
if ($wpdb->last_error !== '' || !is_array($debug_option_rows) || count($debug_option_rows) > 1) {
    fwrite(STDERR, "BLOCKED: diagnostic option baseline could not be read uniquely.\n"); exit(2);
}
$debug_option_existed = count($debug_option_rows) === 1;
$debug_option_row_before = $debug_option_existed ? $debug_option_rows[0] : null;
$debug_before = $debug_option_existed ? maybe_unserialize((string)$debug_option_row_before['option_value']) : array();
if (!is_array($debug_before)) { fwrite(STDERR, "BLOCKED: diagnostic option has an unexpected shape.\n"); exit(2); }
if (count($debug_before) > ZMP_Debug::MAX_EVENTS - 20) { fwrite(STDERR, "BLOCKED: diagnostic event buffer has insufficient capacity.\n"); exit(2); }

$token_id = bin2hex(random_bytes(16));
$reserved_email = 'zmp-test-' . $token_id . '@example.test';
$reserved_email = strtolower($reserved_email);
$reserved_epoch = $baseline_epoch + 1;
$reason = 'Synthetic President approval route ' . $token_id;
$pending_key = hash('sha256', $requester_id . ':' . $owner_id . ':' . (int) $shared['owner']->id);
$profile_expected = $profile_before;
$profile_expected['official_email'] = $reserved_email;
$profile_expected['official_email_epoch'] = (string) $reserved_epoch;

$configured_mu_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : null;
if (!is_string($configured_mu_dir) || $configured_mu_dir === '') {
    fwrite(STDERR, "BLOCKED: configured MU-plugin directory is unavailable.\n"); exit(2);
}
$mu_dir = $configured_mu_dir;
$content_real = realpath(WP_CONTENT_DIR);
$mu_parent_real = realpath(dirname($mu_dir));
$mu_parent_normalized = $mu_parent_real ? wp_normalize_path($mu_parent_real) : '';
$content_normalized = $content_real ? wp_normalize_path($content_real) : '';
$mu_parent_within_content = $content_normalized !== '' && $mu_parent_normalized !== ''
    && ($mu_parent_normalized === $content_normalized
        || strpos($mu_parent_normalized, trailingslashit($content_normalized)) === 0);
$mu_created = false;
$mu_identity = null;
$guard = $mu_dir . '/zmp65-email-approve-' . $token_id . '.php';
$mail_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.mail';
$network_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.network';
$route_issue_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.route-issued';
$route_guard_loaded_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-loaded';
$route_host_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-host';
$route_method_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-method';
$route_path_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-path';
$route_action_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-action';
$route_request_id_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-request-id';
$route_token_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-token';
$route_decision_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.guard-decision';
$route_seen_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.route-seen';
$route_complete_marker = $mu_dir . '/.zmp65-email-approve-' . $token_id . '.route-complete';
$route_marker_token = bin2hex(random_bytes(16));
$marker_paths = array(
    $mail_marker,$network_marker,$route_issue_marker,$route_guard_loaded_marker,$route_host_marker,
    $route_method_marker,$route_path_marker,$route_action_marker,$route_request_id_marker,$route_token_marker,
    $route_decision_marker,$route_seen_marker,$route_complete_marker,
);
$guard_source = <<<'PHP'
<?php
$marker_mail = __DIR__ . '/__MAIL_MARKER__';
$marker_network = __DIR__ . '/__NETWORK_MARKER__';
$marker_route_guard_loaded = __DIR__ . '/__ROUTE_GUARD_LOADED_MARKER__';
$marker_route_host = __DIR__ . '/__ROUTE_HOST_MARKER__';
$marker_route_method = __DIR__ . '/__ROUTE_METHOD_MARKER__';
$marker_route_path = __DIR__ . '/__ROUTE_PATH_MARKER__';
$marker_route_action = __DIR__ . '/__ROUTE_ACTION_MARKER__';
$marker_route_request_id = __DIR__ . '/__ROUTE_REQUEST_ID_MARKER__';
$marker_route_token = __DIR__ . '/__ROUTE_TOKEN_MARKER__';
$marker_route_decision = __DIR__ . '/__ROUTE_DECISION_MARKER__';
$marker_route_seen = __DIR__ . '/__ROUTE_SEEN_MARKER__';
$marker_route_complete = __DIR__ . '/__ROUTE_COMPLETE_MARKER__';
$marker_identities = __MARKER_IDENTITIES__;
$zmp_write_marker = static function ($path, $key, $value) use ($marker_identities) {
    $identity = $marker_identities[$key] ?? null;
    if (!is_array($identity) || is_link($path)) { return false; }
    $before = @lstat($path);
    if (!is_array($before) || (int)$before['dev'] !== $identity['dev'] || (int)$before['ino'] !== $identity['ino']) { return false; }
    $handle = @fopen($path, 'r+');
    if (!is_resource($handle)) { return false; }
    $opened = fstat($handle);
    if (!is_array($opened) || (int)$opened['dev'] !== $identity['dev'] || (int)$opened['ino'] !== $identity['ino']) {
        fclose($handle); return false;
    }
    $ok = ftruncate($handle, 0) && rewind($handle);
    $length = strlen($value); $written = 0;
    while ($ok && $written < $length) {
        $count = fwrite($handle, substr($value, $written));
        if ($count === false || $count === 0) { $ok = false; break; }
        $written += $count;
    }
    if (!fflush($handle) || $written !== $length) { $ok = false; }
    rewind($handle);
    $readback = stream_get_contents($handle);
    fclose($handle);
    $after = is_link($path) ? false : @lstat($path);
    return $ok && $readback === $value && is_array($after)
        && (int)$after['dev'] === $identity['dev'] && (int)$after['ino'] === $identity['ino'];
};
$expected_route_token = '__EXPECTED_ROUTE_TOKEN__';
$expected_route_action = '__EXPECTED_ROUTE_ACTION__';
$expected_request_id = (int) '__EXPECTED_REQUEST_ID__';
$request_path = isset($_SERVER['REQUEST_URI']) ? parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
$posted_action = $_POST['action'] ?? null;
$request_host_ok = (string) ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1:8094';
$request_method_ok = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST';
$request_path_ok = $request_path === '/wp-admin/admin-post.php';
$request_action_ok = is_scalar($posted_action) && (string) $posted_action === $expected_route_action;
$request_id_ok = is_scalar($_POST['request_id'] ?? null) && (int) $_POST['request_id'] === $expected_request_id;
$request_token_ok = is_string($_POST['zmp65_route_marker'] ?? null)
    && hash_equals($expected_route_token, $_POST['zmp65_route_marker']);
$request_decision = $_POST['decision'] ?? null;
$request_decision_ok = is_scalar($request_decision) && (string) $request_decision === 'approved';
$diagnostic_candidate = array_key_exists('zmp65_route_marker', $_POST)
    || $request_action_ok
    || (is_scalar($_POST['request_id'] ?? null) && (int) $_POST['request_id'] === $expected_request_id);
$zmp_write_marker($marker_route_guard_loaded, 'guard-loaded', 'loaded');
if ($diagnostic_candidate) {
    foreach (array(
        'guard-host'=>array($marker_route_host,$request_host_ok),
        'guard-method'=>array($marker_route_method,$request_method_ok),
        'guard-path'=>array($marker_route_path,$request_path_ok),
        'guard-action'=>array($marker_route_action,$request_action_ok),
        'guard-request-id'=>array($marker_route_request_id,$request_id_ok),
        'guard-token'=>array($marker_route_token,$request_token_ok),
        'guard-decision'=>array($marker_route_decision,$request_decision_ok),
    ) as $marker_key=>$marker_result) {
        $zmp_write_marker($marker_result[0], $marker_key, $marker_result[1] ? 'pass' : 'fail');
    }
}
$route_request_matches = $request_method_ok && $request_host_ok && $request_path_ok
    && $request_action_ok && $request_decision_ok && $request_id_ok && $request_token_ok;
if ($route_request_matches) {
    add_action('init', static function () use ($marker_route_seen, $zmp_write_marker) {
        $zmp_write_marker($marker_route_seen, 'route-seen', 'seen');
    }, PHP_INT_MAX);
    register_shutdown_function(static function () use ($marker_route_complete, $zmp_write_marker) {
        $zmp_write_marker($marker_route_complete, 'route-complete', 'complete');
    });
}
add_filter('pre_wp_mail', static function ($short_circuit, $atts) use ($marker_mail, $zmp_write_marker) {
    $zmp_write_marker($marker_mail, 'mail', 'mail');
    return true;
}, PHP_INT_MAX, 2);
add_filter('pre_http_request', static function ($pre, $args, $url) use ($marker_network, $zmp_write_marker) {
    $zmp_write_marker($marker_network, 'network', 'http');
    return new WP_Error('zmp_test_network_blocked', 'Outbound HTTP is blocked during the local approval test.');
}, PHP_INT_MAX, 3);
PHP;
$guard_hash = '';
$guard_created = false;
$guard_identity = null;
$guard_handle = null;
$marker_ownership = array();
$marker_open_handles = array();
if (!$content_real || !$mu_parent_within_content || is_link($mu_dir) || (file_exists($mu_dir) && !is_dir($mu_dir))
    || (is_dir($mu_dir) && (!$mu_real = realpath($mu_dir)
        || strpos(wp_normalize_path($mu_real), wp_normalize_path($content_real) . '/') !== 0))) {
    fwrite(STDERR, "BLOCKED: temporary guard paths are unavailable or occupied.\n"); exit(2);
}
$temporary_paths = array_merge(array($guard), $marker_paths);
foreach ($temporary_paths as $temporary_path) {
    if (file_exists($temporary_path) || is_link($temporary_path) || @lstat($temporary_path) !== false) {
        fwrite(STDERR, "BLOCKED: temporary guard paths are unavailable or occupied.\n"); exit(2);
    }
}
$marker_identity_matches = static function ($path, $identity) {
    if (!is_array($identity) || is_link($path)) { return false; }
    $stat = @lstat($path);
    return is_array($stat) && (int)$stat['dev'] === $identity['dev'] && (int)$stat['ino'] === $identity['ino'];
};
$create_owned_marker = static function ($path, $value) use (&$marker_ownership, &$marker_open_handles, $marker_identity_matches) {
    if (file_exists($path) || is_link($path) || @lstat($path) !== false) { return false; }
    // x+ keeps exclusive creation while allowing the same owned handle to verify its write.
    $handle = @fopen($path, 'x+b');
    if (!is_resource($handle)) { return false; }
    $marker_ownership[$path] = null;
    $marker_open_handles[$path] = $handle;
    $stat = @fstat($handle);
    if (!is_array($stat) || !isset($stat['dev'], $stat['ino'])) { return false; }
    $marker_ownership[$path] = array('dev'=>(int)$stat['dev'],'ino'=>(int)$stat['ino']);
    $length = strlen($value); $written = 0; $ok = true;
    while ($written < $length) {
        $count = fwrite($handle, substr($value, $written));
        if ($count === false || $count === 0) { $ok = false; break; }
        $written += $count;
    }
    if (!fflush($handle) || $written !== $length) { $ok = false; }
    rewind($handle);
    $readback = stream_get_contents($handle);
    fclose($handle);
    unset($marker_open_handles[$path]);
    return $ok && $readback === $value && $marker_identity_matches($path, $marker_ownership[$path]);
};
$write_owned_marker = static function ($path, $value) use (&$marker_ownership, $marker_identity_matches) {
    $identity = $marker_ownership[$path] ?? null;
    if (!$marker_identity_matches($path, $identity)) { return false; }
    $handle = @fopen($path, 'r+');
    if (!is_resource($handle)) { return false; }
    $opened = fstat($handle);
    if (!is_array($opened) || (int)$opened['dev'] !== $identity['dev'] || (int)$opened['ino'] !== $identity['ino']) {
        fclose($handle); return false;
    }
    $ok = ftruncate($handle, 0) && rewind($handle);
    $length = strlen($value); $written = 0;
    while ($ok && $written < $length) {
        $count = fwrite($handle, substr($value, $written));
        if ($count === false || $count === 0) { $ok = false; break; }
        $written += $count;
    }
    if (!fflush($handle) || $written !== $length) { $ok = false; }
    rewind($handle);
    $readback = stream_get_contents($handle);
    fclose($handle);
    return $ok && $readback === $value && $marker_identity_matches($path, $identity);
};
$read_owned_marker = static function ($path) use (&$marker_ownership, $marker_identity_matches) {
    $identity = $marker_ownership[$path] ?? null;
    if (!$marker_identity_matches($path, $identity)) { return null; }
    $handle = @fopen($path, 'r');
    if (!is_resource($handle)) { return null; }
    $opened = fstat($handle);
    if (!is_array($opened) || (int)$opened['dev'] !== $identity['dev'] || (int)$opened['ino'] !== $identity['ino']) {
        fclose($handle); return null;
    }
    $value = stream_get_contents($handle);
    fclose($handle);
    return is_string($value) && $marker_identity_matches($path, $identity) ? $value : null;
};

$assertions = array(); $failures = array();
$assert = static function ($name, $ok) use (&$assertions, &$failures) { $assertions[$name] = $ok ? 'PASS' : 'FAIL'; if (!$ok) { $failures[] = $name; } };
$request_id = 0; $request_created = false; $profile_changed = false; $route_ok = false; $transport_ambiguous = false;
$route_started = false; $route_debug_baseline = array();
$ambiguous_completion_unverified = false;
$session_token = ''; $session_manager = null; $guard_removed = false; $markers_removed = false;
$mu_removed = true; $request_cleanup = false; $profile_cleanup = false; $debug_cleanup = false; $debug_events = array();
$route_debug_events = array(); $route_debug_test_events = array();
$route_issue_verified = false; $route_seen_verified = false; $route_complete_verified = false;
$token_expiration = time() + 300;
$cookie_name = defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : '';
$make_president_cookie = static function () use (&$session_token, &$session_manager, $president_id, $token_expiration, $cookie_name) {
    $session_manager = WP_Session_Tokens::get_instance($president_id);
    $session_token = $session_manager->create($token_expiration);
    if (!is_string($session_token) || $session_token === '') { throw new RuntimeException('temporary President session creation failed'); }
    if ($cookie_name === '') { throw new RuntimeException('logged-in cookie name unavailable'); }
    $cookie = wp_generate_auth_cookie($president_id, $token_expiration, 'logged_in', $session_token);
    if (!is_string($cookie) || $cookie === '') { throw new RuntimeException('temporary President auth cookie generation failed'); }
    return $cookie;
};
$request_row = static function ($id) use ($wpdb, $request_table) {
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$request_table} WHERE id=%d", (int) $id), ARRAY_A);
};
$request_events = static function ($id) use ($wpdb, $event_table) {
    return $wpdb->get_results($wpdb->prepare("SELECT id,request_id,event_type,actor_user_id,event_at,event_data FROM {$event_table} WHERE request_id=%d ORDER BY id", (int) $id), ARRAY_A);
};
$no_address_response = static function ($response) use ($reserved_email) {
    if (is_wp_error($response)) { return false; }
    $text = (string) wp_remote_retrieve_body($response) . ' ' . (string) wp_remote_retrieve_header($response, 'location');
    return strpos(strtolower($text), $reserved_email) === false
        && !preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text);
};
$expected_route_url = admin_url('admin-post.php');
add_filter('pre_wp_mail', static function ($short_circuit, $atts) use ($mail_marker, $write_owned_marker) {
    if (!$write_owned_marker($mail_marker, 'mail')) { throw new RuntimeException('mail guard marker write failed'); }
    return true;
}, PHP_INT_MAX, 2);
add_filter('pre_http_request', static function ($pre, $args, $url) use (
    $expected_route_url, $network_marker, $route_issue_marker, $route_marker_token, &$request_id, $write_owned_marker
) {
    if (is_string($url) && hash_equals($expected_route_url, $url)
        && strtoupper((string) ($args['method'] ?? 'GET')) === 'POST') {
        $body = $args['body'] ?? null;
        $matches = is_array($body)
            && (string) ($body['action'] ?? '') === ZMP_School_Email_Access::DECIDE_ACTION
            && (int) ($body['request_id'] ?? 0) === $request_id
            && (string) ($body['decision'] ?? '') === 'approved'
            && is_string($body['zmp65_route_marker'] ?? null)
            && hash_equals($route_marker_token, $body['zmp65_route_marker']);
        if ($matches) {
            if (!$write_owned_marker($route_issue_marker, 'issued')) { throw new RuntimeException('local route issue marker write failed'); }
            return $pre;
        }
    }
    if (!$write_owned_marker($network_marker, 'http')) { throw new RuntimeException('HTTP guard marker write failed'); }
    return new WP_Error('zmp_test_network_blocked', 'Only the one authorized local admin-post request is allowed.');
}, PHP_INT_MAX, 3);

try {
    if (!is_dir($mu_dir)) {
        if (!mkdir($mu_dir, 0700)) { throw new RuntimeException('cannot create configured MU-plugin directory'); }
        $mu_created = true;
        $mu_stat = @lstat($mu_dir);
        if (is_link($mu_dir) || !is_array($mu_stat) || !isset($mu_stat['dev'], $mu_stat['ino'])) {
            throw new RuntimeException('configured MU-plugin directory ownership identity unavailable');
        }
        $mu_identity = array('dev'=>(int)$mu_stat['dev'],'ino'=>(int)$mu_stat['ino']);
    }
    $mu_real = realpath($mu_dir);
    if (!$mu_real || strpos(wp_normalize_path($mu_real), wp_normalize_path($content_real) . '/') !== 0) { throw new RuntimeException('MU plugin directory escaped wp-content'); }
    $marker_initial_values = array(
        $mail_marker=>'not-called', $network_marker=>'not-called', $route_issue_marker=>'not-issued',
        $route_guard_loaded_marker=>'not-loaded', $route_host_marker=>'not-observed',
        $route_method_marker=>'not-observed', $route_path_marker=>'not-observed',
        $route_action_marker=>'not-observed', $route_request_id_marker=>'not-observed',
        $route_token_marker=>'not-observed', $route_decision_marker=>'not-observed',
        $route_seen_marker=>'not-seen', $route_complete_marker=>'not-completed',
    );
    foreach ($marker_initial_values as $marker_path=>$marker_value) {
        if (!$create_owned_marker($marker_path, $marker_value)) {
            throw new RuntimeException('mail/network/route marker initialization failed');
        }
    }
    if ($wpdb->last_error !== '') {
        throw new RuntimeException('mail/network guard marker initialization failed');
    }
    $updated_profile = $wpdb->update($profile_table,
        array('official_email'=>$reserved_email,'official_email_epoch'=>$reserved_epoch),
        array('id'=>$profile_id,'mentor_id'=>(int)$owner_mentor->id,'school_id'=>(int)$shared['owner']->id,
            'official_email'=>$baseline_email,'official_email_epoch'=>$baseline_epoch),
        array('%s','%d'), array('%d','%d','%d','%s','%d'));
    if ($updated_profile !== 1) { throw new RuntimeException('exact synthetic profile update did not affect one row'); }
    $profile_changed = true;
    $profile_now = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$profile_table} WHERE id=%d", $profile_id), ARRAY_A);
    if (!$profile_now || (string) $profile_now['official_email'] !== $reserved_email
        || (int) $profile_now['official_email_epoch'] !== $reserved_epoch) { throw new RuntimeException('synthetic profile email/epoch readback failed'); }

    $endpoint_parts = wp_parse_url($expected_route_url);
    if (!is_array($endpoint_parts) || ($endpoint_parts['scheme'] ?? '') !== 'http'
        || ($endpoint_parts['host'] ?? '') !== '127.0.0.1' || (int) ($endpoint_parts['port'] ?? 0) !== 8094
        || ($endpoint_parts['path'] ?? '') !== '/wp-admin/admin-post.php') { throw new RuntimeException('decision route is outside authorized loopback endpoint'); }

    wp_set_current_user($requester_id);
    $cli_flow = ZMP_Debug::request_id();
    $submission = ZMP_School_Email_Access::submit_request($requester_id, $owner_id, (int) $shared['requester']->id, $reason);
    if (is_wp_error($submission) || empty($submission['request_id']) || !empty($submission['existing'])) {
        throw new RuntimeException('unique synthetic pending request could not be created');
    }
    $request_id = (int) $submission['request_id']; $request_created = true;
    $pending = $request_row($request_id); $submitted_events = $request_events($request_id);
    if (!$pending || $request_id <= $request_max_before || (int)$pending['requester_user_id'] !== $requester_id
        || (int)$pending['owner_user_id'] !== $owner_id || (int)$pending['school_id'] !== (int)$shared['owner']->id
        || $pending['reason'] !== $reason || $pending['status'] !== 'pending'
        || $pending['pending_key'] !== $pending_key || $pending['approved_email_hash'] !== ''
        || (int)$pending['requester_engagement_id'] !== (int)$shared['requester']->id
        || (int)$pending['requester_engagement_epoch'] !== (int)$shared['requester']->engagement_epoch
        || (int)$pending['owner_engagement_id'] !== (int)$shared['owner']->id
        || (int)$pending['owner_engagement_epoch'] !== (int)$shared['owner']->engagement_epoch
        || (string)$pending['school_key'] !== (string)$shared['owner']->school_key
        || (int)$pending['approved_email_epoch'] !== 0 || count((array)$submitted_events) !== 1
        || $submitted_events[0]['event_type'] !== 'submitted' || (int)$submitted_events[0]['actor_user_id'] !== $requester_id) {
        throw new RuntimeException('synthetic request did not begin in the expected pending state');
    }
    $marker_paths_by_key = array(
        'mail'=>$mail_marker,'network'=>$network_marker,'guard-loaded'=>$route_guard_loaded_marker,
        'guard-host'=>$route_host_marker,'guard-method'=>$route_method_marker,'guard-path'=>$route_path_marker,
        'guard-action'=>$route_action_marker,'guard-request-id'=>$route_request_id_marker,
        'guard-token'=>$route_token_marker,'guard-decision'=>$route_decision_marker,
        'route-seen'=>$route_seen_marker,'route-complete'=>$route_complete_marker,
    );
    $marker_identity_by_key = array();
    foreach ($marker_paths_by_key as $marker_key=>$marker_path) {
        $identity = $marker_ownership[$marker_path] ?? null;
        if (!is_array($identity)) { throw new RuntimeException('temporary marker ownership identity unavailable'); }
        $marker_identity_by_key[$marker_key] = $identity;
    }
    $guard_source = str_replace(
        array('__MAIL_MARKER__','__NETWORK_MARKER__','__ROUTE_GUARD_LOADED_MARKER__','__ROUTE_HOST_MARKER__',
            '__ROUTE_METHOD_MARKER__','__ROUTE_PATH_MARKER__','__ROUTE_ACTION_MARKER__','__ROUTE_REQUEST_ID_MARKER__',
            '__ROUTE_TOKEN_MARKER__','__ROUTE_DECISION_MARKER__','__ROUTE_SEEN_MARKER__','__ROUTE_COMPLETE_MARKER__',
            '__MARKER_IDENTITIES__','__EXPECTED_ROUTE_TOKEN__','__EXPECTED_ROUTE_ACTION__','__EXPECTED_REQUEST_ID__'),
        array(basename($mail_marker),basename($network_marker),basename($route_guard_loaded_marker),basename($route_host_marker),
            basename($route_method_marker),basename($route_path_marker),basename($route_action_marker),basename($route_request_id_marker),
            basename($route_token_marker),basename($route_decision_marker),basename($route_seen_marker),basename($route_complete_marker),
            var_export($marker_identity_by_key, true),
            $route_marker_token,ZMP_School_Email_Access::DECIDE_ACTION,(string)$request_id),
        $guard_source
    );
    $guard_hash = hash('sha256', $guard_source);
    $guard_handle = @fopen($guard, 'x');
    if (!is_resource($guard_handle)) { throw new RuntimeException('temporary route guard exclusive creation failed'); }
    $guard_created = true;
    $guard_stat = fstat($guard_handle);
    if (!is_array($guard_stat) || !isset($guard_stat['dev'], $guard_stat['ino'])) {
        throw new RuntimeException('temporary route guard ownership identity unavailable');
    }
    $guard_identity = array('dev'=>(int)$guard_stat['dev'],'ino'=>(int)$guard_stat['ino']);
    $guard_length = strlen($guard_source);
    $guard_written = 0;
    $guard_write_ok = true;
    while ($guard_written < $guard_length) {
        $written = fwrite($guard_handle, substr($guard_source, $guard_written));
        if ($written === false || $written === 0) { $guard_write_ok = false; break; }
        $guard_written += $written;
    }
    if (!fflush($guard_handle)) { $guard_write_ok = false; }
    fclose($guard_handle);
    $guard_handle = null;
    if (!$guard_write_ok || $guard_written !== $guard_length
        || !hash_equals($guard_hash, (string)hash_file('sha256', $guard))) {
        throw new RuntimeException('temporary route guard write/hash failed');
    }

    $debug_after_submit = get_option($debug_option, array());
    $debug_after_submit = is_array($debug_after_submit) ? $debug_after_submit : array();
    foreach (array_slice($debug_after_submit, count($debug_before)) as $event) {
        if (is_array($event) && (string)($event['context']['flow_id'] ?? '') === $cli_flow) { $debug_events[] = $event; }
    }
    if (!$debug_events || !in_array('School email access request submitted.', array_column($debug_events, 'message'), true)) {
        throw new RuntimeException('synthetic request debug events could not be attributed');
    }
    $route_debug_test_events = $debug_events;

    $session_cookie = $make_president_cookie();
    $_COOKIE[$cookie_name] = $session_cookie;
    wp_set_current_user($president_id);
    $nonce_action = 'zmp_school_email_access_decide_' . $request_id;
    $nonce = wp_create_nonce($nonce_action);
    $route_debug_baseline = get_option($debug_option, array());
    if (!is_array($route_debug_baseline)) { throw new RuntimeException('diagnostic option changed shape before route POST'); }
    $expected_debug_before_route = array_merge($debug_before, $debug_events);
    if (wp_json_encode($route_debug_baseline) !== wp_json_encode($expected_debug_before_route)) {
        throw new RuntimeException('diagnostic option changed unexpectedly before route POST');
    }
    $route_started = true;
    $response = wp_remote_post($expected_route_url, array(
        'timeout'=>15, 'redirection'=>0,
        'headers'=>array('Referer'=>admin_url('profile.php'),'Cookie'=>$cookie_name . '=' . $session_cookie),
        'body'=>array('action'=>ZMP_School_Email_Access::DECIDE_ACTION,'request_id'=>$request_id,
            'decision'=>'approved','_wpnonce'=>$nonce,'zmp65_route_marker'=>$route_marker_token),
    ));
    if (is_wp_error($response)) { $transport_ambiguous = true; throw new RuntimeException('approved decision HTTP response was ambiguous'); }
    $http_code = (int) wp_remote_retrieve_response_code($response);
    $location = (string) wp_remote_retrieve_header($response, 'location');
    $assert('president_approved_route_returns_generic_302', $http_code === 302 && strpos($location, 'zmp_school_email_status=request_approved') !== false);
    $assert('approved_response_contains_no_email_address', $no_address_response($response));

    $approved = $request_row($request_id); $approved_events = $request_events($request_id);
    $expected_hash = hash('sha256', strtolower(trim($reserved_email)));
    $assert('approved_request_state_actor_and_time', $approved && $approved['status'] === 'approved'
        && $approved['pending_key'] === null && (int)$approved['decided_by'] === $president_id
        && !empty($approved['decided_at']));
    $assert('approved_email_hash_and_epoch_match_synthetic_profile', $approved
        && hash_equals($expected_hash, (string)$approved['approved_email_hash'])
        && (int)$approved['approved_email_epoch'] === $reserved_epoch);
    $approval_event = count((array)$approved_events) === 2 ? $approved_events[1] : null;
    $approval_data = $approval_event ? json_decode((string)$approval_event['event_data'], true) : null;
    $assert('one_approved_event_has_exact_relationship_actor_and_time', $approval_event
        && $approval_event['event_type'] === 'approved' && (int)$approval_event['actor_user_id'] === $president_id
        && (string)$approval_event['event_at'] === (string)$approved['decided_at']
        && (int)$approval_event['request_id'] === $request_id
        && $approval_data === array('requester_user_id'=>$requester_id,'owner_user_id'=>$owner_id,'school_id'=>(int)$shared['owner']->id));
    $assert('submitted_event_remains_unchanged', count((array)$approved_events) === 2
        && $approved_events[0]['event_type'] === 'submitted' && (int)$approved_events[0]['actor_user_id'] === $requester_id
        && (int)$approved_events[0]['id'] === (int)$submitted_events[0]['id']
        && (string)$approved_events[0]['event_at'] === (string)$submitted_events[0]['event_at']
        && (string)$approved_events[0]['event_data'] === (string)$submitted_events[0]['event_data']);
    wp_cache_delete($debug_option, 'options');
    wp_cache_delete('alloptions', 'options');
    $route_debug_rows_after = $wpdb->get_results($wpdb->prepare(
        "SELECT option_id,option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $debug_option
    ), ARRAY_A);
    $route_debug_row_valid = $wpdb->last_error === '' && is_array($route_debug_rows_after)
        && count($route_debug_rows_after) === 1;
    $route_debug_after = $route_debug_row_valid
        ? maybe_unserialize((string)$route_debug_rows_after[0]['option_value']) : null;
    $route_debug_row_valid = $route_debug_row_valid && is_array($route_debug_after);
    if (!$route_debug_row_valid) { $route_debug_after = array(); }
    $debug_prefix_matches = count($route_debug_after) >= count($route_debug_baseline)
        && wp_json_encode(array_slice($route_debug_after, 0, count($route_debug_baseline))) === wp_json_encode($route_debug_baseline);
    $route_debug_events = $debug_prefix_matches ? array_values(array_slice($route_debug_after, count($route_debug_baseline))) : array();
    $approved_debug = array_values(array_filter($route_debug_events, static function ($event) use ($request_id, $shared, $president_id) {
        return is_array($event) && ($event['message'] ?? '') === 'School email request decision recorded.'
            && ($event['level'] ?? '') === 'info' && (int)($event['user_id'] ?? 0) === $president_id
            && preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/', (string)($event['context']['flow_id'] ?? ''))
            && (int)($event['context']['request_id'] ?? 0) === $request_id
            && (int)($event['context']['school_id'] ?? 0) === (int)$shared['owner']->id
            && ($event['context']['outcome'] ?? '') === 'approved';
    }));
    $approved_flow_id = count($approved_debug) === 1 ? (string)($approved_debug[0]['context']['flow_id'] ?? '') : '';
    $init_probe_debug = array_values(array_filter($route_debug_events, static function ($event) use ($president_id, $approved_flow_id) {
        return is_array($event) && ($event['message'] ?? '') === 'ZMP test request reached WordPress init.'
            && ($event['level'] ?? '') === 'info' && (int)($event['user_id'] ?? 0) === $president_id
            && (string)($event['context']['flow_id'] ?? '') === $approved_flow_id
            && ($event['context']['form_action'] ?? '') === ZMP_School_Email_Access::DECIDE_ACTION
            && (int)($event['context']['post_fields_count'] ?? 0) === 5
            && (int)($event['context']['logged_in'] ?? 0) === 1
            && (int)($event['context']['role_count'] ?? 0) === 1;
    }));
    $assert('approved_debug_event_has_static_request_context', $route_debug_row_valid
        && $debug_prefix_matches && count($approved_debug) === 1);
    $assert('safe_init_probe_event_is_allowed_once', $route_debug_row_valid
        && $debug_prefix_matches && count($route_debug_events) === 2 && count($init_probe_debug) === 1);
    $route_debug_test_events = array_merge($debug_events, $route_debug_events);
    $route_issue_verified = $read_owned_marker($route_issue_marker) === 'issued';
    $route_guard_loaded_verified = $read_owned_marker($route_guard_loaded_marker) === 'loaded';
    $route_host_verified = $read_owned_marker($route_host_marker) === 'pass';
    $route_method_verified = $read_owned_marker($route_method_marker) === 'pass';
    $route_path_verified = $read_owned_marker($route_path_marker) === 'pass';
    $route_action_verified = $read_owned_marker($route_action_marker) === 'pass';
    $route_request_id_verified = $read_owned_marker($route_request_id_marker) === 'pass';
    $route_token_verified = $read_owned_marker($route_token_marker) === 'pass';
    $route_decision_verified = $read_owned_marker($route_decision_marker) === 'pass';
    $route_seen_verified = $read_owned_marker($route_seen_marker) === 'seen';
    $route_complete_verified = $read_owned_marker($route_complete_marker) === 'complete';
    $assert('reserved_email_is_never_disclosed_by_decision_response', strpos(strtolower($location . (string)wp_remote_retrieve_body($response)), $reserved_email) === false);
    $assert('exact_local_approval_post_issued_marker_verified', $route_issue_verified);
    $assert('temporary_mu_guard_loaded_marker_verified', $route_guard_loaded_verified);
    $assert('mu_guard_host_predicate_verified', $route_host_verified);
    $assert('mu_guard_method_predicate_verified', $route_method_verified);
    $assert('mu_guard_path_predicate_verified', $route_path_verified);
    $assert('mu_guard_action_predicate_verified', $route_action_verified);
    $assert('mu_guard_request_id_predicate_verified', $route_request_id_verified);
    $assert('mu_guard_token_predicate_verified', $route_token_verified);
    $assert('mu_guard_approval_decision_predicate_verified', $route_decision_verified);
    $assert('exact_local_approval_post_seen_marker_verified', $route_seen_verified);
    $assert('exact_local_approval_post_completion_marker_verified', $route_complete_verified);
    $marker_contents = $read_owned_marker($mail_marker);
    $assert('no_mail_or_outbound_http_attempted', $marker_contents === 'not-called' && $read_owned_marker($network_marker) === 'not-called');
    $route_ok = count($failures) === 0;
} catch (Throwable $error) {
    $failures[] = 'runtime_exception';
    fwrite(STDERR, ($transport_ambiguous ? 'INDETERMINATE: ' : 'FAIL: ') . sanitize_text_field($error->getMessage()) . "\n");
} finally {
    if ($transport_ambiguous) {
        for ($attempt=0; $attempt<20; $attempt++) {
            if ($read_owned_marker($route_complete_marker) === 'complete') { break; }
            usleep(100000);
        }
        if ($read_owned_marker($route_complete_marker) !== 'complete') {
            $ambiguous_completion_unverified = true;
        }
    }
    if (!$ambiguous_completion_unverified) {
    if ($request_id < 1) {
        $candidate = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$request_table} WHERE id>%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s", $request_max_before,$requester_id,$owner_id,(int)$shared['owner']->id,$reason));
        if ($wpdb->last_error === '' && is_array($candidate) && count($candidate) === 1) { $request_id = (int)$candidate[0]->id; }
    }
    if ($request_id > 0) {
        $identity = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$request_table} WHERE id=%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s",$request_id,$requester_id,$owner_id,(int)$shared['owner']->id,$reason));
        if ($wpdb->last_error === '' && $identity === 1) {
            $wpdb->delete($event_table,array('request_id'=>$request_id),array('%d'));
            $wpdb->delete($request_table,array('id'=>$request_id,'requester_user_id'=>$requester_id,'owner_user_id'=>$owner_id,'school_id'=>(int)$shared['owner']->id,'reason'=>$reason),array('%d','%d','%d','%d','%s'));
            $request_cleanup = $wpdb->last_error === ''
                && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$request_table} WHERE id=%d",$request_id)) === 0
                && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d",$request_id)) === 0;
        }
    } else {
        $remaining_candidates = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$request_table} WHERE requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s",$requester_id,$owner_id,(int)$shared['owner']->id,$reason));
        $request_cleanup = $wpdb->last_error === '' && is_array($remaining_candidates) && count($remaining_candidates) === 0;
    }

    if ($profile_changed) {
        $profile_current = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$profile_table} WHERE id=%d",$profile_id),ARRAY_A);
        $expected_fixture = $profile_expected;
        if ($profile_current && wp_json_encode($profile_current) === wp_json_encode($expected_fixture)) {
            $wpdb->update($profile_table,array('official_email'=>$baseline_email,'official_email_epoch'=>$baseline_epoch),
                array('id'=>$profile_id,'official_email'=>$reserved_email,'official_email_epoch'=>$reserved_epoch),array('%s','%d'),array('%d','%s','%d'));
        }
        $profile_restored = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$profile_table} WHERE id=%d",$profile_id),ARRAY_A);
        $profile_cleanup = $wpdb->last_error === '' && $profile_restored && wp_json_encode($profile_restored) === wp_json_encode($profile_before)
            && hash_equals($profile_snapshot_hash,hash('sha256',wp_json_encode($profile_restored)));
    } else {
        $profile_unchanged = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$profile_table} WHERE id=%d",$profile_id),ARRAY_A);
        $profile_cleanup = $wpdb->last_error === '' && $profile_unchanged
            && hash_equals($profile_snapshot_hash,hash('sha256',wp_json_encode($profile_unchanged)));
    }

    $sessions_removed = true;
    if ($session_manager && $session_token !== '') { $session_manager->destroy($session_token); if ($session_manager->verify($session_token)) { $sessions_removed = false; } }

    wp_cache_delete($debug_option, 'options');
    wp_cache_delete('alloptions', 'options');
    $debug_rows_now = $wpdb->get_results($wpdb->prepare(
        "SELECT option_id,option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $debug_option
    ), ARRAY_A);
    $debug_rows_safe_to_restore = $wpdb->last_error === '' && is_array($debug_rows_now) && count($debug_rows_now) <= 1;
    if ($debug_rows_safe_to_restore && $debug_rows_now) {
        $current_debug = maybe_unserialize((string)$debug_rows_now[0]['option_value']);
        $expected_debug_after_test = array_merge($debug_before, $route_debug_test_events);
        $debug_rows_safe_to_restore = is_array($current_debug)
            && wp_json_encode($current_debug) === wp_json_encode($expected_debug_after_test);
    }
    if ($debug_rows_safe_to_restore) {
        if ($debug_option_existed) {
            if (count($debug_rows_now) === 1 && (int)$debug_rows_now[0]['option_id'] === (int)$debug_option_row_before['option_id']) {
                $updated = $wpdb->update($wpdb->options,
                    array('option_value'=>$debug_option_row_before['option_value'],'autoload'=>$debug_option_row_before['autoload']),
                    array('option_id'=>(int)$debug_option_row_before['option_id'],'option_name'=>$debug_option),
                    array('%s','%s'), array('%d','%s')
                );
                $debug_cleanup = $updated !== false;
            } elseif (count($debug_rows_now) === 0) {
                $id_occupied = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_id=%d", (int)$debug_option_row_before['option_id']));
                if ($id_occupied === 0) {
                    $debug_cleanup = $wpdb->insert($wpdb->options, array(
                        'option_id'=>(int)$debug_option_row_before['option_id'],'option_name'=>$debug_option,
                        'option_value'=>$debug_option_row_before['option_value'],'autoload'=>$debug_option_row_before['autoload'],
                    ), array('%d','%s','%s','%s')) !== false;
                }
            }
        } elseif (count($debug_rows_now) === 0) {
            $debug_cleanup = true;
        } else {
            $debug_row_now = $debug_rows_now[0];
            $deleted = $wpdb->delete($wpdb->options, array(
                'option_id'=>(int)$debug_row_now['option_id'],'option_name'=>$debug_option,
                'option_value'=>$debug_row_now['option_value'],'autoload'=>$debug_row_now['autoload'],
            ), array('%d','%s','%s','%s'));
            $debug_cleanup = $deleted === 1;
        }
    }
    wp_cache_delete($debug_option, 'options');
    wp_cache_delete('alloptions', 'options');
    $debug_rows_after_restore = $wpdb->get_results($wpdb->prepare(
        "SELECT option_id,option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",
        $debug_option
    ), ARRAY_A);
    if ($debug_option_existed) {
        $debug_cleanup = $debug_cleanup && is_array($debug_rows_after_restore) && count($debug_rows_after_restore) === 1
            && (int)$debug_rows_after_restore[0]['option_id'] === (int)$debug_option_row_before['option_id']
            && $debug_rows_after_restore[0]['option_value'] === $debug_option_row_before['option_value']
            && $debug_rows_after_restore[0]['autoload'] === $debug_option_row_before['autoload'];
        $debug_readback = get_option($debug_option, null);
        $debug_cleanup = $debug_cleanup && is_array($debug_readback)
            && wp_json_encode($debug_readback) === wp_json_encode($debug_before);
    } else {
        $debug_cleanup = $debug_cleanup && is_array($debug_rows_after_restore) && count($debug_rows_after_restore) === 0
            && get_option($debug_option, null) === null;
    }

    foreach ($marker_open_handles as $open_marker_path=>$open_marker_handle) {
        if (!is_resource($open_marker_handle)) { unset($marker_open_handles[$open_marker_path]); continue; }
        if (!is_array($marker_ownership[$open_marker_path] ?? null)) {
            $retry_stat = @fstat($open_marker_handle);
            if (is_array($retry_stat) && isset($retry_stat['dev'], $retry_stat['ino'])) {
                $marker_ownership[$open_marker_path] = array('dev'=>(int)$retry_stat['dev'],'ino'=>(int)$retry_stat['ino']);
            }
        }
        fclose($open_marker_handle);
        unset($marker_open_handles[$open_marker_path]);
    }
    if ($guard_created && !is_array($guard_identity) && is_resource($guard_handle)) {
        $guard_retry_stat = @fstat($guard_handle);
        if (is_array($guard_retry_stat) && isset($guard_retry_stat['dev'], $guard_retry_stat['ino'])) {
            $guard_identity = array('dev'=>(int)$guard_retry_stat['dev'],'ino'=>(int)$guard_retry_stat['ino']);
        }
    }
    if (is_resource($guard_handle)) { fclose($guard_handle); $guard_handle = null; }

    $guard_path_present = file_exists($guard) || is_link($guard);
    if (!$guard_path_present) {
        $guard_removed = true;
    } elseif ($guard_created && is_array($guard_identity) && !is_link($guard) && is_file($guard)) {
        $guard_path_stat = @lstat($guard);
        $guard_still_owned = is_array($guard_path_stat)
            && (int)$guard_path_stat['dev'] === $guard_identity['dev']
            && (int)$guard_path_stat['ino'] === $guard_identity['ino'];
        if ($guard_still_owned) {
            $guard_removed = unlink($guard) && !file_exists($guard) && !is_link($guard);
        }
    }
    $mail_value = $read_owned_marker($mail_marker);
    $network_value = $read_owned_marker($network_marker);
    $markers_removed = true;
    foreach ($marker_paths as $path) {
        $path_present = file_exists($path) || is_link($path) || @lstat($path) !== false;
        if (!$path_present) { continue; }
        $owned_identity = $marker_ownership[$path] ?? null;
        if (is_link($path) || !is_array($owned_identity) || !$marker_identity_matches($path, $owned_identity)
            || !is_file($path) || !unlink($path)
            || file_exists($path) || is_link($path) || @lstat($path) !== false) {
            $markers_removed = false;
        }
    }
    if ($mu_created) {
        $mu_path_stat = is_link($mu_dir) ? false : @lstat($mu_dir);
        $mu_still_owned = is_array($mu_identity) && is_array($mu_path_stat)
            && (int)$mu_path_stat['dev'] === $mu_identity['dev']
            && (int)$mu_path_stat['ino'] === $mu_identity['ino'];
        if ($mu_still_owned && is_dir($mu_dir)) { $mu_removed=rmdir($mu_dir)&&!is_dir($mu_dir)&&!is_link($mu_dir); }
        else { $mu_removed=false; }
    }
    }
}

if ($ambiguous_completion_unverified) {
    fwrite(STDERR, "INDETERMINATE: local route completion could not be verified after the HTTP transport error; cleanup was deliberately withheld to avoid racing an in-flight decision. The synthetic fixture/guard may remain for controlled recovery.\n");
    exit(3);
}

$request_after = $wpdb->get_results("SELECT * FROM {$request_table} ORDER BY id",ARRAY_A);
$events_after = $wpdb->get_results("SELECT * FROM {$event_table} ORDER BY id",ARRAY_A);
$assert('exact_request_and_event_cleanup_preserves_prior_rows', $request_cleanup
    && $wpdb->last_error === '' && hash_equals($request_snapshot_hash,hash('sha256',wp_json_encode($request_after)))
    && hash_equals($event_snapshot_hash,hash('sha256',wp_json_encode($events_after))));
$assert('profile_email_epoch_exactly_restored', $profile_cleanup);
$assert('session_debug_option_exact_restoration_and_guard_cleanup_verified', $sessions_removed && $debug_cleanup && $guard_removed && $markers_removed && $mu_removed);
$assert('mail_and_outbound_http_were_not_attempted', $mail_value === 'not-called' && $network_value === 'not-called');
foreach ($assertions as $name=>$status) { echo $status . ' ' . $name . "\n"; }
if ($transport_ambiguous) { fwrite(STDERR,"INDETERMINATE: local approval POST response was ambiguous; inspect exact request before interpreting route behavior.\n"); exit(3); }
echo 'SUMMARY ' . count(array_filter($assertions,static function($status){return $status==='PASS';})) . '/' . count($assertions) . " PASS\n";
exit($failures ? 1 : 0);
