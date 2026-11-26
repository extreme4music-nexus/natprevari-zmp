<?php
/**
 * OWNER RULE: EVERY MEMBER MUST HAVE subscriber; zmp_teacher is additive, and the President must have both.
 * Focused regression for editing the primary school on an existing teacher/PRESIDENT profile.
 */
global $wpdb;
$site = wp_parse_url(home_url('/'));
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp_debug_prodclone_20261125'
    || $wpdb->prefix !== 'wp_yqqdt5_' || !is_array($site)
    || ($site['scheme'] ?? '') !== 'http' || !in_array(($site['host'] ?? ''), array('localhost','127.0.0.1'), true)
    || (int)($site['port'] ?? 0) !== 8111) {
    fwrite(STDERR, "BLOCKED: unexpected local clone/database.\n"); exit(2);
}
foreach (array('ZMP_Mentor_Schools','ZMP_Mentor_Subjects','ZMP_School_Profile_UI','ZMP_Support') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required plugin service unavailable.\n"); exit(2); }
}
$actor = get_user_by('login','codex66a_admin_2511');
if (!$actor || !user_can($actor,'manage_options') || !ZMP_Mentor_Schools::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: local administrator or school schema preflight failed.\n"); exit(2);
}
$catalog = ZMP_Mentor_Subjects::approved_subjects();
if (is_wp_error($catalog) || !$catalog) { fwrite(STDERR, "BLOCKED: no approved subject for test.\n"); exit(2); }
$subject_id = (int)$catalog[0]->id;
$teacher_table = $wpdb->prefix.'zmp_mentors';
$school_table = $wpdb->prefix.'zmp_mentor_schools';
$assignment_table = $wpdb->prefix.'zmp_mentor_school_subjects';
$grade_table = $wpdb->prefix.'zmp_mentor_school_subject_grades';
$before = array(
 'users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
 'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$teacher_table}"),
 'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$school_table}"),
 'assignments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$assignment_table}"),
 'grades'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$grade_table}"),
);
$token = strtolower(wp_generate_password(16,false,false));
$login = 'zmp_school_edit_'.$token;
$email = 'school-edit-'.$token.'@example.test';
$school1 = 'Synthetic Editable Primary '.$token;
$municipality1 = json_decode('"\u0421\u043a\u043e\u043f\u0458\u0435"');
$school2 = 'Synthetic Corrected Primary '.$token;
$municipality2 = json_decode('"\u041e\u0445\u0440\u0438\u0434"');
$checks = array(); $uid=0; $mid=0; $ok_cleanup=true; $original= get_current_user_id();
$assert=static function($key,$value) use (&$checks) { $checks[$key]=$value?'PASS':'FAIL'; };
try {
    $uid=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(28,true,true),'user_email'=>$email,'display_name'=>'Synthetic School Edit President','role'=>'subscriber'));
    if (is_wp_error($uid) || (int)$uid<1) { throw new RuntimeException('synthetic user create failed'); }
    $uid=(int)$uid;
    $target=get_user_by('id',$uid);
    foreach (array('zmp_teacher','zmp_president','administrator') as $role) {
        if (!get_role($role)) { throw new RuntimeException('expected test role is not registered'); }
        $target->add_role($role);
    }
    $target=get_user_by('id',$uid);
    $assert('subscriber_teacher_president_admin_roles_can_coexist',
        in_array('subscriber',(array)$target->roles,true) && in_array('zmp_teacher',(array)$target->roles,true)
        && in_array('zmp_president',(array)$target->roles,true) && in_array('administrator',(array)$target->roles,true));
    $inserted=$wpdb->insert($teacher_table,array('wp_user_id'=>$uid,'full_name'=>'Synthetic School Edit President','email'=>$email,'phone'=>'','school_name'=>'','municipality'=>'','region'=>null,'login_token'=>wp_generate_password(32,false)));
    if ($inserted!==1) { throw new RuntimeException('empty synthetic mentor row creation failed'); }
    $mid=(int)$wpdb->insert_id;
    wp_set_current_user((int)$actor->ID);
    ob_start(); ZMP_Support::get_instance()->render_user_profile_fields($target); $html=(string)ob_get_clean();
    $assert('existing_profile_school_and_municipality_are_editable',
        strpos($html,'name="zmp_school_name"')!==false && strpos($html,'name="zmp_municipality"')!==false
        && preg_match('/name="zmp_school_name"[^>]*readonly/',$html)!==1
        && preg_match('/name="zmp_municipality"[^>]*readonly/',$html)!==1
        && strpos($html,'assignments[primary]['.$subject_id.'][]')!==false);
    $post=array(
        '_wpnonce'=>wp_create_nonce('update-user_'.$uid),
        'zmp_teacher_profile_present'=>'1',
        'zmp_teacher_assignments_present'=>'1',
        'zmp_school_name'=>$school1,
        'zmp_municipality'=>$municipality1,
        'zmp_phone'=>'',
        'assignments'=>array('primary'=>array($subject_id=>array('grade_6'))),
    );
    $_POST=$post;
    $first=ZMP_Support::get_instance()->save_user_profile_fields($uid);
    $primary1=$wpdb->get_row($wpdb->prepare("SELECT id,school_name,municipality,is_primary,is_active FROM {$school_table} WHERE mentor_id=%d AND is_primary=1 AND is_active=1",$mid));
    $assigned1=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$assignment_table} WHERE mentor_id=%d AND school_id=%d AND subject_id=%d",$mid,(int)($primary1->id??0),$subject_id));
    $grades1=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$grade_table} g JOIN {$assignment_table} a ON a.id=g.assignment_id WHERE a.mentor_id=%d AND a.school_id=%d AND g.grade_label=%s",$mid,(int)($primary1->id??0),'grade_6'));
    $assert('submit_creates_primary_school_and_assignment', $first===true && $primary1 && $primary1->school_name===$school1 && $primary1->municipality===$municipality1 && $assigned1===1 && $grades1===1);
    $school_id=(int)($primary1->id??0);
    $post['_wpnonce']=wp_create_nonce('update-user_'.$uid);
    $post['zmp_school_name']=$school2;
    $post['zmp_municipality']=$municipality2;
    $post['assignments']=array((string)$school_id=>array($subject_id=>array('grade_7')));
    $_POST=$post;
    $second=ZMP_Support::get_instance()->save_user_profile_fields($uid);
    $primary2=$wpdb->get_row($wpdb->prepare("SELECT id,school_name,municipality,is_primary,is_active FROM {$school_table} WHERE mentor_id=%d AND is_primary=1 AND is_active=1",$mid));
    $mentor=$wpdb->get_row($wpdb->prepare("SELECT school_name,municipality,region FROM {$teacher_table} WHERE id=%d AND wp_user_id=%d",$mid,$uid));
    $assigned2=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$assignment_table} WHERE mentor_id=%d AND school_id=%d AND subject_id=%d",$mid,$school_id,$subject_id));
    $grades2=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$grade_table} g JOIN {$assignment_table} a ON a.id=g.assignment_id WHERE a.mentor_id=%d AND a.school_id=%d AND g.grade_label=%s",$mid,$school_id,'grade_7'));
    $assert('rename_updates_same_primary_engagement_and_preserves_assignment_link',
        $second===true && $primary2 && (int)$primary2->id===$school_id && $primary2->school_name===$school2 && $primary2->municipality===$municipality2
        && $mentor && $mentor->school_name===$school2 && $mentor->municipality===$municipality2 && $assigned2===1 && $grades2===1);
} catch (Throwable $e) {
    fwrite(STDERR,'TEST_EXCEPTION '.sanitize_key($e->getMessage())."\n");
    $checks['runtime_completed']='FAIL';
} finally {
    $_POST=array();
    wp_set_current_user($original);
    if ($mid) {
        $wpdb->query($wpdb->prepare("DELETE g FROM {$grade_table} g JOIN {$assignment_table} a ON a.id=g.assignment_id WHERE a.mentor_id=%d",$mid));
        $wpdb->delete($assignment_table,array('mentor_id'=>$mid),array('%d'));
        $wpdb->delete($school_table,array('mentor_id'=>$mid),array('%d'));
        $wpdb->delete($teacher_table,array('id'=>$mid,'wp_user_id'=>$uid),array('%d'));
    }
    if ($uid && !wp_delete_user($uid)) { $ok_cleanup=false; }
    $after=array(
     'users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
     'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$teacher_table}"),
     'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$school_table}"),
     'assignments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$assignment_table}"),
     'grades'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$grade_table}"),
    );
    if ($wpdb->last_error!=='' || $before!==$after) { $ok_cleanup=false; }
}
$checks['synthetic_rows_removed_and_baselines_restored']=$ok_cleanup?'PASS':'FAIL';
foreach ($checks as $k=>$v) { echo $v.' '.$k."\n"; }
$passed=count(array_filter($checks,static function($v){return $v==='PASS';}));
echo 'RESULT '.$passed.'/'.count($checks)."\n";
if ($passed!==count($checks)) { exit(1); }
