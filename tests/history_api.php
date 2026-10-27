<?php
// History response is scoped to the native member session. Fixtures are always rolled back.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function history_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function history_ids($groups){$ids=[];foreach($groups as $items)foreach($items as $item)$ids[]=$item['historyid'];return $ids;}
$savedGet=$_GET;$savedUid=$_G['uid'];$before=DB::fetch_all('SELECT * FROM %t ORDER BY historyid',['flipbook_history']);
$history=new ReflectionMethod(flipbook\front_api::class,'history');$history->setAccessible(true);
DB::query('START TRANSACTION');try{
 $product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);history_check((bool)$product,'a real available product supplies the history target');
 $uid=max(1000000,intval(DB::result_first('SELECT MAX(uid) FROM %t',['flipbook_history'])),intval(DB::result_first('SELECT MAX(uid) FROM %t',['common_member'])))+1;
 $otherUid=$uid+1;$base=['uid'=>$uid,'targettype'=>'product','targetid'=>$product['productid'],'action'=>'read','progress'=>json_encode(['page'=>2,'total'=>4]),'dateline'=>TIMESTAMP];
 $read=DB::insert('flipbook_history',$base,true);$share=DB::insert('flipbook_history',array_replace($base,['action'=>'share']),true);$other=DB::insert('flipbook_history',array_replace($base,['uid'=>$otherUid]),true);
 $_G['uid']=$uid;$_GET=['action'=>'read','uid'=>$otherUid];$data=$history->invoke(null);
 history_check($data['action']==='read'&&history_ids($data['groups'])===[$read],'read history returns only the current native session member records');
 history_check($data['counts']===['read'=>1,'share'=>1],'history counts include the current member read and share totals');
 $item=array_values($data['groups'])[0][0];history_check($item['title']===flipbook\front_helper::e($product['name'])&&$item['progress']==='02 / 04'&&$item['percent']===50&&$item['url']!=='','history keeps the real product title, navigation link, and stored reading progress');
 history_check(!in_array($other,history_ids($data['groups']),true),'request UID cannot expose another member history');
 $_GET['action']='share';$data=$history->invoke(null);history_check($data['action']==='share'&&history_ids($data['groups'])===[$share],'share entry returns the current member real share history');
 foreach(['read'=>$read,'share'=>$share] as $action=>$expected){
  $_GET=['action'=>$action];$context=flipbook\front_composer::context('history');$ids=[];foreach($context['history']['groups'] as $group)foreach($group['items'] as $record)$ids[]=$record['historyid'];
  history_check($ids===[$expected]&&$context['history']['action']===$action&&!isset($context['history']['tabs']),'shared '.$action.' page contains only matching records and no switching-tab data');
 }
 $_GET['action']='delete';$data=$history->invoke(null);history_check($data['action']==='read'&&history_ids($data['groups'])===[$read],'unknown history action safely falls back to read');
 $_G['uid']=0;$_GET=['uid'=>$uid,'action'=>'share'];$data=$history->invoke(null);history_check($data['groups']===[]&&$data['counts']===['read'=>0,'share'=>0],'guest history remains empty even when a request supplies a member UID');
}finally{DB::query('ROLLBACK');$_GET=$savedGet;$_G['uid']=$savedUid;}
history_check(DB::fetch_all('SELECT * FROM %t ORDER BY historyid',['flipbook_history'])===$before,'all temporary history fixtures were rolled back');
$legacy=['schemaVersion'=>8,'layout'=>['topGap'=>3,'componentGap'=>7],'components'=>[['id'=>'records','type'=>'container','preset'=>1,'props'=>['itemGap'=>11,'children'=>[['id'=>'old-switch','type'=>'history_tabs','preset'=>1,'props'=>[]],['id'=>'rows','type'=>'repeat','preset'=>1,'props'=>['binding'=>'history.groups','children'=>[['id'=>'day','type'=>'text','props'=>['binding'=>'item.day']]]]]]]]]];
$clean=flipbook\front_micro::sanitize($legacy);
history_check(array_column($clean['components'][0]['props']['children'],'id')===['rows'],'existing nested record switches are removed while preset record identities remain');
history_check($clean['layout']['topGap']==3&&$clean['layout']['componentGap']==7&&$clean['components'][0]['props']['itemGap']==11,'removing the switch does not change page or record spacing');
history_check(flipbook\front_micro::validate($legacy)===''&&$clean==flipbook\front_micro::sanitize($clean),'old configurations import and roundtrip without reintroducing the switch');
$defaults=flipbook\front_micro::sanitize(flipbook\front_micro::default_config('history'));$types=[];$walk=function($nodes)use(&$walk,&$types){foreach($nodes as $node){$types[]=$node['type'];$walk($node['props']['children']??[]);}};$walk($defaults['components']);
history_check(!in_array('history_tabs',$types,true)&&!isset($defaults['schema']['types']['history_tabs']),'new history pages and the component library no longer offer the removed switch');
$menu=function($icon,$link){return ['id'=>'menu-'.$icon,'type'=>'container','props'=>['link'=>$link,'itemGap'=>9,'children'=>[['id'=>'icon-'.$icon,'type'=>'icon','props'=>['icon'=>$icon]],['id'=>'link-'.$icon,'type'=>'button','props'=>['action'=>'open','link'=>$link]]]]];};
$menus=flipbook\front_micro::sanitize(['schemaVersion'=>8,'components'=>[$menu('share','page:history'),$menu('history','page:history'),$menu('share','page:about')]])['components'];
history_check($menus[0]['props']['link']==='page:history?action=share'&&$menus[0]['props']['children'][1]['props']['link']==='page:history?action=share','existing share-menu row and button both open share records instead of read history');
history_check($menus[1]['props']['link']==='page:history'&&$menus[2]['props']['link']==='page:about'&&$menus[0]['props']['itemGap']==9,'read-history destinations, custom links and configured menu styles are preserved');
