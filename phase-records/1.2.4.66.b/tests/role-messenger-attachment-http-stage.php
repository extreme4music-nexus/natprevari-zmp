<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('local runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$messages=$wpdb->prefix.'zmp_role_messages'; $suffix=gmdate('YmdHis').wp_generate_password(6,false,false); $base='zmp_synthetic_attach_'.strtolower($suffix);
$secret_path='/home/tonic/zmp-local-sandbox/phase66b-test/private/messenger-attachment-session.json';
$data=array('fixture'=>'synthetic-messenger-attachment','suffix'=>$suffix,'users'=>array(),'messages'=>array());
$make_user=static function($login,$role) use (&$data) {
    $id=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(48,true,true),'user_email'=>$login.'@example.invalid','display_name'=>'Synthetic Messenger Test','role'=>'subscriber'));
    if(is_wp_error($id)){WP_CLI::error('synthetic user setup failed');}
    $data['users'][]=(int)$id; if($role==='moderator'){(new WP_User($id))->add_role('zmp_moderator');}
    clean_user_cache($id); return (int)$id;
};
$cookie_data=static function($id) {
    $exp=time()+DAY_IN_SECONDS; $token=WP_Session_Tokens::get_instance($id)->create($exp);
    $cookie=wp_generate_auth_cookie($id,$exp,'logged_in',$token); $_COOKIE[LOGGED_IN_COOKIE]=$cookie; wp_set_current_user($id);
    return array('name'=>LOGGED_IN_COOKIE,'value'=>$cookie,'message_nonce'=>wp_create_nonce(ZMP_Role_Messenger::NONCE),'export_nonce'=>wp_create_nonce(ZMP_Role_Messenger::NONCE.'_export'));
};
$sender=$make_user($base,'moderator'); $recipient=$make_user($base.'_recipient','moderator'); $denied=$make_user($base.'_denied','subscriber');
$created=current_time('mysql'); $expiry=(new DateTimeImmutable($created,wp_timezone()))->modify('+2 days')->format('Y-m-d H:i:s');
$ok=$wpdb->insert($messages,array('thread_id'=>0,'scope_type'=>'moderator','season_id'=>0,'category_id'=>0,'region_key'=>'','sender_id'=>$sender,'subject'=>'Synthetic attachment thread '.$suffix,'message'=>'Synthetic root for file and scope verification.','created_at'=>$created,'expires_at'=>$expiry),array('%d','%s','%d','%d','%s','%d','%s','%s','%s','%s'));
if(!$ok){foreach($data['users'] as $id){wp_delete_user($id);} WP_CLI::error('synthetic root creation failed');}
$root=(int)$wpdb->insert_id; $wpdb->update($messages,array('thread_id'=>$root),array('id'=>$root),array('%d'),array('%d')); $data['messages']['root']=$root; $data['messages']['sender']=$sender; $data['messages']['recipient']=$recipient; $data['messages']['denied']=$denied;
$data['sender_session']=$cookie_data($sender); $data['recipient_session']=$cookie_data($recipient); $data['denied_session']=$cookie_data($denied);
if(!is_dir(dirname($secret_path))||file_put_contents($secret_path,wp_json_encode($data,JSON_PRETTY_PRINT))===false){$wpdb->delete($messages,array('id'=>$root),array('%d'));foreach($data['users'] as $id){wp_delete_user($id);}WP_CLI::error('private fixture metadata could not be stored');}
@chmod($secret_path,0600); WP_CLI::log('synthetic attachment fixture staged; secret session file kept mode 0600');