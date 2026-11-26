<?php
/**
 * Exercises the `.66.b` circular-invitation send handler with one synthetic
 * approved campaign and one reserved `.invalid` recipient. `pre_wp_mail`
 * short-circuits before all real transports. A shutdown cleanup removes only
 * this script's exact recipient and campaign rows, including after handler exit.
 */
if (!defined('WP_CLI') || !class_exists('ZMP_Circular_Invitations') || !class_exists('ZMP_Category_Registrations')) {
    WP_CLI::error('Required WP-CLI/plugin classes unavailable.');
}
global $wpdb;
$user_id = 1;
wp_set_current_user($user_id);
if (!user_can($user_id, 'manage_options')) {
    WP_CLI::error('Synthetic administrator fixture unavailable.');
}
$season = ZMP_Category_Registrations::active_season();
if (!$season || empty($season->id) || !is_email('zmp-capture-probe@example.invalid')) {
    WP_CLI::error('Active synthetic season or reserved recipient is unavailable.');
}
$campaign_id = 0;
$recipient_id = 0;
$mail_captured = false;
$mail_calls = 0;
$cleanup_ok = false;
$expected_email = 'zmp-capture-probe@example.invalid';
$cleanup = static function () use (&$campaign_id, &$recipient_id, &$cleanup_ok, $wpdb) {
    $cleanup_ok = true;
    if ($recipient_id > 0) {
        $id = $recipient_id;
        $deleted = $wpdb->delete($wpdb->prefix . 'zmp_circular_recipients', array('id' => $id), array('%d'));
        $left = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_circular_recipients WHERE id=%d", $id));
        $cleanup_ok = $cleanup_ok && $deleted === 1 && (int) $left === 0;
        $recipient_id = 0;
    }
    if ($campaign_id > 0) {
        $id = $campaign_id;
        $deleted = $wpdb->delete($wpdb->prefix . 'zmp_circular_invitations', array('id' => $id), array('%d'));
        $left = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_circular_invitations WHERE id=%d", $id));
        $cleanup_ok = $cleanup_ok && $deleted === 1 && (int) $left === 0;
        $campaign_id = 0;
    }
};
register_shutdown_function(static function () use (&$campaign_id, &$recipient_id, &$mail_captured, &$mail_calls, &$cleanup_ok, $expected_email, $cleanup, $wpdb) {
    $campaign_status = $campaign_id ? (string) $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_circular_invitations WHERE id=%d", $campaign_id)) : '';
    $recipient = $recipient_id ? $wpdb->get_row($wpdb->prepare("SELECT status,public_token_hash,recipient_email FROM {$wpdb->prefix}zmp_circular_recipients WHERE id=%d", $recipient_id)) : null;
    $valid_synthetic_send = $mail_captured && $mail_calls === 1 && $campaign_status === 'sent'
        && $recipient && $recipient->status === 'sent' && $recipient->recipient_email === $expected_email
        && preg_match('/^[a-f0-9]{64}$/', (string) $recipient->public_token_hash) === 1;
    $cleanup();
    $cleanup_ok = !$campaign_id && !$recipient_id;
    WP_CLI::log('synthetic_recipient_captured_before_transport=' . ($mail_captured && $mail_calls === 1 ? 'PASS' : 'FAIL'));
    WP_CLI::log('send_handler_completed_campaign_and_token_state=' . ($valid_synthetic_send ? 'PASS' : 'FAIL'));
    WP_CLI::log('exact_fixture_cleanup=' . ($cleanup_ok ? 'PASS' : 'FAIL'));
    if (!$valid_synthetic_send || !$cleanup_ok) {
        WP_CLI::error('Synthetic invitation handler check failed. No real transport was invoked.');
    }
    WP_CLI::success('Synthetic invitation send handler verified with in-memory mail capture; fixture removed.');
});
add_filter('pre_wp_mail', static function ($pre, $atts) use (&$mail_captured, &$mail_calls, $expected_email) {
    $mail_calls++;
    $to = $atts['to'] ?? '';
    $targets = is_array($to) ? $to : preg_split('/[,;]/', (string) $to);
    $mail_captured = in_array($expected_email, array_map('trim', $targets), true);
    return true; // Short-circuit before PHPMailer/SMTP.
}, 10, 2);

$now = current_time('mysql');
$inserted = $wpdb->insert($wpdb->prefix . 'zmp_circular_invitations', array(
    'season_id' => absint($season->id),
    'academic_year' => (string) $season->academic_year,
    'title' => 'Synthetic invitation send check',
    'body_template' => 'Synthetic-only test message.',
    'registration_deadline' => '',
    'requirements' => 'Synthetic test only.',
    'category_ids_json' => '[]',
    'group_ids_json' => '{}',
    'school_contact_ids_json' => '[]',
    'details_snapshot' => '{}',
    'status' => 'approved',
    'board_approval_reference' => 'SYNTHETIC-TEST-ONLY',
    'board_approval_date' => gmdate('Y-m-d'),
    'approved_by' => $user_id,
    'approved_at' => $now,
    'created_by' => $user_id,
    'created_at' => $now,
    'updated_at' => $now,
), array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%s','%s'));
if ($inserted !== 1 || $wpdb->last_error !== '') {
    WP_CLI::error('Could not create isolated synthetic campaign fixture.');
}
$campaign_id = absint($wpdb->insert_id);
$inserted = $wpdb->insert($wpdb->prefix . 'zmp_circular_recipients', array(
    'invitation_id' => $campaign_id,
    'school_contact_id' => 0,
    'school_name' => 'Synthetic school for email capture test',
    'recipient_email' => $expected_email,
    'status' => 'pending',
), array('%d','%d','%s','%s','%s'));
if ($inserted !== 1 || $wpdb->last_error !== '') {
    WP_CLI::error('Could not create isolated synthetic recipient fixture.');
}
$recipient_id = absint($wpdb->insert_id);
$_POST = array(
    'invitation_id' => $campaign_id,
    'confirm_send' => '1',
    'expected_count' => '1',
    '_wpnonce' => wp_create_nonce(ZMP_Circular_Invitations::NONCE),
);
$_REQUEST = $_POST;
ZMP_Circular_Invitations::handle_send();
WP_CLI::error('Unexpectedly returned from send handler without redirect/exit.');


