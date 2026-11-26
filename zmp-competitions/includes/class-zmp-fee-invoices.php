<?php
if (!defined('ABSPATH')) { exit; }

/** Separate, auditable invoice requests and delayed knowledge-test fees. */
class ZMP_Fee_Invoices {
    const NONCE = 'zmp_fee_invoice_batch';
    const SCHEMA = '2';

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_invoice_batches (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            idempotency_key CHAR(64) NOT NULL,
            request_status VARCHAR(24) NOT NULL DEFAULT 'submitted',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_idempotency (wp_user_id,idempotency_key),
            KEY season_user (season_id,wp_user_id,created_at)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_invoice_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id BIGINT(20) UNSIGNED NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(150) NOT NULL,
            school_address VARCHAR(255) NOT NULL,
            tax_number VARCHAR(50) NOT NULL,
            school_email VARCHAR(190) NOT NULL,
            personal_email VARCHAR(190) NOT NULL,
            teacher_name VARCHAR(190) NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            request_status VARCHAR(24) NOT NULL DEFAULT 'submitted',
            email_status VARCHAR(24) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY batch_school (batch_id,school_id),
            KEY user_season (wp_user_id,season_id,created_at),
            KEY school_season (school_id,season_id,created_at)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_invoice_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT(20) UNSIGNED NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            category_name_snapshot VARCHAR(190) NOT NULL,
            billing_basis_snapshot VARCHAR(20) NOT NULL,
            charge_trigger_snapshot VARCHAR(40) NOT NULL,
            unit_amount DECIMAL(10,2) NOT NULL,
            quantity INT(10) UNSIGNED NOT NULL,
            line_total DECIMAL(12,2) NOT NULL,
            exception_reason VARCHAR(500) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_category (request_id,category_id),
            KEY season_school (season_id,school_id,category_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_invoice_entries (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT(20) UNSIGNED NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            student_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category_student (season_id,category_id,student_id),
            KEY request_id (request_id),
            KEY school_season (school_id,season_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_exceptions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            exception_type VARCHAR(20) NOT NULL,
            override_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            reason VARCHAR(500) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY scope (season_id,category_id,school_id),
            KEY active_scope (season_id,category_id,is_active)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_settings_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            school_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            change_type VARCHAR(40) NOT NULL,
            before_json LONGTEXT NOT NULL,
            after_json LONGTEXT NOT NULL,
            reason VARCHAR(500) NOT NULL,
            changed_by BIGINT(20) UNSIGNED NOT NULL,
            changed_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY scope (season_id,category_id,school_id,changed_at)
        ) $c;");
        if (get_option('zmp_fee_payment_details', false) === false) {
            add_option('zmp_fee_payment_details', array(
                'account_number' => '300000001410804',
                'bank_name' => 'Комерцијална банка',
                'tax_number' => '4030001439759',
            ), '', false);
        }
        update_option('zmp_fee_invoice_schema', self::SCHEMA, false);
    }

    public static function init() {
        add_action('admin_post_zmp_submit_invoice_batch', array(__CLASS__, 'handle_submit_batch'));
        add_action('admin_post_zmp_upload_knowledge_fee_proof', array(__CLASS__, 'handle_knowledge_fee_proof'));
        add_action('admin_post_zmp_save_fee_exception', array(__CLASS__, 'save_fee_exception'));
        add_action('admin_post_zmp_remove_fee_exception', array(__CLASS__, 'remove_fee_exception'));
        add_action('admin_post_zmp_save_fee_payment_details', array(__CLASS__, 'save_payment_details'));
    }

    private static function can_moderate() {
        return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('zmp_moderator_cap'));
    }

    private static function money_to_minor($value) {
        $value = trim((string) $value);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/', $value)) { return false; }
        $parts = explode('.', $value, 2);
        return ((int) $parts[0] * 100) + (int) str_pad(isset($parts[1]) ? $parts[1] : '0', 2, '0');
    }

    private static function minor_to_decimal($minor) {
        $minor = max(0, (int) $minor);
        return (string) intdiv($minor, 100) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Use integer minor units so floating-point rounding cannot change invoice totals. */
    public static function calculate_minor_total($lines) {
        $total = 0;
        foreach ((array) $lines as $line) {
            $unit = isset($line['unit_minor']) ? (int) $line['unit_minor'] : 0;
            $quantity = isset($line['quantity']) ? (int) $line['quantity'] : 0;
            if ($unit < 0 || $quantity < 1 || $quantity > 500 || $unit > 100000000) { return false; }
            $subtotal = $unit * $quantity;
            if ($subtotal > 1000000000 || $total > 1000000000 - $subtotal) { return false; }
            $total += $subtotal;
        }
        return $total;
    }

    /** Unpaid, not-yet-invoiced municipal qualifiers available for a new invoice. */
    public static function eligible_knowledge_entries($season_id, $school_id = 0) {
        return self::query_qualified_knowledge_entries($season_id, $school_id, true, true);
    }

    /** Qualification is independent of an invoice or an uploaded payment proof. */
    public static function qualified_knowledge_entries($season_id, $school_id = 0) {
        return self::query_qualified_knowledge_entries($season_id, $school_id, false, false);
    }

    private static function query_qualified_knowledge_entries($season_id, $school_id, $exclude_paid, $exclude_invoiced) {
        global $wpdb;
        $season_id = absint($season_id);
        $season = $wpdb->get_row($wpdb->prepare(
            "SELECT academic_year,legacy_competition_id FROM {$wpdb->prefix}zmp_seasons WHERE id=%d", $season_id
        ));
        if (!$season || !(int)$season->legacy_competition_id) { return array(); }
        $sql = "SELECT DISTINCT s.id AS student_id,s.id AS entry_id,s.mentor_id,s.mentor_school_id,s.access_code,
                       s.first_name,s.last_name,p.id AS result_id,p.total_points,p.award_level,p.completed_at
                FROM {$wpdb->prefix}zmp_phase_results p
                INNER JOIN {$wpdb->prefix}zmp_students s ON s.id=p.student_id
                INNER JOIN {$wpdb->prefix}zmp_phase_status ps ON ps.competition_id=p.competition_id AND ps.phase=p.phase AND ps.status='closed'
                WHERE p.competition_id=%d AND p.academic_year=%s AND p.phase='municipal' AND p.qualified_next_stage=1
                  AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}zmp_phase_results newer
                    WHERE newer.competition_id=p.competition_id AND newer.student_id=p.student_id AND newer.phase='municipal'
                      AND (newer.completed_at>p.completed_at OR (newer.completed_at=p.completed_at AND newer.id>p.id))
                  )";
        $args = array((int)$season->legacy_competition_id,(string)$season->academic_year);
        if ($exclude_paid) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}zmp_student_documents pd WHERE pd.student_id=s.id AND pd.season_id=%d AND pd.document_type='payment_proof')";
            $args[] = $season_id;
        }
        if ($exclude_invoiced) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}zmp_fee_invoice_entries ie WHERE ie.student_id=s.id AND ie.season_id=%d AND ie.category_id=(SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' ORDER BY is_active DESC,id DESC LIMIT 1))";
            $args[] = $season_id;
        }
        if (absint($school_id)) { $sql .= ' AND s.mentor_school_id=%d'; $args[] = absint($school_id); }
        $sql .= ' ORDER BY p.completed_at DESC,p.id DESC';
        $rows = (array)$wpdb->get_results($wpdb->prepare($sql,$args));
        $unique = array();
        foreach ($rows as $row) {
            $id = (int)$row->student_id;
            if (!isset($unique[$id])) { $unique[$id] = $row; }
        }
        return array_values($unique);
    }

    public static function knowledge_student_qualified($season_id, $student_id) {
        $student_id = absint($student_id);
        if (!$student_id) { return false; }
        foreach (self::qualified_knowledge_entries($season_id, 0) as $row) {
            if ((int)$row->student_id === $student_id) { return true; }
        }
        return false;
    }

    private static function eligible_knowledge_count($season_id, $school_id) {
        return count(self::eligible_knowledge_entries($season_id, $school_id));
    }

    private static function get_exception($season_id, $category_id, $school_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}zmp_fee_exceptions WHERE season_id=%d AND category_id=%d AND school_id IN (0,%d) AND is_active=1 ORDER BY school_id DESC LIMIT 1",
            absint($season_id), absint($category_id), absint($school_id)
        ));
    }

    /** Resolve the active school-specific or global moderator fee exception. */
    public static function effective_fee_amount($season_id,$category_id,$school_id,$base_amount) {
        $base_minor=self::money_to_minor($base_amount);
        if($base_minor===false)return '0.00';
        $exception=self::get_exception(absint($season_id),absint($category_id),absint($school_id));
        if($exception&&$exception->exception_type==='exempt')return '0.00';
        if($exception&&$exception->exception_type==='override'){
            $override_minor=self::money_to_minor($exception->override_amount);
            return $override_minor===false?'0.00':self::minor_to_decimal($override_minor);
        }
        return self::minor_to_decimal($base_minor);
    }
    private static function teacher_context($season_id) {
        global $wpdb;
        if (!is_user_logged_in() || !current_user_can('zmp_teacher_cap')) { return new WP_Error('fee_forbidden'); }
        $user_id = get_current_user_id();
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d", $user_id));
        $season = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d", absint($season_id)));
        if (!$mentor || !$season) { return new WP_Error('fee_context_invalid'); }
        return array('user_id'=>$user_id,'mentor'=>$mentor,'season'=>$season);
    }

    public static function submit_batch($season_id, $items, $school_details, $idempotency_key) {
        global $wpdb;
        $context = self::teacher_context($season_id);
        if (is_wp_error($context)) { return $context; }
        $season_id = absint($season_id); $items = (array)$items; $school_details = (array)$school_details;
        $idempotency_key = sanitize_text_field((string)$idempotency_key);
        if (!preg_match('/^[a-f0-9-]{32,64}$/i', $idempotency_key) || !$items || count($items)>50) { return new WP_Error('fee_batch_invalid'); }
        $key_hash = hash('sha256', strtolower($idempotency_key));
        $batch_table = $wpdb->prefix.'zmp_fee_invoice_batches';
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $batch_table WHERE wp_user_id=%d AND idempotency_key=%s", $context['user_id'], $key_hash));
        if ($existing) { return self::batch_result((int)$existing->id,true); }

        $groups = array();
        foreach ($items as $item) {
            if (!is_array($item)) { return new WP_Error('fee_item_invalid'); }
            $school_id=absint($item['school_id']??0);$category_id=absint($item['category_id']??0);
            $school=ZMP_Mentor_Schools::owned_school((int)$context['mentor']->id,$school_id);
            if (!$school||!$category_id) { return new WP_Error('fee_school_forbidden'); }
            $category=$wpdb->get_row($wpdb->prepare(
                "SELECT c.id,c.name_mk,c.category_code,f.fee_amount,f.billing_basis,f.payment_instructions,f.charge_trigger
                 FROM {$wpdb->prefix}zmp_categories c
                 INNER JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1
                 LEFT JOIN {$wpdb->prefix}zmp_category_fees f ON f.season_id=sc.season_id AND f.category_id=c.id
                 WHERE c.id=%d AND c.is_active=1",$season_id,$category_id
            ));
            if (!$category||$category->fee_amount===null||!in_array((string)$category->billing_basis,array('entry','participant'),true)) { return new WP_Error('fee_unavailable'); }
            $trigger=(string)($category->charge_trigger?:'at_registration');
            $quantity=isset($item['quantity'])&&ctype_digit((string)$item['quantity'])?(int)$item['quantity']:0;
            $knowledge_student_ids=array();
            if ($category->category_code==='knowledge_test'&&$trigger==='after_regional_qualification') {
                $knowledge_entries=self::eligible_knowledge_entries($season_id,$school_id);
                $knowledge_student_ids=array_values(array_unique(array_map(static function($entry){return (int)$entry->student_id;},$knowledge_entries)));
                $quantity=count($knowledge_student_ids);
            }
            if ($quantity<1||$quantity>500) { return new WP_Error('fee_quantity_invalid'); }
            $exception=self::get_exception($season_id,$category_id,$school_id);
            if ($exception&&$exception->exception_type==='exempt') { continue; }
            $unit_minor=self::money_to_minor($exception&&$exception->exception_type==='override'?$exception->override_amount:$category->fee_amount);
            if ($unit_minor===false||$unit_minor<0) { return new WP_Error('fee_amount_invalid'); }
            if (!isset($groups[$school_id])) { $groups[$school_id]=array('school'=>$school,'items'=>array()); }
            if (isset($groups[$school_id]['items'][$category_id])) {
                if ($category->category_code==='knowledge_test'&&$trigger==='after_regional_qualification') { return new WP_Error('fee_item_duplicate'); }
                $groups[$school_id]['items'][$category_id]['quantity']+=$quantity;
                if ($groups[$school_id]['items'][$category_id]['quantity']>500) { return new WP_Error('fee_quantity_invalid'); }
            } else {
                $groups[$school_id]['items'][$category_id]=array('category'=>$category,'quantity'=>$quantity,'unit_minor'=>$unit_minor,'trigger'=>$trigger,'exception'=>$exception,'knowledge_student_ids'=>$knowledge_student_ids);
            }
        }
        if (!$groups) { return new WP_Error('fee_batch_empty'); }

        $teacher=wp_get_current_user();$now=current_time('mysql');
        $wpdb->query('START TRANSACTION');
        $created=$wpdb->insert($batch_table,array('season_id'=>$season_id,'mentor_id'=>(int)$context['mentor']->id,'wp_user_id'=>(int)$context['user_id'],'idempotency_key'=>$key_hash,'request_status'=>'submitted','created_at'=>$now));
        if (!$created) {
            $wpdb->query('ROLLBACK');
            $existing=$wpdb->get_row($wpdb->prepare("SELECT id FROM $batch_table WHERE wp_user_id=%d AND idempotency_key=%s",$context['user_id'],$key_hash));
            return $existing?self::batch_result((int)$existing->id,true):new WP_Error('fee_batch_save_failed');
        }
        $batch_id=(int)$wpdb->insert_id;$request_ids=array();$totals=array('grand_total'=>0,'by_school'=>array());$mail_jobs=array();
        foreach ($groups as $school_id=>$group) {
            $details=(array)($school_details[$school_id]??array());
            $address=sanitize_text_field(wp_unslash($details['school_address']??''));$tax=sanitize_text_field(wp_unslash($details['tax_number']??''));
            $school_email=sanitize_email(wp_unslash($details['school_email']??''));$personal_email=sanitize_email(wp_unslash($details['personal_email']??''));
            $teacher_name=sanitize_text_field(wp_unslash($details['teacher_name']??$teacher->display_name));
            if (!$address||!$tax||!is_email($school_email)||!is_email($personal_email)||!$teacher_name) { $wpdb->query('ROLLBACK');return new WP_Error('fee_invoice_details_invalid'); }
            $official_school_email=ZMP_Registration_Requirements::official_school_contact_for_invoice($group['school']->school_name,$group['school']->municipality);
            if (!$official_school_email||strtolower($school_email)!==strtolower($official_school_email)) { $wpdb->query('ROLLBACK');return new WP_Error('fee_invoice_school_email_unverified'); }
            if (!is_email($teacher->user_email)||strtolower($personal_email)!==strtolower($teacher->user_email)) { $wpdb->query('ROLLBACK');return new WP_Error('fee_invoice_personal_email_mismatch'); }
            $school_email=$official_school_email;
            $line_specs=array();foreach($group['items'] as $entry)$line_specs[]=array('unit_minor'=>$entry['unit_minor'],'quantity'=>$entry['quantity']);
            $school_total=self::calculate_minor_total($line_specs);
            if ($school_total===false||$school_total<1) { $wpdb->query('ROLLBACK');return new WP_Error('fee_total_invalid'); }
            $school=$group['school'];
            $ok=$wpdb->insert($wpdb->prefix.'zmp_fee_invoice_requests',array(
                'batch_id'=>$batch_id,'season_id'=>$season_id,'school_id'=>(int)$school_id,'mentor_id'=>(int)$context['mentor']->id,'wp_user_id'=>(int)$context['user_id'],
                'school_name'=>sanitize_text_field($school->school_name),'municipality'=>sanitize_text_field($school->municipality),'school_address'=>$address,'tax_number'=>$tax,
                'school_email'=>$school_email,'personal_email'=>$personal_email,'teacher_name'=>$teacher_name,'total_amount'=>self::minor_to_decimal($school_total),
                'request_status'=>'submitted','email_status'=>'pending','created_at'=>$now
            ));
            if (!$ok) { $wpdb->query('ROLLBACK');return new WP_Error('fee_request_save_failed'); }
            $request_id=(int)$wpdb->insert_id;$request_ids[(int)$school_id]=$request_id;$totals['by_school'][(int)$school_id]=self::minor_to_decimal($school_total);$totals['grand_total']+=$school_total;$lines=array();
            foreach ($group['items'] as $category_id=>$entry) {
                $line_total=$entry['unit_minor']*$entry['quantity'];
                $saved=$wpdb->insert($wpdb->prefix.'zmp_fee_invoice_items',array(
                    'request_id'=>$request_id,'season_id'=>$season_id,'school_id'=>(int)$school_id,'category_id'=>(int)$category_id,'category_name_snapshot'=>sanitize_text_field($entry['category']->name_mk),
                    'billing_basis_snapshot'=>(string)$entry['category']->billing_basis,'charge_trigger_snapshot'=>$entry['trigger'],'unit_amount'=>self::minor_to_decimal($entry['unit_minor']),
                    'quantity'=>(int)$entry['quantity'],'line_total'=>self::minor_to_decimal($line_total),'exception_reason'=>$entry['exception']?sanitize_text_field($entry['exception']->reason):'','created_at'=>$now
                ));
                if (!$saved) { $wpdb->query('ROLLBACK');return new WP_Error('fee_item_save_failed'); }
                foreach ((array)$entry['knowledge_student_ids'] as $student_id) {
                    if (!self::knowledge_student_qualified($season_id,$student_id)) {
                        $wpdb->query('ROLLBACK');
                        return new WP_Error('fee_knowledge_changed');
                    }
                    $linked=$wpdb->insert($wpdb->prefix.'zmp_fee_invoice_entries',array(
                        'request_id'=>$request_id,'season_id'=>$season_id,'school_id'=>(int)$school_id,'category_id'=>(int)$category_id,
                        'student_id'=>(int)$student_id,'created_at'=>$now
                    ));
                    if (!$linked) {
                        $wpdb->query('ROLLBACK');
                        return new WP_Error('fee_knowledge_already_invoiced');
                    }
                }
                $lines[]=sanitize_text_field($entry['category']->name_mk).' × '.(int)$entry['quantity'].' — '.number_format_i18n($line_total/100,2).' ден.';
            }
            $mail_jobs[]=array('request_id'=>$request_id,'school_email'=>$school_email,'personal_email'=>$personal_email,'subject'=>'Барање за фактура — '.$context['season']->academic_year,
                'body'=>"Барање за фактура\nНаставник: {$teacher_name}\nУчилиште: {$school->school_name}\nОпштина: {$school->municipality}\nАдреса: {$address}\nЕДБ: {$tax}\nГодина: {$context['season']->academic_year}\n\n".implode("\n",$lines)."\n\nВкупно: ".number_format_i18n($school_total/100,2).' денари.');
        }
        $wpdb->query('COMMIT');
        $totals['grand_total']=self::minor_to_decimal($totals['grand_total']);
        foreach ($mail_jobs as $job) {
            $association=sanitize_email(get_option('admin_email'));
            $to_school=wp_mail($job['school_email'],$job['subject'],$job['body']);
            $to_teacher=wp_mail($job['personal_email'],$job['subject'],$job['body']);
            $to_association=is_email($association)?wp_mail($association,$job['subject'],$job['body']):false;
            $status=$to_school&&$to_teacher&&$to_association?'sent':(($to_school||$to_teacher||$to_association)?'partial_failure':'failed');
            $wpdb->update($wpdb->prefix.'zmp_fee_invoice_requests',array('email_status'=>$status),array('id'=>$job['request_id']));
        }
        return array('batch_id'=>$batch_id,'request_ids'=>$request_ids,'totals'=>$totals,'duplicate'=>false);
    }

    private static function batch_result($batch_id,$duplicate) {
        global $wpdb;
        $rows=(array)$wpdb->get_results($wpdb->prepare("SELECT id,school_id,total_amount FROM {$wpdb->prefix}zmp_fee_invoice_requests WHERE batch_id=%d ORDER BY id",absint($batch_id)));
        $request_ids=array();$by_school=array();$grand=0;
        foreach($rows as $row){$request_ids[(int)$row->school_id]=(int)$row->id;$minor=self::money_to_minor($row->total_amount);$by_school[(int)$row->school_id]=self::minor_to_decimal($minor===false?0:$minor);$grand+=$minor===false?0:$minor;}
        return array('batch_id'=>absint($batch_id),'request_ids'=>$request_ids,'totals'=>array('grand_total'=>self::minor_to_decimal($grand),'by_school'=>$by_school),'duplicate'=>(bool)$duplicate);
    }

    public static function handle_submit_batch() {
        if(!is_user_logged_in()||!current_user_can('zmp_teacher_cap'))wp_die('Најавете се како наставник.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);
        $season_id=absint($_POST['season_id']??0);$raw=(array)wp_unslash($_POST['items']??array());$items=array();
        foreach($raw as $school_id=>$categories)foreach((array)$categories as $category_id=>$details)if(!empty($details['selected']))$items[]=array('school_id'=>absint($school_id),'category_id'=>absint($category_id),'quantity'=>sanitize_text_field($details['quantity']??''));
        $details=(array)wp_unslash($_POST['school_details']??array());$key=sanitize_text_field(wp_unslash($_POST['idempotency_key']??''));
        if(!preg_match('/^[a-f0-9-]{32,64}$/i',$key))$key=wp_generate_uuid4();
        $result=self::submit_batch($season_id,$items,$details,$key);$status=is_wp_error($result)?$result->get_error_code():(!empty($result['duplicate'])?'invoice_duplicate':'invoice_submitted');
        $url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_requirements_status',sanitize_key($status),$url));exit;
    }

    /** Separate teacher workflow; registration and personal payment remain independent. */
    public static function render_teacher_portal($season) {
        if (!is_user_logged_in() || !current_user_can('zmp_teacher_cap') || !is_object($season) || empty($season->id)) { return ''; }
        global $wpdb;
        $mentor=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d",get_current_user_id()));
        if(!$mentor)return '<section class="zmp-card"><h4>Барања за фактура</h4><p>Прво пополнете го профилот на наставникот.</p></section>';
        $schools=(array)ZMP_Mentor_Schools::for_mentor((int)$mentor->id);
        $categories=(array)$wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk,c.category_code,f.fee_amount,f.billing_basis,f.charge_trigger FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 LEFT JOIN {$wpdb->prefix}zmp_category_fees f ON f.season_id=sc.season_id AND f.category_id=c.id WHERE c.is_active=1 ORDER BY c.sort_order,c.id",(int)$season->id));
        $user=wp_get_current_user();$form=ZMP_Registration_Requirements::get_form((int)$season->id);$details=(array)get_option('zmp_fee_payment_details',array());
        $requests=(array)$wpdb->get_results($wpdb->prepare("SELECT r.* FROM {$wpdb->prefix}zmp_fee_invoice_requests r WHERE r.wp_user_id=%d AND r.season_id=%d ORDER BY r.id DESC LIMIT 20",get_current_user_id(),(int)$season->id));
        ob_start(); ?>
        <section class="zmp-card zmp-fee-invoice-portal">
            <h4>Барања за фактура</h4>
            <p>Барањето за фактура е одделно од пријавувањето и личната уплата. Изберете повеќе категории. За секое училиште се создава посебно барање; цените и вкупните суми ги пресметува серверот според ценовникот на годината.</p>
            <?php if($form): ?><p><a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg('zmp_registration_form',(int)$season->id,home_url('/')),'zmp_form_'.(int)$season->id)); ?>">Преземи годишен формулар за пријава</a></p><?php else: ?><p>Модераторите сè уште немаат поставено годишен формулар за пријава.</p><?php endif; ?>
            <h5>Податоци за уплата</h5><dl><dt>Жиро-сметка</dt><dd><?php echo esc_html($details['account_number']??''); ?></dd><dt>Банка</dt><dd><?php echo esc_html($details['bank_name']??''); ?></dd><dt>ЕДБ</dt><dd><?php echo esc_html($details['tax_number']??''); ?></dd></dl>
            <?php if(!$schools): ?><p>Прво додајте училиште во профилот.</p><?php else: ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form zmp-fee-invoice-form">
                <?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_submit_invoice_batch"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>"><input type="hidden" name="idempotency_key" value="<?php echo esc_attr(wp_generate_uuid4()); ?>">
                <?php foreach($schools as $school): $contact=ZMP_Registration_Requirements::official_school_contact_for_invoice($school->school_name,$school->municipality); ?>
                    <fieldset><legend><?php echo esc_html($school->school_name.' · '.$school->municipality); ?></legend>
                        <div class="zmp-form-row"><label>Адреса на училиштето *<input name="school_details[<?php echo (int)$school->id; ?>][school_address]" maxlength="255" required></label><label>ЕДБ *<input name="school_details[<?php echo (int)$school->id; ?>][tax_number]" maxlength="50" required></label></div>
                        <div class="zmp-form-row"><label>Службена е-пошта на училиштето *<input name="school_details[<?php echo (int)$school->id; ?>][school_email]" type="email" maxlength="190" value="<?php echo esc_attr($contact); ?>" readonly required aria-describedby="school-email-help-<?php echo (int)$school->id; ?>"><small id="school-email-help-<?php echo (int)$school->id; ?>"><?php echo $contact?'Потврдената службена адреса на училиштето.':'Модераторот треба да ја потврди службената адреса пред барање за фактура.'; ?></small></label><label>Е-пошта на наставникот *<input name="school_details[<?php echo (int)$school->id; ?>][personal_email]" type="email" maxlength="190" value="<?php echo esc_attr($user->user_email); ?>" readonly required></label></div>
                        <label>Име и презиме на наставникот *<input name="school_details[<?php echo (int)$school->id; ?>][teacher_name]" maxlength="190" value="<?php echo esc_attr($user->display_name); ?>" required></label>
                        <div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Единична цена</th><th>Број</th><th>Вклучи</th></tr></thead><tbody>
                        <?php foreach($categories as $category):
                            $exception=self::get_exception((int)$season->id,(int)$category->id,(int)$school->id);
                            $exempt=$exception&&$exception->exception_type==='exempt';
                            $fee=(string)($exception&&$exception->exception_type==='override'?$exception->override_amount:($category->fee_amount??''));
                            $knowledge_delayed=$category->category_code==='knowledge_test'&&($category->charge_trigger??'')==='after_regional_qualification';
                            $qualified_count=$knowledge_delayed?self::eligible_knowledge_count((int)$season->id,(int)$school->id):0;
                            if($knowledge_delayed&&$qualified_count<1)continue;
                        ?><tr><td><?php echo esc_html($category->name_mk.($knowledge_delayed?' · квалификувани за регионално ниво':'').' — '.(($category->billing_basis??'entry')==='participant'?'по учесник':'по пријава/состав')); ?></td>
                            <td data-unit-minor="<?php $unit_minor=self::money_to_minor($fee); echo $unit_minor===false?0:(int)$unit_minor; ?>"><?php echo $exempt?'Ослободено со одлука на модератор':($fee!==''?esc_html(number_format_i18n((float)$fee,2).' ден.'):'Не е поставена'); ?></td>
                            <td><input type="number" name="items[<?php echo (int)$school->id; ?>][<?php echo (int)$category->id; ?>][quantity]" min="1" max="500" value="<?php echo $knowledge_delayed?(int)$qualified_count:1; ?>" <?php echo $knowledge_delayed?'readonly aria-label="Се пресметува според квалификуваните ученици"':''; ?>></td>
                            <td><input type="checkbox" name="items[<?php echo (int)$school->id; ?>][<?php echo (int)$category->id; ?>][selected]" value="1" aria-label="Вклучи <?php echo esc_attr($category->name_mk); ?>" <?php echo (!$contact||$exempt||$fee===''||(float)$fee<=0)?'disabled':''; ?>></td></tr><?php endforeach; ?>
                        </tbody></table></div><p>Вкупно за ова училиште: <output data-school-total>0,00 ден.</output></p>
                    </fieldset>
                <?php endforeach; ?>
                <p class="zmp-fee-invoice-total">Вкупно избрано: <output data-grand-total>0,00 ден.</output></p><button class="zmp-btn zmp-btn-primary" type="submit">Поднеси избрани барања за фактура</button>
            </form>
            <script>(function(){var form=document.querySelector('.zmp-fee-invoice-form');if(!form)return;var grand=form.querySelector('[data-grand-total]');function fmt(minor){return new Intl.NumberFormat('mk-MK',{minimumFractionDigits:2,maximumFractionDigits:2}).format(minor/100)+' ден.';}function sync(){var total=0;form.querySelectorAll('fieldset').forEach(function(box){var subtotal=0;var fields=box.querySelectorAll('input[name*="school_details"]'),checks=box.querySelectorAll('input[type="checkbox"][name*="[selected]"]');var active=Array.prototype.some.call(checks,function(c){return c.checked;});fields.forEach(function(f){f.required=active;f.disabled=!active;});box.querySelectorAll('tbody tr').forEach(function(row){var check=row.querySelector('input[type="checkbox"]'),qty=row.querySelector('input[type="number"]'),unit=row.querySelector('[data-unit-minor]');if(check&&check.checked&&qty&&unit){var q=Math.max(0,parseInt(qty.value||'0',10)||0),minor=parseInt(unit.getAttribute('data-unit-minor')||'0',10)||0;subtotal+=q*minor;total+=q*minor;}});var schoolTotal=box.querySelector("[data-school-total]");if(schoolTotal)schoolTotal.textContent=fmt(subtotal);});if(grand)grand.textContent=fmt(total);}form.addEventListener('change',sync);form.addEventListener('input',sync);sync();})();</script>
            <?php endif; ?>

            <?php if($requests): ?><h5>Мои барања за фактура</h5><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Училиште</th><th>Вкупно</th><th>Статус на барање</th><th>Е-пошта</th><th>Датум</th></tr></thead><tbody><?php foreach($requests as $request): ?><tr><td><?php echo esc_html($request->school_name); ?></td><td><?php echo esc_html(number_format_i18n((float)$request->total_amount,2).' ден.'); ?></td><td><?php echo esc_html($request->request_status); ?></td><td><?php echo esc_html($request->email_status); ?></td><td><?php echo esc_html($request->created_at); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>

            <?php self::render_qualified_knowledge_proofs($season,$mentor); ?>
        </section>
        <?php return ob_get_clean();
    }

    private static function render_qualified_knowledge_proofs($season,$mentor) {
        global $wpdb;
        $category_id=(int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' AND is_active=1 LIMIT 1");
        $fee=$category_id?ZMP_Registration_Requirements::get_fee((int)$season->id,$category_id):null;
        if(!$fee||($fee->charge_trigger??'')!=='after_regional_qualification')return;
        $rows=self::qualified_knowledge_entries((int)$season->id,0);
        $owned=array();foreach((array)ZMP_Mentor_Schools::for_mentor((int)$mentor->id) as $school)$owned[(int)$school->id]=$school;
        foreach($rows as $student){
            $school_id=(int)$student->mentor_school_id;
            if(!isset($owned[$school_id])||(int)$student->mentor_id!==(int)$mentor->id)continue;$effective_fee=self::effective_fee_amount((int)$season->id,$category_id,$school_id,(float)$fee->fee_amount);if($effective_fee<0.01)continue;
            $has=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_student_documents WHERE student_id=%d AND season_id=%d AND document_type='payment_proof'",(int)$student->student_id,(int)$season->id));
            if($has)continue;
            ?><details class="zmp-knowledge-fee-proof"><summary>Котизација за регионално ниво — <?php echo esc_html($student->access_code); ?> · <?php echo esc_html($owned[$school_id]->school_name); ?></summary>
                <p>Ученикот е потврден како пласиран на регионално ниво. Прикачете доказ за уплата или поднесете барање за фактура погоре.</p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form">
                    <?php wp_nonce_field('zmp_knowledge_fee_proof'); ?><input type="hidden" name="action" value="zmp_upload_knowledge_fee_proof"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>"><input type="hidden" name="school_id" value="<?php echo $school_id; ?>"><input type="hidden" name="student_id" value="<?php echo (int)$student->student_id; ?>">
                    <label>Кој ја извршил уплатата<select name="payer_type" required><option value="teacher">Наставникот</option><option value="school">Училиштето</option></select></label><label>Доказ за уплата (PDF/JPG/PNG) *<input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" required></label><button class="zmp-btn zmp-btn-primary">Прикачи доказ за уплата</button>
                </form></details><?php
        }
    }

    /** Moderator controls for payment details and scoped fee exceptions. */
    public static function render_moderator_panel($season) {
        if(!self::can_moderate())return '';
        global $wpdb;$details=(array)get_option('zmp_fee_payment_details',array());
        $categories=$season?(array)$wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 ORDER BY c.sort_order",(int)$season->id)):array();
        $schools=(array)$wpdb->get_results("SELECT id,school_name,municipality FROM {$wpdb->prefix}zmp_mentor_schools ORDER BY school_name LIMIT 500");
        $exceptions=$season?(array)$wpdb->get_results($wpdb->prepare("SELECT e.*,c.name_mk FROM {$wpdb->prefix}zmp_fee_exceptions e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id WHERE e.season_id=%d ORDER BY e.updated_at DESC LIMIT 50",(int)$season->id)):array();
        $audit=(array)$wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_fee_settings_audit ORDER BY id DESC LIMIT 20");
        ob_start(); ?>
        <section class="zmp-card"><h4>Податоци за уплата</h4><p>Модераторите можат да ги ажурираат; секоја измена се запишува со образложение.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_save_fee_payment_details">
                <label>Жиро-сметка<input name="account_number" maxlength="64" value="<?php echo esc_attr($details['account_number']??''); ?>" required></label><label>Банка<input name="bank_name" maxlength="190" value="<?php echo esc_attr($details['bank_name']??''); ?>" required></label><label>ЕДБ<input name="tax_number" maxlength="64" value="<?php echo esc_attr($details['tax_number']??''); ?>" required></label><label>Образложение *<textarea name="reason" minlength="10" maxlength="500" required></textarea></label><button class="zmp-btn zmp-btn-primary">Зачувај податоци за уплата</button>
            </form>
        </section>
        <?php if($season&&$categories): ?><section class="zmp-card"><h4>Исклучоци и корекции на котизација</h4>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_save_fee_exception"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>">
                <label>Категорија<select name="category_id" required><?php foreach($categories as $cat): ?><option value="<?php echo (int)$cat->id; ?>"><?php echo esc_html($cat->name_mk); ?></option><?php endforeach; ?></select></label>
                <label>Училиште (празно = сите)<select name="school_id"><option value="0">Сите училишта</option><?php foreach($schools as $school): ?><option value="<?php echo (int)$school->id; ?>"><?php echo esc_html($school->school_name.' · '.$school->municipality); ?></option><?php endforeach; ?></select></label>
                <label>Правило<select name="exception_type" onchange="var a=this.form.querySelector('[name=override_amount]');a.required=this.value==='override';a.min=a.required?'0.01':'0';"><option value="exempt">Ослободување</option><option value="override">Посебен износ</option></select></label><label>Посебен износ<input type="number" name="override_amount" min="0" max="1000000" step="0.01" value="0"></label>
                <label>Образложение *<textarea name="reason" minlength="10" maxlength="500" required></textarea></label><button class="zmp-btn zmp-btn-primary">Зачувај правило</button>
            </form>
            <?php if($exceptions): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Училиште</th><th>Правило</th><th>Износ</th><th>Образложение</th><th>Активно</th><th>Дејство</th></tr></thead><tbody><?php foreach($exceptions as $e): ?><tr><td><?php echo esc_html($e->name_mk); ?></td><td><?php echo (int)$e->school_id?esc_html((string)$wpdb->get_var($wpdb->prepare("SELECT school_name FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d",(int)$e->school_id))):'Сите'; ?></td><td><?php echo esc_html($e->exception_type); ?></td><td><?php echo esc_html(number_format_i18n((float)$e->override_amount,2).' ден.'); ?></td><td><?php echo esc_html($e->reason); ?></td><td><?php echo (int)$e->is_active?'Да':'Не'; ?></td><td><?php if((int)$e->is_active): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form zmp-inline-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_remove_fee_exception"><input type="hidden" name="exception_id" value="<?php echo (int)$e->id; ?>"><label>Причина за повлекување<textarea name="reason" minlength="10" maxlength="500" required></textarea></label><button class="zmp-btn" type="submit">Повлечи правило</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </section><?php endif; ?>
        <?php if($audit): ?><section class="zmp-card"><h4>Ревизорска трага на поставките</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Вид промена</th><th>Образложение</th><th>Корисник</th><th>Време</th></tr></thead><tbody><?php foreach($audit as $a): ?><tr><td><?php echo esc_html($a->change_type); ?></td><td><?php echo esc_html($a->reason); ?></td><td><?php echo (int)$a->changed_by; ?></td><td><?php echo esc_html($a->changed_at); ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
        <?php return ob_get_clean();
    }

    public static function save_payment_details() {
        if(!self::can_moderate())wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);
        $next=array('account_number'=>sanitize_text_field(wp_unslash($_POST['account_number']??'')),'bank_name'=>sanitize_text_field(wp_unslash($_POST['bank_name']??'')),'tax_number'=>sanitize_text_field(wp_unslash($_POST['tax_number']??'')));
        $reason=sanitize_textarea_field(wp_unslash($_POST['reason']??''));
        if(!$next['account_number']||!$next['bank_name']||!$next['tax_number']||mb_strlen($reason)<10||mb_strlen($reason)>500)self::redirect('fee_settings_invalid');
        $before=(array)get_option('zmp_fee_payment_details',array());
        if($before!==$next){
            global $wpdb;$wpdb->query('START TRANSACTION');
            if(!self::audit_change(0,0,0,'payment_details',$before,$next,$reason)){$wpdb->query('ROLLBACK');self::redirect('save_error');}
            update_option('zmp_fee_payment_details',$next,false);$wpdb->query('COMMIT');
        }
        self::redirect('fee_settings_saved');
    }

    public static function save_fee_exception() {
        if(!self::can_moderate())wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);global $wpdb;
        $season_id=absint($_POST['season_id']??0);$category_id=absint($_POST['category_id']??0);$school_id=absint($_POST['school_id']??0);
        $type=sanitize_key(wp_unslash($_POST['exception_type']??''));$reason=sanitize_textarea_field(wp_unslash($_POST['reason']??''));
        $amount_minor=self::money_to_minor(sanitize_text_field(wp_unslash($_POST['override_amount']??'0')));
        $enabled=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND category_id=%d AND is_enabled=1",$season_id,$category_id));
        if(!$season_id||!$enabled||!in_array($type,array('exempt','override'),true)||($type==='override'&&($amount_minor===false||$amount_minor<1))||mb_strlen($reason)<10||mb_strlen($reason)>500)self::redirect('fee_exception_invalid');
        if($school_id&&!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d",$school_id)))self::redirect('fee_exception_invalid');
        $table=$wpdb->prefix.'zmp_fee_exceptions';$before=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE season_id=%d AND category_id=%d AND school_id=%d",$season_id,$category_id,$school_id),ARRAY_A);$now=current_time('mysql');
        $after=array('season_id'=>$season_id,'category_id'=>$category_id,'school_id'=>$school_id,'exception_type'=>$type,'override_amount'=>self::minor_to_decimal($type==='override'?$amount_minor:0),'reason'=>$reason,'is_active'=>1,'created_by'=>$before?(int)$before['created_by']:get_current_user_id(),'created_at'=>$before?$before['created_at']:$now,'updated_by'=>get_current_user_id(),'updated_at'=>$now);
        $wpdb->query('START TRANSACTION');
        $ok=$wpdb->query($wpdb->prepare("INSERT INTO $table (season_id,category_id,school_id,exception_type,override_amount,reason,is_active,created_by,created_at,updated_by,updated_at) VALUES (%d,%d,%d,%s,%s,%s,1,%d,%s,%d,%s) ON DUPLICATE KEY UPDATE exception_type=VALUES(exception_type),override_amount=VALUES(override_amount),reason=VALUES(reason),is_active=1,updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)",
            $season_id,$category_id,$school_id,$type,$after['override_amount'],$reason,$after['created_by'],$after['created_at'],$after['updated_by'],$after['updated_at']));
        if($ok===false||!self::audit_change($season_id,$category_id,$school_id,'fee_exception',$before?:array(),$after,$reason)){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        $wpdb->query('COMMIT');self::redirect('fee_settings_saved');
    }

    public static function remove_fee_exception() {
        if(!self::can_moderate())wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);global $wpdb;
        $id=absint($_POST['exception_id']??0);$reason=sanitize_textarea_field(wp_unslash($_POST['reason']??''));
        $table=$wpdb->prefix.'zmp_fee_exceptions';$before=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND is_active=1",$id),ARRAY_A);
        if(!$before||mb_strlen($reason)<10||mb_strlen($reason)>500)self::redirect('fee_exception_invalid');
        $after=$before;$after['is_active']=0;$after['reason']=$reason;$after['updated_by']=get_current_user_id();$after['updated_at']=current_time('mysql');
        $wpdb->query('START TRANSACTION');$ok=$wpdb->update($table,array('is_active'=>0,'reason'=>$reason,'updated_by'=>get_current_user_id(),'updated_at'=>current_time('mysql')),array('id'=>$id,'is_active'=>1));
        if($ok!==1||!self::audit_change((int)$before['season_id'],(int)$before['category_id'],(int)$before['school_id'],'fee_exception_removed',$before,$after,$reason)){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        $wpdb->query('COMMIT');self::redirect('fee_settings_saved');
    }

    private static function audit_change($season_id,$category_id,$school_id,$type,$before,$after,$reason) {
        global $wpdb;
        return (bool)$wpdb->insert($wpdb->prefix.'zmp_fee_settings_audit',array(
            'season_id'=>absint($season_id),'category_id'=>absint($category_id),'school_id'=>absint($school_id),'change_type'=>sanitize_key($type),
            'before_json'=>wp_json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'after_json'=>wp_json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'reason'=>sanitize_text_field($reason),'changed_by'=>get_current_user_id(),'changed_at'=>current_time('mysql')
        ));
    }

    public static function handle_knowledge_fee_proof() {
        if(!is_user_logged_in()||!current_user_can('zmp_teacher_cap'))wp_die('Најавете се како наставник.','Нема пристап',array('response'=>403));
        check_admin_referer('zmp_knowledge_fee_proof');global $wpdb;
        $student_id=absint($_POST['student_id']??0);$season_id=absint($_POST['season_id']??0);$school_id=absint($_POST['school_id']??0);$user_id=get_current_user_id();
        $mentor=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d",$user_id));
        $student=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_students WHERE id=%d AND mentor_id=%d AND mentor_school_id=%d",$student_id,$mentor?(int)$mentor->id:0,$school_id));
        $knowledge_id=(int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' AND is_active=1 LIMIT 1");
        $existing=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_student_documents WHERE student_id=%d AND season_id=%d AND document_type='payment_proof'",$student_id,$season_id));
        if(!$student||!$knowledge_id||!self::knowledge_student_qualified($season_id,$student_id)||$existing)self::redirect('fee_proof_not_eligible');
        $fee=ZMP_Registration_Requirements::get_fee($season_id,$knowledge_id);
        if(!$fee)self::redirect('fee_unavailable');$effective_fee=self::effective_fee_amount($season_id,$knowledge_id,$school_id,(float)$fee->fee_amount);if(self::money_to_minor($effective_fee)<1)self::redirect('fee_not_due');
        $file=ZMP_Registration_Requirements::store_payment_proof('payment_proof',$season_id,$knowledge_id);
        if(is_wp_error($file))self::redirect($file->get_error_code());
        $payer=sanitize_key(wp_unslash($_POST['payer_type']??''));
        if(!in_array($payer,array('teacher','school'),true)){ZMP_Registration_Requirements::remove_entry_document_files(array($file));self::redirect('fee_proof_invalid');}
        $saved=$wpdb->insert($wpdb->prefix.'zmp_student_documents',array(
            'season_id'=>$season_id,'student_id'=>$student_id,'document_type'=>'payment_proof','file_key'=>$file['file_key'],'drive_file_id'=>$file['drive_file_id'],
            'storage_backend'=>$file['storage_backend'],'original_name'=>$file['original_name'],'mime_type'=>$file['mime_type'],'file_size'=>$file['file_size'],'sha256'=>$file['sha256'],
            'uploaded_by'=>$user_id,'payer_type'=>$payer,'fee_amount'=>$effective_fee,'created_at'=>current_time('mysql')
        ));
        if(!$saved){ZMP_Registration_Requirements::remove_entry_document_files(array($file));self::redirect('save_error');}
        self::redirect('fee_proof_saved');
    }

    private static function redirect($status) {
        $url=wp_get_referer()?:home_url('/');
        wp_safe_redirect(add_query_arg('zmp_requirements_status',sanitize_key($status),$url));exit;
    }
}




