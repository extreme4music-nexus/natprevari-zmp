<?php
/**
 * Plugin Name: ZMP President Document Key Setup
 * Description: One-purpose temporary installer for the ZMP presidential-document encryption key.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ZMP
 */

if (!defined('ABSPATH')) {
    exit;
}

final class ZMP_President_Document_Key_Setup {
    private const KEY_NAME = 'ZMP_PRESIDENT_DOCUMENT_KEY';
    private const PAGE_SLUG = 'zmp-president-document-key-setup';

    public static function boot() {
        add_action('admin_menu', array(__CLASS__, 'add_tools_page'));
        add_action('admin_post_zmp_install_president_document_key', array(__CLASS__, 'install_key'));
    }

    public static function add_tools_page() {
        add_management_page(
            'ЗМП — шифрирање документи',
            'ЗМП — клуч за документи',
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render_page')
        );
    }

    private static function key_is_valid() {
        return defined(self::KEY_NAME)
            && is_string(constant(self::KEY_NAME))
            && preg_match('/^[a-f0-9]{64}$/i', constant(self::KEY_NAME)) === 1;
    }

    private static function encrypted_document_count() {
        global $wpdb;

        $table = $wpdb->prefix . 'zmp_president_documents';
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return new WP_Error('unsafe_table_name');
        }

        $wpdb->last_error = '';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($wpdb->last_error !== '') {
            return new WP_Error('document_table_check_failed');
        }
        if ($found !== $table) {
            return 0;
        }

        $wpdb->last_error = '';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE encryption_version = 1");
        if ($wpdb->last_error !== '' || !is_numeric($count)) {
            return new WP_Error('encrypted_document_check_failed');
        }

