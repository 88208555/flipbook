<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');
class front_wechat_push {
 public static function signature($token,$timestamp,$nonce,$encrypted=''){$parts=[$token,(string)$timestamp,(string)$nonce];if($encrypted!=='')$parts[]=$encrypted;sort($parts,SORT_STRING);return sha1(implode('',$parts));}
 public static function decrypt($encrypted,$key,$appid){
  $key=base64_decode($key.'=',true);$cipher=base64_decode($encrypted,true);
  if(!$key || strlen($key)!==32 || !$cipher)throw new \RuntimeException('invalid cipher');
  $plain=openssl_decrypt($cipher,'aes-256-cbc',$key,OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,substr($key,0,16));
  if(!$plain || strlen($plain)<21)throw new \RuntimeException('invalid cipher');
  $pad=ord(substr($plain,-1));if($pad<1||$pad>32||substr($plain,-$pad)!==str_repeat(chr($pad),$pad))throw new \RuntimeException('invalid padding');$plain=substr($plain,0,-$pad);
  $length=unpack('N',substr($plain,16,4))[1];if($length>strlen($plain)-20 || !hash_equals($appid,substr($plain,20+$length)))throw new \RuntimeException('invalid receiver');
  return substr($plain,20,$length);
 }
 public static function handle(){
  if(front_service_config::get('wechat_push_enabled')!=='1')self::out(404,'disabled');
  $method=$_SERVER['REQUEST_METHOD']??'GET';if(!in_array($method,['GET','POST'],true))self::out(405,'method');
  $ts=(string)($_GET['timestamp']??'');$nonce=(string)($_GET['nonce']??'');if(!ctype_digit($ts)||abs(time()-intval($ts))>300||strlen($nonce)>128)self::out(403,'invalid signature');
  $token=front_service_config::get('wechat_push_token');if(!$token)self::out(403,'invalid configuration');
  $encrypted='';$raw='';
  if($method==='POST'){
   $raw=file_get_contents('php://input',false,null,0,65537);if(strlen($raw)>65536)self::out(413,'too large');
   $body=json_decode($raw,true);if(!is_array($body)){$body=self::xml($raw);}
   $encrypted=(string)($body['Encrypt']??'');
  }elseif(($_GET['encrypt_type']??'')==='aes'){$encrypted=(string)($_GET['echostr']??'');}
  $mode=front_service_config::get('wechat_push_mode');if($method==='POST'&&$mode==='encrypted'&&$encrypted==='')self::out(403,'encrypted message required');
  $signature=(string)($_GET[$encrypted!==''?'msg_signature':'signature']??'');
  if(!hash_equals(self::signature($token,$ts,$nonce,$encrypted),$signature))self::out(403,'invalid signature');
  if($encrypted!==''){try{$raw=self::decrypt($encrypted,front_service_config::get('wechat_push_aes'),front_service_config::get('wechat_appid'));}catch(\Throwable $e){self::out(403,'invalid cipher');}}
  if($method==='GET')self::out(200,$encrypted!==''?$raw:(string)($_GET['echostr']??''));
  // Valid events are acknowledged; this endpoint never sends messages to members.
  self::out(200,'success');
 }
 private static function xml($raw){if(stripos($raw,'<!DOCTYPE')!==false || stripos($raw,'<!ENTITY')!==false)self::out(400,'invalid body');libxml_use_internal_errors(true);$xml=simplexml_load_string($raw,'SimpleXMLElement',LIBXML_NONET|LIBXML_NOCDATA);if($xml===false)self::out(400,'invalid body');return json_decode(json_encode($xml),true);}
 private static function out($code,$text){http_response_code($code);header('Content-Type:text/plain;charset=utf-8');header('Cache-Control:no-store');echo $text;exit;}
}
