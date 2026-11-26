<?php
/** Source-level regression for the dashboard release selector. */
define('ABSPATH', __DIR__ . '/');

$root = dirname(__DIR__);
$plugin = file_get_contents($root . '/zmp-competitions.php');
if (!is_string($plugin) || !preg_match("/define\\('ZMP_VERSION',\\s*'([^']+)'\\)/", $plugin, $version)) {
    fwrite(STDERR, "plugin_version_missing\n");
    exit(2);
}
if ($version[1] !== '1.2.4.65') {
    fwrite(STDERR, "expected_plugin_version_1.2.4.65_got_" . $version[1] . "\n");
    exit(1);
}

require_once $root . '/admin/class-zmp-admin.php';
$method = new ReflectionMethod('ZMP_Admin', 'whats_new_releases');
$method->setAccessible(true);
$releases = $method->invoke(null);
foreach (array('1.2.4.65', '1.2.4.64', '1.2.4.63') as $required) {
    if (!isset($releases[$required]) || !is_array($releases[$required]) || !$releases[$required]) {
        fwrite(STDERR, "missing_dashboard_release_{$required}\n");
        exit(1);
    }
}

$notes65 = implode(' ', $releases['1.2.4.65']);
if (strpos($notes65, 'Во тек') === false && strpos($notes65, 'unreleased') === false) {
    fwrite(STDERR, "current_phase_must_be_marked_in_progress\n");
    exit(1);
}
$required65 = array(
    'Претседателот може доброволно да дозволи приказ',
    'согласноста е исклучена по дифолт',
    'важи само за активен мандат',
    'schema_not_ready',
    'само исчистено име на AJAX-дејството',
    'содржината на барањето не се бележи',
);
foreach ($required65 as $required) {
    if (strpos($notes65, $required) === false) {
        fwrite(STDERR, "current_phase_required_content_missing\n");
        exit(1);
    }
}
fwrite(STDOUT, "DASHBOARD_WHATS_NEW_63_64_65_PASS\n");
