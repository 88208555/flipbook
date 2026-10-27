<?php
// Read-only HTML structure tests for the actual admin modules; no browser is started.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
if(!defined('IN_ADMINCP'))define('IN_ADMINCP',true);if(!defined('ADMINSCRIPT'))define('ADMINSCRIPT','admin.php');require_once './source/function/function_admincp.php';$lang=lang('admincp');
function admin_layout_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function admin_layout_snapshot(){$snapshot=[];foreach(['flipbook_product','flipbook_sku','flipbook_supplier','flipbook_category','flipbook_series','flipbook_album','flipbook_page','flipbook_page_product'] as $table){$rows=array_map('json_encode',DB::fetch_all('SELECT * FROM %t',[$table]));sort($rows);$snapshot[$table]=$rows;}return hash('sha256',json_encode($snapshot));}
function admin_layout_render($pmod,$op,$id=0){global $_G,$lang;$_GET=['do'=>2,'op'=>$op,'id'=>$id];ob_start();include './source/plugin/flipbook/'.$pmod.'.inc.php';return ob_get_clean();}
function admin_layout_dom($html){$previous=libxml_use_internal_errors(true);$doc=new DOMDocument();$doc->loadHTML('<!doctype html><meta charset="utf-8">'.$html);libxml_clear_errors();libxml_use_internal_errors($previous);return new DOMXPath($doc);}
$savedGet=$_GET;$before=admin_layout_snapshot();
try{
 foreach(['product'=>['flipbook_product','productid'],'category'=>['flipbook_category','catid'],'series'=>['flipbook_series','seriesid'],'supplier'=>['flipbook_supplier','supplierid']] as $pmod=>$table){
  $id=intval(DB::result_first('SELECT '.$table[1].' FROM %t ORDER BY '.$table[1].' LIMIT 1',[$table[0]]));
  foreach(['list',$id?'edit':'add'] as $op){
   $html=admin_layout_render($pmod,$op,$id);$dom=admin_layout_dom($html);$heading='//*[contains(concat(" ",normalize-space(@class)," ")," fb-product-heading ")]';$actions=$heading.'//*[@data-product-heading-actions]';
   admin_layout_check($dom->query($heading)->length===1&&$dom->query($actions)->length===1,$pmod.' '.$op.' has one title row containing the shared action area');
   admin_layout_check($dom->query($heading.'//nav/a')->length===4,$pmod.' '.$op.' keeps all four management navigation entries in that row');
   admin_layout_check($dom->query($actions.'//button[not(contains(concat(" ",normalize-space(@class)," ")," fb-admin-button "))]')->length===0,$pmod.' '.$op.' action buttons use the shared admin class');
   if($pmod==='supplier'&&$op==='list'){
    $workspace='//*[@data-fb-supplier-workspace]';
    admin_layout_check($dom->query($workspace)->length===1&&$dom->query($workspace.'//section[contains(@class,"fb-supplier-pane")]')->length===2,'supplier workspace separates suppliers and associated styles into two panes');
    admin_layout_check($dom->query($workspace.'//form[@data-supplier-search]//input[@name="supplier_name" or @name="supplier_code"]')->length===2,'supplier search filters both name and code');
    admin_layout_check($dom->query($workspace.'//*[@data-supplier-add]')->length===1&&$dom->query($workspace.'//*[@data-style-add and @disabled]')->length===1,'supplier and style add controls have distinct pane actions and require a selected owner');
    admin_layout_check($dom->query($workspace.'//table')->length===2&&$dom->query($workspace.'//*[@data-supplier-rows or @data-supplier-style-rows]')->length===2,'each pane has its own actual data table');
    admin_layout_check($dom->query($workspace.'//*[@data-supplier-pagination or @data-supplier-style-pagination]')->length===2,'each pane has an independent pagination container');
    admin_layout_check($dom->query($workspace.'//dialog[@data-supplier-dialog]//form[@data-supplier-edit]')->length===1,'supplier and style editing use the shared modal form');
    admin_layout_check($dom->query($workspace.'//*[@data-style-owner and @readonly]')->length===1&&$dom->query($workspace.'//input[@type="hidden" and @name="supplierid"]')->length===1,'style modal displays a read-only owner while submitting its actual supplier identity');
    admin_layout_check($dom->query($workspace.'//details[@data-supplier-extra]//*[@name="contact" or @name="phone" or @name="address" or @name="note" or @name="available"]')->length===5,'supplier modal preserves all existing optional contact and availability fields');
    admin_layout_check($dom->query($workspace.'//button[not(contains(concat(" ",normalize-space(@class)," ")," fb-admin-button "))]')->length===0,'all supplier workspace and dialog buttons reuse the shared control style');
    continue;
   }
   $form=$pmod==='product'&&$op!=='list'?'fb-product-form':flipbook\admin_crud::form_id($pmod,$op==='list'?'list':'edit');
   admin_layout_check($dom->query('//form[@id="'.$form.'"]')->length===1&&$dom->query($actions.'//button[@type="submit" and @form="'.$form.'"]')->length===1,$pmod.' '.$op.' title-row submit targets its actual form');
   admin_layout_check($dom->query('//form[@id="'.$form.'"]//input[@type="hidden" and @name="flipbooksubmit" and @value="1"]')->length===1,$pmod.' '.$op.' preserves the native POST submit marker');
   admin_layout_check($dom->query('//form[@id="'.$form.'"]//*[@type="submit"]')->length===0,$pmod.' '.$op.' has no duplicate footer or tab-row submit');
   if($op==='list'){
    admin_layout_check($dom->query($actions.'//a[contains(@href,"op=add")]')->length===1&&$dom->query($actions.'//button[@data-crud-delete and @disabled]')->length===1,$pmod.' list provides title-row add and initially disabled batch delete');
    admin_layout_check($dom->query('//form[@id="'.$form.'"]//input[@data-crud-select-all]')->length===1,$pmod.' list retains a real select-all checkbox');
   }else{
    admin_layout_check($dom->query($actions.'//a[normalize-space(.)="返回列表"]')->length===1,$pmod.' form has its single return action in the title row');
   }
   if($pmod==='product'&&$op!=='list'){
    admin_layout_check($dom->query('//div[contains(@class,"fb-product-toolbar")]//button[@type="submit"]')->length===0&&$dom->query('//div[contains(@class,"fb-product-toolbar-actions")]')->length===0,'product edit tabs contain no second action toolbar');
    admin_layout_check($dom->query($actions.'//button[@data-product-context and not(@hidden)]')->length===0,'product basic tab starts with unrelated add/upload actions hidden');
    admin_layout_check($dom->query('//form//*[@data-sku-add or @data-gallery-add or @data-table-col-add]')->length===0,'product add actions are not duplicated beneath the active component');
    $first='//*[contains(concat(" ",normalize-space(@class)," ")," fb-size-first-row ")]';
    admin_layout_check($dom->query($first.'//*[@id="fb-size-enabled" and self::button and @role="switch"]')->length===1&&$dom->query($first.'//*[@id="fb-size-reuse" and self::select]')->length===1,'size chart switch and reuse selector share the same first row');
    admin_layout_check($dom->query('//*[@id="fb-size-enabled"]')->length===1&&$dom->query('//input[@id="fb-size-enabled"]')->length===0,'size chart has a single accessible switch with no duplicate checkbox');
    admin_layout_check($dom->query('//div[contains(@class,"fb-image-actions")]//svg')->length===0&&$dom->query('//div[contains(@class,"fb-image-actions")]//button[normalize-space(.)="替换"]')->length>0,'image hover operations remain available as plain text buttons');
   }
  }
 }
}finally{$_GET=$savedGet;}
admin_layout_check(admin_layout_snapshot()===$before,'rendering every admin list and form leaves all business data unchanged');
