<?php
if (!defined('ABSPATH')) { exit; }

/** Category scoped commission/organizer assignments and secure onboarding. */
class ZMP_Committees_Organizers {
    const NONCE = 'zmp_committees_organizers';
    const ACCEPT_NONCE = 'zmp_accept_committee_invite';

    public static function install() {
        global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate(); $p=$wpdb->prefix;
        dbDelta("CREATE TABLE {$p}zmp_committee_invites (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            invite_name VARCHAR(190) NOT NULL,
            invite_email VARCHAR(190) NOT NULL,
            commission_categories LONGTEXT NOT NULL,
            organizer_categories LONGTEXT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            accepted_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            accepted_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY token_hash (token_hash),
            KEY email_status (invite_email,accepted_at)
        ) $c;");
        dbDelta("CREATE TABLE {$p}zmp_organizer_assignments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            organizer_user_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_by BIGINT(20) UNSIGNED NOT NULL,
            assigned_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category_user (season_id,category_id,organizer_user_id),
            KEY organizer_user (organizer_user_id)
        ) $c;");
        dbDelta("CREATE TABLE {$p}zmp_member_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(40) NOT NULL,
            entity_type VARCHAR(30) NOT NULL,
            entity_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            details LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY season_action (season_id,action,created_at)
        ) $c;");
    }

    public static function init() {
        add_action('admin_post_zmp_assign_competition_members', array(__CLASS__,'handle_assign'));
        add_action('admin_post_nopriv_zmp_accept_committee_invite', array(__CLASS__,'handle_accept'));
        add_action('admin_post_zmp_accept_committee_invite', array(__CLASS__,'handle_accept'));
        add_action('admin_post_zmp_organizer_message', array(__CLASS__,'handle_message'));
        add_action('admin_post_zmp_organizer_mark_read', array(__CLASS__,'handle_mark_read'));
    }

