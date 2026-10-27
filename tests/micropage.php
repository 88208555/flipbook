<?php
// Run: docker exec -i flipbook-php php < tests/micropage.php
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1'; $_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php'; C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function check($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS '.$message."\n"; }
use flipbook\front_micro;
foreach(front_micro::keys() as $key=>$name) {
 $config=front_micro::default_config($key);
 check(front_micro::validate($config)==='', $key.' valid default');
 check($config['nav']['height']===44 && $config['nav']['firstGap']===($key==='detail'?0:16),$key.' unified navigation / first gap');
 check(in_array($config['components'][0]['type'],['social','product_detail','album_list','favorite_list','member_center','history_list','reading_preference','markdown']),$key.' registered content component');
}
$c=['components'=>array_fill(0,61,['type'=>'text','props'=>[]])];
check(front_micro::validate($c)!=='','reject component overflow');
$c=['components'=>[['type'=>'tab','props'=>['tabs'=>[['children'=>array_fill(0,60,['type'=>'text','props'=>[]])]]]]]];
check(front_micro::validate($c)!=='','nested nodes share global limit');
$c=front_micro::sanitize($c);check(count($c['components'][0]['props']['tabs'][0]['children'])===59,'sanitize enforces global 60 nodes');
$c=front_micro::sanitize(['components'=>[['type'=>'text','props'=>['text'=>'<script>test</script>','link'=>'url://evil.example','size'=>[]]]]]);
check($c['components'][0]['props']['text']==='test' && $c['components'][0]['props']['link']==='','text escaping and protocol validation');
check(front_micro::link_url('product:1')==='plugin.php?id=flipbook:micro&key=detail&productid=1','product links reach detail');
check(front_micro::link_url('page:unknown')==='' && front_micro::link_url('url:/../secret')==='','reject unknown / traversal links');

function micro_dom($html) {
 $document=new DOMDocument();
 $previous=libxml_use_internal_errors(true);
 try { $document->loadHTML('<meta charset="utf-8">'.$html); }
 finally { libxml_clear_errors();libxml_use_internal_errors($previous); }
 return [$document,new DOMXPath($document)];
}
function micro_nodes($xpath,$class) {
 return $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," '.$class.' ")]');
}
function micro_controller($query) {
 $previous=$_GET;$_GET=$query;ob_start();
 try { include DISCUZ_ROOT.'source/plugin/flipbook/micro.inc.php';return ob_get_clean(); }
 catch(Throwable $error) { ob_end_clean();throw $error; }
 finally { $_GET=$previous; }
}

// The logo itself must stay identical when only its containing navigation row changes.
$logoConfig=front_micro::default_config('home');
$logoConfig['components']=[];$logoConfig['tabbar']['enabled']=0;
$logoConfig['nav']['height']=44;$logoConfig['nav']['back']['show']=1;
foreach(['text','image'] as $kind) {
 $logoConfig['nav']['logo']['type']=$kind;
 $logoConfig['nav']['logo']['image']='source/plugin/flipbook/static/img/album-shirt.jpg';
 $logoConfig['nav']['logo']['text']='Logo < & test';
 $logoConfig['nav']['logo']['top']=7;
 $rendered=[];
 foreach([1,0] as $enabled) {
  $logoConfig['nav']['enabled']=$enabled;
  [$document,$xpath]=micro_dom(front_micro::render(['key'=>'home','config'=>front_micro::sanitize($logoConfig)]));
  $logos=micro_nodes($xpath,'fb-mc-logo');
  check($logos->length===1,$kind.' logo renders exactly once with navigation '.($enabled?'on':'off'));
  $rendered[$enabled]=$document->saveHTML($logos->item(0));
  $rows=micro_nodes($xpath,$enabled?'fb-mc-nav':'fb-mc-floating-logo');
  check($rows->length===1 && strpos($rows->item(0)->getAttribute('style'),'height:calc(88 * var(--fb-rpx, .5px))')!==false,$kind.' logo uses the same 44px row with navigation '.($enabled?'on':'off'));
  if(!$enabled) check(strpos($rows->item(0)->getAttribute('style'),'safe-area-inset-top')!==false,'H5 floating logo row starts after the real top safe area');
 }
 check($rendered[1]===$rendered[0],$kind.' H5 logo markup, custom top offset, dimensions and back-button spacing survive the navigation switch');
 check(strpos($rendered[1],'top:calc(14 * var(--fb-rpx, .5px))')!==false,$kind.' H5 logo retains its configured 7px offset');
}

