<?php
// Transactional persistence and the real product editor/API projection. No fixture survives.
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php';C::app()->init();
if(!defined('IN_ADMINCP'))define('IN_ADMINCP',true);
if(!defined('ADMINSCRIPT'))define('ADMINSCRIPT','admin.php');
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
$plugin=getenv('FLIPBOOK_PLUGIN_ROOT')?:DISCUZ_ROOT.'source/plugin/flipbook';
foreach(['front/front_share.php'=>'front_share','front/front_view.php'=>'front_view','admin/admin_product.php'=>'admin_product'] as $file=>$class)if(!class_exists('flipbook\\'.$class,false))require $plugin.'/'.$file;
$checks=[];
function share_check($value,$message){global $checks;if(!$value)throw new RuntimeException($message);$checks[]=$message;}
function share_fingerprint(){
 $snapshot=[];foreach(['flipbook_product','flipbook_product_media','flipbook_sku','flipbook_album','flipbook_page','flipbook_page_product'] as $table){$rows=array_map('json_encode',DB::fetch_all('SELECT * FROM %t',[$table]));sort($rows);$snapshot[$table]=$rows;}
 return hash('sha256',json_encode($snapshot));
}
$before=share_fingerprint();$oldGet=$_GET;$oldPost=$_POST;$error=null;
DB::query('START TRANSACTION');
try{
 $row=DB::fetch_first('SELECT * FROM %t ORDER BY productid LIMIT 1',['flipbook_product']);share_check((bool)$row,'a real product supplies the schema for rolled-back fixtures');
 unset($row['productid']);$row['code']='SHARE-AUDIT-'.bin2hex(random_bytes(4));$row['name']='商品名称自动跟随';$row['summary']='商品副标题自动跟随';$row['description']='商品说明，不能误用副标题。';$row['cover']='data/attachment/flipbook/audit-first.jpg';$row['spec']='{}';$row['available']=1;
 $id=DB::insert('flipbook_product',$row,true);$row['productid']=$id;
 $default=flipbook\front_share::product($row);
 share_check($default===['title'=>$row['name'],'subtitle'=>$row['summary'],'description'=>$row['description'],'image'=>$row['cover']],'empty overrides use product name, summary, description, and first cover');
 $_GET=['share_title'=>'专属分享标题','share_subtitle'=>'独立的分享副标题','share_description'=>"自定义描述\n第二行",'spec_content'=>'其他内容保留'];$_POST=[];
 flipbook\admin_product::save($id);$loaded=flipbook\admin_product::load($id);$saved=flipbook\table_flipbook_product::t()->fetch($id);
 share_check($loaded['share_title']===$_GET['share_title']&&$loaded['share_subtitle']===$_GET['share_subtitle']&&$loaded['share_description']===$_GET['share_description'],'all three overrides survive a real save and admin reload');
 share_check($loaded['spec_content']==='其他内容保留','saving share fields preserves other submitted product specifications');
 $effective=flipbook\front_view::product_detail($saved)['share'];
 share_check($effective['title']===$_GET['share_title']&&$effective['subtitle']===$_GET['share_subtitle']&&$effective['description']===$_GET['share_description']&&$effective['image']===$row['cover'],'the shared product detail payload resolves overrides and automatic image');
 $_GET['share_title']='';flipbook\admin_product::save($id);DB::update('flipbook_product',['name'=>'新的商品名称','description'=>'新的商品说明','cover'=>'data/attachment/flipbook/audit-new-first.jpg'],['productid'=>$id]);
 $partial=flipbook\front_share::product(flipbook\table_flipbook_product::t()->fetch($id));
 share_check($partial['title']==='新的商品名称'&&$partial['subtitle']===$_GET['share_subtitle']&&$partial['description']===$_GET['share_description']&&$partial['image']==='data/attachment/flipbook/audit-new-first.jpg','each blank field follows later product edits independently and the image always follows its cover');
 foreach(['share_title'=>10,'share_subtitle'=>20,'share_description'=>200] as $field=>$limit)$_GET[$field]=str_repeat('😀',$limit);
 flipbook\admin_product::save($id);$loaded=flipbook\admin_product::load($id);
 foreach(['share_title'=>10,'share_subtitle'=>20,'share_description'=>200] as $field=>$limit)share_check(mb_strlen($loaded[$field],'UTF-8')===$limit,$field.' accepts its exact Unicode code point boundary');
 $stable=share_fingerprint();
 foreach(['share_title'=>10,'share_subtitle'=>20,'share_description'=>200] as $field=>$limit){$valid=$_GET[$field];$_GET[$field].='字';$rejected=false;try{flipbook\admin_product::save($id);}catch(RuntimeException $e){$rejected=true;}
  share_check($rejected&&share_fingerprint()===$stable,$field.' rejects excess content before writing any product or related album');$_GET[$field]=$valid;
 }
 $_GET['share_title']=[];$rejected=false;try{flipbook\admin_product::save($id);}catch(RuntimeException $e){$rejected=true;}
 share_check($rejected&&share_fingerprint()===$stable,'invalid array input cannot overwrite saved share data');
 $_GET=['share_title'=>'','share_subtitle'=>'','share_description'=>''];flipbook\admin_product::save($id);
 DB::update('flipbook_product',['description'=>str_repeat('说',201),'summary'=>'副标题不是说明','name'=>'长商品名称用于分享显示一行'.str_repeat('长',35)],['productid'=>$id]);
 $saved=flipbook\table_flipbook_product::t()->fetch($id);$product=flipbook\front_view::product_detail($saved);
 share_check(mb_strlen($product['share']['description'],'UTF-8')===200&&$product['share']['description']===str_repeat('说',200),'default description uses 商品说明 and safely fits the 200-character maximum');
 $safe=$saved;$safe['spec']=json_encode(['share'=>['title'=>'<b>安全</b>','subtitle'=>"单行\n副标题",'description'=>'<i>说明</i>','image'=>'https://ignored.example/image.jpg']]);
 share_check(flipbook\front_share::product($safe)===['title'=>'安全','subtitle'=>'单行 副标题','description'=>'说明','image'=>$saved['cover']],'text is sanitized and a forged image override never replaces the automatic cover');
 $flipbookDraftConfigOnly=true;$config=include $plugin.'/product.inc.php';
 $saved['__extra']=flipbook\admin_product::load($id);ob_start();flipbook\admin_product::render($config,$saved);$html=ob_get_clean();
 share_check(strpos($html,'data-panel="overlay"')<strpos($html,'data-panel="share"'),'share settings is the final tab immediately after floating content');
 foreach(['share_title'=>10,'share_subtitle'=>20,'share_description'=>200] as $field=>$limit)share_check(strpos($html,'name="'.$field.'"')!==false&&strpos($html,'data-share-limit="'.$limit.'"')!==false,'the real editor renders '.$field.' with a Unicode limit and counter');
 share_check(strpos($html,'name="share_image"')===false&&strpos($html,'分享图片自动使用商品首图')!==false,'share settings explains automatic cover selection and has no image upload field');
 $result=['html'=>$html,'product'=>$product,'checks'=>&$checks];
}catch(Throwable $caught){$error=$caught;}finally{DB::query('ROLLBACK');$_GET=$oldGet;$_POST=$oldPost;}
if($error)throw $error;
share_check(share_fingerprint()===$before,'all original products, images, SKUs, albums and page links remain unchanged after rollback');
if(getenv('FLIPBOOK_SHARE_JSON')==='1')echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
else foreach($checks as $message)echo 'PASS '.$message."\n";
