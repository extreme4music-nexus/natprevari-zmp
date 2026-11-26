<?php
/**
 * Behavior-specific .64 -> .65 whole-plugin migration retention fixture.
 * Run in three WP-CLI invocations: prepare (.64), verify (.65), cleanup (.65).
 * It is deliberately bound to one disposable local site/database and writes
 * its hash-only manifest outside the plugin tree.
 */
if (!defined('ABSPATH')) { exit(2); }
global $wpdb;

const ZMP65_MIGRATION_DB = 'zmp65_migration_synthetic';
const ZMP65_MIGRATION_PREFIX = 'z65m_';
const ZMP65_MIGRATION_SITE = '/home/tonic/zmp-local-sandbox/phase65-test/site-migration';
const ZMP65_MIGRATION_TEMP_ROOT = '/home/tonic/zmp-local-sandbox/phase65-test/tmp';
const ZMP65_MIGRATION_MANIFEST = '/home/tonic/zmp-local-sandbox/phase65-test/tmp/phase65-whole-plugin-migration/manifest.json';
const ZMP65_MIGRATION_DIGEST = '/home/tonic/zmp-local-sandbox/phase65-test/tmp/phase65-whole-plugin-migration/manifest.sha256';
const ZMP65_MIGRATION_SOURCE64 = '/home/tonic/zmp-local-sandbox/phase65-test/tmp/phase65-whole-plugin-migration/source-1.2.4.64.sha256';
const ZMP65_MIGRATION_SOURCE65 = '/home/tonic/zmp-local-sandbox/phase65-test/tmp/phase65-whole-plugin-migration/source-1.2.4.65.sha256';

$source_manifests = array(
    '1.2.4.64' => array('path'=>ZMP65_MIGRATION_SOURCE64,'sha256'=>'F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0'),
    '1.2.4.65' => array('path'=>ZMP65_MIGRATION_SOURCE65,'sha256'=>'40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB'),
);

$fail = static function ($code) {
    fwrite(STDERR, wp_json_encode(array('status'=>'BLOCKED','error_code'=>$code), JSON_UNESCAPED_SLASHES) . "\n");
    exit(3);
};
$action = getenv('ZMP_PHASE65_MIGRATION_ACTION');
if (!in_array($action, array('prepare','verify','cleanup'), true)) { $fail('action_not_allowed'); }
$temp_root = realpath(ZMP65_MIGRATION_TEMP_ROOT);
$task_dir = dirname(ZMP65_MIGRATION_MANIFEST);
if ($temp_root !== ZMP65_MIGRATION_TEMP_ROOT || is_link(ZMP65_MIGRATION_TEMP_ROOT)
    || !is_dir($task_dir) || is_link($task_dir) || realpath($task_dir) !== $task_dir
    || dirname($task_dir) !== $temp_root) { $fail('unsafe_task_temp_path'); }
foreach (array(ZMP65_MIGRATION_SOURCE64,ZMP65_MIGRATION_SOURCE65,ZMP65_MIGRATION_MANIFEST,ZMP65_MIGRATION_DIGEST) as $path) {
    if (is_link($path) || (file_exists($path) && !is_file($path)) || realpath(dirname($path)) !== $task_dir) { $fail('unsafe_task_file_path'); }
}
if (($wpdb->dbname ?? '') !== ZMP65_MIGRATION_DB || (string)$wpdb->prefix !== ZMP65_MIGRATION_PREFIX) { $fail('wrong_synthetic_database'); }
if ((string)$wpdb->users !== ZMP65_MIGRATION_PREFIX . 'users') { $fail('unexpected_user_table'); }
$site_root = realpath(ABSPATH);
$expected_site = realpath(ZMP65_MIGRATION_SITE);
$plugin_root = realpath(ZMP_PLUGIN_DIR);
if (!$site_root || !$expected_site || $site_root !== $expected_site || !$plugin_root
    || $plugin_root !== realpath(ZMP65_MIGRATION_SITE . '/wp-content/plugins/zmp-competitions')) { $fail('wrong_site_path'); }
