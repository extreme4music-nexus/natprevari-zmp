<?php
/** Synthetic role-denial matrix for circular invitation admin-post actions. */
if (!defined('WP_CLI') || !class_exists('ZMP_Circular_Invitations')) { WP_CLI::error('required runtime unavailable'); }
global $wpdb;
$uid = 117;
$user = get_userdata($uid);
if (!$user || user_can($uid, 'manage_options') || user_can($uid, 'zmp_moderator_cap') || user_can($uid, 'view_zmp_moderator_dashboard')) { WP_CLI::error('synthetic teacher role fixture is not suitable'); }
$tables = array($wpdb->prefix . ZMP_Circular_Invitations::TABLE, $wpdb->prefix . ZMP_Circular_Invitations::RECIPIENTS);
$before = array(); foreach ($tables as $table) { $before[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"); }
$mail_calls = 0; add_filter('pre_wp_mail', static function () use (&$mail_calls) { $mail_calls++; return true; });
$denied = array();
add_filter('wp_die_handler', static function ($handler) use (&$denied) {
    return static function ($message = '', $title = '', $args = array()) use (&$denied) {
        $denied[] = (int) ($args['response'] ?? 0);
        throw new RuntimeException('expected_role_denial');
    };
});
wp_set_current_user($uid);
$routes = array(
    'admin_post_zmp_save_circular_invitation',
    'admin_post_zmp_approve_circular_invitation',
    'admin_post_zmp_send_circular_invitation',
);
$ok = true;
foreach ($routes as $route) {
    $_POST = array('action'=>substr($route, strlen('admin_post_')), 'invitation_id'=>'999999999'); $_GET = array(); $_REQUEST = $_POST;
    $before_denials = count($denied);
    try { do_action($route); } catch (RuntimeException $e) { if ($e->getMessage() !== 'expected_role_denial') { throw $e; } }
    $route_ok = count($denied) === $before_denials + 1 && end($denied) === 403;
    WP_CLI::log($route . '_teacher_denied_403=' . ($route_ok ? 'PASS' : 'FAIL'));
    if (!$route_ok) { $ok = false; }
}
$after = array(); foreach ($tables as $table) { $after[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"); }
$unchanged = $before === $after; WP_CLI::log('invitation_and_recipient_counts_unchanged=' . ($unchanged ? 'PASS' : 'FAIL'));
WP_CLI::log('mail_transport_calls=' . $mail_calls);
if ($mail_calls !== 0 || !$unchanged) { $ok = false; }
if (!$ok) { WP_CLI::error('One or more synthetic invitation authorization checks failed.'); }
WP_CLI::success('Three synthetic Teacher denials verified; no DB row changes or mail calls.');
