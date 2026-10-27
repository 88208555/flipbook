<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Receive into independent drafts; commit through the existing native editor only. */
class service_draft_publish {
 public static function target($source,$kind,$remote) {
  $def=service_fields::entities()[$kind];$map=service_store::mapping($source,$kind,$remote);
  return $map && DB::result_first('SELECT '.$def['pk'].' FROM %t WHERE '.$def['pk'].'=%d',[$def['table'],$map['localid']])?intval($map['localid']):0;
 }
 public static function token($kind,$remote) {return 'source:'.$kind.':'.intval($remote);}
 private static function relation_fields($kind,$item,$matches) {
  $out=[];
  foreach($matches as $match){
   $field=$match['kind']==='category'?($kind==='product'?'product_category':'parentid'):($match['kind']==='series'?'product_series':'supplierid');
   if($kind!=='product' && ($match['kind']!=='category'||intval($item['catid'])===intval($match['remoteid'])))continue;
   $out[$field]=$match['status']==='new'?self::token($match['kind'],$match['remoteid']):(string)$match['targetid'];
  }
  return $out;
 }
 public static function receive($uid,$source,$kind,$raw) {
  $check=service_match::require_ready($source,$kind,$raw);$item=$check['item'];$def=service_fields::entities()[$kind];$remote=intval($item[$def['pk']]??0);
  if(!$remote)throw new \RuntimeException('来源编号无效');
  $target=self::target($source,$kind,$remote);
  if($kind!=='product')foreach($check['relations'] as $r)if($r['kind']===$kind&&$r['remoteid']===$remote)$target=$r['targetid'];
  $fields=self::relation_fields($kind,$item,$check['relations'])+service_draft_editor::snapshot_fields($kind,$item,$source,$target);
  $payload=['snapshot'=>$raw,'fields'=>$fields,'editor_key'=>bin2hex(random_bytes(16))];
  $row=service_draft_store::receive($uid,$kind,$source,$remote,$payload,$target);
  if($kind==='product'){
   foreach($item['category_path']??[] as $category){$category['category_path']=array_slice($item['category_path'],0,array_search($category,$item['category_path'],true)+1);self::receive($uid,$source,'category',$category);}
   foreach(['series','supplier'] as $type)if(!empty($item[$type]))self::receive($uid,$source,$type,$item[$type]);
  }
  if($kind==='category'&&count($item['category_path']??[])>1){$path=array_slice($item['category_path'],0,-1);$parent=$path[array_key_last($path)];$parent['category_path']=$path;self::receive($uid,$source,'category',$parent);}
  return $row;
 }
 public static function pending_options($draft) {
  if($draft['origin']!=='external')return [];
  $payload=json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR);$check=service_match::inspect($draft['sourceid'],$draft['kind'],$payload['snapshot']);$out=[];
  foreach($check['relations'] as $r)if($r['status']==='new'){
   if($draft['kind']!=='product'&&($r['kind']!=='category'||intval($r['remoteid'])===intval($draft['remoteid'])))continue;
   $field=$r['kind']==='category'?($draft['kind']==='product'?'product_category':'parentid'):($r['kind']==='series'?'product_series':'supplierid');
   $out[$field][self::token($r['kind'],$r['remoteid'])]='待新建 · '.$r['name'];
  }
  return $out;
 }
 public static function validate_fields($kind,$fields) {
  $config=service_draft_editor::config($kind);
  if($kind==='product')foreach(['medialines'=>5000,'overlay_lines'=>10000,'spec_fields'=>10000,'spec_content'=>10000,'spec_colors'=>500,'spec_sizes'=>500,'spec_model'=>255,'spec_fit'=>255,'showcase_name'=>100,'showcase_subtitle'=>255,'showcase_description'=>3000,'supplier'=>255] as $field=>$limit)if(mb_strlen((string)($fields[$field]??''))>$limit)throw new \RuntimeException('字段 '.$field.' 超过原生长度限制，未截断或上架');
  foreach($config['schema'] as $name=>$schema){
   $value=$fields[$name]??($schema['default']??'');
   if(!is_scalar($value)||(!empty($schema['required'])&&trim((string)$value)==='')||(isset($schema['maxlen'])&&mb_strlen((string)$value)>$schema['maxlen']))throw new \RuntimeException($schema['title'].'未填写或长度无效');
   if($schema['type']==='int'&&(!preg_match('/^-?[0-9]+$/D',(string)$value)||(float)$value<($kind==='supplier'?-2147483648:-32768)||(float)$value>($kind==='supplier'?2147483647:32767)))throw new \RuntimeException($schema['title'].'超出原生整数范围');
   if($schema['type']==='amount'&&!preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D',(string)$value))throw new \RuntimeException($schema['title'].'格式无效');
   if($schema['type']==='select'&&intval($value)>0&&!isset(\flipbook\admin_crud::source($schema['source'])[intval($value)]))throw new \RuntimeException($schema['title'].'所选记录不存在');
  }
  if($kind==='product'){
   \flipbook\admin_sizechart::parse($fields['sizechart']??'{}');
   if(!is_array(json_decode($fields['skulines']??'[]',true)))throw new \RuntimeException('SKU 配置无效');
   foreach(json_decode($fields['skulines']??'[]',true) as $sku)foreach(['code'=>80,'name'=>100,'color'=>60,'size'=>60] as $key=>$max)if(mb_strlen((string)($sku[$key]??''))>$max)throw new \RuntimeException('SKU '.$key.' 超过原生长度限制');
   foreach(['product_category'=>'category','product_series'=>'series'] as $field=>$source)if(intval($fields[$field]??0)>0&&!isset(\flipbook\admin_crud::source($source)[intval($fields[$field])]))throw new \RuntimeException('分类或系列不存在');
  }
 }
 private static function dependency($uid,$source,$kind,$remote) {
  $key=hash('sha256',$uid.':external:'.$kind.':'.$source.':'.$remote);
  $draft=DB::fetch_first('SELECT * FROM %t WHERE dkey=%s FOR UPDATE',['flipbook_sync_draft',$key]);
  if(!$draft)throw new \RuntimeException('关联草稿不存在，请重新从来源列表加入草稿');
  return self::commit($uid,$draft);
 }
 private static function resolve($uid,$draft,$fields) {
  $source=intval($draft['sourceid']);$kind=$draft['kind'];
  $types=$kind==='product'?['supplierid'=>'supplier','product_category'=>'category','product_series'=>'series']:($kind==='category'?['parentid'=>'category']:[]);
  $snapshot=json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR)['snapshot']??[];
  foreach($types as $field=>$type){
   $value=(string)($fields[$field]??'0');
   if(str_starts_with($value,'source:')){
    if(!preg_match('/^source:'.$type.':([1-9][0-9]*)$/D',$value,$m))throw new \RuntimeException('关联类型不匹配');
    if($kind==='category'&&intval($m[1])===intval($draft['remoteid']))throw new \RuntimeException('分类不能选择自身作为上级，草稿已保留');
    $check=service_match::require_ready($source,$kind,$snapshot);$valid=false;
    foreach($check['relations'] as $r)if($r['kind']===$type&&$r['remoteid']===intval($m[1]))$valid=true;
    if(!$valid)throw new \RuntimeException('关联草稿不属于当前来源记录');
    $fields[$field]=(string)(self::target($source,$type,intval($m[1]))?:self::dependency($uid,$source,$type,intval($m[1])));
   }elseif(!ctype_digit($value))throw new \RuntimeException('关联字段格式无效');
  }
  return $fields;
 }
 public static function commit($uid,$draft) {
  $kind=$draft['kind'];$source=intval($draft['sourceid']);$def=service_fields::entities()[$kind];$payload=json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR);
  $sourceItem=$draft['origin']==='external'?service_match::require_ready($source,$kind,$payload['snapshot'])['item']:[];
  $fields=$payload['fields'];$fields['available']='1';$fields=self::resolve($uid,$draft,$fields);self::validate_fields($kind,$fields);
  $id=intval($draft['targetid']);if($draft['origin']==='external')$id=self::target($source,$kind,$draft['remoteid'])?:$id;
  if($id&&!DB::result_first('SELECT '.$def['pk'].' FROM %t WHERE '.$def['pk'].'=%d',[$def['table'],$id]))throw new \RuntimeException('原编辑目标不存在，草稿已保留');
  if($kind==='category'){$parent=intval($fields['parentid']??0);$seen=$id?[$id=>true]:[];while($parent){if(isset($seen[$parent]))throw new \RuntimeException('上级分类不能形成循环，草稿已保留');$seen[$parent]=true;$parent=intval(DB::result_first('SELECT parentid FROM %t WHERE catid=%d',['flipbook_category',$parent]));}}
  $config=service_draft_editor::config($kind);$get=$_GET;$post=$_POST;
  $mediaRefs=$kind==='product'&&isset($sourceItem['media'])?service_fields::collection_patch($source,'media',$sourceItem['media'],$id?\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($id):[],'image'):[];
  try{
   $_POST=$_GET=$fields;$_GET['id']=(string)$id;
   $values=\flipbook\admin_crud::collect($config);if($values===false)throw new \RuntimeException('原生字段校验未通过');
   if($kind==='product'&&$values['code']!==''&&DB::result_first('SELECT productid FROM %t WHERE code=%s AND productid<>%d',['flipbook_product',$values['code'],$id]))throw new \RuntimeException('商品编码与其他商品冲突，草稿已保留');
   if($id)DB::update($def['table'],$values,[$def['pk']=>$id]);else $id=DB::insert($def['table'],$values,true);
   if(!empty($config['extra_save']))call_user_func($config['extra_save'],$id);
  }finally{$_GET=$get;$_POST=$post;}
  if($draft['origin']==='external'){
   service_store::remember($source,$kind,$draft['remoteid'],$id);
   if($kind==='product')foreach(json_decode($fields['skulines']??'[]',true)?:[] as $sku)if(!empty($sku['_remoteid'])){ $local=DB::result_first('SELECT skuid FROM %t WHERE productid=%d AND code=%s',['flipbook_sku',$id,trim((string)($sku['code']??''))]);if($local)service_store::remember($source,'sku',intval($sku['_remoteid']),intval($local));}
   if($kind==='product')self::remember_media($source,$mediaRefs,$id);
   if($kind!=='product')service_match::save_choice($source,$kind,$draft['remoteid'],'existing',$id);
  }
  service_draft_store::remove($draft['draftid'],$uid,$draft['revision']);return $id;
 }
 private static function remember_media($source,$references,$productid) {
  $rows=\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($productid);$used=[];
  foreach($references as $reference)if(!empty($reference['_remoteid']))foreach($rows as $row){
   if(isset($used[$row['mediaid']])||$row['image']!==($reference['image']??'')||$row['type']!==($reference['type']??'detail'))continue;
   service_store::remember($source,'media',intval($reference['_remoteid']),intval($row['mediaid']));$used[$row['mediaid']]=true;break;
  }
 }
 public static function publish($uid,$versions) {
  if(!$versions||count($versions)>50)throw new \RuntimeException('请选择 1–50 条草稿');
  $ids=array_map('intval',array_keys($versions));sort($ids);$lock='fb-draft-publish-'.$uid;
  if(!DB::result_first('SELECT GET_LOCK(%s,1)',[$lock]))throw new \RuntimeException('草稿正在上架，请稍后再试');
  $sourceLocks=[];DB::query('START TRANSACTION');
  try{
   $rows=[];foreach($ids as $id){$r=DB::fetch_first('SELECT * FROM %t WHERE draftid=%d AND uid=%d FOR UPDATE',['flipbook_sync_draft',$id,$uid]);if(!$r)throw new \RuntimeException('草稿不存在或无权访问');if(intval($r['revision'])!==intval($versions[$id]))throw new \RuntimeException('草稿已更新，请刷新列表后再上架');$rows[]=$r;}
   $sources=array_values(array_unique(array_filter(array_map(fn($row)=>intval($row['sourceid']),$rows))));sort($sources);foreach($sources as $source){service_import::lock($source);$sourceLocks[]=$source;}
   usort($rows,fn($a,$b)=>array_search($a['kind'],['category','series','supplier','product'])<=>array_search($b['kind'],['category','series','supplier','product']));
   $result=[];foreach($rows as $row){$exists=DB::result_first('SELECT draftid FROM %t WHERE draftid=%d',['flipbook_sync_draft',$row['draftid']]);if($exists)$result[]=self::commit($uid,$row);}
   DB::query('COMMIT');return $result;
  }catch(\Throwable $e){DB::query('ROLLBACK');throw $e;}finally{foreach($sourceLocks as $source)service_import::unlock($source);DB::query('SELECT RELEASE_LOCK(%s)',[$lock]);}
 }
}
