<?php
// Real MySQL fixture checks. Existing business rows are compared after exact cleanup.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require './source/class/class_core.php';C::app()->init();require_once './source/function/function_admincp.php';
use flipbook_sync\service_auth as Auth;
use flipbook_sync\service_store as Store;
use flipbook_sync\service_export as Export;
use flipbook_sync\service_import as Importer;
use flipbook_sync\service_client as Client;
set_exception_handler(function($e){fwrite(STDERR,'FAIL '.$e->getMessage()."\n");exit(1);});
$checks=0;
function check($value,$message){global $checks;if(!$value)throw new RuntimeException($message);$checks++;echo 'PASS '.$message."\n";}
function rejects($fn,$message){try{$fn();}catch(Throwable $e){check(true,$message);return;}throw new RuntimeException('Expected rejection: '.$message);}
function snapshot(){
 $out=[];
 foreach(['flipbook_product','flipbook_sku','flipbook_product_media','flipbook_album','flipbook_page','flipbook_page_product','flipbook_category','flipbook_series','flipbook_supplier','flipbook_setting','flipbook_micropage','flipbook_identity'] as $table) {
  $rows=DB::fetch_all('SELECT * FROM %t',[$table]);usort($rows,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));$out[$table]=hash('sha256',json_encode($rows));
 }
 return $out;
}
Store::install();$instance=Store::instance();Store::install();
check(Store::instance()===$instance && preg_match('/^[a-f0-9]{32}$/D',$instance),'repeated installation preserves the source identity');
$before=snapshot();
DB::query('START TRANSACTION');
try {
 // Discuz X5 merges raw POST values into GET; it does not add magic quotes.
 $postBefore=$_POST;$getBefore=$_GET;$requestBefore=$_SERVER['REQUEST_METHOD'];$uidBefore=$_G['uid'];$adminBefore=$_G['adminid'];
 try{
  $rawFields=['name'=>'原始请求草稿','skulines'=>json_encode([['code'=>'RAW-REQUEST','name'=>'带"引号"与\\反斜线']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'sizechart'=>json_encode(['note'=>'尺码"说明"'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'spec_content'=>"包含引号 \" 与路径 C:\\images\\test"];
  $rawJson=json_encode($rawFields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$_POST=['raw'=>$rawJson];
  check(flipbook_sync\admin_controller::input('raw',200000)===$rawJson,'raw admin JSON keeps nested quotes and backslashes intact');
  $_G['uid']=4294000002;$_G['adminid']=1;$_SERVER['REQUEST_METHOD']='POST';
  $_POST=['draft_action'=>'save','draftsubmit'=>'1','formhash'=>formhash(),'draftid'=>'0','targetid'=>'0','editor_key'=>bin2hex(random_bytes(16)),'editid'=>bin2hex(random_bytes(16)),'sequence'=>'1','revision'=>'0','payload'=>$rawJson];$_GET=$_POST+['pmod'=>'product','do'=>2];
  $rawSaved=flipbook_sync\service_draft_editor::save_request($_G['uid']);$rawDraft=flipbook_sync\service_draft_store::fetch($rawSaved['draftid'],$_G['uid']);
  check(json_decode($rawDraft['payload'],true,512,JSON_THROW_ON_ERROR)['fields']===$rawFields,'actual raw POST draft request preserves nested SKU and size-chart JSON');
  $_POST['payload']='{invalid';$_GET['payload']=$_POST['payload'];
  rejects(fn()=>flipbook_sync\service_draft_editor::save_request($_G['uid']),'malformed raw draft JSON is rejected');
  check(flipbook_sync\service_draft_store::fetch($rawSaved['draftid'],$_G['uid'])['payload']===$rawDraft['payload'],'malformed requests preserve the previously saved draft');
 }finally{$_POST=$postBefore;$_GET=$getBefore;$_SERVER['REQUEST_METHOD']=$requestBefore;$_G['uid']=$uidBefore;$_G['adminid']=$adminBefore;}
 $a=Auth::issue('Audit-Sync.Example.','fixture',"203.0.113.10\n2001:db8::10");$b=Auth::issue('other-sync.example');
 check($a['key']!==$b['key'] && preg_match('/^fbps_[0-9]+\.[a-f0-9]{64}$/D',$a['key']),'independent cryptographic 256-bit credentials');
 $stored=DB::fetch_first('SELECT * FROM %t WHERE clientid=%d',['flipbook_sync_key',$a['clientid']]);
 check($stored['domain']==='audit-sync.example' && !in_array($a['key'],$stored,true) && !array_key_exists('expires',$stored),'exact canonical domain, hash-only credential and no expiry column');
 check(Auth::authenticate('Bearer '.$a['key'],'https://audit-sync.example/','203.0.113.10')['clientid']==$a['clientid'],'valid service credential and allowed IP are accepted');
 check(Auth::authenticate('Bearer '.$a['key'],'audit-sync.example','2001:db8::10')['clientid']==$a['clientid'],'multiple IP addresses round trip and authenticate');
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'],'other-sync.example','203.0.113.10'),'another domain cannot use this credential');
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'],'audit-sync.example','203.0.113.11'),'unlisted server IP is rejected');
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'],'audit-sync.example','203.0.113.10','https://evil.example'),'mismatched browser Origin is rejected');
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'].'0','audit-sync.example','203.0.113.10'),'invalid credential is rejected');
 rejects(fn()=>Auth::issue('audit-sync.example'),'duplicate domain grants are rejected');
 foreach(['*.example.com','https://user:pass@example.com','example.com/path','example.com?key=x','bad..example.com'] as $domain) rejects(fn()=>Auth::domain($domain),'invalid domain '.$domain);
 DB::update('flipbook_sync_key',['enabled'=>0],['clientid'=>$a['clientid']]);
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'],'audit-sync.example','203.0.113.10'),'disabled credential stops working');
 $rotated=Auth::rotate($a['clientid']);DB::update('flipbook_sync_key',['enabled'=>1],['clientid'=>$a['clientid']]);
 rejects(fn()=>Auth::authenticate('Bearer '.$a['key'],'audit-sync.example','203.0.113.10'),'rotation invalidates the old credential');
 check(Auth::authenticate('Bearer '.$rotated['key'],'audit-sync.example','203.0.113.10')['clientid']==$a['clientid'],'rotated credential works without reassigning the domain');
 $fields=flipbook_sync\service_fields::catalog();flipbook_sync\service_fields::save(0,array_keys($fields));
 check(flipbook_sync\service_fields::allowed($a['clientid'])===array_keys($fields),'existing grants default to full field access');
 flipbook_sync\service_fields::save($a['clientid'],['name','pricevalue']);
 check(flipbook_sync\service_fields::allowed($a['clientid'])===['name','pricevalue'] && flipbook_sync\service_fields::allowed($b['clientid'])===['name','pricevalue'],'field permissions persist in one shared policy');
 check(flipbook_sync\service_fields::mode($a['clientid'])==='shared'&&flipbook_sync\service_fields::mode($b['clientid'])==='shared','all domain grants use shared field settings');
 flipbook_sync\service_fields::save(0,['summary']);check(flipbook_sync\service_fields::allowed($b['clientid'])===['summary']&&flipbook_sync\service_fields::allowed($a['clientid'])===['summary'],'shared changes apply to every domain');
 check(flipbook_sync\service_fields::allowed($a['clientid'])===['summary']&&flipbook_sync\service_fields::mode($a['clientid'])==='shared','shared mode has no separate per-domain override');
 $open=flipbook_sync\service_fields::local_enabled();flipbook_sync\service_fields::local_toggle();check(flipbook_sync\service_fields::local_enabled()!==$open,'local source close/open state is stored independently of domain credentials');flipbook_sync\service_fields::local_toggle();check(flipbook_sync\service_fields::local_enabled()===$open,'local source toggle restores its prior availability');
 flipbook_sync\service_fields::save($a['clientid'],[]);
 check(flipbook_sync\service_fields::allowed($a['clientid'])===[],'all fields off persists without reverting to default access');
 rejects(fn()=>flipbook_sync\service_fields::save($a['clientid'],['keyhash']),'unknown or private fields cannot be granted');
 check(flipbook_sync\service_fields::allowed($a['clientid'])===[],'invalid permission updates preserve the saved policy');
 $cipher=Store::encrypt($a['key']);check(Store::decrypt($cipher)===$a['key'] && strpos($cipher,$a['key'])===false,'receiver credential is authenticated-encrypted at rest');
 rejects(fn()=>Store::decrypt(substr($cipher,0,-4).'aaaa'),'tampered encrypted credential is rejected');
 foreach(['http://example.com','https://user:pass@example.com','https://example.com/?key=x','https://example.com/../api'] as $url) rejects(fn()=>Client::endpoint($url),'unsafe remote endpoint is rejected');
 foreach(['127.0.0.1','10.0.0.1','169.254.169.254'] as $ip) rejects(fn()=>Client::address($ip),'private or local network is rejected');
} finally {DB::query('ROLLBACK');}

$page=Export::page(0,1);check(count($page['items'])===1,'real catalog uses bounded keyset pagination');
$item=$page['items'][0];
check(isset($item['skus'],$item['media'],$item['spec']['sizeChart'],$item['category_path']) && array_key_exists('supplier',$item) && array_key_exists('showcase',$item),'complete product contract includes SKU, images, size chart and relationships');
check(!isset($item['supplier']['phone'],$item['supplier']['contact'],$item['supplier']['address']),'supplier personal contact details are not exported');
foreach(['cover','video'] as $key) check($item[$key]==='' || preg_match('#^https?://#',$item[$key]),$key.' is an absolute media address');
$catalog=flipbook_sync\service_fields::catalog();
$filtered=flipbook_sync\service_fields::project($item,['name']);
check(array_keys($filtered)===['productid','name','checksum'],'restricted products contain only explicitly permitted business fields');
$changedPrivate=$item;$changedPrivate['pricevalue']='123.45';
check(flipbook_sync\service_fields::project($changedPrivate,['name'])['checksum']===$filtered['checksum'],'checksums reveal no changes to denied fields');
$noFields=flipbook_sync\service_fields::project($item,[]);
check(array_keys($noFields)===['productid','checksum'],'all-off policy exports only the required product identity and projected checksum');
check(array_keys(Export::page(0,1,null,['name'])['items'][0])===['productid','name','checksum'],'paginated exports apply the same field projection');
$noContent=flipbook_sync\service_fields::project($item,array_values(array_diff(array_keys($catalog),['spec.content','spec.sizeChart'])));
check(!isset($noContent['spec']['content'],$noContent['spec']['contentHtml'],$noContent['spec']['sizeChart'],$noContent['spec']['sizetable'],$noContent['spec']['sizeTableNote']),'disabled content and size chart remove their derived representations too');
foreach($catalog as $path=>$label) {
 $projected=flipbook_sync\service_fields::project($item,array_values(array_diff(array_keys($catalog),[$path])));
 $parts=explode('.',$path);$value=$projected;$present=true;
 foreach($parts as $part) {
  if(is_array($value) && array_is_list($value) && $value) $value=$value[0];
  if(!is_array($value) || !array_key_exists($part,$value)) {$present=false;break;}
  $value=$value[$part];
 }
 check(!$present,'individual field switch denies '.$path);
}
$second=Export::page($page['next_cursor'],1,$page['upper_bound']);
check(!$second['items'] || $second['items'][0]['productid']>$item['productid'],'the next page does not repeat products');
check(Export::page(0,0)['limit']===1 && Export::page(0,500)['limit']===100,'pagination limits are bounded');
check(strpos(Export::link('product:1'),'key=detail&productid=1')!==false && strpos(Export::link('page:catalog'),'key=catalog')!==false,'internal source links become absolute rather than pointing at unrelated local IDs');
Importer::validate_page(['cursorid'=>0,'upperid'=>$page['upper_bound']],$page);
rejects(fn()=>Importer::validate_page(['cursorid'=>$page['next_cursor'],'upperid'=>$page['upper_bound']],$page),'repeated or backward cursors are rejected');

$tag='SYNC'.strtoupper(bin2hex(random_bytes(4)));$source=0;$native=0;
try {
 $source=DB::insert('flipbook_sync_source',['name'=>$tag,'endpoint'=>'https://'.strtolower($tag).'.example/','domain'=>'audit-sync.example','keycipher'=>Store::encrypt('fixture'),'enabled'=>1,'instanceid'=>str_repeat('a',32),'lastsync'=>0],true);
 $remote=$item;$remote['productid']=900001;$remote['name']='同步验收 '.$tag;$remote['code']=$tag.'-SPU';
 foreach($remote['skus'] as $i=>&$sku) {$sku['code']=$tag.'-SKU-'.$i;$sku['available']=$i===0?0:1;}unset($sku);
 check(Importer::apply($source,$remote)==='added','first real import creates a mapped product');
 $map=Store::mapping($source,'product',900001);$local=intval($map['localid']);$product=flipbook\table_flipbook_product::t()->fetch($local);
 check($local!==intval($item['productid']) && $product['code']===$remote['code'],'source IDs are never used as local IDs');
 check(Importer::apply($source,$remote)==='skipped','repeated full import is idempotent');
 $skus=flipbook\front_sku::rows($local);
 check(count($skus)===count($remote['skus']) && (!$skus || !$skus[0]['available']),'SKU content and disabled states are retained');
 check(flipbook\admin_sizechart::from_spec($product['spec'])===$remote['spec']['sizeChart'],'the size chart round trips through the existing product model');
 check(count(flipbook\table_flipbook_product_media::t()->fetch_all_by_product($local))===count($remote['media']),'all product media groups import');
 $remote['summary']='同步更新 '.$tag;
 check(Importer::apply($source,$remote)==='changed' && flipbook\table_flipbook_product::t()->fetch($local)['summary']===$remote['summary'],'changed source data updates the same local product');
 $beforePartial=flipbook\table_flipbook_product::t()->fetch($local);
 $partial=['productid'=>900001,'summary'=>'partial '.$tag];
 check(Importer::apply($source,$partial)==='changed','a partial field grant can update an existing mapped product');
 $afterPartial=flipbook\table_flipbook_product::t()->fetch($local);
 check($afterPartial['summary']===$partial['summary'] && $afterPartial['name']===$beforePartial['name'] && $afterPartial['pricevalue']===$beforePartial['pricevalue'] && $afterPartial['spec']===$beforePartial['spec'],'missing product and specification fields keep local values');
 check(count(flipbook\front_sku::rows($local))===count($remote['skus']) && count(flipbook\table_flipbook_product_media::t()->fetch_all_by_product($local))===count($remote['media']),'omitted SKU and media permissions never clear local collections');
 if($remote['skus']) {
  $skuPatch=['productid'=>900001,'skus'=>array_map(fn($sku)=>['skuid'=>$sku['skuid'],'productid'=>900001,'name'=>'partial SKU '.$tag],$remote['skus'])];
  Importer::apply($source,$skuPatch);$partialSkus=flipbook\front_sku::rows($local);
  check($partialSkus[0]['name']==='partial SKU '.$tag && $partialSkus[0]['code']===$remote['skus'][0]['code'] && $partialSkus[0]['images']===$remote['skus'][0]['images'],'SKU field permissions preserve denied codes and images through source mappings');
 }
 if($remote['media']) {
  $mediaPatch=['productid'=>900001,'media'=>array_map(fn($media)=>['mediaid'=>$media['mediaid'],'productid'=>900001,'title'=>'partial media '.$tag],$remote['media'])];
  Importer::apply($source,$mediaPatch);$partialMedia=flipbook\table_flipbook_product_media::t()->fetch_all_by_product($local);
  check($partialMedia[0]['title']==='partial media '.$tag && $partialMedia[0]['image']===$remote['media'][0]['image'],'media field permissions preserve denied image URLs through source mappings');
 }
 $remote['available']=0;Importer::apply($source,$remote);
 check(!flipbook\table_flipbook_product::t()->fetch($local)['available'],'unpublished source product becomes unpublished locally');
 check(Importer::apply($source,$remote,null,true)==='changed' && flipbook\table_flipbook_product::t()->fetch($local)['available']==1,'one-click publication synchronizes and publishes a source product');
 check(Importer::apply($source,$remote,null,true)==='skipped' && intval(Store::mapping($source,'product',900001)['localid'])===$local,'repeated one-click publication is idempotent and creates no duplicate');
 Importer::apply($source,$remote);
 check(!flipbook\table_flipbook_product::t()->fetch($local)['available'],'ordinary sync can still apply source unpublication after an explicit local publication');
 $stable=snapshot();$bad=$remote;$bad['pricevalue']='invalid';
 if($bad['supplier'])$bad['supplier']['name']='must-roll-back';
 rejects(fn()=>Importer::apply($source,$bad),'invalid product rolls back its relationship updates');
 check(snapshot()===$stable,'failed import leaves every business row unchanged');
 $jobid=bin2hex(random_bytes(16));DB::insert('flipbook_sync_job',['jobid'=>$jobid,'sourceid'=>$source,'status'=>'running','created'=>time(),'updated'=>time(),'message'=>'']);
 $bad=$remote;$bad['summary']='progress transaction fixture';
 rejects(function() use ($source,$bad,$jobid){Importer::apply($source,$bad,function() use ($jobid){DB::update('flipbook_sync_job',['processed'=>1],['jobid'=>$jobid]);throw new RuntimeException('fixture progress write failure');});},'progress failure rolls back the same product transaction');
 check(snapshot()===$stable && intval(DB::result_first('SELECT processed FROM %t WHERE jobid=%s',['flipbook_sync_job',$jobid]))===0,'product data and saved progress remain atomic');
 $nativeData=$product;unset($nativeData['productid']);$nativeData['code']=$tag.'-NATIVE';$native=DB::insert('flipbook_product',$nativeData,true);
 $bad=$remote;$bad['productid']=900002;$bad['code']=$tag.'-NATIVE';
 rejects(fn()=>Importer::apply($source,$bad),'SPU conflict never overwrites a self-created product');
 check(flipbook\table_flipbook_product::t()->fetch($native)['code']===$tag.'-NATIVE' && !Store::mapping($source,'product',900002),'conflict preserves the native product and creates no source mapping');
 if($remote['skus']) {
  DB::insert('flipbook_sku',['productid'=>$native,'code'=>$tag.'-NATIVE-SKU','name'=>'native fixture','color'=>'','size'=>'','images'=>'[]','displayorder'=>0,'available'=>1]);
  $bad=$remote;$bad['summary']='must roll back';$bad['skus'][0]['code']=$tag.'-NATIVE-SKU';$stable=snapshot();
  rejects(fn()=>Importer::apply($source,$bad),'SKU conflicts roll back the current product');
  check(snapshot()===$stable,'SKU failure preserves the original product and media');
 }

 $bulk=$item;$bulk['category_path']=[['catid'=>931001,'parentid'=>0,'name'=>$tag.'批量分类','displayorder'=>0,'available'=>1]];$bulk['series']=['seriesid'=>931002,'name'=>$tag.'批量系列','description'=>'','cover'=>'','displayorder'=>0,'available'=>1];$bulk['supplier']=['supplierid'=>931003,'name'=>$tag.'批量供应商','code'=>$tag.'-BULK-SUP','displayorder'=>0,'available'=>1];
 foreach(['category'=>931001,'series'=>931002,'supplier'=>931003] as $kind=>$remoteId)flipbook_sync\service_match::save_choice($source,$kind,$remoteId,'new');
 $versions=[];$bulkBefore=snapshot();
 for($i=0;$i<2;$i++){$copy=$bulk;$copy['productid']=900010+$i;$copy['name']=$tag.'批量'.$i;$copy['code']=$tag.'-BULK-'.$i;foreach($copy['skus'] as &$sku)$sku['code'].='-BULK-'.$i;unset($sku);$draft=flipbook_sync\service_draft_publish::receive(4294000002,$source,'product',$copy);$versions[$draft['draftid']]=$draft['revision'];}
 check(snapshot()===$bulkBefore,'receiving two products with shared pending relations leaves production untouched');
 $bulkIds=flipbook_sync\service_draft_publish::publish(4294000002,$versions);
 $bulkCategory=flipbook_sync\service_draft_publish::target($source,'category',931001);$bulkValid=count($bulkIds)===2;
 foreach($bulkIds as $bulkId)$bulkValid=$bulkValid&&intval(flipbook\table_flipbook_product::t()->fetch($bulkId)['available'])===1&&intval(flipbook\admin_product::load($bulkId)['product_category'])===$bulkCategory;
 check($bulkValid&&$bulkCategory>0,'bulk publish reuses the newly committed relation mapping for every selected product');
 check(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND sourceid=%d',['flipbook_sync_draft',4294000002,$source]))===0,'successful bulk publish removes both product drafts and shared dependency drafts');
} finally {
 if($source) {
  $maps=DB::fetch_all('SELECT * FROM %t WHERE sourceid=%d',['flipbook_sync_map',$source]);
  foreach($maps as $map) if($map['kind']==='product') {
   $ownedPages=DB::fetch_all('SELECT p.pageid,p.albumid FROM %t p JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d',['flipbook_page','flipbook_page_product',$map['localid']]);foreach($ownedPages as $page){DB::delete('flipbook_page_product',['pageid'=>$page['pageid']]);DB::delete('flipbook_page',['pageid'=>$page['pageid']]);DB::delete('flipbook_album',['albumid'=>$page['albumid']]);}
   DB::delete('flipbook_sku',['productid'=>$map['localid']]);DB::delete('flipbook_product_media',['productid'=>$map['localid']]);DB::delete('flipbook_page_product',['productid'=>$map['localid']]);DB::delete('flipbook_product',['productid'=>$map['localid']]);
  }
  $tables=['album'=>['flipbook_album','albumid'],'page'=>['flipbook_page','pageid'],'category'=>['flipbook_category','catid'],'series'=>['flipbook_series','seriesid'],'supplier'=>['flipbook_supplier','supplierid']];
  foreach($maps as $map) if(isset($tables[$map['kind']])) {[$table,$pk]=$tables[$map['kind']];DB::delete($table,[$pk=>$map['localid']]);}
  DB::delete('flipbook_sync_draft',['uid'=>4294000002,'sourceid'=>$source]);DB::query('DELETE FROM %t WHERE skey LIKE %s OR skey LIKE %s',['flipbook_sync_setting','choice:'.$source.':%','mapping:'.$source.':%']);
  DB::delete('flipbook_sync_job',['sourceid'=>$source]);DB::delete('flipbook_sync_map',['sourceid'=>$source]);DB::delete('flipbook_sync_source',['sourceid'=>$source]);
 }
 if($native) {DB::delete('flipbook_sku',['productid'=>$native]);DB::delete('flipbook_product',['productid'=>$native]);}
}
check(snapshot()===$before,'all original products, configuration, member bindings and relationships remain unchanged');
echo 'TOTAL '.$checks." checks\n";
