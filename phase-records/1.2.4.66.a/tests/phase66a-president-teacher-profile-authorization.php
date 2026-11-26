<?php
/**
 * Clone-only President teacher-profile authorization check with fixture-owned mentor mapping
 * and active school engagement. Exercises real self read/write and other-user denial, then
 * removes only the synthetic user's profile, school, mentor row and account. No HTTP/email.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI){fwrite(STDOUT,"BLOCKED: WP-CLI only; writes=0\n");exit(2);}
$site='/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site';$origin='http://127.0.0.1:8102';
$db='zmp66a_isolated';$db_host='localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock';$prefix='z66a_';
$fixture='/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-authorization.php';
$plugin='zmp-competitions/zmp-competitions.php';$plugin_dir=WP_PLUGIN_DIR.'/zmp-competitions';
$hashes=array(
    'zmp-competitions.php'=>'c0f6cd135f7fbefb09847fa047f76828736c30c0fa802dad3f06fdd9c3a4989f',
    'includes/class-zmp-mentor-school-profile.php'=>'8379706ab7c13553decb45ce43ca35772c5735cb5d442459d15a54268ae4fc3b',
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
    ||!class_exists('ZMP_Mentor_School_Profile')||!class_exists('ZMP_Mentor_Schools')||!class_exists('ZMP_Regions')||!class_exists('ZMP_President_Mandate')
    ||!ZMP_Mentor_School_Profile::schema_is_compatible()||!ZMP_Mentor_Schools::schema_is_compatible()||!get_role('zmp_president')){
    fwrite(STDOUT,"BLOCKED: plugin/schema/role preflight; writes=0\n");exit(2);}
$user_table=$wpdb->users;$usermeta_table=$wpdb->usermeta;$mentor_table=$wpdb->prefix.'zmp_mentors';
$school_table=$wpdb->prefix.'zmp_mentor_schools';$profile_table=ZMP_Mentor_School_Profile::table_name();
$tables=array($user_table,$usermeta_table,$mentor_table,$school_table,$profile_table,$wpdb->prefix.'zmp_passkey_credentials',
    $wpdb->prefix.'zmp_passkey_challenges',$wpdb->prefix.'zmp_subject_proposals',$wpdb->prefix.'zmp_subject_proposal_events',
    ZMP_President_Mandate::table_name(),ZMP_President_Mandate::audit_table_name());
$count=static function($table)use($wpdb){return(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");};
$table_fp=static function($table)use($wpdb){$rows=$wpdb->get_results("SELECT * FROM `{$table}` ORDER BY 1",ARRAY_A);return hash('sha256',serialize($rows?:array()));};
$missing=new stdClass();$role_option=$wpdb->get_blog_prefix().'user_roles';wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');
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
$original_user=(int)get_current_user_id();$user_id=0;$mentor_id=0;$school_id=0;$setup_ok=false;$mapping_ok=false;$school_ok=false;
$self_teacher=false;$self_read=false;$self_read_code='other';$self_write=false;$self_write_code='other';$other_read_denied=false;$other_write_denied=false;$cleanup_ok=false;$integrity_ok=false;
$error_code=static function($value){if(!is_wp_error($value)){return '';} $code=(string)$value->get_error_code();return in_array($code,array('forbidden','teacher_profile_required','school_not_owned','schema_not_ready'),true)?$code:'other';};
try{
    $unique=bin2hex(random_bytes(10));$login='zmp66a_profdiag_'.$unique;$email='zmp66a-profdiag-'.$unique.'@example.invalid';
    $created=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(48,true,true),'user_email'=>$email,
        'display_name'=>'Synthetic President teacher-profile check','role'=>'zmp_president'));
    if(is_wp_error($created)||!is_int($created)||$created<1){throw new RuntimeException('synthetic-user-create');}$user_id=$created;
    $user=get_userdata($user_id);if(!$user||array_values((array)$user->roles)!==array('zmp_president')){throw new RuntimeException('exclusive-President-role');}
    $school_name='Synthetic profile school '.$unique;$municipality='Центар';$region=ZMP_Regions::region_for_municipality($municipality);
    if(!$region||!ZMP_Regions::valid_region($region)){throw new RuntimeException('synthetic-region');}
    $login_token=bin2hex(random_bytes(32));$inserted=$wpdb->insert($mentor_table,array('wp_user_id'=>$user_id,'full_name'=>'Synthetic President teacher',
        'email'=>$email,'phone'=>'','school_name'=>$school_name,'municipality'=>$municipality,'region'=>$region,'login_token'=>$login_token));unset($login_token);
    if($inserted!==1){throw new RuntimeException('synthetic-mentor-insert');}$mentor_id=(int)$wpdb->insert_id;
    $mentor=ZMP_Mentor_School_Profile::mentor_for_user($user_id);$mapping_ok=is_object($mentor)&&(int)$mentor->id===$mentor_id;
    $added=ZMP_Mentor_Schools::add_school($mentor_id,$school_name,$municipality,true);
    if(is_wp_error($added)||!is_numeric($added)||(int)$added<1){throw new RuntimeException('synthetic-school-add');}$school_id=(int)$added;
    $school=ZMP_Mentor_Schools::owned_school($mentor_id,$school_id);$school_ok=is_object($school)&&(int)$school->is_active===1;
    if(!$mapping_ok||!$school_ok){throw new RuntimeException('synthetic-profile-mapping');}
    $setup_ok=true;$payload=ZMP_Mentor_School_Profile::profile_defaults();wp_set_current_user($user_id);
    $self_teacher=(bool)ZMP_Mentor_School_Profile::is_teacher($user_id);$read_result=ZMP_Mentor_School_Profile::get_profile($user_id,$school_id);
    $self_read=!is_wp_error($read_result)&&is_array($read_result);$self_read_code=$error_code($read_result)?:($self_read?'profile':'other');
    $write_result=ZMP_Mentor_School_Profile::save_profile($user_id,$school_id,$payload);$self_write=$write_result===true;
    $self_write_code=$error_code($write_result)?:($self_write?'saved':'other');
    wp_set_current_user((int)$protected->ID);$other_read=ZMP_Mentor_School_Profile::get_profile($user_id,$school_id);
    $other_read_denied=is_wp_error($other_read)&&$error_code($other_read)==='forbidden';$other_write=ZMP_Mentor_School_Profile::save_profile($user_id,$school_id,$payload);
    $other_write_denied=is_wp_error($other_write)&&$error_code($other_write)==='forbidden';
}catch(Throwable $ignored){$setup_ok=false;}
finally{
    if($user_id>0){$mentor_ids=(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$mentor_table} WHERE wp_user_id=%d",$user_id));
        foreach($mentor_ids as $owned_mentor_id){$owned_mentor_id=(int)$owned_mentor_id;if($owned_mentor_id>0){
            $wpdb->delete($profile_table,array('mentor_id'=>$owned_mentor_id),array('%d'));$wpdb->delete($school_table,array('mentor_id'=>$owned_mentor_id),array('%d'));}}
        $wpdb->delete($mentor_table,array('wp_user_id'=>$user_id),array('%d'));if(get_userdata($user_id)){wp_delete_user($user_id);}}
    wp_set_current_user($original_user);wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');wp_cache_delete('alloptions','options');
    $current_debug=get_option('zmp_debug_events',$missing);if(serialize($current_debug)!==serialize($debug_before)){if($debug_before===$missing){delete_option('zmp_debug_events');}else{update_option('zmp_debug_events',$debug_before,false);}}
    $current_capture=get_option('zmp_debug_capture',$missing);if(serialize($current_capture)!==serialize($capture_before)){if($capture_before===$missing){delete_option('zmp_debug_capture');}else{update_option('zmp_debug_capture',$capture_before,false);}}
    wp_cache_delete('zmp_debug_events','options');wp_cache_delete('zmp_debug_capture','options');wp_cache_delete('alloptions','options');$after=$snapshot();
    $cleanup_ok=$after===$before&&(int)get_current_user_id()===$original_user;
}
$integrity_proc=@proc_open(array('/usr/local/bin/wp','--path='.$site,'db','check'),array(0=>array('file','/dev/null','r'),1=>array('file','/dev/null','w'),2=>array('file','/dev/null','w')),$pipes,$site,null,array('bypass_shell'=>true));
if(is_resource($integrity_proc)){$deadline=microtime(true)+25.0;$status=null;do{$status=@proc_get_status($integrity_proc);if(is_array($status)&&empty($status['running'])){break;}usleep(1000);}while(microtime(true)<$deadline);
    if(is_array($status)&&!empty($status['running'])){@proc_terminate($integrity_proc);}$closed=@proc_close($integrity_proc);$integrity_ok=is_array($status)&&empty($status['running'])&&(($status['exitcode']??-1)===0||$closed===0);}
$auth_pass=$self_teacher&&$self_read&&$self_write;$self_read_label=$self_read?'PASS':$self_read_code;$self_write_label=$self_write?'PASS':$self_write_code;
fwrite(STDOUT,'SETUP='.($setup_ok?'PASS':'FAIL').'; MENTOR_MAPPING='.($mapping_ok?'PASS':'FAIL').'; ACTIVE_SCHOOL='.($school_ok?'PASS':'FAIL').'; PRESIDENT_TEACHER_AUTH='.($self_teacher?'PASS':'FAIL').'; SELF_READ='.$self_read_label.'; SELF_WRITE='.$self_write_label.'; OTHER_READ='.($other_read_denied?'DENIED':'FAIL').'; OTHER_WRITE='.($other_write_denied?'DENIED':'FAIL').'; CLEANUP='.($cleanup_ok?'PASS':'FAIL').'; INTEGRITY='.($integrity_ok?'PASS':'FAIL')."\n");
exit($setup_ok&&$mapping_ok&&$school_ok&&$auth_pass&&$other_read_denied&&$other_write_denied&&$cleanup_ok&&$integrity_ok?0:1);