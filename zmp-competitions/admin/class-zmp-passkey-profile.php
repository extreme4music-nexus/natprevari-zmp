<?php
if (!defined('ABSPATH')) { exit; }

/** Self-profile enrollment controls; requests can act only on the current account. */
final class ZMP_Passkey_Profile {
    const OPTIONS_ACTION = 'zmp_passkey_registration_options';
    const FINISH_ACTION = 'zmp_passkey_registration_finish';
    const REMOVE_ACTION = 'zmp_passkey_remove';

    public static function init() {
        add_action('show_user_profile', array(__CLASS__, 'render_own_profile'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_own_profile_script'));
        add_action('admin_post_' . self::OPTIONS_ACTION, array(__CLASS__, 'handle_options'));
        add_action('admin_post_' . self::FINISH_ACTION, array(__CLASS__, 'handle_finish'));
        add_action('admin_post_' . self::REMOVE_ACTION, array(__CLASS__, 'handle_remove'));
    }

    /** WordPress fires this hook on the current user's profile page only. */
    public static function render_own_profile($profile_user) {
        if (!$profile_user instanceof WP_User || !is_user_logged_in()
            || (int) $profile_user->ID !== (int) get_current_user_id()
            || !current_user_can('read')) {
            return;
        }

        $user_id = (int) $profile_user->ID;
        $can_enroll = ZMP_Passkeys::can_register_for_user($user_id);
        $credentials = class_exists('ZMP_Passkey_Store')
            ? ZMP_Passkey_Store::list_credentials_for_user($user_id)
            : array();
        if (!$can_enroll && !$credentials) {
            return;
        }

        echo '<h2>' . esc_html__('Passkey sign-in', 'zmp-competitions') . '</h2>';
        echo '<p>' . esc_html__('Manage passkeys linked to this account. Your current password is required for enrollment or removal.', 'zmp-competitions') . '</p>';
        echo '<fieldset class="zmp-passkey-profile"><legend>' . esc_html__('Passkey enrollment', 'zmp-competitions') . '</legend>';
        echo '<label for="zmp-passkey-current-password">' . esc_html__('Current password', 'zmp-competitions') . '</label> ';
        echo '<input id="zmp-passkey-current-password" type="password" name="current_password" autocomplete="current-password"> ';
        if ($can_enroll) {
            wp_nonce_field(self::OPTIONS_ACTION, '_zmp_passkey_nonce', false);
            wp_nonce_field(self::FINISH_ACTION, '_zmp_passkey_finish_nonce', false);
            echo '<button type="button" class="button button-primary zmp-passkey-profile__begin">' . esc_html__('Begin passkey enrollment', 'zmp-competitions') . '</button>';
        }
        if ($credentials) {
            wp_nonce_field(self::REMOVE_ACTION, '_zmp_passkey_remove_nonce', false);
            echo '<ul class="zmp-passkey-profile__credentials">';
            foreach ($credentials as $credential) {
                if (!is_array($credential) || !isset($credential['credential_id'])) {
                    continue;
                }
                $credential_id = self::encode_credential_id((string) $credential['credential_id']);
                if ($credential_id === '') {
                    continue;
                }
                $label = isset($credential['label']) && $credential['label'] !== ''
                    ? (string) $credential['label']
                    : __('Passkey', 'zmp-competitions');
                echo '<li class="zmp-passkey-profile__credential">';
                echo '<span>' . esc_html($label) . '</span> ';
                echo '<button type="button" class="button zmp-passkey-profile__remove" data-credential-id="' . esc_attr($credential_id) . '">';
                echo esc_html__('Remove passkey', 'zmp-competitions') . '</button></li>';
            }
            echo '</ul>';
        }
        echo '<p class="zmp-passkey-profile__status" role="status" aria-live="polite"></p>';
        echo '</fieldset>';
        if ($can_enroll) {
            echo '<p class="description">' . esc_html__('Passkey enrollment is available to the active President with an active mandate. A passkey does not change account roles or permissions.', 'zmp-competitions') . '</p>';
        }
    }

    /** Load the browser ceremony only on the current user's own profile page. */
    public static function enqueue_own_profile_script($hook) {
        if ($hook !== 'profile.php' || !is_user_logged_in() || !current_user_can('read')
            || (int) get_current_user_id() < 1 || !defined('ZMP_PLUGIN_DIR') || !defined('ZMP_PLUGIN_URL')) {
            return;
        }
        $relative_path = 'assets/js/zmp-passkeys.js';
        $script_path = ZMP_PLUGIN_DIR . $relative_path;
        if (!is_readable($script_path)) {
            return;
        }

        wp_enqueue_script('zmp-passkeys', ZMP_PLUGIN_URL . $relative_path, array(), ZMP_VERSION, true);
        wp_localize_script('zmp-passkeys', 'zmpPasskeyProfile', array(
            'endpoint' => admin_url('admin-post.php'),
            'optionsAction' => self::OPTIONS_ACTION,
            'finishAction' => self::FINISH_ACTION,
            'optionsNonce' => wp_create_nonce(self::OPTIONS_ACTION),
            'finishNonce' => wp_create_nonce(self::FINISH_ACTION),
            'removeAction' => self::REMOVE_ACTION,
            'removeNonce' => wp_create_nonce(self::REMOVE_ACTION),
            'debugDiagnostics' => defined('ZMP_PASSKEY_DIAGNOSTICS') && ZMP_PASSKEY_DIAGNOSTICS === true,
        ));
    }

    public static function handle_options() {
        $reason = '';
        if (!self::valid_request(self::OPTIONS_ACTION, $reason)) {
            self::diagnostic('options_request_rejected', $reason);
            self::send_unavailable(403);
        }
        self::diagnostic('options_request_accepted');
        $password = isset($_POST['current_password']) && is_string($_POST['current_password'])
            ? wp_unslash($_POST['current_password'])
            : '';
        $result = ZMP_Passkeys::registration_options(get_current_user_id(), $password);
        if (is_wp_error($result)) {
            self::diagnostic('options_handler_rejected');
            self::send_unavailable(400);
        }
        self::diagnostic('options_handler_succeeded');
        wp_send_json_success($result, 200);
    }

    public static function handle_finish() {
        $reason = '';
        if (!self::valid_request(self::FINISH_ACTION, $reason)) {
            self::diagnostic('finish_request_rejected', $reason);
            self::send_unavailable(403);
        }
        self::diagnostic('finish_request_accepted');
        $challenge_id = isset($_POST['challenge_id']) && is_string($_POST['challenge_id'])
            ? sanitize_text_field(wp_unslash($_POST['challenge_id']))
            : '';
        $payload_json = isset($_POST['payload_json']) && is_string($_POST['payload_json'])
            ? wp_unslash($_POST['payload_json'])
            : '';
        $result = ZMP_Passkeys::finish_registration(get_current_user_id(), $challenge_id, $payload_json);
        if (is_wp_error($result)) {
            self::diagnostic('finish_handler_rejected');
            self::send_unavailable(400);
        }
        self::diagnostic('finish_handler_succeeded');
        wp_send_json_success(array('registered' => true), 200);
    }

    public static function handle_remove() {
        if (!ZMP_Passkeys::is_secure_request_context() || !self::valid_request(self::REMOVE_ACTION)) {
            self::send_unavailable(403);
        }
        $credential_id_token = isset($_POST['credential_id']) && is_string($_POST['credential_id'])
            ? sanitize_text_field(wp_unslash($_POST['credential_id']))
            : '';
        $credential_id = self::decode_credential_id($credential_id_token);
        if (!is_string($credential_id) || $credential_id === '') {
            self::send_unavailable(400);
        }
        $password = isset($_POST['current_password']) && is_string($_POST['current_password'])
            ? wp_unslash($_POST['current_password'])
            : '';
        $result = ZMP_Passkeys::remove_credential(get_current_user_id(), $credential_id, $password);
        if (is_wp_error($result)) {
            self::send_unavailable(400);
        }
        wp_send_json_success(array('removed' => true), 200);
    }

    /** Encode binary credential identifiers before placing them in an HTML attribute. */
    private static function encode_credential_id($credential_id) {
        if (!is_string($credential_id) || $credential_id === '' || strlen($credential_id) > 2048) {
            return '';
        }
        return rtrim(strtr(base64_encode($credential_id), '+/', '-_'), '=');
    }

    /** Decode only canonical base64url credential identifiers from the owner form. */
    private static function decode_credential_id($token) {
        if (!is_string($token) || $token === '' || strlen($token) > 2731
            || !preg_match('/\\A[A-Za-z0-9_-]+\\z/D', $token)) {
            return null;
        }
        try {
            $credential_id = \ParagonIE\ConstantTime\Base64UrlSafe::decode($token, true);
        } catch (\Throwable $error) {
            return null;
        }
        if (!is_string($credential_id) || $credential_id === '' || strlen($credential_id) > 2048
            || !hash_equals($token, self::encode_credential_id($credential_id))) {
            return null;
        }
        return $credential_id;
    }

    private static function valid_request($nonce_action, &$reason = null) {
        $reason = '';
        if (!is_user_logged_in()) {
            $reason = 'not_logged_in';
            return false;
        }
        if (!current_user_can('read') || (int) get_current_user_id() < 1) {
            $reason = 'capability_or_user_unavailable';
            return false;
        }
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            $reason = 'method_invalid';
            return false;
        }
        $submitted_action = isset($_POST['action']) && is_string($_POST['action'])
            ? sanitize_key(wp_unslash($_POST['action']))
            : '';
        if ($submitted_action !== $nonce_action) {
            $reason = 'action_invalid';
            return false;
        }
        $nonce_fields = array(
            self::OPTIONS_ACTION => '_zmp_passkey_nonce',
            self::FINISH_ACTION => '_zmp_passkey_finish_nonce',
            self::REMOVE_ACTION => '_zmp_passkey_remove_nonce',
        );
        $nonce_field = $nonce_fields[$nonce_action] ?? '';
        if ($nonce_field === '') {
            $reason = 'nonce_configuration_missing';
            return false;
        }
        $nonce = isset($_POST[$nonce_field]) && is_string($_POST[$nonce_field])
            ? sanitize_text_field(wp_unslash($_POST[$nonce_field]))
            : '';
        if ($nonce === '') {
            $reason = 'nonce_missing';
            return false;
        }
        if (wp_verify_nonce($nonce, $nonce_action) === false) {
            $reason = 'nonce_invalid';
            return false;
        }
        return true;
    }

    /** Log fixed enrollment stage/result labels only when local diagnostics are explicitly enabled. */
    private static function diagnostic($event, $category = '') {
        if (!defined('ZMP_PASSKEY_DIAGNOSTICS') || ZMP_PASSKEY_DIAGNOSTICS !== true) {
            return;
        }
        $allowed_events = array(
            'options_request_rejected', 'options_request_accepted', 'options_handler_rejected',
            'options_handler_succeeded', 'finish_request_rejected', 'finish_request_accepted',
            'finish_handler_rejected', 'finish_handler_succeeded',
        );
        $allowed_categories = array(
            '', 'not_logged_in', 'capability_or_user_unavailable', 'method_invalid', 'action_invalid',
            'nonce_configuration_missing', 'nonce_missing', 'nonce_invalid',
        );
        if (!in_array($event, $allowed_events, true) || !in_array($category, $allowed_categories, true)) {
            return;
        }
        $header_value = '';
        if ($event === 'options_request_rejected') {
            $category_headers = array(
                'not_logged_in' => 'auth_required',
                'capability_or_user_unavailable' => 'capability_denied',
                'method_invalid' => 'request_invalid',
                'action_invalid' => 'action_mismatch',
                'nonce_configuration_missing' => 'request_invalid',
                'nonce_missing' => 'nonce_missing',
                'nonce_invalid' => 'nonce_invalid',
            );
            $header_value = $category_headers[$category] ?? '';
        } elseif ($event === 'options_handler_rejected') {
            $header_value = 'options_handler_rejected';
        } elseif ($event === 'options_handler_succeeded') {
            $header_value = 'options_handler_succeeded';
        }
        if ($header_value !== '' && !headers_sent()) {
            header('X-ZMP-Passkey-Diagnostic: ' . $header_value, true);
        }
        error_log('[ZMP_PASSKEY_DIAG] ' . $event . ($category !== '' ? ' category=' . $category : ''));
    }

    private static function send_unavailable($status) {
        wp_send_json_error(
            array('code' => 'passkey_operation_unavailable'),
            (int) $status
        );
    }
}
