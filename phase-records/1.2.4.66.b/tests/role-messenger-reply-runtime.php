<?php
if (!defined('WP_CLI') || !class_exists('ZMP_Role_Messenger')) { WP_CLI::error('local runtime unavailable'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
$messages = $wpdb->prefix . 'zmp_role_messages';
$notifications = $wpdb->prefix . 'zmp_role_message_notifications';
$reads = $wpdb->prefix . 'zmp_role_message_reads';
$attachments = $wpdb->prefix . 'zmp_role_message_attachments';
$suffix = gmdate('YmdHis') . wp_generate_password(6, false, false);
$login = 'zmp_synthetic_reply_' . strtolower($suffix);
$subject = 'Synthetic reply ' . $suffix;
$root_body = 'Synthetic root used only for local reply-path verification.';
$reply_body = 'Synthetic reply through actual moderator message handler.';
$baseline = array(
    'messages' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $messages"),
    'notifications' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $notifications"),
    'reads' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $reads"),
    'attachments' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $attachments"),
);
$user_id = 0; $recipient_id = 0; $root_id = 0; $reply_id = 0;
$result = array('role_stack'=>false,'root_created'=>false,'reply_saved'=>false,'root_expiry_preserved'=>false,'recipient_notified'=>false,'baseline_restored'=>false,'synthetic_users_removed'=>false);
$cleanup = static function () use (&$user_id,&$recipient_id,&$root_id,&$reply_id,$wpdb,$messages,$notifications,$reads,$attachments,$baseline,&$result) {
    $ids = array_values(array_filter(array((int)$root_id,(int)$reply_id)));
    if ($ids) {
        foreach ($ids as $id) {
            $wpdb->delete($reads,array('message_id'=>$id));
            $wpdb->delete($notifications,array('message_id'=>$id));
            $wpdb->delete($attachments,array('message_id'=>$id));
        }
        foreach ($ids as $id) { $wpdb->delete($messages,array('id'=>$id),array('%d')); }
    }
    if ($user_id) { wp_delete_user($user_id); }
    if ($recipient_id) { wp_delete_user($recipient_id); }
    $result['baseline_restored'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM $messages")===$baseline['messages']
        && (int)$wpdb->get_var("SELECT COUNT(*) FROM $notifications")===$baseline['notifications']
        && (int)$wpdb->get_var("SELECT COUNT(*) FROM $reads")===$baseline['reads']
        && (int)$wpdb->get_var("SELECT COUNT(*) FROM $attachments")===$baseline['attachments'];
    $result['synthetic_users_removed'] = !$user_id || !get_userdata($user_id);
    $result['synthetic_users_removed'] = $result['synthetic_users_removed'] && (!$recipient_id || !get_userdata($recipient_id));
};
register_shutdown_function(static function () use (&$result,&$user_id,&$recipient_id,&$root_id,&$reply_id,$wpdb,$messages,$notifications,$baseline,$cleanup) {
    if ($root_id && !$reply_id) {
        $reply_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $messages WHERE thread_id=%d AND sender_id=%d AND message=%s ORDER BY id DESC LIMIT 1",$root_id,$user_id,'Synthetic reply through actual moderator message handler.'));
    }
    if ($root_id && $reply_id) {
        $root=$wpdb->get_row($wpdb->prepare("SELECT id,thread_id,scope_type,season_id,category_id,region_key,expires_at FROM $messages WHERE id=%d",$root_id));
        $reply=$wpdb->get_row($wpdb->prepare("SELECT id,thread_id,scope_type,season_id,category_id,region_key,sender_id,message,expires_at FROM $messages WHERE id=%d",$reply_id));
        $result['reply_saved'] = $reply && (int)$reply->thread_id===$root_id && $reply->scope_type==='moderator' && (int)$reply->season_id===0 && (int)$reply->category_id===0 && $reply->region_key==='' && (int)$reply->sender_id===$user_id && $reply->message==='Synthetic reply through actual moderator message handler.';
        $result['root_expiry_preserved'] = $root && $reply && $root->expires_at===$reply->expires_at;
        $result['recipient_notified'] = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $notifications WHERE message_id=%d AND thread_id=%d AND recipient_user_id=%d AND status='unread'",$reply_id,$root_id,$recipient_id))===1;
        $result['root_created'] = $root && (int)$root->thread_id===$root_id && $root->scope_type==='moderator';
    }
    $cleanup();
    echo 'ZMP_REPLY_RESULT=' . wp_json_encode($result) . PHP_EOL;
    if (in_array(false,$result,true)) { WP_CLI::error('Synthetic reply verification or exact cleanup failed.'); }
    WP_CLI::success('Synthetic moderator reply verified; fixture removed.');
});
$user_id=wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(40,true,true),'user_email'=>$login.'@example.invalid','display_name'=>'Synthetic Moderator Sender','role'=>'subscriber'));
if (is_wp_error($user_id)) { WP_CLI::error('synthetic sender creation failed'); }
$recipient_id=wp_insert_user(array('user_login'=>$login.'_recipient','user_pass'=>wp_generate_password(40,true,true),'user_email'=>$login.'_recipient@example.invalid','display_name'=>'Synthetic Moderator Recipient','role'=>'subscriber'));
if (is_wp_error($recipient_id)) { $cleanup(); WP_CLI::error('synthetic recipient creation failed'); }
$sender=new WP_User($user_id); $sender->add_role('zmp_moderator');
$recipient=new WP_User($recipient_id); $recipient->add_role('zmp_moderator');
clean_user_cache($user_id); clean_user_cache($recipient_id); wp_set_current_user($user_id);
$result['role_stack']=user_can($user_id,'zmp_moderator_cap') && in_array('subscriber',(array)get_userdata($user_id)->roles,true) && in_array('zmp_moderator',(array)get_userdata($user_id)->roles,true);
$created=current_time('mysql'); $expiry=(new DateTimeImmutable($created,wp_timezone()))->modify('+2 days')->format('Y-m-d H:i:s');
$ok=$wpdb->insert($messages,array('thread_id'=>0,'scope_type'=>'moderator','season_id'=>0,'category_id'=>0,'region_key'=>'','sender_id'=>$user_id,'subject'=>$subject,'message'=>$root_body,'created_at'=>$created,'expires_at'=>$expiry),array('%d','%s','%d','%d','%s','%d','%s','%s','%s','%s'));
if (!$ok) { WP_CLI::error('synthetic root creation failed'); }
$root_id=(int)$wpdb->insert_id;
if ($wpdb->update($messages,array('thread_id'=>$root_id),array('id'=>$root_id),array('%d'),array('%d'))===false) { WP_CLI::error('synthetic root link failed'); }
$_FILES=array();
$_POST=array('zmp_role_message_action'=>'send','_wpnonce'=>wp_create_nonce(ZMP_Role_Messenger::NONCE),'scope_type'=>'moderator','thread_id'=>(string)$root_id,'message'=>$reply_body);
$_REQUEST=$_POST;
ZMP_Role_Messenger::handle_post();
WP_CLI::error('reply handler returned without redirect/exit');
