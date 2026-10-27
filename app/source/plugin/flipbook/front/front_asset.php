<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ'))exit('Access Denied');
/** Registry of plugin-owned files. Shared references are checked before deletion. */
class front_asset {
 public static function register($data){$data+=['uid'=>front_helper::uid(),'productid'=>0,'scope'=>'micropage','purpose'=>'image','status'=>'active','created'=>TIMESTAMP,'updated'=>TIMESTAMP];return DB::insert('flipbook_asset',$data,true);}
 private static function contains($value,$url){
  if(is_array($value)){foreach($value as $v)if(self::contains($v,$url))return true;return false;}
  if(!is_string($value))return false;
  $decoded=json_decode($value,true);if(is_array($decoded))return self::contains($decoded,$url);
  $value=str_replace('\\/','/',$value);$needle=ltrim($url,'/');
  return $value===$url || ltrim($value,'/')===$needle || strpos($value,$url)!==false;
 }
 public static function referenced($url){
  $sources=['flipbook_sku'=>['images'],'flipbook_product'=>['cover','video','spec'],'flipbook_product_media'=>['image'],'flipbook_album'=>['cover','video'],'flipbook_page'=>['image','imagecfg'],'flipbook_series'=>['cover'],'flipbook_micropage'=>['config'],'flipbook_setting'=>['svalue']];
  foreach($sources as $table=>$columns)foreach(DB::fetch_all('SELECT '.implode(',',$columns).' FROM %t',[$table]) as $row)if(self::contains($row,$url))return true;
  return false;
 }
 public static function remove($id){
  $asset=DB::fetch_first('SELECT * FROM %t WHERE assetid=%d',['flipbook_asset',$id]);
  if(!$asset || $asset['status']==='deleted')return true;
  if(self::referenced($asset['url']))return false;
  try{front_media_storage::delete_object($asset);DB::update('flipbook_asset',['status'=>'deleted','updated'=>TIMESTAMP],['assetid'=>$id]);return true;}
  catch(\RuntimeException $error){DB::update('flipbook_asset',['status'=>'pending','updated'=>TIMESTAMP],['assetid'=>$id]);return false;}
 }
 public static function cleanup($productid=null){
  $where=$productid===null?"status='pending' OR (status='active' AND created<".(TIMESTAMP-86400).')':"status IN ('active','pending') AND productid=".max(0,intval($productid));
  $count=0;foreach(DB::fetch_all('SELECT assetid FROM %t WHERE '.$where.' ORDER BY assetid LIMIT 200',['flipbook_asset']) as $row)if(self::remove($row['assetid']))$count++;return $count;
 }
 private static $previous=[];
 public static function product_values($id){return ['product'=>table_flipbook_product::t()->fetch($id),'skus'=>front_sku::rows($id),'media'=>table_flipbook_product_media::t()->fetch_all_by_product($id),'showcase'=>DB::fetch_all('SELECT a.* FROM %t a JOIN %t p ON p.albumid=a.albumid JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d',['flipbook_album','flipbook_page','flipbook_page_product',$id])];}
 public static function capture($id){self::$previous[$id]=self::product_values($id);}
 public static function cleanup_replaced($before,$after){foreach(DB::fetch_all('SELECT assetid,url FROM %t WHERE status IN (%s,%s)',['flipbook_asset','active','pending']) as $row)if(self::contains($before,$row['url']) && !self::contains($after,$row['url']))self::remove($row['assetid']);}
 public static function after_product_save($id){self::adopt($id);if(isset(self::$previous[$id]))self::cleanup_replaced(self::$previous[$id],self::product_values($id));}
 public static function adopt($productid){$values=self::product_values($productid);foreach(DB::fetch_all('SELECT assetid,url FROM %t WHERE uid=%d AND productid=0 AND status=%s',['flipbook_asset',front_helper::uid(),'active']) as $row)if(self::contains($values,$row['url']))DB::update('flipbook_asset',['productid'=>$productid],['assetid'=>$row['assetid']]);}

 public static function respond($op){
  header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');admin_helper::needperm('edit');
  if($op==='assets'){ $id=max(0,intval($_GET['productid']??0));$rows=DB::fetch_all('SELECT assetid,url,kind,purpose,size,productid FROM %t WHERE status=%s AND (productid=%d OR (productid=0 AND uid=%d)) ORDER BY assetid DESC LIMIT 100',['flipbook_asset','active',$id,front_helper::uid()]);echo json_encode(['ok'=>1,'items'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit; }
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST' || ($_POST['formhash']??'')!==front_helper::formhash()){echo json_encode(['ok'=>0,'message'=>'表单校验失败']);exit;}
  $assetid=max(0,intval($_POST['assetid']??0));echo json_encode(['ok'=>self::remove($assetid)?1:0,'message'=>'已清理未引用文件；被商品或页面使用的文件会保留']);exit;
 }
}
