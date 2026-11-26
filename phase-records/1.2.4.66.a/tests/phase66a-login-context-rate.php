<?php
/** TDD red coverage for passkey login origin binding and anonymous challenge volume (.66.a). */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) { fwrite(STDERR,"BLOCKED: passkey components/schema unavailable\n"); exit(2); }
global $wpdb;
$ct=$wpdb->prefix.'zmp_passkey_challenges'; $rt=$wpdb->prefix.'zmp_passkey_credentials'; $ut=$wpdb->users;
$checks=array(); $failures=array(); $owned=array(); $original_user=(int)get_current_user_id(); $ip='127.0.0.77'; $packed_ip=@inet_pton($ip); $peer_hash=is_string($packed_ip)?hash_hmac('sha256',inet_ntop($packed_ip),wp_salt('auth')):''; $rate_key='zmp_passkey_login_rate_'.$peer_hash;
$before_transient=get_transient($rate_key); delete_transient($rate_key);
$count=static function($table) use($wpdb){return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");};
$cred_fp=static function() use($wpdb,$rt){$rows=$wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$rt} ORDER BY credential_id_hash",ARRAY_A);return hash('sha256',serialize(is_array($rows)?$rows:array()));};
$base=array($count($ut),$count($rt),$count($ct),$cred_fp());
$protected_user=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$ut} WHERE user_login=%s",'zmp66a_admin'));
$protected_before=$protected_user?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$protected_user,'Synthetic UI review only')):0;
$assert=static function($ok,$name)use(&$checks,&$failures){$checks[]=$name;if(!$ok)$failures[]=$name;};
$origin_parts=wp_parse_url(home_url('/')); $scheme=strtolower((string)($origin_parts['scheme']??'http')); $rp=strtolower(rtrim((string)($origin_parts['host']??'127.0.0.1'),'.')); $port=isset($origin_parts['port'])?(int)$origin_parts['port']:null; $is_default=($scheme==='http'&&$port===80)||($scheme==='https'&&$port===443); $origin=$scheme.'://'.$rp.($port!==null&&!$is_default?':'.$port:'');
$context_hash=static function($o,$r){return hash('sha256',"zmp-passkey-login-origin-v1\n".$o."\n".$r);};
$lookup=static function($id)use($wpdb,$ct){return $wpdb->get_row($wpdb->prepare("SELECT ceremony,context_hash,consumed_at,expires_at FROM {$ct} WHERE challenge_hash=%s",hash('sha256',$id)),ARRAY_A);};
$send=static function($action,$post)use($ip,$origin) {
    $parts=wp_parse_url($origin); $host=(string)($parts['host']??'127.0.0.1'); $port=isset($parts['port'])?(int)$parts['port']:80;
    $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']='http'; $_SERVER['SERVER_PORT']=(string)$port; unset($_SERVER['HTTPS']);
    $_SERVER['HTTP_ORIGIN']=$origin; $_SERVER['HTTP_REFERER']=$origin.'/wp-admin/admin-ajax.php'; $_SERVER['REMOTE_ADDR']=$ip; $_SERVER['REQUEST_METHOD']='POST'; $_POST=array_merge(array('action'=>$action),$post);
    $secure=ZMP_Passkeys::is_secure_request_context(); $status=null; $die=null;
    $status_cb=static function($h,$c)use(&$status){$status=is_numeric($c)?(int)$c:null;return $h;};
    $die_cb=static function()use(&$die){return static function($m='',$t='',$a=array())use(&$die){$die=is_array($a)&&isset($a['response'])?(int)$a['response']:null;throw new RuntimeException('request-complete');};};
    $ajax=static function(){return true;}; add_filter('wp_doing_ajax',$ajax); add_filter('wp_die_ajax_handler',$die_cb); add_filter('wp_die_json_handler',$die_cb); add_filter('status_header',$status_cb,10,2);
    ob_start(); try { $hook=$action==='zmp_passkey_login_options'?'wp_ajax_nopriv_zmp_passkey_login_options':'wp_ajax_nopriv_zmp_passkey_login_finish'; do_action($hook); } catch(Throwable $e) { }
    $body=(string)ob_get_clean(); remove_filter('wp_doing_ajax',$ajax); remove_filter('wp_die_ajax_handler',$die_cb); remove_filter('wp_die_json_handler',$die_cb); remove_filter('status_header',$status_cb,10);
    return array('json'=>json_decode($body,true),'status'=>$status,'die'=>$die,'secure'=>$secure);
};
try {
    wp_set_current_user(0);
    // Explicit acceptance policy: <=5 pending anonymous challenges per verified source IP per 300-second challenge TTL.
    $successes=0; $rate_ids=array();
    for($i=0;$i<12;$i++) {
        $response=$send('zmp_passkey_login_options',array()); $data=$response['json']['data']??array();
        if(($response['json']['success']??null)===true) { $successes++; $id=$data['challenge_id']??''; if(is_string($id)&&$id!==''){$owned[]=$id;$rate_ids[]=$id;} }
    }
    $pending=0; foreach($rate_ids as $id){$row=$lookup($id);if(is_array($row)&&$row['consumed_at']===null)$pending++;}
    $assert($successes<=5&&$pending<=5,'12 real anonymous options requests from one synthetic IP create at most five pending challenges per 300-second window');
    foreach($rate_ids as $id) $wpdb->delete($ct,array('challenge_hash'=>hash('sha256',$id)),array('%s'));
    $owned=array(); delete_transient($rate_key);

    // Issue-time origin/RP context must be persisted in the challenge context hash.
    $issued=$send('zmp_passkey_login_options',array()); $idata=$issued['json']['data']??array(); $issued_id=$idata['challenge_id']??'';
    if(is_string($issued_id)&&$issued_id!=='') {
        $owned[]=$issued_id; $row=$lookup($issued_id); $opt=$idata['options']??array();
        $assert(($opt['rpId']??null)===$rp,'request options expose the configured RP ID');
        $assert(is_array($row)&&hash_equals($context_hash($origin,$rp),(string)($row['context_hash']??'')),'challenge persists the canonical issue-time origin/RP ID context digest');

        // Simulate configuration drift by changing only this fixture-owned row to the digest
        // for another valid loopback origin. The request itself remains exact same-origin.
        $drift_hash=$context_hash('http://localhost:8101','localhost');
        $wpdb->update($ct,array('context_hash'=>$drift_hash),array('challenge_hash'=>hash('sha256',$issued_id)),array('%s'),array('%s'));
        $finish=$send('zmp_passkey_login_finish',array('challenge_id'=>$issued_id,'credential'=>'{"invalid":"synthetic"}'));
        $after=$lookup($issued_id);
        $assert($finish['secure']===true,'configuration-drift case retains a valid same-origin browser request');
        $assert(is_array($finish['json'])&&($finish['json']['success']??null)===false&&!is_user_logged_in(),'issue-time origin/RP mismatch is denied without creating a login session');
        $assert(is_array($after)&&($after['consumed_at']??null)===null,'issue-time origin/RP mismatch is rejected before consuming its challenge');
    } else { $assert(false,'real anonymous options endpoint creates a challenge for origin-context test'); }
} catch(Throwable $e) { $failures[]='fixture error: '.get_class($e); }
finally {
    foreach(array_unique($owned) as $id)$wpdb->delete($ct,array('challenge_hash'=>hash('sha256',$id)),array('%s'));
    delete_transient($rate_key); if($before_transient!==false)set_transient($rate_key,$before_transient,300);
    wp_set_current_user($original_user);
}
$after=array($count($ut),$count($rt),$count($ct),$cred_fp());
$protected_after=$protected_user?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$protected_user,'Synthetic UI review only')):0;
$clean=$after[0]===$base[0]&&$after[1]===$base[1]&&$after[2]===$base[2]&&hash_equals($base[3],$after[3])&&$protected_before===$protected_after;
$checks[]='cleanup preserves user, credential fingerprint, challenge count and protected UI credential';if(!$clean)$failures[]='synthetic baseline changed during fixture';
if($failures){fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks)."): login origin/rate policy\n");foreach($failures as $f)fwrite(STDERR,'- '.$f."\n");fwrite(STDOUT,'CLEANUP '.($clean?'PASS':'FAIL')."\n");exit(1);}
fwrite(STDOUT,'PASS ('.count($checks).'): login origin binding and challenge-volume policy'."\nCLEANUP PASS: synthetic baselines and protected UI credential unchanged\n");exit(0);
