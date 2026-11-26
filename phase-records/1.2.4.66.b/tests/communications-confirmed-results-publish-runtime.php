<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Communications')) {
    WP_CLI::error('isolated WordPress runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';

global $wpdb;
$year = '2026/2027';
$phase = 'regional';
$competition_id = ZMP_Communications::legacy_competition_id_for_year($year);
$phase_status_table = $wpdb->prefix . 'zmp_phase_status';
$results_table = $wpdb->prefix . 'zmp_phase_results';
$announcements = $wpdb->prefix . 'zmp_announcements';
$notifications = $wpdb->prefix . 'zmp_notifications';
$posts = $wpdb->posts;
$mentor_id = 116;
$recipient = $wpdb->get_row($wpdb->prepare(
    "SELECT u.ID,u.user_login,u.user_email FROM {$wpdb->users} u JOIN {$wpdb->prefix}zmp_mentors m ON m.wp_user_id=u.ID WHERE u.ID=%d",
    $mentor_id
));
$status_before = $competition_id ? $wpdb->get_row($wpdb->prepare(
    "SELECT competition_id,phase,status,closed_by,closed_at,decision_reason FROM $phase_status_table WHERE competition_id=%d AND phase=%s",
    $competition_id,
    $phase
)) : null;
$results_before = $competition_id ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $results_table WHERE competition_id=%d AND academic_year=%s AND phase=%s",
    $competition_id,
    $year,
    $phase
)) : -1;
if (!$competition_id || !$status_before || $status_before->status !== 'open' || $results_before !== 0 ||
    !$recipient || strpos((string) $recipient->user_login, 'zmp66') !== 0 ||
    substr((string) $recipient->user_email, -16) !== '@example.invalid') {
    WP_CLI::error('safe synthetic positive-result precondition failed; no changes made');
}

$before = array(
    'announcements' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements"),
    'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications"),
    'posts' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts"),
    'users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
    'results' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $results_table"),
);
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_results_publish_' . strtolower($suffix);
$title = '[SYNTHETIC LOCAL TEST] Confirmed results ' . $suffix;
$body = 'Synthetic confirmed result release for the local Communications gate test.';
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

