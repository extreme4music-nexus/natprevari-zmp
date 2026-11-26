<?php
/** Seed a valid mapped `.63` baseline in the separate isolated migration retry database. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_migration_retry' || !class_exists('ZMP_Mentor_Schools')) { fwrite(STDERR,"wrong_baseline_runtime\n"); exit(3); }
$prefix=$wpdb->prefix; $token=bin2hex(random_bytes(6));
$active_competition=(int)$wpdb->get_var("SELECT id FROM {$prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1");
if($active_competition<1){fwrite(STDERR,"active_competition_required\n");exit(4);}
$ids=array('users'=>array(),'mentors'=>array(),'schools'=>array(),'assignment'=>0,'grade'=>0,'students'=>array(),'registration'=>0,'mandate'=>0,'active_competition'=>$active_competition);
$assert=static function($ok,$label){if(!$ok){throw new RuntimeException($label);}};
$user=static function($key,$role) use(&$ids,$token,$assert){$suffix=$token.'_'.$key;$id=wp_insert_user(array('user_login'=>'zmp64r_'.$suffix,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>'zmp64r-'.$suffix.'@example.invalid','display_name'=>'Synthetic '.$key,'role'=>$role));$assert(!is_wp_error($id)&&(int)$id>0,'insert_user_'.$key);$ids['users'][$key]=(int)$id;return(int)$id;};
try{
    $owner=$user('owner','zmp_teacher');$requester=$user('requester','zmp_teacher');$other=$user('other','zmp_teacher');
    $president=$user('president','zmp_president');$user('moderator','zmp_moderator');$user('testator','zmp_testator');
    get_user_by('id',$president)->add_cap('view_zmp_president_dashboard');
    foreach(array('owner'=>$owner,'requester'=>$requester,'other'=>$other) as $key=>$uid){
        $ok=$wpdb->insert($prefix.'zmp_mentors',array('wp_user_id'=>$uid,'full_name'=>'Synthetic '.$key.' '.$token,'email'=>'zmp64r-'.$key.'-'.$token.'@example.invalid','phone'=>'5550100','school_name'=>'Synthetic School '.$token,'municipality'=>'Скопје','region'=>'skopje','login_token'=>hash('sha256',$token.'-'.$key)));
        $assert($ok===1,'insert_mentor_'.$key);$ids['mentors'][$key]=(int)$wpdb->insert_id;
    }
    $school_key=ZMP_Mentor_Schools::school_key('Synthetic School '.$token,'Скопје');
    $second_key=ZMP_Mentor_Schools::school_key('Synthetic Second School '.$token,'Скопје');
    $foreign_key=ZMP_Mentor_Schools::school_key('Synthetic Foreign School '.$token,'Скопје');
    $add_school=static function($mentor_id,$key,$name,$primary)use($wpdb,$prefix,$assert){$ok=$wpdb->insert($prefix.'zmp_mentor_schools',array('mentor_id'=>$mentor_id,'school_name'=>$name,'municipality'=>'Скопје','region'=>'skopje','region_is_override'=>0,'school_key'=>$key,'is_primary'=>$primary?1:0,'is_active'=>1));$assert($ok===1,'insert_school');return(int)$wpdb->insert_id;};
    $ids['schools']['owner']=$add_school($ids['mentors']['owner'],$school_key,'Synthetic School '.$token,true);
    $ids['schools']['owner_second']=$add_school($ids['mentors']['owner'],$second_key,'Synthetic Second School '.$token,false);
    $ids['schools']['requester']=$add_school($ids['mentors']['requester'],$school_key,'Synthetic School '.$token,true);
    $ids['schools']['other']=$add_school($ids['mentors']['other'],$foreign_key,'Synthetic Foreign School '.$token,true);
    $assert($wpdb->insert($prefix.'zmp_mentor_school_subjects',array('mentor_id'=>$ids['mentors']['owner'],'school_id'=>$ids['schools']['owner'],'subject_id'=>1))===1,'insert_assignment');$ids['assignment']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_mentor_school_subject_grades',array('assignment_id'=>$ids['assignment'],'grade_key'=>'zmp64r_'.$token,'grade_label'=>'VIII одделение'))===1,'insert_grade');$ids['grade']=(int)$wpdb->insert_id;
    $student_values=array(
        array('label'=>'preserved_nonzero','competition_id'=>$active_competition,'access_code'=>'Z64R'.$token.'A'),
        array('label'=>'zero_backfill','competition_id'=>0,'access_code'=>'Z64R'.$token.'B'),
    );
    foreach($student_values as $value){$ok=$wpdb->insert($prefix.'zmp_students',array('mentor_id'=>$ids['mentors']['owner'],'mentor_school_id'=>$ids['schools']['owner'],'competition_id'=>$value['competition_id'],'first_name'=>'Synthetic','last_name'=>$token.'-'.$value['label'],'grade'=>8,'language'=>'mk','school_name'=>'Synthetic School '.$token,'municipality'=>'Скопје','region'=>'skopje','access_code'=>$value['access_code'],'access_pin'=>wp_hash_password('synthetic-pin-'.$token),'access_pin_hash'=>hash('sha256','synthetic-pin-'.$token),'current_phase'=>'school','qualified_for_next'=>0));$assert($ok===1,'insert_student_'.$value['label']);$ids['students'][$value['label']]=(int)$wpdb->insert_id;}
    $season_id=640000+(int)hexdec(substr($token,0,4));
    $assert($wpdb->insert($prefix.'zmp_registration_forms',array('season_id'=>$season_id,'file_key'=>'z64r-'.$token,'drive_file_id'=>'','storage_backend'=>'local','original_name'=>'synthetic-registration.pdf','mime_type'=>'application/pdf','file_size'=>64,'sha256'=>hash('sha256','registration-'.$token),'updated_by'=>1,'updated_at'=>current_time('mysql')))===1,'insert_registration');$ids['registration']=(int)$wpdb->insert_id;
    $assert($wpdb->insert($prefix.'zmp_president_mandates',array('user_id'=>$president,'start_date'=>gmdate('Y-m-d',time()-86400),'end_date'=>gmdate('Y-m-d',time()+86400*30),'board_decision'=>'Synthetic mandate '.$token,'status'=>'active','appointed_by'=>1,'appointed_at'=>current_time('mysql'),'closed_by'=>0,'closed_at'=>null,'close_reason'=>'','predecessor_mandate_id'=>0,'predecessor_user_id'=>0,'handover_deadline'=>null,'handover_extension_months'=>0))===1,'insert_mandate');$ids['mandate']=(int)$wpdb->insert_id;
    $table_map=array('school'=>'zmp_mentor_schools','assignment'=>'zmp_mentor_school_subjects','grade'=>'zmp_mentor_school_subject_grades','registration'=>'zmp_registration_forms');$hashes=array();
    foreach($table_map as $key=>$table){$id=(int)($key==='school'?$ids['schools']['owner']:$ids[$key]);$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}{$table} WHERE id=%d",$id),ARRAY_A);$assert(is_array($row),'snapshot_'.$key);$hashes[$key]=hash('sha256',wp_json_encode($row));}
    $student_hashes=array();foreach($ids['students'] as $key=>$id){$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}zmp_students WHERE id=%d",$id),ARRAY_A);$assert(is_array($row),'snapshot_student_'.$key);$student_hashes[$key]=hash('sha256',wp_json_encode($row));}
    $manifest=array('schema'=>'phase64-valid-baseline-v1','database'=>'zmp64_migration_retry','prefix'=>$prefix,'token_sha256'=>hash('sha256',$token),'ids'=>$ids,'baseline_hashes'=>$hashes,'student_baseline_hashes'=>$student_hashes,'baseline_plugin'=>'a2c098eab04952030db1197812e48f7a428a63332571cbfc6a20313dfad3b3a8');
    file_put_contents('/tmp/zmp64-isolated-20261103/retry-run/manifest.json',wp_json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo wp_json_encode(array('status'=>'PASS','database'=>$wpdb->dbname,'synthetic_user_count'=>count($ids['users']),'legacy_rows'=>array('schools'=>count($ids['schools']),'assignment'=>1,'grade'=>1,'students'=>count($ids['students']),'registration'=>1,'mandate'=>1),'active_competition_id'=>$active_competition,'synthetic_ids'=>$ids,'baseline_hashes'=>$hashes,'student_hashes'=>$student_hashes),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}catch(Throwable $e){echo wp_json_encode(array('status'=>'FAIL','error_code'=>'synthetic_seed_failed','cleanup_required'=>true),JSON_UNESCAPED_UNICODE)."\n";exit(1);}
