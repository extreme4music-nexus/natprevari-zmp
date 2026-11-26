<?php
if (!defined('ABSPATH')) { exit; }
$base='/home/tonic/zmp-local-sandbox/phase66b-test/tmp/route-matrix-';
$active=$base.'active.flag';
add_filter('query', static function($sql) use ($base,$active){if(!is_file($active))return $sql;if(preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|CREATE|TRUNCATE)\b/i',$sql)){$route=trim((string)@file_get_contents($base.'current.txt'));file_put_contents($base.hash('sha256',$route).'.sql',substr(preg_replace('/\s+/',' ',$sql),0,500)."\n",FILE_APPEND);return 'SELECT 1 WHERE 1=0';}return $sql;},PHP_INT_MIN);
add_filter('pre_http_request',static function($pre,$args,$url)use($base,$active){if(is_file($active)){$route=trim((string)@file_get_contents($base.'current.txt'));file_put_contents($base.hash('sha256',$route).'.http',parse_url($url,PHP_URL_HOST)."\n",FILE_APPEND);return new WP_Error('zmp_route_matrix_network_blocked','Blocked by local route-matrix harness.');}return $pre;},PHP_INT_MIN,3);
add_filter('pre_wp_mail',static function($return,$atts)use($base,$active){if(is_file($active)){$route=trim((string)@file_get_contents($base.'current.txt'));file_put_contents($base.hash('sha256',$route).'.mail',"blocked\n",FILE_APPEND);return true;}return $return;},PHP_INT_MIN,2);
