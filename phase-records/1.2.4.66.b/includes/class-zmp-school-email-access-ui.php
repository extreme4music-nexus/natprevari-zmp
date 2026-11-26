<?php
if (!defined('ABSPATH')) { exit; }

/** Presentation for owner-scoped official-school-email requests and President review. */
final class ZMP_School_Email_Access_UI {
    public static function init() {
        add_shortcode('zmp_school_email_access', array(__CLASS__, 'render_shortcode'));
        add_action('admin_post_zmp_school_email_access_disclose', array(__CLASS__, 'handle_disclosure'));
    }

    public static function render_shortcode() {
        if (!is_user_logged_in()) {
            return '<p class="zmp-school-email-access__notice" role="status">' . esc_html__('Најавете се со наставнички профил за да поднесете или прегледате барање.', 'zmp-competitions') . '</p>';
        }
        if (!class_exists('ZMP_School_Email_Access') || !class_exists('ZMP_Mentor_School_Profile')) {
            return '<p class="zmp-school-email-access__notice" role="status">' . esc_html__('Барањата за службена е-пошта моментално не се достапни.', 'zmp-competitions') . '</p>';
        }

        $user_id = get_current_user_id();
        $out = '<section class="zmp-school-email-access" aria-labelledby="zmp-school-email-access-title"><h2 id="zmp-school-email-access-title">' . esc_html__('Барање за службена училишна е-пошта', 'zmp-competitions') . '</h2>';
        $is_teacher = ZMP_Mentor_School_Profile::is_teacher($user_id);
        $is_president = self::is_active_president($user_id);

        if ($is_teacher) {
            // Request choices are limited to the requester's own active engagements.
            // The directory school list is broader and must not be used as authority
            // for an email-access request.
            $schools = self::call('ZMP_Mentor_School_Profile', 'get_engagements', array($user_id));
            if (is_wp_error($schools)) {
                $out .= '<p class="zmp-school-email-access__notice" role="status">' . esc_html__('Поднесувањето барања не е достапно за оваа сметка.', 'zmp-competitions') . '</p>';
            } elseif (is_array($schools) && $schools) {
                $out .= '<p>' . esc_html__('Изберете колега од истото училиште и наведете зошто ви е потребна службената адреса. Адресата се прикажува само по одобрение од активниот Претседател.', 'zmp-competitions') . '</p>';
                foreach ($schools as $school) { $out .= self::request_form($user_id, $school); }
            } else {
                $out .= '<p role="status">' . esc_html__('Во профилот нема активно училиште за кое може да се поднесе барање.', 'zmp-competitions') . '</p>';
            }

            $requests = self::call('ZMP_School_Email_Access', 'requests_for_requester', array($user_id));
            $out .= '<section class="zmp-school-email-access__requests"><h3>' . esc_html__('Мои барања', 'zmp-competitions') . '</h3>';
            if (is_wp_error($requests)) {
                $out .= '<p role="status">' . esc_html__('Статусот на барањата не е достапен.', 'zmp-competitions') . '</p>';
            } elseif (!$requests) {
                $out .= '<p role="status">' . esc_html__('Сè уште немате поднесено барања.', 'zmp-competitions') . '</p>';
            } else {
                $out .= '<ul class="zmp-school-email-access__request-list">';
                foreach ((array) $requests as $request) { $out .= self::request_status($request); }
                $out .= '</ul>';
            }
            $out .= '</section>';
        }

        if ($is_president) {
            $out .= self::president_queue($user_id);
        } elseif (!$is_teacher) {
            $out .= '<p class="zmp-school-email-access__notice" role="status">' . esc_html__('Немате пристап до поднесување или одобрување на овие барања.', 'zmp-competitions') . '</p>';
        }
        $out .= '</section>';
        return $out;
    }

