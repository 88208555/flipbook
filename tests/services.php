<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
function check($v,$m){if(!$v)throw new RuntimeException($m);echo 'PASS '.$m."\n";}
$secret='test-fixture-not-a-real-secret';$cipher=flipbook\front_service_config::encrypt($secret);
check(strpos($cipher,'enc1:')===0 && strpos($cipher,$secret)===false,'secrets encrypted at rest');
DB::query('START TRANSACTION');try{
 flipbook\table_flipbook_setting::t()->update_setting('cos_secret_key',$cipher,'test');
 check(flipbook\front_service_config::get('cos_secret_key')===$secret,'server decrypts secret');
 flipbook\table_flipbook_setting::t()->update_setting('cos_secret_key',substr($cipher,0,-3).'abc','test');
 check(flipbook\front_service_config::get('cos_secret_key')==='','tampered encrypted secret rejected');
}finally{DB::query('ROLLBACK');}
check(flipbook\front_service_config::validate(['cos_region'=>'ap-shanghai','cos_bucket'=>'album-1250000000','cos_domain'=>'https://cdn.example.com','cos_prefix'=>'album/images'])==='','COS configuration accepts regional bucket / CDN');
check(flipbook\front_service_config::validate(['cos_region'=>'ap-shanghai','cos_domain'=>'http://127.0.0.1'])!=='','COS rejects unsafe domain');
$auth=flipbook\front_media_storage::authorization('test-id','test-key','PUT','/flipbook/test.png',['host'=>'album-1250000000.cos.ap-shanghai.myqcloud.com','content-type'=>'image/png'],1600000000,1600000600);
check(substr($auth,-40)==='fe682a48c5d1593622ecd9e0170330cb49fcdb0b','COS signature matches independent reference');
$html=flipbook\front_markdown::render("# Title\n\n**bold**\n- one\n<script>alert(1)</script>\n[bad](javascript:alert(1))\n[good](https://example.com)");
check(strpos($html,'<h1>Title</h1>')!==false && strpos($html,'<strong>bold</strong>')!==false && strpos($html,'<li>one</li>')!==false,'Markdown headings emphasis and list');
check(strpos($html,'<script>')===false && strpos($html,'href="javascript:')===false,'Markdown rejects raw HTML / script links');
// Product media fixtures are rolled back; never reassign live album pictures as SKU.
DB::query('START TRANSACTION');try{
 $first=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);check((bool)$first,'one complete product is available');
 $other=$first;unset($other['productid']);$other['name']='audit-isolation-temporary';$other['code']='audit-isolation';$otherId=DB::insert('flipbook_product',$other,true);
 $rows=[['productid'=>$first['productid']],['productid'=>$otherId]];
 foreach($rows as $i=>$row){DB::insert('flipbook_sku',['productid'=>$row['productid'],'code'=>'AUDIT-SKU-'.$i,'name'=>'audit-sku-'.$i,'images'=>json_encode(['source/plugin/flipbook/static/img/sku-'.$i.'.jpg']),'displayorder'=>99]);}
 $detail=flipbook\front_view::product_detail($first);$skus=$detail['groups']['sku']??[];
 check(count($skus)>=1 && count(array_filter($skus,fn($sku)=>intval($sku['productid'])!==intval($first['productid'])))===0,'SKU comes only from the requested product');
 check(count(array_filter($skus,fn($sku)=>$sku['title']==='audit-sku-0'))===1 && count(array_filter($skus,fn($sku)=>$sku['title']==='audit-sku-1'))===0,'temporary other product SKU never leaks into this product');
}finally{DB::query('ROLLBACK');}

