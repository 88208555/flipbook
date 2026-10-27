<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');
class front_upload {
 public static function respond(){
  header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST' || !is_string($_POST['formhash']??null) || !hash_equals(front_helper::formhash(),$_POST['formhash'])){echo json_encode(['ok'=>0,'message'=>'表单已过期，请刷新重试']);exit;}
	if(empty($_FILES['file']) || !is_array($_FILES['file'])) {
		echo json_encode(['ok' => 0, 'message' => '未收到文件']); exit;
	}
	$file = $_FILES['file'];
	if(!empty($file['error']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
		echo json_encode(['ok' => 0, 'message' => '上传失败']); exit;
	}
 $kind=($_POST['kind']??'')==='video'?'video':'image';$limit=$kind==='video'?100*1024*1024:8*1024*1024;
	if(intval($file['size']) <= 0 || intval($file['size']) > $limit) {
		echo json_encode(['ok' => 0, 'message' => $kind==='video'?'视频需小于 100MB':'图片需小于 8MB']); exit;
	}
	$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
	$allowed = ['image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'video' => ['mp4', 'webm', 'm4v']];
	$kind = isset($_POST['kind']) && $_POST['kind'] === 'video' ? 'video' : 'image';
	if(!in_array($ext, $allowed[$kind], true)) {
		echo json_encode(['ok' => 0, 'message' => '不支持的文件格式']); exit;
	}
	if($kind === 'image' && !@getimagesize($file['tmp_name'])) {
		echo json_encode(['ok' => 0, 'message' => '图片文件无效']); exit;
	}
	try {
  $asset=front_media_storage::store($file['tmp_name'],$ext,$kind,['scope'=>$_POST['scope']??'micropage','productid'=>$_POST['productid']??0,'purpose'=>$_POST['purpose']??$kind]);
  echo json_encode(['ok'=>1]+$asset,JSON_UNESCAPED_SLASHES);
 } catch(\Throwable $error) { echo json_encode(['ok'=>0,'message'=>$error instanceof \RuntimeException?$error->getMessage():'文件保存失败，请检查存储配置']); }

exit;
 }
}
