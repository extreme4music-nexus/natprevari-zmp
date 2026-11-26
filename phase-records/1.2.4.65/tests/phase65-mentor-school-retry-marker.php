<?php
/**
 * Focused synthetic regression: a failed add-school write must schedule the
 * shared five-minute schema retry when its required index is missing.
 * Run with WP-CLI eval-file against the disposable `.65` database only.
 */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb)) { fwrite(STDERR, "wordpress_required\n"); exit(2); }
if (DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') { fwrite(STDERR, "wrong_synthetic_database\n"); exit(3); }

$table = $wpdb->prefix . 'zmp_mentor_schools';
$option_names = array('zmp_db_version', 'zmp_role_messenger_schema_retry', 'zmp_role_messenger_schema_retry_after', 'zmp_debug_events');
$option_snapshot = array();
$snapshot_ok = true;
foreach ($option_names as $name) {
    $wpdb->last_error = '';
    $option_snapshot[$name] = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
    $snapshot_ok = $snapshot_ok && $wpdb->last_error === '';
}
if (!$snapshot_ok) { fwrite(STDERR, "option_snapshot_failed_before_mutation\n"); exit(4); }

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) { $assertions[$name] = $condition ? 'PASS' : 'FAIL'; };
$restore_options = static function () use ($wpdb, $option_names, $option_snapshot) {
    $all_restored = true;
    foreach ($option_names as $name) {
        $old = $option_snapshot[$name];
        $wpdb->last_error = '';
        $current = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name));
        $current_read_ok = $wpdb->last_error === '';
        $write_ok = $current_read_ok;
        if (is_array($old)) {
            if ($current_read_ok) {
                $wpdb->last_error = '';
                if ($current === null) {
                    $write_result = $wpdb->insert($wpdb->options, array('option_name'=>$name,'option_value'=>$old['option_value'],'autoload'=>$old['autoload']), array('%s','%s','%s'));
                } else {
                    $write_result = $wpdb->update($wpdb->options, array('option_value'=>$old['option_value'],'autoload'=>$old['autoload']), array('option_name'=>$name), array('%s','%s'), array('%s'));
                }
                $write_ok = $write_result !== false && $wpdb->last_error === '';
            }
        } else {
            if ($current_read_ok) {
                $wpdb->last_error = '';
                $write_result = $wpdb->delete($wpdb->options, array('option_name'=>$name), array('%s'));
                $write_ok = $write_result !== false && $wpdb->last_error === '';
            }
        }
        $wpdb->last_error = '';
        $restored = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
        $read_ok = $wpdb->last_error === '';
        if (is_array($old)) {
            $all_restored = $all_restored && $write_ok && $read_ok && is_array($restored)
                && (string)$restored['option_value'] === (string)$old['option_value']
                && (string)$restored['autoload'] === (string)$old['autoload'];
        } else {
            $all_restored = $all_restored && $write_ok && $read_ok && $restored === null;
        }
        wp_cache_delete($name, 'options');
    }
    wp_cache_delete('alloptions', 'options');
    return $all_restored;
};