$expected_version = $action === 'prepare' ? '1.2.4.64' : '1.2.4.65';
if (!defined('ZMP_VERSION') || ZMP_VERSION !== $expected_version || !defined('ZMP_SCHEMA_VERSION') || ZMP_SCHEMA_VERSION !== $expected_version) { $fail('wrong_plugin_version'); }
if ($action === 'prepare') {
    if (get_option('zmp_db_version') !== '1.2.4.64' || get_option('zmp_role_version') !== '1.2.4.64'
        || !ZMP_Mentor_Schools::schema_is_compatible()
        || !ZMP_Mentor_School_Profile::schema_is_compatible()
        || !ZMP_School_Email_Access::schema_is_compatible()) { $fail('baseline_schema_not_exact_64'); }
    // The exact .64 subject class has no schema_is_compatible() API. Its
    // activation has already run under the exact .64 source/markers above;
    // inventory all existing zmp_ tables below rather than calling a .65-only
    // readiness method on the baseline.
}
$source_spec = $source_manifests[$expected_version];
if (!is_file($source_spec['path']) || !hash_equals(strtolower($source_spec['sha256']), strtolower((string)hash_file('sha256',$source_spec['path'])))) { $fail('source_manifest_hash_mismatch'); }
$source_lines = file($source_spec['path'], FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
if (!is_array($source_lines) || !$source_lines) { $fail('source_manifest_unreadable'); }
$expected_files = array();
foreach ($source_lines as $line) {
    if (!preg_match('/^([A-Fa-f0-9]{64})  zmp-competitions\/([a-zA-Z0-9_.\/-]+)$/', $line, $matches)
        || strpos($matches[2], '..') !== false || isset($expected_files[$matches[2]])) { $fail('source_manifest_invalid'); }
    $expected_files[$matches[2]] = strtolower($matches[1]);
}
$actual_files = array();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ZMP_PLUGIN_DIR, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $entry) {
    if ($entry->isLink()) { $fail('plugin_source_symlink_forbidden'); }
    if (!$entry->isFile()) { continue; }
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($entry->getPathname(), strlen(ZMP_PLUGIN_DIR)));
    if (!isset($expected_files[$relative]) || !hash_equals($expected_files[$relative], strtolower((string)hash_file('sha256',$entry->getPathname())))) { $fail('plugin_source_tree_mismatch'); }
    $actual_files[$relative] = true;
}
if (count($actual_files) !== count($expected_files)) { $fail('plugin_source_file_set_mismatch'); }

$row_hash = static function ($table, $id, $columns) use ($wpdb) {
    if (!preg_match('/^[a-z0-9_]+$/i', $table) || !preg_match('/^[a-z0-9_]+$/i', $id)) { return false; }
    foreach ($columns as $column) { if (!preg_match('/^[a-z0-9_]+$/i', $column)) { return false; } }
    $select = implode(',', array_map(static function ($column) { return '`' . $column . '`'; }, $columns));
    $row = $wpdb->get_row($wpdb->prepare("SELECT $select FROM `{$wpdb->prefix}{$table}` WHERE id=%d", (int)$id), ARRAY_A);
    return is_array($row) ? hash('sha256', wp_json_encode($row)) : false;
};
$columns = array(
    'competition' => array('title','academic_year','current_phase','duration_minutes','is_active','workflow_status'),
    'user' => array('user_login','user_email','display_name','user_status','user_registered'),
    'mentor' => array('wp_user_id','full_name','email','phone','school_name','municipality','region'),
    'school' => array('mentor_id','school_name','municipality','region','school_key','is_primary','is_active'),
    'student' => array('mentor_id','mentor_school_id','competition_id','first_name','last_name','grade','language','school_name','municipality','region','access_code','current_phase','qualified_for_next'),
    'registration' => array('season_id','file_key','drive_file_id','storage_backend','original_name','mime_type','file_size','sha256','updated_by'),
);

