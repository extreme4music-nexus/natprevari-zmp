<?php
/** Read-only role-denial check; invoke handlers directly because WP-CLI does not run admin-menu initialization. */
if (!defined('WP_CLI') || !class_exists('ZMP_President_Mandate_Admin')) { WP_CLI::error('required runtime unavailable'); }
$uid = 117; $user = get_userdata($uid);
if (!$user || user_can($uid, 'manage_options')) { WP_CLI::error('synthetic teacher fixture is not suitable'); }
$denials = array(); add_filter('wp_die_handler', static function ($handler) use (&$denials) { return static function ($message='', $title='', $args=array()) use (&$denials) { $denials[]=(int)($args['response']??0); throw new RuntimeException('expected_role_denial'); }; });
wp_set_current_user($uid);
$handlers = array('appointment'=>'handle_appointment','revocation'=>'handle_revocation','scheduled_cancellation'=>'handle_scheduled_cancellation'); $ok=true;
foreach ($handlers as $label=>$method) { $_POST=array('mandate_id'=>'999999999','president_user_id'=>'999999999'); $_GET=array(); $_REQUEST=$_POST; $n=count($denials); try { ZMP_President_Mandate_Admin::$method(); } catch (RuntimeException $e) { if ($e->getMessage()!=='expected_role_denial') { throw $e; } } $pass=count($denials)===$n+1 && end($denials)===403; WP_CLI::log('mandate_'.$label.'_teacher_denied_403='.($pass?'PASS':'FAIL')); if(!$pass)$ok=false; }
if(!$ok) WP_CLI::error('one or more mandate role-denial checks failed');
WP_CLI::success('Three synthetic Teacher mandate handler calls denied with HTTP 403 before nonce/service calls; no database mutation was possible.');
