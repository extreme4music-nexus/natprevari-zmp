<?php
if (!defined('ABSPATH')) { exit; }

/** Admin-curated links for external audio tools. Audio is never sent by the plugin. */
class ZMP_Approved_Audio_Tools {
    const OPTION = 'zmp_approved_audio_tools';
    const PAGE = 'zmp-approved-audio-tools';
    const MAX_TOOLS = 25;
    const AUDITS_PER_RUN = 2;
    const AUDIT_MAX_AGE_DAYS = 14;

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_page'));
        add_action('admin_post_zmp_audio_tool_save', array(__CLASS__, 'save'));
        add_action('admin_post_zmp_audio_tool_audit', array(__CLASS__, 'audit'));
        add_action('admin_post_zmp_audio_tool_approve', array(__CLASS__, 'approve'));
        add_action('admin_post_zmp_audio_tool_reject', array(__CLASS__, 'reject'));
        add_action('admin_post_zmp_audio_tool_archive', array(__CLASS__, 'archive'));
        add_action('admin_notices', array(__CLASS__, 'admin_notice'));
        add_action('init', array(__CLASS__, 'schedule_audit'));
        add_action('zmp_approved_audio_tools_daily_audit', array(__CLASS__, 'automated_audit_batch'));
    }

    public static function schedule_audit() {
        if (!wp_next_scheduled('zmp_approved_audio_tools_daily_audit')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zmp_approved_audio_tools_daily_audit');
        }
    }

    public static function deactivate() { wp_clear_scheduled_hook('zmp_approved_audio_tools_daily_audit'); }

    public static function add_admin_page() {
        add_submenu_page('zmp-competitions', 'Аудио алатки', 'Аудио алатки', 'manage_options', self::PAGE, array(__CLASS__, 'render_admin_page'));
    }

    private static function can_manage() { return current_user_can('manage_options'); }

    private static function posted_text($key) {
        $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        if (!is_scalar($value)) { return ''; }
        $value = trim((string) $value);
        return function_exists('mb_substr') ? mb_substr($value, 0, 5000, 'UTF-8') : substr($value, 0, 5000);
    }

    private static function posted_area($key, $max_length = 1500) {
        $value = sanitize_textarea_field(self::posted_text($key));
        return function_exists('mb_substr') ? mb_substr($value, 0, $max_length, 'UTF-8') : substr($value, 0, $max_length);
    }

    private static function all_tools() {
        $tools = get_option(self::OPTION, array());
        return is_array($tools) ? $tools : array();
    }

    private static function category_label($category) {
        $labels = array('vocal_remover' => 'Отстранување вокал', 'key_change' => 'Промена на тоналитет');
        return isset($labels[$category]) ? $labels[$category] : 'Непозната категорија';
    }

    /** Reject non-HTTPS, private/unsafe URLs, and obvious video-download links. */
    private static function safe_url($raw) {
        if (!is_scalar($raw)) { return ''; }
        $url = esc_url_raw(trim((string) $raw), array('https'));
        if (strlen($url) > 2048) { return ''; }
        $parts = wp_parse_url($url);
        if (!$url || !is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) { return ''; }
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if (!empty($parts['user']) || !empty($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) { return ''; }
        foreach (array(home_url(), site_url()) as $site_url) {
            $site_host = wp_parse_url($site_url, PHP_URL_HOST);
            if (is_string($site_host) && strtolower(rtrim($site_host, '.')) === $host) { return ''; }
        }
        $ip_host = trim($host, '[]');
        if (filter_var($ip_host, FILTER_VALIDATE_IP) !== false && filter_var($ip_host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) { return ''; }
        $blocked_hosts = array('youtube.com', 'youtu.be', 'ytmp3.cc', 'yt-dlp.org');
        foreach ($blocked_hosts as $blocked) {
            if ($host === $blocked || substr($host, -strlen('.' . $blocked)) === '.' . $blocked) { return ''; }
        }
        foreach (array('youtube', 'youtu.be', 'yt-dlp', 'ytmp3', 'youtube-to-mp3') as $fragment) {
            if (stripos($url, $fragment) !== false) { return ''; }
        }
        return $url;
    }

    private static function public_url($raw) {
        $url = self::safe_url($raw);
        if ($url === '' || !function_exists('wp_http_validate_url')) { return ''; }
        $validated = wp_http_validate_url($url);
        return is_string($validated) && $validated !== '' ? esc_url_raw($validated, array('https')) : '';
    }

    private static function safe_id($id) { return is_scalar($id) ? sanitize_key((string) $id) : ''; }

    private static function audit_is_fresh($tool) {
        $checked = is_array($tool) ? sanitize_text_field((string) ($tool['auto_checked_at'] ?? '')) : '';
        $timestamp = $checked !== '' ? strtotime($checked . ' UTC') : false;
        $now = time();
        return $timestamp !== false && $timestamp <= $now && ($now - $timestamp) <= self::AUDIT_MAX_AGE_DAYS * DAY_IN_SECONDS;
    }

    /** A server-side denial is inconclusive; only a recent human review of this URL can expose it. */
    private static function manual_review_is_fresh($tool) {
        if (!is_array($tool)) { return false; }
        $checked = sanitize_text_field((string) ($tool['manual_checked_at'] ?? ''));
        $timestamp = $checked !== '' ? strtotime($checked . ' UTC') : false;
        $now = time();
        if ($timestamp === false || $timestamp > $now || ($now - $timestamp) > self::AUDIT_MAX_AGE_DAYS * DAY_IN_SECONDS) { return false; }
        if (absint($tool['manual_reviewed_by'] ?? 0) < 1) { return false; }
        if (strlen(trim((string) ($tool['manual_review_note'] ?? ''))) < 5) { return false; }
        $required = array('privacy','terms','free','format','quality','processing','rights','youtube_excluded','url_opened');
        $reviewed = array();
        foreach ((array) ($tool['manual_reviewed_checks'] ?? array()) as $check) {
            if (is_scalar($check)) { $reviewed[] = sanitize_key((string) $check); }
        }
        if (array_diff($required, $reviewed)) { return false; }
        $url = self::public_url($tool['url'] ?? '');
        $reviewed_url_hash = sanitize_text_field((string) ($tool['manual_review_url_hash'] ?? ''));
        return $url !== '' && $reviewed_url_hash !== '' && hash_equals(hash('sha256', $url), $reviewed_url_hash);
    }

    public static function teacher_visible_tools() {
        $visible = array();
        foreach (self::all_tools() as $tool) {
            if (!is_array($tool) || ($tool['manual_status'] ?? '') !== 'approved' || !self::audit_is_fresh($tool)) { continue; }
            $audit_state = (string) ($tool['audit_state'] ?? '');
            $available_by_probe = $audit_state === 'available';
            $uncheckable_without_response = $audit_state === 'uncheckable' && (int) ($tool['http_status'] ?? 0) === 0;
            $manual_reviewable_state = $audit_state === 'probe_denied' || $uncheckable_without_response;
            $available_by_manual_review = $manual_reviewable_state && self::manual_review_is_fresh($tool);
            if (!$available_by_probe && !$available_by_manual_review) { continue; }
            $category = sanitize_key((string) ($tool['category'] ?? ''));
            if (!in_array($category, array('vocal_remover', 'key_change'), true)) { continue; }
            $url = self::safe_url($tool['url'] ?? '');
            if ($url === '') { continue; }
            $visible[] = array(
                'name' => sanitize_text_field((string) ($tool['name'] ?? '')),
                'category' => $category,
                'url' => $url,
                'privacy_url' => self::safe_url($tool['privacy_url'] ?? ''),
                'terms_url' => self::safe_url($tool['terms_url'] ?? ''),
                'free_limits' => sanitize_textarea_field((string) ($tool['free_limits'] ?? '')),
                'duration_format_limits' => sanitize_textarea_field((string) ($tool['duration_format_limits'] ?? '')),
                'quality_notes' => sanitize_textarea_field((string) ($tool['quality_notes'] ?? '')),
                'rights_note' => sanitize_textarea_field((string) ($tool['rights_note'] ?? '')),
                'processing_claim' => sanitize_textarea_field((string) ($tool['processing_claim'] ?? '')),
                'manual_checked_at' => sanitize_text_field((string) ($tool['manual_checked_at'] ?? '')),
            );
        }
        return array_values(array_filter($visible, function($tool) { return $tool['name'] !== ''; }));
    }

    private static function save_tools($tools) { update_option(self::OPTION, $tools, false); }

    private static function finish($notice) {
        wp_safe_redirect(add_query_arg(array('page' => self::PAGE, 'notice' => sanitize_key($notice)), admin_url('admin.php')));
        exit;
    }

    private static function require_admin() {
        if (!self::can_manage()) { wp_die('Немате пристап.', '', array('response' => 403)); }
    }

    public static function save() {
        self::require_admin();
        check_admin_referer('zmp_audio_tool_save');
        $name = self::posted_text('name');
        $name = function_exists('mb_substr') ? mb_substr($name, 0, 150, 'UTF-8') : substr($name, 0, 150);
        $name = sanitize_text_field($name);
        $category = sanitize_key(self::posted_text('category'));
        $url = self::public_url(self::posted_text('url'));
        $privacy_raw = self::posted_text('privacy_url');
        $terms_raw = self::posted_text('terms_url');
        $privacy = self::public_url($privacy_raw);
        $terms = self::public_url($terms_raw);
        $id = self::safe_id(self::posted_text('id'));
        if ($name === '' || $url === '' || !in_array($category, array('vocal_remover', 'key_change'), true)
            || $privacy === ''
            || $terms === '') {
            self::finish('invalid');
        }
        $tools = self::all_tools();
        $existing = isset($tools[$id]) && is_array($tools[$id]) ? $tools[$id] : array();
        $is_new = $id === '' || !$existing;
        $active_count = count(array_filter($tools, function($tool) { return is_array($tool) && ($tool['manual_status'] ?? '') !== 'archived'; }));
        $reactivating_archived = ($existing['manual_status'] ?? '') === 'archived';
        if (($is_new || ($reactivating_archived && $existing)) && $active_count >= self::MAX_TOOLS) { self::finish('limit'); }
        if ($is_new) { $id = wp_generate_uuid4(); }
        $same_url = !empty($existing['url']) && hash_equals((string) $existing['url'], $url);
        $same_review = $same_url
            && (string) ($existing['category'] ?? '') === $category
            && (string) ($existing['privacy_url'] ?? '') === $privacy
            && (string) ($existing['terms_url'] ?? '') === $terms
            && (string) ($existing['free_limits'] ?? '') === self::posted_area('free_limits')
            && (string) ($existing['duration_format_limits'] ?? '') === self::posted_area('duration_format_limits')
            && (string) ($existing['quality_notes'] ?? '') === self::posted_area('quality_notes')
            && (string) ($existing['rights_note'] ?? '') === self::posted_area('rights_note')
            && (string) ($existing['processing_claim'] ?? '') === self::posted_area('processing_claim');
        $tools[$id] = array(
            'name' => $name,
            'category' => $category,
            'url' => $url,
            'privacy_url' => $privacy,
            'terms_url' => $terms,
            'free_limits' => self::posted_area('free_limits'),
            'duration_format_limits' => self::posted_area('duration_format_limits'),
            'quality_notes' => self::posted_area('quality_notes'),
            'rights_note' => self::posted_area('rights_note'),
            'processing_claim' => self::posted_area('processing_claim'),
            'manual_status' => $same_review ? ($existing['manual_status'] ?? 'pending') : 'pending',
            'audit_state' => $same_url ? ($existing['audit_state'] ?? 'not_checked') : 'not_checked',
            'http_status' => $same_url ? (int) ($existing['http_status'] ?? 0) : 0,
            'redirect_host' => $same_url ? sanitize_text_field((string) ($existing['redirect_host'] ?? '')) : '',
            'auto_checked_at' => $same_url ? sanitize_text_field((string) ($existing['auto_checked_at'] ?? '')) : '',
            'page_fingerprint' => $same_url ? sanitize_text_field((string) ($existing['page_fingerprint'] ?? '')) : '',
            'manual_checked_at' => $same_review ? sanitize_text_field((string) ($existing['manual_checked_at'] ?? '')) : '',
            'manual_review_note' => $same_review ? sanitize_textarea_field((string) ($existing['manual_review_note'] ?? '')) : '',
            'manual_reviewed_checks' => $same_review && is_array($existing['manual_reviewed_checks'] ?? null) ? $existing['manual_reviewed_checks'] : array(),
            'manual_reviewed_by' => $same_review ? absint($existing['manual_reviewed_by'] ?? 0) : 0,
            'manual_review_url_hash' => $same_review ? sanitize_text_field((string) ($existing['manual_review_url_hash'] ?? '')) : '',
            'needs_admin_attention' => !$same_review && ($existing['manual_status'] ?? '') === 'approved',
        );
        self::save_tools($tools);
        self::finish('saved');
    }

    private static function check_tool_url($raw_url, $previous_tool = array()) {
        $url = self::public_url($raw_url);
        if ($url === '') { return array('state'=>'uncheckable', 'http_status'=>0, 'redirect_host'=>'', 'page_fingerprint'=>''); }
        // One bounded request; redirects are recorded for review and never followed.
        $response = wp_safe_remote_request($url, array(
            'method' => 'GET', 'timeout' => 3, 'redirection' => 0,
            'limit_response_size' => 1024,
            'sslverify' => true,
            'headers' => array('Accept' => 'text/html,application/xhtml+xml'),
        ));
        if (is_wp_error($response)) { return array('state'=>'uncheckable', 'http_status'=>0, 'redirect_host'=>'', 'page_fingerprint'=>''); }
        $http_status = (int) wp_remote_retrieve_response_code($response);
        $location = wp_remote_retrieve_header($response, 'location');
        if ($http_status >= 300 && $http_status < 400) {
            $target = wp_parse_url(is_string($location) ? $location : '', PHP_URL_HOST);
            return array('state'=>'redirect_review', 'http_status'=>$http_status, 'redirect_host'=>is_string($target) && $target !== '' ? sanitize_text_field(strtolower($target)) : 'релативно пренасочување', 'page_fingerprint'=>'');
        }
        if ($http_status >= 200 && $http_status < 300) {
            $body = wp_remote_retrieve_body($response);
            $etag = (string) wp_remote_retrieve_header($response, 'etag');
            $last_modified = (string) wp_remote_retrieve_header($response, 'last-modified');
            $fingerprint_material = implode("\n", array($etag, $last_modified, is_string($body) ? $body : ''));
            $fingerprint = trim($fingerprint_material) !== '' ? hash('sha256', $fingerprint_material) : '';
            if ($fingerprint === '') { return array('state'=>'uncheckable', 'http_status'=>$http_status, 'redirect_host'=>'', 'page_fingerprint'=>''); }
            $previous = is_array($previous_tool) ? sanitize_text_field((string) ($previous_tool['page_fingerprint'] ?? '')) : '';
            $state = $fingerprint !== '' && $previous !== '' && !hash_equals($previous, $fingerprint) ? 'changed_review' : 'available';
            return array('state'=>$state, 'http_status'=>$http_status, 'redirect_host'=>'', 'page_fingerprint'=>$fingerprint);
        }
        // 401/403 means this server-side probe was denied. It does not establish
        // whether a human visitor can open the link in a browser.
        if (in_array($http_status, array(401, 403), true)) { return array('state'=>'probe_denied', 'http_status'=>$http_status, 'redirect_host'=>'', 'page_fingerprint'=>''); }
        if ($http_status >= 400 && $http_status < 600) { return array('state'=>'unavailable', 'http_status'=>$http_status, 'redirect_host'=>'', 'page_fingerprint'=>''); }
        return array('state'=>'uncheckable', 'http_status'=>$http_status, 'redirect_host'=>'', 'page_fingerprint'=>'');
    }

    private static function store_audit_result(&$tool, $result) {
        $was_approved = ($tool['manual_status'] ?? '') === 'approved';
        $tool['audit_state'] = $result['state'];
        $tool['http_status'] = (int) $result['http_status'];
        $tool['redirect_host'] = sanitize_text_field((string) $result['redirect_host']);
        $tool['auto_checked_at'] = current_time('mysql', true);
        if (!empty($result['page_fingerprint'])) { $tool['page_fingerprint'] = sanitize_text_field((string) $result['page_fingerprint']); }
        $uncheckable_without_response = ($result['state'] ?? '') === 'uncheckable' && (int) ($result['http_status'] ?? 0) === 0;
        if (in_array(($result['state'] ?? ''), array('probe_denied'), true) || $uncheckable_without_response) {
            if (self::manual_review_is_fresh($tool)) {
                // Keep an explicit, recent human approval for the exact URL.
                // The probe state remains visible and is never automated success.
                $tool['manual_status'] = 'approved';
                $tool['needs_admin_attention'] = false;
            } else {
                $tool['manual_status'] = 'pending';
                if ($was_approved) { $tool['needs_admin_attention'] = true; }
            }
        } elseif ($result['state'] !== 'available') {
            $tool['manual_status'] = 'pending';
            if ($was_approved) { $tool['needs_admin_attention'] = true; }
        }
    }

    public static function audit() {
        self::require_admin();
        $id = self::safe_id(self::posted_text('id'));
        check_admin_referer('zmp_audio_tool_audit_' . $id);
        $tools = self::all_tools();
        if ($id === '' || empty($tools[$id]) || !is_array($tools[$id])) { self::finish('missing'); }
        self::store_audit_result($tools[$id], self::check_tool_url($tools[$id]['url'] ?? '', $tools[$id]));
        self::save_tools($tools);
        self::finish('audited');
    }

    public static function automated_audit_batch() {
        $tools = self::all_tools();
        $ids = array_keys($tools);
        $total = count($ids);
        if (!$total) { return; }
        $cursor = absint(get_option('zmp_approved_audio_tools_audit_cursor', 0)) % $total;
        $visited = 0; $checked = 0;
        while ($visited < $total && $checked < self::AUDITS_PER_RUN) {
            $id = $ids[($cursor + $visited) % $total];
            $visited++;
            if (!isset($tools[$id]) || !is_array($tools[$id]) || ($tools[$id]['manual_status'] ?? '') !== 'approved') { continue; }
            self::store_audit_result($tools[$id], self::check_tool_url($tools[$id]['url'] ?? '', $tools[$id]));
            $checked++;
        }
        update_option('zmp_approved_audio_tools_audit_cursor', ($cursor + $visited) % $total, false);
        if ($checked) { self::save_tools($tools); }
    }

    public static function admin_notice() {
        if (!self::can_manage()) { return; }
        $count = 0; $stale = 0;
        foreach (self::all_tools() as $tool) {
            if (!is_array($tool)) { continue; }
            if (!empty($tool['needs_admin_attention'])) { $count++; }
            if (($tool['manual_status'] ?? '') === 'approved' && ($tool['audit_state'] ?? '') === 'available' && !self::audit_is_fresh($tool)) { $stale++; }
        }
        if ($count || $stale) {
            $url = add_query_arg(array('page'=>self::PAGE), admin_url('admin.php'));
            $message = array();
            if ($count) { $message[] = sprintf('%d алатки бараат рачен преглед по неуспешна или променета автоматска проверка', $count); }
            if ($stale) { $message[] = sprintf('%d одобрени линкови се скриени бидејќи проверката е постара од %d дена', $stale, self::AUDIT_MAX_AGE_DAYS); }
            echo '<div class="notice notice-warning"><p>' . esc_html(implode('. ', $message) . '.') . ' <a href="' . esc_url($url) . '">Отвори каталог</a></p></div>';
        }
    }

    public static function approve() {
        self::require_admin();
        $id = self::safe_id(self::posted_text('id'));
        check_admin_referer('zmp_audio_tool_approve_' . $id);
        $tools = self::all_tools();
        if ($id === '' || empty($tools[$id]) || !is_array($tools[$id])) { self::finish('missing'); }
        if (($tools[$id]['manual_status'] ?? '') === 'archived') { self::finish('archived_locked'); }
        $tool = $tools[$id];
        $review_complete = self::public_url($tool['url'] ?? '') !== ''
            && self::public_url($tool['privacy_url'] ?? '') !== ''
            && self::public_url($tool['terms_url'] ?? '') !== ''
            && trim((string) ($tool['free_limits'] ?? '')) !== ''
            && trim((string) ($tool['duration_format_limits'] ?? '')) !== ''
            && trim((string) ($tool['quality_notes'] ?? '')) !== ''
            && trim((string) ($tool['rights_note'] ?? '')) !== ''
            && trim((string) ($tool['processing_claim'] ?? '')) !== '';
        $audit_state = (string) ($tool['audit_state'] ?? '');
        $uncheckable_without_response = $audit_state === 'uncheckable' && (int) ($tool['http_status'] ?? 0) === 0;
        if ((!in_array($audit_state, array('available', 'changed_review', 'probe_denied'), true) && !$uncheckable_without_response) || !self::audit_is_fresh($tool)) { self::finish('audit_required'); }
        if (!$review_complete) { self::finish('review_fields_required'); }
        $posted_reviewed = isset($_POST['reviewed']) ? wp_unslash($_POST['reviewed']) : array();
        $reviewed = is_array($posted_reviewed) ? array_map('sanitize_key', array_filter($posted_reviewed, 'is_scalar')) : array();
        $required_reviewed = array('privacy','terms','free','format','quality','processing','rights','youtube_excluded');
        if (($tool['audit_state'] ?? '') === 'probe_denied' || $uncheckable_without_response) { $required_reviewed[] = 'url_opened'; }
        if (array_diff($required_reviewed, $reviewed)) { self::finish('manual_review_required'); }
        $manual_note = self::posted_area('manual_review_note');
        if (strlen($manual_note) < 5) { self::finish('manual_review_required'); }
        $reviewer_id = absint(get_current_user_id());
        if ($reviewer_id < 1) { self::finish('manual_review_required'); }
        $tools[$id]['manual_status'] = 'approved';
        // Retain inconclusive probe states after a human confirms the URL.
        // They must not be represented as an automated success.
        $tools[$id]['audit_state'] = in_array($audit_state, array('probe_denied', 'uncheckable'), true) ? $audit_state : 'available';
        $tools[$id]['manual_checked_at'] = current_time('mysql', true);
        $tools[$id]['manual_review_note'] = $manual_note;
        $tools[$id]['manual_reviewed_checks'] = $required_reviewed;
        $tools[$id]['manual_reviewed_by'] = $reviewer_id;
        $tools[$id]['manual_review_url_hash'] = hash('sha256', self::public_url($tool['url'] ?? ''));
        $tools[$id]['needs_admin_attention'] = false;
        self::save_tools($tools);
        self::finish('approved');
    }

    public static function reject() {
        self::require_admin();
        $id = self::safe_id(self::posted_text('id'));
        check_admin_referer('zmp_audio_tool_reject_' . $id);
        $tools = self::all_tools();
        if ($id === '' || empty($tools[$id]) || !is_array($tools[$id])) { self::finish('missing'); }
        if (($tools[$id]['manual_status'] ?? '') === 'archived') { self::finish('archived_locked'); }
        $note = self::posted_area('manual_review_note', 500);
        if (strlen($note) < 5) { self::finish('manual_review_required'); }
        $tools[$id]['manual_status'] = 'rejected';
        $tools[$id]['manual_checked_at'] = current_time('mysql', true);
        $tools[$id]['manual_review_note'] = $note;
        $tools[$id]['manual_reviewed_checks'] = array();
        $tools[$id]['needs_admin_attention'] = false;
        self::save_tools($tools);
        self::finish('rejected');
    }

    public static function archive() {
        self::require_admin();
        $id = self::safe_id(self::posted_text('id'));
        check_admin_referer('zmp_audio_tool_archive_' . $id);
        $tools = self::all_tools();
        if ($id === '' || empty($tools[$id]) || !is_array($tools[$id])) { self::finish('missing'); }
        $note = self::posted_area('manual_review_note', 500);
        if (strlen($note) < 5) { self::finish('manual_review_required'); }
        $tools[$id]['manual_status'] = 'archived';
        $tools[$id]['manual_checked_at'] = current_time('mysql', true);
        $tools[$id]['manual_review_note'] = $note;
        $tools[$id]['manual_reviewed_checks'] = array();
        $tools[$id]['needs_admin_attention'] = false;
        self::save_tools($tools);
        self::finish('archived');
    }

    private static function action_form($action, $nonce_action, $id, $label, $class = 'button') {
        echo '<form class="zmp-audio-tool-action" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr($action) . '"><input type="hidden" name="id" value="' . esc_attr($id) . '">';
        wp_nonce_field($nonce_action);
        echo '<button class="' . esc_attr($class) . '" type="submit">' . esc_html($label) . '</button></form>';
    }

    private static function manual_review_form($id) {
        echo '<form class="zmp-audio-tool-review" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="zmp_audio_tool_approve"><input type="hidden" name="id" value="' . esc_attr($id) . '">';
        wp_nonce_field('zmp_audio_tool_approve_' . $id);
        $tool = self::all_tools()[$id] ?? array();
        $probe_denied = ($tool['audit_state'] ?? '') === 'probe_denied';
        $uncheckable_without_response = ($tool['audit_state'] ?? '') === 'uncheckable' && (int) ($tool['http_status'] ?? 0) === 0;
        $checks = array('privacy'=>'Приватност','terms'=>'Услови','free'=>'Бесплатен план','format'=>'Формат и должина','quality'=>'Квалитет','processing'=>'Обработка на аудио','rights'=>'Права','youtube_excluded'=>'Не е YouTube-to-MP3/downloader');
        if ($probe_denied || $uncheckable_without_response) { $checks['url_opened'] = 'Лично го отворив линкот во прелистувач'; }
        if ($probe_denied) { echo '<p class="notice notice-warning">Серверската проверка доби HTTP ' . (int) ($tool['http_status'] ?? 0) . '. Ова е одбивање на автоматската проверка и не докажува дека линкот е скршен. Отворете го линкот во прелистувач и потврдете го подолу по лична проверка.</p>'; }
        if ($uncheckable_without_response) { echo '<p class="notice notice-warning">Серверската проверка не можеше да заврши (HTTP 0). Пред рачно одобрување, лично отворете го линкот во прелистувач и потврдете го подолу.</p>'; }
        echo '<fieldset class="zmp-audio-tool-review-checks"><legend>Задолжителни проверки пред рачно одобрување</legend>';
        foreach ($checks as $key => $label) {
            echo '<label style="display:block"><input type="checkbox" name="reviewed[]" value="' . esc_attr($key) . '"> ' . esc_html($label) . '</label>';
        }
        echo '</fieldset>';
        echo '<label style="display:block;margin:6px 0">Белешка од рачната проверка<br><textarea name="manual_review_note" rows="2" maxlength="1500" required></textarea></label>';
        echo '<button class="button button-primary" type="submit">Рачно одобри</button></form>';
    }

    private static function rejection_form($id, $already_approved = false) {
        echo '<form class="zmp-audio-tool-action" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="zmp_audio_tool_reject"><input type="hidden" name="id" value="' . esc_attr($id) . '">';
        wp_nonce_field('zmp_audio_tool_reject_' . $id);
        echo '<label>Причина за ' . ($already_approved ? 'повлекување' : 'одбивање') . '<br><input type="text" name="manual_review_note" maxlength="500" required></label> ';
        echo '<button class="button" type="submit">' . ($already_approved ? 'Повлечи одобрување' : 'Одбиј кандидат') . '</button></form>';
    }

    private static function archive_form($id) {
        echo '<form class="zmp-audio-tool-action" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="zmp_audio_tool_archive"><input type="hidden" name="id" value="' . esc_attr($id) . '">';
        wp_nonce_field('zmp_audio_tool_archive_' . $id);
        echo '<label>Причина за архивација<br><input type="text" name="manual_review_note" maxlength="500" required></label> ';
        echo '<button class="button" type="submit">Архивирај</button></form>';
    }

    public static function render_admin_page() {
        self::require_admin();
        $tools = self::all_tools();
        $edit_id = self::safe_id(isset($_GET['edit']) ? wp_unslash($_GET['edit']) : '');
        $editing = isset($tools[$edit_id]) && is_array($tools[$edit_id]) ? $tools[$edit_id] : array();
        $notice = self::safe_id(isset($_GET['notice']) ? wp_unslash($_GET['notice']) : '');
        $notices = array('invalid'=>'Проверете го името, категоријата и HTTPS линковите.','saved'=>'Алатката е зачувана.','limit'=>'Каталогот достигна лимит од 25 активни кандидати. Архивирајте стар кандидат пред да додадете нов.','audited'=>'Автоматската проверка е зачувана; таа не одобрува алатка.','approved'=>'Алатката е рачно одобрена.','revoked'=>'Одобрувањето е повлечено.','rejected'=>'Алатката е одбиена.','archived'=>'Кандидатот е архивиран.','archived_locked'=>'Архивиран кандидат не може да се одобрува или одбива директно. Уредете го за нов преглед.','audit_required'=>'Потребна е свежа URL проверка. Ако серверската проверка е одбиена, отворете го линкот и направете ја рачната проверка.','review_fields_required'=>'Пред одобрување внесете политика за приватност, услови, бесплатни и форматски ограничувања, квалитет, права и изјава на сервисот.','manual_review_required'=>'За одлуката внесете белешка и, при одобрување, означете ги сите рачни проверки.','missing'=>'Алатката не е пронајдена.');
        $attention = false;
        foreach ($tools as $tool_id => $tool) {
            if (is_array($tool) && !empty($tool['needs_admin_attention'])) { $attention = true; unset($tools[$tool_id]['needs_admin_attention']); }
        }
        if ($attention) { self::save_tools($tools); }
        echo '<div class="wrap"><h1>Надворешни аудио алатки</h1>';
        echo '<p>Плагинот не испраќа аудио до сервисите. Не додавајте YouTube-to-MP3 конвертори или downloader-и. Проверката на линкови не е безбедносна сертификација. Автоматскиот аудит проверува најмногу две одобрени алатки дневно, со HTTPS, проверка на сертификат, максимум 1 KB одговор и без следење пренасочувања. Различен краток отпечаток бара рачна проверка; не е безбедносен заклучок. Одобрувањето секогаш е рачно.</p>';
        if (isset($notices[$notice])) { echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($notices[$notice]) . '</p></div>'; }
        echo '<h2>' . ($editing ? 'Уреди алатка' : 'Додај алатка') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="zmp_audio_tool_save"><input type="hidden" name="id" value="' . esc_attr($edit_id) . '">';
        wp_nonce_field('zmp_audio_tool_save');
        echo '<table class="form-table"><tbody>';
        self::input_row('Име', 'name', $editing['name'] ?? '', true);
        echo '<tr><th><label for="zmp-audio-category">Категорија</label></th><td><select id="zmp-audio-category" name="category" required><option value="vocal_remover" ' . selected($editing['category'] ?? '', 'vocal_remover', false) . '>Отстранување вокал</option><option value="key_change" ' . selected($editing['category'] ?? '', 'key_change', false) . '>Промена на тоналитет</option></select></td></tr>';
        self::input_row('HTTPS линк', 'url', $editing['url'] ?? '', true, 'url');
        self::input_row('Политика за приватност', 'privacy_url', $editing['privacy_url'] ?? '', true, 'url');
        self::input_row('Услови за користење', 'terms_url', $editing['terms_url'] ?? '', true, 'url');
        self::input_row('Бесплатни ограничувања', 'free_limits', $editing['free_limits'] ?? '');
        self::input_row('Ограничувања за должина и формат', 'duration_format_limits', $editing['duration_format_limits'] ?? '');
        self::input_row('Изјава на сервисот за обработка на аудио', 'processing_claim', $editing['processing_claim'] ?? '');
        self::input_row('Забелешка за квалитет', 'quality_notes', $editing['quality_notes'] ?? '');
        self::input_row('Права за снимката', 'rights_note', $editing['rights_note'] ?? '');
        echo '</tbody></table><p><button class="button button-primary" type="submit">Зачувај како ' . ($editing ? 'ажурирање' : 'неодобрена алатка') . '</button></p></form>';
        echo '<h2>Каталог и аудит</h2><div class="zmp-audio-tools-table-wrap" role="region" aria-label="Каталог и аудит на аудио-алатки" tabindex="0"><table class="widefat striped"><thead><tr><th>Алатка</th><th>Функција</th><th>Автоматска проверка</th><th>Рачна одлука и белешка</th><th>Проверени услови</th><th>Дејства</th></tr></thead><tbody>';
        if (!$tools) { echo '<tr><td colspan="6">Нема внесени кандидати. Додајте ги само алатките од контролираниот проектен список.</td></tr>'; }
        foreach ($tools as $id => $tool) {
            if (!is_array($tool)) { continue; }
            $manual_states = array('approved'=>'Одобрена', 'rejected'=>'Одбиена', 'pending'=>'Чека рачен преглед', 'archived'=>'Архивирана');
            $manual = $manual_states[$tool['manual_status'] ?? 'pending'] ?? 'Чека рачен преглед';
            $state = sanitize_key((string) ($tool['audit_state'] ?? 'not_checked'));
            if (($tool['manual_status'] ?? 'pending') === 'pending' && in_array($state, array('unavailable', 'not_checked'), true)) {
                $manual = 'Потребна е проверка пред рачен преглед';
            } elseif (($tool['manual_status'] ?? 'pending') === 'pending' && $state === 'redirect_review') {
                $manual = 'Потребна е корекција на линкот пред одобрување';
            }
            $state_labels = array('available'=>'Достапна', 'changed_review'=>'Содржината е променета — бара преглед', 'redirect_review'=>'Пренасочена — бара преглед', 'probe_denied'=>'Серверската проверка е одбиена — не е потврдено дека линкот е скршен', 'unavailable'=>'Недостапна', 'uncheckable'=>'Не може да се провери', 'not_checked'=>'Не е проверена');
            $status_label = $state_labels[$state] ?? 'Не може да се провери';
            if ($state === 'available' && !self::audit_is_fresh($tool)) { $status_label = 'Проверка застарена — скриена од наставници'; }
            echo '<tr><td><strong>' . esc_html((string) ($tool['name'] ?? '')) . '</strong><br><a href="' . esc_url(self::safe_url($tool['url'] ?? '')) . '" target="_blank" rel="noopener noreferrer nofollow">Отвори линк</a>';
            if (!empty($tool['privacy_url'])) { echo '<br><a href="' . esc_url(self::safe_url($tool['privacy_url'])) . '" target="_blank" rel="noopener noreferrer nofollow">Приватност</a>'; }
            if (!empty($tool['terms_url'])) { echo ' · <a href="' . esc_url(self::safe_url($tool['terms_url'])) . '" target="_blank" rel="noopener noreferrer nofollow">Услови</a>'; }
            echo '</td><td>' . esc_html(self::category_label($tool['category'] ?? '')) . '</td><td>' . esc_html($status_label) . ' · HTTP ' . (int) ($tool['http_status'] ?? 0) . '<br><small>Последна автоматска проверка: ' . esc_html((string) ($tool['auto_checked_at'] ?? '')) . '</small>';
            if (!empty($tool['redirect_host'])) { echo '<br><small>Цел на пренасочување: ' . esc_html($tool['redirect_host']) . '</small>'; }
            $review_labels = array('privacy'=>'Приватност','terms'=>'Услови','free'=>'Бесплатен план','format'=>'Формат/должина','quality'=>'Квалитет','processing'=>'Обработка','rights'=>'Права','youtube_excluded'=>'Исклучен YouTube downloader');
            $reviewed = array();
            foreach ((array) ($tool['manual_reviewed_checks'] ?? array()) as $review_key) { if (isset($review_labels[$review_key])) { $reviewed[] = $review_labels[$review_key]; } }
            $reviewer_id = absint($tool['manual_reviewed_by'] ?? 0);
            $reviewer = $reviewer_id ? get_userdata($reviewer_id) : false;
            $reviewer_label = $reviewer_id ? ($reviewer ? $reviewer->display_name : 'Недостапен корисник') . ' (#' . $reviewer_id . ')' : 'Нема евидентиран рецензент';
            echo '</td><td><strong>' . esc_html($manual) . '</strong><br><small>Последна рачна проверка: ' . esc_html((string) ($tool['manual_checked_at'] ?? '')) . '</small><br><small>Рецензент: ' . esc_html($reviewer_label) . '</small><br><small>Потврдени ставки: ' . esc_html(implode(', ', $reviewed)) . '</small><br><small>' . esc_html((string) ($tool['manual_review_note'] ?? '')) . '</small></td>';
            echo '<td><strong>Бесплатно:</strong> ' . esc_html((string) ($tool['free_limits'] ?? '')) . '<br><strong>Формат/должина:</strong> ' . esc_html((string) ($tool['duration_format_limits'] ?? '')) . '<br><strong>Квалитет:</strong> ' . esc_html((string) ($tool['quality_notes'] ?? '')) . '<br><strong>Обработка според сервисот:</strong> ' . esc_html((string) ($tool['processing_claim'] ?? '')) . ' <em>(изјава на сервисот, не независна потврда)</em><br><strong>Права:</strong> ' . esc_html((string) ($tool['rights_note'] ?? '')) . '</td><td>';
            if (($tool['manual_status'] ?? '') !== 'archived') {
                echo '<a class="button" href="' . esc_url(add_query_arg(array('page'=>self::PAGE,'edit'=>$id), admin_url('admin.php'))) . '">Уреди</a> ';
                self::action_form('zmp_audio_tool_audit', 'zmp_audio_tool_audit_' . $id, $id, 'Провери URL');
                $uncheckable_without_response = $state === 'uncheckable' && (int) ($tool['http_status'] ?? 0) === 0;
                if ((in_array($state, array('available','changed_review','probe_denied'), true) || $uncheckable_without_response) && self::audit_is_fresh($tool) && ($tool['manual_status'] ?? '') !== 'approved') { self::manual_review_form($id); }
                if (($tool['manual_status'] ?? '') !== 'rejected') { self::rejection_form($id, ($tool['manual_status'] ?? '') === 'approved'); }
                self::archive_form($id);
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div><p>Кај наставниците се прикажуваат само рачно одобрени алатки со свежа автоматска проверка или целосен рачен преглед по HTTP 401/403 или неуспешна серверска проверка со HTTP 0. Овие резултати не се автоматски успех и бараат лична проверка во прелистувач. HTTP 404, други недостапни резултати и пренасочувања не се квалификуваат за рачно одобрување.</p></div>';
    }

    private static function input_row($label, $name, $value, $required = false, $type = 'text') {
        $id = 'zmp-audio-' . sanitize_html_class($name);
        $required_marker = $required ? ' <span class="zmp-required" aria-hidden="true">*</span>' : '';
        $required_attribute = $required ? ' required aria-required="true"' : '';
        echo '<tr><th><label for="' . esc_attr($id) . '">' . esc_html($label) . $required_marker . '</label></th><td>';
        if (in_array($name, array('free_limits','duration_format_limits','processing_claim','quality_notes','rights_note'), true)) {
            echo '<textarea class="large-text" rows="2" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"' . $required_attribute . '>' . esc_textarea((string) $value) . '</textarea>';
        } else {
            echo '<input class="regular-text" type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . $required_attribute . '>';
        }
        echo '</td></tr>';
    }
}
