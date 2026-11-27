<?php
if (!defined('ABSPATH')) { exit; }

/** Privacy-conscious diagnostics with short, administrator-controlled test captures. */
class ZMP_Debug {
    const OPTION = 'zmp_debug_events';
    const CAPTURE_OPTION = 'zmp_debug_capture';
    const MAX_EVENTS = 500;
    private static $request_id = null;

    public static function test_types() {
        return array(
            'student_registration' => 'Пријава на ученик',
            'category_registration' => 'Категориска пријава и прилози',
            'student_exam' => 'Тестирање на ученик',
            'testator' => 'Тестатор',
            'moderator' => 'Модератор и организација',
            'jury' => 'Жири и бодување',
            'commission' => 'Комисија и одлуки',
            'communications' => 'Пораки и известувања',
            'google_drive' => 'Google Drive и видео',
            'results_certificates' => 'Резултати и дипломи',
            'matrices_audio_tools' => 'Матрици и аудио-алатки',
            'president_mandate' => 'Претседателски мандат',
            'president_election_documents' => 'Претседателски изборни документи и обврски',
            'president_facsimile_certificate' => 'Криптографски сертификат за претседателски факсимил',
            'president_facsimile_usage' => 'Проверка и примена на претседателски факсимил',
            'school_profile' => 'Училишни профили и статуси на вработување',
            'school_email_access' => 'Барања, одлуки и пристап до службена е-пошта на училиште',
            'teacher_subject_proposals' => 'Предлози за предмети и одлуки на претседател',
            'teacher_register' => 'Регистар на наставници, пребарување и CSV извоз',
            'general' => 'Општо / нераспределено',
        );
    }

    public static function request_id() {
        if (self::$request_id === null) {
            self::$request_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('zmp-', true);
        }
        return self::$request_id;
    }

    public static function active_capture() {
        $capture = get_option(self::CAPTURE_OPTION, array());
        if (!is_array($capture) || empty($capture['session_id']) || empty($capture['test_type']) || empty($capture['expires_at'])) { return array(); }
        if ((int) $capture['expires_at'] <= time()) {
            delete_option(self::CAPTURE_OPTION);
            return array();
        }
        return $capture;
    }

    public static function start_capture($test_type, $duration_minutes, $user_id) {
        $types = self::test_types();
        $test_type = sanitize_key($test_type);
        $duration_minutes = absint($duration_minutes);
        if (!isset($types[$test_type]) || !in_array($duration_minutes, array(15, 30, 60), true)) { return new WP_Error('invalid_capture'); }
        $capture = array(
            'session_id' => wp_generate_uuid4(),
            'test_type' => $test_type,
            'started_at' => current_time('mysql'),
            'started_by' => absint($user_id),
            'expires_at' => time() + ($duration_minutes * MINUTE_IN_SECONDS),
            'duration_minutes' => $duration_minutes,
        );
        update_option(self::CAPTURE_OPTION, $capture, false);
        return $capture;
    }

    public static function stop_capture() {
        delete_option(self::CAPTURE_OPTION);
    }

    /** Store one safe event. A running capture tags events with one test type and session ID. */
    public static function log($level, $message, $context = array(), $test_type = '') {
        $allowed = array('debug', 'info', 'warning', 'error');
        $level = in_array($level, $allowed, true) ? $level : 'info';
        $capture = self::active_capture();
        if ($capture) { $test_type = $capture['test_type']; }
        if (!isset(self::test_types()[$test_type])) { $test_type = self::infer_test_type(); }
        if (!isset(self::test_types()[$test_type])) { $test_type = 'general'; }

        $clean_context = self::sanitize_context($context);
        unset($clean_context['request_action']);
        if (($clean_context['error_code'] ?? '') === 'schema_not_ready'
            && self::is_admin_ajax_request()) {
            $request_action = self::request_action();
            if ($request_action !== '') { $clean_context['request_action'] = $request_action; }
        }
        $clean_context['flow_id'] = self::request_id();
        $event = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'test_type' => $test_type,
            'session_id' => $capture ? sanitize_text_field($capture['session_id']) : '',
            'message' => self::sanitize_message($message, $test_type),
            'user_id' => get_current_user_id(),
            'request' => self::request_path(),
            'context' => $clean_context,
        );
        self::append_event($event);

