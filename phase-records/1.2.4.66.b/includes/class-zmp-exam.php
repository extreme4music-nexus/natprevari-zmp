<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Exam {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Start a PHP session only for the knowledge-test flow; read-only callers close it immediately. */
    private static $opened_session = false;

    private function ensure_student_session($write = false) {
        if (session_status() === PHP_SESSION_ACTIVE) { return true; }
        if (headers_sent() || !session_start()) { return false; }
        self::$opened_session = true;
        if (!$write) { $this->close_student_session(); }
        return true;
    }

    private function close_student_session() {
        if (self::$opened_session && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            self::$opened_session = false;
        }
    }
    private function __construct() {
        add_shortcode('zmp_student_exam', array($this, 'render_exam_portal'));
        add_shortcode('zmp_public_results', array($this, 'render_public_results'));
        add_action('template_redirect', array($this, 'maybe_render_results_pdf'));

        add_action('wp_ajax_zmp_save_answer', array($this, 'ajax_save_answer'));
        add_action('wp_ajax_nopriv_zmp_save_answer', array($this, 'ajax_save_answer'));

        add_action('wp_ajax_zmp_submit_exam', array($this, 'ajax_submit_exam'));
        add_action('wp_ajax_nopriv_zmp_submit_exam', array($this, 'ajax_submit_exam'));

        add_action('wp_ajax_zmp_heartbeat', array($this, 'ajax_heartbeat'));
        add_action('wp_ajax_nopriv_zmp_heartbeat', array($this, 'ajax_heartbeat'));

        add_action('init', array($this, 'handle_student_auth'));
        add_action('init', array($this, 'handle_student_logout'));
    }

    /** Draw exactly twenty distinct questions using the commissioned points mix. */
    private function draw_question_ids($competition_id, $grade, $phase) {
        global $wpdb;
        $mix = get_option('zmp_question_mix_' . absint($competition_id) . '_' . sanitize_key($phase) . '_' . absint($grade), array(2=>5,5=>5,15=>5,20=>5));
        if (!is_array($mix) || empty($mix)) { return new WP_Error('invalid_mix', 'Комисијата мора да зачува распределба од точно 20 прашања.'); }
        $normalized = array(); $total = 0;
        foreach ($mix as $points=>$quantity) {
            $points = absint($points); $quantity = absint($quantity);
            if ($points < 1 || $points > 20 || $quantity < 0 || $quantity > 20) { return new WP_Error('invalid_mix', 'Распределбата содржи неважечки бодови или број прашања.'); }
            if ($quantity > 0) { $normalized[$points] = $quantity; $total += $quantity; }
        }
        if ($total !== 20 || count($normalized) < 2) { return new WP_Error('invalid_mix', 'Комисијата мора да зачува распределба од точно 20 прашања од најмалку две бодовни нивоа.'); }
        $ids = array(); $table = $wpdb->prefix . 'zmp_questions';
        foreach ($normalized as $points=>$quantity) {
            $pool = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table WHERE grade=%d AND points=%d AND (phase=%s OR phase='all')", absint($grade), $points, sanitize_key($phase)));
            if (count($pool) < $quantity) { return new WP_Error('short_pool', 'За распределбата недостигаат прашања со ' . absint($points) . ' бодови. Дополнете ја базата или променете ја распределбата.'); }
            shuffle($pool); $ids = array_merge($ids, array_slice(array_map('absint', $pool), 0, $quantity));
        }
        if (count($ids) !== 20 || count(array_unique($ids)) !== 20) { return new WP_Error('invalid_draw', 'Не може да се состави валиден сет од 20 различни прашања.'); }
        shuffle($ids);
        return $ids;
    }

    private function student_owns_attempt($attempt_id) {
        $this->ensure_student_session(false);
        if (empty($_SESSION['zmp_student_id'])) {
            return false;
        }
        global $wpdb;
        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT id,student_id,phase FROM $table_attempts WHERE id = %d AND student_id = %d AND status = 'in_progress'",
            absint($attempt_id), absint($_SESSION['zmp_student_id'])
        ));
        if (!$attempt) { return false; }
        if ((string)$attempt->phase === 'regional') {
            $student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}zmp_students WHERE id=%d",
                (int)$attempt->student_id
            ));
            if (!$this->has_regional_knowledge_fee_proof($student)) { return false; }
        }
        return true;
    }

    /**
     * AJAX Heartbeat - Синхронизација на преостанатото активно време на секои 5-10 секунди
     */
    public function ajax_heartbeat() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');
        global $wpdb;
        $table_attempts = $wpdb->prefix . 'zmp_attempts';

        $attempt_id = intval($_POST['attempt_id']);
        $remaining  = max(0, intval($_POST['remaining_seconds']));
        $spent     = max(0, intval($_POST['time_spent_seconds']));

        if (!$this->student_owns_attempt($attempt_id)) {
            wp_send_json_error(array('message' => 'Невалиден обид.'));
        }
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_attempts WHERE id = %d", $attempt_id));
        if ($attempt && $attempt->status === 'in_progress' && !empty($attempt->is_paused)) {
            wp_send_json_success(array('paused'=>true, 'remaining_seconds'=>(int)$attempt->remaining_seconds));
        }
        if ($attempt && $attempt->status === 'in_progress') {
            $updated = $wpdb->update($table_attempts, array(
                'remaining_seconds'  => $remaining,
                'time_spent_seconds' => $spent,
                'last_active_at'     => current_time('mysql'),
                'is_paused'          => 0
            ), array('id' => $attempt_id));
            if ($updated === false) {
                wp_send_json_error(array('message' => 'Не може да се зачува времето на обидот.'), 500);
            }

            if ($remaining <= 0) {
                $this->finalize_attempt($attempt_id);
                wp_send_json_success(array('time_up' => true));
            }
            wp_send_json_success(array('paused'=>false, 'remaining_seconds'=>$remaining));
        }

        wp_send_json_success(array('paused'=>false, 'remaining_seconds'=>$attempt ? (int)$attempt->remaining_seconds : $remaining));
    }

    /**
     * Одјава на ученикот (автоматски го паузира времето)
     */
    public function handle_student_logout() {
        if (isset($_GET['zmp_student_logout'])) {
            check_admin_referer('zmp_student_logout');
            $this->ensure_student_session(true);
            unset($_SESSION['zmp_student_id']);
            unset($_SESSION['zmp_student_lang']);
            $this->close_student_session();
            wp_redirect(remove_query_arg(array('zmp_student_logout')));
            exit;
        }
    }

    /**
     * Најава на ученикот со кодот и пинот од пликото
     */
    /** Regional knowledge-test access requires a finalized municipal qualification and, when due, proof for this season. */
    private function has_regional_knowledge_fee_proof($student) {
        if (!$student || empty($student->id) || empty($student->competition_id)) { return false; }
        if (!class_exists('ZMP_Registration_Requirements') || !class_exists('ZMP_Fee_Invoices')) { return false; }
        global $wpdb;
        $academic_year = (string)$wpdb->get_var($wpdb->prepare(
            "SELECT academic_year FROM {$wpdb->prefix}zmp_competitions WHERE id=%d",
            (int)$student->competition_id
        ));
        if ($academic_year === '') { return false; }
        $season_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE academic_year=%s AND legacy_competition_id=%d ORDER BY id DESC LIMIT 1",
            $academic_year,
            (int)$student->competition_id
        ));
        $category_id = (int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' AND is_active=1 ORDER BY id DESC LIMIT 1");
        if (!$season_id || !$category_id || !ZMP_Fee_Invoices::knowledge_student_qualified($season_id,(int)$student->id)) { return false; }
        if (!ZMP_Registration_Requirements::fee_proof_required($season_id,$category_id,(int)$student->id)) { return true; }
        return (bool)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}zmp_student_documents WHERE student_id=%d AND season_id=%d AND document_type='payment_proof' LIMIT 1",
            (int)$student->id,
            $season_id
        ));
    }
    public function handle_student_auth() {
        if (!isset($_POST['zmp_student_login'])) {
            return;
        }

        check_admin_referer('zmp_student_login_nonce');
        global $wpdb;
        $table_students     = $wpdb->prefix . 'zmp_students';
        $table_competitions = $wpdb->prefix . 'zmp_competitions';
        $table_calendar     = $wpdb->prefix . 'zmp_calendar';

        $code = sanitize_text_field(trim($_POST['access_code']));
        $pin  = sanitize_text_field(trim($_POST['access_pin']));
        $lang = sanitize_text_field($_POST['exam_language']);

        $comp = $wpdb->get_row("SELECT * FROM $table_competitions WHERE is_active = 1 LIMIT 1");
        if (!$comp) {
            wp_redirect(add_query_arg('login_err', 'competition_inactive'));
            exit;
        }

        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_students WHERE access_code = %s AND competition_id = %d",
            $code, $comp->id
        ));

        if (!$student || !ZMP_Credentials::verify_and_upgrade($student, $pin)) {
            wp_redirect(add_query_arg('login_err', 'invalid_credentials'));
            exit;
        }

        // Котизацијата за Тест на знаење се бара само по потврдена квалификација за регионално ниво.
        $phase = $student->current_phase ? $student->current_phase : $comp->current_phase;
        if ($phase === 'regional' && !$this->has_regional_knowledge_fee_proof($student)) {
            wp_redirect(add_query_arg('login_err', 'fee_payment_required'));
            exit;
        }
        // Проверка на Календарот за одржување на натпреварот и временскиот прозорец
        $phase_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}zmp_phase_status WHERE competition_id=%d AND phase=%s", $comp->id, $phase));
        if ($phase_status === 'closed') { wp_die(esc_html__('Ова натпреварувачко ниво е официјално затворено од Испитната комисија.', 'zmp-competitions')); }
        $cal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_calendar WHERE phase = %s AND is_active = 1 LIMIT 1", $phase));

        if (!$cal) {
            wp_redirect(add_query_arg('login_err', 'schedule_unavailable'));
            exit;
        }

        if ($cal) {
            $now = current_time('timestamp');
            $start = strtotime($cal->start_datetime);
            $end   = strtotime($cal->end_datetime);

            if ($now < $start) {
                wp_redirect(add_query_arg(array('login_err' => 'window_not_started', 'start_time' => urlencode($cal->start_datetime))));
                exit;
            }

            if ($now > $end) {
                wp_redirect(add_query_arg('login_err', 'window_expired'));
                exit;
            }
        }

        if (!$this->ensure_student_session(true)) { wp_die('Не може да се започне безбедна испитна сесија.', '', array('response'=>500)); }
        session_regenerate_id(true);
        $_SESSION['zmp_student_id'] = $student->id;
        $_SESSION['zmp_student_lang'] = in_array($lang, array('mk', 'sq')) ? $lang : $student->language;
        $this->close_student_session();
        wp_redirect(remove_query_arg(array('login_err', 'start_time')));
        exit;
    }

    /**
     * Рендерирање на испитниот портал за ученици [zmp_student_exam]
     */
    public function render_exam_portal($atts) {
        $this->ensure_student_session(false);
        global $wpdb;
        $table_competitions = $wpdb->prefix . 'zmp_competitions';
        $table_students     = $wpdb->prefix . 'zmp_students';
        $table_questions    = $wpdb->prefix . 'zmp_questions';
        $table_attempts     = $wpdb->prefix . 'zmp_attempts';
        $table_calendar     = $wpdb->prefix . 'zmp_calendar';

        $comp = $wpdb->get_row("SELECT * FROM $table_competitions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $phase = $comp ? $comp->current_phase : 'school';
        $duration = $comp ? intval($comp->duration_minutes) : 45;

        // Доколку има календар со специфицирано времетраење
        $cal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_calendar WHERE phase = %s AND is_active = 1 LIMIT 1", $phase));
        if ($cal && !empty($cal->duration_minutes)) {
            $duration = intval($cal->duration_minutes);
        }

        $student_id = isset($_SESSION['zmp_student_id']) ? intval($_SESSION['zmp_student_id']) : 0;
        $student = null;
        if ($student_id) {
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE id = %d", $student_id));
        }

        // Ако не е најавен - Формулар за најава со плико код
        if (!$student) {
            return $this->render_login_form();
        }

        $phase = $student->current_phase ? $student->current_phase : ($comp ? $comp->current_phase : 'school');
        if ($phase === 'regional' && !$this->has_regional_knowledge_fee_proof($student)) {
            return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-warning"><h3>Потребна е потврда за котизацијата</h3><p>Регионалното тестирање се отклучува по потврдена квалификација од општинското ниво и прикачување потврда за уплата. Контактирајте го наставникот за да ја достави.</p><a class="zmp-btn zmp-btn-secondary" href="' . esc_url(wp_nonce_url(add_query_arg('zmp_student_logout', '1'), 'zmp_student_logout')) . '">Одјави се</a></div></div>';
        }

        $lang = isset($_SESSION['zmp_student_lang']) ? $_SESSION['zmp_student_lang'] : $student->language;

        // Преводи на интерфејсот
        $ui = array(
            'mk' => array(
                'title' => 'Тестирање по Музичко образование',
                'phase' => 'Тековно натпреварувачко ниво на натпреварот',
                'student' => 'Ученик',
                'code' => 'Шифра',
                'grade' => 'одделение',
                'time_left' => 'Преостанато активно време',
                'audio_play' => 'Преслушај музички пример',
                'audio_plays_left' => 'Преостанати преслушувања',
                'submit_btn' => 'Заврши тестирање',
                'confirm_submit' => 'Дали сте сигурни дека сакате да го завршите тестирањето? По кликнување, поените автоматски ќе се пресметаат.',
                'results_title' => 'Резултати од тестирањето',
                'score' => 'Освоени поени',
                'award' => 'Освоена награда / диплома',
                'logout' => 'Одјави се'
            ),
            'sq' => array(
                'title' => 'Testimi i Edukimit Muzikor',
                'phase' => 'Faza aktuale e garës',
                'student' => 'Nxënësi',
                'code' => 'Kodi',
                'grade' => 'klasa',
                'time_left' => 'Koha aktive e mbetur',
                'audio_play' => 'Dëgjo shembullin muzikor',
                'audio_plays_left' => 'Dëgjime të mbetura',
                'submit_btn' => 'Përfundo testimin',
                'confirm_submit' => 'A jeni të sigurt që dëshironi të përfundoni testimin? Pikët do të llogariten automatikisht.',
                'results_title' => 'Rezultatet e testimit',
                'score' => 'Pikët e fituara',
                'award' => 'Çmimi / diploma e fituar',
                'logout' => 'Dilni'
            )
        );
        $t = $ui[$lang];

        // Провери дали ученикот веќе има завршен обид за тековното натпреварувачко ниво
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_attempts WHERE student_id = %d AND phase = %s",
            $student->id, $phase
        ));

        if ($attempt && $attempt->status === 'disqualified') {
            return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-danger">Ученикот е дисквалификуван и не може да продолжи со тестирањето.</div></div>';
        }

        if ($attempt && $attempt->status === 'stopped') {
            return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-warning">Тестот е запрен од модератор. Контактирајте го тестаторот или модераторот.</div></div>';
        }

        if ($attempt && $attempt->status === 'completed') {
            $award = ZMP_Scoring::get_award_details($attempt->total_points, $lang);
            ob_start();
            ?>
            <div class="zmp-container">
                <div class="zmp-card zmp-text-center">
                    <h2>🏆 <?php echo esc_html($t['results_title']); ?></h2>
                    <p><strong><?php echo esc_html($t['student']); ?>:</strong> <?php echo esc_html($student->first_name . ' ' . $student->last_name); ?> (<code><?php echo esc_html($student->access_code); ?></code>)</p>
                    
                    <div class="zmp-score-circle">
                        <div class="zmp-score-number"><?php echo number_format($attempt->total_points, 1); ?></div>
                        <div class="zmp-score-label">од максимум 100</div>
                    </div>

                    <div class="zmp-award-banner zmp-award-<?php echo esc_attr($award['badge']); ?>">
                        <h3><?php echo esc_html($award['label']); ?></h3>
                    </div>

                    <p style="margin-top: 25px; color: #555;">
                        Официјалните ранг-листи и информациите за пласман во следното натпреварувачко ниво ќе бидат објавени по завршување на терминот за тестирање.
                    </p>

                    <div style="margin-top: 20px;">
                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('zmp_student_logout', '1'), 'zmp_student_logout')); ?>" class="zmp-btn zmp-btn-secondary">
                            <?php echo esc_html($t['logout']); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // АКТИВНО ВРЕМЕ И СТОПИРАЊЕ / ПАУЗИРАЊЕ ПРИ ПРЕКИН НА ВРСКА:
        // Ако нема обид, креирај нов обид со почетно преостанато време (remaining_seconds = duration * 60)
        if (!$attempt) {
            $now = current_time('mysql');
            $initial_seconds = $duration * 60;

            $selected_ids = $this->draw_question_ids($student->competition_id, $student->grade, $phase);
            if (is_wp_error($selected_ids)) { return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-warning">Тестот не може да започне: ' . esc_html($selected_ids->get_error_message()) . '</div></div>'; }

            $inserted = $wpdb->insert($table_attempts, array(
                'student_id'         => $student->id,
                'competition_id'     => absint($student->competition_id),
                'phase'              => $phase,
                'grade'              => $student->grade,
                'started_at'         => $now,
                'remaining_seconds'  => $initial_seconds,
                'time_spent_seconds' => 0,
                'last_active_at'     => $now,
                'is_paused'          => 0,
                'status'             => 'in_progress',
                'answers_json'       => json_encode(array()),
                'question_ids_json'  => wp_json_encode($selected_ids)
            ));
            if (!$inserted) { return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-danger">Не може да се започне обидот. Контактирајте ја администрацијата.</div></div>'; }
            $attempt_id = $wpdb->insert_id;
            $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_attempts WHERE id=%d", $attempt_id));
            $remaining_seconds = $initial_seconds;
            $time_spent_seconds = 0;
            $saved_answers = array();
        } else {
            // Обидот веќе постои - ПРОДОЛЖИ ОД ТАМУ КАДЕ ШТО ЗАСТАНАЛ ВО ТОЈ МОМЕНТ!
            $attempt_id = $attempt->id;
            $remaining_seconds = max(0, intval($attempt->remaining_seconds));
            $time_spent_seconds = intval($attempt->time_spent_seconds);
            $saved_answers = !empty($attempt->answers_json) ? json_decode($attempt->answers_json, true) : array();
            $selected_ids = !empty($attempt->question_ids_json) ? array_values(array_filter(array_map('absint', (array)json_decode($attempt->question_ids_json, true)))) : array();
            if (count($selected_ids) !== 20) {
                $selected_ids = $this->draw_question_ids($attempt->competition_id, $attempt->grade, $attempt->phase);
                if (is_wp_error($selected_ids)) { return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-danger">Постојниот обид не може да добие сет од 20 прашања: ' . esc_html($selected_ids->get_error_message()) . '</div></div>'; }
                $updated = $wpdb->update($table_attempts, array('question_ids_json'=>wp_json_encode($selected_ids)), array('id'=>$attempt_id));
                if ($updated === false) { return '<div class="zmp-container"><div class="zmp-card zmp-alert zmp-alert-danger">Не може да се зачува сетот од 20 прашања за постојниот обид.</div></div>'; }
            }

            // Означи дека ученикот повторно е активен
            if (empty($attempt->is_paused)) {
                $wpdb->update($table_attempts, array('last_active_at' => current_time('mysql')), array('id' => $attempt_id));
            }
        }

        // Ако активното време истекло, автоматски заврши го
        if ($remaining_seconds <= 0) {
            $this->finalize_attempt($attempt_id);
            wp_redirect(remove_query_arg(array()));
            exit;
        }

        // Вчитување прашања за одделението на ученикот
        $questions = $wpdb->get_results("SELECT * FROM $table_questions WHERE id IN (" . implode(',', array_map('absint', $selected_ids)) . ") ORDER BY FIELD(id," . implode(',', array_map('absint', $selected_ids)) . ")");

        ob_start();
        ?>
        <div class="zmp-container zmp-exam-room">
            <!-- Лента со податоци и Тајмер -->
            <div class="zmp-exam-header">
                <div>
                    <h4>🎼 <?php echo esc_html($t['title']); ?> - <?php echo esc_html($student->grade); ?>. <?php echo esc_html($t['grade']); ?></h4>
                    <p style="margin: 0; font-size: 13px;">
                        <strong><?php echo esc_html($t['student']); ?>:</strong> <?php echo esc_html($student->first_name . ' ' . $student->last_name); ?> 
                        (<?php echo esc_html($t['code']); ?>: <code><?php echo esc_html($student->access_code); ?></code>)
                    </p>
                </div>
                <div class="zmp-timer-box">
                    <span class="zmp-timer-label"><?php echo esc_html($t['time_left']); ?>:</span>
                    <span id="zmp-countdown" data-seconds="<?php echo esc_attr($remaining_seconds); ?>" data-spent="<?php echo esc_attr((int)$attempt->time_spent_seconds); ?>" data-paused="<?php echo !empty($attempt->is_paused) ? '1' : '0'; ?>" class="zmp-timer-digits">--:--</span>
                </div>
            </div>

            <form id="zmp-exam-form" method="POST">
                <input type="hidden" name="attempt_id" value="<?php echo esc_attr($attempt_id); ?>">

                <?php if (empty($questions)): ?>
                    <div class="zmp-alert zmp-alert-warning">
                        Во моментов нема активни прашања за ова одделение. Ве молиме контактирајте ја комисијата.
                    </div>
                <?php else: ?>
                    <?php 
                    $q_num = 1;
                    foreach ($questions as $q): 
                        $options = json_decode($q->options_json, true);
                        $q_text = ($lang === 'sq' && !empty($q->question_text_sq)) ? $q->question_text_sq : $q->question_text_mk;
                        $selected_ans = isset($saved_answers[$q->id]) ? intval($saved_answers[$q->id]) : -1;
                    ?>
                        <div class="zmp-question-card" id="q-box-<?php echo esc_attr($q->id); ?>">
                            <div class="zmp-q-num">Прашање / Pyetja <?php echo $q_num; ?> (<?php echo esc_html($q->points); ?> бодови)</div>
                            <div class="zmp-q-text"><?php echo nl2br(esc_html($q_text)); ?></div>

                            <!-- Слика на ноти/партитура ако постои -->
                            <?php if (!empty($q->image_url)): ?>
                                <div class="zmp-q-image">
                                    <img src="<?php echo esc_url($q->image_url); ?>" alt="Ноти">
                                </div>
                            <?php endif; ?>

                            <!-- Аудио пример за слушање ако постои -->
                            <?php if (!empty($q->audio_url)): ?>
                                <div class="zmp-audio-player-box">
                                    <label>🎧 <?php echo esc_html($t['audio_play']); ?>:</label>
                                    <audio controls preload="none">
                                        <source src="<?php echo esc_url($q->audio_url); ?>" type="audio/mpeg">
                                        Вашиот прелистувач не поддржува аудио.
                                    </audio>
                                </div>
                            <?php endif; ?>

                            <!-- Понудени одговори -->
                            <div class="zmp-options-list">
                                <?php if (!empty($options) && is_array($options)): ?>
                                    <?php foreach ($options as $idx => $opt): 
                                        $opt_text = ($lang === 'sq' && !empty($opt['text_sq'])) ? $opt['text_sq'] : $opt['text_mk'];
                                    ?>
                                        <label class="zmp-option-item <?php echo ($selected_ans === $idx) ? 'zmp-selected' : ''; ?>">
                                            <input type="radio" 
                                                   name="question_<?php echo esc_attr($q->id); ?>" 
                                                   value="<?php echo esc_attr($idx); ?>"
                                                   data-qid="<?php echo esc_attr($q->id); ?>"
                                                   <?php checked($selected_ans, $idx); disabled(!empty($attempt->is_paused)); ?>
                                                   class="zmp-answer-radio">
                                            <span><?php echo esc_html($opt_text); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        $q_num++;
                    endforeach; 
                    ?>

                    <div style="text-align: center; margin: 30px 0;">
                        <button type="button" id="zmp-btn-submit" class="zmp-btn zmp-btn-primary zmp-btn-large" <?php disabled(!empty($attempt->is_paused)); ?>>
                            ✅ <?php echo esc_html($t['submit_btn']); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Формулар за најава на ученикот
     */
    private function render_login_form() {
        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-card zmp-login-card">
                <div class="zmp-header-box">
                    <div style="font-size: 32px; margin-bottom: 8px;">🎵</div>
                    <h2>Акредитирано здружение на музички педагози</h2>
                    <p>Натпревар по музичко образование | Garë e edukimit muzikor (https://muzika.org.mk)</p>
                </div>

                <?php if (isset($_GET['login_err'])): ?>
                    <?php if ($_GET['login_err'] === 'window_not_started'): ?>
                        <div class="zmp-alert zmp-alert-warning">
                            ⏳ <strong>Тестирањето сè уште не е започнато!</strong><br>
                            Според официјалниот календар, терминот за овој натпревар започнува на:<br>
                            <strong style="color: #1e3a8a; font-size: 16px;"><?php echo isset($_GET['start_time']) ? esc_html(urldecode($_GET['start_time'])) : ''; ?></strong><br>
                            <small>Пристапот за логирање ќе се отвори автоматски во закажаниот час.</small>
                        </div>
                    <?php elseif ($_GET['login_err'] === 'window_expired'): ?>
                        <div class="zmp-alert zmp-alert-danger">
                            ⛔ <strong>Временскиот прозорец за ова тестирање е истечен!</strong><br>
                            Рокот за влегување во тестот според официјалниот календар е завршен.<br>
                            <small>Afati kohor për këtë testim ka skaduar.</small>
                        </div>
                    <?php elseif ($_GET['login_err'] === 'competition_inactive'): ?>
                        <div class="zmp-alert zmp-alert-warning">
                            ⚠️ Тестирањето во моментов не е активно. Ве молиме почекајте терминот за натпреварот да биде отворен од комисијата.<br>
                            <small>Testimi aktualisht nuk është aktiv. Ju lutemi prisni hapjen e garës nga komisioni.</small>
                        </div>
                    <?php elseif (sanitize_key(wp_unslash($_GET['login_err'])) === 'fee_payment_required'): ?>
                        <div class="zmp-alert zmp-alert-warning">Регионалното тестирање се отклучува по потврдена квалификација од општинското ниво и доставена потврда за котизација. Обратете се кај наставникот.</div>
                    <?php else: ?>
                        <div class="zmp-alert zmp-alert-danger">
                            ❌ Погрешна шифра или лозинка од пликото. Ве молиме обидете се повторно.<br>
                            <small>Kodi ose fjalëkalimi i pasaktë nga zarfi. Ju lutemi provoni përsëri.</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="zmp-alert zmp-alert-warning" style="font-size: 13px; margin-bottom: 20px;">
                    ℹ️ <strong>Напомена за учениците:</strong> Вашето корисничко име (шифра) и лозинка (ПИН) се наоѓаат на картичката во запечатеното плико кое ви го врачи вашиот наставник по музичко образование.
                </div>

                <form method="POST" class="zmp-form">
                    <?php wp_nonce_field('zmp_student_login_nonce'); ?>
                    <input type="hidden" name="zmp_student_login" value="1">

                    <div class="zmp-form-group">
                        <label>Корисничко име / Шифра од пликото (Kodi nga zarfi):</label>
                        <input type="text" name="access_code" required placeholder="пр. ZMP-VI-8492" autocomplete="off" style="font-family: monospace; font-size: 16px; font-weight: bold; letter-spacing: 1px;">
                    </div>

                    <div class="zmp-form-group">
                        <label>Лозинка / ПИН од пликото (Fjalëkalimi nga zarfi):</label>
                        <input type="password" name="access_pin" required placeholder="пр. Muzika-582" autocomplete="off" style="font-size: 16px; font-weight: bold;">
                    </div>

                    <div class="zmp-form-group">
                        <label>Изберете наставен јазик за тестот (Zgjidhni gjuhën e testit):</label>
                        <select name="exam_language" style="font-size: 15px; font-weight: 600;">
                            <option value="mk">Македонски јазик</option>
                            <option value="sq">Gjuha shqipe (Албански)</option>
                        </select>
                    </div>

                    <button type="submit" class="zmp-btn zmp-btn-primary zmp-btn-large" style="width: 100%; margin-top: 10px;">
                        Влези во тестот (Hyni në test) &rarr;
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX автоматско зачувување на одговорот во реално време
     */
    public function ajax_save_answer() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');

        global $wpdb;
        $table_attempts = $wpdb->prefix . 'zmp_attempts';

        $attempt_id  = intval($_POST['attempt_id']);
        $question_id = intval($_POST['question_id']);
        $answer_idx  = intval($_POST['answer_index']);

        if (!$this->student_owns_attempt($attempt_id)) {
            wp_send_json_error(array('message' => 'Невалиден обид.'));
        }
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_attempts WHERE id = %d", $attempt_id));
        if (!$attempt || $attempt->status !== 'in_progress' || !empty($attempt->is_paused)) {
            wp_send_json_error(array('message' => 'Обидот не е активен или е паузиран од модератор.'));
        }

        $answers = !empty($attempt->answers_json) ? json_decode($attempt->answers_json, true) : array();
        $answers[$question_id] = $answer_idx;

        $wpdb->update(
            $table_attempts,
            array('answers_json' => json_encode($answers)),
            array('id' => $attempt_id)
        );

        wp_send_json_success(array('saved' => true));
    }

    /**
     * AJAX завршување и пресметка на резултати
     */
    public function ajax_submit_exam() {
        check_ajax_referer('zmp_exam_nonce', 'nonce');

        $attempt_id = intval($_POST['attempt_id']);
        $res = $this->finalize_attempt($attempt_id);

        if ($res) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Завршување на тестот, автоматско бодување и категоризација на диплома
     */
    public function finalize_attempt($attempt_id) {
        global $wpdb;
        $table_attempts  = $wpdb->prefix . 'zmp_attempts';
        $table_questions = $wpdb->prefix . 'zmp_questions';

        if (!$this->student_owns_attempt($attempt_id)) {
            wp_send_json_error(array('message' => 'Невалиден обид.'));
        }
        $attempt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_attempts WHERE id = %d", $attempt_id));
        if (!$attempt || $attempt->status !== 'in_progress' || !empty($attempt->is_paused)) {
            return false;
        }

        $answers = !empty($attempt->answers_json) ? json_decode($attempt->answers_json, true) : array();

        // Пресметка на освоените поени
        $question_ids = !empty($attempt->question_ids_json) ? array_values(array_filter(array_map('absint', (array)json_decode($attempt->question_ids_json, true)))) : array();
        if (count($question_ids) !== 20) { return false; }
        $questions = $wpdb->get_results("SELECT id, correct_option_index, points FROM $table_questions WHERE id IN (" . implode(',', $question_ids) . ")");
        if (count($questions) !== 20) { return false; }

        $total_points = 0.00;
        $max_points   = 0.00;

        foreach ($questions as $q) {
            $max_points += floatval($q->points);
            if (isset($answers[$q->id]) && intval($answers[$q->id]) === intval($q->correct_option_index)) {
                $total_points += floatval($q->points);
            }
        }

        // Скалирање на 100 поени доколку тестот има различен вкупен збир
        if ($max_points > 0 && $max_points != 100.00) {
            $scaled_score = ($total_points / $max_points) * 100.00;
        } else {
            $scaled_score = $total_points;
        }

        $award = ZMP_Scoring::get_award_details($scaled_score, 'mk');

        $wpdb->update(
            $table_attempts,
            array(
                'completed_at' => current_time('mysql'),
                'total_points' => $scaled_score,
                'award_level'  => $award['key'],
                'status'       => 'completed'
            ),
            array('id' => $attempt_id)
        );

        return true;
    }

    /**
     * Јавна ранг-листа [zmp_public_results]
     */
    public function render_public_results($atts) {
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) { return '<div class="zmp-container"><div class="zmp-card">За резултати и одлуки користете ја вашата доделена категорија во табот „Жири и оценување“.</div></div>'; }
        global $wpdb;
        $user = wp_get_current_user();
        $is_teacher = current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard')
            || in_array('zmp_teacher', (array)$user->roles, true);
        $can_view_results = $is_teacher || current_user_can('zmp_testator_cap') || current_user_can('view_zmp_testator_dashboard')
            || in_array('zmp_testator', (array)$user->roles, true) || current_user_can('zmp_commission_cap')
            || current_user_can('view_zmp_commission_dashboard') || in_array('zmp_commission', (array)$user->roles, true)
            || current_user_can('manage_options');
        if (!$can_view_results) {
            return '<div class="zmp-container"><div class="zmp-card">Немате дозвола за преглед на резултатите.</div></div>';
        }
        $result_scope = $is_teacher && isset($_GET['zmp_result_scope'])
            && sanitize_key(wp_unslash($_GET['zmp_result_scope'])) === 'mine' ? 'mine' : 'all';
        $competitions = $wpdb->get_results("SELECT id,academic_year,title,is_active,current_phase,workflow_status FROM {$wpdb->prefix}zmp_competitions ORDER BY academic_year DESC,id DESC");
        $active_comp = null;
        foreach ($competitions as $contest) { if ((int)$contest->is_active === 1) { $active_comp = $contest; break; } }
        $year_filter = isset($_GET['zmp_result_year']) ? sanitize_text_field(wp_unslash($_GET['zmp_result_year'])) : ($active_comp ? $active_comp->academic_year : ($competitions ? $competitions[0]->academic_year : ''));
        $selected_comp = null;
        foreach ($competitions as $contest) { if ($contest->academic_year === $year_filter) { $selected_comp = $contest; break; } }
        if (!$selected_comp && $competitions) { $selected_comp = $competitions[0]; $year_filter = $selected_comp->academic_year; }
        // Historical years default to their own phase state, never to the current year's phase.
        $default_phase = $selected_comp
            ? ((isset($selected_comp->workflow_status) && $selected_comp->workflow_status === 'completed') ? 'all' : $selected_comp->current_phase)
            : 'all';
        $phase_filter = isset($_GET['zmp_result_phase']) ? sanitize_key(wp_unslash($_GET['zmp_result_phase'])) : (isset($_GET['phase']) ? sanitize_key(wp_unslash($_GET['phase'])) : $default_phase);
        if (!in_array($phase_filter, array('all','school','municipal','regional','state'), true)) { $phase_filter = $default_phase; }
        $grade_filter = isset($_GET['grade']) ? absint($_GET['grade']) : 0;
        $group_filter = isset($_GET['zmp_result_group']) ? absint($_GET['zmp_result_group']) : 0;
        $education_filter = isset($_GET['zmp_result_education']) ? sanitize_key(wp_unslash($_GET['zmp_result_education'])) : '';
        if (!in_array($education_filter, array('', 'primary', 'secondary'), true)) { $education_filter = ''; }
        $track_filter = isset($_GET['zmp_result_track']) ? sanitize_key(wp_unslash($_GET['zmp_result_track'])) : '';
        if (!in_array($track_filter, array('', 'gymnasium', 'vocational'), true)) { $track_filter = ''; }
        $region_filter = isset($_GET['zmp_result_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_result_region'])) ? sanitize_key(wp_unslash($_GET['zmp_result_region'])) : '';
        $municipality_filter = isset($_GET['zmp_result_municipality']) ? sanitize_text_field(wp_unslash($_GET['zmp_result_municipality'])) : '';
        $school_filter = isset($_GET['zmp_result_school']) ? sanitize_text_field(wp_unslash($_GET['zmp_result_school'])) : '';
        $category_filter_raw = isset($_GET['zmp_result_category']) ? sanitize_text_field(wp_unslash($_GET['zmp_result_category'])) : 'all';
        $category_filter = $category_filter_raw === 'all' ? -1 : absint($category_filter_raw);
        // A result is visible only after a moderator publishes it following commission confirmation.
        $pub_query = "SELECT DISTINCT category_id FROM {$wpdb->prefix}zmp_announcements WHERE kind='results' AND status='published' AND academic_year=%s";
        $pub_args = array($year_filter);
        if ($phase_filter !== 'all') { $pub_query .= ' AND phase=%s'; $pub_args[] = $phase_filter; }
        $released_categories = array_map('intval', (array)$wpdb->get_col($wpdb->prepare($pub_query, $pub_args)));
        $legacy_competition_id=0;$legacy_released_phases=array();
        if(in_array(0,$released_categories,true)){
            $legacy_competition_id=ZMP_Communications::legacy_competition_id_for_year($year_filter);
            if($legacy_competition_id){
                $announced_phases=array_map('sanitize_key',(array)$wpdb->get_col($wpdb->prepare("SELECT DISTINCT phase FROM {$wpdb->prefix}zmp_announcements WHERE kind='results' AND status='published' AND academic_year=%s AND category_id=0",$year_filter)));
                foreach($announced_phases as $released_phase){
                    if(!in_array($released_phase,array('school','municipal','regional','state'),true))continue;
                    if(ZMP_Communications::closed_legacy_competition_id($year_filter,$released_phase)===$legacy_competition_id)$legacy_released_phases[]=$released_phase;
                }
                if($phase_filter!=='all'&&!in_array($phase_filter,$legacy_released_phases,true))$legacy_released_phases=array();
            }
            if(!$legacy_released_phases)$released_categories=array_values(array_diff($released_categories,array(0)));
        }
        if ($category_filter >= 0) { $released_categories = in_array($category_filter, $released_categories, true) ? array($category_filter) : array(); }
        $results = array();
        if (in_array(0, $released_categories, true)) {
            $where_args = array($legacy_competition_id,$year_filter); $phase_clause = ''; $grade_clause = ''; $region_clause = ''; $municipality_clause = ''; $school_clause = ''; $education_clause = '';
            if ($phase_filter !== 'all') { $phase_clause = ' AND phase=%s'; $where_args[] = $phase_filter; } else { $phase_clause = ' AND phase IN (' . implode(',',array_fill(0,count($legacy_released_phases),'%s')) . ')'; $where_args = array_merge($where_args,$legacy_released_phases); }
            if ($grade_filter > 0) { $grade_clause = ' AND grade=%d'; $where_args[] = $grade_filter; }
            if ($region_filter !== '') { $region_clause = ' AND region=%s'; $where_args[] = $region_filter; }
            if ($municipality_filter !== '') { $municipality_clause = ' AND municipality=%s'; $where_args[] = $municipality_filter; }
            if ($school_filter !== '') { $school_clause = ' AND school_name LIKE %s'; $where_args[] = '%' . $wpdb->esc_like($school_filter) . '%'; }
            // Legacy knowledge results store grades only: 6-9 are primary and 1-2 are secondary.
            if ($education_filter === 'primary') { $education_clause = ' AND grade BETWEEN 6 AND 9'; }
            elseif ($education_filter === 'secondary') { $education_clause = ' AND grade BETWEEN 1 AND 2'; }
            if ($group_filter || $track_filter !== '') { $education_clause .= ' AND 1=0'; }
            $mentor_clause = $result_scope === 'mine' ? ' AND mentor_user_id=%d' : '';
            if ($result_scope === 'mine') { $where_args[] = get_current_user_id(); }
            $results = (array)$wpdb->get_results($wpdb->prepare(
                "SELECT attempt_id AS id,0 AS category_id,0 AS group_id,phase,access_code,student_name AS participant_name,grade,municipality,region,school_name,total_points,award_level,qualified_next_stage,completed_at,'' AS category_name,'' AS group_name,'knowledge' AS result_type FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s$phase_clause$grade_clause$region_clause$municipality_clause$school_clause$education_clause$mentor_clause ORDER BY total_points DESC,completed_at ASC,attempt_id ASC",
                $where_args
            ));
        }
        $category_ids = array_values(array_filter($released_categories, function($id){ return $id > 0; }));
        if ($category_ids) {
            $in = implode(',', array_map('absint', $category_ids));
            $phase_clause = $phase_filter === 'all' ? '' : $wpdb->prepare(' AND f.phase=%s', $phase_filter);
            $grade_clause = $grade_filter > 0 ? $wpdb->prepare(' AND e.grade=%d', $grade_filter) : '';
            $mentor_clause = $result_scope === 'mine' ? $wpdb->prepare(' AND e.wp_user_id=%d', get_current_user_id()) : '';
            $region_clause = $region_filter !== '' ? $wpdb->prepare(' AND e.region=%s', $region_filter) : '';
            $municipality_clause = $municipality_filter !== '' ? $wpdb->prepare(' AND e.municipality=%s', $municipality_filter) : '';
            $school_clause = $school_filter !== '' ? $wpdb->prepare(' AND e.school_name LIKE %s', '%' . $wpdb->esc_like($school_filter) . '%') : '';
            $group_clause = $group_filter ? $wpdb->prepare(' AND e.group_id=%d', $group_filter) : '';
            $education_clause = $education_filter !== '' ? $wpdb->prepare(' AND g.education_level=%s', $education_filter) : '';
            $track_clause = $track_filter !== '' ? $wpdb->prepare(' AND g.school_track=%s', $track_filter) : '';
            $category_rows = $wpdb->get_results("SELECT f.entry_id AS id,f.category_id,e.group_id,f.phase,e.participant_name AS access_code,e.participant_name,e.grade,e.municipality,e.region,e.school_name,f.final_points AS total_points,f.award_key AS award_level,0 AS qualified_next_stage,f.decided_at AS completed_at,c.name_mk AS category_name,g.name_mk AS group_name,g.education_level,g.school_track,COALESCE((SELECT SUM(cr.max_points) FROM {$wpdb->prefix}zmp_scoring_criteria cr WHERE cr.season_id=f.season_id AND cr.category_id=f.category_id AND cr.is_active=1),0) AS max_points,'category' AS result_type FROM {$wpdb->prefix}zmp_category_final_results f JOIN {$wpdb->prefix}zmp_seasons s ON s.id=f.season_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=f.category_id JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=f.entry_id LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE s.academic_year=" . $wpdb->prepare('%s', $year_filter) . " AND f.category_id IN ($in) AND f.status='confirmed'$phase_clause$grade_clause$region_clause$municipality_clause$school_clause$group_clause$education_clause$track_clause$mentor_clause ORDER BY f.final_points DESC,f.decided_at ASC");
            $results = array_merge($results, (array)$category_rows);
        }
        usort($results, function($a,$b){
            $scope_a = (int)($a->category_id ?? 0) . ':' . (int)($a->group_id ?? 0) . ':' . $a->phase;
            $scope_b = (int)($b->category_id ?? 0) . ':' . (int)($b->group_id ?? 0) . ':' . $b->phase;
            return strcmp($scope_a, $scope_b) ?: (((float)$b->total_points <=> (float)$a->total_points) ?: strcmp((string)$a->completed_at,(string)$b->completed_at));
        });
        // Rankings are comparable only inside the same category/group/phase score scale.
        $rank_scope = ''; $scope_position = 0; $previous_points = null; $previous_rank = 0;
        foreach ($results as $result) {
            $scope = (int)($result->category_id ?? 0) . ':' . (int)($result->group_id ?? 0) . ':' . $result->phase;
            if ($scope !== $rank_scope) { $rank_scope = $scope; $scope_position = 0; $previous_points = null; $previous_rank = 0; }
            $scope_position++;
            if ($previous_points === null || (float)$result->total_points !== $previous_points) { $previous_rank = $scope_position; }
            $result->display_rank = $previous_rank;
            $previous_points = (float)$result->total_points;
        }
        $top10_results = array();
        if ($category_filter >= 0 && $phase_filter !== 'all') {
            $top10_results = $results;
            usort($top10_results, function($a, $b) { return ((float)$b->total_points <=> (float)$a->total_points) ?: strcmp((string)$a->completed_at, (string)$b->completed_at); });
            $top10_results = array_slice($top10_results, 0, 10);
        }
        $result_groups = $wpdb->get_results("SELECT g.id,g.name_mk AS group_name,g.education_level,g.school_track,c.name_mk AS category_name FROM {$wpdb->prefix}zmp_category_groups g JOIN {$wpdb->prefix}zmp_categories c ON c.id=g.category_id WHERE g.is_active=1 AND c.is_active=1 AND c.category_code<>'knowledge_test' ORDER BY c.sort_order,g.sort_order,g.id");
        $grade_labels = array(1=>'I година',2=>'II година',3=>'III година',4=>'IV година',5=>'V одделение',6=>'VI одделение',7=>'VII одделение',8=>'VIII одделение',9=>'IX одделение',10=>'X година',11=>'XI година',12=>'XII година',13=>'XIII година');
        ob_start();
        ?>
        <div class="zmp-container"><div class="zmp-card">
            <h3>🏆 Официјални резултати и ранг-листа</h3>
            <p>Прикажани се само официјално објавените резултати. Тестот на знаење прикажува шифра; музичките категории го прикажуваат името на учесникот или ансамблот.</p>
            <?php if ($is_teacher): ?><nav class="zmp-results-scope" aria-label="Опсег на резултати"><a class="zmp-btn <?php echo $result_scope === 'all' ? 'zmp-btn-primary' : 'zmp-btn-secondary'; ?>" href="<?php echo esc_url(add_query_arg('zmp_result_scope','all')); ?>">Сите објавени резултати</a> <a class="zmp-btn <?php echo $result_scope === 'mine' ? 'zmp-btn-primary' : 'zmp-btn-secondary'; ?>" href="<?php echo esc_url(add_query_arg('zmp_result_scope','mine')); ?>">Мои ученици</a></nav><?php endif; ?>
            <form method="get" class="zmp-filter-bar">
                <?php if (isset($_GET['zmp_portal_tab'])): ?><input type="hidden" name="zmp_portal_tab" value="rezultati"><?php endif; ?>
                <?php if ($is_teacher): ?><input type="hidden" name="zmp_result_scope" value="<?php echo esc_attr($result_scope); ?>"><?php endif; ?>
                <label>Учебна година <select name="zmp_result_year"><?php foreach ($competitions as $contest): ?><option value="<?php echo esc_attr($contest->academic_year); ?>" <?php selected($year_filter,$contest->academic_year); ?>><?php echo esc_html($contest->academic_year); ?></option><?php endforeach; ?></select></label>
                <label>Натпреварувачко ниво <select name="zmp_result_phase"><option value="all" <?php selected($phase_filter,'all'); ?>>Сите натпреварувачки нивоа</option><option value="school" <?php selected($phase_filter,'school'); ?>>Училишно натпреварувачко ниво</option><option value="municipal" <?php selected($phase_filter,'municipal'); ?>>Општинско натпреварувачко ниво</option><option value="regional" <?php selected($phase_filter,'regional'); ?>>Регионално натпреварувачко ниво</option><option value="state" <?php selected($phase_filter,'state'); ?>>Државно натпреварувачко ниво</option></select></label>
                <label>Категорија <select name="zmp_result_category"><option value="all" <?php selected($category_filter,-1); ?>>Сите објавени категории</option><option value="0" <?php selected($category_filter,0); ?>>Тест на знаење</option><?php foreach ($wpdb->get_results("SELECT id,name_mk FROM {$wpdb->prefix}zmp_categories WHERE is_active=1 AND category_code<>'knowledge_test' ORDER BY sort_order,id") as $result_category): ?><option value="<?php echo (int)$result_category->id; ?>" <?php selected($category_filter,(int)$result_category->id); ?>><?php echo esc_html($result_category->name_mk); ?></option><?php endforeach; ?></select></label>
                <label>Образование <select name="zmp_result_education"><option value="">Сите нивоа</option><option value="primary" <?php selected($education_filter,'primary'); ?>>Основно</option><option value="secondary" <?php selected($education_filter,'secondary'); ?>>Средно</option></select></label>
                <label>Тип средно <select name="zmp_result_track"><option value="">Сите типови</option><option value="gymnasium" <?php selected($track_filter,'gymnasium'); ?>>Гимназиско</option><option value="vocational" <?php selected($track_filter,'vocational'); ?>>Стручно</option></select></label>
                <label>Група <select name="zmp_result_group"><option value="0">Сите групи</option><?php foreach ($result_groups as $result_group): ?><option value="<?php echo (int)$result_group->id; ?>" <?php selected($group_filter,(int)$result_group->id); ?>><?php echo esc_html($result_group->category_name . ' — ' . $result_group->group_name); ?></option><?php endforeach; ?></select></label>
                <label>Одделение / година <select name="grade"><option value="0">Сите</option><?php foreach ($grade_labels as $grade_value=>$grade_label): ?><option value="<?php echo (int)$grade_value; ?>" <?php selected($grade_filter,(int)$grade_value); ?>><?php echo esc_html($grade_label); ?></option><?php endforeach; ?></select></label>
                <label>Регион <select name="zmp_result_region"><option value="">Сите региони</option><?php foreach (ZMP_Regions::regions() as $region_key=>$region_data): ?><option value="<?php echo esc_attr($region_key); ?>" <?php selected($region_filter,$region_key); ?>><?php echo esc_html($region_data['label']); ?></option><?php endforeach; ?></select></label>
                <label>Општина <input name="zmp_result_municipality" value="<?php echo esc_attr($municipality_filter); ?>" list="zmp-results-municipalities" placeholder="Сите општини"></label><datalist id="zmp-results-municipalities"><?php foreach (ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist>
                <label>Училиште <input name="zmp_result_school" value="<?php echo esc_attr($school_filter); ?>" placeholder="Име на училиште"></label>
                <button type="submit" class="zmp-btn zmp-btn-primary">Прикажи резултати</button>
            </form>
            <?php if ($phase_filter !== 'all' && $year_filter !== ''): ?>
                <p><a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url(add_query_arg(array('zmp_results_csv'=>1,'zmp_result_year'=>$year_filter,'zmp_result_phase'=>$phase_filter,'zmp_result_category'=>$category_filter < 0 ? 'all' : $category_filter,'zmp_result_scope'=>$result_scope,'zmp_result_region'=>$region_filter,'zmp_result_municipality'=>$municipality_filter,'zmp_result_school'=>$school_filter,'zmp_result_education'=>$education_filter,'zmp_result_track'=>$track_filter,'zmp_result_group'=>$group_filter,'grade'=>$grade_filter))); ?>">Преземи ја резултатите за избраното натпреварувачко ниво во Excel (CSV)</a>
                <?php if ($category_filter >= 0): ?><a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url(add_query_arg(array('zmp_results_pdf'=>1,'grade'=>$grade_filter,'phase'=>$phase_filter,'zmp_result_year'=>$year_filter,'zmp_result_category'=>$category_filter,'zmp_result_scope'=>$result_scope,'zmp_result_region'=>$region_filter,'zmp_result_municipality'=>$municipality_filter,'zmp_result_school'=>$school_filter,'zmp_result_education'=>$education_filter,'zmp_result_track'=>$track_filter,'zmp_result_group'=>$group_filter))); ?>" target="_blank" rel="noopener">Преземи / печати PDF</a><?php else: ?><span>За PDF изберете конкретна категорија.</span><?php endif; ?></p>
            <?php endif; ?>
            <?php if ($category_filter >= 0 && $phase_filter !== 'all'): ?><h4>Топ 10 — <?php echo esc_html($year_filter); ?> / <?php echo esc_html((array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво')[$phase_filter] ?? $phase_filter)); ?></h4>
            <ol class="zmp-results-top10"><?php foreach ($top10_results as $top): ?><li><strong><?php echo esc_html($top->result_type === 'category' ? $top->participant_name : $top->access_code); ?></strong><?php if (!empty($top->category_name)): ?> — <?php echo esc_html($top->category_name . (!empty($top->group_name) ? ' / ' . $top->group_name : '')); ?><?php elseif (!empty($top->grade)): ?> — <?php echo esc_html($grade_labels[(int)$top->grade] ?? $top->grade); ?><?php endif; ?>, <?php echo esc_html(number_format((float)$top->total_points,1)); ?> поени</li><?php endforeach; ?><?php if (empty($top10_results)): ?><li>Нема јавно објавени резултати за избраниот период.</li><?php endif; ?></ol><?php elseif ($category_filter < 0): ?><p>За Топ 10 изберете конкретна категорија; бодовните скали не се исти меѓу категориите.</p><?php else: ?><p>За Топ 10 изберете конкретно натпреварувачко ниво.</p><?php endif; ?>
            <div class="zmp-table-responsive"><table class="zmp-table zmp-sortable-results" data-page-size="25"><thead><tr><th>Ранг</th><th>Ученик / ансамбл</th><th>Категорија / група</th><th>Образование</th><th>Натпреварувачко ниво</th><th>Одделение / година</th><th>Регион</th><th>Општина</th><th>Училиште</th><th>Освоени поени</th><th>Освоена награда</th><th>Пласман во следно ниво</th></tr></thead><tbody>
            <?php if (empty($results)): ?><tr><td colspan="12" style="text-align:center">Нема јавно објавени резултати за избраните филтри.</td></tr><?php else: foreach ($results as $row): $award = $row->result_type === 'category' ? array('first'=>'Прва награда','second'=>'Втора награда','third'=>'Трета награда','honorable'=>'Пофалница')[$row->award_level] ?? 'Без награда' : ZMP_Scoring::get_award_details($row->total_points,'mk')['name_mk']; $row_education = $row->result_type === 'category' ? (($row->education_level ?? '') === 'primary' ? 'Основно' : 'Средно') : (((int)$row->grade >= 6 && (int)$row->grade <= 9) ? 'Основно' : (((int)$row->grade >= 1 && (int)$row->grade <= 2) ? 'Средно' : '—')); $row_track = !empty($row->school_track) ? ($row->school_track === 'gymnasium' ? ' — Гимназиско' : ' — Стручно') : ''; ?>
                <tr><td><strong>#<?php echo (int)$row->display_rank; ?></strong></td><td><?php echo $row->result_type === 'category' ? esc_html($row->participant_name) : '<code>' . esc_html($row->access_code) . '</code>'; ?></td><td><?php echo esc_html(trim(($row->category_name ?? '') . (!empty($row->group_name) ? ' / ' . $row->group_name : ''))); ?></td><td><?php echo esc_html($row_education . $row_track); ?></td><td><?php echo esc_html(ZMP_Category_Jury::phase_label($row->phase)); ?></td><td><?php echo $row->grade ? esc_html($grade_labels[(int)$row->grade] ?? (string)$row->grade) : '—'; ?></td><td><?php echo esc_html(ZMP_Regions::label($row->region)); ?></td><td><?php echo esc_html($row->municipality); ?></td><td><?php echo esc_html($row->school_name); ?></td><td><strong><?php echo esc_html(number_format((float)$row->total_points,1)); ?></strong><?php echo $row->result_type === 'category' && (float)$row->max_points > 0 ? ' / ' . esc_html(number_format((float)$row->max_points,1)) : ($row->result_type === 'knowledge' ? ' / 100' : ''); ?></td><td><?php echo esc_html($award); ?></td><td><?php echo $row->qualified_next_stage ? '<span style="color:#16a34a;font-weight:bold">✓ Пласиран(а)</span>' : '—'; ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody></table></div><div class="zmp-results-pagination" aria-live="polite"><button type="button" class="zmp-btn zmp-btn-secondary" data-results-prev>Претходна</button> <span data-results-page></span> <button type="button" class="zmp-btn zmp-btn-secondary" data-results-next>Следна</button></div>
        </div></div>
        <?php
        return ob_get_clean();
    }

    public function maybe_render_results_pdf() {
        if (empty($_GET['zmp_results_pdf'])) return;
        if (!current_user_can('zmp_teacher_cap') && !current_user_can('view_zmp_teacher_dashboard')
            && !current_user_can('zmp_testator_cap') && !current_user_can('view_zmp_testator_dashboard')
            && !current_user_can('zmp_commission_cap') && !current_user_can('view_zmp_commission_dashboard')
            && !current_user_can('manage_options')) {
            wp_die('Немате дозвола за преземање резултати.', '', array('response'=>403));
        }
        global $wpdb;
        $year = sanitize_text_field(wp_unslash($_GET['zmp_result_year'] ?? ''));
        $category_id = absint($_GET['zmp_result_category'] ?? 0);
        if (class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::is_restricted_juror()) {
            $season_id = absint($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_seasons WHERE academic_year=%s", sanitize_text_field(wp_unslash($_GET['zmp_result_year'] ?? '')))));
            if (!$season_id || !$category_id || !ZMP_Category_Jury::has_assignment($season_id,$category_id,get_current_user_id())) { wp_die('Немате доделба за PDF резултати од оваа категорија и година.', '', array('response'=>403)); }
        }
        $grade = absint($_GET['grade'] ?? 0);
        $region = sanitize_key(wp_unslash($_GET['zmp_result_region'] ?? ''));
        if (!ZMP_Regions::valid_region($region)) { $region = ''; }
        $municipality = sanitize_text_field(wp_unslash($_GET['zmp_result_municipality'] ?? ''));
        $school = sanitize_text_field(wp_unslash($_GET['zmp_result_school'] ?? ''));
        $group_id = absint($_GET['zmp_result_group'] ?? 0);
        $education = sanitize_key(wp_unslash($_GET['zmp_result_education'] ?? ''));
        if (!in_array($education, array('', 'primary', 'secondary'), true)) { $education = ''; }
        $track = sanitize_key(wp_unslash($_GET['zmp_result_track'] ?? ''));
        if (!in_array($track, array('', 'gymnasium', 'vocational'), true)) { $track = ''; }
        $phase = sanitize_key(wp_unslash($_GET['phase'] ?? 'school'));
        $mine = sanitize_key(wp_unslash($_GET['zmp_result_scope'] ?? '')) === 'mine'
            && (current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard'));
        if (!in_array($phase,array('school','municipal','regional','state'),true)) { wp_die('Изберете натпреварувачко ниво за PDF.', '', array('response'=>400)); }
        if (!ZMP_Communications::is_result_published($year, $category_id, $phase)) { wp_die('Резултатите за ова натпреварувачко ниво сè уште не се објавени.', '', array('response'=>404)); }
        if ($category_id > 0) {
            $query = "SELECT f.final_points AS total_points,f.award_key AS award_level,f.phase,e.participant_name AS access_code,e.grade,e.region,e.municipality,e.school_name,c.name_mk AS category,g.name_mk AS group_name,g.education_level,g.school_track FROM {$wpdb->prefix}zmp_category_final_results f JOIN {$wpdb->prefix}zmp_seasons s ON s.id=f.season_id JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=f.entry_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=f.category_id LEFT JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE s.academic_year=%s AND f.category_id=%d AND f.phase=%s AND f.status='confirmed'";
            $args = array($year,$category_id,$phase); if ($mine) { $query .= ' AND e.wp_user_id=%d'; $args[] = get_current_user_id(); }
            if ($grade) { $query .= ' AND e.grade=%d'; $args[] = $grade; }
            if ($group_id) { $query .= ' AND e.group_id=%d'; $args[] = $group_id; }
            if ($education !== '') { $query .= ' AND g.education_level=%s'; $args[] = $education; }
            if ($track !== '') { $query .= ' AND g.school_track=%s'; $args[] = $track; }
        } else {
            $legacy_competition_id=ZMP_Communications::closed_legacy_competition_id($year,$phase);
            if(!$legacy_competition_id)wp_die('Резултатите не се достапни за недвосмислен затворен натпревар.','',array('response'=>404));
            $query = "SELECT total_points,award_level,qualified_next_stage,phase,access_code,grade,region,municipality,school_name,'' AS category,'' AS group_name,CASE WHEN grade BETWEEN 6 AND 9 THEN 'primary' WHEN grade BETWEEN 1 AND 2 THEN 'secondary' ELSE '' END AS education_level,'' AS school_track FROM {$wpdb->prefix}zmp_phase_results WHERE competition_id=%d AND academic_year=%s AND phase=%s";
            $args = array($legacy_competition_id,$year,$phase); if ($grade) { $query .= ' AND grade=%d'; $args[] = $grade; } if ($mine) { $query .= ' AND mentor_user_id=%d'; $args[] = get_current_user_id(); }
            if ($education === 'primary') { $query .= ' AND grade BETWEEN 6 AND 9'; }
            elseif ($education === 'secondary') { $query .= ' AND grade BETWEEN 1 AND 2'; }
            if ($group_id || $track !== '') { $query .= ' AND 1=0'; }
        }
        if ($region !== '') { $query .= ' AND ' . ($category_id > 0 ? 'e.' : '') . 'region=%s'; $args[]=$region; }
        if ($municipality !== '') { $query .= ' AND ' . ($category_id > 0 ? 'e.' : '') . 'municipality=%s'; $args[]=$municipality; }
        if ($school !== '') { $query .= ' AND ' . ($category_id > 0 ? 'e.' : '') . 'school_name LIKE %s'; $args[]='%' . $wpdb->esc_like($school) . '%'; }
        $rows = $wpdb->get_results($wpdb->prepare($query.' ORDER BY total_points DESC', $args));
        nocache_headers(); header('Content-Type: text/html; charset=utf-8');
        ?><!doctype html><html lang="mk"><meta charset="utf-8"><title>Резултати од натпреварот</title><style>body{font:14px Arial;margin:24px;color:#172033}table{border-collapse:collapse;width:100%}th,td{border:1px solid #aaa;padding:7px;text-align:left}th{background:#eee}.print{margin-bottom:16px}@media print{.print{display:none}}</style><button class="print" onclick="window.print()">Печати / зачувај како PDF</button><h1>Официјални резултати — <?php echo esc_html($year.' / '.$phase); ?></h1><table><thead><tr><th>Ранг</th><th>Ученик/ансамбл</th><th>Категорија</th><th>Група</th><th>Образование</th><th>Тип средно</th><th>Одделение / година</th><th>Регион</th><th>Општина</th><th>Училиште</th><th>Поени</th></tr></thead><tbody><?php foreach($rows as $i=>$r): ?><tr><td><?php echo esc_html($i+1); ?></td><td><?php echo esc_html($r->access_code); ?></td><td><?php echo esc_html($r->category); ?></td><td><?php echo esc_html($r->group_name); ?></td><td><?php echo esc_html(($r->education_level ?? '')==='primary'?'Основно':(($r->education_level ?? '')==='secondary'?'Средно':'—')); ?></td><td><?php echo esc_html(($r->school_track ?? '')==='gymnasium'?'Гимназиско':(($r->school_track ?? '')==='vocational'?'Стручно':'—')); ?></td><td><?php echo esc_html($r->grade); ?></td><td><?php echo esc_html(ZMP_Regions::label($r->region)); ?></td><td><?php echo esc_html($r->municipality); ?></td><td><?php echo esc_html($r->school_name); ?></td><td><?php echo esc_html(number_format((float)$r->total_points,1)); ?></td></tr><?php endforeach; ?></tbody></table><script>window.addEventListener('load',function(){window.print()});</script></html><?php
        exit;
    }

}





