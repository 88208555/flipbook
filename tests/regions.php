<?php
// Run: docker exec -i flipbook-php php < tests/regions.php
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1'; $_SERVER['HTTP_HOST']='127.0.0.1:8088'; $_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php'; C::app()->init();
use flipbook\front_micro;
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage()."\n");exit(1);});
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$schema=front_micro::region_schema();
check(array_keys($schema)===array_keys(front_micro::region_registry()),'editor labels and renderer selectors share one component registry');
check(count($schema)===count(front_micro::types()),'every registered component has internal region configuration');
$sample=['enabled'=>0,'gapLeft'=>0,'padTop'=>17,'bg'=>'#123456','radius'=>0,'width'=>0,'height'=>75,'borderWidth'=>2,'borderColor'=>'#ffffff','borderStyle'=>'dashed','size'=>19,'weight'=>600,'color'=>'#abcdef','family'=>'serif','lineHeight'=>1.7,'letterSpacing'=>0,'align'=>'right'];
$total=0;
foreach($schema as $type=>$regions){
 $input=[];foreach($regions as $key=>$label){$input[$key]=$sample;$total++;}
 $config=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>['regions'=>$input]]]]);
 check($config['components'][0]['props']['regions']==$input,$type.' preserves every internal region and explicit zero through save sanitization');
 $empty=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>[]]]]);
 check($empty['components'][0]['props']['regions']===[],$type.' keeps unconfigured child regions inherited');
}
check($total>150,'all actual nested functional areas are covered by the shared schema');
check(front_micro::region_style([])==='' && front_micro::region_style(['bg'=>'#123456'])==='background:#123456','one child setting never resets unrelated spacing or size');
$style=front_micro::region_style($sample,true);
foreach(['display:none!important','margin-left:0px!important','padding-top:17px!important','height:75px!important','width:auto!important','font-size:19px!important','line-height:1.7!important','border-style:dashed!important'] as $part)check(strpos($style,$part)!==false,'H5 child style applies '.$part);
$bad=front_micro::region_props(['bg'=>'red;}body{display:none','family'=>'x;display:none','align'=>'x;display:none','unknown'=>'foo']);
check(front_micro::region_style($bad)==='','child style values cannot inject CSS or unknown properties');
$selectors=front_micro::all_region_selectors();
check(isset($selectors['type:search']['input']) && isset($selectors['search']['searchInput']) && !isset($selectors['search']['input']),'search component and search results use separate selector namespaces');
$css=front_micro::region_css('product_highlights',['container'=>['bg'=>'#123456']],'.fb-mc-product_highlights[data-fb-component="mc_1"]');
check(strpos($css,'data-fb-region~="container"')!==false && strpos($css,'.fb-mc-product_details')===false,'detail and highlight regions cannot overwrite one another');
check(!preg_match('/,\s*\[data-fb-region/', $css),'every comma-separated H5 selector is scoped to its owning business component');
$config=front_micro::sanitize(['nav'=>['enabled'=>0,'logo'=>['enabled'=>0],'back'=>['show'=>0]],'tabbar'=>['enabled'=>0],'components'=>[
 ['type'=>'text','props'=>['text'=>'first','regions'=>['content'=>['color'=>'#123456']]]],
 ['type'=>'text','props'=>['text'=>'second']],
 ['type'=>'search','props'=>['placeholder'=>'Find product','bg'=>'#334455','action'=>'page:search','regions'=>['input'=>['size'=>21]]]]
]]);
$html=front_micro::render(['key'=>'search','config'=>$config]);
check(strpos($html,'.fb-mc-text[data-fb-component="mc_1"]')!==false && strpos($html,'.fb-mc-text[data-fb-component="mc_2"]')===false,'two sibling text components keep child overrides isolated');
check(strpos($html,'Find product')!==false && strpos($html,'name="keyword"')!==false && strpos($html,'value="search"')!==false && strpos($html,'#334455')!==false,'configured search input keeps its text, background and real keyword destination');
check(strpos($html,'.fb-mc-search[data-fb-component="mc_3"] .fb-mc-search-input')!==false,'search child style reaches the search form rather than the product list');
echo 'PASS all region checks completed without modifying stored content or micro pages'."\n";
