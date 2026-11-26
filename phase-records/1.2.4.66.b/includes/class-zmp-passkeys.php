<?php
if (!defined('ABSPATH')) { exit; }

/** Passkey ceremonies bound to existing WordPress identity and session state. */
final class ZMP_Passkeys {
    const USER_HANDLE_META = 'zmp_passkey_user_handle';
    const MAX_REGISTRATION_PAYLOAD_BYTES = 131072;
    const MAX_LOGIN_PAYLOAD_BYTES = 131072;
    const MAX_PENDING_LOGIN_CHALLENGES_PER_IP = 5;
    const MAX_TOTAL_LOGIN_CHALLENGES = 500;

    private static $serializer = null;

    /** Register the anonymous passkey sign-in endpoints without changing WP password login. */
    public static function init() {
        add_action('wp_ajax_nopriv_zmp_passkey_login_options', array(__CLASS__, 'ajax_login_options'));
        add_action('wp_ajax_nopriv_zmp_passkey_login_finish', array(__CLASS__, 'ajax_login_finish'));
        add_action('wp_ajax_zmp_president_subject_step_up_options', array(__CLASS__, 'ajax_step_up_options'));
    }

    /** Create assertion options for one exact President proposal decision. */
    public static function step_up_options($user_id, $action, $resource_id, $decision, $canonical_subject_id, $route_nonce, $context_binding = '') {
        $user_id = self::positive_id($user_id);
        $resource_id = self::positive_id($resource_id);
        $canonical_subject_id = self::positive_id($canonical_subject_id, true);
        if (!$user_id || !$resource_id || !self::valid_step_up_context($action, $decision, $canonical_subject_id, $context_binding)) {
            return self::step_up_error('step_up_context_invalid');
        }
        if (!is_string($route_nonce) || $route_nonce === ''
            || !wp_verify_nonce($route_nonce, self::step_up_nonce_action($action))) {
            return self::step_up_error('step_up_nonce_invalid');
        }
        if (!self::can_perform_step_up($user_id, $action)) {
            return self::step_up_error('step_up_authority_session_unavailable');
        }
        if (!self::is_secure_request_context()) {
            return self::step_up_error('step_up_secure_origin_invalid');
        }
        if (!class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()) {
            return self::step_up_error('step_up_store_schema_unavailable');
        }
        if (!self::library_available()) {
            return self::step_up_error('step_up_library_unavailable');
        }
        if (!ZMP_Passkey_Store::list_credentials_for_user($user_id)) {
            return self::step_up_error('step_up_credentials_unavailable');
        }

        $origin = self::site_origin();
        $session_hash = self::current_session_hash($user_id);
        if ($origin === null) { return self::step_up_error('step_up_secure_origin_invalid'); }
        if ($session_hash === null) { return self::step_up_error('step_up_authority_session_unavailable'); }
        $context_hash = $origin !== null ? self::step_up_context_digest(
            $origin, $user_id, $session_hash, $action, $resource_id, $decision, $canonical_subject_id, $route_nonce, $context_binding
        ) : null;
        if ($context_hash === null) { return self::step_up_error('step_up_context_invalid'); }

        try {
            $challenge_id = ZMP_Passkey_Store::create_challenge(array(
                'user_id'=>$user_id,
                'session_hash'=>$session_hash,
                'ceremony'=>'step_up',
                'action'=>$action,
                'resource_id'=>$resource_id,
                'decision'=>$decision,
                'context_hash'=>$context_hash,
            ));
        } catch (Throwable $error) {
            return self::step_up_error('step_up_challenge_insert_failed');
        }
        if (!is_string($challenge_id) || $challenge_id === '') {
            return self::step_up_error('step_up_challenge_insert_failed');
        }

        try {
            $options_object = self::request_options($challenge_id, $origin);
        } catch (Throwable $error) {
            self::burn_step_up_challenge($challenge_id, $user_id, $session_hash, $action, $resource_id, $decision, $context_hash);
            return self::step_up_error('step_up_options_generation_failed');
        }
        try {
            $serialized = self::serializer()->serialize($options_object, 'json');
            $options = json_decode($serialized, true);
        } catch (Throwable $error) {
            self::burn_step_up_challenge($challenge_id, $user_id, $session_hash, $action, $resource_id, $decision, $context_hash);
            return self::step_up_error('step_up_options_serialization_invalid');
        }
        if (!is_array($options) || !isset($options['challenge'], $options['rpId'], $options['userVerification'])
            || !is_string($options['challenge']) || !hash_equals($challenge_id, $options['challenge'])
            || !is_string($options['rpId']) || !hash_equals($origin['host'], strtolower($options['rpId']))
            || $options['userVerification'] !== 'required'
            || (isset($options['allowCredentials']) && !is_array($options['allowCredentials']))) {
            self::burn_step_up_challenge($challenge_id, $user_id, $session_hash, $action, $resource_id, $decision, $context_hash);
            return self::step_up_error('step_up_options_serialization_invalid');
        }
        return array('challenge_id'=>$challenge_id, 'options'=>$options);
    }

