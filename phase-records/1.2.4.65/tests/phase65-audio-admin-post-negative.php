<?php
/**
 * Bounded local negative-route check for approved-audio manual approval.
 * Run only with WP-CLI on the disposable `.65` site. The only HTTP calls are
 * loopback POSTs to this same WordPress site; this test never fetches the
 * synthetic candidate URLs or the owner's URL.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Approved_Audio_Tools')) {
    fwrite(STDERR, "wordpress_plugin_required\n");
    exit(2);
}

global $wpdb;
if (DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "wrong_synthetic_database\n");
    exit(2);
}

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) {
    $assertions[$name] = $condition ? 'PASS' : 'FAIL';
};

$admin = get_user_by('login', 'zmp65_synthetic_admin');
$admin_id = $admin instanceof WP_User ? absint($admin->ID) : 0;
if (!$admin_id || !user_can($admin_id, 'manage_options') || substr(strrchr((string) $admin->user_email, '@') ?: '', 1) !== 'example.invalid') {
    fwrite(STDERR, "synthetic_admin_guard_failed\n");
    exit(3);
}

$option_name = ZMP_Approved_Audio_Tools::OPTION;
$option_row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", $option_name), ARRAY_A);
if ($wpdb->last_error !== '') { fwrite(STDERR, "option_snapshot_failed\n"); exit(3); }
$option_existed = is_array($option_row);
$option_before = maybe_unserialize($option_row['option_value'] ?? '');
$meta_rows = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id", $admin_id, '_session_tokens'), ARRAY_A);
if (!is_array($meta_rows) || $wpdb->last_error !== '') { fwrite(STDERR, "session_snapshot_failed\n"); exit(3); }

$candidate_url = 'https://1.1.1.1/zmp65-synthetic-audio-review';
$privacy_url = 'https://1.1.1.1/zmp65-synthetic-privacy';
$terms_url = 'https://1.1.1.1/zmp65-synthetic-terms';
$public_url = new ReflectionMethod('ZMP_Approved_Audio_Tools', 'public_url');
$public_url->setAccessible(true);
foreach (array($candidate_url, $privacy_url, $terms_url) as $url) {
    if ($public_url->invoke(null, $url) === '') {
        fwrite(STDERR, "synthetic_url_validation_failed_before_mutation\n");
        exit(3);
    }
}

$tool_id = 'zmp65-negative-route';
$fixture = array(
    'name' => 'Synthetic route candidate',
    'category' => 'vocal_remover',
    'url' => $candidate_url,
    'privacy_url' => $privacy_url,
    'terms_url' => $terms_url,
    'free_limits' => 'Synthetic limits',
    'duration_format_limits' => 'Synthetic format',
    'quality_notes' => 'Synthetic quality',
    'rights_note' => 'Synthetic rights',
    'processing_claim' => 'Synthetic processing statement',
    'audit_state' => 'probe_denied',
    'http_status' => 403,
    'auto_checked_at' => current_time('mysql', true),
    'manual_status' => 'pending',
);
$fixture_option = is_array($option_before) ? $option_before : array();
if (isset($fixture_option[$tool_id])) { fwrite(STDERR, "synthetic_tool_id_collision\n"); exit(3); }
$fixture_option[$tool_id] = $fixture;

$session_token = '';
$auth_cookie = '';
$logged_cookie = '';
$url = admin_url('admin-post.php');
$url_parts = wp_parse_url($url);
if (!is_array($url_parts)
    || strtolower((string) ($url_parts['scheme'] ?? '')) !== 'http'
    || strtolower((string) ($url_parts['host'] ?? '')) !== '127.0.0.1'
    || (int) ($url_parts['port'] ?? 0) !== 8094
    || (string) ($url_parts['path'] ?? '') !== '/wp-admin/admin-post.php'
    || isset($url_parts['user'])
    || isset($url_parts['pass'])
    || isset($url_parts['query'])
    || isset($url_parts['fragment'])) {
    fwrite(STDERR, "admin_post_url_not_exact_loopback\n");
    exit(3);
}

$restore_state = static function () use ($wpdb, $option_name, $option_row, $option_existed, $admin_id, $meta_rows) {
    $ok = true;
    if ($option_existed) {
        $ok = $wpdb->update($wpdb->options, array('option_value'=>$option_row['option_value'],'autoload'=>$option_row['autoload']), array('option_name'=>$option_name), array('%s','%s'), array('%s')) !== false && $wpdb->last_error === '';
    } else {
        $ok = $wpdb->delete($wpdb->options, array('option_name'=>$option_name), array('%s')) !== false && $wpdb->last_error === '';
    }
    $deleted = $wpdb->delete($wpdb->usermeta, array('user_id'=>$admin_id,'meta_key'=>'_session_tokens'), array('%d','%s'));
    $ok = $ok && $deleted !== false && $wpdb->last_error === '';
    foreach ($meta_rows as $row) {
        $inserted = $wpdb->insert($wpdb->usermeta, array('umeta_id'=>(int)$row['umeta_id'],'user_id'=>$admin_id,'meta_key'=>'_session_tokens','meta_value'=>$row['meta_value']), array('%d','%d','%s','%s'));
        $ok = $ok && $inserted !== false && $wpdb->last_error === '';
    }
    wp_cache_delete($option_name, 'options');
    wp_cache_delete('alloptions', 'options');
    clean_user_cache($admin_id);
    $current = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1", $option_name), ARRAY_A);
    $ok = $ok && $wpdb->last_error === '';
    if ($option_existed) {
        $ok = $ok && is_array($current) && (string)$current['option_value']===(string)$option_row['option_value'] && (string)$current['autoload']===(string)$option_row['autoload'];
    } else {
        $ok = $ok && $current === null;
    }
    $current_meta = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id", $admin_id, '_session_tokens'), ARRAY_A);
    return $ok && $wpdb->last_error === '' && $current_meta === $meta_rows;
};

try {
    update_option($option_name, $fixture_option, false);
    wp_cache_delete($option_name, 'options');
    $assert(is_array(get_option($option_name)) && isset(get_option($option_name)[$tool_id]), 'synthetic_probe_denied_candidate_seeded');

    $base_post = array(
        'action' => 'zmp_audio_tool_approve',
        'id' => $tool_id,
        'manual_review_note' => 'Synthetic incomplete review',
    );
    $unauthenticated = wp_remote_post($url, array('body'=>$base_post,'redirection'=>0,'timeout'=>12));
    $unauth_status = is_wp_error($unauthenticated) ? 0 : (int)wp_remote_retrieve_response_code($unauthenticated);
    $assert($unauth_status >= 400, 'unauthenticated_approval_post_rejected');
    $after_unauth = get_option($option_name, array());
    $assert(is_array($after_unauth) && ($after_unauth[$tool_id]['manual_status'] ?? '') === 'pending', 'unauthenticated_post_did_not_change_candidate');

    $expires = time() + 1800;
    $session_token = WP_Session_Tokens::get_instance($admin_id)->create($expires);
    $auth_cookie = wp_generate_auth_cookie($admin_id, $expires, 'auth', $session_token);
    $logged_cookie = wp_generate_auth_cookie($admin_id, $expires, 'logged_in', $session_token);
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    wp_set_current_user($admin_id);
    $cookie_header = AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie;
    $nonce = wp_create_nonce('zmp_audio_tool_approve_' . $tool_id);
    $incomplete_post = $base_post + array(
        '_wpnonce' => $nonce,
        'reviewed' => array('privacy','terms','free','format','quality','processing','rights','youtube_excluded'),
    );
    $incomplete = wp_remote_post($url, array('headers'=>array('Cookie'=>$cookie_header),'body'=>$incomplete_post,'redirection'=>0,'timeout'=>12));
    $incomplete_status = is_wp_error($incomplete) ? 0 : (int)wp_remote_retrieve_response_code($incomplete);
    $location = is_wp_error($incomplete) ? '' : (string)wp_remote_retrieve_header($incomplete, 'location');
    $assert($incomplete_status === 302 && strpos($location, 'notice=manual_review_required') !== false, 'valid_admin_post_with_missing_url_opened_check_rejected');
    $after_incomplete = get_option($option_name, array());
    $assert(is_array($after_incomplete) && ($after_incomplete[$tool_id]['manual_status'] ?? '') === 'pending' && empty($after_incomplete[$tool_id]['manual_reviewed_by']), 'incomplete_checklist_did_not_approve_candidate');
} catch (Throwable $error) {
    $assertions['execution'] = 'FAIL';
    fwrite(STDERR, 'audio_negative_route:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    if ($session_token !== '') { WP_Session_Tokens::get_instance($admin_id)->destroy($session_token); }
    foreach (array(LOGGED_IN_COOKIE, AUTH_COOKIE) as $cookie_name) { unset($_COOKIE[$cookie_name]); }
    wp_set_current_user(0);
    $assert($restore_state(), 'cleanup_restores_exact_audio_option_and_admin_session_meta');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures,'unauthenticated_http_status'=>$unauth_status ?? null,'incomplete_checklist_http_status'=>$incomplete_status ?? null), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
