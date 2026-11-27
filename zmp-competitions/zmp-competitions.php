<?php
/**
 * Plugin Name: ЗМП Натпревари - Музичко Образование (ZMP Competitions)
 * Plugin URI:  https://muzika.org.mk
 * Description: Комплетен систем за организација на натпревари по музичко образование (училишни, општински, регионални, државни). Двојазичен (MK/SQ), плико систем со кодови, банка на прашања со аудио и ноти, автоматско бодување, рангирање и Excel експорт.
 * Version:     1.2.4.66.a
 * Author:      Акредитирано здружение на музички педагози на Македонија
 * Author URI:  https://muzika.org.mk
 * Text Domain: zmp-competitions
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Директен пристап не е дозволен
}

define('ZMP_VERSION', '1.2.4.66.a');
define('ZMP_SCHEMA_VERSION', '1.2.4.66.a');
define('ZMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZMP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Keep the WebAuthn dependency isolated from the plugin's existing Composer vendor tree.
$zmp_webauthn_autoload = ZMP_PLUGIN_DIR . 'vendor/webauthn/vendor/autoload.php';
// The isolated WebAuthn 5.3.9 dependency requires PHP 8.2. Keep unrelated plugin startup
// parse-compatible on older PHP versions and fail passkey operations closed there.
if (PHP_VERSION_ID >= 80200 && is_readable($zmp_webauthn_autoload)) {
    require_once $zmp_webauthn_autoload;
}

// Вклучување на основните класи
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-database.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-passkey-store.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-passkeys.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-passkey-login-ui.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-regions.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-schools.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-school-profile.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-subjects.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-subject-proposals.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-competition-structure.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-category-registrations.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-matrix-catalog.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-approved-audio-tools.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-moderator-operations.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-category-jury.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-document-review.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-registration-windows.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-region-requests.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-google-drive.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-youtube.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-communications.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-role-messenger.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-mandate.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-public-display.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-governance.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-subject-approvals.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-school-email-access.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-school-profile-ui.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-employment-directory.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-school-email-access-ui.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-dashboard.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-teacher-register.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-president-facsimile-certificates.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-circular-invitations.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-committees-organizers.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-registration-requirements.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-fee-invoices.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-credentials.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-debug.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-csv-safety.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-scoring.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-pdf.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-support.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor-category-results.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-mentor.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-shortcodes.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-governance.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-certificate-templates.php';
require_once ZMP_PLUGIN_DIR . 'includes/class-zmp-exam.php';
require_once ZMP_PLUGIN_DIR . 'admin/class-zmp-admin.php';
require_once ZMP_PLUGIN_DIR . 'admin/class-zmp-president-mandate-admin.php';
require_once ZMP_PLUGIN_DIR . 'admin/class-zmp-passkey-profile.php';

ZMP_Passkey_Store::init();
ZMP_Passkeys::init();
ZMP_Passkey_Login_UI::init();
ZMP_Passkey_Profile::init();

ZMP_School_Email_Access::register_hooks();
ZMP_School_Profile_UI::init();
ZMP_Employment_Directory::init();
ZMP_School_Email_Access_UI::init();

/**
 * Активација на додатокот - креирање на табели во базата и регистрација на улоги
 */
function zmp_competitions_log_schema_prerequisite_failure($component) {
    $allowed = array(
        'messenger_install','mentor_schools_install','mentor_subjects_install','subject_proposals_install','region_requests_install',
        'school_profile_install','school_email_install','registration_requirements_api','registration_facsimile_schema',
        'messenger_schema_api','messenger_schema_contract','messenger_retention_audit_schema','subject_proposals_api',
        'subject_proposals_schema','school_profile_api','school_profile_schema','school_email_api','school_email_schema',
        'retry_option_cleanup','database_marker_read','database_marker_write','database_marker_readback','database_marker_postcondition'
    );
    if (!in_array($component, $allowed, true)) { $component = 'messenger_install'; }
    if (class_exists('ZMP_Debug')) {
        ZMP_Debug::log('error', 'Plugin schema prerequisite not ready.', array(
            'schema_component' => 'messenger',
            'schema_issue' => 'prerequisite_not_ready',
            'schema_expected' => 'plugin schema upgrade prerequisite ready',
            'schema_actual' => 'not_ready',
            'schema_prerequisite_component' => $component,
            'schema_prerequisite_check_state' => 'blocked',
            'outcome' => 'fail_closed',
            'error_code' => 'schema_not_ready',
        ), 'communications');
    }
    if (class_exists('ZMP_Role_Messenger') && is_callable(array('ZMP_Role_Messenger', 'mark_schema_retry_pending'))) {
        ZMP_Role_Messenger::mark_schema_retry_pending();
    }
    return false;
}

