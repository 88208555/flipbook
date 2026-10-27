<?php
// Run: docker exec -i flipbook-php php < tests/product_components.php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
function verify_product_component($value,$message){if(!$value)throw new RuntimeException($message);echo 'PASS '.$message.PHP_EOL;}
$product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);verify_product_component((bool)$product,'an actual published product supplies product information');
$_GET['productid']=intval($product['productid']);
$config=flipbook\front_micro::sanitize(['nav'=>['enabled'=>0],'tabbar'=>['enabled'=>0],'components'=>[['type'=>'product_info','props'=>['regions'=>['fieldLabel'=>['width'=>40],'heading'=>['color'=>'#123456'],'tableHead'=>['size'=>18]]]]]]);
$html=flipbook\front_micro::render(['key'=>'detail','config'=>$config]);$d=new DOMDocument();@$d->loadHTML('<?xml encoding="UTF-8">'.$html);$x=new DOMXPath($d);
verify_product_component($x->query('//*[contains(concat(" ",normalize-space(@class)," ")," fb-product-info ")]')->length===1,'formal H5 product information renders once');
verify_product_component($x->query('//*[@data-fb-region="heading" and contains(@class,"fb-info-heading")]')->length===1,'heading uses the shared semantic class without browser h3 sizing');
$spec=flipbook\front_helper::spec($product['spec']);
verify_product_component($x->query('//*[contains(@class,"fb-info-chip")]')->length===count($spec['colors'])+count($spec['sizes']),'color and size values use the same chip class on H5 and native');
$cells=0;foreach($spec['sizetable'] as $row)$cells+=count($row);
verify_product_component($x->query('//*[contains(@class,"fb-info-cell")]')->length===$cells,'all size-table cells use the shared class instead of platform text tag selectors');
verify_product_component(strpos($html,'#123456')!==false,'configured heading color survives formal rendering');
$css=file_get_contents('./source/plugin/flipbook/static/css/micro-business.css');
verify_product_component(strpos($css,'.fb-info-label { min-width:6em;flex:none;white-space:nowrap;')!==false,'generated H5 CSS shares the nonwrapping six-em label baseline');
verify_product_component(strpos($css,'.fb-info-values>text')===false && strpos($css,'.fb-info-tr>text')===false,'generated product styles have no native-only text selectors');
