<?php
/** Synthetic integration regression for schema drift discovered on an ordinary request. */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb)) { fwrite(STDERR, "wordpress_required\n"); exit(2); }
$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) { $assertions[$name] = $condition ? 'PASS' : 'FAIL'; };
$ready = static function () { return ZMP_Mentor_Schools::schema_is_compatible() && ZMP_Mentor_School_Profile::schema_is_compatible() && ZMP_School_Email_Access::schema_is_compatible(); };
$table = $wpdb->prefix . 'zmp_mentor_schools';
$assert($ready(), 'pristine_school_profile_email_schemas_ready');
$wpdb->last_error = '';
$dropped = false !== $wpdb->query("ALTER TABLE `{$table}` DROP INDEX `mentor_active`");
$assert($dropped && !$ready(), 'controlled_missing_index_detected');
if ($dropped) {
    zmp_competitions_init();
    $assert($ready(), 'ordinary_request_initializer_repairs_schema_drift');
}
// Restore a clean disposable database even when the regression correctly fails.
ZMP_Mentor_Schools::install();
ZMP_Mentor_School_Profile::install();
ZMP_School_Email_Access::install();
$assert($ready(), 'test_cleanup_restores_ready_schema');
$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
