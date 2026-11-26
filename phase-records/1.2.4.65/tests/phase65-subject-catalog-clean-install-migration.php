<?php
/**
 * Synthetic acceptance fixture for catalog creation and nullable timestamp repair.
 * Run only on the disposable .65 site identified below. This fixture intentionally
 * drops only the empty subject catalog table in that isolated database.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('DB_NAME')
    || DB_NAME !== 'zmp65_subject_catalog_test') {
    fwrite(STDERR,"BLOCKED: dedicated disposable WP-CLI site required.\n"); exit(2);
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
    || (int)($site['port']??0)!==8100 || !defined('ZMP_VERSION') || ZMP_VERSION!=='1.2.4.65') {
    fwrite(STDERR,"BLOCKED: isolated .65 candidate identity mismatch.\n"); exit(2);
}
foreach ($source_hashes as $class=>$expected_hash) {
    if (!class_exists($class)) { fwrite(STDERR,"BLOCKED: required product service unavailable.\n"); exit(2); }
    $file=(new ReflectionClass($class))->getFileName();
    if (!is_string($file) || !is_file($file)
        || !hash_equals($expected_hash,strtoupper(hash_file('sha256',$file)))) {
        fwrite(STDERR,"BLOCKED: loaded product source differs from reviewed candidate.\n"); exit(2);
    }
}
$admins=get_users(array('role'=>'administrator','fields'=>'ID','number'=>2));
$users=count_users();
if (count((array)$admins)!==1 || (int)($users['total_users']??-1)!==1) {
    fwrite(STDERR,"BLOCKED: one-admin synthetic site required.\n"); exit(2);
}
$table=$wpdb->prefix.'zmp_subject_catalog';
$mentor_table=$wpdb->prefix.'zmp_mentors';
$table_exists=static function() use ($wpdb,$table) {
    $wpdb->last_error='';
    $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
    if ($wpdb->last_error!=='') { return null; }
    return (string)$found===$table;
};
$count=static function($name) use ($wpdb) {
    $wpdb->last_error='';
    $value=$wpdb->get_var('SELECT COUNT(*) FROM `'.esc_sql($name).'`');
    return $wpdb->last_error==='' && is_numeric($value)?(int)$value:null;
};
$catalog_schema_check=static function() {
    return (new ReflectionMethod('ZMP_Mentor_Subject_Proposals','catalog_schema_is_compatible'))->invoke(null);
};
$schema_contract=static function() use ($wpdb,$table,$catalog_schema_check) {
    $wpdb->last_error='';
    $columns=$wpdb->get_results('SHOW COLUMNS FROM `'.esc_sql($table).'`');
    if (!is_array($columns) || $wpdb->last_error!=='') { return false; }
    $found=array();
    foreach ($columns as $column) {
        $type=strtolower((string)($column->Type??''));
        $type=preg_replace('/\b(bigint|tinyint)\(\d+\)/','$1',$type);
        $found[(string)($column->Field??'')]=array(
            'type'=>$type,'null'=>strtoupper((string)($column->Null??'')),
            'extra'=>strtolower((string)($column->Extra??'')),
        );
    }
    $expected=array(
        'id'=>array('bigint unsigned','NO','auto_increment'),
        'subject_name'=>array('varchar(190)','NO',''),
        'subject_key'=>array('char(64)','NO',''),
        'status'=>array('varchar(20)','NO',''),
        'is_active'=>array('tinyint','NO',''),
        'created_by'=>array('bigint unsigned','NO',''),
        'approved_by'=>array('bigint unsigned','NO',''),
        'approved_at'=>array('datetime','YES',''),
        'created_at'=>array('datetime','NO',''),
        'updated_at'=>array('datetime','NO',''),
    );
    foreach ($expected as $name=>$shape) {
        if (!isset($found[$name]) || $found[$name]['type']!==$shape[0]
            || $found[$name]['null']!==$shape[1]
            || ($shape[2]!=='' && strpos($found[$name]['extra'],$shape[2])===false)) { return false; }
    }
    $wpdb->last_error='';
    $indexes=$wpdb->get_results('SHOW INDEX FROM `'.esc_sql($table).'`');
    if (!is_array($indexes) || $wpdb->last_error!=='') { return false; }
    $actual=array();
    foreach ($indexes as $row) {
        $name=(string)($row->Key_name??''); $seq=(int)($row->Seq_in_index??0);
        if ($name==='' || $seq<1) { continue; }
        $actual[$name]['columns'][$seq]=(string)($row->Column_name??'');
        $actual[$name]['unique']=((int)($row->Non_unique??1)===0);
    }
    $required=array(
        'PRIMARY'=>array(array('id'),true),
        'subject_key'=>array(array('subject_key'),true),
        'subject_status_active'=>array(array('status','is_active'),false),
    );
    foreach ($required as $name=>$shape) {
        if (!isset($actual[$name])) { return false; }
        ksort($actual[$name]['columns']);
        if (array_values($actual[$name]['columns'])!==$shape[0] || $actual[$name]['unique']!==$shape[1]) { return false; }
    }
    return $catalog_schema_check()===true;
};
$initial_exists=$table_exists();
$baseline_count=$initial_exists===true?$count($table):($initial_exists===false?0:null);
$baseline_mentors=$count($mentor_table);
if ($initial_exists===null || $baseline_count!==0 || $baseline_mentors!==0) {
    fwrite(STDERR,"BLOCKED: disposable catalog and mentor tables must be empty.\n"); exit(2);
}
$seed='zmp65-catalog-migration-'.wp_generate_password(18,false,false);
$subject_ids=array(
    'fixed'=>hash('sha256',$seed.'-fixed'),
    'null'=>hash('sha256',$seed.'-null'),
);
$row_ids=array(); $schema_mutated=false; $assertions=0; $failures=array();
$check=static function($ok,$label) use (&$assertions,&$failures) { $assertions++; if (!$ok) { $failures[]=$label; } };
$fixed_time='2021-02-03 04:05:06';
try {
    if ($initial_exists) {
        $drop=$wpdb->query('DROP TABLE `'.esc_sql($table).'`');
        if ($drop===false || $wpdb->last_error!=='') { throw new RuntimeException('empty scoped catalog drop failed'); }
    }
    $schema_mutated=true;
    if ($table_exists()!==false) { throw new RuntimeException('catalog was not absent before clean install'); }

    $clean_install=ZMP_Mentor_Subjects::install();
    $clean_contract=$table_exists()===true && $schema_contract();
    $check($clean_install===true && $clean_contract,'clean catalog install creates compatible typed/indexed schema with NOT NULL timestamps');
    if ($clean_install!==true || !$clean_contract) { throw new RuntimeException('clean catalog installation failed contract'); }

    foreach (array('fixed','null') as $label) {
        $time=$label==='fixed'?$fixed_time:current_time('mysql');
        $wpdb->last_error='';
        $inserted=$wpdb->insert($table,array(
            'subject_name'=>'Synthetic Migration '.ucfirst($label),
            'subject_key'=>$subject_ids[$label], 'status'=>'pending','is_active'=>0,
            'created_by'=>(int)$admins[0],'approved_by'=>0,'approved_at'=>null,
            'created_at'=>$time,'updated_at'=>$time,
        ));
        if ($inserted!==1) { throw new RuntimeException('synthetic migration row setup failed'); }
        $row_ids[$label]=(int)$wpdb->insert_id;
        if (!$row_ids[$label]) {
            $row_ids[$label]=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM `{$table}` WHERE subject_key=%s",$subject_ids[$label]));
        }
        if (!$row_ids[$label]) { throw new RuntimeException('synthetic migration row identity unavailable'); }
    }
    $alter=$wpdb->query('ALTER TABLE `'.esc_sql($table).'` MODIFY `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP, MODIFY `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
    if ($alter===false || $wpdb->last_error!=='') { throw new RuntimeException('controlled nullable drift setup failed'); }
    $schema_mutated=true;
    $nulled=$wpdb->update($table,array('created_at'=>null,'updated_at'=>null),array('id'=>$row_ids['null']));
    if ($nulled===false || $wpdb->last_error!=='') { throw new RuntimeException('synthetic NULL timestamp setup failed'); }
    $check($catalog_schema_check()===false,'nullable drift fails catalog schema compatibility');

    $upgrade=ZMP_Mentor_Subjects::install();
    $upgrade_contract=$schema_contract();
    $check($upgrade===true && $upgrade_contract,'upgrade repairs nullable columns and restores schema/type/index readiness');
    $fixed_row=$wpdb->get_row($wpdb->prepare("SELECT subject_name,subject_key,status,is_active,created_at,updated_at FROM `{$table}` WHERE id=%d",$row_ids['fixed']),ARRAY_A);
    $null_row=$wpdb->get_row($wpdb->prepare("SELECT subject_name,subject_key,status,is_active,created_at,updated_at FROM `{$table}` WHERE id=%d",$row_ids['null']),ARRAY_A);
    $check(is_array($fixed_row) && $fixed_row['subject_name']==='Synthetic Migration Fixed'
        && hash_equals($subject_ids['fixed'],(string)$fixed_row['subject_key']) && $fixed_row['status']==='pending'
        && (int)$fixed_row['is_active']===0 && $fixed_row['created_at']===$fixed_time && $fixed_row['updated_at']===$fixed_time,
        'upgrade preserves existing non-NULL values and timestamps');
    $check(is_array($null_row) && $null_row['subject_name']==='Synthetic Migration Null'
        && hash_equals($subject_ids['null'],(string)$null_row['subject_key']) && $null_row['status']==='pending'
        && (int)$null_row['is_active']===0 && !empty($null_row['created_at']) && !empty($null_row['updated_at']),
        'upgrade preserves other row values and fills NULL timestamps');
} catch (Throwable $e) {
    $failures[]='fixture aborted safely: '.get_class($e);
} finally {
    if ($schema_mutated) {
        if ($table_exists()===true) {
            foreach ($subject_ids as $label=>$subject_key) {
                $deleted=$wpdb->delete($table,array('subject_key'=>$subject_key));
                if ($deleted!==1) { $failures[]='owned catalog row cleanup failed'; }
            }
        }
        $restored=ZMP_Mentor_Subjects::install();
        if ($restored!==true || $table_exists()!==true || !$schema_contract()) { $failures[]='canonical catalog schema restoration failed'; }
        if ($count($table)!==$baseline_count) { $failures[]='catalog row-count baseline not restored'; }
    }
    if ($count($mentor_table)!==$baseline_mentors) { $failures[]='mentor baseline changed'; }
}
if ($failures) { fwrite(STDERR,'FAIL: '.wp_json_encode(array('assertions'=>$assertions,'failures'=>$failures),JSON_UNESCAPED_SLASHES)."\n"); exit(1); }
fwrite(STDOUT,'PASS: '.wp_json_encode(array('assertions'=>$assertions,'failures'=>array()),JSON_UNESCAPED_SLASHES)."\n");