$attempt_id = mt_rand(100000000, 1900000000);
$result_row_id = 0;
$result = array(
    'open_phase_and_empty_results_baseline' => true,
    'moderator_authorized' => current_user_can('zmp_moderator_cap'),
    'synthetic_phase_closed_temporarily' => false,
    'synthetic_confirmed_result_inserted' => false,
    'results_announcement_published' => false,
    'exact_result_mentor_notified' => false,
    'linked_published_wordpress_post' => false,
    'mail_attempts' => 0,
    'announcement_count_restored' => false,
    'notification_count_restored' => false,
    'post_count_restored' => false,
    'user_count_restored' => false,
    'results_count_restored' => false,
    'phase_status_restored_exactly' => false,
    'synthetic_user_removed' => false,
);
$mail_attempts = 0;
add_filter('pre_wp_mail', static function ($return, $atts) use (&$mail_attempts) {
    $mail_attempts++;
    return true;
}, PHP_INT_MAX, 2);
register_shutdown_function(static function () use (&$result, &$mail_attempts, &$result_row_id, $wpdb, $phase_status_table, $results_table, $announcements, $notifications, $posts, $competition_id, $phase, $status_before, $year, $user_id, $attempt_id, $title, $body, $mentor_id, $before) {
    if ($result_row_id) {
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id,kind,academic_year,category_id,phase,title,body,status,created_by,wp_post_id,published_at FROM $announcements WHERE title=%s AND created_by=%d ORDER BY id DESC LIMIT 1",
            $title,
            (int) $user_id
        ));
        $result['results_announcement_published'] = $item &&
            $item->kind === 'results' &&
            $item->academic_year === $year &&
            (int) $item->category_id === 0 &&
            $item->phase === $phase &&
            $item->title === $title &&
            $item->body === $body &&
            $item->status === 'published' &&
            (int) $item->created_by === (int) $user_id &&
            !empty($item->published_at);
        if ($item) {
            $actual_recipients = array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
                "SELECT recipient_user_id FROM $notifications WHERE announcement_id=%d AND status='unread' ORDER BY recipient_user_id",
                (int) $item->id
            )));
            $result['exact_result_mentor_notified'] = $actual_recipients === array((int) $mentor_id);
            $post_id = (int) $item->wp_post_id;
            $post = $post_id ? get_post($post_id) : null;
            $result['linked_published_wordpress_post'] = $post &&
                $post->post_status === 'publish' &&
                $post->post_title === $title &&
                (int) $post->post_author === (int) $user_id &&
                (int) get_post_meta($post_id, '_zmp_announcement_id', true) === (int) $item->id &&
                get_post_meta($post_id, '_zmp_announcement_scope', true) === $year . ' · Регионално натпреварувачко ниво';
            $wpdb->delete($notifications, array('announcement_id' => (int) $item->id), array('%d'));
            $post_ids = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'any',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_key' => '_zmp_announcement_id',
                'meta_value' => (string) (int) $item->id,
                'author' => (int) $user_id,
                'title' => $title,
            ));
            foreach ($post_ids as $post_id) {
                wp_delete_post((int) $post_id, true);
            }
            $wpdb->delete($announcements, array('id' => (int) $item->id, 'created_by' => (int) $user_id), array('%d', '%d'));
        }
    }
    if ($result_row_id) {
        $result_row = $wpdb->get_row($wpdb->prepare("SELECT id,attempt_id FROM $results_table WHERE id=%d", $result_row_id));
        $result['synthetic_confirmed_result_inserted'] = $result_row && (int) $result_row->attempt_id === (int) $attempt_id;
        $wpdb->delete($results_table, array('id' => (int) $result_row_id, 'attempt_id' => (int) $attempt_id), array('%d', '%d'));
    }
    $wpdb->update($phase_status_table, array(
        'status' => (string) $status_before->status,
        'closed_by' => (int) $status_before->closed_by,
        'closed_at' => $status_before->closed_at,
        'decision_reason' => $status_before->decision_reason,
    ), array('competition_id' => (int) $competition_id, 'phase' => $phase), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
    $restored = $wpdb->get_row($wpdb->prepare(
        "SELECT status,closed_by,closed_at,decision_reason FROM $phase_status_table WHERE competition_id=%d AND phase=%s",
        $competition_id,
        $phase
    ));
    $result['synthetic_phase_closed_temporarily'] = $restored && $status_before->status === 'open';
    $result['phase_status_restored_exactly'] = $restored &&
        $restored->status === $status_before->status &&
        (int) $restored->closed_by === (int) $status_before->closed_by &&
        $restored->closed_at === $status_before->closed_at &&
        $restored->decision_reason === $status_before->decision_reason;
    wp_delete_user((int) $user_id);
    $result['mail_attempts'] = $mail_attempts;
    $result['announcement_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $announcements") === $before['announcements'];
    $result['notification_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications") === $before['notifications'];
    $result['post_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $posts") === $before['posts'];
    $result['user_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $before['users'];
    $result['results_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $results_table") === $before['results'];
    $result['synthetic_user_removed'] = !get_userdata((int) $user_id);
    echo 'ZMP_COMMUNICATIONS_CONFIRMED_RESULTS_PUBLISH=' . wp_json_encode($result) . PHP_EOL;
});

$updated = $wpdb->update($phase_status_table, array(
    'status' => 'closed',
    'closed_by' => (int) $user_id,
    'closed_at' => current_time('mysql'),
    'decision_reason' => 'Synthetic local Communications gate test only.',
), array('competition_id' => (int) $competition_id, 'phase' => $phase), array('%s', '%d', '%s', '%s'), array('%d', '%s'));
if ($updated !== 1) {
    WP_CLI::error('temporary phase close failed; shutdown cleanup will restore state');
}
$result['synthetic_phase_closed_temporarily'] = true;
$inserted = $wpdb->insert($results_table, array(
    'competition_id' => (int) $competition_id,
    'attempt_id' => (int) $attempt_id,
    'student_id' => (int) $attempt_id,
    'mentor_id' => 1,
    'mentor_user_id' => (int) $mentor_id,
    'academic_year' => $year,
    'phase' => $phase,
    'student_name' => 'Synthetic Results Gate Student',
    'first_name' => 'Synthetic',
    'last_name' => 'Gate',
    'access_code' => 'SYNTHETIC',
    'school_name' => 'Synthetic Test School',
    'municipality' => 'Synthetic Municipality',
    'region' => 'skopje',
    'grade' => 1,
    'total_points' => '10.00',
    'award_level' => 'synthetic',
    'qualified_next_stage' => 0,
    'completed_at' => null,
), array('%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s'));
$result_row_id = $inserted ? (int) $wpdb->insert_id : 0;
if (!$result_row_id) {
    WP_CLI::error('synthetic result insert failed; shutdown cleanup will restore phase status');
}

$_POST = array(
    '_wpnonce' => wp_create_nonce(ZMP_Communications::NONCE),
    'announcement_id' => '0',
    'kind' => 'results',
    'publish_status' => 'published',
    'academic_year' => $year,
    'category_id' => '0',
    'phase' => $phase,
    'title' => $title,
    'body' => $body,
);
$_REQUEST = $_POST;
ZMP_Communications::handle_publish();
WP_CLI::error('confirmed-results handler returned without redirect/exit');
