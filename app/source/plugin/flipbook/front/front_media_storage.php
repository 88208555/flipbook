<?php
namespace flipbook;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Tencent COS XML API, signed and uploaded on the server. */
class front_media_storage {
 public static function authorization($id,$secret,$method,$path,$headers,$start,$end) {
  $encoded=[]; foreach($headers as $name=>$value) $encoded[strtolower($name)]=rawurlencode($value); ksort($encoded,SORT_STRING);
  $pairs=[]; foreach($encoded as $name=>$value) $pairs[]=rawurlencode($name).'='.$value;
  $time=$start.';'.$end;
  $http=strtolower($method)."\n".$path."\n\n".implode('&',$pairs)."\n";
  $signkey=hash_hmac('sha1',$time,$secret);
  $signature=hash_hmac('sha1',"sha1\n".$time."\n".sha1($http)."\n",$signkey);
  return 'q-sign-algorithm=sha1&q-ak='.$id.'&q-sign-time='.$time.'&q-key-time='.$time.'&q-header-list='.implode(';',array_keys($encoded)).'&q-url-param-list=&q-signature='.$signature;
 }
 public static function storage_context($context,$kind) {
  $scope=in_array($context['scope']??'',['product','wechat'],true)?$context['scope']:'micropage';
  $productid=$scope==='wechat'?0:max(0,intval($context['productid']??0));
  $purpose=preg_replace('/[^a-z_]/','',substr((string)($context['purpose']??$kind),0,30))?:$kind;
  if($scope==='wechat' && $kind!=='image')throw new \RuntimeException('微信配置仅支持上传图片');
  $folder=$scope==='product'?'products/'.($productid?:'draft-'.front_helper::uid()).'/'.$purpose:($scope==='wechat'?'wechat/'.$purpose:'micropages/'.$purpose);
  return ['scope'=>$scope,'productid'=>$productid,'purpose'=>$purpose,'folder'=>$folder];
 }
 public static function store($file,$ext,$kind,$context=[]) {
  $name=gmdate('YmdHis').'_'.bin2hex(random_bytes(8)).'.'.$ext;
  $context=self::storage_context($context,$kind);$scope=$context['scope'];$productid=$context['productid'];$purpose=$context['purpose'];$folder=$context['folder'];
  $key=$folder.'/'.gmdate('Y/m').'/'.$name;
  if(front_service_config::get('storage_provider')!=='cos') {
   $relative='data/attachment/flipbook/'.$key;$path=DISCUZ_ROOT.$relative;$dir=dirname($path);
   if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new \RuntimeException('上传目录不可写');
   if(!move_uploaded_file($file,$path)) throw new \RuntimeException('文件保存失败');
   $id=front_asset::register(['productid'=>$productid,'scope'=>$scope,'purpose'=>$purpose,'kind'=>$kind,'provider'=>'local','objectkey'=>$relative,'bucket'=>'','region'=>'','url'=>$relative,'size'=>filesize($path)]);
   return ['url'=>$relative,'assetid'=>$id];
  }
  $bucket=front_service_config::get('cos_bucket');$region=front_service_config::get('cos_region');
  $id=front_service_config::get('cos_secret_id');$secret=front_service_config::get('cos_secret_key');
  if(!$bucket || !$region || !$id || !$secret) throw new \RuntimeException('请先完成腾讯云 COS 配置');
  $host=$bucket.'.cos.'.$region.'.myqcloud.com';
  $key=trim(front_service_config::get('cos_prefix'),'/').'/'.$key;
  $path='/'.implode('/',array_map('rawurlencode',explode('/',ltrim($key,'/'))));
  $types=['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif','mp4'=>'video/mp4','m4v'=>'video/mp4','webm'=>'video/webm'];
  $mime=$types[$ext]??'application/octet-stream';$now=time();
  $auth=self::authorization($id,$secret,'PUT',$path,['host'=>$host,'content-type'=>$mime],$now-60,$now+600);
  $stream=fopen($file,'rb');$curl=curl_init('https://'.$host.$path);
  curl_setopt_array($curl,[CURLOPT_UPLOAD=>true,CURLOPT_INFILE=>$stream,CURLOPT_INFILESIZE=>filesize($file),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>90,CURLOPT_HTTPHEADER=>['Host: '.$host,'Content-Type: '.$mime,'Authorization: '.$auth],CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
  $body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);fclose($stream);
  if($body===false || $status<200 || $status>=300) throw new \RuntimeException('COS 上传失败，请检查地域、密钥与存储桶权限');
  $url=rtrim(front_service_config::get('cos_domain')?:'https://'.$host,'/').$path;
  $assetid=front_asset::register(['productid'=>$productid,'scope'=>$scope,'purpose'=>$purpose,'kind'=>$kind,'provider'=>'cos','objectkey'=>ltrim($key,'/'),'bucket'=>$bucket,'region'=>$region,'url'=>$url,'size'=>filesize($file)]);
  return ['url'=>$url,'assetid'=>$assetid];
 }
 public static function delete_object($asset) {
  if($asset['provider']==='local') {
   if(strpos($asset['objectkey'],'data/attachment/flipbook/')!==0 || strpos($asset['objectkey'],'..')!==false)throw new \RuntimeException('文件不属于插件上传目录');
   $path=DISCUZ_ROOT.$asset['objectkey'];if(is_file($path)&&!unlink($path))throw new \RuntimeException('本地文件清理失败');return;
  }
  if($asset['provider']!=='cos' || !preg_match('/^[a-z0-9-]+-[0-9]+$/',$asset['bucket']) || !preg_match('/^[a-z]{2}-[a-z-]+$/',$asset['region']))throw new \RuntimeException('存储记录无效');
  $id=front_service_config::get('cos_secret_id');$secret=front_service_config::get('cos_secret_key');if(!$id||!$secret)throw new \RuntimeException('COS 凭据未配置');
  $host=$asset['bucket'].'.cos.'.$asset['region'].'.myqcloud.com';$path='/'.implode('/',array_map('rawurlencode',explode('/',$asset['objectkey'])));$now=time();
  $auth=self::authorization($id,$secret,'DELETE',$path,['host'=>$host],$now-60,$now+600);
  $curl=curl_init('https://'.$host.$path);curl_setopt_array($curl,[CURLOPT_CUSTOMREQUEST=>'DELETE',CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Host: '.$host,'Authorization: '.$auth],CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
  $body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);if($body===false||(!in_array($status,[200,202,204,404],true)))throw new \RuntimeException('COS 清理失败');
 }
}
