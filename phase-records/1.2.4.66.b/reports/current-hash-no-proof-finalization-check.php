<?php
/** Current-hash no-proof finalization guard; synthetic isolated site; no writes expected. */
if (!defined('WP_CLI') || !class_exists('ZMP_Category_Jury')) { WP_CLI::error('required runtime unavailable'); }
global $wpdb;
$season_id = 1; $category_id = 2; $entry_id = 1; $phase = 'municipal'; $actor = 116;
$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s", $season_id, $category_id, $entry_id, $phase), ARRAY_A);
if (!$row || ($row['status'] ?? '') !== 'confirmed') { WP_CLI::error('scoped synthetic baseline result is not the expected confirmed record'); }
$row_hash = hash('sha256', serialize($row));
$audit_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_audit WHERE season_id=%d AND category_id=%d AND entry_id=%d AND action IN ('result_confirmed','result_reconfirmed_after_jury_correction')", $season_id, $category_id, $entry_id));
$redirect_status = '';
add_filter('wp_redirect', static function ($location, $status) use (&$redirect_status) { $query = wp_parse_url($location, PHP_URL_QUERY); parse_str((string) $query, $args); $redirect_status = sanitize_key($args['zmp_jury_status'] ?? ''); return $location; }, 10, 2);
register_shutdown_function(static function () use (&$wpdb, $season_id, $category_id, $entry_id, $phase, $row_hash, $audit_count, &$redirect_status) {
    $after = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s", $season_id, $category_id, $entry_id, $phase), ARRAY_A);
    $after_hash = $after ? hash('sha256', serialize($after)) : '';
    $after_audit = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_audit WHERE season_id=%d AND category_id=%d AND entry_id=%d AND action IN ('result_confirmed','result_reconfirmed_after_jury_correction')", $season_id, $category_id, $entry_id));
    $ok = $redirect_status === 'step_up_required' && hash_equals($row_hash, $after_hash) && $audit_count === $after_audit;
    echo 'current_hash_no_proof_redirect=' . ($redirect_status === 'step_up_required' ? 'PASS' : 'FAIL') . PHP_EOL;
    echo 'result_and_confirmation_audit_unchanged=' . (hash_equals($row_hash, $after_hash) && $audit_count === $after_audit ? 'PASS' : 'FAIL') . PHP_EOL;
    echo 'current_hash_no_proof_finalization=' . ($ok ? 'PASS' : 'FAIL') . PHP_EOL;
});
wp_set_current_user($actor);
$_GET = array();
$_POST = array('zmp_jury_action'=>'finalize_result','category_id'=>(string)$category_id,'entry_id'=>(string)$entry_id,'phase'=>$phase,'_wpnonce'=>wp_create_nonce('zmp_category_jury'),'decision_basis'=>str_repeat('Synthetic factual basis ', 2),'decision_note'=>str_repeat('Synthetic fair outcome rationale ', 2));
$_REQUEST = $_POST;
ZMP_Category_Jury::handle_post();
WP_CLI::error('handler returned without expected guarded redirect');
