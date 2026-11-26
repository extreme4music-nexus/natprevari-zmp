<?php
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$table = ZMP_President_Mandate::table_name();
$audit = ZMP_President_Mandate::audit_table_name();
$base_mandates = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$base_events = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$audit}");
$login = 'zmp66b_deny_' . strtolower(str_replace('-', '', wp_generate_uuid4()));
$uid = wp_insert_user(array(
    'user_login' => $login,
    'user_email' => $login . '@example.invalid',
    'user_pass' => wp_generate_password(48, true, true),
    'role' => 'subscriber',
));
if (is_wp_error($uid) || !$uid) {
    WP_CLI::error('synthetic subscriber creation failed');
}
$uid = (int) $uid;
$results = array();
$clean = static function () use ($uid, $wpdb, $table, $audit) {
    $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d", $uid));
    foreach ((array) $ids as $id) {
        $wpdb->delete($audit, array('mandate_id' => (int) $id), array('%d'));
    }
    $wpdb->delete($table, array('user_id' => $uid), array('%d'));
    if (get_userdata($uid)) {
        wp_delete_user($uid);
    }
};
register_shutdown_function(static function () use ($uid, $wpdb, $table, $audit, $base_mandates, $base_events, &$results, $clean) {
    $clean();
    $remaining_user_rows = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE ID=%d", $uid));
    $remaining_mandates = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=%d", $uid));
    $remaining_events = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$audit} WHERE mandate_id IN (SELECT id FROM {$table} WHERE user_id=%d)", $uid));
    $summary = array(
        'route' => 'admin_post_zmp_president_mandate_appoint',
        'denials' => $results,
        'exact_cleanup' => $remaining_user_rows === 0 && $remaining_mandates === 0 && $remaining_events === 0,
        'base_mandate_count' => $base_mandates,
        'current_mandate_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
        'base_audit_count' => $base_events,
        'current_audit_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$audit}"),
    );
    $summary['no_mutation'] = $summary['base_mandate_count'] === $summary['current_mandate_count']
        && $summary['base_audit_count'] === $summary['current_audit_count'];
    WP_CLI::log(wp_json_encode($summary));
    if (!$summary['exact_cleanup'] || !$summary['no_mutation']) {
        WP_CLI::error('denial fixture cleanup or no-mutation assertion failed');
    }
    foreach ($results as $result) {
        if (empty($result['denied']) || empty($result['no_mutation'])) {
            WP_CLI::error('authorization denial/no-mutation assertion failed');
        }
    }
});
$throw_die = static function ($message = '', $title = '', $args = array()) {
    throw new RuntimeException('wp_die intercepted: ' . wp_strip_all_tags((string) $message));
};
add_filter('wp_die_handler', static function () use ($throw_die) {
    return $throw_die;
});
$valid_start = gmdate('Y-m-d', strtotime('+5 years'));
$decision = 'Synthetic denial/no-mutation local test.';
wp_set_current_user($uid);
$_POST = array(
    'president_user_id' => $uid,
    'start_date' => $valid_start,
    'board_decision' => $decision,
    '_wpnonce' => wp_create_nonce('zmp_president_mandate_appoint'),
);
$_REQUEST = $_POST;
try {
    ZMP_President_Mandate_Admin::handle_appointment();
    $results[] = array('case' => 'subscriber_with_valid_nonce', 'denied' => false, 'no_mutation' => false);
} catch (RuntimeException $e) {
    $results[] = array(
        'case' => 'subscriber_with_valid_nonce',
        'denied' => strpos($e->getMessage(), 'Само администратор') !== false,
        'no_mutation' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === $base_mandates
            && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$audit}") === $base_events,
    );
}
wp_set_current_user(1);
$_POST = array(
    'president_user_id' => $uid,
    'start_date' => $valid_start,
    'board_decision' => $decision,
    '_wpnonce' => 'invalid-nonce',
);
$_REQUEST = $_POST;
try {
    ZMP_President_Mandate_Admin::handle_appointment();
    $results[] = array('case' => 'administrator_with_invalid_nonce', 'denied' => false, 'no_mutation' => false);
} catch (RuntimeException $e) {
    $results[] = array(
        'case' => 'administrator_with_invalid_nonce',
        'denied' => true,
        'no_mutation' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") === $base_mandates
            && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$audit}") === $base_events,
    );
}
