<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Support {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'handle_commission_question_actions'), 1);
        // Кориснички профил полиња (Општина и Училиште)
        add_action('show_user_profile', array($this, 'render_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'render_user_profile_fields'));
        // Validate and commit the ZMP teacher payload before other callbacks
        // persist the separate school-profile fields from this same form.
        add_action('personal_options_update', array($this, 'save_user_profile_fields'), 9);
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'), 9);

        // AJAX акции
        add_action('wp_ajax_zmp_submit_incident', array($this, 'ajax_submit_incident'));
        add_action('wp_ajax_zmp_reset_student_test', array($this, 'ajax_reset_student_test'));
        add_action('wp_ajax_zmp_reply_incident', array($this, 'ajax_reply_incident'));
        add_action('wp_ajax_zmp_submit_complaint', array($this, 'ajax_submit_complaint'));
        add_action('wp_ajax_zmp_testator_decide_complaint', array($this, 'ajax_testator_decide_complaint'));
        add_action('wp_ajax_zmp_disqualify_target', array($this, 'ajax_disqualify_target'));
        add_action('wp_ajax_zmp_test_db_connection', array($this, 'ajax_test_db_connection'));
        add_action('wp_ajax_zmp_moderator_live_students', array($this, 'ajax_moderator_live_students'));
    }

    /** Save question bank entries and the 20-question point distribution. */
    public function handle_commission_question_actions() {
        if (empty($_POST['zmp_commission_question_action'])) { return; }
        $user=wp_get_current_user();
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('zmp_testator_cap') && !current_user_can('view_zmp_testator_dashboard') && !in_array('zmp_testator',(array)$user->roles,true)) || (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror())) { wp_die('Немате овластување.', '', array('response'=>403)); }
        check_admin_referer('zmp_commission_question_action');
        global $wpdb;
        $action_raw = wp_unslash($_POST['zmp_commission_question_action']);
        $action = is_scalar($action_raw) ? sanitize_key((string)$action_raw) : '';
        if (!empty($_POST['zmp_load_mix'])) {
            $target = add_query_arg(array('mix_phase'=>sanitize_key(wp_unslash($_POST['mix_phase'] ?? 'school')),'mix_grade'=>absint($_POST['mix_grade'] ?? 6)), wp_get_referer() ?: home_url('/'));
            wp_safe_redirect($target); exit;
        }
        if ($action === 'save_mix') {
            $phase_raw = wp_unslash($_POST['mix_phase'] ?? 'school'); $phase = is_scalar($phase_raw) ? sanitize_key((string)$phase_raw) : '';
            $grade_value = wp_unslash($_POST['mix_grade'] ?? ''); $grade_raw = is_scalar($grade_value) ? filter_var($grade_value, FILTER_VALIDATE_INT) : false;
            $grade = ($grade_raw === false) ? 0 : (int)$grade_raw;
            $counts = array(); $total = 0;
            foreach ((array)($_POST['mix_count'] ?? array()) as $point => $count) {
                $point = filter_var((string)$point, FILTER_VALIDATE_INT);
                $count_value = wp_unslash($count); $count = is_scalar($count_value) ? filter_var($count_value, FILTER_VALIDATE_INT) : false;
                if ($point === false || $count === false || $point < 1 || $point > 20 || $count < 0 || $count > 20) { wp_die('Бодовите и бројот на прашања мора да бидат цели броеви од 0 до 20.'); }
                if ($count > 0) { $counts[(int)$point] = (int)$count; $total += (int)$count; }
            }
            if (!in_array($phase, array('school','municipal','regional','state'), true) || !in_array($grade, array(6,7,8,9), true) || $total !== 20 || count($counts) < 2) { wp_die('Распределбата мора да содржи точно 20 прашања од најмалку две бодовни нивоа, со важечка натпреварувачко ниво и одделение.'); }
            update_option('zmp_question_mix_' . absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")) . '_' . $phase . '_' . $grade, $counts, false);
        } elseif ($action === 'add_question') {
            $grade_value = wp_unslash($_POST['grade'] ?? ''); $grade_raw = is_scalar($grade_value) ? filter_var($grade_value, FILTER_VALIDATE_INT) : false; $grade = ($grade_raw === false) ? 0 : (int)$grade_raw;
            $phase_value = wp_unslash($_POST['phase'] ?? ''); $phase = is_scalar($phase_value) ? sanitize_key((string)$phase_value) : '';
            $points_value = wp_unslash($_POST['points'] ?? ''); $points_raw = is_scalar($points_value) ? filter_var($points_value, FILTER_VALIDATE_INT) : false; $points = ($points_raw === false) ? 0 : (int)$points_raw;
            $mk_value = wp_unslash($_POST['question_text_mk'] ?? ''); $sq_value = wp_unslash($_POST['question_text_sq'] ?? '');
            $mk = is_scalar($mk_value) ? sanitize_textarea_field((string)$mk_value) : ''; $sq = is_scalar($sq_value) ? sanitize_textarea_field((string)$sq_value) : '';
            $options = array(); $raw_mk = (array)($_POST['option_mk'] ?? array()); $raw_sq = (array)($_POST['option_sq'] ?? array());
            for ($i=0; $i<4; $i++) { $option_mk=wp_unslash($raw_mk[$i] ?? ''); $option_sq=wp_unslash($raw_sq[$i] ?? ''); $options[] = array('text_mk'=>is_scalar($option_mk)?sanitize_text_field((string)$option_mk):'', 'text_sq'=>is_scalar($option_sq)?sanitize_text_field((string)$option_sq):''); }
            $correct_value = wp_unslash($_POST['correct_option_index'] ?? ''); $correct_raw = is_scalar($correct_value) ? filter_var($correct_value, FILTER_VALIDATE_INT) : false; $correct = ($correct_raw === false) ? -1 : (int)$correct_raw;
            if (!in_array($grade,array(6,7,8,9),true) || !in_array($phase,array('all','school','municipal','regional','state'),true) || $points<1 || $points>20 || $mk==='' || $sq==='' || $correct<0 || $correct>3 || count(array_filter($options,function($o){return $o['text_mk']!=='' && $o['text_sq']!=='';}))!==4) { wp_die('Проверете ги задолжителните полиња и бодовите (1–20).'); }
            $audio_value=wp_unslash($_POST['audio_url'] ?? ''); $image_value=wp_unslash($_POST['image_url'] ?? '');
            $wpdb->insert($wpdb->prefix.'zmp_questions', array('grade'=>$grade,'phase'=>$phase,'question_text_mk'=>$mk,'question_text_sq'=>$sq,'question_type'=>'multiple_choice','audio_url'=>is_scalar($audio_value)?esc_url_raw((string)$audio_value):'','image_url'=>is_scalar($image_value)?esc_url_raw((string)$image_value):'','options_json'=>wp_json_encode($options),'correct_option_index'=>$correct,'points'=>$points));
            if (!$wpdb->insert_id) { wp_die('Прашањето не беше зачувано. Проверете ја базата.'); }
        } else { wp_die('Непозната акција.'); }
        $redirect = add_query_arg('zmp_question_saved','1',wp_get_referer() ?: home_url('/'));
        if ($action === 'save_mix') { $redirect = add_query_arg(array('mix_phase'=>$phase,'mix_grade'=>$grade), $redirect); }
        wp_safe_redirect($redirect); exit;
    }

    /** Render owner-scoped school profile and school/subject/grade assignments. */
    public function render_user_profile_fields($user) {
        if (!current_user_can('edit_user', (int)$user->ID)) { return; }
        global $wpdb;
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d ORDER BY id ASC LIMIT 1", (int)$user->ID));
        $roles = array_values((array)$user->roles);
        $president_self_setup = (int)$user->ID === get_current_user_id()
            && in_array('zmp_teacher', $roles, true)
            && ZMP_Mentor_School_Profile::has_role_stack((int)$user->ID, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && user_can((int)$user->ID, 'view_zmp_president_dashboard');
        if (!$mentor && in_array('zmp_president', $roles, true) && !$president_self_setup) { return; }
        if (!$mentor && !$president_self_setup && !in_array('zmp_teacher', $roles, true)
            && !user_can((int)$user->ID, 'zmp_teacher_cap') && !user_can((int)$user->ID, 'view_zmp_teacher_dashboard')) { return; }
        $school = $mentor ? (string)$mentor->school_name : (string)get_user_meta($user->ID, 'zmp_school_name', true);
        $municipality = $mentor ? (string)$mentor->municipality : (string)get_user_meta($user->ID, 'zmp_municipality', true);
        $phone = $mentor ? (string)$mentor->phone : (string)get_user_meta($user->ID, 'zmp_phone', true);
        $subjects = ZMP_Mentor_Subjects::approved_subjects();
        if (is_wp_error($subjects)) {
            echo '<h2>' . esc_html__('ЗМП Натпревари — училишта и наставни предмети', 'zmp-competitions') . '</h2>';
            echo '<div class="notice notice-error" role="alert"><p>' . esc_html__('Регистарот на предмети моментално не е достапен. Училишните и предметните промени се привремено оневозможени.', 'zmp-competitions') . '</p></div>';
            return;
        }
        $schools = $mentor ? (array)ZMP_Mentor_Schools::for_mentor((int)$mentor->id) : array();
        $assignments = ZMP_Mentor_Subjects::teacher_assignments((int)$user->ID);
        ?>
        <h2>ЗМП Натпревари — училишта и наставни предмети</h2>
        <input type="hidden" name="zmp_teacher_profile_present" value="1">
        <table class="form-table">
            <tr><th><label for="zmp_school_name">Примарно училиште *</label></th><td><input type="text" name="zmp_school_name" id="zmp_school_name" value="<?php echo esc_attr($school); ?>" class="regular-text" required maxlength="255" <?php echo $mentor ? 'readonly aria-readonly="true"' : ''; ?>><p class="description">Регионот се одредува серверски од општината. Постар профил го задржува истиот примарен запис; ново училиште додадете подолу.</p></td></tr>
            <tr><th><label for="zmp_municipality">Општина / град *</label></th><td><input type="text" name="zmp_municipality" id="zmp_municipality" value="<?php echo esc_attr($municipality); ?>" class="regular-text" required maxlength="150" <?php echo $mentor ? 'readonly aria-readonly="true"' : ''; ?>><p class="description">Општината мора да овозможи автоматско одредување регион.</p></td></tr>
            <tr><th><label for="zmp_phone">Телефон за контакт</label></th><td><input type="text" name="zmp_phone" id="zmp_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" maxlength="50"></td></tr>
        </table>
        <h3>Активни училишта</h3>
        <?php if ($schools): ?><div class="zmp-teacher-profile-schools"><ul><?php foreach ($schools as $school_row): ?><li><?php echo esc_html($school_row->school_name.' — '.$school_row->municipality.' · '.ZMP_Regions::label($school_row->region).(!empty($school_row->is_primary)?' (примарно)':'')); ?><?php if (!empty($school_row->region_is_override)): ?> — одобрен регионален исклучок<?php endif; ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <style id="zmp-teacher-profile-school-layout">
            #profile-page fieldset.zmp-teacher-school-assignment{box-sizing:border-box;min-width:0;width:100%;margin:18px 0 0;padding:12px 16px;border:1px solid #c3c4c7;border-radius:4px;background:#fff}
            #profile-page fieldset.zmp-teacher-school-assignment>legend{float:none;display:block;max-width:100%;margin:0 0 10px;padding:0 8px;line-height:1.45;white-space:normal;overflow-wrap:anywhere}
            #profile-page fieldset.zmp-teacher-subject-assignment{display:flex;flex-wrap:wrap;gap:8px 16px;padding:10px 14px;border-color:#dcdcde}
            #profile-page fieldset.zmp-teacher-subject-assignment>legend{float:none;display:block;flex:0 0 100%;max-width:100%;margin:0 0 4px;padding:0 6px;font-size:14px;line-height:1.4;white-space:normal;overflow-wrap:anywhere}
            #profile-page .zmp-teacher-grade-option{display:inline-flex;align-items:center;gap:5px;min-width:112px;margin:0;vertical-align:top;line-height:1.4}
            #profile-page .zmp-teacher-grade-option input[type=checkbox]{flex:0 0 auto;margin:0}
            @media screen and (max-width:640px){#profile-page fieldset.zmp-teacher-school-assignment{padding:10px}#profile-page fieldset.zmp-teacher-subject-assignment{gap:8px 10px}#profile-page .zmp-teacher-grade-option{min-width:calc(50% - 10px)}}
            #zmp-teacher-new-school-fields .zmp-teacher-new-school-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
            #zmp-teacher-new-school-fields .zmp-teacher-new-school-fields>label{display:block;min-width:0;margin:0}
            #zmp-teacher-new-school-fields .zmp-teacher-new-school-fields input{display:block;width:100%;max-width:100%;min-width:0;margin-top:6px;box-sizing:border-box}
            @media screen and (max-width:640px){#zmp-teacher-new-school-fields .zmp-teacher-new-school-fields{grid-template-columns:minmax(0,1fr)}}
        </style>
        <?php if ($subjects): ?>
            <input type="hidden" name="zmp_teacher_assignments_present" value="1">
            <?php if (!$mentor): ?><fieldset class="zmp-teacher-school-assignment"><legend><strong>Примарно училиште — наставни предмети</strong></legend><?php $this->render_teacher_subject_grade_controls('primary',$subjects,array()); ?></fieldset><?php endif; ?>
            <?php foreach ($schools as $school_row): $school_id=(int)$school_row->id; ?><fieldset class="zmp-teacher-school-assignment"><legend><strong><?php echo esc_html($school_row->school_name); ?></strong> — <?php echo esc_html($school_row->municipality.' · '.ZMP_Regions::label($school_row->region)); ?></legend><?php $this->render_teacher_subject_grade_controls((string)$school_id,$subjects,(array)($assignments[$school_id]??array())); ?></fieldset><?php endforeach; ?>
            <fieldset id="zmp-teacher-new-school-fields" class="zmp-teacher-school-assignment"><legend><strong>Ново училиште</strong></legend>
                <div class="zmp-teacher-new-school-fields">
                    <label>Име на училиште<input type="text" name="zmp_profile_new_school_name" maxlength="255"></label>
                    <label>Општина<input type="text" name="zmp_profile_new_school_municipality" maxlength="150" list="zmp-profile-municipalities"></label>
                </div>
                <datalist id="zmp-profile-municipalities"><?php foreach (ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist>
                <?php $this->render_teacher_subject_grade_controls('new',$subjects,array()); ?>
                <p class="description">Ако внесете училиште, задолжително изберете одобрен предмет и најмалку едно одделение/година.</p>
            </fieldset>
            <p class="description">За секое активно училиште изберете најмалку еден предмет и едно или повеќе одделенија/години. Нов предмет се предлага преку предвидениот тек во следната фаза.</p>
        <?php else: ?>
            <p>За секое училиште е зачуван општински регион. Каталогот на одобрени предмети е празен; ќе можете да ги поврзете предметите и одделенијата откако ќе се одобрат ставки во заедничкиот каталог. Училишта сепак може да се додадат.</p>
            <fieldset id="zmp-teacher-new-school-fields" class="zmp-teacher-school-assignment"><legend><strong>Ново училиште</strong></legend><div class="zmp-teacher-new-school-fields"><label>Име на училиште<input type="text" name="zmp_profile_new_school_name" maxlength="255"></label><label>Општина<input type="text" name="zmp_profile_new_school_municipality" maxlength="150" list="zmp-profile-municipalities-empty"></label></div><datalist id="zmp-profile-municipalities-empty"><?php foreach (ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist></fieldset>
        <?php endif; ?>
        <?php
    }

    private function render_teacher_subject_grade_controls($school_key, $subjects, $selected_by_subject) {
        $grade_keys = array_merge(array_map(function($n){return 'grade_'.$n;},range(1,9)),array_map(function($n){return 'year_'.$n;},range(1,4)));
        foreach ((array)$subjects as $subject) {
            $subject_id = (int)$subject->id;
            $selected = (array)($selected_by_subject[$subject_id] ?? array());
            echo '<fieldset class="zmp-teacher-subject-assignment"><legend>'.esc_html($subject->subject_name).'</legend>';
            foreach ($grade_keys as $grade_key) {
                $label = strpos($grade_key,'grade_')===0 ? substr($grade_key,6).' одделение' : substr($grade_key,5).' година';
                echo '<label class="zmp-teacher-grade-option"><input type="checkbox" name="assignments['.esc_attr($school_key).']['.$subject_id.'][]" value="'.esc_attr($grade_key).'" '.checked(in_array($grade_key,$selected,true),true,false).'> '.esc_html($label).'</label>';
            }
            echo '</fieldset>';
        }
    }
    /** Validate WordPress core profile nonce/capability and persist only this teacher's profile. */
    public function save_user_profile_fields($user_id) {
        $user_id = absint($user_id);
        if (!$user_id || !isset($_POST['zmp_teacher_profile_present'])) { return false; }
        global $wpdb;
        $transaction_active = false;
        $setup_lock_names = array();
        $release_setup_locks = function() use (&$setup_lock_names, $wpdb) {
            $released_all = true;
            foreach (array_reverse($setup_lock_names) as $lock_name) {
                $wpdb->last_error = '';
                $released = $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
                if ($wpdb->last_error !== '' || (string)$released !== '1') { $released_all = false; }
            }
            $setup_lock_names = array();
            return $released_all;
        };
        $abort_profile = function($message, $title = '', $args = array()) use (&$transaction_active, $release_setup_locks, $user_id, $wpdb) {
            if ($transaction_active) {
                $wpdb->query('ROLLBACK');
                $transaction_active = false;
                wp_cache_delete($user_id, 'user_meta');
            }
            if (!$release_setup_locks()) { $message .= ' Заклучувањето не е потврдено како ослободено; освежете ја страницата и проверете го профилот пред повторен обид.'; }
            wp_die($message, $title, $args);
        };
        if (!current_user_can('edit_user', $user_id)) { return false; }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'update-user_' . $user_id) && !wp_verify_nonce($nonce, 'update-user')) { $abort_profile('Безбедносната проверка не помина.', '', array('response'=>403)); }
        $user = get_userdata($user_id);
        if (!$user) { return false; }
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d ORDER BY id ASC LIMIT 1", $user_id));
        $roles = array_values((array)$user->roles);
        $president_self_setup = $user_id === get_current_user_id()
            && in_array('zmp_teacher', $roles, true)
            && ZMP_Mentor_School_Profile::has_role_stack($user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && user_can($user_id, 'view_zmp_president_dashboard');
        if (!$mentor && in_array('zmp_president', $roles, true) && !$president_self_setup) {
            if (class_exists('ZMP_Debug')) { ZMP_Debug::log('warning','School profile first-time President setup denied.',array('outcome'=>'denied','error_code'=>'president_setup_not_owner','user_id'=>$user_id),'school_profile'); }
            return false;
        }
        if (!$mentor && !$president_self_setup && !in_array('zmp_teacher',$roles,true)
            && !user_can($user_id,'zmp_teacher_cap') && !user_can($user_id,'view_zmp_teacher_dashboard')) { return false; }
        // Check the catalog before any mentor, user-meta, primary-school, or
        // assignment writes. A valid empty catalog is still allowed; query
        // failure must not be treated as permission to create school records.
        $subjects = ZMP_Mentor_Subjects::approved_subjects();
        if (is_wp_error($subjects)) {
            $abort_profile(esc_html__('Регистарот на предмети моментално не е достапен. Училишните и предметните промени не се зачувани.', 'zmp-competitions'), 'Промените не се зачувани', array('response'=>503));
        }
        // A profile page can remain open while the approved-subject catalog changes.
        // Reject a stale form before any school/profile callback writes its payload.
        if (!empty($subjects) && !isset($_POST['zmp_teacher_assignments_present'])) {
            $abort_profile('Каталогот на предмети е ажуриран. Освежете го профилот, изберете предмет и одделение/година за секое активно училиште, па поднесете повторно.', 'Потребно е освежување на профилот', array('response'=>409));
        }
        $school = sanitize_text_field(wp_unslash($_POST['zmp_school_name'] ?? ''));
        $municipality = sanitize_text_field(wp_unslash($_POST['zmp_municipality'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['zmp_phone'] ?? ''));
        if (self::profile_text_length($school) < 3 || self::profile_text_length($school) > 255 || self::profile_text_length($municipality) < 2 || self::profile_text_length($municipality) > 150 || self::profile_text_length($phone) > 50) {
            $abort_profile('Внесете важечко примарно училиште, општина и телефон.', 'Невалиден профил', array('response'=>400));
        }
        if ($mentor && ZMP_Mentor_Schools::school_key($mentor->school_name,$mentor->municipality) !== ZMP_Mentor_Schools::school_key($school,$municipality)) {
            $abort_profile('Примарното училиште не може да се смени преку WordPress профилот. Додајте училиште во порталот или побарајте промена на регион преку допис.', 'Училиштето се управува преку порталот', array('response'=>409));
        }
        $existing_school = null;
        $region = ZMP_Regions::region_for_municipality($municipality);
        if (!$region || !ZMP_Regions::valid_region($region)) { $abort_profile('Регионот не може автоматски да се одреди од општината.', 'Невалидна општина', array('response'=>400)); }
        // The normalized value is also used by the shared first-time teacher
        // collision check below, even when the account is not a President.
        $normalized_email = strtolower(trim((string)$user->user_email));
        $own_transaction = false;
        if (!$mentor) {
            // Serialize all first-time writes by normalized email so teacher
            // and President creation cannot race into duplicate email rows.
            // President requests also take a stable user lock if email changes.
            $lock_names = array(
                'zmp_ps_e_' . substr(hash('sha256', $normalized_email), 0, 54),
            );
            if ($president_self_setup) { $lock_names[] = 'zmp_ps_u_' . (string)$user_id; }
            sort($lock_names, SORT_STRING);
            foreach ($lock_names as $lock_name) {
                $lock_acquired = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock_name));
                if ((string)$lock_acquired !== '1') {
                    $abort_profile('Профилот не може безбедно да се зачува во моментов. Обидете се повторно или побарајте помош од администратор.', 'Профилот не е зачуван', array('response'=>503));
                }
                $setup_lock_names[] = $lock_name;
            }
            // A concurrent submission from this same account may have finished
            // while this request waited. Reuse only its exact user mapping.
            $wpdb->last_error = '';
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d ORDER BY id ASC LIMIT 1", $user_id));
            if ($wpdb->last_error !== '') {
                $abort_profile('Не можеме да ја потврдиме постојната наставничка врска. Ништо не е зачувано; побарајте помош од администратор.', 'Профилот не е зачуван', array('response'=>503));
            }
            if (!$mentor && $president_self_setup) {
                $transaction_tables = array($wpdb->users, $wpdb->usermeta, $wpdb->prefix.'zmp_mentors',
                    $wpdb->prefix.'zmp_mentor_schools', ZMP_Mentor_School_Profile::table_name(),
                    $wpdb->prefix.'zmp_mentor_school_subjects', $wpdb->prefix.'zmp_mentor_school_subject_grades');
                $placeholders = implode(',', array_fill(0, count($transaction_tables), '%s'));
                $engine_rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})",
                    $transaction_tables
                ), ARRAY_A);
                $engines = array();
                foreach ((array)$engine_rows as $engine_row) { $engines[(string)$engine_row['TABLE_NAME']] = strtoupper((string)$engine_row['ENGINE']); }
                foreach ($transaction_tables as $transaction_table) {
                    if (($engines[$transaction_table] ?? '') !== 'INNODB') {
                        $abort_profile('Базата не ги поддржува безбедно сите промени во профилот. Ништо не е зачувано; побарајте помош од администратор.', 'Профилот не е зачуван', array('response'=>503));
                    }
                }
                if ($wpdb->query('START TRANSACTION') === false) {
                    $abort_profile('Промените не можеа безбедно да започнат.', 'Грешка', array('response'=>500));
                }
                $transaction_active = true;
                $own_transaction = true;
            }
        }
        // All ZMP teacher-profile writes, including existing mentors, must roll
        // back together if any later school/subject/grade validation fails.
        if (!$transaction_active) {
            $transaction_tables = array($wpdb->users, $wpdb->usermeta, $wpdb->prefix.'zmp_mentors',
                $wpdb->prefix.'zmp_mentor_schools', ZMP_Mentor_School_Profile::table_name(),
                $wpdb->prefix.'zmp_mentor_school_subjects', $wpdb->prefix.'zmp_mentor_school_subject_grades');
            $placeholders = implode(',', array_fill(0, count($transaction_tables), '%s'));
            $engine_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$placeholders})",
                $transaction_tables
            ), ARRAY_A);
            $engines = array();
            foreach ((array)$engine_rows as $engine_row) { $engines[(string)$engine_row['TABLE_NAME']] = strtoupper((string)$engine_row['ENGINE']); }
            foreach ($transaction_tables as $transaction_table) {
                if (($engines[$transaction_table] ?? '') !== 'INNODB') {
                    $abort_profile('Базата не ги поддржува безбедно сите промени во профилот. Ништо не е зачувано; побарајте помош од администратор.', 'Профилот не е зачуван', array('response'=>503));
                }
            }
            if ($wpdb->query('START TRANSACTION') === false) {
                $abort_profile('Промените не можеа безбедно да започнат.', 'Грешка', array('response'=>500));
            }
            $transaction_active = true;
            $own_transaction = true;
        }
        if ($mentor) {
            $school_key = ZMP_Mentor_Schools::school_key($school,$municipality);
            $existing_school = $wpdb->get_row($wpdb->prepare("SELECT region,region_is_override FROM {$wpdb->prefix}zmp_mentor_schools WHERE mentor_id=%d AND school_key=%s AND is_active=1", (int)$mentor->id, $school_key));
            if ($existing_school && !empty($existing_school->region_is_override) && ZMP_Regions::valid_region($existing_school->region)) { $region=$existing_school->region; }
            $saved = $wpdb->update($wpdb->prefix.'zmp_mentors', array('school_name'=>$school,'municipality'=>$municipality,'region'=>$region,'phone'=>$phone,'full_name'=>$user->display_name), array('id'=>(int)$mentor->id,'wp_user_id'=>$user_id));
            if ($saved === false) { $abort_profile('Наставничкиот профил не можеше да се зачува.', 'Грешка', array('response'=>500)); }
        } else {
            // Never infer a mentor-to-account link from email. A collision needs
            // explicit administrator review; do not silently create a duplicate.
            $email_owner = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE LOWER(TRIM(email))=LOWER(TRIM(%s)) LIMIT 1",
                $normalized_email
            ));
            if ($wpdb->last_error !== '') {
                $abort_profile('Не можеме безбедно да провериме дали наставничкиот запис веќе постои. Промените не се зачувани; побарајте помош од администратор.', 'Профилот не е зачуван', array('response'=>503));
            }
            if ($email_owner) {
                $abort_profile('Постои наставнички запис со оваа адреса. Не е поврзан автоматски; побарајте од администратор безбедно да ја провери и поврзе сметката.', 'Потребна е проверка од администратор', array('response'=>409));
            }
            $saved = $wpdb->insert($wpdb->prefix.'zmp_mentors', array('wp_user_id'=>$user_id,'full_name'=>$user->display_name,'email'=>$user->user_email,'phone'=>$phone,'school_name'=>$school,'municipality'=>$municipality,'region'=>$region,'login_token'=>wp_generate_password(32,false)));
            if (!$saved) { $abort_profile('Наставничкиот профил не можеше да се зачува.', 'Грешка', array('response'=>500)); }
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d ORDER BY id ASC LIMIT 1", $user_id));
        }
        $profile_meta = array('zmp_school_name'=>$school,'zmp_municipality'=>$municipality,'zmp_region'=>$region,'zmp_phone'=>$phone);
        foreach ($profile_meta as $meta_key=>$meta_value) {
            $wpdb->last_error = '';
            update_user_meta($user_id,$meta_key,$meta_value);
            if ($wpdb->last_error !== '') {
                $abort_profile('Наставничките податоци не можеа безбедно да се зачуваат. Промените не се потврдени.', 'Профилот не е зачуван', array('response'=>500));
            }
            $wpdb->last_error = '';
            // get_var() returns NULL for both a missing row and a stored empty string.
            // Read the row so an intentionally blank phone value can be verified.
            $stored_meta_row = $wpdb->get_row($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id DESC LIMIT 1",
                $user_id, $meta_key
            ));
            if ($wpdb->last_error !== '' || !is_object($stored_meta_row) || !property_exists($stored_meta_row, 'meta_value') || (string)$stored_meta_row->meta_value !== (string)$meta_value) {
                $abort_profile('Не можеме да ја потврдиме зачуваната наставничка информација. Промените не се потврдени.', 'Профилот не е зачуван', array('response'=>500));
            }
        }
        $primary_school_id = $mentor ? ZMP_Mentor_Schools::ensure_primary($mentor) : 0;
        if (!$primary_school_id) { $abort_profile('Примарното училиште не можеше да се евидентира.', 'Грешка', array('response'=>500)); }

        $new_school_name = sanitize_text_field(wp_unslash($_POST['zmp_profile_new_school_name'] ?? ''));
        $new_school_municipality = sanitize_text_field(wp_unslash($_POST['zmp_profile_new_school_municipality'] ?? ''));
        $has_new_name = $new_school_name !== '';
        $has_new_municipality = $new_school_municipality !== '';
        if ($has_new_name !== $has_new_municipality) { $abort_profile('За ново училиште внесете и име и општина.', 'Невалидно училиште', array('response'=>400)); }
        $catalog_has_subjects = !empty($subjects);
        $assignments_present = isset($_POST['zmp_teacher_assignments_present']);

        $raw_payload = isset($_POST['assignments']) && is_array($_POST['assignments']) ? wp_unslash($_POST['assignments']) : array();
        if (!is_array($raw_payload)) { $raw_payload=array(); }
        if (!$mentor || !$existing_school) {
            if (array_key_exists('primary',$raw_payload)) {
                if (array_key_exists((string)$primary_school_id,$raw_payload)) { $abort_profile('Дупликатна врска за примарното училиште.', 'Невалиден профил', array('response'=>400)); }
                $raw_payload[(string)$primary_school_id]=$raw_payload['primary'];
                unset($raw_payload['primary']);
            }
        } elseif (array_key_exists('primary',$raw_payload)) {
            $abort_profile('Невалидна врска за училиште.', 'Невалиден профил', array('response'=>400));
        }

        $new_school_id = 0;
        $own_transaction = $transaction_active;
        if ($has_new_name) {
            if (self::profile_text_length($new_school_name)<3 || self::profile_text_length($new_school_name)>255 || self::profile_text_length($new_school_municipality)<2 || self::profile_text_length($new_school_municipality)>150 || !ZMP_Regions::region_for_municipality($new_school_municipality)) {
                $abort_profile('Проверете го името и поддржаната општина на новото училиште.', 'Невалидно училиште', array('response'=>400));
            }
            $new_key=ZMP_Mentor_Schools::school_key($new_school_name,$new_school_municipality);
            if ($new_key===ZMP_Mentor_Schools::school_key($school,$municipality)) { $abort_profile('Примарното училиште веќе е внесено во профилот.', 'Дупликатно училиште', array('response'=>409)); }
            foreach (ZMP_Mentor_Schools::for_mentor((int)$mentor->id) as $existing_school_row) {
                if ($new_key===ZMP_Mentor_Schools::school_key($existing_school_row->school_name,$existing_school_row->municipality)) { $abort_profile('Училиштето веќе е внесено во профилот.', 'Дупликатно училиште', array('response'=>409)); }
            }
            if ($catalog_has_subjects && (!isset($raw_payload['new']) || !is_array($raw_payload['new']) || !$raw_payload['new'])) {
                $abort_profile('За новото училиште изберете најмалку еден одобрен предмет и одделение/година.', 'Недостасуваат наставни податоци', array('response'=>400));
            }
            if (!$own_transaction) {
                if ($wpdb->query('START TRANSACTION')===false) { $abort_profile('Промените не можеа безбедно да започнат.', 'Грешка', array('response'=>500)); }
                $own_transaction = true;
                $transaction_active = true;
            }
            $added = ZMP_Mentor_Schools::add_school((int)$mentor->id,$new_school_name,$new_school_municipality,false);
            if (is_wp_error($added)) { $abort_profile(esc_html($added->get_error_message()), 'Училиштето не е зачувано', array('response'=>400)); }
            $new_school_id=(int)$added;
            if (array_key_exists('new',$raw_payload)) {
                $raw_payload[(string)$new_school_id]=$raw_payload['new'];
                unset($raw_payload['new']);
            }
        } elseif (array_key_exists('new',$raw_payload)) {
            $abort_profile('За наставните податоци на ново училиште прво внесете име и општина.', 'Невалидно училиште', array('response'=>400));
        }

        if ($assignments_present) {
            $result=ZMP_Mentor_Subjects::save_teacher_assignments($user_id,$raw_payload,$own_transaction);
            if (is_wp_error($result)) {
                $abort_profile(esc_html($result->get_error_message()), 'Предметите не можеа да се зачуваат', array('response'=>400));
            }
        }
        if ($own_transaction && $wpdb->query('COMMIT')===false) { $abort_profile('Промените не можеа да се потврдат.', 'Грешка', array('response'=>500)); }
        if ($own_transaction) { $transaction_active = false; }
        if (!$release_setup_locks()) {
            wp_die('Профилот може да е зачуван, но ослободувањето на безбедносното заклучување не е потврдено. Освежете ја страницата и проверете го профилот пред повторен обид.', 'Потребна е проверка на профилот', array('response'=>503));
        }
        return true;
    }
    private static function profile_text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string)$value,'UTF-8'); }
        if (preg_match_all('/./us',(string)$value,$matches)) { return count($matches[0]); }
        return strlen((string)$value);
    }
    /**
     * Проверка на рокот од 24 часа според календарот на натпреварот или завршувањето на тестот
     */
    public static function can_submit_complaint($completed_at) {
        if (empty($completed_at)) {
            return false;
        }
        $completed_time = self::parse_site_datetime($completed_at);
        if (!$completed_time) { return false; }
        $diff = time() - $completed_time;
        return ($diff >= 0 && $diff <= 86400); // 24 часа = 86400 сек
    }

    /** Parse a local WordPress DATETIME value using the site's configured timezone. */
    private static function parse_site_datetime($value) {
        try {
            $date = new DateTimeImmutable((string)$value, wp_timezone());
            return $date->getTimestamp();
        } catch (Exception $error) {
            return 0;
        }
    }

    public static function get_complaint_time_remaining_label($completed_at) {
        if (empty($completed_at)) {
            return '';
        }
        $completed_time = self::parse_site_datetime($completed_at);
        if (!$completed_time) { return ''; }
        $diff = 86400 - (time() - $completed_time);
        if ($diff <= 0) {
            return 'Рокот од 24ч е истечен';
        }
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        return "Уште {$hours}ч и {$minutes}мин";
    }

    /**
     * 3. КОНТРОЛНА ТАБЛА ЗА ТЕСТАТОРИ [zmp_testator_portal]
     */
    public function render_testator_portal($atts) {
        if (!is_user_logged_in()) {
            return '<div class="zmp-container"><div class="zmp-card">Ве молиме најавете се на сајтот.</div></div>';
        }

        $user = wp_get_current_user();
        $is_testator = current_user_can('zmp_testator_cap')
            || current_user_can('view_zmp_testator_dashboard')
            || current_user_can('zmp_moderator_cap')
            || current_user_can('zmp_commission_cap')
            || in_array('zmp_testator', (array) $user->roles, true)
            || in_array('zmp_moderator', (array) $user->roles, true)
            || in_array('zmp_commission', (array) $user->roles, true)
            || current_user_can('manage_options');

        if (!$is_testator) {
            return '<div class="zmp-container"><div class="zmp-card zmp-text-center"><h3>Немате привилегија на Тестатор.</h3><p>Администраторот треба да ви додели улога Тестатор.</p></div></div>';
        }

        global $wpdb;
        $table_attempts   = $wpdb->prefix . 'zmp_attempts';
        $table_students   = $wpdb->prefix . 'zmp_students';
        $table_incidents  = $wpdb->prefix . 'zmp_incidents';
        $table_complaints = $wpdb->prefix . 'zmp_complaints';

        $user_school = get_user_meta($user->ID, 'zmp_school_name', true);
        $user_mun    = get_user_meta($user->ID, 'zmp_municipality', true);

        // Administrators can audit every school; testators remain scoped to their assigned school.
        $school_students = current_user_can('manage_options')
            ? $wpdb->get_results("SELECT a.*, s.first_name, s.last_name, s.access_code, s.school_name, s.grade FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id ORDER BY a.started_at DESC LIMIT 100")
            : $wpdb->get_results($wpdb->prepare("SELECT a.*, s.first_name, s.last_name, s.access_code, s.school_name, s.grade FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id WHERE s.school_name=%s ORDER BY a.started_at DESC",$user_school));

        $complaints = current_user_can('manage_options')
            ? $wpdb->get_results("SELECT * FROM $table_complaints ORDER BY created_at DESC LIMIT 100")
            : $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_complaints WHERE school_name=%s ORDER BY created_at DESC",$user_school));

        $duties = current_user_can('manage_options')
            ? $wpdb->get_results("SELECT d.*, u.display_name AS testator_name FROM {$wpdb->prefix}zmp_testator_duties d LEFT JOIN {$wpdb->users} u ON u.ID=d.testator_user_id ORDER BY d.duty_date ASC")
            : $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_testator_duties WHERE testator_user_id=%d ORDER BY duty_date ASC",$user->ID));

        $stage_duties = current_user_can('manage_options')
            ? $wpdb->get_results("SELECT d.*,s.academic_year,c.name_mk AS category_name,e.starts_at,e.ends_at,e.delivery_mode,e.location,e.online_details FROM {$wpdb->prefix}zmp_stage_testator_assignments d JOIN {$wpdb->prefix}zmp_seasons s ON s.id=d.season_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=d.category_id LEFT JOIN {$wpdb->prefix}zmp_stage_events e ON e.season_id=d.season_id AND e.category_id=d.category_id AND e.phase=d.phase WHERE c.category_code='knowledge_test' ORDER BY e.starts_at ASC,d.id DESC")
            : $wpdb->get_results($wpdb->prepare("SELECT d.*,s.academic_year,c.name_mk AS category_name,e.starts_at,e.ends_at,e.delivery_mode,e.location,e.online_details FROM {$wpdb->prefix}zmp_stage_testator_assignments d JOIN {$wpdb->prefix}zmp_seasons s ON s.id=d.season_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=d.category_id LEFT JOIN {$wpdb->prefix}zmp_stage_events e ON e.season_id=d.season_id AND e.category_id=d.category_id AND e.phase=d.phase WHERE c.category_code='knowledge_test' AND d.testator_user_id=%d ORDER BY e.starts_at ASC,d.id DESC",$user->ID));

        // Дописи испратени од овој тестатор до модераторите
        $dispatches = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_incidents WHERE testator_user_id = %d ORDER BY created_at DESC LIMIT 20",
            $user->ID
        ));
        $testator_tab = isset($_GET['zmp_testator_tab']) && is_scalar($_GET['zmp_testator_tab']) ? sanitize_key(wp_unslash($_GET['zmp_testator_tab'])) : 'duties';
        $testator_tabs = array('duties'=>'Распоред и задачи','monitoring'=>'Ученици','complaints'=>'Жалби','messages'=>'Дописи');
        $has_testator_room = ZMP_Role_Messenger::can_access_current_role_room('tester');
        if ($has_testator_room) {
            $testator_tabs = array_slice($testator_tabs, 0, 1, true) + array('messenger'=>'Месинџер') + array_slice($testator_tabs, 1, null, true);
        }
        if (!isset($testator_tabs[$testator_tab])) { $testator_tab = 'duties'; }
        $testator_url = get_permalink() ?: home_url('/');
        // Keep the outer portal route when testator tabs are rendered inside [zmp_natprevari_portal].
        if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) {
            $testator_url = add_query_arg('zmp_portal_tab', sanitize_key(wp_unslash($_GET['zmp_portal_tab'])), $testator_url);
        }
        $pending_testator_dispatches = count(array_filter((array)$dispatches, function($dispatch){return isset($dispatch->status) && $dispatch->status !== 'resolved';}));

        ob_start();
        ?>
        <div class="zmp-container" data-zmp-workspace="testator" data-zmp-workspace-query="zmp_testator_tab">
            <div class="zmp-mentor-header">
                <div>
                    <h3>🛡️ Контролна табла за Тестатори</h3>
                    <p>
                        <strong>Дежурен тестатор:</strong> <?php echo esc_html($user->display_name); ?> | 
                        <strong>Училиште:</strong> <?php echo esc_html($user_school ? $user_school : 'Недефинирано'); ?> (<?php echo esc_html($user_mun); ?>)
                    </p>
                </div>
                <div>
                    <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="zmp-btn zmp-btn-secondary">Одјава</a>
                </div>
            </div>

            <nav class="zmp-workspace__nav" aria-label="Портал за тестатори">
                <?php foreach ($testator_tabs as $key=>$label): $tab_count=$key==='messages'?$pending_testator_dispatches:($key==='messenger'?ZMP_Role_Messenger::unread_count('tester'):0); ?><a class="zmp-workspace__tab" data-workspace-tab="<?php echo esc_attr($key); ?>" href="<?php echo esc_url(add_query_arg('zmp_testator_tab',$key,$testator_url)); ?>" <?php echo $testator_tab===$key?'aria-current="page"':''; ?>><?php echo esc_html($label); ?><?php if($tab_count): ?><span class="zmp-workspace__badge"><?php echo esc_html($tab_count); ?></span><?php endif; ?></a><?php endforeach; ?>
            </nav>
            <?php if (!$has_testator_room) { echo ZMP_Role_Messenger::unavailable_notice('tester'); } ?>
            <div class="zmp-workspace__panel" data-workspace-panel="duties" <?php echo $testator_tab==='duties'?'':'hidden'; ?>>

            <div class="zmp-card"><h4>Распоред на дежурства</h4>
                <?php if (empty($duties)): ?><p>Немате доделени дежурства.</p><?php else: ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Датум</th><th>Натпреварувачко ниво</th><th>Локација</th><th>Белешки</th></tr></thead><tbody><?php foreach ($duties as $duty): ?><tr><td><?php echo esc_html($duty->duty_date); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($duty->phase)); ?></td><td><?php echo esc_html($duty->location); ?></td><td><?php echo esc_html($duty->notes); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
            </div>

            <div class="zmp-card"><h4>Задолженија по категории и натпреварувачко ниво</h4>
                <?php if (empty($stage_duties)): ?><p>Немате доделени задолженија по категории.</p><?php else: ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Година</th><th>Категорија</th><th>Натпреварувачко ниво</th><th>Распоред</th><th>Начин и место</th><th>Белешки</th></tr></thead><tbody><?php foreach ($stage_duties as $duty): ?><tr><td><?php echo esc_html($duty->academic_year); ?></td><td><?php echo esc_html($duty->category_name); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($duty->phase)); ?></td><td><?php echo esc_html($duty->starts_at?:'Не е внесен'); ?><?php if($duty->ends_at): ?><br><?php echo esc_html($duty->ends_at); ?><?php endif; ?></td><td><?php echo esc_html($duty->delivery_mode==='physical'?'Физички':($duty->delivery_mode==='online'?'Онлајн':'Комбинирано')); ?><?php if($duty->location): ?><br><?php echo esc_html($duty->location); ?><?php endif; ?><?php if($duty->online_details): ?><br><?php echo nl2br(esc_html($duty->online_details)); ?><?php endif; ?></td><td><?php echo esc_html($duty->notes); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
            </div>
            </div>
            <div class="zmp-workspace__panel" data-workspace-panel="messages" <?php echo $testator_tab==='messages'?'':'hidden'; ?>>

            <!-- ИСПРАЌАЊЕ СЛУЖБЕН ДОПИС ДО МОДЕРАТОРИТЕ -->
            <div class="zmp-card" style="border-top: 4px solid #0284c7;">
                <h4>📨 Испрати службен допис до Модераторите (Пријава на дефект / Барање за рестарт)</h4>
                <form id="zmp-incident-form" class="zmp-form">
                    <div class="zmp-form-row">
                        <div class="zmp-form-group">
                            <label>Предмет на дописот *</label>
                            <input type="text" name="subject" id="zmp_inc_subject" required placeholder="пр. Итен допис: Прекин на електрична енергија во сала 2">
                        </div>
                        <div class="zmp-form-group">
                            <label>Тип на дефект / барање *</label>
                            <select name="incident_type" id="zmp_inc_type" required>
                                <option value="defect">Технички дефект на компјутер / прелистувач</option>
                                <option value="internet_drop">Прекин на интернет врска</option>
                                <option value="reset_request">Барање за ресетирање тест за ученик</option>
                                <option value="other">Останато</option>
                            </select>
                        </div>
                    </div>

                    <div class="zmp-form-row">
                        <div class="zmp-form-group">
                            <label>Шифра на ученик (доколку се однесува на конкретен ученик)</label>
                            <input type="text" name="student_code" id="zmp_inc_code" placeholder="пр. ZMP-VI-8492">
                        </div>
                        <div class="zmp-form-group">
                            <label>Итност</label>
                            <select name="priority" id="zmp_inc_priority">
                                <option value="normal">Нормална</option>
                                <option value="urgent">Итна (Urgent)</option>
                                <option value="emergency">Критична (Emergency)</option>
                            </select>
                        </div>
                    </div>

                    <div class="zmp-form-group">
                        <label>Содржина на дописот / Опис на ситуацијата *</label>
                        <textarea name="message" id="zmp_inc_msg" rows="3" required placeholder="Детално образложение за модераторите..." style="width: 100%;"></textarea>
                    </div>

                    <button type="button" id="zmp-btn-send-incident" class="zmp-btn zmp-btn-primary">
                        🚀 Испрати службен допис &rarr;
                    </button>
                    <span id="zmp-incident-status" style="margin-left: 15px; font-weight: bold;"></span>
                </form>
            </div>
            </div>
            <?php if ($has_testator_room): ?><div class="zmp-workspace__panel" data-workspace-panel="messenger" <?php echo $testator_tab==='messenger'?'':'hidden'; ?>><?php echo ZMP_Role_Messenger::render('tester', $testator_tab==='messenger'); ?></div><?php endif; ?>
            <div class="zmp-workspace__panel" data-workspace-panel="monitoring" <?php echo $testator_tab==='monitoring'?'':'hidden'; ?>>

            <!-- МОНИТОРИНГ НА ТЕСТИРАЊЕТО ВО УЧИЛИШТЕТО & ДИСКВАЛИФИКАЦИЈА -->
            <div class="zmp-card">
                <h4>👥 Активност на тестирањето во вашето училиште</h4>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Шифра</th>
                                <th>Ученик</th>
                                <th>Одд.</th>
                                <th>Преостанато активно време</th>
                                <th>Статус</th>
                                <th>Акции (Рестарт / Дисквалификација)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($school_students)): ?>
                                <tr><td colspan="6">Нема ученици во ова училиште.</td></tr>
                            <?php else: foreach ($school_students as $st): 
                                $rem_min = floor($st->remaining_seconds / 60);
                                $rem_sec = $st->remaining_seconds % 60;
                            ?>
                                <tr>
                                    <td><code><?php echo esc_html($st->access_code); ?></code></td>
                                    <td><strong><?php echo esc_html($st->first_name . ' ' . $st->last_name); ?></strong></td>
                                    <td><?php echo esc_html($st->grade); ?> одд.</td>
                                    <td><strong><?php echo sprintf('%02d:%02d', $rem_min, $rem_sec); ?></strong></td>
                                    <td>
                                        <?php if ($st->status === 'completed'): ?>
                                            <span class="zmp-badge" style="background: #16a34a;">Завршен (<?php echo $st->total_points; ?> б.)</span>
                                        <?php elseif ($st->status === 'disqualified'): ?>
                                            <span class="zmp-badge" style="background: #dc2626;">Дисквалификуван</span>
                                        <?php else: ?>
                                            <span class="zmp-badge" style="background: #2563eb;">Во тек (Активен)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($st->status !== 'disqualified'): ?>
                                            
    <button type="button" class="zmp-btn zmp-btn-secondary zmp-reset-trigger" data-attempt="<?php echo $st->id; ?>" data-code="<?php echo esc_attr($st->access_code); ?>" style="padding: 4px 8px; font-size: 11px;">
                                                🔄 Рестартирај
                                            </button>
                                            <button type="button" class="zmp-btn zmp-btn-danger zmp-disqualify-trigger" data-type="student" data-id="<?php echo $st->student_id; ?>" data-code="<?php echo esc_attr($st->access_code); ?>" data-name="<?php echo esc_attr($st->first_name . ' ' . $st->last_name); ?>" data-school="<?php echo esc_attr($user_school); ?>" style="padding: 4px 8px; font-size: 11px; background: #dc2626; color: #fff;">
                                                🚫 Дисквалификувај
                                            </button>
                                        <?php else: ?>
                                            <small style="color: red;">Дисквалификуван</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
            <div class="zmp-workspace__panel" data-workspace-panel="complaints" <?php echo $testator_tab==='complaints'?'':'hidden'; ?>>

            <!-- ЖАЛБИ ОД МЕНТОРИ (ПРИЕМ, ОДЛУКА ИЛИ ПРЕПРАЌАЊЕ ДО МОДЕРАТОРИ) -->
            <div class="zmp-card" style="border-top: 4px solid #f59e0b;">
                <h4>⚖️ Жалби од Ментори (поднесени во рок од 24ч од завршувањето)</h4>
                <p style="font-size: 13px; color: #64748b;">
                    Како тестатор, можете да одлучите по жалбата (Прифати / Одбиј) или доколку има потреба да ја <strong>проследите до модераторите</strong>.
                </p>

                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Датум</th>
                                <th>Ментор</th>
                                <th>Ученик</th>
                                <th>Предмет & Образложение</th>
                                <th>Проследување / белешка од Тестаторот</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($complaints)): ?>
                                <tr><td colspan="6">Нема поднесено жалби за ова училиште.</td></tr>
                            <?php else: foreach ($complaints as $c): ?>
                                <tr>
                                    <td><small><?php echo esc_html($c->created_at); ?></small></td>
                                    <td><strong><?php echo esc_html($c->mentor_name); ?></strong></td>
                                    <td><?php echo esc_html($c->student_name); ?><br><code><?php echo esc_html($c->student_code); ?></code></td>
                                    <td>
                                        <strong><?php echo esc_html($c->complaint_subject); ?></strong>
                                        <p style="margin: 4px 0; font-size: 12px;"><?php echo nl2br(esc_html($c->complaint_text)); ?></p>
                                    </td>
                                    <td>
                                        <?php if ($c->testator_decision === 'pending'): ?>
                                            <div style="display:flex;gap:5px;flex-direction:column">
                                                <button type="button" class="zmp-btn zmp-btn-primary zmp-testator-complaint-btn" data-id="<?php echo esc_attr($c->id); ?>" data-decision="forwarded_to_moderator" style="padding:4px 8px;font-size:11px;background:#9333ea;color:#fff">↗ Достави белешка до Комисијата</button>
                                                <small>Прифаќање или одбивање го одредува Испитната комисија.</small>
                                            </div>
                                        <?php else: ?>
                                            <strong><?php echo esc_html($c->testator_decision); ?></strong>
                                            <?php if (!empty($c->testator_notes)): ?><br><small><?php echo esc_html($c->testator_notes); ?></small><?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c->status === 'accepted'): ?><span style="color: #16a34a; font-weight: bold;">Прифатена</span>
                                        <?php elseif ($c->status === 'rejected'): ?><span style="color: #dc2626; font-weight: bold;">Одбиена</span>
                                        <?php elseif ($c->status === 'forwarded'): ?><span style="color: #9333ea; font-weight: bold;">Кај Испитната комисија</span>
                                        <?php else: ?><span style="color: #ca8a04;">Чека одлука</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
            <div class="zmp-workspace__panel" <?php echo $testator_tab==='messages'?'':'hidden'; ?>>

            <!-- ИСТОРИЈА НА ДОПИСИ ДО МОДЕРАТОРИТЕ -->
            <div class="zmp-card">
                <h4>📬 Ваши испратени дописи до Модераторите</h4>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Датум</th>
                                <th>Предмет</th>
                                <th>Порака</th>
                                <th>Одговор од Модератор</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dispatches)): ?>
                                <tr><td colspan="5">Немате испратено дописи.</td></tr>
                            <?php else: foreach ($dispatches as $d): ?>
                                <tr>
                                    <td><small><?php echo esc_html($d->created_at); ?></small></td>
                                    <td><strong><?php echo esc_html($d->subject); ?></strong></td>
                                    <td><?php echo nl2br(esc_html($d->message)); ?></td>
                                    <td>
                                        <?php if (!empty($d->moderator_reply)): ?>
                                            <strong style="color: #1e3a8a;">&rarr; <?php echo nl2br(esc_html($d->moderator_reply)); ?></strong>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-style: italic;">Се чека одговор...</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($d->status === 'resolved'): ?><span style="color: #16a34a; font-weight: bold;">&check; Решено</span>
                                        <?php else: ?><span style="color: #dc2626; font-weight: bold;">Отворено</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 2. МОДЕРАТОРСКА КОНТРОЛНА ТАБЛА [zmp_moderator_portal]
     */
    public function render_moderator_portal($atts) {
        if (!is_user_logged_in()) {
            return '<div class="zmp-container"><div class="zmp-card">Ве молиме најавете се на сајтот.</div></div>';
        }

        $user = wp_get_current_user();
        $is_mod = current_user_can('zmp_moderator_cap')
            || current_user_can('view_zmp_moderator_dashboard')
            || current_user_can('zmp_commission_cap')
            || in_array('zmp_moderator', (array) $user->roles, true)
            || in_array('zmp_commission', (array) $user->roles, true)
            || current_user_can('manage_options');

        if (!$is_mod) {
            return '<div class="zmp-container"><div class="zmp-card">Немате улога на Модератор.</div></div>';
        }

        global $wpdb;
        $table_incidents  = $wpdb->prefix . 'zmp_incidents';
        $table_attempts   = $wpdb->prefix . 'zmp_attempts';
        $table_students   = $wpdb->prefix . 'zmp_students';
        $table_complaints = $wpdb->prefix . 'zmp_complaints';

        // Дописи од тестаторите (како е-пошта инбокс)
        $dispatches = $wpdb->get_results("SELECT i.*, a.id AS active_attempt_id, a.is_paused AS attempt_is_paused, a.status AS attempt_status FROM $table_incidents i LEFT JOIN $table_attempts a ON a.id=(SELECT a2.id FROM $table_attempts a2 WHERE a2.student_id=i.student_id AND a2.competition_id=i.competition_id AND a2.status IN ('in_progress','completed','stopped') ORDER BY a2.started_at DESC LIMIT 1) WHERE i.competition_id=(SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1) ORDER BY i.status ASC, i.created_at DESC LIMIT 50");

        // Ученици што се тестираат во моментот во живо
        $active_competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1"));
        $live_students = $active_competition_id ? $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, s.first_name, s.last_name, s.access_code, s.school_name, s.municipality, s.grade,
                (SELECT i.id FROM $table_incidents i WHERE i.competition_id=a.competition_id AND i.student_id=s.id AND i.status<>'resolved' ORDER BY i.created_at DESC,i.id DESC LIMIT 1) AS incident_id
             FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id
             WHERE a.competition_id=%d AND a.status='in_progress' ORDER BY a.last_active_at DESC",
            $active_competition_id
        )) : array();

        // Препратени жалби од тестатори
        $forwarded_complaints = $wpdb->get_results(
            "SELECT * FROM $table_complaints WHERE status = 'forwarded' OR testator_decision = 'forwarded_to_moderator' ORDER BY created_at DESC"
        );

        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-mentor-header">
                <div>
                    <h3>🎛️ Модераторска контролна табла</h3>
                    <p><strong>Модератор:</strong> <?php echo esc_html($user->display_name); ?> | Увид над сите училишта, дописи и ресетирање тестови</p>
                </div>
                <div>
                    <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="zmp-btn zmp-btn-secondary">Одјава</a>
                </div>
            </div>

            <!-- ИНБОКС НА СЛУЖБЕНИ ДОПИСИ ОД ТЕСТАТОРИТЕ (КАКО ФОРМА НА Е-ПОШТА) -->
            <div class="zmp-card" style="border-top: 4px solid #1e3a8a;">
                <h4>📬 Службени дописи од Тестаторите (Е-пошта Инбокс)</h4>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Време</th>
                                <th>Испраќач (Тестатор / Училиште)</th>
                                <th>Предмет на дописот</th>
                                <th>Порака</th>
                                <th>Одговор & Решение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dispatches)): ?>
                                <tr><td colspan="5">Нема пристигнато дописи.</td></tr>
                            <?php else: foreach ($dispatches as $d): ?>
                                <tr style="<?php echo ($d->status === 'unread') ? 'background: #eff6ff; font-weight: 500;' : ''; ?>">
                                    <td><small><?php echo esc_html($d->created_at); ?></small></td>
                                    <td>
                                        <strong><?php echo esc_html($d->testator_name); ?></strong><br>
                                        <small><?php echo esc_html($d->school_name . ' (' . $d->municipality . ')'); ?></small>
                                    </td>
                                    <td>
                                        <span class="zmp-badge" style="background: <?php echo ($d->priority === 'emergency') ? '#dc2626' : (($d->priority === 'urgent') ? '#ea580c' : '#0284c7'); ?>;">
                                            <?php echo strtoupper(esc_html($d->priority)); ?>
                                        </span>
                                        <br><strong><?php echo esc_html($d->subject); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($d->student_code)): ?><code><?php echo esc_html($d->student_code); ?></code>: <?php endif; ?>
                                        <?php echo nl2br(esc_html($d->message)); ?>
                                    </td>
                                    <td>
                                        <?php if ($d->status === 'resolved'): ?>
                                            <span style="color: #16a34a; font-weight: bold;">&check; Решено</span>: <?php echo esc_html($d->moderator_reply); ?>
                                        <?php else: ?>
                                            <div style="display: flex; gap: 5px;">
                                                <input type="text" id="reply-msg-<?php echo $d->id; ?>" placeholder="Внесете одговор/решение..." style="padding: 6px; font-size: 13px; width: 70%;">
                                                <button type="button" class="zmp-btn zmp-btn-primary zmp-mod-reply-btn" data-id="<?php echo $d->id; ?>" style="padding: 6px 12px; font-size: 12px;">Одговори</button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($d->active_attempt_id) && $d->status !== 'resolved'): ?>
                                            <div class="zmp-mod-controls" style="margin-top:8px;display:flex;gap:4px;flex-wrap:wrap">
                                                <?php if ($d->attempt_status === 'in_progress'): ?>
                                                    <?php if (empty($d->attempt_is_paused)): ?><button type="button" class="button zmp-attempt-control" data-action="pause" data-attempt="<?php echo esc_attr($d->active_attempt_id); ?>" data-incident="<?php echo esc_attr($d->id); ?>">Паузирај</button><?php else: ?><button type="button" class="button zmp-attempt-control" data-action="resume" data-attempt="<?php echo esc_attr($d->active_attempt_id); ?>" data-incident="<?php echo esc_attr($d->id); ?>">Продолжи</button><?php endif; ?>
                                                    <button type="button" class="button zmp-attempt-control" data-action="stop" data-attempt="<?php echo esc_attr($d->active_attempt_id); ?>" data-incident="<?php echo esc_attr($d->id); ?>">Запри</button>
                                                <?php endif; ?>
                                                <button type="button" class="button zmp-attempt-control" data-action="restart" data-attempt="<?php echo esc_attr($d->active_attempt_id); ?>" data-incident="<?php echo esc_attr($d->id); ?>">Рестартирај</button>
                                            </div>
                                        <?php elseif (!empty($d->student_id)): ?><small>Нема активен тест за поврзаниот ученик; проверете го периодот и натпреварувачкото ниво.</small><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- УВИД НАД ТЕКОТ НА ТЕСТОВИТЕ НА СЕКОЈ УЧЕНИК ВО МОМЕНТОТ ВО ЖИВО -->
            <div class="zmp-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0;">🔴 Тековни активни тестирања во живо (<span id="zmp-live-students-count"><?php echo count($live_students); ?></span>)</h4>
                    <button onclick="window.location.reload()" class="zmp-btn zmp-btn-secondary" style="padding: 6px 12px; font-size: 12px;">🔄 Освежи листа</button>
                </div>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Шифра</th>
                                <th>Ученик</th>
                                <th>Училиште и Општина</th>
                                <th>Одд.</th>
                                <th>Преостанати активни мин.</th>
                                <th>Последна активност</th>
                                <th>Опција Ресетирање</th>
                            </tr>
                        </thead>
                        <tbody id="zmp-live-students-body">
                            <?php if (empty($live_students)): ?>
                                <tr><td colspan="7">Во моментов нема активни ученици на тест.</td></tr>
                            <?php else: foreach ($live_students as $ls): 
                                $rem_min = floor($ls->remaining_seconds / 60);
                                $rem_sec = $ls->remaining_seconds % 60;
                            ?>
                                <tr>
                                    <td><code><?php echo esc_html($ls->access_code); ?></code></td>
                                    <td><strong><?php echo esc_html($ls->first_name . ' ' . $ls->last_name); ?></strong></td>
                                    <td><?php echo esc_html($ls->school_name . ' (' . $ls->municipality . ')'); ?></td>
                                    <td><?php echo esc_html($ls->grade); ?> одд.</td>
                                    <td><strong><?php echo sprintf('%02d:%02d', $rem_min, $rem_sec); ?></strong></td>
                                    <td><small><?php echo esc_html($ls->last_active_at); ?></small></td>
                                    <td>
                                        <?php if (!empty($ls->incident_id)): ?>
                                            <?php if (empty($ls->is_paused)): ?><button type="button" class="button zmp-attempt-control" data-action="pause" data-incident="<?php echo esc_attr($ls->incident_id); ?>" data-attempt="<?php echo esc_attr($ls->id); ?>" data-code="<?php echo esc_attr($ls->access_code); ?>">⏸ Паузирај</button><?php else: ?><button type="button" class="button zmp-attempt-control" data-action="resume" data-incident="<?php echo esc_attr($ls->incident_id); ?>" data-attempt="<?php echo esc_attr($ls->id); ?>" data-code="<?php echo esc_attr($ls->access_code); ?>">▶ Продолжи</button><?php endif; ?>
                                            <button type="button" class="button zmp-attempt-control" data-action="stop" data-incident="<?php echo esc_attr($ls->incident_id); ?>" data-attempt="<?php echo esc_attr($ls->id); ?>" data-code="<?php echo esc_attr($ls->access_code); ?>">⏹ Запри</button>
                                            <button type="button" class="button zmp-attempt-control" data-action="restart" data-incident="<?php echo esc_attr($ls->incident_id); ?>" data-attempt="<?php echo esc_attr($ls->id); ?>" data-code="<?php echo esc_attr($ls->access_code); ?>">↻ Рестартирај</button>
                                        <?php else: ?><small>Чека допис од тестатор.</small><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php $active_competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")); $moderator_audit = $active_competition_id ? $wpdb->get_results($wpdb->prepare("SELECT a.*,u.display_name FROM {$wpdb->prefix}zmp_moderator_actions a LEFT JOIN {$wpdb->users} u ON u.ID=a.moderator_user_id WHERE a.competition_id=%d ORDER BY a.created_at DESC LIMIT 50", $active_competition_id)) : array(); ?>
            <div class="zmp-card"><h4>Ревизорски дневник — паузирање, запирање и рестартирање</h4>
                <?php if (!$moderator_audit): ?><p>Нема евидентирани акции.</p><?php else: ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Време</th><th>Акција</th><th>Обид / ученик</th><th>Модератор</th><th>Причина</th></tr></thead><tbody><?php foreach ($moderator_audit as $entry): ?><tr><td><?php echo esc_html($entry->created_at); ?></td><td><?php echo esc_html($entry->action); ?></td><td><?php echo esc_html($entry->attempt_id.' / '.$entry->student_id); ?></td><td><?php echo esc_html($entry->display_name ?: 'Избришан корисник'); ?></td><td><?php echo nl2br(esc_html($entry->reason)); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
            </div>

            <!-- ПРЕПРАТЕНИ ЖАЛБИ ОД ТЕСТАТОРИТЕ -->
            <?php if (!empty($forwarded_complaints)): ?>
                <div class="zmp-card" style="border-top: 4px solid #9333ea;">
                    <h4>↗ Препратени жалби од Тестатори на одлучување</h4>
                    <div class="zmp-table-responsive">
                        <table class="zmp-table">
                            <thead>
                                <tr>
                                    <th>Училиште</th>
                                    <th>Ученик</th>
                                    <th>Предмет</th>
                                    <th>Образложение</th>
                                    <th>Одлука на Модераторите</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forwarded_complaints as $fc): ?>
                                    <tr>
                                        <td><?php echo esc_html($fc->school_name); ?></td>
                                        <td><strong><?php echo esc_html($fc->student_name); ?></strong> (<code><?php echo esc_html($fc->student_code); ?></code>)</td>
                                        <td><?php echo esc_html($fc->complaint_subject); ?></td>
                                        <td><?php echo nl2br(esc_html($fc->complaint_text)); ?></td>
                                        <td>
