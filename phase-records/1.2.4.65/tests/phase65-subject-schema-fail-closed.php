<?php
/**
 * Bounded, synthetic-only subject queue schema acceptance check.
 *
 * Run as two separate fresh WP-CLI processes against the disposable
 * zmp65_subject_schema_test site (prefix z65q_, URL 127.0.0.1:8098):
 *   ZMP65_SUBJECT_SCHEMA_TEST_MODE=healthy wp eval-file tests/phase65-subject-schema-fail-closed.php
 *   ZMP65_SUBJECT_SCHEMA_TEST_MODE=drift   wp eval-file tests/phase65-subject-schema-fail-closed.php
 * Never run against production, the persistent clone, or an active phase site.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_schema_test') {
    fwrite(STDERR, "BLOCKED: dedicated disposable WP-CLI site required.\n");
    exit(2);
}
global $wpdb;
$site = wp_parse_url(home_url('/'));
$mode = getenv('ZMP65_SUBJECT_SCHEMA_TEST_MODE');
if (!isset($wpdb) || $wpdb->prefix !== 'z65q_'
    || !is_array($site) || ($site['scheme'] ?? '') !== 'http'
    || ($site['host'] ?? '') !== '127.0.0.1' || (int) ($site['port'] ?? 0) !== 8098
    || !in_array($mode, array('healthy','drift'), true)) {
    fwrite(STDERR, "BLOCKED: isolated site identity or explicit test mode mismatch.\n");
    exit(2);
}
foreach (array('ZMP_Mentor_Subject_Proposals','ZMP_President_Subject_Approvals','ZMP_President_Mandate','ZMP_Debug') as $class) {
    if (!class_exists($class)) { fwrite(STDERR, "BLOCKED: required product service unavailable.\n"); exit(2); }
}
$source_hashes = array(
    'ZMP_Mentor_Subject_Proposals'=>'CCECC3DB93A13CDDF688807BC2C3D128FC8404B122A35ACD5E018BC9F6DC77E2',
    'ZMP_President_Subject_Approvals'=>'1C120C1A3452404D6E4CB844457DC3C563431645DA32B7E4356CFF804371F5A4',
);
foreach ($source_hashes as $class=>$expected_hash) {
    $source_file = (new ReflectionClass($class))->getFileName();
    $actual_hash = is_string($source_file) && is_file($source_file) ? hash_file('sha256',$source_file) : false;
    if (!is_string($actual_hash) || !hash_equals($expected_hash,strtoupper($actual_hash))) {
        fwrite(STDERR, "BLOCKED: loaded product source does not match the reviewed candidate.\n"); exit(2);
    }
}
$admin_ids = get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$user_totals = count_users();
if (count((array) $admin_ids) !== 1 || (int) ($user_totals['total_users'] ?? -1) !== 1) {
    fwrite(STDERR, "BLOCKED: synthetic disposable-site administrator unavailable.\n"); exit(2);
}
$admin_id = (int) $admin_ids[0];
$proposal_table = $wpdb->prefix . 'zmp_subject_proposals';
$events_table = $wpdb->prefix . 'zmp_subject_proposal_events';
$catalog_table = $wpdb->prefix . 'zmp_subject_catalog';
$aliases_table = $wpdb->prefix . 'zmp_subject_aliases';
$state_option = 'zmp65_subject_schema_acceptance_state';
$debug_option = ZMP_Debug::OPTION;
$assertions = 0;
$failures = array();
$check = static function ($ok, $name) use (&$assertions, &$failures) {
    $assertions++;
    if (!$ok) { $failures[] = $name; }
};
$count_rows = static function ($table, $where = '', $values = array()) use ($wpdb) {
    $sql = 'SELECT COUNT(*) FROM `' . esc_sql($table) . '`' . $where;
    $value = $wpdb->get_var($values ? $wpdb->prepare($sql, $values) : $sql);
    return $wpdb->last_error === '' && is_numeric($value) ? (int) $value : null;
};
$schema_error = static function ($result) {
    if (!is_wp_error($result) || $result->get_error_code() !== 'schema_not_ready') { return false; }
    foreach ($result->get_error_messages('schema_not_ready') as $message) {
        if (!is_string($message) || preg_match('/\b(SELECT|SHOW|CREATE|ALTER|DROP)\b|z65q_zmp_/i', $message)) { return false; }
    }
    return true;
};
$capture = static function ($callback) {
    ob_start();
    try { call_user_func($callback); return (string) ob_get_contents(); }
    finally { ob_end_clean(); }
};
$http_count = 0;
$mail_count = 0;
$block_http = static function ($preempt) use (&$http_count) { $http_count++; return new WP_Error('zmp65_local_http_blocked'); };
$block_mail = static function ($preempt) use (&$mail_count) { $mail_count++; return false; };
add_filter('pre_http_request', $block_http, PHP_INT_MAX, 1);
add_filter('pre_wp_mail', $block_mail, PHP_INT_MAX, 1);
$original_user = get_current_user_id();
$index_dropped = false;
$healthy_completed = false;
$state = get_option($state_option, array());
$teacher_id = (int) ($state['teacher_id'] ?? 0);
$president_id = (int) ($state['president_id'] ?? 0);
$mandate_id = (int) ($state['mandate_id'] ?? 0);
$canonical_id = (int) ($state['canonical_id'] ?? 0);
$proposal_ids = array_map('intval', (array) ($state['proposal_ids'] ?? array()));
$suffix = (string) ($state['suffix'] ?? '');
$proposal_names = (array) ($state['proposal_names'] ?? array());
$debug_before = $state['debug_before'] ?? null;

try {
    if ($mode === 'healthy') {
        if ($state || !ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
            throw new RuntimeException('fresh healthy baseline required');
        }
        foreach (array($proposal_table,$events_table,$catalog_table,$aliases_table,
            ZMP_President_Mandate::table_name(),ZMP_President_Mandate::audit_table_name()) as $table) {
            if ($count_rows($table) !== 0) { throw new RuntimeException('disposable baseline tables must be empty'); }
        }
        $indexes = $wpdb->get_results("SHOW INDEX FROM `" . esc_sql($catalog_table) . "` WHERE Key_name='subject_status_active'");
        $cols = array();
        foreach ((array) $indexes as $row) {
            if ((int) ($row->Non_unique ?? 0) !== 1) { throw new RuntimeException('proposal index shape mismatch'); }
            $cols[(int) ($row->Seq_in_index ?? 0)] = (string) ($row->Column_name ?? '');
        }
        ksort($cols);
        if (array_values($cols) !== array('status','is_active')) { throw new RuntimeException('catalog index missing'); }
        $debug_row = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s", $debug_option), ARRAY_A);
        if ($wpdb->last_error !== '') { throw new RuntimeException('diagnostic baseline unreadable'); }
        $debug_before = is_array($debug_row) ? array('existed'=>true,'row'=>$debug_row) : array('existed'=>false);
        $suffix = strtolower(wp_generate_password(12, false, false));
        $teacher_login = 'zmp65_schema_teacher_' . $suffix;
        $president_login = 'zmp65_schema_president_' . $suffix;
        $teacher_created = wp_insert_user(array('user_login'=>$teacher_login,'user_pass'=>wp_generate_password(32,true,true),
            'user_email'=>$teacher_login . '@example.invalid','display_name'=>'Synthetic schema Teacher','role'=>'zmp_teacher'));
        $president_created = wp_insert_user(array('user_login'=>$president_login,'user_pass'=>wp_generate_password(32,true,true),
            'user_email'=>$president_login . '@example.invalid','display_name'=>'Synthetic schema President','role'=>'subscriber'));
        $teacher_id = is_wp_error($teacher_created) ? 0 : (int)$teacher_created;
        $president_id = is_wp_error($president_created) ? 0 : (int)$president_created;
        if (!$teacher_id || !$president_id) {
            throw new RuntimeException('synthetic users could not be created');
        }
        $start = current_time('Y-m-d');
        $end = ZMP_President_Mandate::term_end_date($start);
        wp_set_current_user($admin_id);
        $mandate_created = ZMP_President_Mandate::appoint($president_id,$start,$end,'Synthetic subject-schema test');
        $mandate_id = is_wp_error($mandate_created) ? 0 : (int)$mandate_created;
        if (!$mandate_id) { throw new RuntimeException('synthetic President mandate failed'); }
        $proposal_names = array('Synthetic schema queue ' . $suffix,'Synthetic schema decision ' . $suffix,'Synthetic schema alias query ' . $suffix);
        wp_set_current_user($teacher_id);
        foreach ($proposal_names as $name) {
            $id = ZMP_Mentor_Subject_Proposals::submit($teacher_id,$name);
            if (is_wp_error($id) || (int)$id < 1) { throw new RuntimeException('healthy proposal submission failed'); }
            $proposal_ids[] = (int)$id;
        }
        $check(ZMP_Mentor_Subject_Proposals::schema_is_compatible(), 'healthy_schema_compatible');
        $pending = ZMP_Mentor_Subject_Proposals::pending_count();
        $check(!is_wp_error($pending) && (int)$pending === 3, 'healthy_pending_count');
        $matches = ZMP_Mentor_Subject_Proposals::find_normalized_matches($proposal_names[0]);
        $check(is_array($matches) && is_array($matches['canonical'] ?? null) && is_array($matches['aliases'] ?? null), 'healthy_catalog_match_query_shape');
        wp_set_current_user($president_id);
        $check(ZMP_President_Subject_Approvals::can_review(), 'active_synthetic_president_can_review');
        $queue = $capture(array('ZMP_President_Subject_Approvals','render'));
        $check(strpos($queue,$proposal_names[0]) !== false && strpos($queue,$proposal_names[1]) !== false
            && strpos($queue,$proposal_names[2]) !== false, 'healthy_queue_renders_pending_items');
        $check(strpos($queue,'Редицата за предлози моментално не е достапна поради проблем со шемата.') === false, 'healthy_queue_not_unavailable');
        $canonical_name = 'Synthetic canonical subject ' . $suffix;
        $canonical_created = ZMP_Mentor_Subjects::create_approved_subject_for_president($canonical_name,$president_id);
        $canonical_id = is_wp_error($canonical_created) ? 0 : (int)$canonical_created;
        if (!$canonical_id) { throw new RuntimeException('synthetic canonical subject creation failed'); }
        $event_count_before_query_failure = $count_rows($events_table);
        $alias_count_before_query_failure = $count_rows($aliases_table);
        $catalog_count_before_query_failure = $count_rows($catalog_table);
        $query_failure_injected = 0;
        $inject_alias_query_error = static function ($query) use (&$query_failure_injected,$aliases_table) {
            if (is_string($query) && strpos($query,'SELECT id,canonical_subject_id FROM ' . $aliases_table . ' WHERE alias_key=') === 0) {
                $query_failure_injected++;
                return preg_replace('/^SELECT id,canonical_subject_id/', 'SELECT zmp65_forced_missing_column,id,canonical_subject_id', $query, 1);
            }
            return $query;
        };
        add_filter('query',$inject_alias_query_error,PHP_INT_MAX,1);
        wp_set_current_user($president_id);
        $query_error_decision = ZMP_Mentor_Subject_Proposals::decide($president_id,$proposal_ids[2],'approve_alias',$canonical_id);
        remove_filter('query',$inject_alias_query_error,PHP_INT_MAX);
        $check($query_failure_injected === 1, 'alias_query_failure_injected_once_after_preflight');
        $check($schema_error($query_error_decision), 'mid_transaction_alias_query_error_returns_schema_not_ready');
        $query_error_proposal = $wpdb->get_row($wpdb->prepare("SELECT status,decided_by FROM {$proposal_table} WHERE id=%d",$proposal_ids[2]),ARRAY_A);
        $check(is_array($query_error_proposal) && $query_error_proposal['status']==='pending' && (int)$query_error_proposal['decided_by']===0,
            'alias_query_error_rolls_back_proposal_decision');
        $check($count_rows($events_table)===$event_count_before_query_failure
            && $count_rows($aliases_table)===$alias_count_before_query_failure
            && $count_rows($catalog_table)===$catalog_count_before_query_failure,
            'alias_query_error_rolls_back_alias_event_and_catalog_changes');
        $state = array('teacher_id'=>$teacher_id,'president_id'=>$president_id,'mandate_id'=>$mandate_id,'canonical_id'=>$canonical_id,
            'proposal_ids'=>$proposal_ids,'proposal_names'=>$proposal_names,'suffix'=>$suffix,'debug_before'=>$debug_before);
        update_option($state_option,$state,false);
        if ($wpdb->last_error !== '') { throw new RuntimeException('test state could not be persisted'); }
        if ($http_count !== 0 || $mail_count !== 0) { throw new RuntimeException('unexpected external operation attempted'); }
        if ($failures) { throw new RuntimeException('healthy acceptance assertion failed'); }
        $healthy_completed = true;
        fwrite(STDOUT,"PASS: healthy synthetic queue behavior; run drift mode in a fresh WP-CLI process.\n");
        exit(0);
    }

    if (!$teacher_id || !$president_id || !$mandate_id || !$canonical_id || count($proposal_ids) !== 3
        || !is_array($debug_before) || !array_key_exists('existed',$debug_before)) { throw new RuntimeException('healthy-mode fixture state missing'); }
    $index_rows = $wpdb->get_results("SHOW INDEX FROM `" . esc_sql($catalog_table) . "` WHERE Key_name='subject_status_active'");
    if ($wpdb->last_error !== '' || count((array)$index_rows) !== 2) { throw new RuntimeException('expected index not present before drift'); }
    if ($wpdb->query("ALTER TABLE `" . esc_sql($catalog_table) . "` DROP INDEX `subject_status_active`") === false) {
        throw new RuntimeException('required catalog index could not be dropped');
    }
    $index_dropped = true;
    $check(!ZMP_Mentor_Subject_Proposals::schema_is_compatible(), 'missing_required_index_detected');
    wp_set_current_user($teacher_id);
    $submit = ZMP_Mentor_Subject_Proposals::submit($teacher_id,'Synthetic forbidden ' . $suffix);
    $check($schema_error($submit), 'submit_fails_closed');
    wp_set_current_user($president_id);
    $decide = ZMP_Mentor_Subject_Proposals::decide($president_id,$proposal_ids[0],'reject');
    $check($schema_error($decide), 'decision_fails_closed');
    $pending = ZMP_Mentor_Subject_Proposals::pending_count();
    $check($schema_error($pending), 'count_fails_closed');
    $matches = ZMP_Mentor_Subject_Proposals::find_normalized_matches($proposal_names[0]);
    $check($schema_error($matches), 'canonical_match_lookup_fails_closed');
    $queue = $capture(array('ZMP_President_Subject_Approvals','render'));
    $unavailable = 'Редицата за предлози моментално не е достапна поради проблем со шемата.';
    $check(strpos($queue,$unavailable) !== false, 'queue_shows_generic_unavailable_notice');
    $check(strpos($queue,'Нема предлози што чекаат одлука.') === false, 'queue_does_not_claim_empty');
    $check(strpos($queue,'Предлози што чекаат одлука: 0') === false, 'queue_does_not_claim_zero');
    $dashboard = $capture(array('ZMP_President_Subject_Approvals','render_dashboard_notice'));
    $check(strpos($dashboard,$unavailable) !== false, 'dashboard_shows_generic_unavailable_notice');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id,status,decided_by FROM {$proposal_table} WHERE id IN (%d,%d,%d)",$proposal_ids[0],$proposal_ids[1],$proposal_ids[2]),ARRAY_A);
    $all_pending = is_array($rows) && count($rows)===3;
    foreach ((array)$rows as $row) { if (($row['status'] ?? '')!=='pending' || (int)($row['decided_by'] ?? -1)!==0) { $all_pending=false; } }
    $check($all_pending, 'drift_leaves_pending_rows_unchanged');
    $check($count_rows($events_table)===3 && $count_rows($proposal_table)===3, 'drift_creates_no_rows_or_events');
    $events = get_option($debug_option,array());
    $safe_diagnostics = false;
    foreach ((array)$events as $event) {
        $ctx = is_array($event['context'] ?? null) ? $event['context'] : array();
        if (($ctx['error_code'] ?? '') !== 'schema_not_ready' || ($ctx['outcome'] ?? '') !== 'fail_closed') { continue; }
        $json = wp_json_encode($event);
        $safe_diagnostics = is_string($json) && !preg_match('/\b(SELECT|SHOW|CREATE|ALTER|DROP)\b|z65q_zmp_|last_error/i',$json);
        foreach (array('sql','query','database_error','raw_error','table_name','operation') as $forbidden) {
            if (array_key_exists($forbidden,$ctx)) { $safe_diagnostics = false; }
        }
        if ($safe_diagnostics) { break; }
    }
    $check($safe_diagnostics, 'diagnostics_are_allowlisted_and_privacy_safe');
} catch (Throwable $error) {
    $failures[] = 'functional_setup_or_execution_exception';
} finally {
    if ($mode === 'drift') {
        if ($index_dropped) {
            $restored = $wpdb->query("ALTER TABLE `" . esc_sql($catalog_table) . "` ADD KEY `subject_status_active` (`status`,`is_active`)");
            if ($restored === false) { $failures[] = 'catalog_index_restoration'; }
            else { $index_dropped = false; }
        }
        wp_set_current_user($admin_id);
        foreach ($proposal_ids as $id) {
            $wpdb->delete($events_table,array('proposal_id'=>$id),array('%d'));
            $wpdb->delete($proposal_table,array('id'=>$id),array('%d'));
        }
        if ($canonical_id) { $wpdb->delete($catalog_table,array('id'=>$canonical_id),array('%d')); }
        if ($canonical_id) { $wpdb->delete($aliases_table,array('canonical_subject_id'=>$canonical_id),array('%d')); }
        if ($mandate_id) {
            $wpdb->delete(ZMP_President_Mandate::audit_table_name(),array('mandate_id'=>$mandate_id),array('%d'));
            $wpdb->delete(ZMP_President_Mandate::table_name(),array('id'=>$mandate_id),array('%d'));
        }
        foreach (array($teacher_id,$president_id) as $id) { if ($id) { wp_delete_user($id); } }
        if (is_array($debug_before)) {
            if (!empty($debug_before['existed']) && is_array($debug_before['row'] ?? null)) {
                $row = $debug_before['row'];
                $wpdb->update($wpdb->options,array('option_value'=>$row['option_value'],'autoload'=>$row['autoload']),
                    array('option_name'=>$debug_option),array('%s','%s'),array('%s'));
            } elseif (empty($debug_before['existed'])) { $wpdb->delete($wpdb->options,array('option_name'=>$debug_option),array('%s')); }
            wp_cache_delete($debug_option,'options'); wp_cache_delete('alloptions','options'); wp_cache_delete('notoptions','options');
        }
        delete_option($state_option);
        $audit_rows = $count_rows(ZMP_President_Mandate::audit_table_name());
        $mandate_rows = $count_rows(ZMP_President_Mandate::table_name());
        $catalog_indexes = $wpdb->get_results("SHOW INDEX FROM `" . esc_sql($catalog_table) . "` WHERE Key_name='subject_status_active'");
        $remaining_synthetic_users = 0;
        foreach (array($teacher_id,$president_id) as $id) { if ($id && get_user_by('id',$id)) { $remaining_synthetic_users++; } }
        $state_removed = get_option($state_option,false) === false;
        if ($count_rows($proposal_table)!==0 || $count_rows($events_table)!==0
            || $count_rows($catalog_table)!==0 || $count_rows($aliases_table)!==0
            || $audit_rows!==0 || $mandate_rows!==0 || $remaining_synthetic_users!==0
            || count((array)$catalog_indexes)!==2 || !$state_removed || $index_dropped) {
            $failures[]='synthetic_rows_users_state_or_index_not_restored';
        }
    } elseif ($mode === 'healthy' && !$healthy_completed) {
        wp_set_current_user($admin_id);
        foreach ($proposal_ids as $id) {
            if (is_numeric($id) && (int)$id > 0) {
                $wpdb->delete($events_table,array('proposal_id'=>(int)$id),array('%d'));
                $wpdb->delete($proposal_table,array('id'=>(int)$id),array('%d'));
            }
        }
        if (is_numeric($canonical_id) && (int)$canonical_id > 0) {
            $wpdb->delete($aliases_table,array('canonical_subject_id'=>(int)$canonical_id),array('%d'));
            $wpdb->delete($catalog_table,array('id'=>(int)$canonical_id),array('%d'));
        }
        if (is_numeric($mandate_id) && (int)$mandate_id > 0) {
            $wpdb->delete(ZMP_President_Mandate::audit_table_name(),array('mandate_id'=>(int)$mandate_id),array('%d'));
            $wpdb->delete(ZMP_President_Mandate::table_name(),array('id'=>(int)$mandate_id),array('%d'));
        }
        foreach (array($teacher_id,$president_id) as $id) { if (is_numeric($id) && (int)$id > 0) { wp_delete_user((int)$id); } }
        if (is_array($debug_before) && array_key_exists('existed',$debug_before)) {
            if (!empty($debug_before['existed']) && is_array($debug_before['row'] ?? null)) {
                $row = $debug_before['row'];
                $wpdb->update($wpdb->options,array('option_value'=>$row['option_value'],'autoload'=>$row['autoload']),
                    array('option_name'=>$debug_option),array('%s','%s'),array('%s'));
            } elseif (empty($debug_before['existed'])) { $wpdb->delete($wpdb->options,array('option_name'=>$debug_option),array('%s')); }
            wp_cache_delete($debug_option,'options'); wp_cache_delete('alloptions','options'); wp_cache_delete('notoptions','options');
        }
        delete_option($state_option);
        $remaining_users = 0;
        foreach (array($teacher_id,$president_id) as $id) { if (is_numeric($id) && (int)$id > 0 && get_user_by('id',(int)$id)) { $remaining_users++; } }
        $debug_after_cleanup = $wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$debug_option),ARRAY_A);
        $debug_cleanup_ok = is_array($debug_before) && !empty($debug_before['existed']) && is_array($debug_before['row'] ?? null)
            ? is_array($debug_after_cleanup)
                && $debug_after_cleanup['option_value'] === $debug_before['row']['option_value']
                && $debug_after_cleanup['autoload'] === $debug_before['row']['autoload']
            : is_array($debug_before) && empty($debug_before['existed']) && !is_array($debug_after_cleanup);
        if ($count_rows($proposal_table)!==0 || $count_rows($events_table)!==0
            || $count_rows($catalog_table)!==0 || $count_rows($aliases_table)!==0
            || $count_rows(ZMP_President_Mandate::table_name())!==0
            || $count_rows(ZMP_President_Mandate::audit_table_name())!==0
            || $remaining_users!==0 || (int)(count_users()['total_users'] ?? -1)!==1
            || get_option($state_option,false)!==false || !$debug_cleanup_ok) {
            $failures[]='partial_healthy_fixture_cleanup';
        }
    }
    wp_set_current_user($original_user);
    remove_filter('pre_http_request',$block_http,PHP_INT_MAX);
    remove_filter('pre_wp_mail',$block_mail,PHP_INT_MAX);
}

if ($http_count !== 0) { $failures[]='no_http_requests_attempted'; }
if ($mail_count !== 0) { $failures[]='no_mail_attempted'; }
if ($failures) { fwrite(STDERR,'FAIL: '.implode(',',array_values(array_unique($failures)))."\n"); exit(1); }
fwrite(STDOUT,'PASS: schema-drift fail-closed behavior ('.$assertions." assertions).\n");
exit(0);
