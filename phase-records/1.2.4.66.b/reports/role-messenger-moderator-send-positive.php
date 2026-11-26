<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('local runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;

$messages = $wpdb->prefix . 'zmp_role_messages';
$notifications = $wpdb->prefix . 'zmp_role_message_notifications';
$reads = $wpdb->prefix . 'zmp_role_message_reads';
$attachments = $wpdb->prefix . 'zmp_role_message_attachments';
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_messenger_' . strtolower($suffix);
$subject = 'Synthetic moderator message ' . $suffix;
$baseline = array(
    'messages' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $messages"),
    'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications"),
    'reads' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $reads"),
    'attachments' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $attachments"),
);
$user_id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(40, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic Messenger Moderator',
    'role' => 'subscriber',
));
if (is_wp_error($user_id)) { WP_CLI::error('synthetic moderator creation failed'); }
$recipient_id = wp_insert_user(array(
    'user_login' => $login . '_recipient',
    'user_pass' => wp_generate_password(40, true, true),
    'user_email' => $login . '_recipient@example.invalid',
    'display_name' => 'Synthetic Messenger Recipient',
    'role' => 'subscriber',
));
if (is_wp_error($recipient_id)) { wp_delete_user($user_id); WP_CLI::error('synthetic recipient creation failed'); }
$user = new WP_User($user_id);
$user->add_role('zmp_moderator');
$recipient = new WP_User($recipient_id);
$recipient->add_role('zmp_moderator');
clean_user_cache($user_id);
clean_user_cache($recipient_id);
wp_set_current_user($user_id);
$_FILES = array();
$_POST = array(
    'zmp_role_message_action' => 'send',
    '_wpnonce' => wp_create_nonce(ZMP_Role_Messenger::NONCE),
    'scope_type' => 'moderator',
    'thread_id' => '0',
    'subject' => $subject,
    'message' => 'Synthetic local moderator-room message for authorization and cleanup verification.',
);
$_REQUEST = $_POST;
$result = array(
    'subscriber_moderator_roles' => in_array('subscriber', (array) $user->roles, true)
        && in_array('zmp_moderator', (array) $user->roles, true)
        && current_user_can('zmp_moderator_cap'),
    'moderator_room_access' => ZMP_Role_Messenger::can_access_room('moderator', 0, 0),
    'message_saved_for_actor' => false,
    'root_thread_self_linked' => false,
    'retention_expiry_set' => false,
    'recipient_moderator_role' => in_array('zmp_moderator', (array) get_userdata($recipient_id)->roles, true)
        && user_can($recipient_id, 'zmp_moderator_cap'),
    'recipient_notification_created' => false,
    'baseline_counts_restored' => false,
    'synthetic_user_removed' => false,
);
register_shutdown_function(static function () use (&$result, $wpdb, $messages, $notifications, $reads, $attachments, $baseline, $subject, $user_id, $recipient_id) {
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id,thread_id,scope_type,season_id,category_id,region_key,sender_id,subject,message,expires_at FROM $messages WHERE sender_id=%d AND subject=%s ORDER BY id DESC LIMIT 1",
        (int) $user_id, $subject
    ));
    if ($row) {
        $result['message_saved_for_actor'] = $row->scope_type === 'moderator' && (int) $row->season_id === 0
            && (int) $row->category_id === 0 && (string) $row->region_key === ''
            && (int) $row->sender_id === (int) $user_id
            && $row->message === 'Synthetic local moderator-room message for authorization and cleanup verification.';
        $result['root_thread_self_linked'] = (int) $row->thread_id === (int) $row->id;
        $result['retention_expiry_set'] = !empty($row->expires_at);
        $result['recipient_notification_created'] = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $notifications WHERE message_id=%d AND thread_id=%d AND scope_type=%s AND recipient_user_id=%d AND status=%s",
            (int) $row->id, (int) $row->id, 'moderator', (int) $recipient_id, 'unread'
        ));
        $wpdb->delete($reads, array('message_id' => (int) $row->id));
        $wpdb->delete($notifications, array('message_id' => (int) $row->id));
        $wpdb->delete($attachments, array('message_id' => (int) $row->id));
        $wpdb->delete($messages, array('id' => (int) $row->id));
    }
    wp_delete_user($user_id);
    wp_delete_user($recipient_id);
    $result['baseline_counts_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $messages") === $baseline['messages']
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications") === $baseline['notifications']
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $reads") === $baseline['reads']
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $attachments") === $baseline['attachments'];
    $result['synthetic_user_removed'] = !get_userdata($user_id) && !get_userdata($recipient_id);
    echo 'ZMP_SYNTHETIC_RESULT=' . wp_json_encode($result) . PHP_EOL;
});

ZMP_Role_Messenger::handle_post();
WP_CLI::error('message handler returned without redirect/exit');
