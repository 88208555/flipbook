<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require './source/class/class_core.php';C::app()->init();
if(getenv('FLIPBOOK_SYNC_CANDIDATE')==='1'&&is_file('/tmp/flipbook-sync-increment-test/flipbook/admin/admin_crud.php')){
 require '/tmp/flipbook-sync-increment-test/flipbook/admin/admin_crud.php';require '/tmp/flipbook-sync-increment-test/flipbook/admin/admin_product.php';
 spl_autoload_register(function($name){if(str_starts_with($name,'flipbook_sync\\')){ $relative=str_replace('\\','/',substr($name,14));$path='/tmp/flipbook-sync-increment-test/flipbook_sync/'.(str_starts_with($relative,'admin_')?'admin/':'service/').$relative.'.php';if(is_file($path))require $path;}},true,true);
}
use flipbook_sync\service_draft_store as Draft;
use flipbook_sync\service_draft_editor as Editor;
use flipbook_sync\service_draft_publish as Publisher;
use flipbook_sync\service_match as MatchFields;
use flipbook_sync\service_store as Store;
use flipbook_sync\service_export as Export;
$checks=0;
function check($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";$checks++;}
function rejects($call,$message){try{$call();}catch(Throwable $e){check(true,$message);return;}throw new RuntimeException('Expected rejection: '.$message);}
function snapshot(){ $out=[];foreach(['flipbook_product','flipbook_sku','flipbook_product_media','flipbook_album','flipbook_page','flipbook_page_product','flipbook_category','flipbook_series','flipbook_supplier','flipbook_setting','flipbook_micropage','flipbook_identity'] as $t){$rows=DB::fetch_all('SELECT * FROM %t',[$t]);usort($rows,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));$out[$t]=hash('sha256',json_encode($rows));}return $out;}
Store::install();$before=snapshot();$beforeDrafts=DB::fetch_all('SELECT * FROM %t ORDER BY draftid',['flipbook_sync_draft']);$tag='DRAFT'.strtoupper(bin2hex(random_bytes(4)));$source=0;$uid=4294000001;$targets=[];
$_G['uid']=$uid;$_G['adminid']=1;$_GET=['do'=>2,'pmod'=>'product'];
try{
 $source=DB::insert('flipbook_sync_source',['name'=>$tag,'endpoint'=>'https://'.strtolower($tag).'.example/','domain'=>'fixture.example','keycipher'=>Store::encrypt('fixture'),'enabled'=>1,'instanceid'=>str_repeat('e',32),'lastsync'=>0],true);
 $item=Export::page(0,1)['items'][0];$item['spec']['supplier']='供应商源说明';$item['showcase']['description']='独立首页正文';$item['productid']=912345;$item['name']=$tag;$item['code']=$tag.'-SPU';
 $item['category_path']=[['catid'=>921001,'name'=>$tag.'分类','parentid'=>0,'displayorder'=>0,'available'=>1]];
 $item['series']=['seriesid'=>921002,'name'=>$tag.'系列','description'=>'fixture','cover'=>'https://images.example/original.webp','displayorder'=>0,'available'=>1];
 $item['supplier']=['supplierid'=>921003,'name'=>$tag.'供应商','code'=>$tag.'-SUP','displayorder'=>0,'available'=>1];
 foreach($item['skus'] as $i=>&$sku){$sku['code']=$tag.'-SKU'.$i;$sku['images']=['https://images.example/original-'.$i.'.webp'];$sku['available']=$i===0?0:1;}unset($sku);
 if(!$item['media'])$item['media']=[['mediaid'=>913001,'productid'=>912345,'type'=>'highlight','title'=>'图组标题','image'=>'https://images.example/group.webp','displayorder'=>0]];
 foreach($item['media'] as $i=>&$media)$media['image']='https://images.example/group-'.$i.'.webp';unset($media);
 check(!MatchFields::inspect($source,'product',$item)['ok'],'unmatched category/series/supplier block import');
 rejects(fn()=>Publisher::receive($uid,$source,'product',$item),'backend rejects one-click draft import before relationships match');
 check(snapshot()===$before,'blocked import changes no production data');
 foreach(['category'=>921001,'series'=>921002,'supplier'=>921003] as $kind=>$remote)MatchFields::save_choice($source,$kind,$remote,'new');
 check(MatchFields::inspect($source,'product',$item)['ok'],'explicit same-type new choices pass');
 rejects(fn()=>MatchFields::save_choice($source,'product',912345,'new'),'product cannot be configured as a category/series/supplier relation');
 rejects(fn()=>MatchFields::save_mapping($source,'product',['vendor'=>'supplierid']),'ordinary field mapping cannot override mandatory supplier relation');
 $bad=$item;$bad['extraFabric']='棉';check(!MatchFields::inspect($source,'product',$bad)['ok'],'unknown source fields are reported as mismatches');
 MatchFields::save_mapping($source,'product',['extraFabric'=>'custom:面料来源']);
 $mapped=MatchFields::inspect($source,'product',$bad);check($mapped['ok']&&end($mapped['item']['spec']['fields'])['value']==='棉','explicit new product attribute resolves unknown field');
 $draft=Publisher::receive($uid,$source,'product',$bad);
 check(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND sourceid=%d',['flipbook_sync_draft',$uid,$source]))===4,'external product creates four distinct typed drafts');
 check(snapshot()===$before,'receiving all four draft types leaves production untouched');
 $payload=json_decode($draft['payload'],true);check($payload['fields']['available']==='0','received product defaults to draft/disabled');
 $edit=bin2hex(random_bytes(16));$payload['fields']['summary']='管理员编辑';
 $saved=Draft::save($uid,'external','product',$source,912345,0,$payload,$edit,2,$draft['revision']);
 $older=Draft::save($uid,'external','product',$source,912345,0,['fields'=>['name'=>'old']],$edit,1,$saved['revision']);
 check($older['revision']===$saved['revision']&&json_decode($older['payload'],true)['fields']['summary']==='管理员编辑','late autosave cannot overwrite newer sequence');
 rejects(fn()=>Draft::save($uid,'external','product',$source,912345,0,$payload,bin2hex(random_bytes(16)),1,1),'stale second editing session cannot overwrite newer draft');
 rejects(fn()=>Draft::fetch($saved['draftid'],$uid+1),'draft access is owner-bound');
 $repeat=Publisher::receive($uid,$source,'product',$bad);check($repeat['revision']===$saved['revision'],'repeated one-click import preserves edited draft');
 rejects(fn()=>Publisher::publish($uid,[$draft['draftid']=>1]),'stale publish version rejected');
 check(snapshot()===$before,'stale publication cannot partially create relationships');
 $published=Publisher::publish($uid,[$saved['draftid']=>$saved['revision']]);$local=$published[0];$targets[]=$local;
 $row=flipbook\table_flipbook_product::t()->fetch($local);
 check($row['name']===$tag&&$row['summary']==='管理员编辑'&&intval($row['available'])===1,'publish uses edited native fields and enables product');
 check(!DB::result_first('SELECT draftid FROM %t WHERE uid=%d AND sourceid=%d',['flipbook_sync_draft',$uid,$source]),'successful dependency publication clears exact four drafts');
 check(intval(Store::mapping($source,'product',912345)['localid'])===$local,'source identity maps to local product');
 check($row['cover']===$item['cover'],'cover keeps original URL');check(flipbook\front_helper::spec($row['spec'])['supplier']==='供应商源说明'&&flipbook\admin_product::load($local)['showcase_description']==='独立首页正文','native editor preserves independent supplier text and showcase body');
 $skus=flipbook\front_sku::rows($local);check(!$skus||$skus[0]['images'][0]==='https://images.example/original-0.webp','SKU URLs are reused without media copies');
 check(!$skus||intval($skus[0]['available'])===0,'native draft publication preserves disabled SKU status');
 $media=flipbook\table_flipbook_product_media::t()->fetch_all_by_product($local);check(!$media||$media[0]['image']==='https://images.example/group-0.webp','media group URLs remain original');
 $mappedMedia=true;foreach($item['media'] as $sourceMedia)if($sourceMedia['type']!=='sku'){$m=Store::mapping($source,'media',$sourceMedia['mediaid']);$mappedMedia=$mappedMedia&&$m&&DB::result_first('SELECT mediaid FROM %t WHERE mediaid=%d AND productid=%d',['flipbook_product_media',$m['localid'],$local]);}
 check($mappedMedia,'draft publication records native media identities for partial field updates');
 $partial=$item;foreach($partial['media'] as &$sourceMedia)unset($sourceMedia['type'],$sourceMedia['title'],$sourceMedia['displayorder']);unset($sourceMedia);
 $expectedLines=implode("\n",array_map(fn($m)=>$m['type'].'|'.$m['title'].'|'.$m['image'],$media));
 check(Editor::snapshot_fields('product',$partial,$source,$local)['medialines']===$expectedLines,'disabled media type and title fields preserve the existing native values');
 $withoutImage=$item;$withoutImage['summary']=$row['summary'];foreach($withoutImage['media'] as &$sourceMedia){unset($sourceMedia['image']);$sourceMedia['title']='授权更新标题';}unset($sourceMedia);
 $mediaDraft=Publisher::receive($uid,$source,'product',$withoutImage);Publisher::publish($uid,[$mediaDraft['draftid']=>$mediaDraft['revision']]);$media=flipbook\table_flipbook_product_media::t()->fetch_all_by_product($local);
 check($media[0]['image']==='https://images.example/group-0.webp'&&$media[0]['title']==='授权更新标题','publishing with image permission disabled keeps the URL while updating permitted media titles');
 $reordered=$partial;$reordered['media']=array_reverse($reordered['media']);$expectedLines=implode("\n",array_map(fn($m)=>$m['type'].'|'.$m['title'].'|'.$m['image'],$media));
 check(Editor::snapshot_fields('product',$reordered,$source,$local)['medialines']===$expectedLines,'disabled media ordering preserves native order even if the response sequence changes');
 $ambiguous=[$media[0],array_replace($media[0],['mediaid'=>999998,'displayorder'=>1])];$retained=flipbook_sync\service_fields::collection_patch($source,'media',[['mediaid'=>999999,'image'=>$media[0]['image']]],$ambiguous,'image');
 check(count($retained)===2&&$retained[0]['title']===$media[0]['title']&&$retained[1]['type']===$media[0]['type'],'ambiguous duplicate URLs preserve local media rather than inventing cleared denied fields');
 DB::query('START TRANSACTION');
 try{
  DB::delete('flipbook_product_media',['productid'=>$local]);foreach($media as $copy){unset($copy['mediaid']);DB::insert('flipbook_product_media',$copy);}
  $expectedLines=implode("\n",array_map(fn($m)=>$m['type'].'|'.$m['title'].'|'.$m['image'],$media));
  check(Editor::snapshot_fields('product',$partial,$source,$local)['medialines']===$expectedLines,'native media ID rebuild falls back to an unambiguous original URL without clearing disabled fields');
 }finally{DB::query('ROLLBACK');}
 $oldGet=$_GET;$_GET=['do'=>2,'pmod'=>'product','view'=>'drafts','origin'=>'external','kind'=>'category','op'=>'edit'];ob_start();flipbook\admin_product::navigation('category');$navigation=html_entity_decode(ob_get_clean());$_GET=$oldGet;check(str_contains($navigation,'origin=external')&&str_contains($navigation,'kind=category'),'draft editor back navigation keeps the external category tab');
 $nativeConfig=Editor::config('product');$fields=array_map('strval',array_intersect_key($row,$nativeConfig['schema']))+array_map('strval',flipbook\admin_product::load($local));
 $key=bin2hex(random_bytes(16));$fields['summary']='仅草稿修改';$auto=Draft::save($uid,'local','product',0,0,$local,['fields'=>$fields,'editor_key'=>$key],bin2hex(random_bytes(16)),1,0);
 check(flipbook\table_flipbook_product::t()->fetch($local)['summary']==='管理员编辑','local automatic draft never alters published content');
 check(Draft::local($uid,$local)['draftid']===$auto['draftid'],'reopening retrieves persistent local draft');
 rejects(fn()=>Draft::save($uid,'local','category',0,0,0,['fields'=>[]],bin2hex(random_bytes(16)),1,0,'x'),'local drafts do not invent category/series/supplier draft systems');
 $_POST=['draftid'=>$auto['draftid'],'draft_revision'=>$auto['revision']];Editor::before_save($local);Editor::clear_after_save($local);
 check(!Draft::local($uid,$local),'successful native save clears matching local snapshot');
 DB::query('START TRANSACTION');$boxGet=$_GET;
 try{
  $inactive=$row;unset($inactive['productid']);$inactive['name']=$tag.'-LOCALBOX';$inactive['code']=$tag.'-LOCALBOX';$inactive['available']=0;
  $inactiveId=DB::insert('flipbook_product',$inactive,true);DB::update('flipbook_product',['available'=>0],['productid'=>$local]);
  $_GET=['do'=>2,'pmod'=>'product','view'=>'drafts'];ob_start();flipbook_sync\admin_draft::run();$box=ob_get_clean();
  check(str_contains($box,'value="native:'.$inactiveId.':')&&!str_contains($box,'value="native:'.$local.':'),'native draft box renders under Discuz SQL protection and excludes mapped external products');
  $inactiveFields=$fields;$inactiveFields['name']=$inactive['name'];Draft::save($uid,'local','product',0,0,$inactiveId,['fields'=>$inactiveFields,'editor_key'=>bin2hex(random_bytes(16))],bin2hex(random_bytes(16)),1,0);
  ob_start();flipbook_sync\admin_draft::run();$box=ob_get_clean();
  check(!str_contains($box,'value="native:'.$inactiveId.':')&&str_contains($box,$inactive['name']),'native inactive product with an edit snapshot appears only once in the draft box');
  $entry=getenv('FLIPBOOK_SYNC_CANDIDATE')==='1'?'/tmp/flipbook-sync-increment-test/flipbook/product.inc.php':'/var/www/html/source/plugin/flipbook/product.inc.php';
  ob_start();$entryResult=include $entry;$entryHtml=ob_get_clean();
  check((bool)$entryResult&&str_contains($entryHtml,'草稿箱'),'native draft module reports successful inclusion to the Discuz admin loader');
 }finally{$_GET=$boxGet;DB::query('ROLLBACK');}
 $update=$item;unset($update['summary'],$update['spec'],$update['skus'],$update['media'],$update['showcase']);
 $new=Publisher::receive($uid,$source,'product',$update);$p=json_decode($new['payload'],true);
 check($p['fields']['summary']==='管理员编辑','disabled source fields preserve existing local product content in editor');
 $edit=bin2hex(random_bytes(16));$p['fields']['name']='';$invalid=Draft::save($uid,'external','product',$source,912345,$local,$p,$edit,1,$new['revision']);$stable=snapshot();
 rejects(fn()=>Publisher::publish($uid,[$invalid['draftid']=>$invalid['revision']]),'incomplete native draft cannot publish');
 check(snapshot()===$stable&&Draft::fetch($invalid['draftid'],$uid),'failed publication rolls back and retains draft');
 $badUrl=$item;$badUrl['cover']='javascript:alert(1)';check(!MatchFields::inspect($source,'product',$badUrl)['ok'],'invalid source media URL is red mismatch before import');
 $cycle=$item;$cycle['category_path'][]=['catid'=>921001,'parentid'=>921001,'name'=>'cycle'];check(!MatchFields::inspect($source,'product',$cycle)['ok'],'repeated category IDs cannot form cyclic dependency drafts');
 foreach([921010,921011] as $remote)MatchFields::save_choice($source,'category',$remote,'new');
 $parent=['catid'=>921010,'parentid'=>0,'name'=>$tag.'独立父分类','displayorder'=>0,'available'=>1];$child=['catid'=>921011,'parentid'=>921010,'name'=>$tag.'独立子分类','displayorder'=>0,'available'=>1];$child['category_path']=[$parent,$child];
 $catDraft=Publisher::receive($uid,$source,'category',$child);check(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND kind=%s AND remoteid IN (%d,%d)',['flipbook_sync_draft',$uid,'category',921010,921011]))===2,'standalone child category also receives its pending parent draft');
 $pending=Publisher::pending_options($catDraft);check(!isset($pending['parentid'][Publisher::token('category',921011)])&&isset($pending['parentid'][Publisher::token('category',921010)]),'pending parent selector excludes the category itself');
 $correctPayload=json_decode($catDraft['payload'],true);$selfPayload=$correctPayload;$selfPayload['fields']['parentid']=Publisher::token('category',921011);$selfEdit=bin2hex(random_bytes(16));
 $selfDraft=Draft::save($uid,'external','category',$source,921011,0,$selfPayload,$selfEdit,1,$catDraft['revision']);
 rejects(fn()=>Publisher::publish($uid,[$selfDraft['draftid']=>$selfDraft['revision']]),'server rejects a self-referencing pending category before dependency recursion');
 $catDraft=Draft::save($uid,'external','category',$source,921011,0,$correctPayload,$selfEdit,2,$selfDraft['revision']);
 $publishedCat=Publisher::publish($uid,[$catDraft['draftid']=>$catDraft['revision']]);$parentMap=Store::mapping($source,'category',921010);$childRow=DB::fetch_first('SELECT * FROM %t WHERE catid=%d',['flipbook_category',$publishedCat[0]]);
 check(intval($childRow['parentid'])===intval($parentMap['localid'])&&intval($childRow['parentid'])!==921010,'standalone category publishes native parent mapping atomically');
 foreach(['category','series','supplier'] as $kind){$entity=Export::entity_page($kind,0,1);check(isset($entity['items'],$entity['has_more']),'typed '.$kind.' API page is available');}
}finally{
 if($source){
  $maps=DB::fetch_all('SELECT * FROM %t WHERE sourceid=%d',['flipbook_sync_map',$source]);
  foreach($maps as $m)if($m['kind']==='product'){
   $pid=intval($m['localid']);$albums=DB::fetch_all('SELECT p.albumid,p.pageid FROM %t p JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d',['flipbook_page','flipbook_page_product',$pid]);
   foreach($albums as $a){DB::delete('flipbook_page_product',['pageid'=>$a['pageid']]);DB::delete('flipbook_page',['pageid'=>$a['pageid']]);DB::delete('flipbook_album',['albumid'=>$a['albumid']]);}
   DB::delete('flipbook_sku',['productid'=>$pid]);DB::delete('flipbook_product_media',['productid'=>$pid]);DB::delete('flipbook_product',['productid'=>$pid]);DB::delete('flipbook_sync_draft',['uid'=>$uid,'origin'=>'local','kind'=>'product','targetid'=>$pid]);
  }
  foreach($maps as $m)if(in_array($m['kind'],['category','series','supplier'],true)){$def=flipbook_sync\service_fields::entities()[$m['kind']];DB::delete($def['table'],[$def['pk']=>$m['localid']]);}
  DB::delete('flipbook_sync_draft',['uid'=>$uid,'sourceid'=>$source]);DB::delete('flipbook_sync_map',['sourceid'=>$source]);DB::delete('flipbook_sync_source',['sourceid'=>$source]);
  DB::query('DELETE FROM %t WHERE skey LIKE %s OR skey LIKE %s',['flipbook_sync_setting','choice:'.$source.':%','mapping:'.$source.':%']);
 }
}
check(snapshot()===$before,'original product/configuration/member data unchanged after fixture cleanup');check(DB::fetch_all('SELECT * FROM %t ORDER BY draftid',['flipbook_sync_draft'])===$beforeDrafts,'all pre-existing drafts remain unchanged after owned fixture cleanup');echo 'TOTAL '.$checks." draft checks\n";