function zmp_competitions_commit_messenger_schema($messenger_schema_ready, $school_profile_ready, $school_email_access_ready, $install_results = array()) {
    foreach ((array) $install_results as $component => $ready) {
        if (!$ready) { return zmp_competitions_log_schema_prerequisite_failure($component); }
    }
    if (!$messenger_schema_ready) { return zmp_competitions_log_schema_prerequisite_failure('messenger_install'); }
    if (!class_exists('ZMP_Registration_Requirements')
        || !is_callable(array('ZMP_Registration_Requirements', 'admin_facsimile_schema_is_compatible'))) {
        return zmp_competitions_log_schema_prerequisite_failure('registration_requirements_api');
    }
    if (!ZMP_Registration_Requirements::admin_facsimile_schema_is_compatible()) {
        return zmp_competitions_log_schema_prerequisite_failure('registration_facsimile_schema');
    }
    if (!is_callable(array('ZMP_Role_Messenger', 'schema_is_compatible'))) {
        return zmp_competitions_log_schema_prerequisite_failure('messenger_schema_api');
    }
    if (!ZMP_Role_Messenger::schema_is_compatible(true)) {
        return zmp_competitions_log_schema_prerequisite_failure('messenger_schema_contract');
    }
    if (!is_callable(array('ZMP_Role_Messenger', 'retention_audit_schema_is_compatible'))) {
        return zmp_competitions_log_schema_prerequisite_failure('messenger_schema_api');
    }
    if (!ZMP_Role_Messenger::retention_audit_schema_is_compatible(true)) {
        return zmp_competitions_log_schema_prerequisite_failure('messenger_retention_audit_schema');
    }
    if (!class_exists('ZMP_Mentor_Subject_Proposals')
        || !is_callable(array('ZMP_Mentor_Subject_Proposals', 'schema_is_compatible'))) {
        return zmp_competitions_log_schema_prerequisite_failure('subject_proposals_api');
    }
    if (!ZMP_Mentor_Subject_Proposals::schema_is_compatible()) {
        return zmp_competitions_log_schema_prerequisite_failure('subject_proposals_schema');
    }
    if (!$school_profile_ready) { return zmp_competitions_log_schema_prerequisite_failure('school_profile_install'); }
    if (!class_exists('ZMP_Mentor_School_Profile')) {
        return zmp_competitions_log_schema_prerequisite_failure('school_profile_api');
    }
    if (!ZMP_Mentor_School_Profile::schema_is_compatible()) {
        return zmp_competitions_log_schema_prerequisite_failure('school_profile_schema');
    }
    if (!$school_email_access_ready) { return zmp_competitions_log_schema_prerequisite_failure('school_email_install'); }
    if (!class_exists('ZMP_School_Email_Access')) {
        return zmp_competitions_log_schema_prerequisite_failure('school_email_api');
    }
    if (!ZMP_School_Email_Access::schema_is_compatible()) {
        return zmp_competitions_log_schema_prerequisite_failure('school_email_schema');
    }

    // Clear and verify Messenger's retry intent before advancing the shared DB
    // marker. If storage cannot confirm this state, retain the old DB version.
    delete_option('zmp_role_messenger_schema_retry');
    $retry_read_ok = false;
    $retry_value = ZMP_Role_Messenger::schema_option_read('zmp_role_messenger_schema_retry', $retry_read_ok);
    if (!$retry_read_ok || $retry_value !== null || ZMP_Role_Messenger::schema_retry_pending()) {
        return zmp_competitions_log_schema_prerequisite_failure('retry_option_cleanup');
    }

    // Capture the current marker state before handling the general DB version.
    // A false result is expected on a forward upgrade from an older schema.
    $marker_was_current = ZMP_Role_Messenger::schema_marker_is_current();
    // update_option() returns false both for storage failure and for an
    // unchanged value. Avoid a no-op write when the marker is already current;
    // otherwise require both a successful write result and a direct readback.
    $marker_read_ok = false;
    $previous_marker = ZMP_Role_Messenger::schema_option_read('zmp_db_version', $marker_read_ok);
    if (!$marker_read_ok || ($marker_was_current && $previous_marker !== ZMP_SCHEMA_VERSION)) {
        return zmp_competitions_log_schema_prerequisite_failure('database_marker_read');
    }
    if ($previous_marker !== ZMP_SCHEMA_VERSION) {
        $updated = update_option('zmp_db_version', ZMP_SCHEMA_VERSION);
        $saved_read_ok = false;
        $saved_marker = ZMP_Role_Messenger::schema_option_read('zmp_db_version', $saved_read_ok);
        if (!$updated || !$saved_read_ok || $saved_marker !== ZMP_SCHEMA_VERSION) {
            if ($previous_marker === null) { delete_option('zmp_db_version'); }
            else { update_option('zmp_db_version', $previous_marker); }
            return zmp_competitions_log_schema_prerequisite_failure('database_marker_write');
        }
    }
    if (!ZMP_Role_Messenger::schema_marker_is_current() || ZMP_Role_Messenger::schema_retry_pending()) {
        if ($previous_marker === null) { delete_option('zmp_db_version'); }
        else { update_option('zmp_db_version', $previous_marker); }
        return zmp_competitions_log_schema_prerequisite_failure('database_marker_postcondition');
    }
    delete_option('zmp_role_messenger_schema_retry_after');
    return true;
}

