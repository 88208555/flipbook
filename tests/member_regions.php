<?php
// Render the real templates using projections of existing products and transaction-only references.
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();require_once './source/class/class_template.php';
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
function member_region_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function member_region_render($name,$fb){global $_G;$parser=new template();$compiled=$parser->parse_template('./source/plugin/flipbook/template/'.$name.'.htm',1,'source/plugin/flipbook/template','flipbook:'.$name);token_get_all($compiled,TOKEN_PARSE);ob_start();eval('?>'.$compiled);return ob_get_clean();}
function member_reference_snapshot(){return [DB::fetch_all('SELECT * FROM %t ORDER BY historyid',['flipbook_history']),DB::fetch_all('SELECT * FROM %t ORDER BY uid,targettype,targetid',['flipbook_favorite'])];}
$before=member_reference_snapshot();
DB::query('START TRANSACTION');try{
 $product=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY productid LIMIT 1',['flipbook_product']);$album=DB::fetch_first('SELECT * FROM %t WHERE available=1 ORDER BY albumid LIMIT 1',['flipbook_album']);member_region_check((bool)$product&&(bool)$album,'existing published product and album supply all target content');
 $uid=max(1000000,intval(DB::result_first('SELECT MAX(uid) FROM %t',['flipbook_history'])),intval(DB::result_first('SELECT MAX(uid) FROM %t',['flipbook_favorite'])),intval(DB::result_first('SELECT MAX(uid) FROM %t',['common_member'])))+1;
 DB::insert('flipbook_favorite',['uid'=>$uid,'targettype'=>'product','targetid'=>$product['productid'],'dateline'=>TIMESTAMP]);DB::insert('flipbook_favorite',['uid'=>$uid,'targettype'=>'album','targetid'=>$album['albumid'],'dateline'=>TIMESTAMP]);
 DB::insert('flipbook_history',['uid'=>$uid,'targettype'=>'album','targetid'=>$album['albumid'],'action'=>'read','progress'=>json_encode(['page'=>1,'total'=>max(1,intval($album['pagecount']))]),'dateline'=>TIMESTAMP]);
 $resume=flipbook\front_view::resume($uid);member_region_check((bool)$resume&&isset($resume['progress']['percent']),'continue-reading projection uses the stored history and existing album');
 $mine=['options'=>flipbook\front_micro::member_options(),'member'=>['avatar'=>'','name'=>'','group'=>''],'logged'=>true,'counts'=>['favorite'=>'02','history'=>'01','share'=>'00'],'resume'=>$resume,'links'=>['favorites'=>'','history'=>''],'rows'=>[]];
 $html=member_region_render('micro_mine',$mine);$percent=$resume['progress']['percent'];
 member_region_check(strpos($html,'data-fb-region="resumeProgress" class="fb-progress" max="100" value="'.$percent.'"')!==false,'continue-reading progress renders the exact percent from the real history projection');
 member_region_check(substr_count($html,'data-fb-region="statDivider"')===2,'enabled member statistics render the two real independent divider nodes');
 unset($mine['resume']['progress']['percent']);$html=member_region_render('micro_mine',$mine);member_region_check(strpos($html,'data-fb-region="resumeProgress"')===false,'missing progress does not create an empty progress placeholder');
 $mine['resume']=[];$html=member_region_render('micro_mine',$mine);member_region_check(strpos($html,'data-fb-region="resumeProgress"')===false,'no reading history produces no progress node');
 $mine['options']['showStats']=0;$html=member_region_render('micro_mine',$mine);member_region_check(strpos($html,'data-fb-region="statDivider"')===false,'statistics dividers follow the real statistics visibility switch');
 $favorites=flipbook\front_view::favorites($uid);$fb=['tab'=>'product','taburls'=>['product'=>'','album'=>''],'countpad'=>['product'=>'01','album'=>'01'],'logged'=>true,'favorites'=>$favorites,'urls'=>['index'=>''],'loginurl'=>''];$html=member_region_render('micro_favorites',$fb);
 member_region_check(strpos($html,'data-fb-region="cardMeta" class="fb-card-meta">'.$favorites['products'][0]['code'].'</span>')!==false,'product favorite metadata displays the existing product code');
 $fb['tab']='album';$item=$favorites['albums'][0];$expected=$item['meta']??($item['code']!==''?$item['code']:$item['subtitle']);$html=member_region_render('micro_favorites',$fb);member_region_check(strpos($html,'data-fb-region="cardMeta" class="fb-card-meta">'.$expected.'</span>')!==false,'album favorite metadata uses the same real meta/code/subtitle fallback as the mini program');
 $fb['favorites']['albums'][0]['meta']=$item['subtitle'];$html=member_region_render('micro_favorites',$fb);member_region_check(strpos($html,'data-fb-region="cardMeta" class="fb-card-meta">'.$item['subtitle'].'</span>')!==false,'an existing nonempty meta field takes priority over the code');
 foreach(['meta','code','subtitle'] as $key)$fb['favorites']['albums'][0][$key]='';$html=member_region_render('micro_favorites',$fb);member_region_check(strpos($html,'data-fb-region="cardMeta"')===false,'empty metadata renders no node and reserves no placeholder');
}finally{DB::query('ROLLBACK');}
member_region_check(member_reference_snapshot()===$before,'all temporary references were rolled back without modifying target products or albums');
