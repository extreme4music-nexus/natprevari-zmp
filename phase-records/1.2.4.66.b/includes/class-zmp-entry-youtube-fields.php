<?php
/** Integrated from the Owner-confirmed temporary entry video fields add-on; phase .66.b. */
if (!defined('ABSPATH')) { exit; }

final class ZMP_Entry_YouTube_Fields {
    const VERSION = '0.1.0';

    public static function init() {
        add_filter('do_shortcode_tag', array(__CLASS__, 'inject_registration_video_fields'), 20, 4);
    }

    public static function inject_registration_video_fields($output, $tag, $attr, $match) {
        if ($tag !== 'zmp_category_entries' || !is_string($output) || strpos($output, 'zmp-category-registration') === false) {
            return $output;
        }
        if (!class_exists('ZMP_YouTube') || !class_exists('ZMP_Category_Registrations') || !is_user_logged_in()) {
            return $output;
        }

        $form_end = strpos($output, '</form>');
        if ($form_end === false) { return $output; }
        $form_start = strrpos(substr($output, 0, $form_end), '<form');
        if ($form_start === false) { return $output; }
        $form_markup = substr($output, $form_start, $form_end - $form_start);
        if (strpos($form_markup, 'name="category_id"') === false) { return $output; }

        $season = ZMP_Category_Registrations::active_season();
        if (!$season) { return $output; }
        $schools = self::owned_schools();
        $categories = self::active_music_categories((int) $season->id);
        $modes = array();
        foreach ($schools as $school) {
            foreach ($categories as $category) {
                $modes[(string) $school->id][(string) $category->id] = ZMP_YouTube::regional_delivery_mode((int) $season->id, (int) $category->id, (string) $school->region);
            }
        }

        $connected = ZMP_YouTube::is_connected();
        $config = wp_json_encode(array('modes' => $modes, 'connected' => (bool) $connected));
        $config = str_replace('</', '<\\/', (string) $config);
        $markup = '<fieldset class="zmp-entry-video-fields" id="zmp-entry-video-fields" data-config="' . esc_attr($config) . '" hidden>'
            . '<legend>Видеа за натпреварот</legend>'
            . '<p class="zmp-entry-video-guidance" aria-live="polite"></p>'
            . '<div class="zmp-entry-video-slots"></div>'
            . '<p class="zmp-entry-video-progress" aria-live="polite"></p>'
            . '</fieldset>';
        $script_path = ZMP_PLUGIN_DIR . 'assets/js/zmp-entry-youtube-fields.js';
        wp_enqueue_script(
            'zmp-entry-youtube-fields',
            ZMP_PLUGIN_URL . 'assets/js/zmp-entry-youtube-fields.js',
            array('zmp-public-js'),
            file_exists($script_path) ? (string) filemtime($script_path) : ZMP_VERSION,
            true
        );
        $markup = '<style>.zmp-entry-video-fields{margin:1.25rem 0;padding:1rem;border:1px solid #c3c4c7;border-radius:8px}.zmp-entry-video-fields legend{font-weight:600}.zmp-entry-video-slot{display:block;margin:.75rem 0}.zmp-entry-video-slot input{display:block;max-width:100%;margin-top:.35rem}.zmp-entry-video-progress{font-weight:600}</style>' . $markup;
        $output = substr_replace($output, $markup, $form_end, 0);
        return $output;
    }

    private static function owned_schools() {
        if (!class_exists('ZMP_Mentor_Schools')) { return array(); }
        global $wpdb;
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d", get_current_user_id()));
        return $mentor ? (array) ZMP_Mentor_Schools::for_mentor((int) $mentor->id) : array();
    }

    private static function active_music_categories($season_id) {
        global $wpdb;
        return (array) $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.id,c.category_code FROM {$wpdb->prefix}zmp_categories c
             JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1
             WHERE c.is_active=1 AND c.category_code<>'knowledge_test'",
            $season_id
        ));
    }

}

add_action('plugins_loaded', array('ZMP_Entry_YouTube_Fields', 'init'), 30);

