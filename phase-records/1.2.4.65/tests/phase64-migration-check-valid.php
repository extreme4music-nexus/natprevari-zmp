<?php
/** Verify valid `.63` → `.64` row preservation, zero-ID normalization and repeat installs. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_migration_retry' || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.64') { fwrite(STDERR,"wrong_test_runtime\n"); exit(3); }
$manifest=json_decode((string)file_get_contents('/tmp/zmp64-isolated-20261103/retry-run/manifest.json'),true);
if (!is_array($manifest) || ($manifest['database']??'')!=='zmp64_migration_retry') { fwrite(STDERR,"manifest_unavailable\n"); exit(4); }
$prefix=$wpdb->prefix;$ids=$manifest['ids'];$checks=array();$failures=array();$install_passes=array();
$check=static function($condition,$label)use(&$checks,&$failures){$checks[]=array('assertion'=>$label,'result'=>$condition?'PASS':'FAIL');if(!$condition){$failures[]=$label;}};
$read=static function($table,$id,$columns=null)use($wpdb,$prefix){$sql='SELECT * FROM '.$prefix.$table.' WHERE id=%d';if(is_array($columns)){$sql='SELECT '.implode(',',array_map(static function($c){return '`'.preg_replace('/[^a-z0-9_]/i','',$c).'`';},$columns)).' FROM '.$prefix.$table.' WHERE id=%d';}return $wpdb->get_row($wpdb->prepare($sql,(int)$id),ARRAY_A);};
$hash=static function($row){return is_array($row)?hash('sha256',wp_json_encode($row)):'';};
$school_columns=array('id','mentor_id','school_name','municipality','region','region_is_override','school_key','is_primary','is_active','created_at','updated_at');
$table_map=array('school'=>array('zmp_mentor_schools',(int)$ids['schools']['owner'],$school_columns),'assignment'=>array('zmp_mentor_school_subjects',(int)$ids['assignment'],null),'grade'=>array('zmp_mentor_school_subject_grades',(int)$ids['grade'],null),'registration'=>array('zmp_registration_forms',(int)$ids['registration'],null));
foreach($table_map as $key=>$spec){$row=$read($spec[0],$spec[1],$spec[2]);$check($row && hash_equals((string)$manifest['baseline_hashes'][$key],$hash($row)),'preserve_'.$key.'_baseline_hash');}
$preserved_id=(int)$ids['students']['preserved_nonzero'];$preserved=$read('zmp_students',$preserved_id);$check($preserved && hash_equals((string)$manifest['student_baseline_hashes']['preserved_nonzero'],$hash($preserved)),'preserve_nonzero_student_full_hash');
$backfill_id=(int)$ids['students']['zero_backfill'];$backfill=$read('zmp_students',$backfill_id);$backfill_projected=$backfill;$backfill_projected['competition_id']='0';
$check($backfill && hash_equals((string)$manifest['student_baseline_hashes']['zero_backfill'],$hash($backfill_projected)),'backfill_student_only_expected_competition_field');
$check((int)($backfill['competition_id']??-1)===(int)$ids['active_competition'],'legacy_zero_competition_id_maps_to_active');
$epoch_count=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_schools WHERE engagement_epoch=1');$school_count=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_schools');
$check($school_count===4 && $epoch_count===4,'all_legacy_engagements_preserved_with_epoch_one');
$check(ZMP_Mentor_Schools::schema_is_compatible(),'mentor_school_schema_compatible');$check(ZMP_Mentor_School_Profile::schema_is_compatible(),'profile_schema_compatible');$check(ZMP_School_Email_Access::schema_is_compatible(),'email_access_schema_compatible');
$feature_counts=static function()use($wpdb,$prefix){return array('profiles'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_mentor_school_profiles'),'requests'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_requests'),'events'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.$prefix.'zmp_school_email_request_events'));};
$counts_before=$feature_counts();
for($pass=1;$pass<=2;$pass++){
    $install_passes[$pass]=array('schools'=>(bool)ZMP_Mentor_Schools::install(),'profiles'=>(bool)ZMP_Mentor_School_Profile::install(),'email_access'=>(bool)ZMP_School_Email_Access::install());
}
$counts_after=$feature_counts();
$check($install_passes[1]===array('schools'=>true,'profiles'=>true,'email_access'=>true),'install_pass_one_all_services_true');
$check($install_passes[2]===array('schools'=>true,'profiles'=>true,'email_access'=>true),'install_pass_two_all_services_true');
$check($counts_before===$counts_after,'repeat_installs_create_no_duplicate_feature_rows');
foreach($table_map as $key=>$spec){$row=$read($spec[0],$spec[1],$spec[2]);$check($row && hash_equals((string)$manifest['baseline_hashes'][$key],$hash($row)),'repeat_preserve_'.$key);}
$preserved=$read('zmp_students',$preserved_id);$check($preserved && hash_equals((string)$manifest['student_baseline_hashes']['preserved_nonzero'],$hash($preserved)),'repeat_preserve_nonzero_student_full_hash');
$backfill=$read('zmp_students',$backfill_id);$backfill_projected=$backfill;$backfill_projected['competition_id']='0';$check($backfill && hash_equals((string)$manifest['student_baseline_hashes']['zero_backfill'],$hash($backfill_projected)) && (int)$backfill['competition_id']===(int)$ids['active_competition'],'repeat_zero_backfill_is_stable');
$check($wpdb->last_error==='','wpdb_has_no_error');
$out=array('status'=>$failures?'FAIL':'PASS','assertion_count'=>count($checks),'failures'=>$failures,'checks'=>$checks,'database'=>$wpdb->dbname,'version'=>ZMP_VERSION,'install_passes'=>$install_passes,'legacy_synthetic_ids'=>array('school'=>(int)$ids['schools']['owner'],'assignment'=>(int)$ids['assignment'],'grade'=>(int)$ids['grade'],'preserved_student'=>$preserved_id,'backfill_student'=>$backfill_id,'registration'=>(int)$ids['registration']),'active_competition_id'=>(int)$ids['active_competition'],'baseline_hashes'=>array('school'=>$manifest['baseline_hashes']['school'],'assignment'=>$manifest['baseline_hashes']['assignment'],'grade'=>$manifest['baseline_hashes']['grade'],'preserved_student'=>$manifest['student_baseline_hashes']['preserved_nonzero'],'zero_backfill_student'=>$manifest['student_baseline_hashes']['zero_backfill'],'registration'=>$manifest['baseline_hashes']['registration']),'engagement_epoch_one_count'=>$epoch_count,'school_count'=>$school_count,'feature_counts_before'=>$counts_before,'feature_counts_after'=>$counts_after,'wpdb_error_present'=>$wpdb->last_error!=='');
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($failures){exit(1);}
