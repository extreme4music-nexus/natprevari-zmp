<?php
if (!defined('ABSPATH')) { exit; }

/** Active-President authorization and auditable disclosure for school email requests. */
class ZMP_School_Email_Access {
    const SUBMIT_ACTION = 'zmp_school_email_request_submit';
    const DECIDE_ACTION = 'zmp_school_email_access_decide';
    private static function request_table() { global $wpdb; return $wpdb->prefix . 'zmp_school_email_requests'; }
    private static function event_table() { global $wpdb; return $wpdb->prefix . 'zmp_school_email_request_events'; }

    public static function register_hooks() {
        add_action('admin_post_' . self::SUBMIT_ACTION,array(__CLASS__,'handle_submit_request'));
        add_action('admin_post_' . self::DECIDE_ACTION,array(__CLASS__,'handle_decision'));
    }

    /** Create request/event storage only after the private school-profile table is ready. */
    public static function install() {
        global $wpdb;
        if (!class_exists('ZMP_Mentor_School_Profile') || !ZMP_Mentor_School_Profile::schema_is_compatible()) { self::debug_event('error','School email access schema prerequisite is not ready.',array('outcome'=>'fail_closed','error_code'=>'schema_not_ready')); return false; }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE " . self::request_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            requester_user_id BIGINT(20) UNSIGNED NOT NULL,
            owner_user_id BIGINT(20) UNSIGNED NOT NULL,
            mentor_id BIGINT(20) UNSIGNED NOT NULL,
            school_id BIGINT(20) UNSIGNED NOT NULL,
            requester_engagement_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            requester_engagement_epoch BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            owner_engagement_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            owner_engagement_epoch BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            school_key CHAR(64) NOT NULL,
            reason VARCHAR(500) NOT NULL,
            status VARCHAR(16) NOT NULL DEFAULT 'pending',
            pending_key CHAR(64) NULL DEFAULT NULL,
            approved_email_hash CHAR(64) NOT NULL DEFAULT '',
            approved_email_epoch BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            decided_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            decided_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY pending_key (pending_key),
            KEY requester_status (requester_user_id,status,created_at),
            KEY owner_school_status (owner_user_id,school_id,status),
            KEY status_created (status,created_at)
        ) $collate;");
        dbDelta("CREATE TABLE " . self::event_table() . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT(20) UNSIGNED NOT NULL,
            event_type VARCHAR(24) NOT NULL,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL,
            event_at DATETIME NOT NULL,
            event_data LONGTEXT NOT NULL,
            PRIMARY KEY  (id),
            KEY request_event_time (request_id,event_at),
            KEY actor_event_time (actor_user_id,event_at),
            KEY event_type (event_type)
        ) $collate;");
        return self::schema_is_compatible();
    }

    /** Fail closed unless required columns, types/nullability, and indexes match. */
    public static function schema_is_compatible() {
        // A valid local email schema is not sufficient: email reads and writes
        // depend on the school engagement and private profile schemas too.
        // Check those prerequisites before touching either email table so a
        // cooldown cannot allow disclosure against a broken prerequisite.
        $profile_ready = class_exists('ZMP_Mentor_School_Profile')
            && ZMP_Mentor_School_Profile::schema_is_compatible();
        if (!$profile_ready) {
            if (class_exists('ZMP_Role_Messenger') && is_callable(array('ZMP_Role_Messenger', 'mark_schema_failure_pending'))) {
                ZMP_Role_Messenger::mark_schema_failure_pending();
            }
            return false;
        }
        $ready = self::schema_is_ready();
        if (!$ready && class_exists('ZMP_Role_Messenger') && is_callable(array('ZMP_Role_Messenger', 'mark_schema_failure_pending'))) {
            ZMP_Role_Messenger::mark_schema_failure_pending();
        }
        return $ready;
    }

    private static function schema_is_ready() {
        return self::table_schema_is_ready(self::request_table(),array(
            'id'=>array('bigint(20) unsigned','NO'),'requester_user_id'=>array('bigint(20) unsigned','NO'),
            'owner_user_id'=>array('bigint(20) unsigned','NO'),'mentor_id'=>array('bigint(20) unsigned','NO'),
            'school_id'=>array('bigint(20) unsigned','NO'),'school_key'=>array('char(64)','NO'),
            'requester_engagement_id'=>array('bigint(20) unsigned','NO'),'requester_engagement_epoch'=>array('bigint(20) unsigned','NO'),
            'owner_engagement_id'=>array('bigint(20) unsigned','NO'),'owner_engagement_epoch'=>array('bigint(20) unsigned','NO'),
            'reason'=>array('varchar(500)','NO'),'status'=>array('varchar(16)','NO'),
            'pending_key'=>array('char(64)','YES'),'approved_email_hash'=>array('char(64)','NO'),
            'approved_email_epoch'=>array('bigint(20) unsigned','NO'),'created_at'=>array('datetime','NO'),
            'decided_by'=>array('bigint(20) unsigned','NO'),'decided_at'=>array('datetime','YES'),
        ),array('PRIMARY'=>array(array('id'),true),'pending_key'=>array(array('pending_key'),true),
            'requester_status'=>array(array('requester_user_id','status','created_at'),false),
            'owner_school_status'=>array(array('owner_user_id','school_id','status'),false),
            'status_created'=>array(array('status','created_at'),false)))
            && self::table_schema_is_ready(self::event_table(),array(
                'id'=>array('bigint(20) unsigned','NO'),'request_id'=>array('bigint(20) unsigned','NO'),
                'event_type'=>array('varchar(24)','NO'),'actor_user_id'=>array('bigint(20) unsigned','NO'),
                'event_at'=>array('datetime','NO'),'event_data'=>array('longtext','NO'),
            ),array('PRIMARY'=>array(array('id'),true),'request_event_time'=>array(array('request_id','event_at'),false),
                'actor_event_time'=>array(array('actor_user_id','event_at'),false),'event_type'=>array(array('event_type'),false)));
    }

    /** Only contacts at the requester's own school identity are selectable; no address is returned. */
    public static function get_requestable_contacts($requester_wp_user_id,$requester_school_id) {
        global $wpdb;
        self::private_no_store_headers();
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата за адреси не се подготвени.'); }
        $requester_wp_user_id=self::positive_id($requester_wp_user_id); $requester_school_id=self::positive_id($requester_school_id);
        if (!$requester_wp_user_id || $requester_wp_user_id!==get_current_user_id() || !is_user_logged_in()
            || !ZMP_Mentor_School_Profile::is_teacher($requester_wp_user_id)) { return self::error('forbidden','Само наставник може да побара службена адреса.'); }
        $requester_mentor=ZMP_Mentor_School_Profile::mentor_for_user($requester_wp_user_id);
        $requester_school=$requester_mentor?ZMP_Mentor_Schools::owned_school((int)$requester_mentor->id,$requester_school_id):null;
        if (!$requester_school) { return self::error('school_not_owned','Училишниот ангажман не е активен.'); }
        $rows=$wpdb->get_results($wpdb->prepare(
            'SELECT m.wp_user_id AS owner_user_id,m.full_name AS owner_name,s.id AS school_id,s.school_name,s.municipality
             FROM ' . $wpdb->prefix . 'zmp_mentor_schools s JOIN ' . $wpdb->prefix . 'zmp_mentors m ON m.id=s.mentor_id
             WHERE s.school_key=%s AND s.is_active=1 AND m.wp_user_id<>%d ORDER BY m.full_name,s.id',
            (string)$requester_school->school_key,$requester_wp_user_id
        ),ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error!=='') { return self::error('database_error','Контактите не можеа да се прочитаат.'); }
        $out=array();
        foreach ($rows as $row) {
            $owner_id=(int)$row['owner_user_id'];
            if (!ZMP_Mentor_School_Profile::is_teacher($owner_id)) { continue; }
            $out[]=array('owner_user_id'=>$owner_id,'school_id'=>(int)$row['school_id'],'owner_name'=>(string)$row['owner_name'],'school_name'=>(string)$row['school_name'],'municipality'=>(string)$row['municipality']);
        }
        return $out;
    }

    /** Submit or idempotently reuse the requester's pending request for this school contact. */
    public static function submit_request($requester_wp_user_id,$owner_wp_user_id,$school_id,$reason) {
        global $wpdb;
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата за адреси не се подготвени.'); }
        $requester_wp_user_id=self::positive_id($requester_wp_user_id); $owner_wp_user_id=self::positive_id($owner_wp_user_id); $school_id=self::positive_id($school_id);
        if (!$requester_wp_user_id || $requester_wp_user_id!==get_current_user_id() || !is_user_logged_in()
            || !ZMP_Mentor_School_Profile::is_teacher($requester_wp_user_id) || !$owner_wp_user_id || $owner_wp_user_id===$requester_wp_user_id) {
            return self::error('forbidden','Само најавен наставник може да поднесе барање.');
        }
        $reason=is_scalar($reason)?sanitize_textarea_field(trim((string)wp_unslash($reason))):'';
        if (self::text_length($reason)<10 || self::text_length($reason)>500) { return self::error('invalid_reason','Образложението мора да содржи од 10 до 500 знаци.'); }
        if (!ZMP_Mentor_School_Profile::is_teacher($owner_wp_user_id)) { return self::error('contact_unavailable','Контактот не е достапен за барање.'); }
        $owner_mentor=ZMP_Mentor_School_Profile::mentor_for_user($owner_wp_user_id);
        $requester_mentor=ZMP_Mentor_School_Profile::mentor_for_user($requester_wp_user_id);
        $requester_school=$requester_mentor?ZMP_Mentor_Schools::owned_school((int)$requester_mentor->id,$school_id):null;
        if (!$owner_mentor || !$requester_school) { return self::error('school_not_owned','Училишниот ангажман не е достапен.'); }
        // The form supplies the requester's own school engagement ID. Resolve the
        // owner's matching engagement by canonical school key before storing the request.
        $owner_school=$wpdb->get_row($wpdb->prepare('SELECT id,mentor_id,school_key,school_name,municipality,is_active,engagement_epoch FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND school_key=%s AND is_active=1 LIMIT 1',(int)$owner_mentor->id,(string)$requester_school->school_key));
        if (!$owner_school || $wpdb->last_error!=='') { return self::error('school_relationship_required','Барањето мора да се однесува на заедничко активно училиште.'); }
        $target_school_id=(int)$owner_school->id;
        $pending_key=hash('sha256',$requester_wp_user_id . ':' . $owner_wp_user_id . ':' . $target_school_id);
        if (!self::transaction_start()) { return self::error('database_error','Барањето не може безбедно да започне.'); }
        $existing_rows=$wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::request_table() . " WHERE requester_user_id=%d AND owner_user_id=%d AND school_id=%d AND status IN ('pending','approved') ORDER BY id FOR UPDATE",
            $requester_wp_user_id,$owner_wp_user_id,$target_school_id));
        if (!is_array($existing_rows) || $wpdb->last_error!=='') { self::transaction_finish(false); return self::error('database_error','Претходните барања не можеа да се проверат.'); }
        $locked_engagements=self::lock_current_engagements($requester_school,$owner_school,$requester_wp_user_id,$owner_wp_user_id,(int)$requester_mentor->id,(int)$owner_mentor->id);
        if (!$locked_engagements) { self::transaction_finish(false); return self::error('school_relationship_required','Училишниот ангажман се променил и барањето не е зачувано.'); }
        $requester_school=$locked_engagements['requester']; $owner_school=$locked_engagements['owner'];
        $profile=$wpdb->get_row($wpdb->prepare('SELECT official_email,official_email_epoch FROM ' . ZMP_Mentor_School_Profile::table_name() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$owner_mentor->id,$target_school_id));
        if ($wpdb->last_error!=='') { self::transaction_finish(false); return self::error('database_error','Училишната адреса не можеше безбедно да се провери.'); }
        $profile_available=$profile && (int)$profile->official_email_epoch>0 && trim((string)$profile->official_email)!=='' && is_email($profile->official_email);
        $current_email_hash=$profile_available?hash('sha256',strtolower(trim((string)$profile->official_email))):'';
        $invalidated_any=false;
        $invalidation_logs=array();
        foreach ($existing_rows as $existing) {
            $matching=self::request_engagements_match($existing,$requester_school,$owner_school,false);
            if ($matching===null) { self::transaction_finish(false); return self::error('database_error','Училишниот ангажман не можеше да се провери.'); }
            $matching=$matching && (int)$existing->requester_user_id===$requester_wp_user_id
                && (int)$existing->owner_user_id===$owner_wp_user_id
                && (int)$existing->mentor_id===(int)$owner_mentor->id;
            if ($matching) {
                $contact_changed=!$profile_available || ($existing->status==='approved' && ((int)$existing->approved_email_epoch!==(int)$profile->official_email_epoch
                    || !hash_equals((string)$existing->approved_email_hash,$current_email_hash)));
                if ($contact_changed) {
                    $now=current_time('mysql');
                    if (!self::invalidate_request($existing,$requester_wp_user_id,$now,'contact_changed')) {
                        self::transaction_finish(false); return self::error('database_error','Претходното одобрение не можеше безбедно да се повлече.');
                    }
                    $invalidation_logs[]=array('request_id'=>(int)$existing->id,'school_id'=>(int)$existing->school_id,'outcome'=>'invalidated','error_code'=>'contact_changed');
                    $invalidated_any=true;
                    continue;
                }
                if ($invalidated_any) {
                    if (!self::transaction_finish(true)) { return self::error('database_error','Претходното одобрение не можеше безбедно да се повлече.'); }
                    self::log_invalidation_events($invalidation_logs);
                } else { self::transaction_finish(false); }
                return array('request_id'=>(int)$existing->id,'status'=>(string)$existing->status,'existing'=>true);
            }
            $now=current_time('mysql');
            if (!self::invalidate_request($existing,$requester_wp_user_id,$now,'eligibility_changed')) {
                self::transaction_finish(false);
                return self::error('database_error','Претходното барање не можеше безбедно да се поништи.');
            }
            $invalidation_logs[]=array('request_id'=>(int)$existing->id,'school_id'=>(int)$existing->school_id,'outcome'=>'invalidated','error_code'=>'eligibility_changed');
            $invalidated_any=true;
        }
        if (!$profile_available) {
            if ($invalidated_any) {
                if (!self::transaction_finish(true)) { return self::error('database_error','Претходното одобрение не можеше безбедно да се повлече.'); }
                self::log_invalidation_events($invalidation_logs);
            }
            else { self::transaction_finish(false); }
            return self::error('contact_unavailable','Овој контакт моментално не може да прими барање.');
        }
        $created=current_time('mysql');
        $inserted=$wpdb->insert(self::request_table(),array('requester_user_id'=>$requester_wp_user_id,'owner_user_id'=>$owner_wp_user_id,
            'mentor_id'=>(int)$owner_mentor->id,'school_id'=>$target_school_id,
            'requester_engagement_id'=>(int)$requester_school->id,'requester_engagement_epoch'=>(int)$requester_school->engagement_epoch,
            'owner_engagement_id'=>(int)$owner_school->id,'owner_engagement_epoch'=>(int)$owner_school->engagement_epoch,
            'school_key'=>(string)$owner_school->school_key,'reason'=>$reason,
            'status'=>'pending','pending_key'=>$pending_key,'created_at'=>$created,'decided_by'=>0,'decided_at'=>null));
        if (!$inserted) {
            $winner=$wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::request_table() . ' WHERE pending_key=%s LIMIT 1 FOR UPDATE',$pending_key));
            $winner_valid=$winner && $winner->status==='pending' && self::request_engagements_match($winner,$requester_school,$owner_school,false);
            self::transaction_finish(false);
            if ($winner_valid) { return array('request_id'=>(int)$winner->id,'status'=>'pending','existing'=>true); }
            return self::error('database_error','Барањето не можеше да се зачува.');
        }
        $request_id=(int)$wpdb->insert_id;
        if (!$request_id || !self::append_event($request_id,'submitted',$requester_wp_user_id,$created,array('owner_user_id'=>$owner_wp_user_id,'school_id'=>$target_school_id))) {
            self::transaction_finish(false); return self::error('database_error','Барањето не можеше да се евидентира.');
        }
        if (!self::transaction_finish(true)) { return self::error('database_error','Барањето не можеше да се потврди.'); }
        self::log_invalidation_events($invalidation_logs);
        self::debug_event('info','School email access request submitted.',array('request_id'=>$request_id,'school_id'=>$target_school_id,'outcome'=>'pending'));
        return array('request_id'=>$request_id,'status'=>'pending','existing'=>false);
    }

    /** Statuses only for the signed-in requester; no requested email is returned. */
    public static function requests_for_requester($requester_wp_user_id) {
        global $wpdb;
        self::private_no_store_headers();
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата не се подготвени.'); }
        $requester_wp_user_id=self::positive_id($requester_wp_user_id);
        if (!$requester_wp_user_id || $requester_wp_user_id!==get_current_user_id() || !ZMP_Mentor_School_Profile::is_teacher($requester_wp_user_id)) { return self::error('forbidden','Немате пристап до овие барања.'); }
        $rows=$wpdb->get_results($wpdb->prepare(
            'SELECT r.id AS request_id,r.status,r.reason,r.created_at,r.decided_at,r.requester_user_id,r.owner_user_id,r.mentor_id,r.school_id,r.school_key,
                    r.requester_engagement_id,r.requester_engagement_epoch,r.owner_engagement_id,r.owner_engagement_epoch,r.approved_email_hash,r.approved_email_epoch,
                    m.full_name AS owner_name,s.school_name,s.municipality
             FROM ' . self::request_table() . ' r JOIN ' . $wpdb->prefix . 'zmp_mentor_schools s ON s.id=r.school_id AND s.mentor_id=r.mentor_id
             JOIN ' . $wpdb->prefix . 'zmp_mentors m ON m.id=r.mentor_id WHERE r.requester_user_id=%d ORDER BY r.created_at DESC,r.id DESC LIMIT 100',
            $requester_wp_user_id
        ),ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error!=='') { return self::error('database_error','Статусот на барањата не можеше да се прочита.'); }
        $invalidations=array();
        foreach ($rows as &$status_row) {
            if ((string)$status_row['status']!=='approved') { continue; }
            if (!self::transaction_start()) { unset($status_row); return self::error('database_error','Статусот на одобрението не можеше безбедно да се провери.'); }
            $request=$wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::request_table() . ' WHERE id=%d AND requester_user_id=%d LIMIT 1 FOR UPDATE',(int)$status_row['request_id'],$requester_wp_user_id));
            if ($wpdb->last_error!=='') { self::transaction_finish(false); unset($status_row); return self::error('database_error','Статусот на одобрението не можеше безбедно да се провери.'); }
            if (!$request || (string)$request->status!=='approved') {
                self::transaction_finish(false);
                $status_row['status']=$request?(string)$request->status:'invalidated';
                continue;
            }
            $engagements_match=self::request_engagements_match($request,null,null,true);
            if ($engagements_match===null) { self::transaction_finish(false); unset($status_row); return self::error('database_error','Училишниот ангажман не можеше да се провери.'); }
            $reason_code='';
            if (!$engagements_match || !ZMP_Mentor_School_Profile::is_teacher((int)$request->owner_user_id)) { $reason_code='eligibility_changed'; }
            else {
                $profile=$wpdb->get_row($wpdb->prepare('SELECT official_email,official_email_epoch FROM ' . ZMP_Mentor_School_Profile::table_name() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$request->mentor_id,(int)$request->school_id));
                if ($wpdb->last_error!=='') { self::transaction_finish(false); unset($status_row); return self::error('database_error','Училишната адреса не можеше безбедно да се провери.'); }
                $email=($profile && is_email($profile->official_email))?strtolower(trim((string)$profile->official_email)):'';
                $email_is_current=$email!=='' && (int)$profile->official_email_epoch>0
                    && (int)$request->approved_email_epoch===(int)$profile->official_email_epoch
                    && hash_equals((string)$request->approved_email_hash,hash('sha256',$email));
                if (!$email_is_current) { $reason_code='contact_changed'; }
            }
            if ($reason_code!=='') {
                $now=current_time('mysql');
                if (!self::invalidate_request($request,$requester_wp_user_id,$now,$reason_code) || !self::transaction_finish(true)) {
                    self::transaction_finish(false); unset($status_row); return self::error('database_error','Неважечкото одобрение не можеше безбедно да се поништи.');
                }
                $status_row['status']='invalidated';
                $invalidations[]=array('request_id'=>(int)$request->id,'school_id'=>(int)$request->school_id,'outcome'=>'invalidated','error_code'=>$reason_code);
            } elseif (!self::transaction_finish(true)) {
                unset($status_row); return self::error('database_error','Статусот на одобрението не можеше безбедно да се прочита.');
            }
        }
        unset($status_row);
        self::log_invalidation_events($invalidations);
        return array_map(static function($r){return array('request_id'=>(int)$r['request_id'],'owner_name'=>(string)$r['owner_name'],'school_name'=>(string)$r['school_name'],'municipality'=>(string)$r['municipality'],'status'=>(string)$r['status'],'reason'=>(string)$r['reason'],'created_at'=>(string)$r['created_at'],'decided_at'=>$r['decided_at']===null?'':(string)$r['decided_at']);},$rows);
    }

    /** Pending decisions are available only to the exact active President; records contain no address. */
    public static function pending_for_active_president($president_wp_user_id) {
        global $wpdb;
        self::private_no_store_headers();
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата не се подготвени.'); }
        $president_wp_user_id=self::positive_id($president_wp_user_id);
        if (!$president_wp_user_id || $president_wp_user_id!==get_current_user_id() || !self::is_active_president($president_wp_user_id)) { return self::error('forbidden','Само актуелниот претседател со активен мандат може да ги прегледа барањата.'); }
        $rows=$wpdb->get_results("SELECT r.id AS request_id,r.requester_user_id,r.owner_user_id,r.school_id,r.reason,r.created_at,um.display_name AS requester_name,om.full_name AS owner_name,s.school_name,s.municipality
             FROM " . self::request_table() . " r JOIN {$wpdb->prefix}zmp_mentor_schools s ON s.id=r.school_id AND s.mentor_id=r.mentor_id AND s.is_active=1
             JOIN {$wpdb->prefix}zmp_mentors om ON om.id=r.mentor_id JOIN {$wpdb->users} um ON um.ID=r.requester_user_id
             WHERE r.status='pending' ORDER BY r.created_at,r.id");
        if (!is_array($rows) || $wpdb->last_error!=='') { return self::error('database_error','Редицата на барања не можеше да се прочита.'); }
        $out=array();
        foreach ($rows as $r) {
            if (!ZMP_Mentor_School_Profile::is_teacher((int)$r->owner_user_id) || !ZMP_Mentor_School_Profile::is_teacher((int)$r->requester_user_id)) { continue; }
            $out[]=array('request_id'=>(int)$r->request_id,'requester_user_id'=>(int)$r->requester_user_id,'requester_name'=>(string)$r->requester_name,
                'owner_user_id'=>(int)$r->owner_user_id,'owner_name'=>(string)$r->owner_name,'school_id'=>(int)$r->school_id,
                'school_name'=>(string)$r->school_name,'municipality'=>(string)$r->municipality,'reason'=>(string)$r->reason,'created_at'=>(string)$r->created_at);
        }
        return $out;
    }

    /** Decide one pending request under a transaction; only the exact active President may decide. */
    public static function decide($request_id,$president_wp_user_id,$decision) {
        global $wpdb;
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата не се подготвени.'); }
        $request_id=self::positive_id($request_id); $president_wp_user_id=self::positive_id($president_wp_user_id);
        if (!$request_id || !$president_wp_user_id || $president_wp_user_id!==get_current_user_id() || !self::is_active_president($president_wp_user_id)) { return self::error('forbidden','Само актуелниот претседател може да одлучи за барањето.'); }
        if (!is_string($decision) || !in_array($decision,array('approved','rejected'),true)) { return self::error('invalid_decision','Одлуката не е важечка.'); }
        if (!self::transaction_start()) { return self::error('database_error','Одлуката не може безбедно да започне.'); }
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::request_table() . ' WHERE id=%d LIMIT 1 FOR UPDATE',$request_id));
        if (!$row || $wpdb->last_error!=='') { self::transaction_finish(false); return self::error('request_not_found','Барањето не постои.'); }
        if ($row->status!=='pending') {
            $same=$row->status===$decision && (int)$row->decided_by===$president_wp_user_id;
            self::transaction_finish(false);
            return $same?true:self::error('already_decided','Барањето веќе е разгледано.');
        }
        $engagements_match=self::request_engagements_match($row,null,null,true);
        if ($engagements_match===null) { self::transaction_finish(false); return self::error('database_error','Училишниот ангажман не можеше да се провери.'); }
        if (!$engagements_match) {
            $now=current_time('mysql');
            if (!self::invalidate_request($row,$president_wp_user_id,$now,'eligibility_changed') || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                return self::error('database_error','Неважечкото барање не можеше да се поништи.');
            }
            self::debug_event('warning','School email request invalidated during President decision.',array('request_id'=>$request_id,'school_id'=>(int)$row->school_id,'outcome'=>'invalidated','error_code'=>'eligibility_changed'));
            return self::error('contact_unavailable','Барањето е поништено бидејќи училишниот ангажман се променил.');
        }
        $school=ZMP_Mentor_Schools::owned_school((int)$row->mentor_id,(int)$row->school_id);
        $profile=$wpdb->get_row($wpdb->prepare('SELECT official_email,official_email_epoch FROM ' . ZMP_Mentor_School_Profile::table_name() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$row->mentor_id,(int)$row->school_id));
        $has_email=$profile && (int)$profile->official_email_epoch>0 && trim((string)$profile->official_email)!=='' && is_email($profile->official_email);
        if (!$school || (string)$school->school_key!==(string)$row->school_key || !ZMP_Mentor_School_Profile::is_teacher((int)$row->owner_user_id)
            || !ZMP_Mentor_School_Profile::is_teacher((int)$row->requester_user_id) || ($decision==='approved' && !$has_email)) {
            self::transaction_finish(false); return self::error('contact_unavailable','Барањето веќе не може безбедно да се одобри.');
        }
        $now=current_time('mysql');
        $email_hash=$decision==='approved'?hash('sha256',strtolower(trim((string)$profile->official_email))):'';
        $email_epoch=$decision==='approved'?(int)$profile->official_email_epoch:0;
        $updated=$wpdb->update(self::request_table(),array('status'=>$decision,'pending_key'=>null,'approved_email_hash'=>$email_hash,'approved_email_epoch'=>$email_epoch,'decided_by'=>$president_wp_user_id,'decided_at'=>$now),array('id'=>$request_id,'status'=>'pending'));
        if ($updated!==1 || !self::append_event($request_id,$decision,$president_wp_user_id,$now,array('requester_user_id'=>(int)$row->requester_user_id,'owner_user_id'=>(int)$row->owner_user_id,'school_id'=>(int)$row->school_id))) {
            self::transaction_finish(false); return self::error('database_error','Одлуката не можеше да се евидентира.');
        }
        if (!self::transaction_finish(true)) { return self::error('database_error','Одлуката не можеше да се потврди.'); }
        self::debug_event('info','School email request decision recorded.',array('request_id'=>$request_id,'school_id'=>(int)$row->school_id,'outcome'=>$decision));
        return true;
    }

    /** Disclose only to the approved requester; the nonce is POST-only and every reveal is audited. */
    public static function disclose_approved_email($request_id,$viewer_wp_user_id) {
        global $wpdb;
        if (!self::schema_is_compatible()) { return self::error('schema_not_ready','Барањата не се подготвени.'); }
        $request_id=self::positive_id($request_id); $viewer_wp_user_id=self::positive_id($viewer_wp_user_id);
        $nonce=isset($_POST['_wpnonce'])&&is_scalar($_POST['_wpnonce'])?sanitize_text_field(wp_unslash($_POST['_wpnonce'])):'';
        if (!$request_id || !$viewer_wp_user_id || $viewer_wp_user_id!==get_current_user_id() || !is_user_logged_in()
            || !ZMP_Mentor_School_Profile::is_teacher($viewer_wp_user_id) || !wp_verify_nonce($nonce,'zmp_school_email_disclose_' . $request_id)) {
            return self::error('forbidden','Адресата не може да се прикаже.');
        }
        self::private_no_store_headers();
        if (!self::transaction_start()) { return self::error('database_error','Адресата не може безбедно да се провери.'); }
        $request=$wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::request_table() . ' WHERE id=%d LIMIT 1 FOR UPDATE',$request_id));
        if (!$request || $wpdb->last_error!=='' || (int)$request->requester_user_id!==$viewer_wp_user_id || $request->status!=='approved') {
            self::transaction_finish(false); return self::error('not_approved','Адресата не е одобрена за овој корисник.');
        }
        $engagements_match=self::request_engagements_match($request,null,null,true);
        if ($engagements_match===null) { self::transaction_finish(false); return self::error('database_error','Училишниот ангажман не можеше да се провери.'); }
        if (!$engagements_match) {
            $now=current_time('mysql');
            if (!self::invalidate_request($request,$viewer_wp_user_id,$now,'eligibility_changed') || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                return self::error('database_error','Неважечкото одобрение не можеше да се поништи.');
            }
            self::debug_event('warning','Approved school email request invalidated before disclosure.',array('request_id'=>$request_id,'school_id'=>(int)$request->school_id,'outcome'=>'invalidated','error_code'=>'eligibility_changed'));
            return self::error('contact_unavailable','Адресата повеќе не е достапна бидејќи училишниот ангажман се променил.');
        }
        $school=ZMP_Mentor_Schools::owned_school((int)$request->mentor_id,(int)$request->school_id);
        $requester_mentor=ZMP_Mentor_School_Profile::mentor_for_user($viewer_wp_user_id);
        $relationship=$requester_mentor?$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . $wpdb->prefix . 'zmp_mentor_schools WHERE mentor_id=%d AND school_key=%s AND is_active=1 LIMIT 1',(int)$requester_mentor->id,(string)$request->school_key)):0;
        $profile=$wpdb->get_row($wpdb->prepare('SELECT official_email,official_email_epoch FROM ' . ZMP_Mentor_School_Profile::table_name() . ' WHERE mentor_id=%d AND school_id=%d LIMIT 1 FOR UPDATE',(int)$request->mentor_id,(int)$request->school_id));
        if ($wpdb->last_error!=='') { self::transaction_finish(false); return self::error('database_error','Адресата не може безбедно да се провери.'); }
        $current_email=$profile?trim((string)$profile->official_email):'';
        if (!$school || (string)$school->school_key!==(string)$request->school_key || !$relationship
            || !ZMP_Mentor_School_Profile::is_teacher((int)$request->owner_user_id)) {
            self::transaction_finish(false); return self::error('contact_unavailable','Адресата веќе не е достапна.');
        }
        $email_current=$profile && (int)$profile->official_email_epoch>0 && $current_email!=='' && is_email($current_email)
            && (int)$request->approved_email_epoch===(int)$profile->official_email_epoch
            && hash_equals((string)$request->approved_email_hash,hash('sha256',strtolower($current_email)));
        if (!$email_current) {
            $now=current_time('mysql');
            if (!self::invalidate_request($request,$viewer_wp_user_id,$now,'contact_changed') || !self::transaction_finish(true)) {
                self::transaction_finish(false);
                return self::error('database_error','Одобрението не можеше безбедно да се повлече.');
            }
            self::debug_event('warning','Approved school email request revoked after contact change.',array('request_id'=>$request_id,'school_id'=>(int)$request->school_id,'outcome'=>'invalidated','error_code'=>'contact_changed'));
            return self::error('contact_unavailable','Адресата повеќе не е достапна за ова одобрение.');
        }
        $now=current_time('mysql');
        if (!self::append_event($request_id,'disclosed',$viewer_wp_user_id,$now,array('owner_user_id'=>(int)$request->owner_user_id,'school_id'=>(int)$request->school_id))) {
            self::transaction_finish(false); return self::error('database_error','Приказот на адресата не можеше да се евидентира.');
        }
        if (!self::transaction_finish(true)) { return self::error('database_error','Приказот на адресата не можеше да се потврди.'); }
        self::debug_event('info','Approved school email disclosed to requester.',array('request_id'=>$request_id,'school_id'=>(int)$request->school_id,'outcome'=>'disclosed'));
        return $current_email;
    }

    /** POST endpoint for teacher requests; sensitive values are never placed in the redirect. */
    public static function handle_submit_request() {
        if (!is_user_logged_in() || !wp_verify_nonce(self::post_scalar('_wpnonce'),'zmp_school_email_request_submit')) { wp_die('Безбедносната проверка не помина.','Нема пристап',array('response'=>403)); }
        $result=self::submit_request(get_current_user_id(),self::post_int('owner_user_id'),self::post_int('school_id'),self::post_scalar('reason'));
        $status=is_wp_error($result)?'request_failed':'request_submitted';
        self::redirect_with_status($status);
    }

    /** President decision endpoint; the nonce is bound to the request ID. */
    public static function handle_decision() {
        $request_id=self::post_int('request_id');
        if (!is_user_logged_in() || !$request_id || !wp_verify_nonce(self::post_scalar('_wpnonce'),'zmp_school_email_access_decide_' . $request_id)) { wp_die('Безбедносната проверка не помина.','Нема пристап',array('response'=>403)); }
        $decision=self::post_scalar('decision');
        $result=self::decide($request_id,get_current_user_id(),$decision);
        self::redirect_with_status(is_wp_error($result)?'decision_failed':($decision==='approved'?'request_approved':'request_rejected'));
    }

    private static function is_active_president($user_id) {
        $user=get_userdata(absint($user_id));
        if (!$user) { return false; }
        return ZMP_Mentor_School_Profile::has_role_stack((int)$user_id, 'zmp_president', array('zmp_teacher','zmp_moderator','zmp_commission','zmp_testator','zmp_organizer'))
            && user_can($user,'view_zmp_president_dashboard') && class_exists('ZMP_President_Mandate')
            && ZMP_President_Mandate::user_has_active_mandate((int)$user_id);
    }
    private static function append_event($request_id,$type,$actor,$at,$data) {
        global $wpdb;
        return (bool)$wpdb->insert(self::event_table(),array('request_id'=>(int)$request_id,'event_type'=>sanitize_key($type),'actor_user_id'=>(int)$actor,'event_at'=>$at,
            'event_data'=>wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
    }
    /** Compare both request-bound engagements with their current immutable row IDs and epochs. */
    private static function request_engagements_match($request,$requester_snapshot=null,$owner_snapshot=null,$for_update=false) {
        global $wpdb;
        if (!$request || (int)($request->requester_engagement_id ?? 0)<1 || (int)($request->owner_engagement_id ?? 0)<1
            || (int)($request->requester_engagement_epoch ?? 0)<1 || (int)($request->owner_engagement_epoch ?? 0)<1
            || (int)$request->owner_engagement_id!==(int)$request->school_id) { return false; }
        if ($requester_snapshot && ((int)$requester_snapshot->id!==(int)$request->requester_engagement_id
            || (int)$requester_snapshot->engagement_epoch!==(int)$request->requester_engagement_epoch
            || (int)$requester_snapshot->is_active!==1 || (string)$requester_snapshot->school_key!==(string)$request->school_key)) { return false; }
        if ($owner_snapshot && ((int)$owner_snapshot->id!==(int)$request->owner_engagement_id
            || (int)$owner_snapshot->engagement_epoch!==(int)$request->owner_engagement_epoch
            || (int)$owner_snapshot->is_active!==1 || (string)$owner_snapshot->school_key!==(string)$request->school_key)) { return false; }
        if ($requester_snapshot && $owner_snapshot) { return true; }
        $lock=$for_update?' FOR UPDATE':'';
        $rows=$wpdb->get_results($wpdb->prepare(
            'SELECT s.id,s.mentor_id,s.school_key,s.engagement_epoch,m.wp_user_id FROM ' . $wpdb->prefix . 'zmp_mentor_schools s
             JOIN ' . $wpdb->prefix . 'zmp_mentors m ON m.id=s.mentor_id
             WHERE s.id IN (%d,%d) AND s.is_active=1 ORDER BY s.id' . $lock,
            (int)$request->requester_engagement_id,(int)$request->owner_engagement_id));
        if ($wpdb->last_error!=='') { return null; }
        if (!is_array($rows) || count($rows)!==2) { return false; }
        $by_id=array(); foreach ($rows as $engagement) { $by_id[(int)$engagement->id]=$engagement; }
        $requester=$by_id[(int)$request->requester_engagement_id]??null;
        $owner=$by_id[(int)$request->owner_engagement_id]??null;
        return $requester && $owner
            && (int)$requester->wp_user_id===(int)$request->requester_user_id
            && (int)$requester->engagement_epoch===(int)$request->requester_engagement_epoch
            && (int)$owner->mentor_id===(int)$request->mentor_id
            && (int)$owner->wp_user_id===(int)$request->owner_user_id
            && (int)$owner->engagement_epoch===(int)$request->owner_engagement_epoch
            && (string)$requester->school_key===(string)$request->school_key
            && (string)$owner->school_key===(string)$request->school_key;
    }
    /** Lock exact active requester and owner rows in deterministic ID order before inserting. */
    private static function lock_current_engagements($requester_snapshot,$owner_snapshot,$requester_user_id,$owner_user_id,$requester_mentor_id,$owner_mentor_id) {
        global $wpdb;
        $table=$wpdb->prefix . 'zmp_mentor_schools';
        $rows=$wpdb->get_results($wpdb->prepare(
            "SELECT s.id,s.mentor_id,s.school_key,s.is_active,s.engagement_epoch,m.wp_user_id FROM $table s
             JOIN {$wpdb->prefix}zmp_mentors m ON m.id=s.mentor_id
             WHERE s.id IN (%d,%d) ORDER BY s.id FOR UPDATE",
            (int)$requester_snapshot->id,(int)$owner_snapshot->id));
        if (!is_array($rows) || $wpdb->last_error!=='' || count($rows)!==2) { return null; }
        $by_id=array(); foreach ($rows as $row) { $by_id[(int)$row->id]=$row; }
        $requester=$by_id[(int)$requester_snapshot->id]??null;
        $owner=$by_id[(int)$owner_snapshot->id]??null;
        foreach (array($requester,$owner) as $row) { if (!$row || (int)$row->is_active!==1 || (string)$row->school_key!==(string)$requester_snapshot->school_key) { return null; } }
        if ((int)$requester->wp_user_id!==(int)$requester_user_id || (int)$requester->mentor_id!==(int)$requester_mentor_id
            || (int)$owner->wp_user_id!==(int)$owner_user_id || (int)$owner->mentor_id!==(int)$owner_mentor_id) { return null; }
        return array('requester'=>$requester,'owner'=>$owner);
    }
    /** Permanently revoke pending/approved request access and append a non-sensitive event. */
    private static function invalidate_request($request,$actor_user_id,$at,$reason_code) {
        global $wpdb;
        if (!$request || !in_array((string)$request->status,array('pending','approved'),true)) { return false; }
        $updated=$wpdb->update(self::request_table(),array('status'=>'invalidated','pending_key'=>null,'approved_email_hash'=>'','approved_email_epoch'=>0,
            'decided_by'=>absint($actor_user_id),'decided_at'=>$at),array('id'=>(int)$request->id,'status'=>(string)$request->status));
        if ($updated!==1) { return false; }
        $event_type=$reason_code==='contact_changed'?'contact_changed':'eligibility_invalidated';
        return self::append_event((int)$request->id,$event_type,absint($actor_user_id),$at,array(
            'requester_user_id'=>(int)$request->requester_user_id,'owner_user_id'=>(int)$request->owner_user_id,
            'requester_engagement_id'=>(int)($request->requester_engagement_id ?? 0),'owner_engagement_id'=>(int)($request->owner_engagement_id ?? 0),
            'reason_code'=>sanitize_key($reason_code),
        ));
    }
    private static function debug_event($level,$message,$context) {
        if (class_exists('ZMP_Debug')) { ZMP_Debug::log($level,$message,$context,'school_email_access'); }
    }
    private static function log_invalidation_events($events) {
        foreach ((array)$events as $event) {
            $code=sanitize_key($event['error_code']??'eligibility_changed');
            $message=$code==='contact_changed'?'School email request invalidated after contact change.':'Stale school email request invalidated before resubmission.';
            self::debug_event('warning',$message,array('request_id'=>(int)($event['request_id']??0),'school_id'=>(int)($event['school_id']??0),
                'outcome'=>'invalidated','error_code'=>$code));
        }
    }
    private static function transaction_start() { global $wpdb; return $wpdb->query('START TRANSACTION')!==false; }
    private static function transaction_finish($commit) {
        global $wpdb;
        if (!$commit) { $wpdb->query('ROLLBACK'); return false; }
        if ($wpdb->query('COMMIT')===false) { $wpdb->query('ROLLBACK'); return false; }
        return true;
    }
    private static function table_schema_is_ready($table,$required_columns,$required_indexes) {
        global $wpdb;
        $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
        if ((string)$found!==(string)$table) { return false; }
        $columns=$wpdb->get_results('SHOW COLUMNS FROM `' . esc_sql($table) . '`');
        if (!is_array($columns)) { return false; }
        $actual=array(); foreach ($columns as $column) { if (is_object($column)&&isset($column->Field)) { $actual[(string)$column->Field]=$column; } }
        foreach ($required_columns as $name=>$shape) {
            if (!isset($actual[$name]) || ZMP_Database::normalize_schema_type($actual[$name]->Type)!==ZMP_Database::normalize_schema_type($shape[0]) || strtoupper((string)$actual[$name]->Null)!==$shape[1]) { return false; }
        }
        $rows=$wpdb->get_results('SHOW INDEX FROM `' . esc_sql($table) . '`'); if (!is_array($rows)) { return false; }
        $indexes=array();
        foreach ($rows as $row) {
            if (!is_object($row)||empty($row->Key_name)||empty($row->Seq_in_index)) { continue; }
            $name=(string)$row->Key_name; $seq=(int)$row->Seq_in_index;
            $indexes[$name]['columns'][$seq]=(string)$row->Column_name; $indexes[$name]['unique']=((int)$row->Non_unique===0);
        }
        foreach ($required_indexes as $name=>$expected) {
            if (!isset($indexes[$name])) { return false; }
            ksort($indexes[$name]['columns']);
            if (array_values($indexes[$name]['columns'])!==$expected[0] || $indexes[$name]['unique']!==$expected[1]) { return false; }
        }
        return true;
    }
    private static function post_scalar($key) {
        if (!isset($_POST[$key])||!is_scalar($_POST[$key])) { return ''; }
        return sanitize_text_field(wp_unslash($_POST[$key]));
    }
    private static function post_int($key) { return self::positive_id(self::post_scalar($key)); }
    private static function positive_id($value) {
        if (!is_int($value) && !is_string($value)) { return 0; }
        $text=(string)$value;
        if ($text==='' || !preg_match('/^[0-9]+$/',$text)) { return 0; }
        $id=(int)$text;
        return $id>0?$id:0;
    }
    private static function redirect_with_status($status) {
        $referer=wp_get_referer();
        if (!$referer || strpos($referer,home_url('/'))!==0) { $referer=home_url('/'); }
        wp_safe_redirect(add_query_arg('zmp_school_email_status',sanitize_key($status),$referer));
        exit;
    }
    private static function text_length($value) {
        if (function_exists('mb_strlen')) { return mb_strlen((string)$value,'UTF-8'); }
        if (preg_match_all('/./us',(string)$value,$matches)) { return count($matches[0]); }
        return strlen((string)$value);
    }
    private static function private_no_store_headers() {
        if (function_exists('nocache_headers')) { nocache_headers(); }
        if (!headers_sent()) { header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache'); }
    }
    private static function error($code,$message) {
        $safe_code=sanitize_key($code);
        self::debug_event('warning','School email access operation failed.',array('outcome'=>'failed','error_code'=>$safe_code));
        return new WP_Error($code,$message);
    }
}




