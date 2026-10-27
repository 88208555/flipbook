<?php
if(!defined('IN_DISCUZ')) exit('Access Denied');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') throw new RuntimeException('接口仅支持 GET',405);
 if(!in_array('flipbook',$_G['setting']['plugins']['available']??[],true)) throw new RuntimeException('来源产品插件未启用',503);
 if(isset($_GET['key']) || isset($_GET['token']) || isset($_GET['apikey'])) throw new RuntimeException('请通过 Authorization 请求头提交密钥',400);
 $grant=\flipbook_sync\service_auth::authenticate($_SERVER['HTTP_AUTHORIZATION']??'',$_SERVER['HTTP_X_FLIPBOOK_DOMAIN']??'',$_SERVER['REMOTE_ADDR']??'',$_SERVER['HTTP_ORIGIN']??'');
 if(!\flipbook_sync\service_fields::local_enabled()) throw new RuntimeException('本站本地数据源已关闭',503);
 $fields=\flipbook_sync\service_fields::allowed($grant['clientid']);
 $action=$_GET['action']??'products';
 if(!is_string($action)) throw new RuntimeException('参数无效',400);
 if($action==='info') $data=\flipbook_sync\service_export::info();
 elseif(in_array($action,['categories','series','suppliers'],true)) {
  foreach(['cursor','limit','upper'] as $key) if(isset($_GET[$key]) && (!is_string($_GET[$key]) || !ctype_digit($_GET[$key]) || strlen($_GET[$key])>10)) throw new RuntimeException('分页参数无效',400);
  $kind=['categories'=>'category','series'=>'series','suppliers'=>'supplier'][$action];
  $data=\flipbook_sync\service_export::entity_page($kind,$_GET['cursor']??0,$_GET['limit']??10,$_GET['upper']??null,$fields);
 } elseif($action==='entity') {
  $kind=$_GET['kind']??'';$id=$_GET['recordid']??'';
  if(!is_string($kind)||!is_string($id)||!ctype_digit($id)||strlen($id)>10) throw new RuntimeException('记录参数无效',400);
  $data=\flipbook_sync\service_export::info()+['entity'=>$kind,'item'=>\flipbook_sync\service_export::entity_item($kind,$id,$fields)];
 } elseif($action==='products') {
  foreach(['cursor','limit','upper'] as $key) if(isset($_GET[$key]) && (!is_string($_GET[$key]) || !ctype_digit($_GET[$key]) || strlen($_GET[$key])>10)) throw new RuntimeException('分页参数无效',400);
  $data=\flipbook_sync\service_export::page($_GET['cursor']??0,$_GET['limit']??20,$_GET['upper']??null,$fields);
 } elseif($action==='product') {
  if(!is_string($_GET['productid']??null) || !ctype_digit($_GET['productid'])) throw new RuntimeException('商品编号无效',400);
  $row=\flipbook\table_flipbook_product::t()->fetch(intval($_GET['productid']));
  if(!$row) throw new RuntimeException('商品不存在',404);
  $data=\flipbook_sync\service_export::info()+['item'=>\flipbook_sync\service_fields::project(\flipbook_sync\service_export::product($row),$fields)];
 } else throw new RuntimeException('接口操作不存在',404);
 $data['allowed_fields']=$fields;
 echo json_encode(['ok'=>1,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $error) {
 $code=intval($error->getCode());$status=in_array($code,[400,401,403,404,405,503],true)?$code:500;
 http_response_code($status);
 echo json_encode(['ok'=>0,'message'=>$status===500?'产品接口暂不可用，请联系管理员':$error->getMessage()],JSON_UNESCAPED_UNICODE);
}
exit;
