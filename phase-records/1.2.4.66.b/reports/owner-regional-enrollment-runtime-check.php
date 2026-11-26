<?php
/**
 * Focused disposable regional-only passkey eligibility check for `.66.b`.
 * Creates one synthetic commission user and exact regional assignment in the
 * isolated phase test DB, verifies eligibility and denial without assignment,
 * then removes only the generated assignment and user (including on shutdown).
 * It never creates, enrolls, or removes a passkey credential.
 */
if (!defined('WP_CLI') || !class_exists('ZMP_Passkeys') || !class_exists('ZMP_Category_Jury') || !class_exists('ZMP_Category_Registrations')) {
    WP_CLI::error('Required WP-CLI/plugin classes unavailable; no fixture created.');
}
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$user_id = 0;
$assignment_id = 0;
$clean = static function () use (&$user_id, &$assignment_id, $wpdb) {
    if ($assignment_id > 0) {
        $wpdb->delete($wpdb->prefix . 'zmp_jury_regional_assignments', array('id' => $assignment_id), array('%d'));
        $assignment_id = 0;
    }
    if ($user_id > 0 && get_userdata($user_id)) {
        wp_delete_user($user_id);
    }
    $user_id = 0;
};
register_shutdown_function($clean);

$season = ZMP_Category_Registrations::active_season();
if (!$season || empty($season->id) || get_option('zmp_regional_jury_migration_49_2') !== 'complete') {
    WP_CLI::error('NOT_RUN: active season or completed regional migration unavailable; no fixture created.');
}
$role = get_role('zmp_commission');
if (!$role || (!$role->has_cap('zmp_commission_cap') && !$role->has_cap('view_zmp_commission_dashboard'))) {
    WP_CLI::error('NOT_RUN: commission role/capability unavailable; no fixture created.');
}
$wpdb->last_error = '';
$template = $wpdb->get_row($wpdb->prepare(
    "SELECT season_id,category_id,region FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d ORDER BY id ASC LIMIT 1",
    absint($season->id)
));
if ($wpdb->last_error !== '' || !$template || !class_exists('ZMP_Regions') || !ZMP_Regions::valid_region((string) $template->region)) {
    WP_CLI::error('NOT_RUN: no valid synthetic regional assignment exists for the active season; no fixture created.');
}

$login = 'zmp66b_regional_' . strtolower(str_replace('-', '', wp_generate_uuid4()));
$created = wp_insert_user(array(
    'user_login' => $login,
    'user_email' => $login . '@example.invalid',
    'user_pass' => wp_generate_password(48, true, true),
    'role' => 'zmp_commission',
));
if (is_wp_error($created) || !$created) {
    WP_CLI::error('FAIL: synthetic regional-only user creation failed.');
}
$user_id = absint($created);
$wpdb->last_error = '';
$inserted = $wpdb->insert($wpdb->prefix . 'zmp_jury_regional_assignments', array(
    'season_id' => absint($season->id),
    'category_id' => absint($template->category_id),
    'region' => sanitize_key($template->region),
    'juror_user_id' => $user_id,
    'assigned_by' => 1,
    'assigned_at' => current_time('mysql'),
), array('%d', '%d', '%s', '%d', '%d', '%s'));
if ($inserted !== 1 || $wpdb->last_error !== '') {
    $clean();
    WP_CLI::error('FAIL: regional-only fixture assignment creation failed; cleanup attempted.');
}
$assignment_id = absint($wpdb->insert_id);

$ordinary_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND juror_user_id=%d",
    absint($season->id), $user_id
));
$has_regional_assignment = ZMP_Category_Jury::has_any_assignment(absint($season->id), absint($template->category_id), $user_id);
$eligible_with_region = ZMP_Passkeys::can_register_for_user($user_id);
$pass = $ordinary_count === 0 && $has_regional_assignment && $eligible_with_region;

$wpdb->delete($wpdb->prefix . 'zmp_jury_regional_assignments', array('id' => $assignment_id), array('%d'));
$assignment_id = 0;
$denied_without_region = !ZMP_Passkeys::can_register_for_user($user_id);
$clean();
$cleanup_ok = !get_userdata($user_id) && (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE juror_user_id=%d",
    absint($created)
)) === 0;

WP_CLI::log('regional_only_fixture_has_no_ordinary_assignment=' . ($ordinary_count === 0 ? 'PASS' : 'FAIL'));
WP_CLI::log('regional_only_assignment_recognized=' . ($pass ? 'PASS' : 'FAIL'));
WP_CLI::log('regional_only_without_assignment_rejected=' . ($denied_without_region ? 'PASS' : 'FAIL'));
WP_CLI::log('fixture_cleanup=' . ($cleanup_ok ? 'PASS' : 'FAIL'));
if (!$pass || !$denied_without_region || !$cleanup_ok) {
    WP_CLI::error('Focused regional-only runtime verification failed; inspect only this isolated synthetic site.');
}
WP_CLI::success('Regional-only eligibility verified; temporary synthetic user and assignment removed.');
