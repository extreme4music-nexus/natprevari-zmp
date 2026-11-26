<?php
/**
 * One bounded synthetic Teacher proposal + active-President approve_new flow.
 * Review the exact hash before one local execution. Never use this on a live
 * site or with non-synthetic accounts/data.
 */
global $wpdb;
$abort = static function ($message) { fwrite(STDERR, "BLOCKED: {$message}\n"); exit(2); };
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_isolated'
    || !isset($wpdb) || $wpdb->prefix !== 'z65_') { $abort('wrong_database_or_prefix'); }
$site = wp_parse_url(home_url('/'));
$endpoint = wp_parse_url(admin_url('admin-post.php'));
if (!is_array($site) || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8094 || !is_array($endpoint)
    || ($endpoint['scheme'] ?? '') !== 'http' || ($endpoint['host'] ?? '') !== '127.0.0.1'
    || (int) ($endpoint['port'] ?? 0) !== 8094 || ($endpoint['path'] ?? '') !== '/wp-admin/admin-post.php'
    || isset($endpoint['user']) || isset($endpoint['pass']) || isset($endpoint['query']) || isset($endpoint['fragment'])) {
    $abort('wrong_loopback_site_or_route');
}
$active_plugins = (array) get_option('active_plugins', array());
if ($active_plugins !== array('zmp-competitions/zmp-competitions.php')) { $abort('unexpected_active_plugin_inventory'); }
$source_hashes = array(
    'class-zmp-mentor.php' => '897298C52A8317378392245A68A3DCFD09B60E06A35EDDDB32D0E1433FC03FC2',
    'class-zmp-president-subject-approvals.php' => '720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4',
    'class-zmp-mentor-subject-proposals.php' => '8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69',
    'class-zmp-mentor-subjects.php' => 'A350933909C8046AB98B6F6CA9DC21DBAC68CD090ECC00F4BCCF89EBBF5475F6',
    'class-zmp-president-mandate.php' => '5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40',
    'class-zmp-debug.php' => 'ED252BEF7763DB792AE684117586693A688EA952412966F48CBEB2B2F5BCBEC6',
);
$plugin_root = trailingslashit(WP_PLUGIN_DIR) . 'zmp-competitions/includes/';
foreach ($source_hashes as $file => $expected_hash) {
    $path = $plugin_root . $file;
    if (!is_readable($path) || !hash_equals($expected_hash, strtoupper(hash_file('sha256', $path)))) {
        $abort('installed_route_source_hash_mismatch');
    }
}
foreach (array('ZMP_Mentor','ZMP_Mentor_Subject_Proposals','ZMP_President_Subject_Approvals','ZMP_President_Mandate','ZMP_Mentor_Subjects','ZMP_Debug') as $class) {
    if (!class_exists($class)) { $abort('required_route_class_unavailable'); }
}
require_once ABSPATH . 'wp-admin/includes/user.php';
if (!ZMP_Mentor_Subject_Proposals::schema_is_compatible()) { $abort('proposal_schema_not_ready'); }

$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$event_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$catalog_table = $wpdb->prefix . 'zmp_subject_catalog';
$alias_table = $wpdb->prefix . 'zmp_subject_aliases';
$mandate_table = ZMP_President_Mandate::table_name();
$mandate_audit_table = ZMP_President_Mandate::audit_table_name();
$tracked_tables = array($wpdb->users, $wpdb->usermeta, $wpdb->posts, $wpdb->postmeta, $wpdb->options,
    $proposal_table, $event_table, $catalog_table, $alias_table, $mandate_table, $mandate_audit_table);
