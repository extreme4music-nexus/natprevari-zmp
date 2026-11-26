<?php
if (!defined('ABSPATH')) { exit; }

/** Active-President-only review queue for shared teacher subject proposals. */
class ZMP_President_Subject_Approvals {
    const PAGE = 'zmp-president-subject-approvals';
    const ACTION = 'zmp_president_subject_decision';
    const NONCE = 'zmp_president_subject_decision';

    public static function init() {
        if (!is_admin()) { return; }
        add_action('admin_menu', array(__CLASS__, 'register_menu'), 20);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_step_up_assets'));
        add_action('admin_post_' . self::ACTION, array(__CLASS__, 'handle_post'));
    }

    /** Load the step-up ceremony only on the active President decision queue. */
    public static function enqueue_step_up_assets($hook_suffix = '') {
        if (!is_admin() || !self::can_review()
            || !isset($_GET['page']) || !is_scalar($_GET['page'])
            || sanitize_key(wp_unslash((string) $_GET['page'])) !== self::PAGE) {
            return;
        }

        $script_path = ZMP_PLUGIN_DIR . 'assets/js/zmp-president-subject-stepup.js';
        $style_path = ZMP_PLUGIN_DIR . 'assets/css/zmp-president-subject-stepup.css';
        $script_version = is_file($script_path) ? (string) filemtime($script_path) : ZMP_VERSION;
        $style_version = is_file($style_path) ? (string) filemtime($style_path) : ZMP_VERSION;
        wp_enqueue_style('zmp-president-subject-stepup', ZMP_PLUGIN_URL . 'assets/css/zmp-president-subject-stepup.css', array(), $style_version);
        wp_enqueue_script('zmp-president-subject-stepup', ZMP_PLUGIN_URL . 'assets/js/zmp-president-subject-stepup.js', array(), $script_version, true);

        $is_albanian = strpos(strtolower((string) get_locale()), 'sq_') === 0;
        wp_localize_script('zmp-president-subject-stepup', 'zmpPresidentSubjectStepUp', array(
            'endpoint'=>admin_url('admin-ajax.php'),
            'optionsAction'=>'zmp_president_subject_step_up_options',
            'messages'=>$is_albanian ? array(
                'working'=>'Po përgatitet verifikimi me passkey…',
                'continue'=>'Ndiqni kërkesën e sigurisë në shfletues.',
                'canceled'=>'Kërkesa për passkey u anulua. Vendimi nuk u dërgua.',
                'unsupported'=>'Ky shfletues ose lidhje nuk mbështet verifikimin me passkey. Vendimi nuk u dërgua.',
                'failed'=>'Verifikimi me passkey nuk mund të përfundohej. Vendimi nuk u dërgua.'
            ) : array(
                'working'=>'Се подготвува проверката со passkey…',
                'continue'=>'Следете го безбедносното барање во прелистувачот.',
                'canceled'=>'Барањето за passkey е откажано. Одлуката не е испратена.',
                'unsupported'=>'Овој прелистувач или врска не поддржува проверка со passkey. Одлуката не е испратена.',
                'failed'=>'Проверката со passkey не може да се заврши. Одлуката не е испратена.'
            )
        ));
    }

    public static function register_menu() {
        if (!self::can_review()) { return; }
        add_submenu_page(
            ZMP_President_Dashboard::PAGE,
            __('Предлози за предмети', 'zmp-competitions'),
            __('Предлози за предмети', 'zmp-competitions'),
            'view_zmp_president_dashboard',
            self::PAGE,
            array(__CLASS__, 'render')
        );
    }