    /** Verify recent authenticator control for the exact submitted proposal decision. */
    public static function verify_step_up($user_id, $challenge_id, $action, $resource_id, $decision, $canonical_subject_id, $payload_json, $route_nonce, $context_binding = '') {
        $user_id = self::positive_id($user_id);
        $resource_id = self::positive_id($resource_id);
        $canonical_subject_id = self::positive_id($canonical_subject_id, true);
        if (!$user_id || !is_string($challenge_id) || $challenge_id === '' || strlen($challenge_id) > 64
            || !is_string($payload_json) || $payload_json === '' || strlen($payload_json) > self::MAX_LOGIN_PAYLOAD_BYTES
            || !self::valid_step_up_context($action, $decision, $canonical_subject_id, $context_binding)
            || !is_string($route_nonce) || $route_nonce === ''
            || !wp_verify_nonce($route_nonce, self::step_up_nonce_action($action))
            || !self::is_secure_request_context() || !self::can_perform_step_up($user_id, $action)
            || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()
            || !self::library_available()) {
            return self::generic_error();
        }

        $session_hash = self::current_session_hash($user_id);
        $origin = self::site_origin();
        $context_hash = $origin !== null ? self::step_up_context_digest(
            $origin, $user_id, $session_hash, $action, $resource_id, $decision, $canonical_subject_id, $route_nonce, $context_binding
        ) : null;
        if ($origin === null || $context_hash === null) { return self::generic_error(); }

        // Consume the exact challenge before parsing assertion data to prevent replay.
        $binding = ZMP_Passkey_Store::consume_challenge($challenge_id, array(
            'user_id'=>$user_id,
            'session_hash'=>$session_hash,
            'ceremony'=>'step_up',
            'action'=>$action,
            'resource_id'=>$resource_id,
            'decision'=>$decision,
            'context_hash'=>$context_hash,
        ));
        if (!is_array($binding)) { return self::generic_error(); }

        $payload = json_decode($payload_json);
        if (json_last_error() !== JSON_ERROR_NONE || !$payload instanceof stdClass
            || !isset($payload->response) || !$payload->response instanceof stdClass) {
            return self::generic_error();
        }

        try {
            $credential = self::serializer()->deserialize($payload_json, \Webauthn\PublicKeyCredential::class, 'json');
            if (!$credential instanceof \Webauthn\PublicKeyCredential
                || !$credential->response instanceof \Webauthn\AuthenticatorAssertionResponse
                || !is_string($credential->rawId) || $credential->rawId === '' || strlen($credential->rawId) > 2048
                || ZMP_Passkey_Store::find_credential_user_id($credential->rawId) !== $user_id) {
                return self::generic_error();
            }
            $record = ZMP_Passkey_Store::find_credential($credential->rawId);
            $expected_handle = self::user_handle($user_id, false);
            if (!$record instanceof \Webauthn\CredentialRecord || $expected_handle === null
                || !hash_equals($credential->rawId, $record->publicKeyCredentialId)
                || !hash_equals($expected_handle, $record->userHandle)) {
                return self::generic_error();
            }

            $updated_record = \Webauthn\AuthenticatorAssertionResponseValidator::create(
                self::ceremony_factory($origin)->requestCeremony()
            )->check($record, $credential->response, self::request_options($challenge_id, $origin), $origin['host'], $expected_handle);
            if (!$updated_record instanceof \Webauthn\CredentialRecord
                || !ZMP_Passkey_Store::update_credential_after_assertion($user_id, $credential->rawId, $updated_record)) {
                return self::generic_error();
            }
        } catch (Throwable $error) {
            return self::generic_error();
        }
        return true;
    }

