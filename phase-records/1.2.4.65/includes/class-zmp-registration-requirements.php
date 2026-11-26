<?php
if (!defined('ABSPATH')) { exit; }

/** Annual application forms, category fees, payment proofs, and invoice notices. */
class ZMP_Registration_Requirements {
    const NONCE = 'zmp_registration_requirements';
    const FACSIMILE_SUBMIT_ACTION = 'zmp_submit_president_facsimile';
    private static $archive_temp_files = array();

    /** Serialize active-facsimile changes with President certificate registration. */
    public static function acquire_facsimile_write_lock($timeout = 10) {
        global $wpdb;
        $lock_scope = (string) ($wpdb->dbname ?? '') . '|' . (string) $wpdb->prefix;
        $lock_name = 'zmp_facsimile_' . substr(hash('sha256', $lock_scope), 0, 40);
        $acquired = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $lock_name, max(0, absint($timeout))));
        return (string) $acquired === '1';
    }

    /** Release the shared lock used by active-facsimile changes and registration. */
    public static function release_facsimile_write_lock() {
        global $wpdb;
        $lock_scope = (string) ($wpdb->dbname ?? '') . '|' . (string) $wpdb->prefix;
        $lock_name = 'zmp_facsimile_' . substr(hash('sha256', $lock_scope), 0, 40);
        return (string) $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name)) === '1';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_registration_forms (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            file_key VARCHAR(80) NOT NULL,
            drive_file_id VARCHAR(190) NOT NULL DEFAULT '',
            storage_backend VARCHAR(16) NOT NULL DEFAULT 'local',
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_id (season_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_category_fees (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            billing_basis VARCHAR(20) NOT NULL DEFAULT 'entry',
            charge_trigger VARCHAR(40) NOT NULL DEFAULT 'at_registration',
            payment_instructions TEXT NOT NULL,
            updated_by BIGINT(20) UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category (season_id,category_id)
        ) $c;");
        // The knowledge-test fee is only ever due after regional qualification.
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_category_fees f INNER JOIN {$wpdb->prefix}zmp_categories c ON c.id=f.category_id SET f.charge_trigger='after_regional_qualification' WHERE c.category_code='knowledge_test' AND f.charge_trigger<>'after_regional_qualification'");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_entry_documents (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            document_type VARCHAR(30) NOT NULL,
            file_key VARCHAR(80) NOT NULL,
            drive_file_id VARCHAR(190) NOT NULL DEFAULT '',
            storage_backend VARCHAR(16) NOT NULL DEFAULT 'local',
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            uploaded_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY entry_document (entry_id,document_type),
            KEY season_id (season_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_requests (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            payer_type VARCHAR(20) NOT NULL,
            teacher_name VARCHAR(190) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            school_address VARCHAR(255) NOT NULL,
            tax_number VARCHAR(50) NOT NULL,
            school_email VARCHAR(190) NOT NULL,
            personal_email VARCHAR(190) NOT NULL,
            participant_count INT(10) UNSIGNED NOT NULL DEFAULT 1,
            billing_basis VARCHAR(20) NOT NULL DEFAULT 'entry',
            unit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            amount DECIMAL(10,2) NOT NULL,
            email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY scope (season_id,category_id,wp_user_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_fee_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            previous_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            new_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            previous_basis VARCHAR(20) NOT NULL DEFAULT 'entry',
            new_basis VARCHAR(20) NOT NULL DEFAULT 'entry',
            previous_charge_trigger VARCHAR(40) NOT NULL DEFAULT 'at_registration',
            new_charge_trigger VARCHAR(40) NOT NULL DEFAULT 'at_registration',
            previous_instructions TEXT NOT NULL,
            new_instructions TEXT NOT NULL,
            change_reason VARCHAR(500) NOT NULL DEFAULT '',
            changed_by BIGINT(20) UNSIGNED NOT NULL,
            changed_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY scope (season_id,category_id,changed_at)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_admin_facsimiles (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            president_name VARCHAR(190) NOT NULL,
            file_key VARCHAR(80) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(50) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            valid_from DATE NOT NULL,
            valid_to DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            mandate_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            president_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            uploaded_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status_validity (status,valid_from,valid_to),
            KEY mandate_status (mandate_id,status),
            KEY president_user_id (president_user_id)
        ) $c;");        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_school_contacts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            school_key CHAR(64) NOT NULL,
            school_name VARCHAR(255) NOT NULL,
            municipality VARCHAR(150) NOT NULL,
            region VARCHAR(30) NOT NULL,
            email VARCHAR(190) NOT NULL,
            source VARCHAR(30) NOT NULL DEFAULT 'moderator',
            is_official TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY school_email (school_key,email),
            KEY official_active (is_official,is_active)
        ) $c;");        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_student_documents (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            student_id BIGINT(20) UNSIGNED NOT NULL,
            document_type VARCHAR(30) NOT NULL,
            file_key VARCHAR(80) NOT NULL,
            drive_file_id VARCHAR(190) NOT NULL DEFAULT '',
            storage_backend VARCHAR(16) NOT NULL DEFAULT 'local',
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            uploaded_by BIGINT(20) UNSIGNED NOT NULL,
            payer_type VARCHAR(20) NOT NULL,
            fee_amount DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY student_document (student_id,document_type),
            KEY season_id (season_id)
        ) $c;");
    }

    /** Confirm the mandate-binding migration completed before the global schema marker advances. */
    public static function admin_facsimile_schema_is_compatible() {
        global $wpdb;
        $table = $wpdb->prefix . 'zmp_admin_facsimiles';
        $found_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($found_table !== $table) { return false; }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        if (!is_array($columns)) { return false; }
        $found_columns = array();
        foreach ($columns as $column) {
            if (isset($column->Field)) { $found_columns[(string) $column->Field] = $column; }
        }
        foreach (array('mandate_id', 'president_user_id') as $name) {
            $column = $found_columns[$name] ?? null;
            if (!$column || ZMP_Database::normalize_schema_type($column->Type) !== ZMP_Database::normalize_schema_type('bigint(20) unsigned')
                || strtoupper((string) $column->Null) !== 'NO' || (string) $column->Default !== '0') { return false; }
        }
        $index_rows = $wpdb->get_results('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '`');
        if (!is_array($index_rows)) { return false; }
        $indexes = array();
        foreach ($index_rows as $index) {
            if (!isset($index->Key_name, $index->Column_name, $index->Seq_in_index, $index->Non_unique)) { continue; }
            $name = (string) $index->Key_name;
            if (!isset($indexes[$name])) {
                $indexes[$name] = array('unique' => (int) $index->Non_unique === 0, 'columns' => array());
            }
            $indexes[$name]['columns'][(int) $index->Seq_in_index] = (string) $index->Column_name;
        }
        foreach (array(
            'mandate_status' => array(false, array('mandate_id', 'status')),
            'president_user_id' => array(false, array('president_user_id')),
        ) as $name => $expected) {
            if (!isset($indexes[$name]) || $indexes[$name]['unique'] !== $expected[0]) { return false; }
            ksort($indexes[$name]['columns'], SORT_NUMERIC);
            if (array_values($indexes[$name]['columns']) !== $expected[1]) { return false; }
        }
        return true;
    }

    public static function init() {
        add_action('admin_post_zmp_save_registration_requirements', array(__CLASS__, 'save_requirements'));
        add_action('admin_post_zmp_request_fee_invoice', array(__CLASS__, 'request_fee_invoice'));
        add_action('admin_post_zmp_save_school_contact', array(__CLASS__, 'save_school_contact'));
        add_action('admin_post_zmp_remove_school_contact', array(__CLASS__, 'remove_school_contact'));
        add_action('admin_post_zmp_download_registration_form', array(__CLASS__, 'download_form'));
        add_action('admin_post_zmp_download_entry_document', array(__CLASS__, 'download_entry_document'));
        add_action('admin_post_zmp_save_admin_facsimile', array(__CLASS__, 'save_admin_facsimile'));
        add_action('admin_post_' . self::FACSIMILE_SUBMIT_ACTION, array(__CLASS__, 'submit_president_facsimile'));
        add_action('admin_post_zmp_download_admin_facsimile', array(__CLASS__, 'download_admin_facsimile'));
        add_action('init', array(__CLASS__, 'handle_private_downloads'), 0);
    }

    public static function get_fee($season_id, $category_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_category_fees WHERE season_id=%d AND category_id=%d", absint($season_id), absint($category_id)));
    }

    public static function get_form($season_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_registration_forms WHERE season_id=%d", absint($season_id)));
    }

    /** Moderator controls live in the role portal and are scoped to the active season. */
    public static function render_moderator_panel() {
        if (!self::can_moderate()) { return ''; }
        $season = ZMP_Category_Registrations::active_season();
        if (!$season) { return '<section class="zmp-card"><h3>Документи и котизации</h3><p>Нема активна натпреварувачка година.</p></section>'.self::render_school_contacts_panel().ZMP_Fee_Invoices::render_moderator_panel(null); }
        global $wpdb;
        $categories = $wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk,c.category_code,f.fee_amount,f.billing_basis,f.charge_trigger,f.payment_instructions FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d LEFT JOIN {$wpdb->prefix}zmp_category_fees f ON f.season_id=sc.season_id AND f.category_id=c.id WHERE sc.is_enabled=1 ORDER BY c.sort_order", $season->id));
        $form = self::get_form($season->id);
        $fee_history=$wpdb->get_results($wpdb->prepare("SELECT h.*,c.name_mk,u.display_name FROM {$wpdb->prefix}zmp_fee_audit h JOIN {$wpdb->prefix}zmp_categories c ON c.id=h.category_id LEFT JOIN {$wpdb->users} u ON u.ID=h.changed_by WHERE h.season_id=%d ORDER BY h.id DESC LIMIT 20",(int)$season->id));
        ob_start(); ?>
        <section class="zmp-card"><h3>Годишен формулар, котизации и пријави</h3><p><?php echo esc_html($season->title); ?> — модераторите го поставуваат годишниот формулар и ги уредуваат котизациите за секоја активна категорија.</p>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_save_registration_requirements"><input type="hidden" name="season_id" value="<?php echo (int)$season->id; ?>">
        <label>Годишен формулар за пријава (PDF)<input type="file" name="registration_form" accept="application/pdf,.pdf"></label><?php if ($form): ?><p>Тековен формулар: <a href="<?php echo esc_url(self::form_url($season->id)); ?>"><?php echo esc_html($form->original_name); ?></a></p><?php endif; ?>
        <label>Категорија за котизација<select name="category_id" required><option value="">Изберете категорија</option><?php foreach ($categories as $category): ?><option value="<?php echo (int)$category->id; ?>" data-code="<?php echo esc_attr($category->category_code); ?>" data-fee="<?php echo esc_attr($category->fee_amount??''); ?>" data-basis="<?php echo esc_attr($category->billing_basis??'entry'); ?>" data-trigger="<?php echo esc_attr($category->charge_trigger??($category->category_code==='knowledge_test'?'after_regional_qualification':'at_registration')); ?>" data-instructions="<?php echo esc_attr($category->payment_instructions??''); ?>"><?php echo esc_html($category->name_mk); ?></option><?php endforeach; ?></select></label>
        <label>Котизација за избраната основа (денари)<input type="number" name="fee_amount" id="zmp-fee-amount" min="0" max="1000000" step="0.01" required></label><label>Начин на пресметка<select name="billing_basis" id="zmp-fee-basis" required><option value="entry">По пријава / состав</option><option value="participant">По учесник</option></select></label>
        <label>Кога се наплаќа<select name="charge_trigger" id="zmp-fee-trigger" required><option value="after_regional_qualification">По потврдена квалификација за регионално ниво</option></select></label>
        <label>Информации за уплата / фактура<textarea name="payment_instructions" id="zmp-fee-instructions" rows="3" maxlength="2000" placeholder="Податоци за уплатница или фактура"></textarea></label><label>Образложение за промената *<textarea name="change_reason" rows="2" maxlength="500" minlength="10" required></textarea></label><button class="zmp-btn zmp-btn-primary">Зачувај формулар и котизација</button></form>
        <script>(function(){var f=document.querySelector('form input[name="action"][value="zmp_save_registration_requirements"]');if(!f)return;var form=f.form,c=form.querySelector('[name="category_id"]'),amount=form.querySelector('#zmp-fee-amount'),basis=form.querySelector('#zmp-fee-basis'),trigger=form.querySelector('#zmp-fee-trigger'),instructions=form.querySelector('#zmp-fee-instructions');function sync(){var o=c.options[c.selectedIndex];if(!o||!o.value)return;amount.value=o.dataset.fee||'';basis.value=o.dataset.basis||'entry';trigger.value=o.dataset.trigger||(o.dataset.code==='knowledge_test'?'after_regional_qualification':'at_registration');instructions.value=o.dataset.instructions||'';}c.addEventListener('change',sync);})();</script>
        <h4>Поставени котизации</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Износ по основа</th><th>Пресметка</th><th>Наплата</th><th>Инструкции</th></tr></thead><tbody><?php foreach ($categories as $category): ?><tr><td><?php echo esc_html($category->name_mk); ?></td><td><?php echo $category->fee_amount===null?'Не е поставена':esc_html(number_format_i18n((float)$category->fee_amount,2).' ден.'); ?></td><td><?php echo esc_html(($category->billing_basis??'entry')==='participant'?'По учесник':'По пријава/состав'); ?></td><td><?php echo esc_html(($category->charge_trigger??'at_registration')==='after_regional_qualification'?'По квалификација за регионално':'При пријавување'); ?></td><td><?php echo esc_html($category->payment_instructions?:'—'); ?></td></tr><?php endforeach; ?></tbody></table></div><?php if($fee_history): ?><h4>Историја на измени на цените</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Претходно</th><th>Ново</th><th>Инструкции</th><th>Образложение</th><th>Изменил</th><th>Датум</th></tr></thead><tbody><?php foreach($fee_history as $change): ?><tr><td><?php echo esc_html($change->name_mk); ?></td><td><?php echo esc_html(number_format_i18n((float)$change->previous_amount,2).' ден. · '.(($change->previous_basis==='participant')?'по учесник':'по пријава')); ?></td><td><?php echo esc_html(number_format_i18n((float)$change->new_amount,2).' ден. · '.(($change->new_basis==='participant')?'по учесник':'по пријава')); ?></td><td><details><summary>Прегледај</summary><div><strong>Претходни:</strong> <?php echo esc_html($change->previous_instructions); ?><br><strong>Нови:</strong> <?php echo esc_html($change->new_instructions); ?></div></details></td><td><?php echo esc_html($change->change_reason?:'—'); ?></td><td><?php echo esc_html($change->changed_by?($change->display_name?:'Корисник #'.(int)$change->changed_by):'—'); ?></td><td><?php echo esc_html($change->changed_at); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
        <?php echo self::render_school_contacts_panel(); ?><?php echo ZMP_Fee_Invoices::render_moderator_panel($season); ?>
        <?php return ob_get_clean();
    }

    /** Render moderator-managed official school contacts; teacher-provided addresses remain unverified candidates. */
    private static function render_school_contacts_panel() {
        if (!self::can_moderate()) { return ''; }
        global $wpdb;
        $contacts=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_school_contacts WHERE is_active=1 ORDER BY is_official DESC,school_name,email");
        ob_start(); ?>
        <section class="zmp-card"><h4>Службени е-пошти на училиштата</h4>
        <p>Само адреси што модератор ќе ги означи како службени може да се користат за идни групни известувања. Адресите од барањата се чуваат како непотврдени предлози.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_save_school_contact">
        <label>Училиште<input name="school_name" maxlength="255" required></label><label>Општина<input name="municipality" maxlength="150" required></label><label>Службена е-пошта<input name="email" type="email" maxlength="190" required></label><button class="zmp-btn zmp-btn-primary">Зачувај / потврди адреса</button></form>
        <?php if($contacts): ?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Училиште</th><th>Општина / регион</th><th>Е-пошта</th><th>Потекло</th><th>Статус</th><th></th></tr></thead><tbody><?php foreach($contacts as $contact): ?><tr><td><?php echo esc_html($contact->school_name); ?></td><td><?php echo esc_html($contact->municipality.' / '.ZMP_Regions::label($contact->region)); ?></td><td><?php echo esc_html($contact->email); ?></td><td><?php echo esc_html($contact->source==='moderator'?'Модератор':'Барање од наставник'); ?></td><td><?php echo (int)$contact->is_official?'Службена':'Непотврдена'; ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_remove_school_contact"><input type="hidden" name="contact_id" value="<?php echo (int)$contact->id; ?>"><button class="button" onclick="return confirm('Да се отстрани оваа адреса?')">Отстрани</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
        <?php return ob_get_clean();
    }

    public static function save_school_contact() {
        if(!self::can_moderate()) wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE); global $wpdb;
        $name=sanitize_text_field(wp_unslash($_POST['school_name']??'')); $municipality=sanitize_text_field(wp_unslash($_POST['municipality']??'')); $email=sanitize_email(wp_unslash($_POST['email']??''));
        $region=ZMP_Regions::region_for_municipality($municipality);
        if(strlen($name)<3||strlen($name)>255||!$region||!is_email($email)){self::redirect('contact_invalid');}
        $key=ZMP_Mentor_Schools::school_key($name,$municipality); $now=current_time('mysql'); $table=$wpdb->prefix.'zmp_school_contacts';
        $ok=$wpdb->query($wpdb->prepare("INSERT INTO $table (school_key,school_name,municipality,region,email,source,is_official,is_active,created_by,created_at,updated_at) VALUES (%s,%s,%s,%s,%s,'moderator',1,1,%d,%s,%s) ON DUPLICATE KEY UPDATE school_name=VALUES(school_name),municipality=VALUES(municipality),region=VALUES(region),source='moderator',is_official=1,is_active=1,created_by=VALUES(created_by),updated_at=VALUES(updated_at)",$key,$name,$municipality,$region,$email,get_current_user_id(),$now,$now));
        self::redirect($ok===false?'save_error':'contact_saved');
    }

    public static function remove_school_contact() {
        if(!self::can_moderate()) wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE); global $wpdb;
        $wpdb->update($wpdb->prefix.'zmp_school_contacts',array('is_active'=>0,'is_official'=>0,'updated_at'=>current_time('mysql')),array('id'=>absint($_POST['contact_id']??0)));
        self::redirect('contact_removed');
    }

    private static function official_school_contact($name,$municipality) {
        global $wpdb; $key=ZMP_Mentor_Schools::school_key($name,$municipality);
        return (string)$wpdb->get_var($wpdb->prepare("SELECT email FROM {$wpdb->prefix}zmp_school_contacts WHERE school_key=%s AND is_official=1 AND is_active=1 ORDER BY id DESC LIMIT 1",$key));
    }

    public static function official_school_contact_for_invoice($name,$municipality) {
        return self::official_school_contact($name,$municipality);
    }

    /** Initial knowledge registration is free; proof is later required only for that student's municipal qualifier. */
    public static function fee_proof_required($season_id,$category_id,$student_id=0,$school_id=0) {
        global $wpdb;
        $category=$wpdb->get_row($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",absint($category_id)));
        if(!$category)return true;
        $fee=self::get_fee($season_id,$category_id);
        if(!$fee)return false;
        if(!$school_id&&$category->category_code==='knowledge_test'&&absint($student_id)){
            $school_id=(int)$wpdb->get_var($wpdb->prepare("SELECT mentor_school_id FROM {$wpdb->prefix}zmp_students WHERE id=%d",absint($student_id)));
        }
        $amount=(string)$fee->fee_amount;
        if(class_exists('ZMP_Fee_Invoices')&&absint($school_id)){
            $amount=ZMP_Fee_Invoices::effective_fee_amount($season_id,$category_id,$school_id,$amount);
        }
        $trigger=(string)($fee->charge_trigger??'at_registration');
        if($category->category_code==='knowledge_test'){
            if($trigger==='at_registration')return $amount>0;
            if($trigger==='after_regional_qualification'&&absint($student_id)&&class_exists('ZMP_Fee_Invoices'))return $amount>0&&ZMP_Fee_Invoices::knowledge_student_qualified($season_id,$student_id);
            return false;
        }
        return $trigger==='at_registration'&&$amount>0;
    }
    /** Resolve the billable fee using trusted category/year, school exception, and qualification state. */
    public static function effective_entry_fee($season_id,$category_id,$student_id=0,$school_id=0) {
        $fee=self::get_fee($season_id,$category_id);
        $proof_required=self::fee_proof_required($season_id,$category_id,$student_id,$school_id);
        $amount=$proof_required&&$fee?(string)$fee->fee_amount:'0.00';
        if($proof_required&&class_exists('ZMP_Fee_Invoices')&&absint($school_id)){
            $amount=ZMP_Fee_Invoices::effective_fee_amount($season_id,$category_id,$school_id,$amount);
        } elseif($proof_required&&$fee&&class_exists('ZMP_Fee_Invoices')&&absint($student_id)){
            global $wpdb;
            $student_school=(int)$wpdb->get_var($wpdb->prepare("SELECT mentor_school_id FROM {$wpdb->prefix}zmp_students WHERE id=%d",absint($student_id)));
            if($student_school)$amount=ZMP_Fee_Invoices::effective_fee_amount($season_id,$category_id,$student_school,$amount);
        }
        return array('amount'=>$amount,'proof_required'=>(bool)$proof_required,'configured'=>(bool)$fee);
    }
    public static function store_payment_proof($field,$season_id,$category_id) {
        return self::store_upload($field,array('pdf','jpg','jpeg','png'),$season_id,$category_id,'payment-proof');
    }

    public static function render_teacher_controls($season, $categories) {
        if(!is_user_logged_in()||!current_user_can('zmp_teacher_cap'))return '';
        self::render_notice();
        return ZMP_Fee_Invoices::render_teacher_portal($season);
    }
    /** Validate and store the required scanned application and fee proof before entry insertion. */
    public static function receive_entry_documents($season_id, $category_id, $school_id=0) {
        $form=self::get_form($season_id);$fee=self::get_fee($season_id,$category_id);$fee_rule=self::effective_entry_fee($season_id,$category_id,0,absint($school_id));
        $requires_proof=(bool)$fee_rule['proof_required'];
        if(!$form||($requires_proof&&(!$fee||$fee_rule['amount']<=0)))return new WP_Error('requirements_unavailable');
        $application=self::store_upload('signed_application',array('pdf','jpg','jpeg','png'),$season_id,$category_id,'signed-application');
        if(is_wp_error($application))return $application;
        $proof=null;
        if(!$requires_proof&&!empty($_FILES['payment_proof']['name'])){self::delete_document_file($application);return new WP_Error('fee_not_due');}
        if($requires_proof){
            $proof=self::store_upload('payment_proof',array('pdf','jpg','jpeg','png'),$season_id,$category_id,'payment-proof');
            if(is_wp_error($proof)){self::delete_document_file($application);return $proof;}
        }
        $consent=self::store_upload('media_consent',array('pdf','jpg','jpeg','png'),$season_id,$category_id,'media-consent');
        if(is_wp_error($consent)){self::remove_entry_document_files(array($application,$proof));return new WP_Error('consent_required','Прикачете ја потпишаната согласност.');}
        $documents=array('application'=>$application,'consent'=>$consent,'fee_amount'=>$requires_proof?(string)$fee_rule['amount']:'0.00');
        if($proof)$documents['proof']=$proof;
        if(!empty($_FILES['sheet_music']['name'])){
            $score=self::store_upload('sheet_music',array('pdf','jpg','jpeg','png'),$season_id,$category_id,'sheet-music');
            if(is_wp_error($score)){self::remove_entry_document_files(array($application,$proof,$consent));return $score;}
            $documents['sheet_music']=$score;
        }
        return $documents;
    }
    public static function save_entry_documents($season_id,$entry_id,$documents,$roster=null) {
        global $wpdb;$ok=true;$wpdb->query('START TRANSACTION');
        foreach(array('application'=>'signed_application','proof'=>'payment_proof','consent'=>'media_consent') as $key=>$type){
            if(empty($documents[$key]))continue;
            $f=$documents[$key];
            $ok=$ok&&(bool)$wpdb->insert($wpdb->prefix.'zmp_entry_documents',array('season_id'=>(int)$season_id,'entry_id'=>(int)$entry_id,'document_type'=>$type,'file_key'=>$f['file_key'],'drive_file_id'=>$f['drive_file_id'],'storage_backend'=>$f['storage_backend'],'original_name'=>$f['original_name'],'mime_type'=>$f['mime_type'],'file_size'=>$f['file_size'],'sha256'=>$f['sha256'],'uploaded_by'=>get_current_user_id(),'created_at'=>current_time('mysql')));
        }
        if(!empty($documents['sheet_music'])){$f=$documents['sheet_music'];$ok=$ok&&(bool)$wpdb->insert($wpdb->prefix.'zmp_entry_documents',array('season_id'=>(int)$season_id,'entry_id'=>(int)$entry_id,'document_type'=>'sheet_music','file_key'=>$f['file_key'],'drive_file_id'=>$f['drive_file_id'],'storage_backend'=>$f['storage_backend'],'original_name'=>$f['original_name'],'mime_type'=>$f['mime_type'],'file_size'=>$f['file_size'],'sha256'=>$f['sha256'],'uploaded_by'=>get_current_user_id(),'created_at'=>current_time('mysql')));}
        if(is_array($roster)&&!empty($roster['drive_file_id'])){$ok=$ok&&(bool)$wpdb->insert($wpdb->prefix.'zmp_entry_documents',array('season_id'=>(int)$season_id,'entry_id'=>(int)$entry_id,'document_type'=>'participant_roster','file_key'=>'','drive_file_id'=>$roster['drive_file_id'],'storage_backend'=>'drive','original_name'=>$roster['original_name'],'mime_type'=>$roster['mime_type'],'file_size'=>(int)$roster['file_size'],'sha256'=>$roster['sha256'],'uploaded_by'=>get_current_user_id(),'created_at'=>current_time('mysql')));}
        if(!$ok){$wpdb->query('ROLLBACK');$wpdb->delete($wpdb->prefix.'zmp_entry_documents',array('entry_id'=>(int)$entry_id));self::remove_entry_document_files(array_values(array_filter(array($documents['application']??null,$documents['proof']??null,$documents['consent']??null,$documents['sheet_music']??null))));if(is_array($roster)&&!empty($roster['drive_file_id']))ZMP_Google_Drive::delete_private_file($roster['drive_file_id']);return false;}
        $wpdb->query('COMMIT');return true;
    }
    public static function save_student_documents($season_id,$student_id,$documents,$payer_type) {
        global $wpdb;$fee=(float)($documents['fee_amount']??0);$ok=true;$wpdb->query('START TRANSACTION');
        foreach(array('application'=>'signed_application','proof'=>'payment_proof','consent'=>'media_consent') as $key=>$type){
            if(empty($documents[$key]))continue;
            $f=$documents[$key];
            $ok=$ok&&(bool)$wpdb->insert($wpdb->prefix.'zmp_student_documents',array('season_id'=>(int)$season_id,'student_id'=>(int)$student_id,'document_type'=>$type,'file_key'=>$f['file_key'],'drive_file_id'=>$f['drive_file_id'],'storage_backend'=>$f['storage_backend'],'original_name'=>$f['original_name'],'mime_type'=>$f['mime_type'],'file_size'=>$f['file_size'],'sha256'=>$f['sha256'],'uploaded_by'=>get_current_user_id(),'payer_type'=>$payer_type,'fee_amount'=>$fee,'created_at'=>current_time('mysql')));
        }
        if(!$ok){$wpdb->query('ROLLBACK');$wpdb->delete($wpdb->prefix.'zmp_student_documents',array('student_id'=>(int)$student_id));self::remove_entry_document_files(array_values(array_filter(array($documents['application']??null,$documents['proof']??null,$documents['consent']??null))));return false;}
        $wpdb->query('COMMIT');return true;
    }
    public static function student_document_rows_for_season($season_id){global $wpdb;return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_student_documents WHERE season_id=%d",absint($season_id)),ARRAY_A);}
    public static function all_student_document_rows(){global $wpdb;return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_student_documents",ARRAY_A);}
    /** Move legacy host-stored application documents to Drive after hash verification, then remove only the verified local copy. */
    public static function migrate_legacy_files($limit=10) {
        global $wpdb; $limit=max(1,min(20,absint($limit))); $dir=self::private_directory();
        if(is_wp_error($dir))return array('moved'=>0,'failed'=>1,'remaining'=>-1);
        $items=array(); $remaining=0;
        $forms=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_registration_forms WHERE storage_backend='local' AND file_key<>'' ORDER BY id LIMIT ".(int)$limit,ARRAY_A);
        $remaining+=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_registration_forms WHERE storage_backend='local' AND file_key<>''");
        foreach((array)$forms as $row){$row['_table']='zmp_registration_forms';$row['_category_id']=absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' LIMIT 1"));$row['_purpose']='annual-form';$items[]=$row;}
        $left=$limit-count($items);
        if($left>0){$rows=$wpdb->get_results("SELECT d.*,e.category_id AS _category_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.storage_backend='local' AND d.file_key<>'' ORDER BY d.id LIMIT ".(int)$left,ARRAY_A);$remaining+=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_entry_documents WHERE storage_backend='local' AND file_key<>''");foreach((array)$rows as $row){$row['_table']='zmp_entry_documents';$row['_purpose']=sanitize_key($row['document_type']);$items[]=$row;}}
        $left=$limit-count($items);
        if($left>0){$knowledge=absint($wpdb->get_var("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code='knowledge_test' LIMIT 1"));$rows=$wpdb->get_results("SELECT d.*,s.competition_id FROM {$wpdb->prefix}zmp_student_documents d JOIN {$wpdb->prefix}zmp_students s ON s.id=d.student_id WHERE d.storage_backend='local' AND d.file_key<>'' ORDER BY d.id LIMIT ".(int)$left,ARRAY_A);$remaining+=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_student_documents WHERE storage_backend='local' AND file_key<>''");foreach((array)$rows as $row){$row['_table']='zmp_student_documents';$row['_category_id']=$knowledge;$row['_purpose']=sanitize_key($row['document_type']);$items[]=$row;}}
        $moved=0;$failed=0;
        foreach($items as $row){
            $path=trailingslashit($dir).$row['file_key'];
            if(empty($row['_category_id'])||!self::valid_saved_file((object)$row,$path)){++$failed;continue;}
            $drive=ZMP_Google_Drive::upload_private_document($path,(int)$row['file_size'],$row['mime_type'],$row['original_name'],(int)$row['season_id'],(int)$row['_category_id'],$row['_purpose']);
            if(is_wp_error($drive)){++$failed;continue;}
            $temp=ZMP_Google_Drive::download_private_file_to_temp($drive);$verified=!is_wp_error($temp)&&hash_equals((string)$row['sha256'],hash_file('sha256',$temp));
            if(is_string($temp)&&is_file($temp))@unlink($temp);
            if(!$verified){ZMP_Google_Drive::delete_private_file($drive);++$failed;continue;}
            $saved=$wpdb->update($wpdb->prefix.$row['_table'],array('file_key'=>'','drive_file_id'=>$drive,'storage_backend'=>'drive'),array('id'=>(int)$row['id']));
            if($saved===false){ZMP_Google_Drive::delete_private_file($drive);++$failed;continue;}
            if(!wp_delete_file($path)){++$failed;continue;}
            ++$moved;
        }
        return array('moved'=>$moved,'failed'=>$failed,'remaining'=>max(0,$remaining-$moved));
    }
    public static function render_student_document_links($student_id){global $wpdb;$rows=$wpdb->get_results($wpdb->prepare("SELECT id,document_type,original_name FROM {$wpdb->prefix}zmp_student_documents WHERE student_id=%d ORDER BY document_type",absint($student_id)));if(!$rows)return ''; $html='<ul class="zmp-entry-documents">';foreach($rows as $row){$label=$row->document_type==='signed_application'?'Потпишан формулар':($row->document_type==='media_consent'?'Согласност за медиумска употреба':($row->document_type==='sheet_music'?'Нотен запис':'Потврда за уплата'));$url=wp_nonce_url(add_query_arg('zmp_student_document',(int)$row->id,home_url('/')),'zmp_student_doc_'.(int)$row->id);$html.='<li><a href="'.esc_url($url).'">'.esc_html($label.' — '.$row->original_name).'</a></li>';}return $html.'</ul>';}
    public static function remove_student_document_files($rows){self::remove_entry_document_files($rows);}

    public static function remove_entry_document_files($rows) { foreach ((array)$rows as $row) { $type=is_array($row)?($row['document_type']??''):($row->document_type??'');if($type==='participant_roster')continue;self::delete_document_file($row); } }
    public static function document_rows_for_season($season_id) { global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_entry_documents WHERE season_id=%d",absint($season_id)),ARRAY_A); }
    public static function document_revision_rows_for_season($season_id) { global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_entry_document_revisions WHERE season_id=%d",absint($season_id)),ARRAY_A); }
    public static function remove_document_revision_files($rows) { foreach((array)$rows as $row) { $get=function($key)use($row){return is_array($row)?($row[$key]??''):($row->$key??'');};if($get('document_type')==='participant_roster'&&class_exists('ZMP_Category_Registrations')){ZMP_Category_Registrations::remove_roster_files(array(array('roster_filename'=>$get('file_key'),'roster_drive_file_id'=>$get('drive_file_id'),'roster_storage_backend'=>$get('storage_backend'),'roster_sha256'=>$get('sha256'))));continue;}self::delete_document_file($row); } }
    public static function all_document_rows() { global $wpdb; return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_entry_documents",ARRAY_A); }
    public static function render_entry_document_links($entry_id) {
        global $wpdb;$rows=$wpdb->get_results($wpdb->prepare("SELECT id,document_type,original_name FROM {$wpdb->prefix}zmp_entry_documents WHERE entry_id=%d ORDER BY document_type",absint($entry_id)));if(!$rows)return '';$html='<ul class="zmp-entry-documents">';
        foreach($rows as $row){$label=$row->document_type==='signed_application'?'Потпишан формулар':($row->document_type==='media_consent'?'Согласност за медиумска употреба':($row->document_type==='sheet_music'?'Нотен запис':'Потврда за уплата'));$url=wp_nonce_url(add_query_arg('zmp_entry_document',(int)$row->id,home_url('/')),'zmp_entry_doc_'.(int)$row->id);$html.='<li><a href="'.esc_url($url).'">'.esc_html($label.' — '.$row->original_name).'</a></li>';}
        return $html.'</ul>';
    }

    public static function add_documents_to_archive($archive,$season_id) {
        global $wpdb;$rows=self::document_rows_for_season($season_id);$revision_rows=self::document_revision_rows_for_season($season_id);$student_rows=self::student_document_rows_for_season($season_id);$forms=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_registration_forms WHERE season_id=%d",absint($season_id)),ARRAY_A);
        foreach($forms as $form)if(!self::add_document_to_archive($archive,$form,'registration/'.$form['original_name']))return false;
        foreach($rows as $row){$name=sanitize_file_name($row['original_name'])?:('document-'.$row['id']);if(!self::add_document_to_archive($archive,$row,'entry-documents/entry-'.$row['entry_id'].'/'.$row['document_type'].'-'.$name))return false;}
        foreach($revision_rows as $row){$name=sanitize_file_name($row['original_name'])?:('revision-'.$row['id']);if(!self::add_document_to_archive($archive,$row,'entry-document-history/entry-'.$row['entry_id'].'/document-'.$row['document_id'].'/revision-'.$row['id'].'-'.$name))return false;}
        foreach($student_rows as $row){$name=sanitize_file_name($row['original_name'])?:('document-'.$row['id']);if(!self::add_document_to_archive($archive,$row,'knowledge-test/students/'.$row['student_id'].'/'.$row['document_type'].'-'.$name))return false;}
        return true;
    }
    private static function add_document_to_archive($archive,$row,$archive_path) {
        $v=function($key)use($row){return is_array($row)?($row[$key]??''):($row->$key??'');};
        if($v('document_type')==='participant_roster'&&class_exists('ZMP_Category_Registrations'))return ZMP_Category_Registrations::add_roster_document_to_archive($archive,is_object($row)?(array)$row:$row,$archive_path);
        if($v('storage_backend')==='drive'&&$v('drive_file_id')!==''){$path=ZMP_Google_Drive::download_private_file_to_temp($v('drive_file_id'));if(is_wp_error($path))return false;self::$archive_temp_files[]=$path;}
        else{$dir=self::private_directory();if(is_wp_error($dir)||!preg_match('/^[a-f0-9]{48}\\.(pdf|jpg|jpeg|png)$/',(string)$v('file_key')))return false;$path=trailingslashit($dir).$v('file_key');}
        $hash=is_file($path)?hash_file('sha256',$path):false;if(!is_string($hash)||!hash_equals((string)$v('sha256'),$hash))return false;
        try{$added=$archive->addFile($path,$archive_path);}catch(Throwable $error){return false;}
        return !($archive instanceof ZipArchive)||$added;
    }
    public static function cleanup_archive_temp_files() { foreach(self::$archive_temp_files as $path)if(is_string($path)&&is_file($path))@unlink($path);self::$archive_temp_files=array(); }
    public static function form_rows_for_season($season_id) { global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_registration_forms WHERE season_id=%d",absint($season_id)),ARRAY_A); }
    public static function remove_form_files($rows) { foreach((array)$rows as $row) self::delete_document_file($row); }

    public static function save_requirements() {
        if(!self::can_moderate())wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);global $wpdb;
        $season_id=absint($_POST['season_id']??0);$category_id=absint($_POST['category_id']??0);
        $season=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d",$season_id));
        $category=$wpdb->get_row($wpdb->prepare("SELECT c.category_code FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1",$season_id,$category_id));
        $fee_raw=sanitize_text_field(wp_unslash($_POST['fee_amount']??''));
        $fee_valid=preg_match('/^(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,2})?$/',$fee_raw);
        $fee=$fee_valid?(float)$fee_raw:-1;
        $instructions=sanitize_textarea_field(wp_unslash($_POST['payment_instructions']??''));
        $basis=sanitize_key(wp_unslash($_POST['billing_basis']??'entry'));
        $trigger=sanitize_key(wp_unslash($_POST['charge_trigger']??'at_registration'));
        $reason=sanitize_textarea_field(wp_unslash($_POST['change_reason']??''));
        if(!$season||!$category||!$fee_valid||$fee<0||$fee>1000000||!in_array($basis,array('entry','participant'),true)||mb_strlen($instructions)>2000||mb_strlen($reason)<10||mb_strlen($reason)>500)self::redirect('invalid');
        if($category->category_code==='knowledge_test') {
            if($trigger!=='after_regional_qualification')self::redirect('invalid');
        } else { $trigger='at_registration'; }
        $new_form=null;
        if(!empty($_FILES['registration_form']['name'])){$new_form=self::store_upload('registration_form',array('pdf'),$season_id,$category_id,'annual-form');if(is_wp_error($new_form))self::redirect($new_form->get_error_code());}
        $old=$new_form?self::get_form($season_id):null;$previous=self::get_fee($season_id,$category_id);
        $before=array('fee_amount'=>$previous?(string)$previous->fee_amount:'0.00','billing_basis'=>$previous?(string)$previous->billing_basis:'entry','charge_trigger'=>$previous?(string)($previous->charge_trigger??'at_registration'):'at_registration','payment_instructions'=>$previous?(string)$previous->payment_instructions:'');
        $after=array('fee_amount'=>number_format($fee,2,'.',''),'billing_basis'=>$basis,'charge_trigger'=>$trigger,'payment_instructions'=>$instructions);
        $changed=!$previous||$before!==$after;
        $wpdb->query('START TRANSACTION');
        if($changed){
            $audit=$wpdb->insert($wpdb->prefix.'zmp_fee_audit',array(
                'season_id'=>$season_id,'category_id'=>$category_id,'previous_amount'=>(float)$before['fee_amount'],'new_amount'=>$fee,
                'previous_basis'=>$before['billing_basis'],'new_basis'=>$basis,'previous_charge_trigger'=>$before['charge_trigger'],'new_charge_trigger'=>$trigger,
                'previous_instructions'=>$before['payment_instructions'],'new_instructions'=>$instructions,'change_reason'=>$reason,
                'changed_by'=>get_current_user_id(),'changed_at'=>current_time('mysql')
            ));
            if(!$audit){$wpdb->query('ROLLBACK');if($new_form)self::delete_document_file($new_form);self::redirect('save_error');}
        }
        $saved=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}zmp_category_fees (season_id,category_id,fee_amount,billing_basis,charge_trigger,payment_instructions,updated_by,updated_at) VALUES (%d,%d,%f,%s,%s,%s,%d,%s) ON DUPLICATE KEY UPDATE fee_amount=VALUES(fee_amount),billing_basis=VALUES(billing_basis),charge_trigger=VALUES(charge_trigger),payment_instructions=VALUES(payment_instructions),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)",$season_id,$category_id,$fee,$basis,$trigger,$instructions,get_current_user_id(),current_time('mysql')));
        if($saved===false){$wpdb->query('ROLLBACK');if($new_form)self::delete_document_file($new_form);self::redirect('save_error');}
        if($new_form){
            $data=array('season_id'=>$season_id,'file_key'=>'','drive_file_id'=>$new_form['drive_file_id'],'storage_backend'=>'drive','original_name'=>$new_form['original_name'],'mime_type'=>$new_form['mime_type'],'file_size'=>$new_form['file_size'],'sha256'=>$new_form['sha256'],'updated_by'=>get_current_user_id(),'updated_at'=>current_time('mysql'));
            $form_saved=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}zmp_registration_forms (season_id,file_key,drive_file_id,storage_backend,original_name,mime_type,file_size,sha256,updated_by,updated_at) VALUES (%d,%s,%s,%s,%s,%s,%d,%s,%d,%s) ON DUPLICATE KEY UPDATE file_key=VALUES(file_key),drive_file_id=VALUES(drive_file_id),storage_backend=VALUES(storage_backend),original_name=VALUES(original_name),mime_type=VALUES(mime_type),file_size=VALUES(file_size),sha256=VALUES(sha256),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)",$data['season_id'],$data['file_key'],$data['drive_file_id'],$data['storage_backend'],$data['original_name'],$data['mime_type'],$data['file_size'],$data['sha256'],$data['updated_by'],$data['updated_at']));
            if($form_saved===false){$wpdb->query('ROLLBACK');self::delete_document_file($new_form);self::redirect('save_error');}
        }
        $wpdb->query('COMMIT');if($new_form&&$old)self::delete_document_file($old);self::redirect('saved');
    }
    /** Deprecated single-item endpoint: invoices must use the audited batch workflow. */
    public static function request_fee_invoice() {
        if (!is_user_logged_in() || !current_user_can('zmp_teacher_cap')) {
            wp_die('Access denied.','No access',array('response'=>403));
        }
        check_admin_referer(self::NONCE);
        self::redirect('invoice_flow_moved');
    }    public static function handle_private_downloads() { if(isset($_GET['zmp_registration_form']))self::download_form();if(isset($_GET['zmp_entry_document']))self::download_entry_document();if(isset($_GET['zmp_student_document']))self::download_student_document(); }
    public static function download_form() {
        if(!is_user_logged_in()||(!current_user_can('zmp_teacher_cap')&&!self::can_moderate()))wp_die('Немате пристап.','Нема пристап',array('response'=>403));$season_id=absint($_GET['zmp_registration_form']);if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'zmp_form_'.$season_id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));$form=self::get_form($season_id);if(!$form)wp_die('Формуларот не е поставен.','Недостапно',array('response'=>404));self::stream_file($form);
    }
    public static function download_entry_document() {
        if(!is_user_logged_in())wp_die('Најавете се.','Нема пристап',array('response'=>403));$id=absint($_GET['zmp_entry_document']);if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'zmp_entry_doc_'.$id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));global $wpdb;$owner=(current_user_can('manage_options')||current_user_can('zmp_moderator_cap'))?'':$wpdb->prepare(' AND e.wp_user_id=%d',get_current_user_id());$doc=$wpdb->get_row($wpdb->prepare("SELECT d.*,e.season_id,e.category_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.id=%d$owner",$id));if(!$doc&&is_user_logged_in()){ $candidate=$wpdb->get_row($wpdb->prepare("SELECT d.*,e.season_id,e.category_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.id=%d",$id));if($candidate&&class_exists('ZMP_Document_Review')&&ZMP_Document_Review::can_review_entry($candidate->season_id,$candidate->category_id,$candidate->entry_id))$doc=$candidate; }if(!$doc)wp_die('Документот не е пронајден.','Недостапно',array('response'=>404));self::stream_file($doc);
    }
    private static function download_student_document(){if(!is_user_logged_in())wp_die('Најавете се.','Нема пристап',array('response'=>403));$id=absint($_GET['zmp_student_document']);if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'zmp_student_doc_'.$id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));global $wpdb;$owner=(current_user_can('manage_options')||current_user_can('zmp_moderator_cap'))?'':$wpdb->prepare(' AND m.wp_user_id=%d',get_current_user_id());$doc=$wpdb->get_row($wpdb->prepare("SELECT d.* FROM {$wpdb->prefix}zmp_student_documents d JOIN {$wpdb->prefix}zmp_students s ON s.id=d.student_id JOIN {$wpdb->prefix}zmp_mentors m ON m.id=s.mentor_id WHERE d.id=%d$owner",$id));if(!$doc)wp_die('Документот не е пронајден.','Недостапно',array('response'=>404));self::stream_file($doc);}
    public static function store_document_replacement($field,$season_id,$category_id){return self::store_upload($field,array('pdf','jpg','jpeg','png'),absint($season_id),absint($category_id),'document-replacement');}
    public static function store_roster_replacement($field,$season_id,$category_id){
        if(empty($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)return new WP_Error('document_required');
        $f=$_FILES[$field];if(!is_uploaded_file($f['tmp_name'])||(int)$f['size']<1||(int)$f['size']>10*MB_IN_BYTES)return new WP_Error('document_invalid');
        $original=sanitize_file_name(wp_unslash($f['name']));$ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));if(!in_array($ext,array('pdf','docx','xlsx','csv'),true))return new WP_Error('document_type_invalid');
        $head=@file_get_contents($f['tmp_name'],false,null,0,8);$valid=$ext==='pdf'&&strncmp((string)$head,'%PDF-',5)===0||$ext==='csv'&&strpos((string)$head,"\0")===false||in_array($ext,array('docx','xlsx'),true)&&strncmp((string)$head,"PK\x03\x04",4)===0;if(!$valid)return new WP_Error('document_type_invalid');
        $mime=function_exists('mime_content_type')?mime_content_type($f['tmp_name']):'application/octet-stream';$drive=ZMP_Google_Drive::upload_private_document($f['tmp_name'],(int)$f['size'],$mime?:'application/octet-stream',$original,absint($season_id),absint($category_id),'student-rosters');if(is_wp_error($drive))return $drive;
        return array('file_key'=>'','drive_file_id'=>$drive,'storage_backend'=>'drive','original_name'=>$original,'mime_type'=>$mime?:'application/octet-stream','file_size'=>(int)$f['size'],'sha256'=>hash_file('sha256',$f['tmp_name']));
    }
    public static function stream_entry_document($row,$inline=false){return self::stream_file($row,(bool)$inline);}
    private static function stream_file($row,$inline=false) {
        if(($row->document_type??'')==='participant_roster'&&class_exists('ZMP_Category_Registrations'))return ZMP_Category_Registrations::stream_roster_document($row);
        $temporary=false;
        if(($row->storage_backend??'local')==='drive'&&!empty($row->drive_file_id)) {
            $path=ZMP_Google_Drive::download_private_file_to_temp($row->drive_file_id);
            if(is_wp_error($path)) wp_die('Приватниот документ не може да се преземе од Drive.','Недостапно',array('response'=>503));
            $temporary=true;
        } else {
            $dir=self::private_directory(); $path=is_wp_error($dir)?'':trailingslashit($dir).$row->file_key;
            if(!self::valid_saved_file($row,$path)) wp_die('Документот не е достапен.','Недостапно',array('response'=>404));
        }
        $actual_size=is_file($path)?(int)filesize($path):0; $actual_hash=is_file($path)?hash_file('sha256',$path):false;
        $legacy_roster_size_unknown=($row->document_type??'')==='participant_roster'&&(int)$row->file_size===0;
        if((!$legacy_roster_size_unknown&&$actual_size!==(int)$row->file_size)||!is_string($actual_hash)||!hash_equals((string)$row->sha256,$actual_hash)) { if($temporary&&is_file($path))@unlink($path); wp_die('Документот не помина проверка на интегритет.','Недостапно',array('response'=>404)); }
        nocache_headers(); header('X-Content-Type-Options: nosniff'); header('Content-Type: '.sanitize_mime_type($row->mime_type));
        header('Content-Length: '.filesize($path)); $inline=$inline&&in_array(strtolower((string)$row->mime_type),array('application/pdf','image/jpeg','image/png'),true); header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.rawurlencode($row->original_name).'"');
        readfile($path); if($temporary) @unlink($path); exit;
    }
    private static function store_upload($field,$allowed=array('pdf','jpg','jpeg','png'),$season_id=0,$category_id=0,$purpose='registration') {
        if(empty($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)return new WP_Error('document_required');
        $f=$_FILES[$field];
        if(!is_uploaded_file($f['tmp_name'])||(int)$f['size']<1||(int)$f['size']>15*MB_IN_BYTES)return new WP_Error('document_invalid');
        $original=sanitize_file_name(wp_unslash($f['name'])); $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
        if(!in_array($ext,$allowed,true))return new WP_Error('document_type_invalid');
        $mime=function_exists('mime_content_type')?mime_content_type($f['tmp_name']):'';
        $valid=($ext==='pdf'&&strncmp(file_get_contents($f['tmp_name'],false,null,0,5),'%PDF-',5)===0)||(($ext==='jpg'||$ext==='jpeg')&&strncmp(file_get_contents($f['tmp_name'],false,null,0,3),"\xFF\xD8\xFF",3)===0)||($ext==='png'&&strncmp(file_get_contents($f['tmp_name'],false,null,0,8),"\x89PNG\r\n\x1A\n",8)===0);
        if(!$valid)return new WP_Error('document_type_invalid');
        $sha=hash_file('sha256',$f['tmp_name']);
        $drive_id=ZMP_Google_Drive::upload_private_document($f['tmp_name'],(int)$f['size'],$mime?:'application/octet-stream',$original,$season_id,$category_id,$purpose);
        if(is_wp_error($drive_id))return $drive_id;
        return array('file_key'=>'','drive_file_id'=>$drive_id,'storage_backend'=>'drive','original_name'=>$original,'mime_type'=>$mime?:'application/octet-stream','file_size'=>(int)$f['size'],'sha256'=>$sha);
    }
    private static function valid_saved_file($row,$path) { return $path&&basename($row->file_key)===$row->file_key&&preg_match('/^[a-f0-9]{48}\.(pdf|jpg|jpeg|png)$/',$row->file_key)&&is_file($path)&&is_string($hash=hash_file('sha256',$path))&&hash_equals($row->sha256,$hash); }
    private static function delete_file($key) { if(!preg_match('/^[a-f0-9]{48}\.(pdf|jpg|jpeg|png|docx|xlsx|csv)$/',(string)$key))return false;$dir=self::private_directory();$path=is_wp_error($dir)?'':trailingslashit($dir).$key;return !$path||!is_file($path)||wp_delete_file($path); }
    private static function delete_document_file($row) {
        $get=function($key) use ($row) { return is_array($row)?($row[$key]??''):($row->$key??''); };
        if($get('storage_backend')==='drive'&&$get('drive_file_id')!=='') return ZMP_Google_Drive::delete_private_file($get('drive_file_id'));
        return self::delete_file($get('file_key'));
    }
    private static function private_directory() { $root=realpath(!empty($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:ABSPATH);$parent=$root?realpath(dirname($root)):false;if(!$root||!$parent)return new WP_Error('document_storage_unsafe');$dir=trailingslashit($parent).'zmp-private-documents-'.substr(hash('sha256',home_url('/')),0,12);if(!is_dir($dir)&&!wp_mkdir_p($dir))return new WP_Error('document_storage_failed');$real=realpath($dir);if(!$real||strpos(str_replace('\\','/',$real),rtrim(str_replace('\\','/',$root),'/').'/')===0)return new WP_Error('document_storage_unsafe');@chmod($real,0700);return $real; }
    /** Administrator-only management for the confidential president stamp/signature image. */
    public static function render_admin_facsimile_page() {
        if(!current_user_can('manage_options')) wp_die('Немате дозвола за управување со факсимилот.','Нема пристап',array('response'=>403));
        global $wpdb;
        $today=current_time('Y-m-d');
        $active=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE status='active' AND valid_from<=%s AND (valid_to IS NULL OR valid_to>=%s) ORDER BY valid_from DESC,id DESC LIMIT 1",$today,$today));
        $history=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}zmp_admin_facsimiles ORDER BY id DESC LIMIT 20");
        $status=sanitize_key(wp_unslash($_GET['zmp_facsimile_status']??''));
        $association_email=sanitize_email(get_option("admin_email"));
        ?>
        <div class="wrap"><h1>Факсимил на активниот претседател</h1>
        <p>Пристапот е само за администратори. Сликата се чува надвор од јавната папка и не се додава автоматски на фактури. За автоматско издавање фактури се потребни посебно одобрени официјални податоци и правила.</p>
        <?php if($status==='saved'): ?><div class="notice notice-success"><p>Факсимилот е зачуван приватно. Претходната поставка останува во историјата.</p></div><?php elseif($status==='invalid'): ?><div class="notice notice-error"><p>Проверете го името, датумите и PNG/JPG датотеката (најмногу 2 MB).</p></div><?php elseif($status==='error'): ?><div class="notice notice-error"><p>Факсимилот не беше зачуван. Проверете ја приватната архива.</p></div><?php endif; ?>
        <p>Барањата за котизација се испраќаат до административната адреса: <strong><?php echo esc_html($association_email); ?></strong>. Адресата се менува во WordPress → Поставки → Општо.</p><section class="card" style="max-width:760px;padding:16px"><h2>Постави нов факсимил</h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('zmp_admin_facsimile'); ?><input type="hidden" name="action" value="zmp_save_admin_facsimile">
        <p><label>Име на претседателот<br><input type="text" name="president_name" maxlength="190" required class="regular-text"></label></p>
        <p><label>Важи од<br><input type="date" name="valid_from" required></label> &nbsp; <label>Важи до (по избор)<br><input type="date" name="valid_to"></label></p>
        <p><label>Слика од печатот и потписот (PNG или JPG, најмногу 2 MB)<br><input type="file" name="facsimile_file" accept="image/png,image/jpeg,.png,.jpg,.jpeg" required></label></p>
        <p><button class="button button-primary">Зачувај приватно</button></p></form></section>
        <?php if($active): ?><section class="card" style="max-width:760px;padding:16px"><h2>Тековна поставка</h2><p><strong><?php echo esc_html($active->president_name); ?></strong> · <?php echo esc_html($active->valid_from); ?> — <?php echo esc_html($active->valid_to?:'без датум на истек'); ?></p><p><?php echo esc_html($active->original_name); ?> · <?php echo esc_html(size_format((int)$active->file_size)); ?></p><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_download_admin_facsimile&id='.(int)$active->id),'zmp_admin_facsimile_'.(int)$active->id)); ?>">Прегледај приватна копија</a></section><?php endif; ?>
        <h2>Историја на поставки</h2><table class="widefat striped" style="max-width:1000px"><thead><tr><th>Претседател</th><th>Период</th><th>Датотека</th><th>Статус</th><th>Поставено</th><th></th></tr></thead><tbody>
        <?php if(!$history): ?><tr><td colspan="6">Сè уште нема поставен факсимил.</td></tr><?php else: foreach($history as $row): ?><tr><td><?php echo esc_html($row->president_name); ?></td><td><?php echo esc_html($row->valid_from.' — '.($row->valid_to?:'без истек')); ?></td><td><?php echo esc_html($row->original_name); ?></td><td><?php echo esc_html($row->status==='active'?'Тековен':'Архивиран'); ?></td><td><?php echo esc_html($row->created_at); ?></td><td><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_download_admin_facsimile&id='.(int)$row->id),'zmp_admin_facsimile_'.(int)$row->id)); ?>">Преглед</a></td></tr><?php endforeach; endif; ?>
        </tbody></table></div><?php
    }

    /** Return only the current President's mandate-bound pending image. */
    public static function current_pending_facsimile() {
        if (!class_exists('ZMP_President_Facsimile_Certificates')
            || !ZMP_President_Facsimile_Certificates::is_current_president()) { return false; }
        global $wpdb;
        $mandate = ZMP_President_Facsimile_Certificates::current_mandate_context();
        if (is_wp_error($mandate)) { return false; }
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id,president_name,file_key,original_name,mime_type,file_size,sha256,valid_from,valid_to,status,mandate_id,president_user_id FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE mandate_id=%d AND president_user_id=%d AND status='pending' ORDER BY id DESC LIMIT 1",
            (int) $mandate->id, get_current_user_id()
        ));
        if (!$row || !preg_match('/^[a-f0-9]{64}$/', strtolower((string) $row->sha256))) { return false; }
        $verified = self::get_admin_facsimile((int) $row->id);
        if (is_wp_error($verified) || !hash_equals(strtolower((string) $row->sha256), strtolower((string) ($verified['sha256'] ?? '')))) { return false; }
        return $row;
    }

    /** Accept a new facsimile only from the current President; it remains pending until signed. */
    public static function submit_president_facsimile() {
        if (!class_exists('ZMP_President_Facsimile_Certificates')
            || !ZMP_President_Facsimile_Certificates::is_current_president()) { wp_die('Немате дозвола.', 'Нема пристап', array('response' => 403)); }
        check_admin_referer(self::FACSIMILE_SUBMIT_ACTION, 'zmp_president_facsimile_nonce');
        global $wpdb;
        $mandate = ZMP_President_Facsimile_Certificates::current_mandate_context();
        if (is_wp_error($mandate)) { self::facsimile_redirect('upload_failed'); }
        $name = sanitize_text_field(wp_unslash($_POST['president_name'] ?? ''));
        $from = sanitize_text_field(wp_unslash($_POST['valid_from'] ?? ''));
        $to = sanitize_text_field(wp_unslash($_POST['valid_to'] ?? ''));
        $valid_date = static function ($value) {
            $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
            return $date && $date->format('Y-m-d') === (string) $value;
        };
        $file = $_FILES['facsimile_file'] ?? array();
        if ($name === '' || mb_strlen($name) > 190 || !$valid_date($from)
            || $from < (string) $mandate->start_date || $from > (string) $mandate->end_date
            || ($to !== '' && (!$valid_date($to) || $to < $from || $to > (string) $mandate->end_date))
            || empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file($file['tmp_name']) || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
            self::facsimile_redirect('upload_invalid');
        }
        $image = @getimagesize($file['tmp_name']);
        $mime = is_array($image) ? (string) ($image['mime'] ?? '') : '';
        $extensions = array('image/png' => 'png', 'image/jpeg' => 'jpg');
        if (!isset($extensions[$mime])) { self::facsimile_redirect('upload_invalid'); }
        $dir = self::private_facsimile_directory();
        if (is_wp_error($dir)) { self::facsimile_redirect('upload_failed'); }
        try { $key = bin2hex(random_bytes(24)) . '.' . $extensions[$mime]; }
        catch (Throwable $e) { self::facsimile_redirect('upload_failed'); }
        $path = trailingslashit($dir) . $key;
        if (!move_uploaded_file($file['tmp_name'], $path)) { self::facsimile_redirect('upload_failed'); }
        @chmod($path, 0600);
        $size = (int) filesize($path);
        $sha = hash_file('sha256', $path);
        if ((fileperms($path) & 0777) !== 0600 || $size < 1 || $size > 2 * 1024 * 1024 || !is_string($sha)) {
            wp_delete_file($path); self::facsimile_redirect('upload_failed');
        }
        $original = sanitize_file_name(wp_unslash($file['name'] ?? 'facsimile.' . $extensions[$mime]));
        if (!self::acquire_facsimile_write_lock()) { wp_delete_file($path); self::facsimile_redirect('upload_failed'); }
        $in_transaction = false;
        $saved = false;
        try {
            if (false === $wpdb->query('START TRANSACTION')) { throw new RuntimeException('upload_failed'); }
            $in_transaction = true;
            $locked = $wpdb->get_row($wpdb->prepare(
                "SELECT id,user_id,start_date,end_date,status FROM {$wpdb->prefix}zmp_president_mandates WHERE id=%d FOR UPDATE", (int) $mandate->id
            ));
            $today = current_time('Y-m-d');
            if (!$locked || (int) $locked->user_id !== get_current_user_id() || $locked->status !== 'active'
                || (string) $locked->start_date > $today || (string) $locked->end_date < $today
                || !ZMP_President_Facsimile_Certificates::is_current_president()) { throw new RuntimeException('upload_failed'); }
            $table = $wpdb->prefix . 'zmp_admin_facsimiles';
            $handover_waiting = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM " . ZMP_President_Facsimile_Certificates::certificates_table() . " WHERE mandate_id=%d AND status='pending_handover' LIMIT 1 FOR UPDATE",
                (int) $locked->id
            ));
            if ($handover_waiting) { throw new RuntimeException('handover_pending'); }
            $pending = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$table} WHERE mandate_id=%d AND president_user_id=%d AND status='pending' FOR UPDATE", (int) $locked->id, get_current_user_id()
            ));
            foreach ((array) $pending as $pending_id) {
                if (false === $wpdb->update($table, array('status' => 'superseded'), array('id' => (int) $pending_id, 'status' => 'pending'), array('%s'), array('%d', '%s'))) { throw new RuntimeException('upload_failed'); }
            }
            $inserted = $wpdb->insert($table, array(
                'president_name' => $name, 'file_key' => $key, 'original_name' => $original, 'mime_type' => $mime,
                'file_size' => $size, 'sha256' => $sha, 'valid_from' => $from, 'valid_to' => $to !== '' ? $to : null,
                'status' => 'pending', 'mandate_id' => (int) $locked->id, 'president_user_id' => get_current_user_id(),
                'uploaded_by' => get_current_user_id(), 'created_at' => current_time('mysql'),
            ), array('%s','%s','%s','%s','%d','%s','%s','%s','%s','%d','%d','%d','%s'));
            if (!$inserted || false === $wpdb->query('COMMIT')) { throw new RuntimeException('upload_failed'); }
            $in_transaction = false;
            $saved = true;
        } catch (Throwable $e) {
            // Keep the flow fail closed; the uploaded file is removed below on error.
        } finally {
            if ($in_transaction) { $wpdb->query('ROLLBACK'); }
            self::release_facsimile_write_lock();
        }
        if (!$saved) { wp_delete_file($path); self::facsimile_redirect('upload_failed'); }
        self::facsimile_redirect('uploaded');
    }

    /** Fail closed if the legacy global upload could replace mandate authority. */
    private static function legacy_admin_facsimile_write_blocked($for_update = false) {
        global $wpdb;
        $facsimiles = $wpdb->prefix . 'zmp_admin_facsimiles';
        $suffix = $for_update ? ' FOR UPDATE' : '';
        $wpdb->last_error = '';
        $bound_facsimile = $wpdb->get_var("SELECT id FROM {$facsimiles} WHERE mandate_id>0 AND status IN ('pending','active') LIMIT 1{$suffix}");
        if ($wpdb->last_error !== '' || $bound_facsimile) { return true; }
        if (!class_exists('ZMP_President_Mandate')) { return false; }
        $mandates = ZMP_President_Mandate::table_name();
        $wpdb->last_error = '';
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $mandates));
        if ($wpdb->last_error !== '') { return true; }
        if ($found !== $mandates) { return false; }
        $wpdb->last_error = '';
        $active = $wpdb->get_var("SELECT id FROM {$mandates} WHERE status IN ('active','scheduled') LIMIT 1{$suffix}");
        return $wpdb->last_error !== '' || (bool) $active;
    }

    public static function save_admin_facsimile() {
        if(!current_user_can('manage_options')) wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        check_admin_referer('zmp_admin_facsimile'); global $wpdb;
        // Fast preflight only. Repeat these checks under the shared writer lock
        // and transaction immediately before any active-row update/insert.
        if (self::legacy_admin_facsimile_write_blocked()) { self::facsimile_redirect('error'); }
        $name=sanitize_text_field(wp_unslash($_POST['president_name']??''));
        $from=sanitize_text_field(wp_unslash($_POST['valid_from']??'')); $to=sanitize_text_field(wp_unslash($_POST['valid_to']??''));
        $valid_date=function($value){$d=DateTime::createFromFormat('!Y-m-d',$value);return $d&&$d->format('Y-m-d')===$value;};
        $file=$_FILES['facsimile_file']??array();
        if($name===''||mb_strlen($name)>190||!$valid_date($from)||($to!==''&&(!$valid_date($to)||$to<$from))||empty($file['tmp_name'])||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'])||(int)$file['size']<1||(int)$file['size']>2*1024*1024) self::facsimile_redirect('invalid');
        $image=@getimagesize($file['tmp_name']); $mime=is_array($image)?(string)($image['mime']??''):'';
        $extensions=array('image/png'=>'png','image/jpeg'=>'jpg'); if(!isset($extensions[$mime])) self::facsimile_redirect('invalid');
        $dir=self::private_facsimile_directory(); if(is_wp_error($dir)) self::facsimile_redirect('error');
        try{$key=bin2hex(random_bytes(24)).'.'.$extensions[$mime];}catch(Throwable $e){self::facsimile_redirect('error');}
        $path=trailingslashit($dir).$key;
        if(!move_uploaded_file($file['tmp_name'],$path)) self::facsimile_redirect('error');
        @chmod($path,0600); if((fileperms($path)&0777)!==0600){wp_delete_file($path);self::facsimile_redirect('error');} $size=(int)filesize($path); $sha=hash_file('sha256',$path); if($size<1||$size>2*1024*1024||!is_string($sha)){wp_delete_file($path);self::facsimile_redirect('error');} $original=sanitize_file_name(wp_unslash($file['name']??'facsimile.'.$extensions[$mime]));
        if (!self::acquire_facsimile_write_lock()) { wp_delete_file($path); self::facsimile_redirect('error'); }
        $transaction_started = false;
        $result = 'error';
        try {
            if (false !== $wpdb->query('START TRANSACTION')) {
                $transaction_started = true;
                if (!self::legacy_admin_facsimile_write_blocked(true)) {
                $effective = DateTimeImmutable::createFromFormat('!Y-m-d', $from, wp_timezone());
                $previous_day = $effective ? $effective->modify('-1 day')->format('Y-m-d') : '';
                $table = $wpdb->prefix.'zmp_admin_facsimiles';
                $clipped = $wpdb->query($wpdb->prepare("UPDATE $table SET valid_to=%s WHERE status='active' AND valid_from<%s AND (valid_to IS NULL OR valid_to >= %s)", $previous_day, $from, $from));
                $future_end = $to !== '' ? $to : '9999-12-31';
                $archived = $wpdb->query($wpdb->prepare("UPDATE $table SET status='archived' WHERE status='active' AND valid_from >= %s AND valid_from <= %s", $from, $future_end));
                if ($clipped !== false && $archived !== false) {
                    $ok = $wpdb->insert($wpdb->prefix.'zmp_admin_facsimiles',array('president_name'=>$name,'file_key'=>$key,'original_name'=>$original,'mime_type'=>$mime,'file_size'=>$size,'sha256'=>$sha,'valid_from'=>$from,'valid_to'=>$to?:null,'status'=>'active','uploaded_by'=>get_current_user_id(),'created_at'=>current_time('mysql')));
                    if ($ok) {
                        $transaction_started = false;
                        try {
                            $committed = $wpdb->query('COMMIT');
                            $result = $committed === false ? 'error_uncertain' : 'saved';
                        } catch (Throwable $e) {
                            $result = 'error_uncertain';
                        }
                    }
                }
                }
            }
        } catch (Throwable $e) {
            $result = 'error';
        } finally {
            if ($transaction_started) { $wpdb->query('ROLLBACK'); }
            self::release_facsimile_write_lock();
        }
        if ($result === 'error') { wp_delete_file($path); }
        self::facsimile_redirect($result === 'saved' ? 'saved' : 'error');
    }

    /** Return a verified private facsimile path for certificate generation; no URL is exposed. */
    public static function get_admin_facsimile($id = 0) {
        global $wpdb;
        $id = absint($id);
        $today = current_time('Y-m-d');
        $row = $id
            ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d", $id))
            : $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE status='active' AND valid_from<=%s AND (valid_to IS NULL OR valid_to>=%s) ORDER BY valid_from DESC,id DESC LIMIT 1", $today, $today));
        if (!$row || !preg_match('/^[a-f0-9]{48}\\.(png|jpg)$/', (string)$row->file_key)) { return new WP_Error('facsimile_not_found'); }
        $dir = self::private_facsimile_directory();
        if (is_wp_error($dir)) { return $dir; }
        $path = trailingslashit($dir) . $row->file_key;
        $real = realpath($path);
        $base = rtrim(str_replace('\\\\', '/', $dir), '/') . '/';
        $normalized = $real ? str_replace('\\\\', '/', $real) : '';
        if (!$real || strpos($normalized, $base) !== 0 || !is_file($real) || !is_readable($real) || !hash_equals((string)$row->sha256, (string)hash_file('sha256', $real))) { return new WP_Error('facsimile_integrity'); }
        return array('id'=>(int)$row->id, 'path'=>$real, 'mime'=>(string)$row->mime_type, 'sha256'=>(string)$row->sha256, 'name'=>(string)$row->president_name);
    }
    public static function download_admin_facsimile() {
        if(!current_user_can('manage_options')) wp_die('Немате дозвола.','Нема пристап',array('response'=>403));
        $id=absint($_GET['id']??0); check_admin_referer('zmp_admin_facsimile_'.$id); global $wpdb;
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_admin_facsimiles WHERE id=%d",$id));
        if(!$row||!preg_match('/^[a-f0-9]{48}\\.(png|jpg)$/',$row->file_key)) wp_die('Датотеката не е пронајдена.','Недостапно',array('response'=>404));
        $dir=self::private_facsimile_directory(); $path=is_wp_error($dir)?'':trailingslashit($dir).$row->file_key; $real=$path?realpath($path):false;
        if(!$real||strpos(str_replace('\\\\','/',$real),rtrim(str_replace('\\\\','/',$dir),'/').'/')!==0||!is_file($real)||!hash_equals((string)$row->sha256,(string)hash_file('sha256',$real))) wp_die('Приватната датотека не помина проверка.','Недостапно',array('response'=>404));
        nocache_headers(); header('Content-Type: '.($row->mime_type==='image/png'?'image/png':'image/jpeg')); header('Content-Disposition: inline; filename="facsimile-'.$id.'.'.($row->mime_type==='image/png'?'png':'jpg').'"'); header('X-Content-Type-Options: nosniff'); header('Content-Length: '.(int)filesize($real)); readfile($real); exit;
    }

    private static function private_facsimile_directory() {
        $root=realpath(!empty($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:ABSPATH); $parent=$root?realpath(dirname($root)):false;
        if(!$root||!$parent)return new WP_Error('facsimile_storage_unsafe');
        $dir=trailingslashit($parent).'zmp-private-facsimiles-'.substr(hash('sha256',home_url('/')),0,12);
        if(!is_dir($dir)&&!wp_mkdir_p($dir))return new WP_Error('facsimile_storage_failed');
        $real=realpath($dir); $normalized_root=rtrim(str_replace('\\\\','/',$root),'/').'/';
        if(!$real||strpos(str_replace('\\\\','/',trailingslashit($real)),$normalized_root)===0)return new WP_Error('facsimile_storage_unsafe');
        @chmod($real,0700); if((fileperms($real)&0777)!==0700)return new WP_Error('facsimile_storage_permissions'); return $real;
    }

    private static function facsimile_redirect($status) {
        wp_safe_redirect(add_query_arg('zmp_president_facsimile_status',sanitize_key($status),admin_url('admin.php?page=zmp-president-oversight'))); exit;
    }
    private static function form_url($season_id) { return wp_nonce_url(add_query_arg('zmp_registration_form',absint($season_id),home_url('/')),'zmp_form_'.absint($season_id)); }
    private static function render_notice() { $code=sanitize_key(wp_unslash($_GET['zmp_requirements_status']??''));$messages=array('saved'=>'Годишниот формулар и котизацијата се зачувани.','invoice_sent'=>'Податоците за котизација се испратени на двете адреси; новото барање е зачувано.','invoice_email_failed'=>'Барањето е зачувано одделно, но испраќањето не успеа до една или двете адреси. Проверете ја поштата на сајтот.','invoice_invalid'=>'Проверете го училиштето, категоријата и адресите за е-пошта.','invoice_submitted'=>'Барањата за фактура се зачувани; статусот на секоја е-пошта е прикажан подолу.','invoice_duplicate'=>'Ова барање веќе е примено; не е создадено ново барање.','fee_not_due'=>'Доказ за котизација не се бара пред квалификација за регионалното ниво.','fee_proof_saved'=>'Доказот за котизација за регионалното ниво е зачуван.','fee_proof_not_eligible'=>'Доказ може да се приложи само за ученик од вашето училиште што е потврден за регионално ниво и нема претходен доказ.','fee_unavailable'=>'Модераторите сè уште немаат поставено валидна котизација за оваа категорија.','fee_settings_invalid'=>'Проверете ги податоците и внесете образложение од најмалку 10 знаци.','fee_exception_invalid'=>'Проверете ја категоријата, училиштето, правилото и образложението.','fee_settings_saved'=>'Поставките за котизација се зачувани и евидентирани.','fee_forbidden'=>'Немате дозвола за ова барање.','fee_context_invalid'=>'Проверете го активниот профил и натпреварувачката година.','fee_batch_invalid'=>'Изберете најмалку една категорија и проверете ги податоците.','fee_item_invalid'=>'Една од избраните ставки не е исправна.','fee_school_forbidden'=>'Изберете училиште поврзано со вашиот наставнички профил.','fee_quantity_invalid'=>'Проверете го бројот на пријави или квалификувани ученици.','fee_amount_invalid'=>'Котизацијата не е во дозволениот формат.','fee_batch_empty'=>'Нема ставки за фактурирање по применетите ослободувања.','fee_invoice_details_invalid'=>'За секое избрано училиште внесете адреса, ЕДБ и валидни е-пошти.','fee_total_invalid'=>'Вкупниот износ не е валиден.','fee_batch_save_failed'=>'Барањето не е зачувано; обидете се повторно.','fee_request_save_failed'=>'Барањето за училиштето не е зачувано.','fee_item_save_failed'=>'Ставките за барањето не се зачувани.','fee_proof_invalid'=>'Изберете дали уплатата е извршена од наставникот или училиштето.','document_required'=>'Прикачете го потпишаниот годишен формулар, потврдата за уплата и потпишаната согласност.','consent_required'=>'Прикачете ја потпишаната согласност за медиумска употреба.','requirements_unavailable'=>'Модераторите треба да го постават годишниот формулар и износот за котизација пред пријавувањето.','document_invalid'=>'Документот е празен или го надминува лимитот од 15 MB.','document_type_invalid'=>'Прифатени се исправни PDF, JPG, JPEG или PNG документи.','document_storage_unsafe'=>'Приватната архива не е безбедно достапна.','document_storage_failed'=>'Не може да се подготви приватната архива.','document_store_failed'=>'Документот не е зачуван. Обидете се повторно.','save_error'=>'Промените не се зачувани. Обидете се повторно.','contact_invalid'=>'Проверете го името на училиштето, општината и е-поштата.','contact_saved'=>'Службената е-пошта е зачувана и потврдена.','contact_removed'=>'Е-поштата е отстранета од активниот список.','invoice_submitted'=>'Барањата за фактура се зачувани; статусот на е-поштата е прикажан подолу.','invoice_duplicate'=>'Ова барање веќе е примено; не е создадено ново барање.','fee_not_due'=>'Доказ за котизација не се бара пред квалификација за регионалното ниво.','fee_proof_saved'=>'Доказот за котизација за регионалното ниво е зачуван.','fee_proof_not_eligible'=>'Доказ може да се приложи само за ваш ученик потврден за регионално ниво без претходен доказ.','fee_unavailable'=>'Модераторите сè уште немаат поставено валидна котизација за оваа категорија.','fee_settings_invalid'=>'Проверете ги податоците и внесете образложение од најмалку 10 знаци.','fee_exception_invalid'=>'Проверете ја категоријата, училиштето, правилото и образложението.','fee_settings_saved'=>'Поставките за котизација се зачувани и евидентирани.','fee_forbidden'=>'Немате дозвола за ова барање.','fee_context_invalid'=>'Проверете го активниот профил и натпреварувачката година.','fee_batch_invalid'=>'Изберете најмалку една категорија и проверете ги податоците.','fee_item_invalid'=>'Една од избраните ставки не е исправна.','fee_school_forbidden'=>'Изберете училиште поврзано со вашиот наставнички профил.','fee_quantity_invalid'=>'Проверете го бројот на пријави или квалификувани ученици.','fee_amount_invalid'=>'Котизацијата не е во дозволениот формат.','fee_batch_empty'=>'Нема ставки за фактурирање по применетите ослободувања.','fee_invoice_details_invalid'=>'За секое избрано училиште внесете адреса, ЕДБ и валидни е-пошти.','fee_total_invalid'=>'Вкупниот износ не е валиден.','fee_batch_save_failed'=>'Барањето не е зачувано; обидете се повторно.','fee_request_save_failed'=>'Барањето за училиштето не е зачувано.','fee_item_save_failed'=>'Ставките за барањето не се зачувани.','fee_proof_invalid'=>'Изберете дали уплатата е извршена од наставникот или училиштето.');$messages['invoice_flow_moved']='Фактурното барање се поднесува преку новата посебна форма.';
$messages['fee_invoice_school_email_unverified']='Модератор мора претходно да ја потврди службената е-пошта на училиштето.';
$messages['fee_invoice_personal_email_mismatch']='Користете ја е-поштата поврзана со вашата најава.';
$messages['fee_item_duplicate']='Истата категорија не може да се додаде повеќе пати за истото училиште.';
$messages['fee_knowledge_already_invoiced']='Овој ученик веќе е вклучен во барање за фактура.';
$messages['fee_knowledge_changed']='Квалификацијата се промени. Освежете ја страната и обидете се повторно.';
if(isset($messages[$code]))echo '<p class="zmp-alert" role="status">'.esc_html($messages[$code]).'</p>'; }
    private static function can_moderate() { return is_user_logged_in()&&(current_user_can('manage_options')||current_user_can('zmp_moderator_cap')); }
    private static function redirect($status) { $url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_requirements_status',sanitize_key($status),remove_query_arg('zmp_requirements_status',$url)));exit; }
}













