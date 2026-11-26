<?php
/**
 * Task 5 behavior matrix for assertion-bound active-President subject decisions.
 * Synthetic-only; private key, assertion payloads, cookies and nonces remain in memory.
 * No test-runner behavior is under test. Run only on the owner-authorized local clone
 * after verifying the exact database/site identity below; all fixture-owned rows are cleaned.
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
$route_outcomes = array();
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
$site_identity = defined('DB_NAME') && DB_NAME === 'zmp_debug_prodclone_20261125'
    && $wpdb->prefix === 'wp_yqqdt5_' && home_url('/') === 'http://localhost:8111/';
$plugin_active = is_array($plugins) && in_array('zmp-competitions/zmp-competitions.php', $plugins, true);
$preflight = $site_identity && $plugin_active && $schema_ok;
if (!$preflight) { fwrite(STDERR, "BLOCKED: exact authorized local clone/schema/plugin preflight failed; no test writes\n"); exit(2); }

$missing = new stdClass();
$debug_before = get_option('zmp_debug_events', $missing);
$capture_before = get_option('zmp_debug_capture', $missing);
$debug_before_hash = hash('sha256', serialize($debug_before));
$labels = array(
    'valid new-subject approval', 'valid alias approval', 'valid rejection', 'alias canonical-target mismatch', 'bad signature',
    'client origin mismatch', 'RP ID hash mismatch', 'user verification missing', 'challenge expiry',
    'successful one-use then replay', 'changed resource', 'changed decision', 'changed user',
    'changed session', 'lost President role', 'inactive mandate', 'invalid route nonce',
    'nonpending resource/no-write',
);

$b64url = static function($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); };
$site = wp_parse_url(home_url('/'));
$scheme = strtolower((string) ($site['scheme'] ?? 'http'));
$host = strtolower((string) ($site['host'] ?? '127.0.0.1'));
$port = isset($site['port']) ? (int) $site['port'] : ($scheme === 'https' ? 443 : 80);
$origin = $scheme . '://' . $host . ($port !== ($scheme === 'https' ? 443 : 80) ? ':' . $port : '');
$origin_parts = wp_parse_url($origin); $admin_route_parts = wp_parse_url(admin_url('admin-post.php'));
$origin_scheme = is_array($origin_parts) ? strtolower((string) ($origin_parts['scheme'] ?? '')) : '';
$admin_route_scheme = is_array($admin_route_parts) ? strtolower((string) ($admin_route_parts['scheme'] ?? '')) : '';
$origin_effective_port = is_array($origin_parts) ? (int) ($origin_parts['port'] ?? ($origin_scheme === 'https' ? 443 : 80)) : 0;
$admin_route_effective_port = is_array($admin_route_parts) ? (int) ($admin_route_parts['port'] ?? ($admin_route_scheme === 'https' ? 443 : 80)) : 0;
$origin_matches_admin_route = is_array($origin_parts) && is_array($admin_route_parts)
    && $origin_scheme !== '' && $origin_scheme === $admin_route_scheme
    && strtolower((string) ($origin_parts['host'] ?? '')) === strtolower((string) ($admin_route_parts['host'] ?? ''))
    && $origin_effective_port > 0 && $origin_effective_port === $admin_route_effective_port
    && in_array(strtolower((string) ($admin_route_parts['host'] ?? '')), array('localhost', '127.0.0.1'), true)
    && $admin_route_effective_port === $port
    && ($admin_route_parts['path'] ?? '') === '/wp-admin/admin-post.php';
if (!$origin_matches_admin_route) { fwrite(STDERR, "BLOCKED: configured Origin and admin-post URL scheme/host/port do not match; no fixture writes\n"); exit(2); }
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
$issue = static function($proposal_id, $decision, $canonical_id = 0) use (&$challenge_ids, &$route_nonce, &$president_id, $assert, $host) {
    $result = ZMP_Passkeys::step_up_options($president_id, 'president_subject_decision', (int) $proposal_id, $decision, (int) $canonical_id, $route_nonce);
    if (is_wp_error($result) || !is_array($result)) { $assert(false, 'step-up options issued for ' . $decision); return null; }
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
$verify = static function($challenge, $proposal_id, $decision, $canonical_id, $payload, $user_id = null, $verify_nonce = null) use (&$president_id, &$route_nonce) {
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
$alias_catalog_snapshot = static function($alias_key, $target_ids) use ($wpdb, $alias_table, $subject_table) {
    $target_ids = array_values(array_unique(array_map('intval', (array) $target_ids)));
    if (!is_string($alias_key) || $alias_key === '' || count($target_ids) !== 2 || $target_ids[0] < 1 || $target_ids[1] < 1) return null;
    $aliases = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$alias_table} WHERE alias_key=%s ORDER BY id ASC", $alias_key
    ), ARRAY_A);
    $catalog = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$subject_table} WHERE id IN (%d,%d) ORDER BY id ASC", $target_ids[0], $target_ids[1]
    ), ARRAY_A);
    if (!is_array($aliases) || !is_array($catalog)) return null;
    return hash('sha256', serialize(array($aliases, $catalog)));
};
$route = static function($proposal_id, $decision, $canonical_id, $challenge_id, $payload, $nonce) use (&$auth_cookie, &$logged_cookie, $host, $port, $origin) {
    $url = admin_url('admin-post.php');
    $parts = wp_parse_url($url);
    $origin_parts = wp_parse_url($origin);
    $origin_scheme = is_array($origin_parts) ? strtolower((string) ($origin_parts['scheme'] ?? '')) : '';
    $route_scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
    $origin_port = is_array($origin_parts) ? (int) ($origin_parts['port'] ?? ($origin_scheme === 'https' ? 443 : 80)) : 0;
    $route_port = is_array($parts) ? (int) ($parts['port'] ?? ($route_scheme === 'https' ? 443 : 80)) : 0;
    if (!is_array($parts) || !in_array(strtolower((string) ($parts['host'] ?? '')), array('localhost', '127.0.0.1'), true) || strtolower((string) ($parts['host'] ?? '')) !== $host || (int) ($parts['port'] ?? 0) !== $port
        || ($parts['path'] ?? '') !== '/wp-admin/admin-post.php'
        || $origin_scheme === '' || $origin_scheme !== $route_scheme
        || strtolower((string) ($origin_parts['host'] ?? '')) !== strtolower((string) ($parts['host'] ?? ''))
        || $origin_port < 1 || $origin_port !== $route_port) return new WP_Error('non_loopback_route');
    return wp_remote_post($url, array(
        'timeout'=>12, 'redirection'=>0, 'sslverify'=>false,
        'headers'=>array(
            'Cookie'=>AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_cookie,
            'Origin'=>$origin,
        ),
        'body'=>array(
            'action'=>'zmp_president_subject_decision', '_wpnonce'=>$nonce, 'proposal_id'=>(string) $proposal_id,
            'decision'=>$decision, 'canonical_subject_id'=>(string) $canonical_id,
            'step_up_challenge_id'=>$challenge_id, 'step_up_credential'=>$payload,
        ),
    ));
};
$safe_route_outcome = static function($response) {
    if (is_wp_error($response)) return array('http_status'=>0,'location_status'=>'TRANSPORT_ERROR','decision_status'=>'TRANSPORT_ERROR');
    $http_status = (int) wp_remote_retrieve_response_code($response);
    $location = wp_remote_retrieve_header($response, 'location');
    if (!is_string($location) || $location === '') return array('http_status'=>$http_status,'location_status'=>'NO_LOCATION','decision_status'=>'NO_REDIRECT_STATUS');
    $parts = wp_parse_url($location);
    $query = array();
    if (is_array($parts) && isset($parts['query'])) wp_parse_str((string) $parts['query'], $query);
    $candidate = is_scalar($query['zmp_subject_decision_status'] ?? null)
        ? (string) $query['zmp_subject_decision_status'] : '';
    $allowed = array('approved','rejected','failed');
    $decision_status = in_array($candidate, $allowed, true) ? $candidate : 'UNEXPECTED_STATUS';
    return array('http_status'=>$http_status,'location_status'=>$decision_status,'decision_status'=>$decision_status);
};
$safe_proposal_state = static function($row, $event_count, $actor_id = null) {
    $candidate_status = is_array($row) && is_scalar($row['status'] ?? null) ? (string) $row['status'] : '';
    $allowed_statuses = array('pending','approved','rejected');
    $status = in_array($candidate_status,$allowed_statuses,true) ? $candidate_status : 'UNEXPECTED_STATUS';
    $state = array('proposal_status'=>$status,'event_count'=>max(0,(int)$event_count));
    if ($actor_id !== null) $state['actor_matches_expected'] = is_array($row) && (int)($row['decided_by'] ?? 0)===(int)$actor_id;
    return $state;
};

try {
    $stage = 'synthetic actors and credential';
    $suffix = bin2hex(random_bytes(8));
    $teacher = wp_insert_user(array('user_login'=>'zmp66a_t5m_teacher_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),'user_email'=>'zmp66a-t5m-teacher-'.$suffix.'@example.invalid','role'=>'subscriber'));
    if (is_wp_error($teacher) || !is_int($teacher) || $teacher < 1) throw new RuntimeException('synthetic teacher creation failed');
    $teacher_id = $teacher; (new WP_User($teacher_id))->add_role('zmp_teacher');
    $president = wp_insert_user(array('user_login'=>'zmp66a_t5m_president_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),'user_email'=>'zmp66a-t5m-president-'.$suffix.'@example.invalid','role'=>'subscriber'));
    if (is_wp_error($president) || !is_int($president) || $president < 1) throw new RuntimeException('synthetic President creation failed');
    $president_id = $president; (new WP_User($president_id))->add_role('zmp_teacher');
    $set_request();
    $admin = get_user_by('login','codex66a_admin_2511');
    if (!$admin instanceof WP_User || !user_can($admin,'manage_options')) $admin = get_user_by('login','zmp_debug_admin_20261125');
    if (!$admin instanceof WP_User || !user_can($admin,'manage_options')) throw new RuntimeException('synthetic admin actor unavailable');
    $today = current_time('Y-m-d'); $end = ZMP_President_Mandate::term_end_date($today);
    wp_set_current_user($admin->ID);
    $mandate = ZMP_President_Mandate::appoint($president_id,$today,$end,'Synthetic Task5 matrix appointment');
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
    if (!ZMP_Passkey_Store::save_credential($president_id,$record,'Synthetic Task5 matrix key')) throw new RuntimeException('synthetic public credential storage failed');

    $stage = 'create fixture proposals and alias subjects';
    $proposal_name = array();
    foreach (array('valid-new','valid-alias','valid-reject','alias-mismatch','bad-nonce','nonpending','negative-matrix') as $kind) {
        $proposal_name[$kind] = 'Synthetic Task5 matrix '.$kind.' '.$suffix;
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
        $subject_name = 'Synthetic Task5 matrix '.$kind.' '.$suffix;
        wp_set_current_user($president_id);
        $subject_id = ZMP_Mentor_Subjects::create_approved_subject_for_president($subject_name,$president_id);
        if (is_wp_error($subject_id) || !is_int($subject_id) || $subject_id<1) throw new RuntimeException('synthetic canonical subject creation failed');
        $subject_ids[$kind] = $subject_id;
    }
    wp_set_current_user($president_id); $set_request();
    $nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!wp_verify_nonce($nonce,ZMP_President_Subject_Approvals::NONCE)) throw new RuntimeException('valid President route nonce unavailable');
    $route_nonce = $nonce;

    $stage = 'valid new-subject approval through actual route';
    $valid_new = $issue($proposal_ids['valid-new'],'approve_new',0);
    if (!$valid_new) throw new RuntimeException('valid new-subject challenge unavailable');
    $new_response = $route($proposal_ids['valid-new'],'approve_new',0,$valid_new['id'],$assertion($valid_new['id']),$nonce);
    $new_route_outcome = $safe_route_outcome($new_response);
    $new_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by,canonical_subject_id FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-new']),ARRAY_A);
    $new_event_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-new'],$president_id,'approved_new'));
    $new_subject_id = is_array($new_row) ? (int)($new_row['canonical_subject_id']??0) : 0;
    $new_subject_owner = $new_subject_id > 0 ? (int)$wpdb->get_var($wpdb->prepare("SELECT created_by FROM {$subject_table} WHERE id=%d",$new_subject_id)) : 0;
    $route_outcomes['approve_new'] = array_merge($new_route_outcome,$safe_proposal_state($new_row,$new_event_count,$president_id),array(
        'canonical_subject_created_by_actor'=>$new_subject_id>0 && $new_subject_owner===$president_id,
    ));
    $assert($new_route_outcome['http_status']===302 && $new_route_outcome['decision_status']==='approved'
        && is_array($new_row) && ($new_row['status']??'')==='approved'
        && (int)($new_row['decided_by']??0)===$president_id && $new_subject_id>0 && $new_subject_owner===$president_id && $new_event_count===1,
        'approve_new route HTTP '.$new_route_outcome['http_status'].' decision='.$new_route_outcome['decision_status'].'; actor/event/catalog checked');
    if ($new_subject_id > 0) $subject_ids['approved-new'] = $new_subject_id;

    $stage = 'valid alias approval through actual route';
    $valid_alias = $issue($proposal_ids['valid-alias'],'approve_alias',$subject_ids['canonical-A']);
    if (!$valid_alias) throw new RuntimeException('valid alias challenge unavailable');
    $valid_payload = $assertion($valid_alias['id']);
    $alias_response = $route($proposal_ids['valid-alias'],'approve_alias',$subject_ids['canonical-A'],$valid_alias['id'],$valid_payload,$nonce);
    $alias_route_outcome = $safe_route_outcome($alias_response);
    $alias_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by,canonical_subject_id FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-alias']),ARRAY_A);
    $alias_event_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-alias'],$president_id,'approved_alias'));
    $route_outcomes['approve_alias'] = array_merge($alias_route_outcome,$safe_proposal_state($alias_row,$alias_event_count,$president_id),array(
        'canonical_target_matches'=>$subject_ids['canonical-A']>0 && (int)($alias_row['canonical_subject_id']??0)===$subject_ids['canonical-A'],
    ));
    $assert($alias_route_outcome['http_status']===302 && $alias_route_outcome['decision_status']==='approved'
        && is_array($alias_row) && ($alias_row['status']??'')==='approved'
        && (int)($alias_row['decided_by']??0)===$president_id && (int)($alias_row['canonical_subject_id']??0)===$subject_ids['canonical-A'] && $alias_event_count===1,
        'approve_alias route HTTP '.$alias_route_outcome['http_status'].' decision='.$alias_route_outcome['decision_status'].'; exact canonical target/actor/event checked');
    $alias_ids = array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$alias_table} WHERE alias_key=%s AND canonical_subject_id=%d AND created_by=%d",$proposal_keys['valid-alias'],$subject_ids['canonical-A'],$president_id)));

    $stage = 'valid rejection through actual route';
    $valid_reject = $issue($proposal_ids['valid-reject'],'reject',0);
    if (!$valid_reject) throw new RuntimeException('valid rejection challenge unavailable');
    $reject_payload = $assertion($valid_reject['id']);
    $reject_response = $route($proposal_ids['valid-reject'],'reject',0,$valid_reject['id'],$reject_payload,$nonce);
    $reject_route_outcome = $safe_route_outcome($reject_response);
    $reject_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by FROM {$proposal_table} WHERE id=%d",$proposal_ids['valid-reject']),ARRAY_A);
    $reject_events = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",$proposal_ids['valid-reject'],$president_id,'rejected'));
    $route_outcomes['reject'] = array_merge($reject_route_outcome,$safe_proposal_state($reject_row,$reject_events,$president_id));
    $assert($reject_route_outcome['http_status']===302 && $reject_route_outcome['decision_status']==='rejected'
        && is_array($reject_row) && ($reject_row['status']??'')==='rejected'
        && (int)($reject_row['decided_by']??0)===$president_id && $reject_events===1,
        'reject route HTTP '.$reject_route_outcome['http_status'].' decision='.$reject_route_outcome['decision_status'].'; actor/event checked');

    $stage = 'canonical alias target mismatch';
    $mismatch = $issue($proposal_ids['alias-mismatch'],'approve_alias',$subject_ids['canonical-A']);
    if (!$mismatch) throw new RuntimeException('alias mismatch challenge unavailable');
    $mismatch_before = $proposal_snapshot($proposal_ids['alias-mismatch']);
    $mismatch_alias_catalog_before = $alias_catalog_snapshot($proposal_keys['alias-mismatch'],array($subject_ids['canonical-A'],$subject_ids['canonical-B']));
    $mismatch_payload = $assertion($mismatch['id']);
    $mismatch_response = $route($proposal_ids['alias-mismatch'],'approve_alias',$subject_ids['canonical-B'],$mismatch['id'],$mismatch_payload,$nonce);
    $mismatch_route_outcome = $safe_route_outcome($mismatch_response);
    $mismatch_row = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by FROM {$proposal_table} WHERE id=%d",$proposal_ids['alias-mismatch']),ARRAY_A);
    $mismatch_event_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d",$proposal_ids['alias-mismatch']));
    $mismatch_unchanged = hash_equals($mismatch_before,$proposal_snapshot($proposal_ids['alias-mismatch']));
    $mismatch_alias_catalog_after = $alias_catalog_snapshot($proposal_keys['alias-mismatch'],array($subject_ids['canonical-A'],$subject_ids['canonical-B']));
    $mismatch_alias_catalog_unchanged = is_string($mismatch_alias_catalog_before) && is_string($mismatch_alias_catalog_after)
        && hash_equals($mismatch_alias_catalog_before,$mismatch_alias_catalog_after);
    $route_outcomes['alias_target_mismatch'] = array_merge($mismatch_route_outcome,$safe_proposal_state($mismatch_row,$mismatch_event_count,$president_id),array(
        'proposal_event_snapshot_unchanged'=>$mismatch_unchanged,
        'fixture_alias_catalog_snapshot_unchanged'=>$mismatch_alias_catalog_unchanged,
    ));
    $assert($mismatch_route_outcome['http_status']===302 && $mismatch_route_outcome['decision_status']==='failed'
        && $mismatch_unchanged && $mismatch_alias_catalog_unchanged,
        'alias target mismatch route HTTP '.$mismatch_route_outcome['http_status'].' decision='.$mismatch_route_outcome['decision_status'].'; proposal/event and fixture alias/catalog snapshots unchanged');

    $stage = 'negative assertion/context matrix';
    $negative = array(
        array('bad signature','reject',0,'bad_signature'),
        array('client origin mismatch','reject',0,'bad_origin'),
        array('RP ID mismatch','reject',0,'bad_rp'),
        array('user verification missing','reject',0,'missing_uv'),
    );
    foreach ($negative as $case) {
        $issued = $issue($proposal_ids['negative-matrix'],$case[1],$case[2]);
        if (!$issued) throw new RuntimeException('negative-matrix challenge unavailable');
        $payload = $case[3]==='bad_origin' ? $assertion($issued['id'],'valid','http://wrong.invalid') : $assertion($issued['id'],$case[3]);
        $snapshot = $proposal_snapshot($proposal_ids['negative-matrix']);
        $result = $verify($issued['id'],$proposal_ids['negative-matrix'],$case[1],$case[2],$payload);
        $assert(is_wp_error($result) && hash_equals($snapshot,$proposal_snapshot($proposal_ids['negative-matrix'])),$case[0].' is denied without proposal/event write');
    }

    foreach (array(array('changed resource',$proposal_ids['negative-matrix']+100,'reject',0),array('changed decision',$proposal_ids['negative-matrix'],'approve_alias',$subject_ids['canonical-A'])) as $scope_case) {
        $issued = $issue($proposal_ids['negative-matrix'],'reject',0);
        if (!$issued) throw new RuntimeException('scope challenge unavailable');
        $payload = $assertion($issued['id']); $snapshot = $proposal_snapshot($proposal_ids['negative-matrix']);
        $result = $verify($issued['id'],$scope_case[1],$scope_case[2],$scope_case[3],$payload);
        $assert(is_wp_error($result) && hash_equals($snapshot,$proposal_snapshot($proposal_ids['negative-matrix'])),$scope_case[0].' is denied without write');
    }

    $stage = 'expiry and successful replay matrix';
    $expired = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$expired) throw new RuntimeException('expiry challenge unavailable');
    $wpdb->update($challenge_table,array('expires_at'=>gmdate('Y-m-d H:i:s',time()-60)),array('challenge_hash'=>hash('sha256',$expired['id'])),array('%s'),array('%s'));
    $expired_result = $verify($expired['id'],$proposal_ids['negative-matrix'],'reject',0,$assertion($expired['id']));
    $assert(is_wp_error($expired_result),'expired step-up challenge is denied');
    $replay = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$replay) throw new RuntimeException('replay challenge unavailable');
    $replay_payload = $assertion($replay['id']);
    $first_verify = $verify($replay['id'],$proposal_ids['negative-matrix'],'reject',0,$replay_payload);
    $second_verify = $verify($replay['id'],$proposal_ids['negative-matrix'],'reject',0,$replay_payload);
    $assert($first_verify===true && is_wp_error($second_verify),'valid proof is one-use and replay is denied');

    $stage = 'changed user/session and lost role/mandate';
    $binding = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$binding) throw new RuntimeException('identity binding challenge unavailable');
    $binding_payload = $assertion($binding['id']); $snapshot = $proposal_snapshot($proposal_ids['negative-matrix']);
    wp_set_current_user($teacher_id);
    $wrong_user = $verify($binding['id'],$proposal_ids['negative-matrix'],'reject',0,$binding_payload,$teacher_id);
    $assert(is_wp_error($wrong_user) && hash_equals($snapshot,$proposal_snapshot($proposal_ids['negative-matrix'])),'changed user cannot consume President step-up');
    $new_session = $new_cookie_session($president_id);
    $new_session_nonce = wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!wp_verify_nonce($new_session_nonce,ZMP_President_Subject_Approvals::NONCE)) throw new RuntimeException('fresh-session President route nonce unavailable');
    $route_nonce = $new_session_nonce;
    $session_binding = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$session_binding) throw new RuntimeException('changed-session challenge unavailable');
    $session_payload = $assertion($session_binding['id']);
    $auth_cookie = $original_auth_cookie; $logged_cookie = $original_logged_cookie;
    $_COOKIE[AUTH_COOKIE] = $auth_cookie; $_COOKIE[LOGGED_IN_COOKIE] = $logged_cookie;
    wp_set_current_user($president_id); $route_nonce = $nonce;
    if (!wp_verify_nonce($route_nonce,ZMP_President_Subject_Approvals::NONCE)) throw new RuntimeException('restored-session President route nonce unavailable');
    $new_session_result = $verify($session_binding['id'],$proposal_ids['negative-matrix'],'reject',0,$session_payload);
    $assert($new_session!=='' && is_wp_error($new_session_result),'fresh-session challenge rejected under restored original session and valid original nonce (combined session/nonce binding)');
    // Issue a fresh challenge for each live-role/mandate check; no prior negative may consume it.
    wp_set_current_user($president_id);
    $role_binding = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$role_binding) throw new RuntimeException('lost-role challenge unavailable');
    $role_payload = $assertion($role_binding['id']);
    $role_before = $proposal_snapshot($proposal_ids['negative-matrix']);
    $president_user = get_user_by('id',$president_id); $president_user->remove_role('zmp_president'); clean_user_cache($president_id); wp_set_current_user($president_id);
    $lost_role = $route($proposal_ids['negative-matrix'],'reject',0,$role_binding['id'],$role_payload,$nonce);
    $assert(!is_wp_error($lost_role) && (int)wp_remote_retrieve_response_code($lost_role)===403
        && hash_equals($role_before,$proposal_snapshot($proposal_ids['negative-matrix'])),'lost President role denies route with no proposal/event write');
    $president_user = get_user_by('id',$president_id); $president_user->add_role('zmp_president'); clean_user_cache($president_id); wp_set_current_user($president_id);
    $mandate_binding = $issue($proposal_ids['negative-matrix'],'reject',0);
    if (!$mandate_binding) throw new RuntimeException('inactive-mandate challenge unavailable');
    $mandate_payload = $assertion($mandate_binding['id']);
    $mandate_before = $proposal_snapshot($proposal_ids['negative-matrix']);
    $wpdb->update($mandate_table,array('status'=>'suspended'),array('id'=>$mandate_id,'user_id'=>$president_id),array('%s'),array('%d','%d'));
    $lost_mandate = $route($proposal_ids['negative-matrix'],'reject',0,$mandate_binding['id'],$mandate_payload,$nonce);
    $assert(!is_wp_error($lost_mandate) && (int)wp_remote_retrieve_response_code($lost_mandate)===403
        && hash_equals($mandate_before,$proposal_snapshot($proposal_ids['negative-matrix'])),'inactive mandate denies route with no proposal/event write');
    $wpdb->update($mandate_table,array('status'=>'active'),array('id'=>$mandate_id,'user_id'=>$president_id),array('%s'),array('%d','%d'));
    clean_user_cache($president_id); wp_set_current_user($president_id);

    $stage = 'invalid route nonce and nonpending no-write';
    $bad_nonce = $issue($proposal_ids['bad-nonce'],'reject',0);
    if (!$bad_nonce) throw new RuntimeException('bad nonce challenge unavailable');
    $bad_nonce_before = $proposal_snapshot($proposal_ids['bad-nonce']);
    $bad_nonce_response = $route($proposal_ids['bad-nonce'],'reject',0,$bad_nonce['id'],$assertion($bad_nonce['id']),'invalid-nonce');
    $assert(!is_wp_error($bad_nonce_response) && (int)wp_remote_retrieve_response_code($bad_nonce_response)!==302
        && hash_equals($bad_nonce_before,$proposal_snapshot($proposal_ids['bad-nonce'])),'invalid route nonce prevents decision/audit write');

    $nonpending = $issue($proposal_ids['nonpending'],'reject',0);
    if (!$nonpending) throw new RuntimeException('nonpending challenge unavailable');
    $wpdb->update($proposal_table,array('status'=>'rejected','decided_by'=>$president_id,'decided_at'=>current_time('mysql')),array('id'=>$proposal_ids['nonpending'],'proposal_key'=>$proposal_keys['nonpending']),array('%s','%d','%s'),array('%d','%s'));
    $nonpending_before = $proposal_snapshot($proposal_ids['nonpending']);
    $nonpending_response = $route($proposal_ids['nonpending'],'reject',0,$nonpending['id'],$assertion($nonpending['id']),$nonce);
    $assert(!is_wp_error($nonpending_response) && hash_equals($nonpending_before,$proposal_snapshot($proposal_ids['nonpending'])),'already-decided proposal remains unchanged with no second event');

    // A deterministic interleaving between assertion consumption and proposal-row locking
    // requires a product synchronization seam. Do not add a test hook or use timing sleeps.
    $checks[] = 'true concurrent race interleaving NOT RUN (no deterministic, approved synchronization seam)';
} catch (Throwable $error) {
    $safe_reasons = array(
        'synthetic teacher creation failed','synthetic President creation failed','synthetic admin actor unavailable',
        'synthetic mandate appointment failed','active synthetic mandate not established','synthetic President session unavailable',
        'in-memory P-256 key unavailable','synthetic public credential storage failed'
    );
    $reason = $error instanceof RuntimeException && in_array($error->getMessage(), $safe_reasons, true) ? ': ' . $error->getMessage() : '';
    $failures[] = 'matrix fixture stopped during ' . $stage . ' (' . get_class($error) . ')' . $reason;
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
if(!$clean){$changed=array();foreach($before as $key=>$value){if(($after[$key]??null)!==$value)$changed[]=$key;} $failures[]='synthetic baseline or protected credential changed (fields: '.implode(',',$changed).')';}
fwrite(STDOUT,'SAFE_ROUTE_OUTCOMES '.wp_json_encode($route_outcomes,JSON_UNESCAPED_SLASHES).PHP_EOL);if($failures){fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks).'): Task5 step-up behavior matrix'."\n");foreach($failures as $f)fwrite(STDERR,'- '.$f."\n");fwrite(STDOUT,'CLEANUP '.($clean?'PASS':'FAIL')."; true concurrent race interleaving NOT RUN\nPRIVATE MATERIAL/ASSERTIONS/COOKIES: never printed\n");exit(1);}
fwrite(STDOUT,'PASS ('.count($checks).'): Task5 step-up behavior matrix; true concurrent race interleaving NOT RUN'."\nCLEANUP PASS; protected credential unchanged\nPRIVATE MATERIAL/ASSERTIONS/COOKIES: never printed\n");exit(0);


