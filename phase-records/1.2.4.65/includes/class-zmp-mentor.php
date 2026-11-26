<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Mentor {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('zmp_mentor_portal', array($this, 'render_mentor_portal'));
        add_action('init', array($this, 'handle_mentor_actions'));
        add_action('admin_post_zmp_submit_subject_proposal', array($this, 'handle_subject_proposal'));
        add_action('admin_post_zmp_export_teacher_register', array($this, 'handle_teacher_register_export'));
    }

    /** Submit a subject proposal through the service's strict teacher/actor checks. */
    public function handle_subject_proposal() {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            wp_die(esc_html__('Предлогот може да се испрати само преку формуларот.', 'zmp-competitions'), '', array('response'=>405));
        }
        if (!is_user_logged_in() || !isset($_POST['_wpnonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'zmp_submit_subject_proposal')) {
            wp_die(esc_html__('Безбедносната проверка не помина.', 'zmp-competitions'), '', array('response'=>403));
        }
        if (!class_exists('ZMP_Mentor_Subject_Proposals')) {
            wp_die(esc_html__('Предлозите за предмети моментално не се достапни.', 'zmp-competitions'), '', array('response'=>503));
        }
        $name = isset($_POST['zmp_subject_name']) && is_scalar($_POST['zmp_subject_name'])
            ? sanitize_text_field(wp_unslash($_POST['zmp_subject_name'])) : '';
        $result = ZMP_Mentor_Subject_Proposals::submit(get_current_user_id(), (string) $name);
        $status = is_wp_error($result) ? $result->get_error_code() : 'submitted';
        $referer = wp_get_referer();
        $fallback = home_url('/');
        $return = wp_validate_redirect($referer ?: '', $fallback);
        wp_safe_redirect(add_query_arg('zmp_subject_status', sanitize_key($status), $return));
        exit;
    }

    /** Generate the restricted register CSV only after an authenticated nonce-protected POST. */
    public function handle_teacher_register_export() {
        if (!is_user_logged_in() || !isset($_POST['_wpnonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'zmp_export_teacher_register')) {
            wp_die(esc_html__('Безбедносната проверка не помина.', 'zmp-competitions'), '', array('response'=>403));
        }
        if (!class_exists('ZMP_Teacher_Register') || !ZMP_Teacher_Register::can_view_current_user()) {
            wp_die(esc_html__('Немате дозвола за пристап до регистарот.', 'zmp-competitions'), '', array('response'=>403));
        }
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            wp_die(esc_html__('Извозот е достапен само преку испраќање на формуларот.', 'zmp-competitions'), '', array('response'=>405));
        }
        $filters = self::teacher_register_filters($_POST);
        if (function_exists('nocache_headers')) { nocache_headers(); }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="zmp-teacher-register.csv"');
        header('X-Content-Type-Options: nosniff');
        $result = ZMP_Teacher_Register::export_csv($filters);
        if (is_wp_error($result)) {
            status_header(403);
            wp_die(esc_html($result->get_error_message()));
        }
        exit;
    }

    private static function teacher_register_filters($input, $name_key = 'name') {
        $filters = array();
        foreach (array('subject','school','municipality','region') as $key) {
            if (isset($input[$key]) && is_scalar($input[$key])) {
                $filters[$key] = sanitize_text_field(wp_unslash((string) $input[$key]));
            }
        }
        if (isset($input[$name_key]) && is_scalar($input[$name_key])) {
            $filters['name'] = sanitize_text_field(wp_unslash((string) $input[$name_key]));
        }
        return $filters;
    }

    /** Render the proposal and restricted directory panels for an allowed viewer. */
    public static function render_teacher_register_shortcode() {
        if (!is_user_logged_in() || !class_exists('ZMP_Teacher_Register') || !ZMP_Teacher_Register::can_view_current_user()) {
            return '<div class="zmp-alert zmp-alert-warning" role="alert">' . esc_html__('Овој регистар е достапен само за овластени корисници.', 'zmp-competitions') . '</div>';
        }
        return self::get_instance()->render_teacher_register_panel();
    }

    private function render_teacher_register_panel() {
        global $wpdb;
        if (!is_user_logged_in() || !ZMP_Teacher_Register::can_view_current_user()) { return ''; }
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $can_propose = ZMP_Mentor_School_Profile::is_teacher((int) $user->ID);
        $status = isset($_GET['zmp_subject_status']) && is_scalar($_GET['zmp_subject_status'])
            ? sanitize_key(wp_unslash($_GET['zmp_subject_status'])) : '';
        $status_messages = array(
            'submitted'=>array('success',__('Предлогот е испратен за одобрување.', 'zmp-competitions')),
            'forbidden'=>array('error',__('Предлогот не може да се испрати од оваа сметка.', 'zmp-competitions')),
            'invalid_subject'=>array('error',__('Внесете име на предмет до 190 знаци.', 'zmp-competitions')),
            'proposal_pending'=>array('warning',__('Предлог за овој предмет веќе чека одлука.', 'zmp-competitions')),
            'proposal_already_decided'=>array('warning',__('Овој предмет веќе е разгледан. Проверете го одобрениот регистар.', 'zmp-competitions')),
            'database_error'=>array('error',__('Предлогот не е зачуван. Обидете се повторно.', 'zmp-competitions')),
        );
        $filters = self::teacher_register_filters($_GET, 'zmp_register_name');
        $page = isset($_GET['zmp_register_page']) && is_scalar($_GET['zmp_register_page']) ? absint(wp_unslash($_GET['zmp_register_page'])) : 1;
        $results = ZMP_Teacher_Register::search($filters, max(1, $page), 20);
        $proposal_rows = array();
        if ($can_propose) {
            $proposal_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT proposed_name,status,created_at,decided_at FROM {$wpdb->prefix}zmp_subject_proposals WHERE proposer_id=%d ORDER BY created_at DESC,id DESC LIMIT 20",
                get_current_user_id()
            ));
        }
        $status_labels = array('pending'=>__('На чекање', 'zmp-competitions'),'approved'=>__('Одобрен', 'zmp-competitions'),'rejected'=>__('Одбиен', 'zmp-competitions'));
        ob_start(); ?>
        <section class="zmp-teacher-register" aria-labelledby="zmp-teacher-register-title">
            <h2 id="zmp-teacher-register-title"><?php echo esc_html__('Заеднички регистар на наставници и предмети', 'zmp-competitions'); ?></h2>
            <p><?php echo esc_html__('Регистарот прикажува само професионални податоци за наставникот, одобрените предмети и училиштата.', 'zmp-competitions'); ?></p>
            <?php if (isset($status_messages[$status])): $message = $status_messages[$status]; ?><p class="zmp-alert <?php echo $message[0] === 'success' ? 'zmp-alert-success' : ($message[0] === 'error' ? 'zmp-alert-danger' : 'zmp-alert-warning'); ?>" role="<?php echo $message[0] === 'error' ? 'alert' : 'status'; ?>" aria-live="polite"><?php echo esc_html($message[1]); ?></p><?php endif; ?>
            <?php if ($can_propose): ?>
                <section class="zmp-register-proposal" aria-labelledby="zmp-register-proposal-title">
                    <h3 id="zmp-register-proposal-title"><?php echo esc_html__('Предложи предмет', 'zmp-competitions'); ?></h3>
                    <p id="zmp-register-proposal-help"><?php echo esc_html__('Новите предмети се појавуваат во изборот по одлука на активниот претседател.', 'zmp-competitions'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form">
                        <input type="hidden" name="action" value="zmp_submit_subject_proposal">
                        <?php wp_nonce_field('zmp_submit_subject_proposal'); ?>
                        <label for="zmp-subject-proposal-name"><?php echo esc_html__('Име на предмет', 'zmp-competitions'); ?></label>
                        <div class="zmp-register-proposal__row"><input id="zmp-subject-proposal-name" name="zmp_subject_name" type="text" maxlength="190" required aria-describedby="zmp-register-proposal-help"><button type="submit" class="zmp-btn zmp-btn-primary"><?php echo esc_html__('Испрати предлог', 'zmp-competitions'); ?></button></div>
                    </form>
                    <h4><?php echo esc_html__('Мои последни предлози', 'zmp-competitions'); ?></h4>
                    <?php if ($proposal_rows): ?><ul class="zmp-register-proposal__list"><?php foreach ($proposal_rows as $proposal): ?><li><span><?php echo esc_html($proposal->proposed_name); ?></span><span class="zmp-register-status"><?php echo esc_html($status_labels[$proposal->status] ?? __('Непознат статус', 'zmp-competitions')); ?></span></li><?php endforeach; ?></ul><?php else: ?><p><?php echo esc_html__('Сè уште немате испратено предлог.', 'zmp-competitions'); ?></p><?php endif; ?>
                </section>
            <?php endif; ?>
            <section class="zmp-register-directory" aria-labelledby="zmp-register-directory-title">
                <h3 id="zmp-register-directory-title"><?php echo esc_html__('Пребарај наставници', 'zmp-competitions'); ?></h3>
                <form method="get" class="zmp-form zmp-register-filters">
                    <?php if (isset($_GET['page_id']) && is_scalar($_GET['page_id']) && absint(wp_unslash($_GET['page_id'])) > 0): ?><input type="hidden" name="page_id" value="<?php echo esc_attr(absint(wp_unslash($_GET['page_id']))); ?>"><?php endif; ?>
                    <?php foreach (array('zmp_portal_tab','zmp_mentor_tab') as $query_key): if (isset($_GET[$query_key]) && is_scalar($_GET[$query_key])): ?><input type="hidden" name="<?php echo esc_attr($query_key); ?>" value="<?php echo esc_attr(sanitize_key(wp_unslash($_GET[$query_key]))); ?>"><?php endif; endforeach; ?>
                    <?php foreach (array('zmp_register_name'=>array('name',__('Име на наставник', 'zmp-competitions')),'subject'=>array('subject',__('Предмет', 'zmp-competitions')),'school'=>array('school',__('Училиште', 'zmp-competitions')),'municipality'=>array('municipality',__('Општина', 'zmp-competitions')),'region'=>array('region',__('Регион', 'zmp-competitions'))) as $query_key=>$field): ?>
                        <label for="zmp-register-<?php echo esc_attr($field[0]); ?>"><?php echo esc_html($field[1]); ?><input id="zmp-register-<?php echo esc_attr($field[0]); ?>" name="<?php echo esc_attr($query_key); ?>" type="search" value="<?php echo esc_attr($filters[$field[0]] ?? ''); ?>" maxlength="100"></label>
                    <?php endforeach; ?>
                    <input type="hidden" name="zmp_register_page" value="1">
                    <button type="submit" class="zmp-btn zmp-btn-primary"><?php echo esc_html__('Пребарај', 'zmp-competitions'); ?></button>
                    <a class="zmp-btn zmp-btn-secondary" href="<?php echo esc_url(remove_query_arg(array('name','zmp_register_name','subject','school','municipality','region','zmp_register_page'))); ?>"><?php echo esc_html__('Исчисти', 'zmp-competitions'); ?></a>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-register-export">
                    <input type="hidden" name="action" value="zmp_export_teacher_register"><?php wp_nonce_field('zmp_export_teacher_register'); ?>
                    <?php foreach ($filters as $key=>$value): ?><input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"><?php endforeach; ?>
                    <button type="submit" class="zmp-btn zmp-btn-secondary"><?php echo esc_html__('Преземи CSV', 'zmp-competitions'); ?></button>
                </form>
                <?php if (is_wp_error($results)): ?><p class="zmp-alert zmp-alert-warning" role="alert"><?php echo esc_html__('Регистарот не е достапен.', 'zmp-competitions'); ?></p>
                <?php elseif ($results['items']): ?>
                    <p class="zmp-register-count" role="status" aria-live="polite"><?php printf(esc_html__('%s резултати', 'zmp-competitions'), esc_html(number_format_i18n($results['total']))); ?></p>
                    <div class="zmp-table-responsive zmp-register-table-wrap" tabindex="0" role="region" aria-label="<?php echo esc_attr__('Табела со резултати од регистарот. На тесен екран може да се движите хоризонтално.', 'zmp-competitions'); ?>"><table class="zmp-table zmp-register-table"><caption class="screen-reader-text"><?php echo esc_html__('Резултати од регистарот на наставници', 'zmp-competitions'); ?></caption><thead><tr><th scope="col"><?php echo esc_html__('Наставник', 'zmp-competitions'); ?></th><th scope="col"><?php echo esc_html__('Предмет', 'zmp-competitions'); ?></th><th scope="col"><?php echo esc_html__('Одделенија/години', 'zmp-competitions'); ?></th><th scope="col"><?php echo esc_html__('Училиште', 'zmp-competitions'); ?></th><th scope="col"><?php echo esc_html__('Општина', 'zmp-competitions'); ?></th><th scope="col"><?php echo esc_html__('Регион', 'zmp-competitions'); ?></th></tr></thead><tbody><?php foreach ($results['items'] as $row): ?><tr><td><?php echo esc_html($row['name']); ?></td><td><?php echo esc_html($row['subject']); ?></td><td><?php echo esc_html($row['grades']); ?></td><td><?php echo esc_html($row['school']); ?></td><td><?php echo esc_html($row['municipality']); ?></td><td><?php echo esc_html($row['region']); ?></td></tr><?php endforeach; ?></tbody></table></div>
                    <?php if ($results['pages'] > 1): ?><nav class="zmp-register-pagination" aria-label="<?php echo esc_attr__('Страници со резултати', 'zmp-competitions'); ?>"><?php for ($page_num = 1; $page_num <= $results['pages']; $page_num++): $pagination_args = array_merge($filters, array('zmp_register_page'=>$page_num)); if (isset($pagination_args['name'])) { $pagination_args['zmp_register_name'] = $pagination_args['name']; unset($pagination_args['name']); } if (isset($_GET['page_id']) && is_scalar($_GET['page_id']) && absint(wp_unslash($_GET['page_id'])) > 0) { $pagination_args['page_id'] = absint(wp_unslash($_GET['page_id'])); } if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) { $pagination_args['zmp_portal_tab'] = sanitize_key(wp_unslash($_GET['zmp_portal_tab'])); } if (isset($_GET['zmp_mentor_tab']) && is_scalar($_GET['zmp_mentor_tab'])) { $pagination_args['zmp_mentor_tab'] = sanitize_key(wp_unslash($_GET['zmp_mentor_tab'])); } $page_url = add_query_arg($pagination_args); ?><a class="zmp-btn <?php echo $page_num === $results['current_page'] ? 'zmp-btn-primary' : 'zmp-btn-secondary'; ?>" href="<?php echo esc_url($page_url); ?>" <?php echo $page_num === $results['current_page'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html((string) $page_num); ?></a><?php endfor; ?></nav><?php endif; ?>
                <?php else: ?><p class="zmp-register-empty" role="status"><?php echo esc_html__('Нема резултати за избраните услови.', 'zmp-competitions'); ?></p><?php endif; ?>
            </section>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Преземање на PDF за поединечен ученик или збирен PDF за сите ученици
     */
    public function handle_mentor_actions() {
        global $wpdb;
        $table_mentors  = $wpdb->prefix . 'zmp_mentors';
        $table_students = $wpdb->prefix . 'zmp_students';

        // 1. Преземање на PDF за поединечен ученик
        if (isset($_GET['zmp_action']) && $_GET['zmp_action'] === 'download_student_pdf' && isset($_GET['student_id'])) {
            $student_id = intval($_GET['student_id']);
            $current_user_id = get_current_user_id();

            if (!$current_user_id && !current_user_can('manage_options')) {
                wp_die('Немате овластување за преземање на овој документ.');
            }

            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id = %d", $current_user_id));
            if (!$mentor && !current_user_can('manage_options')) {
                wp_die('Менторот не е пронајден.');
            }

            if (current_user_can('manage_options')) {
                $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE id = %d", $student_id));
            } else {
                $student = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_students WHERE id = %d AND mentor_id = %d",
                    $student_id, $mentor->id
                ));
            }

            if (!$student) {
                wp_die('Ученикот не е пронајден или немате пристап.');
            }

            ZMP_PDF::render_envelope_pdf(array($student));
            exit;
        }

        // 2. Преземање на збирен PDF за сите ученици на менторот
        if (isset($_GET['zmp_action']) && $_GET['zmp_action'] === 'download_all_students_pdf') {
            $current_user_id = get_current_user_id();
            if (!$current_user_id && !current_user_can('manage_options')) {
                wp_die('Ве молиме најавете се.');
            }

            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id = %d", $current_user_id));
            if (!$mentor && !current_user_can('manage_options')) {
                wp_die('Менторот не е пронајден.');
            }

            if (current_user_can('manage_options') && isset($_GET['mentor_id'])) {
                $m_id = intval($_GET['mentor_id']);
                $students = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_students WHERE mentor_id = %d ORDER BY grade ASC, last_name ASC",
                    $m_id
                ));
            } else {
                $students = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_students WHERE mentor_id = %d ORDER BY grade ASC, last_name ASC",
                    $mentor->id
                ));
            }

            if (empty($students)) {
                wp_die('Немате пријавено ученици за печатење.');
            }

            ZMP_PDF::render_envelope_pdf($students);
            exit;
        }

        // 3. Обработка на POST формулари
        if (!isset($_POST['zmp_mentor_action'])) {
            return;
        }
        $posted_action = sanitize_key(wp_unslash($_POST['zmp_mentor_action']));
        ZMP_Debug::log('debug', 'Mentor action handler entered.', array('form_action' => $posted_action, 'post_fields_count' => count($_POST)));
        if (!in_array($posted_action, array('setup_mentor_profile', 'update_mentor_profile', 'add_mentor_school', 'add_student', 'add_complaint', 'send_message', 'mark_message_read'), true)) {
            ZMP_Debug::log('warning', 'Mentor action handler ignored unknown action.', array('form_action' => $posted_action));
        }

        // Поставување податоци за училиште на наставникот (ако прв пат се најавува)
        if (in_array($posted_action, array('setup_mentor_profile','update_mentor_profile'), true)) {
            ZMP_Debug::log('debug', 'Mentor profile setup flow started.');
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'zmp_mentor_setup_nonce')) {
                ZMP_Debug::log('error', 'Mentor profile setup stopped: nonce verification failed.');
                wp_die('Безбедносната проверка не помина. Освежете ја страницата и обидете се повторно.');
            }
            $current_user = wp_get_current_user();
            if (!$current_user->ID) {
                wp_die('Не сте најавени.');
            }
            $approved_subjects = class_exists('ZMP_Mentor_Subjects') ? ZMP_Mentor_Subjects::approved_subjects() : new WP_Error('schema_not_ready', 'Регистарот на предмети моментално не е достапен.');
            if (is_wp_error($approved_subjects)) {
                wp_die(esc_html__('Регистарот на предмети моментално не е достапен. Училишниот профил не е зачуван.', 'zmp-competitions'), 'Промените не се зачувани', array('response'=>503));
            }

            $full_name    = sanitize_text_field($_POST['full_name']);
            $phone        = sanitize_text_field($_POST['phone']);
            $school_name  = sanitize_text_field($_POST['school_name']);
            $municipality = sanitize_text_field($_POST['municipality']);
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id = %d", $current_user->ID));
            $auto_region = ZMP_Regions::region_for_municipality($municipality);
            // A teacher cannot bypass moderator approval by changing the municipality
            // field, which would otherwise silently change the profile region.
            if ($existing && $existing->municipality !== $municipality && $auto_region !== '' && $auto_region !== $existing->region) {
                wp_die('Новата општина припаѓа во друг регион. Прво испратете барање за промена на регион преку допис и почекајте одлука од модераторите.');
            }
            $region = $existing && $existing->municipality === $municipality && ZMP_Regions::valid_region($existing->region) ? $existing->region : $auto_region;
            if ($region === '') { wp_die('Не можеме да го утврдиме регионот од избраната општина. Проверете ја општината.'); }
            if ($existing) {
                $wpdb->update($table_mentors, array(
                    'full_name'    => $full_name,
                    'phone'        => $phone,
                    'school_name'  => $school_name,
                    'municipality' => $municipality,
                    'region'       => $region
                ), array('id' => $existing->id));
            } else {
                $wpdb->insert($table_mentors, array(
                    'wp_user_id'   => $current_user->ID,
                    'full_name'    => $full_name,
                    'email'        => $current_user->user_email,
                    'phone'        => $phone,
                    'school_name'  => $school_name,
                    'municipality' => $municipality,
                    'region'       => $region,
                    'login_token'  => wp_generate_password(32, false)
                ));
            }

            // Always store data in user meta for quick lookup (both insert and update)
            update_user_meta($current_user->ID, 'zmp_school_name', $school_name);
            update_user_meta($current_user->ID, 'zmp_municipality', $municipality);
            update_user_meta($current_user->ID, 'zmp_region', $region);
            update_user_meta($current_user->ID, 'zmp_phone', $phone);
            $saved_mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id=%d", $current_user->ID));
            ZMP_Mentor_Schools::ensure_primary($saved_mentor);

            wp_redirect(remove_query_arg(array('setup')));
            exit;
        }

        if ($posted_action === 'add_mentor_school') {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'zmp_add_mentor_school')) { wp_die('Безбедносната проверка не помина.', '', array('response'=>403)); }
            $current_user = wp_get_current_user();
            if (!$current_user->ID || !(current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard') || in_array('zmp_teacher',(array)$current_user->roles,true) || current_user_can('manage_options'))) { wp_die('Немате дозвола да уредувате училишта.', '', array('response'=>403)); }
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id=%d", $current_user->ID));
            if (!$mentor) { wp_die('Прво зачувајте го профилот на наставникот.', '', array('response'=>400)); }
            $approved_subjects = class_exists('ZMP_Mentor_Subjects') ? ZMP_Mentor_Subjects::approved_subjects() : new WP_Error('schema_not_ready', 'Регистарот на предмети моментално не е достапен.');
            if (is_wp_error($approved_subjects)) { wp_die(esc_html__('Регистарот на предмети моментално не е достапен. Училиштето не е зачувано.', 'zmp-competitions'), 'Промените не се зачувани', array('response'=>503)); }
            $result = ZMP_Mentor_Schools::add_school((int)$mentor->id, wp_unslash($_POST['school_name'] ?? ''), wp_unslash($_POST['municipality'] ?? ''));
            $message = is_wp_error($result) ? $result->get_error_code() : 'school_added';
            wp_safe_redirect(add_query_arg(array('tab'=>'schools','msg'=>$message), remove_query_arg('msg')));
            exit;
        }

        if ($posted_action === 'send_message') {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'zmp_send_message_nonce')) { wp_die('Безбедносната проверка не помина.', '', array('response'=>403)); }
            $subject=sanitize_text_field(wp_unslash($_POST['subject']??''));$message=sanitize_textarea_field(wp_unslash($_POST['message']??''));$requested=isset($_POST['recipients'])&&is_array($_POST['recipients'])?array_map('sanitize_key',wp_unslash($_POST['recipients'])):array();
            if(mb_strlen($subject)<3||mb_strlen($message)<10)wp_die('Внесете тема и порака.','Невалидна порака',array('response'=>400));
            $role_map=array('moderators'=>'zmp_moderator','commission'=>'zmp_commission');$users=array();foreach(array_unique($requested) as $key){if(isset($role_map[$key]))$users=array_merge($users,get_users(array('role'=>$role_map[$key],'fields'=>array('ID'))));}
            $ids=array_values(array_unique(array_map(function($u){return (int)$u->ID;},$users)));if(!$ids)wp_die('Изберете најмалку еден примач.','Невалидна порака',array('response'=>400));
            $wpdb->query('START TRANSACTION');foreach($ids as $recipient_id){if(!$wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>get_current_user_id(),'sender_role'=>'zmp_teacher','recipient_id'=>$recipient_id,'competition_id'=>0,'subject'=>$subject,'message'=>$message,'status'=>'unread','created_at'=>current_time('mysql')))){$wpdb->query('ROLLBACK');wp_die('Пораката не можеше да се зачува.','Грешка',array('response'=>500));}}
            $wpdb->query('COMMIT');wp_safe_redirect(add_query_arg(array('tab'=>'messages','msg'=>'message_sent'),remove_query_arg(array('msg','new_student'))));exit;
        }
        if ($posted_action === 'mark_message_read') {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'zmp_teacher_message_read')) { wp_die('Безбедносната проверка не помина.', '', array('response'=>403)); }
            $message_id=absint($_POST['message_id']??0);$wpdb->update($wpdb->prefix.'zmp_internal_messages',array('status'=>'read'),array('id'=>$message_id,'recipient_id'=>get_current_user_id(),'status'=>'unread'),array('%s'),array('%d','%d','%s'));
            wp_safe_redirect(add_query_arg(array('tab'=>'messages','msg'=>'message_read'),remove_query_arg(array('msg'))));exit;
        }

        // Додавање нов ученик од наставникот
        if ($posted_action === 'add_student') {
            ZMP_Debug::log('info', 'Student registration request received.');
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'zmp_add_student_nonce')) {
                ZMP_Debug::log('error', 'Student registration stopped: nonce verification failed.', array('nonce_field_present' => isset($_POST['_wpnonce']) ? 1 : 0));
                wp_die('Безбедносната проверка не помина. Освежете ја страницата и обидете се повторно.');
            }
            ZMP_Debug::log('debug', 'Student registration nonce verified.');

            $current_user = wp_get_current_user();
            if (!$current_user->ID) {
                ZMP_Debug::log('error', 'Student registration stopped: current user is not authenticated.');
                wp_die('Не сте најавени.');
            }
            ZMP_Debug::log('debug', 'Student registration user identified.', array('user_id' => (int) $current_user->ID, 'role_count' => count((array) $current_user->roles), 'is_admin' => current_user_can('manage_options') ? 1 : 0));

            $is_teacher = current_user_can('zmp_teacher_cap')
                || current_user_can('view_zmp_teacher_dashboard')
                || in_array('zmp_teacher', (array) $current_user->roles, true)
                || current_user_can('manage_options');
            ZMP_Debug::log('debug', 'Student registration teacher capability checked.', array('is_teacher' => $is_teacher ? 1 : 0));
            if (!$is_teacher) {
                ZMP_Debug::log('error', 'Student registration stopped: user lacks teacher capability.', array('user_id' => (int) $current_user->ID));
                wp_die('Немате привилегија на наставник.');
            }

            ZMP_Debug::log('debug', 'Looking up mentor profile.', array('user_id' => (int) $current_user->ID, 'mentor_table' => $table_mentors));
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id = %d", $current_user->ID));

            ZMP_Debug::log('debug', 'Mentor profile lookup completed.', array('profile_found' => $mentor ? 1 : 0, 'db_error_present' => $wpdb->last_error ? 1 : 0));
            if (!$mentor) {
                ZMP_Debug::log('error', 'Student registration stopped: mentor profile not found.', array('user_id' => $current_user->ID));
                wp_die('Прво пополнете ги податоците за вашето училиште.');
            }
            ZMP_Mentor_Schools::ensure_primary($mentor);
            $mentor_school_id = isset($_POST['mentor_school_id']) && is_scalar($_POST['mentor_school_id']) ? absint(wp_unslash($_POST['mentor_school_id'])) : 0;
            $student_school = $mentor_school_id ? ZMP_Mentor_Schools::owned_school((int)$mentor->id, $mentor_school_id) : null;
            if (!$mentor_school_id) { $owned_schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id); if (count((array)$owned_schools) === 1) { $student_school = $owned_schools[0]; } }
            if (!$student_school) {
                ZMP_Debug::log('warning', 'Student registration stopped: no school selected from teacher profile.', array('mentor_id'=>(int)$mentor->id,'user_id'=>(int)$current_user->ID));
                wp_safe_redirect(add_query_arg(array('tab'=>'categories','msg'=>'school_required'), remove_query_arg('new_student')));
                exit;
            }

            ZMP_Debug::log('debug', 'Validating submitted student fields.', array('has_first_name' => !empty($_POST['first_name']) ? 1 : 0, 'has_last_name' => !empty($_POST['last_name']) ? 1 : 0, 'has_grade' => isset($_POST['grade']) ? 1 : 0, 'has_language' => isset($_POST['language']) ? 1 : 0));
            $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
            $last_name  = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
            $grade      = isset($_POST['grade']) ? intval($_POST['grade']) : 0;
            $language   = isset($_POST['language']) ? sanitize_text_field(wp_unslash($_POST['language'])) : '';

            if (empty($first_name) || empty($last_name) || !in_array($grade, array(6, 7, 8, 9))) {
                ZMP_Debug::log('warning', 'Student registration stopped: validation failed.', array('user_id' => $current_user->ID, 'grade' => $grade));
                wp_die('Невалидни податоци за ученикот.');
            }

            // The submitted school is selected from the teacher's owned school list.

            ZMP_Debug::log('debug', 'Mentor school and municipality verified.');
            $grade_roman = array(6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX');
            $prefixes = array('Muzika', 'Noti', 'Ritam', 'Melodija', 'Harmonija', 'Solo', 'Takt');
            $access_pin = $prefixes[array_rand($prefixes)] . '-' . wp_rand(100, 999);
            ZMP_Debug::log('debug', 'Generated temporary PIN; attempting encryption.');
            $encrypted_pin = ZMP_Credentials::encrypt_pin($access_pin);
            if ($encrypted_pin === false) {
                ZMP_Debug::log('error', 'PIN encryption failed during student registration.', array('user_id' => $current_user->ID));
                wp_safe_redirect(add_query_arg('msg', 'student_save_failed', remove_query_arg('new_student')));
                exit;
            }

            // Match the admin's source of truth (is_active). Older installations may
            // not yet have workflow_status, or may contain legacy status values.
            $competition_table = $wpdb->prefix . 'zmp_competitions';
            ZMP_Debug::log('debug', 'Looking up active competition.', array('competition_table' => $competition_table));
            $competition = $wpdb->get_row("SELECT * FROM $competition_table WHERE is_active=1 ORDER BY id DESC LIMIT 1");
            ZMP_Debug::log('debug', 'Active competition query completed.', array('competition_found' => $competition ? 1 : 0, 'db_error_present' => $wpdb->last_error ? 1 : 0));
            if ($wpdb->last_error) { ZMP_Debug::log('error', 'Active competition query returned a database error.', array('db_error' => (string) $wpdb->last_error)); }
            $active_competition_id = $competition ? absint($competition->id) : 0;
            if ($competition && isset($competition->workflow_status)
                && in_array((string) $competition->workflow_status, array('completed', 'ready_to_complete'), true)) {
                $active_competition_id = 0;
            }
            if (!$active_competition_id) {
                ZMP_Debug::log('warning', 'Student registration stopped: no active competition found.', array('user_id' => $current_user->ID));
                wp_safe_redirect(add_query_arg('msg', 'no_active_competition', remove_query_arg('new_student')));
                exit;
            }

            $requirements_season=ZMP_Category_Registrations::active_season();
            $knowledge_category_id=(int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' AND is_active=1 LIMIT 1");
            if(!$requirements_season||!$knowledge_category_id){wp_safe_redirect(add_query_arg('zmp_requirements_status','requirements_unavailable',remove_query_arg('new_student')));exit;}
            if (!ZMP_Registration_Windows::allows((int)$requirements_season->id, $knowledge_category_id)) { wp_safe_redirect(add_query_arg('msg','registration_closed',remove_query_arg('new_student'))); exit; }
            $student_documents=ZMP_Registration_Requirements::receive_entry_documents((int)$requirements_season->id,$knowledge_category_id);
            if(is_wp_error($student_documents)){wp_safe_redirect(add_query_arg('zmp_requirements_status',$student_documents->get_error_code(),remove_query_arg('new_student')));exit;}
            $student_payer=sanitize_key(wp_unslash($_POST['payer_type']??''));
            if(!in_array($student_payer,array('teacher','school'),true)){ZMP_Registration_Requirements::remove_entry_document_files(array_values(array_filter(array($student_documents['application']??null,$student_documents['proof']??null,$student_documents['consent']??null))));wp_safe_redirect(add_query_arg('msg','student_save_failed',remove_query_arg('new_student')));exit;}

            // The unique index in the database is the final protection against a
            // collision under concurrent registrations. Retry duplicate-code errors.
            $new_student_id = 0;
            $inserted = false;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $access_code = 'ZMP-' . $grade_roman[$grade] . '-' . wp_rand(1000, 9999);
                ZMP_Debug::log('debug', 'Attempting student database insert.', array('attempt' => $attempt + 1, 'students_table' => $table_students, 'mentor_id' => (int) $mentor->id, 'competition_id' => (int) $active_competition_id, 'grade' => $grade));
                $inserted = $wpdb->insert($table_students, array(
                    'mentor_id'    => (int) $mentor->id,
                    'mentor_school_id' => (int) $student_school->id,
                    'competition_id' => $active_competition_id,
                    'first_name'   => $first_name,
                    'last_name'    => $last_name,
                    'grade'        => $grade,
                    'language'     => ($language === 'sq') ? 'sq' : 'mk',
                    'school_name'  => $student_school->school_name,
                    'municipality' => $student_school->municipality,
                    'region'       => $student_school->region,
                    'access_code'  => $access_code,
                    'access_pin'   => $encrypted_pin,
                    'access_pin_hash' => wp_hash_password($access_pin),
                    'current_phase'=> 'school',
                    'qualified_for_next' => 0
                ));

                if ($inserted !== false) {
                    ZMP_Debug::log('debug', 'Student database insert returned success.', array('attempt' => $attempt + 1, 'rows_affected' => (int) $inserted, 'insert_id_present' => $wpdb->insert_id ? 1 : 0));
                    $new_student_id = (int) $wpdb->insert_id;
                    break;
                }

                // Only retry a unique-code collision. Other SQL errors are not
                // recoverable by generating another code.
                if (stripos((string) $wpdb->last_error, 'duplicate') === false) {
                    ZMP_Debug::log('error', 'Student database insert failed.', array('mentor_id' => (int) $mentor->id, 'competition_id' => (int) $active_competition_id, 'db_error' => (string) $wpdb->last_error, 'attempt' => $attempt + 1));
                    break;
                }
                ZMP_Debug::log('warning', 'Student access-code collision; retrying insert.', array('mentor_id' => (int) $mentor->id, 'competition_id' => (int) $active_competition_id, 'attempt' => $attempt + 1));
            }

            if (!$inserted || !$new_student_id) {
                ZMP_Registration_Requirements::remove_entry_document_files(array_values(array_filter(array($student_documents['application']??null,$student_documents['proof']??null,$student_documents['consent']??null))));
                ZMP_Debug::log('error', 'Student registration failed after database insert attempts.', array('mentor_id' => (int) $mentor->id, 'competition_id' => (int) $active_competition_id, 'db_error' => (string) $wpdb->last_error));
                wp_safe_redirect(add_query_arg('msg', 'student_save_failed', remove_query_arg('new_student')));
                exit;
            }

            if(!ZMP_Registration_Requirements::save_student_documents((int)$requirements_season->id,$new_student_id,$student_documents,$student_payer)){$wpdb->delete($table_students,array('id'=>$new_student_id));wp_safe_redirect(add_query_arg('zmp_requirements_status','save_error',remove_query_arg('new_student')));exit;}

            ZMP_Communications::notify_teacher((int)$current_user->ID,'Примена пријава за тест на знаење','Пријавата за ученикот/ученичката „'.$first_name.' '.$last_name.'“ е примена за учебната година '.$competition->academic_year.'.',$competition->academic_year,0,'school');

            // PRG redirect: the next page fetches the saved row and displays the
            // one-time credentials plus a printable/PDF card link.
            ZMP_Debug::log('info', 'Student registration succeeded; redirecting to result page.', array('student_id' => $new_student_id, 'mentor_id' => (int) $mentor->id, 'competition_id' => (int) $active_competition_id, 'grade' => $grade));
            $redirect_url = add_query_arg(array(
                'msg' => 'student_added',
                'new_student' => $new_student_id,
                'tab' => 'categories'
            ));
            ZMP_Debug::log('debug', 'Student registration redirect prepared.', array('student_id' => $new_student_id, 'redirect_has_result_flag' => 1));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Рендерирање на менторскиот портал [zmp_mentor_portal]
     */
    public function render_mentor_portal($atts) {
        global $wpdb;
        $table_mentors  = $wpdb->prefix . 'zmp_mentors';
        $table_students = $wpdb->prefix . 'zmp_students';

        // 1. Проверка дали корисникот е најавен на веб-страницата muzika.org.mk
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        $current_user = wp_get_current_user();
        $is_teacher = current_user_can('zmp_teacher_cap')
            || current_user_can('view_zmp_teacher_dashboard')
            || in_array('zmp_teacher', (array) $current_user->roles, true)
            || current_user_can('manage_options');

        // 2. Проверка дали има доделено привилегија наставник
        if (!$is_teacher) {
            return $this->render_pending_approval($current_user);
        }

        // 3. Корисникот е одобрен наставник - преземи го неговиот менторски запис
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE wp_user_id = %d", $current_user->ID));


        // Автоматско повлекување од корисничкиот профил ако постојат мета податоци
        $meta_school = get_user_meta($current_user->ID, 'zmp_school_name', true);
        $meta_mun    = get_user_meta($current_user->ID, 'zmp_municipality', true);
        $meta_phone  = get_user_meta($current_user->ID, 'zmp_phone', true);

        if (!$mentor && (!empty($meta_school) || !empty($meta_mun))) {
            $wpdb->insert($table_mentors, array(
                'wp_user_id'   => $current_user->ID,
                'full_name'    => $current_user->display_name,
                'email'        => $current_user->user_email,
                'phone'        => $meta_phone ? $meta_phone : '',
                'school_name'  => $meta_school ? $meta_school : '',
                'municipality' => $meta_mun ? $meta_mun : '',
                'region'       => get_user_meta($current_user->ID, 'zmp_region', true) ?: ZMP_Regions::region_for_municipality($meta_mun),
                'login_token'  => wp_generate_password(32, false)
            ));
            $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_mentors WHERE id = %d", $wpdb->insert_id));
        }

        // Populate missing fields from user meta if available
        if ($mentor) {
            if (empty($mentor->school_name) && !empty($meta_school)) {
                $mentor->school_name = $meta_school;
                // Also update DB to keep in sync
                $wpdb->update($table_mentors, ['school_name' => $meta_school], ['id' => $mentor->id]);
            }
            if (empty($mentor->municipality) && !empty($meta_mun)) {
                $mentor->municipality = $meta_mun;
                $wpdb->update($table_mentors, ['municipality' => $meta_mun], ['id' => $mentor->id]);
            }
            if (empty($mentor->region)) {
                $mentor->region = get_user_meta($current_user->ID, 'zmp_region', true);
                if (!ZMP_Regions::valid_region($mentor->region)) { $mentor->region = ZMP_Regions::region_for_municipality($mentor->municipality); }
                if ($mentor->region !== '') { $wpdb->update($table_mentors, array('region'=>$mentor->region), array('id'=>$mentor->id)); }
            }
            if (empty($mentor->phone) && !empty($meta_phone)) {
                $mentor->phone = $meta_phone;
                $wpdb->update($table_mentors, ['phone' => $meta_phone], ['id' => $mentor->id]);
            }
        }

        // The registration handler redirects with the new student ID. Load it
        // before the profile-completeness gate so the teacher can retrieve the
        // student's credentials even when the profile still needs a
        // phone number. The ownership condition prevents IDOR disclosure.
        $new_student = null;
        if ($mentor && isset($_GET['msg'], $_GET['new_student'])
            && is_scalar($_GET['msg']) && is_scalar($_GET['new_student'])
            && sanitize_key((string) wp_unslash($_GET['msg'])) === 'student_added') {
            $new_student_id = absint(wp_unslash($_GET['new_student']));
            if ($new_student_id > 0) {
                $new_student = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_students WHERE id = %d AND mentor_id = %d",
                    $new_student_id,
                    (int) $mentor->id
                ));
                if ($new_student) {
                    $new_student->access_pin = ZMP_Credentials::reveal_pin($new_student->access_pin);
                }
            }
        }

        // Ако сепак нема внесен профил за училиште, општина и телефон - прикажи брз формулар
        $has_complete_profile = $mentor
            && !empty($mentor->school_name)
            && !empty($mentor->municipality)
            && !empty($mentor->region)
            && !empty($mentor->phone);

        if (!$has_complete_profile) {
            if ($new_student) {
                return $this->render_student_registration_success($new_student)
                    . $this->render_profile_setup($current_user, $mentor, true);
            }
            return $this->render_profile_setup($current_user, $mentor);
        }

        // Пријавени ученици на овој наставник
        $student_region = isset($_GET['zmp_student_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_student_region'])) ? sanitize_key(wp_unslash($_GET['zmp_student_region'])) : '';
        $student_municipality = sanitize_text_field(wp_unslash($_GET['zmp_student_municipality'] ?? ''));
        $student_school = sanitize_text_field(wp_unslash($_GET['zmp_student_school'] ?? ''));
        $students_sql = "SELECT * FROM $table_students WHERE mentor_id=%d";
        $students_args = array((int)$mentor->id);
        if ($student_region !== '') { $students_sql .= ' AND region=%s'; $students_args[]=$student_region; }
        if ($student_municipality !== '') { $students_sql .= ' AND municipality=%s'; $students_args[]=$student_municipality; }
        if ($student_school !== '') { $students_sql .= ' AND school_name LIKE %s'; $students_args[]='%' . $wpdb->esc_like($student_school) . '%'; }
        $students = $wpdb->get_results($wpdb->prepare($students_sql . ' ORDER BY grade ASC,last_name ASC',$students_args));

        // Dedicated mentor submenu combines live current-season scores and immutable snapshots from prior years.
        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $my_results = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id,0 AS phase_result_id,a.phase,a.grade,a.completed_at,a.total_points,a.award_level,a.qualified_next_stage,s.first_name,s.last_name,s.access_code,s.region,s.municipality,s.school_name,c.academic_year
             FROM $table_attempts a JOIN $table_students s ON s.id=a.student_id JOIN {$wpdb->prefix}zmp_competitions c ON c.id=a.competition_id
             WHERE s.mentor_id=%d AND a.status='completed' AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}zmp_phase_results pr WHERE pr.attempt_id=a.id)
             UNION ALL
             SELECT p.attempt_id AS id,p.id AS phase_result_id,p.phase,p.grade,p.completed_at,p.total_points,p.award_level,p.qualified_next_stage,p.first_name,p.last_name,p.access_code,p.region,p.municipality,p.school_name,p.academic_year
             FROM {$wpdb->prefix}zmp_phase_results p WHERE p.mentor_user_id=%d
             ORDER BY completed_at DESC",
            $mentor->id, $current_user->ID
        ));
        $mentor_result_region = isset($_GET['zmp_mentor_result_region']) && ZMP_Regions::valid_region(wp_unslash($_GET['zmp_mentor_result_region'])) ? sanitize_key(wp_unslash($_GET['zmp_mentor_result_region'])) : '';
        $mentor_result_municipality = sanitize_text_field(wp_unslash($_GET['zmp_mentor_result_municipality'] ?? ''));
        $mentor_result_school = sanitize_text_field(wp_unslash($_GET['zmp_mentor_result_school'] ?? ''));
        $my_results = array_values(array_filter((array)$my_results, function($row) use ($mentor_result_region,$mentor_result_municipality,$mentor_result_school) {
            return ($mentor_result_region === '' || $row->region === $mentor_result_region)
                && ($mentor_result_municipality === '' || $row->municipality === $mentor_result_municipality)
                && ($mentor_result_school === '' || stripos($row->school_name, $mentor_result_school) !== false);
        }));

        // Добиени жалби од овој наставник
        $table_complaints = $wpdb->prefix . 'zmp_complaints';
        $my_complaints = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_complaints WHERE mentor_user_id = %d ORDER BY created_at DESC",
            $current_user->ID
        ));

        $active_tab = isset($_GET['zmp_mentor_tab']) && is_scalar($_GET['zmp_mentor_tab'])
            ? sanitize_key(wp_unslash($_GET['zmp_mentor_tab']))
            : (isset($_GET['tab']) && is_scalar($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'students');
        $legacy_schools_tab = $active_tab === 'schools' || (isset($_GET['tab']) && is_scalar($_GET['tab']) && sanitize_key(wp_unslash($_GET['tab'])) === 'schools');
        if ($legacy_schools_tab) { $active_tab = 'profile'; }
        $mentor_tabs = array('students','categories','matrices','messages','complaints','results','profile');
        if (!in_array($active_tab, $mentor_tabs, true)) { $active_tab = 'students'; }
        $mentor_tabs_url = remove_query_arg(array('tab', 'zmp_mentor_tab', 'msg', 'new_student'), get_permalink() ?: home_url('/'));
        if (isset($_GET['zmp_portal_tab']) && is_scalar($_GET['zmp_portal_tab'])) {
            $mentor_tabs_url = add_query_arg('zmp_portal_tab', sanitize_key(wp_unslash($_GET['zmp_portal_tab'])), $mentor_tabs_url);
        }
        $unread_teacher_messages = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_internal_messages WHERE recipient_id=%d AND status='unread'", (int)$current_user->ID));

        ob_start();
        ?>
        <div class="zmp-container">
            <!-- Заглавие на наставникот -->
            <div class="zmp-mentor-header">
                <div class="zmp-mentor-info">
                    <h3>🎵 Наставник: <?php echo esc_html($mentor->full_name); ?></h3>
                    <p>
                        <strong>Училиште:</strong> <?php echo esc_html($mentor->school_name); ?> | 
                        <strong>Општина:</strong> <?php echo esc_html($mentor->municipality); ?> | <strong>Регион:</strong> <?php echo esc_html(ZMP_Regions::label($mentor->region)); ?> | 
                        <strong>Контакт:</strong> <?php echo esc_html($mentor->phone); ?>
                    </p>
                </div>
                <div>
                    <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="zmp-btn zmp-btn-secondary">
                        Одјава (<?php echo esc_html($current_user->user_login); ?>)
                    </a>
                </div>
            </div>

            <!-- Навигациски табови -->
<nav class="zmp-workspace__nav zmp-tabs-bar" aria-label="Портал за наставници">
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'students', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='students'?'aria-current="page"':''; ?>>
        📋 Мои пријави и ученици
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'categories', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='categories'?'aria-current="page"':''; ?>>
        📝 Нови пријави
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'matrices', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='matrices'?'aria-current="page"':''; ?>>
        🎼 Матрици
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'messages', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='messages'?'aria-current="page"':''; ?> data-notification-count="<?php echo (int)$unread_teacher_messages; ?>">
        📧 Дописи<?php if($unread_teacher_messages): ?> <span class="zmp-workspace__badge"><?php echo esc_html($unread_teacher_messages); ?></span><?php endif; ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'complaints', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='complaints'?'aria-current="page"':''; ?>>
        ⚖️ Жалби и приговори (рок: 24ч)
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'results', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='results'?'aria-current="page"':''; ?>>
        🏆 Резултати на моите ученици (<?php echo count($my_results); ?>)
    </a>
    <a href="<?php echo esc_url(add_query_arg('zmp_mentor_tab', 'profile', $mentor_tabs_url)); ?>" class="zmp-workspace__tab" <?php echo $active_tab==='profile'?'aria-current="page"':''; ?>>👤 Профил</a>
</nav>

            <?php
// Show status alerts
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'message_sent':
            echo '<div class="zmp-alert zmp-alert-success" style="margin-bottom:20px;">Пораката е испратена.</div>';
            break;
        case 'message_read':
            echo '<div class="zmp-alert zmp-alert-success" style="margin-bottom:20px;">Пораката е означена како прочитана.</div>';
            break;
        case 'school_added':
            echo '<div class="zmp-alert zmp-alert-success">Училиштето е додадено.</div>';
            break;
        case 'school_exists':
            echo '<div class="zmp-alert">Ова училиште веќе е во профилот.</div>';
            break;
        case 'school_required':
            echo '<div class="zmp-alert zmp-alert-warning">Изберете училиште од профилот пред да ја поднесете пријавата.</div>';
            break;
        case 'complaint_added':
            echo '<div class="zmp-alert zmp-alert-success" style="margin-bottom:20px;">Жалбата е поднесена.</div>';
            break;
        case 'student_save_failed':
            echo '<div class="zmp-alert zmp-alert-danger" role="alert" style="margin-bottom:20px;">Ученикот не е зачуван во базата. Нема генерирано успешна пријава. Проверете ги податоците или контактирајте го администраторот.</div>';
            break;
        case 'mentor_profile_incomplete':
            echo '<div class="zmp-alert zmp-alert-warning" role="alert" style="margin-bottom:20px;">Во профилот на наставникот недостасува училиште или општина. Ажурирајте го профилот пред пријавување ученик.</div>';
            break;
        case 'no_active_competition':
            echo '<div class="zmp-alert zmp-alert-warning" role="alert" style="margin-bottom:20px;">Во моментов нема активна натпреварувачка година. Ученикот не е пријавен.</div>';
            break;
        case 'registration_closed':
            echo '<div class="zmp-alert zmp-alert-warning" role="alert" style="margin-bottom:20px;">Рокот за пријавување на тестот на знаење е затворен или сè уште не е отворен. Ученикот не е пријавен.</div>';
            break;
    }
}
?>
<?php if ($active_tab === 'profile'): ?>
                <section class="zmp-profile-overview" aria-labelledby="zmp-profile-heading">
                    <h3 id="zmp-profile-heading">Профил и мои училишта</h3>
                    <?php echo $this->render_profile_setup($current_user, $mentor); ?>
                    <details class="zmp-profile-schools" <?php echo $legacy_schools_tab ? 'open' : ''; ?>>
                        <summary>🏫 Мои училишта и региони</summary>
                        <?php echo $this->render_mentor_schools_panel($mentor); ?>
                    </details>
                </section>
            <?php elseif ($active_tab === 'categories'): ?>
                <?php if ($new_student) echo $this->render_student_registration_success($new_student); ?>
                <?php echo $this->render_registration_hub($mentor); ?>
            <?php elseif ($active_tab === 'matrices'): ?>
                <?php
                $approved_audio_tools = class_exists('ZMP_Approved_Audio_Tools')
                    ? (array) ZMP_Approved_Audio_Tools::teacher_visible_tools()
                    : array();
                $audio_tool_groups = array(
                    'vocal_remover' => array(
                        'label' => esc_html__('Отстранување вокал', 'zmp-competitions'),
                        'tools' => array(),
                    ),
                    'key_change' => array(
                        'label' => esc_html__('Промена на тоналитет', 'zmp-competitions'),
                        'tools' => array(),
                    ),
                );
                foreach ($approved_audio_tools as $audio_tool) {
                    if (!is_array($audio_tool) || !isset($audio_tool_groups[$audio_tool['category'] ?? ''])) {
                        continue;
                    }
                    $audio_tool_name = sanitize_text_field((string) ($audio_tool['name'] ?? ''));
                    $audio_tool_url = trim((string) ($audio_tool['url'] ?? ''));
                    $audio_tool_url_parts = wp_parse_url($audio_tool_url);
                    if ($audio_tool_name === '' || !is_array($audio_tool_url_parts) || strtolower((string) ($audio_tool_url_parts['scheme'] ?? '')) !== 'https' || empty($audio_tool_url_parts['host'])) {
                        continue;
                    }
                    $audio_tool['_display_name'] = $audio_tool_name;
                    $audio_tool_groups[$audio_tool['category']]['tools'][] = $audio_tool;
                }
                ?>
                <section class="zmp-approved-tools" aria-labelledby="zmp-approved-tools-heading">
                    <header class="zmp-approved-tools__intro">
                        <h3 id="zmp-approved-tools-heading"><?php echo esc_html__('Матрици и алатки за аудио', 'zmp-competitions'); ?></h3>
                        <p><?php echo esc_html__('Изберете од рачно проверените надворешни алатки за отстранување вокал или промена на тоналитет.', 'zmp-competitions'); ?></p>
                    </header>
                    <aside class="zmp-approved-tools__rights" role="note">
                        <strong><?php echo esc_html__('Пред да користите снимка', 'zmp-competitions'); ?></strong>
                        <p><?php echo esc_html__('Користете аудио само ако имате право или дозвола за него. Плагинот не испраќа аудио до овие сервиси; пред да поставите снимка, проверете ги нивните правила за приватност и користење. Изјавите за обработка се објавени од сервисите и не претставуваат независна сертификација.', 'zmp-competitions'); ?></p>
                    </aside>
                    <?php
                    $visible_audio_tool_count = 0;
                    foreach ($audio_tool_groups as $group_key => $group) :
                        if (empty($group['tools'])) {
                            continue;
                        }
                        $visible_audio_tool_count += count($group['tools']);
                        ?>
                        <section class="zmp-approved-tools__group" aria-labelledby="zmp-approved-tools-<?php echo esc_attr($group_key); ?>">
                            <h4 id="zmp-approved-tools-<?php echo esc_attr($group_key); ?>"><?php echo esc_html($group['label']); ?></h4>
                            <div class="zmp-approved-tools__grid">
                                <?php foreach ($group['tools'] as $tool_index => $audio_tool) :
                                    $tool_id = 'zmp-approved-audio-tool-' . sanitize_html_class($group_key . '-' . (string) $tool_index);
                                    $privacy_url = trim((string) ($audio_tool['privacy_url'] ?? ''));
                                    $privacy_parts = wp_parse_url($privacy_url);
                                    $has_privacy_url = is_array($privacy_parts) && strtolower((string) ($privacy_parts['scheme'] ?? '')) === 'https' && !empty($privacy_parts['host']);
                                    $terms_url = trim((string) ($audio_tool['terms_url'] ?? ''));
                                    $terms_parts = wp_parse_url($terms_url);
                                    $has_terms_url = is_array($terms_parts) && strtolower((string) ($terms_parts['scheme'] ?? '')) === 'https' && !empty($terms_parts['host']);
                                    ?>
                                    <article class="zmp-approved-tools__card" aria-labelledby="<?php echo esc_attr($tool_id); ?>">
                                        <div class="zmp-approved-tools__card-heading">
                                            <h5 id="<?php echo esc_attr($tool_id); ?>"><?php echo esc_html($audio_tool['_display_name']); ?></h5>
                                            <span class="zmp-approved-tools__badge"><?php echo esc_html__('Рачно одобрено', 'zmp-competitions'); ?></span>
                                        </div>
                                        <p class="zmp-approved-tools__category"><?php echo esc_html($group['label']); ?></p>
                                        <?php if (!empty($audio_tool['free_limits'])) : ?>
                                            <p><strong><?php echo esc_html__('Бесплатни ограничувања:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['free_limits']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($audio_tool['duration_format_limits'])) : ?>
                                            <p><strong><?php echo esc_html__('Формат и должина:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['duration_format_limits']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($audio_tool['quality_notes'])) : ?>
                                            <p><strong><?php echo esc_html__('Квалитет:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['quality_notes']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($audio_tool['processing_claim'])) : ?>
                                            <p><strong><?php echo esc_html__('Обработка според сервисот:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['processing_claim']); ?> <span class="zmp-muted"><?php echo esc_html__('(изјава на сервисот, не независна потврда)', 'zmp-competitions'); ?></span></p>
                                        <?php endif; ?>
                                        <?php if (!empty($audio_tool['rights_note'])) : ?>
                                            <p class="zmp-approved-tools__rights-note"><strong><?php echo esc_html__('Права:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['rights_note']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($audio_tool['manual_checked_at'])) : ?>
                                            <p class="zmp-muted"><strong><?php echo esc_html__('Последна рачна проверка:', 'zmp-competitions'); ?></strong> <?php echo esc_html((string) $audio_tool['manual_checked_at']); ?></p>
                                        <?php endif; ?>
                                        <p class="zmp-approved-tools__links">
                                            <a class="zmp-btn zmp-btn-primary" href="<?php echo esc_url($audio_tool['url']); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html__('Отвори ја надворешната алатка', 'zmp-competitions'); ?> <span aria-hidden="true">↗</span></a>
                                            <?php if ($has_privacy_url) : ?>
                                                <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html__('Приватност', 'zmp-competitions'); ?><span class="screen-reader-text">: <?php echo esc_html($audio_tool['_display_name']); ?></span></a>
                                            <?php endif; ?>
                                            <?php if ($has_terms_url) : ?>
                                                <a href="<?php echo esc_url($terms_url); ?>" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html__('Услови за користење', 'zmp-competitions'); ?><span class="screen-reader-text">: <?php echo esc_html($audio_tool['_display_name']); ?></span></a>
                                            <?php endif; ?>
                                        </p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                    <?php if ($visible_audio_tool_count === 0) : ?>
                        <div class="zmp-approved-tools__empty" role="status">
                            <h4><?php echo esc_html__('Нема одобрени алатки во моментов', 'zmp-competitions'); ?></h4>
                            <p><?php echo esc_html__('Кога администраторот ќе одобри и провери алатка, таа ќе се појави тука.', 'zmp-competitions'); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php elseif ($active_tab === 'students'): ?>
                <!-- ТАБ 1: ПРИЈАВУВАЊЕ И ЛИСТА СО PDF -->

                <?php if ($new_student): ?>
                    <?php echo $this->render_student_registration_success($new_student); // Ownership was verified above. ?>
                <?php endif; ?>

                                <div class="zmp-card zmp-card--notice"><p>Нови пријави за тест на знаење и музичките категории се поднесуваат од табот „Нови пријави“.</p><p class="zmp-muted">За преглед и исправка на постојни пријави, останете во овој таб.</p></div>
                <?php echo do_shortcode('[zmp_category_entries entries_only="1"]'); ?>

                <div class="zmp-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h4 style="margin: 0;">📋 Список на пријавени ученици (Вкупно: <?php echo count($students); ?>)</h4>
                            <small style="color: #64748b;">Секој ученик има своја генерирана PDF картичка за плико.</small>
                        </div>

                        <?php if (!empty($students)): ?>
                            <a href="<?php echo esc_url(add_query_arg('zmp_action', 'download_all_students_pdf')); ?>" target="_blank" class="zmp-btn zmp-btn-gold">
                                📑 Преземи збирен PDF (сите одеднаш)
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="get" class="zmp-filter-bar"><?php if(isset($_GET['zmp_portal_tab'])): ?><input type="hidden" name="zmp_portal_tab" value="nastavnici"><?php endif; ?><label>Регион <select name="zmp_student_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($student_region,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_student_municipality" list="zmp-student-municipality-options" value="<?php echo esc_attr($student_municipality); ?>"></label><datalist id="zmp-student-municipality-options"><?php foreach(ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist><label>Училиште <input name="zmp_student_school" value="<?php echo esc_attr($student_school); ?>"></label><button class="zmp-btn zmp-btn-secondary">Филтрирај ученици</button></form>
                    <?php if (empty($students)): ?>
                        <p style="color: #64748b; font-style: italic;">Сè уште немате пријавено ученици.</p>
                    <?php else: ?>
                        <div class="zmp-table-responsive">
                            <table class="zmp-table">
                                <thead>
                                    <tr>
                                        <th>Одд.</th>
                                        <th>Име и Презиме</th>
                                        <th>Јазик</th>
                                        <th>Корисничко име (Шифра)</th>
                                        <th>Лозинка (ПИН)</th>
                                        <th>Тековно натпреварувачко ниво</th>
                                        <th>Регион</th>
                                        <th>Општина</th>
                                        <th>Училиште</th>
                                        <th>PDF за плико</th>
                                        <th>Формулар и уплата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $st): $display_pin = ZMP_Credentials::reveal_pin($st->access_pin); ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($st->grade); ?> одд.</strong></td>
                                            <td><?php echo esc_html($st->first_name . ' ' . $st->last_name); ?></td>
                                            <td><span class="zmp-badge"><?php echo strtoupper($st->language); ?></span></td>
                                            <td><code class="zmp-code"><?php echo esc_html($st->access_code); ?></code></td>
                                            <td><span class="zmp-pin"><?php echo esc_html($display_pin); ?></span></td>
                                            <td><span class="zmp-phase-pill"><?php echo esc_html(ZMP_Category_Jury::phase_label($st->current_phase)); ?></span></td>
                                            <td><?php echo esc_html(ZMP_Regions::label($st->region)); ?></td>
                                            <td><?php echo esc_html($st->municipality); ?></td>
                                            <td><?php echo esc_html($st->school_name); ?></td>
                                            <td>
                                                <a href="<?php echo esc_url(add_query_arg(array('zmp_action' => 'download_student_pdf', 'student_id' => $st->id))); ?>" target="_blank" class="zmp-btn zmp-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                                    📄 Преземи PDF
                                                </a>
                                            </td>
                                            <td><?php echo ZMP_Registration_Requirements::render_student_document_links($st->id); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($active_tab === 'complaints'): ?>
                <!-- ТАБ 3: ЖАЛБИ -->
                <div class="zmp-card">
                    <h4>⚖️ Поднеси жалба за ученик</h4>
                    <form method="POST" class="zmp-form">
                        <?php wp_nonce_field('zmp_add_complaint_nonce'); ?>
                        <input type="hidden" name="zmp_mentor_action" value="add_complaint">
                        <div class="zmp-form-row">
                            <div class="zmp-form-group">
                                <label>Ученик *</label>
                                <select name="student_id" required>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?php echo esc_attr($s->id); ?>">
                                            <?php echo esc_html($s->first_name . ' ' . $s->last_name . ' – ' . $s->access_code); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="zmp-form-group">
                            <label>Наслов на жалбата *</label>
                            <input type="text" name="complaint_subject" required placeholder="Наслов">
                        </div>
                        <div class="zmp-form-group">
                            <label>Текст на жалбата *</label>
                            <textarea name="complaint_text" required rows="6" placeholder="Опишете детално проблемот"></textarea>
                        </div>
                        <button type="submit" class="zmp-btn zmp-btn-danger">Поднеси жалба</button>
                    </form>
                </div>
<?php elseif ($active_tab === 'messages'): ?>
                <!-- ТАБ 4: ПОРАКИ -->
                <div class="zmp-card">
                    <h4>📨 Преписка</h4>
                    <?php $teacher_messages=$wpdb->get_results($wpdb->prepare("SELECT m.*,u.display_name AS other_name FROM {$wpdb->prefix}zmp_internal_messages m LEFT JOIN {$wpdb->users} u ON u.ID=CASE WHEN m.sender_id=%d THEN m.recipient_id ELSE m.sender_id END WHERE m.sender_id=%d OR m.recipient_id=%d ORDER BY m.created_at DESC LIMIT 100",$current_user->ID,$current_user->ID,$current_user->ID)); ?>
                    <?php if(!$teacher_messages): ?><p>Сè уште нема пораки.</p><?php else: foreach($teacher_messages as $tm): $incoming=(int)$tm->recipient_id===(int)$current_user->ID; $message_body=(string)$tm->message; $repair_doc_id=0; if($incoming&&$tm->sender_role==='zmp_commission'&&preg_match('/\[ZMP_DOCUMENT_REPAIR:(\d+)\]/',$message_body,$repair_match)){ $repair_doc_id=absint($repair_match[1]); $message_body=preg_replace('/\s*\[ZMP_DOCUMENT_REPAIR:\d+\]/','',$message_body); } ?>
                        <article class="zmp-card"><h5><?php echo esc_html($tm->subject); ?></h5><small><?php echo $incoming?'Од: ':'До: '; ?><?php echo esc_html($tm->other_name?:'Корисник'); ?> · <?php echo esc_html($tm->created_at); ?> · <?php echo $incoming&&$tm->status==='unread'?'Непрочитана':'Прочитана / испратена'; ?></small><p><?php echo nl2br(esc_html($message_body)); ?></p><?php if($repair_doc_id): $repair_url=add_query_arg(array('zmp_portal_tab'=>'nastavnici','zmp_mentor_tab'=>'students','zmp_document_repair'=>$repair_doc_id),get_permalink()?:home_url('/')).'#zmp-document-repair-'.$repair_doc_id; ?><p><a class="zmp-btn zmp-btn-primary" href="<?php echo esc_url($repair_url); ?>">Отвори ја пријавата и прикачи исправка</a></p><?php endif; ?>
                        <?php if($incoming&&$tm->status==='unread'): ?><form method="post"><?php wp_nonce_field('zmp_teacher_message_read'); ?><input type="hidden" name="zmp_mentor_action" value="mark_message_read"><input type="hidden" name="message_id" value="<?php echo (int)$tm->id; ?>"><button class="button">Означи како прочитана</button></form><?php endif; ?></article>
                    <?php endforeach; endif; ?>
                    <h4>📧 Интерен месенџер – испрати порака</h4>
                    <form method="POST" class="zmp-form">
                        <?php wp_nonce_field('zmp_send_message_nonce'); ?>
                        <input type="hidden" name="zmp_mentor_action" value="send_message">
                        <div class="zmp-form-row">
                            <div class="zmp-form-group">
                                <label>Тема *</label>
                                <input type="text" name="subject" required placeholder="Тема на пораката">
                            </div>
                            <div class="zmp-form-group">
                                <label>Порака *</label>
                                <textarea name="message" required rows="4" placeholder="Текст на пораката"></textarea>
                            </div>
                        </div>
                        <div class="zmp-form-row">
                            <label>Примачи *</label><br>
                            <label><input type="checkbox" name="recipients[]" value="commission"> Комисија</label>
                            <label><input type="checkbox" name="recipients[]" value="moderators"> Модератори</label>
                        </div>
                        <button type="submit" class="zmp-btn zmp-btn-primary">Испрати порака</button>
                    </form>
                </div>
<?php elseif ($active_tab === 'results'): ?>
                <!-- ТАБ 2: РЕЗУЛТАТИ ИСКЛУЧИВО ЗА МОИТЕ УЧЕНИЦИ -->
                <div class="zmp-card">
                    <h4>🏆 Резултати на моите ученици — по учебна година и натпреварувачко ниво</h4>
                    <p style="color: #64748b; font-size: 14px;">
                        Како ментор, овде имате увид исклучиво во резултатите и освоените награди на вашите ученици.
                    </p>

                    <form method="get" class="zmp-filter-bar"><input type="hidden" name="tab" value="results"><label>Регион <select name="zmp_mentor_result_region"><option value="">Сите региони</option><?php foreach(ZMP_Regions::regions() as $rk=>$rd): ?><option value="<?php echo esc_attr($rk); ?>" <?php selected($mentor_result_region,$rk); ?>><?php echo esc_html($rd['label']); ?></option><?php endforeach; ?></select></label><label>Општина <input name="zmp_mentor_result_municipality" value="<?php echo esc_attr($mentor_result_municipality); ?>"></label><label>Училиште <input name="zmp_mentor_result_school" value="<?php echo esc_attr($mentor_result_school); ?>"></label><button class="zmp-btn zmp-btn-primary">Филтрирај</button></form>
                    <div class="zmp-table-responsive">
                        <?php $knowledge_phase_labels = array_merge(array('school' => 'Училишно натпреварувачко ниво'), ZMP_Category_Jury::PHASES); ?><table class="zmp-table">
                            <thead>
                                <tr>
                                    <th>Шифра</th>
                                    <th>Ученик</th>
                                    <th>Одд.</th>
                                    <th>Година</th>
                                    <th>Натпреварувачко ниво</th>
                                    <th>Регион</th>
                                    <th>Општина</th>
                                    <th>Училиште</th>
                                    <th>Поени</th>
                                    <th>Освоена Награда</th>
                                    <th>Пласман</th>
                                    <th>Завршено на</th>
                                    <th>Диплома</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_results)): ?>
                                    <tr><td colspan="13">Вашите ученици сè уште немаат завршено тестирање.</td></tr>
                                <?php else: foreach ($my_results as $res): 
                                    $award = ZMP_Scoring::get_award_details($res->total_points, 'mk');
                                ?>
                                    <tr>
                                        <td><code><?php echo esc_html($res->access_code); ?></code></td>
                                        <td><strong><?php echo esc_html($res->first_name . ' ' . $res->last_name); ?></strong></td>
                                        <td><?php echo esc_html($res->grade); ?> одд.</td>
                                        <td><?php echo esc_html($res->academic_year); ?></td>
                                        <td><span class="zmp-badge"><?php echo esc_html($knowledge_phase_labels[$res->phase] ?? 'Натпреварувачко ниво'); ?></span></td>
                                        <td><?php echo esc_html(ZMP_Regions::label($res->region)); ?></td><td><?php echo esc_html($res->municipality); ?></td><td><?php echo esc_html($res->school_name); ?></td>
                                        <td><strong><?php echo number_format($res->total_points, 1); ?></strong> / 100</td>
                                        <td><span class="zmp-award-tag zmp-tag-<?php echo esc_attr($award['badge']); ?>"><?php echo esc_html($award['name_mk']); ?></span></td>
                                        <td>
                                            <?php if ($res->qualified_next_stage == 1): ?>
                                                <span style="color: #16a34a; font-weight: bold;">&check; Пласиран(а)</span>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo esc_html($res->completed_at ? $res->completed_at : 'Во тек'); ?></small></td>
                                        <td><?php echo !empty($res->phase_result_id) && class_exists('ZMP_Certificate_Templates') ? ZMP_Certificate_Templates::render_knowledge_link((int)$res->phase_result_id) : '—'; ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php echo class_exists('ZMP_Mentor_Category_Results') ? ZMP_Mentor_Category_Results::render() : ''; ?>

            <?php elseif ($active_tab === 'complaints'): ?>
                <!-- ТАБ 3: ЖАЛБИ И ПРИГОВОРИ (РОК ОД 24 ЧАСА) -->
                <div class="zmp-card">
                    <h4>⚖️ Поднесување на официјална жалба / приговор (Рок: 24 часа)</h4>
                    <p style="color: #64748b; font-size: 14px;">
                        Согласно правилникот, наставникот има право да поднесе жалба за свој ученик <strong>исклучиво во рок од 24 часа</strong> од завршувањето на тестирањето.
                    </p>

                    <!-- Формулар за нова жалба -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                        <h5>📝 Формулар за приговор</h5>
                        <form id="zmp-complaint-form" class="zmp-form">
                            <div class="zmp-form-row">
                                <div class="zmp-form-group">
                                    <label>Изберете ученик (дозволено само во рок од 24ч) *</label>
                                    <select name="student_id" id="zmp_complaint_student" required>
                                        <option value="">-- Изберете ученик --</option>
                                        <?php 
                                        foreach ($my_results as $mr): 
                                            if ($mr->status === 'completed' && ZMP_Support::can_submit_complaint($mr->completed_at)):
                                        ?>
                                            <option value="<?php echo $mr->student_id; ?>">
                                                <?php echo esc_html($mr->first_name . ' ' . $mr->last_name); ?> (<?php echo esc_html($mr->access_code); ?>) - <?php echo ZMP_Support::get_complaint_time_remaining_label($mr->completed_at); ?>
                                            </option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </div>
                                <div class="zmp-form-group">
                                    <label>Предмет на жалбата *</label>
                                    <input type="text" name="subject" id="zmp_complaint_subject" placeholder="пр. Приговор на прашање бр. 4 или технички дефект" required>
                                </div>
                            </div>

                            <div class="zmp-form-group">
                                <label>Детално образложение на жалбата *</label>
                                <textarea name="complaint_text" id="zmp_complaint_text" rows="3" required placeholder="Наведете ги аргументите и причините за приговорот..." style="width: 100%;"></textarea>
                            </div>

                            <button type="button" id="zmp-btn-submit-complaint" class="zmp-btn zmp-btn-primary">
                                Поднеси официјална жалба &rarr;
                            </button>
                            <span id="zmp-complaint-msg" style="margin-left: 15px; font-weight: bold;"></span>
                        </form>
                    </div>

                    <!-- Историја на поднесени жалби -->
                    <h5>📋 Претходно поднесени жалби</h5>
                    <div class="zmp-table-responsive">
                        <table class="zmp-table">
                            <thead>
                                <tr>
                                    <th>Датум</th>
                                    <th>Ученик</th>
                                    <th>Предмет</th>
                                    <th>Образложение</th>
                                    <th>Одговор на Комисијата</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_complaints)): ?>
                                    <tr><td colspan="6">Немате поднесено жалби.</td></tr>
                                <?php else: foreach ($my_complaints as $c): ?>
                                    <tr>
                                        <td><small><?php echo esc_html($c->created_at); ?></small></td>
                                        <td><strong><?php echo esc_html($c->student_name); ?></strong> (<code><?php echo esc_html($c->student_code); ?></code>)</td>
                                        <td><?php echo esc_html($c->complaint_subject); ?></td>
                                        <td><?php echo nl2br(esc_html($c->complaint_text)); ?></td>
                                        <td>
                                            <?php if (!empty($c->admin_notes)): ?>
                                                <em><?php echo nl2br(esc_html($c->admin_notes)); ?></em>
                                                <?php if ($c->points_adjustment != 0): ?>
                                                    <br><strong style="color: #16a34a;">Корекција на поени: +<?php echo $c->points_adjustment; ?></strong>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-style: italic;">Се разгледува...</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($c->status === 'accepted'): ?>
                                                <span style="color: #16a34a; font-weight: bold;">&check; Прифатена</span>
                                            <?php elseif ($c->status === 'rejected'): ?>
                                                <span style="color: #dc2626; font-weight: bold;">❌ Одбиена</span>
                                            <?php else: ?>
                                                <span style="color: #ca8a04; font-weight: bold;">Во разгледување</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
            <?php echo ZMP_Communications::render_teacher_inbox(); ?>
        </div>
        <?php
        $html = ob_get_clean();
        if ($active_tab === 'profile') {
            $html .= ZMP_Region_Requests::render_teacher_panel();
        }
        return $html;
    }

    /**
     * Порака кога корисникот не е најавен на muzika.org.mk
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-card zmp-text-center">
                <h2>🔐 Најава за наставници по музичко образование</h2>
                <p style="color: #64748b; max-width: 600px; margin: 10px auto 25px auto;">
                    Пријавувањето на учениците за натпреварите се врши исклучиво од регистрирани наставници со одобрена привилегија од администраторот на здружението.
                </p>

                <div style="max-width: 420px; margin: 0 auto; text-align: left;">
                    <?php 
                    wp_login_form(array(
                        'redirect'       => get_permalink(),
                        'label_username' => 'Корисничко име или Е-пошта',
                        'label_password' => 'Лозинка',
                        'label_remember' => 'Запомни ме',
                        'label_log_in'   => 'Најави се на сајтот',
                        'remember'       => true
                    )); 
                    ?>
                </div>

                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 14px;">
                    Сè уште немате кориснички профил на нашата веб-страница?
                    <br>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="zmp-btn zmp-btn-secondary" style="margin-top: 10px;">
                        Регистрирај нов кориснички профил на muzika.org.mk &rarr;
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Порака кога корисникот е најавен, но нема доделено привилегија наставник од администраторот
     */
    private function render_pending_approval($user) {
        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-card zmp-text-center">
                <div style="font-size: 48px; margin-bottom: 10px;">⏳</div>
                <h3 style="color: #1e3a8a;">Профилот чека одобрување од администраторот</h3>
                
                <p style="color: #475569; font-size: 16px; max-width: 650px; margin: 15px auto;">
                    Почитувани <strong><?php echo esc_html($user->display_name); ?></strong> (<?php echo esc_html($user->user_email); ?>),<br>
                    Вашиот кориснички профил е регистриран, но сè уште нема доделено привилегија <strong>„Наставник по музичко образование“</strong>.
                </p>

                <div class="zmp-alert zmp-alert-warning" style="max-width: 600px; margin: 20px auto; text-align: left;">
                    📌 <strong>Што треба да направите?</strong><br>
                    Администраторот на Здружението на музички педагози на Македонија треба да ви ја активира наставничката улога преку административниот панел. 
                    Откако ќе ви биде доделена привилегијата, автоматски ќе добиете пристап до формуларот за пријавување на ученици.
                </div>

                <p style="font-size: 14px; color: #64748b;">
                    За побрзо одобрување контактирајте го здружението на <a href="mailto:info@muzika.org.mk">info@muzika.org.mk</a>.
                </p>

                <div style="margin-top: 25px;">
                    <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>" class="zmp-btn zmp-btn-secondary">
                        Одјави се од овој профил
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the new student's credentials and PDF-card link after registration.
     *
     * @param object $student Student row owned by the current mentor.
     * @return string
     */
    private function render_student_registration_success($student) {
        ob_start();
        ?>
        <div class="zmp-alert zmp-alert-success" style="border-left: 5px solid #16a34a; padding: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #166534;">🎉 Ученикот е успешно пријавен!</h4>
            <p style="margin: 0 0 15px 0;">
                <strong>Ученик:</strong> <?php echo esc_html($student->first_name . ' ' . $student->last_name); ?> (<?php echo esc_html($student->grade); ?>. одд.)<br>
                <strong>Шифра (Корисничко име):</strong> <code><?php echo esc_html($student->access_code); ?></code> |
                <strong>Лозинка (ПИН):</strong> <strong><?php echo esc_html($student->access_pin); ?></strong>
            </p>
            <a href="<?php echo esc_url(add_query_arg(array('zmp_action' => 'download_student_pdf', 'student_id' => absint($student->id)))); ?>" target="_blank" rel="noopener" class="zmp-btn zmp-btn-gold">
                📄 Отвори картичка за печатење / зачувување како PDF &rarr;
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Формулар за првично поставување податоци за училиштето на наставникот
     *
     * @param WP_User      $user Current mentor account.
     * @param object|null  $mentor Mentor profile row, if present.
     * @param bool         $after_student_registration Whether this form follows a saved student registration.
     * @return string
     */
    /** One registration workspace for every category enabled and open this year. */
    private function render_registration_hub($mentor) {
        if (!$mentor) { return '<div class="zmp-alert">Прво пополнете го профилот на наставникот.</div>'; }
        ZMP_Mentor_Schools::ensure_primary($mentor);
        $schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id);
        if (!$schools) { return '<div class="zmp-alert">Додајте училиште во профилот пред да започнете пријава.</div>'; }
        $season = ZMP_Category_Registrations::active_season();
        if (!$season) { return '<div class="zmp-alert">Нема активна натпреварувачка година.</div>'; }
        global $wpdb;
        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id,c.name_mk,c.category_code FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 WHERE c.is_active=1 ORDER BY c.sort_order,c.id",
            (int)$season->id
        ));
        $open_categories = array();
        foreach ((array)$categories as $category) { if (ZMP_Registration_Windows::allows((int)$season->id, (int)$category->id)) { $open_categories[] = $category; } }
        $knowledge_open = false;
        foreach ($open_categories as $category) { if ($category->category_code === 'knowledge_test') { $knowledge_open = true; break; } }
        ob_start(); ?>
        <section class="zmp-card zmp-registration-hub" aria-labelledby="zmp-registration-title">
            <h3 id="zmp-registration-title">Нова пријава — <?php echo esc_html($season->title); ?></h3>
            <p>Изберете училиште, потоа категорија. Формуларот ќе ги прикаже полињата потребни за избраниот натпревар.</p>
            <?php if ($open_categories): ?>
            <div class="zmp-form-row">
                <label>Училиште *<select id="zmp-registration-school" required><option value="">Изберете училиште</option>
                    <?php foreach ($schools as $school): ?><option value="<?php echo (int)$school->id; ?>"><?php echo esc_html($school->school_name.' · '.$school->municipality.' · '.ZMP_Regions::label($school->region)); ?></option><?php endforeach; ?>
                </select></label>
                <label>Категорија *<select id="zmp-registration-category" required><option value="">Изберете категорија</option>
                    <?php foreach ($open_categories as $category): ?><option value="<?php echo (int)$category->id; ?>" data-code="<?php echo esc_attr($category->category_code); ?>"><?php echo esc_html($category->name_mk); ?></option><?php endforeach; ?>
                </select></label>
            </div>
            <?php if ($knowledge_open) echo $this->render_knowledge_registration_form(); ?>
            <?php else: ?><div class="zmp-alert">Нема активни категории со отворен рок за пријавување.</div><?php endif; ?>
        </section>
        <div class="zmp-registration-category-workspace"><?php echo do_shortcode('[zmp_category_entries registration_hub="1"]'); ?></div>
        <?php if ($open_categories): ?><script>(function(){var school=document.getElementById('zmp-registration-school'),category=document.getElementById('zmp-registration-category'),knowledge=document.getElementById('zmp-knowledge-registration-panel'),music=document.querySelector('.zmp-category-registration'),schoolInputs=[document.getElementById('zmp-knowledge-school-id'),document.getElementById('zmp-entry-school-id')],innerCategory=document.getElementById('zmp-entry-category');if(!school||!category)return;function sync(){schoolInputs.forEach(function(input){if(input)input.value=school.value;});var option=category.options[category.selectedIndex],isKnowledge=option&&option.getAttribute('data-code')==='knowledge_test';if(knowledge)knowledge.hidden=!isKnowledge;if(music)music.hidden=isKnowledge;if(innerCategory&&!isKnowledge&&option){innerCategory.value=option.value;innerCategory.dispatchEvent(new Event('change',{bubbles:true}));}}school.addEventListener('change',sync);category.addEventListener('change',sync);if(category.options.length>1){category.selectedIndex=1;}if(school.options.length>1){school.selectedIndex=1;}sync();})();</script><?php endif; ?>
        <?php return ob_get_clean();
    }

    /** Knowledge-test fields reuse the established secure student-registration handler. */
    private function render_knowledge_registration_form() {
        ob_start(); ?>
        <div id="zmp-knowledge-registration-panel" class="zmp-card" hidden>
            <h4>Пријава за тест на знаење</h4>
            <form method="post" enctype="multipart/form-data" class="zmp-form">
                <?php wp_nonce_field('zmp_add_student_nonce'); ?>
                <input type="hidden" name="zmp_mentor_action" value="add_student">
                <input type="hidden" id="zmp-knowledge-school-id" name="mentor_school_id" value="">
                <input type="hidden" name="payer_type" value="teacher"><p class="zmp-alert">За тестот на знаење нема котизација при првичната пријава. Ако ученикот се пласира од општинско на регионално ниво, тогаш се поднесува посебно барање за фактура или доказ за уплата.</p>
                <label>Потпишан годишен формулар (PDF/JPG/PNG) *<input type="file" name="signed_application" accept=".pdf,.jpg,.jpeg,.png" required></label>
                
                <label>Потпишана согласност за фотографирање, снимање и објавување материјал само за потребите на државните натпревари на здружението (PDF/JPG/PNG) *<input type="file" name="media_consent" accept=".pdf,.jpg,.jpeg,.png" required></label>
                <div class="zmp-form-row"><label>Име на ученикот *<input type="text" name="first_name" required></label><label>Презиме на ученикот *<input type="text" name="last_name" required></label></div>
                <div class="zmp-form-row"><label>Одделение *<select name="grade" required><option value="6">VI одделение</option><option value="7">VII одделение</option><option value="8">VIII одделение</option><option value="9">IX одделение</option></select></label>
                <label>Јазик на тестирање *<select name="language" required><option value="mk">Македонски јазик</option><option value="sq">Албански јазик</option></select></label></div>
                <button type="submit" class="zmp-btn zmp-btn-success">Генерирај кодови и спреми PDF</button>
            </form>
        </div>
        <?php return ob_get_clean();
    }

    private function render_mentor_schools_panel($mentor) {
        if (!$mentor) { return ''; }
        $subjects = class_exists('ZMP_Mentor_Subjects') ? ZMP_Mentor_Subjects::approved_subjects() : new WP_Error('schema_not_ready', '');
        if (is_wp_error($subjects)) {
            return '<section class="zmp-card" aria-labelledby="zmp-my-schools-title"><h3 id="zmp-my-schools-title">Мои училишта</h3><div class="zmp-alert zmp-alert-danger" role="alert"><p>' . esc_html__('Регистарот на предмети моментално не е достапен. Прегледот и промените на училишните предмети се привремено оневозможени.', 'zmp-competitions') . '</p></div></section>';
        }
        $schools = ZMP_Mentor_Schools::for_mentor((int)$mentor->id);
        $assignments = class_exists('ZMP_Mentor_Subjects')
            ? (array) ZMP_Mentor_Subjects::teacher_assignments((int) $mentor->wp_user_id)
            : array();
        $assignment_status = sanitize_key(wp_unslash($_GET['msg'] ?? ''));
        $assignment_status_messages = array(
            'school_added' => __('Училиштето е додадено.', 'zmp-competitions'),
            'invalid_school' => __('Внесете важечко име на училиште и општина.', 'zmp-competitions'),
            'unknown_municipality' => __('Не може да се утврди регионот. Проверете ја општината.', 'zmp-competitions'),
            'school_save_failed' => __('Училиштето не е зачувано. Обидете се повторно.', 'zmp-competitions'),
            'assignments_saved' => __('Предметите и одделенијата/годините се зачувани.', 'zmp-competitions'),
            'assignments_invalid' => __('Изберете најмалку еден одобрен предмет и одделение/година за секое активно училиште.', 'zmp-competitions'),
            'schema_not_ready' => __('Регистарот на предмети моментално не е достапен. Училишните и предметните промени не се зачувани.', 'zmp-competitions'),
            'assignment_save_failed' => __('Податоците не можеа да се зачуваат. Обидете се повторно.', 'zmp-competitions'),
        );
        $subject_names = array();
        foreach ($subjects as $subject) {
            $subject_id = is_object($subject) ? (int) ($subject->id ?? 0) : (int) ($subject['id'] ?? 0);
            $subject_name = is_object($subject) ? (string) ($subject->subject_name ?? '') : (string) ($subject['subject_name'] ?? '');
            if ($subject_id > 0 && $subject_name !== '') { $subject_names[$subject_id] = $subject_name; }
        }
        $grade_year_options = array(
            'grade_1' => esc_html__('I одделение', 'zmp-competitions'),
            'grade_2' => esc_html__('II одделение', 'zmp-competitions'),
            'grade_3' => esc_html__('III одделение', 'zmp-competitions'),
            'grade_4' => esc_html__('IV одделение', 'zmp-competitions'),
            'grade_5' => esc_html__('V одделение', 'zmp-competitions'),
            'grade_6' => esc_html__('VI одделение', 'zmp-competitions'),
            'grade_7' => esc_html__('VII одделение', 'zmp-competitions'),
            'grade_8' => esc_html__('VIII одделение', 'zmp-competitions'),
            'grade_9' => esc_html__('IX одделение', 'zmp-competitions'),
            'year_1' => esc_html__('I година', 'zmp-competitions'),
            'year_2' => esc_html__('II година', 'zmp-competitions'),
            'year_3' => esc_html__('III година', 'zmp-competitions'),
            'year_4' => esc_html__('IV година', 'zmp-competitions'),
        );
        ob_start(); ?>
        <section class="zmp-card" aria-labelledby="zmp-my-schools-title">
            <h3 id="zmp-my-schools-title">Мои училишта</h3>
            <?php if (isset($assignment_status_messages[$assignment_status])): $status_is_success = in_array($assignment_status, array('school_added','assignments_saved'), true); ?><p class="zmp-alert <?php echo $status_is_success ? 'zmp-alert-success' : 'zmp-alert-danger'; ?>" role="<?php echo $status_is_success ? 'status' : 'alert'; ?>" aria-live="polite"><?php echo esc_html($assignment_status_messages[$assignment_status]); ?></p><?php endif; ?>
            <p>Регионот се поставува автоматски според општината. Старите пријави ја задржуваат снимката на училиштето со кое биле поднесени.</p>
            <?php if ($schools): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Училиште</th><th>Општина</th><th>Регион</th><th>Предмети и одделенија/години</th><th>Статус</th></tr></thead><tbody>
                <?php foreach ($schools as $school): $school_assignments = (array) ($assignments[(int) $school->id] ?? array()); ?><tr><td><?php echo esc_html($school->school_name); ?></td><td><?php echo esc_html($school->municipality); ?></td><td><?php echo esc_html(ZMP_Regions::label($school->region)); ?></td><td>
                    <?php $assigned_rows = array(); foreach ($school_assignments as $assigned_subject_id => $assigned_values) { $assigned_values = array_values(array_intersect(array_map('strval', (array) $assigned_values), array_keys($grade_year_options))); if (isset($subject_names[(int) $assigned_subject_id]) && $assigned_values) { $assigned_rows[] = $subject_names[(int) $assigned_subject_id] . ': ' . implode(', ', array_map(function ($value) use ($grade_year_options) { return $grade_year_options[$value]; }, $assigned_values)); } } ?>
                    <?php if ($assigned_rows): ?><ul><?php foreach ($assigned_rows as $assigned_row): ?><li><?php echo esc_html($assigned_row); ?></li><?php endforeach; ?></ul><?php else: ?><span><?php echo esc_html__('Сè уште нема избрани предмети.', 'zmp-competitions'); ?></span><?php endif; ?>
                </td><td><?php echo (int)$school->is_primary ? 'Примарно' : 'Активно'; ?></td></tr><?php endforeach; ?>
            </tbody></table></div><?php else: ?><p>Сè уште нема додадени училишта.</p><?php endif; ?>
            <h4>Додај училиште</h4>
            <form method="post" class="zmp-form" id="zmp-add-mentor-school-form"><input type="hidden" name="zmp_mentor_action" value="add_mentor_school"><?php wp_nonce_field('zmp_add_mentor_school'); ?>
                <div class="zmp-form-row"><label><?php echo esc_html__('Име на училиште *', 'zmp-competitions'); ?><input name="school_name" maxlength="255" required></label><label><?php echo esc_html__('Општина *', 'zmp-competitions'); ?><input name="municipality" id="zmp-school-municipality" list="zmp-school-municipality-options" maxlength="150" required></label></div>
                <datalist id="zmp-school-municipality-options"><?php foreach (ZMP_Regions::municipality_options() as $municipality): ?><option value="<?php echo esc_attr($municipality); ?>"><?php endforeach; ?></datalist>
                <div class="zmp-form-group"><label for="zmp-school-region"><?php echo esc_html__('Регион (се одредува според општината)', 'zmp-competitions'); ?></label><input type="text" id="zmp-school-region" readonly aria-readonly="true" aria-describedby="zmp-school-region-help"><small id="zmp-school-region-help" aria-live="polite"><?php echo esc_html__('Внесете општина од списокот за да се прикаже регионот.', 'zmp-competitions'); ?></small></div>
                <button type="submit" class="zmp-btn zmp-btn-primary">Додај училиште</button>
            </form>
            <?php if ($schools && $subject_names): ?>
                <h4><?php echo esc_html__('Предмети и одделенија/години по училиште', 'zmp-competitions'); ?></h4>
                <p><?php echo esc_html__('За секое активно училиште изберете најмалку еден одобрен предмет и барем едно одделение или година. Предметите и одделенијата/годините се чуваат одделно за секое училиште.', 'zmp-competitions'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form zmp-teacher-school-assignments">
                    <?php wp_nonce_field('zmp_save_teacher_assignments'); ?>
                    <input type="hidden" name="action" value="zmp_save_teacher_assignments">
                    <?php foreach ($schools as $school): $school_id = (int) $school->id; $school_assignments = (array) ($assignments[$school_id] ?? array()); ?>
                        <fieldset class="zmp-card" aria-describedby="zmp-school-assignment-help-<?php echo $school_id; ?>">
                            <legend><?php echo esc_html($school->school_name . ' · ' . $school->municipality . ' · ' . ZMP_Regions::label($school->region)); ?></legend>
                            <p id="zmp-school-assignment-help-<?php echo $school_id; ?>"><?php echo esc_html__('Изберете предмети и соодветни одделенија/години за ова училиште.', 'zmp-competitions'); ?></p>
                            <?php foreach ($subject_names as $subject_id => $subject_name): $selected_years = array_map('strval', (array) ($school_assignments[$subject_id] ?? array())); ?>
                                <fieldset class="zmp-mentor-subject-years"><legend><?php echo esc_html($subject_name); ?></legend>
                                    <div class="zmp-form-row zmp-mentor-grade-year-options">
                                        <?php foreach ($grade_year_options as $grade_year => $grade_year_label): $input_id = 'zmp-school-' . $school_id . '-subject-' . (int) $subject_id . '-' . $grade_year; ?>
                                            <label for="<?php echo esc_attr($input_id); ?>"><input type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="assignments[<?php echo $school_id; ?>][<?php echo (int) $subject_id; ?>][]" value="<?php echo esc_attr($grade_year); ?>" <?php checked(in_array($grade_year, $selected_years, true)); ?>> <?php echo esc_html($grade_year_label); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endforeach; ?>
                    <button type="submit" class="zmp-btn zmp-btn-primary"><?php echo esc_html__('Зачувај предмети и одделенија/години', 'zmp-competitions'); ?></button>
                </form>
            <?php elseif (!$subject_names): ?>
                <div class="zmp-alert zmp-alert-info" role="status"><p><?php echo esc_html__('Сѐ уште нема одобрени предмети за избор. Можете да додавате училишта; предметите ќе се појават тука кога ќе бидат внесени во заедничкиот регистар.', 'zmp-competitions'); ?></p></div>
            <?php endif; ?>
        </section>
        <?php
        $html = ob_get_clean();
        $municipality_regions = ZMP_Regions::client_lookup_map();
        $region_labels = array_map(function ($row) { return $row['label']; }, ZMP_Regions::regions());
        $html .= '<script>(function(){var form=document.getElementById("zmp-add-mentor-school-form"),m=document.getElementById("zmp-school-municipality"),r=document.getElementById("zmp-school-region"),help=document.getElementById("zmp-school-region-help");if(!form||!m||!r||!help)return;var map=' . wp_json_encode($municipality_regions) . ',labels=' . wp_json_encode($region_labels) . ';function key(v){var t=v.trim().toLocaleLowerCase().replace(/^(општина|opština|opstina|municipality|град|grad)\\s+/,"");var tr={"ѓ":"gj","ќ":"kj","љ":"lj","њ":"nj","џ":"dzh","ж":"zh","ч":"ch","ш":"sh","ѕ":"dz","ј":"j","ц":"c","а":"a","б":"b","в":"v","г":"g","д":"d","е":"e","з":"z","и":"i","к":"k","л":"l","м":"m","н":"n","о":"o","п":"p","р":"r","с":"s","т":"t","у":"u","ф":"f","х":"h","č":"ch","ć":"c","š":"sh","ž":"zh","đ":"dj"};return t.replace(/./gu,function(c){return tr[c]||c;}).replace(/[^a-z0-9]+/g,"");}function update(){var region=map[key(m.value)];r.value=region&&labels[region]?labels[region]:"";help.textContent=r.value?' . wp_json_encode(esc_html__('Регионот е автоматски одреден според општината.', 'zmp-competitions')) . ':' . wp_json_encode(esc_html__('Не може да се утврди регион. Проверете ја општината.', 'zmp-competitions')) . ';}m.addEventListener("input",update);m.addEventListener("change",update);update();})();</script>';
        return $html;
    }
    private function render_profile_setup($user, $mentor, $after_student_registration = false) {
        ob_start();
        ?>
        <div class="zmp-container">
            <div class="zmp-card">
                <div class="zmp-header-box">
                    <h2>🏫 Податоци за училиштето и наставникот</h2>
                    <p><?php echo $after_student_registration
                        ? esc_html__('Ученикот е пријавен. Пополнете ги преостанатите податоци за да продолжите да го користите порталот.', 'zmp-competitions')
                    : esc_html__('Внесете ги училиштето и општината. Регионот се поставува автоматски според општината; за исклучок поднесете барање преку допис до модераторите.', 'zmp-competitions'); ?></p>
                </div>

                <form method="POST" class="zmp-form">
                    <?php wp_nonce_field('zmp_mentor_setup_nonce'); ?>
                    <input type="hidden" name="zmp_mentor_action" value="setup_mentor_profile">

                    <div class="zmp-form-group">
                        <label>Име и презиме на наставникот *</label>
                        <input type="text" name="full_name" value="<?php echo esc_attr($mentor ? $mentor->full_name : $user->display_name); ?>" required>
                    </div>

                    <p><?php echo esc_html__('Додадете најмалку едно училиште овде. По зачувувањето на профилот можете да внесете и други училишта и за секое да ги изберете предметите и одделенијата/годините што ги предавате.', 'zmp-competitions'); ?></p>

                    <div class="zmp-form-row">
                        <div class="zmp-form-group">
                            <label>Училиште *</label>
                            <input type="text" name="school_name" value="<?php echo esc_attr($mentor ? $mentor->school_name : ''); ?>" required placeholder="пр. ООУ „Кузман Јосифовски - Питу“">
                        </div>
                        <div class="zmp-form-group">
                            <label>Општина / Град *</label>
                        <input type="text" name="municipality" id="zmp-mentor-municipality" list="zmp-municipality-options" value="<?php echo esc_attr($mentor ? $mentor->municipality : ''); ?>" required placeholder="пр. Кисела Вода">
                        <datalist id="zmp-municipality-options"><?php foreach (ZMP_Regions::municipality_options() as $municipality_option): ?><option value="<?php echo esc_attr($municipality_option); ?>"><?php endforeach; ?></datalist>
                        </div>
                    </div>

                    <div class="zmp-form-group">
                        <label>Регион *</label>
                        <input type="text" id="zmp-mentor-region" value="<?php $selected_region = $mentor && ZMP_Regions::valid_region($mentor->region) ? $mentor->region : ZMP_Regions::region_for_municipality($mentor ? $mentor->municipality : ''); echo esc_attr(ZMP_Regions::label($selected_region)); ?>" readonly aria-readonly="true">
                        <small>Автоматски се одредува според општината. Измена се бара преку допис и ја одобрува модератор.</small>
                    </div>

                    <div class="zmp-form-group">
                        <label>Телефон за контакт (наставник) *</label>
                        <input type="text" name="phone" value="<?php echo esc_attr($mentor ? $mentor->phone : ''); ?>" required placeholder="07X XXX XXX">
                    </div>

                    <button type="submit" class="zmp-btn zmp-btn-primary">
                        Зачувај податоци и продолжи &rarr;
                    </button>
                </form>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        $municipality_regions = ZMP_Regions::client_lookup_map();
        $html .= '<script>(function(){var m=document.getElementById("zmp-mentor-municipality"),r=document.getElementById("zmp-mentor-region"),map=' . wp_json_encode($municipality_regions) . ';if(!m||!r)return;function key(v){var t=v.trim().toLocaleLowerCase().replace(/^(општина|opština|opstina|municipality|град|grad)\\s+/,"");var tr={"ѓ":"gj","ќ":"kj","љ":"lj","њ":"nj","џ":"dzh","ж":"zh","ч":"ch","ш":"sh","ѕ":"dz","ј":"j","ц":"c","а":"a","б":"b","в":"v","г":"g","д":"d","е":"e","з":"z","и":"i","к":"k","л":"l","м":"m","н":"n","о":"o","п":"p","р":"r","с":"s","т":"t","у":"u","ф":"f","х":"h","č":"ch","ć":"c","š":"sh","ž":"zh","đ":"dj"};return t.replace(/./gu,function(c){return tr[c]||c;}).replace(/[^a-z0-9]+/g,"");}var labels=' . wp_json_encode(array_map(function($row){return $row['label'];}, ZMP_Regions::regions())) . ';function suggest(){var k=key(m.value);if(map[k]&&labels[map[k]])r.value=labels[map[k]];}m.addEventListener("input",suggest);m.addEventListener("change",suggest);})();</script>';
        return $html;
    }
}


