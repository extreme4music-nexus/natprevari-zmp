<?php
/** Prove local synthetic wp_mail interception without invoking a transport. */
if (!defined('WP_CLI') || !function_exists('wp_mail')) {
    WP_CLI::error('WP-CLI/wp_mail unavailable.');
}
$expected = 'zmp-capture-probe@example.invalid';
$captured = false;
add_filter('pre_wp_mail', static function ($pre, $atts) use (&$captured, $expected) {
    $to = (array) ($atts['to'] ?? array());
    $captured = in_array($expected, $to, true);
    return true; // short-circuit before PHPMailer/SMTP transport
}, 10, 2);
$result = wp_mail($expected, 'Synthetic local capture probe', 'Synthetic-only body; no external recipient.');
if ($result !== true || !$captured) {
    WP_CLI::error('FAIL: wp_mail was not captured by pre_wp_mail.');
}
WP_CLI::success('Synthetic wp_mail captured in memory before transport; no SMTP/real email was used.');
