<?php
/** Expected-RED test for anonymous passkey quota loss when its transient is evicted. */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) { fwrite(STDERR,"BLOCKED: active passkey components/schema unavailable\n"); exit(2); }
global $wpdb;
$ct=$wpdb->prefix.'zmp_passkey_challenges'; $rt=$wpdb->prefix.'zmp_passkey_credentials'; $ut=$wpdb->users;
$ip='127.0.0.171'; $packed_ip=@inet_pton($ip); $peer_hash=is_string($packed_ip)?hash_hmac('sha256',inet_ntop($packed_ip),wp_salt('auth')):''; $rate_key='zmp_passkey_login_rate_'.$peer_hash;
$prior_user=(int)get_current_user_id(); $prior_post=$_POST; $server_keys=array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before=array(); foreach($server_keys as $k)$server_before[$k]=array_key_exists($k,$_SERVER)?array(true,$_SERVER[$k]):array(false,null);
$prior_rate_state=get_transient($rate_key); $prior_rate_expiry=(int)get_option('_transient_timeout_'.$rate_key,0);
$checks=array(); $failures=array(); $owned=array();
$count=static function($table)use($wpdb){return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");};
$credential_fp=static function()use($wpdb,$rt){$rows=$wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$rt} ORDER BY credential_id_hash",ARRAY_A);return hash('sha256',serialize(is_array($rows)?$rows:array()));};
$before=array($count($ut),$count($rt),$count($ct),$credential_fp());
$owner=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$ut} WHERE user_login=%s",'zmp66a_admin'));
$protected_before=$owner?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$owner,'Synthetic UI review only')):0;
$parts=wp_parse_url(home_url('/')); $scheme=strtolower((string)($parts['scheme']??'http')); $host=strtolower(rtrim((string)($parts['host']??'127.0.0.1'),'.')); $port=isset($parts['port'])?(int)$parts['port']:80;
$origin=$scheme.'://'.$host.((($scheme==='http'&&$port===80)||($scheme==='https'&&$port===443))?'':':'.$port);
$assert=static function($condition,$label)use(&$checks,&$failures){$checks[]=$label;if(!$condition)$failures[]=$label;};
$generic=static function($r){$j=$r['json']??null;return ($r['status']??null)===400&&is_array($j)&&array_keys($j)===array('success','data')&&($j['success']??null)===false&&($j['data']['message']??null)===__('Passkey sign-in could not be completed. Try again or use your password.','zmp-competitions');};
$send=static function($action,$fields)use($ip,$host,$port,$origin){
    $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']=$scheme='http'; $_SERVER['SERVER_PORT']=(string)$port; unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN']=$origin; $_SERVER['HTTP_REFERER']=$origin.'/wp-admin/admin-ajax.php'; $_SERVER['REMOTE_ADDR']=$ip; $_SERVER['REQUEST_METHOD']='POST'; $_POST=array_merge(array('action'=>$action),$fields);
    $secure=ZMP_Passkeys::is_secure_request_context(); $status=null;
    $status_filter=static function($header,$code)use(&$status){$status=is_numeric($code)?(int)$code:null;return $header;};
    $die_filter=static function(){return static function($message='',$title='',$args=array()){throw new RuntimeException('synthetic ajax complete');};}; $doing_ajax=static function(){return true;};
    add_filter('wp_doing_ajax',$doing_ajax); add_filter('wp_die_ajax_handler',$die_filter); add_filter('wp_die_json_handler',$die_filter); add_filter('status_header',$status_filter,10,2);
    ob_start(); try { do_action($action==='zmp_passkey_login_options'?'wp_ajax_nopriv_zmp_passkey_login_options':'wp_ajax_nopriv_zmp_passkey_login_finish'); } catch(Throwable $e) { }
    $body=(string)ob_get_clean(); remove_filter('wp_doing_ajax',$doing_ajax); remove_filter('wp_die_ajax_handler',$die_filter); remove_filter('wp_die_json_handler',$die_filter); remove_filter('status_header',$status_filter,10);
    return array('json'=>json_decode($body,true),'status'=>$status,'secure'=>$secure);
};
$lookup=static function($id)use($wpdb,$ct){return $wpdb->get_row($wpdb->prepare("SELECT ceremony,context_hash,consumed_at,expires_at FROM {$ct} WHERE challenge_hash=%s",hash('sha256',$id)),ARRAY_A);};
$options_successes=0; $consumed=0; $transient_present=false; $sixth_success=false; $first_finish_error=null; $sixth_response=null;
try {
    wp_set_current_user(0);
    delete_transient($rate_key);
    $payload=wp_json_encode(array('id'=>'synthetic-invalid-assertion','rawId'=>'c3ludGhldGljLWludmFsaWQ','type'=>'public-key','response'=>array('clientDataJSON'=>'e30','authenticatorData'=>'AA','signature'=>'AA','userHandle'=>null)));
    for($i=0;$i<5;$i++) {
        $options=$send('zmp_passkey_login_options',array());
        if($options['secure']&&($options['json']['success']??null)===true) {
            $options_successes++; $id=$options['json']['data']['challenge_id']??'';
            if(is_string($id)&&$id!=='') {
                $owned[]=$id; $finish=$send('zmp_passkey_login_finish',array('challenge_id'=>$id,'credential'=>$payload));
                if($first_finish_error===null)$first_finish_error=$finish;
                $row=$lookup($id);
                if($generic($finish)&&is_array($row)&&!empty($row['consumed_at'])&&!is_user_logged_in())$consumed++;
            }
        }
    }
    $transient_present=get_transient($rate_key)!==false;
    delete_transient($rate_key); // Remove only the obsolete/irrelevant peer transient; durable DB quota must govern.
    $sixth_response=$send('zmp_passkey_login_options',array());
    if($sixth_response['secure']&&($sixth_response['json']['success']??null)===true) {
        $sixth_success=true; $id=$sixth_response['json']['data']['challenge_id']??''; if(is_string($id)&&$id!=='')$owned[]=$id;
    }
    $assert($options_successes===5&&$consumed===5,'five initial options challenges were accepted and consumed through generic invalid-finish responses');
    $assert(!$sixth_success&&$generic($sixth_response),'sixth issuance after deleting any peer transient is denied generically by durable DB quota');
    $assert(!is_user_logged_in()&&(int)get_current_user_id()===0,'invalid finishes never create a WordPress session');
    fwrite(STDOUT,'OBSERVED initial_options='.$options_successes.'; consumed='.$consumed.'; obsolete_transient_before_delete='.($transient_present?'present':'absent').'; sixth_options_success='.($sixth_success?'true':'false')."\n");
} catch(Throwable $e) { $failures[]='fixture execution error: '.get_class($e); }
finally {
    foreach(array_unique($owned) as $id)$wpdb->delete($ct,array('challenge_hash'=>hash('sha256',$id)),array('%s'));
    delete_transient($rate_key); if($prior_rate_state!==false){$ttl=$prior_rate_expiry>time()?$prior_rate_expiry-time():300;set_transient($rate_key,$prior_rate_state,$ttl);}
    wp_set_current_user($prior_user); $_POST=$prior_post; foreach($server_before as $k=>$state){if($state[0])$_SERVER[$k]=$state[1];else unset($_SERVER[$k]);}
}
$after=array($count($ut),$count($rt),$count($ct),$credential_fp()); $protected_after=$owner?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$owner,'Synthetic UI review only')):0;
$rate_restored=get_transient($rate_key)===$prior_rate_state;
$cleanup=$after[0]===$before[0]&&$after[1]===$before[1]&&$after[2]===$before[2]&&hash_equals($before[3],$after[3])&&$protected_before===$protected_after&&$rate_restored;
$checks[]='cleanup preserves users, credentials, challenge rows, protected UI row, transient state and request globals';if(!$cleanup)$failures[]='fixture cleanup changed a protected baseline';
if($failures){fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks)."): transient eviction quota\n");foreach($failures as $f)fwrite(STDERR,'- '.$f."\n");fwrite(STDOUT,'CLEANUP '.($cleanup?'PASS':'FAIL')."\n");exit(1);}
fwrite(STDOUT,'PASS ('.count($checks).'): durable quota survives transient eviction'."\nCLEANUP PASS: synthetic baselines and exact transient state restored\n");exit(0);
