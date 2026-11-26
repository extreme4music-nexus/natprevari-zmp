<?php
/**
 * Clone-only diagnostic-v2 concurrent replay of one valid President subject-decision step-up proof.
 * Two WP-CLI workers rendezvous in a test-only query filter immediately before the exact
 * challenge-consume UPDATE, then invoke the real handler. No HTTP/browser/server call.
 * Private synthetic request material is transient and mode 0600; reports expose no proof values.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDOUT, "BLOCKED: WP-CLI only; writes=0\n"); exit(2); }
$expected_site = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site';
$expected_origin = 'http://127.0.0.1:8102';
$expected_db = 'zmp66a_isolated';
$expected_db_host = 'localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock';
$expected_prefix = 'z66a_';
$expected_run_root = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run';
$expected_fixture = '/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic-v2.php';
$expected_plugin = 'zmp-competitions/zmp-competitions.php';
$plugin_dir = WP_PLUGIN_DIR . '/zmp-competitions';
$hashes = array(
    'zmp-competitions.php'=>'e97c178329fcad3662508d7d492c4cfea729a7f5b927cd73a829206dbf03ba69',
    'includes/class-zmp-passkeys.php'=>'acee4133dee295e2bdd401ef22b7cd304626a53f0f09d698027c69e34dcdc2ae',
    'includes/class-zmp-passkey-store.php'=>'f9038de6499b844529118fe98ab8bd32fe30414bd1061bc35650f638308d02db',
    'includes/class-zmp-president-subject-approvals.php'=>'45435cb47d290d736622b71e8c0c5e92416bbf4418e2a0020b23d61ef0b16d88',
    'includes/class-zmp-mentor-subject-proposals.php'=>'8dd89aa7aafe5a7ab1fc6f83a2b65112865842e9354578bb5a1be18061c89f69',
);
$args_for_eval = isset($args) && is_array($args) ? $args : array();
$worker_mode = (($args_for_eval[0] ?? '') === 'worker');
$worker_id = $worker_mode ? (int) ($args_for_eval[1] ?? 0) : 0;
$state_dir = $worker_mode ? (string) ($args_for_eval[2] ?? '') : '';
$invocation_fixture = $worker_mode ? (string) ($args_for_eval[3] ?? '') : (string) ($args_for_eval[0] ?? '');
$emit = static function ($line) { while (ob_get_level() > 0) { ob_end_clean(); } fwrite(STDOUT, $line . "\n"); };
$site_guard = static function () use ($expected_site,$expected_origin,$expected_db,$expected_db_host,$expected_prefix,$expected_fixture,$expected_plugin,$plugin_dir,$hashes,$invocation_fixture) {
    global $wpdb;
    if (!defined('DB_NAME') || DB_NAME !== $expected_db || !defined('DB_HOST') || DB_HOST !== $expected_db_host
        || $wpdb->prefix !== $expected_prefix || rtrim((string) @realpath(ABSPATH), '/') !== $expected_site
        || rtrim((string) @realpath($plugin_dir), '/') !== $expected_site . '/wp-content/plugins/zmp-competitions'
        || rtrim(home_url('/'), '/') !== $expected_origin || rtrim(site_url('/'), '/') !== $expected_origin
        || rtrim((string) @realpath($invocation_fixture), '/') !== $expected_fixture) { return false; }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $installed = get_plugins();
    if (!is_array($installed) || array_keys($installed) !== array($expected_plugin)) { return false; }
    $active = get_option('active_plugins', array());
    if (!is_array($active) || $active !== array($expected_plugin) || get_option('zmp_passkey_schema_version') !== '3'
        || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) { return false; }
    foreach ($hashes as $rel=>$want) { $file=$plugin_dir.'/'.$rel; $got=is_file($file)?hash_file('sha256',$file):false; if (!is_string($got)||!hash_equals($want,strtolower($got))) { return false; } }
    return true;
};
if (!$site_guard() || !method_exists('ZMP_Passkeys','step_up_options') || !method_exists('ZMP_Passkeys','verify_step_up')
    || !class_exists('ZMP_President_Subject_Approvals') || !class_exists('ZMP_Mentor_Subject_Proposals')
    || !class_exists('ZMP_President_Mandate')) {
    $emit('BLOCKED: exact clone/source/config preflight failed; writes=0'); exit(2);
}
global $wpdb;
$challenge_table=$wpdb->prefix.'zmp_passkey_challenges'; $credential_table=$wpdb->prefix.'zmp_passkey_credentials';
$proposal_table=$wpdb->prefix.'zmp_subject_proposals'; $proposal_events_table=$wpdb->prefix.'zmp_subject_proposal_events';
$mandate_table=ZMP_President_Mandate::table_name(); $mandate_audit_table=ZMP_President_Mandate::audit_table_name();
$subject_table=$wpdb->prefix.'zmp_subject_catalog'; $alias_table=$wpdb->prefix.'zmp_subject_aliases';
$role_option=$wpdb->get_blog_prefix().'user_roles';
$write_json=static function($path,array $value){$tmp=$path.'.tmp';$json=wp_json_encode($value,JSON_UNESCAPED_SLASHES);if(!is_string($json)||@file_put_contents($tmp,$json,LOCK_EX)===false||!@chmod($tmp,0600)||!@rename($tmp,$path)){@unlink($tmp);return false;}return true;};if ($worker_mode) {
    $run=$expected_run_root; $state_real=@realpath($state_dir);
    if (!in_array($worker_id,array(1,2),true)||!is_string($state_real)||$state_real!==$state_dir
        ||dirname($state_real)!==$run||!preg_match('/\Aphase66a-president-route-race-[a-f0-9]{16}\z/D',basename($state_real))
        ||(fileperms($state_real)&0777)!==0700) { $emit('BLOCKED: private worker scope invalid; called=false'); exit(2); }
    $request_path=$state_real.'/request.json';
    if(!is_file($request_path)||(fileperms($request_path)&0777)!==0600){$emit('BLOCKED: private proof envelope unavailable; called=false');exit(2);}
    $request=json_decode((string)@file_get_contents($request_path),true);
    $needed=array('president_id','proposal_id','challenge_id','route_nonce','assertion','auth_cookie','logged_cookie');
    if(!is_array($request)||array_diff($needed,array_keys($request))||!is_int($request['president_id'])||$request['president_id']<1
        ||!is_int($request['proposal_id'])||$request['proposal_id']<1
        ||!is_string($request['challenge_id'])||!preg_match('/\A[A-Za-z0-9_-]{43}\z/D',$request['challenge_id'])
        ||!is_string($request['route_nonce'])||$request['route_nonce']===''||!is_string($request['assertion'])||$request['assertion']===''
        ||!is_string($request['auth_cookie'])||!is_string($request['logged_cookie'])){$emit('BLOCKED: proof envelope invalid; called=false');exit(2);}
    $_SERVER['HTTP_HOST']='127.0.0.1:8102';$_SERVER['REQUEST_SCHEME']='http';$_SERVER['SERVER_PORT']='8102';unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN']=$expected_origin;$_SERVER['HTTP_REFERER']=$expected_origin.'/';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['REQUEST_METHOD']='POST';
    $_SERVER['PHP_SELF']='/wp-admin/admin-post.php';$_SERVER['SCRIPT_NAME']='/wp-admin/admin-post.php';
    $_COOKIE[AUTH_COOKIE]=$request['auth_cookie'];$_COOKIE[LOGGED_IN_COOKIE]=$request['logged_cookie'];wp_set_current_user($request['president_id']);
    $parsed=wp_parse_auth_cookie($request['logged_cookie'],'logged_in');$token=is_array($parsed)?(string)($parsed['token']??''):'';
    if($token===''||!WP_Session_Tokens::get_instance($request['president_id'])->verify($token)
        ||!wp_verify_nonce($request['route_nonce'],ZMP_President_Subject_Approvals::NONCE)
        ||!ZMP_President_Mandate::user_has_active_mandate($request['president_id'])){
        $emit('BLOCKED: synthetic President session/nonce/mandate preflight failed; called=false');exit(2);}
    $challenge_hash=hash('sha256',$request['challenge_id']);
    $row=$wpdb->get_row($wpdb->prepare("SELECT user_id,session_hash,ceremony,action,resource_id,decision,context_hash,peer_hash,consumed_at,expires_at FROM {$challenge_table} WHERE challenge_hash=%s",$challenge_hash),ARRAY_A);
    $session_hash=hash('sha256',$token);
    $context="zmp-passkey-step-up-president-subject-v1\n".$expected_origin."\n127.0.0.1\n".$request['president_id']."\n".$session_hash
        ."\npresident_subject_decision\n".$request['proposal_id']."\nreject\n0\n".hash('sha256',$request['route_nonce']);
    $context_hash=hash('sha256',$context);
    if(!is_array($row)||(int)$row['user_id']!==$request['president_id']||!hash_equals($session_hash,(string)$row['session_hash'])
        ||$row['ceremony']!=='step_up'||$row['action']!=='president_subject_decision'||(int)$row['resource_id']!==$request['proposal_id']
        ||$row['decision']!=='reject'||!hash_equals($context_hash,(string)$row['context_hash'])||$row['peer_hash']!==''
        ||$row['consumed_at']!==null||strtotime((string)$row['expires_at'].' UTC')<=time()){
        $emit('BLOCKED: exact step-up challenge preflight failed; called=false');exit(2);}
    $sql='UPDATE '.$challenge_table.' SET consumed_at = UTC_TIMESTAMP() WHERE challenge_hash = %s'
        .' AND user_id = %d AND session_hash = %s AND ceremony = %s AND action = %s'
        .' AND resource_id = %d AND decision = %s AND context_hash = %s'
        ." AND (ceremony <> 'login' OR peer_hash <> '') AND consumed_at IS NULL"
        .' AND expires_at > UTC_TIMESTAMP()';
    $exact_query=$wpdb->prepare($sql,$challenge_hash,$request['president_id'],$session_hash,'step_up','president_subject_decision',$request['proposal_id'],'reject',$context_hash);
    if(!is_string($exact_query)||$exact_query===''){$emit('BLOCKED: exact consume query unavailable; called=false');exit(2);}
    $hits=0;$ready=false;$route_status='none';$redirect_code=0;
    $ready_path=$state_real.'/ready-'.$worker_id;$release_path=$state_real.'/release';$result_path=$state_real.'/result-'.$worker_id.'.json';
    register_shutdown_function(static function()use(&$hits,&$ready,&$route_status,&$redirect_code,$write_json,$result_path,$worker_id){
        $status=in_array($route_status,array('rejected','failed','approved'),true)?$route_status:'none';
        $write_json($result_path,array('worker'=>$worker_id,'preflight'=>true,'ready'=>$ready,'boundary_hits'=>$hits,
            'called'=>$hits===1,'route_status'=>$status,'redirect_code'=>in_array($redirect_code,array(302,303),true)?$redirect_code:0));
    });
    $query_filter=static function($query)use($exact_query,&$hits,&$ready,$ready_path,$release_path){
        if(!is_string($query)||!hash_equals($exact_query,$query)){return $query;}
        $hits++;if($hits!==1){return $query;}
        if(@file_put_contents($ready_path.'.tmp','ready',LOCK_EX)!==5||!@chmod($ready_path.'.tmp',0600)||!@rename($ready_path.'.tmp',$ready_path)){exit(2);}
        $ready=true;$deadline=microtime(true)+20.0;
        while(!is_file($release_path)&&microtime(true)<$deadline){usleep(1000);}
        if(!is_file($release_path)||trim((string)@file_get_contents($release_path))!=='go'){exit(2);}
        return $query;
    };
    $redirect_filter=static function($location,$status=302)use(&$route_status,&$redirect_code){
        $parts=wp_parse_url((string)$location);$query=array();if(is_array($parts)&&isset($parts['query'])){parse_str((string)$parts['query'],$query);}
        $v=is_scalar($query['zmp_subject_decision_status']??null)?sanitize_key((string)$query['zmp_subject_decision_status']):'';
        $route_status=in_array($v,array('rejected','failed','approved'),true)?$v:'none';$redirect_code=(int)$status;return $location;
    };
    add_filter('query',$query_filter,PHP_INT_MAX);add_filter('wp_redirect',$redirect_filter,10,2);
    $_POST=array('action'=>ZMP_President_Subject_Approvals::ACTION,'_wpnonce'=>$request['route_nonce'],
        'proposal_id'=>(string)$request['proposal_id'],'decision'=>'reject','canonical_subject_id'=>'0',
        'step_up_challenge_id'=>$request['challenge_id'],'step_up_credential'=>$request['assertion']);
    $_REQUEST=$_POST;
    ZMP_President_Subject_Approvals::handle_post();
    $emit('BLOCKED: handler returned without redirect');exit(2);
}if(!$args_for_eval||($args_for_eval[0]??null)!==$expected_fixture||rtrim((string)@realpath($expected_run_root),'/')!==$expected_run_root
    ||!is_writable($expected_run_root)||!function_exists('proc_open')||!function_exists('proc_close')||!function_exists('proc_terminate')
    ||!function_exists('posix_getpgid')||!function_exists('posix_getsid')||!function_exists('posix_kill')||!is_executable('/usr/bin/setsid')){
    $emit('BLOCKED: exact parent invocation/worker runtime preflight failed; writes=0');exit(2);}
require_once ABSPATH.'wp-admin/includes/user.php';
$user_table=$wpdb->users;$usermeta_table=$wpdb->usermeta;
$count=static function($table)use($wpdb){return(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");};
$credential_fp=static function()use($wpdb,$credential_table){$r=$wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} ORDER BY credential_id_hash",ARRAY_A);return hash('sha256',serialize(is_array($r)?$r:array()));};
$protected_fp=static function()use($wpdb,$credential_table){$u=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_login=%s",'zmp66a_admin'));$r=$u?$wpdb->get_results($wpdb->prepare("SELECT user_id,credential_id_hash,label,credential_record FROM {$credential_table} WHERE user_id=%d ORDER BY credential_id_hash",$u),ARRAY_A):array();return hash('sha256',serialize(is_array($r)?$r:array()));};
$missing=new stdClass();$debug_before=get_option('zmp_debug_events',$missing);$capture_before=get_option('zmp_debug_capture',$missing);
$role_option=$wpdb->get_blog_prefix().'user_roles';
$snapshot=static function()use($wpdb,$count,$credential_fp,$protected_fp,$proposal_table,$proposal_events_table,$mandate_table,$mandate_audit_table,$subject_table,$alias_table,$role_option,$missing){
    wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');
    return array('users'=>$count($wpdb->users),'credentials'=>$count($wpdb->prefix.'zmp_passkey_credentials'),
        'challenges'=>$count($wpdb->prefix.'zmp_passkey_challenges'),'proposals'=>$count($proposal_table),
        'proposal_events'=>$count($proposal_events_table),'mandates'=>$count($mandate_table),'mandate_audit'=>$count($mandate_audit_table),
        'subjects'=>$count($subject_table),'aliases'=>$count($alias_table),'credential_fp'=>$credential_fp(),
        'protected_fp'=>$protected_fp(),'roles_hash'=>hash('sha256',serialize(get_option($role_option,array()))),
        'debug_hash'=>hash('sha256',serialize(get_option('zmp_debug_events',$missing))),
        'capture_hash'=>hash('sha256',serialize(get_option('zmp_debug_capture',$missing))));
};
$before=$snapshot();
$protected_user=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$user_table} WHERE user_login=%s",'zmp66a_admin'));
$protected_label=$protected_user?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$credential_table} WHERE user_id=%d AND label=%s",$protected_user,'Synthetic UI review only')):0;
if($before['users']!==1||$before['credentials']!==1||$before['challenges']!==0||!$protected_user||$protected_label!==1){
    $emit('BLOCKED: one-user/one-credential/zero-challenge synthetic baseline missing; writes=0');exit(2);}
$original_user=(int)get_current_user_id();$post_before=$_POST;$request_before=$_REQUEST;$cookie_before=$_COOKIE;
$server_keys=array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD','PHP_SELF','SCRIPT_NAME');
$server_before=array();foreach($server_keys as $key){$server_before[$key]=array_key_exists($key,$_SERVER)?array(true,$_SERVER[$key]):array(false,null);}
$teacher_id=0;$president_id=0;$mandate_id=0;$proposal_id=0;$proposal_key='';$credential_id='';$private_key=null;$user_handle='';
$auth_cookie='';$logged_cookie='';$session_token='';$route_nonce='';$challenge_id='';$state_dir='';$state_created=false;$release_written=false;
$worker_handles=array();$worker_pids=array();$worker_results=array();$worker_exit_ok=true;$worker_supervision_ok=true;$boundary_hits=0;$rejected=0;$failed=0;
$proposal_ok=false;$challenge_ok=false;$counter_ok=false;$integrity_ok=false;$artifacts_removed=false;$cleanup_ok=false;
$checks=array();$failures=array();$stage='setup';$worker_status_evaluated=false;$worker_exit_evaluated=false;$post_state_evaluated=false;
$assert_ids=array('boundary_hits','worker_status_matrix','proposal_event','challenge_consumed','credential_counter','worker_exit','fixture_exception','cleanup_baseline','db_integrity');
$assert=static function($ok,$id)use(&$checks,&$failures,$assert_ids){if(!in_array($id,$assert_ids,true)){$failures[]='fixture_exception';return;}$checks[]=$id;if(!$ok){$failures[]=$id;}};
$wait_process_exit=static function($proc,$seconds){$deadline=microtime(true)+$seconds;$status=null;do{$status=@proc_get_status($proc);if(is_array($status)&&empty($status['running'])){return$status;}usleep(10000);}while(microtime(true)<$deadline);return$status;};
$process_group_members=static function($pgid){if($pgid<2||!is_dir('/proc')){return false;}$files=@glob('/proc/[0-9]*/stat');if(!is_array($files)){return false;}$members=array();
    foreach($files as $file){$raw=@file_get_contents($file);if(!is_string($raw)){if(is_file($file)){return false;}continue;}$close=strrpos($raw,')');if($close===false){return false;}
        $fields=preg_split('/\s+/',trim(substr($raw,$close+1)));if(!is_array($fields)||count($fields)<4){return false;}$member_group=(int)$fields[2];if($member_group!==$pgid){continue;}
        if((int)$fields[3]!==$pgid){return false;}if(!in_array($fields[0],array('Z','X'),true)){$members[(int)basename(dirname($file))]=$fields[0];}}
    return$members;};
