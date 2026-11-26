<?php
/** Local-only retry-cooldown regression for a confirmed stale schema marker. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only.\n"); exit(2); }
global $wpdb;
$root = '/home/tonic/zmp65-review-20261124/site-66a-subject-test';
$url = rtrim(home_url('/'), '/');
if (!defined('DB_NAME') || DB_NAME !== 'zmp65_subject_test' || $wpdb->prefix !== 'z66a_'
    || rtrim((string) realpath(ABSPATH), '/') !== $root || $url !== 'http://127.0.0.1:8100'
    || !function_exists('zmp_competitions_init') || !defined('ZMP_SCHEMA_VERSION')) {
    fwrite(STDERR, "BLOCKED: unexpected site/database identity; writes=0\n"); exit(2);
}
$retry_key = ZMP_Role_Messenger::SCHEMA_RETRY_OPTION;
$retry_after_key = 'zmp_role_messenger_schema_retry_after';
$missing = new stdClass();
$original = array(
    $retry_key => get_option($retry_key, $missing),
    $retry_after_key => get_option($retry_after_key, $missing),
);
$checks = array();
$assert = static function($name, $value) use (&$checks) { $checks[$name] = $value ? 'PASS' : 'FAIL'; };
$read_option = static function($key, $value) use ($missing) {
    if ($value === $missing) { delete_option($key); } else { update_option($key, $value, false); }
};
try {
    update_option('zmp_db_version', '1.2.4.64', false);
    update_option($retry_key, 1, false);
    update_option($retry_after_key, time() + 240, false);
    zmp_competitions_init();
    $assert('stale_marker_bypasses_future_retry_cooldown', get_option('zmp_db_version') === ZMP_SCHEMA_VERSION
        && !ZMP_Role_Messenger::schema_retry_pending());

    update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
    update_option($retry_key, 1, false);
    update_option($retry_after_key, time() + 240, false);
    zmp_competitions_init();
    $assert('current_marker_preserves_ordinary_retry_cooldown', get_option('zmp_db_version') === ZMP_SCHEMA_VERSION
        && ZMP_Role_Messenger::schema_retry_pending() && !ZMP_Role_Messenger::schema_retry_allowed());
} catch (Throwable $e) {
    fwrite(STDERR, 'TEST EXCEPTION: ' . sanitize_key($e->getMessage()) . "\n");
    $checks['dispatcher-completed'] = 'FAIL';
} finally {
    foreach ($original as $key => $value) { $read_option($key, $value); }
    update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
    wp_cache_delete('alloptions', 'options');
}
foreach ($checks as $name => $status) { echo $status . ' ' . $name . "\n"; }
$passed = count(array_filter($checks, static function($status) { return $status === 'PASS'; }));
echo 'SUMMARY ' . $passed . '/' . count($checks) . " PASS\n";
exit($passed === count($checks) ? 0 : 1);