// Capture all data for every plugin-owned table. Raw row values never leave
// this request: the manifest contains column names and sorted SHA-256 row/key
// fingerprints only. During migration comparison, only baseline columns are
// selected so additive columns are allowed.
$inventory = static function ($baseline = null) use ($wpdb, $fail) {
    $wpdb->last_error = '';
    $pattern = $wpdb->esc_like($wpdb->prefix . 'zmp_') . '%';
    $names = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $pattern));
    if (!is_array($names) || $wpdb->last_error !== '') { $fail('plugin_table_inventory_failed'); }
    sort($names, SORT_STRING);
    $tables = array();
    foreach ($names as $table) {
        $table = (string)$table;
        $prefix = (string)$wpdb->prefix . 'zmp_';
        $suffix = substr($table, strlen($prefix));
        if (strpos($table, $prefix) !== 0 || $suffix === '' || !preg_match('/^[a-z0-9_]+$/i', $suffix)) { $fail('plugin_table_name_invalid'); }
        $quoted = '`' . $table . '`';
        $wpdb->last_error = '';
        $columns = $wpdb->get_results('SHOW COLUMNS FROM ' . $quoted, ARRAY_A);
        if (!is_array($columns) || !$columns || $wpdb->last_error !== '') { $fail('plugin_table_columns_unreadable'); }
        $column_names = array();
        foreach ($columns as $column) {
            $name = (string)($column['Field'] ?? '');
            if ($name === '' || !preg_match('/^[a-z0-9_]+$/i', $name)) { $fail('plugin_column_name_invalid'); }
            $column_names[] = $name;
        }
        $wpdb->last_error = '';
        $indexes = $wpdb->get_results('SHOW INDEX FROM ' . $quoted, ARRAY_A);
        if (!is_array($indexes) || $wpdb->last_error !== '') { $fail('plugin_table_primary_key_unreadable'); }
        $indexes = array_values(array_filter($indexes, static function ($index) { return (string)($index['Key_name'] ?? '') === 'PRIMARY'; }));
        usort($indexes, static function ($left, $right) { return (int)($left['Seq_in_index'] ?? 0) <=> (int)($right['Seq_in_index'] ?? 0); });
        $primary = array();
        foreach ($indexes as $index) {
            $column = (string)($index['Column_name'] ?? '');
            if (!in_array($column, $column_names, true)) { $fail('plugin_table_primary_key_invalid'); }
            $primary[] = $column;
        }
        if (is_array($baseline) && isset($baseline[$table]['columns'])) {
            $selected_columns = $baseline[$table]['columns'];
            foreach ($selected_columns as $selected_column) {
                if (!in_array($selected_column, $column_names, true)) { $fail('baseline_column_missing'); }
            }
        } else {
            $selected_columns = $column_names;
        }
        if (!$selected_columns) { $fail('plugin_table_no_columns'); }
        $select = implode(',', array_map(static function ($column) { return '`' . $column . '`'; }, $selected_columns));
        if ($table === $wpdb->prefix . 'zmp_subject_catalog') {
            foreach (array('id','created_at','updated_at') as $required) { if (!in_array($required, $column_names, true)) { $fail('subject_catalog_timestamp_columns_missing'); } }
        }
        $wpdb->last_error = '';
        $rows = $wpdb->get_results('SELECT ' . $select . ' FROM ' . $quoted, ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error !== '') { $fail('plugin_table_rows_unreadable'); }
        $key_hashes = array(); $row_hashes = array(); $content_hashes = array(); $timestamp_values = array(); $null_timestamps = array('created_at'=>0,'updated_at'=>0);
        foreach ($rows as $row) {
            $key = array();
            if ($primary) { foreach ($primary as $column) { $key[$column] = $row[$column] ?? null; } }
            $key_hash = $primary ? hash('sha256', serialize($key)) : '';
            $content = $row;
            if ($table === $wpdb->prefix . 'zmp_subject_catalog') {
                foreach (array('created_at','updated_at') as $timestamp) {
                    if (!isset($row[$timestamp]) || $row[$timestamp] === null) { $null_timestamps[$timestamp]++; }
                    $timestamp_value = $row[$timestamp] ?? null;
                    $timestamp_values[$key_hash][$timestamp] = $timestamp_value === null ? null : hash('sha256', (string)$timestamp_value);
                    // .65 intentionally backfills legacy NULL values. Preserve
                    // non-NULL timestamps exactly and validate NULL backfills below.
                    unset($content[$timestamp]);
                }
            }
            $content_hash = hash('sha256', serialize($content));
            $content_hashes[] = $primary ? hash('sha256', $key_hash . ':' . $content_hash) : $content_hash;
            if ($primary) {
                $key_hashes[] = $key_hash;
            } else {
                $row_hashes[] = $content_hash;
            }
        }
        sort($key_hashes, SORT_STRING); sort($row_hashes, SORT_STRING); sort($content_hashes, SORT_STRING);
        $tables[$table] = array('columns'=>$selected_columns,'primary_key'=>$primary,'row_count'=>count($rows),'key_hashes'=>$key_hashes,'row_hashes'=>$row_hashes,'content_hashes'=>$content_hashes,'timestamp_values'=>$timestamp_values,'null_timestamps'=>$null_timestamps);
    }
    return $tables;
};

