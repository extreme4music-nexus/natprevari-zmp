<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';

global $wpdb;
$year = '2026/2027';
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_comms_update_' . strtolower($suffix);
$new_title = '[SYNTHETIC LOCAL TEST] Linked post update ' . $suffix;
$new_body = 'Updated synthetic announcement body for local synchronization test.';
$old_title = '[SYNTHETIC LOCAL TEST] Old linked draft ' . $suffix;
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
$recipient_rows = (array) $wpdb->get_results("SELECT m.wp_user_id,u.user_login,u.user_email FROM $mentors m JOIN {$wpdb->users} u ON u.ID=m.wp_user_id ORDER BY m.wp_user_id");
$recipients = array();
foreach ($recipient_rows as $recipient) {
    if (strpos((string) $recipient->user_login, 'zmp66') !== 0 || substr((string) $recipient->user_email, -16) !== '@example.invalid') {
        WP_CLI::error('recipient safety precondition failed; handler not called');
    }
    $recipients[] = (int) $recipient->wp_user_id;
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
    'title' => $old_title,
    'body' => 'Initial synthetic draft body.',
    'status' => 'draft',
    'created_by' => (int) $user_id,
    'published_at' => null,
    'created_at' => current_time('mysql'),
), array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s'));
$announcement_id = $inserted ? (int) $wpdb->insert_id : 0;
if (!$announcement_id) {
    wp_delete_user((int) $user_id);
    WP_CLI::error('synthetic announcement insert failed; moderator removed');
}
$post_id = wp_insert_post(wp_slash(array(
    'post_title' => $old_title,
    'post_content' => 'Initial synthetic linked post body.',
    'post_status' => 'draft',
    'post_type' => 'post',
    'post_author' => (int) $user_id,
)), true);
if (is_wp_error($post_id) || !$post_id) {
    $wpdb->delete($announcements, array('id' => $announcement_id, 'created_by' => (int) $user_id), array('%d', '%d'));
    wp_delete_user((int) $user_id);
    WP_CLI::error('synthetic linked post insert failed; fixture cleaned');
}
$post_id = (int) $post_id;
update_post_meta($post_id, '_zmp_announcement_id', $announcement_id);
update_post_meta($post_id, '_zmp_announcement_scope', 'OLD SYNTHETIC SCOPE');
$wpdb->update($announcements, array('wp_post_id' => $post_id), array('id' => $announcement_id, 'created_by' => (int) $user_id), array('%d'), array('%d', '%d'));

$result = array(
    'moderator_authorized' => current_user_can('zmp_moderator_cap'),
    'draft_and_linked_post_precondition' => false,
    'announcement_published_with_updated_content' => false,
    'same_linked_post_updated_in_place' => false,
    'no_duplicate_wordpress_post' => false,
    'exact_synthetic_notifications' => false,
    'mail_attempts' => 0,
    'announcement_count_restored' => false,
    'notification_count_restored' => false,
    'post_count_restored' => false,
    'user_count_restored' => false,
    'synthetic_user_removed' => false,
);
$pre = $wpdb->get_row($wpdb->prepare("SELECT status,wp_post_id FROM $announcements WHERE id=%d", $announcement_id));
$result['draft_and_linked_post_precondition'] = $pre && $pre->status === 'draft' && (int) $pre->wp_post_id === $post_id && get_post_status($post_id) === 'draft';
$mail_attempts = 0;
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);
register_shutdown_function(static function () use (&$result, &$mail_attempts, $wpdb, $announcements, $notifications, $posts, $user_id, $announcement_id, $post_id, $old_title, $new_title, $new_body, $year, $recipients, $before) {
    $item = $wpdb->get_row($wpdb->prepare("SELECT status,title,body,wp_post_id,published_at FROM $announcements WHERE id=%d AND created_by=%d", $announcement_id, (int) $user_id));
    if ($item) {
        $result['announcement_published_with_updated_content'] =
            $item->status === 'published' &&
            $item->title === $new_title &&
            $item->body === $new_body &&
            (int) $item->wp_post_id === $post_id &&
            !empty($item->published_at);
    }
    $post = get_post($post_id);
    $expected_content = wpautop(esc_html($new_body));
    $result['same_linked_post_updated_in_place'] = $post &&
        $post->post_status === 'publish' &&
        $post->post_type === 'post' &&
        (int) $post->post_author === (int) $user_id &&
        $post->post_title === $new_title &&
        $post->post_content === $expected_content &&
        (int) get_post_meta($post_id, '_zmp_announcement_id', true) === $announcement_id &&
        get_post_meta($post_id, '_zmp_announcement_scope', true) === $year;
    $linked_post_ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID FROM $posts p JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type='post' AND pm.meta_key='_zmp_announcement_id' AND pm.meta_value=%s",
        (string) $announcement_id
    )));
    $result['no_duplicate_wordpress_post'] = $linked_post_ids === array($post_id);
    $actual = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
        "SELECT recipient_user_id FROM $notifications WHERE announcement_id=%d AND status='unread' ORDER BY recipient_user_id",
        $announcement_id
    )));
    sort($actual, SORT_NUMERIC);
    $result['exact_synthetic_notifications'] = $actual === $recipients;

    $wpdb->delete($notifications, array('announcement_id' => $announcement_id), array('%d'));
    $post = get_post($post_id);
    if ($post && (int) $post->post_author === (int) $user_id && (int) get_post_meta($post_id, '_zmp_announcement_id', true) === $announcement_id) {
        wp_delete_post($post_id, true);
    }
    $wpdb->delete($announcements, array('id' => $announcement_id, 'created_by' => (int) $user_id), array('%d', '%d'));
    wp_delete_user((int) $user_id);
    $result['mail_attempts'] = $mail_attempts;
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements") === $before['announcements'];
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications") === $before['notifications'];
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts") === $before['posts'];
    $result['user_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $before['users'];
    $result['synthetic_user_removed'] = !get_userdata((int) $user_id);
    echo 'ZMP_COMMUNICATIONS_LINKED_POST_UPDATE=' . wp_json_encode($result) . PHP_EOL;
});

$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => (string) $announcement_id,
    'kind' => 'announcement',
    'publish_status' => 'published',
    'academic_year' => $year,
    'category_id' => '0',
    'phase' => '',
    'title' => $new_title,
    'body' => $new_body,
);
$_REQUEST = $_POST;
ZMP_Communications::handle_publish();
WP_CLI::error('linked-post update handler returned without redirect/exit');
