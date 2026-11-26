<?php
/** Seed only synthetic `.63` baseline rows in the isolated `.64` test database. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_test') { fwrite(STDERR, "wrong_database\n"); exit(3); }
if (!class_exists('ZMP_Mentor_Schools') || (string)$wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "zmp_mentor_schools'") !== $wpdb->prefix . 'zmp_mentor_schools') { fwrite(STDERR, "baseline_plugin_unavailable\n"); exit(4); }
$token = bin2hex(random_bytes(6));
$prefix = $wpdb->prefix;
$ids = array('users'=>array(),'mentors'=>array(),'schools'=>array(),'assignment'=>0,'grade'=>0,'student'=>0,'registration'=>0,'mandate'=>0);
$assert = static function($ok,$label) { if (!$ok) { throw new RuntimeException($label); } };
$insert_user = static function($key,$role) use (&$ids,$token,$assert) {
    $suffix = $token . '_' . $key;
    $id = wp_insert_user(array('user_login'=>'zmp64_'.$suffix,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>'zmp64-'.$suffix.'@example.invalid','display_name'=>'Synthetic '.$key,'role'=>$role));
    $assert(!is_wp_error($id) && (int)$id>0,'synthetic_user_insert_'.$key);
    $ids['users'][$key]=(int)$id;
    return (int)$id;
};
try {
    $owner_id=$insert_user('owner','zmp_teacher');
    $requester_id=$insert_user('requester','zmp_teacher');
    $other_id=$insert_user('other','zmp_teacher');
    $president_id=$insert_user('president','zmp_president');
    $moderator_id=$insert_user('moderator','zmp_moderator');
    $testator_id=$insert_user('testator','zmp_testator');
    $president=get_user_by('id',$president_id); $president->add_cap('view_zmp_president_dashboard');
    foreach (array('owner'=>$owner_id,'requester'=>$requester_id,'other'=>$other_id) as $key=>$uid) {
        $ok=$wpdb->insert($prefix.'zmp_mentors',array('wp_user_id'=>$uid,'full_name'=>'Synthetic '.$key.' '.$token,'email'=>'zmp64-'.$key.'-'.$token.'@example.invalid','phone'=>'5550100','school_name'=>'Synthetic School '.$token,'municipality'=>'Test','region'=>'skopje','login_token'=>hash('sha256',$token.'-'.$key)));
        $assert($ok===1,'mentor_insert_'.$key); $ids['mentors'][$key]=(int)$wpdb->insert_id;
    }
    $school_key=hash('sha256','school-'.$token);
    $other_school_key=hash('sha256','other-school-'.$token);
    $foreign_key=hash('sha256','foreign-school-'.$token);
    $insert_school=static function($mentor_id,$key,$school_name) use ($wpdb,$prefix,$assert) {
        $ok=$wpdb->insert($prefix.'zmp_mentor_schools',array('mentor_id'=>$mentor_id,'school_name'=>$school_name,'municipality'=>'Test','region'=>'skopje','region_is_override'=>0,'school_key'=>$key,'is_primary'=>1,'is_active'=>1));
        $assert($ok===1,'school_insert'); return (int)$wpdb->insert_id;
    };
    $ids['schools']['owner']=$insert_school($ids['mentors']['owner'],$school_key,'Synthetic School '.$token);
    $ids['schools']['owner_second']=$insert_school($ids['mentors']['owner'],$other_school_key,'Synthetic Second School '.$token);
    $ids['schools']['requester']=$insert_school($ids['mentors']['requester'],$school_key,'Synthetic School '.$token);
    $ids['schools']['other']=$insert_school($ids['mentors']['other'],$foreign_key,'Synthetic Foreign School '.$token);
    $assert($wpdb->insert($prefix.'zmp_mentor_school_subjects',array('mentor_id'=>$ids['mentors']['owner'],'school_id'=>$ids['schools']['owner'],'subject_id'=>1))===1,'baseline_assignment_insert');
    $ids['assignment']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_mentor_school_subject_grades',array('assignment_id'=>$ids['assignment'],'grade_key'=>'zmp64_'.$token,'grade_label'=>'VIII одделение'))===1,'baseline_grade_insert');
    $ids['grade']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_students',array('mentor_id'=>$ids['mentors']['owner'],'mentor_school_id'=>$ids['schools']['owner'],'competition_id'=>0,'first_name'=>'Synthetic','last_name'=>$token,'grade'=>8,'language'=>'mk','school_name'=>'Synthetic School '.$token,'municipality'=>'Test','region'=>'skopje','access_code'=>'Z64'.$token,'access_pin'=>wp_hash_password('synthetic-pin-'.$token),'access_pin_hash'=>hash('sha256','synthetic-pin-'.$token),'current_phase'=>'school','qualified_for_next'=>0))===1,'baseline_student_insert');
    $ids['student']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_registration_forms',array('season_id'=>640000+(int)hexdec(substr($token,0,4)),'file_key'=>'z64-'.$token,'drive_file_id'=>'','storage_backend'=>'local','original_name'=>'synthetic-registration.pdf','mime_type'=>'application/pdf','file_size'=>64,'sha256'=>hash('sha256','registration-'.$token),'updated_by'=>1,'updated_at'=>current_time('mysql')))===1,'baseline_registration_insert');
    $ids['registration']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_president_mandates',array('user_id'=>$president_id,'start_date'=>gmdate('Y-m-d',time()-86400),'end_date'=>gmdate('Y-m-d',time()+86400*30),'board_decision'=>'Synthetic mandate '.$token,'status'=>'active','appointed_by'=>1,'appointed_at'=>current_time('mysql'),'closed_by'=>0,'closed_at'=>null,'close_reason'=>'','predecessor_mandate_id'=>0,'predecessor_user_id'=>0,'handover_deadline'=>null,'handover_extension_months'=>0))===1,'baseline_mandate_insert');
    $ids['mandate']=(int)$wpdb->insert_id;
    $tables=array('schools'=>'zmp_mentor_schools','assignment'=>'zmp_mentor_school_subjects','grade'=>'zmp_mentor_school_subject_grades','student'=>'zmp_students','registration'=>'zmp_registration_forms');
    $hashes=array();
    foreach($tables as $key=>$table) {
        $id=(int)$ids[$key]; $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}{$table} WHERE id=%d",$id),ARRAY_A);
        $assert(is_array($row),'baseline_snapshot_'.$key);
        $hashes[$key]=hash('sha256',wp_json_encode($row));
    }
    $manifest=array('schema'=>'phase64-synthetic-v1','database'=>'zmp64_test','prefix'=>$prefix,'token_sha256'=>hash('sha256',$token),'ids'=>$ids,'baseline_hashes'=>$hashes,'baseline_plugin'=>'a2c098eab04952030db1197812e48f7a428a63332571cbfc6a20313dfad3b3a8');
    file_put_contents('/tmp/zmp64-isolated-20261103/run/manifest.json',wp_json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo wp_json_encode(array('status'=>'PASS','database'=>$wpdb->dbname,'synthetic_user_count'=>count($ids['users']),'baseline_rows'=>array('schools'=>count($ids['schools']),'assignment'=>1,'grade'=>1,'student'=>1,'registration'=>1,'mandate'=>1),'ids'=>$ids,'hashes'=>$hashes),JSON_UNESCAPED_UNICODE)."\n";
} catch(Throwable $e) {
    echo wp_json_encode(array('status'=>'FAIL','error_code'=>'synthetic_seed_failed','cleanup_required'=>true),JSON_UNESCAPED_UNICODE)."\n";
    exit(1);
}
