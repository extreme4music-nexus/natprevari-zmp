<?php
/**
 * One bounded actual-loopback HTTP check for the Teacher's school-profile
 * save hook under ready and deliberately incomplete schema.
 * Run only with WP-CLI in the disposable `.65` site.
 */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') {
    fwrite(STDERR, "wrong_synthetic_database\n");
    exit(2);
}

$uid = 30;
$school_id = 4;
$profile_table = $wpdb->prefix . 'zmp_mentor_school_profiles';
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$option_names = array(
    'zmp_db_version', 'zmp_role_messenger_schema_retry', 'zmp_role_messenger_schema_retry_after',
    'zmp_debug_events', '_transient_zmp_school_profile_notice_' . $uid,
    '_transient_timeout_zmp_school_profile_notice_' . $uid,
);
$profile_meta_keys = array('first_name','last_name','nickname','description','rich_editing','comment_shortcuts','admin_color','show_admin_bar_front','locale','syntax_highlighting','_session_tokens');
$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) { $assertions[$name] = $condition ? 'PASS' : 'FAIL'; };

$user_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID=%d", $uid), ARRAY_A);
$meta_placeholders = implode(',', array_fill(0, count($profile_meta_keys), '%s'));
$meta_args = array_merge(array($uid), $profile_meta_keys);
$user_meta = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN ({$meta_placeholders}) ORDER BY umeta_id", $meta_args), ARRAY_A);
$profile_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$profile_table}` WHERE mentor_id=%d AND school_id=%d LIMIT 1", 3, $school_id), ARRAY_A);
$index_exists = $wpdb->get_var("SHOW INDEX FROM `{$school_table}` WHERE Key_name='mentor_active'");
$option_snapshot = array();
$snapshot_ok = $user_row && is_array($user_meta) && $profile_row && $index_exists !== null;
foreach ($option_names as $name) {
    $wpdb->last_error = '';
    $option_snapshot[$name] = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
    $snapshot_ok = $snapshot_ok && $wpdb->last_error === '';
}
if (!$snapshot_ok) { fwrite(STDERR, "safe_snapshot_or_baseline_check_failed\n"); exit(3); }

$restore_options = static function () use ($wpdb, $option_names, $option_snapshot) {
    $ok = true;
    foreach ($option_names as $name) {
        $old = $option_snapshot[$name];
        $wpdb->last_error = '';
        $current = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name));
        if ($wpdb->last_error !== '') { $ok = false; continue; }
        if (is_array($old)) {
            if ($current === null) { $ok = $ok && $wpdb->insert($wpdb->options, array('option_name'=>$name,'option_value'=>$old['option_value'],'autoload'=>$old['autoload']), array('%s','%s','%s')) !== false && $wpdb->last_error === ''; }
            else { $ok = $ok && $wpdb->update($wpdb->options, array('option_value'=>$old['option_value'],'autoload'=>$old['autoload']), array('option_name'=>$name), array('%s','%s'), array('%s')) !== false && $wpdb->last_error === ''; }
        } elseif ($current !== null) { $ok = $ok && $wpdb->delete($wpdb->options, array('option_name'=>$name), array('%s')) !== false && $wpdb->last_error === ''; }
        $wpdb->last_error = '';
        $now = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
        $readback_ok = $wpdb->last_error === '';
        if (is_array($old)) { $ok = $ok && $readback_ok && is_array($now) && (string)$now['option_value']===(string)$old['option_value'] && (string)$now['autoload']===(string)$old['autoload']; }
        else { $ok = $ok && $readback_ok && $now === null; }
        wp_cache_delete($name, 'options');
    }
    wp_cache_delete('alloptions', 'options');
    return $ok;
};

$restore_user = static function () use ($wpdb, $uid, $user_row, $user_meta, $profile_meta_keys) {
    $user_data = $user_row;
    unset($user_data['ID']);
    $ok = $wpdb->update($wpdb->users, $user_data, array('ID'=>$uid)) !== false;
    foreach ($profile_meta_keys as $key) {
        $wpdb->last_error = '';
        $deleted = $wpdb->delete($wpdb->usermeta, array('user_id'=>$uid,'meta_key'=>$key), array('%d','%s'));
        $ok = $ok && $deleted !== false && $wpdb->last_error === '';
    }
    foreach ($user_meta as $meta) {
        $wpdb->last_error = '';
        $inserted = $wpdb->insert($wpdb->usermeta, array('umeta_id'=>(int)$meta['umeta_id'],'user_id'=>$uid,'meta_key'=>$meta['meta_key'],'meta_value'=>$meta['meta_value']), array('%d','%d','%s','%s'));
        $ok = $ok && $inserted !== false && $wpdb->last_error === '';
    }
    $meta_args = array_merge(array($uid), $profile_meta_keys);
    $meta_placeholders = implode(',', array_fill(0, count($profile_meta_keys), '%s'));
    $wpdb->last_error = '';
    $restored_meta = $wpdb->get_results($wpdb->prepare("SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN ({$meta_placeholders}) ORDER BY umeta_id", $meta_args), ARRAY_A);
    $ok = $ok && $wpdb->last_error === '' && $restored_meta === $user_meta;
    clean_user_cache($uid);
    return $ok;
};

$restore_profile = static function () use ($wpdb, $profile_table, $profile_row) {
    $data = $profile_row;
    unset($data['id']);
    $ok = $wpdb->update($profile_table, $data, array('id'=>(int)$profile_row['id'])) !== false;
    $now = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$profile_table}` WHERE id=%d", (int)$profile_row['id']), ARRAY_A);
    return $ok && $now === $profile_row;
};

