<?php
/** Bounded synthetic regression for anonymous passkey challenge issuance churn. */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) { fwrite(STDERR,"BLOCKED: active passkey components/schema unavailable\n"); exit(2); }
global $wpdb;
$ct=$wpdb->prefix.'zmp_passkey_challenges'; $rt=$wpdb->prefix.'zmp_passkey_credentials'; $ut=$wpdb->users;
$ip='127.0.0.113'; $packed_ip=@inet_pton($ip); $peer_hash=is_string($packed_ip)?hash_hmac('sha256',inet_ntop($packed_ip),wp_salt('auth')):''; $rate_key='zmp_passkey_login_rate_'.$peer_hash;
$original_user=(int)get_current_user_id(); $post_before=$_POST; $server_keys=array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before=array(); foreach($server_keys as $k)$server_before[$k]=array_key_exists($k,$_SERVER)?array(true,$_SERVER[$k]):array(false,null);
$prior_rate_state=get_transient($rate_key); $prior_rate_expiry=(int)get_option('_transient_timeout_'.$rate_key,0); delete_transient($rate_key);
$checks=array(); $failures=array(); $owned_ids=array(); $accepted=0; $all_consumed=true; $all_anonymous=true; $secure_requests=0; $options_denial=null; $finish_denial=null;
$count=static function($table)use($wpdb){return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");};
$credential_fp=static function()use($wpdb,$rt){$rows=$wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$rt} ORDER BY credential_id_hash",ARRAY_A);return hash('sha256',serialize(is_array($rows)?$rows:array()));};
$before=array($count($ut),$count($rt),$count($ct),$credential_fp());
$owner=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$ut} WHERE user_login=%s",'zmp66a_admin'));
$protected_before=$owner?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$owner,'Synthetic UI review only')):0;
$parts=wp_parse_url(home_url('/')); $scheme=strtolower((string)($parts['scheme']??'http')); $host=strtolower(rtrim((string)($parts['host']??'127.0.0.1'),'.')); $port=isset($parts['port'])?(int)$parts['port']:80;
$origin=$scheme.'://'.$host.(($scheme==='http'&&$port===80)||($scheme==='https'&&$port===443)?'':':'.$port);
$generic=static function($response){$json=$response['json']??null;return $response['status']===400&&is_array($json)&&array_keys($json)===array('success','data')&&($json['success']??null)===false&&($json['data']['message']??null)===__('Passkey sign-in could not be completed. Try again or use your password.','zmp-competitions');};
$send=static function($action,$fields)use($ip,$host,$port,$origin){
    $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']='http'; $_SERVER['SERVER_PORT']=(string)$port; unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN']=$origin; $_SERVER['HTTP_REFERER']=$origin.'/wp-admin/admin-ajax.php'; $_SERVER['REMOTE_ADDR']=$ip; $_SERVER['REQUEST_METHOD']='POST'; $_POST=array_merge(array('action'=>$action),$fields);
    $secure=ZMP_Passkeys::is_secure_request_context(); $status=null; $die_response=null;
    $status_cb=static function($header,$code)use(&$status){$status=is_numeric($code)?(int)$code:null;return $header;};
    $die_cb=static function()use(&$die_response){return static function($message='',$title='',$args=array())use(&$die_response){$die_response=is_array($args)&&isset($args['response'])?(int)$args['response']:null;throw new RuntimeException('synthetic-request-complete');};};
    $ajax=static function(){return true;}; add_filter('wp_doing_ajax',$ajax); add_filter('wp_die_ajax_handler',$die_cb); add_filter('wp_die_json_handler',$die_cb); add_filter('status_header',$status_cb,10,2);
    ob_start(); try { do_action($action==='zmp_passkey_login_options'?'wp_ajax_nopriv_zmp_passkey_login_options':'wp_ajax_nopriv_zmp_passkey_login_finish'); } catch(Throwable $e) { }
    $body=(string)ob_get_clean(); remove_filter('wp_doing_ajax',$ajax); remove_filter('wp_die_ajax_handler',$die_cb); remove_filter('wp_die_json_handler',$die_cb); remove_filter('status_header',$status_cb,10);
    return array('json'=>json_decode($body,true),'status'=>$status,'die'=>$die_response,'secure'=>$secure);
};
$lookup=static function($id)use($wpdb,$ct){return $wpdb->get_row($wpdb->prepare("SELECT ceremony,consumed_at,expires_at FROM {$ct} WHERE challenge_hash=%s",hash('sha256',$id)),ARRAY_A);};
$assert=static function($condition,$name)use(&$checks,&$failures){$checks[]=$name;if(!$condition)$failures[]=$name;};
try {
    wp_set_current_user(0);
    $assert(has_action('wp_ajax_nopriv_zmp_passkey_login_options',array('ZMP_Passkeys','ajax_login_options'))!==false,'anonymous login-options controller is registered');
    $assert(has_action('wp_ajax_nopriv_zmp_passkey_login_finish',array('ZMP_Passkeys','ajax_login_finish'))!==false,'anonymous login-finish controller is registered');
    $invalid_payload=wp_json_encode(array('id'=>'synthetic-invalid-assertion','rawId'=>'c3ludGhldGljLWludmFsaWQ','type'=>'public-key','response'=>array('clientDataJSON'=>'e30','authenticatorData'=>'AA','signature'=>'AA','userHandle'=>null)));
    for($i=0;$i<12;$i++) {
        $options=$send('zmp_passkey_login_options',array());
        if($options['secure'])$secure_requests++;
        if(($options['json']['success']??null)===true) {
            $accepted++; $id=$options['json']['data']['challenge_id']??'';
            if(is_string($id)&&$id!=='') {
                $owned_ids[]=$id; $row=$lookup($id);
                if(!is_array($row)||($row['ceremony']??'')!=='login'||!empty($row['consumed_at']))$all_consumed=false;
                $finish=$send('zmp_passkey_login_finish',array('challenge_id'=>$id,'credential'=>$invalid_payload));
                if($finish_denial===null)$finish_denial=$finish;
                $after=$lookup($id);
                if(!is_array($after)||empty($after['consumed_at'])||!$generic($finish))$all_consumed=false;
                if(is_user_logged_in()||(int)get_current_user_id()!==0)$all_anonymous=false;
            } else { $all_consumed=false; }
        } elseif($options_denial===null) { $options_denial=$options; }
    }
    $assert($secure_requests===12,'all 12 requests use the configured same-origin context and one synthetic peer');
    $assert($accepted<=5&&count($owned_ids)<=5,'total accepted options/challenges remains at most five after prior challenges are consumed within the 300-second window');
    $assert($options_denial!==null,'requests after the issuance limit receive a generic denial');
    $assert($options_denial!==null&&$finish_denial!==null&&$generic($options_denial)&&$generic($finish_denial)&&$options_denial['json']===$finish_denial['json'],'later options denial is indistinguishable from invalid-finish generic denial');
    $assert($all_consumed,'each accepted challenge is consumed by its invalid assertion and does not count as pending');
    $assert($all_anonymous&&!is_user_logged_in()&&(int)get_current_user_id()===0,'invalid assertions never create an authenticated WordPress session');
    fwrite(STDOUT,'OBSERVED accepted_options='.$accepted.'; owned_challenges='.count($owned_ids).'; limit_denials='.($options_denial===null?0:1)."\n");
} catch(Throwable $e) { $failures[]='fixture execution error: '.get_class($e); }
finally {
    foreach(array_unique($owned_ids) as $id)$wpdb->delete($ct,array('challenge_hash'=>hash('sha256',$id)),array('%s'));
    delete_transient($rate_key); if($prior_rate_state!==false){$ttl=$prior_rate_expiry>time()?$prior_rate_expiry-time():300;set_transient($rate_key,$prior_rate_state,$ttl);}
    wp_set_current_user($original_user); $_POST=$post_before; foreach($server_before as $k=>$state){if($state[0])$_SERVER[$k]=$state[1];else unset($_SERVER[$k]);}
}
$after=array($count($ut),$count($rt),$count($ct),$credential_fp());
$protected_after=$owner?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$owner,'Synthetic UI review only')):0;
$cleanup=$after[0]===$before[0]&&$after[1]===$before[1]&&$after[2]===$before[2]&&hash_equals($before[3],$after[3])&&$protected_before===$protected_after;
$checks[]='cleanup preserves users, credentials, challenge count, protected UI row and request globals';if(!$cleanup)$failures[]='fixture cleanup changed a protected baseline';
if($failures){fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks)."): login issuance churn\n");foreach($failures as $f)fwrite(STDERR,'- '.$f."\n");fwrite(STDOUT,'CLEANUP '.($cleanup?'PASS':'FAIL')."\n");exit(1);}
fwrite(STDOUT,'PASS ('.count($checks).'): login issuance churn limit after consumption'."\nCLEANUP PASS: synthetic baselines, transient and request globals restored\n");exit(0);
