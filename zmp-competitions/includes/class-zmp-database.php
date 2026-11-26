<?php
if (!defined('ABSPATH')) {
    exit;
}

class ZMP_Database {

    /** Normalize integer display widths returned differently by supported MySQL versions. */
    public static function normalize_schema_type($type) {
        $type = strtolower(trim((string) $type));
        return preg_replace('/\\b(tinyint|smallint|mediumint|int|integer|bigint)\\s*\\(\\s*\\d+\\s*\\)/i', '$1', $type);
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Табела за конфигурација на натпреварот и тековното натпреварувачко ниво
        $table_competitions = $wpdb->prefix . 'zmp_competitions';
        $sql1 = "CREATE TABLE $table_competitions (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            title VARCHAR(255) NOT NULL,
            academic_year VARCHAR(50) NOT NULL,
            current_phase VARCHAR(50) DEFAULT 'school' NOT NULL,
            duration_minutes INT DEFAULT 45 NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            workflow_status VARCHAR(20) DEFAULT 'active' NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            completion_note TEXT DEFAULT NULL,
            finalized_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            finalized_at DATETIME DEFAULT NULL,
            finalization_note TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql1);

        // 2. Табела за наставници / ментори (поврзани со WordPress корисник)
        $table_mentors = $wpdb->prefix . 'zmp_mentors';
        $sql2 = "CREATE TABLE $table_mentors (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            wp_user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) DEFAULT '',
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(100) NOT NULL,
            region VARCHAR(100) DEFAULT '',
            login_token VARCHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY wp_user_id (wp_user_id)
        ) $charset_collate;";
        dbDelta($sql2);

        // 3. Табела за натпреварувачи (ученици) со кориснички имиња и кодови за плико
        $table_students = $wpdb->prefix . 'zmp_students';
        $sql3 = "CREATE TABLE $table_students (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            mentor_id INT NOT NULL,
            mentor_school_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            grade INT NOT NULL,
            language VARCHAR(10) DEFAULT 'mk' NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(100) NOT NULL,
            region VARCHAR(30) DEFAULT '' NOT NULL,
            access_code VARCHAR(50) NOT NULL,
            access_pin VARCHAR(255) NOT NULL,
            access_pin_hash VARCHAR(255) DEFAULT '' NOT NULL,
            current_phase VARCHAR(50) DEFAULT 'school' NOT NULL,
            qualified_for_next TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY access_code (access_code)
        ) $charset_collate;";
        dbDelta($sql3);

        // 4. Банка на прашања (Двојазично MK/SQ со аудио и нотни слики)
        $table_questions = $wpdb->prefix . 'zmp_questions';
        $sql4 = "CREATE TABLE $table_questions (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            grade INT NOT NULL,
            phase VARCHAR(50) DEFAULT 'all' NOT NULL,
            question_text_mk TEXT NOT NULL,
            question_text_sq TEXT NOT NULL,
            question_type VARCHAR(50) DEFAULT 'multiple_choice' NOT NULL,
            audio_url TEXT DEFAULT NULL,
            image_url TEXT DEFAULT NULL,
            options_json LONGTEXT NOT NULL,
            correct_option_index INT NOT NULL,
            points DECIMAL(5,2) DEFAULT 5.00 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql4);

        // 5. Табела за резултати / обиди на тестот (со паузирање, активно време и рестартирање)
        $table_attempts = $wpdb->prefix . 'zmp_attempts';
        $sql5 = "CREATE TABLE $table_attempts (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            student_id INT NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            phase VARCHAR(50) NOT NULL,
            grade INT NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            remaining_seconds INT DEFAULT 2700 NOT NULL,
            time_spent_seconds INT DEFAULT 0 NOT NULL,
            last_active_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_paused TINYINT(1) DEFAULT 0 NOT NULL,
            reset_count INT DEFAULT 0 NOT NULL,
            reset_reason TEXT DEFAULT NULL,
            total_points DECIMAL(5,2) DEFAULT 0.00 NOT NULL,
            award_level VARCHAR(50) DEFAULT '' NOT NULL,
            qualified_next_stage TINYINT(1) DEFAULT 0 NOT NULL,
            answers_json LONGTEXT DEFAULT NULL,
            question_ids_json LONGTEXT DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'in_progress' NOT NULL,
            UNIQUE KEY student_phase_unique (student_id, phase)
        ) $charset_collate;";
        dbDelta($sql5);

        // 6. Календар на натпревари (Автоматско отворање според датум и час со временски прозорец)
        $table_calendar = $wpdb->prefix . 'zmp_calendar';
        $sql6 = "CREATE TABLE $table_calendar (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            phase VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            duration_minutes INT DEFAULT 45 NOT NULL,
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql6);

        // 7. Дописи / Инциденти во сала (како форма на е-пошта од Тестатори до Модератори)
        $table_incidents = $wpdb->prefix . 'zmp_incidents';
        $sql7 = "CREATE TABLE $table_incidents (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            subject VARCHAR(255) DEFAULT 'Службен допис за дефект / проблем' NOT NULL,
            testator_user_id BIGINT(20) UNSIGNED NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            testator_name VARCHAR(150) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(100) NOT NULL,
            student_id INT DEFAULT 0,
            student_code VARCHAR(50) DEFAULT '',
            incident_type VARCHAR(50) DEFAULT 'defect',
            priority VARCHAR(20) DEFAULT 'normal' NOT NULL,
            message TEXT NOT NULL,
            moderator_reply TEXT DEFAULT NULL,
            status VARCHAR(30) DEFAULT 'unread' NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL
        ) $charset_collate;";
        dbDelta($sql7);

        // 8. Жалби од наставници за ученици (до Тестатори со можност за одлука или препраќање до модератори)
        $table_complaints = $wpdb->prefix . 'zmp_complaints';
        $sql8 = "CREATE TABLE $table_complaints (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            mentor_user_id BIGINT(20) UNSIGNED NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            mentor_name VARCHAR(150) NOT NULL,
            student_id INT NOT NULL,
            student_name VARCHAR(150) NOT NULL,
            student_code VARCHAR(50) NOT NULL,
            attempt_id INT NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            phase VARCHAR(50) NOT NULL,
            complaint_subject VARCHAR(255) NOT NULL,
            complaint_text TEXT NOT NULL,
            testator_user_id BIGINT(20) UNSIGNED DEFAULT 0,
            testator_decision VARCHAR(50) DEFAULT 'pending' NOT NULL,
            testator_notes TEXT DEFAULT NULL,
            status VARCHAR(30) DEFAULT 'pending' NOT NULL,
            points_adjustment DECIMAL(5,2) DEFAULT 0.00,
            admin_notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL
        ) $charset_collate;";
        dbDelta($sql8);

        // 9. Испитна Комисија (Највисоко тело: колеги од средно/високо музичко образование)
        $table_commission = $wpdb->prefix . 'zmp_commission';
        $sql9 = "CREATE TABLE $table_commission (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            user_id BIGINT(20) UNSIGNED NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            institution VARCHAR(255) NOT NULL,
            academic_title VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) DEFAULT '',
            is_active TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql9);

        // 10. Регистар на Дисквалификации (на ученик, тестатор или ментор со задолжително образложение)
        $table_disqualifications = $wpdb->prefix . 'zmp_disqualifications';
        $sql10 = "CREATE TABLE $table_disqualifications (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            target_type VARCHAR(30) NOT NULL,
            target_id INT NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            target_name VARCHAR(150) NOT NULL,
            target_code VARCHAR(50) DEFAULT '',
            school_name VARCHAR(255) NOT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            disqualified_by_user_id BIGINT(20) UNSIGNED NOT NULL,
            disqualified_by_name VARCHAR(150) NOT NULL,
            disqualified_by_role VARCHAR(50) NOT NULL,
            detailed_reason TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql10);

        // 11. Конфигурација на Датабаза и Системски Поставки
        $table_settings = $wpdb->prefix . 'zmp_settings';
        $sql11 = "CREATE TABLE $table_settings (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            db_host VARCHAR(150) DEFAULT '127.0.0.1' NOT NULL,
            db_user VARCHAR(150) DEFAULT '' NOT NULL,
            db_pass VARCHAR(255) DEFAULT '' NOT NULL,
            db_name VARCHAR(150) DEFAULT '' NOT NULL,
            custom_db_enabled TINYINT(1) DEFAULT 0 NOT NULL,
            profile_fields_enabled TINYINT(1) DEFAULT 1 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql11);

        // 12. Log table for Archive & Reset actions
        $table_reset_log = $wpdb->prefix . 'zmp_reset_log';
        $sql12 = "CREATE TABLE $table_reset_log (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            action VARCHAR(50) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql12);

        // 13. Internal messages table for communication between roles
        $table_internal_messages = $wpdb->prefix . 'zmp_internal_messages';
        $sql13 = "CREATE TABLE $table_internal_messages (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            sender_id BIGINT(20) UNSIGNED NOT NULL,
            sender_role VARCHAR(30) NOT NULL,
            recipient_id BIGINT(20) UNSIGNED NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'unread' NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql13);

        // 14. Testator duties table
        $table_testator_duties = $wpdb->prefix . 'zmp_testator_duties';
        $sql14 = "CREATE TABLE $table_testator_duties (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            testator_user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            duty_date DATE NOT NULL,
            phase VARCHAR(50) DEFAULT 'school' NOT NULL,
            location VARCHAR(255) NOT NULL,
            notes TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql14);

        // 15. Commission decisions table
        $table_commission_decisions = $wpdb->prefix . 'zmp_commission_decisions';
        $sql15 = "CREATE TABLE $table_commission_decisions (
            id INT NOT NULL AUTO_INCREMENT,
            PRIMARY KEY  (id),
            decision_type VARCHAR(50) NOT NULL,
            source_type VARCHAR(50) DEFAULT '' NOT NULL,
            source_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            competition_id INT DEFAULT 0 NOT NULL,
            target_type VARCHAR(30) DEFAULT '' NOT NULL,
            target_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            target_code VARCHAR(50) DEFAULT '' NOT NULL,
            school_name VARCHAR(255) DEFAULT '' NOT NULL,
            created_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            subject_name VARCHAR(255) NOT NULL,
            reason TEXT NOT NULL,
            decision_note TEXT DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'pending' NOT NULL,
            reviewed_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        dbDelta($sql15);

        // 16. Per-competition phase closure and its accountable decision record.
        $table_phase_status = $wpdb->prefix . 'zmp_phase_status';
        $sql16 = "CREATE TABLE $table_phase_status (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            competition_id INT NOT NULL,
            phase VARCHAR(30) NOT NULL,
            status VARCHAR(20) DEFAULT 'open' NOT NULL,
            closed_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            closed_at DATETIME DEFAULT NULL,
            decision_reason TEXT DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY competition_phase (competition_id, phase)
        ) $charset_collate;";
        dbDelta($sql16);

        // 17. Immutable public result snapshots captured when a phase is closed.
        $table_phase_results = $wpdb->prefix . 'zmp_phase_results';
        $sql17 = "CREATE TABLE $table_phase_results (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            competition_id INT NOT NULL,
            attempt_id INT NOT NULL,
            student_id INT NOT NULL,
            mentor_id INT NOT NULL,
            mentor_user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            academic_year VARCHAR(50) NOT NULL,
            phase VARCHAR(30) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) DEFAULT '' NOT NULL,
            last_name VARCHAR(100) DEFAULT '' NOT NULL,
            access_code VARCHAR(50) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(100) NOT NULL,
            region VARCHAR(30) DEFAULT '' NOT NULL,
            grade INT NOT NULL,
            total_points DECIMAL(5,2) NOT NULL,
            award_level VARCHAR(50) DEFAULT '' NOT NULL,
            qualified_next_stage TINYINT(1) DEFAULT 0 NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY attempt_id (attempt_id),
            KEY year_phase (academic_year, phase),
            KEY mentor_year (mentor_user_id, academic_year)
        ) $charset_collate;";
        dbDelta($sql17);

        // 18. Moderator pause, stop and restart audit trail.
        $table_moderator_actions = $wpdb->prefix . 'zmp_moderator_actions';
        $sql18 = "CREATE TABLE $table_moderator_actions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            competition_id INT NOT NULL,
            incident_id INT DEFAULT 0 NOT NULL,
            attempt_id INT NOT NULL,
            student_id INT NOT NULL,
            moderator_user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(20) NOT NULL,
            reason TEXT NOT NULL,
            previous_status VARCHAR(30) NOT NULL,
            new_status VARCHAR(30) NOT NULL,
            previous_remaining_seconds INT DEFAULT 0 NOT NULL,
            previous_score DECIMAL(5,2) DEFAULT 0.00 NOT NULL,
            previous_completed_at DATETIME DEFAULT NULL,
            previous_answers_json LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY attempt_id (attempt_id),
            KEY incident_id (incident_id)
        ) $charset_collate;";
        dbDelta($sql18);

        // 19. Commission score decisions; old and new values plus factual rationale.
        $table_score_adjustments = $wpdb->prefix . 'zmp_score_adjustments';
        $sql19 = "CREATE TABLE $table_score_adjustments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            competition_id INT NOT NULL,
            complaint_id INT NOT NULL,
            attempt_id INT NOT NULL,
            student_id INT NOT NULL,
            commission_user_id BIGINT(20) UNSIGNED NOT NULL,
            outcome VARCHAR(20) NOT NULL,
            old_score DECIMAL(5,2) NOT NULL,
            new_score DECIMAL(5,2) NOT NULL,
            rationale TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY complaint_id (complaint_id),
            KEY attempt_id (attempt_id)
        ) $charset_collate;";
        dbDelta($sql19);

        // 20. Server-side archive ledger. Paths remain private and downloads are permission checked.
        $table_archives = $wpdb->prefix . 'zmp_competition_archives';
        $sql20 = "CREATE TABLE $table_archives (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            competition_id INT NOT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            file_path TEXT NOT NULL,
            drive_file_id VARCHAR(190) NOT NULL DEFAULT '',
            storage_backend VARCHAR(16) NOT NULL DEFAULT 'local',
            sha256 CHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY competition_id (competition_id)
        ) $charset_collate;";
        dbDelta($sql20);

        // Moderator-managed Word diploma templates, privately stored in Drive.
        $table_certificate_templates = $wpdb->prefix . 'zmp_certificate_templates';
        $sql21 = "CREATE TABLE $table_certificate_templates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            template_key CHAR(32) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            drive_file_id VARCHAR(191) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            uploaded_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category (season_id,category_id),
            KEY template_key (template_key),
            KEY drive_file_id (drive_file_id)
        ) $charset_collate;";
        dbDelta($sql21);
        // Immutable certificate issuance records preserve the first generated PDF and facsimile snapshot.
        $table_certificate_issuances = $wpdb->prefix . 'zmp_certificate_issuances';
        $sql22 = "CREATE TABLE $table_certificate_issuances (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            result_type VARCHAR(16) NOT NULL,
            result_id BIGINT(20) UNSIGNED NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            phase VARCHAR(20) NOT NULL,
            award_key VARCHAR(32) NOT NULL,
            template_key CHAR(32) NOT NULL,
            template_drive_file_id VARCHAR(191) NOT NULL,
            template_snapshot_drive_file_id VARCHAR(191) NOT NULL,
            template_sha256 CHAR(64) NOT NULL,
            facsimile_id BIGINT(20) UNSIGNED NOT NULL,
            facsimile_sha256 CHAR(64) NOT NULL,
            pdf_drive_file_id VARCHAR(191) NOT NULL,
            pdf_sha256 CHAR(64) NOT NULL,
            rendered_data_sha256 CHAR(64) NOT NULL,
            issued_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY result_snapshot (result_type,result_id,rendered_data_sha256),
            KEY season_category (season_id,category_id),
            KEY template_drive_file_id (template_drive_file_id),
            KEY facsimile_id (facsimile_id),
            KEY pdf_drive_file_id (pdf_drive_file_id)
        ) $charset_collate;";
        dbDelta($sql22);
        // Replace the pre-snapshot uniqueness rule so a corrected result can receive a new diploma while preserving the old PDF.
        $legacy_issuance_index = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=%s AND index_name=%s",
            $table_certificate_issuances, 'result_issuance'
        ));
        if ((int)$legacy_issuance_index > 0) { $wpdb->query("ALTER TABLE $table_certificate_issuances DROP INDEX result_issuance"); }

        // Backfill old records to the current season once, retaining legacy compatibility.
        $active_id = absint($wpdb->get_var("SELECT id FROM $table_competitions WHERE is_active = 1 ORDER BY id DESC LIMIT 1"));
        if ($active_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_students SET competition_id = %d WHERE competition_id = 0", $active_id));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_attempts SET competition_id = %d WHERE competition_id = 0", $active_id));
        }
        // Backfill legacy rows through their student/attempt links so annual reset is scoped safely.
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_incidents i JOIN {$wpdb->prefix}zmp_students s ON s.id=i.student_id SET i.competition_id=s.competition_id WHERE i.competition_id=0 AND i.student_id>0");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_complaints c JOIN {$wpdb->prefix}zmp_students s ON s.id=c.student_id SET c.competition_id=s.competition_id WHERE c.competition_id=0");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_disqualifications d JOIN {$wpdb->prefix}zmp_students s ON s.id=d.target_id SET d.competition_id=s.competition_id WHERE d.competition_id=0 AND d.target_type='student'");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_commission_decisions d JOIN {$wpdb->prefix}zmp_complaints c ON c.id=d.source_id SET d.competition_id=c.competition_id WHERE d.competition_id=0 AND d.source_type='complaint'");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_commission_decisions d JOIN {$wpdb->prefix}zmp_attempts a ON a.student_id=d.target_id SET d.competition_id=a.competition_id WHERE d.competition_id=0 AND d.target_type='student'");
        if ($active_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_testator_duties SET competition_id=%d WHERE competition_id=0", $active_id));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}zmp_internal_messages SET competition_id=%d WHERE competition_id=0", $active_id));
        }

        $competitions = $wpdb->get_col("SELECT id FROM $table_competitions");
        foreach ($competitions as $competition_id) {
            foreach (array('school', 'municipal', 'regional', 'state') as $phase) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_phase_status WHERE competition_id = %d AND phase = %s LIMIT 1",
                    absint($competition_id), $phase
                ));
                if (!$exists) {
                    $wpdb->insert($table_phase_status, array('competition_id' => absint($competition_id), 'phase' => $phase, 'status' => 'open'));
                }
            }
        }
    }

    public static function insert_initial_data() {
        global $wpdb;
        $table_competitions = $wpdb->prefix . 'zmp_competitions';
        $table_questions    = $wpdb->prefix . 'zmp_questions';
        
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_competitions");
        if (!$exists) {
            $wpdb->insert($table_competitions, array(
                'title'          => 'Државен натпревар по музичко образование 2026/2027',
                'academic_year'  => '2026/2027',
                'current_phase'  => 'school',
                'duration_minutes' => 45,
                'is_active'      => 1
            ));
        }
        $phase_table = $wpdb->prefix . 'zmp_phase_status';
        foreach ($wpdb->get_col("SELECT id FROM $table_competitions") as $competition_id) {
            foreach (array('school','municipal','regional','state') as $phase) {
                $phase_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $phase_table WHERE competition_id=%d AND phase=%s LIMIT 1", absint($competition_id), $phase));
                if (!$phase_exists) { $wpdb->insert($phase_table, array('competition_id'=>absint($competition_id),'phase'=>$phase,'status'=>'open')); }
            }
        }

        // Примерни почетни датуми за Календар на натпревари
        $table_calendar = $wpdb->prefix . 'zmp_calendar';
        $cal_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_calendar");
        if (!$cal_exists) {
            $wpdb->insert($table_calendar, array(
                'phase'            => 'school',
                'title'            => 'Училишен натпревар по музичко образование',
                'start_datetime'   => date('Y-m-d 08:00:00'),
                'end_datetime'     => date('Y-m-d 20:00:00', strtotime('+14 days')),
                'duration_minutes' => 45,
                'is_active'        => 1
            ));
            $wpdb->insert($table_calendar, array(
                'phase'            => 'municipal',
                'title'            => 'Општински натпревар по музичко образование',
                'start_datetime'   => date('Y-m-d 09:00:00', strtotime('+20 days')),
                'end_datetime'     => date('Y-m-d 18:00:00', strtotime('+21 days')),
                'duration_minutes' => 45,
                'is_active'        => 1
            ));
            $wpdb->insert($table_calendar, array(
                'phase'            => 'regional',
                'title'            => 'Регионален натпревар по музичко образование',
                'start_datetime'   => date('Y-m-d 09:00:00', strtotime('+40 days')),
                'end_datetime'     => date('Y-m-d 18:00:00', strtotime('+41 days')),
                'duration_minutes' => 45,
                'is_active'        => 1
            ));
            $wpdb->insert($table_calendar, array(
                'phase'            => 'state',
                'title'            => 'Државен натпревар по музичко образование',
                'start_datetime'   => date('Y-m-d 10:00:00', strtotime('+60 days')),
                'end_datetime'     => date('Y-m-d 16:00:00', strtotime('+60 days')),
                'duration_minutes' => 45,
                'is_active'        => 1
            ));
        }

        // Примерни двојазични прашања за тестирање доколку банката е празна
        $q_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions");
        if (!$q_exists) {
            // Прашање за VI одделение
            $wpdb->insert($table_questions, array(
                'grade' => 6,
                'phase' => 'all',
                'question_text_mk' => 'Кој од наведените инструменти спаѓа во групата на жичени гудачки инструменти?',
                'question_text_sq' => 'Cili nga instrumentet e mëposhtme bën pjesë në grupin e instrumenteve me hark?',
                'question_type' => 'multiple_choice',
                'options_json' => json_encode(array(
                    array('text_mk' => 'Виолина', 'text_sq' => 'Violina'),
                    array('text_mk' => 'Флејта', 'text_sq' => 'Flauti'),
                    array('text_mk' => 'Труба', 'text_sq' => 'Trumbeta'),
                    array('text_mk' => 'Тапан', 'text_sq' => 'Daullja')
                )),
                'correct_option_index' => 0,
                'points' => 10.00
            ));

            // Прашање за VII одделение
            $wpdb->insert($table_questions, array(
                'grade' => 7,
                'phase' => 'all',
                'question_text_mk' => 'Колку времиња трае цела нота во такт 4/4?',
                'question_text_sq' => 'Sa kohë zgjat nota e plotë në ritmin 4/4?',
                'question_type' => 'multiple_choice',
                'options_json' => json_encode(array(
                    array('text_mk' => '1 време', 'text_sq' => '1 kohë'),
                    array('text_mk' => '2 времиња', 'text_sq' => '2 kohë'),
                    array('text_mk' => '3 времиња', 'text_sq' => '3 kohë'),
                    array('text_mk' => '4 времиња', 'text_sq' => '4 kohë')
                )),
                'correct_option_index' => 3,
                'points' => 10.00
            ));

            // Прашање за VIII одделение
            $wpdb->insert($table_questions, array(
                'grade' => 8,
                'phase' => 'all',
                'question_text_mk' => 'Кој познат композитор ја напишал познатата „Ода на радоста“ (Деветта симфонија)?',
                'question_text_sq' => 'Cili kompozitor i njohur e shkroi të famshmen "Oda e gëzimit" (Simfonia e Nëntë)?',
                'question_type' => 'multiple_choice',
                'options_json' => json_encode(array(
                    array('text_mk' => 'Лудвиг ван Бетовен', 'text_sq' => 'Ludwig van Beethoven'),
                    array('text_mk' => 'Волфганг Амадеус Моцарт', 'text_sq' => 'Wolfgang Amadeus Mozart'),
                    array('text_mk' => 'Јохан Себастијан Бах', 'text_sq' => 'Johann Sebastian Bach'),
                    array('text_mk' => 'Франц Шуберт', 'text_sq' => 'Franz Schubert')
                )),
                'correct_option_index' => 0,
                'points' => 10.00
            ));

            $wpdb->insert($table_questions, array(
                'grade' => 9,
                'phase' => 'all',
                'question_text_mk' => 'Како се нарекува музичкиот облик компониран за еден соло инструмент и симфониски оркестар?',
                'question_text_sq' => 'Si quhet forma muzikore e kompozuar për një instrument solo dhe orkestër simfonike?',
                'question_type' => 'multiple_choice',
                'options_json' => json_encode(array(
                    array('text_mk' => 'Концерт', 'text_sq' => 'Koncert'),
                    array('text_mk' => 'Соната', 'text_sq' => 'Sonatë'),
                    array('text_mk' => 'Квартет', 'text_sq' => 'Kuartet'),
                    array('text_mk' => 'Симфонија', 'text_sq' => 'Simfoni')
                )),
                'correct_option_index' => 0,
                'points' => 10.00
            ));
        }

        // Почетни поставки за база и полиња
        $table_settings = $wpdb->prefix . 'zmp_settings';
        $set_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_settings");
        if (!$set_exists) {
            $wpdb->insert($table_settings, array(
                'db_host'               => '127.0.0.1',
                'db_user'               => defined('DB_USER') ? DB_USER : '',
                'db_pass'               => '',
                'db_name'               => defined('DB_NAME') ? DB_NAME : '',
                'custom_db_enabled'     => 0,
                'profile_fields_enabled'=> 1
            ));
        }
    }
}
