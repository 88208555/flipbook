<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** The existing native schemas, form renderer and validators remain authoritative. */
class service_draft_editor {
 public static function enabled() {
  global $_G;
  return in_array('flipbook_sync',$_G['setting']['plugins']['available']??[],true);
 }
 public static function config($kind) {
  if(!in_array($kind,['product','category','series','supplier'],true)) throw new \RuntimeException('草稿类型无效');
  $file=dirname(__DIR__,2).'/flipbook/'.$kind.'.inc.php';
  if(!method_exists('\\flipbook\\admin_product','form_values')||!str_contains((string)file_get_contents($file),'$flipbookDraftConfigOnly'))throw new \RuntimeException('请先更新同版本 flipbook 的六个草稿兼容文件，再使用草稿和字段匹配功能');
  $flipbookDraftConfigOnly=true;return include $file;
 }
 public static function fields($kind,$input) {
  if(!is_array($input) || array_is_list($input)) throw new \RuntimeException('草稿字段格式无效');
  $config=self::config($kind);$keys=array_keys($config['schema']+($config['extra_fields']??[]));
  if($kind==='product') $keys=array_merge($keys,['sizechart','skulines']);
  $out=[];
  foreach($input as $key=>$value) {
   if(!in_array($key,$keys,true)) continue;
   if(!is_string($value) || strlen($value)>200000) throw new \RuntimeException('草稿字段格式或长度无效');
   $out[$key]=$value;
  }
  return $out;
 }
 public static function snapshot_fields($kind,$item,$source=0,$target=0) {
  $config=self::config($kind);$def=service_fields::entities()[$kind];
  $existing=$target?DB::fetch_first('SELECT * FROM %t WHERE '.$def['pk'].'=%d',[$def['table'],$target]):[];
  $out=[];foreach($config['schema'] as $field=>$schema)$out[$field]=(string)($item[$field]??$existing[$field]??$schema['default']??'');
  if($kind!=='product'){$out['available']='0';return $out;}
  $row=service_fields::product_patch($item,$existing);$row['spec']=json_encode($row['spec'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
  $album=$target?DB::fetch_first('SELECT a.* FROM %t a JOIN %t p ON p.albumid=a.albumid JOIN %t pp ON pp.pageid=p.pageid WHERE pp.productid=%d ORDER BY a.albumid LIMIT 1',['flipbook_album','flipbook_page','flipbook_page_product',$target]):[];
  $album=service_fields::merge($album?:[],$item['showcase']??[]);
  $localSkus=$target?\flipbook\front_sku::rows($target):[];
  $skus=array_key_exists('skus',$item)?service_fields::collection_patch($source,'sku',$item['skus'],$localSkus,'code'):$localSkus;
  foreach($skus as &$sku){$sku['skuid']=intval($sku['_localid']??0);unset($sku['_localid'],$sku['productid']);}unset($sku);
  $extra=\flipbook\admin_product::form_values($row,$album,$skus);
  $localMedia=$target?\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($target):[];
  $media=array_key_exists('media',$item)?service_fields::collection_patch($source,'media',$item['media'],$localMedia,'image'):$localMedia;
  usort($media,fn($a,$b)=>strcmp($a['type']??'detail',$b['type']??'detail')?:(intval($a['displayorder']??0)<=>intval($b['displayorder']??0)));
  $lines=[];foreach($media as $entry){if(($entry['type']??'')==='sku')continue;$lines[]=($entry['type']??'detail').'|'.($entry['title']??'').'|'.($entry['image']??'');}
  $extra['medialines']=implode("\n",$lines);$out+=array_map('strval',$extra);$out['available']='0';return $out;
 }
 public static function save_request($uid) {
  if(!submitcheck('draftsubmit')) throw new \RuntimeException('草稿保存请求已过期，请保留当前编辑页面并重新登录');
  \flipbook\admin_helper::needperm('edit');
  $id=max(0,intval($_POST['draftid']??0));$old=$id?service_draft_store::fetch($id,$uid):null;
  $kind=$old?$old['kind']:'product';$origin=$old?$old['origin']:'local';
  $raw=$_POST['payload']??'';
  if(!is_string($raw) || strlen($raw)>1048576) throw new \RuntimeException('草稿内容过大或格式无效');
  $fields=self::fields($kind,json_decode($raw,true,512,JSON_THROW_ON_ERROR));
  $payload=$old?json_decode($old['payload'],true,512,JSON_THROW_ON_ERROR):[];$payload['fields']=$fields;
  $payload['editor_key']=$payload['editor_key']??(string)($_POST['editor_key']??'');
  if(!preg_match('/^[a-f0-9]{32}$/D',$payload['editor_key'])) throw new \RuntimeException('草稿编辑身份无效');
  $target=$old?intval($old['targetid']):max(0,intval($_POST['targetid']??0));
  if($origin==='local' && $target && !\flipbook\table_flipbook_product::t()->fetch($target)) throw new \RuntimeException('原商品不存在，未覆盖其他商品');
  $editid=is_string($_POST['editid']??null)?$_POST['editid']:'';
  $row=service_draft_store::save($uid,$origin,$kind,intval($old['sourceid']??0),intval($old['remoteid']??0),$target,$payload,$editid,intval($_POST['sequence']??0),intval($_POST['revision']??0),$payload['editor_key']);
  return ['draftid'=>intval($row['draftid']),'revision'=>intval($row['revision'])];
 }
 public static function overlay($row,$id) {
  global $_G;
  $requested=max(0,intval($_GET['draftid']??0));
  $draft=$requested?service_draft_store::fetch($requested,intval($_G['uid'])):($id?service_draft_store::local(intval($_G['uid']),$id):null);
  if(!$draft) return [$row,null];
  if($draft['kind']!=='product' || intval($draft['targetid'])!==intval($id)) throw new \RuntimeException('草稿与当前商品不匹配');
  $payload=json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR);
  $row=($payload['fields']??[])+($row?:[]);$row['productid']=$id;$row['__draft']=$draft;
  return [$row,$draft];
 }
 public static function assets($config,$row,$kind='product') {
  $draft=$row['__draft']??null;$payload=$draft?json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR):[];
  $id=intval($row[$kind==='product'?'productid':(['category'=>'catid','series'=>'seriesid','supplier'=>'supplierid'][$kind])]??0);
  $keys=array_keys($config['schema']+($config['extra_fields']??[]));if($kind==='product')$keys=array_merge($keys,['sizechart','skulines']);
  $meta=['formId'=>$kind==='product'?'fb-product-form':\flipbook\admin_crud::form_id($kind),'fields'=>$keys,'kind'=>$kind,'targetid'=>$draft?intval($draft['targetid']):$id,'draftid'=>intval($draft['draftid']??0),'revision'=>intval($draft['revision']??0),'editorKey'=>$payload['editor_key']??bin2hex(random_bytes(16)),'endpoint'=>\flipbook\admin_helper::url('product',['view'=>'drafts','inajax'=>1])];
  echo '<link rel="stylesheet" href="source/plugin/flipbook_sync/static/admin.css?v='.@filemtime(__DIR__.'/../static/admin.css').'"><script type="application/json" data-product-draft-config>'.json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR).'</script><script src="source/plugin/flipbook_sync/static/draft.js?v='.@filemtime(__DIR__.'/../static/draft.js').'" defer></script>';
 }
 public static function clear_after_save($productid) {
  global $_G;
  $id=max(0,intval($_POST['draftid']??0));
  if(!$id) return;
  $row=DB::fetch_first('SELECT * FROM %t WHERE draftid=%d AND uid=%d',['flipbook_sync_draft',$id,intval($_G['uid'])]);
  if(!$row) return;
  if($row['origin']!=='local' || $row['kind']!=='product' || (intval($row['targetid'])!==0 && intval($row['targetid'])!==intval($productid))) return;
  service_draft_store::remove($id,intval($_G['uid']),intval($_POST['draft_revision']??0));
 }
 public static function before_save($productid) {
  global $_G;
  $id=max(0,intval($_POST['draftid']??0));if(!$id)return;
  $draft=service_draft_store::fetch($id,intval($_G['uid']));
  if($draft['origin']!=='local' || $draft['kind']!=='product' || intval($draft['targetid'])!==intval($productid)) throw new \RuntimeException('草稿与当前商品不匹配，外部草稿请从草稿箱确认上架');
  if(intval($draft['revision'])!==intval($_POST['draft_revision']??0)) throw new \RuntimeException('草稿已有较新的编辑版本，未覆盖正式商品，请重新打开草稿');
 }
}
