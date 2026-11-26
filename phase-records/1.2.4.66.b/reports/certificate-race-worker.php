<?php
$dir='/home/tonic/zmp-local-sandbox/phase66b-test/tmp/certificate-race-';
$f=json_decode(file_get_contents($dir.'fixture.json'),true); if(!$f) throw new RuntimeException('fixture_missing');
$driveBefore=get_option('zmp_google_drive_settings',null);$debugBefore=get_option('zmp_debug_events',null);$debugNull=$debugBefore===null;
$rm=new ReflectionMethod('ZMP_Google_Drive','encrypt');$rm->setAccessible(true);
$settings=is_array($driveBefore)?$driveBefore:array();$settings['client_id']='synthetic-client';$settings['client_secret_enc']=$rm->invoke(null,'synthetic-client-secret');$settings['refresh_token_enc']=$rm->invoke(null,'synthetic-refresh-token');$settings['root_folder_id']='synthetic-root';$settings['folder_ids']['']='';update_option('zmp_google_drive_settings',$settings,false);
$uploadCount=0;$httpLog=array();
add_filter('pre_http_request',function($pre,$args,$url) use (&$uploadCount,&$httpLog,$dir,$f){$method=strtoupper($args['method']??'GET');$httpLog[]=array('method'=>$method,'url'=>parse_url($url,PHP_URL_HOST).parse_url($url,PHP_URL_PATH));$response=function($code,$body='',$headers=array()){return array('response'=>array('code'=>$code,'message'=>'Synthetic'),'headers'=>$headers,'body'=>$body,'cookies'=>array(),'filename'=>null);};
if(strpos($url,'oauth2.googleapis.com/token')!==false)return $response(200,'{"access_token":"synthetic-token","expires_in":3600}');
if($method==='GET'&&strpos($url,'/drive/v3/files?')!==false)return $response(200,'{"files":[]}');
if($method==='GET'&&strpos($url,'/drive/v3/files/synthetic-template-file?')!==false){$target=$args['filename']??'';if(!$target||!copy($f['template_path'],$target))return new WP_Error('mock_template_copy_failed');return $response(200,'');}
if($method==='POST'&&strpos($url,'/drive/v3/files?')!==false){$meta=json_decode($args['body']??'{}',true);if(strpos($url,'uploadType=resumable')!==false){$uploadCount++;return $response(200,'',array('location'=>'https://mock.invalid/upload/'.$uploadCount));}$id='synthetic-folder-'.$uploadCount;return $response(200,'{"id":"'.$id.'"}');}
if($method==='PUT'&&strpos($url,'mock.invalid/upload/')!==false){$n=(int)basename(parse_url($url,PHP_URL_PATH));if($n===2){file_put_contents($dir.'pdf-paused',json_encode(array('time'=>microtime(true),'pid'=>getmypid(),'http_count'=>count($httpLog))));$until=microtime(true)+60;while(!is_file($dir.'pdf-release')&&microtime(true)<$until)usleep(50000);if(!is_file($dir.'pdf-release'))return new WP_Error('mock_pause_timeout');}return $response(200,'{"id":"synthetic-drive-'.$n.'"}');}
if($method==='DELETE')return $response(204,'');
return new WP_Error('synthetic_external_http_blocked');},10,3);
$resultId=(int)$f['result_id'];$row=(object)array('id'=>$resultId,'season_id'=>(int)$f['season_id'],'category_id'=>(int)$f['category_id'],'entry_id'=>1,'phase'=>$f['phase'],'award_key'=>$f['award_key'],'participant_name'=>'Synthetic Race Participant','group_name'=>'','participant_type'=>'individual','first_name'=>'Synthetic','last_name'=>'Participant','category_name'=>'Synthetic Category','academic_year'=>'2026–2027','school_name'=>'Synthetic School','final_points'=>10,'decided_at'=>current_time('mysql'));
$pdf=ZMP_Certificate_Templates::get_issued_pdf_file('category',$resultId,$row);$out=array('result'=>is_wp_error($pdf)?'error':'success','error_code'=>is_wp_error($pdf)?$pdf->get_error_code():'','pdf_size'=>is_string($pdf)&&is_file($pdf)?filesize($pdf):0,'uploads'=>$uploadCount,'http_requests'=>$httpLog);if(is_string($pdf)&&is_file($pdf))unlink($pdf);file_put_contents($dir.'worker-result.json',wp_json_encode($out));
if($driveBefore===null)delete_option('zmp_google_drive_settings');else update_option('zmp_google_drive_settings',$driveBefore,false);
if($debugNull)delete_option('zmp_debug_events');else update_option('zmp_debug_events',$debugBefore,false);
WP_CLI::log(wp_json_encode($out));if($out['result']!=='success')WP_CLI::error('certificate issuance mock failed');
