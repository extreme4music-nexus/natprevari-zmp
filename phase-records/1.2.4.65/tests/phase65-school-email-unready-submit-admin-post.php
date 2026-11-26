<?php
/**
 * Candidate only. Do not run until exact-hash Code Review PASS and Lead direction.
 * This is bound to a disposable clone, never the active `.65` site/database.
 * It tests generic fail-closed behavior for a synthetic Teacher admin-post.
 */
global $wpdb;
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_unready_email_test'
    || !isset($wpdb) || $wpdb->prefix !== 'z65u_') {
    fwrite(STDERR,"BLOCKED: expected disposable clone DB zmp65_unready_email_test / prefix z65u_.\n"); exit(2);
}
$site=wp_parse_url(home_url('/'));
if (!is_array($site)||($site['scheme']??'')!=='http'||($site['host']??'')!=='127.0.0.1'
    ||(int)($site['port']??0)!==8095) { fwrite(STDERR,"BLOCKED: expected clone loopback URL on port 8095.\n"); exit(2); }
if (!class_exists('ZMP_School_Email_Access')||!class_exists('ZMP_Mentor_School_Profile')
    ||!class_exists('ZMP_Mentor_Schools')) { fwrite(STDERR,"BLOCKED: required plugin services unavailable.\n"); exit(2); }

// These fixture IDs must be verified as synthetic Teachers in the clone.
$requester_id=31; $owner_id=30;
$requester=ZMP_Mentor_School_Profile::mentor_for_user($requester_id);
$owner=ZMP_Mentor_School_Profile::mentor_for_user($owner_id);
if (!$requester||!$owner||!ZMP_Mentor_School_Profile::is_teacher($requester_id)
    ||!ZMP_Mentor_School_Profile::is_teacher($owner_id)) { fwrite(STDERR,"BLOCKED: clone lacks expected synthetic Teacher fixtures.\n"); exit(2); }
$schools=static function($mentor_id)use($wpdb){return $wpdb->get_results($wpdb->prepare(
    'SELECT id,school_key FROM '.$wpdb->prefix.'zmp_mentor_schools WHERE mentor_id=%d AND is_active=1',(int)$mentor_id));};
$shared=null;
foreach((array)$schools($requester->id)as $a)foreach((array)$schools($owner->id)as $b)
    if(hash_equals((string)$a->school_key,(string)$b->school_key)){$shared=array($a,$b);break 2;}
if(!$shared){fwrite(STDERR,"BLOCKED: no shared active synthetic school in clone.\n");exit(2);}
$school_table=$wpdb->prefix.'zmp_mentor_schools';
$request_table=$wpdb->prefix.'zmp_school_email_requests';
$event_table=$wpdb->prefix.'zmp_school_email_request_events';
$indexes=$wpdb->get_results("SHOW INDEX FROM `{$school_table}`");
$named=array_values(array_filter((array)$indexes,static function($row){return($row->Key_name??'')==='mentor_active';}));
if($wpdb->last_error!==''||count($named)!==2||$named[0]->Column_name!=='mentor_id'||$named[1]->Column_name!=='is_active'){
    fwrite(STDERR,"BLOCKED: clone mentor_active index baseline differs from expected.\n");exit(2);
}
$pending=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$request_table} WHERE requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND status IN ('pending','approved')",
    $requester_id,$owner_id,(int)$shared[1]->id));
$request_max=(int)$wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$request_table}");
$event_max=(int)$wpdb->get_var("SELECT COALESCE(MAX(id),0) FROM {$event_table}");
if($wpdb->last_error!==''||$pending!==0){fwrite(STDERR,"BLOCKED: matching request exists or clone baseline unreadable.\n");exit(2);}

