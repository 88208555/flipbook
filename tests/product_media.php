<?php
// Read-only rendering of independent components against the existing real product image groups.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';require './source/class/class_core.php';C::app()->init();require_once './source/class/class_template.php';
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function product_media_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function product_media_compile($name){$parser=new template();$compiled=$parser->parse_template('./source/plugin/flipbook/template/'.$name.'.htm',1,'source/plugin/flipbook/template','flipbook:'.$name);token_get_all($compiled,TOKEN_PARSE);return $compiled;}
function product_media_render($fb_media,$fb_media_options,$fb_media_code,$fb_media_group){global $_G;$compiled=product_media_compile('product_media');ob_start();eval('?>'.$compiled);return ob_get_clean();}
function product_media_style($id,$highlights,$details){global $_G;$saved=$_GET;$_GET=['productid'=>$id];$fb_micro_embed=true;$fb_social_options=[];$fb_separate_highlights=$highlights;$fb_separate_details=$details;ob_start();include './source/plugin/flipbook/style.inc.php';ob_end_clean();$compiled=product_media_compile('micro_style');ob_start();eval('?>'.$compiled);$html=ob_get_clean();$_GET=$saved;return $html;}
$before=DB::fetch_all('SELECT * FROM %t ORDER BY mediaid',['flipbook_product_media']);$product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);product_media_check((bool)$product,'a real published product supplies the independent component data');$view=flipbook\front_view::product_detail($product);
foreach(['highlight'=>['product_highlights','产品卖点'],'detail'=>['product_details','细节展示']] as $group=>$config){
 $items=$view['groups'][$group];$valid=array_values(array_filter($items,fn($item)=>!empty($item['hasimage'])&&$item['image']!==''));$options=['title'=>$config[1],'size'=>14,'color'=>'#F2F2F4','showTitle'=>1,'showText'=>1,'showCode'=>$group==='highlight'?1:0];$html=product_media_render($items,$options,$view['code'],$group);
 product_media_check(strpos($html,'data-fb-product-component="'.$config[0].'"')!==false,$config[0].' has its own component root and container');
 product_media_check(substr_count($html,'data-fb-region="image"')===count($valid),$config[0].' renders only this product real valid image group');
 foreach($valid as $item)product_media_check(strpos($html,'src="'.$item['image'].'"')!==false,$config[0].' preserves real media '.$item['mediaid']);
 product_media_check(strpos($html,'data-fb-product-title')!==false&&strpos($html,'data-fb-region="title"')!==false,$config[0].' exposes a title node independently from the code');
 product_media_check((strpos($html,'data-fb-product-code')!==false)===($group==='highlight'),$config[0].' follows its configured code visibility');
 product_media_check(strpos($html,'class="fb-detail-')===false&&strpos($html,'object-fit:')===false,$config[0].' uses shared semantic classes without old spacing or carousel-fit overrides');
 $off=array_replace($options,['showTitle'=>0,'showText'=>0,'showCode'=>0]);$hidden=product_media_render($items,$off,$view['code'],$group);
 product_media_check(strpos($hidden,'data-fb-product-title')===false&&strpos($hidden,'data-fb-product-code')===false&&strpos($hidden,'data-fb-product-text')===false,$config[0].' hides its own title text and code without removing images');
 product_media_check(substr_count($hidden,'data-fb-region="image"')===count($valid),$config[0].' keeps the same image group when text controls are disabled');
 $empty=product_media_render([],$options,$view['code'],$group);product_media_check(strpos($empty,'data-fb-product-empty')!==false&&strpos($empty,'<img')===false,$config[0].' empty data explains the missing media without fabricating images');
}
$legacy=product_media_style($product['productid'],false,false);$separate=product_media_style($product['productid'],true,true);
product_media_check(strpos($legacy,'data-group="highlight"')!==false&&strpos($legacy,'data-group="detail"')!==false,'legacy social detail keeps its real inline groups when independent-component flags are absent');
product_media_check(strpos($separate,'data-group="highlight"')===false&&strpos($separate,'data-group="detail"')===false,'independent-component flags suppress the duplicate social image groups');
product_media_check(substr_count($legacy,'class="fb-web-reader-slide"')===substr_count($separate,'class="fb-web-reader-slide"'),'extracting the image groups does not change any main carousel slide');
product_media_check(DB::fetch_all('SELECT * FROM %t ORDER BY mediaid',['flipbook_product_media'])===$before,'rendering independent media components does not change stored media');
