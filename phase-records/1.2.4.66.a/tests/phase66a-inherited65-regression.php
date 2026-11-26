<?php
/** Isolated regression test for .65 fixes inherited by .66.a. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only.\n"); exit(2); }
global $wpdb;
$expected_root = '/home/tonic/zmp65-review-20261124/site-66a-subject-test';
$plugin_dir = WP_PLUGIN_DIR . '/zmp-competitions';
$site_url = rtrim(home_url('/'), '/');
if (!defined('DB_NAME') || DB_NAME !== 'zmp65_subject_test' || $wpdb->prefix !== 'z66a_'
    || rtrim((string) realpath(ABSPATH), '/') !== $expected_root || $site_url !== 'http://127.0.0.1:8100'
    || !is_file($plugin_dir . '/zmp-competitions.php')) {
    fwrite(STDERR, "BLOCKED: unexpected site/database identity; writes=0\n"); exit(2);
}
$checks = array();
$assert = static function($key, $ok) use (&$checks) { $checks[$key] = $ok ? 'PASS' : 'FAIL'; };
$types = array(
    'bigint(20) unsigned' => 'bigint unsigned', 'BIGINT UNSIGNED' => 'bigint unsigned',
    'int(10) unsigned' => 'int unsigned', 'tinyint(1)' => 'tinyint', 'varchar(254)' => 'varchar(254)',
);
$types_ok = true;
foreach ($types as $input => $expected) { if (ZMP_Database::normalize_schema_type($input) !== $expected) { $types_ok = false; } }
$assert('integer-display-width-normalization', $types_ok);
$assert('mentor-school-schema-ready', ZMP_Mentor_Schools::schema_is_compatible());
$assert('teacher-profile-schema-ready', ZMP_Mentor_School_Profile::schema_is_compatible());
$assert('school-email-schema-ready', ZMP_School_Email_Access::schema_is_compatible());

$original_user = get_current_user_id();
$user_id = 0;
$president_id = 0;
$mentor_table=$wpdb->prefix.'zmp_mentors';$school_table=$wpdb->prefix.'zmp_mentor_schools';$profile_table=ZMP_Mentor_School_Profile::table_name();
$baseline=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
    'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$mentor_table}`"),
    'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$school_table}`"),
    'profiles'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$profile_table}`"));
$mentor_id=0;$school_id=0;
try {
    $suffix = bin2hex(random_bytes(8));
    $created = wp_insert_user(array(
        'user_login' => 'zmp66a_inherited65_' . $suffix,
        'user_pass' => wp_generate_password(40, true, true),
        'user_email' => 'zmp66a-inherited65-' . $suffix . '@example.invalid',
        'role' => 'zmp_teacher',
    ));
    if (is_wp_error($created) || (int) $created < 1) { throw new RuntimeException('synthetic-user'); }
    $user_id = (int) $created;
    $user = get_user_by('id', $user_id);
    foreach (array('subscriber', 'administrator', 'zmp_moderator', 'zmp_commission', 'zmp_testator', 'zmp_organizer') as $role) { $user->add_role($role); }
    wp_set_current_user($user_id);
    $roles = (array) get_userdata($user_id)->roles;
    $assert('subscriber-teacher-admin-and-additive-roles',
        in_array('subscriber', $roles, true) && in_array('zmp_teacher', $roles, true)
        && ZMP_Mentor_School_Profile::is_teacher($user_id)
        && ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id));
    $municipality='Скопје';$region=ZMP_Regions::region_for_municipality($municipality);
    $school_name='Synthetic inherited .65 school '.$suffix;
    $mentor_insert=$wpdb->insert($mentor_table,array('wp_user_id'=>$user_id,'full_name'=>'Synthetic Teacher Stack','email'=>'profile-'.$suffix.'@example.test',
        'phone'=>'','school_name'=>$school_name,'municipality'=>$municipality,'region'=>$region,'login_token'=>wp_generate_password(32,false)));
    if($mentor_insert!==1){throw new RuntimeException('synthetic-mentor');}$mentor_id=(int)$wpdb->insert_id;
    $school_insert=$wpdb->insert($school_table,array('mentor_id'=>$mentor_id,'school_name'=>$school_name,'municipality'=>$municipality,'region'=>$region,
        'region_is_override'=>0,'school_key'=>ZMP_Mentor_Schools::school_key($school_name,$municipality),'is_primary'=>1,'is_active'=>1,
        'engagement_epoch'=>1,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')));
    if($school_insert!==1){throw new RuntimeException('synthetic-school');}$school_id=(int)$wpdb->insert_id;
    $profile_payload=ZMP_Mentor_School_Profile::profile_defaults();
    $profile_payload['official_email']='profile-'.$suffix.'@example.test';$profile_payload['employment_term']='indefinite';$profile_payload['workload']='full';
    $profile_saved=ZMP_Mentor_School_Profile::save_profile($user_id,$school_id,$profile_payload);
    $profile_read=ZMP_Mentor_School_Profile::get_profile($user_id,$school_id);
    $assert('admin-teacher-subscriber-profile-save-readback', $profile_saved===true && is_array($profile_read)
        && $profile_read['official_email']===$profile_payload['official_email']
        && $profile_read['employment_term']==='indefinite' && $profile_read['workload']==='full');
    $user->remove_role('subscriber');
    $assert('subscriber-remains-required', !ZMP_Mentor_School_Profile::is_teacher($user_id)
        && !ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id));
    $user->add_role('subscriber');
    $user->remove_role('zmp_teacher');
    $assert('teacher-role-remains-required', !ZMP_Mentor_School_Profile::is_teacher($user_id)
        && !ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id));

    $president = wp_insert_user(array('user_login'=>'zmp66a_president_stack_'.$suffix,
        'user_pass'=>wp_generate_password(40,true,true), 'user_email'=>'zmp66a-president-stack-'.$suffix.'@example.invalid', 'role'=>'zmp_president'));
    if (is_wp_error($president) || (int)$president < 1) { throw new RuntimeException('synthetic-president'); }
    $president_id = (int)$president;
    $president_user = get_user_by('id',$president_id);
    foreach (array('subscriber','zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer') as $role) { $president_user->add_role($role); }
    wp_set_current_user($president_id);
    $assert('president-must-be-subscriber-and-teacher', ZMP_Mentor_School_Profile::is_teacher($president_id)
        && ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($president_id)
        && ZMP_Mentor_School_Profile::has_role_stack($president_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer')));
    $assignment_result = ZMP_Mentor_Subjects::save_teacher_assignments($president_id,array());
    $assert('stacked-president-subject-service-reaches-profile-required', is_wp_error($assignment_result)
        && $assignment_result->get_error_code()==='teacher_profile_required');
    $president_user->remove_role('zmp_teacher');
    $assignment_denied = ZMP_Mentor_Subjects::save_teacher_assignments($president_id,array());
    $assert('president-without-teacher-is-denied-teacher-service', is_wp_error($assignment_denied)
        && $assignment_denied->get_error_code()==='forbidden');
} catch (Throwable $e) {
    fwrite(STDERR, 'TEST EXCEPTION: ' . sanitize_key($e->getMessage()) . "\n");
    $checks['synthetic-role-stack-regression'] = 'FAIL';
} finally {
    wp_set_current_user($original_user);
    if($school_id>0){$wpdb->delete($profile_table,array('mentor_id'=>$mentor_id,'school_id'=>$school_id),array('%d','%d'));
        $wpdb->delete($school_table,array('id'=>$school_id,'mentor_id'=>$mentor_id),array('%d','%d'));}
    if($mentor_id>0){$wpdb->delete($mentor_table,array('id'=>$mentor_id,'wp_user_id'=>$user_id),array('%d','%d'));}
    if ($user_id > 0 && get_userdata($user_id)) { wp_delete_user($user_id); }
    if ($president_id > 0 && get_userdata($president_id)) { wp_delete_user($president_id); }
}
$after=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
    'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$mentor_table}`"),
    'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$school_table}`"),
    'profiles'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$profile_table}`"));
$assert('synthetic-profile-and-user-cleanup', $after===$baseline && $wpdb->last_error==='');
foreach ($checks as $name => $status) { echo $status . ' ' . $name . "\n"; }
$passed = count(array_filter($checks, static function($status) { return $status === 'PASS'; }));
echo 'SUMMARY ' . $passed . '/' . count($checks) . " PASS\n";
exit($passed === count($checks) ? 0 : 1);
