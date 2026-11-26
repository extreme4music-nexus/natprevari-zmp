<?php
/** Actual `.63` baseline → `.64` migration/preservation and repeat-install check. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_test' || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.64') { fwrite(STDERR,"wrong_test_runtime\n"); exit(3); }
$manifest=json_decode((string)file_get_contents('/tmp/zmp64-isolated-20261103/run/manifest.json'),true);
if (!is_array($manifest) || ($manifest['database']??'')!=='zmp64_test') { fwrite(STDERR,"manifest_unavailable\n"); exit(4); }
$prefix=$wpdb->prefix; $ids=$manifest['ids']; $checks=array(); $failures=array();
$check=static function($ok,$label) use (&$checks,&$failures) { $checks[]=array('assertion'=>$label,'result'=>$ok?'PASS':'FAIL'); if(!$ok){$failures[]=$label;} };
$row_hash=static function($table,$id,$columns=null) use ($wpdb,$prefix) {
    $sql='SELECT * FROM '.$prefix.$table.' WHERE id=%d';
    if (is_array($columns)) { $sql='SELECT '.implode(',',array_map(static function($c){return '`'.preg_replace('/[^a-z0-9_]/i','',$c).'`';},$columns)).' FROM '.$prefix.$table.' WHERE id=%d'; }
    $row=$wpdb->get_row($wpdb->prepare($sql,(int)$id),ARRAY_A);
    return is_array($row)?hash('sha256',wp_json_encode($row)):'';
};
$legacy_school_columns=array('id','mentor_id','school_name','municipality','region','region_is_override','school_key','is_primary','is_active','created_at','updated_at');
$map=array('schools'=>'zmp_mentor_schools','assignment'=>'zmp_mentor_school_subjects','grade'=>'zmp_mentor_school_subject_grades','student'=>'zmp_students','registration'=>'zmp_registration_forms');
foreach($map as $key=>$table) {
    $columns=$key==='schools'?$legacy_school_columns:null;
    $current=$row_hash($table,$ids[$key],$columns);
    $check($current!=='' && hash_equals((string)$manifest['baseline_hashes'][$key],$current),'preserve_'.$key.'_row_hash');
}
$epoch_count=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_schools WHERE engagement_epoch=1');
$check($epoch_count===(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_schools'),'legacy_engagements_receive_epoch_one');
$check(ZMP_Mentor_Schools::schema_is_compatible(),'mentor_school_schema_compatible');
$check(ZMP_Mentor_School_Profile::schema_is_compatible(),'profile_schema_compatible');
$check(ZMP_School_Email_Access::schema_is_compatible(),'email_access_schema_compatible');
$before_counts=array('profiles'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_school_profiles'),'requests'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_requests'),'events'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_request_events'));
$install_passes=array();
for($i=1;$i<=2;$i++) {
    $install_passes[]=array('schools'=>(bool)ZMP_Mentor_Schools::install(),'profiles'=>(bool)ZMP_Mentor_School_Profile::install(),'email'=>(bool)ZMP_School_Email_Access::install());
}
$after_counts=array('profiles'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_school_profiles'),'requests'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_requests'),'events'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_request_events'));
$check($install_passes[0]===array('schools'=>true,'profiles'=>true,'email'=>true) && $install_passes[1]===array('schools'=>true,'profiles'=>true,'email'=>true),'repeat_install_two_passes');
$check($before_counts===$after_counts,'repeat_install_creates_no_duplicate_feature_rows');
foreach($map as $key=>$table) {
    $columns=$key==='schools'?$legacy_school_columns:null;
    $current=$row_hash($table,$ids[$key],$columns);
    $check($current!=='' && hash_equals((string)$manifest['baseline_hashes'][$key],$current),'preserve_'.$key.'_after_repeat_install');
}
$check($wpdb->last_error==='','wpdb_has_no_error');
$out=array('status'=>$failures?'FAIL':'PASS','assertion_count'=>count($checks),'failures'=>$failures,'checks'=>$checks,'database'=>$wpdb->dbname,'version'=>ZMP_VERSION,'synthetic_ids'=>array('baseline_school'=>$ids['schools']['owner'],'assignment'=>$ids['assignment'],'grade'=>$ids['grade'],'student'=>$ids['student'],'registration'=>$ids['registration']),'existing_school_epoch_one_count'=>$epoch_count,'feature_table_counts_before'=>$before_counts,'feature_table_counts_after'=>$after_counts,'wpdb_error'=>($wpdb->last_error===''?'':'database_error'));
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($failures){exit(1);}
