<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Storage owned exclusively by the synchronisation plugin. */
class service_store {
 public static function install() {
  $tables = [
   'flipbook_sync_key'=>"clientid int unsigned NOT NULL AUTO_INCREMENT,domain varchar(253) NOT NULL,keyhash char(64) NOT NULL,enabled tinyint NOT NULL DEFAULT 1,note varchar(150) NOT NULL DEFAULT '',ips text,created int unsigned NOT NULL,lastused int unsigned NOT NULL DEFAULT 0,PRIMARY KEY(clientid),UNIQUE KEY domain(domain)",
   'flipbook_sync_source'=>"sourceid int unsigned NOT NULL AUTO_INCREMENT,name varchar(100) NOT NULL,endpoint varchar(500) NOT NULL,domain varchar(253) NOT NULL,keycipher text,enabled tinyint NOT NULL DEFAULT 1,instanceid varchar(64) NOT NULL DEFAULT '',lastsync int unsigned NOT NULL DEFAULT 0,PRIMARY KEY(sourceid),UNIQUE KEY endpoint(endpoint)",
   'flipbook_sync_map'=>"sourceid int unsigned NOT NULL,kind varchar(16) NOT NULL,remoteid int unsigned NOT NULL,localid int unsigned NOT NULL,checksum char(64) NOT NULL DEFAULT '',PRIMARY KEY(sourceid,kind,remoteid),KEY localid(kind,localid)",
   'flipbook_sync_job'=>"jobid char(32) NOT NULL,sourceid int unsigned NOT NULL,status varchar(16) NOT NULL,cursorid int unsigned NOT NULL DEFAULT 0,upperid int unsigned NOT NULL DEFAULT 0,total int unsigned NOT NULL DEFAULT 0,processed int unsigned NOT NULL DEFAULT 0,added int unsigned NOT NULL DEFAULT 0,changed int unsigned NOT NULL DEFAULT 0,skipped int unsigned NOT NULL DEFAULT 0,message text,created int unsigned NOT NULL,updated int unsigned NOT NULL,PRIMARY KEY(jobid),KEY sourceid(sourceid,status)",
   'flipbook_sync_log'=>"logid int unsigned NOT NULL AUTO_INCREMENT,sourceid int unsigned NOT NULL DEFAULT 0,action varchar(30) NOT NULL,message text,dateline int unsigned NOT NULL,PRIMARY KEY(logid)",
   'flipbook_sync_setting'=>"skey varchar(40) NOT NULL,svalue text,PRIMARY KEY(skey)"
  ];
  foreach($tables as $table=>$columns) DB::query('CREATE TABLE IF NOT EXISTS %t ('.$columns.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',[$table]);
  service_draft_store::install();
  DB::query('INSERT IGNORE INTO %t (skey,svalue) VALUES (%s,%s)',['flipbook_sync_setting','instanceid',bin2hex(random_bytes(16))]);
 }
 public static function instance() {
  return (string)DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','instanceid']);
 }
 public static function log($sourceid,$action,$message) {
  DB::insert('flipbook_sync_log',['sourceid'=>$sourceid,'action'=>$action,'message'=>mb_substr($message,0,2000),'dateline'=>time()]);
 }
 public static function source($id) {
  $row=DB::fetch_first('SELECT * FROM %t WHERE sourceid=%d',['flipbook_sync_source',intval($id)]);
  if(!$row || !$row['enabled']) throw new \RuntimeException('数据源不存在或已停用');
  return $row;
 }
 public static function encrypt($value) {
  return \flipbook\front_service_config::encrypt($value);
 }
 public static function decrypt($value) {
  global $_G;
  if(strpos($value,'enc1:')!==0) throw new \RuntimeException('请重新保存数据源密钥');
  $raw=base64_decode(substr($value,5),true);
  if($raw===false || strlen($raw)<28) throw new \RuntimeException('数据源密钥无效');
  $secret=openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',$_G['config']['security']['authkey'],true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));
  if(!$secret) throw new \RuntimeException('数据源密钥无法读取，请重新保存');
  return $secret;
 }
 public static function mapping($source,$kind,$remote) {
  return DB::fetch_first('SELECT * FROM %t WHERE sourceid=%d AND kind=%s AND remoteid=%d',['flipbook_sync_map',$source,$kind,$remote]);
 }
 public static function remember($source,$kind,$remote,$local,$checksum='') {
  DB::query('INSERT INTO %t (sourceid,kind,remoteid,localid,checksum) VALUES (%d,%s,%d,%d,%s) ON DUPLICATE KEY UPDATE localid=VALUES(localid),checksum=VALUES(checksum)', ['flipbook_sync_map',$source,$kind,$remote,$local,$checksum]);
 }
}
