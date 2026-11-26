<?php
/**
 * Bounded local positive-route check for manual approval of a synthetic
 * probe_denied audio candidate. Run only with WP-CLI eval-file on the
 * disposable `.65` site. Candidate URLs use reserved synthetic paths on a
 * public IP and are never requested or opened. The `url_opened` checkbox below
 * is synthetic test input only; it is not evidence of a human opening a URL.
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
$meta_rows = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id", $admin_id, '_session_tokens'), ARRAY_A);
if (!is_array($meta_rows) || $wpdb->last_error !== '') { fwrite(STDERR, "session_snapshot_failed\n"); exit(3); }

$candidate_url = 'https://1.1.1.1/zmp65-synthetic-audio-review';
$privacy_url = 'https://1.1.1.1/zmp65-synthetic-privacy';
$terms_url = 'https://1.1.1.1/zmp65-synthetic-terms';
$public_url = new ReflectionMethod('ZMP_Approved_Audio_Tools', 'public_url');
$public_url->setAccessible(true);
foreach (array($candidate_url, $privacy_url, $terms_url) as $candidate) {
    if ($public_url->invoke(null, $candidate) === '') {
        fwrite(STDERR, "synthetic_url_validation_failed_before_mutation\n");
        exit(3);
    }
}

$tool_id = 'zmp65-positive-manual-route';
$fixture = array(
    'name' => 'Synthetic manual-route candidate',
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
    'needs_admin_attention' => true,
);
$fixture_option = is_array(maybe_unserialize($option_row['option_value'] ?? '')) ? maybe_unserialize($option_row['option_value']) : array();
if (isset($fixture_option[$tool_id])) { fwrite(STDERR, "synthetic_tool_id_collision\n"); exit(3); }
$fixture_option[$tool_id] = $fixture;
$read_option_fresh = static function () use ($option_name) {
    wp_cache_delete($option_name, 'options');
    wp_cache_delete('alloptions', 'options');
    return get_option($option_name, array());
};

$url = admin_url('admin-post.php');
$parts = wp_parse_url($url);
if (!is_array($parts)
    || strtolower((string) ($parts['scheme'] ?? '')) !== 'http'
    || strtolower((string) ($parts['host'] ?? '')) !== '127.0.0.1'
    || (int) ($parts['port'] ?? 0) !== 8094
    || (string) ($parts['path'] ?? '') !== '/wp-admin/admin-post.php'
    || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
    fwrite(STDERR, "admin_post_url_not_exact_loopback\n");
    exit(3);
}

$session_token = '';
$auth_cookie = '';
$logged_cookie = '';
$external_http_attempts = array();
$loopback_http_posts = 0;
$http_guard = static function ($preempt, $args, $request_url) use (&$external_http_attempts, &$loopback_http_posts, $url) {
    if ($request_url === $url) {
        $loopback_http_posts++;
        return $preempt;
    }
    $external_http_attempts[] = (string) $request_url;
    return new WP_Error('zmp65_test_external_request_blocked', 'External HTTP is disabled in this test.');
};
add_filter('pre_http_request', $http_guard, 0, 3);

$restore_state = static function () use ($wpdb, $option_name, $option_row, $option_existed, $admin_id, $meta_rows) {
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
    if ($option_existed) {
        $ok = $ok && is_array($current) && (string)$current['option_value']===(string)$option_row['option_value'] && (string)$current['autoload']===(string)$option_row['autoload'];
    } else {
        $ok = $ok && $current === null;
    }
    $current_meta = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id", $admin_id, '_session_tokens'), ARRAY_A);
    return $ok && $wpdb->last_error === '' && $current_meta === $meta_rows;
};

$unauth_status = null;
$invalid_nonce_status = null;
$approved_status = null;
$approved_location = '';
try {
    update_option($option_name, $fixture_option, false);
    wp_cache_delete($option_name, 'options');
    wp_cache_delete('alloptions', 'options');
    $seeded = get_option($option_name, array());
    $assert(is_array($seeded) && ($seeded[$tool_id]['manual_status'] ?? '') === 'pending', 'synthetic_probe_denied_candidate_seeded_pending');
    $assert(user_can($admin_id, 'manage_options'), 'synthetic_reviewer_has_required_manage_options_capability');

    $expires = time() + 1800;
    $session_token = WP_Session_Tokens::get_instance($admin_id)->create($expires);
    $auth_cookie = wp_generate_auth_cookie($admin_id, $expires, 'auth', $session_token);
    $logged_cookie = wp_generate_auth_cookie($admin_id, $expires, 'logged_in', $session_token);
    $cookie_header = AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie;
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    wp_set_current_user($admin_id);
    $nonce = wp_create_nonce('zmp_audio_tool_approve_' . $tool_id);

    $base_post = array(
        'action' => 'zmp_audio_tool_approve',
        'id' => $tool_id,
        'reviewed' => array('privacy','terms','free','format','quality','processing','rights','youtube_excluded','url_opened'),
        'manual_review_note' => 'Synthetic test input only; no person opened this URL.',
    );
    $unauth = wp_remote_post($url, array('body'=>$base_post,'redirection'=>0,'timeout'=>12));
    $unauth_status = is_wp_error($unauth) ? 0 : (int)wp_remote_retrieve_response_code($unauth);
    $assert($unauth_status >= 400, 'unauthenticated_approval_route_rejected');
    $after_unauth = $read_option_fresh();
    $assert(($after_unauth[$tool_id]['manual_status'] ?? '') === 'pending', 'unauthenticated_request_did_not_approve');

    $invalid = wp_remote_post($url, array('headers'=>array('Cookie'=>$cookie_header),'body'=>$base_post + array('_wpnonce'=>'invalid_nonce_value'),'redirection'=>0,'timeout'=>12));
    $invalid_nonce_status = is_wp_error($invalid) ? 0 : (int)wp_remote_retrieve_response_code($invalid);
    $assert($invalid_nonce_status === 403, 'authenticated_invalid_nonce_rejected');
    $after_invalid = $read_option_fresh();
    $assert(($after_invalid[$tool_id]['manual_status'] ?? '') === 'pending', 'invalid_nonce_did_not_approve');

    $approved = wp_remote_post($url, array('headers'=>array('Cookie'=>$cookie_header),'body'=>$base_post + array('_wpnonce'=>$nonce),'redirection'=>0,'timeout'=>12));
    $approved_status = is_wp_error($approved) ? 0 : (int)wp_remote_retrieve_response_code($approved);
    $approved_location = is_wp_error($approved) ? '' : (string)wp_remote_retrieve_header($approved, 'location');
    $assert($approved_status === 302 && strpos($approved_location, 'notice=approved') !== false, 'valid_admin_nonce_approval_route_redirected_as_approved');

    $saved_option = $read_option_fresh();
    $saved = is_array($saved_option) ? ($saved_option[$tool_id] ?? array()) : array();
    $required = array('privacy','terms','free','format','quality','processing','rights','youtube_excluded','url_opened');
    $assert(($saved['manual_status'] ?? '') === 'approved', 'approval_route_persisted_manual_approval');
    $assert(($saved['audit_state'] ?? '') === 'probe_denied' && (int)($saved['http_status'] ?? 0) === 403, 'approval_preserved_inconclusive_probe_denial');
    $assert(absint($saved['manual_reviewed_by'] ?? 0) === $admin_id && $admin_id > 0, 'approval_bound_to_synthetic_admin_reviewer');
    $assert(!empty($saved['manual_checked_at']) && strtotime((string)$saved['manual_checked_at'] . ' UTC') !== false, 'approval_recorded_review_timestamp');
    $assert(($saved['manual_review_url_hash'] ?? '') === hash('sha256', $candidate_url), 'approval_bound_to_exact_candidate_url_hash');
    $assert(($saved['manual_reviewed_checks'] ?? array()) === $required, 'approval_recorded_complete_synthetic_checklist');
    $assert(($saved['manual_review_note'] ?? '') === 'Synthetic test input only; no person opened this URL.', 'approval_kept_synthetic_review_note');

    $visible = ZMP_Approved_Audio_Tools::teacher_visible_tools();
    $teacher_tool = null;
    foreach ($visible as $item) { if (($item['url'] ?? '') === $candidate_url) { $teacher_tool = $item; break; } }
    $assert(is_array($teacher_tool), 'recent_manual_approval_makes_probe_denied_candidate_teacher_visible');
    $assert(is_array($teacher_tool) && !array_key_exists('manual_reviewed_by', $teacher_tool) && !array_key_exists('manual_review_note', $teacher_tool) && !array_key_exists('manual_review_url_hash', $teacher_tool) && !array_key_exists('audit_state', $teacher_tool), 'teacher_payload_hides_reviewer_and_internal_probe_details');

    ob_start();
    $_GET['page'] = ZMP_Approved_Audio_Tools::PAGE;
    ZMP_Approved_Audio_Tools::render_admin_page();
    $admin_html = (string)ob_get_clean();
    unset($_GET['page']);
    $reviewer_label = (string)$admin->display_name . ' (#' . $admin_id . ')';
    $assert(strpos($admin_html, esc_html($reviewer_label)) !== false && strpos($admin_html, esc_html((string)$saved['manual_review_note'])) !== false, 'admin_catalog_discloses_reviewer_and_note');
    $assert(strpos($admin_html, 'probe-denied') === false && strpos($admin_html, 'HTTP 403') !== false, 'admin_catalog_retains_visible_http_403_context');
    $assert($external_http_attempts === array(), 'no_external_http_request_attempted');
    $assert($loopback_http_posts === 3, 'only_three_expected_loopback_admin_post_requests');
} catch (Throwable $error) {
    if (ob_get_level() > 0) { ob_end_clean(); }
    $assertions['execution'] = 'FAIL';
    fwrite(STDERR, 'audio_manual_route:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    remove_filter('pre_http_request', $http_guard, 0);
    if ($session_token !== '') { WP_Session_Tokens::get_instance($admin_id)->destroy($session_token); }
    foreach (array(LOGGED_IN_COOKIE, AUTH_COOKIE) as $cookie_name) { unset($_COOKIE[$cookie_name]); }
    wp_set_current_user(0);
    $assert($restore_state(), 'cleanup_restores_exact_audio_option_and_admin_session_meta');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array(
    'assertions' => $assertions,
    'failures' => $failures,
    'unauthenticated_http_status' => $unauth_status,
    'invalid_nonce_http_status' => $invalid_nonce_status,
    'approved_http_status' => $approved_status,
    'approved_redirect' => $approved_location,
    'loopback_admin_post_requests' => $loopback_http_posts,
    'external_http_attempt_count' => count($external_http_attempts),
    'url_opened_field_semantics' => 'synthetic checkbox input; no human URL opening asserted',
), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
