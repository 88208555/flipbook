<?php
if(!defined('IN_DISCUZ')||!defined('IN_ADMINCP'))exit('Access Denied');
use flipbook\admin_helper;
use flipbook\admin_crud;
$config=['pmod'=>'supplier','table'=>'flipbook_supplier','title'=>'供应商户','orderby'=>['displayorder'=>'ASC'],
 'columns'=>['name','code','contact','phone','displayorder','available'],
 'schema'=>[
  'name'=>['title'=>'供应商户名称','type'=>'text','required'=>true,'maxlen'=>150],
  'code'=>['title'=>'供应商户编码','type'=>'text','maxlen'=>60],
  'contact'=>['title'=>'联系人','type'=>'text','maxlen'=>100],
  'phone'=>['title'=>'联系电话','type'=>'text','maxlen'=>60],
  'address'=>['title'=>'地址','type'=>'textarea','maxlen'=>500],
  'note'=>['title'=>'备注','type'=>'textarea','maxlen'=>2000],
  'displayorder'=>['title'=>'排序','type'=>'int','default'=>0],
  'available'=>['title'=>'是否启用','type'=>'bool','default'=>1]],
 'before_delete'=>function($id){if(DB::result_first('SELECT COUNT(*) FROM %t WHERE supplierid=%d',['flipbook_product',$id]))throw new RuntimeException('该供应商户仍关联商品，请先修改商品的供应商户');}];
