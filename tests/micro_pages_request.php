<?php
// Run each native request in a separate PHP process; roll back before shutdown.
chdir('/var/www/html');$_SERVER['SCRIPT_NAME']='/admin.php';$_SERVER['PHP_SELF']='/admin.php';$_SERVER['SCRIPT_FILENAME']='/var/www/html/admin.php';
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='localhost:8088';$_SERVER['REQUEST_URI']='/admin.php';$_SERVER['REQUEST_METHOD']='POST';
require './source/class/class_core.php';C::app()->init();
define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require_once './source/function/function_admincp.php';
$_G['uid']=1;$_G['adminid']=1;
$pluginid=2;$mode=getenv('FLIPBOOK_REQUEST_MODE')?:'create';
$before=flipbook\table_flipbook_micropage::t()->fetch_all_pages();
DB::query('START TRANSACTION');
ob_start();
register_shutdown_function(function()use($mode,$before){
 $html=ob_get_clean();
 $rows=flipbook\table_flipbook_micropage::t()->fetch_all_pages();
 DB::query('ROLLBACK');flipbook\front_micro::clear_custom_keys();
 $valid=$mode==='create' ? count($rows)===count($before)+1&&str_contains($html,'路由和链接已自动配置')&&preg_match('/op=edit&key=micro_[a-f0-9]{16}/',$html) : $rows===$before;
 if(!$valid){file_put_contents('php://stderr','FAIL '.$mode.' native create request: '.strip_tags($html)."\n");exit(1);}
 if(flipbook\table_flipbook_micropage::t()->fetch_all_pages()!==$before){file_put_contents('php://stderr','FAIL request rollback'."\n");exit(1);}
 echo 'PASS '.$mode.' native create request and rollback'."\n";
});
$_POST=['name'=>'请求测试微页面','category'=>'micro','flipbookmicrocreatesubmit'=>'1','formhash'=>formhash()];
if($mode==='category')$_POST['category']='detail';
if($mode==='route')$_POST['key']='home';
if($mode==='csrf')unset($_POST['flipbookmicrocreatesubmit']);
if($mode==='permission'){$_G['uid']=0;$_G['adminid']=0;}
$_GET=$_POST+['action'=>'plugins','operation'=>'config','do'=>2,'pmod'=>'micropage','op'=>'create'];
include './source/plugin/flipbook/micropage.inc.php';
