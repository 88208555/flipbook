<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

class service_auth {
 public static function domain($input) {
  if(!is_string($input) || strlen($input)>300 || preg_match('/[\s\x00-\x1f\x7f%]/',$input)) throw new \InvalidArgumentException('请填写准确的域名，不支持通配符');
  $parts=parse_url(strpos($input,'://')===false?'https://'.$input:$input);
  if(!$parts || !in_array($parts['scheme']??'',['http','https'],true) || isset($parts['user'],$parts['pass']) || isset($parts['user']) || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path']??'',['','/'],true)) throw new \InvalidArgumentException('域名不能包含账号、路径或查询参数');
  $host=strtolower(rtrim($parts['host']??'','.'));
  if($host==='' || strlen($host)>253 || (!filter_var($host,FILTER_VALIDATE_IP) && !preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D',$host)) || strpos($host,'..')!==false) throw new \InvalidArgumentException('域名格式无效，国际域名请使用 Punycode');
  foreach(explode('.',$host) as $label) if(strlen($label)>63 || $label[0]==='-' || substr($label,-1)==='-') throw new \InvalidArgumentException('域名格式无效');
  $port=intval($parts['port']??0);
  return $host.($port && !in_array($port,[80,443],true)?':'.$port:'');
 }
 public static function ips($raw) {
  $ips=preg_split('/[\s,]+/',trim($raw),-1,PREG_SPLIT_NO_EMPTY);
  if(count($ips)>16) throw new \InvalidArgumentException('最多配置 16 个固定服务器 IP');
  foreach($ips as $ip) if(!filter_var($ip,FILTER_VALIDATE_IP)) throw new \InvalidArgumentException('服务器 IP 格式无效');
  return implode("\n",array_unique($ips));
 }
 public static function issue($domain,$note='',$ips='') {
  $domain=self::domain($domain);$ips=self::ips($ips);
  if(DB::result_first('SELECT clientid FROM %t WHERE domain=%s',['flipbook_sync_key',$domain])) throw new \RuntimeException('此域名已有独立密钥，请使用重新生成');
  $secret=bin2hex(random_bytes(32));
  $id=DB::insert('flipbook_sync_key',['domain'=>$domain,'keyhash'=>hash('sha256',$secret),'enabled'=>1,'note'=>mb_substr(strip_tags($note),0,150),'ips'=>$ips,'created'=>time(),'lastused'=>0],true);
  return ['clientid'=>$id,'domain'=>$domain,'key'=>'fbps_'.$id.'.'.$secret];
 }
 public static function rotate($id) {
  $row=DB::fetch_first('SELECT clientid,domain FROM %t WHERE clientid=%d',['flipbook_sync_key',intval($id)]);
  if(!$row) throw new \RuntimeException('授权不存在');
  $secret=bin2hex(random_bytes(32));DB::update('flipbook_sync_key',['keyhash'=>hash('sha256',$secret),'created'=>time()],['clientid'=>$row['clientid']]);
  return ['clientid'=>intval($row['clientid']),'domain'=>$row['domain'],'key'=>'fbps_'.$row['clientid'].'.'.$secret];
 }
 public static function authenticate($authorization,$domain,$ip,$origin='') {
  if(!preg_match('/^Bearer (fbps_([1-9][0-9]*)\.([a-f0-9]{64}))$/D',$authorization,$m)) throw new \RuntimeException('调用密钥无效',401);
  $row=DB::fetch_first('SELECT * FROM %t WHERE clientid=%d',['flipbook_sync_key',intval($m[2])]);
  if(!$row || !$row['enabled'] || !hash_equals($row['keyhash'],hash('sha256',$m[3]))) throw new \RuntimeException('调用密钥无效或已停用',401);
  try {$claimed=self::domain($domain);$browser=$origin!==''?self::domain($origin):$claimed;} catch(\InvalidArgumentException $e) {throw new \RuntimeException('调用域名未获授权',403);}
  if($claimed!==$row['domain'] || $browser!==$claimed) throw new \RuntimeException('调用域名未获授权',403);
  $ips=preg_split('/[\s,]+/',trim($row['ips']),-1,PREG_SPLIT_NO_EMPTY);
  if($ips && !in_array($ip,$ips,true)) throw new \RuntimeException('调用服务器 IP 未获授权',403);
  DB::update('flipbook_sync_key',['lastused'=>time()],['clientid'=>$row['clientid']]);
  return $row;
 }
}
