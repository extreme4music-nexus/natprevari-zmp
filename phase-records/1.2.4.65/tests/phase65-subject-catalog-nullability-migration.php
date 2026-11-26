<?php
/** Synthetic migration regression for the approved-subject catalog timestamp contract. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_catalog_test') {
    fwrite(STDERR, "BLOCKED: dedicated disposable WP-CLI site required.\n"); exit(2);
}
global $wpdb;
$site=wp_parse_url(home_url('/'));
$root='/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site';
$source_hashes=array(
    'ZMP_Mentor_Subjects'=>'A350933909C8046AB98B6F6CA9DC21DBAC68CD090ECC00F4BCCF89EBBF5475F6',
    'ZMP_Mentor_Subject_Proposals'=>'8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69',
);
if (!isset($wpdb) || $wpdb->prefix!=='z65_' || realpath(ABSPATH)!==$root
    || !is_array($site) || ($site['scheme']??'')!=='http' || ($site['host']??'')!=='127.0.0.1'
    || (int)($site['port']??0)!==8100
    || !class_exists('ZMP_Mentor_Subject_Proposals')) {
    fwrite(STDERR,"BLOCKED: isolated site or exact candidate mismatch.\n"); exit(2);
}
foreach ($source_hashes as $class=>$expected_hash) {
    if (!class_exists($class)) { fwrite(STDERR,"BLOCKED: required candidate service unavailable.\n"); exit(2); }
    $source_file=(new ReflectionClass($class))->getFileName();
    if (!is_string($source_file) || !is_file($source_file)
        || !hash_equals($expected_hash,strtoupper(hash_file('sha256',$source_file)))) {
        fwrite(STDERR,"BLOCKED: loaded product source differs from reviewed candidate.\n"); exit(2);
    }
}
$admins=get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$totals=count_users();
if (count((array)$admins)!==1 || (int)($totals['total_users']??-1)!==1) {
    fwrite(STDERR,"BLOCKED: one-admin synthetic site required.\n"); exit(2);
}
$table=$wpdb->prefix.'zmp_subject_catalog';
$mentors=$wpdb->prefix.'zmp_mentors';
$table_exists=static function() use ($wpdb,$table) {
    $wpdb->last_error='';
    $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
    if ($wpdb->last_error!=='') { return null; }
    return (string)$found===$table;
};
$count=static function($name) use ($wpdb) {
    $value=$wpdb->get_var('SELECT COUNT(*) FROM `'.esc_sql($name).'`');
    return $wpdb->last_error==='' && is_numeric($value)?(int)$value:null;
};
$timestamp_nullability=static function() use ($wpdb,$table) {
    $rows=$wpdb->get_results('SHOW COLUMNS FROM `'.esc_sql($table).'`');
    if (!is_array($rows) || $wpdb->last_error!=='') { return array(); }
    $nullable=array();
    foreach ($rows as $column) { $nullable[(string)($column->Field??'')]=strtoupper((string)($column->Null??'')); }
    return $nullable;
};
$schema_check=static function() {
    return (new ReflectionMethod('ZMP_Mentor_Subject_Proposals','catalog_schema_is_compatible'))->invoke(null);
};
$initial_exists=$table_exists();
$before_count=$initial_exists===true?$count($table):($initial_exists===false?0:null);
$mentor_count=$count($mentors);
if ($initial_exists===null || $before_count!==0 || $mentor_count!==0) {
    fwrite(STDERR,"BLOCKED: empty synthetic catalog and no mentor rows required.\n"); exit(2);
}
$suffix=wp_generate_password(18,false,false);
$subject_key=hash('sha256','zmp65-nullability-'.$suffix);
$row_id=0; $assertions=0; $failures=array(); $schema_mutated=false;
$check=static function($ok,$name) use (&$assertions,&$failures) {
    $assertions++;
    if (!$ok) { $failures[]=$name; }
};
try {
    if ($initial_exists) {
        $dropped=$wpdb->query('DROP TABLE `'.esc_sql($table).'`');
        if ($dropped===false || $wpdb->last_error!=='') { throw new RuntimeException('empty catalog drop for isolated install check failed'); }
    }
    $schema_mutated=true;
    $wpdb->last_error='';
    $fresh_install=ZMP_Mentor_Subjects::install();
    $fresh_exists=$table_exists();
    $fresh_columns=$fresh_exists?$timestamp_nullability():array();
    $fresh_ready=$schema_check()===true;
    $check($fresh_install===true && $fresh_exists && $fresh_ready
        && ($fresh_columns['created_at']??'')==='NO' && ($fresh_columns['updated_at']??'')==='NO',
        'install creates absent catalog with NOT NULL timestamps and compatible schema');
    if ($fresh_install!==true || !$fresh_exists || !$fresh_ready
        || ($fresh_columns['created_at']??'')!=='NO' || ($fresh_columns['updated_at']??'')!=='NO') {
        throw new RuntimeException('fresh catalog install contract failed');
    }
    $wpdb->last_error='';
    $inserted=$wpdb->insert($table,array(
        'subject_name'=>'Synthetic Nullability Migration Probe','subject_key'=>$subject_key,
        'status'=>'pending','is_active'=>0,'created_by'=>(int)$admins[0],'approved_by'=>0,
        'approved_at'=>null,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql'),
    ));
    $row_id=$inserted===1?(int)$wpdb->insert_id:0;
    if (!$row_id) { throw new RuntimeException('synthetic row setup failed'); }

    $altered=$wpdb->query("ALTER TABLE `".esc_sql($table)."` MODIFY `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP, MODIFY `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP");
    if ($altered===false || $wpdb->last_error!=='') { throw new RuntimeException('controlled nullable drift setup failed'); }
    $nulled=$wpdb->update($table,array('created_at'=>null,'updated_at'=>null),array('id'=>$row_id));
    if ($nulled===false || $wpdb->last_error!=='') { throw new RuntimeException('synthetic null timestamp setup failed'); }
    $check($schema_check()===false,'nullable catalog timestamps fail readiness check');

    $installed=ZMP_Mentor_Subjects::install();
    $check($installed===true,'install repairs nullable timestamp drift');
    $check($schema_check()===true,'catalog readiness matches repaired installed schema');
    $nullable=$timestamp_nullability();
    $check(($nullable['created_at']??'')==='NO' && ($nullable['updated_at']??'')==='NO','installed catalog timestamp columns are NOT NULL');
    $row=$wpdb->get_row($wpdb->prepare("SELECT subject_name,subject_key,status,is_active,created_at,updated_at FROM `{$table}` WHERE id=%d",$row_id),ARRAY_A);
    $check(is_array($row) && $row['subject_name']==='Synthetic Nullability Migration Probe'
        && hash_equals($subject_key,(string)$row['subject_key']) && $row['status']==='pending'
        && (int)$row['is_active']===0 && !empty($row['created_at']) && !empty($row['updated_at']),
        'migration preserves catalog values and fills only missing timestamps');
} catch (Throwable $e) {
    $failures[]='fixture aborted safely: '.get_class($e);
} finally {
    if ($schema_mutated) {
        if ($table_exists() && $row_id) {
            $deleted=$wpdb->delete($table,array('id'=>$row_id));
            if ($deleted!==1) { $failures[]='synthetic catalog row cleanup failed'; }
        }
        // Reapply canonical schema after the controlled DROP/ALTER even when an
        // assertion or intermediate installation step failed.
        $restored=ZMP_Mentor_Subjects::install();
        if ($restored!==true || !$table_exists() || $schema_check()!==true) { $failures[]='canonical schema restoration failed'; }
        $restored_nullability=$table_exists()?$timestamp_nullability():array();
        if (($restored_nullability['created_at']??'')!=='NO' || ($restored_nullability['updated_at']??'')!=='NO') {
            $failures[]='canonical timestamp nullability restoration failed';
        }
    }
    $final_count=$table_exists()?$count($table):null;
    if ($final_count!==$before_count) { $failures[]='catalog row-count baseline not restored'; }
    if ($count($mentors)!==$mentor_count) { $failures[]='mentor baseline changed'; }
}
if ($failures) { fwrite(STDERR,'FAIL: '.wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures),JSON_UNESCAPED_SLASHES)."\n"); exit(1); }
fwrite(STDOUT,'PASS: '.wp_json_encode(array('assertions'=>$assertions,'failures'=>array()),JSON_UNESCAPED_SLASHES)."\n");
