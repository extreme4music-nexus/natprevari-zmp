<?php
/**
 * Focused synthetic WordPress regression for throttled school/profile/email schema recovery.
 * Run with WP-CLI eval-file against the disposable `.65` database only.
 */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb)) { fwrite(STDERR, "wordpress_required\n"); exit(2); }
if (DB_NAME !== 'zmp65_isolated' || $wpdb->prefix !== 'z65_') { fwrite(STDERR, "wrong_synthetic_database\n"); exit(3); }

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) { $assertions[$name] = $condition ? 'PASS' : 'FAIL'; };
$option_names = array('zmp_db_version', 'zmp_role_messenger_schema_retry', 'zmp_role_messenger_schema_retry_after', 'zmp_debug_events');
$option_snapshot = array();
$option_snapshot_queries_ok = true;
foreach ($option_names as $name) {
    $wpdb->last_error = '';
    $option_snapshot[$name] = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
    $option_snapshot_queries_ok = $option_snapshot_queries_ok && $wpdb->last_error === '';
}
if (!$option_snapshot_queries_ok) {
    // Never mutate retry/debug/schema options unless their exact initial state is known.
    fwrite(STDERR, "option_snapshot_failed_before_mutation\n");
    exit(4);
}
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$profile_table = ZMP_Mentor_School_Profile::table_name();
$email_requests = $wpdb->prefix . 'zmp_school_email_requests';
$email_events = $wpdb->prefix . 'zmp_school_email_request_events';
$mentor_table = $wpdb->prefix . 'zmp_mentors';
$count_tables = array($mentor_table, $school_table, $profile_table, $email_requests, $email_events);
$original_counts = null;
$fixture_users = array();
$fixture_rows = array('mentors'=>array(),'schools'=>array(),'profiles'=>array(),'requests'=>array());
$row_counts = static function () use ($wpdb, $count_tables) {
    $counts = array();
    foreach ($count_tables as $table) {
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($found !== $table) { $counts[$table] = null; continue; }
        $counts[$table] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `" . esc_sql($table) . "`");
    }
    return $counts;
};
$ready = static function () {
    return ZMP_Mentor_Schools::schema_is_compatible()
        && ZMP_Mentor_School_Profile::schema_is_compatible()
        && ZMP_School_Email_Access::schema_is_compatible();
};
$restore_options = static function () use ($wpdb, $option_names, $option_snapshot, $option_snapshot_queries_ok) {
    $all_restored = $option_snapshot_queries_ok;
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
        $wpdb->last_error = '';
        $restored = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM `{$wpdb->options}` WHERE option_name=%s LIMIT 1", $name), ARRAY_A);
        $readback_ok = $wpdb->last_error === '';
        if (is_array($old)) {
            $all_restored = $all_restored && $readback_ok && is_array($restored)
                && (string)$restored['option_value'] === (string)$old['option_value']
                && (string)$restored['autoload'] === (string)$old['autoload'];
        } else {
            $all_restored = $all_restored && $readback_ok && $restored === null;
        }
        wp_cache_delete($name, 'options');
    }
    wp_cache_delete('alloptions', 'options');
    return $all_restored;
};
$ensure_repaired = static function () use ($assert, $ready) {
    // Scoped restoration after each scenario, even when an assertion fails.
    ZMP_Mentor_Schools::install();
    ZMP_Mentor_School_Profile::install();
    ZMP_School_Email_Access::install();
    $assert($ready(), 'scenario_cleanup_schema_restored');
};
$run_case = static function ($prefix, $mutate_sql, $fail_closed_check) use ($wpdb, $assert, $ready, $row_counts, $ensure_repaired, $school_table) {
    $ensure_repaired();
    $baseline_counts = $row_counts();
    $wpdb->last_error = '';
    $changed = false !== $wpdb->query($mutate_sql);
    $assert($changed && !$ready(), $prefix . '_controlled_schema_drift_detected');
    if (!$changed) { return; }

    // Simulate a persisted retry intent whose five-minute deadline has not elapsed.
    update_option('zmp_db_version', ZMP_SCHEMA_VERSION, false);
    update_option('zmp_role_messenger_schema_retry', '1', false);
    update_option('zmp_role_messenger_schema_retry_after', (string) (time() + 240), false);
    zmp_competitions_init();
    $assert(!$ready(), $prefix . '_future_retry_deadline_preserves_fail_closed_schema');
    $fail_closed_check($assert, $prefix);

    // An ordinary request after the deadline should repair the schema.
    update_option('zmp_role_messenger_schema_retry_after', (string) (time() - 1), false);
    zmp_competitions_init();
    $assert($ready(), $prefix . '_expired_retry_deadline_repairs_all_school_schemas');
    $after_repair_counts = $row_counts();
    $assert($after_repair_counts === $baseline_counts, $prefix . '_repair_does_not_duplicate_or_create_migration_rows');

    // A subsequent request must be idempotent.
    zmp_competitions_init();
    $assert($ready() && $row_counts() === $baseline_counts, $prefix . '_repeat_request_is_idempotent');
    $duplicates = (int) $wpdb->get_var("SELECT COUNT(*) FROM (SELECT mentor_id FROM `" . esc_sql($school_table) . "` WHERE is_primary=1 GROUP BY mentor_id HAVING COUNT(*)>1) AS duplicate_primary_rows");
    $assert($duplicates === 0, $prefix . '_legacy_primary_school_rows_are_not_duplicated');
    $ensure_repaired();
};

