<?php
global $wpdb;
wp_set_current_user(1);
foreach (array('ZMP_Mentor_School_Profile','ZMP_Mentor_Schools','ZMP_President_Mandate','ZMP_School_Email_Access','ZMP_Debug') as $class) {
    if (!class_exists($class)) WP_CLI::error('required local service missing');
}
if (DB_NAME!=='zmp66b_isolated'||$wpdb->prefix!=='z66a_'||!current_user_can('manage_options')
    ||!ZMP_Mentor_School_Profile::schema_is_compatible()||!ZMP_Mentor_Schools::schema_is_compatible()
    ||!ZMP_School_Email_Access::schema_is_compatible()) WP_CLI::error('local database/schema/admin preflight failed');
$mandate_table=ZMP_President_Mandate::table_name();
$active=$wpdb->get_row("SELECT id,user_id,status FROM {$mandate_table} WHERE status='active'",ARRAY_A);
if(!is_array($active)||(int)$active['id']!==58||(int)$active['user_id']!==116) WP_CLI::error('active synthetic President baseline mismatch');
$role_meta_key=$wpdb->get_blog_prefix().'capabilities';
$president_role_before=$wpdb->get_row($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=116 AND meta_key=%s",$role_meta_key),ARRAY_A);
if(!is_array($president_role_before)) WP_CLI::error('active President role metadata missing');
$token=strtolower(wp_generate_password(14,false,false));
$school_name='Synthetic disclosure school '.$token;$municipality='Скопје';
$email='approved-contact-'.$token.'@example.test';
$users=array();$mentors=array();$schools=array();$request_id=0;$checks=array();
$tables=array('users'=>$wpdb->users,'usermeta'=>$wpdb->usermeta,'mentors'=>$wpdb->prefix.'zmp_mentors','schools'=>$wpdb->prefix.'zmp_mentor_schools','profiles'=>$wpdb->prefix.'zmp_mentor_school_profiles','requests'=>$wpdb->prefix.'zmp_school_email_requests','events'=>$wpdb->prefix.'zmp_school_email_request_events','mandates'=>$mandate_table,'mandate_audit'=>ZMP_President_Mandate::audit_table_name());
$counts=array();foreach($tables as $k=>$t)$counts[$k]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}");
$debug_table=$wpdb->options;$debug_name=ZMP_Debug::OPTION;
$debug_before=$wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s",$debug_name),ARRAY_A);
$assert=static function($name,$ok)use(&$checks){$checks[$name]=$ok===true;};
$make_teacher=static function($part)use(&$users,$token){$login='zmp66_disclose_'.$part.'_'.$token;$id=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(36,true,true),'user_email'=>$login.'@example.test','display_name'=>'Synthetic '.$part,'role'=>'zmp_teacher'));if(is_wp_error($id)||(int)$id<1)throw new RuntimeException('teacher creation failed');$id=(int)$id;$users[]=$id;get_user_by('id',$id)->add_role('subscriber');clean_user_cache($id);return $id;};
try {
    $requester=$make_teacher('requester');$owner=$make_teacher('owner');$outsider=$make_teacher('outsider');
    foreach(array($requester,$owner,$outsider) as $uid){$u=get_userdata($uid);$ok=$wpdb->insert($wpdb->prefix.'zmp_mentors',array('wp_user_id'=>$uid,'full_name'=>$u->display_name,'email'=>$u->user_email,'phone'=>'','school_name'=>$school_name,'municipality'=>$municipality,'region'=>'Скопски','login_token'=>wp_generate_password(32,false)));if($ok!==1)throw new RuntimeException('mentor row creation failed');$mentors[$uid]=(int)$wpdb->insert_id;}
    wp_set_current_user($requester);$requester_school=ZMP_Mentor_Schools::add_school($mentors[$requester],$school_name,$municipality,true);if(is_wp_error($requester_school)||(int)$requester_school<1)throw new RuntimeException('requester engagement setup failed');$schools[]=(int)$requester_school;
    wp_set_current_user($owner);$owner_school=ZMP_Mentor_Schools::add_school($mentors[$owner],$school_name,$municipality,true);if(is_wp_error($owner_school)||(int)$owner_school<1)throw new RuntimeException('owner engagement setup failed');$schools[]=(int)$owner_school;
    $payload=array('official_email'=>$email,'employment_term'=>'indefinite','workload'=>'full','retired'=>0,'replacement'=>0,'replacement_for'=>'','replacement_start_date'=>'','replacement_end_date'=>'','employment_term_visible'=>0,'workload_visible'=>0,'retired_visible'=>0,'replacement_visible'=>0,'replacement_for_visible'=>0,'replacement_start_date_visible'=>0,'replacement_end_date_visible'=>0);
    $saved=ZMP_Mentor_School_Profile::save_profile($owner,(int)$owner_school,$payload);if(is_wp_error($saved))throw new RuntimeException('owner profile setup failed');
    wp_set_current_user($requester);$submitted=ZMP_School_Email_Access::submit_request($requester,$owner,(int)$requester_school,'Synthetic request for approved address disclosure '.$token);if(is_wp_error($submitted)||($submitted['status']??'')!=='pending')throw new RuntimeException('request submission failed');$request_id=(int)$submitted['request_id'];
    $president_user=get_user_by('id',116);foreach(array('zmp_teacher','subscriber','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer') as $role)$president_user->add_role($role);clean_user_cache(116);
    wp_set_current_user(116);$approved=ZMP_School_Email_Access::decide($request_id,116,'approved');if(is_wp_error($approved)||$approved!==true)throw new RuntimeException('President approval failed');
    wp_set_current_user($requester);$_POST=array('_wpnonce'=>wp_create_nonce('zmp_school_email_disclose_'.$request_id));$_REQUEST=$_POST;
    $revealed=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester);
    $events_table=$wpdb->prefix.'zmp_school_email_request_events';
    $event_rows=(array)$wpdb->get_results($wpdb->prepare("SELECT event_type,event_data FROM {$events_table} WHERE request_id=%d ORDER BY id",$request_id),ARRAY_A);
    $disclosed=count(array_filter($event_rows,static function($e){return $e['event_type']==='disclosed';}));
    $event_data_safe=true;foreach($event_rows as $e)if(strpos((string)$e['event_data'],$email)!==false)$event_data_safe=false;
    $assert('approved_requester_with_valid_nonce_reads_exact_contact_and_is_audited',$revealed===$email&&$disclosed===1&&$event_data_safe);
    $event_count_before=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$events_table} WHERE request_id=%d",$request_id));
    wp_set_current_user($outsider);$_POST=array('_wpnonce'=>wp_create_nonce('zmp_school_email_disclose_'.$request_id));$_REQUEST=$_POST;
    $outsider_result=ZMP_School_Email_Access::disclose_approved_email($request_id,$outsider);
    $assert('other_teacher_cannot_read_approved_contact_or_append_disclosure_event',is_wp_error($outsider_result)&&in_array($outsider_result->get_error_code(),array('forbidden','not_approved'),true)&&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$events_table} WHERE request_id=%d",$request_id))===$event_count_before);
    wp_set_current_user($requester);$_POST=array('_wpnonce'=>'invalid-nonce');$_REQUEST=$_POST;
    $nonce_result=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester);
    $assert('requester_invalid_nonce_cannot_read_contact_or_append_event',is_wp_error($nonce_result)&&$nonce_result->get_error_code()==='forbidden'&&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$events_table} WHERE request_id=%d",$request_id))===$event_count_before);
    $changed_email='changed-contact-'.$token.'@example.test';
    $changed_payload=$payload;$changed_payload['official_email']=$changed_email;
    wp_set_current_user($owner);$profile_changed=ZMP_Mentor_School_Profile::save_profile($owner,(int)$owner_school,$changed_payload);
    if(is_wp_error($profile_changed))throw new RuntimeException('synthetic email change failed');
    wp_set_current_user($requester);$_POST=array('_wpnonce'=>wp_create_nonce('zmp_school_email_disclose_'.$request_id));$_REQUEST=$_POST;
    $stale_result=ZMP_School_Email_Access::disclose_approved_email($request_id,$requester);
    $stale_row=$wpdb->get_row($wpdb->prepare("SELECT status,approved_email_hash,approved_email_epoch FROM {$wpdb->prefix}zmp_school_email_requests WHERE id=%d",$request_id),ARRAY_A);
    $all_events=(array)$wpdb->get_results($wpdb->prepare("SELECT event_type,event_data FROM {$events_table} WHERE request_id=%d ORDER BY id",$request_id),ARRAY_A);
    $has_contact_changed=(bool)array_filter($all_events,static function($e){return $e['event_type']==='contact_changed';});
    $stale_audit_safe=true;foreach($all_events as $e)if(strpos((string)$e['event_data'],$email)!==false||strpos((string)$e['event_data'],$changed_email)!==false)$stale_audit_safe=false;
    $assert('changed_contact_invalidates_old_approval_without_disclosing_stale_address',is_wp_error($stale_result)&&$stale_result->get_error_code()==='contact_unavailable'&&is_array($stale_row)&&$stale_row['status']==='invalidated'&&$stale_row['approved_email_hash']===''&&(int)$stale_row['approved_email_epoch']===0&&$has_contact_changed&&$stale_audit_safe);
} catch(Throwable $e){WP_CLI::warning('synthetic disclosure test setup/runtime failure: '.sanitize_key($e->getMessage()));$checks['runtime_completed']=false;}
finally {
    wp_set_current_user(1);
    if($request_id){$wpdb->delete($wpdb->prefix.'zmp_school_email_request_events',array('request_id'=>$request_id),array('%d'));$wpdb->delete($wpdb->prefix.'zmp_school_email_requests',array('id'=>$request_id),array('%d'));}
    foreach($mentors as $uid=>$mid){foreach($schools as $sid){$wpdb->delete($wpdb->prefix.'zmp_mentor_school_profiles',array('mentor_id'=>$mid,'school_id'=>(int)$sid),array('%d','%d'));$wpdb->delete($wpdb->prefix.'zmp_mentor_schools',array('id'=>(int)$sid,'mentor_id'=>(int)$mid),array('%d','%d'));}$wpdb->delete($wpdb->prefix.'zmp_mentors',array('id'=>(int)$mid,'wp_user_id'=>(int)$uid),array('%d','%d'));}
    foreach($users as $uid)if(get_userdata((int)$uid))wp_delete_user((int)$uid);
    $wpdb->update($wpdb->usermeta,array('meta_value'=>$president_role_before['meta_value']),array('umeta_id'=>(int)$president_role_before['umeta_id'],'user_id'=>116,'meta_key'=>$role_meta_key),array('%s'),array('%d'));clean_user_cache(116);
    wp_cache_delete($debug_name,'options');wp_cache_delete('alloptions','options');
    $current=$wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s",$debug_name),ARRAY_A);
    if(is_array($debug_before)){if(is_array($current))$wpdb->update($debug_table,array('option_value'=>$debug_before['option_value'],'autoload'=>$debug_before['autoload']),array('option_id'=>(int)$current['option_id']),array('%s','%s'),array('%d'));else$wpdb->insert($debug_table,array('option_name'=>$debug_name,'option_value'=>$debug_before['option_value'],'autoload'=>$debug_before['autoload']),array('%s','%s','%s'));}elseif(is_array($current))$wpdb->delete($debug_table,array('option_id'=>(int)$current['option_id']),array('%d'));
    wp_cache_delete($debug_name,'options');wp_cache_delete('alloptions','options');
    $after=array();foreach($tables as $k=>$t)$after[$k]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t}");
    $assert('exact_synthetic_rows_users_and_existing_mandate_restored',$counts===$after&&(int)$wpdb->get_var("SELECT COUNT(*) FROM {$mandate_table} WHERE id=58 AND user_id=116 AND status='active'")===1);
    $debug_after=$wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s",$debug_name),ARRAY_A);
    $assert('diagnostic_option_restored_exactly',$debug_before===$debug_after);
    $role_after=$wpdb->get_row($wpdb->prepare("SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=116 AND meta_key=%s",$role_meta_key),ARRAY_A);
    $assert('active_president_role_metadata_restored_exactly',$president_role_before===$role_after);
}
foreach($checks as $name=>$ok)echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
$pass=count(array_filter($checks,static function($v){return $v===true;}));
echo 'SUMMARY '.$pass.'/'.count($checks).' PASS'.PHP_EOL;
if($pass!==count($checks))exit(1);
