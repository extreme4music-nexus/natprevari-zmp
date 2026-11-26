<?php
if (!defined('ABSPATH')) {
    exit;
}

/** Competition years, categories, grade groups, and phase policy configuration. */
class ZMP_Competition_Structure {
    const PAGE = 'zmp-competition-structure';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'handle_admin_actions'));
    }

    /** Install additive tables and idempotently seed the standard categories. */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        dbDelta("CREATE TABLE {$prefix}zmp_seasons (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            academic_year VARCHAR(9) NOT NULL,
            title VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'draft' NOT NULL,
            legacy_competition_id INT DEFAULT 0 NOT NULL,
            created_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY academic_year (academic_year),
            KEY status (status)
        ) $collate;");
        dbDelta("CREATE TABLE {$prefix}zmp_categories (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            category_code VARCHAR(50) NOT NULL,
            name_mk VARCHAR(150) NOT NULL,
            name_sq VARCHAR(150) DEFAULT '' NOT NULL,
            participant_type VARCHAR(20) DEFAULT 'individual' NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY category_code (category_code)
        ) $collate;");
        dbDelta("CREATE TABLE {$prefix}zmp_category_groups (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            group_code VARCHAR(60) NOT NULL,
            name_mk VARCHAR(150) NOT NULL,
            education_level VARCHAR(20) NOT NULL,
            grade_start SMALLINT NOT NULL,
            grade_end SMALLINT NOT NULL,
            school_track VARCHAR(30) DEFAULT '' NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY category_group (category_id,group_code),
            KEY category_active (category_id,is_active)
        ) $collate;");
        dbDelta("CREATE TABLE {$prefix}zmp_season_categories (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            is_enabled TINYINT(1) DEFAULT 0 NOT NULL,
            municipal_min_entries INT DEFAULT 5 NOT NULL,
            delivery_mode VARCHAR(20) DEFAULT 'hybrid' NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category (season_id,category_id),
            KEY season_id (season_id)
        ) $collate;");
        dbDelta("CREATE TABLE {$prefix}zmp_season_stages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            is_enabled TINYINT(1) DEFAULT 1 NOT NULL,
            is_required TINYINT(1) DEFAULT 0 NOT NULL,
            min_entries INT DEFAULT 0 NOT NULL,
            delivery_mode VARCHAR(20) DEFAULT 'hybrid' NOT NULL,
            sort_order INT DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY category_phase (season_category_id,phase),
            KEY phase (phase)
        ) $collate;");

        self::seed_categories();
        self::sync_legacy_seasons();
    }

    private static function seed_categories() {
        global $wpdb;
        $categories = array(
            array('knowledge_test', 'Тест на знаење', 'knowledge', 10),
            array('vocal_interpretation', 'Вокална интерпретација', 'individual', 20),
            array('school_choir', 'Училишен хор', 'group', 30),
            array('school_orchestra', 'Училишен оркестар', 'group', 40),
            array('school_bands', 'Училишен бенд', 'group', 50),
        );
        $table = $wpdb->prefix . 'zmp_categories';
        foreach ($categories as $row) {
            $id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE category_code=%s", $row[0]));
            if (!$id) {
                $wpdb->insert($table, array('category_code'=>$row[0], 'name_mk'=>$row[1], 'participant_type'=>$row[2], 'sort_order'=>$row[3]));
                $id = (int)$wpdb->insert_id;
            }
            if ($id) { self::seed_groups($id, $row[0]); }
        }
    }

    private static function seed_groups($category_id, $code) {
        global $wpdb;
        $sets = array(
            'knowledge_test' => array(
                array('primary_6','Основно VI одделение','primary',6,6,''), array('primary_7','Основно VII одделение','primary',7,7,''),
                array('primary_8','Основно VIII одделение','primary',8,8,''), array('primary_9','Основно IX одделение','primary',9,9,''),
                array('secondary_1','Средно I година','secondary',1,1,''), array('secondary_2','Средно II година','secondary',2,2,''),
            ),
            'vocal_interpretation' => array(
                array('primary_6','Основно VI одделение','primary',6,6,''), array('primary_7','Основно VII одделение','primary',7,7,''),
                array('primary_8','Основно VIII одделение','primary',8,8,''), array('primary_9','Основно IX одделение','primary',9,9,''),
                array('secondary_gymnasium_1_2','Средно гимназиско I–II година','secondary',1,2,'gymnasium'),
                array('secondary_gymnasium_3_4','Средно гимназиско III–IV година','secondary',3,4,'gymnasium'),
                array('secondary_vocational_1_2','Средно стручно I–II година','secondary',1,2,'vocational'),
                array('secondary_vocational_3_4','Средно стручно III–IV година','secondary',3,4,'vocational'),
            ),
            'school_choir' => array(
                array('primary_1_5','Основно I–V одделение','primary',1,5,''), array('primary_6_9','Основно VI–IX одделение','primary',6,9,''),
                array('secondary_1_4','Средно I–IV година','secondary',1,4,''),
            ),
            'school_orchestra' => array(
                array('primary_1_5','Основно I–V одделение','primary',1,5,''), array('primary_6_9','Основно VI–IX одделение','primary',6,9,''),
                array('secondary_1_4','Средно I–IV година','secondary',1,4,''),
            ),
            'school_bands' => array(
                array('primary_6_9','Основно VI–IX одделение','primary',6,9,''), array('secondary_1_4','Средно I–IV година','secondary',1,4,''),
            ),
        );
        if (empty($sets[$code])) { return; }
        $table = $wpdb->prefix . 'zmp_category_groups';
        foreach ($sets[$code] as $i => $group) {
            $exists = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE category_id=%d AND group_code=%s", $category_id, $group[0]));
            $data = array('name_mk'=>$group[1], 'education_level'=>$group[2], 'grade_start'=>$group[3], 'grade_end'=>$group[4], 'school_track'=>$group[5], 'is_active'=>1, 'sort_order'=>$i + 1);
            if (!$exists) {
                $wpdb->insert($table, array_merge(array('category_id'=>$category_id, 'group_code'=>$group[0]), $data));
            }
        }
        $obsolete_codes = array(
            'knowledge_test'=>array('primary_6_9'),
            'vocal_interpretation'=>array('primary_6_9','secondary_gymnasium_1_4','secondary_vocational_1_4'),
            'school_choir'=>array('primary_1_4'),
            'school_bands'=>array('primary_1_9'),
        );
        foreach ($obsolete_codes[$code] ?? array() as $obsolete_code) {
            $wpdb->update($table, array('is_active'=>0), array('category_id'=>$category_id, 'group_code'=>$obsolete_code));
        }
    }

    /** Keep existing knowledge-test seasons addressable without changing legacy rows. */
    private static function sync_legacy_seasons() {
        global $wpdb;
        $competitions = $wpdb->get_results("SELECT id,title,academic_year,is_active,workflow_status FROM {$wpdb->prefix}zmp_competitions ORDER BY academic_year,id");
        if (!$competitions) { return; }
        $season_table = $wpdb->prefix . 'zmp_seasons';
        $category_table = $wpdb->prefix . 'zmp_categories';
        $mapping_table = $wpdb->prefix . 'zmp_season_categories';
        $knowledge_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $category_table WHERE category_code=%s", 'knowledge_test'));
        foreach ($competitions as $competition) {
            $season_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $season_table WHERE academic_year=%s", $competition->academic_year));
            if (!$season_id) {
                $wpdb->insert($season_table, array(
                    'academic_year'=>$competition->academic_year,
                    'title'=>$competition->title,
                    'status'=>$competition->workflow_status === 'completed' ? 'completed' : ($competition->is_active ? 'active' : 'draft'),
                    'legacy_competition_id'=>(int)$competition->id,
                ));
                $season_id = (int)$wpdb->insert_id;
            } else {
                $wpdb->update($season_table, array('legacy_competition_id'=>(int)$competition->id), array('id'=>$season_id));
            }
            if (!$season_id || !$knowledge_id) { continue; }
            $mapping_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $mapping_table WHERE season_id=%d AND category_id=%d", $season_id, $knowledge_id));
            if (!$mapping_id) {
                $wpdb->insert($mapping_table, array('season_id'=>$season_id, 'category_id'=>$knowledge_id, 'is_enabled'=>1));
                $mapping_id = (int)$wpdb->insert_id;
            }
            self::seed_stages($mapping_id, 'knowledge_test');
            $categories = $wpdb->get_results("SELECT id,category_code FROM $category_table WHERE category_code<> 'knowledge_test'");
            foreach ($categories as $category) {
                $other_mapping = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $mapping_table WHERE season_id=%d AND category_id=%d", $season_id, $category->id));
                if (!$other_mapping) {
                    $wpdb->insert($mapping_table, array('season_id'=>$season_id, 'category_id'=>(int)$category->id, 'is_enabled'=>0));
                    $other_mapping = (int)$wpdb->insert_id;
                }
                if ($other_mapping) { self::seed_stages($other_mapping, $category->category_code); }
            }
        }
    }

    private static function seed_stages($mapping_id, $category_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_season_stages';
        $knowledge = $category_code === 'knowledge_test';
        $stages = array(
            array('school', $knowledge, $knowledge, 1, 'hybrid'),
            array('municipal', true, $knowledge, 2, 'hybrid'),
            array('regional', true, true, 3, 'hybrid'),
            array('state', true, true, 4, 'physical'),
        );
        foreach ($stages as $stage) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE season_category_id=%d AND phase=%s", $mapping_id, $stage[0]));
            if (!$exists) {
                $wpdb->insert($table, array(
                    'season_category_id'=>$mapping_id, 'phase'=>$stage[0], 'is_enabled'=>(int)$stage[1],
                    'is_required'=>(int)$stage[2], 'min_entries'=>$stage[0] === 'municipal' && !$knowledge ? 5 : 0,
                    'delivery_mode'=>$stage[4], 'sort_order'=>$stage[3],
                ));
            }
        }
    }

    public static function register_menu() {
        add_submenu_page('zmp-competitions', 'Години и категории', 'Години и категории', 'manage_options', self::PAGE, array(__CLASS__, 'render_page'));
    }

    public static function handle_admin_actions() {
        if (!is_admin() || !current_user_can('manage_options') || empty($_POST['zmp_structure_action'])) { return; }
        check_admin_referer('zmp_competition_structure');
        global $wpdb;
        $action = sanitize_key(wp_unslash($_POST['zmp_structure_action']));
        $season_table = $wpdb->prefix . 'zmp_seasons';
        if ($action === 'create_season') {
            $year = self::posted_text('academic_year');
            $title = self::posted_text('title');
            if (!preg_match('/^\d{4}\/\d{4}$/', $year) || (int)substr($year, 5, 4) !== (int)substr($year, 0, 4) + 1 || $title === '') {
                self::redirect('invalid_season');
            }
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $season_table WHERE academic_year=%s", $year));
            if ($exists) { self::redirect('season_exists'); }
            if (!$wpdb->insert($season_table, array('academic_year'=>$year, 'title'=>$title, 'status'=>'draft', 'created_by'=>get_current_user_id()))) {
                self::redirect('save_error');
            }
            $season_id = (int)$wpdb->insert_id;
            $categories = $wpdb->get_results("SELECT id,category_code FROM {$wpdb->prefix}zmp_categories ORDER BY sort_order");
            foreach ($categories as $category) {
                $enabled = 0;
                if ($wpdb->insert($wpdb->prefix . 'zmp_season_categories', array('season_id'=>$season_id, 'category_id'=>(int)$category->id, 'is_enabled'=>$enabled))) {
                    self::seed_stages((int)$wpdb->insert_id, $category->category_code);
                }
            }
            self::redirect('season_created');
        }
        if ($action === 'save_categories') {
            $season_id = self::posted_uint('season_id');
            $season = $wpdb->get_row($wpdb->prepare("SELECT * FROM $season_table WHERE id=%d", $season_id));
            if (!$season || $season->status === 'completed') { self::redirect('invalid_season'); }
            $categories = $wpdb->get_results("SELECT id,category_code FROM {$wpdb->prefix}zmp_categories");
            $posted_categories = isset($_POST['categories']) && is_array($_POST['categories']) ? wp_unslash($_POST['categories']) : array();
            $posted_minimums = isset($_POST['municipal_min_entries']) && is_array($_POST['municipal_min_entries']) ? wp_unslash($_POST['municipal_min_entries']) : array();
            $posted_delivery = isset($_POST['delivery_mode']) && is_array($_POST['delivery_mode']) ? wp_unslash($_POST['delivery_mode']) : array();
            $posted_stages = isset($_POST['stages']) && is_array($_POST['stages']) ? wp_unslash($_POST['stages']) : array();
            foreach ($categories as $category) {
                $mapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d", $season_id, $category->id));
                $enabled = !empty($posted_categories[$category->id]) ? 1 : 0;
                $min_value = $posted_minimums[$category->id] ?? 5;
                $min_entries = is_scalar($min_value) ? absint($min_value) : 5;
                $min_entries = max(1, min(10000, $min_entries));
                $delivery_value = $posted_delivery[$category->id] ?? 'hybrid';
                $delivery = is_scalar($delivery_value) ? sanitize_key($delivery_value) : 'hybrid';
                if (!in_array($delivery, array('physical','online','hybrid'), true)) { $delivery = 'hybrid'; }
                if ($mapping) {
                    $wpdb->update($wpdb->prefix . 'zmp_season_categories', array('is_enabled'=>$enabled,'municipal_min_entries'=>$min_entries,'delivery_mode'=>$delivery), array('id'=>$mapping->id));
                }
                if (!$mapping) { continue; }
                $stage_settings = $posted_stages[$category->id] ?? array();
                if (!is_array($stage_settings)) { $stage_settings = array(); }
                $stages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_season_stages WHERE season_category_id=%d", $mapping->id));
                foreach ($stages as $stage) {
                    $stage_data = isset($stage_settings[$stage->phase]) && is_array($stage_settings[$stage->phase]) ? $stage_settings[$stage->phase] : array();
                    $stage_data = wp_unslash($stage_data);
                    $is_enabled = (int)$stage->is_enabled;
                    $is_required = (int)$stage->is_required;
                    $min = $stage->phase === 'municipal' ? $min_entries : 0;
                    $stage_delivery_value = $stage_data['delivery'] ?? $delivery;
                    $stage_delivery = $stage->phase === 'state' ? 'physical' : (is_scalar($stage_delivery_value) ? sanitize_key($stage_delivery_value) : 'hybrid');
                    if (!in_array($stage_delivery, array('physical','online','hybrid'), true)) { $stage_delivery = 'hybrid'; }
                    if ($category->category_code === 'knowledge_test') {
                        $is_enabled = 1;
                        $is_required = 1;
                    } elseif ($stage->phase === 'school') {
                        $is_enabled = 0;
                        $is_required = 0;
                    } elseif (in_array($stage->phase, array('regional','state'), true)) {
                        $is_enabled = 1;
                        $is_required = 1;
                    }
                    $wpdb->update($wpdb->prefix . 'zmp_season_stages', array('is_enabled'=>$is_enabled,'is_required'=>$is_required,'min_entries'=>$min,'delivery_mode'=>$stage_delivery), array('id'=>$stage->id));
                }
            }
            self::redirect('settings_saved', $season_id);
        }
        if ($action === 'activate_season') {
            $season_id = self::posted_uint('season_id');
            $season = $wpdb->get_row($wpdb->prepare("SELECT * FROM $season_table WHERE id=%d", $season_id));
            if (!$season || $season->status === 'completed') { self::redirect('invalid_season'); }
            $enabled_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND is_enabled=1", $season_id));
            if ($enabled_count < 1) { self::redirect('no_categories', $season_id); }
            $competition_table = $wpdb->prefix . 'zmp_competitions';
            $wpdb->query('START TRANSACTION');
            if (!$season->legacy_competition_id) {
                if ($wpdb->update($competition_table, array('is_active'=>0), array('is_active'=>1)) === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
                $inserted = $wpdb->insert($competition_table, array('title'=>$season->title,'academic_year'=>$season->academic_year,'current_phase'=>'school','duration_minutes'=>45,'is_active'=>1,'workflow_status'=>'active'));
                if (!$inserted) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
                $legacy_id = (int)$wpdb->insert_id;
                if ($wpdb->update($season_table, array('legacy_competition_id'=>$legacy_id), array('id'=>$season_id)) === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
                foreach (array('school','municipal','regional','state') as $phase) {
                    if (!$wpdb->insert($wpdb->prefix . 'zmp_phase_status', array('competition_id'=>$legacy_id,'phase'=>$phase,'status'=>'open'))) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
                }
            } else {
                if ($wpdb->update($competition_table, array('is_active'=>0), array('is_active'=>1)) === false || $wpdb->update($competition_table, array('is_active'=>1), array('id'=>$season->legacy_competition_id)) === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
            }
            if ($wpdb->update($season_table, array('status'=>'draft'), array('status'=>'active')) === false || $wpdb->update($season_table, array('status'=>'active'), array('id'=>$season_id)) === false) { $wpdb->query('ROLLBACK'); self::redirect('save_error'); }
            $wpdb->query('COMMIT');
            self::redirect('season_activated', $season_id);
        }
        if ($action === 'save_group') {
            $id = self::posted_uint('group_id');
            $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_groups WHERE id=%d", $id));
            if (!$group) { self::redirect('invalid_group'); }
            $name = self::posted_text('name_mk');
            $start = self::posted_uint('grade_start');
            $end = self::posted_uint('grade_end');
            $level = sanitize_key(self::posted_text('education_level'));
            $track = sanitize_key(self::posted_text('school_track'));
            if ($name === '' || $start < 1 || $end < $start || $end > 13 || !in_array($level, array('primary','secondary'), true) || !in_array($track, array('','gymnasium','vocational'), true)) { self::redirect('invalid_group'); }
            if ($level === 'primary' && $track !== '') { self::redirect('invalid_group'); }
            $wpdb->update($wpdb->prefix . 'zmp_category_groups', array('name_mk'=>$name,'grade_start'=>$start,'grade_end'=>$end,'education_level'=>$level,'school_track'=>$track,'is_active'=>empty($_POST['is_active']) ? 0 : 1), array('id'=>$id));
            self::redirect('group_saved');
        }
    }

    private static function redirect($result, $season_id = 0) {
        $args = array('page'=>self::PAGE, 'zmp_structure_result'=>$result);
        if ($season_id) { $args['season_id'] = absint($season_id); }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private static function posted_text($key) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) { return ''; }
        return sanitize_text_field(wp_unslash((string)$_POST[$key]));
    }

    private static function posted_uint($key) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) { return 0; }
        return absint(wp_unslash((string)$_POST[$key]));
    }

    /** Resolve stage availability for a season/category and current entry count. */
    public static function evaluate_phases($season_id, $category_id, $registration_count = null) {
        global $wpdb;
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d",
            absint($season_id), absint($category_id)
        ));
        if (!$mapping || !(int)$mapping->is_enabled) { return array(); }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}zmp_season_stages WHERE season_category_id=%d ORDER BY sort_order",
            (int)$mapping->id
        ));
        if ($registration_count === null) {
            $registration_count = class_exists('ZMP_Category_Registrations')
                ? ZMP_Category_Registrations::count_for_category($season_id, $category_id)
                : 0;
        }
        $count = is_scalar($registration_count) ? max(0, (int)$registration_count) : 0;
        $resolved = array();
        foreach ($rows as $row) {
            $enabled = (bool)$row->is_enabled;
            if (!$enabled) { continue; }
            $eligible = $enabled && ($row->phase !== 'municipal' || $count >= (int)$row->min_entries);
            $resolved[$row->phase] = array(
                'available'=>$eligible,
                'required'=>(bool)$row->is_required,
                'conditional'=>$row->phase === 'municipal',
                'minimum_entries'=>(int)$row->min_entries,
                'registration_count'=>$count,
                'delivery_mode'=>$row->phase === 'state' ? 'physical' : $row->delivery_mode,
            );
        }
        return $resolved;
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Недоволни дозволи.', 'zmp-competitions')); }
        global $wpdb;
        $seasons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_seasons ORDER BY academic_year DESC");
        $selected_id = isset($_GET['season_id']) && is_scalar($_GET['season_id']) ? absint(wp_unslash((string)$_GET['season_id'])) : 0;
        if (!$selected_id && $seasons) { $selected_id = (int)$seasons[0]->id; }
        $season = $selected_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d", $selected_id)) : null;
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_categories ORDER BY sort_order,id");
        $mappings = $season ? $wpdb->get_results($wpdb->prepare("SELECT sc.*,c.category_code,c.name_mk,c.participant_type FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_categories c ON c.id=sc.category_id WHERE sc.season_id=%d ORDER BY c.sort_order", $season->id)) : array();
        $groups = $wpdb->get_results("SELECT g.*,c.name_mk AS category_name FROM {$wpdb->prefix}zmp_category_groups g JOIN {$wpdb->prefix}zmp_categories c ON c.id=g.category_id ORDER BY c.sort_order,g.sort_order,g.id");
        $messages = array(
            'season_created'=>'Новата година е зачувана како нацрт.', 'season_exists'=>'Оваа учебна година веќе постои.',
            'invalid_season'=>'Податоците за натпреварувачката година не се валидни или годината е затворена.', 'no_categories'=>'Вклучи барем една одобрена категорија пред да ја активираш годината.',
            'settings_saved'=>'Поставките за категориите и фазите се зачувани.', 'season_activated'=>'Натпреварувачката година е активирана.',
            'group_saved'=>'Групата е ажурирана.', 'invalid_group'=>'Податоците за групата не се валидни.', 'save_error'=>'Зачувувањето не успеа.',
        );
        $result_value = isset($_GET['zmp_structure_result']) && is_scalar($_GET['zmp_structure_result']) ? wp_unslash((string)$_GET['zmp_structure_result']) : '';
        $result = sanitize_key($result_value);
        ?>
        <div class="wrap"><h1>Натпреварувачки години и категории</h1>
        <?php if (isset($messages[$result])): ?><div class="notice <?php echo in_array($result,array('invalid_season','invalid_group','season_exists','save_error'),true) ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html($messages[$result]); ?></p></div><?php endif; ?>
        <p>Секоја година започнува како нацрт без вклучени категории. Избери ги категориите што Управниот одбор одобрил за оваа година; тестот на знаење е опционален. Постарите пријави и групи се задржуваат за историјата.</p>
        <hr><h2>Нова натпреварувачка година</h2>
        <form method="post" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap"><?php wp_nonce_field('zmp_competition_structure'); ?><input type="hidden" name="zmp_structure_action" value="create_season"><label>Учебна година<br><input name="academic_year" placeholder="2026/2027" pattern="[0-9]{4}/[0-9]{4}" required></label><label>Назив<br><input name="title" class="regular-text" required></label><button class="button button-primary">Креирај нацрт</button></form>
        <hr><h2>Поставки по година</h2>
        <?php if (!$seasons): ?><p>Нема внесени натпреварувачки години.</p><?php else: ?>
        <form method="get" style="margin-bottom:12px"><input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE); ?>"><label>Избери година <select name="season_id" onchange="this.form.submit()"><?php foreach ($seasons as $item): ?><option value="<?php echo (int)$item->id; ?>" <?php selected($selected_id,(int)$item->id); ?>><?php echo esc_html($item->academic_year . ' — ' . $item->title . ' (' . $item->status . ')'); ?></option><?php endforeach; ?></select></label></form>
        <?php if ($season): ?>
        <form method="post"><?php wp_nonce_field('zmp_competition_structure'); ?><input type="hidden" name="zmp_structure_action" value="save_categories"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>">
        <table class="widefat striped"><thead><tr><th>Категорија</th><th>Вклучена</th><th>Одржување</th><th>Минимум пријави за општинска</th><th>Фази</th></tr></thead><tbody>
        <?php foreach ($mappings as $mapping): $stages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_season_stages WHERE season_category_id=%d ORDER BY sort_order", $mapping->id)); ?>
        <tr><td><strong><?php echo esc_html($mapping->name_mk); ?></strong><br><small><?php echo esc_html($mapping->category_code); ?></small></td><td><label><input type="checkbox" name="categories[<?php echo (int)$mapping->category_id; ?>]" value="1" <?php checked((int)$mapping->is_enabled,1); ?> ><?php echo $mapping->category_code==='knowledge_test' ? 'Опционална — вклучи само со одобрение' : 'Вклучи за оваа година'; ?></label></td><td><select name="delivery_mode[<?php echo (int)$mapping->category_id; ?>]"><option value="hybrid" <?php selected($mapping->delivery_mode,'hybrid'); ?>>Физички или онлајн</option><option value="physical" <?php selected($mapping->delivery_mode,'physical'); ?>>Физички</option><option value="online" <?php selected($mapping->delivery_mode,'online'); ?>>Онлајн</option></select></td><td><input type="number" min="1" max="10000" name="municipal_min_entries[<?php echo (int)$mapping->category_id; ?>]" value="<?php echo (int)$mapping->municipal_min_entries; ?>"></td><td><ul style="margin:0"><?php foreach ($stages as $stage): ?><li><?php echo esc_html(array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво')[$stage->phase] ?? $stage->phase); ?>: <?php echo !$stage->is_enabled ? 'не се одржува' : ($stage->is_required ? 'задолжителна' : ($stage->phase==='municipal' ? 'ако има доволно пријави' : 'по избор')); ?><?php if ($stage->phase==='state'): ?> — секогаш физички<?php else: ?> <select aria-label="Начин на одржување" name="stages[<?php echo (int)$mapping->category_id; ?>][<?php echo esc_attr($stage->phase); ?>][delivery]"><option value="hybrid" <?php selected($stage->delivery_mode,'hybrid'); ?>>Физички или онлајн</option><option value="physical" <?php selected($stage->delivery_mode,'physical'); ?>>Физички</option><option value="online" <?php selected($stage->delivery_mode,'online'); ?>>Онлајн</option></select><?php endif; ?></li><?php endforeach; ?></ul></td></tr>
        <?php endforeach; ?></tbody></table><p><button class="button button-primary" <?php disabled($season->status==='completed'); ?>>Зачувај поставки</button></p></form>
        <?php if ($season->status!=='active' && $season->status!=='completed'): ?><form method="post" onsubmit="return confirm('Да се активира оваа година? Тоа ќе ја замени активната година во постојниот тест на знаење.');"><?php wp_nonce_field('zmp_competition_structure'); ?><input type="hidden" name="zmp_structure_action" value="activate_season"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>"><button class="button" name="activate" value="1">Активирај година</button></form><?php endif; ?>
        <?php endif; endif; ?>
        <hr><h2>Групи по одделение / година</h2><p>Опсезите се броеви на одделение за основно и година за средно. Групите се споделени низ натпреварувачките години.</p>
        <table class="widefat striped"><thead><tr><th>Категорија</th><th>Група</th><th>Ниво</th><th>Опсег</th><th>Насока</th><th>Статус</th><th>Уреди</th></tr></thead><tbody>
        <?php foreach ($groups as $group): ?><tr><td><?php echo esc_html($group->category_name); ?></td><td><?php echo esc_html($group->name_mk); ?></td><td><?php echo esc_html($group->education_level==='primary'?'Основно':'Средно'); ?></td><td><?php echo (int)$group->grade_start; ?>–<?php echo (int)$group->grade_end; ?></td><td><?php echo esc_html($group->school_track==='gymnasium'?'Гимназиско':($group->school_track==='vocational'?'Стручно':'—')); ?></td><td><?php echo $group->is_active?'Активна':'Неактивна'; ?></td><td><details><summary>Уреди</summary><form method="post"><?php wp_nonce_field('zmp_competition_structure'); ?><input type="hidden" name="zmp_structure_action" value="save_group"><input type="hidden" name="group_id" value="<?php echo (int)$group->id; ?>"><p><input name="name_mk" value="<?php echo esc_attr($group->name_mk); ?>" required></p><p><select name="education_level"><option value="primary" <?php selected($group->education_level,'primary'); ?>>Основно</option><option value="secondary" <?php selected($group->education_level,'secondary'); ?>>Средно</option></select> <input type="number" min="1" max="13" name="grade_start" value="<?php echo (int)$group->grade_start; ?>" required> – <input type="number" min="1" max="13" name="grade_end" value="<?php echo (int)$group->grade_end; ?>" required></p><p><select name="school_track"><option value="" <?php selected($group->school_track,''); ?>>Општо</option><option value="gymnasium" <?php selected($group->school_track,'gymnasium'); ?>>Гимназиско</option><option value="vocational" <?php selected($group->school_track,'vocational'); ?>>Стручно</option></select> <label><input type="checkbox" name="is_active" value="1" <?php checked((int)$group->is_active,1); ?>> Активна</label></p><button class="button">Зачувај група</button></form></details></td></tr><?php endforeach; ?>
        <?php if (!$groups): ?><tr><td colspan="7">Нема внесени групи.</td></tr><?php endif; ?></tbody></table></div>
        <?php
    }
}
