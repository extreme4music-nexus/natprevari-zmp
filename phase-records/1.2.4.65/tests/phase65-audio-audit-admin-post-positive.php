<?php
/**
 * Candidate only. Do not run until exact-hash Code Review PASS and Lead direction.
 * Real authenticated admin-post audit; the clone-local MU guard intercepts the
 * exact synthetic probe URL and blocks every other outbound HTTP request.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_Approved_Audio_Tools')) {
    fwrite(STDERR,"BLOCKED: WordPress and audio service are required.\n"); exit(2);
}
global $wpdb;
if (!defined('DB_NAME') || DB_NAME!=='zmp65_audio_audit_test'||$wpdb->prefix!=='z65a_') {
    fwrite(STDERR,"BLOCKED: expected disposable clone DB zmp65_audio_audit_test / prefix z65a_.\n"); exit(2);
}
$site=wp_parse_url(home_url('/'));
if(!is_array($site)||($site['scheme']??'')!=='http'||($site['host']??'')!=='127.0.0.1'||(int)($site['port']??0)!==8096){
    fwrite(STDERR,"BLOCKED: expected audio-test clone at 127.0.0.1:8096.\n");exit(2);
}
$admin=get_user_by('login','zmp65_synthetic_admin');
$admin_id=$admin instanceof WP_User?absint($admin->ID):0;
if(!$admin_id||!user_can($admin_id,'manage_options')||substr(strrchr((string)$admin->user_email,'@')?:'',1)!=='example.invalid'){
    fwrite(STDERR,"BLOCKED: synthetic administrator fixture is unavailable.\n");exit(2);
}

$option=ZMP_Approved_Audio_Tools::OPTION;
$option_row=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1",$option),ARRAY_A);
if($wpdb->last_error!==''){fwrite(STDERR,"BLOCKED: audio option baseline unreadable.\n");exit(2);}
$option_existed=is_array($option_row);
$baseline=is_array($option_row)?maybe_unserialize($option_row['option_value']):array();
if(!is_array($baseline))$baseline=array();
$tool_id='zmp65-audit-route-403';
if(isset($baseline[$tool_id])){fwrite(STDERR,"BLOCKED: synthetic tool ID collision.\n");exit(2);}
$candidate_url='https://1.1.1.1/zmp65-audio-audit-positive';
$public_url=new ReflectionMethod('ZMP_Approved_Audio_Tools','public_url');$public_url->setAccessible(true);
if($public_url->invoke(null,$candidate_url)===''){fwrite(STDERR,"BLOCKED: synthetic public URL rejected by product validation.\n");exit(2);}

$endpoint=admin_url('admin-post.php');$ep=wp_parse_url($endpoint);
if(!is_array($ep)||($ep['scheme']??'')!=='http'||($ep['host']??'')!=='127.0.0.1'||(int)($ep['port']??0)!==8096
    ||($ep['path']??'')!=='/wp-admin/admin-post.php'||isset($ep['user'])||isset($ep['pass'])||isset($ep['query'])||isset($ep['fragment'])){
    fwrite(STDERR,"BLOCKED: route endpoint is not exact clone loopback.\n");exit(2);
}
$content=realpath(WP_CONTENT_DIR);$intended_mu=$content?untrailingslashit(wp_normalize_path($content.'/mu-plugins')):'';
$configured_mu=defined('WPMU_PLUGIN_DIR')?untrailingslashit(wp_normalize_path(WPMU_PLUGIN_DIR)):'';
$made_dir=false;$mu_identity=null;
$mu_dir=$configured_mu;
$remove_owned_mu_directory=static function()use(&$made_dir,&$mu_identity,$mu_dir,$intended_mu){
    if(!$made_dir)return true;
    if(!is_array($mu_identity)||!is_dir($mu_dir)||is_link($mu_dir)||realpath($mu_dir)!==$intended_mu)return false;
    $stat=@lstat($mu_dir);$owner=@fileowner($mu_dir);$entries=@scandir($mu_dir);
    if(!is_array($stat)||$stat['dev']!==$mu_identity['dev']||$stat['ino']!==$mu_identity['ino']
        ||$stat['uid']!==$mu_identity['uid']||$owner!==$mu_identity['uid']||!is_array($entries)
        ||count($entries)!==2)return false;
    if(!@rmdir($mu_dir))return false;
    $made_dir=false;return true;
};
if(!$content||!defined('WPMU_PLUGIN_DIR')||$configured_mu!==$intended_mu||is_link($configured_mu)){
    fwrite(STDERR,"BLOCKED: clone-local MU directory target is missing, linked, or outside wp-content.\n");exit(2);
}
if(!file_exists($configured_mu)){
    if(is_link($configured_mu)||!@mkdir($configured_mu,0755)){
        fwrite(STDERR,"BLOCKED: clone-local MU directory could not be created exclusively.\n");exit(2);
    }
    $made_dir=true;
}
$effective_mu=realpath($configured_mu);$mu_stat=@lstat($configured_mu);$content_stat=@lstat($content);
if($made_dir&&is_array($mu_stat))$mu_identity=array('dev'=>$mu_stat['dev'],'ino'=>$mu_stat['ino'],'uid'=>$mu_stat['uid']);
if(!$effective_mu||$effective_mu!==$intended_mu||!is_array($mu_stat)||!is_array($content_stat)
    ||$mu_stat['uid']!==$content_stat['uid']||strpos(wp_normalize_path($effective_mu),wp_normalize_path($content).'/')!==0){
    $removed=$remove_owned_mu_directory();
    fwrite(STDERR,$removed?"BLOCKED: clone-local MU directory failed canonical path/ownership checks.\n":"BLOCKED: clone-local MU directory failed canonical checks and owned cleanup was incomplete.\n");exit(2);
}
$mu_dir=$effective_mu;
$run_id=bin2hex(random_bytes(12));
$guard=$mu_dir.'/zmp65-audio-audit-route-guard.php';
$preflight_handshake=$mu_dir.'/.zmp65-audio-audit-preflight-'.$run_id;
$audit_handshake=$mu_dir.'/.zmp65-audio-audit-route-'.$run_id;
$events_prefix=$mu_dir.'/.zmp65-audio-audit-event-'.$run_id.'-';
$handshake_files=array($preflight_handshake=>'preflight:'.$run_id,$audit_handshake=>'audit:'.$run_id);
$guard_source="<?php\n\$audit_run_id=".var_export($run_id,true).";\$preflight_handshake=".var_export($preflight_handshake,true).";\$audit_handshake=".var_export($audit_handshake,true).";\$events_prefix=".var_export($events_prefix,true).";\n"
    ."\$write_once=static function(\$path,\$value){\$handle=@fopen(\$path,'x+b');if(\$handle===false)return false;\$created=@fstat(\$handle);if(!is_array(\$created)){fclose(\$handle);return false;}\$length=strlen(\$value);\$written=0;while(\$written<\$length){\$count=fwrite(\$handle,substr(\$value,\$written));if(!is_int(\$count)||\$count<1)break;\$written+=\$count;}\$flushed=fflush(\$handle);\$closed=fclose(\$handle);clearstatcache(true,\$path);\$current=@lstat(\$path);return \$written===\$length&&\$flushed&&\$closed&&!is_link(\$path)&&is_array(\$current)&&\$current['dev']===\$created['dev']&&\$current['ino']===\$created['ino']&&\$current['uid']===\$created['uid']&&@fileowner(\$path)===\$created['uid'];};\n"
    ."\$record_event=static function(\$name)use(\$events_prefix,\$audit_run_id){\$allowed=array('synthetic_403_get','unexpected_probe_method','unexpected_outbound_http','guard_handshake_missing');if(!in_array(\$name,\$allowed,true))return false;for(\$i=0;\$i<100;\$i++){\$path=\$events_prefix.\$i;if(is_link(\$path))continue;\$handle=@fopen(\$path,'x+b');if(\$handle===false)continue;\$created=@fstat(\$handle);if(!is_array(\$created)){fclose(\$handle);return false;}\$value=\$name.':'.\$audit_run_id;\$length=strlen(\$value);\$written=0;while(\$written<\$length){\$count=fwrite(\$handle,substr(\$value,\$written));if(!is_int(\$count)||\$count<1)break;\$written+=\$count;}\$flushed=fflush(\$handle);\$closed=fclose(\$handle);clearstatcache(true,\$path);\$current=@lstat(\$path);if(\$written===\$length&&\$flushed&&\$closed&&!is_link(\$path)&&is_array(\$current)&&\$current['dev']===\$created['dev']&&\$current['ino']===\$created['ino']&&\$current['uid']===\$created['uid']&&@fileowner(\$path)===\$created['uid'])return true;return false;}return false;};\n"
    ."\$request_path=(string)parse_url((string)(\$_SERVER['REQUEST_URI']??''),PHP_URL_PATH);\$request_method=strtoupper((string)(\$_SERVER['REQUEST_METHOD']??''));\$GLOBALS['zmp65_audio_audit_route_run']='';\n"
    ."if(\$request_method==='GET'&&\$request_path==='/wp-login.php'){if(!\$write_once(\$preflight_handshake,'preflight:'.\$audit_run_id))throw new RuntimeException('zmp65_audio_preflight_handshake_write_failed');}\n"
    ."elseif(\$request_method==='POST'&&\$request_path==='/wp-admin/admin-post.php'&&((\$_POST['action']??'')==='zmp_audio_tool_audit')&&((\$_POST['id']??'')==='zmp65-audit-route-403')){if(!\$write_once(\$audit_handshake,'audit:'.\$audit_run_id))throw new RuntimeException('zmp65_audio_audit_handshake_write_failed');\$GLOBALS['zmp65_audio_audit_route_run']=\$audit_run_id;}\n"
    ."add_filter('pre_http_request',static function(\$pre,\$args,\$url)use(\$audit_run_id,\$record_event){\n"
    ."if((string)(\$GLOBALS['zmp65_audio_audit_route_run']??'')!==\$audit_run_id){if(!\$record_event('guard_handshake_missing'))throw new RuntimeException('zmp65_audio_event_write_failed');return new WP_Error('zmp65_audio_guard_handshake_missing','The clone guard did not confirm this audit request.');}\n"
    ."\$target=".var_export($candidate_url,true).";if(\$url===\$target){\$method=strtoupper((string)(\$args['method']??'GET'));if(\$method!=='GET'){if(!\$record_event('unexpected_probe_method'))throw new RuntimeException('zmp65_audio_event_write_failed');return new WP_Error('zmp65_audio_probe_method_invalid','Unexpected probe method blocked.');}if(!\$record_event('synthetic_403_get'))throw new RuntimeException('zmp65_audio_event_write_failed');return array('headers'=>array(),'body'=>'Synthetic forbidden response','response'=>array('code'=>403,'message'=>'Forbidden'),'cookies'=>array(),'filename'=>null);}\n"
    ."if(!\$record_event('unexpected_outbound_http'))throw new RuntimeException('zmp65_audio_event_write_failed');return new WP_Error('zmp65_audio_test_http_blocked','Unexpected outbound HTTP blocked by disposable test guard.');\n"
    ."},0,3);\n";
$guard_hash=hash('sha256',$guard_source);$guard_created=false;$guard_identity=null;
$preexisting_event_paths=glob($events_prefix.'*');
if(!$effective_mu||$effective_mu!==$intended_mu
    ||strpos(wp_normalize_path($effective_mu),wp_normalize_path($content).'/')!==0
    ||is_link(WPMU_PLUGIN_DIR)||is_link($guard)||file_exists($guard)
    ||is_link($preflight_handshake)||file_exists($preflight_handshake)
    ||is_link($audit_handshake)||file_exists($audit_handshake)
    ||!is_array($preexisting_event_paths)||$preexisting_event_paths){
    $removed=$remove_owned_mu_directory();
    if(!$removed)fwrite(STDERR,"BLOCKED: owned clone-local MU directory cleanup was incomplete.\n");
    fwrite(STDERR,"BLOCKED: clone-local MU guard path is occupied, linked, or outside wp-content.\n");exit(2);
}
$existing_mu_php=array_values(array_filter((array)glob($mu_dir.'/*.php'),static function($path)use($guard){return $path!==$guard;}));
if($existing_mu_php){$removed=$remove_owned_mu_directory();if(!$removed)fwrite(STDERR,"BLOCKED: owned clone-local MU directory cleanup was incomplete.\n");fwrite(STDERR,"BLOCKED: other top-level MU plugins exist; cannot prove this guard loads before requests.\n");exit(2);}
$preflight_url=home_url('/wp-login.php');$preflight_parts=wp_parse_url($preflight_url);
if(!is_array($preflight_parts)||($preflight_parts['scheme']??'')!=='http'||($preflight_parts['host']??'')!=='127.0.0.1'
    ||(int)($preflight_parts['port']??0)!==8096||($preflight_parts['path']??'')!=='/wp-login.php'
    ||isset($preflight_parts['user'])||isset($preflight_parts['pass'])||isset($preflight_parts['query'])||isset($preflight_parts['fragment'])){
    $removed=$remove_owned_mu_directory();if(!$removed)fwrite(STDERR,"BLOCKED: owned clone-local MU directory cleanup was incomplete.\n");fwrite(STDERR,"BLOCKED: guard-load preflight URL is not exact clone loopback.\n");exit(2);
}

$tool=array('name'=>'Synthetic audio audit route','category'=>'vocal_remover','url'=>$candidate_url,
    'privacy_url'=>'https://1.1.1.1/zmp65-audio-audit-privacy','terms_url'=>'https://1.1.1.1/zmp65-audio-audit-terms',
    'free_limits'=>'Synthetic limits','duration_format_limits'=>'Synthetic format','quality_notes'=>'Synthetic quality',
    'rights_note'=>'Synthetic rights','processing_claim'=>'Synthetic processing statement','audit_state'=>'available',
    'http_status'=>200,'manual_status'=>'pending','auto_checked_at'=>'','needs_admin_attention'=>false);
$fixture=$baseline;$fixture[$tool_id]=$tool;
$token='';$session_ok=false;$seeded=false;$assertions=array();
$assert=static function($condition,$name)use(&$assertions){$assertions[$name]=$condition?'PASS':'FAIL';};
$loopback_posts=0;$preflight_requests=0;$unexpected_cli_http=0;
$cli_guard=static function($pre,$args,$url)use(&$loopback_posts,&$preflight_requests,&$unexpected_cli_http,$endpoint,$preflight_url,$tool_id){
    if($url===$preflight_url){$preflight_requests++;if(strtoupper((string)($args['method']??'GET'))!=='GET')return new WP_Error('zmp65_audio_test_bad_preflight','Only exact loopback GET preflight is allowed.');return $pre;}
    if($url===$endpoint){$loopback_posts++;$body=(array)($args['body']??array());
        if(strtoupper((string)($args['method']??'GET'))!=='POST'||($body['action']??'')!=='zmp_audio_tool_audit'||($body['id']??'')!==$tool_id){
            return new WP_Error('zmp65_audio_test_bad_admin_post','Unexpected admin-post payload blocked.');}
        return $pre;
    }
    $unexpected_cli_http++;return new WP_Error('zmp65_audio_test_cli_http_blocked','Unexpected CLI outbound HTTP blocked.');
};
$allowed_guard_events=array('synthetic_403_get','unexpected_probe_method','unexpected_outbound_http','guard_handshake_missing');
$event_identities=array();$handshake_identities=array();$preflight_guard_events=array();$audit_guard_events=array();
$read_owned_file=static function($path,$directory,$validator,&$identity,$remove=false){
    clearstatcache(true,$path);
    if(!file_exists($path)&&!is_link($path)){
        if($identity!==null)return array('ok'=>false,'content'=>'','identity'=>null);
        return array('ok'=>true,'content'=>'','identity'=>null);
    }
    if(is_link($path)||!is_file($path)||realpath($directory)!==$directory||realpath(dirname($path))!==$directory){
        return array('ok'=>false,'content'=>'','identity'=>null);
    }
    $before=@lstat($path);$owner=@fileowner($path);$directory_owner=@fileowner($directory);$handle=@fopen($path,'r+b');
    if(!is_array($before)||$handle===false||!flock($handle,LOCK_EX)){
        if(is_resource($handle))fclose($handle);
        return array('ok'=>false,'content'=>'','identity'=>null);
    }
    $opened=@fstat($handle);$current=@lstat($path);
    $same=is_array($opened)&&is_array($current)&&$opened['dev']===$before['dev']&&$opened['ino']===$before['ino']
        &&$opened['uid']===$before['uid']&&$current['dev']===$before['dev']&&$current['ino']===$before['ino']
        &&$current['uid']===$before['uid']&&$owner===$before['uid']&&$directory_owner===$before['uid'];
    if(is_array($identity))$same=$same&&$identity['dev']===$before['dev']&&$identity['ino']===$before['ino']&&$identity['uid']===$before['uid'];
    $raw=$same?stream_get_contents($handle):false;$valid=$same&&is_string($raw)&&$validator($raw);
    if($valid&&$remove){
        clearstatcache(true,$path);$latest=@lstat($path);
        $valid=is_array($latest)&&$latest['dev']===$before['dev']&&$latest['ino']===$before['ino']&&$latest['uid']===$before['uid']&&@unlink($path);
        if($valid)$identity=null;
    }elseif($valid){
        $identity=array('dev'=>$before['dev'],'ino'=>$before['ino'],'uid'=>$before['uid']);
    }
    flock($handle,LOCK_UN);fclose($handle);
    return array('ok'=>$valid,'content'=>is_string($raw)?$raw:'','identity'=>$valid&&!$remove?$identity:null);
};
$read_guard_events=static function($prefix,$directory,$run_id,$allowed,&$identities,$remove=false)use($read_owned_file){
    $paths=glob($prefix.'*');if(!is_array($paths)||count($paths)>100)return array('ok'=>false,'names'=>array());
    sort($paths,SORT_STRING);$names=array();$seen=array();
    foreach($paths as $path){
        $suffix=substr($path,strlen($prefix));
        if(!preg_match('/^[0-9]{1,3}$/',$suffix)||is_link($path))return array('ok'=>false,'names'=>$names);
        $event_name='';$validator=static function($raw)use($run_id,$allowed,&$event_name){
            $parts=explode(':',$raw,2);if(!isset($parts[1])||!hash_equals($run_id,$parts[1])||!in_array($parts[0],$allowed,true))return false;
            $event_name=$parts[0];return true;
        };
        $identity=$identities[$path]??null;$result=$read_owned_file($path,$directory,$validator,$identity,$remove);
        if(empty($result['ok']))return array('ok'=>false,'names'=>$names);
        $names[]=$event_name;$seen[$path]=true;
        if($remove)unset($identities[$path]);else $identities[$path]=$identity;
    }
    foreach(array_keys($identities) as $known_path){if(!isset($seen[$known_path]))return array('ok'=>false,'names'=>$names);}
    return array('ok'=>true,'names'=>$names);
};
$restore=static function()use($wpdb,$option,$option_row,$option_existed,$admin_id,&$token,$guard,$guard_hash,&$guard_created,&$guard_identity,$handshake_files,&$handshake_identities,$events_prefix,$run_id,$mu_dir,$remove_owned_mu_directory,$allowed_guard_events,&$event_identities,$read_guard_events,$read_owned_file){
    $ok=true;
    if($option_existed){$ok=$wpdb->update($wpdb->options,array('option_value'=>$option_row['option_value'],'autoload'=>$option_row['autoload']),array('option_name'=>$option),array('%s','%s'),array('%s'))!==false&&$wpdb->last_error==='';}
    else{$ok=$wpdb->delete($wpdb->options,array('option_name'=>$option),array('%s'))!==false&&$wpdb->last_error==='';}
    wp_cache_delete($option,'options');wp_cache_delete('alloptions','options');
    if($token!==''){WP_Session_Tokens::get_instance($admin_id)->destroy($token);$ok=$ok&&!WP_Session_Tokens::get_instance($admin_id)->verify($token);}
    if($guard_created){
        $stat=is_link($guard)?false:@lstat($guard);$owner=is_link($guard)?false:@fileowner($guard);
        $same_identity=is_array($stat)&&is_array($guard_identity)&&$stat['dev']===$guard_identity['dev']
            &&$stat['ino']===$guard_identity['ino']&&$stat['uid']===$guard_identity['uid']
            &&$owner===$guard_identity['uid'];
        if($same_identity&&hash_equals($guard_hash,(string)hash_file('sha256',$guard)))$ok=@unlink($guard)&&$ok;else $ok=false;
    }
    $event_cleanup=$read_guard_events($events_prefix,$mu_dir,$run_id,$allowed_guard_events,$event_identities,true);
    $ok=!empty($event_cleanup['ok'])&&$ok;
    foreach($handshake_files as $path=>$expected_value){
        $identity=$handshake_identities[$path]??null;
        $validator=static function($raw)use($expected_value){return hash_equals($expected_value,$raw);};
        $handshake_cleanup=$read_owned_file($path,$mu_dir,$validator,$identity,true);
        $ok=!empty($handshake_cleanup['ok'])&&$ok;
    }
    $ok=$remove_owned_mu_directory()&&$ok;
    $after=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s LIMIT 1",$option),ARRAY_A);
    if($option_existed)$ok=$ok&&is_array($after)&&(string)$after['option_value']===(string)$option_row['option_value']&&(string)$after['autoload']===(string)$option_row['autoload'];
    else $ok=$ok&&$after===null;
    return $ok&&$wpdb->last_error==='';
};

try{
    $guard_handle=@fopen($guard,'x+b');
    if($guard_handle===false)throw new RuntimeException('mu_guard_exclusive_create_failed');
    $guard_created=true;
    $created_stat=@fstat($guard_handle);
    if(!is_array($created_stat)){fclose($guard_handle);throw new RuntimeException('mu_guard_identity_unavailable');}
    $guard_identity=array('dev'=>$created_stat['dev'],'ino'=>$created_stat['ino'],'uid'=>$created_stat['uid']);
    $written=0;$source_length=strlen($guard_source);
    while($written<$source_length){$count=fwrite($guard_handle,substr($guard_source,$written));if(!is_int($count)||$count<1)break;$written+=$count;}
    $flushed=fflush($guard_handle);$closed=fclose($guard_handle);$guard_stat=@lstat($guard);$guard_owner=@fileowner($guard);
    $guard_identity_ok=is_array($guard_stat)&&$guard_stat['dev']===$guard_identity['dev']&&$guard_stat['ino']===$guard_identity['ino']
        &&$guard_stat['uid']===$guard_identity['uid']&&$guard_owner===$guard_identity['uid']&&!is_link($guard);
    if($written!==$source_length||!$flushed||!$closed||!$guard_identity_ok
        ||!hash_equals($guard_hash,(string)hash_file('sha256',$guard)))throw new RuntimeException('mu_guard_write_or_identity_failed');
    add_filter('pre_http_request',$cli_guard,0,3);
    $preflight=wp_remote_get($preflight_url,array('redirection'=>0,'timeout'=>12));
    $preflight_status=is_wp_error($preflight)?0:(int)wp_remote_retrieve_response_code($preflight);
    $preflight_handshake_identity=null;
    $preflight_handshake_result=$read_owned_file($preflight_handshake,$mu_dir,static function($raw)use($run_id){return hash_equals('preflight:'.$run_id,$raw);},$preflight_handshake_identity,false);
    if(is_array($preflight_handshake_identity))$handshake_identities[$preflight_handshake]=$preflight_handshake_identity;
    $preflight_handshake_value=(string)($preflight_handshake_result['content']??'');
    $assert(!is_wp_error($preflight)&&$preflight_status===200&&!empty($preflight_handshake_result['ok'])&&$preflight_handshake_value===('preflight:'.$run_id)
        &&$preflight_requests===1,'effective_mu_guard_loaded_on_harmless_loopback_preflight');
    if(is_wp_error($preflight)||$preflight_status!==200||empty($preflight_handshake_result['ok'])||$preflight_handshake_value!==('preflight:'.$run_id)||$preflight_requests!==1){
        throw new RuntimeException('guard_load_preflight_unverified');
    }
    $preflight_events_result=$read_guard_events($events_prefix,$mu_dir,$run_id,$allowed_guard_events,$event_identities,true);
    $preflight_guard_events=is_array($preflight_events_result['names']??null)?$preflight_events_result['names']:array();
    $assert(!empty($preflight_events_result['ok']),'preflight_guard_events_were_allowlisted_and_cleared');
    if(empty($preflight_events_result['ok']))throw new RuntimeException('preflight_guard_event_clear_unverified');
    $preflight_handshake_cleanup_identity=$handshake_identities[$preflight_handshake]??$preflight_handshake_identity;
    $preflight_handshake_cleanup=$read_owned_file($preflight_handshake,$mu_dir,static function($raw)use($run_id){return hash_equals('preflight:'.$run_id,$raw);},$preflight_handshake_cleanup_identity,true);
    unset($handshake_identities[$preflight_handshake]);
    if(empty($preflight_handshake_cleanup['ok']))throw new RuntimeException('preflight_handshake_clear_failed');
    if(update_option($option,$fixture,false)===false&&get_option($option)!==$fixture)throw new RuntimeException('synthetic_fixture_seed_failed');
    $seeded=true;wp_cache_delete($option,'options');wp_cache_delete('alloptions','options');
    $expires=time()+600;$token=WP_Session_Tokens::get_instance($admin_id)->create($expires);
    if(!is_string($token)||$token==='')throw new RuntimeException('temporary_admin_session_failed');
    $auth=wp_generate_auth_cookie($admin_id,$expires,'auth',$token);$logged=wp_generate_auth_cookie($admin_id,$expires,'logged_in',$token);
    if(!$auth||!$logged)throw new RuntimeException('temporary_admin_cookie_failed');
    $_COOKIE[LOGGED_IN_COOKIE]=$logged;wp_set_current_user($admin_id);$nonce=wp_create_nonce('zmp_audio_tool_audit_'.$tool_id);
    $response=wp_remote_post($endpoint,array('headers'=>array('Cookie'=>AUTH_COOKIE.'='.$auth.'; '.LOGGED_IN_COOKIE.'='.$logged),
        'body'=>array('action'=>'zmp_audio_tool_audit','id'=>$tool_id,'_wpnonce'=>$nonce),'redirection'=>0,'timeout'=>15));
    $assert(!is_wp_error($response),'actual_loopback_admin_post_completed');
    if(is_wp_error($response))throw new RuntimeException('loopback_admin_post_failed');
    $status=(int)wp_remote_retrieve_response_code($response);$location=(string)wp_remote_retrieve_header($response,'location');
    $redirect=wp_parse_url($location);$redirect_query=array();if(is_array($redirect)&&isset($redirect['query']))parse_str($redirect['query'],$redirect_query);
    $assert($status===302&&is_array($redirect)&&($redirect['scheme']??'')==='http'&&($redirect['host']??'')==='127.0.0.1'
        &&(int)($redirect['port']??0)===8096&&($redirect['path']??'')==='/wp-admin/admin.php'
        &&$redirect_query===array('page'=>'zmp-approved-audio-tools','notice'=>'audited'),'exact_admin_post_redirect_target_and_notice');
    $assert($loopback_posts===1,'exactly_one_authenticated_admin_post');
    $fresh=$wpdb->get_row($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s LIMIT 1",$option),ARRAY_A);
    $saved=is_array($fresh)?maybe_unserialize($fresh['option_value']):array();$saved_tool=is_array($saved)?($saved[$tool_id]??array()):array();
    $assert(($saved_tool['audit_state']??'')==='probe_denied'&&(int)($saved_tool['http_status']??0)===403,'persisted_probe_denied_and_403');
    $assert(($saved_tool['manual_status']??'')==='pending','automated_403_does_not_approve_tool');
    $audit_handshake_identity=null;
    $audit_handshake_result=$read_owned_file($audit_handshake,$mu_dir,static function($raw)use($run_id){return hash_equals('audit:'.$run_id,$raw);},$audit_handshake_identity,false);
    if(is_array($audit_handshake_identity))$handshake_identities[$audit_handshake]=$audit_handshake_identity;
    $route_handshake=(string)($audit_handshake_result['content']??'');
    $audit_events_result=$read_guard_events($events_prefix,$mu_dir,$run_id,$allowed_guard_events,$event_identities,false);
    $audit_guard_events=is_array($audit_events_result['names']??null)?$audit_events_result['names']:array();
    $assert(!empty($audit_handshake_result['ok'])&&$route_handshake===('audit:'.$run_id),'actual_admin_post_reloaded_effective_mu_guard');
    $assert(!empty($audit_events_result['ok'])&&$audit_guard_events===array('synthetic_403_get'),'route_guard_intercepted_only_exact_synthetic_GET_and_no_external_http');
    wp_cache_delete($option,'options');wp_cache_delete('alloptions','options');
    $_GET['page']=ZMP_Approved_Audio_Tools::PAGE;ob_start();ZMP_Approved_Audio_Tools::render_admin_page();$html=(string)ob_get_clean();unset($_GET['page']);
    $assert(strpos($html,'Серверската проверка е одбиена — не е потврдено дека линкот е скршен')!==false
        &&strpos($html,'не докажува дека линкот е скршен')!==false,'rendered_label_explains_probe_denial_does_not_prove_broken_link');
}catch(Throwable $e){if(ob_get_level()>0)ob_end_clean();$assertions['execution']='FAIL';fwrite(STDERR,'audio_audit_route:'.sanitize_key($e->getMessage())."\n");}
finally{
    remove_filter('pre_http_request',$cli_guard,0);foreach(array(LOGGED_IN_COOKIE,AUTH_COOKIE)as $cookie_name)unset($_COOKIE[$cookie_name]);wp_set_current_user(0);
    $restored=$restore();$session_ok=$restored;$assert($restored,'cleanup_restored_exact_audio_option_autoload_session_and_guard');
}
$failures=array_keys(array_filter($assertions,static function($result){return$result!=='PASS';}));
echo wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures,'loopback_admin_post_count'=>$loopback_posts,
    'unexpected_cli_http_count'=>$unexpected_cli_http,'preflight_guard_events'=>$preflight_guard_events,
    'audit_guard_events'=>$audit_guard_events,'external_probe'=>'synthetic 403 intercepted in clone MU guard; no network'),JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($failures?1:0);