<span>Препратено до Испитната комисија за одлука.</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 4. КОНТРОЛНА ТАБЛА ЗА ИСПИТНА КОМИСИЈА [zmp_commission_portal]
     */
    public function render_commission_portal($atts) {
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { return ''; }
        if (!is_user_logged_in()) {
            return '<div class="zmp-container"><div class="zmp-card">Ве молиме најавете се.</div></div>';
        }

        $user = wp_get_current_user();
        $is_comm = in_array('zmp_commission', (array) $user->roles) || current_user_can('manage_options');

        if (!$is_comm) {
            return '<div class="zmp-container"><div class="zmp-card zmp-text-center"><h3>Пристапот е дозволен исклучиво за членови на Испитната комисија.</h3></div></div>';
        }

        global $wpdb;
        $table_comm = $wpdb->prefix . 'zmp_commission';
        $table_disq = $wpdb->prefix . 'zmp_disqualifications';
        $active_competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1"));
        $mix_phase_selected = sanitize_key(wp_unslash($_GET['mix_phase'] ?? 'school'));
        if (!in_array($mix_phase_selected, array('school','municipal','regional','state'), true)) { $mix_phase_selected = 'school'; }
        $mix_grade_selected = absint($_GET['mix_grade'] ?? 6);
        if (!in_array($mix_grade_selected, array(6,7,8,9), true)) { $mix_grade_selected = 6; }
        $saved_mix = get_option('zmp_question_mix_' . $active_competition_id . '_' . $mix_phase_selected . '_' . $mix_grade_selected, array(2=>5,5=>5,15=>5,20=>5));

        $members = $wpdb->get_results("SELECT * FROM $table_comm WHERE is_active = 1 ORDER BY id ASC");
        $disqualifications = $wpdb->get_results("SELECT * FROM $table_disq ORDER BY created_at DESC");

        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-mentor-header" style="background: linear-gradient(135deg, #1e3a8a, #0f172a); color: #fff; border-radius: 10px; padding: 25px;">
                <div>
                    <h2 style="color: #f59e0b; margin: 0 0 5px 0;">🏛️ Испитна Комисија (Врховно тело)</h2>
                    <p style="margin: 0; opacity: 0.9;">
                        Член: <strong><?php echo esc_html($user->display_name); ?></strong> | Надзор на целиот процес на натпреварување, коректност и највисока арбитража.
                    </p>
                </div>
                <div>
                    <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="zmp-btn zmp-btn-gold">Одјава</a>
                </div>
            </div>

            <div class="zmp-card zmp-commission-section" data-commission-panel="questions" role="region" aria-labelledby="zmp-commission-tab-questions" style="margin-top:25px;border-top:4px solid #f59e0b">
                <h3>📚 Банка и делегирање на прашања</h3>
                <p>Комисијата го задава одделението, натпреварувачкото ниво и тежината. Бодовите (1–20) се тежина на прашањето. На секој ученик му се доделуваат точно 20 случајно избрани прашања според зачуваната распределба; истиот избор останува при повторно отворање на тестот.</p>
                <?php if (!empty($_GET['zmp_question_saved'])): ?><p class="zmp-alert zmp-alert-success">Промените се зачувани.</p><?php endif; ?>
                <form method="post" class="zmp-form">
                    <?php wp_nonce_field('zmp_commission_question_action'); ?><input type="hidden" name="zmp_commission_question_action" value="save_mix">
                    <h4>Распределба за 20 прашања</h4>
                    <label>Натпреварувачко ниво <select name="mix_phase"><option value="school" <?php selected($mix_phase_selected,'school'); ?>>Училишно натпреварувачко ниво</option><option value="municipal" <?php selected($mix_phase_selected,'municipal'); ?>>Општинско натпреварувачко ниво</option><option value="regional" <?php selected($mix_phase_selected,'regional'); ?>>Регионално натпреварувачко ниво</option><option value="state" <?php selected($mix_phase_selected,'state'); ?>>Државно натпреварувачко ниво</option></select></label>
                    <label>Одделение <select name="mix_grade"><?php foreach(array(6,7,8,9) as $mix_grade_option): ?><option value="<?php echo esc_attr($mix_grade_option); ?>" <?php selected($mix_grade_selected,$mix_grade_option); ?>><?php echo esc_html($mix_grade_option); ?></option><?php endforeach; ?></select></label>
                    <p>Внесете колку прашања да се извлечат за секоја вредност на бодови. Збирот мора да биде 20. Пример: 5 прашања со 2, 5 со 5, 5 со 15 и 5 со 20 бодови.</p>
                    <div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Бодови за точен одговор / тежина</th><th>Број прашања во тестот</th></tr></thead><tbody>
                    <?php for ($point=1;$point<=20;$point++): ?><tr><td><?php echo esc_html($point); ?></td><td><input type="number" min="0" max="20" name="mix_count[<?php echo esc_attr($point); ?>]" value="<?php echo esc_attr(absint($saved_mix[$point] ?? $saved_mix[(string)$point] ?? 0)); ?>" style="width:90px"></td></tr><?php endfor; ?>
                    </tbody></table></div>
                    <button class="zmp-btn" name="zmp_load_mix" value="1" type="submit">Вчитај зачувана распределба</button>
                    <button class="zmp-btn zmp-btn-primary" type="submit">Зачувај распределба</button>
                </form>
                <hr><h4>Додај прашање во базата</h4>
                <form method="post" class="zmp-form">
                    <?php wp_nonce_field('zmp_commission_question_action'); ?><input type="hidden" name="zmp_commission_question_action" value="add_question">
                    <label>Одделение <select name="grade"><option>6</option><option>7</option><option>8</option><option>9</option></select></label>
                    <label>Натпреварувачко ниво <select name="phase"><option value="all">Сите натпреварувачки нивоа</option><option value="school">Училишно натпреварувачко ниво</option><option value="municipal">Општинско натпреварувачко ниво</option><option value="regional">Регионално натпреварувачко ниво</option><option value="state">Државно натпреварувачко ниво</option></select></label>
                    <label>Бодови (1–20) <input type="number" name="points" min="1" max="20" required></label>
                    <p><textarea name="question_text_mk" required placeholder="Прашање на македонски"></textarea><textarea name="question_text_sq" required placeholder="Pyetja në shqip"></textarea></p>
                    <?php for($i=0;$i<4;$i++): ?><p><input name="option_mk[]" required placeholder="Опција <?php echo chr(65+$i); ?> (MK)"> <input name="option_sq[]" required placeholder="Opsioni <?php echo chr(65+$i); ?> (SQ)"> <label><input type="radio" name="correct_option_index" value="<?php echo $i; ?>" <?php checked($i,0); ?>> Точен одговор</label></p><?php endfor; ?>
                    <p><label>Аудио URL <input name="audio_url" type="url"></label> <label>Слика URL <input name="image_url" type="url"></label></p>
                    <button class="zmp-btn zmp-btn-primary" type="submit">Додај прашање</button>
                </form>
                <h4>База на прашања — групирана по одделение, натпреварувачко ниво и бодови</h4>
                <?php $bank = $wpdb->get_results("SELECT grade,phase,points,COUNT(*) AS question_count FROM {$wpdb->prefix}zmp_questions GROUP BY grade,phase,points ORDER BY grade,phase,points"); ?>
                <div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Одделение</th><th>Натпреварувачко ниво / делегирање</th><th>Бодови / тежина</th><th>Прашања во базата</th></tr></thead><tbody>
                <?php if (!$bank): ?><tr><td colspan="4">Базата сè уште е празна.</td></tr><?php else: foreach($bank as $bucket): ?><tr><td><?php echo esc_html($bucket->grade); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($bucket->phase)); ?></td><td><?php echo esc_html((int)$bucket->points); ?></td><td><?php echo esc_html($bucket->question_count); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
                <?php $bank_questions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_questions ORDER BY grade,phase,points,id"); ?>
                <div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Одделение</th><th>Натпреварувачко ниво</th><th>Прашање (MK / SQ)</th><th>Ниво — бодови</th><th>Точен одговор</th></tr></thead><tbody>
                <?php if (!$bank_questions): ?><tr><td colspan="5">Нема внесени прашања.</td></tr><?php else: foreach($bank_questions as $bank_question): $bank_options=json_decode($bank_question->options_json,true); $correct_text=is_array($bank_options)&&isset($bank_options[(int)$bank_question->correct_option_index])?$bank_options[(int)$bank_question->correct_option_index]:array(); ?><tr><td><?php echo esc_html($bank_question->grade); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($bank_question->phase)); ?></td><td><?php echo esc_html($bank_question->question_text_mk); ?><br><small><?php echo esc_html($bank_question->question_text_sq); ?></small></td><td><strong><?php echo esc_html((int)$bank_question->points); ?></strong></td><td><?php echo esc_html(($correct_text['text_mk'] ?? '') . ' / ' . ($correct_text['text_sq'] ?? '')); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div>
            </div>

            <!-- ЧЛЕНОВИ НА ИСПИТНАТА КОМИСИЈА -->
            <div class="zmp-card zmp-commission-section" data-commission-panel="records" role="region" aria-labelledby="zmp-commission-tab-records" style="margin-top: 25px;">
                <h4>🎓 Состав на Испитната Комисија (Колеги од средно/високо музичко образование)</h4>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Име и Презиме</th>
                                <th>Институција / Музичко училиште / Факултет</th>
                                <th>Звање</th>
                                <th>Контакт Е-пошта</th>
                                <th>Телефон</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="5">Администраторот сè уште нема внесено членови во комисијата преку контролната табла.</td></tr>
                            <?php else: foreach ($members as $m): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($m->full_name); ?></strong></td>
                                    <td><?php echo esc_html($m->institution); ?></td>
                                    <td><span class="zmp-badge"><?php echo esc_html($m->academic_title); ?></span></td>
                                    <td><?php echo esc_html($m->email); ?></td>
                                    <td><?php echo esc_html($m->phone); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ИЗРЕКУВАЊЕ НА ДИСКВАЛИФИКАЦИЈА ОД ИСПИТНАТА КОМИСИЈА -->
            <div class="zmp-card zmp-commission-section" data-commission-panel="discipline" role="region" aria-labelledby="zmp-commission-tab-discipline" style="border-top: 4px solid #dc2626;">
                <h4 style="color: #dc2626;">🚫 Изрекување на Дисквалификација (со задолжително образложение)</h4>
                <p style="font-size: 13.5px; color: #64748b;">
                    Испитната комисија како највисоко тело има овластување да дисквалификува <strong>Ученик</strong>, <strong>Ментор/Наставник</strong> или <strong>Тестатор</strong> доколку се утврди нерегуларност.
                </p>

                <form id="zmp-disqualify-form" class="zmp-form">
                    <div class="zmp-form-row">
                        <div class="zmp-form-group">
                            <label>Кого дисквалификувате? *</label>
                            <select name="target_type" id="zmp_disq_type" required>
                                <option value="student">Ученик (Student)</option>
                                <option value="mentor">Ментор / Наставник (Mentor)</option>
                                <option value="testator">Тестатор / Дежурен во сала (Testator)</option>
                            </select>
                        </div>
                        <div class="zmp-form-group">
                            <label>Име и презиме на лицето *</label>
                            <input type="text" name="target_name" id="zmp_disq_name" required placeholder="пр. Петар Стојанов">
                        </div>
                    </div>

                    <div class="zmp-form-row">
                        <div class="zmp-form-group">
                            <label>Шифра / Корисничко име (за ученик)</label>
                            <input type="text" name="target_code" id="zmp_disq_code" placeholder="пр. ZMP-VI-8492">
                        </div>
                        <div class="zmp-form-group">
                            <label>Училиште и Општина *</label>
                            <input type="text" name="school_name" id="zmp_disq_school" required placeholder="пр. ООУ Гоце Делчев, Струмица">
                        </div>
                    </div>

                    <div class="zmp-form-group">
                        <label>Задолжително детално образложение за дисквалификацијата *</label>
                        <textarea name="detailed_reason" id="zmp_disq_reason" rows="3" required placeholder="Наведете ги конкретните факти, прекршувања и причини за одлуката за дисквалификација..." style="width: 100%;"></textarea>
                    </div>

                    <button type="button" id="zmp-btn-disqualify" class="zmp-btn zmp-btn-danger" style="background: #dc2626; color: #fff;">
                        Изречи официјална дисквалификација
                    </button>
                    <span id="zmp-disq-status" style="margin-left: 15px; font-weight: bold;"></span>
                </form>
            </div>

            <!-- РЕГИСТАР НА ДИСКВАЛИФИКАЦИИ -->
            <div class="zmp-card zmp-commission-section" data-commission-panel="discipline" role="region" aria-labelledby="zmp-commission-tab-discipline">
                <h4>📜 Официјален регистар на изречени дисквалификации</h4>
                <div class="zmp-table-responsive">
                    <table class="zmp-table">
                        <thead>
                            <tr>
                                <th>Датум</th>
                                <th>Тип</th>
                                <th>Име и Презиме</th>
                                <th>Шифра / Училиште</th>
                                <th>Изречена од</th>
                                <th>Детално образложение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($disqualifications)): ?>
                                <tr><td colspan="6">Нема изречени дисквалификации. Натпреварот се одвива регуларно.</td></tr>
                            <?php else: foreach ($disqualifications as $dq): ?>
                                <tr>
                                    <td><small><?php echo esc_html($dq->created_at); ?></small></td>
                                    <td><span class="zmp-badge" style="background: #dc2626;"><?php echo strtoupper(esc_html($dq->target_type)); ?></span></td>
                                    <td><strong><?php echo esc_html($dq->target_name); ?></strong></td>
                                    <td><code><?php echo esc_html($dq->target_code); ?></code><br><small><?php echo esc_html($dq->school_name); ?></small></td>
                                    <td><strong><?php echo esc_html($dq->disqualified_by_name); ?></strong> (<?php echo esc_html($dq->disqualified_by_role); ?>)</td>
                                    <td><em style="color: #991b1b;"><?php echo nl2br(esc_html($dq->detailed_reason)); ?></em></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX Тестатор одлучува по жалба или препраќа до модератори
     */
    private function zmp_user_has_role($roles) {
        $user = wp_get_current_user();
        return current_user_can('manage_options') || (bool) array_intersect((array) $roles, (array) $user->roles);
    }

    public function ajax_submit_incident() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if (!$this->zmp_user_has_role(array('zmp_testator'))) { wp_send_json_error(array('message' => 'Немате привилегија на тестатор.')); }
        $user = wp_get_current_user();
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if ($subject === '' || $message === '') { wp_send_json_error(array('message' => 'Внесете предмет и опис.')); }
        $type = sanitize_key($_POST['incident_type'] ?? 'other');
        $priority = sanitize_key($_POST['priority'] ?? 'normal');
        if (!in_array($type, array('defect', 'reset_request', 'internet_drop', 'other'), true)) { $type = 'other'; }
        if (!in_array($priority, array('normal', 'urgent', 'emergency'), true)) { $priority = 'normal'; }
        $school = sanitize_text_field(get_user_meta($user->ID, 'zmp_school_name', true));
        $code = sanitize_text_field($_POST['student_code'] ?? '');
        $student_id = 0;
        global $wpdb;
        if ($code !== '') {
            $student = $wpdb->get_row($wpdb->prepare("SELECT id, school_name FROM {$wpdb->prefix}zmp_students WHERE access_code = %s", $code));
            if (!$student || ($school !== '' && $student->school_name !== $school)) { wp_send_json_error(array('message' => 'Ученикот не е пронајден во вашето училиште.')); }
            $student_id = absint($student->id);
        }
        if ($type === 'reset_request' && !$student_id) { wp_send_json_error(array('message'=>'За барање за запирање или рестарт внесете шифра на ученик.')); }
        $ok = $wpdb->insert($wpdb->prefix . 'zmp_incidents', array(
            'subject' => $subject, 'testator_user_id' => $user->ID, 'competition_id' => absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")), 'testator_name' => $user->display_name,
            'school_name' => $school ?: 'Не е наведено', 'municipality' => sanitize_text_field(get_user_meta($user->ID, 'zmp_municipality', true)),
            'student_id' => $student_id, 'student_code' => $code, 'incident_type' => $type, 'priority' => $priority,
            'message' => $message, 'status' => 'unread'
        ));
        if (!$ok) { wp_send_json_error(array('message' => 'Допистот не можеше да се зачува.')); }
        wp_send_json_success(array('message' => 'Допистот е испратен.'));
    }

    public function ajax_reset_student_test() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        $roles = array('zmp_testator', 'zmp_moderator', 'zmp_commission');
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { wp_send_json_error(array('message'=>'Дејството од старото знаење е достапно само на тестаторите и модераторите.'),403); }
        if (!$this->zmp_user_has_role($roles)) { wp_send_json_error(array('message' => 'Немате привилегија за ресетирање.')); }
        global $wpdb;
        $attempt_id = absint($_POST['attempt_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT a.*, s.school_name FROM {$wpdb->prefix}zmp_attempts a JOIN {$wpdb->prefix}zmp_students s ON s.id = a.student_id WHERE a.id = %d", $attempt_id));
        if (!$attempt || $attempt->status === 'disqualified' || $reason === '') { wp_send_json_error(array('message' => 'Обидот не може да се ресетира.')); }
        $user = wp_get_current_user();
        if (!$this->zmp_user_has_role(array('zmp_moderator', 'zmp_commission')) && get_user_meta($user->ID, 'zmp_school_name', true) !== $attempt->school_name) { wp_send_json_error(array('message' => 'Ученикот не е од вашето училиште.')); }
        $minutes = (int) $wpdb->get_var($wpdb->prepare("SELECT duration_minutes FROM {$wpdb->prefix}zmp_calendar WHERE phase = %s AND is_active = 1 ORDER BY id DESC LIMIT 1", $attempt->phase));
        if ($minutes < 1) { $minutes = 45; }
        $updated = $wpdb->update($wpdb->prefix . 'zmp_attempts', array(
            'started_at' => current_time('mysql'), 'completed_at' => null, 'remaining_seconds' => $minutes * 60,
            'time_spent_seconds' => 0, 'last_active_at' => current_time('mysql'), 'is_paused' => 0,
            'reset_count' => absint($attempt->reset_count) + 1, 'reset_reason' => $reason, 'total_points' => 0,
            'award_level' => '', 'qualified_next_stage' => 0, 'answers_json' => wp_json_encode(array()), 'status' => 'in_progress'
        ), array('id' => $attempt_id));
        if ($updated === false) { wp_send_json_error(array('message' => 'Ресетирањето не успеа.')); }
        wp_send_json_success(array('message' => 'Тестот е ресетиран.'));
    }

    public function ajax_reply_incident() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if ((class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) || !$this->zmp_user_has_role(array('zmp_moderator','zmp_testator'))) { wp_send_json_error(array('message' => 'Овој одговор е ограничен на тестатори и модератори.'),403); }
        $id = absint($_POST['incident_id'] ?? 0);
        $reply = sanitize_textarea_field($_POST['reply'] ?? '');
        if (!$id || $reply === '') { wp_send_json_error(array('message' => 'Внесете одговор.')); }
        global $wpdb;
        $ok = $wpdb->update($wpdb->prefix . 'zmp_incidents', array('moderator_reply' => $reply, 'status' => 'resolved', 'resolved_at' => current_time('mysql')), array('id' => $id));
        if ($ok === false) { wp_send_json_error(array('message' => 'Одговорот не можеше да се зачува.')); }
        wp_send_json_success();
    }

    public function ajax_submit_complaint() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if (!$this->zmp_user_has_role(array('zmp_teacher'))) { wp_send_json_error(array('message' => 'Немате привилегија на наставник.')); }
        global $wpdb;
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id = %d", get_current_user_id()));
        $student_id = absint($_POST['student_id'] ?? 0);
        $student = $mentor ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_students WHERE id = %d AND mentor_id = %d", $student_id, $mentor->id)) : null;
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $text = sanitize_textarea_field($_POST['complaint_text'] ?? '');
        if (!$student || $subject === '' || $text === '') { wp_send_json_error(array('message' => 'Проверете ги податоците за жалбата.')); }
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_attempts WHERE student_id = %d AND status = %s ORDER BY completed_at DESC LIMIT 1", $student_id, 'completed'));
        if (!$attempt || !self::can_submit_complaint($attempt->completed_at)) { wp_send_json_error(array('message' => 'Рокот од 24 часа е истечен или тестот не е завршен.')); }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_complaints WHERE attempt_id = %d AND mentor_user_id = %d LIMIT 1", $attempt->id, get_current_user_id()));
        if ($exists) { wp_send_json_error(array('message' => 'Веќе има жалба за овој тест.')); }
        $ok = $wpdb->insert($wpdb->prefix . 'zmp_complaints', array(
            'mentor_user_id' => get_current_user_id(), 'competition_id' => absint($attempt->competition_id), 'mentor_name' => $mentor->full_name,
            'student_id' => $student_id, 'student_name' => $student->first_name . ' ' . $student->last_name,
            'student_code' => $student->access_code, 'attempt_id' => $attempt->id, 'school_name' => $student->school_name,
            'phase' => $attempt->phase, 'complaint_subject' => $subject, 'complaint_text' => $text, 'status' => 'pending'
        ));
        if (!$ok) { wp_send_json_error(array('message' => 'Жалбата не можеше да се зачува.')); }
        $competition_id = absint($attempt->competition_id);
        $decision_ok = $wpdb->insert($wpdb->prefix . 'zmp_commission_decisions', array(
            'decision_type'=>'complaint','source_type'=>'complaint','source_id'=>$wpdb->insert_id,
            'competition_id'=>$competition_id,'target_type'=>'student','target_id'=>$student_id,
            'target_code'=>$student->access_code,'school_name'=>$student->school_name,'created_by'=>get_current_user_id(),
            'subject_name'=>$student->first_name.' '.$student->last_name,
            'reason'=>$subject."\n\n".$text,'status'=>'pending'
        ));
        if (!$decision_ok) { wp_send_json_error(array('message'=>'Жалбата е зачувана и ќе се појави во редот на Комисијата, но известувањето не е заведено.')); }
        wp_send_json_success(array('message' => 'Жалбата е поднесена и проследена до Комисијата за одлука.'));
    }
    public function ajax_testator_decide_complaint() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if (!$this->zmp_user_has_role(array('zmp_testator'))) { wp_send_json_error(array('message' => 'Немате привилегии.')); }
        $id = absint($_POST['complaint_id'] ?? 0);
        $decision = sanitize_key($_POST['decision'] ?? '');
        if (!$id || $decision !== 'forwarded_to_moderator') { wp_send_json_error(array('message' => 'Невалидна одлука.')); }
        global $wpdb;
        $school = get_user_meta(get_current_user_id(), 'zmp_school_name', true);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $status = 'forwarded';
        $where = array('id'=>$id, 'testator_decision'=>'pending');
        if (!current_user_can('manage_options')) $where['school_name'] = $school;
        $updated = $wpdb->update($wpdb->prefix . 'zmp_complaints', array(
            'testator_user_id' => get_current_user_id(), 'testator_decision' => $decision,
            'testator_notes' => $notes, 'status' => $status,
        ), $where);
        if ($updated === false) { wp_send_json_error(array('message' => 'Одлуката не можеше да се зачува.')); }
        if ($decision === 'forwarded_to_moderator' && $updated) {
            $complaint = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_complaints WHERE id=%d", $id));
            if ($complaint) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_commission_decisions WHERE source_type='complaint' AND source_id=%d AND status='pending'", $id));
                if (!$exists && !$wpdb->insert($wpdb->prefix . 'zmp_commission_decisions', array(
                    'decision_type'=>'complaint', 'source_type'=>'complaint', 'source_id'=>$id, 'competition_id'=>absint($complaint->competition_id),
                    'target_type'=>'student', 'target_id'=>$complaint->student_id, 'target_code'=>$complaint->student_code,
                    'school_name'=>$complaint->school_name, 'created_by'=>get_current_user_id(),
                    'subject_name'=>$complaint->student_name, 'reason'=>$complaint->complaint_subject . "\n\n" . $complaint->complaint_text . ($notes ? "\n\nБелешка од тестатор: ".$notes : ''),
                    'status'=>'pending'
                ))) {
                    wp_send_json_error(array('message'=>'Жалбата е проследена, но барањето до Комисијата не можеше да се зачува.'));
                }
            }
        }
        wp_send_json_success();
    }
    /**
     * AJAX Дисквалификација на ученик/ментор/тестатор
     */
    public function ajax_disqualify_target() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        $user = wp_get_current_user();
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { wp_send_json_error(array('message'=>'Користете ја дисквалификацијата од доделената категорија.'),403); }
        $is_commission = in_array('zmp_commission', (array) $user->roles, true) || current_user_can('manage_options');
        $is_testator = in_array('zmp_testator', (array) $user->roles, true);
        if (!$is_commission && !$is_testator) { wp_send_json_error(array('message' => 'Немате овластување.')); }
        $type = sanitize_key($_POST['target_type'] ?? '');
        $name = sanitize_text_field($_POST['target_name'] ?? '');
        $code = sanitize_text_field($_POST['target_code'] ?? '');
        $school = sanitize_text_field($_POST['school_name'] ?? '');
        $reason = sanitize_textarea_field($_POST['detailed_reason'] ?? '');
        if (!in_array($type, array('student', 'mentor', 'testator'), true) || $name === '' || $reason === '') { wp_send_json_error(array('message' => 'Проверете ги задолжителните полиња.')); }
        if ($is_testator && !$is_commission && $type !== 'student') { wp_send_json_error(array('message' => 'Тестаторите можат да дисквалификуваат само ученици.')); }
        global $wpdb;
        $target_id = absint($_POST['target_id'] ?? 0);
        if ($type === 'student') {
            $student = $wpdb->get_row($wpdb->prepare("SELECT id, school_name FROM {$wpdb->prefix}zmp_students WHERE access_code = %s", $code));
            if (!$student || ($is_testator && !$is_commission && $student->school_name !== get_user_meta($user->ID, 'zmp_school_name', true))) {
                wp_send_json_error(array('message' => 'Ученикот не е пронајден во дозволеното училиште.'));
            }
            $target_id = absint($student->id);
            $school = $student->school_name;
        }
        if ($is_testator && !$is_commission) {
            $ok = $wpdb->insert($wpdb->prefix . 'zmp_commission_decisions', array(
                'decision_type'=>'disqualification', 'source_type'=>'testator_request', 'competition_id'=>absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")), 'target_type'=>$type,
                'target_id'=>$target_id, 'target_code'=>$code, 'school_name'=>$school ?: 'Не е наведено',
                'created_by'=>$user->ID, 'subject_name'=>$name, 'reason'=>$reason, 'status'=>'pending'
            ));
            if (!$ok) { wp_send_json_error(array('message' => 'Барањето не можеше да се испрати до Комисијата.')); }
            wp_send_json_success(array('message' => 'Барањето е испратено до Комисијата за одлука.'));
        }
        $ok = $wpdb->insert($wpdb->prefix . 'zmp_disqualifications', array(
            'target_type' => $type, 'target_id' => $target_id, 'competition_id' => absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1")), 'target_name' => $name,
            'target_code' => $code, 'school_name' => $school ?: 'Не е наведено',
            'disqualified_by_user_id' => $user->ID, 'disqualified_by_name' => $user->display_name,
            'disqualified_by_role' => 'Испитна комисија', 'detailed_reason' => $reason
        ));
        if (!$ok) { wp_send_json_error(array('message' => 'Одлуката не можеше да се зачува.')); }
        if ($type === 'student') {
            $wpdb->update($wpdb->prefix . 'zmp_attempts', array('status' => 'disqualified', 'reset_reason' => 'Дисквалификуван: ' . $reason), array('student_id' => $target_id));
        }
        wp_send_json_success(array('message' => 'Дисквалификацијата е запишана.'));
    }
    public function ajax_moderator_live_students() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if ((class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) || !$this->zmp_user_has_role(array('zmp_moderator','zmp_testator'))) { wp_send_json_error(array('message' => 'Овој преглед е ограничен на тестатори и модератори.'), 403); }
        global $wpdb;
        $competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1"));
        $rows = $competition_id ? $wpdb->get_results($wpdb->prepare(
            "SELECT a.id,a.remaining_seconds,a.is_paused,a.last_active_at,s.first_name,s.last_name,s.access_code,s.school_name,s.municipality,s.grade,
                (SELECT i.id FROM {$wpdb->prefix}zmp_incidents i WHERE i.competition_id=a.competition_id AND i.student_id=s.id AND i.status<>'resolved' ORDER BY i.created_at DESC,i.id DESC LIMIT 1) AS incident_id
             FROM {$wpdb->prefix}zmp_attempts a JOIN {$wpdb->prefix}zmp_students s ON s.id=a.student_id
             WHERE a.competition_id=%d AND a.status='in_progress' ORDER BY a.last_active_at DESC", $competition_id
        )) : array();
        wp_send_json_success(array_map(function($r) { return array('id'=>(int)$r->id, 'incident_id'=>(int)$r->incident_id, 'remaining'=>(int)$r->remaining_seconds, 'paused'=>(bool)$r->is_paused, 'active'=>$r->last_active_at, 'name'=>$r->first_name.' '.$r->last_name, 'code'=>$r->access_code, 'school'=>$r->school_name.' ('.$r->municipality.')', 'grade'=>(int)$r->grade); }, $rows));
    }

    /**
     * AJAX Тестирање на врска со датабаза од Администраторската табла
     */
    public function ajax_test_db_connection() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Немате права.'));
        }

        $host = sanitize_text_field($_POST['db_host']);
        $user = sanitize_text_field($_POST['db_user']);
        $pass = sanitize_text_field($_POST['db_pass']);
        $name = sanitize_text_field($_POST['db_name']);

        if (empty($host)) {
            $host = '127.0.0.1';
        }

        // Обиди се со mysqli
        if (function_exists('mysqli_connect')) {
            $conn = @mysqli_connect($host, $user, $pass, $name);
            if ($conn) {
                mysqli_close($conn);
                wp_send_json_success(array('message' => 'Успешно воспоставена врска со датабазата на ' . $host . '!'));
            } else {
                wp_send_json_error(array('message' => 'Грешка при поврзување: ' . mysqli_connect_error()));
            }
        } else {
            wp_send_json_success(array('message' => 'Поврзувањето се управува преку вградениот WordPress $wpdb драјвер.'));
        }
    }
}





