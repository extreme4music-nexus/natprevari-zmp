<?php
/** Narrow WP runtime acceptance for `.63` diagnostics carried into `.64`. */
if (!defined('ABSPATH') || !class_exists('WP_CLI')) { exit(1); }

global $wpdb;
$checks = array();
$failures = array();
$check = static function($condition, $label) use (&$checks, &$failures) {
    $checks[] = array('label'=>$label, 'pass'=>(bool) $condition);
    if (!$condition) { $failures[] = $label; }
};
$require = static function($condition, $label) use (&$checks, &$failures) {
    $checks[] = array('label'=>$label, 'pass'=>(bool) $condition);
    if (!$condition) { $failures[] = $label; throw new RuntimeException($label); }
};
$matches_session = static function($events, $session_id) {
    return array_values(array_filter((array) $events, static function($event) use ($session_id) {
        return is_array($event) && isset($event['session_id']) && $event['session_id'] === $session_id;
    }));
};

$debug_option = ZMP_Debug::OPTION;
$capture_option = ZMP_Debug::CAPTURE_OPTION;
$old_events = get_option($debug_option, null);
$old_capture = get_option($capture_option, null);
$old_user_id = get_current_user_id();
$old_post = $_POST;
$old_request = $_REQUEST;
$old_get = $_GET;
$old_method_exists = array_key_exists('REQUEST_METHOD', $_SERVER);
$old_uri_exists = array_key_exists('REQUEST_URI', $_SERVER);
$old_method = $_SERVER['REQUEST_METHOD'] ?? null;
$old_uri = $_SERVER['REQUEST_URI'] ?? null;
$created_users = array();
$proposal_ids = array();
$mandate_id = 0;
$token = strtolower(bin2hex(random_bytes(5)));
$secret = 'ZMP64_PRIVATE_MARKER_' . strtoupper($token);
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$proposal_events_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$mandate_table = $wpdb->prefix . 'zmp_president_mandates';
$current_session = '';

