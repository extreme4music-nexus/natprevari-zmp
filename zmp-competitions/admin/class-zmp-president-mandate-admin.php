<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress admin controls for appointing and revoking the President mandate.
 * The mandate service remains the authoritative authorization and validation layer.
 */
class ZMP_President_Mandate_Admin {
    const PAGE = 'zmp-president-mandate';
    const HANDOVER_PAGE = 'zmp-president-handover-extension';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_post_zmp_president_mandate_appoint', array(__CLASS__, 'handle_appointment'));
        add_action('admin_post_zmp_president_mandate_revoke', array(__CLASS__, 'handle_revocation'));
        add_action('admin_post_zmp_president_mandate_cancel', array(__CLASS__, 'handle_scheduled_cancellation'));
    }

    public static function register_menu() {
        if (!current_user_can('manage_options')) {
            $context = class_exists('ZMP_President_Mandate') && is_callable(array('ZMP_President_Mandate', 'handover_extension_context'))
                ? ZMP_President_Mandate::handover_extension_context()
                : false;
            if (is_object($context) && !is_wp_error($context)) {
                add_menu_page(
                    __('Продолжување на предавање', 'zmp-competitions'),
                    __('Предавање на мандат', 'zmp-competitions'),
                    'read',
                    self::HANDOVER_PAGE,
                    array(__CLASS__, 'render_handover_extension_page'),
                    'dashicons-update',
                    58
                );
            }
            return;
        }

        add_submenu_page(
            'zmp-competitions',
            __('Претседателски мандат', 'zmp-competitions'),
            __('Мандат на претседател', 'zmp-competitions'),
            'manage_options',
            self::PAGE,
            array(__CLASS__, 'render_page')
        );
    }

    public static function enqueue_assets($hook_suffix) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (!in_array($page, array(self::PAGE, self::HANDOVER_PAGE), true)) {
            return;
        }

        wp_enqueue_style(
            'zmp-president-mandate-admin',
            ZMP_PLUGIN_URL . 'assets/css/zmp-president-mandate.css',
            array(),
            defined('ZMP_VERSION') ? ZMP_VERSION : null
        );
    }

    private static function require_administrator() {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('Само администратор може да управува со претседателски мандат.', 'zmp-competitions'),
                '',
                array('response' => 403)
            );
        }
    }

    private static function posted_text($key, $textarea = false) {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            return '';
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        if ($textarea) {
            $value = sanitize_textarea_field(wp_unslash($_POST[$key]));
        }

        return trim($value);
    }

    private static function redirect_with_notice($notice) {
        $url = add_query_arg(
            array(
                'page' => self::PAGE,
                'zmp_mandate_notice' => sanitize_key($notice),
            ),
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }

    public static function handle_appointment() {
        self::require_administrator();
        check_admin_referer('zmp_president_mandate_appoint');

        if (!class_exists('ZMP_President_Mandate')) {
            self::redirect_with_notice('service_unavailable');
        }

        $user_id = isset($_POST['president_user_id']) ? absint(wp_unslash($_POST['president_user_id'])) : 0;
        $start_date = self::posted_text('start_date');
        $end_date = ZMP_President_Mandate::term_end_date($start_date);
        if (is_wp_error($end_date)) {
            self::redirect_with_notice('error_' . $end_date->get_error_code());
        }
        $board_decision = self::posted_text('board_decision', true);

        $result = ZMP_President_Mandate::appoint($user_id, $start_date, $end_date, $board_decision);
        if (is_wp_error($result)) {
            self::redirect_with_notice('error_' . $result->get_error_code());
        }

        if ($start_date > current_time('Y-m-d')) {
            self::redirect_with_notice('scheduled');
        }

        self::redirect_with_notice('appointed');
    }

    public static function handle_revocation() {
        self::require_administrator();
        check_admin_referer('zmp_president_mandate_revoke');

        if (!class_exists('ZMP_President_Mandate')) {
            self::redirect_with_notice('service_unavailable');
        }

        $mandate_id = isset($_POST['mandate_id']) ? absint(wp_unslash($_POST['mandate_id'])) : 0;
        $reason = self::posted_text('revocation_reason', true);
        $result = ZMP_President_Mandate::revoke($mandate_id, $reason);
        if (is_wp_error($result)) {
            self::redirect_with_notice('error_' . $result->get_error_code());
        }

        self::redirect_with_notice('revoked');
    }

    public static function handle_scheduled_cancellation() {
        self::require_administrator();

        $mandate_id = isset($_POST['mandate_id']) ? absint(wp_unslash($_POST['mandate_id'])) : 0;
        check_admin_referer('zmp_president_mandate_cancel_' . $mandate_id);

        if (!class_exists('ZMP_President_Mandate')) {
            self::redirect_with_notice('service_unavailable');
        }

        $reason = self::posted_text('cancellation_reason', true);
        $result = ZMP_President_Mandate::revoke($mandate_id, $reason);
        if (is_wp_error($result)) {
            self::redirect_with_notice('error_' . $result->get_error_code());
        }

        self::redirect_with_notice('scheduled_cancelled');
    }

    private static function notice_message($notice) {
        $messages = array(
            'appointed' => array('success', __('Мандатот е евидентиран и пристапот е доделен.', 'zmp-competitions')),
            'scheduled' => array('success', __('Идниот мандат е евидентиран. Претседателскиот пристап ќе започне на внесениот почетен датум.', 'zmp-competitions')),
            'scheduled_cancelled' => array('success', __('Закажаното назначување е откажано и евидентирано во историјата.', 'zmp-competitions')),
            'revoked' => array('success', __('Мандатот е отповикан и пристапот е отстранет.', 'zmp-competitions')),
            'service_unavailable' => array('error', __('Услугата за мандати не е достапна. Освежете ја страницата или проверете ја инсталацијата.', 'zmp-competitions')),
            'error_appointment_requires_admin' => array('error', __('Само администратор може да назначи претседател.', 'zmp-competitions')),
            'error_mandate_revoke_requires_admin' => array('error', __('Само администратор може да отповика мандат.', 'zmp-competitions')),
            'error_mandate_invalid_user' => array('error', __('Изберете постоечки WordPress корисник што може да ја добие претседателската улога.', 'zmp-competitions')),
            'error_president_account_admin' => array('error', __('Сметката не ги исполнува задолжителните улоги за Претседател: subscriber и zmp_teacher.', 'zmp-competitions')),
            'error_mandate_invalid_start_date' => array('error', __('Внесете валиден датум за почеток на мандатот.', 'zmp-competitions')),
            'error_mandate_invalid_end_date' => array('error', __('Внесете валиден датум за крај на мандатот.', 'zmp-competitions')),
            'error_mandate_invalid_date_range' => array('error', __('Крајниот датум мора да биде на или по почетниот датум.', 'zmp-competitions')),
            'error_mandate_term_overlap' => array('error', __('Периодот се преклопува со друг мандат. Изберете датум што не се преклопува.', 'zmp-competitions')),
            'error_mandate_term_already_expired' => array('error', __('Не може да се назначи мандат што завршил пред денешниот датум.', 'zmp-competitions')),
            'error_mandate_transition_busy' => array('error', __('Друг администратор моментално менува мандат. Почекајте и обидете се повторно.', 'zmp-competitions')),
            'error_mandate_board_decision_required' => array('error', __('Внесете ја ознаката или бројот на одлуката на Управниот одбор.', 'zmp-competitions')),
            'error_mandate_schema_not_ready' => array('error', __('Табелите за мандати не се подготвени. Пробајте повторно по проверката на базата.', 'zmp-competitions')),
            'error_mandate_db_write_failed' => array('error', __('Промената не е зачувана. Проверете ја базата и обидете се повторно.', 'zmp-competitions')),
            'error_mandate_role_assignment_failed' => array('error', __('Улогата не е доделена; проверете ја сметката и обидете се повторно.', 'zmp-competitions')),
            'error_mandate_not_found' => array('error', __('Активниот мандат не е пронајден или веќе е променет.', 'zmp-competitions')),
            'error_mandate_reason_required' => array('error', __('Внесете причина за отповикување на мандатот.', 'zmp-competitions')),
        );

        return isset($messages[$notice]) ? $messages[$notice] : null;
    }

    private static function row_value($row, $key, $default = '') {
        if (is_object($row) && isset($row->{$key})) {
            return $row->{$key};
        }
        if (is_array($row) && array_key_exists($key, $row)) {
            return $row[$key];
        }
        return $default;
    }

    /** A former President receives only this scoped handover action while eligible. */
    public static function render_handover_extension_page() {
        if (!current_user_can('read') || !class_exists('ZMP_President_Mandate')
            || !is_callable(array('ZMP_President_Mandate', 'handover_extension_context'))) {
            wp_die(esc_html__('Немате пристап до оваа страница.', 'zmp-competitions'), '', array('response' => 403));
        }

        $context = ZMP_President_Mandate::handover_extension_context();
        if (!$context || is_wp_error($context)) {
            wp_die(esc_html__('Нема активно барање за предавање на мандат за оваа сметка.', 'zmp-competitions'), '', array('response' => 403));
        }

        $notice_code = isset($_GET['zmp_handover_notice']) ? sanitize_key(wp_unslash($_GET['zmp_handover_notice'])) : '';
        $facsimile_notice_code = isset($_GET['zmp_president_facsimile_status'])
            ? sanitize_key(wp_unslash($_GET['zmp_president_facsimile_status']))
            : '';
        $facsimile_handover = class_exists('ZMP_President_Facsimile_Certificates')
            && is_callable(array('ZMP_President_Facsimile_Certificates', 'pending_handover_for_current_user'))
            ? ZMP_President_Facsimile_Certificates::pending_handover_for_current_user()
            : false;
        ?>
        <div class="wrap zmp-admin-wrap zmp-president-mandate zmp-pm-handover-page">
            <h1><?php esc_html_e('Продолжување на предавање на мандат', 'zmp-competitions'); ?></h1>
            <p><?php esc_html_e('Оваа ограничена страница му дозволува на претходниот претседател да го продолжи рокот за предавање на документацијата за еден месец по барање.', 'zmp-competitions'); ?></p>
            <?php if ('extended' === $notice_code): ?>
                <div class="notice notice-success" role="status" aria-live="polite"><p><?php esc_html_e('Рокот е продолжен за еден месец и промената е запишана во ревизорската историја.', 'zmp-competitions'); ?></p></div>
            <?php elseif (strpos($notice_code, 'error_') === 0): ?>
                <div class="notice notice-error" role="alert"><p><?php esc_html_e('Продолжувањето не е зачувано. Проверете дали рокот сè уште е активен и обидете се повторно.', 'zmp-competitions'); ?></p></div>
            <?php endif; ?>

            <?php if ('facsimile_confirmed' === $facsimile_notice_code): ?>
                <div class="notice notice-success" role="status" aria-live="polite"><p><?php esc_html_e('Потврдата за замената е евидентирана. Новиот факсимил е активиран, а претходниот е архивиран.', 'zmp-competitions'); ?></p></div>
            <?php elseif ('facsimile_refused' === $facsimile_notice_code): ?>
                <div class="notice notice-success" role="status" aria-live="polite"><p><?php esc_html_e('Одбивањето е евидентирано. Новиот факсимил останува неактивен.', 'zmp-competitions'); ?></p></div>
            <?php elseif (strpos($facsimile_notice_code, 'facsimile_error_') === 0): ?>
                <div class="notice notice-error" role="alert"><p><?php esc_html_e('Одлуката за замена не е зачувана. Проверете дали барањето и рокот сè уште се важечки.', 'zmp-competitions'); ?></p></div>
            <?php endif; ?>
            <section class="zmp-pm-card zmp-pm-handover-card" aria-labelledby="zmp-pm-handover-heading">
                <h2 id="zmp-pm-handover-heading"><?php esc_html_e('Тековен рок за предавање', 'zmp-competitions'); ?></h2>
                <dl class="zmp-pm-summary">
                    <div><dt><?php esc_html_e('Нов претседател', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label(self::row_value($context, 'successor_user_id', 0))); ?></dd></div>
                    <div><dt><?php esc_html_e('Краен рок', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($context, 'deadline', '—')); ?></dd></div>
                </dl>
                <p class="description"><?php esc_html_e('Основниот рок е три календарски месеци од почетокот на новиот мандат. При истек се архивираат само документите од претходниот мандат; записите и шифрираните документи се задржуваат без трајно бришење.', 'zmp-competitions'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-pm-handover-form">
                    <input type="hidden" name="action" value="zmp_president_handover_extend">
                    <?php wp_nonce_field('zmp_president_handover_extend', 'zmp_president_handover_nonce'); ?>
                    <label for="zmp-pm-handover-reason"><?php esc_html_e('Причина за едномесечно продолжување', 'zmp-competitions'); ?></label>
                    <textarea id="zmp-pm-handover-reason" name="handover_reason" rows="3" required aria-describedby="zmp-pm-handover-help"></textarea>
                    <p class="description" id="zmp-pm-handover-help"><?php esc_html_e('Секое поднесување додава еден календарски месец и се евидентира. Повторете го барањето ако е потребно уште време.', 'zmp-competitions'); ?></p>
                    <?php submit_button(__('Продолжи за еден месец', 'zmp-competitions'), 'primary', 'submit'); ?>
                </form>
            </section>

            <?php if (is_array($facsimile_handover) || is_object($facsimile_handover)): ?>
                <?php $handover_id = absint(self::row_value($facsimile_handover, 'certificate_id', 0)); ?>
                <?php if ($handover_id > 0
                    && class_exists('ZMP_President_Facsimile_Certificates')
                    && defined('ZMP_President_Facsimile_Certificates::HANDOVER_ACTION')
                    && is_callable(array('ZMP_President_Facsimile_Certificates', 'handover_action_url'))
                    && is_callable(array('ZMP_President_Facsimile_Certificates', 'handover_nonce_action'))): ?>
                    <section class="zmp-pm-card zmp-pm-handover-card" aria-labelledby="zmp-pm-facsimile-handover-heading">
                        <h2 id="zmp-pm-facsimile-handover-heading"><?php esc_html_e('Потврда за замена на факсимил', 'zmp-competitions'); ?></h2>
                        <p><?php esc_html_e('Ова е одделно барање за замена на факсимил. Одлуката важи само за прикажаниот потпишан предлог; одбивањето го остава предлогот неактивен.', 'zmp-competitions'); ?></p>
                        <dl class="zmp-pm-summary">
                            <div><dt><?php esc_html_e('Нов претседател', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label(self::row_value($facsimile_handover, 'successor_user_id', 0))); ?></dd></div>
                            <div><dt><?php esc_html_e('Сметка на претходниот претседател', 'zmp-competitions'); ?></dt><dd><?php echo esc_html((string) absint(self::row_value($facsimile_handover, 'predecessor_user_id', 0))); ?></dd></div>
                            <div><dt><?php esc_html_e('ID на сертификатот', 'zmp-competitions'); ?></dt><dd><?php echo esc_html((string) $handover_id); ?></dd></div>
                            <div><dt><?php esc_html_e('ID на новиот факсимил', 'zmp-competitions'); ?></dt><dd><?php echo esc_html((string) absint(self::row_value($facsimile_handover, 'facsimile_id', 0))); ?></dd></div>
                            <div><dt><?php esc_html_e('SHA-256 на новиот факсимил', 'zmp-competitions'); ?></dt><dd><code><?php echo esc_html(sanitize_text_field((string) self::row_value($facsimile_handover, 'facsimile_sha256', ''))); ?></code></dd></div>
                            <?php if (absint(self::row_value($facsimile_handover, 'outgoing_certificate_id', 0)) > 0): ?>
                                <div><dt><?php esc_html_e('ID на сертификатот што се заменува', 'zmp-competitions'); ?></dt><dd><?php echo esc_html((string) absint(self::row_value($facsimile_handover, 'outgoing_certificate_id', 0))); ?></dd></div>
                            <?php endif; ?>
                            <?php if (absint(self::row_value($facsimile_handover, 'outgoing_facsimile_id', 0)) > 0): ?>
                                <div><dt><?php esc_html_e('Претходен претседател', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(sanitize_text_field((string) self::row_value($facsimile_handover, 'outgoing_president_name', ''))); ?></dd></div>
                                <div><dt><?php esc_html_e('ID на претходниот факсимил што се заменува', 'zmp-competitions'); ?></dt><dd><?php echo esc_html((string) absint(self::row_value($facsimile_handover, 'outgoing_facsimile_id', 0))); ?></dd></div>
                                <div><dt><?php esc_html_e('SHA-256 на претходниот факсимил', 'zmp-competitions'); ?></dt><dd><code><?php echo esc_html(sanitize_text_field((string) self::row_value($facsimile_handover, 'outgoing_facsimile_sha256', ''))); ?></code></dd></div>
                            <?php endif; ?>
                            <div><dt><?php esc_html_e('Рок за одлука', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(sanitize_text_field((string) self::row_value($facsimile_handover, 'handover_deadline', ''))); ?></dd></div>
                        </dl>
                        <?php foreach (array('confirm' => __('Потврди ја оваа замена', 'zmp-competitions'), 'refuse' => __('Одбиј ја оваа замена', 'zmp-competitions')) as $decision => $label): ?>
                            <form method="post" action="<?php echo esc_url(ZMP_President_Facsimile_Certificates::handover_action_url()); ?>" class="zmp-pm-handover-form">
                                <input type="hidden" name="certificate_id" value="<?php echo esc_attr((string) $handover_id); ?>">
                                <input type="hidden" name="decision" value="<?php echo esc_attr($decision); ?>">
                                <?php wp_nonce_field(ZMP_President_Facsimile_Certificates::handover_nonce_action($handover_id, $decision), 'zmp_president_facsimile_nonce'); ?>
                                <?php submit_button($label, 'confirm' === $decision ? 'primary' : 'secondary', 'submit', false); ?>
                            </form>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function user_label($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) {
            return __('Непозната сметка', 'zmp-competitions');
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return sprintf(__('Корисник #%d (избришан)', 'zmp-competitions'), $user_id);
        }
        return sprintf('%s (#%d)', $user->display_name, $user_id);
    }

    private static function format_event($event_type) {
        $labels = array(
            'appointed' => __('Назначување', 'zmp-competitions'),
            'replaced' => __('Замена', 'zmp-competitions'),
            'started' => __('Почеток на мандат', 'zmp-competitions'),
            'expired' => __('Истек', 'zmp-competitions'),
            'revoked' => __('Отповикување', 'zmp-competitions'),
            'activation_failed' => __('Неуспешна активација', 'zmp-competitions'),
        );
        return isset($labels[$event_type]) ? $labels[$event_type] : (string) $event_type;
    }

    private static function eligible_users($renewal_user_id = 0) {
        $users = get_users(
            array(
                'orderby' => 'display_name',
                'order' => 'ASC',
                'number' => 1000,
                'fields' => 'all',
            )
        );

        if (!is_array($users)) {
            return array();
        }

        $renewal_user_id = absint($renewal_user_id);
        return array_values(array_filter($users, static function ($user) use ($renewal_user_id) {
            if (!($user instanceof WP_User)) {
                return false;
            }

            $roles = array_values((array) $user->roles);
            $is_eligible_member = function_exists('zmp_competitions_president_account_is_eligible')
                && zmp_competitions_president_account_is_eligible($user);
            $is_current_president = $renewal_user_id > 0 && (int) $user->ID === $renewal_user_id
                && in_array('zmp_president', $roles, true) && $is_eligible_member;
            return $is_eligible_member || $is_current_president;
        }));
    }

    public static function render_page() {
        self::require_administrator();

        $notice_code = isset($_GET['zmp_mandate_notice']) ? sanitize_key(wp_unslash($_GET['zmp_mandate_notice'])) : '';
        $notice = self::notice_message($notice_code);
        $current = class_exists('ZMP_President_Mandate') ? ZMP_President_Mandate::current_mandate() : null;
        $history = class_exists('ZMP_President_Mandate') ? ZMP_President_Mandate::history() : array();
        $scheduled = class_exists('ZMP_President_Mandate') && is_callable(array('ZMP_President_Mandate', 'scheduled_mandates'))
            ? ZMP_President_Mandate::scheduled_mandates()
            : array();
        $history_error = is_wp_error($history);
        $scheduled_error = is_wp_error($scheduled);
        if (!is_array($history) && !$history_error) {
            $history = is_iterable($history) ? iterator_to_array($history, false) : array();
        }
        if (!is_array($scheduled) && !$scheduled_error) {
            $scheduled = is_iterable($scheduled) ? iterator_to_array($scheduled, false) : array();
        }
        $current_error = is_wp_error($current);
        $current_id = self::row_value($current, 'id', self::row_value($current, 'mandate_id', is_numeric($current) ? $current : 0));
        $current_user_id = self::row_value($current, 'user_id', 0);
        $users = self::eligible_users($current_user_id);

        $governance_available = class_exists('ZMP_President_Governance');
        $governance_retention_status = $governance_available
            && is_callable(array('ZMP_President_Governance', 'retention_status'))
            ? ZMP_President_Governance::retention_status()
            : null;
        $governance_retention_status_error = $governance_available
            && (!is_array($governance_retention_status) || is_wp_error($governance_retention_status));
        $governance_encryption_ready = is_array($governance_retention_status)
            ? !empty($governance_retention_status['encryption_ready'])
            : ($governance_available && is_callable(array('ZMP_President_Governance', 'encryption_ready')) && ZMP_President_Governance::encryption_ready());
        $governance_status = isset($_GET['zmp_president_governance_status'])
            ? sanitize_key(wp_unslash($_GET['zmp_president_governance_status']))
            : '';
        $governance_notices = array(
            'saved' => array('success', __('Промените се зачувани.', 'zmp-competitions')),
            'saved_with_audit_warning' => array('warning', __('Исходот на дејството не може да се потврди. Проверете ја архивата или историјата пред повторно да се обидете.', 'zmp-competitions')),
            'acknowledged' => array('success', __('Претседателот ја потврди тековната верзија на обврските.', 'zmp-competitions')),
            'invalid' => array('error', __('Податоците не се исправни или верзијата веќе постои. Проверете ги внесените полиња.', 'zmp-competitions')),
            'forbidden' => array('error', __('Немате дозвола за ова дејство.', 'zmp-competitions')),
            'failed' => array('error', __('Дејството не успеа. Не е потврдено дека записот е зачуван.', 'zmp-competitions')),
        );
        $governance_documents = $governance_current_obligation = $governance_obligation_history = $governance_acknowledgments = array();
        $governance_documents_error = $governance_current_error = $governance_history_error = $governance_acknowledgments_error = false;
        $governance_mandates = array();
        $governance_mandates_error = false;
        if ($governance_available) {
            $governance_documents = ZMP_President_Governance::president_documents();
            $governance_current_obligation = ZMP_President_Governance::current_obligation();
            $governance_obligation_history = ZMP_President_Governance::obligation_history();
            $governance_acknowledgments = ZMP_President_Governance::acknowledgment_history();
            $governance_documents_error = is_wp_error($governance_documents);
            $governance_current_error = is_wp_error($governance_current_obligation);
            $governance_history_error = is_wp_error($governance_obligation_history);
            $governance_acknowledgments_error = is_wp_error($governance_acknowledgments);
            if (!$governance_documents_error && !is_array($governance_documents)) { $governance_documents = is_iterable($governance_documents) ? iterator_to_array($governance_documents, false) : array(); }
            if (!$governance_history_error && !is_array($governance_obligation_history)) { $governance_obligation_history = is_iterable($governance_obligation_history) ? iterator_to_array($governance_obligation_history, false) : array(); }
            if (!$governance_acknowledgments_error && !is_array($governance_acknowledgments)) { $governance_acknowledgments = is_iterable($governance_acknowledgments) ? iterator_to_array($governance_acknowledgments, false) : array(); }

            global $wpdb;
            if (class_exists('ZMP_President_Mandate') && is_callable(array('ZMP_President_Mandate', 'table_name'))) {
                $mandate_table = ZMP_President_Mandate::table_name();
                if (is_string($mandate_table) && preg_match('/^[A-Za-z0-9_]+$/', $mandate_table)) {
                    $governance_mandates = $wpdb->get_results("SELECT id,user_id,start_date,end_date,status FROM `{$mandate_table}` ORDER BY start_date DESC,id DESC");
                    if ($wpdb->last_error) { $governance_mandates_error = true; $governance_mandates = array(); }
                } else {
                    $governance_mandates_error = true;
                }
            } else {
                $governance_mandates_error = true;
            }
        }

        ?>
        <div class="wrap zmp-admin-wrap zmp-president-mandate">
            <h1><?php esc_html_e('Претседателски мандат', 'zmp-competitions'); ?></h1>
            <p><?php esc_html_e('Назначувањето, датумите, одлуката на Управниот одбор и завршувањето на мандатот се евидентираат за ревизија.', 'zmp-competitions'); ?></p>

            <?php if ($notice): ?>
                <div class="notice notice-<?php echo esc_attr($notice[0]); ?> is-dismissible" role="status" aria-live="polite">
                    <p><?php echo esc_html($notice[1]); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($governance_notices[$governance_status])): ?>
                <div class="notice notice-<?php echo esc_attr($governance_notices[$governance_status][0]); ?> is-dismissible" role="status" aria-live="polite">
                    <p><?php echo esc_html($governance_notices[$governance_status][1]); ?></p>
                </div>
            <?php endif; ?>

            <div class="notice notice-warning zmp-pm-account-notice" role="note">
                <p><strong><?php esc_html_e('Користете посебна, неадминистраторска сметка.', 'zmp-competitions'); ?></strong>
                    <?php esc_html_e('Администраторот го евидентира мандатот. Кандидатот мора веќе да има subscriber и zmp_teacher. Администраторските и другите дозволени ЗМП улоги може да останат комбинирани; формата не ги менува.', 'zmp-competitions'); ?>
                    <?php esc_html_e('Кандидатот може да ги задржи и другите дозволени ЗМП улоги, како модератор, комисија, тестатор или организатор; формата не ги менува. Администраторски сметки не се прифатливи.', 'zmp-competitions'); ?>
                    <a href="<?php echo esc_url(admin_url('user-new.php')); ?>"><?php esc_html_e('Додај корисник', 'zmp-competitions'); ?></a>
                </p>
            </div>

            <?php if ($current_error): ?>
                <div class="notice notice-error" role="alert"><p><?php esc_html_e('Не може да се прочита тековниот мандат. Назначувањето и отповикувањето се оневозможени додека не се провери базата.', 'zmp-competitions'); ?></p></div>
            <?php elseif (is_array($current) || is_object($current) || is_numeric($current)): ?>
                <section class="zmp-pm-card zmp-pm-active" aria-labelledby="zmp-pm-active-heading">
                    <div class="zmp-pm-section-heading">
                        <div>
                            <h2 id="zmp-pm-active-heading"><?php esc_html_e('Тековен мандат', 'zmp-competitions'); ?></h2>
                            <p><?php esc_html_e('Само активниот мандат дава пристап до надзорната страница.', 'zmp-competitions'); ?></p>
                        </div>
                        <span class="zmp-pm-status"><?php esc_html_e('Активен', 'zmp-competitions'); ?></span>
                    </div>
                    <dl class="zmp-pm-summary">
                        <div><dt><?php esc_html_e('Претседател', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label($current_user_id)); ?></dd></div>
                        <div><dt><?php esc_html_e('Период (4 години, заклучно)', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($current, 'start_date', '—')); ?> – <?php echo esc_html(self::row_value($current, 'end_date', '—')); ?></dd></div>
                        <?php $current_handover_deadline = sanitize_text_field((string) self::row_value($current, 'handover_deadline', '')); ?>
                        <?php if ($current_handover_deadline !== ''): ?>
                            <div><dt><?php esc_html_e('Рок за предавање документација', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($current_handover_deadline); ?></dd></div>
                        <?php endif; ?>
                        <div><dt><?php esc_html_e('Одлука на Управниот одбор', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($current, 'board_decision', '—')); ?></dd></div>
                    </dl>
                    <?php if ($current_id): ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-pm-revoke-form" onsubmit="return confirm('<?php echo esc_js(__('Дали сигурно сакате да го отповикате мандатот?', 'zmp-competitions')); ?>');">
                            <input type="hidden" name="action" value="zmp_president_mandate_revoke">
                            <input type="hidden" name="mandate_id" value="<?php echo esc_attr(absint($current_id)); ?>">
                            <?php wp_nonce_field('zmp_president_mandate_revoke'); ?>
                            <label for="zmp-pm-revocation-reason"><?php esc_html_e('Причина за отповикување', 'zmp-competitions'); ?></label>
                            <textarea id="zmp-pm-revocation-reason" name="revocation_reason" rows="2" required aria-describedby="zmp-pm-revoke-help"></textarea>
                            <p class="description" id="zmp-pm-revoke-help"><?php esc_html_e('Причината се зачувува во ревизорската историја.', 'zmp-competitions'); ?></p>
                            <?php submit_button(__('Отповикај мандат', 'zmp-competitions'), 'secondary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="zmp-pm-card zmp-pm-empty" aria-labelledby="zmp-pm-active-heading">
                    <h2 id="zmp-pm-active-heading"><?php esc_html_e('Нема активен мандат', 'zmp-competitions'); ?></h2>
                    <p><?php esc_html_e('Назначете регистриран неадминистраторски корисник и наведете го периодот и одлуката на Управниот одбор.', 'zmp-competitions'); ?></p>
                </section>
            <?php endif; ?>

            <section class="zmp-pm-card zmp-pm-governance" aria-labelledby="zmp-pm-governance-heading">
                <div class="zmp-pm-section-heading">
                    <div>
                        <h2 id="zmp-pm-governance-heading"><?php esc_html_e('Изборни документи и обврски', 'zmp-competitions'); ?></h2>
                        <p><?php esc_html_e('Документите се поврзуваат со конкретен мандат. По завршување на рокот за предавање се архивираат документите од претходниот мандат; архивата се чува шифрирана без трајно бришење.', 'zmp-competitions'); ?></p>
                    </div>
                </div>

                <?php if (!$governance_available): ?>
                    <p class="notice notice-warning inline" role="status"><?php esc_html_e('Евиденцијата за изборни документи и обврски моментално не е достапна.', 'zmp-competitions'); ?></p>
                <?php else: ?>
                    <?php if ($governance_retention_status_error): ?>
                        <p class="notice notice-error inline" role="alert" aria-live="assertive"><?php esc_html_e('Статусот на архивирањето и задржувањето не може да се провери.', 'zmp-competitions'); ?></p>
                    <?php else: ?>
                        <?php $governance_due_documents = absint($governance_retention_status['due_documents'] ?? 0); ?>
                        <?php $governance_plaintext_cleanup = absint($governance_retention_status['plaintext_cleanup_pending'] ?? 0); ?>
                        <?php if ($governance_due_documents > 0): ?>
                            <p class="notice notice-warning inline" role="status" aria-live="polite"><?php echo esc_html(sprintf(_n('%s документ чека задоцнето архивирање.', '%s документи чекаат задоцнето архивирање.', $governance_due_documents, 'zmp-competitions'), number_format_i18n($governance_due_documents))); ?></p>
                        <?php endif; ?>
                        <?php if ($governance_plaintext_cleanup > 0): ?>
                            <p class="notice notice-error inline" role="alert" aria-live="assertive"><?php echo esc_html(sprintf(_n('%s архивиран документ чека чистење на старата незашифрирана копија.', '%s архивирани документи чекаат чистење на старите незашифрирани копии.', $governance_plaintext_cleanup, 'zmp-competitions'), number_format_i18n($governance_plaintext_cleanup))); ?></p>
                        <?php endif; ?>
                        <?php if ($governance_due_documents === 0 && $governance_plaintext_cleanup === 0): ?>
                            <p class="notice notice-success inline" role="status" aria-live="polite"><?php esc_html_e('Нема документи што чекаат архивирање или чистење на стара копија.', 'zmp-competitions'); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!$governance_encryption_ready): ?>
                        <p class="notice notice-error inline" role="alert"><?php esc_html_e('Шифрирањето не е подготвено. Поставувањето и архивирањето се оневозможени додека администраторот не конфигурира стабилен таен клуч во wp-config.php. Не внесувајте го клучот во оваа форма.', 'zmp-competitions'); ?></p>
                    <?php endif; ?>
                    <div class="zmp-pm-governance-grid">
                        <section class="zmp-pm-governance-section" aria-labelledby="zmp-pm-governance-upload-heading">
                            <h3 id="zmp-pm-governance-upload-heading"><?php esc_html_e('Додај изборен документ', 'zmp-competitions'); ?></h3>
                            <?php if (!$governance_encryption_ready): ?>
                                <p class="notice notice-warning inline" role="status"><?php esc_html_e('Поставувањето е оневозможено додека не се подготви шифрирањето.', 'zmp-competitions'); ?></p>
                            <?php elseif ($governance_mandates_error): ?>
                                <p class="notice notice-error inline" role="alert"><?php esc_html_e('Списокот на мандати не може да се вчита; поставувањето е оневозможено.', 'zmp-competitions'); ?></p>
                            <?php elseif (!$governance_mandates): ?>
                                <p class="zmp-pm-muted" role="status"><?php esc_html_e('Нема евидентирани мандати за поврзување со документ.', 'zmp-competitions'); ?></p>
                            <?php else: ?>
                                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(ZMP_President_Governance::form_action_url('upload')); ?>" class="zmp-pm-governance-form">
                                    <?php wp_nonce_field(ZMP_President_Governance::nonce_action('upload'), '_zmp_president_governance_nonce'); ?>
                                    <label for="zmp-pm-governance-mandate"><?php esc_html_e('Мандат', 'zmp-competitions'); ?></label>
                                    <select id="zmp-pm-governance-mandate" name="mandate_id" required>
                                        <option value=""><?php esc_html_e('Изберете мандат', 'zmp-competitions'); ?></option>
                                        <?php foreach ($governance_mandates as $governance_mandate): ?>
                                            <?php
                                            $governance_mandate_id = absint(self::row_value($governance_mandate, 'id', 0));
                                            $governance_mandate_user = absint(self::row_value($governance_mandate, 'user_id', 0));
                                            $governance_mandate_status = sanitize_key((string) self::row_value($governance_mandate, 'status', ''));
                                            $governance_mandate_period = sanitize_text_field((string) self::row_value($governance_mandate, 'start_date', '')) . ' – ' . sanitize_text_field((string) self::row_value($governance_mandate, 'end_date', ''));
                                            ?>
                                            <?php if ($governance_mandate_id): ?>
                                                <option value="<?php echo esc_attr($governance_mandate_id); ?>"><?php echo esc_html(self::user_label($governance_mandate_user) . ' · ' . $governance_mandate_period . ' · ' . $governance_mandate_status); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="zmp-pm-governance-document"><?php esc_html_e('Изборен записник или документ за мандатот (PDF)', 'zmp-competitions'); ?></label>
                                    <input id="zmp-pm-governance-document" type="file" name="president_election_document" accept="application/pdf,.pdf" required>
                                    <p class="description"><?php esc_html_e('Документот се чува во приватна архива и се поврзува со избраниот мандат.', 'zmp-competitions'); ?></p>
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Прикачи документ', 'zmp-competitions'); ?></button>
                                </form>
                            <?php endif; ?>
                        </section>

                        <section class="zmp-pm-governance-section" aria-labelledby="zmp-pm-governance-documents-heading">
                            <h3 id="zmp-pm-governance-documents-heading"><?php esc_html_e('Архива на документи', 'zmp-competitions'); ?></h3>
                            <?php if ($governance_documents_error): ?>
                                <p class="notice notice-error inline" role="alert"><?php esc_html_e('Документите не можеа да се вчитаат.', 'zmp-competitions'); ?></p>
                            <?php elseif (!$governance_documents): ?>
                                <p class="zmp-pm-muted" role="status"><?php esc_html_e('Сè уште нема изборни документи.', 'zmp-competitions'); ?></p>
                            <?php else: ?>
                                <div class="zmp-pm-governance-list">
                                    <?php foreach ($governance_documents as $governance_document): ?>
                                        <?php
                                        $governance_document_id = absint(self::row_value($governance_document, 'id', 0));
                                        $governance_document_name = sanitize_text_field((string) self::row_value($governance_document, 'original_name', 'Документ'));
                                        $governance_document_status = sanitize_key((string) self::row_value($governance_document, 'status', ''));
                                        $governance_document_created = sanitize_text_field((string) self::row_value($governance_document, 'created_at', ''));
                                        $governance_document_size = absint(self::row_value($governance_document, 'file_size', 0));
                                        $governance_document_mime = sanitize_mime_type((string) self::row_value($governance_document, 'mime_type', 'application/pdf'));
                                        $governance_document_created_by = absint(self::row_value($governance_document, 'created_by', 0));
                                        $governance_document_archived_at = sanitize_text_field((string) self::row_value($governance_document, 'archived_at', ''));
                                        $governance_document_archived_by = absint(self::row_value($governance_document, 'archived_by', 0));
                                        $governance_document_mandate = absint(self::row_value($governance_document, 'mandate_id', 0));
                                        $is_document_archived = $governance_document_status === 'archived';
                                        ?>
                                        <article class="zmp-pm-governance-item" aria-labelledby="zmp-pm-governance-document-<?php echo esc_attr($governance_document_id); ?>">
                                            <div class="zmp-pm-governance-item-heading">
                                                <h4 id="zmp-pm-governance-document-<?php echo esc_attr($governance_document_id); ?>"><?php echo esc_html($governance_document_name); ?></h4>
                                                <span class="zmp-pm-governance-badge <?php echo $is_document_archived ? 'is-archived' : 'is-active'; ?>"><?php echo esc_html($is_document_archived ? __('Архивиран', 'zmp-competitions') : __('Активен', 'zmp-competitions')); ?></span>
                                            </div>
                                            <dl class="zmp-pm-governance-meta">
                                                <div><dt><?php esc_html_e('Мандат', 'zmp-competitions'); ?></dt><dd>#<?php echo esc_html($governance_document_mandate); ?></dd></div>
                                                <div><dt><?php esc_html_e('Формат', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($governance_document_mime); ?></dd></div>
                                                <div><dt><?php esc_html_e('Големина', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(size_format($governance_document_size)); ?></dd></div>
                                                <div><dt><?php esc_html_e('Додаден', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($governance_document_created); ?></dd></div>
                                                <div><dt><?php esc_html_e('Додал', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label($governance_document_created_by)); ?></dd></div>
                                                <?php if ($is_document_archived): ?>
                                                    <div><dt><?php esc_html_e('Архивиран', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($governance_document_archived_at); ?></dd></div>
                                                    <div><dt><?php esc_html_e('Архивирал', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label($governance_document_archived_by)); ?></dd></div>
                                                <?php endif; ?>
                                            </dl>
                                            <div class="zmp-pm-governance-actions">
                                                <?php if ($governance_document_id): ?>
                                                    <?php if (!$is_document_archived && $governance_encryption_ready): ?>
                                                        <form method="post" action="<?php echo esc_url(ZMP_President_Governance::form_action_url('archive')); ?>">
                                                            <?php wp_nonce_field(ZMP_President_Governance::nonce_action('archive'), '_zmp_president_governance_nonce'); ?>
                                                            <input type="hidden" name="document_id" value="<?php echo esc_attr($governance_document_id); ?>">
                                                            <button type="submit" class="button button-secondary"><?php esc_html_e('Архивирај (зачувај евиденција)', 'zmp-competitions'); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <section class="zmp-pm-governance-section zmp-pm-governance-obligations" aria-labelledby="zmp-pm-governance-obligations-heading">
                        <h3 id="zmp-pm-governance-obligations-heading"><?php esc_html_e('Верзии на обврските', 'zmp-competitions'); ?></h3>
                        <?php if ($governance_history_error): ?>
                            <p class="notice notice-error inline" role="alert"><?php esc_html_e('Историјата на обврски не може да се вчита.', 'zmp-competitions'); ?></p>
                        <?php elseif (!$governance_obligation_history): ?>
                            <p class="zmp-pm-muted" role="status"><?php esc_html_e('Сè уште нема објавени верзии.', 'zmp-competitions'); ?></p>
                        <?php else: ?>
                            <div class="zmp-pm-governance-list">
                                <?php foreach ($governance_obligation_history as $governance_version): ?>
                                    <?php
                                    $governance_version_id = absint(self::row_value($governance_version, 'id', 0));
                                    $governance_version_label = sanitize_text_field((string) self::row_value($governance_version, 'version_label', ''));
                                    $governance_version_hash = strtolower(sanitize_text_field((string) self::row_value($governance_version, 'text_sha256', self::row_value($governance_version, 'content_sha256', ''))));
                                    $governance_version_published = sanitize_text_field((string) self::row_value($governance_version, 'published_at', self::row_value($governance_version, 'created_at', '')));
                                    $governance_version_published_by = absint(self::row_value($governance_version, 'published_by', self::row_value($governance_version, 'created_by', 0)));
                                    $governance_is_current = !is_wp_error($governance_current_obligation) && (int) self::row_value($governance_current_obligation, 'id', 0) === $governance_version_id;
                                    ?>
                                    <article class="zmp-pm-governance-item" aria-labelledby="zmp-pm-governance-version-<?php echo esc_attr($governance_version_id); ?>">
                                        <div class="zmp-pm-governance-item-heading">
                                            <h4 id="zmp-pm-governance-version-<?php echo esc_attr($governance_version_id); ?>"><?php echo esc_html($governance_version_label); ?></h4>
                                            <?php if ($governance_is_current): ?><span class="zmp-pm-governance-badge is-active"><?php esc_html_e('Тековна верзија', 'zmp-competitions'); ?></span><?php endif; ?>
                                        </div>
                                        <dl class="zmp-pm-governance-meta">
                                            <div><dt><?php esc_html_e('Одлука на Управниот одбор', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($governance_version, 'board_reference', '')); ?></dd></div>
                                            <div><dt><?php esc_html_e('Правен преглед', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($governance_version, 'legal_reference', '')); ?></dd></div>
                                            <div><dt><?php esc_html_e('Објавил', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::user_label($governance_version_published_by)); ?></dd></div>
                                            <div><dt><?php esc_html_e('Објавено', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($governance_version_published); ?></dd></div>
                                        </dl>
                                        <details class="zmp-pm-governance-version-text"><summary><?php esc_html_e('Прикажи го објавениот текст', 'zmp-competitions'); ?></summary><div><?php echo esc_html((string) self::row_value($governance_version, 'text', '')); ?></div></details>
                                        <div class="zmp-pm-governance-acknowledgments">
                                            <h5><?php esc_html_e('Потврди', 'zmp-competitions'); ?></h5>
                                            <?php if ($governance_acknowledgments_error): ?>
                                                <p class="notice notice-error inline" role="alert"><?php esc_html_e('Потврдите не можеа да се вчитаат.', 'zmp-competitions'); ?></p>
                                            <?php else: ?>
                                                <?php
                                                $version_acks = array();
                                                foreach ((array) $governance_acknowledgments as $governance_ack) {
                                                    $ack_version_id = absint(self::row_value($governance_ack, 'obligation_version_id', 0));
                                                    $ack_hash = strtolower(sanitize_text_field((string) self::row_value($governance_ack, 'obligation_sha256', self::row_value($governance_ack, 'content_sha256', ''))));
                                                    if ($ack_version_id === $governance_version_id && ($governance_version_hash === '' || hash_equals($governance_version_hash, $ack_hash))) { $version_acks[] = $governance_ack; }
                                                }
                                                ?>
                                                <?php if (!$version_acks): ?>
                                                    <p class="zmp-pm-muted"><?php esc_html_e('Сè уште нема потврди за оваа верзија.', 'zmp-competitions'); ?></p>
                                                <?php else: ?>
                                                    <ul>
                                                        <?php foreach ($version_acks as $governance_ack): ?>
                                                            <li><?php echo esc_html(self::user_label(absint(self::row_value($governance_ack, 'user_id', 0)))); ?> · <?php esc_html_e('мандат', 'zmp-competitions'); ?> #<?php echo esc_html(absint(self::row_value($governance_ack, 'mandate_id', 0))); ?> · <?php echo esc_html(self::row_value($governance_ack, 'acknowledged_at', '')); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($governance_current_error): ?>
                            <p class="notice notice-error inline" role="alert"><?php esc_html_e('Тековната верзија не може да се прочита; објавувањето е оневозможено.', 'zmp-competitions'); ?></p>
                        <?php else: ?>
                            <form method="post" action="<?php echo esc_url(ZMP_President_Governance::form_action_url('publish')); ?>" class="zmp-pm-governance-form zmp-pm-governance-publish">
                                <?php wp_nonce_field(ZMP_President_Governance::nonce_action('publish'), '_zmp_president_governance_nonce'); ?>
                                <h4><?php esc_html_e('Објави нова верзија', 'zmp-competitions'); ?></h4>
                                <p class="description" id="zmp-pm-governance-publish-help"><?php esc_html_e('Внесете само точен текст доставен и веќе одобрен од Управниот одбор и прегледан од правно овластено лице. Не внесувајте нацрт или сопствено формулиран текст.', 'zmp-competitions'); ?></p>
                                <label for="zmp-pm-governance-version-label"><?php esc_html_e('Ознака на верзија', 'zmp-competitions'); ?></label>
                                <input id="zmp-pm-governance-version-label" type="text" name="version_label" maxlength="100" required>
                                <label for="zmp-pm-governance-version-text"><?php esc_html_e('Одобрениот текст', 'zmp-competitions'); ?></label>
                                <textarea id="zmp-pm-governance-version-text" name="text" rows="10" maxlength="200000" required aria-describedby="zmp-pm-governance-publish-help"></textarea>
                                <label for="zmp-pm-governance-board-reference"><?php esc_html_e('Референца за одлуката на Управниот одбор', 'zmp-competitions'); ?></label>
                                <textarea id="zmp-pm-governance-board-reference" name="board_reference" rows="2" maxlength="4000" required></textarea>
                                <label for="zmp-pm-governance-legal-reference"><?php esc_html_e('Референца за правниот преглед', 'zmp-competitions'); ?></label>
                                <textarea id="zmp-pm-governance-legal-reference" name="legal_reference" rows="2" maxlength="4000" required></textarea>
                                <button type="submit" class="button button-primary"><?php esc_html_e('Објави нова верзија', 'zmp-competitions'); ?></button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </section>

            <section class="zmp-pm-card" aria-labelledby="zmp-pm-appoint-heading">
                <div class="zmp-pm-section-heading">
                    <div>
                        <h2 id="zmp-pm-appoint-heading"><?php esc_html_e('Назначи претседател', 'zmp-competitions'); ?></h2>
                        <p><?php esc_html_e('Ако веќе има активен мандат, назначувањето го евидентира неговото заменување.', 'zmp-competitions'); ?></p>
                    </div>
                </div>
                <?php if ($current_error): ?>
                    <p class="zmp-pm-muted"><?php esc_html_e('Формата е привремено оневозможена бидејќи статусот на активниот мандат не може да се провери.', 'zmp-competitions'); ?></p>
                <?php elseif (!$users): ?>
                    <p class="zmp-pm-muted"><?php esc_html_e('Нема достапна неадминистраторска сметка за назначување.', 'zmp-competitions'); ?> <a href="<?php echo esc_url(admin_url('user-new.php')); ?>"><?php esc_html_e('Додај корисник', 'zmp-competitions'); ?></a></p>
                <?php else: ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-pm-appointment-form">
                        <input type="hidden" name="action" value="zmp_president_mandate_appoint">
                        <?php wp_nonce_field('zmp_president_mandate_appoint'); ?>
                        <div class="zmp-pm-form-grid">
                            <div class="zmp-pm-field zmp-pm-field-wide">
                                <label for="zmp-pm-president-user"><?php esc_html_e('Регистриран корисник', 'zmp-competitions'); ?></label>
                                <select id="zmp-pm-president-user" name="president_user_id" required aria-describedby="zmp-pm-user-help">
                                    <option value=""><?php esc_html_e('Изберете корисник', 'zmp-competitions'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name . ' (#' . $user->ID . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description" id="zmp-pm-user-help"><?php esc_html_e('Изберете регистриран корисник што веќе има subscriber и zmp_teacher. Администраторските и дозволените дополнителни ЗМП улоги се зачувуваат; формата не ги менува. Тековниот Претседател може да се избере при обновување.', 'zmp-competitions'); ?></p>
                            </div>
                            <div class="zmp-pm-field">
                                <label for="zmp-pm-start-date"><?php esc_html_e('Почеток на мандатот', 'zmp-competitions'); ?></label>
                                <input id="zmp-pm-start-date" type="date" name="start_date" required aria-describedby="zmp-pm-term-help">
                                <p class="description" id="zmp-pm-term-help"><?php esc_html_e('Мандатот трае точно четири календарски години. Крајниот датум се пресметува автоматски и не се внесува рачно.', 'zmp-competitions'); ?></p>
                            </div>
                            <div class="zmp-pm-field zmp-pm-term-preview" aria-live="polite">
                                <span class="zmp-pm-term-preview-label"><?php esc_html_e('Автоматски пресметан крај (заклучно)', 'zmp-competitions'); ?></span>
                                <output id="zmp-pm-end-date-preview" for="zmp-pm-start-date" data-empty="<?php esc_attr_e('Изберете почетен датум.', 'zmp-competitions'); ?>"><?php esc_html_e('Изберете почетен датум.', 'zmp-competitions'); ?></output>
                            </div>
                            <div class="zmp-pm-field zmp-pm-field-wide">
                                <label for="zmp-pm-board-decision"><?php esc_html_e('Одлука на Управниот одбор', 'zmp-competitions'); ?></label>
                                <textarea id="zmp-pm-board-decision" name="board_decision" rows="3" required aria-describedby="zmp-pm-decision-help"></textarea>
                                <p class="description" id="zmp-pm-decision-help"><?php esc_html_e('Внесете број, датум или кратка ознака за одлуката. Не прикачувајте документ во оваа фаза.', 'zmp-competitions'); ?></p>
                            </div>
                        </div>
                        <?php submit_button(__('Евидентирај назначување', 'zmp-competitions'), 'primary', 'submit'); ?>
                    </form>
                    <script>
                    (function () {
                        var start = document.getElementById('zmp-pm-start-date');
                        var end = document.getElementById('zmp-pm-end-date-preview');
                        if (!start || !end) { return; }
                        function updateTermEnd() {
                            if (!start.value) {
                                end.textContent = end.getAttribute('data-empty') || '';
                                return;
                            }
                            var parts = start.value.split('-').map(Number);
                            var anniversary = new Date(Date.UTC(parts[0] + 4, parts[1] - 1, parts[2]));
                            anniversary.setUTCDate(anniversary.getUTCDate() - 1);
                            end.textContent = anniversary.toISOString().slice(0, 10);
                        }
                        start.addEventListener('input', updateTermEnd);
                        start.addEventListener('change', updateTermEnd);
                        updateTermEnd();
                    }());
                    </script>
                <?php endif; ?>
            </section>

            <section class="zmp-pm-card" aria-labelledby="zmp-pm-scheduled-heading">
                <div class="zmp-pm-section-heading">
                    <div>
                        <h2 id="zmp-pm-scheduled-heading"><?php esc_html_e('Закажани назначувања', 'zmp-competitions'); ?></h2>
                        <p><?php esc_html_e('Овие мандати започнуваат на нивниот почетен датум. Може да се откажат пред активирање.', 'zmp-competitions'); ?></p>
                    </div>
                </div>
                <?php if ($scheduled_error): ?>
                    <p class="notice notice-error inline" role="alert"><?php esc_html_e('Закажаните назначувања не можеа да се вчитаат.', 'zmp-competitions'); ?></p>
                <?php elseif (!$scheduled): ?>
                    <p class="zmp-pm-muted"><?php esc_html_e('Нема закажани назначувања.', 'zmp-competitions'); ?></p>
                <?php else: ?>
                    <div class="zmp-pm-scheduled-list">
                        <?php foreach ($scheduled as $appointment): ?>
                            <?php
                            $scheduled_id = absint(self::row_value($appointment, 'id', 0));
                            $scheduled_user_id = absint(self::row_value($appointment, 'user_id', 0));
                            ?>
                            <article class="zmp-pm-scheduled-item" aria-labelledby="zmp-pm-scheduled-<?php echo esc_attr($scheduled_id); ?>">
                                <div class="zmp-pm-scheduled-details">
                                    <h3 id="zmp-pm-scheduled-<?php echo esc_attr($scheduled_id); ?>"><?php echo esc_html(self::user_label($scheduled_user_id)); ?></h3>
                                    <dl>
                                        <div><dt><?php esc_html_e('Период (4 години, заклучно)', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($appointment, 'start_date', '—')); ?> – <?php echo esc_html(self::row_value($appointment, 'end_date', '—')); ?></dd></div>
                                        <?php $scheduled_handover_deadline = sanitize_text_field((string) self::row_value($appointment, 'handover_deadline', '')); ?>
                                        <?php if ($scheduled_handover_deadline !== ''): ?>
                                            <div><dt><?php esc_html_e('Рок за предавање документација', 'zmp-competitions'); ?></dt><dd><?php echo esc_html($scheduled_handover_deadline); ?></dd></div>
                                        <?php endif; ?>
                                        <div><dt><?php esc_html_e('Одлука на Управниот одбор', 'zmp-competitions'); ?></dt><dd><?php echo esc_html(self::row_value($appointment, 'board_decision', '—')); ?></dd></div>
                                    </dl>
                                </div>
                                <?php if ($scheduled_id): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-pm-cancel-form">
                                        <input type="hidden" name="action" value="zmp_president_mandate_cancel">
                                        <input type="hidden" name="mandate_id" value="<?php echo esc_attr($scheduled_id); ?>">
                                        <?php wp_nonce_field('zmp_president_mandate_cancel_' . $scheduled_id); ?>
                                        <label for="zmp-pm-cancel-reason-<?php echo esc_attr($scheduled_id); ?>"><?php esc_html_e('Причина за откажување', 'zmp-competitions'); ?></label>
                                        <textarea id="zmp-pm-cancel-reason-<?php echo esc_attr($scheduled_id); ?>" name="cancellation_reason" rows="2" required aria-describedby="zmp-pm-cancel-help-<?php echo esc_attr($scheduled_id); ?>"></textarea>
                                        <p class="description" id="zmp-pm-cancel-help-<?php echo esc_attr($scheduled_id); ?>"><?php esc_html_e('Откажувањето се запишува во ревизорската историја.', 'zmp-competitions'); ?></p>
                                        <?php submit_button(__('Откажи назначување', 'zmp-competitions'), 'secondary', 'submit', false); ?>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="zmp-pm-card" aria-labelledby="zmp-pm-history-heading">
                <div class="zmp-pm-section-heading">
                    <div>
                        <h2 id="zmp-pm-history-heading"><?php esc_html_e('Ревизорска историја', 'zmp-competitions'); ?></h2>
                        <p><?php esc_html_e('Историјата ги прикажува назначувањата, замените, истекувањата и отповикувањата.', 'zmp-competitions'); ?></p>
                    </div>
                </div>
                <?php if ($history_error): ?>
                    <p class="notice notice-error inline" role="alert"><?php esc_html_e('Историјата не може да се вчита.', 'zmp-competitions'); ?></p>
                <?php elseif (!$history): ?>
                    <p class="zmp-pm-muted"><?php esc_html_e('Сè уште нема записи за мандат.', 'zmp-competitions'); ?></p>
                <?php else: ?>
                    <div class="zmp-pm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e('Ревизорска историја на мандати', 'zmp-competitions'); ?>">
                        <table class="widefat striped zmp-pm-history-table">
                            <thead><tr>
                                <th scope="col"><?php esc_html_e('Датум', 'zmp-competitions'); ?></th>
                                <th scope="col"><?php esc_html_e('Настан', 'zmp-competitions'); ?></th>
                                <th scope="col"><?php esc_html_e('Корисник', 'zmp-competitions'); ?></th>
                                <th scope="col"><?php esc_html_e('Период', 'zmp-competitions'); ?></th>
                                <th scope="col"><?php esc_html_e('Одлука / причина', 'zmp-competitions'); ?></th>
                                <th scope="col"><?php esc_html_e('Извршил', 'zmp-competitions'); ?></th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($history as $event): ?>
                                    <?php
                                    $event_type = (string) self::row_value($event, 'event_type', '');
                                    $start_date = (string) self::row_value($event, 'start_date', '');
                                    $end_date = (string) self::row_value($event, 'end_date', '');
                                    $period = trim($start_date . ($end_date !== '' ? ' – ' . $end_date : ''), ' –');
                                    $decision = (string) self::row_value($event, 'board_decision', '');
                                    $reason = (string) self::row_value($event, 'reason', '');
                                    $decision_or_reason = $decision !== '' ? $decision : $reason;
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html(self::row_value($event, 'created_at', '—')); ?></td>
                                        <td><?php echo esc_html(self::format_event($event_type)); ?></td>
                                        <td><?php echo esc_html(self::user_label(self::row_value($event, 'user_id', 0))); ?></td>
                                        <td><?php echo esc_html($period !== '' ? $period : '—'); ?></td>
                                        <td><?php echo esc_html($decision_or_reason !== '' ? $decision_or_reason : '—'); ?></td>
                                        <td><?php echo esc_html(self::user_label(self::row_value($event, 'actor_user_id', 0))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php
    }
}