        return (int) $count;
    }

    private static function config_path() {
        $candidates = array(
            ABSPATH . 'wp-config.php',
            dirname(untrailingslashit(ABSPATH)) . DIRECTORY_SEPARATOR . 'wp-config.php',
        );

        foreach ($candidates as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }
            if (is_link($candidate)) {
                return new WP_Error('symlink_config');
            }
            $real = realpath($candidate);
            if (!$real || !is_readable($real)) {
                return new WP_Error('config_unreadable');
            }
            return $real;
        }

        return new WP_Error('config_not_found');
    }

    private static function path_is_inside($path, $directory) {
        $path = rtrim(str_replace('\\', '/', $path), '/') . '/';
        $directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
        return strpos($path, $directory) === 0;
    }

    private static function safe_work_directory($config_path) {
        $document_root = realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ABSPATH);
        if (!$document_root) {
            return new WP_Error('document_root_unavailable');
        }

        $config_directory = dirname($config_path);
        $safe_directory = self::path_is_inside($config_path, $document_root)
            ? dirname($document_root)
            : $config_directory;

        $safe_real = realpath($safe_directory);
        $config_dir_real = realpath($config_directory);
        if (!$safe_real || !$config_dir_real || !is_writable($safe_real) || !is_writable($config_dir_real)
            || self::path_is_inside($safe_real, $document_root)) {
            return new WP_Error('safe_directory_unavailable');
        }

        $safe_stat = @stat($safe_real);
        $config_stat = @stat($config_dir_real);
        if (!$safe_stat || !$config_stat || $safe_stat['dev'] !== $config_stat['dev']) {
            return new WP_Error('safe_directory_filesystem_mismatch');
        }

        return $safe_real;
    }

    private static function write_exclusive($path, $contents, $mode) {
        $handle = @fopen($path, 'x+b');
        if (!$handle) {
            return false;
        }

        $length = strlen($contents);
        $offset = 0;
        while ($offset < $length) {
            $written = @fwrite($handle, substr($contents, $offset));
            if ($written === false || $written === 0) {
                fclose($handle);
                @unlink($path);
                return false;
            }
            $offset += $written;
        }

        $flushed = @fflush($handle);
        if (function_exists('fsync')) {
            @fsync($handle);
        }
        fclose($handle);

        if (!$flushed || !@chmod($path, $mode)) {
            @unlink($path);
            return false;
        }

        return is_file($path) && filesize($path) === $length;
    }

    private static function insert_constant($contents, $key) {
        $pattern = <<<'REGEX'
~^[ \t]*require_once[ \t]+ABSPATH[ \t]*\.[ \t]*(['"])wp-settings\.php\1[ \t]*;[ \t]*\r?$~m
REGEX;
        $matches = array();
        $count = preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE);
        if ($count !== 1) {
            return new WP_Error('settings_marker_unrecognized');
        }

        $newline = strpos($contents, "\r\n") !== false ? "\r\n" : "\n";
        $offset = $matches[0][0][1];
        $line = "define( '" . self::KEY_NAME . "', '" . $key . "' );" . $newline;
        $updated = substr($contents, 0, $offset) . $line . substr($contents, $offset);

        try {
            token_get_all($updated, TOKEN_PARSE);
        } catch (ParseError $error) {
            return new WP_Error('config_syntax_check_failed');
        }

        return $updated;
    }

    public static function install_key() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Немате дозвола да ја менувате оваа поставка.', 'zmp-document-key-setup'), '', array('response' => 403));
        }
        check_admin_referer('zmp_install_president_document_key');

        if (defined(self::KEY_NAME)) {
            self::redirect('already_defined');
        }

        $encrypted_count = self::encrypted_document_count();
        if (is_wp_error($encrypted_count)) {
            self::redirect('document_check_failed');
        }
        if ($encrypted_count > 0) {
            self::redirect('existing_encrypted_documents');
        }

        $config_path = self::config_path();
        if (is_wp_error($config_path)) {
            self::redirect($config_path->get_error_code());
        }

        $safe_directory = self::safe_work_directory($config_path);
        if (is_wp_error($safe_directory)) {
            self::redirect($safe_directory->get_error_code());
        }

        $original = @file_get_contents($config_path);
        $original_stat = @stat($config_path);
        if (!is_string($original) || !$original_stat) {
            self::redirect('config_read_failed');
        }

        try {
            $key = bin2hex(random_bytes(32));
        } catch (Throwable $error) {
            self::redirect('random_generation_failed');
        }

        $updated = self::insert_constant($original, $key);
        if (is_wp_error($updated)) {
            self::redirect($updated->get_error_code());
        }

        try {
            $backup_suffix = bin2hex(random_bytes(6));
        } catch (Throwable $error) {
            self::redirect('random_generation_failed');
        }
        $backup = $safe_directory . DIRECTORY_SEPARATOR
            . '.zmp-wp-config-backup-' . gmdate('Ymd-His') . '-' . $backup_suffix . '.php';
        if (!self::write_exclusive($backup, $original, 0600)) {
            self::redirect('backup_failed');
        }

        $temporary = @tempnam($safe_directory, '.zmp-config-');
        if (!$temporary) {
            self::redirect('temporary_file_failed');
        }
        @chmod($temporary, 0600);

        $written = @file_put_contents($temporary, $updated, LOCK_EX);
        if ($written !== strlen($updated) || !@chmod($temporary, $original_stat['mode'] & 0777)) {
            @unlink($temporary);
            self::redirect('temporary_write_failed');
        }

        $temporary_stat = @stat($temporary);
        if (!$temporary_stat) {
            @unlink($temporary);
            self::redirect('temporary_stat_failed');
        }

        if ($temporary_stat['uid'] !== $original_stat['uid'] && (!function_exists('chown') || !@chown($temporary, $original_stat['uid']))) {
            @unlink($temporary);
            self::redirect('file_owner_preservation_failed');
        }
        if ($temporary_stat['gid'] !== $original_stat['gid'] && (!function_exists('chgrp') || !@chgrp($temporary, $original_stat['gid']))) {
            @unlink($temporary);
            self::redirect('file_group_preservation_failed');
        }

        clearstatcache(true, $temporary);
        $final_stat = @stat($temporary);
        if (!$final_stat || $final_stat['uid'] !== $original_stat['uid'] || $final_stat['gid'] !== $original_stat['gid']) {
            @unlink($temporary);
            self::redirect('file_owner_preservation_failed');
        }

        if (!@rename($temporary, $config_path)) {
            @unlink($temporary);
            self::redirect('config_replace_failed');
        }

        self::redirect('installed');
    }

    private static function redirect($status) {
        $allowed = array(
            'already_defined', 'document_check_failed', 'existing_encrypted_documents',
            'config_not_found', 'symlink_config', 'config_unreadable',
            'document_root_unavailable', 'safe_directory_unavailable', 'safe_directory_filesystem_mismatch',
            'config_read_failed', 'random_generation_failed', 'settings_marker_unrecognized',
            'config_syntax_check_failed', 'backup_failed', 'temporary_file_failed',
            'temporary_write_failed', 'temporary_stat_failed', 'file_owner_preservation_failed',
            'file_group_preservation_failed', 'config_replace_failed', 'installed',
        );
        if (!in_array($status, $allowed, true)) {
            $status = 'config_replace_failed';
        }
        wp_safe_redirect(add_query_arg(
            array('page' => self::PAGE_SLUG, 'zmp_key_setup' => $status),
            admin_url('tools.php')
        ));
        exit;
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Немате дозвола да ја отворите оваа страница.', 'zmp-document-key-setup'), '', array('response' => 403));
        }

        $status = isset($_GET['zmp_key_setup']) && is_scalar($_GET['zmp_key_setup'])
            ? sanitize_key(wp_unslash((string) $_GET['zmp_key_setup']))
            : '';
        $messages = array(
            'installed' => array('success', 'Клучот е генериран и додаден во wp-config.php. Не беше прикажан и не е зачуван во базата. Деактивирајте го и избришете го привремениот додаток.'),
            'already_defined' => array('info', 'Константата за клуч веќе постои. Овој алат нема да ја менува.'),
            'existing_encrypted_documents' => array('error', 'Веќе постојат шифрирани претседателски документи. Алатот одби да генерира друг клуч. Вратете го оригиналниот клуч или обратете се кај хостинг-поддршката.'),
            'document_check_failed' => array('error', 'Алатот не можеше безбедно да провери дали има шифрирани документи и не направи промена.'),
            'config_not_found' => array('error', 'wp-config.php не е пронајден на поддржана WordPress локација. Не е направена промена.'),
            'symlink_config' => array('error', 'wp-config.php е симболичка врска. Алатот одби да го менува.'),
            'config_unreadable' => array('error', 'wp-config.php не може безбедно да се прочита. Не е направена промена.'),
            'document_root_unavailable' => array('error', 'Коренот на веб-страницата не може безбедно да се утврди. Не е направена промена.'),
            'safe_directory_unavailable' => array('error', 'Нема достапна заштитена папка надвор од веб-коренот. Побарајте од хостинг-поддршката да го постави клучот.'),
            'safe_directory_filesystem_mismatch' => array('error', 'Не може да се гарантира безбедна замена на фајлот. Не е направена промена.'),
            'config_read_failed' => array('error', 'wp-config.php не може да се прочита. Не е направена промена.'),
            'random_generation_failed' => array('error', 'Не може да се генерира безбеден случаен клуч. Не е направена промена.'),
            'settings_marker_unrecognized' => array('error', 'Стандардната линија за wp-settings.php не е најдена точно еднаш. Не е направена промена.'),
            'config_syntax_check_failed' => array('error', 'Предложениот wp-config.php не ја помина проверката на PHP синтаксата. Не е направена промена.'),
            'backup_failed' => array('error', 'Не може да се создаде заштитна резервна копија надвор од веб-коренот. Не е направена промена.'),
            'temporary_file_failed' => array('error', 'Не може да се создаде заштитен привремен фајл. Не е направена промена.'),
            'temporary_write_failed' => array('error', 'Привремената конфигурација не може безбедно да се запише. Не е направена промена.'),
            'temporary_stat_failed' => array('error', 'Привремениот конфигурациски фајл не може да се провери. Не е направена промена.'),
            'file_owner_preservation_failed' => array('error', 'Не може да се зачува сопственикот на оригиналниот фајл. Не е направена промена.'),
            'file_group_preservation_failed' => array('error', 'Не може да се зачува групата на оригиналниот фајл. Не е направена промена.'),
            'config_replace_failed' => array('error', 'wp-config.php не може безбедно да се замени. Не е направена промена.'),
        );

        echo '<div class="wrap"><h1>ZMP President Document Encryption</h1>';
        echo '<p>Ова е привремен алат што го поставува само клучот за шифрирање на ЗМП документите. Не е општ уредувач на wp-config.php.</p>';
        if (self::key_is_valid()) {
            echo '<div class="notice notice-success"><p>Валиден клуч за шифрирање е поставен. Не генерирајте и не заменувајте клуч.</p></div>';
        } elseif (defined(self::KEY_NAME)) {
            echo '<div class="notice notice-error"><p>Константата е поставена, но не е во потребниот хексадецимален формат од 64 знака. Алатот нема да ја менува; обратете се кај хостинг-поддршката.</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p>Нема валиден клуч. Пред единствената промена, алатот проверува дали има шифрирани документи и создава заштитна резервна копија.</p></div>';
            echo '<p><strong>Генерираниот клуч нема да се прикаже, запише во лог или зачува во WordPress базата.</strong> If any encrypted presidential documents already exist, the helper will refuse to continue.</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="zmp_install_president_document_key">';
            wp_nonce_field('zmp_install_president_document_key');
            submit_button('Генерирај и постави клуч');
            echo '</form>';
        }
        if (isset($messages[$status])) {
            $class = $messages[$status][0] === 'success' ? 'notice-success' : ($messages[$status][0] === 'error' ? 'notice-error' : 'notice-info');
            echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($messages[$status][1]) . '</p></div>';
        }
        echo '<p>По успешно поставување, деактивирајте го и избришете го привремениот алат. Чувајте безбедна офлајн копија од wp-config.php.</p>';
        echo '</div>';
    }
}

ZMP_President_Document_Key_Setup::boot();