$compare_inventory = static function ($baseline, $current) use ($wpdb) {
    $failed = array();
    $row_deltas = array();
    foreach ($baseline as $table=>$before) {
        if (!isset($current[$table])) { $failed[] = $table; continue; }
        $after = $current[$table];
        if (array_diff($before['columns'], $after['columns'])) { $failed[] = $table . ':baseline_column_missing'; }
        if ($before['primary_key'] !== $after['primary_key']) { $failed[] = $table; continue; }
        // Activation may add canonical/default rows while migrating old data.
        // Acceptance is that every baseline row (including duplicate row
        // multiplicity) remains present and unchanged; exact total equality
        // incorrectly rejects valid additive migration behavior.
        $row_deltas[$table] = array('baseline_rows'=>(int)$before['row_count'],'current_rows'=>(int)$after['row_count'],'added_rows'=>max(0,(int)$after['row_count']-(int)$before['row_count']));
        if ($before['primary_key']) {
            $required = array_count_values($before['content_hashes']);
            $available = array_count_values($after['content_hashes']);
            foreach ($required as $hash=>$count) { if (($available[$hash] ?? 0) < $count) { $failed[] = $table . ':baseline_row_missing_or_changed'; break; } }
            foreach (($before['timestamp_values'] ?? array()) as $key_hash=>$timestamps) {
                foreach ($timestamps as $column=>$value) {
                    $after_value = $after['timestamp_values'][$key_hash][$column] ?? null;
                    if ($value !== null && !is_string($after_value) || ($value !== null && !hash_equals($value, (string)$after_value))) { $failed[] = $table . ':' . $column . '_changed'; }
                    if ($value === null && $after_value === null) { $failed[] = $table . ':' . $column . '_not_backfilled'; }
                }
            }
        } else {
            $required = array_count_values($before['row_hashes']);
            $available = array_count_values($after['row_hashes']);
            foreach ($required as $hash=>$count) { if (($available[$hash] ?? 0) < $count) { $failed[] = $table . ':baseline_row_missing_or_changed'; break; } }
        }
    }
    $catalog = $wpdb->prefix . 'zmp_subject_catalog';
    if (isset($current[$catalog])) {
        // .65 deliberately backfills legacy NULL catalog timestamps.
        if (($current[$catalog]['null_timestamps']['created_at'] ?? 0) !== 0 || ($current[$catalog]['null_timestamps']['updated_at'] ?? 0) !== 0) { $failed[] = $catalog . ':timestamp_backfill'; }
    }
    return array('failures'=>array_values(array_unique($failed)),'row_deltas'=>$row_deltas);
};

$inventory_digest = static function ($tables) {
    return hash('sha256', serialize($tables));
};

$read_manifest = static function () use ($fail) {
    if (!is_file(ZMP65_MIGRATION_MANIFEST) || !is_file(ZMP65_MIGRATION_DIGEST)) { $fail('manifest_missing'); }
    $json = file_get_contents(ZMP65_MIGRATION_MANIFEST);
    $digest = trim((string)file_get_contents(ZMP65_MIGRATION_DIGEST));
    if (!is_string($json) || !preg_match('/^[a-f0-9]{64}$/', $digest) || !hash_equals($digest, hash('sha256', $json))) { $fail('manifest_digest_mismatch'); }
    $manifest = json_decode($json, true);
    if (!is_array($manifest) || ($manifest['schema'] ?? '') !== 'zmp65-whole-plugin-migration-v2'
        || ($manifest['database'] ?? '') !== ZMP65_MIGRATION_DB || ($manifest['prefix'] ?? '') !== ZMP65_MIGRATION_PREFIX
        || !isset($manifest['token'], $manifest['ids'], $manifest['row_hashes'], $manifest['plugin_inventory'])
        || !is_array($manifest['plugin_inventory']) || !$manifest['plugin_inventory']) { $fail('manifest_invalid'); }
    return $manifest;
};

