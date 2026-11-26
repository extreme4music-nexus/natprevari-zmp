<?php
/**
 * Focused synthetic WordPress check for safe AJAX action attribution on schema_not_ready logs.
 * Run with WP-CLI eval-file against the disposable `.65` synthetic database only.
 */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb) || !class_exists('ZMP_Debug')) {
    fwrite(STDERR, "wordpress_debug_required\n");
    exit(2);
}
if (DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "wrong_synthetic_database\n");
    exit(3);
}

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) {
    $assertions[$name] = $condition ? 'PASS' : 'FAIL';
};

$event_option_name = ZMP_Debug::OPTION;
$option_names = array(ZMP_Debug::OPTION, ZMP_Debug::CAPTURE_OPTION);
$option_rows = array();
$snapshot_ok = true;
foreach ($option_names as $option_name) {
    $wpdb->last_error = '';
    $option_rows[$option_name] = $wpdb->get_row(
        $wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $option_name),
        ARRAY_A
    );
    if ($wpdb->last_error !== '') { $snapshot_ok = false; }
}
if (!$snapshot_ok) {
    fwrite(STDERR, "debug_option_snapshot_failed_before_mutation\n");
    exit(4);
}

$saved_globals = array(
    '_SERVER' => $_SERVER,
    '_POST' => $_POST,
    '_GET' => $_GET,
    '_COOKIE' => $_COOKIE,
    '_REQUEST' => $_REQUEST,
);

$restore_debug_options = static function () use ($wpdb, $option_names, $option_rows) {
    $all_restored = true;
    foreach ($option_names as $option_name) {
        $option_row = $option_rows[$option_name];
        if (is_array($option_row)) {
            $wpdb->update(
                $wpdb->options,
                array('option_value' => $option_row['option_value'], 'autoload' => $option_row['autoload']),
                array('option_name' => $option_name),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            $wpdb->delete($wpdb->options, array('option_name' => $option_name), array('%s'));
        }
        wp_cache_delete($option_name, 'options');
        wp_cache_delete('alloptions', 'options');
        $wpdb->last_error = '';
        $after = $wpdb->get_row(
            $wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $option_name),
            ARRAY_A
        );
        if ($wpdb->last_error !== '') { $all_restored = false; continue; }
        if (is_array($option_row)) {
            $all_restored = $all_restored && is_array($after)
                && (string) $after['option_value'] === (string) $option_row['option_value']
                && (string) $after['autoload'] === (string) $option_row['autoload'];
        } else {
            $all_restored = $all_restored && $after === null;
        }
    }
    return $all_restored;
};

$run_case = static function ($name, $uri, $post, $get, $cookie, $error_code = 'schema_not_ready', $caller_context = array()) use ($assert, $event_option_name) {
    $_SERVER['REQUEST_URI'] = $uri;
    $_POST = $post;
    $_GET = $get;
    $_COOKIE = $cookie;
    $_REQUEST = array_merge($get, $post, $cookie);

    ZMP_Debug::log(
        'error',
        'School profile schema is not ready.',
        array_merge(array('outcome' => 'fail_closed', 'error_code' => $error_code), $caller_context),
        'school_profile'
    );

    $events = get_option($event_option_name, array());
    $event = is_array($events) ? end($events) : false;
    $context = is_array($event) && isset($event['context']) && is_array($event['context']) ? $event['context'] : array();
    $encoded = is_array($event) ? wp_json_encode($event) : '';
    return array($context, $encoded);
};

