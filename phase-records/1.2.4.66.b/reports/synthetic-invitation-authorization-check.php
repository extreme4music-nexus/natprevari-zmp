<?php
/** Read-only route authorization check for the synthetic teacher against invitation send. */
if (!defined('WP_CLI') || !class_exists('ZMP_Circular_Invitations')) {
    WP_CLI::error('WP-CLI or invitation class unavailable.');
}
$teacher_id = 117; // synthetic teacher fixture in the isolated `.66.b` database
wp_set_current_user($teacher_id);
if (!get_userdata($teacher_id) || user_can($teacher_id, 'manage_options')) {
    WP_CLI::error('Synthetic teacher authorization fixture is not suitable.');
}
$denial_status = 0;
add_filter('wp_die_handler', static function ($handler) use (&$denial_status) {
    return static function ($message = '', $title = '', $args = array()) use (&$denial_status) {
        $denial_status = (int) ($args['response'] ?? 0);
        throw new RuntimeException('expected_synthetic_authorization_denial');
    };
});
$_POST = array('invitation_id' => 999999999, 'confirm_send' => '1', 'expected_count' => '0');
$_REQUEST = $_POST;
try {
    do_action('admin_post_zmp_send_circular_invitation');
} catch (RuntimeException $e) {
    if ($e->getMessage() !== 'expected_synthetic_authorization_denial') {
        WP_CLI::error('Unexpected exception in synthetic authorization check.');
    }
}
if ($denial_status !== 403) {
    WP_CLI::error('FAIL: synthetic teacher was not rejected with HTTP 403.');
}
WP_CLI::success('Synthetic teacher denied invitation-send route with HTTP 403 before nonce/resource processing; no fixture or email was created.');
