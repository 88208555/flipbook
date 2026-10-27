<?php
chdir('/var/www/html');$_SERVER['SCRIPT_NAME']='/admin.php';$_SERVER['PHP_SELF']='/admin.php';$_SERVER['SCRIPT_FILENAME']='/var/www/html/admin.php';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='localhost:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';
require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');require_once './source/function/function_admincp.php';$lang=lang('admincp');
use flipbook\admin_micropage;use flipbook\front_micro;
function manage_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function manage_reject($method,$input){try{admin_micropage::$method($input);}catch(RuntimeException $e){return true;}return false;}
function manage_list(){global $_G,$lang;ob_start();include './source/plugin/flipbook/micropage.inc.php';return ob_get_clean();}
$table=flipbook\table_flipbook_micropage::t();$before=$table->fetch_all_pages();DB::query('START TRANSACTION');
try{
 $custom=admin_micropage::create(['name'=>'旧页面名称']);$key=$custom['key'];$routes=front_micro::routes($key);$stored=$table->fetch_by_key($key);
 foreach(array_keys(front_micro::builtin_keys()) as $builtin){
  manage_check(manage_reject('rename',['key'=>$builtin,'name'=>'Blocked','version'=>1]),$builtin.' cannot be renamed');
  manage_check(manage_reject('remove',['key'=>$builtin,'version'=>1]),$builtin.' cannot be deleted');
 }
 $renamed=admin_micropage::rename(['key'=>$key,'name'=>'品牌故事 & 尺码','version'=>1]);
 manage_check($renamed['name']==='品牌故事 & 尺码'&&$renamed['version']===2,'custom rename updates the name and version');
 manage_check($table->fetch_by_key($key)['config']===$stored['config']&&front_micro::routes($key)===$routes,'rename preserves content and every generated address');
 manage_check(front_micro::keys()[$key]==='品牌故事 & 尺码','the editor page-link registry immediately uses the renamed label');
 manage_check(manage_reject('rename',['key'=>$key,'name'=>'Lost update','version'=>1])&&manage_reject('remove',['key'=>$key,'version'=>1]),'stale rename and delete requests are rejected');
 manage_check($table->save_page($key,'Old name','{}',1)===0,'an old open editor cannot restore the previous name');
 $_G['uid']=1;$_G['adminid']=1;$_GET=['pmod'=>'micropage','do'=>2];$list=manage_list();
 $doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$list);$xpath=new DOMXPath($doc);
 manage_check($xpath->query('//button[@data-fb-micro-rename and @data-key="'.$key.'"]')->length===1&&$xpath->query('//button[@data-fb-micro-delete and @data-key="'.$key.'"]')->length===1,'only the custom page gets rename and delete actions');
 foreach(array_keys(front_micro::builtin_keys()) as $builtin)manage_check($xpath->query('//button[(@data-fb-micro-delete or @data-fb-micro-rename) and @data-key="'.$builtin.'"]')->length===0,$builtin.' keeps edit-only controls');
 manage_check(str_contains($list,'前台地址')&&str_contains($list,'小程序地址')&&str_contains($list,'http://localhost:8088/plugin.php?id=flipbook:micro&amp;key='.$key)&&str_contains($list,$routes['mini']),'both addresses are explicitly displayed in separate columns');
 $pathLink=$xpath->query('//div[@class="fb-micro-address"]/a[contains(@href,"key='.$key.'")]')->item(0);
 manage_check($pathLink && $pathLink->textContent===$routes['h5'] && $pathLink->getAttribute('title')===$routes['h5'],'front address displays only its relative path with full path tooltip');
 manage_check($pathLink->getAttribute('href')==='http://localhost:8088/'.$routes['h5'] && $xpath->query('//button[@data-fb-micro-copy="http://localhost:8088/'.$routes['h5'].'"]')->length===1,'opening and copying retain the complete absolute URL');
 if(getenv('FLIPBOOK_MANAGE_FIXTURE'))echo 'FIXTURE '.json_encode(['list'=>$list,'key'=>$key,'routes'=>$routes],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 admin_micropage::remove(['key'=>$key,'version'=>2]);
 manage_check(!$table->fetch_by_key($key)&&!isset(front_micro::keys()[$key])&&front_micro::link_url('page:'.$key)===''&&front_micro::routes($key)===[],'deletion removes the page and unregisters all its routes');
 manage_check(manage_reject('rename',['key'=>$key,'name'=>'Deleted','version'=>2]),'deleted pages cannot be renamed back into existence');
 foreach($before as $row)manage_check($table->fetch_by_key($row['pkey'])===$row,'existing '.$row['pkey'].' is unchanged');
}finally{DB::query('ROLLBACK');front_micro::clear_custom_keys();}
manage_check($table->fetch_all_pages()===$before,'all management fixtures were rolled back');
