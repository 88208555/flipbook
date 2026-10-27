<?php
namespace flipbook;

if(!defined('IN_DISCUZ')) exit('Access Denied');

/** One registry for independent, product-bound pieces in both renderers. */
class front_product_parts {
 public static function definitions() {
  static $parts;
  if($parts===null)$parts=json_decode(file_get_contents(dirname(__DIR__).'/static/config/product-parts.json'),true);
  return $parts;
 }

 private static function part($type,$props,$id) {
  $definition=self::definitions()[$type];$effective=front_micro::effective_regions($definition['source'],$props);$regions=array_intersect_key($effective,array_flip($definition['regions']));
  return ['id'=>substr($id,0,15).'_'.$type,'type'=>$type,'props'=>['regions'=>$regions,'caption'=>$props['caption']??[],'floating'=>$props['floating']??[],'title'=>$props['title']??($definition['title']??'产品信息'),'size'=>$props['size']??14,'color'=>$props['color']??'#F2F2F4']];
 }

 /** Replace a legacy aggregate once; subsequent edits never regenerate deleted pieces. */
 public static function expand($nodes) {
  $out=[];
  foreach($nodes as $node) {
   if(!is_array($node)){$out[]=$node;continue;}
   if(($node['type']??'')==='group'){$node['props']['children']=self::expand($node['props']['children']??[]);$out[]=$node;continue;}
   if(($node['type']??'')==='tab')foreach($node['props']['tabs']??[] as $i=>$tab)$node['props']['tabs'][$i]['children']=self::expand($tab['children']??[]);
   $type=$node['type']??'';$p=$node['props']??[];$id=$node['id']??'product';
   if($type==='product_info') {
    $children=[];
    foreach(self::definitions() as $part=>$def)if($def['source']==='product_info' && (empty($def['flag'])?($p['title']??'产品信息')!=='':($p[$def['flag']]??1)))$children[]=self::part($part,$p,$id);
   } elseif(in_array($type,['product_highlights','product_details'],true)) {
    $prefix=$type==='product_highlights'?'highlight':'detail';$children=[];
    if(($p['showTitle']??1) && ($p['title']??($prefix==='highlight'?'产品卖点':'细节展示'))!=='')$children[]=self::part('product_'.$prefix.'_title',$p,$id);
    if($p['showCode']??($prefix==='highlight'?1:0))$children[]=self::part('product_highlight_code',$p,$id);
    $list=self::part('product_'.$prefix.'_list',$p,$id);$list['props']['children']=[self::part('product_media_image',$p,$id)];
    if($p['showText']??1)$list['props']['children'][]=self::part('product_media_text',$p,$id);
    foreach($list['props']['children'] as &$child)$child['props']['mediaGroup']=$prefix;unset($child);
    $children[]=$list;
   } elseif($type==='product_detail' || (in_array($type,['social','business'],true)&&($p['source']??'')==='detail')) {
    $media=[self::part('product_gallery',$p,$id)];
    if($p['floating']['enabled']??1){$floating=self::part('product_floating',$p,$id);$floating['props']['overlay']=1;$media[]=$floating;}
    $children=[['id'=>substr($id,0,25).'_media','type'=>'group','props'=>['layoutRole'=>'media','children'=>$media]]];
    $caption=$p['caption']??[];
    if($caption['enabled']??1) {
     $captionParts=[];
     if($caption['showTitle']??1)$captionParts[]=self::part('product_title',$p,$id);
     $captionParts[]=['id'=>substr($id,0,25).'_prices','type'=>'group','props'=>['layoutRole'=>'price','children'=>[self::part('product_price',$p,$id),self::part('product_original_price',$p,$id)]]];
     $subtitle=[];
     if($caption['showCode']??1)$subtitle[]=self::part('product_code',$p,$id);
     if($caption['showSubtitle']??1)$subtitle[]=self::part('product_summary',$p,$id);
     if($subtitle)$captionParts[]=['id'=>substr($id,0,25).'_summary','type'=>'group','props'=>['layoutRole'=>'subtitle','children'=>$subtitle]];
     $children[]=['id'=>substr($id,0,25).'_caption','type'=>'group','props'=>array_merge($p['regions']['caption']??[],['layoutRole'=>'caption','children'=>$captionParts])];
    }
    $children[]=self::part('product_thumbnails',$p,$id);
    $children[]=self::part('product_buy',$p,$id);$children[]=self::part('product_contact',$p,$id);
   } else {$out[]=$node;continue;}
   if(!empty($p['regions']['container'])) {
    $container=$p['regions']['container'];
    if(isset($container['width']))$container['boxWidth']=$container['width'];
    if(isset($container['height']))$container['boxHeight']=$container['height'];
    if(isset($container['enabled'])&&!$container['enabled'])$container['display']='none';
    $container['children']=$children;$children=[['type'=>'group','props'=>$container]];
   }
   $node['type']='group';$node['props']=$p;$node['props']['children']=$children;
   unset($node['props']['caption'],$node['props']['floating'],$node['props']['source'],$node['props']['regions']);
   $out[]=$node;
  }
  return $out;
 }