if ($action === 'prepare') {
    if (file_exists(ZMP65_MIGRATION_MANIFEST) || file_exists(ZMP65_MIGRATION_DIGEST)) { $fail('manifest_already_exists'); }
    $token = bin2hex(random_bytes(12));
    $login = 'zmp65mig_' . $token;
    $user_id = 0; $mentor_id = 0; $school_id = 0; $student_id = 0; $registration_id = 0;
    $competition_id = 0;
    $manifest_created = false; $digest_created = false;
    try {
        $competition_ok = $wpdb->insert($wpdb->prefix . 'zmp_competitions', array('title'=>'Synthetic migration ' . $token,'academic_year'=>'2098/2099','current_phase'=>'school','duration_minutes'=>45,'is_active'=>0,'workflow_status'=>'active'));
        if ($competition_ok !== 1) { throw new RuntimeException('insert_competition'); }
        $competition_id = (int)$wpdb->insert_id;
        $user_id = wp_insert_user(array('user_login'=>$login,'user_pass'=>wp_generate_password(48,true,true),'user_email'=>$login . '@example.invalid','display_name'=>'Synthetic migration ' . $token,'role'=>'zmp_teacher'));
        if (is_wp_error($user_id) || (int)$user_id < 1) { $user_id = 0; throw new RuntimeException('insert_user'); }
        $user_id = (int)$user_id;
        if ($wpdb->insert($wpdb->prefix . 'zmp_mentors', array('wp_user_id'=>$user_id,'full_name'=>'Synthetic migration ' . $token,'email'=>$login . '@example.invalid','phone'=>'5550100','school_name'=>'Synthetic Migration School ' . $token,'municipality'=>'Скопје','region'=>'skopje','login_token'=>hash('sha256',$token))) !== 1) { throw new RuntimeException('insert_mentor'); }
        $mentor_id = (int)$wpdb->insert_id;
        $school_name = 'Synthetic Migration School ' . $token;
        $school_key = ZMP_Mentor_Schools::school_key($school_name, 'Скопје');
        if ($wpdb->insert($wpdb->prefix . 'zmp_mentor_schools', array('mentor_id'=>$mentor_id,'school_name'=>$school_name,'municipality'=>'Скопје','region'=>'skopje','region_is_override'=>0,'school_key'=>$school_key,'is_primary'=>1,'is_active'=>1)) !== 1) { throw new RuntimeException('insert_school'); }
        $school_id = (int)$wpdb->insert_id;
        $pin = 'synthetic-pin-' . $token;
        if ($wpdb->insert($wpdb->prefix . 'zmp_students', array('mentor_id'=>$mentor_id,'mentor_school_id'=>$school_id,'competition_id'=>$competition_id,'first_name'=>'Synthetic','last_name'=>'Migration ' . $token,'grade'=>8,'language'=>'mk','school_name'=>$school_name,'municipality'=>'Скопје','region'=>'skopje','access_code'=>'Z65M' . strtoupper($token),'access_pin'=>wp_hash_password($pin),'access_pin_hash'=>hash('sha256',$pin),'current_phase'=>'school','qualified_for_next'=>0)) !== 1) { throw new RuntimeException('insert_student'); }
        $student_id = (int)$wpdb->insert_id;
        if ($wpdb->insert($wpdb->prefix . 'zmp_registration_forms', array('season_id'=>209899,'file_key'=>'z65m-' . $token,'drive_file_id'=>'','storage_backend'=>'local','original_name'=>'synthetic-migration.pdf','mime_type'=>'application/pdf','file_size'=>0,'sha256'=>hash('sha256','registration-' . $token),'updated_by'=>1,'updated_at'=>current_time('mysql'))) !== 1) { throw new RuntimeException('insert_registration'); }
        $registration_id = (int)$wpdb->insert_id;
        $ids = array('competition'=>$competition_id,'user'=>$user_id,'mentor'=>$mentor_id,'school'=>$school_id,'student'=>$student_id,'registration'=>$registration_id);
        $tables = array('competition'=>'zmp_competitions','user'=>'users','mentor'=>'zmp_mentors','school'=>'zmp_mentor_schools','student'=>'zmp_students','registration'=>'zmp_registration_forms');
        $hashes = array(); foreach ($tables as $key=>$table) { $hashes[$key]=$row_hash($table,$ids[$key],$columns[$key]); if (!$hashes[$key]) { throw new RuntimeException('snapshot_' . $key); } }
        $plugin_inventory = $inventory();
        if (!$plugin_inventory) { throw new RuntimeException('snapshot_plugin_inventory_empty'); }
        $manifest = array('schema'=>'zmp65-whole-plugin-migration-v2','database'=>ZMP65_MIGRATION_DB,'prefix'=>ZMP65_MIGRATION_PREFIX,'baseline_version'=>'1.2.4.64','token'=>$token,'ids'=>$ids,'row_hashes'=>$hashes,'plugin_inventory'=>$plugin_inventory,'source_manifest_sha256'=>strtolower($source_spec['sha256']));
        $json = wp_json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $handle = @fopen(ZMP65_MIGRATION_MANIFEST, 'x');
        if (!$handle) { throw new RuntimeException('manifest_create'); }
        $manifest_created = true;
        if (fwrite($handle, $json) !== strlen($json)) { fclose($handle); throw new RuntimeException('manifest_create'); }
        fclose($handle);
        $digest_handle = @fopen(ZMP65_MIGRATION_DIGEST, 'x');
        if (!$digest_handle) { throw new RuntimeException('manifest_digest_create'); }
        $digest_created = true;
        if (fwrite($digest_handle, hash('sha256',$json)) !== 64) { fclose($digest_handle); throw new RuntimeException('manifest_digest_create'); }
        fclose($digest_handle);
        echo wp_json_encode(array('status'=>'PASS','action'=>'prepare','version'=>ZMP_VERSION,'synthetic_rows'=>count($hashes),'manifest_sha256'=>hash('sha256',$json)), JSON_UNESCAPED_SLASHES) . "\n";
    } catch (Throwable $e) {
        // Delete only rows created in this invocation and recognizable by this token.
        if ($registration_id) { $wpdb->delete($wpdb->prefix.'zmp_registration_forms',array('id'=>$registration_id,'file_key'=>'z65m-'.$token)); }
        if ($student_id) { $wpdb->delete($wpdb->prefix.'zmp_students',array('id'=>$student_id,'access_code'=>'Z65M'.strtoupper($token))); }
        if ($school_id) { $wpdb->delete($wpdb->prefix.'zmp_mentor_schools',array('id'=>$school_id,'mentor_id'=>$mentor_id,'school_key'=>ZMP_Mentor_Schools::school_key('Synthetic Migration School '.$token,'Скопје'))); }
        if ($mentor_id) { $wpdb->delete($wpdb->prefix.'zmp_mentors',array('id'=>$mentor_id,'wp_user_id'=>$user_id,'login_token'=>hash('sha256',$token))); }
        if ($user_id) { wp_delete_user($user_id); }
        if ($competition_id) { $wpdb->delete($wpdb->prefix.'zmp_competitions',array('id'=>$competition_id,'title'=>'Synthetic migration '.$token)); }
        if ($manifest_created && is_file(ZMP65_MIGRATION_MANIFEST) && !is_link(ZMP65_MIGRATION_MANIFEST) && realpath(dirname(ZMP65_MIGRATION_MANIFEST)) === $task_dir) { @unlink(ZMP65_MIGRATION_MANIFEST); }
        if ($digest_created && is_file(ZMP65_MIGRATION_DIGEST) && !is_link(ZMP65_MIGRATION_DIGEST) && realpath(dirname(ZMP65_MIGRATION_DIGEST)) === $task_dir) { @unlink(ZMP65_MIGRATION_DIGEST); }
        fwrite(STDERR, wp_json_encode(array('status'=>'FAIL','error_code'=>'synthetic_prepare_failed','cleanup_db_error'=>$wpdb->last_error!==''), JSON_UNESCAPED_SLASHES) . "\n");
        exit(1);
    }
    exit(0);
}

