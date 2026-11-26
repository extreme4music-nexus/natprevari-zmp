<?php
/**
 * Focused positive route verification for three assertion-bound President subject decisions.
 * Synthetic-only; private key, assertion payloads, cookies and nonces remain in memory.
 * No test-runner behavior is under test. This is one separate route check; the Task5 matrix and diagnostics remain unchanged.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')
    || !class_exists('ZMP_President_Subject_Approvals') || !class_exists('ZMP_President_Mandate')
    || !class_exists('ZMP_Mentor_Subject_Proposals') || !class_exists('ZMP_Mentor_Subjects')) {
    fwrite(STDERR, "BLOCKED: .66.a Task5 behavior APIs/services unavailable\n"); exit(2);
}
if (!method_exists('ZMP_Passkeys', 'step_up_options') || !method_exists('ZMP_Passkeys', 'verify_step_up')) {
    fwrite(STDERR, "FAIL (1/1): Task5 step-up APIs are not implemented; no synthetic data was created\n"); exit(1);
}
global $wpdb;
$users_table = $wpdb->users; $usermeta_table = $wpdb->usermeta;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$proposal_events_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$mandate_table = ZMP_President_Mandate::table_name();
$mandate_audit_table = ZMP_President_Mandate::audit_table_name();
$subject_table = $wpdb->prefix . 'zmp_subject_catalog';
$alias_table = $wpdb->prefix . 'zmp_subject_aliases';
$role_option = $wpdb->get_blog_prefix() . 'user_roles';
$checks = array(); $failures = array(); $stage = 'preflight';
$teacher_id = 0; $president_id = 0; $mandate_id = 0; $credential_id = '';
$proposal_ids = array(); $proposal_keys = array(); $challenge_ids = array(); $subject_ids = array(); $alias_ids = array();
$session_tokens = array(); $private_key = null; $counter = 0; $auth_cookie = ''; $logged_cookie = ''; $user_handle = ''; $route_nonce = '';
$original_user = (int) get_current_user_id(); $post_before = $_POST; $request_before = $_REQUEST; $cookie_before = $_COOKIE;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD','PHP_SELF','SCRIPT_NAME');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$count = static function($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function() use ($wpdb, $credential_table) {
    $rows = $wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A);
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$users_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_digest = static function() use ($wpdb, $credential_table, $protected_user) {
    if (!$protected_user) return '';
    $row = $wpdb->get_row($wpdb->prepare("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} WHERE user_id=%d AND label=%s LIMIT 1", $protected_user, 'Synthetic UI review only'), ARRAY_A);
    return is_array($row) ? hash('sha256', serialize($row)) : '';
};
$before = array(
    'users'=>$count($users_table), 'credentials'=>$count($credential_table), 'challenges'=>$count($challenge_table),
    'proposals'=>$count($proposal_table), 'proposal_events'=>$count($proposal_events_table), 'mandates'=>$count($mandate_table),
    'mandate_audit'=>$count($mandate_audit_table), 'subjects'=>$count($subject_table), 'aliases'=>$count($alias_table),
    'credential_fp'=>$credential_fingerprint(), 'protected_digest'=>$protected_digest(),
    'roles_hash'=>hash('sha256', serialize(get_option($role_option, array()))),
);
$assert = static function($ok, $label) use (&$checks, &$failures) { $checks[] = $label; if (!$ok) $failures[] = $label; };
$plugins = get_option('active_plugins', array());
$schema_ok = ZMP_Passkey_Store::schema_is_compatible() && ZMP_Mentor_Subject_Proposals::schema_is_compatible();
$baseline_ok = $before['users'] === 1 && $before['credentials'] === 1 && $before['challenges'] === 0
    && $before['proposals'] === 0 && $before['proposal_events'] === 0 && $before['mandates'] === 0
    && $before['mandate_audit'] === 0 && $before['subjects'] === 0 && $before['aliases'] === 0
    && $protected_user > 0 && $before['protected_digest'] !== '';
$preflight = defined('DB_NAME') && DB_NAME === 'zmp66a_isolated' && $wpdb->prefix === 'z66a_'
    && is_array($plugins) && $plugins === array('zmp-competitions/zmp-competitions.php') && $schema_ok && $baseline_ok;
if (!$preflight) { fwrite(STDERR, "BLOCKED: strict `.66.a` synthetic baseline/schema/plugin preflight changed; no test writes\n"); exit(2); }

$missing = new stdClass();
$debug_before = get_option('zmp_debug_events', $missing);
$capture_before = get_option('zmp_debug_capture', $missing);
$debug_before_hash = hash('sha256', serialize($debug_before));
$b64url = static function($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); };
$site = wp_parse_url(home_url('/'));
$scheme = strtolower((string) ($site['scheme'] ?? 'http'));
$host = strtolower((string) ($site['host'] ?? '127.0.0.1'));
$port = isset($site['port']) ? (int) $site['port'] : ($scheme === 'https' ? 443 : 80);
$origin = $scheme . '://' . $host . ($port !== ($scheme === 'https' ? 443 : 80) ? ':' . $port : '');
$set_request = static function($request_origin = null) use ($scheme, $host, $port, $origin) {
    $_SERVER['HTTP_HOST'] = $host . ':' . $port; $_SERVER['REQUEST_SCHEME'] = $scheme;
    $_SERVER['SERVER_PORT'] = (string) $port; if ($scheme === 'https') $_SERVER['HTTPS'] = 'on'; else unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN'] = $request_origin === null ? $origin : $request_origin;
    $_SERVER['HTTP_REFERER'] = $origin . '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'POST';
};
$new_cookie_session = static function($user_id) use (&$auth_cookie, &$logged_cookie, &$session_tokens) {
    $auth_cb = static function($cookie) use (&$auth_cookie) { if (is_string($cookie)) $auth_cookie = $cookie; };
    $logged_cb = static function($cookie) use (&$logged_cookie) { if (is_string($cookie)) $logged_cookie = $cookie; };
    add_action('set_auth_cookie', $auth_cb, 10, 6); add_action('set_logged_in_cookie', $logged_cb, 10, 6);
    wp_set_current_user((int) $user_id); wp_set_auth_cookie((int) $user_id, false, false);
    remove_action('set_auth_cookie', $auth_cb, 10); remove_action('set_logged_in_cookie', $logged_cb, 10);
    $parsed = $logged_cookie !== '' ? wp_parse_auth_cookie($logged_cookie, 'logged_in') : false;
    $token = is_array($parsed) ? (string) ($parsed['token'] ?? '') : '';
    if ($token !== '') $session_tokens[] = $token;
    $_COOKIE[AUTH_COOKIE] = $auth_cookie; $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    return $token;
};
$last_step_up_error_category = 'NONE';
$issue = static function($proposal_id, $decision, $canonical_id = 0) use (&$challenge_ids, &$route_nonce, &$last_step_up_error_category, $president_id, $assert, $host) {
    $result = ZMP_Passkeys::step_up_options($president_id, 'president_subject_decision', (int) $proposal_id, $decision, (int) $canonical_id, $route_nonce);
    if (is_wp_error($result)) {
        $allowed_error_codes = array(
            'step_up_context_invalid',
            'step_up_nonce_invalid',
            'step_up_authority_session_unavailable',
            'step_up_secure_origin_invalid',
            'step_up_store_schema_unavailable',
            'step_up_library_unavailable',
            'step_up_credentials_unavailable',
            'step_up_challenge_insert_failed',
            'step_up_options_serialization_invalid',
            'step_up_options_generation_failed',
        );
        $candidate_error_code = $result->get_error_code();
        $last_step_up_error_category = is_string($candidate_error_code) && in_array($candidate_error_code, $allowed_error_codes, true)
            ? $candidate_error_code : 'UNEXPECTED_CODE';
        $assert(false, 'step-up options issued for ' . $decision . ' (' . $last_step_up_error_category . ')');
        return null;
    }
    if (!is_array($result)) { $last_step_up_error_category = 'NON_ERROR_RESULT'; $assert(false, 'step-up options issued for ' . $decision . ' (NON_ERROR_RESULT)'); return null; }
    $id = is_string($result['challenge_id'] ?? null) ? $result['challenge_id'] : '';
    $options = is_array($result['options'] ?? null) ? $result['options'] : array();
    if ($id === '' && is_string($options['challenge'] ?? null)) $id = $options['challenge'];
    if ($id === '') { $assert(false, 'step-up challenge ID returned'); return null; }
    $challenge_ids[] = $id;
    $assert(($options['challenge'] ?? $id) === $id && ($options['rpId'] ?? '') === $host
        && ($options['userVerification'] ?? '') === 'required', 'step-up challenge is unambiguous and requires user verification');
    return array('id'=>$id, 'options'=>$options);
};
$assertion = static function($challenge_id, $variant = 'valid', $client_origin = null, $rp = null) use (&$private_key, &$credential_id, &$counter, &$user_handle, $host, $origin, $b64url) {
    $counter++;
    $client = wp_json_encode(array('type'=>'webauthn.get','challenge'=>$challenge_id,'origin'=>$client_origin ?? $origin,'crossOrigin'=>false), JSON_UNESCAPED_SLASHES);
    $rp_hash_host = $variant === 'bad_rp' ? 'wrong.invalid' : (string) ($rp ?? $host);
    $flags = Webauthn\AuthenticatorData::FLAG_UP;
    if ($variant !== 'missing_uv') $flags |= Webauthn\AuthenticatorData::FLAG_UV;
    $auth_data = hash('sha256', $rp_hash_host, true) . chr($flags) . pack('N', $counter);
    $signature = '';
    openssl_sign($auth_data . hash('sha256', $client, true), $signature, $private_key, OPENSSL_ALGO_SHA256);
    if ($variant === 'bad_signature' && $signature !== '') $signature[0] = chr(ord($signature[0]) ^ 0x01);
    return wp_json_encode(array(
        'id'=>$b64url($credential_id), 'rawId'=>$b64url($credential_id), 'type'=>'public-key',
        'response'=>array('clientDataJSON'=>$b64url($client),'authenticatorData'=>$b64url($auth_data),'signature'=>$b64url($signature),'userHandle'=>$b64url($user_handle)),
        'clientExtensionResults'=>new stdClass(),
    ), JSON_UNESCAPED_SLASHES);
};
$verify = static function($challenge, $proposal_id, $decision, $canonical_id, $payload, $user_id = null, $verify_nonce = null) use ($president_id, &$route_nonce) {
    return ZMP_Passkeys::verify_step_up(
        $user_id === null ? $president_id : (int) $user_id,
        (string) $challenge,
        'president_subject_decision',
        (int) $proposal_id,
        (string) $decision,
        (int) $canonical_id,
        (string) $payload,
        $verify_nonce === null ? $route_nonce : (string) $verify_nonce
    );
};
$proposal_snapshot = static function($proposal_id) use ($wpdb, $proposal_table, $proposal_events_table) {
    $proposal = $wpdb->get_row($wpdb->prepare("SELECT id,status,decided_by,decided_at,canonical_subject_id FROM {$proposal_table} WHERE id=%d", (int) $proposal_id), ARRAY_A);
    $events = $wpdb->get_results($wpdb->prepare("SELECT event_type,actor_id,event_at,event_data FROM {$proposal_events_table} WHERE proposal_id=%d ORDER BY id", (int) $proposal_id), ARRAY_A);
    return hash('sha256', serialize(array($proposal, $events)));
};
$route = static function($proposal_id, $decision, $canonical_id, $challenge_id, $payload, $nonce) use (&$auth_cookie, &$logged_cookie, $host, $port, $origin) {
    $url = admin_url('admin-post.php');
    $parts = wp_parse_url($url);
    if (!is_array($parts) || ($parts['host'] ?? '') !== '127.0.0.1' || (int) ($parts['port'] ?? 0) !== 8101
        || ($parts['path'] ?? '') !== '/wp-admin/admin-post.php') return new WP_Error('non_loopback_route');
    return wp_remote_post($url, array(
        'timeout'=>12, 'redirection'=>0, 'sslverify'=>false,
        'headers'=>array('Cookie'=>AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie),
        'body'=>array(
            'action'=>'zmp_president_subject_decision', '_wpnonce'=>$nonce, 'proposal_id'=>(string) $proposal_id,
            'decision'=>$decision, 'canonical_subject_id'=>(string) $canonical_id,
            'step_up_challenge_id'=>$challenge_id, 'step_up_credential'=>$payload,
        ),
    ));
};

try {
    $stage = 'synthetic actors and credential';
    $suffix = bin2hex(random_bytes(8));
    $teacher = wp_insert_user(array('user_login'=>'zmp66a_t5m_teacher_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),'user_email'=>'zmp66a-t5m-teacher-'.$suffix.'@example.invalid','role'=>'zmp_teacher'));
    if (is_wp_error($teacher) || !is_int($teacher) || $teacher < 1) throw new RuntimeException('synthetic teacher creation failed');
    $teacher_id = $teacher;
    $president = wp_insert_user(array('user_login'=>'zmp66a_t5m_president_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),'user_email'=>'zmp66a-t5m-president-'.$suffix.'@example.invalid','role'=>'subscriber'));
    if (is_wp_error($president) || !is_int($president) || $president < 1) throw new RuntimeException('synthetic President creation failed');
    $president_id = $president;
    $set_request();
    $admin = get_user_by('login','zmp66a_admin');
    if (!$admin instanceof WP_User || !user_can($admin,'manage_options')) throw new RuntimeException('synthetic admin actor unavailable');
    $today = current_time('Y-m-d'); $end = ZMP_President_Mandate::term_end_date($today);
    wp_set_current_user($admin->ID);
    $mandate = ZMP_President_Mandate::appoint($president_id,$today,$end,'Synthetic Task5 positive route appointment');
    if (is_wp_error($mandate) || !is_int($mandate) || $mandate < 1) throw new RuntimeException('synthetic mandate appointment failed');
    $mandate_id = $mandate; clean_user_cache($president_id);
    if (!ZMP_President_Mandate::user_has_active_mandate($president_id)) throw new RuntimeException('active synthetic mandate not established');
    wp_set_current_user($president_id); $session_token = $new_cookie_session($president_id);
    if ($session_token === '' || !WP_Session_Tokens::get_instance($president_id)->verify($session_token)) throw new RuntimeException('synthetic President session unavailable');
    $original_auth_cookie = $auth_cookie; $original_logged_cookie = $logged_cookie;

    $user_handle = random_bytes(32); update_user_meta($president_id,ZMP_Passkeys::USER_HANDLE_META,$b64url($user_handle));
    $private_key = openssl_pkey_new(array('private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1'));
    $details = $private_key ? openssl_pkey_get_details($private_key) : false;
    if (!is_array($details) || !isset($details['ec']['x'],$details['ec']['y']) || strlen($details['ec']['x'])!==32 || strlen($details['ec']['y'])!==32) throw new RuntimeException('in-memory P-256 key unavailable');
    $cose = CBOR\MapObject::create();
    $cose->add(CBOR\UnsignedIntegerObject::create(1),CBOR\UnsignedIntegerObject::create(2));
    $cose->add(CBOR\UnsignedIntegerObject::create(3),CBOR\NegativeIntegerObject::create(-7));
    $cose->add(CBOR\NegativeIntegerObject::create(-1),CBOR\UnsignedIntegerObject::create(1));
    $cose->add(CBOR\NegativeIntegerObject::create(-2),CBOR\ByteStringObject::create($details['ec']['x']));
    $cose->add(CBOR\NegativeIntegerObject::create(-3),CBOR\ByteStringObject::create($details['ec']['y']));
    $credential_id = random_bytes(32);
    $record = Webauthn\CredentialRecord::create($credential_id,'public-key',array(),'none',Webauthn\TrustPath\EmptyTrustPath::create(),Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000'),(new CBOR\Encoder())->encode($cose),$user_handle,0);
    if (!ZMP_Passkey_Store::save_credential($president_id,$record,'Synthetic Task5 positive route key')) throw new RuntimeException('synthetic public credential storage failed');

    $stage = 'create fixture proposals and alias subjects';
    $proposal_name = array();
    foreach (array('valid-new','valid-alias','valid-reject') as $kind) {
        $proposal_name[$kind] = 'Synthetic Task5 positive route '.$kind.' '.$suffix;
        wp_set_current_user($teacher_id);
        $id = ZMP_Mentor_Subject_Proposals::submit($teacher_id,$proposal_name[$kind]);
        if (is_wp_error($id) || !is_int($id) || $id<1) throw new RuntimeException('synthetic proposal service failed');
        $proposal_ids[$kind] = $id;
        $row = $wpdb->get_row($wpdb->prepare("SELECT proposal_key,status FROM {$proposal_table} WHERE id=%d",$id),ARRAY_A);
        if (!is_array($row) || ($row['status']??'')!=='pending') throw new RuntimeException('synthetic proposal row missing');
        $proposal_keys[$kind] = (string)$row['proposal_key'];
    }
    wp_set_current_user($admin->ID);
    foreach (array('canonical-A','canonical-B') as $kind) {
        $subject_name = 'Synthetic Task5 positive route '.$kind.' '.$suffix;
        wp_set_current_user($president_id);
        $subject_id = ZMP_Mentor_Subjects::create_approved_subject_for_president($subject_name,$president_id);
        if (is_wp_error($subject_id) || !is_int($subject_id) || $subject_id<1) throw new RuntimeException('synthetic canonical subject creation failed');
        $subject_ids[$kind] = $subject_id;
    }
    wp_set_current_user($president_id); $set_request();
    $nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!wp_verify_nonce($nonce,ZMP_President_Subject_Approvals::NONCE)) throw new RuntimeException('valid President route nonce unavailable');
    $route_nonce = $nonce;

    $stage = 'approve_new actual admin-post route';
    $valid_new = $issue($proposal_ids['valid-new'],'approve_new',0);
    if (!$valid_new) throw new RuntimeException('valid new-subject challenge unavailable');
    $new_response = $route($proposal_ids['valid-new'],'approve_new',0,$valid_new['id'],$assertion($valid_new['id']),$nonce);
    if (is_wp_error($new_response)) throw new RuntimeException('valid new-subject loopback route failed');
    $new_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by,canonical_subject_id FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-new']),ARRAY_A);
    $new_event_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-new'],$president_id,'approved_new'));
    $new_subject_id = is_array($new_row) ? (int)($new_row['canonical_subject_id']??0) : 0;
    $new_subject_owner = $new_subject_id > 0 ? (int)$wpdb->get_var($wpdb->prepare("SELECT created_by FROM {$subject_table} WHERE id=%d",$new_subject_id)) : 0;
    $assert((int)wp_remote_retrieve_response_code($new_response)===302 && is_array($new_row) && ($new_row['status']??'')==='approved'
        && (int)($new_row['decided_by']??0)===$president_id && $new_subject_id>0 && $new_subject_owner===$president_id && $new_event_count===1,
        'valid assertion authorizes exact pending new-subject approval');
    if ($new_subject_id > 0) $subject_ids['approved-new'] = $new_subject_id;

    $stage = 'approve_alias actual admin-post route';
    $valid_alias = $issue($proposal_ids['valid-alias'],'approve_alias',$subject_ids['canonical-A']);
    if (!$valid_alias) throw new RuntimeException('valid alias challenge unavailable');
    $valid_payload = $assertion($valid_alias['id']);
    $alias_response = $route($proposal_ids['valid-alias'],'approve_alias',$subject_ids['canonical-A'],$valid_alias['id'],$valid_payload,$nonce);
    if (is_wp_error($alias_response)) throw new RuntimeException('valid alias loopback route failed');
    $alias_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by,canonical_subject_id FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-alias']),ARRAY_A);
    $alias_event_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-alias'],$president_id,'approved_alias'));
    $assert((int)wp_remote_retrieve_response_code($alias_response)===302 && is_array($alias_row) && ($alias_row['status']??'')==='approved'
        && (int)($alias_row['decided_by']??0)===$president_id && (int)($alias_row['canonical_subject_id']??0)===$subject_ids['canonical-A'] && $alias_event_count===1,
        'valid assertion authorizes only the exact pending alias approval');
    $alias_ids = array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$alias_table} WHERE alias_key=%s AND canonical_subject_id=%d AND created_by=%d",$proposal_keys['valid-alias'],$subject_ids['canonical-A'],$president_id)));

    $stage = 'reject actual admin-post route';
    $valid_reject = $issue($proposal_ids['valid-reject'],'reject',0);
    if (!$valid_reject) throw new RuntimeException('valid rejection challenge unavailable');
    $reject_payload = $assertion($valid_reject['id']);
    $reject_response = $route($proposal_ids['valid-reject'],'reject',0,$valid_reject['id'],$reject_payload,$nonce);
    if (is_wp_error($reject_response)) throw new RuntimeException('valid reject loopback route failed');
    $reject_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-reject']),ARRAY_A);
    $reject_events = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-reject'],$president_id,'rejected'));
    $assert((int)wp_remote_retrieve_response_code($reject_response)===302 && is_array($reject_row) && ($reject_row['status']??'')==='rejected'
        && (int)($reject_row['decided_by']??0)===$president_id && $reject_events===1,'valid assertion authorizes the exact pending rejection');

} catch (Throwable $error) {
    $failures[] = 'positive route fixture stopped during ' . $stage . ' (' . get_class($error) . ')';
} finally {
    foreach ($session_tokens as $token) {
        if ($president_id > 0 && is_string($token) && $token !== '') WP_Session_Tokens::get_instance($president_id)->destroy($token);
    }
    foreach ($challenge_ids as $challenge_id) $wpdb->delete($challenge_table,array('challenge_hash'=>hash('sha256',$challenge_id)),array('%s'));
    foreach ($proposal_ids as $kind=>$proposal_id) {
        $wpdb->delete($proposal_events_table,array('proposal_id'=>(int)$proposal_id),array('%d'));
        $where=array('id'=>(int)$proposal_id); $formats=array('%d');
        if (!empty($proposal_keys[$kind])) {$where['proposal_key']=$proposal_keys[$kind];$formats[]='%s';}
        $wpdb->delete($proposal_table,$where,$formats);
    }
    // Capture every alias created for this fixture's unique proposal keys, including any
    // unexpected write caused by a failing target-mismatch assertion.
    foreach ($proposal_keys as $proposal_key) {
        $owned_aliases = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$alias_table} WHERE alias_key=%s AND created_by=%d", $proposal_key, $president_id));
        foreach ((array) $owned_aliases as $owned_alias_id) $alias_ids[] = (int) $owned_alias_id;
    }
    foreach (array_unique($alias_ids) as $alias_id) $wpdb->delete($alias_table,array('id'=>(int)$alias_id,'created_by'=>$president_id),array('%d','%d'));
    foreach ($subject_ids as $subject_id) $wpdb->delete($subject_table,array('id'=>(int)$subject_id),array('%d'));
    if ($credential_id!=='' && $president_id>0) $wpdb->delete($credential_table,array('credential_id_hash'=>hash('sha256',$credential_id),'user_id'=>$president_id),array('%s','%d'));
    if ($mandate_id>0) {
        $wpdb->delete($mandate_audit_table,array('mandate_id'=>$mandate_id),array('%d'));
        $wpdb->delete($mandate_table,array('id'=>$mandate_id,'user_id'=>$president_id),array('%d','%d'));
    }
    if (is_resource($private_key) || $private_key instanceof OpenSSLAsymmetricKey) openssl_pkey_free($private_key);
    require_once ABSPATH.'wp-admin/includes/user.php';
    foreach (array($teacher_id,$president_id) as $uid) { if ($uid>0 && get_userdata($uid)) wp_delete_user($uid); if ($uid>0) clean_user_cache($uid); }

    wp_cache_delete('zmp_debug_events','options'); wp_cache_delete('alloptions','options');
    $debug_after=get_option('zmp_debug_events',$missing);
    if ($debug_after!==$missing && is_array($debug_after)) {
        $filtered=array();
        foreach ($debug_after as $event) {
            $ctx=is_array($event)&&is_array($event['context']??null)?$event['context']:array();
            $owned=(int)($ctx['proposal_id']??0)>0 && in_array((int)$ctx['proposal_id'],array_values($proposal_ids),true);
            if (is_array($event) && in_array((int)($event['user_id']??0),array($teacher_id,$president_id),true)) $owned=true;
            if (!$owned) $filtered[]=$event;
        }
        if (count($filtered)!==count($debug_after)) {
            if ($filtered) update_option('zmp_debug_events',array_values($filtered),false);
            elseif ($debug_before===$missing) delete_option('zmp_debug_events');
            else update_option('zmp_debug_events',$debug_before,false);
        }
    }
    wp_cache_delete('zmp_debug_capture','options'); wp_cache_delete('alloptions','options');
    if (serialize(get_option('zmp_debug_capture',$missing))!==serialize($capture_before)) {
        if ($capture_before===$missing) delete_option('zmp_debug_capture'); else update_option('zmp_debug_capture',$capture_before,false);
    }
    wp_cache_delete('zmp_debug_events','options'); wp_cache_delete('alloptions','options');
    if (!hash_equals($debug_before_hash,hash('sha256',serialize(get_option('zmp_debug_events',$missing))))) $failures[]='debug events did not restore to exact pre-fixture state';
    wp_set_current_user($original_user); $_POST=$post_before; $_REQUEST=$request_before; $_COOKIE=$cookie_before;
    foreach ($server_before as $key=>$saved) {if($saved[0])$_SERVER[$key]=$saved[1];else unset($_SERVER[$key]);}
}
$after=array(
    'users'=>$count($users_table),'credentials'=>$count($credential_table),'challenges'=>$count($challenge_table),
    'proposals'=>$count($proposal_table),'proposal_events'=>$count($proposal_events_table),'mandates'=>$count($mandate_table),
    'mandate_audit'=>$count($mandate_audit_table),'subjects'=>$count($subject_table),'aliases'=>$count($alias_table),
    'credential_fp'=>$credential_fingerprint(),'protected_digest'=>$protected_digest(),
    'roles_hash'=>hash('sha256',serialize(get_option($role_option,array()))),
);
$clean=$after===$before && hash_equals($before['credential_fp'],$after['credential_fp'])
    && hash_equals($before['protected_digest'],$after['protected_digest']) && (int)get_current_user_id()===$original_user;
$checks[]='exact cleanup restores user/credential/challenge/proposal/mandate/catalog/alias/role/debug baseline';
if(!$clean)$failures[]='synthetic baseline or protected credential changed';
if($failures){fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks).'): Task5 positive route verification'."\n");foreach($failures as $f)fwrite(STDERR,'- '.$f."\n");fwrite(STDOUT,'CLEANUP '.($clean?'PASS':'FAIL')."; concurrent replay/interleaving NOT RUN\nPRIVATE MATERIAL/ASSERTIONS/COOKIES: never printed\n");exit(1);}
fwrite(STDOUT,'PASS ('.count($checks).'): Task5 positive route verification; concurrent replay/interleaving NOT RUN'."\nCLEANUP PASS; protected credential unchanged\nPRIVATE MATERIAL/ASSERTIONS/COOKIES: never printed\n");exit(0);





