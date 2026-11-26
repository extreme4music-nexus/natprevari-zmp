<?php
if (!defined('WP_CLI') || !class_exists('ZMP_President_Mandate') || !class_exists('ZMP_President_Mandate_Admin')) { WP_CLI::error('isolated President mandate runtime unavailable'); }
require_once ABSPATH.'wp-admin/includes/user.php'; global $wpdb; wp_set_current_user(1);
if (DB_NAME !== 'zmp66b_isolated' || !current_user_can('manage_options')) { WP_CLI::error('local admin preflight failed; no writes made'); }
$mandates=ZMP_President_Mandate::table_name(); $audit=ZMP_President_Mandate::audit_table_name();
$before=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),'mandates'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$mandates}`"),'audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$audit}`"));
$ids=array(); $state=array('checks'=>array(),'exact_cleanup'=>false);
register_shutdown_function(static function()use(&$ids,&$state,$wpdb,$before,$mandates,$audit){foreach($ids as $id){if(get_userdata((int)$id)){wp_delete_user((int)$id);}} $after=array('users'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),'mandates'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$mandates}`"),'audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$audit}`"));$state['exact_cleanup']=$after===$before;$state['all_assertions_pass']=$state['exact_cleanup']&&!in_array(false,$state['checks'],true);echo 'ZMP_PRESIDENT_TEACHER_ELIGIBILITY='.wp_json_encode($state).PHP_EOL;});
$stamp=substr(strtolower(str_replace('-','',wp_generate_uuid4())),0,8);
$make=static function($tag,$roles)use(&$ids,$stamp){$login='zmp_pres_'.$tag.'_'.$stamp;$id=wp_insert_user(array('user_login'=>$login,'user_email'=>$login.'@example.invalid','user_pass'=>wp_generate_password(40,true,true),'display_name'=>'Synthetic President eligibility '.$tag,'role'=>$roles[0]));if(is_wp_error($id)){return $id;} $id=(int)$id;$ids[]=$id;$u=new WP_User($id);foreach(array_slice($roles,1) as $role){$u->add_role($role);}clean_user_cache($id);return $id;};
$valid=$make('valid_stacked',array('zmp_teacher','subscriber','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'));
$invalid=$make('subscriber_without_teacher',array('subscriber'));
$stale=$make('stale_president',array('zmp_teacher','subscriber','zmp_president'));
if(is_wp_error($valid)||is_wp_error($invalid)||is_wp_error($stale)){WP_CLI::error('synthetic account creation failed; shutdown cleanup will run');}
$method=new ReflectionMethod('ZMP_President_Mandate_Admin','eligible_users');$method->setAccessible(true);$list=$method->invoke(null,999999);$listed=array_map(static function($user){return(int)$user->ID;},$list);
$state['checks']['stacked_teacher_subscriber_candidate_included']=in_array($valid,$listed,true);
$state['checks']['subscriber_without_teacher_candidate_excluded']=!in_array($invalid,$listed,true);
$state['checks']['stale_president_candidate_excluded']=!in_array($stale,$listed,true);
$state['checks']['administrator_excluded']=!in_array(1,$listed,true);
$current=ZMP_President_Mandate::current_mandate();$start=$current?gmdate('Y-m-d',strtotime($current->end_date.' +1 day')):gmdate('Y-m-d',strtotime('+5 years'));$end=ZMP_President_Mandate::term_end_date($start);
$result=ZMP_President_Mandate::appoint((int)$invalid,$start,$end,'Synthetic local eligibility denial check.');
$state['checks']['direct_appointment_requires_teacher']=is_wp_error($result)&&$result->get_error_code()==='president_account_ineligible';
$after_denial=array('mandates'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$mandates}`"),'audit'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `{$audit}`"));
$state['checks']['denial_no_mandate_or_audit_write']=$after_denial===array('mandates'=>$before['mandates'],'audit'=>$before['audit']);


$state['direct_appointment_error_code']=is_wp_error($result)?sanitize_key($result->get_error_code()):(is_bool($result)?('boolean_'.($result?'true':'false')):gettype($result));


