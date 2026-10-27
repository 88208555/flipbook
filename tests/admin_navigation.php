<?php
// Exercise the real navigation hook against both native admin shell templates.
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_HOST']='localhost:8088';
$_SERVER['REQUEST_URI']='/';
$_SERVER['REQUEST_METHOD']='GET';
require './source/class/class_core.php';
C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
define('IN_ADMINCP',true);
define('ADMINSCRIPT','admin.php');
if(!defined('PLATFORM'))define('PLATFORM','system');
require_once './source/function/function_admincp.php';
$_G['adminid']=1;
$_G['setting']['plugins']['available'][]='flipbook';
$admincp=new class {
 public $perms=[];
 public function allow(...$args){return true;}
};
function navigation_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$native='<li id="lm_portal"><a id="leftmn_portal"><span>门户</span></a><ul><li><a href="admin.php?action=article">文章</a></li></ul></li><li id="lm_tools"><a>工具</a><ul><li><a href="admin.php?action=tools">工具</a></li></ul></li>';
$buffered=new ReflectionProperty(flipbook\admin_navigation::class,'buffered');
foreach(['default','discuzx5'] as $name){
 $source=file_get_contents('./template/'.$name.'/admin/main.php');
 foreach(['without-logo','with-logo'] as $variant){
  $html=str_replace('$leftmenus',$native,$source);
  if($variant==='with-logo')$html=str_replace('</nav>','<div id="copyright"><img src="logo.svg"></div></nav>',$html);
  $buffered->setValue(null,false);
  ob_start();
  flipbook\admin_navigation::template(['param'=>['admin/main']]);
  echo $html;
  ob_end_flush();
  $rendered=ob_get_clean();
  $doc=new DOMDocument();
  @$doc->loadHTML('<?xml encoding="UTF-8">'.$rendered);
  $xpath=new DOMXPath($doc);
  $prefix=$name.' '.$variant;
  navigation_check($xpath->query('//*[@id="leftmenu"]/li[@id="lm_flipbook"]')->length===1,$prefix.' injects the album menu once inside the sidebar');
  navigation_check($xpath->query('//*[@id="lm_portal"]/following-sibling::li[1][@id="lm_flipbook"]')->length===1,$prefix.' preserves the menu order after Portal');
  navigation_check($xpath->query('//*[@id="leftmenu"]/li[@id="lm_tools"]')->length===1,$prefix.' preserves the following native menu');
  foreach(flipbook\admin_helper::pmods() as $module=>$label){
   $id='submn_flipbook_nav_flipbook_'.$module;
   $links=$xpath->query('//*[@id="menu_flipbook"]//a[@id="'.$id.'"]');
   navigation_check($links->length===1,$prefix.' restores '.$label);
   parse_str(parse_url(html_entity_decode($links->item(0)->getAttribute('href')),PHP_URL_QUERY),$query);
   navigation_check(($query['identifier']??'')==='flipbook'&&($query['pmod']??'')===($module==='release'?'wechat':$module),$prefix.' keeps the '.$module.' destination');
  }
  navigation_check($xpath->query('//*[@id="header_flipbook"]')->length===1&&str_contains($rendered,"var headers = new Array('flipbook',"),$prefix.' registers native tab navigation');
 }
}
// Removed dashboard cards must not execute their callbacks or leave placeholders.
function show_note(){throw new RuntimeException('Removed notes widget executed');}
function show_news(){throw new RuntimeException('Removed news widget executed');}
function show_forever_thanks(){throw new RuntimeException('Removed team widget executed');}
function navigation_widget_keep(){echo '<p id="retained-widget">Operational widget</p>';}
$_G['cache']['admin']['widget']=['data'=>['data'=>[
 'left'=>['show_news','show_forever_thanks','navigation_widget_keep'],
 'right'=>['show_note','navigation_widget_keep'],
], 'hide'=>['left'=>['show_news'],'right'=>['show_note']]]];
foreach(['left','right'] as $side){
 ob_start();admin\widget_view::output($side);$rendered=ob_get_clean();
 navigation_check(str_contains($rendered,'id="retained-widget"'),$side.' preserves operational dashboard widgets');
 navigation_check(!preg_match('/data-widget="(?:show_note|show_news|show_forever_thanks)"/',$rendered),$side.' omits removed cards and their hidden placeholders');
}
foreach(['default/admin/main.php','discuzx5/admin/main.php','default/admin/index.php','default/admin/blank.php'] as $file){
 $source=file_get_contents('./template/'.$file);
 navigation_check(!str_contains($source,'id="footerbars"')&&!str_contains($source,'{lang copyright}')&&!str_contains($source,'Based on MitFrame'),$file.' removes the requested footer text');
}