    /** Reveal only as the direct no-cache response to an approved requester POST. */
    public static function handle_disclosure() {
        if (!is_user_logged_in() || !isset($_POST['request_id']) || !is_scalar($_POST['request_id'])) {
            self::disclosure_error();
        }
        $request_id = absint(wp_unslash((string) $_POST['request_id']));
        if ($request_id < 1 || !class_exists('ZMP_School_Email_Access')
            || !is_callable(array('ZMP_School_Email_Access', 'disclose_approved_email'))) {
            self::disclosure_error();
        }
        if (!isset($_POST['_wpnonce']) || !is_scalar($_POST['_wpnonce'])
            || !wp_verify_nonce(
                wp_unslash((string) $_POST['_wpnonce']),
                'zmp_school_email_disclose_' . $request_id
            )) {
            self::disclosure_error();
        }
        $email = ZMP_School_Email_Access::disclose_approved_email($request_id, get_current_user_id());
        if (is_wp_error($email) || !is_string($email) || $email === '') { self::disclosure_error(); }

        if (!headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
            header('Pragma: no-cache', true);
            header('X-Robots-Tag: noindex, nofollow, noarchive', true);
            header('Content-Type: text/html; charset=' . get_option('blog_charset', 'UTF-8'), true);
        }
        status_header(200);
        echo '<!doctype html><html lang="' . esc_attr(determine_locale()) . '"><head><meta charset="' . esc_attr(get_option('blog_charset', 'UTF-8')) . '"><meta name="robots" content="noindex,nofollow,noarchive"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__('Одобрена службена адреса', 'zmp-competitions') . '</title></head><body><main class="zmp-school-email-disclosure"><h1>' . esc_html__('Одобрена службена адреса', 'zmp-competitions') . '</h1><p>' . esc_html__('Адресата е прикажана за ова одобрено барање и прегледот е евидентиран.', 'zmp-competitions') . '</p><p><strong>' . esc_html($email) . '</strong></p><p>' . esc_html__('Затворете ја оваа страница по употреба.', 'zmp-competitions') . '</p></main></body></html>';
        exit;
    }

    private static function request_form($requester_id, $school) {
        $school_id = absint(self::value($school, 'school_id', 0));
        $school_name = (string) self::value($school, 'school_name', '');
        $municipality = (string) self::value($school, 'municipality', '');
        if (!$school_id) { return ''; }
        $contacts = self::call('ZMP_School_Email_Access', 'get_requestable_contacts', array((int) $requester_id, $school_id));
        $heading = '<h4>' . esc_html($school_name) . ($municipality !== '' ? ' — ' . esc_html($municipality) : '') . '</h4>';
        if (is_wp_error($contacts)) { return '<section class="zmp-school-email-access__school">' . $heading . '<p role="status">' . esc_html__('Контактите за ова училиште моментално не се достапни.', 'zmp-competitions') . '</p></section>'; }
        if (!$contacts) { return '<section class="zmp-school-email-access__school">' . $heading . '<p role="status">' . esc_html__('Нема колега во ова училиште за кого може да се поднесе барање.', 'zmp-competitions') . '</p></section>'; }

        ob_start();
        echo '<section class="zmp-school-email-access__school">' . $heading;
        echo '<form class="zmp-school-email-access__form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="zmp_school_email_request_submit"><input type="hidden" name="school_id" value="' . (int) $school_id . '">';
        echo '<label for="zmp-email-contact-' . (int) $school_id . '">' . esc_html__('Колега', 'zmp-competitions') . '</label><select id="zmp-email-contact-' . (int) $school_id . '" name="owner_user_id" required><option value="">' . esc_html__('Изберете колега', 'zmp-competitions') . '</option>';
        foreach ((array) $contacts as $contact) {
            $owner_id = absint(self::value($contact, 'owner_user_id', 0));
            $owner_name = (string) self::value($contact, 'owner_name', '');
            if ($owner_id < 1 || $owner_name === '') { continue; }
            echo '<option value="' . (int) $owner_id . '">' . esc_html($owner_name) . '</option>';
        }
        echo '</select><label for="zmp-email-reason-' . (int) $school_id . '">' . esc_html__('Причина за барањето', 'zmp-competitions') . '</label><textarea id="zmp-email-reason-' . (int) $school_id . '" name="reason" rows="3" maxlength="1000" required></textarea>';
        wp_nonce_field('zmp_school_email_request_submit');
        echo '<button type="submit" class="button button-primary">' . esc_html__('Поднеси барање', 'zmp-competitions') . '</button></form></section>';
        return (string) ob_get_clean();
    }

