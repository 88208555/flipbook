<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
require '/tmp/flipbook-audit-tables.php';
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
function activity_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
$_G['uid']=900001;$_POST['uid']=900002;
$product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);$id=intval($product['productid']);
flipbook\front_action::record_read('product',$id);flipbook\front_action::record_read('product',$id);
activity_check(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND action=%s',['flipbook_history',900001,'read']))===1,'repeated product visits update the current member footprint without duplicate rows');
activity_check(intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d',['flipbook_history',900002]))===0,'a supplied request UID cannot receive or change another member footprint');
$before=DB::result_first('SELECT COUNT(*) FROM %t',['flipbook_history']);
foreach([['private',1],['product',2147483647]] as $target){try{flipbook\front_action::record_read(...$target);throw new LogicException('Invalid target was accepted');}catch(InvalidArgumentException $e){}}
activity_check(DB::result_first('SELECT COUNT(*) FROM %t',['flipbook_history'])==$before,'unavailable and unknown activity targets cannot create records');
$_G['uid']=0;try{flipbook\front_action::record_read('product',$id);throw new LogicException('Guest was accepted');}catch(InvalidArgumentException $e){}
activity_check(DB::result_first('SELECT COUNT(*) FROM %t',['flipbook_history'])==$before,'guest reading cannot write member history');
$album=DB::fetch_first('SELECT * FROM %t ORDER BY albumid LIMIT 1',['flipbook_album']);$page=DB::fetch_first('SELECT * FROM %t ORDER BY pageid LIMIT 1',['flipbook_page']);$link=DB::fetch_first('SELECT * FROM %t LIMIT 1',['flipbook_page_product']);
$newProduct=DB::insert('flipbook_product',array_merge($product,['productid'=>900002,'code'=>'AUDIT-READ-PAGE-2']),true);
DB::insert('flipbook_album',array_merge($album,['albumid'=>900001,'pagecount'=>2,'available'=>1]));
foreach([1=>$id,2=>$newProduct] as $i=>$pid){DB::insert('flipbook_page',array_merge($page,['pageid'=>900000+$i,'albumid'=>900001,'available'=>1,'displayorder'=>$i]));DB::insert('flipbook_page_product',array_merge($link,['pageid'=>900000+$i,'productid'=>$pid]));}
$_GET=['albumid'=>900001,'page'=>2];$ctx=flipbook\front_composer::context('detail');
activity_check(intval($ctx['product']['productid'])===$newProduct&&$ctx['readTarget']===['targettype'=>'album','targetid'=>900001,'page'=>2],'continuing an album opens its saved page product and retains the authoritative reading target');
$_G['uid']=900001;$result=flipbook\front_action::record_read('album',900001,999);
activity_check($result['page']===2&&$result['total']===2,'album progress is clamped to the real album page count');
DB::update('flipbook_album',['available'=>0],['albumid'=>900001]);$ctx=flipbook\front_composer::context('detail');
activity_check(empty($ctx['product']['productid'])&&$ctx['readTarget']===null,'an offline album never falls back to an unrelated preview product');
$_GET=['albumid'=>2147483647];$ctx=flipbook\front_composer::context('detail');activity_check(empty($ctx['product']['productid'])&&$ctx['readTarget']===null,'a nonexistent album does not display or track an unrelated product');
