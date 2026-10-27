<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Draft content lives separately from the published product and related tables. */
class service_draft_store {
 public static function install() {
  DB::query("CREATE TABLE IF NOT EXISTS %t (draftid int unsigned NOT NULL AUTO_INCREMENT,dkey char(64) NOT NULL,uid int unsigned NOT NULL,origin varchar(12) NOT NULL,kind varchar(12) NOT NULL,sourceid int unsigned NOT NULL DEFAULT 0,remoteid int unsigned NOT NULL DEFAULT 0,targetid int unsigned NOT NULL DEFAULT 0,name varchar(150) NOT NULL DEFAULT '',payload mediumtext NOT NULL,revision int unsigned NOT NULL DEFAULT 1,editid char(32) NOT NULL DEFAULT '',sequenceid int unsigned NOT NULL DEFAULT 0,created int unsigned NOT NULL,updated int unsigned NOT NULL,PRIMARY KEY(draftid),UNIQUE KEY dkey(dkey),KEY owner(uid,origin,kind,updated),KEY target(kind,targetid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",['flipbook_sync_draft']);
 }
 public static function fetch($id,$uid) {
  $row=DB::fetch_first('SELECT * FROM %t WHERE draftid=%d AND uid=%d',['flipbook_sync_draft',intval($id),intval($uid)]);
  if(!$row) throw new \RuntimeException('草稿不存在或无权访问');
  return $row;
 }
 public static function local($uid,$productid,$newid='') {
  $key=hash('sha256',$uid.':local:product:'.($productid>0?$productid:$newid));
  return DB::fetch_first('SELECT * FROM %t WHERE dkey=%s',['flipbook_sync_draft',$key]);
 }
 public static function save($uid,$origin,$kind,$source,$remote,$target,$payload,$editid,$sequence,$expected,$newid='') {
  if($uid<1 || !in_array($origin,['local','external'],true) || !in_array($kind,['product','category','series','supplier'],true) || ($origin==='local' && $kind!=='product')) throw new \RuntimeException('草稿类型无效');
  if(!preg_match('/^[a-f0-9]{32}$/D',$editid) || $sequence<1 || $sequence>2147483647) throw new \RuntimeException('草稿编辑会话无效');
  $raw=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
  if(strlen($raw)>1048576) throw new \RuntimeException('草稿内容超过 1 MiB，未保存');
  $binding=$origin==='local'?($target>0?$target:$newid):$source.':'.$remote;
  if($binding==='') throw new \RuntimeException('草稿身份无效');
  $key=hash('sha256',$uid.':'.$origin.':'.$kind.':'.$binding);$lock='fb-draft-'.substr($key,0,48);
  if(!DB::result_first('SELECT GET_LOCK(%s,1)',[$lock])) throw new \RuntimeException('草稿正在保存，请稍后再试');
  try {
   $old=DB::fetch_first('SELECT * FROM %t WHERE dkey=%s',['flipbook_sync_draft',$key]);
   if($old && $old['editid']===$editid && $sequence<=intval($old['sequenceid'])) return $old;
   if($old && $old['editid']!==$editid && intval($old['revision'])!==intval($expected)) throw new \RuntimeException('草稿已被另一个编辑页面更新，请重新打开；当前输入尚未覆盖新版本');
   $values=['uid'=>$uid,'origin'=>$origin,'kind'=>$kind,'sourceid'=>$source,'remoteid'=>$remote,'targetid'=>$target,'name'=>mb_substr(strip_tags((string)($payload['fields']['name']??'未命名商品')),0,150),'payload'=>$raw,'revision'=>$old?intval($old['revision'])+1:1,'editid'=>$editid,'sequenceid'=>$sequence,'updated'=>time()];
   if($old) {DB::update('flipbook_sync_draft',$values,['draftid'=>$old['draftid']]);$id=intval($old['draftid']);}
   else $id=DB::insert('flipbook_sync_draft',$values+['dkey'=>$key,'created'=>time()],true);
   return self::fetch($id,$uid);
  } finally {DB::query('SELECT RELEASE_LOCK(%s)',[$lock]);}
 }
 public static function receive($uid,$kind,$source,$remote,$payload,$target=0) {
  $key=hash('sha256',$uid.':external:'.$kind.':'.$source.':'.$remote);
  $old=DB::fetch_first('SELECT * FROM %t WHERE dkey=%s',['flipbook_sync_draft',$key]);
  if($old) return ['retained'=>true]+$old; // Another import must not erase a user's saved draft edits.
  return self::save($uid,'external',$kind,$source,$remote,$target,$payload,bin2hex(random_bytes(16)),1,0);
 }
 public static function remove($id,$uid,$revision) {
  DB::delete('flipbook_sync_draft',['draftid'=>intval($id),'uid'=>intval($uid),'revision'=>intval($revision)]);
 }
}