$manifest = $read_manifest();
if (($manifest['baseline_version'] ?? '') !== '1.2.4.64' || ($manifest['source_manifest_sha256'] ?? '') !== strtolower($source_manifests['1.2.4.64']['sha256'])) { $fail('baseline_identity_mismatch'); }
$ids = $manifest['ids']; $checks = array(); $failures = array();
$token = (string)$manifest['token'];
if (!preg_match('/^[a-f0-9]{24}$/', $token) || !is_array($ids)
    || !isset($ids['competition'],$ids['user'],$ids['mentor'],$ids['school'],$ids['student'],$ids['registration'])) { $fail('manifest_ids_invalid'); }
$owned_user = get_user_by('id',(int)$ids['user']);
if (!$owned_user || (string)$owned_user->user_login !== 'zmp65mig_' . $token || (string)$owned_user->user_email !== 'zmp65mig_' . $token . '@example.invalid') { $fail('synthetic_user_ownership_mismatch'); }
$owned_checks = array(
    (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE id=%d AND title=%s",(int)$ids['competition'],'Synthetic migration '.$token)),
    (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentors WHERE id=%d AND wp_user_id=%d AND login_token=%s",(int)$ids['mentor'],(int)$ids['user'],hash('sha256',$token))),
    (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentor_schools WHERE id=%d AND mentor_id=%d AND school_key=%s",(int)$ids['school'],(int)$ids['mentor'],ZMP_Mentor_Schools::school_key('Synthetic Migration School '.$token,'Скопје'))),
    (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_students WHERE id=%d AND mentor_id=%d AND access_code=%s",(int)$ids['student'],(int)$ids['mentor'],'Z65M'.strtoupper($token))),
    (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_registration_forms WHERE id=%d AND file_key=%s",(int)$ids['registration'],'z65m-'.$token)),
);
if (in_array(0,$owned_checks,true)) { $fail('synthetic_row_ownership_mismatch'); }
$marker_counts = array(
    'competition'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE title=%s",'Synthetic migration '.$token)),
    'mentor'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentors WHERE login_token=%s",hash('sha256',$token))),
    'school'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentor_schools WHERE school_key=%s",ZMP_Mentor_Schools::school_key('Synthetic Migration School '.$token,'Скопје'))),
    'student'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_students WHERE access_code=%s",'Z65M'.strtoupper($token))),
    'registration'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_registration_forms WHERE file_key=%s",'z65m-'.$token)),
);
$check = static function ($condition, $name) use (&$checks,&$failures) { $checks[]=array('assertion'=>$name,'result'=>$condition?'PASS':'FAIL'); if (!$condition) { $failures[]=$name; } };
$current_inventory = $inventory($manifest['plugin_inventory']);
$migration_comparison = $compare_inventory($manifest['plugin_inventory'], $current_inventory);
$check(!$migration_comparison['failures'], 'retained_all_baseline_plugin_rows');
$tables = array('competition'=>'zmp_competitions','user'=>'users','mentor'=>'zmp_mentors','school'=>'zmp_mentor_schools','student'=>'zmp_students','registration'=>'zmp_registration_forms');
foreach ($tables as $key=>$table) { $actual=$row_hash($table,(int)$ids[$key],$columns[$key]); $check($actual && hash_equals((string)$manifest['row_hashes'][$key],$actual),'retained_'.$key.'_hash'); }
if ($action === 'verify') {
    foreach ($marker_counts as $key=>$count) { $check($count===1,'unique_synthetic_'.$key.'_marker'); }
    $check(get_option('zmp_db_version') === '1.2.4.65','schema_version_marker');
    $check(get_option('zmp_role_version') === '1.2.4.65','role_version_marker');
    $check(ZMP_Mentor_Schools::schema_is_compatible(),'mentor_school_schema_ready');
    $check(ZMP_Mentor_School_Profile::schema_is_compatible(),'school_profile_schema_ready');
    // The subject class exposes a safe query, not a public compatibility API.
    // The version marker is committed only when install() reports subject
    // schema readiness; this query separately confirms catalog availability.
    $approved_subjects = ZMP_Mentor_Subjects::approved_subjects();
    $check(!is_wp_error($approved_subjects),'mentor_subject_catalog_query_ready');
    $check(ZMP_Mentor_Subject_Proposals::schema_is_compatible(),'subject_proposal_schema_ready');
    $check(ZMP_School_Email_Access::schema_is_compatible(),'school_email_schema_ready');
    $before_inventory = $inventory();
    zmp_competitions_activate();
    $after_inventory = $inventory();
    $check(hash_equals($inventory_digest($before_inventory),$inventory_digest($after_inventory)),'repeat_activation_no_plugin_row_changes');
    foreach ($tables as $key=>$table) { $actual=$row_hash($table,(int)$ids[$key],$columns[$key]); $check($actual && hash_equals((string)$manifest['row_hashes'][$key],$actual),'repeat_preserve_'.$key); }
    $check($wpdb->last_error==='','wpdb_has_no_error');
    echo wp_json_encode(array('status'=>$failures?'FAIL':'PASS','action'=>'verify','assertion_count'=>count($checks),'failures'=>$failures,'checks'=>$checks,'baseline_plugin_table_count'=>count($manifest['plugin_inventory']),'baseline_row_comparison_failures'=>$migration_comparison['failures'],'migration_row_deltas'=>$migration_comparison['row_deltas'],'repeat_activation_inventory_sha256_before'=>$inventory_digest($before_inventory),'repeat_activation_inventory_sha256_after'=>$inventory_digest($after_inventory),'version'=>ZMP_VERSION,'database'=>ZMP65_MIGRATION_DB,'prefix'=>ZMP65_MIGRATION_PREFIX,'source_manifest_sha256'=>$source_spec['sha256'],'manifest_sha256'=>hash_file('sha256',ZMP65_MIGRATION_MANIFEST)), JSON_UNESCAPED_SLASHES) . "\n";
    exit($failures?1:0);
}

// Explicit cleanup is a separate invocation so a failed verify still leaves
// the synthetic evidence available until its diagnostics are recorded.
$cleanup = array();
$cleanup['registration'] = $wpdb->delete($wpdb->prefix.'zmp_registration_forms',array('id'=>(int)$ids['registration'],'file_key'=>'z65m-'.$token,'sha256'=>hash('sha256','registration-'.$token)));
$cleanup['student'] = $wpdb->delete($wpdb->prefix.'zmp_students',array('id'=>(int)$ids['student'],'mentor_id'=>(int)$ids['mentor'],'competition_id'=>(int)$ids['competition'],'access_code'=>'Z65M'.strtoupper($token)));
$cleanup['school'] = $wpdb->delete($wpdb->prefix.'zmp_mentor_schools',array('id'=>(int)$ids['school'],'mentor_id'=>(int)$ids['mentor'],'school_key'=>ZMP_Mentor_Schools::school_key('Synthetic Migration School '.$token,'Скопје')));
$cleanup['mentor'] = $wpdb->delete($wpdb->prefix.'zmp_mentors',array('id'=>(int)$ids['mentor'],'wp_user_id'=>(int)$ids['user'],'login_token'=>hash('sha256',$token)));
$user_deleted = wp_delete_user((int)$ids['user']);
$cleanup['competition'] = $wpdb->delete($wpdb->prefix.'zmp_competitions',array('id'=>(int)$ids['competition'],'title'=>'Synthetic migration '.$token));
$remaining = array(
    'registration'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_registration_forms WHERE file_key=%s",'z65m-'.$token)),
    'student'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_students WHERE access_code=%s",'Z65M'.strtoupper($token))),
    'school'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentor_schools WHERE school_key=%s",ZMP_Mentor_Schools::school_key('Synthetic Migration School '.$token,'Скопје'))),
    'mentor'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_mentors WHERE login_token=%s",hash('sha256',$token))),
    'user'=>get_user_by('id',(int)$ids['user'])?1:0,
    'competition'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE id=%d AND title=%s",(int)$ids['competition'],'Synthetic migration '.$token)),
    'competition_marker'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}zmp_competitions WHERE title=%s",'Synthetic migration '.$token)),
);
$markers_unique_before_cleanup = true;
foreach ($marker_counts as $count) { if ($count !== 1) { $markers_unique_before_cleanup = false; } }
$cleanup_ok = $markers_unique_before_cleanup && $cleanup['registration']===1 && $cleanup['student']===1 && $cleanup['school']===1 && $cleanup['mentor']===1 && $user_deleted && $cleanup['competition']===1 && array_sum($remaining)===0 && $wpdb->last_error==='';
if ($cleanup_ok) { @unlink(ZMP65_MIGRATION_MANIFEST); @unlink(ZMP65_MIGRATION_DIGEST); }
echo wp_json_encode(array('status'=>$cleanup_ok?'PASS':'FAIL','action'=>'cleanup','duplicate_markers_before_cleanup'=>array_filter($marker_counts,static function($count){return $count!==1;}),'deleted_rows'=>$cleanup,'remaining'=>$remaining,'manifest_removed'=>$cleanup_ok), JSON_UNESCAPED_SLASHES) . "\n";
exit($cleanup_ok?0:1);
