<?php
/**
 * Synthetic offline integration check for YouTube token/session/upload helpers.
 * All HTTP calls are intercepted by pre_http_request; never run outside the
 * isolated phase66b-test WordPress site.
 */
if (!defined('ABSPATH') || !class_exists('ZMP_YouTube')) {
    fwrite(STDERR, "wordpress_or_youtube_class_missing\n");
    exit(2);
}

$sentinel = new stdClass();
$previous = get_option('zmp_youtube_settings', $sentinel);
$temporary_file = WP_CONTENT_DIR . '/uploads/zmp-youtube-synthetic-offline-' . bin2hex(random_bytes(8)) . '.bin';
$checks = array();
$mock_counts = array('token' => 0, 'session' => 0, 'probe' => 0, 'chunk' => 0, 'unexpected' => 0);
$assert = static function ($name, $condition) use (&$checks) {
    $checks[$name] = (bool) $condition;
    echo $name . '=' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
};

register_shutdown_function(static function () use ($previous, $sentinel, $temporary_file) {
    if ($previous === $sentinel) {
        delete_option('zmp_youtube_settings');
    } else {
        update_option('zmp_youtube_settings', $previous, false);
    }
    if (is_file($temporary_file)) {
        unlink($temporary_file);
    }
    $restored = $previous === $sentinel
        ? get_option('zmp_youtube_settings', $sentinel) === $sentinel
        : get_option('zmp_youtube_settings', $sentinel) === $previous;
    echo 'synthetic_settings_restored=' . ($restored ? 'PASS' : 'FAIL') . PHP_EOL;
    echo 'synthetic_upload_file_removed=' . (!is_file($temporary_file) ? 'PASS' : 'FAIL') . PHP_EOL;
});

$encrypt = new ReflectionMethod('ZMP_YouTube', 'encrypt');
$encrypt->setAccessible(true);
$settings = array(
    'client_id' => 'synthetic-offline-test.apps.googleusercontent.com',
    'client_secret_enc' => $encrypt->invoke(null, 'synthetic-client-secret-never-sent'),
    'refresh_token_enc' => $encrypt->invoke(null, 'synthetic-refresh-token-never-sent'),
    'channel_id' => 'synthetic-channel-id',
);
$assert('synthetic_credential_sealing_created', !empty($settings['client_secret_enc']) && !empty($settings['refresh_token_enc']));
update_option('zmp_youtube_settings', $settings, false);

add_filter('pre_http_request', static function ($pre, $args, $url) use (&$mock_counts) {
    $method = strtoupper((string) ($args['method'] ?? 'GET'));
    $headers = (array) ($args['headers'] ?? array());
    $range = (string) ($headers['Content-Range'] ?? $headers['content-range'] ?? '');
    $body = '';
    $code = 200;
    $response_headers = array();

    if ($url === 'https://oauth2.googleapis.com/token' && $method === 'POST') {
        $mock_counts['token']++;
        $body = wp_json_encode(array('access_token' => 'synthetic-access-token'));
    } elseif (strpos($url, 'https://www.googleapis.com/upload/youtube/v3/videos?') === 0 && $method === 'POST') {
        $mock_counts['session']++;
        $response_headers['location'] = 'https://www.googleapis.com/upload/youtube/v3/videos?upload_id=synthetic-session';
    } elseif (strpos($url, 'https://www.googleapis.com/upload/youtube/') === 0 && $method === 'PUT' && strpos($range, 'bytes */') === 0) {
        $mock_counts['probe']++;
        $code = 308;
    } elseif (strpos($url, 'https://www.googleapis.com/upload/youtube/') === 0 && $method === 'PUT' && strpos($range, 'bytes 0-') === 0) {
        $mock_counts['chunk']++;
        $code = 201;
        $body = wp_json_encode(array(
            'id' => 'AbcdefGhijk',
            'status' => array('privacyStatus' => 'unlisted'),
            'processingDetails' => array('processingStatus' => 'processing'),
        ));
    } else {
        $mock_counts['unexpected']++;
        return new WP_Error('synthetic_test_blocked_unexpected_http');
    }

    return array(
        'headers' => $response_headers,
        'body' => $body,
        'response' => array('code' => $code, 'message' => 'Synthetic response'),
        'cookies' => array(),
        'filename' => null,
    );
}, 10, 3);

$entry = (object) array('registration_key' => 'synthetic-registration-key', 'academic_year' => '2099/2100');
$start = new ReflectionMethod('ZMP_YouTube', 'start_resumable_upload');
$start->setAccessible(true);
$session = $start->invoke(null, $entry, 'performance', 'video/mp4', 256);
$assert('mocked_resumable_session_created', is_array($session) && !is_wp_error($session));
$decrypt = new ReflectionMethod('ZMP_YouTube', 'decrypt');
$decrypt->setAccessible(true);
$assert('session_url_stored_encrypted', is_array($session) && !empty($session['encrypted_url']) && $decrypt->invoke(null, $session['encrypted_url']) === $session['url']);

wp_mkdir_p(dirname($temporary_file));
file_put_contents($temporary_file, str_repeat('S', 256));
$resume = new ReflectionMethod('ZMP_YouTube', 'resume_upload');
$resume->setAccessible(true);
$result = $resume->invoke(null, 999999, 999999, $temporary_file, 256, 'video/mp4', $session['url'], 0);
$assert('mocked_resumable_transfer_parsed', is_array($result) && $result['id'] === 'AbcdefGhijk' && $result['privacy'] === 'unlisted' && $result['processing'] === 'processing');
$assert('only_expected_mocked_http_calls', $mock_counts === array('token' => 2, 'session' => 1, 'probe' => 1, 'chunk' => 1, 'unexpected' => 0));

foreach ($mock_counts as $name => $count) {
    echo 'mock_http_' . $name . '=' . (int) $count . PHP_EOL;
}
if (in_array(false, $checks, true)) {
    exit(1);
}
echo 'Success: YouTube helper flow used only intercepted synthetic HTTP; settings and temp file are restored/removed at shutdown.' . PHP_EOL;