    /** Authenticated same-origin transport for President step-up options. */
    public static function ajax_step_up_options() {
        if (!self::is_secure_request_context()) {
            wp_send_json_error(array('message'=>__('Passkey verification could not be completed.', 'zmp-competitions')), 400);
        }
        $nonce = isset($_POST['route_nonce']) && is_scalar($_POST['route_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['route_nonce'])) : '';
        $action = isset($_POST['decision_action']) && is_scalar($_POST['decision_action'])
            ? sanitize_key(wp_unslash((string) $_POST['decision_action'])) : '';
        $proposal_id = self::positive_id(isset($_POST['proposal_id']) ? wp_unslash($_POST['proposal_id']) : null);
        $decision = isset($_POST['decision']) && is_scalar($_POST['decision'])
            ? sanitize_key(wp_unslash((string) $_POST['decision'])) : '';
        $canonical_id = self::positive_id(isset($_POST['canonical_subject_id']) ? wp_unslash($_POST['canonical_subject_id']) : null, true);
        if (!is_user_logged_in() || $action !== 'president_subject_decision' || !$proposal_id) {
            wp_send_json_error(array('message'=>__('Passkey verification could not be completed.', 'zmp-competitions')), 400);
        }
        $result = self::step_up_options(get_current_user_id(), $action, $proposal_id, $decision, $canonical_id, $nonce);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message'=>__('Passkey verification could not be completed.', 'zmp-competitions')), 400);
        }
        wp_send_json_success($result);
    }

    /** Create discoverable-credential request options without accepting an account identifier. */
    public static function login_options() {
        return self::rate_limited_login_options();
    }

    /** Create options only after the caller holds the verified-peer database lock. */
    private static function create_login_options($peer_hash) {
        if (is_user_logged_in() || !class_exists('ZMP_Passkey_Store')
            || !ZMP_Passkey_Store::schema_is_compatible() || !self::library_available()) {
            return self::generic_error();
        }

        $origin = self::site_origin();
        if ($origin === null) {
            return self::generic_error();
        }
        $origin_digest = self::login_origin_digest($origin);
        if ($origin_digest === null) {
            return self::generic_error();
        }

        $challenge_id = ZMP_Passkey_Store::create_challenge(array(
            'user_id' => 0,
            'session_hash' => '',
            'ceremony' => 'login',
            'context_hash' => $origin_digest,
            'peer_hash' => $peer_hash,
        ));
        if (!is_string($challenge_id) || $challenge_id === '') {
            return self::generic_error();
        }

        try {
            $request_options = self::request_options($challenge_id, $origin);
            $serialized = self::serializer()->serialize($request_options, 'json');
            $options = json_decode($serialized, true);
            if (!is_array($options) || !isset($options['challenge'], $options['rpId'])
                || !is_string($options['challenge']) || !hash_equals($challenge_id, $options['challenge'])
                || !is_string($options['rpId']) || !hash_equals($origin['host'], strtolower($options['rpId']))
                || !isset($options['userVerification']) || $options['userVerification'] !== 'required'
                || (isset($options['allowCredentials']) && (!is_array($options['allowCredentials']) || $options['allowCredentials']))) {
                self::burn_login_challenge($challenge_id, $origin_digest);
                return self::generic_error();
            }
            return array('challenge_id' => $challenge_id, 'options' => $options);
        } catch (Throwable $error) {
            self::burn_login_challenge($challenge_id, $origin_digest);
            return self::generic_error();
        }
    }

    /** Verify a discoverable assertion and create a WP session only after atomic challenge/record checks. */
    public static function finish_login($challenge_id, $payload_json) {
        if (!is_string($challenge_id) || $challenge_id === '' || strlen($challenge_id) > 64
            || !is_string($payload_json) || is_user_logged_in() || !self::is_secure_request_context()
            || !class_exists('ZMP_Passkey_Store') || !ZMP_Passkey_Store::schema_is_compatible()
            || !self::library_available() || $payload_json === '' || strlen($payload_json) > self::MAX_LOGIN_PAYLOAD_BYTES) {
            return self::generic_error();
        }

        $origin = self::site_origin();
        $origin_digest = $origin !== null ? self::login_origin_digest($origin) : null;
        if ($origin === null || $origin_digest === null) {
            return self::generic_error();
        }

        $binding = ZMP_Passkey_Store::consume_challenge($challenge_id, array(
            'user_id' => 0,
            'session_hash' => '',
            'ceremony' => 'login',
            'context_hash' => $origin_digest,
        ));
        if (!is_array($binding)) {
            return self::generic_error();
        }

        $payload = json_decode($payload_json);
        if (json_last_error() !== JSON_ERROR_NONE || !$payload instanceof stdClass
            || !isset($payload->response) || !$payload->response instanceof stdClass) {
            return self::generic_error();
        }

        try {
            $credential = self::serializer()->deserialize(
                $payload_json,
                \Webauthn\PublicKeyCredential::class,
                'json'
            );
            if (!$credential instanceof \Webauthn\PublicKeyCredential
                || !$credential->response instanceof \Webauthn\AuthenticatorAssertionResponse
                || !is_string($credential->rawId) || $credential->rawId === ''
                || strlen($credential->rawId) > 2048) {
                return self::generic_error();
            }

            // Resolve ownership from the private credential table, never from userHandle/client input.
            $user_id = ZMP_Passkey_Store::find_credential_user_id($credential->rawId);
            $record = ZMP_Passkey_Store::find_credential($credential->rawId);
            $user = $user_id !== null ? get_userdata($user_id) : false;
            $expected_handle = $user_id !== null ? self::user_handle($user_id, false) : null;
            if (!is_int($user_id) || !$user instanceof WP_User || $expected_handle === null
                || !$record instanceof \Webauthn\CredentialRecord
                || !hash_equals($credential->rawId, $record->publicKeyCredentialId)
                || !hash_equals($expected_handle, $record->userHandle)) {
                return self::generic_error();
            }

            $request_options = self::request_options($challenge_id, $origin);
            $factory = self::ceremony_factory($origin);
            $validator = \Webauthn\AuthenticatorAssertionResponseValidator::create(
                $factory->requestCeremony()
            );
            $updated_record = $validator->check(
                $record,
                $credential->response,
                $request_options,
                $origin['host'],
                $expected_handle
            );
            if (!$updated_record instanceof \Webauthn\CredentialRecord
                || !ZMP_Passkey_Store::update_credential_after_assertion($user_id, $credential->rawId, $updated_record)) {
                return self::generic_error();
            }
        } catch (Throwable $error) {
            // Keep assertion, credential and validator details out of public output and logs.
            return self::generic_error();
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, false, is_ssl());
        do_action('wp_login', $user->user_login, $user);
        return array('authenticated' => true, 'redirect' => home_url('/'));
    }

    /** Public transport wrapper; same-origin checks fail closed before creating a challenge. */
    public static function ajax_login_options() {
        if (!self::is_secure_request_context()) {
            self::send_login_error();
        }
        $result = self::login_options();
        if (is_wp_error($result)) {
            self::send_login_error();
        }
        wp_send_json_success($result);
    }

    /** Public transport wrapper; all rejected assertions receive one fixed response. */
    public static function ajax_login_finish() {
        $challenge_id = isset($_POST['challenge_id']) ? wp_unslash($_POST['challenge_id']) : '';
        $payload_json = isset($_POST['credential']) ? wp_unslash($_POST['credential']) : '';
        if (!is_string($challenge_id) || !is_string($payload_json)) {
            self::send_login_error();
        }
        $result = self::finish_login($challenge_id, $payload_json);
        if (is_wp_error($result)) {
            self::send_login_error();
        }
        wp_send_json_success($result);
    }

    private static function send_login_error() {
        wp_send_json_error(
            array('message' => __('Passkey sign-in could not be completed. Try again or use your password.', 'zmp-competitions')),
            400
        );
    }

    /** Serialize global and per-peer durable issuance counts with challenge insertion. */
    private static function rate_limited_login_options() {
        global $wpdb;

        $peer_hash = self::request_peer_hash();
        if ($peer_hash === null || !class_exists('ZMP_Passkey_Store')
            || !ZMP_Passkey_Store::schema_is_compatible()) {
            return self::generic_error();
        }

        // Every issuance takes this shared lock before its peer-specific lock, so the
        // aggregate cap and per-peer cap are checked atomically with the insert.
        $global_lock_name = 'zmpplr_global';
        $peer_lock_name = 'zmpplr_' . substr($peer_hash, 0, 48);
        $global_lock_acquired = false;
        $peer_lock_acquired = false;
        try {
            $global_lock_acquired = (string) $wpdb->get_var($wpdb->prepare(
                'SELECT GET_LOCK(%s, %d)',
                $global_lock_name,
                5
            )) === '1';
        } catch (Throwable $error) {
            return self::generic_error();
        }
        if (!$global_lock_acquired) {
            return self::generic_error();
        }

        $result = null;
        $origin_digest = null;
        $peer_released = true;
        $global_released = false;
        try {
            $peer_lock_acquired = (string) $wpdb->get_var($wpdb->prepare(
                'SELECT GET_LOCK(%s, %d)',
                $peer_lock_name,
                5
            )) === '1';
            if (!$peer_lock_acquired) {
                return self::generic_error();
            }

            $total_count = $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'zmp_passkey_challenges'
                    . ' WHERE ceremony = %s'
                    . ' AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d SECOND)'
                    . ' AND expires_at > UTC_TIMESTAMP()',
                'login',
                ZMP_Passkey_Store::CHALLENGE_TTL
            ));
            if (!is_numeric($total_count)) {
                return self::generic_error();
            }
            if ((int) $total_count >= self::MAX_TOTAL_LOGIN_CHALLENGES) {
                return self::generic_error();
            }

            $peer_count = $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'zmp_passkey_challenges'
                    . ' WHERE ceremony = %s AND peer_hash = %s'
                    . ' AND created_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d SECOND)'
                    . ' AND expires_at > UTC_TIMESTAMP()',
                'login',
                $peer_hash,
                ZMP_Passkey_Store::CHALLENGE_TTL
            ));
            if (!is_numeric($peer_count)) {
                return self::generic_error();
            }
            if ((int) $peer_count >= self::MAX_PENDING_LOGIN_CHALLENGES_PER_IP) {
                return self::generic_error();
            }

            $origin = self::site_origin();
            $origin_digest = $origin !== null ? self::login_origin_digest($origin) : null;
            if ($origin_digest === null) {
                return self::generic_error();
            }
            $result = self::create_login_options($peer_hash);
        } catch (Throwable $error) {
            $result = self::generic_error();
        } finally {
            if ($peer_lock_acquired) {
                try {
                    $peer_released = (string) $wpdb->get_var($wpdb->prepare(
                        'SELECT RELEASE_LOCK(%s)',
                        $peer_lock_name
                    )) === '1';
                } catch (Throwable $error) {
                    $peer_released = false;
                }
            }
            try {
                $global_released = (string) $wpdb->get_var($wpdb->prepare(
                    'SELECT RELEASE_LOCK(%s)',
                    $global_lock_name
                )) === '1';
            } catch (Throwable $error) {
                $global_released = false;
            }
        }

        if (!$peer_released || !$global_released) {
            if (is_array($result) && isset($result['challenge_id']) && $origin_digest !== null) {
                self::burn_login_challenge($result['challenge_id'], $origin_digest);
            }
            return self::generic_error();
        }
        return is_array($result) ? $result : self::generic_error();
    }

    /** Return a keyed digest of the canonical, validated direct request peer. */
    private static function request_peer_hash() {
        if (!isset($_SERVER['REMOTE_ADDR']) || !is_string($_SERVER['REMOTE_ADDR'])
            || filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) === false
            || !function_exists('wp_salt')) {
            return null;
        }
        $packed = @inet_pton($_SERVER['REMOTE_ADDR']);
        $canonical_peer = $packed !== false ? @inet_ntop($packed) : false;
        $key = wp_salt('auth');
        if (!is_string($canonical_peer) || $canonical_peer === '' || !is_string($key) || $key === '') {
            return null;
        }
        return hash_hmac('sha256', $canonical_peer, $key);
    }

    /** Bind anonymous login to the exact canonical configured origin and RP host. */
    private static function login_origin_digest(array $origin) {
        if (empty($origin['origin']) || empty($origin['host'])) {
            return null;
        }
        $parts = wp_parse_url($origin['origin']);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $default_port = $scheme === 'https' ? 443 : 80;
        $port = isset($parts['port']) ? (int) $parts['port'] : $default_port;
        if (($scheme !== 'https' && $scheme !== 'http') || $host !== $origin['host']
            || $port < 1 || $port > 65535) {
            return null;
        }
        $canonical_origin = $scheme . '://' . $host
            . ($port !== $default_port ? ':' . $port : '');
        // Domain-separated, versioned, and delimiter-bounded to prevent ambiguous encodings.
        return hash('sha256', "zmp-passkey-login-origin-v1\n" . $canonical_origin . "\n" . $host);
    }

    public static function can_register_for_user(int $user_id): bool {
        if (self::has_active_president_authority($user_id) || user_can($user_id, 'manage_options')) { return true; }
        if (!user_can($user_id, 'zmp_commission_cap') && !user_can($user_id, 'view_zmp_commission_dashboard')) { return false; }
        if (!class_exists('ZMP_Category_Registrations') || !class_exists('ZMP_Category_Jury')) { return false; }
        $season = ZMP_Category_Registrations::active_season();
        if (!$season || empty($season->id)) { return false; }
        global $wpdb;
        $assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND juror_user_id=%d",
            absint($season->id), $user_id
        ));
        if ($wpdb->last_error !== '') { return false; }
        if ((int) $assigned > 0) { return true; }

        // Regional-only jury members can perform assigned regional work without a
        // category-level row; require an active migration and a valid exact assignment.
        if (get_option('zmp_regional_jury_migration_49_2') !== 'complete') { return false; }
        $wpdb->last_error = '';
        $regional_categories = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT category_id FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d AND juror_user_id=%d",
            absint($season->id), $user_id
        ));
        if ($wpdb->last_error !== '') { return false; }
        foreach ((array) $regional_categories as $category_id) {
            if (ZMP_Category_Jury::has_any_assignment((int) $season->id, absint($category_id), $user_id)) { return true; }
        }
        return false;
    }

    /** Require a secure actual request context for credential removal. */
    public static function is_secure_request_context(): bool {
        $configured = self::site_origin();
        if ($configured === null || !isset($_SERVER['HTTP_HOST']) || !is_string($_SERVER['HTTP_HOST'])) {
            return false;
        }

        $request_is_https = function_exists('is_ssl') && is_ssl();
        $request_scheme = $request_is_https ? 'https' : 'http';
        if (isset($_SERVER['REQUEST_SCHEME']) && is_string($_SERVER['REQUEST_SCHEME'])
            && strtolower($_SERVER['REQUEST_SCHEME']) !== $request_scheme) {
            return false;
        }

        $request_host_parts = wp_parse_url('http://' . $_SERVER['HTTP_HOST']);
        if (!is_array($request_host_parts) || empty($request_host_parts['host'])
            || isset($request_host_parts['user']) || isset($request_host_parts['pass'])
            || isset($request_host_parts['path']) || isset($request_host_parts['query'])
            || isset($request_host_parts['fragment'])) {
            return false;
        }
        $request_host = strtolower(rtrim((string) $request_host_parts['host'], '.'));
        $default_port = $request_is_https ? 443 : 80;
        $server_port = isset($_SERVER['SERVER_PORT']) && ctype_digit((string) $_SERVER['SERVER_PORT'])
            ? (int) $_SERVER['SERVER_PORT']
            : $default_port;
        $request_port = isset($request_host_parts['port']) ? (int) $request_host_parts['port'] : $server_port;
        if ($server_port !== $request_port) {
            return false;
        }
        if ($request_port < 1 || $request_port > 65535) {
            return false;
        }

        // Require every supplied browser source header to agree with the actual request.
        // Never infer the request transport or peer identity from forwarded headers.
        $source_headers = array();
        if (isset($_SERVER['HTTP_ORIGIN']) && is_string($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== '') {
            $source_headers[] = array($_SERVER['HTTP_ORIGIN'], true);
        }
        if (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
            $source_headers[] = array($_SERVER['HTTP_REFERER'], false);
        }
        if (!$source_headers) {
            return false;
        }
        foreach ($source_headers as $source_header) {
            list($source, $require_empty_path) = $source_header;
            $source_parts = wp_parse_url($source);
            if (!is_array($source_parts) || empty($source_parts['scheme']) || empty($source_parts['host'])
                || isset($source_parts['user']) || isset($source_parts['pass'])
                || ($require_empty_path && (isset($source_parts['path']) || isset($source_parts['query']) || isset($source_parts['fragment'])))) {
                return false;
            }
            $source_scheme = strtolower((string) $source_parts['scheme']);
            $source_host = strtolower(rtrim((string) $source_parts['host'], '.'));
            $source_port = isset($source_parts['port'])
                ? (int) $source_parts['port']
                : ($source_scheme === 'https' ? 443 : 80);
            if ($source_scheme !== $request_scheme || $source_host !== $request_host || $source_port !== $request_port) {
                return false;
            }
        }

        $configured_parts = wp_parse_url($configured['origin']);
        if (!is_array($configured_parts) || empty($configured_parts['scheme']) || empty($configured_parts['host'])) {
            return false;
        }
        $configured_scheme = strtolower((string) $configured_parts['scheme']);
        $configured_host = strtolower(rtrim((string) $configured_parts['host'], '.'));
        $configured_port = isset($configured_parts['port'])
            ? (int) $configured_parts['port']
            : ($configured_scheme === 'https' ? 443 : 80);

        if ($request_is_https) {
            return $configured_scheme === 'https'
                && $request_host === $configured_host
                && $request_port === $configured_port;
        }

        $is_allowed_loopback_host = $request_host === 'localhost' || $request_host === '127.0.0.1';
        return $request_scheme === 'http'
            && $is_allowed_loopback_host
            && self::is_loopback_peer($_SERVER['REMOTE_ADDR'] ?? null);
    }

    private static function is_loopback_peer($remote_addr) {
        if (!is_string($remote_addr) || filter_var($remote_addr, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $packed = @inet_pton($remote_addr);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 4) {
            return ord($packed[0]) === 127;
        }
        return $packed === @inet_pton('::1') || $packed === @inet_pton('::ffff:127.0.0.1');
    }

    /** Return registration options only for the authenticated account owner. */
    public static function registration_options(int $user_id, string $current_password) {
        $session_hash = self::current_session_hash($user_id);
        $user = $user_id > 0 ? get_userdata($user_id) : false;
        if ($session_hash === null) {
            self::diagnostic('registration_options_rejected', 'session_unavailable');
            return self::generic_error();
        }
        if (!$user instanceof WP_User) {
            self::diagnostic('registration_options_rejected', 'user_unavailable');
            return self::generic_error();
        }
        if (!self::can_register_for_user($user_id)) {
            self::diagnostic('registration_options_rejected', 'president_authority_unavailable');
            return self::generic_error();
        }
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            self::diagnostic('registration_options_rejected', 'password_rejected');
            return self::generic_error();
        }
        if (!class_exists('ZMP_Passkey_Store')) {
            self::diagnostic('registration_options_rejected', 'store_unavailable');
            return self::generic_error();
        }
        if (!ZMP_Passkey_Store::schema_is_compatible()) {
            self::diagnostic('registration_options_rejected', 'schema_incompatible');
            return self::generic_error();
        }
        if (!self::library_available()) {
            self::diagnostic('registration_options_rejected', 'webauthn_library_unavailable');
            return self::generic_error();
        }

        $origin = self::site_origin();
        if ($origin === null) {
            self::diagnostic('registration_options_rejected', 'origin_unavailable');
            return self::generic_error();
        }
        $user_handle = self::user_handle($user_id, true);
        if ($user_handle === null) {
            self::diagnostic('registration_options_rejected', 'user_handle_unavailable');
            return self::generic_error();
        }

        $challenge_id = ZMP_Passkey_Store::create_challenge(array(
            'user_id' => $user_id,
            'session_hash' => $session_hash,
            'ceremony' => 'registration',
        ));
        if (!is_string($challenge_id) || $challenge_id === '') {
            self::diagnostic('registration_options_rejected', 'challenge_creation_failed');
            return self::generic_error();
        }

        try {
            $creation_options = self::creation_options($challenge_id, $user_handle, $origin);
            $serialized = self::serializer()->serialize($creation_options, 'json');
            $options = json_decode($serialized, true);
            if (!is_array($options) || !isset($options['challenge'], $options['rp']['id'], $options['user']['id'])) {
                self::burn_registration_challenge($challenge_id, $user_id, $session_hash);
                self::diagnostic('registration_options_rejected', 'options_shape_invalid');
                return self::generic_error();
            }
            self::diagnostic('registration_options_succeeded');
            return array('challenge_id' => $challenge_id, 'options' => $options);
        } catch (Throwable $error) {
            self::burn_registration_challenge($challenge_id, $user_id, $session_hash);
            self::diagnostic('registration_options_rejected', 'options_generation_failed');
            return self::generic_error();
        }
    }

    /** Validate and persist a registration attestation for the exact owner/session. */
    public static function finish_registration(int $user_id, string $challenge_id, string $payload_json) {
        $session_hash = self::current_session_hash($user_id);
        if ($session_hash === null) {
            self::diagnostic('registration_finish_rejected', 'session_unavailable');
            return self::generic_error();
        }
        if (!self::can_register_for_user($user_id)) {
            self::diagnostic('registration_finish_rejected', 'president_authority_unavailable');
            return self::generic_error();
        }
        if (!class_exists('ZMP_Passkey_Store')) {
            self::diagnostic('registration_finish_rejected', 'store_unavailable');
            return self::generic_error();
        }
        if (!ZMP_Passkey_Store::schema_is_compatible()) {
            self::diagnostic('registration_finish_rejected', 'schema_incompatible');
            return self::generic_error();
        }
        if (!self::library_available()) {
            self::diagnostic('registration_finish_rejected', 'webauthn_library_unavailable');
            return self::generic_error();
        }

        $binding = ZMP_Passkey_Store::consume_challenge($challenge_id, array(
            'user_id' => $user_id,
            'session_hash' => $session_hash,
            'ceremony' => 'registration',
        ));
        if (!is_array($binding)) {
            self::diagnostic('registration_finish_rejected', 'challenge_binding_unavailable');
            return self::generic_error();
        }

        if ($payload_json === '' || strlen($payload_json) > self::MAX_REGISTRATION_PAYLOAD_BYTES) {
            self::diagnostic('registration_finish_rejected', 'payload_size_invalid');
            return self::generic_error();
        }
        $payload_object = json_decode($payload_json);
        if (json_last_error() !== JSON_ERROR_NONE || !is_object($payload_object)
            || !isset($payload_object->response) || !is_object($payload_object->response)) {
            self::diagnostic('registration_finish_rejected', 'payload_shape_invalid');
            return self::generic_error();
        }

        $user_handle = self::user_handle($user_id, false);
        $origin = self::site_origin();
        if ($user_handle === null || $origin === null) {
            self::diagnostic('registration_finish_rejected', 'origin_or_user_handle_unavailable');
            return self::generic_error();
        }

        try {
            $creation_options = self::creation_options($challenge_id, $user_handle, $origin);
            $credential = self::serializer()->deserialize(
                $payload_json,
                \Webauthn\PublicKeyCredential::class,
                'json'
            );
            if (!$credential instanceof \Webauthn\PublicKeyCredential
                || !$credential->response instanceof \Webauthn\AuthenticatorAttestationResponse) {
                self::diagnostic('registration_finish_rejected', 'attestation_type_invalid');
                return self::generic_error();
            }

            $factory = self::ceremony_factory($origin);
            $validator = \Webauthn\AuthenticatorAttestationResponseValidator::create(
                $factory->creationCeremony()
            );
            $record = $validator->check($credential->response, $creation_options, $origin['host']);
            if (!$record instanceof \Webauthn\CredentialRecord
                || !hash_equals($user_handle, $record->userHandle)
                || !ZMP_Passkey_Store::save_credential($user_id, $record, 'Passkey')) {
                self::diagnostic('registration_finish_rejected', 'attestation_validation_or_storage_failed');
                return self::generic_error();
            }
        } catch (Throwable $error) {
            // The public error is fixed; WebAuthn exceptions can include client payload details.
            self::diagnostic('registration_finish_rejected', 'attestation_processing_failed');
            return self::generic_error();
        }

        if (class_exists('ZMP_Debug') && is_callable(array('ZMP_Debug', 'log'))) {
            try {
                ZMP_Debug::log('info', 'Passkey credential registered.', array('outcome' => 'success'), 'general');
            } catch (Throwable $error) {
                // Logging must not turn a committed registration into a failed response.
            }
        }
        self::diagnostic('registration_finish_succeeded');
        return true;
    }

    /** Remove only the current account's credential after current-password reauthentication. */
    public static function remove_credential(int $user_id, string $credential_id, string $current_password) {
        $user = $user_id > 0 ? get_userdata($user_id) : false;
        if (self::current_session_hash($user_id) === null || !$user instanceof WP_User
            || $credential_id === '' || strlen($credential_id) > 2048
            || !wp_check_password($current_password, $user->user_pass, $user_id)
            || !class_exists('ZMP_Passkey_Store')
            || !ZMP_Passkey_Store::schema_is_compatible()
            || ZMP_Passkey_Store::find_credential_user_id($credential_id) !== $user_id
            || !ZMP_Passkey_Store::delete_credential($user_id, $credential_id)) {
            return self::generic_error();
        }

        if (class_exists('ZMP_Debug') && is_callable(array('ZMP_Debug', 'log'))) {
            try {
                ZMP_Debug::log('info', 'Passkey credential removed.', array('outcome' => 'success'), 'general');
            } catch (Throwable $error) {
                // Logging must not alter the completed removal result.
            }
        }
        return true;
    }

    private static function current_session_hash($user_id) {
        if (!is_user_logged_in() || (int) get_current_user_id() !== (int) $user_id || (int) $user_id < 1
            || !function_exists('wp_get_session_token')) {
            return null;
        }
        $session_token = wp_get_session_token();
        if (!is_string($session_token) || $session_token === '') {
            return null;
        }
        return hash('sha256', $session_token);
    }

    /** A stable random 256-bit handle, stored on the account without PII. */
    private static function user_handle($user_id, $create) {
        $encoded = self::canonical_stored_user_handle($user_id);
        if (is_string($encoded) && preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $encoded)) {
            try {
                $decoded = \ParagonIE\ConstantTime\Base64UrlSafe::decode($encoded, true);
                return strlen($decoded) === 32 ? $decoded : null;
            } catch (Throwable $error) {
                return null;
            }
        }
        if ($encoded !== '' || !$create) {
            return null;
        }

        try {
            $candidate = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        } catch (Throwable $error) {
            return null;
        }
        add_user_meta($user_id, self::USER_HANDLE_META, $candidate, true);
        // Re-read from the database after attempting the insert. WordPress usermeta has no
        // unique key on (user_id, meta_key), so a competing insert may win after add_user_meta's
        // initial lookup while both rows are still written. The first stored row is canonical.
        $stored = self::canonical_stored_user_handle($user_id);
        if (!is_string($stored) || $stored === '') {
            return null;
        }
        $candidate = $stored;
        try {
            $decoded = \ParagonIE\ConstantTime\Base64UrlSafe::decode($candidate, true);
            return strlen($decoded) === 32 ? $decoded : null;
        } catch (Throwable $error) {
            return null;
        }
    }

    private static function canonical_stored_user_handle($user_id) {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            'SELECT meta_value FROM ' . $wpdb->usermeta . ' WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id ASC LIMIT 1',
            (int) $user_id,
            self::USER_HANDLE_META
        ));
        if ($value === null) {
            return '';
        }
        if (!is_string($value) || !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value)) {
            return null;
        }
        try {
            return strlen(\ParagonIE\ConstantTime\Base64UrlSafe::decode($value, true)) === 32 ? $value : null;
        } catch (Throwable $error) {
            return null;
        }
    }

    private static function has_active_president_authority($user_id) {
        $user = get_userdata($user_id);
        return $user instanceof WP_User
            && in_array('zmp_president', (array) $user->roles, true)
            && class_exists('ZMP_President_Mandate')
            && ZMP_President_Mandate::user_has_active_mandate((int) $user_id) === true;
    }

    /** Resolve only the configured WordPress origin; never trust the request Host header. */
    private static function site_origin() {
        $configured_home = defined('WP_HOME') ? WP_HOME : get_option('home');
        if (!is_string($configured_home) || $configured_home === '') {
            return null;
        }
        $parts = wp_parse_url($configured_home);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $is_loopback = $host === 'localhost' || $host === '127.0.0.1';
        if ($scheme !== 'https' && !($scheme === 'http' && $is_loopback)) {
            return null;
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if ($port !== null && ($port < 1 || $port > 65535)) {
            return null;
        }

        $origin = $scheme . '://' . $host . ($port !== null ? ':' . $port : '');
        return array('origin' => $origin, 'host' => $host, 'is_loopback_http' => $scheme === 'http');
    }

    private static function request_options($challenge_id, array $origin) {
        $challenge = \ParagonIE\ConstantTime\Base64UrlSafe::decode($challenge_id, true);
        if (strlen($challenge) !== 32 || empty($origin['host'])) {
            throw new RuntimeException('Invalid passkey ceremony input.');
        }

        return \Webauthn\PublicKeyCredentialRequestOptions::create(
            $challenge,
            $origin['host'],
            array(),
            \Webauthn\PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            60000
        );
    }

    private static function creation_options($challenge_id, $user_handle, array $origin) {
        $challenge = \ParagonIE\ConstantTime\Base64UrlSafe::decode($challenge_id, true);
        if (strlen($challenge) !== 32 || strlen($user_handle) !== 32) {
            throw new RuntimeException('Invalid passkey ceremony input.');
        }

        $rp = \Webauthn\PublicKeyCredentialRpEntity::create('', $origin['host']);
        $handle_name = 'zmp-' . rtrim(strtr(base64_encode($user_handle), '+/', '-_'), '=');
        $user = \Webauthn\PublicKeyCredentialUserEntity::create(
            $handle_name,
            $user_handle,
            'ZMP account'
        );
        $parameters = array(
            \Webauthn\PublicKeyCredentialParameters::createPk(-7),
            \Webauthn\PublicKeyCredentialParameters::createPk(-257),
        );
        $selection = \Webauthn\AuthenticatorSelectionCriteria::create(
            null,
            \Webauthn\AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            \Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
        );

        return \Webauthn\PublicKeyCredentialCreationOptions::create(
            $rp,
            $user,
            $challenge,
            $parameters,
            $selection,
            \Webauthn\PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            array(),
            60000
        );
    }

    private static function ceremony_factory(array $origin) {
        $factory = new \Webauthn\CeremonyStep\CeremonyStepManagerFactory();
        $factory->setAllowedOrigins(array($origin['origin']), false);
        if ($origin['is_loopback_http']) {
            // 5.3.x still requires this allow-list entry for HTTP origins in addition to exact origin matching.
            $factory->setSecuredRelyingPartyId(array($origin['host']));
        }
        return $factory;
    }

    private static function serializer() {
        if (self::$serializer === null) {
            $support = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
            self::$serializer = (new \Webauthn\Denormalizer\WebauthnSerializerFactory($support))->create();
        }
        return self::$serializer;
    }

    private static function library_available() {
        return class_exists('\Webauthn\CredentialRecord')
            && class_exists('\Webauthn\AuthenticatorAttestationResponseValidator')
            && class_exists('\Webauthn\Denormalizer\WebauthnSerializerFactory')
            && class_exists('\ParagonIE\ConstantTime\Base64UrlSafe');
    }

    private static function burn_registration_challenge($challenge_id, $user_id, $session_hash) {
        if (class_exists('ZMP_Passkey_Store')) {
            ZMP_Passkey_Store::consume_challenge($challenge_id, array(
                'user_id' => (int) $user_id,
                'session_hash' => $session_hash,
                'ceremony' => 'registration',
            ));
        }
    }

    private static function burn_login_challenge($challenge_id, $origin_digest) {
        if (class_exists('ZMP_Passkey_Store')) {
            ZMP_Passkey_Store::consume_challenge($challenge_id, array(
                'user_id' => 0,
                'session_hash' => '',
                'ceremony' => 'login',
                'context_hash' => $origin_digest,
            ));
        }
    }

    private static function burn_step_up_challenge($challenge_id, $user_id, $session_hash, $action, $resource_id, $decision, $context_hash) {
        if (class_exists('ZMP_Passkey_Store')) {
            ZMP_Passkey_Store::consume_challenge($challenge_id, array(
                'user_id'=>(int) $user_id,
                'session_hash'=>$session_hash,
                'ceremony'=>'step_up',
                'action'=>$action,
                'resource_id'=>(int) $resource_id,
                'decision'=>$decision,
                'context_hash'=>$context_hash,
            ));
        }
    }

    private static function valid_step_up_context($action, $decision, $canonical_subject_id, $context_binding = '') {
        if ($action === 'president_subject_decision') {
            if (!is_string($decision) || !in_array($decision, array('approve_new','approve_alias','reject'), true) || $context_binding !== '') { return false; }
            return $decision === 'approve_alias' ? is_int($canonical_subject_id) && $canonical_subject_id > 0 : $canonical_subject_id === 0;
        }
        return $action === 'jury_result_finalization'
            && is_string($decision) && in_array($decision, array('school','municipal','regional','state'), true)
            && is_int($canonical_subject_id) && $canonical_subject_id > 0
            && is_string($context_binding) && preg_match('/\\A[a-f0-9]{64}\\z/D', $context_binding) === 1;
    }

    private static function step_up_nonce_action($action) {
        return $action === 'jury_result_finalization' ? 'zmp_category_jury' : 'zmp_president_subject_decision';
    }

    private static function can_perform_step_up($user_id, $action) {
        if ($action === 'president_subject_decision') { return self::can_perform_subject_step_up($user_id); }
        return $action === 'jury_result_finalization' && is_user_logged_in()
            && (int) get_current_user_id() === (int) $user_id
            && (current_user_can('manage_options') || current_user_can('zmp_commission_cap') || current_user_can('view_zmp_commission_dashboard'))
            && (bool) self::current_session_hash($user_id);
    }

    private static function can_perform_subject_step_up($user_id) {
        if (!is_user_logged_in() || (int) get_current_user_id() !== (int) $user_id
            || !current_user_can('view_zmp_president_dashboard') || current_user_can('manage_options')
            || !class_exists('ZMP_President_Subject_Approvals')
            || !ZMP_President_Subject_Approvals::can_review()
            || !self::current_session_hash($user_id)) {
            return false;
        }
        return true;
    }

    private static function step_up_context_digest(array $origin, $user_id, $session_hash, $action, $resource_id, $decision, $canonical_subject_id, $route_nonce, $context_binding = '') {
        if (empty($origin['origin']) || empty($origin['host']) || !is_string($session_hash) || $session_hash === ''
            || !is_string($route_nonce) || $route_nonce === '' || !self::valid_step_up_context($action, $decision, $canonical_subject_id, $context_binding)) {
            return null;
        }
        $parts = wp_parse_url($origin['origin']);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) { return null; }
        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $default_port = $scheme === 'https' ? 443 : 80;
        $port = isset($parts['port']) ? (int) $parts['port'] : $default_port;
        if (($scheme !== 'https' && $scheme !== 'http') || $host !== $origin['host'] || $port < 1 || $port > 65535) {
            return null;
        }
        $canonical_origin = $scheme . '://' . $host . ($port !== $default_port ? ':' . $port : '');
        $context = "zmp-passkey-step-up-v2\n"
            . $canonical_origin . "\n" . $host . "\n" . (int) $user_id . "\n" . $session_hash . "\n"
            . $action . "\n" . (int) $resource_id . "\n" . $decision . "\n" . (int) $canonical_subject_id . "\n"
            . hash('sha256', $route_nonce) . "\n" . $context_binding;
        return hash('sha256', $context);
    }

    private static function positive_id($value, $allow_zero = false) {
        if ($allow_zero && ($value === 0 || $value === '0')) { return 0; }
        if (!is_int($value) && (!is_string($value) || !preg_match('/\A[1-9][0-9]*\z/D', $value))) { return 0; }
        $number = (int) $value;
        return $number > 0 && (string) $number === (string) $value ? $number : 0;
    }

    /** Emit fixed registration-stage labels only when explicitly enabled for local diagnosis. */
    private static function diagnostic($event, $category = '') {
        if (!defined('ZMP_PASSKEY_DIAGNOSTICS') || ZMP_PASSKEY_DIAGNOSTICS !== true) {
            return;
        }
        $allowed_events = array(
            'registration_options_rejected', 'registration_options_succeeded',
            'registration_finish_rejected', 'registration_finish_succeeded',
        );
        $allowed_categories = array(
            '', 'session_unavailable', 'user_unavailable', 'president_authority_unavailable',
            'password_rejected', 'store_unavailable', 'schema_incompatible',
            'webauthn_library_unavailable', 'origin_unavailable', 'user_handle_unavailable',
            'challenge_creation_failed', 'options_shape_invalid', 'options_generation_failed',
            'challenge_binding_unavailable', 'payload_size_invalid', 'payload_shape_invalid',
            'origin_or_user_handle_unavailable', 'attestation_type_invalid',
            'attestation_validation_or_storage_failed', 'attestation_processing_failed',
        );
        if (!in_array($event, $allowed_events, true) || !in_array($category, $allowed_categories, true)) {
            return;
        }
        error_log('[ZMP_PASSKEY_DIAG] ' . $event . ($category !== '' ? ' category=' . $category : ''));
    }

    private static function generic_error() {
        return new WP_Error(
            'passkey_operation_unavailable',
            __('Passkey operation could not be completed. Check the account and try again.', 'zmp-competitions')
        );
    }

    /** Preserve the public generic message while exposing only a fixed internal diagnostic category. */
    private static function step_up_error($code) {
        $allowed_codes = array(
            'step_up_context_invalid',
            'step_up_nonce_invalid',
            'step_up_authority_session_unavailable',
            'step_up_secure_origin_invalid',
            'step_up_store_schema_unavailable',
            'step_up_library_unavailable',
            'step_up_credentials_unavailable',
            'step_up_challenge_insert_failed',
            'step_up_options_serialization_invalid',
            'step_up_options_generation_failed',
        );
        if (!in_array($code, $allowed_codes, true)) { $code = 'step_up_context_invalid'; }
        return new WP_Error(
            $code,
            __('Passkey operation could not be completed. Check the account and try again.', 'zmp-competitions')
        );
    }
}
