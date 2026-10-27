<?php
namespace flipbook;

use DB;

if(!defined('IN_DISCUZ'))exit('Access Denied');

/** The same bounded, session-scoped list protocol serves H5 and the mini-program. */
class front_list {
 public static function supports($binding,$key){
  return isset(front_composer::schema()['lists'][$binding])&&in_array($key,front_composer::schema()['bindingPages'][explode('.',$binding)[0]]??[],true);
 }

 public static function page($binding,$page=1){
  $size=intval(front_composer::schema()['listDefaults']['pageSize']);
  $page=max(1,min(100000,intval($page)));$start=($page-1)*$size;$limit=$size+1;$items=[];
  $uid=front_helper::uid();$keyword=is_scalar($_GET['keyword']??null)?mb_substr(trim((string)$_GET['keyword']),0,40,'UTF-8'):'';
  $catid=max(0,intval($_GET['catid']??0));$seriesid=max(0,intval($_GET['seriesid']??0));
  if($binding==='catalog.items'){
   $where=' WHERE available=1';$filters=[];
   foreach(['catid'=>$catid,'seriesid'=>$seriesid] as $field=>$value)if($value){$where.=' AND '.$field.'=%d';$filters[]=$value;}
   $albumWhere=$where;$albumArgs=array_merge(['flipbook_album'],$filters);
   $like='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$keyword).'%';
   if($keyword!==''){$albumWhere.=' AND (name LIKE %s OR subtitle LIKE %s OR description LIKE %s)';$albumArgs=array_merge($albumArgs,[$like,$like,$like]);}
   $albumCount=intval(DB::result_first('SELECT COUNT(*) FROM %t'.$albumWhere,$albumArgs));
   if($start<$albumCount)foreach(DB::fetch_all('SELECT * FROM %t'.$albumWhere.' ORDER BY displayorder,albumid LIMIT '.$start.','.$limit,$albumArgs) as $row)$items[]=self::card($row,'album',true);
   $remaining=$limit-count($items);if($keyword!==''&&$remaining>0){$productStart=max(0,$start-$albumCount);foreach(table_flipbook_product::t()->fetch_all_available($catid,$seriesid,$keyword,$remaining,$productStart) as $row)$items[]=self::card($row,'product');}
  }elseif($binding==='favorites.items'&&$uid>0){
   $type=($_GET['tab']??'')==='album'?'album':'product';$table=$type==='album'?'flipbook_album':'flipbook_product';$id=$type==='album'?'albumid':'productid';
   $rows=DB::fetch_all('SELECT t.* FROM %t f INNER JOIN %t t ON f.targetid=t.'.$id.' WHERE f.uid=%d AND f.targettype=%s AND t.available=1 ORDER BY f.dateline DESC,t.'.$id.' DESC LIMIT '.$start.','.$limit,['flipbook_favorite',$table,$uid,$type]);
   foreach($rows as $row)$items[]=array_merge(self::card($row,$type),['favorited'=>1]);
  }elseif($binding==='history.groups'&&$uid>0){
   $action=($_GET['action']??$_GET['tab']??'')==='share'?'share':'read';
   foreach(front_view::history_list($uid,$action,$limit,$start) as $day=>$records)foreach($records as $record){$record['link']=$record['url'];$record['meta']=trim(($record['targettype']==='album'?'':$record['meta']).' '.$record['time']);$record['_day']=$day;$items[]=$record;}
  }
  $more=count($items)>$size;$items=array_slice($items,0,$size);
  if($binding==='history.groups'){$groups=[];foreach($items as $record){$day=$record['_day'];unset($record['_day']);$groups[$day][]=$record;}$items=[];foreach($groups as $day=>$records)$items[]=['day'=>$day,'items'=>$records];}
  return ['items'=>$items,'pagination'=>['binding'=>$binding,'page'=>$page,'pageSize'=>$size,'hasMore'=>$more,'nextPage'=>$more?$page+1:null]];
 }

 private static function card($row,$type,$useProductCover=false){
  $item=$type==='album'?front_view::album($row):front_view::product($row);$id=intval($item[$type.'id']);
  if($useProductCover&&$type==='album'&&!empty($item['productCover'])){$item['cover']=$item['productCover'];$item['hascover']=1;}
  return array_merge($item,['listId'=>$type.':'.$id,'image'=>$item['cover'],'link'=>$type==='album'?'page:detail?albumid='.$id:'product:'.$id,'targettype'=>$type,'targetid'=>$id,'meta'=>$type==='album'?'':$item['code']]);
 }

 public static function favorite_counts($uid){
  $counts=['product'=>0,'album'=>0,'total'=>0];if($uid<=0)return $counts;
  foreach(['product','album'] as $type){$table=$type==='album'?'flipbook_album':'flipbook_product';$id=$type==='album'?'albumid':'productid';$counts[$type]=intval(DB::result_first('SELECT COUNT(*) FROM %t f INNER JOIN %t t ON f.targetid=t.'.$id.' WHERE f.uid=%d AND f.targettype=%s AND t.available=1',['flipbook_favorite',$table,$uid,$type]));}
  $counts['total']=$counts['product']+$counts['album'];return $counts;
 }
}
