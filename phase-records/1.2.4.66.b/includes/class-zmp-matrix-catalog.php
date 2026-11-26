<?php
if (!defined('ABSPATH')) { exit; }

/** Private Drive-backed backing-track catalog shared by eligible teachers. */
class ZMP_Matrix_Catalog {
    const NONCE = 'zmp_matrix_catalog';
    const ADMIN_NONCE = 'zmp_matrix_picker_admin';
    const CACHE_TTL = 300;
    const MAX_IMPORT_FILES = 10;
    const MAX_AUDIO_BYTES = 20971520;
    private static function audio_mimes() { return array('audio/mpeg','audio/mp3','audio/wav','audio/x-wav','audio/wave','audio/ogg','audio/mp4','audio/aac','audio/flac','audio/webm'); }
    public static function install() {
        global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate(); $table=$wpdb->prefix.'zmp_matrices';
        self::drop_legacy_unique_drive_id_index($table);
        dbDelta("CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            artist VARCHAR(190) NOT NULL DEFAULT '',
            rights_note VARCHAR(255) NOT NULL DEFAULT '',
            language VARCHAR(20) NOT NULL DEFAULT '',
            duration_seconds INT(10) UNSIGNED NOT NULL DEFAULT 0,
            category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            drive_file_id VARCHAR(190) NOT NULL,
            source_folder_id VARCHAR(190) NOT NULL DEFAULT '',
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            drive_md5 CHAR(32) NOT NULL DEFAULT '',
            uploaded_by BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY drive_file_id (drive_file_id),
            KEY category_status (category_id,status),
            KEY title (title),
            KEY source_folder_status (source_folder_id,status)
        ) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}zmp_entry_matrices (
            entry_id BIGINT(20) UNSIGNED NOT NULL,
            matrix_id BIGINT(20) UNSIGNED NOT NULL,
            linked_by BIGINT(20) UNSIGNED NOT NULL,
            linked_at DATETIME NOT NULL,
            PRIMARY KEY  (entry_id),
            KEY matrix_id (matrix_id)
        ) $c;");
        if(!get_option('zmp_matrix_picker_migration_48')){$folder=class_exists('ZMP_Google_Drive')?ZMP_Google_Drive::configured_matrix_folder_id():'';if($folder!==''){$wpdb->update($table,array('source_folder_id'=>$folder),array('source_folder_id'=>''),array('%s'),array('%s'));$wpdb->query($wpdb->prepare("UPDATE $table SET status='needs_picker' WHERE uploaded_by=0 AND status='active' AND source_folder_id=%s",$folder));}update_option('zmp_matrix_picker_migration_48',1,false);}
    }
    private static function drop_legacy_unique_drive_id_index($table) {
        global $wpdb;$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));if($exists!==$table)return;
        $indexes=$wpdb->get_results("SHOW INDEX FROM `$table`");foreach((array)$indexes as $index){if($index->Key_name==='drive_file_id'&&(int)$index->Non_unique===0){$wpdb->query("ALTER TABLE `$table` DROP INDEX `drive_file_id`");break;}}
    }
    public static function init() {
        add_action('wp_ajax_zmp_matrix_search',array(__CLASS__,'search')); add_action('wp_ajax_zmp_matrix_preview_url',array(__CLASS__,'preview_url')); add_action('wp_ajax_zmp_matrix_upload',array(__CLASS__,'upload')); add_action('wp_ajax_zmp_matrix_picker_config',array(__CLASS__,'picker_config')); add_action('wp_ajax_zmp_matrix_import_drive',array(__CLASS__,'import_drive_files')); add_action('admin_post_zmp_matrix_stream',array(__CLASS__,'stream')); add_action('admin_enqueue_scripts',array(__CLASS__,'enqueue_picker_api'));
    }
    private static function allowed() { return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('zmp_teacher_cap') || current_user_can('view_zmp_teacher_dashboard')); }
    public static function enqueue_picker_api($hook) { if($hook==='zmp-competitions_page_zmp-google-drive'&&current_user_can('manage_options'))wp_enqueue_script('zmp-google-picker-api','https://apis.google.com/js/api.js',array(),null,true); }
    public static function render_picker() {
        if(!self::allowed())return '';$ajax=admin_url('admin-ajax.php');$script_path=ZMP_PLUGIN_DIR.'assets/js/zmp-matrix-catalog.js';$script_version=is_file($script_path)?(string)filemtime($script_path):ZMP_VERSION;$max_audio_bytes=min(self::MAX_AUDIO_BYTES,max(0,(int)wp_max_upload_size()));$max_audio_label=size_format($max_audio_bytes);wp_enqueue_script('zmp-matrix-catalog',ZMP_PLUGIN_URL.'assets/js/zmp-matrix-catalog.js',array(),$script_version,true);wp_localize_script('zmp-matrix-catalog','zmpMatrixCatalog',array('ajaxUrl'=>$ajax,'nonce'=>wp_create_nonce(self::NONCE),'cacheTtl'=>self::CACHE_TTL,'maxAudioBytes'=>$max_audio_bytes,'maxAudioLabel'=>$max_audio_label));
        return '<fieldset class="zmp-matrix-picker" hidden><legend>Матрица за вокална интерпретација</legend><input type="hidden" name="matrix_id" id="zmp-matrix-id" value=""><p id="zmp-matrix-selected" role="status">Изберете матрица од каталогот или прикачете нова.</p><button type="button" class="zmp-btn zmp-btn-secondary" id="zmp-matrix-open">Пребарај матрици</button> <button type="button" class="zmp-btn zmp-btn-secondary" id="zmp-matrix-upload-open">Прикачи нова матрица</button><div id="zmp-matrix-dialog" class="zmp-matrix-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="zmp-matrix-heading"><div class="zmp-matrix-dialog-inner"><button type="button" id="zmp-matrix-close" aria-label="Затвори">×</button><h4 id="zmp-matrix-heading">Каталог на матрици</h4><label>Пребарување <input type="search" id="zmp-matrix-search" autocomplete="off"></label><button type="button" id="zmp-matrix-refresh">Освежи каталог</button><p id="zmp-matrix-status" role="status" aria-live="polite"></p><div id="zmp-matrix-results" aria-live="polite"></div><button type="button" id="zmp-matrix-more" hidden>Повеќе резултати</button><section id="zmp-matrix-upload-panel" hidden><h4>Нова матрица</h4><label>Наслов * <input id="zmp-matrix-title-input" maxlength="255"></label><label>Изведувач / автор <input id="zmp-matrix-artist" maxlength="190"></label><label>Извор и права за користење * <input id="zmp-matrix-rights" maxlength="255" required></label><label>Јазик <input id="zmp-matrix-language" maxlength="20"></label><label>Аудио фајл (MP3, WAV, OGG, M4A; до '.esc_html($max_audio_label).') * <input type="file" id="zmp-matrix-file" accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4,.mp3,.wav,.ogg,.m4a"></label><button type="button" id="zmp-matrix-upload-submit">Прикачи и избери</button><p id="zmp-matrix-upload-status" role="status"></p></section></div></div></fieldset>';
    }
    public static function search() {
        if(!self::allowed())wp_send_json_error(array('message'=>'Нема пристап.'),403);check_ajax_referer(self::NONCE);global $wpdb;$q=isset($_POST['q'])&&is_scalar($_POST['q'])?sanitize_text_field(wp_unslash($_POST['q'])):'';$category=absint($_POST['category_id']??0);$page=min(100,absint($_POST['page']??0));$folder=ZMP_Google_Drive::matrix_folder_id();if($folder==='')wp_send_json_error(array('message'=>'Drive матрици не се поставени.'),503);$like='%'.$wpdb->esc_like($q).'%';$table=$wpdb->prefix.'zmp_matrices';$where=$wpdb->prepare("source_folder_id=%s AND status='active' AND (category_id=0 OR category_id=%d) AND (title LIKE %s OR artist LIKE %s)",$folder,$category,$like,$like);$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,artist,mime_type FROM $table WHERE $where ORDER BY title,drive_file_id LIMIT 21 OFFSET %d",$page*20));$more=count((array)$rows)>20;$rows=array_slice((array)$rows,0,20);$items=array();foreach($rows as $row)$items[]=array('id'=>(int)$row->id,'title'=>$row->title,'artist'=>$row->artist,'mime_type'=>$row->mime_type);wp_send_json_success(array('items'=>$items,'has_more'=>$more,'page'=>$page,'folder_key'=>substr(hash('sha256',$folder),0,16)));
    }
    public static function preview_url() { if(!self::allowed())wp_send_json_error(array('message'=>'Нема пристап.'),403);check_ajax_referer(self::NONCE);$id=absint($_POST['id']??0);$category=absint($_POST['category_id']??0);if(!$id||!self::valid_matrix($id,$category))wp_send_json_error(array('message'=>'Матрицата повеќе не е достапна.'),404);wp_send_json_success(array('url'=>wp_nonce_url(admin_url('admin-post.php?action=zmp_matrix_stream&id='.$id),'zmp_matrix_stream_'.$id))); }
    public static function stream() {
        if(!self::allowed())wp_die('Немате пристап.','Нема пристап',array('response'=>403));$id=absint($_GET['id']??0);if(!$id||!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce']??'')),'zmp_matrix_stream_'.$id))wp_die('Неважечка проверка.','Нема пристап',array('response'=>403));global $wpdb;$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zmp_matrices WHERE id=%d AND status='active' AND source_folder_id=%s",$id,ZMP_Google_Drive::matrix_folder_id()));if(!$row)wp_die('Матрицата не е достапна.','Недостапно',array('response'=>404));$path=ZMP_Google_Drive::download_private_file_to_temp($row->drive_file_id);if(is_wp_error($path))wp_die('Не може да се отвори приватната матрица.','Недостапно',array('response'=>503));$integrity=!empty($row->drive_md5)?hash_equals(strtolower((string)$row->drive_md5),strtolower((string)md5_file($path))):hash_equals((string)$row->sha256,(string)hash_file('sha256',$path));if(!$integrity){@unlink($path);wp_die('Проверката на матрицата не помина.','Недостапно',array('response'=>503));}$size=(int)filesize($path);$start=0;$end=$size-1;$partial=false;$range=trim((string)($_SERVER['HTTP_RANGE']??''));if($range!==''){if(!preg_match('/^bytes=(\d*)-(\d*)$/',$range,$m)||($m[1]===''&&$m[2]==='')){@unlink($path);header('Content-Range: bytes */'.$size);status_header(416);exit;}if($m[1]===''){$suffix=(int)$m[2];if($suffix<1){@unlink($path);header('Content-Range: bytes */'.$size);status_header(416);exit;}$start=max(0,$size-$suffix);}else{$start=(int)$m[1];if($m[2]!=='')$end=(int)$m[2];}if($start>=$size||$end<$start){@unlink($path);header('Content-Range: bytes */'.$size);status_header(416);exit;}$end=min($end,$size-1);$partial=true;}nocache_headers();header('X-Content-Type-Options: nosniff');header('Accept-Ranges: bytes');header('Content-Type: '.sanitize_mime_type($row->mime_type));header('Content-Length: '.($end-$start+1));header('Content-Disposition: inline; filename="matrix-audio"');if($partial){status_header(206);header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);}$fh=fopen($path,'rb');if($fh){fseek($fh,$start);$remaining=$end-$start+1;while($remaining>0&&!feof($fh)){if(connection_aborted())break;$chunk=fread($fh,min(65536,$remaining));if($chunk===false||$chunk==='')break;echo $chunk;$remaining-=strlen($chunk);flush();}fclose($fh);}@unlink($path);exit;
    }
    public static function upload() {
        if(!self::allowed())wp_send_json_error(array('message'=>'Нема пристап.'),403);check_ajax_referer(self::NONCE);$max_bytes=min(self::MAX_AUDIO_BYTES,max(0,(int)wp_max_upload_size()));$max_label=size_format($max_bytes);$post_limit=wp_convert_hr_to_bytes((string)ini_get('post_max_size'));$content_length=absint($_SERVER['CONTENT_LENGTH']??0);if($post_limit>0&&$content_length>$post_limit)wp_send_json_error(array('message'=>'Барањето го надминува тековниот серверски лимит. Максималната големина за матрица е '.$max_label.'.'),413);$title=sanitize_text_field(wp_unslash($_POST['title']??''));$artist=sanitize_text_field(wp_unslash($_POST['artist']??''));$rights=sanitize_text_field(wp_unslash($_POST['rights_note']??''));$language=sanitize_text_field(wp_unslash($_POST['language']??''));$category=absint($_POST['category_id']??0);$duration=max(0,min(3600,absint($_POST['duration_seconds']??0)));$file=$_FILES['file']??null;if($title===''||$rights==='')wp_send_json_error(array('message'=>'Пополнете ги задолжителните полиња за наслов и извор/права.'),400);if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)wp_send_json_error(array('message'=>'Изберете аудио фајл.'),400);$upload_error=(int)($file['error']??UPLOAD_ERR_OK);if($upload_error!==UPLOAD_ERR_OK){$message=in_array($upload_error,array(UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE),true)?'Фајлот го надминува тековниот серверски лимит. Максималната големина за матрица е '.$max_label.'.':'Прикачувањето на аудио не успеа. Проверете го фајлот и обидете се повторно.';wp_send_json_error(array('message'=>$message),in_array($upload_error,array(UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE),true)?413:400);}if(!is_uploaded_file($file['tmp_name'])||(int)$file['size']<1)wp_send_json_error(array('message'=>'Серверот не го примил аудио фајлот. Обидете се повторно.'),400);if((int)$file['size']>$max_bytes)wp_send_json_error(array('message'=>'Фајлот ја надминува дозволената големина од '.$max_label.'.'),413);$ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));$mimes=array('mp3'=>'audio/mpeg','wav'=>'audio/wav','ogg'=>'audio/ogg','m4a'=>'audio/mp4');if(!isset($mimes[$ext]))wp_send_json_error(array('message'=>'Форматот не е поддржан.'),400);$mime=function_exists('mime_content_type')?mime_content_type($file['tmp_name']):'';if(!in_array($mime,array_values($mimes),true))wp_send_json_error(array('message'=>'Содржината не одговара на аудио форматот.'),400);$folder=ZMP_Google_Drive::matrix_folder_id();$drive=ZMP_Google_Drive::upload_private_document($file['tmp_name'],(int)$file['size'],$mimes[$ext],sanitize_file_name($file['name']),0,0,'matrix');if(is_wp_error($drive))wp_send_json_error(array('message'=>'Drive не ја зачува матрицата.'),503);global $wpdb;$ok=$wpdb->insert($wpdb->prefix.'zmp_matrices',array('title'=>$title,'artist'=>$artist,'rights_note'=>$rights,'language'=>$language,'duration_seconds'=>$duration,'category_id'=>$category,'drive_file_id'=>$drive,'source_folder_id'=>$folder,'mime_type'=>$mimes[$ext],'file_size'=>(int)$file['size'],'sha256'=>hash_file('sha256',$file['tmp_name']),'drive_md5'=>'','uploaded_by'=>get_current_user_id(),'status'=>'active','created_at'=>current_time('mysql')));if(!$ok){ZMP_Google_Drive::delete_private_file($drive);wp_send_json_error(array('message'=>'Не успеа да се зачува записот во каталогот.'),500);}wp_send_json_success(array('id'=>(int)$wpdb->insert_id,'title'=>$title));
    }
    public static function valid_matrix($id,$category_id=0) {global $wpdb;$row=$wpdb->get_row($wpdb->prepare("SELECT id,category_id FROM {$wpdb->prefix}zmp_matrices WHERE id=%d AND status='active' AND source_folder_id=%s",absint($id),ZMP_Google_Drive::matrix_folder_id()));return $row&&((int)$row->category_id===0||(int)$row->category_id===absint($category_id));}
    public static function picker_config() {if(!current_user_can('manage_options'))wp_send_json_error(array('message'=>'Нема пристап.'),403);check_ajax_referer(self::ADMIN_NONCE);$cfg=ZMP_Google_Drive::matrix_picker_admin_config();if(is_wp_error($cfg))wp_send_json_error(array('message'=>'Проверете ги API key, бројот на Cloud проектот и OAuth врската во Drive поставките.'),503);nocache_headers();wp_send_json_success($cfg);}
    public static function is_valid_picker_file($meta,$folder) {
        if(!is_array($meta)||!empty($meta['trashed']))return false;$folder=sanitize_text_field((string)$folder);$mime=strtolower((string)($meta['mimeType']??''));$size=absint($meta['size']??0);
        return $folder!==''&&in_array($folder,(array)($meta['parents']??array()),true)&&in_array($mime,self::audio_mimes(),true)&&$size>0&&$size<=self::MAX_AUDIO_BYTES;
    }
    /** Import selected IDs through a result-returning service so the exact import behavior is regression-testable. */
    public static function import_selected_file_ids($ids) {
        if(!is_array($ids)||count($ids)<1||count($ids)>self::MAX_IMPORT_FILES)return new WP_Error('matrix_import_invalid_selection','Изберете од 1 до 10 матрици. За повеќе повторете ја постапката.');
        $folder=ZMP_Google_Drive::matrix_folder_id();
        if($folder==='')return new WP_Error('matrix_import_folder_missing','Не е поставена папка за матрици.');
        global $wpdb;$table=$wpdb->prefix.'zmp_matrices';$added=0;$existing=0;$invalid=0;
        foreach(array_unique($ids) as $raw_id){
            $id=is_scalar($raw_id)?sanitize_text_field((string)$raw_id):'';
            if(!preg_match('/^[A-Za-z0-9_-]{10,190}$/',$id)){$invalid++;continue;}
            $meta=ZMP_Google_Drive::get_matrix_file_metadata($id);
            if(is_wp_error($meta)||!self::is_valid_picker_file($meta,$folder)){$invalid++;continue;}
            $wpdb->last_error='';
            $existing_rows=$wpdb->get_col($wpdb->prepare("SELECT id FROM $table WHERE drive_file_id=%s",$id));
            if($wpdb->last_error!==''){$invalid++;continue;}
            if($existing_rows){
                // Legacy registrations can point at different rows for the same Drive file ID.
                // Reactivate/update all of them so every existing entry remains playable.
                $updated=$wpdb->update($table,array('title'=>sanitize_text_field(pathinfo((string)$meta['name'],PATHINFO_FILENAME)),'source_folder_id'=>$folder,'mime_type'=>sanitize_mime_type($meta['mimeType']),'file_size'=>(int)$meta['size'],'drive_md5'=>sanitize_text_field($meta['md5Checksum']??''),'status'=>'active'),array('drive_file_id'=>$id));
                if($updated===false){$invalid++;continue;}
                $existing++;continue;
            }
            $tmp=ZMP_Google_Drive::download_private_file_to_temp($id);
            if(is_wp_error($tmp)){$invalid++;continue;}
            $sha=is_file($tmp)?hash_file('sha256',$tmp):false;$md5=is_file($tmp)?md5_file($tmp):false;@unlink($tmp);
            if(!$sha||!$md5||(!empty($meta['md5Checksum'])&&!hash_equals(strtolower((string)$meta['md5Checksum']),strtolower((string)$md5)))){$invalid++;continue;}
            $ok=$wpdb->insert($table,array('title'=>sanitize_text_field(pathinfo((string)$meta['name'],PATHINFO_FILENAME)),'artist'=>'','rights_note'=>'','language'=>'','duration_seconds'=>0,'category_id'=>0,'drive_file_id'=>$id,'source_folder_id'=>$folder,'mime_type'=>sanitize_mime_type($meta['mimeType']),'file_size'=>(int)$meta['size'],'sha256'=>$sha,'drive_md5'=>$md5,'uploaded_by'=>0,'status'=>'active','created_at'=>current_time('mysql')));
            if($ok)$added++;else{$again=$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE drive_file_id=%s",$id));if($again)$existing++;else $invalid++;}
        }
        return array('added'=>$added,'existing'=>$existing,'unavailable'=>$invalid);
    }
    public static function import_drive_files() {
        if(!current_user_can('manage_options'))wp_send_json_error(array('message'=>'Нема пристап.'),403);
        check_ajax_referer(self::ADMIN_NONCE);
        $raw=wp_unslash($_POST['file_ids']??'');$ids=json_decode(is_string($raw)?$raw:'',true);
        $result=self::import_selected_file_ids($ids);
        if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),400);
        $result['message']=sprintf('Додадени: %d · веќе во каталогот: %d · недостапни или неподдржани: %d.',$result['added'],$result['existing'],$result['unavailable']);
        wp_send_json_success($result);
    }
}




