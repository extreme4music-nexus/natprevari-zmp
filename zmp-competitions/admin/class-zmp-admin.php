<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_post_zmp_export_debug_logs', array($this, 'export_debug_logs'));
    }

    public function register_admin_menus() {
        add_menu_page(
            'ЗМП Натпревари',
            'ЗМП Натпревари',
            'manage_options',
            'zmp-competitions',
            array($this, 'view_dashboard'),
            'dashicons-media-audio',
            25
        );

        add_submenu_page(
            'zmp-competitions',
            'Распоред на тестатори',
            'Распоред на тестатори',
            'manage_options',
            'zmp-testator-duties',
            array($this, 'view_testator_duties')
        );

        add_submenu_page(
            'zmp-competitions',
            'Дебагирање и Ерор Логови',
            'Дебагирање',
            'manage_options',
            'zmp-debugging',
            array($this, 'view_debugging')
        );

        add_submenu_page(
            'zmp-competitions',
            'Датабаза и Поставки',
            'Датабаза и Поставки',
            'manage_options',
            'zmp-settings',
            array($this, 'view_settings')
        );

        add_submenu_page('zmp-competitions', 'Целосно ресетирање на тестирањето', 'Целосен ресет', 'manage_options', 'zmp-full-reset', array($this, 'view_full_reset'));

        add_submenu_page('zmp-competitions', 'Факсимил на претседател', 'Факсимил', 'manage_options', 'zmp-facsimile', array('ZMP_Registration_Requirements', 'render_admin_facsimile_page'));

        add_submenu_page(
            'zmp-competitions',
            'Испитна Комисија',
            'Испитна Комисија',
            'manage_options',
            'zmp-commission',
            array($this, 'view_commission')
        );

        add_submenu_page(
            'zmp-competitions',
            'Улоги и Привилегии',
            'Улоги и Привилегии',
            'manage_options',
            'zmp-teachers',
            array($this, 'view_teachers')
        );

        add_submenu_page(
            'zmp-competitions',
            'Календар на натпревари',
            'Календар на натпревари',
            'manage_options',
            'zmp-calendar',
            array($this, 'view_calendar')
        );

        add_submenu_page(
            'zmp-competitions',
            'Инциденти во сала',
            'Инциденти во сала',
            'manage_options',
            'zmp-incidents',
            array($this, 'view_incidents')
        );

        add_submenu_page(
            'zmp-competitions',
            'Жалби и Приговори (24ч)',
            'Жалби и Приговори (24ч)',
            'manage_options',
            'zmp-complaints',
            array($this, 'view_complaints')
        );

        add_submenu_page(
            'zmp-competitions',
            'Банка на прашања',
            'Банка на прашања',
            'manage_options',
            'zmp-questions',
            array($this, 'view_questions')
        );

        add_submenu_page(
            'zmp-competitions',
            'Ученици и Ментори',
            'Ученици и Ментори',
            'manage_options',
            'zmp-students',
            array($this, 'view_students')
        );

        add_submenu_page(
            'zmp-competitions',
            'Ранг-листи и Пласман',
            'Ранг-листи и Пласман',
            'manage_options',
            'zmp-results',
            array($this, 'view_results')
        );

        // Role dashboards are available from [zmp_natprevari_portal]. Keep this
        // admin menu focused on platform-wide configuration and oversight.
    }

    /**
     * Обработка на административни акции
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['zmp_clear_debug_log'])) {
            check_admin_referer('zmp_clear_debug_log');
            delete_option(ZMP_Debug::OPTION);
            wp_safe_redirect(add_query_arg(array('page' => 'zmp-debugging', 'cleared' => '1'), admin_url('admin.php')));
            exit;
        }

        if (isset($_POST['zmp_test_debug_log'])) {
            check_admin_referer('zmp_test_debug_log');
            ZMP_Debug::log('info', 'Diagnostic self-test: admin button pressed.', array('self_test' => 1));
            wp_safe_redirect(add_query_arg(array('page' => 'zmp-debugging', 'self_test' => '1'), admin_url('admin.php')));
            exit;
        }

        if (isset($_POST['zmp_run_messenger_schema_diagnostic'])) {
            check_admin_referer('zmp_run_messenger_schema_diagnostic');
            $diagnostic = ZMP_Role_Messenger::schema_diagnostic_snapshot();
            $diagnostic['schema_notice_active'] = !empty($diagnostic['notice_active']) ? 1 : 0;
            $diagnostic['outcome'] = !empty($diagnostic['notice_active']) || ($diagnostic['schema_compatible'] ?? '') !== 'yes' ? 'failed' : 'ready';
            unset($diagnostic['notice_active']);
            ZMP_Debug::log(($diagnostic['outcome'] === 'ready') ? 'info' : 'error', 'Messenger schema diagnostic completed.', $diagnostic, 'communications');
            wp_safe_redirect(add_query_arg(array('page' => 'zmp-debugging', 'messenger_schema_check' => '1'), admin_url('admin.php')));
            exit;
        }

        if (isset($_POST['zmp_start_debug_capture'])) {
            check_admin_referer('zmp_start_debug_capture');
            $capture = ZMP_Debug::start_capture(
                sanitize_key(wp_unslash($_POST['test_type'] ?? '')),
                absint($_POST['duration_minutes'] ?? 0),
                get_current_user_id()
            );
            if (is_wp_error($capture)) { wp_die('Изберете валиден тип тест и времетраење.'); }
            ZMP_Debug::log('info', 'Diagnostic test capture started.', array('duration_minutes' => (int) $capture['duration_minutes']));
            wp_safe_redirect(add_query_arg(array('page' => 'zmp-debugging', 'capture_started' => '1'), admin_url('admin.php')));
            exit;
        }

        if (isset($_POST['zmp_stop_debug_capture'])) {
            check_admin_referer('zmp_stop_debug_capture');
            ZMP_Debug::log('info', 'Diagnostic test capture stopped.');
            ZMP_Debug::stop_capture();
            wp_safe_redirect(add_query_arg(array('page' => 'zmp-debugging', 'capture_stopped' => '1'), admin_url('admin.php')));
            exit;
        }

        global $wpdb;

        if (isset($_POST['zmp_full_testing_reset'])) {
            check_admin_referer('zmp_full_testing_reset');
            if (sanitize_text_field(wp_unslash($_POST['confirmation'] ?? '')) !== 'RESET ZMP') { wp_die('Потврдата не е точна. Ништо не е избришано.'); }
            if (!self::full_reset_is_allowed()) { wp_die('Целосниот ресет е заклучен бидејќи веќе има завршена натпреварувачка година.'); }
            if (!self::perform_full_testing_reset()) { wp_die('Ресетот не е завршен: базата одбила бришење. Проверете ги логовите и повторете по поправката.'); }
            wp_safe_redirect(admin_url('admin.php?page=zmp-full-reset&reset_done=1')); exit;
        }

        if (isset($_POST['zmp_save_testator_duty'])) {
            check_admin_referer('zmp_save_testator_duty');
            $id = absint($_POST['duty_id'] ?? 0);
            $active_competition_id = absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1"));
            $data = array(
                'testator_user_id' => absint($_POST['testator_user_id'] ?? 0),
                'competition_id' => $active_competition_id,
                'duty_date' => sanitize_text_field($_POST['duty_date'] ?? ''),
                'phase' => sanitize_key($_POST['phase'] ?? 'school'),
                'location' => sanitize_text_field($_POST['location'] ?? ''),
                'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            );
            $testator = get_user_by('id', $data['testator_user_id']);
            $date_parts = array_map('intval', explode('-', $data['duty_date']));
            $valid_date = count($date_parts) === 3 && checkdate($date_parts[1], $date_parts[2], $date_parts[0]);
            if (!$testator || !in_array('zmp_testator', (array)$testator->roles, true) || !$valid_date || !in_array($data['phase'], array('school','municipal','regional','state'), true) || $data['location'] === '') {
                wp_die('Изберете тестаторска сметка, валиден датум, натпреварувачко ниво и локација.');
            }
            if ($id) $wpdb->update($wpdb->prefix . 'zmp_testator_duties', $data, array('id'=>$id));
            else $wpdb->insert($wpdb->prefix . 'zmp_testator_duties', $data);
            wp_safe_redirect(admin_url('admin.php?page=zmp-testator-duties&updated=1')); exit;
        }
        if (isset($_GET['zmp_delete_duty'])) {
            $id = absint($_GET['zmp_delete_duty']);
            check_admin_referer('zmp_delete_duty_' . $id);
            $wpdb->delete($wpdb->prefix . 'zmp_testator_duties', array('id'=>$id), array('%d'));
            wp_safe_redirect(admin_url('admin.php?page=zmp-testator-duties&deleted=1')); exit;
        }

        // 1. Зачувување Поставки за Датабаза и Профилни полиња
        if (isset($_POST['zmp_save_db_settings'])) {
            check_admin_referer('zmp_db_settings_nonce');
            $table_settings = $wpdb->prefix . 'zmp_settings';

            $host    = sanitize_text_field($_POST['db_host']);
            $user    = sanitize_text_field($_POST['db_user']);
            $pass    = sanitize_text_field($_POST['db_pass']);
            $name    = sanitize_text_field($_POST['db_name']);
            $custom  = isset($_POST['custom_db_enabled']) ? 1 : 0;
            $fields  = isset($_POST['profile_fields_enabled']) ? 1 : 0;

            if (empty($host)) {
                $host = '127.0.0.1';
            }

            $exists = $wpdb->get_row("SELECT id FROM $table_settings LIMIT 1");
            if ($exists) {
                $wpdb->update($table_settings, array(
                    'db_host'                => $host,
                    'db_user'                => $user,
                    'db_pass'                => $pass,
                    'db_name'                => $name,
                    'custom_db_enabled'      => $custom,
                    'profile_fields_enabled' => $fields
                ), array('id' => $exists->id));
            } else {
                $wpdb->insert($table_settings, array(
                    'db_host'                => $host,
                    'db_user'                => $user,
                    'db_pass'                => $pass,
                    'db_name'                => $name,
                    'custom_db_enabled'      => $custom,
                    'profile_fields_enabled' => $fields
                ));
            }

            wp_redirect(add_query_arg('msg', 'settings_saved', admin_url('admin.php?page=zmp-settings')));
            exit;
        }

        // 2. Додавање Член на Испитната Комисија
        if (isset($_POST['zmp_add_commission_member'])) {
            check_admin_referer('zmp_commission_nonce');
            $table_comm = $wpdb->prefix . 'zmp_commission';

            $user_id     = intval($_POST['user_id']);
            $full_name   = sanitize_text_field($_POST['full_name']);
            $institution = sanitize_text_field($_POST['institution']);
            $title       = sanitize_text_field($_POST['academic_title']);
            $email       = sanitize_email($_POST['email']);
            $phone       = sanitize_text_field($_POST['phone']);

            if ($user_id > 0) {
                $commission_user = get_user_by('id', $user_id);
                if ($commission_user && in_array('zmp_president', (array)$commission_user->roles, true)) {
                    wp_redirect(add_query_arg('msg', 'president_cannot_be_commission', admin_url('admin.php?page=zmp-commission')));
                    exit;
                }
            }

            if ($user_id > 0) {
                $commission_user = get_user_by('id', $user_id);
                if ($commission_user && function_exists('zmp_competitions_is_president_user') && zmp_competitions_is_president_user($commission_user)) {
                    wp_redirect(add_query_arg('msg', 'president_cannot_be_commission', admin_url('admin.php?page=zmp-commission')));
                    exit;
                }
            }

            $wpdb->insert($table_comm, array(
                'user_id'        => $user_id,
                'full_name'      => $full_name,
                'institution'    => $institution,
                'academic_title' => $title,
                'email'          => $email,
                'phone'          => $phone,
                'is_active'      => 1
            ));

            // Автоматски додели улога zmp_commission на корисникот
            if ($user_id > 0) {
                $u = get_user_by('id', $user_id);
                if ($u) {
                    $u->add_role('zmp_commission');
                }
            }

            wp_redirect(add_query_arg('msg', 'member_added', admin_url('admin.php?page=zmp-commission')));
            exit;
        }

        // Бришење член на комисија
        if (isset($_GET['action']) && $_GET['action'] === 'delete_commission_member' && isset($_GET['mid'])) {
            check_admin_referer('zmp_delete_comm_nonce');
            $table_comm = $wpdb->prefix . 'zmp_commission';
            $mid = intval($_GET['mid']);

            $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_comm WHERE id = %d", $mid));
            if ($member && $member->user_id > 0) {
                $u = get_user_by('id', $member->user_id);
                if ($u) {
                    $u->remove_role('zmp_commission');
                }
            }

            $wpdb->delete($table_comm, array('id' => $mid));
            wp_redirect(add_query_arg('msg', 'member_deleted', admin_url('admin.php?page=zmp-commission')));
            exit;
        }

        // 3. Доделување / Одземање улоги (Наставник, Тестатор, Модератор, Комисија)
        if (isset($_GET['action']) && isset($_GET['role']) && isset($_GET['user_id'])) {
            check_admin_referer('zmp_roles_nonce');
            $user_id = intval($_GET['user_id']);
            $role    = sanitize_text_field($_GET['role']);
            $action  = sanitize_text_field($_GET['action']);

            $user = get_user_by('id', $user_id);
            $role_message = 'role_updated';
            if ($user && 'zmp_president' === $role) {
                $role_message = (in_array('administrator', (array) $user->roles, true) || $user->has_cap('manage_options'))
                    ? 'president_admin_account_denied'
                    : 'president_mandate_required';
            } elseif ($user && in_array($role, array('zmp_teacher', 'zmp_testator', 'zmp_moderator', 'zmp_commission'), true)) {
                if ($action === 'grant_role') {
                    if (in_array('zmp_president', (array)$user->roles, true)) {
                        $role_message = 'president_must_be_revoked_first';
                    } else {
                        $user->add_role($role);
                    }
                } elseif ($action === 'revoke_role') {
                    $user->remove_role($role);
                }
            }
            wp_redirect(add_query_arg('msg', $role_message, admin_url('admin.php?page=zmp-teachers')));
            exit;
        }

        // 4. Ажурирање Календар
        if (isset($_POST['zmp_save_calendar'])) {
            check_admin_referer('zmp_calendar_nonce');
            $table_cal = $wpdb->prefix . 'zmp_calendar';

            $cal_id    = intval($_POST['cal_id']);
            $phase     = sanitize_text_field($_POST['phase']);
            $title     = sanitize_text_field($_POST['title']);
            $start_dt  = sanitize_text_field($_POST['start_datetime']);
            $end_dt    = sanitize_text_field($_POST['end_datetime']);
            $duration  = intval($_POST['duration_minutes']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($cal_id > 0) {
                $wpdb->update($table_cal, array(
                    'title'            => $title,
                    'start_datetime'   => $start_dt,
                    'end_datetime'     => $end_dt,
                    'duration_minutes' => $duration,
                    'is_active'        => $is_active
                ), array('id' => $cal_id));
            } else {
                $wpdb->insert($table_cal, array(
                    'phase'            => $phase,
                    'title'            => $title,
                    'start_datetime'   => $start_dt,
                    'end_datetime'     => $end_dt,
                    'duration_minutes' => $duration,
                    'is_active'        => $is_active
                ));
            }

            wp_redirect(add_query_arg('msg', 'calendar_saved', admin_url('admin.php?page=zmp-calendar')));
            exit;
        }

        // 5. Решавање Жалба
        if (isset($_POST['zmp_resolve_complaint'])) {
            check_admin_referer('zmp_complaint_resolve_nonce');
            wp_die(esc_html__('Жалбите се решаваат преку порталот на Испитната комисија, каде што се задолжителни фактичко образложение и ревизорска трага на бодовите.', 'zmp-competitions'));
        }

        // 6. Експорт во Excel
        if (isset($_GET['zmp_export']) && $_GET['zmp_export'] === 'excel') {
            $phase = isset($_GET['phase']) ? sanitize_text_field($_GET['phase']) : 'all';
            $grade = isset($_GET['grade']) ? sanitize_text_field($_GET['grade']) : 'all';
            $region = sanitize_key(wp_unslash($_GET['zmp_admin_region'] ?? ''));
            $municipality = sanitize_text_field(wp_unslash($_GET['zmp_admin_municipality'] ?? ''));
            $school = sanitize_text_field(wp_unslash($_GET['zmp_admin_school'] ?? ''));
            ZMP_Scoring::export_to_excel_csv($phase, $grade, $region, $municipality, $school);
            exit;
        }

        // 7. Квалификации
        if (isset($_POST['zmp_run_qualifications'])) {
            check_admin_referer('zmp_qualifications_nonce');
            $phase = sanitize_text_field($_POST['phase_to_qualify']);
            ZMP_Scoring::process_phase_qualifications($phase);
            wp_redirect(add_query_arg('msg', 'qualifications_calculated', admin_url('admin.php?page=zmp-results')));
            exit;
        }

        // 8. Додавање прашање
        if (isset($_POST['zmp_add_question'])) {
            check_admin_referer('zmp_add_question_nonce');
            $table = $wpdb->prefix . 'zmp_questions';

            $options = array();
            for ($i = 0; $i < 4; $i++) {
                $options[] = array(
                    'text_mk' => sanitize_text_field($_POST['option_mk'][$i]),
                    'text_sq' => sanitize_text_field($_POST['option_sq'][$i])
                );
            }

            $points = isset($_POST['points']) ? intval($_POST['points']) : 0;
            if ($points < 1 || $points > 20 || !in_array(intval($_POST['correct_option_index'] ?? -1), array(0,1,2,3), true)) { wp_die('Бодовите мора да бидат цел број од 1 до 20, со еден точен одговор.'); }
            $wpdb->insert($table, array(
                'grade'               => intval($_POST['grade']),
                'phase'               => sanitize_text_field($_POST['phase']),
                'question_text_mk'    => sanitize_textarea_field($_POST['question_text_mk']),
                'question_text_sq'    => sanitize_textarea_field($_POST['question_text_sq']),
                'question_type'       => sanitize_text_field($_POST['question_type']),
                'audio_url'           => esc_url_raw($_POST['audio_url']),
                'image_url'           => esc_url_raw($_POST['image_url']),
                'options_json'        => json_encode($options),
                'correct_option_index'=> intval($_POST['correct_option_index']),
                'points'              => $points
            ));

            wp_redirect(add_query_arg('msg', 'question_added', admin_url('admin.php?page=zmp-questions')));
            exit;
        }

        // 9. Бришење прашање
        if (isset($_GET['action']) && $_GET['action'] === 'delete_question' && isset($_GET['qid'])) {
            check_admin_referer('zmp_delete_q_nonce');
            $question_id = absint($_GET['qid']);
            $attempt_sets = $wpdb->get_col("SELECT question_ids_json FROM {$wpdb->prefix}zmp_attempts WHERE question_ids_json IS NOT NULL");
            foreach ((array)$attempt_sets as $attempt_set) {
                if (in_array($question_id, array_map('absint', (array)json_decode($attempt_set, true)), true)) { wp_die('Прашањето не може да се избрише бидејќи е зачувано во еден или повеќе тест-обиди.'); }
            }
            $wpdb->delete($wpdb->prefix . 'zmp_questions', array('id' => $question_id));
            wp_redirect(add_query_arg('msg', 'question_deleted', admin_url('admin.php?page=zmp-questions')));
            exit;
        }
    }

    /** A completed season protects the full archive from the pre-completion reset. */
    public static function full_reset_is_allowed() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE workflow_status='completed'") === 0;
    }

    /** Clears every ZMP competition row and mix setting, then seeds a blank season shell. */
    public static function perform_full_testing_reset() {
        global $wpdb;
        if (!self::full_reset_is_allowed()) { return false; }
        $archives = $wpdb->get_col("SELECT file_path FROM {$wpdb->prefix}zmp_competition_archives");
        $roster_rows = ZMP_Category_Registrations::roster_rows_for_season();
        $entry_document_rows = ZMP_Registration_Requirements::all_document_rows();
        $student_document_rows = ZMP_Registration_Requirements::all_student_document_rows();
        $registration_form_rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_registration_forms", ARRAY_A);
        // Preflight external YouTube storage before deleting Drive files or certificates.
        if (class_exists('ZMP_YouTube') && !ZMP_YouTube::delete_all_videos_for_reset()) { return false; }
        if (class_exists('ZMP_Certificate_Templates') && !ZMP_Certificate_Templates::delete_all_for_reset()) { return false; }
        $notice_post_ids = array_map('absint', (array)$wpdb->get_col("SELECT wp_post_id FROM {$wpdb->prefix}zmp_announcements WHERE wp_post_id>0"));
        if (class_exists('ZMP_Google_Drive') && !ZMP_Google_Drive::delete_all_videos_for_reset()) { return false; }
        $wpdb->query('START TRANSACTION');
        $tables = array('zmp_committee_invites','zmp_organizer_assignments','zmp_member_audit','zmp_certificate_templates','zmp_youtube_media','zmp_jury_correction_requests','zmp_jury_audit','zmp_category_final_results','zmp_jury_scores','zmp_jury_assessments','zmp_category_awards','zmp_scoring_criteria','zmp_jury_assignments','zmp_operations_audit','zmp_stage_tasks','zmp_stage_testator_assignments','zmp_stage_regional_events','zmp_stage_events','zmp_registration_windows','zmp_region_change_requests','zmp_notifications','zmp_announcements','zmp_entry_documents','zmp_student_documents','zmp_fee_requests','zmp_registration_forms','zmp_category_fees','zmp_category_media','zmp_category_entries','zmp_internal_messages','zmp_incidents','zmp_complaints','zmp_commission_decisions','zmp_disqualifications','zmp_testator_duties','zmp_moderator_actions','zmp_score_adjustments','zmp_phase_results','zmp_phase_status','zmp_attempts','zmp_students','zmp_mentors','zmp_commission','zmp_questions','zmp_calendar','zmp_competition_archives','zmp_reset_log','zmp_settings','zmp_competitions');
        foreach ($tables as $suffix) { if ($wpdb->query('DELETE FROM ' . $wpdb->prefix . $suffix) === false) { $wpdb->query('ROLLBACK'); return false; } }
        $options_table = $wpdb->prefix . 'options';
        $mix_options = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $options_table WHERE option_name LIKE %s", $wpdb->esc_like('zmp_question_mix_') . '%'));
        foreach ((array)$mix_options as $option_name) { delete_option($option_name); }
        if ($wpdb->query($wpdb->prepare("DELETE FROM $options_table WHERE option_name LIKE %s", $wpdb->esc_like('zmp_question_mix_') . '%')) === false) { $wpdb->query('ROLLBACK'); return false; }
        $year = (int)wp_date('Y'); if ((int)wp_date('n') < 7) { $year--; }
        $academic_year = $year . '/' . ($year + 1);
        if (!$wpdb->insert($wpdb->prefix . 'zmp_competitions', array('title'=>'Државен натпревар по музичко образование ' . $academic_year,'academic_year'=>$academic_year,'current_phase'=>'school','duration_minutes'=>45,'is_active'=>1,'workflow_status'=>'active'))) { $wpdb->query('ROLLBACK'); return false; }
        $new_competition_id = (int)$wpdb->insert_id;
        foreach (array('school','municipal','regional','state') as $phase) {
            if (!$wpdb->insert($wpdb->prefix . 'zmp_phase_status', array('competition_id'=>$new_competition_id,'phase'=>$phase,'status'=>'open'))) { $wpdb->query('ROLLBACK'); return false; }
        }
        $wpdb->query('COMMIT');
        foreach ($notice_post_ids as $notice_post_id) {
            $post = get_post($notice_post_id);
            if ($post && $post->post_type === 'post' && (int)get_post_meta($notice_post_id, '_zmp_announcement_id', true) > 0) { wp_delete_post($notice_post_id, true); }
        }
        ZMP_Category_Registrations::remove_roster_files((array)$roster_rows);
        ZMP_Registration_Requirements::remove_entry_document_files((array)$entry_document_rows);
        ZMP_Registration_Requirements::remove_student_document_files((array)$student_document_rows);
        ZMP_Registration_Requirements::remove_form_files((array)$registration_form_rows);
        foreach ((array)$archives as $archive_file) {
            $real = realpath($archive_file); $upload = realpath(wp_upload_dir()['basedir']);
            if ($real && $upload && strpos($real, $upload . DIRECTORY_SEPARATOR) === 0 && basename($real) === basename($archive_file) && preg_match('/^zmp-[0-9]+-[0-9]{8}-[0-9]{6}-[A-Za-z0-9]{8}\.zip$/', basename($real))) { @unlink($real); }
        }
        return true;
    }

    /**
     * Контролна табла (Dashboard)
     */
    public function view_testator_duties() {
        if (!current_user_can('manage_options')) wp_die('Недоволни привилегии.');
        global $wpdb;
        $users = get_users(array('role'=>'zmp_testator', 'orderby'=>'display_name', 'order'=>'ASC'));
        $rows = $wpdb->get_results("SELECT d.*, u.display_name FROM {$wpdb->prefix}zmp_testator_duties d LEFT JOIN {$wpdb->users} u ON u.ID=d.testator_user_id ORDER BY d.duty_date DESC, u.display_name ASC");
        $editing = !empty($_GET['edit_duty']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_testator_duties WHERE id=%d", absint($_GET['edit_duty']))) : null;
        ?>
        <div class="wrap"><h1>Распоред на тестатори</h1>
        <?php if (isset($_GET['updated']) || isset($_GET['deleted'])): ?><div class="notice notice-success"><p>Распоредот е ажуриран.</p></div><?php endif; ?>
        <form method="post" class="card" style="max-width:760px;padding:16px">
            <?php wp_nonce_field('zmp_save_testator_duty'); ?>
            <input type="hidden" name="zmp_save_testator_duty" value="1">
            <input type="hidden" name="duty_id" value="<?php echo esc_attr($editing->id ?? 0); ?>">
            <p><label>Тестатор<br><select name="testator_user_id" required><option value="">Изберете тестатор</option><?php foreach ($users as $u): ?><option value="<?php echo esc_attr($u->ID); ?>" <?php selected((int)($editing->testator_user_id ?? 0),(int)$u->ID); ?>><?php echo esc_html($u->display_name); ?></option><?php endforeach; ?></select></label></p>
            <p><label>Датум <input type="date" name="duty_date" required value="<?php echo esc_attr($editing->duty_date ?? ''); ?>"></label>
            <label>Натпреварувачко ниво <select name="phase"><?php foreach(array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво') as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($editing->phase ?? 'school',$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label></p>
            <p><label>Локација <input class="regular-text" name="location" required value="<?php echo esc_attr($editing->location ?? ''); ?>"></label></p>
            <p><label>Белешки<br><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea($editing->notes ?? ''); ?></textarea></label></p>
            <?php submit_button($editing ? 'Зачувај промени' : 'Додели дежурство'); ?>
        </form><h2>Доделени дежурства</h2><table class="wp-list-table widefat striped"><thead><tr><th>Датум</th><th>Тестатор</th><th>Натпреварувачко ниво</th><th>Локација</th><th>Белешки</th><th>Акции</th></tr></thead><tbody>
        <?php foreach($rows as $row): ?><tr><td><?php echo esc_html($row->duty_date); ?></td><td><?php echo esc_html($row->display_name ?: 'Избришан корисник'); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($row->phase)); ?></td><td><?php echo esc_html($row->location); ?></td><td><?php echo esc_html($row->notes); ?></td><td><a href="<?php echo esc_url(admin_url('admin.php?page=zmp-testator-duties&edit_duty='.$row->id)); ?>">Уреди</a> | <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-testator-duties&zmp_delete_duty='.$row->id),'zmp_delete_duty_'.$row->id)); ?>" onclick="return confirm('Да се избрише дежурството?')">Избриши</a></td></tr><?php endforeach; ?>
        <?php if(!$rows): ?><tr><td colspan="6">Сѐ уште нема доделени дежурства.</td></tr><?php endif; ?></tbody></table></div>
        <?php
    }

    private static function whats_new_releases() {
        return array(
            '1.2.4.66.a' => array(
                'Паски за активниот Претседател: од личниот WordPress профил регистрирајте, прегледајте или отстранете сопствен passkey. За регистрација и отстранување се бара тековната лозинка. Делот се прикажува само со активен претседателски мандат или веќе регистриран passkey.',
                'На WordPress страницата за најава е достапно најавување со passkey; вообичаеното најавување со корисничко име и лозинка останува достапно.',
                'Одлуките на Претседателот за предложен предмет бараат свеж passkey: одобри како нов, поврзи како алијас или одбиј. Откажан или неуспешен authenticator не ја поднесува одлуката.',
                'Профилот на наставникот/Претседателот ги зачувува училишните ангажмани и полињата за видливост по училиште. По зачувување, повторно отворете го профилот и потврдете ги вредностите.',
                'Улогите се комбинираат: секој член мора да има subscriber; zmp_teacher е дополнителна улога. Претседателот мора да има subscriber и zmp_teacher; дополнителните дозволени улоги не смеат да ги исклучат другите функции.',
                'Назначување прв Претседател: администраторот ја евидентира одлуката. Кандидатот мора да има subscriber и zmp_teacher; администраторските и дозволените ЗМП улоги може да се комбинираат и се зачувуваат.',
                'При ажурирање од .65 на .66.a се извршува поправката на Messenger шемата; ако базата содржи застарен scoped_threads индекс, плагинот го заменува без да ги избрише пораките.',
                'Шемата за passkey има посебна проверка и миграција. При неподготвена или делумно мигрирана шема текот запира без да зачува одлука или да создаде автентицирана сесија.',
                'Дебагирањето е проширено со безбедни настани за passkey и профилните текови; не ги запишува PIN-от, лозинката, assertion-от, challenge-вредноста или содржината на барањето.',
                'Проверка од сопственик: користете тест-сметка со subscriber и zmp_teacher, активен претседателски мандат и синтетички предложен предмет. Потврдете регистрација, најава, одлука со свеж passkey, откажување без поднесување и зачувување/повторно читање на профилот.',
            ),
            '1.2.4.65' => array(
                'Во тек е интеграциска и приватносна проверка на претседателските текови, училишниот регистар и службената е-пошта.',
                'Претседателот може доброволно да дозволи приказ на администраторски избрана локална фотографија; согласноста е исклучена по дифолт и важи само за активен мандат.',
                'Во проверка се обработката на неподготвена шема и разликувањето на одбиена URL-проверка од потврдено недостапна алатка.',
                'Дијагностиката за schema_not_ready бележи само исчистено име на AJAX-дејството; содржината на барањето не се бележи.',
            ),
            '1.2.4.64' => array(
                'Службената е-пошта и статусот на вработување се евидентираат одделно за секој училишен ангажман.',
                'Пристап до службена адреса се дава само по конкретна, евидентирана одлука на активниот Претседател.',
                'Во внатрешниот директориум се прикажуваат само полињата за вработување што корисникот ги вклучил.',
            ),
            '1.2.4.63' => array(
                'Наставниците можат да предложат нов предмет; активниот Претседател може да го одобри, поврзе како алијас или одбие.',
                'Училишниот регистар поддржува пребарување, страници и приватносно филтриран CSV-извоз за овластени улоги.',
                'Дијагностичките записи за предлози и регистарот ги ограничуваат чувствителните податоци.',
            ),
            '1.2.4.62' => array(
                'Наставничкиот профил поддржува повеќе училишта со општина и автоматски одреден регион за секое училиште.',
                'За секое училиште се евидентираат одобрени предмети и одделенијата/годините што наставникот ги предава; изборот е поврзан со заедничкиот регистар.',
                'Наставникот може да побара корекција на регионот преку допис до модераторите; одобрената промена важи само за избраното училиште, а старите пријави ја чуваат својата снимка.',
            ),            '1.2.4.61' => array(
                'На јавната почетна страна се прикажува тековниот претседател и периодот на мандатот без приватни контактни или изборни податоци.',
                'Администраторот може да го постави датумот на објава и пораката за состојба без активен мандат; одговорот се освежува без кеширање.',
                'Навигацијата на порталот се распоредува и на тесен екран, со видлив фокус од тастатура.',
            ),
            '1.2.4.60' => array(
                'Додаден е контролиран премин кон нов претседател, со ограничен пристап на претходниот носител до неговите сопствени евидентирани документи за време на предавањето.',
                'Факсимилот на претходниот претседател не се користи за нови документи без важечко овластување; потврдата, отповикувањето и историјата се евидентираат.',
                'Поправена е петминутната пауза за повторен обид при неусогласена Messenger шема; миграцијата повторно се обидува по рокот или веднаш при реактивација.',
            ),            '1.2.4.59' => array(
                'Новоиздадена PDF диплома се создава само ако Ed25519 сертификатот е валиден за тековниот мандат, точниот факсимил, неговиот hash и дозволената употреба.',
                'Изменет, истечен, отповикан или несоодветен сертификат го запира создавањето нова диплома; веќе издадените PDF дипломи остануваат непроменливи во архивата.',
                'Во „Дебагирање“ е додаден посебен тип за проверка и примена на претседателскиот факсимил.',
            ),
            '1.2.4.58' => array(
                'Претседателскиот јавен Ed25519 клуч се регистрира преку манифест потпишан офлајн; приватниот клуч не се внесува ниту се чува во WordPress.',
                'Манифестот врзува активен мандат, сериски број, отпечаток на јавниот клуч, точен факсимил, дозволена употреба и период на важност.',
                'Администратор може да отповика компромитиран сертификат; повторното издавање чува историја, а примена врз документи е планирана за .59.',
            ),
            '1.2.4.57' => array(
                'Наставничкиот портал има видлив дел „Котизација и барање фактура“, независен од отворените категории; наставничкиот и модераторскиот тек остануваат одделни.',
                'Изборните записници и одлуки се поврзуваат со претседателскиот мандат, а текстот за правните и организациските обврски се чува верзионирано.',
                'Претседателот потврдува конкретна верзија на обврските; записот ја врзува потврдата со мандатот, прикажаниот текст и времето.',
                'Претседателскиот мандат трае четири години; предавањето има почетен рок од три месеци со ревидирани едномесечни продолжувања, а документите се архивираат шифрирани без трајно бришење.',
                'Аудио-алатките ги одбиваат идните timestamps за автоматска проверка; URL-полињата за приватност и услови се задолжителни пред рачно одобрување.',
            ),
            '1.2.4.55' => array(
                'Задолжителните рачни проверки на аудио-алатките се групирани со јасна насловна ознака за полесно користење со читач на екран.',
                'Административниот каталог со шест колони може хоризонтално да се движи со тастатура и на тесен екран.',
            ),
            '1.2.4.54' => array(
                'Наставничкиот портал доби таб „Матрици“ со рачно одобрени алатки за отстранување вокал и промена на тоналитет.',
                'Кај наставниците се прикажуваат само рачно одобрени алатки со свежа успешна проверка на достапноста.',
                'Администраторите добија каталог за кандидати, проверка на услови и права и задолжително рачно одобрување; YouTube downloader-ите се исклучени.',
                'Автоматската проверка опфаќа најмногу две одобрени алатки дневно и до 1 KB одговор; пренасочување, недостапност или промена ја сокрива алатката и бара ново одобрување.',
                'Во „Дебагирање“ е додаден посебен capture за Матрици и аудио-алатки; URL query-параметрите и вредностите од формите не се логираат.',
            ),
            '1.2.4.53' => array(
                'Додадени се засебни месинџер-простори за Модератори, Тестатори, Организатори и Комисии.',
                'Комисиските разговори се ограничени по натпреварувачка година, категорија и регион или национално назначување.',
                'Додадени се пребарување, броеви за непрочитани пораки, приватни прилози и CSV-извоз.',
                'Серверот повторно го проверува членството при читање, испраќање, извоз и преземање.',
            ),
            '1.2.4.49' => array(
                'Ограничен е пристапот на комисијата до резултати и документи според доделен регион.',
                'Додадени се проверки за спречување преглед, измена и преземање податоци од друг регион.',
                'Додадени се регионални безбедносни регресиски проверки.',
            ),
            '1.2.4.50' => array(
                'Додадени се посебни годишни котизации и барања за фактура.',
                'Едно барање може да опфати повеќе категории и ученици, групирани по училиште.',
                'Износите се пресметуваат и проверуваат на серверот; исклучоците се евидентираат.',
            ),
            '1.2.4.51' => array(
                'Начинот на регионалниот натпревар за музичките категории се поставува одделно за секој регион.',
                'Регионалното жири може да се назначи независно од државното жири.',
                'Пристапот до оценување, видеа, дипломи и резултати се проверува според назначената категорија, етапа и регион.',
                'CSV-извозите ги неутрализираат опасните формули во текстуалните полиња.',
            ),
            '1.2.4.52' => array(
                'Додадена е посебна улога Претседател со read-only надзорен приказ.',
                'Напредокот се прикажува само збирно по година, категорија, регион, група и натпреварувачко ниво.',
                'Надзорниот приказ не открива имиња, училишта, пораки, видеа или поединечни пријави.',
            ),
            '1.2.4.56' => array(
                'Претседателот се назначува преку мандат со датумите и одлуката на Управниот одбор.',
                'Назначувањето, промената, почетокот, истекот и отповикувањето се зачувуваат во ревизиска историја.',
                'Пристапот важи само во периодот на мандатот и не додава администраторски права.',
            ),
        );
    }

    public function view_dashboard() {
        global $wpdb;
        $release_notes = self::whats_new_releases();
        $selected_release_value = $_GET['zmp_whats_new_version'] ?? ZMP_VERSION;
        $selected_release = is_scalar($selected_release_value)
            ? sanitize_text_field(wp_unslash((string) $selected_release_value))
            : ZMP_VERSION;
        if (!isset($release_notes[$selected_release])) { $selected_release = ZMP_VERSION; }
        $total_students = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_students");
        $total_mentors  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentors");
        $total_attempts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_attempts WHERE status = 'completed'");
        $open_incidents = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_incidents WHERE status = 'unread' OR status = 'open'");
        $open_complaints= $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_complaints WHERE status = 'pending'");
        $comm_count     = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_commission WHERE is_active = 1");

        $comp = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $phase_labels = array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво');
        $current_phase = (string)($comp->current_phase ?? 'school');
        $current_phase_label = isset($phase_labels[$current_phase]) ? $phase_labels[$current_phase] : 'Не е поставено';
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>🎼 Административен преглед на натпреварите</h1>
            <p>Една почетна точка за состојбата, активностите и поставките на ЗМП Натпревари.</p>

            <section class="zmp-admin-box zmp-whats-new" data-zmp-whats-new aria-labelledby="zmp-whats-new-title">
                <h2 id="zmp-whats-new-title">Што е ново?</h2>
                <div class="zmp-whats-new-picker">
                    <label for="zmp-whats-new-version">Избери верзија</label>
                    <select id="zmp-whats-new-version" data-zmp-whats-new-select>
                        <?php foreach ($release_notes as $version => $notes): ?>
                            <option value="<?php echo esc_attr($version); ?>" <?php selected($selected_release, $version); ?>><?php echo esc_html($version); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="zmp-whats-new-notes" aria-live="polite">
                    <?php foreach ($release_notes as $version => $notes): ?>
                        <div data-zmp-whats-new-version="<?php echo esc_attr($version); ?>" <?php echo $selected_release !== $version ? 'hidden' : ''; ?>>
                            <ul>
                                <?php foreach ($notes as $note): ?><li><?php echo esc_html($note); ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="zmp-dash-grid">
                <div class="zmp-dash-card">
                    <div class="dash-val"><?php echo intval($total_students); ?></div>
                    <div class="dash-label">Пријавени ученици</div>
                </div>
                <div class="zmp-dash-card">
                    <div class="dash-val"><?php echo intval($total_mentors); ?></div>
                    <div class="dash-label">Наставници / Ментори</div>
                </div>
                <div class="zmp-dash-card">
                    <div class="dash-val" style="color: #9333ea;"><?php echo intval($comm_count); ?></div>
                    <div class="dash-label">Испитна Комисија</div>
                </div>
                <div class="zmp-dash-card">
                    <div class="dash-val" style="color: <?php echo ($open_incidents > 0) ? '#dc2626' : '#16a34a'; ?>;"><?php echo intval($open_incidents); ?></div>
                    <div class="dash-label">Дописи од Тестатори</div>
                </div>
                <div class="zmp-dash-card">
                    <div class="dash-val" style="color: <?php echo ($open_complaints > 0) ? '#d97706' : '#16a34a'; ?>;"><?php echo intval($open_complaints); ?></div>
                    <div class="dash-label">Жалби во тек (24ч)</div>
                </div>
            </div>

            <div class="zmp-admin-box">
                <h3>📌 Основни параметри на натпреварот</h3>
                <p><strong>Активно натпреварувачко ниво:</strong> <span class="zmp-badge"><?php echo esc_html($current_phase_label); ?></span></p>
                <p><strong>Време на ученик:</strong> <?php echo esc_html($comp ? $comp->duration_minutes : 45); ?> минути активно време (паузира при прекин на врска)</p>
            </div>

            <div class="zmp-admin-box">
            <section aria-labelledby="zmp-admin-quicklinks-title">
                <h2 id="zmp-admin-quicklinks-title">Брзи алатки</h2>
                <nav class="zmp-admin-quicklinks" aria-label="Административни кратенки">
                    <a class="zmp-admin-quicklink" href="<?php echo esc_url(admin_url('admin.php?page=zmp-competition-structure')); ?>">Години и категории<small>Активирај натпреварувачка година и уреди категории.</small></a>
                    <a class="zmp-admin-quicklink" href="<?php echo esc_url(admin_url('admin.php?page=zmp-calendar')); ?>">Календар<small>Прегледај ги термините на натпреварите.</small></a>
                    <a class="zmp-admin-quicklink" href="<?php echo esc_url(admin_url('admin.php?page=zmp-results')); ?>">Резултати<small>Отвори ги ранг-листите и пласманот.</small></a>
                    <a class="zmp-admin-quicklink" href="<?php echo esc_url(admin_url('admin.php?page=zmp-debugging')); ?>">Дијагностика<small>Прегледај ги логовите и извозите.</small></a>
                    <a class="zmp-admin-quicklink" href="<?php echo esc_url(admin_url('admin.php?page=zmp-settings')); ?>">Поставки<small>Провери ја конфигурацијата на плагинот.</small></a>
                </nav>
            </section>

                <details class="zmp-admin-shortcode-reference">
                <summary>Технички кратенки за страниците на веб-страницата</summary>
                <ul>
                    <li><code>[zmp_mentor_portal]</code> - Портал за наставници (пријавување, PDF картички, резултати за сопствени ученици, жалби во рок од 24ч).</li>
                    <li><code>[zmp_student_exam]</code> - Сала за онлајн тестирање (логин со шифра и лозинка, автоматско отворање според календар).</li>
                    <li><code>[zmp_testator_portal]</code> - Портал за Тестатори (дежурни во училиште, дописи до модератори, прием на жалби, дисквалификација).</li>
                    <li><code>[zmp_moderator_portal]</code> - Центар за Модератори (инбокс на дописи, мониторинг во живо на активни ученици, ресетирање тест).</li>
                    <li><code>[zmp_commission_portal]</code> - Портал за Испитна Комисија (највисоко тело: надзор, арбитража, дисквалификација на ученик/ментор/тестатор).</li>
                    <li><code>[zmp_public_results]</code> - Јавно објавување на резултатите и рангирањето.</li>
                    </ul>
                </details>
            </div>
        </div>
        <?php
    }

    /**
     * 1. АДМИНИСТРАТОРСКА КОНТРОЛНА ТАБЛА: ПОСТАВКИ И ДАТАБАЗА
     */
    public function view_settings() {
        global $wpdb;
        $table_settings = $wpdb->prefix . 'zmp_settings';
        $settings = $wpdb->get_row("SELECT * FROM $table_settings LIMIT 1");

        $db_host = $settings ? $settings->db_host : '127.0.0.1';
        $db_user = $settings ? $settings->db_user : (defined('DB_USER') ? DB_USER : '');
        $db_pass = $settings ? $settings->db_pass : (defined('DB_PASSWORD') ? DB_PASSWORD : '');
        $db_name = $settings ? $settings->db_name : (defined('DB_NAME') ? DB_NAME : '');
        $custom_db = $settings ? $settings->custom_db_enabled : 0;
        $profile_f = $settings ? $settings->profile_fields_enabled : 1;

        $comp = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE is_active = 1 LIMIT 1");
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>⚙️ Администраторска контролна табла - Датабаза и Поставки</h1>
            <p>Управувајте со пристапот до датабазата, глобалните кориснички полиња и конфигурацијата на плагинот.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'settings_saved'): ?>
                <div class="updated notice is-dismissible"><p>&check; Поставките се успешно зачувани!</p></div>
            <?php endif; ?>

            <div class="zmp-admin-box" style="max-width: 800px; border-left: 4px solid #2563eb;">
                <h3>🗄️ Конфигурација на Датабаза (Database Connection)</h3>
                <p style="font-size: 13.5px; color: #475569;">
                    Плагинот стандардно ја користи локалната датабаза на веб-страницата. Доколку сакате да дефинирате посебна адреса, внесете ја подолу (default: <code>127.0.0.1</code>).
                </p>

                <form method="POST">
                    <?php wp_nonce_field('zmp_db_settings_nonce'); ?>
                    <input type="hidden" name="zmp_save_db_settings" value="1">

                    <table class="form-table">
                        <tr>
                            <th><label for="db_host">Датабаза Адреса / Сервер (Host) *</label></th>
                            <td>
                                <input type="text" name="db_host" id="cfg_db_host" value="<?php echo esc_attr($db_host); ?>" class="regular-text" required placeholder="127.0.0.1">
                                <p class="description">Дефаулт: <strong>127.0.0.1</strong> или <strong>localhost</strong>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="db_name">Име на Датабаза (DB Name) *</label></th>
                            <td>
                                <input type="text" name="db_name" id="cfg_db_name" value="<?php echo esc_attr($db_name); ?>" class="regular-text" required placeholder="пр. muzika_db">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="db_user">Корисничко име (DB User) *</label></th>
                            <td>
                                <input type="text" name="db_user" id="cfg_db_user" value="<?php echo esc_attr($db_user); ?>" class="regular-text" required placeholder="пр. root или db_user">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="db_pass">Лозинка за Датабаза (DB Password)</label></th>
                            <td>
                                <input type="password" name="db_pass" id="cfg_db_pass" value="<?php echo esc_attr($db_pass); ?>" class="regular-text" placeholder="••••••••">
                            </td>
                        </tr>
                        <tr>
                            <th><label>Тестирање на врска</label></th>
                            <td>
                                <button type="button" id="zmp-btn-test-db" class="button button-secondary">
                                    🔌 Тестирај врска со датабазата
                                </button>
                                <span id="zmp-db-test-result" style="margin-left: 10px; font-weight: bold;"></span>
                            </td>
                        </tr>
                    </table>

                    <hr style="margin: 25px 0;">

                    <h3>👤 Глобални кориснички профили</h3>
                    <p style="font-size: 13.5px; color: #475569;">
                        Автоматски додава полиња во сите WordPress профили на наставниците и колегите за водење на натпреварот.
                    </p>

                    <table class="form-table">
                        <tr>
                            <th><label>Полиња во профилот</label></th>
                            <td>
                                <label><input type="checkbox" name="profile_fields_enabled" value="1" <?php checked($profile_f, 1); ?>> Овозможи полиња <strong>Општина</strong>, <strong>Основно училиште (ООУ)</strong> и <strong>Телефон</strong> во профилите на колегите</label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-hero">💾 Зачувај системски поставки</button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 4. АДМИНИСТРАЦИЈА: ИСПИТНА КОМИСИЈА (ЧЛЕНОВИ И ДИСКВАЛИФИКАЦИИ)
     */
    public function view_commission() {
        global $wpdb;
        $table_comm = $wpdb->prefix . 'zmp_commission';
        $table_disq = $wpdb->prefix . 'zmp_disqualifications';

        $members = $wpdb->get_results("SELECT * FROM $table_comm ORDER BY id ASC");
        $disqualifications = $wpdb->get_results("SELECT * FROM $table_disq ORDER BY created_at DESC");
        $users = get_users(array('orderby' => 'display_name'));
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>🏛️ Испитна Комисија (Највисоко Стручно Тело)</h1>
            <p>
                Испитната комисија е составена од <strong>2, 3 или најмногу 4 члена</strong> од редот на колегите од средно или високо музичко образование
                (професори од средните музички училишта или факултетите за музичка уметност).
                Таа го набљудува целиот процес, ја гарантира коректноста и има <strong>врховно право да дисквалификува ученик, ментор или тестатор</strong>.
            </p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'member_added'): ?>
                <div class="updated notice is-dismissible"><p>&check; Членот на Испитната Комисија е успешно додаден и му е доделена улогата!</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'member_deleted'): ?>
                <div class="updated notice is-dismissible"><p>Членот е отстранет од комисијата.</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_cannot_be_commission'): ?>
                <div class="error notice is-dismissible"><p>Претседателот не може да се додели и во оперативна комисија.</p></div>
            <?php endif; ?>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Формулар за додавање член -->
                <div class="zmp-admin-box" style="flex: 1; min-width: 320px; border-top: 4px solid #9333ea;">
                    <h3>➕ Додај член на Испитна Комисија</h3>
                    <form method="POST">
                        <?php wp_nonce_field('zmp_commission_nonce'); ?>
                        <input type="hidden" name="zmp_add_commission_member" value="1">

                        <div style="margin-bottom: 12px;">
                            <label><strong>Поврзи со регистриран корисник:</strong></label><br>
                            <select name="user_id" style="width: 100%;">
                                <option value="0">-- Изберете кориснички профил --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u->ID; ?>"><?php echo esc_html($u->display_name); ?> (<?php echo esc_html($u->user_login); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label><strong>Име и Презиме на колегата *</strong></label><br>
                            <input type="text" name="full_name" required style="width: 100%;" placeholder="пр. проф. м-р Гоце Костовски">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label><strong>Институција (Средно / Високо образование) *</strong></label><br>
                            <input type="text" name="institution" required style="width: 100%;" placeholder="пр. Факултет за музичка уметност - Скопје (ФМУ)">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label><strong>Академско звање / Позиција *</strong></label><br>
                            <input type="text" name="academic_title" required style="width: 100%;" placeholder="пр. Редовен професор по Солфеж и Теорија">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label><strong>Е-пошта (Email) *</strong></label><br>
                            <input type="email" name="email" required style="width: 100%;" placeholder="profesor@fmu.ukim.edu.mk">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label><strong>Телефон за контакт</strong></label><br>
                            <input type="text" name="phone" style="width: 100%;" placeholder="07X XXX XXX">
                        </div>

                        <button type="submit" class="button button-primary">Зачувај член на Испитна Комисија</button>
                    </form>
                </div>

                <!-- Тековен состав на комисијата -->
                <div class="zmp-admin-box" style="flex: 2; min-width: 450px;">
                    <h3>📋 Тековен состав (Членови: <?php echo count($members); ?> / макс 4)</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Име и Презиме</th>
                                <th>Институција</th>
                                <th>Звање</th>
                                <th>Контакт</th>
                                <th width="80">Акција</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="5">Сè уште нема внесено членови на комисијата.</td></tr>
                            <?php else: foreach ($members as $m): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($m->full_name); ?></strong></td>
                                    <td><?php echo esc_html($m->institution); ?></td>
                                    <td><span class="zmp-badge" style="background: #9333ea;"><?php echo esc_html($m->academic_title); ?></span></td>
                                    <td><?php echo esc_html($m->email); ?><br><small><?php echo esc_html($m->phone); ?></small></td>
                                    <td>
                                        <?php $del_url = wp_nonce_url(admin_url('admin.php?page=zmp-commission&action=delete_commission_member&mid=' . $m->id), 'zmp_delete_comm_nonce'); ?>
                                        <a href="<?php echo esc_url($del_url); ?>" onclick="return confirm('Дали сте сигурни?');" style="color: red;">Избриши</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- РЕГИСТАР НА ДИСКВАЛИФИКАЦИИ -->
            <div class="zmp-admin-box" style="margin-top: 25px;">
                <h3 style="color: #dc2626;">🚫 Регистар на сите изречени дисквалификации</h3>
                <p>Евиденција за секоја дисквалификација изречена од Испитната комисија или Тестаторите со детално правно образложение.</p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="130">Датум</th>
                            <th width="100">Субјект</th>
                            <th width="160">Лице (Шифра)</th>
                            <th>Училиште</th>
                            <th width="160">Изречена од</th>
                            <th>Детално образложение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disqualifications)): ?>
                            <tr><td colspan="6">Нема изречени дисквалификации. Натпреварот тече регуларно.</td></tr>
                        <?php else: foreach ($disqualifications as $dq): ?>
                            <tr>
                                <td><small><?php echo esc_html($dq->created_at); ?></small></td>
                                <td><span class="zmp-badge" style="background: #dc2626;"><?php echo strtoupper(esc_html($dq->target_type)); ?></span></td>
                                <td><strong><?php echo esc_html($dq->target_name); ?></strong><br><code><?php echo esc_html($dq->target_code); ?></code></td>
                                <td><?php echo esc_html($dq->school_name); ?></td>
                                <td><strong><?php echo esc_html($dq->disqualified_by_name); ?></strong><br><small>(<?php echo esc_html($dq->disqualified_by_role); ?>)</small></td>
                                <td><em style="color: #991b1b;"><?php echo nl2br(esc_html($dq->detailed_reason)); ?></em></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Управување со корисници и улоги
     */
    public function view_teachers() {
        $users = get_users(array('orderby' => 'registered', 'order' => 'DESC'));
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>Управување со Корисници и Улоги</h1>
            <p>
                Доделете или одземете улоги на регистрираните колеги:
                <strong>Наставник / Ментор</strong>, <strong>Тестатор</strong>, <strong>Модератор</strong> или <strong>Испитна комисија</strong>.
            </p>
            <p class="description">Претседателскиот пристап се доделува преку евидентиран мандат. Сметката на Претседателот мора да има subscriber и zmp_teacher; администраторските и дозволените ЗМП улоги може да се комбинираат и остануваат зачувани. Се внесуваат датумите и одлуката на Управниот одбор; улога без мандат не дава претседателски пристап.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'role_updated'): ?>
                <div class="updated notice is-dismissible"><p>&check; Улогата е успешно ажурирана!</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_role_assigned_exclusively'): ?>
                <div class="error notice is-dismissible"><p>Претседател не се доделува преку брза промена на улога. Користете ја страницата за мандат.</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_admin_account_denied'): ?>
                <div class="error notice is-dismissible"><p>Претседателската улога не се доделува преку брза промена на улога. Користете ја страницата за мандат; постојните улоги се зачувуваат.</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_mandate_required'): ?>
                <div class="error notice is-dismissible"><p>Потребен е евидентиран мандат. Назначете Претседател преку страницата „Мандат на претседател“.</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_role_assignment_failed'): ?>
                <div class="error notice is-dismissible"><p>Улогата Претседател не беше доделена. Проверете ја улогата и привилегиите на сметката.</p></div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'president_must_be_revoked_first'): ?>
                <div class="error notice is-dismissible"><p>Прво одземете ја улогата Претседател пред да доделите друга оперативна улога.</p></div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="140">Корисничко име</th>
                        <th width="160">Име и Презиме</th>
                        <th width="180">Е-пошта</th>
                        <th>Училиште и Општина (од профил)</th>
                        <th width="180">Тековни улоги</th>
                        <th width="320">Управување со привилегии (1 клик)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6">Нема регистрирани корисници.</td></tr>
                    <?php else:
                        foreach ($users as $u):
                            $roles = (array) $u->roles;
                            $is_teacher   = in_array('zmp_teacher', $roles);
                            $is_testator  = in_array('zmp_testator', $roles);
                            $is_moderator = in_array('zmp_moderator', $roles);
                            $is_comm      = in_array('zmp_commission', $roles);
                            $is_president = in_array('zmp_president', $roles);
                            $is_admin     = in_array('administrator', $roles, true) || $u->has_cap('manage_options');

                            $school = get_user_meta($u->ID, 'zmp_school_name', true);
                            $mun    = get_user_meta($u->ID, 'zmp_municipality', true);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($u->user_login); ?></strong></td>
                            <td><?php echo esc_html($u->display_name); ?></td>
                            <td><?php echo esc_html($u->user_email); ?></td>
                            <td>
                                <?php if (!empty($school)): ?>
                                    <strong><?php echo esc_html($school); ?></strong> (<?php echo esc_html($mun); ?>)
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Нема пополнето</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_admin): ?><span class="zmp-badge" style="background: #0f172a;">Админ</span><?php endif; ?>
                                <?php if ($is_comm): ?><span class="zmp-badge" style="background: #9333ea;">Комисија</span><?php endif; ?>
                                <?php if ($is_moderator): ?><span class="zmp-badge" style="background: #1e40af;">Модератор</span><?php endif; ?>
                                <?php if ($is_testator): ?><span class="zmp-badge" style="background: #0284c7;">Тестатор</span><?php endif; ?>
                                <?php if ($is_teacher): ?><span class="zmp-badge" style="background: #16a34a;">Наставник</span><?php endif; ?>
                                <?php if ($is_president): ?><span class="zmp-badge" style="background: #0f766e;">Претседател</span><?php endif; ?>
                                <?php if (!$is_admin && !$is_teacher && !$is_testator && !$is_moderator && !$is_comm && !$is_president): ?>
                                    <span class="zmp-badge" style="background: #ca8a04;">Обичен корисник</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$is_admin): ?>
                                    <!-- Тогле Наставник -->
                                    <?php if (!$is_teacher && !$is_president): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=grant_role&role=zmp_teacher&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small">
                                            + Наставник
                                        </a>
                                    <?php elseif ($is_teacher): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=revoke_role&role=zmp_teacher&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small" style="color: red;">
                                            - Наставник
                                        </a>
                                    <?php endif; ?>

                                    <!-- Тогле Тестатор -->
                                    <?php if (!$is_testator && !$is_president): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=grant_role&role=zmp_testator&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small">
                                            + Тестатор
                                        </a>
                                    <?php elseif ($is_testator): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=revoke_role&role=zmp_testator&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small" style="color: red;">
                                            - Тестатор
                                        </a>
                                    <?php endif; ?>

                                    <!-- Тогле Модератор -->
                                    <?php if (!$is_moderator && !$is_president): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=grant_role&role=zmp_moderator&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small">
                                            + Модератор
                                        </a>
                                    <?php elseif ($is_moderator): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=revoke_role&role=zmp_moderator&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small" style="color: red;">
                                            - Модератор
                                        </a>
                                    <?php endif; ?>

                                    <!-- Тогле Комисија -->
                                    <?php if (!$is_comm && !$is_president): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=grant_role&role=zmp_commission&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small" style="color: purple;">
                                            + Комисија
                                        </a>
                                    <?php elseif ($is_comm): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=zmp-teachers&action=revoke_role&role=zmp_commission&user_id=' . $u->ID), 'zmp_roles_nonce')); ?>" class="button button-small" style="color: red;">
                                            - Комисија
                                        </a>
                                    <?php endif; ?>

                                    <!-- President access is managed through a dated mandate, never a role-only toggle. -->
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=zmp-president-mandate')); ?>" class="button button-small" style="color: #0f766e;">
                                        Управување со мандат
                                    </a>
                                <?php else: ?>
                                    <small style="color: #64748b;">Админ има полн пристап</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Календар на натпревари
     */
    public function view_calendar() {
        global $wpdb;
        $table_cal = $wpdb->prefix . 'zmp_calendar';
        $schedules = $wpdb->get_results("SELECT * FROM $table_cal ORDER BY start_datetime ASC");
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>📅 Календар на натпревари и автоматски временски прозорци</h1>
            <p>
                Внесете го датумот и часот за секој натпревар.
                Системот <strong>автоматски ќе го отвори тестирањето</strong> точно во зададениот час, и ќе дозволи влез сè до истекот на временскиот прозорец.
                Времетраењето на тестот се пресметува според активното време на ученикот.
            </p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'calendar_saved'): ?>
                <div class="updated notice is-dismissible"><p>&check; Календарот е успешно зачуван!</p></div>
            <?php endif; ?>

            <div class="zmp-admin-box">
                <h3>➕ Додај / Измени термин во календарот</h3>
                <form method="POST">
                    <?php wp_nonce_field('zmp_calendar_nonce'); ?>
                    <input type="hidden" name="zmp_save_calendar" value="1">
                    <input type="hidden" name="cal_id" value="0">

                    <table class="form-table">
                        <tr>
                            <th><label>Натпреварувачко ниво *</label></th>
                            <td>
                                <select name="phase" required>
                                    <option value="school">Училишни натпревари</option>
                                    <option value="municipal">Општински натпревари</option>
                                    <option value="regional">Регионални натпревари</option>
                                    <option value="state">Државен натпревар</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Наслов на терминот *</label></th>
                            <td><input type="text" name="title" required placeholder="пр. Општински натпревар по музичко образование 2026/2027" style="width: 70%;"></td>
                        </tr>
                        <tr>
                            <th><label>Датум и час на отворање (Почеток) *</label></th>
                            <td>
                                <input type="datetime-local" name="start_datetime" required>
                                <p class="description">Точниот момент кога страницата автоматски ќе овозможи логирање на учениците.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Крај на временскиот прозорец (Затворање) *</label></th>
                            <td>
                                <input type="datetime-local" name="end_datetime" required>
                                <p class="description">Поголем рок (на пр. прозорец од неколку часа или цел ден) во кој учениците можат да започнат.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Активно време за тест (минути) *</label></th>
                            <td>
                                <input type="number" name="duration_minutes" value="45" style="width: 100px;" required>
                                <p class="description">Колку минути активно време ќе има секој ученик откако ќе влезе во тестот.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Статус</label></th>
                            <td><label><input type="checkbox" name="is_active" value="1" checked> Терминот е активен</label></td>
                        </tr>
                    </table>

                    <button type="submit" class="button button-primary">Зачувај термин во календарот</button>
                </form>
            </div>

            <h3>Постоечки термини во календарот</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Натпреварувачко ниво</th>
                        <th>Наслов</th>
                        <th>Датум и час на отворање</th>
                        <th>Крај на прозорецот</th>
                        <th>Активно време</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr><td colspan="6">Нема дефинирано термини.</td></tr>
                    <?php else: foreach ($schedules as $s):
                        $now = current_time('timestamp');
                        $st  = strtotime($s->start_datetime);
                        $et  = strtotime($s->end_datetime);
                        $is_open = ($now >= $st && $now <= $et);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html(ZMP_Category_Jury::phase_label($s->phase)); ?></strong></td>
                            <td><?php echo esc_html($s->title); ?></td>
                            <td><?php echo esc_html($s->start_datetime); ?></td>
                            <td><?php echo esc_html($s->end_datetime); ?></td>
                            <td><strong><?php echo esc_html($s->duration_minutes); ?> мин.</strong></td>
                            <td>
                                <?php if (!$s->is_active): ?>
                                    <span style="color: gray;">Деактивиран</span>
                                <?php elseif ($is_open): ?>
                                    <span class="zmp-badge" style="background: #16a34a;">🟢 ОТВОРЕНО ВО ЖИВО</span>
                                <?php elseif ($now < $st): ?>
                                    <span class="zmp-badge" style="background: #2563eb;">Се чека почеток</span>
                                <?php else: ?>
                                    <span class="zmp-badge" style="background: #64748b;">Завршено</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Инциденти и дописи од Тестаторите
     */
    public function view_incidents() {
        global $wpdb;
        $table_incidents = $wpdb->prefix . 'zmp_incidents';
        $incidents = $wpdb->get_results("SELECT * FROM $table_incidents ORDER BY status ASC, created_at DESC LIMIT 50");
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>🚨 Инциденти и Службени Дописи во сала (Тестатори &rarr; Модератори)</h1>
            <p>Преглед на сите дописи испратени од дежурните тестатори од училишните сали.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="130">Време</th>
                        <th width="160">Тестатор</th>
                        <th width="180">Училиште / Општина</th>
                        <th>Предмет и Порака</th>
                        <th>Одговор од Модератор</th>
                        <th width="100">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidents)): ?>
                        <tr><td colspan="6">Нема дописи.</td></tr>
                    <?php else: foreach ($incidents as $inc): ?>
                        <tr>
                            <td><small><?php echo esc_html($inc->created_at); ?></small></td>
                            <td><strong><?php echo esc_html($inc->testator_name); ?></strong></td>
                            <td><?php echo esc_html($inc->school_name . ' (' . $inc->municipality . ')'); ?></td>
                            <td>
                                <strong><?php echo esc_html($inc->subject); ?></strong><br>
                                <?php if (!empty($inc->student_code)): ?><code><?php echo esc_html($inc->student_code); ?></code>: <?php endif; ?>
                                <?php echo nl2br(esc_html($inc->message)); ?>
                            </td>
                            <td>
                                <?php if (!empty($inc->moderator_reply)): ?>
                                    <strong style="color: #1e3a8a;">&rarr; <?php echo nl2br(esc_html($inc->moderator_reply)); ?></strong>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Се чека одговор</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inc->status === 'resolved'): ?>
                                    <span style="color: #16a34a; font-weight: bold;">&check; Решено</span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-weight: bold;">Отворено</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Жалби и приговори (Рок од 24 часа)
     */
    public function view_complaints() {
        global $wpdb;
        $table_complaints = $wpdb->prefix . 'zmp_complaints';
        $complaints = $wpdb->get_results("SELECT * FROM $table_complaints ORDER BY created_at DESC");
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>⚖️ Официјални Жалби и Приговори (Рок од 24 часа)</h1>
            <p>Преглед на жалбите поднесени од менторите. Одлуките и промените на бодови се поднесуваат преку порталот на Испитната комисија со конкретно образложение и ревизорска трага.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="130">Датум</th>
                        <th width="150">Наставник / Училиште</th>
                        <th width="160">Ученик (Шифра)</th>
                        <th>Предмет и Образложение</th>
                        <th width="130">Одлука Тестатор</th>
                        <th width="130">Статус</th>
                        <th width="220">Решение од Комисијата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($complaints)): ?>
                        <tr><td colspan="7">Нема поднесено жалби.</td></tr>
                    <?php else: foreach ($complaints as $c): ?>
                        <tr>
                            <td><small><?php echo esc_html($c->created_at); ?></small></td>
                            <td><strong><?php echo esc_html($c->mentor_name); ?></strong><br><small><?php echo esc_html($c->school_name); ?></small></td>
                            <td><?php echo esc_html($c->student_name); ?><br><code><?php echo esc_html($c->student_code); ?></code></td>
                            <td>
                                <strong><?php echo esc_html($c->complaint_subject); ?></strong>
                                <p style="margin: 5px 0;"><?php echo nl2br(esc_html($c->complaint_text)); ?></p>
                            </td>
                            <td>
                                <span class="zmp-badge"><?php echo esc_html($c->testator_decision); ?></span>
                            </td>
                            <td>
                                <?php if ($c->status === 'accepted'): ?>
                                    <span style="color: #16a34a; font-weight: bold;">&check; Прифатена (+<?php echo $c->points_adjustment; ?>)</span>
                                <?php elseif ($c->status === 'rejected'): ?>
                                    <span style="color: #dc2626; font-weight: bold;">❌ Одбиена</span>
                                <?php else: ?>
                                    <span style="color: #ca8a04; font-weight: bold;">Во разгледување</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $c->status === 'pending' || $c->status === 'forwarded' ? '<em>Чека образложена одлука во порталот на Комисијата.</em>' : '<em>'.nl2br(esc_html($c->admin_notes)).'</em>'; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Банка на прашања
     */
    public function view_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_questions';
        $questions = $wpdb->get_results("SELECT * FROM $table ORDER BY grade ASC, id DESC");
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>Банка на прашања (Двојазично MK / SQ со аудио и ноти)</h1>

            <?php if (isset($_GET['msg'])): ?>
                <div class="updated notice"><p>&check; Операцијата е успешно извршена.</p></div>
            <?php endif; ?>

            <div class="zmp-admin-box">
                <h3>➕ Додај ново прашање</h3>
                <form method="POST">
                    <?php wp_nonce_field('zmp_add_question_nonce'); ?>
                    <input type="hidden" name="zmp_add_question" value="1">

                    <table class="form-table">
                        <tr>
                            <th><label>Одделение и Натпреварувачко ниво</label></th>
                            <td>
                                <select name="grade" required>
                                    <option value="6">VI (шесто) одделение</option>
                                    <option value="7">VII (седмо) одделение</option>
                                    <option value="8">VIII (осмо) одделение</option>
                                    <option value="9">IX (деветто) одделение</option>
                                </select>
                                <select name="phase">
                                    <option value="all">Сите нивоа</option>
                                    <option value="school">Училишно натпреварувачко ниво</option>
                                    <option value="municipal">Општинско натпреварувачко ниво</option>
                                    <option value="regional">Регионално натпреварувачко ниво</option>
                                    <option value="state">Државно натпреварувачко ниво</option>
                                </select>
                                <select name="question_type">
                                    <option value="multiple_choice">Текстуално со избор</option>
                                    <option value="audio_listen">Аудио слушање (музички исечок)</option>
                                    <option value="notation_image">Нотен запис / партитура (слика)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Текст на прашањето (Македонски) *</label></th>
                            <td><textarea name="question_text_mk" rows="2" style="width: 100%;" required placeholder="пр. Кој композитор го компонирал ова дело?"></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Текст на прашањето (Shqip / Албански) *</label></th>
                            <td><textarea name="question_text_sq" rows="2" style="width: 100%;" required placeholder="Cili kompozitor e ka kompozuar këtë vepër?"></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Аудио фајл (URL)</label></th>
                            <td>
                                <input type="text" name="audio_url" id="zmp_audio_url" style="width: 70%;" placeholder="https://muzika.org.mk/.../audio.mp3">
                                <button type="button" class="button zmp-upload-media" data-target="#zmp_audio_url">Прикачи аудио</button>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Слика со нотен запис (URL)</label></th>
                            <td>
                                <input type="text" name="image_url" id="zmp_image_url" style="width: 70%;" placeholder="https://muzika.org.mk/.../notes.png">
                                <button type="button" class="button zmp-upload-media" data-target="#zmp_image_url">Прикачи ноти</button>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Понудени одговори (A, B, C, D)</label></th>
                            <td>
                                <?php for ($i = 0; $i < 4; $i++): $letter = chr(65 + $i); ?>
                                    <div style="margin-bottom: 8px;">
                                        <strong>Опција <?php echo $letter; ?>:</strong>
                                        <input type="text" name="option_mk[]" placeholder="Одговор на македонски" style="width: 40%;" required>
                                        <input type="text" name="option_sq[]" placeholder="Përgjigjja në shqip" style="width: 40%;" required>
                                        <label><input type="radio" name="correct_option_index" value="<?php echo $i; ?>" <?php checked($i, 0); ?>> Точен одговор</label>
                                    </div>
                                <?php endfor; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Бодови за прашањето</label></th>
                                <td><input type="number" min="1" max="20" step="1" name="points" value="5" style="width: 80px;" required></td>
                        </tr>
                    </table>

                    <button type="submit" class="button button-primary">Зачувај прашање во банката</button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="80">Одд.</th>
                        <th>Текст (Македонски)</th>
                        <th>Текст (Shqip)</th>
                        <th width="90">Мултимедија</th>
                        <th width="70">Бодови</th>
                        <th width="80">Акции</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="7">Сè уште нема внесено прашања.</td></tr>
                    <?php else: foreach ($questions as $q): ?>
                        <tr>
                            <td>#<?php echo $q->id; ?></td>
                            <td><strong><?php echo $q->grade; ?> одд.</strong></td>
                            <td><?php echo esc_html($q->question_text_mk); ?></td>
                            <td><?php echo esc_html($q->question_text_sq); ?></td>
                            <td>
                                <?php if (!empty($q->audio_url)): ?><span title="Аудио">🎵 Аудио</span><?php endif; ?>
                                <?php if (!empty($q->image_url)): ?><span title="Ноти">🎼 Слика</span><?php endif; ?>
                            </td>
                            <td><?php echo $q->points; ?></td>
                            <td>
                                <?php $del_url = wp_nonce_url(admin_url('admin.php?page=zmp-questions&action=delete_question&qid=' . $q->id), 'zmp_delete_q_nonce'); ?>
                                <a href="<?php echo esc_url($del_url); ?>" onclick="return confirm('Дали сте сигурни?');" style="color: red;">Избриши</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Преглед на ученици и ментори
     */
    public function view_students() {
        global $wpdb;
        $table_students = $wpdb->prefix . 'zmp_students';
        $table_mentors  = $wpdb->prefix . 'zmp_mentors';

        $region = isset($_GET['zmp_student_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_student_region'])) ? sanitize_key(wp_unslash($_GET['zmp_student_region'])) : '';
        $municipality = sanitize_text_field(wp_unslash($_GET['zmp_student_municipality'] ?? ''));
        $school = sanitize_text_field(wp_unslash($_GET['zmp_student_school'] ?? ''));
        $query = "SELECT s.*, m.full_name as mentor_name, m.phone as mentor_phone FROM $table_students s LEFT JOIN $table_mentors m ON m.id=s.mentor_id WHERE 1=1";
        $args = array();
        if ($region !== '') { $query .= ' AND s.region=%s'; $args[]=$region; }
        if ($municipality !== '') { $query .= ' AND s.municipality=%s'; $args[]=$municipality; }
        if ($school !== '') { $query .= ' AND s.school_name LIKE %s'; $args[]='%' . $wpdb->esc_like($school) . '%'; }
        $query .= ' ORDER BY s.grade ASC,s.last_name ASC';
        $students = $args ? $wpdb->get_results($wpdb->prepare($query,$args)) : $wpdb->get_results($query);
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>Ученици и Ментори</h1>
            <p>Комплетен преглед на сите пријавени натпреварувачи, запечатените кодови и генерираните PDF картички за плико.</p>
            <form method="get" class="zmp-filter-bar"><input type="hidden" name="page" value="zmp-students"><label>Регион <select name="zmp_student_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($region,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_student_municipality" list="zmp-admin-student-municipalities" value="<?php echo esc_attr($municipality); ?>"></label><datalist id="zmp-admin-student-municipalities"><?php foreach(ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist><label>Училиште <input name="zmp_student_school" value="<?php echo esc_attr($school); ?>"></label><button class="button button-primary">Филтрирај ученици</button></form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="70">Одд.</th>
                        <th>Име и Презиме</th>
                        <th width="60">Јазик</th>
                        <th>Училиште</th>
                        <th>Општина</th>
                        <th>Регион</th>
                        <th>Шифра (Корисничко име)</th>
                        <th>Лозинка (ПИН)</th>
                        <th>Наставник (Ментор)</th>
                        <th width="80">Натпреварувачко ниво</th>
                        <th width="120">PDF Картичка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="11">Нема пријавени ученици.</td></tr>
                    <?php else: foreach ($students as $st): ?>
                        <tr>
                            <td><strong><?php echo $st->grade; ?> одд.</strong></td>
                            <td><?php echo esc_html($st->first_name . ' ' . $st->last_name); ?></td>
                            <td><?php echo strtoupper($st->language); ?></td>
                            <td><?php echo esc_html($st->school_name); ?></td>
                            <td><?php echo esc_html($st->municipality); ?></td>
                            <td><?php echo esc_html(ZMP_Regions::label($st->region)); ?></td>
                            <td><code><?php echo esc_html($st->access_code); ?></code></td>
                            <td><strong><?php echo esc_html(ZMP_Credentials::reveal_pin($st->access_pin)); ?></strong></td>
                            <td><?php echo esc_html($st->mentor_name); ?></td>
                            <td><span class="zmp-badge"><?php echo esc_html(ZMP_Category_Jury::phase_label($st->current_phase)); ?></span></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('zmp_action' => 'download_student_pdf', 'student_id' => $st->id), home_url('/'))); ?>" target="_blank" class="button button-small">
                                    📄 PDF картичка
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Ранг листи, резултати и пресметка на пласман
     */
    public function view_results() {
        global $wpdb;
        $comp = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE is_active = 1 LIMIT 1");
        $phase = $comp ? $comp->current_phase : 'school';

        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $table_students = $wpdb->prefix . 'zmp_students';
        $region_filter = isset($_GET['zmp_admin_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_admin_region'])) ? sanitize_key(wp_unslash($_GET['zmp_admin_region'])) : '';
        $municipality_filter = sanitize_text_field(wp_unslash($_GET['zmp_admin_municipality'] ?? ''));
        $school_filter = sanitize_text_field(wp_unslash($_GET['zmp_admin_school'] ?? ''));
        $query = "SELECT a.*, s.first_name, s.last_name, s.school_name, s.municipality, s.region, s.access_code FROM $table_attempts a JOIN $table_students s ON s.id = a.student_id WHERE 1=1";
        $args = array();
        if ($region_filter !== '') { $query .= ' AND s.region=%s'; $args[]=$region_filter; }
        if ($municipality_filter !== '') { $query .= ' AND s.municipality=%s'; $args[]=$municipality_filter; }
        if ($school_filter !== '') { $query .= ' AND s.school_name LIKE %s'; $args[]='%' . $wpdb->esc_like($school_filter) . '%'; }
        $query .= ' ORDER BY a.phase ASC, a.grade ASC, a.total_points DESC';
        $results = $args ? $wpdb->get_results($wpdb->prepare($query,$args)) : $wpdb->get_results($query);
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>Ранг-листи, Бодување и Пласман за следно ниво</h1>

            <form method="get" class="zmp-filter-bar"><input type="hidden" name="page" value="zmp-results"><label>Регион <select name="zmp_admin_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($region_filter,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_admin_municipality" value="<?php echo esc_attr($municipality_filter); ?>"></label><label>Училиште <input name="zmp_admin_school" value="<?php echo esc_attr($school_filter); ?>"></label><button class="button button-primary">Филтрирај резултати</button></form>

            <div style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="zmp-admin-box" style="flex: 1;">
                    <h3>📊 Извоз на податоци во Excel (.xlsx / .csv)</h3>
                    <p>Симнете ја комплетната табела со сите резултати, ментори, училишта и награди со поддршка за кирилица и албански карактери.</p>
                    <a href="<?php echo esc_url(add_query_arg(array('page'=>'zmp-results','zmp_export'=>'excel','zmp_admin_region'=>$region_filter,'zmp_admin_municipality'=>$municipality_filter,'zmp_admin_school'=>$school_filter), admin_url('admin.php'))); ?>" class="button button-primary button-hero">
                        📥 Преземи комплетна Excel табела
                    </a>
                </div>

                <div class="zmp-admin-box" style="flex: 1;">
                    <h3>⚙️ Автоматски пласман во следно ниво</h3>
                    <p>Според правилникот:
                       <br>&bull; Училишно/Општинско: Топ 3 ученици
                       <br>&bull; Регионално: Само 1-во место со повеќе од 95 поени (96-100) оди на Државен!
                    </p>
                    <form method="POST">
                        <?php wp_nonce_field('zmp_qualifications_nonce'); ?>
                        <input type="hidden" name="zmp_run_qualifications" value="1">
                        <select name="phase_to_qualify" style="margin-right: 10px;">
                            <option value="school" <?php selected($phase, 'school'); ?>>Училишно &rarr; Општинско</option>
                            <option value="municipal" <?php selected($phase, 'municipal'); ?>>Општинско &rarr; Регионално</option>
                            <option value="regional" <?php selected($phase, 'regional'); ?>>Регионално &rarr; Државно</option>
                        </select>
                        <button type="submit" class="button button-secondary" onclick="return confirm('Дали сакате да го пресметате пласманот според правилникот?');">
                            Пресметај пласман
                        </button>
                    </form>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="90">Натпреварувачко ниво</th>
                        <th width="80">Одд.</th>
                        <th>Шифра</th>
                        <th>Ученик</th>
                        <th>Училиште / Општина</th>
                        <th>Регион</th>
                        <th width="90">Поени</th>
                        <th>Освоена награда</th>
                        <th width="120">Пласман понатаму</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="9">Нема завршени тестирања.</td></tr>
                    <?php else: foreach ($results as $r):
                        $award = ZMP_Scoring::get_award_details($r->total_points, 'mk');
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html(ZMP_Category_Jury::phase_label($r->phase)); ?></strong></td>
                            <td><?php echo $r->grade; ?> одд.</td>
                            <td><code><?php echo esc_html($r->access_code); ?></code></td>
                            <td><?php echo esc_html($r->first_name . ' ' . $r->last_name); ?></td>
                            <td><?php echo esc_html($r->school_name . ' (' . $r->municipality . ')'); ?></td>
                            <td><?php echo esc_html(ZMP_Regions::label($r->region)); ?></td>
                            <td><strong><?php echo number_format($r->total_points, 1); ?></strong></td>
                            <td><?php echo esc_html($award['name_mk']); ?></td>
                            <td>
                                <?php if ($r->qualified_next_stage == 1): ?>
                                    <span style="color: green; font-weight: bold;">&check; Пласиран(а)</span>
                                <?php elseif ($r->status === 'disqualified'): ?>
                                    <span style="color: red; font-weight: bold;">Дисквалификуван</span>
                                <?php else: ?>
                                    <span style="color: gray;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /** Secure diagnostics screen: plugin events plus matching lines from WP debug.log. */
    public function view_full_reset() {
        if (!current_user_can('manage_options')) { wp_die('Недоволни привилегии.'); }
        global $wpdb;
        $completed = !self::full_reset_is_allowed();
        ?>
        <div class="wrap zmp-admin-wrap"><h1>Целосен ресет на тестирањето</h1>
        <?php if (isset($_GET['reset_done'])): ?><div class="notice notice-success"><p>Сите претходни ZMP натпреварувачки податоци се избришани. Создадена е празна почетна натпреварувачка година без ученици и прашања. Шемата и WordPress сметките/улогите се зачувани.</p></div><?php endif; ?>
        <?php if ($completed): ?><div class="notice notice-error"><p>Ресетирањето е заклучено: има официјално завршена година.</p></div>
        <?php else: ?><div class="notice notice-warning"><p><strong>Ова трајно ги брише сите постојни натпреварувачки записи:</strong> години и натпреварувачки нивоа, ученици, ментори, прашања, обиди/одговори, резултати, пораки, жалби, одлуки, дежурства, членови на комисијата, поставки за распределба и архивски записи и ZIP-датотеки. По бришењето се создава празна почетна натпреварувачка година. WordPress корисничките сметки, улоги и структурата на базата остануваат.</p><p>Достапно е само пред официјално завршување на натпреварувачка година.</p>
        <form method="post" onsubmit="return confirm('Ова ќе ги избрише сите натпреварувачки податоци и не може да се врати. Продолжи?');"><?php wp_nonce_field('zmp_full_testing_reset'); ?><label>Внесете <code>RESET ZMP</code> за потврда: <input name="confirmation" required autocomplete="off"></label> <button class="button button-primary button-danger" name="zmp_full_testing_reset" value="1">Избриши ги сите податоци</button></form></div><?php endif; ?></div>
        <?php
    }

    public function export_debug_logs() {
        if (!current_user_can('manage_options')) { wp_die('Немате дозвола за извоз на дијагностички логови.', '', array('response' => 403)); }
        check_admin_referer('zmp_export_debug_logs');
        $format = sanitize_key(wp_unslash($_POST['format'] ?? ''));
        if (!in_array($format, array('json','csv'), true)) { wp_die('Неподдржан формат за извоз.'); }
        $events = get_option(ZMP_Debug::OPTION, array());
        if (!is_array($events)) { $events = array(); }
        $test_type = sanitize_key(wp_unslash($_POST['test_type'] ?? 'all'));
        if ($test_type !== 'all' && !isset(ZMP_Debug::test_types()[$test_type])) { $test_type = 'all'; }
        $session_id = sanitize_text_field(wp_unslash($_POST['session_id'] ?? ''));
        $flow_id = sanitize_text_field(wp_unslash($_POST['flow_id'] ?? ''));
        $events = ZMP_Debug::filter_events($events, $test_type, $session_id, $flow_id);
        $payload = ZMP_Debug::export_payload($events, $format);
        if (is_wp_error($payload)) { wp_die('Не може да се подготви извозот.'); }
        nocache_headers();
        $filename = 'zmp-diagnostics-' . $test_type . '-' . gmdate('Ymd-His') . '.' . $format;
        header('Content-Type: ' . ($format === 'json' ? 'application/json; charset=utf-8' : 'text/csv; charset=utf-8'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo $payload;
        exit;
    }

    public function view_debugging() {
        if (!current_user_can('manage_options')) { wp_die('Немате дозвола за пристап.'); }
        $all_events = get_option(ZMP_Debug::OPTION, array());
        if (!is_array($all_events)) { $all_events = array(); }
        $types = ZMP_Debug::test_types();
        $selected_type = sanitize_key(wp_unslash($_GET['zmp_debug_type'] ?? 'all'));
        if ($selected_type !== 'all' && !isset($types[$selected_type])) { $selected_type = 'all'; }
        $selected_session = sanitize_text_field(wp_unslash($_GET['zmp_debug_session'] ?? ''));
        $selected_flow = sanitize_text_field(wp_unslash($_GET['zmp_debug_flow'] ?? ''));
        $events = ZMP_Debug::filter_events($all_events, $selected_type, $selected_session, $selected_flow);
        $events = array_reverse($events);
        $messenger_diagnostic = isset($_GET['messenger_schema_check']) ? ZMP_Role_Messenger::schema_diagnostic_snapshot() : null;
        $capture = ZMP_Debug::active_capture();
        $wp_log_path = (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) ? (is_string(WP_DEBUG_LOG) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log') : '';
        $wp_lines = array();
        $wp_log_available = false;
        if ($wp_log_path && is_string($wp_log_path) && is_readable($wp_log_path) && is_file($wp_log_path)) {
            $wp_log_available = true;
            $lines = @file($wp_log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach (array_reverse($lines) as $line) {
                    if (stripos($line, 'ZMP Competitions') !== false) { $wp_lines[] = $line; }
                    if (count($wp_lines) >= 100) { break; }
                }
            }
        }
        ?>
        <div class="wrap zmp-admin-wrap">
            <h1>Дебагирање — ЗМП Натпревари</h1>
            <p>Оваа страница прикажува и извезува структурирани дијагностички настани од плагинoт. Пристапот е ограничен на администратори.</p>
            <?php if (isset($_GET['cleared'])): ?><div class="notice notice-success is-dismissible"><p>Локалниот ZMP дневник е исчистен.</p></div><?php endif; ?>
            <?php if (isset($_GET['capture_started'])): ?><div class="notice notice-success is-dismissible"><p>Снимањето на дијагностички тест е започнато.</p></div><?php endif; ?>
            <?php if (isset($_GET['capture_stopped'])): ?><div class="notice notice-success is-dismissible"><p>Снимањето на дијагностички тест е запрено.</p></div><?php endif; ?>
            <div class="notice notice-info"><p><strong>Приватност:</strong> логовите не запишуваат имиња, е-пошти, лозинки, PIN-кодови, токени, cookies или вредности од одговорите. Query-параметрите од адресите се отстрануваат. Експортот содржи само структурирани ZMP-настани, а не целиот WordPress debug.log.</p></div>
            <h2>Messenger — проверка на шемата</h2>
            <p>Проверката само ги чита состојбата на верзијата/retry и тековните табели, колони и индекси. Не извршува миграција и не менува пораки.</p>
            <form method="post" style="margin:12px 0;">
                <?php wp_nonce_field('zmp_run_messenger_schema_diagnostic'); ?>
                <button type="submit" name="zmp_run_messenger_schema_diagnostic" value="1" class="button button-primary">Провери ја Messenger шемата</button>
            </form>
            <?php if (is_array($messenger_diagnostic)): ?>
                <div class="notice <?php echo (!empty($messenger_diagnostic['notice_active']) || ($messenger_diagnostic['schema_compatible'] ?? '') !== 'yes') ? 'notice-error' : 'notice-success'; ?>"><p><strong>Messenger дијагностика:</strong> инсталиран плагин <code><?php echo esc_html($messenger_diagnostic['plugin_version']); ?></code>; предупредување <?php echo !empty($messenger_diagnostic['notice_active']) ? 'активно' : 'неактивно'; ?>; DB marker <code><?php echo esc_html($messenger_diagnostic['schema_database_marker']); ?></code> (<?php echo esc_html($messenger_diagnostic['schema_marker_state']); ?>); потребна шема <code><?php echo esc_html($messenger_diagnostic['schema_version']); ?></code>; retry <code><?php echo esc_html($messenger_diagnostic['schema_retry_state']); ?></code>; retry рок <code><?php echo esc_html($messenger_diagnostic['schema_retry_deadline']); ?></code>; шема компатибилна <code><?php echo esc_html($messenger_diagnostic['schema_compatible']); ?></code>.</p>
                <p>Наод: <code><?php echo esc_html($messenger_diagnostic['schema_table'] ?: '—'); ?></code> / <code><?php echo esc_html($messenger_diagnostic['schema_object'] ?: '—'); ?></code>; причина <code><?php echo esc_html($messenger_diagnostic['schema_issue']); ?></code>; очекувано <code><?php echo esc_html($messenger_diagnostic['schema_expected'] ?: '—'); ?></code>; најдено <code><?php echo esc_html($messenger_diagnostic['schema_actual'] ?: '—'); ?></code>.</p>
                <p>Другите шемни предуслови не се извршуваат повторно од ова копче, за дијагностиката да не ја менува retry-состојбата. Последни постоечки релевантни записи: <?php echo (int) $messenger_diagnostic['schema_prerequisite_event_count']; ?>.</p>
                <?php if (!empty($messenger_diagnostic['schema_prerequisite_recent_events'])): ?><ul><?php foreach ($messenger_diagnostic['schema_prerequisite_recent_events'] as $schema_event): ?><li><code><?php echo esc_html($schema_event['time']); ?></code> — <code><?php echo esc_html($schema_event['event']); ?></code><?php if ($schema_event['prerequisite'] !== ''): ?> — блокирачко предусловие <code><?php echo esc_html($schema_event['prerequisite']); ?></code><?php endif; ?><?php if ($schema_event['table'] !== ''): ?> — <code><?php echo esc_html($schema_event['table']); ?></code> / <code><?php echo esc_html($schema_event['object']); ?></code>; <code><?php echo esc_html($schema_event['issue']); ?></code><?php endif; ?><?php if ($schema_event['expected'] !== '' || $schema_event['actual'] !== ''): ?>; очекувано <code><?php echo esc_html($schema_event['expected']); ?></code>; најдено <code><?php echo esc_html($schema_event['actual']); ?></code><?php endif; ?></li><?php endforeach; ?></ul><?php else: ?><p>Нема совпаѓачки зачуван настан во локалниот ZMP дневник.</p><?php endif; ?></div>
            <?php endif; ?>
            <h2>Снимање на тест</h2>
            <?php if ($capture): ?>
                <p><strong>Снимањето е активно:</strong> <?php echo esc_html($types[$capture['test_type']] ?? $capture['test_type']); ?> — започнато <?php echo esc_html($capture['started_at'] ?? ''); ?>; истекува автоматски. Идентификатор: <code><?php echo esc_html($capture['session_id']); ?></code></p>
                <form method="post" style="margin:12px 0;"><?php wp_nonce_field('zmp_stop_debug_capture'); ?><button type="submit" name="zmp_stop_debug_capture" value="1" class="button">Запри снимање</button></form>
            <?php else: ?>
                <p>Избери што ќе тестираш, започни снимање, па изврши го тестот на страницата. Снимањето престанува само по 15, 30 или 60 минути.</p>
                <form method="post" style="margin:12px 0;">
                    <?php wp_nonce_field('zmp_start_debug_capture'); ?>
                    <label for="zmp-debug-test-type">Тип тест </label><select id="zmp-debug-test-type" name="test_type" required><?php foreach ($types as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                    <label for="zmp-debug-duration">Времетраење </label><select id="zmp-debug-duration" name="duration_minutes"><option value="15">15 минути</option><option value="30">30 минути</option><option value="60" selected>60 минути</option></select>
                    <button type="submit" name="zmp_start_debug_capture" value="1" class="button button-primary">Започни снимање</button>
                </form>
            <?php endif; ?>
            <h2>Настани од ZMP плагинoт (<?php echo (int) count($events); ?> од најмногу <?php echo (int) ZMP_Debug::MAX_EVENTS; ?> зачувани)</h2>
            <p>Секое барање добива flow ID; настаните во снимањето го делат session ID. Ова овозможува да се проследи еден тест низ повеќе барања.</p>
            <?php if (isset($_GET['self_test'])): ?><div class="notice notice-success is-dismissible"><p>Тест-записот е испратен. Ако не се појави подолу по освежување, проблемот е во зачувувањето/читањето на дневникот.</p></div><?php endif; ?>
            <form method="post" style="display:inline-block;margin:12px 8px 12px 0;">
                <?php wp_nonce_field('zmp_test_debug_log'); ?>
                <button type="submit" name="zmp_test_debug_log" value="1" class="button button-primary">Тестирај логирање</button>
            </form>
            <form method="post" style="display:inline-block;margin:12px 0;">
                <?php wp_nonce_field('zmp_clear_debug_log'); ?>
                <button type="submit" name="zmp_clear_debug_log" value="1" class="button" onclick="return confirm('Да се избришат локалните ZMP дијагностички записи?');">Исчисти ZMP дневник</button>
            </form>
            <form method="get" style="margin:12px 0;padding:10px;background:#fff;border:1px solid #ccd0d4;">
                <input type="hidden" name="page" value="zmp-debugging">
                <label for="zmp-debug-filter-type">Тип тест </label><select id="zmp-debug-filter-type" name="zmp_debug_type"><option value="all" <?php selected($selected_type, 'all'); ?>>Сите типови</option><?php foreach ($types as $key => $label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($selected_type, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
                <label for="zmp-debug-filter-session">Session ID </label><input id="zmp-debug-filter-session" name="zmp_debug_session" value="<?php echo esc_attr($selected_session); ?>" class="regular-text">
                <label for="zmp-debug-filter-flow">Flow ID </label><input id="zmp-debug-filter-flow" name="zmp_debug_flow" value="<?php echo esc_attr($selected_flow); ?>" class="regular-text">
                <button class="button">Филтрирај</button>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:12px 0;">
                <input type="hidden" name="action" value="zmp_export_debug_logs"><?php wp_nonce_field('zmp_export_debug_logs'); ?>
                <input type="hidden" name="test_type" value="<?php echo esc_attr($selected_type); ?>"><input type="hidden" name="session_id" value="<?php echo esc_attr($selected_session); ?>"><input type="hidden" name="flow_id" value="<?php echo esc_attr($selected_flow); ?>">
                <button type="submit" name="format" value="json" class="button">Експортирај JSON</button> <button type="submit" name="format" value="csv" class="button">Експортирај CSV</button>
            </form>
            <style>
                .zmp-debug-events-scroll { max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
                .zmp-debug-events-scroll:focus { outline: 2px solid #2271b1; outline-offset: 2px; }
                .zmp-debug-events-scroll > table { min-width: 1000px; }
                #zmp-debug-test-type, #zmp-debug-filter-type { max-width: 100%; box-sizing: border-box; }
            </style>
            <p id="zmp-debug-events-scroll-hint" class="screen-reader-text">Кога регионот е во фокус, притиснете ArrowLeft или ArrowRight за хоризонтално придвижување; Home оди на почетокот, а End на крајот.</p>
            <div class="zmp-debug-events-scroll" role="region" tabindex="0" aria-label="Табела со структурирани дијагностички настани" aria-describedby="zmp-debug-events-scroll-hint" aria-keyshortcuts="ArrowLeft ArrowRight Home End">
                <table class="widefat fixed striped"><thead><tr><th style="width:145px">Време</th><th style="width:65px">Ниво</th><th>Тип тест</th><th>Session / Flow ID</th><th>Настан и барање</th><th style="width:70px">WP user ID</th><th>Контекст</th></tr></thead><tbody>
                <?php if (!$events): ?><tr><td colspan="7">Нема настани за избраните филтри. Започни снимање пред тестот или отстрани ги филтрите.</td></tr><?php else: foreach ($events as $raw_event): $event = ZMP_Debug::normalize_event($raw_event); ?>
                    <tr><td><?php echo esc_html($event['time']); ?></td><td><strong><?php echo esc_html(strtoupper($event['level'])); ?></strong></td><td><?php echo esc_html($types[$event['test_type']] ?? $event['test_type']); ?></td><td><small>Session: <code><?php echo esc_html($event['session_id'] ?: '—'); ?></code><br>Flow: <code><?php echo esc_html($event['flow_id']); ?></code></small></td><td><?php echo esc_html($event['message']); ?><br><small><?php echo esc_html($event['request']); ?></small></td><td><?php echo (int) $event['user_id']; ?></td><td><code><?php echo esc_html(wp_json_encode($event['context'], JSON_UNESCAPED_UNICODE)); ?></code></td></tr>
                <?php endforeach; endif; ?></tbody></table>
            </div>
            <script>
                (function () {
                    var region = document.querySelector('.zmp-debug-events-scroll');
                    if (!region) { return; }
                    region.addEventListener('keydown', function (event) {
                        if (event.target !== region || event.altKey || event.ctrlKey || event.metaKey) { return; }
                        var step = Math.max(80, Math.floor(region.clientWidth * 0.8));
                        var next = null;
                        if (event.key === 'ArrowLeft') { next = Math.max(0, region.scrollLeft - step); }
                        else if (event.key === 'ArrowRight') { next = Math.min(region.scrollWidth - region.clientWidth, region.scrollLeft + step); }
                        else if (event.key === 'Home') { next = 0; }
                        else if (event.key === 'End') { next = region.scrollWidth - region.clientWidth; }
                        if (next !== null) {
                            event.preventDefault();
                            region.scrollLeft = next;
                        }
                    });
                }());
            </script>
            <h2 style="margin-top:28px">WordPress debug.log — ZMP-записи</h2>
            <?php if (!$wp_log_path): ?><p><strong>Не е активиран WP_DEBUG_LOG.</strong> ZMP дневникот погоре сепак работи без оваа поставка.</p>
            <?php elseif (!$wp_log_available): ?><p>Патеката до debug.log е поставена, но датотеката не постои или не е читлива: <code><?php echo esc_html(basename($wp_log_path)); ?></code>.</p>
            <?php elseif (!$wp_lines): ?><p>Нема пронајдени редови со „ZMP Competitions“ во WordPress debug.log.</p>
            <?php else: ?><textarea readonly rows="14" style="width:100%;font-family:monospace" aria-label="ZMP lines from WordPress debug log"><?php echo esc_textarea(implode("\n", $wp_lines)); ?></textarea><?php endif; ?>
            <p><strong>Напомена:</strong> ова не може да ги прочита серверските PHP/Apache/Nginx логови ако хостингот ги чува надвор од WordPress или ги блокира. Во таков случај, тие се достапни преку контролниот панел на хостингот.</p>
        </div>
        <?php
    }

}
