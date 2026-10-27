<?php
/** CLI-only, read-only database export: structure + explicitly selected publishable content. */
if(PHP_SAPI !== 'cli') exit(1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$options = getopt('', ['root:', 'output:']);
$root = rtrim($options['root'] ?? '/var/www/html', '/');
$out = rtrim($options['output'] ?? '/tmp/flipbook-release', '/');
if(!is_dir($out) && !mkdir($out, 0700, true)) throw new RuntimeException('Cannot create output directory');
foreach(['schema.sql', 'data.sql', 'manifest.json'] as $file) {
    if(file_exists($out.'/'.$file)) throw new RuntimeException('Output already exists; choose an empty directory');
}
$_config = [];
require $root.'/config/config_global.php';
$cfg = $_config['db'][1];
$db = new mysqli($cfg['dbhost'], $cfg['dbuser'], $cfg['dbpw'], $cfg['dbname']);
$db->set_charset('utf8mb4');
$db->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");
$db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
$prefix = $cfg['tablepre'];
if(!preg_match('/^[A-Za-z0-9_]+$/D', $prefix)) throw new RuntimeException('Unsupported table prefix');
define('IN_DISCUZ', true);
require $root.'/source/plugin/flipbook/front/front_service_config.php';
require $root.'/source/plugin/flipbook/front/front_order_store.php';
$serviceSchema = flipbook\front_service_config::schema();
$system = explode(' ', 'common_admincp_group common_admincp_menu_platform common_admincp_perm common_admingroup common_block_style common_credit_rule common_cron common_district common_editorblock common_friendlink common_member_profile_setting common_nav common_plugin common_pluginvar common_relatedlink common_secquestion common_setting common_smiley common_style common_stylevar common_stylevar_extra common_template common_usergroup common_usergroup_field common_word common_word_type forum_attachtype forum_bbcode forum_faq forum_forum forum_forumfield forum_grouplevel forum_imagetype forum_medal forum_onlinelist forum_typeoption home_album_category home_blog_category home_click portal_category ucenter_settings');
$content = explode(' ', 'flipbook_album flipbook_asset flipbook_category flipbook_micropage flipbook_page flipbook_page_product flipbook_product flipbook_product_media flipbook_series flipbook_setting flipbook_sku flipbook_supplier');
$allowed = array_fill_keys(array_merge($system, $content), true);
function scrubReleaseValue($value) {
    if(is_array($value)) {
        foreach($value as $key => $entry) {
            if(is_string($key) && preg_match('/(?:^|_)(?:password|passwd|pass|secret|token|authkey|appkey|sitekey|privatekey|private_key|api_key|secret_key|secret_id|keycipher|keyhash|username|email|mailfrom)$/i', $key)) $value[$key] = '';
            else $value[$key] = scrubReleaseValue($entry);
        }
        return $value;
    }
    if(!is_string($value)) return $value;
    if(str_starts_with($value, 'enc1:') || str_contains($value, 'PRIVATE KEY-----')) return '';
    if(preg_match('/^(?:a|s|i|b|d|N):/', $value)) {
        $decoded = @unserialize($value, ['allowed_classes' => false]);
        if($decoded !== false || $value === 'b:0;') return serialize(scrubReleaseValue($decoded));
    }
    if($value !== '' && in_array($value[0], ['[', '{'], true)) {
        $decoded = json_decode($value, true);
        if(is_array($decoded)) return json_encode(scrubReleaseValue($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return str_replace(['http://127.0.0.1:8088/', 'http://localhost:8088/'], '/', $value);
}
function releaseRow($table, $row, $serviceSchema) {
    if($table === 'flipbook_setting') {
        $key = $row['skey'];
        if(($serviceSchema[$key]['type'] ?? '') === 'secret') return null;
        if(preg_match('/^wechat_(?:enabled|orders_enabled|trade_enabled|push_enabled)$/D', $key)) $row['svalue'] = '0';
        if($key === 'storage_provider') $row['svalue'] = 'local';
        if(preg_match('/(?:secret|token|private_key|api_key|access_key)/i', $key)) return null;
        $row['svalue'] = scrubReleaseValue($row['svalue']);
    } elseif($table === 'common_setting') {
        $key = $row['skey'];
        if(in_array($key, ['authkey', 'my_sitekey', 'my_siteid', 'sitevipkey', 'siteuniqueid', 'statcode', 'notifyusers', 'cloudaddons_newversion', 'cloudaddons_recommendaddon', 'backupdir'], true)) return null;
        if($key === 'adminemail') $row['svalue'] = 'admin@example.invalid';
        elseif($key === 'siteurl') $row['svalue'] = 'http://localhost/';
        else $row['svalue'] = scrubReleaseValue($row['svalue']);
    } elseif($table === 'ucenter_settings') {
        if(preg_match('/^mail/', $row['k'])) return null;
        $row['v'] = scrubReleaseValue($row['v']);
    } elseif($table === 'common_pluginvar') {
        if(preg_match('/(?:password|secret|token|private_key|api_key)/i', $row['variable']) || ($row['type'] ?? '') === 'password') return null;
        $row['value'] = scrubReleaseValue($row['value']);
    } elseif($table === 'flipbook_supplier') {
        foreach(['contact', 'phone', 'address', 'note'] as $field) if(isset($row[$field])) $row[$field] = '';
    }
    foreach($row as $key => $value) {
        if(in_array($key, ['uid', 'authorid'], true)) $row[$key] = '0';
        elseif(is_string($value) && $table !== 'flipbook_micropage') $row[$key] = scrubReleaseValue($value);
    }
    if($table === 'common_cron') { $row['lastrun'] = '0'; $row['nextrun'] = (string)(time() + 3600); }
    return $row;
}
$schema = "-- Flipbook deployment schema; original table prefix: {$prefix}\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
$data = "-- Publishable application content. Accounts, sessions, orders, access keys and activity records excluded.\nSET NAMES utf8mb4;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSTART TRANSACTION;\n";
$manifest = ['release' => '2026.10.27', 'exportedAt' => date(DATE_ATOM), 'tablePrefix' => $prefix, 'schemaTables' => [], 'dataRows' => [], 'omittedNonemptyTables' => [], 'policy' => 'All table structures; selected system configuration, products, albums, SKU, assets and saved micropages. No member accounts/passwords/contact details, sessions/logs, orders/payments, encrypted credentials, sync authorization/source credentials or activity records.'];
$tables = $db->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetch_all(MYSQLI_NUM);
foreach($tables as [$name]) {
    if(!str_starts_with($name, $prefix) || !preg_match('/^[A-Za-z0-9_]+$/D', $name)) continue;
    $table = substr($name, strlen($prefix));
    $create = $db->query('SHOW CREATE TABLE `'.$name.'`')->fetch_row()[1];
    $schema .= preg_replace('/ AUTO_INCREMENT=\d+/', '', $create).";\n\n";
    $manifest['schemaTables'][] = $name;
    if(!isset($allowed[$table])) {
        $count = (int)$db->query('SELECT COUNT(*) FROM `'.$name.'`')->fetch_row()[0];
        if($count) $manifest['omittedNonemptyTables'][$name] = $count;
        continue;
    }
    $rows = $db->query('SELECT * FROM `'.$name.'`')->fetch_all(MYSQLI_ASSOC);
    $manifest['dataRows'][$name] = 0;
    foreach($rows as $row) {
        $row = releaseRow($table, $row, $serviceSchema);
        if($row === null) continue;
        $values = array_map(fn($v) => $v === null ? 'NULL' : "'".$db->real_escape_string((string)$v)."'", array_values($row));
        $data .= 'INSERT INTO `'.$name.'` (`'.implode('`,`', array_keys($row)).'`) VALUES ('.implode(',', $values).");\n";
        $manifest['dataRows'][$name]++;
    }
}
if(!in_array($prefix.'flipbook_order', $manifest['schemaTables'], true)) {
    $schema .= str_replace('%t', '`'.$prefix.'flipbook_order`', flipbook\front_order_store::schema()).";\n";
    $manifest['schemaTables'][] = $prefix.'flipbook_order';
}
$schema .= "SET FOREIGN_KEY_CHECKS=1;\n";
$data .= "COMMIT;\n";
$db->rollback();
file_put_contents($out.'/schema.sql', $schema);
file_put_contents($out.'/data.sql', $data);
$manifest['sha256'] = ['schema.sql' => hash('sha256', $schema), 'data.sql' => hash('sha256', $data)];
file_put_contents($out.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['schemaTables' => count($manifest['schemaTables']), 'contentRows' => array_sum($manifest['dataRows']), 'output' => $out], JSON_UNESCAPED_SLASHES)."\n";
