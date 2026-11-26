<?php
/** Synthetic integration regression for voluntary President Home photos. */
global $wpdb;
if (!defined('ABSPATH') || !isset($wpdb) || !($wpdb instanceof wpdb)
    || !class_exists('ZMP_President_Public_Display') || !class_exists('ZMP_President_Mandate')) {
    fwrite(STDERR, "wordpress_plugin_required\n");
    exit(2);
}

$assertions = array();
$assert = static function ($condition, $name) use (&$assertions) {
    $assertions[$name] = $condition ? 'PASS' : 'FAIL';
};
$token = strtolower(wp_generate_password(12, false, false));
$user_ids = array();
$synthetic_emails = array();
$attachment_ids = array();
$mandate_ids = array();
$original_post = isset($_POST) && is_array($_POST) ? $_POST : array();
$original_user_id = get_current_user_id();
$table = ZMP_President_Mandate::table_name();
$today = current_time('Y-m-d');
$tomorrow = gmdate('Y-m-d', strtotime($today . ' +1 day'));
$yesterday = gmdate('Y-m-d', strtotime($today . ' -1 day'));

$extract_presidents = static function () {
    $data = ZMP_President_Public_Display::public_mandate_data();
    return is_array($data) && isset($data['presidents']) && is_array($data['presidents'])
        ? $data['presidents']
        : array();
};

