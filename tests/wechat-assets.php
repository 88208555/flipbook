<?php
// Managed-file lifecycle tests use unique local files and rollback every setting/asset/SKU fixture.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/admin.php';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require './source/class/class_core.php';C::app()->init();require_once './source/function/function_admincp.php';$lang=lang('admincp');
function qr_must($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function qr_snapshot(){$rows=[];foreach(['flipbook_setting','flipbook_asset','flipbook_sku'] as $table){$data=array_map('json_encode',DB::fetch_all('SELECT * FROM %t',[$table]));sort($data);$rows[$table]=$data;}return hash('sha256',json_encode($rows));}
function qr_save_fixture($url){$settings=flipbook\table_flipbook_setting::t();$old=$settings->fetch_setting('wechat_qrcode','');foreach(flipbook\front_service_config::prepare_updates(['wechat_qrcode'=>$url]) as $key=>$value)$settings->update_setting($key,$value,'audit');flipbook\front_asset::cleanup_replaced(['wechat_qrcode'=>$old],['wechat_qrcode'=>$settings->fetch_setting('wechat_qrcode','')]);}
$original=qr_snapshot();$files=[];$fixtureDir='data/attachment/flipbook/wechat/qrcode/audit-'.bin2hex(random_bytes(6));$failed=false;$routeExpected=false;$routeBuffer=0;$assets=[];
DB::query('START TRANSACTION');
register_shutdown_function(function()use(&$failed,&$routeExpected,&$routeBuffer,&$files,&$assets,$fixtureDir,$original){
 try{
  if($routeExpected && !$failed){
   $html=ob_get_level()>$routeBuffer?ob_get_clean():'';
   qr_must(strpos($html,'设置已保存')!==false,'actual admincp POST completes the native settings-save path');
   qr_must(flipbook\front_service_config::get('wechat_qrcode')===$files['routeNew'],'actual settings POST persists the uploaded QR URL');
   qr_must(!is_file(DISCUZ_ROOT.$files['routeOld']) && DB::result_first('SELECT status FROM %t WHERE assetid=%d',['flipbook_asset',$assets['routeOld']])==='deleted','actual successful save cleans up the replaced managed file');
   qr_must(is_file(DISCUZ_ROOT.$files['routeNew']) && flipbook\front_asset::referenced($files['routeNew']),'newly saved QR file remains protected by its setting reference');
   qr_must(flipbook\front_service_config::get('wechat_secret')==='fixture-app-secret' && strpos($html,'fixture-app-secret')===false,'the QR settings save preserves and never renders the existing encrypted AppSecret');
   qr_save_fixture('');qr_must(flipbook\front_service_config::get('wechat_qrcode')==='' && !is_file(DISCUZ_ROOT.$files['routeNew']),'saving an explicit QR clear releases the last managed-file reference');
  }
 }catch(Throwable $error){$failed=true;file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");}
 finally{DB::query('ROLLBACK');foreach($files as $url)if(is_file(DISCUZ_ROOT.$url))unlink(DISCUZ_ROOT.$url);if(is_dir(DISCUZ_ROOT.$fixtureDir))rmdir(DISCUZ_ROOT.$fixtureDir);}
 try{qr_must(qr_snapshot()===$original,'all QR files and database fixtures are cleaned up without changing real configuration');}catch(Throwable $error){$failed=true;file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");}
 if($failed)exit(1);
});
set_exception_handler(function($error)use(&$failed){$failed=true;file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
$context=flipbook\front_media_storage::storage_context(['scope'=>'wechat','purpose'=>'qrcode','productid'=>123],'image');
qr_must($context===['scope'=>'wechat','productid'=>0,'purpose'=>'qrcode','folder'=>'wechat/qrcode'],'WeChat QR uploads use their own standard folder and do not attach to a product');
qr_must(flipbook\front_media_storage::storage_context(['scope'=>'product','purpose'=>'sku','productid'=>123],'image')['folder']==='products/123/sku','product upload directory remains unchanged');
qr_must(flipbook\front_media_storage::storage_context(['scope'=>'micropage','purpose'=>'image'],'image')['folder']==='micropages/image','micro-page upload directory remains unchanged');
try{flipbook\front_media_storage::storage_context(['scope'=>'wechat','purpose'=>'qrcode'],'video');throw new RuntimeException('QR accepted video');}catch(RuntimeException $error){qr_must($error->getMessage()==='微信配置仅支持上传图片','WeChat configuration rejects non-image uploads before storing a file');}
$setting=flipbook\table_flipbook_setting::t();foreach(flipbook\front_service_config::schema() as $key=>$field){if($field['type']==='readonly')continue;$value=$field['default'];if($field['type']==='secret' && $key==='wechat_secret')$value=flipbook\front_service_config::encrypt('fixture-app-secret');$setting->update_setting($key,$value,'audit');}
mkdir(DISCUZ_ROOT.$fixtureDir,0755,true);$image=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0f0AAAAASUVORK5CYII=');
foreach(['shared','next','routeOld','routeNew'] as $name){$url=$fixtureDir.'/'.$name.'.png';file_put_contents(DISCUZ_ROOT.$url,$image);$files[$name]=$url;$assets[$name]=flipbook\front_asset::register(['scope'=>'wechat','purpose'=>'qrcode','kind'=>'image','provider'=>'local','objectkey'=>$url,'bucket'=>'','region'=>'','url'=>$url,'size'=>strlen($image),'created'=>TIMESTAMP-90000]);}
$setting->update_setting('wechat_qrcode',$files['shared'],'audit');
qr_must(flipbook\front_asset::referenced($files['shared']) && !flipbook\front_asset::remove($assets['shared']) && is_file(DISCUZ_ROOT.$files['shared']),'a saved QR setting protects even an aged asset from cleanup');
$sku=DB::insert('flipbook_sku',['productid'=>0,'code'=>'AUDIT-QR-'.bin2hex(random_bytes(4)),'name'=>'QR shared-reference fixture','images'=>json_encode([$files['shared']])],true);
qr_save_fixture($files['next']);
qr_must(flipbook\front_service_config::get('wechat_qrcode')===$files['next'] && is_file(DISCUZ_ROOT.$files['shared']),'replacing the QR retains an old file that is still referenced by an SKU');
DB::delete('flipbook_sku',['skuid'=>$sku]);flipbook\front_asset::cleanup_replaced(['wechat_qrcode'=>$files['shared']],['wechat_qrcode'=>$files['next']]);
qr_must(!is_file(DISCUZ_ROOT.$files['shared']),'old QR file is deleted after its final shared reference disappears');
$setting->update_setting('audit_qr_reference',$files['next'],'audit');qr_save_fixture($files['routeOld']);
qr_must(is_file(DISCUZ_ROOT.$files['next']) && flipbook\front_asset::referenced($files['next']),'other saved plugin settings also protect a shared managed image');
DB::delete('flipbook_setting',['skey'=>'audit_qr_reference']);flipbook\front_asset::cleanup_replaced($files['next'],$files['routeOld']);
qr_must(!is_file(DISCUZ_ROOT.$files['next']),'removing the last settings reference permits managed-file cleanup');
$before=qr_snapshot();try{flipbook\front_service_config::prepare_updates(['wechat_qrcode'=>'javascript:invalid']);throw new RuntimeException('invalid QR accepted');}catch(InvalidArgumentException $expected){}
qr_must(qr_snapshot()===$before && is_file(DISCUZ_ROOT.$files['routeOld']),'invalid replacement leaves the saved URL and its file unchanged');
// Exercise the real admincp POST; native cpmsg exits, then the shutdown assertions above verify and rollback it.
$_G['uid']=900000001;$_G['adminid']=1;$_G['username']='QR fixture administrator';$pluginid=2;
$_POST=['flipbooksubmit'=>'1','wechat_tab'=>'login'];foreach(flipbook\front_service_config::schema() as $key=>$field)if(strpos($key,'wechat_')===0 && $field['type']!=='readonly')$_POST[$key]=$field['type']==='secret'?'':flipbook\front_service_config::get($key);
$_POST['wechat_qrcode']=$files['routeNew'];$_POST['formhash']=formhash();$_GET=$_POST+['pmod'=>'wechat','do'=>2];$_SERVER['REQUEST_METHOD']='POST';
$routeExpected=true;$routeBuffer=ob_get_level();ob_start();include './source/plugin/flipbook/admincp.inc.php';throw new RuntimeException('native save unexpectedly returned instead of confirming success');
