<?php
if (!defined('ABSPATH')) { exit; }

/** Register public portal shortcodes and process commission decisions. */
class ZMP_Shortcodes {
    public static function init() {
        $support = ZMP_Support::get_instance();
        add_shortcode('zmp_natprevari_portal', array(__CLASS__, 'render_natprevari_portal'));
        add_shortcode('zmp_testator_portal', array($support, 'render_testator_portal'));
        add_shortcode('zmp_moderator_portal', array(__CLASS__, 'render_moderator_portal'));
        add_shortcode('zmp_commission_portal', array(__CLASS__, 'render_commission_portal'));
        add_shortcode('zmp_organizer_portal', array('ZMP_Committees_Organizers', 'render_organizer_portal'));
        add_shortcode('zmp_teacher_register', array('ZMP_Mentor', 'render_teacher_register_shortcode'));
        add_action('template_redirect', array(__CLASS__, 'process_commission_decision'));
        add_action('template_redirect', array(__CLASS__, 'process_commission_message'));
        add_action('wp_ajax_zmp_commission_notifications', array(__CLASS__, 'ajax_commission_notifications'));
    }

    /**
     * Render the public competition landing page and role-aware portal navigation.
     *
     * @return string
     */
    public static function render_natprevari_portal() {
        global $wpdb;

        $user_id  = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $active_competition = (bool) $wpdb->get_var(
            "SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active = 1 ORDER BY id DESC LIMIT 1"
        );

        // Portal navigation follows the granted capability/role. A missing profile
        // must not hide the tab; the destination portal can request profile setup.
        $user_roles = is_user_logged_in() ? (array) wp_get_current_user()->roles : array();
        $is_teacher = $is_admin || current_user_can('zmp_teacher_cap')
            || current_user_can('view_zmp_teacher_dashboard') || in_array('zmp_teacher', $user_roles, true);
        $is_testator = $is_admin || current_user_can('zmp_testator_cap')
            || current_user_can('view_zmp_testator_dashboard') || in_array('zmp_testator', $user_roles, true);
        $is_moderator = $is_admin || current_user_can('zmp_moderator_cap')
            || current_user_can('view_zmp_moderator_dashboard') || in_array('zmp_moderator', $user_roles, true);
        $is_commission = $is_admin || current_user_can('zmp_commission_cap')
            || current_user_can('view_zmp_commission_dashboard') || in_array('zmp_commission', $user_roles, true);
        $is_organizer = $is_admin || current_user_can('zmp_organizer_cap')
            || current_user_can('view_zmp_organizer_dashboard') || in_array('zmp_organizer', $user_roles, true);

        $tabs = array(
            'main' => array('label' => 'Почетна', 'shortcode' => ''),
            'testiranje' => array('label' => 'Тестирање', 'shortcode' => 'zmp_student_exam'),
        );
        if ($is_teacher) {
            $tabs['nastavnici'] = array('label' => 'Наставници', 'shortcode' => 'zmp_mentor_portal');
        }
        if (is_user_logged_in() && class_exists('ZMP_Teacher_Register') && ZMP_Teacher_Register::can_view_current_user()) {
            $tabs['teacher-register'] = array('label' => 'Регистар на наставници', 'shortcode' => 'zmp_teacher_register');
        }
        if ($is_testator) {
            $tabs['testatori'] = array('label' => 'Тестатори', 'shortcode' => 'zmp_testator_portal');
        }
        if ($is_moderator || $is_admin) {
            $tabs['moderatori'] = array('label' => 'Модератори', 'shortcode' => 'zmp_moderator_portal');
        }
        if ($is_commission) {
            $tabs['komisija'] = array('label' => 'Комисија', 'shortcode' => 'zmp_commission_portal');
        }
        if ($is_organizer) {
            $tabs['organizatori'] = array('label' => 'Организатори', 'shortcode' => 'zmp_organizer_portal');
        }
        if ($is_teacher || $is_testator || $is_commission || $is_admin) {
            $tabs['rezultati'] = array('label' => 'Резултати', 'shortcode' => 'zmp_public_results');
        }

        $requested_tab = isset($_GET['zmp_portal_tab'])
            ? sanitize_key(wp_unslash($_GET['zmp_portal_tab']))
            : 'main';
        // Keep links created before the homepage subtabs were introduced working.
        $legacy_home_tab = '';
        if ($requested_tab === 'izvestuvanja' || $requested_tab === 'konkursi') {
            $legacy_home_tab = $requested_tab;
            $requested_tab = 'main';
        }
        if (!isset($tabs[$requested_tab])) {
            $requested_tab = 'main';
        }
        $home_tabs = array('main' => 'Почетна', 'izvestuvanja' => 'Известувања', 'konkursi' => 'Конкурси');
        $requested_home_tab = isset($_GET['zmp_home_tab']) && is_scalar($_GET['zmp_home_tab'])
            ? sanitize_key(wp_unslash($_GET['zmp_home_tab']))
            : ($legacy_home_tab ?: 'main');
        if (!isset($home_tabs[$requested_home_tab])) {
            $requested_home_tab = 'main';
        }

        $page_url = get_permalink();
        if (!$page_url) {
            $page_url = home_url('/');
        }

        ob_start();
        ?>
        <style>
            .zmp-natprevari-portal{max-width:1280px;margin:0 auto 24px}
            .zmp-natprevari-portal__nav{display:flex;gap:8px;overflow-x:auto;overscroll-behavior-inline:contain;padding:10px 4px 14px;margin:0 0 20px;border-bottom:1px solid #dbe4ef;scrollbar-width:thin}
            .zmp-natprevari-portal__tab{position:relative;display:inline-flex;align-items:center;gap:8px;flex:0 0 auto;min-height:42px;padding:10px 15px;border:1px solid #d8e1ec;border-radius:8px 8px 0 0;background:#f5f8fc;color:#18324f;font:inherit;font-weight:650;text-decoration:none}
            .zmp-natprevari-portal__tab:hover,.zmp-natprevari-portal__tab:focus-visible{border-color:#1674d1;outline:2px solid #93c5fd;outline-offset:1px}
            .zmp-natprevari-portal__tab[aria-current="page"]{color:#fff;background:#174a7e;border-color:#174a7e;box-shadow:0 3px 0 #0b6acb}
            .zmp-natprevari-portal__subnav{display:flex;gap:8px;overflow-x:auto;padding:4px 4px 12px;margin:0 0 18px;border-bottom:1px solid #dbe4ef}
            .zmp-natprevari-portal__subtab{display:inline-flex;align-items:center;flex:0 0 auto;min-height:38px;padding:8px 14px;border:1px solid #cbd9e8;border-radius:999px;background:#edf4fb;color:#174a7e;font:inherit;font-weight:650;text-decoration:none}
            .zmp-natprevari-portal__subtab:hover,.zmp-natprevari-portal__subtab:focus-visible{border-color:#1674d1;outline:2px solid #93c5fd;outline-offset:1px}
            .zmp-natprevari-portal__subtab[aria-current="page"]{color:#fff;background:#174a7e;border-color:#174a7e;box-shadow:0 2px 0 #0b6acb}
            .zmp-natprevari-portal__welcome{padding:24px 20px;text-align:center;background:#f5f8fc;border-radius:10px;margin-bottom:18px}
            .zmp-natprevari-portal__welcome h1{margin-top:0}
            .zmp-natprevari-portal__badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:#1685ee;color:#fff;font-size:12px;font-weight:800}
            @media(max-width:640px){.zmp-natprevari-portal__nav,.zmp-natprevari-portal__subnav{margin-inline:-8px;padding-inline:8px}.zmp-natprevari-portal__tab{min-height:44px;padding:10px 12px}.zmp-natprevari-portal__subnav{flex-wrap:wrap;overflow-x:visible}.zmp-natprevari-portal__subtab{flex:0 1 auto;max-width:100%;min-height:44px;white-space:normal;overflow-wrap:anywhere}}
        </style>
        <div class="zmp-natprevari-portal">
            <?php $portal_url = remove_query_arg('zmp_home_tab', $page_url); ?>
            <nav class="zmp-natprevari-portal__nav" aria-label="Главно мени">
                <?php foreach ($tabs as $tab_key => $tab) : ?>
                    <a class="zmp-natprevari-portal__tab" href="<?php echo esc_url(add_query_arg('zmp_portal_tab', $tab_key, $portal_url)); ?>" <?php echo $requested_tab === $tab_key ? 'aria-current="page"' : ''; ?>><?php echo esc_html($tab['label']); ?></a>
                <?php endforeach; ?>
            </nav>
            <main class="zmp-natprevari-portal__content">
                <?php if ($requested_tab === 'main') : ?>
                    <nav class="zmp-natprevari-portal__subnav" aria-label="Подмени на почетната страница">
                        <?php foreach ($home_tabs as $home_key => $home_label) : ?>
                            <?php $home_url = add_query_arg('zmp_portal_tab', 'main', $page_url); $home_url = add_query_arg('zmp_home_tab', $home_key, $home_url); ?>
                            <a class="zmp-natprevari-portal__subtab" href="<?php echo esc_url($home_url); ?>" <?php echo $requested_home_tab === $home_key ? 'aria-current="page"' : ''; ?>><?php echo esc_html($home_label); ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php if ($requested_home_tab === 'main') : ?>
                        <section class="zmp-natprevari-portal__welcome">
                            <h1>ДРЖАВНИ НАТПРЕВАРИ</h1>
                            <p><?php esc_html_e('Добредојдовте на порталот за државните натпревари.', 'zmp-competitions'); ?></p>
                        </section>
                        <?php echo ZMP_President_Public_Display::render_home_block(); ?>
                        <?php echo ZMP_Communications::render_public_announcements(); ?>
                    <?php elseif ($requested_home_tab === 'izvestuvanja') : ?>
                        <?php echo ZMP_Communications::render_public_wordpress_category('Известувања'); ?>
                    <?php else : ?>
                        <?php echo ZMP_Communications::render_public_wordpress_category('Конкурси'); ?>
                    <?php endif; ?>
                <?php else : ?>
                    <?php echo do_shortcode('[' . $tabs[$requested_tab]['shortcode'] . ']'); // Shortcode comes from the fixed allow-list above. ?>
                <?php endif; ?>
            </main>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Render the moderator operations panel and its post-completion archive workflow. */
    public static function render_moderator_portal($atts) {
        $user = wp_get_current_user();
        $is_moderator = current_user_can('zmp_moderator_cap') || current_user_can('view_zmp_moderator_dashboard')
            || current_user_can('manage_options') || in_array('zmp_moderator', (array) $user->roles, true);
        if (!$is_moderator) { return ZMP_Support::get_instance()->render_moderator_portal($atts); }

        $content = ZMP_Support::get_instance()->render_moderator_portal($atts);
        $messenger_active = isset($_GET['zmp_moderator_tab']) && is_scalar($_GET['zmp_moderator_tab']) && sanitize_key(wp_unslash($_GET['zmp_moderator_tab'])) === 'messenger';
        $panels = array(
            'overview' => ZMP_Moderator_Operations::render_panel(),
            'delivery' => ZMP_Moderator_Operations::render_delivery_panel(),
            'applications' => $content,
            'rules' => ZMP_Registration_Windows::render_moderator_panel() . ZMP_Category_Jury::render_moderator_criteria_panel() . ZMP_YouTube::render_regional_jury_access_panel(),
            'fees' => ZMP_Registration_Requirements::render_moderator_panel() . ZMP_Certificate_Templates::render_panel(),
            'members' => ZMP_Committees_Organizers::render_moderator_panel(),
            'schools' => ZMP_Circular_Invitations::render_moderator_panel() . ZMP_Communications::render_moderator_panel(),
            'messages' => ZMP_Region_Requests::render_moderator_panel() . ZMP_Category_Jury::render_moderator_review_panel(),
            'messenger' => ZMP_Role_Messenger::render('moderator', $messenger_active),
            'stages' => ZMP_Governance::render_moderator_phase_controls_static(),
            'archive' => ZMP_Governance::render_moderator_archive_controls_static(),
        );
        global $wpdb;
        $moderator_id = get_current_user_id();
        $pending_messages = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_internal_messages WHERE recipient_id=%d AND status='unread'", $moderator_id));
        $pending_region_requests = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_region_change_requests WHERE status='pending'");
        $pending_jury_corrections = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE status='pending'");
        $tabs = array(
            'overview' => 'Преглед', 'applications' => 'Пријави и тест на знаење',
            'delivery' => 'Начин на одржување',
            'rules' => 'Категории и рокови', 'fees' => 'Котизации и документи',
            'members' => 'Комисии и организатори', 'schools' => 'Училишта и циркулари',
            'messages' => array('label'=>'Дописи и барања','count'=>$pending_messages+$pending_region_requests+$pending_jury_corrections),
            'stages' => 'Натпреварувачки нивоа', 'archive' => 'Архива',
        );
        $messenger_notice = '';
        if (ZMP_Role_Messenger::can_access_current_role_room('moderator')) {
            $messenger_tab = array('label'=>'Месинџер','count'=>ZMP_Role_Messenger::unread_count('moderator'));
            $tabs = array_slice($tabs, 0, 1, true) + array('messenger' => $messenger_tab) + array_slice($tabs, 1, null, true);
        } else { unset($panels['messenger']); $messenger_notice = ZMP_Role_Messenger::unavailable_notice('moderator'); }
        return self::render_workspace('moderator', 'zmp_moderator_tab', $tabs, $panels, 'overview', $messenger_notice);
    }

    public static function render_commission_portal($atts) {
        if (!current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard') && !current_user_can('manage_options')) {
            return '<div class="zmp-container"><div class="zmp-card">Немате привилегии.</div></div>';
        }
        $jury_only = class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror();
        global $wpdb;
        $user_id = get_current_user_id();
        $messages_table = $wpdb->prefix . 'zmp_internal_messages';
        $complaints_table = $wpdb->prefix . 'zmp_complaints';
        $decisions_table = $wpdb->prefix . 'zmp_commission_decisions';
        $unread_messages = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE recipient_id=%d AND status='unread'",
            $user_id
        ));
        $pending_complaints = $jury_only ? 0 : (int) $wpdb->get_var("SELECT COUNT(*) FROM $complaints_table WHERE status IN ('pending','forwarded')");
        $pending_decisions = $jury_only ? 0 : (int) $wpdb->get_var("SELECT COUNT(*) FROM $decisions_table WHERE status='pending' AND decision_type <> 'complaint'");
        $communication_count = $unread_messages + $pending_complaints + $pending_decisions;
        $requested_tab = isset($_GET['zmp_commission_tab']) ? sanitize_key(wp_unslash($_GET['zmp_commission_tab'])) : 'progress';
        $has_messenger_room = ZMP_Role_Messenger::has_current_commission_room();
        $allowed_tabs = array('progress','communication','questions','jury','arbitration','discipline','records');
        if ($has_messenger_room) { $allowed_tabs[] = 'messenger'; }
        if (!in_array($requested_tab, $allowed_tabs, true)) { $requested_tab = 'progress'; }

        $tabs = array(
            'progress' => 'Тековен процес',
            'communication' => 'Комуникација',
            'questions' => 'Банка на прашања',
            'jury' => 'Жири и оценување',
            'arbitration' => 'Жалби и одлуки',
            'discipline' => 'Дисквалификации',
            'records' => 'Регистар и состав',
        );
        if ($has_messenger_room) {
            $messenger_tab = 'Месинџер' . (ZMP_Role_Messenger::unread_count('commission') ? ' (' . ZMP_Role_Messenger::unread_count('commission') . ')' : '');
            $tabs = array_slice($tabs, 0, 1, true) + array('messenger' => $messenger_tab) + array_slice($tabs, 1, null, true);
        }
        if ($jury_only) {
            $jury_tabs = array('progress','communication','jury');
            if ($has_messenger_room) { $jury_tabs[] = 'messenger'; }
            $tabs = array_intersect_key($tabs, array_flip($jury_tabs));
            if (!isset($tabs[$requested_tab])) { $requested_tab = 'progress'; }
        }

        $badge = '<span class="zmp-commission-tab__badge" aria-label="' . esc_attr(sprintf(_n('%d непрочитана или нерешена ставка', '%d непрочитани или нерешени ставки', $communication_count, 'zmp-competitions'), $communication_count)) . '"' . ($communication_count === 0 ? ' hidden' : '') . '>' . esc_html(number_format_i18n($communication_count)) . '</span>';

        ob_start(); ?>
        <div class="zmp-commission-dashboard" data-zmp-commission-dashboard>
            <nav class="zmp-commission-tabs" aria-label="Мени на Испитната комисија">
                <?php foreach ($tabs as $key => $label): ?>
                    <a href="<?php $commission_url = get_permalink() ?: home_url('/'); if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) { $commission_url = add_query_arg('zmp_portal_tab', sanitize_key(wp_unslash($_GET['zmp_portal_tab'])), $commission_url); } echo esc_url(add_query_arg('zmp_commission_tab', $key, $commission_url)); ?>" class="zmp-commission-tab" id="zmp-commission-tab-<?php echo esc_attr($key); ?>" <?php echo $key === $requested_tab ? 'aria-current="page"' : ''; ?> data-commission-tab="<?php echo esc_attr($key); ?>" <?php echo $key === 'communication' ? 'data-commission-notifications="1" data-notification-count="' . esc_attr($communication_count) . '"' : ''; ?>>
                        <?php echo esc_html($label); ?><?php if ($key === 'communication') { echo $badge; } // Escaped badge HTML is generated above from an integer. ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if (!$has_messenger_room) { echo ZMP_Role_Messenger::unavailable_notice('commission'); } ?>
            <div class="zmp-commission-content">
                <?php if ($jury_only) : ?><section class="zmp-card" data-commission-panel="progress" <?php echo $requested_tab === 'progress' ? '' : 'hidden'; ?>><h3>Тековен процес</h3><p>Вашата работна табла ги прикажува само категориите и натпреварувачки нивоате што ви се доделени. За пријави, оценување и конечни одлуки отворете „Жири и оценување“.</p></section><?php endif; ?>
                <?php echo self::set_commission_panel_visibility(self::render_commission_communications(), $requested_tab); ?>
                <?php if ($has_messenger_room) { echo self::set_commission_panel_visibility('<section data-commission-panel="messenger">' . ZMP_Role_Messenger::render('commission', $requested_tab === 'messenger') . '</section>', $requested_tab); } ?>
                <?php echo self::set_commission_panel_visibility(ZMP_Category_Jury::render_panel(), $requested_tab); ?>
                <?php echo self::set_commission_panel_visibility(self::render_pending_decisions($atts), $requested_tab); ?>
                <?php if (!$jury_only) : ?>
                    <?php echo self::set_commission_panel_visibility(ZMP_Governance::render_commission_panels_static(), $requested_tab); ?>
                    <?php echo self::set_commission_panel_visibility(ZMP_Support::get_instance()->render_commission_portal($atts), $requested_tab); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    /** Build a server-rendered, accessible horizontal tab workspace. */
    public static function render_workspace($name, $query_key, array $tabs, array $panels, $default, $notice = '') {
        $requested = isset($_GET[$query_key]) && is_scalar($_GET[$query_key]) ? sanitize_key(wp_unslash($_GET[$query_key])) : $default;
        if (!isset($tabs[$requested]) || !isset($panels[$requested])) { $requested = $default; }
        $url = get_permalink() ?: home_url('/');
        // Preserve the selected outer portal tab when this workspace is nested in [zmp_natprevari_portal].
        if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) {
            $url = add_query_arg('zmp_portal_tab', sanitize_key(wp_unslash($_GET['zmp_portal_tab'])), $url);
        }
        ob_start(); ?>
        <div class="zmp-workspace zmp-workspace--<?php echo esc_attr($name); ?>" data-zmp-workspace="<?php echo esc_attr($name); ?>" data-zmp-workspace-query="<?php echo esc_attr($query_key); ?>">
            <nav class="zmp-workspace__nav" aria-label="<?php echo esc_attr($tabs[$default] . ' — навигација'); ?>">
                <?php foreach ($tabs as $key => $label): if (!array_key_exists($key, $panels)) { continue; } $tab_text=is_array($label)?(string)($label['label']??''):(string)$label; $tab_count=is_array($label)?absint($label['count']??0):0; ?>
                    <a class="zmp-workspace__tab" data-workspace-tab="<?php echo esc_attr($key); ?>" href="<?php echo esc_url(add_query_arg($query_key, $key, $url)); ?>" <?php echo $requested === $key ? 'aria-current="page"' : ''; ?><?php echo $tab_count > 0 ? ' data-notification-count="' . esc_attr($tab_count) . '"' : ''; ?>><?php echo esc_html($tab_text); ?><?php if($tab_count>0):?><span class="zmp-workspace__badge" aria-label="<?php echo esc_attr($tab_count.' непрочитани или нерешени ставки'); ?>"><?php echo esc_html(number_format_i18n($tab_count));?></span><?php endif;?></a>
                <?php endforeach; ?>
            </nav>
            <?php if ($notice !== '') { echo wp_kses_post($notice); } ?>
            <?php foreach ($tabs as $key => $label): if (!array_key_exists($key, $panels)) { continue; } ?>
                <section class="zmp-workspace__panel" data-workspace-panel="<?php echo esc_attr($key); ?>" id="zmp-<?php echo esc_attr($name . '-' . $key); ?>" aria-label="<?php echo esc_attr(is_array($label)?($label['label']??''):$label); ?>" <?php echo $requested === $key ? '' : 'hidden'; ?>>
                    <?php echo $panels[$key]; // Rendered by access-checked plugin panel methods. ?>
                </section>
            <?php endforeach; ?>
        </div>
        <?php return ob_get_clean();
    }

    /** Keep commission panels usable when scripts are blocked or delayed. */
    private static function set_commission_panel_visibility($html, $active) {
        return preg_replace_callback('/<([a-z][a-z0-9]*)\b([^>]*\bdata-commission-panel="([a-z0-9_-]+)"[^>]*)>/i', function ($match) use ($active) {
            if ($match[3] === $active || preg_match('/\shidden(?:\s|=|$)/i', $match[2])) { return $match[0]; }
            return '<' . $match[1] . $match[2] . ' hidden>';
        }, (string) $html);
    }

    /** Render read/unread internal correspondence addressed to the current commission member. */
    private static function render_commission_communications() {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_internal_messages';
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name AS sender_name FROM $table m LEFT JOIN {$wpdb->users} u ON u.ID=m.sender_id WHERE m.recipient_id=%d ORDER BY (m.status='unread') DESC, m.created_at DESC LIMIT 100",
            get_current_user_id()
        ));
        ob_start(); ?>
        <section class="zmp-card zmp-commission-inbox" data-commission-panel="communication" role="region" aria-labelledby="zmp-commission-tab-communication">
            <h3>📨 Примени пораки и известувања</h3>
            <p>Овде се прикажани внатрешните пораки испратени до вашата сметка. Нерешените жалби и одлуки се бројат во синото известување и се достапни во табот „Жалби и одлуки“.</p>
            <?php if (isset($_GET['zmp_message_updated'])): ?><p class="zmp-alert zmp-alert-success">Пораката е означена како прочитана.</p><?php endif; ?>
            <?php if (!$messages): ?><p>Немате примени пораки.</p><?php else: ?>
                <div class="zmp-commission-message-list">
                    <?php foreach ($messages as $message): ?>
                        <article class="zmp-commission-message <?php echo $message->status === 'unread' ? 'is-unread' : 'is-read'; ?>">
                            <header><strong><?php echo esc_html($message->subject); ?></strong><span><?php echo esc_html($message->created_at); ?></span></header>
                            <p class="zmp-commission-message__meta"><?php echo esc_html($message->sender_name ?: 'Избришан корисник'); ?> · <?php echo esc_html($message->sender_role); ?><?php if ((int) $message->competition_id): ?> · Натпревар #<?php echo esc_html($message->competition_id); ?><?php endif; ?></p>
                            <div><?php echo nl2br(esc_html($message->message)); ?></div>
                            <?php if ($message->status === 'unread'): ?>
                                <form method="post" class="zmp-commission-message__action">
                                    <?php wp_nonce_field('zmp_commission_message_' . absint($message->id)); ?>
                                    <input type="hidden" name="zmp_commission_message_action" value="mark_read">
                                    <input type="hidden" name="message_id" value="<?php echo esc_attr($message->id); ?>">
                                    <input type="hidden" name="return_url" value="<?php echo esc_url(get_permalink() ?: home_url('/')); ?>">
                                    <button type="submit" class="zmp-btn zmp-btn-primary">Означи како прочитана</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }

    /** Mark a commission inbox message read only for its intended recipient. */
    public static function process_commission_message() {
        $action = isset($_POST['zmp_commission_message_action']) ? wp_unslash($_POST['zmp_commission_message_action']) : '';
        if (!is_scalar($action) || sanitize_key((string) $action) !== 'mark_read') { return; }
        if (!current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard') && !current_user_can('manage_options')) {
            wp_die(esc_html__('Немате привилегии.', 'zmp-competitions'), '', array('response' => 403));
        }
        $message_id = absint($_POST['message_id'] ?? 0);
        check_admin_referer('zmp_commission_message_' . $message_id);
        if (!$message_id) { wp_die(esc_html__('Невалидна порака.', 'zmp-competitions'), '', array('response' => 400)); }
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'zmp_internal_messages', array('status' => 'read'), array('id' => $message_id, 'recipient_id' => get_current_user_id(), 'status' => 'unread'), array('%s'), array('%d','%d','%s'));
        $return_raw = isset($_POST['return_url']) ? wp_unslash($_POST['return_url']) : '';
        $return_url = is_scalar($return_raw) ? esc_url_raw((string) $return_raw) : '';
        $redirect = wp_validate_redirect($return_url, home_url('/'));
        $redirect = remove_query_arg(array('zmp_message_updated','zmp_commission_tab'), $redirect);
        wp_safe_redirect(add_query_arg(array('zmp_message_updated'=>'1','zmp_commission_tab'=>'communication'), $redirect));
        exit;
    }

    /** Return live counts for unread messages and unresolved commission correspondence. */
    public static function ajax_commission_notifications() {
        if (!current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Немате привилегии.'), 403);
        }
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        global $wpdb;
        $user_id = get_current_user_id();
        $messages_table = $wpdb->prefix . 'zmp_internal_messages';
        $complaints_table = $wpdb->prefix . 'zmp_complaints';
        $decisions_table = $wpdb->prefix . 'zmp_commission_decisions';
        $unread = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $messages_table WHERE recipient_id=%d AND status='unread'", $user_id));
        $jury_only = class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror();
        $complaints = $jury_only ? 0 : (int) $wpdb->get_var("SELECT COUNT(*) FROM $complaints_table WHERE status IN ('pending','forwarded')");
        $decisions = $jury_only ? 0 : (int) $wpdb->get_var("SELECT COUNT(*) FROM $decisions_table WHERE status='pending' AND decision_type <> 'complaint'");
        wp_send_json_success(array('count' => $unread + $complaints + $decisions));
    }

    private static function render_pending_decisions($atts) {
        if (class_exists('ZMP_Category_Jury') and ZMP_Category_Jury::is_restricted_juror()) { return ''; }
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_commission_decisions';
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = %s AND decision_type <> 'complaint' ORDER BY created_at ASC",
            'pending'
        ));
        ob_start();
        ?>
        <div class="zmp-container zmp-commission-decisions" data-commission-panel="arbitration" role="region" aria-labelledby="zmp-commission-tab-arbitration">
            <div class="zmp-card">
                <h3><?php esc_html_e('Одлуки што чекаат разгледување', 'zmp-competitions'); ?></h3>
                <?php if (empty($items)): ?>
                    <p><?php esc_html_e('Нема одлуки што чекаат.', 'zmp-competitions'); ?></p>
                <?php else: ?>
                    <div class="zmp-table-responsive"><table class="zmp-table">
                        <thead><tr><th>ID</th><th>Тип</th><th>Субјект</th><th>Причина</th><th>Одлука</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td><?php echo esc_html($item->decision_type); ?></td>
                                <td><?php echo esc_html($item->subject_name); ?></td>
                                <td><?php echo nl2br(esc_html($item->reason)); ?></td>
                                <td><form method="post">
                                    <?php wp_nonce_field('zmp_commission_action', 'zmp_commission_nonce'); ?>
                                    <input type="hidden" name="zmp_commission_action" value="apply_decision">
                                    <input type="hidden" name="decision_id" value="<?php echo esc_attr($item->id); ?>">
                                    <label><span class="screen-reader-text">Белешка</span><textarea name="decision_note" rows="2" placeholder="Белешка за одлуката"></textarea></label>
                                    <button class="zmp-btn zmp-btn-primary" name="decision" value="approve">Одобри</button>
                                    <button class="zmp-btn zmp-btn-danger" name="decision" value="reject">Одбиј</button>
                                </form></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function process_commission_decision() {
        if (empty($_POST['zmp_commission_action']) || $_POST['zmp_commission_action'] !== 'apply_decision') {
            return;
        }
        if (!current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard') && !current_user_can('manage_options')) {
            wp_die(esc_html__('Немате привилегии за оваа одлука.', 'zmp-competitions'));
        }
        if (class_exists('ZMP_Category_Jury') and ZMP_Category_Jury::is_restricted_juror()) { wp_die(esc_html__('Оваа стара одлука не е ограничена по категорија. Користете ја доделената работна табла на жирито.', 'zmp-competitions'), '', array('response'=>403)); }
        check_admin_referer('zmp_commission_action', 'zmp_commission_nonce');
        $decision_id = absint($_POST['decision_id'] ?? 0);
        $decision = sanitize_key($_POST['decision'] ?? '');
        if (!$decision_id || !in_array($decision, array('approve', 'reject'), true)) {
            wp_die(esc_html__('Невалидна одлука.', 'zmp-competitions'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_commission_decisions';
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND status='pending'", $decision_id));
        if (!$item) wp_die(esc_html__('Одлуката веќе е обработена или не постои.', 'zmp-competitions'));
        if ($item->decision_type === 'complaint') {
            wp_die(esc_html__('Жалбите мора да се решат преку формуларот на Комисијата за да се зачуваат образложението и ревизорската трага на бодовите.', 'zmp-competitions'));
        }
        $note = sanitize_textarea_field($_POST['decision_note'] ?? '');
        $approved = ($decision === 'approve');
        if ($approved && $item->decision_type === 'disqualification') {
            $saved = $wpdb->insert($wpdb->prefix . 'zmp_disqualifications', array(
                'target_type'=>$item->target_type, 'target_id'=>$item->target_id, 'target_name'=>$item->subject_name,
                'target_code'=>$item->target_code, 'school_name'=>$item->school_name ?: 'Не е наведено',
                'reviewed_at'=>current_time('mysql'), 'disqualified_by_user_id'=>get_current_user_id(),
                'disqualified_by_name'=>wp_get_current_user()->display_name, 'disqualified_by_role'=>'Испитна комисија',
                'detailed_reason'=>$item->reason . ($note ? "\n\nОдлука: ".$note : '')
            ));
            if (!$saved) wp_die(esc_html__('Дисквалификацијата не можеше да се запише.', 'zmp-competitions'));
            if ($item->target_type === 'student' && $item->target_id) {
                $wpdb->update($wpdb->prefix . 'zmp_attempts', array('status'=>'disqualified','reset_reason'=>'Дисквалификуван од Комисијата: '.$item->reason), array('student_id'=>$item->target_id));
            }
        }
        if ($item->source_type === 'complaint' && $item->source_id) {
            $wpdb->update($wpdb->prefix . 'zmp_complaints', array(
                'status'=>$approved ? 'accepted' : 'rejected',
                'admin_notes'=>trim($item->reason . ($note ? "\n\nОдлука на Комисијата: ".$note : '')),
                'reviewed_at'=>current_time('mysql')
            ), array('id'=>$item->source_id));
        }
        $wpdb->update($table, array(
            'status'=>$approved ? 'approved' : 'rejected',
            'decision_note'=>$note, 'reviewed_by'=>get_current_user_id(), 'reviewed_at'=>current_time('mysql')
        ), array('id'=>$decision_id, 'status'=>'pending'));
        wp_safe_redirect(remove_query_arg(array('zmp_decision')));
        exit;
    }
}