try {
    for ($i = 1; $i <= 2; $i++) {
        $login = 'zmp65photo_' . $token . '_' . $i;
        $user_id = wp_insert_user(array(
            'user_login' => $login,
            'user_pass' => wp_generate_password(32, true, true),
            'user_email' => $login . '@example.invalid',
            'display_name' => 'Synthetic President ' . $i,
            'role' => 'subscriber',
        ));
        if (is_wp_error($user_id)) {
            throw new RuntimeException('synthetic_user_creation_failed');
        }
        $user_ids[$i] = (int) $user_id;
        $synthetic_emails[$i] = $login . '@example.invalid';

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Synthetic President portrait ' . $i,
            'post_status' => 'inherit',
        ), false);
        if (is_wp_error($attachment_id) || !$attachment_id) {
            throw new RuntimeException('synthetic_attachment_creation_failed');
        }
        $attachment_ids[$i] = (int) $attachment_id;
        update_post_meta($attachment_id, '_wp_attached_file', 'zmp-phase65-test/' . $token . '-' . $i . '.jpg');
        update_post_meta($attachment_id, '_wp_attachment_metadata', array(
            'width' => 64,
            'height' => 64,
            'file' => 'zmp-phase65-test/' . $token . '-' . $i . '.jpg',
            'sizes' => array(),
            'image_meta' => array(),
        ));
    }

    $wpdb->last_error = '';
    $inserted = $wpdb->insert($table, array(
        'user_id' => $user_ids[1],
        'start_date' => $today,
        'end_date' => $tomorrow,
        'board_decision' => 'Synthetic test fixture only.',
        'status' => 'active',
        'appointed_by' => get_current_user_id(),
        'appointed_at' => current_time('mysql'),
        'close_reason' => '',
    ));
    if (1 !== $inserted) {
        throw new RuntimeException('synthetic_first_mandate_creation_failed');
    }
    $mandate_ids[1] = (int) $wpdb->insert_id;
    $inserted = $wpdb->insert($table, array(
        'user_id' => $user_ids[2],
        'start_date' => $today,
        'end_date' => $tomorrow,
        'board_decision' => 'Synthetic transition fixture only.',
        'status' => 'active',
        'appointed_by' => get_current_user_id(),
        'appointed_at' => current_time('mysql'),
        'close_reason' => '',
        'predecessor_mandate_id' => $mandate_ids[1],
        'predecessor_user_id' => $user_ids[1],
        'handover_deadline' => $tomorrow,
    ));
    if (1 !== $inserted) {
        throw new RuntimeException('synthetic_successor_mandate_creation_failed');
    }
    $mandate_ids[2] = (int) $wpdb->insert_id;

    $data_without_opt_in = ZMP_President_Public_Display::public_mandate_data();
    $default_presidents = $extract_presidents();
    $assert(count($default_presidents) === 2, 'both_current_transition_presidents_are_returned');
    $assert(count(array_filter($default_presidents, static function ($row) { return !empty($row['photo_url']); })) === 0,
        'photo_is_off_without_explicit_opt_in');
    $assert(!is_array($data_without_opt_in) || !array_key_exists('user_email', $data_without_opt_in),
        'public_response_omits_email');

    $admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
    $admin_id = is_array($admins) && !empty($admins) ? (int) $admins[0] : 0;
    if ($admin_id < 1) {
        throw new RuntimeException('synthetic_administrator_unavailable');
    }
    $consent_before_admin_edit = get_user_meta($user_ids[2], 'zmp_president_public_photo_opt_in', true);
    $attachment_before_admin_edit = get_user_meta($user_ids[2], 'zmp_president_public_photo_attachment_id', true);
    wp_set_current_user($admin_id);
    $_POST = array(
        ZMP_President_Public_Display::PHOTO_PROFILE_NONCE => wp_create_nonce(
            ZMP_President_Public_Display::PHOTO_PROFILE_ACTION . '_' . $user_ids[2]
        ),
        'zmp_president_public_photo_opt_in' => '1',
        'zmp_president_public_photo_attachment_id' => (string) $attachment_ids[2],
    );
    ZMP_President_Public_Display::save_profile_photo_fields($user_ids[2]);
    $assert(get_user_meta($user_ids[2], 'zmp_president_public_photo_opt_in', true) === $consent_before_admin_edit,
        'admin_edit_with_valid_nonce_cannot_grant_president_consent');
    $assert((int) get_user_meta($user_ids[2], 'zmp_president_public_photo_attachment_id', true) === $attachment_ids[2]
        && (int) $attachment_before_admin_edit !== $attachment_ids[2],
        'admin_can_manage_local_image_without_granting_consent');

    // The synthetic President selects a local image and controls their consent from their own profile.
    $wpdb->update($wpdb->posts, array('post_author' => $user_ids[2]), array('ID' => $attachment_ids[2]), array('%d'), array('%d'));
    wp_set_current_user($user_ids[2]);
    $_POST = array(
        ZMP_President_Public_Display::PHOTO_PROFILE_NONCE => wp_create_nonce(
            ZMP_President_Public_Display::PHOTO_PROFILE_ACTION . '_' . $user_ids[2]
        ),
        'zmp_president_public_photo_opt_in' => '1',
        'zmp_president_public_photo_attachment_id' => (string) $attachment_ids[2],
    );
    ZMP_President_Public_Display::save_profile_photo_fields($user_ids[2]);
    $assert('1' === (string) get_user_meta($user_ids[2], 'zmp_president_public_photo_opt_in', true)
        && (int) get_user_meta($user_ids[2], 'zmp_president_public_photo_attachment_id', true) === $attachment_ids[2],
        'president_self_profile_can_opt_in_with_local_image');
    $_POST = array(
        ZMP_President_Public_Display::PHOTO_PROFILE_NONCE => wp_create_nonce(
            ZMP_President_Public_Display::PHOTO_PROFILE_ACTION . '_' . $user_ids[2]
        ),
        'zmp_president_public_photo_opt_in' => '0',
        'zmp_president_public_photo_attachment_id' => (string) $attachment_ids[2],
    );
    ZMP_President_Public_Display::save_profile_photo_fields($user_ids[2]);
    $assert('0' === (string) get_user_meta($user_ids[2], 'zmp_president_public_photo_opt_in', true),
        'president_self_profile_can_opt_out');
    $_POST = $original_post;

    foreach (array(1, 2) as $i) {
        update_user_meta($user_ids[$i], 'zmp_president_public_photo_opt_in', '1');
        update_user_meta($user_ids[$i], 'zmp_president_public_photo_attachment_id', $attachment_ids[$i]);
    }
    $opted_in = $extract_presidents();
    $by_name = array();
    foreach ($opted_in as $row) {
        if (isset($row['display_name'])) {
            $by_name[$row['display_name']] = $row;
        }
    }
    $url_one = wp_get_attachment_url($attachment_ids[1]);
    $url_two = wp_get_attachment_url($attachment_ids[2]);
    $assert(count($opted_in) === 2 && isset($by_name['Synthetic President 1']['photo_url'], $by_name['Synthetic President 2']['photo_url']),
        'each_opted_in_current_president_gets_own_photo');
    $assert(isset($by_name['Synthetic President 1']['photo_url'], $by_name['Synthetic President 2']['photo_url'])
        && $by_name['Synthetic President 1']['photo_url'] === $url_one
        && $by_name['Synthetic President 2']['photo_url'] === $url_two,
        'photos_are_local_wordpress_image_attachments');
    $encoded = wp_json_encode($opted_in);
    $decoded = is_string($encoded) ? json_decode($encoded, true) : null;
    $allowed_public_keys = array('display_name', 'start_date', 'end_date', 'photo_url');
    $only_public_fields = is_array($decoded);
    foreach ((array) $decoded as $president) {
        if (!is_array($president) || array_diff(array_keys($president), $allowed_public_keys)) {
            $only_public_fields = false;
            break;
        }
    }
    $synthetic_email_leaked = false;
    foreach ($synthetic_emails as $synthetic_email) {
        if (is_string($encoded) && strpos($encoded, $synthetic_email) !== false) {
            $synthetic_email_leaked = true;
            break;
        }
    }
    $assert($only_public_fields && !$synthetic_email_leaked,
        'public_response_omits_account_identifiers_and_private_metadata');

    update_user_meta($user_ids[1], 'zmp_president_public_photo_opt_in', '0');
    $one_opted_out = $extract_presidents();
    $photos_after_opt_out = array_filter($one_opted_out, static function ($row) { return !empty($row['photo_url']); });
    $photo_names_after_opt_out = array_values(array_map(static function ($row) {
        return isset($row['display_name']) ? $row['display_name'] : '';
    }, $photos_after_opt_out));
    $assert(count($one_opted_out) === 2 && $photo_names_after_opt_out === array('Synthetic President 2'),
        'opt_out_removes_only_that_presidents_photo_immediately');

    $wpdb->update($table, array('status' => 'replaced', 'end_date' => $yesterday), array('id' => $mandate_ids[1]), array('%s', '%s'), array('%d'));
    $ineligible = $extract_presidents();
    $assert(count($ineligible) === 1 && isset($ineligible[0]['display_name'])
        && $ineligible[0]['display_name'] === 'Synthetic President 2',
        'replaced_or_expired_president_is_removed_from_public_data');
} catch (Throwable $error) {
    $assertions['fixture_execution'] = 'FAIL';
    fwrite(STDERR, 'fixture_execution_failed:' . sanitize_key($error->getMessage()) . PHP_EOL);
} finally {
    $_POST = $original_post;
    wp_set_current_user($original_user_id);
    foreach ($mandate_ids as $mandate_id) {
        $wpdb->delete($table, array('id' => $mandate_id), array('%d'));
    }
    foreach ($attachment_ids as $attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }
    foreach ($user_ids as $user_id) {
        wp_delete_user($user_id);
    }
}

$failures = array_keys(array_filter($assertions, static function ($value) { return $value !== 'PASS'; }));
echo wp_json_encode(array('assertions' => $assertions, 'failures' => $failures), JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
