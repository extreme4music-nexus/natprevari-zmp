<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';

global $wpdb;
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_comms_' . strtolower($suffix);
$title = '[SYNTHETIC LOCAL TEST] Communications publish ' . $suffix;
$body = 'Synthetic publication used only to verify local notification and post synchronization.';
$year = '2026/2027';
$announcement_table = $wpdb->prefix . 'zmp_announcements';
$notification_table = $wpdb->prefix . 'zmp_notifications';
$posts_table = $wpdb->posts;
$mentors_table = $wpdb->prefix . 'zmp_mentors';
$post_count_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts_table");
$announcement_count_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcement_table");
$notification_count_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notification_table");
$user_count_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
$mentor_rows = (array) $wpdb->get_results("SELECT m.wp_user_id,u.user_login,u.user_email FROM $mentors_table m JOIN {$wpdb->users} u ON u.ID=m.wp_user_id ORDER BY m.wp_user_id");
$expected_recipients = array();
foreach ($mentor_rows as $mentor) {
    if (strpos((string) $mentor->user_login, 'zmp66') !== 0 || substr((string) $mentor->user_email, -16) !== '@example.invalid') {
        WP_CLI::error('recipient safety precondition failed; no handler was called');
    }
    $expected_recipients[] = (int) $mentor->wp_user_id;
}
sort($expected_recipients, SORT_NUMERIC);
if (!$expected_recipients || count($expected_recipients) !== 2) {
    WP_CLI::error('expected exactly two pre-existing synthetic recipients; no handler was called');
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
$synthetic_user = new WP_User($user_id);
$synthetic_user->add_role('zmp_moderator');
clean_user_cache($user_id);
wp_set_current_user($user_id);

$result = array(
    'recipient_safety_precondition' => true,
    'moderator_capability' => current_user_can('zmp_moderator_cap'),
    'published_announcement' => false,
    'exact_synthetic_notifications' => false,
    'published_wordpress_post_linked' => false,
    'notification_email_intercepted' => 0,
    'announcement_count_restored' => false,
    'notification_count_restored' => false,
    'post_count_restored' => false,
    'user_count_restored' => false,
    'synthetic_user_removed' => false,
);
$mail_attempts = 0;
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);

register_shutdown_function(static function () use (&$result, &$mail_attempts, $wpdb, $announcement_table, $notification_table, $posts_table, $user_id, $title, $body, $year, $expected_recipients, $post_count_before, $announcement_count_before, $notification_count_before, $user_count_before) {
    $announcement = $wpdb->get_row($wpdb->prepare(
        "SELECT id,kind,academic_year,category_id,phase,title,body,status,created_by,wp_post_id,published_at FROM $announcement_table WHERE title=%s AND created_by=%d ORDER BY id DESC LIMIT 1",
        $title,
        (int) $user_id
    ));
    if ($announcement) {
        $result['published_announcement'] =
            $announcement->kind === 'announcement' &&
            $announcement->academic_year === $year &&
            (int) $announcement->category_id === 0 &&
            $announcement->phase === '' &&
            $announcement->body === $body &&
            $announcement->status === 'published' &&
            (int) $announcement->created_by === (int) $user_id &&
            !empty($announcement->published_at);

        $actual_recipients = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT recipient_user_id FROM $notification_table WHERE announcement_id=%d AND status='unread' ORDER BY recipient_user_id",
            (int) $announcement->id
        )));
        sort($actual_recipients, SORT_NUMERIC);
        $result['exact_synthetic_notifications'] = $actual_recipients === $expected_recipients;

        $post_id = (int) $announcement->wp_post_id;
        $post = $post_id ? get_post($post_id) : null;
        $result['published_wordpress_post_linked'] = $post &&
            $post->post_status === 'publish' &&
            $post->post_type === 'post' &&
            (int) $post->post_author === (int) $user_id &&
            $post->post_title === $title &&
            (int) get_post_meta($post_id, '_zmp_announcement_id', true) === (int) $announcement->id &&
            get_post_meta($post_id, '_zmp_announcement_scope', true) === $year;

        $wpdb->delete($notification_table, array('announcement_id' => (int) $announcement->id), array('%d'));
        $post_ids = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => '_zmp_announcement_id',
            'meta_value' => (string) (int) $announcement->id,
            'author' => (int) $user_id,
            'title' => $title,
        ));
        foreach ($post_ids as $post_id) {
            wp_delete_post((int) $post_id, true);
        }
        $wpdb->delete($announcement_table, array('id' => (int) $announcement->id, 'created_by' => (int) $user_id), array('%d', '%d'));
    }
    wp_delete_user((int) $user_id);
    $result['notification_email_intercepted'] = $mail_attempts;
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcement_table") === $announcement_count_before;
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notification_table") === $notification_count_before;
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts_table") === $post_count_before;
    $result['user_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $user_count_before;
    $result['synthetic_user_removed'] = !get_userdata((int) $user_id);
    echo 'ZMP_COMMUNICATIONS_PUBLISH_RESULT=' . wp_json_encode($result) . PHP_EOL;
});

$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => '0',
    'kind' => 'announcement',
    'publish_status' => 'published',
    'academic_year' => $year,
    'category_id' => '0',
    'phase' => '',
    'title' => $title,
    'body' => $body,
);
$_REQUEST = $_POST;
ZMP_Communications::handle_publish();
WP_CLI::error('publish handler returned without redirect/exit');
