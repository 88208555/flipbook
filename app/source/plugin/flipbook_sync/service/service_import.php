<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Only objects created for this source are eligible for replacement. */
class service_import {
 public static function text($value,$max,$label) {
  if(!is_scalar($value) || is_bool($value) || mb_strlen((string)$value,'UTF-8')>$max) throw new \RuntimeException($label.'格式或长度无效');
  return trim(strip_tags((string)$value));
 }
 public static function image($value) {
  if($value==='') return '';
  if(!is_string($value) || strlen($value)>255 || !preg_match('#^https?://#i',$value) || !\flipbook\admin_helper::cleanurl($value)) throw new \RuntimeException('图片或视频地址无效或超出本站字段长度');
  return $value;
 }
 private static function number($value,$label,$max=4294967295) {
  if(!is_scalar($value) || !preg_match('/^[0-9]+$/D',(string)$value) || $value>$max) throw new \RuntimeException($label.'无效');
  return intval($value);
 }
 private static function record($source,$kind,$remote,$table,$pk,$values) {
  $map=service_store::mapping($source,$kind,$remote);
  $id=$map?intval($map['localid']):0;
  if($id && !DB::result_first('SELECT '.$pk.' FROM %t WHERE '.$pk.'=%d',[$table,$id])) $id=0;
  if($id) DB::update($table,$values,[$pk=>$id]);
  else $id=DB::insert($table,$values,true);
  service_store::remember($source,$kind,$remote,$id);
  return $id;
 }
 private static function related($source,$item,$existing,$album) {
  $parent=intval($album['catid']??0);$remoteParent=0;
  if(!array_key_exists('category_path',$item)) $categories=null;else {$categories=$item['category_path'];$parent=0;}
  if($categories!==null && (!is_array($categories) || count($categories)>30)) throw new \RuntimeException('商品分类层级无效');
  foreach($categories??[] as $category) {
   $category=service_fields::related_patch($source,'category',$category,'flipbook_category','catid');
   $remote=self::number($category['catid']??'','分类编号');
   if(!$remote || self::number($category['parentid']??'','上级分类')!==$remoteParent) throw new \RuntimeException('商品分类关系无效');
   $parent=self::record($source,'category',$remote,'flipbook_category','catid',['name'=>self::text($category['name']??'',100,'分类名称'),'parentid'=>$parent,'displayorder'=>intval($category['displayorder']??0),'available'=>empty($category['available'])?0:1]);
   $remoteParent=$remote;
  }
  $series=array_key_exists('series',$item)?0:intval($album['seriesid']??0);$supplier=array_key_exists('supplier',$item)?0:intval($existing['supplierid']??0);
  if(!empty($item['series'])) {
   $r=service_fields::related_patch($source,'series',$item['series'],'flipbook_series','seriesid');$id=self::number($r['seriesid']??'','系列编号');if(!$id) throw new \RuntimeException('系列编号无效');
   $series=self::record($source,'series',$id,'flipbook_series','seriesid',['name'=>self::text($r['name']??'',100,'系列名称'),'description'=>self::text($r['description']??'',255,'系列说明'),'cover'=>self::image($r['cover']??''),'displayorder'=>intval($r['displayorder']??0),'available'=>empty($r['available'])?0:1,'dateline'=>time()]);
  }
  if(!empty($item['supplier'])) {
   $r=service_fields::related_patch($source,'supplier',$item['supplier'],'flipbook_supplier','supplierid');$id=self::number($r['supplierid']??'','供应商编号');if(!$id) throw new \RuntimeException('供应商编号无效');
   $supplier=self::record($source,'supplier',$id,'flipbook_supplier','supplierid',['name'=>self::text($r['name']??'',150,'供应商名称'),'code'=>self::text($r['code']??'',60,'供应商编码'),'displayorder'=>intval($r['displayorder']??0),'available'=>empty($r['available'])?0:1]);
  }
  return [$parent,$series,$supplier];
 }
 private static function product_values($item,$supplier) {
  $values=[];
  foreach(['name'=>150,'code'=>60,'summary'=>255,'description'=>3000,'currency'=>8] as $key=>$max) $values[$key]=self::text($item[$key]??'',$max,$key);
  if($values['name']==='') throw new \RuntimeException('商品名称不能为空');
  foreach(['pricevalue','priceorigin'] as $key) {
   $v=$item[$key]??'0.00';
   if(!is_scalar($v) || !preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D',(string)$v)) throw new \RuntimeException('商品价格格式无效');
   $values[$key]=(string)$v;
  }
  foreach(['cover','video'] as $key) $values[$key]=self::image($item[$key]??'');
  if(!is_array($item['spec']??null)) throw new \RuntimeException('商品规格格式无效');
  $raw=json_encode($item['spec'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  if(strlen($raw)>200000) throw new \RuntimeException('商品规格过大');
  $spec=\flipbook\front_helper::spec($raw);
  $spec['sizeChart']=\flipbook\admin_sizechart::parse(json_encode($item['spec']['sizeChart']??\flipbook\admin_sizechart::from_spec($raw),JSON_UNESCAPED_UNICODE));
  $spec['floatingImage']['src']=self::image($spec['floatingImage']['src']??'');
  $values['spec']=json_encode($spec,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
  $values+=['supplierid'=>$supplier,'available'=>empty($item['available'])?0:1,'priceshow'=>empty($item['priceshow'])?0:1,'displayorder'=>max(-32768,min(32767,intval($item['displayorder']??0))),'dateline'=>time()];
  return $values;
 }
 private static function skus($source,$product,$items) {
  if(!is_array($items) || count($items)>100) throw new \RuntimeException('SKU 列表无效');
  $existing=[];
  foreach(\flipbook\front_sku::rows($product) as $row) $existing[$row['code']]=intval($row['skuid']);
  $values=[];
  foreach($items as $item) {
   if(!is_array($item) || (isset($item['images']) && !is_array($item['images']))) throw new \RuntimeException('SKU 图片列表无效');
   foreach($item['images']??[] as $image) self::image($image);
   $item['skuid']=$existing[$item['code']??'']??0;$item['images']=$item['images']??[];$values[]=$item;
  }
  \flipbook\front_sku::save($product,json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
  foreach($values as $value) {
   if(empty($value['available'])) DB::update('flipbook_sku',['available'=>0],['productid'=>$product,'code'=>$value['code']]);
   if(!empty($value['_remoteid'])) { $id=DB::result_first('SELECT skuid FROM %t WHERE productid=%d AND code=%s',['flipbook_sku',$product,$value['code']]);service_store::remember($source,'sku',$value['_remoteid'],$id);}
  }
 }
 private static function showcase($source,$remote,$product,$item,$category,$series,$previous=[]) {
  $row=$item['showcase']??[];
  if(!is_array($row)) $row=[];
  $row+=$previous?:[];
  $album=self::record($source,'album',$remote,'flipbook_album','albumid',['name'=>self::text($row['name']??$item['name'],100,'首页名称'),'subtitle'=>self::text($row['subtitle']??$item['summary'],255,'首页说明'),'description'=>self::text($row['description']??$item['description'],3000,'首页内容'),'cover'=>self::image($row['cover']??$item['cover']),'video'=>self::image($row['video']??$item['video']),'catid'=>$category,'seriesid'=>$series,'pagecount'=>1,'displayorder'=>max(-32768,min(32767,intval($item['displayorder']??0))),'available'=>empty($item['available'])?0:1,'dateline'=>time()]);
  $page=self::record($source,'page',$remote,'flipbook_page','pageid',['albumid'=>$album,'title'=>self::text($item['name'],150,'商品名称'),'description'=>self::text($item['summary'],255,'商品说明'),'image'=>self::image($row['cover']??$item['cover']),'imagecfg'=>'{}','displayorder'=>0,'available'=>empty($item['available'])?0:1,'dateline'=>time()]);
  DB::query('INSERT IGNORE INTO %t (pageid,productid,displayorder) VALUES (%d,%d,0)',['flipbook_page_product',$page,$product]);
 }
 public static function apply($source,$item,$progress=null,$publish=false) {
  if(!is_array($item)) throw new \RuntimeException('商品数据格式无效');
  $remote=self::number($item['productid']??'','商品编号');if(!$remote) throw new \RuntimeException('商品编号无效');
  unset($item['checksum']);$received=$item;$hashItem=$item;if($publish)$hashItem['_publish']=true;$checksum=hash('sha256',json_encode($hashItem,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  DB::query('START TRANSACTION');
  try {
   $map=service_store::mapping($source,'product',$remote);$id=$map?intval($map['localid']):0;
   $existing=$id?\flipbook\table_flipbook_product::t()->fetch($id):null;
   if($existing && (!$publish || intval($existing['available'])===1) && hash_equals($map['checksum'],$checksum)) {if($progress)$progress('skipped');DB::query('COMMIT');return 'skipped';}
   $item=service_fields::product_patch($item,$existing);if($publish)$item['available']=1;
   $albumMap=service_store::mapping($source,'album',$remote);$album=$albumMap?DB::fetch_first('SELECT * FROM %t WHERE albumid=%d',['flipbook_album',$albumMap['localid']]):[];
   $code=self::text($item['code']??'',60,'商品编码');
   if($code!=='' && DB::result_first('SELECT productid FROM %t WHERE code=%s AND productid<>%d',['flipbook_product',$code,$existing?$id:0])) throw new \RuntimeException('商品编码 '.$code.' 与本站其他商品冲突，未覆盖');
   [$category,$series,$supplier]=self::related($source,$received,$existing,$album);$values=self::product_values($item,$supplier);
   if($existing) \flipbook\table_flipbook_product::t()->update($id,$values);
   else $id=\flipbook\table_flipbook_product::t()->insert($values,true);
   if(array_key_exists('skus',$received)) self::skus($source,$id,service_fields::collection_patch($source,'sku',$received['skus'],\flipbook\front_sku::rows($id),'code'));
   if(array_key_exists('media',$received)) {
    $mediaRows=service_fields::collection_patch($source,'media',$received['media'],\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($id),'image');
    if(count($mediaRows)>300) throw new \RuntimeException('商品图组超过 300 张');
    \flipbook\table_flipbook_product_media::t()->delete_by_product($id);
    foreach($mediaRows as $media) {
    $type=$media['type']??'detail';
    if(!isset(\flipbook\table_flipbook_product_media::$types[$type])) throw new \RuntimeException('商品图组类型无效');
    $mediaid=DB::insert('flipbook_product_media',['productid'=>$id,'type'=>$type,'title'=>self::text($media['title']??'',150,'图片标题'),'image'=>self::image($media['image']??''),'displayorder'=>intval($media['displayorder']??0)],true);
    if(!empty($media['_remoteid'])) service_store::remember($source,'media',$media['_remoteid'],$mediaid);
    }
   }
   self::showcase($source,$remote,$id,$item,$category,$series,$album);
   service_store::remember($source,'product',$remote,$id,$checksum);
   $kind=$existing?'changed':'added';if($progress)$progress($kind);
   DB::query('COMMIT');return $kind;
  } catch(\Throwable $e) {DB::query('ROLLBACK');throw $e;}
 }
 public static function lock($source) {
  if(!DB::result_first('SELECT GET_LOCK(%s,0)',['flipbook-sync-'.$source])) throw new \RuntimeException('此数据源正在同步，请稍后再试');
 }
 public static function unlock($source) {DB::query('SELECT RELEASE_LOCK(%s)',['flipbook-sync-'.$source]);}
 public static function start($sourceid) {
  $source=service_store::source($sourceid);self::lock($sourceid);
  try {
   $info=service_client::request($source,['action'=>'info']);
   $job=DB::fetch_first('SELECT * FROM %t WHERE sourceid=%d AND status IN (%s,%s,%s) ORDER BY created DESC LIMIT 1',['flipbook_sync_job',$sourceid,'running','failed','paused']);
   if($job) {global $_G;$owner=intval(DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','owner:'.$job['jobid']]));if($owner && $owner!==intval($_G['uid']))throw new \RuntimeException('该来源的同步由另一管理员发起，请由原管理员继续');if(!$owner)DB::query('INSERT INTO %t (skey,svalue) VALUES (%s,%s)',['flipbook_sync_setting','owner:'.$job['jobid'],(string)intval($_G['uid'])]);DB::update('flipbook_sync_job',['status'=>'running','message'=>'','updated'=>time()],['jobid'=>$job['jobid']]);$job['status']='running';return $job;}
   $job=['jobid'=>bin2hex(random_bytes(16)),'sourceid'=>$sourceid,'status'=>'running','cursorid'=>0,'upperid'=>self::number($info['upper']??'','来源上界'),'total'=>self::number($info['total']??'','来源数量'),'processed'=>0,'added'=>0,'changed'=>0,'skipped'=>0,'message'=>'','created'=>time(),'updated'=>time()];
   DB::update('flipbook_sync_source',['instanceid'=>$info['instanceid']],['sourceid'=>$sourceid]);
   DB::insert('flipbook_sync_job',$job);global $_G;DB::insert('flipbook_sync_setting',['skey'=>'owner:'.$job['jobid'],'svalue'=>(string)intval($_G['uid'])]);return $job;
  } finally {self::unlock($sourceid);}
 }
 public static function step($jobid) {
  if(!is_string($jobid) || !preg_match('/^[a-f0-9]{32}$/D',$jobid)) throw new \RuntimeException('同步任务无效');
  $job=DB::fetch_first('SELECT * FROM %t WHERE jobid=%s',['flipbook_sync_job',$jobid]);
  if(!$job) throw new \RuntimeException('同步任务不存在');
  $sourceid=intval($job['sourceid']);self::lock($sourceid);
  try {
   $job=DB::fetch_first('SELECT * FROM %t WHERE jobid=%s',['flipbook_sync_job',$jobid]);
   if($job['status']!=='running') return $job;
   $source=service_store::source($sourceid);global $_G;$owner=intval(DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','owner:'.$jobid]));if($owner!==intval($_G['uid']))throw new \RuntimeException('仅发起同步的管理员可继续此任务');
   $data=service_client::request($source,['action'=>'products','cursor'=>$job['cursorid'],'upper'=>$job['upperid'],'limit'=>10]);
   self::validate_page($job,$data);
   foreach($data['items'] as $item) {
    DB::query('START TRANSACTION');
    try{
     $draft=service_draft_publish::receive($owner,$sourceid,'product',$item);
     $counter=empty($draft['retained'])?'added':'skipped';$job[$counter]++;$job['processed']++;$job['cursorid']=intval($item['productid']);$job['updated']=time();
     DB::update('flipbook_sync_job',array_intersect_key($job,array_flip(['added','skipped','processed','cursorid','updated'])),['jobid'=>$jobid]);DB::query('COMMIT');
    }catch(\Throwable $e){DB::query('ROLLBACK');throw $e;}
   }
   if(!$data['has_more']) {
    $job['status']='complete';$job['message']='已同步至草稿箱';$job['updated']=time();
    DB::update('flipbook_sync_job',['status'=>'complete','message'=>'已同步至草稿箱','updated'=>time()],['jobid'=>$jobid]);
    DB::update('flipbook_sync_source',['lastsync'=>time()],['sourceid'=>$sourceid]);
    service_store::log($sourceid,'complete','同步完成：新增 '.$job['added'].'，更新 '.$job['changed'].'，未变化 '.$job['skipped']);
   }
   return $job;
  } catch(\Throwable $e) {
   DB::update('flipbook_sync_job',['status'=>'failed','message'=>mb_substr($e->getMessage(),0,1000),'updated'=>time()],['jobid'=>$jobid]);
   service_store::log($sourceid,'failed',mb_substr($e->getMessage(),0,1000));throw $e;
  } finally {self::unlock($sourceid);}
 }
 public static function validate_page($job,$data) {
  if(!is_array($data['items']??null) || count($data['items'])>10 || !is_bool($data['has_more']??null) || intval($data['upper_bound']??-1)!==intval($job['upperid'])) throw new \RuntimeException('来源分页数据无效');
  $cursor=intval($job['cursorid']);
  foreach($data['items'] as $item) {
   $id=self::number($item['productid']??'','来源商品编号');
   if($id<=$cursor || $id>intval($job['upperid'])) throw new \RuntimeException('来源分页顺序无效');
   $cursor=$id;
  }
  if(intval($data['next_cursor']??-1)!==$cursor || (!$data['items'] && $data['has_more'])) throw new \RuntimeException('来源分页游标未前进');
 }
}
