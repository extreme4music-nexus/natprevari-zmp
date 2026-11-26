<?php
$route=trim((string)@file_get_contents('/home/tonic/zmp-local-sandbox/phase66b-test/tmp/route-matrix-current.txt'));
if(!$route || !preg_match('/^(admin_post|wp_ajax)(_nopriv)?_zmp_[a-z0-9_]+$/',$route)){WP_CLI::error('invalid route token');}
$base='/home/tonic/zmp-local-sandbox/phase66b-test/tmp/route-matrix-';$id=hash('sha256',$route);
foreach(array('.sql','.http','.mail','.result') as $s){@unlink($base.$id.$s);}
file_put_contents($base.'current.txt',$route);file_put_contents($base.'active.flag','1');
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REQUEST_URI']='/wp-admin/admin-ajax.php';$_REQUEST=array('action'=>preg_replace('/^(admin_post|wp_ajax)(_nopriv)?_/','',$route));$_POST=$_REQUEST;if($route==="admin_post_zmp_role_message")$_POST["zmp_role_message_action"]="send";$_GET=array();$_FILES=array();
if(strpos($route,'_nopriv_')!==false){wp_set_current_user(0);}else{wp_set_current_user(117);}
register_shutdown_function(static function()use($base,$id,$route){@unlink($base.'active.flag');$error=error_get_last();$out=array('hook'=>$route,'http_status'=>http_response_code(),'dml_blocked'=>is_file($base.$id.'.sql')?count(file($base.$id.'.sql')):0,'external_http_blocked'=>is_file($base.$id.'.http')?count(file($base.$id.'.http')):0,'mail_blocked'=>is_file($base.$id.'.mail')?count(file($base.$id.'.mail')):0,'fatal'=>($error&&in_array($error['type'],array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR),true))?substr($error['message'],0,160):null);file_put_contents($base.$id.'.result',wp_json_encode($out));});
do_action($route);
