<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

class front_commerce {
 public static function validate_settings($values){
  foreach(['wechat_orders_enabled','wechat_trade_enabled','wechat_customer_enabled','wechat_customer_card'] as $key)if(isset($values[$key])&&!in_array($values[$key],['0','1'],true))return '交易或客服开关格式不正确';
  foreach(['wechat_trade_buy_text'=>10,'wechat_customer_title'=>20,'wechat_customer_session'=>80] as $key=>$limit){$value=$values[$key]??'';if(mb_strlen($value,'UTF-8')>$limit||preg_match('/[\x00-\x1f\x7f<>]/',$value))return '交易与客服文字包含无效内容或超过字数限制';}
  if(!preg_match('/^[0-9]{1,6}$/D',$values['wechat_shipping_fen']??'0')||intval($values['wechat_shipping_fen']??0)>100000)return '每单运费需为 0–100000 分的整数';
  foreach(['wechat_pay_mchid'=>'/^[1-9][0-9]{5,31}$/D','wechat_pay_serial'=>'/^[A-Fa-f0-9]{20,64}$/D','wechat_pay_api_key'=>'/^[A-Za-z0-9]{32}$/D','wechat_pay_public_id'=>'/^(?:PUB_KEY_ID_[A-Za-z0-9_]{10,100}|[A-Fa-f0-9]{20,64})$/D'] as $key=>$pattern)if(($values[$key]??'')!==''&&!preg_match($pattern,$values[$key]))return '微信支付商户号、证书编号、公钥 ID 或 APIv3 密钥格式无效';
  if(($values['wechat_pay_private_key']??'')!==''){$key=@openssl_pkey_get_private($values['wechat_pay_private_key']);$details=$key?openssl_pkey_get_details($key):false;if(!$details||$details['type']!==OPENSSL_KEYTYPE_RSA||$details['bits']<2048)return '商户 API 私钥需为有效的 RSA 2048 位及以上 PEM 私钥';}
  if(($values['wechat_pay_public_key']??'')!==''){$key=@openssl_pkey_get_public($values['wechat_pay_public_key']);$details=$key?openssl_pkey_get_details($key):false;if(!$details||$details['type']!==OPENSSL_KEYTYPE_RSA||$details['bits']<2048)return '微信支付公钥或平台证书需包含有效 RSA 公钥';}
  if(($values['wechat_pay_serial']??'')!==''&&strcasecmp($values['wechat_pay_serial'],$values['wechat_pay_public_id']??'')===0)return '商户证书与微信支付平台验签证书不能使用同一编号';
  $url=$values['wechat_pay_notify_url']??'';
  if($url!==''){$parts=parse_url($url);parse_str($parts['query']??'',$query);if(!$parts||($parts['scheme']??'')!=='https'||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['fragment'])||!preg_match('~/plugin\.php$~D',$parts['path']??'')||($query['id']??'')!=='flipbook:api'||($query['commerce_action']??'')!=='notify')return '请填写本站 HTTPS 支付通知地址，使用页面提供的通知接口路径';}
  if(($values['wechat_trade_enabled']??'0')==='1'){
   if(($values['wechat_orders_enabled']??'0')!=='1')return '请先启用微信订单';
   foreach(['wechat_appid','wechat_secret','wechat_pay_mchid','wechat_pay_serial','wechat_pay_private_key','wechat_pay_api_key','wechat_pay_public_id','wechat_pay_public_key','wechat_pay_notify_url'] as $key)if(($values[$key]??'')==='')return '开启交易前请完整配置小程序与自有微信支付商户信息';
   if(!front_order_store::ready())return '订单数据表尚未安装，请先按插件升级流程安装订单结构；本页面不会自动迁移数据';
  }
  return '';
 }
 public static function state(){
  $orders=front_service_config::get('wechat_orders_enabled')==='1';$ready=$orders&&front_order_store::ready();$trade=front_service_config::get('wechat_trade_enabled')==='1';$reason='';
  if(!$orders||!$trade)$reason='商品交易尚未开放';elseif(!$ready)$reason='订单服务尚未完成安装';else foreach(['appid','secret','pay_mchid','pay_serial','pay_private_key','pay_api_key','pay_public_id','pay_public_key','pay_notify_url'] as $key)if(front_service_config::get('wechat_'.$key)===''){$reason='交易服务配置尚未完成';break;}
  return ['enabled'=>$reason===''&&$ready,'ordersEnabled'=>$ready,'reason'=>$reason,'shippingFen'=>intval(front_service_config::get('wechat_shipping_fen')),'buyText'=>front_service_config::get('wechat_trade_buy_text')?:'立即购买','customer'=>['enabled'=>front_service_config::get('wechat_customer_enabled')==='1','title'=>front_service_config::get('wechat_customer_title')?:'联系客服','sessionFrom'=>front_service_config::get('wechat_customer_session'),'sendCard'=>front_service_config::get('wechat_customer_card')==='1']];
 }
 public static function service(){
  $pay=front_wechat_pay::settings();$settings=front_view::settings();return new front_order_service(new front_order_store(),new front_wechat_pay($pay),['appid'=>$pay['appid'],'mchid'=>$pay['pay_mchid'],'shippingFen'=>intval(front_service_config::get('wechat_shipping_fen')),'currency'=>$settings['currency']]);
 }
 private static function input(){
  $input=$_POST;if(isset($input['address'])){$value=$input['address'];if(!is_string($value)||strlen($value)>3000)throw new \InvalidArgumentException('收货地址格式无效');$input['address']=json_decode($value,true,8,JSON_THROW_ON_ERROR);}return $input;
 }
 private static function headers(){
  $headers=[];foreach(['Timestamp','Nonce','Serial','Signature'] as $key)$headers['Wechatpay-'.$key]=$_SERVER['HTTP_WECHATPAY_'.strtoupper($key)]??'';return $headers;
 }
 public static function handle($action){
  header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');
  if($action==='notify'){
   try{
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!front_order_store::ready())throw new \RuntimeException('订单通知服务不可用');
    $body=file_get_contents('php://input',false,null,0,65537);$pay=new front_wechat_pay(front_wechat_pay::settings());self::service()->apply_success($pay->notification(self::headers(),$body));http_response_code(204);
   }catch(\Throwable $error){http_response_code(400);echo json_encode(['code'=>'FAIL','message'=>'支付通知未通过验证，请稍后重试'],JSON_UNESCAPED_UNICODE);}
   exit;
  }
  try{
   $state=self::state();if($action==='state')self::out(['state'=>$state]);
   $uid=front_helper::uid();if($uid<=0)self::out(['code'=>'needlogin','message'=>'请先登录'],401);
   if(!$state['ordersEnabled'])self::out(['code'=>'disabled','message'=>$state['reason']?:'订单功能尚未开放'],409);
   if(in_array($action,['create','pay','refresh','close'],true)){
    if($_SERVER['REQUEST_METHOD']!=='POST'||!is_string($_POST['formhash']??null)||!hash_equals(front_helper::formhash(),$_POST['formhash']))self::out(['code'=>'formhash','message'=>'页面已过期，请刷新重试'],403);
    if(in_array($action,['create','pay'],true)&&!$state['enabled'])self::out(['code'=>'disabled','message'=>$state['reason']],409);
   }
   $id=filter_var($_GET['orderid']??$_POST['orderid']??0,FILTER_VALIDATE_INT);$service=self::service();
   if($action==='orders'){
    $page=max(1,min(10000,intval($_GET['page']??1)));$status=is_string($_GET['status']??null)?$_GET['status']:'';$rows=(new front_order_store())->list($uid,$page,intval(front_composer::schema()['listDefaults']['pageSize']),$status);$rows['items']=array_map(fn($row)=>$service->project($row),$rows['items']);self::out(['orders'=>$rows]);
   }elseif($action==='order'&&$id>0)self::out(['order'=>$service->detail($uid,$id)]);
   elseif($action==='create')self::out($service->create($uid,self::input()));
   elseif($action==='pay'&&$id>0)self::out($service->payment($uid,$id));
   elseif($action==='refresh'&&$id>0)self::out(['order'=>$service->detail($uid,$id,true)]);
   elseif($action==='close'&&$id>0)self::out(['order'=>$service->close($uid,$id)]);
   else self::out(['message'=>'订单操作或编号无效'],400);
  }catch(\Throwable $error){self::out(['message'=>$error instanceof \RuntimeException||$error instanceof \InvalidArgumentException?$error->getMessage():'订单处理失败，请稍后在订单列表核对'],400);}
 }
 private static function out($data,$status=200){http_response_code($status);echo json_encode(['ok'=>$status===200?1:0,'formhash'=>front_helper::formhash()]+$data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
}
