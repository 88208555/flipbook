<?php
namespace flipbook;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Navigation icons are inert monochrome graphics shared by H5 and native rendering. */
class front_nav_icon {
 const MAX_SVG=48000;
 public static function sanitize($source) {
  if(!is_string($source)||trim($source)==='')return '';
  if(strlen($source)>self::MAX_SVG||preg_match('/<!|<\?|&(?:#|[a-z])/i',$source))throw new \RuntimeException('SVG 需小于 48KB，不支持文档声明或实体');
  $document=new \DOMDocument();$previous=libxml_use_internal_errors(true);
  try{$loaded=$document->loadXML($source,LIBXML_NONET|LIBXML_NOBLANKS);}finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
  $root=$document->documentElement;
  if(!$loaded||!$root||$root->tagName!=='svg')throw new \RuntimeException('请提供完整的 SVG 图标');
  $tags=['svg','g','path','rect','circle','ellipse','line','polyline','polygon'];
  $geometry=['d','x','y','x1','x2','y1','y2','cx','cy','r','rx','ry','width','height','points','transform'];
  $paint=['fill','stroke'];$other=['viewBox','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','fill-rule','clip-rule','opacity','fill-opacity','stroke-opacity'];
  $count=0;$walk=function($node)use(&$walk,&$count,$tags,$geometry,$paint,$other){
   if(++$count>700||!in_array($node->tagName,$tags,true)||($node->namespaceURI&&$node->namespaceURI!=='http://www.w3.org/2000/svg'))throw new \RuntimeException('图标包含不支持的 SVG 元素');
   $attrs=[];foreach($node->attributes as $attr)$attrs[$attr->name]=$attr->value;
   foreach($attrs as $key=>$value){
    if($key==='xmlns'&&$node->tagName==='svg'){if($value!=='http://www.w3.org/2000/svg')throw new \RuntimeException('SVG 命名空间无效');continue;}
    if(in_array($key,['version','id'],true)){$node->removeAttribute($key);continue;}
    if(in_array($key,$paint,true)){
     if(!preg_match('/^(?:none|currentColor|transparent|#[0-9a-f]{3,8}|[a-z]{1,24}|rgba?\([0-9.,%\s]+\))$/i',trim($value)))throw new \RuntimeException('图标不支持外部颜色或资源引用');
     $node->setAttribute($key,in_array(strtolower(trim($value)),['none','transparent'],true)?'none':'currentColor');
    }elseif(in_array($key,$geometry,true)){
     if(strlen($value)>24000||!preg_match('/^[a-z0-9eE.,+\-()\s%]*$/i',$value)||preg_match('/(?:url|javascript|data)/i',$value))throw new \RuntimeException('SVG 路径或变换无效');
    }elseif(in_array($key,$other,true)){
     if(strlen($value)>120||!preg_match('/^[a-z0-9.,+\-\s%]*$/i',$value))throw new \RuntimeException('SVG 样式无效');
    }else{throw new \RuntimeException('图标含有不支持的属性；请使用纯路径 SVG');}
   }
   foreach(iterator_to_array($node->childNodes) as $child){
    if($child instanceof \DOMElement)$walk($child);
    elseif($child->nodeType===XML_COMMENT_NODE||($child->nodeType===XML_TEXT_NODE&&trim($child->nodeValue)===''))$node->removeChild($child);
    else throw new \RuntimeException('SVG 仅支持图形，不支持文字、脚本或外部内容');
   }
  };$walk($root);
  $view=$root->getAttribute('viewBox');
  if($view===''){
   $width=$root->getAttribute('width');$height=$root->getAttribute('height');
   if(!is_numeric($width)||!is_numeric($height))throw new \RuntimeException('SVG 需要 viewBox 或有效宽高');
   $view='0 0 '.$width.' '.$height;
  }
  $numbers=preg_split('/[\s,]+/',trim($view));
  if(count($numbers)!==4||array_filter($numbers,fn($v)=>!is_numeric($v))||floatval($numbers[2])<=0||floatval($numbers[3])<=0||max(array_map('abs',array_map('floatval',$numbers)))>10000)throw new \RuntimeException('SVG viewBox 无效');
  $root->setAttribute('viewBox',implode(' ',$numbers));$root->setAttribute('xmlns','http://www.w3.org/2000/svg');
  $root->removeAttribute('width');$root->removeAttribute('height');
  if(!$root->hasAttribute('fill'))$root->setAttribute('fill','currentColor');
  $result=$document->saveXML($root);
  if(strlen($result)>self::MAX_SVG)throw new \RuntimeException('图标内容过大');
  return $result;
 }
 public static function raster($bytes) {
  if(!is_string($bytes)||strlen($bytes)>1024*1024)throw new \RuntimeException('图标图片需小于 1MB');
  $info=@getimagesizefromstring($bytes);
  if(!$info||!in_array($info[2],[IMAGETYPE_PNG,IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_WEBP],true)||$info[0]>2048||$info[1]>2048)throw new \RuntimeException('请上传 2048×2048 以内的 PNG、JPG、WebP 或 GIF 图标');
  $image=@imagecreatefromstring($bytes);if(!$image)throw new \RuntimeException('图片无法读取');
  $canvas=imagecreatetruecolor(48,48);imagealphablending($canvas,false);imagesavealpha($canvas,true);imagefill($canvas,0,0,imagecolorallocatealpha($canvas,0,0,0,127));
  $scale=min(48/$info[0],48/$info[1]);$width=max(1,(int)round($info[0]*$scale));$height=max(1,(int)round($info[1]*$scale));
  imagecopyresampled($canvas,$image,(int)floor((48-$width)/2),(int)floor((48-$height)/2),0,0,$width,$height,$info[0],$info[1]);imagedestroy($image);
  $levels=[1=>[],2=>[],3=>[],4=>[]];
  for($y=0;$y<48;$y++){
   $start=0;$last=0;
   for($x=0;$x<=48;$x++){
    $level=0;
    if($x<48){$c=imagecolorsforindex($canvas,imagecolorat($canvas,$x,$y));$coverage=1-$c['alpha']/127;
     // Opaque white backgrounds are treated as empty so JPEG icons can be tinted too.
     if($c['alpha']===0&&min($c['red'],$c['green'],$c['blue'])>245)$coverage=0;
     $level=(int)round($coverage*4);
    }
    if($level!==$last){if($last>0)$levels[$last][]='M'.$start.' '.$y.'h'.($x-$start).'v1H'.$start.'z';$start=$x;$last=$level;}
   }
  }imagedestroy($canvas);
  $svg='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="currentColor">';$pixels=0;
  foreach($levels as $level=>$paths)if($paths){$pixels+=count($paths);$svg.='<path opacity="'.($level/4).'" d="'.implode('',$paths).'"/>';}
  if(!$pixels)throw new \RuntimeException('图片中没有可显示的图标轮廓');
  return self::sanitize($svg.'</svg>');
 }
 public static function html($source) {
  $svg=self::sanitize($source);
  return $svg===''?'':str_replace('<svg ','<svg class="fb-icon fb-custom-nav-icon" aria-hidden="true" ',$svg);
 }
 public static function respond() {
  header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_string($_POST['formhash']??null)||!hash_equals(front_helper::formhash(),$_POST['formhash'])){echo json_encode(['ok'=>0,'message'=>'表单已过期，请刷新重试']);exit;}
  try{
   if(isset($_FILES['file'])){
    $file=$_FILES['file'];if(!is_array($file)||!empty($file['error'])||empty($file['tmp_name'])||!is_uploaded_file($file['tmp_name'])||intval($file['size'])>1024*1024)throw new \RuntimeException('图标上传失败或超过 1MB');
    $bytes=file_get_contents($file['tmp_name']);$svg=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))==='svg'?self::sanitize($bytes):self::raster($bytes);
   }else{$svg=self::sanitize($_POST['svg']??'');if($svg==='')throw new \RuntimeException('请粘贴 SVG 图标');}
   echo json_encode(['ok'=>1,'svg'=>$svg],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  }catch(\Throwable $error){http_response_code(400);echo json_encode(['ok'=>0,'message'=>$error instanceof \RuntimeException?$error->getMessage():'图标处理失败'],JSON_UNESCAPED_UNICODE);}
  exit;
 }
}
