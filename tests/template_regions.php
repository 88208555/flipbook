<?php
// Compile region-marked templates without executing controllers or changing content.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});require_once './source/class/class_template.php';
function region_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$registry=json_decode(file_get_contents('./source/plugin/flipbook/static/config/component-regions.json'),true);
$templates=['micro_discover'=>'social','micro_style'=>'product_detail','micro_catalog'=>'album_list','micro_favorites'=>'favorite_list','micro_mine'=>'member_center','history'=>'history_list','preference'=>'reading_preference','product_info'=>'product_info','product_media'=>['product_highlights','product_details']];
foreach($templates as $name=>$types){
 $source='./source/plugin/flipbook/template/'.$name.'.htm';$parser=new template();$compiled=$parser->parse_template($source,1,'source/plugin/flipbook/template','flipbook:'.$name);token_get_all($compiled,TOKEN_PARSE);region_check(true,'compiled PHP syntax '.$name);
 preg_match_all('/data-fb-region="([^"]*)"/s',file_get_contents($source),$matches);$tokens=[];
 foreach($matches[1] as $attribute){$plain=preg_replace('/\{[^}]*\}/s',' ',$attribute);foreach(preg_split('/\s+/',trim($plain)) as $token)if($token!=='')$tokens[$token]=true;}
 foreach((array)$types as $type)foreach(array_keys($tokens) as $token)region_check(isset($registry[$type][$token]),$name.' region '.$token.' is registered under '.$type);
}
