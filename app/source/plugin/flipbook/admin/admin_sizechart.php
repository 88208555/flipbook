<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** Product-owned size charts. Named charts can be copied; they never create SKU records. */
class admin_sizechart {
 const MAX_ROWS=50;
 const MAX_COLUMNS=19; // The first (size) column is additional: 20 columns in total.
 const MAX_CHARS=100000;

 public static function from_spec($spec) {
  if(!is_array($spec))$spec=json_decode((string)$spec,true)?:[];
  if(is_array($spec['sizeChart']??null))return $spec['sizeChart'];
  $table=is_array($spec['sizetable']??null)?$spec['sizetable']:[];
  $header=array_shift($table)?:['尺码'];$rows=[];
  foreach($table as $row)if(is_array($row)&&isset($row[0]))$rows[]=['size'=>(string)$row[0],'mark'=>'','values'=>array_map('strval',array_slice($row,1))];
  return ['enabled'=>count($rows)>0,'name'=>'','columns'=>array_map('strval',array_slice($header,1)),'rows'=>$rows,'note'=>''];
 }
 public static function sku_sizes($skus) {
  $sizes=[];foreach($skus as $sku){$size=trim(strip_tags((string)($sku['size']??'')));if($size!==''&&!in_array($size,$sizes,true))$sizes[]=$size;}return $sizes;
 }
 private static function text($value,$label,$max=0) {
  if(!is_string($value))throw new \RuntimeException($label.'格式无效');
  if($max&&mb_strlen($value,'UTF-8')>$max)throw new \RuntimeException($label.'不能超过 '.$max.' 字');
  return trim(strip_tags(str_replace(["\r\n","\r"],"\n",$value)));
 }
 public static function parse($raw) {
  if(!is_string($raw)||mb_strlen($raw,'UTF-8')>self::MAX_CHARS)throw new \RuntimeException('尺码表总字数不能超过 '.self::MAX_CHARS.' 字，请精简后保存');
  $data=json_decode($raw,true);
  if(!is_array($data)||!is_array($data['columns']??null)||!is_array($data['rows']??null))throw new \RuntimeException('尺码表格式无效');
  if(count($data['rows'])>self::MAX_ROWS)throw new \RuntimeException('尺码表最多 51 行（含表头），请精简后保存');
  if(count($data['columns'])>self::MAX_COLUMNS)throw new \RuntimeException('尺码表每行最多 20 列（含尺码），请精简后保存');
  $chart=['enabled'=>!empty($data['enabled']),'name'=>self::text($data['name']??'','尺码表名称',40),'columns'=>[],'rows'=>[],'note'=>self::text($data['note']??'','测量提示',300)];
  foreach($data['columns'] as $column)$chart['columns'][]=self::text($column,'自定义列名称',100);
  $seen=[];
  foreach($data['rows'] as $row){
   if(!is_array($row)||!is_array($row['values']??null))throw new \RuntimeException('尺码表行格式无效');
   $size=self::text($row['size']??'','尺码名称',60);
   if($size===''||isset($seen[$size]))throw new \RuntimeException('尺码表的尺码名称不能为空或重复');$seen[$size]=true;
   if(count($row['values'])>count($chart['columns']))throw new \RuntimeException('尺码表单元格数量超出自定义列数，请检查后保存');
   $values=[];foreach($row['values'] as $cell)$values[]=self::text($cell,'测量值');
   while(count($values)<count($chart['columns']))$values[]='';
   $chart['rows'][]=['size'=>$size,'mark'=>self::text($row['mark']??'','尺码标记',60),'values'=>$values];
  }
  return $chart;
 }
 public static function legacy($raw) {
  if(!is_string($raw))throw new \RuntimeException('尺码表格式无效');
  if(mb_strlen($raw,'UTF-8')>self::MAX_CHARS)throw new \RuntimeException('尺码表总字数不能超过 '.self::MAX_CHARS.' 字，请精简后保存');
  $raw=trim(strip_tags(str_replace(["\r\n","\r"],"\n",$raw)));$lines=$raw===''?[]:explode("\n",$raw);$table=[];
  if(count($lines)>self::MAX_ROWS+1)throw new \RuntimeException('尺码表最多 51 行（含表头），请精简后保存');
  foreach($lines as $line){$cells=array_map('trim',explode('|',$line));if(count($cells)>self::MAX_COLUMNS+1)throw new \RuntimeException('尺码表每行最多 20 列，请精简后保存');if(trim($line)!=='')$table[]=$cells;}
  return self::from_spec(['sizetable'=>$table]);
 }
 public static function table($chart,$sizes=[]) {
  if(empty($chart['enabled']))return [];
  $rows=$chart['rows'];
  if($sizes){$indexed=[];foreach($rows as $row)$indexed[$row['size']]=$row;$rows=[];foreach($sizes as $size)if(isset($indexed[$size]))$rows[]=$indexed[$size];}
  if(!$rows)return [];
  $table=[array_merge(['尺码'],$chart['columns'])];foreach($rows as $row)$table[]=array_merge([$row['mark']!==''?$row['mark']:$row['size']],$row['values']);return $table;
 }
 public static function choices($exclude=0) {
  $choices=[];
  foreach(DB::fetch_all('SELECT productid,name,spec FROM %t WHERE productid<>%d ORDER BY productid DESC',['flipbook_product',$exclude]) as $product){$chart=self::from_spec($product['spec']);if(trim((string)($chart['name']??''))!=='')$choices[]=['productid'=>intval($product['productid']),'name'=>$chart['name'],'productName'=>$product['name']];}return $choices;
 }
 public static function respond() {
  header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
  $product=table_flipbook_product::t()->fetch(max(0,intval($_GET['chartProductid']??0)));
  if(!$product){http_response_code(404);echo json_encode(['ok'=>0,'message'=>'已保存的尺码表不存在']);exit;}
  echo json_encode(['ok'=>1,'chart'=>self::from_spec($product['spec'])],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
 }
}