$index_removed = false;
$session_token = '';
try {
    $user = get_userdata($uid);
    $mentor = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d LIMIT 1", $uid));
    $role_ok = $user && in_array('zmp_teacher', (array)$user->roles, true) && (int)$mentor === 3;
    $assert($role_ok, 'synthetic_teacher_and_owned_school_fixture_ready');
    if (!$role_ok) { throw new RuntimeException('synthetic_fixture_not_ready'); }

    $expires = time() + 1800;
    $session_token = WP_Session_Tokens::get_instance($uid)->create($expires);
    $auth_cookie = wp_generate_auth_cookie($uid, $expires, 'auth', $session_token);
    $logged_cookie = wp_generate_auth_cookie($uid, $expires, 'logged_in', $session_token);
    $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    wp_set_current_user($uid);
    $cookie_header = AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie;
    $url = 'http://127.0.0.1:8094/wp-admin/profile.php';
    $get = wp_remote_get($url, array('headers'=>array('Cookie'=>$cookie_header),'redirection'=>0,'timeout'=>12));
    $get_ok = !is_wp_error($get) && (int)wp_remote_retrieve_response_code($get) === 200;
    $body = $get_ok ? (string)wp_remote_retrieve_body($get) : '';
    $nonce_match = $get_ok && preg_match('/name=["\']_wpnonce["\'][^>]*value=["\']([^"\']+)["\']/i', $body, $core_nonce_match)
        && preg_match('/name=["\']zmp_school_profile_nonce["\'][^>]*value=["\']([^"\']+)["\']/i', $body, $school_nonce_match);
    $assert($get_ok && $nonce_match, 'actual_loopback_profile_get_authenticated_and_nonces_rendered');
    if (!$nonce_match) { throw new RuntimeException('authenticated_profile_get_or_nonce_failed'); }
    $core_nonce = html_entity_decode($core_nonce_match[1], ENT_QUOTES, 'UTF-8');
    $school_nonce = html_entity_decode($school_nonce_match[1], ENT_QUOTES, 'UTF-8');

    $meta = array();
    foreach ($user_meta as $item) { $meta[(string)$item['meta_key']] = maybe_unserialize($item['meta_value']); }
    $profile_payload = array();
    foreach (array('official_email','employment_term','workload','replacement_for','replacement_start_date','replacement_end_date','retired','replacement','employment_term_visible','workload_visible','retired_visible','replacement_visible','replacement_for_visible','replacement_start_date_visible','replacement_end_date_visible') as $field) {
        $profile_payload[$field] = $profile_row[$field] ?? '';
    }
    $baseline_workload = (string)$profile_row['workload'];
    $ready_workload = $baseline_workload === 'part' ? 'full' : 'part';
    $drift_workload = $ready_workload;
    $post_profile = static function ($workload) use ($url, $cookie_header, $core_nonce, $school_nonce, $user_row, $meta, $school_id, $profile_payload) {
        $payload = $profile_payload;
        $payload['workload'] = $workload;
        $form = array(
            'action'=>'update', '_wpnonce'=>$core_nonce, '_wp_http_referer'=>'/wp-admin/profile.php',
            'checkuser_id'=>(int)$user_row['ID'], 'user_id'=>(int)$user_row['ID'],
            'user_login'=>(string)$user_row['user_login'], 'email'=>(string)$user_row['user_email'],
            'nickname'=>(string)($meta['nickname'] ?? $user_row['user_login']), 'display_name'=>(string)$user_row['display_name'],
            'first_name'=>(string)($meta['first_name'] ?? ''), 'last_name'=>(string)($meta['last_name'] ?? ''),
            'url'=>(string)$user_row['user_url'], 'description'=>(string)($meta['description'] ?? ''),
            'zmp_school_profile_nonce'=>$school_nonce, 'zmp_school_profile'=>array($school_id=>$payload),
        );
        return wp_remote_post($url, array('headers'=>array('Cookie'=>$cookie_header,'Referer'=>$url),'body'=>$form,'redirection'=>0,'timeout'=>12));
    };

    $ready_response = $post_profile($ready_workload);
    $ready_status = is_wp_error($ready_response) ? 0 : (int)wp_remote_retrieve_response_code($ready_response);
    $ready_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$profile_table}` WHERE id=%d", (int)$profile_row['id']), ARRAY_A);
    $assert($ready_status >= 200 && $ready_status < 400 && is_array($ready_row) && (string)$ready_row['workload'] === $ready_workload, 'actual_http_profile_save_succeeds_with_ready_schema');

    $assert($restore_profile(), 'baseline_profile_restored_before_drift_request');
    foreach (array('zmp_role_messenger_schema_retry','zmp_role_messenger_schema_retry_after') as $name) { delete_option($name); }
    update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
    $index_removed = false !== $wpdb->query("ALTER TABLE `{$school_table}` DROP INDEX `mentor_active`");
    $assert($index_removed, 'controlled_profile_schema_drift_created');
    if ($index_removed) {
        $drift_response = $post_profile($drift_workload);
        $drift_status = is_wp_error($drift_response) ? 0 : (int)wp_remote_retrieve_response_code($drift_response);
        $after_drift = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$profile_table}` WHERE id=%d", (int)$profile_row['id']), ARRAY_A);
        $retry_deadline = get_option('zmp_role_messenger_schema_retry_after', '');
        $retry_valid = ZMP_Role_Messenger::schema_retry_pending() && is_scalar($retry_deadline) && ctype_digit((string)$retry_deadline)
            && (int)$retry_deadline >= time() + 240 && (int)$retry_deadline <= time() + 300;
        $assert($drift_status >= 200 && $drift_status < 400 && is_array($after_drift) && (string)$after_drift['workload'] === $baseline_workload, 'actual_http_profile_save_fails_closed_under_schema_drift');
        $assert($retry_valid, 'actual_http_profile_drift_persists_five_minute_retry');
    }
} catch (Throwable $error) {
    $assertions['execution'] = 'FAIL';
    fwrite(STDERR, 'profile_http_route_check:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    if ($index_removed) {
        $existing_index = $wpdb->get_var("SHOW INDEX FROM `{$school_table}` WHERE Key_name='mentor_active'");
        $index_restored = $existing_index !== null;
        if (!$index_restored) { $index_restored = false !== $wpdb->query("ALTER TABLE `{$school_table}` ADD KEY `mentor_active` (`mentor_id`,`is_active`)"); }
        $assert($index_restored, 'cleanup_restores_mentor_active_index');
    }
    $assert($restore_profile(), 'cleanup_restores_exact_profile_row');
    if ($session_token !== '') { WP_Session_Tokens::get_instance($uid)->destroy($session_token); }
    foreach (array(LOGGED_IN_COOKIE, AUTH_COOKIE) as $cookie_name) { unset($_COOKIE[$cookie_name]); }
    wp_set_current_user(0);
    $assert($restore_user(), 'cleanup_restores_exact_user_and_user_meta_rows');
    $assert($restore_options(), 'cleanup_restores_exact_option_existence_value_and_autoload_state');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
