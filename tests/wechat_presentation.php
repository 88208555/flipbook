<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/admin.php';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');require './source/class/class_core.php';C::app()->init();require_once './source/function/function_admincp.php';$lang=lang('admincp');
$root=getenv('FLIPBOOK_PLUGIN_ROOT')?:DISCUZ_ROOT.'source/plugin/flipbook';
foreach(['admin/admin_helper.php','front/front_wechat_release.php','front/front_wechat_subscription.php','front/front_service_config.php','admin/admin_wechat.php','front/front_micro.php'] as $file)require_once $root.'/'.$file;
$checks=[];function verify($ok,$text){global $checks;if(!$ok)throw new RuntimeException($text);$checks[]=$text;}
set_exception_handler(static function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
use flipbook\front_micro;use flipbook\front_service_config;use flipbook\front_wechat_subscription;
$before=flipbook\table_flipbook_setting::t()->fetch_all_setting();
$key=openssl_pkey_new(['private_key_bits'=>1024,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);openssl_pkey_export($key,$pem);
$updates=front_service_config::prepare_updates(['wechat_upload_private_key'=>$pem,'wechat_ci_url'=>'https://ci.example.test','wechat_ci_token'=>str_repeat('t',32),'wechat_ci_api_base'=>'https://album.example.test','wechat_release_version'=>'1.2.3','wechat_release_description'=>'发布说明']);
verify(strpos($updates['wechat_upload_private_key'],'enc1:')===0&&strpos($updates['wechat_ci_token'],'enc1:')===0,'upload private key and worker token are encrypted before persistence');
verify(!array_key_exists('wechat_upload_private_key',front_service_config::prepare_updates(['wechat_upload_private_key'=>''])),'blank private key preserves the existing ciphertext');
foreach([['wechat_upload_private_key'=>'bad-key'],['wechat_ci_token'=>'short'],['wechat_ci_url'=>'http://ci.example.test'],['wechat_ci_api_base'=>'http://album.example.test'],['wechat_release_description'=>str_repeat('字',201)]] as $invalid){$rejected=false;try{front_service_config::prepare_updates($invalid);}catch(InvalidArgumentException $e){$rejected=true;}verify($rejected,'invalid upload setting rejected: '.array_key_first($invalid));}
$mapping=['product'=>['templateId'=>'template_123456','enabled'=>1,'title'=>'商品内容通知','fields'=>[['key'=>'thing1','label'=>'商品名称','source'=>'product_name'],['key'=>'character_string2','label'=>'商品编码','source'=>'product_code']]]];
verify(front_wechat_subscription::validate($mapping)['product']['fields'][0]['source']==='product_name','subscription mapping uses actual product fields');
foreach([['product'=>['templateId'=>'template_123456','enabled'=>1,'fields'=>[]]],['product'=>['fields'=>[['key'=>'thing1','source'=>'order_no']]]],['product'=>['fields'=>'invalid']]] as $invalid){$rejected=false;try{front_wechat_subscription::validate($invalid);}catch(InvalidArgumentException $e){$rejected=true;}verify($rejected,'invalid or foreign subscription fields cannot be enabled');}
$types=front_micro::types();$normalized=[];$effective=[];
foreach($types as $type=>$label){$node=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>[]]]])['components'][0];$normalized[$type]=$node;$effective[$type]=front_micro::effective_regions($type,$node['props']);}
verify($effective['product_highlights']['title']['size']===$effective['product_details']['title']['size'],'highlight and detail titles use the same default size');
verify($effective['product_highlights']['title']['align']==='center'&&$effective['markdown']['heading']['align']==='center','product and Markdown headings share centered defaults');
verify($effective['markdown']['paragraph']['size']===$effective['text']['content']['size'],'Markdown description and ordinary text share the same default size');
verify(!isset($normalized['markdown']['props']['regions']['paragraph']),'rendering defaults do not rewrite sparse user region settings');
$custom=front_micro::sanitize(['components'=>[['type'=>'text','props'=>['textStyleVersion'=>1,'size'=>31,'color'=>'#123456','align'=>'right','regions'=>['content'=>['size'=>0,'gapTop'=>0,'enabled'=>0]]]]]])['components'][0];$region=front_micro::effective_regions('text',$custom['props'])['content'];
verify($region['size']===0&&$region['color']==='#123456'&&$region['align']==='right'&&$region['enabled']===0,'explicit color, alignment, zero and disabled state survive role defaults');
$legacy=front_micro::sanitize(['components'=>[['type'=>'markdown','props'=>['regions'=>['heading'=>['size'=>29]]]]]])['components'][0];verify($legacy['props']['regions']['subheading']['size']===29,'legacy Markdown heading overrides retain their subtitle coverage');
$_GET=['do'=>2,'pmod'=>'wechat'];verify(strpos(flipbook\admin_helper::url('release'),'pmod=wechat')!==false&&strpos(flipbook\admin_helper::url('release'),'wechat_tab=release')!==false,'release entry uses the already registered WeChat plugin module');
ob_start();flipbook\admin_wechat::release();$release=ob_get_clean();ob_start();flipbook\admin_wechat::subscribe();$subscribe=ob_get_clean();
foreach(['wechat_upload_private_key','wechat_ci_token','wechat_secret'] as $secret){$value=front_service_config::get($secret);verify($value===''||strpos($release.$subscribe,$value)===false,'admin HTML never refills '.$secret);}
verify(strpos($release,'106.55.0.61')!==false&&strpos($release,'一键云端发布')!==false&&strpos($release,'本地发布')!==false,'release UI includes both publishing sources and the requested whitelist IP');
verify(strpos($subscribe,'商品名称')!==false&&strpos($subscribe,'order_no')===false&&strpos($subscribe,'物流')===false,'subscription UI stays within album and product business content');
verify(flipbook\table_flipbook_setting::t()->fetch_all_setting()===$before,'validation and preview leave live settings unchanged');
$detail=front_micro::sanitize(['components'=>[['type'=>'markdown','props'=>['markdown'=>"# 商品说明\n## 副标题\n\n描述\n- 纯棉面料\n- 米白、墨黑"]]]]);$html=front_micro::render(['key'=>'about','config'=>$detail]);
verify(strpos($html,'text-align:center!important')!==false&&strpos($html,'font-size:calc(44 * var(--fb-rpx, .5px))!important')!==false,'actual H5 rendering applies the shared typography to Markdown');
$config=front_micro::sanitize(front_micro::default_config('home'));
ob_start();$section='wechat';$_GET['pmod']='wechat';include $root.'/admincp.inc.php';$admin=ob_get_clean();
verify(strpos($admin,'fb-wechat-panel-develop')!==false&&strpos($admin,'fb-wechat-panel-release')!==false,'WeChat info, development and version push each render separate panels');
echo json_encode(['checks'=>$checks,'admin'=>$admin,'release'=>$release,'subscribe'=>$subscribe,'normalized'=>$normalized,'effective'=>$effective,'presets'=>front_micro::text_presets(),'config'=>$config,'regions'=>front_micro::region_schema(),'sources'=>front_micro::region_sources(),'selectors'=>front_micro::all_region_selectors(),'types'=>$types,'keys'=>front_micro::keys(),'markdown'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