function zmp_competitions_activate() {
    if (ZMP_Role_Messenger::mark_schema_retry_pending() && ZMP_Role_Messenger::acquire_schema_migration_lock()) {
        try {
            // Another request may have cleared the first intent before this lock was acquired.
            if (ZMP_Role_Messenger::mark_schema_retry_pending() && ZMP_Role_Messenger::defer_schema_retry()) {
                ZMP_Database::create_tables();
                $mentor_schools_ready = ZMP_Mentor_Schools::install();
                $school_profile_ready = ZMP_Mentor_School_Profile::install();
                $mentor_subjects_ready = ZMP_Mentor_Subjects::install();
                $subject_proposals_ready = ZMP_Mentor_Subject_Proposals::install();
                $school_email_access_ready = ZMP_School_Email_Access::install();
                ZMP_Competition_Structure::install();
                ZMP_Category_Registrations::install();
                ZMP_Matrix_Catalog::install();
                ZMP_Moderator_Operations::install();
                ZMP_Category_Jury::install();
                ZMP_Registration_Windows::install();
                $region_requests_ready = ZMP_Region_Requests::install();
                ZMP_Google_Drive::install();
                ZMP_YouTube::install();
                ZMP_Communications::install();
                $messenger_schema_ready = ZMP_Role_Messenger::install();
                ZMP_President_Mandate::install();
                ZMP_President_Governance::install();
                ZMP_President_Facsimile_Certificates::install();
                ZMP_Circular_Invitations::install();
                ZMP_Committees_Organizers::install();
                ZMP_Registration_Requirements::install();
                ZMP_Fee_Invoices::install();
                ZMP_Document_Review::install();
                ZMP_Credentials::migrate_legacy_pins();
                ZMP_Database::insert_initial_data();
                zmp_competitions_commit_messenger_schema($messenger_schema_ready && $mentor_schools_ready && $mentor_subjects_ready && $subject_proposals_ready && $region_requests_ready, $school_profile_ready, $school_email_access_ready, array(
                    'messenger_install' => $messenger_schema_ready,
                    'mentor_schools_install' => $mentor_schools_ready,
                    'mentor_subjects_install' => $mentor_subjects_ready,
                    'subject_proposals_install' => $subject_proposals_ready,
                    'region_requests_install' => $region_requests_ready,
                    'school_profile_install' => $school_profile_ready,
                    'school_email_install' => $school_email_access_ready,
                ));
            }
        } finally {
            if (!ZMP_Role_Messenger::release_schema_migration_lock()) { ZMP_Role_Messenger::mark_schema_retry_pending(); }
        }
    }

    // 1. Улога: Наставник по музичко образование
    add_role('zmp_teacher', 'Наставник по музичко образование', array(
        'read' => true,
        'zmp_teacher_cap' => true,
        'view_zmp_teacher_dashboard' => true
    ));

    // 2. Улога: Тестатор (Дежурен наставник во сала за тестирање)
    add_role('zmp_testator', 'Тестатор (Дежурен во сала)', array(
        'read'                 => true,
        'zmp_testator_cap'     => true,
        'view_zmp_testator_dashboard' => true
    ));

    // 3. Улога: Модератор на натпревар (Техничка поддршка / Операции)
    add_role('zmp_moderator', 'Модератор на натпревар', array(
        'read'                  => true,
        'zmp_moderator_cap'    => true,
        'view_zmp_moderator_dashboard' => true
    ));

    // 4. Улога: Испитна комисија (Највисоко стручно тело)
    add_role('zmp_commission', 'Член на Испитна комисија', array(
        'read'                   => true,
        'zmp_commission_cap'    => true,
        'view_zmp_commission_dashboard' => true
    ));
    add_role('zmp_organizer', 'Организатор на натпревар', array(
        'read' => true,
        'zmp_organizer_cap' => true,
        'view_zmp_organizer_dashboard' => true
    ));
    zmp_competitions_sync_role_caps();
}
register_activation_hook(__FILE__, 'zmp_competitions_activate');
register_deactivation_hook(__FILE__, array('ZMP_Approved_Audio_Tools', 'deactivate'));
register_deactivation_hook(__FILE__, array('ZMP_President_Mandate', 'deactivate'));

