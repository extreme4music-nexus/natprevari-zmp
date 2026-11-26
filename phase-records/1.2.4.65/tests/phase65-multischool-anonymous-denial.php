<?php
/** Bounded anonymous-denial check for the .65 employment directory and profile service. */
if (!defined('ABSPATH')) { exit; }

$results = array();
$failures = array();
$assert = static function ($condition, $name) use (&$results, &$failures) {
    $results[$name] = $condition ? 'PASS' : 'FAIL';
    if (!$condition) { $failures[] = $name; }
};
$abort = static function ($reason) {
    echo wp_json_encode(array('overall'=>'BLOCKED','reason'=>$reason,'profile_values_emitted'=>false,'email_values_emitted'=>false)) . "\n";
    exit(2);
};

global $wpdb;
if (defined('DB_NAME') && DB_NAME !== 'zmp65_isolated') { $abort('wrong_database'); }
if ($wpdb->prefix !== 'z65_') { $abort('wrong_table_prefix'); }
$active = (array) get_option('active_plugins', array());
if ($active !== array('zmp-competitions/zmp-competitions.php')) { $abort('unexpected_active_plugin_inventory'); }
$plugin_root = trailingslashit(WP_PLUGIN_DIR) . 'zmp-competitions/';
$expected = array(
    'class-zmp-mentor-school-profile.php' => '8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B',
    'class-zmp-employment-directory.php' => '255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042',
);
foreach ($expected as $file => $hash) {
    $actual = is_readable($plugin_root . 'includes/' . $file) ? strtoupper(hash_file('sha256', $plugin_root . 'includes/' . $file)) : '';
    if (!hash_equals($hash, $actual)) { $abort('installed_source_hash_mismatch'); }
}
if (!class_exists('ZMP_Mentor_School_Profile') || !class_exists('ZMP_Employment_Directory')) { $abort('feature_classes_unavailable'); }

$original_user = get_current_user_id();
wp_set_current_user(0);
$assert(!is_user_logged_in() && get_current_user_id() === 0, 'anonymous_wp_context_established');
$directory = ZMP_Mentor_School_Profile::get_employment_directory(0, 0);
$assert(is_wp_error($directory) && $directory->get_error_code() === 'forbidden', 'anonymous_directory_service_denied');
$profile = ZMP_Mentor_School_Profile::get_profile(127, 17);
$assert(is_wp_error($profile) && $profile->get_error_code() === 'forbidden', 'anonymous_owner_profile_service_denied');
$html = ZMP_Employment_Directory::render_shortcode();
$assert(is_string($html) && strpos($html, 'Најавете се') !== false, 'anonymous_shortcode_shows_login_notice');
$assert(strpos($html, 'zmp-employment-directory__school') === false
    && strpos($html, 'official_email') === false
    && strpos($html, 'Работен однос') === false
    && strpos($html, 'Пензиониран статус') === false,
    'anonymous_shortcode_contains_no_school_or_employment_fields');
wp_set_current_user($original_user);
$assert(get_current_user_id() === $original_user, 'wordpress_user_context_restored');

$failed = count($failures);
echo wp_json_encode(array(
    'overall' => $failed === 0 ? 'PASS' : 'FAIL',
    'assertions' => $results,
    'source_hashes' => array('profile_service'=>'8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B','directory_renderer'=>'255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042'),
    'profile_values_emitted' => false,
    'email_values_emitted' => false,
    'email_send_attempts' => 0,
)) . "\n";
exit($failed === 0 ? 0 : 1);
