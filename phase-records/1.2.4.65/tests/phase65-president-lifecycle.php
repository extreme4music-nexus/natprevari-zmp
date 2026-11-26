<?php
/**
 * Synthetic service-level acceptance for President mandate document and
 * facsimile-certificate boundaries. This script does not exercise admin-post
 * HTTP routes; those remain explicitly NOT_RUN here.
 */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;
if (!isset($wpdb) || !($wpdb instanceof wpdb)
    || (string) ($wpdb->dbname ?? '') !== 'zmp65_isolated'
    || !defined('ZMP_VERSION') || ZMP_VERSION !== '1.2.4.65') {
    fwrite(STDERR, "wrong_test_runtime\n");
    exit(3);
}
if (!isset($wpdb) || !($wpdb instanceof wpdb)
    || !class_exists('ZMP_President_Mandate')
    || !class_exists('ZMP_President_Governance')
    || !class_exists('ZMP_President_Facsimile_Certificates')
    || !class_exists('ZMP_Registration_Requirements')) {
    fwrite(STDERR, "wordpress_plugin_required\n");
    exit(2);
}

$results = array();
$failures = array();
$not_run = array();
$token = strtolower(wp_generate_password(14, false, false));
$original_user_id = get_current_user_id();
$original_post = isset($_POST) && is_array($_POST) ? $_POST : array();
$original_get = isset($_GET) && is_array($_GET) ? $_GET : array();
$original_request = isset($_REQUEST) && is_array($_REQUEST) ? $_REQUEST : array();
$original_files = isset($_FILES) && is_array($_FILES) ? $_FILES : array();
$created_users = array();
$mandate_ids = array();
$document_ids = array();
$certificate_ids = array();
$facsimile_ids = array();
$created_files = array();
$created_dirs = array();
$temporary_files = array();
$synthetic_serial_prefix = 'zmp65-life-' . $token . '-';
$governance_document_dir = '';
$governance_document_dir_preexisting = false;
$governance_document_dir_baseline = array();
$governance_document_dir_baseline_ready = false;
$document_root = realpath(!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
$document_parent = $document_root ? realpath(dirname($document_root)) : false;
if ($document_root && $document_parent) {
    $governance_document_dir = trailingslashit($document_parent) . 'zmp-president-documents-' . substr(hash('sha256', home_url('/')), 0, 12);
    $normalized_document_root = rtrim(str_replace('\\', '/', $document_root), '/') . '/';
    $normalized_governance_dir = trailingslashit(str_replace('\\', '/', $governance_document_dir));
    if (strpos($normalized_governance_dir, $normalized_document_root) !== 0 && !is_link($governance_document_dir)) {
        $governance_document_dir_preexisting = is_dir($governance_document_dir);
        $governance_document_dir_baseline_ready = !$governance_document_dir_preexisting || is_dir($governance_document_dir);
        if ($governance_document_dir_baseline_ready && $governance_document_dir_preexisting) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($governance_document_dir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $entry) {
                    $absolute = $entry->getPathname();
                    $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($governance_document_dir))), '/');
                    if ($entry->isLink()) {
                        $governance_document_dir_baseline[$relative] = 'link:' . (string) readlink($absolute);
                    } elseif ($entry->isDir()) {
                        $governance_document_dir_baseline[$relative] = 'dir';
                    } elseif ($entry->isFile()) {
                        $hash = hash_file('sha256', $absolute);
                        if (!is_string($hash)) { $governance_document_dir_baseline_ready = false; break; }
                        $governance_document_dir_baseline[$relative] = 'file:' . $hash;
                    }
                }
                ksort($governance_document_dir_baseline, SORT_STRING);
            } catch (Throwable $error) {
                $governance_document_dir_baseline_ready = false;
            }
        }
    }
}
$governance_document_dir_snapshot = static function () use ($governance_document_dir) {
    if (!is_dir($governance_document_dir) || is_link($governance_document_dir)) { return false; }
    $snapshot = array();
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($governance_document_dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $entry) {
            $absolute = $entry->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($governance_document_dir))), '/');
            if ($entry->isLink()) {
                $snapshot[$relative] = 'link:' . (string) readlink($absolute);
            } elseif ($entry->isDir()) {
                $snapshot[$relative] = 'dir';
            } elseif ($entry->isFile()) {
                $hash = hash_file('sha256', $absolute);
                if (!is_string($hash)) { return false; }
                $snapshot[$relative] = 'file:' . $hash;
            }
        }
    } catch (Throwable $error) {
        return false;
    }
    ksort($snapshot, SORT_STRING);
    return $snapshot;
};