// Cooldown state is changed only in the clone and restored to exact existence,
// stored value, and autoload. Debug options/traces are intentionally untouched;
// normal product diagnostics may be created in this disposable clone.
$option_names=array('zmp_db_version','zmp_role_messenger_schema_retry','zmp_role_messenger_schema_retry_after');
$option_before=array();
foreach($option_names as $name){
    $option_before[$name]=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$name),ARRAY_A);
    if($wpdb->last_error!==''){fwrite(STDERR,"BLOCKED: clone retry-option baseline unreadable.\n");exit(2);}
}
$manager=WP_Session_Tokens::get_instance($requester_id);$expires=time()+300;$token=$manager->create($expires);
if(!is_string($token)||$token===''){fwrite(STDERR,"FAIL: temporary synthetic auth session creation failed.\n");exit(1);}
$dropped=false;$options_changed=false;$request_ids=array();$event_ids=array();$mail_guard_written=false;$mail_guard_attempted=false;
$schema_restored=false;$options_restored=false;$session_restored=false;
$run_failed=false;$cleanup_failed=false;$behavior_passed=false;
$logged_cookie_mutated=false;$logged_cookie_was_set=false;$logged_cookie_before=null;$cookie_name='';
$mu_dir=WP_CONTENT_DIR.'/mu-plugins';$mu_guard=$mu_dir.'/zmp65-unready-email-mail-guard.php';
$mail_marker=$mu_dir.'/.zmp65-unready-email-mail-called';
$handler_marker=$mu_dir.'/.zmp65-unready-email-handler-reached';
$guard_source="<?php\n"
    ."if(strtoupper((string)(\$_SERVER['REQUEST_METHOD']??''))==='POST'&&(string)parse_url((string)(\$_SERVER['REQUEST_URI']??''),PHP_URL_PATH)==='/wp-admin/admin-post.php'&&((\$_POST['action']??'')==='zmp_school_email_request_submit')){\n"
    ."register_shutdown_function(static function(){\$status=(int)http_response_code();\$matched=false;foreach(headers_list() as \$header){if(stripos(\$header,'Location:')!==0)continue;\$location=trim(substr(\$header,9));\$parts=parse_url(\$location);\$query=array();if(is_array(\$parts)&&isset(\$parts['query']))parse_str((string)\$parts['query'],\$query);\$matched=is_array(\$parts)&&strtolower((string)(\$parts['scheme']??''))==='http'&&strtolower((string)(\$parts['host']??''))==='127.0.0.1'&&(int)(\$parts['port']??0)===8095&&((\$parts['path']??'')==='/')&&!isset(\$parts['user'])&&!isset(\$parts['pass'])&&!isset(\$parts['fragment'])&&\$query===array('zmp_school_email_status'=>'request_failed');if(\$matched)break;}file_put_contents(__DIR__.'/.zmp65-unready-email-handler-reached',\$status===302&&\$matched?'handler_redirect_request_failed':'route_shutdown_other',LOCK_EX);});}\n"
    ."add_filter('pre_wp_mail',static function(\$short){file_put_contents(__DIR__.'/.zmp65-unready-email-mail-called','called',LOCK_EX);return false;},PHP_INT_MAX);\n";