try {
    ZMP_Mentor_Schools::install();
    ZMP_Mentor_School_Profile::install();
    ZMP_School_Email_Access::install();
    $assert($ready(), 'baseline_school_profile_email_schemas_ready');
    $assert(ZMP_Role_Messenger::schema_marker_is_current(), 'baseline_shared_schema_marker_is_current');
    // Capture the clean database baseline before adding synthetic requester rows.
    $original_counts = $row_counts();

    // Build one fully approved, synthetic disclosure so the missing-school-index
    // case proves that a valid address cannot pass through during cooldown.
    $token = wp_generate_password(12, false, false);
    $create_teacher = static function ($suffix) use (&$fixture_users, &$fixture_rows, $wpdb, $mentor_table, $school_table, $profile_table, $email_requests, $token) {
        $login = 'zmp65_schema_' . $token . '_' . $suffix;
        $user_id = wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(32,true,true),
            'user_email'=>$login.'@example.invalid','display_name'=>'Synthetic schema fixture','role'=>'zmp_teacher'));
        if (is_wp_error($user_id) || (int)$user_id < 1) { return 0; }
        $fixture_users[] = (int)$user_id;
        if (!ZMP_Mentor_School_Profile::is_teacher((int)$user_id)) { return 0; }
        $ok = $wpdb->insert($mentor_table,array('wp_user_id'=>(int)$user_id,'full_name'=>'Synthetic '.$suffix,
            'email'=>$login.'@example.invalid','phone'=>'','school_name'=>'Schema Fixture School','municipality'=>'Скопје',
            'region'=>'Скопски','login_token'=>wp_generate_password(32,false,false)));
        if (!$ok) { return 0; }
        $mentor_id = (int)$wpdb->insert_id;
        $fixture_rows['mentors'][] = $mentor_id;
        $school_key = ZMP_Mentor_Schools::school_key('Schema Fixture School','Скопје');
        $ok = $wpdb->insert($school_table,array('mentor_id'=>$mentor_id,'school_name'=>'Schema Fixture School',
            'municipality'=>'Скопје','region'=>'Скопски','region_is_override'=>0,'school_key'=>$school_key,
            'is_primary'=>1,'is_active'=>1,'engagement_epoch'=>1));
        if (!$ok) { return 0; }
        $school_id = (int)$wpdb->insert_id;
        $fixture_rows['schools'][] = $school_id;
        $email = $suffix === 'owner' ? 'schema-owner@example.invalid' : '';
        $now = current_time('mysql');
        $profile = array_merge(ZMP_Mentor_School_Profile::profile_defaults(),array('mentor_id'=>$mentor_id,
            'school_id'=>$school_id,'official_email'=>$email,'official_email_epoch'=>1,'created_at'=>$now,'updated_at'=>$now));
        if (!$wpdb->insert($profile_table,$profile)) { return 0; }
        $fixture_rows['profiles'][] = (int)$wpdb->insert_id;
        return array('user_id'=>(int)$user_id,'mentor_id'=>$mentor_id,'school_id'=>$school_id,'school_key'=>$school_key);
    };
    $requester = $create_teacher('requester');
    $owner = $create_teacher('owner');
    $fixture_ready = is_array($requester) && is_array($owner);
    if ($fixture_ready) {
        $fixture_reason = 'Synthetic schema retry test ' . $token;
        $inserted_request = $wpdb->insert($email_requests,array('requester_user_id'=>$requester['user_id'],'owner_user_id'=>$owner['user_id'],
            'mentor_id'=>$owner['mentor_id'],'school_id'=>$owner['school_id'],'requester_engagement_id'=>$requester['school_id'],
            'requester_engagement_epoch'=>1,'owner_engagement_id'=>$owner['school_id'],'owner_engagement_epoch'=>1,
            'school_key'=>$owner['school_key'],'reason'=>$fixture_reason,'status'=>'approved','pending_key'=>null,
            'approved_email_hash'=>hash('sha256','schema-owner@example.invalid'),'approved_email_epoch'=>1,
            'created_at'=>current_time('mysql'),'decided_by'=>1,'decided_at'=>current_time('mysql')));
        $fixture_request_id = 0;
        if ($inserted_request === 1) {
            $candidate_request_id = (int)$wpdb->insert_id;
            $verified_request_id = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$email_requests} WHERE id=%d AND requester_user_id=%d AND owner_user_id=%d AND reason=%s AND status='approved' LIMIT 1",
                $candidate_request_id,$requester['user_id'],$owner['user_id'],$fixture_reason
            ));
            if ($candidate_request_id > 0 && $verified_request_id === $candidate_request_id) {
                $fixture_request_id = $candidate_request_id;
                $fixture_rows['requests'][] = $fixture_request_id;
            } elseif ($candidate_request_id > 0) {
                // If readback failed, delete only the row matching this unique fixture identity.
                $wpdb->delete($email_requests,array('id'=>$candidate_request_id,'requester_user_id'=>$requester['user_id'],
                    'owner_user_id'=>$owner['user_id'],'reason'=>$fixture_reason),array('%d','%d','%d','%s'));
            }
        }
        $fixture_ready = $fixture_request_id > 0;
        if ($fixture_ready) {
            wp_set_current_user($requester['user_id']);
            $_POST = array('_wpnonce'=>wp_create_nonce('zmp_school_email_disclose_' . $fixture_request_id));
            $fixture_ready = is_user_logged_in() && wp_verify_nonce($_POST['_wpnonce'],'zmp_school_email_disclose_' . $fixture_request_id)
                && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$email_requests} WHERE id=%d AND status='approved'",$fixture_request_id))===1;
            $_POST = array();
            wp_set_current_user(0);
        }
    } else { $fixture_request_id = 0; }
    $assert($fixture_ready, 'synthetic_approved_disclosure_fixture_created');
    $run_case('school_missing_index', "ALTER TABLE `{$school_table}` DROP INDEX `mentor_active`", static function ($assert, $prefix) use ($fixture_ready, $fixture_request_id, $requester, $owner) {
        $profile = ZMP_Mentor_School_Profile::get_profile(1, 1);
        $assert(is_wp_error($profile) && $profile->get_error_code() === 'schema_not_ready', $prefix . '_profile_read_fails_closed_during_cooldown');
        $email_install = ZMP_School_Email_Access::install();
        $assert($email_install === false, $prefix . '_email_schema_install_fails_closed_on_profile_prerequisite');
        if (!$fixture_ready) { return; }
        wp_set_current_user($requester['user_id']);
        $contacts = ZMP_School_Email_Access::get_requestable_contacts($requester['user_id'],$requester['school_id']);
        $assert(is_wp_error($contacts) && $contacts->get_error_code()==='schema_not_ready', $prefix . '_email_contacts_fail_closed_during_cooldown');
        $requests = ZMP_School_Email_Access::requests_for_requester($requester['user_id']);
        $assert(is_wp_error($requests) && $requests->get_error_code()==='schema_not_ready', $prefix . '_request_status_fails_closed_during_cooldown');
        $_POST = array('_wpnonce'=>wp_create_nonce('zmp_school_email_disclose_' . $fixture_request_id));
        $disclosure = ZMP_School_Email_Access::disclose_approved_email($fixture_request_id,$requester['user_id']);
        $assert(is_wp_error($disclosure) && $disclosure->get_error_code()==='schema_not_ready', $prefix . '_approved_disclosure_fails_closed_during_cooldown');
        $assert(!is_string($disclosure) || $disclosure!=='schema-owner@example.invalid', $prefix . '_approved_email_never_disclosed_with_missing_prerequisite');
        $_POST = array();
        wp_set_current_user($owner['user_id']);
        $owner_requests = ZMP_School_Email_Access::submit_request($owner['user_id'],$requester['user_id'],$owner['school_id'],'Synthetic cooldown check');
        $assert(is_wp_error($owner_requests) && $owner_requests->get_error_code()==='schema_not_ready', $prefix . '_request_submit_fails_closed_during_cooldown');
        $decision = ZMP_School_Email_Access::decide($fixture_request_id,$owner['user_id'],'approved');
        $assert(is_wp_error($decision) && $decision->get_error_code()==='schema_not_ready', $prefix . '_decision_fails_closed_during_cooldown');
    });

    $run_case('profile_wrong_column_type', "ALTER TABLE `{$profile_table}` MODIFY `official_email_epoch` BIGINT(20) NOT NULL DEFAULT 1", static function ($assert, $prefix) {
        $profile = ZMP_Mentor_School_Profile::save_profile(1, 1, array());
        $assert(is_wp_error($profile) && $profile->get_error_code() === 'schema_not_ready', $prefix . '_profile_save_fails_closed_during_cooldown');
    });

    $run_case('email_missing_event_index', "ALTER TABLE `{$email_events}` DROP INDEX `request_event_time`", static function ($assert, $prefix) {
        $email = ZMP_School_Email_Access::get_requestable_contacts(1, 1);
        $assert(is_wp_error($email) && $email->get_error_code() === 'schema_not_ready', $prefix . '_email_contact_lookup_fails_closed_during_cooldown');
    });
} catch (Throwable $error) {
    $assertions['test_execution'] = 'FAIL';
    fwrite(STDERR, 'synthetic_schema_test_failed:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    // Restore every schema affected by this test, then restore exact option bytes/autoload state.
    ZMP_Mentor_Schools::install();
    ZMP_Mentor_School_Profile::install();
    ZMP_School_Email_Access::install();
    $assert($ready(), 'final_cleanup_restores_all_schema_checks');
    foreach ($fixture_rows['requests'] as $id) { $wpdb->delete($email_events,array('request_id'=>(int)$id),array('%d')); $wpdb->delete($email_requests,array('id'=>(int)$id),array('%d')); }
    foreach ($fixture_rows['profiles'] as $id) { $wpdb->delete($profile_table,array('id'=>(int)$id),array('%d')); }
    foreach ($fixture_rows['schools'] as $id) { $wpdb->delete($school_table,array('id'=>(int)$id),array('%d')); }
    foreach ($fixture_rows['mentors'] as $id) { $wpdb->delete($mentor_table,array('id'=>(int)$id),array('%d')); }
    foreach ($fixture_users as $id) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user((int)$id); }
    $assert($original_counts === null || $row_counts() === $original_counts, 'cleanup_removes_synthetic_disclosure_fixture');
    $assert($restore_options(), 'cleanup_restores_exact_option_existence_value_and_autoload_state');
    $assert(ZMP_Role_Messenger::schema_marker_is_current() === ($option_snapshot['zmp_db_version'] && $option_snapshot['zmp_db_version']['option_value'] === ZMP_SCHEMA_VERSION), 'cleanup_restores_original_shared_marker_state');
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