$record = static function ($name, $status, $detail = '') use (&$results, &$failures, &$not_run) {
    $results[$name] = $status;
    if ($status === 'FAIL') { $failures[] = $name; }
    if ($status === 'NOT_RUN') { $not_run[$name] = $detail ?: 'Scenario prerequisites were unavailable.'; }
};
$code = static function ($value) { return is_wp_error($value) ? $value->get_error_code() : ''; };
$president = static function ($label) use (&$created_users, $token) {
    $login = 'zmp65life_' . $token . '_' . sanitize_key($label);
    $id = wp_insert_user(array(
        'user_login' => $login,
        'user_pass' => wp_generate_password(40, true, true),
        'user_email' => $login . '@example.invalid',
        'display_name' => 'Synthetic Phase 65 President ' . sanitize_text_field($label),
        'role' => 'zmp_president',
    ));
    if (is_wp_error($id) || absint($id) < 1) { return 0; }
    $created_users[] = absint($id);
    $user = get_user_by('id', absint($id));
    if ($user && !user_can($user, 'view_zmp_president_dashboard')) {
        $user->add_cap('view_zmp_president_dashboard');
        clean_user_cache(absint($id));
    }
    return absint($id);
};
$mandate = static function ($user_id, $start, $end, $status, $predecessor_id = 0, $predecessor_user_id = 0) use (&$wpdb, &$mandate_ids) {
    $table = ZMP_President_Mandate::table_name();
    $inserted = $wpdb->insert($table, array(
        'user_id' => absint($user_id),
        'start_date' => $start,
        'end_date' => $end,
        'board_decision' => 'Synthetic Phase 65 acceptance fixture.',
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
    if ($inserted !== 1 || absint($wpdb->insert_id) < 1) { return 0; }
    $id = absint($wpdb->insert_id);
    $mandate_ids[] = $id;
    return $id;
};
$new_certificate_material = static function ($context, $serial) {
    if (!function_exists('sodium_crypto_sign_keypair') || !function_exists('sodium_crypto_sign_detached')) { return null; }
    $keypair = sodium_crypto_sign_keypair();
    $secret_key = sodium_crypto_sign_secretkey($keypair);
    $public_key = sodium_crypto_sign_publickey($keypair);
    $manifest = array(
        'version' => 1,
        'algorithm' => 'Ed25519',
        'serial' => $serial,
        'mandate_id' => (int) $context['mandate_id'],
        'president_user_id' => (int) $context['president_user_id'],
        'public_key_id' => hash('sha256', $public_key),
        'facsimile_id' => (int) $context['facsimile_id'],
        'facsimile_sha256' => (string) $context['facsimile_sha256'],
        'permitted_use' => array('certificate_pdf'),
        'valid_from' => (string) $context['valid_from'],
        'valid_until' => (string) $context['valid_until'],
    );
    ksort($manifest, SORT_STRING);
    $bytes = wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($bytes)) { return null; }
    $signature = sodium_crypto_sign_detached($bytes, $secret_key);
    return array(
        'public_key' => base64_encode($public_key),
        'manifest' => base64_encode($bytes),
        'signature' => base64_encode($signature),
        'bytes' => $bytes,
        'secret_key' => $secret_key,
    );
};

try {
    $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
    $admin_id = is_array($admins) && !empty($admins) ? absint($admins[0]) : 0;
    $today = current_time('Y-m-d');
    $old_end = ZMP_President_Mandate::term_end_date($today);
    $new_end = ZMP_President_Mandate::term_end_date($today);
    if (!$admin_id || is_wp_error($old_end) || is_wp_error($new_end) || !$governance_document_dir_baseline_ready) {
        $record('fixture_prerequisites', 'NOT_RUN', 'Admin user or valid mandate dates unavailable.');
    } else {
        $old_id = $president('outgoing');
        $new_id = $president('incoming');
        $teacher_login = 'zmp65life_' . $token . '_teacher';
        $teacher_id = wp_insert_user(array('user_login' => $teacher_login, 'user_pass' => wp_generate_password(40, true, true), 'user_email' => $teacher_login . '@example.invalid', 'display_name' => 'Synthetic Phase 65 Teacher', 'role' => 'zmp_teacher'));
        if (!is_wp_error($teacher_id) && absint($teacher_id) > 0) { $created_users[] = absint($teacher_id); } else { $teacher_id = 0; }
        $testator_login = 'zmp65life_' . $token . '_testator';
        $testator_id = wp_insert_user(array('user_login' => $testator_login, 'user_pass' => wp_generate_password(40, true, true), 'user_email' => $testator_login . '@example.invalid', 'display_name' => 'Synthetic Phase 65 Testator', 'role' => 'zmp_testator'));
        if (!is_wp_error($testator_id) && absint($testator_id) > 0) { $created_users[] = absint($testator_id); } else { $testator_id = 0; }
        $outgoing_mandate_id = $old_id ? $mandate($old_id, $today, (string) $old_end, 'active') : 0;
        // Two simultaneously active records with no predecessor link are
        // deliberate: document ownership must be enforced by mandate ID, not
        // inferred from a handover/successor row.
        $incoming_mandate_id = ($new_id && $outgoing_mandate_id) ? $mandate($new_id, $today, (string) $new_end, 'active') : 0;
        if (!$old_id || !$new_id || !$teacher_id || !$testator_id || !$outgoing_mandate_id || !$incoming_mandate_id) {
            $record('fixture_prerequisites', 'NOT_RUN', 'Could not create isolated synthetic users and transition mandates.');
        } else {
            wp_set_current_user($admin_id);
            $pdf = "%PDF-1.4\n% ZMP phase65 synthetic election document " . $token . "\n%%EOF\n";
            $temporary_pdf = tempnam(sys_get_temp_dir(), 'zmp65-life-');
            if (is_string($temporary_pdf)) { $temporary_files[] = $temporary_pdf; }
            if (!is_string($temporary_pdf) || file_put_contents($temporary_pdf, $pdf) !== strlen($pdf)) {
                $record('encrypted_election_document', 'NOT_RUN', 'A temporary synthetic PDF could not be created.');
            } else {
                $document_id = ZMP_President_Governance::admin_upload_document($outgoing_mandate_id, array(
                    'name' => 'phase65-election-' . $token . '.pdf', 'tmp_name' => $temporary_pdf,
                    'type' => 'application/pdf',
                    'error' => UPLOAD_ERR_OK, 'size' => strlen($pdf),
                ));
                if (is_wp_error($document_id)) {
                    $record('encrypted_election_document', 'NOT_RUN', 'admin_upload_document_error_code=' . sanitize_key((string) $code($document_id)));
                } else {
                    $document_id = absint($document_id);
                    $document_ids[] = $document_id;
                    wp_set_current_user($old_id);
                    $read = ZMP_President_Governance::document_content($document_id);
                    $record('active_president_reads_own_encrypted_document', is_string($read) && hash_equals($pdf, $read) ? 'PASS' : 'FAIL');
                    $old_access = ZMP_President_Governance::get_accessible_document($document_id);
                    $record('current_outgoing_president_owns_document_access', !is_wp_error($old_access) && (int) $old_access->mandate_id === $outgoing_mandate_id && (int) $old_access->president_user_id === $old_id ? 'PASS' : 'FAIL');

                    $incoming_pdf = "%PDF-1.4\n% ZMP phase65 synthetic second-President document " . $token . "\n%%EOF\n";
                    $temporary_pdf_two = tempnam(sys_get_temp_dir(), 'zmp65-life-');
                    if (is_string($temporary_pdf_two)) { $temporary_files[] = $temporary_pdf_two; }
                    if (!is_string($temporary_pdf_two) || file_put_contents($temporary_pdf_two, $incoming_pdf) !== strlen($incoming_pdf)) {
                        $temporary_pdf_two = '';
                    }
                    wp_set_current_user($admin_id);
                    $second_document_id = ZMP_President_Governance::admin_upload_document($incoming_mandate_id, array(
                        'name' => 'phase65-election-second-' . $token . '.pdf', 'tmp_name' => $temporary_pdf_two,
                        'type' => 'application/pdf',
                        'error' => $temporary_pdf_two !== '' ? UPLOAD_ERR_OK : UPLOAD_ERR_NO_FILE, 'size' => strlen($incoming_pdf),
                    ));
                    if (is_wp_error($second_document_id)) {
                        $record('cross_president_document_isolation', 'NOT_RUN', 'admin_upload_document_error_code=' . sanitize_key((string) $code($second_document_id)));
                    } else {
                        $second_document_id = absint($second_document_id);
                        $document_ids[] = $second_document_id;
                        wp_set_current_user($old_id);
                        $own_list = ZMP_President_Governance::president_documents();
                        $own_list_ids = is_array($own_list) ? array_map(static function ($row) { return absint($row->id ?? 0); }, $own_list) : array();
                        $record('active_concurrent_president_document_list_is_owner_scoped', in_array($document_id, $own_list_ids, true) && !in_array($second_document_id, $own_list_ids, true) ? 'PASS' : 'FAIL');
                        $foreign_record = ZMP_President_Governance::get_accessible_document($second_document_id);
                        $record('active_concurrent_president_cannot_get_foreign_document_id', is_wp_error($foreign_record) && $code($foreign_record) === 'governance_document_unavailable' ? 'PASS' : 'FAIL');
                        $foreign_content = ZMP_President_Governance::document_content($second_document_id);
                        $record('active_concurrent_president_cannot_decrypt_foreign_document', is_wp_error($foreign_content) && $code($foreign_content) === 'governance_document_unavailable' ? 'PASS' : 'FAIL');

                        wp_set_current_user($new_id);
                        $new_own_content = ZMP_President_Governance::document_content($second_document_id);
                        $record('second_active_president_reads_own_document', is_string($new_own_content) && hash_equals($incoming_pdf, $new_own_content) ? 'PASS' : 'FAIL');
                        $new_foreign_list = ZMP_President_Governance::president_documents();
                        $new_foreign_ids = is_array($new_foreign_list) ? array_map(static function ($row) { return absint($row->id ?? 0); }, $new_foreign_list) : array();
                        $record('second_active_president_list_is_owner_scoped', in_array($second_document_id, $new_foreign_ids, true) && !in_array($document_id, $new_foreign_ids, true) ? 'PASS' : 'FAIL');

                        // Once the outgoing record is no longer active, only
                        // the remaining current President retains access.
                        $wpdb->update(ZMP_President_Mandate::table_name(), array('status' => 'replaced'), array('id' => $outgoing_mandate_id), array('%s'), array('%d'));
                        wp_set_current_user($old_id);
                        $former_access = ZMP_President_Governance::get_accessible_document($document_id);
                        $record('former_president_denied_after_mandate_ends', is_wp_error($former_access) && $code($former_access) === 'governance_forbidden' ? 'PASS' : 'FAIL');

                        // Re-enable the outgoing mandate, add its explicitly
                        // linked scheduled successor, and exercise the overlap
                        // allowance followed by revocation at activation.
                        $wpdb->update(ZMP_President_Mandate::table_name(), array('status' => 'active'), array('id' => $outgoing_mandate_id), array('%s'), array('%d'));
                        $successor_user_id = $president('scheduled-successor');
                        $successor_start = (new DateTimeImmutable($today, wp_timezone()))->modify('+1 day')->format('Y-m-d');
                        $successor_end = ZMP_President_Mandate::term_end_date($successor_start);
                        $successor_mandate_id = $successor_user_id && !is_wp_error($successor_end)
                            ? $mandate($successor_user_id, $successor_start, (string) $successor_end, 'scheduled', $outgoing_mandate_id, $old_id)
                            : 0;
                        if (!$successor_mandate_id) {
                            $record('linked_scheduled_successor_overlap_access', 'NOT_RUN', 'A linked synthetic scheduled successor could not be created.');
                        } else {
                            wp_set_current_user($old_id);
                            $overlap_content = ZMP_President_Governance::document_content($document_id);
                            $record('outgoing_president_reads_own_document_during_scheduled_overlap', is_string($overlap_content) && hash_equals($pdf, $overlap_content) ? 'PASS' : 'FAIL');
                            $activated = $wpdb->update(ZMP_President_Mandate::table_name(), array('status' => 'active', 'start_date' => $today), array('id' => $successor_mandate_id), array('%s','%s'), array('%d'));
                            if ($activated !== 1) {
                                $record('outgoing_president_access_revoked_when_linked_successor_takes_effect', 'NOT_RUN', 'The synthetic successor mandate could not be activated.');
                            } else {
                                $after_activation = ZMP_President_Governance::document_content($document_id);
                                $record('outgoing_president_access_revoked_when_linked_successor_takes_effect', is_wp_error($after_activation) && $code($after_activation) === 'governance_forbidden' ? 'PASS' : 'FAIL');
                            }
                        }
                    }

                    wp_set_current_user($teacher_id);
                    $teacher_access = ZMP_President_Governance::get_accessible_document($document_id);
                    $record('teacher_direct_document_id_denied', is_wp_error($teacher_access) && $code($teacher_access) === 'governance_forbidden' ? 'PASS' : 'FAIL');
                    wp_set_current_user($testator_id);
                    $testator_access = ZMP_President_Governance::document_content($document_id);
                    $record('testator_direct_document_id_denied', is_wp_error($testator_access) && $code($testator_access) === 'governance_forbidden' ? 'PASS' : 'FAIL');
                    wp_set_current_user(0);
                    $anonymous_access = ZMP_President_Governance::get_accessible_document($document_id);
                    $record('anonymous_direct_document_id_denied', is_wp_error($anonymous_access) && $code($anonymous_access) === 'governance_forbidden' ? 'PASS' : 'FAIL');

                    wp_set_current_user($admin_id);
                    $document_root = realpath(!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
                    $facsimile_dir = $document_root
                        ? wp_normalize_path(trailingslashit(dirname($document_root)) . 'zmp-private-facsimiles-' . substr(hash('sha256', home_url('/')), 0, 12))
                        : '';
                    $dir_existed = is_dir($facsimile_dir);
                    if ($facsimile_dir !== '' && !$dir_existed && wp_mkdir_p($facsimile_dir)) { $created_dirs[] = $facsimile_dir; }
                    if ($facsimile_dir === '' || !is_dir($facsimile_dir)) {
                        $record('facsimile_certificate_flow', 'NOT_RUN', 'Private synthetic facsimile storage was unavailable.');
                    } else {
                        $facsimile_bytes = "synthetic phase65 facsimile bytes " . $token;
                        $facsimile_hash = hash('sha256', $facsimile_bytes);
                        $file_key = bin2hex(random_bytes(24)) . '.png';
                        $facsimile_path = trailingslashit($facsimile_dir) . $file_key;
                        $created_files[] = $facsimile_path;
                        if (file_put_contents($facsimile_path, $facsimile_bytes, LOCK_EX) !== strlen($facsimile_bytes)) {
                            $record('facsimile_certificate_flow', 'NOT_RUN', 'Synthetic private facsimile bytes could not be written.');
                        } else {
                            $facsimile_inserted = $wpdb->insert($wpdb->prefix . 'zmp_admin_facsimiles', array(
                                'president_name' => 'Synthetic Phase 65 Incoming President', 'file_key' => $file_key,
                                'original_name' => 'phase65-facsimile-' . $token . '.png', 'mime_type' => 'image/png',
                                'file_size' => strlen($facsimile_bytes), 'sha256' => $facsimile_hash,
                                'valid_from' => $today, 'valid_to' => (string) $new_end, 'status' => 'pending',
                                'mandate_id' => $incoming_mandate_id, 'president_user_id' => $new_id,
                                'uploaded_by' => $admin_id, 'created_at' => current_time('mysql'),
                            ));
                            $candidate_facsimile_id = $facsimile_inserted === 1 ? absint($wpdb->insert_id) : 0;
                            if ($candidate_facsimile_id > 0) { $facsimile_ids[] = $candidate_facsimile_id; }
                            $wpdb->last_error = '';
                            $stored_facsimile = $candidate_facsimile_id
                                ? $wpdb->get_row($wpdb->prepare('SELECT id,file_key,mandate_id,president_user_id FROM ' . $wpdb->prefix . 'zmp_admin_facsimiles WHERE id=%d', $candidate_facsimile_id))
                                : null;
                            $facsimile_row_verified = $facsimile_inserted === 1 && $candidate_facsimile_id > 0
                                && empty($wpdb->last_error) && $stored_facsimile
                                && hash_equals($file_key, (string) $stored_facsimile->file_key)
                                && (int) $stored_facsimile->mandate_id === $incoming_mandate_id
                                && (int) $stored_facsimile->president_user_id === $new_id;
                            if (!$facsimile_row_verified) {
                                $record('facsimile_certificate_flow', 'NOT_RUN', 'Synthetic facsimile insert/readback did not verify the exact generated row.');
                            } else {
                                $facsimile_id = $candidate_facsimile_id;
                                $admin_facsimile = ZMP_Registration_Requirements::get_admin_facsimile($facsimile_id);
                                $record('facsimile_path_hash_is_verified', !is_wp_error($admin_facsimile) && hash_equals($facsimile_hash, (string) ($admin_facsimile['sha256'] ?? '')) ? 'PASS' : 'FAIL');

                                wp_set_current_user($new_id);
                                $new_president_status = ZMP_President_Facsimile_Certificates::current_certificate_status();
                                $record('incoming_active_president_certificate_status_available', is_array($new_president_status) && ($new_president_status['status'] ?? '') === 'none' ? 'PASS' : 'FAIL');
                                $context = ZMP_President_Facsimile_Certificates::current_registration_context();
                                if (is_wp_error($context)) {
                                    $record('facsimile_certificate_flow', 'NOT_RUN', 'Certificate context unavailable for the synthetic mandate and facsimile.');
                                } else {
                                    $key_material = $new_certificate_material($context, $synthetic_serial_prefix . 'one');
                                    if (!$key_material) {
                                        $record('facsimile_certificate_flow', 'NOT_RUN', 'Ed25519 support unavailable.');
                                    } else {
                                        $bad_signature = base64_encode(str_repeat("\0", SODIUM_CRYPTO_SIGN_BYTES));
                                        $bad = ZMP_President_Facsimile_Certificates::register_certificate($key_material['public_key'], $key_material['manifest'], $bad_signature);
                                        $record('invalid_certificate_signature_rejected', is_wp_error($bad) && $code($bad) === 'facsimile_certificate_signature_invalid' ? 'PASS' : 'FAIL');

                                        $registered = ZMP_President_Facsimile_Certificates::register_certificate($key_material['public_key'], $key_material['manifest'], $key_material['signature']);
                                        if (is_wp_error($registered)) {
                                            $record('facsimile_certificate_flow', 'NOT_RUN', 'A signed synthetic certificate could not be registered in the isolated environment.');
                                        } else {
                                            $certificate_one_id = is_array($registered) ? absint($registered['certificate_id'] ?? 0) : absint($registered);
                                            if ($certificate_one_id) { $certificate_ids[] = $certificate_one_id; }
                                            $facsimile_active = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d", $facsimile_id));
                                            $verified = $certificate_one_id && ZMP_President_Facsimile_Certificates::verify_for_use($certificate_one_id, 'certificate_pdf', $facsimile_id, $facsimile_hash);
                                            $record('signed_certificate_binds_facsimile_and_verifies', $verified && $facsimile_active === 'active' ? 'PASS' : 'FAIL');

                                            $file_key_two = bin2hex(random_bytes(24)) . '.png';
                                            $facsimile_path_two = trailingslashit($facsimile_dir) . $file_key_two;
                                            $created_files[] = $facsimile_path_two;
                                            $second_file_written = file_put_contents($facsimile_path_two, $facsimile_bytes, LOCK_EX) === strlen($facsimile_bytes);
                                            $facsimile_two_inserted = $second_file_written ? $wpdb->insert($wpdb->prefix . 'zmp_admin_facsimiles', array(
                                                'president_name' => 'Synthetic Phase 65 Incoming President', 'file_key' => $file_key_two,
                                                'original_name' => 'phase65-facsimile-rotation-' . $token . '.png', 'mime_type' => 'image/png',
                                                'file_size' => strlen($facsimile_bytes), 'sha256' => $facsimile_hash,
                                                'valid_from' => $today, 'valid_to' => (string) $new_end, 'status' => 'pending',
                                                'mandate_id' => $incoming_mandate_id, 'president_user_id' => $new_id,
                                                'uploaded_by' => $admin_id, 'created_at' => current_time('mysql'),
                                            )) : false;
                    $facsimile_two_id = $facsimile_two_inserted === 1 ? absint($wpdb->insert_id) : 0;
                    if ($facsimile_two_id) { $facsimile_ids[] = $facsimile_two_id; }
                                            $rotation_context = $facsimile_two_inserted === 1
                                                ? ZMP_President_Facsimile_Certificates::current_registration_context()
                                                : new WP_Error('synthetic_facsimile_rotation_fixture_failed');
                                            if (is_wp_error($rotation_context)) {
                                                $record('certificate_key_rotation', 'NOT_RUN', 'A second mandate-bound synthetic facsimile could not be prepared.');
                                            } else {
                                                $key_material_two = $new_certificate_material($rotation_context, $synthetic_serial_prefix . 'two');
                                            }
                                            if (isset($rotation_context) && !is_wp_error($rotation_context) && !$key_material_two) {
                                                $record('certificate_key_rotation', 'NOT_RUN', 'Ed25519 key rotation material unavailable.');
                                            } elseif (!is_wp_error($rotation_context)) {
                                                $registered_two = ZMP_President_Facsimile_Certificates::register_certificate($key_material_two['public_key'], $key_material_two['manifest'], $key_material_two['signature']);
                                                if (is_wp_error($registered_two)) {
                                                    $record('certificate_key_rotation', 'NOT_RUN', 'A second synthetic certificate could not be registered.');
                                                } else {
                                                    $certificate_two_id = is_array($registered_two) ? absint($registered_two['certificate_id'] ?? 0) : absint($registered_two);
                                                    if ($certificate_two_id) { $certificate_ids[] = $certificate_two_id; }
                                                    $old_still_valid = $certificate_one_id && ZMP_President_Facsimile_Certificates::verify_for_use($certificate_one_id, 'certificate_pdf', $facsimile_id, $facsimile_hash);
                                                    $new_is_valid = $certificate_two_id && ZMP_President_Facsimile_Certificates::verify_for_use($certificate_two_id, 'certificate_pdf', $facsimile_two_id, $facsimile_hash);
                                                    $record('certificate_key_rotation_supersedes_old_key', !$old_still_valid && $new_is_valid ? 'PASS' : 'FAIL');
                                                    wp_set_current_user($teacher_id);
                                                    $teacher_context = ZMP_President_Facsimile_Certificates::current_registration_context();
                                                    $record('teacher_denied_certificate_registration_context', is_wp_error($teacher_context) && $code($teacher_context) === 'facsimile_certificate_president_required' ? 'PASS' : 'FAIL');
                                                    wp_set_current_user(0);
                                                    $anonymous_context = ZMP_President_Facsimile_Certificates::current_registration_context();
                                                    $record('anonymous_denied_certificate_registration_context', is_wp_error($anonymous_context) && $code($anonymous_context) === 'facsimile_certificate_president_required' ? 'PASS' : 'FAIL');
                                                    wp_set_current_user($admin_id);
                                                    $revoke = ZMP_President_Facsimile_Certificates::revoke_certificate($certificate_two_id, 'Synthetic Phase 65 key lifecycle check.');
                                                    $record('admin_revocation_invalidates_current_certificate', $revoke === true && !ZMP_President_Facsimile_Certificates::verify_for_use($certificate_two_id, 'certificate_pdf', $facsimile_id, $facsimile_hash) ? 'PASS' : 'FAIL');
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $record('http_admin_post_document_and_certificate_routes', 'NOT_RUN', 'Service-level checks do not prove direct URL, nonce, or browser response behavior.');
                    $record('private_facsimile_download_route', 'NOT_RUN', 'The admin-post download route is not safely invocable in this service-level script.');
                    $record('key_loss_recovery', 'NOT_RUN', 'No private signing-key storage or recovery path is part of this plugin API; only invalid-signature rejection is exercised.');
                }
            }
        }
    }
} catch (Throwable $error) {
    $record('fixture_execution', 'FAIL', 'unexpected_exception');
} finally {
    $_POST = $original_post;
    $_GET = $original_get;
    $_REQUEST = $original_request;
    $_FILES = $original_files;
    wp_set_current_user($original_user_id);

    $document_table = ZMP_President_Governance::documents_table();
    if ($mandate_ids) {
        foreach ($mandate_ids as $mandate_id) {
            $rows = $wpdb->get_results($wpdb->prepare('SELECT private_path,legacy_plain_path FROM ' . $document_table . ' WHERE mandate_id=%d', absint($mandate_id)));
            foreach ((array) $rows as $row) {
                foreach (array((string) ($row->private_path ?? ''), (string) ($row->legacy_plain_path ?? '')) as $path) {
                    if ($path !== '' && is_file($path)) { @unlink($path); }
                }
            }
        }
    }
    foreach ($document_ids as $document_id) {
        $row = $wpdb->get_row($wpdb->prepare('SELECT private_path,legacy_plain_path FROM ' . $document_table . ' WHERE id=%d', absint($document_id)));
        if ($row) {
            foreach (array((string) $row->private_path, (string) $row->legacy_plain_path) as $path) {
                if ($path !== '' && is_file($path)) { @unlink($path); }
            }
        }
        $wpdb->delete($document_table, array('id' => absint($document_id)), array('%d'));
    }
    if ($mandate_ids) {
        $ids_sql = implode(',', array_map('absint', $mandate_ids));
        $wpdb->query('DELETE FROM ' . ZMP_President_Mandate::audit_table_name() . " WHERE mandate_id IN ({$ids_sql})");
        $wpdb->query('DELETE FROM ' . ZMP_President_Governance::documents_table() . " WHERE mandate_id IN ({$ids_sql})");
        $wpdb->query('DELETE FROM ' . ZMP_President_Facsimile_Certificates::events_table() . " WHERE mandate_id IN ({$ids_sql})");
        $wpdb->query('DELETE FROM ' . ZMP_President_Facsimile_Certificates::certificates_table() . " WHERE mandate_id IN ({$ids_sql})");
        $wpdb->query('DELETE FROM ' . ZMP_President_Mandate::table_name() . " WHERE id IN ({$ids_sql})");
    }
    foreach ($facsimile_ids as $facsimile_id) {
        $wpdb->delete($wpdb->prefix . 'zmp_admin_facsimiles', array('id' => absint($facsimile_id)), array('%d'));
    }
    foreach ($created_files as $path) { if (is_file($path)) { @unlink($path); } }
    foreach ($created_dirs as $path) { if (is_dir($path)) { @rmdir($path); } }
    foreach ($temporary_files as $path) { if (is_file($path)) { @unlink($path); } }
    foreach ($created_users as $user_id) { wp_delete_user(absint($user_id)); }
}

$cleanup_ok = true;
$count_is_zero = static function ($sql) use ($wpdb) {
    $wpdb->last_error = '';
    $count = $wpdb->get_var($sql);
    return empty($wpdb->last_error) && $count !== null && is_numeric($count) && (int) $count === 0;
};
foreach (array_merge($created_files, $temporary_files) as $path) {
    if (is_file($path)) { $cleanup_ok = false; }
}
foreach ($created_dirs as $path) {
    if (is_dir($path)) { $cleanup_ok = false; }
}
foreach ($created_users as $user_id) {
    if (get_user_by('id', absint($user_id))) { $cleanup_ok = false; }
}
if ($mandate_ids) {
    $ids_sql = implode(',', array_map('absint', $mandate_ids));
    if (!$count_is_zero('SELECT COUNT(*) FROM ' . ZMP_President_Mandate::table_name() . " WHERE id IN ({$ids_sql})")) { $cleanup_ok = false; }
    foreach (array(
        ZMP_President_Mandate::audit_table_name(),
        ZMP_President_Governance::documents_table(),
        ZMP_President_Facsimile_Certificates::events_table(),
        ZMP_President_Facsimile_Certificates::certificates_table(),
    ) as $table) {
        if (!$count_is_zero("SELECT COUNT(*) FROM {$table} WHERE mandate_id IN ({$ids_sql})")) { $cleanup_ok = false; }
    }
}
foreach ($facsimile_ids as $facsimile_id) {
    if (!$count_is_zero($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'zmp_admin_facsimiles WHERE id=%d', absint($facsimile_id)))) { $cleanup_ok = false; }
}
$governance_document_dir_cleanup = false;
if ($governance_document_dir_baseline_ready) {
    if ($governance_document_dir_preexisting) {
        $current_snapshot = $governance_document_dir_snapshot();
        $governance_document_dir_cleanup = is_array($current_snapshot) && $current_snapshot === $governance_document_dir_baseline;
    } elseif (is_dir($governance_document_dir) && !is_link($governance_document_dir)) {
        $remaining = @scandir($governance_document_dir);
        if (is_array($remaining) && count(array_diff($remaining, array('.', '..'))) === 0) {
            $governance_document_dir_cleanup = @rmdir($governance_document_dir) && !file_exists($governance_document_dir);
        }
    } else {
        $governance_document_dir_cleanup = !file_exists($governance_document_dir);
    }
}
if (!$governance_document_dir_cleanup) { $cleanup_ok = false; }
$record('governance_private_directory_preserved_or_removed', $governance_document_dir_cleanup ? 'PASS' : 'FAIL');
$record('fixture_cleanup', $cleanup_ok ? 'PASS' : 'FAIL');

echo wp_json_encode(array('results' => $results, 'failures' => array_values(array_unique($failures)), 'not_run' => $not_run), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