/** Keep custom dashboard access limited to the matching operational role. */
function zmp_competitions_sync_role_caps() {
    $role_definitions = array(
        'zmp_teacher' => array('Наставник по музичко образование', array('zmp_teacher_cap'=>true,'view_zmp_teacher_dashboard'=>true)),
        'zmp_testator' => array('Тестатор (Дежурен во сала)', array('zmp_testator_cap'=>true,'view_zmp_testator_dashboard'=>true)),
        'zmp_moderator' => array('Модератор на натпревар', array('zmp_moderator_cap'=>true,'view_zmp_moderator_dashboard'=>true)),
        'zmp_commission' => array('Член на Испитна комисија', array('zmp_commission_cap'=>true,'view_zmp_commission_dashboard'=>true)),
        'zmp_organizer' => array('Организатор на натпревар', array('zmp_organizer_cap'=>true,'view_zmp_organizer_dashboard'=>true)),
        'zmp_president' => array('Претседател', array('view_zmp_president_dashboard'=>true)),
    );
    foreach ($role_definitions as $role_name => $definition) { add_role($role_name, $definition[0], array_merge(array('read'=>true), $definition[1])); }
    $role_caps = array(
        'zmp_teacher' => array('zmp_teacher_cap', 'view_zmp_teacher_dashboard'),
        'zmp_testator' => array('zmp_testator_cap', 'view_zmp_testator_dashboard'),
        'zmp_moderator' => array('zmp_moderator_cap', 'view_zmp_moderator_dashboard'),
        'zmp_commission' => array('zmp_commission_cap', 'view_zmp_commission_dashboard'),
        'zmp_organizer' => array('zmp_organizer_cap', 'view_zmp_organizer_dashboard'),
        'zmp_president' => array('read', 'view_zmp_president_dashboard'),
    );
    foreach ($role_caps as $role_name => $capabilities) {
        $role = get_role($role_name);
        if (!$role) { continue; }
        foreach ($capabilities as $capability) { $role->add_cap($capability); }
        foreach (array('view_zmp_testator_dashboard', 'view_zmp_moderator_dashboard', 'view_zmp_commission_dashboard', 'view_zmp_organizer_dashboard', 'view_zmp_president_dashboard') as $restricted_capability) {
            if (!in_array($restricted_capability, $capabilities, true)) { $role->remove_cap($restricted_capability); }
        }
    }
    $president = get_role('zmp_president');
    if ($president) {
        foreach ((array)$president->capabilities as $capability => $granted) {
            if (!in_array($capability, array('read','view_zmp_president_dashboard'), true)) { $president->remove_cap($capability); }
        }
        $president->add_cap('read');
        $president->add_cap('view_zmp_president_dashboard');
    }
    $administrator = get_role('administrator');
    if ($administrator) {
        foreach (array('view_zmp_testator_dashboard', 'view_zmp_moderator_dashboard', 'view_zmp_commission_dashboard', 'view_zmp_organizer_dashboard', 'view_zmp_president_dashboard') as $capability) {
            $administrator->add_cap($capability);
        }
    }
}