if(!empty($flipbookDraftConfigOnly)) return $config;
$supplierReply=static function($data){
 ajaxshowheader();
 echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
 ajaxshowfooter();
};
$supplierAction=admin_helper::enumval('supplier_action',array_fill_keys(['list','supplier_save','supplier_delete','style_save','style_delete','supplier_order','style_order'],true));
if($supplierAction){
 try{
  if($supplierAction==='list'){
   $name=admin_helper::text('supplier_name',150);$code=admin_helper::text('supplier_code',60);
   $where='1';$params=['flipbook_supplier'];
   foreach(['name'=>$name,'code'=>$code] as $field=>$value){if($value!==''){$where.=' AND `'.$field.'` LIKE %s';$params[]='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$value).'%';}}
   $perpage=admin_helper::uintval('perpage');if(!in_array($perpage,[10,20,50],true))$perpage=10;
   $count=(int)DB::result_first('SELECT COUNT(*) FROM %t WHERE '.$where,$params);$page=min(max(1,admin_helper::uintval('page')),max(1,(int)ceil($count/$perpage)));
   $rows=DB::fetch_all('SELECT * FROM %t WHERE '.$where.' ORDER BY displayorder,supplierid LIMIT '.(($page-1)*$perpage).','.$perpage,$params);
   $selectedid=admin_helper::uintval('selectedid');$selected=$selectedid?DB::fetch_first('SELECT * FROM %t WHERE '.$where.' AND supplierid=%d',array_merge($params,[$selectedid])):[];
   if($selected&&!in_array((int)$selected['supplierid'],array_map('intval',array_column($rows,'supplierid')),true)){
    $preceding=(int)DB::result_first('SELECT COUNT(*) FROM %t WHERE '.$where.' AND (displayorder<%d OR (displayorder=%d AND supplierid<%d))',array_merge($params,[(int)$selected['displayorder'],(int)$selected['displayorder'],(int)$selected['supplierid']]));
    $page=(int)floor($preceding/$perpage)+1;
    $rows=DB::fetch_all('SELECT * FROM %t WHERE '.$where.' ORDER BY displayorder,supplierid LIMIT '.(($page-1)*$perpage).','.$perpage,$params);
   }
   if(!$selected&&$rows)$selected=$rows[0];
   $selectedid=(int)($selected['supplierid']??0);$stylepage=max(1,admin_helper::uintval('stylepage'));$stylecount=0;$styles=[];
   if($selectedid){
    $stylecount=(int)DB::result_first('SELECT COUNT(*) FROM %t WHERE supplierid=%d',['flipbook_product',$selectedid]);$stylepage=min($stylepage,max(1,(int)ceil($stylecount/$perpage)));
    $styles=DB::fetch_all('SELECT productid,supplierid,name,code,displayorder FROM %t WHERE supplierid=%d ORDER BY displayorder,productid LIMIT '.(($stylepage-1)*$perpage).','.$perpage,['flipbook_product',$selectedid]);
   }else $stylepage=1;
   $supplierReply(['success'=>true,'suppliers'=>$rows,'count'=>$count,'page'=>$page,'perpage'=>$perpage,'selected'=>$selected,'styles'=>$styles,'styleCount'=>$stylecount,'stylePage'=>$stylepage]);
  }
  if(!submitcheck('flipbooksubmit'))throw new RuntimeException('请求已失效，请刷新页面后重试');
  $order=trim((string)($_GET['displayorder']??'0'));
  $isStyleOrder=strpos($supplierAction,'style_')===0;$orderMin=$isStyleOrder?-32768:-2147483648;$orderMax=$isStyleOrder?32767:2147483647;
  if(!preg_match('/^-?\d+$/',$order)||(float)$order<$orderMin||(float)$order>$orderMax)throw new RuntimeException('排序必须填写 '.$orderMin.' 到 '.$orderMax.' 之间的整数');
  if($supplierAction==='supplier_order'||$supplierAction==='style_order'){
   admin_helper::needperm('edit');$id=admin_helper::uintval('id');$isStyle=$supplierAction==='style_order';$table=$isStyle?'flipbook_product':'flipbook_supplier';$pk=$isStyle?'productid':'supplierid';$row=admin_crud::fetch($table,$pk,$id);
   if(!$row)throw new RuntimeException('记录不存在或已删除');
   if($isStyle&&(int)$row['supplierid']!==admin_helper::uintval('supplierid'))throw new RuntimeException('该款式不属于当前供应商户');
   DB::update($table,['displayorder'=>(int)$order],[$pk=>$id]);$supplierReply(['success'=>true,'message'=>'排序已保存']);
  }
  if($supplierAction==='supplier_save'||$supplierAction==='supplier_delete'){
   $config['before_save']=static function($id){if($id&&!admin_crud::fetch('flipbook_supplier','supplierid',$id))throw new RuntimeException('供应商户不存在或已删除');};
   $config['after_save']=static function($id)use($supplierReply){$supplierReply(['success'=>true,'id'=>(int)$id,'message'=>'供应商户已保存']);};
   $config['after_delete']=static function()use($supplierReply){$supplierReply(['success'=>true,'message'=>'供应商户已删除']);};
   admin_crud::handle_post($config);return;
  }
  $supplierid=admin_helper::uintval('supplierid');$supplier=admin_crud::fetch('flipbook_supplier','supplierid',$supplierid);
  if(!$supplier)throw new RuntimeException('请先选择有效的供应商户');
  $styleConfig=['pmod'=>'supplier','table'=>'flipbook_product','schema'=>[
   'supplierid'=>['title'=>'所属供应商','type'=>'select','source'=>'supplier','required'=>true],
   'name'=>['title'=>'款式名称','type'=>'text','required'=>true,'maxlen'=>150],
   'code'=>['title'=>'款式编码','type'=>'text','maxlen'=>60],
   'displayorder'=>['title'=>'排序','type'=>'int']],
   'before_save'=>static function($id)use($supplierid){if($id){$row=admin_crud::fetch('flipbook_product','productid',$id);if(!$row||(int)$row['supplierid']!==$supplierid)throw new RuntimeException('该款式不属于当前供应商户');}},
   'after_save'=>static function($id)use($supplierReply){$supplierReply(['success'=>true,'id'=>(int)$id,'message'=>'款式已保存']);},
   'before_delete'=>static function($id)use($supplierid){$row=admin_crud::fetch('flipbook_product','productid',$id);if(!$row||(int)$row['supplierid']!==$supplierid)throw new RuntimeException('该款式不属于当前供应商户');flipbook\admin_product::delete_chain($id);DB::delete('flipbook_sku',['productid'=>$id]);flipbook\table_flipbook_product_media::t()->delete_by_product($id);flipbook\table_flipbook_page_product::t()->delete_by_product($id);},
   'after_delete'=>static function($ids)use($supplierReply){foreach($ids as $id)flipbook\front_asset::cleanup($id);$supplierReply(['success'=>true,'message'=>'款式已删除']);}];
  admin_crud::handle_post($styleConfig);return;
 }catch(Throwable $error){$supplierReply(['success'=>false,'message'=>$error instanceof RuntimeException?$error->getMessage():'操作失败，未保存修改']);}
}
if(admin_crud::handle_post($config))return;
$op=admin_helper::enumval('op',['list'=>'','add'=>'','edit'=>'']);
if($op==='add'||$op==='edit'){
 flipbook\admin_product::navigation('supplier');$row=admin_crud::fetch('flipbook_supplier','supplierid',admin_helper::uintval('id'));if($op==='edit'&&!$row)admin_helper::failed('供应商户不存在');admin_crud::render_form($config,$row);return;
}
echo '<div class="fb-supplier-page">';
flipbook\admin_product::navigation('supplier');
?>
<div class="fb-supplier-workspace" data-fb-supplier-workspace data-url="<?php echo admin_helper::e(admin_helper::url('supplier')); ?>" data-formhash="<?php echo admin_helper::e(FORMHASH); ?>">
 <section class="fb-supplier-pane" aria-label="供应商户">
  <form class="fb-supplier-filters" data-supplier-search>
   <h3>供应商户</h3>
   <label>供应商名称<input name="supplier_name" maxlength="150" placeholder="请输入供应商名称"></label>
   <label>供应商编码<input name="supplier_code" maxlength="60" placeholder="请输入供应商编码"></label>
   <div class="fb-supplier-actions"><button class="fb-admin-button" type="submit">搜索</button><button class="fb-admin-button" type="button" data-supplier-reset>重置</button><button class="fb-admin-button" type="button" data-supplier-add>添加供应商</button></div>
  </form>
  <div class="fb-supplier-table-wrap"><table class="fb-supplier-table"><thead><tr><th>中文供应商名字</th><th>供应商编码</th><th>排序</th><th>操作</th></tr></thead><tbody data-supplier-rows></tbody></table></div>
  <div class="fb-supplier-pagination" data-supplier-pagination></div>
 </section>
 <section class="fb-supplier-pane" aria-label="关联款式">
  <div class="fb-supplier-style-heading"><h3 data-supplier-selected>请选择供应商户</h3><button class="fb-admin-button" type="button" data-style-add disabled>添加款式</button></div>
  <div class="fb-supplier-table-wrap"><table class="fb-supplier-table"><thead><tr><th>款式名称</th><th>款式编码</th><th>排序</th><th>操作</th></tr></thead><tbody data-supplier-style-rows></tbody></table></div>
  <div class="fb-supplier-pagination" data-supplier-style-pagination></div>
 </section>
 <p class="fb-supplier-status" role="status" aria-live="polite" data-supplier-status></p>
 <dialog class="fb-supplier-dialog" data-supplier-dialog>
  <form data-supplier-edit>
   <div class="fb-supplier-dialog-heading"><h3 data-supplier-dialog-title>编辑供应商</h3><button class="fb-admin-button" type="button" data-supplier-close>关闭</button></div>
   <input type="hidden" name="id"><input type="hidden" name="supplierid">
   <div class="fb-admin-form-row" data-style-owner-row hidden><label class="fb-admin-form-label" for="fb-style-owner">所属供应商</label><div class="fb-admin-form-field"><input id="fb-style-owner" data-style-owner readonly></div></div>
   <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-edit-name" data-supplier-name-label>供应商名称</label><div class="fb-admin-form-field"><input id="fb-supplier-edit-name" name="name" maxlength="150" required></div></div>
   <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-edit-code" data-supplier-code-label>供应商编码</label><div class="fb-admin-form-field"><input id="fb-supplier-edit-code" name="code" maxlength="60"></div></div>
   <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-edit-order">排序</label><div class="fb-admin-form-field"><input id="fb-supplier-edit-order" name="displayorder" type="number" step="1" min="-2147483648" max="2147483647" value="0" required></div></div>
   <details class="fb-supplier-extra" data-supplier-extra><summary>更多供应商信息</summary>
    <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-contact">联系人</label><div class="fb-admin-form-field"><input id="fb-supplier-contact" name="contact" maxlength="100"></div></div>
    <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-phone">联系电话</label><div class="fb-admin-form-field"><input id="fb-supplier-phone" name="phone" maxlength="60"></div></div>
    <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-address">地址</label><div class="fb-admin-form-field"><textarea id="fb-supplier-address" name="address" maxlength="500" rows="2"></textarea></div></div>
    <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-note">备注</label><div class="fb-admin-form-field"><textarea id="fb-supplier-note" name="note" maxlength="2000" rows="3"></textarea></div></div>
    <div class="fb-admin-form-row"><label class="fb-admin-form-label" for="fb-supplier-enabled">是否启用</label><div class="fb-admin-form-field"><select id="fb-supplier-enabled" name="available"><option value="1">启用</option><option value="0">停用</option></select></div></div>
   </details>
   <p class="fb-supplier-dialog-status" role="status" data-supplier-dialog-status></p>
   <div class="fb-supplier-dialog-actions"><button class="fb-admin-button" type="button" data-supplier-close>取消</button><button class="fb-admin-button" type="submit">确定</button></div>
  </form>
 </dialog>
</div></div>