$table_snapshot = static function ($table) use ($wpdb) {
    if (!is_string($table) || !preg_match('/\A[A-Za-z0-9_]+\z/D', $table)) { return false; }
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    if ((string) $found !== $table) { return false; }
    $rows = $wpdb->get_results('SELECT * FROM `' . esc_sql($table) . '`', ARRAY_A);
    if (!is_array($rows) || $wpdb->last_error !== '') { return false; }
    $encoded = array();
    foreach ($rows as $row) { $encoded[] = wp_json_encode($row); }
    if (in_array(false, $encoded, true)) { return false; }
    sort($encoded, SORT_STRING);
    return hash('sha256', implode("\n", $encoded));
};
$baseline_tables = array();
foreach ($tracked_tables as $table) {
    $baseline_tables[$table] = $table_snapshot($table);
    if ($baseline_tables[$table] === false) { $abort('required_baseline_table_unavailable'); }
}
foreach (array($proposal_table, $event_table, $catalog_table, $alias_table) as $table) {
    $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($table) . '`');
    if ($wpdb->last_error !== '' || $count !== 0) { $abort('first_catalog_requires_empty_subject_tables'); }
}

$president_id = 25; // Pre-validated synthetic local test principal; never choose among accounts dynamically.
$president_identity_fingerprint = '80bd4ad310d9b1cd35a03e9a85ca87120539cb984450bd81b5eda862e240f17c';
$president_candidate = get_userdata($president_id);
$today = current_time('Y-m-d');
$president_fingerprint = $president_candidate
    ? hash('sha256', (string)$president_candidate->user_login . '|' . (string)$president_candidate->user_email . '|' . (string)$president_candidate->display_name)
    : '';
$active_mandate_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$mandate_table} WHERE user_id=%d AND status='active' AND start_date<=%s AND end_date>=%s",
    $president_id, $today, $today
));
if (!$president_candidate || !hash_equals($president_identity_fingerprint, $president_fingerprint)
    || array_values((array) $president_candidate->roles) !== array('zmp_president')
    || substr(strtolower((string) $president_candidate->user_email), -8) !== '.invalid'
    || strpos((string) $president_candidate->display_name, 'Synthetic') !== 0
    || !ZMP_President_Mandate::user_has_active_mandate($president_id)
    || $wpdb->last_error !== '' || $active_mandate_count !== 1) {
    $abort('exact_prevalidated_synthetic_president_mandate_required');
}

$diagnostic_names = array(ZMP_Debug::OPTION, ZMP_Debug::CAPTURE_OPTION);
$diagnostic_baseline = array();
foreach ($diagnostic_names as $option_name) {
    $diagnostic_baseline[$option_name] = $wpdb->get_row($wpdb->prepare(
        "SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $option_name
    ), ARRAY_A);
    if ($wpdb->last_error !== '') { $abort('diagnostic_option_baseline_unavailable'); }
}
$initial_user_id = get_current_user_id();
$initial_cookie = $_COOKIE;
$initial_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
$checks = array();
$failures = array();
$assert = static function ($name, $ok) use (&$checks, &$failures) { $checks[$name] = $ok ? 'PASS' : 'FAIL'; if (!$ok) { $failures[] = $name; } };
$created_teacher_id = 0;
$teacher_login = '';
$teacher_email = '';
$president_login = '';
$president_email = '';
$phrase = '';
$subject_key = '';
$proposal_id = 0;
$canonical_id = 0;
$teacher_session = null;
$teacher_token = '';
$president_session = null;
$president_token = '';
$mail_attempts = 0;
$blocked_http_attempts = 0;
$request_count = 0;
$transport_error = false;
$cleanup_ok = true;
$route_url = admin_url('admin-post.php');
$network_guard = static function ($pre, $args, $url) use (&$blocked_http_attempts, $route_url) {
    if (is_string($url) && hash_equals($route_url, $url)
        && strtoupper((string) ($args['method'] ?? 'GET')) === 'POST') { return $pre; }
    $blocked_http_attempts++;
    return new WP_Error('zmp65_external_http_blocked');
};
$mail_guard = static function ($short_circuit, $atts) use (&$mail_attempts) { $mail_attempts++; return true; };
add_filter('pre_http_request', $network_guard, PHP_INT_MAX, 3);
add_filter('pre_wp_mail', $mail_guard, PHP_INT_MAX, 2);
    $make_session = static function ($user_id, &$manager, &$token) {
    $manager = WP_Session_Tokens::get_instance((int) $user_id);
    $expires = time() + 300;
    $token = $manager->create($expires);
    if (!is_string($token) || $token === '') { throw new RuntimeException('session_creation_failed'); }
    return wp_generate_auth_cookie((int) $user_id, $expires, 'logged_in', $token);
};
$post_route = static function ($cookie, $fields) use (&$request_count, &$transport_error, $route_url) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $headers = array('Referer'=>home_url('/teacher-register'), 'Cookie'=>LOGGED_IN_COOKIE . '=' . $cookie);
    $request_count++;
    $response = wp_remote_post($route_url, array(
        'timeout'=>15, 'redirection'=>0, 'headers'=>$headers,
        'body'=>array_merge(array('action'=>$fields['action'], '_wpnonce'=>$fields['_wpnonce']), $fields['body']),
    ));
    if (is_wp_error($response)) { $transport_error = true; throw new RuntimeException('local_route_transport_failed'); }
    return $response;
};
$response_is_private = static function ($response) use (&$teacher_login, &$teacher_email, &$president_login, &$president_email, &$phrase) {
    $body = (string) wp_remote_retrieve_body($response);
    $location = (string) wp_remote_retrieve_header($response, 'location');
    foreach (array($teacher_login, $teacher_email, $president_login, $president_email, $phrase) as $sensitive) {
        if ($sensitive !== '' && (stripos($body, $sensitive) !== false || stripos($location, $sensitive) !== false)) { return false; }
    }
    return true;
};

