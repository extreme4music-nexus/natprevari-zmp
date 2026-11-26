<?php
/**
 * Verifies safe first-time self-setup of the current President's own teacher profile.
 * Clone-only; exercises the real profile form/save path, rejects email collisions,
 * and restores every touched synthetic table/option. No HTTP or email.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI){fwrite(STDOUT,"BLOCKED: WP-CLI only; writes=0\n");exit(2);}
$site='/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site';$origin='http://127.0.0.1:8102';
$db='zmp66a_isolated';$db_host='localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock';$prefix='z66a_';
$fixture='/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-self-setup.php';
$plugin='zmp-competitions/zmp-competitions.php';$plugin_dir=WP_PLUGIN_DIR.'/zmp-competitions';
$hashes=array(
    'includes/class-zmp-support.php'=>'a44ffaf243e185abb32b0e1b4706ef9febee6ec1764710d09ecaf8de60487d81',
    'includes/class-zmp-mentor-subjects.php'=>'d9d6ba3b5dd56c20e4cbb1e830a44a3210824b18da3f7c3a9e920cdeef87c245',
    'includes/class-zmp-mentor-school-profile.php'=>'bed1c58e6d895fa04907d6c53caa9494946fbf0bf53c13e115d0af8a834f6c31',
    'includes/class-zmp-mentor-schools.php'=>'72d8ec5c13817c9825539aba015e765831a95d477432cf846de05832f54f1b46',
    'includes/class-zmp-database.php'=>'ed0c587d041c06f00dbc122e3aff0bf2a8c8708193630ba82e1fee1a23762e16',
    'includes/class-zmp-regions.php'=>'3557ad3bfec2f701627cb546b16650f3f08c3924affba54b48a3a43450830222',
);
$args_for_eval=isset($args)&&is_array($args)?$args:array();$invocation=$args_for_eval[0]??'';global $wpdb;
if(!defined('DB_NAME')||DB_NAME!==$db||!defined('DB_HOST')||DB_HOST!==$db_host||$wpdb->prefix!==$prefix
    ||rtrim((string)@realpath(ABSPATH),'/')!==$site||rtrim(home_url('/'),'/')!==$origin||rtrim(site_url('/'),'/')!==$origin
    ||rtrim((string)@realpath($invocation),'/')!==$fixture){fwrite(STDOUT,"BLOCKED: exact clone/invocation identity; writes=0\n");exit(2);}
require_once ABSPATH.'wp-admin/includes/plugin.php';require_once ABSPATH.'wp-admin/includes/user.php';
$installed=get_plugins();$active=(array)get_option('active_plugins',array());
foreach($hashes as $relative=>$expected){$actual=is_file($plugin_dir.'/'.$relative)?strtolower((string)hash_file('sha256',$plugin_dir.'/'.$relative)):'';if(!hash_equals($expected,$actual)){fwrite(STDOUT,"BLOCKED: exact source hash mismatch; writes=0\n");exit(2);}}
if(!is_array($installed)||array_keys($installed)!==array($plugin)||$active!==array($plugin)||get_option('zmp_passkey_schema_version')!=='3'
    ||!class_exists('ZMP_Support')||!class_exists('ZMP_Mentor_Subjects')||!class_exists('ZMP_Mentor_School_Profile')
    ||!class_exists('ZMP_Mentor_Schools')||!class_exists('ZMP_Regions')||!ZMP_Mentor_School_Profile::schema_is_compatible()
    ||!ZMP_Mentor_Schools::schema_is_compatible()||!get_role('zmp_president')){
    fwrite(STDOUT,"BLOCKED: plugin/schema/role preflight; writes=0\n");exit(2);}
$user_table=$wpdb->users;$usermeta_table=$wpdb->usermeta;$mentor_table=$wpdb->prefix.'zmp_mentors';
$school_table=$wpdb->prefix.'zmp_mentor_schools';$profile_table=ZMP_Mentor_School_Profile::table_name();
$assignment_table=$wpdb->prefix.'zmp_mentor_school_subjects';$grade_table=$wpdb->prefix.'zmp_mentor_school_subject_grades';
$tables=array($user_table,$usermeta_table,$mentor_table,$school_table,$profile_table,$assignment_table,$grade_table,
    $wpdb->prefix.'zmp_passkey_credentials',$wpdb->prefix.'zmp_passkey_challenges',$wpdb->prefix.'zmp_subject_proposals',
    $wpdb->prefix.'zmp_subject_proposal_events',ZMP_President_Mandate::table_name(),ZMP_President_Mandate::audit_table_name());
$count=static function($table)use($wpdb){return(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");};
$table_fp=static function($table)use($wpdb){$rows=$wpdb->get_results("SELECT * FROM {$table} ORDER BY 1",ARRAY_A);return hash('sha256',serialize($rows?:array()));};
$missing=new stdClass();$role_option=$wpdb->get_blog_prefix().'user_roles';
wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');
$debug_before=get_option('zmp_debug_events',$missing);$capture_before=get_option('zmp_debug_capture',$missing);
$snapshot=static function()use($tables,$count,$table_fp,$role_option,$missing){$out=array();foreach($tables as $table){$out[$table]=array($count($table),$table_fp($table));}
    $out['roles']=hash('sha256',serialize(get_option($role_option,array())));$out['debug']=hash('sha256',serialize(get_option('zmp_debug_events',$missing)));
    $out['capture']=hash('sha256',serialize(get_option('zmp_debug_capture',$missing)));return $out;};
$before=$snapshot();$protected=get_user_by('login','zmp66a_admin');
$protected_credentials=$protected?(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$wpdb->prefix.'zmp_passkey_credentials WHERE user_id=%d',(int)$protected->ID)):0;
if(!$protected||$before[$user_table][0]!==1||$protected_credentials!==1||$before[$wpdb->prefix.'zmp_passkey_challenges'][0]!==0
    ||$before[$mentor_table][0]!==0||$before[$school_table][0]!==0||$before[$profile_table][0]!==0
    ||$before[$wpdb->prefix.'zmp_subject_proposals'][0]!==0||$before[$wpdb->prefix.'zmp_subject_proposal_events'][0]!==0){
    fwrite(STDOUT,"BLOCKED: expected isolated synthetic baseline absent; writes=0\n");exit(2);}
$old_post=$_POST;$original_user=(int)get_current_user_id();$user_id=0;$conflict_user_id=0;$conflict_mentor_id=0;
$rendered=false;$admin_denied=false;$saved=false;$mapping_ok=false;$engagement_ok=false;$other_user_denied=false;
$rollback_blocked=false;$rollback_clean=false;$rollback_response=0;
$collision_blocked=false;$collision_unchanged=false;$collision_response=0;$collision_guidance=false;
$cleanup_ok=false;$integrity_ok=false;$mentor_id=0;$die_filter=null;$rollback_filter=null;
try{
    $unique=bin2hex(random_bytes(10));$email='zmp66a-profile-setup-'.$unique.'@example.invalid';$login='zmp66a_setup_'.$unique;
    $created=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(48,true,true),'user_email'=>$email,
        'display_name'=>'Synthetic President self-profile setup','role'=>'zmp_president'));
    if(is_wp_error($created)||!is_int($created)||$created<1){throw new RuntimeException('synthetic-president-create');}
    $user_id=$created;$president=get_userdata($user_id);
    if(!$president||array_values((array)$president->roles)!==array('zmp_president')||!user_can($president,'view_zmp_president_dashboard')){
        throw new RuntimeException('exclusive-president-role-capability');}
    $school='Synthetic President Profile '.$unique;$municipality='Центар';$region=ZMP_Regions::region_for_municipality($municipality);
    if(!$region||!ZMP_Regions::valid_region($region)){throw new RuntimeException('synthetic-region');}
    // An administrator editing someone else's unmapped President must not create a profile.
    wp_set_current_user((int)$protected->ID);
    $_POST=array('zmp_teacher_profile_present'=>'1','_wpnonce'=>wp_create_nonce('update-user_'.$user_id),
        'zmp_school_name'=>$school,'zmp_municipality'=>$municipality,'zmp_phone'=>'');
    $admin_result=ZMP_Support::get_instance()->save_user_profile_fields($user_id);
    $admin_denied=$admin_result===false&&!ZMP_Mentor_School_Profile::mentor_for_user($user_id);
    // The account owner sees the standard teacher-profile form and uses its normal validation/save route.
    wp_set_current_user($user_id);ob_start();ZMP_Support::get_instance()->render_user_profile_fields($president);$markup=ob_get_clean();
    $rendered=strpos($markup,'name="zmp_teacher_profile_present"')!==false;
    $subjects=ZMP_Mentor_Subjects::approved_subjects();if(is_wp_error($subjects)){throw new RuntimeException('approved-catalog-unavailable');}
    // Force a post-insert validation failure. The first-time transaction must
    // roll back mentor, school/profile rows, and the four user-meta fields.
    $_POST=array('zmp_teacher_profile_present'=>'1','_wpnonce'=>wp_create_nonce('update-user_'.$user_id),
        'zmp_school_name'=>$school,'zmp_municipality'=>$municipality,'zmp_phone'=>'','zmp_profile_new_school_name'=>'Synthetic invalid partial setup');
    if($subjects){$subject=$subjects[0];$subject_id=is_object($subject)?absint($subject->id??0):absint($subject['id']??0);
        $_POST['zmp_teacher_assignments_present']='1';$_POST['assignments']=array('primary'=>array($subject_id=>array('grade_6')));}
    $rollback_filter=static function($handler)use(&$rollback_response){return static function($message='',$title='',$args=array())use(&$rollback_response){
        $rollback_response=is_array($args)?(int)($args['response']??0):0;throw new RuntimeException('expected-atomic-setup-block');};};
    add_filter('wp_die_handler',$rollback_filter,999);
    try{ZMP_Support::get_instance()->save_user_profile_fields($user_id);}catch(RuntimeException $caught){$rollback_blocked=$caught->getMessage()==='expected-atomic-setup-block';}
    remove_filter('wp_die_handler',$rollback_filter,999);
    wp_cache_delete($user_id,'user_meta');
    $rollback_clean=$rollback_blocked&&$rollback_response===400
        &&!ZMP_Mentor_School_Profile::mentor_for_user($user_id)
        &&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$school_table} WHERE mentor_id IN (SELECT id FROM {$mentor_table} WHERE wp_user_id=%d)",$user_id))===0
        &&(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$profile_table} WHERE mentor_id IN (SELECT id FROM {$mentor_table} WHERE wp_user_id=%d)",$user_id))===0
        &&get_user_meta($user_id,'zmp_school_name',true)===''
        &&get_user_meta($user_id,'zmp_municipality',true)===''
        &&get_user_meta($user_id,'zmp_region',true)===''
        &&get_user_meta($user_id,'zmp_phone',true)==='';
    $_POST=array('zmp_teacher_profile_present'=>'1','_wpnonce'=>wp_create_nonce('update-user_'.$user_id),
        'zmp_school_name'=>$school,'zmp_municipality'=>$municipality,'zmp_phone'=>'');
    if($subjects){$subject=$subjects[0];$subject_id=is_object($subject)?absint($subject->id??0):absint($subject['id']??0);
        if(!$subject_id){throw new RuntimeException('approved-subject-id');}
        $_POST['zmp_teacher_assignments_present']='1';$_POST['assignments']=array('primary'=>array($subject_id=>array('grade_6')));}
    $saved=ZMP_Support::get_instance()->save_user_profile_fields($user_id)===true;
    $mentor=ZMP_Mentor_School_Profile::mentor_for_user($user_id);$mentor_id=is_object($mentor)?(int)$mentor->id:0;
    $linked_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mentor_table} WHERE wp_user_id=%d",$user_id));
    $email_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mentor_table} WHERE LOWER(email)=LOWER(%s)",$email));
    $schools=$mentor_id?ZMP_Mentor_Schools::for_mentor($mentor_id,true):array();
    $mapping_ok=$saved&&$mentor_id>0&&$linked_count===1&&$email_count===1&&count((array)$schools)===1;
    $engagements=ZMP_Mentor_School_Profile::get_engagements($user_id);
    $engagement_ok=!is_wp_error($engagements)&&is_array($engagements)&&count($engagements)===1
        &&(int)$engagements[0]['wp_user_id']===$user_id&&(int)$engagements[0]['mentor_id']===$mentor_id;

    // Another President cannot use the owner-scoped profile service for this account.
    $conflict_email='zmp66a-profile-collision-'.$unique.'@example.invalid';$conflict_login='zmp66a_conflict_'.$unique;
    $other=wp_insert_user(array('user_login'=>$conflict_login,'user_pass'=>wp_generate_password(48,true,true),
        'user_email'=>$conflict_email,'display_name'=>'Synthetic President collision case','role'=>'zmp_president'));
    if(is_wp_error($other)||!is_int($other)||$other<1){throw new RuntimeException('collision-president-create');}$conflict_user_id=$other;
    $insert=$wpdb->insert($mentor_table,array('wp_user_id'=>0,'full_name'=>'Synthetic existing teacher record','email'=>$conflict_email,
        'phone'=>'','school_name'=>'Synthetic Existing School '.$unique,'municipality'=>$municipality,'region'=>$region,
        'login_token'=>wp_generate_password(32,false,false)));
    if($insert!==1){throw new RuntimeException('collision-row-create');}$conflict_mentor_id=(int)$wpdb->insert_id;
    wp_set_current_user($user_id);$other_profile=ZMP_Mentor_School_Profile::get_engagements($conflict_user_id);
    $other_user_denied=is_wp_error($other_profile)&&$other_profile->get_error_code()==='forbidden';

    // Verify an email collision is rejected and never auto-linked.
    wp_set_current_user($conflict_user_id);
    $collision_president=get_userdata($conflict_user_id);ob_start();ZMP_Support::get_instance()->render_user_profile_fields($collision_president);$collision_markup=ob_get_clean();
    $collision_form=strpos($collision_markup,'name="zmp_teacher_profile_present"')!==false;
    $_POST=array('zmp_teacher_profile_present'=>'1','_wpnonce'=>wp_create_nonce('update-user_'.$conflict_user_id),
        'zmp_school_name'=>'Synthetic Collision School '.$unique,'zmp_municipality'=>$municipality,'zmp_phone'=>'');
    if($subjects){$subject=$subjects[0];$subject_id=is_object($subject)?absint($subject->id??0):absint($subject['id']??0);
        $_POST['zmp_teacher_assignments_present']='1';$_POST['assignments']=array('primary'=>array($subject_id=>array('grade_6')));}
    $die_filter=static function($handler)use(&$collision_response,&$collision_guidance){return static function($message='',$title='',$args=array())use(&$collision_response,&$collision_guidance){
        $collision_response=is_array($args)?(int)($args['response']??0):0;
        $safe_message=wp_strip_all_tags((string)$message);
        $collision_guidance=strpos($safe_message,'автоматски')!==false&&strpos($safe_message,'администратор')!==false;
        throw new RuntimeException('expected-profile-setup-block');};};
    add_filter('wp_die_handler',$die_filter,999);
    try{ZMP_Support::get_instance()->save_user_profile_fields($conflict_user_id);}catch(RuntimeException $caught){$collision_blocked=$caught->getMessage()==='expected-profile-setup-block';}
    remove_filter('wp_die_handler',$die_filter,999);$die_filter=null;
    $collision_mapping=ZMP_Mentor_School_Profile::mentor_for_user($conflict_user_id);
    $remaining=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$mentor_table} WHERE LOWER(email)=LOWER(%s)",$conflict_email));
    $collision_unchanged=$collision_form&&$collision_blocked&&$collision_response===409&&$collision_guidance&&!$collision_mapping&&$remaining===1
        &&(int)$wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$mentor_table} WHERE id=%d",$conflict_mentor_id))===0;
}catch(Throwable $ignored){
    // Emit only outcome flags; never print user data or credentials.
}finally{
    if($rollback_filter!==null){remove_filter('wp_die_handler',$rollback_filter,999);}
    if($die_filter!==null){remove_filter('wp_die_handler',$die_filter,999);}
    $owned_ids=(array)$wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$mentor_table} WHERE wp_user_id IN (%d,%d) OR id=%d",
        max(0,$user_id),max(0,$conflict_user_id),max(0,$conflict_mentor_id)
    ));
    foreach($owned_ids as $owned_mentor_id){$owned_mentor_id=(int)$owned_mentor_id;
        $assignment_ids=(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$assignment_table} WHERE mentor_id=%d",$owned_mentor_id));
        if($assignment_ids){$in=implode(',',array_map('absint',$assignment_ids));$wpdb->query("DELETE FROM {$grade_table} WHERE assignment_id IN ({$in})");}
        $wpdb->delete($assignment_table,array('mentor_id'=>$owned_mentor_id),array('%d'));
        $wpdb->delete($profile_table,array('mentor_id'=>$owned_mentor_id),array('%d'));
        $wpdb->delete($school_table,array('mentor_id'=>$owned_mentor_id),array('%d'));
        $wpdb->delete($mentor_table,array('id'=>$owned_mentor_id),array('%d'));
    }
    foreach(array($user_id,$conflict_user_id) as $uid){if($uid>0&&get_userdata($uid)){wp_delete_user($uid);}}
    $_POST=$old_post;wp_set_current_user($original_user);
    wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');wp_cache_delete('alloptions','options');
    $current_debug=get_option('zmp_debug_events',$missing);if(serialize($current_debug)!==serialize($debug_before)){if($debug_before===$missing){delete_option('zmp_debug_events');}else{update_option('zmp_debug_events',$debug_before,false);}}
    $current_capture=get_option('zmp_debug_capture',$missing);if(serialize($current_capture)!==serialize($capture_before)){if($capture_before===$missing){delete_option('zmp_debug_capture');}else{update_option('zmp_debug_capture',$capture_before,false);}}
    wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');wp_cache_delete('alloptions','options');$after=$snapshot();$cleanup_ok=$after===$before;
}
$integrity_proc=@proc_open(array('/usr/local/bin/wp','--path='.$site,'db','check'),array(0=>array('file','/dev/null','r'),1=>array('file','/dev/null','w'),2=>array('file','/dev/null','w')),$pipes,$site,null,array('bypass_shell'=>true));
if(is_resource($integrity_proc)){$deadline=microtime(true)+25.0;$status=null;do{$status=@proc_get_status($integrity_proc);if(is_array($status)&&empty($status['running'])){break;}usleep(1000);}while(microtime(true)<$deadline);
    if(is_array($status)&&!empty($status['running'])){@proc_terminate($integrity_proc);}$closed=@proc_close($integrity_proc);$integrity_ok=is_array($status)&&empty($status['running'])&&(($status['exitcode']??-1)===0||$closed===0);}
$out=array('ADMIN_OTHER_USER_SETUP'=>($admin_denied?'DENIED':'FAIL'),'PRESIDENT_FORM'=>($rendered?'PASS':'FAIL'),
    'SELF_PROFILE_SETUP'=>($saved?'PASS':'FAIL'),'SELF_MAPPING_AND_SCHOOL'=>($mapping_ok?'PASS':'FAIL'),
    'SELF_ENGAGEMENT_READ'=>($engagement_ok?'PASS':'FAIL'),'OTHER_PRESIDENT_DENIED'=>($other_user_denied?'PASS':'FAIL'),
    'EMAIL_COLLISION_NO_AUTOLINK'=>($collision_unchanged?'PASS':'FAIL'),'FIRST_SETUP_ROLLBACK'=>($rollback_clean?'PASS':'FAIL'),
    'CLEANUP'=>($cleanup_ok?'PASS':'FAIL'),'DB_INTEGRITY'=>($integrity_ok?'PASS':'FAIL'));
foreach($out as $key=>$value){fwrite(STDOUT,$key.'='.$value.'; ');}
fwrite(STDOUT,"\n");$all=($admin_denied&&$rendered&&$saved&&$mapping_ok&&$engagement_ok&&$other_user_denied&&$collision_unchanged&&$rollback_clean&&$cleanup_ok&&$integrity_ok);
exit($all?0:1);