    private static function can_moderate() { return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('zmp_moderator_cap') || current_user_can('view_zmp_moderator_dashboard')); }
    private static function season() { return class_exists('ZMP_Category_Registrations') ? ZMP_Category_Registrations::active_season() : null; }
    private static function season_by_id($season_id) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d",(int)$season_id)); }
    private static function categories($season_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 WHERE c.is_active=1 ORDER BY c.sort_order,c.id",$season_id));
    }
    private static function is_category_member($user_id,$category_id,$season_id) {
        global $wpdb; $p=$wpdb->prefix;
        $role_ok=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}zmp_jury_assignments WHERE season_id=%d AND category_id=%d AND juror_user_id=%d",$season_id,$category_id,$user_id));
        $org_ok=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}zmp_organizer_assignments WHERE season_id=%d AND category_id=%d AND organizer_user_id=%d",$season_id,$category_id,$user_id));
        return $role_ok>0 || $org_ok>0;
    }
    private static function assign_user($user_id,$commission,$organizer,$season_id,$actor) {
        global $wpdb; $p=$wpdb->prefix; $user=get_user_by('id',$user_id); if(!$user||in_array('zmp_president',(array)$user->roles,true))return false;
        $knowledge=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}zmp_categories WHERE category_code='knowledge_test'"));
        $allowed_comm=array(); foreach($commission as $id){$id=absint($id); if($id && $id!==$knowledge && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}zmp_season_categories WHERE season_id=%d AND category_id=%d AND is_enabled=1",$season_id,$id)))$allowed_comm[]=$id;}
        $allowed_org=array(); foreach($organizer as $id){$id=absint($id); if($id && $id!==$knowledge && (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}zmp_season_categories WHERE season_id=%d AND category_id=%d AND is_enabled=1",$season_id,$id)))$allowed_org[]=$id;}
        $needs_comm=(bool)$allowed_comm; $needs_org=(bool)$allowed_org;
        $wpdb->query('START TRANSACTION');
        $now=current_time('mysql');
        foreach(array_unique($allowed_comm) as $category_id){$ok=$wpdb->query($wpdb->prepare("INSERT INTO {$p}zmp_jury_assignments (season_id,category_id,juror_user_id,assigned_by,assigned_at) VALUES (%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE assigned_by=VALUES(assigned_by),assigned_at=VALUES(assigned_at)",$season_id,$category_id,$user_id,$actor,$now));if($ok===false){$wpdb->query('ROLLBACK');return false;}}
        foreach(array_unique($allowed_org) as $category_id){$ok=$wpdb->query($wpdb->prepare("INSERT INTO {$p}zmp_organizer_assignments (season_id,category_id,organizer_user_id,assigned_by,assigned_at) VALUES (%d,%d,%d,%d,%s) ON DUPLICATE KEY UPDATE assigned_by=VALUES(assigned_by),assigned_at=VALUES(assigned_at)",$season_id,$category_id,$user_id,$actor,$now));if($ok===false){$wpdb->query('ROLLBACK');return false;}}
        $wpdb->query('COMMIT');
        if($needs_comm)$user->add_role('zmp_commission');
        if($needs_org)$user->add_role('zmp_organizer');
        return true;
    }

    public static function handle_assign() {
        if(!self::can_moderate())wp_die('Немате дозвола за оваа акција.', 'Нема пристап', array('response'=>403));
        check_admin_referer(self::NONCE);
        global $wpdb;
        $season=self::season(); if(!$season)self::redirect('no_season');
        $name=sanitize_text_field(wp_unslash($_POST['member_name']??'')); $email=sanitize_email(wp_unslash($_POST['member_email']??''));
        $commission=array_map('absint',(array)wp_unslash($_POST['commission_categories']??array()));
        $organizer=array_map('absint',(array)wp_unslash($_POST['organizer_categories']??array()));
        $commission=array_values(array_unique(array_filter($commission))); $organizer=array_values(array_unique(array_filter($organizer)));
        $knowledge=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_categories WHERE category_code=%s",'knowledge_test'));
        $valid=array_map('intval',wp_list_pluck(self::categories((int)$season->id),'id'));
        $commission=array_values(array_intersect($commission,$valid)); $organizer=array_values(array_intersect($organizer,$valid));
        $commission=array_values(array_diff($commission,array($knowledge))); $organizer=array_values(array_diff($organizer,array($knowledge)));
        if(!$name||!is_email($email)||(!$commission&&!$organizer))self::redirect('invalid_member');
        global $wpdb; $existing_id=(int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE LOWER(user_email)=LOWER(%s) LIMIT 1",$email)); $existing=$existing_id?get_user_by('id',$existing_id):false;
        if($existing){
            if(in_array('zmp_president',(array)$existing->roles,true))self::redirect('president_operational_blocked');
            if(!self::assign_user($existing->ID,$commission,$organizer,(int)$season->id,get_current_user_id()))self::redirect('save_error');
            self::audit((int)$season->id,'competition_member_assigned','user',(int)$existing->ID,array('email_hash'=>hash('sha256',strtolower($email)),'commission_categories'=>$commission,'organizer_categories'=>$organizer));
            self::redirect('assigned_existing');
        }
        $token=bin2hex(random_bytes(32)); $expiry=gmdate('Y-m-d H:i:s',time()+7*DAY_IN_SECONDS);
        $inserted=$wpdb->insert($wpdb->prefix.'zmp_committee_invites',array('season_id'=>(int)$season->id,'invite_name'=>$name,'invite_email'=>$email,'commission_categories'=>wp_json_encode($commission),'organizer_categories'=>wp_json_encode($organizer),'token_hash'=>hash('sha256',$token),'expires_at'=>$expiry,'created_by'=>get_current_user_id(),'created_at'=>current_time('mysql')));
        if(!$inserted)self::redirect('save_error');
        $invite_id=(int)$wpdb->insert_id;
        $accept_url=add_query_arg(array('action'=>'zmp_accept_committee_invite','token'=>$token),admin_url('admin-post.php'));
        $body="Здраво {$name},\n\nВе покануваме да пристапите до системот за натпревари на ЗМП. Отворете ја безбедната врска во следните 7 дена и поставете сопствена лозинка:\n{$accept_url}\n\nВрската може да се искористи само еднаш. Ако не ја очекувавте поканата, игнорирајте ја.\n";
        if(!wp_mail($email,'Покана за пристап до системот за натпревари на ЗМП',$body,array('Content-Type: text/plain; charset=UTF-8'))){$wpdb->delete($wpdb->prefix.'zmp_committee_invites',array('id'=>$invite_id),array('%d'));self::audit((int)$season->id,'competition_invite_mail_failed','invite',$invite_id,array('email_hash'=>hash('sha256',strtolower($email))));self::redirect('mail_failed');}
        self::audit((int)$season->id,'competition_member_invited','invite',$invite_id,array('email_hash'=>hash('sha256',strtolower($email)),'commission_categories'=>$commission,'organizer_categories'=>$organizer,'expiry_days'=>7));
        self::redirect('invite_sent');
    }

    public static function handle_accept() {
        $token=sanitize_text_field(wp_unslash($_POST['token']??$_GET['token']??''));
        if(!preg_match('/^[a-f0-9]{64}$/',$token))wp_die('Поканата не е валидна.','Неважечка покана',array('response'=>400));
        if($_SERVER['REQUEST_METHOD']==='GET'){
            global $wpdb; $invite=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_committee_invites WHERE token_hash=%s AND accepted_at IS NULL AND expires_at>=%s",hash('sha256',$token),gmdate('Y-m-d H:i:s')));
            if(!$invite)wp_die('Поканата е неважечка или истечена. Побарајте нова од модераторите.','Поканата не важи',array('response'=>410));
            nocache_headers(); if(!headers_sent())header('Referrer-Policy: no-referrer'); echo '<!doctype html><html lang="mk"><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>Постави лозинка</title><body><main style="max-width:32rem;margin:3rem auto;font:16px sans-serif"><h1>Поставете лозинка</h1><p>Поканата е за '.esc_html($invite->invite_name).'. Изберете сопствена лозинка (најмалку 12 знаци).</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_accept_committee_invite"><input type="hidden" name="token" value="'.esc_attr($token).'">'.wp_nonce_field(self::ACCEPT_NONCE,'_wpnonce',true,false).'<label>Корисничко име<input name="username" required minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]+" autocomplete="username"></label><br><label>Лозинка<input type="password" name="password" required minlength="12" autocomplete="new-password"></label><br><button type="submit">Креирај сметка и активирај пристап</button></form></main></body></html>'; exit;
        }
        check_admin_referer(self::ACCEPT_NONCE);
        $username=sanitize_user(wp_unslash($_POST['username']??''),true); $password=(string)wp_unslash($_POST['password']??'');
        if(strlen($username)<3||strlen($password)<12||strlen($password)>4096)wp_die('Корисничкото име или лозинката не ги исполнуваат условите.','Невалидни податоци',array('response'=>400));
        global $wpdb; $table=$wpdb->prefix.'zmp_committee_invites'; $wpdb->query('START TRANSACTION');
        $invite=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token_hash=%s AND accepted_at IS NULL AND expires_at>=%s FOR UPDATE",hash('sha256',$token),gmdate('Y-m-d H:i:s')));
        if(!$invite){$wpdb->query('ROLLBACK');wp_die('Поканата е веќе искористена или истечена.','Поканата не важи',array('response'=>410));}
        if(username_exists($username)||email_exists($invite->invite_email)){$wpdb->query('ROLLBACK');wp_die('Корисничкото име или е-поштата веќе се користат. Побарајте модератор да ја поврзе постојната сметка.','Постои сметка',array('response'=>409));}
        $user_id=wp_insert_user(array('user_login'=>$username,'user_email'=>$invite->invite_email,'display_name'=>$invite->invite_name,'user_pass'=>$password,'role'=>'subscriber'));
        if(is_wp_error($user_id)){$wpdb->query('ROLLBACK');wp_die('Сметката не можеше да се креира. Контактирајте модератор.','Грешка',array('response'=>500));}
        $season=self::season_by_id((int)$invite->season_id); if(!$season){require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user_id);$wpdb->query('ROLLBACK');wp_die('Натпреварувачката година од поканата не постои. Контактирајте модератор.','Грешка',array('response'=>409));}
        $commission=json_decode($invite->commission_categories,true);$organizer=json_decode($invite->organizer_categories,true);
        if(!self::assign_user($user_id,is_array($commission)?$commission:array(),is_array($organizer)?$organizer:array(),(int)$season->id,(int)$invite->created_by)){$wpdb->query('ROLLBACK');require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user_id);wp_die('Доделувањата не можеа да се зачуваат. Контактирајте модератор.','Грешка',array('response'=>500));}
        self::audit((int)$season->id,'committee_invite_accepted','user',(int)$user_id,array('invite_id'=>(int)$invite->id,'email_hash'=>hash('sha256',strtolower($invite->invite_email))));
        $updated=$wpdb->update($table,array('accepted_user_id'=>(int)$user_id,'accepted_at'=>current_time('mysql')),array('id'=>(int)$invite->id,'accepted_at'=>null),array('%d','%s'),array('%d','%s'));
        if($updated!==1){$wpdb->query('ROLLBACK');require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user_id);wp_die('Поканата не можеше да се активира. Контактирајте модератор.','Грешка',array('response'=>500));}
        $wpdb->query('COMMIT'); wp_set_current_user($user_id); wp_set_auth_cookie($user_id,true,is_ssl()); wp_safe_redirect(home_url('/')); exit;
    }

    public static function handle_message() {
        if(!is_user_logged_in()||(!current_user_can('zmp_organizer_cap')&&!current_user_can('view_zmp_organizer_dashboard')))wp_die('Немате дозвола за дописи на организатори.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE); global $wpdb; $season=self::season(); if(!$season)self::redirect('no_season');
        $to=absint($_POST['recipient_id']??0);$subject=sanitize_text_field(wp_unslash($_POST['subject']??''));$message=sanitize_textarea_field(wp_unslash($_POST['message']??''));
        if(!$to||strlen($subject)<3||strlen($message)<10||strlen($message)>5000)self::redirect('invalid_message');
        $sender=get_current_user_id();$recipient=get_user_by('id',$to);if(!$recipient)self::redirect('invalid_recipient');
        if(!(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_organizer_assignments WHERE season_id=%d AND organizer_user_id=%d",$season->id,$sender)))wp_die('Немате активно доделување како организатор за оваа година.','Нема пристап',array('response'=>403));
        if(!self::recipient_allowed($sender,$recipient,(int)$season->id))wp_die('Примачот не е во дозволениот организациски круг.','Нема пристап',array('response'=>403));
        if(!self::insert_message($sender,$to,$subject,$message))self::redirect('save_error'); self::redirect('message_sent');
    }

    private static function insert_message($sender_id,$recipient_id,$subject,$message){global $wpdb;$ok=$wpdb->insert($wpdb->prefix.'zmp_internal_messages',array('sender_id'=>(int)$sender_id,'sender_role'=>'zmp_organizer','recipient_id'=>(int)$recipient_id,'competition_id'=>0,'subject'=>sanitize_text_field($subject),'message'=>sanitize_textarea_field($message),'status'=>'unread','created_at'=>current_time('mysql')));return $ok?(int)$wpdb->insert_id:false;}

    public static function handle_mark_read(){
        if(!is_user_logged_in()||(!current_user_can('zmp_organizer_cap')&&!current_user_can('view_zmp_organizer_dashboard')))wp_die('Немате пристап.','Нема пристап',array('response'=>403));
        check_admin_referer(self::NONCE);global $wpdb;$id=absint($_POST['message_id']??0);$changed=$wpdb->update($wpdb->prefix.'zmp_internal_messages',array('status'=>'read'),array('id'=>$id,'recipient_id'=>get_current_user_id(),'status'=>'unread'),array('%s'),array('%d','%d','%s'));if($changed===false)self::redirect('save_error');self::redirect('message_read');
    }

    private static function recipient_allowed($sender_id,$recipient,$season_id) {
        if(!$recipient||!$sender_id||!$season_id)return false;
        if(in_array('zmp_moderator',(array)$recipient->roles,true)||in_array('zmp_commission',(array)$recipient->roles,true))return true;
        return in_array('zmp_organizer',(array)$recipient->roles,true)&&self::is_shared_organizer((int)$sender_id,(int)$recipient->ID,(int)$season_id);
    }

    public static function render_moderator_panel() {
        if(!self::can_moderate())return ''; global $wpdb; $season=self::season(); $status=sanitize_key(wp_unslash($_GET['zmp_members_status']??'')); $categories=$season?self::categories((int)$season->id):array();
        $rows=$season?$wpdb->get_results($wpdb->prepare("SELECT j.category_id,j.juror_user_id AS user_id,u.display_name,u.user_email,'commission' AS member_type FROM {$wpdb->prefix}zmp_jury_assignments j JOIN {$wpdb->users} u ON u.ID=j.juror_user_id WHERE j.season_id=%d UNION ALL SELECT o.category_id,o.organizer_user_id,u.display_name,u.user_email,'organizer' AS member_type FROM {$wpdb->prefix}zmp_organizer_assignments o JOIN {$wpdb->users} u ON u.ID=o.organizer_user_id WHERE o.season_id=%d ORDER BY member_type,display_name",$season->id,$season->id)):array();
        $invites=$wpdb->get_results("SELECT id,invite_name,invite_email,commission_categories,organizer_categories,expires_at,accepted_user_id,accepted_at FROM {$wpdb->prefix}zmp_committee_invites ORDER BY created_at DESC LIMIT 30");
        ob_start(); ?><section class="zmp-card zmp-committee-management"><h3>Комисии и организатори</h3><p>Изберете ги активните категории за член на комисија, организатор или за двете улоги. Нов член добива покана со врска за еднократно поставување сопствена лозинка; постојна сметка се поврзува без дуплирање.</p>
        <?php if($status):?><p class="zmp-alert" role="status"><?php echo esc_html(self::status_message($status));?></p><?php endif;?>
        <?php if(!$season):?><p>Нема активна натпреварувачка година.</p><?php else:?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="zmp-form"><input type="hidden" name="action" value="zmp_assign_competition_members"><?php wp_nonce_field(self::NONCE);?><label>Име и презиме<input name="member_name" maxlength="190" required></label><label>Е-пошта<input type="email" name="member_email" maxlength="190" required></label><fieldset><legend>Член на комисија — избери една или повеќе категории</legend><?php foreach($categories as $cat):if((string)$wpdb->get_var($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",$cat->id))==='knowledge_test')continue;?><label><input type="checkbox" name="commission_categories[]" value="<?php echo (int)$cat->id;?>"> <?php echo esc_html($cat->name_mk);?></label><?php endforeach;?></fieldset><fieldset><legend>Организатор — избери една или повеќе категории</legend><?php foreach($categories as $cat):if((string)$wpdb->get_var($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",$cat->id))==='knowledge_test')continue;?><label><input type="checkbox" name="organizer_categories[]" value="<?php echo (int)$cat->id;?>"> <?php echo esc_html($cat->name_mk);?></label><?php endforeach;?></fieldset><button class="button button-primary">Додели / испрати покана</button></form><?php endif;?>
        <h4>Членови во активната година</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Улога</th><th>Категорија</th><th>Член</th><th>Е-пошта</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="4">Нема доделени членови.</td></tr><?php else:foreach($rows as $row):$cat_name=$wpdb->get_var($wpdb->prepare("SELECT name_mk FROM {$wpdb->prefix}zmp_categories WHERE id=%d",$row->category_id));?><tr><td><?php echo $row->member_type==='commission'?'Комисија':'Организатор';?></td><td><?php echo esc_html($cat_name);?></td><td><?php echo esc_html($row->display_name);?></td><td><?php echo esc_html($row->user_email);?></td></tr><?php endforeach;endif;?></tbody></table></div>
        <h4>Покани</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Член</th><th>Е-пошта</th><th>Состојба</th><th>Истекува</th></tr></thead><tbody><?php if(!$invites):?><tr><td colspan="4">Нема покани.</td></tr><?php else:foreach($invites as $i):?><tr><td><?php echo esc_html($i->invite_name);?></td><td><?php echo esc_html($i->invite_email);?></td><td><?php echo $i->accepted_at?'Активирана':(strtotime($i->expires_at.' UTC')<time()?'Истечена':'Чека одговор');?></td><td><?php echo esc_html($i->expires_at);?></td></tr><?php endforeach;endif;?></tbody></table></div></section><?php return ob_get_clean();
    }

    public static function render_organizer_portal() {
        if(!is_user_logged_in()||(!current_user_can('zmp_organizer_cap')&&!current_user_can('view_zmp_organizer_dashboard')&&!current_user_can('manage_options')))return '<section class="zmp-card"><p>Овој портал е достапен само за доделените организатори.</p></section>';
        global $wpdb;$season=self::season();if(!$season)return '<section class="zmp-card"><p>Нема активна натпреварувачка година.</p></section>'.ZMP_Role_Messenger::unavailable_notice('organizer');
        $uid=get_current_user_id();$categories=$wpdb->get_results($wpdb->prepare("SELECT DISTINCT c.id,c.name_mk FROM {$wpdb->prefix}zmp_organizer_assignments a JOIN {$wpdb->prefix}zmp_categories c ON c.id=a.category_id WHERE a.season_id=%d AND a.organizer_user_id=%d ORDER BY c.sort_order",$season->id,$uid));
        if(!$categories)return '<section class="zmp-card"><p>Немате доделена категорија за организација.</p></section>'.ZMP_Role_Messenger::unavailable_notice('organizer');
        $category_ids=array_values(array_filter(array_map(function($category){return absint($category->id);},$categories)));$category_sql=implode(',',$category_ids);
        $events=$wpdb->get_results($wpdb->prepare("SELECT e.phase,e.starts_at,e.ends_at,e.delivery_mode,e.location,e.online_details,'' AS region,c.name_mk AS category_name FROM {$wpdb->prefix}zmp_stage_events e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id WHERE e.season_id=%d AND e.category_id IN ($category_sql) UNION ALL SELECT e.phase,e.starts_at,e.ends_at,e.delivery_mode,e.location,e.online_details,e.region,c.name_mk AS category_name FROM {$wpdb->prefix}zmp_stage_regional_events e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id WHERE e.season_id=%d AND e.category_id IN ($category_sql) ORDER BY starts_at",$season->id,$season->id));
        $entries=$wpdb->get_results($wpdb->prepare("SELECT e.id,e.participant_name,e.grade,e.participant_type,e.school_name,e.municipality,e.region,e.status,e.created_at,c.name_mk AS category_name,g.name_mk AS group_name FROM {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id JOIN {$wpdb->prefix}zmp_category_groups g ON g.id=e.group_id WHERE e.season_id=%d AND e.category_id IN ($category_sql) ORDER BY c.sort_order,g.sort_order,e.school_name,e.participant_name",$season->id));
        $messages=$wpdb->get_results($wpdb->prepare("SELECT m.*,u.display_name AS other_name FROM {$wpdb->prefix}zmp_internal_messages m LEFT JOIN {$wpdb->users} u ON u.ID=CASE WHEN m.sender_id=%d THEN m.recipient_id ELSE m.sender_id END WHERE m.sender_id=%d OR m.recipient_id=%d ORDER BY (m.status='unread') DESC,m.created_at DESC LIMIT 60",$uid,$uid,$uid));
        $allowed_ids=array();foreach(array('zmp_moderator','zmp_commission') as $role){foreach(get_users(array('role'=>$role,'fields'=>array('ID','display_name','user_email'))) as $r){if((int)$r->ID!==$uid)$allowed_ids[(int)$r->ID]=$r;}}
        foreach(get_users(array('role'=>'zmp_organizer','fields'=>array('ID','display_name','user_email'))) as $r){if((int)$r->ID!==$uid&&self::is_shared_organizer($uid,(int)$r->ID,(int)$season->id))$allowed_ids[(int)$r->ID]=$r;}
        $allowed=array_values($allowed_ids);$unread=0;foreach((array)$messages as $message){if((int)$message->recipient_id===$uid&&$message->status==='unread')$unread++;}
        ob_start();?><section class="zmp-card zmp-organizer-portal"><h3>Организаторски портал</h3><p>Година: <?php echo esc_html($season->title);?>. Ваши категории: <?php echo esc_html(implode(', ',wp_list_pluck($categories,'name_mk')));?>. Достапни се само пријавите и распоредите од доделените категории.</p><?php if(!empty($_GET['zmp_members_status'])):?><p class="zmp-alert" role="status"><?php echo esc_html(self::status_message(sanitize_key(wp_unslash($_GET['zmp_members_status']))));?></p><?php endif;?></section><?php $home=ob_get_clean();
        ob_start();?><section class="zmp-card"><h3>Распоред на моите категории</h3><?php if(!$events):?><p>Модераторите сè уште немаат внесено распоред.</p><?php else:?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија</th><th>Натпреварувачко ниво</th><th>Регион</th><th>Почеток</th><th>Крај</th><th>Начин и локација</th></tr></thead><tbody><?php foreach($events as $event):?><tr><td><?php echo esc_html($event->category_name);?></td><td><?php echo esc_html(self::phase_label($event->phase));?></td><td><?php echo esc_html($event->region?ZMP_Regions::label($event->region):'Сите региони');?></td><td><?php echo esc_html($event->starts_at?:'Не е внесен');?></td><td><?php echo esc_html($event->ends_at?:'—');?></td><td><?php echo esc_html($event->delivery_mode==='online'?'Онлајн':($event->delivery_mode==='hybrid'?'Комбинирано':'Физички'));?><?php if($event->location):?><br><?php echo esc_html($event->location);?><?php endif;?><?php if($event->online_details):?><br><?php echo nl2br(esc_html($event->online_details));?><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php $schedule=ob_get_clean();
        ob_start();?><section class="zmp-card"><h3>Учесници од доделените категории (<?php echo count((array)$entries);?>)</h3><?php if(!$entries):?><p>Сè уште нема пријавени учесници во вашите категории.</p><?php else:?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Категорија / група</th><th>Учесник</th><th>Училиште</th><th>Регион / општина</th><th>Одделение/година</th><th>Статус</th><th>Пријавено</th></tr></thead><tbody><?php foreach($entries as $entry):?><tr><td><?php echo esc_html($entry->category_name.' · '.$entry->group_name);?></td><td><?php echo esc_html($entry->participant_name);?></td><td><?php echo esc_html($entry->school_name);?></td><td><?php echo esc_html(ZMP_Regions::label($entry->region).' · '.$entry->municipality);?></td><td><?php echo esc_html($entry->grade);?></td><td><?php echo esc_html($entry->status);?></td><td><?php echo esc_html($entry->created_at);?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php $participants=ob_get_clean();
        ob_start();?><section class="zmp-card"><h3>Комуникација и примени дописи<?php if($unread):?> <span class="zmp-workspace__badge"><?php echo (int)$unread;?></span><?php endif;?></h3><?php if(!$messages):?><p>Сè уште нема дописи.</p><?php else:?><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Датум</th><th>Учесник во разговорот</th><th>Наслов</th><th>Порака</th><th>Статус</th></tr></thead><tbody><?php foreach($messages as $message):$incoming=(int)$message->recipient_id===$uid;?><tr><td><?php echo esc_html($message->created_at);?></td><td><?php echo esc_html($message->other_name?:'Корисник');?> · <?php echo $incoming?'Од':'До';?></td><td><?php echo esc_html($message->subject);?></td><td><?php echo nl2br(esc_html($message->message));?></td><td><?php echo $incoming&&$message->status==='unread'?'Непрочитана':'Прочитана / испратена';?><?php if($incoming&&$message->status==='unread'):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="zmp_organizer_mark_read"><input type="hidden" name="message_id" value="<?php echo (int)$message->id;?>"><?php wp_nonce_field(self::NONCE);?><button class="button">Означи како прочитана</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php $communication=ob_get_clean();
        ob_start();?><section class="zmp-card"><h3>Испрати официјално барање</h3><p>Барањето се зачувува како интерен допис и е достапно само за избраниот примач.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="zmp-form"><input type="hidden" name="action" value="zmp_organizer_message"><?php wp_nonce_field(self::NONCE);?><label>До<select name="recipient_id" required><option value="">Изберете модератор, комисија или заеднички организатор</option><?php foreach($allowed as $recipient):?><option value="<?php echo (int)$recipient->ID;?>"><?php echo esc_html($recipient->display_name.' — '.$recipient->user_email);?></option><?php endforeach;?></select></label><label>Наслов<input name="subject" maxlength="190" required></label><label>Барање и образложение<textarea name="message" minlength="10" maxlength="5000" required></textarea></label><button class="button button-primary">Испрати барање</button></form></section><?php $requests=ob_get_clean();
        $organizer_tab=isset($_GET['zmp_organizer_tab'])&&is_scalar($_GET['zmp_organizer_tab'])?sanitize_key(wp_unslash($_GET['zmp_organizer_tab'])):'home';
        $messenger=ZMP_Role_Messenger::render('organizer',$organizer_tab==='messenger');
        $messenger_unread=ZMP_Role_Messenger::unread_count('organizer');
        $tabs=array('home'=>'Мој натпревар','schedule'=>'Распоред','participants'=>'Учесници','communication'=>'Комуникација'.($unread?' ('.$unread.')':''),'requests'=>'Барања');
        $panels=array('home'=>$home,'schedule'=>$schedule,'participants'=>$participants,'communication'=>$communication,'requests'=>$requests);
        $messenger_notice='';
        if(ZMP_Role_Messenger::can_access_current_role_room('organizer')){$tabs=array_slice($tabs,0,1,true)+array('messenger'=>array('label'=>'Месинџер','count'=>$messenger_unread))+array_slice($tabs,1,null,true);$panels['messenger']=$messenger;}
        else{$messenger_notice=ZMP_Role_Messenger::unavailable_notice('organizer');}
        return ZMP_Shortcodes::render_workspace('organizer','zmp_organizer_tab',$tabs,$panels,'home',$messenger_notice);
    }
    private static function phase_label($phase){$labels=array('school'=>'Училишно натпреварувачко ниво','municipal'=>'Општинско натпреварувачко ниво','regional'=>'Регионално натпреварувачко ниво','state'=>'Државно натпреварувачко ниво');return isset($labels[$phase])?$labels[$phase]:sanitize_text_field($phase);}
    private static function is_shared_organizer($a,$b,$season){global $wpdb;return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_organizer_assignments x JOIN {$wpdb->prefix}zmp_organizer_assignments y ON x.season_id=y.season_id AND x.category_id=y.category_id WHERE x.season_id=%d AND x.organizer_user_id=%d AND y.organizer_user_id=%d",$season,$a,$b))>0;}
    private static function audit($season,$action,$type,$id,$details){global $wpdb;return $wpdb->insert($wpdb->prefix.'zmp_member_audit',array('season_id'=>(int)$season,'actor_user_id'=>get_current_user_id(),'action'=>sanitize_key($action),'entity_type'=>sanitize_key($type),'entity_id'=>(int)$id,'details'=>wp_json_encode($details),'created_at'=>current_time('mysql')));}
    private static function redirect($status){$url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_members_status',sanitize_key($status),$url));exit;}
    private static function status_message($status){$m=array('no_season'=>'Нема активна натпреварувачка година.','invalid_member'=>'Внесете валидно име, е-пошта и најмалку една категорија.','president_operational_blocked'=>'Претседателската сметка не може да се назначи во оперативна улога.','assigned_existing'=>'Постојната сметка е доделена на избраните категории.','invite_sent'=>'Поканата е испратена. Линкот важи 7 дена и може да се искористи еднаш.','mail_failed'=>'Поканата не е испратена; нема зачувана активна покана. Проверете ја поставката за пошта.','save_error'=>'Промените не се зачувани. Проверете ја евиденцијата и обидете се повторно.','message_sent'=>'Дописот е испратен.','message_read'=>'Дописот е означен како прочитан.','invalid_message'=>'Проверете го насловот и пораката.','invalid_recipient'=>'Избраниот примач не е достапен.');return $m[$status]??'Операцијата е обработена.';}
}
