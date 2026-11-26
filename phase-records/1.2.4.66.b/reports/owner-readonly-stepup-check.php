<?php
/**
 * Owner-run, read-only checks for the `.66.b` qualification binding and regional
 * passkey-enrollment eligibility. Run only with WP-CLI against phase66b-test/site.
 * This script creates no accounts, credentials, challenges, results or audit rows.
 */

if (!class_exists('WP_CLI') || !class_exists('ZMP_Category_Jury') || !class_exists('ZMP_Passkeys')) {
    fwrite(STDERR, "Required WordPress/plugin classes are not loaded.\n");
    exit(2);
}

$checks = array();
$normalizer = new ReflectionMethod('ZMP_Category_Jury', 'normalize_qualified_next_stage');
$normalizer->setAccessible(true);
$next_stage_lookup = new ReflectionMethod('ZMP_Category_Jury', 'next_stage_exists');
$next_stage_lookup->setAccessible(true);
$binding_method = new ReflectionMethod('ZMP_Category_Jury', 'finalization_step_up_binding');
$binding_method->setAccessible(true);

$season_id = 1;
$category_id = 2;
$phase = 'municipal';
$has_next_stage = (bool) $next_stage_lookup->invoke(null, $season_id, $category_id, $phase);
$missing = $normalizer->invoke(null, $season_id, $category_id, $phase, null, false);
$zero = $normalizer->invoke(null, $season_id, $category_id, $phase, '0', true);
$malformed_on = $normalizer->invoke(null, $season_id, $category_id, $phase, 'on', true);
$malformed_true = $normalizer->invoke(null, $season_id, $category_id, $phase, 'true', true);
$one = $normalizer->invoke(null, $season_id, $category_id, $phase, '1', true);

$checks['unchecked_missing_maps_to_zero'] = $missing === 0;
$checks['explicit_zero_maps_to_zero'] = $zero === 0;
$checks['on_is_rejected'] = is_wp_error($malformed_on);
$checks['true_is_rejected'] = is_wp_error($malformed_true);
$checks['one_matches_next_stage_availability'] = $has_next_stage ? $one === 1 : is_wp_error($one);

$digest_zero = $binding_method->invoke(null, $season_id, $category_id, 1, $phase, '', 0, 'synthetic basis', 'synthetic rationale');
$digest_one = $binding_method->invoke(null, $season_id, $category_id, 1, $phase, '', 1, 'synthetic basis', 'synthetic rationale');
$checks['zero_and_one_have_distinct_bindings'] = is_string($digest_zero) && is_string($digest_one) && !hash_equals($digest_zero, $digest_one);

foreach ($checks as $name => $passed) {
    WP_CLI::log($name . '=' . ($passed ? 'PASS' : 'FAIL'));
}
WP_CLI::log('next_stage_available=' . ($has_next_stage ? 'yes' : 'no'));

// Optional read-only enrollment check; no fixture is created if none exists.
global $wpdb;
$season = class_exists('ZMP_Category_Registrations') ? ZMP_Category_Registrations::active_season() : null;
$eligible_regional_only_users = array();
if ($season && !empty($season->id) && get_option('zmp_regional_jury_migration_49_2') === 'complete') {
    $wpdb->last_error = '';
    $regional_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT r.juror_user_id, r.category_id
         FROM {$wpdb->prefix}zmp_jury_regional_assignments r
         WHERE r.season_id=%d
           AND NOT EXISTS (
               SELECT 1 FROM {$wpdb->prefix}zmp_jury_assignments a
               WHERE a.season_id=r.season_id AND a.juror_user_id=r.juror_user_id
           )",
        absint($season->id)
    ));
    if ($wpdb->last_error === '') {
        foreach ((array) $regional_rows as $row) {
            $user_id = absint($row->juror_user_id);
            $user = get_userdata($user_id);
            if ($user && in_array('zmp_commission', (array) $user->roles, true)
                && ZMP_Category_Jury::has_any_assignment((int) $season->id, absint($row->category_id), $user_id)) {
                $eligible_regional_only_users[$user_id] = true;
            }
        }
    }
}

if ($eligible_regional_only_users) {
    $regional_enrollment_passes = true;
    foreach (array_keys($eligible_regional_only_users) as $user_id) {
        if (!ZMP_Passkeys::can_register_for_user((int) $user_id)) {
            $regional_enrollment_passes = false;
            break;
        }
    }
    WP_CLI::log('regional_only_enrollment=' . ($regional_enrollment_passes ? 'PASS' : 'FAIL'));
    $checks['regional_only_enrollment'] = $regional_enrollment_passes;
} else {
    WP_CLI::log('regional_only_enrollment=NOT_RUN_NO_ELIGIBLE_SYNTHETIC_FIXTURE');
}

if (in_array(false, $checks, true)) {
    WP_CLI::error('One or more read-only checks failed. No database writes were requested.');
}
WP_CLI::success('Read-only checks completed. No passkey prompt or database mutation was used.');