$key=base64_encode(str_repeat('k',32));$aes=substr($key,0,43);$appid='wx1234567890abcdef';$message='<xml><MsgType>event</MsgType></xml>';$plain=str_repeat('r',16).pack('N',strlen($message)).$message.$appid;$pad=32-(strlen($plain)%32);$plain.=str_repeat(chr($pad),$pad);$encrypted=base64_encode(openssl_encrypt($plain,'aes-256-cbc',str_repeat('k',32),OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,str_repeat('k',16)));
check(flipbook\front_wechat_push::decrypt($encrypted,$aes,$appid)===$message,'WeChat message decryption checks receiver and padding');
try{flipbook\front_wechat_push::decrypt($encrypted,$aes,'wxwrong');check(false,'wrong app rejected');}catch(RuntimeException $e){check(true,'WeChat message rejects another AppID');}
$parts=['token123','1600000000','nonce123'];sort($parts,SORT_STRING);check(flipbook\front_wechat_push::signature('token123','1600000000','nonce123')===sha1(implode('',$parts)),'WeChat callback signature is deterministic');
check(flipbook\front_share::settings()['titleTemplate']!=='','share settings include current product title template');
// Referenced managed files survive; removing the last reference releases the object.
DB::query('START TRANSACTION');$path='data/attachment/flipbook/audit-asset-'.bin2hex(random_bytes(5)).'.txt';try{
 if(!is_dir(dirname(DISCUZ_ROOT.$path)))mkdir(dirname(DISCUZ_ROOT.$path),0755,true);file_put_contents(DISCUZ_ROOT.$path,'asset lifecycle fixture');
 $asset=flipbook\front_asset::register(['productid'=>1,'scope'=>'product','purpose'=>'sku','kind'=>'image','provider'=>'local','objectkey'=>$path,'bucket'=>'','region'=>'','url'=>$path,'size'=>23]);
 $sku=DB::insert('flipbook_sku',['productid'=>1,'code'=>'AUDIT-ASSET-FIXTURE','name'=>'audit','images'=>json_encode([$path])],true);
 check(!flipbook\front_asset::remove($asset) && is_file(DISCUZ_ROOT.$path),'referenced SKU asset is protected');
 DB::delete('flipbook_sku',['skuid'=>$sku]);check(flipbook\front_asset::remove($asset) && !is_file(DISCUZ_ROOT.$path),'last reference removal deletes managed local object');
}finally{DB::query('ROLLBACK');if(is_file(DISCUZ_ROOT.$path))unlink(DISCUZ_ROOT.$path);}
// Supplier records are dynamic and independently referenced by products.
DB::query('START TRANSACTION');try{
 $supplier=DB::insert('flipbook_supplier',['name'=>'audit supplier','code'=>'SUP-AUDIT','contact'=>'','phone'=>'','address'=>'','note'=>'','displayorder'=>0,'available'=>1],true);
 $options=flipbook\admin_crud::source('supplier');check(($options[$supplier]??'')==='audit supplier','supplier selector reads independent dynamic records');
 DB::update('flipbook_product',['supplierid'=>$supplier],['productid'=>1]);
 check(intval(flipbook\table_flipbook_product::t()->fetch(1)['supplierid'])===$supplier,'product supplier association uses supplier ID');
 DB::update('flipbook_supplier',['name'=>'audit supplier renamed'],['supplierid'=>$supplier]);
 check((flipbook\admin_crud::source('supplier')[$supplier]??'')==='audit supplier renamed','supplier edits update selector without rewriting products');
}finally{DB::query('ROLLBACK');}
// Size-table edits keep the existing per-product serialization contract.
DB::query('START TRANSACTION');$oldGet=$_GET;try{
 $row=flipbook\table_flipbook_product::t()->fetch(1);unset($row['productid']);$row['name']='audit-size-table';$row['code']='AUDIT-SIZE-TABLE';$id=DB::insert('flipbook_product',$row,true);
 $_GET=['spec_colors'=>'米白','spec_sizes'=>'S,M','spec_fit'=>'宽松','spec_model'=>'','spec_table'=>"尺码|胸围|衣长\nS|104|66\nM|110|68",'spec_fields'=>'面料|棉','spec_content'=>'','product_category'=>1,'product_series'=>1];
 flipbook\admin_product::save($id);$spec=flipbook\front_helper::spec(flipbook\table_flipbook_product::t()->fetch($id)['spec']);
 check($spec['sizetable']===[['尺码','胸围','衣长'],['S','104','66'],['M','110','68']],'visual size table serializes and loads complete rows and columns');
}finally{DB::query('ROLLBACK');$_GET=$oldGet;}
