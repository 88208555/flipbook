<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** Authenticated administration talks to the separately configured private CI worker. */
class front_wechat_release {
 public static function configured(){return front_service_config::get('wechat_ci_url')!==''&&front_service_config::get('wechat_ci_token')!==''&&front_service_config::get('wechat_upload_private_key')!==''&&front_service_config::get('wechat_appid')!=='';}
 public static function worker_url($url){
  $p=parse_url((string)$url);if(!$p||empty($p['host'])||isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment']))return false;
  return ($p['scheme']??'')==='https'||(($p['scheme']??'')==='http'&&in_array(strtolower($p['host']),['localhost','127.0.0.1','[::1]','host.docker.internal'],true));
 }
 public static function request($method,$suffix,$payload=null){
  $base=rtrim(front_service_config::get('wechat_ci_url'),'/');$token=front_service_config::get('wechat_ci_token');
  if(!self::worker_url($base)||$token==='')throw new \RuntimeException('请先完成上传代码配置并连接构建服务');
  $curl=curl_init($base.$suffix);$body='';
  curl_setopt_array($curl,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_WRITEFUNCTION=>static function($c,$part)use(&$body){if(strlen($body)+strlen($part)>1024*1024)return 0;$body.=$part;return strlen($part);}]);
  if($payload!==null)curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  $ok=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
  if($ok===false)throw new \RuntimeException('构建服务连接中断，请刷新发布记录确认结果后再操作');
  $data=json_decode($body,true);if(!is_array($data)||empty($data['ok'])||$code<200||$code>=300)throw new \RuntimeException(is_array($data)&&is_string($data['message']??null)?mb_substr($data['message'],0,500,'UTF-8'):'构建服务返回异常');
  return $data;
 }
 public static function list($page=1,$size=10){return self::request('GET','/jobs?'.http_build_query(['appid'=>front_service_config::get('wechat_appid'),'page'=>max(1,intval($page)),'pageSize'=>in_array(intval($size),[10,20,50],true)?intval($size):10]));}
 public static function start($values,$archive=''){
  if(!self::configured())throw new \RuntimeException('请先保存 AppID、上传私钥和构建服务配置');
  $version=trim((string)($values['version']??''));$description=trim((string)($values['description']??''));$requestId=(string)($values['request_id']??'');$source=(string)($values['source']??'cloud');
  if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,31}$/D',$version)||mb_strlen($description,'UTF-8')>200||!preg_match('/^[A-Za-z0-9_-]{16,100}$/D',$requestId)||!in_array($source,['cloud','local'],true))throw new \InvalidArgumentException('版本号、说明或发布请求格式不正确');
  $api=front_service_config::get('wechat_ci_api_base');if(!preg_match('~^https://[^\s?#]+$~D',$api)||parse_url($api,PHP_URL_USER)!==null)throw new \InvalidArgumentException('请配置正式小程序使用的 HTTPS 接口地址');
  if($source==='local'&&($archive===''||strlen($archive)>22*1024*1024||substr($archive,0,2)!=='PK'))throw new \InvalidArgumentException('请上传 22MB 以内的完整小程序源码 ZIP');
  $payload=['appid'=>front_service_config::get('wechat_appid'),'privateKey'=>front_service_config::get('wechat_upload_private_key'),'version'=>$version,'description'=>$description,'source'=>$source,'requestId'=>$requestId,'apiBase'=>$api];
  if($source==='local')$payload['archive']=base64_encode($archive);
  return self::request('POST','/jobs',$payload);
 }
 public static function remove($id){if(!preg_match('/^[a-f0-9-]{36}$/D',$id))throw new \InvalidArgumentException('版本标识无效');return self::request('DELETE','/jobs/'.$id.'?'.http_build_query(['appid'=>front_service_config::get('wechat_appid')]));}
}
