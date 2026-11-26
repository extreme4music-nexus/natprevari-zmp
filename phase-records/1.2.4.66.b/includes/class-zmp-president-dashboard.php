<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Read-only aggregate oversight for the President role.
 *
 * Queries intentionally return only grouped dimensions and counts. Never add
 * participant-level fields or links to this page.
 */
class ZMP_President_Dashboard {
    const PAGE = 'zmp-president-oversight';

    public static function init() {
        if (is_admin()) {
            add_action('admin_menu', array(__CLASS__, 'register_menu'));
            add_action('admin_menu', array(__CLASS__, 'restrict_admin_menu'), PHP_INT_MAX);
            add_action('admin_init', array(__CLASS__, 'enforce_admin_scope'), 1);
        }
    }

    private static function is_president_member_user() {
        if (!is_user_logged_in() || !current_user_can('view_zmp_president_dashboard') || current_user_can('manage_options')) {
            return false;
        }
        $roles = array_values((array) wp_get_current_user()->roles);
        $required_roles = array('subscriber', 'zmp_teacher', 'zmp_president');
        $allowed_roles = array_merge($required_roles, array('zmp_moderator', 'zmp_commission', 'zmp_testator', 'zmp_organizer'));
        return !array_diff($required_roles, $roles) && !array_diff($roles, $allowed_roles);
    }

    private static function admin_screen_policy($screen, $page = '', $action = '') {
        if ($screen === 'index.php') { return 'redirect'; }
        if ($screen === 'profile.php') { return 'allow'; }
        if ($screen === 'admin.php' && in_array($page, array(self::PAGE, ZMP_President_Subject_Approvals::PAGE), true)) { return 'allow'; }
        if ($screen === 'admin-post.php' && in_array($action, array(
            'zmp_president_governance_download',
            'zmp_president_governance_acknowledge',
            'zmp_president_handover_extend',
            ZMP_Registration_Requirements::FACSIMILE_SUBMIT_ACTION,
            ZMP_President_Facsimile_Certificates::REGISTER_ACTION,
            'zmp_school_email_access_decide',
            ZMP_President_Subject_Approvals::ACTION,
            ZMP_Passkey_Profile::OPTIONS_ACTION,
            ZMP_Passkey_Profile::FINISH_ACTION,
            ZMP_Passkey_Profile::REMOVE_ACTION,
        ), true)) { return 'allow'; }
        if ($screen === 'admin-ajax.php' && $action === 'zmp_president_public_display_get') { return 'allow'; }
        return 'deny';
    }

