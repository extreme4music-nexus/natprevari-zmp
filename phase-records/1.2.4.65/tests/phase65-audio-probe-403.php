<?php
/**
 * Synthetic regression for audio URL probes that receive an HTTP 403.
 * Run with WP-CLI eval-file against the disposable phase `.65` site only.
 * The pre_http_request filter guarantees no outbound network request occurs.
 * The approval action handler is not invoked here: its redirect helper exits,
 * bypassing this same-process try/finally restoration; verify the real route in UI.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Approved_Audio_Tools')) {
    fwrite(STDERR, "wordpress_plugin_required\n");
    exit(2);
}

global $wpdb;
$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) {
    $assertions[$name] = $condition ? 'PASS' : 'FAIL';
};
$option_name = ZMP_Approved_Audio_Tools::OPTION;
$cursor_name = 'zmp_approved_audio_tools_audit_cursor';
$option_existed = (bool) $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $option_name));
$cursor_existed = (bool) $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $cursor_name));
$original_option = get_option($option_name, null);
$original_cursor = get_option($cursor_name, null);
$network_hits = 0;
$probe_url = 'https://1.1.1.1/zmp-synthetic-403-check';
$tool_id = 'zmp65-probe-403';
$synthetic_admin = get_user_by('login', 'zmp65_synthetic_admin');
$synthetic_admin_id = $synthetic_admin instanceof WP_User && substr(strrchr((string) $synthetic_admin->user_email, '@') ?: '', 1) === 'example.invalid'
    ? absint($synthetic_admin->ID)
    : 0;
$assert($synthetic_admin_id > 0, 'synthetic_administrator_identity_available');

$synthetic_403 = static function ($preempt, $args, $url) use (&$network_hits, $probe_url) {
    if ($url !== $probe_url) { return $preempt; }
    $network_hits++;
    return array(
        'headers' => array(),
        'body' => 'Synthetic forbidden response',
        'response' => array('code' => 403, 'message' => 'Forbidden'),
        'cookies' => array(),
        'filename' => null,
    );
};
add_filter('pre_http_request', $synthetic_403, 0, 3);

try {
    $check = new ReflectionMethod('ZMP_Approved_Audio_Tools', 'check_tool_url');
    $result = $check->invoke(null, $probe_url, array());
    $assert(is_array($result) && (int) ($result['http_status'] ?? 0) === 403, 'synthetic_probe_returns_http_403');
    $assert(is_array($result) && ($result['state'] ?? '') === 'probe_denied', 'probe_403_is_distinct_from_confirmed_unavailable');
    $assert(is_array($result) && ($result['state'] ?? '') !== 'available', 'probe_403_is_not_automatic_success');
    $assert($network_hits === 1, 'controlled_response_intercepted_without_network');

    $now = current_time('mysql', true);
    $base = array(
        'name' => 'Synthetic audio tool', 'category' => 'vocal_remover', 'url' => $probe_url,
        'privacy_url' => 'https://1.1.1.1/privacy', 'terms_url' => 'https://1.1.1.1/terms',
        'free_limits' => 'Synthetic free limit', 'duration_format_limits' => 'Synthetic format limit',
        'quality_notes' => 'Synthetic quality note', 'rights_note' => 'Synthetic rights note',
        'processing_claim' => 'Synthetic processing statement', 'audit_state' => 'probe_denied',
        'http_status' => 403, 'auto_checked_at' => $now,
    );
    $required = array('privacy','terms','free','format','quality','processing','rights','youtube_excluded','url_opened');
    $confirmed_approved = $base + array(
        'manual_status' => 'approved', 'manual_checked_at' => $now,
        'manual_review_note' => 'Synthetic browser check confirmed this URL opens.',
        'manual_reviewed_checks' => $required,
        'manual_review_url_hash' => hash('sha256', $probe_url),
        'manual_reviewed_by' => $synthetic_admin_id,
    );

    // Exercise the actual cron/batch path: approved tool -> intercepted 403 -> persisted record.
    $automated_fixture = $confirmed_approved;
    $automated_fixture['audit_state'] = 'available';
    update_option($option_name, array($tool_id => $automated_fixture), false);
    update_option($cursor_name, 0, false);
    ZMP_Approved_Audio_Tools::automated_audit_batch();
    $audited_option = get_option($option_name, array());
    $audited_tool = is_array($audited_option) ? ($audited_option[$tool_id] ?? array()) : array();
    $assert(($audited_tool['audit_state'] ?? '') === 'probe_denied', 'automated_batch_persists_probe_denied_not_available');
    $assert(($audited_tool['manual_status'] ?? '') === 'approved', 'automated_403_preserves_approval_with_recent_exact_url_human_confirmation');
    $assert(($audited_tool['manual_review_url_hash'] ?? '') === hash('sha256', $probe_url), 'automated_batch_preserves_url_bound_human_confirmation');
    $audited_visible = ZMP_Approved_Audio_Tools::teacher_visible_tools();
    $assert(count($audited_visible) === 1 && !array_key_exists('audit_state', $audited_visible[0]), 'batch_result_teacher_visibility_does_not_expose_automatic_success');
    $assert(count($audited_visible) === 1 && !array_key_exists('manual_reviewed_by', $audited_visible[0]) && !array_key_exists('manual_review_note', $audited_visible[0]), 'teacher_payload_keeps_reviewer_identity_and_private_note');
    $assert($network_hits === 2, 'automated_batch_uses_exactly_one_additional_intercepted_request');

    $invalid_confirmation_cases = array(
        'wrong_url_hash' => static function (&$tool) { $tool['manual_review_url_hash'] = hash('sha256', 'https://1.1.1.1/other-synthetic-url'); },
        'stale_human_review' => static function (&$tool) { $tool['manual_checked_at'] = gmdate('Y-m-d H:i:s', time() - 15 * DAY_IN_SECONDS); },
        'missing_url_opened_check' => static function (&$tool) { $tool['manual_reviewed_checks'] = array_values(array_diff($tool['manual_reviewed_checks'], array('url_opened'))); },
        'missing_reviewer' => static function (&$tool) { unset($tool['manual_reviewed_by']); },
        'zero_reviewer' => static function (&$tool) { $tool['manual_reviewed_by'] = 0; },
    );
    foreach ($invalid_confirmation_cases as $case => $mutate) {
        $invalid_fixture = $confirmed_approved;
        $invalid_fixture['audit_state'] = 'available';
        $mutate($invalid_fixture);
        update_option($option_name, array($tool_id => $invalid_fixture), false);
        update_option($cursor_name, 0, false);
        ZMP_Approved_Audio_Tools::automated_audit_batch();
        $after_invalid = get_option($option_name, array());
        $invalid_tool = is_array($after_invalid) ? ($after_invalid[$tool_id] ?? array()) : array();
        $assert(($invalid_tool['audit_state'] ?? '') === 'probe_denied', $case . '_remains_probe_denied');
        $assert(($invalid_tool['manual_status'] ?? '') === 'pending', $case . '_cannot_retain_approval');
        $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, $case . '_cannot_remain_teacher_visible');
    }

    update_option($option_name, array($tool_id => $base + array('manual_status' => 'pending')), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'unreviewed_probe_denied_tool_is_not_teacher_visible');

    $manually_reviewed = $confirmed_approved;
    update_option($option_name, array($tool_id => $manually_reviewed), false);
    $visible = ZMP_Approved_Audio_Tools::teacher_visible_tools();
    $assert(count($visible) === 1, 'recent_recorded_manual_review_allows_probe_denied_tool');
    $assert(count($visible) === 1 && !array_key_exists('audit_state', $visible[0]), 'public_record_does_not_claim_automatic_success');
    $stored_after_manual_review = get_option($option_name, array());
    $assert(($stored_after_manual_review[$tool_id]['audit_state'] ?? '') === 'probe_denied', 'manual_review_keeps_403_state_distinct_from_automatic_success');

    $without_manual_evidence = $manually_reviewed;
    unset($without_manual_evidence['manual_checked_at'], $without_manual_evidence['manual_review_note'], $without_manual_evidence['manual_reviewed_checks']);
    update_option($option_name, array($tool_id => $without_manual_evidence), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'approval_without_recorded_manual_review_is_not_visible');

    $without_url_binding = $manually_reviewed;
    unset($without_url_binding['manual_review_url_hash']);
    update_option($option_name, array($tool_id => $without_url_binding), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'manual_review_without_url_hash_is_not_visible');

    $wrong_url_binding = $manually_reviewed;
    $wrong_url_binding['manual_review_url_hash'] = hash('sha256', 'https://1.1.1.1/other-synthetic-url');
    update_option($option_name, array($tool_id => $wrong_url_binding), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'manual_review_for_different_url_is_not_visible');

    $missing_reviewer = $manually_reviewed;
    unset($missing_reviewer['manual_reviewed_by']);
    update_option($option_name, array($tool_id => $missing_reviewer), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'manual_review_without_reviewer_identity_is_not_visible');

    $zero_reviewer = $manually_reviewed;
    $zero_reviewer['manual_reviewed_by'] = 0;
    update_option($option_name, array($tool_id => $zero_reviewer), false);
    $assert(count(ZMP_Approved_Audio_Tools::teacher_visible_tools()) === 0, 'manual_review_with_zero_reviewer_id_is_not_visible');
} finally {
    remove_filter('pre_http_request', $synthetic_403, 0);
    if ($option_existed) { update_option($option_name, $original_option, false); } else { delete_option($option_name); }
    if ($cursor_existed) { update_option($cursor_name, $original_cursor, false); } else { delete_option($cursor_name); }
}

$option_exists_after = (bool) $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $option_name));
$cursor_exists_after = (bool) $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $cursor_name));
$assert($option_exists_after === $option_existed && maybe_serialize(get_option($option_name, null)) === maybe_serialize($original_option), 'audio_fixture_option_restored_exactly');
$assert($cursor_exists_after === $cursor_existed && maybe_serialize(get_option($cursor_name, null)) === maybe_serialize($original_cursor), 'audit_cursor_restored_exactly');

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures, 'controlled_response_interceptions' => $network_hits), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
