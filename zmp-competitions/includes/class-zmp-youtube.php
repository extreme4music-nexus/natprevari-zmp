<?php
if (!defined('ABSPATH')) { exit; }

/** Least-purpose OAuth and resumable YouTube uploads for online regional entries. */
class ZMP_YouTube {
    const OPTION = 'zmp_youtube_settings';
    const NONCE = 'zmp_youtube_admin';
    const MAX_VIDEO_BYTES = 524288000;
    const CHUNK_BYTES = 8388608;
    const SCOPE_UPLOAD = 'https://www.googleapis.com/auth/youtube.upload';
    const SCOPE_READ = 'https://www.googleapis.com/auth/youtube.readonly';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_post_zmp_youtube_save', array(__CLASS__, 'save_settings'));
        add_action('admin_post_zmp_youtube_connect', array(__CLASS__, 'connect'));
        add_action('admin_post_zmp_youtube_callback', array(__CLASS__, 'callback'));
        add_action('admin_post_zmp_youtube_disconnect', array(__CLASS__, 'disconnect'));
        add_action('admin_post_zmp_youtube_upload', array(__CLASS__, 'upload_entry_video'));
        add_action('admin_post_zmp_youtube_check', array(__CLASS__, 'check_entry_processing'));
        add_action('admin_post_zmp_youtube_finalize', array(__CLASS__, 'finalize_entry'));
        add_action('admin_post_zmp_youtube_assign_regional_juror', array(__CLASS__, 'handle_regional_jury_assignment'));
        add_action('admin_post_zmp_youtube_unassign_regional_juror', array(__CLASS__, 'handle_regional_jury_assignment'));
        add_action('admin_post_zmp_youtube_clear_uncertain_upload', array(__CLASS__, 'clear_uncertain_upload'));
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'zmp_youtube_media';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL DEFAULT 'regional',
            slot_key VARCHAR(30) NOT NULL,
            video_id VARCHAR(32) NOT NULL DEFAULT '',
            upload_session_enc LONGTEXT NOT NULL,
            upload_offset BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            file_sha256 CHAR(64) NOT NULL DEFAULT '',
            file_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            mime_type VARCHAR(100) NOT NULL DEFAULT '',
            privacy_status VARCHAR(20) NOT NULL DEFAULT '',
            processing_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            uploaded_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY entry_slot (entry_id,phase,slot_key),
            KEY season_category (season_id,category_id,phase),
            KEY status (processing_status)
        ) $charset;");
        $regional_jury_table = $wpdb->prefix . 'zmp_youtube_regional_jury';
        dbDelta("CREATE TABLE $regional_jury_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            region VARCHAR(30) NOT NULL,
            juror_user_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            assigned_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY season_category_region_juror (season_id,category_id,region,juror_user_id),
            KEY juror_scope (season_id,juror_user_id),
            KEY category_region (season_id,category_id,region)
        ) $charset;");
        // Copy existing regional video-jury assignments into the general jury scope.
        $legacy_table = $wpdb->prefix . 'zmp_youtube_regional_jury';
        $generic_table = $wpdb->prefix . 'zmp_jury_regional_assignments';
        $wpdb->last_error = '';
        if (get_option('zmp_regional_jury_migration_49_2') === 'complete') { return; }
        $legacy_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($legacy_table)));
        $migration_ok = ($wpdb->last_error === '' && $legacy_exists === $legacy_table);
        if ($migration_ok) {
            $wpdb->last_error = '';
            $copied = $wpdb->query("INSERT IGNORE INTO $generic_table (season_id,category_id,region,juror_user_id,assigned_by,assigned_at)
                SELECT season_id,category_id,region,juror_user_id,assigned_by,assigned_at FROM $legacy_table");
            $migration_ok = ($copied !== false && $wpdb->last_error === '');
        }
        update_option('zmp_regional_jury_migration_49_2', $migration_ok ? 'complete' : 'failed', false);
    }

    public static function register_menu() {
        add_submenu_page('zmp-competitions', 'YouTube видео архива', 'YouTube видео', 'manage_options', 'zmp-youtube', array(__CLASS__, 'render_admin_page'));
    }

    public static function render_admin_page() {
        self::require_admin();
        $settings = self::settings();
        $connected = self::is_connected();
        $status = sanitize_key(wp_unslash($_GET['zmp_youtube_status'] ?? ''));
        $messages = array(
            'saved'=>'YouTube OAuth поставките се зачувани.', 'connected'=>'YouTube каналот е поврзан.',
            'disconnected'=>'YouTube каналот е исклучен од плагинот.', 'invalid'=>'Проверете го Client ID и OAuth Client secret.',
            'secret_required'=>'Внесете OAuth Client secret.', 'encryption_unavailable'=>'Серверот нема OpenSSL за безбедно чување на токените.',
            'oauth_state_invalid'=>'Поврзувањето истече или не одговара на оваа сесија.', 'oauth_denied'=>'Google не го одобри поврзувањето.',
            'oauth_exchange_failed'=>'Google не ја потврди OAuth сесијата. Проверете Client ID, secret и Redirect URI.',
            'refresh_token_missing'=>'Google не врати траен токен. Отповикајте ја старата дозвола и поврзете повторно.',
            'channel_unavailable'=>'Не можевме да го потврдиме YouTube каналот за овластената сметка.',
        );
        echo '<div class="wrap"><h1>YouTube — видео за онлајн регионални натпревари</h1>';
        if(isset($messages[$status])) echo '<div class="notice notice-'.($status==='connected'||$status==='saved'||$status==='disconnected'?'success':'error').'"><p>'.esc_html($messages[$status]).'</p></div>';
        echo '<p>Само овластениот администратор го поврзува YouTube каналот на здружението. OAuth токените се шифрираат со WordPress salts и не се прикажуваат или логираат. Поставувањето бара upload и read-only дозволи: read-only се користи само за проверка на обработката и Unlisted статусот. Ако смените OAuth Client secret, ќе треба повторно да го поврзете каналот.</p>';
        echo '<p><strong>Важна граница:</strong> „Unlisted“ видеото не е приватно: секој што ќе ја добие врската може да го гледа и препрати. Во насловот и описот не се внесуваат имиња, училишта или други лични податоци.</p>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_save">';
        wp_nonce_field('zmp_youtube_save');
        echo '<table class="form-table"><tr><th><label for="zmp-youtube-client-id">OAuth Client ID</label></th><td><input id="zmp-youtube-client-id" class="regular-text" name="client_id" value="'.esc_attr((string)($settings['client_id']??'')).'" autocomplete="off" required></td></tr>';
        echo '<tr><th><label for="zmp-youtube-client-secret">OAuth Client secret</label></th><td><input id="zmp-youtube-client-secret" class="regular-text" type="password" name="client_secret" value="" autocomplete="new-password"><p class="description">Оставете празно за да се зачува постојната шифрирана вредност.</p></td></tr></table>';
        submit_button('Зачувај OAuth поставки'); echo '</form><p>Redirect URI за Google Cloud: <code>'.esc_html(self::redirect_uri()).'</code></p>';
        if($connected) {
            echo '<p><strong>Статус:</strong> Поврзан канал · ID <code>'.esc_html($settings['channel_id']).'</code></p>';
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_disconnect">'; wp_nonce_field('zmp_youtube_disconnect'); submit_button('Исклучи YouTube канал','secondary'); echo '</form>';
        } elseif(!empty($settings['client_id'])&&!empty($settings['client_secret_enc'])) {
            echo '<p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_youtube_connect'),'zmp_youtube_connect')).'">Поврзи го официјалниот канал</a></p>';
        } else echo '<p>Зачувајте OAuth Client ID и secret, па поврзете ја сметката што е сопственик на каналот.</p>';
        echo '<h2>Правила за видеа</h2><ul><li>Се прикачуваат само за регионално натпреварувачко ниво со начин „Онлајн“.</li><li>Вокална интерпретација: едно видео; хор/оркестар: задолжително и изборно; бенд: две изборни.</li><li>Државните натпревари се одржуваат со физичко присуство и нема нов видео upload.</li><li>Пријавата останува нацрт додека сите потребни видеа не се прикачат, YouTube не потврди Unlisted и обработката не заврши.</li></ul></div>';
    }

    public static function save_settings() {
        self::require_admin(); check_admin_referer('zmp_youtube_save');
        $settings=self::settings(); $id=sanitize_text_field(wp_unslash($_POST['client_id']??'')); $secret=trim((string)wp_unslash($_POST['client_secret']??''));
        if($id===''||strlen($id)>255||strpos($id,'.apps.googleusercontent.com')===false) self::redirect_admin('invalid');
        if($secret!=='') {$settings['client_secret_enc']=self::encrypt($secret); if(!$settings['client_secret_enc'])self::redirect_admin('encryption_unavailable'); unset($settings['refresh_token_enc'],$settings['channel_id'],$settings['channel_title']);}
        if(empty($settings['client_secret_enc']))self::redirect_admin('secret_required');
        if(!empty($settings['client_id'])&&!hash_equals((string)$settings['client_id'],$id)) { unset($settings['refresh_token_enc'],$settings['channel_id']); }
        $settings['client_id']=$id; update_option(self::OPTION,$settings,false); self::redirect_admin('saved');
    }

    public static function connect() {
        self::require_admin(); check_admin_referer('zmp_youtube_connect'); $s=self::settings();
        if(empty($s['client_id'])||empty($s['client_secret_enc']))self::redirect_admin('secret_required');
        $state=bin2hex(random_bytes(32)); set_transient('zmp_youtube_oauth_'.hash('sha256',$state),array('uid'=>get_current_user_id(),'nonce'=>wp_create_nonce('zmp_youtube_user_'.get_current_user_id())),10*MINUTE_IN_SECONDS);
        $query=array('client_id'=>$s['client_id'],'redirect_uri'=>self::redirect_uri(),'response_type'=>'code','scope'=>self::SCOPE_UPLOAD.' '.self::SCOPE_READ,'access_type'=>'offline','prompt'=>'consent','state'=>$state);
        wp_redirect('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($query,'','&',PHP_QUERY_RFC3986)); exit;
    }

    public static function callback() {
        self::require_admin(); $state=isset($_GET['state'])&&is_scalar($_GET['state'])?sanitize_text_field(wp_unslash($_GET['state'])):'';
        $key='zmp_youtube_oauth_'.hash('sha256',$state); $ctx=$state!==''?get_transient($key):false; delete_transient($key);
        $uid=is_array($ctx)?absint($ctx['uid']??0):0;
        if(!$uid||$uid!==get_current_user_id()||!wp_verify_nonce((string)($ctx['nonce']??''),'zmp_youtube_user_'.$uid))self::redirect_admin('oauth_state_invalid');
        if(!empty($_GET['error']))self::redirect_admin('oauth_denied');
        $code=isset($_GET['code'])&&is_scalar($_GET['code'])?sanitize_text_field(wp_unslash($_GET['code'])):''; if($code==='')self::redirect_admin('oauth_exchange_failed');
        $s=self::settings(); $secret=self::decrypt($s['client_secret_enc']??'');
        $res=wp_remote_post('https://oauth2.googleapis.com/token',array('timeout'=>30,'body'=>array('code'=>$code,'client_id'=>$s['client_id'],'client_secret'=>$secret,'redirect_uri'=>self::redirect_uri(),'grant_type'=>'authorization_code')));
        if(is_wp_error($res)||wp_remote_retrieve_response_code($res)!==200)self::redirect_admin('oauth_exchange_failed');
        $tokens=json_decode(wp_remote_retrieve_body($res),true); if(empty($tokens['access_token']))self::redirect_admin('oauth_exchange_failed');
        // Never pair a new access token/channel with a previously stored account token.
        if(empty($tokens['refresh_token']))self::redirect_admin('refresh_token_missing');
        $refresh_token_enc=self::encrypt((string)$tokens['refresh_token']); if(!$refresh_token_enc)self::redirect_admin('encryption_unavailable');
        $s['refresh_token_enc']=$refresh_token_enc;
        $channel=self::api_get('https://www.googleapis.com/youtube/v3/channels?part=id%2Csnippet&mine=true',(string)$tokens['access_token']);
        if(is_wp_error($channel)||empty($channel['items'][0]['id']))self::redirect_admin('channel_unavailable');
        $s['channel_id']=sanitize_text_field($channel['items'][0]['id']); $s['channel_title']=sanitize_text_field($channel['items'][0]['snippet']['title']??'YouTube канал'); update_option(self::OPTION,$s,false);
        self::redirect_admin('connected');
    }

    public static function disconnect() {
        self::require_admin(); check_admin_referer('zmp_youtube_disconnect'); $s=self::settings(); $token=self::decrypt($s['refresh_token_enc']??'');
        if($token) wp_remote_post('https://oauth2.googleapis.com/revoke',array('timeout'=>10,'body'=>array('token'=>$token))); delete_transient('zmp_youtube_access_token');
        unset($s['refresh_token_enc'],$s['channel_id'],$s['channel_title']); update_option(self::OPTION,$s,false); self::redirect_admin('disconnected');
    }

    /** Resolve the configured delivery mode for one region; hybrid without a regional override is not safe to infer. */
    public static function regional_delivery_mode($season_id,$category_id,$region) {
        global $wpdb;
        $region=sanitize_key((string)$region);
        if(!$region||!class_exists('ZMP_Regions')||!ZMP_Regions::valid_region($region))return 'unconfigured';
        $category_code=(string)$wpdb->get_var($wpdb->prepare("SELECT category_code FROM {$wpdb->prefix}zmp_categories WHERE id=%d",absint($category_id)));
        if((string)$wpdb->last_error!==''||$category_code==='')return 'unconfigured';
        $event=$wpdb->get_row($wpdb->prepare("SELECT delivery_mode FROM {$wpdb->prefix}zmp_stage_regional_events WHERE season_id=%d AND category_id=%d AND phase='regional' AND region=%s",absint($season_id),absint($category_id),$region));
        if((string)$wpdb->last_error!=='')return 'unconfigured';
        if($event)return in_array($event->delivery_mode,array('online','physical'),true)?$event->delivery_mode:'unconfigured';
        // Keep the knowledge-test schedule independent; music categories require an explicit per-region event.
        if($category_code!=='knowledge_test')return 'unconfigured';
        $mode=$wpdb->get_var($wpdb->prepare("SELECT st.delivery_mode FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND st.phase='regional' AND st.is_enabled=1 LIMIT 1",absint($season_id),absint($category_id)));
        if((string)$wpdb->last_error!==''||$mode===null||$mode==='')return 'unconfigured';
        return in_array($mode,array('online','physical'),true)?$mode:'unconfigured';
    }
    /** Only online regional entries require all video slots. */
    public static function requires_online_regional($season_id,$category_id,$region) {
        return self::regional_delivery_mode($season_id,$category_id,$region)==='online';
    }

    /** The jury's broad category assignment is not enough to view another region's videos. */
    /** Regional video access delegates to the shared, fail-closed regional jury assignment. */
    public static function is_regional_video_juror_for($season,$category,$region,$user) {
        return class_exists('ZMP_Category_Jury')
            && ZMP_Category_Jury::is_regional_juror_for($season,$category,$region,$user);
    }

    /** Assign or remove one commission member's regional video access. */
    public static function handle_regional_jury_assignment() {
        if(!is_user_logged_in()||(!current_user_can('manage_options')&&!current_user_can('zmp_moderator_cap')))wp_die('Недоволни привилегии.','Нема пристап',array('response'=>403));
        $action=sanitize_key(wp_unslash($_POST['action']??''));
        $season_id=absint($_POST['season_id']??0);$category_id=absint($_POST['category_id']??0);$juror_id=absint($_POST['juror_user_id']??0);
        $region=sanitize_key(wp_unslash($_POST['region']??''));
        if(!in_array($action,array('zmp_youtube_assign_regional_juror','zmp_youtube_unassign_regional_juror'),true)||!class_exists('ZMP_Regions')||!ZMP_Regions::valid_region($region))wp_die('Невалидно регионално жири.','Неважечки податоци',array('response'=>400));
        check_admin_referer('zmp_regional_jury_'.$season_id.'_'.$category_id.'_'.$region);
        global $wpdb;$season=ZMP_Category_Registrations::active_season();
        $category=$wpdb->get_row($wpdb->prepare("SELECT c.id,c.category_code FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 WHERE c.id=%d AND c.is_active=1",$season_id,$category_id));
        if($wpdb->last_error!=='')wp_die('Регионалното назначување не може безбедно да се провери.','Грешка',array('response'=>500));
        if(!$season||$season_id!==(int)$season->id||!$category||$category->category_code==='knowledge_test')wp_die('Сезонската категорија не е достапна.','Неважечки податоци',array('response'=>409));
                if($wpdb->last_error!=='')wp_die('Регионалното назначување не може безбедно да се провери.','Грешка',array('response'=>500));
        if(get_option('zmp_regional_jury_migration_49_2')!=='complete')wp_die('Миграцијата на регионалното жири не е завршена.','Грешка',array('response'=>500));
        $table=$wpdb->prefix.'zmp_jury_regional_assignments';
        $wpdb->query('START TRANSACTION');
        if($action==='zmp_youtube_assign_regional_juror') {
            $user=get_user_by('id',$juror_id);
            if(!$user||!in_array('zmp_commission',(array)$user->roles,true))wp_die('Изберете регистриран член на комисија.','Неважечко жири',array('response'=>409));
            $saved=$wpdb->query($wpdb->prepare("INSERT INTO $table (season_id,category_id,region,juror_user_id,assigned_by,assigned_at) VALUES (%d,%d,%s,%d,%d,%s) ON DUPLICATE KEY UPDATE assigned_by=VALUES(assigned_by),assigned_at=VALUES(assigned_at)",$season_id,$category_id,$region,$juror_id,get_current_user_id(),current_time('mysql')));
        } else {
            $saved=$wpdb->delete($table,array('season_id'=>$season_id,'category_id'=>$category_id,'region'=>$region,'juror_user_id'=>$juror_id),array('%d','%d','%s','%d'));
        }
        if($saved===false){$wpdb->query('ROLLBACK');wp_die('Регионалниот пристап не можеше да се зачува.','Грешка',array('response'=>500));}
        $audited=$wpdb->insert($wpdb->prefix.'zmp_operations_audit',array(
            'season_id'=>$season_id,'category_id'=>$category_id,'phase'=>'regional','actor_user_id'=>get_current_user_id(),
            'action'=>$action==='zmp_youtube_assign_regional_juror'?'regional_juror_assigned':'regional_juror_unassigned',
            'object_type'=>'regional_jury','object_id'=>$juror_id,
            'details'=>wp_json_encode(array('region'=>$region,'category_id'=>$category_id)),
            'created_at'=>current_time('mysql')
        ));
        if(!$audited){$wpdb->query('ROLLBACK');wp_die('Регионалниот пристап не можеше да се евидентира. Промената е поништена.','Грешка',array('response'=>500));}
        $wpdb->query('COMMIT');
        $redirect=wp_get_referer()?:home_url('/');
        wp_safe_redirect(add_query_arg('zmp_youtube_jury_status',$action==='zmp_youtube_assign_regional_juror'?'assigned':'unassigned',$redirect));exit;
    }

    /** Release an ambiguous upload slot only after a moderator manually checks the channel for an orphan video. */
    public static function clear_uncertain_upload() {
        if(!is_user_logged_in()||(!current_user_can('manage_options')&&!current_user_can('zmp_moderator_cap')))wp_die('Недоволни привилегии.','Нема пристап',array('response'=>403));
        $media_id=absint($_POST['media_id']??0);check_admin_referer('zmp_youtube_clear_uncertain_'.$media_id);
        if(empty($_POST['confirm_no_video']))wp_die('Потврдете дека рачно го проверивте каналот и нема orphan видео.','Потребна е потврда',array('response'=>400));
        global $wpdb;$media_table=$wpdb->prefix.'zmp_youtube_media';
        $row=$wpdb->get_row($wpdb->prepare("SELECT m.id,m.season_id,m.category_id,m.entry_id,m.slot_key,e.region,e.status FROM $media_table m JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=m.entry_id WHERE m.id=%d AND m.processing_status='session_unknown' AND m.video_id=''",$media_id));
        if(!$row||$row->status!=='draft')wp_die('Неизвесниот upload не е достапен за разрешување.','Неважечка состојба',array('response'=>409));
        $audit_table=$wpdb->prefix.'zmp_operations_audit';$details=wp_json_encode(array('media_id'=>(int)$row->id,'entry_id'=>(int)$row->entry_id,'region'=>sanitize_key($row->region),'slot_key'=>sanitize_key($row->slot_key),'confirmed_no_video'=>true));
        $wpdb->query('START TRANSACTION');
        $deleted=$wpdb->delete($media_table,array('id'=>(int)$row->id,'processing_status'=>'session_unknown','video_id'=>''),array('%d','%s','%s'));
        $audited=$deleted?$wpdb->insert($audit_table,array('season_id'=>(int)$row->season_id,'category_id'=>(int)$row->category_id,'phase'=>'regional','actor_user_id'=>get_current_user_id(),'action'=>'youtube_uncertain_upload_cleared','object_type'=>'youtube_media','object_id'=>(int)$row->id,'details'=>$details,'created_at'=>current_time('mysql'))):false;
        if(!$deleted||!$audited){$wpdb->query('ROLLBACK');wp_die('Неизвесниот upload не е исчистен; записот останува безбеден.','Грешка',array('response'=>500));}
        $wpdb->query('COMMIT');$redirect=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_youtube_jury_status','upload_cleared',$redirect));exit;
    }

    /** Moderator panel for exact-region commission assignments across performance categories. */
    public static function render_regional_jury_access_panel() {
        if(!is_user_logged_in()||(!current_user_can('manage_options')&&!current_user_can('zmp_moderator_cap')))return '';
        global $wpdb;$season=ZMP_Category_Registrations::active_season();
        if(!$season)return '<section class="zmp-card"><h3>Регионални назначувања на комисиите</h3><p>Нема активна натпреварувачка година.</p></section>';
        $categories=$wpdb->get_results($wpdb->prepare("SELECT c.id,c.name_mk,c.category_code FROM {$wpdb->prefix}zmp_categories c JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1 WHERE c.is_active=1 AND c.category_code<>'knowledge_test' ORDER BY c.sort_order,c.id",$season->id));
        if(!$categories)return '<section class="zmp-card"><h3>Регионални назначувања на комисиите</h3><p>Нема активни комисиски категории за оваа натпреварувачка година.</p></section>';
        $regions=class_exists('ZMP_Regions')?ZMP_Regions::regions():array();$status=sanitize_key(wp_unslash($_GET['zmp_youtube_jury_status']??''));
        $commission_members=get_users(array('role'=>'zmp_commission','fields'=>array('ID','display_name','user_email'),'orderby'=>'display_name','order'=>'ASC'));
        $html='<section class="zmp-card"><h3>Регионални назначувања на комисиите</h3><p>Регионалното назначување е посебно од националното: член може да има пристап само до избраните регионални категории и региони. Државното жири се уредува одделно во назначувањата на комисијата.</p>';
        if(get_option('zmp_regional_jury_migration_49_2')!=='complete')$html.='<p class="zmp-alert" role="alert">Регионалните оценки се заклучени додека миграцијата на постојните назначувања не заврши.</p>';
        if($status==='assigned')$html.='<p class="zmp-alert zmp-alert-success" role="status">Регионалното назначување е доделено.</p>';
        elseif($status==='unassigned')$html.='<p class="zmp-alert zmp-alert-success" role="status">Регионалното назначување е отстрането.</p>';
        elseif($status==='upload_cleared')$html.='<p class="zmp-alert zmp-alert-success" role="status">Неизвесниот upload е евидентиран и ослободен по рачна проверка.</p>';
        $uncertain=$wpdb->get_results("SELECT m.id,m.entry_id,m.season_id,m.category_id,m.slot_key,e.region,e.participant_name,e.school_name,c.name_mk AS category_name,s.academic_year,m.updated_at FROM {$wpdb->prefix}zmp_youtube_media m JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=m.entry_id JOIN {$wpdb->prefix}zmp_categories c ON c.id=m.category_id JOIN {$wpdb->prefix}zmp_seasons s ON s.id=m.season_id WHERE m.processing_status='session_unknown' AND m.video_id='' AND e.status='draft' ORDER BY m.updated_at");
        $html.='<details class="zmp-card"><summary><strong>Неизвесни upload сесии</strong> ('.count((array)$uncertain).')</summary><p>Сесијата може да завршила на YouTube, но одговорот да не стигнал до сајтот. Пред да ја ослободите, рачно проверете го YouTube Studio за upload без поврзан запис. Системот намерно не започнува нов upload автоматски.</p>';
        if(!$uncertain)$html.='<p>Нема upload сесии што чекаат проверка.</p>';
        foreach((array)$uncertain as $pending) {
            $html.='<article class="zmp-card"><strong>'.esc_html($pending->category_name).' · '.esc_html($pending->participant_name).'</strong><p>'.esc_html($pending->school_name).' · '.esc_html(ZMP_Regions::label($pending->region)).' · '.esc_html($pending->academic_year).' · '.esc_html($pending->slot_key).' · '.esc_html($pending->updated_at).'</p>';
            $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_clear_uncertain_upload"><input type="hidden" name="media_id" value="'.(int)$pending->id.'">'.wp_nonce_field('zmp_youtube_clear_uncertain_'.(int)$pending->id,'_wpnonce',true,false).'<label><input type="checkbox" name="confirm_no_video" value="1" required> Рачно го проверив YouTube Studio и потврдувам дека не постои создадено видео за оваа upload сесија.</label> <button class="button">Ослободи за нов upload</button></form></article>';
        }
        $html.='</details>';

        foreach((array)$categories as $category) {
            $html.='<details class="zmp-card"><summary><strong>'.esc_html($category->name_mk).'</strong></summary>';
            foreach($regions as $region_key=>$region) {
                $assigned=$wpdb->get_results($wpdb->prepare("SELECT u.ID,u.display_name FROM {$wpdb->prefix}zmp_jury_regional_assignments r JOIN {$wpdb->users} u ON u.ID=r.juror_user_id WHERE r.season_id=%d AND r.category_id=%d AND r.region=%s ORDER BY u.display_name",$season->id,$category->id,$region_key));
                $html.='<div class="zmp-youtube-region-assignment"><h4>'.esc_html($region['label']).'</h4><ul>';
                foreach((array)$assigned as $member) {
                    $html.='<li>'.esc_html($member->display_name).' <form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline"><input type="hidden" name="action" value="zmp_youtube_unassign_regional_juror"><input type="hidden" name="season_id" value="'.(int)$season->id.'"><input type="hidden" name="category_id" value="'.(int)$category->id.'"><input type="hidden" name="region" value="'.esc_attr($region_key).'"><input type="hidden" name="juror_user_id" value="'.(int)$member->ID.'">';
                    $html.=wp_nonce_field('zmp_regional_jury_'.$season->id.'_'.$category->id.'_'.$region_key,'_wpnonce',true,false).'<button class="button">Отстрани</button></form></li>';
                }
                if(!$assigned)$html.='<li>Нема доделени членови.</li>';
                $html.='</ul><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_assign_regional_juror"><input type="hidden" name="season_id" value="'.(int)$season->id.'"><input type="hidden" name="category_id" value="'.(int)$category->id.'"><input type="hidden" name="region" value="'.esc_attr($region_key).'">';
                $html.=wp_nonce_field('zmp_regional_jury_'.$season->id.'_'.$category->id.'_'.$region_key,'_wpnonce',true,false).'<label>Член на комисија <select name="juror_user_id" required><option value="">Изберете член</option>';
                foreach((array)$commission_members as $member)$html.='<option value="'.(int)$member->ID.'">'.esc_html($member->display_name?:$member->user_email).'</option>';
                $html.='</select></label> <button class="button button-primary">Додели за овој регион</button></form></div>';
            }
        }
        return $html.'</section>';
    }

    public static function required_slots($category_code) {
        $slots=array('vocal_interpretation'=>array('performance'=>'Настап'), 'school_choir'=>array('required'=>'Задолжителна композиција','optional'=>'Изборна композиција'), 'school_orchestra'=>array('required'=>'Задолжителна композиција','optional'=>'Изборна композиција'), 'school_bands'=>array('optional_one'=>'Изборна композиција 1','optional_two'=>'Изборна композиција 2'));
        return $slots[$category_code]??array();
    }

    public static function render_entry_controls($entry) {
        $mode=self::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region);
        if(!in_array($mode,array('online','physical'),true))return '<p class="zmp-alert">Модераторот треба да го постави начинот на регионалното натпреварување за овој регион пред пријавата да може да продолжи.</p>';
        $online=$mode==='online';$slots=self::required_slots((string)$entry->category_code);if(!$slots)return '';
        $rows=self::entry_media((int)$entry->id);$by_slot=array();foreach((array)$rows as $row)$by_slot[$row->slot_key]=$row;
        $draft=($entry->status??'')==='draft';$connected=self::is_connected();
        $stage_closed=self::regional_stage_is_closed((int)$entry->season_id,(int)$entry->category_id);
        $current_user=wp_get_current_user();
        $owns_entry=is_user_logged_in()&&(int)($entry->wp_user_id??0)>0&&(int)$entry->wp_user_id===(int)$current_user->ID&&(current_user_can('zmp_teacher_cap')||current_user_can('view_zmp_teacher_dashboard')||in_array('zmp_teacher',(array)$current_user->roles,true));
        $upload_allowed=$owns_entry&&self::can_upload_entry_video($entry,$mode);
        $html='<div class="zmp-youtube-entry"><p>'.($online?'Онлајн регионално натпреварувачко ниво — сите видеа се задолжителни.':'Регионалното натпреварувачко ниво е со физичко присуство — видеата се опционални.').' Unlisted не е приватно; линкот може да се препрати.</p>';
        if($owns_entry&&$stage_closed)$html.='<p class="zmp-alert">Регионалната етапа е затворена; поставувањето видеа веќе не е достапно.</p>';
        if($upload_allowed&&!$connected) {
            $html.='<p class="zmp-alert">YouTube каналот не е поврзан од администратор.'.($online?' Пријавата не може да се поднесе без задолжителните видеа.':' Ново видео не е задолжително за физичкиот натпревар, но започната upload-сесија или непотврден статус мора прво да се разреши.').($online?'':' Ако пријавата веќе содржи видео, каналот мора повторно да се поврзе за проверка.').'</p>';
        }
        foreach($slots as $key=>$label) {
            $row=$by_slot[$key]??null;$stored_state=$row?(string)$row->processing_status:'pending';
            $stale_resumable=$row&&$stored_state==='uploading'&&self::can_resume_upload_row($row);
            $state=$stale_resumable?'upload_failed':$stored_state;
            if($row&&$state==='ready'&&!preg_match('/^[A-Za-z0-9_-]{11}$/',(string)($row->video_id??'')))$state='invalid_record';
            $labels=array('pending'=>'Чека видео','uploading'=>'Се прикачува — почекајте пред да започнете нов upload','upload_failed'=>'Прикачувањето прекина — изберете го истиот фајл за продолжување','session_starting'=>'Се започнува безбедната сесија','session_unknown'=>'Сесијата истече; не започнувајте нов upload пред проверка од модератор','failed'=>'YouTube не успеа да го обработи видеото — побарајте помош од модератор. Не прикачувајте повторно.','processing'=>'Прикачено како Unlisted — се обработува','ready'=>'Подготвено и проверено','invalid_record'=>'Записот за видеото е нецелосен — побарајте модераторска проверка','privacy_error'=>'Приватносниот статус не е потврден','optional_skipped'=>'Ова опционално видео нема да се прикажува на комисијата');
            $html.='<section class="zmp-youtube-slot"><strong>'.esc_html($label).'</strong><span class="zmp-youtube-state zmp-youtube-state-'.esc_attr($state).'">'.esc_html($labels[$state]??$labels['pending']).'</span>';
            $can_retry=$upload_allowed&&$connected&&(!$row||self::can_resume_upload_row($row));
            if($can_retry) {
                $html.='<form method="post" enctype="multipart/form-data" action="'.esc_url(admin_url('admin-post.php')).'" class="zmp-youtube-upload-form"><input type="hidden" name="action" value="zmp_youtube_upload"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="slot_key" value="'.esc_attr($key).'">';
                $html.=wp_nonce_field('zmp_youtube_upload_'.(int)$entry->id,'_wpnonce',true,false);$max=min(self::MAX_VIDEO_BYTES,(int)wp_max_upload_size());
                $html.='<label>Видео (MP4, MOV, WEBM, AVI; максимум '.esc_html(size_format($max)).') <input type="file" name="youtube_video" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.m4v,.mov,.webm,.avi,.mpeg,.mpg" required></label><button class="button button-primary">'.($row?'Продолжи upload':'Прикачи видео').'</button><progress max="100" value="0" hidden></progress><span class="zmp-youtube-progress" aria-live="polite"></span></form>';
            } elseif($state==='failed') $html.='<p>Не прикачувајте повторно; контактирајте модератор.</p>';
            elseif($state==='privacy_error') $html.='<p>Контактирајте модератор за проверка на видеото во каналот.</p>';
            elseif($state==='session_unknown') $html.='<p>За да не се создаде дупликат, пријавата останува нацрт додека модератор не ја провери и разреши upload-сесијата.</p>';
            elseif($row&&$state==='processing') $html.='<p>Видеото сè уште се обработува. Проверете го статусот пред комисијата да го прегледа.</p>';
            elseif($row&&$state==='ready') $html.='<p>Видеото е потврдено како Unlisted и подготвено за комисијата.</p>';
            if($upload_allowed&&$connected&&in_array($state,array('processing','privacy_error'),true)&&!empty($row->video_id)) {
                $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_check"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="slot_key" value="'.esc_attr($key).'">';
                $html.=wp_nonce_field('zmp_youtube_check_'.(int)$entry->id,'_wpnonce',true,false).'<button class="button">Провери статус на видеото</button></form>';
            } elseif($upload_allowed&&$connected&&$state==='session_unknown'&&!empty($row->video_id)) {
                $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_youtube_check"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="slot_key" value="'.esc_attr($key).'">';
                $html.=wp_nonce_field('zmp_youtube_check_'.(int)$entry->id,'_wpnonce',true,false).'<button class="button">Провери статус на видеото</button></form>';
            }
            $html.='</section>';
        }
        $all_ready=count($slots)>0;$physical_busy=false;$physical_skip=false;$physical_blocked=false;$physical_reconnect_required=false;$physical_transition=false;
        foreach($slots as $key=>$label) {
            $row=$by_slot[$key]??null;
            if(!$row||$row->processing_status!=='ready'||!preg_match('/^[A-Za-z0-9_-]{11}$/',(string)($row->video_id??'')))$all_ready=false;
            if($row&&self::recent_upload_activity($row))$physical_busy=true;
            if($row) {
                if(!empty($row->video_id)&&!$connected){$physical_blocked=true;$physical_reconnect_required=true;}
                $state=(string)$row->processing_status;
                if(in_array($state,array('uploading','session_starting'),true)&&!self::recent_upload_activity($row))$physical_transition=true;
                if(!in_array($state,array('ready','optional_skipped'),true)) {
                    $physical_skip=true;
                    if(!self::physical_optional_video_can_skip($row))$physical_blocked=true;
                } elseif(!self::physical_optional_video_can_skip($row)) {
                    $physical_blocked=true;
                }
            }
        }
        if($draft) {
            if($online&&$all_ready&&$connected) {
                $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="zmp-youtube-finalize"><input type="hidden" name="action" value="zmp_youtube_finalize"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'">'.wp_nonce_field('zmp_youtube_finalize_'.(int)$entry->id,'_wpnonce',true,false).'<button class="button button-primary">Потврди и поднеси пријава</button></form>';
            } elseif($online&&$all_ready) {
                $html.='<p class="zmp-alert"><strong>Нацрт:</strong> видеата се прикачени, но пријавата не може да се поднесе додека администраторот повторно не го поврзе YouTube каналот за проверка.</p>';
            } elseif($online) {
                $html.='<p><strong>Нацрт:</strong> пријавата не е поднесена. Сите задолжителни видеа мора да бидат проверени.</p>';
            } elseif(!$physical_busy&&$physical_transition&&!$physical_reconnect_required) {
                $html.='<p class="zmp-alert">Потребна е проверка на upload-сесијата пред поднесување. Притиснете за системот да ја означи како неизвесна и да ја прикаже на модератор за проверка.</p>';
                $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="zmp-youtube-finalize"><input type="hidden" name="action" value="zmp_youtube_finalize"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'"><input type="hidden" name="skip_optional_videos" value="1">'.wp_nonce_field('zmp_youtube_finalize_'.(int)$entry->id,'_wpnonce',true,false).'<button class="button">Провери upload-сесија</button></form>';
            } elseif(!$physical_busy&&$physical_blocked) {
                $html.='<p class="zmp-alert"><strong>Нацрт:</strong> пријавата не може да се поднесе додека не се потврди безбедниот статус на секое започнато видео. '.($physical_reconnect_required?'Администраторот треба повторно да го поврзе YouTube каналот за проверка.':'Проверете го статусот или побарајте модераторска проверка.').'</p>';
            } elseif(!$physical_busy) {
                $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="zmp-youtube-finalize"><input type="hidden" name="action" value="zmp_youtube_finalize"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'">'.wp_nonce_field('zmp_youtube_finalize_'.(int)$entry->id,'_wpnonce',true,false);
                if($physical_skip)$html.='<input type="hidden" name="skip_optional_videos" value="1"><button class="button button-primary">Поднеси пријава без неготовите видеа</button>';
                else $html.='<button class="button button-primary">Потврди и поднеси пријава</button>';
                $html.='</form>';
            } else {
                $html.='<p><strong>Нацрт:</strong> почекајте да заврши започнатиот upload или проверката на обработката.</p>';
            }
        }
        $html.='</div><script>(function(){document.querySelectorAll(".zmp-youtube-upload-form").forEach(function(f){f.addEventListener("submit",function(e){if(!window.XMLHttpRequest)return;e.preventDefault();var x=new XMLHttpRequest(),p=f.querySelector("progress"),t=f.querySelector(".zmp-youtube-progress"),b=f.querySelector("button");if(p){p.hidden=false;p.value=0;}if(b)b.disabled=true;x.upload.addEventListener("progress",function(v){if(v.lengthComputable){var n=Math.round(v.loaded/v.total*100);if(p)p.value=n;if(t)t.textContent="Испратено до серверот: "+n+"%";}});x.onload=function(){if(t)t.textContent="Серверот го обработува upload-от. Освежување…";window.location.reload();};x.onerror=function(){if(t)t.textContent="Мрежната врска прекина. Повторете само со истиот видео-фајл за продолжување.";if(b)b.disabled=false;};x.open("POST",f.action);x.send(new FormData(f));});});})();</script>';
        return $html;
    }

    public static function render_physical_draft_finalize($entry) {
        if(($entry->status??'')!=='draft')return '';
        return '<div class="zmp-youtube-entry"><p>За оваа пријава не се бара ново видео.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="zmp-youtube-finalize"><input type="hidden" name="action" value="zmp_youtube_finalize"><input type="hidden" name="entry_id" value="'.(int)$entry->id.'">'.wp_nonce_field('zmp_youtube_finalize_'.(int)$entry->id,'_wpnonce',true,false).'<button class="button button-primary">Потврди и поднеси пријава</button></form></div>';
    }

    public static function upload_entry_video() {
        $entry_id=absint($_POST['entry_id']??0);self::require_entry_owner($entry_id,'zmp_youtube_upload_'.$entry_id);global $wpdb;
        $entry=self::entry($entry_id);$slot=sanitize_key(wp_unslash($_POST['slot_key']??''));
        $mode=$entry?self::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region):'';
        $slots=$entry?self::required_slots((string)$entry->category_code):array();
        if(!$entry||!isset($slots[$slot]))self::redirect_front('youtube_scope_invalid');
        if(in_array($mode,array('online','physical'),true)&&self::regional_stage_is_closed((int)$entry->season_id,(int)$entry->category_id))self::redirect_front('youtube_stage_closed');
        if(!self::can_upload_entry_video($entry,$mode))self::redirect_front('youtube_scope_invalid');
        if(!self::is_connected())self::redirect_front('youtube_not_connected');
        $file=$_FILES['youtube_video']??array();
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||empty($file['tmp_name'])||!is_uploaded_file($file['tmp_name']))self::redirect_front('youtube_upload_invalid');
        $size=(int)($file['size']??0);$max=min(self::MAX_VIDEO_BYTES,(int)wp_max_upload_size());$mime=self::video_mime($file['tmp_name'],$file['name']??'');$sha=is_file($file['tmp_name'])?hash_file('sha256',$file['tmp_name']):false;
        if($size<1||$size>$max||!$mime||!is_string($sha))self::redirect_front('youtube_upload_invalid');
        $row=self::media_row($entry_id,$slot);
        if($row&&(string)$row->processing_status==='uploading'&&!self::can_resume_upload_row($row))self::redirect_front('youtube_upload_active');
        if($row&&$row->processing_status==='ready'&&hash_equals((string)$row->file_sha256,$sha))self::redirect_front('youtube_video_ready');
        if($row&&(!empty($row->video_id)||!hash_equals((string)$row->file_sha256,$sha)))self::redirect_front('youtube_replace_not_supported');
        if($row&&in_array((string)$row->processing_status,array('session_unknown','session_starting'),true))self::redirect_front('youtube_upload_uncertain');
        if($row) {
            $url=!empty($row->upload_session_enc)?self::decrypt($row->upload_session_enc):'';
            if(!$url)self::redirect_front('youtube_upload_uncertain');
            if(!in_array((string)$row->processing_status,array('uploading','upload_failed'),true))self::redirect_front('youtube_replace_not_supported');
            if(!self::claim_upload_resume($row,$entry_id))self::redirect_front('youtube_upload_active');
            $media_id=(int)$row->id;
        } else {
            // Reserve the unique entry/slot before calling YouTube. A lost response can never trigger a blind second upload.
            $now=current_time('mysql');
            $saved=$wpdb->insert($wpdb->prefix.'zmp_youtube_media',array(
                'season_id'=>(int)$entry->season_id,'category_id'=>(int)$entry->category_id,'entry_id'=>$entry_id,'phase'=>'regional','slot_key'=>$slot,
                'video_id'=>'','upload_session_enc'=>'','upload_offset'=>0,'file_sha256'=>$sha,'file_size'=>$size,'mime_type'=>$mime,'privacy_status'=>'',
                'processing_status'=>'session_starting','uploaded_by'=>get_current_user_id(),'created_at'=>$now,'updated_at'=>$now
            ));
            if(!$saved)self::redirect_front('youtube_database_failed');
            $media_id=(int)$wpdb->insert_id;
            $session=self::start_resumable_upload($entry,$slot,$mime,$size);
            if(is_wp_error($session)) {
                $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('processing_status'=>'session_unknown','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
                self::redirect_front('youtube_upload_uncertain');
            }
            $saved=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>$session['encrypted_url'],'processing_status'=>'uploading','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
            if($saved===false) {
                $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>'','processing_status'=>'session_unknown','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
                self::redirect_front('youtube_upload_uncertain');
            }
            $url=$session['url'];
        }
        $media=self::media_row($entry_id,$slot);$result=self::resume_upload($entry_id,$media_id,$file['tmp_name'],$size,$mime,$url,(int)($media->upload_offset??0));
        if(is_wp_error($result)&&$result->get_error_code()==='youtube_session_expired') {
            $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>'','processing_status'=>'session_unknown','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
            self::redirect_front('youtube_upload_uncertain');
        }
        if(is_wp_error($result)) {
            $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('processing_status'=>'upload_failed','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
            self::redirect_front('youtube_upload_failed');
        }
        $video_id=sanitize_text_field($result['id']??'');$privacy=sanitize_key($result['privacy']??'');
        if(!preg_match('/^[A-Za-z0-9_-]{11}$/',$video_id)) {
            $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>'','processing_status'=>'session_unknown','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
            self::redirect_front('youtube_upload_uncertain');
        }
        if($privacy!=='unlisted') {
            $saved=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('video_id'=>$video_id,'privacy_status'=>$privacy,'processing_status'=>'privacy_error','upload_session_enc'=>'','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
            if($saved===false)self::redirect_front('youtube_database_failed');
            self::redirect_front('youtube_privacy_invalid');
        }
        $state=self::media_state($privacy,sanitize_key($result['processing']??''));
        $saved=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('video_id'=>$video_id,'upload_session_enc'=>'','upload_offset'=>$size,'privacy_status'=>'unlisted','processing_status'=>$state,'updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id));
        if($saved===false)self::redirect_front('youtube_database_failed');
        if(class_exists('ZMP_Debug'))ZMP_Debug::log('info','YouTube entry video upload finished.',array('entry_id'=>$entry_id,'slot'=>$slot,'status'=>$state,'bytes'=>$size),'youtube');
        self::redirect_front($state==='ready'?'youtube_video_ready':($state==='failed'?'youtube_processing_failed':'youtube_processing'));
    }

    public static function check_entry_processing() {
        $entry_id=absint($_POST['entry_id']??0); self::require_entry_owner($entry_id,'zmp_youtube_check_'.$entry_id);
        $slot=sanitize_key(wp_unslash($_POST['slot_key']??'')); $row=self::media_row($entry_id,$slot); $entry=self::entry($entry_id);
        $mode=$entry?self::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region):'';
        if(!$row||!$entry||!isset(self::required_slots($entry->category_code)[$slot]))self::redirect_front('youtube_scope_invalid');
        if(in_array($mode,array('online','physical'),true)&&self::regional_stage_is_closed((int)$entry->season_id,(int)$entry->category_id))self::redirect_front('youtube_stage_closed');
        if(!self::can_upload_entry_video($entry,$mode))self::redirect_front('youtube_scope_invalid');
        $result=self::verify_video($row->video_id); if(is_wp_error($result))self::redirect_front('youtube_verify_failed');
        $state=self::media_state($result['privacy'],$result['processing']);
        global $wpdb; $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('privacy_status'=>$result['privacy'],'processing_status'=>$state,'updated_at'=>current_time('mysql')),array('id'=>(int)$row->id));
        self::redirect_front($state==='ready'?'youtube_video_ready':($state==='failed'?'youtube_processing_failed':($state==='privacy_error'?'youtube_privacy_invalid':'youtube_processing')));
    }

    public static function finalize_entry() {
        $entry_id=absint($_POST['entry_id']??0);self::require_entry_owner($entry_id,'zmp_youtube_finalize_'.$entry_id);$entry=self::entry($entry_id);
        if(!$entry||$entry->status!=='draft')self::redirect_front('youtube_scope_invalid');
        $mode=self::regional_delivery_mode((int)$entry->season_id,(int)$entry->category_id,(string)$entry->region);if($mode==='unconfigured')self::redirect_front('youtube_mode_unavailable');
        if(!self::has_required_documents($entry_id))self::redirect_front('youtube_documents_missing');
        global $wpdb;
        if($mode==='online') {
            $slots=self::required_slots($entry->category_code);if(!$slots)self::redirect_front('youtube_missing');
            foreach($slots as $key=>$label) {
                $row=self::media_row($entry_id,$key);if(!$row||!preg_match('/^[A-Za-z0-9_-]{11}$/',(string)$row->video_id))self::redirect_front('youtube_missing');
                $verified=self::verify_video($row->video_id);if(is_wp_error($verified))self::redirect_front('youtube_verify_failed');
                $state=self::media_state($verified['privacy'],$verified['processing']);
                $wpdb->update($wpdb->prefix.'zmp_youtube_media',array('privacy_status'=>$verified['privacy'],'processing_status'=>$state,'updated_at'=>current_time('mysql')),array('id'=>(int)$row->id));
                if($state==='failed')self::redirect_front('youtube_processing_failed');
                if($state!=='ready')self::redirect_front($state==='privacy_error'?'youtube_privacy_invalid':'youtube_processing');
            }
        } elseif($mode==='physical') {
            $rows=self::entry_media($entry_id);$skip=!empty($_POST['skip_optional_videos']);
            foreach((array)$rows as $row) {
                $state=(string)($row->processing_status??'');
                if(self::recent_upload_activity($row))self::redirect_front('youtube_processing');
                if(in_array($state,array('uploading','session_starting'),true)) {
                    $updated_at=(string)($row->updated_at??'');
                    if($updated_at==='')self::redirect_front('youtube_finalize_failed');
                    $transitioned=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>'','processing_status'=>'session_unknown','updated_at'=>current_time('mysql')),array('id'=>(int)$row->id,'entry_id'=>$entry_id,'processing_status'=>$state,'updated_at'=>$updated_at));
                    if($transitioned!==1)self::redirect_front('youtube_finalize_failed');
                    self::redirect_front('youtube_upload_uncertain');
                }
                if($state==='ready'&&empty($row->video_id))self::redirect_front('youtube_verify_failed');
                if($state==='session_unknown'&&empty($row->video_id))self::redirect_front('youtube_upload_uncertain');
                if($state==='upload_failed'&&empty($row->video_id))self::redirect_front('youtube_upload_failed');
                if(!empty($row->video_id)) {
                    $verified=self::verify_video((string)$row->video_id);
                    if(is_wp_error($verified))self::redirect_front('youtube_verify_failed');
                    $state=self::media_state($verified['privacy'],$verified['processing']);
                    $saved=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('privacy_status'=>$verified['privacy'],'processing_status'=>$state,'updated_at'=>current_time('mysql')),array('id'=>(int)$row->id,'entry_id'=>$entry_id));
                    if($saved===false)self::redirect_front('youtube_database_failed');
                    $row->privacy_status=$verified['privacy'];$row->processing_status=$state;
                    if($state==='privacy_error')self::redirect_front('youtube_privacy_invalid');
                } elseif($state==='privacy_error') {
                    self::redirect_front('youtube_privacy_invalid');
                } elseif($state==='session_unknown') {
                    self::redirect_front('youtube_upload_uncertain');
                }
                if(!empty($row->upload_session_enc))self::redirect_front('youtube_upload_uncertain');
                if(!$skip&&!in_array((string)$row->processing_status,array('ready','optional_skipped'),true))self::redirect_front('youtube_optional_video_skip_required');
                if($skip&&!self::physical_optional_video_can_skip($row))self::redirect_front(self::physical_optional_video_skip_error($row));
            }
            if($skip)foreach((array)$rows as $row) {
                if(in_array((string)$row->processing_status,array('ready','optional_skipped'),true))continue;
                if(!self::physical_optional_video_can_skip($row))self::redirect_front(self::physical_optional_video_skip_error($row));
                // Keep the external object untouched; only mark a verified, safe optional slot as omitted from jury review.
                $saved=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_session_enc'=>'','processing_status'=>'optional_skipped','updated_at'=>current_time('mysql')),array('id'=>(int)$row->id,'entry_id'=>$entry_id));
                if($saved===false)self::redirect_front('youtube_database_failed');
                $row->processing_status='optional_skipped';
            }
        }
        $changed=$wpdb->update($wpdb->prefix.'zmp_category_entries',array('status'=>'submitted','updated_at'=>current_time('mysql')),array('id'=>$entry_id,'wp_user_id'=>get_current_user_id(),'status'=>'draft'));
        if($changed!==1)self::redirect_front('youtube_finalize_failed');
        $message=$mode==='online'?'Пријавата е поднесена откако сите задолжителни видеа се потврдени како Unlisted.':($mode==='physical'?'Пријавата е поднесена за физичко регионално натпреварување; видеата беа опционални.':'Пријавата е поднесена; за ова натпреварувачко ниво не се бара ново видео.');
        ZMP_Communications::notify_teacher((int)$entry->wp_user_id,'Примена пријава: '.$entry->category_name,$message,$entry->academic_year,(int)$entry->category_id,'');
        self::redirect_front('entry_submitted');
    }

    /** Reviewer-only player. Regional video access is checked against the entry's exact region. */
    public static function video_player($entry_id,$phase) {
        if($phase!=='regional'||!is_user_logged_in())return '';
        global $wpdb;
        $row=$wpdb->get_row($wpdb->prepare("SELECT m.category_id,m.season_id,e.region,e.status FROM {$wpdb->prefix}zmp_youtube_media m JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=m.entry_id WHERE m.entry_id=%d AND m.phase='regional' AND m.processing_status='ready' AND e.status IN ('submitted','approved','complete') ORDER BY m.slot_key LIMIT 1",absint($entry_id)));
        if(!$row||!in_array(self::regional_delivery_mode((int)$row->season_id,(int)$row->category_id,(string)$row->region),array('online','physical'),true))return '';
        $allowed=current_user_can('manage_options')||current_user_can('zmp_moderator_cap')||self::is_regional_video_juror_for((int)$row->season_id,(int)$row->category_id,(string)$row->region,get_current_user_id());
        if(!$allowed)return '';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT slot_key,video_id FROM {$wpdb->prefix}zmp_youtube_media WHERE entry_id=%d AND phase='regional' AND processing_status='ready' ORDER BY slot_key",absint($entry_id)));
        $html='';foreach((array)$rows as $video){$url='https://www.youtube-nocookie.com/embed/'.rawurlencode($video->video_id).'?rel=0';$html.='<div class="zmp-video-review"><p>'.esc_html(str_replace('_',' ',(string)$video->slot_key)).'</p><iframe width="560" height="315" src="'.esc_url($url).'" title="Видео за оценување" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';}
        return $html;
    }

    public static function is_connected() { $s=self::settings(); return !empty($s['client_id'])&&!empty($s['channel_id'])&&(bool)self::decrypt($s['client_secret_enc']??'')&&(bool)self::decrypt($s['refresh_token_enc']??''); }
    /** Do not erase database references to externally hosted videos during a full reset. */
    public static function delete_all_videos_for_reset() {
        if(!current_user_can('manage_options'))return false;
        global $wpdb;$table=$wpdb->prefix.'zmp_youtube_media';
        $has_video=(int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE video_id<>''");
        // This connector intentionally lacks permission to delete channel videos.
        // Stop the reset before it deletes any other archive or competition data.
        return $has_video===0;
    }
    /** Only draft regional uploads and unreviewed physical-regional submissions may be changed. */
    private static function can_upload_entry_video($entry,$mode) {
        if(!is_object($entry)||!in_array($mode,array('online','physical'),true))return false;
        if(self::regional_stage_is_closed((int)($entry->season_id??0),(int)($entry->category_id??0)))return false;
        $status=(string)($entry->status??'');
        if($status==='draft')return true;
        if($status!=='submitted'||$mode!=='physical')return false;
        return !self::regional_entry_has_review($entry);
    }
    private static function regional_entry_has_review($entry) {
        global $wpdb;
        $season_id=absint($entry->season_id??0);$category_id=absint($entry->category_id??0);$entry_id=absint($entry->id??0);
        if(!$season_id||!$category_id||!$entry_id)return true;
        $query=$wpdb->prepare(
            "SELECT (EXISTS(SELECT 1 FROM {$wpdb->prefix}zmp_jury_assessments WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase='regional') OR EXISTS(SELECT 1 FROM {$wpdb->prefix}zmp_category_final_results WHERE season_id=%d AND category_id=%d AND entry_id=%d AND phase='regional'))",
            $season_id,$category_id,$entry_id,$season_id,$category_id,$entry_id
        );
        $reviewed=$wpdb->get_var($query);
        return $reviewed===null||(int)$reviewed!==0;
    }
    private static function can_resume_upload_row($row) {
        if(!$row||!in_array((string)($row->processing_status??''),array('uploading','upload_failed'),true)||empty($row->upload_session_enc)||!empty($row->video_id))return false;
        return (string)$row->processing_status==='upload_failed'||!self::recent_upload_activity($row);
    }
    private static function claim_upload_resume($row,$entry_id) {
        global $wpdb;
        $entry_id=absint($entry_id);$media_id=absint($row->id??0);$status=(string)($row->processing_status??'');$updated=(string)($row->updated_at??'');
        if(!$entry_id||!$media_id||$updated===''||!self::can_resume_upload_row($row))return false;
        $claimed=$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('processing_status'=>'uploading','updated_at'=>current_time('mysql')),array('id'=>$media_id,'entry_id'=>$entry_id,'processing_status'=>$status,'updated_at'=>$updated),array('%s','%s'),array('%d','%d','%s','%s'));
        return $claimed===1;
    }
    private static function regional_stage_is_closed($season_id,$category_id) {
        global $wpdb;
        $table=$wpdb->prefix.'zmp_category_stage_closures';
        $exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
        if($exists!==$table)return true;
        $closed=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE season_id=%d AND category_id=%d AND phase='regional'",absint($season_id),absint($category_id)));
        if($closed===null||$wpdb->last_error!=='')return true;
        return (int)$closed>0;
    }
    private static function physical_optional_video_can_skip($row) {
        if(!is_object($row))return false;
        $state=(string)($row->processing_status??'');
        if(in_array($state,array('uploading','session_starting','session_unknown','upload_failed','privacy_error'),true))return false;
        if(!empty($row->upload_session_enc))return false;
        $video_id=trim((string)($row->video_id??''));
        if($state==='ready'&&!preg_match('/^[A-Za-z0-9_-]{11}$/',$video_id))return false;
        if($video_id!==''&&(string)($row->privacy_status??'')!=='unlisted')return false;
        return in_array($state,array('pending','processing','failed','ready','optional_skipped'),true);
    }
    private static function physical_optional_video_skip_error($row) {
        $state=(string)($row->processing_status??'');
        if($state==='privacy_error'||(!empty($row->video_id)&&(string)($row->privacy_status??'')!=='unlisted'))return 'youtube_privacy_invalid';
        if($state==='session_unknown'||in_array($state,array('uploading','session_starting'),true)||!empty($row->upload_session_enc))return 'youtube_upload_uncertain';
        if($state==='upload_failed')return 'youtube_upload_failed';
        return 'youtube_optional_video_skip_required';
    }
    private static function recent_upload_activity($row) {
        if(!$row||!in_array((string)$row->processing_status,array('uploading','session_starting'),true))return false;
        $updated=strtotime((string)($row->updated_at??''));
        if(!$updated)return true;
        return max(0,(int)current_time('timestamp')-$updated)<600;
    }
    private static function entry_media($entry_id) { global $wpdb; return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_youtube_media WHERE entry_id=%d AND phase='regional' ORDER BY slot_key",absint($entry_id))); }
    private static function media_row($entry_id,$slot) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_youtube_media WHERE entry_id=%d AND phase='regional' AND slot_key=%s",absint($entry_id),sanitize_key($slot))); }
    private static function entry($id) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT e.*,c.category_code,c.name_mk AS category_name,s.academic_year FROM {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id JOIN {$wpdb->prefix}zmp_seasons s ON s.id=e.season_id WHERE e.id=%d",absint($id))); }

    private static function start_resumable_upload($entry,$slot,$mime,$size) {
        $token=self::access_token();if(is_wp_error($token))return $token;
        $ref=substr(hash_hmac('sha256',(string)$entry->registration_key.'|'.$slot,wp_salt('auth')),0,12);
        $title='ЗМП — натпревари '.$entry->academic_year.' · '.$ref;
        // Do not silently classify child participants as "not made for kids".
        $metadata=array('snippet'=>array('title'=>$title,'categoryId'=>'10'),'status'=>array('privacyStatus'=>'unlisted'));
        $url='https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet%2Cstatus%2CprocessingDetails';
        $res=wp_remote_post($url,array('timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json; charset=UTF-8','X-Upload-Content-Type'=>$mime,'X-Upload-Content-Length'=>(string)$size),'body'=>wp_json_encode($metadata)));
        if(is_wp_error($res)||wp_remote_retrieve_response_code($res)!==200)return new WP_Error('youtube_session_failed');
        $location=wp_remote_retrieve_header($res,'location'); if(!is_string($location)||strpos($location,'https://www.googleapis.com/upload/youtube/')!==0)return new WP_Error('youtube_session_failed');
        // Keep the resumable URL only in encrypted form: it is a bearer credential.
        $encrypted_url=self::encrypt($location); if(!$encrypted_url)return new WP_Error('youtube_session_secure_storage_unavailable');
        return array('url'=>$location,'encrypted_url'=>$encrypted_url);
    }

    private static function resume_upload($entry_id,$media_id,$path,$size,$mime,$url,$stored_offset) {
        $token=self::access_token();if(is_wp_error($token))return $token;
        $probe=wp_remote_request($url,array('method'=>'PUT','timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Length'=>'0','Content-Range'=>'bytes */'.$size),'body'=>''));
        if(is_wp_error($probe))return new WP_Error('youtube_resume_failed');
        $probe_code=(int)wp_remote_retrieve_response_code($probe);
        if($probe_code===200||$probe_code===201)return self::upload_result(wp_remote_retrieve_body($probe));
        if(!in_array($probe_code,array(308,404,410),true))return new WP_Error('youtube_resume_failed');
        if($probe_code===404||$probe_code===410)return new WP_Error('youtube_session_expired');
        $range=(string)wp_remote_retrieve_header($probe,'range');$offset=0;if(preg_match('/bytes=0-(\d+)/',$range,$m))$offset=(int)$m[1]+1;
        $fh=fopen($path,'rb');if(!$fh)return new WP_Error('youtube_file_unreadable');
        while($offset<$size) {
            if(fseek($fh,$offset)!==0){fclose($fh);return new WP_Error('youtube_file_unreadable');}
            $length=min(self::CHUNK_BYTES,$size-$offset);$chunk='';while(strlen($chunk)<$length&&!feof($fh)){$part=fread($fh,min(1048576,$length-strlen($chunk)));if($part===false||$part==='')break;$chunk.=$part;}
            if(strlen($chunk)!==$length){fclose($fh);return new WP_Error('youtube_file_unreadable');}
            $end=$offset+$length-1;$res=wp_remote_request($url,array('method'=>'PUT','timeout'=>180,'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Length'=>(string)$length,'Content-Type'=>$mime,'Content-Range'=>'bytes '.$offset.'-'.$end.'/'.$size),'body'=>$chunk));
            if(is_wp_error($res)){fclose($fh);return new WP_Error('youtube_transfer_interrupted');}
            $code=(int)wp_remote_retrieve_response_code($res);
            if($code===200||$code===201){fclose($fh);return self::upload_result(wp_remote_retrieve_body($res));}
            if($code!==308){fclose($fh);return new WP_Error('youtube_transfer_failed');}
            $range=(string)wp_remote_retrieve_header($res,'range');if(preg_match('/bytes=0-(\d+)/',$range,$m))$offset=(int)$m[1]+1;else $offset=$end+1;
            global $wpdb;$wpdb->update($wpdb->prefix.'zmp_youtube_media',array('upload_offset'=>$offset,'processing_status'=>'uploading','updated_at'=>current_time('mysql')),array('id'=>absint($media_id),'entry_id'=>absint($entry_id),'phase'=>'regional'));
        }
        fclose($fh);return new WP_Error('youtube_upload_incomplete');
    }

    private static function upload_result($body) { $d=json_decode((string)$body,true); if(empty($d['id']))return new WP_Error('youtube_response_invalid');return array('id'=>sanitize_text_field($d['id']),'privacy'=>sanitize_key($d['status']['privacyStatus']??''),'processing'=>sanitize_key($d['processingDetails']['processingStatus']??'')); }
    private static function verify_video($id) {
        if(!preg_match('/^[A-Za-z0-9_-]{11}$/',(string)$id))return new WP_Error('youtube_id_invalid');$token=self::access_token();if(is_wp_error($token))return $token;
        $url='https://www.googleapis.com/youtube/v3/videos?'.http_build_query(array('part'=>'status,processingDetails','id'=>$id),'','&',PHP_QUERY_RFC3986);$d=self::api_get($url,$token);
        if(is_wp_error($d)||empty($d['items'][0]))return new WP_Error('youtube_verify_failed');$v=$d['items'][0];return array('privacy'=>sanitize_key($v['status']['privacyStatus']??''),'processing'=>sanitize_key($v['processingDetails']['processingStatus']??''));
    }
    private static function api_get($url,$token) { $r=wp_remote_get($url,array('timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token)));if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return new WP_Error('youtube_api_failed');$d=json_decode(wp_remote_retrieve_body($r),true);return is_array($d)?$d:new WP_Error('youtube_api_response'); }
    private static function media_state($privacy,$processing) { if((string)$privacy!=='unlisted')return 'privacy_error';if((string)$processing==='succeeded')return 'ready';if(in_array((string)$processing,array('failed','terminated'),true))return 'failed';return 'processing'; }
    private static function has_required_documents($entry_id) { global $wpdb;$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT document_type) FROM {$wpdb->prefix}zmp_entry_documents WHERE entry_id=%d AND document_type IN ('signed_application','payment_proof','media_consent') AND (drive_file_id<>'' OR file_key<>'')",absint($entry_id)));return $count===3; }
    private static function access_token() {
        $s=self::settings();$secret=self::decrypt($s['client_secret_enc']??'');$refresh=self::decrypt($s['refresh_token_enc']??'');if(empty($s['client_id'])||!$secret||!$refresh)return new WP_Error('youtube_not_connected');
        $r=wp_remote_post('https://oauth2.googleapis.com/token',array('timeout'=>30,'body'=>array('client_id'=>$s['client_id'],'client_secret'=>$secret,'refresh_token'=>$refresh,'grant_type'=>'refresh_token')));
        if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return new WP_Error('youtube_token_refresh');$d=json_decode(wp_remote_retrieve_body($r),true);if(empty($d['access_token']))return new WP_Error('youtube_token_refresh');
        // Access tokens stay only in request memory; only refresh tokens are persisted, encrypted.
        return sanitize_text_field($d['access_token']);
    }
    private static function video_mime($path,$name) {
        $ext=strtolower(pathinfo((string)$name,PATHINFO_EXTENSION));$types=array('mp4'=>'video/mp4','m4v'=>'video/mp4','mov'=>'video/quicktime','webm'=>'video/webm','avi'=>'video/x-msvideo','mpeg'=>'video/mpeg','mpg'=>'video/mpeg');if(!isset($types[$ext]))return '';
        $finfo=function_exists('finfo_open')?finfo_open(FILEINFO_MIME_TYPE):false;$detected=$finfo?finfo_file($finfo,$path):'';if($finfo)finfo_close($finfo);
        return in_array($detected,array('application/octet-stream','application/mp4','video/mp4','video/quicktime','video/webm','video/x-msvideo','video/mpeg'),true)?$types[$ext]:'';
    }
    private static function require_entry_owner($id,$nonce) { if(!is_user_logged_in()||!current_user_can('zmp_teacher_cap')&&!current_user_can('view_zmp_teacher_dashboard')&&!in_array('zmp_teacher',(array)wp_get_current_user()->roles,true))wp_die('Потребна е овластена сметка на наставник.','Нема пристап',array('response'=>403));check_admin_referer($nonce);$entry=self::entry($id);if(!$entry||(int)$entry->wp_user_id!==get_current_user_id())wp_die('Пријавата не е достапна за оваа сметка.','Нема пристап',array('response'=>403)); }
    private static function is_admin() { return current_user_can('manage_options'); }
    private static function require_admin() { if(!self::is_admin())wp_die('Недоволни привилегии.','Нема пристап',array('response'=>403)); }
    private static function settings() { $s=get_option(self::OPTION,array());return is_array($s)?$s:array(); }
    private static function encrypt($value) { if(!function_exists('openssl_encrypt'))return '';try{$iv=random_bytes(12);}catch(Throwable $e){return '';}$tag='';$key=hash('sha256',wp_salt('auth').wp_salt('secure_auth'),true);$cipher=openssl_encrypt((string)$value,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);return $cipher!==false?base64_encode($iv.$tag.$cipher):''; }
    private static function decrypt($value) { if(!function_exists('openssl_decrypt'))return ''; $raw=base64_decode((string)$value,true);if($raw===false||strlen($raw)<29)return ''; $key=hash('sha256',wp_salt('auth').wp_salt('secure_auth'),true);$plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));return is_string($plain)?$plain:''; }
    private static function redirect_uri() { return admin_url('admin-post.php?action=zmp_youtube_callback'); }
    private static function redirect_admin($status) { wp_safe_redirect(add_query_arg(array('page'=>'zmp-youtube','zmp_youtube_status'=>sanitize_key($status)),admin_url('admin.php')));exit; }
    private static function redirect_front($status) { $url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_youtube_status',sanitize_key($status),remove_query_arg('zmp_youtube_status',$url)));exit; }
}
