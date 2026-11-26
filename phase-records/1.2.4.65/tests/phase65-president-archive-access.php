<?php
/**
 * Focused synthetic acceptance for President document access across mandate
 * transition. This tests service behavior only; HTTP/nonce/browser behavior is
 * a separate gate.
 */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (!isset($wpdb) || !($wpdb instanceof wpdb)
    || (string) ($wpdb->dbname ?? '') !== 'zmp65_isolated'
    || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65') {
    fwrite(STDERR, "wrong_test_runtime\n");
    exit(3);
}
if (!class_exists('ZMP_President_Mandate') || !class_exists('ZMP_President_Governance')) {
    fwrite(STDERR, "wordpress_plugin_required\n");
    exit(2);
}

$results = array();
$failures = array();
$not_run = array();
$token = strtolower(wp_generate_password(16, false, false));
$original_user_id = get_current_user_id();
$created_users = array();
$mandate_ids = array();
$baseline_audit_rows = array();
$audit_baseline_valid = true;
$document_ids = array();
$temporary_files = array();
$cleanup_ok = true;
$record = static function($name, $status, $detail = '') use (&$results, &$failures, &$not_run) {
    $results[$name] = $status;
    if ($status === 'FAIL') { $failures[] = $name; }
    if ($status === 'NOT_RUN') { $not_run[$name] = $detail ?: 'Scenario prerequisites were unavailable.'; }
};
$error_code = static function($value) { return is_wp_error($value) ? (string) $value->get_error_code() : ''; };
$create_president = static function($label) use (&$created_users, $token) {
    $login = 'zmp65archive_' . $token . '_' . sanitize_key($label);
    $id = wp_insert_user(array(
        'user_login' => $login,
        'user_pass' => wp_generate_password(40, true, true),
        'user_email' => $login . '@example.invalid',
        'display_name' => 'Synthetic Phase 65 Archive ' . sanitize_text_field($label),
        'role' => 'zmp_president',
    ));
    if (is_wp_error($id) || absint($id) < 1) { return 0; }
    $id = absint($id);
    $created_users[] = $id;
    $user = get_user_by('id', $id);
    if ($user && !user_can($user, 'view_zmp_president_dashboard')) {
        $user->add_cap('view_zmp_president_dashboard');
        clean_user_cache($id);
    }
    return $id;
};
$insert_mandate = static function($user_id, $start, $end, $status, $predecessor_id = 0, $predecessor_user_id = 0) use (&$wpdb, &$mandate_ids, &$baseline_audit_rows, &$audit_baseline_valid) {
    $table = ZMP_President_Mandate::table_name();
    $inserted = $wpdb->insert($table, array(
        'user_id' => absint($user_id),
        'start_date' => $start,
        'end_date' => $end,
        'board_decision' => 'Synthetic phase 65 archive access acceptance fixture.',
        'status' => $status,
        'appointed_by' => get_current_user_id(),
        'appointed_at' => current_time('mysql'),
        'closed_by' => 0,
        'closed_at' => null,
        'close_reason' => '',
        'predecessor_mandate_id' => absint($predecessor_id),
        'predecessor_user_id' => absint($predecessor_user_id),
        'handover_deadline' => $predecessor_id ? $end : null,
        'handover_extension_months' => 0,
    ));
    if (!$inserted || absint($wpdb->insert_id) < 1) { return 0; }
    $id = absint($wpdb->insert_id);
    $mandate_ids[] = $id;
    $wpdb->last_error = '';
    $audit_rows = $wpdb->get_results($wpdb->prepare(
        'SELECT id,mandate_id,user_id,event_type,actor_user_id,reason,event_data,created_at FROM ' . ZMP_President_Mandate::audit_table_name() . ' WHERE mandate_id=%d ORDER BY id ASC',
        $id
    ), ARRAY_A);
    if (!is_array($audit_rows) || !empty($wpdb->last_error)) {
        $audit_baseline_valid = false;
        return 0;
    }
    $baseline_audit_rows[$id] = $audit_rows;
    return $id;
};
$create_pdf_document = static function($admin_id, $mandate_id, $label) use (&$temporary_files, &$document_ids, $token, $record) {
    $bytes = "%PDF-1.4\n% Synthetic phase65 archive " . $label . ' ' . $token . "\n%%EOF\n";
    $tmp = tempnam(sys_get_temp_dir(), 'zmp65-archive-');
    if (!is_string($tmp)) { return array(0, '', 'temporary_file_unavailable'); }
    $temporary_files[] = $tmp;
    if (file_put_contents($tmp, $bytes, LOCK_EX) !== strlen($bytes)) { return array(0, '', 'temporary_pdf_write_failed'); }
    wp_set_current_user($admin_id);
    $document_id = ZMP_President_Governance::admin_upload_document($mandate_id, array(
        'name' => 'phase65-archive-' . sanitize_key($label) . '-' . $token . '.pdf',
        'tmp_name' => $tmp,
        'type' => 'application/pdf',
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($bytes),
    ));
    if (is_wp_error($document_id) || absint($document_id) < 1) {
        return array(0, '', is_wp_error($document_id) ? sanitize_key($document_id->get_error_code()) : 'document_create_failed');
    }
    $document_id = absint($document_id);
    $document_ids[] = $document_id;
    return array($document_id, $bytes, '');
};