/** Validate the required member role stack for a President account. */
function zmp_competitions_president_account_is_eligible($user) {
    if (!($user instanceof WP_User)) {
        return false;
    }
    $user = get_user_by('id', (int) $user->ID);
    if (!($user instanceof WP_User)) {
        return false;
    }

    $roles = array_values((array) $user->roles);
    $required_roles = array('subscriber', 'zmp_teacher');
    $allowed_roles = array('administrator','subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer');
    foreach ($required_roles as $required_role) {
        if (!in_array($required_role, $roles, true)) {
            return false;
        }
    }
    if (array_diff($roles, $allowed_roles)) {
        return false;
    }

    $cap_roles = array_keys((array) $user->caps);
    sort($roles);
    sort($cap_roles);
    if ($roles !== $cap_roles) {
        return false;
    }
    foreach ($roles as $role) {
        if (empty($user->caps[$role])) {
            return false;
        }
    }
    return true;
}

/** Grant the scoped President role only under an authorized, date-valid mandate. */
function zmp_competitions_grant_president_role($user, $effective_date = null) {
    $is_cron = function_exists('wp_doing_cron') && wp_doing_cron();
    if (!current_user_can('manage_options') && !$is_cron) {
        return false;
    }
    if (!($user instanceof WP_User)) {
        return false;
    }
    // Re-read from storage so eligibility cannot rely on a stale WP_User cache.
    $user = get_user_by('id', (int) $user->ID);
    if (!($user instanceof WP_User)) {
        return false;
    }
    $roles = array_values((array) $user->roles);
    if (!zmp_competitions_president_account_is_eligible($user)) {
        return false;
    }
    if (null === $effective_date) {
        $effective_date = current_time('Y-m-d');
    }
    if (!is_string($effective_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_date)) {
        return false;
    }
    $parsed_date = DateTimeImmutable::createFromFormat('!Y-m-d', $effective_date, wp_timezone());
    $date_errors = DateTimeImmutable::getLastErrors();
    if (!$parsed_date instanceof DateTimeImmutable
        || ($date_errors && (0 !== $date_errors['warning_count'] || 0 !== $date_errors['error_count']))
        || $parsed_date->format('Y-m-d') !== $effective_date
    ) {
        return false;
    }
    global $wpdb;
    $active_mandate = $wpdb->get_var($wpdb->prepare(
        'SELECT id FROM ' . $wpdb->prefix . 'zmp_president_mandates WHERE user_id = %d AND status = %s AND start_date <= %s AND end_date >= %s LIMIT 1',
        (int) $user->ID,
        'active',
        $effective_date,
        $effective_date
    ));
    if ((int) $active_mandate < 1) {
        return false;
    }
    $user->add_role('zmp_president');
    $actual_roles = array_values((array) $user->roles);
    $expected_roles = array_values(array_unique(array_merge($roles, array('zmp_president'))));
    sort($actual_roles); sort($expected_roles);
    return $actual_roles === $expected_roles
        && user_can($user, 'read')
        && user_can($user, 'view_zmp_president_dashboard');
}

function zmp_competitions_is_president_user($user) {
    return $user instanceof WP_User && in_array('zmp_president', (array)$user->roles, true);
}


/**
 * Регистрација на модулите пред `init`.
 *
 * The request handlers in ZMP_Mentor and ZMP_Exam attach their own `init`
 * callbacks. Creating those objects from an `init` callback is too late:
 * WordPress may already have passed the callback priority they register at.
 */
