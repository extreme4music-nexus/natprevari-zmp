<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) { WP_CLI::error('local runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;

$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_comms_' . strtolower($suffix);
$title = 'Synthetic route check ' . $suffix;
$year = '2026/2027';
$posts_table = $wpdb->posts;
$announcements_table = $wpdb->prefix . 'zmp_announcements';
$notifications_table = $wpdb->prefix . 'zmp_notifications';
$baseline_posts = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts_table");
$baseline_announcements = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements_table");
$baseline_notifications = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications_table");
$user_id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(40, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic Communications Moderator',
    'role' => 'subscriber',
));
if (is_wp_error($user_id)) { WP_CLI::error('synthetic moderator creation failed'); }
$user = new WP_User($user_id);
$user->add_role('zmp_moderator');
clean_user_cache($user_id);
wp_set_current_user($user_id);
$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => '0',
    'kind' => 'announcement',
    'publish_status' => 'draft',
    'academic_year' => $year,
    'category_id' => '0',
    'phase' => '',
    'title' => $title,
    'body' => 'Synthetic local draft; no notification or publication is expected.',
);
$_REQUEST = $_POST;
$result = array(
    'moderator_capability' => current_user_can('zmp_moderator_cap'),
    'synthetic_draft_created' => false,
    'no_public_post_created' => false,
    'no_notification_created' => false,
    'announcement_count_restored' => false,
    'post_count_restored' => false,
    'notification_count_restored' => false,
    'synthetic_user_removed' => false,
);
register_shutdown_function(static function () use (&$result, $wpdb, $posts_table, $announcements_table, $notifications_table, $baseline_posts, $baseline_announcements, $baseline_notifications, $title, $user_id) {
    $ann = $wpdb->get_row($wpdb->prepare(
        "SELECT id,status,created_by,wp_post_id FROM $announcements_table WHERE title=%s AND created_by=%d ORDER BY id DESC LIMIT 1",
        $title, (int) $user_id
    ));
    if ($ann) {
        $result['synthetic_draft_created'] = $ann->status === 'draft' && (int) $ann->created_by === (int) $user_id && !(int) $ann->wp_post_id;
        $notification_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $notifications_table WHERE announcement_id=%d", (int) $ann->id
        ));
        $result['no_notification_created'] = $notification_count === 0;
        $result['no_public_post_created'] = !(int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE ID=%d", (int) $ann->wp_post_id
        ));
        $wpdb->delete($notifications_table, array('announcement_id' => (int) $ann->id));
        $wpdb->delete($announcements_table, array('id' => (int) $ann->id));
    }
    wp_delete_user($user_id);
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements_table") === $baseline_announcements;
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts_table") === $baseline_posts;
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications_table") === $baseline_notifications;
    $result['synthetic_user_removed'] = !get_userdata($user_id);
    echo 'ZMP_SYNTHETIC_RESULT=' . wp_json_encode($result) . PHP_EOL;
});

ZMP_Communications::handle_publish();
WP_CLI::error('draft handler returned without redirect/exit');
