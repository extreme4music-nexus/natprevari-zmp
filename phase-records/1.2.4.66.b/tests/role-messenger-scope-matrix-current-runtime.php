<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('isolated WordPress runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php'; global $wpdb;
$season=ZMP_Category_Registrations::active_season(); if(!$season||(int)$season->id!==1||get_option('zmp_regional_jury_migration_49_2')!=='complete'){WP_CLI::error('safe active-season/region precondition failed; no mutations made');} $sid=(int)$season->id; $category=2;
$tables=array('moderator'=>'zmp_role_messages','tester'=>'zmp_stage_testator_assignments','organizer'=>'zmp_organizer_assignments','jury'=>'zmp_jury_assignments','regional'=>'zmp_jury_regional_assignments');
$before=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}")); foreach($tables as $key=>$suffix){$before[$key]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$suffix}");}
$ids=array('moderator'=>0,'tester'=>0,'organizer'=>0,'commission'=>0,'president'=>0); $assignment_ids=array('tester'=>0,'organizer'=>0,'jury'=>0,'regional_commission'=>0,'regional_stacked_tester'=>0); $state=array('checks'=>array(),'exact_cleanup'=>false);
register_shutdown_function(static function()use(&$ids,&$assignment_ids,&$state,$wpdb,$before,$tables){foreach(array('regional_stacked_tester','regional_commission') as $key){if($assignment_ids[$key]){$wpdb->delete($wpdb->prefix.$tables['regional'],array('id'=>$assignment_ids[$key]),array('%d'));}} if($assignment_ids['jury']){$wpdb->delete($wpdb->prefix.$tables['jury'],array('id'=>$assignment_ids['jury']),array('%d'));} if($assignment_ids['organizer']){$wpdb->delete($wpdb->prefix.$tables['organizer'],array('id'=>$assignment_ids['organizer']),array('%d'));} if($assignment_ids['tester']){$wpdb->delete($wpdb->prefix.$tables['tester'],array('id'=>$assignment_ids['tester']),array('%d'));} foreach($ids as $id){if($id){wp_delete_user((int)$id);}} $after=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}")); foreach($tables as $key=>$suffix){$after[$key]=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$suffix}");} $state['exact_cleanup']=$after===$before; $state['all_assertions_pass']=$state['exact_cleanup']&&!in_array(false,$state['checks'],true); echo 'ZMP_ROLE_MESSENGER_SCOPE_MATRIX='.wp_json_encode($state).PHP_EOL;});
$stamp=gmdate('YmdHis').wp_generate_password(5,false,false); $make=static function($tag,$role)use($stamp){$u=wp_insert_user(array('user_login'=>'zmp_syn_msg_'.$tag.'_'.$stamp,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>'zmp_syn_msg_'.$tag.'_'.$stamp.'@example.invalid','display_name'=>'Synthetic Messenger '.$tag,'role'=>'subscriber')); if(is_wp_error($u)){return $u;} $user=new WP_User($u); $user->add_role('zmp_teacher'); $user->add_role($role); clean_user_cache($u); return (int)$u;};
foreach(array('moderator'=>'zmp_moderator','tester'=>'zmp_testator','organizer'=>'zmp_organizer','commission'=>'zmp_commission','president'=>'zmp_president') as $tag=>$role){$ids[$tag]=$make($tag,$role); if(is_wp_error($ids[$tag])){WP_CLI::error('synthetic role account creation failed');}}
// Subscriber + Teacher + Testator + Moderator + Commission stacked member.
$stacked=new WP_User($ids['tester']); $stacked->add_role('zmp_moderator'); $stacked->add_role('zmp_commission'); clean_user_cache($ids['tester']);
// Representative President also retains Subscriber, Teacher, and an unassigned Commission role.
$president=new WP_User($ids['president']); $president->add_role('zmp_commission'); clean_user_cache($ids['president']);
$insert=static function($table,$data)use($wpdb){$ok=$wpdb->insert($wpdb->prefix.$table,$data);return $ok?(int)$wpdb->insert_id:0;};
$assignment_ids['tester']=$insert('zmp_stage_testator_assignments',array('season_id'=>$sid,'category_id'=>$category,'phase'=>'municipal','testator_user_id'=>$ids['tester'],'assigned_by'=>1,'notes'=>'Synthetic local role-scope test only.','created_at'=>current_time('mysql')));
$assignment_ids['organizer']=$insert('zmp_organizer_assignments',array('season_id'=>$sid,'category_id'=>$category,'organizer_user_id'=>$ids['organizer'],'assigned_by'=>1,'assigned_at'=>current_time('mysql')));
$assignment_ids['jury']=$insert('zmp_jury_assignments',array('season_id'=>$sid,'category_id'=>$category,'juror_user_id'=>$ids['commission'],'assigned_by'=>1,'assigned_at'=>current_time('mysql')));
$assignment_ids['regional_commission']=$insert('zmp_jury_regional_assignments',array('season_id'=>$sid,'category_id'=>$category,'region'=>'skopje','juror_user_id'=>$ids['commission'],'assigned_by'=>1,'assigned_at'=>current_time('mysql')));
$assignment_ids['regional_stacked_tester']=$insert('zmp_jury_regional_assignments',array('season_id'=>$sid,'category_id'=>$category,'region'=>'skopje','juror_user_id'=>$ids['tester'],'assigned_by'=>1,'assigned_at'=>current_time('mysql')));
if(in_array(0,$assignment_ids,true)){WP_CLI::error('synthetic role assignment insert failed; shutdown cleanup will run');}
$check=static function($user,$scope,$season_id,$category_id,$region=''){wp_set_current_user((int)$user);return(bool)ZMP_Role_Messenger::can_access_room($scope,$season_id,$category_id,$region);};
$state['checks']=array(
'subscriber_teacher_present_on_all_synthetic_roles'=>array_reduce($ids,static function($ok,$id){$u=get_userdata((int)$id);return $ok&&$u&&in_array('subscriber',(array)$u->roles,true)&&in_array('zmp_teacher',(array)$u->roles,true);},true),
'moderator_room'=>$check($ids['moderator'],'moderator',0,0)===true,
'moderator_not_tester'=>$check($ids['moderator'],'tester',$sid,0)===false,
'stacked_tester_assigned_room'=>$check($ids['tester'],'tester',$sid,0)===true,
'stacked_moderator_room'=>$check($ids['tester'],'moderator',0,0)===true,
'stacked_regional_commission_room'=>$check($ids['tester'],'commission',$sid,$category,'skopje')===true,
'stacked_tester_not_organizer'=>$check($ids['tester'],'organizer',$sid,0)===false,
'stacked_regional_scope_not_national'=>$check($ids['tester'],'commission',$sid,$category,'')===false,
'stacked_regional_other_region_denied'=>$check($ids['tester'],'commission',$sid,$category,'pelagonija')===false,
'organizer_assigned_room'=>$check($ids['organizer'],'organizer',$sid,0)===true,
'organizer_not_commission'=>$check($ids['organizer'],'commission',$sid,$category)===false,
'commission_assigned_category'=>$check($ids['commission'],'commission',$sid,$category)===true,
'commission_other_category_denied'=>$check($ids['commission'],'commission',$sid,3)===false,
'commission_exact_region'=>$check($ids['commission'],'commission',$sid,$category,'skopje')===true,
'commission_other_region_denied'=>$check($ids['commission'],'commission',$sid,$category,'pelagonija')===false,
'president_has_no_bypass'=>($check($ids['president'],'moderator',0,0)===false&&$check($ids['president'],'commission',$sid,$category)===false),
'administrator_has_no_bypass'=>($check(1,'moderator',0,0)===false&&$check(1,'commission',$sid,$category)===false),
'ordinary_teacher_denied'=>($check(117,'moderator',0,0)===false&&$check(117,'commission',$sid,$category)===false)
);
