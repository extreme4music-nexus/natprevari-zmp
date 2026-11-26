<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public, minimal current-President display and administrator settings.
 *
 * This class deliberately does not call or relax the admin-only
 * ZMP_President_Mandate::current_mandate() method.
 */
class ZMP_President_Public_Display {
    const OPTION_KEY = 'zmp_president_public_display_settings';
    const STATE_OPTION_KEY = 'zmp_president_public_display_state_signature';
    const PAGE_SLUG = 'zmp-president-public-display';
    const SAVE_ACTION = 'zmp_president_public_display_save';
    const AJAX_ACTION = 'zmp_president_public_display_get';
    const PHOTO_OPT_IN_META = 'zmp_president_public_photo_opt_in';
    const PHOTO_ATTACHMENT_META = 'zmp_president_public_photo_attachment_id';
    const PHOTO_PROFILE_NONCE = 'zmp_president_public_photo_nonce';
    const PHOTO_PROFILE_ACTION = 'zmp_president_public_photo_save';

    /** Register admin, public cache, and mandate-state hooks. */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_admin_page'));
        add_action('admin_post_' . self::SAVE_ACTION, array(__CLASS__, 'handle_settings_save'));
        add_action('wp_ajax_' . self::AJAX_ACTION, array(__CLASS__, 'handle_public_display_request'));
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, array(__CLASS__, 'handle_public_display_request'));
        add_action('wp', array(__CLASS__, 'maybe_disable_page_cache'), 0);
        add_action('init', array(__CLASS__, 'observe_mandate_state'), 99);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_public_script'));
        add_action('show_user_profile', array(__CLASS__, 'render_profile_photo_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'render_profile_photo_fields'));
        add_action('personal_options_update', array(__CLASS__, 'save_profile_photo_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_profile_photo_fields'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_profile_photo_media'));
    }

    /** Return only approved public fields for a currently effective mandate. */
    public static function public_mandate_data() {
        $settings = self::settings();
        $today = current_time('Y-m-d');
        if (!empty($settings['publish_on']) && $settings['publish_on'] > $today) {
            return null;
        }

        $rows = self::active_mandate_rows();
        if (!$rows) {
            return null;
        }

        $presidents = array();
        foreach ($rows as $row) {
            $display_name = isset($row->display_name) && is_string($row->display_name)
                ? trim(wp_strip_all_tags($row->display_name))
                : '';
            $start_date = isset($row->start_date) ? (string) $row->start_date : '';
            $end_date = isset($row->end_date) ? (string) $row->end_date : '';
            $user_id = isset($row->user_id) ? (int) $row->user_id : 0;
            if ('' === $display_name || $user_id < 1 || !self::valid_date($start_date) || !self::valid_date($end_date)) {
                continue;
            }
            $president = array(
                'display_name' => $display_name,
                'start_date' => $start_date,
                'end_date' => $end_date,
            );
            $photo_url = self::local_attachment_url(absint(get_user_meta($user_id, self::PHOTO_ATTACHMENT_META, true)));
            if ('1' !== (string) get_user_meta($user_id, self::PHOTO_OPT_IN_META, true)) {
                $photo_url = '';
            }
            if ('' !== $photo_url) {
                $president['photo_url'] = $photo_url;
            }
            $presidents[] = $president;
        }
        if (!$presidents) {
            return null;
        }

        // Retain the original single-President fields for existing consumers.
        return array_merge($presidents[0], array('presidents' => $presidents));
    }

    /** Return escaped HTML for the public portal home tab. */
    public static function render_home_block() {
        $endpoint = admin_url('admin-ajax.php');
        return sprintf(
            '<section class="zmp-president-public-display" data-endpoint="%1$s" data-action="%2$s" data-term-label="%3$s" data-transition-label="%4$s" aria-labelledby="zmp-president-public-display-title" aria-live="polite" hidden>' .
            '<h2 id="zmp-president-public-display-title">%5$s</h2>' .
            '<p class="zmp-president-public-display__name"><strong></strong></p>' .
            '<p class="zmp-president-public-display__term"></p>' .
            '<div class="zmp-president-public-display__list" role="list" aria-label="%6$s"></div>' .
            '<p class="zmp-president-public-display__transition" role="status"></p>' .
            '</section>',
            esc_url($endpoint),
            esc_attr(self::AJAX_ACTION),
            esc_attr__('Мандат: %1$s – %2$s', 'zmp-competitions'),
            esc_attr__('Во моментов нема активен мандат.', 'zmp-competitions'),
            esc_html__('Претседател на ЗМП', 'zmp-competitions'),
            esc_attr__('Активни претседатели', 'zmp-competitions')
        );
    }

    /** Return the current public display state through an uncached read-only endpoint. */
    public static function handle_public_display_request() {
        if (!headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        }

        $settings = self::settings();
        $today = current_time('Y-m-d');
        $mandate = (!empty($settings['publish_on']) && $settings['publish_on'] > $today)
            ? null
            : self::public_mandate_data();

        if ($mandate) {
            $data = array(
                'state' => 'president',
                'display_name' => $mandate['display_name'],
                'start_date' => $mandate['start_date'],
                'end_date' => $mandate['end_date'],
                'presidents' => isset($mandate['presidents']) && is_array($mandate['presidents']) ? $mandate['presidents'] : array(),
            );
        } elseif ('show' === $settings['transition_state']
            && (empty($settings['publish_on']) || $settings['publish_on'] <= $today)) {
            $data = array(
                'state' => 'transition',
                'display_name' => '',
                'start_date' => '',
                'end_date' => '',
                'presidents' => array(),
            );
        } else {
            $data = array(
                'state' => 'hidden',
                'display_name' => '',
                'start_date' => '',
                'end_date' => '',
                'presidents' => array(),
            );
        }

        wp_send_json_success($data);
    }

    /** Enqueue the fragment loader only for pages containing the public portal shortcode. */
    public static function enqueue_public_script() {
        if (!is_singular()) {
            return;
        }
        $post = get_queried_object();
        if (!$post || empty($post->post_content) || !has_shortcode((string) $post->post_content, 'zmp_natprevari_portal')) {
            return;
        }
        wp_enqueue_script(
            'zmp-president-public-display',
            ZMP_PLUGIN_URL . 'assets/js/zmp-president-public-display.js',
            array(),
            defined('ZMP_VERSION') ? ZMP_VERSION : null,
            true
        );
        wp_enqueue_style(
            'zmp-president-public-display',
            ZMP_PLUGIN_URL . 'assets/css/zmp-president-public-display.css',
            array(),
            defined('ZMP_VERSION') ? ZMP_VERSION : null
        );
    }

    /** Enqueue the media picker only for an eligible President's profile or an administrator editing one. */
    public static function enqueue_profile_photo_media($hook) {
        if (!in_array((string) $hook, array('profile.php', 'user-edit.php'), true) || !defined('ZMP_PLUGIN_URL')) {
            return;
        }
        $target_id = isset($_GET['user_id']) ? absint(wp_unslash($_GET['user_id'])) : get_current_user_id();
        if ($target_id < 1 || !self::has_president_record($target_id)
            || !current_user_can('edit_user', $target_id)
            || (get_current_user_id() !== $target_id && !current_user_can('manage_options'))) {
            return;
        }
        wp_enqueue_style(
            'zmp-president-public-display',
            ZMP_PLUGIN_URL . 'assets/css/zmp-president-public-display.css',
            array(),
            defined('ZMP_VERSION') ? ZMP_VERSION : null
        );
        if (!current_user_can('upload_files')) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'zmp-president-photo-profile',
            ZMP_PLUGIN_URL . 'assets/js/zmp-president-photo-profile.js',
            array('jquery'),
            defined('ZMP_VERSION') ? ZMP_VERSION : null,
            true
        );
    }

    /** Render a self-service, default-off photo preference on President user profiles. */
    public static function render_profile_photo_fields($user) {
        $target_id = is_object($user) && isset($user->ID) ? (int) $user->ID : 0;
        if ($target_id < 1 || !self::has_president_record($target_id)
            || !current_user_can('edit_user', $target_id)
            || (get_current_user_id() !== $target_id && !current_user_can('manage_options'))) {
            return;
        }

        $opted_in = '1' === (string) get_user_meta($target_id, self::PHOTO_OPT_IN_META, true);
        $can_manage_consent = get_current_user_id() === $target_id;
        $attachment_id = absint(get_user_meta($target_id, self::PHOTO_ATTACHMENT_META, true));
        $photo_url = self::local_attachment_url($attachment_id);
        ?>
        <h2><?php echo esc_html__('Јавна фотографија на претседател', 'zmp-competitions'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Фотографија на почетната страна', 'zmp-competitions'); ?></th>
                <td>
                    <fieldset class="zmp-president-public-photo-field">
                        <legend class="screen-reader-text"><?php echo esc_html__('Фотографија на почетната страна', 'zmp-competitions'); ?></legend>
                        <?php if ($can_manage_consent) : ?>
                            <input type="hidden" name="<?php echo esc_attr(self::PHOTO_OPT_IN_META); ?>" value="0">
                            <label for="zmp-president-public-photo-opt-in">
                                <input type="checkbox" id="zmp-president-public-photo-opt-in" name="<?php echo esc_attr(self::PHOTO_OPT_IN_META); ?>" value="1" <?php checked($opted_in); ?>>
                                <?php echo esc_html__('Доброволно прикажи ја мојата фотографија покрај активниот мандат.', 'zmp-competitions'); ?>
                            </label>
                        <?php else : ?>
                            <label for="zmp-president-public-photo-opt-in">
                                <input type="checkbox" id="zmp-president-public-photo-opt-in" value="1" <?php checked($opted_in); ?> disabled aria-describedby="zmp-president-photo-consent-note">
                                <?php echo esc_html__('Доброволно прикажи ја фотографијата покрај активниот мандат.', 'zmp-competitions'); ?>
                            </label>
                            <p class="description" id="zmp-president-photo-consent-note"><?php echo esc_html__('Само претседателот може да ја даде или повлече оваа согласност од сопствениот профил.', 'zmp-competitions'); ?></p>
                        <?php endif; ?>
                        <input type="hidden" id="zmp-president-public-photo-attachment-id" name="<?php echo esc_attr(self::PHOTO_ATTACHMENT_META); ?>" value="<?php echo esc_attr($attachment_id); ?>">
                        <?php if (current_user_can('upload_files')) : ?>
                            <p class="zmp-president-public-photo-field__controls">
                                <button type="button" class="button zmp-president-photo-select" data-target="#zmp-president-public-photo-attachment-id"><?php echo esc_html__('Избери фотографија', 'zmp-competitions'); ?></button>
                                <button type="button" class="button zmp-president-photo-remove" data-target="#zmp-president-public-photo-attachment-id"><?php echo esc_html__('Отстрани фотографија', 'zmp-competitions'); ?></button>
                            </p>
                        <?php else : ?>
                            <p class="description"><?php echo esc_html__('За избор или отстранување на локална слика, побарајте администратор да ја уреди оваа поставка.', 'zmp-competitions'); ?></p>
                        <?php endif; ?>
                        <img class="zmp-president-photo-preview" src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr__('Преглед на избраната фотографија', 'zmp-competitions'); ?>" <?php echo $photo_url === '' ? 'hidden' : ''; ?>>
                        <p class="description"><?php echo esc_html__('Стандардно е исклучено. Фотографијата што ја избрал администратор или овластено лице се прикажува јавно само додека имате активен мандат и оваа согласност е вклучена.', 'zmp-competitions'); ?></p>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
        wp_nonce_field(self::profile_photo_nonce_action($target_id), self::PHOTO_PROFILE_NONCE, false);
    }

    /** Persist President-only consent and allow administrators to manage the local attachment separately. */
    public static function save_profile_photo_fields($user_id) {
        $user_id = absint($user_id);
        if ($user_id < 1 || !isset($_POST[self::PHOTO_PROFILE_NONCE])
            || !current_user_can('edit_user', $user_id)
            || (get_current_user_id() !== $user_id && !current_user_can('manage_options'))
            || !self::has_president_record($user_id)) {
            return;
        }

        $nonce = is_string($_POST[self::PHOTO_PROFILE_NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::PHOTO_PROFILE_NONCE])) : '';
        if (!wp_verify_nonce($nonce, self::profile_photo_nonce_action($user_id))) {
            return;
        }

        $can_change_consent = get_current_user_id() === $user_id;
        $before_opt_in = get_user_meta($user_id, self::PHOTO_OPT_IN_META, true);
        $wants_public = $can_change_consent
            ? (isset($_POST[self::PHOTO_OPT_IN_META]) && is_scalar($_POST[self::PHOTO_OPT_IN_META])
                && '1' === (string) wp_unslash($_POST[self::PHOTO_OPT_IN_META]))
            : ('1' === (string) $before_opt_in);
        $posted_attachment = isset($_POST[self::PHOTO_ATTACHMENT_META]) && is_scalar($_POST[self::PHOTO_ATTACHMENT_META])
            ? absint(wp_unslash($_POST[self::PHOTO_ATTACHMENT_META]))
            : 0;
        $existing_attachment = absint(get_user_meta($user_id, self::PHOTO_ATTACHMENT_META, true));
        $attachment_is_existing_local = $posted_attachment > 0 && $posted_attachment === $existing_attachment
            && '' !== self::local_attachment_url($posted_attachment);
        $attachment_id = $attachment_is_existing_local || ($posted_attachment > 0 && self::valid_profile_attachment($posted_attachment, $user_id))
            ? $posted_attachment
            : 0;
        if ($can_change_consent && $wants_public && $attachment_id < 1) {
            $wants_public = false;
        }
        $before_attachment = get_user_meta($user_id, self::PHOTO_ATTACHMENT_META, true);
        if ($can_change_consent) {
            update_user_meta($user_id, self::PHOTO_OPT_IN_META, $wants_public ? '1' : '0');
        }
        if ($attachment_id > 0) {
            update_user_meta($user_id, self::PHOTO_ATTACHMENT_META, $attachment_id);
        } else {
            delete_user_meta($user_id, self::PHOTO_ATTACHMENT_META);
        }

        if ((string) $before_opt_in !== ($wants_public ? '1' : '0') || (int) $before_attachment !== $attachment_id) {
            self::invalidate_public_display_caches();
        }
    }

    /** Add settings below the ZMP plugin menu for administrators only. */
    public static function register_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        add_submenu_page(
            'zmp-competitions',
            __('Јавно прикажување на претседател', 'zmp-competitions'),
            __('Јавно прикажување на претседател', 'zmp-competitions'),
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render_admin_page')
        );
    }

    /** Render administrator-only publication settings. */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('Немате дозвола да ги менувате јавните поставки за претседател.', 'zmp-competitions'),
                '',
                array('response' => 403)
            );
        }

        $settings = self::settings();
        $notice = isset($_GET['zmp_president_public_notice']) && is_scalar($_GET['zmp_president_public_notice'])
            ? sanitize_key(wp_unslash($_GET['zmp_president_public_notice']))
            : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Јавно прикажување на претседател', 'zmp-competitions'); ?></h1>
            <?php if ('saved' === $notice) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Поставките се зачувани.', 'zmp-competitions'); ?></p></div>
            <?php elseif ('invalid' === $notice) : ?>
                <div class="notice notice-error"><p><?php echo esc_html__('Проверете го датумот и преодната состојба. Поставките не се променети.', 'zmp-competitions'); ?></p></div>
            <?php elseif ('save_failed' === $notice) : ?>
                <div class="notice notice-error"><p><?php echo esc_html__('Поставките не се зачувани. Повторете обид или контактирајте администратор.', 'zmp-competitions'); ?></p></div>
            <?php endif; ?>
            <p><?php echo esc_html__('На јавната почетна страна се прикажуваат само името и периодот од важечкиот мандат. Не се објавуваат контакт-податоци, изборни документи или информации за клуч.', 'zmp-competitions'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <?php wp_nonce_field(self::SAVE_ACTION); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="zmp-president-publish-on"><?php echo esc_html__('Датум на објавување', 'zmp-competitions'); ?></label></th>
                        <td>
                            <input type="date" id="zmp-president-publish-on" name="publish_on" value="<?php echo esc_attr($settings['publish_on']); ?>">
                            <p class="description"><?php echo esc_html__('Оставете празно за објавување веднаш штом мандатот е важечки според датумите. Инаку, приказот е скриен до избраниот датум.', 'zmp-competitions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="zmp-president-transition-state"><?php echo esc_html__('Кога нема активен мандат', 'zmp-competitions'); ?></label></th>
                        <td>
                            <select id="zmp-president-transition-state" name="transition_state">
                                <option value="show" <?php selected($settings['transition_state'], 'show'); ?>><?php echo esc_html__('Прикажи известување дека нема активен мандат', 'zmp-competitions'); ?></option>
                                <option value="hide" <?php selected($settings['transition_state'], 'hide'); ?>><?php echo esc_html__('Сокриј го делот', 'zmp-competitions'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Зачувај поставки', 'zmp-competitions')); ?>
            </form>
        </div>
        <?php
    }

    /** Validate and save settings through the administrator-only POST route. */
    public static function handle_settings_save() {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('Само администратор може да ги менува овие поставки.', 'zmp-competitions'),
                '',
                array('response' => 403)
            );
        }
        check_admin_referer(self::SAVE_ACTION);

        $publish_on = isset($_POST['publish_on']) && is_string($_POST['publish_on'])
            ? trim((string) wp_unslash($_POST['publish_on']))
            : null;
        $transition_state = isset($_POST['transition_state']) && is_string($_POST['transition_state'])
            ? sanitize_key(wp_unslash($_POST['transition_state']))
            : '';

        if (null === $publish_on
            || ('' !== $publish_on && !self::valid_date($publish_on))
            || !in_array($transition_state, array('show', 'hide'), true)) {
            self::redirect_with_notice('invalid');
        }

        $old_settings = self::settings();
        $new_settings = array(
            'publish_on' => $publish_on,
            'transition_state' => $transition_state,
        );
        $persisted = $new_settings === get_option(self::OPTION_KEY, null);
        if (!$persisted) {
            update_option(self::OPTION_KEY, $new_settings, false);
            $persisted = $new_settings === get_option(self::OPTION_KEY, null);
        }

        if (!$persisted) {
            self::redirect_with_notice('save_failed');
        }

        if ($new_settings !== $old_settings) {
            self::invalidate_public_display_caches();
        }

        self::redirect_with_notice('saved');
    }

    /** Mark a portal page non-cacheable before its shortcode is rendered. */
    public static function maybe_disable_page_cache() {
        if (!is_singular()) {
            return;
        }
        $post = get_queried_object();
        if (!$post || empty($post->post_content) || !has_shortcode((string) $post->post_content, 'zmp_natprevari_portal')) {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (!headers_sent()) {
            nocache_headers();
        }
    }

    /** Detect mandate/date changes and ask WordPress/cache integrations to purge portal pages. */
    public static function observe_mandate_state() {
        $state = array();
        foreach (self::active_mandate_rows() as $row) {
            $user_id = (int) $row->user_id;
            $state[] = array(
                (int) $row->id,
                $user_id,
                (string) $row->start_date,
                (string) $row->end_date,
                '1' === (string) get_user_meta($user_id, self::PHOTO_OPT_IN_META, true),
                absint(get_user_meta($user_id, self::PHOTO_ATTACHMENT_META, true)),
            );
        }
        $signature = hash('sha256', (string) wp_json_encode($state));
        $previous = (string) get_option(self::STATE_OPTION_KEY, '');
        if ($signature !== $previous) {
            update_option(self::STATE_OPTION_KEY, $signature, false);
            self::invalidate_public_display_caches();
        }
    }

    /** Return unique active mandate users for the site's local date, newest mandate first. */
    private static function active_mandate_rows() {
        global $wpdb;
        if (!class_exists('ZMP_President_Mandate') || !is_callable(array('ZMP_President_Mandate', 'table_name'))) {
            return array();
        }

        $table = ZMP_President_Mandate::table_name();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($found !== $table) {
            return array();
        }

        $today = current_time('Y-m-d');
        if (!self::valid_date($today)) {
            return array();
        }
        $users = $wpdb->users;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id,m.user_id,m.start_date,m.end_date,u.display_name
             FROM {$table} AS m
             INNER JOIN {$users} AS u ON u.ID = m.user_id
             WHERE m.status = %s AND m.start_date <= %s AND m.end_date >= %s
             ORDER BY m.start_date DESC,m.id DESC",
            'active',
            $today,
            $today
        ));
        if (!is_array($rows) || !$rows) {
            return array();
        }
        $unique = array();
        foreach ($rows as $row) {
            if (!isset($row->user_id, $row->display_name, $row->start_date, $row->end_date)
                || (int) $row->user_id < 1
                || !self::valid_date((string) $row->start_date)
                || !self::valid_date((string) $row->end_date)) {
                continue;
            }
            $user_id = (int) $row->user_id;
            if (!isset($unique[$user_id])) {
                $unique[$user_id] = $row;
            }
        }
        return array_values($unique);
    }

    /** Whether this account has or is scheduled to hold a President mandate. */
    private static function has_president_record($user_id) {
        global $wpdb;
        if (!class_exists('ZMP_President_Mandate') || !is_callable(array('ZMP_President_Mandate', 'table_name'))) {
            return false;
        }
        $table = ZMP_President_Mandate::table_name();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($found !== $table) {
            return false;
        }
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id=%d AND status IN ('active','scheduled','expired','replaced') LIMIT 1",
            absint($user_id)
        ));
    }

    /** Verify that a selected image is an inherited, local WordPress media attachment. */
    private static function valid_profile_attachment($attachment_id, $user_id) {
        $attachment = get_post(absint($attachment_id));
        if (!$attachment || 'attachment' !== $attachment->post_type || 'inherit' !== $attachment->post_status
            || !is_string($attachment->post_mime_type) || 0 !== strpos($attachment->post_mime_type, 'image/')) {
            return false;
        }
        if (get_current_user_id() !== (int) $user_id && !current_user_can('manage_options')
            || get_current_user_id() === (int) $user_id && (int) $attachment->post_author !== (int) $user_id) {
            return false;
        }
        return '' !== self::local_attachment_url($attachment_id);
    }

    /** Return the site's own upload URL for a public image attachment; never use Gravatar or remote URLs. */
    private static function local_attachment_url($attachment_id) {
        $attachment_id = absint($attachment_id);
        if ($attachment_id < 1) {
            return '';
        }
        $attachment = get_post($attachment_id);
        if (!$attachment || 'attachment' !== $attachment->post_type || 'inherit' !== $attachment->post_status
            || !is_string($attachment->post_mime_type) || 0 !== strpos($attachment->post_mime_type, 'image/')) {
            return '';
        }
        $relative = get_post_meta($attachment_id, '_wp_attached_file', true);
        if (!is_string($relative) || '' === trim($relative)) {
            return '';
        }
        $relative = str_replace('\\', '/', $relative);
        if (0 === strpos($relative, '/') || preg_match('#(^|/)\.\.?(/|$)#', $relative) || false !== strpos($relative, '?') || false !== strpos($relative, '#')) {
            return '';
        }
        $uploads = wp_upload_dir();
        if (!is_array($uploads) || !empty($uploads['error']) || empty($uploads['baseurl'])) {
            return '';
        }
        $url = wp_get_attachment_url($attachment_id);
        $base_url = esc_url_raw((string) $uploads['baseurl'], array('http', 'https'));
        $url = is_string($url) ? esc_url_raw($url, array('http', 'https')) : '';
        $base_parts = wp_parse_url($base_url);
        $parts = wp_parse_url($url);
        if (!$base_url || !$url || !is_array($base_parts) || !is_array($parts)
            || empty($base_parts['host']) || empty($parts['host'])
            || strtolower($base_parts['host']) !== strtolower($parts['host'])
            || strtolower((string) ($base_parts['scheme'] ?? '')) !== strtolower((string) ($parts['scheme'] ?? ''))
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !isset($base_parts['path'], $parts['path'])) {
            return '';
        }
        $scheme = strtolower((string) ($base_parts['scheme'] ?? ''));
        $base_port = isset($base_parts['port']) ? (int) $base_parts['port'] : ('https' === $scheme ? 443 : 80);
        $photo_port = isset($parts['port']) ? (int) $parts['port'] : ('https' === $scheme ? 443 : 80);
        if ($base_port !== $photo_port) {
            return '';
        }
        $base_path = rtrim((string) $base_parts['path'], '/');
        if ('' !== $base_path && 0 !== strpos((string) $parts['path'], $base_path . '/')) {
            return '';
        }
        return $url;
    }

    /** Add the profile photo chooser nonce only to users with a President record. */
    private static function profile_photo_nonce_action($user_id) {
        return self::PHOTO_PROFILE_ACTION . '_' . absint($user_id);
    }

    /** Return validated settings, ignoring malformed stored option values. */
    private static function settings() {
        $stored = get_option(self::OPTION_KEY, array());
        $stored = is_array($stored) ? $stored : array();
        $publish_on = isset($stored['publish_on']) && is_string($stored['publish_on'])
            && ('' === $stored['publish_on'] || self::valid_date($stored['publish_on']))
            ? $stored['publish_on']
            : '';
        $transition_state = isset($stored['transition_state'])
            && in_array($stored['transition_state'], array('show', 'hide'), true)
            ? $stored['transition_state']
            : 'show';
        return array(
            'publish_on' => $publish_on,
            'transition_state' => $transition_state,
        );
    }

    /** Strictly validate a real site-local YYYY-MM-DD calendar date. */
    private static function valid_date($value) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone());
        $errors = DateTimeImmutable::getLastErrors();
        return $date instanceof DateTimeImmutable
            && (!$errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']))
            && $date->format('Y-m-d') === $value;
    }

    /** Invalidate WordPress post/object caches and notify configured cache integrations. */
    private static function invalidate_public_display_caches() {
        $page_ids = array();
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ));
        foreach ((array) $pages as $page_id) {
            $page = get_post((int) $page_id);
            if ($page && has_shortcode((string) $page->post_content, 'zmp_natprevari_portal')) {
                clean_post_cache((int) $page_id);
                $page_ids[] = (int) $page_id;
            }
        }
        do_action('zmp_president_public_display_cache_purge', $page_ids);
    }

    /** Redirect to the settings screen with a fixed, sanitized status code. */
    private static function redirect_with_notice($notice) {
        $url = add_query_arg(
            array(
                'page' => self::PAGE_SLUG,
                'zmp_president_public_notice' => sanitize_key($notice),
            ),
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }
}
