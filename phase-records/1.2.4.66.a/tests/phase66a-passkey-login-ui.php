<?php
/** Concrete Task 4 login-page integration acceptance for .66.a; expected RED before UI implementation. */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys')) {
    fwrite(STDERR, "BLOCKED: .66.a passkey plugin unavailable\n"); exit(2);
}
global $wpdb, $pagenow;
$users_table = $wpdb->users;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$checks = array(); $failures = array();
$count = static function($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function() use ($wpdb, $credential_table) {
    $rows = $wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A);
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};
$before = array($count($users_table), $count($credential_table), $count($challenge_table), $credential_fingerprint());
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$users_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_before = $protected_user ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only')) : 0;
$assert = static function($ok, $label) use (&$checks, &$failures) { $checks[] = $label; if (!$ok) $failures[] = $label; };
$scripts = wp_scripts(); $styles = wp_styles();
$script_queue_before = $scripts->queue; $style_queue_before = $styles->queue;
$pagenow_before = isset($pagenow) ? $pagenow : null;
try {
    // Exercise the same WordPress login template hooks: wp-login.php fires these in this order.
    $pagenow = 'wp-login.php';
    $scripts->queue = array(); $styles->queue = array();
    do_action('login_enqueue_scripts');
    $login_script_queue = $scripts->queue; $login_style_queue = $styles->queue;
    ob_start(); do_action('login_form'); $login_form_hook = (string) ob_get_clean();
    $password_form = wp_login_form(array('echo' => false, 'remember' => false));
    $login_markup = $login_form_hook . (is_string($password_form) ? $password_form : '');

    $assert(preg_match('/<button\b[^>]*\bid=["\']zmp-passkey-login["\'][^>]*>/i', $login_markup) === 1, 'login form hook renders a passkey sign-in button');
    $assert(preg_match('/<(?:div|p|span)\b[^>]*(?:role=["\']status["\']|aria-live=["\'](?:polite|assertive)["\'])[^>]*>/i', $login_markup) === 1, 'login form hook renders an accessible passkey status area');
    $assert(strpos($login_markup, 'name="log"') !== false && strpos($login_markup, 'name="pwd"') !== false, 'standard WordPress username/password form remains available');

    $login_script_handles = array_values(array_filter($login_script_queue, static function($handle) { return strpos((string) $handle, 'zmp-passkey-login') !== false; }));
    $login_style_handles = array_values(array_filter($login_style_queue, static function($handle) { return strpos((string) $handle, 'zmp-passkey-login') !== false; }));
    $assert(count($login_script_handles) > 0, 'passkey login script is enqueued on wp-login.php hook');
    $assert(count($login_style_handles) > 0, 'passkey login styles are enqueued on wp-login.php hook');

    // Login assets must not be placed on the ordinary front-end enqueue path.
    $scripts->queue = array(); $styles->queue = array(); $pagenow = 'index.php';
    do_action('wp_enqueue_scripts');
    $front_script_queue = $scripts->queue; $front_style_queue = $styles->queue;
    $front_login_assets = array_filter(array_merge($front_script_queue, $front_style_queue), static function($handle) { return strpos((string) $handle, 'zmp-passkey-login') !== false; });
    $assert(count($front_login_assets) === 0, 'passkey login script/styles are absent from ordinary front-end enqueue hook');

    $plugin_root = defined('ZMP_PLUGIN_DIR') ? ZMP_PLUGIN_DIR : '';
    $script_path = $plugin_root . 'assets/js/zmp-passkey-login.js';
    $script_source = is_file($script_path) ? file_get_contents($script_path) : false;
    $assert(is_string($script_source) && $script_source !== '', 'passkey login browser script exists for request inspection');
    $options_position = is_string($script_source) ? strpos($script_source, 'zmp_passkey_login_options') : false;
    $request_region = $options_position !== false ? substr($script_source, max(0, $options_position - 500), 1800) : '';
    $has_account_field = preg_match('/["\'](?:username|user_?id|email|account|login)["\']\s*:/i', $request_region) === 1;
    $assert($options_position !== false && !$has_account_field, 'passkey options request source contains no username/email/account identifier fields');
} finally {
    $scripts->queue = $script_queue_before; $styles->queue = $style_queue_before;
    if ($pagenow_before === null) unset($pagenow); else $pagenow = $pagenow_before;
}
$after = array($count($users_table), $count($credential_table), $count($challenge_table), $credential_fingerprint());
$protected_after = $protected_user ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s", $protected_user, 'Synthetic UI review only')) : 0;
$baseline_ok = $after[0] === $before[0] && $after[1] === $before[1] && $after[2] === $before[2]
    && hash_equals($before[3], $after[3]) && $protected_before === $protected_after;
$checks[] = 'read-only login hook inspection preserves users/credentials/challenges and protected credential';
if (!$baseline_ok) $failures[] = 'database or protected credential baseline changed';
if ($failures) {
    fwrite(STDERR, 'FAIL (' . count($failures) . '/' . count($checks) . "): passkey login UI integration\n");
    foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . "\n");
    fwrite(STDOUT, 'DATABASE CLEANUP/BASELINE ' . ($baseline_ok ? 'PASS' : 'FAIL') . "\n");
    exit(1);
}
fwrite(STDOUT, 'PASS (' . count($checks) . "): passkey login UI integration\nDATABASE BASELINE PASS: no rows changed\n");
exit(0);
