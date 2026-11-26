<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$now = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_rollback_' . strtolower($now);
$title = '[SYNTHETIC LOCAL TEST] rollback ' . $now;
$body = 'Synthetic announcement must roll back when its linked post fails.';
$year = '2026/2027';
$ann_table = $wpdb->prefix . 'zmp_announcements';
$notice_table = $wpdb->prefix . 'zmp_notifications';
$post_table = $wpdb->posts;
$mentor_table = $wpdb->prefix . 'zmp_mentors';
$ann_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $ann_table");
$notice_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notice_table");
$post_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $post_table");
$user_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
$recipients = array();
foreach ((array) $wpdb->get_results("SELECT m.wp_user_id,u.user_login,u.user_email FROM $mentor_table m JOIN {$wpdb->users} u ON u.ID=m.wp_user_id") as $mentor) {
    if (strpos((string) $mentor->user_login, 'zmp66') !== 0 || substr((string) $mentor->user_email, -16) !== '@example.invalid') {
        WP_CLI::error('recipient synthetic-safety precondition failed');
    }
    $recipients[] = (int) $mentor->wp_user_id;
}
if (count($recipients) !== 2) {
    WP_CLI::error('expected exactly two existing synthetic recipients');
}
$uid = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(48, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic rollback moderator',
    'role' => 'subscriber',
));
if (is_wp_error($uid) || !$uid) {
    WP_CLI::error('synthetic moderator creation failed');
}
$uid = (int) $uid;
$user = new WP_User($uid);
$user->add_role('zmp_moderator');
clean_user_cache($uid);
wp_set_current_user($uid);
$mail_attempts = 0;
$forced_failure = 0;
$redirect = '';
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);
add_filter('wp_insert_post_empty_content', static function ($empty, $postarr) use ($title, &$forced_failure) {
    if ((string) ($postarr['post_title'] ?? '') === $title) {
        $forced_failure++;
        return true;
    }
    return $empty;
}, PHP_INT_MAX, 2);
add_filter('wp_redirect', static function ($location, $status) use (&$redirect) {
    $redirect = (string) $location;
    return $location;
}, PHP_INT_MAX, 2);
register_shutdown_function(static function () use ($wpdb, $ann_table, $notice_table, $post_table, $uid, $title, $ann_before, $notice_before, $post_before, $user_before, &$mail_attempts, &$forced_failure, &$redirect) {
    $ann = $wpdb->get_row($wpdb->prepare("SELECT id FROM $ann_table WHERE title=%s AND created_by=%d", $title, $uid));
    $owned_notices = $ann ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $notice_table WHERE announcement_id=%d", (int) $ann->id)) : 0;
    $owned_posts = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $post_table WHERE post_title=%s AND post_author=%d", $title, $uid));
    $counts_unchanged_before_cleanup = !$ann
        && $owned_notices === 0
        && $owned_posts === 0
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $ann_table") === $ann_before
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $notice_table") === $notice_before
        && (int) $wpdb->get_var("SELECT COUNT(*) FROM $post_table") === $post_before;
    if ($ann) {
        $wpdb->delete($notice_table, array('announcement_id' => (int) $ann->id), array('%d'));
        $wpdb->delete($ann_table, array('id' => (int) $ann->id, 'created_by' => $uid), array('%d', '%d'));
    }
    foreach ((array) get_posts(array('post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'title' => $title, 'author' => $uid)) as $post_id) {
        wp_delete_post((int) $post_id, true);
    }
    if (get_userdata($uid)) {
        wp_delete_user($uid);
    }
    $final = array(
        'forced_wp_post_failure_triggered' => $forced_failure === 1,
        'redirect_status' => (string) wp_parse_url($redirect, PHP_URL_QUERY),
        'redirected_post_create_error' => strpos($redirect, 'post_create_error') !== false,
        'announcement_notifications_posts_unchanged_before_cleanup' => $counts_unchanged_before_cleanup,
        'no_email_attempt' => $mail_attempts === 0,
        'exact_cleanup' => !get_userdata($uid)
            && (int) $wpdb->get_var("SELECT COUNT(*) FROM $ann_table") === $ann_before
            && (int) $wpdb->get_var("SELECT COUNT(*) FROM $notice_table") === $notice_before
            && (int) $wpdb->get_var("SELECT COUNT(*) FROM $post_table") === $post_before,
    );
    WP_CLI::log(wp_json_encode($final));
    foreach ($final as $key => $value) {
        if ($key !== 'redirect_status' && $value !== true) {
            WP_CLI::error('rollback assertion failed: ' . $key);
        }
    }
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
