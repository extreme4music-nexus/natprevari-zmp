<?php
$secret='/home/tonic/zmp-local-sandbox/phase66b-test/private/messenger-attachment-session.json';
$file='/home/tonic/zmp-local-sandbox/phase66b-test/tmp/synthetic-messenger-attachment.pdf';
$out='/home/tonic/zmp-local-sandbox/phase66b-test/tmp/messenger-attachment-post-response.bin';
$data=json_decode(file_get_contents($secret),true); $pdf=file_get_contents($file);
if(!is_array($data)||!is_string($pdf)||substr($pdf,0,5)!=='%PDF-'||!is_array($data['sender_session']??null)){fwrite(STDERR,"synthetic upload preflight failed\n");exit(2);}
$boundary='----ZMPsynthetic'.bin2hex(random_bytes(16)); $fields=array('action'=>'zmp_role_message','zmp_role_message_action'=>'send','scope_type'=>'moderator','category_id'=>'0','region_key'=>'','thread_id'=>(string)$data['messages']['root'],'message'=>'Synthetic attachment reply for private storage and download verification.','_wpnonce'=>$data['sender_session']['message_nonce']);
$body=''; foreach($fields as $name=>$value){$body.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"".$name."\"\r\n\r\n".$value."\r\n";}
$body.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"attachment\"; filename=\"synthetic-messenger.pdf\"\r\nContent-Type: application/pdf\r\n\r\n".$pdf."\r\n--".$boundary."--\r\n";
$context=stream_context_create(array('http'=>array('method'=>'POST','header'=>'Content-Type: multipart/form-data; boundary='.$boundary."\r\n".'Cookie: '.$data['sender_session']['name'].'='.$data['sender_session']['value']."\r\n".'Connection: close', 'content'=>$body,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15)));
$response=file_get_contents('http://127.0.0.1:8103/wp-admin/admin-post.php',false,$context); $headers=$http_response_header??array(); $status=''; $location=''; foreach($headers as $header){if(preg_match('/^HTTP\/\S+\s+(\d{3})/',$header,$m)){$status=$m[1];} if(stripos($header,'Location:')===0){$location=trim(substr($header,9));}}
file_put_contents($out,is_string($response)?$response:''); chmod($out,0600); echo 'upload_http_status='.$status.PHP_EOL; echo 'redirect_has_sent_flag='.(strpos($location,'zmp_role_message_sent=1')!==false?'yes':'no').PHP_EOL; echo 'response_body_bytes='.strlen((string)$response).PHP_EOL;
if($status!=='302'||strpos($location,'zmp_role_message_sent=1')===false){exit(1);}