try {
    $require(empty($wpdb->last_error), 'preflight_db_error_empty');
    $require(!is_array($old_capture) || empty($old_capture['session_id']) || (int) ($old_capture['expires_at'] ?? 0) <= time(), 'no_unexpired_capture_overwritten');
    $require(class_exists('ZMP_Mentor_Subject_Proposals') && ZMP_Mentor_Subject_Proposals::schema_is_compatible(), 'proposal_schema_ready');
    $require(isset(ZMP_Debug::test_types()['teacher_subject_proposals'], ZMP_Debug::test_types()['teacher_register']), 'carried_categories_available');

    $roles = wp_roles();
    foreach (array('zmp_teacher','zmp_president','subscriber') as $role) {
        $require(isset($roles->roles[$role]), 'required_role_' . $role);
    }

    $create_user = static function($role, $suffix) use (&$created_users, $token) {
        $login = 'zmp64_diag_' . $suffix . '_' . $token;
        $id = wp_insert_user(array(
            'user_login'=>$login,
            'user_pass'=>wp_generate_password(32, true, true),
            'user_email'=>$login . '@example.invalid',
            'display_name'=>'Synthetic diagnostic ' . $suffix,
            'role'=>$role,
        ));
        if (is_wp_error($id)) { throw new RuntimeException('synthetic_user_creation_failed'); }
        $created_users[] = (int) $id;
        return (int) $id;
    };
    $teacher_id = $create_user('zmp_teacher', 'teacher');
    $president_id = $create_user('zmp_president', 'president');
    $subscriber_id = $create_user('subscriber', 'subscriber');

    $today = current_time('Y-m-d');
    $end_date = gmdate('Y-m-d', strtotime($today . ' +365 days'));
    $mandate_inserted = $wpdb->insert($mandate_table, array(
        'user_id'=>$president_id,
        'start_date'=>$today,
        'end_date'=>$end_date,
        'board_decision'=>'Synthetic diagnostics acceptance fixture',
        'status'=>'active',
        'appointed_by'=>0,
        'appointed_at'=>current_time('mysql'),
        'closed_by'=>0,
        'closed_at'=>null,
        'close_reason'=>'',
        'predecessor_mandate_id'=>0,
        'predecessor_user_id'=>0,
        'handover_deadline'=>null,
        'handover_extension_months'=>0,
    ));
    $require($mandate_inserted === 1, 'synthetic_active_mandate_created');
    $mandate_id = (int) $wpdb->insert_id;
    $require(ZMP_President_Mandate::user_has_active_mandate($president_id), 'synthetic_mandate_is_active');

    // Actual teacher proposal submit and active-President rejection under capture.
    $subject_capture = ZMP_Debug::start_capture('teacher_subject_proposals', 15, $teacher_id);
    $require(!is_wp_error($subject_capture), 'proposal_capture_started');
    $current_session = $subject_capture['session_id'];
    wp_set_current_user($teacher_id);
    $proposal_result = ZMP_Mentor_Subject_Proposals::submit($teacher_id, 'Synthetic Carry Forward ' . $token);
    $require(is_int($proposal_result) && $proposal_result > 0, 'teacher_proposal_submitted');
    $proposal_id = $proposal_result;
    $proposal_ids[] = $proposal_id;
    wp_set_current_user($president_id);
    $decision_result = ZMP_Mentor_Subject_Proposals::decide($president_id, $proposal_id, 'reject');
    $require(!is_wp_error($decision_result), 'active_president_rejects_proposal');
    $proposal_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$proposal_table} WHERE id=%d", $proposal_id));
    $require($proposal_status === 'rejected', 'proposal_rejection_persisted');
    $subject_events = $matches_session(get_option($debug_option, array()), $current_session);
    $subject_messages = array_column($subject_events, 'message');
    $require(in_array('Subject proposal submitted.', $subject_messages, true), 'proposal_submit_debug_event');
    $require(in_array('Subject proposal decision committed.', $subject_messages, true), 'president_decision_debug_event');
    $require(count($subject_events) === 2, 'proposal_capture_contains_exact_flow_events');
    $require(count(array_filter($subject_events, static function($event) {
        $context = $event['context'] ?? array();
        return ($event['test_type'] ?? '') === 'teacher_subject_proposals'
            && is_int($context['proposal_id'] ?? null)
            && is_int($context['actor_user_id'] ?? null)
            && (!isset($context['decision']) || in_array($context['decision'], array('approve_new','approve_alias','reject','invalid'), true));
    })) === 2 && count(array_filter($subject_events, static function($event) {
        return ($event['context']['decision'] ?? '') === 'reject';
    })) === 1, 'proposal_events_safe_context');
    ZMP_Debug::stop_capture();

    // Search/export and denied role paths. A unique marker is supplied only as an input filter.
    $register_capture = ZMP_Debug::start_capture('teacher_register', 15, $teacher_id);
    $require(!is_wp_error($register_capture), 'register_capture_started');
    $current_session = $register_capture['session_id'];
    wp_set_current_user($teacher_id);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/teacher-register?name=' . rawurlencode($secret);
    $search_result = ZMP_Teacher_Register::search(array('name'=>$secret), 1, 20);
    $require(is_array($search_result) && ($search_result['total'] ?? -1) === 0, 'teacher_register_search_empty_result');
    ob_start();
    $export_result = ZMP_Teacher_Register::export_csv(array('name'=>$secret));
    $csv_output = (string) ob_get_clean();
    $require($export_result === true, 'teacher_register_empty_csv_export');
    $require(strpos($csv_output, 'name,subject,grades,school,municipality,region') !== false, 'teacher_register_csv_header');
    wp_set_current_user($subscriber_id);
    $denied_search = ZMP_Teacher_Register::search(array('name'=>$secret), 1, 20);
    $require(is_wp_error($denied_search) && $denied_search->get_error_code() === 'forbidden', 'subscriber_register_search_denied');
    $denied_export = ZMP_Teacher_Register::export_csv(array('name'=>$secret));
    $require(is_wp_error($denied_export) && $denied_export->get_error_code() === 'forbidden', 'subscriber_register_export_denied');
    $register_events = $matches_session(get_option($debug_option, array()), $current_session);
    $register_messages = array_column($register_events, 'message');
    $require(in_array('Teacher register search completed.', $register_messages, true), 'register_search_success_event');
    $require(in_array('Teacher register CSV export completed.', $register_messages, true), 'register_export_success_event');
    $require(in_array('Teacher register search denied.', $register_messages, true), 'register_search_denial_event');
    $require(in_array('Teacher register CSV export denied.', $register_messages, true), 'register_export_denial_event');
    $require(count(array_filter($register_events, static function($event) { return ($event['test_type'] ?? '') !== 'teacher_register'; })) === 0, 'register_capture_category_consistent');
    ZMP_Debug::stop_capture();

    // Exercise both search query failure branches and CSV batch query failure using a one-shot SQL filter.
    $inject_query_failure = static function($needle) {
        $hits = 0;
        $filter = static function($query) use ($needle, &$hits) {
            if ($hits === 0 && strpos((string) $query, $needle) !== false) {
                $hits++;
                return 'SELECT zmp_diagnostic_missing_column FROM zmp_diagnostic_missing_table';
            }
            return $query;
        };
        add_filter('query', $filter, 10, 1);
        return array($filter, static function() use (&$hits) { return $hits; });
    };
    $run_db_error_case = static function($kind, $needle, $expected_message) use ($inject_query_failure, $teacher_id, $secret, $check, $matches_session, $debug_option) {
        $capture = ZMP_Debug::start_capture('teacher_register', 15, $teacher_id);
        if (is_wp_error($capture)) { throw new RuntimeException('db_error_capture_start_failed'); }
        wp_set_current_user($teacher_id);
        list($filter, $get_hits) = $inject_query_failure($needle);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/teacher-register?name=' . rawurlencode($secret);
        if ($kind === 'csv') {
            ob_start();
            $result = ZMP_Teacher_Register::export_csv(array('name'=>$secret));
            ob_end_clean();
        } else {
            $result = ZMP_Teacher_Register::search(array('name'=>$secret), 1, 20);
        }
        remove_filter('query', $filter, 10);
        $events = $matches_session(get_option($debug_option, array()), $capture['session_id']);
        ZMP_Debug::stop_capture();
        $expected_error_code = $kind === 'csv' ? 'export_failed' : 'database_error';
        $check(is_wp_error($result) && $result->get_error_code() === $expected_error_code, $kind . '_db_error_returns_error');
        $check($get_hits() === 1, $kind . '_db_error_injected_once');
        $check(in_array($expected_message, array_column($events, 'message'), true), $kind . '_db_error_safe_event');
        $check(!in_array('Teacher register search completed.', array_column($events, 'message'), true)
            && !in_array('Teacher register CSV export completed.', array_column($events, 'message'), true), $kind . '_db_error_not_logged_as_success');
        $safe_codes = array_column(array_map(static function($event) { return $event['context'] ?? array(); }, $events), 'error_code');
        $check(in_array('database_query_failed', $safe_codes, true), $kind . '_db_error_code_retained');
    };
    $run_db_error_case('count', 'SELECT COUNT(*) FROM (', 'Teacher register search query failed.');
    $run_db_error_case('rows', 'SELECT m.full_name AS name', 'Teacher register search query failed.');
    $run_db_error_case('csv', 'SELECT m.full_name AS name', 'Teacher register CSV batch query failed.');

    // Validate the three fixed safe President codes and reject an arbitrary injected code.
    $error_capture = ZMP_Debug::start_capture('teacher_subject_proposals', 15, $president_id);
    $require(!is_wp_error($error_capture), 'error_code_capture_started');
    $current_session = $error_capture['session_id'];
    wp_set_current_user($president_id);
    foreach (array('canonical_subject_invalid','alias_equals_canonical','alias_conflict') as $safe_code) {
        ZMP_Debug::log('warning', 'President subject decision processed.', array(
            'decision'=>'approve_alias', 'outcome'=>'failed', 'error_code'=>$safe_code,
        ), 'teacher_subject_proposals');
    }
    ZMP_Debug::log('warning', 'President subject decision processed.', array(
        'decision'=>'approve_alias', 'outcome'=>'failed', 'error_code'=>'untrusted_' . $secret,
    ), 'teacher_subject_proposals');
    $error_events = $matches_session(get_option($debug_option, array()), $current_session);
    foreach (array('canonical_subject_invalid','alias_equals_canonical','alias_conflict') as $safe_code) {
        $require(count(array_filter($error_events, static function($event) use ($safe_code) {
            return ($event['context']['error_code'] ?? '') === $safe_code;
        })) === 1, 'decision_error_code_retained_' . $safe_code);
    }
    $require(count(array_filter($error_events, static function($event) {
        return strpos((string) ($event['context']['error_code'] ?? ''), 'untrusted_') === 0;
    })) === 0, 'arbitrary_error_code_dropped');
    ZMP_Debug::stop_capture();

    // Verify action inference, path-only request recording, raw event and JSON/CSV normalization.
    $before_inference = count((array) get_option($debug_option, array()));
    $_POST = array(); $_REQUEST = array('action'=>'zmp_president_subject_decision');
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_SERVER['REQUEST_URI'] = '/wp-admin/admin-post.php?marker=' . rawurlencode($secret);
    ZMP_Debug::log('info', 'Subject proposal decision committed.', array('decision'=>'reject','proposal_id'=>777), '');
    $_POST = array(); $_REQUEST = array('zmp_register_name'=>$secret);
    $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['REQUEST_URI'] = '/teacher-register?name=' . rawurlencode($secret);
    ZMP_Debug::log('info', 'Teacher register search completed.', array('page'=>1,'page_size'=>20,'result_count'=>0,'total'=>0), '');
    $_POST = array(); $_REQUEST = array('action'=>'zmp_export_teacher_register');
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_SERVER['REQUEST_URI'] = '/wp-admin/admin-post.php?marker=' . rawurlencode($secret);
    ZMP_Debug::log('info', 'Teacher register CSV export completed.', array('batch_count'=>0,'row_count'=>0), '');
    $all_events = (array) get_option($debug_option, array());
    $inferred_events = array_slice($all_events, $before_inference);
    $require(($inferred_events[0]['test_type'] ?? '') === 'teacher_subject_proposals', 'proposal_action_category_inference');
    $require(($inferred_events[1]['test_type'] ?? '') === 'teacher_register', 'register_filter_category_inference');
    $require(($inferred_events[2]['test_type'] ?? '') === 'teacher_register', 'register_export_category_inference');
    $require(($inferred_events[1]['request'] ?? '') === '/teacher-register', 'query_string_removed_from_request_path');

    // Raw arbitrary content and field names must not enter events or normalized exports.
    $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['REQUEST_URI'] = '/debug?secret=' . rawurlencode($secret);
    ZMP_Debug::log('info', 'Untrusted message ' . $secret, array(
        'reason'=>$secret,'name'=>$secret,'email'=>$secret,'search'=>$secret,
        'request_reason'=>$secret,'error_code'=>'untrusted_' . $secret,
    ), 'teacher_register');
    $events_after_secret = (array) get_option($debug_option, array());
    $serialized_raw = wp_json_encode($events_after_secret);
    $json_export = ZMP_Debug::export_payload($events_after_secret, 'json');
    $csv_export = ZMP_Debug::export_payload($events_after_secret, 'csv');
    $require(strpos((string) $serialized_raw, $secret) === false, 'sensitive_marker_absent_raw_events');
    $require(strpos((string) $json_export, $secret) === false, 'sensitive_marker_absent_json_export');
    $require(strpos((string) $csv_export, $secret) === false, 'sensitive_marker_absent_csv_export');
    $require(strpos((string) $serialized_raw, 'Untrusted message') === false, 'arbitrary_message_replaced');
    $require(strpos((string) $csv_export, 'Untrusted message') === false, 'arbitrary_message_absent_csv');
    $types = ZMP_Debug::test_types();
    $require(isset($types['school_profile']), 'existing_school_profile_category_retained');
    $require(isset($types['school_email_access']), 'existing_school_email_category_retained');
    $last_event = end($events_after_secret);
    $normalized = ZMP_Debug::normalize_event($last_event);
    $require($normalized['message'] === 'Diagnostic event recorded.', 'arbitrary_message_normalized_to_constant');
    $unsafe_keys = array_intersect(array('reason','name','email','search','request_reason'), array_keys($normalized['context']));
    $require($unsafe_keys === array(), 'unsafe_context_fields_removed');
    $require(($normalized['context']['flow_id'] ?? '') !== '', 'safe_flow_id_retained');
} catch (Throwable $error) {
    if (empty($failures)) { $failures[] = 'runtime_exception'; }
} finally {
    // Restore request globals and options exactly; clean only fixture-owned rows and users.
    ZMP_Debug::stop_capture();
    foreach ($proposal_ids as $proposal_id) {
        $wpdb->delete($proposal_events_table, array('proposal_id'=>(int) $proposal_id), array('%d'));
        $wpdb->delete($proposal_table, array('id'=>(int) $proposal_id), array('%d'));
    }
    if ($mandate_id > 0) { $wpdb->delete($mandate_table, array('id'=>$mandate_id), array('%d')); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach (array_reverse($created_users) as $user_id) { wp_delete_user((int) $user_id); }
    wp_set_current_user($old_user_id);
    $_POST = $old_post; $_REQUEST = $old_request; $_GET = $old_get;
    if ($old_method_exists) { $_SERVER['REQUEST_METHOD'] = $old_method; } else { unset($_SERVER['REQUEST_METHOD']); }
    if ($old_uri_exists) { $_SERVER['REQUEST_URI'] = $old_uri; } else { unset($_SERVER['REQUEST_URI']); }
    if ($old_events === null) { delete_option($debug_option); } else { update_option($debug_option, $old_events, false); }
    if ($old_capture === null) { delete_option($capture_option); } else { update_option($capture_option, $old_capture, false); }
    $restored_events = get_option($debug_option, null);
    $restored_capture = get_option($capture_option, null);
    $check($restored_events === $old_events, 'debug_event_option_restored_exactly');
    $check($restored_capture === $old_capture, 'capture_option_restored_exactly');
    $check(count(array_filter($created_users, static function($user_id) { return get_userdata((int) $user_id) !== false; })) === 0, 'temporary_users_removed');
    $check($mandate_id < 1 || (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mandate_table} WHERE id=%d", $mandate_id)) === 0, 'temporary_mandate_removed');
    $remaining_proposals = 0; $remaining_proposal_events = 0;
    foreach ($proposal_ids as $proposal_id) {
        $remaining_proposals += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_table} WHERE id=%d", $proposal_id));
        $remaining_proposal_events += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d", $proposal_id));
    }
    $check($remaining_proposals === 0, 'temporary_proposals_removed');
    $check($remaining_proposal_events === 0, 'temporary_proposal_events_removed');
    $check(empty($wpdb->last_error), 'post_cleanup_db_error_empty');
}

$passed = count(array_filter($checks, static function($row) { return $row['pass']; }));
$failed = count($checks) - $passed;
WP_CLI::log('TARGETED_DIAGNOSTICS_RESULT ' . wp_json_encode(array(
    'passed'=>$passed,'failed'=>$failed,'total'=>count($checks),'failures'=>$failures,
    'proposal_fixture_count'=>count($proposal_ids),'temporary_user_count'=>count($created_users),
    'temporary_mandate_created'=>($mandate_id > 0),
)));
foreach ($checks as $row) { WP_CLI::log(($row['pass'] ? 'PASS ' : 'FAIL ') . $row['label']); }
if ($failed > 0) { WP_CLI::halt(1); }
