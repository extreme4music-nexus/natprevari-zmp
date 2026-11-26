<?php
/** Focused local profile rendering check for a teacher with the complete authorized member-role stack. */
global $wpdb;
$allowed_sites = array('z65s_'=>8097,'z66_'=>8099,'z66a_'=>8100,'z66s_'=>8098);
$site = wp_parse_url(home_url('/'));
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME !== 'zmp65_subject_test'
    || !isset($allowed_sites[$wpdb->prefix]) || !is_array($site)
    || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int)($site['port'] ?? 0) !== $allowed_sites[$wpdb->prefix]) {
    fwrite(STDERR, "BLOCKED: unexpected synthetic site/database or non-loopback URL.\n"); exit(2);
}
foreach (array('ZMP_Mentor_School_Profile','ZMP_Mentor_Schools','ZMP_School_Profile_UI','ZMP_Regions') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required ZMP service is unavailable.\n"); exit(2); }
}
if (!current_user_can('manage_options') || !ZMP_Mentor_School_Profile::schema_is_compatible() || !ZMP_Mentor_Schools::schema_is_compatible()) {
    fwrite(STDERR, "BLOCKED: schema preflight or synthetic administrator check failed.\n"); exit(2);
}
$token = strtolower(wp_generate_password(18, false, false));
$login = 'zmp65_profile_stack_' . $token;
$email = 'profile-' . $token . '@example.test';
$school_name = 'Synthetic Profile School ' . $token;
$municipality = 'Скопје';
$school_key = ZMP_Mentor_Schools::school_key($school_name, $municipality);
$teacher_table = $wpdb->prefix . 'zmp_mentors';
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$profile_table = $wpdb->prefix . 'zmp_mentor_school_profiles';
$before = array(
    'users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
    'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$teacher_table}"),
    'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$school_table}"),
    'profiles'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$profile_table}"),
);
if ($wpdb->last_error !== '' || (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$school_table} WHERE school_key=%s", $school_key)) !== 0) {
    fwrite(STDERR, "BLOCKED: baseline or unique synthetic school key is not clean.\n"); exit(2);
}
$original_user = get_current_user_id();
$user_id = 0; $mentor_id = 0; $school_id = 0; $control_user_ids = array(); $checks = array(); $cleanup_ok = true;
$assert = static function($name,$ok) use (&$checks) { $checks[$name] = $ok ? 'PASS' : 'FAIL'; };
try {
    $user_id = wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(28,true,true),'user_email'=>$email,'display_name'=>'Synthetic Profile Stack Teacher','role'=>'zmp_teacher'));
    if (is_wp_error($user_id) || (int)$user_id < 1) { throw new RuntimeException('synthetic user creation failed'); }
    $user_id = (int)$user_id;
    $teacher = get_user_by('id',$user_id);
    foreach (array('subscriber','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer') as $role) { $teacher->add_role($role); }
    $teacher = get_user_by('id',$user_id);
    $assert('teacher_subscriber_and_all_member_roles_preserved',
        $teacher && in_array('zmp_teacher',(array)$teacher->roles,true) && in_array('subscriber',(array)$teacher->roles,true)
        && in_array('zmp_moderator',(array)$teacher->roles,true) && in_array('zmp_commission',(array)$teacher->roles,true)
        && in_array('zmp_testator',(array)$teacher->roles,true) && in_array('zmp_organizer',(array)$teacher->roles,true));
    foreach (array('zmp_testator'=>'testator','zmp_organizer'=>'organizer') as $role=>$label) {
        $control_id = wp_insert_user(array('user_login'=>'zmp65_profile_control_' . $label . '_' . $token,'user_pass'=>wp_generate_password(28,true,true),'user_email'=>$label . '-' . $token . '@example.test','display_name'=>'Synthetic ' . $label . ' Control','role'=>$role));
        if (is_wp_error($control_id) || (int)$control_id < 1) { throw new RuntimeException('negative-control user creation failed'); }
        $control_id = (int)$control_id; $control_user_ids[] = $control_id;
        $control_user = get_user_by('id',$control_id); $control_user->add_role('subscriber');
        wp_set_current_user($control_id);
        $check_name = $label . '_subscriber_without_teacher_is_rejected';
        $assert($check_name, !ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($control_id));
    }
    wp_set_current_user($user_id);

    $mentor_ok = $wpdb->insert($teacher_table,array('wp_user_id'=>$user_id,'full_name'=>'Synthetic Profile Stack Teacher','email'=>$email,'phone'=>'','school_name'=>$school_name,'municipality'=>$municipality,'region'=>'Скопски','login_token'=>wp_generate_password(32,false)));
    if ($mentor_ok !== 1) { throw new RuntimeException('synthetic mentor row creation failed'); }
    $mentor_id = (int)$wpdb->insert_id;
    $region = ZMP_Regions::region_for_municipality($municipality);
    $school_ok = $wpdb->insert($school_table,array('mentor_id'=>$mentor_id,'school_name'=>$school_name,'municipality'=>$municipality,'region'=>$region,'region_is_override'=>0,'school_key'=>$school_key,'is_primary'=>1,'is_active'=>1,'engagement_epoch'=>1,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')));
    if ($school_ok !== 1) { throw new RuntimeException('synthetic school engagement creation failed'); }
    $school_id = (int)$wpdb->insert_id;
    wp_set_current_user($user_id);
    $assert('own_profile_helper_accepts_complete_teacher_subscriber_role_stack', ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id));
    wp_set_current_user($original_user);
    $assert('own_profile_helper_rejects_different_current_user', !ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id));
    wp_set_current_user($user_id);
    $engagements = ZMP_Mentor_School_Profile::get_engagements($user_id);
    $assert('engagement_service_returns_owned_school_and_profile', !is_wp_error($engagements) && is_array($engagements) && count($engagements)===1 && (int)($engagements[0]['school_id']??0)===$school_id && is_array($engagements[0]['profile']??null));
    ob_start(); ZMP_School_Profile_UI::render(get_userdata($user_id)); $html = (string)ob_get_clean();
    $assert('school_profile_form_renders_without_unavailable_error', strpos($html,'notice-error')===false && strpos($html,'zmp-school-profile__school')!==false && strpos($html,esc_html($school_name))!==false);
    $assert('profile_fields_rendered_for_teacher', strpos($html,'official_email')!==false && strpos($html,'employment_term')!==false && strpos($html,'workload')!==false);
} catch (Throwable $e) {
    fwrite(STDERR, "SETUP_OR_ASSERTION_EXCEPTION " . sanitize_key($e->getMessage()) . "\n");
    $checks['runtime_completed'] = 'FAIL';
} finally {
    wp_set_current_user($original_user);
    if ($school_id > 0) {
        $wpdb->delete($profile_table,array('mentor_id'=>$mentor_id,'school_id'=>$school_id),array('%d','%d'));
        if ($wpdb->delete($school_table,array('id'=>$school_id,'mentor_id'=>$mentor_id),array('%d','%d')) !== 1) { $cleanup_ok=false; }
    }
    if ($mentor_id > 0 && $wpdb->delete($teacher_table,array('id'=>$mentor_id,'wp_user_id'=>$user_id),array('%d','%d')) !== 1) { $cleanup_ok=false; }
    foreach ($control_user_ids as $control_user_id) { if (!wp_delete_user((int)$control_user_id)) { $cleanup_ok=false; } }
    if ($user_id > 0 && !wp_delete_user($user_id)) { $cleanup_ok=false; }
    $after = array(
        'users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
        'mentors'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$teacher_table}"),
        'schools'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$school_table}"),
        'profiles'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$profile_table}"),
    );
    if ($wpdb->last_error !== '' || $before !== $after) { $cleanup_ok=false; }
}
$checks['exact_synthetic_cleanup_and_table_baselines_restored'] = $cleanup_ok ? 'PASS' : 'FAIL';
foreach ($checks as $name=>$status) { echo $status . ' ' . $name . "\n"; }
$passed = count(array_filter($checks,static function($s){return $s==='PASS';}));
echo 'SUMMARY ' . $passed . '/' . count($checks) . " PASS\n";
if ($passed !== count($checks)) { exit(1); }