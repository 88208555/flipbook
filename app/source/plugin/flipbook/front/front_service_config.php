<?php
namespace flipbook;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Single deployment settings source; secrets are encrypted with the site's auth key. */
class front_service_config {
 public static function schema() {
  return [
   'wechat_orders_enabled'=>['title'=>'启用微信订单','type'=>'bool','default'=>'0','group'=>'微信订单','managed'=>1],
   'wechat_trade_enabled'=>['title'=>'启用交易组件','type'=>'bool','default'=>'0','group'=>'交易组件','managed'=>1],
   'wechat_trade_buy_text'=>['title'=>'购买按钮文字','type'=>'text','default'=>'立即购买','group'=>'交易组件','managed'=>1],
   'wechat_shipping_fen'=>['title'=>'每单运费（分）','type'=>'text','default'=>'0','group'=>'交易组件','managed'=>1],
   'wechat_pay_mchid'=>['title'=>'微信支付商户号','type'=>'text','default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_serial'=>['title'=>'商户证书序列号','type'=>'text','default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_private_key'=>['title'=>'商户 API 私钥','type'=>'secret','maxBytes'=>20000,'default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_api_key'=>['title'=>'APIv3 密钥','type'=>'secret','default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_public_id'=>['title'=>'微信支付公钥 ID / 平台证书序列号','type'=>'text','default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_public_key'=>['title'=>'微信支付公钥 / 平台证书','type'=>'secret','maxBytes'=>20000,'default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_pay_notify_url'=>['title'=>'支付结果通知地址','type'=>'text','default'=>'','group'=>'交易组件','managed'=>1],
   'wechat_customer_enabled'=>['title'=>'启用小程序客服','type'=>'bool','default'=>'0','group'=>'小程序客服','managed'=>1],
   'wechat_customer_title'=>['title'=>'客服按钮文字','type'=>'text','default'=>'联系客服','group'=>'小程序客服','managed'=>1],
   'wechat_customer_session'=>['title'=>'会话来源','type'=>'text','default'=>'画册商品咨询','group'=>'小程序客服','managed'=>1],
   'wechat_customer_card'=>['title'=>'显示当前商品卡片','type'=>'bool','default'=>'1','group'=>'小程序客服','managed'=>1],
   'wechat_enabled'=>['title'=>'启用微信小程序登录','type'=>'bool','default'=>'0','group'=>'微信小程序登录'],
   'wechat_name'=>['title'=>'小程序名称','type'=>'text','default'=>'','group'=>'微信小程序登录'],
   'wechat_original_id'=>['title'=>'原始 ID','type'=>'text','default'=>'','group'=>'微信小程序登录'],
   'wechat_qrcode'=>['title'=>'小程序二维码','type'=>'text','default'=>'','group'=>'微信小程序登录'],
   'wechat_appid'=>['title'=>'微信 AppID','type'=>'text','default'=>'','group'=>'微信小程序登录'],
   'wechat_secret'=>['title'=>'微信 AppSecret','type'=>'secret','default'=>'','group'=>'微信小程序登录'],
   'wechat_push_enabled'=>['title'=>'启用消息推送接收','type'=>'bool','default'=>'0','group'=>'消息推送'],
   'wechat_push_url'=>['title'=>'消息推送地址','type'=>'readonly','default'=>'','group'=>'消息推送'],
   'wechat_push_token'=>['title'=>'消息校验 Token','type'=>'secret','default'=>'','group'=>'消息推送'],
   'wechat_push_mode'=>['title'=>'消息加密方式','type'=>'select','options'=>[['plain','明文模式'],['compatible','兼容模式'],['encrypted','安全模式']],'default'=>'encrypted','group'=>'消息推送'],
   'wechat_push_aes'=>['title'=>'消息加密 EncodingAESKey','type'=>'secret','default'=>'','group'=>'消息推送'],
   'wechat_subscribe_templates'=>['title'=>'订阅消息模板','type'=>'textarea','default'=>'','group'=>'订阅消息','comment'=>'每行：用途名称 | 微信模板 ID。模板需先在微信公众平台申请。'],
   'wechat_ci_url'=>['title'=>'构建服务地址','type'=>'text','default'=>'','managed'=>true],
   'wechat_ci_token'=>['title'=>'构建服务通信密钥','type'=>'secret','default'=>'','managed'=>true],
   'wechat_upload_private_key'=>['title'=>'代码上传私钥','type'=>'secret','default'=>'','managed'=>true,'maxBytes'=>20000],
   'wechat_ci_api_base'=>['title'=>'小程序接口地址','type'=>'text','default'=>'','managed'=>true],
   'wechat_release_version'=>['title'=>'默认版本号','type'=>'text','default'=>'1.0.0','managed'=>true],
   'wechat_release_description'=>['title'=>'默认版本说明','type'=>'textarea','default'=>'','managed'=>true],
   'wechat_subscribe_config'=>['title'=>'订阅消息配置','type'=>'textarea','default'=>'{}','managed'=>true,'maxBytes'=>30000],
   'storage_provider'=>['title'=>'上传存储','type'=>'select','options'=>[['local','本地存储'],['cos','腾讯云 COS（对象存储）']],'default'=>'local','group'=>'文件存储'],
   'cos_bucket'=>['title'=>'COS 存储桶（含 APPID）','type'=>'text','default'=>'','group'=>'文件存储','comment'=>'例如 album-1250000000'],
   'cos_region'=>['title'=>'COS 地域','type'=>'text','default'=>'ap-guangzhou','group'=>'文件存储','comment'=>'例如 ap-guangzhou、ap-shanghai'],
   'cos_secret_id'=>['title'=>'COS SecretId','type'=>'secret','default'=>'','group'=>'文件存储'],
   'cos_secret_key'=>['title'=>'COS SecretKey','type'=>'secret','default'=>'','group'=>'文件存储'],
   'cos_domain'=>['title'=>'COS 访问域名（可选）','type'=>'text','default'=>'','group'=>'文件存储','comment'=>'HTTPS 自定义 CDN 域名；留空使用存储桶域名。存储桶需允许读取公开素材'],
   'cos_prefix'=>['title'=>'COS 文件目录','type'=>'text','default'=>'flipbook','group'=>'文件存储'],
  ];
 }
 private static function key() { global $_G; return hash('sha256', $_G['config']['security']['authkey'], true); }
 public static function encrypt($value) {
  if($value==='') return '';
  $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($value,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag);
  if($cipher===false) throw new \RuntimeException('密钥保存失败');
  return 'enc1:'.base64_encode($iv.$tag.$cipher);
 }
 public static function decrypt($value) {
  if(!is_string($value)||$value===''||strpos($value,'enc1:')!==0)return '';
  $raw=base64_decode(substr($value,5),true);if($raw===false||strlen($raw)<28)return '';
  return openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16))?:'';
 }
 public static function get($key) {
  $schema=self::schema(); if(!isset($schema[$key])) return '';
  $rows=table_flipbook_setting::t()->fetch_all_setting(); $value=$rows[$key]??$schema[$key]['default'];
  if($schema[$key]['type']==='secret' && $value!=='')return self::decrypt($value);
  return $value;
 }
 public static function validate($values) {
  $commerce=front_commerce::validate_settings($values);if($commerce!=='')return $commerce;
  foreach(['wechat_name'=>'小程序名称','wechat_original_id'=>'原始 ID'] as $key=>$title) {
   $value=$values[$key]??'';
   if(mb_strlen($value,'UTF-8')>80 || preg_match('/[\x00-\x1F\x7F<>]/',$value)) return $title.'请使用 80 字以内的安全文本';
  }
  if(($values['wechat_qrcode']??'')!=='' && admin_helper::cleanurl($values['wechat_qrcode'])!==$values['wechat_qrcode']) return '小程序二维码图片地址无效';
  if(($values['wechat_appid']??'')!=='' && !preg_match('/^wx[a-zA-Z0-9]{16}$/',$values['wechat_appid'])) return '微信 AppID 格式不正确';
  if(isset($values['wechat_push_mode']) && !in_array($values['wechat_push_mode'],['plain','compatible','encrypted'],true))return '消息加密方式无效';
  if(($values['wechat_push_token']??'')!=='' && !preg_match('/^[a-zA-Z0-9]{3,32}$/',$values['wechat_push_token']))return '消息校验 Token 使用 3 至 32 位字母或数字';
  if(($values['wechat_push_aes']??'')!=='' && !preg_match('/^[a-zA-Z0-9+\/]{43}$/',$values['wechat_push_aes']))return 'EncodingAESKey 需要 43 位字符';
  if(($values['wechat_push_enabled']??'0')==='1' && (empty($values['wechat_push_token']) || (($values['wechat_push_mode']??'encrypted')!=='plain' && empty($values['wechat_push_aes']))))return '请填写消息校验 Token 和对应的消息加密密钥';
  if(($values['wechat_ci_url']??'')!=='' && !front_wechat_release::worker_url($values['wechat_ci_url']))return '构建服务地址需要 HTTPS；本机开发可使用本地 HTTP 地址';
  if(($values['wechat_ci_token']??'')!=='' && (strlen($values['wechat_ci_token'])<32 || preg_match('/[\x00-\x20\x7f]/',$values['wechat_ci_token'])))return '构建服务通信密钥至少 32 位，不能包含空白';
  if(($values['wechat_upload_private_key']??'')!==''){$key=@openssl_pkey_get_private($values['wechat_upload_private_key']);if(!$key || (openssl_pkey_get_details($key)['type']??-1)!==OPENSSL_KEYTYPE_RSA)return '上传私钥格式无效，请选择微信公众平台下载的 RSA 私钥';}
  if(($values['wechat_ci_api_base']??'')!=='' && (!preg_match('~^https://[^\s?#]+$~D',$values['wechat_ci_api_base']) || parse_url($values['wechat_ci_api_base'],PHP_URL_USER)!==null))return '小程序接口地址需要为 HTTPS 站点地址';
  if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,31}$/D',$values['wechat_release_version']??'1.0.0'))return '版本号格式不正确';
  if(mb_strlen($values['wechat_release_description']??'','UTF-8')>200)return '版本说明最多 200 字';
  if(isset($values['wechat_subscribe_config'])){try{front_wechat_subscription::validate(json_decode($values['wechat_subscribe_config'],true));}catch(\InvalidArgumentException $error){return $error->getMessage();}}
  foreach(array_filter(explode("\n",$values['wechat_subscribe_templates']??'')) as $line)if(!preg_match('/^.{1,40}\s*\|\s*[a-zA-Z0-9_-]{10,200}\s*$/u',trim($line)))return '订阅消息模板按“用途名称 | 模板 ID”填写';
  if(($values['cos_bucket']??'')!=='' && !preg_match('/^[a-z0-9][a-z0-9-]{0,50}-[0-9]{5,20}$/',$values['cos_bucket'])) return 'COS 存储桶格式不正确';
  if(!preg_match('/^[a-z]{2}-[a-z]+(?:-[a-z]+)?$/',$values['cos_region']??'ap-guangzhou')) return 'COS 地域格式不正确';
  if(($values['cos_domain']??'')!=='' && !preg_match('#^https://[a-zA-Z0-9.-]+(?::443)?/?$#',$values['cos_domain'])) return 'COS 访问域名需要 HTTPS 域名';
  if(!preg_match('#^[a-zA-Z0-9/_-]{0,100}$#',$values['cos_prefix']??'')) return 'COS 文件目录仅支持字母、数字、斜线、下划线和短横线';
  return '';
 }

 /** Prepare the complete validated update before writing any setting; blank secrets retain their ciphertext. */
 public static function prepare_updates($values,$clear=[]) {
  $schema=self::schema();$combined=[];$updates=[];
  foreach($schema as $key=>$field) $combined[$key]=self::get($key);
  foreach($values as $key=>$value) {
   if(!isset($schema[$key]) || $schema[$key]['type']==='readonly')continue;
   if(!is_scalar($value) || is_bool($value))throw new \InvalidArgumentException('设置内容格式不正确');
   $value=trim((string)$value);
   if(strlen($value)>($schema[$key]['maxBytes']??2000))throw new \InvalidArgumentException('设置内容过长');
   if($schema[$key]['type']==='secret') {
    if(in_array($key,$clear,true))$value='';
    elseif($value==='')continue;
   }
   $combined[$key]=$value;$updates[$key]=$value;
  }
  $error=self::validate($combined);if($error!=='')throw new \InvalidArgumentException($error);
  foreach($updates as $key=>$value)if($schema[$key]['type']==='secret' && $value!=='')$updates[$key]=self::encrypt($value);
  return $updates;
 }

 /** Actual deployment origins only; this plugin does not provide a WebSocket server. */
 public static function server_domains() {
  global $_G;
  $origin=function($url){$parts=parse_url($url);if(!$parts || !in_array($parts['scheme']??'',['http','https'],true) || empty($parts['host']))return '';return $parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');};
  $site=$origin($_G['siteurl']??'');$download=$site!==''?[$site]:[];
  if(self::get('storage_provider')==='cos') {
   $domain=self::get('cos_domain');$bucket=self::get('cos_bucket');$region=self::get('cos_region');
   if($domain==='' && $bucket!=='' && $region!=='')$domain='https://'.$bucket.'.cos.'.$region.'.myqcloud.com';
   $cos=$origin($domain);if($cos!=='' && !in_array($cos,$download,true))$download[]=$cos;
  }
  return ['request'=>$site,'socket'=>'','upload'=>$site,'download'=>implode('; ',$download)];
 }
}
