<?php
/**
 * Full mandate-service appointment check for an Administrator + ZMP role stack.
 * Local synthetic clone only. Removes its mandate, audit rows, and user in finally.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { fwrite(STDERR, "BLOCKED: WP-CLI only\n"); exit(2); }
if (DB_NAME !== 'zmp_debug_prodclone_20261125' || $GLOBALS['wpdb']->prefix !== 'wp_yqqdt5_'
    || home_url('/') !== 'http://localhost:8111/') {
    fwrite(STDERR, "BLOCKED: wrong local clone identity\n"); exit(2);
}
if (!class_exists('ZMP_President_Mandate')) { fwrite(STDERR, "BLOCKED: mandate service missing\n"); exit(2); }

global $wpdb;
$mandates = $wpdb->prefix . 'zmp_president_mandates';
$audits = $wpdb->prefix . 'zmp_president_mandate_audit';
$initial_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
$initial_mandates = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mandates}");
$user_id = 0;
$mandate_id = 0;
$ok = false;
$cleanup_ok = false;
try {
    $created = wp_insert_user(array(
        'user_login' => 'codex66a_admin_president_' . bin2hex(random_bytes(5)),
        'user_pass' => wp_generate_password(48, true, true),
        'user_email' => 'codex66a-admin-president-' . bin2hex(random_bytes(5)) . '@example.invalid',
        'role' => 'administrator',
    ));
    if (is_wp_error($created) || !is_int($created) || $created < 1) { throw new RuntimeException('synthetic account create failed'); }
    $user_id = $created;
    $user = get_user_by('id', $user_id);
    foreach (array('subscriber','zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer') as $role) { $user->add_role($role); }
    clean_user_cache($user_id);
    wp_set_current_user($user_id);
    $start = current_time('Y-m-d');
    $end = ZMP_President_Mandate::term_end_date($start);
    if (is_wp_error($end)) { throw new RuntimeException('term date calculation failed'); }
    $result = ZMP_President_Mandate::appoint($user_id, $start, $end, 'Synthetic test: Administrator and allowed ZMP roles may be stacked.');
    if (is_wp_error($result) || !is_int($result) || $result < 1) { throw new RuntimeException('appointment service rejected stacked Administrator: ' . (is_wp_error($result) ? $result->get_error_code() : 'invalid result')); }
    $mandate_id = $result;
    clean_user_cache($user_id);
    $user = get_user_by('id', $user_id);
    $expected = array('administrator','subscriber','zmp_teacher','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer');
    $actual = array_values((array) $user->roles);
    sort($expected); sort($actual);
    if ($actual !== $expected || !user_can($user, 'manage_options') || !user_can($user, 'view_zmp_president_dashboard')) {
        throw new RuntimeException('appointment did not preserve stacked roles and capabilities');
    }
    $ok = true;
} catch (Throwable $error) {
    fwrite(STDERR, 'APPOINTMENT_ERROR=' . get_class($error) . ': ' . $error->getMessage() . PHP_EOL);
} finally {
    // If the service throws mid-transaction, unwind only this WP-CLI test request.
    $wpdb->query('ROLLBACK');
    if ($mandate_id > 0) {
        wp_set_current_user($user_id);
        ZMP_President_Mandate::revoke($mandate_id, 'Synthetic test cleanup');
        $wpdb->delete($audits, array('mandate_id' => $mandate_id), array('%d'));
        $wpdb->delete($mandates, array('id' => $mandate_id), array('%d'));
    }
    wp_set_current_user(0);
    if ($user_id > 0 && get_userdata($user_id)) { wp_delete_user($user_id); }
    $cleanup_ok = ((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}") === $initial_users)
        && ((int) $wpdb->get_var("SELECT COUNT(*) FROM {$mandates}") === $initial_mandates);
}
fwrite(STDOUT, 'ADMIN_STACKED_APPOINTMENT=' . ($ok ? 'PASS' : 'FAIL') . '; SYNTHETIC_CLEANUP=' . ($cleanup_ok ? 'PASS' : 'FAIL') . PHP_EOL);
exit($ok && $cleanup_ok ? 0 : 1);
