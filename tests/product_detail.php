<?php
// Read-only migration and rendering checks for independent detail and menu eligibility.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
use flipbook\front_micro;
function detail_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$before=DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage']);
foreach(['social','business'] as $legacy){
 $config=front_micro::sanitize(['actions'=>['enabled'=>1],'components'=>[['type'=>$legacy,'props'=>['source'=>'detail','sectionsDetached'=>1,'caption'=>['enabled'=>0],'regions'=>['gallery'=>['gapLeft'=>-20,'radius'=>15],'thumbs'=>['enabled'=>0]]]]]]);
 $node=$config['components'][0];detail_check($node['type']==='product_detail'&&$node['props']['source']==='detail',$legacy.' detail migrates to its own component rather than home social');
 detail_check($node['props']['regions']['gallery']['gapLeft']===-20&&$node['props']['regions']['gallery']['radius']===15&&$node['props']['caption']['enabled']===0,$legacy.' migration preserves exact user gallery and visibility settings');
 detail_check(front_micro::sanitize($config)===$config,$legacy.' migration remains stable after saving and reloading');
 detail_check($config['actions']['enabled']===1&&front_micro::action_data($config)['enabled']===0,$legacy.' detail keeps stored menu configuration while disabling its runtime menu');
}
detail_check(front_micro::sanitize(front_micro::default_config('detail'))['components'][0]['type']==='product_detail','new detail pages start with the independent product detail component');
foreach(array_keys(front_micro::types()) as $type){$config=front_micro::sanitize(['actions'=>['enabled'=>1],'components'=>[['type'=>$type,'props'=>[]]]]);detail_check((bool)front_micro::action_data($config)['enabled']===($type==='social'),$type.' receives only its intended floating-menu eligibility');}
$nested=front_micro::sanitize(['components'=>[['type'=>'tab','props'=>['tabs'=>[['title'=>'One','children'=>[['type'=>'social','props'=>[]]]]]]]]]);
detail_check(front_micro::action_data($nested)['enabled']===1,'a nested home social component retains its menu');
$_GET['productid']=front_micro::preview_productid();$config=front_micro::sanitize(front_micro::default_config('detail'));$html=front_micro::render(['key'=>'detail','config'=>$config]);
detail_check(strpos($html,'data-fb-block="product_detail"')!==false&&strpos($html,'data-fb-block="social"')===false,'formal detail HTML identifies the independent component');
detail_check(strpos($html,'fb-web-reader-track')!==false&&strpos($html,'fb-stage-rail')===false,'formal detail renders its product carousel without the home floating menu');
detail_check(DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage'])===$before,'migration and rendering leave the stored page configuration and versions unchanged');
