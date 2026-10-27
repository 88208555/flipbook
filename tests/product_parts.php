<?php
// Current component migration contract; writes are limited to the audit's temporary tables.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='localhost:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($e){file_put_contents('php://stderr','FAIL '.$e->getMessage()."\n");exit(1);});
function part_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function part_flat($nodes){$out=[];foreach($nodes as $n){$out[]=$n;$out=array_merge($out,part_flat($n['props']['children']??[]));foreach($n['props']['tabs']??[] as $tab)$out=array_merge($out,part_flat($tab['children']??[]));}return $out;}
$old=['schemaVersion'=>1,'components'=>[['id'=>'legacy-root','type'=>'group','props'=>['gapLeft'=>-8,'children'=>[
 ['id'=>'main','type'=>'product_gallery','props'=>['regions'=>['gallery'=>['height'=>340,'radius'=>9]]]],
 ['id'=>'caption','type'=>'group','props'=>['layoutRole'=>'caption','children'=>[['id'=>'title','type'=>'product_title','props'=>['regions'=>['title'=>['color'=>'#abcdef']]]],['id'=>'prices','type'=>'group','props'=>['layoutRole'=>'price','children'=>[['id'=>'price','type'=>'product_price','props'=>[]],['id'=>'original','type'=>'product_original_price','props'=>[]]]]],['id'=>'subtitle-row','type'=>'group','props'=>['layoutRole'=>'subtitle','children'=>[['id'=>'code','type'=>'product_code','props'=>[]],['id'=>'summary','type'=>'product_summary','props'=>[]]]]]]]],
 ['id'=>'thumbs','type'=>'product_thumbnails','props'=>['regions'=>['thumbs'=>['gapTop'=>-12,'radius'=>7]]]],
 ['id'=>'highlight','type'=>'product_highlight_title','props'=>['title'=>'原有卖点标题']],['id'=>'highlight-list','type'=>'product_highlight_list','props'=>[]],
 ['id'=>'info','type'=>'group','props'=>['children'=>[['type'=>'product_info_title','props'=>['title'=>'原有产品信息']],['type'=>'product_colors','props'=>[]],['type'=>'product_sizes','props'=>[]],['type'=>'product_fit','props'=>[]],['type'=>'product_fields','props'=>[]],['id'=>'table','type'=>'product_size_table','props'=>[]],['id'=>'content','type'=>'product_content','props'=>[]]]]],
 ['id'=>'buy','type'=>'product_buy','props'=>[]],['id'=>'contact','type'=>'product_contact','props'=>[]],['id'=>'floating','type'=>'product_floating','props'=>[]]
]]]]];
$c=flipbook\front_micro::sanitize($old);$flat=part_flat($c['components']);$byId=array_column($flat,null,'id');$types=array_column($flat,'type');
part_check(flipbook\front_micro::validate($c)===''&&json_encode($c)===json_encode(flipbook\front_micro::sanitize($c)),'all former component names become one valid and idempotent current tree');
part_check($byId['legacy-root']['type']==='container'&&$byId['legacy-root']['props']['gapLeft']==-8,'former group containers retain their contents, identity and negative outer margin');
part_check($byId['main']['props']['boxHeight']==340&&$byId['main']['props']['radius']==9,'former gallery-region dimensions transfer to visible component settings');
part_check($byId['thumbs']['type']==='product_skus'&&$byId['thumbs']['props']['gapTop']==-12&&$byId['thumbs']['props']['radius']==7,'former thumbnail names, spacing and image corners survive conversion');
part_check($byId['summary']['type']==='product_subtitle'&&$byId['title']['props']['color']==='#abcdef','subtitle content and explicit title color remain configured');
part_check($byId['caption_heading']['props']['direction']==='row'&&$byId['prices']['props']['itemsAlign']==='flex-end'&&$byId['subtitle-row']['props']['direction']==='row','former title/price and subtitle layouts use the canonical editable row configuration');
part_check(count(array_filter($types,fn($t)=>$t==='product_info'))===1&&$byId['info']['props']['text']==='原有产品信息','former separate product-information fields combine into exactly one product information component');
part_check($byId['table']['type']==='product_size_table'&&$byId['content']['type']==='product_content','the size table and product content retain separate component identities');
part_check($byId['highlight-list']['type']==='repeat'&&count($byId['highlight-list']['props']['children'])===2,'former media lists retain their image and caption templates');
part_check($byId['buy']['props']['action']==='buy'&&$byId['contact']['props']['action']==='customer'&&$byId['floating']['props']['binding']==='product.spec.floatingImage.src','purchase, customer and floating media keep their actual functions');
part_check(!str_contains(json_encode($c['components']),'"regions"'),'the converted document stores no second internal-region style configuration');
$before=flipbook\table_flipbook_micropage::t()->fetch_all_pages();DB::query('START TRANSACTION');try{$table=flipbook\table_flipbook_micropage::t();$version=$table->save_page('audit_aliases','audit',json_encode($c),0);$fresh=flipbook\front_micro::sanitize(json_decode($table->fetch_by_key('audit_aliases')['config'],true));part_check($version===1&&json_encode($fresh)===json_encode($c),'a real versioned save and fresh read retain the converted tree exactly');}finally{DB::query('ROLLBACK');}
part_check(flipbook\table_flipbook_micropage::t()->fetch_all_pages()===$before,'the migration fixture leaves all stored configurations unchanged');