$school_name = 'Synthetic Retry Marker ' . wp_generate_password(12, false, false);
$school_key = ZMP_Mentor_Schools::school_key($school_name, 'Скопје');
$changed = false;
$original_schema_ready = false;
$original_count = null;
$fixture_absent_before = false;
try {
    // This readiness check is inside try so its retry-option side effect, if the
    // database is already unhealthy, is also restored from the exact snapshot.
    $original_schema_ready = ZMP_Mentor_Schools::schema_is_compatible();
    $wpdb->last_error = '';
    $original_count_raw = $original_schema_ready ? $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`") : null;
    $count_read_ok = $original_schema_ready && $wpdb->last_error === '' && $original_count_raw !== null;
    $original_count = $count_read_ok ? (int)$original_count_raw : null;
    $wpdb->last_error = '';
    $fixture_count_before = $count_read_ok ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `" . esc_sql($table) . "` WHERE mentor_id=%d AND school_key=%s", 2147483000, $school_key)) : null;
    $fixture_count_read_ok = $count_read_ok && $wpdb->last_error === '' && $fixture_count_before !== null;
    $fixture_absent_before = $fixture_count_read_ok && (int)$fixture_count_before === 0;
    if (!$original_schema_ready || !$count_read_ok) {
        $assert(false, 'baseline_school_schema_ready_and_count_readable');
    } else {
        $assert(true, 'baseline_school_schema_ready_and_count_readable');
        $assert($fixture_count_read_ok && $fixture_absent_before, 'unique_school_fixture_key_absent_before_mutation');
    }
    if ($original_schema_ready && $count_read_ok && $fixture_count_read_ok && $fixture_absent_before) {
        // Model a current shared schema marker with no pending retry, then drift.
        update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
        delete_option('zmp_role_messenger_schema_retry');
        delete_option('zmp_role_messenger_schema_retry_after');
        $wpdb->last_error = '';
        $changed = false !== $wpdb->query("ALTER TABLE `" . esc_sql($table) . "` DROP INDEX `mentor_active`");
        $assert($changed, 'controlled_missing_index_created');
        if ($changed) {
            $result = ZMP_Mentor_Schools::add_school(2147483000, $school_name, 'Скопје', false);
            $assert(is_wp_error($result) && $result->get_error_code() === 'school_schema_not_ready', 'add_school_fails_closed_on_schema_drift');
            $assert(ZMP_Role_Messenger::schema_retry_pending(), 'add_school_persists_retry_intent');
            $deadline = get_option('zmp_role_messenger_schema_retry_after', '');
            $deadline_valid = is_scalar($deadline) && ctype_digit((string)$deadline)
                && (int)$deadline >= time() + 240 && (int)$deadline <= time() + 300;
            $assert($deadline_valid, 'add_school_sets_five_minute_retry_deadline');
            $assert(!ZMP_Role_Messenger::schema_retry_allowed(), 'add_school_retry_is_throttled_until_deadline');
            $wpdb->last_error = '';
            $written = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `" . esc_sql($table) . "` WHERE mentor_id=%d AND school_key=%s", 2147483000, $school_key));
            $specific_read_ok = $wpdb->last_error === '' && $written !== null;
            $wpdb->last_error = '';
            $after_count = $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`");
            $total_read_ok = $wpdb->last_error === '' && $after_count !== null;
            $assert($specific_read_ok && $total_read_ok && (int)$written === 0 && (int)$after_count === $original_count, 'add_school_drift_does_not_write_school_row');
        }
    }
} catch (Throwable $error) {
    $assertions['execution'] = 'FAIL';
    fwrite(STDERR, 'synthetic_school_retry_test_failed:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    try {
        if ($changed && $fixture_absent_before) {
            $repaired = ZMP_Mentor_Schools::install();
            $assert($repaired && ZMP_Mentor_Schools::schema_is_compatible(), 'cleanup_restores_school_schema');
            $wpdb->last_error = '';
            $delete_result = $wpdb->delete($table, array('mentor_id'=>2147483000,'school_key'=>$school_key), array('%d','%s'));
            $delete_ok = $delete_result !== false && $wpdb->last_error === '';
            $assert($delete_ok, 'cleanup_deletes_only_unique_school_fixture_without_sql_error');
            $wpdb->last_error = '';
            $remaining_fixture = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `" . esc_sql($table) . "` WHERE mentor_id=%d AND school_key=%s", 2147483000, $school_key));
            $specific_read_ok = $wpdb->last_error === '' && $remaining_fixture !== null;
            $wpdb->last_error = '';
            $after_count = $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`");
            $total_read_ok = $wpdb->last_error === '' && $after_count !== null;
            $assert($specific_read_ok && (int)$remaining_fixture === 0, 'cleanup_readback_confirms_test_fixture_absent');
            $assert($total_read_ok && ($original_count === null || (int)$after_count === $original_count), 'cleanup_restores_original_school_row_count');
        }
    } catch (Throwable $error) {
        $assertions['cleanup_sql'] = 'FAIL';
        fwrite(STDERR, 'synthetic_school_retry_cleanup_failed:' . sanitize_key($error->getMessage()) . PHP_EOL);
    } finally {
        $assert($restore_options(), 'cleanup_restores_exact_option_existence_value_and_autoload_state');
    }
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
