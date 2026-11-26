<?php
/** Anonymous passkey assertion failure-path checks for .66.a synthetic WordPress site. */
if (!defined('ABSPATH') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: .66.a passkey API/schema unavailable\n"); exit(2);
}
global $wpdb;
$ct = $wpdb->prefix . 'zmp_passkey_challenges'; $rt = $wpdb->prefix . 'zmp_passkey_credentials'; $ut = $wpdb->users;
$checks = array(); $failures = array(); $owned = array(); $user_before = get_current_user_id(); $post_before = $_POST;
$keys = array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD');
$server_before = array(); foreach ($keys as $k) { $server_before[$k] = array_key_exists($k,$_SERVER) ? array(true,$_SERVER[$k]) : array(false,null); }
$count = static function($table) use ($wpdb) { return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}"); };
$cred_fp = static function() use ($wpdb,$rt) { $rows=$wpdb->get_results("SELECT user_id,credential_id_hash,label,credential_record FROM {$rt} ORDER BY credential_id_hash",ARRAY_A); return hash('sha256',serialize(is_array($rows)?$rows:array())); };
$before = array($count($ut),$count($rt),$cred_fp());
$challenge_rows_before = $wpdb->get_results("SELECT * FROM {$ct} ORDER BY challenge_hash", ARRAY_A);
if (!is_array($challenge_rows_before)) $challenge_rows_before = array();
$protected_user=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$ut} WHERE user_login=%s",'zmp66a_admin'));
$protected_before=$protected_user ? (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$protected_user,'Synthetic UI review only')) : 0;
$assert=static function($ok,$label) use (&$checks,&$failures) { $checks[]=$label; if(!$ok)$failures[]=$label; };
$home_parts=wp_parse_url(home_url('/')); $scheme=strtolower((string)($home_parts['scheme']??'http')); $rp_id=strtolower(rtrim((string)($home_parts['host']??'127.0.0.1'),'.')); $port=isset($home_parts['port'])?(int)$home_parts['port']:null; $default_port=($scheme==='http'&&$port===80)||($scheme==='https'&&$port===443); $canonical_origin=$scheme.'://'.$rp_id.($port!==null&&!$default_port?':'.$port:'');
$issue_ip='127.0.0.31'; $issue_packed=@inet_pton($issue_ip); $issue_peer_hash=is_string($issue_packed)?hash_hmac('sha256',inet_ntop($issue_packed),wp_salt('auth')):'';
$login_binding=static function()use($canonical_origin,$rp_id,$issue_peer_hash){return array('user_id'=>0,'session_hash'=>'','ceremony'=>'login','context_hash'=>hash('sha256',"zmp-passkey-login-origin-v1\n".$canonical_origin."\n".$rp_id),'peer_hash'=>$issue_peer_hash);};
$lookup=static function($id) use($wpdb,$ct) { return $wpdb->get_row($wpdb->prepare("SELECT ceremony,peer_hash,consumed_at,expires_at FROM {$ct} WHERE challenge_hash=%s",hash('sha256',$id)),ARRAY_A); };
$has_private_key=null; $has_private_key=static function($v) use (&$has_private_key) { if(!is_array($v)&&!is_object($v)) return false; foreach((array)$v as $k=>$item) { $n=strtolower(preg_replace('/[^a-z0-9]/i','',(string)$k)); if(in_array($n,array('account','email','username','userid','userhandle','displayname','user_id'),true)||$has_private_key($item)) return true; } return false; };
$invoke=static function($id,$payload,$origin) use($assert) {
    $saved=array(); foreach(array('HTTP_HOST','REQUEST_SCHEME','SERVER_PORT','HTTPS','HTTP_ORIGIN','HTTP_REFERER','REMOTE_ADDR','REQUEST_METHOD') as $k) $saved[$k]=array_key_exists($k,$_SERVER)?array(true,$_SERVER[$k]):array(false,null);
    $parts=wp_parse_url(home_url('/')); $host=(string)($parts['host']??'127.0.0.1'); $port=isset($parts['port'])?(int)$parts['port']:80;
    $_SERVER['HTTP_HOST']=$host.':'.$port; $_SERVER['REQUEST_SCHEME']='http'; $_SERVER['SERVER_PORT']=(string)$port; unset($_SERVER['HTTPS']);
    $origin_parts=wp_parse_url($origin); $origin=rtrim((string)($origin_parts['scheme']??'http'),'://').'://'.(string)($origin_parts['host']??$host).(isset($origin_parts['port'])?':'.(int)$origin_parts['port']:'');
    $_SERVER['HTTP_ORIGIN']=$origin; $_SERVER['HTTP_REFERER']=home_url('/'); $_SERVER['REMOTE_ADDR']='127.0.0.32'; $_SERVER['REQUEST_METHOD']='POST';
    $_POST=array('action'=>'zmp_passkey_login_finish','challenge_id'=>$id,'credential'=>$payload);
    $status=null; $die_status=null; $status_cb=static function($h,$c) use (&$status) { $status=is_numeric($c)?(int)$c:null; return $h; };
    $die_cb=static function() use (&$die_status) { return static function($m='',$t='',$a=array()) use (&$die_status) { $die_status=is_array($a)&&isset($a['response'])?(int)$a['response']:null; throw new RuntimeException('ajax-done'); }; };
    $ajax=static function(){return true;}; add_filter('wp_doing_ajax',$ajax); add_filter('wp_die_ajax_handler',$die_cb); add_filter('wp_die_json_handler',$die_cb); add_filter('status_header',$status_cb,10,2);
    ob_start(); try { do_action('wp_ajax_nopriv_zmp_passkey_login_finish'); } catch(Throwable $e) { if($e->getMessage()!=='ajax-done') $assert(false,'controller exits through captured JSON response'); }
    $body=(string)ob_get_clean(); remove_filter('wp_doing_ajax',$ajax); remove_filter('wp_die_ajax_handler',$die_cb); remove_filter('wp_die_json_handler',$die_cb); remove_filter('status_header',$status_cb,10);
    foreach($saved as $k=>$s) { if($s[0])$_SERVER[$k]=$s[1]; else unset($_SERVER[$k]); } $_POST=array();
    return array('json'=>json_decode($body,true),'status'=>$status,'die'=>$die_status);
};
try {
    wp_set_current_user(0); $assert(!is_user_logged_in()&&(int)get_current_user_id()===0,'flow begins anonymous');
    $hook=has_action('wp_ajax_nopriv_zmp_passkey_login_finish',array('ZMP_Passkeys','ajax_login_finish'))!==false;
    $assert($hook,'anonymous finish endpoint is registered on WordPress AJAX hook');
    if($hook) {
        $id=ZMP_Passkey_Store::create_challenge($login_binding());
        if(is_string($id)&&$id!=='') {
            $owned[]=$id; $row=$lookup($id); $assert(is_array($row)&&$row['ceremony']==='login'&&$row['consumed_at']===null&&hash_equals($issue_peer_hash,(string)$row['peer_hash']),'synthetic login challenge stores the keyed issuance peer and begins unused');
            $payload=wp_json_encode(array('id'=>'synthetic-invalid','rawId'=>'c3ludGhldGljLWludmFsaWQ','type'=>'public-key','response'=>array('clientDataJSON'=>'e30','authenticatorData'=>'AA','signature'=>'AA','userHandle'=>null)));
            $res=$invoke($id,$payload,home_url('/')); $after=$lookup($id); $body=$res['json'];
            $assert($res['status']===400,'invalid assertion sets HTTP 400 status header');
            $assert(is_array($body)&&($body['success']??null)===false,'invalid assertion returns JSON error body');
            $assert(is_array($body)&&array_keys($body)===array('success','data')&&isset($body['data']['message'])&&$body['data']['message']===__('Passkey sign-in could not be completed. Try again or use your password.','zmp-competitions')&&!$has_private_key($body),'failure uses fixed generic shape without account fields');
            $assert(!is_user_logged_in()&&(int)get_current_user_id()===0,'invalid assertion creates no WordPress login session');
            $assert(is_array($after)&&!empty($after['consumed_at']),'invalid assertion consumes its one-use challenge');
            $replay=$invoke($id,$payload,home_url('/')); $assert(is_array($replay['json'])&&($replay['json']['success']??null)===false&&$replay['status']===400&&!is_user_logged_in(),'replay is generically denied without session');
        } else $assert(false,'synthetic login challenge created');
        foreach(array(array('login','http://wrong-origin.invalid','wrong origin'),array('registration',home_url('/'),'wrong ceremony')) as $case) {
            $other=ZMP_Passkey_Store::create_challenge($login_binding());
            if(is_string($other)&&$other!==''&&$case[0]==='registration') $wpdb->update($ct,array('user_id'=>$protected_user,'session_hash'=>hash('sha256','phase66a-valid-registration-shape'),'ceremony'=>'registration','context_hash'=>'','peer_hash'=>''),array('challenge_hash'=>hash('sha256',$other)),array('%d','%s','%s','%s','%s'),array('%s'));
            if(is_string($other)&&$other!=='') { $owned[]=$other; $r=$invoke($other,'{"not":"credential"}',$case[1]); $row=$lookup($other); $assert(is_array($r['json'])&&($r['json']['success']??null)===false&&$r['status']===400&&is_array($row)&&$row['consumed_at']===null,$case[2].' is denied without consuming the challenge'); }
            else $assert(false,$case[2].' synthetic challenge created');
        }
        $expired=ZMP_Passkey_Store::create_challenge($login_binding());
        if(is_string($expired)&&$expired!=='') { $owned[]=$expired; $wpdb->update($ct,array('expires_at'=>gmdate('Y-m-d H:i:s',time()-60)),array('challenge_hash'=>hash('sha256',$expired)),array('%s'),array('%s')); $r=$invoke($expired,'{"not":"credential"}',home_url('/')); $row=$lookup($expired); $assert(is_array($r['json'])&&($r['json']['success']??null)===false&&$r['status']===400&&is_array($row)&&$row['consumed_at']===null,'expired challenge is denied without session or consumption'); }
        else $assert(false,'expired synthetic challenge created');
    }
} catch(Throwable $e) { $failures[]='fixture execution error: '.get_class($e); }
finally {
    foreach(array_unique($owned) as $id) $wpdb->delete($ct,array('challenge_hash'=>hash('sha256',$id)),array('%s'));
    wp_set_current_user((int)$user_before); $_POST=$post_before; foreach($server_before as $k=>$s) { if($s[0])$_SERVER[$k]=$s[1]; else unset($_SERVER[$k]); }
}
$after=array($count($ut),$count($rt),$cred_fp()); $protected_after=$protected_user?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rt} WHERE user_id=%d AND label=%s",$protected_user,'Synthetic UI review only')):0;
$remaining_challenge_rows=$wpdb->get_results("SELECT * FROM {$ct} ORDER BY challenge_hash", ARRAY_A);
if (!is_array($remaining_challenge_rows)) $remaining_challenge_rows = array();
$remaining_by_hash=array(); foreach($remaining_challenge_rows as $row) $remaining_by_hash[(string)$row['challenge_hash']]=$row;
$baseline_by_hash=array(); foreach($challenge_rows_before as $row) $baseline_by_hash[(string)$row['challenge_hash']]=$row;
$owned_hashes=array(); foreach(array_unique($owned) as $id) $owned_hashes[hash('sha256',$id)]=true;
$challenge_cleanup=true;
foreach($baseline_by_hash as $hash=>$row){
    if(isset($remaining_by_hash[$hash])){if(serialize($remaining_by_hash[$hash])!==serialize($row))$challenge_cleanup=false;continue;}
    $expiry=strtotime((string)($row['expires_at']??'').' UTC');
    if($expiry===false||$expiry>time())$challenge_cleanup=false;
}
foreach($remaining_by_hash as $hash=>$row){if(!isset($baseline_by_hash[$hash])&&!isset($owned_hashes[$hash]))$challenge_cleanup=false;}
$baseline=$after[0]===$before[0]&&$after[1]===$before[1]&&hash_equals($before[2],$after[2])&&$protected_before===$protected_after;
$checks[]='cleanup preserves users, credentials and protected UI row'; if(!$baseline)$failures[]='user/credential baseline changed';
$checks[]='owned challenges removed; unrelated live challenge rows preserved; only expired baseline rows may be cleaned'; if(!$challenge_cleanup)$failures[]='challenge cleanup changed a live/unowned row';
if($failures) { fwrite(STDERR,'FAIL ('.count($failures).'/'.count($checks)."): passkey assertion login\n"); foreach($failures as $f)fwrite(STDERR,'- '.$f."\n"); fwrite(STDOUT,'CLEANUP '.($baseline?'PASS':'FAIL')."\nVALID ASSERTION / COUNTER PERSISTENCE: NOT RUN\n"); exit(1); }
fwrite(STDOUT,'PASS ('.count($checks).'): passkey assertion login denial behavior'."\nCLEANUP PASS: all synthetic baselines and protected UI credential unchanged\nVALID ASSERTION / COUNTER PERSISTENCE: NOT RUN (no reusable pinned-library fixture identified; no custom signature verification written)\n"); exit(0);
