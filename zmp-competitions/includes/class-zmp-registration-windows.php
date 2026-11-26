<?php
if (!defined('ABSPATH')) { exit; }

/** Moderator-configured registration windows, enforced at the write boundary. */
class ZMP_Registration_Windows {
    const NONCE = 'zmp_registration_windows';

    // Run after the plugin's init-time migration bridge (priority 10).
    public static function init() { add_action('init', array(__CLASS__, 'handle_post'), 20); }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_registration_windows (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            opens_at DATETIME DEFAULT NULL,
            closes_at DATETIME DEFAULT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category (season_id,category_id),
            KEY closes_at (closes_at)
        ) {$wpdb->get_charset_collate()};");
    }

    public static function handle_post() {
        if (empty($_POST['zmp_registration_window_action'])) { return; }
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) {
            wp_die('Немате дозвола за управување со рокови.', 'Нема пристап', array('response'=>403));
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), self::NONCE)) {
            wp_die('Безбедносната проверка не помина.', 'Неважечка проверка', array('response'=>403));
        }
        global $wpdb;
        $season_id = absint($_POST['season_id'] ?? 0);
        $category_id = absint($_POST['category_id'] ?? 0);
        $season = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE id=%d AND status<>'completed'", $season_id));
        $category = $wpdb->get_row($wpdb->prepare("SELECT c.id FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 AND c.id=%d", $season_id, $category_id));
        if (!$season || !$category) { self::redirect('invalid_scope'); }
        $opens = self::parse_datetime($_POST['opens_at'] ?? '');
        $closes = self::parse_datetime($_POST['closes_at'] ?? '');
        if ($opens === false || $closes === false || ($opens && $closes && strtotime($closes) <= strtotime($opens))) { self::redirect('invalid_time'); }
        // Build NULL values into SQL instead of passing null through a %s placeholder;
        // wpdb can otherwise store an empty string where the window must be unbounded.
        $open_sql = $opens === '' ? 'NULL' : '%s';
        $close_sql = $closes === '' ? 'NULL' : '%s';
        $query = "INSERT INTO {$wpdb->prefix}zmp_registration_windows (season_id,category_id,opens_at,closes_at,updated_by,updated_at) VALUES (%d,%d,{$open_sql},{$close_sql},%d,%s) ON DUPLICATE KEY UPDATE opens_at=VALUES(opens_at),closes_at=VALUES(closes_at),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)";
        $args = array($season_id, $category_id);
        if ($opens !== '') { $args[] = $opens; }
        if ($closes !== '') { $args[] = $closes; }
        $args[] = get_current_user_id();
        $args[] = current_time('mysql');
        $ok = $wpdb->query($wpdb->prepare($query, $args));
        if ($ok === false) { self::redirect('save_error'); }
        self::redirect('saved');
    }

    /** Existing seasons remain open until a moderator explicitly configures a window. */
    public static function allows($season_id, $category_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT opens_at,closes_at FROM {$wpdb->prefix}zmp_registration_windows WHERE season_id=%d AND category_id=%d", absint($season_id), absint($category_id)));
        if (!$row) { return true; }
        $now = current_time('timestamp', true);
        $opening = ($row->opens_at && $row->opens_at !== '0000-00-00 00:00:00') ? strtotime($row->opens_at . ' UTC') : false;
        $closing = ($row->closes_at && $row->closes_at !== '0000-00-00 00:00:00') ? strtotime($row->closes_at . ' UTC') : false;
        if ($opening !== false && $opening > $now) { return false; }
        if ($closing !== false && $closing <= $now) { return false; }
        return true;
    }

    public static function render_moderator_panel() {
        if (!is_user_logged_in() || (!current_user_can('zmp_moderator_cap') && !current_user_can('view_zmp_moderator_dashboard') && !current_user_can('manage_options'))) { return ''; }
        global $wpdb;
        $seasons = $wpdb->get_results("SELECT id,academic_year,title FROM {$wpdb->prefix}zmp_seasons WHERE status<>'completed' ORDER BY academic_year DESC");
        if (!$seasons) { return '<section class="zmp-card"><h3>Рокови за пријавување</h3><p>Нема активна натпреварувачка година.</p></section>'; }
        $season_id = absint($_GET['zmp_window_season'] ?? $seasons[0]->id);
        $season = null; foreach ($seasons as $candidate) { if ((int)$candidate->id === $season_id) { $season=$candidate; break; } }
        if (!$season) { $season=$seasons[0]; $season_id=(int)$season->id; }
        $categories = $wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk,w.opens_at,w.closes_at FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 LEFT JOIN {$wpdb->prefix}zmp_registration_windows w ON w.season_id=sc.season_id AND w.category_id=c.id ORDER BY c.sort_order,c.id", $season_id));
        $status = sanitize_key(wp_unslash($_GET['zmp_window_status'] ?? ''));
        $messages = array('saved'=>'Рокот е зачуван.','invalid_time'=>'Внесете валидни датуми; затворањето мора да биде по отворањето.','invalid_scope'=>'Избраната година или категорија не е достапна.','save_error'=>'Рокот не можеше да се зачува.');
        ob_start(); ?>
        <section class="zmp-card"><h3>Рокови за пријавување</h3>
        <p>Поставете отворање и затворање по категорија. Датумите се толкуваат според временската зона на WordPress. Празен датум значи дека нема ограничување од таа страна.</p>
        <?php if (isset($messages[$status])): ?><p class="zmp-alert"><?php echo esc_html($messages[$status]); ?></p><?php endif; ?>
        <form method="get" class="zmp-form"><label>Натпреварувачка година <select name="zmp_window_season" onchange="this.form.submit()"><?php foreach ($seasons as $s): ?><option value="<?php echo (int)$s->id; ?>" <?php selected($season_id,(int)$s->id); ?>><?php echo esc_html($s->academic_year.' — '.$s->title); ?></option><?php endforeach; ?></select></label><?php foreach ((array)$_GET as $key=>$value) { if (in_array((string)$key,array('zmp_window_season','zmp_window_status'),true)||!is_scalar($value)) continue; ?><input type="hidden" name="<?php echo esc_attr(sanitize_key($key)); ?>" value="<?php echo esc_attr(sanitize_text_field(wp_unslash((string)$value))); ?>"><?php } ?></form>
        <?php if (!$categories): ?><p>Нема активни категории.</p><?php else: foreach ($categories as $cat): ?><form method="post" class="zmp-form" style="border-top:1px solid #ddd;padding:12px 0"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="zmp_registration_window_action" value="save"><input type="hidden" name="season_id" value="<?php echo $season_id; ?>"><input type="hidden" name="category_id" value="<?php echo (int)$cat->id; ?>"><strong><?php echo esc_html($cat->name_mk); ?></strong><label>Отворање <input type="datetime-local" name="opens_at" value="<?php echo esc_attr(self::format_local($cat->opens_at)); ?>"></label><label>Затворање <input type="datetime-local" name="closes_at" value="<?php echo esc_attr(self::format_local($cat->closes_at)); ?>"></label><button class="button">Зачувај рок</button></form><?php endforeach; endif; ?>
        </section><?php return ob_get_clean();
    }

    private static function parse_datetime($raw) {
        $raw = is_scalar($raw) ? sanitize_text_field(wp_unslash((string)$raw)) : '';
        if ($raw === '') { return ''; }
        $date = DateTime::createFromFormat('!Y-m-d\\TH:i', $raw, wp_timezone());
        $errors = DateTime::getLastErrors();
        if (!$date || ($errors && (!empty($errors['warning_count']) || !empty($errors['error_count'])))) { return false; }
        return get_gmt_from_date($date->format('Y-m-d H:i:s'));
    }
    private static function format_local($gmt) { if (!$gmt || $gmt==='0000-00-00 00:00:00') return ''; $local = get_date_from_gmt($gmt, 'Y-m-d H:i:s'); $date = DateTime::createFromFormat('!Y-m-d H:i:s', $local, wp_timezone()); return $date ? $date->format('Y-m-d\\TH:i') : ''; }
    private static function redirect($status) { $url = wp_get_referer() ?: home_url('/'); wp_safe_redirect(add_query_arg('zmp_window_status', sanitize_key($status), $url)); exit; }
}
