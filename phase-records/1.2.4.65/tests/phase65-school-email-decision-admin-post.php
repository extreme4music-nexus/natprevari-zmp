<?php
/**
 * One controlled admin-post decision-route test for synthetic `.65` data.
 * Run with: wp eval-file phase65-school-email-decision-admin-post.php --allow-root
 * It creates one pending request through the service, exercises anonymous,
 * bad-nonce, non-President and active-President rejection routes, and removes
 * only its uniquely identified request/events, sessions and temporary mail guard.
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
    || !class_exists('ZMP_Mentor_Schools') || !class_exists('ZMP_President_Mandate')) {
    fwrite(STDERR, "BLOCKED: required plugin services are unavailable.\n");
    exit(2);
}

$requester_id = 31;
$owner_id = 30;
$president_id = 25;
$requester = get_userdata($requester_id);
$owner = get_userdata($owner_id);
$president = get_userdata($president_id);
if (!$requester || !$owner || !$president
    || !ZMP_Mentor_School_Profile::is_teacher($requester_id)
    || !ZMP_Mentor_School_Profile::is_teacher($owner_id)
    || array_values((array) $president->roles) !== array('zmp_president')
    || !user_can($president_id, 'view_zmp_president_dashboard')
    || !ZMP_President_Mandate::user_has_active_mandate($president_id)) {
    fwrite(STDERR, "BLOCKED: expected synthetic Teacher and active-President fixtures are unavailable.\n");
    exit(2);
}

$requester_mentor = ZMP_Mentor_School_Profile::mentor_for_user($requester_id);
$owner_mentor = ZMP_Mentor_School_Profile::mentor_for_user($owner_id);
if (!$requester_mentor || !$owner_mentor) {
    fwrite(STDERR, "BLOCKED: synthetic Teacher mentor fixtures are unavailable.\n");
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
if ($wpdb->last_error !== '' || !$shared) {
    fwrite(STDERR, "BLOCKED: the synthetic Teachers do not share a ready active school.\n");
    exit(2);
}

$request_table = $wpdb->prefix . 'zmp_school_email_requests';
$event_table = $wpdb->prefix . 'zmp_school_email_request_events';
$existing_pair = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$request_table} WHERE requester_user_id=%d AND owner_user_id=%d",
    $requester_id,
    $owner_id
));
if ($wpdb->last_error !== '' || $existing_pair !== 0) {
    fwrite(STDERR, "BLOCKED: a prior reverse-direction request exists; refusing to touch it.\n");
    exit(2);
}
$request_id_before = (int) $wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$request_table}");
if ($wpdb->last_error !== '') {
    fwrite(STDERR, "BLOCKED: request-table baseline could not be read.\n");
    exit(2);
}

$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
$mu_guard = $mu_dir . '/zmp65-no-mail-decision-test.php';
$mail_marker = $mu_dir . '/.zmp65-no-mail-decision-test-hit';
$content_real = realpath(WP_CONTENT_DIR);
$mu_dir_created = false;
$mu_dir_removed = true;
$mu_guard_source = "<?php\nadd_filter('pre_wp_mail', static function (\$short_circuit, \$atts) {\n    \$marker = __DIR__ . '/.zmp65-no-mail-decision-test-hit';\n    if (file_put_contents(\$marker, 'blocked', LOCK_EX) !== 7) {\n        if (!headers_sent()) { header('X-ZMP-Mail-Guard: marker-write-failed'); }\n        throw new RuntimeException('test mail guard marker write failed');\n    }\n    return false;\n}, PHP_INT_MAX, 2);\n";
$mu_guard_hash = hash('sha256', $mu_guard_source);
if (!$content_real || is_link($mu_dir) || (file_exists($mu_dir) && !is_dir($mu_dir))
    || (is_dir($mu_dir) && (!$mu_dir_real = realpath($mu_dir)
        || strpos(wp_normalize_path($mu_dir_real), wp_normalize_path($content_real) . '/') !== 0))
    || file_exists($mu_guard) || file_exists($mail_marker)) {
    fwrite(STDERR, "BLOCKED: temporary mail-guard path is unavailable or already occupied.\n");
    exit(2);
}

$assertions = array();
$fails = array();
$assert = static function ($name, $condition) use (&$assertions, &$fails) {
    $assertions[$name] = $condition ? 'PASS' : 'FAIL';
    if (!$condition) { $fails[] = $name; }
};
$request_id = 0;
$fixture_reason = 'Synthetic President decision route check ' . wp_generate_password(16, false, false);
$session_records = array();
$route_responses = array();
$guard_removed = false;
$marker_removed = false;
$mail_blocked = false;
$sessions_removed = false;
$cleanup_ok = false;
$transport_ambiguous = false;
$route_ok = false;
$route_code = 0;
$token_expiration = time() + 300;
$cookie_name = defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : '';

$new_session = static function ($user_id) use (&$session_records, $token_expiration, $cookie_name) {
    $manager = WP_Session_Tokens::get_instance((int) $user_id);
    $token = $manager->create($token_expiration);
    if (!is_string($token) || $token === '') {
        throw new RuntimeException('temporary authentication session creation failed');
    }
    $session_records[] = array('manager' => $manager, 'token' => $token);
    if ($cookie_name === '') {
        throw new RuntimeException('logged-in cookie name is unavailable');
    }
    $cookie = wp_generate_auth_cookie((int) $user_id, $token_expiration, 'logged_in', $token);
    if ($cookie === '') {
        throw new RuntimeException('temporary authentication cookie generation failed');
    }
    return $cookie;
};
$make_nonce = static function ($user_id, $cookie, $action) use ($cookie_name) {
    $_COOKIE[$cookie_name] = $cookie;
    wp_set_current_user((int) $user_id);
    return wp_create_nonce($action);
};
$request_state = static function ($id) use ($wpdb, $request_table) {
    return $wpdb->get_row($wpdb->prepare(
        "SELECT id,requester_user_id,owner_user_id,school_id,reason,status,approved_email_hash,approved_email_epoch,decided_by,decided_at FROM {$request_table} WHERE id=%d",
        (int) $id
    ));
};
$request_events = static function ($id) use ($wpdb, $event_table) {
    return $wpdb->get_results($wpdb->prepare(
        "SELECT id,request_id,event_type,actor_user_id,event_data FROM {$event_table} WHERE request_id=%d ORDER BY id",
        (int) $id
    ));
};
$initial_state = null;
$initial_event = null;
$pending_unchanged = static function ($id) use ($request_state, $request_events, $wpdb, $requester_id, &$initial_state, &$initial_event) {
    $state = $request_state($id);
    $events = $request_events($id);
    return $wpdb->last_error === '' && $state && $initial_state
        && (int) $state->requester_user_id === (int) $initial_state->requester_user_id
        && (int) $state->owner_user_id === (int) $initial_state->owner_user_id
        && (int) $state->school_id === (int) $initial_state->school_id
        && (string) $state->reason === (string) $initial_state->reason
        && $state->status === 'pending'
        && $state->approved_email_hash === '' && (int) $state->approved_email_epoch === 0
        && (int) $state->decided_by === 0 && $state->decided_at === null
        && count((array) $events) === 1 && $initial_event
        && (int) $events[0]->id === (int) $initial_event->id
        && (int) $events[0]->request_id === (int) $initial_event->request_id
        && $events[0]->event_type === $initial_event->event_type
        && (int) $events[0]->actor_user_id === (int) $initial_event->actor_user_id
        && (string) $events[0]->event_data === (string) $initial_event->event_data
        && $events[0]->event_type === 'submitted'
        && (int) $events[0]->actor_user_id === $requester_id;
};
$post_decision = static function ($cookie, $nonce, $decision_id, $decision) use ($cookie_name) {
    $headers = array('Referer' => admin_url('profile.php'));
    if (is_string($cookie) && $cookie !== '') {
        $headers['Cookie'] = $cookie_name . '=' . $cookie;
    }
    $response = wp_remote_post(admin_url('admin-post.php'), array(
        'timeout' => 15,
        'redirection' => 0,
        'headers' => $headers,
        'body' => array(
            'action' => ZMP_School_Email_Access::DECIDE_ACTION,
            'request_id' => (int) $decision_id,
            'decision' => $decision,
            '_wpnonce' => $nonce,
        ),
    ));
    return $response;
};

try {
    wp_set_current_user($requester_id);
    $submission = ZMP_School_Email_Access::submit_request(
        $requester_id,
        $owner_id,
        (int) $shared['requester']->id,
        $fixture_reason
    );
    if (!is_wp_error($submission) && !empty($submission['request_id']) && empty($submission['existing'])) {
        $request_id = (int) $submission['request_id'];
    }
    if (is_wp_error($submission) || empty($submission['request_id']) || !empty($submission['existing'])) {
        throw new RuntimeException('a unique synthetic pending request could not be created');
    }
    $initial = $request_state($request_id);
    $initial_events = $request_events($request_id);
    if (!$initial || (int) $initial->requester_user_id !== $requester_id
        || (int) $initial->owner_user_id !== $owner_id || $initial->status !== 'pending'
        || (int) $initial->school_id !== (int) $shared['owner']->id
        || $initial->approved_email_hash !== '' || (int) $initial->approved_email_epoch !== 0
        || (int) $initial->decided_by !== 0 || $initial->decided_at !== null
        || count((array) $initial_events) !== 1 || $initial_events[0]->event_type !== 'submitted'
        || (int) $initial_events[0]->actor_user_id !== $requester_id) {
        throw new RuntimeException('synthetic request did not start in the expected pending state');
    }
    $initial_state = $initial;
    $initial_event = $initial_events[0];

    $president_cookie = $new_session($president_id);
    $teacher_cookie = $new_session($requester_id);
    if (!is_dir($mu_dir)) {
        if (!mkdir($mu_dir, 0700)) { throw new RuntimeException('temporary mail guard directory could not be created'); }
        $mu_dir_created = true;
    }
    $mu_dir_real = realpath($mu_dir);
    if (!$mu_dir_real || !$content_real
        || strpos(wp_normalize_path($mu_dir_real), wp_normalize_path($content_real) . '/') !== 0) {
        throw new RuntimeException('temporary mail guard directory resolved outside wp-content');
    }
    if (file_put_contents($mail_marker, 'not-called', LOCK_EX) !== 10
        || file_get_contents($mail_marker) !== 'not-called') {
        throw new RuntimeException('temporary mail guard marker could not be initialized');
    }
    if (file_put_contents($mu_guard, $mu_guard_source, LOCK_EX) !== strlen($mu_guard_source)
        || !hash_equals($mu_guard_hash, (string) hash_file('sha256', $mu_guard))) {
        throw new RuntimeException('temporary mail guard could not be installed safely');
    }

    $endpoint = admin_url('admin-post.php');
    $endpoint_parts = wp_parse_url($endpoint);
    if (!is_array($endpoint_parts) || ($endpoint_parts['host'] ?? '') !== '127.0.0.1'
        || (int) ($endpoint_parts['port'] ?? 0) !== 8094
        || ($endpoint_parts['path'] ?? '') !== '/wp-admin/admin-post.php') {
        throw new RuntimeException('decision endpoint is outside the authorized loopback route');
    }
    $nonce_action = 'zmp_school_email_access_decide_' . $request_id;
    $president_nonce = $make_nonce($president_id, $president_cookie, $nonce_action);
    $teacher_nonce = $make_nonce($requester_id, $teacher_cookie, $nonce_action);

    $anonymous_response = $post_decision('', 'invalid-anonymous-nonce', $request_id, 'rejected');
    $route_responses['anonymous'] = $anonymous_response;
    if (is_wp_error($anonymous_response)) { $transport_ambiguous = true; throw new RuntimeException('anonymous route transport failed'); }
    $anonymous_code = (int) wp_remote_retrieve_response_code($anonymous_response);
    $anonymous_body = (string) wp_remote_retrieve_body($anonymous_response);
    $assert('anonymous_denied_with_generic_http_error', in_array($anonymous_code, array(400, 403), true));
    $assert('anonymous_response_has_no_email_address', !preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $anonymous_body));
    $state = $request_state($request_id); $events = $request_events($request_id);
    $assert('anonymous_denial_preserves_pending_request_fields_and_event', $pending_unchanged($request_id));

    $bad_nonce_response = $post_decision($president_cookie, 'invalid-' . wp_generate_password(20, false, false), $request_id, 'rejected');
    $route_responses['bad_nonce'] = $bad_nonce_response;
    if (is_wp_error($bad_nonce_response)) { $transport_ambiguous = true; throw new RuntimeException('bad-nonce route transport failed'); }
    $bad_nonce_code = (int) wp_remote_retrieve_response_code($bad_nonce_response);
    $bad_nonce_body = (string) wp_remote_retrieve_body($bad_nonce_response);
    $assert('bad_nonce_denied_http_403', $bad_nonce_code === 403);
    $assert('bad_nonce_uses_generic_security_response', strpos($bad_nonce_body, 'Безбедносната проверка не помина.') !== false);
    $assert('bad_nonce_response_has_no_email_address', !preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $bad_nonce_body));
    $state = $request_state($request_id); $events = $request_events($request_id);
    $assert('bad_nonce_preserves_pending_request_fields_and_event', $pending_unchanged($request_id));

    $teacher_response = $post_decision($teacher_cookie, $teacher_nonce, $request_id, 'rejected');
    $route_responses['non_president'] = $teacher_response;
    if (is_wp_error($teacher_response)) { $transport_ambiguous = true; throw new RuntimeException('non-President route transport failed'); }
    $teacher_code = (int) wp_remote_retrieve_response_code($teacher_response);
    $teacher_body = (string) wp_remote_retrieve_body($teacher_response);
    $teacher_location = (string) wp_remote_retrieve_header($teacher_response, 'location');
    $assert('non_president_gets_generic_decision_failure_redirect', $teacher_code === 302 && strpos($teacher_location, 'zmp_school_email_status=decision_failed') !== false);
    $assert('non_president_response_has_no_email_address', !preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $teacher_body . ' ' . $teacher_location));
    $state = $request_state($request_id); $events = $request_events($request_id);
    $assert('non_president_denial_preserves_pending_request_fields_and_event', $pending_unchanged($request_id));

    $president_nonce = $make_nonce($president_id, $president_cookie, $nonce_action);
    $president_response = $post_decision($president_cookie, $president_nonce, $request_id, 'rejected');
    $route_responses['active_president'] = $president_response;
    if (is_wp_error($president_response)) { $transport_ambiguous = true; throw new RuntimeException('active-President route transport failed'); }
    $route_code = (int) wp_remote_retrieve_response_code($president_response);
    $president_body = (string) wp_remote_retrieve_body($president_response);
    $president_location = (string) wp_remote_retrieve_header($president_response, 'location');
    $assert('active_president_rejection_redirects_with_generic_status', $route_code === 302 && strpos($president_location, 'zmp_school_email_status=request_rejected') !== false);
    $assert('active_president_response_has_no_email_address', !preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $president_body . ' ' . $president_location));
    $state = $request_state($request_id); $events = $request_events($request_id);
    $rejected_events = array_values(array_filter((array) $events, static function ($event) {
        return $event->event_type === 'rejected' && (int) $event->actor_user_id === 25;
    }));
    $event_data = count($rejected_events) === 1 ? json_decode((string) $rejected_events[0]->event_data, true) : null;
    $assert('active_president_sets_rejected_state_and_actor', $state && $state->status === 'rejected' && (int) $state->decided_by === $president_id && !empty($state->decided_at));
    $assert('rejection_keeps_approved_email_hash_empty_and_epoch_zero', $state && $state->approved_email_hash === '' && (int) $state->approved_email_epoch === 0);
    $assert('rejection_audit_event_has_exact_actor_and_relationship', count($rejected_events) === 1 && is_array($event_data)
        && (int) $event_data['requester_user_id'] === $requester_id
        && (int) $event_data['owner_user_id'] === $owner_id
        && (int) $event_data['school_id'] === (int) $shared['owner']->id);
    $assert('request_has_only_submission_and_rejection_events', count((array) $events) === 2
        && $events[0]->event_type === 'submitted' && $events[1]->event_type === 'rejected');

    $marker_state = is_file($mail_marker) ? file_get_contents($mail_marker) : '';
    $mail_blocked = $marker_state === 'blocked';
    $assert('no_email_call_during_all_decision_routes', $marker_state === 'not-called');
    $route_ok = count($fails) === 0 && !$transport_ambiguous;
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
        $mail_blocked = $mail_blocked || $marker_state === 'blocked';
        if (in_array($marker_state, array('not-called', 'blocked'), true)) {
            $marker_removed = unlink($mail_marker) && !file_exists($mail_marker);
        }
    }
    if ($mu_dir_created) { $mu_dir_removed = rmdir($mu_dir) && !file_exists($mu_dir); }

    if ($request_id < 1) {
        $candidates = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$request_table} WHERE id>%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s ORDER BY id",
            $request_id_before,
            $requester_id,
            $owner_id,
            (int) $shared['owner']->id,
            $fixture_reason
        ));
        if ($wpdb->last_error === '' && is_array($candidates) && count($candidates) === 1) {
            $request_id = (int) $candidates[0]->id;
        } elseif ($wpdb->last_error === '' && is_array($candidates) && count($candidates) === 0) {
            $cleanup_ok = true;
        }
    }
    if ($request_id > 0) {
        $identity_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$request_table} WHERE id=%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s",
            $request_id,
            $requester_id,
            $owner_id,
            (int) $shared['owner']->id,
            $fixture_reason
        ));
        if ($wpdb->last_error === '' && $identity_count === 1) {
            $wpdb->delete($event_table, array('request_id' => $request_id), array('%d'));
            $wpdb->delete($request_table, array(
                'id' => $request_id,
                'requester_user_id' => $requester_id,
                'owner_user_id' => $owner_id,
                'school_id' => (int) $shared['owner']->id,
                'reason' => $fixture_reason,
            ), array('%d', '%d', '%d', '%d', '%s'));
            $remaining_request = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$request_table} WHERE id=%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND reason=%s",
                $request_id,
                $requester_id,
                $owner_id,
                (int) $shared['owner']->id,
                $fixture_reason
            ));
            $remaining_events = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d",
                $request_id
            ));
            $cleanup_ok = $wpdb->last_error === '' && $remaining_request === 0 && $remaining_events === 0;
        }
    }
    $sessions_removed = true;
    foreach ($session_records as $session_record) {
        $session_record['manager']->destroy($session_record['token']);
        if ($session_record['manager']->verify($session_record['token'])) { $sessions_removed = false; }
    }
}

if ($route_ok && $cleanup_ok && $guard_removed && $marker_removed && $sessions_removed && $mu_dir_removed && !$mail_blocked) {
    echo 'PASS: ' . count($assertions) . '/' . count($assertions) . ' assertions; anonymous, bad-nonce and non-President denials; active-President rejection; exact cleanup verified.' . "\n";
    exit(0);
}
foreach ($assertions as $name => $status) {
    if ($status !== 'PASS') { fwrite(STDERR, $name . "=FAIL\n"); }
}
if ($mail_blocked) { fwrite(STDERR, "FAIL: mail call intercepted and blocked by the test guard.\n"); }
if (!$cleanup_ok || !$guard_removed || !$marker_removed || !$sessions_removed || !$mu_dir_removed) {
    fwrite(STDERR, "CLEANUP: exact request/event/session/mail-guard cleanup could not be fully verified.\n");
}
exit(1);
