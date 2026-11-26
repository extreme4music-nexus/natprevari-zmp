<?php
if (!defined('ABSPATH')) { exit; }

/** Private election records and append-only President obligation history. */
class ZMP_President_Governance {
    const SCHEMA_VERSION = '2';
    const MAX_DOCUMENT_BYTES = 15728640;
    const NONCE_FIELD = '_zmp_president_governance_nonce';

    public static function documents_table() { global $wpdb; return $wpdb->prefix . 'zmp_president_documents'; }
    public static function obligations_table() { global $wpdb; return $wpdb->prefix . 'zmp_president_obligation_versions'; }
    public static function acknowledgments_table() { global $wpdb; return $wpdb->prefix . 'zmp_president_obligation_acknowledgements'; }

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_install'), 2);
        add_action('admin_post_zmp_president_governance_upload', array(__CLASS__, 'handle_upload'));
        add_action('admin_post_zmp_president_governance_archive', array(__CLASS__, 'handle_archive'));
        add_action('admin_post_zmp_president_governance_publish', array(__CLASS__, 'handle_publish'));
        add_action('admin_post_zmp_president_governance_acknowledge', array(__CLASS__, 'handle_acknowledge'));
        add_action('admin_post_zmp_president_governance_download', array(__CLASS__, 'handle_download'));
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $documents = self::documents_table();
        $obligations = self::obligations_table();
        $acks = self::acknowledgments_table();
        dbDelta("CREATE TABLE {$documents} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mandate_id bigint(20) unsigned NOT NULL,
            president_user_id bigint(20) unsigned NOT NULL,
            private_path text NOT NULL,
            original_name varchar(255) NOT NULL,
            mime_type varchar(100) NOT NULL,
            file_size bigint(20) unsigned NOT NULL,
            sha256 char(64) NOT NULL,
            encryption_version smallint(5) unsigned NOT NULL DEFAULT 0,
            cipher_sha256 char(64) NOT NULL DEFAULT '',
            stored_size bigint(20) unsigned NOT NULL DEFAULT 0,
            legacy_plain_path text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            archived_by bigint(20) unsigned NOT NULL DEFAULT 0,
            archived_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY mandate_status (mandate_id,status),
            KEY president_user_id (president_user_id)
        ) {$collate} ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$obligations} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            version_label varchar(100) NOT NULL,
            text longtext NOT NULL,
            text_sha256 char(64) NOT NULL,
            board_reference text NOT NULL,
            legal_reference text NOT NULL,
            published_by bigint(20) unsigned NOT NULL,
            published_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY version_label (version_label),
            KEY published_at (published_at)
        ) {$collate} ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$acks} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mandate_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            obligation_version_id bigint(20) unsigned NOT NULL,
            obligation_sha256 char(64) NOT NULL,
            acknowledged_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY mandate_id (mandate_id),
            KEY user_id (user_id),
            KEY obligation_version_id (obligation_version_id),
            KEY acknowledged_at (acknowledged_at)
        ) {$collate} ENGINE=InnoDB;");
        $ok = self::tables_exist();
        if ($ok) { update_option('zmp_president_governance_schema_version', self::SCHEMA_VERSION, false); }
        return $ok;
    }

    public static function maybe_install() {
        if (get_option('zmp_president_governance_schema_version') === self::SCHEMA_VERSION && self::tables_exist()) { return true; }
        return self::install();
    }

    private static function tables_exist() {
        global $wpdb;
        foreach (array(self::documents_table(), self::obligations_table(), self::acknowledgments_table()) as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) { return false; }
        }
        return true;
    }

    private static function error($code, $message = 'Барањето не може да се изврши.') { return new WP_Error($code, $message); }
    private static function is_admin() { return is_user_logged_in() && current_user_can('manage_options'); }
    private static function current_user_id() { return get_current_user_id(); }
    private static function transaction_start() { global $wpdb; return false !== $wpdb->query('START TRANSACTION'); }
    private static function transaction_finish($commit) {
        global $wpdb;
        return false !== $wpdb->query($commit ? 'COMMIT' : 'ROLLBACK');
    }

    private static function active_mandate($user_id) {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id || !class_exists('ZMP_President_Mandate')) { return null; }
        $today = current_time('Y-m-d');
        return $wpdb->get_row($wpdb->prepare(
            'SELECT id,user_id,start_date,end_date,status,predecessor_mandate_id,predecessor_user_id FROM ' . ZMP_President_Mandate::table_name() . ' WHERE user_id=%d AND status=%s AND start_date<=%s AND end_date>=%s ORDER BY start_date DESC,id DESC LIMIT 1',
            $user_id, 'active', $today, $today
        ));
    }

    /**
     * Return the directly linked predecessor's official document scope only
     * for the currently effective President. A mandate with a scheduled or
     * effective successor remains limited to its own records. The mandate
     * relation and recorded predecessor user must agree; query failures fail
     * closed.
     */
    private static function predecessor_archive_scope($current_mandate) {
        global $wpdb;
        $current_id = is_object($current_mandate) ? absint($current_mandate->id ?? 0) : 0;
        $predecessor_id = is_object($current_mandate) ? absint($current_mandate->predecessor_mandate_id ?? 0) : 0;
        $predecessor_user_id = is_object($current_mandate) ? absint($current_mandate->predecessor_user_id ?? 0) : 0;
        if (!$current_id || !$predecessor_id || !$predecessor_user_id || !class_exists('ZMP_President_Mandate')) { return false; }

        $successor = self::successor_transition_for_mandate($current_id);
        if (is_wp_error($successor)) { return $successor; }
        if ($successor) { return false; }

        $wpdb->last_error = '';
        $predecessor = $wpdb->get_row($wpdb->prepare(
            'SELECT id,user_id FROM ' . ZMP_President_Mandate::table_name() . ' WHERE id=%d AND user_id=%d LIMIT 1',
            $predecessor_id,
            $predecessor_user_id
        ));
        if (!empty($wpdb->last_error)) { return self::error('governance_mandate_unavailable'); }
        if (!$predecessor || (int) $predecessor->id !== $predecessor_id || (int) $predecessor->user_id !== $predecessor_user_id) { return false; }

        return array('mandate_id'=>$predecessor_id, 'user_id'=>$predecessor_user_id);
    }

    private static function can_read_as_president() {
        $user_id = self::current_user_id();
        $user = $user_id ? get_user_by('id', $user_id) : false;
        if (!($user instanceof WP_User)
            || !ZMP_Mentor_School_Profile::has_role_stack($user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            || !user_can($user, 'view_zmp_president_dashboard')) {
            return false;
        }
        return self::active_mandate($user_id);
    }

    /**
     * A sitting President who is handing over to a scheduled successor may read
     * only records owned by that mandate. Database errors fail closed.
     */
    private static function successor_transition_for_mandate($mandate_id) {
        global $wpdb;
        $mandate_id = absint($mandate_id);
        if (!$mandate_id || !class_exists('ZMP_President_Mandate')) { return self::error('governance_mandate_unavailable'); }
        $today = current_time('Y-m-d');
        $wpdb->last_error = '';
        $successor = $wpdb->get_row($wpdb->prepare(
            'SELECT id,user_id,start_date,status FROM ' . ZMP_President_Mandate::table_name() . " WHERE predecessor_mandate_id=%d AND (status='scheduled' OR (status='active' AND start_date<=%s AND end_date>=%s)) ORDER BY start_date ASC,id ASC LIMIT 1",
            $mandate_id,
            $today,
            $today
        ));
        if (!empty($wpdb->last_error)) { return self::error('governance_mandate_unavailable'); }
        return $successor;
    }

    /** Allowlisted action URL for forms; nonce creation remains in the view. */
    public static function form_action_url($action) {
        $allowed = array('upload','archive','publish','acknowledge','download');
        $action = sanitize_key((string) $action);
        if (!in_array($action, $allowed, true)) { return ''; }
        return add_query_arg('action', 'zmp_president_governance_' . $action, admin_url('admin-post.php'));
    }

    public static function nonce_action($action) {
        $url = self::form_action_url($action);
        return $url ? 'zmp_president_governance_' . sanitize_key((string) $action) : '';
    }

    public static function download_nonce_action($document_id) {
        $document_id = absint($document_id);
        return $document_id ? self::nonce_action('download') . '_' . $document_id : '';
    }

    public static function admin_upload_document($mandate_id, $file_array) {
        global $wpdb;
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        if (!self::tables_exist()) { return self::error('governance_schema_unavailable'); }
        $mandate_id = absint($mandate_id);
        $mandate = $mandate_id ? $wpdb->get_row($wpdb->prepare('SELECT id,user_id FROM ' . ZMP_President_Mandate::table_name() . ' WHERE id=%d', $mandate_id)) : null;
        if (!$mandate || absint($mandate->user_id) < 1 || !get_user_by('id', absint($mandate->user_id))) { return self::error('governance_mandate_invalid'); }
        if (!is_array($file_array) || (int) ($file_array['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return self::error('governance_document_required'); }
        $tmp = isset($file_array['tmp_name']) ? (string) $file_array['tmp_name'] : '';
        $size = is_file($tmp) ? (int) filesize($tmp) : 0;
        $name = sanitize_file_name(wp_unslash((string) ($file_array['name'] ?? '')));
        if (!$tmp || !is_readable($tmp) || $size < 1 || $size > self::MAX_DOCUMENT_BYTES || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
            return self::error('governance_document_invalid');
        }
        $header = @file_get_contents($tmp, false, null, 0, 8);
        if (!is_string($header) || strpos($header, '%PDF-') !== 0) { return self::error('governance_document_invalid'); }
        $plain = @file_get_contents($tmp);
        $sha = is_string($plain) ? hash('sha256', $plain) : false;
        if (!is_string($plain) || strlen($plain) !== $size || !is_string($sha) || !preg_match('/^[a-f0-9]{64}$/', $sha)) { return self::error('governance_document_integrity_failed'); }
        $encrypted = self::encrypt_document_bytes($plain, $mandate_id, $name ?: 'election-document.pdf');
        if (is_wp_error($encrypted)) { return $encrypted; }
        $dir = self::private_directory();
        if (is_wp_error($dir)) { return $dir; }
        try { $key = bin2hex(random_bytes(24)) . '.pdf.enc'; }
        catch (Throwable $e) { return self::error('governance_document_store_failed'); }
        $path = trailingslashit($dir) . $key;
        $written = @file_put_contents($path, $encrypted['bytes'], LOCK_EX);
        if ($written !== $encrypted['stored_size'] || !@chmod($path, 0600) || !is_file($path) || (int) filesize($path) !== $encrypted['stored_size']
            || !hash_equals($encrypted['cipher_sha256'], (string) @hash_file('sha256', $path))) {
            @unlink($path); return self::error('governance_document_integrity_failed');
        }
        if (!self::transaction_start()) { @unlink($path); return self::error('governance_document_store_failed'); }
        $inserted = $wpdb->insert(self::documents_table(), array(
            'mandate_id' => $mandate_id, 'president_user_id' => absint($mandate->user_id), 'private_path' => $path,
            'original_name' => $name ?: 'election-document.pdf', 'mime_type' => 'application/pdf', 'file_size' => $size,
            'sha256' => $sha, 'encryption_version' => 1, 'cipher_sha256' => $encrypted['cipher_sha256'],
            'stored_size' => $encrypted['stored_size'], 'legacy_plain_path' => '',
            'status' => 'active', 'created_by' => self::current_user_id(), 'created_at' => current_time('mysql'),
        ), array('%d','%d','%s','%s','%s','%d','%s','%d','%s','%d','%s','%s','%d','%s'));
        if (!$inserted) {
            $rolled_back = self::transaction_finish(false);
            if ($rolled_back) { @unlink($path); return self::error('governance_document_store_failed'); }
            return self::error('governance_document_commit_uncertain');
        }
        $id = (int) $wpdb->insert_id;
        if (!self::audit($mandate_id, absint($mandate->user_id), 'election_document_uploaded', array('document_id' => $id, 'sha256' => $sha, 'file_size' => $size, 'encryption_version' => 1))) {
            if (self::transaction_finish(false)) { @unlink($path); return self::error('governance_document_audit_failed'); }
            return self::error('governance_document_commit_uncertain');
        }
        if (!self::transaction_finish(true)) { return self::error('governance_document_commit_uncertain'); }
        return $id;
    }

    public static function admin_archive_document($document_id) {
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        return self::archive_document_internal(absint($document_id), false, 0, null);
    }

    private static function admin_archive_document_legacy($document_id) {
        global $wpdb;
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        $document_id = absint($document_id);
        $doc = $document_id ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::documents_table() . ' WHERE id=%d', $document_id)) : null;
        if (!$doc) { return self::error('governance_document_not_found'); }
        if ($doc->status === 'archived' && (int) $doc->encryption_version === 1 && empty($doc->legacy_plain_path)) { return true; }
        if (!self::transaction_start()) { return self::error('governance_document_archive_failed'); }
        $updated = $wpdb->update(self::documents_table(), array('status' => 'archived', 'archived_by' => self::current_user_id(), 'archived_at' => current_time('mysql')), array('id' => $document_id, 'status' => 'active'), array('%s','%d','%s'), array('%d','%s'));
        if (1 !== $updated) {
            return self::error(self::transaction_finish(false) ? 'governance_document_archive_failed' : 'governance_document_archive_commit_uncertain');
        }
        if (!self::audit((int) $doc->mandate_id, (int) $doc->president_user_id, 'election_document_archived', array('document_id' => $document_id, 'sha256' => $doc->sha256))) {
            return self::error(self::transaction_finish(false) ? 'governance_document_archive_failed' : 'governance_document_archive_commit_uncertain');
        }
        if (!self::transaction_finish(true)) { return self::error('governance_document_archive_commit_uncertain'); }
        return true;
    }

    /** Admin receives all metadata; President metadata is always scoped to its mandate owner. */
    public static function president_documents($user_id = 0) {
        global $wpdb;
        if (!self::tables_exist()) { return self::error('governance_schema_unavailable'); }
        if (self::is_admin()) {
            return $wpdb->get_results('SELECT id,mandate_id,president_user_id,original_name,mime_type,file_size,sha256,status,created_by,created_at,archived_by,archived_at FROM ' . self::documents_table() . ' ORDER BY created_at DESC,id DESC');
        }
        if (!class_exists('ZMP_President_Mandate') || !is_callable(array('ZMP_President_Mandate', 'with_governance_document_read_lock'))) {
            return self::error('governance_mandate_unavailable');
        }
        return ZMP_President_Mandate::with_governance_document_read_lock(static function () use ($wpdb) {
            $mandate = self::can_read_as_president();
            if (!$mandate) { return self::error('governance_forbidden'); }
            $successor = self::successor_transition_for_mandate((int) $mandate->id);
            if (is_wp_error($successor)) { return $successor; }
            if ($successor && 'active' === (string) $successor->status) { return self::error('governance_forbidden'); }
            $archive_scope = self::predecessor_archive_scope($mandate);
            if (is_wp_error($archive_scope)) { return $archive_scope; }
            $sql = 'SELECT id,mandate_id,president_user_id,original_name,mime_type,file_size,sha256,status,created_by,created_at,archived_by,archived_at FROM ' . self::documents_table() . ' WHERE (mandate_id=%d AND president_user_id=%d)';
            $values = array((int) $mandate->id, self::current_user_id());
            if (is_array($archive_scope)) {
                $sql .= ' OR (mandate_id=%d AND president_user_id=%d)';
                $values[] = (int) $archive_scope['mandate_id'];
                $values[] = (int) $archive_scope['user_id'];
            }
            $sql .= ' ORDER BY created_at DESC,id DESC';
            return $wpdb->get_results($wpdb->prepare($sql, $values));
        });
    }

    /** Retrieve only an authorized document for decryption or download. */
    public static function get_accessible_document($document_id) {
        global $wpdb;
        $document_id = absint($document_id);
        if (!$document_id) { return self::error('governance_document_not_found'); }
        $mandate = self::can_read_as_president();
        if (!$mandate) { return self::error('governance_forbidden'); }
        $doc = $wpdb->get_row($wpdb->prepare('SELECT id,mandate_id,president_user_id,original_name,mime_type,file_size,sha256,encryption_version,cipher_sha256,stored_size,legacy_plain_path,status,created_at FROM ' . self::documents_table() . ' WHERE id=%d', $document_id));
        if (!$doc) { return self::error('governance_document_unavailable'); }
        $successor = self::successor_transition_for_mandate((int) $mandate->id);
        if (is_wp_error($successor)) { return $successor; }
        if ($successor && 'active' === (string) $successor->status) { return self::error('governance_forbidden'); }
        if ((int) $doc->mandate_id === (int) $mandate->id && (int) $doc->president_user_id === self::current_user_id()) {
            return $doc;
        }
        $archive_scope = self::predecessor_archive_scope($mandate);
        if (is_wp_error($archive_scope)) { return $archive_scope; }
        if (!is_array($archive_scope)
            || (int) $doc->mandate_id !== (int) $archive_scope['mandate_id']
            || (int) $doc->president_user_id !== (int) $archive_scope['user_id']) {
            return self::error('governance_document_unavailable');
        }
        return $doc;
    }

    /** Return the PDF only after current-President authorization and integrity checks. */
    public static function document_content($document_id) {
        if (!class_exists('ZMP_President_Mandate') || !is_callable(array('ZMP_President_Mandate', 'with_governance_document_read_lock'))) {
            return self::error('governance_mandate_unavailable');
        }
        return ZMP_President_Mandate::with_governance_document_read_lock(static function () use ($document_id) {
            global $wpdb;
            $authorized = self::get_accessible_document($document_id);
            if (is_wp_error($authorized)) { return $authorized; }
            if (!in_array((string) $authorized->status, array('active','archived'), true)) { return self::error('governance_document_unavailable'); }
            $doc = $wpdb->get_row($wpdb->prepare(
                'SELECT id,mandate_id,private_path,original_name,file_size,sha256,encryption_version,cipher_sha256,stored_size FROM ' . self::documents_table() . ' WHERE id=%d',
                absint($document_id)
            ));
            $path = $doc ? self::safe_document_path((string) $doc->private_path) : false;
            if (!$doc || !$path || !is_file($path)) { return self::error('governance_document_unavailable'); }
            $plain_size = (int) $doc->file_size;
            $stored_size = (int) filesize($path);
            if ($plain_size < 1 || $plain_size > self::MAX_DOCUMENT_BYTES || $stored_size < 1 || $stored_size > self::MAX_DOCUMENT_BYTES + 33
                || ((int) $doc->encryption_version === 1 && $stored_size !== (int) $doc->stored_size)
                || ((int) $doc->encryption_version === 0 && $stored_size !== $plain_size)) {
                return self::error('governance_document_integrity_failed');
            }
            $stored = @file_get_contents($path);
            if (!is_string($stored)) { return self::error('governance_document_integrity_failed'); }
            if ((int) $doc->encryption_version === 1) {
                if (strlen($stored) !== (int) $doc->stored_size || !preg_match('/^[a-f0-9]{64}$/', (string) $doc->cipher_sha256)
                    || !hash_equals((string) $doc->cipher_sha256, hash('sha256', $stored))) {
                    return self::error('governance_document_integrity_failed');
                }
                $plain = self::decrypt_document_bytes($stored, (int) $doc->mandate_id, (string) $doc->original_name, (int) $doc->file_size);
                if (is_wp_error($plain)) { return $plain; }
            } elseif ((int) $doc->encryption_version === 0) {
                if (strlen($stored) !== (int) $doc->file_size || !preg_match('/^[a-f0-9]{64}$/', (string) $doc->sha256)
                    || !hash_equals((string) $doc->sha256, hash('sha256', $stored))) {
                    return self::error('governance_document_integrity_failed');
                }
                $plain = $stored;
            } else {
                return self::error('governance_document_encryption_unsupported');
            }
            if (strlen($plain) !== (int) $doc->file_size || !hash_equals((string) $doc->sha256, hash('sha256', $plain)) || strpos($plain, '%PDF-') !== 0) {
                return self::error('governance_document_integrity_failed');
            }
            return $plain;
        });
    }

    public static function stream_document($document_id) {
        global $wpdb;
        $authorized = self::get_accessible_document($document_id);
        if (is_wp_error($authorized)) { wp_die('Документот не е достапен.', 'Нема пристап', array('response' => 403)); }
        $plain = self::document_content($document_id);
        if (is_wp_error($plain)) { wp_die('Документот не е достапен или не помина проверка на интегритет.', 'Недостапно', array('response' => 404)); }
        nocache_headers();
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($plain));
        header('Content-Disposition: attachment; filename="' . rawurlencode(sanitize_file_name($authorized->original_name)) . '"');
        echo $plain;
        exit;
    }

    public static function publish_obligation($version_label, $text, $board_reference, $legal_reference) {
        global $wpdb;
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        if (!is_string($version_label) || !is_string($text) || !is_string($board_reference) || !is_string($legal_reference)) { return self::error('governance_obligation_invalid'); }
        $label = trim(sanitize_text_field($version_label));
        $plain_text = sanitize_textarea_field($text);
        $board = trim(sanitize_textarea_field($board_reference));
        $legal = trim(sanitize_textarea_field($legal_reference));
        if ($label === '' || strlen($label) > 100 || trim($plain_text) === '' || strlen($plain_text) > 200000 || $board === '' || strlen($board) > 4000 || $legal === '' || strlen($legal) > 4000) {
            return self::error('governance_obligation_approval_required');
        }
        $hash = hash('sha256', $plain_text);
        if (!self::transaction_start()) { return self::error('governance_obligation_write_failed'); }
        $ok = $wpdb->insert(self::obligations_table(), array(
            'version_label' => $label, 'text' => $plain_text, 'text_sha256' => $hash, 'board_reference' => $board,
            'legal_reference' => $legal, 'published_by' => self::current_user_id(), 'published_at' => current_time('mysql'),
        ), array('%s','%s','%s','%s','%s','%d','%s'));
        if (!$ok) {
            $exists = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::obligations_table() . ' WHERE version_label=%s', $label));
            $rolled_back = self::transaction_finish(false);
            if (!$rolled_back) { return self::error('governance_obligation_commit_uncertain'); }
            return self::error($exists ? 'governance_obligation_version_exists' : 'governance_obligation_write_failed');
        }
        $id = (int) $wpdb->insert_id;
        if (!self::audit(0, 0, 'obligation_published', array('version_id' => $id, 'version_label' => $label, 'sha256' => $hash))) {
            return self::error(self::transaction_finish(false) ? 'governance_obligation_write_failed' : 'governance_obligation_commit_uncertain');
        }
        if (!self::transaction_finish(true)) { return self::error('governance_obligation_commit_uncertain'); }
        return $id;
    }

    public static function current_obligation() {
        global $wpdb;
        if (!self::is_admin() && !self::can_read_as_president()) { return self::error('governance_forbidden'); }
        return $wpdb->get_row('SELECT id,version_label,text,text_sha256,board_reference,legal_reference,published_by,published_at FROM ' . self::obligations_table() . ' ORDER BY id DESC LIMIT 1');
    }

    public static function obligation_history() {
        global $wpdb;
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        return $wpdb->get_results('SELECT id,version_label,text,text_sha256,board_reference,legal_reference,published_by,published_at FROM ' . self::obligations_table() . ' ORDER BY id DESC');
    }

    public static function acknowledge_current() {
        global $wpdb;
        if (!is_user_logged_in() || !self::can_read_as_president()) { return self::error('governance_forbidden'); }
        $user_id = self::current_user_id();
        $mandate = self::active_mandate($user_id);
        $obligation = self::current_obligation();
        if (!$mandate || !$obligation || is_wp_error($obligation) || empty($obligation->id) || !preg_match('/^[a-f0-9]{64}$/', (string) $obligation->text_sha256)) {
            return self::error('governance_obligation_unavailable');
        }
        if (!self::transaction_start()) { return self::error('governance_acknowledgment_failed'); }
        $ok = $wpdb->insert(self::acknowledgments_table(), array(
            'mandate_id' => (int) $mandate->id, 'user_id' => $user_id, 'obligation_version_id' => (int) $obligation->id,
            'obligation_sha256' => (string) $obligation->text_sha256, 'acknowledged_at' => current_time('mysql'),
        ), array('%d','%d','%d','%s','%s'));
        if (!$ok) {
            return self::error(self::transaction_finish(false) ? 'governance_acknowledgment_failed' : 'governance_acknowledgment_commit_uncertain');
        }
        $id = (int) $wpdb->insert_id;
        if (!self::audit((int) $mandate->id, $user_id, 'obligation_acknowledged', array('acknowledgment_id' => $id, 'version_id' => (int) $obligation->id, 'sha256' => (string) $obligation->text_sha256, 'acknowledged_at' => current_time('mysql')))) {
            return self::error(self::transaction_finish(false) ? 'governance_acknowledgment_failed' : 'governance_acknowledgment_commit_uncertain');
        }
        if (!self::transaction_finish(true)) { return self::error('governance_acknowledgment_commit_uncertain'); }
        return $id;
    }

    public static function acknowledgment_history($user_id = 0) {
        global $wpdb;
        if (self::is_admin()) {
            return $wpdb->get_results('SELECT a.id,a.mandate_id,a.user_id,a.obligation_version_id,a.obligation_sha256,a.acknowledged_at,v.version_label FROM ' . self::acknowledgments_table() . ' a LEFT JOIN ' . self::obligations_table() . ' v ON v.id=a.obligation_version_id ORDER BY a.acknowledged_at DESC,a.id DESC');
        }
        $mandate = self::can_read_as_president();
        if (!$mandate) { return self::error('governance_forbidden'); }
        return $wpdb->get_results($wpdb->prepare('SELECT a.id,a.mandate_id,a.user_id,a.obligation_version_id,a.obligation_sha256,a.acknowledged_at,v.version_label FROM ' . self::acknowledgments_table() . ' a LEFT JOIN ' . self::obligations_table() . ' v ON v.id=a.obligation_version_id WHERE a.mandate_id=%d AND a.user_id=%d ORDER BY a.acknowledged_at DESC,a.id DESC', (int) $mandate->id, self::current_user_id()));
    }

    private static function audit($mandate_id, $user_id, $event, $data) {
        return class_exists('ZMP_President_Mandate')
            && ZMP_President_Mandate::record_governance_event((int) $mandate_id, (int) $user_id, $event, $data);
    }

    private static function private_directory() {
        $root = realpath(!empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
        $parent = $root ? realpath(dirname($root)) : false;
        if (!$root || !$parent) { return self::error('governance_document_storage_unsafe'); }
        $dir = trailingslashit($parent) . 'zmp-president-documents-' . substr(hash('sha256', home_url('/')), 0, 12);
        if (!is_dir($dir) && !wp_mkdir_p($dir)) { return self::error('governance_document_storage_failed'); }
        $real = realpath($dir);
        $normalized_root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $normalized_dir = trailingslashit(str_replace('\\', '/', (string) $real));
        if (!$real || strpos($normalized_dir, $normalized_root) === 0) { return self::error('governance_document_storage_unsafe'); }
        @chmod($real, 0700);
        return $real;
    }

    private static function encryption_key() {
        if (!defined('ZMP_PRESIDENT_DOCUMENT_KEY') || !is_string(ZMP_PRESIDENT_DOCUMENT_KEY)
            || !preg_match('/^[a-f0-9]{64}$/i', ZMP_PRESIDENT_DOCUMENT_KEY)) {
            return self::error('governance_encryption_key_unavailable');
        }
        $key = hex2bin(strtolower(ZMP_PRESIDENT_DOCUMENT_KEY));
        return is_string($key) && strlen($key) === 32 ? $key : self::error('governance_encryption_key_unavailable');
    }

    public static function encryption_ready() {
        if (is_wp_error(self::encryption_key()) || !function_exists('openssl_encrypt') || !function_exists('openssl_decrypt') || !function_exists('openssl_get_cipher_methods')) { return false; }
        $methods = array_map('strtolower', openssl_get_cipher_methods());
        return in_array('aes-256-gcm', $methods, true);
    }

    /** Admin-only retention status; exposes no key, file path, or document content. */
    public static function retention_status() {
        global $wpdb;
        if (!self::is_admin()) { return self::error('governance_forbidden'); }
        if (!self::tables_exist() || !class_exists('ZMP_President_Mandate')) { return self::error('governance_schema_unavailable'); }
        $due = $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . self::documents_table() . ' d INNER JOIN ' . ZMP_President_Mandate::table_name() . ' s ON s.predecessor_mandate_id=d.mandate_id WHERE d.status=%s AND s.handover_deadline<=%s',
            'active', current_time('Y-m-d')
        ));
        $cleanup = $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . self::documents_table() . ' WHERE status=%s AND legacy_plain_path<>%s',
            'archived', ''
        ));
        if (!is_numeric($due) || !is_numeric($cleanup)) { return self::error('governance_retention_status_unavailable'); }
        return array('encryption_ready' => self::encryption_ready(), 'due_documents' => (int) $due, 'plaintext_cleanup_pending' => (int) $cleanup);
    }

    private static function document_aad($mandate_id, $original_name, $plain_size) {
        return 'zmp-president-election-document|1|' . absint($mandate_id) . '|' . sanitize_file_name((string) $original_name) . '|' . absint($plain_size);
    }

    private static function encrypt_document_bytes($plain, $mandate_id, $original_name) {
        $key = self::encryption_key();
        if (is_wp_error($key)) { return $key; }
        if (!function_exists('openssl_encrypt') || !is_string($plain) || strlen($plain) < 1 || strlen($plain) > self::MAX_DOCUMENT_BYTES) {
            return self::error('governance_encryption_unavailable');
        }
        try { $nonce = random_bytes(12); }
        catch (Throwable $e) { return self::error('governance_encryption_failed'); }
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, self::document_aad($mandate_id, $original_name, strlen($plain)), 16);
        if (!is_string($cipher) || strlen($nonce) !== 12 || strlen($tag) !== 16) { return self::error('governance_encryption_failed'); }
        $stored = "ZMPD\x01" . $nonce . $tag . $cipher;
        return array('bytes' => $stored, 'cipher_sha256' => hash('sha256', $stored), 'stored_size' => strlen($stored));
    }

    private static function decrypt_document_bytes($stored, $mandate_id, $original_name, $plain_size) {
        $key = self::encryption_key();
        if (is_wp_error($key)) { return $key; }
        if (!function_exists('openssl_decrypt') || !is_string($stored) || strlen($stored) < 34 || substr($stored, 0, 5) !== "ZMPD\x01") {
            return self::error('governance_document_integrity_failed');
        }
        $nonce = substr($stored, 5, 12);
        $tag = substr($stored, 17, 16);
        $cipher = substr($stored, 33);
        if (strlen($nonce) !== 12 || strlen($tag) !== 16 || strlen($cipher) < 1) { return self::error('governance_document_integrity_failed'); }
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, self::document_aad($mandate_id, $original_name, $plain_size));
        return is_string($plain) ? $plain : self::error('governance_document_integrity_failed');
    }

    private static function safe_document_path($candidate) {
        $dir = self::private_directory();
        $path = realpath((string) $candidate);
        if (!$path || is_wp_error($dir)) { return false; }
        $normalized_path = wp_normalize_path($path);
        $normalized_dir = trailingslashit(wp_normalize_path(realpath($dir) ?: $dir));
        if (DIRECTORY_SEPARATOR === '\\') { $normalized_path = strtolower($normalized_path); $normalized_dir = strtolower($normalized_dir); }
        return strpos($normalized_path, $normalized_dir) === 0 ? $path : false;
    }

    private static function write_encrypted_document_file($bytes) {
        $dir = self::private_directory();
        if (is_wp_error($dir)) { return $dir; }
        try { $name = bin2hex(random_bytes(24)) . '.pdf.enc'; }
        catch (Throwable $e) { return self::error('governance_document_store_failed'); }
        $path = trailingslashit($dir) . $name;
        $written = @file_put_contents($path, $bytes, LOCK_EX);
        if ($written !== strlen($bytes) || !@chmod($path, 0600) || !is_file($path)
            || (int) filesize($path) !== strlen($bytes) || !hash_equals(hash('sha256', $bytes), (string) @hash_file('sha256', $path))) {
            @unlink($path);
            return self::error('governance_document_store_failed');
        }
        return $path;
    }

    private static function cleanup_legacy_plaintext($document_id, $path) {
        global $wpdb;
        if ($path === '') { return true; }
        $safe = self::safe_document_path($path);
        if ($safe && !@unlink($safe)) { return false; }
        if (!$safe && file_exists($path)) { return false; }
        $updated = $wpdb->update(self::documents_table(), array('legacy_plain_path' => ''), array('id' => absint($document_id), 'legacy_plain_path' => $path), array('%s'), array('%d','%s'));
        return 1 === $updated || 0 === $updated;
    }

    private static function archive_document_internal($document_id, $automatic, $successor_mandate_id, $effective_date = null) {
        global $wpdb;
        $doc = $document_id ? $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::documents_table() . ' WHERE id=%d', absint($document_id))) : null;
        if (!$doc) { return self::error('governance_document_not_found'); }
        if (!in_array((string) $doc->status, array('active','archived'), true)) { return self::error('governance_document_unavailable'); }
        if ($doc->status === 'archived' && (int) $doc->encryption_version === 1) {
            if (!empty($doc->legacy_plain_path) && !self::cleanup_legacy_plaintext($document_id, (string) $doc->legacy_plain_path)) {
                return self::error('governance_document_plaintext_cleanup_pending');
            }
            return true;
        }
        $path = self::safe_document_path((string) $doc->private_path);
        if (!$path || !is_file($path)) { return self::error('governance_document_unavailable'); }
        $stored = @file_get_contents($path);
        if (!is_string($stored)) { return self::error('governance_document_integrity_failed'); }
        $new_path = (string) $doc->private_path;
        $cipher_sha = (string) ($doc->cipher_sha256 ?? '');
        $stored_size = (int) ($doc->stored_size ?? 0);
        $encryption_version = (int) ($doc->encryption_version ?? 0);
        $legacy_path = (string) ($doc->legacy_plain_path ?? '');
        if ($encryption_version === 0) {
            if (strlen($stored) !== (int) $doc->file_size || !hash_equals((string) $doc->sha256, hash('sha256', $stored)) || strpos($stored, '%PDF-') !== 0) {
                return self::error('governance_document_integrity_failed');
            }
            $encrypted = self::encrypt_document_bytes($stored, (int) $doc->mandate_id, (string) $doc->original_name);
            if (is_wp_error($encrypted)) { return $encrypted; }
            $new_path = self::write_encrypted_document_file($encrypted['bytes']);
            if (is_wp_error($new_path)) { return $new_path; }
            $cipher_sha = $encrypted['cipher_sha256'];
            $stored_size = $encrypted['stored_size'];
            $encryption_version = 1;
            $legacy_path = (string) $doc->private_path;
        } elseif ($encryption_version === 1) {
            if (strlen($stored) !== $stored_size || !preg_match('/^[a-f0-9]{64}$/', $cipher_sha) || !hash_equals($cipher_sha, hash('sha256', $stored))) {
                return self::error('governance_document_integrity_failed');
            }
        } else {
            return self::error('governance_document_encryption_unsupported');
        }
        if (!self::transaction_start()) {
            if ($new_path !== (string) $doc->private_path) { @unlink($new_path); }
            return self::error('governance_document_archive_failed');
        }
        $updated = $wpdb->update(self::documents_table(), array(
            'private_path' => $new_path,
            'encryption_version' => $encryption_version,
            'cipher_sha256' => $cipher_sha,
            'stored_size' => $stored_size,
            'legacy_plain_path' => $legacy_path,
            'status' => 'archived',
            'archived_by' => $doc->status === 'archived' ? absint($doc->archived_by) : ($automatic ? 0 : self::current_user_id()),
            'archived_at' => $doc->status === 'archived' && !empty($doc->archived_at) ? $doc->archived_at : current_time('mysql'),
        ), array('id' => $document_id, 'status' => $doc->status), array('%s','%d','%s','%d','%s','%s','%d','%s'), array('%d','%s'));
        if (1 !== $updated) {
            $rolled_back = self::transaction_finish(false);
            if ($new_path !== (string) $doc->private_path && $rolled_back) { @unlink($new_path); }
            return self::error($rolled_back ? 'governance_document_archive_failed' : 'governance_document_archive_commit_uncertain');
        }
        $event_type = $automatic ? ($doc->status === 'archived' ? 'election_document_encrypted' : 'election_documents_auto_archived') : 'election_document_archived';
        $event_data = array('document_id' => $document_id, 'sha256' => $doc->sha256, 'encryption_version' => 1);
        if ($automatic && $doc->status !== 'archived') {
            $event_data['successor_mandate_id'] = absint($successor_mandate_id);
            $event_data['effective_date'] = null === $effective_date ? self::today() : (string) $effective_date;
        }
        if (!self::audit((int) $doc->mandate_id, (int) $doc->president_user_id, $event_type, $event_data)) {
            $rolled_back = self::transaction_finish(false);
            if ($new_path !== (string) $doc->private_path && $rolled_back) { @unlink($new_path); }
            return self::error($rolled_back ? 'governance_document_audit_failed' : 'governance_document_archive_commit_uncertain');
        }
        if (!self::transaction_finish(true)) { return self::error('governance_document_archive_commit_uncertain'); }
        if ($legacy_path !== '' && !self::cleanup_legacy_plaintext($document_id, $legacy_path)) {
            return self::error('governance_document_plaintext_cleanup_pending');
        }
        return true;
    }

    /** Archive predecessor records at the successor's due date; ciphertext and rows remain indefinitely. */
    public static function archive_due_predecessor_documents($today = null) {
        global $wpdb;
        $is_cron = function_exists('wp_doing_cron') && wp_doing_cron();
        if (!$is_cron && !self::is_admin()) { return self::error('governance_forbidden'); }
        $today = null === $today ? current_time('Y-m-d') : (string) $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $today)) { return self::error('governance_archive_date_invalid'); }
        $parsed_today = DateTimeImmutable::createFromFormat('!Y-m-d', $today, wp_timezone());
        $date_errors = DateTimeImmutable::getLastErrors();
        if (!$parsed_today || ($date_errors && ($date_errors['warning_count'] || $date_errors['error_count'])) || $parsed_today->format('Y-m-d') !== $today) {
            return self::error('governance_archive_date_invalid');
        }
        $due = $wpdb->get_results($wpdb->prepare(
            'SELECT d.id,d.mandate_id,d.president_user_id,s.id AS successor_mandate_id FROM ' . self::documents_table() . ' d INNER JOIN ' . ZMP_President_Mandate::table_name() . ' s ON s.predecessor_mandate_id=d.mandate_id WHERE d.mandate_id>0 AND d.status=%s AND s.predecessor_mandate_id>0 AND s.handover_deadline<=%s ORDER BY d.id ASC',
            'active',
            $today
        ));
        if (!is_array($due)) { return self::error('governance_archive_query_failed'); }
        $archived = 0;
        foreach ($due as $doc) {
            $result = self::archive_document_internal((int) $doc->id, true, (int) $doc->successor_mandate_id, $today);
            if (is_wp_error($result)) { return $result; }
            $archived++;
        }
        $archived_plaintext = $wpdb->get_results($wpdb->prepare(
            'SELECT id FROM ' . self::documents_table() . ' WHERE status=%s AND encryption_version=%d ORDER BY id ASC',
            'archived', 0
        ));
        if (!is_array($archived_plaintext)) { return self::error('governance_archive_query_failed'); }
        foreach ($archived_plaintext as $doc) {
            $result = self::archive_document_internal((int) $doc->id, true, 0, $today);
            if (is_wp_error($result)) { return $result; }
        }
        $legacy = $wpdb->get_results($wpdb->prepare(
            'SELECT id,legacy_plain_path FROM ' . self::documents_table() . ' WHERE status=%s AND encryption_version=%d AND legacy_plain_path<>%s',
            'archived', 1, ''
        ));
        if (!is_array($legacy)) { return self::error('governance_archive_query_failed'); }
        foreach ($legacy as $row) { self::cleanup_legacy_plaintext((int) $row->id, (string) $row->legacy_plain_path); }
        return $archived;
    }

    private static function redirect_status($status) {
        $referer = wp_get_referer();
        if (!$referer) { $referer = admin_url(); }
        wp_safe_redirect(add_query_arg('zmp_president_governance_status', sanitize_key($status), $referer));
        exit;
    }

    private static function result_status($result, $validation_status = 'invalid') {
        if (!is_wp_error($result)) { return 'saved'; }
        $code = $result->get_error_code();
        if (false !== strpos($code, '_commit_uncertain')) { return 'saved_with_audit_warning'; }
        if (false !== strpos($code, 'plaintext_cleanup_pending')) { return 'saved_with_audit_warning'; }
        if (false !== strpos($code, 'forbidden')) { return 'forbidden'; }
        if (false !== strpos($code, 'failed') || false !== strpos($code, 'unavailable') || false !== strpos($code, 'uncertain')) { return 'failed'; }
        return $validation_status;
    }

    private static function verify_admin_request($action) {
        check_admin_referer(self::nonce_action($action), self::NONCE_FIELD);
        return self::is_admin();
    }

    public static function handle_upload() {
        if (!self::verify_admin_request('upload')) { self::redirect_status('forbidden'); }
        $file = $_FILES['president_election_document'] ?? array();
        if (!is_array($file) || empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) { self::redirect_status('invalid'); }
        $result = self::admin_upload_document(absint($_POST['mandate_id'] ?? 0), $file);
        self::redirect_status(self::result_status($result));
    }

    public static function handle_archive() {
        if (!self::verify_admin_request('archive')) { self::redirect_status('forbidden'); }
        $result = self::admin_archive_document(absint($_POST['document_id'] ?? 0));
        self::redirect_status(self::result_status($result, 'failed'));
    }

    public static function handle_publish() {
        if (!self::verify_admin_request('publish')) { self::redirect_status('forbidden'); }
        $result = self::publish_obligation(
            sanitize_text_field(wp_unslash($_POST['version_label'] ?? '')),
            wp_unslash($_POST['text'] ?? ''),
            sanitize_textarea_field(wp_unslash($_POST['board_reference'] ?? '')),
            sanitize_textarea_field(wp_unslash($_POST['legal_reference'] ?? ''))
        );
        self::redirect_status(self::result_status($result));
    }

    public static function handle_acknowledge() {
        check_admin_referer(self::nonce_action('acknowledge'), self::NONCE_FIELD);
        $version_id = absint($_POST['obligation_version_id'] ?? 0);
        $submitted_hash = strtolower(sanitize_text_field(wp_unslash($_POST['content_sha256'] ?? '')));
        $confirmed = (string) wp_unslash($_POST['confirm_obligation'] ?? '') === '1';
        $current = self::current_obligation();
        if (!$confirmed || !$version_id || is_wp_error($current) || !$current || (int) $current->id !== $version_id
            || !preg_match('/^[a-f0-9]{64}$/', $submitted_hash) || !hash_equals((string) $current->text_sha256, $submitted_hash)) {
            self::redirect_status('invalid');
        }
        $result = self::acknowledge_current();
        if (is_wp_error($result)) {
            self::redirect_status(self::result_status($result, 'forbidden'));
        }
        self::redirect_status('acknowledged');
    }

    public static function handle_download() {
        $id = absint($_GET['document_id'] ?? 0);
        check_admin_referer(self::download_nonce_action($id), self::NONCE_FIELD);
        self::stream_document($id);
    }
}
