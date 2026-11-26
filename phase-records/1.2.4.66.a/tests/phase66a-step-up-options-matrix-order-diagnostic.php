<?php
/**
 * One-shot sanitized diagnostic for Task5 step_up_options denial.
 * Never prints nonce, session token, credential ID/record, challenge, or raw error.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store')
    || !class_exists('ZMP_President_Mandate') || !class_exists('ZMP_President_Subject_Approvals')
    || !class_exists('ZMP_Mentor_Subject_Proposals') || !class_exists('ZMP_Mentor_Subjects')) {
    fwrite(STDERR, "BLOCKED: expected `.66.a` APIs unavailable\n"); exit(2);
}
global $wpdb;
$users_table = $wpdb->users;
$credential_table = $wpdb->prefix . 'zmp_passkey_credentials';
$challenge_table = $wpdb->prefix . 'zmp_passkey_challenges';
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$proposal_events_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$mandate_table = ZMP_President_Mandate::table_name();
$mandate_audit_table = ZMP_President_Mandate::audit_table_name();
$subject_table = $wpdb->prefix . 'zmp_subject_catalog';
$alias_table = $wpdb->prefix . 'zmp_subject_aliases';
$role_option = $wpdb->get_blog_prefix() . 'user_roles';
$original_user = (int) get_current_user_id();
$post_before = $_POST; $request_before = $_REQUEST; $cookie_before = $_COOKIE;
$server_keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before = array(); foreach ($server_keys as $key) $server_before[$key] = array_key_exists($key, $_SERVER) ? array(true, $_SERVER[$key]) : array(false, null);
$count = static function($table) use ($wpdb) { return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$credential_fingerprint = static function() use ($wpdb, $credential_table) {
    $rows = $wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash", ARRAY_A);
    return hash('sha256', serialize(is_array($rows) ? $rows : array()));
};
$protected_user = (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$users_table} WHERE user_login=%s", 'zmp66a_admin'));
$protected_digest = static function() use ($wpdb, $credential_table, $protected_user) {
    $row = $protected_user ? $wpdb->get_row($wpdb->prepare(
        "SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} WHERE user_id=%d AND label=%s LIMIT 1",
        $protected_user, 'Synthetic UI review only'
    ), ARRAY_A) : null;
    return is_array($row) ? hash('sha256', serialize($row)) : '';
};
$before = array(
    'users'=>$count($users_table), 'credentials'=>$count($credential_table), 'challenges'=>$count($challenge_table),
    'proposals'=>$count($proposal_table), 'events'=>$count($proposal_events_table), 'mandates'=>$count($mandate_table),
    'mandate_audit'=>$count($mandate_audit_table), 'subjects'=>$count($subject_table), 'aliases'=>$count($alias_table),
    'credential_fp'=>$credential_fingerprint(), 'protected_digest'=>$protected_digest(),
    'roles_hash'=>hash('sha256', serialize(get_option($role_option, array()))),
);
$debug_missing = new stdClass();
$debug_before = get_option('zmp_debug_events', $debug_missing);
$debug_capture_before = get_option('zmp_debug_capture', $debug_missing);
$debug_hash_before = hash('sha256', serialize($debug_before));
$plugins = get_option('active_plugins', array());
$schema_ok = ZMP_Passkey_Store::schema_is_compatible() && ZMP_Mentor_Subject_Proposals::schema_is_compatible();
$baseline_ok = $before['users']===1 && $before['credentials']===1 && $before['challenges']===0
    && $before['proposals']===0 && $before['events']===0 && $before['mandates']===0 && $before['mandate_audit']===0
    && $before['subjects']===0 && $before['aliases']===0 && $protected_user>0 && $before['protected_digest']!=='';
if (!defined('DB_NAME') || DB_NAME!=='zmp66a_isolated' || $wpdb->prefix!=='z66a_'
    || $plugins!==array('zmp-competitions/zmp-competitions.php') || !$schema_ok || !$baseline_ok) {
    fwrite(STDERR, "BLOCKED: strict `.66.a` diagnostic preflight mismatch; no fixture writes\n"); exit(2);
}

$teacher_id = 0; $president_id = 0; $mandate_id = 0; $proposal_id = 0; $proposal_key = '';
$proposal_ids = array(); $proposal_keys = array(); $subject_ids = array(); $alias_ids = array();
$credential_id = ''; $private_key = null; $session_token = ''; $auth_cookie = ''; $logged_cookie = '';
$diagnostic = array(); $result_category = 'NOT_REACHED'; $error_code_category = 'NONE'; $challenge_count_before = 0; $challenge_count_after = 0;
$challenge_metadata_match = false; $challenge_consumed = null; $cleanup_ok = false; $setup_stage = 'actor_setup';
$b64url = static function($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); };
try {
    $suffix = bin2hex(random_bytes(8));
    $teacher = wp_insert_user(array('user_login'=>'zmp66a_opt_diag_teacher_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),
        'user_email'=>'zmp66a-opt-diag-teacher-'.$suffix.'@example.invalid','role'=>'zmp_teacher'));
    if (is_wp_error($teacher) || (int)$teacher<1) throw new RuntimeException('setup');
    $teacher_id = (int)$teacher;
    $president = wp_insert_user(array('user_login'=>'zmp66a_opt_diag_president_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),
        'user_email'=>'zmp66a-opt-diag-president-'.$suffix.'@example.invalid','role'=>'subscriber'));
    if (is_wp_error($president) || (int)$president<1) throw new RuntimeException('setup');
    $president_id = (int)$president;

    $home = wp_parse_url(home_url('/'));
    if (!is_array($home) || empty($home['scheme']) || empty($home['host'])) throw new RuntimeException('setup');
    $scheme = strtolower((string)$home['scheme']); $host = strtolower((string)$home['host']);
    $port = isset($home['port']) ? (int)$home['port'] : ($scheme==='https'?443:80);
    $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']=$scheme; $_SERVER['SERVER_PORT']=(string)$port;
    if ($scheme==='https') $_SERVER['HTTPS']='on'; else unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN']=$scheme.'://'.$host.($port!==($scheme==='https'?443:80)?':'.$port:'');
    $_SERVER['HTTP_REFERER']=$_SERVER['HTTP_ORIGIN'].'/'; $_SERVER['REMOTE_ADDR']='127.0.0.1'; $_SERVER['REQUEST_METHOD']='POST';
    $set_request=static function() use ($scheme,$host,$port) {
        $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']=$scheme; $_SERVER['SERVER_PORT']=(string)$port;
        if ($scheme==='https') $_SERVER['HTTPS']='on'; else unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_ORIGIN']=$scheme.'://'.$host.($port!==($scheme==='https'?443:80)?':'.$port:'');
        $_SERVER['HTTP_REFERER']=$_SERVER['HTTP_ORIGIN'].'/'; $_SERVER['REMOTE_ADDR']='127.0.0.1'; $_SERVER['REQUEST_METHOD']='POST';
    };

    $admin = get_user_by('login','zmp66a_admin');
    if (!$admin instanceof WP_User || !user_can($admin,'manage_options')) throw new RuntimeException('setup');
    wp_set_current_user($admin->ID);
    $today=current_time('Y-m-d'); $end=ZMP_President_Mandate::term_end_date($today);
    $mandate=ZMP_President_Mandate::appoint($president_id,$today,$end,'Synthetic options diagnostic appointment');
    if (is_wp_error($mandate) || !is_int($mandate) || $mandate<1) throw new RuntimeException('setup');
    $mandate_id=$mandate; clean_user_cache($president_id);

    wp_set_current_user($president_id);
    $auth_cb=static function($cookie) use (&$auth_cookie) { if (is_string($cookie)) $auth_cookie=$cookie; };
    $logged_cb=static function($cookie) use (&$logged_cookie) { if (is_string($cookie)) $logged_cookie=$cookie; };
    add_action('set_auth_cookie',$auth_cb,10,6); add_action('set_logged_in_cookie',$logged_cb,10,6);
    wp_set_auth_cookie($president_id,false,false);
    remove_action('set_auth_cookie',$auth_cb,10); remove_action('set_logged_in_cookie',$logged_cb,10);
    $parsed=$logged_cookie!==''?wp_parse_auth_cookie($logged_cookie,'logged_in'):false;
    $session_token=is_array($parsed)?(string)($parsed['token']??''):'';
    if ($session_token==='') throw new RuntimeException('setup');
    $_COOKIE[AUTH_COOKIE]=$auth_cookie; $_COOKIE[LOGGED_IN_COOKIE]=$logged_cookie;

    $user_handle=random_bytes(32);
    update_user_meta($president_id,ZMP_Passkeys::USER_HANDLE_META,$b64url($user_handle));
    $private_key=openssl_pkey_new(array('private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1'));
    $details=$private_key?openssl_pkey_get_details($private_key):false;
    if (!is_array($details) || !isset($details['ec']['x'],$details['ec']['y']) || strlen($details['ec']['x'])!==32 || strlen($details['ec']['y'])!==32) throw new RuntimeException('setup');
    $cose=CBOR\MapObject::create();
    $cose->add(CBOR\UnsignedIntegerObject::create(1),CBOR\UnsignedIntegerObject::create(2));
    $cose->add(CBOR\UnsignedIntegerObject::create(3),CBOR\NegativeIntegerObject::create(-7));
    $cose->add(CBOR\NegativeIntegerObject::create(-1),CBOR\UnsignedIntegerObject::create(1));
    $cose->add(CBOR\NegativeIntegerObject::create(-2),CBOR\ByteStringObject::create($details['ec']['x']));
    $cose->add(CBOR\NegativeIntegerObject::create(-3),CBOR\ByteStringObject::create($details['ec']['y']));
    $credential_id=random_bytes(32);
    $record=Webauthn\CredentialRecord::create($credential_id,'public-key',array(),'none',Webauthn\TrustPath\EmptyTrustPath::create(),
        Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000'),(new CBOR\Encoder())->encode($cose),$user_handle,0);
    if (!ZMP_Passkey_Store::save_credential($president_id,$record,'Synthetic options diagnostic key')) throw new RuntimeException('setup');

    $proposal_name=array();
    foreach (array('valid-new','valid-alias','valid-reject','alias-mismatch','bad-nonce','nonpending','negative-matrix') as $kind) {
        $proposal_name[$kind]='Synthetic Task5 matrix '.$kind.' '.$suffix;
        wp_set_current_user($teacher_id);
        $proposal=ZMP_Mentor_Subject_Proposals::submit($teacher_id,$proposal_name[$kind]);
        if (is_wp_error($proposal) || !is_int($proposal) || $proposal<1) throw new RuntimeException('setup');
        $proposal_ids[$kind]=$proposal;
        $row=$wpdb->get_row($wpdb->prepare("SELECT proposal_key,status FROM {$proposal_table} WHERE id=%d",$proposal),ARRAY_A);
        if (!is_array($row) || ($row['status']??'')!=='pending') throw new RuntimeException('setup');
        $proposal_keys[$kind]=(string)$row['proposal_key'];
        if ($kind==='valid-new') { $proposal_id=$proposal; $proposal_key=$proposal_keys[$kind]; }
    }
    if ($proposal_id<1 || $proposal_key==='') throw new RuntimeException('setup');

    // Match the matrix's extra catalog setup and actor/request ordering before its first options call.
    wp_set_current_user($admin->ID);
    foreach (array('canonical-A','canonical-B') as $kind) {
        $subject_name='Synthetic Task5 matrix '.$kind.' '.$suffix;
        wp_set_current_user($president_id);
        $subject_id=ZMP_Mentor_Subjects::create_approved_subject_for_president($subject_name,$president_id);
        if (is_wp_error($subject_id) || !is_int($subject_id) || $subject_id<1) throw new RuntimeException('setup');
        $subject_ids[$kind]=$subject_id;
    }

    wp_set_current_user($president_id);
    $set_request();
    $route_nonce=wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if (!wp_verify_nonce($route_nonce,ZMP_President_Subject_Approvals::NONCE)) throw new RuntimeException('setup');
    $configured=$home['scheme'].'://'.$home['host'].($port!==($scheme==='https'?443:80)?':'.$port:'');
    $origin_match=isset($_SERVER['HTTP_ORIGIN']) && hash_equals($configured,(string)$_SERVER['HTTP_ORIGIN']);
    $session_valid=WP_Session_Tokens::get_instance($president_id)->verify($session_token);
    $credential_rows=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND credential_id_hash=%s",$president_id,hash('sha256',$credential_id)));
    $credential_list=ZMP_Passkey_Store::list_credentials_for_user($president_id);
    $diagnostic=array(
        'exact_president_role'=>(get_userdata($president_id)->roles===array('zmp_president')),
        'current_capability'=>(bool)current_user_can('view_zmp_president_dashboard'),
        'active_mandate'=>(bool)ZMP_President_Mandate::user_has_active_mandate($president_id),
        'can_review'=>(bool)ZMP_President_Subject_Approvals::can_review(),
        'current_user_matches'=>((int)get_current_user_id()===$president_id),
        'session_token_valid'=>(bool)$session_valid,
        'route_nonce_valid'=>(bool)wp_verify_nonce($route_nonce,ZMP_President_Subject_Approvals::NONCE),
        'secure_request_context'=>(bool)ZMP_Passkeys::is_secure_request_context(),
        'configured_origin_matches_request'=>$origin_match,
        'schema_and_library_available'=>(bool)(ZMP_Passkey_Store::schema_is_compatible() && ZMP_Mentor_Subject_Proposals::schema_is_compatible()
            && class_exists('Webauthn\\CredentialRecord') && class_exists('Webauthn\\PublicKeyCredentialRequestOptions')
            && class_exists('Webauthn\\Denormalizer\\WebauthnSerializerFactory') && class_exists('ParagonIE\\ConstantTime\\Base64UrlSafe')),
        'exact_synthetic_credential_row'=>(bool)($credential_rows===1),
        'exact_synthetic_credential_list_count'=>(bool)(is_array($credential_list) && count($credential_list)===1),
    );

    $scope=array('user_id'=>$president_id,'ceremony'=>'step_up','action'=>'president_subject_decision','resource_id'=>$proposal_id,'decision'=>'approve_new');
    $challenge_count_before=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$challenge_table} WHERE user_id=%d AND ceremony=%s AND action=%s AND resource_id=%d AND decision=%s",
        $president_id,'step_up','president_subject_decision',$proposal_id,'approve_new'));
    $result=ZMP_Passkeys::step_up_options($president_id,'president_subject_decision',$proposal_id,'approve_new',0,$route_nonce);
    $result_category=is_array($result)?'OPTIONS_ARRAY':(is_wp_error($result)?'WP_ERROR':'OTHER');
    if (is_wp_error($result)) {
        $allowed_error_codes=array(
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
        $candidate_error_code=$result->get_error_code();
        $error_code_category=is_string($candidate_error_code) && in_array($candidate_error_code,$allowed_error_codes,true)
            ? $candidate_error_code : 'UNEXPECTED_CODE';
    }
    $after_rows=$wpdb->get_results($wpdb->prepare("SELECT ceremony,action,resource_id,decision,context_hash,consumed_at FROM {$challenge_table} WHERE user_id=%d AND ceremony=%s AND action=%s AND resource_id=%d AND decision=%s",
        $president_id,'step_up','president_subject_decision',$proposal_id,'approve_new'),ARRAY_A);
    $challenge_count_after=is_array($after_rows)?count($after_rows):0;
    if ($challenge_count_after===1 && is_array($after_rows[0])) {
        $row=$after_rows[0];
        $challenge_metadata_match=$row['ceremony']==='step_up' && $row['action']==='president_subject_decision'
            && (int)$row['resource_id']===$proposal_id && $row['decision']==='approve_new'
            && is_string($row['context_hash']) && preg_match('/\A[a-f0-9]{64}\z/',$row['context_hash'])===1;
        $challenge_consumed=$row['consumed_at']!==null;
    }
} catch (Throwable $error) {
    $result_category='SETUP_OR_CALL_EXCEPTION';
}

if ($session_token!=='' && $president_id>0) WP_Session_Tokens::get_instance($president_id)->destroy($session_token);
if ($president_id>0 && $proposal_id>0) {
    $wpdb->delete($challenge_table,array('user_id'=>$president_id,'ceremony'=>'step_up','action'=>'president_subject_decision','resource_id'=>$proposal_id,'decision'=>'approve_new'),array('%d','%s','%s','%d','%s'));
}
foreach ($proposal_ids as $kind=>$owned_proposal_id) {
    $wpdb->delete($proposal_events_table,array('proposal_id'=>(int)$owned_proposal_id),array('%d'));
    $where=array('id'=>(int)$owned_proposal_id); $formats=array('%d');
    if (!empty($proposal_keys[$kind])) {$where['proposal_key']=$proposal_keys[$kind];$formats[]='%s';}
    $wpdb->delete($proposal_table,$where,$formats);
}
foreach ($proposal_keys as $owned_key) {
    $rows=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$alias_table} WHERE alias_key=%s AND created_by=%d",$owned_key,$president_id));
    foreach ((array)$rows as $alias_id) $alias_ids[]=(int)$alias_id;
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
foreach (array($teacher_id,$president_id) as $uid) if ($uid>0 && get_userdata($uid)) wp_delete_user($uid);
wp_cache_delete('zmp_debug_events','options'); wp_cache_delete('alloptions','options');
$debug_after=get_option('zmp_debug_events',$debug_missing);
if (is_array($debug_after)) {
    $filtered=array();
    foreach ($debug_after as $event) {
        $ctx=is_array($event)&&is_array($event['context']??null)?$event['context']:array();
        $owned=in_array((int)($ctx['proposal_id']??0),array_values($proposal_ids),true)
            || in_array((int)($event['user_id']??0),array($teacher_id,$president_id),true);
        if (!$owned) $filtered[]=$event;
    }
    if (count($filtered)!==count($debug_after)) {
        if ($filtered) update_option('zmp_debug_events',array_values($filtered),false);
        elseif ($debug_before===$debug_missing) delete_option('zmp_debug_events');
        else update_option('zmp_debug_events',$debug_before,false);
    }
}
wp_cache_delete('zmp_debug_capture','options'); wp_cache_delete('alloptions','options');
if (serialize(get_option('zmp_debug_capture',$debug_missing))!==serialize($debug_capture_before)) {
    if ($debug_capture_before===$debug_missing) delete_option('zmp_debug_capture'); else update_option('zmp_debug_capture',$debug_capture_before,false);
}
wp_cache_delete('zmp_debug_events','options'); wp_cache_delete('alloptions','options');
wp_set_current_user($original_user); $_POST=$post_before; $_REQUEST=$request_before; $_COOKIE=$cookie_before;
foreach ($server_before as $key=>$saved) { if ($saved[0]) $_SERVER[$key]=$saved[1]; else unset($_SERVER[$key]); }
$after=array(
    'users'=>$count($users_table),'credentials'=>$count($credential_table),'challenges'=>$count($challenge_table),
    'proposals'=>$count($proposal_table),'events'=>$count($proposal_events_table),'mandates'=>$count($mandate_table),
    'mandate_audit'=>$count($mandate_audit_table),'subjects'=>$count($subject_table),'aliases'=>$count($alias_table),
    'credential_fp'=>$credential_fingerprint(),'protected_digest'=>$protected_digest(),
    'roles_hash'=>hash('sha256',serialize(get_option($role_option,array()))),
);
$cleanup_ok=$after===$before && hash_equals($before['credential_fp'],$after['credential_fp'])
    && hash_equals($before['protected_digest'],$after['protected_digest']) && hash_equals($debug_hash_before,hash('sha256',serialize(get_option('zmp_debug_events',$debug_missing))))
    && serialize(get_option('zmp_debug_capture',$debug_missing))===serialize($debug_capture_before)
    && (int)get_current_user_id()===$original_user;
$stage='';
if ($result_category==='WP_ERROR') {
    if ($challenge_count_after===0) $stage='DENIED_WITHOUT_SCOPED_CHALLENGE';
    elseif ($challenge_count_after===1 && $challenge_metadata_match && $challenge_consumed===true) $stage='CHALLENGE_CREATED_THEN_BURNED';
    elseif ($challenge_count_after===1 && $challenge_metadata_match && $challenge_consumed===false) $stage='CHALLENGE_CREATED_UNCONSUMED';
    else $stage='CHALLENGE_METADATA_UNEXPECTED';
} elseif ($result_category==='OPTIONS_ARRAY') $stage='OPTIONS_RETURNED';
else $stage='CALL_DID_NOT_RETURN_NORMAL_RESULT';
echo 'PRECONDITIONS '.wp_json_encode($diagnostic,JSON_UNESCAPED_SLASHES)."\n";
echo 'RESULT '.$result_category.'; error_code_category='.$error_code_category.'; scoped_challenges_before='.$challenge_count_before.'; scoped_challenges_after='.$challenge_count_after
    .'; scoped_metadata_match='.($challenge_metadata_match?'true':'false').'; scoped_consumed='.($challenge_consumed===null?'unknown':($challenge_consumed?'true':'false')).'; stage='.$stage."\n";
echo 'CLEANUP '.($cleanup_ok?'PASS':'FAIL')."; baseline_restored=".($cleanup_ok?'true':'false')."\n";
echo "SECRETS_AND_RAW_ERRORS: NEVER_PRINTED\n";
exit($cleanup_ok?0:1);
