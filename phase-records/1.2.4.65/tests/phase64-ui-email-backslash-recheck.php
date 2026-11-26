<?php
/** Focused actual-UI check for the one-unslash backslash email edge. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_migration_final' || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.64') { fwrite(STDERR,"wrong_test_runtime\n"); exit(3); }
$manifest=json_decode((string)file_get_contents('/tmp/zmp64-isolated-20261103/final-run/manifest.json'),true);
if (!is_array($manifest) || ($manifest['database']??'')!=='zmp64_migration_final' || empty($manifest['ids'])) { fwrite(STDERR,"synthetic_manifest_unavailable\n"); exit(4); }
$ids=$manifest['ids']; $owner_id=(int)($ids['users']['owner']??0); $owner_mentor=(int)($ids['mentors']['owner']??0); $school_id=(int)($ids['schools']['owner_second']??0);
if (!$owner_id || !$owner_mentor || !$school_id) { fwrite(STDERR,"synthetic_ids_unavailable\n"); exit(5); }
$profile_table=$wpdb->prefix.'zmp_mentor_school_profiles'; $request_table=$wpdb->prefix.'zmp_school_email_requests';
$checks=array(); $details=array(); $assert=static function($label,$ok,$detail='')use(&$checks,&$details){$checks[$label]=$ok?'PASS':'FAIL';if($detail!==''){$details[$label]=$detail;}};
$get_row=static function()use($wpdb,$profile_table,$owner_mentor,$school_id){return $wpdb->get_row($wpdb->prepare("SELECT official_email,official_email_epoch FROM {$profile_table} WHERE mentor_id=%d AND school_id=%d LIMIT 1",$owner_mentor,$school_id),ARRAY_A);};
$get_epoch=static function()use($get_row){$row=$get_row();return is_array($row)?(int)$row['official_email_epoch']:null;};
$old_post=$_POST; $old_request=$_REQUEST; $old_user=get_current_user_id();
$notice_key='zmp_school_profile_notice_'.$owner_id; $notice_before=get_transient($notice_key); $debug_before=get_option(ZMP_Debug::OPTION,'__zmp_targeted_absent__');
$mail_calls=0; $mail_filter=static function($pre,$atts)use(&$mail_calls){$mail_calls++;return true;}; add_filter('pre_wp_mail',$mail_filter,PHP_INT_MAX,2);
$baseline_missing=false; $profile_created=false; $markers=array();
try {
    wp_set_current_user($owner_id);
    $ui_ready=current_user_can('edit_user',$owner_id) && ZMP_Mentor_School_Profile::is_teacher($owner_id);
    $profile=ZMP_Mentor_School_Profile::get_profile($owner_id,$school_id); $row_before=$get_row();
    $request_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$request_table} WHERE school_id=%d",$school_id));
    $baseline_missing=!is_array($row_before);
    $assert('synthetic_ui_preconditions', $ui_ready && $baseline_missing && is_array($profile) && ($profile['official_email']??null)==='' && $request_count===0);
    if ($ui_ready && $baseline_missing && is_array($profile) && $request_count===0) {
        $marker=wp_generate_uuid4(); $raw_email='synthetic-'.$marker.'\\suffix@example.invalid';
        $markers=array('marker'=>$marker,'raw'=>$raw_email,'sanitized'=>sanitize_email($raw_email));
        $assert('backslash_input_becomes_valid_only_if_unslashed_twice',is_email($markers['sanitized']) && $markers['sanitized']!==$raw_email);
        $payload=array(
            'official_email'=>$raw_email,'employment_term'=>(string)$profile['employment_term'],'workload'=>(string)$profile['workload'],
            'replacement_for'=>(string)$profile['replacement_for'],'replacement_start_date'=>(string)$profile['replacement_start_date'],
            'replacement_end_date'=>(string)$profile['replacement_end_date'],'retired'=>(string)$profile['retired'],'replacement'=>(string)$profile['replacement'],
            'employment_term_visible'=>(string)$profile['employment_term_visible'],'workload_visible'=>(string)$profile['workload_visible'],
            'retired_visible'=>(string)$profile['retired_visible'],'replacement_visible'=>(string)$profile['replacement_visible'],
            'replacement_for_visible'=>(string)$profile['replacement_for_visible'],'replacement_start_date_visible'=>(string)$profile['replacement_start_date_visible'],
            'replacement_end_date_visible'=>(string)$profile['replacement_end_date_visible']
        );
        $_POST=wp_slash(array('zmp_school_profile_nonce'=>wp_create_nonce('zmp_school_profile_update_'.$owner_id),'zmp_school_profile'=>array($school_id=>$payload)));
        $_REQUEST=array(); ZMP_School_Profile_UI::save($owner_id); $notice=get_transient($notice_key); delete_transient($notice_key);
        $after=$get_row(); $profile_created=is_array($after);
        $assert('actual_ui_rejects_one_unslashed_backslash_email_without_mutation',$notice==='error' && !is_array($after) && $get_epoch()===null);
    }
} finally {
    if ($baseline_missing && $profile_created) { $wpdb->delete($profile_table,array('mentor_id'=>$owner_mentor,'school_id'=>$school_id),array('%d','%d')); }
    if ($debug_before==='__zmp_targeted_absent__') { delete_option(ZMP_Debug::OPTION); } else { update_option(ZMP_Debug::OPTION,$debug_before,false); }
    if ($notice_before===false) { delete_transient($notice_key); } else { set_transient($notice_key,$notice_before,60); }
    remove_filter('pre_wp_mail',$mail_filter,PHP_INT_MAX); $_POST=$old_post; $_REQUEST=$old_request; wp_set_current_user($old_user);
}
$assert('synthetic_profile_row_absent_after_cleanup',$baseline_missing && !is_array($get_row()));
$assert('no_email_send_attempted',$mail_calls===0);
$assert('database_connection_has_no_error',$wpdb->last_error==='');
$out=array('status'=>in_array('FAIL',$checks,true)?'FAIL':'PASS','checks'=>$checks,'details'=>$details,'mail_calls'=>$mail_calls,'database'=>$wpdb->dbname);
echo wp_json_encode($out,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
