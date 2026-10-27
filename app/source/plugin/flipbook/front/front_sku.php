<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ'))exit('Access Denied');
/** SKU identifiers belong to a product, independently of the product's SPU code. */
class front_sku {
 public static function rows($productid,$public=false){
  $rows=DB::fetch_all('SELECT * FROM %t WHERE productid=%d ORDER BY displayorder,skuid',['flipbook_sku',intval($productid)]);
  foreach($rows as &$row){$row['skuid']=intval($row['skuid']);$row['productid']=intval($row['productid']);$row['images']=array_values(array_filter(array_map([front_helper::class,'img'],json_decode($row['images'],true)?:[])));if($public)foreach(['name','code','color','size'] as $field)$row[$field]=front_helper::e($row[$field]);}unset($row);return $rows;
 }
 public static function media($productid){$out=[];foreach(self::rows($productid,true) as $sku){if(!$sku['available'])continue;foreach($sku['images'] as $i=>$image)$out[]=['mediaid'=>'sku-'.$sku['skuid'].'-'.$i,'productid'=>intval($productid),'skuid'=>$sku['skuid'],'skuCode'=>$sku['code'],'image'=>front_helper::e($image),'hasimage'=>1,'title'=>$sku['name']?:$sku['code'],'text'=>'','type'=>'sku'];}return $out;}
 public static function save($productid,$raw){
  if(!is_string($raw)||strlen($raw)>200000)throw new \RuntimeException('SKU 配置过大');$rows=json_decode($raw,true);if(!is_array($rows)||count($rows)>100)throw new \RuntimeException('SKU 配置无效，最多 100 个 SKU');
  $product=table_flipbook_product::t()->fetch($productid);$clean=[];$codes=[];
  foreach($rows as $i=>$row){$code=trim((string)($row['code']??''));if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,79}$/',$code))throw new \RuntimeException('每个 SKU 必须填写独立商品编码，仅支持字母、数字、点、短横线和下划线');
   if(strcasecmp($code,$product['code'])===0)throw new \RuntimeException('SKU 商品编码不能与商品主编码相同');if(isset($codes[strtolower($code)]))throw new \RuntimeException('SKU 商品编码不能重复');$codes[strtolower($code)]=true;
   $id=max(0,intval($row['skuid']??0));if($id&&!DB::result_first('SELECT skuid FROM %t WHERE skuid=%d AND productid=%d',['flipbook_sku',$id,$productid]))throw new \RuntimeException('SKU 不属于当前商品');
   $duplicate=DB::result_first('SELECT skuid FROM %t WHERE code=%s AND productid<>%d',['flipbook_sku',$code,$productid]);if($duplicate)throw new \RuntimeException('SKU 商品编码已被其他商品使用');
   $submittedImages=$row['images']??[];if(!is_array($submittedImages))throw new \RuntimeException('SKU 图片格式无效');if(count($submittedImages)>20)throw new \RuntimeException('每个 SKU 最多 20 张图片，请移除多余图片后保存');
   $images=[];foreach($submittedImages as $image){$url=admin_helper::cleanurl((string)$image);if($url!=='')$images[]=$url;}
   $clean[]=['skuid'=>$id,'productid'=>$productid,'code'=>$code,'name'=>mb_substr(strip_tags((string)($row['name']??'')),0,100),'color'=>mb_substr(strip_tags((string)($row['color']??'')),0,60),'size'=>mb_substr(strip_tags((string)($row['size']??'')),0,60),'images'=>json_encode(array_values(array_unique($images)),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'displayorder'=>$i,'available'=>1];
  }
  // Clear this product only, after validation; the enclosing product transaction is atomic.
  DB::delete('flipbook_sku',['productid'=>$productid]);foreach($clean as $row){if(!$row['skuid'])unset($row['skuid']);DB::insert('flipbook_sku',$row);}
 }
 public static function migrate(){
  foreach(DB::fetch_all('SELECT * FROM %t ORDER BY productid',['flipbook_product']) as $product){$id=intval($product['productid']);if(DB::result_first('SELECT COUNT(*) FROM %t WHERE productid=%d',['flipbook_sku',$id]))continue;$media=table_flipbook_product_media::t()->fetch_all_by_product_type($id,'sku');if(!$media)continue;$spec=front_helper::spec($product['spec']);$groups=[];
   foreach($media as $row){$name=$row['title'];foreach($spec['colors'] as $color)if(mb_strpos($name,$color)===0){$name=$color;break;}$groups[$name][]=$row['image'];}
   DB::query('START TRANSACTION');try{$i=0;foreach($groups as $name=>$images){$code=($product['code']?:'SPU-'.$id).'-'.str_pad((string)++$i,3,'0',STR_PAD_LEFT);DB::insert('flipbook_sku',['productid'=>$id,'code'=>$code,'name'=>$name,'color'=>in_array($name,$spec['colors'],true)?$name:'','size'=>'','images'=>json_encode($images,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'displayorder'=>$i,'available'=>1]);}DB::delete('flipbook_product_media',['productid'=>$id,'type'=>'sku']);DB::query('COMMIT');}catch(\Throwable $e){DB::query('ROLLBACK');throw $e;}
  }
 }
}