$wait_group_empty=static function($pgid,$seconds)use($process_group_members){$deadline=microtime(true)+$seconds;$members=false;do{$members=$process_group_members($pgid);if(!is_array($members)||!$members){return$members;}usleep(10000);}while(microtime(true)<$deadline);return$members;};
$stop_process_group=static function($proc,$pgid)use($wait_process_exit,$process_group_members,$wait_group_empty){$status=@proc_get_status($proc);if(!is_array($status)){return false;}
    $members=$process_group_members($pgid);if(!is_array($members)){$had_active=!empty($status['running']);if($had_active){if(!@proc_terminate($proc,SIGTERM)){return false;}$status=$wait_process_exit($proc,1.0);
            if(!is_array($status)||!empty($status['running'])){if(!@proc_terminate($proc,SIGKILL)){return false;}$status=$wait_process_exit($proc,1.0);}}
        if(!is_array($status)||!empty($status['running'])){return false;}@proc_close($proc);return false;}
    $had_active=(bool)$members||!empty($status['running']);$group_owned=$pgid>=2&&@posix_getpgid($pgid)===$pgid&&@posix_getsid($pgid)===$pgid;
    if(!empty($status['running'])&&!$group_owned){if(!@proc_terminate($proc,SIGTERM)){return false;}$status=$wait_process_exit($proc,1.0);
        if(!is_array($status)||!empty($status['running'])){if(!@proc_terminate($proc,SIGKILL)){return false;}$status=$wait_process_exit($proc,1.0);}
        if(!is_array($status)||!empty($status['running'])){return false;}}
    elseif(!empty($status['running'])||$members){if(!@posix_kill(-$pgid,SIGTERM)){return false;}$members=$wait_group_empty($pgid,1.0);if(!is_array($members)){return false;}
        if($members){if(!@posix_kill(-$pgid,SIGKILL)){return false;}$members=$wait_group_empty($pgid,1.0);}}
    if(!is_array($members)||$members){return false;}$status=$wait_process_exit($proc,1.0);if(!is_array($status)||!empty($status['running'])){return false;}
    $members=$process_group_members($pgid);if(!is_array($members)||$members){return false;}
    return array('status'=>$status,'had_active'=>$had_active,'group_empty'=>true);};
