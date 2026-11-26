<?php
/**
 * Exhaustive role-stack eligibility check for the authorized local .66.a clone.
 * Synthetic-only user; all generated rows are removed in finally.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only\n"); exit(2); }
if (DB_NAME !== 'zmp_debug_prodclone_20261125' || $GLOBALS['wpdb']->prefix !== 'wp_yqqdt5_'
    || !in_array(home_url('/'), array('http://127.0.0.1:8111/', 'http://localhost:8111/'), true)) { fwrite(STDERR, "BLOCKED: wrong clone identity\n"); exit(2); }
if (!class_exists('ZMP_Mentor_School_Profile')) { fwrite(STDERR, "BLOCKED: profile service missing\n"); exit(2); }
$roles = array('administrator','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer');
foreach (array_merge(array('subscriber','zmp_teacher'), $roles) as $role) {
    if (!get_role($role)) { fwrite(STDERR, "BLOCKED: expected role missing\n"); exit(2); }
}
$start_users = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->users}");
$user_id = 0;
$checks = 0;
$failures = array();
try {
    $created = wp_insert_user(array(
        'user_login' => 'codex66a_stack_matrix_' . bin2hex(random_bytes(6)),
        'user_pass' => wp_generate_password(48, true, true),
        'user_email' => 'codex66a-stack-' . bin2hex(random_bytes(6)) . '@example.invalid',
        'role' => 'subscriber',
    ));
    if (is_wp_error($created) || !is_int($created) || $created < 1) { throw new RuntimeException('synthetic account create failed'); }
    $user_id = $created;
    $checks++;
    $expected_extra = array('administrator','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer');
    for ($mask = 0; $mask < (1 << count($roles)); $mask++) {
        $user = get_user_by('id', $user_id);
        foreach ((array) $user->roles as $existing) { $user->remove_role($existing); }
        $user->add_role('subscriber');
        $user->add_role('zmp_teacher');
        $selected = array();
        foreach ($roles as $bit => $role) {
            if (($mask & (1 << $bit)) !== 0) { $user->add_role($role); $selected[] = $role; }
        }
        clean_user_cache($user_id);
        $actual_roles = array_values((array) get_user_by('id', $user_id)->roles);
        $expected_roles = array_merge(array('subscriber','zmp_teacher'), $selected);
        sort($actual_roles); sort($expected_roles);
        $allowed = ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id);
        $current_allowed = (bool) $user_id && (int) get_current_user_id() === $user_id;
        wp_set_current_user($user_id);
        $allowed = ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id);
        $checks++;
        if ($actual_roles !== $expected_roles || !$allowed) { $failures[] = 'stack mask ' . $mask; }
    }
    $user = get_user_by('id', $user_id);
    foreach ((array) $user->roles as $existing) { $user->remove_role($existing); }
    $user->add_role('subscriber');
    wp_set_current_user($user_id);
    $checks++;
    if (ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id)) { $failures[] = 'subscriber without teacher was allowed'; }
    $user->add_role('zmp_teacher');
    $user->remove_role('subscriber');
    clean_user_cache($user_id);
    $checks++;
    if (ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id)) { $failures[] = 'teacher without subscriber was allowed'; }
    $user->add_role('editor');
    $user->add_role('subscriber');
    clean_user_cache($user_id);
    $checks++;
    if (ZMP_Mentor_School_Profile::can_manage_own_teacher_profile($user_id)) { $failures[] = 'unapproved extra role was allowed'; }
} catch (Throwable $e) {
    $failures[] = 'fixture exception ' . get_class($e);
} finally {
    wp_set_current_user(0);
    if ($user_id > 0 && get_userdata($user_id)) { wp_delete_user($user_id); }
}
$end_users = (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->users}");
$clean = $end_users === $start_users;
if (!$clean) { $failures[] = 'user count did not return to baseline'; }
fwrite(STDOUT, 'ROLE_STACK_MATRIX=' . (empty($failures) ? 'PASS' : 'FAIL') . ' (' . $checks . ' checks); CLEANUP=' . ($clean ? 'PASS' : 'FAIL') . PHP_EOL);
foreach ($failures as $failure) { fwrite(STDERR, '- ' . $failure . PHP_EOL); }
exit(empty($failures) && $clean ? 0 : 1);

