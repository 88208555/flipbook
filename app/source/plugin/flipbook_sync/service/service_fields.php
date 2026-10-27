<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** One field catalogue owns both the permission controls and the API projection. */
class service_fields {
 public static function entities() {
  return ['product'=>['table'=>'flipbook_product','pk'=>'productid','group'=>'product','action'=>'products'], 'category'=>['table'=>'flipbook_category','pk'=>'catid','group'=>'category_path','action'=>'categories'], 'series'=>['table'=>'flipbook_series','pk'=>'seriesid','group'=>'series','action'=>'series'], 'supplier'=>['table'=>'flipbook_supplier','pk'=>'supplierid','group'=>'supplier','action'=>'suppliers']];
 }
 public static function groups() {
  return [
   'product'=>['label'=>'商品资料','fields'=>['name'=>'商品名称','code'=>'商品主编码 SPU','summary'=>'副标题','description'=>'商品说明','cover'=>'封面地址','video'=>'视频地址','priceshow'=>'价格显示开关','pricevalue'=>'售价','priceorigin'=>'划线价','currency'=>'币种','displayorder'=>'排序','available'=>'上架状态','dateline'=>'创建时间']],
   'spec'=>['label'=>'规格与内容','fields'=>['colors'=>'颜色','sizes'=>'尺码','model'=>'模特资料','fit'=>'版型','supplier'=>'规格内供应商文字','fields'=>'自定义属性','content'=>'补充内容（含 HTML）','overlayLines'=>'悬浮文字','floatingImage.src'=>'悬浮图片地址','floatingImage.link'=>'悬浮图片链接','sizeChart'=>'尺码表（含旧格式及说明）']],
   'skus'=>['label'=>'SKU','ids'=>['skuid','productid'],'many'=>true,'fields'=>['code'=>'SKU 编码','name'=>'SKU 名称','color'=>'颜色','size'=>'尺码','images'=>'图片地址','displayorder'=>'排序','available'=>'上架状态']],
   'media'=>['label'=>'卖点 / 细节 / 模特图组','ids'=>['mediaid','productid'],'many'=>true,'fields'=>['type'=>'图组类型','title'=>'图片标题','image'=>'图片地址','displayorder'=>'排序']],
   'showcase'=>['label'=>'首页展示','ids'=>['albumid','catid','seriesid'],'fields'=>['name'=>'展示名称','subtitle'=>'展示副标题','description'=>'展示说明','cover'=>'封面地址','video'=>'视频地址','pagecount'=>'页数','displayorder'=>'排序','available'=>'上架状态','dateline'=>'创建时间']],
   'category_path'=>['label'=>'分类层级','ids'=>['catid','parentid'],'many'=>true,'fields'=>['name'=>'分类名称','displayorder'=>'排序','available'=>'启用状态']],
   'series'=>['label'=>'商品系列','ids'=>['seriesid'],'fields'=>['name'=>'系列名称','description'=>'系列说明','cover'=>'封面地址','displayorder'=>'排序','available'=>'启用状态','dateline'=>'创建时间']],
   'supplier'=>['label'=>'供应商','ids'=>['supplierid'],'fields'=>['name'=>'供应商名称','code'=>'供应商编码','displayorder'=>'排序','available'=>'启用状态']]
  ];
 }
 public static function catalog() {
  $out=[];
  foreach(self::groups() as $group=>$definition) foreach($definition['fields'] as $field=>$label) $out[($group==='product'?'':$group.'.').$field]=$label;
  return $out;
 }
 public static function validate($fields) {
  if(!is_array($fields) || !array_is_list($fields) || count($fields)>count(self::catalog())) throw new \InvalidArgumentException('调用字段配置无效');
  $known=self::catalog();
  foreach($fields as $field) if(!is_string($field) || !isset($known[$field])) throw new \InvalidArgumentException('存在不支持的调用字段');
  return array_values(array_intersect(array_keys($known),$fields));
 }
 public static function allowed($clientid) {
  $row=DB::fetch_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','fields:0']);
  if(!$row) return array_keys(self::catalog());
  try {return self::validate(json_decode($row['svalue'],true,512,JSON_THROW_ON_ERROR));}
  catch(\Throwable $e) {throw new \RuntimeException('调用字段配置损坏，请在后台重新保存授权');}
 }
 public static function mode($clientid) {
  return 'shared';
 }
 public static function inherit($clientid) {
  if(intval($clientid)<1) throw new \InvalidArgumentException('共享配置不能继承自身');
  DB::delete('flipbook_sync_setting',['skey'=>'fields:'.intval($clientid)]);
 }
 public static function local_enabled() {
  $value=DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','local-enabled']);
  return $value===null || $value===false || $value==='' || $value==='1';
 }
 public static function local_toggle() {
  $enabled=self::local_enabled()?0:1;
  DB::query('INSERT INTO %t (skey,svalue) VALUES (%s,%s) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)',['flipbook_sync_setting','local-enabled',(string)$enabled]);
  return $enabled;
 }
 public static function save($clientid,$fields) {
  $fields=self::validate($fields);
  DB::query('INSERT INTO %t (skey,svalue) VALUES (%s,%s) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)',['flipbook_sync_setting','fields:0',json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
 }
 private static function copy_path(&$out,$input,$path) {
  $parts=explode('.',$path);$value=$input;
  foreach($parts as $part) {if(!is_array($value) || !array_key_exists($part,$value)) return;$value=$value[$part];}
  $target=&$out;
  foreach($parts as $part) {$target=&$target[$part];}
  $target=$value;
 }
 private static function row($input,$fields,$ids=[]) {
  if($input===null) return null;
  $out=array_intersect_key($input,array_flip($ids));
  foreach($fields as $field) self::copy_path($out,$input,$field);
  return $out;
 }
 public static function project($item,$fields) {
  $enabled=array_flip(self::validate($fields));$out=['productid'=>intval($item['productid'])];
  foreach(self::groups() as $group=>$definition) {
   $selected=[];
   foreach($definition['fields'] as $field=>$label) if(isset($enabled[($group==='product'?'':$group.'.').$field])) $selected[]=$field;
   if(!$selected) continue;
   if($group==='product') {$out+=self::row($item,$selected);continue;}
   if(!array_key_exists($group,$item)) continue;
   if($group==='spec') {
    if(in_array('content',$selected,true)) $selected[]='contentHtml';
    if(in_array('sizeChart',$selected,true)) {$selected[]='sizetable';$selected[]='sizeTableNote';}
   }
   $out[$group]=!empty($definition['many'])?array_map(fn($row)=>self::row($row,$selected,$definition['ids']??[]),$item[$group]):self::row($item[$group],$selected,$definition['ids']??[]);
  }
  $out['checksum']=hash('sha256',json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  return $out;
 }
 public static function entity($kind,$item,$fields) {
  $group=self::entities()[$kind]['group']??null;
  if(!$group) throw new \InvalidArgumentException('来源数据类型无效');
  $definition=self::groups()[$group];$enabled=array_flip(self::validate($fields));$selected=[];
  foreach($definition['fields'] as $field=>$label) if(isset($enabled[$group.'.'.$field])) $selected[]=$field;
  $out=self::row($item,$selected,$definition['ids']);
  $out['checksum']=hash('sha256',json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
  return $out;
 }
 /** Merge object properties by presence; an explicit empty array/string still clears a field. */
 public static function merge($base,$patch) {
  foreach($patch as $key=>$value) {
   if(is_array($value) && !array_is_list($value) && is_array($base[$key]??null)) $base[$key]=self::merge($base[$key],$value);
   else $base[$key]=$value;
  }
  return $base;
 }
 public static function product_patch($item,$existing) {
  $base=$existing?:['name'=>'同步商品 '.intval($item['productid']),'code'=>'','summary'=>'','description'=>'','cover'=>'','video'=>'','pricevalue'=>'0.00','priceorigin'=>'0.00','currency'=>'CNY','priceshow'=>0,'available'=>0,'displayorder'=>0];
  $spec=\flipbook\front_helper::spec($existing['spec']??'{}');$spec['sizeChart']=\flipbook\admin_sizechart::from_spec($existing['spec']??'{}');
  $base['spec']=$spec;
  return self::merge($base,$item);
 }
 public static function related_patch($source,$kind,$row,$table,$pk) {
  $map=service_store::mapping($source,$kind,intval($row[$pk]??0));
  $base=$map?DB::fetch_first('SELECT * FROM %t WHERE '.$pk.'=%d',[$table,$map['localid']]):[];
  return $row+($base?:[]);
 }
 public static function collection_patch($source,$kind,$rows,$local,$required) {
  if(!is_array($rows)) throw new \RuntimeException('来源图组或 SKU 格式无效');
  $pk=$kind==='sku'?'skuid':'mediaid';$out=[];$retain=false;
  foreach($rows as $row) {
   if(!is_array($row)) throw new \RuntimeException('来源图组或 SKU 格式无效');
   $remote=intval($row[$pk]??0);$map=$remote?service_store::mapping($source,$kind,$remote):null;$base=[];
   foreach($local as $candidate) if(($map && intval($candidate[$pk])===intval($map['localid'])) || (!$map && $kind==='sku' && isset($row['code']) && $row['code']===$candidate['code'])) {$base=$candidate;break;}
   // Native media edits rebuild row IDs; recover an unambiguous original URL.
   if(!$base&&$kind==='media'&&isset($row['image'])){
    $matches=array_values(array_filter($local,fn($candidate)=>$candidate['image']===$row['image']&&(!isset($row['type'])||$candidate['type']===$row['type'])));
    if(count($matches)===1)$base=$matches[0];
   }
   if(!$base&&$kind==='media'&&$local&&(!array_key_exists('type',$row)||!array_key_exists('title',$row)||!array_key_exists('displayorder',$row))){$retain=true;continue;}
   if(!$base && !isset($row[$required])) {$retain=true;continue;}
   $row=$row+$base;$row['_localid']=intval($base[$pk]??0);$row['_remoteid']=$remote;$out[]=$row;
  }
  if($retain) foreach($local as $row) {
   $found=false;foreach($out as $candidate) if(intval($candidate['_localid']??0)===intval($row[$pk])) {$found=true;break;}
   if(!$found) $out[]=$row;
  }
  return $out;
 }
}
