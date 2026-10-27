<?php
// Render the real list/editor controllers without saving configuration.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
if(!defined('IN_ADMINCP'))define('IN_ADMINCP',true);if(!defined('ADMINSCRIPT'))define('ADMINSCRIPT','admin.php');require_once './source/function/function_admincp.php';$lang=lang('admincp');
function editor_shell_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function editor_shell_render($query){global $_G,$lang;$_GET=['do'=>2,'pmod'=>'micropage']+$query;ob_start();include './source/plugin/flipbook/micropage.inc.php';return ob_get_clean();}
function editor_shell_dom($html){$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($doc);}
$before=DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage']);
$list=editor_shell_dom(editor_shell_render([]));$keys=flipbook\front_micro::keys();
editor_shell_check($list->query('//*[@id="fbmp"]')->length===0,'the page list contains no embedded visual editor');
foreach($keys as $key=>$label){
 $links=$list->query('//a[@data-fb-admin-open="flipbook_micro_editor_'.$key.'" and @data-fb-admin-title="可视化编辑 · '.$label.'"]');
 editor_shell_check($links->length>=1,$key.' list entry has its own stable tab identity and page title');
 foreach($links as $link){parse_str(parse_url(html_entity_decode($link->getAttribute('href')),PHP_URL_QUERY),$query);editor_shell_check(($query['op']??'')==='edit'&&($query['key']??'')===$key&&$link->getAttribute('target')==='_blank',$key.' entry opens the matching independent edit route');}
 $html=editor_shell_render(['op'=>'edit','key'=>$key]);$editor=editor_shell_dom($html);
 editor_shell_check($editor->query('//*[@id="fbmp"]')->length===1&&$editor->query('//*[contains(concat(" ",normalize-space(@class)," ")," fbmp-pages ")]')->length===0,$key.' editor has one canvas and no mixed page navigation');
 editor_shell_check($editor->query('//*[contains(@class,"fbmp-editor-identity")]/strong[.="'.$label.'"]')->length===1&&strpos($html,'data-fb-editor')!==false,$key.' independent editor identifies the page and activates standalone layout');
 editor_shell_check($editor->query('//*[@id="fbmp-form"]//input[@name="key" and @value="'.$key.'"]')->length===1&&$editor->query('//*[@id="fbmp-save"]')->length===1,$key.' retains its own save target and synchronization action');
}
editor_shell_check(DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage'])===$before,'opening every editor leaves all stored page configuration and versions unchanged');
