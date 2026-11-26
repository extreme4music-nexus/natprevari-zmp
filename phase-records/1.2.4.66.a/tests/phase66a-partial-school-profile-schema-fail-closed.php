<?php
/** Controlled partial-schema fail-closed check; restores the exact index/options afterward. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only.\n"); exit(2); }
global $wpdb;
$root='/home/tonic/zmp65-review-20261124/site-66a-subject-test';
$table=$wpdb->prefix.'zmp_mentor_school_profiles';
if (!defined('DB_NAME') || DB_NAME!=='zmp65_subject_test' || $wpdb->prefix!=='z66a_'
    || rtrim((string)realpath(ABSPATH),'/')!==$root || rtrim(home_url('/'),'/')!=='http://127.0.0.1:8100'
    || $table!=='z66a_zmp_mentor_school_profiles') {
    fwrite(STDERR,"BLOCKED: unexpected site/database identity; writes=0\n"); exit(2);
}
$rows=$wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name='school_id'");
if (!is_array($rows)||count($rows)!==1||(string)$rows[0]->Column_name!=='school_id'||(int)$rows[0]->Non_unique!==1) {
    fwrite(STDERR,"BLOCKED: required baseline index shape is not exact; writes=0\n"); exit(2);
}
$missing=new stdClass();
$retry_keys=array(ZMP_Role_Messenger::SCHEMA_RETRY_OPTION,'zmp_role_messenger_schema_retry_after');
$saved=array('debug'=>get_option('zmp_debug_events',$missing));
foreach($retry_keys as $key){$saved[$key]=get_option($key,$missing);}
$checks=array();$assert=static function($name,$ok)use(&$checks){$checks[$name]=$ok?'PASS':'FAIL';};
$dropped=false;
try {
    $dropped=$wpdb->query("ALTER TABLE `{$table}` DROP INDEX `school_id`")!==false;
    $assert('synthetic-index-removal', $dropped);
    $compatible=ZMP_Mentor_School_Profile::schema_is_compatible();
    $assert('partial-profile-schema-rejected', !$compatible);
    $result=ZMP_Mentor_School_Profile::get_engagements(0);
    $assert('profile-operation-fails-closed-with-schema-not-ready', is_wp_error($result)&&$result->get_error_code()==='schema_not_ready');
} catch(Throwable $e) {
    fwrite(STDERR,'TEST EXCEPTION: '.sanitize_key($e->getMessage())."\n");$checks['controlled-failure-path']='FAIL';
} finally {
    $current=$wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name='school_id'");
    if ((!is_array($current)||count($current)===0)&&$dropped) {$wpdb->query("ALTER TABLE `{$table}` ADD KEY `school_id` (`school_id`)");}
    foreach($saved as $key=>$value){if($value===$missing){delete_option($key);}else{update_option($key,$value,false);}}
    wp_cache_delete('alloptions','options');
}
$restored=$wpdb->get_results("SHOW INDEX FROM `{$table}` WHERE Key_name='school_id'");
$assert('index-and-option-state-restored', is_array($restored)&&count($restored)===1
    && (string)$restored[0]->Column_name==='school_id'&&(int)$restored[0]->Non_unique===1
    && ZMP_Mentor_School_Profile::schema_is_compatible());
foreach($checks as $name=>$status){echo $status.' '.$name."\n";}
$passed=count(array_filter($checks,static function($s){return $s==='PASS';}));
echo 'SUMMARY '.$passed.'/'.count($checks)." PASS\n";
exit($passed===count($checks)?0:1);
