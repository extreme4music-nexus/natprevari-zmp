<?php
if (!defined('ABSPATH')) { exit; }

/** Category- and season-specific jury assignments, scoring, and final decisions. */
class ZMP_Category_Jury {
    /** MySQL advisory lock serializing mutations for one category stage. */
    private static $active_stage_lock = '';
    private static $stage_lock_shutdown_registered = false;
    const PHASES = array('municipal' => 'Општинско натпреварувачко ниво', 'regional' => 'Регионално натпреварувачко ниво', 'state' => 'Државно натпреварувачко ниво');

    /** Convert internal phase keys to safe, consistent user-facing labels. */
    public static function phase_label($phase) {
        $labels = array('school' => 'Училишно натпреварувачко ниво') + self::PHASES;
        $phase = (string) $phase;
        return isset($labels[$phase]) ? $labels[$phase] : 'Натпреварувачко ниво';
    }
    const AWARDS = array('first' => 'Прва награда', 'second' => 'Втора награда', 'third' => 'Трета награда', 'honorable' => 'Пофалница');

    // Run after the plugin's init-time upgrade bridge, so first-request writes on upgrade have tables.
    public static function init() {
        add_action('init', array(__CLASS__, 'handle_post'), 20);
        add_action('admin_post_zmp_jury_print_roster', array(__CLASS__, 'print_physical_roster'));
        if (!self::$stage_lock_shutdown_registered) {
            register_shutdown_function(array(__CLASS__, 'shutdown_release_stage_lock'));
            self::$stage_lock_shutdown_registered = true;
        }
    }

    /** Add auditable tables. Every table carries season_id for safe annual ZIP export. */
    public static function install() {
        global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate();
        $sql=array(
            "CREATE TABLE {$wpdb->prefix}zmp_jury_assignments (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                juror_user_id BIGINT(20) UNSIGNED NOT NULL,
                assigned_by BIGINT(20) UNSIGNED NOT NULL,
                assigned_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY season_category_juror (season_id,category_id,juror_user_id),
                KEY juror (juror_user_id)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_jury_regional_assignments (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                region VARCHAR(30) NOT NULL,
                juror_user_id BIGINT(20) UNSIGNED NOT NULL,
                assigned_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                assigned_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY season_category_region_juror (season_id,category_id,region,juror_user_id),
                KEY juror_scope (season_id,juror_user_id),
                KEY category_region (season_id,category_id,region)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_scoring_criteria (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                criterion_name VARCHAR(190) NOT NULL,
                description TEXT NOT NULL,
                max_points DECIMAL(8,2) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT(20) UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY season_category (season_id,category_id)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_category_awards (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                award_key VARCHAR(20) NOT NULL,
                min_points DECIMAL(8,2) NOT NULL,
                updated_by BIGINT(20) UNSIGNED NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY season_category_award (season_id,category_id,award_key)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_jury_assessments (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                entry_id BIGINT(20) UNSIGNED NOT NULL,
                phase VARCHAR(20) NOT NULL,
                juror_user_id BIGINT(20) UNSIGNED NOT NULL,
                total_points DECIMAL(8,2) NOT NULL DEFAULT 0,
                rationale TEXT NOT NULL,
                private_notes TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'submitted',
                submitted_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY assessment_scope (season_id,category_id,entry_id,phase,juror_user_id),
                KEY entry (entry_id)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_jury_scores (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                assessment_id BIGINT(20) UNSIGNED NOT NULL,
                criterion_id BIGINT(20) UNSIGNED NOT NULL,
                points DECIMAL(8,2) NOT NULL,
                note TEXT NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY assessment_criterion (assessment_id,criterion_id),
                KEY season_id (season_id)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_jury_correction_requests (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                entry_id BIGINT(20) UNSIGNED NOT NULL,
                phase VARCHAR(20) NOT NULL,
                juror_user_id BIGINT(20) UNSIGNED NOT NULL,
                reason TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                decision_note TEXT NOT NULL,
                decided_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                decided_at DATETIME DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY status_created (status,created_at),
                KEY scope (season_id,category_id,entry_id,phase)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_category_final_results (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                entry_id BIGINT(20) UNSIGNED NOT NULL,
                phase VARCHAR(20) NOT NULL,
                jury_average DECIMAL(8,2) NOT NULL,
                final_points DECIMAL(8,2) NOT NULL,
                award_key VARCHAR(20) NOT NULL DEFAULT '',
                qualified_next_stage TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
                decision_note TEXT NOT NULL,
                decided_by BIGINT(20) UNSIGNED NOT NULL,
                decided_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY result_scope (season_id,category_id,entry_id,phase)
            ) $c;",
            "CREATE TABLE {$wpdb->prefix}zmp_jury_audit (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                season_id BIGINT(20) UNSIGNED NOT NULL,
                category_id BIGINT(20) UNSIGNED NOT NULL,
                entry_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                actor_user_id BIGINT(20) UNSIGNED NOT NULL,
                action VARCHAR(40) NOT NULL,
                details LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY scope (season_id,category_id),
                KEY entry (entry_id)
            ) $c;"
        ); foreach($sql as $statement){dbDelta($statement);}
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_category_stage_closures (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            close_note TEXT NOT NULL,
            closed_by BIGINT(20) UNSIGNED NOT NULL,
            closed_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY stage_scope (season_id,category_id,phase),
            KEY closed_at (closed_at)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_jury_discipline (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL,
            reason TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY discipline_scope (season_id,category_id,entry_id,phase),
            KEY scope (season_id,category_id,phase)
        ) $c;");
    }
    private static function can_manage(){return current_user_can('manage_options')||current_user_can('zmp_commission_cap')||current_user_can('view_zmp_commission_dashboard');}
    private static function active_season(){return ZMP_Category_Registrations::active_season();}
    /** A commission user is restricted to their explicit season/category assignment. */
    public static function is_restricted_juror() {
        return is_user_logged_in()
            && (current_user_can('zmp_commission_cap') || current_user_can('view_zmp_commission_dashboard'))
            && !current_user_can('manage_options')
            && !current_user_can('zmp_moderator_cap');
    }

    public static function has_assignment($season_id, $category_id, $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        global $wpdb;
        $wpdb->last_error = '';
        $assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND category_id=%d AND juror_user_id=%d",
            absint($season_id), absint($category_id), absint($user_id)
        ));
        return $wpdb->last_error === '' && (int) $assigned > 0;
    }

    public static function has_any_assignment($season_id, $category_id, $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        if (self::has_assignment($season_id, $category_id, $user_id)) { return true; }
        $regions = self::regional_regions_for_juror($season_id, $category_id, $user_id);
        return is_array($regions) && !empty($regions);
    }

    /** Resolve one explicit competition-level assignment. State means national category membership. */
    public static function has_phase_assignment($season_id, $category_id, $phase, $region = '', $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        $phase = sanitize_key((string) $phase);
        if ($phase === 'state') { return self::has_assignment($season_id, $category_id, $user_id); }
        if ($phase === 'regional') { return self::is_regional_juror_for($season_id, $category_id, $region, $user_id); }
        if ($phase === 'municipal') { return self::is_municipal_juror_for($season_id, $category_id, $region, $user_id); }
        return false;
    }

    /** Preserve the legacy municipal rule: category membership plus the exact region assignment. */
    private static function is_municipal_juror_for($season_id, $category_id, $region, $user_id) {
        if (!self::has_assignment($season_id, $category_id, $user_id) || get_option('zmp_regional_jury_migration_49_2') !== 'complete' || !class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return false; }
        global $wpdb;
        $wpdb->last_error = '';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d AND category_id=%d AND region=%s AND juror_user_id=%d",
            absint($season_id), absint($category_id), sanitize_key($region), absint($user_id)
        ));
        return $wpdb->last_error === '' && (int) $count > 0;
    }

    /** Fail closed for writes unless the member is assigned to the exact entry's level and region. */
    private static function phase_entry_authorized($season_id, $category_id, $entry_id, $phase, $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        if (!self::is_restricted_juror()) { return true; }
        $phase = sanitize_key((string) $phase);
        if ($phase === 'state') {
            if (!self::has_phase_assignment($season_id, $category_id, $phase, '', $user_id)) { return false; }
            return in_array(absint($entry_id), self::state_eligible_entry_ids($season_id, $category_id), true);
        }
        if (!in_array($phase, array('regional', 'municipal'), true)) { return false; }
        global $wpdb;
        $wpdb->last_error = '';
        $region = $wpdb->get_var($wpdb->prepare(
            "SELECT region FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",
            absint($entry_id), absint($season_id), absint($category_id)
        ));
        if ($wpdb->last_error !== '' || !class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return false; }
        return self::has_phase_assignment($season_id, $category_id, $phase, $region, $user_id);
    }

    private static function require_phase_assignment($season_id, $category_id, $entry_id, $phase) {
        if (self::is_restricted_juror() && !self::phase_entry_authorized($season_id, $category_id, $entry_id, $phase, get_current_user_id())) {
            wp_die('Немате назначување за оваа категорија, регион и натпреварувачко ниво.', 'Нема пристап', array('response' => 403));
        }
    }

    /** Public resource-level guard for adjacent components that serve jury data. */
    public static function can_access_entry_phase($season_id,$category_id,$entry_id,$phase,$user_id=0) {
        return self::phase_entry_authorized($season_id,$category_id,$entry_id,$phase,$user_id);
    }

    /** Exact regional jury assignment, independent of whether the category uses video. */
    public static function is_regional_juror_for($season_id, $category_id, $region, $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        if (get_option('zmp_regional_jury_migration_49_2') !== 'complete' || !class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return false; }
        $user = get_userdata(absint($user_id));
        if (!$user || !in_array('zmp_commission', (array) $user->roles, true)) { return false; }
        global $wpdb;
        $wpdb->last_error = '';
        $assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_regional_assignments r\n             WHERE r.season_id=%d AND r.category_id=%d AND r.region=%s AND r.juror_user_id=%d",
            absint($season_id), absint($category_id), sanitize_key($region), absint($user_id)
        ));
        return $wpdb->last_error === '' && (int) $assigned > 0;
    }

    /** Lists only valid regional assignments; false means the authorization lookup failed. */
    private static function regional_regions_for_juror($season_id, $category_id, $user_id) {
        if (get_option('zmp_regional_jury_migration_49_2') !== 'complete') { return false; }
        global $wpdb;
        if (!class_exists('ZMP_Regions')) { return false; }
        $wpdb->last_error = '';
        $regions = $wpdb->get_col($wpdb->prepare(
            "SELECT region FROM {$wpdb->prefix}zmp_jury_regional_assignments WHERE season_id=%d AND category_id=%d AND juror_user_id=%d",
            absint($season_id), absint($category_id), absint($user_id)
        ));
        if ($wpdb->last_error !== '') { return false; }
        $valid = array();
        foreach ((array) $regions as $region) {
            if (ZMP_Regions::valid_region($region)) { $valid[] = sanitize_key($region); }
        }
        return array_values(array_unique($valid));
    }

    private static function regional_entry_authorized($season_id, $category_id, $entry_id, $user_id = 0) {
        if (!$user_id) { $user_id = get_current_user_id(); }
        if ((int) $user_id === get_current_user_id() && !self::is_restricted_juror()) { return true; }
        $target_user = get_userdata(absint($user_id));
        if (!$target_user || !in_array('zmp_commission', (array) $target_user->roles, true)) { return false; }
        global $wpdb;
        $wpdb->last_error = '';
        $region = $wpdb->get_var($wpdb->prepare(
            "SELECT region FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",
            absint($entry_id), absint($season_id), absint($category_id)
        ));
        if ($wpdb->last_error !== '' || !class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return false; }
        return self::is_regional_juror_for($season_id, $category_id, $region, $user_id);
    }

    private static function state_eligible_entry_ids($season_id, $category_id) {
        $eligible = self::stage_eligible_entries($season_id, $category_id, 'state');
        if ($eligible === false) { return array(); }
        return array_map(function($entry) { return (int) $entry->id; }, $eligible);
    }