function zmp_competitions_load_modules() {
    if (is_admin()) {
        ZMP_Admin::get_instance();
        ZMP_President_Mandate_Admin::init();
    }
    ZMP_President_Mandate::init();
    ZMP_President_Public_Display::init();
    ZMP_President_Governance::init();
    ZMP_President_Facsimile_Certificates::init();
    ZMP_President_Dashboard::init();
    ZMP_President_Subject_Approvals::init();

    ZMP_Support::get_instance();
    ZMP_Mentor::get_instance();
    ZMP_Exam::get_instance();
    ZMP_Shortcodes::init();
    ZMP_Governance::init();
    ZMP_Certificate_Templates::init();
    ZMP_Competition_Structure::init();
    ZMP_Category_Registrations::init();
    ZMP_Matrix_Catalog::init();
    ZMP_Approved_Audio_Tools::init();
    ZMP_Moderator_Operations::init();
    ZMP_Category_Jury::init();
    ZMP_Document_Review::init();
    ZMP_Registration_Windows::init();
    ZMP_Region_Requests::init();
    ZMP_Mentor_Subjects::register_hooks();
    ZMP_Google_Drive::init();
    ZMP_YouTube::init();
    ZMP_Communications::init();
    ZMP_Role_Messenger::init();
    ZMP_Circular_Invitations::init();
    ZMP_Committees_Organizers::init();
    ZMP_Registration_Requirements::init();
    ZMP_Fee_Invoices::init();
}
add_action('plugins_loaded', 'zmp_competitions_load_modules');

/**
 * Иницијализација на плагинот
 */
function zmp_competitions_init() {
    if (get_option('zmp_role_version') !== ZMP_SCHEMA_VERSION) {
        zmp_competitions_sync_role_caps();
        update_option('zmp_role_version', ZMP_SCHEMA_VERSION);
    }

    // Apply schema updates once after plugin upgrades, without requiring reactivation.
    // Keep ordinary initialization to the durable migration state. Messenger's
    // retention initializer performs its own locked fresh-schema check and
    // records retry intent; school/profile/email operations do the same when
    // their fail-closed readiness predicates detect actual drift.
    $schema_marker_read_ok = false;
    $schema_marker = ZMP_Role_Messenger::schema_option_read('zmp_db_version', $schema_marker_read_ok);
    $schema_upgrade_required = $schema_marker_read_ok && $schema_marker !== ZMP_SCHEMA_VERSION;
    if (!$schema_marker_read_ok || $schema_upgrade_required || ZMP_Role_Messenger::schema_retry_pending()) {
        $retry_allowed = ZMP_Role_Messenger::schema_retry_allowed();
        if (ZMP_Role_Messenger::mark_schema_retry_pending() && $retry_allowed && ZMP_Role_Messenger::acquire_schema_migration_lock()) {
            try {
                $locked_retry_allowed = ZMP_Role_Messenger::schema_retry_allowed();
                if (ZMP_Role_Messenger::mark_schema_retry_pending() && $locked_retry_allowed && ZMP_Role_Messenger::defer_schema_retry()) {
                    ZMP_Database::create_tables();
                    $mentor_schools_ready = ZMP_Mentor_Schools::install();
                    $school_profile_ready = ZMP_Mentor_School_Profile::install();
                    $mentor_subjects_ready = ZMP_Mentor_Subjects::install();
                    $subject_proposals_ready = ZMP_Mentor_Subject_Proposals::install();
                    $school_email_access_ready = ZMP_School_Email_Access::install();
                    ZMP_Competition_Structure::install();
                    ZMP_Category_Registrations::install();
                    ZMP_Matrix_Catalog::install();
                    ZMP_Moderator_Operations::install();
                    ZMP_Category_Jury::install();
                    ZMP_Registration_Windows::install();
                    $region_requests_ready = ZMP_Region_Requests::install();
                    ZMP_Google_Drive::install();
                    ZMP_Communications::install();
                    $messenger_schema_ready = ZMP_Role_Messenger::install();
                    ZMP_President_Facsimile_Certificates::install();
                    ZMP_Circular_Invitations::install();
                    ZMP_Committees_Organizers::install();
                    ZMP_Registration_Requirements::install();
                    ZMP_Fee_Invoices::install();
                    ZMP_Document_Review::install();
                    ZMP_Credentials::migrate_legacy_pins();
                    ZMP_Regions::migrate_existing_data();
                    ZMP_YouTube::install();
                    zmp_competitions_commit_messenger_schema($messenger_schema_ready && $mentor_schools_ready && $mentor_subjects_ready && $subject_proposals_ready && $region_requests_ready, $school_profile_ready, $school_email_access_ready, array(
                    'messenger_install' => $messenger_schema_ready,
                    'mentor_schools_install' => $mentor_schools_ready,
                    'mentor_subjects_install' => $mentor_subjects_ready,
                    'subject_proposals_install' => $subject_proposals_ready,
                    'region_requests_install' => $region_requests_ready,
                    'school_profile_install' => $school_profile_ready,
                    'school_email_install' => $school_email_access_ready,
                ));
                }
            } finally {
                if (!ZMP_Role_Messenger::release_schema_migration_lock()) { ZMP_Role_Messenger::mark_schema_retry_pending(); }
            }
        }
    }
}
add_action('init', 'zmp_competitions_init');

