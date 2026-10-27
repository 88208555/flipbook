<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

class admin_controller {
 public static function url($tab='admincp') {
  return 'admin.php?'.http_build_query(['action'=>'plugins','operation'=>'config','do'=>max(0,intval($_GET['do']??0)),'identifier'=>'flipbook_sync','pmod'=>$tab==='data'?'guide':$tab]+($tab==='data'?['view'=>'data']:[]));
 }
 public static function native_url($params=[]) {
  $id=intval(DB::result_first('SELECT pluginid FROM %t WHERE identifier=%s',['common_plugin','flipbook']));
  return \flipbook\admin_helper::url('product',['do'=>$id]+$params);
 }
 public static function input($key,$max=1000) {
  $value=$_POST[$key]??'';
  if(!is_string($value) || strlen($value)>$max) throw new \InvalidArgumentException('提交内容格式或长度无效');
  return trim($value);
 }
 public static function run($tab) {
  global $_G;
  if(!defined('IN_ADMINCP') || intval($_G['adminid'])!==1 || intval($_G['uid'])<=0) {http_response_code(403);exit('Access Denied');}
  if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
   if(ob_get_level()>0) ob_end_clean();
   header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
   try {
    if(!submitcheck('syncsubmit')) throw new \RuntimeException('页面已过期，请刷新后再试');
    $result=self::action(self::input('sync_action',40));
    echo json_encode(['ok'=>1]+$result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
   } catch(\Throwable $error) {
    http_response_code(400);echo json_encode(['ok'=>0,'message'=>$error instanceof \RuntimeException || $error instanceof \InvalidArgumentException?$error->getMessage():'操作失败，请检查插件日志'],JSON_UNESCAPED_UNICODE);
   }
   exit;
  }
  admin_view::render($tab);
 }
 private static function fields() {
  $raw=self::input('fields',12000);
  try {$fields=json_decode($raw,true,512,JSON_THROW_ON_ERROR);} catch(\Throwable $e) {throw new \InvalidArgumentException('请重新打开授权表单选择调用字段');}
  return service_fields::validate($fields);
 }
 private static function field_mode(){return 'shared';}
 private static function save_fields($id,$fields,$mode){service_fields::save(0,$fields);}
 private static function action($action) {
  $id=max(0,intval(self::input('recordid',10)));
  switch($action) {
   case 'key-create':
    $fields=self::fields();$mode=self::field_mode();DB::query('START TRANSACTION');
    try {$grant=service_auth::issue(self::input('domain',300),self::input('note',450),self::input('ips',1000));self::save_fields($grant['clientid'],$fields,$mode);DB::query('COMMIT');}
    catch(\Throwable $e) {DB::query('ROLLBACK');throw $e;}
    return ['message'=>'授权已创建。请保存本次显示的密钥。','grant'=>$grant];
   case 'key-rotate':
    return ['message'=>'密钥已重新生成，旧密钥立即失效。','grant'=>service_auth::rotate($id)];
   case 'key-toggle':
    $row=DB::fetch_first('SELECT clientid,enabled FROM %t WHERE clientid=%d',['flipbook_sync_key',$id]);
    if(!$row) throw new \RuntimeException('授权不存在');
    DB::update('flipbook_sync_key',['enabled'=>$row['enabled']?0:1],['clientid'=>$id]);
    return ['message'=>$row['enabled']?'授权已停用':'授权已启用'];
   case 'key-update':
    if(!DB::result_first('SELECT clientid FROM %t WHERE clientid=%d',['flipbook_sync_key',$id])) throw new \RuntimeException('授权不存在');
    $fields=self::fields();$mode=self::field_mode();$values=['note'=>service_import::text(self::input('note',450),150,'备注'),'ips'=>service_auth::ips(self::input('ips',1000))];
    DB::query('START TRANSACTION');
    try {DB::update('flipbook_sync_key',$values,['clientid'=>$id]);self::save_fields($id,$fields,$mode);DB::query('COMMIT');}
    catch(\Throwable $e) {DB::query('ROLLBACK');throw $e;}
    return ['message'=>'授权资料已保存'];
   case 'local-toggle': return ['message'=>service_fields::local_toggle()?'本地数据源已开放':'本地数据源已关闭，外部调用已停止'];
   case 'local-products': return self::local_products($id);
   case 'fields-save':
    if($id && !DB::result_first('SELECT clientid FROM %t WHERE clientid=%d',['flipbook_sync_key',$id])) throw new \RuntimeException('授权不存在');
    self::save_fields($id,self::fields(),self::field_mode());return ['message'=>'调用字段已保存'];
   case 'source-save': return self::save_source($id);
   case 'source-products': return self::browse($id);
   case 'source-publish': return self::publish($id);
   case 'source-match': return self::match($id);
   case 'match-save': return self::save_match($id);
   case 'source-toggle':
    $source=DB::fetch_first('SELECT sourceid,enabled FROM %t WHERE sourceid=%d',['flipbook_sync_source',$id]);
    if(!$source) throw new \RuntimeException('数据源不存在');
    DB::update('flipbook_sync_source',['enabled'=>$source['enabled']?0:1],['sourceid'=>$id]);return ['message'=>$source['enabled']?'数据源已停用':'数据源已启用'];
   case 'source-test':
    $data=service_client::request(service_store::source($id),['action'=>'info']);return ['message'=>'连接成功，来源共有 '.intval($data['total']).' 个商品'];
   case 'job-start': return ['job'=>self::public_job(service_import::start($id))];
   case 'job-step': return ['job'=>self::public_job(service_import::step(self::input('jobid',32)))];
   case 'job-pause':
    $jobid=self::input('jobid',32);$job=DB::fetch_first('SELECT * FROM %t WHERE jobid=%s',['flipbook_sync_job',$jobid]);
    if(!$job) throw new \RuntimeException('同步任务不存在');
    service_import::lock($job['sourceid']);
    try {DB::query('UPDATE %t SET status=%s,updated=%d WHERE jobid=%s AND status=%s',['flipbook_sync_job','paused',time(),$jobid,'running']);}
    finally {service_import::unlock($job['sourceid']);}
    return ['message'=>'已停止后续批次，已保存的草稿保留；可继续同步'];
  }
  throw new \RuntimeException('不支持的操作');
 }
 private static function local_products($id) {
  if($id && !DB::result_first('SELECT clientid FROM %t WHERE clientid=%d',['flipbook_sync_key',$id])) throw new \RuntimeException('授权不存在');
  $cursor=self::input('cursor',10);if($cursor!=='' && !ctype_digit($cursor)) throw new \RuntimeException('分页位置无效');
  $fields=service_fields::allowed($id);$data=service_export::page(intval($cursor),10,null,$fields);
  foreach($data['items'] as &$item) $item['local_productid']=$item['productid'];unset($item);
  $data['shared_fields']=service_fields::allowed(0);$data['allowed_fields']=$fields;$data['field_mode']=service_fields::mode($id);$data['local_enabled']=service_fields::local_enabled();
  return ['catalog'=>$data];
 }
 private static function kind() {
  $kind=self::input('kind',12)?:'product';if(!isset(service_fields::entities()[$kind]))throw new \RuntimeException('数据类型无效');return $kind;
 }
 private static function remote_item($source,$kind,$remote) {
  $binding=service_store::source($source);$data=service_client::request($binding,$kind==='product'?['action'=>'product','productid'=>$remote]:['action'=>'entity','kind'=>$kind,'recordid'=>$remote]);
  $pk=service_fields::entities()[$kind]['pk'];
  if(!is_array($data['item']??null)||intval($data['item'][$pk]??0)!==$remote)throw new \RuntimeException('来源记录不匹配');if($binding['instanceid']==='')DB::update('flipbook_sync_source',['instanceid'=>$data['instanceid']],['sourceid'=>$source]);return $data['item'];
 }
 private static function browse($id) {
  $kind=self::kind();$definition=service_fields::entities()[$kind];$cursor=self::input('cursor',10);if($cursor!==''&&!ctype_digit($cursor))throw new \RuntimeException('分页位置无效');
  $data=service_client::request(service_store::source($id),['action'=>$definition['action'],'cursor'=>intval($cursor),'limit'=>10]);
  if(!is_array($data['items']??null)||count($data['items'])>10||!is_bool($data['has_more']??null)||!is_numeric($data['next_cursor']??null))throw new \RuntimeException('来源列表无效');
  global $_G;
  foreach($data['items'] as &$item){
   if(!is_array($item)||!is_numeric($item[$definition['pk']]??null))throw new \RuntimeException('来源编号无效');
   $remote=intval($item[$definition['pk']]);$map=service_store::mapping($id,$kind,$remote);$item['local_productid']=$map?intval($map['localid']):0;
   unset($item['local_productid']);$check=service_match::inspect($id,$kind,$item);
   $item['recordid']=$remote;$item['match']=['ok'=>$check['ok'],'issues'=>$check['issues']];$item['local_productid']=$map?intval($map['localid']):0;
   $item['in_draft']=(bool)DB::result_first('SELECT draftid FROM %t WHERE uid=%d AND sourceid=%d AND origin=%s AND kind=%s AND remoteid=%d',['flipbook_sync_draft',intval($_G['uid']),$id,'external',$kind,$remote]);
  }unset($item);return ['catalog'=>$data];
 }
 private static function publish($id) {
  global $_G;$kind=self::kind();$remote=self::input('productid',10);if(!ctype_digit($remote)||intval($remote)<1)throw new \RuntimeException('来源编号无效');
  service_import::lock($id);
  try{
   $item=self::remote_item($id,$kind,intval($remote));$draft=service_draft_publish::receive(intval($_G['uid']),$id,$kind,$item);
   service_store::log($id,'draft','来源记录已加入草稿箱');return ['message'=>!empty($draft['retained'])?'该记录已在草稿箱，已保留现有编辑内容':'已加入草稿箱，编辑确认后可单个或批量上架','draftid'=>intval($draft['draftid'])];
  }finally{service_import::unlock($id);}
 }
 private static function match($id) {
  $kind=self::kind();$remote=intval(self::input('productid',10));if($remote<1)throw new \RuntimeException('来源编号无效');
  $check=service_match::inspect($id,$kind,self::remote_item($id,$kind,$remote));$choices=[];
  foreach(service_fields::entities() as $type=>$def)if($type!=='product')$choices[$type]=array_map(fn($row)=>['id'=>intval($row[$def['pk']]),'name'=>$row['name']],DB::fetch_all('SELECT '.$def['pk'].',name FROM %t ORDER BY name,'.$def['pk'],[$def['table']]));
  $config=service_draft_editor::config($kind);$fields=[];foreach($config['schema'] as $field=>$spec)if(!in_array($field,['supplierid','parentid'],true))$fields[]=['key'=>$field,'title'=>$spec['title']];
  return ['match'=>array_intersect_key($check,array_flip(['ok','issues','relations'])),'choices'=>$choices,'fields'=>$fields,'mapping'=>service_match::mapping($id,$kind)];
 }
 private static function save_match($id) {
  $kind=self::kind();$choices=json_decode(self::input('choices',20000),true,512,JSON_THROW_ON_ERROR);$mapping=json_decode(self::input('mapping',12000),true,512,JSON_THROW_ON_ERROR);
  if(!is_array($choices)||count($choices)>32)throw new \RuntimeException('关系匹配配置无效');
  DB::query('START TRANSACTION');
  try{
   foreach($choices as $choice){if(!is_array($choice))throw new \RuntimeException('关系匹配配置无效');service_match::save_choice($id,(string)($choice['kind']??''),intval($choice['remoteid']??0),(string)($choice['mode']??''),intval($choice['targetid']??0));}
   service_match::save_mapping($id,$kind,$mapping);DB::query('COMMIT');
  }catch(\Throwable $e){DB::query('ROLLBACK');throw $e;}
  return ['message'=>'匹配配置已保存，请检查列表状态'];
 }
 private static function save_source($id) {
  $row=$id?DB::fetch_first('SELECT * FROM %t WHERE sourceid=%d',['flipbook_sync_source',$id]):null;
  if($id && !$row) throw new \RuntimeException('数据源不存在');
  $name=service_import::text(self::input('name',300),100,'数据源名称');
  if($name==='') throw new \RuntimeException('请填写数据源名称');
  $endpoint=service_client::endpoint(self::input('endpoint',500));
  if($row && $row['endpoint']!==$endpoint) throw new \RuntimeException('来源地址不能替换，请新增数据源以保留原来源映射');
  if(DB::result_first('SELECT sourceid FROM %t WHERE endpoint=%s AND sourceid<>%d',['flipbook_sync_source',$endpoint,$id])) throw new \RuntimeException('此数据源已存在');
  $values=['name'=>$name,'endpoint'=>$endpoint,'domain'=>service_client::own_domain()];
  $key=$_SERVER['HTTP_X_FLIPBOOK_SOURCE_KEY']??'';
  if(!is_string($key) || strlen($key)>100) throw new \RuntimeException('调用密钥格式无效');
  $key=trim($key);
  if($key!=='') {
   if(!preg_match('/^fbps_[1-9][0-9]*\.[a-f0-9]{64}$/D',$key)) throw new \RuntimeException('调用密钥格式无效');
   $values['keycipher']=service_store::encrypt($key);
  } elseif(!$row) throw new \RuntimeException('请填写来源站授予本站的调用密钥');
  if($row) DB::update('flipbook_sync_source',$values,['sourceid'=>$id]);
  else $id=DB::insert('flipbook_sync_source',$values+['enabled'=>1,'instanceid'=>'','lastsync'=>0],true);
  return ['message'=>'数据源已保存','sourceid'=>$id];
 }
 public static function public_job($job) {
  return array_intersect_key($job,array_flip(['jobid','status','total','processed','added','changed','skipped','message','updated']));
 }
}