    public static function can_review() {
        if (!is_user_logged_in() || current_user_can('manage_options')
            || !current_user_can('view_zmp_president_dashboard')) { return false; }
        $user = get_userdata(get_current_user_id());
        return $user && ZMP_Mentor_School_Profile::has_role_stack(get_current_user_id(), 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && class_exists('ZMP_President_Mandate')
            && is_callable(array('ZMP_President_Mandate','user_has_active_mandate'))
            && ZMP_President_Mandate::user_has_active_mandate(get_current_user_id());
    }

    /** Render a pending badge only inside the President's existing dashboard. */
    public static function render_dashboard_notice() {
        if (!self::can_review() || !class_exists('ZMP_Mentor_Subject_Proposals')) { return; }
        $count = ZMP_Mentor_Subject_Proposals::pending_count();
        if (is_wp_error($count)) {
            self::render_schema_notice();
            return;
        }
        if ($count < 1) { return; }
        $url = add_query_arg(array('page'=>self::PAGE), admin_url('admin.php'));
        echo '<div class="notice notice-info" role="status" aria-live="polite"><p>';
        echo esc_html(sprintf(__('Чекаат %d предлози за предмети.', 'zmp-competitions'), $count)) . ' ';
        echo '<a href="' . esc_url($url) . '">' . esc_html__('Отворете ја редицата за одлуки.', 'zmp-competitions') . '</a>';
        echo '</p></div>';
    }

    public static function render() {
        if (!self::can_review()) {
            wp_die(esc_html__('Немате пристап до предлозите за предмети.', 'zmp-competitions'), '', array('response'=>403));
        }
        if (!class_exists('ZMP_Mentor_Subject_Proposals') || !class_exists('ZMP_Mentor_Subjects')) {
            wp_die(esc_html__('Сервисот за предмети моментално не е достапен.', 'zmp-competitions'), '', array('response'=>503));
        }
        if (!ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
            self::schema_not_ready('render');
            self::render_schema_notice();
            return;
        }

        $pending = self::pending_proposals();
        if (is_wp_error($pending)) {
            self::render_schema_notice();
            return;
        }
        $subjects = ZMP_Mentor_Subjects::approved_subjects();
        if (is_wp_error($subjects)) {
            self::render_schema_notice();
            return;
        }
        $matches_by_proposal = array();
        foreach ($pending as $proposal) {
            $proposal_id = absint($proposal->id ?? 0);
            if (!$proposal_id) { continue; }
            $matches = ZMP_Mentor_Subject_Proposals::find_normalized_matches((string) ($proposal->proposed_name ?? ''));
            if (is_wp_error($matches)) {
                self::render_schema_notice();
                return;
            }
            $matches_by_proposal[$proposal_id] = $matches;
        }
        $status = isset($_GET['zmp_subject_decision_status']) && is_scalar($_GET['zmp_subject_decision_status'])
            ? sanitize_key(wp_unslash((string) $_GET['zmp_subject_decision_status']))
            : '';
        $messages = array(
            'approved'=>array('success', __('Предлогот е одобрен.', 'zmp-competitions')),
            'rejected'=>array('success', __('Предлогот е одбиен.', 'zmp-competitions')),
            'failed'=>array('error', __('Одлуката не успеа. Освежете ја редицата и проверете го статусот.', 'zmp-competitions')),
        );

        echo '<div class="wrap zmp-subject-approvals"><h1>' . esc_html__('Предлози за предмети', 'zmp-competitions') . '</h1>';
        if (isset($messages[$status])) {
            echo '<div class="notice notice-' . esc_attr($messages[$status][0]) . '" role="status" aria-live="polite"><p>' . esc_html($messages[$status][1]) . '</p></div>';
        }
        echo '<p>' . esc_html(sprintf(__('Предлози што чекаат одлука: %d', 'zmp-competitions'), count($pending))) . '</p>';
        $register_url = add_query_arg(array('zmp_portal_tab'=>'teacher-register'), home_url('/'));
        echo '<p class="zmp-subject-approvals__register-link"><a href="' . esc_url($register_url) . '">' . esc_html__('Отвори го регистарот на музички педагози', 'zmp-competitions') . '</a></p>';
        if (!$pending) {
            echo '<p>' . esc_html__('Нема предлози што чекаат одлука.', 'zmp-competitions') . '</p></div>';
            return;
        }

        echo '<div class="zmp-subject-approvals__queue">';
        foreach ($pending as $proposal) {
            $proposal_id = absint($proposal->id ?? 0);
            if (!$proposal_id) { continue; }
            echo '<article class="zmp-subject-approvals__proposal"><h2>' . esc_html((string) ($proposal->proposed_name ?? '')) . '</h2>';
            echo '<p>' . esc_html(sprintf(__('Поднесено: %s', 'zmp-competitions'), (string) ($proposal->created_at ?? ''))) . '</p>';
            $matches = $matches_by_proposal[$proposal_id] ?? array('canonical'=>array(),'aliases'=>array());
            echo '<div class="zmp-subject-approvals__matches"><h3>' . esc_html__('Постојни нормализирани совпаѓања', 'zmp-competitions') . '</h3>';
            if (empty($matches['canonical']) && empty($matches['aliases'])) {
                echo '<p>' . esc_html__('Не се пронајдени предмети или синоними со исто нормализирано име.', 'zmp-competitions') . '</p>';
            } else {
                echo '<ul>';
                foreach ((array) $matches['canonical'] as $match) {
                    echo '<li>' . esc_html(sprintf(__('Канонски предмет: %s (ID %d, статус %s)', 'zmp-competitions'), (string) ($match->subject_name ?? ''), absint($match->id ?? 0), (string) ($match->status ?? ''))) . '</li>';
                }
                foreach ((array) $matches['aliases'] as $match) {
                    echo '<li>' . esc_html(sprintf(__('Синоним: %s → %s (ID %d)', 'zmp-competitions'), (string) ($match->alias_name ?? ''), (string) ($match->canonical_name ?? ''), absint($match->canonical_subject_id ?? 0))) . '</li>';
                }
                echo '</ul>';
                echo '<p>' . esc_html__('Одобрувањето како нов предмет ќе биде одбиено додека постои совпаѓање; изберете изрично одобрен канонски предмет за синоним или одбијте го предлогот.', 'zmp-competitions') . '</p>';
            }
            echo '</div>';
            echo '<form class="zmp-president-stepup-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="' . esc_attr(self::ACTION) . '">';
            echo '<input type="hidden" name="proposal_id" value="' . esc_attr((string) $proposal_id) . '">';
            wp_nonce_field(self::NONCE, '_wpnonce');
            echo '<p class="zmp-president-stepup-required">' . esc_html__('За секоја одлука е потребна проверка со passkey.', 'zmp-competitions') . '</p>';
            echo '<div class="zmp-subject-approvals__actions">';
            echo '<label for="zmp-subject-canonical-' . esc_attr((string) $proposal_id) . '">' . esc_html__('Канонски предмет за одобрување како синоним', 'zmp-competitions') . ' ';
            echo '<select id="zmp-subject-canonical-' . esc_attr((string) $proposal_id) . '" name="canonical_subject_id"><option value="0">' . esc_html__('Изберете одобрен предмет', 'zmp-competitions') . '</option>';
            foreach ($subjects as $subject) {
                echo '<option value="' . esc_attr((string) absint($subject->id ?? 0)) . '">' . esc_html((string) ($subject->subject_name ?? '')) . '</option>';
            }
            echo '</select></label>';
            echo '<button class="button button-primary" name="decision" value="approve_new">' . esc_html__('Одобри како нов предмет', 'zmp-competitions') . '</button> ';
            echo '<button class="button" name="decision" value="approve_alias">' . esc_html__('Одобри како синоним', 'zmp-competitions') . '</button> ';
            echo '<button class="button" name="decision" value="reject">' . esc_html__('Одбиј', 'zmp-competitions') . '</button>';
            echo '</div>';
            echo '<input type="hidden" name="step_up_challenge_id" value="">';
            echo '<input type="hidden" name="step_up_credential" value="">';
            echo '<p class="zmp-president-stepup-status" role="status" aria-live="polite" aria-atomic="true"></p>';
            echo '</form></article>';
        }
        echo '</div></div>';
    }

    public static function handle_post() {
        if (!self::can_review()) {
            self::debug_event('warning', 'President subject decision denied.', array('error_code'=>'forbidden'));
            wp_die(esc_html__('Немате дозвола да одлучувате за предлози.', 'zmp-competitions'), '', array('response'=>403));
        }
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST'
            || sanitize_key(wp_unslash($_POST['action'] ?? '')) !== self::ACTION
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), self::NONCE)) {
            self::debug_event('warning', 'President subject decision request failed security validation.', array('error_code'=>'invalid_nonce_or_method'));
            wp_die(esc_html__('Безбедносната проверка не помина.', 'zmp-competitions'), '', array('response'=>403));
        }
        $proposal_id = self::positive_id($_POST['proposal_id'] ?? null);
        $decision = is_scalar($_POST['decision'] ?? null) ? sanitize_key(wp_unslash((string) $_POST['decision'])) : '';
        $canonical_input = isset($_POST['canonical_subject_id']) && is_scalar($_POST['canonical_subject_id'])
            ? wp_unslash((string) $_POST['canonical_subject_id']) : null;
        $canonical_id = self::positive_id($canonical_input, true);
        $canonical_valid = $decision === 'approve_alias'
            ? $canonical_id > 0 && (string) $canonical_id === (string) $canonical_input
            : in_array($decision, array('approve_new','reject'), true) && $canonical_input === '0';
        $challenge_id = isset($_POST['step_up_challenge_id']) && is_scalar($_POST['step_up_challenge_id'])
            ? wp_unslash((string) $_POST['step_up_challenge_id']) : '';
        $assertion_json = isset($_POST['step_up_credential']) && is_scalar($_POST['step_up_credential'])
            ? wp_unslash((string) $_POST['step_up_credential']) : '';
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['_wpnonce'])) : '';
        $proof = $proposal_id && $canonical_valid && class_exists('ZMP_Passkeys')
            ? ZMP_Passkeys::verify_step_up(
                get_current_user_id(),
                $challenge_id,
                'president_subject_decision',
                $proposal_id,
                $decision,
                $canonical_id,
                $assertion_json,
                $nonce
            )
            : new WP_Error('invalid_step_up');
        $route_still_valid = !is_wp_error($proof)
            && wp_verify_nonce($nonce, self::NONCE)
            && self::can_review()
            && get_current_user_id() > 0;
        $result = $route_still_valid && $proposal_id && $canonical_valid && class_exists('ZMP_Mentor_Subject_Proposals')
            ? ZMP_Mentor_Subject_Proposals::decide(get_current_user_id(), $proposal_id, $decision, $canonical_id)
            : new WP_Error('invalid_decision');
        $status = is_wp_error($result) ? 'failed' : ($decision === 'reject' ? 'rejected' : 'approved');
        $allowed_decisions = array('approve_new','approve_alias','reject');
        $logged_decision = in_array($decision, $allowed_decisions, true) ? $decision : 'invalid';
        self::debug_event(is_wp_error($result) ? 'warning' : 'info', 'President subject decision processed.', array(
            'proposal_id'=>$proposal_id,
            'decision'=>$logged_decision,
            'outcome'=>$status,
            'error_code'=>is_wp_error($result) ? $result->get_error_code() : '',
        ));
        $url = add_query_arg(array('page'=>self::PAGE,'zmp_subject_decision_status'=>$status), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    private static function pending_proposals() {
        global $wpdb;
        if (!class_exists('ZMP_Mentor_Subject_Proposals') || !ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
            self::schema_not_ready('pending_proposals');
            return new WP_Error('schema_not_ready', 'Регистарот на предмети моментално не е достапен поради проблем со шемата.');
        }
        $rows = $wpdb->get_results("SELECT id,proposed_name,created_at FROM {$wpdb->prefix}zmp_subject_proposals WHERE status='pending' ORDER BY created_at ASC,id ASC");
        if (!is_array($rows)) {
            self::debug_event('error', 'President subject proposal queue query failed.', array('outcome'=>'fail_closed','error_code'=>'schema_not_ready'));
            return new WP_Error('schema_not_ready', 'Регистарот на предмети моментално не е достапен поради проблем со шемата.');
        }
        return $rows;
    }

    private static function render_schema_notice() {
        echo '<div class="notice notice-error" role="alert"><p>' . esc_html__('Редицата за предлози моментално не е достапна поради проблем со шемата. Проверете ја состојбата на регистарот и обидете се повторно.', 'zmp-competitions') . '</p></div>';
    }

    private static function schema_not_ready($operation) {
        $allowed_operations = array('render');
        $operation = in_array($operation, $allowed_operations, true) ? $operation : 'unknown';
        self::debug_event('error', 'President subject proposal schema is not ready.', array(
            'outcome'=>'fail_closed',
            'error_code'=>'schema_not_ready',
        ));
    }

    private static function positive_id($value, $allow_zero = false) {
        if ($allow_zero && ($value === 0 || $value === '0')) { return 0; }
        if (!is_int($value) && (!is_string($value) || !preg_match('/^[1-9][0-9]*$/', $value))) { return 0; }
        $number = (int) $value;
        return $number > 0 && (string) $number === (string) $value ? $number : 0;
    }

    /** Emit only high-level, privacy-safe President decision diagnostics. */
    private static function debug_event($level, $message, $context = array()) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level, $message, $context, 'teacher_subject_proposals'); }
    }
}



