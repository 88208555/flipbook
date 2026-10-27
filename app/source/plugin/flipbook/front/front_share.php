<?php
namespace flipbook;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Public product sharing. The requested product is always the source of media and text. */
class front_share {
 /** Three optional per-product overrides. Empty values keep following product data. */
 public static function configuration($input,$validate=false) {
  $input=is_array($input)?$input:[];$result=[];
  foreach(['title'=>['主标题',10],'subtitle'=>['副标题',20],'description'=>['描述',200]] as $key=>$rule){
   $value=$input[$key]??'';
   if(!is_scalar($value)){if($validate)throw new \RuntimeException('分享'.$rule[0].'格式无效');$value='';}
   $value=trim(strip_tags(str_replace(["\r\n","\r"],"\n",(string)$value)));
   if($key!=='description')$value=preg_replace('/[\r\n\t]+/u',' ',$value);
   if($validate&&mb_strlen($value,'UTF-8')>$rule[1])throw new \RuntimeException('分享'.$rule[0].'最多 '.$rule[1].' 字');
   $result[$key]=mb_substr($value,0,$rule[1],'UTF-8');
  }
  return $result;
 }
 public static function product($row) {
  $spec=json_decode((string)($row['spec']??''),true);$share=self::configuration($spec['share']??[]);
  foreach(['title'=>'name','subtitle'=>'summary','description'=>'description'] as $key=>$field){
   if($share[$key]!=='')continue;
   $value=trim(strip_tags((string)($row[$field]??'')));
   $share[$key]=$key==='description'?mb_substr($value,0,200,'UTF-8'):preg_replace('/[\r\n\t]+/u',' ',$value);
  }
  $share['image']=front_helper::img($row['cover']??'');
  return $share;
 }
 public static function settings() {
  $s=front_view::settings();$raw=table_flipbook_setting::t()->fetch_all_setting();
  $color=function($key,$fallback)use($raw){return preg_match('/^#[0-9a-f]{6}$/i',$raw[$key]??'')?$raw[$key]:$fallback;};
  return ['titleTemplate'=>$s['sharetitle']?:'{name}','descriptionTemplate'=>$s['sharetext']?:'{summary}',
   'bg'=>$color('share_bg','#FFFFFF'),'color'=>$color('share_color','#111111'),
   'radius'=>max(0,min(40,intval($raw['share_radius']??20))),
   'showPrice'=>($raw['share_showprice']??'1')==='1',
   'qrChannel'=>($raw['share_qr_channel']??'h5')==='wechat'?'wechat':'h5',
   'qrHint'=>trim($raw['share_qr_hint']??'扫码查看商品')];
 }
 public static function handle() {
  $id=max(0,intval($_GET['productid']??0));$row=table_flipbook_product::t()->fetch($id);
  if(!$row || !$row['available'])self::fail('商品已下架',404);
  if(($_GET['channel']??'h5')==='wechat') {
   try { $file=self::mini_code($id);header('Content-Type: image/png');header('Cache-Control: public,max-age=3600');readfile($file);exit; }
   catch(\Throwable $e){self::fail('小程序码暂不可用，请检查微信 AppID、AppSecret 和发布版本',503);}
  }
  global $_G;
  $site=rtrim($_G['siteurl'],'/').'/';
  $url=$site.front_helper::url('micro',['key'=>'detail','productid'=>$id]);
  require_once libfile('class/qrcode');header('Content-Type: image/png');header('Cache-Control: public,max-age=3600');
  \QRcode::png($url,false,QR_ECLEVEL_M,6,4);exit;
 }
 private static function request($url,$data) {
  $c=curl_init($url);curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
  $body=curl_exec($c);$status=curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);
  if($status!==200 || $body===false)throw new \RuntimeException('微信服务请求失败');return $body;
 }
 private static function mini_code($id) {
  global $_G;
  $appid=front_service_config::get('wechat_appid');$secret=front_service_config::get('wechat_secret');if(!$appid||!$secret)throw new \RuntimeException('微信尚未配置');
  $dir=DISCUZ_ROOT.'data/attachment/flipbook/qrcodes/'.substr(hash('sha256',$appid),0,16);$file=$dir.'/product-'.$id.'.png';if(is_file($file))return $file;
  loadcache('flipbook_wx_token');$cache=$_G['cache']['flipbook_wx_token']??[];
  if(($cache['appid']??'')!==$appid || ($cache['expires']??0)<time()+60) {
   $data=json_decode(self::request('https://api.weixin.qq.com/cgi-bin/stable_token',['grant_type'=>'client_credential','appid'=>$appid,'secret'=>$secret,'force_refresh'=>false]),true);
   if(empty($data['access_token']))throw new \RuntimeException('获取微信凭据失败');
   $cache=['appid'=>$appid,'token'=>$data['access_token'],'expires'=>time()+intval($data['expires_in']??7200)-120];savecache('flipbook_wx_token',$cache);
  }
  $image=self::request('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.rawurlencode($cache['token']),['scene'=>'p='.$id,'page'=>'pages/micro/index','width'=>430,'check_path'=>true,'env_version'=>'release']);
  if(substr($image,0,8)!=="\x89PNG\r\n\x1a\n")throw new \RuntimeException('生成小程序码失败');
  if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new \RuntimeException('二维码目录不可写');
  $temp=tempnam($dir,'qr-');file_put_contents($temp,$image);rename($temp,$file);return $file;
 }
 private static function fail($message,$status){http_response_code($status);header('Content-Type: application/json;charset=utf-8');echo json_encode(['ok'=>0,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
}
