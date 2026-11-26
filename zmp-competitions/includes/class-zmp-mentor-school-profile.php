<?php
if (!defined('ABSPATH')) { exit; }

/** Private per-school teacher contact and employment profile data. */
class ZMP_Mentor_School_Profile {
    /** Allows explicitly permitted role combinations and requires Subscriber membership. */
    public static function has_role_stack($wp_user_id, $required_role, array $allowed_extra_roles = array()) {
        $user = get_userdata(self::positive_id($wp_user_id));
        if (!$user || !is_string($required_role) || $required_role === '') { return false; }
        $roles = array_values(array_unique((array) $user->roles));
        if (!in_array($required_role, $roles, true) || !in_array('subscriber', $roles, true)) { return false; }
        $allowed = array_values(array_unique(array_merge(array($required_role, 'subscriber'), $allowed_extra_roles)));
        foreach ($roles as $role) { if (!in_array($role, $allowed, true)) { return false; } }
        return true;
    }
    private static function table() { global $wpdb; return $wpdb->prefix . 'zmp_mentor_school_profiles'; }
    public static function table_name() { return self::table(); }

    private static function profile_fields() {
        return array(
            'official_email' => '',
            'employment_term' => '',
            'workload' => '',
            'retired' => 0,
            'replacement' => 0,
            'replacement_for' => '',
            'replacement_start_date' => '',
            'replacement_end_date' => '',
            'employment_term_visible' => 0,
            'workload_visible' => 0,
            'retired_visible' => 0,
            'replacement_visible' => 0,
            'replacement_for_visible' => 0,
            'replacement_start_date_visible' => 0,
            'replacement_end_date_visible' => 0,
        );
    }