$event_count=static function($proposal,$actor,$type)use($wpdb,$proposal_events_table){return(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$proposal_events_table} WHERE proposal_id=%d AND actor_id=%d AND event_type=%s",(int)$proposal,(int)$actor,(string)$type));};$remove_state=static function($dir)use($expected_run_root){
    if(!is_string($dir)||dirname($dir)!==$expected_run_root||@realpath(dirname($dir))!==$expected_run_root
        ||!preg_match('/\Aphase66a-president-route-race-[a-f0-9]{16}\z/D',basename($dir))){return false;}
    $allowed=array('request.json','request.json.tmp','ready-1','ready-1.tmp','ready-2','ready-2.tmp','release','release.tmp',
        'result-1.json','result-1.json.tmp','result-2.json','result-2.json.tmp');$items=@scandir($dir);if(!is_array($items)){return!file_exists($dir);}
    foreach($items as $item){if($item==='.'||$item==='..'){continue;}if(!in_array($item,$allowed,true)||!@unlink($dir.'/'.$item)){return false;}}
    return@rmdir($dir);
};
try{
    $stage='private rendezvous';$state_dir=$expected_run_root.'/phase66a-president-route-race-'.bin2hex(random_bytes(8));
    if(!@mkdir($state_dir,0700)){throw new RuntimeException('private-state-unavailable');}
    $state_created=true;
    if(!@chmod($state_dir,0700)||@realpath($state_dir)!==$state_dir||(fileperms($state_dir)&0777)!==0700){throw new RuntimeException('private-state-unavailable');}
    $stage='synthetic President and teacher';$suffix=bin2hex(random_bytes(8));
    $teacher=wp_insert_user(array('user_login'=>'zmp66a_rr_teacher_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),
        'user_email'=>'zmp66a-rr-teacher-'.$suffix.'@example.invalid','role'=>'zmp_teacher'));
    if(is_wp_error($teacher)||!is_int($teacher)||$teacher<1){throw new RuntimeException('teacher-create-failed');}$teacher_id=$teacher;
    $president=wp_insert_user(array('user_login'=>'zmp66a_rr_president_'.$suffix,'user_pass'=>bin2hex(random_bytes(24)),
        'user_email'=>'zmp66a-rr-president-'.$suffix.'@example.invalid','role'=>'subscriber'));
    if(is_wp_error($president)||!is_int($president)||$president<1){throw new RuntimeException('president-create-failed');}$president_id=$president;
    $admin=get_user_by('login','zmp66a_admin');if(!$admin instanceof WP_User||!user_can($admin,'manage_options')){throw new RuntimeException('synthetic-admin-unavailable');}
    wp_set_current_user($admin->ID);$today=current_time('Y-m-d');
    $mandate=ZMP_President_Mandate::appoint($president_id,$today,ZMP_President_Mandate::term_end_date($today),'Synthetic concurrent route review');
    if(is_wp_error($mandate)||!is_int($mandate)||$mandate<1){throw new RuntimeException('mandate-create-failed');}$mandate_id=$mandate;clean_user_cache($president_id);
    if(!ZMP_President_Mandate::user_has_active_mandate($president_id)){throw new RuntimeException('active-mandate-missing');}

    $stage='synthetic passkey';$user_handle=random_bytes(32);
    update_user_meta($president_id,ZMP_Passkeys::USER_HANDLE_META,rtrim(strtr(base64_encode($user_handle),'+/','-_'),'='));
    $private_key=openssl_pkey_new(array('private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1'));
    $details=$private_key?openssl_pkey_get_details($private_key):false;
    if(!is_array($details)||!isset($details['ec']['x'],$details['ec']['y'])||strlen($details['ec']['x'])!==32||strlen($details['ec']['y'])!==32){throw new RuntimeException('p256-unavailable');}
    $cose=CBOR\MapObject::create();$cose->add(CBOR\UnsignedIntegerObject::create(1),CBOR\UnsignedIntegerObject::create(2));
    $cose->add(CBOR\UnsignedIntegerObject::create(3),CBOR\NegativeIntegerObject::create(-7));
    $cose->add(CBOR\NegativeIntegerObject::create(-1),CBOR\UnsignedIntegerObject::create(1));
    $cose->add(CBOR\NegativeIntegerObject::create(-2),CBOR\ByteStringObject::create($details['ec']['x']));
    $cose->add(CBOR\NegativeIntegerObject::create(-3),CBOR\ByteStringObject::create($details['ec']['y']));$credential_id=random_bytes(32);
    $record=Webauthn\CredentialRecord::create($credential_id,'public-key',array(),'none',Webauthn\TrustPath\EmptyTrustPath::create(),
        Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000'),(new CBOR\Encoder())->encode($cose),$user_handle,0);
    if(!ZMP_Passkey_Store::save_credential($president_id,$record,'Synthetic concurrent route key')){throw new RuntimeException('credential-store-failed');}

    $stage='synthetic proposal';wp_set_current_user($teacher_id);
    $submitted=ZMP_Mentor_Subject_Proposals::submit($teacher_id,'Synthetic concurrent route rejection '.$suffix);
    if(is_wp_error($submitted)||!is_int($submitted)||$submitted<1){throw new RuntimeException('proposal-submit-failed');}$proposal_id=$submitted;
    $pr=$wpdb->get_row($wpdb->prepare("SELECT proposal_key,status FROM {$proposal_table} WHERE id=%d",$proposal_id),ARRAY_A);
    if(!is_array($pr)||($pr['status']??'')!=='pending'){throw new RuntimeException('pending-proposal-missing');}$proposal_key=(string)$pr['proposal_key'];    $stage='one valid President proof';$_SERVER['HTTP_HOST']='127.0.0.1:8102';$_SERVER['REQUEST_SCHEME']='http';$_SERVER['SERVER_PORT']='8102';
    unset($_SERVER['HTTPS']);$_SERVER['HTTP_ORIGIN']=$expected_origin;$_SERVER['HTTP_REFERER']=$expected_origin.'/';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['REQUEST_METHOD']='POST';
    wp_set_current_user($president_id);
    $auth_hook=static function($cookie)use(&$auth_cookie){if(is_string($cookie)){$auth_cookie=$cookie;}};
    $logged_hook=static function($cookie)use(&$logged_cookie){if(is_string($cookie)){$logged_cookie=$cookie;}};
    add_action('set_auth_cookie',$auth_hook,10,6);add_action('set_logged_in_cookie',$logged_hook,10,6);
    wp_set_auth_cookie($president_id,false,false);remove_action('set_auth_cookie',$auth_hook,10);remove_action('set_logged_in_cookie',$logged_hook,10);
    $parsed=$logged_cookie!==''?wp_parse_auth_cookie($logged_cookie,'logged_in'):false;$session_token=is_array($parsed)?(string)($parsed['token']??''):'';
    if($session_token===''||$auth_cookie===''||!WP_Session_Tokens::get_instance($president_id)->verify($session_token)){throw new RuntimeException('session-create-failed');}
    $_COOKIE[AUTH_COOKIE]=$auth_cookie;$_COOKIE[LOGGED_IN_COOKIE]=$logged_cookie;
    $route_nonce=wp_create_nonce(ZMP_President_Subject_Approvals::NONCE);
    if(!wp_verify_nonce($route_nonce,ZMP_President_Subject_Approvals::NONCE)){throw new RuntimeException('nonce-create-failed');}
    $issued=ZMP_Passkeys::step_up_options($president_id,'president_subject_decision',$proposal_id,'reject',0,$route_nonce);
    if(is_wp_error($issued)||!is_array($issued)||!is_string($issued['challenge_id']??null)||$issued['challenge_id']===''
        ||!is_array($issued['options']??null)||($issued['options']['challenge']??null)!==$issued['challenge_id']
        ||($issued['options']['rpId']??null)!=='127.0.0.1'||($issued['options']['userVerification']??null)!=='required'){
        throw new RuntimeException('step-up-options-failed');}
    $challenge_id=$issued['challenge_id'];
    $client=wp_json_encode(array('type'=>'webauthn.get','challenge'=>$challenge_id,'origin'=>$expected_origin,'crossOrigin'=>false),JSON_UNESCAPED_SLASHES);
    $auth_data=hash('sha256','127.0.0.1',true).chr(Webauthn\AuthenticatorData::FLAG_UP|Webauthn\AuthenticatorData::FLAG_UV).pack('N',1);
    $signature='';if(!is_string($client)||!openssl_sign($auth_data.hash('sha256',$client,true),$signature,$private_key,OPENSSL_ALGO_SHA256)){throw new RuntimeException('assertion-sign-failed');}
    $b64url=static function($v){return rtrim(strtr(base64_encode($v),'+/','-_'),'=');};
    $assertion_json=wp_json_encode(array('id'=>$b64url($credential_id),'rawId'=>$b64url($credential_id),'type'=>'public-key',
        'response'=>array('clientDataJSON'=>$b64url($client),'authenticatorData'=>$b64url($auth_data),'signature'=>$b64url($signature),'userHandle'=>$b64url($user_handle)),
        'clientExtensionResults'=>new stdClass()),JSON_UNESCAPED_SLASHES);
    if(!is_string($assertion_json)||$assertion_json===''){throw new RuntimeException('assertion-encode-failed');}
    $envelope=array('president_id'=>$president_id,'proposal_id'=>$proposal_id,'challenge_id'=>$challenge_id,'route_nonce'=>$route_nonce,
        'assertion'=>$assertion_json,'auth_cookie'=>$auth_cookie,'logged_cookie'=>$logged_cookie);
    $request_path=$state_dir.'/request.json';$json=wp_json_encode($envelope,JSON_UNESCAPED_SLASHES);
    if(!is_string($json)||@file_put_contents($request_path.'.tmp',$json,LOCK_EX)===false||!@chmod($request_path.'.tmp',0600)
        ||!@rename($request_path.'.tmp',$request_path)||(fileperms($request_path)&0777)!==0600){throw new RuntimeException('private-proof-write-failed');}
    unset($envelope,$json);

    $stage='two handler workers';if(!is_executable('/usr/local/bin/wp')){throw new RuntimeException('wp-cli-unavailable');}
    $descriptors=array(0=>array('file','/dev/null','r'),1=>array('file','/dev/null','w'),2=>array('file','/dev/null','w'));
    for($id=1;$id<=2;$id++){
        $cmd=array('/usr/bin/setsid','--wait','/usr/local/bin/wp','--path='.$expected_site,'eval-file',$expected_fixture,'worker',(string)$id,$state_dir,$expected_fixture);
        $pipes=array();$proc=@proc_open($cmd,$descriptors,$pipes,$expected_site,null,array('bypass_shell'=>true));
        if(!is_resource($proc)){throw new RuntimeException('worker-start-failed');}$worker_handles[$id]=$proc;$status=@proc_get_status($proc);
        if(!is_array($status)||!isset($status['pid'])||(int)$status['pid']<2){throw new RuntimeException('worker-group-identity-unavailable');}
        $worker_pids[$id]=(int)$status['pid'];$group_deadline=microtime(true)+2.0;
        while((@posix_getpgid($worker_pids[$id])!==$worker_pids[$id]||@posix_getsid($worker_pids[$id])!==$worker_pids[$id])&&microtime(true)<$group_deadline){$status=@proc_get_status($proc);if(!is_array($status)||empty($status['running'])){break;}usleep(10000);}
        if(@posix_getpgid($worker_pids[$id])!==$worker_pids[$id]||@posix_getsid($worker_pids[$id])!==$worker_pids[$id]){throw new RuntimeException('worker-process-group-not-established');}
    }
    $deadline=microtime(true)+20.0;
    while(microtime(true)<$deadline&&(!is_file($state_dir.'/ready-1')||!is_file($state_dir.'/ready-2'))){usleep(1000);}
    if(!is_file($state_dir.'/ready-1')||!is_file($state_dir.'/ready-2')){throw new RuntimeException('both-update-boundaries-not-reached');}
    if(@file_put_contents($state_dir.'/release.tmp','go',LOCK_EX)!==2||!@chmod($state_dir.'/release.tmp',0600)
        ||!@rename($state_dir.'/release.tmp',$state_dir.'/release')){throw new RuntimeException('barrier-release-failed');}$release_written=true;
    $deadline=microtime(true)+25.0;
    while(microtime(true)<$deadline&&(!is_file($state_dir.'/result-1.json')||!is_file($state_dir.'/result-2.json'))){usleep(1000);}    for($id=1;$id<=2;$id++){
        $path=$state_dir.'/result-'.$id.'.json';$result=is_file($path)&&(fileperms($path)&0777)===0600?json_decode((string)@file_get_contents($path),true):null;
        if(!is_array($result)||($result['worker']??null)!==$id||($result['preflight']??false)!==true||($result['ready']??false)!==true
            ||($result['called']??false)!==true||(int)($result['boundary_hits']??0)!==1
            ||!in_array(($result['route_status']??''),array('rejected','failed'),true)
            ||!in_array((int)($result['redirect_code']??0),array(302,303),true)){throw new RuntimeException('worker-result-invalid');}
        $worker_results[$id]=$result;$boundary_hits+=(int)$result['boundary_hits'];
        if($result['route_status']==='rejected'){$rejected++;}if($result['route_status']==='failed'){$failed++;}
    }
    $worker_status_evaluated=true;$worker_exit_codes=array();$worker_exit_status=array();
    $exit_deadline=microtime(true)+5.0;
    while(microtime(true)<$exit_deadline&&count($worker_exit_codes)<count($worker_handles)){
        foreach($worker_handles as $id=>$proc){if(isset($worker_exit_codes[$id])||!is_resource($proc)){continue;}
            $status=@proc_get_status($proc);
            if(!is_array($status)){continue;}
            if(!empty($status['running'])){$worker_exit_status[$id]='running';continue;}
            $code=isset($status['exitcode'])&&is_int($status['exitcode'])?$status['exitcode']:-1;
            if($code>=0){$worker_exit_codes[$id]=$code;$worker_exit_status[$id]='exited';}
            else{$worker_exit_status[$id]='unknown';}
        }
        if(count($worker_exit_codes)<count($worker_handles)){usleep(10000);}
    }
    foreach($worker_handles as $id=>$proc){if(!is_resource($proc)){$worker_exit_ok=false;continue;}
        $group_result=$stop_process_group($proc,(int)($worker_pids[$id]??0));if(!is_array($group_result)||empty($group_result['group_empty'])){$worker_supervision_ok=false;continue;}
        $status=$group_result['status'];if(!isset($worker_exit_codes[$id])){$worker_exit_ok=false;$worker_exit_codes[$id]=isset($status['exitcode'])?(int)$status['exitcode']:-1;}
        elseif(!empty($group_result['had_active'])){$worker_exit_ok=false;}
        $closed=@proc_close($proc);$worker_handles[$id]=null;
        if(!isset($worker_exit_codes[$id])||$worker_exit_codes[$id]!==0
            ||($closed!==-1&&$closed!==$worker_exit_codes[$id])){$worker_exit_ok=false;}}
    $worker_exit_evaluated=true;
    $proposal=$wpdb->get_row($wpdb->prepare("SELECT status,decided_by FROM {$proposal_table} WHERE id=%d AND proposal_key=%s",$proposal_id,$proposal_key),ARRAY_A);
    $proposal_ok=is_array($proposal)&&($proposal['status']??'')==='rejected'&&(int)($proposal['decided_by']??0)===$president_id
        &&$event_count($proposal_id,$president_id,'rejected')===1;
    $challenge=$wpdb->get_row($wpdb->prepare("SELECT consumed_at FROM {$challenge_table} WHERE challenge_hash=%s AND user_id=%d",hash('sha256',$challenge_id),$president_id),ARRAY_A);
    $challenge_ok=is_array($challenge)&&!empty($challenge['consumed_at']);
    $stored=ZMP_Passkey_Store::find_credential($credential_id);$counter_ok=is_object($stored)&&(int)($stored->counter??-1)===1;
    $post_state_evaluated=true;
    $assert($boundary_hits===2,'boundary_hits');$assert($rejected===1&&$failed===1,'worker_status_matrix');
    $assert($proposal_ok,'proposal_event');
    $assert($challenge_ok,'challenge_consumed');$assert($counter_ok,'credential_counter');
    $assert($worker_exit_ok,'worker_exit');
}catch(Throwable $ignored){$failures[]='fixture_exception';}
finally{
    if($state_created&&$state_dir!==''){
        if(!$release_written){@file_put_contents($state_dir.'/release.tmp','abort',LOCK_EX);@chmod($state_dir.'/release.tmp',0600);@rename($state_dir.'/release.tmp',$state_dir.'/release');}
        foreach($worker_handles as $id=>$proc){if(is_resource($proc)){$group_result=$stop_process_group($proc,(int)($worker_pids[$id]??0));
            if(!is_array($group_result)||empty($group_result['group_empty'])){$worker_supervision_ok=false;continue;}if(!empty($group_result['had_active'])){$worker_exit_ok=false;}
            @proc_close($proc);$worker_handles[$id]=null;}}
    }
    if(!$worker_supervision_ok){$emit('BLOCKED: worker process group survived bounded TERM/KILL; stop before database cleanup');exit(2);}
    if($president_id>0&&$proposal_id>0){
        if($challenge_id!==''){$wpdb->delete($challenge_table,array('challenge_hash'=>hash('sha256',$challenge_id),'user_id'=>$president_id),array('%s','%d'));}
        $owned=$wpdb->get_col($wpdb->prepare("SELECT challenge_hash FROM {$challenge_table} WHERE user_id=%d AND ceremony=%s AND action=%s AND resource_id=%d AND decision=%s",$president_id,'step_up','president_subject_decision',$proposal_id,'reject'));
        foreach((array)$owned as $hash){if(is_string($hash)&&preg_match('/\A[a-f0-9]{64}\z/D',$hash)){$wpdb->delete($challenge_table,array('challenge_hash'=>$hash,'user_id'=>$president_id),array('%s','%d'));}}
    }
    if($proposal_id>0){$wpdb->delete($proposal_events_table,array('proposal_id'=>$proposal_id),array('%d'));$where=array('id'=>$proposal_id);$formats=array('%d');
        if($proposal_key!==''){$where['proposal_key']=$proposal_key;$formats[]='%s';}$wpdb->delete($proposal_table,$where,$formats);}
    if($credential_id!==''&&$president_id>0){$wpdb->delete($credential_table,array('user_id'=>$president_id,'credential_id_hash'=>hash('sha256',$credential_id)),array('%d','%s'));}
    if($mandate_id>0&&$president_id>0){$wpdb->delete($mandate_audit_table,array('mandate_id'=>$mandate_id),array('%d'));$wpdb->delete($mandate_table,array('id'=>$mandate_id,'user_id'=>$president_id),array('%d','%d'));}
    if(is_resource($private_key)||$private_key instanceof OpenSSLAsymmetricKey){openssl_pkey_free($private_key);}
    if($session_token!==''&&$president_id>0){WP_Session_Tokens::get_instance($president_id)->destroy($session_token);}
    foreach(array($teacher_id,$president_id) as $uid){if($uid>0&&get_userdata($uid)){wp_delete_user($uid);clean_user_cache($uid);}}
    wp_cache_delete('zmp_debug_events','options');wp_cache_delete('alloptions','options');$events=get_option('zmp_debug_events',$missing);
    if($events!==$missing&&is_array($events)){$keep=array();foreach($events as $event){$ctx=is_array($event)&&is_array($event['context']??null)?$event['context']:array();
        $owned=$proposal_id>0&&(int)($ctx['proposal_id']??0)===$proposal_id;if(is_array($event)){$event_user_id=(int)($event['user_id']??0);if(($teacher_id>0&&$event_user_id===$teacher_id)||($president_id>0&&$event_user_id===$president_id)){$owned=true;}}
        if(!$owned){$keep[]=$event;}}if(count($keep)!==count($events)){if($keep){update_option('zmp_debug_events',array_values($keep),false);}elseif($debug_before===$missing){delete_option('zmp_debug_events');}else{update_option('zmp_debug_events',$debug_before,false);}}}
    wp_cache_delete('zmp_debug_capture','options');wp_cache_delete('alloptions','options');
    if(serialize(get_option('zmp_debug_capture',$missing))!==serialize($capture_before)){if($capture_before===$missing){delete_option('zmp_debug_capture');}else{update_option('zmp_debug_capture',$capture_before,false);}}
    wp_set_current_user($original_user);$_POST=$post_before;$_REQUEST=$request_before;$_COOKIE=$cookie_before;
    foreach($server_before as $key=>$saved){if($saved[0]){$_SERVER[$key]=$saved[1];}else{unset($_SERVER[$key]);}}
    if($state_created&&$state_dir!==''){$artifacts_removed=$remove_state($state_dir);}
}$after=$snapshot();$cleanup_ok=$after===$before&&hash_equals($before['credential_fp'],$after['credential_fp'])
    &&hash_equals($before['protected_fp'],$after['protected_fp'])&&(int)get_current_user_id()===$original_user&&$artifacts_removed;
$integrity_ok=false;$integrity_proc=@proc_open(array('/usr/bin/setsid','--wait','/usr/local/bin/wp','--path='.$expected_site,'db','check'),
    array(0=>array('file','/dev/null','r'),1=>array('file','/dev/null','w'),2=>array('file','/dev/null','w')),$integrity_pipes,$expected_site,null,array('bypass_shell'=>true));
if(is_resource($integrity_proc)){$integrity_status=@proc_get_status($integrity_proc);$integrity_pid=is_array($integrity_status)?(int)($integrity_status['pid']??0):0;
    $group_deadline=microtime(true)+2.0;while($integrity_pid>1&&(@posix_getpgid($integrity_pid)!==$integrity_pid||@posix_getsid($integrity_pid)!==$integrity_pid)&&microtime(true)<$group_deadline){$integrity_status=@proc_get_status($integrity_proc);if(!is_array($integrity_status)||empty($integrity_status['running'])){break;}usleep(10000);}
    $group_ready=$integrity_pid>1&&@posix_getpgid($integrity_pid)===$integrity_pid&&@posix_getsid($integrity_pid)===$integrity_pid;
    if($group_ready){$integrity_status=$wait_process_exit($integrity_proc,25.0);$timed_out=!is_array($integrity_status)||!empty($integrity_status['running']);}
    else{$integrity_status=array('running'=>true);$timed_out=true;}
    $integrity_group_result=$stop_process_group($integrity_proc,$integrity_pid);
    if(!is_array($integrity_group_result)||empty($integrity_group_result['group_empty'])){$worker_supervision_ok=false;}
    elseif(!$timed_out||$group_ready){$integrity_status=$integrity_group_result['status'];$exit_code=(int)($integrity_status['exitcode']??-1);$closed=@proc_close($integrity_proc);
        $integrity_ok=!$timed_out&&empty($integrity_group_result['had_active'])&&($exit_code===0||($exit_code===-1&&$closed===0));}}
if(!$worker_supervision_ok){$emit('BLOCKED: integrity process group survived bounded TERM/KILL; recovery required');exit(2);}
$assert($cleanup_ok,'cleanup_baseline');
$assert($integrity_ok,'db_integrity');
$emit('DIAGNOSTIC: worker_status_evaluated='.($worker_status_evaluated?'true':'false').'; worker_status_matrix_ok='.($worker_status_evaluated&&$boundary_hits===2&&$rejected===1&&$failed===1?'true':'false').'; worker_exit_evaluated='.($worker_exit_evaluated?'true':'false').'; worker_exit_ok='.($worker_exit_evaluated&&$worker_exit_ok?'true':'false').'; post_state_evaluated='.($post_state_evaluated?'true':'false').'; proposal_event_ok='.($post_state_evaluated&&$proposal_ok?'true':'false').'; challenge_consumed_ok='.($post_state_evaluated&&$challenge_ok?'true':'false').'; credential_counter_ok='.($post_state_evaluated&&$counter_ok?'true':'false').'; cleanup_baseline='.($cleanup_ok?'true':'false').'; artifacts_removed='.($artifacts_removed?'true':'false').'; db_integrity='.($integrity_ok?'true':'false'));
$allowed_ids=$assert_ids;
$reported=array_values(array_unique(array_intersect($allowed_ids,$failures)));
if($reported){foreach($reported as $id){$emit('ASSERTION_FAIL:'.$id);}exit(1);}
$emit('PASS: concurrent President subject route replay; workers=2');exit(0);
