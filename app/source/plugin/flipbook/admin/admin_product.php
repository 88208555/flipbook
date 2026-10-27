<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ'))exit('Access Denied');
class admin_product {
 public static function navigation($active,$canPublish=true){
  admin_helper::assets();
  $draftView=(string)($_GET['view']??'')==='drafts';
  $draftParams=['view'=>'drafts']+($draftView?['origin'=>(string)($_GET['origin']??'local'),'kind'=>(string)($_GET['kind']??'product')]:[]);
  $drafts=class_exists('\flipbook_sync\service_draft_editor')&&\flipbook_sync\service_draft_editor::enabled();
  $editing=in_array((string)($_GET['op']??''),['add','edit'],true);
  $form=$active==='product'&&$editing?'fb-product-form':admin_crud::form_id($active,$editing?'edit':'list');
  echo '<link rel="stylesheet" href="source/plugin/flipbook/static/admin/product.css?v='.@filemtime(__DIR__.'/../static/admin/product.css').'">';
  echo '<div class="fb-product-heading"><nav class="fb-product-subnav">';
  foreach(['product'=>'商品列表','category'=>'商品分类','series'=>'商品系列','supplier'=>'供应商户'] as $key=>$label)echo '<a class="fb-admin-tab fb-product-nav-item'.($key===$active&&!$draftView?' is-active':'').'" href="'.admin_helper::e(admin_helper::url($key)).'">'.$label.'</a>';
  if($drafts)echo '<a class="fb-admin-tab fb-product-nav-item'.($draftView?' is-active':'').'" href="'.admin_helper::e(admin_helper::url('product',$draftParams)).'">草稿箱</a>';
  echo '</nav><div class="fb-product-heading-actions" data-product-heading-actions>';
  if($editing){
   if($active==='product'){
    foreach(['gallery'=>'添加图片','sku'=>'新增 SKU','sizetable'=>'添加自定义列'] as $context=>$label){$attribute=['gallery'=>'data-gallery-add=""','sku'=>'data-sku-add','sizetable'=>'data-table-col-add'][$context];echo '<button class="fb-admin-button" type="button" data-product-context="'.$context.'" '.$attribute.' hidden>'.$label.'</button>';}
    foreach(['cover'=>['cover'=>'上传图片'],'showcase'=>['showcase_cover'=>'上传图片'],'video'=>['video'=>'上传商品视频','showcase_video'=>'上传首页视频'],'overlay'=>['floating_image'=>'上传悬浮图片']] as $context=>$fields)foreach($fields as $field=>$label)echo '<button class="fb-admin-button" type="button" data-product-context="'.$context.'" data-product-upload="'.$field.'" hidden>'.$label.'</button>';
   }
   echo '<a class="fb-admin-button" href="'.admin_helper::e($draftView?admin_helper::url('product',$draftParams):admin_helper::url($active)).'">返回列表</a>';
   if($drafts&&($active==='product'||$draftView))echo '<button class="fb-admin-button" type="button" data-product-draft-save>保存草稿</button>';
   echo '<button class="fb-admin-button" type="submit" form="'.$form.'"'.(!$canPublish?' disabled':'').'>'.($draftView?'上架 / 启用':($active==='product'?'保存商品':'保存')).'</button>';
  }elseif($active!=='drafts'){
   echo '<a class="fb-admin-button" href="'.admin_helper::e(admin_helper::url($active,['op'=>'add'])).'">新增</a><button class="fb-admin-button" type="submit" form="'.$form.'" data-crud-delete disabled>删除选中</button>';
  }
  echo '</div></div><script src="source/plugin/flipbook/static/admin/crud.js?v='.@filemtime(__DIR__.'/../static/admin/crud.js').'" defer></script>';
 }

