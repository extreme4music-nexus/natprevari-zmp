<?php
/** Targeted `.64` follow-up for the previously failed email, diagnostics and fixture-order assertions. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_migration_final' || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.64') {
    fwrite(STDERR, "wrong_test_runtime\n"); exit(3);
}
$manifest_path='/tmp/zmp64-isolated-20261103/final-run/manifest.json';
$manifest=json_decode((string)file_get_contents($manifest_path),true);
if (!is_array($manifest) || ($manifest['database']??'')!=='zmp64_migration_final' || empty($manifest['ids'])) {
    fwrite(STDERR, "synthetic_manifest_unavailable\n"); exit(4);
}
$ids=$manifest['ids'];
$owner_id=(int)($ids['users']['owner']??0); $requester_id=(int)($ids['users']['requester']??0); $president_id=(int)($ids['users']['president']??0);
$owner_mentor=(int)($ids['mentors']['owner']??0); $owner_school=(int)($ids['schools']['owner_second']??0); $other_school=(int)($ids['schools']['other']??0);
if (!$owner_id || !$requester_id || !$president_id || !$owner_mentor || !$owner_school || !$other_school) { fwrite(STDERR,"synthetic_ids_unavailable\n"); exit(5); }
$checks=array(); $details=array(); $assert=static function($label,$condition,$detail='')use(&$checks,&$details){$checks[$label]=$condition?'PASS':'FAIL';if($detail!==''){$details[$label]=$detail;}};
$profile_table=$wpdb->prefix.'zmp_mentor_school_profiles';
$request_table=$wpdb->prefix.'zmp_school_email_requests';
$mail_calls=0; $mail_filter=static function($pre,$atts)use(&$mail_calls){$mail_calls++;return true;}; add_filter('pre_wp_mail',$mail_filter,PHP_INT_MAX,2);
$old_post=$_POST; $old_request=$_REQUEST; $old_user=get_current_user_id();
$notice_key='zmp_school_profile_notice_'.$owner_id; $notice_before=get_transient($notice_key);
$debug_option_name=ZMP_Debug::OPTION; $debug_option_before=get_option($debug_option_name,'__zmp_targeted_absent__');
$debug_capture_before=get_option(ZMP_Debug::CAPTURE_OPTION,null);
$profile_created_by_test=false; $profile_baseline_missing=false; $ui_owner_ready=false;
$marker=wp_generate_uuid4(); $raw_markers=array('reason'=>'reason-'.$marker,'email'=>'mail-'.$marker.'@example.invalid','name'=>'name-'.$marker,'nonce'=>'nonce-'.$marker,'message'=>'message-'.$marker,'payload'=>'payload-'.$marker);
$get_profile_row=static function()use($wpdb,$profile_table,$owner_mentor,$owner_school){return $wpdb->get_row($wpdb->prepare("SELECT official_email,official_email_epoch FROM {$profile_table} WHERE mentor_id=%d AND school_id=%d LIMIT 1",$owner_mentor,$owner_school),ARRAY_A);};
$get_epoch=static function()use($get_profile_row){$row=$get_profile_row();return is_array($row)?(int)$row['official_email_epoch']:null;};
$post_profile=static function($payload)use($owner_id,$owner_school,$notice_key){
    $_POST=wp_slash(array('zmp_school_profile_nonce'=>wp_create_nonce('zmp_school_profile_update_'.$owner_id),'zmp_school_profile'=>array($owner_school=>$payload)));
    $_REQUEST=array(); ZMP_School_Profile_UI::save($owner_id); $status=get_transient($notice_key); delete_transient($notice_key); return $status;
};
try {
    $assert('synthetic_manifest_and_candidate_loaded',true);
    $assert('no_capture_was_already_active',empty($debug_capture_before),empty($debug_capture_before)?'':'active diagnostic capture prevents isolated assertion');

    wp_set_current_user($owner_id);
    $ui_owner_ready=current_user_can('edit_user',$owner_id) && ZMP_Mentor_School_Profile::is_teacher($owner_id);
    $assert('owner_can_use_actual_profile_ui',$ui_owner_ready);
    $profile_before=ZMP_Mentor_School_Profile::get_profile($owner_id,$owner_school);
    $row_before=$get_profile_row();
    $request_count_before=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$request_table} WHERE school_id=%d",$owner_school));
    $profile_baseline_missing=!is_array($row_before);
    $assert('unused_synthetic_school_profile_is_empty',$profile_baseline_missing && is_array($profile_before) && ($profile_before['official_email']??null)===''
        && $request_count_before===0,'the selected synthetic owner-school must have no profile or email requests before the check');
    if ($ui_owner_ready && $profile_baseline_missing && is_array($profile_before) && $request_count_before===0) {
        $base=array(
            'official_email'=>'','employment_term'=>(string)$profile_before['employment_term'],'workload'=>(string)$profile_before['workload'],
            'replacement_for'=>(string)$profile_before['replacement_for'],'replacement_start_date'=>(string)$profile_before['replacement_start_date'],
            'replacement_end_date'=>(string)$profile_before['replacement_end_date'],'retired'=>(string)$profile_before['retired'],'replacement'=>(string)$profile_before['replacement'],
            'employment_term_visible'=>(string)$profile_before['employment_term_visible'],'workload_visible'=>(string)$profile_before['workload_visible'],
            'retired_visible'=>(string)$profile_before['retired_visible'],'replacement_visible'=>(string)$profile_before['replacement_visible'],
            'replacement_for_visible'=>(string)$profile_before['replacement_for_visible'],'replacement_start_date_visible'=>(string)$profile_before['replacement_start_date_visible'],
            'replacement_end_date_visible'=>(string)$profile_before['replacement_end_date_visible']
        );
        $invalid=$base; $invalid['official_email']='synthetic-'.$marker.' @example.invalid';
        $sanitized_invalid=sanitize_email($invalid['official_email']);
        $invalid_notice=$post_profile($invalid);
        $invalid_row=$get_profile_row();
        $assert('malformed_email_fixture_would_sanitize_to_valid_address',is_email($sanitized_invalid));
        $assert('profile_ui_rejects_malformed_nonempty_email_without_row_or_epoch_change',$invalid_notice==='error' && !is_array($invalid_row) && $get_epoch()===null);

        $empty_notice=$post_profile($base); $empty_row=$get_profile_row();
        $profile_created_by_test=is_array($empty_row);
        $assert('profile_ui_accepts_empty_optional_email',$empty_notice==='success' && is_array($empty_row) && $empty_row['official_email']==='' && (int)$empty_row['official_email_epoch']===1);
    }

    wp_set_current_user($requester_id);
    $cross_school=ZMP_School_Email_Access::submit_request($requester_id,$owner_id,$other_school,'Synthetic fail-closed scope check '.$marker);
    $assert('foreign_school_request_is_denied_fail_closed',is_wp_error($cross_school) && $cross_school->get_error_code()==='school_not_owned',is_wp_error($cross_school)?$cross_school->get_error_code():'unexpected success');

    wp_set_current_user($president_id);
    $pending_rows=$wpdb->get_results($wpdb->prepare("SELECT id FROM {$request_table} WHERE owner_user_id=%d AND status='pending' ORDER BY id DESC",$owner_id),ARRAY_A);
    $president_queue=ZMP_School_Email_Access::pending_for_active_president($president_id);
    $pending_ids=array_map(static function($row){return(int)$row['id'];},(array)$pending_rows);
    $queue_match=null; foreach((array)$president_queue as $queue_item){$qid=(int)($queue_item['request_id']??0);if(in_array($qid,$pending_ids,true)){$queue_match=$queue_item;break;}}
    $queue_json=is_array($queue_match)?wp_json_encode($queue_match):'';
    $assert('president_pending_queue_contains_pending_request_without_email',is_array($queue_match) && !array_key_exists('official_email',$queue_match) && strpos($queue_json,'@example.invalid')===false);

    if (empty($debug_capture_before)) {
        ZMP_Debug::log('info',$raw_markers['message'],array('school_id'=>$owner_school,'user_id'=>$owner_id,'reason'=>$raw_markers['reason'],'official_email'=>$raw_markers['email'],'teacher_name'=>$raw_markers['name'],'nonce'=>$raw_markers['nonce'],'private_payload'=>array('value'=>$raw_markers['payload'])),'school_profile');
        ZMP_Debug::log('info','School profile saved.',array('school_id'=>$owner_school,'user_id'=>$owner_id,'outcome'=>'saved'),'school_profile');
        ZMP_Debug::log('info',$raw_markers['message'],array('school_id'=>$owner_school,'user_id'=>$requester_id,'reason'=>$raw_markers['reason'],'official_email'=>$raw_markers['email'],'teacher_name'=>$raw_markers['name'],'nonce'=>$raw_markers['nonce'],'private_payload'=>array('value'=>$raw_markers['payload'])),'school_email_access');
        ZMP_Debug::log('info','School email access request submitted.',array('school_id'=>$owner_school,'requester_user_id'=>$requester_id,'outcome'=>'pending'),'school_email_access');
        ZMP_Debug::log('info','Teacher Matrices tab requested during diagnostic capture.',array('school_id'=>$owner_school,'user_id'=>$owner_id),'matrices_audio_tools');
        $all_events=get_option($debug_option_name,array()); $new_events=array_slice((array)$all_events,-5);
        $profile_events=ZMP_Debug::filter_events($new_events,'school_profile'); $email_events=ZMP_Debug::filter_events($new_events,'school_email_access');
        $legacy_events=ZMP_Debug::filter_events($new_events,'matrices_audio_tools');
        $profile_serialized=wp_json_encode($profile_events).ZMP_Debug::export_payload($profile_events,'json').ZMP_Debug::export_payload($profile_events,'csv');
        $email_serialized=wp_json_encode($email_events).ZMP_Debug::export_payload($email_events,'json').ZMP_Debug::export_payload($email_events,'csv');
        $all_serialized=wp_json_encode($new_events).ZMP_Debug::export_payload($new_events,'json').ZMP_Debug::export_payload($new_events,'csv');
        $profile_clean=true; $email_clean=true;
        foreach($raw_markers as $value){if(strpos($profile_serialized,$value)!==false){$profile_clean=false;}if(strpos($email_serialized,$value)!==false){$email_clean=false;}if(strpos($all_serialized,$value)!==false){$profile_clean=false;$email_clean=false;}}
        $profile_fixed=false; $email_fixed=false; $legacy_fixed=false; $flow_ids=true;
        foreach(array_merge($profile_events,$email_events,$legacy_events) as $event){if(!preg_match('/^[a-f0-9-]{36}$/',(string)($event['flow_id']??''))){$flow_ids=false;}}
        foreach($profile_events as $event){if(($event['message']??'')==='School profile saved.'){$profile_fixed=true;}}
        foreach($email_events as $event){if(($event['message']??'')==='School email access request submitted.'){$email_fixed=true;}}
        foreach($legacy_events as $event){if(($event['message']??'')==='Teacher Matrices tab requested during diagnostic capture.'){$legacy_fixed=true;}}
        $assert('profile_diagnostics_redact_sensitive_context_and_free_text',$profile_clean);
        $assert('email_diagnostics_redact_sensitive_context_and_free_text',$email_clean);
        $assert('diagnostics_keep_fixed_legacy_and_new_messages_and_generated_flow_ids',$profile_fixed && $email_fixed && $legacy_fixed && $flow_ids);
    } else {
        $assert('diagnostic_capture_inactive_for_isolated_log_recheck',false,'active capture prevents category-bound message assertions');
    }
} finally {
    if ($profile_created_by_test && $profile_baseline_missing) { $wpdb->delete($profile_table,array('mentor_id'=>$owner_mentor,'school_id'=>$owner_school),array('%d','%d')); }
    if ($debug_option_before==='__zmp_targeted_absent__') { delete_option($debug_option_name); } else { update_option($debug_option_name,$debug_option_before,false); }
    if ($notice_before===false) { delete_transient($notice_key); } else { set_transient($notice_key,$notice_before,60); }
    remove_filter('pre_wp_mail',$mail_filter,PHP_INT_MAX);
    $_POST=$old_post; $_REQUEST=$old_request; wp_set_current_user($old_user);
}
$assert('test_owned_profile_row_cleaned',$profile_baseline_missing && !is_array($get_profile_row()));
$assert('no_email_send_attempted',$mail_calls===0);
$assert('database_connection_has_no_error',$wpdb->last_error==='');
$out=array('status'=>in_array('FAIL',$checks,true)?'FAIL':'PASS','checks'=>$checks,'details'=>$details,'mail_calls'=>$mail_calls,'database'=>$wpdb->dbname);
echo wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