try {
    $admins = get_users(array('role'=>'administrator', 'number'=>1, 'fields'=>'ID'));
    $admin_id = is_array($admins) && !empty($admins) ? absint($admins[0]) : 0;
    $today = current_time('Y-m-d');
    $yesterday = (new DateTimeImmutable($today, wp_timezone()))->modify('-1 day')->format('Y-m-d');
    $tomorrow = (new DateTimeImmutable($today, wp_timezone()))->modify('+1 day')->format('Y-m-d');
    $old_end = ZMP_President_Mandate::term_end_date($yesterday);
    $new_end = ZMP_President_Mandate::term_end_date($today);
    $other_end = ZMP_President_Mandate::term_end_date($today);
        if (!$admin_id || is_wp_error($old_end) || is_wp_error($new_end) || is_wp_error($other_end)
        || !ZMP_President_Governance::encryption_ready()) {
        $record('fixture_prerequisites', 'NOT_RUN', 'Synthetic admin, mandate dates, or document encryption were unavailable.');
    } else {
        $prior_id = $create_president('prior');
        $former_id = $create_president('outgoing');
        $current_id = $create_president('incoming');
        $other_id = $create_president('unlinked');
        $prior_mandate_id = $prior_id ? $insert_mandate($prior_id, $yesterday, $yesterday, 'replaced') : 0;
        $former_mandate_id = ($former_id && $prior_mandate_id)
            ? $insert_mandate($former_id, $yesterday, (string) $old_end, 'active', $prior_mandate_id, $prior_id)
            : 0;
        $current_mandate_id = ($current_id && $former_mandate_id)
            ? $insert_mandate($current_id, $tomorrow, (string) $new_end, 'scheduled', $former_mandate_id, $former_id)
            : 0;
        $other_mandate_id = $other_id ? $insert_mandate($other_id, $today, (string) $other_end, 'active') : 0;
        if (!$prior_id || !$former_id || !$current_id || !$other_id || !$prior_mandate_id || !$former_mandate_id || !$current_mandate_id || !$other_mandate_id) {
            $record('fixture_prerequisites', 'NOT_RUN', 'Synthetic Presidents or mandate records could not be created.');
        } else {
            list($prior_doc_id, $prior_pdf, $prior_doc_error) = $create_pdf_document($admin_id, $prior_mandate_id, 'prior-archive');
            list($former_doc_id, $former_pdf, $former_doc_error) = $create_pdf_document($admin_id, $former_mandate_id, 'outgoing-archive');
            list($other_doc_id, $other_pdf, $other_doc_error) = $create_pdf_document($admin_id, $other_mandate_id, 'unrelated');
            if (!$prior_doc_id || !$former_doc_id || !$other_doc_id) {
                $record('archive_document_fixtures', 'NOT_RUN', 'Synthetic encrypted PDF creation failed: ' . sanitize_key($prior_doc_error . '_' . $former_doc_error . '_' . $other_doc_error));
            } else {
                wp_set_current_user($admin_id);
                $prior_archived = ZMP_President_Governance::admin_archive_document($prior_doc_id);
                $record('older_predecessor_record_is_archived', $prior_archived === true ? 'PASS' : 'FAIL');

                wp_set_current_user($former_id);
                $overlap_access = ZMP_President_Governance::document_content($former_doc_id);
                $record('outgoing_president_reads_only_own_document_during_linked_scheduled_overlap', is_string($overlap_access) && hash_equals($former_pdf, $overlap_access) ? 'PASS' : 'FAIL');
                $older_access = ZMP_President_Governance::get_accessible_document($prior_doc_id);
                $older_content = ZMP_President_Governance::document_content($prior_doc_id);
                $overlap_list = ZMP_President_Governance::president_documents();
                $overlap_list_ids = is_array($overlap_list) ? array_map(static function($row) { return absint($row->id ?? 0); }, $overlap_list) : array();
                $record('outgoing_president_cannot_access_older_predecessor_archive_during_overlap', is_wp_error($older_access)
                    && $error_code($older_access) === 'governance_document_unavailable'
                    && is_wp_error($older_content) && $error_code($older_content) === 'governance_document_unavailable'
                    && !in_array($prior_doc_id, $overlap_list_ids, true) ? 'PASS' : 'FAIL');

                wp_set_current_user($admin_id);
                $archive_result = ZMP_President_Governance::admin_archive_document($former_doc_id);
                $record('predecessor_document_is_archived', $archive_result === true ? 'PASS' : 'FAIL');

                $activated = $wpdb->update(ZMP_President_Mandate::table_name(), array('status'=>'active', 'start_date'=>$today), array('id'=>$current_mandate_id), array('%s','%s'), array('%d'));
                $former_closed = $wpdb->update(ZMP_President_Mandate::table_name(), array('status'=>'replaced', 'end_date'=>$yesterday), array('id'=>$former_mandate_id), array('%s','%s'), array('%d'));
                $record('successor_effective_transition_fixture', $activated === 1 && $former_closed === 1 ? 'PASS' : 'FAIL');

                wp_set_current_user($current_id);
                $current_list = ZMP_President_Governance::president_documents();
                $current_list_ids = is_array($current_list) ? array_map(static function($row) { return absint($row->id ?? 0); }, $current_list) : array();
                $record('current_president_lists_only_direct_predecessor_archive', in_array($former_doc_id, $current_list_ids, true)
                    && !in_array($prior_doc_id, $current_list_ids, true) && !in_array($other_doc_id, $current_list_ids, true) ? 'PASS' : 'FAIL');
                $current_access = ZMP_President_Governance::get_accessible_document($former_doc_id);
                $record('current_president_retrieves_only_directly_linked_predecessor_record', !is_wp_error($current_access)
                    && absint($current_access->mandate_id ?? 0) === $former_mandate_id
                    && absint($current_access->president_user_id ?? 0) === $former_id
                    && (string) ($current_access->status ?? '') === 'archived' ? 'PASS' : 'FAIL');
                $current_content = ZMP_President_Governance::document_content($former_doc_id);
                $record('current_president_decrypts_archive_with_plaintext_integrity', is_string($current_content)
                    && hash_equals($former_pdf, $current_content)
                    && is_object($current_access)
                    && (int) ($current_access->encryption_version ?? 0) === 1
                    && preg_match('/^[a-f0-9]{64}$/', (string) ($current_access->cipher_sha256 ?? '')) ? 'PASS' : 'FAIL');
                $unrelated_access = ZMP_President_Governance::document_content($other_doc_id);
                $record('current_president_cannot_read_unrelated_mandate_document', is_wp_error($unrelated_access)
                    && $error_code($unrelated_access) === 'governance_document_unavailable' ? 'PASS' : 'FAIL');
                $older_archive_access = ZMP_President_Governance::document_content($prior_doc_id);
                $record('current_president_cannot_read_non_direct_predecessor_archive', is_wp_error($older_archive_access)
                    && $error_code($older_archive_access) === 'governance_document_unavailable' ? 'PASS' : 'FAIL');

                wp_set_current_user($former_id);
                $former_access = ZMP_President_Governance::get_accessible_document($former_doc_id);
                $former_list = ZMP_President_Governance::president_documents();
                $record('former_president_denied_after_successor_effective', is_wp_error($former_access)
                    && $error_code($former_access) === 'governance_forbidden'
                    && is_wp_error($former_list) && $error_code($former_list) === 'governance_forbidden' ? 'PASS' : 'FAIL');

                wp_set_current_user($other_id);
                $outsider_access = ZMP_President_Governance::get_accessible_document($former_doc_id);
                $record('unlinked_active_president_denied_predecessor_archive', is_wp_error($outsider_access)
                    && $error_code($outsider_access) === 'governance_document_unavailable' ? 'PASS' : 'FAIL');

                wp_set_current_user($admin_id);
                $admin_access = ZMP_President_Governance::get_accessible_document($former_doc_id);
                $admin_content = ZMP_President_Governance::document_content($former_doc_id);
                $record('administrator_cannot_use_president_scoped_direct_document_api', is_wp_error($admin_access)
                    && $error_code($admin_access) === 'governance_forbidden'
                    && is_wp_error($admin_content) && $error_code($admin_content) === 'governance_forbidden' ? 'PASS' : 'FAIL');

                $row = $wpdb->get_row($wpdb->prepare('SELECT private_path,sha256,encryption_version,cipher_sha256 FROM ' . ZMP_President_Governance::documents_table() . ' WHERE id=%d', $former_doc_id));
                $root = realpath(!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
                $expected_prefix = $root ? trailingslashit(dirname($root)) . 'zmp-president-documents-' . substr(hash('sha256', home_url('/')), 0, 12) . DIRECTORY_SEPARATOR : '';
                $encrypted_path_ok = $row && (int) $row->encryption_version === 1
                    && $expected_prefix !== '' && strpos(wp_normalize_path((string) $row->private_path), wp_normalize_path($expected_prefix)) === 0
                    && is_file((string) $row->private_path)
                    && !hash_equals($former_pdf, (string) file_get_contents((string) $row->private_path))
                    && hash_equals((string) $row->cipher_sha256, hash_file('sha256', (string) $row->private_path))
                    && hash_equals(hash('sha256', $former_pdf), (string) $row->sha256);
                $record('archive_ciphertext_remains_private_and_integrity_bound', $encrypted_path_ok ? 'PASS' : 'FAIL');
            }
        }
    }
} catch (Throwable $error) {
    $record('fixture_execution', 'FAIL', 'unexpected_exception');
} finally {
    wp_set_current_user($original_user_id);
    $document_root = realpath(!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
    $private_dir_prefix = $document_root
        ? trailingslashit(dirname($document_root)) . 'zmp-president-documents-' . substr(hash('sha256', home_url('/')), 0, 12) . DIRECTORY_SEPARATOR
        : '';
    foreach ($document_ids as $document_id) {
        $wpdb->last_error = '';
        $row = $wpdb->get_row($wpdb->prepare('SELECT private_path,legacy_plain_path FROM ' . ZMP_President_Governance::documents_table() . ' WHERE id=%d', absint($document_id)));
        if (!empty($wpdb->last_error)) { $cleanup_ok = false; }
        if ($row) {
            foreach (array((string) $row->private_path, (string) $row->legacy_plain_path) as $path) {
                if ($path === '' || !is_file($path)) { continue; }
                if ($private_dir_prefix === ''
                    || strpos(wp_normalize_path($path), wp_normalize_path($private_dir_prefix)) !== 0
                    || !@unlink($path) || is_file($path)) {
                    $cleanup_ok = false;
                }
            }
        } else {
            $cleanup_ok = false;
        }
        $wpdb->last_error = '';
        $deleted = $wpdb->delete(ZMP_President_Governance::documents_table(), array('id'=>absint($document_id)), array('%d'));
        if ($deleted !== 1 || !empty($wpdb->last_error)) { $cleanup_ok = false; }
    }
    foreach ($mandate_ids as $mandate_id) {
        if (!$audit_baseline_valid || !array_key_exists($mandate_id, $baseline_audit_rows)) {
            $cleanup_ok = false;
            continue;
        }
        $baseline_ids = array_map('absint', array_column($baseline_audit_rows[$mandate_id], 'id'));
        $wpdb->last_error = '';
        $delete_sql = 'DELETE FROM ' . ZMP_President_Mandate::audit_table_name() . ' WHERE mandate_id=%d';
        $delete_args = array(absint($mandate_id));
        if ($baseline_ids) {
            $placeholders = implode(',', array_fill(0, count($baseline_ids), '%d'));
            $delete_sql .= ' AND id NOT IN (' . $placeholders . ')';
            $delete_args = array_merge($delete_args, $baseline_ids);
        }
        $deleted_audit_rows = $wpdb->query($wpdb->prepare($delete_sql, $delete_args));
        if ($deleted_audit_rows === false || !empty($wpdb->last_error)) { $cleanup_ok = false; }

        $wpdb->last_error = '';
        $remaining_audit_rows = $wpdb->get_results($wpdb->prepare(
            'SELECT id,mandate_id,user_id,event_type,actor_user_id,reason,event_data,created_at FROM ' . ZMP_President_Mandate::audit_table_name() . ' WHERE mandate_id=%d ORDER BY id ASC',
            absint($mandate_id)
        ), ARRAY_A);
        if (!is_array($remaining_audit_rows) || !empty($wpdb->last_error)
            || $remaining_audit_rows !== $baseline_audit_rows[$mandate_id]) { $cleanup_ok = false; }

        $wpdb->last_error = '';
        $remaining_documents = $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . ZMP_President_Governance::documents_table() . ' WHERE mandate_id=%d',
            absint($mandate_id)
        ));
        if ($remaining_documents === null || !empty($wpdb->last_error) || (int) $remaining_documents !== 0) { $cleanup_ok = false; }

        $wpdb->last_error = '';
        $deleted_mandate = $wpdb->delete(ZMP_President_Mandate::table_name(), array('id'=>absint($mandate_id)), array('%d'));
        if ($deleted_mandate !== 1 || !empty($wpdb->last_error)) { $cleanup_ok = false; }
    }
    foreach ($temporary_files as $path) {
        if (is_file($path) && (!@unlink($path) || is_file($path))) { $cleanup_ok = false; }
    }
    foreach ($created_users as $user_id) {
        $deleted_user = wp_delete_user(absint($user_id));
        if ($deleted_user !== true && get_user_by('id', absint($user_id))) { $cleanup_ok = false; }
    }
}

