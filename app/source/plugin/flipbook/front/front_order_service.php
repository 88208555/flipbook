<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

class front_order_service {
 private $store;private $pay;private $config;
 public function __construct($store,$pay,$config){$this->store=$store;$this->pay=$pay;$this->config=$config;}
 public static function money($value){
  if(!is_string($value)&&!is_int($value))throw new \InvalidArgumentException('商品价格格式无效');
  if(!preg_match('/^(0|[1-9][0-9]{0,6})(?:\.([0-9]{1,2}))?$/D',(string)$value,$match))throw new \InvalidArgumentException('商品价格需为有效的两位小数金额');
  $fen=intval($match[1])*100+intval(str_pad($match[2]??'',2,'0'));if($fen<=0||$fen>100000000)throw new \InvalidArgumentException('商品价格超出可交易范围');return $fen;
 }
 public static function address($raw){
  if(!is_array($raw))throw new \InvalidArgumentException('请填写收货地址');$out=[];
  foreach(['name'=>40,'phone'=>30,'province'=>40,'city'=>40,'district'=>40,'detail'=>160] as $key=>$limit){$value=$raw[$key]??'';if(!is_scalar($value))throw new \InvalidArgumentException('收货地址格式无效');$value=trim((string)$value);if(mb_strlen($value,'UTF-8')>$limit||preg_match('/[\x00-\x1f\x7f<>]/',$value))throw new \InvalidArgumentException('收货地址内容无效');$out[$key]=$value;}
  if($out['name']===''||!preg_match('/^[+0-9][0-9 ()-]{4,29}$/D',$out['phone'])||$out['province']===''||$out['city']===''||$out['detail']==='')throw new \InvalidArgumentException('请填写完整的姓名、电话和收货地址');return $out;
 }
 public static function public_order($row,$address=false){
  $snapshot=json_decode($row['snapshot'],true)?:[];$out=['id'=>intval($row['orderid']),'number'=>$row['out_trade_no'],'productid'=>intval($row['productid']),'skuid'=>intval($row['skuid']),'quantity'=>intval($row['quantity']),'title'=>$row['title'],'snapshot'=>$snapshot,'unitFen'=>intval($row['unit_fen']),'shippingFen'=>intval($row['shipping_fen']),'totalFen'=>intval($row['total_fen']),'total'=>number_format($row['total_fen']/100,2,'.',''),'status'=>$row['status'],'created'=>intval($row['created']),'expires'=>intval($row['expires']),'paidAt'=>intval($row['paid_at']),'carrier'=>$row['carrier'],'tracking'=>$row['tracking']];
  if($address)$out['address']=json_decode($row['address'],true)?:[];return $out;
 }
 private function owned($uid,$id){$row=$this->store->by_id($uid,$id);if(!$row)throw new \RuntimeException('订单不存在或无权查看');return $row;}
 public function create($uid,$input){
  if($uid<=0)throw new \RuntimeException('请先登录');$key=$input['requestKey']??'';if(!is_string($key)||!preg_match('/^[A-Za-z0-9_-]{16,64}$/D',$key))throw new \InvalidArgumentException('下单凭证无效，请返回商品重新下单');
  $productid=filter_var($input['productid']??null,FILTER_VALIDATE_INT);$skuid=filter_var($input['skuid']??0,FILTER_VALIDATE_INT);$quantity=filter_var($input['quantity']??null,FILTER_VALIDATE_INT);
  if(!$productid||$productid<1||$skuid===false||$skuid<0||!$quantity||$quantity<1||$quantity>99)throw new \InvalidArgumentException('请选择有效商品、SKU 和 1–99 件数量');
  $quoted=filter_var($input['expectedTotalFen']??null,FILTER_VALIDATE_INT);if(!$quoted||$quoted<1||$quoted>100000000)throw new \InvalidArgumentException('请重新确认商品金额');
  $address=self::address($input['address']??null);$digest=hash('sha256',json_encode([$productid,$skuid,$quantity,$address,$quoted],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  $row=$this->store->transaction(function()use($uid,$key,$digest,$input,$productid,$skuid,$quantity,$address,$quoted){
   $old=$this->store->by_request($uid,$key);if($old){if(!hash_equals($old['request_digest'],$digest))throw new \RuntimeException('同一下单凭证不可用于不同商品或地址');return $old;}
   if($this->store->pending_count($uid,$this->pay->now()-60)>=10)throw new \RuntimeException('下单过于频繁，请稍后重试');
   $product=$this->store->product($productid);if(!$product||!intval($product['available']))throw new \RuntimeException('商品已下架');
   if(($product['currency']??'CNY')!=='CNY'||($this->config['currency']??'CNY')!=='CNY'||empty($product['priceshow']))throw new \RuntimeException('该商品尚未开放人民币交易');
   $sku=null;$skus=$this->store->skus($productid);foreach($skus as $item)if(intval($item['skuid'])===$skuid)$sku=$item;
   if(($skus&&!$sku)||(!$skus&&$skuid))throw new \RuntimeException('所选 SKU 不属于该商品或已停用');
   $unit=self::money($product['pricevalue']);$shipping=intval($this->config['shippingFen']??0);$total=$unit*$quantity+$shipping;if($total<=0||$total>100000000)throw new \RuntimeException('订单金额超出可交易范围');if($total!==$quoted)throw new \RuntimeException('商品价格或运费已更新，请刷新并确认金额后重试');
   $openid=$this->pay->openid($input['code']??'');$images=$sku?(json_decode($sku['images'],true)?:[]):[];
   $snapshot=['name'=>$product['name'],'code'=>$product['code'],'image'=>$images[0]??$product['cover'],'sku'=>$sku?['name'=>$sku['name'],'code'=>$sku['code'],'color'=>$sku['color'],'size'=>$sku['size']]:null];$now=$this->pay->now();
   $row=['uid'=>$uid,'request_key'=>$key,'request_digest'=>$digest,'out_trade_no'=>'FB'.gmdate('YmdHis',$now).strtoupper(bin2hex(random_bytes(8))),'appid'=>$this->config['appid'],'mchid'=>$this->config['mchid'],'productid'=>$productid,'skuid'=>$skuid,'quantity'=>$quantity,'unit_fen'=>$unit,'shipping_fen'=>$shipping,'total_fen'=>$total,'title'=>$product['name'],'snapshot'=>json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'address'=>front_service_config::encrypt(json_encode($address,JSON_UNESCAPED_UNICODE)),'payer'=>front_service_config::encrypt($openid),'status'=>'pending','transaction_id'=>null,'prepay'=>'','created'=>$now,'expires'=>$now+1800,'paid_at'=>0,'closed_at'=>0,'shipped_at'=>0,'carrier'=>'','tracking'=>''];
   // 2 + 14 + 16 = 32 ASCII characters, as required by the merchant order-number protocol.
   $row['orderid']=$this->store->insert($row);return $row;
  });
  try{return $this->payment($uid,$row['orderid']);}
  catch(\Throwable $error){
   // The order already exists. Return its identity so an uncertain prepay never loses the customer's recovery path.
   return ['order'=>$this->project($this->owned($uid,$row['orderid']),true),'paymentPending'=>true,'message'=>'订单已创建，支付结果尚未确认，请在订单中继续支付或刷新状态'];
  }
 }
 public function payment($uid,$id){
  $row=$this->owned($uid,$id);if($row['status']!=='pending')return ['order'=>$this->project($row,true)];
  if($row['expires']<=$this->pay->now())throw new \RuntimeException('订单支付时间已过，请刷新核对后关闭订单并重新下单');
  if($row['appid']!==$this->config['appid']||$row['mchid']!==$this->config['mchid'])throw new \RuntimeException('当前商户配置与订单不一致，请联系商家');
  // Serialize prepay creation for this order. A repeated callback/query never creates another order.
  return $this->store->transaction(function()use($uid,$row){
   $current=$this->store->by_number($row['out_trade_no'],true);if($current['status']!=='pending')return ['order'=>$this->project($current,true)];
   $prepay=front_service_config::decrypt($current['prepay']);
   if($prepay===''){$prepay=$this->pay->prepay($current,front_service_config::decrypt($current['payer']));$this->store->update($current['orderid'],['prepay'=>front_service_config::encrypt($prepay)]);}
   return ['order'=>$this->project($current,true),'payment'=>$this->pay->mini_parameters($prepay)];
  });
 }
 public function project($row,$withAddress=false){$safe=$row;if($withAddress)$safe['address']=front_service_config::decrypt($row['address']);return self::public_order($safe,$withAddress);}
 public function apply_success($data){
  $number=$data['out_trade_no']??'';if(!is_string($number)||!preg_match('/^FB[0-9]{14}[A-F0-9]{16}$/D',$number)||($data['trade_state']??'')!=='SUCCESS')throw new \RuntimeException('交易状态无效');
  return $this->store->transaction(function()use($data,$number){
   $row=$this->store->by_number($number,true);if(!$row)throw new \RuntimeException('未找到对应订单');
   if(($data['appid']??'')!==$row['appid']||($data['mchid']??'')!==$row['mchid']||!isset($data['amount']['total'])||!is_int($data['amount']['total'])||$data['amount']['total']!==intval($row['total_fen'])||($data['amount']['currency']??'')!=='CNY'||!is_string($data['payer']['openid']??null)||!hash_equals(front_service_config::decrypt($row['payer']),$data['payer']['openid'])||!preg_match('/^[A-Za-z0-9_-]{10,64}$/D',$data['transaction_id']??''))throw new \RuntimeException('支付回执与本订单的商户、金额或付款人不一致');
   if(in_array($row['status'],['paid','shipped'],true)){if($row['transaction_id']!==$data['transaction_id'])throw new \RuntimeException('订单交易号不一致');return true;}
   $this->store->update($row['orderid'],['status'=>'paid','transaction_id'=>$data['transaction_id'],'paid_at'=>$this->pay->now()]);return true;
  });
 }
 public function detail($uid,$id,$refresh=false){
  $row=$this->owned($uid,$id);
  if($refresh&&$row['status']==='pending'){
   if($row['mchid']!==$this->config['mchid']||$row['appid']!==$this->config['appid'])throw new \RuntimeException('当前商户配置与订单不一致，请联系商家');
   $remote=$this->pay->query($row['out_trade_no']);if(($remote['out_trade_no']??'')!==$row['out_trade_no']||($remote['appid']??'')!==$row['appid']||($remote['mchid']??'')!==$row['mchid'])throw new \RuntimeException('微信查询回执与当前订单不一致');if(($remote['trade_state']??'')==='SUCCESS')$this->apply_success($remote);
   elseif(($remote['trade_state']??'')==='CLOSED')$this->store->transaction(function()use($row){$current=$this->store->by_number($row['out_trade_no'],true);if($current['status']==='pending')$this->store->update($current['orderid'],['status'=>'closed','closed_at'=>$this->pay->now()]);});
   $row=$this->owned($uid,$id);
  }
  return $this->project($row,true);
 }
 public function close($uid,$id){
  $row=$this->owned($uid,$id);if($row['status']!=='pending')return $this->project($row,true);
  if($row['mchid']!==$this->config['mchid']||$row['appid']!==$this->config['appid'])throw new \RuntimeException('当前商户配置与订单不一致，请联系商家');
  $this->pay->close($row['out_trade_no']);
  $this->store->transaction(function()use($row){$current=$this->store->by_number($row['out_trade_no'],true);if($current['status']==='pending')$this->store->update($row['orderid'],['status'=>'closed','closed_at'=>$this->pay->now()]);});return $this->project($this->owned($uid,$id),true);
 }
 public function shipped($number,$carrier,$tracking){
  if(!is_string($number)||!preg_match('/^FB[0-9]{14}[A-F0-9]{16}$/D',$number)||!is_string($carrier)||!is_string($tracking))throw new \InvalidArgumentException('发货信息格式无效');
  $carrier=trim((string)$carrier);$tracking=trim((string)$tracking);if($carrier===''||mb_strlen($carrier)>80||preg_match('/[\x00-\x1f\x7f<>]/',$carrier)||!preg_match('/^[A-Za-z0-9-]{5,80}$/D',$tracking))throw new \InvalidArgumentException('请填写有效物流公司和运单号');
  return $this->store->transaction(function()use($number,$carrier,$tracking){$row=$this->store->by_number($number,true);if(!$row||!in_array($row['status'],['paid','shipped'],true))throw new \RuntimeException('仅已付款订单可以记录发货');$this->store->update($row['orderid'],['status'=>'shipped','carrier'=>$carrier,'tracking'=>$tracking,'shipped_at'=>$this->pay->now()]);return true;});
 }
}
