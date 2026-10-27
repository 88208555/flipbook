<?php
// Render the real administration form without starting a browser. Fixture settings are rolled back.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/admin.php';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require './source/class/class_core.php';C::app()->init();require_once './source/function/function_admincp.php';$lang=lang('admincp');
$pluginRoot=getenv('FLIPBOOK_PLUGIN_ROOT')?:DISCUZ_ROOT.'source/plugin/flipbook';foreach(['admin/admin_helper.php','front/front_wechat_release.php','front/front_wechat_subscription.php','front/front_service_config.php','front/front_wechat_pay.php','front/front_order_store.php','front/front_order_service.php','front/front_commerce.php','admin/admin_commerce.php','admin/admin_wechat.php'] as $file)require_once $pluginRoot.'/'.$file;
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function wechat_must($value,$message){if(!$value)throw new RuntimeException($message);echo 'PASS '.$message.PHP_EOL;}
function wechat_snapshot(){return hash('sha256',json_encode(DB::fetch_all('SELECT * FROM %t ORDER BY skey',['flipbook_setting'])));}
function wechat_render($tab){global $_G,$lang,$pluginRoot;$_GET=['pmod'=>'wechat','do'=>2,'wechat_tab'=>$tab];$_POST=[];ob_start();include $pluginRoot.'/admincp.inc.php';return ob_get_clean();}
function wechat_dom($html){$d=new DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($d);}
$original=wechat_snapshot();$oldGet=$_GET;$oldPost=$_POST;$fixtureSecrets=['wechat_secret'=>'fixture-app-secret','wechat_push_token'=>'fixtureToken123','wechat_push_aes'=>str_repeat('K',43),'cos_secret_id'=>'fixture-cos-id','cos_secret_key'=>'fixture-cos-key'];
DB::query('START TRANSACTION');
try{
 foreach($fixtureSecrets as $key=>$value)flipbook\table_flipbook_setting::t()->update_setting($key,flipbook\front_service_config::encrypt($value),'audit');
 foreach(['wechat_enabled'=>'0','wechat_appid'=>'wx1234567890abcdef','wechat_name'=>'审计小程序','wechat_original_id'=>'gh_fixture_123','wechat_qrcode'=>'data/attachment/flipbook/micropages/qrcode/fixture.png','wechat_push_enabled'=>'1','wechat_push_mode'=>'encrypted','wechat_subscribe_templates'=>'商品动态 | fixture_template_123','storage_provider'=>'local'] as $key=>$value)flipbook\table_flipbook_setting::t()->update_setting($key,$value,'audit');
 $html=wechat_render('push');$x=wechat_dom($html);
 wechat_must($x->query('//form')->length===1,'one form owns all WeChat configuration fields');
 wechat_must($x->query('//*[@data-fb-wechat-tab]')->length===9 && $x->query('//*[@data-fb-wechat-panel]')->length===9,'nine real configuration tab panels');
 wechat_must($x->query('//*[@data-fb-wechat-panel and not(@hidden)]')->item(0)->getAttribute('data-fb-wechat-panel')==='push','selected tab restores from validated query');
 foreach(flipbook\front_service_config::schema() as $key=>$field){if(strpos($key,'wechat_')!==0||$field['type']==='readonly'||!empty($field['managed'])||$key==='wechat_subscribe_templates')continue;wechat_must($x->query('//*[@name="'.$key.'" and not(@disabled)]')->length>0,$key.' remains enabled inside the shared form');}
 wechat_must($x->query('//input[@name="flipbooksubmit" and contains(@class,"fb-admin-button")]')->length===1,'shared save button is outside the switchable panels');
 $push='//*[@data-fb-wechat-panel="push"]';$login='//*[@data-fb-wechat-panel="login"]';$server='//*[@data-fb-wechat-panel="server"]';
 wechat_must($x->query($push.'//input[@id="fb-wechat-push-url" and @readonly and @type="text"]')->length===1,'callback URL is a read-only copyable input');
 $positions=array_map(function($id)use($html){return strpos($html,'id="'.$id.'"');},['fb-wechat-push-url','fb-wechat_push_token','fb-wechat_push_aes']);
 wechat_must($positions[0]<$positions[1] && $positions[1]<$positions[2] && $positions[2]<strpos($html,'name="wechat_push_mode"'),'push field order is URL, Token, EncodingAESKey, encryption mode');
 wechat_must($x->query($push.'//input[@name="wechat_push_token" and @type="text" and @value="" and @minlength="3" and @maxlength="32" and @pattern="[A-Za-z0-9]{3,32}"]')->length===1,'Token accepts only a new 3–32 character alphanumeric value and never prefills a secret');
 wechat_must($x->query($push.'//input[@name="wechat_push_aes" and @type="password" and @value="" and @maxlength="43"]')->length===1,'EncodingAESKey stays a blank password field limited to 43 characters');
 wechat_must($x->query('//*[@data-fb-count-for="fb-wechat_push_token" and normalize-space(.)="0/32"]')->length===1 && $x->query('//*[@data-fb-count-for="fb-wechat_push_aes" and normalize-space(.)="0/43"]')->length===1,'secret counters start at zero without exposing saved lengths');
 wechat_must($x->query($push.'//input[@name="wechat_push_mode" and @type="radio"]')->length===3 && $x->query($push.'//input[@name="wechat_push_mode" and @value="encrypted" and @checked]')->length===1,'three encryption modes share one radio group with the configured mode selected');
 wechat_must($x->query($push.'//*[contains(@class,"fb-admin-form-label")]/span[@aria-label="必填"]')->length===2,'Token and AES labels retain required markers with explanatory conditions');
 wechat_must($x->query($push.'//div[contains(@class,"fb-admin-input-line")]//input[@type="checkbox"]')->length===0 && $x->query($push.'//div[contains(@class,"fb-admin-form-help")]//input[@type="checkbox"]')->length===2,'secret clearing controls occupy the help rows rather than the primary input rows');
 wechat_must($x->query($login.'//input[@name="wechat_enabled"]')->length===2 && $x->query('//*[@data-fb-wechat-panel="develop"]//input[@name="wechat_appid"]')->length===1 && $x->query($login.'//input[@name="wechat_appid"]')->length===0,'information owns login enable and development owns credentials');
 wechat_must($x->query($login.'//*[@data-fb-wechat-qrcode-field]//input[@name="wechat_qrcode" and @type="hidden"]')->length===1 && $x->query($login.'//img[@data-fb-wechat-qrcode-preview and @src]')->length===1,'saved QR image uses the managed-upload hidden field and a real preview');
 wechat_must($x->query($server.'//input[@readonly]')->length===4 && $x->query($server.'//button[@data-fb-copy]')->length===4,'server configuration has four read-only domain fields with copy actions');
 wechat_must($x->query($server.'//input[@id="fb-wechat-domain-socket" and @value=""]')->length===1 && $x->query($server.'//button[@data-fb-copy="fb-wechat-domain-socket" and @disabled]')->length===1,'unused WebSocket service has no fabricated URL or enabled copy action');
 wechat_must($x->query('//button[not(contains(concat(" ",normalize-space(@class)," ")," fb-admin-button "))]')->length===0 && $x->query('//*[@role="tab" and not(contains(@class,"fb-admin-tab"))]')->length===0,'all actions and tabs use the shared administration control classes');
 foreach($fixtureSecrets as $secret)wechat_must(strpos($html,$secret)===false,'rendering never leaks a stored fixture secret');
 foreach(['login','develop','subscribe','orders','trade','customer','server','release'] as $tab){$dom=wechat_dom(wechat_render($tab));wechat_must($dom->query('//*[@data-fb-wechat-panel and not(@hidden)]')->item(0)->getAttribute('data-fb-wechat-panel')===$tab,'direct '.$tab.' selection preserves all other tab fields');}
 $payload=['wechat_enabled'=>'1','wechat_appid'=>'wx1234567890abcdef','wechat_name'=>'保存后小程序','wechat_original_id'=>'gh_fixture_456','wechat_qrcode'=>'data/attachment/flipbook/micropages/qrcode/fixture.png','wechat_secret'=>'','wechat_push_enabled'=>'1','wechat_push_token'=>'','wechat_push_aes'=>'','wechat_push_mode'=>'compatible','wechat_subscribe_templates'=>'商品上新 | fixture_template_456'];
 $updates=flipbook\front_service_config::prepare_updates($payload);
 wechat_must(!isset($updates['wechat_secret'],$updates['wechat_push_token'],$updates['wechat_push_aes']),'blank secrets are omitted from updates and preserve existing encrypted values');
 foreach($updates as $key=>$value)flipbook\table_flipbook_setting::t()->update_setting($key,$value,'audit');
 wechat_must(flipbook\front_service_config::get('wechat_name')==='保存后小程序' && flipbook\front_service_config::get('wechat_push_mode')==='compatible' && flipbook\front_service_config::get('wechat_subscribe_templates')==='商品上新 | fixture_template_456','saving the shared form keeps updates from all three editable tabs');
 foreach($fixtureSecrets as $key=>$secret)wechat_must(flipbook\front_service_config::get($key)===$secret,'shared WeChat save preserves an unchanged encrypted credential');
 $newToken='replacementFixtureToken';$newUpdates=flipbook\front_service_config::prepare_updates(['wechat_push_token'=>$newToken]);
 wechat_must(strpos($newUpdates['wechat_push_token'],'enc1:')===0 && strpos($newUpdates['wechat_push_token'],$newToken)===false,'new plaintext Token is encrypted before persistence');
 foreach([['wechat_push_token'=>'ab'],['wechat_push_token'=>str_repeat('T',33)],['wechat_push_token'=>'bad-token'],['wechat_push_aes'=>str_repeat('A',42)],['wechat_push_mode'=>'unknown'],['wechat_original_id'=>str_repeat('a',81)],['wechat_qrcode'=>'javascript:alert(1)']] as $invalid){$before=wechat_snapshot();try{flipbook\front_service_config::prepare_updates($invalid);throw new RuntimeException('invalid configuration accepted');}catch(InvalidArgumentException $expected){}wechat_must(wechat_snapshot()===$before,'invalid setting is rejected before any existing setting changes');}
 $before=wechat_snapshot();try{flipbook\front_service_config::prepare_updates(['wechat_push_token'=>''],['wechat_push_token']);throw new RuntimeException('enabled push cleared required token');}catch(InvalidArgumentException $expected){}
 wechat_must(wechat_snapshot()===$before,'clearing a required key while push is enabled leaves all saved settings unchanged');
 $cleared=flipbook\front_service_config::prepare_updates(['wechat_push_enabled'=>'0','wechat_push_token'=>''],['wechat_push_token']);
 wechat_must(array_key_exists('wechat_push_token',$cleared) && $cleared['wechat_push_token']==='','explicit clearing is retained once push is disabled');
 $site=$_G['siteurl'];$_G['siteurl']='http://127.0.0.1:8088/forum/';$domains=flipbook\front_service_config::server_domains();
 wechat_must($domains['request']==='http://127.0.0.1:8088' && $domains['upload']==='http://127.0.0.1:8088' && $domains['socket']==='' && $domains['download']==='http://127.0.0.1:8088','local deployment shows its real HTTP origin without inventing HTTPS or WebSocket');
 foreach(['storage_provider'=>'cos','cos_domain'=>'https://cdn.fixture.example','cos_bucket'=>'fixture-1250000000','cos_region'=>'ap-shanghai'] as $key=>$value)flipbook\table_flipbook_setting::t()->update_setting($key,$value,'audit');
 $domains=flipbook\front_service_config::server_domains();wechat_must($domains['download']==='http://127.0.0.1:8088; https://cdn.fixture.example','enabled COS contributes its configured public download origin');
 flipbook\table_flipbook_setting::t()->update_setting('cos_domain','','audit');$domains=flipbook\front_service_config::server_domains();wechat_must(strpos($domains['download'],'https://fixture-1250000000.cos.ap-shanghai.myqcloud.com')!==false,'COS without a custom domain displays its actual regional bucket domain');
 $_G['siteurl']=$site;
}finally{DB::query('ROLLBACK');$_GET=$oldGet;$_POST=$oldPost;}
wechat_must(wechat_snapshot()===$original,'all rendering and persistence fixtures roll back without changing real settings');
