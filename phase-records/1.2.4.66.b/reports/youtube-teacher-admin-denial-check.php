<?php
/** Synthetic Teacher authorization checks for YouTube admin-only routes. */
if (!defined('ABSPATH') || !class_exists('ZMP_YouTube')) { fwrite(STDERR, "wordpress_or_youtube_class_missing\n"); exit(2); }
$teacher_id = 117;
$before_option = get_option(ZMP_YouTube::OPTION, null);
global $wpdb;
$media_table = $wpdb->prefix . 'zmp_youtube_media';
$jury_table = $wpdb->prefix . 'zmp_youtube_regional_jury';
$before_media = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$media_table}");
$before_jury = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$jury_table}");
$results = array();
add_filter('wp_die_handler', function () {
    return function ($message = '', $title = '', $args = array()) {
        throw new RuntimeException('WP_DIE:' . (int) ($args['response'] ?? 0));
    };
}, 9999);
wp_set_current_user($teacher_id);
$_POST = array(); $_GET = array();
$checks = array(
    'admin_page' => array('ZMP_YouTube', 'render_admin_page'),
    'settings_save' => array('ZMP_YouTube', 'save_settings'),
    'oauth_connect' => array('ZMP_YouTube', 'connect'),
    'oauth_callback' => array('ZMP_YouTube', 'callback'),
    'oauth_disconnect' => array('ZMP_YouTube', 'disconnect'),
    'regional_juror_assignment' => array('ZMP_YouTube', 'handle_regional_jury_assignment'),
    'uncertain_upload_clear' => array('ZMP_YouTube', 'clear_uncertain_upload'),
);
foreach ($checks as $name => $callable) {
    try { call_user_func($callable); $results[$name] = 'UNEXPECTED_RETURN'; }
    catch (Throwable $e) { $results[$name] = $e->getMessage() === 'WP_DIE:403' ? 'DENIED_403' : 'UNEXPECTED_' . get_class($e); }
}
foreach ($results as $name => $result) { echo $name . '=' . $result . "\n"; }
$after_option = get_option(ZMP_YouTube::OPTION, null);
$after_media = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$media_table}");
$after_jury = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$jury_table}");
$unchanged = serialize($before_option) === serialize($after_option) && $before_media === $after_media && $before_jury === $after_jury;
echo 'youtube_settings_and_media_juror_counts_unchanged=' . ($unchanged ? 'PASS' : 'FAIL') . "\n";
if (!$unchanged) { exit(1); }
foreach ($results as $result) { if ($result !== 'DENIED_403') exit(1); }
echo "Success: synthetic Teacher denied all sampled YouTube admin-only surfaces; state unchanged.\n";
