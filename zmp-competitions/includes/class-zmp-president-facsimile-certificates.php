<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Stores verifiable public-key authorizations for President facsimiles.
 * Private Ed25519 keys are created and retained off-server; this class never
 * accepts or stores secret-key material.
 */
class ZMP_President_Facsimile_Certificates {
    const SCHEMA_VERSION = '2';
    const NONCE_FIELD = 'zmp_president_facsimile_nonce';
    const REGISTER_ACTION = 'zmp_register_president_facsimile_certificate';
    const REVOKE_ACTION = 'zmp_revoke_president_facsimile_certificate';
    const HANDOVER_ACTION = 'zmp_decide_president_facsimile_handover';
    const MANIFEST_MAX_BYTES = 16384;
    const CERTIFICATE_USE = 'certificate_pdf';

    public static function certificates_table() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_president_facsimile_certificates';
    }

    public static function events_table() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_president_facsimile_certificate_events';
    }

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_install'), 2);
        add_action('admin_post_' . self::REGISTER_ACTION, array(__CLASS__, 'handle_register'));
        add_action('admin_post_' . self::REVOKE_ACTION, array(__CLASS__, 'handle_revoke'));
        add_action('admin_post_' . self::HANDOVER_ACTION, array(__CLASS__, 'handle_handover_decision'));
        add_action('admin_menu', array(__CLASS__, 'register_admin_page'));
    }

    public static function install() {
        global $wpdb;
        if (!function_exists('dbDelta')) { require_once ABSPATH . 'wp-admin/includes/upgrade.php'; }
        $collate = $wpdb->get_charset_collate();
        $certificates = self::certificates_table();
        $events = self::events_table();
        dbDelta("CREATE TABLE {$certificates} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mandate_id bigint(20) unsigned NOT NULL,
            president_user_id bigint(20) unsigned NOT NULL,
            serial varchar(80) NOT NULL,
            public_key_id char(64) NOT NULL,
            public_key_b64 varchar(128) NOT NULL,
            facsimile_id bigint(20) unsigned NOT NULL,
            facsimile_sha256 char(64) NOT NULL,
            permitted_use_json text NOT NULL,
            valid_from date NOT NULL,
            valid_until date NOT NULL,
            manifest_b64 longtext NOT NULL,
            signature_b64 varchar(128) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL,
            superseded_by bigint(20) unsigned NOT NULL DEFAULT 0,
            superseded_at datetime DEFAULT NULL,
            revoked_by bigint(20) unsigned NOT NULL DEFAULT 0,
            revoked_at datetime DEFAULT NULL,
            revocation_reason text NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY mandate_serial (mandate_id,serial),
            KEY mandate_status (mandate_id,status),
            KEY president_user_id (president_user_id),
            KEY facsimile_id (facsimile_id),
            KEY valid_until (valid_until)
        ) {$collate} ENGINE=InnoDB;");
        dbDelta("CREATE TABLE {$events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            certificate_id bigint(20) unsigned NOT NULL,
            mandate_id bigint(20) unsigned NOT NULL,
            actor_user_id bigint(20) unsigned NOT NULL,
            event_type varchar(24) NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY certificate_id (certificate_id),
            KEY mandate_id (mandate_id),
            KEY actor_user_id (actor_user_id),
            KEY event_type_created (event_type,created_at)
        ) {$collate} ENGINE=InnoDB;");
        // dbDelta adds missing columns and indexes but does not remove obsolete
        // indexes. The first candidate schema used mandate_key as UNIQUE,
        // which incorrectly prevented reissuing under the same public key.
        $legacy_indexes = $wpdb->get_results('SHOW INDEX FROM `' . self::certificates_table() . '`');
        if (is_array($legacy_indexes)) {
            foreach ($legacy_indexes as $index) {
                if (isset($index->Key_name) && $index->Key_name === 'mandate_key') {
                    if (false === $wpdb->query('ALTER TABLE `' . self::certificates_table() . '` DROP INDEX `mandate_key`')) {
                        delete_option('zmp_president_facsimile_certificate_schema_version');
                        return false;
                    }
                    break;
                }
            }
        }
        $ok = self::schema_is_complete();
        if ($ok) { update_option('zmp_president_facsimile_certificate_schema_version', self::SCHEMA_VERSION, false); }
        else { delete_option('zmp_president_facsimile_certificate_schema_version'); }
        return $ok;
    }

    public static function maybe_install() {
        if (get_option('zmp_president_facsimile_certificate_schema_version') === self::SCHEMA_VERSION && self::schema_is_complete()) {
            return true;
        }
        return self::install();
    }

    /** Verify the full schema before persisting the migration marker. */
    public static function schema_is_complete() {
        global $wpdb;
        if (!self::tables_exist()) { return false; }
        $required_columns = array(
            self::certificates_table() => array(
                'id','mandate_id','president_user_id','serial','public_key_id','public_key_b64','facsimile_id',
                'facsimile_sha256','permitted_use_json','valid_from','valid_until','manifest_b64','signature_b64',
                'status','created_by','created_at','superseded_by','superseded_at','revoked_by','revoked_at','revocation_reason',
            ),
            self::events_table() => array('id','certificate_id','mandate_id','actor_user_id','event_type','event_data','created_at'),
        );
        $required_indexes = array(
            self::certificates_table() => array(
                'PRIMARY'=>array(true,array('id')),
                'mandate_serial'=>array(true,array('mandate_id','serial')),
                'mandate_status'=>array(false,array('mandate_id','status')),
                'president_user_id'=>array(false,array('president_user_id')),
                'facsimile_id'=>array(false,array('facsimile_id')),
                'valid_until'=>array(false,array('valid_until')),
            ),
            self::events_table() => array(
                'PRIMARY'=>array(true,array('id')),
                'certificate_id'=>array(false,array('certificate_id')),
                'mandate_id'=>array(false,array('mandate_id')),
                'actor_user_id'=>array(false,array('actor_user_id')),
                'event_type_created'=>array(false,array('event_type','created_at')),
            ),
        );
        foreach ($required_columns as $table => $columns) {
            $rows = $wpdb->get_results('SHOW COLUMNS FROM `' . $table . '`');
            if (!is_array($rows)) { return false; }
            $found = array();
            foreach ($rows as $row) { if (isset($row->Field)) { $found[(string)$row->Field] = true; } }
            foreach ($columns as $column) { if (empty($found[$column])) { return false; } }

            $index_rows = $wpdb->get_results('SHOW INDEX FROM `' . $table . '`');
            if (!is_array($index_rows)) { return false; }
            $found_indexes = array();
            foreach ($index_rows as $index) {
                if (!isset($index->Key_name, $index->Column_name, $index->Seq_in_index, $index->Non_unique)) { continue; }
                $name = (string) $index->Key_name;
                if (!isset($found_indexes[$name])) {
                    $found_indexes[$name] = array('unique'=>(int)$index->Non_unique === 0,'columns'=>array());
                }
                $found_indexes[$name]['columns'][(int)$index->Seq_in_index] = (string)$index->Column_name;
            }
            // Version 1 created this redundant unique index, which blocked a
            // reissue using the same public key even after dropping it from
            // the dbDelta definition. Migration version 2 must remove it.
            if ($table === self::certificates_table() && isset($found_indexes['mandate_key'])) { return false; }
            foreach ($required_indexes[$table] as $name => $expected) {
                if (!isset($found_indexes[$name]) || $found_indexes[$name]['unique'] !== $expected[0]) { return false; }
                ksort($found_indexes[$name]['columns'], SORT_NUMERIC);
                if (array_values($found_indexes[$name]['columns']) !== $expected[1]) { return false; }
            }
        }
        return true;
    }

    private static function tables_exist() {
        global $wpdb;
        foreach (array(self::certificates_table(), self::events_table()) as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) !== $table) { return false; }
        }
        return true;
    }

    private static function error($code, $message = 'Барањето за сертификат не може да се изврши.') {
        return new WP_Error($code, $message);
    }

    private static function current_user_id() {
        return get_current_user_id();
    }

    public static function is_current_president() {
        if (!is_user_logged_in() || !current_user_can('view_zmp_president_dashboard')) { return false; }
        $user = wp_get_current_user();
        if (!$user instanceof WP_User || !ZMP_Mentor_School_Profile::has_role_stack((int) $user->ID, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))) { return false; }
        return class_exists('ZMP_President_Mandate')
            && ZMP_President_Mandate::user_has_active_mandate((int) $user->ID);
    }

    private static function active_mandate_for_current_president() {
        if (!self::is_current_president()) { return self::error('facsimile_certificate_president_required'); }
        return self::get_active_mandate_by_user(self::current_user_id());
    }

    /** Public, safe mandate context for the President's pending upload handler. */
    public static function current_mandate_context() {
        return self::active_mandate_for_current_president();
    }

    private static function get_active_mandate_by_user($user_id) {
        global $wpdb;
        $user_id = absint($user_id);
        if ($user_id < 1) { return self::error('facsimile_certificate_mandate_unavailable'); }
        $today = current_time('Y-m-d');
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id,user_id,start_date,end_date,predecessor_mandate_id,predecessor_user_id,handover_deadline FROM {$wpdb->prefix}zmp_president_mandates
             WHERE user_id=%d AND status='active' AND start_date<=%s AND end_date>=%s
             ORDER BY start_date DESC,id DESC LIMIT 1",
            $user_id, $today, $today
        ));
        return $row ?: self::error('facsimile_certificate_mandate_unavailable');
    }

    private static function active_facsimile_for_mandate($mandate, $allow_pending = false) {
        global $wpdb;
        if (!$mandate || is_wp_error($mandate)) { return self::error('facsimile_certificate_mandate_unavailable'); }
        if (!class_exists('ZMP_Registration_Requirements')
            || !is_callable(array('ZMP_Registration_Requirements', 'get_admin_facsimile'))) {
            return self::error('facsimile_certificate_facsimile_unavailable');
        }
        // A mandate's signature can only bind to a file explicitly submitted
        // for that mandate. Unbound legacy/global rows are never authority.
        $statuses = $allow_pending ? "status='pending'" : "status='active'";
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id,sha256,valid_from,valid_to,status FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE mandate_id=%d AND president_user_id=%d AND {$statuses} ORDER BY id DESC LIMIT 1",
            (int) $mandate->id, (int) $mandate->user_id
        ));
        // Preserve a pre-.60 authorization only for that exact active mandate
        // certificate. This compatibility path is never used for registration.
        if (!$row && !$allow_pending) {
            $legacy_certificate = $wpdb->get_row($wpdb->prepare(
                "SELECT facsimile_id,facsimile_sha256 FROM " . self::certificates_table() . " WHERE mandate_id=%d AND president_user_id=%d AND status='active' ORDER BY id DESC LIMIT 1",
                (int) $mandate->id, (int) $mandate->user_id
            ));
            if ($legacy_certificate) {
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT id,sha256,valid_from,valid_to,status FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d AND status='active'",
                    (int) $legacy_certificate->facsimile_id
                ));
                if (!$row || !hash_equals(strtolower((string) $row->sha256), strtolower((string) $legacy_certificate->facsimile_sha256))) { $row = null; }
            }
        }
        if (is_wp_error($row)) { return self::error('facsimile_certificate_facsimile_unavailable'); }
        if (!$row) { return self::error('facsimile_certificate_facsimile_unavailable'); }
        $facsimile_id = absint($row->id ?? 0);
        $facsimile_hash = strtolower((string) ($row->sha256 ?? ''));
        $today = current_time('Y-m-d');
        if (!$facsimile_id || !preg_match('/^[a-f0-9]{64}$/', $facsimile_hash)
            || (string) $row->status !== ($allow_pending ? 'pending' : 'active')
            || (string) $row->valid_from > $today || (!empty($row->valid_to) && (string) $row->valid_to < $today)) {
            return self::error('facsimile_certificate_facsimile_unavailable');
        }
        $verified = ZMP_Registration_Requirements::get_admin_facsimile($facsimile_id);
        if (is_wp_error($verified) || !hash_equals($facsimile_hash, strtolower((string) ($verified['sha256'] ?? '')))) {
            return self::error('facsimile_certificate_facsimile_unavailable');
        }
        if (!is_object($row) || absint($row->id ?? 0) < 1
            || !isset($row->valid_from) || !preg_match('/^[a-f0-9]{64}$/', strtolower((string) ($row->sha256 ?? '')))) {
            return self::error('facsimile_certificate_facsimile_unavailable');
        }
        return $row;
    }

    /** Return the outgoing active certificate/image, or an exact legacy active image if no cert exists. */
    private static function outgoing_certificate_for_mandate($successor_mandate) {
        global $wpdb;
        $predecessor_id = absint($successor_mandate->predecessor_mandate_id ?? 0);
        $predecessor_user_id = absint($successor_mandate->predecessor_user_id ?? 0);
        if (!$predecessor_id || !$predecessor_user_id) { return false; }
        $cert = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id,c.mandate_id,c.president_user_id,c.facsimile_id,c.facsimile_sha256,c.status,f.president_name AS outgoing_president_name,f.status AS facsimile_status
             FROM " . self::certificates_table() . " c
             INNER JOIN {$wpdb->prefix}zmp_admin_facsimiles f ON f.id=c.facsimile_id
             WHERE c.mandate_id=%d AND c.president_user_id=%d AND c.status='active' AND f.status='active'
             ORDER BY c.id DESC LIMIT 1",
            $predecessor_id, $predecessor_user_id
        ));
        if ($cert && preg_match('/^[a-f0-9]{64}$/', strtolower((string) $cert->facsimile_sha256))) {
            $file = ZMP_Registration_Requirements::get_admin_facsimile((int) $cert->facsimile_id);
            if (!is_wp_error($file) && hash_equals(strtolower((string) $cert->facsimile_sha256), strtolower((string) ($file['sha256'] ?? '')))) {
                $cert->outgoing_certificate_id = (int) $cert->id;
                $cert->outgoing_facsimile_id = (int) $cert->facsimile_id;
                $cert->outgoing_facsimile_sha256 = strtolower((string) $cert->facsimile_sha256);
                return $cert;
            }
        }
        // No usable predecessor cert: the single legacy/global active image still
        // requires the predecessor's confirmation before it can be replaced.
        $legacy = $wpdb->get_row($wpdb->prepare(
            "SELECT id,mandate_id,president_user_id,president_name,sha256,status FROM {$wpdb->prefix}zmp_admin_facsimiles
             WHERE status='active' AND (mandate_id=%d AND president_user_id=%d OR mandate_id=0)
             ORDER BY CASE WHEN mandate_id=%d THEN 0 ELSE 1 END,id DESC LIMIT 1",
            $predecessor_id, $predecessor_user_id, $predecessor_id
        ));
        if (!$legacy || !preg_match('/^[a-f0-9]{64}$/', strtolower((string) $legacy->sha256))) { return false; }
        $file = ZMP_Registration_Requirements::get_admin_facsimile((int) $legacy->id);
        if (is_wp_error($file) || !hash_equals(strtolower((string) $legacy->sha256), strtolower((string) ($file['sha256'] ?? '')))) { return false; }
        return (object) array(
            'id' => 0, 'outgoing_certificate_id' => 0, 'mandate_id' => $predecessor_id,
            'president_user_id' => $predecessor_user_id, 'facsimile_id' => (int) $legacy->id,
            'facsimile_sha256' => strtolower((string) $legacy->sha256),
            'outgoing_facsimile_id' => (int) $legacy->id,
            'outgoing_facsimile_sha256' => strtolower((string) $legacy->sha256),
            'outgoing_president_name' => (string) $legacy->president_name, 'status' => 'active',
        );
    }

    /** Safe, non-secret values used by the current President to prepare the signed manifest. */
    public static function current_registration_context() {
        $mandate = self::active_mandate_for_current_president();
        if (is_wp_error($mandate)) { return $mandate; }
        $facsimile = self::active_facsimile_for_mandate($mandate, true);
        if (is_wp_error($facsimile)) { return $facsimile; }
        $today = current_time('Y-m-d');
        $valid_from = max($today, (string) $mandate->start_date, (string) $facsimile->valid_from);
        $valid_until = (string) $mandate->end_date;
        if (!empty($facsimile->valid_to) && $facsimile->valid_to < $valid_until) { $valid_until = (string) $facsimile->valid_to; }
        if ($valid_from > $valid_until) { return self::error('facsimile_certificate_validity_unavailable'); }
        return array(
            'mandate_id' => (int) $mandate->id,
            'president_user_id' => (int) $mandate->user_id,
            'facsimile_id' => (int) $facsimile->id,
            'facsimile_sha256' => strtolower((string) $facsimile->sha256),
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'permitted_use' => array(self::CERTIFICATE_USE),
            'handover_required' => self::outgoing_certificate_for_mandate($mandate) !== false,
            'predecessor_mandate_id' => absint($mandate->predecessor_mandate_id ?? 0),
            'predecessor_user_id' => absint($mandate->predecessor_user_id ?? 0),
            'handover_deadline' => (string) ($mandate->handover_deadline ?? ''),
        );
    }

    public static function register_action_url() {
        return add_query_arg('action', self::REGISTER_ACTION, admin_url('admin-post.php'));
    }

    public static function register_nonce_action() {
        return self::REGISTER_ACTION;
    }

    public static function revoke_action_url() {
        return add_query_arg('action', self::REVOKE_ACTION, admin_url('admin-post.php'));
    }

    public static function revoke_nonce_action($certificate_id) {
        return self::REVOKE_ACTION . '_' . absint($certificate_id);
    }

    public static function handover_action_url() {
        return add_query_arg('action', self::HANDOVER_ACTION, admin_url('admin-post.php'));
    }

    public static function handover_nonce_action($certificate_id, $decision) {
        return self::HANDOVER_ACTION . '_' . absint($certificate_id) . '_' . sanitize_key((string) $decision);
    }

    private static function strict_base64_decode($value, $max_decoded_bytes) {
        if (!is_string($value) || $value === '' || strlen($value) > (int) ceil($max_decoded_bytes * 4 / 3) + 8) { return false; }
        $decoded = base64_decode($value, true);
        if (!is_string($decoded) || strlen($decoded) > $max_decoded_bytes || !hash_equals(base64_encode($decoded), $value)) { return false; }
        return $decoded;
    }

    private static function valid_date($date) {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { return false; }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private static function decode_manifest($manifest_bytes) {
        if (!is_string($manifest_bytes) || $manifest_bytes === '' || strlen($manifest_bytes) > self::MANIFEST_MAX_BYTES
            || !preg_match('//u', $manifest_bytes)) { return self::error('facsimile_certificate_manifest_invalid'); }
        $data = json_decode($manifest_bytes, true, 16);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) { return self::error('facsimile_certificate_manifest_invalid'); }
        $expected = array('version','algorithm','serial','mandate_id','president_user_id','public_key_id','facsimile_id','facsimile_sha256','permitted_use','valid_from','valid_until');
        $keys = array_keys($data);
        sort($keys, SORT_STRING);
        $sorted_expected = $expected;
        sort($sorted_expected, SORT_STRING);
        if ($keys !== $sorted_expected) { return self::error('facsimile_certificate_manifest_invalid'); }
        if (!isset($data['version']) || $data['version'] !== 1 || ($data['algorithm'] ?? null) !== 'Ed25519') {
            return self::error('facsimile_certificate_manifest_invalid');
        }
        if (!is_string($data['serial']) || !preg_match('/^[A-Za-z0-9._-]{1,80}$/', $data['serial'])) {
            return self::error('facsimile_certificate_manifest_invalid');
        }
        foreach (array('mandate_id','president_user_id','facsimile_id') as $integer_key) {
            if (!isset($data[$integer_key]) || !is_int($data[$integer_key]) || $data[$integer_key] < 1) {
                return self::error('facsimile_certificate_manifest_invalid');
            }
        }
        foreach (array('public_key_id','facsimile_sha256') as $hash_key) {
            if (!isset($data[$hash_key]) || !is_string($data[$hash_key]) || !preg_match('/^[a-f0-9]{64}$/', $data[$hash_key])) {
                return self::error('facsimile_certificate_manifest_invalid');
            }
        }
        if (!is_array($data['permitted_use']) || !$data['permitted_use'] || array_values($data['permitted_use']) !== $data['permitted_use']) {
            return self::error('facsimile_certificate_manifest_invalid');
        }
        $uses = $data['permitted_use'];
        foreach ($uses as $use) {
            if (!is_string($use) || $use !== self::CERTIFICATE_USE) { return self::error('facsimile_certificate_use_invalid'); }
        }
        if (count(array_unique($uses)) !== count($uses)) { return self::error('facsimile_certificate_manifest_invalid'); }
        if (!self::valid_date($data['valid_from'] ?? null) || !self::valid_date($data['valid_until'] ?? null)
            || $data['valid_from'] > $data['valid_until']) { return self::error('facsimile_certificate_validity_invalid'); }
        // The detached signature is over exact bytes. Require one compact,
        // deterministic JSON representation to reject duplicate or ambiguous keys.
        $canonical_data = $data;
        ksort($canonical_data, SORT_STRING);
        $canonical = wp_json_encode($canonical_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($canonical) || !hash_equals($canonical, $manifest_bytes)) {
            return self::error('facsimile_certificate_manifest_invalid');
        }
        return $data;
    }

    private static function validate_registration($public_key_b64, $manifest_b64, $signature_b64) {
        if (!function_exists('sodium_crypto_sign_verify_detached')
            || !defined('SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES') || !defined('SODIUM_CRYPTO_SIGN_BYTES')) {
            return self::error('facsimile_certificate_crypto_unavailable');
        }
        $public_key = self::strict_base64_decode($public_key_b64, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $manifest = self::strict_base64_decode($manifest_b64, self::MANIFEST_MAX_BYTES);
        $signature = self::strict_base64_decode($signature_b64, SODIUM_CRYPTO_SIGN_BYTES);
        if (!is_string($public_key) || strlen($public_key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !is_string($manifest) || !is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return self::error('facsimile_certificate_input_invalid');
        }
        try {
            if (!sodium_crypto_sign_verify_detached($signature, $manifest, $public_key)) {
                return self::error('facsimile_certificate_signature_invalid');
            }
        } catch (Throwable $e) {
            return self::error('facsimile_certificate_signature_invalid');
        }
        $data = self::decode_manifest($manifest);
        if (is_wp_error($data)) { return $data; }
        $key_id = hash('sha256', $public_key);
        if (!hash_equals($key_id, $data['public_key_id'])) { return self::error('facsimile_certificate_key_id_mismatch'); }
        return array('public_key' => $public_key, 'manifest' => $manifest, 'signature' => $signature, 'data' => $data, 'key_id' => $key_id);
    }

    /** Register or rotate a President public key after verifying its signed manifest. */
    public static function register_certificate($public_key_b64, $manifest_b64, $signature_b64) {
        global $wpdb;
        if (!self::is_current_president()) { return self::error('facsimile_certificate_president_required'); }
        if (!self::tables_exist()) { return self::error('facsimile_certificate_schema_unavailable'); }
        $verified = self::validate_registration($public_key_b64, $manifest_b64, $signature_b64);
        if (is_wp_error($verified)) { return $verified; }
        $context = self::current_registration_context();
        if (is_wp_error($context)) { return $context; }
        $manifest = $verified['data'];
        if ((int) $manifest['president_user_id'] !== (int) $context['president_user_id']
            || (int) $manifest['mandate_id'] !== (int) $context['mandate_id']) {
            return self::error('facsimile_certificate_mandate_mismatch');
        }
        if ((int) $manifest['facsimile_id'] !== (int) $context['facsimile_id']
            || !hash_equals((string) $context['facsimile_sha256'], $manifest['facsimile_sha256'])) {
            return self::error('facsimile_certificate_facsimile_mismatch');
        }
        if ($manifest['valid_from'] !== $context['valid_from'] || $manifest['valid_until'] > $context['valid_until']) {
            return self::error('facsimile_certificate_validity_invalid');
        }
        $uses = $manifest['permitted_use'];
        $expected_uses = $context['permitted_use'];
        sort($uses, SORT_STRING); sort($expected_uses, SORT_STRING);
        if ($uses !== $expected_uses) { return self::error('facsimile_certificate_use_invalid'); }

        // The shared advisory lock serializes this revalidation/write with an
        // administrator replacing the active facsimile, including empty-table
        // cases where a row lock cannot protect a newly inserted replacement.
        if (!ZMP_Registration_Requirements::acquire_facsimile_write_lock()) {
            return self::error('facsimile_certificate_write_failed');
        }
        try {
        if (false === $wpdb->query('START TRANSACTION')) { return self::error('facsimile_certificate_write_failed'); }
        $table = self::certificates_table();
        // Lock the stable parent row first. This serializes first registrations
        // too, when no certificate row exists yet and SELECT ... FOR UPDATE on
        // the child table would otherwise lock nothing.
        $mandate_table = $wpdb->prefix . 'zmp_president_mandates';
        $locked_mandate = $wpdb->get_row($wpdb->prepare(
            "SELECT id,user_id,start_date,end_date,status,predecessor_mandate_id,predecessor_user_id,handover_deadline FROM {$mandate_table} WHERE id=%d FOR UPDATE",
            (int) $context['mandate_id']
        ));
        $today = current_time('Y-m-d');
        if (!$locked_mandate || (int)$locked_mandate->user_id !== (int)$context['president_user_id']
            || (string)$locked_mandate->status !== 'active'
            || (string)$locked_mandate->start_date > $today || (string)$locked_mandate->end_date < $today
            || !self::is_current_president()) {
            $wpdb->query('ROLLBACK');
            return self::error('facsimile_certificate_mandate_mismatch');
        }
        $fresh_context = self::current_registration_context();
        if (is_wp_error($fresh_context)) { $wpdb->query('ROLLBACK'); return $fresh_context; }
        foreach (array('mandate_id','president_user_id','facsimile_id','facsimile_sha256','valid_from','valid_until','permitted_use','handover_required','predecessor_mandate_id','predecessor_user_id','handover_deadline') as $context_key) {
            if (($fresh_context[$context_key] ?? null) !== ($context[$context_key] ?? null)) {
                $wpdb->query('ROLLBACK');
                return self::error('facsimile_certificate_context_changed');
            }
        }
        $context = $fresh_context;
        $old = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$table} WHERE mandate_id=%d AND status='active' FOR UPDATE",
            (int) $context['mandate_id']
        ));
        if (!is_array($old)) { $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed'); }
        $pending_handover = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE mandate_id=%d AND status='pending_handover' LIMIT 1 FOR UPDATE", (int) $context['mandate_id']
        ));
        if ($pending_handover) { $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_handover_pending'); }
        $outgoing = self::outgoing_certificate_for_mandate($locked_mandate);
        $handover_required = $outgoing !== false;
        $new_status = $handover_required ? 'pending_handover' : 'active';
        $now = current_time('mysql');
        foreach ($old as $previous) {
            $old_id = absint($previous->id ?? 0);
            if ($old_id < 1 || false === $wpdb->update($table,
                array('status'=>'superseded','superseded_at'=>$now), array('id'=>$old_id,'status'=>'active'), array('%s','%s'), array('%d','%s'))) {
                $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed');
            }
        }
        $inserted = $wpdb->insert($table, array(
            'mandate_id' => (int) $context['mandate_id'],
            'president_user_id' => (int) $context['president_user_id'],
            'serial' => (string) $manifest['serial'],
            'public_key_id' => (string) $verified['key_id'],
            'public_key_b64' => base64_encode($verified['public_key']),
            'facsimile_id' => (int) $context['facsimile_id'],
            'facsimile_sha256' => (string) $context['facsimile_sha256'],
            'permitted_use_json' => wp_json_encode($uses),
            'valid_from' => (string) $manifest['valid_from'],
            'valid_until' => (string) $manifest['valid_until'],
            'manifest_b64' => base64_encode($verified['manifest']),
            'signature_b64' => base64_encode($verified['signature']),
            'status' => $new_status,
            'created_by' => self::current_user_id(),
            'created_at' => $now,
            'revocation_reason' => '',
        ), array('%d','%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        if (!$inserted) { $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed'); }
        $certificate_id = (int) $wpdb->insert_id;
        foreach ($old as $previous) {
            $old_id = absint($previous->id ?? 0);
            if (!self::insert_event($old_id, (int) $context['mandate_id'], self::current_user_id(), 'superseded', array('replacement_id'=>$certificate_id))) {
                $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed');
            }
        }
        $event_data = array('serial'=>(string)$manifest['serial'],'public_key_id'=>(string)$verified['key_id'],
            'facsimile_id'=>(int)$context['facsimile_id'],'facsimile_sha256'=>(string)$context['facsimile_sha256'],
            'valid_from'=>(string)$manifest['valid_from'],'valid_until'=>(string)$manifest['valid_until']);
        $event_data['outgoing_certificate_id'] = $handover_required ? (int) $outgoing->id : 0;
        $event_data['outgoing_facsimile_id'] = $handover_required ? (int) $outgoing->facsimile_id : 0;
        $event_data['outgoing_facsimile_sha256'] = $handover_required ? (string) $outgoing->facsimile_sha256 : '';
        if (!self::insert_event($certificate_id, (int) $context['mandate_id'], self::current_user_id(), $handover_required ? 'handover_pending' : 'registered', $event_data)) {
            $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed');
        }
        if (!$handover_required) {
            $archived = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}zmp_admin_facsimiles SET status='archived' WHERE mandate_id=%d AND president_user_id=%d AND status='active' AND id<>%d",
                (int) $context['mandate_id'], (int) $context['president_user_id'], (int) $context['facsimile_id']
            ));
            $activated = $wpdb->update($wpdb->prefix . 'zmp_admin_facsimiles', array('status' => 'active'),
                array('id' => (int) $context['facsimile_id'], 'mandate_id' => (int) $context['mandate_id'], 'president_user_id' => (int) $context['president_user_id'], 'status' => 'pending'),
                array('%s'), array('%d','%d','%d','%s'));
            if ($archived === false || $activated !== 1 || !self::insert_event($certificate_id, (int) $context['mandate_id'], self::current_user_id(), 'facsimile_activated', array(
                'facsimile_id' => (int) $context['facsimile_id'], 'facsimile_sha256' => (string) $context['facsimile_sha256'],
            ))) {
                $wpdb->query('ROLLBACK'); return self::error('facsimile_certificate_write_failed');
            }
        }
        if (false === $wpdb->query('COMMIT')) { return self::error('facsimile_certificate_commit_uncertain'); }
        if (class_exists('ZMP_Debug')) {
            ZMP_Debug::log('info', 'President facsimile public-key certificate registered.', array(
                'certificate_id'=>$certificate_id,'mandate_id'=>(int)$context['mandate_id'],'public_key_id'=>(string)$verified['key_id']
            ), 'president_facsimile_certificate');
        }
        return $handover_required ? array('certificate_id' => $certificate_id, 'status' => 'pending_handover') : $certificate_id;
        } finally {
            ZMP_Registration_Requirements::release_facsimile_write_lock();
        }
    }

    public static function current_certificate_status() {
        global $wpdb;
        if (!self::is_current_president()) { return self::error('facsimile_certificate_president_required'); }
        $mandate = self::active_mandate_for_current_president();
        if (is_wp_error($mandate)) { return $mandate; }
        if (!self::tables_exist()) { return self::error('facsimile_certificate_schema_unavailable'); }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT id,serial,public_key_id,facsimile_sha256,valid_from,valid_until,status,revoked_at,revocation_reason FROM ' . self::certificates_table() . ' WHERE mandate_id=%d ORDER BY id DESC LIMIT 1',
            (int) $mandate->id
        ));
        if (!$row) {
            return array('certificate_id'=>0,'status'=>'none','serial'=>'','fingerprint'=>'','facsimile_sha256'=>'',
                'valid_from'=>'','valid_until'=>'','revoked_at'=>'','revocation_reason'=>'');
        }
        $status = sanitize_key((string) $row->status);
        if ($status === 'active' && (string) $row->valid_until < current_time('Y-m-d')) { $status = 'expired'; }
        if (!in_array($status, array('active','expired','revoked','superseded','pending_handover'), true)) { $status = 'none'; }
        return array(
            'certificate_id' => (int) $row->id,
            'status' => $status,
            'serial' => sanitize_text_field((string) $row->serial),
            'fingerprint' => sanitize_text_field((string) $row->public_key_id),
            'facsimile_sha256' => sanitize_text_field((string) $row->facsimile_sha256),
            'valid_from' => sanitize_text_field((string) $row->valid_from),
            'valid_until' => sanitize_text_field((string) $row->valid_until),
            'revoked_at' => sanitize_text_field((string) ($row->revoked_at ?? '')),
            'revocation_reason' => sanitize_textarea_field((string) ($row->revocation_reason ?? '')),
        );
    }

    /** Check a certificate before later phases use it; `.58` itself never applies the facsimile. */
    public static function verify_for_use($certificate_id, $use, $facsimile_id, $facsimile_sha256, $at_date = '') {
        global $wpdb;
        if (!function_exists('sodium_crypto_sign_verify_detached')
            || !defined('SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES') || !defined('SODIUM_CRYPTO_SIGN_BYTES')) { return false; }
        $certificate_id = absint($certificate_id);
        $use = sanitize_key((string) $use);
        $date = $at_date !== '' ? (string) $at_date : current_time('Y-m-d');
        if (!$certificate_id || !self::valid_date($date)) { return false; }
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::certificates_table() . ' WHERE id=%d', $certificate_id));
        if (!$row || $row->status !== 'active' || $date < (string) $row->valid_from || $date > (string) $row->valid_until
            || (int) $row->facsimile_id !== absint($facsimile_id) || !hash_equals((string) $row->facsimile_sha256, strtolower((string) $facsimile_sha256))) { return false; }
        $uses = json_decode((string) $row->permitted_use_json, true);
        if (!is_array($uses) || !in_array($use, $uses, true)) { return false; }
        $public_key = self::strict_base64_decode((string) $row->public_key_b64, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $manifest = self::strict_base64_decode((string) $row->manifest_b64, self::MANIFEST_MAX_BYTES);
        $signature = self::strict_base64_decode((string) $row->signature_b64, SODIUM_CRYPTO_SIGN_BYTES);
        if (!is_string($public_key) || strlen($public_key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !is_string($manifest) || !is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || !hash_equals((string) $row->public_key_id, hash('sha256', $public_key))) { return false; }
        $data = self::decode_manifest($manifest);
        if (is_wp_error($data) || (int)$data['mandate_id'] !== (int)$row->mandate_id
            || (int)$data['president_user_id'] !== (int)$row->president_user_id
            || (string)$data['serial'] !== (string)$row->serial
            || (string)$data['public_key_id'] !== (string)$row->public_key_id
            || (int)$data['facsimile_id'] !== (int)$row->facsimile_id
            || !hash_equals((string)$data['facsimile_sha256'], (string)$row->facsimile_sha256)
            || (string)$data['valid_from'] !== (string)$row->valid_from
            || (string)$data['valid_until'] !== (string)$row->valid_until
            || array_values($data['permitted_use']) !== array_values($uses)) { return false; }
        if (!ZMP_President_Mandate::user_has_active_mandate((int)$row->president_user_id)) { return false; }
        $active_mandate = self::get_active_mandate_by_user((int)$row->president_user_id);
        if (!$active_mandate || (int)$active_mandate->id !== (int)$row->mandate_id) { return false; }
        $active_facsimile = self::active_facsimile_for_mandate($active_mandate);
        if (!$active_facsimile || is_wp_error($active_facsimile) || (int)$active_facsimile->id !== (int)$row->facsimile_id
            || !hash_equals((string)$active_facsimile->sha256, (string)$row->facsimile_sha256)) { return false; }
        try { return sodium_crypto_sign_verify_detached($signature, $manifest, $public_key); }
        catch (Throwable $e) { return false; }
    }

    /** Return true only when the latest certificate for the current mandate authorizes this use. */
    public static function active_certificate_allows_use($use, $facsimile_id, $facsimile_sha256, $at_date = '') {
        global $wpdb;
        $use = sanitize_key((string) $use);
        $facsimile_id = absint($facsimile_id);
        $facsimile_sha256 = strtolower((string) $facsimile_sha256);
        $date = $at_date !== '' ? (string) $at_date : current_time('Y-m-d');
        if (!$facsimile_id || !preg_match('/^[a-f0-9]{64}$/', $facsimile_sha256) || !self::valid_date($date)) { return false; }

        $mandate = $wpdb->get_row($wpdb->prepare(
            "SELECT id,user_id FROM {$wpdb->prefix}zmp_president_mandates
             WHERE status='active' AND start_date<=%s AND end_date>=%s
             ORDER BY start_date DESC,id DESC LIMIT 1",
            $date, $date
        ));
        if (!$mandate || absint($mandate->id ?? 0) < 1 || absint($mandate->user_id ?? 0) < 1) { return false; }

        $certificate = $wpdb->get_row($wpdb->prepare(
            "SELECT id,status FROM " . self::certificates_table() . "
             WHERE mandate_id=%d AND status='active' ORDER BY id DESC LIMIT 1",
            absint($mandate->id)
        ));
        if (!$certificate || $certificate->status !== 'active') { return false; }

        return self::verify_for_use(
            absint($certificate->id),
            $use,
            $facsimile_id,
            $facsimile_sha256,
            $date
        ) === true;
    }

    public static function revoke_certificate($certificate_id, $reason) {
        global $wpdb;
        if (!is_user_logged_in() || !current_user_can('manage_options')) { return self::error('facsimile_certificate_admin_required'); }
        $certificate_id = absint($certificate_id);
        $reason = trim(sanitize_textarea_field((string) $reason));
        if (!$certificate_id || strlen($reason) < 8 || strlen($reason) > 2000) { return self::error('facsimile_certificate_revocation_invalid'); }
        if (!self::tables_exist()) { return self::error('facsimile_certificate_schema_unavailable'); }
        if (!class_exists('ZMP_Registration_Requirements')
            || !is_callable(array('ZMP_Registration_Requirements', 'acquire_facsimile_write_lock'))
            || !is_callable(array('ZMP_Registration_Requirements', 'release_facsimile_write_lock'))
            || !ZMP_Registration_Requirements::acquire_facsimile_write_lock()) {
            return self::error('facsimile_certificate_write_failed');
        }
        $transaction_started = false;
        try {
            if (false === $wpdb->query('START TRANSACTION')) { return self::error('facsimile_certificate_write_failed'); }
            $transaction_started = true;
            $table = self::certificates_table();
            $row = $wpdb->get_row($wpdb->prepare("SELECT id,mandate_id,status,serial,public_key_id FROM {$table} WHERE id=%d FOR UPDATE", $certificate_id));
            if (!$row || $row->status !== 'active') { return self::error('facsimile_certificate_not_active'); }
            $now = current_time('mysql');
            $updated = $wpdb->update($table, array('status'=>'revoked','revoked_by'=>self::current_user_id(),'revoked_at'=>$now,'revocation_reason'=>$reason),
                array('id'=>$certificate_id,'status'=>'active'), array('%s','%d','%s','%s'), array('%d','%s'));
            if ($updated !== 1) { return self::error('facsimile_certificate_write_failed'); }
            if (!self::insert_event($certificate_id,(int)$row->mandate_id,self::current_user_id(),'revoked',array('serial'=>(string)$row->serial,'public_key_id'=>(string)$row->public_key_id,'reason'=>$reason))) {
                return self::error('facsimile_certificate_write_failed');
            }
            $committed = $wpdb->query('COMMIT');
            if (false === $committed) { return self::error('facsimile_certificate_commit_uncertain'); }
            $transaction_started = false;
            if (class_exists('ZMP_Debug')) { ZMP_Debug::log('warning','President facsimile certificate revoked.',array('certificate_id'=>$certificate_id,'mandate_id'=>(int)$row->mandate_id),'president_facsimile_certificate'); }
            return true;
        } finally {
            if ($transaction_started) { $wpdb->query('ROLLBACK'); }
            ZMP_Registration_Requirements::release_facsimile_write_lock();
        }
    }

    private static function insert_event($certificate_id, $mandate_id, $actor_user_id, $event_type, $data) {
        global $wpdb;
        return 1 === $wpdb->insert(self::events_table(), array(
            'certificate_id'=>absint($certificate_id),'mandate_id'=>absint($mandate_id),'actor_user_id'=>absint($actor_user_id),
            'event_type'=>sanitize_key((string)$event_type),'event_data'=>wp_json_encode($data),'created_at'=>current_time('mysql'),
        ), array('%d','%d','%d','%s','%s','%s'));
    }

    /** Former President's tightly scoped, current handover requiring a decision. */
    public static function pending_handover_for_current_user() {
        if (!is_user_logged_in()) { return false; }
        global $wpdb;
        $today = current_time('Y-m-d');
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT c.id AS certificate_id,c.mandate_id AS successor_mandate_id,c.president_user_id AS successor_user_id,
                    c.facsimile_id,c.facsimile_sha256,c.status,
                    m.predecessor_mandate_id,m.predecessor_user_id,m.handover_deadline,
                    incomingf.president_name AS successor_president_name
             FROM " . self::certificates_table() . " c
             INNER JOIN {$wpdb->prefix}zmp_president_mandates m ON m.id=c.mandate_id
             INNER JOIN {$wpdb->prefix}zmp_admin_facsimiles incomingf ON incomingf.id=c.facsimile_id AND incomingf.mandate_id=c.mandate_id AND incomingf.president_user_id=c.president_user_id AND incomingf.status='pending'
             WHERE c.status='pending_handover' AND m.status='active' AND m.predecessor_user_id=%d AND m.handover_deadline>=%s
               AND c.facsimile_sha256=incomingf.sha256
             ORDER BY c.id DESC LIMIT 1",
            get_current_user_id(), $today
        ));
        if (!$row || (int) $row->predecessor_user_id !== get_current_user_id()) { return false; }
        $successor_mandate = $wpdb->get_row($wpdb->prepare(
            'SELECT id,user_id,predecessor_mandate_id,predecessor_user_id,handover_deadline FROM ' . ZMP_President_Mandate::table_name() . ' WHERE id=%d', (int) $row->successor_mandate_id
        ));
        $outgoing = $successor_mandate ? self::outgoing_certificate_for_mandate($successor_mandate) : false;
        if (!$outgoing || (int) ($outgoing->outgoing_facsimile_id ?? 0) < 1) { return false; }
        $row->outgoing_certificate_id = (int) ($outgoing->outgoing_certificate_id ?? 0);
        $row->outgoing_facsimile_id = (int) $outgoing->outgoing_facsimile_id;
        $row->outgoing_facsimile_sha256 = (string) $outgoing->outgoing_facsimile_sha256;
        $row->outgoing_president_name = (string) ($outgoing->outgoing_president_name ?? '');
        $old_file = ZMP_Registration_Requirements::get_admin_facsimile((int) $row->outgoing_facsimile_id);
        $new_file = ZMP_Registration_Requirements::get_admin_facsimile((int) $row->facsimile_id);
        if (is_wp_error($old_file) || is_wp_error($new_file)
            || !hash_equals(strtolower((string) $row->outgoing_facsimile_sha256), strtolower((string) ($old_file['sha256'] ?? '')))
            || !hash_equals(strtolower((string) $row->facsimile_sha256), strtolower((string) ($new_file['sha256'] ?? ''))) ) { return false; }
        $row->predecessor_user_id = (int) $row->predecessor_user_id;
        $row->successor_user_id = (int) $row->successor_user_id;
        $row->successor_president_name = (string) $row->successor_president_name;
        $row->predecessor_name = (string) $row->outgoing_president_name;
        return $row;
    }

    public static function handle_handover_decision() {
        $id = absint($_POST['certificate_id'] ?? 0);
        $decision = sanitize_key((string) wp_unslash($_POST['decision'] ?? ''));
        check_admin_referer(self::handover_nonce_action($id, $decision), self::NONCE_FIELD);
        $result = self::decide_handover($id, $decision);
        if (is_wp_error($result)) {
            self::redirect_status('facsimile_error_' . $result->get_error_code(), admin_url('admin.php?page=zmp-president-handover-extension'));
        }
        self::redirect_status($decision === 'confirm' ? 'facsimile_confirmed' : 'facsimile_refused', admin_url('admin.php?page=zmp-president-handover-extension'));
    }

    /** Apply former-President confirmation/refusal atomically; no administrator override. */
    public static function decide_handover($certificate_id, $decision) {
        global $wpdb;
        $certificate_id = absint($certificate_id);
        $decision = sanitize_key((string) $decision);
        if (!is_user_logged_in() || !$certificate_id || !in_array($decision, array('confirm', 'refuse'), true)) { return self::error('handover_invalid'); }
        if (!class_exists('ZMP_Registration_Requirements') || !ZMP_Registration_Requirements::acquire_facsimile_write_lock()) { return self::error('handover_write_failed'); }
        $transaction = false;
        try {
            if (false === $wpdb->query('START TRANSACTION')) { return self::error('handover_write_failed'); }
            $transaction = true;
            $cert_table = self::certificates_table();
            $mandate_table = ZMP_President_Mandate::table_name();
            $candidate = $wpdb->get_row($wpdb->prepare(
                "SELECT c.id,c.mandate_id,c.president_user_id,c.facsimile_id,c.facsimile_sha256,c.status,
                        m.user_id AS successor_user_id,m.predecessor_mandate_id,m.predecessor_user_id,m.handover_deadline,m.status AS mandate_status,m.start_date,m.end_date
                 FROM {$cert_table} c INNER JOIN {$mandate_table} m ON m.id=c.mandate_id
                 WHERE c.id=%d FOR UPDATE", $certificate_id
            ));
            $today = current_time('Y-m-d');
            if (!$candidate || $candidate->status !== 'pending_handover' || $candidate->mandate_status !== 'active'
                || (int) $candidate->predecessor_user_id !== get_current_user_id()
                || (int) $candidate->successor_user_id !== (int) $candidate->president_user_id
                || (int) $candidate->predecessor_mandate_id < 1 || (string) $candidate->handover_deadline < $today
                || (string) $candidate->start_date > $today || (string) $candidate->end_date < $today) {
                return self::error('handover_not_available');
            }
            $predecessor = $wpdb->get_row($wpdb->prepare(
                "SELECT id,user_id,start_date,end_date,status FROM {$mandate_table} WHERE id=%d FOR UPDATE",
                (int) $candidate->predecessor_mandate_id
            ));
            if (!$predecessor || (int) $predecessor->user_id !== (int) $candidate->predecessor_user_id
                || ($predecessor->status === 'active' && (string) $predecessor->end_date >= $today)
                || ZMP_President_Mandate::user_has_active_mandate((int) $candidate->predecessor_user_id)) {
                return self::error('handover_predecessor_mandate_invalid');
            }
            $incoming = $wpdb->get_row($wpdb->prepare(
                "SELECT id,mandate_id,president_user_id,sha256,status FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d FOR UPDATE", (int) $candidate->facsimile_id
            ));
            $pending_event = $wpdb->get_row($wpdb->prepare(
                "SELECT event_data FROM " . self::events_table() . " WHERE certificate_id=%d AND event_type='handover_pending' ORDER BY id DESC LIMIT 1",
                $certificate_id
            ));
            $pending_data = $pending_event ? json_decode((string) $pending_event->event_data, true) : null;
            if (!is_array($pending_data)) { return self::error('handover_context_changed'); }
            $outgoing_certificate_id = absint($pending_data['outgoing_certificate_id'] ?? 0);
            $outgoing_facsimile_id = absint($pending_data['outgoing_facsimile_id'] ?? 0);
            $outgoing_facsimile_sha256 = strtolower((string) ($pending_data['outgoing_facsimile_sha256'] ?? ''));
            if (!$outgoing_facsimile_id || !preg_match('/^[a-f0-9]{64}$/', $outgoing_facsimile_sha256)) { return self::error('handover_context_changed'); }
            $outgoing = false;
            if ($outgoing_certificate_id) {
                $outgoing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id,mandate_id,president_user_id,facsimile_id,facsimile_sha256,status FROM {$cert_table} WHERE id=%d FOR UPDATE", $outgoing_certificate_id
                ));
            }
            if (!$incoming || $incoming->status !== 'pending' || (int) $incoming->mandate_id !== (int) $candidate->mandate_id
                || (int) $incoming->president_user_id !== (int) $candidate->president_user_id
                || !hash_equals(strtolower((string) $incoming->sha256), strtolower((string) $candidate->facsimile_sha256))
                || ($outgoing_certificate_id && (!$outgoing || $outgoing->status !== 'active'
                    || (int) $outgoing->mandate_id !== (int) $candidate->predecessor_mandate_id
                    || (int) $outgoing->president_user_id !== (int) $candidate->predecessor_user_id
                    || (int) $outgoing->facsimile_id !== $outgoing_facsimile_id
                    || !hash_equals(strtolower((string) $outgoing->facsimile_sha256), $outgoing_facsimile_sha256)))) {
                return self::error('handover_context_changed');
            }
            $old_image = $wpdb->get_row($wpdb->prepare(
                "SELECT id,mandate_id,president_user_id,sha256,status FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d FOR UPDATE", $outgoing_facsimile_id
            ));
            // Legacy outgoing rows stay unbound; the predecessor's signed certificate
            // supplies their identity. They are never selected as successor authority.
            if (!$old_image || $old_image->status !== 'active'
                || !hash_equals(strtolower((string) $old_image->sha256), $outgoing_facsimile_sha256)) { return self::error('handover_context_changed'); }
            $old_file = ZMP_Registration_Requirements::get_admin_facsimile($outgoing_facsimile_id);
            $new_file = ZMP_Registration_Requirements::get_admin_facsimile((int) $incoming->id);
            if (is_wp_error($old_file) || is_wp_error($new_file)
                || !hash_equals($outgoing_facsimile_sha256, strtolower((string) ($old_file['sha256'] ?? '')))
                || !hash_equals(strtolower((string) $candidate->facsimile_sha256), strtolower((string) ($new_file['sha256'] ?? '')))) {
                return self::error('handover_file_integrity');
            }
            $now = current_time('mysql');
            if ($decision === 'refuse') {
                $updated = $wpdb->update($cert_table, array('status' => 'revoked', 'revoked_by' => get_current_user_id(), 'revoked_at' => $now, 'revocation_reason' => 'Former President refused facsimile handover.'),
                    array('id' => $certificate_id, 'status' => 'pending_handover'), array('%s','%d','%s','%s'), array('%d','%s'));
                if ($updated !== 1 || !self::insert_event($certificate_id, (int) $candidate->mandate_id, get_current_user_id(), 'handover_refused', array(
                    'predecessor_mandate_id' => (int) $candidate->predecessor_mandate_id,
                    'outgoing_certificate_id' => $outgoing_certificate_id, 'outgoing_facsimile_id' => $outgoing_facsimile_id,
                    'outgoing_facsimile_sha256' => $outgoing_facsimile_sha256,
                    'incoming_facsimile_id' => (int) $incoming->id, 'incoming_facsimile_sha256' => (string) $incoming->sha256,
                ))) { return self::error('handover_write_failed'); }
                $result_status = 'facsimile_refused';
            } else {
                $old_revoked = $outgoing_certificate_id ? $wpdb->update($cert_table, array('status' => 'revoked', 'revoked_by' => get_current_user_id(), 'revoked_at' => $now, 'revocation_reason' => 'Replaced after former President confirmation.'),
                    array('id' => $outgoing_certificate_id, 'status' => 'active'), array('%s','%d','%s','%s'), array('%d','%s')) : 1;
                $archived = $wpdb->update($wpdb->prefix . 'zmp_admin_facsimiles', array('status' => 'archived'), array('id' => (int) $old_image->id, 'status' => 'active'), array('%s'), array('%d','%s'));
                $activated_image = $wpdb->update($wpdb->prefix . 'zmp_admin_facsimiles', array('status' => 'active'), array('id' => (int) $incoming->id, 'status' => 'pending'), array('%s'), array('%d','%s'));
                $activated_cert = $wpdb->update($cert_table, array('status' => 'active'), array('id' => $certificate_id, 'status' => 'pending_handover'), array('%s'), array('%d','%s'));
                $event_data = array('predecessor_mandate_id' => (int) $candidate->predecessor_mandate_id,
                    'outgoing_certificate_id' => $outgoing_certificate_id, 'outgoing_facsimile_id' => $outgoing_facsimile_id,
                    'outgoing_facsimile_sha256' => $outgoing_facsimile_sha256,
                    'incoming_facsimile_id' => (int) $incoming->id, 'incoming_facsimile_sha256' => (string) $incoming->sha256);
                if ($old_revoked !== 1 || $archived !== 1 || $activated_image !== 1 || $activated_cert !== 1
                    || ($outgoing_certificate_id && !self::insert_event($outgoing_certificate_id, (int) $candidate->predecessor_mandate_id, get_current_user_id(), 'handover_replaced', $event_data))
                    || !self::insert_event($certificate_id, (int) $candidate->mandate_id, get_current_user_id(), 'handover_confirmed', $event_data)) { return self::error('handover_write_failed'); }
                $result_status = 'facsimile_confirmed';
            }
            if (false === $wpdb->query('COMMIT')) { return self::error('facsimile_certificate_commit_uncertain'); }
            $transaction = false;
            return true;
        } finally {
            if ($transaction) { $wpdb->query('ROLLBACK'); }
            ZMP_Registration_Requirements::release_facsimile_write_lock();
        }
    }

    public static function handle_register() {
        check_admin_referer(self::register_nonce_action(), self::NONCE_FIELD);
        foreach (array('private_key','private_key_b64','secret_key','secret_key_b64') as $forbidden_field) {
            if (array_key_exists($forbidden_field, $_POST)) { self::redirect_status('invalid'); }
        }
        $result = self::register_certificate(
            is_string($_POST['public_key_b64'] ?? null) ? wp_unslash($_POST['public_key_b64']) : '',
            is_string($_POST['signed_manifest_b64'] ?? null) ? wp_unslash($_POST['signed_manifest_b64']) : '',
            is_string($_POST['signature_b64'] ?? null) ? wp_unslash($_POST['signature_b64']) : ''
        );
        self::redirect_status(is_wp_error($result) ? $result->get_error_code() : (is_array($result) && ($result['status'] ?? '') === 'pending_handover' ? 'pending_handover' : 'registered'));
    }

    public static function handle_revoke() {
        $id = absint($_POST['certificate_id'] ?? 0);
        check_admin_referer(self::revoke_nonce_action($id), self::NONCE_FIELD);
        $result = self::revoke_certificate($id, is_string($_POST['reason'] ?? null) ? wp_unslash($_POST['reason']) : '');
        self::redirect_status(is_wp_error($result) ? $result->get_error_code() : 'revoked', admin_url('admin.php?page=zmp-president-facsimile-certificates'));
    }

    private static function redirect_status($status, $base = '') {
        $base = $base ?: (wp_get_referer() ?: home_url('/'));
        $url = add_query_arg('zmp_president_facsimile_status', sanitize_key((string)$status), remove_query_arg('zmp_president_facsimile_status', $base));
        wp_safe_redirect($url);
        exit;
    }

    public static function register_admin_page() {
        if (!current_user_can('manage_options')) { return; }
        add_submenu_page('zmp-competitions','Сертификати за претседателски факсимил','Сертификати за факсимил','manage_options',
            'zmp-president-facsimile-certificates',array(__CLASS__,'render_admin_page'));
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Немате дозвола.','Нема пристап',array('response'=>403)); }
        global $wpdb;
        $rows = $wpdb->get_results('SELECT id,mandate_id,president_user_id,serial,public_key_id,facsimile_id,facsimile_sha256,valid_from,valid_until,status,revoked_at,revocation_reason FROM ' . self::certificates_table() . ' ORDER BY id DESC LIMIT 100');
        $notice = isset($_GET['zmp_president_facsimile_status']) && is_scalar($_GET['zmp_president_facsimile_status'])
            ? sanitize_key(wp_unslash((string)$_GET['zmp_president_facsimile_status'])) : '';
        ?>
        <div class="wrap"><h1>Сертификати за претседателски факсимил</h1>
        <?php if ($notice === 'revoked'): ?><div class="notice notice-success"><p>Сертификатот е отповикан.</p></div><?php elseif ($notice && $notice !== 'registered'): ?><div class="notice notice-error"><p>Отповикувањето не е извршено.</p></div><?php endif; ?>
        <p>Се прикажуваат јавни отпечатоци и статуси. Приватниот клуч не се прикачува ниту се чува во WordPress.</p>
        <style>
            .zmp-facsimile-certificate-table-scroll { max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .zmp-facsimile-certificate-table-scroll:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
            .zmp-facsimile-certificate-table { min-width: 50rem; }
            .zmp-facsimile-certificate-table code { white-space: normal; overflow-wrap: anywhere; word-break: break-word; }
        </style>
        <p id="zmp-facsimile-certificate-scroll-help" class="screen-reader-text">Табелата може да биде поширока од екранот. Користете ги стрелките лево и десно или хоризонтално лизгање за да ги прегледате сите колони.</p>
        <div id="zmp-facsimile-certificate-table-scroll" class="zmp-facsimile-certificate-table-scroll" tabindex="0" role="region" aria-label="Сертификати за претседателски факсимил" aria-describedby="zmp-facsimile-certificate-scroll-help" aria-keyshortcuts="ArrowLeft ArrowRight Home End">
        <table class="widefat striped zmp-facsimile-certificate-table"><thead><tr><th>Мандат</th><th>Претседател ID</th><th>Сериски број</th><th>Отпечаток на јавниот клуч</th><th>Факсимил SHA-256</th><th>Важност</th><th>Статус</th><th>Акција</th></tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="8">Нема регистрирани сертификати.</td></tr><?php else: foreach ($rows as $row): ?>
            <tr><td><?php echo (int)$row->mandate_id; ?></td><td><?php echo (int)$row->president_user_id; ?></td><td><?php echo esc_html($row->serial); ?></td><td><code><?php echo esc_html($row->public_key_id); ?></code></td><td><code><?php echo esc_html($row->facsimile_sha256); ?></code></td><td><?php echo esc_html($row->valid_from . ' — ' . $row->valid_until); ?></td><td><?php echo esc_html($row->status); ?><?php if ($row->status === 'revoked' && $row->revoked_at): ?> (<?php echo esc_html($row->revoked_at); ?>)<?php endif; ?></td>
            <td><?php if ($row->status === 'active'): ?><form method="post" action="<?php echo esc_url(self::revoke_action_url()); ?>" onsubmit="return confirm('Да се отповика сертификатот?');"><input type="hidden" name="certificate_id" value="<?php echo (int)$row->id; ?>"><?php wp_nonce_field(self::revoke_nonce_action($row->id),self::NONCE_FIELD); ?><label class="screen-reader-text" for="zmp-revoke-reason-<?php echo (int)$row->id; ?>">Причина за отповикување</label><input id="zmp-revoke-reason-<?php echo (int)$row->id; ?>" name="reason" required minlength="8" maxlength="2000"><button class="button button-secondary" type="submit">Отповикај</button></form><?php else: ?>—<?php endif; ?></td></tr>
        <?php endforeach; endif; ?></tbody></table></div>
        <script>
            (function () {
                var region = document.getElementById('zmp-facsimile-certificate-table-scroll');
                if (!region) { return; }
                region.addEventListener('keydown', function (event) {
                    if (document.activeElement !== region) { return; }
                    var step = Math.max(120, Math.floor(region.clientWidth * 0.8));
                    if (event.key === 'ArrowRight') {
                        region.scrollLeft += step;
                    } else if (event.key === 'ArrowLeft') {
                        region.scrollLeft -= step;
                    } else if (event.key === 'Home') {
                        region.scrollLeft = 0;
                    } else if (event.key === 'End') {
                        region.scrollLeft = region.scrollWidth;
                    } else {
                        return;
                    }
                    event.preventDefault();
                });
            }());
        </script></div>
        <?php
    }
}