 public static function options($type,$props) {
  $definition=self::definitions()[$type];
  if($definition['source']==='product_info') {
   foreach(['showColors','showSizes','showFit','showTable','showFields','showContent'] as $flag)$props[$flag]=0;
   if($definition['flag']){$props[$definition['flag']]=1;$props['title']='';}
  }
  if(in_array($definition['source'],['product_highlights','product_details'],true))$props+=['showTitle'=>1,'showCode'=>1,'showText'=>1];
  $props['sectionsDetached']=1;
  return $props;
 }

 private static $mediaContext=null;
 public static function media($group) {
  $productid=intval($_GET['productid']??0);
  if(!$productid && !empty($_GET['albumid']))$productid=front_view::first_productid(intval($_GET['albumid']));
  $row=$productid?table_flipbook_product::t()->fetch($productid):[];
  if(!$row || empty($row['available']))return [];
  $product=front_view::product_detail($row);
  return array_values(array_filter($product['groups'][$group]??[],function($item)use($productid){return !empty($item['image']) && intval($item['productid'])===$productid;}));
 }

 public static function repeat_html($type,$props,$render) {
  $definition=self::definitions()[$type];$html='<div class="fb-product-media-list" data-fb-region="list">';
  $previous=self::$mediaContext;
  try {
   foreach(self::media($definition['repeat']) as $item){self::$mediaContext=$item;$html.='<div class="fb-product-media-item" data-fb-region="item">'.$render($props['children']).'</div>';}
  } finally {self::$mediaContext=$previous;}
  return $html.'</div>';
 }

 public static function item_html($type,$props) {
  $item=self::$mediaContext??(self::media(($props['mediaGroup']??'highlight')==='detail'?'detail':'highlight')[0]??[]);
  if(!$item)return '';
  if($type==='product_media_image')return '<button class="fb-product-media-picture" type="button" data-fb-viewer="'.front_helper::e($item['image']).'" data-fb-viewer-group="product-media"><img class="fb-product-media-image" data-fb-region="image" src="'.front_helper::e($item['image']).'" alt="" /></button>';
  return '<div class="fb-product-media-text" data-fb-region="text">'.front_helper::e(html_entity_decode($item['text']?:($item['title']??''),ENT_QUOTES,'UTF-8')).'</div>';
 }

 /** Extract only the selected template region, rather than rendering hidden copies. */
 public static function fragment($html,$type) {
  $definition=self::definitions()[$type];$field=$definition['field'];
  $doc=new \DOMDocument();$old=libxml_use_internal_errors(true);
  try{$doc->loadHTML('<?xml encoding="UTF-8">'.$html);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
  $xpath=new \DOMXPath($doc);$output='';
  foreach($xpath->query('//*[@data-fb-region="'.$field.'"]') as $element) {
   if($field==='gallery')foreach($xpath->query('.//*[@data-fb-region="floatingImage"]',$element) as $floating)$floating->parentNode->removeChild($floating);
   $output.=$doc->saveHTML($element);
  }
  if($output==='' && $field==='gallery')foreach($xpath->query('//*[@data-fb-region="empty"]') as $element)$output.=$doc->saveHTML($element);
  if($field==='table')foreach($xpath->query('//*[@data-fb-region="tableNote"]') as $element)$output.=$doc->saveHTML($element);
  if(in_array($field,['gallery','thumbs'],true)) {
   $reader=$xpath->query('//*[@data-fb-product]')->item(0);$productid=$reader?$reader->getAttribute('data-fb-product'):'';
   $output='<div class="fb-web-reader" data-fb-product="'.front_helper::e($productid).'">'.$output.'</div>';
   if($field==='gallery')foreach($xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," fb-viewer ") or contains(concat(" ",normalize-space(@class)," ")," fb-share ")]') as $element)$output.=$doc->saveHTML($element);
  }
  return $output;
 }
}
