<?php
if (!defined('ABSPATH')) { exit; }

/** Owner-controlled school profile fields rendered on the WordPress user profile. */
final class ZMP_School_Profile_UI {
    public static function init() {
        add_action('show_user_profile', array(__CLASS__, 'render'));
        add_action('edit_user_profile', array(__CLASS__, 'render'));
        add_action('personal_options_update', array(__CLASS__, 'save'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save'));
        add_action('admin_notices', array(__CLASS__, 'notice'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
    }

    public static function enqueue_styles($hook) {
        if (!in_array((string) $hook, array('profile.php', 'user-edit.php'), true) || !defined('ZMP_PLUGIN_URL')) { return; }
        wp_enqueue_style('zmp-public-css', ZMP_PLUGIN_URL . 'assets/css/zmp-public.css', array(), defined('ZMP_VERSION') ? ZMP_VERSION : null);
    }

    public static function render($user) {
        if (!is_object($user) || empty($user->ID) || !current_user_can('edit_user', (int) $user->ID)
            || !class_exists('ZMP_Mentor_School_Profile') || !is_callable(array('ZMP_Mentor_School_Profile', 'get_engagements'))) {
            return;
        }
        $user_id = (int) $user->ID;
        $engagements = ZMP_Mentor_School_Profile::get_engagements($user_id);
        if (is_wp_error($engagements)) {
            $message = $engagements->get_error_code() === 'teacher_profile_required'
                ? __('Наставничкиот профил не е поврзан со оваа сметка. Проверете дали сметката ги има улогите Subscriber и Наставник и дали има наставнички запис.', 'zmp-competitions')
                : __('Податоците за училишниот профил не се достапни.', 'zmp-competitions');
            echo '<h2>' . esc_html__('Профил по училиште', 'zmp-competitions') . '</h2><p class="notice notice-error">' . esc_html($message) . '</p>';
            return;
        }
        if (!$engagements) { return; }

        echo '<div class="zmp-school-profile"><h2>' . esc_html__('Профил по училиште', 'zmp-competitions') . '</h2>';
        echo '<p>' . esc_html__('Секој податок за видливост го контролирате одделно. Адресата за контакт е приватна и не се објавува во директориумот.', 'zmp-competitions') . '</p>';
        wp_nonce_field('zmp_school_profile_update_' . $user_id, 'zmp_school_profile_nonce', false);

        foreach ((array) $engagements as $engagement) {
            $school_id = (int) self::value($engagement, 'school_id', 0);
            $school_name = (string) self::value($engagement, 'school_name', '');
            $municipality = (string) self::value($engagement, 'municipality', '');
            $profile = self::value($engagement, 'profile', array());
            if ($school_id < 1) { continue; }
            $prefix = 'zmp-school-profile-' . $user_id . '-' . $school_id;
            echo '<fieldset class="zmp-school-profile__school"><legend>' . esc_html($school_name) . ($municipality !== '' ? ' — ' . esc_html($municipality) : '') . '</legend>';
            echo '<div class="zmp-school-profile__grid">';
            self::text_field($prefix . '-email', $school_id, 'official_email', __('Службена е-пошта', 'zmp-competitions'), $profile, 'email', 254);
            self::select_field($prefix . '-term', $school_id, 'employment_term', __('Работен однос', 'zmp-competitions'), $profile, array('' => __('Не е наведено', 'zmp-competitions'), 'fixed_term' => __('На определено време', 'zmp-competitions'), 'indefinite' => __('На неопределено време', 'zmp-competitions')));
            self::select_field($prefix . '-workload', $school_id, 'workload', __('Работен фонд', 'zmp-competitions'), $profile, array('' => __('Не е наведено', 'zmp-competitions'), 'full' => __('Полн фонд', 'zmp-competitions'), 'part' => __('Неполн фонд', 'zmp-competitions')));
            self::checkbox_field($prefix . '-retired', $school_id, 'retired', __('Пензиониран/а', 'zmp-competitions'), $profile);
            self::checkbox_field($prefix . '-replacement', $school_id, 'replacement', __('Работи како замена', 'zmp-competitions'), $profile);
            self::text_field($prefix . '-replacement-for', $school_id, 'replacement_for', __('Заменуван/а колега', 'zmp-competitions'), $profile, 'text', 190);
            self::date_field($prefix . '-replacement-start', $school_id, 'replacement_start_date', __('Почеток на замена', 'zmp-competitions'), $profile);
            self::date_field($prefix . '-replacement-end', $school_id, 'replacement_end_date', __('Крај на замена', 'zmp-competitions'), $profile);
            echo '</div><fieldset class="zmp-school-profile__visibility"><legend>' . esc_html__('Видливост во внатрешниот директориум', 'zmp-competitions') . '</legend><p>' . esc_html__('Само најавени наставници и повисоки дозволени улоги можат да ги видат полињата што ќе ги вклучите.', 'zmp-competitions') . '</p><div class="zmp-school-profile__visibility-grid">';
            foreach (self::visibility_fields() as $field => $label) {
                $visible = (bool) self::value($profile, $field, false);
                $id = $prefix . '-' . $field;
                $name = 'zmp_school_profile[' . (int) $school_id . '][' . $field . ']';
                echo '<label for="' . esc_attr($id) . '"><input type="hidden" name="' . esc_attr($name) . '" value="0"><input id="' . esc_attr($id) . '" type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($visible, true, false) . '> ' . esc_html($label) . '</label>';
            }
            echo '</div></fieldset></fieldset>';
        }
        echo '</div>';
    }

    public static function save($user_id) {
        $user_id = absint($user_id);
        if (!$user_id || !current_user_can('edit_user', $user_id) || !class_exists('ZMP_Mentor_School_Profile')
            || !is_callable(array('ZMP_Mentor_School_Profile', 'save_profile'))) { return; }
        $nonce = isset($_POST['zmp_school_profile_nonce']) && is_scalar($_POST['zmp_school_profile_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['zmp_school_profile_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'zmp_school_profile_update_' . $user_id)) { return; }
        $raw = isset($_POST['zmp_school_profile']) && is_array($_POST['zmp_school_profile']) ? wp_unslash($_POST['zmp_school_profile']) : array();
        foreach ($raw as $school_key => $values) {
            $school_id = filter_var((string) $school_key, FILTER_VALIDATE_INT);
            if ($school_id === false || $school_id < 1 || !is_array($values)) { continue; }
            $payload = array();
            foreach (array('official_email', 'employment_term', 'workload', 'replacement_for', 'replacement_start_date', 'replacement_end_date') as $field) {
                if (isset($values[$field]) && is_scalar($values[$field])) {
                    $payload[$field] = $field === 'official_email'
                        ? (string) $values[$field]
                        : sanitize_text_field((string) $values[$field]);
                }
            }
            foreach (array('retired', 'replacement') as $field) {
                $payload[$field] = isset($values[$field]) && is_scalar($values[$field]) && (string) $values[$field] === '1' ? 1 : 0;
            }
            foreach (array_keys(self::visibility_fields()) as $field) {
                $payload[$field] = isset($values[$field]) && is_scalar($values[$field]) && (string) $values[$field] === '1' ? 1 : 0;
            }
            $result = ZMP_Mentor_School_Profile::save_profile($user_id, (int) $school_id, $payload);
            if (is_wp_error($result)) {
                set_transient(self::notice_key($user_id), 'error', 60);
                return;
            }
        }
        set_transient(self::notice_key($user_id), 'success', 60);
    }

    public static function notice() {
        $user_id = get_current_user_id();
        if (!$user_id || !current_user_can('edit_user', $user_id)) { return; }
        $status = get_transient(self::notice_key($user_id));
        if (!$status) { return; }
        delete_transient(self::notice_key($user_id));
        $class = $status === 'success' ? 'notice-success' : 'notice-error';
        $message = $status === 'success' ? __('Профилите по училиште се зачувани.', 'zmp-competitions') : __('Профилот не беше зачуван. Проверете ги полињата и обидете се повторно.', 'zmp-competitions');
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    private static function text_field($id, $school_id, $field, $label, $profile, $type, $max) {
        $name = 'zmp_school_profile[' . (int) $school_id . '][' . $field . ']';
        echo '<div class="zmp-school-profile__field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label><input id="' . esc_attr($id) . '" type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" maxlength="' . (int) $max . '" value="' . esc_attr((string) self::value($profile, $field, '')) . '"></div>';
    }

    private static function date_field($id, $school_id, $field, $label, $profile) {
        echo '<div class="zmp-school-profile__field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label><input id="' . esc_attr($id) . '" type="date" name="zmp_school_profile[' . (int) $school_id . '][' . esc_attr($field) . ']" value="' . esc_attr((string) self::value($profile, $field, '')) . '"></div>';
    }

    private static function select_field($id, $school_id, $field, $label, $profile, array $options) {
        $selected = (string) self::value($profile, $field, '');
        echo '<div class="zmp-school-profile__field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label><select id="' . esc_attr($id) . '" name="zmp_school_profile[' . (int) $school_id . '][' . esc_attr($field) . ']">';
        foreach ($options as $value => $text) { echo '<option value="' . esc_attr((string) $value) . '" ' . selected($selected, (string) $value, false) . '>' . esc_html($text) . '</option>'; }
        echo '</select></div>';
    }

    private static function checkbox_field($id, $school_id, $field, $label, $profile) {
        $name = 'zmp_school_profile[' . (int) $school_id . '][' . $field . ']';
        echo '<div class="zmp-school-profile__field zmp-school-profile__field--check"><input type="hidden" name="' . esc_attr($name) . '" value="0"><label for="' . esc_attr($id) . '"><input id="' . esc_attr($id) . '" type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked((bool) self::value($profile, $field, false), true, false) . '> ' . esc_html($label) . '</label></div>';
    }

    private static function visibility_fields() {
        return array(
            'employment_term_visible' => __('Работен однос', 'zmp-competitions'),
            'workload_visible' => __('Работен фонд', 'zmp-competitions'),
            'retired_visible' => __('Пензиониран статус', 'zmp-competitions'),
            'replacement_visible' => __('Ангажман на замена', 'zmp-competitions'),
            'replacement_for_visible' => __('Заменуван/а колега', 'zmp-competitions'),
            'replacement_start_date_visible' => __('Почеток на замена', 'zmp-competitions'),
            'replacement_end_date_visible' => __('Крај на замена', 'zmp-competitions'),
        );
    }

    private static function value($row, $key, $default = null) {
        if (is_array($row)) { return array_key_exists($key, $row) ? $row[$key] : $default; }
        if (is_object($row)) { return isset($row->$key) ? $row->$key : $default; }
        return $default;
    }

    private static function notice_key($user_id) { return 'zmp_school_profile_notice_' . absint($user_id); }
}
