<?php
// Product persistence regressions. Every fixture lives inside a rolled-back transaction.
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_HOST']='127.0.0.1:8088';
$_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php';
C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});

function product_check($value,$message) {
    if(!$value)throw new RuntimeException($message);
    echo 'PASS '.$message."\n";
}
function product_fingerprint() {
    $snapshot=[];
    foreach(['flipbook_product','flipbook_sku','flipbook_supplier','flipbook_album','flipbook_page','flipbook_page_product'] as $table) {
        $rows=DB::fetch_all('SELECT * FROM %t',[$table]);
        $encoded=array_map('json_encode',$rows);sort($encoded);
        $snapshot[$table]=$encoded;
    }
    return hash('sha256',json_encode($snapshot));
}
function product_rejects($callback,$message) {
    $rejected=false;
    try{$callback();}catch(RuntimeException $error){$rejected=true;}
    product_check($rejected,$message);
}

$before=product_fingerprint();$oldGet=$_GET;
DB::query('START TRANSACTION');
try {
    $source=DB::fetch_first('SELECT * FROM %t ORDER BY productid LIMIT 1',['flipbook_product']);
    product_check((bool)$source,'an existing product supplies the schema for temporary fixtures');
    unset($source['productid']);$source['name']='transaction-only product audit';
    $source['code']='AUDIT-SPU-'.bin2hex(random_bytes(4));
    $id=DB::insert('flipbook_product',$source,true);
    $other=$source;$other['code'].='-OTHER';$otherId=DB::insert('flipbook_product',$other,true);
    $supplier=DB::insert('flipbook_supplier',['name'=>'transaction-only supplier audit','code'=>'AUDIT-SUP','note'=>'','available'=>1],true);
    $collect=new ReflectionMethod(flipbook\admin_crud::class,'collect');$collect->setAccessible(true);
    $_GET=['supplierid'=>$supplier];
    $data=$collect->invoke(null,['table'=>'flipbook_product','schema'=>['supplierid'=>['title'=>'供应商户','type'=>'select','source'=>'supplier']]]);
    DB::update('flipbook_product',$data,['productid'=>$id]);
    product_check(intval(flipbook\table_flipbook_product::t()->fetch($id)['supplierid'])===$supplier,'supplier ID survives the same schema collector used by product saves');
    DB::update('flipbook_supplier',['name'=>'renamed audit supplier'],['supplierid'=>$supplier]);
    product_check(flipbook\admin_crud::source('supplier')[$supplier]==='renamed audit supplier','supplier changes remain dynamic through its independent record');

    $prefix=$source['code'].'-SKU';
    $images=['data/attachment/flipbook/products/1/sku/2026/10/original-a.png','https://cdn.example.com/original-b.jpg?version=1'];
    $sku=['code'=>$prefix.'-01','name'=>'Original white','color'=>'米白','size'=>'M','images'=>$images];
    flipbook\front_sku::save($id,json_encode([$sku]));
    $saved=flipbook\front_sku::rows($id);
    product_check(count($saved)===1 && $saved[0]['images']===$images,'SKU save and admin reload preserve original image URLs and order');
    $stable=$saved[0];$stable['name']='Edited SKU';
    flipbook\front_sku::save($id,json_encode([$stable]));
    $updated=flipbook\front_sku::rows($id);
    product_check($updated[0]['skuid']===$saved[0]['skuid'] && $updated[0]['images']===$images && $updated[0]['name']==='Edited SKU','editing a SKU keeps its ID, image references, and new field values');
    $snapshot=json_encode($updated);
    $sameAsSpu=$stable;$sameAsSpu['code']=strtolower($source['code']);
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$sameAsSpu])),'SKU code cannot equal its product SPU even with different case');
    $sameCode=$stable;$sameCode['skuid']=0;$sameCode['code']=strtolower($stable['code']);
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$stable,$sameCode])),'same-product SKU codes are case-insensitively unique');
    $otherSku=$sku;$otherSku['code']=$prefix.'-OTHER';
    flipbook\front_sku::save($otherId,json_encode([$otherSku]));
    $crossProduct=$stable;$crossProduct['code']=strtolower($otherSku['code']);
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$crossProduct])),'SKU code cannot reuse another product SKU code');
    $foreign=$stable;$foreign['skuid']=flipbook\front_sku::rows($otherId)[0]['skuid'];
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$foreign])),'another product SKU ID cannot be injected into the current product');
    product_check(json_encode(flipbook\front_sku::rows($id))===$snapshot,'rejected SKU edits leave the existing product SKU rows unchanged');

    $twenty=$stable;$twenty['images']=[];for($i=0;$i<20;$i++)$twenty['images'][]='data/attachment/flipbook/audit-original-'.$i.'.png';
    flipbook\front_sku::save($id,json_encode([$twenty]));
    product_check(flipbook\front_sku::rows($id)[0]['images']===$twenty['images'],'all 20 allowed original SKU images survive a complete save');
    $imageSnapshot=product_fingerprint();$twentyOne=$twenty;$twentyOne['images'][]='data/attachment/flipbook/audit-original-20.png';
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$twentyOne])),'a 21st SKU image is explicitly rejected instead of truncated');
    product_check(product_fingerprint()===$imageSnapshot,'too many SKU images leaves every existing product and media reference unchanged');
    $badImages=$twenty;$badImages['images']='data/attachment/flipbook/audit.png';
    product_rejects(fn()=>flipbook\front_sku::save($id,json_encode([$badImages])),'non-array SKU image input is explicitly rejected');
    product_check(product_fingerprint()===$imageSnapshot,'invalid SKU image structure leaves the existing images unchanged');

    // Legacy products may have a size table before any SKU size attributes are configured.
    DB::delete('flipbook_sku',['productid'=>$id]);
    $_GET=['spec_colors'=>'米白,墨黑','spec_sizes'=>'S,M','spec_fit'=>'宽松','spec_model'=>'178cm / M','spec_table'=>"尺码|胸围|衣长\nS|104|66\nM||68",'spec_fields'=>'面料|100%棉','spec_content'=>"# 说明\n\n此内容属于临时商品",'overlay_lines'=>"第一行\n第二行",'floating_image'=>$images[0],'floating_link'=>'product:'.$id,'product_category'=>0,'product_series'=>0];
    flipbook\admin_product::save($id);
    $loaded=flipbook\admin_product::load($id);
    product_check($loaded['spec_table']===$_GET['spec_table'],'visual size table round trip preserves empty cells and complete rows');
    product_check($loaded['spec_content']===$_GET['spec_content'] && $loaded['spec_fields']===$_GET['spec_fields'] && $loaded['floating_image']===$images[0],'saving the size table retains other submitted product detail and floating fields');
    product_check(intval(flipbook\table_flipbook_product::t()->fetch($id)['supplierid'])===$supplier,'saving detail tabs preserves the independent supplier association');

    $longTable=['尺码|'.implode('|',array_fill(0,10,str_repeat('h',60)))];
    for($i=0;$i<20;$i++)$longTable[]='Size'.$i.'|'.implode('|',array_fill(0,10,str_repeat('x',60)));
    $_GET['spec_table']=implode("\n",$longTable);flipbook\admin_product::save($id);
    product_check(flipbook\admin_product::load($id)['spec_table']===$_GET['spec_table'],'a valid table over 10000 characters keeps every cell and row');
    $maxTable=['尺码|'.implode('|',array_fill(0,19,'测量'))];for($i=0;$i<50;$i++)$maxTable[]='Size'.$i.'|'.implode('|',array_fill(0,19,''));
    $_GET['spec_table']=implode("\n",$maxTable);flipbook\admin_product::save($id);
    product_check(flipbook\admin_product::load($id)['spec_table']===$_GET['spec_table'],'the complete 51-row and 20-column boundary preserves blank measurement values');
    $tableSnapshot=product_fingerprint();$validTable=$_GET['spec_table'];
    foreach(['52 rows'=>$validTable."\nEXTRA|",'21 columns'=>'尺码|'.implode('|',array_fill(0,20,'测量'))."\nS|",'100001 characters'=>str_repeat('x',100001)] as $label=>$invalid){
        $_GET['spec_table']=$invalid;
        product_rejects(fn()=>flipbook\admin_product::save($id),'oversized legacy size table rejects '.$label);
        product_check(product_fingerprint()===$tableSnapshot,'rejected '.$label.' does not alter the saved product or related showcase');
    }

    $chart=['enabled'=>true,'name'=>'衬衫测量表','columns'=>['胸围（cm）','衣长（cm）'],'rows'=>[['size'=>'S','mark'=>'160','values'=>['104','']],['size'=>'M','mark'=>'165','values'=>['110','68']]],'note'=>'平铺测量，允许合理误差。'];
    $_GET['sizechart']=json_encode($chart,JSON_UNESCAPED_UNICODE);flipbook\admin_product::save($id);
    $raw=json_decode(flipbook\table_flipbook_product::t()->fetch($id)['spec'],true);
    product_check($raw['sizeChart']===$chart && $raw['sizetable'][1]===['160','104',''],'structured chart keeps marks, original sizes, notes, and blank values');
    $choices=flipbook\admin_sizechart::choices($otherId);
    product_check(count(array_filter($choices,fn($choice)=>$choice['productid']===$id&&$choice['name']==='衬衫测量表'))===1,'a named saved product chart is available for other products to reuse');
    $chart['enabled']=false;$_GET['sizechart']=json_encode($chart,JSON_UNESCAPED_UNICODE);flipbook\admin_product::save($id);
    $hidden=flipbook\table_flipbook_product::t()->fetch($id);
    product_check(flipbook\front_view::product_detail($hidden)['spec']['sizetable']===[],'disabled chart disappears from the shared H5 and mini-program detail payload');
    product_check(json_decode(flipbook\admin_product::load($id)['sizechart'],true)===$chart,'hiding a chart preserves all saved measurements and metadata for editing');
    $chart['enabled']=true;$_GET['sizechart']=json_encode($chart,JSON_UNESCAPED_UNICODE);flipbook\admin_product::save($id);
    $visible=flipbook\front_view::product_detail(flipbook\table_flipbook_product::t()->fetch($id));
    product_check($visible['spec']['sizetable'][2]===['165','110','68']&&$visible['spec']['sizeTableNote']===$chart['note'],'re-enabling restores the complete table and configured measurement note');
    $chartSnapshot=product_fingerprint();
    foreach(['40-character name'=>['name'=>str_repeat('字',41)],'300-character note'=>['note'=>str_repeat('字',301)],'20-column limit'=>['columns'=>array_fill(0,20,'x')],'51-row limit'=>['rows'=>array_fill(0,51,$chart['rows'][0])],'total-character limit'=>['note'=>str_repeat('字',100001)]] as $label=>$changes){
        $_GET['sizechart']=json_encode(array_replace($chart,$changes),JSON_UNESCAPED_UNICODE);
        product_rejects(fn()=>flipbook\admin_product::save($id),'structured chart explicitly enforces '.$label);
        product_check(product_fingerprint()===$chartSnapshot,'rejected structured '.$label.' leaves all saved values unchanged');
    }
}finally{
    DB::query('ROLLBACK');$_GET=$oldGet;
}
product_check(product_fingerprint()===$before,'all product, SKU, supplier, album and page fixtures were rolled back');