$guard_hash=hash('sha256',$guard_source);$mu_dir_created=false;
try{
    if(file_exists($mu_guard)||file_exists($mail_marker)||file_exists($handler_marker)||is_link($mu_dir))throw new RuntimeException('mail_guard_path_occupied_or_linked');
    if(!is_dir($mu_dir)){if(!mkdir($mu_dir,0700))throw new RuntimeException('mu_plugin_directory_create_failed');$mu_dir_created=true;}
    $mail_guard_attempted=true;
    if(file_put_contents($mu_guard,$guard_source,LOCK_EX)!==strlen($guard_source)
        ||!hash_equals($guard_hash,(string)hash_file('sha256',$mu_guard)))throw new RuntimeException('mail_guard_install_failed');
    $mail_guard_written=true;
    if($wpdb->query("ALTER TABLE `{$school_table}` DROP INDEX `mentor_active`")===false)throw new RuntimeException('controlled_index_fault_failed');
    $dropped=true;
    $options_changed=true;
    update_option('zmp_db_version',ZMP_SCHEMA_VERSION,false);
    update_option('zmp_role_messenger_schema_retry','1',false);
    update_option('zmp_role_messenger_schema_retry_after',(string)(time()+240),false);
    $cookie_name=defined('LOGGED_IN_COOKIE')?LOGGED_IN_COOKIE:'';
    $cookie=wp_generate_auth_cookie($requester_id,$expires,'logged_in',$token);
    $endpoint=admin_url('admin-post.php');$parts=wp_parse_url($endpoint);
    if(!$cookie_name||!$cookie||!is_array($parts)||($parts['host']??'')!=='127.0.0.1'
        ||(int)($parts['port']??0)!==8095||($parts['path']??'')!=='/wp-admin/admin-post.php')throw new RuntimeException('auth_or_clone_endpoint_invalid');
    $logged_cookie_was_set=array_key_exists($cookie_name,$_COOKIE);
    if($logged_cookie_was_set)$logged_cookie_before=$_COOKIE[$cookie_name];
    $_COOKIE[$cookie_name]=$cookie;$logged_cookie_mutated=true;
    wp_set_current_user($requester_id);$nonce=wp_create_nonce('zmp_school_email_request_submit');
    if($logged_cookie_was_set)$_COOKIE[$cookie_name]=$logged_cookie_before;else unset($_COOKIE[$cookie_name]);
    $logged_cookie_mutated=false;
    $response=wp_remote_post($endpoint,array('timeout'=>15,'redirection'=>0,'headers'=>array('Cookie'=>$cookie_name.'='.$cookie),
        'body'=>array('action'=>ZMP_School_Email_Access::SUBMIT_ACTION,'owner_user_id'=>$owner_id,
            'school_id'=>(int)$shared[0]->id,'reason'=>'Synthetic unready-schema request.','_wpnonce'=>$nonce)));
    if(is_wp_error($response))throw new RuntimeException('clone_loopback_transport_failed');
    $status=(int)wp_remote_retrieve_response_code($response);$location=(string)wp_remote_retrieve_header($response,'location');
    $body=(string)wp_remote_retrieve_body($response);
    foreach(array('example.invalid','official_email','request_id','reason=','email=')as $needle)
        if(stripos($location,$needle)!==false||stripos($body,$needle)!==false)throw new RuntimeException('response_disclosure_marker');
    $location_parts=wp_parse_url($location);$location_query=array();
    if(is_array($location_parts)&&isset($location_parts['query']))parse_str((string)$location_parts['query'],$location_query);
    $expected_query=array('zmp_school_email_status'=>'request_failed');
    $exact_target=is_array($location_parts)&&strtolower((string)($location_parts['scheme']??''))==='http'
        &&strtolower((string)($location_parts['host']??''))==='127.0.0.1'&&(int)($location_parts['port']??0)===8095
        &&($location_parts['path']??'')==='/'&&!isset($location_parts['user'])&&!isset($location_parts['pass'])
        &&!isset($location_parts['fragment'])&&$location_query===$expected_query;
    if($status!==302||!$exact_target){
        $allowed_query=array();
        if(isset($location_query['zmp_school_email_status'])&&in_array((string)$location_query['zmp_school_email_status'],array('request_failed','request_submitted'),true)){
            $allowed_query['zmp_school_email_status']=(string)$location_query['zmp_school_email_status'];
        }
        fwrite(STDERR,'REDIRECT_MISMATCH: '.wp_json_encode(array('http_status'=>$status,'location_query'=>$allowed_query),JSON_UNESCAPED_SLASHES)."\n");
        throw new RuntimeException('unexpected_fail_closed_redirect');
    }
    if(!is_file($handler_marker)||file_get_contents($handler_marker)!=='handler_redirect_request_failed')
        throw new RuntimeException('request_handler_redirect_marker_missing');
    $rows=$wpdb->get_results($wpdb->prepare("SELECT id FROM {$request_table} WHERE id>%d AND requester_user_id=%d AND owner_user_id=%d AND school_id=%d",
        $request_max,$requester_id,$owner_id,(int)$shared[1]->id));
    if($wpdb->last_error!=='')throw new RuntimeException('clone_request_readback_failed');
    foreach((array)$rows as $row)$request_ids[]=(int)$row->id;
    $new_events=$wpdb->get_results($wpdb->prepare("SELECT id,request_id,actor_user_id,event_type FROM {$event_table} WHERE id>%d AND actor_user_id=%d",$event_max,$requester_id));
    if($wpdb->last_error!=='')throw new RuntimeException('clone_event_readback_failed');
    foreach((array)$new_events as $event)$event_ids[]=(int)$event->id;
    if($request_ids||$event_ids||file_exists($mail_marker))throw new RuntimeException('request_event_or_mail_side_effect');
    $behavior_passed=true;
}catch(Throwable $error){$run_failed=true;fwrite(STDERR,'FAIL: '.sanitize_key($error->getMessage())."\n");}
finally{
    if($logged_cookie_mutated){if($logged_cookie_was_set)$_COOKIE[$cookie_name]=$logged_cookie_before;else unset($_COOKIE[$cookie_name]);}
    if($dropped){
        $wpdb->last_error='';$wpdb->query("ALTER TABLE `{$school_table}` ADD KEY `mentor_active` (`mentor_id`,`is_active`)");
        $wpdb->last_error='';$schema_restored=ZMP_Mentor_Schools::schema_is_compatible();
    }
    foreach($request_ids as $id){
        $event_rows=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$event_table} WHERE request_id=%d",$id));
        foreach((array)$event_rows as $event_id){$event_ids[]=(int)$event_id;$wpdb->delete($event_table,array('id'=>(int)$event_id,'request_id'=>$id),array('%d','%d'));}
        $wpdb->delete($request_table,array('id'=>$id,'requester_user_id'=>$requester_id,'owner_user_id'=>$owner_id,
            'school_id'=>(int)$shared[1]->id),array('%d','%d','%d','%d'));
    }
    if($options_changed){
        foreach($option_before as $name=>$row){
            if(is_array($row)){
                $wpdb->last_error='';
                $wpdb->update($wpdb->options,array('option_value'=>$row['option_value'],'autoload'=>$row['autoload']),array('option_name'=>$name),array('%s','%s'),array('%s'));
                if($wpdb->last_error!==''){$cleanup_failed=true;continue;}
                $wpdb->last_error='';
                $restored=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$name),ARRAY_A);
                if($wpdb->last_error!==''||!is_array($restored)){
                    $wpdb->last_error='';
                    $inserted=$wpdb->insert($wpdb->options,array('option_name'=>$name,'option_value'=>$row['option_value'],'autoload'=>$row['autoload']),array('%s','%s','%s'));
                    if($inserted===false||$wpdb->last_error!==''){$cleanup_failed=true;continue;}
                }
            }else{
                $wpdb->last_error='';
                $wpdb->delete($wpdb->options,array('option_name'=>$name),array('%s'));
                if($wpdb->last_error!==''){$cleanup_failed=true;continue;}
            }
            wp_cache_delete($name,'options');
        }
        wp_cache_delete('alloptions','options');
        $options_restored=true;
        foreach($option_before as $name=>$row){
            $wpdb->last_error='';
            $after=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$name),ARRAY_A);
            if($wpdb->last_error!==''||(($row===null)!==($after===null))
                ||(is_array($row)&&(!is_array($after)||(string)$after['option_value']!==(string)$row['option_value']
                    ||(string)$after['autoload']!==(string)$row['autoload']))){$options_restored=false;$cleanup_failed=true;}
        }
    }else{
        $options_restored=true;
    }
    if($mail_guard_attempted&&file_exists($mu_guard)){
        $guard_file_hash=hash_file('sha256',$mu_guard);
        if($mail_guard_written&&is_string($guard_file_hash)&&hash_equals($guard_hash,$guard_file_hash)){
            if(!unlink($mu_guard))$cleanup_failed=true;
        }else{
            $cleanup_failed=true;
        }
    }
    if(file_exists($mail_marker)){
        if(file_get_contents($mail_marker)==='called'){
            if(!unlink($mail_marker))$cleanup_failed=true;
        }else{
            $cleanup_failed=true;
        }
    }
    if(file_exists($handler_marker)){
        if(file_get_contents($handler_marker)==='handler_redirect_request_failed'||file_get_contents($handler_marker)==='route_shutdown_other'){
            if(!unlink($handler_marker))$cleanup_failed=true;
        }else{$cleanup_failed=true;}
    }
    if($mail_guard_attempted&&(file_exists($mu_guard)||file_exists($mail_marker)||file_exists($handler_marker)))$cleanup_failed=true;
    if($mu_dir_created){
        if(!rmdir($mu_dir)||is_dir($mu_dir))$cleanup_failed=true;
    }
    $manager->destroy($token);$session_restored=!$manager->verify($token);
    if(!$schema_restored||!$options_restored||!$session_restored)$cleanup_failed=true;
    if($cleanup_failed)fwrite(STDERR,"CLEANUP: clone restoration incomplete; discard clone after evidence capture.\n");
}
if($run_failed||$cleanup_failed||!$schema_restored||!$options_restored||!$session_restored||!$behavior_passed)exit(1);
echo "PASS: generic fail-closed redirect; no request/event, response disclosure, or mail marker; exact cleanup verified.\n";
