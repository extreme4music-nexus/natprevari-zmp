<?php
/**
 * Read-only, offline contract checks for the .66.b YouTube integration.
 * Run only through WP-CLI on the isolated phase66b-test site.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_YouTube')) {
    fwrite(STDERR, "wordpress_or_youtube_class_missing\n");
    exit(2);
}

$checks = array();
$assert = static function ($name, $condition) use (&$checks) {
    $checks[$name] = (bool) $condition;
    echo $name . '=' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
};

$assert('youtube_unconfigured_fails_closed', ZMP_YouTube::is_connected() === false);
$assert('vocal_slot_contract', array_keys(ZMP_YouTube::required_slots('vocal_interpretation')) === array('performance'));
$assert('choir_required_optional_slots', array_keys(ZMP_YouTube::required_slots('school_choir')) === array('required', 'optional'));
$assert('orchestra_required_optional_slots', array_keys(ZMP_YouTube::required_slots('school_orchestra')) === array('required', 'optional'));
$assert('band_two_optional_slots', array_keys(ZMP_YouTube::required_slots('school_bands')) === array('optional_one', 'optional_two'));
$assert('unknown_category_has_no_slots', ZMP_YouTube::required_slots('synthetic_unknown') === array());
$assert('invalid_region_fails_closed', ZMP_YouTube::regional_delivery_mode(999999, 999999, 'synthetic-invalid-region') === 'unconfigured');
$assert('nonregional_player_is_empty', ZMP_YouTube::video_player(999999, 'national') === '');

$mediaState = new ReflectionMethod('ZMP_YouTube', 'media_state');
$mediaState->setAccessible(true);
$assert('unlisted_succeeded_is_ready', $mediaState->invoke(null, 'unlisted', 'succeeded') === 'ready');
$assert('non_unlisted_is_privacy_error', $mediaState->invoke(null, 'public', 'succeeded') === 'privacy_error');
$assert('unlisted_processing_stays_processing', $mediaState->invoke(null, 'unlisted', 'processing') === 'processing');
$assert('unlisted_failed_is_failed', $mediaState->invoke(null, 'unlisted', 'failed') === 'failed');

$uploadResult = new ReflectionMethod('ZMP_YouTube', 'upload_result');
$uploadResult->setAccessible(true);
$invalidUpload = $uploadResult->invoke(null, '{"status":{"privacyStatus":"unlisted"}}');
$validUpload = $uploadResult->invoke(null, '{"id":"AbcdefGhijk","status":{"privacyStatus":"unlisted"},"processingDetails":{"processingStatus":"processing"}}');
$assert('upload_response_without_video_id_rejected', is_wp_error($invalidUpload) && $invalidUpload->get_error_code() === 'youtube_response_invalid');
$assert('synthetic_upload_response_parsed', is_array($validUpload) && $validUpload['id'] === 'AbcdefGhijk' && $validUpload['privacy'] === 'unlisted' && $validUpload['processing'] === 'processing');

if (in_array(false, $checks, true)) {
    exit(1);
}
echo 'Success: offline YouTube contract checks completed; no database write or external request was made.' . PHP_EOL;
