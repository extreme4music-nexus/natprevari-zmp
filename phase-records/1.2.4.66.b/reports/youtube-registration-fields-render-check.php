<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Entry_YouTube_Fields') || !class_exists('ZMP_Category_Registrations')) {
    WP_CLI::error('local registration runtime unavailable');
}
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;

$season_id = 1;
$category_id = 2;
$region = 'polog';
$mentor_table = $wpdb->prefix . 'zmp_mentors';
$school_table = $wpdb->prefix . 'zmp_mentor_schools';
$event_table = $wpdb->prefix . 'zmp_stage_regional_events';
$baseline = array(
    'mentors' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $mentor_table"),
    'schools' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $school_table"),
    'events' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $event_table"),
);
if ($wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $event_table WHERE season_id=%d AND category_id=%d AND phase=%s AND region=%s",
    $season_id, $category_id, 'regional', $region
))) { WP_CLI::error('chosen synthetic school region already has a delivery row'); }

$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_teacher_youtube_' . strtolower($suffix);
$user_id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(40, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic YouTube Registration Teacher',
    'role' => 'subscriber',
));
if (is_wp_error($user_id)) { WP_CLI::error('synthetic teacher creation failed'); }
$user = new WP_User($user_id);
$user->add_role('zmp_teacher');
clean_user_cache($user_id);
$mentor_id = 0;
$school_id = 0;
$event_id = 0;
$result = array();

try {
    $mentor_key = hash('sha256', 'synthetic-youtube-mentor-' . $suffix);
    if ($wpdb->insert($mentor_table, array(
        'wp_user_id' => (int) $user_id,
        'full_name' => 'Synthetic Test Teacher',
        'email' => $login . '@example.invalid',
        'phone' => '',
        'school_name' => 'Synthetic Video School',
        'municipality' => 'Tetovo',
        'region' => 'Полошки',
        'login_token' => $mentor_key,
        'created_at' => current_time('mysql'),
    )) === false) { throw new RuntimeException('synthetic_mentor_insert_failed'); }
    $mentor_id = (int) $wpdb->insert_id;
    $mentor = $wpdb->get_row($wpdb->prepare("SELECT id,school_name,municipality,region FROM $mentor_table WHERE id=%d", $mentor_id));
    $school_id = (int) ZMP_Mentor_Schools::ensure_primary($mentor);
    if (!$school_id || !ZMP_Mentor_Schools::owned_school($mentor_id, $school_id)) { throw new RuntimeException('synthetic_primary_school_setup_failed'); }
    if ($wpdb->insert($event_table, array(
        'season_id' => $season_id,
        'category_id' => $category_id,
        'phase' => 'regional',
        'region' => $region,
        'starts_at' => null,
        'ends_at' => null,
        'delivery_mode' => 'online',
        'location' => '',
        'online_details' => '',
        'moderator_notes' => '',
        'updated_by' => (int) $user_id,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    )) === false) { throw new RuntimeException('synthetic_delivery_insert_failed'); }
    $event_id = (int) $wpdb->insert_id;

    wp_set_current_user($user_id);
    $output = do_shortcode('[' . ZMP_Category_Registrations::SHORTCODE . ']');
    $scripts = wp_scripts();
    $registered = $scripts->query('zmp-entry-youtube-fields', 'registered');
    $queued = in_array('zmp-entry-youtube-fields', (array) $scripts->queue, true);
    $config_data = array();
    if (preg_match('/data-config="([^"]+)"/', $output, $config_match)) {
        $config_data = json_decode(html_entity_decode($config_match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
    }
    $result = array(
        'synthetic_teacher_capability' => current_user_can('zmp_teacher_cap'),
        'category_registration_form_rendered' => strpos($output, 'zmp-category-registration') !== false
            && strpos($output, 'name="category_id"') !== false,
        'youtube_fields_present' => strpos($output, 'id="zmp-entry-video-fields"') !== false
            && strpos($output, 'Видеа за натпреварот') !== false,
        'configured_school_category_mode_in_markup' => isset($config_data['modes'][(string) $school_id][(string) $category_id])
            && $config_data['modes'][(string) $school_id][(string) $category_id] === 'online',
        'versioned_script_registered_and_queued' => (bool) $registered && $queued,
        'script_source_points_to_phase_asset' => $registered && strpos((string) $registered->src, 'assets/js/zmp-entry-youtube-fields.js') !== false,
        'script_version_matches_asset_mtime' => $registered && (string) $registered->ver === (string) filemtime(ZMP_PLUGIN_DIR . 'assets/js/zmp-entry-youtube-fields.js'),
        'youtube_fields_initially_hidden_for_js_activation' => strpos($output, 'id="zmp-entry-video-fields"') !== false
            && (bool) preg_match('/<fieldset[^>]*id="zmp-entry-video-fields"[^>]*\shidden(?:\s|>)/', $output),
    );
} catch (Throwable $e) {
    $result['test_error_code'] = $e->getMessage();
} finally {
    if ($event_id) { $wpdb->delete($event_table, array('id' => $event_id)); }
    if ($school_id) { $wpdb->delete($school_table, array('id' => $school_id, 'mentor_id' => $mentor_id)); }
    if ($mentor_id) { $wpdb->delete($mentor_table, array('id' => $mentor_id, 'wp_user_id' => (int) $user_id)); }
    wp_delete_user($user_id);
}

$result['mentor_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $mentor_table") === $baseline['mentors'];
$result['school_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $school_table") === $baseline['schools'];
$result['regional_event_count_restored'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $event_table") === $baseline['events'];
$result['synthetic_school_removed'] = !$school_id || !(bool) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $school_table WHERE id=%d AND mentor_id=%d", $school_id, $mentor_id));
$result['synthetic_user_removed'] = !get_userdata($user_id);
WP_CLI::log(wp_json_encode($result));
foreach ($result as $key => $value) {
    if (is_bool($value) && !$value) { WP_CLI::error('render assertion failed: ' . $key); }
}
if (isset($result['test_error_code'])) { WP_CLI::error('synthetic setup failed'); }
