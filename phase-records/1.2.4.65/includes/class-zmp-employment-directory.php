<?php
if (!defined('ABSPATH')) { exit; }

/** Teacher+ view of owner-enabled employment fields for shared school engagements. */
final class ZMP_Employment_Directory {
    public static function init() {
        add_shortcode('zmp_employment_directory', array(__CLASS__, 'render_shortcode'));
    }

    public static function render_shortcode() {
        if (!is_user_logged_in() || !class_exists('ZMP_Mentor_School_Profile')
            || !is_callable(array('ZMP_Mentor_School_Profile', 'get_employment_directory'))) {
            return '<p class="zmp-employment-directory__notice">' . esc_html__('Најавете се со наставнички профил за да ги видите споделените податоци.', 'zmp-competitions') . '</p>';
        }
        $viewer_id = get_current_user_id();
        $rows = ZMP_Mentor_School_Profile::get_employment_directory($viewer_id, 0);
        if (is_wp_error($rows)) {
            return '<p class="zmp-employment-directory__notice" role="status">' . esc_html__('Директориумот не е достапен за оваа сметка.', 'zmp-competitions') . '</p>';
        }
        if (!is_array($rows) || !$rows) {
            return '<div class="zmp-employment-directory"><h2>' . esc_html__('Вработување по училиште', 'zmp-competitions') . '</h2><p role="status">' . esc_html__('Нема споделени податоци за училиштата со кои сте поврзани.', 'zmp-competitions') . '</p></div>';
        }

        $groups = array();
        foreach ($rows as $row) {
            $school_id = absint(self::value($row, 'school_id', 0));
            if (!$school_id) { continue; }
            if (!isset($groups[$school_id])) {
                $groups[$school_id] = array(
                    'school_name' => (string) self::value($row, 'school_name', ''),
                    'municipality' => (string) self::value($row, 'municipality', ''),
                    'items' => array(),
                );
            }
            $groups[$school_id]['items'][] = $row;
        }
        if (!$groups) {
            return '<div class="zmp-employment-directory"><h2>' . esc_html__('Вработување по училиште', 'zmp-competitions') . '</h2><p role="status">' . esc_html__('Нема споделени податоци за училиштата со кои сте поврзани.', 'zmp-competitions') . '</p></div>';
        }

        ob_start();
        echo '<section class="zmp-employment-directory" aria-labelledby="zmp-employment-directory-title"><h2 id="zmp-employment-directory-title">' . esc_html__('Вработување по училиште', 'zmp-competitions') . '</h2>';
        echo '<p class="zmp-employment-directory__privacy">' . esc_html__('Прикажани се само податоците што колегите избрале да ги споделат. Службените е-пошти не се прикажуваат во овој директориум.', 'zmp-competitions') . '</p>';
        foreach ($groups as $group) {
            echo '<section class="zmp-employment-directory__school"><h3>' . esc_html($group['school_name']);
            if ($group['municipality'] !== '') { echo ' <span class="zmp-employment-directory__municipality">' . esc_html($group['municipality']) . '</span>'; }
            echo '</h3><ul class="zmp-employment-directory__people">';
            foreach ($group['items'] as $row) { self::render_person($row); }
            echo '</ul></section>';
        }
        echo '</section>';
        return (string) ob_get_clean();
    }

    private static function render_person($row) {
        $name = (string) self::value($row, 'teacher_name', '');
        if ($name === '') { return; }
        echo '<li class="zmp-employment-directory__person"><h4>' . esc_html($name) . '</h4><dl>';
        $fields = array(
            'employment_term' => array(__('Работен однос', 'zmp-competitions'), array('fixed_term' => __('На определено време', 'zmp-competitions'), 'indefinite' => __('На неопределено време', 'zmp-competitions'))),
            'workload' => array(__('Работен фонд', 'zmp-competitions'), array('full' => __('Полн фонд', 'zmp-competitions'), 'part' => __('Неполн фонд', 'zmp-competitions'))),
            'retired' => array(__('Пензиониран статус', 'zmp-competitions'), array(1 => __('Да', 'zmp-competitions'), 0 => __('Не', 'zmp-competitions'))),
            'replacement' => array(__('Ангажман на замена', 'zmp-competitions'), array(1 => __('Да', 'zmp-competitions'), 0 => __('Не', 'zmp-competitions'))),
        );
        $printed = false;
        foreach ($fields as $key => $config) {
            if (!self::has($row, $key)) { continue; }
            $raw = self::value($row, $key, '');
            $map_key = is_bool($raw) ? (int) $raw : (string) $raw;
            if (!array_key_exists($map_key, $config[1])) { continue; }
            self::definition($config[0], $config[1][$map_key]);
            $printed = true;
        }
        foreach (array(
            'replacement_for' => __('Заменуван/а колега', 'zmp-competitions'),
            'replacement_start_date' => __('Почеток на замена', 'zmp-competitions'),
            'replacement_end_date' => __('Крај на замена', 'zmp-competitions'),
        ) as $key => $label) {
            if (!self::has($row, $key)) { continue; }
            $value = (string) self::value($row, $key, '');
            if ($value === '') { continue; }
            self::definition($label, $value);
            $printed = true;
        }
        if (!$printed) { echo '<dt class="screen-reader-text">' . esc_html__('Споделени податоци', 'zmp-competitions') . '</dt><dd>' . esc_html__('Нема споделени полиња.', 'zmp-competitions') . '</dd>'; }
        echo '</dl></li>';
    }

    private static function definition($label, $value) {
        echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd>';
    }

    private static function has($row, $key) {
        if (is_array($row)) { return array_key_exists($key, $row); }
        return is_object($row) && property_exists($row, $key);
    }

    private static function value($row, $key, $default = null) {
        if (is_array($row)) { return array_key_exists($key, $row) ? $row[$key] : $default; }
        if (is_object($row)) { return isset($row->$key) ? $row->$key : $default; }
        return $default;
    }
}