    /** IDs this commission member may see in participant workspaces. A failed regional lookup
     * deliberately falls back to state-qualified entries only; it never broadens access. */
    public static function visible_entry_ids_for_current_juror($season_id, $category_id) {
        if (!self::is_restricted_juror()) { return null; }
        $season_id = absint($season_id); $category_id = absint($category_id);
        $uid = get_current_user_id();
        $visible = self::has_assignment($season_id, $category_id, $uid) ? self::state_eligible_entry_ids($season_id, $category_id) : array();
        $regions = self::regional_regions_for_juror($season_id, $category_id, $uid);
        if ($regions === false) { return array_values(array_unique($visible)); }
        global $wpdb;
        $wpdb->last_error = '';
        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT id,region FROM {$wpdb->prefix}zmp_category_entries WHERE season_id=%d AND category_id=%d AND status IN ('submitted','approved','complete')",
            $season_id, $category_id
        ));
        if ($wpdb->last_error !== '') { return array_values(array_unique($visible)); }
        foreach ((array) $entries as $entry) {
            if (in_array(sanitize_key((string) $entry->region), $regions, true)) { $visible[] = (int) $entry->id; }
        }
        return array_values(array_unique(array_map('intval', $visible)));
    }

    public static function entry_visible_to_current_juror($season_id, $category_id, $entry_id) {
        if (!self::is_restricted_juror()) { return true; }
        $visible = self::visible_entry_ids_for_current_juror($season_id, $category_id);
        return is_array($visible) && in_array(absint($entry_id), $visible, true);
    }
    private static function phase_juror_count($season_id, $category_id, $phase, $region = '') {
        if (in_array($phase, array('regional','municipal'), true) && get_option('zmp_regional_jury_migration_49_2') !== 'complete') { return 0; }
        global $wpdb;
        $wpdb->last_error = '';
        if ($phase === 'regional') {
            if (!class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return 0; }
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_regional_assignments r
                 JOIN {$wpdb->users} u ON u.ID=r.juror_user_id
                 WHERE r.season_id=%d AND r.category_id=%d AND r.region=%s",
                absint($season_id), absint($category_id), sanitize_key($region)
            ));
        } elseif ($phase === 'municipal') {
            if (!class_exists('ZMP_Regions') || !ZMP_Regions::valid_region($region)) { return 0; }
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_regional_assignments r
                 JOIN {$wpdb->prefix}zmp_jury_assignments c ON c.season_id=r.season_id AND c.category_id=r.category_id AND c.juror_user_id=r.juror_user_id
                 JOIN {$wpdb->users} u ON u.ID=r.juror_user_id
                 WHERE r.season_id=%d AND r.category_id=%d AND r.region=%s",
                absint($season_id), absint($category_id), sanitize_key($region)
            ));
        } else {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id=%d AND category_id=%d",
                absint($season_id), absint($category_id)
            ));
        }
        return $wpdb->last_error === '' ? (int) $count : 0;
    }

    /** Handle all writes through the shortcode with scoped nonce and capability checks. */
    public static function handle_post(){
        $action=isset($_POST['zmp_jury_action'])?sanitize_key(wp_unslash($_POST['zmp_jury_action'])):''; if(!$action)return;
        $moderator_actions=array('add_criterion','edit_criterion','deactivate_criterion','save_awards','assign_juror','unassign_juror','resolve_correction','close_stage');
        $commission_actions=array('submit_assessment','request_correction','finalize_result','disqualify_entry');
        $is_moderator=current_user_can('manage_options')||current_user_can('zmp_moderator_cap');
        $is_commission=current_user_can('manage_options')||current_user_can('zmp_commission_cap')||current_user_can('view_zmp_commission_dashboard');
        $allowed=current_user_can('manage_options')||($is_moderator&&in_array($action,$moderator_actions,true))||($is_commission&&in_array($action,$commission_actions,true));
        if(!is_user_logged_in()||!$allowed)wp_die('Немате дозвола за оваа акција.','Нема пристап',array('response'=>403));
        if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce']??'')),'zmp_category_jury'))wp_die('Безбедносната проверка не помина.','Неважечка проверка',array('response'=>403));
        $season=self::active_season();$category_id=absint($_POST['category_id']??0);
        if(!$season||!$category_id||!self::category_enabled((int)$season->id,$category_id))self::redirect('invalid_scope');
        if(self::is_restricted_juror() && !self::has_any_assignment((int)$season->id,$category_id,get_current_user_id()))wp_die('Немате доделба за оваа категорија и година.','Нема пристап',array('response'=>403));
        global $wpdb;$uid=get_current_user_id();
        if($action==='add_criterion'){
            $name=sanitize_text_field(wp_unslash($_POST['criterion_name']??''));$desc=sanitize_textarea_field(wp_unslash($_POST['criterion_description']??''));$max=self::decimal($_POST['max_points']??'');
            if($name===''||$max===false||$max<=0||$max>1000)self::redirect('invalid_criterion');
            if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$category_id)))self::redirect('criteria_locked');
            $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1",$season->id,$category_id));
            if(!$wpdb->insert($wpdb->prefix.'zmp_scoring_criteria',array('season_id'=>(int)$season->id,'category_id'=>$category_id,'criterion_name'=>$name,'description'=>$desc,'max_points'=>$max,'sort_order'=>$count+1,'created_by'=>$uid,'created_at'=>current_time('mysql'))))self::redirect('save_error');
            self::audit($season->id,$category_id,0,'criterion_added',array('criterion_id'=>$wpdb->insert_id,'name'=>$name,'max_points'=>$max));self::redirect('saved');
        }
        if($action==='edit_criterion'||$action==='deactivate_criterion'){
            if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$category_id)))self::redirect('criteria_locked');
            $criterion_id=absint($_POST['criterion_id']??0);$criterion=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_scoring_criteria WHERE id=%d AND season_id=%d AND category_id=%d AND is_active=1",$criterion_id,$season->id,$category_id));if(!$criterion)self::redirect('invalid_criterion');
            if($action==='deactivate_criterion'){$ok=$wpdb->update($wpdb->prefix.'zmp_scoring_criteria',array('is_active'=>0),array('id'=>$criterion_id));if($ok===false)self::redirect('save_error');self::audit($season->id,$category_id,0,'criterion_deactivated',array('criterion_id'=>$criterion_id,'name'=>$criterion->criterion_name));self::redirect('saved');}
            $name=sanitize_text_field(wp_unslash($_POST['criterion_name']??''));$description=sanitize_textarea_field(wp_unslash($_POST['criterion_description']??''));$max=self::decimal($_POST['max_points']??'');if($name===''||$max===false||$max<=0||$max>1000)self::redirect('invalid_criterion');
            $ok=$wpdb->update($wpdb->prefix.'zmp_scoring_criteria',array('criterion_name'=>$name,'description'=>$description,'max_points'=>$max),array('id'=>$criterion_id));if($ok===false)self::redirect('save_error');self::audit($season->id,$category_id,0,'criterion_updated',array('criterion_id'=>$criterion_id,'previous'=>array('name'=>$criterion->criterion_name,'max_points'=>(float)$criterion->max_points),'current'=>array('name'=>$name,'max_points'=>$max)));self::redirect('saved');
        }
        if($action==='assign_juror'){
            if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$category_id)))self::redirect('jury_locked');
            $juror=absint($_POST['juror_user_id']??0);$user=get_user_by('id',$juror);if(!$user||!in_array('zmp_commission',(array)$user->roles,true))self::redirect('invalid_juror');
            $ok=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}zmp_jury_assignments (season_id,category_id,juror_user_id,assigned_by,assigned_at) VALUES (%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE assigned_by=VALUES(assigned_by),assigned_at=VALUES(assigned_at)",$season->id,$category_id,$juror,$uid,current_time('mysql')));
            if($ok===false)self::redirect('save_error');self::audit($season->id,$category_id,0,'juror_assigned',array('juror_user_id'=>$juror));self::redirect('saved');
        }
        if($action==='unassign_juror'){
            $juror=absint($_POST['juror_user_id']??0);if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$category_id)))self::redirect('jury_locked');
            $ok=$wpdb->delete($wpdb->prefix.'zmp_jury_assignments',array('season_id'=>(int)$season->id,'category_id'=>$category_id,'juror_user_id'=>$juror),array('%d','%d','%d'));if($ok===false)self::redirect('save_error');self::audit($season->id,$category_id,0,'juror_unassigned',array('juror_user_id'=>$juror));self::redirect('saved');
        }
        if($action==='save_awards'){
            if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d",$season->id,$category_id)))self::redirect('awards_locked');
            $values=isset($_POST['award_threshold'])&&is_array($_POST['award_threshold'])?wp_unslash($_POST['award_threshold']):array();$parsed=array();
            foreach(self::AWARDS as $key=>$label){$v=self::decimal($values[$key]??'');if($v===false||$v<0)self::redirect('invalid_awards');$parsed[$key]=$v;}
            $nums=array_values($parsed);for($i=1;$i<count($nums);$i++){if($nums[$i-1]<=$nums[$i])self::redirect('invalid_awards');}if($parsed['first']>self::maximum_points($season->id,$category_id))self::redirect('invalid_awards');
            $wpdb->query('START TRANSACTION');foreach($parsed as $key=>$min){$ok=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}zmp_category_awards (season_id,category_id,award_key,min_points,updated_by,updated_at) VALUES (%d,%d,%s,%f,%d,%s) ON DUPLICATE KEY UPDATE min_points=VALUES(min_points),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)",$season->id,$category_id,$key,$min,$uid,current_time('mysql')));if($ok===false){$wpdb->query('ROLLBACK');self::redirect('save_error');}}
            self::audit($season->id,$category_id,0,'award_thresholds_updated',$parsed);if($wpdb->last_error){$wpdb->query('ROLLBACK');self::redirect('save_error');}$wpdb->query('COMMIT');self::redirect('saved');
        }
        if($action==='submit_assessment')self::submit_assessment($season,$category_id);
        if($action==='request_correction')self::request_correction($season,$category_id);
        if($action==='resolve_correction')self::resolve_correction($season,$category_id);
        if($action==='close_stage')self::close_stage($season,$category_id);
        if($action==='finalize_result')self::finalize_result($season,$category_id);
        if($action==='disqualify_entry')self::disqualify_entry($season,$category_id);
        self::redirect('unknown_action');
    }

    /** Return enabled category stages in their configured order. */
    private static function enabled_stages($season_id,$category_id) {
        global $wpdb;
        return (array)$wpdb->get_results($wpdb->prepare(
            "SELECT st.phase,st.delivery_mode,st.sort_order,st.min_entries,sc.municipal_min_entries FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND st.is_enabled=1 AND st.phase IN ('municipal','regional','state') ORDER BY st.sort_order,st.id",
            absint($season_id),absint($category_id)
        ));
    }
    private static function stage_is_closed($season_id,$category_id,$phase) {
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_stage_closures WHERE season_id=%d AND category_id=%d AND phase=%s",absint($season_id),absint($category_id),sanitize_key($phase)));
    }
    private static function municipal_threshold($season_id,$category_id) {
        global $wpdb;
        return max(1,(int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(NULLIF(st.min_entries,0),NULLIF(sc.municipal_min_entries,0),1) FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND st.phase='municipal' AND st.is_enabled=1 LIMIT 1",
            absint($season_id),absint($category_id)
        )));
    }
    /** Count only live entries in the same municipality, season, and category. */
    private static function municipality_entry_count($season_id,$category_id,$municipality,$region) {
        global $wpdb;
        $municipality=trim((string)$municipality);$region=sanitize_key((string)$region);if($municipality===''||$region==='')return 0;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_entries WHERE season_id=%d AND category_id=%d AND municipality=%s AND region=%s AND status IN ('submitted','approved','complete')",
            absint($season_id),absint($category_id),$municipality,$region
        ));
    }
    private static function municipality_below_minimum($season_id,$category_id,$municipality,$region) {
        global $wpdb;
        $municipality=trim((string)$municipality);$region=sanitize_key((string)$region);if($municipality===''||$region==='')return false;
        $enabled=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND st.phase='municipal' AND st.is_enabled=1",absint($season_id),absint($category_id)));
        if(!$enabled)return false;
        return self::municipality_entry_count($season_id,$category_id,$municipality,$region)<self::municipal_threshold($season_id,$category_id);
    }    /** Return the active entries eligible to be judged at this stage; false means the stage is unavailable. */
    public static function stage_eligible_entries($season_id,$category_id,$phase) {
        global $wpdb;
        $season_id=absint($season_id);$category_id=absint($category_id);$phase=sanitize_key($phase);
        if(!$season_id||!$category_id||!isset(self::PHASES[$phase]))return false;
        $stages=self::enabled_stages($season_id,$category_id);$index=null;
        foreach($stages as $i=>$stage){if($stage->phase===$phase){$index=$i;break;}}
        if($index===null)return false;

        $entries=(array)$wpdb->get_results($wpdb->prepare(
            "SELECT id,region,municipality FROM {$wpdb->prefix}zmp_category_entries WHERE season_id=%d AND category_id=%d AND status IN ('submitted','approved','complete') ORDER BY id",
            $season_id,$category_id
        ));
        $eligible=array();
        foreach($entries as $entry){
            $skips_municipal=self::municipality_below_minimum($season_id,$category_id,(string)$entry->municipality,(string)$entry->region);
            if($phase==='municipal'&&$skips_municipal)continue;
            if($index>0){
                $previous=$stages[$index-1]->phase;$bypass_municipal=$previous==='municipal'&&$skips_municipal;
                if(!$bypass_municipal){
                    if(!self::stage_is_closed($season_id,$category_id,$previous))return false;
                    $qualified=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='confirmed' AND qualified_next_stage=1",$season_id,$category_id,(int)$entry->id,$previous));
                    if(!$qualified)continue;
                }
            }
            $eligible[]=$entry;
        }
        return $eligible;
    }
    /** Whether a phase-scoped discipline decision excludes an entry from its result set. */
    public static function stage_entry_is_disqualified($season_id,$category_id,$entry_id,$phase) {
        return self::is_disqualified(absint($season_id),absint($category_id),absint($entry_id),sanitize_key($phase));
    }
    /** Entry-stage gate; later stages require a closed predecessor and explicit qualification unless that entry skipped municipal. */
    public static function stage_access_status($season_id,$category_id,$entry_id,$phase) {
        global $wpdb;
        $season_id=absint($season_id);$category_id=absint($category_id);$entry_id=absint($entry_id);$phase=sanitize_key($phase);
        if(!$season_id||!$category_id||!$entry_id||!isset(self::PHASES[$phase]))return array('allowed'=>false,'reason'=>'stage_disabled');
        $entry_exists=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",$entry_id,$season_id,$category_id));
        if(!$entry_exists)return array('allowed'=>false,'reason'=>'entry_scope_invalid');
        $stages=self::enabled_stages($season_id,$category_id);$index=null;
        foreach($stages as $i=>$stage){if($stage->phase===$phase){$index=$i;break;}}
        if($index===null)return array('allowed'=>false,'reason'=>'stage_disabled');
        $entry=$wpdb->get_row($wpdb->prepare("SELECT municipality,region FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",$entry_id,$season_id,$category_id));
        if(!$entry)return array('allowed'=>false,'reason'=>'entry_scope_invalid');
        $skips_municipal=self::municipality_below_minimum($season_id,$category_id,(string)$entry->municipality,(string)$entry->region);
        if($phase==='municipal'&&$skips_municipal)return array('allowed'=>false,'reason'=>'municipal_stage_skipped');
        if(self::stage_is_closed($season_id,$category_id,$phase))return array('allowed'=>false,'reason'=>'stage_closed');
        if($index>0){
            $previous=$stages[$index-1]->phase;
            $bypass_municipal=$previous==='municipal'&&$skips_municipal;
            if(!$bypass_municipal){
                if(!self::stage_is_closed($season_id,$category_id,$previous))return array('allowed'=>false,'reason'=>'previous_stage_open');
                $qualified=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='confirmed' AND qualified_next_stage=1",$season_id,$category_id,$entry_id,$previous));
                if(!$qualified)return array('allowed'=>false,'reason'=>'not_qualified');
            }
        }
        return array('allowed'=>true,'reason'=>'');
    }
    private static function next_stage_exists($season_id,$category_id,$phase) {
        $stages=self::enabled_stages($season_id,$category_id);
        foreach($stages as $i=>$stage){if($stage->phase===$phase)return isset($stages[$i+1]);}
        return false;
    }
    /** Moderator-only, exact-scope stage close. */
    private static function close_stage($season,$category_id) {
        global $wpdb;
        if(!current_user_can('manage_options')&&!current_user_can('zmp_moderator_cap'))wp_die('Немате дозвола за затворање на натпреварувачко ниво.','Нема пристап',array('response'=>403));
        $phase=sanitize_key(wp_unslash($_POST['phase']??''));$note=sanitize_textarea_field(wp_unslash($_POST['close_note']??''));
        if(!isset(self::PHASES[$phase])||mb_strlen($note)<30)self::redirect('stage_close_invalid');
        self::acquire_stage_lock((int)$season->id,(int)$category_id,$phase);
        $stages=self::enabled_stages((int)$season->id,$category_id);$index=null;
        foreach($stages as $i=>$stage){if($stage->phase===$phase){$index=$i;break;}}
        if($index===null)self::redirect('stage_disabled');
        if(self::stage_is_closed((int)$season->id,$category_id,$phase))self::redirect('stage_already_closed');
        $entries=self::stage_eligible_entries((int)$season->id,$category_id,$phase);
        if($entries===false)self::redirect($index>0?'previous_stage_open':'stage_disabled');
        $eligible=count($entries);
        foreach($entries as $entry){
            if(class_exists('ZMP_Document_Review')&&!ZMP_Document_Review::documents_ready((int)$entry->id))self::redirect('documents_not_approved');
            if($phase==='regional'&&!self::regional_media_ready((int)$season->id,$category_id,(int)$entry->id,(string)$entry->region))self::redirect('media_not_ready');
            $decided=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='confirmed'",(int)$season->id,$category_id,(int)$entry->id,$phase));
            if(!$decided&&!self::is_disqualified((int)$season->id,$category_id,(int)$entry->id,$phase))self::redirect('stage_decisions_incomplete');
        }
        $pending=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE season_id=%d AND category_id=%d AND phase=%s AND status='pending'",(int)$season->id,$category_id,$phase));
        if($pending)self::redirect('stage_corrections_pending');
        $wpdb->query('START TRANSACTION');
        $inserted=$wpdb->insert($wpdb->prefix.'zmp_category_stage_closures',array('season_id'=>(int)$season->id,'category_id'=>(int)$category_id,'phase'=>$phase,'close_note'=>$note,'closed_by'=>get_current_user_id(),'closed_at'=>current_time('mysql')),array('%d','%d','%s','%s','%d','%s'));
        if(!$inserted){$wpdb->query('ROLLBACK');if(self::stage_is_closed((int)$season->id,$category_id,$phase))self::redirect('stage_already_closed');self::redirect('save_error');}
        if(!self::audit($season->id,$category_id,0,'category_stage_closed',array('phase'=>$phase,'eligible_entries'=>$eligible,'close_note'=>$note))){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        $wpdb->query('COMMIT');
        self::redirect('stage_closed');
    }

    private static function disqualify_entry($season,$category_id){
        global $wpdb;
        $entry_id=absint($_POST['entry_id']??0);
        $phase=sanitize_key(wp_unslash($_POST['phase']??''));
        $reason=sanitize_textarea_field(wp_unslash($_POST['discipline_reason']??''));
        if(!$entry_id||!isset(self::PHASES[$phase])||mb_strlen($reason)<30)self::redirect('discipline_invalid');
        $entry=$wpdb->get_row($wpdb->prepare("SELECT id,participant_name,school_name FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d AND status IN ('submitted','approved','complete')",$entry_id,$season->id,$category_id));
        if(!$entry)wp_die('Пријавата не припаѓа на доделената категорија и година.','Нема пристап',array('response'=>403));self::require_phase_assignment((int)$season->id,$category_id,$entry_id,$phase);
        self::acquire_stage_lock((int)$season->id,(int)$category_id,$phase);$access=self::stage_access_status((int)$season->id,$category_id,$entry_id,$phase);if(empty($access['allowed']))self::redirect($access['reason']);
        $final=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='confirmed'",$season->id,$category_id,$entry_id,$phase));
        if($final)self::redirect('discipline_after_final');
        $saved=$wpdb->insert($wpdb->prefix.'zmp_jury_discipline',array('season_id'=>(int)$season->id,'category_id'=>$category_id,'entry_id'=>$entry_id,'phase'=>$phase,'actor_user_id'=>get_current_user_id(),'reason'=>$reason,'created_at'=>current_time('mysql')));
        if(!$saved){
            $exists=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_discipline WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$category_id,$entry_id,$phase));
            if(!$exists)self::redirect('save_error');
            self::redirect('already_disqualified');
        }
        self::audit($season->id,$category_id,$entry_id,'entry_disqualified',array('phase'=>$phase,'participant'=>$entry->participant_name,'reason'=>$reason));
        self::redirect('entry_disqualified');
    }

    /** Check a disqualification using the complete season/category/entry/stage scope. */
    private static function is_disqualified($season_id,$category_id,$entry_id,$phase){
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_discipline WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",(int)$season_id,(int)$category_id,(int)$entry_id,sanitize_key($phase)));
    }

    /** Verify every required YouTube upload before enabling online regional scoring. */
    private static function regional_media_ready($season_id,$category_id,$entry_id,$region){
        if(!class_exists('ZMP_YouTube'))return false;
        $mode=ZMP_YouTube::regional_delivery_mode((int)$season_id,(int)$category_id,(string)$region);
        if($mode==='unconfigured')return false;
        if($mode!=='online')return in_array($mode,array('physical','none'),true);
        global $wpdb;
        $category_code=(string)$wpdb->get_var($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",(int)$category_id));
        $required=ZMP_YouTube::required_slots($category_code);if(!$required)return false;
        foreach($required as $slot=>$label){$ready=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_youtube_media WHERE entry_id=%d AND phase='regional' AND slot_key=%s AND processing_status='ready'",(int)$entry_id,sanitize_key($slot)));if(!$ready)return false;}
        return true;
    }
    private static function submit_assessment($season,$category_id){
        global $wpdb;$uid=get_current_user_id();$entry_id=absint($_POST['entry_id']??0);$phase=sanitize_key(wp_unslash($_POST['phase']??''));$rationale=sanitize_textarea_field(wp_unslash($_POST['rationale']??''));$private_notes=sanitize_textarea_field(wp_unslash($_POST['private_notes']??''));
        if(!array_key_exists($phase,self::PHASES))self::redirect('invalid_entry_phase');if(mb_strlen($rationale)<30)self::redirect('rationale_required');
                if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$category_id,$entry_id,$phase)))self::redirect('result_locked');
        $entry=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d AND status IN ('submitted','approved')",$entry_id,$season->id,$category_id));
        $map=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d AND is_enabled=1",$season->id,$category_id));$phase_ok=$map?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_stages WHERE season_category_id=%d AND phase=%s AND is_enabled=1",$map->id,$phase)):0;
        if(!$entry||!$phase_ok)self::redirect('invalid_entry_phase');self::require_phase_assignment((int)$season->id,$category_id,$entry_id,$phase);self::acquire_stage_lock((int)$season->id,(int)$category_id,$phase);$access=self::stage_access_status((int)$season->id,$category_id,$entry_id,$phase);if(empty($access['allowed']))self::redirect($access['reason']);if(self::is_disqualified((int)$season->id,$category_id,$entry_id,$phase))self::redirect('entry_disqualified');if(class_exists('ZMP_Document_Review')&&!ZMP_Document_Review::documents_ready($entry_id))self::redirect('documents_not_approved');
        if($phase==='regional'&&!self::regional_media_ready((int)$season->id,$category_id,$entry_id,(string)$entry->region))self::redirect('media_not_ready');
        if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$category_id,$entry_id,$phase)))self::redirect('result_locked');
        $criteria=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1 ORDER BY sort_order,id",$season->id,$category_id));if(!$criteria)self::redirect('no_criteria');
        $raw=isset($_POST['scores'])&&is_array($_POST['scores'])?wp_unslash($_POST['scores']):array();$scores=array();$total=0;foreach($criteria as $criterion){$val=self::decimal($raw[$criterion->id]??'');if($val===false||$val<0||$val>(float)$criterion->max_points)self::redirect('invalid_score');$scores[$criterion->id]=$val;$total+=$val;}
        $old=$wpdb->get_row($wpdb->prepare("SELECT id,total_points FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d",$season->id,$category_id,$entry_id,$phase,$uid));
        if($old)self::redirect('assessment_exists');
        $wpdb->query('START TRANSACTION');$saved=$wpdb->insert($wpdb->prefix.'zmp_jury_assessments',array('season_id'=>(int)$season->id,'category_id'=>$category_id,'entry_id'=>$entry_id,'phase'=>$phase,'juror_user_id'=>$uid,'total_points'=>round($total,2),'rationale'=>$rationale,'private_notes'=>$private_notes,'status'=>'submitted','submitted_at'=>current_time('mysql')));if(!$saved){$wpdb->query('ROLLBACK');self::redirect('save_error');}$aid=(int)$wpdb->insert_id;
        foreach($scores as $criterion_id=>$points){if(!$wpdb->insert($wpdb->prefix.'zmp_jury_scores',array('season_id'=>(int)$season->id,'assessment_id'=>$aid,'criterion_id'=>(int)$criterion_id,'points'=>$points,'note'=>''))){$wpdb->query('ROLLBACK');self::redirect('save_error');}}
        self::audit($season->id,$category_id,$entry_id,'assessment_submitted',array('assessment_id'=>$aid,'phase'=>$phase,'total'=>round($total,2),'scores'=>$scores,'rationale'=>$rationale));if($wpdb->last_error){$wpdb->query('ROLLBACK');self::redirect('save_error');}$wpdb->query('COMMIT');self::redirect('assessment_saved');
    }

    private static function request_correction($season,$category_id){
        global $wpdb;$uid=get_current_user_id();$entry_id=absint($_POST['entry_id']??0);$phase=sanitize_key(wp_unslash($_POST['phase']??''));$reason=sanitize_textarea_field(wp_unslash($_POST['correction_reason']??''));
                $entry=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",$entry_id,$season->id,$category_id));
        if(!$entry||!array_key_exists($phase,self::PHASES)||mb_strlen($reason)<30)self::redirect('correction_invalid');self::require_phase_assignment((int)$season->id,$category_id,$entry_id,$phase);self::acquire_stage_lock((int)$season->id,(int)$category_id,$phase);$access=self::stage_access_status((int)$season->id,$category_id,$entry_id,$phase);if(empty($access['allowed']))self::redirect($access['reason']);if(self::is_disqualified((int)$season->id,$category_id,$entry_id,$phase))self::redirect('entry_disqualified');
        if(!(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d",$season->id,$category_id,$entry_id,$phase,$uid)))self::redirect('correction_invalid');
        if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d AND status='pending'",$season->id,$category_id,$entry_id,$phase,$uid)))self::redirect('correction_pending');
        $mods=get_users(array('role'=>'zmp_moderator','fields'=>array('ID')));if(!$mods)self::redirect('correction_delivery_failed');
        $wpdb->query('START TRANSACTION');
        $ok=$wpdb->insert($wpdb->prefix.'zmp_jury_correction_requests',array('season_id'=>(int)$season->id,'category_id'=>$category_id,'entry_id'=>$entry_id,'phase'=>$phase,'juror_user_id'=>$uid,'reason'=>$reason,'status'=>'pending','decision_note'=>'','created_at'=>current_time('mysql')));
        if(!$ok){$wpdb->query('ROLLBACK');self::redirect('save_error');}$request_id=(int)$wpdb->insert_id;
        foreach($mods as $mod)if(!$wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>$uid,'sender_role'=>'zmp_commission','recipient_id'=>(int)$mod->ID,'competition_id'=>0,'subject'=>'Барање за исправка на оценка #'.$request_id,'message'=>$reason,'status'=>'unread','created_at'=>current_time('mysql')))){$wpdb->query('ROLLBACK');self::redirect('correction_delivery_failed');}
        $wpdb->query('COMMIT');self::redirect('correction_requested');
    }

    private static function resolve_correction($season,$category_id){
        global $wpdb;$id=absint($_POST['correction_request_id']??0);$decision=sanitize_key(wp_unslash($_POST['decision']??''));$note=sanitize_textarea_field(wp_unslash($_POST['decision_note']??''));
        $request=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE id=%d AND season_id=%d AND category_id=%d AND status='pending'",$id,$season->id,$category_id));
        if(!$request||!in_array($decision,array('approved','rejected'),true)||mb_strlen($note)<30)self::redirect('correction_invalid');self::acquire_stage_lock((int)$season->id,(int)$category_id,(string)$request->phase);$access=self::stage_access_status((int)$season->id,$category_id,(int)$request->entry_id,(string)$request->phase);if(empty($access['allowed']))self::redirect($access['reason']);if($decision==='approved'&&self::is_disqualified((int)$season->id,$category_id,(int)$request->entry_id,(string)$request->phase))self::redirect('entry_disqualified');
        $final_result=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$category_id,$request->entry_id,$request->phase));
        $assessment=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d",$season->id,$category_id,$request->entry_id,$request->phase,$request->juror_user_id));
        if(!$assessment)self::redirect('correction_invalid');
        $old_scores=$wpdb->get_results($wpdb->prepare("SELECT criterion_id,points FROM {$wpdb->prefix}zmp_jury_scores WHERE assessment_id=%d",$assessment->id),ARRAY_A);
        $new_scores=array();$total=0;
        if($decision==='approved'){
            $criteria=$wpdb->get_results($wpdb->prepare("SELECT id,max_points FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1",$season->id,$category_id));$posted=is_array($_POST['scores']??null)?wp_unslash($_POST['scores']):array();
            foreach($criteria as $criterion){$v=self::decimal($posted[$criterion->id]??'');if($v===false||$v<0||$v>(float)$criterion->max_points)self::redirect('correction_invalid');$new_scores[(int)$criterion->id]=$v;$total+=$v;}
            if(!$criteria)self::redirect('correction_invalid');
        }
        $wpdb->query('START TRANSACTION');$withdrawn_results=array();
        if($decision==='approved'){
            if($wpdb->delete($wpdb->prefix.'zmp_jury_scores',array('assessment_id'=>(int)$assessment->id))===false){$wpdb->query('ROLLBACK');self::redirect('save_error');}
            foreach($new_scores as $criterion_id=>$points)if(!$wpdb->insert($wpdb->prefix.'zmp_jury_scores',array('season_id'=>(int)$season->id,'assessment_id'=>(int)$assessment->id,'criterion_id'=>$criterion_id,'points'=>$points,'note'=>''))){$wpdb->query('ROLLBACK');self::redirect('save_error');}
            if($wpdb->update($wpdb->prefix.'zmp_jury_assessments',array('total_points'=>round($total,2),'status'=>'submitted','submitted_at'=>current_time('mysql')),array('id'=>(int)$assessment->id))===false){$wpdb->query('ROLLBACK');self::redirect('save_error');}
            if($final_result&&$final_result->status==='confirmed'){
                if($wpdb->update($wpdb->prefix.'zmp_category_final_results',array('status'=>'pending_review'),array('id'=>(int)$final_result->id,'status'=>'confirmed'))!==1){$wpdb->query('ROLLBACK');self::redirect('save_error');}
                // Withdraw result notices and their ordinary WordPress posts until
                // the commission has reconfirmed this category/phase result.
                $result_announcements=$wpdb->get_results($wpdb->prepare("SELECT id,wp_post_id FROM {$wpdb->prefix}zmp_announcements WHERE kind='results' AND academic_year=%s AND category_id=%d AND phase=%s AND status='published'",$season->academic_year,$category_id,$request->phase));
                foreach((array)$result_announcements as $announcement){
                    if($announcement->wp_post_id){$post_update=wp_update_post(array('ID'=>(int)$announcement->wp_post_id,'post_status'=>'draft'),true);if(is_wp_error($post_update)||!$post_update){$wpdb->query('ROLLBACK');self::redirect('save_error');}}
                    if($wpdb->update($wpdb->prefix.'zmp_announcements',array('status'=>'draft'),array('id'=>(int)$announcement->id,'status'=>'published'))===false){$wpdb->query('ROLLBACK');self::redirect('save_error');}
                    $withdrawn_results[]=(int)$announcement->id;
                }
            }
        }
        if($wpdb->update($wpdb->prefix.'zmp_jury_correction_requests',array('status'=>$decision,'decision_note'=>$note,'decided_by'=>get_current_user_id(),'decided_at'=>current_time('mysql')),array('id'=>$id,'status'=>'pending'))!==1){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        $decision_message=$decision==='approved'?'Барањето е одобрено и оценката е исправена. ':'Барањето е одбиено. ';
        if($decision==='approved'&&$final_result&&$final_result->status==='confirmed')$decision_message.='Објавениот резултат е привремено скриен додека комисијата повторно не го потврди. ';
        if(!$wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>get_current_user_id(),'sender_role'=>'zmp_moderator','recipient_id'=>(int)$request->juror_user_id,'competition_id'=>0,'subject'=>'Одлука за барање за исправка на оценка #'.$id,'message'=>$decision_message.$note,'status'=>'unread','created_at'=>current_time('mysql')))){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        self::audit($season->id,$category_id,$request->entry_id,$decision==='approved'?'jury_score_corrected':'jury_score_correction_rejected',array('request_id'=>$id,'assessment_id'=>(int)$assessment->id,'old_total'=>(float)$assessment->total_points,'new_total'=>$decision==='approved'?round($total,2):(float)$assessment->total_points,'old_scores'=>$old_scores,'new_scores'=>$new_scores,'withdrawn_result_announcements'=>$withdrawn_results,'decision'=>$decision,'reason'=>$note));
        if($wpdb->last_error){$wpdb->query('ROLLBACK');self::redirect('save_error');}$wpdb->query('COMMIT');self::redirect('correction_decided');
    }

    private static function finalize_result($season,$category_id){
        global $wpdb;$entry_id=absint($_POST['entry_id']??0);$phase=sanitize_key(wp_unslash($_POST['phase']??''));$basis=sanitize_textarea_field(wp_unslash($_POST['decision_basis']??''));$note=sanitize_textarea_field(wp_unslash($_POST['decision_note']??''));if(!array_key_exists($phase,self::PHASES))self::redirect('invalid_entry_phase');self::acquire_stage_lock((int)$season->id,(int)$category_id,$phase);$access=self::stage_access_status((int)$season->id,$category_id,$entry_id,$phase);if(empty($access['allowed']))self::redirect($access['reason']);if(mb_strlen($basis)<30||mb_strlen($note)<30)self::redirect('decision_rationale_required');$note="Фактичка основа / доказ:\n".$basis."\nОбразложение на одлуката:\n".$note;
        if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_awards WHERE season_id=%d AND category_id=%d",$season->id,$category_id))!==count(self::AWARDS))self::redirect('awards_not_configured');
        $entry=$wpdb->get_row($wpdb->prepare("SELECT id,region FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d AND season_id=%d AND category_id=%d",$entry_id,$season->id,$category_id));
        if(!$entry)self::redirect('invalid_entry_phase');
        self::require_phase_assignment((int)$season->id,$category_id,$entry_id,$phase);
        if(self::is_disqualified((int)$season->id,$category_id,$entry_id,$phase))self::redirect('entry_disqualified');
        $assigned=self::phase_juror_count((int)$season->id,(int)$category_id,$phase,(string)$entry->region);
        if(in_array($phase,array('regional','municipal'),true)){
            $regional_join=" JOIN {$wpdb->prefix}zmp_jury_regional_assignments r ON r.season_id=a.season_id AND r.category_id=a.category_id AND r.region=%s AND r.juror_user_id=a.juror_user_id";
            if($phase==='municipal')$regional_join.=" JOIN {$wpdb->prefix}zmp_jury_assignments c ON c.season_id=r.season_id AND c.category_id=r.category_id AND c.juror_user_id=r.juror_user_id";
            $submitted=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments a$regional_join WHERE a.season_id=%d AND a.category_id=%d AND a.entry_id=%d AND a.phase=%s AND a.status='submitted'",(string)$entry->region,$season->id,$category_id,$entry_id,$phase));
            $avg_query=$wpdb->prepare("SELECT AVG(a.total_points) FROM {$wpdb->prefix}zmp_jury_assessments a$regional_join WHERE a.season_id=%d AND a.category_id=%d AND a.entry_id=%d AND a.phase=%s AND a.status='submitted'",(string)$entry->region,$season->id,$category_id,$entry_id,$phase);
        }else{
            $submitted=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='submitted'",$season->id,$category_id,$entry_id,$phase));
            $avg_query=$wpdb->prepare("SELECT AVG(total_points) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND status='submitted'",$season->id,$category_id,$entry_id,$phase);
        }
        if($wpdb->last_error!==''||$assigned<1||$submitted!==$assigned)self::redirect('assessments_incomplete');
        $existing_result=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$category_id,$entry_id,$phase));
        if($existing_result&&$existing_result->status!=='pending_review')self::redirect('result_locked');
        $avg=(float)$wpdb->get_var($avg_query);if($wpdb->last_error!=='')self::redirect('assessments_incomplete');$raw=trim((string)wp_unslash($_POST['final_points']??''));$final=$avg;
        if($raw!==''){$v=self::decimal($raw);if($v===false||$v<0||$v>self::maximum_points($season->id,$category_id))self::redirect('invalid_final_score');$final=$v;$note='Измена од просекот '.number_format($avg,2,'.','').' на '.number_format($final,2,'.','').'. Образложение: '.$note;}
        $award=self::award_for($season->id,$category_id,$final);$wpdb->query('START TRANSACTION');$qualified=self::next_stage_exists((int)$season->id,$category_id,$phase)&&!empty($_POST['qualified_next_stage'])?1:0;$result_data=array('jury_average'=>$avg,'final_points'=>$final,'award_key'=>$award,'qualified_next_stage'=>$qualified,'status'=>'confirmed','decision_note'=>$note,'decided_by'=>get_current_user_id(),'decided_at'=>current_time('mysql'));if($existing_result){$ok=$wpdb->update($wpdb->prefix.'zmp_category_final_results',$result_data,array('id'=>(int)$existing_result->id,'status'=>'pending_review'));}else{$result_data=array_merge(array('season_id'=>(int)$season->id,'category_id'=>$category_id,'entry_id'=>$entry_id,'phase'=>$phase),$result_data);$ok=$wpdb->insert($wpdb->prefix.'zmp_category_final_results',$result_data);}if($ok!==1){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        self::audit($season->id,$category_id,$entry_id,$existing_result?'result_reconfirmed_after_jury_correction':'result_confirmed',array('phase'=>$phase,'previous_final_points'=>$existing_result?(float)$existing_result->final_points:null,'jury_average'=>round($avg,2),'final_points'=>round($final,2),'award'=>$award,'note'=>$note));if($wpdb->last_error){$wpdb->query('ROLLBACK');self::redirect('save_error');}$wpdb->query('COMMIT');self::redirect($existing_result?'result_reconfirmed':'result_confirmed');
    }

    /** Render configuration and scoring forms in the commission horizontal tab. */
    public static function render_panel(){
        if(!self::can_manage())return '<section class="zmp-card" data-commission-panel="jury"><p>Немате пристап.</p></section>';
        global $wpdb;
        $season=self::active_season();
        $jury_only=self::is_restricted_juror();
        ob_start();
        echo '<section class="zmp-card zmp-category-jury" data-commission-panel="jury"><h3>Жири и оценување по категорија</h3>';
        self::render_notice();
        if(!$season){echo '<p>Нема активна натпреварувачка година.</p></section>';return ob_get_clean();}
        $cats=$wpdb->get_results($wpdb->prepare("SELECT c.id,c.category_code,c.name_mk FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 AND c.is_active=1 AND c.category_code<>'knowledge_test' ORDER BY c.sort_order",$season->id));
        if($jury_only){$cats=array_values(array_filter((array)$cats,function($cat)use($season){return self::has_any_assignment((int)$season->id,(int)$cat->id,get_current_user_id());}));}
        if(!$cats){echo '<p>Нема активни или доделени категории за оваа година.</p></section>';return ob_get_clean();}
        echo '<p>Година: <strong>'.esc_html($season->title).'</strong>. Комисијата оценува само во доделените категории; организациските задачи остануваат кај модераторите.</p>';
        if($jury_only){
            echo '<label>Категорија за оценување <select id="zmp-jury-category-select">';
            foreach($cats as $index=>$cat)echo '<option value="'.(int)$cat->id.'">'.esc_html($cat->name_mk).'</option>';
            echo '</select></label><script>(function(){var root=document.querySelector(".zmp-category-jury"),s=root&&root.querySelector("#zmp-jury-category-select");if(!root||!s)return;function show(){root.querySelectorAll("[data-zmp-jury-category]").forEach(function(x){x.hidden=x.dataset.zmpJuryCategory!==s.value;});}s.addEventListener("change",show);show();})();</script>';
        }
        foreach($cats as $index=>$cat){
            $cid=(int)$cat->id;
            $criteria=$wpdb->get_results($wpdb->prepare("SELECT id,criterion_name,description,max_points,sort_order FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1 ORDER BY sort_order,id",$season->id,$cid));
            $hidden=($jury_only&&$index>0)?' hidden':'';
            echo '<details class="zmp-card" data-zmp-jury-category="'.$cid.'"'.$hidden.' style="margin:1rem 0"><summary><strong>'.esc_html($cat->name_mk).'</strong></summary><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Критериум</th><th>Опис</th><th>Макс. бодови</th>'.($jury_only?'':'<th>Управување</th>').'</tr></thead><tbody>';
            foreach((array)$criteria as $cr){
                echo '<tr><td>'.esc_html($cr->criterion_name).'</td><td>'.esc_html($cr->description).'</td><td>'.esc_html(number_format((float)$cr->max_points,2)).'</td>';
                if(!$jury_only){
                    echo '<td><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="edit_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="criterion_id" value="'.(int)$cr->id.'"><label>Назив<input name="criterion_name" value="'.esc_attr($cr->criterion_name).'" maxlength="190" required></label><label>Опис<textarea name="criterion_description" rows="2">'.esc_textarea($cr->description).'</textarea></label><label>Максимум бодови<input type="number" name="max_points" min="0.01" max="1000" step="0.01" value="'.esc_attr($cr->max_points).'" required></label><button class="button">Зачувај</button></form><form method="post">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="deactivate_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="criterion_id" value="'.(int)$cr->id.'"><button class="button">Отстрани</button></form></td>';
                }
                echo '</tr>';
            }
            if(!$criteria)echo '<tr><td colspan="'.($jury_only?'3':'4').'">Критериумите сè уште не се внесени.</td></tr>';
            echo '</tbody></table></div>';
            if(!$jury_only){
                echo '<form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="add_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><h4>Додај критериум</h4><label>Назив<input name="criterion_name" maxlength="190" required></label><label>Опис и мерливи насоки<textarea name="criterion_description" rows="2"></textarea></label><label>Максимум бодови<input type="number" name="max_points" min="0.01" max="1000" step="0.01" required></label><button class="button button-primary">Додади критериум</button></form>';
                $assign=$wpdb->get_results($wpdb->prepare("SELECT a.juror_user_id,u.display_name,u.user_email FROM {$wpdb->prefix}zmp_jury_assignments a LEFT JOIN {$wpdb->users} u ON u.ID=a.juror_user_id WHERE a.season_id=%d AND a.category_id=%d ORDER BY u.display_name",$season->id,$cid));
                $thresholds=$wpdb->get_results($wpdb->prepare("SELECT award_key,min_points FROM {$wpdb->prefix}zmp_category_awards WHERE season_id=%d AND category_id=%d",$season->id,$cid));$awards=array();foreach((array)$thresholds as $t)$awards[$t->award_key]=$t->min_points;
                echo '<h4>Доделено жири</h4><ul>';foreach((array)$assign as $a){echo '<li>'.esc_html($a->display_name?:$a->user_email).' (ID '.(int)$a->juror_user_id.')';if(!(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$cid))){echo ' <form method="post" style="display:inline">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="unassign_juror"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="juror_user_id" value="'.(int)$a->juror_user_id.'"><button class="button">Отстрани</button></form>';};echo '</li>';};if(!$assign)echo '<li>Нема доделени членови.</li>';echo '</ul><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="assign_juror"><input type="hidden" name="category_id" value="'.$cid.'">';wp_dropdown_users(array('name'=>'juror_user_id','role'=>'zmp_commission','show_option_none'=>'Изберете член','option_none_value'=>'','orderby'=>'display_name'));echo '<button class="button">Додели член</button></form>';
                echo '<form method="post" class="zmp-form"><h4>Прагови за награди (максимум '.esc_html(number_format(self::maximum_points($season->id,$cid),2)).')</h4>';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="save_awards"><input type="hidden" name="category_id" value="'.$cid.'">';foreach(self::AWARDS as $key=>$label)echo '<label>'.esc_html($label).'<input type="number" name="award_threshold['.esc_attr($key).']" min="0" step="0.01" value="'.esc_attr($awards[$key]??'').'" required></label>';echo '<p>Праговите вредности мораат да бидат строго опаѓачки: Прва &gt; Втора &gt; Трета &gt; Пофалница.</p><button class="button">Зачувај прагови</button></form>';
            }
            self::render_scoring_entries($season,$cat,$criteria);
            if(class_exists('ZMP_Document_Review'))echo ZMP_Document_Review::render_panel((int)$season->id,$cid);
            self::render_results($season,$cid);
            echo '</details>';
        }
        echo '</section>';
        return ob_get_clean();
    }
    /** Moderator-only rubric editor; jury scores and final commission decisions stay in the commission panel. */
    public static function render_moderator_criteria_panel(){
        if(!is_user_logged_in()||(!current_user_can('zmp_moderator_cap')&&!current_user_can('manage_options')))return '';
        global $wpdb;$season=self::active_season();if(!$season)return '<section class="zmp-card"><h3>Критериуми и бодови</h3><p>Нема активна натпреварувачка година.</p></section>';
        $cats=$wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.is_enabled=1 AND c.is_active=1 AND c.category_code<>'knowledge_test' ORDER BY c.sort_order",$season->id));ob_start();
        echo '<section class="zmp-card"><h3>Критериуми и бодови за категориите</h3>';self::render_notice();echo '<p>Модераторите ги подготвуваат правилата пред жирито да започне со оценување. По првото оценување системот ги заклучува критериумите; конечните оценки и одлуки остануваат кај комисијата.</p>';
        foreach($cats as $cat){$cid=(int)$cat->id;$criteria=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1 ORDER BY sort_order,id",$season->id,$cid));$locked=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d",$season->id,$cid))>0;
            echo '<details class="zmp-card"><summary><strong>'.esc_html($cat->name_mk).'</strong></summary><p>'.($locked?'Оценувањето започнало — критериумите се заклучени.':'Внесете критериум и максимални бодови.').'</p><table class="zmp-table"><thead><tr><th>Критериум</th><th>Максимум бодови</th><th>Уредување</th></tr></thead><tbody>';
            foreach($criteria as $cr){echo '<tr><td>'.esc_html($cr->criterion_name).'</td><td>'.esc_html(number_format((float)$cr->max_points,2)).'</td><td><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="edit_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="criterion_id" value="'.(int)$cr->id.'"><label>Назив<input name="criterion_name" value="'.esc_attr($cr->criterion_name).'" required></label><label>Опис<textarea name="criterion_description">'.esc_textarea($cr->description).'</textarea></label><label>Максимум бодови<input type="number" name="max_points" min="0.01" max="1000" step="0.01" value="'.esc_attr($cr->max_points).'" required></label><button class="button" '.disabled($locked,true,false).'>Зачувај</button></form><form method="post">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="deactivate_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="criterion_id" value="'.(int)$cr->id.'"><button class="button" '.disabled($locked,true,false).'>Отстрани</button></form></td></tr>';}
            if(!$criteria)echo '<tr><td colspan="3">Критериумите сè уште не се внесени.</td></tr>';echo '</tbody></table><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<h4>Додај критериум</h4><input type="hidden" name="zmp_jury_action" value="add_criterion"><input type="hidden" name="category_id" value="'.$cid.'"><label>Назив<input name="criterion_name" maxlength="190" required></label><label>Опис и насоки<textarea name="criterion_description" rows="2"></textarea></label><label>Максимум бодови<input type="number" name="max_points" min="0.01" max="1000" step="0.01" required></label><button class="button button-primary" '.disabled($locked,true,false).'>Додај</button></form></details>';
        }
        foreach($cats as $cat){$cid=(int)$cat->id;$stages=self::enabled_stages((int)$season->id,$cid);if(!$stages)continue;echo '<h4>Затворање на натпреварувачки нивоа — '.esc_html($cat->name_mk).'</h4>';foreach($stages as $stage){$closed=self::stage_is_closed((int)$season->id,$cid,$stage->phase);echo '<form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="close_stage"><input type="hidden" name="category_id" value="'.$cid.'"><input type="hidden" name="phase" value="'.esc_attr($stage->phase).'"><label>Образложение за затворање (најмалку 30 знаци)<textarea name="close_note" minlength="30" required></textarea></label><button class="button" '.disabled($closed,true,false).'>'.esc_html((self::phase_label($stage->phase)).' — '.($closed?'Веќе затворено':'Затвори натпреварувачко ниво')).'</button></form>';}}
        echo '</section>';return ob_get_clean();
    }

    /** Moderators review correction correspondence and access jury-only evidence notes. */
    public static function render_moderator_review_panel(){
        if(!is_user_logged_in()||(!current_user_can('zmp_moderator_cap')&&!current_user_can('manage_options')))return '';
        global $wpdb;$season=self::active_season();if(!$season)return '';
        $requests=$wpdb->get_results($wpdb->prepare("SELECT r.*,c.name_mk AS category_name,e.participant_name,e.school_name,u.display_name AS juror_name FROM {$wpdb->prefix}zmp_jury_correction_requests r JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id LEFT JOIN {$wpdb->users} u ON u.ID=r.juror_user_id WHERE r.season_id=%d AND r.status='pending' ORDER BY r.created_at",$season->id));
        // A moderator can retrieve a juror's private note only in the context of a
        // specific correction correspondence, never as a browsable season-wide list.
        $evidence_request_id=absint($_GET['zmp_jury_evidence_request']??0);$evidence_request=null;$notes=array();
        if($evidence_request_id && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])),'zmp_jury_evidence_'.$evidence_request_id)){
            $evidence_request=$wpdb->get_row($wpdb->prepare("SELECT r.id,r.category_id,r.entry_id,r.phase,c.name_mk AS category_name,e.participant_name,e.school_name FROM {$wpdb->prefix}zmp_jury_correction_requests r JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id WHERE r.id=%d AND r.season_id=%d AND r.status IN ('pending','approved','rejected')",$evidence_request_id,$season->id));
            if($evidence_request){$notes=$wpdb->get_results($wpdb->prepare("SELECT a.phase,a.private_notes,a.submitted_at,u.display_name FROM {$wpdb->prefix}zmp_jury_assessments a LEFT JOIN {$wpdb->users} u ON u.ID=a.juror_user_id WHERE a.season_id=%d AND a.category_id=%d AND a.entry_id=%d AND a.phase=%s AND a.private_notes<>'' ORDER BY a.submitted_at DESC",$season->id,$evidence_request->category_id,$evidence_request->entry_id,$evidence_request->phase));$audit_ok=self::audit($season->id,$evidence_request->category_id,$evidence_request->entry_id,'private_notes_read',array('request_id'=>$evidence_request_id,'notes_count'=>count((array)$notes)));if(!$audit_ok)$notes=array();}
        }
        ob_start();echo '<section class="zmp-card"><h3>Жири: барања за исправка и приватни докази</h3><p>Белешките се видливи само за нивниот автор и овластени модератори; пристапот е врзан со барање за исправка и се евидентира.</p>';
        if(!$requests)echo '<p>Нема барања за исправка што чекаат.</p>';
        foreach($requests as $r){$assessment=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d",$r->season_id,$r->category_id,$r->entry_id,$r->phase,$r->juror_user_id));$score_rows=$assessment?$wpdb->get_results($wpdb->prepare("SELECT criterion_id,points FROM {$wpdb->prefix}zmp_jury_scores WHERE assessment_id=%d",$assessment->id),ARRAY_A):array();$saved=array();foreach((array)$score_rows as $sr)$saved[(int)$sr['criterion_id']]=(float)$sr['points'];$criteria=$wpdb->get_results($wpdb->prepare("SELECT id,criterion_name,max_points FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1 ORDER BY sort_order,id",$r->season_id,$r->category_id));
            echo '<article class="zmp-card"><h4>'.esc_html($r->category_name.' · '.$r->participant_name.' · '.self::phase_label($r->phase)).'</h4><p>Жирито: '.esc_html($r->juror_name?:'—').' · '.esc_html($r->school_name).'</p><p>'.nl2br(esc_html($r->reason)).'</p><p>Тековни бодови: '.esc_html(number_format((float)($assessment->total_points??0),2)).'</p><p><a class="button" href="'.esc_url(wp_nonce_url(add_query_arg('zmp_jury_evidence_request',(int)$r->id),'zmp_jury_evidence_'.(int)$r->id)).'">Отвори приватни белешки како доказ</a></p><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="resolve_correction"><input type="hidden" name="category_id" value="'.(int)$r->category_id.'"><input type="hidden" name="correction_request_id" value="'.(int)$r->id.'">';foreach((array)$criteria as $cr)echo '<label>'.esc_html($cr->criterion_name).' (0–'.esc_html(number_format((float)$cr->max_points,2)).')<input type="number" name="scores['.(int)$cr->id.']" min="0" max="'.esc_attr($cr->max_points).'" step="0.01" value="'.esc_attr($saved[(int)$cr->id]??'').'"></label>';echo '<label>Образложение на одлуката (најмалку 30 знаци)<textarea name="decision_note" minlength="30" required></textarea></label><button class="button button-primary" name="decision" value="approved">Одобри корекција и зачувај бодови</button><button class="button" name="decision" value="rejected">Одбиј со образложение</button></form></article>';
        }
        if($evidence_request){echo '<h4>Приватни докази за барање #'.(int)$evidence_request->id.'</h4><p>'.esc_html($evidence_request->category_name.' · '.$evidence_request->participant_name.' · '.$evidence_request->school_name.' · ('.(self::phase_label($evidence_request->phase)).')').'</p>';if(!$notes)echo '<p>За оваа пријава нема внесени приватни белешки.</p>';else{echo '<ul>';foreach($notes as $n)echo '<li><strong>'.esc_html($n->display_name?:'Член на жири').' · '.esc_html($n->submitted_at).'</strong><p>'.nl2br(esc_html($n->private_notes)).'</p></li>';echo '</ul>';}}
        echo '</section>';return ob_get_clean();
    }

    private static function approved_scoring_entries($season_id,$category_id,$phase='') {
        global $wpdb;
        $entries=$wpdb->get_results($wpdb->prepare("SELECT e.*,g.name_mk AS group_name FROM {$wpdb->prefix}zmp_category_entries e LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE e.season_id=%d AND e.category_id=%d AND e.status IN ('submitted','approved','complete') ORDER BY e.created_at,e.id",absint($season_id),absint($category_id)));
        $approved=array();
        foreach((array)$entries as $entry){
            if(class_exists('ZMP_Document_Review')&&!ZMP_Document_Review::documents_ready((int)$entry->id))continue;
            if($phase!==''&&self::is_disqualified((int)$season_id,(int)$category_id,(int)$entry->id,$phase))continue;
            $approved[]=$entry;
        }
        return $approved;
    }

    /** Printable physical-stage roster. It contains the same document-approved rows as the electronic scoring workspace. */
    public static function print_physical_roster() {
        if(!is_user_logged_in()||(!current_user_can('manage_options')&&!current_user_can('zmp_commission_cap')&&!current_user_can('view_zmp_commission_dashboard')))wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        $category_id=absint($_GET['category_id']??0);$phase=sanitize_key(wp_unslash($_GET['phase']??''));$season=self::active_season();
        if(!$season||!$category_id||!isset(self::PHASES[$phase])||!self::category_enabled((int)$season->id,$category_id))wp_die('Невалидна категорија или натпреварувачко ниво.','Невалиден опсег',array('response'=>400));
        check_admin_referer('zmp_jury_print_roster_'.(int)$season->id.'_'.$category_id.'_'.$phase);
        if(self::is_restricted_juror()){
            $uid=get_current_user_id();
            if($phase==='state'&&!self::has_phase_assignment((int)$season->id,$category_id,'state','',$uid))wp_die('Немате државна доделба за оваа категорија и година.','Нема пристап',array('response'=>403));
            if(in_array($phase,array('regional','municipal'),true)){
                $regions=self::regional_regions_for_juror((int)$season->id,$category_id,$uid);
                if(!is_array($regions)||!$regions||($phase==='municipal'&&!self::has_assignment((int)$season->id,$category_id,$uid)))wp_die('Немате регионална доделба за оваа категорија и година.','Нема пристап',array('response'=>403));
            }
        }
        global $wpdb;
        $stage=$wpdb->get_row($wpdb->prepare("SELECT st.delivery_mode FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND st.phase=%s AND st.is_enabled=1",$season->id,$category_id,$phase));
        if(!$stage||($phase!=='state'&&$phase!=='regional'&&$stage->delivery_mode==='online'))wp_die('Печатен список е достапен за натпреварувачко ниво со физичко присуство.','Невалиден начин на одржување',array('response'=>409));
        $criteria=$wpdb->get_results($wpdb->prepare("SELECT id,criterion_name,max_points FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1 ORDER BY sort_order,id",$season->id,$category_id));
        if(!$criteria)wp_die('Критериумите за оваа категорија не се внесени.','Недостасуваат критериуми',array('response'=>409));
        $entries=self::approved_scoring_entries((int)$season->id,$category_id,$phase);
        if(self::is_restricted_juror()){
            if(in_array($phase,array('regional','municipal'),true)){
                $regions=self::regional_regions_for_juror((int)$season->id,$category_id,get_current_user_id());
                if($regions===false)wp_die('Регионалните назначувања не може безбедно да се проверат.','Грешка',array('response'=>500));
$entries=array_values(array_filter((array)$entries,function($entry)use($regions){return in_array(sanitize_key($entry->region),$regions,true);}));
            }elseif($phase==='state'){
                $eligible_ids=self::state_eligible_entry_ids((int)$season->id,$category_id);
                $entries=array_values(array_filter((array)$entries,function($entry)use($eligible_ids){return in_array((int)$entry->id,$eligible_ids,true);}));
            }else{
$entries=array_values(array_filter((array)$entries,function($entry)use($regions){return in_array(sanitize_key($entry->region),$regions,true);}));
            }
        }
        if($phase==='regional'&&class_exists('ZMP_YouTube'))$entries=array_values(array_filter($entries,function($entry)use($season,$category_id){return ZMP_YouTube::regional_delivery_mode((int)$season->id,$category_id,(string)$entry->region)==='physical';}));
        if($phase==='regional'&&!$entries)wp_die('Нема документарно одобрени учесници со физичко регионално натпреварувачко ниво за печатење.','Нема физички учесници',array('response'=>409));
        nocache_headers();header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="mk"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Листа за оценување</title><style>body{font:14px Arial,sans-serif;color:#111;margin:20px}h1,h2{margin:.3em 0}table{border-collapse:collapse;width:100%;margin-top:14px}th,td{border:1px solid #555;padding:6px;vertical-align:top}th{background:#eee}.score{min-width:70px;height:32px}.small{font-size:11px}@media print{.actions{display:none}body{margin:8mm}tr{break-inside:avoid}thead{display:table-header-group}}</style>';
        echo '<h1>Жирирање — '.esc_html(self::PHASES[$phase]).'</h1><h2>'.esc_html($season->title).' · '.esc_html($wpdb->get_var($wpdb->prepare("SELECT name_mk FROM {$wpdb->prefix}zmp_categories WHERE id=%d",$category_id))).'</h2><p>Учесници со одобрена документација: '.count($entries).'. Критериумите се исти како во електронскиот образец.</p><div class="actions"><button onclick="window.print()">Печати</button></div><table><thead><tr><th>#</th><th>Учесник / состав</th><th>Група</th><th>Училиште / општина / регион</th>';
        foreach($criteria as $criterion)echo '<th>'.esc_html($criterion->criterion_name).'<br><span class="small">макс. '.esc_html(number_format((float)$criterion->max_points,2)).'</span></th>';
        echo '<th>Забелешки / вкупно</th></tr></thead><tbody>';
        foreach($entries as $i=>$entry){echo '<tr><td>'.($i+1).'</td><td>'.esc_html($entry->participant_name).'</td><td>'.esc_html($entry->group_name).'</td><td>'.esc_html($entry->school_name).' / '.esc_html($entry->municipality).' / '.esc_html(ZMP_Regions::label($entry->region)).'</td>';foreach($criteria as $criterion)echo '<td class="score"></td>';echo '<td class="score"></td></tr>';}
        if(!$entries)echo '<tr><td colspan="'.(5+count($criteria)).'">Нема учесници со одобрена документација.</td></tr>';
        echo '</tbody></table><script>window.addEventListener("load",function(){if(new URLSearchParams(location.search).get("autoprint")==="1")window.print();});</script></html>';
        exit;
    }

    private static function render_scoring_entries($season,$cat,$criteria) {
        global $wpdb;$uid=get_current_user_id();
        $assigned=current_user_can('manage_options')?true:self::has_any_assignment((int)$season->id,(int)$cat->id,$uid);
        if(!$assigned){echo '<p>Оценувачките формулари се видливи само на доделените членови на жирито.</p>';return;}
        if(!$criteria){echo '<p>Прво треба да се внесат критериуми за оценување.</p>';return;}
        $entries=$wpdb->get_results($wpdb->prepare("SELECT e.*,g.name_mk AS group_name FROM {$wpdb->prefix}zmp_category_entries e LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE e.season_id=%d AND e.category_id=%d AND e.status IN ('submitted','approved','complete') ORDER BY e.created_at,e.id",$season->id,$cat->id));
        $map=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d",$season->id,$cat->id));
        $stages=$map?$wpdb->get_results($wpdb->prepare("SELECT phase,delivery_mode FROM {$wpdb->prefix}zmp_season_stages WHERE season_category_id=%d AND is_enabled=1 AND phase IN ('municipal','regional','state') ORDER BY sort_order",$map->id)):array();
        $state_eligible_ids=self::state_eligible_entry_ids((int)$season->id,(int)$cat->id);
        $regional_regions=array();
        if(self::is_restricted_juror()){
            $regional_regions=self::regional_regions_for_juror((int)$season->id,(int)$cat->id,$uid);
            if($regional_regions===false){
                $entries=array_values(array_filter((array)$entries,function($entry)use($state_eligible_ids){return in_array((int)$entry->id,$state_eligible_ids,true);}));
            }else{
                $entries=array_values(array_filter((array)$entries,function($entry)use($regional_regions,$state_eligible_ids){return in_array(sanitize_key($entry->region),$regional_regions,true)||in_array((int)$entry->id,$state_eligible_ids,true);}));
            }
        }
        if(!$entries){echo '<p>Нема пријави достапни за вашето назначување и натпреварувачко ниво.</p>';return;}
        echo '<div class="zmp-jury-print-rosters"><h5>Печатни листи за физичко оценување</h5>';
        foreach($stages as $print_stage){$phase=$print_stage->phase;if($phase!=='state'&&$phase!=='regional'&&$print_stage->delivery_mode==='online')continue;$roster_url=wp_nonce_url(add_query_arg(array('action'=>'zmp_jury_print_roster','category_id'=>(int)$cat->id,'phase'=>$phase),admin_url('admin-post.php')),'zmp_jury_print_roster_'.(int)$season->id.'_'.(int)$cat->id.'_'.$phase);echo '<a class="button" target="_blank" rel="noopener" href="'.esc_url($roster_url).'">Печатен список — '.esc_html(self::phase_label($phase)).'</a> ';}
        echo '</div>';
        foreach($entries as $entry){
            $document_state=class_exists('ZMP_Document_Review')?ZMP_Document_Review::entry_label((int)$entry->id):array('key'=>'ready_for_review','label'=>'Подготвена за оценување');
            echo '<article class="zmp-card zmp-jury-entry"><h4>'.esc_html($entry->participant_name).' — '.esc_html($entry->group_name).' ('.esc_html($entry->school_name).')</h4><p><span class="zmp-doc-state zmp-doc-state--'.esc_attr($document_state['key']).'">'.esc_html($document_state['label']).'</span></p>';
            foreach($stages as $stage){
                $phase=$stage->phase;
                if(self::is_restricted_juror()&&!self::phase_entry_authorized((int)$season->id,(int)$cat->id,(int)$entry->id,(string)$phase,$uid))continue;
                $phase_name=self::phase_label($phase);$stage_access=self::stage_access_status((int)$season->id,(int)$cat->id,(int)$entry->id,(string)$phase);if(empty($stage_access['allowed'])){echo '<h5>'.esc_html($phase_name).'</h5><p class="zmp-alert">'.esc_html($stage_access['reason']==='previous_stage_open'?'Претходното натпреварувачко ниво сè уште не е затворено.':($stage_access['reason']==='not_qualified'?'Учесникот не е пласиран во ова натпреварувачко ниво.':($stage_access['reason']==='stage_closed'?'Натпреварувачкото ниво е затворено за оценување.':($stage_access['reason']==='municipal_stage_skipped'?'Општинското натпреварувачко ниво е прескокнато за оваа општина; учесникот може да продолжи во регионалното натпреварувачко ниво.':'Натпреварувачкото ниво не е активно.')))).'</p>';continue;}
                $regional_mode=($phase==='regional'&&class_exists('ZMP_YouTube'))?ZMP_YouTube::regional_delivery_mode((int)$season->id,(int)$cat->id,(string)$entry->region):'';
                $online=$regional_mode==='online';$regional_video_stage=in_array($regional_mode,array('online','physical'),true);$player='';
                if($phase==='regional'&&(!class_exists('ZMP_YouTube')||$regional_mode==='unconfigured')){echo '<h5>'.esc_html($phase_name).'</h5><p class="zmp-alert zmp-alert-warning zmp-jury-delivery-warning">Не може безбедно да се потврди начинот на регионалното натпреварување; оценувањето е заклучено.</p>';continue;}$media_ready=!$online||self::regional_media_ready((int)$season->id,(int)$cat->id,(int)$entry->id,(string)$entry->region);
                if($regional_video_stage){$player=ZMP_YouTube::video_player((int)$entry->id,$phase);if($player)echo '<h5>'.esc_html($phase_name).' — видео (може да се паузира и повтори)</h5>'.$player;if($online&&!$media_ready)echo '<p class="zmp-alert">Оценувањето останува заклучено додека YouTube не ги потврди сите задолжителни видеа.</p>';elseif($online&&!$player)echo '<p class="zmp-alert">Видеото сè уште не е подготвено за безбедно оценување.</p>';}
                else { /* Physical/non-regional stages remain scoreable without a video. */ }
                $assessment=$wpdb->get_row($wpdb->prepare("SELECT id,total_points,private_notes,status FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s AND juror_user_id=%d",$season->id,$cat->id,$entry->id,$phase,$uid));
                $final=$wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase=%s",$season->id,$cat->id,$entry->id,$phase));
                $disqualified=self::is_disqualified((int)$season->id,(int)$cat->id,(int)$entry->id,$phase);
                if($disqualified){$evaluation=array('key'=>'disqualified','label'=>'Дисквалификувана во оваа категорија и натпреварувачко ниво');}
                elseif($final&&$final->status==='confirmed'){$evaluation=array('key'=>'evaluated','label'=>'Оценета — конечниот резултат е потврден');}
                elseif($final&&$final->status==='pending_review'){$evaluation=array('key'=>'awaiting_decision','label'=>'Чека повторна конечна потврда');}
                elseif($assessment){$evaluation=array('key'=>'awaiting_decision','label'=>'Оценета — чека конечна потврда');}
                elseif(!empty($document_state['key'])&&$document_state['key']!=='ready_for_review'){$evaluation=array('key'=>'waiting_documents','label'=>'Чека одобрување на документацијата');}
                elseif($online&&!$media_ready){$evaluation=array('key'=>'waiting_documents','label'=>'Чека YouTube потврда за видеата');}
                else{$evaluation=array('key'=>'not_evaluated','label'=>'Подготвена — неоценета');}
                echo '<h5>'.esc_html($phase_name).' <span class="zmp-doc-state zmp-doc-state--'.esc_attr($evaluation['key']).'">'.esc_html($evaluation['label']).'</span></h5>';
                if($disqualified){echo '<p class="zmp-alert">Пријавата е дисквалификувана само за оваа категорија и на ова натпреварувачко ниво. Оценување и повторно финализирање не се достапни.</p>';continue;}
                if($document_state['key']!=='ready_for_review'){echo '<p>Бодувањето ќе се отклучи по одобрување на сите задолжителни документи.</p>';continue;}
                if($online&&!$media_ready){echo '<p>Бодувањето ќе се отклучи откако YouTube ќе ги потврди сите задолжителни видеа.</p>';continue;}if($online&&!$player){echo '<p>Безбедниот видео-плеер не е достапен; оценувањето е заклучено.</p>';continue;}
                if($assessment&&$assessment->private_notes!=='')echo '<div class="zmp-private-jury-note"><strong>Мои приватни забелешки:</strong><p>'.nl2br(esc_html($assessment->private_notes)).'</p></div>';
                if(!$final){
                    if($assessment){echo '<p>Оценката е веќе поднесена и не може директно да се презапише.</p><form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="request_correction"><input type="hidden" name="category_id" value="'.(int)$cat->id.'"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="phase" value="'.esc_attr($phase).'"> <label>Барање за исправка (најмалку 30 знаци)<textarea name="correction_reason" minlength="30" required></textarea></label><button class="button">Испрати барање до модераторите</button></form>';}
                    else {
                        echo '<form method="post" class="zmp-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="submit_assessment"><input type="hidden" name="category_id" value="'.(int)$cat->id.'"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="phase" value="'.esc_attr($phase).'">';
                        foreach($criteria as $criterion)echo '<label>'.esc_html($criterion->criterion_name).' (0–'.esc_html(number_format((float)$criterion->max_points,2)).')<input type="number" name="scores['.(int)$criterion->id.']" min="0" max="'.esc_attr($criterion->max_points).'" step="0.01" required></label>';
                        echo '<label>Фактичко образложение на оценките (најмалку 30 знаци)<textarea name="rationale" minlength="30" required></textarea></label><label>Мои приватни забелешки (видливи само за авторот и овластени модератори во истрага)<textarea name="private_notes" rows="3"></textarea></label><button class="button button-primary">Поднеси оценка</button></form>';
                    }
                    echo '<form method="post" class="zmp-form zmp-discipline-form">';wp_nonce_field('zmp_category_jury');echo '<input type="hidden" name="zmp_jury_action" value="disqualify_entry"><input type="hidden" name="category_id" value="'.(int)$cat->id.'"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="phase" value="'.esc_attr($phase).'"><label>Образложение за дисквалификација (најмалку 30 знаци)<textarea name="discipline_reason" minlength="30" required></textarea></label><button class="button button-secondary">Дисквалификувај само во оваа категорија и на ова натпреварувачко ниво</button></form>';
                }else{echo '<p>Оценката е потврдена. Одобрена корекција повторно ќе ја испрати на конечна потврда.</p>';}
            }
            echo '</article>';
        }
    }

    private static function render_results($season,$category_id) {
        global $wpdb;
        $restricted=self::is_restricted_juror();
        $state_eligible_ids=self::state_eligible_entry_ids((int)$season->id,(int)$category_id);
        $region=isset($_GET['zmp_jury_region'])&&ZMP_Regions::valid_region(wp_unslash($_GET['zmp_jury_region']))?sanitize_key(wp_unslash($_GET['zmp_jury_region'])):'';
        $municipality=sanitize_text_field(wp_unslash($_GET['zmp_jury_municipality']??''));
        $school=sanitize_text_field(wp_unslash($_GET['zmp_jury_school']??''));
        $geo='';$geo_args=array();
        if($region!==''){$geo.=' AND e.region=%s';$geo_args[]=$region;}
        if($municipality!==''){$geo.=' AND e.municipality=%s';$geo_args[]=$municipality;}
        if($school!==''){$geo.=' AND e.school_name LIKE %s';$geo_args[]='%' . $wpdb->esc_like($school) . '%';}

        $ready_args=array_merge(array((int)$season->id,(int)$category_id),$geo_args);
        $ready_sql="SELECT a.entry_id,a.phase,AVG(a.total_points) AS jury_average,COUNT(*) AS submitted,e.participant_name,e.school_name,e.region,e.municipality
            FROM {$wpdb->prefix}zmp_jury_assessments a
            JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=a.entry_id AND e.season_id=a.season_id AND e.category_id=a.category_id
            WHERE a.season_id=%d AND a.category_id=%d AND a.status='submitted'
            AND (
                a.phase NOT IN ('regional','municipal')
                OR (a.phase='regional' AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}zmp_jury_regional_assignments ra
                    WHERE ra.season_id=a.season_id AND ra.category_id=a.category_id
                      AND ra.region=e.region AND ra.juror_user_id=a.juror_user_id
                ))
                OR (a.phase='municipal' AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}zmp_jury_regional_assignments ra
                    WHERE ra.season_id=a.season_id AND ra.category_id=a.category_id
                      AND ra.region=e.region AND ra.juror_user_id=a.juror_user_id
                      AND EXISTS (SELECT 1 FROM {$wpdb->prefix}zmp_jury_assignments ca WHERE ca.season_id=ra.season_id AND ca.category_id=ra.category_id AND ca.juror_user_id=ra.juror_user_id)
                ))
            )$geo
            AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}zmp_category_final_results f WHERE f.season_id=a.season_id AND f.category_id=a.category_id AND f.entry_id=a.entry_id AND f.phase=a.phase AND f.status='confirmed')
            GROUP BY a.entry_id,a.phase,e.participant_name,e.school_name,e.region,e.municipality
            ORDER BY a.phase,e.participant_name";
        $ready=$wpdb->get_results($wpdb->prepare($ready_sql,$ready_args));
        echo '<h4>Подготвени за конечна одлука</h4>';
        $visible_ready=0;
        foreach((array)$ready as $item){
            if($restricted){
                if(!self::phase_entry_authorized((int)$season->id,(int)$category_id,(int)$item->entry_id,(string)$item->phase,get_current_user_id()))continue;
            }
            $assigned=self::phase_juror_count((int)$season->id,(int)$category_id,(string)$item->phase,(string)$item->region);
            if($assigned<1||(int)$item->submitted!==$assigned)continue;
            $visible_ready++;
            $max=self::maximum_points($season->id,$category_id);
            echo '<article class="zmp-card"><h5>'.esc_html(self::phase_label($item->phase)).' — '.esc_html($item->participant_name).' ('.esc_html($item->school_name).')</h5><p>Просек на сите '.(int)$assigned.' членови: '.esc_html(number_format((float)$item->jury_average,2)).' од '.esc_html(number_format($max,2)).' бодови.</p><form method="post" class="zmp-form">';
            wp_nonce_field('zmp_category_jury');
            echo '<input type="hidden" name="zmp_jury_action" value="finalize_result"><input type="hidden" name="category_id" value="'.(int)$category_id.'"><input type="hidden" name="entry_id" value="'.(int)$item->entry_id.'"><input type="hidden" name="phase" value="'.esc_attr($item->phase).'">'.(self::next_stage_exists((int)$season->id,(int)$category_id,(string)$item->phase)?'<label><input type="checkbox" name="qualified_next_stage" value="1"> Потврди пласман во следното активно натпреварувачко ниво</label>':'').'<label>Конечни бодови (оставете празно за просек; измена бара дополнително образложение)<input type="number" name="final_points" min="0" max="'.esc_attr($max).'" step="0.01"></label><label>Фактичка основа / доказ за одлуката (најмалку 30 знаци)<textarea name="decision_basis" minlength="30" required></textarea></label><label>Образложение зошто исходот е правичен (најмалку 30 знаци)<textarea name="decision_note" minlength="30" required></textarea></label><button class="button button-primary">Потврди конечен резултат</button></form></article>';
        }
        if(!$visible_ready)echo '<p>Нема оценки што чекаат конечна одлука во вашиот опсег.</p>';

        echo '<form method="get" class="zmp-filter-bar">';
        foreach((array)$_GET as $k=>$v){if(in_array($k,array('zmp_jury_region','zmp_jury_municipality','zmp_jury_school'),true)||!is_scalar($v))continue;echo '<input type="hidden" name="'.esc_attr(sanitize_key($k)).'" value="'.esc_attr(sanitize_text_field(wp_unslash($v))).'">';}
        echo '<label>Регион <select name="zmp_jury_region"><option value="">Сите региони</option>';
        foreach(ZMP_Regions::regions() as $rk=>$rd)echo '<option value="'.esc_attr($rk).'" '.selected($region,$rk,false).'>'.esc_html($rd['label']).'</option>';
        echo '</select></label><label>Општина <input name="zmp_jury_municipality" value="'.esc_attr($municipality).'"></label><label>Училиште <input name="zmp_jury_school" value="'.esc_attr($school).'"></label><button class="button">Филтрирај</button></form>';

        $rows_args=array_merge(array((int)$season->id,(int)$category_id),$geo_args);
        $rows=$wpdb->get_results($wpdb->prepare("SELECT r.*,e.participant_name,e.school_name,e.region,e.municipality,u.display_name
            FROM {$wpdb->prefix}zmp_category_final_results r
            JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id AND e.season_id=r.season_id AND e.category_id=r.category_id
            LEFT JOIN {$wpdb->users} u ON u.ID=r.decided_by
            WHERE r.season_id=%d AND r.category_id=%d AND r.status='confirmed'$geo
            ORDER BY r.phase,r.final_points DESC",$rows_args));
        $visible_rows=array();
        foreach((array)$rows as $r){
            if($restricted){
                if(!self::phase_entry_authorized((int)$season->id,(int)$category_id,(int)$r->entry_id,(string)$r->phase,get_current_user_id()))continue;
            }
            $visible_rows[]=$r;
        }
        echo '<h4>Потврдени резултати и конечни одлуки</h4>';
        if(!$visible_rows){echo '<p>Нема потврдени резултати во вашиот опсег.</p>';return;}
        echo '<div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Натпреварувачко ниво</th><th>Учесник</th><th>Регион</th><th>Општина</th><th>Училиште</th><th>Просек</th><th>Конечни бодови</th><th>Награда</th><th>Одлучил</th><th>Образложение</th></tr></thead><tbody>';
        foreach($visible_rows as $r)echo '<tr><td>'.esc_html(self::phase_label($r->phase)).'</td><td>'.esc_html($r->participant_name).'</td><td>'.esc_html(ZMP_Regions::label($r->region)).'</td><td>'.esc_html($r->municipality).'</td><td>'.esc_html($r->school_name).'</td><td>'.esc_html(number_format((float)$r->jury_average,2)).'</td><td>'.esc_html(number_format((float)$r->final_points,2)).'</td><td>'.esc_html(self::AWARDS[$r->award_key]??'Без награда').'</td><td>'.esc_html($r->display_name).'</td><td>'.nl2br(esc_html($r->decision_note)).'</td></tr>';
        echo '</tbody></table></div>';
    }
    private static function category_enabled($season_id,$category_id){global $wpdb;return (bool)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_categories c ON c.id=sc.category_id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND c.is_active=1 AND c.category_code<>'knowledge_test'",$season_id,$category_id));}
    private static function maximum_points($season_id,$category_id){global $wpdb;return (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(max_points),0) FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id=%d AND category_id=%d AND is_active=1",$season_id,$category_id));}
    private static function award_for($season_id,$category_id,$points){global $wpdb;$rows=$wpdb->get_results($wpdb->prepare("SELECT award_key,min_points FROM {$wpdb->prefix}zmp_category_awards WHERE season_id=%d AND category_id=%d ORDER BY min_points DESC",$season_id,$category_id));foreach($rows as $r)if(array_key_exists((string)$r->award_key,self::AWARDS)&&(float)$points>=(float)$r->min_points)return $r->award_key;return '';}
    private static function decimal($value){$raw=trim((string)$value);if($raw===''||!is_numeric($raw))return false;$n=(float)$raw;return is_finite($n)?round($n,2):false;}
    private static function audit($season_id,$category_id,$entry_id,$action,array $details){global $wpdb;return $wpdb->insert($wpdb->prefix.'zmp_jury_audit',array('season_id'=>(int)$season_id,'category_id'=>(int)$category_id,'entry_id'=>(int)$entry_id,'actor_user_id'=>get_current_user_id(),'action'=>sanitize_key($action),'details'=>wp_json_encode($details),'created_at'=>current_time('mysql')));}
    /** Serialize stage mutations against moderator stage closure. */
    private static function acquire_stage_lock($season_id,$category_id,$phase){
        global $wpdb;
        if(self::$active_stage_lock!=='')return;
        $name='zmp_stage_'.substr(hash('sha256',get_current_blog_id().'|'.$season_id.'|'.$category_id.'|'.$phase),0,52);
        $acquired=$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)',$name));
        if((int)$acquired!==1)self::redirect('stage_busy');
        self::$active_stage_lock=$name;
    }

    public static function shutdown_release_stage_lock(){ self::release_stage_lock(true); }

    private static function release_stage_lock($is_shutdown=false){
        global $wpdb;
        if(self::$active_stage_lock==='')return;
        $name=self::$active_stage_lock;
        $released=$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$name));
        if($released!==null){self::$active_stage_lock='';return;}
        if($is_shutdown){error_log('ZMP: stage lock release failed; request shutdown will close the database session.');return;}
        error_log('ZMP: stage lock release failed; retrying during request shutdown.');
    }

    private static function redirect($status){self::release_stage_lock();$url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_jury_status',sanitize_key($status),$url));exit;}
    public static function audit_external($season_id,$category_id,$entry_id,$action,$details){self::audit(absint($season_id),absint($category_id),absint($entry_id),sanitize_key($action),(array)$details);}
    private static function render_notice(){if(empty($_GET['zmp_jury_status']))return;$messages=array('stage_closed'=>'Натпреварувачкото ниво е затворено.','stage_already_closed'=>'Натпреварувачкото ниво веќе е затворено.','stage_disabled'=>'Натпреварувачкото ниво не е активно за категоријата.','previous_stage_open'=>'Претходното натпреварувачко ниво мора прво да се затвори.','not_qualified'=>'Учесникот нема потврдена одлука за пласман во ова натпреварувачко ниво.','entry_scope_invalid'=>'Пријавата не припаѓа на избраната категорија и година.','municipal_stage_skipped'=>'Општинското натпреварувачко ниво не се одржува во оваа општина бидејќи нема доволно пријави.','stage_busy'=>'Промена за ова натпреварувачко ниво веќе е во тек. Почекајте и обидете се повторно.','stage_close_invalid'=>'Потребно е да изберете активно натпреварувачко ниво и да внесете образложение од најмалку 30 знаци.','stage_decisions_incomplete'=>'Сите подобни пријави мора да имаат потврден резултат или дисквалификација пред затворање.','stage_corrections_pending'=>'Не може да се затвори натпреварувачко ниво додека има нерешени барања за исправка.','saved'=>'Промените се зачувани.','assessment_saved'=>'Оценката е поднесена и влезе во ревизорската евиденција.','assessment_exists'=>'Оценката веќе е поднесена. За исправка испратете барање до модераторите; директно презапишување не е дозволено.','result_confirmed'=>'Конечниот резултат е потврден со образложение.','correction_requested'=>'Барањето за исправка е испратено до модераторите преку допис.','correction_pending'=>'Веќе има барање за исправка што чека одлука.','correction_delivery_failed'=>'Барањето не е испратено бидејќи дописот до модераторите не можеше да се зачува.','correction_decided'=>'Одлуката за исправка е евидентирана.','correction_invalid'=>'Проверете ја оценката, натпреварувачкото ниво и образложението.','documents_not_approved'=>'Оценувањето е заклучено додека задолжителните документи не бидат прегледани и одобрени.','media_not_ready'=>'Оценувањето е заклучено додека сите задолжителни видеа не бидат обработени и проверени.','document_saved'=>'Одлуката за документ е зачувана, а наставникот е известен преку порталот.','document_invalid'=>'Изберете одлука и внесете конкретна причина за одбивање (најмалку 10 знаци).','document_save_error'=>'Одлуката за документот не е зачувана.','invalid_scope'=>'Изберете активна категорија и натпреварувачка година.','invalid_criterion'=>'Називот и максималните бодови на критериумот се задолжителни.','criteria_locked'=>'Критериумите се заклучени откако започнало оценувањето.','jury_locked'=>'Составот на жирито е заклучен по поднесување оценка.','awards_locked'=>'Праговите на наградите се заклучени по првата конечна одлука.','awards_not_configured'=>'Поставете ги сите четири прагови за награди пред конечна одлука.','invalid_juror'=>'Изберете член со улога Испитна комисија.','invalid_awards'=>'Праговите мора да бидат валидни, во рамките на максимумот и строго опаѓачки.','rationale_required'=>'Оценувањето бара образложение од најмалку 30 знаци.','decision_rationale_required'=>'Комисијата мора да внесе конкретна фактичка основа и посебно образложение (најмалку 30 знаци во секое поле).','invalid_entry_phase'=>'Пријавата или натпреварувачкото ниво не е достапно.','entry_disqualified'=>'Учесникот е дисквалификуван во оваа категорија и натпреварувачко ниво.','already_disqualified'=>'Учесникот веќе е дисквалификуван во ова натпреварувачко ниво.','discipline_after_final'=>'Дисквалификација не може да се внесе по конечната одлука.','result_locked'=>'Оценувањето е заклучено по конечна одлука.','no_criteria'=>'Прво внесете критериуми за категоријата.','invalid_score'=>'Секој резултат мора да биде во опсегот на критериумот.','assessments_incomplete'=>'Секој доделен член мора да поднесе оценка пред потврда.','invalid_final_score'=>'Конечните бодови ја надминуваат валидната скала.','save_error'=>'Промените не се зачувани. Проверете ја базата.');$key=sanitize_key(wp_unslash($_GET['zmp_jury_status']));if(isset($messages[$key]))echo '<p class="zmp-alert">'.esc_html($messages[$key]).'</p>';}
}
