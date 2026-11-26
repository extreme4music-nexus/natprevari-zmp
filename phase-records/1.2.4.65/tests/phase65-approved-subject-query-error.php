<?php
/**
 * Synthetic regression fixture for approved-subject catalog query failures.
 * Run only after the exact reviewed candidate is staged at the disposable
 * site /home/tonic/zmp-local-sandbox/phase65-subject-catalog/site (DB
 * zmp65_subject_catalog_test, prefix z65_, URL 127.0.0.1:8100).
 * This fixture deliberately has no mode that targets another site.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_catalog_test') {
    fwrite(STDERR, "BLOCKED: dedicated disposable WP-CLI site required.\n"); exit(2);
}
global $wpdb;
$site = wp_parse_url(home_url('/'));
$expected_root = '/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site';
$actual_root = realpath(ABSPATH);
if (!isset($wpdb) || $wpdb->prefix !== 'z65_' || !is_array($site)
    || ($site['scheme'] ?? '') !== 'http' || ($site['host'] ?? '') !== '127.0.0.1'
    || (int) ($site['port'] ?? 0) !== 8100 || $actual_root !== $expected_root
    || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65') {
    fwrite(STDERR, "BLOCKED: isolated candidate identity mismatch.\n"); exit(2);
}
$source_hashes = array(
    'ZMP_Mentor_Subjects'=>'A350933909C8046AB98B6F6CA9DC21DBAC68CD090ECC00F4BCCF89EBBF5475F6',
    'ZMP_Mentor_Subject_Proposals'=>'8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69',
    'ZMP_Support'=>'34BE99AC328999513A80F9B95D0C2A3EE78CB631299BE33B975AB5292DA95198',
    'ZMP_Mentor'=>'897298C52A8317378392245A68A3DCFD09B60E06A35EDDDB32D0E1433FC03FC2',
    'ZMP_President_Subject_Approvals'=>'720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4',
);
foreach ($source_hashes as $class=>$expected) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: candidate service unavailable.\n"); exit(2); }
    $file = (new ReflectionClass($class))->getFileName();
    if (!is_string($file) || !is_file($file) || !hash_equals($expected, strtoupper(hash_file('sha256', $file)))) {
        fwrite(STDERR, "BLOCKED: loaded product source differs from reviewed candidate.\n"); exit(2);
    }
}
foreach (array('ZMP_Mentor_Subject_Proposals','ZMP_President_Mandate','ZMP_Mentor_Schools','ZMP_Regions','ZMP_Debug') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required product service unavailable.\n"); exit(2); }
}
if (ZMP_Mentor_Subject_Proposals::schema_is_compatible() !== true) {
    fwrite(STDERR, "BLOCKED: subject proposal/catalog prerequisite schema is not ready.\n"); exit(2);
}
$admins = get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$user_totals = count_users();
if (count((array)$admins) !== 1 || (int)($user_totals['total_users'] ?? -1) !== 1) {
    fwrite(STDERR, "BLOCKED: fresh synthetic site with one administrator required.\n"); exit(2);
}
$catalog = $wpdb->prefix . 'zmp_subject_catalog';
$mentor_table = $wpdb->prefix . 'zmp_mentors';
$schools_table = $wpdb->prefix . 'zmp_mentor_schools';
$assignments_table = $wpdb->prefix . 'zmp_mentor_school_subjects';
$grades_table = $wpdb->prefix . 'zmp_mentor_school_subject_grades';
$debug_option = ZMP_Debug::OPTION;
$debug_row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $debug_option), ARRAY_A);
$debug_existed = is_array($debug_row);
$debug_before = $debug_existed ? maybe_unserialize($debug_row['option_value']) : null;
$debug_autoload = $debug_existed ? $debug_row['autoload'] : 'yes';
$created_users = array(); $mandate_id = 0; $created_mentor_id = 0; $assertions = 0; $failures = array();
$check = static function($ok,$label,$evidence=array()) use (&$assertions,&$failures) {
    $assertions++;
    if (!$ok) {
        $safe=array('assertion'=>$label);
        foreach ((array)$evidence as $key=>$value) {
            if (in_array($key,array('error_code','injected_queries','wp_die_caught','changed_resources','before','after'),true)) {
                if (is_scalar($value) || is_array($value)) { $safe[$key]=$value; }
            }
        }
        $failures[]=$safe;
    }
};
$count = static function($table) use ($wpdb) {
    $value = $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($table) . '`');
    return $wpdb->last_error === '' && is_numeric($value) ? (int)$value : null;
};
$capture = static function($callback) { ob_start(); try { $callback(); return (string)ob_get_contents(); } finally { ob_end_clean(); } };
$inject_count = 0;
$inject = static function($sql) use (&$inject_count,$wpdb) {
    $needle = 'SELECT id,subject_name FROM ' . $wpdb->prefix . 'zmp_subject_catalog';
    if (stripos($sql,$needle) !== false) { $inject_count++; return str_ireplace($needle, 'SELECT id,zmp65_missing_catalog_column AS subject_name FROM ' . $wpdb->prefix . 'zmp_subject_catalog', $sql); }
    return $sql;
};
$with_catalog_error = static function($callback) use ($inject,&$inject_count,$wpdb) {
    $previous_suppression=$wpdb->suppress_errors(true);
    $inject_count = 0; add_filter('query',$inject,PHP_INT_MAX,1);
    try { $result = $callback(); return array($result,$inject_count); }
    finally { remove_filter('query',$inject,PHP_INT_MAX); $wpdb->suppress_errors($previous_suppression); }
};
$http_calls=0; $mail_calls=0;
$block_http=static function($preempt) use (&$http_calls) { $http_calls++; return new WP_Error('zmp65_http_blocked'); };
$block_mail=static function($preempt) use (&$mail_calls) { $mail_calls++; return false; };
add_filter('pre_http_request',$block_http,PHP_INT_MAX,1); add_filter('pre_wp_mail',$block_mail,PHP_INT_MAX,1);
$old_user=get_current_user_id(); $old_post=$_POST; $old_get=$_GET;
$tracked_tables=array($catalog,$mentor_table,$schools_table,$assignments_table,$grades_table,
    ZMP_President_Mandate::table_name(),ZMP_President_Mandate::audit_table_name());
$baseline_counts=array();
foreach ($tracked_tables as $table) { $baseline_counts[$table]=$count($table); }
$debug_original_value=$debug_existed ? (string)$debug_row['option_value'] : null;
$die_handler=static function() { return static function($message='',$title='',$args=array()) { throw new RuntimeException('expected fail-closed wp_die'); }; };
add_filter('wp_die_handler',$die_handler,PHP_INT_MAX,1);
try {
    foreach (array($catalog,$mentor_table,$schools_table,$assignments_table,$grades_table,
        ZMP_President_Mandate::table_name(),ZMP_President_Mandate::audit_table_name()) as $table) {
        if ($count($table) === null) { throw new RuntimeException('required schema unavailable'); }
    }
    $approved_count=$wpdb->get_var("SELECT COUNT(*) FROM `".esc_sql($catalog)."` WHERE status='approved' AND is_active=1");
    if ($wpdb->last_error !== '' || (int)$approved_count !== 0) { throw new RuntimeException('fresh empty approved catalog required'); }
    $empty=ZMP_Mentor_Subjects::approved_subjects();
    $check(is_array($empty) && $empty === array(),'healthy empty catalog is distinct');

    $create_user=static function($data) use (&$created_users) {
        $uid=wp_insert_user($data);
        if (!is_wp_error($uid)) { $created_users[]=(int)$uid; }
        return $uid;
    };
    $teacher_a=$create_user(array('user_login'=>'zmp65q_teacher_a','user_pass'=>wp_generate_password(32),'user_email'=>'zmp65q-a@example.invalid','role'=>'zmp_teacher','display_name'=>'Synthetic Teacher A'));
    if (is_wp_error($teacher_a)) throw new RuntimeException('synthetic user setup failed');
    $teacher_b=$create_user(array('user_login'=>'zmp65q_teacher_b','user_pass'=>wp_generate_password(32),'user_email'=>'zmp65q-b@example.invalid','role'=>'zmp_teacher','display_name'=>'Synthetic Teacher B'));
    if (is_wp_error($teacher_b)) throw new RuntimeException('synthetic user setup failed');
    $president=$create_user(array('user_login'=>'zmp65q_president','user_pass'=>wp_generate_password(32),'user_email'=>'zmp65q-president@example.invalid','role'=>'zmp_president','display_name'=>'Synthetic President'));
    if (is_wp_error($president)) throw new RuntimeException('synthetic user setup failed');
    $puser=get_user_by('id',$president); $puser->add_cap('view_zmp_president_dashboard');
    $start=current_time('Y-m-d'); $end=ZMP_President_Mandate::term_end_date($start);
    $mandate_ok=$wpdb->insert(ZMP_President_Mandate::table_name(),array(
        'user_id'=>(int)$president,'start_date'=>$start,'end_date'=>$end,
        'board_decision'=>'Synthetic subject query regression.','status'=>'active',
        'appointed_by'=>(int)$admins[0],'appointed_at'=>current_time('mysql'),
        'closed_by'=>0,'closed_at'=>null,'close_reason'=>'',
        'predecessor_mandate_id'=>0,'predecessor_user_id'=>0,
        'handover_deadline'=>null,'handover_extension_months'=>0,
    ));
    $mandate_id=$mandate_ok===1?(int)$wpdb->insert_id:0;
    if ($mandate_id < 1) throw new RuntimeException('synthetic mandate setup failed');

    $result=$with_catalog_error(static function(){ return ZMP_Mentor_Subjects::approved_subjects(); });
    $error=$result[0];
    $check($result[1]===1 && is_wp_error($error) && $error->get_error_code()==='schema_not_ready'
        && strpos(implode(' ', $error->get_error_messages()),'zmp65_missing_catalog_column')===false,'catalog query error returns generic schema_not_ready');

    wp_set_current_user((int)$president);
    $html=$with_catalog_error(static function() use ($capture){ return $capture(static function(){ ZMP_President_Subject_Approvals::render(); }); });
    $check($html[1]===1 && strpos($html[0],'Редицата за предлози моментално не е достапна')!==false
        && strpos($html[0],'Нема предлози што чекаат одлука.')===false,'president queue renders unavailable state',array('injected_queries'=>$html[1]));

    $mentor_inserted=$wpdb->insert($mentor_table,array('wp_user_id'=>(int)$teacher_b,'full_name'=>'Synthetic Teacher B','email'=>'zmp65q-b@example.invalid','phone'=>'','school_name'=>'Синтетичко училиште','municipality'=>'Скопје','region'=>ZMP_Regions::region_for_municipality('Скопје'),'login_token'=>'synthetic-test-token'));
    $created_mentor_id=$mentor_inserted===1?(int)$wpdb->insert_id:0;
    if (!$created_mentor_id) throw new RuntimeException('synthetic mentor setup failed');
    $mentor=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$mentor_table} WHERE wp_user_id=%d",(int)$teacher_b));
    $mentor_object=(new ReflectionClass('ZMP_Mentor'))->newInstanceWithoutConstructor();
    $panel_method=new ReflectionMethod('ZMP_Mentor','render_mentor_schools_panel');
    $panel=$with_catalog_error(static function() use ($panel_method,$mentor_object,$mentor){ return $panel_method->invoke($mentor_object,$mentor); });
    $check($panel[1]===1 && strpos($panel[0],'моментално не е достапен')!==false
        && strpos($panel[0],'нема активни училишта')===false && strpos($panel[0],'zmp-add-mentor-school-form')===false,'mentor schools panel renders unavailable state');

    wp_set_current_user((int)$admins[0]);
    $support=ZMP_Support::get_instance(); $teacher_user=get_userdata((int)$teacher_a);
    $profile=$with_catalog_error(static function() use ($support,$teacher_user,$capture){ return $capture(static function() use ($support,$teacher_user){ $support->render_user_profile_fields($teacher_user); }); });
    $check($profile[1]===1 && strpos($profile[0],'моментално не е достапен')!==false
        && strpos($profile[0],'zmp_teacher_assignments_present')===false,'teacher profile suppresses controls when unavailable');

    $snapshot=static function() use ($count,$mentor_table,$schools_table,$assignments_table,$grades_table,$teacher_a,$teacher_b) {
        return array('mentors'=>$count($mentor_table),'schools'=>$count($schools_table),'assignments'=>$count($assignments_table),'grades'=>$count($grades_table),
            'a_school'=>get_user_meta((int)$teacher_a,'zmp_school_name',true),'a_municipality'=>get_user_meta((int)$teacher_a,'zmp_municipality',true),
            'a_region'=>get_user_meta((int)$teacher_a,'zmp_region',true),'a_phone'=>get_user_meta((int)$teacher_a,'zmp_phone',true),
            'b_school'=>get_user_meta((int)$teacher_b,'zmp_school_name',true),'b_municipality'=>get_user_meta((int)$teacher_b,'zmp_municipality',true),
            'b_region'=>get_user_meta((int)$teacher_b,'zmp_region',true),'b_phone'=>get_user_meta((int)$teacher_b,'zmp_phone',true));
    };
    $before=$snapshot();
    $assert_blocked=static function($callback,$label) use (&$failures,&$assertions,$with_catalog_error,$snapshot,$check) {
        $before=$snapshot(); $caught=false;
        try { $r=$with_catalog_error($callback); } catch (RuntimeException $e) { $caught=$e->getMessage()==='expected fail-closed wp_die'; $r=array(null,1); }
        $after=$snapshot();
        $changed=array();
        foreach ($before as $key=>$value) { if (!array_key_exists($key,$after) || $after[$key]!==$value) { $changed[]=$key; } }
        foreach ($after as $key=>$value) { if (!array_key_exists($key,$before)) { $changed[]=$key; } }
        $check($caught && ($r[1]??0)===1 && !$changed,$label,array('injected_queries'=>(int)($r[1]??0),'wp_die_caught'=>$caught,'changed_resources'=>$changed));
    };

    wp_set_current_user((int)$teacher_a);
    $_POST=array('zmp_mentor_action'=>'setup_mentor_profile','_wpnonce'=>wp_create_nonce('zmp_mentor_setup_nonce'),'full_name'=>'Synthetic Teacher A','phone'=>'','school_name'=>'Синтетичко училиште','municipality'=>'Скопје');
    $assert_blocked(static function() { ZMP_Mentor::get_instance()->handle_mentor_actions(); },'profile setup blocks before DB/meta writes');

    wp_set_current_user((int)$teacher_b);
    $_POST=array('zmp_mentor_action'=>'add_mentor_school','_wpnonce'=>wp_create_nonce('zmp_add_mentor_school'),'school_name'=>'Друго синтетичко училиште','municipality'=>'Скопје');
    $assert_blocked(static function() { ZMP_Mentor::get_instance()->handle_mentor_actions(); },'direct school add blocks before insert');

    wp_set_current_user((int)$admins[0]);
    $_POST=array('zmp_teacher_profile_present'=>'1','_wpnonce'=>wp_create_nonce('update-user_'.(int)$teacher_a),'zmp_school_name'=>'Синтетичко училиште','zmp_municipality'=>'Скопје','zmp_phone'=>'');
    $assert_blocked(static function() use ($support,$teacher_a) { $support->save_user_profile_fields((int)$teacher_a); },'profile save blocks before writes');

    wp_set_current_user((int)$teacher_b);
    $assignment_before=$snapshot();
    $assignment_result=$with_catalog_error(static function() use ($teacher_b) {
        return ZMP_Mentor_Subjects::save_teacher_assignments((int)$teacher_b,array(array(1=>array('grade_7'))));
    });
    $assign=$assignment_result[0]; $assignment_after=$snapshot(); $assignment_changes=array();
    foreach ($assignment_before as $key=>$value) { if (!array_key_exists($key,$assignment_after) || $assignment_after[$key]!==$value) { $assignment_changes[]=$key; } }
    $check($assignment_result[1]===1 && is_wp_error($assign) && $assign->get_error_code()==='schema_not_ready'
        && !$assignment_changes,'assignment save blocks before mutation',array('error_code'=>is_wp_error($assign)?$assign->get_error_code():'none','injected_queries'=>$assignment_result[1],'changed_resources'=>$assignment_changes));
    $after_all=$snapshot(); $all_changes=array();
    foreach ($before as $key=>$value) { if (!array_key_exists($key,$after_all) || $after_all[$key]!==$value) { $all_changes[]=$key; } }
    $check(!$all_changes,'no partial school, assignment, grade, mentor or profile writes',array('changed_resources'=>$all_changes));
    $debug_after=get_option($debug_option,array());
    $debug_json=wp_json_encode($debug_after,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $check(is_string($debug_json) && strpos($debug_json,'zmp65_missing_catalog_column')===false
        && stripos($debug_json,'Unknown column')===false && stripos($debug_json,'SELECT id,')===false,
        'diagnostics do not disclose database error details or query text');
    $check($http_calls===0 && $mail_calls===0,'no outbound requests or mail');
} catch (Throwable $e) {
    $failures[]='fixture aborted safely: '.get_class($e);
} finally {
    remove_filter('wp_die_handler',$die_handler,PHP_INT_MAX);
    remove_filter('pre_http_request',$block_http,PHP_INT_MAX); remove_filter('pre_wp_mail',$block_mail,PHP_INT_MAX);
    wp_set_current_user($old_user); $_POST=$old_post; $_GET=$old_get;
    if ($mandate_id) {
        $audit_deleted=$wpdb->delete(ZMP_President_Mandate::audit_table_name(),array('mandate_id'=>$mandate_id));
        $mandate_deleted=$wpdb->delete(ZMP_President_Mandate::table_name(),array('id'=>$mandate_id));
        if ($audit_deleted===false || $mandate_deleted!==1) { $failures[]='mandate cleanup failed'; }
    }
    if ($created_users) {
        $owner_ids=implode(',',array_map('absint',$created_users));
        $owned_mentor_ids=$wpdb->get_col("SELECT id FROM `".esc_sql($mentor_table)."` WHERE wp_user_id IN ({$owner_ids})");
        if ($wpdb->last_error!=='' || !is_array($owned_mentor_ids)) { $failures[]='owned mentor lookup failed'; $owned_mentor_ids=array(); }
        foreach (array_unique(array_map('absint',$owned_mentor_ids)) as $owned_mentor_id) {
            $grades_deleted=$wpdb->query($wpdb->prepare("DELETE g FROM `{$grades_table}` g INNER JOIN `{$assignments_table}` a ON a.id=g.assignment_id WHERE a.mentor_id=%d",$owned_mentor_id));
            if ($grades_deleted===false) { $failures[]='grade cleanup failed'; }
            foreach (array($assignments_table,$schools_table) as $table) {
                $deleted=$wpdb->delete($table,array('mentor_id'=>$owned_mentor_id));
                if ($deleted===false) { $failures[]='mentor relation cleanup failed'; }
            }
            $mentor_deleted=$wpdb->delete($mentor_table,array('id'=>$owned_mentor_id));
            if ($mentor_deleted===false) { $failures[]='mentor cleanup failed'; }
        }
        foreach ($created_users as $uid) {
            foreach (array('zmp_school_name','zmp_municipality','zmp_region','zmp_phone') as $meta_key) { delete_user_meta($uid,$meta_key); }
        }
    }
    foreach ($created_users as $uid) {
        if (!wp_delete_user($uid) || get_user_by('id',$uid)) { $failures[]='synthetic user cleanup failed'; }
    }
    if ($debug_existed) {
        $restored=$wpdb->update($wpdb->options,array('option_value'=>$debug_original_value,'autoload'=>$debug_autoload),array('option_name'=>$debug_option));
        if ($restored===false) { $failures[]='diagnostic option restore failed'; }
        wp_cache_delete($debug_option,'options');
        $debug_check=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$debug_option),ARRAY_A);
        if (!is_array($debug_check) || $debug_check['option_value']!==$debug_original_value || $debug_check['autoload']!==$debug_autoload) { $failures[]='diagnostic option baseline mismatch'; }
    } else {
        delete_option($debug_option);
        $left=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name=%s",$debug_option));
        if ($left===null || (int)$left!==0) { $failures[]='diagnostic option cleanup failed'; }
    }
    foreach ($tracked_tables as $table) {
        if ($baseline_counts[$table]===null || $count($table)!==$baseline_counts[$table]) { $failures[]='database baseline not restored'; break; }
    }
}
if ($failures) { fwrite(STDERR, "FAIL: ".wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures),JSON_UNESCAPED_SLASHES)."\n"); exit(1); }
fwrite(STDOUT, "PASS: ".wp_json_encode(array('assertions'=>$assertions,'failures'=>array()),JSON_UNESCAPED_SLASHES)."\n");