/**
 * Вчитување на скрипти и стилови
 */
function zmp_competitions_enqueue_assets() {
    $css_path = ZMP_PLUGIN_DIR . 'assets/css/zmp-public.css';
    $js_path  = ZMP_PLUGIN_DIR . 'assets/js/zmp-public.js';
    wp_register_style('zmp-public-css', ZMP_PLUGIN_URL . 'assets/css/zmp-public.css', array(), file_exists($css_path) ? filemtime($css_path) : ZMP_VERSION);
    wp_register_script('zmp-public-js', ZMP_PLUGIN_URL . 'assets/js/zmp-public.js', array('jquery'), file_exists($js_path) ? filemtime($js_path) : ZMP_VERSION, true);

    $post = get_post();
    if (!$post) {
        return;
    }
    $script_shortcodes = array('zmp_natprevari_portal', 'zmp_mentor_portal', 'zmp_student_exam', 'zmp_testator_portal', 'zmp_moderator_portal', 'zmp_commission_portal', 'zmp_organizer_portal', 'zmp_public_results', 'zmp_teacher_register');
    $style_shortcodes = array_merge($script_shortcodes, array('zmp_employment_directory', 'zmp_school_email_access'));
    foreach ($style_shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            wp_enqueue_style('zmp-public-css');
            break;
        }
    }
    foreach ($script_shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            wp_enqueue_script('zmp-public-js');
            wp_localize_script('zmp-public-js', 'zmp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('zmp_exam_nonce')
            ));
            break;
        }
    }
}
add_action('wp_enqueue_scripts', 'zmp_competitions_enqueue_assets');

function zmp_competitions_admin_assets($hook) {
    if (strpos($hook, 'zmp') !== false) {
        wp_enqueue_media();
        $admin_css = ZMP_PLUGIN_DIR . 'assets/css/zmp-admin.css';
        wp_enqueue_style('zmp-admin-css', ZMP_PLUGIN_URL . 'assets/css/zmp-admin.css', array(), is_file($admin_css) ? (string) filemtime($admin_css) : ZMP_VERSION);
        wp_enqueue_script('zmp-admin-js', ZMP_PLUGIN_URL . 'assets/js/zmp-admin.js', array('jquery'), ZMP_VERSION, true);
        if (strpos($hook, 'zmp-testator') !== false || strpos($hook, 'zmp-moderator') !== false || strpos($hook, 'zmp-commission') !== false) {
            $public_css = ZMP_PLUGIN_DIR . 'assets/css/zmp-public.css';
            $public_js = ZMP_PLUGIN_DIR . 'assets/js/zmp-public.js';
            wp_enqueue_style('zmp-public-css', ZMP_PLUGIN_URL . 'assets/css/zmp-public.css', array(), file_exists($public_css) ? filemtime($public_css) : ZMP_VERSION);
            wp_enqueue_script('zmp-public-js', ZMP_PLUGIN_URL . 'assets/js/zmp-public.js', array('jquery'), file_exists($public_js) ? filemtime($public_js) : ZMP_VERSION, true);
            wp_localize_script('zmp-public-js', 'zmp_ajax', array('ajax_url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('zmp_exam_nonce')));
        }
    }
}
add_action('admin_enqueue_scripts', 'zmp_competitions_admin_assets');





