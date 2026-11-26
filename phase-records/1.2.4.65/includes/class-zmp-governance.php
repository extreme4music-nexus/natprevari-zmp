<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Competition governance workflows: moderator exam controls, commission review,
 * phase closure, season snapshots, exports, and protected yearly archives.
 */
class ZMP_Governance {
    private static $instance = null;
    private static $phases = array('school', 'municipal', 'regional', 'state');
    private static $phase_labels = array('school'=>'Училишно натпреварувачко ниво', 'municipal'=>'Општинско натпреварувачко ниво', 'regional'=>'Регионално натпреварувачко ниво', 'state'=>'Државно натпреварувачко ниво');

    public static function init() {
        if (self::$instance) { return; }
        self::$instance = new self();
    }

    public static function render_commission_panels_static() {
        return self::$instance ? self::$instance->render_commission_panels() : '';
    }

    public static function render_moderator_phase_controls_static() {
        return self::$instance ? self::$instance->render_moderator_phase_controls() : '';
    }
    public static function render_moderator_archive_controls_static() {
        return self::$instance ? self::$instance->render_moderator_archive_controls() : '';
    }

    /** Render certificate links only for the entry owner or authorized competition staff. */
    public static function render_entry_certificates($entry_id) {
        if (!self::$instance || !is_user_logged_in()) { return ''; }
        global $wpdb;
        $entry_id = absint($entry_id);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id,r.season_id,r.category_id,r.phase,r.award_key,r.final_points,c.name_mk AS category_name
             FROM {$wpdb->prefix}zmp_category_final_results r
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id
             WHERE r.entry_id=%d AND r.status='confirmed' ORDER BY r.decided_at", $entry_id
        ));
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) {
            $rows = array_values(array_filter((array)$rows, static function($row) use ($entry_id) {
                return ZMP_Category_Jury::can_access_entry_phase((int)$row->season_id,(int)$row->category_id,$entry_id,(string)$row->phase,get_current_user_id());
            }));
        } elseif (!current_user_can('manage_options') && !current_user_can('zmp_moderator_cap')) {
            $user_id = get_current_user_id();
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT r.id,r.season_id,r.category_id,r.phase,r.award_key,r.final_points,c.name_mk AS category_name
                 FROM {$wpdb->prefix}zmp_category_final_results r
                 JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id
                 LEFT JOIN {$wpdb->prefix}zmp_mentors m ON m.id=e.mentor_id
                 JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id
                 WHERE r.entry_id=%d AND r.status='confirmed' AND (e.wp_user_id=%d OR m.wp_user_id=%d) ORDER BY r.decided_at",
                $entry_id, $user_id, $user_id
            ));
        }
        if (!$rows) { return ''; }
        $html = '<ul class="zmp-certificate-links">';
        foreach ($rows as $row) {
            if (!self::award_label($row->award_key)) { continue; }
            $url = wp_nonce_url(admin_url('admin-post.php?action=zmp_download_certificate&result_id='.(int)$row->id), 'zmp_certificate_'.(int)$row->id);
            $label = self::award_label($row->award_key).' — '.($row->category_name ?: '').' — '.self::phase_label($row->phase);
            $html .= '<li><a href="'.esc_url($url).'">Преземи PDF диплома за печатење: '.esc_html($label).'</a></li>';
        }
        return $html.'</ul>';
    }

    private function __construct() {
        add_action('wp_ajax_zmp_moderator_control_attempt', array($this, 'ajax_moderator_control_attempt'));
        add_action('wp_ajax_zmp_commission_decide_complaint', array($this, 'ajax_commission_decide_complaint'));
        add_action('wp_ajax_zmp_commission_close_phase', array($this, 'ajax_commission_close_phase'));
        add_action('wp_ajax_zmp_commission_finalize_competition', array($this, 'ajax_commission_finalize_competition'));
        add_action('admin_post_zmp_archive_competition', array($this, 'download_new_archive'));
        add_action('admin_post_zmp_download_year_archive', array($this, 'download_archive'));
        add_action('admin_post_zmp_download_certificate', array($this, 'download_certificate'));
        add_action('admin_post_zmp_reset_competition', array($this, 'reset_competition'));
        add_action('template_redirect', array($this, 'maybe_export_public_results'));
    }

    private function can_moderate() {
        return current_user_can('zmp_moderator_cap') || current_user_can('manage_options');
    }

    private function can_commission() {
        return current_user_can('zmp_commission_cap') || current_user_can('manage_options');
    }

    private function active_competition() {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    }

    private function phase_status_rows($competition_id) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT phase,status FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d", absint($competition_id)));
        $indexed = array();
        foreach ((array)$rows as $row) { $indexed[$row->phase] = $row; }
        return $indexed;
    }
    private function require_ajax_capability($capability) {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        if ($capability === 'commission' && class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { wp_send_json_error(array('message'=>'Оваа стара акција не е ограничена по категорија и година.'),403); }
        $allowed = ($capability === 'moderator') ? $this->can_moderate() : $this->can_commission();
        if (!$allowed) { wp_send_json_error(array('message'=>'Немате овластување за оваа акција.'), 403); }
    }

    /** Moderator actions are tied to an in-progress or previously completed attempt and require a written reason. */
    public function ajax_moderator_control_attempt() {
        $this->require_ajax_capability('moderator');
        global $wpdb;
        $attempt_id = absint($_POST['attempt_id'] ?? 0);
        $incident_id = absint($_POST['incident_id'] ?? 0);
        $action_name = sanitize_key($_POST['control_action'] ?? '');
        $reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
        if (!$attempt_id || !in_array($action_name, array('pause','resume','stop','restart'), true) || mb_strlen($reason) < 20) {
            wp_send_json_error(array('message'=>'Изберете акција и внесете образложение од најмалку 20 знаци.'), 400);
        }
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, s.id AS linked_student_id FROM {$wpdb->prefix}zmp_attempts a JOIN {$wpdb->prefix}zmp_students s ON s.id=a.student_id WHERE a.id=%d",
            $attempt_id
        ));
        if (!$attempt) { wp_send_json_error(array('message'=>'Обидот не постои.')); }
        $phase_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s", $attempt->competition_id, $attempt->phase));
        if ($phase_status === 'closed') { wp_send_json_error(array('message'=>'Ова натпреварувачко ниво веќе е затворено од комисијата; резултатите се заклучени.'), 409); }
        if (!$incident_id) {
            wp_send_json_error(array('message'=>'Оваа акција мора да биде поврзана со активен допис од тестаторот за ученикот.'), 400);
        }
        $incident = $wpdb->get_row($wpdb->prepare(
            "SELECT id, student_id, competition_id, status FROM {$wpdb->prefix}zmp_incidents WHERE id=%d",
            $incident_id
        ));
        if (!$incident || (int)$incident->student_id !== (int)$attempt->student_id
            || (int)$incident->competition_id !== (int)$attempt->competition_id
            || $incident->status === 'resolved') {
            wp_send_json_error(array('message'=>'Потребен е неразрешен допис од тестаторот поврзан со истиот ученик и натпревар.'), 400);
        }
        $old_status = $attempt->status . (!empty($attempt->is_paused) ? ':paused' : '');
        $update = array();
        if ($action_name === 'pause' && $attempt->status === 'in_progress' && !$attempt->is_paused) {
            $update = array('is_paused'=>1, 'last_active_at'=>current_time('mysql'));
        } elseif ($action_name === 'resume' && $attempt->status === 'in_progress' && $attempt->is_paused) {
            $update = array('is_paused'=>0, 'last_active_at'=>current_time('mysql'));
        } elseif ($action_name === 'stop' && $attempt->status === 'in_progress') {
            $update = array('status'=>'stopped', 'is_paused'=>1, 'completed_at'=>current_time('mysql'));
        } elseif ($action_name === 'restart' && in_array($attempt->status, array('in_progress','stopped','completed'), true)) {
            $minutes = (int)$wpdb->get_var($wpdb->prepare("SELECT duration_minutes FROM {$wpdb->prefix}zmp_calendar WHERE phase=%s AND is_active=1 ORDER BY id DESC LIMIT 1", $attempt->phase));
            if ($minutes < 1) { $minutes = 45; }
            $update = array('status'=>'in_progress','is_paused'=>0,'started_at'=>current_time('mysql'),'completed_at'=>null,'remaining_seconds'=>$minutes*60,'time_spent_seconds'=>0,'last_active_at'=>current_time('mysql'),'reset_count'=>absint($attempt->reset_count)+1,'reset_reason'=>$reason,'total_points'=>0,'award_level'=>'','qualified_next_stage'=>0,'answers_json'=>wp_json_encode(array()));
        }
        if (!$update) { wp_send_json_error(array('message'=>'Акцијата не е дозволена за моменталниот статус на тестот.'), 409); }
        $new_status = isset($update['status']) ? $update['status'] : ($action_name === 'pause' ? 'in_progress:paused' : 'in_progress');
        $wpdb->query('START TRANSACTION');
        $updated = $wpdb->update($wpdb->prefix.'zmp_attempts', $update, array('id'=>$attempt_id));
        $logged = $updated !== false && $wpdb->insert($wpdb->prefix.'zmp_moderator_actions', array(
            'competition_id'=>absint($attempt->competition_id),'incident_id'=>$incident_id,'attempt_id'=>$attempt_id,
            'student_id'=>absint($attempt->student_id),'moderator_user_id'=>get_current_user_id(),
            'action'=>$action_name,'reason'=>$reason,'previous_status'=>$old_status,'new_status'=>$new_status,
            'previous_remaining_seconds'=>absint($attempt->remaining_seconds),'previous_score'=>(float)$attempt->total_points,
            'previous_completed_at'=>$attempt->completed_at,'previous_answers_json'=>$attempt->answers_json
        ));
        if ($updated === false || !$logged) { $wpdb->query('ROLLBACK'); wp_send_json_error(array('message'=>'Акцијата или нејзината ревизорска трага не можеа да се зачуваат.')); }
        $wpdb->query('COMMIT');
        wp_send_json_success(array('message'=>'Акцијата е запишана во дневникот.'));
    }

    /** Commission adjudication requires a factual rationale; point changes are explicit new totals and audited. */
    public function ajax_commission_decide_complaint() {
        $this->require_ajax_capability('commission');
        global $wpdb;
        $complaint_id = absint($_POST['complaint_id'] ?? 0);
        $outcome = sanitize_key($_POST['outcome'] ?? '');
        $rationale = sanitize_textarea_field(wp_unslash($_POST['rationale'] ?? ''));
        $question_ref = sanitize_text_field(wp_unslash($_POST['question_ref'] ?? ''));
        $marking_basis = sanitize_text_field(wp_unslash($_POST['marking_basis'] ?? ''));
        $score_raw = isset($_POST['new_score']) ? trim((string)wp_unslash($_POST['new_score'])) : '';
        if (!$complaint_id || !in_array($outcome, array('accepted','rejected'), true) || $question_ref === '' || $marking_basis === '' || mb_strlen($rationale) < 30) {
            wp_send_json_error(array('message'=>'Одлуката бара референца за прашањето, основа за бодување и проверливо образложение од најмалку 30 знаци.'), 400);
        }
        $rationale = "Прашање/дел: {$question_ref}\nОснова за оценување: {$marking_basis}\nФактичко образложение: {$rationale}";
        if ($score_raw !== '' && (!is_numeric($score_raw) || (float)$score_raw < 0 || (float)$score_raw > 100)) {
            wp_send_json_error(array('message'=>'Новиот вкупен резултат мора да биде број од 0 до 100.')); 
        }
        $complaint = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_complaints WHERE id=%d AND status IN ('pending','forwarded')", $complaint_id));
        if (!$complaint) { wp_send_json_error(array('message'=>'Жалбата веќе е обработена или не постои.'), 404); }
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_attempts WHERE id=%d AND student_id=%d", $complaint->attempt_id, $complaint->student_id));
        if (!$attempt || $attempt->status !== 'completed') { wp_send_json_error(array('message'=>'Не е пронајден завршен тест поврзан со жалбата.')); }
        if ($score_raw !== '' && $outcome !== 'accepted') { wp_send_json_error(array('message'=>'Промена на бодови може да се направи само при прифаќање на жалбата.')); }
        $phase_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s", $attempt->competition_id, $attempt->phase));
        $next_index = array_search($attempt->phase, self::$phases, true) + 1;
        $downstream_phase = ($next_index < count(self::$phases)) ? self::$phases[$next_index] : '';
        if ($score_raw !== '' && $phase_status === 'closed' && $downstream_phase) {
            $downstream_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_attempts WHERE competition_id=%d AND phase=%s", $attempt->competition_id, $downstream_phase));
            if ($downstream_count) { wp_send_json_error(array('message'=>'Бодовна промена е блокирана бидејќи следното натпреварувачко ниво веќе започнало. Комисијата може да ја реши жалбата без промена на бодовите.')); }
        }
        $old_score = (float)$attempt->total_points;
        $new_score = $score_raw === '' ? $old_score : round((float)$score_raw, 2);
        $award = ZMP_Scoring::get_award_details($new_score, 'mk');
        $wpdb->query('START TRANSACTION');
        $updated = $wpdb->update($wpdb->prefix.'zmp_complaints', array(
            'status'=>$outcome,'points_adjustment'=>round($new_score-$old_score,2),
            'admin_notes'=>$rationale,'reviewed_at'=>current_time('mysql')
        ), array('id'=>$complaint_id,'status'=>$complaint->status));
        $attempt_updated = $updated === 1 ? $wpdb->update($wpdb->prefix.'zmp_attempts', array('total_points'=>$new_score,'award_level'=>$award['key']), array('id'=>$attempt->id)) : false;
        if ($updated !== 1 || $attempt_updated === false) { $wpdb->query('ROLLBACK'); wp_send_json_error(array('message'=>'Жалбата е веќе обработена или резултатот не може да се ажурира. Ништо не е променето.'), 409); }
        if ($score_raw !== '' && $phase_status === 'closed' && $downstream_phase) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_students s JOIN {$wpdb->prefix}zmp_attempts a ON a.student_id=s.id SET s.current_phase=%s,s.qualified_for_next=0 WHERE a.competition_id=%d AND a.phase=%s AND s.current_phase=%s", $attempt->phase, $attempt->competition_id, $attempt->phase, $downstream_phase));
            ZMP_Scoring::process_phase_qualifications($attempt->phase, $attempt->competition_id);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_phase_results r JOIN {$wpdb->prefix}zmp_attempts a ON a.id=r.attempt_id SET r.total_points=a.total_points,r.award_level=a.award_level,r.qualified_next_stage=a.qualified_next_stage WHERE r.competition_id=%d AND r.phase=%s", $attempt->competition_id, $attempt->phase));
        }
        $audit = $wpdb->insert($wpdb->prefix.'zmp_score_adjustments', array(
            'competition_id'=>absint($attempt->competition_id),'complaint_id'=>$complaint_id,'attempt_id'=>absint($attempt->id),
            'student_id'=>absint($attempt->student_id),'commission_user_id'=>get_current_user_id(),'outcome'=>$outcome,
            'old_score'=>$old_score,'new_score'=>$new_score,'rationale'=>$rationale
        ));
        if ($audit === false) { $wpdb->query('ROLLBACK'); wp_send_json_error(array('message'=>'Одлуката не е зачувана во целост. Ништо не е променето.')); }
        $wpdb->update($wpdb->prefix.'zmp_phase_results', array('total_points'=>$new_score,'award_level'=>$award['key']), array('attempt_id'=>$attempt->id));
        $wpdb->update($wpdb->prefix.'zmp_commission_decisions', array('status'=>$outcome==='accepted'?'approved':'rejected','decision_note'=>$rationale,'reviewed_by'=>get_current_user_id(),'reviewed_at'=>current_time('mysql')), array('source_type'=>'complaint','source_id'=>$complaint_id,'status'=>'pending'));
        $wpdb->query('COMMIT');
        wp_send_json_success(array('message'=>'Одлуката е зачувана со старите и новите бодови и образложението.'));
    }

    /** Close only the current phase after all running attempts are resolved; snapshot scores before advancing. */
    public function ajax_commission_close_phase() {
        $this->require_ajax_capability('moderator');
        global $wpdb;
        $comp = $this->active_competition();
        $phase = sanitize_key($_POST['phase'] ?? '');
        $reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));
        if (!$comp || !in_array($phase, self::$phases, true) || $phase !== $comp->current_phase || mb_strlen($reason) < 30) {
            wp_send_json_error(array('message'=>'Може да се затвори само тековното натпреварувачко ниво и потребно е образложение од најмалку 30 знаци.'), 400);
        }
        $running = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_attempts WHERE competition_id=%d AND phase=%s AND status='in_progress'", $comp->id, $phase));
        if ($running) { wp_send_json_error(array('message'=>sprintf('Натпреварувачкото ниво не може да се затвори: остануваат %d активни тестови. Тестовите мора да бидат завршени или модераторски запрени.', $running)), 409); }
        $status_table = $wpdb->prefix.'zmp_phase_status';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $status_table WHERE competition_id=%d AND phase=%s", $comp->id, $phase));
        if (!$row || $row->status !== 'open') { wp_send_json_error(array('message'=>'Ова натпреварувачко ниво веќе е затворено.'), 409); }

        if ($phase !== 'state') { ZMP_Scoring::process_phase_qualifications($phase, $comp->id); }
        $results_table = $wpdb->prefix.'zmp_phase_results';
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $results_table (competition_id,attempt_id,student_id,mentor_id,mentor_user_id,academic_year,phase,student_name,first_name,last_name,access_code,school_name,municipality,region,grade,total_points,award_level,qualified_next_stage,completed_at)
             SELECT a.competition_id,a.id,s.id,s.mentor_id,COALESCE(m.wp_user_id,0),c.academic_year,a.phase,CONCAT(s.first_name,' ',s.last_name),s.first_name,s.last_name,s.access_code,s.school_name,s.municipality,COALESCE(NULLIF(s.region,''),NULLIF(m.region,''),''),s.grade,a.total_points,a.award_level,a.qualified_next_stage,a.completed_at
             FROM {$wpdb->prefix}zmp_attempts a JOIN {$wpdb->prefix}zmp_students s ON s.id=a.student_id
             LEFT JOIN {$wpdb->prefix}zmp_mentors m ON m.id=s.mentor_id
             JOIN {$wpdb->prefix}zmp_competitions c ON c.id=a.competition_id
             WHERE a.competition_id=%d AND a.phase=%s AND a.status='completed'",
            $comp->id, $phase
        ));
        if ($wpdb->last_error) { wp_send_json_error(array('message'=>'Резултатите не можеа да се зачуваат: '.$wpdb->last_error), 500); }

        $wpdb->query('START TRANSACTION');
        $closed = $wpdb->update($status_table, array('status'=>'closed','closed_by'=>get_current_user_id(),'closed_at'=>current_time('mysql'),'decision_reason'=>$reason), array('id'=>$row->id,'status'=>'open'));
        if ($closed !== 1) { $wpdb->query('ROLLBACK'); wp_send_json_error(array('message'=>'Натпреварувачкото ниво не е затворена. Освежете ја страницата и проверете го статусот.'), 409); }
        $next_index = array_search($phase, self::$phases, true) + 1;
        if ($next_index < count(self::$phases)) {
            $wpdb->update($wpdb->prefix.'zmp_competitions', array('current_phase'=>self::$phases[$next_index]), array('id'=>$comp->id));
        } else {
            $wpdb->update($wpdb->prefix.'zmp_competitions', array('workflow_status'=>'ready_to_complete','completion_note'=>$reason), array('id'=>$comp->id));
        }
        $wpdb->query('COMMIT');
        wp_send_json_success(array('message'=> $phase === 'state' ? 'Државното натпреварувачко ниво е затворено. Комисијата треба посебно да ја поднесе годината како целосно завршена.' : 'Натпреварувачкото ниво е затворено, резултатите се архивирани и тековното натпреварувачко ниво е унапредено.'));
    }

    /** Final annual submission is a moderator action after all four phases are closed. */
    public function ajax_commission_finalize_competition() {
        $this->require_ajax_capability('moderator');
        global $wpdb;
        $raw_reason = $_POST['reason'] ?? null;
        if (!is_string($raw_reason)) {
            wp_send_json_error(array('message'=>'Внесете валидно завршно образложение.'), 400);
        }
        $comp = $this->active_competition();
        $reason = sanitize_textarea_field(wp_unslash($raw_reason));
        if (!$comp || $comp->workflow_status !== 'ready_to_complete' || mb_strlen($reason) < 30) {
            wp_send_json_error(array('message'=>'Затворете ги сите натпреварувачки нивоа и внесете завршно образложение од најмалку 30 знаци.'), 400);
        }
        $phase_placeholders = implode(',', array_fill(0, count(self::$phases), '%s'));
        $phase_query = "SELECT phase FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND status='closed' AND phase IN ($phase_placeholders)";
        $closed_phases = $wpdb->get_col($wpdb->prepare($phase_query, array_merge(array((int)$comp->id), self::$phases)));
        $closed_phase_set = array_fill_keys(array_map('strval', (array)$closed_phases), true);
        foreach (self::$phases as $required_phase) {
            if (!isset($closed_phase_set[$required_phase])) {
                wp_send_json_error(array('message'=>'Не се затворени сите четири натпреварувачки нивоа.'), 409);
            }
        }
        $updated = $wpdb->update($wpdb->prefix.'zmp_competitions', array(
            'is_active'=>0,'workflow_status'=>'completed','completed_at'=>current_time('mysql'),
            'finalized_by'=>get_current_user_id(),'finalized_at'=>current_time('mysql'),'finalization_note'=>$reason
        ), array('id'=>$comp->id,'workflow_status'=>'ready_to_complete'));
        if ($updated !== 1) { wp_send_json_error(array('message'=>'Годината е веќе поднесена или не може да се ажурира.'), 409); }
        wp_send_json_success(array('message'=>'Натпреварувачката година е официјално завршена. Модераторите сега можат да креираат архива.'));
    }

    /** Render commission-owned complaint decisions and score audit records only. */
    public function render_commission_panels() {
        if (!$this->can_commission()) { return ''; }
        global $wpdb;
        $comp = $this->active_competition();
        $status_rows = $comp ? $this->phase_status_rows((int)$comp->id) : array();
        $complaints = $wpdb->get_results("SELECT c.*,s.first_name,s.last_name,s.school_name FROM {$wpdb->prefix}zmp_complaints c LEFT JOIN {$wpdb->prefix}zmp_students s ON s.id=c.student_id ORDER BY c.created_at DESC LIMIT 100");
        $score_history = $wpdb->get_results("SELECT a.*,u.display_name,c.student_name,c.student_code AS access_code FROM {$wpdb->prefix}zmp_score_adjustments a LEFT JOIN {$wpdb->users} u ON u.ID=a.commission_user_id LEFT JOIN {$wpdb->prefix}zmp_complaints c ON c.id=a.complaint_id ORDER BY a.created_at DESC LIMIT 100");
        ob_start(); ?>
        <section class="zmp-card zmp-commission-progress" data-commission-panel="progress" role="region" aria-labelledby="zmp-commission-progress-title">
            <h3 id="zmp-commission-progress-title">Тековен процес</h3>
            <?php if (!$comp): ?><p>Нема активен натпреварувачки циклус.</p><?php else: ?>
                <p>Натпреварувачка година: <strong><?php echo esc_html($comp->academic_year); ?></strong></p>
                <ol class="zmp-phase-status-list"><?php foreach (self::$phase_labels as $phase_key => $phase_label): $phase_row = $status_rows[$phase_key] ?? null; ?>
                    <li data-phase="<?php echo esc_attr($phase_key); ?>"><?php echo esc_html($phase_label); ?> — <?php echo esc_html($phase_row->status ?? ($phase_key === $comp->current_phase ? 'open' : 'pending')); ?></li>
                <?php endforeach; ?></ol>
            <?php endif; ?>
        </section>
        <section class="zmp-card zmp-commission-governance" data-commission-panel="arbitration" role="region" aria-labelledby="zmp-commission-tab-arbitration">
            <h3>Жалби од наставници — одлука на Комисијата</h3>
            <?php if (empty($complaints)): ?><p>Нема отворени жалби.</p><?php else: foreach ($complaints as $complaint): ?>
                <article class="zmp-complaint-row"><h4><?php echo esc_html(($complaint->student_code ?: ('#'.$complaint->student_id)).' — '.($complaint->first_name ?? '').' '.($complaint->last_name ?? '')); ?></h4>
                    <p><?php echo esc_html($complaint->message); ?></p><p><?php echo esc_html($complaint->school_name ?? ''); ?></p>
                    <form class="zmp-commission-complaint-form">
                        <?php wp_nonce_field('zmp_exam_nonce', 'nonce'); ?><input type="hidden" name="complaint_id" value="<?php echo (int)$complaint->id; ?>">
                        <label>Образложение на одлуката<br><textarea name="decision_note" rows="3" required></textarea></label>
                        <button type="button" class="button button-primary zmp-commission-complaint-decision" data-outcome="accepted">Прифати жалба</button>
                        <button type="button" class="button zmp-commission-complaint-decision" data-outcome="rejected">Одбиј жалба</button>
                    </form>
                </article>
            <?php endforeach; endif; ?>
        </section>
        <section class="zmp-card zmp-commission-score-history" data-commission-panel="records" role="region" aria-labelledby="zmp-commission-tab-records"><h3>Ревизорска трага на одлуки за бодови</h3>
            <?php if (empty($score_history)): ?><p>Нема корекции на бодови.</p><?php else: ?><div class="zmp-table-scroll"><table class="widefat striped"><thead><tr><th>Учесник</th><th>Комисија</th><th>Претходни поени</th><th>Нови поени</th><th>Образложение</th><th>Датум</th></tr></thead><tbody>
                <?php foreach ($score_history as $entry): ?><tr><td><?php echo esc_html(($entry->access_code ?: 'Учесник').' — '.($entry->student_name ?? '')); ?></td><td><?php echo esc_html($entry->display_name ?? ''); ?></td><td><?php echo esc_html($entry->previous_points); ?></td><td><?php echo esc_html($entry->new_points); ?></td><td><?php echo esc_html($entry->reason); ?></td><td><?php echo esc_html($entry->created_at); ?></td></tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }

    /** Stage closure and annual completion are controlled only from the moderator workspace. */
    public function render_moderator_phase_controls() {
        if (!$this->can_moderate()) { return ''; }
        $comp = $this->active_competition();
        $status_rows = $comp ? $this->phase_status_rows((int)$comp->id) : array();
        ob_start(); ?>
        <section class="zmp-card zmp-phase-governance" data-moderator-panel="phase-closure" role="region" aria-labelledby="zmp-moderator-phase-title">
            <h3 id="zmp-moderator-phase-title">Натпреварувачки нивоа на натпреварувачката година</h3>
            <?php if (!$comp): ?><p>Нема активен натпреварувачки циклус.</p>
            <?php else: ?><p>Натпреварувачка година: <strong><?php echo esc_html($comp->academic_year); ?></strong></p>
                <ol class="zmp-phase-status-list"><?php foreach (self::$phase_labels as $phase_key => $phase_label): $phase_row = $status_rows[$phase_key] ?? null; ?>
                    <li data-phase="<?php echo esc_attr($phase_key); ?>"><?php echo esc_html($phase_label); ?> — <?php echo esc_html($phase_row->status ?? ($phase_key === $comp->current_phase ? 'open' : 'pending')); ?></li>
                <?php endforeach; ?></ol>
                <?php if ($comp->workflow_status === 'ready_to_complete'): ?>
                    <form class="zmp-finalize-competition-form"><label>Завршно решение и потврда дека се разгледани резултатите, жалбите и записите за сите натпреварувачки нивоа (најмалку 30 знаци)<br><textarea name="reason" rows="3" minlength="30" required></textarea></label><button type="button" class="button button-primary zmp-finalize-competition">Поднеси ја годината како целосно завршена</button></form>
                <?php elseif ($comp->workflow_status === 'completed'): ?><p>Натпреварувачката година е финализирана.</p>
                <?php else: ?><form class="zmp-close-phase-form"><input type="hidden" name="phase" value="<?php echo esc_attr($comp->current_phase); ?>"><label>Образложение за затворање на тековното натпреварувачко ниво<br><textarea name="reason" rows="3" minlength="30" required></textarea></label><button type="button" class="button button-primary zmp-close-phase">Затвори натпреварувачко ниво: <?php echo esc_html(self::$phase_labels[$comp->current_phase] ?? $comp->current_phase); ?></button></form><?php endif; ?>
            <?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }
    /** Show archive and new-season reset actions only after the moderator has closed all four phases. */
    public function render_moderator_archive_controls() {
        if (!$this->can_moderate()) { return ''; }
        global $wpdb;
        $completed = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE workflow_status='completed' ORDER BY id DESC");
        if (!$completed) { return ''; }
        ob_start(); ?>
        <section class="zmp-card"><h3>Архива и започнување нова натпреварувачка година</h3>
        <p>Архивата е ZIP со CSV извоз на записите за избраната натпреварувачка година, вклучувајќи ги обидите и одговорите, интерните дописи, жалбите, записите за пауза/стоп/рестарт, бодовните одлуки и затворањето на натпреварувачки нивоате. Поставките за поврзување, лозинки/PIN-клучеви, токени и приватни патеки не се вклучени. Зачуваната архива се презема само преку проверка на улога и nonce.</p>
        <?php foreach ($completed as $season):
            $archive = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_competition_archives WHERE competition_id=%d ORDER BY id DESC LIMIT 1", $season->id)); ?>
            <div class="zmp-card"><b><?php echo esc_html($season->title . ' (' . $season->academic_year . ')'); ?></b>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="zmp_archive_competition"><input type="hidden" name="competition_id" value="<?php echo esc_attr($season->id); ?>"><?php wp_nonce_field('zmp_archive_competition_'.$season->id); ?><button class="button button-primary">Создај и преземи целосна ZIP архива</button></form>
                <?php if ($archive && file_exists($archive->file_path)): ?>
                    <p>Последна архива: <?php echo esc_html($archive->created_at); ?> | SHA-256: <code><?php echo esc_html($archive->sha256); ?></code></p>
                    <p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_download_year_archive&archive_id='.$archive->id), 'zmp_download_archive_'.$archive->id)); ?>">Преземи ја зачуваната архива повторно</a></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Ова ќе ги избрише оперативните податоци за тековната година и ќе започне нова година. Потврдете само откако ќе ја преземете и проверите ZIP архивата.');">
                        <input type="hidden" name="action" value="zmp_reset_competition"><input type="hidden" name="competition_id" value="<?php echo esc_attr($season->id); ?>"><input type="hidden" name="archive_id" value="<?php echo esc_attr($archive->id); ?>">
                        <label>Нова учебна година (пример 2027/2028) <input name="new_academic_year" required pattern="[0-9]{4}/[0-9]{4}" placeholder="2027/2028"></label>
                        <label><input type="checkbox" name="archive_verified" value="1" required> Ја презедов и ја проверив архивата.</label>
                        <?php wp_nonce_field('zmp_reset_competition_'.$season->id); ?><button class="button button-secondary">Потврди архива и започни нова година</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?></section>
        <?php return ob_get_clean();
    }

    private function private_archive_dir() {
        return trailingslashit(dirname(untrailingslashit(ABSPATH))) . 'zmp-private-archives';
    }

    private function stream_archive($file, $delete_after = false) {
        if (!is_file($file) || !is_readable($file)) { wp_die(esc_html__('Архивата не е пронајдена.', 'zmp-competitions'), '', array('response'=>404)); }
        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: '.filesize($file));
        readfile($file);
        if ($delete_after) { @unlink($file); }
        exit;
    }

    private static function award_label($award) {
        $labels = array('first'=>'Прва награда','second'=>'Втора награда','third'=>'Трета награда','honorable'=>'Пофалница');
        return $labels[$award] ?? '';
    }

    private static function phase_label($phase) {
        $labels = array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво');
        return $labels[$phase] ?? sanitize_text_field($phase);
    }

    /** Build an escaped printable certificate from an immutable commission-confirmed result. */
    private static function certificate_html($row) {
        $name = esc_html($row->participant_name);
        $school = esc_html($row->school_name);
        $category = esc_html($row->category_name);
        $group = esc_html($row->group_name ?: '');
        $year = esc_html($row->academic_year);
        $award = esc_html(self::award_label($row->award_key));
        $phase = esc_html(self::phase_label($row->phase));
        $points = esc_html(number_format((float)$row->final_points, 2, ',', ''));
        $title = esc_html($row->competition_title ?: 'Државни натпревари на ЗМП');
        return '<!doctype html><html lang="mk"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Диплома — '.$name.'</title><style>@page{size:A4 landscape;margin:14mm}*{box-sizing:border-box}body{font-family:Georgia,"Times New Roman",serif;color:#17345c;background:#fff;margin:0}.certificate{height:180mm;border:10px double #b08b3b;padding:18mm;text-align:center;position:relative}.eyebrow{font:600 14pt Arial,sans-serif;letter-spacing:2px;text-transform:uppercase}.title{font-size:34pt;margin:12mm 0 2mm}.recipient{font-size:27pt;font-weight:bold;border-bottom:1px solid #b08b3b;display:inline-block;min-width:65%;padding:0 10mm 4mm}.details{font:14pt/1.7 Arial,sans-serif;margin:10mm auto;max-width:85%}.award{font-size:25pt;font-weight:bold;color:#8a641d;margin:8mm}.seal{margin-top:12mm;font:12pt Arial,sans-serif}.print{font:14px Arial,sans-serif;margin:12px auto;display:block}@media print{.print{display:none}}</style></head><body><button class="print" onclick="window.print()">Печати / Зачувај како PDF</button><main class="certificate"><div class="eyebrow">Акредитирано здружение на музички педагози на Македонија</div><h1 class="title">Диплома</h1><div class="recipient">'.$name.'</div><p class="details">за освоена награда на натпреварот <strong>'.$title.'</strong><br>Категорија: '.$category.($group!==''?'; група: '.$group:'').'<br>Натпреварувачко ниво: '.$phase.' · '.$year.'<br>Училиште: '.$school.'<br>Остварени бодови: '.$points.'</p><div class="award">'.$award.'</div><div class="seal">Комисија на ЗМП<br><small>Потврден резултат: '.esc_html($row->decided_at).'</small></div></main></body></html>';
    }

    /** Printable certificate content is available only for a confirmed result and a valid award. */
    public static function certificate_html_for_result($result_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT r.id,r.phase,r.award_key,r.final_points,r.decided_at,e.participant_name,e.school_name,g.name_mk AS group_name,c.name_mk AS category_name,s.academic_year,co.title AS competition_title
             FROM {$wpdb->prefix}zmp_category_final_results r
             JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id
             LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id
             JOIN {$wpdb->prefix}zmp_seasons s ON s.id=r.season_id
             LEFT JOIN {$wpdb->prefix}zmp_competitions co ON co.id=s.legacy_competition_id
             WHERE r.id=%d AND r.status='confirmed'", absint($result_id)
        ));
        if (!$row || !self::award_label($row->award_key)) { return false; }
        return self::certificate_html($row);
    }

    /** Keep certificate access available to the entry owner and competition staff only. */
    public static function can_view_certificate($owner_user_id,$season_id=0,$category_id=0,$entry_id=0,$phase='') {
        if (!is_user_logged_in()) { return false; }
        if (current_user_can('manage_options') || current_user_can('zmp_moderator_cap')) { return true; }
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) {
            return $season_id>0 && $category_id>0 && $entry_id>0 && $phase!==''
                && ZMP_Category_Jury::can_access_entry_phase((int)$season_id,(int)$category_id,(int)$entry_id,(string)$phase,get_current_user_id());
        }
        $user_id=get_current_user_id();
        if ((int)$owner_user_id===$user_id) { return true; }
        if (!$entry_id) { return false; }
        global $wpdb;
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_entries e LEFT JOIN {$wpdb->prefix}zmp_mentors m ON m.id=e.mentor_id WHERE e.id=%d AND e.season_id=%d AND e.category_id=%d AND (e.wp_user_id=%d OR m.wp_user_id=%d)",
            absint($entry_id),absint($season_id),absint($category_id),$user_id,$user_id
        ));
    }

    /** Return only commission-closed knowledge results for immutable diploma archiving. */
    private static function knowledge_certificate_rows_for_archive($season_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.id,p.competition_id,p.phase,p.award_level AS award_key,p.total_points AS final_points,p.completed_at AS decided_at,p.student_name AS participant_name,p.first_name,p.last_name,p.school_name,'' AS group_name,c.id AS category_id,c.name_mk AS category_name,c.participant_type,s.id AS season_id,s.academic_year,co.title AS competition_title
             FROM {$wpdb->prefix}zmp_phase_results p
             JOIN {$wpdb->prefix}zmp_seasons s ON s.academic_year=p.academic_year AND s.legacy_competition_id=p.competition_id
             JOIN {$wpdb->prefix}zmp_categories c ON c.category_code='knowledge_test'
             JOIN {$wpdb->prefix}zmp_phase_status ps ON ps.competition_id=p.competition_id AND ps.phase=p.phase AND ps.status='closed'
             LEFT JOIN {$wpdb->prefix}zmp_competitions co ON co.id=p.competition_id
             WHERE s.id=%d AND p.award_level<>'' ORDER BY p.student_name,p.phase",
            absint($season_id)
        ));
    }
    /** Add printable certificates for a single season; reject a failed ZIP write. */
    /** Add immutable, privately issued PDF certificates for confirmed results to the season archive. */
    /** Add immutable, privately issued PDFs for every confirmed category and knowledge-test result. */
    public static function add_certificates_to_archive($archive, $season_id) {
        global $wpdb;
        $category_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id,r.season_id,r.phase,r.award_key,r.final_points,r.decided_at,e.id AS entry_id,e.participant_name,e.school_name,g.name_mk AS group_name,c.id AS category_id,c.name_mk AS category_name,c.participant_type,s.academic_year,co.title AS competition_title
             FROM {$wpdb->prefix}zmp_category_final_results r
             JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id
             LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id
             JOIN {$wpdb->prefix}zmp_seasons s ON s.id=r.season_id
             LEFT JOIN {$wpdb->prefix}zmp_competitions co ON co.id=s.legacy_competition_id
             WHERE r.season_id=%d AND r.status='confirmed' ORDER BY c.sort_order,e.participant_name,r.phase", absint($season_id)
        ));
        $knowledge_rows = self::knowledge_certificate_rows_for_archive($season_id);
        foreach ((array)$category_rows as $row) { $row->certificate_type='category'; }
        foreach ((array)$knowledge_rows as $row) { $row->certificate_type='knowledge'; $row->entry_id=0; }
        foreach (array_merge((array)$category_rows,(array)$knowledge_rows) as $row) {
            if (!self::award_label($row->award_key)) { continue; }
            if (!class_exists('ZMP_Certificate_Templates')) { return false; }
            $pdf = ZMP_Certificate_Templates::get_issued_pdf_file($row->certificate_type, (int)$row->id, $row);
            if (is_wp_error($pdf) || !is_file($pdf) || !is_readable($pdf)) { if (!is_wp_error($pdf)) { @unlink($pdf); } return false; }
            $category_slug = sanitize_title($row->category_name);
            $category_folder = 'category-'.(int)$row->category_id.($category_slug ? '-'.$category_slug : '');
            $path = 'certificates/'.sanitize_file_name($row->academic_year).'/'.$category_folder.'/'.sanitize_key($row->phase).'/'.$row->certificate_type.'-'.(int)$row->id.'.pdf';
            $added = $archive->addFile($pdf, $path);
            @unlink($pdf);
            if (!$added) { return false; }
        }
        return true;
    }
    /** Authorized owner/staff can view a printable diploma; only confirmed decisions qualify. */
    public function download_certificate() {
        if (!is_user_logged_in()) { wp_die('Потребна е најава.', '', array('response'=>403)); }
        $id = absint($_GET['result_id'] ?? 0);
        check_admin_referer('zmp_certificate_'.$id);
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT r.id,r.season_id,r.category_id,r.phase,r.award_key,r.final_points,r.decided_at,e.id AS entry_id,e.participant_name,e.school_name,g.name_mk AS group_name,c.name_mk AS category_name,c.participant_type,s.academic_year,co.title AS competition_title,e.wp_user_id
             FROM {$wpdb->prefix}zmp_category_final_results r
             JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=r.entry_id
             LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id
             JOIN {$wpdb->prefix}zmp_categories c ON c.id=r.category_id
             JOIN {$wpdb->prefix}zmp_seasons s ON s.id=r.season_id
             LEFT JOIN {$wpdb->prefix}zmp_competitions co ON co.id=s.legacy_competition_id
             WHERE r.id=%d AND r.status='confirmed'", $id
        ));
        if (!$row) { wp_die('Потврдена диплома не е пронајдена.', '', array('response'=>404)); }
        if (!self::can_view_certificate($row->wp_user_id,(int)$row->season_id,(int)$row->category_id,(int)$row->entry_id,(string)$row->phase)) { wp_die('Немате дозвола за оваа диплома.', '', array('response'=>403)); }
        if (!self::award_label($row->award_key)) { wp_die('Наградата не е валидна.', '', array('response'=>409)); }
        if (!class_exists('ZMP_Certificate_Templates')) { wp_die('PDF дипломата не е достапна.', '', array('response'=>503)); }
        ZMP_Certificate_Templates::stream_pdf_for_result('category', $id, $row);
    }

    /** Generate a season archive from every plugin table except credential/settings configuration. */
    public function download_new_archive() {
        if (!$this->can_moderate()) { wp_die('Немате дозвола.', '', array('response'=>403)); }
        $competition_id = absint($_POST['competition_id'] ?? 0);
        check_admin_referer('zmp_archive_competition_'.$competition_id);
        global $wpdb;
        $comp = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE id=%d AND workflow_status='completed'", $competition_id));
        if (!$comp) { wp_die('Архива е достапна само за завршен натпревар.', '', array('response'=>409)); }
        if (!class_exists('ZipArchive') && !class_exists('PharData')) { wp_die('На серверот не е овозможена PHP екстензија за креирање ZIP архиви.', '', array('response'=>500)); }
        $dir = $this->private_archive_dir();
        if (!wp_mkdir_p($dir)) { wp_die('Не може да се создаде приватен архивски директориум.', '', array('response'=>500)); }
        @file_put_contents($dir.'/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($dir.'/index.php', "<?php http_response_code(404); exit;\n");
        $file = trailingslashit($dir).'zmp-'.$competition_id.'-'.gmdate('Ymd-His').'-'.wp_generate_password(8,false,false).'.zip';
        $use_zip_archive = class_exists('ZipArchive');
        if ($use_zip_archive) {
            $zip = new ZipArchive();
            if ($zip->open($file, ZipArchive::CREATE | ZipArchive::EXCL) !== true) { wp_die('ZIP архивата не може да се создаде.', '', array('response'=>500)); }
        } else {
            try { $zip = new PharData($file, 0, null, Phar::ZIP); }
            catch (Throwable $error) { wp_die('ZIP архивата не може да се создаде.', '', array('response'=>500)); }
        }
        $prefix = $wpdb->prefix.'zmp_';
        $tables = $wpdb->get_col('SHOW TABLES');
        // Do not leak database paths from the legacy reset log. Operational tables
        // are scoped below to this competition; reference tables are retained whole.
        $excluded = array($wpdb->prefix.'zmp_settings', $wpdb->prefix.'zmp_competition_archives', $wpdb->prefix.'zmp_reset_log', $wpdb->prefix.'zmp_document_repair_tokens');
        $temp_files = array();
        $manifest = "ZMP Competition Archive\nTitle: {$comp->title}\nAcademic year: {$comp->academic_year}\nCompetition ID: {$competition_id}\nCreated UTC: ".gmdate('c')."\nSeason-linked tables contain only records for this competition. Shared reference tables are included as lookup data. actors.csv maps recorded WordPress user IDs to display names and roles. Passwords, credential settings, resumable upload bearer URLs, private jury notes, private archive path ledger, and legacy reset log (which contains server paths) are excluded. Private jury evidence is available only through its complaint-scoped, audited review workflow.\n";
        $zip->addFromString('MANIFEST.txt', $manifest);
        foreach ($tables as $table) {
            if (strpos($table, $prefix) !== 0 || in_array($table, $excluded, true)) { continue; }
            $columns = $wpdb->get_col('SHOW COLUMNS FROM `'.esc_sql($table).'`', 0);
            $where = '';
            $query_args = array();
            if ($table === $wpdb->prefix.'zmp_competitions') {
                $where = ' WHERE id=%d';
                $query_args[] = $competition_id;
            } elseif ($table === $wpdb->prefix.'zmp_seasons') {
                $where = ' WHERE legacy_competition_id=%d';
                $query_args[] = $competition_id;
            } elseif (in_array('competition_id', (array)$columns, true)) {
                $where = ' WHERE competition_id=%d';
                $query_args[] = $competition_id;
            } elseif (in_array('season_id', (array)$columns, true)) {
                $season_ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d",$competition_id));
                if(!$season_ids)continue;
                $where=' WHERE season_id IN ('.implode(',',array_map('absint',$season_ids)).')';
            } elseif (in_array('season_category_id', (array)$columns, true)) {
                $season_ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d",$competition_id));
                if(!$season_ids)continue;
                $mapping_ids=$wpdb->get_col("SELECT id FROM {$wpdb->prefix}zmp_season_categories WHERE season_id IN (".implode(',',array_map('absint',$season_ids)).')');
                if(!$mapping_ids)continue;
                $where=' WHERE season_category_id IN ('.implode(',',array_map('absint',$mapping_ids)).')';
            }
            $tmp = wp_tempnam('zmp-export');
            if (!$tmp) { continue; }
            $temp_files[] = $tmp;
            $out = fopen($tmp, 'w');
            if (!$out) { continue; }
            $offset = 0; $batch = 500; $header_written = false;
            do {
                $sql = "SELECT * FROM `".esc_sql($table)."`$where LIMIT $batch OFFSET $offset";
                if ($query_args) { $sql = $wpdb->prepare($sql, $query_args); }
                $rows = $wpdb->get_results($sql, ARRAY_A);
                if (!$rows) { break; }
                foreach ($rows as $row) {
                    $row = self::archive_row_without_secrets($row);
                    if (!$header_written) { fputcsv($out, array_keys($row)); $header_written = true; }
                    fputcsv($out, ZMP_Csv_Safety::safe_row(array_values($row)));
                }
                $offset += $batch;
            } while (count($rows) === $batch);
            fclose($out);
            if ($header_written) { $zip->addFile($tmp, 'tables/'.substr($table, strlen($prefix)).'.csv'); }
            else { @unlink($tmp); }
        }
        $season_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d",$competition_id));
        if($season_id&&!ZMP_Category_Registrations::add_rosters_to_archive($zip,$season_id)){
            if($use_zip_archive){$zip->close();}else{unset($zip);}
            ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files();
            foreach($temp_files as $tmp_file){@unlink($tmp_file);}@unlink($file);
            wp_die('Еден или повеќе училишни списоци недостасуваат или не се совпаѓаат со проверочниот hash. Архивата не е финализирана.','',array('response'=>500));
        }
        if($season_id&&!ZMP_Registration_Requirements::add_documents_to_archive($zip,$season_id)){
            if($use_zip_archive){$zip->close();}else{unset($zip);}
            ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files();
            foreach($temp_files as $tmp_file){@unlink($tmp_file);}@unlink($file);
            wp_die('Годишниот формулар или прикачените пријави/потврди недостасуваат или не се совпаѓаат со проверочниот hash. Архивата не е финализирана.','',array('response'=>500));
        }
        // Include self-contained printable diplomas only for commission-confirmed results.
        if (!self::add_certificates_to_archive($zip, $season_id)) {
            if ($use_zip_archive) { $zip->close(); } else { unset($zip); }
            ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files();
            foreach ($temp_files as $tmp_file) { @unlink($tmp_file); }
            @unlink($file);
            wp_die('Не може да се додаде диплома во годишната архива.', '', array('response'=>500));
        }
        if ($season_id && class_exists('ZMP_Certificate_Templates') && !ZMP_Certificate_Templates::add_templates_to_archive($zip,$season_id)) {
            if ($use_zip_archive) { $zip->close(); } else { unset($zip); }
            ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files();
            foreach ($temp_files as $tmp_file) { @unlink($tmp_file); }
            @unlink($file);
            wp_die('Word шаблон за диплома недостасува во приватниот Drive или не може да се додаде во годишната архива.', '', array('response'=>500));
        }
        // Explicit complete phase result workbooks (CSV opens directly in Excel).
        $phase_table = $wpdb->prefix.'zmp_phase_results';
        foreach (self::$phases as $phase_key) {
            $tmp = wp_tempnam('zmp-phase-results');
            if (!$tmp) { continue; }
            $temp_files[] = $tmp;
            $out = fopen($tmp, 'w');
            if (!$out) { continue; }
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, array('Ранг','Име и презиме','Шифра','Одделение','Училиште','Регион','Општина','Натпреварувачко ниво','Бодови','Награда','Квалификуван','Завршено'));
            $rows = $wpdb->get_results($wpdb->prepare("SELECT student_name,access_code,grade,school_name,region,municipality,phase,total_points,award_level,qualified_next_stage,completed_at FROM $phase_table WHERE competition_id=%d AND phase=%s ORDER BY total_points DESC,completed_at ASC,attempt_id ASC", $competition_id, $phase_key), ARRAY_A);
            $rank = 1;
            foreach ($rows as $row) {
                $row['rank'] = $rank++;
                fputcsv($out, ZMP_Csv_Safety::safe_row(array($row['rank'],$row['student_name'],$row['access_code'],$row['grade'],$row['school_name'],ZMP_Regions::label($row['region']),$row['municipality'],$row['phase'],$row['total_points'],$row['award_level'],$row['qualified_next_stage']?'ДА':'НЕ',$row['completed_at'])));
            }
            fclose($out);
            $zip->addFile($tmp, 'results/'.$comp->academic_year.'-'.$phase_key.'.csv');
        }
        // Preserve human-readable attribution for moderator, commission, mentor,
        // testator and phase-finalization actions without exporting credentials.
        $actor_ids = array();
        $actor_queries = array(
            $wpdb->prepare("SELECT moderator_user_id FROM {$wpdb->prefix}zmp_moderator_actions WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT testator_user_id FROM {$wpdb->prefix}zmp_incidents WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT testator_user_id FROM {$wpdb->prefix}zmp_testator_duties WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT actor_user_id FROM {$wpdb->prefix}zmp_operations_audit WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT updated_by FROM {$wpdb->prefix}zmp_stage_events WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT assigned_by FROM {$wpdb->prefix}zmp_stage_testator_assignments WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT testator_user_id FROM {$wpdb->prefix}zmp_stage_testator_assignments WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT created_by FROM {$wpdb->prefix}zmp_stage_tasks WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT reviewed_by FROM {$wpdb->prefix}zmp_category_entries WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}zmp_category_entries WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT assigned_by FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT juror_user_id FROM {$wpdb->prefix}zmp_jury_assignments WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT created_by FROM {$wpdb->prefix}zmp_scoring_criteria WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT updated_by FROM {$wpdb->prefix}zmp_category_awards WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT juror_user_id FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT decided_by FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT actor_user_id FROM {$wpdb->prefix}zmp_jury_audit WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT juror_user_id FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT decided_by FROM {$wpdb->prefix}zmp_jury_correction_requests WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}zmp_region_change_requests WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT decided_by FROM {$wpdb->prefix}zmp_region_change_requests WHERE season_id IN (SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d)",$competition_id),
            $wpdb->prepare("SELECT created_by FROM {$wpdb->prefix}zmp_commission_decisions WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT reviewed_by FROM {$wpdb->prefix}zmp_commission_decisions WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT commission_user_id FROM {$wpdb->prefix}zmp_score_adjustments WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT closed_by FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d", $competition_id),
            $wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}zmp_mentors WHERE id IN (SELECT DISTINCT mentor_id FROM {$wpdb->prefix}zmp_students WHERE competition_id=%d)", $competition_id),
        );
        foreach ($actor_queries as $actor_query) {
            $actor_ids = array_merge($actor_ids, array_map('absint', (array)$wpdb->get_col($actor_query)));
        }
        if (!empty($comp->finalized_by)) { $actor_ids[] = absint($comp->finalized_by); }
        $actor_ids = array_values(array_unique(array_filter($actor_ids)));
        $actor_csv = fopen('php://temp', 'w+');
        fprintf($actor_csv, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($actor_csv, array('user_id','user_login','display_name','roles'));
        foreach ($actor_ids as $actor_id) {
            $actor = get_userdata($actor_id);
            if (!$actor) { fputcsv($actor_csv, ZMP_Csv_Safety::safe_row(array($actor_id,'[deleted]','[deleted]',''))); continue; }
            fputcsv($actor_csv, ZMP_Csv_Safety::safe_row(array($actor_id,$actor->user_login,$actor->display_name,implode('|',(array)$actor->roles))));
        }
        rewind($actor_csv);
        $zip->addFromString('actors.csv', stream_get_contents($actor_csv));
        fclose($actor_csv);
        if ($use_zip_archive) {
            if (!$zip->close()) { ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files(); foreach ($temp_files as $tmp_file) { @unlink($tmp_file); } @unlink($file); wp_die('ZIP архивата не е успешно финализирана.', '', array('response'=>500)); }
        } else {
            unset($zip);
            clearstatcache(true, $file);
            if (!is_file($file) || filesize($file) < 22) { ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files(); foreach ($temp_files as $tmp_file) { @unlink($tmp_file); } @unlink($file); wp_die('ZIP архивата не е успешно финализирана.', '', array('response'=>500)); }
        }
        ZMP_Category_Registrations::cleanup_archive_temp_files(); ZMP_Registration_Requirements::cleanup_archive_temp_files();
        foreach ($temp_files as $tmp_file) { @unlink($tmp_file); }
        @chmod($file, 0600);
        $archive_hash=hash_file('sha256',$file);
        $drive_id=ZMP_Google_Drive::upload_private_archive($file,filesize($file),$competition_id,$comp->academic_year);
        if(is_wp_error($drive_id)){@unlink($file);wp_die('Архивата не е зачувана во Drive. Хостингот не ја задржа копијата; повторете по проверка на Drive врската.','',array('response'=>503));}
        $ok = $wpdb->insert($wpdb->prefix.'zmp_competition_archives', array('competition_id'=>$competition_id,'created_by'=>get_current_user_id(),'file_path'=>'','drive_file_id'=>$drive_id,'storage_backend'=>'drive','sha256'=>$archive_hash));
        if (!$ok) { ZMP_Google_Drive::delete_private_file($drive_id); @unlink($file); wp_die('Архивата е креирана, но нејзиниот запис не успеа.', '', array('response'=>500)); }
        $this->stream_archive($file,true);
    }

    /** Remove credentials, bearer URLs, and private jury evidence from generic archives. */
    private static function archive_row_without_secrets($row) {
        foreach (array_keys((array)$row) as $column) {
            if (preg_match('/password|pin|token|secret|db_pass|upload_session/i', (string)$column) || $column === 'private_notes') { unset($row[$column]); }
        }
        return $row;
    }

    public function download_archive() {
        if (!$this->can_moderate()) { wp_die('Немате дозвола.', '', array('response'=>403)); }
        $id = absint($_GET['archive_id'] ?? 0);
        check_admin_referer('zmp_download_archive_'.$id);
        global $wpdb;
        $archive = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_competition_archives WHERE id=%d", $id));
        if (!$archive) { wp_die('Архивата не е пронајдена.', '', array('response'=>404)); }
        if (($archive->storage_backend??'local')==='drive'&&!empty($archive->drive_file_id)) {
            $tmp=ZMP_Google_Drive::download_private_file_to_temp($archive->drive_file_id);
            if(is_wp_error($tmp)||!hash_equals($archive->sha256,hash_file('sha256',$tmp))){if(!is_wp_error($tmp))@unlink($tmp);wp_die('Drive архивата не може да се провери.', '', array('response'=>404));}
            $this->stream_archive($tmp,true);
        }
        if (!$archive->file_path || !is_file($archive->file_path) || !hash_equals($archive->sha256, hash_file('sha256', $archive->file_path))) { wp_die('Архивата не постои или нејзиниот hash не се совпаѓа.', '', array('response'=>404)); }
        $this->stream_archive($archive->file_path);
    }

    public static function migrate_legacy_archives($limit=5) {
        global $wpdb; $limit=max(1,min(10,absint($limit))); $remaining=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competition_archives WHERE storage_backend='local' AND file_path<>''"); $dir=realpath(trailingslashit(dirname(untrailingslashit(ABSPATH))).'zmp-private-archives');
        if(!$dir)return array('moved'=>0,'failed'=>$remaining?1:0,'remaining'=>$remaining);
        $rows=$wpdb->get_results($wpdb->prepare("SELECT a.*,c.academic_year FROM {$wpdb->prefix}zmp_competition_archives a JOIN {$wpdb->prefix}zmp_competitions c ON c.id=a.competition_id WHERE a.storage_backend='local' AND a.file_path<>'' ORDER BY a.id LIMIT %d",$limit));
        $moved=0;$failed=0;
        foreach((array)$rows as $row){$path=realpath($row->file_path);if(!$path||dirname($path)!==$dir||!is_file($path)||!hash_equals((string)$row->sha256,hash_file('sha256',$path))){$failed++;continue;}
            $drive=ZMP_Google_Drive::upload_private_archive($path,(int)filesize($path),(int)$row->competition_id,$row->academic_year);if(is_wp_error($drive)){$failed++;continue;}
            $tmp=ZMP_Google_Drive::download_private_file_to_temp($drive);$verified=!is_wp_error($tmp)&&hash_equals((string)$row->sha256,hash_file('sha256',$tmp));if(is_string($tmp)&&is_file($tmp))@unlink($tmp);if(!$verified){ZMP_Google_Drive::delete_private_file($drive);$failed++;continue;}
            $saved=$wpdb->update($wpdb->prefix.'zmp_competition_archives',array('file_path'=>'','drive_file_id'=>$drive,'storage_backend'=>'drive'),array('id'=>(int)$row->id));if($saved===false){ZMP_Google_Drive::delete_private_file($drive);$failed++;continue;}if(!wp_delete_file($path)){$failed++;continue;}$moved++;
        }
        return array('moved'=>$moved,'failed'=>$failed,'remaining'=>max(0,$remaining-$moved));
    }

    /** Keep history and snapshots; clear only active-season student/testing operations after a verified archive. */
    public function reset_competition() {
        if (!$this->can_moderate()) { wp_die('Немате дозвола.', '', array('response'=>403)); }
        $competition_id = absint($_POST['competition_id'] ?? 0);
        check_admin_referer('zmp_reset_competition_'.$competition_id);
        if (empty($_POST['archive_verified'])) { wp_die('Потребно е да потврдите дека архивата е преземена и проверена.', '', array('response'=>400)); }
        $new_year = sanitize_text_field(wp_unslash($_POST['new_academic_year'] ?? ''));
        if (!preg_match('/^\d{4}\/\d{4}$/', $new_year) || (int)substr($new_year,5,4) !== (int)substr($new_year,0,4)+1) { wp_die('Внесете валидна учебна година, на пример 2027/2028.', '', array('response'=>400)); }
        global $wpdb;
        $comp = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_competitions WHERE id=%d AND workflow_status='completed'", $competition_id));
        $archive_id = absint($_POST['archive_id'] ?? 0);
        $archive = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_competition_archives WHERE id=%d AND competition_id=%d", $archive_id, $competition_id));
        if (!$comp || !$archive || !is_file($archive->file_path) || !hash_equals($archive->sha256, hash_file('sha256',$archive->file_path))) { wp_die('Потребна е валидна ZIP архива за завршената година.', '', array('response'=>409)); }
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_competitions WHERE academic_year=%s", $new_year))) { wp_die('Оваа учебна година веќе постои.', '', array('response'=>409)); }

        // Keep the completed season, immutable phase result snapshots, and archive ledger.
        // Detailed decisions and audit trails remain in the verified ZIP before operational rows are cleared.
        $wpdb->query('START TRANSACTION');
        $season_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE legacy_competition_id=%d",$competition_id));
        $roster_rows=$season_id?ZMP_Category_Registrations::roster_rows_for_season($season_id):array();
        $entry_document_rows=$season_id?ZMP_Registration_Requirements::document_rows_for_season($season_id):array();
        $document_revision_rows=$season_id?ZMP_Registration_Requirements::document_revision_rows_for_season($season_id):array();
        $student_document_rows=$season_id?ZMP_Registration_Requirements::student_document_rows_for_season($season_id):array();
        $cleanup_ok = true;
        foreach (array('zmp_internal_messages','zmp_incidents','zmp_complaints','zmp_commission_decisions','zmp_disqualifications','zmp_testator_duties','zmp_moderator_actions','zmp_score_adjustments','zmp_phase_status','zmp_attempts','zmp_students') as $suffix) {
            if ($wpdb->delete($wpdb->prefix.$suffix, array('competition_id'=>$competition_id)) === false) { $cleanup_ok = false; break; }
        }
        if($cleanup_ok&&$season_id){
            foreach(array('zmp_document_repair_tokens','zmp_entry_document_revisions','zmp_entry_document_reviews','zmp_entry_documents','zmp_student_documents','zmp_fee_requests','zmp_category_entries','zmp_stage_regional_events','zmp_stage_events','zmp_stage_testator_assignments','zmp_stage_tasks','zmp_operations_audit') as $suffix){if($wpdb->delete($wpdb->prefix.$suffix,array('season_id'=>$season_id))===false){$cleanup_ok=false;break;}}
        }
        if (!$cleanup_ok || $wpdb->query("UPDATE {$wpdb->prefix}zmp_calendar SET is_active=0") === false) { $wpdb->query('ROLLBACK'); wp_die('Податоците не се ресетирани бидејќи операцијата за чистење не успеа.', '', array('response'=>500)); }
        $wpdb->insert($wpdb->prefix.'zmp_competitions', array('title'=>'Државен натпревар по музичко образование '.$new_year,'academic_year'=>$new_year,'current_phase'=>'school','duration_minutes'=>45,'is_active'=>1,'workflow_status'=>'active'));
        $new_competition_id = (int)$wpdb->insert_id;
        if (!$new_competition_id) { $wpdb->query('ROLLBACK'); wp_die('Не може да се започне новата учебна година.', '', array('response'=>500)); }
        foreach (self::$phases as $phase) { if (!$wpdb->insert($wpdb->prefix.'zmp_phase_status', array('competition_id'=>$new_competition_id,'phase'=>$phase,'status'=>'open'))) { $wpdb->query('ROLLBACK'); wp_die('Новата година не е започната бидејќи натпреварувачки нивоате не можеа да се иницијализираат.', '', array('response'=>500)); } }
        $wpdb->query('COMMIT');
        ZMP_Category_Registrations::remove_roster_files((array)$roster_rows);
        ZMP_Registration_Requirements::remove_entry_document_files((array)$entry_document_rows);
        ZMP_Registration_Requirements::remove_document_revision_files((array)$document_revision_rows);
        ZMP_Registration_Requirements::remove_student_document_files((array)$student_document_rows);
        wp_safe_redirect(add_query_arg('zmp_year_started', $new_year, wp_get_referer() ?: admin_url()));
        exit;
    }

    /** Public results export is anonymized; the archive carries the full private audit data. */
    public function maybe_export_public_results() {
        if (empty($_GET['zmp_results_csv'])) { return; }
        if (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard')
            && !current_user_can('zmp_testator_cap') && !current_user_can('view_zmp_testator_dashboard')
            && !current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard')
            && !current_user_can('manage_options')) {
            wp_die('Немате дозвола за преземање резултати.', '', array('response'=>403));
        }
        global $wpdb;
        $year = sanitize_text_field(wp_unslash($_GET['zmp_result_year'] ?? ''));
        $phase = sanitize_key(wp_unslash($_GET['zmp_result_phase'] ?? ''));
        $region = sanitize_key(wp_unslash($_GET['zmp_result_region'] ?? ''));
        if (!ZMP_Regions::valid_region($region)) { $region = ''; }
        $municipality = sanitize_text_field(wp_unslash($_GET['zmp_result_municipality'] ?? ''));
        $school = sanitize_text_field(wp_unslash($_GET['zmp_result_school'] ?? ''));
        $grade = absint($_GET['grade'] ?? 0);
        $group_id = absint($_GET['zmp_result_group'] ?? 0);
        $education = sanitize_key(wp_unslash($_GET['zmp_result_education'] ?? ''));
        if (!in_array($education, array('', 'primary', 'secondary'), true)) { $education = ''; }
        $track = sanitize_key(wp_unslash($_GET['zmp_result_track'] ?? ''));
        if (!in_array($track, array('', 'gymnasium', 'vocational'), true)) { $track = ''; }
        $category_raw = sanitize_text_field(wp_unslash($_GET['zmp_result_category'] ?? '0'));
        $all_categories = $category_raw === 'all';
        $category_id = absint($category_raw);
        $mine = sanitize_key(wp_unslash($_GET['zmp_result_scope'] ?? '')) === 'mine'
            && (current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard'));
        if (!in_array($phase, self::$phases, true)) { wp_die('Изберете натпреварувачко ниво.', '', array('response'=>400)); }
        $category_ids = $all_categories
            ? array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT DISTINCT category_id FROM {$wpdb->prefix}zmp_announcements WHERE kind='results' AND status='published' AND academic_year=%s AND phase=%s",$year,$phase)))
            : array($category_id);
        if (!$category_ids) { wp_die('Резултатите за ова натпреварувачко ниво сè уште не се објавени.', '', array('response'=>404)); }
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) {
            $season_id = absint($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE academic_year=%s", $year)));
            if (!$season_id || (!$all_categories && $category_id === 0)) { wp_die('Немате доделба за резултатите од избраната категорија и година.', '', array('response'=>403)); }
            if ($all_categories) {
                $category_ids = array_values(array_filter(array_map('intval', $category_ids), static function ($assigned_category_id) use ($season_id) {
                    return ZMP_Category_Jury::has_any_assignment($season_id, $assigned_category_id, get_current_user_id());
                }));
            } elseif (!ZMP_Category_Jury::has_any_assignment($season_id, $category_id, get_current_user_id())) {
                wp_die('Немате доделба за резултатите од избраната категорија и година.', '', array('response'=>403));
            }
            if (!$category_ids) { wp_die('Нема објавени резултати во вашите доделени категории.', '', array('response'=>404)); }
        }
        $rows = array();
        foreach ($category_ids as $scope_category_id) {
            if (!ZMP_Communications::is_result_published($year,$scope_category_id,$phase)) { continue; }
            if ($scope_category_id > 0) {
                $query = "SELECT e.id AS _entry_id,e.participant_name AS participant,e.school_name,c.name_mk AS category,g.name_mk AS group_name,g.education_level,g.school_track,e.grade,e.region,e.municipality,f.phase,f.final_points AS points,f.award_key AS award,f.decided_at AS completed_at FROM {$wpdb->prefix}zmp_category_final_results f JOIN {$wpdb->prefix}zmp_seasons s ON s.id=f.season_id JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=f.entry_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=f.category_id LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE s.academic_year=%s AND f.category_id=%d AND f.phase=%s AND f.status='confirmed'";
                $args = array($year,$scope_category_id,$phase); if ($mine) { $query .= ' AND e.wp_user_id=%d'; $args[] = get_current_user_id(); }
                if ($grade) { $query .= ' AND e.grade=%d'; $args[] = $grade; }
                if ($group_id) { $query .= ' AND e.group_id=%d'; $args[] = $group_id; }
                if ($education !== '') { $query .= ' AND g.education_level=%s'; $args[] = $education; }
                if ($track !== '') { $query .= ' AND g.school_track=%s'; $args[] = $track; }
            } else {
                // Legacy test results have no entry/phase assignment key; do not expose them
                // through the category jury export path, which must remain phase-scoped.
                if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { continue; }
                $legacy_competition_id=ZMP_Communications::closed_legacy_competition_id($year,$phase);
                if(!$legacy_competition_id)continue;
                $query = "SELECT access_code AS participant,school_name,'' AS category,'' AS group_name,CASE WHEN grade BETWEEN 6 AND 9 THEN 'primary' WHEN grade BETWEEN 1 AND 2 THEN 'secondary' ELSE '' END AS education_level,'' AS school_track,grade,region,municipality,phase,total_points AS points,award_level AS award,completed_at FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s AND phase=%s";
                $args = array($legacy_competition_id,$year,$phase); if ($mine) { $query .= ' AND mentor_user_id=%d'; $args[] = get_current_user_id(); }
                if ($grade) { $query .= ' AND grade=%d'; $args[] = $grade; }
                if ($education === 'primary') { $query .= ' AND grade BETWEEN 6 AND 9'; }
                elseif ($education === 'secondary') { $query .= ' AND grade BETWEEN 1 AND 2'; }
                if ($group_id || $track !== '') { $query .= ' AND 1=0'; }
            }
            $alias = $scope_category_id > 0 ? 'e' : '';
            if ($region !== '') { $query .= ' AND ' . ($alias ? 'e.' : '') . 'region=%s'; $args[] = $region; }
            if ($municipality !== '') { $query .= ' AND ' . ($alias ? 'e.' : '') . 'municipality=%s'; $args[] = $municipality; }
            if ($school !== '') { $query .= ' AND ' . ($alias ? 'e.' : '') . 'school_name LIKE %s'; $args[] = '%' . $wpdb->esc_like($school) . '%'; }
            $category_rows = (array) $wpdb->get_results($wpdb->prepare($query.' ORDER BY points DESC,completed_at ASC',$args),ARRAY_A);
            if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) {
                $category_rows = array_values(array_filter($category_rows, static function ($row) use ($season_id, $scope_category_id, $phase) {
                    return ZMP_Category_Jury::can_access_entry_phase(
                        $season_id,
                        $scope_category_id,
                        absint($row['_entry_id'] ?? 0),
                        $phase
                    );
                }));
            }
            foreach ($category_rows as &$category_row) { unset($category_row['_entry_id']); }
            unset($category_row);
            $rows = array_merge($rows,$category_rows);
        }
        if (!$rows) { wp_die('Нема објавени резултати за избраниот опсег.', '', array('response'=>404)); }
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.sanitize_file_name('ZMP-'.$year.'-'.$phase.'.csv').'"');
        $out = fopen('php://output','w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, array('Ученик/ансамбл','Училиште','Категорија','Група','Образование','Тип средно','Одделение/година','Регион','Општина','Натпреварувачко ниво','Бодови','Награда','Завршено'));
        foreach ($rows as $row) { fputcsv($out, ZMP_Csv_Safety::safe_row($row)); }
        fclose($out); exit;
    }
}
