<?php
/** Read-only, redacted diagnosis of the first `.63`→`.64` migration result. */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (($wpdb->dbname ?? '') !== 'zmp64_test') { exit(3); }
$manifest=json_decode((string)file_get_contents('/tmp/zmp64-isolated-20261103/run/manifest.json'),true);
if (!is_array($manifest)) { exit(4); }
$prefix=$wpdb->prefix; $ids=$manifest['ids']; $student_id=(int)$ids['student'];
$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}zmp_students WHERE id=%d",$student_id),ARRAY_A);
$user=get_userdata((int)$ids['users']['owner']);
$token='';
if($user && preg_match('/^zmp64_([a-f0-9]+)_owner$/',(string)$user->user_login,$m)){ $token=$m[1]; }
$expected=array('id'=>$student_id,'mentor_id'=>(int)$ids['mentors']['owner'],'mentor_school_id'=>(int)$ids['schools']['owner'],'competition_id'=>0,'first_name'=>'Synthetic','last_name'=>$token,'grade'=>8,'language'=>'mk','school_name'=>'Synthetic School '.$token,'municipality'=>'Test','region'=>'skopje','access_code'=>'Z64'.$token,'access_pin_hash'=>hash('sha256','synthetic-pin-'.$token),'current_phase'=>'school','qualified_for_next'=>0);
$field_matches=array();
foreach($expected as $field=>$value){ $field_matches[$field]=is_array($row) && (string)($row[$field]??'')===(string)$value; }
$pin_input_valid=is_array($row) && wp_check_password('synthetic-pin-'.$token,(string)($row['access_pin']??''));
$full_current_hash=is_array($row)?hash('sha256',wp_json_encode($row)):'';
$events=get_option('zmp_debug_events',array()); $safe_events=array();
foreach((array)$events as $event){
    if(($event['category']??'')==='school_profile' && in_array(($event['message']??''),array('School engagement migration is ready.','School engagement migration is not ready.','School engagement schema is not ready.'),true)){
        $safe_events[]=array('message'=>$event['message'],'outcome'=>$event['context']['outcome']??'','error_code'=>$event['context']['error_code']??'');
    }
}
$out=array('database'=>$wpdb->dbname,'version'=>defined('ZMP_VERSION')?ZMP_VERSION:'','student_id'=>$student_id,'baseline_hash'=>$manifest['baseline_hashes']['student'],'current_full_row_hash'=>$full_current_hash,'student_column_match'=>$field_matches,'synthetic_pin_input_still_valid'=>$pin_input_valid,
    'municipality_region_for_test'=>ZMP_Regions::region_for_municipality('Test')?:'unmapped','municipality_region_for_skopje'=>ZMP_Regions::region_for_municipality('Скопје')?:'unmapped',
    'current_schema_compatibility'=>array('schools'=>ZMP_Mentor_Schools::schema_is_compatible(),'profiles'=>ZMP_Mentor_School_Profile::schema_is_compatible(),'email_access'=>ZMP_School_Email_Access::schema_is_compatible()),
    'safe_school_migration_events'=>$safe_events,'wpdb_error_present'=>$wpdb->last_error!=='');
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