        if ($level === 'error' || $level === 'warning') {
            error_log('[ZMP Competitions][' . strtoupper($level) . '] ' . $event['message'] . (!empty($clean_context) ? ' ' . wp_json_encode($clean_context) : ''));
        }
    }

    private static function append_event($event) {
        $events = get_option(self::OPTION, array());
        if (!is_array($events)) { $events = array(); }
        $events[] = $event;
        if (count($events) > self::MAX_EVENTS) { $events = array_slice($events, -self::MAX_EVENTS); }
        if (get_option(self::OPTION, null) === null) { add_option(self::OPTION, $events, '', false); }
        else { update_option(self::OPTION, $events, false); }
    }

    private static function sanitize_context($context) {
        $clean = array();
        $integer_keys = array(
            'request_id','school_id','user_id','owner_user_id','requester_user_id','mentor_id','student_id','entry_id','media_id','competition_id',
            'certificate_id','mandate_id','facsimile_id','public_key_id','engagement_epoch','official_email_epoch','requester_engagement_id',
            'owner_engagement_id','requester_engagement_epoch','owner_engagement_epoch','file_size','bytes','attempt','rows_affected',
            'post_fields_count','role_count','duration_minutes','actor_user_id','proposal_id','canonical_subject_id','page','page_size',
            'result_count','batch_count','row_count','count','total','updated_count','inserted_count','schema_prerequisite_event_count'
        );
        $schema_detail_keys = array('schema_component','schema_table','schema_object','schema_issue','schema_expected','schema_actual','schema_marker_state','schema_retry_state','schema_retry_after_state','schema_compatible','schema_version','plugin_version','schema_database_marker','schema_retry_deadline','schema_prerequisite_check_state','schema_prerequisite_component');
        $schema_detail_values = array(
            'schema_component' => array('messenger'),
            'schema_marker_state' => array('current','missing','stale','storage_failure','read_failed','unexpected'),
            'schema_retry_state' => array('none','pending','storage_failure','unexpected','read_failed'),
            'schema_retry_after_state' => array('missing','cooldown','due','invalid','read_failed'),
            'schema_compatible' => array('yes','no'),
            'schema_prerequisite_check_state' => array('not_rechecked_read_only','blocked'),
            'schema_prerequisite_component' => array('messenger_install','mentor_schools_install','mentor_subjects_install','subject_proposals_install','region_requests_install','school_profile_install','school_email_install','registration_requirements_api','registration_facsimile_schema','messenger_schema_api','messenger_schema_contract','messenger_retention_audit_schema','subject_proposals_api','subject_proposals_schema','school_profile_api','school_profile_schema','school_email_api','school_email_schema','retry_option_cleanup','database_marker_read','database_marker_write','database_marker_readback','database_marker_postcondition'),
        );
        $flag_keys = array('is_admin','is_teacher','profile_found','db_error_present','insert_id_present','redirect_has_result_flag','nonce_field_present','schema_notice_active','schema_prefix_present',
            'has_first_name','has_last_name','has_grade','has_language','logged_in','email_changed');
        $outcomes = array('fail_closed','ready','denied','saved','active','reactivated','created','invalidated','pending','approved','rejected','disclosed','failed','success','started','completed','committed');
        $statuses = array('save_error','entry_invalid','invalid_entry','mentor_profile_incomplete','no_active_season','drive_upload_failed','video_database_failed',
            'request_failed','request_submitted','request_approved','request_rejected','decision_failed','contact_changed','eligibility_changed','schema_not_ready',
            'database_error','forbidden','contact_unavailable','school_not_owned','school_relationship_required','invalid_reason','invalid_decision','request_not_found','not_approved',
            'pending','approved','rejected','invalidated','disclosed','saved','created','active','reactivated','failed','success','started','completed','committed');
        $error_codes = array('schema_not_ready','database_error','forbidden','invalid_reason','contact_unavailable','school_not_owned','school_relationship_required',
            'eligibility_changed','contact_changed','invalid_decision','request_not_found','not_approved','invalid_export_format','export_stream_unavailable',
            'school_schema_not_ready','school_save_failed','president_setup_not_owner','facsimile_certificate_write_failed','facsimile_certificate_commit_uncertain',
            'invalid_nonce_or_method','invalid_subject','proposal_pending','proposal_already_decided','proposal_not_found','invalid_decision','canonical_subject_required',
            'invalid_canonical_subject','canonical_subject_invalid','alias_equals_canonical','alias_conflict','subject_duplicate',
            'database_query_failed','staging_write_failed','response_failed');
        $actions = array('zmp_school_profile_save','zmp_school_email_request_submit','zmp_school_email_access_decide','zmp_school_email_access_disclose',
            'zmp_save_school_contact','zmp_remove_school_contact','zmp_submit_subject_proposal','zmp_president_subject_decision','zmp_export_teacher_register',
            'zmp_mentor_action','zmp_category_entry_action','zmp_exam_action','zmp_student_exam_action',
            'zmp_testator_action','zmp_operations_action','zmp_moderator_action','zmp_jury_action','zmp_commission_action','zmp_commission_question_action',
            'zmp_commission_decision_action','zmp_drive_action','zmp_request_fee_invoice','zmp_announcement_action','zmp_comms_action','zmp_results_action');
        $allowed = array_merge($integer_keys,$flag_keys,$schema_detail_keys,array('outcome','status','error_code','form_action','request_action','tab','slot','flow_id','decision'));
        foreach ((array) $context as $key => $value) {
            $key = sanitize_key($key);
            if ($key === '' || !in_array($key,$allowed,true) || !is_scalar($value)) { continue; }
            if (in_array($key,$integer_keys,true)) {
                if (!is_numeric($value) || (string)(int)$value !== (string)$value || (int)$value < 0) { continue; }
                $clean[$key]=(int)$value;
                continue;
            }
            if (in_array($key,$flag_keys,true)) {
                if (in_array($value,array(0,1,'0','1',false,true),true)) { $clean[$key]=(int)(bool)$value; }
                continue;
            }
            if (!is_string($value)) { continue; }
            if ($key==='flow_id') {
                if (preg_match('/^[a-f0-9-]{20,64}$/i',$value)) { $clean[$key]=$value; }
                continue;
            }
            if (in_array($key, $schema_detail_keys, true)) {
                if (isset($schema_detail_values[$key])) {
                    $value = sanitize_key($value);
                    if (in_array($value, $schema_detail_values[$key], true)) { $clean[$key] = $value; }
                } else {
                    $value = trim(sanitize_text_field($value));
                    if ($value !== '' && strlen($value) <= 240 && preg_match('/^[A-Za-z0-9_., ():+\/-]+$/', $value)) { $clean[$key] = $value; }
                }
                continue;
            }
            $value=sanitize_key($value);
            if ($key==='request_action') {
                if ($value!=='' && strlen($value)<=64) { $clean[$key]=$value; }
                continue;
            }
            if ($key==='outcome' && in_array($value,$outcomes,true)) { $clean[$key]=$value; }
            elseif ($key==='status' && in_array($value,$statuses,true)) { $clean[$key]=$value; }
            elseif ($key==='error_code' && in_array($value,$error_codes,true)) { $clean[$key]=$value; }
            elseif ($key==='decision' && in_array($value,array('approve_new','approve_alias','reject','invalid'),true)) { $clean[$key]=$value; }
            elseif ($key==='form_action' && in_array($value,$actions,true)) { $clean[$key]=$value; }
            elseif ($key==='tab' && $value==='matrices') { $clean[$key]=$value; }
            elseif ($key==='slot' && in_array($value,array('1','2','3','main','preview'),true)) { $clean[$key]=$value; }
        }
        return $clean;
    }

    /** Keep fixed diagnostic text used by current callers; unknown or caller-supplied messages collapse to a constant. */
    private static function sanitize_message($message,$test_type) {
        $safe_messages=array(
            'Category registration workflow finished.','Teacher Matrices tab requested during diagnostic capture.','ZMP test request reached WordPress init.',
            'Diagnostic self-test: admin button pressed.','Diagnostic test capture started.','Diagnostic test capture stopped.',
            'Messenger schema diagnostic completed.',
            'Plugin schema prerequisite not ready.','Messenger schema migration failure detail.',
            'Google Drive video upload started.','Google Drive video upload failed.','Google Drive upload completed but media record could not be saved.',
            'Google Drive video upload and media record completed.','Google Drive video workflow finished.',
            'Mentor action handler entered.','Mentor action handler ignored unknown action.','Mentor profile setup flow started.',
            'Mentor profile setup stopped: nonce verification failed.','Student registration request received.',
            'Student registration stopped: nonce verification failed.','Student registration nonce verified.',
            'Student registration stopped: current user is not authenticated.','Student registration user identified.',
            'Student registration teacher capability checked.','Student registration stopped: user lacks teacher capability.',
            'Looking up mentor profile.','Mentor profile lookup completed.','Student registration stopped: mentor profile not found.',
            'Student registration stopped: no school selected from teacher profile.','Validating submitted student fields.',
            'Student registration stopped: validation failed.','Mentor school and municipality verified.',
            'Generated temporary PIN; attempting encryption.','PIN encryption failed during student registration.',
            'Looking up active competition.','Active competition query completed.','Active competition query returned a database error.',
            'Student registration stopped: no active competition found.','Attempting student database insert.',
            'Student database insert returned success.','Student database insert failed.','Student access-code collision; retrying insert.',
            'Student registration failed after database insert attempts.','Student registration succeeded; redirecting to result page.',
            'Student registration redirect prepared.','President facsimile public-key certificate registered.','President facsimile certificate revoked.',
            'YouTube entry video upload finished.',
            'School engagement schema is not ready.','School engagement migration is ready.','School engagement migration is not ready.',
            'School engagement save blocked by schema readiness.','School engagement activated.','School engagement created.',
            'School profile schema is not ready.','School profile schema is not ready for save.','School profile save authorization denied.',
            'School profile saved.','School directory authorization denied.','School profile operation failed.','School profile first-time President setup denied.',
            'School email access schema prerequisite is not ready.','School email access request submitted.','School email request invalidated during President decision.',
            'School email request decision recorded.','Approved school email request invalidated before disclosure.',
            'Approved school email request revoked after contact change.','Approved school email disclosed to requester.',
            'School email access operation failed.','School email request invalidated after contact change.','Stale school email request invalidated before resubmission.',
            'Subject proposal submission denied.','Subject proposal validation failed.','Subject proposal submitted.','Subject proposal decision denied.',
            'Subject creation failed during proposal approval.','Subject proposal decision committed.','President subject decision denied.',
            'President subject decision request failed security validation.','President subject decision processed.',
            'Teacher register search denied.','Teacher register search completed.','Teacher register search query failed.','Teacher register CSV export denied.',
            'Teacher register CSV batch query failed.','Teacher register CSV staging failed.','Teacher register CSV response failed.',
            'Teacher register CSV export completed.'
        );
        if (is_string($message) && in_array($message,$safe_messages,true)) { return $message; }
        return 'Diagnostic event recorded.';
    }

    private static function request_path() {
        $request = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request, PHP_URL_PATH);
        return is_string($path) ? substr(sanitize_text_field($path), 0, 255) : '';
    }

    private static function is_admin_ajax_request() {
        $admin_ajax_url = admin_url('admin-ajax.php');
        $expected_path = wp_parse_url($admin_ajax_url, PHP_URL_PATH);
        $request_path = self::request_path();
        if (!is_string($expected_path) || $expected_path === '' || $request_path === '') { return false; }
        $expected_path = self::normalize_url_path($expected_path);
        $request_path = self::normalize_url_path($request_path);
        return $expected_path !== '' && $request_path !== '' && hash_equals($expected_path, $request_path);
    }

    private static function normalize_url_path($path) {
        if (!is_string($path) || $path === '' || strlen($path) > 255 || strpos($path, "\0") !== false) { return ''; }
        $path = wp_normalize_path($path);
        $path = '/' . ltrim($path, '/');
        return $path;
    }

    /** Return only the bounded WordPress AJAX action name, never an arbitrary request field. */
    private static function request_action() {
        if (isset($_POST['action'])) { $raw = $_POST['action']; }
        elseif (isset($_GET['action'])) { $raw = $_GET['action']; }
        else { return ''; }
        if (!is_scalar($raw)) { return ''; }
        $raw = (string) wp_unslash((string) $raw);
        if ($raw === '' || strlen($raw) > 256) { return ''; }
        $action = sanitize_key($raw);
        return ($action !== '' && strlen($action) <= 64) ? $action : '';
    }

    private static function infer_test_type() {
        $keys = array_keys((array) $_POST);
        $map = array(
            'zmp_mentor_action' => 'student_registration',
            'zmp_category_entry_action' => 'category_registration',
            'zmp_exam_action' => 'student_exam',
            'zmp_student_exam_action' => 'student_exam',
            'zmp_testator_action' => 'testator',
            'zmp_operations_action' => 'moderator',
            'zmp_moderator_action' => 'moderator',
            'zmp_jury_action' => 'jury',
            'zmp_commission_action' => 'commission',
            'zmp_commission_question_action' => 'commission',
            'zmp_commission_decision_action' => 'commission',
            'zmp_drive_action' => 'google_drive',
            'zmp_request_fee_invoice' => 'communications',
            'zmp_announcement_action' => 'communications',
            'zmp_comms_action' => 'communications',
            'zmp_results_action' => 'results_certificates',
            'zmp_school_profile_action' => 'school_profile',
            'zmp_school_email_action' => 'school_email_access',
            'zmp_school_profile' => 'school_profile',
            'zmp_school_profile_nonce' => 'school_profile',
        );
        foreach ($map as $key => $type) { if (in_array($key, $keys, true)) { return $type; } }
        $request_action = $_REQUEST['action'] ?? '';
        $wp_action = is_scalar($request_action) ? sanitize_key((string) wp_unslash((string) $request_action)) : '';
        if (strpos($wp_action, 'zmp_audio_tool_') === 0 || strpos($wp_action, 'zmp_matrix_') === 0) { return 'matrices_audio_tools'; }
        if (strpos($wp_action, 'zmp_drive_') === 0 || strpos($wp_action, 'zmp_video_') === 0) { return 'google_drive'; }
        if ($wp_action === 'zmp_request_fee_invoice' || strpos($wp_action, 'zmp_announcement_') === 0) { return 'communications'; }
        if (strpos($wp_action, 'zmp_president_mandate_') === 0) { return 'president_mandate'; }
        if (strpos($wp_action, 'zmp_school_email_') === 0) { return 'school_email_access'; }
        if (strpos($wp_action, 'zmp_school_profile_') === 0 || strpos($wp_action, 'zmp_mentor_school_profile_') === 0) { return 'school_profile'; }
        if (in_array($wp_action,array('zmp_submit_subject_proposal','zmp_president_subject_decision'),true)) { return 'teacher_subject_proposals'; }
        if ($wp_action === 'zmp_export_teacher_register' || isset($_REQUEST['zmp_register_name'])
            || (isset($_REQUEST['zmp_portal_tab']) && is_scalar($_REQUEST['zmp_portal_tab'])
                && sanitize_key(wp_unslash((string) $_REQUEST['zmp_portal_tab'])) === 'teacher-register')) { return 'teacher_register'; }
        if (strpos($wp_action, 'zmp_register_president_facsimile_certificate') === 0
            || strpos($wp_action, 'zmp_revoke_president_facsimile_certificate') === 0) { return 'president_facsimile_certificate'; }
        if (strpos($wp_action, 'zmp_download_certificate') === 0) { return 'results_certificates'; }
        if (strpos($wp_action, 'zmp_category_') === 0) { return 'category_registration'; }
        return 'general';
    }

    /** Log the start of plugin POST workflows before their handlers can return or fail. */
    public static function register_request_probe() {
        add_action('init', array(__CLASS__, 'probe_plugin_request'), 1);
    }

    public static function probe_plugin_request() {
        $request_method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
        if ($request_method === 'GET' && is_user_logged_in()) {
            $tab = '';
            foreach (array('zmp_mentor_tab', 'tab') as $query_key) {
                if (isset($_GET[$query_key]) && is_scalar($_GET[$query_key])) {
                    $tab = sanitize_key(wp_unslash($_GET[$query_key]));
                    if ($tab !== '') { break; }
                }
            }
            if ($tab === 'matrices') {
                $capture = self::active_capture();
                if ($capture && ($capture['test_type'] ?? '') === 'matrices_audio_tools') {
                    self::log('info', 'Teacher Matrices tab requested during diagnostic capture.', array('tab' => 'matrices'), 'matrices_audio_tools');
                }
            }
            return;
        }
        if ($request_method !== 'POST') { return; }
        $keys = array_keys((array) $_POST);
        $plugin_fields = array('zmp_mentor_action','zmp_category_entry_action','zmp_exam_action','zmp_student_exam_action','zmp_testator_action','zmp_operations_action','zmp_moderator_action','zmp_jury_action','zmp_commission_action','zmp_commission_question_action','zmp_commission_decision_action','zmp_drive_action','zmp_request_fee_invoice','zmp_announcement_action','zmp_comms_action','zmp_results_action','zmp_school_profile_action','zmp_school_email_action','zmp_school_profile');
        $posted_action_value = $_POST['action'] ?? '';
        $posted_action = is_scalar($posted_action_value) ? sanitize_key((string) wp_unslash((string) $posted_action_value)) : '';
        $is_zmp_action = strpos($posted_action, 'zmp_') === 0;
        if (!array_intersect($plugin_fields, $keys) && !$is_zmp_action) { return; }
        $action = '';
        foreach ($plugin_fields as $field) {
            if (isset($_POST[$field]) && is_scalar($_POST[$field])) { $action = sanitize_key(wp_unslash($_POST[$field])); break; }
        }
        if ($action === '' && $is_zmp_action) { $action = $posted_action; }
        self::log('info', 'ZMP test request reached WordPress init.', array(
            'form_action' => $action,
            'post_fields_count' => count($_POST),
            'logged_in' => is_user_logged_in() ? 1 : 0,
            'role_count' => is_user_logged_in() ? count((array) wp_get_current_user()->roles) : 0,
        ));
    }

    public static function filter_events($events, $test_type = 'all', $session_id = '', $flow_id = '') {
        $result = array();
        $test_type = sanitize_key($test_type);
        $session_id = sanitize_text_field($session_id);
        $flow_id = sanitize_text_field($flow_id);
        foreach ((array) $events as $event) {
            if (!is_array($event)) { continue; }
            $normalized = self::normalize_event($event);
            if ($test_type !== 'all' && $normalized['test_type'] !== $test_type) { continue; }
            if ($session_id !== '' && $normalized['session_id'] !== $session_id) { continue; }
            if ($flow_id !== '' && $normalized['flow_id'] !== $flow_id) { continue; }
            $result[] = $normalized;
        }
        return $result;
    }

    public static function normalize_event($event) {
        $levels = array('debug','info','warning','error');
        $context = self::sanitize_context($event['context'] ?? array());
        $type = sanitize_key($event['test_type'] ?? 'general');
        if (!isset(self::test_types()[$type])) { $type = 'general'; }
        $session_id = sanitize_text_field($event['session_id'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9-]{0,64}$/', $session_id)) { $session_id = ''; }
        $request = (string) ($event['request'] ?? '');
        $request = wp_parse_url($request, PHP_URL_PATH);
        return array(
            'time' => sanitize_text_field($event['time'] ?? ''),
            'level' => in_array($event['level'] ?? '', $levels, true) ? $event['level'] : 'info',
            'test_type' => $type,
            'session_id' => $session_id,
            'flow_id' => sanitize_text_field($context['flow_id'] ?? ''),
            'message' => self::sanitize_message($event['message'] ?? '', $type),
            'user_id' => absint($event['user_id'] ?? 0),
            'request' => is_string($request) ? substr(sanitize_text_field($request), 0, 255) : '',
            'context' => $context,
        );
    }

    public static function export_payload($events, $format) {
        $format = sanitize_key($format);
        if ($format === 'json') { return wp_json_encode(array_values($events), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); }
        if ($format !== 'csv') { return new WP_Error('invalid_export_format'); }
        $stream = fopen('php://temp', 'r+');
        if (!$stream) { return new WP_Error('export_stream_unavailable'); }
        fputcsv($stream, array('time','level','test_type','session_id','flow_id','message','user_id','request','context'));
        foreach ((array) $events as $event) {
            $event = self::normalize_event($event);
            $row = array($event['time'],$event['level'],$event['test_type'],$event['session_id'],$event['flow_id'],$event['message'],$event['user_id'],$event['request'],wp_json_encode($event['context'], JSON_UNESCAPED_UNICODE));
            fputcsv($stream, array_map(array(__CLASS__, 'csv_safe_cell'), $row));
        }
        rewind($stream);
        $payload = stream_get_contents($stream);
        fclose($stream);
        return $payload;
    }

    public static function csv_safe_cell($value) {
        $value = (string) $value;
        $is_number = preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value);
        return !$is_number && preg_match('/^[\t\r ]*[=+@-]/', $value) ? "'" . $value : $value;
    }
}
ZMP_Debug::register_request_probe();
