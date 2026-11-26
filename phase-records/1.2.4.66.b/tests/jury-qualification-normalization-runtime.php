<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Category_Jury')) { WP_CLI::error('isolated WordPress runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$jury='ZMP_Category_Jury';$active=new ReflectionMethod($jury,'active_season');$active->setAccessible(true);$season=$active->invoke(null);$category_id=2;$norm=new ReflectionMethod($jury,'normalize_qualified_next_stage');$norm->setAccessible(true);$steps=new ReflectionMethod($jury,'next_stage_exists');$steps->setAccessible(true);
if(!$season||(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d AND is_enabled=1",$season->id,$category_id))!==1)WP_CLI::error('safe active season/category prerequisite failed; no changes made');
$cases=array(
 'absent_is_false'=>!is_wp_error($norm->invoke(null,(int)$season->id,$category_id,'regional',null,false))&&$norm->invoke(null,(int)$season->id,$category_id,'regional',null,false)===0,
 'zero_is_false'=>$norm->invoke(null,(int)$season->id,$category_id,'regional','0',true)===0,
 'one_with_next_stage_is_true'=>$steps->invoke(null,(int)$season->id,$category_id,'regional')&&$norm->invoke(null,(int)$season->id,$category_id,'regional','1',true)===1,
 'on_is_rejected'=>is_wp_error($norm->invoke(null,(int)$season->id,$category_id,'regional','on',true)),
 'other_value_is_rejected'=>is_wp_error($norm->invoke(null,(int)$season->id,$category_id,'regional','2',true)),
 'one_without_next_stage_is_rejected'=>!$steps->invoke(null,(int)$season->id,$category_id,'state')&&is_wp_error($norm->invoke(null,(int)$season->id,$category_id,'state','1',true)),
);
$results=$wpdb->prefix.'zmp_category_final_results';$audit=$wpdb->prefix.'zmp_jury_audit';$before=array('results'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $results"),'audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $audit"));
$suffix=gmdate('YmdHis').wp_generate_password(5,false,false);$login='zmp_synthetic_jury_norm_'.$suffix;$user=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(48,true,true),'user_email'=>$login.'@example.invalid','display_name'=>'Synthetic Jury Normalization Tester','role'=>'administrator'));if(is_wp_error($user))WP_CLI::error('synthetic test administrator creation failed');
$redirect='';add_filter('wp_redirect',static function($location)use(&$redirect){$redirect=(string)$location;return $location;},1,1);wp_set_current_user($user);
register_shutdown_function(static function()use(&$cases,&$redirect,$wpdb,$results,$audit,$before,$user){$after=array('results'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $results"),'audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $audit"));$no_mutation=$after===$before;$invalid_redirect=strpos($redirect,'qualification_invalid')!==false;wp_delete_user((int)$user);$removed=!get_userdata((int)$user);echo 'ZMP_JURY_QUALIFICATION_NORMALIZATION='.wp_json_encode(array('unit_cases'=>$cases,'all_unit_cases_pass'=>!in_array(false,$cases,true),'actual_post_rejected_before_write'=>$invalid_redirect&&$no_mutation,'result_and_audit_counts_unchanged'=>$no_mutation,'synthetic_user_removed'=>$removed)).PHP_EOL;});
$_POST=array('_wpnonce'=>wp_create_nonce('zmp_category_jury'),'zmp_jury_action'=>'finalize_result','category_id'=>(string)$category_id,'entry_id'=>'1','phase'=>'regional','qualified_next_stage'=>'on','decision_basis'=>str_repeat('Synthetic factual basis. ',2),'decision_note'=>str_repeat('Synthetic fair decision rationale. ',2));$_REQUEST=$_POST;ZMP_Category_Jury::handle_post();WP_CLI::error('invalid qualification unexpectedly returned from handler');
