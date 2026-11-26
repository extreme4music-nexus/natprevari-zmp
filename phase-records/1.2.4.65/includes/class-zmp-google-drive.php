<?php
if (!defined('ABSPATH')) { exit; }

/** Private Google Drive storage for videos and registration documents. */
class ZMP_Google_Drive {
    const OPTION = 'zmp_google_drive_settings';
    const MAX_VIDEO_BYTES = 524288000; // 500 MiB; host upload limits may be lower.
    const CHUNK_BYTES = 8388608; // Drive resumable-upload chunks must be multiples of 256 KiB.

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_post_zmp_save_drive_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_zmp_save_drive_folders', array(__CLASS__, 'save_folder_settings'));
        add_action('admin_post_zmp_migrate_drive_folders', array(__CLASS__, 'migrate_folder_batch'));
        add_action('admin_post_zmp_drive_connect', array(__CLASS__, 'connect'));
        add_action('admin_post_zmp_drive_callback', array(__CLASS__, 'callback'));
        add_action('admin_post_zmp_drive_disconnect', array(__CLASS__, 'disconnect'));
        // Category videos use YouTube in .49; Drive remains for documents, rosters, scores and matrices.
        add_action('admin_post_zmp_stream_category_video', array(__CLASS__, 'stream_video'));
        add_action('admin_post_zmp_purge_category_videos', array(__CLASS__, 'purge_season_videos'));
        add_action('admin_post_zmp_migrate_legacy_storage', array(__CLASS__, 'migrate_legacy_storage'));
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'zmp_category_media';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            season_id BIGINT(20) UNSIGNED NOT NULL,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            phase VARCHAR(20) NOT NULL,
            drive_file_id VARCHAR(190) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            uploaded_by BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY season_category_phase (season_id,category_id,phase),
            KEY entry_phase_status (entry_id,phase,status)
        ) $charset;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_drive_migration_audit (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(30) NOT NULL,
            entity_id BIGINT(20) UNSIGNED NOT NULL,
            old_file_id VARCHAR(190) NOT NULL,
            new_file_id VARCHAR(190) NOT NULL,
            sha256 CHAR(64) NOT NULL,
            target_signature CHAR(64) NOT NULL,
            migrated_by BIGINT(20) UNSIGNED NOT NULL,
            migrated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY entity (entity_type,entity_id),
            KEY target_signature (target_signature)
        ) $charset;");
    }

    public static function register_menu() {
        add_submenu_page('zmp-competitions', 'Google Drive архива', 'Google Drive', 'manage_options', 'zmp-google-drive', array(__CLASS__, 'render_admin_page'));
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Недоволни привилегии.'); }
        $settings = self::settings();
        $connected = !empty($settings['refresh_token_enc']) && !empty($settings['root_folder_id']);
        $client_id = (string)($settings['client_id'] ?? '');
        $picker_key=(string)($settings['picker_api_key']??''); $picker_app=(string)($settings['picker_app_id']??'');
        $max = size_format(min(self::MAX_VIDEO_BYTES, (int)wp_max_upload_size()));
        ?>
        <div class="wrap"><h1>Приватна Google Drive архива</h1>
        <?php if (isset($_GET['zmp_drive_status'])): ?><div class="notice notice-<?php echo esc_attr($_GET['zmp_drive_status']==='connected'?'success':'error'); ?>"><p><?php echo esc_html(self::status_message(sanitize_key(wp_unslash($_GET['zmp_drive_status'])))); ?></p></div><?php endif; ?>
        <p>Видеата, годишните формулари, потврдите, списоците и завршните архиви се прикачуваат во приватна Drive-папка. Google OAuth мора да биде поставен за сметката на ЗМП и Drive API да биде вклучен во Google Cloud.</p>
        <h2>OAuth поставки</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="zmp_save_drive_settings"><?php wp_nonce_field('zmp_save_drive_settings'); ?>
        <table class="form-table"><tr><th><label for="zmp-drive-client-id">OAuth Client ID</label></th><td><input id="zmp-drive-client-id" class="regular-text" type="text" name="client_id" value="<?php echo esc_attr($client_id); ?>" autocomplete="off" required></td></tr>
        <tr><th><label for="zmp-drive-client-secret">OAuth Client secret</label></th><td><input id="zmp-drive-client-secret" class="regular-text" type="password" name="client_secret" value="" autocomplete="new-password"><p class="description">Оставете празно за да се задржи постојната шифрирана вредност. Redirect URI: <code><?php echo esc_html(self::redirect_uri()); ?></code></p></td></tr><tr><th><label for="zmp-drive-picker-key">Google Picker API key</label></th><td><input id="zmp-drive-picker-key" class="regular-text" type="text" name="picker_api_key" value="<?php echo esc_attr($picker_key); ?>" autocomplete="off"><p class="description">Јавен API key за Google Picker. Во Google Cloud ограничете го на доменот на сајтот и само на Google Picker API.</p></td></tr><tr><th><label for="zmp-drive-picker-app">Google Cloud project number</label></th><td><input id="zmp-drive-picker-app" class="regular-text" type="text" name="picker_app_id" value="<?php echo esc_attr($picker_app); ?>" inputmode="numeric" autocomplete="off"><p class="description">Бројот на Cloud проектот во кој е вклучен Google Picker API.</p></td></tr></table>
        <?php submit_button('Зачувај OAuth поставки'); ?></form>
        <?php if ($connected): ?><p><strong>Статус:</strong> Поврзано · Root папка ID: <code><?php echo esc_html($settings['root_folder_id']); ?></code></p>
        <p>Формулари, потврди, списоци и завршни ZIP-архиви се чуваат во Drive. Старите датотеки на хостингот се префрлаат во мали, проверливи серии. Хешот се проверува пред да се избрише локалната копија.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="zmp_migrate_legacy_storage"><?php wp_nonce_field('zmp_migrate_legacy_storage'); ?><?php submit_button('Пренеси следна серија стари фајлови во Drive', 'secondary', 'submit', false); ?></form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="zmp_drive_disconnect"><?php wp_nonce_field('zmp_drive_disconnect'); ?><?php submit_button('Исклучи Google Drive', 'secondary', 'submit', false); ?></form>
        <?php elseif ($client_id && !empty($settings['client_secret_enc'])): ?><p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zmp_drive_connect'), 'zmp_drive_connect')); ?>">Поврзи ја сметката на ЗМП</a></p><?php else: ?><p>Внесете ги OAuth Client ID и Client secret и зачувајте пред поврзување.</p><?php endif; ?>
        <hr><h2>Видеа за завршени години</h2><p>Видеата се задржуваат до завршување на Државниот натпревар. Администратор може да ги избрише од Drive само откако годината е официјално завршена.</p>
        <?php echo self::render_folder_settings($settings); ?>
        <?php echo self::render_matrix_picker_admin($settings); ?>
        <?php echo self::render_completed_seasons(); ?>
        </div><?php
    }

    private static function folder_labels() { return array('applications'=>'Потпишани пријави','payments'=>'Докази за уплата','consents'=>'Согласности за медиумска употреба','rosters'=>'Архивирани списоци','sheet_music'=>'Нотни записи','matrices'=>'Матрици'); }
    private static function purpose_folder_key($purpose) { $map=array('signed-application'=>'applications','signed_application'=>'applications','payment-proof'=>'payments','payment_proof'=>'payments','media-consent'=>'consents','media_consent'=>'consents','student-rosters'=>'rosters','sheet-music'=>'sheet_music','sheet_music'=>'sheet_music','annual-form'=>'applications','matrix'=>'matrices');return $map[sanitize_key($purpose)]??''; }
    private static function render_folder_settings($settings) {
        if(!current_user_can('manage_options'))return '';$values=(array)($settings['folder_ids']??array());$status=sanitize_key(wp_unslash($_GET['zmp_drive_folders']??''));$html='<hr><h2>Drive папки по намена</h2><p>Внесете ID на приватна папка до која поврзаната сметка на ЗМП има пристап. Празно поле ја користи постојната root структура. Промената на ID не ги преместува постојните фајлови.</p>';
        if($status==='saved')$html.='<div class="notice notice-success"><p>ID-поставките се зачувани и повторно вчитани од базата.</p></div>';elseif($status==='saved_unverified')$html.='<div class="notice notice-warning"><p>ID-поставките се зачувани. Drive сè уште не потврди пристап до една или повеќе папки; проверете ја врската и дозволите пред користење.</p></div>';elseif($status==='invalid')$html.='<div class="notice notice-error"><p>Формуларот не е целосен или некој ID има недозволен формат. Не е зачувана промена.</p></div>';
        $html.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="zmp_save_drive_folders">'.wp_nonce_field('zmp_save_drive_folders','_wpnonce',true,false).'<table class="form-table">';
        foreach(self::folder_labels() as $key=>$label)$html.='<tr><th><label for="zmp-drive-folder-'.esc_attr($key).'">'.esc_html($label).'</label></th><td><input class="regular-text" id="zmp-drive-folder-'.esc_attr($key).'" name="folder_ids['.esc_attr($key).']" value="'.esc_attr($values[$key]??'').'" maxlength="190" autocomplete="off" placeholder="Google Drive folder ID"></td></tr>';
        $html.='</table><p class="description">Стандардните папки се пополнети за партитури и матрици. Зачувувањето на ID не преместува постојни фајлови.</p>'.get_submit_button('Зачувај Drive папки').'</form>';
        $summary=self::folder_migration_summary();$html.='<hr><h3>Проверка и безбедно копирање во новите папки</h3><p>Прегледот покажува број на постојни Drive-фајлови што можат да се копираат според новите ID. Копирањето оди во серии од најмногу 5. Секој фајл се проверува со SHA-256 и старата копија останува зачувана. Ниту еден стар фајл не се брише автоматски.</p><ul>';
        foreach($summary as $label=>$count)$html.='<li>'.esc_html($label).': '.(int)$count.'</li>';
        $html.='</ul><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(\'Ќе се копираат најмногу пет приватни фајлови и референците ќе се ажурираат по проверка. Продолжи?\');"><input type="hidden" name="action" value="zmp_migrate_drive_folders">'.wp_nonce_field('zmp_migrate_drive_folders','_wpnonce',true,false).'<label>Потврда <input name="confirmation" required placeholder="КОПИРАЈ И ПРОВЕРИ"></label> '.get_submit_button('Копирај и провери следна серија','secondary','submit',false).'</form>';
        if(isset($_GET['zmp_folder_migration'])){$st=sanitize_key(wp_unslash($_GET['zmp_folder_migration']));$messages=array('batch'=>'Серијата е копирана и проверена. Повторете додека не се прикаже дека нема преостанати фајлови.','complete'=>'Нема преостанати непреместени фајлови. Оригиналите се зачувани.','error'=>'Серијата не беше завршена; оригиналната копија е зачувана. Проверете ја Drive врската и записите.');if(isset($messages[$st]))$html.='<div class="notice notice-'.($st==='error'?'error':'success').'"><p>'.esc_html($messages[$st]).'</p></div>';}
        return $html;
    }
    private static function render_matrix_picker_admin($settings) {
        if(!current_user_can('manage_options'))return '';$key=(string)($settings['picker_api_key']??'');$app=(string)($settings['picker_app_id']??'');if($key===''||$app===''||empty($settings['refresh_token_enc']))return '<hr><section class="zmp-drive-picker-setup"><h2>Каталог на матрици од постојната Drive-папка</h2><p>За најбезбеден пристап, администраторот избира постојни аудио-фајлови преку Google Picker. Плагинот не скенира или презема пристап до целата Drive-сметка. Внесете Picker API key и Cloud project number погоре и овозможете Google Picker API во Cloud.</p><p>Ограничете го API key на доменот на веб-сајтот, https://docs.google.com/* и само на Google Picker API. Користете го истиот Cloud проект како и Drive OAuth. Фајловите што ќе ги изберете ќе бидат достапни во наставничкиот пребарувач.</p></section>';
        $nonce=wp_create_nonce('zmp_matrix_picker_admin');$ajax=admin_url('admin-ajax.php');$api=wp_json_encode($ajax);$nn=wp_json_encode($nonce);$mimes=wp_json_encode(implode(',',array('audio/mpeg','audio/mp3','audio/wav','audio/x-wav','audio/wave','audio/ogg','audio/mp4','audio/aac','audio/flac','audio/webm')));
        return '<hr><section class="zmp-drive-picker-setup"><h2>Каталог на матрици од Drive</h2><p>Изберете до 10 постојни матрици во еден циклус со Google Picker. Пристап се дава само до избраните фајлови. За повеќе фајлови, повторете го увозот.</p><button type="button" class="button button-primary" id="zmp-matrix-admin-import">Освежи каталог од Drive</button><p id="zmp-matrix-admin-status" role="status" aria-live="polite"></p></section><script>(function(){var btn=document.getElementById("zmp-matrix-admin-import"),status=document.getElementById("zmp-matrix-admin-status"),ajax='.$api.',nonce='.$nn.',mimes='.$mimes.',ready=false;if(!btn)return;function loadPicker(cb){if(window.gapi&&gapi.load){gapi.load("picker",cb);return;}status.textContent="Google Picker не се вчита. Проверете дали е овозможен во Google Cloud.";}btn.addEventListener("click",function(){btn.disabled=true;status.textContent="Се подготвува безбеден избор на Drive-файлови...";var d=new FormData();d.append("action","zmp_matrix_picker_config");d.append("_ajax_nonce",nonce);fetch(ajax,{method:"POST",credentials:"same-origin",body:d}).then(function(r){return r.json();}).then(function(x){if(!x.success)throw new Error(x.data&&x.data.message||"Picker поставките не се подготвени.");var c=x.data;loadPicker(function(){var view=new google.picker.DocsView(google.picker.ViewId.DOCS).setParent(c.folderId).setIncludeFolders(false).setMimeTypes(mimes).setMode(google.picker.DocsViewMode.LIST);var picker=new google.picker.PickerBuilder().setDeveloperKey(c.apiKey).setAppId(c.appId).setOAuthToken(c.accessToken).setMaxItems(10).enableFeature(google.picker.Feature.MULTISELECT_ENABLED).addView(view).setCallback(function(data){if(data.action!==google.picker.Action.PICKED){btn.disabled=false;status.textContent="Изборот е откажан.";return;}var docs=data[google.picker.Response.DOCUMENTS]||[],ids=docs.map(function(x){return x[google.picker.Document.ID];}).filter(Boolean);var f=new FormData();f.append("action","zmp_matrix_import_drive");f.append("_ajax_nonce",nonce);f.append("file_ids",JSON.stringify(ids));status.textContent="Се проверуваат избраните фајлови...";fetch(ajax,{method:"POST",credentials:"same-origin",body:f}).then(function(r){return r.json();}).then(function(result){if(!result.success)throw new Error(result.data&&result.data.message||"Не успеа увозот.");status.textContent=result.data.message;}).catch(function(e){status.textContent=e.message;}).finally(function(){btn.disabled=false;});}).build();picker.setVisible(true);});}).catch(function(e){status.textContent=e.message;btn.disabled=false;});});})();</script>';
    }
    public static function save_folder_settings() {
        self::require_admin();
        check_admin_referer('zmp_save_drive_folders');
        $incoming=isset($_POST['folder_ids'])&&is_array($_POST['folder_ids'])?wp_unslash($_POST['folder_ids']):array();
        $status=self::persist_folder_settings($incoming);
        self::redirect_folder_settings(is_wp_error($status)?'invalid':$status);
    }

    /** Validate, store, and read back the six folder IDs before reporting success. */
    private static function persist_folder_settings($incoming) {
        $s=self::settings();
        $labels=self::folder_labels();$clean=array();
        foreach($labels as $key=>$label){
            if(!array_key_exists($key,$incoming)||!is_scalar($incoming[$key]))return new WP_Error('folder_settings_incomplete');
            $value=sanitize_text_field((string)$incoming[$key]);
            if($value!==''&&!preg_match('/^[A-Za-z0-9_-]{10,190}$/',$value))return new WP_Error('folder_settings_invalid');
            $clean[$key]=$value;
        }
        $s['folder_ids']=$clean;
        update_option(self::OPTION,$s,false);
        $saved=self::settings();
        if(!isset($saved['folder_ids'])||!is_array($saved['folder_ids']))return new WP_Error('folder_settings_not_persisted');
        foreach($clean as $key=>$value){if(!array_key_exists($key,$saved['folder_ids'])||(string)$saved['folder_ids'][$key]!==$value)return new WP_Error('folder_settings_not_persisted');}
        if(!empty($s['refresh_token_enc'])){
            foreach($clean as $id){if($id!==''&&!self::verify_folder_access($id))return 'saved_unverified';}
        }
        return 'saved';
    }
    private static function folder_migration_summary() {
        global $wpdb;$prefix=$wpdb->prefix;$summary=array('Пријавни документи'=>0,'Документи за тестот на знаење'=>0,'Годишни формулари'=>0,'Списоци на групи'=>0,'Матрици'=>0);
        $summary['Пријавни документи']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}zmp_entry_documents WHERE storage_backend='drive' AND drive_file_id<>''");
        $summary['Документи за тестот на знаење']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}zmp_student_documents WHERE storage_backend='drive' AND drive_file_id<>''");
        $summary['Годишни формулари']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}zmp_registration_forms WHERE storage_backend='drive' AND drive_file_id<>''");
        $summary['Списоци на групи']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}zmp_category_entries WHERE roster_storage_backend='drive' AND roster_drive_file_id<>''");
        $summary['Матрици']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}zmp_matrices WHERE status='active' AND drive_file_id<>''");return $summary;
    }
    private static function migration_candidates($limit=5) {
        global $wpdb;$p=$wpdb->prefix;$items=array();$knowledge=(int)$wpdb->get_var("SELECT id FROM {$p}zmp_categories WHERE category_code='knowledge_test' LIMIT 1");$settings=self::settings();$signature=function($purpose)use($settings){$key=self::purpose_folder_key($purpose);$folder=(string)($settings['folder_ids'][$key]??'');return $folder===''?'':hash('sha256',$key.'|'.$folder);};
        $sources=array(
            array('type'=>'entry_document','table'=>'zmp_entry_documents','query'=>"SELECT d.id,d.drive_file_id,d.sha256,d.season_id,e.category_id,d.document_type AS purpose,d.original_name,d.mime_type,d.file_size FROM {$p}zmp_entry_documents d JOIN {$p}zmp_category_entries e ON e.id=d.entry_id WHERE d.storage_backend='drive' AND d.drive_file_id<>'' ORDER BY d.id"),
            array('type'=>'student_document','table'=>'zmp_student_documents','query'=>"SELECT id,drive_file_id,sha256,season_id,{$knowledge} AS category_id,document_type AS purpose,original_name,mime_type,file_size FROM {$p}zmp_student_documents WHERE storage_backend='drive' AND drive_file_id<>'' ORDER BY id"),
            array('type'=>'registration_form','table'=>'zmp_registration_forms','query'=>"SELECT id,drive_file_id,sha256,season_id,{$knowledge} AS category_id,'annual-form' AS purpose,original_name,mime_type,file_size FROM {$p}zmp_registration_forms WHERE storage_backend='drive' AND drive_file_id<>'' ORDER BY id"),
            array('type'=>'roster','table'=>'zmp_category_entries','query'=>"SELECT id,roster_drive_file_id AS drive_file_id,roster_sha256 AS sha256,season_id,category_id,'student-rosters' AS purpose,roster_original_name AS original_name,'application/octet-stream' AS mime_type,0 AS file_size FROM {$p}zmp_category_entries WHERE roster_storage_backend='drive' AND roster_drive_file_id<>'' ORDER BY id"),
            array('type'=>'matrix','table'=>'zmp_matrices','query'=>"SELECT id,drive_file_id,sha256,0 AS season_id,category_id,'matrix' AS purpose,title AS original_name,mime_type,file_size FROM {$p}zmp_matrices WHERE status='active' AND drive_file_id<>'' ORDER BY id"),
        );
        foreach($sources as $src){$rows=$wpdb->get_results($src['query']);foreach((array)$rows as $row){if(count($items)>=$limit)return $items;$sig=$signature($row->purpose);if($sig==='')continue;$done=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}zmp_drive_migration_audit WHERE entity_type=%s AND entity_id=%d AND new_file_id=%s AND target_signature=%s LIMIT 1",$src['type'],$row->id,$row->drive_file_id,$sig));if($done)continue;$row->entity_type=$src['type'];$row->table_name=$src['table'];$row->target_signature=$sig;$items[]=$row;}}
        return $items;
    }
    public static function migrate_folder_batch() {
        self::require_admin();check_admin_referer('zmp_migrate_drive_folders');if(!isset($_POST['confirmation'])||!is_scalar($_POST['confirmation'])||sanitize_text_field(wp_unslash($_POST['confirmation']))!=='КОПИРАЈ И ПРОВЕРИ')self::redirect_folder_admin('error');
        $settings=self::settings();if(empty($settings['refresh_token_enc'])||empty($settings['root_folder_id']))self::redirect_folder_admin('error');$items=self::migration_candidates(5);if(!$items)self::redirect_folder_admin('complete');global $wpdb;$done=0;
        foreach($items as $item){$old=(string)$item->drive_file_id;$source=self::download_private_file_to_temp($old);if(is_wp_error($source))self::redirect_folder_admin('error');$hash=is_file($source)?hash_file('sha256',$source):false;if(!$hash||!hash_equals((string)$item->sha256,(string)$hash)){@unlink($source);self::redirect_folder_admin('error');}
            $migration_mime=sanitize_mime_type($item->mime_type);if($migration_mime==='application/octet-stream'&&function_exists('mime_content_type'))$migration_mime=sanitize_mime_type(mime_content_type($source));$target=self::upload_private_document($source,(int)filesize($source),$migration_mime,(string)$item->original_name,(int)$item->season_id,(int)$item->category_id,(string)$item->purpose);@unlink($source);if(is_wp_error($target))self::redirect_folder_admin('error');$verify=self::download_private_file_to_temp($target);$verified=!is_wp_error($verify)&&is_file($verify)&&hash_equals((string)$item->sha256,(string)hash_file('sha256',$verify));if(is_string($verify)&&is_file($verify))@unlink($verify);if(!$verified){self::delete_private_file($target);self::redirect_folder_admin('error');}
            $where=array('id'=>(int)$item->id);$column=$item->entity_type==='roster'?'roster_drive_file_id':'drive_file_id';$wpdb->query('START TRANSACTION');$audit=$wpdb->insert($wpdb->prefix.'zmp_drive_migration_audit',array('entity_type'=>$item->entity_type,'entity_id'=>(int)$item->id,'old_file_id'=>$old,'new_file_id'=>$target,'sha256'=>$hash,'target_signature'=>$item->target_signature,'migrated_by'=>get_current_user_id(),'migrated_at'=>current_time('mysql')));$updated=$audit?$wpdb->update($wpdb->prefix.$item->table_name,array($column=>$target),$where):false;if(!$audit||$updated===false){$wpdb->query('ROLLBACK');self::delete_private_file($target);self::redirect_folder_admin('error');}$wpdb->query('COMMIT');$done++;
        }
        self::redirect_folder_admin(self::migration_candidates(1)?'batch':'complete');
    }

    private static function verify_folder_access($id) {
        $token=self::access_token();if(is_wp_error($token))return false;$url='https://www.googleapis.com/drive/v3/files/'.rawurlencode($id).'?'.http_build_query(array('fields'=>'id,mimeType,trashed'),'','&',PHP_QUERY_RFC3986);$r=wp_remote_get($url,array('timeout'=>20,'headers'=>array('Authorization'=>'Bearer '.$token)));if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return false;$d=json_decode(wp_remote_retrieve_body($r),true);return !empty($d['id'])&&($d['mimeType']??'')==='application/vnd.google-apps.folder'&&empty($d['trashed']);
    }

    private static function render_completed_seasons() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT s.id,s.academic_year,s.title,COUNT(m.id) AS video_count FROM {$wpdb->prefix}zmp_seasons s JOIN {$wpdb->prefix}zmp_competitions c ON c.id=s.legacy_competition_id LEFT JOIN {$wpdb->prefix}zmp_category_media m ON m.season_id=s.id AND m.status='active' WHERE c.workflow_status='completed' GROUP BY s.id ORDER BY s.academic_year DESC");
        if (!$rows) { return '<p>Нема завршени години со видеоархива.</p>'; }
        ob_start();
        foreach ($rows as $season) {
            if ((int)$season->video_count < 1) { continue; }
            echo '<section class="card"><h3>'.esc_html($season->title ?: $season->academic_year).'</h3><p>'.(int)$season->video_count.' активни видеа</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(\'Ова трајно ќе ги избрише видеата од Google Drive. Продолжи?\');">';
            wp_nonce_field('zmp_purge_category_videos_'.$season->id);
            echo '<input type="hidden" name="action" value="zmp_purge_category_videos"><input type="hidden" name="season_id" value="'.(int)$season->id.'"><label>Потврда: <input name="confirmation" required autocomplete="off" placeholder="ИЗБРИШИ ВИДЕА"></label> <button class="button button-secondary">Избриши ги видеата од Drive</button></form></section>';
        }
        return ob_get_clean();
    }

    public static function save_settings() {
        self::require_admin();
        check_admin_referer('zmp_save_drive_settings');
        $old = self::settings();
        $client_id = sanitize_text_field(wp_unslash($_POST['client_id'] ?? ''));
        if ($client_id === '' || strlen($client_id) > 255) { self::redirect_admin('invalid_settings'); }
        $raw_secret = trim((string)wp_unslash($_POST['client_secret'] ?? ''));
        $encrypted = $old['client_secret_enc'] ?? '';
        if ($raw_secret !== '') {
            $encrypted = self::encrypt($raw_secret);
            if (!$encrypted) { self::redirect_admin('encryption_unavailable'); }
        }
        if (!$encrypted) { self::redirect_admin('secret_required'); }
        $old['client_id'] = $client_id;
        $old['client_secret_enc'] = $encrypted;
        $picker_key=sanitize_text_field(wp_unslash($_POST['picker_api_key']??($old['picker_api_key']??''))); $picker_app=sanitize_text_field(wp_unslash($_POST['picker_app_id']??($old['picker_app_id']??'')));
        if(strlen($picker_key)>256 || ($picker_app!=='' && !preg_match('/^[0-9]{6,24}$/',$picker_app)))self::redirect_admin('invalid_settings');
        $old['picker_api_key']=$picker_key; $old['picker_app_id']=$picker_app;
        update_option(self::OPTION, $old, false);
        self::redirect_admin('settings_saved');
    }

    public static function connect() {
        self::require_admin();
        check_admin_referer('zmp_drive_connect');
        $s = self::settings();
        if (empty($s['client_id']) || empty($s['client_secret_enc'])) { self::redirect_admin('secret_required'); }
        $state = bin2hex(random_bytes(32));
        set_transient('zmp_drive_oauth_'.hash('sha256',$state), array('user_id'=>get_current_user_id(),'nonce'=>wp_create_nonce('zmp_drive_oauth_user_'.get_current_user_id())), 10 * MINUTE_IN_SECONDS);
        $query = array('client_id'=>$s['client_id'],'redirect_uri'=>self::redirect_uri(),'response_type'=>'code','scope'=>'https://www.googleapis.com/auth/drive.file','access_type'=>'offline','prompt'=>'consent','state'=>$state);
        wp_redirect('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($query,'','&',PHP_QUERY_RFC3986));
        exit;
    }

    public static function callback() {
        self::require_admin();
        $state = isset($_GET['state']) && is_scalar($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $key = 'zmp_drive_oauth_'.hash('sha256',$state);
        $oauth_state = $state !== '' ? get_transient($key) : false;
        delete_transient($key);
        $user_id = is_array($oauth_state) ? absint($oauth_state['user_id'] ?? 0) : 0;
        if (!$user_id || $user_id !== get_current_user_id() || !wp_verify_nonce((string)($oauth_state['nonce'] ?? ''), 'zmp_drive_oauth_user_'.$user_id)) { self::redirect_admin('oauth_state_invalid'); }
        if (!empty($_GET['error'])) { self::redirect_admin('oauth_denied'); }
        $code = isset($_GET['code']) && is_scalar($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        if ($code === '') { self::redirect_admin('oauth_code_missing'); }
        $s = self::settings(); $secret = self::decrypt($s['client_secret_enc'] ?? '');
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array('timeout'=>30,'body'=>array('code'=>$code,'client_id'=>$s['client_id'],'client_secret'=>$secret,'redirect_uri'=>self::redirect_uri(),'grant_type'=>'authorization_code')));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) { self::redirect_admin('oauth_exchange_failed'); }
        $tokens = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($tokens['access_token'])) { self::redirect_admin('oauth_exchange_failed'); }
        $access = (string)$tokens['access_token'];
        if (!empty($tokens['refresh_token'])) { $s['refresh_token_enc'] = self::encrypt((string)$tokens['refresh_token']); }
        if (empty($s['refresh_token_enc'])) { self::redirect_admin('refresh_token_missing'); }
        update_option(self::OPTION, $s, false);
        $root = self::ensure_folder($access, 'ZMP Натпревари', 'root');
        if (is_wp_error($root)) { self::redirect_admin('drive_folder_failed'); }
        $s['root_folder_id'] = $root;
        update_option(self::OPTION, $s, false);
        self::redirect_admin('connected');
    }

    public static function disconnect() {
        self::require_admin(); check_admin_referer('zmp_drive_disconnect');
        $s = self::settings(); $token = self::decrypt($s['refresh_token_enc'] ?? '');
        if ($token) { wp_remote_post('https://oauth2.googleapis.com/revoke', array('timeout'=>10,'body'=>array('token'=>$token))); }
        unset($s['refresh_token_enc'],$s['root_folder_id']); update_option(self::OPTION,$s,false);
        self::redirect_admin('disconnected');
    }

    public static function migrate_legacy_storage() {
        self::require_admin(); check_admin_referer('zmp_migrate_legacy_storage');
        $settings=self::settings();
        if(empty($settings['refresh_token_enc'])||empty($settings['root_folder_id'])) self::redirect_admin('migration_drive_not_connected');
        $limit=10; $docs=ZMP_Registration_Requirements::migrate_legacy_files($limit); $used=(int)$docs['moved'];
        $left=max(0,$limit-$used); $rosters=$left?ZMP_Category_Registrations::migrate_legacy_rosters($left):array('moved'=>0,'failed'=>0,'remaining'=>0); $used+=(int)$rosters['moved'];
        $left=max(0,$limit-$used); $archives=$left?ZMP_Governance::migrate_legacy_archives($left):array('moved'=>0,'failed'=>0,'remaining'=>0);
        $parts=array($docs,$rosters,$archives); $remaining=0; $unavailable=false;
        foreach($parts as $part){$part_remaining=(int)($part['remaining']??-1);if($part_remaining<0){$unavailable=true;continue;}$remaining+=max(0,$part_remaining-(int)($part['moved']??0));}
        $failed=(int)$docs['failed']+(int)$rosters['failed']+(int)$archives['failed'];
        self::redirect_admin(($unavailable||$failed)?'migration_attention':($remaining===0?'migration_complete':'migration_batch'));
    }

    /** Upload a private registration document into a year/category folder without storing it permanently on this host. */
    public static function upload_private_document($path, $size, $mime, $original_name, $season_id, $category_id, $purpose) {
        global $wpdb;
        if (!is_file($path) || !is_readable($path) || (int)$size < 1) { return new WP_Error('drive_upload_read'); }
        $token = self::access_token();
        if (is_wp_error($token)) { return $token; }
        $settings = self::settings();
        $root = (string)($settings['root_folder_id'] ?? '');
        if ($root === '') { return new WP_Error('drive_not_connected'); }
        $purpose_key=self::purpose_folder_key($purpose);
        $configured=(string)($settings['folder_ids'][$purpose_key]??'');
        if($purpose==='matrix'){
            $matrix_root=$configured!==''?$configured:self::ensure_folder($token,'Matrices',$root);
            if(is_wp_error($matrix_root))return $matrix_root;
            $filename='zmp-matrix-'.bin2hex(random_bytes(16)).'.'.strtolower(pathinfo((string)$original_name,PATHINFO_EXTENSION));
            return self::upload_blob_to_folder($path,(int)$size,sanitize_mime_type($mime),$filename,$matrix_root,'Private ZMP backing track.');
        }
        $season = $wpdb->get_row($wpdb->prepare("SELECT academic_year FROM {$wpdb->prefix}zmp_seasons WHERE id=%d", absint($season_id)));
        $category = $wpdb->get_row($wpdb->prepare("SELECT category_code,name_mk FROM {$wpdb->prefix}zmp_categories WHERE id=%d", absint($category_id)));
        if (!$season || !$category) { return new WP_Error('drive_scope_invalid'); }
        $parent=$configured!==''?$configured:$root;
        $year_folder = self::ensure_folder($token, sanitize_file_name((string)$season->academic_year), $parent);
        if (is_wp_error($year_folder)) { return $year_folder; }
        $category_folder = self::ensure_folder($token, sanitize_file_name((string)$category->category_code), $year_folder);
        if (is_wp_error($category_folder)) { return $category_folder; }
        $documents_folder = self::ensure_folder($token, 'registration-documents', $category_folder);
        if (is_wp_error($documents_folder)) { return $documents_folder; }
        $purpose_folder = self::ensure_folder($token, sanitize_key($purpose), $documents_folder);
        if (is_wp_error($purpose_folder)) { return $purpose_folder; }
        $extension = strtolower(pathinfo((string)$original_name, PATHINFO_EXTENSION));
        $filename = 'zmp-document-'.bin2hex(random_bytes(16)).($extension ? '.'.$extension : '');
        return self::upload_blob_to_folder($path, (int)$size, sanitize_mime_type($mime), $filename, $purpose_folder, 'Private ZMP registration document.');
    }

    /** Fetch a Drive file into a short-lived host temp file; callers must delete it in a finally block. */
    public static function download_private_file_to_temp($file_id) {
        $file_id = sanitize_text_field((string)$file_id);
        if ($file_id === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $file_id)) { return new WP_Error('drive_file_id_invalid'); }
        $token = self::access_token();
        if (is_wp_error($token)) { return $token; }
        $tmp = self::private_temp_file('zmp-drive-file');
        if (!$tmp) { return new WP_Error('drive_temp_unavailable'); }
        $response = wp_remote_get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($file_id).'?alt=media', array(
            'timeout'=>120, 'stream'=>true, 'filename'=>$tmp,
            'headers'=>array('Authorization'=>'Bearer '.$token),
        ));
        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200 || !is_file($tmp)) {
            @unlink($tmp);
            return new WP_Error('drive_download_failed');
        }
        return $tmp;
    }

    public static function delete_private_file($file_id) { return self::delete_drive_file($file_id); }

    public static function upload_private_archive($path, $size, $competition_id, $academic_year) {
        if (!is_file($path) || !is_readable($path) || (int)$size < 1) return new WP_Error('drive_upload_read');
        $token=self::access_token(); if(is_wp_error($token))return $token;
        $root=(string)(self::settings()['root_folder_id']??''); if($root==='')return new WP_Error('drive_not_connected');
        $year=self::ensure_folder($token,sanitize_file_name((string)$academic_year),$root);if(is_wp_error($year))return $year;
        $archives=self::ensure_folder($token,'competition-archives',$year);if(is_wp_error($archives))return $archives;
        $name='zmp-competition-'.absint($competition_id).'-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.zip';
        return self::upload_blob_to_folder($path,(int)$size,'application/zip',$name,$archives,'Private ZMP competition archive.');
    }

    /** Shared resumable upload for small registration files and large videos. */
    private static function upload_blob_to_folder($path, $size, $mime, $filename, $folder_id, $description) {
        $token = self::access_token();
        if (is_wp_error($token)) { return $token; }
        $metadata = array('name'=>$filename, 'mimeType'=>$mime, 'parents'=>array($folder_id), 'description'=>$description);
        $init = wp_remote_post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true&fields=id', array(
            'timeout'=>30,
            'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json; charset=UTF-8','X-Upload-Content-Type'=>$mime,'X-Upload-Content-Length'=>(string)$size),
            'body'=>wp_json_encode($metadata),
        ));
        if (is_wp_error($init)) { return new WP_Error('drive_upload_init'); }
        $code = (int)wp_remote_retrieve_response_code($init);
        $url = wp_remote_retrieve_header($init, 'location');
        if ($code !== 200 || !$url) { return new WP_Error('drive_upload_init'); }
        $fh = fopen($path, 'rb');
        if (!$fh) { return new WP_Error('drive_upload_read'); }
        $offset = 0; $id = '';
        while ($offset < $size) {
            $length = min(self::CHUNK_BYTES, $size - $offset); $chunk = '';
            while (strlen($chunk) < $length && !feof($fh)) {
                $piece = fread($fh, min(1048576, $length - strlen($chunk)));
                if ($piece === false || $piece === '') { break; }
                $chunk .= $piece;
            }
            if (strlen($chunk) !== $length) { fclose($fh); return new WP_Error('drive_upload_read'); }
            $end = $offset + $length - 1;
            $response = wp_remote_request($url, array('method'=>'PUT','timeout'=>120,'headers'=>array('Content-Length'=>(string)$length,'Content-Type'=>$mime,'Content-Range'=>'bytes '.$offset.'-'.$end.'/'.$size),'body'=>$chunk));
            if (is_wp_error($response)) { fclose($fh); return new WP_Error('drive_upload_transfer'); }
            $code = (int)wp_remote_retrieve_response_code($response);
            if ($code === 200 || $code === 201) {
                $data = json_decode(wp_remote_retrieve_body($response), true); $id = sanitize_text_field($data['id'] ?? '');
                if (!$id) { fclose($fh); return new WP_Error('drive_upload_response'); }
                $offset = $size; break;
            }
            if ($code !== 308) { fclose($fh); return new WP_Error('drive_upload_transfer'); }
            $offset += $length;
        }
        fclose($fh);
        return $id ?: new WP_Error('drive_upload_incomplete');
    }

    /** Create a short-lived private temp file outside the public WordPress tree. */
    private static function private_temp_file($prefix) {
        if (!function_exists('wp_tempnam')) { require_once ABSPATH.'wp-admin/includes/file.php'; }
        if (!function_exists('wp_tempnam')) { return false; }
        $path=wp_tempnam(sanitize_file_name($prefix));
        if (!$path || !is_file($path)) { return false; }
        $root=realpath(ABSPATH); $temp_dir=realpath(dirname($path));
        $root=$root?rtrim(str_replace('\\','/',$root),'/'):''; $temp_dir=$temp_dir?str_replace('\\','/',$temp_dir):'';
        if (!$root || !$temp_dir || $temp_dir===$root || strpos($temp_dir,$root.'/')===0) { @unlink($path); return false; }
        @chmod($path,0600);
        return $path;
    }

    public static function upload_video() {
        if (!is_user_logged_in()) { auth_redirect(); }
        global $wpdb;
        $entry_id = absint($_POST['entry_id'] ?? 0);
        check_admin_referer('zmp_video_upload_'.$entry_id);
        $entry = $wpdb->get_row($wpdb->prepare("SELECT e.*,c.category_code,c.name_mk AS category_name,s.academic_year FROM {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_categories c ON c.id=e.category_id JOIN {$wpdb->prefix}zmp_seasons s ON s.id=e.season_id WHERE e.id=%d",$entry_id));
        if (!$entry || !self::can_upload_entry($entry)) { wp_die('Немате пристап до оваа пријава.', '', array('response'=>403)); }
        $phase = sanitize_key(wp_unslash($_POST['phase'] ?? ''));
        if (!in_array($phase, array('municipal','regional','state'), true) || !self::phase_enabled($entry->season_id,$entry->category_id,$phase)) { self::redirect_front('video_phase_invalid'); }
        if (isset($_FILES['zmp_video_file']['error']) && (int)$_FILES['zmp_video_file']['error'] === UPLOAD_ERR_INI_SIZE) { self::redirect_front('video_too_large'); }
        $f = $_FILES['zmp_video_file'] ?? array();
        if (!is_array($f) || (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) { self::redirect_front('video_invalid'); }
        $size = (int)($f['size'] ?? 0); $mime = self::video_mime($f['tmp_name'], $f['name'] ?? '');
        if ($size < 1 || $size > self::MAX_VIDEO_BYTES || !$mime) { self::redirect_front($size > self::MAX_VIDEO_BYTES ? 'video_too_large' : 'video_invalid'); }
        $sha = hash_file('sha256',$f['tmp_name']);
        if(class_exists('ZMP_Debug'))ZMP_Debug::log('info','Google Drive video upload started.',array('entry_id'=>$entry_id,'phase'=>$phase,'file_size'=>$size),'google_drive');
        $drive_id = self::upload_to_drive($f['tmp_name'], $size, $mime, wp_unslash($f['name'] ?? 'video.mp4'), $entry, $phase);
        if (is_wp_error($drive_id)) { if(class_exists('ZMP_Debug'))ZMP_Debug::log('error','Google Drive video upload failed.',array('error_code'=>$drive_id->get_error_code(),'phase'=>$phase,'file_size'=>$size),'google_drive'); error_log('ZMP Drive video upload failed: '. $drive_id->get_error_code()); self::redirect_front('drive_upload_failed'); }
        $extension = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
        $safe_name = 'video-entry-'.$entry_id.'-'.$phase.($extension ? '.'.$extension : '');
        $ok = $wpdb->insert($wpdb->prefix.'zmp_category_media',array('season_id'=>(int)$entry->season_id,'category_id'=>(int)$entry->category_id,'entry_id'=>$entry_id,'phase'=>$phase,'drive_file_id'=>$drive_id,'original_filename'=>$safe_name,'mime_type'=>$mime,'file_size'=>$size,'sha256'=>$sha,'uploaded_by'=>get_current_user_id(),'status'=>'active','created_at'=>current_time('mysql')));
if (!$ok) { if(class_exists('ZMP_Debug'))ZMP_Debug::log('error','Google Drive upload completed but media record could not be saved.',array('entry_id'=>$entry_id,'phase'=>$phase,'file_size'=>$size),'google_drive'); self::delete_drive_file($drive_id); self::redirect_front('video_database_failed'); }
        $wpdb->insert($wpdb->prefix.'zmp_operations_audit',array('season_id'=>(int)$entry->season_id,'category_id'=>(int)$entry->category_id,'phase'=>$phase,'actor_user_id'=>get_current_user_id(),'action'=>'drive_video_uploaded','object_type'=>'video','object_id'=>(int)$wpdb->insert_id,'details'=>wp_json_encode(array('entry_id'=>$entry_id,'file_size'=>$size,'sha256'=>$sha)),'created_at'=>current_time('mysql')));
        if(class_exists('ZMP_Debug'))ZMP_Debug::log('info','Google Drive video upload and media record completed.',array('entry_id'=>$entry_id,'phase'=>$phase,'file_size'=>$size,'media_id'=>(int)$wpdb->insert_id),'google_drive');
        self::redirect_front('video_uploaded');
    }

    public static function stream_video() {
        if (!is_user_logged_in()) { auth_redirect(); }
        $id = absint($_GET['media_id'] ?? 0); check_admin_referer('zmp_stream_video_'.$id);
        global $wpdb;
  $media = $wpdb->get_row($wpdb->prepare("SELECT m.*,e.wp_user_id,e.season_id,e.category_id FROM {$wpdb->prefix}zmp_category_media m JOIN {$wpdb->prefix}zmp_category_entries e ON e.id=m.entry_id WHERE m.id=%d AND m.status='active'",$id));
        if (!$media || !self::can_view_media($media)) { wp_die('Немате пристап до видеото.', '', array('response'=>403)); }
        $token = self::access_token(); if (is_wp_error($token)) { wp_die('Google Drive не е достапен.', '', array('response'=>503)); }
        $tmp = self::private_temp_file('zmp-video'); if (!$tmp) { wp_die('Не може безбедно да се подготви приватниот видео-преглед.', '', array('response'=>500)); }
        $headers = array('Authorization'=>'Bearer '.$token);
        if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=\d*-\d*$/',$_SERVER['HTTP_RANGE'])) { $headers['Range']=sanitize_text_field(wp_unslash($_SERVER['HTTP_RANGE'])); }
        $response = wp_remote_get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($media->drive_file_id).'?alt=media',array('timeout'=>90,'stream'=>true,'filename'=>$tmp,'headers'=>$headers));
        if (is_wp_error($response)) { @unlink($tmp); wp_die('Не може да се преземе приватното видео.', '', array('response'=>502)); }
        $code = (int)wp_remote_retrieve_response_code($response);
        if (!in_array($code,array(200,206),true) || !is_file($tmp)) { @unlink($tmp); wp_die('Google Drive одби пристап до видеото.', '', array('response'=>$code===404?404:502)); }
        nocache_headers(); header('Content-Type: '.sanitize_mime_type($media->mime_type)); header('Accept-Ranges: bytes'); header('X-Content-Type-Options: nosniff');
        if ($code===206) { $range=wp_remote_retrieve_header($response,'content-range'); if($range)header('Content-Range: '.$range); status_header(206); }
        header('Content-Length: '.filesize($tmp)); readfile($tmp); @unlink($tmp); exit;
    }

    public static function purge_season_videos() {
        self::require_admin();
        $season_id=absint($_POST['season_id']??0);check_admin_referer('zmp_purge_category_videos_'.$season_id);
        if(sanitize_text_field(wp_unslash($_POST['confirmation']??''))!=='ИЗБРИШИ ВИДЕА')self::redirect_admin('confirmation_invalid');
        global $wpdb;
        $season=$wpdb->get_row($wpdb->prepare("SELECT id,status,legacy_competition_id FROM {$wpdb->prefix}zmp_seasons WHERE id=%d",$season_id));
        $competition=$season?$wpdb->get_row($wpdb->prepare("SELECT workflow_status FROM {$wpdb->prefix}zmp_competitions WHERE id=%d",$season->legacy_competition_id)):null;
        if(!$season||!$competition||$competition->workflow_status!=='completed')self::redirect_admin('season_not_completed');
        $rows=$wpdb->get_results($wpdb->prepare("SELECT id,drive_file_id FROM {$wpdb->prefix}zmp_category_media WHERE season_id=%d AND status='active'",$season_id));
foreach($rows as $row){$result=self::delete_drive_file($row->drive_file_id);if(is_wp_error($result))continue;$wpdb->update($wpdb->prefix.'zmp_category_media',array('status'=>'deleted','deleted_at'=>current_time('mysql')),array('id'=>(int)$row->id),array('%s','%s'),array('%d'));$wpdb->insert($wpdb->prefix.'zmp_operations_audit',array('season_id'=>(int)$season_id,'category_id'=>0,'phase'=>'state','actor_user_id'=>get_current_user_id(),'action'=>'drive_video_deleted','object_type'=>'video','object_id'=>(int)$row->id,'details'=>wp_json_encode(array('drive_file_id'=>$row->drive_file_id)),'created_at'=>current_time('mysql')));}
        self::redirect_admin('purge_finished');
    }

    /** Delete every active Drive video before the administrator's irreversible full reset. */
    public static function delete_all_videos_for_reset() {
        if (!current_user_can('manage_options')) { return false; }
        global $wpdb;
        $rows=$wpdb->get_results("SELECT id,season_id,category_id,phase,drive_file_id FROM {$wpdb->prefix}zmp_category_media WHERE status='active' ORDER BY id");
        foreach((array)$rows as $row){
            $result=self::delete_drive_file($row->drive_file_id);
            if(is_wp_error($result))return false;
            $changed=$wpdb->update($wpdb->prefix.'zmp_category_media',array('status'=>'deleted','deleted_at'=>current_time('mysql')),array('id'=>(int)$row->id),array('%s','%s'),array('%d'));
            if($changed===false)return false;
            $wpdb->insert($wpdb->prefix.'zmp_operations_audit',array('season_id'=>(int)$row->season_id,'category_id'=>(int)$row->category_id,'phase'=>$row->phase,'actor_user_id'=>get_current_user_id(),'action'=>'drive_video_deleted_reset','object_type'=>'video','object_id'=>(int)$row->id,'details'=>wp_json_encode(array('drive_file_id'=>$row->drive_file_id)),'created_at'=>current_time('mysql')));
        }
        return true;
    }
    public static function video_link($entry_id, $phase) {
        $url = self::video_stream_url($entry_id, $phase);
        if (!$url) { return ''; }
        global $wpdb;
        $media=$wpdb->get_row($wpdb->prepare("SELECT m.id,m.original_filename FROM {$wpdb->prefix}zmp_category_media m WHERE m.entry_id=%d AND m.phase=%s AND m.status='active' ORDER BY m.id DESC LIMIT 1",absint($entry_id),sanitize_key($phase)));
        if(!$media)return '';
        return '<a href="'.esc_url($url).'" target="_blank" rel="noopener">Прегледај видео: '.esc_html($media->original_filename).'</a>';
    }

    /** Native video element uses the authenticated range-stream endpoint for seek/replay. */
    public static function video_player($entry_id, $phase) {
        $url = self::video_stream_url($entry_id, $phase); if (!$url) return '';
        global $wpdb;
        $media=$wpdb->get_row($wpdb->prepare("SELECT original_filename FROM {$wpdb->prefix}zmp_category_media WHERE entry_id=%d AND phase=%s AND status='active' ORDER BY id DESC LIMIT 1",absint($entry_id),sanitize_key($phase)));
        return '<div class="zmp-video-review"><p>'.esc_html($media ? $media->original_filename : 'Видео').'</p><video controls playsinline preload="metadata" style="width:100%;max-width:760px" src="'.esc_url($url).'">Вашиот прелистувач не поддржува видео. <a href="'.esc_url($url).'">Отворете го видеото</a>.</video></div>';
    }

    private static function video_stream_url($entry_id, $phase) {
        global $wpdb;
        $media=$wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_category_media WHERE entry_id=%d AND phase=%s AND status='active' ORDER BY id DESC LIMIT 1",absint($entry_id),sanitize_key($phase)));
        if(!$media)return '';
        return wp_nonce_url(admin_url('admin-post.php?action=zmp_stream_category_video&media_id='.(int)$media->id),'zmp_stream_video_'.(int)$media->id);
    }

    public static function can_view_media($media) {
        if (current_user_can('manage_options') || current_user_can('zmp_moderator_cap')) { return true; }
        if ((current_user_can('zmp_commission_cap') || current_user_can('view_zmp_commission_dashboard'))
            && !empty($media->season_id) && !empty($media->category_id) && !empty($media->entry_id) && !empty($media->phase)) {
            return class_exists('ZMP_Category_Jury') && ZMP_Category_Jury::can_access_entry_phase(
                (int)$media->season_id,(int)$media->category_id,(int)$media->entry_id,(string)$media->phase,get_current_user_id()
            );
        }
        if (current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard')) { return (int) $media->wp_user_id === get_current_user_id(); }
        return false;
    }

    private static function can_upload_entry($entry) {
        return current_user_can('manage_options') || ((current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard') || in_array('zmp_teacher',(array)wp_get_current_user()->roles,true)) && (int)$entry->wp_user_id===get_current_user_id() && in_array($entry->status,array('submitted','approved','complete'),true));
    }

    private static function phase_enabled($season_id,$category_id,$phase) {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_season_categories sc JOIN {$wpdb->prefix}zmp_season_stages st ON st.season_category_id=sc.id WHERE sc.season_id=%d AND sc.category_id=%d AND sc.is_enabled=1 AND st.phase=%s AND st.is_enabled=1",$season_id,$category_id,$phase)) > 0;
    }

    private static function video_mime($path,$name) {
        $ext=strtolower(pathinfo((string)$name,PATHINFO_EXTENSION));
        $allowed=array('mp4'=>'video/mp4','m4v'=>'video/mp4','mov'=>'video/quicktime','webm'=>'video/webm','avi'=>'video/x-msvideo','mpeg'=>'video/mpeg','mpg'=>'video/mpeg');
        if(!isset($allowed[$ext]))return '';
        $detected=function_exists('finfo_open')?finfo_file(finfo_open(FILEINFO_MIME_TYPE),$path):'';
        $aliases=array('application/octet-stream','application/mp4','video/mp4','video/quicktime','video/webm','video/x-msvideo','video/mpeg');
        if(!$detected||!in_array($detected,$aliases,true))return '';
        return $allowed[$ext];
    }

    private static function upload_to_drive($path,$size,$mime,$original_name,$entry,$phase) {
        $token=self::access_token();if(is_wp_error($token))return $token;
        $root=self::settings()['root_folder_id']??'';if(!$root)return new WP_Error('drive_not_connected');
        $season_folder=self::ensure_folder($token,(string)$entry->academic_year,$root);if(is_wp_error($season_folder))return $season_folder;
        $category_folder=self::ensure_folder($token,sanitize_file_name($entry->category_code.'-'.$entry->category_name),$season_folder);if(is_wp_error($category_folder))return $category_folder;
        $phase_folder=self::ensure_folder($token,$phase,$category_folder);if(is_wp_error($phase_folder))return $phase_folder;
        $filename='entry-'.(int)$entry->id.'-'.gmdate('Ymd-His').'-'.wp_generate_password(8,false,false).'.'.strtolower(pathinfo((string)$original_name,PATHINFO_EXTENSION));
        $metadata=array('name'=>$filename,'mimeType'=>$mime,'parents'=>array($phase_folder),'description'=>'ZMP category video. Entry ID '.(int)$entry->id.'.');
        $init=wp_remote_post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true',array('timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json; charset=UTF-8','X-Upload-Content-Type'=>$mime,'X-Upload-Content-Length'=>(string)$size),'body'=>wp_json_encode($metadata)));
        if(is_wp_error($init))return new WP_Error('drive_upload_init');$code=(int)wp_remote_retrieve_response_code($init);$url=wp_remote_retrieve_header($init,'location');if($code!==200||!$url)return new WP_Error('drive_upload_init');
        $fh=fopen($path,'rb');if(!$fh)return new WP_Error('drive_upload_read');$offset=0;$id='';
        while($offset<$size){$length=min(self::CHUNK_BYTES,$size-$offset);$chunk='';while(strlen($chunk)<$length&&!feof($fh)){$piece=fread($fh,min(1048576,$length-strlen($chunk)));if($piece===false||$piece===''){break;}$chunk.=$piece;}if(strlen($chunk)!==$length){fclose($fh);return new WP_Error('drive_upload_read');}
            $end=$offset+$length-1;$response=wp_remote_request($url,array('method'=>'PUT','timeout'=>120,'headers'=>array('Content-Length'=>(string)$length,'Content-Type'=>$mime,'Content-Range'=>'bytes '.$offset.'-'.$end.'/'.$size),'body'=>$chunk));
            if(is_wp_error($response)){fclose($fh);return new WP_Error('drive_upload_transfer');}$code=(int)wp_remote_retrieve_response_code($response);
            if($code===200||$code===201){$data=json_decode(wp_remote_retrieve_body($response),true);$id=sanitize_text_field($data['id']??'');if(!$id){fclose($fh);return new WP_Error('drive_upload_response');}$offset=$size;break;}
            if($code!==308){fclose($fh);return new WP_Error('drive_upload_transfer');}$offset+=$length;
        }
        fclose($fh);return $id?:new WP_Error('drive_upload_incomplete');
    }

    private static function ensure_folder($token,$name,$parent) {
        $query="name = '".str_replace("'","\\'",$name)."' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        $query .= " and '" . str_replace("'", "\\'", $parent) . "' in parents";
        $url='https://www.googleapis.com/drive/v3/files?'.http_build_query(array('q'=>$query,'fields'=>'files(id,name)','pageSize'=>1),'','&',PHP_QUERY_RFC3986);
        $res=wp_remote_get($url,array('timeout'=>20,'headers'=>array('Authorization'=>'Bearer '.$token)));
        if(is_wp_error($res)||wp_remote_retrieve_response_code($res)!==200)return new WP_Error('drive_folder_lookup');$data=json_decode(wp_remote_retrieve_body($res),true);if(!empty($data['files'][0]['id']))return sanitize_text_field($data['files'][0]['id']);
        $body=array('name'=>$name,'mimeType'=>'application/vnd.google-apps.folder');if($parent!=='root')$body['parents']=array($parent);
        $res=wp_remote_post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true&fields=id',array('timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'),'body'=>wp_json_encode($body)));
        if(is_wp_error($res)||!in_array(wp_remote_retrieve_response_code($res),array(200,201),true))return new WP_Error('drive_folder_create');$data=json_decode(wp_remote_retrieve_body($res),true);return !empty($data['id'])?sanitize_text_field($data['id']):new WP_Error('drive_folder_create');
    }

    private static function delete_drive_file($file_id) {
        $token=self::access_token();if(is_wp_error($token))return $token;$res=wp_remote_request('https://www.googleapis.com/drive/v3/files/'.rawurlencode($file_id).'?supportsAllDrives=true',array('method'=>'DELETE','timeout'=>30,'headers'=>array('Authorization'=>'Bearer '.$token)));
        if(is_wp_error($res)||!in_array(wp_remote_retrieve_response_code($res),array(200,204,404),true))return new WP_Error('drive_delete_failed');return true;
    }

    /** Read only a saved matrix folder ID; safe during schema installation with no network calls. */
    public static function configured_matrix_folder_id() {
        $s = self::settings();
        $folder = (string) ($s['folder_ids']['matrices'] ?? '');
        return preg_match('/^[A-Za-z0-9_-]{10,190}$/', $folder) ? $folder : '';
    }
    public static function matrix_folder_id() {
        $s=self::settings();$folder=(string)($s['folder_ids']['matrices']??'');if($folder!==''&&preg_match('/^[A-Za-z0-9_-]{10,190}$/',$folder))return $folder;
        $root=(string)($s['root_folder_id']??'');if($root===''||!preg_match('/^[A-Za-z0-9_-]{10,190}$/',$root))return '';$token=self::access_token();if(is_wp_error($token))return '';$created=self::ensure_folder($token,'Matrices',$root);if(is_wp_error($created)||!preg_match('/^[A-Za-z0-9_-]{10,190}$/',$created))return '';$s['folder_ids']['matrices']=$created;update_option(self::OPTION,$s,false);return $created;
    }    /** Return a short-lived drive.file token only to the trusted WordPress administrator who opens Picker. */
    public static function matrix_picker_admin_config() {if(!current_user_can('manage_options'))return new WP_Error('matrix_picker_admin_denied');$s=self::settings();$folder=self::matrix_folder_id();$token=self::access_token();if(empty($s['picker_api_key'])||empty($s['picker_app_id'])||$folder===''||is_wp_error($token))return new WP_Error('matrix_picker_not_ready');return array('apiKey'=>(string)$s['picker_api_key'],'appId'=>(string)$s['picker_app_id'],'folderId'=>$folder,'accessToken'=>$token);}
    /** Metadata lookup is used only after the administrator explicitly selected the Drive item in Picker. */
    public static function get_matrix_file_metadata($file_id) {$id=sanitize_text_field((string)$file_id);if(!preg_match('/^[A-Za-z0-9_-]{10,190}$/',$id))return new WP_Error('drive_file_id_invalid');$token=self::access_token();if(is_wp_error($token))return $token;$url='https://www.googleapis.com/drive/v3/files/'.rawurlencode($id).'?supportsAllDrives=true&fields=id,name,mimeType,size,md5Checksum,parents,trashed';$res=wp_remote_get($url,array('timeout'=>25,'headers'=>array('Authorization'=>'Bearer '.$token)));if(is_wp_error($res))return new WP_Error('drive_matrix_metadata_failed');if((int)wp_remote_retrieve_response_code($res)!==200)return new WP_Error('drive_matrix_metadata_denied');$data=json_decode(wp_remote_retrieve_body($res),true);if(!is_array($data)||empty($data['id']))return new WP_Error('drive_matrix_metadata_invalid');return $data;}
    private static function access_token() {
        $s=self::settings();$client=self::decrypt($s['client_secret_enc']??'');$refresh=self::decrypt($s['refresh_token_enc']??'');if(empty($s['client_id'])||!$client||!$refresh)return new WP_Error('drive_not_connected');
        $res=wp_remote_post('https://oauth2.googleapis.com/token',array('timeout'=>30,'body'=>array('client_id'=>$s['client_id'],'client_secret'=>$client,'refresh_token'=>$refresh,'grant_type'=>'refresh_token')));
        if(is_wp_error($res)||wp_remote_retrieve_response_code($res)!==200)return new WP_Error('drive_token_refresh');$body=json_decode(wp_remote_retrieve_body($res),true);return !empty($body['access_token'])?sanitize_text_field($body['access_token']):new WP_Error('drive_token_refresh');
    }

    private static function settings() {$s=get_option(self::OPTION,array());if(!is_array($s))$s=array();$defaults=array('sheet_music'=>'18AZcLOpY7ELpnayWiPlUknVP-HM4-JCS','matrices'=>'1WsNmETCCbhfyoXlLUDCzLyMLsnRTuwXJlS-yBuC4mgRRCCnBWZ3Y-gMZ-cAaJGdZSIpzNsDr');$s['folder_ids']=array_merge($defaults,is_array($s['folder_ids']??null)?$s['folder_ids']:array());return $s;}
    private static function encrypt($value) {
        if (!function_exists('openssl_encrypt')) { return ''; }
        $iv = random_bytes(12);
        $tag = '';
        $key = hash('sha256', wp_salt('auth') . wp_salt('secure_auth'), true);
        $cipher = openssl_encrypt((string) $value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $cipher !== false ? base64_encode($iv . $tag . $cipher) : '';
    }
    private static function decrypt($value) {
        if (!function_exists('openssl_decrypt')) { return ''; }
        $raw = base64_decode((string) $value, true);
        if ($raw === false || strlen($raw) < 29) { return ''; }
        $key = hash('sha256', wp_salt('auth') . wp_salt('secure_auth'), true);
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return is_string($plain) ? $plain : '';
    }
    private static function redirect_uri() {return admin_url('admin-post.php?action=zmp_drive_callback');}
    private static function require_admin() {if(!current_user_can('manage_options'))wp_die('Недоволни привилегии.','Нема пристап',array('response'=>403));}
    private static function redirect_admin($status) {wp_safe_redirect(add_query_arg(array('page'=>'zmp-google-drive','zmp_drive_status'=>sanitize_key($status)),admin_url('admin.php')));exit;}
    private static function redirect_folder_admin($status) {wp_safe_redirect(add_query_arg(array('page'=>'zmp-google-drive','zmp_folder_migration'=>sanitize_key($status)),admin_url('admin.php')));exit;}
    private static function redirect_folder_settings($status) {wp_safe_redirect(add_query_arg(array('page'=>'zmp-google-drive','zmp_drive_folders'=>sanitize_key($status)),admin_url('admin.php')));exit;}
    private static function redirect_front($status) {if(class_exists('ZMP_Debug'))ZMP_Debug::log(preg_match('/failed|invalid|large|denied|error/i',(string)$status)?'warning':'info','Google Drive video workflow finished.',array('status'=>sanitize_key($status)),'google_drive');$url=wp_get_referer()?:home_url('/');wp_safe_redirect(add_query_arg('zmp_video_status',sanitize_key($status),remove_query_arg('zmp_video_status',$url)));exit;}
    private static function status_message($status) {$m=array('settings_saved'=>'OAuth поставките се зачувани.','connected'=>'Google Drive е поврзан и приватната root папка е подготвена.','disconnected'=>'Google Drive е исклучен.','invalid_settings'=>'OAuth Client ID не е валиден.','secret_required'=>'Потребен е OAuth Client secret.','encryption_unavailable'=>'Серверот нема OpenSSL за безбедно чување на OAuth тајната.','oauth_state_invalid'=>'OAuth повикот истече или не одговара на оваа сесија.','oauth_denied'=>'Google не го одобри поврзувањето.','oauth_code_missing'=>'Од Google не пристигна авторизациски код.','oauth_exchange_failed'=>'Google не ја потврди OAuth сесијата. Проверете ги Client ID, secret, redirect URI и Drive API.','refresh_token_missing'=>'Google не врати refresh token. Отповикајте ја старата дозвола за апликацијата во Google Account и поврзете повторно.','drive_folder_failed'=>'OAuth успеа, но приватната Drive папка не можеше да се создаде.','confirmation_invalid'=>'Потврдата за бришење не се совпаѓа.','season_not_completed'=>'Видеата може да се избришат само по официјално завршување на натпреварувачката година.','purge_finished'=>'Барањата за бришење се испратени. Проверете го бројот на преостанати активни видеа пред да ја затворите архивата.','migration_drive_not_connected'=>'Прво поврзете ја приватната Drive-папка на ЗМП.','migration_complete'=>'Префрлањето заврши. Drive копиите се проверени и локалните копии се отстранети.','migration_batch'=>'Серијата е префрлена. Повторете ја операцијата додека не се прикаже дека е завршена.','migration_attention'=>'Не може да се потврди или префрли барем еден фајл. Оригиналната копија не е избришана; проверете ја врската и пристапот до хостингот пред повторен обид.','folders_saved'=>'ID-поставките за Drive папките се зачувани.','folders_invalid'=>'Drive-папката не е достапна или ID-форматот е невалиден.');return $m[$status]??'Барањето не е завршено.';}
}