// Roll back all persistence checks, preserving existing configuration and versions.
$beforeProducts=DB::fetch_all('SELECT * FROM %t ORDER BY productid',['flipbook_product']);
$beforePages=DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage']);
DB::query('START TRANSACTION');
try {
 $t=flipbook\table_flipbook_micropage::t(); $key='audit_cas';
 DB::delete('flipbook_micropage',['pkey'=>$key]);
 check($t->save_page($key,'test','{}',0)===1,'first save version 1');
 check($t->save_page($key,'test','{}',0)===0,'concurrent creation rejected');
 check($t->save_page($key,'test','{}',1)===2,'version increment');
 check($t->save_page($key,'test','{}',1)===0,'stale editor rejected');

 $fixture=DB::fetch_first('SELECT * FROM %t ORDER BY productid LIMIT 1',['flipbook_product']);
 check((bool)$fixture,'an existing product supplies schema for rollback-only preview fixtures');
 unset($fixture['productid']);
 $fixture['name']='transaction-only preview product';
 $fixture['code']='AUDIT-PREVIEW-'.bin2hex(random_bytes(4));$fixture['available']=1;
 $published=DB::insert('flipbook_product',$fixture,true);
 $fixture['available']=0;$fixture['code'].='-HIDDEN';
 $unpublished=DB::insert('flipbook_product',$fixture,true);
 check(front_micro::preview_productid($published)===$published,'explicit published product is accepted as preview context');
 check(front_micro::preview_productid($unpublished)===0,'unpublished product cannot become preview context');
 check(front_micro::preview_productid($unpublished+1000)===0,'nonexistent product cannot become preview context');
 $fallback=flipbook\table_flipbook_product::t()->fetch(front_micro::preview_productid());
 check($fallback && intval($fallback['available'])===1,'automatic preview context selects an actual published product');

 $oldGet=$_GET;
 try {
  $_GET=[];
  [$document,$xpath]=micro_dom(front_micro::render(['key'=>'detail','config'=>front_micro::sanitize(front_micro::default_config('detail'))]));
  check(micro_nodes($xpath,'fb-grid')->length===0 && micro_nodes($xpath,'fb-empty')->length>0,'detail without a product shows an empty state instead of silently rendering catalog');
 } finally { $_GET=$oldGet; }

 [$document,$xpath]=micro_dom(micro_controller(['key'=>'detail','business_preview'=>1]));
 check($xpath->query('//*[@data-fb-web-reader-track]')->length===1 && micro_nodes($xpath,'fb-grid')->length===0,'editor detail controller resolves real product context and renders one gallery with independent thumbnails');
 check(micro_nodes($xpath,'fb-product-info')->length===0,'social business preview excludes the separate product information module');
 [$document,$xpath]=micro_dom(micro_controller(['key'=>'catalog','business_preview'=>1]));
 check(micro_nodes($xpath,'fb-grid')->length>0 && micro_nodes($xpath,'fb-web-reader')->length===0,'catalog business preview keeps its own product list');
} finally { DB::query('ROLLBACK'); }
check(DB::fetch_all('SELECT * FROM %t ORDER BY productid',['flipbook_product'])===$beforeProducts && DB::fetch_all('SELECT * FROM %t ORDER BY pkey',['flipbook_micropage'])===$beforePages,'preview fixtures and configuration changes were completely rolled back');
