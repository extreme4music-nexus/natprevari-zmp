<?php
if (!defined('ABSPATH')) { exit; }

/** Dedicated storage for public WebAuthn credentials and one-use challenges. */
final class ZMP_Passkey_Store {
    const SCHEMA_VERSION = '3';
    const CHALLENGE_TTL = 300;
    const CREDENTIAL_RECORD_MAX_BYTES = 131072;

    private static $serializer = null;

    public static function init() {
        add_action('plugins_loaded', array(__CLASS__, 'install_schema'), 20);
    }

    /** Create the additive passkey tables and advance only their own schema marker. */
    public static function install_schema() {
        global $wpdb;

        if (self::tables_are_structurally_valid()
            && get_option('zmp_passkey_schema_version') === self::SCHEMA_VERSION) {
            return true;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $credentials = self::credentials_table();
        $challenges = self::challenges_table();

        $credential_sql = "CREATE TABLE {$credentials} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            credential_id_hash char(64) NOT NULL,
            credential_record longtext NOT NULL,
            label varchar(80) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            last_used_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY credential_id_hash (credential_id_hash),
            KEY user_id (user_id)
        ) {$charset_collate};";

        $challenge_sql = "CREATE TABLE {$challenges} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            challenge_hash char(64) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            session_hash char(64) NOT NULL DEFAULT '',
            ceremony varchar(16) NOT NULL,
            action varchar(64) NOT NULL DEFAULT '',
            resource_id bigint(20) unsigned NOT NULL DEFAULT 0,
            decision varchar(24) NOT NULL DEFAULT '',
            context_hash char(64) NOT NULL DEFAULT '',
            peer_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            consumed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY challenge_hash (challenge_hash),
            KEY expires_at (expires_at),
            KEY user_id (user_id),
            KEY ceremony_peer_expiry (ceremony, peer_hash, expires_at)
        ) {$charset_collate};";

        dbDelta($credential_sql);
        dbDelta($challenge_sql);

        if (!self::tables_are_structurally_valid()) {
            return false;
        }

