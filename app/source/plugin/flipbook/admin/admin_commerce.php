<?php
namespace flipbook;
if(!defined('IN_DISCUZ')||!defined('IN_ADMINCP'))exit('Access Denied');

class admin_commerce {
 public static function groups(){return ['orders'=>['wechat_orders_enabled'],'trade'=>['wechat_trade_enabled','wechat_trade_buy_text','wechat_shipping_fen','wechat_pay_mchid','wechat_pay_serial','wechat_pay_private_key','wechat_pay_api_key','wechat_pay_public_id','wechat_pay_public_key','wechat_pay_notify_url'],'customer'=>['wechat_customer_enabled','wechat_customer_title','wechat_customer_session','wechat_customer_card']];}
 public static function dispatch($action){
  if(strpos($action,'commerce-')!==0)return;admin_helper::needperm();
  try{
   if($action==='commerce-orders'){
    if(!front_order_store::ready())self::out(['orders'=>['items'=>[],'total'=>0,'page'=>1,'pageSize'=>20],'installed'=>false]);
    $page=max(1,min(10000,intval($_GET['page']??1)));$status=is_string($_GET['status']??null)?$_GET['status']:'';$rows=(new front_order_store())->list(0,$page,20,$status);$service=front_commerce::service();$rows['items']=array_map(fn($row)=>$service->project($row,true),$rows['items']);self::out(['orders'=>$rows,'installed'=>true]);
   }
   admin_helper::needperm('edit');if($_SERVER['REQUEST_METHOD']!=='POST'||!is_string($_POST['formhash']??null)||!hash_equals((string)formhash(),$_POST['formhash']))throw new \RuntimeException('管理会话已过期，请刷新后重试');
   if($action==='commerce-save'){
    $group=is_string($_POST['group']??null)?$_POST['group']:'';$keys=self::groups()[$group]??[];if(!$keys)throw new \InvalidArgumentException('配置分组无效');$values=[];$clear=[];
    foreach($keys as $key){if(!is_scalar($_POST[$key]??''))throw new \InvalidArgumentException('配置内容格式无效');$values[$key]=(string)($_POST[$key]??'');if(!empty($_POST['clear_'.$key])){$clear[]=$key;$values[$key]='';}
     if(in_array($key,['wechat_pay_private_key','wechat_pay_public_key'],true)&&isset($_FILES[$key])&&($_FILES[$key]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$file=$_FILES[$key];if(is_array($file['name']??null)||($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??'')||intval($file['size']??0)<1||intval($file['size'])>20000||!in_array(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION)),['key','pem','crt','cer'],true))throw new \InvalidArgumentException('支付密钥文件需为 20KB 以内的 PEM 格式');$values[$key]=file_get_contents($file['tmp_name']);}
    }
    $updates=front_service_config::prepare_updates($values,$clear);foreach($updates as $key=>$value)table_flipbook_setting::t()->update_setting($key,$value,'1');$secrets=[];foreach($keys as $key)if((front_service_config::schema()[$key]['type']??'')==='secret')$secrets[$key]=front_service_config::get($key)!=='';
    self::out(['message'=>'配置已保存','state'=>front_commerce::state(),'secrets'=>$secrets]);
   }elseif($action==='commerce-ship'){
    if(!front_order_store::ready())throw new \RuntimeException('请先完成画册插件升级');front_commerce::service()->shipped($_POST['number']??'',$_POST['carrier']??'',$_POST['tracking']??'');self::out(['message'=>'发货记录已保存']);
   }else throw new \InvalidArgumentException('配置操作不存在');
  }catch(\Throwable $error){self::out(['message'=>$error instanceof \RuntimeException||$error instanceof \InvalidArgumentException?$error->getMessage():'操作失败，请核对订单服务配置'],400);}
 }
 private static function out($data,$status=200){if(ob_get_level()>0)ob_end_clean();http_response_code($status);header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');echo json_encode(['ok'=>$status===200?1:0]+$data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
 private static function row($title,$html,$help=''){echo '<div class="fb-admin-form-row"><div class="fb-admin-form-label">'.admin_helper::e($title).'</div><div class="fb-admin-form-field">'.$html.($help!==''?'<p class="fb-admin-form-help">'.$help.'</p>':'').'</div></div>';}
 private static function toggle($key,$value,$auto=false){
  $title=front_service_config::schema()[$key]['title'];return '<button type="button" form="fb-commerce-async-controls" class="fb-admin-button fb-commerce-toggle" role="switch" aria-label="'.admin_helper::e($title).'" aria-checked="'.($value==='1'?'true':'false').'" value="'.($value==='1'?'1':'0').'" data-commerce-setting="'.$key.'" data-commerce-toggle'.($auto?' data-commerce-auto-save':'').'><span class="fb-size-toggle-track" aria-hidden="true"><span class="fb-size-toggle-thumb"></span></span></button>';
 }
 private static function form($group){
  $schema=front_service_config::schema();$e=[admin_helper::class,'e'];
  echo '<div class="fb-admin-wechat-form" role="form" aria-label="'.$e(['orders'=>'微信订单配置','trade'=>'交易组件配置','customer'=>'小程序客服配置'][$group]).'" data-commerce-form="'.$group.'">';
  foreach(self::groups()[$group] as $key){$field=$schema[$key];$value=front_service_config::get($key);$help='';
   if($field['type']==='bool'){$html=self::toggle($key,$value);}
   elseif($field['type']==='secret'){
    $pem=in_array($key,['wechat_pay_private_key','wechat_pay_public_key'],true);$placeholder=$value!==''?'已配置，留空保留':'尚未配置';
    $html=$pem?'<textarea form="fb-commerce-async-controls" data-commerce-setting="'.$key.'" aria-label="'.$e($field['title']).'" rows="4" maxlength="20000" placeholder="'.$placeholder.'"></textarea><input form="fb-commerce-async-controls" type="file" accept=".key,.pem,.crt,.cer" data-commerce-file="'.$key.'" aria-label="上传'.$e($field['title']).'">':'<input form="fb-commerce-async-controls" type="password" autocomplete="new-password" data-commerce-setting="'.$key.'" aria-label="'.$e($field['title']).'" maxlength="32" placeholder="'.$placeholder.'">';
    $html.='<label class="fb-admin-form-help"><input form="fb-commerce-async-controls" type="checkbox" data-commerce-clear="'.$key.'">清除已保存内容</label>';
   }else{$html='<input form="fb-commerce-async-controls" type="'.($key==='wechat_shipping_fen'?'number':($key==='wechat_pay_notify_url'?'url':'text')).'" data-commerce-setting="'.$key.'" aria-label="'.$e($field['title']).'" value="'.$e($value).'" maxlength="'.($key==='wechat_trade_buy_text'?10:300).'"'.($key==='wechat_shipping_fen'?' min="0" max="100000" step="1"':'').'>';}
   if($key==='wechat_orders_enabled')$help=front_order_store::ready()?'订单服务已安装；会员仅能查看自己的订单。':'请先按画册插件的升级流程完成订单服务安装，再配置交易。';
   if($key==='wechat_shipping_fen')$help='每单固定运费，单位为分；0 表示免运费。金额由服务端计算。';
   if($key==='wechat_pay_mchid')$help='使用已关联当前小程序的自有微信支付商户号。';
   if($key==='wechat_pay_private_key')$help='上传商户 API 证书对应的 RSA 私钥；加密保存在服务端，不回填页面。';
   if($key==='wechat_pay_public_key')$help='使用微信支付公钥及其 ID，或对应平台证书及序列号；请随平台轮换更新。';
   if($key==='wechat_pay_notify_url'){global $_G;$url=rtrim($_G['siteurl'],'/').'/plugin.php?id=flipbook:api&amp;commerce_action=notify';$help='本站接口：<code>'.$e(html_entity_decode($url,ENT_QUOTES,'UTF-8')).'</code>。正式通知地址必须使用可公网访问的 HTTPS。';}
   if($key==='wechat_customer_enabled')$help='在小程序中展示原生客服入口；请同时在微信公众平台配置客服人员。';
   self::row($field['title'],$html,$help);
  }
  echo '<div class="fb-admin-form-row"><div></div><div><button type="button" class="fb-admin-button" data-commerce-save="'.$group.'">保存配置</button><p role="status" aria-live="polite" data-commerce-status></p></div></div></div>';
 }
 public static function render($group){
  echo '<div data-commerce data-commerce-group="'.$group.'" data-endpoint="'.admin_helper::e(admin_helper::url('wechat')).'" data-formhash="'.admin_helper::e(formhash()).'">';
  if($group==='orders'){
   echo '<section><div data-commerce-form="orders"><div class="fb-commerce-order-toolbar"><label class="fb-commerce-order-filter">订单状态 <select form="fb-commerce-async-controls" data-commerce-filter><option value="">全部订单</option><option value="pending">待支付</option><option value="paid">已付款</option><option value="shipped">已发货</option><option value="closed">已关闭</option></select></label><div class="fb-commerce-order-actions"><button type="button" class="fb-admin-button" data-commerce-refresh>刷新列表</button><span class="fb-commerce-order-enable">启用微信订单 '.self::toggle('wechat_orders_enabled',front_service_config::get('wechat_orders_enabled'),true).'</span></div></div><p role="status" aria-live="polite" data-commerce-status></p></div><p role="status" data-commerce-list-status></p><div class="fb-wechat-table-wrap"><table class="fb-wechat-table"><thead><tr><th>订单 / 商品</th><th>金额 / 数量</th><th>状态</th><th>创建时间</th><th>收货信息</th><th>操作</th></tr></thead><tbody data-commerce-orders></tbody></table></div><div class="fb-release-pagination"><button type="button" class="fb-admin-button" data-commerce-prev disabled>上一页</button><span data-commerce-page>第 1 页</span><button type="button" class="fb-admin-button" data-commerce-next disabled>下一页</button></div></section>';
   echo '<p class="fb-admin-form-help">订单和支付使用自有微信支付商户号。这里展示画册订单与发货记录；微信平台购物订单管理能力需在平台完成开通与联调。</p><dialog class="fb-wechat-dialog" data-commerce-ship-dialog><h3>记录发货</h3><input form="fb-commerce-async-controls" type="hidden" data-commerce-ship-number><label>物流公司<input form="fb-commerce-async-controls" data-commerce-carrier maxlength="80"></label><label>运单号<input form="fb-commerce-async-controls" data-commerce-tracking maxlength="80"></label><p role="status" data-commerce-ship-status></p><div class="fb-wechat-dialog-actions"><button type="button" class="fb-admin-button" data-commerce-ship-close>取消</button><button type="button" class="fb-admin-button" data-commerce-ship-save>保存发货记录</button></div></dialog>';
  }else self::form($group);
  echo '</div>';
 }
}
