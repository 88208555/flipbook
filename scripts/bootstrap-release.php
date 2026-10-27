<?php
/** First installation only. Creates fresh private config and a fresh founder, never resets existing users. */
if(PHP_SAPI !== 'cli') exit(1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$options = getopt('', ['root:', 'templates:', 'manifest:']);
$root = rtrim($options['root'] ?? '/var/www/html', '/');
$templates = rtrim($options['templates'] ?? $root, '/');
$manifestPath = $options['manifest'] ?? '/opt/flipbook/database/manifest.json';
$manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
$prefix = $manifest['tablePrefix'];
$env = fn($key, $fallback = '') => (($value = getenv($key)) !== false && $value !== '') ? $value : $fallback;
$dbname = $env('MYSQL_DATABASE', 'discuz');
$user = $env('MYSQL_USER', 'discuz');
$password = $env('MYSQL_PASSWORD');
$host = $env('MYSQL_HOST', 'mysql');
$site = rtrim($env('SITE_URL'), '/');
$admin = $env('BOOTSTRAP_ADMIN_USERNAME', 'admin');
$email = $env('BOOTSTRAP_ADMIN_EMAIL', 'admin@example.invalid');
$adminPassword = $env('BOOTSTRAP_ADMIN_PASSWORD');
if(!preg_match('/^[A-Za-z0-9_]+$/D', $dbname.$prefix)) throw new RuntimeException('Invalid database name or prefix');
if(strlen($password) < 16 || strlen($adminPassword) < 16) throw new RuntimeException('Use generated database and administrator passwords (at least 16 characters)');
if(!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($admin) < 3 || mb_strlen($admin) > 20) throw new RuntimeException('Invalid administrator name or email');
if(!preg_match('~^https?://[^\s/?#@]+(?::[0-9]+)?(?:/[^?#]*)?$~D', $site)) throw new RuntimeException('Set SITE_URL to the deployed site URL');
foreach(['config_global.php', 'config_ucenter.php'] as $name) if(file_exists($root.'/config/'.$name)) throw new RuntimeException('Existing private configuration detected; bootstrap is only for a new installation');
$db = new mysqli($host, $user, $password, $dbname);
$db->set_charset('utf8mb4');
if((int)$db->query('SELECT COUNT(*) FROM `'.$prefix.'common_member`')->fetch_row()[0] !== 0 || (int)$db->query('SELECT COUNT(*) FROM `'.$prefix.'ucenter_members`')->fetch_row()[0] !== 0) throw new RuntimeException('Existing accounts detected; refusing to overwrite this database');
foreach($manifest['schemaTables'] as $table) {
    if(!preg_match('/^[A-Za-z0-9_]+$/D', $table)) throw new RuntimeException('Invalid manifest table');
    $db->query('SELECT 1 FROM `'.$table.'` LIMIT 0');
}
$auth = bin2hex(random_bytes(32));
$dbConfig = ['dbhost' => $host, 'dbuser' => $user, 'dbpw' => $password, 'dbcharset' => 'utf8mb4', 'pconnect' => 0, 'dbname' => $dbname, 'tablepre' => $prefix];
$global = "<?php\nrequire __DIR__.'/config_global_default.php';\n\$_config['db'][1] = ".var_export($dbConfig, true).";\n";
$overrides = [
    "['security']['authkey']" => $auth,
    "['security']['error']['showerror']" => 0,
    "['cookie']['cookiepre']" => 'fb_'.bin2hex(random_bytes(4)).'_',
    "['memory']['prefix']" => 'fb_'.bin2hex(random_bytes(6)).'_',
    "['memory']['redis']['server']" => 'redis',
    "['memory']['redis']['port']" => 6379,
    "['remote']['appkey']" => bin2hex(random_bytes(24)),
    "['admincp']['founder']" => '1',
];
// Render values with PHP's own encoder, not shell interpolation.
foreach($overrides as $key => $value) $global .= '$_config'.$key.' = '.var_export($value, true).";\n";
$uc = <<<'PHP'
<?php
if(!isset($_config['db'][1])) require __DIR__.'/config_global.php';
define('UC_CONNECT', 'mysql');
define('UC_STANDALONE', 1);
define('UC_DBHOST', $_config['db'][1]['dbhost']);
define('UC_DBUSER', $_config['db'][1]['dbuser']);
define('UC_DBPW', $_config['db'][1]['dbpw']);
define('UC_DBNAME', $_config['db'][1]['dbname']);
define('UC_DBCHARSET', 'utf8mb4');
define('UC_DBTABLEPRE', '`'.UC_DBNAME.'`.'.$_config['db'][1]['tablepre'].'ucenter_');
define('UC_DBCONNECT', 0);
define('UC_KEY', $_config['security']['authkey']);
define('UC_AVTURL', '');
define('UC_AVTPATH', '');
define('UC_API', '');
define('UC_CHARSET', 'utf-8');
define('UC_IP', '');
define('UC_APPID', '1');
define('UC_PPP', 20);
PHP;
$directories = ['config', 'data', 'data/cache', 'data/sysdata', 'data/template', 'data/threadcache', 'data/log', 'data/temp', 'data/download', 'data/plugindata', 'data/addonmd5', 'data/avatar', 'data/attachment'];
foreach($directories as $dir) {
    if(!is_dir($root.'/'.$dir) && !mkdir($root.'/'.$dir, 0775, true)) throw new RuntimeException('Cannot create runtime directory');
    if(function_exists('posix_geteuid') && posix_geteuid() === 0) { chown($root.'/'.$dir, 33); chgrp($root.'/'.$dir, 33); }
}
if($templates !== $root && !copy($templates.'/config/config_global_default.php', $root.'/config/config_global_default.php')) throw new RuntimeException('Cannot copy configuration template');
$files = ['config/config_global.php' => $global, 'config/config_ucenter.php' => $uc."\n"];
try {
    foreach($files as $path => $value) {
        if(file_put_contents($root.'/'.$path.'.pending', $value, LOCK_EX) === false) throw new RuntimeException('Cannot write configuration');
        chmod($root.'/'.$path.'.pending', 0600);
        if(function_exists('posix_geteuid') && posix_geteuid() === 0) { chown($root.'/'.$path.'.pending', 33); chgrp($root.'/'.$path.'.pending', 33); }
    }
    $q = fn($s) => "'".$db->real_escape_string((string)$s)."'";
    $db->begin_transaction();
    $db->query('INSERT INTO `'.$prefix.'ucenter_members` (uid,username,password,email,regdate,salt) VALUES (1,'.$q($admin).','.$q(password_hash($adminPassword, PASSWORD_BCRYPT)).','.$q($email).','.time().",'')");
    $db->query('INSERT INTO `'.$prefix.'common_member` (uid,loginname,username,password,adminid,groupid,email,regdate,timeoffset) VALUES (1,'.$q($admin).','.$q($admin).','.$q(bin2hex(random_bytes(16))).',1,1,'.$q($email).','.time().",'9999')");
    foreach(['count', 'status', 'field_forum', 'field_home', 'profile'] as $part) {
        $table = $prefix.'common_member_'.$part;
        $values = ['uid' => '1'];
        foreach($db->query('SHOW COLUMNS FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC) as $column) {
            if($column['Field'] === 'uid' || $column['Null'] === 'YES' || $column['Default'] !== null || str_contains($column['Extra'], 'auto_increment')) continue;
            $values[$column['Field']] = $column['Type'] === 'json' ? "'{}'" : (preg_match('/^(?:tinyint|smallint|mediumint|int|bigint|decimal|float|double)/', $column['Type']) ? '0' : "''");
        }
        $db->query('INSERT INTO `'.$table.'` (`'.implode('`,`', array_keys($values)).'`) VALUES ('.implode(',', $values).')');
    }
    $settings = ['siteurl' => $site.'/', 'adminemail' => $email, 'siteuniqueid' => 'DX'.bin2hex(random_bytes(12)), 'authkey' => '', 'notifyusers' => serialize([1 => ['username' => $admin, 'types' => '11111111111111111111']])];
    foreach($settings as $key => $value) $db->query('REPLACE INTO `'.$prefix.'common_setting` (skey,svalue) VALUES ('.$q($key).','.$q($value).')');
    $db->query('REPLACE INTO `'.$prefix.'flipbook_sync_setting` (skey,svalue) VALUES (\'instanceid\','.$q(bin2hex(random_bytes(16))).')');
    foreach($files as $path => $_) if(!rename($root.'/'.$path.'.pending', $root.'/'.$path)) throw new RuntimeException('Cannot install configuration');
    $db->commit();
    touch($root.'/data/install.lock');
} catch(Throwable $error) {
    $db->rollback();
    foreach($files as $path => $_) if(file_exists($root.'/'.$path.'.pending')) unlink($root.'/'.$path.'.pending');
    throw $error;
}
echo "Initialized release with fresh founder and private configuration. Password remains in .env.\n";
