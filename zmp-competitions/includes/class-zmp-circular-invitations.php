<?php
if (!defined('ABSPATH')) { exit; }

/** Annual moderator workflow for approved, school-addressed competition invitations. */
class ZMP_Circular_Invitations {
    const NONCE = 'zmp_circular_invitation';
    const TABLE = 'zmp_circular_invitations';
    const RECIPIENTS = 'zmp_circular_recipients';

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}" . self::TABLE . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            academic_year VARCHAR(9) NOT NULL,
            title VARCHAR(190) NOT NULL,
            body_template LONGTEXT NOT NULL,
            registration_deadline VARCHAR(40) NOT NULL DEFAULT '',
            requirements TEXT NOT NULL,
            category_ids_json LONGTEXT NOT NULL,
            group_ids_json LONGTEXT NOT NULL,
            school_contact_ids_json LONGTEXT NOT NULL,
            details_snapshot LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            board_approval_reference VARCHAR(190) NOT NULL DEFAULT '',
            board_approval_date DATE DEFAULT NULL,
            approved_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            approved_at DATETIME DEFAULT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            last_sent_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY season_status (season_id,status),
            KEY academic_year (academic_year)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}" . self::RECIPIENTS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            invitation_id BIGINT(20) UNSIGNED NOT NULL,
            school_contact_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            school_name VARCHAR(255) NOT NULL,
            recipient_email VARCHAR(190) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            error_code VARCHAR(60) NOT NULL DEFAULT '',
            public_token_hash CHAR(64) NOT NULL DEFAULT '',
            sent_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY invitation_email (invitation_id,recipient_email),
            KEY invitation_status (invitation_id,status)
        ) $c;");
    }

    public static function init() {
        add_action('admin_post_zmp_save_circular_invitation', array(__CLASS__, 'handle_save'));
        add_action('admin_post_zmp_approve_circular_invitation', array(__CLASS__, 'handle_approve'));
        add_action('admin_post_zmp_send_circular_invitation', array(__CLASS__, 'handle_send'));
        add_action('admin_post_zmp_circular_pdf', array(__CLASS__, 'handle_private_print'));
        add_action('init', array(__CLASS__, 'handle_public_print'), 1);
    }

    private static function can_moderate() {
        return is_user_logged_in() && (current_user_can('zmp_moderator_cap') || current_user_can('view_zmp_moderator_dashboard') || current_user_can('manage_options'));
    }

    public static function render_moderator_panel() {
        if (!self::can_moderate()) { return ''; }
        global $wpdb;
        $season_id = absint($_GET['zmp_circular_season'] ?? 0);
        $seasons = $wpdb->get_results("SELECT id,academic_year,title FROM {$wpdb->prefix}zmp_seasons WHERE status<>'completed' ORDER BY academic_year DESC");
        if (!$season_id && $seasons) { $season_id = (int)$seasons[0]->id; }
        $season = null;
        foreach ((array)$seasons as $candidate) { if ((int)$candidate->id === $season_id) { $season = $candidate; break; } }
        $editing_id = absint($_GET['zmp_circular_edit'] ?? 0);
        $editing = $editing_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}" . self::TABLE . " WHERE id=%d AND status='draft'", $editing_id)) : null;
        if ($editing) { $season_id = (int)$editing->season_id; foreach ((array)$seasons as $candidate) { if ((int)$candidate->id === $season_id) { $season=$candidate; break; } } }
        $categories = $season ? $wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk,f.fee_amount FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 LEFT JOIN {$wpdb->prefix}zmp_category_fees f ON f.season_id=sc.season_id AND f.category_id=c.id WHERE c.is_active=1 ORDER BY c.sort_order,c.id", $season_id)) : array();
        $contacts = $wpdb->get_results("SELECT id,school_name,municipality,region,email FROM {$wpdb->prefix}zmp_school_contacts WHERE is_active=1 AND is_official=1 ORDER BY region,municipality,school_name,email");
        $rows = $wpdb->get_results("SELECT i.*,u.display_name FROM {$wpdb->prefix}" . self::TABLE . " i LEFT JOIN {$wpdb->users} u ON u.ID=i.created_by ORDER BY i.created_at DESC LIMIT 30");
        $selected_categories = $editing ? self::json_ids($editing->category_ids_json) : array();
        $selected_groups = $editing ? self::decode_map($editing->group_ids_json) : array();
        $selected_contacts = $editing ? self::json_ids($editing->school_contact_ids_json) : array();
        $status = sanitize_key(wp_unslash($_GET['zmp_circular_status'] ?? ''));
        ob_start(); ?>
        <section class="zmp-card zmp-circular-invitations">
            <h3>Циркуларни покани и отворање на годината</h3>
            <p>Поканите се праќаат само до избраните потврдени училишни адреси, секоја адреса посебно. Прво зачувајте нацрт, внесете податок за одлуката на Управниот одбор, прегледајте ја поканата и потврдете го испраќањето.</p>
            <?php if ($status): ?><p class="zmp-alert" role="status"><?php echo esc_html(self::status_message($status)); ?></p><?php endif; ?>
            <?php if (!$season): ?><p>Нема подготвена натпреварувачка година. Создајте ја во поставките за категории и години.</p><?php else: ?>
            <form method="get" class="zmp-form"><label>Натпреварувачка година <select name="zmp_circular_season" onchange="this.form.submit()"><?php foreach ($seasons as $s): ?><option value="<?php echo (int)$s->id; ?>" <?php selected($season_id,(int)$s->id); ?>><?php echo esc_html($s->academic_year . ' — ' . $s->title); ?></option><?php endforeach; ?></select></label><?php foreach ((array)$_GET as $key=>$value) { if ($key==='zmp_circular_season'||!is_scalar($value)) continue; ?><input type="hidden" name="<?php echo esc_attr(sanitize_key($key)); ?>" value="<?php echo esc_attr(sanitize_text_field(wp_unslash((string)$value))); ?>"><?php } ?></form>
            <?php if (!$categories): ?><p>За избраната година нема активни категории.</p><?php else: ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form zmp-circular-form">
                <?php wp_nonce_field(self::NONCE); ?><input type="hidden" name="action" value="zmp_save_circular_invitation"><input type="hidden" name="invitation_id" value="<?php echo $editing ? (int)$editing->id : 0; ?>"><input type="hidden" name="season_id" value="<?php echo (int)$season_id; ?>">
                <h4><?php echo $editing ? 'Уреди нацрт покана' : 'Подготви нова покана'; ?></h4>
                <label>Наслов на поканата *<input name="title" maxlength="190" required value="<?php echo esc_attr($editing->title ?? ('Покана за учество на натпреварите ' . $season->academic_year)); ?>"></label>
                <label>Рок за пријавување<input type="date" name="registration_deadline" value="<?php echo esc_attr($editing->registration_deadline ?? ''); ?>"></label>
                <label>Услови и дополнителни информации<textarea name="requirements" rows="4" maxlength="5000"><?php echo esc_textarea($editing->requirements ?? ''); ?></textarea></label>
                <label>Текст на поканата *<textarea name="body_template" rows="7" maxlength="12000" required><?php echo esc_textarea($editing->body_template ?? "Почитувани колеги,\n\nВе покануваме да учествувате на натпреварите во учебната година {{ГОДИНА}}.\n\n{{УСЛОВИ}}\n\n{{КАТЕГОРИИ}}\n\n{{КОТИЗАЦИИ}}\n\n{{РОК}}\n\nСо почит,\nЗдружение на музички педагози"); ?></textarea></label>
                <p>Достапни ознаки во текстот: <code>{{ГОДИНА}}</code>, <code>{{РОК}}</code>, <code>{{УСЛОВИ}}</code>, <code>{{КАТЕГОРИИ}}</code>, <code>{{КОТИЗАЦИИ}}</code>.</p>
                <fieldset><legend>Активни категории и натпреварувачки групи</legend>
                <?php foreach ($categories as $category): $groups = $wpdb->get_results($wpdb->prepare("SELECT id,name_mk FROM {$wpdb->prefix}zmp_category_groups WHERE category_id=%d AND is_active=1 ORDER BY sort_order,id", (int)$category->id)); ?>
                    <details class="zmp-circular-category"><summary><label><input type="checkbox" name="category_ids[]" value="<?php echo (int)$category->id; ?>" <?php checked(in_array((int)$category->id,$selected_categories,true)); ?>> <?php echo esc_html($category->name_mk.' — '.($category->fee_amount!==null?number_format_i18n((float)$category->fee_amount,2).' ден.':'котизацијата не е поставена')); ?></label></summary>
                    <?php if (!$groups): ?><p>Нема активни групи.</p><?php else: ?><div class="zmp-circular-groups"><?php foreach($groups as $group): ?><label><input type="checkbox" name="group_ids[<?php echo (int)$category->id; ?>][]" value="<?php echo (int)$group->id; ?>" <?php checked(in_array((int)$group->id,(array)($selected_groups[(int)$category->id] ?? array()),true)); ?>> <?php echo esc_html($group->name_mk); ?></label><?php endforeach; ?></div><?php endif; ?></details>
                <?php endforeach; ?></fieldset>
                <fieldset><legend>Избери училишта за поканата</legend>
                <?php if (!$contacts): ?><p>Нема службени потврдени училишни адреси. Додајте ги во панелот „Училишни контакти“.</p><?php else: ?><div class="zmp-circular-contacts"><?php foreach($contacts as $contact): ?><label><input type="checkbox" name="school_contact_ids[]" value="<?php echo (int)$contact->id; ?>" <?php checked(in_array((int)$contact->id,$selected_contacts,true)); ?>> <?php echo esc_html($contact->school_name . ' · ' . $contact->municipality . ' · ' . $contact->email); ?></label><?php endforeach; ?></div><?php endif; ?></fieldset>
                <p><button class="button button-primary">Зачувај нацрт и прегледај</button></p>
            </form><?php endif; endif; ?>
            <h4>Покани и евиденција</h4><div class="zmp-table-responsive"><table class="zmp-table"><thead><tr><th>Година</th><th>Наслов</th><th>Статус</th><th>Одобрение</th><th>Адреси</th><th>Дејства</th></tr></thead><tbody>
            <?php if (!$rows): ?><tr><td colspan="6">Нема зачувани покани.</td></tr><?php else: foreach ($rows as $row): $recipient_rows=$wpdb->get_results($wpdb->prepare("SELECT school_name,recipient_email,status,error_code,sent_at FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE invitation_id=%d ORDER BY school_name,recipient_email",(int)$row->id)); $recipient_count=count($recipient_rows); ?>
            <tr><td><?php echo esc_html($row->academic_year); ?></td><td><?php echo esc_html($row->title); ?></td><td><?php echo esc_html(self::status_label($row->status)); ?></td><td><?php echo $row->board_approval_reference ? esc_html($row->board_approval_reference.' / '.$row->board_approval_date) : 'Не е внесено'; ?></td><td><?php echo (int)$recipient_count; ?><?php if($recipient_rows): ?><details><summary>Прикажи резултати по адреса</summary><ul><?php foreach($recipient_rows as $rr): ?><li><?php echo esc_html($rr->school_name.' · '.$rr->recipient_email.' — '.self::status_label($rr->status).($rr->error_code?' ('.$rr->error_code.')':'')); ?></li><?php endforeach; ?></ul></details><?php endif; ?></td><td>
                <?php if ($row->status==='draft'): ?><a class="button" href="<?php echo esc_url(add_query_arg(array('zmp_circular_edit'=>(int)$row->id,'zmp_circular_season'=>(int)$row->season_id))); ?>">Уреди нацрт</a> <a class="button" target="_blank" rel="noopener" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_circular_pdf&invitation_id='.(int)$row->id),self::NONCE)); ?>">Преглед / печати PDF</a>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><input type="hidden" name="action" value="zmp_approve_circular_invitation"><input type="hidden" name="invitation_id" value="<?php echo (int)$row->id; ?>"><?php wp_nonce_field(self::NONCE); ?><label>Датум на одлуката <input type="date" name="approval_date" required></label><label>Записник / референтен број <input name="approval_reference" maxlength="190" required></label><label><input type="checkbox" name="board_approval_confirmed" value="1" required> Потврдувам дека Управниот одбор го одобрил почетокот на подготовките.</label><button class="button">Евидентирај одобрение</button></form>
                <?php elseif(in_array($row->status,array('approved','partial','failed'),true)): ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_circular_pdf&invitation_id='.(int)$row->id),self::NONCE)); ?>">Преглед / печати PDF</a>
                <?php $pending=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE invitation_id=%d AND status<>'sent'",(int)$row->id)); if($row->status==='approved'){$pending=count(self::selected_contacts_for($row));} if($pending>0): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zmp-form"><input type="hidden" name="action" value="zmp_send_circular_invitation"><input type="hidden" name="invitation_id" value="<?php echo (int)$row->id; ?>"><input type="hidden" name="expected_count" value="<?php echo (int)$pending; ?>"><?php wp_nonce_field(self::NONCE); ?><label><input type="checkbox" name="confirm_send" value="1" required> Потврдувам испраќање до <?php echo (int)$pending; ?> адреси.</label><button class="button button-primary">Испрати покани</button></form><?php endif; ?>
                <?php else: ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_circular_pdf&invitation_id='.(int)$row->id),self::NONCE)); ?>">Преглед / печати PDF</a><?php endif; ?>
            </td></tr><?php endforeach; endif; ?></tbody></table></div>
        </section><?php return ob_get_clean();
    }

    public static function handle_save() {
        self::require_moderator(); check_admin_referer(self::NONCE); global $wpdb;
        $id=absint($_POST['invitation_id']??0); $season_id=absint($_POST['season_id']??0);
        if($id){$existing=$wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id));if(!$existing||$existing->status!=='draft'){self::redirect('locked');}}
        $season=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_seasons WHERE id=%d AND status<>'completed'",$season_id));
        $title=sanitize_text_field(wp_unslash($_POST['title']??'')); $body=sanitize_textarea_field(wp_unslash($_POST['body_template']??'')); $requirements=sanitize_textarea_field(wp_unslash($_POST['requirements']??'')); $deadline=sanitize_text_field(wp_unslash($_POST['registration_deadline']??''));
        if(!$season||mb_strlen($title)<3||mb_strlen($title)>190||!$body||mb_strlen($body)>12000||mb_strlen($requirements)>5000||($deadline!==''&&!self::valid_date($deadline))){self::redirect('invalid');}
        $category_ids=self::posted_ids('category_ids'); $group_map=array();
        $enabled=array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT category_id FROM {$wpdb->prefix}zmp_season_categories WHERE season_id=%d AND is_enabled=1",$season_id)));
        if(!$category_ids||array_diff($category_ids,$enabled)){self::redirect('invalid_categories');}
        $fee_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_category_fees WHERE season_id=%d AND category_id IN (".implode(',',array_fill(0,count($category_ids),'%d')).")",$season_id,...$category_ids));if($fee_count!==count($category_ids)){self::redirect('fee_missing');}
        $posted_groups=$_POST['group_ids']??array(); if(!is_array($posted_groups)){self::redirect('invalid_groups');}
        foreach($category_ids as $cat_id){$ids=array_map('absint',(array)wp_unslash($posted_groups[$cat_id]??array()));$ids=array_values(array_unique(array_filter($ids)));$available=array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_category_groups WHERE category_id=%d AND is_active=1",$cat_id)));if(!$ids||array_diff($ids,$available)){self::redirect('invalid_groups');}$group_map[$cat_id]=$ids;}
        $contact_ids=self::posted_ids('school_contact_ids');if(!self::valid_contact_selection($contact_ids)){self::redirect('no_recipients');}$contacts=self::contacts_by_ids($contact_ids);if(!$contacts){self::redirect('no_recipients');}
        $snapshot=self::build_snapshot($season,$category_ids,$group_map,$deadline,$requirements);
        $data=array('season_id'=>$season_id,'academic_year'=>$season->academic_year,'title'=>$title,'body_template'=>$body,'registration_deadline'=>$deadline,'requirements'=>$requirements,'category_ids_json'=>wp_json_encode($category_ids),'group_ids_json'=>wp_json_encode($group_map),'school_contact_ids_json'=>wp_json_encode($contact_ids),'details_snapshot'=>wp_json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'updated_at'=>current_time('mysql'));
        if($id){$ok=$wpdb->update($wpdb->prefix.self::TABLE,$data,array('id'=>$id,'status'=>'draft'));}
        else{$data['status']='draft';$data['created_by']=get_current_user_id();$data['created_at']=current_time('mysql');$ok=$wpdb->insert($wpdb->prefix.self::TABLE,$data);$id=(int)$wpdb->insert_id;}
        if($ok===false||!$id){self::redirect('save_error');} self::redirect('saved');
    }

    public static function handle_approve() {
        self::require_moderator();check_admin_referer(self::NONCE);global $wpdb;$id=absint($_POST['invitation_id']??0);$date=sanitize_text_field(wp_unslash($_POST['approval_date']??''));$ref=sanitize_text_field(wp_unslash($_POST['approval_reference']??''));
        if(empty($_POST['board_approval_confirmed'])||!self::valid_date($date)||mb_strlen($ref)<3||mb_strlen($ref)>190){self::redirect('approval_required');}
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d AND status='draft'",$id));if(!$row||!self::valid_contact_selection(self::json_ids($row->school_contact_ids_json))||!self::selected_contacts_for($row)||!self::snapshot_valid($row)){self::redirect('invalid');}
        $wpdb->query('START TRANSACTION');$updated=$wpdb->update($wpdb->prefix.self::TABLE,array('status'=>'approved','board_approval_date'=>$date,'board_approval_reference'=>$ref,'approved_by'=>get_current_user_id(),'approved_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')),array('id'=>$id,'status'=>'draft'));
        if($updated!==1){$wpdb->query('ROLLBACK');self::redirect('save_error');}
        foreach(self::selected_contacts_for($row) as $contact){$wpdb->insert($wpdb->prefix.self::RECIPIENTS,array('invitation_id'=>$id,'school_contact_id'=>(int)$contact->id,'school_name'=>$contact->school_name,'recipient_email'=>$contact->email,'status'=>'pending'));}
        if($wpdb->last_error){$wpdb->query('ROLLBACK');self::redirect('save_error');}$wpdb->query('COMMIT');self::redirect('approved');
    }

    public static function handle_send() {
        self::require_moderator();check_admin_referer(self::NONCE);global $wpdb;$id=absint($_POST['invitation_id']??0);$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",$id));
        if(!$row||!in_array($row->status,array('approved','partial','failed'),true)||!$row->board_approval_reference||!$row->board_approval_date||empty($_POST['confirm_send'])){self::redirect('approval_required');}
        $eligible=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE invitation_id=%d AND status<>'sent' ORDER BY id",$id));if(!$eligible){self::redirect('no_recipients');}
        $expected=absint($_POST['expected_count']??0);if(!$eligible||count($eligible)!==$expected){self::redirect('preview_changed');}
        $updated=$wpdb->update($wpdb->prefix.self::TABLE,array('status'=>'sending','updated_at'=>current_time('mysql')),array('id'=>$id,'status'=>$row->status));if($updated!==1){self::redirect('already_sending');}
        if($wpdb->last_error){$wpdb->update($wpdb->prefix.self::TABLE,array('status'=>'failed'),array('id'=>$id));self::redirect('send_log_error');}
        $content=self::compose_content($row);$subject='Покана: '.$row->title;
        $pending=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE invitation_id=%d AND status<>'sent' ORDER BY id",$id));
        foreach($pending as $recipient){$valid_email=is_email($recipient->recipient_email);$token=bin2hex(random_bytes(32));$wpdb->update($wpdb->prefix.self::RECIPIENTS,array('public_token_hash'=>hash('sha256',$token)),array('id'=>(int)$recipient->id));$pdf_url=add_query_arg(array('zmp_circular_pdf'=>$id,'zmp_circular_recipient'=>(int)$recipient->id,'zmp_circular_token'=>$token),home_url('/'));$html='<div style="font-family:Arial,sans-serif;line-height:1.6"><h2>'.esc_html($row->title).'</h2>'.wpautop(esc_html($content)).'<p><a href="'.esc_url($pdf_url).'">Прегледајте ја поканата и зачувајте ја како PDF</a></p><p>Здружение на музички педагози</p></div>';$sent=$valid_email?wp_mail($recipient->recipient_email,$subject,$html,array('Content-Type: text/html; charset=UTF-8')):false;$wpdb->update($wpdb->prefix.self::RECIPIENTS,array('status'=>$sent?'sent':'failed','error_code'=>$sent?'':($valid_email?'wp_mail_failed':'invalid_email'),'sent_at'=>$sent?current_time('mysql'):null),array('id'=>(int)$recipient->id));}
        $counts=$wpdb->get_row($wpdb->prepare("SELECT SUM(status='sent') AS sent_count,SUM(status='failed') AS failed_count,SUM(status='pending') AS pending_count FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE invitation_id=%d",$id));$final=((int)$counts->sent_count>0&&(int)$counts->failed_count===0&&(int)$counts->pending_count===0)?'sent':(((int)$counts->sent_count>0)?'partial':'failed');
        $wpdb->update($wpdb->prefix.self::TABLE,array('status'=>$final,'last_sent_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')),array('id'=>$id));self::redirect($final==='sent'?'sent':($final==='partial'?'partial':'send_failed'));
    }

    public static function handle_private_print() {
        self::require_moderator();check_admin_referer(self::NONCE);$row=self::get_campaign(absint($_GET['invitation_id']??0));if(!$row)wp_die('Поканата не е пронајдена.','',array('response'=>404));self::render_print_page($row,false);
    }

    public static function handle_public_print() {
        if(!isset($_GET['zmp_circular_pdf'],$_GET['zmp_circular_recipient'],$_GET['zmp_circular_token']))return;global $wpdb;$id=absint($_GET['zmp_circular_pdf']);$recipient_id=absint($_GET['zmp_circular_recipient']);$token=sanitize_text_field(wp_unslash($_GET['zmp_circular_token']));$row=self::get_campaign($id);$recipient=$wpdb->get_row($wpdb->prepare("SELECT public_token_hash,status FROM {$wpdb->prefix}".self::RECIPIENTS." WHERE id=%d AND invitation_id=%d",$recipient_id,$id));if(!$row||!$recipient||$recipient->status!=='sent'||!$token||!hash_equals((string)$recipient->public_token_hash,hash('sha256',$token))){status_header(404);exit; } self::render_print_page($row,true);
    }

    private static function render_print_page($row,$public) {
        $snapshot=json_decode($row->details_snapshot,true);if(!is_array($snapshot))$snapshot=array();$content=self::compose_content($row);nocache_headers();header('Content-Type: text/html; charset=UTF-8');
        ?><!doctype html><html lang="mk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html($row->title); ?></title><style>body{font:16px/1.6 Arial,sans-serif;color:#172033;max-width:850px;margin:32px auto;padding:0 24px}.actions{display:flex;gap:12px;margin-bottom:24px}.actions button{padding:10px 18px;background:#1d4ed8;color:white;border:0;border-radius:6px;font-size:16px}h1{font-size:25px}pre{font:inherit;white-space:pre-wrap}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccd3df;padding:8px;text-align:left}@media print{body{max-width:none;margin:0;padding:0}.actions{display:none}}</style></head><body><div class="actions"><button onclick="window.print()">Печати / зачувај како PDF</button><button onclick="window.close()">Затвори</button></div><h1><?php echo esc_html($row->title); ?></h1><p><strong>Учебна година:</strong> <?php echo esc_html($row->academic_year); ?></p><pre><?php echo esc_html($content); ?></pre><h2>Категории и групи</h2><ul><?php foreach((array)($snapshot['categories']??array()) as $cat): ?><li><strong><?php echo esc_html($cat['name']); ?></strong>: <?php echo esc_html(implode(', ',(array)$cat['groups'])); ?></li><?php endforeach; ?></ul><?php if(!empty($snapshot['fees'])): ?><h2>Котизации</h2><ul><?php foreach($snapshot['fees'] as $fee): ?><li><?php echo esc_html($fee['category'].': '.$fee['amount'].' ден.'); ?><?php if($fee['instructions']): ?> — <?php echo esc_html($fee['instructions']); ?><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?><p>Здружение на музички педагози</p></body></html><?php exit;
    }

    private static function compose_content($row) {
        $snapshot=json_decode($row->details_snapshot,true);if(!is_array($snapshot))$snapshot=array();$cats=array();$fees=array();foreach((array)($snapshot['categories']??array()) as $cat){$cats[]=$cat['name'].(!empty($cat['groups'])?' — '.implode(', ',$cat['groups']):'');}foreach((array)($snapshot['fees']??array()) as $fee)$fees[]=$fee['category'].': '.$fee['amount'].' ден.'.($fee['instructions']?' ('.$fee['instructions'].')':'');
        $replace=array('{{ГОДИНА}}'=>$row->academic_year,'{{РОК}}'=>$row->registration_deadline?:'Рокот ќе биде објавен дополнително.','{{УСЛОВИ}}'=>$row->requirements?:'Условите за пријавување се достапни преку организаторите.','{{КАТЕГОРИИ}}'=>implode("\n",$cats),'{{КОТИЗАЦИИ}}'=>implode("\n",$fees));return strtr($row->body_template,$replace);
    }

    private static function build_snapshot($season,$category_ids,$group_map,$deadline,$requirements) {
        global $wpdb;$snapshot=array('year'=>$season->academic_year,'categories'=>array(),'fees'=>array(),'registration_deadline'=>$deadline,'requirements'=>$requirements);
        foreach($category_ids as $cat_id){$cat=$wpdb->get_row($wpdb->prepare("SELECT name_mk FROM {$wpdb->prefix}zmp_categories WHERE id=%d",$cat_id));$groups=array();foreach($group_map[$cat_id] as $group_id){$g=$wpdb->get_var($wpdb->prepare("SELECT name_mk FROM {$wpdb->prefix}zmp_category_groups WHERE id=%d AND category_id=%d",$group_id,$cat_id));if($g)$groups[]=$g;}$snapshot['categories'][]=array('id'=>$cat_id,'name'=>$cat?$cat->name_mk:'','groups'=>$groups);$fee=$wpdb->get_row($wpdb->prepare("SELECT fee_amount,payment_instructions FROM {$wpdb->prefix}zmp_category_fees WHERE season_id=%d AND category_id=%d",(int)$season->id,$cat_id));if($fee)$snapshot['fees'][]=array('category'=>$cat?$cat->name_mk:'','amount'=>number_format_i18n((float)$fee->fee_amount,2),'instructions'=>$fee->payment_instructions);}
        return $snapshot;
    }

    private static function snapshot_valid($row) { $snapshot=json_decode($row->details_snapshot,true);return is_array($snapshot)&&!empty($snapshot['categories'])&&is_array($snapshot['fees'])&&count($snapshot['fees'])===count($snapshot['categories']); }
    private static function valid_date($value) { if(!is_string($value)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$value))return false;$date=DateTime::createFromFormat('!Y-m-d',$value,wp_timezone());$errors=DateTime::getLastErrors();return $date instanceof DateTime&&$date->format('Y-m-d')===$value&&(!$errors||(!$errors['warning_count']&&!$errors['error_count'])); }
    private static function valid_contact_selection($ids) { global $wpdb;$ids=array_values(array_unique(array_filter(array_map('absint',(array)$ids))));if(!$ids)return false;$in=implode(',',array_fill(0,count($ids),'%d'));$emails=$wpdb->get_col($wpdb->prepare("SELECT email FROM {$wpdb->prefix}zmp_school_contacts WHERE is_active=1 AND is_official=1 AND id IN ($in)",...$ids));if(count($emails)!==count($ids))return false;foreach($emails as $email)if(!is_email($email))return false;return true; }
    private static function get_campaign($id) { global $wpdb;return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}".self::TABLE." WHERE id=%d",absint($id))); }
    private static function selected_contacts_for($row) { return self::contacts_by_ids(self::json_ids($row->school_contact_ids_json)); }
    private static function contacts_by_ids($ids) { global $wpdb;$ids=array_values(array_unique(array_filter(array_map('absint',(array)$ids))));if(!$ids)return array();$in=implode(',',array_fill(0,count($ids),'%d'));$rows=$wpdb->get_results($wpdb->prepare("SELECT id,school_name,municipality,region,email FROM {$wpdb->prefix}zmp_school_contacts WHERE is_active=1 AND is_official=1 AND id IN ($in) ORDER BY school_name,email",...$ids));$unique=array();foreach((array)$rows as $row){$key=strtolower($row->email);if(!isset($unique[$key]))$unique[$key]=$row;}return array_values($unique); }
    private static function posted_ids($key) { $raw=wp_unslash($_POST[$key]??array());if(!is_array($raw))return array();return array_values(array_unique(array_filter(array_map('absint',$raw)))); }
    private static function json_ids($json) { $ids=json_decode((string)$json,true);return is_array($ids)?array_values(array_unique(array_filter(array_map('absint',$ids)))):array(); }
    private static function decode_map($json) { $map=json_decode((string)$json,true);if(!is_array($map))return array();foreach($map as $key=>$ids)$map[(int)$key]=self::json_ids(wp_json_encode($ids));return $map; }
    private static function require_moderator() { if(!self::can_moderate())wp_die('Немате дозвола за оваа постапка.','Нема пристап',array('response'=>403)); }
    private static function redirect($status) { $url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_circular_status',sanitize_key($status),$url));exit; }
    private static function status_label($status) { return array('draft'=>'Нацрт','approved'=>'Одобрено од УО','sending'=>'Се испраќа','sent'=>'Испратено','partial'=>'Делумно испратено','failed'=>'Испраќањето не успеа','pending'=>'Чека испраќање')[sanitize_key($status)]??$status; }
    private static function status_message($status) { return array('saved'=>'Нацртот е зачуван. Проверете ги категориите, адресите и прегледот пред да побарате одобрение.','approved'=>'Одлуката на Управниот одбор е евидентирана. Прегледајте ја поканата и потврдете пред испраќање.','sent'=>'Поканата е испратена до сите избрани адреси.','partial'=>'Дел од пораките се испратени. Неуспешните адреси се евидентирани и може повторно да се испратат.','send_failed'=>'Испраќањето не успеа. Проверете ја поштата на сајтот и повторете ги неуспешните адреси.','invalid'=>'Проверете ги задолжителните полиња и зачуваните податоци.','fee_missing'=>'Поставете котизација за секоја избрана категорија во годишните поставки пред да ја зачувате поканата.','invalid_categories'=>'Изберете активна категорија за годината.','invalid_groups'=>'За секоја категорија изберете една или повеќе активни групи.','no_recipients'=>'Изберете барем една потврдена службена адреса на училиште.','approval_required'=>'Потребни се потврда за одлуката на Управниот одбор, датум и референтен податок.','locked'=>'Одобрените или испратените покани не може да се менуваат.','save_error'=>'Поканата не е зачувана. Проверете ја базата и обидете се повторно.','preview_changed'=>'Списокот на адреси е сменет. Освежете ја страницата и прегледајте ги примачите повторно.','already_sending'=>'Испраќањето веќе е во тек или статусот е сменет. Освежете ја страницата.','send_log_error'=>'Не може безбедно да се зачува списокот за испраќање; ниедна порака не е испратена.','invalid_group'=>'Податоците за категориите или групите не се исправни.');return $messages[$status]??''; }
}
