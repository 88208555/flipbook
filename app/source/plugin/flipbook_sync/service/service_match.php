<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Fixed entity types cannot be remapped into another kind of local object. */
class service_match {
 public static function choice_key($source,$kind,$remote) {return 'choice:'.intval($source).':'.$kind.':'.intval($remote);}
 public static function choice($source,$kind,$remote) {
  $raw=DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting',self::choice_key($source,$kind,$remote)]);
  return $raw?json_decode($raw,true,512,JSON_THROW_ON_ERROR):null;
 }
 public static function save_choice($source,$kind,$remote,$mode,$target=0) {
  $definition=service_fields::entities()[$kind]??null;
  if(!$definition || $kind==='product' || $remote<1 || !in_array($mode,['new','existing'],true)) throw new \RuntimeException('必须选择同类型关系或明确新建');
  service_store::source($source);
  if($mode==='existing' && !DB::result_first('SELECT '.$definition['pk'].' FROM %t WHERE '.$definition['pk'].'=%d',[$definition['table'],intval($target)])) throw new \RuntimeException('指定的同类型本地记录不存在');
  $value=json_encode(['mode'=>$mode,'targetid'=>$mode==='new'?0:intval($target)],JSON_THROW_ON_ERROR);
  DB::query('INSERT INTO %t (skey,svalue) VALUES (%s,%s) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)',['flipbook_sync_setting',self::choice_key($source,$kind,$remote),$value]);
 }
 public static function mapping($source,$kind) {
  $raw=DB::result_first('SELECT svalue FROM %t WHERE skey=%s',['flipbook_sync_setting','mapping:'.intval($source).':'.$kind]);
  return $raw?json_decode($raw,true,512,JSON_THROW_ON_ERROR):[];
 }
 public static function save_mapping($source,$kind,$mapping) {
  $config=service_draft_editor::config($kind);$targets=array_keys($config['schema']);
  if(!is_array($mapping) || count($mapping)>50) throw new \RuntimeException('字段匹配配置无效');
  foreach($mapping as $from=>$to) {
   if(!is_string($from)||$from===''||strlen($from)>100||in_array($from,['productid','catid','seriesid','supplierid','category_path','series','supplier','checksum'],true)||!is_string($to)) throw new \RuntimeException('标准关系和标识字段必须一致，不能重映射');
   if(!in_array($to,$targets,true) && !($kind==='product' && str_starts_with($to,'custom:') && mb_strlen(substr($to,7))>0 && mb_strlen(substr($to,7))<=60)) throw new \RuntimeException('请选择已有同类型字段，或指定商品自定义属性名称');
   if(in_array($to,['supplierid','product_category','product_series','parentid'],true)) throw new \RuntimeException('分类、系列和供应商关系必须在同类型关系匹配中配置');
  }
  service_store::source($source);
  DB::query('INSERT INTO %t (skey,svalue) VALUES (%s,%s) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)',['flipbook_sync_setting','mapping:'.intval($source).':'.$kind,json_encode($mapping,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
 }
 public static function transform($source,$kind,$item) {
  $mapping=self::mapping($source,$kind);$original=$item;
  foreach($mapping as $from=>$to) if(array_key_exists($from,$original)) unset($item[$from]);
  foreach($mapping as $from=>$to) {
   if(!array_key_exists($from,$original))continue;$value=$original[$from];
   if(str_starts_with($to,'custom:')) {
    $value=is_scalar($value)?(string)$value:json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if(mb_strlen($value)>2000) throw new \RuntimeException('自定义属性内容超过原生字段长度，未截断或上架');
    $item['spec']['fields'][]=['label'=>substr($to,7),'value'=>$value];
   } else $item[$to]=$value;
  }
  return $item;
 }
 public static function relation($source,$kind,$row,$parent=0,$parentPending=false) {
  if($row===null) return ['kind'=>$kind,'remoteid'=>0,'name'=>'来源未指定','status'=>'matched','targetid'=>0];
  if(!is_array($row)) return ['kind'=>$kind,'remoteid'=>0,'name'=>'格式无效','status'=>'mismatch','targetid'=>0,'message'=>'标准关系必须是同类型对象，不能用文本或其他关系替代'];
  $definition=service_fields::entities()[$kind];$remote=intval($row[$definition['pk']]??0);$name=$row['name']??null;
  $out=['kind'=>$kind,'remoteid'=>$remote,'name'=>is_string($name)?$name:'名称未授权','status'=>'mismatch','targetid'=>0];
  if($remote<1 || $remote>4294967295 || !is_string($name) || trim($name)==='') return $out+['message'=>'标准关系名称或标识缺失，请检查来源字段授权'];
  if($kind==='supplier' && !array_key_exists('code',$row))return $out+['message'=>'供应商编码未授权，不能确认关系匹配'];
  $map=service_store::mapping($source,$kind,$remote);$choice=self::choice($source,$kind,$remote);
  if($choice && $choice['mode']==='new' && (!$map || !DB::result_first('SELECT '.$definition['pk'].' FROM %t WHERE '.$definition['pk'].'=%d',[$definition['table'],intval($map['localid'])]))) {$out['status']='new';return $out;}
  $target=$choice && $choice['mode']==='existing'?intval($choice['targetid']):intval($map['localid']??0);
  if($target) {
   $local=DB::fetch_first('SELECT * FROM %t WHERE '.$definition['pk'].'=%d',[$definition['table'],$target]);
   if($local && ($kind!=='category'||(!$parentPending && intval($local['parentid'])===$parent))) {$out['status']='matched';$out['targetid']=$target;return $out;}
   return $out+['message'=>'原匹配目标不存在或分类层级不一致，请重新选择同类型记录或新建'];
  }
  if($kind==='category' && $parentPending)return $out+['message'=>'上级分类将新建，请明确选择新建该子分类'];
  $params=[$definition['table'],$name];$where='name=%s';
  if($kind==='supplier'){$where.=' AND code=%s';$params[]=(string)$row['code'];}
  if($kind==='category'){$where.=' AND parentid=%d';$params[]=$parent;}
  $matches=DB::fetch_all('SELECT '.$definition['pk'].' FROM %t WHERE '.$where.' LIMIT 2',$params);
  if(count($matches)===1){$out['status']='matched';$out['targetid']=intval($matches[0][$definition['pk']]);return $out;}
  return $out+['message'=>count($matches)>1?'存在多个同类型目标，请明确指定':'未匹配本地记录，请指定同类型目标或明确新建'];
 }
 private static function schema_issues($kind,$item) {
  $config=service_draft_editor::config($kind);$known=array_keys($config['schema']);$definition=service_fields::entities()[$kind];
  $known=array_merge($known,[$definition['pk'],'checksum','dateline','local_productid']);
  if($kind==='product')$known=array_merge($known,['spec','skus','media','showcase','category_path','series','supplier']);
  if($kind==='category')$known[]='category_path';
  $issues=[];
  if($kind==='product')foreach(['supplierid','product_category','product_series'] as $field)if(array_key_exists($field,$item))$issues[]=['field'=>$field,'message'=>'不能将来源站编号作为本地关系，必须使用标准分类、系列、供应商对象匹配'];
  foreach($item as $field=>$value) if(!in_array($field,$known,true))$issues[]=['field'=>$field,'message'=>'来源字段未匹配，请指定本地字段或新增商品自定义属性'];
  foreach($config['schema'] as $field=>$schema) {
   if(!array_key_exists($field,$item)) {if(!empty($schema['required']))$issues[]=['field'=>$field,'message'=>'必需字段未提供或未获授权'];continue;}
   $value=$item[$field];$valid=is_scalar($value)&&!is_bool($value);
   if($valid&&$schema['type']==='amount')$valid=preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D',(string)$value);
   if($valid&&$schema['type']==='bool')$valid=in_array((string)$value,['0','1'],true);
   if($valid&&$schema['type']==='int')$valid=preg_match('/^-?[0-9]+$/D',(string)$value)&&(float)$value>=($kind==='supplier'?-2147483648:-32768)&&(float)$value<=($kind==='supplier'?2147483647:32767);
   if($valid&&!empty($schema['required']))$valid=trim((string)$value)!=='';
   if($valid&&isset($schema['maxlen']))$valid=mb_strlen((string)$value)<=$schema['maxlen'];
   if(!$valid)$issues[]=['field'=>$field,'message'=>'字段类型或长度不匹配'];
  }
  return $issues;
 }
 public static function inspect($source,$kind,$raw) {
  if(!is_array($raw) || !isset(service_fields::entities()[$kind])) throw new \RuntimeException('来源数据类型无效');
  $item=self::transform($source,$kind,$raw);$issues=self::schema_issues($kind,$item);$relations=[];$pk=service_fields::entities()[$kind]['pk'];if(!ctype_digit((string)($item[$pk]??''))||intval($item[$pk])<1||$item[$pk]>4294967295)$issues[]=['field'=>$pk,'message'=>'来源标识无效'];
  $issues=array_merge($issues,self::media_issues($item));
  if($kind==='product') {
   foreach(['category_path','series','supplier'] as $field)if(!array_key_exists($field,$item))$issues[]=['field'=>$field,'message'=>'强制标准关系字段未授权或未提供'];
   $path=$item['category_path']??[];
   foreach(['spec','showcase'] as $field)if(isset($item[$field])&&!is_array($item[$field]))$issues[]=['field'=>$field,'message'=>'字段必须为对象'];
   foreach(['skus','media'] as $field)if(isset($item[$field])&&(!is_array($item[$field])||!array_is_list($item[$field])))$issues[]=['field'=>$field,'message'=>'字段必须为列表'];
   foreach(['series','supplier'] as $type)if(array_key_exists($type,$item))$relations[]=self::relation($source,$type,$item[$type]);
  } elseif($kind==='category')$path=$item['category_path']??[$item];
  else {$path=[];$relations[]=self::relation($source,$kind,$item);}
  $parent=0;$remoteParent=0;$pending=false;$seen=[];
  if($kind==='category'&&(!is_array($path)||!$path||intval($path[array_key_last($path)]['catid']??0)!==intval($item['catid']??0)))$issues[]=['field'=>'category_path','message'=>'分类路径缺少当前分类'];
  if(!is_array($path)||!array_is_list($path)||count($path)>30)$issues[]=['field'=>'category_path','message'=>'分类路径无效'];
  else foreach($path as $category) {
   if(!is_array($category)||isset($seen[intval($category['catid']??0)])||intval($category['parentid']??-1)!==$remoteParent){$issues[]=['field'=>'category_path','message'=>'分类层级不完整或不一致'];break;}
   $seen[intval($category['catid']??0)]=true;$match=self::relation($source,'category',$category,$parent,$pending);$relations[]=$match;$parent=$match['targetid'];$pending=$pending||$match['status']==='new';$remoteParent=intval($category['catid']??0);
  }
  foreach($relations as $match)if($match['status']==='mismatch')$issues[]=['field'=>$match['kind'],'message'=>$match['message']??'标准关系未匹配'];
  return ['ok'=>!$issues,'issues'=>$issues,'relations'=>$relations,'item'=>$item];
 }
 private static function media_issues($item) {
  $urls=[];foreach(['cover','video'] as $field)if(isset($item[$field]))$urls[$field]=$item[$field];
  foreach(['series','showcase'] as $group)if(is_array($item[$group]??null))foreach(['cover','video'] as $field)if(isset($item[$group][$field]))$urls[$group.'.'.$field]=$item[$group][$field];
  if(isset($item['spec']['floatingImage']['src']))$urls['spec.floatingImage.src']=$item['spec']['floatingImage']['src'];
  foreach(is_array($item['media']??null)?$item['media']:[] as $i=>$media)if(is_array($media)&&array_key_exists('image',$media))$urls['media.'.$i.'.image']=$media['image'];
  foreach(is_array($item['skus']??null)?$item['skus']:[] as $i=>$sku)if(is_array($sku)&&array_key_exists('images',$sku)){
   if(!is_array($sku['images']))return [['field'=>'skus.'.$i.'.images','message'=>'SKU 图片必须为地址列表']];
   foreach($sku['images'] as $j=>$url)$urls['skus.'.$i.'.images.'.$j]=$url;
  }
  $issues=[];foreach($urls as $field=>$url)if(!is_string($url)||strlen($url)>255||($url!==''&&(!preg_match('#^https?://#i',$url)||!\flipbook\admin_helper::cleanurl($url))))$issues[]=['field'=>$field,'message'=>'素材地址必须为合法的原站 HTTP(S) URL，且不超过原生长度限制'];
  return $issues;
 }
 public static function require_ready($source,$kind,$item) {
  $result=self::inspect($source,$kind,$item);
  if(!$result['ok']) throw new \RuntimeException('字段未匹配，不能一键上架：'.implode('；',array_unique(array_column($result['issues'],'message'))));
  return $result;
 }
}