foreach ($document_ids as $document_id) {
    $wpdb->last_error = '';
    $remaining = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ZMP_President_Governance::documents_table() . ' WHERE id=%d', absint($document_id)));
    if ($remaining === null || !empty($wpdb->last_error) || (int) $remaining !== 0) { $cleanup_ok = false; }
}
foreach ($mandate_ids as $mandate_id) {
    $wpdb->last_error = '';
    $remaining = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ZMP_President_Mandate::table_name() . ' WHERE id=%d', absint($mandate_id)));
    if ($remaining === null || !empty($wpdb->last_error) || (int) $remaining !== 0) { $cleanup_ok = false; }
    if ($audit_baseline_valid && array_key_exists($mandate_id, $baseline_audit_rows)) {
        $wpdb->last_error = '';
        $audit_rows = $wpdb->get_results($wpdb->prepare(
            'SELECT id,mandate_id,user_id,event_type,actor_user_id,reason,event_data,created_at FROM ' . ZMP_President_Mandate::audit_table_name() . ' WHERE mandate_id=%d ORDER BY id ASC',
            absint($mandate_id)
        ), ARRAY_A);
        if (!is_array($audit_rows) || !empty($wpdb->last_error)
            || $audit_rows !== $baseline_audit_rows[$mandate_id]) { $cleanup_ok = false; }
    } else {
        $cleanup_ok = false;
    }
}
foreach ($created_users as $user_id) {
    if (get_user_by('id', absint($user_id))) { $cleanup_ok = false; }
}
foreach ($temporary_files as $path) { if (is_file($path)) { $cleanup_ok = false; } }
$record('fixture_cleanup', $cleanup_ok ? 'PASS' : 'FAIL');

echo wp_json_encode(array('results'=>$results, 'failures'=>array_values(array_unique($failures)), 'not_run'=>$not_run), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
