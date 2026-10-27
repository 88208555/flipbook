<?php
// Read-only: compile the formal H5 templates using the existing published product/album.
// Run: docker exec -i flipbook-php php < tests/caption_visibility.php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';require './source/class/class_core.php';C::app()->init();require_once './source/class/class_template.php';
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function caption_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function caption_template($name,$fb,$flags){
 global $_G;
 $fb['captionOptions']=flipbook\front_micro::caption_options($flags);$fb['captionStyle']=flipbook\front_micro::caption_style($fb['captionOptions']);
 $fb_separate_highlights=true;$fb_separate_details=true;
 $parser=new template();$compiled=$parser->parse_template('./source/plugin/flipbook/template/'.$name.'.htm',1,'source/plugin/flipbook/template','flipbook:'.$name);token_get_all($compiled,TOKEN_PARSE);
 ob_start();eval('?>'.$compiled);$html=ob_get_clean();$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($dom);
}
function caption_data($source,$id){global $_G;$saved=$_GET;$_GET=['productid'=>$id];$fb_micro_embed=true;$fb_social_options=[];ob_start();include './source/plugin/flipbook/'.$source.'.inc.php';ob_end_clean();$_GET=$saved;return $fb;}
function caption_count($xpath,$region){return $xpath->query('//*[@data-fb-region="'.$region.'"]')->length;}
$before=DB::fetch_all('SELECT * FROM %t ORDER BY productid',['flipbook_product']);$product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);caption_check((bool)$product,'a real published product supplies the caption data');
$sources=['micro_style'=>caption_data('style',$product['productid']),'micro_discover'=>caption_data('index',$product['productid'])];
caption_check(!empty($sources['micro_discover']['heroes']),'a real published album supplies the home caption data');
foreach($sources as $name=>$data){
 $on=caption_template($name,$data,[]);$baseline=[];foreach(['caption','title','code','subtitle','extra','price','originalPrice'] as $key)$baseline[$key]=caption_count($on,$key);
 caption_check($baseline['caption']>0&&$baseline['title']>0&&$baseline['code']>0&&$baseline['subtitle']>0,$name.' default caption exposes independent real title, code and subtitle nodes');
 $gallery='//*[contains(concat(" ",normalize-space(@class)," ")," '.($name==='micro_style'?'fb-web-reader-slide':'fb-web-hero-item').' ")]';$slides=$on->query($gallery)->length;
 foreach([0,false,'0'] as $off){$hidden=caption_template($name,$data,['enabled'=>$off]);foreach($baseline as $key=>$count)caption_check(caption_count($hidden,$key)===0,$name.' whole-caption off removes '.$key.' ('.var_export($off,true).')');caption_check($hidden->query($gallery)->length===$slides,$name.' hiding text preserves every carousel item');}
 foreach(['showTitle'=>'title','showSubtitle'=>'subtitle','showCode'=>'code','showExtra'=>'extra'] as $flag=>$target){
  $hidden=caption_template($name,$data,[$flag=>0]);caption_check(caption_count($hidden,$target)===0,$name.' '.$flag.' hides only its own node');
  foreach(['caption','title','code','subtitle','price','originalPrice'] as $other)if($other!==$target)caption_check(caption_count($hidden,$other)===$baseline[$other],$name.' '.$flag.' preserves '.$other);
 }
}
$selectors=flipbook\front_micro::region_registry()['social'];
caption_check($selectors['subtitle']['selector']==='[data-fb-region~="subtitle"]','subtitle styling targets its own span, never the shared code/subtitle row');
caption_check(strpos(flipbook\front_micro::region_style(['enabled'=>1],true),'display:')===false,'an enabled style region cannot force a content-hidden caption visible');
caption_check(DB::fetch_all('SELECT * FROM %t ORDER BY productid',['flipbook_product'])===$before,'caption rendering leaves the stored product data unchanged');
