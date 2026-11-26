<?php
if (!defined('ABSPATH')) { exit; }

/** Category-scoped document decisions, private correction notices and one-use repair links. */
class ZMP_Document_Review {
    const REQUIRED_TYPES = array('signed_application','payment_proof','media_consent');
    const MAX_TOKEN_AGE = 604800;

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_entry_document_reviews (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            document_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(24) NOT NULL DEFAULT 'pending',
            reviewer_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            reviewer_note TEXT NOT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY document_scope (document_id),
            KEY scope_status (season_id,category_id,status),
            KEY entry_id (entry_id)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_entry_document_revisions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            document_id BIGINT(20) UNSIGNED NOT NULL,
            document_type VARCHAR(30) NOT NULL DEFAULT '',
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
            KEY document_history (document_id,created_at)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_document_repair_tokens (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            token_hash CHAR(64) NOT NULL,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            document_id BIGINT(20) UNSIGNED NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY document_id (document_id),
            KEY expires_at (expires_at)
        ) $c;");
        // Existing attachments enter the review queue once. Never overwrite a decision.
        $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}zmp_entry_document_reviews (season_id,category_id,entry_id,document_id,status,reviewer_id,reviewer_note,updated_at)
            SELECT d.season_id,e.category_id,d.entry_id,d.id,'pending',0,'',NOW()
            FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id");
        // Bring existing group participant lists into the same review/correction workflow.
        $wpdb->query("INSERT INTO {$wpdb->prefix}zmp_entry_documents (season_id,entry_id,document_type,file_key,drive_file_id,storage_backend,original_name,mime_type,file_size,sha256,uploaded_by,created_at)
            SELECT e.season_id,e.id,'participant_roster',e.roster_filename,e.roster_drive_file_id,e.roster_storage_backend,e.roster_original_name,
                CASE LOWER(SUBSTRING_INDEX(e.roster_original_name,'.',-1)) WHEN 'pdf' THEN 'application/pdf' WHEN 'csv' THEN 'text/csv' WHEN 'docx' THEN 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' WHEN 'xlsx' THEN 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ELSE 'application/octet-stream' END,
                COALESCE(f.file_size,0),e.roster_sha256,COALESCE(e.wp_user_id,0),e.created_at
            FROM {$wpdb->prefix}zmp_category_entries e
            LEFT JOIN {$wpdb->prefix}zmp_entry_documents f ON f.entry_id=e.id AND f.document_type='participant_roster'
            WHERE e.participant_type='group' AND (e.roster_filename<>'' OR e.roster_drive_file_id<>'') AND f.id IS NULL");
        $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}zmp_entry_document_reviews (season_id,category_id,entry_id,document_id,status,reviewer_id,reviewer_note,updated_at)
            SELECT d.season_id,e.category_id,d.entry_id,d.id,'pending',0,'',NOW()
            FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.document_type='participant_roster'");
    }

    public static function init() {
        add_action('init',array(__CLASS__,'handle_review_post'),19);
        add_action('init',array(__CLASS__,'handle_replacement_post'),2);
        add_action('wp_ajax_zmp_document_token_info',array(__CLASS__,'ajax_token_info'));
        add_action('wp_ajax_nopriv_zmp_document_token_info',array(__CLASS__,'ajax_token_info'));
        add_action('wp_ajax_zmp_document_token_upload',array(__CLASS__,'ajax_token_upload'));
        add_action('wp_ajax_nopriv_zmp_document_token_upload',array(__CLASS__,'ajax_token_upload'));
        add_action('init',array(__CLASS__,'handle_preview'),0);
        // The emailed bearer link must work even before a teacher has signed in and
        // even when the teacher has no pending-document row rendered on that page.
        add_action('wp_footer',array(__CLASS__,'render_token_handler'),99);
    }

    public static function render_token_handler(){ echo self::token_upload_script(); }

    public static function can_review($season_id,$category_id) {
        if(current_user_can('manage_options')||current_user_can('zmp_moderator_cap')) return true;
        if(!is_user_logged_in()||(!current_user_can('zmp_commission_cap')&&!current_user_can('view_zmp_commission_dashboard'))) return false;
        return class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::has_any_assignment(absint($season_id),absint($category_id),get_current_user_id());
    }

    public static function can_review_entry($season_id,$category_id,$entry_id) {
        if(!self::can_review($season_id,$category_id)) return false;
        if(current_user_can('manage_options')||current_user_can('zmp_moderator_cap')) return true;
        if(!class_exists('ZMP_Category_Jury')) return false;
        return ZMP_Category_Jury::entry_visible_to_current_juror($season_id,$category_id,$entry_id);
    }
    public static function documents_ready($entry_id) {
        global $wpdb;
        $entry=$wpdb->get_row($wpdb->prepare("SELECT participant_type FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d",absint($entry_id)));
        if(!$entry)return false;
        $docs=$wpdb->get_results($wpdb->prepare("SELECT d.id,d.document_type,r.status FROM {$wpdb->prefix}zmp_entry_documents d LEFT JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.entry_id=%d",absint($entry_id)));
        $types=array();
        foreach((array)$docs as $doc) {
            $types[$doc->document_type]=true;
            if($doc->status!=='approved') return false;
        }
        foreach(self::REQUIRED_TYPES as $required) if(empty($types[$required])) return false;
        if($entry->participant_type==='group'&&empty($types['participant_roster']))return false;
        return true;
    }

    public static function entry_label($entry_id) {
        global $wpdb;
        $entry=$wpdb->get_row($wpdb->prepare("SELECT participant_type FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d",absint($entry_id)));
        $docs=$wpdb->get_results($wpdb->prepare("SELECT d.document_type,COALESCE(r.status,'pending') AS review_status FROM {$wpdb->prefix}zmp_entry_documents d LEFT JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.entry_id=%d",absint($entry_id)));
        if(!$docs) return array('key'=>'waiting_documents','label'=>'Чека документи');
        $pending=0;$rejected=0;
        $roster=false;foreach($docs as $d){if($d->document_type==='participant_roster')$roster=true;if($d->review_status==='needs_correction')$rejected++;elseif($d->review_status!=='approved')$pending++;}
        if($entry&&$entry->participant_type==='group'&&!$roster)$pending++;
        if($rejected)return array('key'=>'needs_correction','label'=>'Документи за дополнување');
        if($pending)return array('key'=>'waiting_documents','label'=>'Чека проверка на документи');
        return array('key'=>'ready_for_review','label'=>'Подготвена за оценување');
    }

    public static function render_panel($season_id,$category_id) {
        if(!self::can_review($season_id,$category_id)) return '';
        global $wpdb;
        $entries=$wpdb->get_results($wpdb->prepare("SELECT e.id,e.participant_name,e.school_name,e.municipality,e.region,e.grade,e.participant_type,c.name_mk AS category_name,g.name_mk AS group_name,
            EXISTS(SELECT 1 FROM {$wpdb->prefix}zmp_jury_assessments a WHERE a.season_id=e.season_id AND a.category_id=e.category_id AND a.entry_id=e.id AND a.status='submitted') AS has_assessment,
            EXISTS(SELECT 1 FROM {$wpdb->prefix}zmp_category_final_results f WHERE f.season_id=e.season_id AND f.category_id=e.category_id AND f.entry_id=e.id AND f.status='confirmed') AS has_confirmed_result
            FROM {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE e.season_id=%d AND e.category_id=%d AND e.status IN ('submitted','approved','complete') ORDER BY e.created_at,e.id",absint($season_id),absint($category_id)));
        if(!current_user_can('manage_options')&&!current_user_can('zmp_moderator_cap')){
            if(!class_exists('ZMP_Category_Jury')){$entries=array();}
            else{$visible_ids=ZMP_Category_Jury::visible_entry_ids_for_current_juror($season_id,$category_id);if(!is_array($visible_ids))$entries=array();else $entries=array_values(array_filter((array)$entries,function($entry)use($visible_ids){return in_array((int)$entry->id,$visible_ids,true);}));}
        }        ob_start();
        echo '<section class="zmp-card zmp-document-review"><h4>Проверка на документи</h4><p>Пријавата ќе може да се оценува откако задолжителните документи ќе бидат одобрени. Секоја состојба има текст и ознака.</p>';
        if(!$entries){echo '<p>Нема поднесени пријави за проверка.</p></section>';return ob_get_clean();}
        echo '<div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Учесник / состав</th><th>Училиште</th><th>Статус на досие</th><th>Документи и одлуки</th></tr></thead><tbody>';
        foreach($entries as $entry){
            $label=self::entry_label($entry->id);
            $docs=$wpdb->get_results($wpdb->prepare("SELECT d.*,r.status AS review_status,r.reviewer_note,r.reviewed_at FROM {$wpdb->prefix}zmp_entry_documents d LEFT JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.entry_id=%d ORDER BY d.id",$entry->id));
            $has_roster=false;foreach((array)$docs as $existing_doc)if($existing_doc->document_type==='participant_roster')$has_roster=true;
            $evaluation_state=!empty($entry->has_confirmed_result)?array('key'=>'evaluated','label'=>'Оценета'):(!empty($entry->has_assessment)?array('key'=>'awaiting_decision','label'=>'Бодирана — чека конечна одлука'):array('key'=>'not_evaluated','label'=>'Неоценета'));
            echo '<tr><td>'.esc_html($entry->participant_name).'<br><small>'.esc_html($entry->category_name.' · '.$entry->group_name).'</small></td><td>'.esc_html($entry->school_name.' · '.$entry->municipality.' · '.ZMP_Regions::label($entry->region)).'</td><td><span class="zmp-doc-state zmp-doc-state--'.esc_attr($label['key']).'">'.esc_html($label['label']).'</span><br><span class="zmp-doc-state zmp-doc-state--'.esc_attr($evaluation_state['key']).'">'.esc_html($evaluation_state['label']).'</span></td><td>';
            if(!$docs) echo 'Нема прикачени документи.';
            if($entry->participant_type==='group'&&!$has_roster)echo '<p class="zmp-doc-state zmp-doc-state--pending">Недостасува архивиран список на учесници</p>';
            foreach((array)$docs as $doc){$status=$doc->review_status?:'pending';$status_label=self::status_label($status);$preview=esc_url(add_query_arg(array('zmp_entry_document_preview'=>(int)$doc->id,'_wpnonce'=>wp_create_nonce('zmp_entry_doc_preview_'.(int)$doc->id)),admin_url('admin-post.php')));$dialog='zmp-doc-preview-'.(int)$doc->id;
                echo '<details class="zmp-doc-item"><summary>'.esc_html(self::document_label($doc->document_type)).' — <span class="zmp-doc-state zmp-doc-state--'.esc_attr($status).'">'.esc_html($status_label).'</span></summary><p>'.esc_html($doc->original_name).' · '.esc_html(size_format((int)$doc->file_size)).'</p><button type="button" class="button" onclick="document.getElementById(\''.esc_js($dialog).'\').showModal()">Отвори документ</button><dialog id="'.esc_attr($dialog).'" class="zmp-doc-dialog"><form method="dialog"><button class="button" aria-label="Затвори">Затвори</button></form><iframe title="'.esc_attr(self::document_label($doc->document_type)).'" src="'.$preview.'" loading="lazy" sandbox="allow-same-origin"></iframe></dialog>';
                if($status==='needs_correction'&&$doc->reviewer_note!=='') echo '<p><strong>Причина:</strong> '.nl2br(esc_html($doc->reviewer_note)).'</p>';
                echo '<form method="post" class="zmp-form zmp-document-review-form">';wp_nonce_field('zmp_document_review_'.$doc->id);echo '<input type="hidden" name="zmp_document_review_action" value="decide"><input type="hidden" name="document_id" value="'.(int)$doc->id.'"><label>Забелешка / фактичка причина (најмалку 10 знаци)<textarea name="reviewer_note" rows="2" maxlength="2000" required>'.esc_textarea($doc->reviewer_note).'</textarea></label><button class="button button-primary" name="decision" value="approved">Одобри документ</button> <button class="button" name="decision" value="needs_correction">Побарај исправка</button></form></details>';
            }
            echo '</td></tr>';
        }
            echo '</tbody></table></div><style>.zmp-doc-dialog{width:min(92vw,1000px);height:min(88vh,800px);border:0;border-radius:12px;padding:1rem}.zmp-doc-dialog::backdrop{background:#102a43bb}.zmp-doc-dialog iframe{width:100%;height:calc(100% - 3rem);border:0}.zmp-doc-state{display:inline-block;padding:.2rem .55rem;border-radius:999px;font-weight:600;margin:.15rem 0}.zmp-doc-state--waiting_documents,.zmp-doc-state--pending,.zmp-doc-state--awaiting_decision{background:#fff1c2;color:#714d00}.zmp-doc-state--needs_correction{background:#ffe2df;color:#8b1d15}.zmp-doc-state--approved,.zmp-doc-state--ready_for_review,.zmp-doc-state--evaluated{background:#d9f2e3;color:#155b34}.zmp-doc-state--not_evaluated{background:#e8edf2;color:#435466}.zmp-doc-item{padding:.6rem;border:1px solid #d8e1e8;border-radius:8px;margin:.4rem 0}.zmp-document-review-form{display:grid;gap:.5rem;margin-top:.5rem}</style></section>';
        return ob_get_clean();
    }

    private static function status_label($status){$labels=array('pending'=>'Чека проверка','approved'=>'Уреден','needs_correction'=>'Потребна е исправка');return $labels[$status]??'Чека проверка';}
    private static function document_label($type){$labels=array('signed_application'=>'Потпишана пријава','payment_proof'=>'Доказ за уплата','media_consent'=>'Согласност за фотографирање и објавување','sheet_music'=>'Нотен запис','participant_roster'=>'Архивиран список на учесници');return $labels[$type]??'Приложен документ';}

    public static function handle_review_post(){
        if(($_POST['zmp_document_review_action']??'')!=='decide')return;
        if(!is_user_logged_in())wp_die('Најавете се.','Нема пристап',array('response'=>403));
        $doc_id=absint($_POST['document_id']??0);$decision=sanitize_key(wp_unslash($_POST['decision']??''));$note=sanitize_textarea_field(wp_unslash($_POST['reviewer_note']??''));
        if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce']??'')),'zmp_document_review_'.$doc_id))wp_die('Безбедносната проверка не помина.','Нема пристап',array('response'=>403));
        if(!in_array($decision,array('approved','needs_correction'),true)||mb_strlen($note)<10)self::redirect_review('invalid');
        global $wpdb;$doc=$wpdb->get_row($wpdb->prepare("SELECT d.*,e.category_id,e.wp_user_id,e.participant_name,e.school_name FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.id=%d",$doc_id));
        if(!$doc||!self::can_review_entry($doc->season_id,$doc->category_id,$doc->entry_id))wp_die('Немате пристап до оваа категорија.','Нема пристап',array('response'=>403));
        $token='';$token_hash='';
        if($decision==='needs_correction'){$token=bin2hex(random_bytes(32));$token_hash=hash('sha256',$token);$wpdb->delete($wpdb->prefix.'zmp_document_repair_tokens',array('document_id'=>$doc_id,'used_at'=>null));$created=$wpdb->insert($wpdb->prefix.'zmp_document_repair_tokens',array('token_hash'=>$token_hash,'season_id'=>(int)$doc->season_id,'entry_id'=>(int)$doc->entry_id,'document_id'=>$doc_id,'expires_at'=>gmdate('Y-m-d H:i:s',time()+self::MAX_TOKEN_AGE),'created_at'=>current_time('mysql')));if(!$created)self::redirect_review('save_error');}
        $review=array('season_id'=>(int)$doc->season_id,'category_id'=>(int)$doc->category_id,'entry_id'=>(int)$doc->entry_id,'document_id'=>$doc_id,'status'=>$decision,'reviewer_id'=>get_current_user_id(),'reviewer_note'=>$note,'reviewed_at'=>current_time('mysql'),'updated_at'=>current_time('mysql'));
        $saved=$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}zmp_entry_document_reviews (season_id,category_id,entry_id,document_id,status,reviewer_id,reviewer_note,reviewed_at,updated_at) VALUES (%d,%d,%d,%d,%s,%d,%s,%s,%s) ON DUPLICATE KEY UPDATE status=VALUES(status),reviewer_id=VALUES(reviewer_id),reviewer_note=VALUES(reviewer_note),reviewed_at=VALUES(reviewed_at),updated_at=VALUES(updated_at)",$review['season_id'],$review['category_id'],$review['entry_id'],$review['document_id'],$review['status'],$review['reviewer_id'],$review['reviewer_note'],$review['reviewed_at'],$review['updated_at']));
        if($saved===false){ if($token_hash!=='')$wpdb->delete($wpdb->prefix.'zmp_document_repair_tokens',array('token_hash'=>$token_hash)); self::redirect_review('save_error'); }
        $message='Документ „'.self::document_label($doc->document_type).'“ за пријавата е '.($decision==='approved'?'одобрен.':'потребно е дополнување. Причина: '.$note);
        if($decision==='needs_correction')$message.="\n\nОтворете го порталот за наставници преку безбедниот линк и прикачете ја исправката. Линкот важи 7 дена и може да се искористи еднаш.";
        if($decision==='needs_correction')$message.="\n[ZMP_DOCUMENT_REPAIR:".$doc_id."]";
        $wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>get_current_user_id(),'sender_role'=>'zmp_commission','recipient_id'=>(int)$doc->wp_user_id,'competition_id'=>0,'subject'=>'Документ за дополнување · пријава #'.(int)$doc->entry_id,'message'=>$message,'status'=>'unread','created_at'=>current_time('mysql')));
        if($decision==='needs_correction'){
            $portal=self::teacher_portal_url();$link=$portal.'#zmp-document-token='.$token;
            $mail="Потребна е исправка на документ за пријава во натпреварите.\n\nДокумент: ".self::document_label($doc->document_type)."\nПричина: ".$note."\n\nБезбеден линк за еднократно прикачување (важи 7 дена):\n".$link."\n\nЛинкот не содржи име или податоци за учесникот. Не го препраќајте.";
            $user=get_user_by('id',(int)$doc->wp_user_id);if($user&&is_email($user->user_email))wp_mail($user->user_email,'Потребна е исправка на документ за пријава',$mail);
        }
        if(class_exists('ZMP_Category_Jury')&&method_exists('ZMP_Category_Jury','audit_external'))ZMP_Category_Jury::audit_external((int)$doc->season_id,(int)$doc->category_id,(int)$doc->entry_id,'entry_document_'.$decision,array('document_type'=>$doc->document_type,'document_id'=>$doc_id,'reviewer_id'=>get_current_user_id(),'note'=>$note));
        self::redirect_review('saved');
    }

    public static function render_teacher_repair($entry_id){
        if(!is_user_logged_in())return '';
        global $wpdb;$owner=(int)$wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}zmp_category_entries WHERE id=%d",absint($entry_id)));if(!$owner||$owner!==get_current_user_id())return '';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT d.*,r.status AS review_status,r.reviewer_note FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.entry_id=%d AND r.status='needs_correction'",absint($entry_id)));
        if(!$rows)return '';
        $html='<div class="zmp-doc-repair"><strong>Документи за исправка</strong>';
        foreach($rows as $doc){$target=absint($_GET['zmp_document_repair']??0)===(int)$doc->id;$roster=$doc->document_type==='participant_roster';$types=$roster?'PDF, DOCX, XLSX или CSV до 10 MB':'PDF/JPG/PNG до 15 MB';$accept=$roster?'.pdf,.docx,.xlsx,.csv':'.pdf,.jpg,.jpeg,.png';$html.='<details id="zmp-document-repair-'.(int)$doc->id.'"'.($target?' open':'').'><summary>'.esc_html(self::document_label($doc->document_type)).' — достави исправка</summary><p>'.nl2br(esc_html($doc->reviewer_note)).'</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="zmp_document_repair_action" value="replace"><input type="hidden" name="document_id" value="'.(int)$doc->id.'">'.wp_nonce_field('zmp_document_repair_'.(int)$doc->id,'_wpnonce',true,false).'<label>Исправен документ ('.$types.') <input type="file" name="replacement_document" accept="'.$accept.'" required></label><button class="button button-primary">Прикачи исправка</button></form></details>';}
        return $html.'</div>';
    }

    private static function token_upload_script(){return '<div id="zmp-document-token-box" class="zmp-card" hidden></div><script>(function(){var m=location.hash.match(/(?:^#|[&#])zmp-document-token=([a-f0-9]{64})/);if(!m)return;var token=m[1],box=document.getElementById("zmp-document-token-box");if(!box)return;var d=new FormData();d.append("action","zmp_document_token_info");d.append("token",token);fetch("'.esc_js(admin_url('admin-ajax.php')).'",{method:"POST",body:d,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(x){if(!x.success)throw new Error();box.hidden=false;box.innerHTML="<h3>Исправка на документ</h3><p>Документ: "+x.data.label+". Линкот важи 7 дена и се користи еднаш.</p><form id=\"zmp-document-token-form\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"action\" value=\"zmp_document_token_upload\"><input type=\"hidden\" name=\"token\" value=\""+token+"\"><input type=\"hidden\" name=\"nonce\" value=\""+x.data.nonce+"\"><label>Исправен документ ("+x.data.types+") <input type=\"file\" name=\"replacement_document\" accept=\""+x.data.accept+"\" required></label><button class=\"button button-primary\">Прикачи исправка</button><p role=\"status\"></p></form>";document.getElementById("zmp-document-token-form").addEventListener("submit",function(e){e.preventDefault();var f=e.currentTarget,p=new FormData(f),s=f.querySelector("p[role=status]");s.textContent="Се прикачува...";fetch("'.esc_js(admin_url('admin-ajax.php')).'",{method:"POST",body:p,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(y){s.textContent=y.success?"Документот е примен и чека проверка.":(y.data&&y.data.message?y.data.message:"Прикачувањето не успеа.");if(y.success){history.replaceState(null,"",location.pathname+location.search);f.remove();}}).catch(function(){s.textContent="Мрежна грешка.";});});}).catch(function(){box.hidden=false;box.textContent="Линкот истекол или е веќе искористен. Најавете се во порталот и контактирајте модератор.";});})();</script>';}

    public static function handle_replacement_post(){
        if(($_POST['zmp_document_repair_action']??'')!=='replace')return;
        $doc_id=absint($_POST['document_id']??0);if(!is_user_logged_in()||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce']??'')),'zmp_document_repair_'.$doc_id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));
        global $wpdb;$doc=$wpdb->get_row($wpdb->prepare("SELECT d.*,e.wp_user_id,e.category_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.id=%d AND r.status='needs_correction'",$doc_id));
        if(!$doc||(int)$doc->wp_user_id!==get_current_user_id())wp_die('Документот не е достапен за оваа сметка.','Нема пристап',array('response'=>403));
        $new=$doc->document_type==='participant_roster'?ZMP_Registration_Requirements::store_roster_replacement('replacement_document',(int)$doc->season_id,(int)$doc->category_id):ZMP_Registration_Requirements::store_document_replacement('replacement_document',(int)$doc->season_id,(int)$doc->category_id);
        if(is_wp_error($new))self::redirect_teacher('replacement_failed');
        self::save_replacement($doc,$new,0);
    }

    public static function ajax_token_info(){
        $token=sanitize_text_field(wp_unslash($_POST['token']??''));$row=self::token_row($token);
        if(!$row)wp_send_json_error(array('message'=>'Линкот не важи.'),403);
        $doc=self::document_for_token($row);if(!$doc)wp_send_json_error(array('message'=>'Документот не е достапен.'),404);
        wp_send_json_success(array('label'=>self::document_label($doc->document_type),'nonce'=>wp_create_nonce('zmp_document_token_upload_'.$row->token_hash),'accept'=>$doc->document_type==='participant_roster'?'.pdf,.docx,.xlsx,.csv':'.pdf,.jpg,.jpeg,.png','types'=>$doc->document_type==='participant_roster'?'PDF, DOCX, XLSX или CSV до 10 MB':'PDF/JPG/PNG до 15 MB'));
    }

    public static function ajax_token_upload(){
        $token=sanitize_text_field(wp_unslash($_POST['token']??''));$row=self::token_row($token);if(!$row)wp_send_json_error(array('message'=>'Линкот истекол или веќе е искористен.'),403);
        if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce']??'')),'zmp_document_token_upload_'.$row->token_hash))wp_send_json_error(array('message'=>'Безбедносната проверка не помина.'),403);
        $doc=self::document_for_token($row);if(!$doc)wp_send_json_error(array('message'=>'Документот не е достапен.'),404);
        $new=$doc->document_type==='participant_roster'?ZMP_Registration_Requirements::store_roster_replacement('replacement_document',(int)$doc->season_id,(int)$doc->category_id):ZMP_Registration_Requirements::store_document_replacement('replacement_document',(int)$doc->season_id,(int)$doc->category_id);
        if(is_wp_error($new))wp_send_json_error(array('message'=>'Прикачете исправен PDF/JPG/PNG документ до 15 MB.'),400);
        $saved=self::save_replacement($doc,$new,(int)$row->id,true);
        if(!$saved)wp_send_json_error(array('message'=>'Документот не е зачуван. Контактирајте модератор.'),500);
        wp_send_json_success(array('message'=>'Документот е примен и чека проверка.'));
    }

    private static function token_row($token){
        if(!preg_match('/^[a-f0-9]{64}$/',$token))return null;global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_document_repair_tokens WHERE token_hash=%s AND used_at IS NULL AND expires_at>%s",hash('sha256',$token),gmdate('Y-m-d H:i:s')));
    }
    private static function document_for_token($row){global $wpdb;return $wpdb->get_row($wpdb->prepare("SELECT d.*,e.category_id,e.wp_user_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id JOIN {$wpdb->prefix}zmp_entry_document_reviews r ON r.document_id=d.id WHERE d.id=%d AND d.entry_id=%d AND d.season_id=%d AND r.status='needs_correction'",$row->document_id,$row->entry_id,$row->season_id));}

    private static function save_replacement($doc,$new,$token_id,$ajax=false){
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $history=$wpdb->insert($wpdb->prefix.'zmp_entry_document_revisions',array('season_id'=>(int)$doc->season_id,'entry_id'=>(int)$doc->entry_id,'document_id'=>(int)$doc->id,'document_type'=>(string)$doc->document_type,'file_key'=>$doc->file_key,'drive_file_id'=>$doc->drive_file_id,'storage_backend'=>$doc->storage_backend,'original_name'=>$doc->original_name,'mime_type'=>$doc->mime_type,'file_size'=>(int)$doc->file_size,'sha256'=>$doc->sha256,'uploaded_by'=>(int)$doc->uploaded_by,'created_at'=>$doc->created_at));
        if(!$history){$wpdb->query('ROLLBACK');ZMP_Registration_Requirements::remove_entry_document_files(array($new));if(!$ajax)self::redirect_teacher('save_error');return false;}
        $updated=$wpdb->update($wpdb->prefix.'zmp_entry_documents',array('file_key'=>$new['file_key'],'drive_file_id'=>$new['drive_file_id'],'storage_backend'=>$new['storage_backend'],'original_name'=>$new['original_name'],'mime_type'=>$new['mime_type'],'file_size'=>$new['file_size'],'sha256'=>$new['sha256'],'uploaded_by'=>get_current_user_id()?:absint($doc->wp_user_id??0),'created_at'=>current_time('mysql')),array('id'=>(int)$doc->id,'entry_id'=>(int)$doc->entry_id));
        $roster_updated=true;
        if($doc->document_type==='participant_roster'){$roster_updated=$wpdb->update($wpdb->prefix.'zmp_category_entries',array('roster_filename'=>$new['file_key'],'roster_drive_file_id'=>$new['drive_file_id'],'roster_storage_backend'=>$new['storage_backend'],'roster_original_name'=>$new['original_name'],'roster_sha256'=>$new['sha256']),array('id'=>(int)$doc->entry_id))!==false;}
        if($token_id){$token_used=$wpdb->update($wpdb->prefix.'zmp_document_repair_tokens',array('used_at'=>current_time('mysql')),array('id'=>$token_id,'used_at'=>null));if($token_used!==1){$wpdb->query('ROLLBACK');ZMP_Registration_Requirements::remove_entry_document_files(array($new));if(!$ajax)self::redirect_teacher('save_error');return false;}}
        $review=$updated===false?false:$wpdb->update($wpdb->prefix.'zmp_entry_document_reviews',array('status'=>'pending','reviewer_id'=>0,'reviewer_note'=>'','reviewed_at'=>null,'updated_at'=>current_time('mysql')),array('document_id'=>(int)$doc->id,'status'=>'needs_correction'));
        if($updated===false||!$roster_updated||$review===false){$wpdb->query('ROLLBACK');ZMP_Registration_Requirements::remove_entry_document_files(array($new));if(!$ajax)self::redirect_teacher('save_error');return false;}
        $wpdb->query('COMMIT');
        $wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>get_current_user_id()?:absint($doc->wp_user_id??0),'sender_role'=>'zmp_teacher','recipient_id'=>(int)$doc->wp_user_id,'competition_id'=>0,'subject'=>'Исправениот документ е примен','message'=>'Исправениот документ е примен и чека повторна проверка.','status'=>'unread','created_at'=>current_time('mysql')));
        if(!$ajax)self::redirect_teacher('replacement_uploaded');return true;
    }

    public static function handle_preview(){
        if(!isset($_GET['zmp_entry_document_preview']))return;
        $id=absint($_GET['zmp_entry_document_preview']);if(!is_user_logged_in()||!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'zmp_entry_doc_preview_'.$id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));
        global $wpdb;$doc=$wpdb->get_row($wpdb->prepare("SELECT d.*,e.category_id FROM {$wpdb->prefix}zmp_entry_documents d JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=d.entry_id WHERE d.id=%d",$id));if(!$doc||!self::can_review_entry($doc->season_id,$doc->category_id,$doc->entry_id))wp_die('Немате пристап до документот.','Нема пристап',array('response'=>403));ZMP_Registration_Requirements::stream_entry_document($doc,true);
    }

    private static function teacher_portal_url(){
        $page=get_page_by_path('natprevari');$url=$page?get_permalink($page->ID):home_url('/');return add_query_arg('zmp_portal_tab','nastavnici',$url);
    }
    private static function redirect_review($status){$url=wp_get_referer()?:admin_url();wp_safe_redirect(add_query_arg(array('zmp_portal_tab'=>'komisija','zmp_commission_tab'=>'jury','zmp_jury_status'=>'document_'.$status),$url));exit;}
    private static function redirect_teacher($status){$url=wp_get_referer()?:self::teacher_portal_url();wp_safe_redirect(add_query_arg(array('zmp_portal_tab'=>'nastavnici','zmp_entry_status'=>$status),$url));exit;}
}