 public static function load($id){
  $row=table_flipbook_product::t()->fetch($id);$album=DB::fetch_first('SELECT a.* FROM %t a JOIN %t p ON p.albumid=a.albumid JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d ORDER BY a.albumid LIMIT 1',['flipbook_album','flipbook_page','flipbook_page_product',$id]);
  return self::form_values($row,$album,front_sku::rows($id));
 }
 public static function form_values($row,$album,$skus) {
  $spec=front_helper::spec($row['spec']??'');
  $raw=json_decode($row['spec']??'',true);$share=front_share::configuration($raw['share']??[]);
  return ['share_title'=>$share['title'],'share_subtitle'=>$share['subtitle'],'share_description'=>$share['description'],'sizechart'=>json_encode(admin_sizechart::from_spec($row['spec']??''),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'skulines'=>json_encode($skus,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'supplier'=>$spec['supplier']??'','overlay_lines'=>implode("\n",$spec['overlayLines']??[]),'floating_image'=>$spec['floatingImage']['src']??'','floating_link'=>$spec['floatingImage']['link']??'','spec_colors'=>implode(',',$spec['colors']),'spec_sizes'=>implode(',',$spec['sizes']),'spec_model'=>$spec['model'],'spec_fit'=>$spec['fit'],'spec_table'=>implode("\n",array_map(function($r){return implode('|',$r);},$spec['sizetable'])),'spec_fields'=>implode("\n",array_map(function($r){return $r['label'].'|'.$r['value'];},$spec['fields']??[])),'spec_content'=>$spec['content']??'','product_category'=>$album['catid']??0,'product_series'=>$album['seriesid']??0,'showcase_name'=>$album['name']??'','showcase_subtitle'=>$album['subtitle']??'','showcase_description'=>$album['description']??'','showcase_cover'=>$album['cover']??'','showcase_video'=>$album['video']??''];
 }
 public static function save($id){
  $spec=['supplier'=>admin_helper::text('supplier',255),'overlayLines'=>array_slice(array_values(array_filter(array_map('trim',explode("\n",admin_helper::textarea('overlay_lines',10000))))),0,20),'floatingImage'=>['src'=>admin_helper::cleanurl(admin_helper::text('floating_image',500)),'link'=>admin_helper::text('floating_link',500)],'colors'=>front_helper::tags(admin_helper::text('spec_colors',500)),'sizes'=>front_helper::tags(admin_helper::text('spec_sizes',500)),'model'=>admin_helper::text('spec_model',255),'fit'=>admin_helper::text('spec_fit',255),'sizetable'=>[],'fields'=>[],'content'=>admin_helper::textarea('spec_content',10000)];
  $spec['share']=front_share::configuration(['title'=>$_GET['share_title']??'','subtitle'=>$_GET['share_subtitle']??'','description'=>$_GET['share_description']??''],true);
  // Validate complete structured or legacy input before any writes. Hiding keeps all cells.
  $chart=isset($_GET['sizechart'])?admin_sizechart::parse($_GET['sizechart']):admin_sizechart::legacy($_GET['spec_table']??'');
  $skuRaw=$_POST['skulines']??$_GET['skulines']??null;
  $skus=$skuRaw===null?front_sku::rows($id):json_decode((string)$skuRaw,true);
  if(!is_array($skus))throw new \RuntimeException('SKU 配置无效');
  $spec['sizeChart']=$chart;$spec['sizetable']=admin_sizechart::table($chart,admin_sizechart::sku_sizes($skus));
  foreach(explode("\n",admin_helper::textarea('spec_fields',10000)) as $line){$parts=explode('|',$line,2);if(count($parts)===2 && trim($parts[0])!=='')$spec['fields'][]=['label'=>trim($parts[0]),'value'=>trim($parts[1])];}
  table_flipbook_product::t()->update($id,['spec'=>json_encode($spec,JSON_UNESCAPED_UNICODE)]);
  $product=table_flipbook_product::t()->fetch($id);$existing=DB::fetch_first('SELECT p.pageid,p.albumid FROM %t p JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d ORDER BY p.pageid LIMIT 1',['flipbook_page','flipbook_page_product',$id]);
  $category=admin_helper::uintval('product_category');$series=admin_helper::uintval('product_series');
  if($category && !table_flipbook_category::t()->fetch($category))throw new \RuntimeException('商品分类不存在');if($series && !table_flipbook_series::t()->fetch($series))throw new \RuntimeException('商品系列不存在');
  $album=['name'=>admin_helper::text('showcase_name',100)?:$product['name'],'subtitle'=>admin_helper::text('showcase_subtitle',255)?:$product['summary'],'description'=>admin_helper::textarea('showcase_description',3000)?:$product['description'],'cover'=>admin_helper::cleanurl(admin_helper::text('showcase_cover',255))?:$product['cover'],'video'=>admin_helper::cleanurl(admin_helper::text('showcase_video',255))?:$product['video'],'catid'=>$category,'seriesid'=>$series,'displayorder'=>$product['displayorder'],'available'=>$product['available'],'pagecount'=>1,'dateline'=>TIMESTAMP];
  $albumid=$existing?intval($existing['albumid']):0;if($albumid)table_flipbook_album::t()->update($albumid,$album);else $albumid=table_flipbook_album::t()->insert($album,true);
  $page=['albumid'=>$albumid,'title'=>$product['name'],'eyebrow'=>$product['code'],'description'=>$product['summary'],'image'=>$product['cover'],'displayorder'=>0,'available'=>$product['available'],'dateline'=>TIMESTAMP];
  if($existing)table_flipbook_page::t()->update($existing['pageid'],$page);else{$pageid=table_flipbook_page::t()->insert($page,true);DB::insert('flipbook_page_product',['pageid'=>$pageid,'productid'=>$id,'displayorder'=>0]);}
  table_flipbook_album::t()->recount_pages($albumid);
 }
 public static function delete_chain($id) {
  $albums=DB::fetch_all('SELECT DISTINCT p.albumid FROM %t p JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d',['flipbook_page','flipbook_page_product',$id]);
  foreach($albums as $a){$albumid=intval($a['albumid']);$other=DB::result_first('SELECT COUNT(*) FROM %t p JOIN %t pp ON pp.pageid=p.pageid WHERE p.albumid=%d AND pp.productid<>%d',['flipbook_page','flipbook_page_product',$albumid,$id]);if(!$other){foreach(DB::fetch_all('SELECT pageid FROM %t WHERE albumid=%d',['flipbook_page',$albumid]) as $p)DB::delete('flipbook_page_product',['pageid'=>$p['pageid']]);DB::delete('flipbook_page',['albumid'=>$albumid]);DB::delete('flipbook_album',['albumid'=>$albumid]);}}
 }
 public static function render($config,$row) {
  $id=intval($row['productid']??0);$values=($row?:[])+($row['__extra']??[]);
  $groups=[
   'basic'=>['基本资料',['name','code','product_category','product_series','supplierid','summary','description','priceshow','pricevalue','priceorigin','currency','displayorder','available']],
   'showcase'=>['首页展示',['showcase_name','showcase_subtitle','showcase_description','showcase_cover']],
   'cover'=>['商品首图',['cover']], 'video'=>['视频',['video','showcase_video']], 'sku'=>['SKU',['skulines']],
   'highlight'=>['商品卖点',[]], 'detail'=>['商品详情',[]], 'model'=>['模特图',[]],
   'spec'=>['规格属性',['spec_colors','spec_sizes','spec_fit','spec_model','supplier','spec_fields','spec_content']],
   'sizetable'=>['尺码表',['sizechart']], 'overlay'=>['悬浮内容',['overlay_lines','floating_image','floating_link']],
   'share'=>['分享配置',['share_title','share_subtitle','share_description']]];
  $schema=$config['schema']+$config['extra_fields']+['skulines'=>['title'=>'SKU','type'=>'textarea'],'sizechart'=>['title'=>'尺码表','type'=>'textarea']];$upload=front_helper::url('asset',['op'=>'upload']);
  echo '<form class="fb-product-editor" id="fb-product-form" method="post" action="'.admin_helper::e(($config['action_url']??admin_helper::url('product',['op'=>'save','id'=>$id]))).'" data-product="'.$id.'" data-upload="'.admin_helper::e($upload).'" data-assets="'.admin_helper::e(front_helper::url('asset',['op'=>'assets','productid'=>$id])).'" data-sizecharts="'.admin_helper::e(front_helper::url('asset',['op'=>'sizecharts'])).'">';
  echo '<input type="hidden" name="formhash" value="'.FORMHASH.'"><input type="hidden" name="flipbooksubmit" value="1"><div class="fb-product-toolbar"><nav class="fb-product-tabs">';
  foreach($groups as $key=>$group)echo '<button type="button" data-panel="'.$key.'"'.($key==='basic'?' class="fb-admin-tab is-active"':' class="fb-admin-tab"').'>'.$group[0].'</button>';
  echo '</nav></div><textarea name="medialines" hidden>'.admin_helper::e($values['medialines']??'').'</textarea>';
  foreach($groups as $key=>$group){echo '<section class="fb-product-panel" data-product-panel="'.$key.'"'.($key!=='basic'?' hidden':'').'><div class="fb-product-fields">';foreach($group[1] as $name){$field=$schema[$name]??['title'=>$name,'type'=>'text'];$value=$values[$name]??($field['default']??'');$type=$field['type'];echo '<div class="fb-product-field'.(in_array($type,['textarea','url'],true)?' is-wide':'').'">'.($name==='skulines'||$name==='sizechart'?'':'<span>'.admin_helper::e($field['title']).'</span>');
   if(in_array($name,['cover','video','showcase_video','floating_image','showcase_cover'],true)){
    $kind=strpos($name,'video')!==false?'video':'image';echo '<div class="fb-media-field" data-media-field="'.$name.'" data-kind="'.$kind.'"><input type="hidden" name="'.$name.'" value="'.admin_helper::e($value).'"><div class="fb-media-preview"></div><div class="fb-image-actions"><button class="fb-admin-button" type="button" data-media-upload>替换</button><button class="fb-admin-button" type="button" data-media-library>素材库</button><button class="fb-admin-button" type="button" data-media-clear>删除</button></div></div>';
   }elseif(strpos($name,'share_')===0){
    $limit=intval($field['maxlen']);$label=admin_helper::e($field['title']);$fallback=['share_title'=>'name','share_subtitle'=>'summary','share_description'=>'description'][$name];
    echo '<div class="fb-share-counted">';
    $attrs=' id="fb-'.$name.'" name="'.$name.'" aria-label="'.$label.'" data-share-limit="'.$limit.'" maxlength="'.($limit*2).'" placeholder="'.admin_helper::e($values[$fallback]??'').'"';
    echo $type==='textarea'?'<textarea'.$attrs.' rows="5">'.admin_helper::e($value).'</textarea>':'<input type="text"'.$attrs.' value="'.admin_helper::e($value).'">';
    echo '<small data-share-count="'.$name.'" aria-live="polite">'.mb_strlen($value,'UTF-8').' / '.$limit.'</small></div>';
   }elseif($name==='sizechart'){self::render_sizechart($value,$id);
   }elseif($name==='skulines'){echo '<textarea name="skulines" hidden>'.admin_helper::e($value).'</textarea><div id="fb-product-skus"></div>';
   }elseif($name==='medialines'){echo '<textarea name="medialines" hidden>'.admin_helper::e($value).'</textarea><div id="fb-product-media"></div>';}
   elseif($type==='textarea')echo '<textarea name="'.$name.'" rows="5">'.admin_helper::e($value).'</textarea>';
   elseif($type==='select'){echo '<select name="'.$name.'"><option value="0">未指定</option>'; foreach($config['pending_options'][$name]??[] as $pending=>$label)echo '<option value="'.admin_helper::e($pending).'"'.((string)$value===(string)$pending?' selected':'').'>'.admin_helper::e($label).'</option>'; $table=['category'=>'flipbook_category','series'=>'flipbook_series','supplier'=>'flipbook_supplier'][$field['source']];$pk=['category'=>'catid','series'=>'seriesid','supplier'=>'supplierid'][$field['source']];foreach(DB::fetch_all('SELECT * FROM %t ORDER BY displayorder,'.$pk,[$table]) as $option)echo '<option value="'.$option[$pk].'"'.(intval($value)===intval($option[$pk])?' selected':'').'>'.admin_helper::e($option['name']).'</option>';echo '</select>';}
   elseif($type==='bool')echo '<select name="'.$name.'"><option value="1"'.(intval($value)?' selected':'').'>'.($name==='available'?'上架 / 启用':'显示 / 启用').'</option><option value="0"'.(!intval($value)?' selected':'').'>'.($name==='available'?'草稿 / 停用':'隐藏 / 关闭').'</option></select>';
   else echo '<input name="'.$name.'" type="'.(in_array($type,['int','amount'],true)?'number':'text').'"'.($type==='amount'?' step="0.01"':'').' value="'.admin_helper::e($value).'"'.(!empty($field['required'])?' required':'').'>';
   if($name!=='spec_table'&&!empty($field['comment']))echo '<small>'.admin_helper::e($field['comment']).'</small>';echo '</div>'; }echo '</div>';if(in_array($key,['highlight','detail','model'],true))echo '<div class="fb-product-gallery" data-gallery-grid="'.$key.'"></div>';if($key==='share')echo '<p class="fb-product-share-help">各项留空时自动跟随当前商品资料；描述默认取商品说明，超过 200 字时截取前 200 字。分享图片自动使用商品首图，按显示区域等比适配。</p>';echo '</section>';}
  echo '<p class="fb-product-status" role="status"></p></form><script src="source/plugin/flipbook/static/admin/product.js?v='.@filemtime(__DIR__.'/../static/admin/product.js').'"></script>';
  if(class_exists('\flipbook_sync\service_draft_editor')&&\flipbook_sync\service_draft_editor::enabled())\flipbook_sync\service_draft_editor::assets($config,$row);
 }

 public static function render_sizechart($value,$id) {
  if($value==='')$value=json_encode(admin_sizechart::from_spec([]));
  echo '<textarea name="sizechart" hidden>'.admin_helper::e($value).'</textarea><div class="fb-size-config">';
  echo '<div class="fb-size-first-row"><div class="fb-size-setting"><label for="fb-size-enabled">显示尺码表</label><button class="fb-admin-button fb-size-toggle" type="button" id="fb-size-enabled" role="switch" aria-checked="false"><span class="fb-size-toggle-track" aria-hidden="true"><span class="fb-size-toggle-thumb"></span></span></button></div>';
  echo '<div class="fb-size-setting"><label for="fb-size-reuse">复用尺码表</label><select id="fb-size-reuse"><option value="">选择已保存的尺码表</option>';
  foreach(admin_sizechart::choices($id) as $choice)echo '<option value="'.$choice['productid'].'">'.admin_helper::e($choice['name'].' · '.$choice['productName']).'</option>';
  echo '</select></div></div><p class="fb-size-help">选择后覆盖当前表格内容；只保留当前商品已有的尺码，不会新增商品规格。</p>';
  echo '<div class="fb-size-setting"><label for="fb-size-name">尺码表名称</label><div class="fb-size-counted"><input id="fb-size-name" maxlength="40" placeholder="填写名称，保存商品后可供其他商品复用"><small data-size-name-count>0 / 40</small></div></div>';
  echo '<p class="fb-size-help">自动读取当前商品 SKU 的尺码；有标记显示标记，无标记显示规格值。长度建议以厘米填写，体重单位请注明在列名中。</p>';
  echo '<div id="fb-size-columns" class="fb-size-columns"></div><div id="fb-size-table"></div>';
  echo '<div class="fb-size-setting fb-size-note"><label for="fb-size-note">测量提示</label><div class="fb-size-counted"><textarea id="fb-size-note" maxlength="300" rows="2"></textarea><small data-size-note-count>0 / 300</small></div></div><p class="fb-size-help">填写后点击右上角“保存商品”，与商品一起保存。未填写的参数保持空白，不会自动计算。</p></div>';
 }

}