    private static function request_status($request) {
        $id = absint(self::value($request, 'request_id', 0));
        $owner = (string) self::value($request, 'owner_name', '');
        $school = (string) self::value($request, 'school_name', '');
        $status = (string) self::value($request, 'status', '');
        $labels = array(
            'pending' => __('Чека одлука', 'zmp-competitions'),
            'approved' => __('Одобрено', 'zmp-competitions'),
            'rejected' => __('Одбиено', 'zmp-competitions'),
            'invalidated' => __('Истечено или недостапно', 'zmp-competitions'),
        );
        if ($id < 1 || !isset($labels[$status])) { return ''; }
        $html = '<li class="zmp-school-email-access__request"><h4>' . esc_html($owner) . ' — ' . esc_html($school) . '</h4><p><strong>' . esc_html__('Статус:', 'zmp-competitions') . '</strong> ' . esc_html($labels[$status]) . '</p>';
        if ($status === 'invalidated') {
            $html .= '<p class="zmp-school-email-access__invalidated" role="status">' . esc_html__('Односот со училиштето е променет, па претходното одобрение повеќе не важи. Ако сè уште ви е потребна адресата, поднесете ново барање.', 'zmp-competitions') . '</p>';
        }
        $reason = (string) self::value($request, 'reason', '');
        if ($reason !== '') { $html .= '<p><strong>' . esc_html__('Причина:', 'zmp-competitions') . '</strong> ' . esc_html($reason) . '</p>'; }
        $created = (string) self::value($request, 'created_at', '');
        if ($created !== '') { $html .= '<p><small>' . esc_html__('Поднесено:', 'zmp-competitions') . ' ' . esc_html($created) . '</small></p>'; }
        if ($status === 'approved') {
            $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="zmp-school-email-access__disclose"><input type="hidden" name="action" value="zmp_school_email_access_disclose"><input type="hidden" name="request_id" value="' . (int) $id . '">';
            $html .= wp_nonce_field('zmp_school_email_disclose_' . $id, '_wpnonce', true, false);
            $html .= '<button type="submit" class="button button-primary">' . esc_html__('Прегледај ја одобрената адреса', 'zmp-competitions') . '</button></form>';
        }
        return $html . '</li>';
    }

    private static function president_queue($president_id) {
        $queue = self::call('ZMP_School_Email_Access', 'pending_for_active_president', array((int) $president_id));
        $html = '<section class="zmp-school-email-access__president"><h3>' . esc_html__('Барања за одобрување', 'zmp-competitions') . '</h3>';
        if (is_wp_error($queue)) { return $html . '<p role="status">' . esc_html__('Редицата за одобрување не е достапна.', 'zmp-competitions') . '</p></section>'; }
        if (!$queue) { return $html . '<p role="status">' . esc_html__('Нема барања што чекаат одлука.', 'zmp-competitions') . '</p></section>'; }
        foreach ((array) $queue as $request) {
            $id = absint(self::value($request, 'request_id', 0));
            if (!$id) { continue; }
            $html .= '<article class="zmp-school-email-access__decision"><h4>' . esc_html((string) self::value($request, 'requester_name', '')) . ' бара адреса од ' . esc_html((string) self::value($request, 'owner_name', '')) . '</h4>';
            $html .= '<p><strong>' . esc_html((string) self::value($request, 'school_name', '')) . '</strong>';
            $municipality = (string) self::value($request, 'municipality', '');
            if ($municipality !== '') { $html .= ' — ' . esc_html($municipality); }
            $html .= '</p><p>' . nl2br(esc_html((string) self::value($request, 'reason', ''))) . '</p>';
            $html .= '<div class="zmp-school-email-access__decision-actions">';
            foreach (array('approved' => __('Одобри', 'zmp-competitions'), 'rejected' => __('Одбиј', 'zmp-competitions')) as $decision => $label) {
                $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="zmp_school_email_access_decide"><input type="hidden" name="request_id" value="' . (int) $id . '"><input type="hidden" name="decision" value="' . esc_attr($decision) . '">';
                $html .= wp_nonce_field('zmp_school_email_access_decide_' . $id, '_wpnonce', true, false);
                $html .= '<button type="submit" class="button">' . esc_html($label) . '</button></form>';
            }
            $html .= '</div></article>';
        }
        return $html . '</section>';
    }

    private static function is_active_president($user_id) {
        $user = get_userdata((int) $user_id);
        if (!$user) { return false; }
        return ZMP_Mentor_School_Profile::has_role_stack((int) $user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && current_user_can('view_zmp_president_dashboard')
            && class_exists('ZMP_President_Mandate')
            && is_callable(array('ZMP_President_Mandate', 'user_has_active_mandate'))
            && ZMP_President_Mandate::user_has_active_mandate((int) $user_id);
    }

    private static function disclosure_error() {
        if (!headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
            header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        }
        wp_die(esc_html__('Одобрената адреса не е достапна за ова барање.', 'zmp-competitions'), esc_html__('Адресата не е достапна', 'zmp-competitions'), array('response' => 403, 'back_link' => true));
    }

    private static function call($class, $method, array $args) {
        if (!class_exists($class) || !is_callable(array($class, $method))) {
            return new WP_Error('service_unavailable', 'Service unavailable.');
        }
        return call_user_func_array(array($class, $method), $args);
    }

    private static function value($row, $key, $default = null) {
        if (is_array($row)) { return array_key_exists($key, $row) ? $row[$key] : $default; }
        if (is_object($row)) { return isset($row->$key) ? $row->$key : $default; }
        return $default;
    }
}




