<?php
/** Actual synthetic `.64` profile, access-request, disclosure, invalidation and diagnostics acceptance. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_migration_final' || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.64') { fwrite(STDERR,"wrong_test_runtime\n"); exit(3); }
$manifest_path='/tmp/zmp64-isolated-20261103/final-run/manifest.json';
$manifest=json_decode((string)file_get_contents($manifest_path),true);
if (!is_array($manifest) || ($manifest['database']??'')!=='zmp64_migration_final') { fwrite(STDERR,"manifest_unavailable\n"); exit(4); }
$prefix=$wpdb->prefix;$ids=$manifest['ids'];$checks=array();$failures=array();$request_ids=array();$diagnostic_sessions=array();$created_users=array();$mail_calls=0;
$assert=static function($condition,$label)use(&$checks,&$failures){$checks[]=array('assertion'=>$label,'result'=>$condition?'PASS':'FAIL');if(!$condition){$failures[]=$label;}};
$error_code=static function($value){return is_wp_error($value)?$value->get_error_code():'';};
$request_row=static function($request_id)use($wpdb,$prefix){return $wpdb->get_row($wpdb->prepare("SELECT id,status,requester_user_id,owner_user_id,school_id,approved_email_epoch,approved_email_hash,decided_by FROM {$prefix}zmp_school_email_requests WHERE id=%d",(int)$request_id),ARRAY_A);};
$event_count=static function($request_id)use($wpdb,$prefix){return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}zmp_school_email_request_events WHERE request_id=%d",(int)$request_id));};
$role_user=static function($login,$role)use(&$created_users){$id=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>$login.'@example.invalid','display_name'=>'Synthetic role fixture','role'=>$role));if(!is_wp_error($id)&&(int)$id>0){$created_users[]=(int)$id;return(int)$id;}return 0;};
$visibility_fields=array('employment_term_visible'=>'employment_term','workload_visible'=>'workload','retired_visible'=>'retired','replacement_visible'=>'replacement','replacement_for_visible'=>'replacement_for','replacement_start_date_visible'=>'replacement_start_date','replacement_end_date_visible'=>'replacement_end_date');
$owner_id=(int)$ids['users']['owner'];$requester_id=(int)$ids['users']['requester'];$other_id=(int)$ids['users']['other'];$president_id=(int)$ids['users']['president'];$moderator_id=(int)$ids['users']['moderator'];$testator_id=(int)$ids['users']['testator'];
$owner_mentor=(int)$ids['mentors']['owner'];$requester_mentor=(int)$ids['mentors']['requester'];$owner_school=(int)$ids['schools']['owner'];$requester_school=(int)$ids['schools']['requester'];$other_school=(int)$ids['schools']['other'];
$token='';$owner_user=get_userdata($owner_id);if($owner_user&&preg_match('/^zmp64r_([a-f0-9]+)_owner$/',(string)$owner_user->user_login,$m)){$token=$m[1];}
if($token===''){fwrite(STDERR,"synthetic_token_lookup_failed\n");exit(5);}
$email_a='zmp64-owner-'.$token.'@example.invalid';$email_b='zmp64-owner-changed-'.$token.'@example.invalid';
$reason='Synthetic access request '.$token;
$base_payload=array('official_email'=>$email_a,'employment_term'=>'fixed_term','workload'=>'full','retired'=>'1','replacement'=>'1','replacement_for'=>'Synthetic colleague '.$token,'replacement_start_date'=>'2026-08-01','replacement_end_date'=>'2026-12-31');
foreach(array_keys($visibility_fields)as$field){$base_payload[$field]='0';}
$event_options_before=get_option(ZMP_Debug::OPTION,array());$capture_option_before=get_option(ZMP_Debug::CAPTURE_OPTION,null);
$error_log='/tmp/zmp64-isolated-20261103/final-run/php-diagnostics.log';@unlink($error_log);$old_error_log=ini_get('error_log');ini_set('error_log',$error_log);
$mail_filter=static function($pre,$atts)use(&$mail_calls){$mail_calls++;return true;};add_filter('pre_wp_mail',$mail_filter,10,2);
try {
    $types=ZMP_Debug::test_types();
    $assert(isset($types['school_profile'])&&isset($types['school_email_access']),'diagnostic_categories_registered');
    $profile_capture=ZMP_Debug::start_capture('school_profile',15,1);
    $assert(!is_wp_error($profile_capture)&&!empty($profile_capture['session_id']),'profile_capture_started');
    $diagnostic_sessions['profile']=(string)($profile_capture['session_id']??'');
    wp_set_current_user($owner_id);
    $default=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);
    $assert(is_array($default)&&$default['official_email']===''&&$default['employment_term_visible']===0&&$default['workload_visible']===0&&$default['retired_visible']===0&&$default['replacement_visible']===0&&$default['replacement_for_visible']===0&&$default['replacement_start_date_visible']===0&&$default['replacement_end_date_visible']===0,'new_school_profile_private_defaults');
    $second_default=ZMP_Mentor_School_Profile::get_profile($owner_id,(int)$ids['schools']['owner_second']);
    $assert(is_array($second_default)&&$second_default['official_email']===''&&$second_default['employment_term']==='','profiles_isolated_by_school');
    $incomplete=$base_payload;unset($incomplete['workload']);
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$incomplete))==='invalid_profile','incomplete_profile_rejected');
    $invalid=$base_payload;$invalid['official_email']='not-an-email';
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$invalid))==='invalid_email','invalid_email_rejected');
    $invalid=$base_payload;$invalid['employment_term']='temporary_forever';
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$invalid))==='invalid_employment','invalid_employment_term_rejected');
    $invalid=$base_payload;$invalid['replacement_end_date']='2026-07-31';
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$invalid))==='invalid_replacement','reversed_replacement_period_rejected');
    $invalid=$base_payload;$invalid['workload_visible']='yes';
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$invalid))==='invalid_visibility','invalid_visibility_value_rejected');
    $assert(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$base_payload)===true,'valid_private_profile_saved');
    $get_email_epoch=static function()use($wpdb,$prefix,$owner_mentor,$owner_school){return(int)$wpdb->get_var($wpdb->prepare("SELECT official_email_epoch FROM {$prefix}zmp_mentor_school_profiles WHERE mentor_id=%d AND school_id=%d",$owner_mentor,$owner_school));};
    $epoch_before_ui=(int)$get_email_epoch();
    $ui_payload=$base_payload;$ui_payload['official_email']=str_replace('@',' @',$email_a);
    $_POST=wp_slash(array('zmp_school_profile_nonce'=>wp_create_nonce('zmp_school_profile_update_'.$owner_id),'zmp_school_profile'=>array($owner_school=>$ui_payload)));$_REQUEST=array();
    ZMP_School_Profile_UI::save($owner_id);$_POST=array();$_REQUEST=array();
    $after_invalid_ui=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);
    $assert(is_array($after_invalid_ui)&&$after_invalid_ui['official_email']===$email_a&&$get_email_epoch()===$epoch_before_ui&&get_transient('zmp_school_profile_notice_'.$owner_id)==='error','profile_ui_rejects_normalized_invalid_email_without_mutation');
    delete_transient('zmp_school_profile_notice_'.$owner_id);
    $ui_payload=$base_payload;$ui_payload['official_email']='';
    $_POST=wp_slash(array('zmp_school_profile_nonce'=>wp_create_nonce('zmp_school_profile_update_'.$owner_id),'zmp_school_profile'=>array($owner_school=>$ui_payload)));$_REQUEST=array();
    ZMP_School_Profile_UI::save($owner_id);$_POST=array();$_REQUEST=array();
    $after_empty_ui=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);
    $assert(is_array($after_empty_ui)&&$after_empty_ui['official_email']===''&&$get_email_epoch()===$epoch_before_ui+1&&get_transient('zmp_school_profile_notice_'.$owner_id)==='success','profile_ui_allows_empty_optional_email');
    delete_transient('zmp_school_profile_notice_'.$owner_id);
    $_POST=wp_slash(array('zmp_school_profile_nonce'=>wp_create_nonce('zmp_school_profile_update_'.$owner_id),'zmp_school_profile'=>array($owner_school=>$base_payload)));$_REQUEST=array();
    ZMP_School_Profile_UI::save($owner_id);$_POST=array();$_REQUEST=array();
    $restored_after_ui=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);
    $assert(is_array($restored_after_ui)&&$restored_after_ui['official_email']===$email_a&&$get_email_epoch()===$epoch_before_ui+2&&get_transient('zmp_school_profile_notice_'.$owner_id)==='success','profile_ui_restores_synthetic_email_after_optional_empty_check');
    delete_transient('zmp_school_profile_notice_'.$owner_id);
    wp_set_current_user($requester_id);
    $assert($error_code(ZMP_Mentor_School_Profile::get_profile($requester_id,$owner_school))==='school_not_owned','cross_owner_profile_read_denied');
    $assert($error_code(ZMP_Mentor_School_Profile::save_profile($requester_id,$owner_school,$base_payload))==='school_not_owned','cross_owner_profile_write_denied');
    foreach($visibility_fields as$flag=>$field){
        $payload=$base_payload;foreach(array_keys($visibility_fields)as$other_flag){$payload[$other_flag]='0';}$payload[$flag]='1';
        wp_set_current_user($owner_id);$saved=ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$payload);
        wp_set_current_user($requester_id);$rows=ZMP_Mentor_School_Profile::get_employment_directory($requester_id,$requester_school);
        $row=null;foreach((array)$rows as$candidate){if((int)($candidate['mentor_id']??0)===$owner_mentor){$row=$candidate;break;}}
        $assert($saved===true&&is_array($row)&&array_key_exists($field,$row),'independent_visibility_shows_'.$field);
        foreach($visibility_fields as$other_flag=>$other_field){if($other_flag!==$flag){$assert(!is_array($row)||!array_key_exists($other_field,$row),'independent_visibility_hides_'.$other_field.'_when_'.$field);}}
        $assert(is_array($row)&&!array_key_exists('official_email',$row),'directory_never_returns_official_email');
    }
    $payload=$base_payload;$payload['employment_term_visible']='1';$payload['retired_visible']='1';
    wp_set_current_user($owner_id);$assert(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$payload)===true,'owner_directory_visibility_saved');
    $subscriber_id=$role_user('zmp64r_'.$token.'_subscriber','subscriber');$mixed_id=$role_user('zmp64r_'.$token.'_mixed','zmp_teacher');
    if($mixed_id){$mixed=get_user_by('id',$mixed_id);$mixed->add_role('zmp_testator');}
    wp_set_current_user($testator_id);$assert($error_code(ZMP_Mentor_School_Profile::get_employment_directory($testator_id,$owner_school))==='forbidden','testator_directory_denied');
    wp_set_current_user($subscriber_id);$assert($error_code(ZMP_Mentor_School_Profile::get_employment_directory($subscriber_id,$owner_school))==='forbidden','subscriber_directory_denied');
    wp_set_current_user($mixed_id);$assert($error_code(ZMP_Mentor_School_Profile::get_employment_directory($mixed_id,$owner_school))==='forbidden','mixed_role_directory_denied');
    wp_set_current_user($moderator_id);$assert(is_array(ZMP_Mentor_School_Profile::get_employment_directory($moderator_id,$owner_school)),'moderator_directory_allowed');
    wp_set_current_user($president_id);$assert(is_array(ZMP_Mentor_School_Profile::get_employment_directory($president_id,$owner_school)),'active_president_directory_allowed');
    wp_set_current_user(1);$assert(is_array(ZMP_Mentor_School_Profile::get_employment_directory(1,$owner_school)),'administrator_directory_allowed');
    ZMP_Debug::log('info','MESSAGE_SENTINEL_'.$token,array('safe_record_id'=>$owner_school,'teacher_name'=>'NAME_SENTINEL_'.$token,'official_email'=>$email_a,'reason'=>$reason,'nonce'=>'NONCE_SENTINEL_'.$token,'url'=>'http://127.0.0.1/path?email='.$email_a),'school_profile');
    ZMP_Debug::log('info','School profile saved.',array('safe_record_id'=>$owner_school,'user_id'=>$owner_id,'outcome'=>'saved'),'school_profile');
    $profile_events=ZMP_Debug::filter_events(get_option(ZMP_Debug::OPTION,array()),'school_profile',$diagnostic_sessions['profile']);
    $profile_json=ZMP_Debug::export_payload($profile_events,'json');$profile_csv=ZMP_Debug::export_payload($profile_events,'csv');$profile_serialized=wp_json_encode($profile_events).$profile_json.$profile_csv;
    $assert(count($profile_events)>0,'profile_capture_contains_service_events');
    $fixed_profile_message_found=false;$safe_flow_id_found=false;foreach($profile_events as$profile_event){if(($profile_event['message']??'')==='School profile saved.'){$fixed_profile_message_found=true;}if(preg_match('/^[a-f0-9-]{36}$/',(string)($profile_event['flow_id']??''))){$safe_flow_id_found=true;}}
    $assert(strpos($profile_serialized,$email_a)===false&&strpos($profile_serialized,$reason)===false&&strpos($profile_serialized,'NAME_SENTINEL_'.$token)===false&&strpos($profile_serialized,'NONCE_SENTINEL_'.$token)===false&&strpos($profile_serialized,'MESSAGE_SENTINEL_'.$token)===false,'profile_diagnostics_redact_sensitive_inputs');
    $assert($fixed_profile_message_found&&$safe_flow_id_found,'profile_diagnostics_keep_safe_fixed_messages_and_flow_ids');
    ZMP_Debug::stop_capture();

    $email_capture=ZMP_Debug::start_capture('school_email_access',15,1);
    $assert(!is_wp_error($email_capture)&&!empty($email_capture['session_id']),'email_capture_started');$diagnostic_sessions['email']=(string)($email_capture['session_id']??'');
    wp_set_current_user($requester_id);
    $contacts=ZMP_School_Email_Access::get_requestable_contacts($requester_id,$requester_school);
    $contact=null;foreach((array)$contacts as$candidate){if((int)($candidate['owner_user_id']??0)===$owner_id){$contact=$candidate;break;}}
    $assert(is_array($contact)&&!array_key_exists('official_email',$contact)&&strpos(wp_json_encode($contact),$email_a)===false,'same_school_contact_available_without_email');
    $invalid_reason=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,'short');
    $assert($error_code($invalid_reason)==='invalid_reason','short_request_reason_rejected');
    $too_long_reason=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,str_repeat('R',501));
    $assert($error_code($too_long_reason)==='invalid_reason','overlong_request_reason_rejected');
    $assert($error_code(ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$other_school,$reason))==='school_not_owned','cross_school_request_denied_fail_closed');
    wp_set_current_user($testator_id);
    $assert($error_code(ZMP_School_Email_Access::submit_request($testator_id,$owner_id,$requester_school,$reason))==='forbidden','testator_request_denied');
    wp_set_current_user($requester_id);
    $pending=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,$reason);
    $assert(is_array($pending)&&($pending['status']??'')==='pending'&&empty($pending['existing']),'requester_creates_pending_access_request');
    $request_id=(int)($pending['request_id']??0);$request_ids[]=$request_id;
    $row=$request_row($request_id);$assert($request_id>0&&($row['status']??'')==='pending','request_persisted_pending');
    $duplicate=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,$reason);
    $assert(is_array($duplicate)&&(int)($duplicate['request_id']??0)===$request_id&&!empty($duplicate['existing']),'same_pending_request_is_idempotent');
    $assert($event_count($request_id)===1,'duplicate_request_creates_no_extra_event');
    wp_set_current_user($president_id);
    $pending_queue=ZMP_School_Email_Access::pending_for_active_president($president_id);
    wp_set_current_user($requester_id);
    $queue_row=null;foreach((array)$pending_queue as$candidate){if((int)($candidate['request_id']??0)===$request_id){$queue_row=$candidate;break;}}
    $assert(is_array($queue_row)&&!array_key_exists('official_email',$queue_row)&&strpos(wp_json_encode($queue_row),$email_a)===false,'president_pending_queue_has_no_email');
    $request_status=ZMP_School_Email_Access::requests_for_requester($requester_id);
    $status_row=null;foreach((array)$request_status as$candidate){if((int)($candidate['request_id']??0)===$request_id){$status_row=$candidate;break;}}
    $assert(is_array($status_row)&&($status_row['status']??'')==='pending'&&!array_key_exists('official_email',$status_row),'requester_pending_status_has_no_email');
    $before_route_events=$event_count($request_id);$wp_die_status=0;$wp_die_caught=false;
    $wp_die_callback=static function($message='',$title='',$args=array())use(&$wp_die_status,&$wp_die_caught){$wp_die_status=(int)($args['response']??0);$wp_die_caught=true;throw new RuntimeException('intercepted_wp_die');};
    $wp_die_filter=static function($handler)use($wp_die_callback){return $wp_die_callback;};
    wp_set_current_user($president_id);$_POST=array('request_id'=>$request_id,'decision'=>'approved','_wpnonce'=>'BAD_NONCE_'.$token);
    add_filter('wp_die_handler',$wp_die_filter,PHP_INT_MAX);
    try{ZMP_School_Email_Access::handle_decision();}catch(Throwable $e){}
    remove_filter('wp_die_handler',$wp_die_filter,PHP_INT_MAX);$_POST=array();$_REQUEST=array();
    $assert($wp_die_caught&&$wp_die_status===403,'invalid_decision_nonce_returns_403');
    $assert(($request_row($request_id)['status']??'')==='pending'&&$event_count($request_id)===$before_route_events,'invalid_nonce_changes_no_request_or_event');
    wp_set_current_user($moderator_id);$before_route_events=$event_count($request_id);
    $assert($error_code(ZMP_School_Email_Access::decide($request_id,$moderator_id,'approved'))==='forbidden','moderator_decision_denied');
    $assert(($request_row($request_id)['status']??'')==='pending'&&$event_count($request_id)===$before_route_events,'moderator_denial_has_no_state_change');
    wp_set_current_user(1);$assert($error_code(ZMP_School_Email_Access::decide($request_id,1,'approved'))==='forbidden','administrator_not_president_decision_denied');
    wp_set_current_user($testator_id);$assert($error_code(ZMP_School_Email_Access::decide($request_id,$testator_id,'approved'))==='forbidden','testator_decision_denied');
    wp_set_current_user($president_id);
    $approved=ZMP_School_Email_Access::decide($request_id,$president_id,'approved');
    $assert($approved===true&&($request_row($request_id)['status']??'')==='approved','active_president_approves_request');
    $after_approval_events=$event_count($request_id);
    $replay=ZMP_School_Email_Access::decide($request_id,$president_id,'approved');
    $assert($replay===true&&$event_count($request_id)===$after_approval_events,'approval_replay_is_idempotent');
    wp_set_current_user($other_id);$_POST=array();$before_disclosure_events=$event_count($request_id);
    $no_nonce=ZMP_School_Email_Access::disclose_approved_email($request_id,$other_id);
    $assert($error_code($no_nonce)==='forbidden'&&$event_count($request_id)===$before_disclosure_events,'disclosure_without_nonce_denied');
    wp_set_current_user($requester_id);$_POST=array();
    $no_post_nonce=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester_id);
    $assert($error_code($no_post_nonce)==='forbidden'&&$event_count($request_id)===$before_disclosure_events,'disclosure_requires_post_nonce');
    $_POST['_wpnonce']=wp_create_nonce('zmp_school_email_disclose_'.$request_id);
    $disclosed=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester_id);
    $assert(is_string($disclosed)&&hash_equals($email_a,$disclosed),'approved_contact_disclosed_to_requester_only');
    $disclosure_events=$event_count($request_id);
    $_POST['_wpnonce']=wp_create_nonce('zmp_school_email_disclose_'.$request_id);
    $disclosed_again=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester_id);
    $assert(is_string($disclosed_again)&&hash_equals($email_a,$disclosed_again)&&$event_count($request_id)===$disclosure_events+1,'repeat_disclosure_is_audited');
    $_POST=array();
    $existing_approved=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,$reason);
    $assert(is_array($existing_approved)&&(int)($existing_approved['request_id']??0)===$request_id&&($existing_approved['status']??'')==='approved','approved_request_reuse_is_idempotent');
    wp_set_current_user($owner_id);$profile=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);$payload=(array)$profile;$payload['official_email']=$email_b;
    $assert(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$payload)===true,'official_email_changed_to_synthetic_b');
    wp_set_current_user($requester_id);
    $after_email_change=ZMP_School_Email_Access::requests_for_requester($requester_id);$changed_status=null;
    foreach((array)$after_email_change as$candidate){if((int)($candidate['request_id']??0)===$request_id){$changed_status=$candidate['status']??null;}}
    $assert($changed_status==='invalidated'&&($request_row($request_id)['status']??'')==='invalidated','email_change_invalidates_status_before_render');
    $_POST['_wpnonce']=wp_create_nonce('zmp_school_email_disclose_'.$request_id);$stale_disclosure=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester_id);$_POST=array();
    $assert($error_code($stale_disclosure)==='not_approved'||$error_code($stale_disclosure)==='contact_unavailable','email_changed_approval_cannot_disclose');
    wp_set_current_user($owner_id);$profile=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);$payload=(array)$profile;$payload['official_email']=$email_a;
    $assert(ZMP_Mentor_School_Profile::save_profile($owner_id,$owner_school,$payload)===true,'official_email_restored_to_original_synthetic_value');
    wp_set_current_user($requester_id);$_POST['_wpnonce']=wp_create_nonce('zmp_school_email_disclose_'.$request_id);$reverted_disclosure=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester_id);$_POST=array();
    $assert(($request_row($request_id)['status']??'')==='invalidated'&&is_wp_error($reverted_disclosure),'email_change_back_does_not_revive_old_approval');
    $fresh=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,$reason);
    $assert(is_array($fresh)&&($fresh['status']??'')==='pending'&&(int)($fresh['request_id']??0)!==$request_id,'email_reversion_requires_fresh_request');
    $fresh_id=(int)$fresh['request_id'];$request_ids[]=$fresh_id;
    wp_set_current_user($president_id);$assert(ZMP_School_Email_Access::decide($fresh_id,$president_id,'approved')===true,'fresh_email_request_requires_president_decision');
    wp_set_current_user($requester_id);
    $epoch_before=(int)$wpdb->get_var($wpdb->prepare("SELECT engagement_epoch FROM {$prefix}zmp_mentor_schools WHERE id=%d",$requester_school));
    $wpdb->update($prefix.'zmp_mentor_schools',array('is_active'=>0),array('id'=>$requester_school,'mentor_id'=>$requester_mentor),array('%d'),array('%d','%d'));
    $reactivated=ZMP_Mentor_Schools::add_school($requester_mentor,'Synthetic School '.$token,'Скопје',true);
    $epoch_after=(int)$wpdb->get_var($wpdb->prepare("SELECT engagement_epoch FROM {$prefix}zmp_mentor_schools WHERE id=%d",$requester_school));
    $assert((int)$reactivated===$requester_school&&$epoch_after===$epoch_before+1,'reactivation_increments_same_engagement_epoch_once');
    $after_reactivation=ZMP_School_Email_Access::requests_for_requester($requester_id);$engagement_status=null;
    foreach((array)$after_reactivation as$candidate){if((int)($candidate['request_id']??0)===$fresh_id){$engagement_status=$candidate['status']??null;}}
    $assert($engagement_status==='invalidated'&&($request_row($fresh_id)['status']??'')==='invalidated','engagement_reactivation_invalidates_approved_request');
    $_POST['_wpnonce']=wp_create_nonce('zmp_school_email_disclose_'.$fresh_id);$engagement_disclosure=ZMP_School_Email_Access::disclose_approved_email($fresh_id,$requester_id);$_POST=array();
    $assert(is_wp_error($engagement_disclosure),'engagement_changed_approval_cannot_disclose');
    $ui_pending=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$requester_school,'Synthetic UI queue review '.$token);
    $assert(is_array($ui_pending)&&($ui_pending['status']??'')==='pending','synthetic_pending_request_prepared_for_ui');
    if(is_array($ui_pending)){$request_ids[]=(int)$ui_pending['request_id'];}
    ZMP_Debug::log('info','Synthetic diagnostic privacy probe.',array('safe_record_id'=>$request_id,'teacher_name'=>'NAME_SENTINEL_'.$token,'official_email'=>$email_a,'reason'=>$reason,'nonce'=>'NONCE_SENTINEL_'.$token,'url'=>'http://127.0.0.1/path?email='.$email_a),'school_email_access');
    $email_events=ZMP_Debug::filter_events(get_option(ZMP_Debug::OPTION,array()),'school_email_access',$diagnostic_sessions['email']);
    $email_json=ZMP_Debug::export_payload($email_events,'json');$email_csv=ZMP_Debug::export_payload($email_events,'csv');$email_serialized=wp_json_encode($email_events).$email_json.$email_csv;
    $assert(count($email_events)>0,'email_capture_contains_service_events');
    $assert(strpos($email_serialized,$email_a)===false&&strpos($email_serialized,$email_b)===false&&strpos($email_serialized,$reason)===false&&strpos($email_serialized,'NAME_SENTINEL_'.$token)===false&&strpos($email_serialized,'NONCE_SENTINEL_'.$token)===false,'email_diagnostics_redact_sensitive_inputs');
    $audit_rows=$wpdb->get_col($wpdb->prepare("SELECT event_data FROM {$prefix}zmp_school_email_request_events WHERE request_id IN (%d,%d)",$request_id,$fresh_id));
    $audit_serialized=wp_json_encode($audit_rows);
    $assert(strpos($audit_serialized,$email_a)===false&&strpos($audit_serialized,$email_b)===false&&strpos($audit_serialized,$reason)===false,'audit_events_exclude_email_and_reason');
    $request_table_rows=$wpdb->get_results($wpdb->prepare("SELECT id,requester_user_id,owner_user_id,school_id,status,approved_email_hash,approved_email_epoch FROM {$prefix}zmp_school_email_requests WHERE id IN (%d,%d)",$request_id,$fresh_id),ARRAY_A);
    $assert(count($request_table_rows)===2&&strpos(wp_json_encode($request_table_rows),$email_a)===false&&strpos(wp_json_encode($request_table_rows),$email_b)===false,'request_storage_contains_no_raw_email');
    $error_log_text=is_file($error_log)?(string)file_get_contents($error_log):'';
    $assert(strpos($error_log_text,$email_a)===false&&strpos($error_log_text,$email_b)===false&&strpos($error_log_text,$reason)===false,'php_error_log_excludes_synthetic_private_data');
    $assert($mail_calls===0,'no_email_was_sent_or_attempted');
    $assert($wpdb->last_error==='','wpdb_has_no_error');
    ZMP_Debug::stop_capture();
    $feature_manifest=array('base_manifest'=>'/tmp/zmp64-isolated-20261103/final-run/manifest.json','database'=>$wpdb->dbname,'prefix'=>$prefix,'created_user_ids'=>$created_users,'request_ids'=>$request_ids,'pending_ui_request_id'=>(int)($ui_pending['request_id']??0),'diagnostic_sessions'=>$diagnostic_sessions,'owned_record_ids'=>array('requester_school'=>$requester_school,'owner_school'=>$owner_school,'owner_mentor'=>$owner_mentor,'requester_mentor'=>$requester_mentor));
    file_put_contents('/tmp/zmp64-isolated-20261103/final-run/feature-manifest.json',wp_json_encode($feature_manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
} catch(Throwable $e) {
    $failures[]='unexpected_exception';$checks[]=array('assertion'=>'unexpected_exception','result'=>'FAIL');
} finally {
    ZMP_Debug::stop_capture();
    remove_filter('pre_wp_mail',$mail_filter,10);
    if($old_error_log!==false){ini_set('error_log',(string)$old_error_log);}
}
$out=array('status'=>$failures?'FAIL':'PASS','assertion_count'=>count($checks),'failures'=>$failures,'checks'=>$checks,'database'=>$wpdb->dbname,'version'=>ZMP_VERSION,'legacy_ids'=>array('owner_user_id'=>$owner_id,'requester_user_id'=>$requester_id,'president_user_id'=>$president_id,'owner_school_id'=>$owner_school,'requester_school_id'=>$requester_school),'request_ids'=>$request_ids,'diagnostic_session_ids'=>$diagnostic_sessions,'mail_calls'=>$mail_calls,'created_user_ids'=>$created_users,'pending_ui_request_id'=>(int)($ui_pending['request_id']??0));
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