    /** Additive, repeatable schema creation. New employment visibility is private by default. */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE " . self::table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            official_email VARCHAR(254) NOT NULL DEFAULT '',
            official_email_epoch BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
            employment_term VARCHAR(16) NOT NULL DEFAULT '',
            workload VARCHAR(8) NOT NULL DEFAULT '',
            retired TINYINT(1) NOT NULL DEFAULT 0,
            replacement TINYINT(1) NOT NULL DEFAULT 0,
            replacement_for VARCHAR(190) NOT NULL DEFAULT '',
            replacement_start_date DATE NULL,
            replacement_end_date DATE NULL,
            employment_term_visible TINYINT(1) NOT NULL DEFAULT 0,
            workload_visible TINYINT(1) NOT NULL DEFAULT 0,
            retired_visible TINYINT(1) NOT NULL DEFAULT 0,
            replacement_visible TINYINT(1) NOT NULL DEFAULT 0,
            replacement_for_visible TINYINT(1) NOT NULL DEFAULT 0,
            replacement_start_date_visible TINYINT(1) NOT NULL DEFAULT 0,
            replacement_end_date_visible TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY mentor_school (mentor_id,school_id),
            KEY school_id (school_id)
        ) $collate;");
        return self::schema_is_compatible();
    }

    /** Fail closed unless table columns, basic nullability, and indexes are present. */
    private static function schema_is_ready() {
        global $wpdb;
        if (!class_exists('ZMP_Mentor_Schools') || !ZMP_Mentor_Schools::schema_is_compatible()) { return false; }
        $table = self::table();
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ((string)$found !== $table) { return false; }
        $columns = $wpdb->get_results('SHOW COLUMNS FROM `' . esc_sql($table) . '`');
        if (!is_array($columns)) { return false; }
        $actual = array();
        foreach ($columns as $column) {
            if (is_object($column) && isset($column->Field)) { $actual[(string)$column->Field] = $column; }
        }
        $required = array(
            'id'=>array('bigint(20) unsigned','NO'), 'mentor_id'=>array('bigint(20) unsigned','NO'),
            'school_id'=>array('bigint(20) unsigned','NO'), 'official_email'=>array('varchar(254)','NO'),
            'official_email_epoch'=>array('bigint(20) unsigned','NO'),
            'employment_term'=>array('varchar(16)','NO'), 'workload'=>array('varchar(8)','NO'),
            'retired'=>array('tinyint(1)','NO'), 'replacement'=>array('tinyint(1)','NO'),
            'replacement_for'=>array('varchar(190)','NO'), 'replacement_start_date'=>array('date','YES'),
            'replacement_end_date'=>array('date','YES'), 'employment_term_visible'=>array('tinyint(1)','NO'),
            'workload_visible'=>array('tinyint(1)','NO'), 'retired_visible'=>array('tinyint(1)','NO'),
            'replacement_visible'=>array('tinyint(1)','NO'), 'replacement_for_visible'=>array('tinyint(1)','NO'),
            'replacement_start_date_visible'=>array('tinyint(1)','NO'), 'replacement_end_date_visible'=>array('tinyint(1)','NO'),
            'created_at'=>array('datetime','NO'), 'updated_at'=>array('datetime','NO'),
        );
        foreach ($required as $name=>$expected) {
            if (!isset($actual[$name]) || ZMP_Database::normalize_schema_type($actual[$name]->Type) !== ZMP_Database::normalize_schema_type($expected[0])
                || strtoupper((string)$actual[$name]->Null) !== $expected[1]) { return false; }
        }
        if ((string)($actual['official_email_epoch']->Default ?? '') !== '1') { return false; }
        $index_rows = $wpdb->get_results('SHOW INDEX FROM `' . esc_sql($table) . '`');
        if (!is_array($index_rows)) { return false; }
        $indexes = array();
        foreach ($index_rows as $row) {
            if (!is_object($row) || empty($row->Key_name) || empty($row->Seq_in_index)) { continue; }
            $name=(string)$row->Key_name; $seq=(int)$row->Seq_in_index;
            $indexes[$name]['columns'][$seq]=(string)$row->Column_name;
            $indexes[$name]['unique']=((int)$row->Non_unique===0);
        }
        $expected_indexes=array('PRIMARY'=>array(array('id'),true),'mentor_school'=>array(array('mentor_id','school_id'),true),'school_id'=>array(array('school_id'),false));
        foreach ($expected_indexes as $name=>$expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns'])!==$expected[0] || $indexes[$name]['unique']!==$expected[1]) { return false; }
        }
        return true;
    }

    /** Fail closed and persist repair intent only when a profile operation checks a broken schema. */
    public static function schema_is_compatible() {
        $ready = self::schema_is_ready();
        if (!$ready && class_exists('ZMP_Role_Messenger') && is_callable(array('ZMP_Role_Messenger', 'mark_schema_failure_pending'))) {
            ZMP_Role_Messenger::mark_schema_failure_pending();
        }
        return $ready;
    }

    /** Get complete private profile fields for the owner only; a new row returns private defaults. */
    public static function get_profile($wp_user_id, $school_id) {
        global $wpdb;
        self::private_no_store_headers();
        $wp_user_id=self::positive_id($wp_user_id); $school_id=self::positive_id($school_id);
        if (!self::schema_is_compatible()) { self::debug_event('error','School profile schema is not ready.',array('school_id'=>$school_id,'outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return self::error('schema_not_ready','Училишниот профил не е подготвен.'); }
        if (!$wp_user_id || $wp_user_id!==get_current_user_id() || !self::can_manage_own_teacher_profile($wp_user_id)) { return self::error('forbidden','Немате пристап до овој училишен профил.'); }
        $mentor=self::mentor_for_user($wp_user_id);
        if (!$mentor) { return self::error('teacher_profile_required','Наставничкиот профил не е достапен.'); }
        $school=ZMP_Mentor_Schools::owned_school((int)$mentor->id,$school_id);
        if (!$school) { return self::error('school_not_owned','Училиштето не е активно во овој профил.'); }
        $profile=self::profile_defaults();
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1',(int)$mentor->id,$school_id),ARRAY_A);
        if ($wpdb->last_error!=='') { return self::error('database_error','Профилот не можеше да се прочита.'); }
        if (is_array($row)) { $profile=self::normalize_row($row); }
        return $profile;
    }

    /** Owner-scoped active engagement list for profile controls, including private defaults. */
    public static function get_engagements($wp_user_id) {
        global $wpdb;
        self::private_no_store_headers();
        $wp_user_id=self::positive_id($wp_user_id);
        if (!self::schema_is_compatible()) { self::debug_event('error','School profile schema is not ready.',array('outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return self::error('schema_not_ready','Училишниот профил не е подготвен.'); }
        if (!$wp_user_id || $wp_user_id!==get_current_user_id() || !self::can_manage_own_teacher_profile($wp_user_id)) { return self::error('forbidden','Немате пристап до училишните ангажмани.'); }
        $mentor=self::mentor_for_user($wp_user_id);
        if (!$mentor) { return self::error('teacher_profile_required','Наставничкиот профил не е поврзан со оваа сметка.'); }
        $rows=ZMP_Mentor_Schools::for_mentor((int)$mentor->id,true);
        $out=array();
        foreach ((array)$rows as $school) {
            $profile=self::get_profile($wp_user_id,(int)$school->id);
            if (is_wp_error($profile)) { return $profile; }
            $out[]=array('wp_user_id'=>$wp_user_id,'mentor_id'=>(int)$mentor->id,'school_id'=>(int)$school->id,'school_name'=>(string)$school->school_name,'municipality'=>(string)$school->municipality,'profile'=>$profile);
        }
        return $out;
    }

    /** Validate and replace a teacher's complete per-school profile payload. */
    /** `$payload` must be unslashed once by its request handler before this service validates it. */
    public static function save_profile($wp_user_id, $school_id, $payload) {
        global $wpdb;
        $wp_user_id=self::positive_id($wp_user_id); $school_id=self::positive_id($school_id);
        if (!self::schema_is_compatible()) { self::debug_event('error','School profile schema is not ready for save.',array('school_id'=>$school_id,'outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return self::error('schema_not_ready','Училишниот профил не е подготвен.'); }
        if (!$wp_user_id || $wp_user_id!==get_current_user_id() || !is_user_logged_in() || !self::can_manage_own_teacher_profile($wp_user_id) || !is_array($payload)) {
            self::debug_event('warning','School profile save authorization denied.',array('user_id'=>$wp_user_id,'school_id'=>$school_id,'outcome'=>'denied','error_code'=>'forbidden'));
            return self::error('forbidden','Само сопственикот на наставничкиот профил може да го менува.');
        }
        $mentor=self::mentor_for_user($wp_user_id);
        if (!$mentor || !ZMP_Mentor_Schools::owned_school((int)$mentor->id,$school_id)) { return self::error('school_not_owned','Училиштето не е активно во овој профил.'); }
        $data=self::validate_payload($payload);
        if (is_wp_error($data)) { return $data; }
        $now=current_time('mysql'); $data['updated_at']=$now;
        if ($wpdb->query('START TRANSACTION')===false) { return self::error('database_error','Профилот не можеше безбедно да се зачува.'); }
        $existing=$wpdb->get_row($wpdb->prepare('SELECT id,official_email,official_email_epoch FROM ' . self::table() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$mentor->id,$school_id));
        if ($wpdb->last_error!=='') { $wpdb->query('ROLLBACK'); return self::error('database_error','Профилот не можеше безбедно да се зачува.'); }
        $email_changed=false;
        if ($existing) {
            $old_epoch=(int)$existing->official_email_epoch;
            if ($old_epoch<1) { $wpdb->query('ROLLBACK'); return self::error('database_error','Верзијата на службената адреса не е важечка.'); }
            $old_email=strtolower(trim((string)$existing->official_email));
            $new_email=strtolower(trim((string)$data['official_email']));
            $email_changed=$old_email!==$new_email;
            $data['official_email_epoch']=$old_epoch+($email_changed?1:0);
            $saved=$wpdb->update(self::table(),$data,array('id'=>(int)$existing->id,'mentor_id'=>(int)$mentor->id,'school_id'=>$school_id));
            if ($saved===false) { $wpdb->query('ROLLBACK'); return self::error('database_error','Профилот не можеше да се зачува.'); }
        } else {
            $data['mentor_id']=(int)$mentor->id; $data['school_id']=$school_id; $data['official_email_epoch']=1; $data['created_at']=$now;
            if (!$wpdb->insert(self::table(),$data)) {
                $winner=$wpdb->get_row($wpdb->prepare('SELECT id,official_email,official_email_epoch FROM ' . self::table() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$mentor->id,$school_id));
                if (!$winner || $wpdb->last_error!=='' || (int)$winner->official_email_epoch<1) { $wpdb->query('ROLLBACK'); return self::error('database_error','Профилот не можеше да се зачува.'); }
                $email_changed=strtolower(trim((string)$winner->official_email))!==strtolower(trim((string)$data['official_email']));
                $data['official_email_epoch']=(int)$winner->official_email_epoch+($email_changed?1:0);
                unset($data['mentor_id'],$data['school_id'],$data['created_at']);
                if ($wpdb->update(self::table(),$data,array('id'=>(int)$winner->id,'mentor_id'=>(int)$mentor->id,'school_id'=>$school_id))===false) { $wpdb->query('ROLLBACK'); return self::error('database_error','Профилот не можеше да се зачува.'); }
            }
        }
        if ($wpdb->query('COMMIT')===false) { $wpdb->query('ROLLBACK'); return self::error('database_error','Профилот не можеше да се потврди.'); }
        self::debug_event('info','School profile saved.',array('user_id'=>$wp_user_id,'school_id'=>$school_id,'email_changed'=>$email_changed?1:0,'outcome'=>'saved'));
        return true;
    }

    /** Only authorized Teacher+ users can enumerate school choices for the internal directory. */
    public static function list_directory_schools($viewer_wp_user_id) {
        global $wpdb;
        $viewer_wp_user_id=self::positive_id($viewer_wp_user_id);
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Училишниот директориум не е подготвен.'); }
        if (!$viewer_wp_user_id || $viewer_wp_user_id!==get_current_user_id() || !self::can_view_directory($viewer_wp_user_id)) { self::debug_event('warning','School directory authorization denied.',array('user_id'=>$viewer_wp_user_id,'outcome'=>'denied','error_code'=>'forbidden')); return self::error('forbidden','Немате пристап до внатрешниот директориум.'); }
        $mentor=self::mentor_for_user($viewer_wp_user_id);
        if (!$mentor) { return array(); }
        $table=$wpdb->prefix . 'zmp_mentor_schools';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT id AS school_id,school_name,municipality FROM $table WHERE mentor_id=%d AND is_active=1 ORDER BY school_name,municipality,id",(int)$mentor->id),ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error!=='') { return self::error('database_error','Директориумот не можеше да се прочита.'); }
        return array_map(static function($row){return array('school_id'=>(int)$row['school_id'],'school_name'=>(string)$row['school_name'],'municipality'=>(string)$row['municipality']);},$rows);
    }

    /** Return peer employment data for one active school identity; hidden fields and email are omitted. */
    public static function get_employment_directory($viewer_wp_user_id, $school_id=0) {
        global $wpdb;
        self::private_no_store_headers();
        $viewer_wp_user_id=self::positive_id($viewer_wp_user_id); $school_id=self::positive_id($school_id);
        if (!self::schema_is_compatible()) { self::debug_event('error','School profile schema is not ready for directory.',array('school_id'=>$school_id,'outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return self::error('schema_not_ready','Училишниот директориум не е подготвен.'); }
        if (!$viewer_wp_user_id || $viewer_wp_user_id!==get_current_user_id() || !self::can_view_directory($viewer_wp_user_id)) { return self::error('forbidden','Немате пристап до овој дел од директориумот.'); }
        $allowed_schools=self::list_directory_schools($viewer_wp_user_id);
        if (is_wp_error($allowed_schools)) { return $allowed_schools; }
        $allowed_ids=array();
        foreach ((array)$allowed_schools as $allowed_school) {
            $allowed_id=self::positive_id($allowed_school['school_id']??0);
            if ($allowed_id) { $allowed_ids[$allowed_id]=$allowed_id; }
        }
        if (!$allowed_ids) { return array(); }
        if ($school_id && !isset($allowed_ids[$school_id])) {
            return self::error('school_not_owned','Училиштето не е достапно за оваа сметка.');
        }
        $school_table=$wpdb->prefix . 'zmp_mentor_schools';
        $allowed_placeholders=implode(',',array_fill(0,count($allowed_ids),'%d'));
        $allowed_args=array_values($allowed_ids);
        $allowed_rows=$wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT school_key FROM $school_table WHERE is_active=1 AND id IN ($allowed_placeholders)",
            $allowed_args
        ),ARRAY_A);
        if (!is_array($allowed_rows) || $wpdb->last_error!=='') { return self::error('database_error','Училишниот директориум не можеше да се провери.'); }
        $school_keys=array();
        foreach ($allowed_rows as $allowed_row) {
            $key=(string)($allowed_row['school_key']??'');
            if ($key!=='') { $school_keys[$key]=$key; }
        }
        if (!$school_keys) { return array(); }
        if ($school_id) {
            $school=$wpdb->get_row($wpdb->prepare("SELECT school_key FROM $school_table WHERE id=%d AND is_active=1 LIMIT 1",$school_id));
            if (!$school || $wpdb->last_error!=='') { return self::error('school_not_found','Училиштето не е достапно.'); }
            $requested_key=(string)$school->school_key;
            if ($requested_key==='') { return array(); }
            $school_keys=array($requested_key=>$requested_key);
        }
        $school_key_placeholders=implode(',',array_fill(0,count($school_keys),'%s'));
        $school_key_args=array_values($school_keys);
        $sql='SELECT m.id AS mentor_id,m.wp_user_id,m.full_name,(SELECT MIN(s2.id) FROM ' . $wpdb->prefix . 'zmp_mentor_schools s2 WHERE s2.school_key=s.school_key AND s2.is_active=1) AS school_id,s.school_name,s.municipality,p.employment_term,p.workload,p.retired,p.replacement,p.replacement_for,p.replacement_start_date,p.replacement_end_date,p.employment_term_visible,p.workload_visible,p.retired_visible,p.replacement_visible,p.replacement_for_visible,p.replacement_start_date_visible,p.replacement_end_date_visible
             FROM ' . $wpdb->prefix . 'zmp_mentor_schools s
             JOIN ' . $wpdb->prefix . 'zmp_mentors m ON m.id=s.mentor_id
             JOIN ' . self::table() . ' p ON p.mentor_id=s.mentor_id AND p.school_id=s.id
             WHERE s.is_active=1';
        $rows=$wpdb->get_results($wpdb->prepare(
            $sql . " AND s.school_key IN ($school_key_placeholders) ORDER BY s.school_name,m.full_name,s.id",
            $school_key_args
        ),ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error!=='') { return self::error('database_error','Податоците од директориумот не можеа да се прочитаат.'); }
        $out=array();
        $visible_fields=array('employment_term','workload','retired','replacement','replacement_for','replacement_start_date','replacement_end_date');
        foreach ($rows as $row) {
            if (!self::is_teacher((int)$row['wp_user_id'])) { continue; }
            $item=array('mentor_id'=>(int)$row['mentor_id'],'school_id'=>(int)$row['school_id'],'school_name'=>(string)$row['school_name'],'municipality'=>(string)$row['municipality'],'teacher_name'=>(string)$row['full_name']);
            foreach ($visible_fields as $field) {
                if ((int)$row[$field . '_visible']!==1) { continue; }
                $value=$row[$field];
                if ($field==='retired' || $field==='replacement') { $item[$field]=(int)$value; }
                elseif ($value!==null && (string)$value!=='') { $item[$field]=(string)$value; }
            }
            if (count($item)>5) { $out[]=$item; }
        }
        return $out;
    }

    /** Return full private defaults for a not-yet-created school profile. */
    public static function profile_defaults() { return self::profile_fields(); }

    /** Teacher-owned engagement resolver used by the email access service. */
    public static function mentor_for_user($wp_user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'zmp_mentors WHERE wp_user_id=%d ORDER BY id ASC LIMIT 1',self::positive_id($wp_user_id)));
    }

    /** Exact current teacher-role check used by teacher-to-teacher services. */
    public static function is_teacher($wp_user_id) {
        return self::has_role_stack($wp_user_id, 'zmp_teacher', array('administrator','zmp_president','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'));
    }

    /** Only an authenticated Teacher-role stack may manage its own school profile. */
    public static function can_manage_own_teacher_profile($wp_user_id) {
        $wp_user_id = self::positive_id($wp_user_id);
        return $wp_user_id > 0 && is_user_logged_in()
            && $wp_user_id === get_current_user_id()
            && self::is_teacher($wp_user_id);
    }
    public static function can_view_directory($wp_user_id) {
        $user=get_userdata(self::positive_id($wp_user_id));
        if (!$user) { return false; }
        if (user_can($user,'manage_options')) { return true; }
        if (self::is_teacher($wp_user_id) || self::has_role_stack($wp_user_id,'zmp_moderator')) { return true; }
        return self::has_role_stack($wp_user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && current_user_can('view_zmp_president_dashboard')
            && class_exists('ZMP_President_Mandate') && ZMP_President_Mandate::user_has_active_mandate((int)$wp_user_id);
    }
    private static function validate_payload($payload) {
        $defaults=self::profile_fields();
        foreach (array_keys($defaults) as $field) { if (!array_key_exists($field,$payload)) { return self::error('invalid_profile','Испратениот профил е нецелосен.'); } }
        if (!is_scalar($payload['official_email']) || !is_scalar($payload['employment_term']) || !is_scalar($payload['workload']) || !is_scalar($payload['replacement_for'])) {
            return self::error('invalid_profile','Полињата на профилот мора да бидат едноставни текстуални вредности.');
        }
        $email_raw=trim((string)$payload['official_email']);
        $email=$email_raw===''?'':sanitize_email($email_raw);
        if ($email_raw!=='' && ($email==='' || !is_email($email) || strlen($email)>254 || strtolower($email)!==strtolower($email_raw))) { return self::error('invalid_email','Внесете важечка службена е-пошта.'); }
        $term=sanitize_key((string)$payload['employment_term']);
        $workload=sanitize_key((string)$payload['workload']);
        if (!in_array($term,array('','fixed_term','indefinite'),true) || !in_array($workload,array('','full','part'),true)) { return self::error('invalid_employment','Изберете важечка категорија на вработување и фонд.'); }
        $retired=self::boolean_field($payload['retired']); $replacement=self::boolean_field($payload['replacement']);
        if ($retired===null || $replacement===null) { return self::error('invalid_employment','Статусните полиња не се важечки.'); }
        $replacement_for=sanitize_text_field((string)$payload['replacement_for']);
        $start=self::date_field($payload['replacement_start_date']); $end=self::date_field($payload['replacement_end_date']);
        if (is_wp_error($start) || is_wp_error($end)) { return self::error('invalid_replacement_period','Внесете ги заменскиот период и датумите во формат YYYY-MM-DD.'); }
        if ($replacement) {
            if (self::text_length($replacement_for)<2 || self::text_length($replacement_for)>190 || $start==='' || $end==='' || $start>$end) {
                return self::error('invalid_replacement','За замена задолжително внесете колега и валиден период од–до.');
            }
        } elseif ($replacement_for!=='' || $start!=='' || $end!=='') {
            return self::error('invalid_replacement','Податоците за колега и период се внесуваат само за активна замена.');
        }
        $data=array('official_email'=>$email,'employment_term'=>$term,'workload'=>$workload,'retired'=>$retired,'replacement'=>$replacement,'replacement_for'=>$replacement_for,'replacement_start_date'=>$start?:null,'replacement_end_date'=>$end?:null);
        foreach (array('employment_term_visible','workload_visible','retired_visible','replacement_visible','replacement_for_visible','replacement_start_date_visible','replacement_end_date_visible') as $field) {
            $value=self::boolean_field($payload[$field]);
            if ($value===null) { return self::error('invalid_visibility','Поставките за видливост не се важечки.'); }
            $data[$field]=$value;
        }
        return $data;
    }

    private static function normalize_row($row) {
        $out=self::profile_fields();
        foreach (array_keys($out) as $key) {
            if (!array_key_exists($key,$row)) { continue; }
            if (substr($key,-8)==='_visible' || in_array($key,array('retired','replacement'),true)) { $out[$key]=(int)$row[$key]; }
            elseif (in_array($key,array('replacement_start_date','replacement_end_date'),true)) { $out[$key]=$row[$key]===null?'':(string)$row[$key]; }
            else { $out[$key]=(string)$row[$key]; }
        }
        return $out;
    }
    private static function boolean_field($value) {
        if (is_bool($value)) { return $value?1:0; }
        if (is_int($value) && ($value===0 || $value===1)) { return $value; }
        if (is_string($value) && ($value==='0' || $value==='1')) { return (int)$value; }
        return null;
    }
    private static function date_field($value) {
        if (!is_scalar($value)) { return self::error('invalid_date','Датумот не е важечки.'); }
        $value=trim((string)$value);
        if ($value==='') { return ''; }
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        $errors=DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d')!==$value) { return self::error('invalid_date','Датумот не е важечки.'); }
        return $value;
    }
    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string)$value,'UTF-8'); }
        if (preg_match_all('/./us',(string)$value,$matches)) { return count($matches[0]); }
        return strlen((string)$value);
    }
    private static function positive_id($value) {
        if (!is_int($value) && !is_string($value)) { return 0; }
        $text=(string)$value;
        if ($text==='' || !preg_match('/^[0-9]+$/',$text)) { return 0; }
        $id=(int)$text;
        return $id>0?$id:0;
    }
    private static function private_no_store_headers() {
        if (function_exists('nocache_headers')) { nocache_headers(); }
        if (!headers_sent()) { header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); }
    }
    private static function debug_event($level,$message,$context) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level,$message,$context,'school_profile'); }
    }
    private static function error($code,$message) {
        self::debug_event('warning','School profile operation failed.',array('outcome'=>'failed','error_code'=>sanitize_key($code)));
        return new WP_Error($code,$message);
    }
}

