<?php
/**
 * Synthetic regression for lazy schema-drift detection and durable recovery intent.
 * Run with WP-CLI eval-file against the disposable `.65` database only.
 */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb)) { fwrite(STDERR, "wordpress_required\n"); exit(2); }
if (DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') { fwrite(STDERR, "wrong_synthetic_database\n"); exit(3); }

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) { $assertions[$name] = $condition ? 'PASS' : 'FAIL'; };
$option_names = array('zmp_db_version', 'zmp_role_messenger_schema_retry', 'zmp_role_messenger_schema_retry_after', 'zmp_role_retention_initialization_version', 'zmp_role_retention_policy', 'zmp_role_retention_last_status', 'cron', 'zmp_debug_events');
$option_snapshot = array();
foreach ($option_names as $name) {
    $option_snapshot[$name] = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
}
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$profile_table = $wpdb->prefix . 'zmp_mentor_school_profiles';
$email_table = $wpdb->prefix . 'zmp_school_email_requests';
$event_table = $wpdb->prefix . 'zmp_school_email_request_events';
$retention_audit_table = $wpdb->prefix . 'zmp_role_retention_audit';
$watched_tables = array($school_table, $profile_table, $email_table, $event_table, $retention_audit_table);
$metadata_query_count = static function ($callback) use ($wpdb, $watched_tables) {
    $count = 0;
    $watch = static function ($query) use (&$count, $watched_tables) {
        $schema_query = preg_match('/^\s*SHOW\s+(TABLES|COLUMNS|INDEX)\b/i', (string) $query)
            || preg_match('/\binformation_schema\.(?:TABLES|COLUMNS|STATISTICS)\b/i', (string) $query);
        if ($schema_query) {
            foreach ($watched_tables as $table) {
                if (strpos((string) $query, $table) !== false) { $count++; break; }
            }
        }
        return $query;
    };
    add_filter('query', $watch, 999);
    try { $callback(); } finally { remove_filter('query', $watch, 999); }
    return $count;
};
$ready = static function () {
    return ZMP_Mentor_Schools::schema_is_compatible()
        && ZMP_Mentor_School_Profile::schema_is_compatible()
        && ZMP_School_Email_Access::schema_is_compatible();
};
$registered_retention_init = null;
$init_hook = $GLOBALS['wp_filter']['init'] ?? null;
if ($init_hook instanceof WP_Hook && isset($init_hook->callbacks) && is_array($init_hook->callbacks)) {
    foreach ($init_hook->callbacks as $priority_callbacks) {
        foreach ($priority_callbacks as $callback_data) {
            $candidate = $callback_data['function'] ?? null;
            if (is_array($candidate) && count($candidate) === 2 && $candidate[0] === 'ZMP_Role_Messenger' && $candidate[1] === 'initialize_retention') {
                $registered_retention_init = $candidate;
                break 2;
            }
        }
    }
}
$ordinary_request = static function () use ($registered_retention_init) {
    zmp_competitions_init();
    return is_callable($registered_retention_init) ? call_user_func($registered_retention_init) : false;
};
$restore_options = static function () use ($wpdb, $option_names, $option_snapshot) {
    foreach ($option_names as $name) {
        $old = $option_snapshot[$name];
        if (is_array($old)) {
            $current = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name));
            if ($current === null) {
                $wpdb->insert($wpdb->options, array('option_name' => $name, 'option_value' => $old['option_value'], 'autoload' => $old['autoload']), array('%s', '%s', '%s'));
            } else {
                $wpdb->update($wpdb->options, array('option_value' => $old['option_value'], 'autoload' => $old['autoload']), array('option_name' => $name), array('%s', '%s'), array('%s'));
            }
        } else {
            $wpdb->delete($wpdb->options, array('option_name' => $name), array('%s'));
        }
        wp_cache_delete($name, 'options');
    }
    wp_cache_delete('alloptions', 'options');
};
$counts = static function () use ($wpdb, $school_table, $profile_table, $email_table, $event_table) {
    $result = array();
    foreach (array($school_table, $profile_table, $email_table, $event_table) as $table) {
        $result[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`");
    }
    return $result;
};

try {
    ZMP_Mentor_Schools::install();
    ZMP_Mentor_School_Profile::install();
    ZMP_School_Email_Access::install();
    $assert($ready(), 'baseline_school_profile_email_schemas_ready');
    $assert(ZMP_Role_Messenger::schema_marker_is_current(), 'baseline_shared_schema_marker_current');
    $assert(ZMP_Role_Messenger::schema_is_compatible(true), 'baseline_messenger_and_retention_schemas_ready');
    $assert(is_callable($registered_retention_init), 'retention_initializer_is_registered_on_real_init_hook');

    // Provide the documented healthy request state: valid retention policy,
    // scheduled retention event, and a completion marker for this schema version.
    if (!is_array(get_option('zmp_role_retention_policy', false))) {
        update_option('zmp_role_retention_policy', array('activated_at'=>current_time('mysql'), 'legacy_max_id'=>0), false);
    }
    if (!wp_next_scheduled('zmp_role_message_retention')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zmp_role_message_retention');
    }
    update_option('zmp_role_retention_initialization_version', ZMP_SCHEMA_VERSION, false);

    // Exercise both the ordinary plugin initializer and the exact callback
    // registered on WordPress's `init` hook in the same synthetic request.
    $healthy_retention_result = false;
    $healthy_queries = $metadata_query_count(static function () use ($ordinary_request, &$healthy_retention_result) { $healthy_retention_result = $ordinary_request(); });
    $assert($healthy_queries === 0, 'healthy_normal_request_and_registered_retention_init_do_not_scan_schema_metadata');
    $assert($healthy_retention_result === true, 'healthy_registered_retention_init_completes');

    // Missing completion state requires one fresh locked check and completion.
    delete_option('zmp_role_retention_initialization_version');
    $first_init_queries = $metadata_query_count(static function () use ($ordinary_request) { $ordinary_request(); });
    $assert($first_init_queries > 0, 'missing_retention_completion_marker_forces_schema_refresh');
    $assert(get_option('zmp_role_retention_initialization_version', '') === ZMP_SCHEMA_VERSION, 'successful_retention_initialization_writes_versioned_completion_marker');

    $baseline_counts = $counts();
    $wpdb->last_error = '';
    $dropped = false !== $wpdb->query("ALTER TABLE `{$school_table}` DROP INDEX `mentor_active`");
    $school_fixture_indexes = $wpdb->get_results("SHOW INDEX FROM `{$school_table}` WHERE Key_name='mentor_active'");
    $assert($dropped && is_array($school_fixture_indexes) && count($school_fixture_indexes) === 0 && $wpdb->last_error === '', 'controlled_missing_index_is_detected');
    if (!$dropped) { throw new RuntimeException('index_fixture_not_applied'); }
    $retention_index_dropped = false !== $wpdb->query("ALTER TABLE `{$retention_audit_table}` DROP INDEX `scope_time`");
    $retention_fixture_indexes = $wpdb->get_results("SHOW INDEX FROM `{$retention_audit_table}` WHERE Key_name='scope_time'");
    $assert($retention_index_dropped && is_array($retention_fixture_indexes) && count($retention_fixture_indexes) === 0 && $wpdb->last_error === '', 'controlled_retention_audit_index_drift_is_detected');
    if (!$retention_index_dropped) { throw new RuntimeException('retention_index_fixture_not_applied'); }

    // A healthy-marker ordinary request must stay cheap and must not prematurely create retry intent.
    update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
    delete_option('zmp_role_messenger_schema_retry');
    $retry_after = (string) (time() + 240);
    update_option('zmp_role_messenger_schema_retry_after', $retry_after, false);
    $drift_init_queries = $metadata_query_count(static function () use ($ordinary_request) { $ordinary_request(); });
    $retry_before_route = get_option('zmp_role_messenger_schema_retry', null);
    $remaining_school_indexes = $wpdb->get_results("SHOW INDEX FROM `{$school_table}` WHERE Key_name='mentor_active'");
    $remaining_retention_indexes = $wpdb->get_results("SHOW INDEX FROM `{$retention_audit_table}` WHERE Key_name='scope_time'");
    $assert($drift_init_queries === 0, 'ordinary_initializer_does_not_scan_schema_after_marker_current');
    $assert(is_array($remaining_school_indexes) && count($remaining_school_indexes) === 0 && is_array($remaining_retention_indexes) && count($remaining_retention_indexes) === 0 && $wpdb->last_error === '', 'ordinary_initializer_does_not_repair_out_of_band_schema_drift');
    $assert($retry_before_route === null, 'ordinary_initializer_does_not_mark_drift_before_readiness_check');

    // A real retention operation is allowed to inspect schema on demand; a
    // detected mismatch records the existing retry state and cooldown.
    $retention_operation_queries = $metadata_query_count(static function () { ZMP_Role_Messenger::run_retention(); });
    $retry_after = (string) get_option('zmp_role_messenger_schema_retry_after', '');
    $assert($retention_operation_queries > 0, 'actual_retention_operation_refreshes_schema_on_demand');
    $assert(get_option('zmp_role_messenger_schema_retry', null) === '1', 'retention_operation_detects_drift_and_persists_retry_intent');
    $assert(ctype_digit($retry_after) && (int) $retry_after > time(), 'retention_operation_defers_schema_retry_for_five_minutes');

    // The real profile service fails closed and must durably start the cooldown
    // when it is the first operation to discover drift, without an older retry
    // deadline left by the retention check.
    delete_option('zmp_role_messenger_schema_retry');
    delete_option('zmp_role_messenger_schema_retry_after');
    $profile_result = ZMP_Mentor_School_Profile::get_engagements(1);
    $retry_pending = get_option('zmp_role_messenger_schema_retry', null);
    $retry_after_after_route = (string) get_option('zmp_role_messenger_schema_retry_after', '');
    $assert(is_wp_error($profile_result) && $profile_result->get_error_code() === 'schema_not_ready', 'profile_readiness_route_fails_closed');
    $assert($retry_pending === '1', 'failed_readiness_check_persists_messenger_retry_intent');
    $assert(ctype_digit($retry_after_after_route) && (int) $retry_after_after_route > time() && (int) $retry_after_after_route <= time() + 300, 'first_failed_readiness_check_starts_five_minute_deadline');

    // Requests before the deadline do not repair, and they do not redo full schema introspection.
    $cooldown_queries = $metadata_query_count(static function () use ($ordinary_request) { $ordinary_request(); });
    $assert($cooldown_queries === 0, 'cooldown_initializer_skips_schema_metadata_queries');
    $assert(!$ready(), 'cooldown_initializer_remains_fail_closed');

    // A normal request after the stored deadline acquires the existing migration lock and repairs drift.
    update_option('zmp_role_messenger_schema_retry_after', (string) (time() - 1), false);
    $recovered_retention_result = $ordinary_request();
    $assert($ready(), 'expired_retry_deadline_repairs_school_profile_email_schemas');
    $assert(ZMP_Role_Messenger::schema_is_compatible(true), 'expired_retry_refreshes_and_repairs_retention_audit_schema');
    $assert(get_option('zmp_role_retention_initialization_version', '') === ZMP_SCHEMA_VERSION, 'successful_retry_migration_restores_versioned_retention_completion_marker');
    $assert($recovered_retention_result === true, 'retention_init_completes_after_expired_retry_migration');
    $assert($counts() === $baseline_counts, 'recovery_does_not_create_or_duplicate_migration_rows');

    $ordinary_request();
    $assert($ready() && $counts() === $baseline_counts, 'post_recovery_ordinary_request_is_idempotent');
} catch (Throwable $error) {
    $assertions['test_execution'] = 'FAIL';
    fwrite(STDERR, 'schema_trigger_test_failed:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    // Restore the controlled DDL defect even if a regression assertion or route check fails.
    ZMP_Mentor_Schools::install();
    ZMP_Mentor_School_Profile::install();
    ZMP_School_Email_Access::install();
    $assert($ready(), 'cleanup_restores_all_schema_checks');
    $assert(ZMP_Role_Messenger::schema_is_compatible(true), 'cleanup_restores_messenger_and_retention_schema');
    $restore_options();
    $options_restored_exactly = true;
    foreach ($option_names as $name) {
        $current = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
        $old = $option_snapshot[$name];
        if (is_array($old) ? $current !== $old : $current !== null) { $options_restored_exactly = false; }
    }
    $assert($options_restored_exactly, 'cleanup_restores_marker_retry_and_debug_options_exactly');
    $assert(ZMP_Role_Messenger::schema_marker_is_current() === (is_array($option_snapshot['zmp_db_version']) && $option_snapshot['zmp_db_version']['option_value'] === ZMP_SCHEMA_VERSION), 'cleanup_restores_original_marker_state');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
