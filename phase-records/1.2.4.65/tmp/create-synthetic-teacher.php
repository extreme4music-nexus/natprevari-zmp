<?php
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(2); }

$credential_path = '/mnt/z/Documents/Natprevari_ZMP/ZMP_PHASE65_TEACHER_SYNTHETIC_LOCAL_ONLY_20261105.txt';
$user_id = 0;

try {
    if (!wp_roles()->is_role('zmp_teacher')) { throw new RuntimeException('teacher_role_unavailable'); }
    if (!is_file($credential_path) || is_link($credential_path) || !is_readable($credential_path)) { throw new RuntimeException('credential_file_unreadable'); }
    $credential_text = file_get_contents($credential_path);
    if (!is_string($credential_text)
        || !preg_match('/^Username:\s*([a-z0-9_]+)\s*$/mi', $credential_text, $username_match)
        || !preg_match('/^Password:\s*(\S+)\s*$/m', $credential_text, $password_match)) { throw new RuntimeException('credential_file_invalid'); }
    $username = $username_match[1];
    $password = $password_match[1];
    if (get_user_by('login', $username)) { throw new RuntimeException('username_already_exists'); }
    $email = $username . '@example.invalid';
    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_pass' => $password,
        'user_email' => $email,
        'display_name' => 'Synthetic Multi-School Teacher',
        'first_name' => 'Synthetic',
        'last_name' => 'Teacher',
        'role' => 'zmp_teacher',
    ));
    if (is_wp_error($user_id) || (int)$user_id < 1) {
        $user_id = 0;
        throw new RuntimeException('synthetic_teacher_create_failed');
    }
    $user_id = (int)$user_id;

    WP_CLI::line('SYNTHETIC_TEACHER_CREATED=1 USER_ID=' . $user_id);
} catch (Throwable $error) {
    if ($user_id > 0) { wp_delete_user($user_id); }
    $safe_codes = array('teacher_role_unavailable','credential_file_unreadable','credential_file_invalid','username_already_exists','synthetic_teacher_create_failed');
    $code = in_array($error->getMessage(), $safe_codes, true) ? $error->getMessage() : 'synthetic_teacher_create_failed';
    WP_CLI::line('SYNTHETIC_TEACHER_CREATED=0 ERROR_CODE=' . $code);
    WP_CLI::halt(1);
}
