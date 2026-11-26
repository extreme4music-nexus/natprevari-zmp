<?php
global $wpdb;
wp_set_current_user(1);
foreach (array('ZMP_Mentor_School_Profile','ZMP_Mentor_Schools','ZMP_President_Mandate','ZMP_School_Email_Access','ZMP_Debug') as $class) {
    if (!class_exists($class)) WP_CLI::error('required local service missing: ' . $class);
}
if (DB_NAME !== 'zmp66b_isolated' || $wpdb->prefix !== 'z66a_' || !current_user_can('manage_options')
    || !ZMP_Mentor_School_Profile::schema_is_compatible() || !ZMP_Mentor_Schools::schema_is_compatible()
    || !ZMP_School_Email_Access::schema_is_compatible()) {
    WP_CLI::error('local database/schema/admin preflight failed');
}
$mandate_table = ZMP_President_Mandate::table_name();
$active = $wpdb->get_row("SELECT id,user_id,status FROM {$mandate_table} WHERE status='active'", ARRAY_A);
if (!is_array($active) || count((array) $wpdb->get_results("SELECT id FROM {$mandate_table} WHERE status='active'")) !== 1 || (int) $active['user_id'] !== 116) {
    WP_CLI::error('existing active synthetic President baseline mismatch');
}
$base_active = $active;
$token = strtolower(wp_generate_password(14, false, false));
$school_name = 'Synthetic denial school ' . $token;
$municipality = 'Скопје';
$email = 'deny-' . $token . '@example.test';
$users = array();
$mentors = array();
$schools = array();
$request_id = 0;
$tables = array(
    'users' => $wpdb->users,
    'mentors' => $wpdb->prefix . 'zmp_mentors',
    'schools' => $wpdb->prefix . 'zmp_mentor_schools',
    'profiles' => $wpdb->prefix . 'zmp_mentor_school_profiles',
    'requests' => $wpdb->prefix . 'zmp_school_email_requests',
    'events' => $wpdb->prefix . 'zmp_school_email_request_events',
);
$counts = array();
foreach ($tables as $key => $table) $counts[$key] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$debug_table = $wpdb->options;
$debug_name = ZMP_Debug::OPTION;
$debug_before = $wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s", $debug_name), ARRAY_A);
if ($wpdb->last_error !== '') WP_CLI::error('baseline snapshot failed');
$check = array();
$make_teacher = static function ($suffix) use (&$users, $token) {
    $login = 'zmp66_maildeny_' . $suffix . '_' . $token;
    $id = wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(36,true,true),'user_email'=>$login.'@example.test','display_name'=>'Synthetic '.$suffix,'role'=>'zmp_teacher'));
    if (is_wp_error($id) || (int)$id < 1) throw new RuntimeException('teacher creation failed');
    $id=(int)$id; $users[]=$id; get_user_by('id',$id)->add_role('subscriber'); clean_user_cache($id); return $id;
};
$assert = static function ($key, $ok) use (&$check) { $check[$key] = $ok ? true : false; };
try {
    $requester = $make_teacher('requester');
    $owner = $make_teacher('owner');
    $ghost_login = 'zmp66_maildeny_president_' . $token;
    $ghost = wp_insert_user(array('user_login'=>$ghost_login,'user_pass'=>wp_generate_password(36,true,true),'user_email'=>$ghost_login.'@example.test','display_name'=>'Synthetic unmandated President','role'=>'zmp_president'));
    if (is_wp_error($ghost) || (int)$ghost < 1) throw new RuntimeException('unmandated President creation failed');
    $ghost=(int)$ghost; $users[]=$ghost; get_user_by('id',$ghost)->add_role('subscriber'); clean_user_cache($ghost);
    foreach (array($requester,$owner) as $uid) {
        $u=get_userdata($uid);
        $ok=$wpdb->insert($wpdb->prefix.'zmp_mentors',array('wp_user_id'=>$uid,'full_name'=>$u->display_name,'email'=>$u->user_email,'phone'=>'','school_name'=>$school_name,'municipality'=>$municipality,'region'=>'Скопски','login_token'=>wp_generate_password(32,false)));
        if ($ok!==1) throw new RuntimeException('synthetic mentor insert failed');
        $mentors[$uid]=(int)$wpdb->insert_id;
    }
    wp_set_current_user($requester);
    $requester_school=ZMP_Mentor_Schools::add_school($mentors[$requester],$school_name,$municipality,true);
    if (is_wp_error($requester_school)||(int)$requester_school<1) throw new RuntimeException('requester school setup failed');
    $schools[]=(int)$requester_school;
    wp_set_current_user($owner);
    $owner_school=ZMP_Mentor_Schools::add_school($mentors[$owner],$school_name,$municipality,true);
    if (is_wp_error($owner_school)||(int)$owner_school<1) throw new RuntimeException('owner school setup failed');
    $schools[]=(int)$owner_school;
    $payload=array('official_email'=>$email,'employment_term'=>'indefinite','workload'=>'full','retired'=>0,'replacement'=>0,'replacement_for'=>'','replacement_start_date'=>'','replacement_end_date'=>'','employment_term_visible'=>0,'workload_visible'=>0,'retired_visible'=>0,'replacement_visible'=>0,'replacement_for_visible'=>0,'replacement_start_date_visible'=>0,'replacement_end_date_visible'=>0);
    $saved=ZMP_Mentor_School_Profile::save_profile($owner,(int)$owner_school,$payload);
    if (is_wp_error($saved)) throw new RuntimeException('synthetic owner profile setup failed');
    wp_set_current_user($requester);
    $submitted=ZMP_School_Email_Access::submit_request($requester,$owner,(int)$requester_school,'Synthetic request for school email denial test '.$token);
    if (is_wp_error($submitted)||($submitted['status']??'')!=='pending') throw new RuntimeException('pending request setup failed');
    $request_id=(int)$submitted['request_id'];
    $request_table=$wpdb->prefix.'zmp_school_email_requests';
    $event_table=$wpdb->prefix.'zmp_school_email_request_events';
    $baseline_request=$wpdb->get_row($wpdb->prepare("SELECT status,decided_by,approved_email_hash,approved_email_epoch FROM {$request_table} WHERE id=%d",$request_id),ARRAY_A);
    $baseline_events=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d",$request_id));
    $baseline_state=static function () use ($wpdb,$request_table,$event_table,$request_id,$baseline_events) {
        $r=$wpdb->get_row($wpdb->prepare("SELECT status,decided_by,approved_email_hash,approved_email_epoch FROM {$request_table} WHERE id=%d",$request_id),ARRAY_A);
        $n=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$event_table} WHERE request_id=%d",$request_id));
        return $r===array('status'=>'pending','decided_by'=>'0','approved_email_hash'=>'','approved_email_epoch'=>'0') && $n===$baseline_events;
    };
    wp_set_current_user($requester);
    $denied_teacher=ZMP_School_Email_Access::decide($request_id,$requester,'approved');
    $assert('ordinary_teacher_cannot_decide_pending_request',is_wp_error($denied_teacher)&&$denied_teacher->get_error_code()==='forbidden'&&$baseline_state());
    wp_set_current_user($ghost);
    $denied_no_mandate=ZMP_School_Email_Access::decide($request_id,$ghost,'approved');
    $assert('president_role_without_active_mandate_cannot_decide',is_wp_error($denied_no_mandate)&&$denied_no_mandate->get_error_code()==='forbidden'&&$baseline_state());
    $die_message='';
    add_filter('wp_die_handler',static function() use (&$die_message) { return static function($message='',$title='',$args=array()) use (&$die_message) { $die_message=wp_strip_all_tags((string)$message); throw new RuntimeException('wp_die intercepted'); }; });
    wp_set_current_user(1);
    $_POST=array('request_id'=>(string)$request_id,'decision'=>'approved','_wpnonce'=>'invalid-nonce');
    $_REQUEST=$_POST;
    try { ZMP_School_Email_Access::handle_decision(); $nonce_denied=false; }
    catch (RuntimeException $e) { $nonce_denied=$e->getMessage()==='wp_die intercepted'; }
    $assert('invalid_nonce_is_rejected_without_decision', $nonce_denied && $baseline_state() && strpos($die_message,'Безбедносната проверка')!==false);
} catch (Throwable $e) {
    WP_CLI::warning('synthetic setup failure: ' . sanitize_key($e->getMessage()));
    $check['runtime_completed']=false;
} finally {
    wp_set_current_user(1);
    if ($request_id>0) {
        $wpdb->delete($wpdb->prefix.'zmp_school_email_request_events',array('request_id'=>$request_id),array('%d'));
        $wpdb->delete($wpdb->prefix.'zmp_school_email_requests',array('id'=>$request_id),array('%d'));
    }
    foreach ($mentors as $uid=>$mid) {
        foreach ($schools as $sid) {
            $wpdb->delete($wpdb->prefix.'zmp_mentor_school_profiles',array('mentor_id'=>$mid,'school_id'=>(int)$sid),array('%d','%d'));
            $wpdb->delete($wpdb->prefix.'zmp_mentor_schools',array('id'=>(int)$sid,'mentor_id'=>(int)$mid),array('%d','%d'));
        }
        $wpdb->delete($wpdb->prefix.'zmp_mentors',array('id'=>(int)$mid,'wp_user_id'=>(int)$uid),array('%d','%d'));
    }
    foreach ($users as $uid) if (get_userdata((int)$uid)) wp_delete_user((int)$uid);
    wp_cache_delete($debug_name,'options'); wp_cache_delete('alloptions','options');
    $current=$wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s",$debug_name),ARRAY_A);
    if (is_array($debug_before)) {
        if (is_array($current)) $wpdb->update($debug_table,array('option_value'=>$debug_before['option_value'],'autoload'=>$debug_before['autoload']),array('option_id'=>(int)$current['option_id']),array('%s','%s'),array('%d'));
        else $wpdb->insert($debug_table,array('option_name'=>$debug_name,'option_value'=>$debug_before['option_value'],'autoload'=>$debug_before['autoload']),array('%s','%s','%s'));
    } elseif (is_array($current)) $wpdb->delete($debug_table,array('option_id'=>(int)$current['option_id']),array('%d'));
    wp_cache_delete($debug_name,'options'); wp_cache_delete('alloptions','options');
    $after=array(); foreach($tables as $key=>$table) $after[$key]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $assert('exact_fixture_rows_and_users_restored',$counts===$after && (int)$wpdb->get_var("SELECT COUNT(*) FROM {$mandate_table} WHERE status='active' AND id=".(int)$base_active['id']." AND user_id=116")===1);
    $debug_after=$wpdb->get_row($wpdb->prepare("SELECT option_id,option_value,autoload FROM {$debug_table} WHERE option_name=%s",$debug_name),ARRAY_A);
    $assert('diagnostic_option_restored_exactly',$debug_before===$debug_after);
}
foreach($check as $key=>$ok) echo ($ok?'PASS ':'FAIL ').$key.PHP_EOL;
$pass=count(array_filter($check,static function($v){return $v===true;}));
echo 'SUMMARY '.$pass.'/'.count($check).' PASS'.PHP_EOL;
if($pass!==count($check)) exit(1);
