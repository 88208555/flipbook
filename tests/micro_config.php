<?php
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php';C::app()->init();
$root=getenv('FLIPBOOK_PLUGIN_ROOT')?:'/var/www/html/source/plugin/flipbook';
require $root.'/front/front_micro.php';
use flipbook\front_micro;
set_exception_handler(function($error){file_put_contents('php://stderr',$error->getMessage()."\n");exit(1);});
$checks=[];
function check_config($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks[]=$label;}
if(isset($roundTrip)){
 echo json_encode(front_micro::sanitize($roundTrip),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
$style=['size'=>19,'weight'=>500,'color'=>'#123456','bg'=>'#F1F2F3','align'=>'right','gapTop'=>0,'gapBottom'=>17,'padLeft'=>9,'radius'=>6,'borderWidth'=>2,'borderColor'=>'#456789','offsetX'=>11,'offsetY'=>-7,'display'=>'flex','direction'=>'column','itemsAlign'=>'flex-end','justify'=>'space-between','wrap'=>'wrap','itemGap'=>7,'grow'=>0,'shrink'=>1,'minWidth'=>0,'maxWidth'=>280,'borderBottomWidth'=>0];
$components=[];
foreach(front_micro::region_schema() as $type=>$regions){
 if(!isset(front_micro::types()[$type]) || in_array($type,['product_detail','product_info','product_highlights','product_details'],true))continue;
 $key=$type==='member_center'?'name':array_key_first($regions);
 $components[]=['id'=>$type,'type'=>$type,'props'=>['regions'=>[$key=>$style],'caption'=>['align'=>'right','size'=>19,'subSize'=>12,'color'=>'#123456'],'color'=>'#123456','size'=>19,'align'=>'right','direction'=>'column','display'=>'flex','itemGap'=>7]];
}
$config=front_micro::sanitize(['components'=>$components]);
foreach($config['components'] as $component){
 $p=$component['props'];$type=$component['type'];$key=$type==='member_center'?'name':array_key_first(front_micro::region_schema()[$type]);
 foreach($style as $field=>$value)check_config((is_int($value)||is_float($value)?isset($p['regions'][$key][$field])&&(float)$p['regions'][$key][$field]===(float)$value:($p['regions'][$key][$field]??null)===$value),$type.' keeps configured '.$field);
 check_config($p['direction']==='column'&&$p['itemGap']===7,$type.' retains outer layout configuration');
 $css=front_micro::region_style($p['regions'][$key]);
 check_config(strpos($css,'font-size:19px')!==false&&strpos($css,'gap:7px')!==false&&strpos($css,'margin-top:0px')!==false,$type.' H5 consumes configured values, including zero');
}
foreach(['text','markdown','product_title','social'] as $type){
 $one=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>['align'=>'left','size'=>14,'color'=>'#F2F2F4','caption'=>['align'=>'left','size'=>22,'subSize'=>13,'color'=>'#FFFFFF']]]]])['components'][0]['props'];
 check_config($one['align']==='left'&&($one['color']??'#F2F2F4')==='#F2F2F4',$type.' never restyles stored text while reading');
 if(isset($one['caption']))check_config($one['caption']['align']==='left'&&$one['caption']['subSize']===13&&$one['caption']['color']==='#FFFFFF',$type.' never replaces saved captions');
}
foreach(['text'=>['content','size'],'markdown'=>['paragraph','size']] as $type=>$binding){
 $initial=front_micro::sanitize(['components'=>[['type'=>$type,'props'=>[]]]]);
 $initial['components'][0]['props'][$binding[1]]=27;
 $reload=front_micro::sanitize($initial)['components'][0]['props'];
 check_config(front_micro::effective_regions($type,$reload)[$binding[0]]['size']===27,$type.' top-level settings remain effective after save and reload');
 check_config(!isset($reload['regions'][$binding[0]]['size']),$type.' defaults never become user overrides');
}
$member=front_micro::sanitize(['components'=>[['type'=>'member_center','props'=>[]]]]);
$member['components'][0]['props']['nameSize']=21;$member['components'][0]['props']['avatarSize']=52;
$member=front_micro::sanitize($member)['components'][0]['props'];
check_config(front_micro::effective_regions('member_center',$member)['name']['size']===21&&front_micro::effective_regions('member_center',$member)['avatar']['width']===52,'member convenience settings share the original configuration instead of being shadowed by defaults');
$hairline=front_micro::region_props(['width'=>0.5,'height'=>24]);check_config($hairline['width']===0.5,'fractional divider width is retained instead of becoming auto');
$border=front_micro::region_style(['borderWidth'=>0,'borderTopWidth'=>0.5,'borderBottomWidth'=>0.5]);check_config(strpos($border,'border-width:0px')<strpos($border,'border-top-width:0.5px'),'specific divider borders follow the general border reset');
$standard=front_micro::effective_regions('member_center');check_config($standard['header']['padLeft']===0&&$standard['header']['padRight']===0,'member header has no fixed horizontal inset');
$hidden=front_micro::region_style(['display'=>'flex','enabled'=>0]);
check_config(strrpos($hidden,'display:none')>strrpos($hidden,'display:flex'),'hidden layout remains hidden when a flex layout is configured');
$pageGaps=front_micro::sanitize(['nav'=>['enabled'=>0,'back'=>['show'=>1,'link'=>'page:catalog','style'=>['bg'=>'#234567','color'=>'#FEDCBA','width'=>42,'radius'=>9]]],'layout'=>['topGap'=>31,'bottomGap'=>0],'components'=>[]]);
check_config($pageGaps['layout']['topGap']===31&&$pageGaps['layout']['bottomGap']===0,'page top and bottom gaps remain independent of navigation visibility');
check_config(front_micro::sanitize($pageGaps)===$pageGaps,'save and reopen preserve the complete page configuration including the back control');
$backHtml=front_micro::render(['key'=>'mine','config'=>$pageGaps]);
check_config(strpos($backHtml,'fb-mc-back-anchor is-floating')!==false&&strpos($backHtml,'#234567')!==false&&strpos($backHtml,'#FEDCBA')!==false,'H5 keeps the configured back control when the navigation bar is hidden');
foreach(['social','product_detail','album_list','favorite_list','history_list','member_center'] as $type){
 $regions=front_micro::effective_regions($type,['regions'=>['button'=>['bg'=>'#246813','color'=>'#FAEBCD','radius'=>7]]]);
 check_config($regions['button']['bg']==='#246813'&&$regions['button']['color']==='#FAEBCD'&&$regions['button']['radius']===7,$type.' action buttons use the shared configuration and retain explicit overrides');
 check_config(isset(front_micro::region_schema()[$type]['emptyTitle'])&&isset($regions['emptyText']['size']),$type.' exposes built-in empty-state text controls');
}
$return=flipbook\front_helper::login_return('plugin.php?id=flipbook:micro&key=detail&productid=1&login=1');
check_config($return==='plugin.php?id=flipbook:micro&key=detail&productid=1','login returns to the original micro-page without reopening login');
check_config(flipbook\front_helper::login_return('https://example.com/')===flipbook\front_helper::url('mine'),'login rejects external return destinations');
$loginHtml=front_micro::login_html(front_micro::page('mine'),$return);
check_config(strpos($loginHtml,'data-fb-login-form')!==false&&strpos($loginHtml,'fb-login-document')!==false&&strpos($loginHtml,'data-fb-login-panel')===false&&strpos($loginHtml,'fb-member-name')===false&&strpos($loginHtml,'fb-mc-tabbar')===false,'H5 login is an independent document without member content, modal, or bottom navigation');
$duplicates=front_micro::sanitize(['components'=>[['id'=>'member_first','type'=>'member_center','props'=>['regions'=>['name'=>['color'=>'#123456']]]],['id'=>'member_second','type'=>'member_center','props'=>['regions'=>['name'=>['color'=>'#654321']]]]]]);
check_config(front_micro::validate($duplicates)==='','all component types may be copied without an unrelated business-component restriction');
$duplicateHtml=front_micro::render(['key'=>'mine','config'=>$duplicates]);
check_config(strpos($duplicateHtml,'.fb-mc-member_center[data-fb-component="mc_1"] [data-fb-region~="name"]{')!==false&&strpos($duplicateHtml,'.fb-mc-member_center[data-fb-component="mc_2"] [data-fb-region~="name"]{')!==false&&strpos($duplicateHtml,'.fb-mc-business[data-source="mine"] [data-fb-region')===false,'H5 business component styles are isolated by instance and never overwrite another component');
$_GET=['key'=>'mine','business_preview'=>1];ob_start();include DISCUZ_ROOT.'source/plugin/flipbook/micro.inc.php';$minePreview=ob_get_clean();
check_config(strpos($minePreview,'data-fb-login-panel')===false&&strpos($minePreview,'data-fb-login-form')===false,'mine never embeds or automatically opens the login form');
echo json_encode(['loginHtml'=>$loginHtml,'minePreview'=>$minePreview,'checks'=>$checks,'config'=>$config,'regions'=>front_micro::region_schema(),'selectors'=>front_micro::all_region_selectors(),'sources'=>front_micro::region_sources(),'componentDefaults'=>front_micro::component_defaults(),'types'=>front_micro::types(),'keys'=>front_micro::keys(),'defaults'=>front_micro::text_presets()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
