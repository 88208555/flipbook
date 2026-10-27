<?php
define('IN_DISCUZ',true);$_G=['config'=>['security'=>['authkey'=>'isolated-commerce-fixture-key']]];
$root=getenv('FLIPBOOK_PLUGIN_ROOT')?:'/var/www/html/source/plugin/flipbook';
foreach(['front_service_config','front_wechat_pay','front_order_store','front_order_service','front_commerce'] as $name)require $root.'/front/'.$name.'.php';
use flipbook\front_wechat_pay as Pay;use flipbook\front_order_service as Orders;use flipbook\front_service_config as Config;
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n".$e->getTraceAsString()."\n");exit(1);});
function check($condition,$text){if(!$condition)throw new RuntimeException($text);echo 'PASS '.$text."\n";}
function rejects($run,$text){$bad=false;try{$run();}catch(Throwable $e){$bad=true;}check($bad,$text);}
function keys(){ $key=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);openssl_pkey_export($key,$pem);return [$pem,openssl_pkey_get_details($key)['key']]; }
[$merchant,$merchantPublic]=keys();[$platform,$platformPublic]=keys();$now=time();
$settings=['appid'=>'wx0000000000000000','secret'=>'isolated-secret','pay_mchid'=>'1234567890','pay_serial'=>str_repeat('A',40),'pay_private_key'=>$merchant,'pay_api_key'=>str_repeat('x',32),'pay_public_id'=>'PUB_KEY_ID_0123456789ABCDEF','pay_public_key'=>$platformPublic,'pay_notify_url'=>'https://album.example.test/plugin.php?id=flipbook:api&commerce_action=notify'];
function signed($body,$key,$id,$timestamp){$nonce='fixture_nonce_123456';return ['Wechatpay-Timestamp'=>(string)$timestamp,'Wechatpay-Nonce'=>$nonce,'Wechatpay-Serial'=>$id,'Wechatpay-Signature'=>Pay::sign($timestamp."\n".$nonce."\n".$body."\n",$key)];}
$http=[];$transport=function($method,$path,$body,$headers)use(&$http,$merchantPublic,$platform,$settings,$now){
 $http[]=[$method,$path,json_decode($body,true)];preg_match_all('/([a-z_]+)="([^"]+)"/',$headers['Authorization'],$matches,PREG_SET_ORDER);$auth=[];foreach($matches as $m)$auth[$m[1]]=$m[2];
 check(openssl_verify($method."\n".$path."\n".$auth['timestamp']."\n".$auth['nonce_str']."\n".$body."\n",base64_decode($auth['signature']),$merchantPublic,OPENSSL_ALGO_SHA256)===1,'merchant HTTP signature covers exact method, path, timestamp, nonce and request bytes');
 $result=str_ends_with($path,'/close')?'':'{"prepay_id":"wx_test_prepay_123456"}';return ['status'=>$result===''?204:200,'body'=>$result,'headers'=>signed($result,$platform,$settings['pay_public_id'],$now)];
};$pay=new Pay($settings,$transport,fn()=>$now);
$order=['appid'=>$settings['appid'],'mchid'=>$settings['pay_mchid'],'title'=>'测试商品','out_trade_no'=>'FB'.gmdate('YmdHis',$now).str_repeat('A',16),'total_fen'=>39900,'expires'=>$now+1800];
$prepay=$pay->prepay($order,'openid_fixture_1234');check($http[0][2]['amount']['total']===39900&&$http[0][2]['amount']['currency']==='CNY','JSAPI prepay sends the server amount in integer cents');
$params=$pay->mini_parameters($prepay);check(openssl_verify($settings['appid']."\n".$params['timeStamp']."\n".$params['nonceStr']."\n".$params['package']."\n",base64_decode($params['paySign']),$merchantPublic,OPENSSL_ALGO_SHA256)===1,'native payment parameters carry a valid merchant RSA signature');
$body='{"valid":true}';$headers=signed($body,$platform,$settings['pay_public_id'],$now);check($pay->verify($headers,$body),'valid platform response is accepted');
rejects(fn()=>$pay->verify($headers,$body.' '),'tampered response body is rejected');$stale=signed($body,$platform,$settings['pay_public_id'],$now-301);rejects(fn()=>$pay->verify($stale,$body),'expired signature timestamp is rejected');$other=$headers;$other['Wechatpay-Serial']='PUB_KEY_ID_other_123456';rejects(fn()=>$pay->verify($other,$body),'unknown platform public key ID is rejected');rejects(fn()=>$pay->verify([],$body),'unsigned response is rejected');
$pay->close($order['out_trade_no']);check($http[1][0]==='POST','empty 204 close response is verified without accepting unsigned bytes');
$success=['appid'=>$settings['appid'],'mchid'=>$settings['pay_mchid'],'out_trade_no'=>$order['out_trade_no'],'transaction_id'=>'42000000000000000001','trade_state'=>'SUCCESS','amount'=>['total'=>39900,'currency'=>'CNY'],'payer'=>['openid'=>'openid_fixture_1234']];
$nonce='123456789012';$plain=json_encode($success);$encrypted=openssl_encrypt($plain,'aes-256-gcm',$settings['pay_api_key'],OPENSSL_RAW_DATA,$nonce,$tag,'transaction');$envelope=['id'=>'fixture_notification','event_type'=>'TRANSACTION.SUCCESS','resource'=>['algorithm'=>'AEAD_AES_256_GCM','nonce'=>$nonce,'associated_data'=>'transaction','ciphertext'=>base64_encode($encrypted.$tag)]];$raw=json_encode($envelope);$event=$pay->notification(signed($raw,$platform,$settings['pay_public_id'],$now),$raw);check($event===$success,'notification is signature verified and authenticated AES-GCM decrypted');
$envelope['resource']['ciphertext']=base64_encode($encrypted.str_repeat('0',16));$bad=json_encode($envelope);rejects(fn()=>$pay->notification(signed($bad,$platform,$settings['pay_public_id'],$now),$bad),'invalid GCM authentication tag cannot change an order');
class Store {
 public $rows=[],$next=1,$forcedCount=0;
 public $product=['productid'=>1,'available'=>1,'priceshow'=>1,'pricevalue'=>'399.00','currency'=>'CNY','name'=>'棉衬衫','code'=>'P-1','cover'=>'/cover.png'];
 public $skus=[['skuid'=>2,'productid'=>1,'available'=>1,'name'=>'米白 M','code'=>'SKU-M','color'=>'米白','size'=>'M','images'=>'["/sku.png"]']];
 public function transaction($run){$before=$this->rows;try{return $run();}catch(Throwable $e){$this->rows=$before;throw $e;}}
 public function by_request($uid,$key){foreach($this->rows as $row)if($row['uid']===$uid&&$row['request_key']===$key)return $row;return null;}
 public function by_id($uid,$id){$row=$this->rows[$id]??null;return $row&&$row['uid']===$uid?$row:null;}
 public function by_number($number,$lock=false){foreach($this->rows as $row)if($row['out_trade_no']===$number)return $row;return null;}
 public function product($id){return $id===1?$this->product:null;}
 public function skus($id){return $this->skus;}
 public function insert($row){$id=$this->next++;$row['orderid']=$id;$this->rows[$id]=$row;return $id;}
 public function update($id,$values){if(!empty($values['transaction_id']))foreach($this->rows as $other=>$row)if($other!==$id&&$row['transaction_id']===$values['transaction_id'])throw new RuntimeException('unique transaction');$this->rows[$id]=array_merge($this->rows[$id],$values);}
 public function pending_count($uid,$since){return $this->forcedCount;}
}
class Gateway extends Pay {
 public $prepayCalls=0,$prepayFails=false,$closeFails=false,$remote;
 public function openid($code){if($code!=='valid_code_123456')throw new RuntimeException('invalid isolated WeChat code');return 'openid_customer_12345';}
 public function prepay($order,$openid){$this->prepayCalls++;if($this->prepayFails)throw new RuntimeException('uncertain prepay');return 'wx_isolated_prepay_123456';}
 public function query($number){return $this->remote;}
 public function close($number){if($this->closeFails)throw new RuntimeException('uncertain network');return [];}
}
$store=new Store();$gateway=new Gateway($settings,null,fn()=>$now);$service=new Orders($store,$gateway,['appid'=>$settings['appid'],'mchid'=>$settings['pay_mchid'],'shippingFen'=>500,'currency'=>'CNY']);
$input=['productid'=>1,'skuid'=>2,'quantity'=>2,'requestKey'=>'checkout_request_123456','code'=>'valid_code_123456','expectedTotalFen'=>80300,'address'=>['name'=>'测试收货人','phone'=>'13800000000','province'=>'上海','city'=>'上海','district'=>'测试区','detail'=>'仅隔离测试地址']];
$result=$service->create(7,$input);$id=$result['order']['id'];check(count($store->rows)===1&&$result['order']['totalFen']===80300&&$result['order']['snapshot']['sku']['code']==='SKU-M','order snapshots its real SKU and authoritative product price plus configured shipping');
$repeated=$input;$repeated['code']='';$second=$service->create(7,$repeated);check(count($store->rows)===1&&$second['order']['id']===$id&&$gateway->prepayCalls===1,'retrying the same checkout is idempotent and does not repeat prepay');
$changed=$input;$changed['quantity']=3;rejects(fn()=>$service->create(7,$changed),'same checkout key cannot be repurposed for a different purchase');
$bad=$input;$bad['requestKey']='other_checkout_123456';$bad['expectedTotalFen']=1;rejects(fn()=>$service->create(7,$bad),'client-supplied low price cannot override the server total');check(count($store->rows)===1,'price mismatch creates no order');
$bad=$input;$bad['requestKey']='wrong_sku_123456789';$bad['skuid']=999;rejects(fn()=>$service->create(7,$bad),'foreign or disabled SKU is rejected');
rejects(fn()=>$service->detail(8,$id),'another member cannot read the order');rejects(fn()=>$service->payment(8,$id),'another member cannot pay the order');rejects(fn()=>$service->close(8,$id),'another member cannot close the order');
$row=$store->rows[$id];check(strpos($row['address'],'测试收货人')===false&&strpos($row['payer'],'openid_customer')===false,'stored address and payer identity are encrypted');$public=$service->project($row);check(!array_intersect(['address','payer','request_key','transaction_id'],array_keys($public)),'order list omits address, payer identity, payment reference and private request fields');
$gateway->closeFails=true;rejects(fn()=>$service->close(7,$id),'uncertain remote close is reported as unconfirmed');check($store->rows[$id]['status']==='pending','unconfirmed close cannot mark the local order closed');
$paid=['appid'=>$row['appid'],'mchid'=>$row['mchid'],'out_trade_no'=>$row['out_trade_no'],'transaction_id'=>'42000000000000000002','trade_state'=>'SUCCESS','amount'=>['total'=>80300,'currency'=>'CNY'],'payer'=>['openid'=>'openid_customer_12345']];$wrong=$paid;$wrong['amount']['total']=1;rejects(fn()=>$service->apply_success($wrong),'signed success with mismatched amount still cannot mark paid');
$service->apply_success($paid);$service->apply_success($paid);check($store->rows[$id]['status']==='paid'&&$store->rows[$id]['transaction_id']===$paid['transaction_id'],'verified success is applied once and repeat delivery stays idempotent');
$altered=$paid;$altered['transaction_id']='42000000000000000003';rejects(fn()=>$service->apply_success($altered),'conflicting transaction ID cannot replace the paid reference');
$service->shipped($row['out_trade_no'],'测试物流','TEST123456');check($store->rows[$id]['status']==='shipped','merchant can record fulfillment for a paid order');$service->apply_success($paid);check($store->rows[$id]['status']==='shipped','replayed payment notification never downgrades fulfillment state');
foreach(['NaN','1e3','-1','0.00','01.00','1.001'] as $value)rejects(fn()=>Orders::money($value),'invalid money rejected: '.$value);check(Orders::money('0.01')===1&&Orders::money('399.00')===39900,'integer cent conversion has no float arithmetic');
$uncertain=$input;$uncertain['requestKey']='uncertain_checkout_123456';$gateway->prepayFails=true;$recovery=$service->create(7,$uncertain);$gateway->prepayFails=false;
check($recovery['paymentPending']&&$recovery['order']['id']>0&&$recovery['order']['status']==='pending','uncertain prepay returns the saved order identity for customer recovery');
$retry=$service->create(7,$uncertain);check($retry['order']['id']===$recovery['order']['id']&&isset($retry['payment']),'retrying uncertain prepay continues the same saved order');
echo "NOTE Isolated in-memory orders and synthetic signatures only; no database migration, real WeChat requests, orders, messages or payments.\n";