    public static function enforce_admin_scope() {
        if (!self::is_president_member_user()) { return; }
        global $pagenow;
        $screen = isset($pagenow) ? (string) $pagenow : '';
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (!ZMP_President_Mandate::user_has_active_mandate(get_current_user_id())) {
            if ($screen === 'profile.php') { return; }
            wp_die(esc_html__('Вашиот претседателски мандат не е активен.', 'zmp-competitions'), '', array('response' => 403));
        }
        $action = '';
        if (isset($_REQUEST['action']) && is_scalar($_REQUEST['action'])) {
            $action = sanitize_key(wp_unslash((string) $_REQUEST['action']));
        }
        $policy = self::admin_screen_policy($screen, $page, $action);
        if ($policy === 'redirect') {
            wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE));
            exit;
        }
        if ($policy === 'deny') {
            wp_die(esc_html__('Оваа администраторска страница не е достапна за претседателската улога.', 'zmp-competitions'), '', array('response' => 403));
        }
    }

    public static function restrict_admin_menu() {
        if (!self::is_president_member_user()) { return; }
        global $menu;
        foreach ((array) $menu as $item) {
            $slug = isset($item[2]) ? (string) $item[2] : '';
            if ($slug !== '' && $slug !== self::PAGE) {
                remove_menu_page($slug);
            }
        }
    }

    private static function can_view_dashboard() {
        if (!is_user_logged_in()) {
            return false;
        }
        if (current_user_can('manage_options')) {
            return true;
        }
        if (!self::is_president_member_user()) {
            return false;
        }
        return ZMP_President_Mandate::user_has_active_mandate(get_current_user_id());
    }

    public static function register_menu() {
        if (!self::can_view_dashboard()) {
            return;
        }
        add_menu_page(
            'Надзор на натпреварите',
            'Надзор',
            current_user_can('manage_options') ? 'manage_options' : 'view_zmp_president_dashboard',
            self::PAGE,
            array(__CLASS__, 'render'),
            'dashicons-chart-area',
            26
        );
    }

    public static function render() {
        if (!self::can_view_dashboard()) {
            wp_die(esc_html__('Немате пристап до оваа страница.', 'zmp-competitions'), '', array('response' => 403));
        }

        $snapshot = self::snapshot();
        echo '<div class="wrap zmp-president-dashboard"><h1>Надзор на натпреварувачката година</h1>';
        if (class_exists('ZMP_President_Subject_Approvals')) { ZMP_President_Subject_Approvals::render_dashboard_notice(); }
        echo '<p class="zmp-president-privacy">Овој приказ содржи само збирни бројки. Не прикажува имиња, училишни записи, пораки или други лични податоци.</p>';
        self::render_governance_panel();
        self::render_facsimile_certificate_panel();

        if (!empty($snapshot['error'])) {
            echo '<div class="notice notice-warning"><p>Збирниот преглед моментално не е достапен. Обидете се повторно подоцна.</p></div></div>';
            return;
        }

        $years = $snapshot['years'];
        if (!$years) {
            echo '<div class="notice notice-info"><p>Сè уште нема внесени натпреварувачки години.</p></div></div>';
            return;
        }

        echo '<nav class="zmp-president-years" aria-label="Филтер по натпреварувачка година">';
        foreach ($years as $year) {
            $url = add_query_arg(array('page' => self::PAGE, 'zmp_president_year' => $year), admin_url('admin.php'));
            $active = $year === $snapshot['year'];
            echo '<a class="button '.($active ? 'button-primary' : '').'" href="'.esc_url($url).'">'.esc_html($year).'</a> ';
        }
        echo '</nav>';

        $cards = array(
            'Пријави' => (int)$snapshot['totals']['entries'],
            'Документи уредни' => (int)$snapshot['totals']['docs_ready'],
            'Документи за проверка/исправка' => (int)$snapshot['totals']['docs_waiting'],
            'Оценети учесници' => (int)$snapshot['totals']['scored'],
            'Потврдени резултати' => (int)$snapshot['totals']['confirmed'],
        );
        echo '<section class="zmp-president-cards" aria-label="Збирен напредок">';
        foreach ($cards as $label => $value) {
            echo '<article class="zmp-president-card"><span>'.esc_html($label).'</span><strong>'.number_format_i18n($value).'</strong></article>';
        }
        echo '</section>';

        echo '<h2>Напредок по категорија, регион и група</h2>';
        if (!$snapshot['rows']) {
            echo '<p>Нема пријави за избраната натпреварувачка година.</p>';
        } else {
            echo '<div class="zmp-president-table-wrap"><table class="widefat striped"><thead><tr>';
            echo '<th>Категорија</th><th>Регион</th><th>Група / одделение</th><th>Пријави</th><th>Уредни документи</th><th>Чека проверка</th><th>Оценети</th>';
            echo '<th>Училишно ниво</th><th>Општинско ниво</th><th>Регионално ниво</th><th>Државно ниво</th>';
            echo '</tr></thead><tbody>';
            foreach ($snapshot['rows'] as $row) {
                echo '<tr><td>'.esc_html($row['category']).'</td><td>'.esc_html($row['region']).'</td><td>'.esc_html($row['group']).'</td>';
                echo '<td>'.number_format_i18n((int)$row['entries']).'</td><td>'.number_format_i18n((int)$row['docs_ready']).'</td><td>'.number_format_i18n((int)$row['docs_waiting']).'</td><td>'.number_format_i18n((int)$row['scored']).'</td>';
                echo '<td>'.number_format_i18n((int)$row['school']).'</td><td>'.number_format_i18n((int)$row['municipal']).'</td><td>'.number_format_i18n((int)$row['regional']).'</td><td>'.number_format_i18n((int)$row['state']).'</td></tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '<p class="description">Бројките се збирни и се освежуваат при отворање на страницата.</p>';
        echo '<style>
        .zmp-president-dashboard{max-width:1500px}.zmp-president-privacy{padding:12px 16px;background:#eef6fb;border-left:4px solid #1877a8;border-radius:4px}
        .zmp-president-years{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0}
        .zmp-president-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:12px;margin:18px 0 28px}
        .zmp-president-card{display:flex;flex-direction:column;gap:8px;background:#fff;border:1px solid #d9e2ec;border-radius:10px;padding:16px;box-shadow:0 2px 6px #102a430d}
        .zmp-president-card span{color:#526477;font-size:13px}.zmp-president-card strong{font-size:27px;color:#173b5e}
        .zmp-president-table-wrap{overflow:auto}.zmp-president-table-wrap table{min-width:1050px}.zmp-president-table-wrap th{white-space:nowrap}
        @media(max-width:782px){.zmp-president-dashboard{margin-right:10px}.zmp-president-card{padding:12px}.zmp-president-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
        </style></div>';
    }

    private static function governance_value($row, $key, $default = '') {
        if (is_array($row) && array_key_exists($key, $row)) { return $row[$key]; }
        if (is_object($row) && isset($row->{$key})) { return $row->{$key}; }
        return $default;
    }

    /** Public-key registration UI. Private signing keys must remain with the President. */
    private static function render_facsimile_certificate_panel() {
        if (!self::is_president_member_user()
            || !class_exists('ZMP_President_Facsimile_Certificates')
            || !is_callable(array('ZMP_President_Facsimile_Certificates', 'is_current_president'))
            || !ZMP_President_Facsimile_Certificates::is_current_president()) {
            return;
        }

        $certificate = ZMP_President_Facsimile_Certificates::current_certificate_status();
        $registration_context = is_callable(array('ZMP_President_Facsimile_Certificates', 'current_registration_context'))
            ? ZMP_President_Facsimile_Certificates::current_registration_context()
            : new WP_Error('facsimile_registration_context_unavailable');
        $pending_facsimile = is_callable(array('ZMP_Registration_Requirements', 'current_pending_facsimile'))
            ? ZMP_Registration_Requirements::current_pending_facsimile()
            : false;
        $status = isset($_GET['zmp_president_facsimile_status'])
            ? sanitize_key(wp_unslash($_GET['zmp_president_facsimile_status']))
            : '';
        $messages = array(
            'uploaded' => array('success', 'Факсимилот е зачуван како неактивен предлог за тековниот мандат.'),
            'upload_invalid' => array('error', 'Факсимилот не е зачуван. Проверете го форматот, датумите и големината на датотеката.'),
            'upload_failed' => array('error', 'Факсимилот не е зачуван. Обидете се повторно подоцна.'),
            'registered' => array('success', 'Сертификатот е регистриран или повторно издаден; претходните записи остануваат во историјата.'),
            'pending_handover' => array('warning', 'Потпишаниот сертификат чека посебна потврда од претходниот претседател и сè уште не може да се користи за нови документи.'),
            'reissued' => array('success', 'Новиот сертификат е регистриран; претходната верзија е зачувана во историјата.'),
            'revoked' => array('success', 'Сертификатот е отповикан.'),
            'invalid' => array('error', 'Сертификатот не е регистриран. Проверете ги јавниот клуч, потписот и податоците во манифестот.'),
            'forbidden' => array('error', 'Немате дозвола за ова дејство.'),
            'failed' => array('error', 'Дејството не успеа. Обидете се повторно подоцна.'),
        );
        $status_data = !is_wp_error($certificate) && is_array($certificate) ? $certificate : array('status' => 'none');
        $certificate_status = sanitize_key((string) self::governance_value($status_data, 'status', 'none'));
        $status_labels = array(
            'none' => 'Нема регистриран сертификат за тековниот мандат.',
            'active' => 'Активен',
            'pending_handover' => 'Чека посебна потврда од претходниот претседател; неактивен е за нова употреба',
            'expired' => 'Истечен',
            'revoked' => 'Отповикан',
            'superseded' => 'Заменет со понова верзија',
        );

        echo '<style>
        .zmp-president-certificate{margin:22px 0 30px;padding:18px;border:1px solid #d9e2ec;border-radius:10px;background:#fff;box-shadow:0 2px 6px #102a430d;min-width:0}
        .zmp-president-certificate h2{margin:0 0 10px;color:#173b5e;font-size:20px}
        .zmp-president-certificate__meta{display:grid;grid-template-columns:minmax(130px,.35fr) minmax(0,1fr);gap:8px 14px;margin:14px 0}
        .zmp-president-certificate__meta dt{font-weight:600;color:#526477}.zmp-president-certificate__meta dd{margin:0;min-width:0;overflow-wrap:anywhere;word-break:break-word}
        .zmp-president-certificate__form{display:grid;gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0}
        .zmp-president-certificate__form fieldset{min-width:0;margin:0;padding:0;border:0}
        .zmp-president-certificate__form label{display:block;font-weight:600}
        .zmp-president-certificate__form textarea{display:block;width:100%;max-width:100%;min-height:76px;margin-top:5px;box-sizing:border-box;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;overflow-wrap:anywhere}
        .zmp-president-certificate__form textarea[name="signed_manifest_b64"]{min-height:100px;white-space:pre-wrap;overflow-wrap:anywhere;resize:vertical}
        .zmp-president-certificate__form .description{display:block;margin-top:4px;font-weight:400}
        .zmp-president-certificate__form textarea:focus-visible,.zmp-president-certificate__form button:focus-visible{outline:3px solid #1685ee;outline-offset:2px}
        @media(max-width:600px){.zmp-president-certificate{padding:14px}.zmp-president-certificate__meta{grid-template-columns:1fr;gap:2px}.zmp-president-certificate__meta dd{margin-bottom:8px}.zmp-president-certificate__form button{width:100%;min-height:44px}}
        </style>';

        echo '<section class="zmp-president-certificate" aria-labelledby="zmp-president-certificate-heading">';
        echo '<h2 id="zmp-president-certificate-heading">' . esc_html__('Криптографски сертификат за факсимил', 'zmp-competitions') . '</h2>';
        echo '<p>' . esc_html__('Приватниот клуч го создавате и го чувате вие, надвор од WordPress. Никогаш не внесувајте приватен клуч во оваа форма. Серверот го чува само јавниот клуч, потпишаниот манифест и податоците за сертификатот.', 'zmp-competitions') . '</p>';

        self::render_pending_facsimile_submission($pending_facsimile);

        if (is_wp_error($certificate)) {
            echo '<p class="notice notice-warning inline" role="status" aria-live="polite">' . esc_html__('Статусот на сертификатот моментално не може да се вчита.', 'zmp-competitions') . '</p>';
        } else {
            echo '<p class="notice notice-info inline" role="status"><strong>' . esc_html($status_labels[$certificate_status] ?? __('Непознат статус', 'zmp-competitions')) . '</strong></p>';
            if ($certificate_status !== 'none') {
                echo '<dl class="zmp-president-certificate__meta">';
                $fields = array(
                    'serial' => __('Сериски број', 'zmp-competitions'),
                    'fingerprint' => __('Отпечаток на клучот', 'zmp-competitions'),
                    'facsimile_sha256' => __('SHA-256 на овластениот факсимил', 'zmp-competitions'),
                    'valid_from' => __('Важи од', 'zmp-competitions'),
                    'valid_until' => __('Важи до', 'zmp-competitions'),
                    'revoked_at' => __('Отповикан на', 'zmp-competitions'),
                    'revocation_reason' => __('Причина за отповикување', 'zmp-competitions'),
                );
                foreach ($fields as $key => $label) {
                    $value = sanitize_text_field((string) self::governance_value($status_data, $key, ''));
                    if ($value === '') { continue; }
                    echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd>';
                }
                echo '</dl>';
            }
        }

        if (isset($messages[$status])) {
            echo '<p class="notice notice-' . esc_attr($messages[$status][0]) . ' inline" role="status" aria-live="polite">' . esc_html($messages[$status][1]) . '</p>';
        } elseif ($status !== '') {
            echo '<p class="notice notice-error inline" role="alert">' . esc_html__('Сертификатот не е регистриран. Проверете ги податоците или обидете се повторно.', 'zmp-competitions') . '</p>';
        }

        if (is_wp_error($registration_context) || !is_array($registration_context)) {
            if (is_array($pending_facsimile) || is_object($pending_facsimile)) {
                echo '<p class="notice notice-warning inline" role="status" aria-live="polite">' . esc_html__('Предлогот е зачуван, но податоците за потпишување моментално не се достапни. Обидете се повторно подоцна.', 'zmp-competitions') . '</p>';
            }
        } else {
            if (!empty($registration_context['handover_required'])) {
                $predecessor = absint($registration_context['predecessor_user_id'] ?? 0);
                $deadline = sanitize_text_field((string) ($registration_context['handover_deadline'] ?? ''));
                echo '<div class="notice notice-warning inline" role="status" aria-live="polite"><p>' . esc_html(sprintf(
                    __('По потпишувањето, сертификатот ќе остане неактивен додека претходниот претседател (сметка #%1$d) одделно не ја потврди замената, најдоцна до %2$s.', 'zmp-competitions'),
                    $predecessor,
                    $deadline !== '' ? $deadline : __('крајниот рок за предавање', 'zmp-competitions')
                )) . '</p></div>';
            }
            echo '<h3>' . esc_html__('Податоци што мора да бидат врзани со манифестот', 'zmp-competitions') . '</h3>';
            echo '<p class="description">' . esc_html__('Користете ги овие точни вредности во потпишаниот манифест.', 'zmp-competitions') . '</p>';
            echo '<dl class="zmp-president-certificate__meta">';
            $context_fields = array(
                'mandate_id' => __('ID на мандатот', 'zmp-competitions'),
                'president_user_id' => __('ID на тековниот претседател', 'zmp-competitions'),
                'facsimile_id' => __('ID на факсимилот', 'zmp-competitions'),
                'facsimile_sha256' => __('SHA-256 на факсимилот', 'zmp-competitions'),
                'permitted_use' => __('Дозволена употреба', 'zmp-competitions'),
                'valid_from' => __('Важи од', 'zmp-competitions'),
                'valid_until' => __('Важи до', 'zmp-competitions'),
            );
            foreach ($context_fields as $key => $label) {
                $raw_value = self::governance_value($registration_context, $key, '');
                $value = is_array($raw_value)
                    ? implode(', ', array_map('sanitize_key', $raw_value))
                    : sanitize_text_field((string) $raw_value);
                echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd>';
            }
            echo '</dl>';
        }

        if (is_array($registration_context)
            && is_callable(array('ZMP_President_Facsimile_Certificates', 'register_action_url'))
            && is_callable(array('ZMP_President_Facsimile_Certificates', 'register_nonce_action'))
            && defined('ZMP_President_Facsimile_Certificates::NONCE_FIELD')) {
            echo '<form class="zmp-president-certificate__form" method="post" action="' . esc_url(ZMP_President_Facsimile_Certificates::register_action_url()) . '">';
            echo '<fieldset><legend>' . esc_html__('Регистрација или повторно издавање', 'zmp-competitions') . '</legend>';
            echo '<p class="description">' . esc_html__('Креирајте го манифестот и потпишете ги неговите точни бајти со приватниот клуч што го чувате надвор од серверот. Потоа внесете го манифестот и потписот во Base64; не го менувајте манифестот по потпишувањето.', 'zmp-competitions') . '</p>';
            echo '<p class="description">' . esc_html__('Манифестот е UTF-8 JSON со точно следниве клучеви, во лексикографски ред и без празни места или нов ред: algorithm, facsimile_id, facsimile_sha256, mandate_id, permitted_use, president_user_id, public_key_id, serial, valid_from, valid_until, version. Користете version=1, algorithm=Ed25519 и permitted_use=["certificate_pdf"]. public_key_id е SHA-256 на декодираниот 32-бајтен јавен клуч.', 'zmp-competitions') . '</p>';
            echo '<label for="zmp-president-certificate-public-key">' . esc_html__('Јавен клуч Ed25519 (Base64)', 'zmp-competitions') . '</label>';
            echo '<textarea id="zmp-president-certificate-public-key" name="public_key_b64" rows="3" required autocomplete="off" spellcheck="false" aria-describedby="zmp-president-certificate-public-key-help"></textarea>';
            echo '<span id="zmp-president-certificate-public-key-help" class="description">' . esc_html__('Внесете го јавниот клуч. Приватниот клуч не се внесува и не се испраќа.', 'zmp-competitions') . '</span>';
            echo '<label for="zmp-president-certificate-manifest">' . esc_html__('Потпишан манифест (Base64 на точните бајти)', 'zmp-competitions') . '</label>';
            echo '<textarea id="zmp-president-certificate-manifest" name="signed_manifest_b64" rows="6" required autocomplete="off" spellcheck="false" aria-describedby="zmp-president-certificate-manifest-help"></textarea>';
            echo '<span id="zmp-president-certificate-manifest-help" class="description">' . esc_html__('Base64-кодирајте ги истите UTF-8 манифест-бајти што сте ги потпишале. Манифестот мора да ги користи точните горни вредности за мандатот и факсимилот.', 'zmp-competitions') . '</span>';
            echo '<label for="zmp-president-certificate-signature">' . esc_html__('Потпис на манифестот (Base64)', 'zmp-competitions') . '</label>';
            echo '<textarea id="zmp-president-certificate-signature" name="signature_b64" rows="3" required autocomplete="off" spellcheck="false"></textarea>';
            wp_nonce_field(ZMP_President_Facsimile_Certificates::register_nonce_action(), ZMP_President_Facsimile_Certificates::NONCE_FIELD);
            echo '<button type="submit" class="button button-primary">' . esc_html__('Потпиши и испрати сертификат', 'zmp-competitions') . '</button>';
            echo '</fieldset></form>';
        }
        echo '</section>';
    }

    /** Submit a mandate-bound candidate without activating it. */
    private static function render_pending_facsimile_submission($pending_facsimile) {
        $has_pending = (is_array($pending_facsimile) || is_object($pending_facsimile))
            && !is_wp_error($pending_facsimile)
            && absint(self::governance_value($pending_facsimile, 'id', 0)) > 0;
        if ($has_pending) {
            echo '<h3>' . esc_html__('Предлог-факсимил за тековниот мандат', 'zmp-competitions') . '</h3>';
            echo '<p class="notice notice-info inline" role="status"><strong>' . esc_html__('Неактивен — чека потпишан сертификат и, кога е потребно, потврда од претходниот претседател.', 'zmp-competitions') . '</strong></p>';
            echo '<dl class="zmp-president-certificate__meta">';
            foreach (array(
                'id' => __('ID на предлогот', 'zmp-competitions'),
                'president_name' => __('Име', 'zmp-competitions'),
                'sha256' => __('SHA-256', 'zmp-competitions'),
                'valid_from' => __('Важност од', 'zmp-competitions'),
                'valid_to' => __('Важност до', 'zmp-competitions'),
            ) as $key => $label) {
                $value = sanitize_text_field((string) self::governance_value($pending_facsimile, $key, ''));
                if ($value === '') { continue; }
                echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd>';
            }
            echo '</dl>';
            return;
        }

        if (!class_exists('ZMP_Registration_Requirements')
            || !defined('ZMP_Registration_Requirements::FACSIMILE_SUBMIT_ACTION')) {
            echo '<p class="notice notice-warning inline" role="status">' . esc_html__('Поставувањето факсимил моментално не е достапно.', 'zmp-competitions') . '</p>';
            return;
        }
        $action = constant('ZMP_Registration_Requirements::FACSIMILE_SUBMIT_ACTION');
        echo '<h3>' . esc_html__('Поднесете факсимил за тековниот мандат', 'zmp-competitions') . '</h3>';
        echo '<p>' . esc_html__('Факсимилот ќе остане неактивен додека тековниот претседател не го овласти со потпишан сертификат. Постар факсимил не се избира автоматски.', 'zmp-competitions') . '</p>';
        echo '<form class="zmp-president-certificate__form" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<fieldset><legend>' . esc_html__('Нова верзија на факсимил', 'zmp-competitions') . '</legend>';
        echo '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
        echo '<label for="zmp-president-facsimile-name">' . esc_html__('Име на претседателот', 'zmp-competitions') . '</label>';
        echo '<input id="zmp-president-facsimile-name" class="regular-text" name="president_name" maxlength="190" required autocomplete="name">';
        echo '<label for="zmp-president-facsimile-from">' . esc_html__('Важи од', 'zmp-competitions') . '</label>';
        echo '<input id="zmp-president-facsimile-from" type="date" name="valid_from" required>'; 
        echo '<label for="zmp-president-facsimile-to">' . esc_html__('Важи до (по избор)', 'zmp-competitions') . '</label>';
        echo '<input id="zmp-president-facsimile-to" type="date" name="valid_to">';
        echo '<label for="zmp-president-facsimile-file">' . esc_html__('Слика на факсимил (PNG или JPEG, до 2 MB)', 'zmp-competitions') . '</label>';
        echo '<input id="zmp-president-facsimile-file" type="file" name="facsimile_file" accept="image/png,image/jpeg" required aria-describedby="zmp-president-facsimile-file-help">';
        echo '<span id="zmp-president-facsimile-file-help" class="description">' . esc_html__('Датотеката се чува приватно. Новиот предлог не ги менува архивираните факсимили.', 'zmp-competitions') . '</span>';
        wp_nonce_field($action, 'zmp_president_facsimile_nonce');
        echo '<button type="submit" class="button button-primary">' . esc_html__('Зачувај неактивен предлог', 'zmp-competitions') . '</button>';
        echo '</fieldset></form>';
    }

    /** Read-only, current-mandate election documents and obligation acknowledgment for the President. */
    private static function render_governance_panel() {
        if (!self::is_president_member_user() || !class_exists('ZMP_President_Governance')) { return; }

        $documents = ZMP_President_Governance::president_documents();
        $obligation = ZMP_President_Governance::current_obligation();
        $acknowledgments = ZMP_President_Governance::acknowledgment_history();
        $status = isset($_GET['zmp_president_governance_status'])
            ? sanitize_key(wp_unslash($_GET['zmp_president_governance_status']))
            : '';
        $messages = array(
            'saved' => array('success', 'Промените се зачувани.'),
            'saved_with_audit_warning' => array('warning', 'Исходот на потврдата не може да се потврди. Проверете ја историјата на потврди пред повторно да се обидете.'),
            'acknowledged' => array('success', 'Потврдата за тековната верзија е зачувана.'),
            'invalid' => array('error', 'Податоците не се исправни. Проверете ја формата и објавената верзија.'),
            'forbidden' => array('error', 'Немате дозвола за ова дејство.'),
            'failed' => array('error', 'Дејството не успеа. Обидете се повторно подоцна.'),
        );

        echo '<style>
        .zmp-president-governance{display:grid;gap:16px;margin:22px 0 30px}
        .zmp-president-governance__section{min-width:0;padding:18px;border:1px solid #d9e2ec;border-radius:10px;background:#fff;box-shadow:0 2px 6px #102a430d}
        .zmp-president-governance__section h2{margin:0 0 12px;color:#173b5e;font-size:20px}
        .zmp-president-governance__list{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}
        .zmp-president-governance__card{min-width:0;padding:14px;border:1px solid #e2e8f0;border-radius:8px;background:#f9fbfd;overflow-wrap:anywhere}
        .zmp-president-governance__card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px}
        .zmp-president-governance__card h3{min-width:0;margin:0;font-size:16px;overflow-wrap:anywhere}
        .zmp-president-governance__badge{flex:0 0 auto;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
        .zmp-president-governance__badge.is-active{background:#e7f5ec;color:#176b37}
        .zmp-president-governance__badge.is-archived{background:#edf0f4;color:#4a5563}
        .zmp-president-governance__meta{display:grid;gap:5px;margin:0 0 12px}
        .zmp-president-governance__meta div{display:grid;grid-template-columns:minmax(100px,.4fr) minmax(0,1fr);gap:8px}
        .zmp-president-governance__meta dt{color:#526477;font-weight:600}.zmp-president-governance__meta dd{margin:0;overflow-wrap:anywhere}
        .zmp-president-governance__text{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.6;padding:14px;border:1px solid #e2e8f0;border-radius:8px;background:#f9fbfd}
        .zmp-president-governance__ack{margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0}
        .zmp-president-governance__ack label{display:flex;align-items:flex-start;gap:8px;margin:0 0 12px}
        .zmp-president-governance__ack input[type=checkbox]{margin-top:3px;flex:0 0 auto}
        .zmp-president-governance__history{margin-top:16px}
        .zmp-president-governance__history li{margin:6px 0;overflow-wrap:anywhere}
        .zmp-president-governance a:focus-visible,.zmp-president-governance button:focus-visible,.zmp-president-governance input:focus-visible{outline:3px solid #1685ee;outline-offset:2px}
        @media(max-width:600px){.zmp-president-governance__section{padding:14px}.zmp-president-governance__list{grid-template-columns:minmax(0,1fr)}.zmp-president-governance__meta div{grid-template-columns:1fr;gap:2px}.zmp-president-governance__card-heading{flex-direction:column;gap:6px}}
        </style>';

        echo '<div class="zmp-president-governance">';
        if (isset($messages[$status])) {
            echo '<div class="notice notice-' . esc_attr($messages[$status][0]) . '" role="status" aria-live="polite"><p>' . esc_html($messages[$status][1]) . '</p></div>';
        }

        echo '<section class="zmp-president-governance__section" aria-labelledby="zmp-president-documents-heading"><h2 id="zmp-president-documents-heading">' . esc_html__('Изборни документи и архива', 'zmp-competitions') . '</h2>';
        if (is_wp_error($documents)) {
            echo '<p class="notice notice-error inline" role="alert">Документите моментално не може да се вчитаат.</p>';
        } elseif (empty($documents)) {
            echo '<p class="description" role="status">' . esc_html__('Нема документи од тековниот или претходните претседателски мандати.', 'zmp-competitions') . '</p>';
        } else {
            echo '<div class="zmp-president-governance__list">';
            foreach ((array) $documents as $document) {
                $document_id = absint(self::governance_value($document, 'id', 0));
                $name = sanitize_text_field((string) self::governance_value($document, 'original_name', 'Документ'));
                $created_at = sanitize_text_field((string) self::governance_value($document, 'created_at', ''));
                $mime_type = sanitize_mime_type((string) self::governance_value($document, 'mime_type', 'application/pdf'));
                $file_size = absint(self::governance_value($document, 'file_size', 0));
                $document_status = sanitize_key((string) self::governance_value($document, 'status', 'active'));
                $archived_at = sanitize_text_field((string) self::governance_value($document, 'archived_at', ''));
                $mandate_id = absint(self::governance_value($document, 'mandate_id', 0));
                $is_archived = $document_status === 'archived';
                echo '<article class="zmp-president-governance__card">';
                echo '<div class="zmp-president-governance__card-heading"><h3>' . esc_html($name) . '</h3><span class="zmp-president-governance__badge' . ($is_archived ? ' is-archived' : ' is-active') . '">' . esc_html($is_archived ? __('Архивиран', 'zmp-competitions') : __('Активен', 'zmp-competitions')) . '</span></div><dl class="zmp-president-governance__meta">';
                if ($mandate_id) { echo '<div><dt>' . esc_html__('Мандат', 'zmp-competitions') . '</dt><dd>#' . esc_html($mandate_id) . '</dd></div>'; }
                echo '<div><dt>' . esc_html__('Тип', 'zmp-competitions') . '</dt><dd>' . esc_html($mime_type) . '</dd></div>';
                if ($file_size) { echo '<div><dt>' . esc_html__('Големина', 'zmp-competitions') . '</dt><dd>' . esc_html(size_format($file_size)) . '</dd></div>'; }
                if ($created_at !== '') { echo '<div><dt>' . esc_html__('Додаден', 'zmp-competitions') . '</dt><dd>' . esc_html($created_at) . '</dd></div>'; }
                if ($is_archived && $archived_at !== '') { echo '<div><dt>' . esc_html__('Архивиран', 'zmp-competitions') . '</dt><dd>' . esc_html($archived_at) . '</dd></div>'; }
                echo '</dl>';
                if ($document_id && is_callable(array('ZMP_President_Governance', 'form_action_url')) && is_callable(array('ZMP_President_Governance', 'nonce_action'))) {
                    $download_action = 'download';
                    echo '<form method="get" action="' . esc_url(ZMP_President_Governance::form_action_url($download_action)) . '">';
                    echo '<input type="hidden" name="action" value="' . esc_attr('zmp_president_governance_' . $download_action) . '">';
                    echo '<input type="hidden" name="document_id" value="' . esc_attr($document_id) . '">';
                    wp_nonce_field(ZMP_President_Governance::download_nonce_action($document_id), '_zmp_president_governance_nonce');
                    echo '<button type="submit" class="button">Преземи документ</button></form>';
                }
                echo '</article>';
            }
            echo '</div>';
        }
        echo '</section>';

        echo '<section class="zmp-president-governance__section" aria-labelledby="zmp-president-obligations-heading"><h2 id="zmp-president-obligations-heading">Обврски на функцијата</h2>';
        if (is_wp_error($obligation)) {
            echo '<p class="notice notice-error inline" role="alert">Тековниот текст не може да се вчита.</p>';
        } elseif (!$obligation) {
            echo '<p class="description" role="status">Сè уште нема објавена одобрена верзија на текстот.</p>';
        } else {
            $obligation_id = absint(self::governance_value($obligation, 'id', 0));
            $version_label = sanitize_text_field((string) self::governance_value($obligation, 'version_label', ''));
            $content_hash = strtolower(sanitize_text_field((string) self::governance_value($obligation, 'content_sha256', self::governance_value($obligation, 'text_sha256', ''))));
            $text = (string) self::governance_value($obligation, 'text', '');
            $board_reference = sanitize_text_field((string) self::governance_value($obligation, 'board_reference', ''));
            $legal_reference = sanitize_text_field((string) self::governance_value($obligation, 'legal_reference', ''));
            $created_at = sanitize_text_field((string) self::governance_value($obligation, 'created_at', ''));
            echo '<article class="zmp-president-governance__card"><h3>Верзија ' . esc_html($version_label) . '</h3><dl class="zmp-president-governance__meta">';
            echo '<div><dt>Одлука на Управниот одбор</dt><dd>' . esc_html($board_reference) . '</dd></div>';
            echo '<div><dt>Правен преглед</dt><dd>' . esc_html($legal_reference) . '</dd></div>';
            if ($created_at !== '') { echo '<div><dt>Објавено</dt><dd>' . esc_html($created_at) . '</dd></div>'; }
            echo '</dl><div class="zmp-president-governance__text">' . esc_html($text) . '</div>';

            $acknowledged = false;
            if (!is_wp_error($acknowledgments) && is_array($acknowledgments)) {
                foreach ($acknowledgments as $acknowledgment) {
                    $ack_id = absint(self::governance_value($acknowledgment, 'obligation_version_id', 0));
                    $ack_hash = strtolower(sanitize_text_field((string) self::governance_value($acknowledgment, 'obligation_sha256', self::governance_value($acknowledgment, 'content_sha256', ''))));
                    if ($ack_id === $obligation_id && $content_hash !== '' && hash_equals($content_hash, $ack_hash)) { $acknowledged = true; break; }
                }
            }
            $valid_obligation_version = $obligation_id > 0 && (bool) preg_match('/^[a-f0-9]{64}$/', $content_hash);
            if (is_wp_error($acknowledgments)) {
                // The history error below explains why confirmation is disabled.
            } elseif (!$valid_obligation_version) {
                echo '<p class="notice notice-error inline" role="alert">Оваа верзија не може безбедно да се потврди. Обратете се кај администраторот.</p>';
            } elseif ($acknowledged) {
                echo '<p class="notice notice-success inline" role="status">Потврдивте дека сте запознаени со оваа верзија.</p>';
            } else {
                $ack_action = 'acknowledge';
                echo '<form class="zmp-president-governance__ack" method="post" action="' . esc_url(ZMP_President_Governance::form_action_url($ack_action)) . '">';
                wp_nonce_field(ZMP_President_Governance::nonce_action($ack_action), '_zmp_president_governance_nonce');
                echo '<input type="hidden" name="obligation_version_id" value="' . esc_attr($obligation_id) . '">';
                echo '<input type="hidden" name="content_sha256" value="' . esc_attr($content_hash) . '">';
                echo '<label><input type="checkbox" name="confirm_obligation" value="1" required> <span>Потврдувам дека го прочитав и се запознав со прикажаниот текст.</span></label>';
                echo '<button type="submit" class="button button-primary">Потврди запознавање</button></form>';
            }
        }

        if (is_wp_error($acknowledgments)) {
            echo '<div class="zmp-president-governance__history"><p class="notice notice-error inline" role="alert">Историјата на потврди не може да се вчита.</p></div>';
        } elseif (!empty($acknowledgments)) {
            echo '<div class="zmp-president-governance__history"><h3>Историја на потврди</h3><ul>';
            foreach ((array) $acknowledgments as $acknowledgment) {
                $ack_version = sanitize_text_field((string) self::governance_value($acknowledgment, 'version_label', ''));
                $ack_date = sanitize_text_field((string) self::governance_value($acknowledgment, 'acknowledged_at', ''));
                echo '<li>Верзија ' . esc_html($ack_version) . ' — ' . esc_html($ack_date) . '</li>';
            }
            echo '</ul></div>';
        } else {
            echo '<div class="zmp-president-governance__history"><h3>Историја на потврди</h3><p class="description">Нема претходни потврди.</p></div>';
        }
        echo '</section></div>';
    }

    private static function snapshot() {
        global $wpdb;
        $seasons_table = $wpdb->prefix . 'zmp_seasons';
        $competitions_table = $wpdb->prefix . 'zmp_competitions';
        $years = $wpdb->get_col("SELECT academic_year FROM $seasons_table UNION SELECT academic_year FROM $competitions_table ORDER BY academic_year DESC");
        if ($wpdb->last_error) { return array('error' => true); }
        $years = array_values(array_unique(array_filter(array_map('strval', (array)$years))));
        if (!$years) { return array('years' => array(), 'rows' => array(), 'totals' => array('entries'=>0,'docs_ready'=>0,'docs_waiting'=>0,'scored'=>0,'confirmed'=>0)); }

        $requested = isset($_GET['zmp_president_year']) ? sanitize_text_field(wp_unslash($_GET['zmp_president_year'])) : '';
        $year = in_array($requested, $years, true) ? $requested : $years[0];
        $rows = array();
        $totals = array('entries'=>0,'docs_ready'=>0,'docs_waiting'=>0,'scored'=>0,'confirmed'=>0);

        $modern = $wpdb->get_results($wpdb->prepare(
            "SELECT c.name_mk AS category_name,e.region,g.name_mk AS group_name,g.education_level,
                COUNT(DISTINCT e.id) AS entry_count,
                COUNT(DISTINCT CASE WHEN COALESCE(d.doc_count,0)>0 AND d.doc_count=d.approved_count
                    AND COALESCE(d.has_signed_application,0)=1 AND COALESCE(d.has_payment_proof,0)=1 AND COALESCE(d.has_media_consent,0)=1
                    AND (e.participant_type<>'group' OR COALESCE(d.has_participant_roster,0)=1) THEN e.id END) AS docs_ready,
                COUNT(DISTINCT e.id)-COUNT(DISTINCT CASE WHEN COALESCE(d.doc_count,0)>0 AND d.doc_count=d.approved_count
                    AND COALESCE(d.has_signed_application,0)=1 AND COALESCE(d.has_payment_proof,0)=1 AND COALESCE(d.has_media_consent,0)=1
                    AND (e.participant_type<>'group' OR COALESCE(d.has_participant_roster,0)=1) THEN e.id END) AS docs_waiting,
                COUNT(DISTINCT CASE WHEN COALESCE(a.scored,0)>0 THEN e.id END) AS scored_count,
                COUNT(DISTINCT CASE WHEN COALESCE(f.school_count,0)>0 THEN e.id END) AS school_count,
                COUNT(DISTINCT CASE WHEN COALESCE(f.municipal_count,0)>0 THEN e.id END) AS municipal_count,
                COUNT(DISTINCT CASE WHEN COALESCE(f.regional_count,0)>0 THEN e.id END) AS regional_count,
                COUNT(DISTINCT CASE WHEN COALESCE(f.state_count,0)>0 THEN e.id END) AS state_count,
                COUNT(DISTINCT CASE WHEN COALESCE(f.confirmed_count,0)>0 THEN e.id END) AS confirmed_count
             FROM {$wpdb->prefix}zmp_category_entries e
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id
             JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id
             JOIN {$wpdb->prefix}zmp_seasons s ON s.id=e.season_id
             LEFT JOIN (
                SELECT doc.entry_id,COUNT(doc.id) AS doc_count,
                    SUM(CASE WHEN review.status='approved' THEN 1 ELSE 0 END) AS approved_count,
                    MAX(CASE WHEN doc.document_type='signed_application' THEN 1 ELSE 0 END) AS has_signed_application,
                    MAX(CASE WHEN doc.document_type='payment_proof' THEN 1 ELSE 0 END) AS has_payment_proof,
                    MAX(CASE WHEN doc.document_type='media_consent' THEN 1 ELSE 0 END) AS has_media_consent,
                    MAX(CASE WHEN doc.document_type='participant_roster' THEN 1 ELSE 0 END) AS has_participant_roster
                FROM {$wpdb->prefix}zmp_entry_documents doc
                JOIN {$wpdb->prefix}zmp_category_entries scoped_doc_entry ON scoped_doc_entry.id=doc.entry_id
                JOIN {$wpdb->prefix}zmp_seasons scoped_doc_season ON scoped_doc_season.id=scoped_doc_entry.season_id AND scoped_doc_season.academic_year=%s
                LEFT JOIN {$wpdb->prefix}zmp_entry_document_reviews review ON review.document_id=doc.id
                GROUP BY doc.entry_id
             ) d ON d.entry_id=e.id
             LEFT JOIN (
                SELECT a.entry_id,COUNT(DISTINCT a.phase) AS scored
                FROM {$wpdb->prefix}zmp_jury_assessments a
                JOIN {$wpdb->prefix}zmp_seasons scoped_assessment_season ON scoped_assessment_season.id=a.season_id AND scoped_assessment_season.academic_year=%s
                WHERE a.status='submitted' GROUP BY a.entry_id
             ) a ON a.entry_id=e.id
             LEFT JOIN (
                SELECT f.entry_id,
                    COUNT(DISTINCT CASE WHEN f.phase='school' AND f.status='confirmed' THEN f.phase END) AS school_count,
                    COUNT(DISTINCT CASE WHEN f.phase='municipal' AND f.status='confirmed' THEN f.phase END) AS municipal_count,
                    COUNT(DISTINCT CASE WHEN f.phase='regional' AND f.status='confirmed' THEN f.phase END) AS regional_count,
                    COUNT(DISTINCT CASE WHEN f.phase='state' AND f.status='confirmed' THEN f.phase END) AS state_count,
                    COUNT(DISTINCT CASE WHEN f.status='confirmed' THEN f.phase END) AS confirmed_count
                FROM {$wpdb->prefix}zmp_category_final_results f
                JOIN {$wpdb->prefix}zmp_seasons scoped_final_season ON scoped_final_season.id=f.season_id AND scoped_final_season.academic_year=%s
                GROUP BY f.entry_id
             ) f ON f.entry_id=e.id
             WHERE s.academic_year=%s AND e.status IN ('submitted','under_review','needs_correction','approved','complete')
             GROUP BY c.id,c.name_mk,e.region,g.id,g.name_mk,g.education_level
             ORDER BY c.name_mk,e.region,g.sort_order,g.name_mk",
            $year,
            $year,
            $year,
            $year
        ));
        if ($wpdb->last_error) { return array('error' => true); }
        foreach ((array)$modern as $item) {
            $entry_count = (int)$item->entry_count;
            $region = class_exists('ZMP_Regions') ? ZMP_Regions::label((string)$item->region) : (string)$item->region;
            $row = array(
                'category'=>(string)$item->category_name,'region'=>$region,'group'=>(string)$item->group_name,
                'entries'=>$entry_count,'docs_ready'=>(int)$item->docs_ready,'docs_waiting'=>(int)$item->docs_waiting,
                'scored'=>(int)$item->scored_count,'school'=>(int)$item->school_count,'municipal'=>(int)$item->municipal_count,
                'regional'=>(int)$item->regional_count,'state'=>(int)$item->state_count,'confirmed'=>(int)$item->confirmed_count,
            );
            $rows[] = $row;
            foreach (array('entries'=>'entries','docs_ready'=>'docs_ready','docs_waiting'=>'docs_waiting','scored'=>'scored','confirmed'=>'confirmed') as $key=>$source) { $totals[$key] += $row[$source]; }
        }

        $legacy = $wpdb->get_results($wpdb->prepare(
            "SELECT s.region,s.grade,COUNT(DISTINCT s.id) AS entry_count,
                COUNT(DISTINCT CASE WHEN r.phase='school' THEN s.id END) AS school_count,
                COUNT(DISTINCT CASE WHEN r.phase='municipal' THEN s.id END) AS municipal_count,
                COUNT(DISTINCT CASE WHEN r.phase='regional' THEN s.id END) AS regional_count,
                COUNT(DISTINCT CASE WHEN r.phase='state' THEN s.id END) AS state_count,
                COUNT(DISTINCT CASE WHEN r.student_id IS NOT NULL THEN s.id END) AS scored_count
             FROM {$wpdb->prefix}zmp_students s
             JOIN {$wpdb->prefix}zmp_competitions c ON c.id=s.competition_id
             LEFT JOIN {$wpdb->prefix}zmp_phase_results r ON r.student_id=s.id AND r.academic_year=c.academic_year
             WHERE c.academic_year=%s
             GROUP BY s.region,s.grade ORDER BY s.region,s.grade",
            $year
        ));
        if ($wpdb->last_error) { return array('error' => true); }
        foreach ((array)$legacy as $item) {
            $grade = (int)$item->grade;
            $group = ($grade >= 6 && $grade <= 9) ? 'Основно '.$grade.' одделение' : 'Средно '.$grade.' година';
            $region = class_exists('ZMP_Regions') ? ZMP_Regions::label((string)$item->region) : (string)$item->region;
            $row = array(
                'category'=>'Тест на знаење','region'=>$region,'group'=>$group,'entries'=>(int)$item->entry_count,
                'docs_ready'=>0,'docs_waiting'=>0,'scored'=>(int)$item->scored_count,
                'school'=>(int)$item->school_count,'municipal'=>(int)$item->municipal_count,
                'regional'=>(int)$item->regional_count,'state'=>(int)$item->state_count,
                'confirmed'=>(int)$item->scored_count,
            );
            $rows[] = $row;
            foreach (array('entries'=>'entries','scored'=>'scored','confirmed'=>'confirmed') as $key=>$source) { $totals[$key] += $row[$source]; }
        }

        usort($rows, static function($a,$b) {
            return array($a['category'],$a['region'],$a['group']) <=> array($b['category'],$b['region'],$b['group']);
        });
        return array('error'=>false,'years'=>$years,'year'=>$year,'rows'=>$rows,'totals'=>$totals);
    }
}

