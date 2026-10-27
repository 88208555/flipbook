<?php
// Isolated configuration and rendered controls. The DB facade below has no write operation.
define('IN_DISCUZ',true);define('IN_ADMINCP',true);
$_G=['siteurl'=>'https://album.example.test/','config'=>['security'=>['authkey'=>'isolated-commerce-config-key']]];
class DB {
 public static function table($name){return 'fixture_'.$name;}
 public static function fetch_all($sql){return getenv('FB_COMMERCE_INSTALLED')==='1'?[['table'=>'fixture_flipbook_order']]:[];}
}
class CommerceSettingsFixture {
 public static $rows=[];
 public static function t(){return new self();}
 public function fetch_all_setting(){return self::$rows;}
}
class CommerceAdminFixture {
 public static function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
 public static function url($module){return '/admin.php?identifier=flipbook&pmod='.$module;}
}
class_alias(CommerceSettingsFixture::class,'flipbook\\table_flipbook_setting');
class_alias(CommerceAdminFixture::class,'flipbook\\admin_helper');
function formhash(){return 'isolated_formhash';}
$root=getenv('FLIPBOOK_PLUGIN_ROOT')?:'/var/www/html/source/plugin/flipbook';
foreach(['front_service_config','front_wechat_pay','front_order_store','front_order_service','front_commerce','front_wechat_release','front_wechat_subscription'] as $name)require $root.'/front/'.$name.'.php';
require $root.'/admin/admin_commerce.php';
use flipbook\front_service_config as Config;use flipbook\front_commerce as Commerce;
$checks=[];
function verify($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks[]=$label;}
function invalid($values,$label,$clear=[]){$rejected=false;try{Config::prepare_updates($values,$clear);}catch(InvalidArgumentException $e){$rejected=true;}verify($rejected,$label);}
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
$schema=Config::schema();foreach($schema as $key=>$field)CommerceSettingsFixture::$rows[$key]=$field['default'];
$key=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);openssl_pkey_export($key,$merchant);
$other=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);$platform=openssl_pkey_get_details($other)['key'];
$values=['wechat_appid'=>'wx0000000000000000','wechat_secret'=>'isolated-app-secret','wechat_pay_mchid'=>'1234567890','wechat_pay_serial'=>str_repeat('A',40),'wechat_pay_private_key'=>$merchant,'wechat_pay_api_key'=>str_repeat('x',32),'wechat_pay_public_id'=>'PUB_KEY_ID_0123456789ABCDEF','wechat_pay_public_key'=>$platform,'wechat_pay_notify_url'=>'https://album.example.test/plugin.php?id=flipbook:api&commerce_action=notify'];
foreach($values as $key=>$value)CommerceSettingsFixture::$rows[$key]=$schema[$key]['type']==='secret'?Config::encrypt($value):$value;
$before=CommerceSettingsFixture::$rows;$updates=Config::prepare_updates(['wechat_customer_enabled'=>'1','wechat_customer_title'=>'画册客服','wechat_pay_api_key'=>'']);
verify(!isset($updates['wechat_pay_api_key'])&&Config::get('wechat_pay_api_key')===str_repeat('x',32),'blank payment secrets preserve their existing encrypted values');
verify($updates['wechat_customer_title']==='画册客服'&&CommerceSettingsFixture::$rows===$before,'configuration preparation validates every field before persistence');
invalid(['wechat_shipping_fen'=>'1.25'],'fractional shipping cents are rejected');
invalid(['wechat_shipping_fen'=>'100001'],'shipping beyond the configured limit is rejected');
invalid(['wechat_trade_buy_text'=>str_repeat('买',11)],'purchase button label enforces its character limit');
invalid(['wechat_pay_private_key'=>'invalid-pem'],'invalid merchant private key is rejected');
invalid(['wechat_pay_notify_url'=>'http://album.example.test/plugin.php?id=flipbook:api&commerce_action=notify'],'payment callback must use the HTTPS notification endpoint');
invalid(['wechat_pay_public_id'=>str_repeat('A',40)],'merchant and platform certificate identities cannot be confused');
invalid(['wechat_trade_enabled'=>'1','wechat_orders_enabled'=>'0'],'payment cannot be enabled without the order service');
$installed=getenv('FB_COMMERCE_INSTALLED')==='1';
if($installed){
 $enabled=Config::prepare_updates(['wechat_orders_enabled'=>'1','wechat_trade_enabled'=>'1']);foreach($enabled as $key=>$value)CommerceSettingsFixture::$rows[$key]=$value;
 verify(Commerce::state()['enabled'],'complete merchant settings enable trading only when the order table is installed');
 invalid(['wechat_pay_private_key'=>''], 'an enabled merchant cannot silently clear its active signing key',['wechat_pay_private_key']);
}else{
 invalid(['wechat_orders_enabled'=>'1','wechat_trade_enabled'=>'1'],'missing order installation blocks trading without creating a table');
 verify(!Commerce::state()['enabled'],'an uninstalled service remains unavailable');
}
$state=Commerce::state();foreach(['pay_api_key','pay_private_key','pay_public_key','mchid','secret'] as $private)verify(strpos(json_encode($state),$private)===false,'public capability state omits '.$private);
$html=[];foreach(['orders','trade','customer'] as $group){ob_start();flipbook\admin_commerce::render($group);$html[$group]=ob_get_clean();$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html[$group]);$x=new DOMXPath($dom);
 verify($x->query('//*[@data-commerce-form="'.$group.'"]')->length===1,$group.' owns one independent configuration form');
 foreach($values as $key=>$value)if($schema[$key]['type']==='secret')verify(strpos($html[$group],$value)===false,$group.' never renders a stored '.$key);
 verify($x->query('//textarea[@data-commerce-setting and normalize-space(.)!=""]')->length===0,$group.' key textareas remain blank');
}
$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html['orders']);$x=new DOMXPath($dom);
verify($x->query('//*[@data-commerce-tab]')->length===0&&$x->query('//*[@data-commerce-setting="wechat_orders_enabled" and @role="switch" and @data-commerce-auto-save]')->length===1,'order list owns one inline enable switch without redundant nested tabs');
if(getenv('FB_COMMERCE_HTML')==='1')echo json_encode(['html'=>$html,'checks'=>$checks,'installed'=>$installed],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
else foreach($checks as $label)echo 'PASS '.$label."\n";
