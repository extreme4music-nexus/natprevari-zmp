<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Moderator_Operations') || !class_exists('ZMP_Shortcodes')) {
    WP_CLI::error('local runtime unavailable');
}

require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$season_id = 1;
$region = 'polog';
$category = null;
foreach ($wpdb->get_results($wpdb->prepare(
    "SELECT c.id,c.category_code FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 ORDER BY c.id",
    $season_id
)) as $candidate) {
    if ($candidate->category_code === 'knowledge_test') { continue; }
    $entry_count = ZMP_Category_Registrations::count_for_category($season_id, (int) $candidate->id);
    $phases = ZMP_Competition_Structure::evaluate_phases($season_id, (int) $candidate->id, $entry_count);
    if (!empty($phases['regional']['available'])) { $category = $candidate; break; }
}
if (!$category) { WP_CLI::error('no active synthetic music regional scope'); }

$event_table = $wpdb->prefix . 'zmp_stage_regional_events';
$event_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $event_table WHERE season_id=%d AND category_id=%d AND phase=%s AND region=%s",
    $season_id, (int) $category->id, 'regional', $region
));
if ($event_exists) { WP_CLI::error('chosen synthetic region scope already exists'); }

$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_moderator_' . strtolower($suffix);
$user_id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => wp_generate_password(40, true, true),
    'user_email' => $login . '@example.invalid',
    'display_name' => 'Synthetic Moderator UI Check',
    'role' => 'subscriber',
));
if (is_wp_error($user_id)) { WP_CLI::error('synthetic moderator creation failed'); }
$user = new WP_User($user_id);
$user->add_role('zmp_moderator');
$user = get_userdata($user_id);
$has_required_roles = in_array('subscriber', (array) $user->roles, true)
    && in_array('zmp_moderator', (array) $user->roles, true)
    && user_can($user, 'zmp_moderator_cap');

$_GET['zmp_portal_tab'] = 'moderatori';
$_GET['zmp_moderator_tab'] = 'delivery';
$_GET['zmp_ops_season'] = (string) $season_id;
$_GET['zmp_ops_category'] = (string) $category->id;
$_GET['zmp_ops_phase'] = 'regional';
$_GET['zmp_ops_region'] = $region;
wp_set_current_user($user_id);
$moderator_html = ZMP_Shortcodes::render_natprevari_portal();
$main_menu_ok = strpos($moderator_html, '>Модератори</a>') !== false;
$child_menu_ok = strpos($moderator_html, 'Начин на одржување') !== false;
$region_scope_ok = strpos($moderator_html, 'name="zmp_ops_region"') !== false
    && strpos($moderator_html, 'value="' . esc_attr($region) . '"') !== false;
$music_modes_ok = strpos($moderator_html, '<option value="physical"') !== false
    && strpos($moderator_html, '<option value="online"') !== false
    && strpos($moderator_html, '<option value="hybrid"') === false;

$user->remove_role('zmp_moderator');
clean_user_cache($user_id);
wp_set_current_user(0);
wp_set_current_user($user_id);
$subscriber_user = wp_get_current_user();
$subscriber_is_only_subscriber = (array) $subscriber_user->roles === array('subscriber')
    && !current_user_can('zmp_moderator_cap')
    && !current_user_can('manage_options');
$subscriber_html = ZMP_Shortcodes::render_natprevari_portal();
$subscriber_denied = $subscriber_is_only_subscriber
    && strpos($subscriber_html, '>Модератори</a>') === false
    && strpos($subscriber_html, 'Начин на одржување') === false;
$user->add_role('zmp_moderator');
clean_user_cache($user_id);
wp_set_current_user(0);
wp_set_current_user($user_id);

$result = array(
    'subscriber_and_moderator_roles' => $has_required_roles,
    'main_portal_menu_keeps_moderatori_label' => $main_menu_ok,
    'delivery_submenu_rendered' => $child_menu_ok,
    'regional_scope_selector_rendered' => $region_scope_ok,
    'regional_music_limits_to_physical_or_online' => $music_modes_ok,
    'subscriber_role_revocation_refreshed' => $subscriber_is_only_subscriber,
    'subscriber_only_cannot_see_moderator_menu' => $subscriber_denied,
    'scope' => array('season_id' => $season_id, 'category_id' => (int) $category->id, 'phase' => 'regional', 'region' => $region),
);
foreach ($result as $key => $value) {
    if (is_bool($value) && !$value) {
        wp_delete_user($user_id);
        WP_CLI::error('render assertion failed: ' . $key);
    }
}

$_POST = array(
    'zmp_operations_action' => 'save_delivery_mode',
    'zmp_operations_nonce' => wp_create_nonce(ZMP_Moderator_Operations::NONCE_ACTION),
    'season_id' => (string) $season_id,
    'category_id' => (string) $category->id,
    'phase' => 'regional',
    'region' => $region,
    'delivery_mode' => 'online',
);
$_REQUEST = $_POST;
$result['http_handler_save_readback_online'] = false;
$result['synthetic_audit_created'] = false;
$result['synthetic_event_removed'] = false;
$result['synthetic_audit_removed'] = false;
$result['synthetic_user_removed'] = false;
register_shutdown_function(static function () use (&$result, $wpdb, $event_table, $season_id, $category, $region, $user_id) {
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id,delivery_mode,updated_by FROM $event_table WHERE season_id=%d AND category_id=%d AND phase=%s AND region=%s",
        $season_id, (int) $category->id, 'regional', $region
    ));
    if ($row) {
        $result['http_handler_save_readback_online'] = $row->delivery_mode === 'online' && (int) $row->updated_by === (int) $user_id;
        $audit_table = $wpdb->prefix . 'zmp_operations_audit';
        $result['synthetic_audit_created'] = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $audit_table WHERE season_id=%d AND category_id=%d AND phase=%s AND actor_user_id=%d AND action=%s AND object_id=%d",
            $season_id, (int) $category->id, 'regional', (int) $user_id, 'set_delivery_mode', (int) $row->id
        ));
        $wpdb->delete($audit_table, array(
            'season_id' => $season_id,
            'category_id' => (int) $category->id,
            'phase' => 'regional',
            'actor_user_id' => (int) $user_id,
            'action' => 'set_delivery_mode',
            'object_id' => (int) $row->id,
        ));
        $result['synthetic_audit_removed'] = !(bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $audit_table WHERE actor_user_id=%d AND action=%s AND object_id=%d",
            (int) $user_id, 'set_delivery_mode', (int) $row->id
        ));
        $result['synthetic_event_removed'] = $wpdb->delete($event_table, array('id' => (int) $row->id)) === 1;
    }
    wp_delete_user($user_id);
    $result['synthetic_user_removed'] = !get_userdata($user_id);
    echo "ZMP_SYNTHETIC_RESULT=" . wp_json_encode($result) . PHP_EOL;
});

ZMP_Moderator_Operations::handle_request();
WP_CLI::error('save handler returned without redirect/exit');
