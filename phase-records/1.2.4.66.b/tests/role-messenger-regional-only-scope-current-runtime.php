<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('isolated WordPress runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php'; global $wpdb;
$season = ZMP_Category_Registrations::active_season();
if (!$season || (int)$season->id !== 1 || get_option('zmp_regional_jury_migration_49_2') !== 'complete') { WP_CLI::error('safe active-season/regional migration precondition failed; no mutations made'); }
$sid = (int)$season->id; $category = 2; $region = 'skopje'; $table = $wpdb->prefix . 'zmp_jury_regional_assignments';
$before = array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),'regional_assignments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"));
$user_id = 0; $assignment_id = 0; $state = array('checks'=>array(),'exact_cleanup'=>false);
register_shutdown_function(static function() use (&$user_id,&$assignment_id,&$state,$wpdb,$table,$before) { if ($assignment_id) { $wpdb->delete($table,array('id'=>$assignment_id),array('%d')); } if ($user_id) { wp_delete_user((int)$user_id); } $after=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),'regional_assignments'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$table}`")); $state['exact_cleanup']=$after===$before; $state['all_assertions_pass']=$state['exact_cleanup']&&!in_array(false,$state['checks'],true); echo 'ZMP_MESSENGER_REGIONAL_ONLY_SCOPE=' . wp_json_encode($state) . PHP_EOL; });
$stamp=gmdate('YmdHis').wp_generate_password(5,false,false); $user_id=wp_insert_user(array('user_login'=>'zmp_syn_regmsg_'.$stamp,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>'zmp_syn_regmsg_'.$stamp.'@example.invalid','display_name'=>'Synthetic Regional Commission','role'=>'subscriber'));
if (is_wp_error($user_id)) { $user_id=0; WP_CLI::error('synthetic Subscriber creation failed'); }
$user_id=(int)$user_id; (new WP_User($user_id))->add_role('zmp_commission'); clean_user_cache($user_id);
$ok=$wpdb->insert($table,array('season_id'=>$sid,'category_id'=>$category,'region'=>$region,'juror_user_id'=>$user_id,'assigned_by'=>1,'assigned_at'=>current_time('mysql')));
if ($ok!==1) { WP_CLI::error('regional-only assignment insert failed; cleanup will run'); } $assignment_id=(int)$wpdb->insert_id;
wp_set_current_user($user_id);
$state['checks']=array(
 'regional_assignment_only'=>$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}zmp_jury_assignments` WHERE season_id=%d AND category_id=%d AND juror_user_id=%d",$sid,$category,$user_id))==0,
 'assigned_region_allowed'=>ZMP_Role_Messenger::can_access_room('commission',$sid,$category,$region)===true,
 'other_region_denied'=>ZMP_Role_Messenger::can_access_room('commission',$sid,$category,'pelagonija')===false,
 'unscoped_national_room_denied'=>ZMP_Role_Messenger::can_access_room('commission',$sid,$category,'')===false,
 'other_category_denied'=>ZMP_Role_Messenger::can_access_room('commission',$sid,3,$region)===false
);
