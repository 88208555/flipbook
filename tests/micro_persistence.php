<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
function persist_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function persist_flat($nodes){$out=[];foreach($nodes as $n){$out[]=$n;$out=array_merge($out,persist_flat($n['props']['children']??[]));foreach($n['props']['tabs']??[] as $t)$out=array_merge($out,persist_flat($t['children']??[]));}return $out;}
$legacy=['components'=>[['type'=>'container','props'=>['children'=>[['id'=>'old-detail','type'=>'social','props'=>['source'=>'detail','gapLeft'=>-20,'gapTop'=>-12,'regions'=>['thumbs'=>['gapLeft'=>-20],'highlights'=>['bg'=>'#123456','gapLeft'=>-8],'highlightTitle'=>['color'=>'#abcdef']]]],['type'=>'product_info','props'=>[]]]]]]];
$c=flipbook\front_micro::sanitize($legacy);$flat=persist_flat($c['components']);$types=array_column($flat,'type');
persist_check(in_array('product_gallery',$types,true)&&in_array('product_skus',$types,true)&&!in_array('home_gallery',$types,true),'the former social/detail alias resolves to detail components inside nested containers');
persist_check(count(array_filter($types,fn($t)=>$t==='product_info'))===1&&in_array('product_size_table',$types,true),'product information stays one component while the size table remains separate');
$thumb=array_values(array_filter($flat,fn($n)=>$n['type']==='product_skus'))[0];persist_check($thumb['props']['gapLeft']==-20,'nested legacy negative thumbnail margins remain configurable');
$highlights=array_filter($flat,fn($n)=>($n['props']['bg']??'')==='#123456'&&($n['props']['gapLeft']??0)==-8);persist_check(count($highlights)===1,'nested bundled media sections retain their configured background and spacing exactly once');
persist_check(json_encode($c)===json_encode(flipbook\front_micro::sanitize($c)),'opening the migrated nested tree repeatedly does not regenerate or duplicate components');
$before=flipbook\table_flipbook_micropage::t()->fetch_all_pages();DB::query('START TRANSACTION');try{$table=flipbook\table_flipbook_micropage::t();$table->save_page('audit_nested','audit',json_encode($c),0);$fresh=flipbook\front_micro::sanitize(json_decode($table->fetch_by_key('audit_nested')['config'],true));persist_check(json_encode($fresh)===json_encode($c),'negative spacing and nested order survive a real save and fresh read');}finally{DB::query('ROLLBACK');}
persist_check(flipbook\table_flipbook_micropage::t()->fetch_all_pages()===$before,'all existing page rows remain unchanged');
