<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')||!defined('IN_ADMINCP'))exit('Access Denied');

/** The draft box reuses native product and typed relation forms. */
class admin_draft {
 public static function run() {
  global $_G;$uid=intval($_G['uid']);\flipbook\admin_helper::needperm('edit');
  if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
   $returnOrigin='local';$returnKind='product';$ajax=($_POST['draft_action']??'')==='save';
   if($ajax&&ob_get_level()>0)ob_end_clean();
   try{
    if($ajax){$result=service_draft_editor::save_request($uid);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode(['ok'=>1]+$result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
    if(submitcheck('flipbooksubmit')&&intval($_GET['draftid']??0)){
     $draft=service_draft_store::fetch(intval($_GET['draftid']),$uid);$returnOrigin=$draft['origin'];$returnKind=$draft['kind'];
     if(intval($_POST['draftid']??0)!==intval($draft['draftid'])||intval($_POST['draft_revision']??0)!==intval($draft['revision']))throw new \RuntimeException('草稿版本已变化，请重新打开编辑');
     service_draft_publish::publish($uid,[$draft['draftid']=>$draft['revision']]);
    }else{
     if(!submitcheck('draftsubmit'))throw new \RuntimeException('页面已过期，请刷新后再试');
     $versions=self::selection($uid);$first=service_draft_store::fetch(array_key_first($versions),$uid);$returnOrigin=$first['origin'];$returnKind=$first['kind'];$action=isset($_POST['single'])?'publish':(string)($_POST['draft_action']??'');
     if($action==='publish')service_draft_publish::publish($uid,$versions);
     elseif($action==='discard')foreach($versions as $id=>$revision){$d=service_draft_store::fetch($id,$uid);if(intval($d['revision'])!==$revision)throw new \RuntimeException('草稿已更新，请刷新');service_draft_store::remove($id,$uid,$revision);}
     else throw new \RuntimeException('不支持的草稿操作');
    }
    \flipbook\admin_helper::succeeded('草稿操作完成','product',['view'=>'drafts','origin'=>$returnOrigin,'kind'=>$returnKind]);return;
   }catch(\Throwable $e){
    $message=$e instanceof \RuntimeException?$e->getMessage():'草稿操作失败，原草稿已保留';
    if($ajax){http_response_code(400);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>0,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
    self::notice($message,true);
   }
  }
  $id=max(0,intval($_GET['draftid']??0));if($id){try{self::editor(service_draft_store::fetch($id,$uid));}catch(\RuntimeException $e){self::notice($e->getMessage(),true);}return;}
  self::listing($uid);
 }
 private static function selection($uid) {
  $selected=$_POST['selected']??[];if(isset($_POST['single']))$selected=[$_POST['single']];
  if(!is_array($selected)||!$selected||count($selected)>50)throw new \RuntimeException('请选择 1–50 条草稿');$out=[];
  foreach($selected as $value){
   if(is_string($value)&&preg_match('/^native:([1-9][0-9]*):([a-f0-9]{64})$/D',$value,$native)){
    if(($_POST['draft_action']??'')!=='publish')throw new \RuntimeException('不能在草稿箱删除正式商品');
    $row=\flipbook\table_flipbook_product::t()->fetch(intval($native[1]));if(!$row||intval($row['available'])!==0||hash('sha256',json_encode($row))!==$native[2])throw new \RuntimeException('商品已变化，请刷新后再上架');
    $config=service_draft_editor::config('product');$fields=array_map('strval',array_intersect_key($row,$config['schema']))+array_map('strval',\flipbook\admin_product::load($row['productid']));
    $draft=service_draft_store::save($uid,'local','product',0,0,intval($native[1]),['fields'=>$fields,'editor_key'=>bin2hex(random_bytes(16))],bin2hex(random_bytes(16)),1,0);$value=$draft['draftid'].':'.$draft['revision'];
   }
   if(!is_string($value)||!preg_match('/^([1-9][0-9]*):([1-9][0-9]*)$/D',$value,$m))throw new \RuntimeException('草稿选择无效');
   $row=service_draft_store::fetch(intval($m[1]),$uid);if(intval($row['revision'])!==intval($m[2]))throw new \RuntimeException('草稿已更新，请刷新列表');$out[intval($m[1])]=intval($m[2]);
  }
  return $out;
 }
 private static function notice($message,$error=false) {echo '<p class="fb-sync-draft-notice'.($error?' is-error':'').'" role="status">'.admin_view::e($message).'</p>';}
 private static function editor($draft) {
  $_GET['op']='edit';$_GET['origin']=$draft['origin'];$_GET['kind']=$draft['kind'];$kind=$draft['kind'];$payload=json_decode($draft['payload'],true,512,JSON_THROW_ON_ERROR);$config=service_draft_editor::config($kind);
  $ready=true;if($draft['origin']==='external'){
   $match=service_match::inspect($draft['sourceid'],$kind,$payload['snapshot']);$ready=$match['ok'];
   if(!$ready)self::notice('字段未匹配：'.implode('；',array_column($match['issues'],'message')).'。请返回同步插件的调用数据列表完成匹配。',true);
  }
  \flipbook\admin_product::navigation($kind,$ready);
  echo '<link rel="stylesheet" href="source/plugin/flipbook_sync/static/admin.css">';
  $def=service_fields::entities()[$kind];$row=$payload['fields'];$row[$def['pk']]=$draft['targetid'];$row['__draft']=$draft;
  $config['action_url']=\flipbook\admin_helper::url('product',['view'=>'drafts','op'=>'edit','draftid'=>$draft['draftid']]);
  $config['pending_options']=service_draft_publish::pending_options($draft);
  if($kind==='product')\flipbook\admin_product::render($config,$row);
  else{
   $config['action_url']=preg_replace('/^admin\.php\?/','',$config['action_url']);
   \flipbook\admin_crud::render_form($config,$row);service_draft_editor::assets($config,$row,$kind);
  }
 }
 private static function listing($uid) {
  \flipbook\admin_product::navigation('drafts');$origin=($_GET['origin']??'local')==='external'?'external':'local';$kind=$origin==='external'?(string)($_GET['kind']??'product'):'product';
  $types=['category'=>'商品分类','series'=>'商品系列','supplier'=>'供应商户','product'=>'商品列表'];if(!isset($types[$kind]))$kind='product';
  $page=max(1,intval($_GET['page']??1));$url=\flipbook\admin_helper::url('product',['view'=>'drafts','origin'=>$origin,'kind'=>$kind]);
  echo '<link rel="stylesheet" href="source/plugin/flipbook_sync/static/admin.css"><section class="fb-sync fb-sync-drafts"><nav class="fb-sync-source-tabs">';
  foreach(['local'=>'本地产品','external'=>'外部产品'] as $value=>$label)echo '<a class="fb-admin-tab'.($origin===$value?' is-active':'').'" href="'.admin_view::e(\flipbook\admin_helper::url('product',['view'=>'drafts','origin'=>$value])).'">'.$label.'</a>';
  echo '</nav>';if($origin==='external'){echo '<nav class="fb-sync-source-tabs">';foreach($types as $value=>$label)echo '<a class="fb-admin-tab'.($kind===$value?' is-active':'').'" href="'.admin_view::e(\flipbook\admin_helper::url('product',['view'=>'drafts','origin'=>$origin,'kind'=>$value])).'">'.$label.'</a>';echo '</nav>';}
  $count=DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND origin=%s AND kind=%s',['flipbook_sync_draft',$uid,$origin,$kind]);
  $rows=DB::fetch_all('SELECT d.*,s.name AS source_name FROM %t d LEFT JOIN %t s ON s.sourceid=d.sourceid WHERE d.uid=%d AND d.origin=%s AND d.kind=%s ORDER BY d.updated DESC,d.draftid DESC LIMIT %d,20',['flipbook_sync_draft','flipbook_sync_source',$uid,$origin,$kind,($page-1)*20]);
  echo '<form method="post" action="'.admin_view::e($url).'" data-draft-list><input type="hidden" name="draftsubmit" value="1"><input type="hidden" name="formhash" value="'.FORMHASH.'"><div class="fb-sync-heading"><p>草稿与正式内容分开保存，编辑确认后再上架。</p><div class="fb-sync-row-actions"><button class="fb-admin-button" name="draft_action" value="publish">批量上架</button><button class="fb-admin-button" name="draft_action" value="discard">删除草稿</button></div></div><div class="fb-sync-table-wrap"><table class="fb-sync-table"><thead><tr><th><input type="checkbox" data-draft-all aria-label="全选草稿"></th><th>名称</th><th>来源</th><th>保存时间</th><th>状态</th><th>操作</th></tr></thead><tbody>';
  foreach($rows as $row){
   $ready=true;if($origin==='external'){$payload=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);$match=service_match::inspect($row['sourceid'],$kind,$payload['snapshot']);$ready=$match['ok'];}
   echo '<tr><td><input type="checkbox" name="selected[]" value="'.$row['draftid'].':'.$row['revision'].'" data-match-ready="'.($ready?'1':'0').'"></td><td>'.admin_view::e($row['name']).'</td><td>'.admin_view::e($origin==='local'?'本地产品':$row['source_name']).'</td><td>'.admin_view::e(dgmdate($row['updated'],'Y-m-d H:i')).'</td><td class="'.(!$ready?'is-error':'').'">'.($ready?'草稿 / 停用':'字段未匹配').'</td><td><div class="fb-sync-row-actions"><a class="fb-admin-button" href="'.admin_view::e(\flipbook\admin_helper::url('product',['view'=>'drafts','op'=>'edit','draftid'=>$row['draftid']])).'">编辑</a><button class="fb-admin-button" name="single" value="'.$row['draftid'].':'.$row['revision'].'" formaction="'.admin_view::e($url.'&draft_action=publish').'"'.(!$ready?' disabled':'').'>上架</button></div></td></tr>';
  }
  if(!$rows)echo '<tr><td colspan="6">暂无草稿</td></tr>';echo '</tbody></table></div></form>'.multi($count,20,$page,$url);
  if($origin==='local')self::inactive($uid);
  echo '</section><script src="source/plugin/flipbook_sync/static/draft.js" defer></script>';
 }
 private static function inactive($uid) {
  $rows=DB::fetch_all('SELECT p.* FROM %t p LEFT JOIN %t m ON m.localid=p.productid AND m.kind=%s LEFT JOIN %t d ON d.targetid=p.productid AND d.uid=%d AND d.origin=%s AND d.kind=%s WHERE p.available=0 AND m.remoteid IS NULL AND d.draftid IS NULL ORDER BY p.displayorder,p.productid LIMIT 100',['flipbook_product','flipbook_sync_map','product','flipbook_sync_draft',$uid,'local','product']);
  if(!$rows)return;echo '<h3>已保存为草稿 / 停用的商品</h3><form method="post" action="'.admin_view::e(\flipbook\admin_helper::url('product',['view'=>'drafts'])).'"><input type="hidden" name="draftsubmit" value="1"><input type="hidden" name="formhash" value="'.FORMHASH.'"><button class="fb-admin-button" name="draft_action" value="publish">上架选中商品</button><div class="fb-sync-table-wrap"><table class="fb-sync-table"><tbody>';
  foreach($rows as $row)echo '<tr><td><input type="checkbox" name="selected[]" value="native:'.$row['productid'].':'.hash('sha256',json_encode($row)).'"></td><td>'.admin_view::e($row['name']).'</td><td><a class="fb-admin-button" href="'.admin_view::e(\flipbook\admin_helper::url('product',['op'=>'edit','id'=>$row['productid']])).'">编辑后上架</a></td></tr>';echo '</tbody></table></div></form>';
 }
}
