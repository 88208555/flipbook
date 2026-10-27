<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** APIv3 signs exact bytes and verifies every successful response before using its content. */
class front_wechat_pay {
 private $settings;private $transport;private $clock;
 public function __construct($settings,$transport=null,$clock=null){$this->settings=$settings;$this->transport=$transport;$this->clock=$clock?:function(){return time();};}
 public function now(){return ($this->clock)();}
 public static function settings(){
  $out=[];foreach(['appid','secret','pay_mchid','pay_serial','pay_private_key','pay_api_key','pay_public_id','pay_public_key','pay_notify_url'] as $key)$out[$key]=front_service_config::get('wechat_'.$key);return $out;
 }
 public static function sign($message,$private){
  if(!@openssl_sign($message,$signature,$private,OPENSSL_ALGO_SHA256))throw new \RuntimeException('支付签名失败，请检查商户私钥');return base64_encode($signature);
 }
 public function authorization($method,$path,$body,$timestamp,$nonce){
  $s=$this->settings;$signature=self::sign($method."\n".$path."\n".$timestamp."\n".$nonce."\n".$body."\n",$s['pay_private_key']);
  return 'WECHATPAY2-SHA256-RSA2048 mchid="'.$s['pay_mchid'].'",nonce_str="'.$nonce.'",timestamp="'.$timestamp.'",serial_no="'.$s['pay_serial'].'",signature="'.$signature.'"';
 }
 public function verify($headers,$body){
  $h=[];foreach($headers as $key=>$value)$h[strtolower($key)]=is_array($value)?end($value):$value;
  $timestamp=$h['wechatpay-timestamp']??'';$nonce=$h['wechatpay-nonce']??'';$signature=$h['wechatpay-signature']??'';$serial=$h['wechatpay-serial']??'';
  if(!is_string($timestamp)||!ctype_digit($timestamp)||abs($this->now()-intval($timestamp))>300||!is_string($nonce)||!preg_match('/^[A-Za-z0-9_-]{8,128}$/D',$nonce)||!is_string($serial)||!hash_equals($this->settings['pay_public_id'],$serial))throw new \RuntimeException('微信支付响应的签名信息无效，请核对支付公钥配置');
  $raw=is_string($signature)?base64_decode($signature,true):false;
  if($raw===false||@openssl_verify($timestamp."\n".$nonce."\n".$body."\n",$raw,$this->settings['pay_public_key'],OPENSSL_ALGO_SHA256)!==1)throw new \RuntimeException('微信支付响应验签失败');
  return true;
 }
 private function request($method,$path,$payload=null){
  if(!preg_match('~^/v3/pay/transactions/(?:jsapi|out-trade-no/[A-Za-z0-9_-]{6,32}(?:/close)?(?:\?mchid=[0-9]+)?)$~D',$path))throw new \RuntimeException('支付请求地址无效');
  $body=$payload===null?'':json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$nonce=bin2hex(random_bytes(16));$headers=['Content-Type'=>'application/json','Accept'=>'application/json','Authorization'=>$this->authorization($method,$path,$body,$this->now(),$nonce)];
  if($this->transport){$response=($this->transport)($method,$path,$body,$headers);}
  else{
   $received=[];$curl=curl_init('https://api.mch.weixin.qq.com'.$path);$sent=[];foreach($headers as $key=>$value)$sent[]=$key.': '.$value;
   curl_setopt_array($curl,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>$sent,CURLOPT_HEADERFUNCTION=>function($curl,$line)use(&$received){$p=strpos($line,':');if($p!==false)$received[strtolower(trim(substr($line,0,$p)))]=trim(substr($line,$p+1));return strlen($line);}]);
   if($body!=='')curl_setopt($curl,CURLOPT_POSTFIELDS,$body);$raw=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
   if($raw===false||!$status)throw new \RuntimeException('支付通信结果未确认，请在订单中刷新核对；不会将此状态当作支付成功');
   $response=['status'=>$status,'headers'=>$received,'body'=>$raw];
  }
  if(!is_array($response)||!is_string($response['body']??null)||strlen($response['body'])>1024*1024)throw new \RuntimeException('支付响应格式无效');
  // Empty 204 responses carry the same signed headers; never skip verification on success.
  $this->verify($response['headers']??[],$response['body']);$data=$response['body']===''?[]:json_decode($response['body'],true,32,JSON_THROW_ON_ERROR);
  if(($response['status']??0)<200||$response['status']>=300)throw new \RuntimeException('微信支付未受理请求：'.preg_replace('/[^A-Z0-9_]/','',(string)($data['code']??'REMOTE_ERROR')));
  return $data;
 }
 public function prepay($order,$openid){
  $s=$this->settings;$data=$this->request('POST','/v3/pay/transactions/jsapi',['appid'=>$order['appid'],'mchid'=>$order['mchid'],'description'=>mb_strcut($order['title'],0,120,'UTF-8'),'out_trade_no'=>$order['out_trade_no'],'time_expire'=>date('c',$order['expires']),'notify_url'=>$s['pay_notify_url'],'amount'=>['total'=>intval($order['total_fen']),'currency'=>'CNY'],'payer'=>['openid'=>$openid]]);
  if(!preg_match('/^[A-Za-z0-9_-]{10,128}$/D',$data['prepay_id']??''))throw new \RuntimeException('微信支付未返回有效的预支付凭证');return $data['prepay_id'];
 }
 public function mini_parameters($prepay){if(!is_string($prepay)||!preg_match('/^[A-Za-z0-9_-]{10,128}$/D',$prepay))throw new \RuntimeException('预支付凭证无效');$timestamp=(string)$this->now();$nonce=bin2hex(random_bytes(16));$package='prepay_id='.$prepay;return ['timeStamp'=>$timestamp,'nonceStr'=>$nonce,'package'=>$package,'signType'=>'RSA','paySign'=>self::sign($this->settings['appid']."\n".$timestamp."\n".$nonce."\n".$package."\n",$this->settings['pay_private_key'])];}
 public function query($number){return $this->request('GET','/v3/pay/transactions/out-trade-no/'.$number.'?mchid='.$this->settings['pay_mchid']);}
 public function close($number){return $this->request('POST','/v3/pay/transactions/out-trade-no/'.$number.'/close',['mchid'=>$this->settings['pay_mchid']]);}
 public function notification($headers,$body){
  if(!is_string($body)||strlen($body)>65536)throw new \RuntimeException('支付通知过大');$this->verify($headers,$body);$envelope=json_decode($body,true,16,JSON_THROW_ON_ERROR);$resource=$envelope['resource']??[];
  if(($envelope['event_type']??'')!=='TRANSACTION.SUCCESS'||($resource['algorithm']??'')!=='AEAD_AES_256_GCM'||!is_string($resource['nonce']??null)||strlen($resource['nonce'])!==12||!is_string($resource['associated_data']??'')||strlen($resource['associated_data']??'')>256)throw new \RuntimeException('支付通知类型或加密参数无效');
  $encrypted=base64_decode($resource['ciphertext']??'',true);if($encrypted===false||strlen($encrypted)<17)throw new \RuntimeException('支付通知密文无效');
  $plain=openssl_decrypt(substr($encrypted,0,-16),'aes-256-gcm',$this->settings['pay_api_key'],OPENSSL_RAW_DATA,$resource['nonce'],substr($encrypted,-16),$resource['associated_data']??'');if($plain===false)throw new \RuntimeException('支付通知解密失败');
  $data=json_decode($plain,true,16,JSON_THROW_ON_ERROR);if(($data['trade_state']??'')!=='SUCCESS')throw new \RuntimeException('支付通知不是成功交易');return $data;
 }
 public function openid($code){
  if(!is_string($code)||!preg_match('/^[A-Za-z0-9_-]{10,256}$/D',$code))throw new \InvalidArgumentException('请重新获取微信登录凭证');$s=$this->settings;
  $curl=curl_init('https://api.weixin.qq.com/sns/jscode2session?'.http_build_query(['appid'=>$s['appid'],'secret'=>$s['secret'],'js_code'=>$code,'grant_type'=>'authorization_code']));curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);$body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);$data=json_decode($body?:'',true);
  if($status!==200||!empty($data['errcode'])||!preg_match('/^[A-Za-z0-9_-]{16,128}$/D',$data['openid']??''))throw new \RuntimeException('微信身份验证失败，请重新登录后重试');return $data['openid'];
 }
}
