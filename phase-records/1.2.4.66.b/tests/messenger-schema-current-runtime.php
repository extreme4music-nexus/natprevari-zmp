<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('isolated WordPress runtime unavailable'); }
global $wpdb;
$suffixes = array('zmp_role_messages','zmp_role_message_reads','zmp_role_message_notifications','zmp_role_message_attachments','zmp_admin_message_review_requests','zmp_admin_message_review_audit','zmp_role_review_notices','zmp_role_retention_audit','zmp_role_retention_queue');
$before = array(); foreach ($suffixes as $suffix) { $table = $wpdb->prefix . $suffix; $before[$suffix] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"); }
$opts = array('zmp_db_version','zmp_role_messenger_schema_retry_pending','zmp_role_messenger_schema_retry_after','zmp_role_messenger_retention_initialized_version'); $option_before = array(); foreach ($opts as $name) { $option_before[$name] = get_option($name, null); }
$checks = array(
  'messenger_schema_compatible' => ZMP_Role_Messenger::schema_is_compatible(true) === true,
  'retention_audit_compatible' => ZMP_Role_Messenger::retention_audit_schema_is_compatible() === true,
  'schema_marker_current' => ZMP_Role_Messenger::schema_marker_is_current() === true,
  'retry_not_pending' => ZMP_Role_Messenger::schema_retry_pending() === false,
  'retry_storage_confirmed' => ZMP_Role_Messenger::schema_retry_storage_unconfirmed() === false,
);
$after = array(); foreach ($suffixes as $suffix) { $table = $wpdb->prefix . $suffix; $after[$suffix] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"); }
$option_after = array(); foreach ($opts as $name) { $option_after[$name] = get_option($name, null); }
$result = array('checks'=>$checks,'messenger_table_row_counts_unchanged'=>$before === $after,'schema_options_unchanged'=>$option_before === $option_after,'all_assertions_pass'=>!in_array(false,$checks,true)&&$before===$after&&$option_before===$option_after);
echo 'ZMP_MESSENGER_SCHEMA_READINESS=' . wp_json_encode($result) . PHP_EOL;
