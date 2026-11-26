<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';

global $wpdb;
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_comms_draft_' . strtolower($suffix);
$title = '[SYNTHETIC LOCAL TEST] Draft republish ' . $suffix;
$body = 'Synthetic saved draft for the local republish route.';
$year = '2026/2027';
$announcements = $wpdb->prefix . 'zmp_announcements';
$notifications = $wpdb->prefix . 'zmp_notifications';
$posts = $wpdb->posts;
$mentors = $wpdb->prefix . 'zmp_mentors';
$before = array(
    'announcements' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements"),
    'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications"),
    'posts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts"),
    'users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
);
$mentor_rows = (array) $wpdb->get_results("SELECT m.wp_user_id,u.user_login,u.user_email FROM $mentors m JOIN {$wpdb->users} u ON u.ID=m.wp_user_id ORDER BY m.wp_user_id");
$recipients = array();
foreach ($mentor_rows as $mentor) {
    if (strpos((string) $mentor->user_login, 'zmp66') !== 0 || substr((string) $mentor->user_email, -16) !== '@example.invalid') {
        WP_CLI::error('recipient safety precondition failed; handler not called');
    }
    $recipients[] = (int) $mentor->wp_user_id;
}
sort($recipients, SORT_NUMERIC);
if (count($recipients) !== 2) {
    WP_CLI::error('expected exactly two synthetic recipients; handler not called');
}

$user_id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(48, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic Communications Moderator',
    'role' => 'subscriber',
));
if (is_wp_error($user_id)) {
    WP_CLI::error('synthetic moderator creation failed');
}
$user = new WP_User($user_id);
$user->add_role('zmp_moderator');
clean_user_cache($user_id);
wp_set_current_user($user_id);

$inserted = $wpdb->insert($announcements, array(
    'kind' => 'announcement',
    'academic_year' => $year,
    'category_id' => 0,
    'phase' => '',
    'title' => $title,
    'body' => $body,
    'status' => 'draft',
    'created_by' => (int) $user_id,
    'published_at' => null,
    'created_at' => current_time('mysql'),
), array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s'));
$announcement_id = $inserted ? (int) $wpdb->insert_id : 0;
if (!$announcement_id) {
    wp_delete_user((int) $user_id);
    WP_CLI::error('synthetic draft insert failed; moderator removed');
}

$result = array(
    'synthetic_subscriber_moderator_authorized' => current_user_can('zmp_moderator_cap'),
    'fixture_was_draft_before_handler' => false,
    'draft_published' => false,
    'exact_synthetic_recipient_notices' => false,
    'linked_wordpress_post_and_scope' => false,
    'mail_attempts' => 0,
    'announcement_count_restored' => false,
    'notification_count_restored' => false,
    'post_count_restored' => false,
    'user_count_restored' => false,
    'synthetic_user_removed' => false,
);
$result['fixture_was_draft_before_handler'] = (string) $wpdb->get_var($wpdb->prepare(
    "SELECT status FROM $announcements WHERE id=%d AND created_by=%d",
    $announcement_id,
    (int) $user_id
)) === 'draft';
$mail_attempts = 0;
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);

register_shutdown_function(static function () use (&$result, &$mail_attempts, $wpdb, $announcements, $notifications, $posts, $user_id, $announcement_id, $title, $body, $year, $recipients, $before) {
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $announcements WHERE id=%d AND created_by=%d", $announcement_id, (int) $user_id));
    if ($item) {
        $result['draft_published'] = $item->status === 'published' && !empty($item->published_at) && $item->title === $title && $item->body === $body;
        $actual = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT recipient_user_id FROM $notifications WHERE announcement_id=%d AND status='unread' ORDER BY recipient_user_id",
            $announcement_id
        )));
        sort($actual, SORT_NUMERIC);
        $result['exact_synthetic_recipient_notices'] = $actual === $recipients;
        $post_id = (int) $item->wp_post_id;
        $post = $post_id ? get_post($post_id) : null;
        $result['linked_wordpress_post_and_scope'] = $post &&
            $post->post_status === 'publish' &&
            $post->post_type === 'post' &&
            (int) $post->post_author === (int) $user_id &&
            $post->post_title === $title &&
            (int) get_post_meta($post_id, '_zmp_announcement_id', true) === $announcement_id &&
            get_post_meta($post_id, '_zmp_announcement_scope', true) === $year;

        $wpdb->delete($notifications, array('announcement_id' => $announcement_id), array('%d'));
        $post_ids = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => '_zmp_announcement_id',
            'meta_value' => (string) $announcement_id,
            'author' => (int) $user_id,
            'title' => $title,
        ));
        foreach ($post_ids as $post_id) {
            wp_delete_post((int) $post_id, true);
        }
        $wpdb->delete($announcements, array('id' => $announcement_id, 'created_by' => (int) $user_id), array('%d', '%d'));
    }
    wp_delete_user((int) $user_id);
    $result['mail_attempts'] = $mail_attempts;
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements") === $before['announcements'];
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications") === $before['notifications'];
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts") === $before['posts'];
    $result['user_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $before['users'];
    $result['synthetic_user_removed'] = !get_userdata((int) $user_id);
    echo 'ZMP_COMMUNICATIONS_DRAFT_REPUBLISH_RESULT=' . wp_json_encode($result) . PHP_EOL;
});

$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => (string) $announcement_id,
);
$_REQUEST = $_POST;
ZMP_Communications::handle_publish_draft();
WP_CLI::error('draft-publish handler returned without redirect/exit');