        update_option('zmp_passkey_schema_version', self::SCHEMA_VERSION, false);
        return get_option('zmp_passkey_schema_version') === self::SCHEMA_VERSION
            && self::tables_are_structurally_valid();
    }

    public static function schema_is_compatible() {
        return self::tables_are_structurally_valid()
            && get_option('zmp_passkey_schema_version') === self::SCHEMA_VERSION;
    }

    /** Store only the verified public credential record, associated to one WP user. */
    public static function save_credential(int $user_id, \Webauthn\CredentialRecord $record, string $label): bool {
        global $wpdb;

        if ($user_id < 1 || !get_userdata($user_id) || !self::schema_is_compatible()
            || !self::valid_record($record)) {
            return false;
        }

        $credential_id = $record->publicKeyCredentialId;
        $serialized = self::serialize_record($record);
        if (!is_string($serialized) || $serialized === '' || strlen($serialized) > self::CREDENTIAL_RECORD_MAX_BYTES) {
            return false;
        }

        // Do not persist unexpected object fields that could contain private material.
        $decoded = json_decode($serialized, true);
        if (!is_array($decoded) || self::contains_private_key_field($decoded)) {
            return false;
        }

        $label = sanitize_text_field($label);
        if (function_exists('mb_substr')) {
            $label = mb_substr($label, 0, 80, 'UTF-8');
        } else {
            $label = substr($label, 0, 80);
        }

        $credential_hash = hash('sha256', $credential_id);
        $existing = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . self::credentials_table() . ' WHERE credential_id_hash = %s LIMIT 1',
            $credential_hash
        ));
        if ($existing !== null) {
            return false;
        }

        // Keep the unique index as the race-safe final guard, while suppressing WPDB's
        // default SQL error output so a duplicate can never print the serialized record.
        $previous_suppression = $wpdb->suppress_errors(true);
        $result = null;
        $ezsql_error_count = isset($GLOBALS['EZSQL_ERROR']) && is_array($GLOBALS['EZSQL_ERROR'])
            ? count($GLOBALS['EZSQL_ERROR'])
            : 0;
        try {
            $result = $wpdb->insert(
                self::credentials_table(),
                array(
                    'user_id' => $user_id,
                    'credential_id_hash' => $credential_hash,
                    'credential_record' => $serialized,
                    'label' => $label,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
        } finally {
            try {
                if ($result === false) {
                    // wpdb records a failed SQL query in EZSQL_ERROR before checking its
                    // suppression flag. Remove only entries appended by this insert whose
                    // query exactly matches this failed INSERT, then clear its in-memory copy.
                    $failed_query = $wpdb->last_query;
                    if (isset($GLOBALS['EZSQL_ERROR']) && is_array($GLOBALS['EZSQL_ERROR'])) {
                        $new_entries = array_slice($GLOBALS['EZSQL_ERROR'], $ezsql_error_count, null, true);
                        foreach ($new_entries as $key => $entry) {
                            if (is_array($entry) && ($entry['query'] ?? null) === $failed_query) {
                                unset($GLOBALS['EZSQL_ERROR'][$key]);
                            }
                        }
                    }
                    $wpdb->last_query = '';
                    $wpdb->last_error = '';
                }
            } finally {
                $wpdb->suppress_errors($previous_suppression);
            }
        }

        // A unique credential ID index rejects re-registration without overwriting ownership.
        return $result === 1;
    }

    /** Retrieve a credential only when the stored record still matches its lookup ID. */
    public static function find_credential(string $credential_id): ?\Webauthn\CredentialRecord {
        global $wpdb;

        if ($credential_id === '' || strlen($credential_id) > 2048 || !self::schema_is_compatible()) {
            return null;
        }
        $json = $wpdb->get_var($wpdb->prepare(
            'SELECT credential_record FROM ' . self::credentials_table() . ' WHERE credential_id_hash = %s LIMIT 1',
            hash('sha256', $credential_id)
        ));
        if (!is_string($json) || $json === '' || strlen($json) > self::CREDENTIAL_RECORD_MAX_BYTES) {
            return null;
        }

        try {
            $record = self::serializer()->deserialize($json, \Webauthn\CredentialRecord::class, 'json');
        } catch (Throwable $error) {
            return null;
        }

        if (!$record instanceof \Webauthn\CredentialRecord
            || !hash_equals($credential_id, $record->publicKeyCredentialId)) {
            return null;
        }
        return $record;
    }

    /**
     * Persist only a fully validated assertion's updated credential state.
     * The exact serialized prior row is part of the UPDATE predicate so concurrent
     * assertions cannot overwrite a newer authenticator counter or backup state.
     */
    public static function update_credential_after_assertion(
        int $user_id,
        string $credential_id,
        \Webauthn\CredentialRecord $updated_record
    ): bool {
        global $wpdb;

        if ($user_id < 1 || $credential_id === '' || strlen($credential_id) > 2048
            || !self::schema_is_compatible() || !self::valid_record($updated_record)
            || !hash_equals($credential_id, $updated_record->publicKeyCredentialId)) {
            return false;
        }

        $table = self::credentials_table();
        $credential_hash = hash('sha256', $credential_id);
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT credential_record FROM ' . $table . ' WHERE user_id = %d AND credential_id_hash = %s LIMIT 1',
            $user_id,
            $credential_hash
        ), ARRAY_A);
        $previous_serialized = is_array($row) ? ($row['credential_record'] ?? null) : null;
        if (!is_string($previous_serialized) || $previous_serialized === ''
            || strlen($previous_serialized) > self::CREDENTIAL_RECORD_MAX_BYTES) {
            return false;
        }

        try {
            $previous_record = self::serializer()->deserialize(
                $previous_serialized,
                \Webauthn\CredentialRecord::class,
                'json'
            );
        } catch (Throwable $error) {
            return false;
        }
        if (!self::valid_record($previous_record)
            || !hash_equals($credential_id, $previous_record->publicKeyCredentialId)
            || !hash_equals($previous_record->userHandle, $updated_record->userHandle)
            || !hash_equals($previous_record->credentialPublicKey, $updated_record->credentialPublicKey)
            || $previous_record->counter < 0 || $updated_record->counter < 0
            || (($previous_record->counter !== 0 || $updated_record->counter !== 0)
                && $updated_record->counter <= $previous_record->counter)) {
            return false;
        }

        $updated_serialized = self::serialize_record($updated_record);
        if (!is_string($updated_serialized) || $updated_serialized === ''
            || strlen($updated_serialized) > self::CREDENTIAL_RECORD_MAX_BYTES) {
            return false;
        }
        $decoded = json_decode($updated_serialized, true);
        if (!is_array($decoded) || self::contains_private_key_field($decoded)) {
            return false;
        }

        $changed = $wpdb->query($wpdb->prepare(
            'UPDATE ' . $table . ' SET credential_record = %s, last_used_at = UTC_TIMESTAMP()'
                . ' WHERE user_id = %d AND credential_id_hash = %s AND credential_record = %s',
            $updated_serialized,
            $user_id,
            $credential_hash,
            $previous_serialized
        ));
        if ($changed === 1) {
            return true;
        }

        // An authenticator with a zero counter may serialize identically. Treat only the
        // exact same row as a successful no-op; an absent, moved, or concurrently changed
        // credential remains a closed failure.
        $current_serialized = $wpdb->get_var($wpdb->prepare(
            'SELECT credential_record FROM ' . $table . ' WHERE user_id = %d AND credential_id_hash = %s LIMIT 1',
            $user_id,
            $credential_hash
        ));
        return is_string($current_serialized) && hash_equals($updated_serialized, $current_serialized);
    }

    /** Return the WordPress account bound to a credential without trusting its user handle. */
    public static function find_credential_user_id(string $credential_id): ?int {
        global $wpdb;

        if ($credential_id === '' || strlen($credential_id) > 2048 || !self::schema_is_compatible()) {
            return null;
        }
        $user_id = $wpdb->get_var($wpdb->prepare(
            'SELECT user_id FROM ' . self::credentials_table() . ' WHERE credential_id_hash = %s LIMIT 1',
            hash('sha256', $credential_id)
        ));
        return is_numeric($user_id) && (int) $user_id > 0 ? (int) $user_id : null;
    }

    /** Return a minimal owner-scoped list for the self-profile removal controls. */
    public static function list_credentials_for_user(int $user_id): array {
        global $wpdb;

        if ($user_id < 1 || !self::schema_is_compatible()) {
            return array();
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT credential_id_hash, credential_record, label, created_at FROM ' . self::credentials_table()
                . ' WHERE user_id = %d ORDER BY id ASC',
            $user_id
        ), ARRAY_A);
        if (!is_array($rows)) {
            return array();
        }

        $credentials = array();
        foreach ($rows as $row) {
            $serialized = $row['credential_record'] ?? null;
            $expected_hash = $row['credential_id_hash'] ?? null;
            if (!is_string($serialized) || $serialized === '' || strlen($serialized) > self::CREDENTIAL_RECORD_MAX_BYTES
                || !is_string($expected_hash) || !preg_match('/\A[a-f0-9]{64}\z/D', $expected_hash)) {
                continue;
            }
            try {
                $record = self::serializer()->deserialize($serialized, \Webauthn\CredentialRecord::class, 'json');
            } catch (Throwable $error) {
                continue;
            }
            if (!self::valid_record($record) || !hash_equals($expected_hash, hash('sha256', $record->publicKeyCredentialId))) {
                continue;
            }
            $credentials[] = array(
                'credential_id' => $record->publicKeyCredentialId,
                'label' => is_string($row['label'] ?? null) ? sanitize_text_field($row['label']) : '',
                'created_at' => is_string($row['created_at'] ?? null) ? $row['created_at'] : '',
            );
        }
        return $credentials;
    }

    /** Create a 256-bit, five-minute challenge; only its SHA-256 hash is stored. */
    public static function create_challenge(array $binding): ?string {
        global $wpdb;

        $binding = self::normalize_binding($binding);
        if ($binding === null || !self::schema_is_compatible()
            || ($binding['ceremony'] === 'login' && $binding['peer_hash'] === '')) {
            return null;
        }

        try {
            $challenge = self::base64url_encode(random_bytes(32));
        } catch (Throwable $error) {
            return null;
        }

        // Remove expired one-use records before adding another row. A failed
        // cleanup must stop issuance so retained rows cannot grow while quota
        // checks ignore expired records.
        try {
            $cleanup_result = $wpdb->query(
                'DELETE FROM ' . self::challenges_table() . ' WHERE expires_at <= UTC_TIMESTAMP()'
            );
        } catch (Throwable $error) {
            return null;
        }
        if ($cleanup_result === false) {
            return null;
        }

        $now = time();
        $context_hash = $binding['context_hash'] ?? '';
        $peer_hash = $binding['peer_hash'] ?? '';
        $result = $wpdb->insert(
            self::challenges_table(),
            array_merge($binding, array(
                'context_hash' => $context_hash,
                'peer_hash' => $peer_hash,
                'challenge_hash' => hash('sha256', $challenge),
                'created_at' => gmdate('Y-m-d H:i:s', $now),
                'expires_at' => gmdate('Y-m-d H:i:s', $now + self::CHALLENGE_TTL),
                'consumed_at' => null,
            )),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        if ($result !== 1) {
            return null;
        }

        return $challenge;
    }

    /** Atomically consume a challenge only for its exact ceremony and action-context binding. */
    public static function consume_challenge(string $challenge_id, array $expected_binding): ?array {
        global $wpdb;

        $binding = self::normalize_binding($expected_binding);
        if ($binding === null || !self::is_challenge($challenge_id) || !self::schema_is_compatible()) {
            return null;
        }

        // Schema-v2 login rows receive peer_hash=''; the login-only predicate below
        // makes those unbound legacy challenges unusable until they expire naturally.
        $sql = 'UPDATE ' . self::challenges_table()
            . ' SET consumed_at = UTC_TIMESTAMP() WHERE challenge_hash = %s'
            . ' AND user_id = %d AND session_hash = %s AND ceremony = %s AND action = %s'
            . ' AND resource_id = %d AND decision = %s AND context_hash = %s'
            . " AND (ceremony <> 'login' OR peer_hash <> '') AND consumed_at IS NULL"
            . ' AND expires_at > UTC_TIMESTAMP()';
        $changed = $wpdb->query($wpdb->prepare(
            $sql,
            hash('sha256', $challenge_id),
            $binding['user_id'],
            $binding['session_hash'],
            $binding['ceremony'],
            $binding['action'],
            $binding['resource_id'],
            $binding['decision'],
            $binding['context_hash'] ?? ''
        ));

        return $changed === 1 ? $binding : null;
    }

    /** Delete only the selected user's credential; return false for a different owner. */
    public static function delete_credential(int $user_id, string $credential_id): bool {
        global $wpdb;

        if ($user_id < 1 || $credential_id === '' || strlen($credential_id) > 2048 || !self::schema_is_compatible()) {
            return false;
        }
        $deleted = $wpdb->delete(
            self::credentials_table(),
            array('user_id' => $user_id, 'credential_id_hash' => hash('sha256', $credential_id)),
            array('%d', '%s')
        );
        return $deleted === 1;
    }

    private static function valid_record($record) {
        return $record instanceof \Webauthn\CredentialRecord
            && is_string($record->publicKeyCredentialId) && $record->publicKeyCredentialId !== ''
            && strlen($record->publicKeyCredentialId) <= 2048
            && is_string($record->credentialPublicKey) && $record->credentialPublicKey !== ''
            && strlen($record->credentialPublicKey) <= 65535
            && is_string($record->userHandle) && $record->userHandle !== ''
            && strlen($record->userHandle) <= 64;
    }

    private static function serialize_record(\Webauthn\CredentialRecord $record) {
        try {
            return self::serializer()->serialize($record, 'json');
        } catch (Throwable $error) {
            return null;
        }
    }

    private static function serializer() {
        if (self::$serializer === null) {
            $manager = \Webauthn\AttestationStatement\AttestationStatementSupportManager::create();
            $factory = new \Webauthn\Denormalizer\WebauthnSerializerFactory($manager);
            self::$serializer = $factory->create();
        }
        return self::$serializer;
    }

    private static function normalize_binding(array $binding) {
        $allowed = array('user_id', 'session_hash', 'ceremony', 'action', 'resource_id', 'decision', 'context_hash', 'peer_hash');
        if (array_diff(array_keys($binding), $allowed)) {
            return null;
        }

        $user_id = $binding['user_id'] ?? 0;
        $resource_id = $binding['resource_id'] ?? 0;
        $session_hash = $binding['session_hash'] ?? '';
        $ceremony = $binding['ceremony'] ?? '';
        $action = $binding['action'] ?? '';
        $decision = $binding['decision'] ?? '';
        $context_hash = $binding['context_hash'] ?? '';
        $peer_hash = $binding['peer_hash'] ?? '';

        if (!is_numeric($user_id) || (int) $user_id < 0 || (string) (int) $user_id !== (string) $user_id
            || !is_numeric($resource_id) || (int) $resource_id < 0 || (string) (int) $resource_id !== (string) $resource_id
            || !is_string($session_hash) || ($session_hash !== '' && !preg_match('/\A[a-f0-9]{64}\z/D', $session_hash))
            || !is_string($ceremony) || !in_array($ceremony, array('registration', 'login', 'step_up'), true)
            || !is_string($action) || strlen($action) > 64 || ($action !== '' && !preg_match('/\A[a-z0-9_:-]+\z/D', $action))
            || !is_string($decision) || strlen($decision) > 24 || ($decision !== '' && !preg_match('/\A[a-z0-9_:-]+\z/D', $decision))
            || !is_string($context_hash) || ($context_hash !== '' && !preg_match('/\A[a-f0-9]{64}\z/D', $context_hash))
            || !is_string($peer_hash) || ($peer_hash !== '' && !preg_match('/\A[a-f0-9]{64}\z/D', $peer_hash))) {
            return null;
        }

        $user_id = (int) $user_id;
        $resource_id = (int) $resource_id;
        if ($ceremony === 'registration') {
            if (!array_key_exists('user_id', $binding) || !array_key_exists('session_hash', $binding)
                || $user_id < 1 || !self::user_exists($user_id) || $session_hash === ''
                || $action !== '' || $resource_id !== 0 || $decision !== '' || $context_hash !== '' || $peer_hash !== '') {
                return null;
            }
        } elseif ($ceremony === 'login') {
            if ($user_id !== 0 || $session_hash !== '' || $action !== '' || $resource_id !== 0 || $decision !== ''
                || $context_hash === '') {
                return null;
            }
        } elseif ($ceremony === 'step_up') {
            if (!array_key_exists('user_id', $binding) || !array_key_exists('session_hash', $binding)
                || !array_key_exists('action', $binding) || !array_key_exists('resource_id', $binding)
                || !array_key_exists('decision', $binding) || $user_id < 1 || !self::user_exists($user_id)
                || $session_hash === '' || $action === '' || $resource_id < 1 || $decision === '' || $peer_hash !== '') {
                return null;
            }
            if ($context_hash === '') {
                return null;
            }
        } elseif ($context_hash !== '' || $peer_hash !== '') {
            return null;
        }

        $normalized = array(
            'user_id' => $user_id,
            'session_hash' => $session_hash,
            'ceremony' => $ceremony,
            'action' => $action,
            'resource_id' => $resource_id,
            'decision' => $decision,
            'peer_hash' => $peer_hash,
        );
        if (array_key_exists('context_hash', $binding)) {
            $normalized['context_hash'] = $context_hash;
        }
        return $normalized;
    }

    private static function user_exists($user_id) {
        return $user_id > 0 && get_userdata($user_id) instanceof WP_User;
    }

    private static function contains_private_key_field(array $value) {
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/private.?key|secret.?key/i', $key)) {
                return true;
            }
            if (is_array($item) && self::contains_private_key_field($item)) {
                return true;
            }
        }
        return false;
    }

    private static function is_challenge($value) {
        return is_string($value) && preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value) === 1;
    }

    private static function base64url_encode($value) {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function table_exists($table) {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        return is_string($found) && $found === $table;
    }

    private static function tables_are_structurally_valid() {
        $credential_columns = array(
            'id', 'user_id', 'credential_id_hash', 'credential_record', 'label', 'created_at', 'last_used_at',
        );
        $challenge_columns = array(
            'id', 'challenge_hash', 'user_id', 'session_hash', 'ceremony', 'action', 'resource_id', 'decision', 'context_hash', 'peer_hash',
            'created_at', 'expires_at', 'consumed_at',
        );
        $credential_indexes = array(
            'PRIMARY' => array(0, array('id')),
            'credential_id_hash' => array(0, array('credential_id_hash')),
            'user_id' => array(1, array('user_id')),
        );
        $challenge_indexes = array(
            'PRIMARY' => array(0, array('id')),
            'challenge_hash' => array(0, array('challenge_hash')),
            'expires_at' => array(1, array('expires_at')),
            'user_id' => array(1, array('user_id')),
            'ceremony_peer_expiry' => array(1, array('ceremony', 'peer_hash', 'expires_at')),
        );

        return self::table_structure_is_valid(self::credentials_table(), $credential_columns, $credential_indexes)
            && self::table_structure_is_valid(
                self::challenges_table(),
                $challenge_columns,
                $challenge_indexes,
                array('peer_hash' => array('type' => 'char(64)', 'null' => 'NO', 'default' => ''))
            );
    }

    private static function table_structure_is_valid(
        $table,
        array $required_columns,
        array $required_indexes,
        array $required_column_definitions = array()
    ) {
        global $wpdb;

        if (!self::table_exists($table)) {
            return false;
        }
        $column_rows = $wpdb->get_results('SHOW COLUMNS FROM ' . $table, ARRAY_A);
        if (!is_array($column_rows)) {
            return false;
        }
        $columns = array();
        $column_definitions = array();
        foreach ($column_rows as $column) {
            if (!isset($column['Field'], $column['Type'], $column['Null'])
                || !array_key_exists('Default', $column)) {
                return false;
            }
            $name = (string) $column['Field'];
            $columns[] = $name;
            $column_definitions[$name] = array(
                'type' => strtolower((string) $column['Type']),
                'null' => strtoupper((string) $column['Null']),
                'default' => $column['Default'],
            );
        }
        if (array_diff($required_columns, $columns)) {
            return false;
        }
        foreach ($required_column_definitions as $name => $expected) {
            if (!isset($column_definitions[$name])
                || $column_definitions[$name]['type'] !== strtolower((string) ($expected['type'] ?? ''))
                || $column_definitions[$name]['null'] !== strtoupper((string) ($expected['null'] ?? ''))
                || $column_definitions[$name]['default'] !== ($expected['default'] ?? null)) {
                return false;
            }
        }

        $rows = $wpdb->get_results('SHOW INDEX FROM ' . $table, ARRAY_A);
        if (!is_array($rows)) {
            return false;
        }
        $indexes = array();
        foreach ($rows as $row) {
            if (!isset($row['Key_name'], $row['Column_name'], $row['Seq_in_index'], $row['Non_unique'])) {
                continue;
            }
            $name = (string) $row['Key_name'];
            $indexes[$name]['non_unique'] = (int) $row['Non_unique'];
            $indexes[$name]['columns'][(int) $row['Seq_in_index']] = (string) $row['Column_name'];
        }

        foreach ($required_indexes as $name => $expected) {
            if (!isset($indexes[$name]) || $indexes[$name]['non_unique'] !== $expected[0]) {
                return false;
            }
            $actual_columns = $indexes[$name]['columns'] ?? array();
            ksort($actual_columns);
            if (array_values($actual_columns) !== $expected[1]) {
                return false;
            }
        }
        return true;
    }

    private static function credentials_table() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_passkey_credentials';
    }

    private static function challenges_table() {
        global $wpdb;
        return $wpdb->prefix . 'zmp_passkey_challenges';
    }
}
