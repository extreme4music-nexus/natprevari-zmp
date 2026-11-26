<?php
/**
 * President appointment role eligibility matrix for the existing local .66.a clone.
 * Creates one disposable synthetic user and removes it in finally.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only\n"); exit(2); }
if (DB_NAME !== 'zmp_debug_prodclone_20261125' || $GLOBALS['wpdb']->prefix !== 'wp_yqqdt5_'
    || !in_array(home_url('/'), array('http://127.0.0.1:8111/', 'http://localhost:8111/'), true)) {
    fwrite(STDERR, "BLOCKED: wrong clone identity\n"); exit(2);
}
if (!function_exists('zmp_competitions_president_account_is_eligible')
    || !class_exists('ZMP_President_Mandate_Admin') || !class_exists('ZMP_President_Mandate')) {
    fwrite(STDERR, "BLOCKED: mandate code missing\n"); exit(2);
}
foreach (array('subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer','administrator','editor') as $role) {
    if (!get_role($role)) { fwrite(STDERR, "BLOCKED: expected role missing\n"); exit(2); }
}
$initial_users = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->users}");
$user_id = 0;
$checks = 0;
$failures = array();
$admin_list = new ReflectionMethod('ZMP_President_Mandate_Admin', 'eligible_users');
$admin_list->setAccessible(true);
$appointee_check = new ReflectionMethod('ZMP_President_Mandate', 'eligible_appointee');
$appointee_check->setAccessible(true);
try {
    $created = wp_insert_user(array(
        'user_login' => 'codex66a_president_elig_' . bin2hex(random_bytes(5)),
        'user_pass' => wp_generate_password(48, true, true),
        'user_email' => 'codex66a-president-elig-' . bin2hex(random_bytes(5)) . '@example.invalid',
        'role' => 'subscriber',
    ));
    if (is_wp_error($created) || !is_int($created) || $created < 1) { throw new RuntimeException('synthetic account create failed'); }
    $user_id = $created;
    $cases = array(
        'subscriber_only' => array(array('subscriber'), false),
        'teacher_only' => array(array('zmp_teacher'), false),
        'subscriber_teacher' => array(array('subscriber','zmp_teacher'), true),
        'stacked_member_roles' => array(array('subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'), true),
        'administrator_stack' => array(array('administrator','subscriber','zmp_teacher'), true),
        'administrator_with_all_member_roles' => array(array('administrator','subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'), true),
        'unapproved_editor_stack' => array(array('subscriber','zmp_teacher','editor'), false),
    );
    foreach ($cases as $name => $case) {
        $user = get_user_by('id', $user_id);
        foreach ((array) $user->roles as $existing) { $user->remove_role($existing); }
        foreach ($case[0] as $role) { $user->add_role($role); }
        clean_user_cache($user_id);
        $user = get_user_by('id', $user_id);

        $central = zmp_competitions_president_account_is_eligible($user);
        $service = (bool) $appointee_check->invoke(null, $user);
        $users = $admin_list->invoke(null, 0);
        $listed = false;
        foreach ($users as $listed_user) {
            if ((int) $listed_user->ID === $user_id) { $listed = true; break; }
        }

        $expected = (bool) $case[1];
        foreach (array('central'=>$central, 'service'=>$service, 'form_list'=>$listed) as $scope=>$actual) {
            $checks++;
            if ($actual !== $expected) { $failures[] = $name . ':' . $scope; }
        }
    }
} catch (Throwable $error) {
    $failures[] = 'fixture exception ' . get_class($error);
} finally {
    if ($user_id > 0 && get_userdata($user_id)) { wp_delete_user($user_id); }
}
$final_users = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->users}");
$clean = $initial_users === $final_users;
if (!$clean) { $failures[] = 'synthetic user cleanup failed'; }
fwrite(STDOUT, 'PRESIDENT_APPOINTMENT_ELIGIBILITY=' . (empty($failures) ? 'PASS' : 'FAIL') . ' (' . $checks . ' checks); CLEANUP=' . ($clean ? 'PASS' : 'FAIL') . PHP_EOL);
foreach ($failures as $failure) { fwrite(STDERR, '- ' . $failure . PHP_EOL); }
exit(empty($failures) && $clean ? 0 : 1);