try {
    $suffix = strtolower(wp_generate_password(20, false, false));
    $teacher_login = 'zmp65_firstcatalog_' . $suffix;
    $teacher_email = $teacher_login . '@example.invalid';
    $phrase = 'Synthetic First Catalog ' . strtoupper(substr($suffix, 0, 8));
    $teacher_id = wp_insert_user(array(
        'user_login'=>$teacher_login, 'user_pass'=>wp_generate_password(48, true, true),
        'user_email'=>$teacher_email, 'display_name'=>'Synthetic First Catalog Teacher', 'role'=>'zmp_teacher',
    ));
    if (is_wp_error($teacher_id) || (int) $teacher_id < 1) { throw new RuntimeException('synthetic_teacher_creation_failed'); }
    $created_teacher_id = (int) $teacher_id;
    $teacher = get_userdata($created_teacher_id);
    if (!$teacher || $teacher->user_login !== $teacher_login || $teacher->user_email !== $teacher_email
        || array_values((array) $teacher->roles) !== array('zmp_teacher')) { throw new RuntimeException('synthetic_teacher_identity_failed'); }
    $subject_key = hash('sha256', strtolower($phrase));

    $teacher_cookie = $make_session($created_teacher_id, $teacher_session, $teacher_token);
    $_COOKIE[LOGGED_IN_COOKIE] = $teacher_cookie;
    wp_set_current_user($created_teacher_id);
    $teacher_nonce = wp_create_nonce('zmp_submit_subject_proposal');
    $teacher_response = $post_route($teacher_cookie, array(
        'action'=>'zmp_submit_subject_proposal', '_wpnonce'=>$teacher_nonce,
        'body'=>array('zmp_subject_name'=>$phrase),
    ));
    $teacher_status = (int) wp_remote_retrieve_response_code($teacher_response);
    $assert('teacher_proposal_route_redirects_without_disclosure', $teacher_status === 302 && $response_is_private($teacher_response));
    $proposal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$proposal_table} WHERE proposal_key=%s", $subject_key), ARRAY_A);
    $proposal_id = is_array($proposal) ? (int) $proposal['id'] : 0;
    $submitted_events = $proposal_id ? $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$event_table} WHERE proposal_id=%d ORDER BY id", $proposal_id
    ), ARRAY_A) : array();
    $assert('teacher_submission_exact_pending_row_and_audit', $wpdb->last_error === '' && $proposal_id > 0
        && $proposal['proposed_name'] === $phrase && $proposal['status'] === 'pending'
        && (int) $proposal['proposer_id'] === $created_teacher_id && (int) $proposal['decided_by'] === 0
        && (int) $proposal['canonical_subject_id'] === 0 && count((array) $submitted_events) === 1
        && $submitted_events[0]['event_type'] === 'submitted' && (int) $submitted_events[0]['actor_id'] === $created_teacher_id);

    $president = get_userdata($president_id);
    $president_login = $president ? (string) $president->user_login : '';
    $president_email = $president ? (string) $president->user_email : '';
    $assert('active_synthetic_president_role_and_mandate_verified', $president
        && array_values((array) $president->roles) === array('zmp_president')
        && substr(strtolower((string) $president->user_email), -8) === '.invalid'
        && ZMP_President_Mandate::user_has_active_mandate($president_id));
    $president_cookie = $make_session($president_id, $president_session, $president_token);
    $_COOKIE[LOGGED_IN_COOKIE] = $president_cookie;
    wp_set_current_user($president_id);
    $president_nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    $decision_fields = array('action'=>ZMP_President_Subject_Approvals::ACTION, '_wpnonce'=>$president_nonce,
        'body'=>array('proposal_id'=>(string) $proposal_id, 'decision'=>'approve_new', 'canonical_subject_id'=>'0'));
    $approval_response = $post_route($president_cookie, $decision_fields);
    $assert('active_president_approve_new_route_redirects_without_disclosure',
        (int) wp_remote_retrieve_response_code($approval_response) === 302
        && strpos((string) wp_remote_retrieve_header($approval_response, 'location'), 'zmp_subject_decision_status=approved') !== false
        && $response_is_private($approval_response));
    $approved = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$proposal_table} WHERE id=%d", $proposal_id), ARRAY_A);
    $canonical_id = is_array($approved) ? (int) $approved['canonical_subject_id'] : 0;
    $catalog = $canonical_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$catalog_table} WHERE id=%d", $canonical_id), ARRAY_A) : null;
    $events = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$event_table} WHERE proposal_id=%d ORDER BY id", $proposal_id), ARRAY_A);
    $approved_event = is_array($events) && count($events) === 2 ? json_decode((string) $events[1]['event_data'], true) : null;
    $assert('exact_approved_proposal_canonical_and_audit_readback', $wpdb->last_error === ''
        && is_array($approved) && $approved['status'] === 'approved'
        && (int) $approved['proposer_id'] === $created_teacher_id && (int) $approved['decided_by'] === $president_id
        && $approved['decided_at'] !== null && $canonical_id > 0
        && is_array($catalog) && $catalog['subject_name'] === $phrase && $catalog['subject_key'] === $subject_key
        && $catalog['status'] === 'approved' && (int) $catalog['is_active'] === 1
        && (int) $catalog['created_by'] === $president_id && (int) $catalog['approved_by'] === $president_id
        && count((array) $events) === 2 && $events[0]['event_type'] === 'submitted'
        && (int) $events[0]['proposal_id'] === $proposal_id && (int) $events[0]['actor_id'] === $created_teacher_id
        && $events[0]['event_at'] !== '' && json_decode((string)$events[0]['event_data'], true) === array('proposed_name'=>$phrase)
        && $events[1]['event_type'] === 'approved_new' && (int) $events[1]['proposal_id'] === $proposal_id
        && (int) $events[1]['actor_id'] === $president_id && $events[1]['event_at'] !== ''
        && $approved_event === array('canonical_subject_id'=>$canonical_id));

    // The same approval POST after commit must fail closed and must not create another catalog row/event.
    $duplicate_response = $post_route($president_cookie, $decision_fields);
    $after_duplicate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$proposal_table} WHERE id=%d", $proposal_id), ARRAY_A);
    $events_after_duplicate = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$event_table} WHERE proposal_id=%d ORDER BY id", $proposal_id), ARRAY_A);
    $catalog_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$catalog_table} WHERE subject_key=%s", $subject_key));
    $dup_location = (string) wp_remote_retrieve_header($duplicate_response, 'location');
    $assert('duplicate_approve_new_fails_without_second_subject_or_audit', (int) wp_remote_retrieve_response_code($duplicate_response) === 302
        && strpos($dup_location, 'zmp_subject_decision_status=failed') !== false
        && is_array($after_duplicate) && $after_duplicate === $approved
        && count((array) $events_after_duplicate) === 2 && $catalog_count === 1
        && $response_is_private($duplicate_response));
    $assert('no_email_attempt_or_external_http_attempt', $mail_attempts === 0 && $blocked_http_attempts === 0);
} catch (Throwable $error) {
    // Emit only a bounded exception class. Never include form/session/identity/database values.
    fwrite(STDERR, 'FAIL: route candidate stopped with ' . get_class($error) . "; no request retry\n");
    $failures[] = 'route_attempt_completed';
} finally {
    remove_filter('pre_http_request', $network_guard, PHP_INT_MAX);
    remove_filter('pre_wp_mail', $mail_guard, PHP_INT_MAX);
    $_COOKIE = $initial_cookie;
    if ($initial_request_method === null) { unset($_SERVER['REQUEST_METHOD']); } else { $_SERVER['REQUEST_METHOD'] = $initial_request_method; }
    wp_set_current_user($initial_user_id);
    if ($teacher_session && $teacher_token !== '') { $teacher_session->destroy($teacher_token); }
    if ($president_session && $president_token !== '') { $president_session->destroy($president_token); }

    $safe_to_cleanup = $created_teacher_id > 0 && $phrase !== '' && $subject_key !== '';
    if ($safe_to_cleanup) {
        $teacher_cleanup_safe = true;
        $find_own_proposal = static function () use ($wpdb, $proposal_table, $subject_key) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$proposal_table} WHERE proposal_key=%s", $subject_key), ARRAY_A);
        };
        $rows = $find_own_proposal();
        if ($transport_error) {
            for ($attempt = 0; $attempt < 20 && is_array($rows) && count($rows) === 0 && $wpdb->last_error === ''; $attempt++) {
                usleep(100000);
                $rows = $find_own_proposal();
            }
        }
        if (!is_array($rows) || count($rows) > 1 || $wpdb->last_error !== '') { $cleanup_ok = false; $teacher_cleanup_safe = false; }
        elseif (count($rows) === 1) {
            $owned = $rows[0];
            $owned_id = (int) $owned['id'];
            $owned_events = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$event_table} WHERE proposal_id=%d ORDER BY id", $owned_id), ARRAY_A);
            $proposal_canonical_id = (int) ($owned['canonical_subject_id'] ?? 0);
            $possible_catalog = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$catalog_table} WHERE subject_key=%s", $subject_key), ARRAY_A);
            $allowed_pending = $owned['status'] === 'pending' && count((array) $owned_events) === 1
                && $owned_events[0]['event_type'] === 'submitted' && (int) $owned_events[0]['actor_id'] === $created_teacher_id
                && json_decode((string) $owned_events[0]['event_data'], true) === array('proposed_name'=>$phrase)
                && (int) $owned['decided_by'] === 0 && $proposal_canonical_id === 0;
            $allowed_approved = $owned['status'] === 'approved' && count((array) $owned_events) === 2
                && $owned_events[0]['event_type'] === 'submitted' && (int) $owned_events[0]['actor_id'] === $created_teacher_id
                && json_decode((string) $owned_events[0]['event_data'], true) === array('proposed_name'=>$phrase)
                && $owned_events[1]['event_type'] === 'approved_new' && (int) $owned_events[1]['actor_id'] === $president_id
                && json_decode((string) $owned_events[1]['event_data'], true) === array('canonical_subject_id'=>$proposal_canonical_id)
                && (int) $owned['decided_by'] === $president_id && $proposal_canonical_id > 0;
            $safe_proposal = $owned['proposed_name'] === $phrase && $owned['proposal_key'] === $subject_key
                && (int) $owned['proposer_id'] === $created_teacher_id && ($allowed_pending || $allowed_approved);
            $safe_catalog = $allowed_pending ? is_array($possible_catalog) && count($possible_catalog) === 0
                : ($allowed_approved && is_array($possible_catalog) && count($possible_catalog) === 1
                    && (int)$possible_catalog[0]['id'] === $proposal_canonical_id
                    && $possible_catalog[0]['subject_name'] === $phrase && $possible_catalog[0]['subject_key'] === $subject_key
                    && $possible_catalog[0]['status'] === 'approved' && (int)$possible_catalog[0]['is_active'] === 1
                    && (int)$possible_catalog[0]['created_by'] === $president_id && (int)$possible_catalog[0]['approved_by'] === $president_id);
            if (!$safe_proposal || !$safe_catalog || $wpdb->last_error !== '') { $cleanup_ok = false; $teacher_cleanup_safe = false; }
            else {
                $transaction = $wpdb->query('START TRANSACTION');
                if ($transaction === false) { $cleanup_ok = false; $teacher_cleanup_safe = false; }
                else {
                    $transaction_ok = true;
                    foreach ($owned_events as $event) {
                        $deleted_event = $wpdb->delete($event_table, array('id'=>(int)$event['id'],'proposal_id'=>$owned_id), array('%d','%d'));
                        if ($deleted_event !== 1) { $transaction_ok = false; break; }
                    }
                    if ($transaction_ok) {
                        $deleted_proposal = $wpdb->delete($proposal_table, array('id'=>$owned_id,'proposal_key'=>$subject_key,'proposer_id'=>$created_teacher_id), array('%d','%s','%d'));
                        if ($deleted_proposal !== 1) { $transaction_ok = false; }
                    }
                    if ($transaction_ok && $allowed_approved) {
                        $deleted_catalog = $wpdb->delete($catalog_table, array('id'=>$proposal_canonical_id,'subject_key'=>$subject_key,'created_by'=>$president_id,'approved_by'=>$president_id), array('%d','%s','%d','%d'));
                        if ($deleted_catalog !== 1) { $transaction_ok = false; }
                    }
                    if ($transaction_ok) {
                        if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); $cleanup_ok = false; $teacher_cleanup_safe = false; }
                    } else { $wpdb->query('ROLLBACK'); $cleanup_ok = false; $teacher_cleanup_safe = false; }
                }
            }
        } else {
            $possible_catalog = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$catalog_table} WHERE subject_key=%s", $subject_key), ARRAY_A);
            if (!is_array($possible_catalog) || count($possible_catalog) !== 0 || $wpdb->last_error !== '') { $cleanup_ok = false; $teacher_cleanup_safe = false; }
        }
        if ($teacher_cleanup_safe) {
            $teacher = get_userdata($created_teacher_id);
            if ($teacher && $teacher->user_login === $teacher_login && $teacher->user_email === $teacher_email
                && array_values((array)$teacher->roles) === array('zmp_teacher')) {
                if (!wp_delete_user($created_teacher_id)) { $cleanup_ok = false; }
            } elseif ($teacher) { $cleanup_ok = false; }
        }
    }

    // Restore only the exact diagnostic option rows this candidate allows the routes to touch.
    foreach ($diagnostic_baseline as $option_name=>$before) {
        if (is_array($before)) {
            $result = $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value),autoload=VALUES(autoload)", $option_name, $before['option_value'], $before['autoload']));
        } else { $result = $wpdb->delete($wpdb->options, array('option_name'=>$option_name), array('%s')); }
        wp_cache_delete($option_name, 'options');
        $after = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $option_name), ARRAY_A);
        if ($result === false || (is_array($before) ? !is_array($after) || $after !== $before : is_array($after))) { $cleanup_ok = false; }
    }
    wp_cache_delete('alloptions', 'options');
    foreach ($baseline_tables as $table=>$expected) { if ($table_snapshot($table) !== $expected) { $cleanup_ok = false; } }
    $assert('exact_fixture_rows_options_sessions_and_wp_context_restored', $cleanup_ok
        && get_current_user_id() === $initial_user_id && $_COOKIE === $initial_cookie
        && (!$teacher_session || !$teacher_session->verify($teacher_token))
        && (!$president_session || !$president_session->verify($president_token)));
    $assert('mail_network_guards_removed_without_attempts', $mail_attempts === 0 && $blocked_http_attempts === 0);
    $assert('exactly_three_local_admin_post_requests_attempted', $request_count === 3 && !$transport_error);
}

foreach ($checks as $name=>$status) { echo $status . ' ' . $name . "\n"; }
echo 'SUMMARY ' . count(array_filter($checks, static function ($status) { return $status === 'PASS'; })) . '/' . count($checks) . " PASS\n";
exit($failures ? 1 : 0);
