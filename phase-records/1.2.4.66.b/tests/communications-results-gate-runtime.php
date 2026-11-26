<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';

global $wpdb;
$mode = getenv('ZMP_TEST_MODE');
if (!in_array($mode, array('new', 'draft'), true)) {
    WP_CLI::error('set ZMP_TEST_MODE to new or draft');
}
$year = '2026/2027';
$phase = 'regional';
$competition_id = ZMP_Communications::legacy_competition_id_for_year($year);
$closed_competition_id = ZMP_Communications::closed_legacy_competition_id($year, $phase);
$results_rows = $competition_id ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s AND phase=%s",
    $competition_id,
    $year,
    $phase
)) : 0;
$phase_status = $competition_id ? (string) $wpdb->get_var($wpdb->prepare(
    "SELECT status FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s",
    $competition_id,
    $phase
)) : '';
if (!$competition_id || $closed_competition_id || $results_rows !== 0 || $phase_status !== 'open') {
    WP_CLI::error('expected an open phase with no results; handler was not called');
}

$announcements = $wpdb->prefix . 'zmp_announcements';
$notifications = $wpdb->prefix . 'zmp_notifications';
$posts = $wpdb->posts;
$before = array(
    'announcements' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements"),
    'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications"),
    'posts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts"),
    'users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
);
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_results_gate_' . strtolower($suffix);
$title = '[SYNTHETIC LOCAL TEST] Results gate ' . $mode . ' ' . $suffix;
$body = 'Synthetic unconfirmed results announcement must remain unpublished.';
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

$announcement_id = 0;
if ($mode === 'draft') {
    $inserted = $wpdb->insert($announcements, array(
        'kind' => 'results',
        'academic_year' => $year,
        'category_id' => 0,
        'phase' => $phase,
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
}

$result = array(
    'open_phase_no_results_precondition' => $phase_status === 'open' && $results_rows === 0 && !$closed_competition_id,
    'moderator_authorized' => current_user_can('zmp_moderator_cap'),
    'redirected_as_results_not_confirmed' => false,
    'new_announcement_not_written' => $mode === 'new' ? false : null,
    'draft_remains_unpublished' => $mode === 'draft' ? false : null,
    'no_notifications_written' => false,
    'no_wordpress_post_written' => false,
    'mail_attempts' => 0,
    'announcement_count_restored' => false,
    'notification_count_restored' => false,
    'post_count_restored' => false,
    'user_count_restored' => false,
    'synthetic_user_removed' => false,
);
if ($mode === 'draft') {
    $result['draft_remains_unpublished'] = (string) $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM $announcements WHERE id=%d AND created_by=%d",
        $announcement_id,
        (int) $user_id
    )) === 'draft';
}
$mail_attempts = 0;
$GLOBALS['zmp_results_gate_redirect'] = '';
add_filter('wp_redirect', static function ($location, $status) {
    $GLOBALS['zmp_results_gate_redirect'] = (string) $location;
    return $location;
}, PHP_INT_MAX, 2);
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);

register_shutdown_function(static function () use (&$result, &$mail_attempts, $wpdb, $mode, $announcements, $notifications, $posts, $user_id, $announcement_id, $title, $before) {
    $redirect = (string) ($GLOBALS['zmp_results_gate_redirect'] ?? '');
    $query = array();
    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $query);
    $result['redirected_as_results_not_confirmed'] = ($query['zmp_comms_status'] ?? '') === 'results_not_confirmed';
    $fixture = $wpdb->get_row($wpdb->prepare(
        "SELECT id,status,wp_post_id,published_at FROM $announcements WHERE title=%s AND created_by=%d ORDER BY id DESC LIMIT 1",
        $title,
        (int) $user_id
    ));
    if ($mode === 'new') {
        $result['new_announcement_not_written'] = !$fixture;
    } elseif ($fixture) {
        $result['draft_remains_unpublished'] =
            $result['draft_remains_unpublished'] &&
            (int) $fixture->id === $announcement_id &&
            $fixture->status === 'draft' &&
            !(int) $fixture->wp_post_id &&
            empty($fixture->published_at);
    }
    $notice_count = $mode === 'draft'
        ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $notifications WHERE announcement_id=%d", $announcement_id))
        : (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $notifications WHERE announcement_id IN (SELECT id FROM $announcements WHERE title=%s AND created_by=%d)", $title, (int) $user_id));
    $result['no_notifications_written'] = $notice_count === 0;
    $linked_post_count = $mode === 'draft' && $announcement_id
        ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_zmp_announcement_id' AND meta_value=%s", (string) $announcement_id))
        : (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN $posts p ON p.ID=pm.post_id WHERE pm.meta_key='_zmp_announcement_id' AND p.post_title=%s", $title));
    $result['no_wordpress_post_written'] = $linked_post_count === 0;

    if ($mode === 'draft') {
        $wpdb->delete($notifications, array('announcement_id' => $announcement_id), array('%d'));
        $wpdb->delete($announcements, array('id' => $announcement_id, 'created_by' => (int) $user_id), array('%d', '%d'));
    } else {
        $wpdb->delete($announcements, array('title' => $title, 'created_by' => (int) $user_id), array('%s', '%d'));
    }
    wp_delete_user((int) $user_id);
    $result['mail_attempts'] = $mail_attempts;
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements") === $before['announcements'];
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications") === $before['notifications'];
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts") === $before['posts'];
    $result['user_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $before['users'];
    $result['synthetic_user_removed'] = !get_userdata((int) $user_id);
    echo 'ZMP_COMMUNICATIONS_RESULTS_GATE_' . strtoupper($mode) . '=' . wp_json_encode($result) . PHP_EOL;
});

$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => (string) $announcement_id,
    'kind' => 'results',
    'publish_status' => 'published',
    'academic_year' => $year,
    'category_id' => '0',
    'phase' => $phase,
    'title' => $title,
    'body' => $body,
);
$_REQUEST = $_POST;
if ($mode === 'new') {
    ZMP_Communications::handle_publish();
} else {
    ZMP_Communications::handle_publish_draft();
}
WP_CLI::error('results handler returned without redirect/exit');
