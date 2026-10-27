<?php
// Isolated connection-local temporary tables exercise real SQL without changing business rows.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function list_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function list_ids($page){return array_column($page['items'],'listId');}
function history_records($page){$items=[];foreach($page['items'] as $group)foreach($group['items'] as $item)$items[]=$item;return $items;}
$product=DB::fetch_first('SELECT * FROM %t ORDER BY productid LIMIT 1',['flipbook_product']);$album=DB::fetch_first('SELECT * FROM %t ORDER BY albumid LIMIT 1',['flipbook_album']);
list_check((bool)$product&&(bool)$album,'real row shapes are available for isolated list fixtures');
$tables=['flipbook_album','flipbook_product','flipbook_favorite','flipbook_history'];
foreach($tables as $table){$definition=DB::fetch_first('SHOW CREATE TABLE %t',[$table]);DB::query(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$definition['Create Table']));}
$uid=9000001;$_G['uid']=$uid;
for($i=1;$i<=23;$i++){
 DB::insert('flipbook_album',array_merge($album,['albumid'=>90000+$i,'name'=>'List Fixture '.$i,'available'=>1,'displayorder'=>$i,'catid'=>770,'seriesid'=>771]));
 DB::insert('flipbook_product',array_merge($product,['productid'=>90000+$i,'name'=>'List Fixture '.$i,'available'=>1,'displayorder'=>$i,'code'=>'LIST-'.$i]));
 foreach(['album','product'] as $type)DB::insert('flipbook_favorite',['uid'=>$uid,'targettype'=>$type,'targetid'=>90000+$i,'dateline'=>TIMESTAMP-$i]);
 foreach(['read','share'] as $action)DB::insert('flipbook_history',['uid'=>$uid,'targettype'=>'product','targetid'=>90000+$i,'action'=>$action,'progress'=>'','dateline'=>TIMESTAMP-($i>15?86400:0)-$i]);
}
$_GET=[];$pages=[];for($p=1;$p<=3;$p++)$pages[]=flipbook\front_list::page('catalog.items',$p);
list_check(array_map(fn($p)=>count($p['items']),$pages)===[10,10,3],'catalog requests return 10, 10 and the final 3 records');
list_check(count(array_unique(array_merge(...array_map('list_ids',$pages))))===23,'catalog page boundaries do not duplicate or skip rows');
list_check($pages[0]['pagination']['nextPage']===2&&$pages[1]['pagination']['hasMore']&&!$pages[2]['pagination']['hasMore']&&$pages[2]['pagination']['nextPage']===null,'the final page stops further loading without a visible page counter');
list_check(array_unique(array_column($pages[0]['items'],'meta'))===[''],'album cards contain no page-count label');
$_GET=['keyword'=>'List Fixture'];$mixed=[];for($p=1;$p<=5;$p++)$mixed=array_merge($mixed,flipbook\front_list::page('catalog.items',$p)['items']);
list_check(count($mixed)===46&&count(array_unique(array_column($mixed,'listId')))===46,'search pagination crosses the album/product boundary with distinct identities');
$_GET=['keyword'=>'List Fixture','catid'=>770,'seriesid'=>771];$filtered=flipbook\front_list::page('catalog.items',3);
list_check(count($filtered['items'])===3&&!$filtered['pagination']['hasMore'],'category and series filtering preserve bounded pagination and the product-album relation');
$_GET=['keyword'=>'%'];list_check(flipbook\front_list::page('catalog.items')['items']===[],'literal search wildcards do not broaden the query');
foreach(['album','product'] as $tab){$_GET=['tab'=>$tab];$first=flipbook\front_list::page('favorites.items');$second=flipbook\front_list::page('favorites.items',2);$last=flipbook\front_list::page('favorites.items',3);list_check(count($first['items'])===10&&count($second['items'])===10&&count($last['items'])===3&&!array_intersect(list_ids($first),list_ids($second)),'favorites '.$tab.' uses the same 10-record protocol and keeps its selected tab');}
foreach(['read','share'] as $action){$_GET=['action'=>$action,'uid'=>$uid+1];$first=flipbook\front_list::page('history.groups');$second=flipbook\front_list::page('history.groups',2);$last=flipbook\front_list::page('history.groups',3);list_check(count(history_records($first))===10&&count(history_records($second))===10&&count(history_records($last))===3&&count($second['items'])===2,'history '.$action.' paginates records across date groups without losing grouping');}
$_G['uid']=$uid+1;list_check(flipbook\front_list::page('favorites.items')['items']===[]&&flipbook\front_list::page('history.groups')['items']===[],'request UID cannot read a different member list');
$_G['uid']=0;$_GET=['uid'=>$uid];list_check(flipbook\front_list::page('favorites.items')['items']===[]&&flipbook\front_list::page('history.groups')['items']===[],'guest favorite and history lists remain empty');
list_check(flipbook\front_list::supports('catalog.items','search')&&!flipbook\front_list::supports('favorites.items','catalog')&&!flipbook\front_list::supports('arbitrary.rows','mine'),'only registered list/page combinations are accepted');
foreach($tables as $table)DB::query('DROP TEMPORARY TABLE `'.DB::table($table).'`');
list_check((bool)DB::fetch_first('SELECT * FROM %t WHERE productid=%d',['flipbook_product',$product['productid']]),'temporary fixtures leave the original business tables intact');
