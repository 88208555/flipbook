<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

class service_export {
 public static function url($value) {
  global $_G;
  $value=\flipbook\front_helper::img($value);
  if($value==='') return '';
  if(preg_match('#^https?://#i',$value)) return $value;
  $base=rtrim($_G['siteurl'],'/');
  if(substr($value,0,2)==='//') return (parse_url($base,PHP_URL_SCHEME)?:'https').':'.$value;
  if($value[0]==='/') {
   $p=parse_url($base);$base=$p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'');
  }
  return $base.'/'.ltrim($value,'/');
 }
 public static function info() {
  global $_G;
  return ['protocol'=>'flipbook-product-sync/1.0','instanceid'=>service_store::instance(),'site'=>rtrim($_G['siteurl'],'/'),'total'=>intval(DB::result_first('SELECT COUNT(*) FROM %t',['flipbook_product'])),'upper'=>intval(DB::result_first('SELECT MAX(productid) FROM %t',['flipbook_product']))];
 }
 public static function link($value) {
  global $_G;
  $base=rtrim($_G['siteurl'],'/').'/plugin.php?id=flipbook:micro&';
  if(preg_match('/^product:([1-9][0-9]*)$/D',$value,$m)) return $base.'key=detail&productid='.$m[1];
  if(preg_match('/^page:([a-z_]+)$/D',$value,$m)) return $base.'key='.$m[1];
  return $value;
 }
 public static function page($cursor=0,$limit=20,$upper=null,$fields=null) {
  $cursor=max(0,intval($cursor));$limit=max(1,min(100,intval($limit)));
  $meta=self::info();$upper=$upper===null?$meta['upper']:max(0,intval($upper));
  $rows=DB::fetch_all('SELECT * FROM %t WHERE productid>%d AND productid<=%d ORDER BY productid LIMIT %d',['flipbook_product',$cursor,$upper,$limit+1]);
  $more=count($rows)>$limit;$rows=array_slice($rows,0,$limit);$items=[];
  foreach($rows as $row) {$item=self::product($row);$items[]=$fields===null?$item:service_fields::project($item,$fields);$cursor=intval($row['productid']);}
  return $meta+['items'=>$items,'next_cursor'=>$cursor,'has_more'=>$more,'upper_bound'=>$upper,'limit'=>$limit];
 }
 public static function entity_page($kind,$cursor=0,$limit=10,$upper=null,$fields=null) {
  $definition=service_fields::entities()[$kind]??null;
  if(!$definition || $kind==='product') throw new \InvalidArgumentException('数据类型无效');
  [$table,$pk]=[$definition['table'],$definition['pk']];$cursor=max(0,intval($cursor));$limit=max(1,min(100,intval($limit)));
  $meta=self::info();$meta['entity']=$kind;$meta['total']=intval(DB::result_first('SELECT COUNT(*) FROM %t',[$table]));$meta['upper']=intval(DB::result_first('SELECT MAX('.$pk.') FROM %t',[$table]));
  $upper=$upper===null?$meta['upper']:max(0,intval($upper));
  $columns=$kind==='supplier'?'supplierid,name,code,displayorder,available':'*';
  $rows=DB::fetch_all('SELECT '.$columns.' FROM %t WHERE '.$pk.'>%d AND '.$pk.'<=%d ORDER BY '.$pk.' LIMIT %d',[$table,$cursor,$upper,$limit+1]);
  $more=count($rows)>$limit;$rows=array_slice($rows,0,$limit);$items=[];
  foreach($rows as $row) {if($kind==='series')$row['cover']=self::url($row['cover']);$projected=service_fields::entity($kind,$row,$fields??array_keys(service_fields::catalog()));if($kind==='category')$projected['category_path']=array_map(fn($parent)=>service_fields::entity('category',$parent,$fields??array_keys(service_fields::catalog())),self::categories(intval($row['catid'])));$items[]=$projected;$cursor=intval($row[$pk]);}
  return $meta+['items'=>$items,'next_cursor'=>$cursor,'has_more'=>$more,'upper_bound'=>$upper,'limit'=>$limit];
 }
 public static function entity_item($kind,$id,$fields) {
  $definitions=service_fields::entities();
  if(!isset($definitions[$kind]) || $kind==='product') throw new \RuntimeException('数据类型无效',400);
  [$table,$pk]=[$definitions[$kind]['table'],$definitions[$kind]['pk']];$columns=$kind==='supplier'?'supplierid,name,code,displayorder,available':'*';
  $row=DB::fetch_first('SELECT '.$columns.' FROM %t WHERE '.$pk.'=%d',[$table,intval($id)]);
  if(!$row) throw new \RuntimeException('来源记录不存在',404);
  if($kind==='series')$row['cover']=self::url($row['cover']);
  $out=service_fields::entity($kind,$row,$fields);
  if($kind==='category')$out['category_path']=array_map(fn($parent)=>service_fields::entity('category',$parent,$fields),self::categories(intval($row['catid'])));
  return $out;
 }
 public static function product($row) {
  $id=intval($row['productid']);
  $fields=['productid','name','code','summary','description','cover','video','priceshow','pricevalue','priceorigin','currency','displayorder','available','dateline'];
  $out=array_intersect_key($row,array_flip($fields));$out['productid']=$id;
  foreach(['priceshow','displayorder','available','dateline'] as $key) $out[$key]=intval($out[$key]);
  foreach(['cover','video'] as $key) $out[$key]=self::url($out[$key]);
  $spec=\flipbook\front_helper::spec($row['spec']);
  $spec['sizeChart']=\flipbook\admin_sizechart::from_spec($row['spec']);
  $spec['floatingImage']['src']=self::url($spec['floatingImage']['src']??'');
  $spec['floatingImage']['link']=self::link($spec['floatingImage']['link']??'');
  $out['spec']=$spec;
  $out['skus']=\flipbook\front_sku::rows($id);
  foreach($out['skus'] as &$sku) $sku['images']=array_map([self::class,'url'],$sku['images']);
  unset($sku);
  $out['media']=\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($id);
  foreach($out['media'] as &$media) $media['image']=self::url($media['image']);
  unset($media);
  $out['supplier']=self::supplier(intval($row['supplierid']));
  $album=DB::fetch_first('SELECT a.* FROM %t a JOIN %t p ON a.albumid=p.albumid JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d ORDER BY p.pageid LIMIT 1',['flipbook_album','flipbook_page','flipbook_page_product',$id]);
  $out['showcase']=$album?:null;$out['category_path']=[];$out['series']=null;
  if($album) {
   $out['showcase']['cover']=self::url($album['cover']);$out['showcase']['video']=self::url($album['video']);
   $out['category_path']=self::categories(intval($album['catid']));
   $series=DB::fetch_first('SELECT * FROM %t WHERE seriesid=%d',['flipbook_series',intval($album['seriesid'])]);
   if($series) {$series['cover']=self::url($series['cover']);$out['series']=$series;}
  }
  $out['checksum']=hash('sha256',json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  return $out;
 }
 private static function supplier($id) {
  return $id?DB::fetch_first('SELECT supplierid,name,code,displayorder,available FROM %t WHERE supplierid=%d',['flipbook_supplier',$id]):null;
 }
 private static function categories($id) {
  $rows=[];$seen=[];
  while($id && count($rows)<30 && !isset($seen[$id])) {
   $seen[$id]=true;$row=DB::fetch_first('SELECT * FROM %t WHERE catid=%d',['flipbook_category',$id]);
   if(!$row) break;
   array_unshift($rows,$row);$id=intval($row['parentid']);
  }
  return $rows;
 }
}
