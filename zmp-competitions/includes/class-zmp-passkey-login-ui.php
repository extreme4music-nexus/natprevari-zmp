<?php
if (!defined('ABSPATH')) { exit; }

/** Anonymous, discoverable passkey login UI. Password login remains WordPress-owned. */
final class ZMP_Passkey_Login_UI {
    const SCRIPT_HANDLE = 'zmp-passkey-login';
    const STYLE_HANDLE = 'zmp-passkey-login';

    public static function init() {
        add_action('login_form', array(__CLASS__, 'render_login_control'));
        add_action('login_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function render_login_control() {
        if (is_user_logged_in()) {
            return;
        }

        echo '<div class="zmp-passkey-login">';
        echo '<button type="button" class="button button-secondary zmp-passkey-login__button" id="zmp-passkey-login">';
        echo esc_html__('Sign in with a passkey', 'zmp-competitions');
        echo '</button>';
        echo '<p class="zmp-passkey-login__status" id="zmp-passkey-login-status" role="status" aria-live="polite" aria-atomic="true"></p>';
        echo '</div>';
    }

    public static function enqueue_assets() {
        global $pagenow;

        if (!isset($pagenow) || $pagenow !== 'wp-login.php' || is_user_logged_in()) {
            return;
        }

        $script_path = ZMP_PLUGIN_DIR . 'assets/js/zmp-passkey-login.js';
        $style_path = ZMP_PLUGIN_DIR . 'assets/css/zmp-passkey-login.css';
        $script_version = is_file($script_path) ? (string) filemtime($script_path) : ZMP_VERSION;
        $style_version = is_file($style_path) ? (string) filemtime($style_path) : ZMP_VERSION;

        wp_enqueue_style(
            self::STYLE_HANDLE,
            ZMP_PLUGIN_URL . 'assets/css/zmp-passkey-login.css',
            array(),
            $style_version
        );
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            ZMP_PLUGIN_URL . 'assets/js/zmp-passkey-login.js',
            array(),
            $script_version,
            true
        );

        $is_albanian = strpos(strtolower((string) get_locale()), 'sq_') === 0;
        wp_localize_script(self::SCRIPT_HANDLE, 'zmpPasskeyLogin', array(
            'endpoint' => admin_url('admin-ajax.php'),
            'optionsAction' => 'zmp_passkey_login_options',
            'finishAction' => 'zmp_passkey_login_finish',
            'messages' => $is_albanian ? array(
                'unsupported' => 'Ky shfletues ose lidhje nuk mbështet hyrjen me passkey. Përdorni fjalëkalimin tuaj.',
                'insecure' => 'Hyrja me passkey kërkon një lidhje të sigurt. Përdorni fjalëkalimin tuaj.',
                'working' => 'Po përgatitet hyrja me passkey…',
                'continue' => 'Ndiqni kërkesën e sigurisë në shfletues.',
                'canceled' => 'Kërkesa për passkey u anulua. Provoni përsëri ose përdorni fjalëkalimin tuaj.',
                'failed' => 'Hyrja me passkey nuk mund të përfundohej. Provoni përsëri ose përdorni fjalëkalimin tuaj.'
            ) : array(
                'unsupported' => 'Овој прелистувач или врска не поддржува најава со passkey. Користете ја лозинката.',
                'insecure' => 'Најавата со passkey бара безбедна врска. Користете ја лозинката.',
                'working' => 'Се подготвува најавата со passkey…',
                'continue' => 'Следете го безбедносното барање во прелистувачот.',
                'canceled' => 'Барањето за passkey е откажано. Обидете се повторно или користете ја лозинката.',
                'failed' => 'Најавата со passkey не може да се заврши. Обидете се повторно или користете ја лозинката.'
            )
        ));
    }
}