try {
    $default_ajax_path = wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
    $assert(is_string($default_ajax_path) && $default_ajax_path !== '', 'wordpress_ajax_path_is_available');

    // POST is authoritative over GET, and only its normalized action name is retained.
    list($context, $encoded) = $run_case(
        'post_precedes_get',
        $default_ajax_path,
        array('action' => 'ZMP_Test/Action', 'nonce' => 'synthetic_nonce_marker', 'payload' => 'synthetic_payload_marker', 'url' => 'https://synthetic.invalid/private'),
        array('action' => 'get_route'),
        array('action' => 'cookie_route'),
        'schema_not_ready',
        array('request_action' => 'caller_forged_action')
    );
    $assert(($context['request_action'] ?? null) === 'zmp_testaction', 'ajax_post_action_precedes_get_and_is_sanitized');
    $assert(strpos($encoded, 'synthetic_nonce_marker') === false && strpos($encoded, 'synthetic_payload_marker') === false && strpos($encoded, 'synthetic.invalid/private') === false, 'ajax_schema_log_excludes_nonce_payload_and_url');

    list($context) = $run_case(
        'get_fallback',
        $default_ajax_path,
        array('nonce' => 'synthetic_nonce_marker'),
        array('action' => 'GET-Only.Route'),
        array('action' => 'cookie_route')
    );
    $assert(($context['request_action'] ?? null) === 'get-onlyroute', 'ajax_get_fallback_is_sanitized_and_cookie_is_ignored');

    list($context) = $run_case(
        'cookie_only',
        $default_ajax_path,
        array(),
        array(),
        array('action' => 'cookie_route')
    );
    $assert(!array_key_exists('request_action', $context), 'ajax_cookie_only_action_is_omitted');

    list($context) = $run_case(
        'nonscalar_post',
        $default_ajax_path,
        array('action' => array('unsafe')),
        array('action' => 'get_route'),
        array()
    );
    $assert(!array_key_exists('request_action', $context), 'ajax_nonscalar_post_action_is_omitted');

    list($context) = $run_case(
        'oversize_raw_action',
        $default_ajax_path,
        array('action' => str_repeat('A', 257)),
        array(),
        array()
    );
    $assert(!array_key_exists('request_action', $context), 'ajax_raw_action_over_256_bytes_is_omitted');

    list($context) = $run_case(
        'oversize_sanitized_action',
        $default_ajax_path,
        array('action' => str_repeat('A', 65)),
        array(),
        array()
    );
    $assert(!array_key_exists('request_action', $context), 'ajax_sanitized_action_over_64_bytes_is_omitted');

    list($context) = $run_case(
        'blank_post_action',
        $default_ajax_path,
        array('action' => ''),
        array('action' => 'get_route'),
        array()
    );
    $assert(!array_key_exists('request_action', $context), 'ajax_blank_post_action_is_omitted_without_fallback');

    list($context) = $run_case(
        'ordinary_profile_path',
        '/wp-admin/profile.php',
        array('action' => 'profile_route'),
        array(),
        array(),
        'schema_not_ready',
        array('request_action' => 'caller_forged_action')
    );
    $assert(!array_key_exists('request_action', $context), 'schema_error_on_non_ajax_path_omits_request_action');

    // Simulate WordPress running from a subdirectory/custom admin path without changing site options.
    $custom_admin_url_filter = static function ($url, $path, $blog_id) {
        if ($path === 'admin-ajax.php') { return 'http://127.0.0.1/custom-admin/admin-ajax.php'; }
        return $url;
    };
    add_filter('admin_url', $custom_admin_url_filter, 10, 3);
    try {
        $custom_ajax_path = wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
        list($context) = $run_case(
            'custom_admin_ajax_path',
            $custom_ajax_path,
            array('action' => 'custom-admin-route'),
            array(),
            array()
        );
        $assert($custom_ajax_path === '/custom-admin/admin-ajax.php', 'custom_admin_url_filter_applied_without_site_mutation');
        $assert(($context['request_action'] ?? null) === 'custom-admin-route', 'request_action_matches_wordpress_custom_admin_ajax_path');
    } finally {
        remove_filter('admin_url', $custom_admin_url_filter, 10);
    }

    list($context) = $run_case(
        'unrelated_error_code',
        $default_ajax_path,
        array('action' => 'unrelated_route'),
        array(),
        array(),
        'database_error',
        array('request_action' => 'caller_forged_action')
    );
    $assert(!array_key_exists('request_action', $context), 'unrelated_ajax_error_code_omits_request_action');
} catch (Throwable $error) {
    $assertions['test_execution'] = 'FAIL';
    fwrite(STDERR, "synthetic_schema_action_test_failed:exception\n");
} finally {
    $_SERVER = $saved_globals['_SERVER'];
    $_POST = $saved_globals['_POST'];
    $_GET = $saved_globals['_GET'];
    $_COOKIE = $saved_globals['_COOKIE'];
    $_REQUEST = $saved_globals['_REQUEST'];
    $assert($restore_debug_options(), 'cleanup_restores_exact_debug_event_and_capture_options');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
