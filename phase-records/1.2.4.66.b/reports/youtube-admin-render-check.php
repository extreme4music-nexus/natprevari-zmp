<?php
/** Render-only check of the YouTube administrator page on synthetic local WP. */
if (!defined('ABSPATH') || !class_exists('ZMP_YouTube')) { fwrite(STDERR, "wordpress_or_youtube_class_missing\n"); exit(2); }
wp_set_current_user(1);
$_GET = array();
ob_start();
ZMP_YouTube::render_admin_page();
$html = ob_get_clean();
$checks = array(
  'page_heading' => strpos($html, 'YouTube — видео за онлајн регионални натпревари') !== false,
  'client_id_field' => strpos($html, 'OAuth Client ID') !== false,
  'secret_password_field' => strpos($html, 'type="password" name="client_secret"') !== false,
  'redirect_uri_visible' => strpos($html, 'admin-post.php?action=zmp_youtube_callback') !== false,
  'unlisted_warning' => strpos($html, 'Unlisted') !== false && strpos($html, 'не е приватно') !== false,
  'video_rules_visible' => strpos($html, 'Вокална интерпретација') !== false && strpos($html, 'Државните натпревари') !== false,
  'disconnected_setup_hint' => strpos($html, 'Зачувајте OAuth Client ID и secret') !== false,
);
$failed = false;
foreach ($checks as $name => $ok) { echo $name . '=' . ($ok ? 'PASS' : 'FAIL') . "\n"; if (!$ok) $failed = true; }
echo 'connected=' . (ZMP_YouTube::is_connected() ? 'yes' : 'no') . "\n";
echo "No settings were submitted or changed; no network request was made.\n";
exit($failed ? 1 : 0);
