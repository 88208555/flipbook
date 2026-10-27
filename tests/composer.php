<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
if(getenv('COMPOSER_STAGE')){require getenv('COMPOSER_STAGE').'/front/front_composer.php';require getenv('COMPOSER_STAGE').'/front/front_micro.php';}
set_exception_handler(function($e){fwrite(STDERR,$e."\n");exit(1);});
use flipbook\front_micro;use flipbook\front_composer;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);fwrite(STDERR,'PASS '.$message."\n");}
$before=flipbook\table_flipbook_micropage::t()->fetch_all_pages();$out=[];
foreach(front_micro::keys() as $key=>$name){$page=front_micro::page($key);check(front_micro::validate($page['config'])==='',$key.' editable tree validates');check(front_micro::sanitize($page['config'])==$page['config'],$key.' stable read conversion');$walk=function($nodes)use(&$walk){foreach($nodes as $node){check(!isset($node['props']['regions']),'atomic node '.$node['type'].' has no region map');$walk($node['props']['children']??[]);foreach($node['props']['tabs']??[] as $tab)$walk($tab['children']??[]);}};$walk($page['config']['components']);$out[$key]=['page'=>$page,'context'=>front_composer::context($key,1)];}
foreach(front_composer::schema()['initialStyle'] as $k=>$v)if(is_numeric($v))check($v===0,$k.' initial value is zero');
check(flipbook\table_flipbook_micropage::t()->fetch_all_pages()===$before,'read conversion never changes saved page rows');
$fixture=front_micro::sanitize(['components'=>[['id'=>'a','type'=>'container','props'=>['children'=>[['id'=>'b','type'=>'text','props'=>['text'=>'fixture','gapTop'=>-8,'padLeft'=>12,'regions'=>['content'=>['color'=>'#abcdef']]]]]]]]]);$p=$fixture['components'][0]['props']['children'][0]['props'];check($p['padLeft']==12&&$p['gapTop']==-8&&$p['color']==='#abcdef','legacy values map into the same independent component props');
check(front_micro::validate(['components'=>[['type'=>'unknown','props'=>[]]]])!=='','unknown components are rejected before saving');
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
