<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** Account templates are fetched from WeChat; field sources belong to this album plugin. */
class front_wechat_subscription {
 public static function definitions(){return [
  'product'=>['title'=>'商品内容通知','fields'=>['product_name'=>'商品名称','product_code'=>'商品编码','product_summary'=>'商品副标题','product_description'=>'商品说明','created_time'=>'商品建档时间','price'=>'商品售价']],
  'album'=>['title'=>'画册内容通知','fields'=>['album_name'=>'画册名称','album_description'=>'画册说明','created_time'=>'画册建档时间']],
 ];}
 public static function validate($config){
  if(!is_array($config)||count($config)>2)throw new \InvalidArgumentException('订阅消息配置无效');$out=[];
  foreach($config as $kind=>$row){
   $definition=self::definitions()[$kind]??null;if(!$definition||!is_array($row))throw new \InvalidArgumentException('订阅消息用途无效');
   $id=(string)($row['templateId']??'');if($id!==''&&!preg_match('/^[a-zA-Z0-9_-]{10,200}$/D',$id))throw new \InvalidArgumentException('订阅消息模板编号无效');
   if(isset($row['fields'])&&!is_array($row['fields']))throw new \InvalidArgumentException('模板字段格式无效');$fields=[];foreach($row['fields']??[] as $item){
    if(!is_array($item)||count($fields)>=20||!preg_match('/^(thing|character_string|time|date|amount|number|phone_number|letter|symbol|phrase|name|car_number|const)[1-9][0-9]*$/D',(string)($item['key']??''))||!isset($definition['fields'][$item['source']??'']))throw new \InvalidArgumentException('模板字段应映射到当前商品或画册资料');
    foreach($fields as $known)if($known['key']===$item['key'])throw new \InvalidArgumentException('模板字段重复');
    $fields[]=['key'=>$item['key'],'label'=>mb_substr(strip_tags((string)($item['label']??$item['key'])),0,40,'UTF-8'),'source'=>$item['source']];
   }
   $enabled=!empty($row['enabled']);if($enabled&&($id===''||!$fields))throw new \InvalidArgumentException('请先获取并匹配模板字段，再启用通知');
   $out[$kind]=['templateId'=>$id,'title'=>mb_substr(strip_tags((string)($row['title']??$definition['title'])),0,80,'UTF-8'),'enabled'=>$enabled?1:0,'fields'=>$fields];
  }
  return $out;
 }
 public static function config(){try{return self::validate(json_decode(front_service_config::get('wechat_subscribe_config'),true));}catch(\InvalidArgumentException $e){return [];}}
 private static function request($url,$body=null){
  $curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
  if($body!==null)curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body),CURLOPT_HTTPHEADER=>['Content-Type: application/json']]);
  $raw=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);$data=json_decode((string)$raw,true);
  if($code!==200||!is_array($data)||!empty($data['errcode']))throw new \RuntimeException('微信模板服务暂不可用'.(isset($data['errcode'])?'（错误码 '.intval($data['errcode']).'）':''));return $data;
 }
 public static function templates(){
  global $_G;$appid=front_service_config::get('wechat_appid');$secret=front_service_config::get('wechat_secret');if($appid===''||$secret==='')throw new \RuntimeException('请先在小程序开发信息中配置 AppID 和 AppSecret');
  loadcache('flipbook_wx_token');$cache=$_G['cache']['flipbook_wx_token']??[];
  if(($cache['appid']??'')!==$appid||($cache['expires']??0)<time()+60){$token=self::request('https://api.weixin.qq.com/cgi-bin/stable_token',['grant_type'=>'client_credential','appid'=>$appid,'secret'=>$secret,'force_refresh'=>false]);if(empty($token['access_token']))throw new \RuntimeException('微信凭据获取失败');$cache=['appid'=>$appid,'token'=>$token['access_token'],'expires'=>time()+intval($token['expires_in']??7200)-120];savecache('flipbook_wx_token',$cache);}
  $data=self::request('https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token='.rawurlencode($cache['token']));$out=[];
  foreach(array_slice((array)($data['data']??[]),0,100) as $row){
   $id=(string)($row['priTmplId']??'');if(!preg_match('/^[A-Za-z0-9_-]{10,200}$/D',$id))continue;$fields=[];
   foreach(explode("\n",(string)($row['content']??'')) as $line)if(preg_match('/^(.*?)[:：]?\s*\{\{([a-z_]+[0-9]+)\.DATA\}\}/u',trim($line),$m))$fields[]=['label'=>trim(strip_tags($m[1])," :："),'key'=>$m[2]];
   $out[]=['templateId'=>$id,'title'=>mb_substr(strip_tags((string)($row['title']??'')),0,80,'UTF-8'),'fields'=>$fields];
  }
  return $out;
 }
}
