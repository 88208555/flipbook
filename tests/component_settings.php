<?php
// Round-trip every component and its registered regions through the actual page writer, then roll back.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
use flipbook\front_micro;
function settings_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$table=flipbook\table_flipbook_micropage::t();$before=$table->fetch_all_pages();$schema=front_micro::region_schema();$regions=0;$fields=0;
$common=['boxWidth'=>240,'boxHeight'=>180,'borderWidth'=>2,'borderColor'=>'#abcdef','borderStyle'=>'dashed','gapLeft'=>17,'gapRight'=>23,'gapTop'=>-7,'gapBottom'=>9,'padLeft'=>5,'padRight'=>11,'padTop'=>3,'padBottom'=>4,'radius'=>6,'bg'=>'#123456','link'=>'page:catalog'];
$inner=['gapLeft'=>-13,'gapRight'=>19,'gapTop'=>-7,'gapBottom'=>9,'padLeft'=>5,'padRight'=>11,'padTop'=>3,'padBottom'=>4,'width'=>24,'height'=>36,'enabled'=>0,'radius'=>6,'borderWidth'=>2,'borderColor'=>'#abcdef','borderStyle'=>'dashed','size'=>18,'weight'=>600,'color'=>'#fedcba','family'=>'serif','lineHeight'=>1.7,'letterSpacing'=>1.5,'align'=>'right','bg'=>'#234567'];
DB::query('START TRANSACTION');try{
 foreach(front_micro::types() as $type=>$label){
  $props=$common;foreach($schema[$type] as $key=>$unused){$props['regions'][$key]=$inner;$regions++;$fields+=count($inner);}
  $props['sectionsDetached']=1;
  $config=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>$props]]]);
  $key='audit_settings_'.bin2hex(random_bytes(5));$version=$table->save_page($key,$label,json_encode($config),0);settings_check($version===1,$type.' writes one atomic isolated configuration version');
  $fresh=front_micro::sanitize(json_decode($table->fetch_by_key($key)['config'],true));$node=$fresh['components'][0];
  settings_check($node['type']===$type,$type.' keeps its exact component type after reload');
  foreach($common as $field=>$value)settings_check($node['props'][$field]===$value,$type.' reload preserves '.$field.' including its independent value');
  settings_check($node['props']['regions']==$props['regions'],$type.' reload preserves every registered internal area and all its settings');
  $html=front_micro::render(['key'=>'detail','config'=>$fresh]);$dom=new DOMDocument();$old=libxml_use_internal_errors(true);$dom->loadHTML('<meta charset="utf-8">'.$html);libxml_clear_errors();libxml_use_internal_errors($old);$xp=new DOMXPath($dom);$root=$xp->query('//section[@data-fb-block="'.$type.'"]')->item(0);settings_check((bool)$root,$type.' renders the actual H5 component');
  if($type==='view_title')settings_check(strpos($root->getAttribute('style'),'inset:0;width:auto;')===false,'view title does not override its explicit container width');
  foreach(['margin-left:calc(34 * var(--fb-rpx, .5px))','margin-right:calc(46 * var(--fb-rpx, .5px))','margin-top:calc(-14 * var(--fb-rpx, .5px))','margin-bottom:calc(18 * var(--fb-rpx, .5px))','padding:calc(6 * var(--fb-rpx, .5px)) calc(22 * var(--fb-rpx, .5px)) calc(8 * var(--fb-rpx, .5px)) calc(10 * var(--fb-rpx, .5px))','width:auto','width:calc(480 * var(--fb-rpx, .5px))','height:calc(360 * var(--fb-rpx, .5px))','border-width:calc(4 * var(--fb-rpx, .5px))','border-color:#abcdef','border-style:dashed'] as $part)settings_check(strpos($root->getAttribute('style'),$part)!==false,$type.' H5 applies '.$part);
 }
}finally{DB::query('ROLLBACK');}
settings_check($table->fetch_all_pages()===$before,'all live micro pages and versions remain unchanged after rollback');
echo 'COVERAGE '.json_encode(['components'=>count(front_micro::types()),'regions'=>$regions,'internalSettingValues'=>$fields],JSON_UNESCAPED_UNICODE)."\n";
