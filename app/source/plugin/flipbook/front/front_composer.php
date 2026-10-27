<?php
namespace flipbook;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** One component tree. Stored region maps are converted to independent nodes on read. */
class front_composer {
 public static function schema(){static $schema;if($schema===null)$schema=json_decode(file_get_contents(dirname(__DIR__).'/static/config/composer.json'),true);return $schema;}
 public static function types(){return array_map(function($type){return $type['label'];},self::schema()['types']);}
 public static function typography(){return self::clean_typography(json_decode(table_flipbook_setting::t()->fetch_setting('micro_typography','{}'),true));}
 public static function clean_typography($raw){$out=self::schema()['defaultTypography'];foreach($out as $role=>&$style)foreach($style as $key=>$value){if(!isset($raw[$role][$key]))continue;$input=$raw[$role][$key];if(is_numeric($value))$style[$key]=is_numeric($input)&&floatval($input)!==0.0?max(0,min($key==='weight'?900:120,(float)$input)):$value;elseif($key==='color')$style[$key]=self::color($input);elseif($key==='family')$style[$key]=in_array($input,['','sans','serif','mono'],true)?$input:'';}unset($style);return $out;}
 public static function color($value){$v=is_scalar($value)?trim((string)$value):'';return preg_match('/^(?:transparent|#[0-9a-f]{3,8}|rgba?\([0-9.,\s]+\))$/i',$v)?$v:'';}
 public static function link($value){$v=is_scalar($value)?trim((string)$value):'';if(strlen($v)>2000||preg_match('/[\x00-\x1f<>"\x27]|(?:^|\/)\.\.(?:\/|$)|^\/\//',$v))return '';if(strpos($v,'url:')===0)$v=substr($v,4);return preg_match('#^(?:page:[a-z0-9_]+(?:\?[^\s]*)?|(?:product|album):\d+|action:(?:login|share)|binding:[a-zA-Z0-9_.]+|https?://[^\s]+|/(?!/)[^\s]*|plugin\.php\?id=flipbook:[^\s]+)$#',$v)?$v:'';}
 public static function normalize_style($raw){if(isset($raw['width']))$raw['boxWidth']=$raw['width'];if(isset($raw['height']))$raw['boxHeight']=$raw['height'];$node=self::normalize([['type'=>'text','props'=>$raw]])[0]['props'];return self::style($node);}
 public static function defaults($type,$props=[]){return array_merge(self::schema()['initialStyle'],self::schema()['types'][$type]['defaults']??[],$type==='button'?(self::schema()['buttonVariants'][$props['action']??'']??[]):[]);}
 private static function node($type,$props=[],$children=null){$n=['id'=>'','type'=>$type,'props'=>$props];if($children!==null)$n['props']['children']=$children;return $n;}
 private static function style($p,$region=null){$raw=$region===null?$p:($p['regions'][$region]??[]);if(isset($raw['height'])&&!isset($raw['boxHeight']))$raw['boxHeight']=$raw['height'];if($region!==null){if(isset($raw['width']))$raw['boxWidth']=$raw['width'];if(isset($raw['height']))$raw['boxHeight']=$raw['height'];}$out=[];foreach(self::schema()['styleFields'] as $field)if(array_key_exists($field['key'],$raw))$out[$field['key']]=$raw[$field['key']];return $out;}
 private static function group($children,$props=[]){return self::node('container',array_merge(['display'=>'flex','direction'=>'column'],$props),$children);}
 private static function text($text,$binding='',$style=[]){return self::node('text',array_merge($style,['text'=>$text,'binding'=>$binding]));}
 private static function image($binding,$style=[],$link=''){return self::node('image',array_merge($style,['binding'=>$binding,'link'=>$link]));}
 private static function repeat($binding,$children,$style=[]){return self::node('repeat',array_merge(['binding'=>$binding,'display'=>'flex','direction'=>'column'],$style),$children);}

 /** Convert former names into the current tree before unknown types can be filtered out. */
 private static function legacy_node($node){
  $type=$node['type']??'';$p=$node['props']??[];
  if($type==='group'){
   $node['type']='container';$caption=self::schema()['presets']['product_caption']['nodes'][0]['props'];$heading=$caption['children'][0]['props'];$role=$p['layoutRole']??'';
   $profile=$role==='price'?$heading['children'][1]['props']:($role==='subtitle'?$caption['children'][1]['props']:$caption);unset($profile['children']);
   foreach($profile as $key=>$value)if(!array_key_exists($key,$p)||$p[$key]==='')$p[$key]=$value;
   $children=$p['children']??[];
   if($role==='caption'&&($children[0]['type']??'')==='product_title'&&($children[1]['props']['layoutRole']??'')==='price'){$row=$heading;$row['children']=array_slice($children,0,2);$p['children']=array_merge([['id'=>($node['id']??'caption').'_heading','type'=>'container','props'=>$row]],array_slice($children,2));}
  }
  if($type==='social'&&($p['source']??'')==='detail')$node['type']='product_detail';
  $aliases=['product_thumbnails'=>['product_skus','thumbs'],'product_summary'=>['product_subtitle','subtitle'],'product_highlight_code'=>['product_code','code'],'product_media_image'=>['image','image'],'product_media_text'=>['text','text']];
  $primary=['product_gallery'=>'gallery','product_title'=>'title','product_price'=>'price','product_original_price'=>'originalPrice','product_code'=>'code','product_colors'=>'colors','product_sizes'=>'sizes','product_fit'=>'fit','product_size_table'=>'sizeTable','product_content'=>'content'];
  if(isset($aliases[$type])){[$node['type'],$field]=$aliases[$type];$p=array_merge($p,self::style($p,$field));}
  elseif(isset($primary[$type])&&isset($p['regions']))$p=array_merge($p,self::style($p,$primary[$type]));
  if($type==='product_media_image'){$p['binding']='item.image';$p['preview']=$p['preview']??1;}
  if($type==='product_media_text')$p['binding']='item.textOrTitle';
  if(in_array($type,['product_highlight_title','product_detail_title','product_info_title'],true)){$node['type']='text';$p=array_merge($p,self::style($p,$type==='product_info_title'?'heading':'title'),['text'=>$p['text']??$p['title']??($type==='product_highlight_title'?'产品卖点':($type==='product_detail_title'?'细节展示':'产品信息')),'textRole'=>'title']);}
  if(in_array($type,['product_highlight_list','product_detail_list'],true)){$node['type']='repeat';$p=array_merge($p,self::style($p,'list'),['binding'=>'product.groups.'.($type==='product_highlight_list'?'highlight':'detail'),'display'=>$p['display']??'flex','direction'=>$p['direction']??'column']);if(!array_key_exists('children',$p))$p['children']=[self::image('item.image',['preview'=>1]),self::text('','item.textOrTitle')];}
  if($type==='product_fields'){$node['type']='product_info';$p=array_merge($p,['text'=>'','showColors'=>0,'showSizes'=>0,'showFit'=>0,'showFields'=>1,'showTable'=>0,'showContent'=>0]);}
  if(in_array($type,['product_buy','product_contact'],true)){$node['type']='button';$action=$type==='product_buy'?'buy':'customer';$p=array_merge($p,self::style($p,$action==='buy'?'buyButton':'customerButton'),['text'=>$p['text']??($action==='buy'?'立即购买':'联系客服'),'action'=>$action,'when'=>$action==='buy'?'commerce.enabled':'commerce.customer.enabled']);}
  if($type==='product_floating'){$node['type']='image';$p=array_merge($p,self::style($p,'floatingImage'),['binding'=>'product.spec.floatingImage.src','link'=>'binding:product.spec.floatingImage.link','when'=>'product.spec.floatingImage.src']);}
  $node['props']=$p;return $node;
 }

 private static function info_bundle($node,$legacy){
  if(!$legacy||($node['type']??'')!=='container')return null;$p=$node['props']??[];$flags=['product_colors'=>'showColors','product_sizes'=>'showSizes','product_fit'=>'showFit'];$found=[];$tail=[];$extras=[];$title='产品信息';$fields=false;
  foreach($p['children']??[] as $child){$type=$child['type']??'';
   if($type==='product_info_title'){$title=$child['props']['text']??$child['props']['title']??$title;continue;}
   if(isset($flags[$type])){if(!empty($child['props']['text']))return null;$found[$type]=true;continue;}
   if($type==='product_fields'){$fields=true;continue;}
   if(in_array($type,['product_size_table','product_content'],true)){$tail[]=$child;continue;}
   if($type==='repeat'&&($child['props']['binding']??'')==='product.spec.fields'){$fields=true;continue;}
   if($type==='text'&&empty($child['props']['binding'])&&count($found)===0){$title=$child['props']['text']??$title;continue;}
   $match=null;if($type==='container')foreach($child['props']['children']??[] as $part)if(isset($flags[$part['type']??''])){if(!empty($part['props']['text']))return null;$match=$part['type'];}
   if($match){$found[$match]=true;continue;}$extras[]=$child;
  }
  if(count($found)<2)return null;$props=array_merge(self::style($p),['text'=>$title,'showColors'=>isset($found['product_colors'])?1:0,'showSizes'=>isset($found['product_sizes'])?1:0,'showFit'=>isset($found['product_fit'])?1:0,'showFields'=>$fields?1:0,'showTable'=>0,'showContent'=>0]);
  return array_merge([['id'=>$node['id']??'','type'=>'product_info','props'=>$props]],$tail,self::upgrade($extras,$legacy));
 }

 private static function login_props($p){
  $out=array_merge(self::defaults('login_form'),self::style($p));
  foreach(self::schema()['types']['login_form']['fields'] as $f)if(array_key_exists($f['key'],$p))$out[$f['key']]=$p[$f['key']];
  $fields=['login_username'=>'usernamePlaceholder','login_password'=>'passwordPlaceholder','login_question'=>'questionPlaceholder','login_answer'=>'answerPlaceholder'];
  $scan=function($nodes)use(&$scan,&$out,$fields){foreach($nodes as $n){$type=$n['type']??'';$props=$n['props']??[];if(isset($fields[$type])){if(isset($props['placeholder']))$out[$fields[$type]]=$props['placeholder'];foreach(['minHeight'=>'controlHeight','padLeft'=>'controlPadX','padTop'=>'controlPadY','radius'=>'controlRadius','borderWidth'=>'controlBorderWidth','borderColor'=>'controlBorderColor','bg'=>'controlBg','color'=>'controlColor'] as $from=>$to)if(array_key_exists($from,$props))$out[$to]=$props[$from];}if($type==='button'){foreach(['login_wechat'=>'wechat','login_account'=>'account','login_submit'=>'submit'] as $action=>$prefix)if(($props['action']??'')===$action){foreach(['bg'=>'Bg','color'=>'Color','borderColor'=>'BorderColor'] as $from=>$suffix)if(!empty($props[$from]))$out[($prefix==='wechat'?'active':($prefix==='account'?'inactive':'submit')).$suffix]=$props[$from];}foreach(['login_wechat'=>'wechatText','login_account'=>'accountText','login_submit'=>'submitText'] as $action=>$key)if(($props['action']??'')===$action&&!empty($props['text']))$out[$key]=$props['text'];}$scan($props['children']??[]);}};
  $scan($p['children']??[]);
  foreach(['wechatBg'=>'activeBg','wechatColor'=>'activeColor','wechatBorderColor'=>'activeBorderColor','accountBg'=>'inactiveBg','accountColor'=>'inactiveColor','accountBorderColor'=>'inactiveBorderColor'] as $old=>$current)if(!array_key_exists($current,$p)&&!empty($p[$old]))$out[$current]=$p[$old];
  if(empty($out['controlBorderColor']))$out['controlBorderColor']=self::defaults('login_form')['controlBorderColor'];
  return $out;
 }

 public static function upgrade($nodes,$legacy=false){
  $out=[];foreach((array)$nodes as $old){$parts=self::upgrade_nodes([$old],$legacy);if(!empty($old['preset'])){$mark=function($items)use(&$mark){foreach($items as &$item){if(array_key_exists('preset',$item)&&empty($item['preset']))continue;$item['preset']=1;if(isset($item['props']['children']))$item['props']['children']=$mark($item['props']['children']);foreach($item['props']['tabs']??[] as $i=>$tab)$item['props']['tabs'][$i]['children']=$mark($tab['children']??[]);}unset($item);return $items;};$parts=$mark($parts);}foreach($parts as $part)$out[]=$part;}return $out;
 }
 private static function upgrade_nodes($nodes,$legacy=false){
  $out=[];foreach((array)$nodes as $old){if(!is_array($old))continue;$old=self::legacy_node($old);$bundle=self::info_bundle($old,$legacy);if($bundle!==null){foreach($bundle as $part)$out[]=$part;continue;}$type=$old['type']??'text';$p=is_array($old['props']??null)?$old['props']:[];$outer=self::style($p);if(in_array($type,['business','social','product_detail','product_highlights','product_details','member_center','album_list','favorite_list','history_list'],true)){$outer['padLeft']=0;$outer['padRight']=0;}$r=function($key)use($p){$style=self::style($p,$key);if(in_array($key,['caption','container','header','stats','menu','list'],true)){$style['padLeft']=0;$style['padRight']=0;}return $style;};
   if($type==='business')$type=['home'=>'social','detail'=>'product_detail','mine'=>'member_center','catalog'=>'album_list','search'=>'album_list','favorites'=>'favorite_list','history'=>'history_list'][$p['source']??'home']??'social';
   if(in_array($type,['reading_preference','history_tabs'],true))continue;
   if($type==='login_form'||($type==='container'&&!empty($p['form']))){$old['type']='login_form';$old['props']=self::login_props($p);$out[]=$old;continue;}
   if($type==='product_detail'){
    $parts=[self::node('product_gallery',$r('gallery'))];$caption=$p['caption']??[];
    if(($caption['enabled']??1)){
     $title=[];if($caption['showTitle']??1)$title[]=self::node('product_title',array_merge(['size'=>$caption['size']??0,'weight'=>$caption['weight']??0,'color'=>$caption['color']??'','align'=>$caption['align']??''],$r('title')));
     $prices=self::group([self::node('product_price',$r('price')),self::node('product_original_price',$r('originalPrice'))],['display'=>'flex','direction'=>'column','itemsAlign'=>'flex-end','itemGap'=>0]);$title[]=$prices;
     $sub=[];if($caption['showCode']??1)$sub[]=self::node('product_code',$r('code'));if($caption['showSubtitle']??1)$sub[]=self::node('product_subtitle',$r('subtitle'));
     $parts[]=self::group([self::group($title,['display'=>'flex','direction'=>'row','justify'=>'space-between','itemsAlign'=>'flex-start']),self::group($sub,['display'=>'flex','direction'=>'row','size'=>$caption['subSize']??0,'color'=>$caption['subColor']??''])],$r('caption'));
    }
    $parts[]=self::node('product_skus',$r('thumbs'));
    $parts[]=self::node('button',['text'=>'立即购买','action'=>'buy','when'=>'commerce.enabled']);$parts[]=self::node('button',['text'=>'联系客服','action'=>'customer','when'=>'commerce.customer.enabled']);
    $out[]=self::group($parts,$outer);continue;
   }
   if(in_array($type,['product_highlights','product_details'],true)){
    $head=[];if($p['showTitle']??1)$head[]=self::text($p['title']??($type==='product_highlights'?'产品卖点':'细节展示'),'',array_merge($r('title'),['textRole'=>'title']));if($p['showCode']??($type==='product_highlights'))$head[]=self::node('product_code',$r('code'));
    $children=[];if($head)$children[]=self::group($head,array_merge(['display'=>'flex','direction'=>'row','justify'=>'space-between','itemsAlign'=>'baseline'],$r('heading')));
    $template=[self::image('item.image',array_merge(['preview'=>1],$r('image')))];if($p['showText']??1)$template[]=self::text('','item.textOrTitle',$r('text'));
    $children[]=self::repeat('product.groups.'.($type==='product_highlights'?'highlight':'detail'),$template,$r('list'));
    $out[]=self::group($children,array_merge($outer,$r('container')));continue;
   }
   if($type==='product_info'&&(isset($p['title'])||isset($p['regions'])||!array_key_exists('text',$p))){
    $parts=[self::node('product_info',array_merge($outer,['text'=>$p['title']??$p['text']??'产品信息','showColors'=>$p['showColors']??1,'showSizes'=>$p['showSizes']??1,'showFit'=>$p['showFit']??1,'showFields'=>$p['showFields']??1,'showTable'=>0,'showContent'=>0]))];
    if($p['showTable']??1)$parts[]=self::node('product_size_table',$r('sizeTable'));
    if($p['showContent']??1)$parts[]=self::node('product_content',$r('content'));
    foreach($parts as $part)$out[]=$part;continue;
   }
   if($type==='member_center'){
    $identity=self::group([self::node('member_name',array_merge(['size'=>$p['nameSize']??0],$r('name'))),self::node('member_note',array_merge(['guestText'=>$p['note']??''],$r('note')))],$r('identity'));
    $avatar=self::node('member_avatar',array_merge(['boxWidth'=>$p['avatarSize']??0,'boxHeight'=>$p['avatarSize']??0],$r('avatar')));
    $parts=[self::group([$avatar,$identity],array_merge(['display'=>'flex','direction'=>'row','itemsAlign'=>'center','link'=>'action:login'],$r('header'))),self::node('divider')];
    if($p['showStats']??1){$stats=[];foreach(['favorite'=>'收藏','history'=>'足迹','share'=>'分享'] as $name=>$label){$stats[]=self::group([self::text('','member.counts.'.$name,$r('statValue')),self::text($label,'',$r('statLabel'))],array_merge(['grow'=>1,'link'=>$name==='favorite'?'page:favorites':'page:history'.($name==='share'?'?action=share':'')],$r('statItem')));if($name!=='share')$stats[]=self::node('divider',array_merge(['axis'=>'vertical'],$r('statDivider')));}$parts[]=self::group($stats,array_merge(['display'=>'flex','direction'=>'row'],$r('stats')));$parts[]=self::node('divider');}
    if($p['showResume']??1)$parts[]=self::group([self::image('member.resume.cover',$r('resumeCover')),self::text('','member.resume.name',$r('resumeTitle')),self::text('继续阅读','',$r('resumeNote')),self::node('progress',array_merge(['binding'=>'member.resume.progress.percent','when'=>'member.resume.progress.total'],$r('resumeProgress')))],array_merge(['when'=>'member.resume','link'=>'binding:member.resume.link'],$r('resume')));
    $menu=[];$menu[]=self::node('button',['text'=>'我的订单','action'=>'orders','when'=>'commerce.ordersEnabled']);foreach(['history'=>['阅读历史','page:history'],'share'=>['分享记录','page:history?action=share'],'about'=>['关于画册','page:about'],'privacy'=>['隐私协议','page:privacy'],'agreement'=>['注册协议','page:agreement']] as $key=>$row)if($p[$key.'Enabled']??1){$link=$p[$key.'Link']??$row[1];$menu[]=self::group([self::node('icon',array_merge(['icon'=>['history'=>'history','share'=>'share','about'=>'info','privacy'=>'locked','agreement'=>'book'][$key]],$r('menuIcon'))),self::node('button',array_merge(['text'=>$p[$key.'Label']??$row[0],'link'=>$link,'action'=>'open','grow'=>1],$r('menuText'))),self::node('icon',array_merge(['icon'=>'chevron_right'],$r('menuArrow')))],array_merge(['display'=>'flex','direction'=>'row','itemsAlign'=>'center','link'=>$link],$r('menuItem')));}
    if($p['showLogout']??1)$menu[]=self::node('button',array_merge(['text'=>'退出登录','action'=>'logout','when'=>'member.uid'],$r('logout')));
    $parts[]=self::group($menu,$r('menu'));$out[]=self::group($parts,$outer);continue;
   }
   if($type==='album_list'){
    $source=($p['source']??'catalog')==='search'?'search':'catalog';$card=self::group([self::image('item.cover',array_merge(self::schema()['presetLayouts']['cardCover'],$r('cardCover')),'binding:item.link'),self::text('','item.name',array_merge($r('cardTitle'),['textRole'=>'title'])),self::text('','item.meta',array_merge($r('cardMeta'),['textRole'=>'subtitle']))],array_merge(['link'=>'binding:item.link'],$r('card')));
    $parts=[self::node('search',array_merge(['action'=>'page:'.$source],$r('search'),$r('searchInput'))),self::node('filters',$r('filters')),self::node('series_filter',$r('seriesSheet')),self::repeat('catalog.items',[$card],array_merge(self::schema()['presetLayouts']['cardList'],$r('list')))];$out[]=self::group($parts,$outer);continue;
   }
   if($type==='favorite_list'){
    $card=self::group([self::image('item.cover',array_merge(self::schema()['presetLayouts']['cardCover'],$r('cardCover')),'binding:item.link'),self::text('','item.name',array_merge($r('cardTitle'),['textRole'=>'title'])),self::text('','item.meta',array_merge($r('cardMeta'),['textRole'=>'subtitle'])),self::node('button',array_merge(['text'=>'取消收藏','action'=>'favorite_remove'],$r('remove')))],$r('card'));
    $out[]=self::group([self::node('favorite_tabs',$r('toolbar')),self::text('最近收藏','',$r('sort')),self::repeat('favorites.items',[$card],array_merge(self::schema()['presetLayouts']['cardList'],$r('list'))),self::node('button',array_merge(['text'=>'去登录','action'=>'login','when'=>'member.guest'],$r('loginTip'))),self::text('还没有收藏','',array_merge(['when'=>'favorites.empty'],$r('empty')))],$outer);continue;
   }
   if($type==='history_list'){
    $profiles=self::schema()['presetLayouts'];$record=self::group([self::image('item.cover',array_merge($profiles['historyCover'],$r('cover')),'binding:item.link'),self::group([self::text('','item.title',array_merge($r('title'),['textRole'=>'title'])),self::text('','item.meta',$r('meta'))],$profiles['historyMain']),self::node('button',array_merge(['text'=>'继续阅读','link'=>'binding:item.link','action'=>'open'],$profiles['historyAction'],$r('action')))],array_merge($profiles['historyRow'],$r('row')));
    $out[]=self::group([self::repeat('history.groups',[self::text('','item.day',$r('dateTitle')),self::repeat('item.items',[$record],$r('list'))]),self::text('暂无记录','',array_merge(['when'=>'history.empty'],$r('empty')))],$outer);continue;
   }
   if($type==='social'){$parts=[self::node('home_gallery',array_merge($r('gallery'),['fillViewport'=>1]))];$caption=$p['caption']??[];if($caption['enabled']??1){if($caption['showTitle']??1)$parts[]=self::text('','home.current.name',array_merge(['textRole'=>'title'],['size'=>$caption['size']??0,'color'=>$caption['color']??''],$r('title')));if($caption['showSubtitle']??1)$parts[]=self::text('','home.current.subtitle',array_merge($r('subtitle'),['textRole'=>'subtitle']));}$out[]=self::group($parts,$outer);continue;}
   if($type==='view_title'){foreach($p['lines']??[] as $line){if(trim($line['text']??'')==='')continue;$out[]=self::text($line['text'],'',array_merge(self::style($line),['position'=>'absolute','anchorY'=>$line['anchor']??'bottom','offsetX'=>$line['left']??0,'offsetY'=>($line['anchor']??'bottom')==='top'?($line['top']??0):($line['bottom']??0)]));}continue;}
   if($type==='hero')$type='carousel';
   if($type==='container'||$type==='repeat')$p['children']=self::upgrade($p['children']??[],$legacy);
   // Former member-menu defaults sent both entries to read history.
   if($type==='container'&&array_filter($p['children'],function($child){return ($child['type']??'')==='icon'&&($child['props']['icon']??'')==='share';})){
    if(($p['link']??'')==='page:history')$p['link']='page:history?action=share';
    foreach($p['children'] as &$child)if(($child['type']??'')==='button'&&($child['props']['action']??'')==='open'&&($child['props']['link']??'')==='page:history')$child['props']['link']='page:history?action=share';unset($child);
   }
   if($type==='tab')foreach($p['tabs']??[] as $i=>$tab)$p['tabs'][$i]['children']=self::upgrade($tab['children']??[],$legacy);
   if(isset($p['regions'])){$primary=['text'=>'content','image'=>'media','carousel'=>'stage','video'=>'media','search'=>'control','divider'=>'line','spacer'=>'space','markdown'=>'content'][$type]??'';if($primary)$p=array_merge($p,$r($primary));}
   if(isset($p['height'])&&!isset($p['boxHeight']))$p['boxHeight']=$p['height'];unset($p['regions'],$p['caption'],$p['textStyleVersion'],$p['contentAlignVersion']);$old['type']=$type;$old['props']=$p;$out[]=$old;
  }return $out;
 }

 /** Upgrade the former automatic spacing/media defaults once; later explicit values remain editable. */
 public static function upgrade_defaults($config){
  $version=intval($config['schemaVersion']??0);if($version>=6)return $config;$schema=self::schema();
  if($version<4&&floatval($config['layout']['topGap']??0)===16.0)$config['layout']['topGap']=$schema['pageDefaults']['layout']['topGap'];
  $walk=function($nodes)use(&$walk,$schema,$version){$nodes=(array)$nodes;foreach($nodes as &$node){if(!is_array($node))continue;$p=&$node['props'];if(!is_array($p))$p=[];$type=$node['type']??'';
   if($version<4&&$type==='product_gallery'&&!array_key_exists('aspectRatio',$p)&&floatval($p['boxHeight']??0)===420.0){$p['boxHeight']=0;$p['aspectRatio']=$schema['types']['product_gallery']['defaults']['aspectRatio'];}
   if($type==='product_skus'&&empty($p['justify']))$p['justify']=$schema['types']['product_skus']['defaults']['justify'];
   $children=$p['children']??[];$types=array_column($children,'type');
   if($version<5&&$type==='container'&&$types===['product_price','product_original_price']&&floatval($p['itemGap']??12)===12.0){$p['itemGap']=0;}
   if($version<5&&$type==='container'&&($types[0]??'')==='product_title'&&($types[1]??'')==='container'&&array_column($children[1]['props']['children']??[],'type')===['product_price','product_original_price']){if(($p['itemsAlign']??'')==='baseline')$p['itemsAlign']='flex-start';$p['children'][0]['props']['grow']=1;$p['children'][0]['props']['shrink']=1;}
   if($version<5&&in_array($type,['product_price','product_original_price'],true)&&empty($p['lineHeight']))$p['lineHeight']=$schema['types'][$type]['defaults']['lineHeight'];
   $edges=['borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth'];if(!empty($p['borderWidth'])&&count(array_filter($edges,function($key)use($p){return !empty($p[$key]);}))===0)foreach($edges as $edge)$p[$edge]='';
   foreach($schema['types'][$type]['defaults']??[] as $key=>$value)if((in_array($type,['filters','favorite_tabs','history_tabs','series_filter'],true)||$key==='borderColor')&&(!array_key_exists($key,$p)||$p[$key]===''||$p[$key]===0||$p[$key]===0.0))$p[$key]=$value;
   if(isset($p['children']))$p['children']=$walk($p['children']);
   foreach($p['tabs']??[] as $i=>$tab)$p['tabs'][$i]['children']=$walk($tab['children']??[]);
  }unset($node);return $nodes;};
  $config['components']=self::upgrade_page_layouts($walk($config['components']??[]));return $config;
 }
 public static function upgrade_page_layouts($nodes,$history=false){
  $profiles=self::schema()['presetLayouts'];$apply=function($p,$defaults){foreach($defaults as $key=>$value)if(!isset($p[$key])||$p[$key]===''||$p[$key]===0||$p[$key]===0.0)$p[$key]=$value;return $p;};
  foreach($nodes as &$node){$p=&$node['props'];if(($node['type']??'')==='repeat'&&in_array($p['binding']??'',['catalog.items','favorites.items'],true)){
   if(empty($p['columns'])){$p=$apply($p,$profiles['cardList']);$p['display']='grid';}
   foreach($p['children']??[] as $i=>$card){$card['props']=$apply($card['props'],$profiles['card']);foreach($card['props']['children']??[] as $j=>$child)if(($child['type']??'')==='image'&&($child['props']['binding']??'')==='item.cover'&&empty($child['props']['boxHeight']))$card['props']['children'][$j]['props']=$apply($child['props'],$profiles['cardCover']);$p['children'][$i]=$card;}
  }
   $historyScope=$history||($p['binding']??'')==='history.groups';
   if($historyScope&&($node['type']??'')==='repeat'&&($p['binding']??'')==='item.items')foreach($p['children']??[] as $i=>$row){
    $children=$row['props']['children']??[];
    if(count($children)===4&&($children[0]['props']['binding']??'')==='item.cover'&&($children[1]['props']['binding']??'')==='item.title'&&($children[2]['props']['binding']??'')==='item.meta'){
     $row['props']=$apply($row['props'],$profiles['historyRow']);$row['props']['direction']='row';$children[0]['props']=$apply($children[0]['props'],$profiles['historyCover']);$children[3]['props']=$apply($children[3]['props'],$profiles['historyAction']);
     $main=self::group([$children[1],$children[2]],$profiles['historyMain']);$main['id']=($row['id']??'history').'_main';$main['preset']=empty($row['preset'])?0:1;$row['props']['children']=[$children[0],$main,$children[3]];$p['children'][$i]=$row;
    }
   }
   if(isset($p['children']))$p['children']=self::upgrade_page_layouts($p['children'],$historyScope);foreach($p['tabs']??[] as $i=>$tab)$p['tabs'][$i]['children']=self::upgrade_page_layouts($tab['children']??[],$historyScope);unset($p);
  }unset($node);return $nodes;
 }
 public static function needs_interface_defaults($nodes){
  foreach((array)$nodes as $node){if(in_array($node['type']??'',['business','social','product_detail','member_center','album_list','favorite_list','history_list'],true))return true;if(self::needs_interface_defaults($node['props']['children']??[]))return true;foreach($node['props']['tabs']??[] as $tab)if(self::needs_interface_defaults($tab['children']??[]))return true;}return false;
 }

 /** Materialize the requested presets once; later saved values remain the only appearance source. */
 public static function interface_defaults($nodes,$member=false,$favorites=false){
  $profiles=self::schema()['presetLayouts'];
  $contains=function($items,$needle)use(&$contains){foreach($items as $item){if(($item['type']??'')===$needle||$contains($item['props']['children']??[],$needle))return true;}return false;};
  foreach($nodes as &$node){$p=&$node['props'];$children=$p['children']??[];$types=array_column($children,'type');$memberScope=$member||$contains($children,'member_name');$favoriteScope=$favorites||$contains($children,'favorite_tabs');
   if(($node['type']??'')==='search')$p=array_merge($p,array_intersect_key(self::defaults('search'),array_flip(['borderWidth','borderColor','borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth','searchDelay','gapLeft','gapRight'])));
   if(($node['type']??'')==='series_filter')$p=array_merge($p,self::schema()['types']['series_filter']['defaults']);
   if($memberScope){
    if(in_array('member_name',$types,true)&&in_array('member_note',$types,true))$p=array_merge($p,$profiles['memberIdentity']);
    if(($node['type']??'')==='divider')$p=array_merge($p,$profiles[($p['axis']??'')==='vertical'?'memberStatDivider':'memberDivider']);
    if(($node['type']??'')==='container'){
     foreach($children as $child)if(strpos($child['props']['binding']??'','member.counts.')===0){$p=array_merge($p,$profiles['memberStatItem']);foreach($p['children'] as &$part)$part['props']['textRole']=strpos($part['props']['binding']??'','member.counts.')===0?'title':'subtitle';unset($part);break;}
     if(($p['direction']??'')==='row'&&$types===['container','divider','container','divider','container'])$p=array_merge($p,$profiles['memberStats']);
     if($types===['icon','button','icon']){$p=array_merge($p,$profiles['memberMenuRow']);$p['children'][0]['props']=array_merge($p['children'][0]['props'],$profiles['memberMenuIcon']);$p['children'][1]['props']=array_merge($p['children'][1]['props'],$profiles['memberMenuButton']);$p['children'][2]['props']=array_merge($p['children'][2]['props'],$profiles['memberMenuIcon']);}
    }
    if(($node['type']??'')==='button'&&($p['action']??'')==='logout')$p=array_merge($p,$profiles['memberMenuButton'],['icon'=>'logout','iconColor'=>$profiles['memberMenuIcon']['color']]);
   }
   if($favoriteScope&&($node['type']??'')==='text'&&($p['text']??'')==='最近收藏')$p['when']='favorites.hasItems';
   if(isset($p['children']))$p['children']=self::interface_defaults($p['children'],$memberScope,$favoriteScope);
   foreach($p['tabs']??[] as $i=>$tab)$p['tabs'][$i]['children']=self::interface_defaults($tab['children']??[],$memberScope,$favoriteScope);
   $children=$p['children']??[];$search=null;$series=null;
   foreach($children as $i=>$child){if($child['type']==='search')$search=$i;if($child['type']==='series_filter')$series=$i;}
   if($search!==null&&$series!==null){$children[$search]['props']=array_merge($children[$search]['props'],['grow'=>1,'shrink'=>1]);$row=self::group([$children[$search],$children[$series]],$profiles['searchRow']);$row['id']=$node['id'].'_search';$row['preset']=1;$new=[];foreach($children as $i=>$child){if($i===$search)$new[]=$row;elseif($i!==$series)$new[]=$child;}$p['children']=$new;}
   unset($p);
  }unset($node);
  $types=array_column($nodes,'type');if(in_array('home_gallery',$types,true)&&!in_array('home_actions',$types,true)){$node=self::node('home_actions');$gallery=$nodes[array_search('home_gallery',$types,true)];$node['id']=$gallery['id'].'_actions';$node['preset']=1;$nodes[]=$node;}
  return $nodes;
 }

 /** Materialize the shared menu icon color once, retaining later explicit edits. */
 public static function menu_icon_defaults($nodes){
  foreach($nodes as &$node){$p=&$node['props'];if(($node['type']??'')==='button'&&($p['action']??'')==='logout'&&empty($p['iconColor']))$p['iconColor']=self::schema()['presetLayouts']['memberMenuIcon']['color'];if(isset($p['children']))$p['children']=self::menu_icon_defaults($p['children']);foreach($p['tabs']??[] as $i=>$tab)$p['tabs'][$i]['children']=self::menu_icon_defaults($tab['children']??[]);unset($p);}unset($node);return $nodes;
 }

 public static function normalize($nodes,$depth=0,$path='components',$baseline=false){
  if($depth>12)return [];$out=[];$schema=self::schema();foreach((array)$nodes as $index=>$node){if(!is_array($node)||!isset($schema['types'][$node['type']??'']))continue;$type=$node['type'];$raw=is_array($node['props']??null)?$node['props']:[];$p=self::defaults($type,$raw);
   foreach($schema['styleFields'] as $field){$key=$field['key'];if(!array_key_exists($key,$raw))continue;$value=$raw[$key];if(!empty($field['inheritEmpty'])&&($value===''||$value===null)){$p[$key]='';continue;}if($baseline&&((is_numeric($value)&&floatval($value)===0.0&&is_numeric($p[$key]??null)&&floatval($p[$key])>0)||($value===''&&!empty($p[$key]))))continue;if($field['type']==='number')$p[$key]=is_numeric($value)?max(in_array($key,['gapTop','gapRight','gapBottom','gapLeft','offsetX','offsetY','letterSpacing','zIndex'],true)?-2400:0,min($key==='weight'?900:2400,(float)$value)):0;elseif($field['type']==='select')$p[$key]=in_array($value,$field['options'],true)?$value:'';else $p[$key]=self::color($value);}
   if($baseline&&in_array($type,['container','product_info'],true)){foreach([['gapLeft','gapRight'],['padLeft','padRight']] as $edges)if(floatval($p[$edges[0]])===floatval($schema['pageDefaults']['layout']['sideGap'])&&floatval($p[$edges[1]])===floatval($schema['pageDefaults']['layout']['sideGap'])){$p[$edges[0]]=0;$p[$edges[1]]=0;}}
   foreach(['text','binding','src','poster','alt','placeholder','action','link','guestText','when','icon','axis','markdown','field'] as $key)if(isset($raw[$key])&&is_scalar($raw[$key]))$p[$key]=mb_substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/','',(string)$raw[$key]),0,$key==='markdown'?30000:2000,'UTF-8');
   foreach(['link'] as $key)if(isset($p[$key]))$p[$key]=self::link($p[$key]);
   foreach(['src','poster'] as $key)if(isset($p[$key]))$p[$key]=front_helper::img($p[$key]);
   foreach(['form','preview','autoplay','playVideo','muted','controls','posterOnEnd','fillViewport','showColors','showSizes','showFit','showTable','showFields','showContent'] as $key)if(array_key_exists($key,$raw))$p[$key]=empty($raw[$key])?0:1;
   foreach(['interval','thickness','itemWidth','itemHeight','value'] as $key)if(array_key_exists($key,$raw)&&!($baseline&&floatval($raw[$key])===0.0&&!empty($p[$key])))$p[$key]=max(0,min(60000,(float)$raw[$key]));
   foreach($schema['types'][$type]['fields'] as $field){$key=$field['key'];if(!array_key_exists($key,$raw)||in_array($key,['binding','text','src','poster','action','link','placeholder','icon','interval','thickness','itemWidth','itemHeight','value'],true))continue;$value=$raw[$key];if($field['type']==='number')$p[$key]=is_numeric($value)?max(0,min(2400,(float)$value)):0;elseif($field['type']==='select')$p[$key]=in_array($value,$field['options']??[],true)?$value:(self::defaults($type)[$key]??'');elseif($field['type']==='color')$p[$key]=self::color($value)?:(!empty($field['inheritDefault'])?self::defaults($type)[$key]:'');elseif($field['type']==='bool')$p[$key]=empty($value)?0:1;elseif($field['type']==='text')$p[$key]=mb_substr(strip_tags(is_scalar($value)?(string)$value:''),0,2000,'UTF-8');}
   if(isset($raw['items'])){$p['items']=[];foreach(array_slice((array)$raw['items'],0,100) as $item)if(is_array($item))$p['items'][]=['image'=>front_helper::img($item['image']??''),'video'=>front_helper::img($item['video']??''),'title'=>strip_tags($item['title']??''),'link'=>self::link($item['link']??'')];}
   if(in_array($type,['container','repeat'],true))$p['children']=self::normalize($raw['children']??[], $depth+1,$path.'.'.$index.'.children',$baseline);
   if($type==='tab'){$p['tabs']=[];foreach(array_slice((array)($raw['tabs']??[]),0,20) as $tabIndex=>$tab)$p['tabs'][]=['title'=>mb_substr((string)($tab['title']??''),0,100,'UTF-8'),'children'=>self::normalize($tab['children']??[],$depth+1,$path.'.'.$index.'.tabs.'.$tabIndex,$baseline)];}
   if(in_array($schema['types'][$type]['defaults']['textRole']??'',['title','subtitle'],true))$p['textRole']=$schema['types'][$type]['defaults']['textRole'];
   $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($node['id']??''));$out[]=['id'=>$id?:'c_'.substr(hash('sha256',$path.'.'.$index),0,12),'type'=>$type,'preset'=>empty($node['preset'])?0:1,'props'=>$p];
  }return $out;
 }

 public static function context($key,$productid=0){
  $uid=front_helper::uid();$settings=front_view::settings();$keyword=is_scalar($_GET['keyword']??'')?(string)($_GET['keyword']??''):'';$catid=max(0,intval($_GET['catid']??0));$seriesid=max(0,intval($_GET['seriesid']??0));$albumid=max(0,intval($_GET['albumid']??0));$productid=$productid?:max(0,intval($_GET['productid']??0));$requestedTarget=$productid||$albumid;$readPage=max(1,intval($_GET['page']??1));$album=$albumid?table_flipbook_album::t()->fetch($albumid):[];
  if($albumid&&!$productid&&!empty($album['available'])){$pages=front_view::pages($albumid);$readPage=min($readPage,max(1,count($pages)));foreach($pages[$readPage-1]['products']??[] as $id){$candidate=table_flipbook_product::t()->fetch($id);if(!empty($candidate['available'])){$productid=intval($id);break;}}}
  if(!$productid&&!$requestedTarget&&$key==='detail')$productid=front_micro::preview_productid();
  if($albumid&&empty($album['available']))$productid=0;
  $row=$productid?table_flipbook_product::t()->fetch($productid):[];$product=$row&&!empty($row['available'])?front_view::product_detail($row):[];$slides=[];
  if(!empty($product['video']))$slides[]=['image'=>$product['firstframe']??$product['cover'],'video'=>$product['video'],'title'=>$product['name'],'skuid'=>0];if(!empty($product['cover']))$slides[]=['image'=>$product['cover'],'title'=>$product['name'],'skuid'=>0];foreach($product['groups']['sku']??[] as $item)if(!empty($item['image']))$slides[]=array_merge($item,['title'=>$item['title']??$product['name']]);
  $product['spec']['colorsText']=implode(' ',(array)($product['spec']['colors']??[]));$product['spec']['sizesText']=implode(' ',(array)($product['spec']['sizes']??[]));$product['spec']['fitText']=trim(($product['spec']['fit']??'').' '.($product['spec']['model']??''));$product['slides']=$slides;if(!empty($product['spec']['content'])&&empty($product['spec']['contentHtml']))$product['spec']['contentHtml']=front_markdown::render($product['spec']['content']);$product['salePrice']=!empty($product['price']['value'])?($product['price']['currency']==='CNY'?'¥':$product['price']['currency']).$product['price']['value']:'';$product['originalPrice']=!empty($product['price']['original'])?'¥'.$product['price']['original']:'';
  $albums=$key==='home'?front_view::albums($seriesid,$catid,$keyword):[];foreach($albums as &$album){$album['image']=$album['cover'];$album['link']='page:detail?albumid='.$album['albumid'];$album['meta']=front_helper::pad($album['pagecount']).' 页';}unset($album);
  $paging=[];$lists=[];foreach(array_keys(self::schema()['lists']) as $binding){$list=front_list::supports($binding,$key)?front_list::page($binding):['items'=>[]];$lists[$binding]=$list['items'];if(isset($list['pagination']))$paging[$binding]=$list['pagination'];}
  $catalog=$lists['catalog.items'];
  $keywordQuery=$keyword!==''?'&keyword='.rawurlencode($keyword):'';
  $categories=[['label'=>'全部','value'=>0,'selected'=>!$catid,'link'=>'page:'.$key.'?catid=0&seriesid='.$seriesid.$keywordQuery]];foreach(front_view::category_list() as $item)$categories[]=['label'=>$item['name'],'value'=>$item['catid'],'selected'=>intval($item['catid'])===$catid,'link'=>'page:'.$key.'?catid='.$item['catid'].'&seriesid='.$seriesid.$keywordQuery];
  $series=[['label'=>'全部系列','value'=>0,'selected'=>!$seriesid,'link'=>'page:'.$key.'?seriesid=0&catid='.$catid.$keywordQuery]];foreach(front_view::series_list() as $item)$series[]=['label'=>$item['name'],'value'=>$item['seriesid'],'selected'=>intval($item['seriesid'])===$seriesid,'link'=>'page:'.$key.'?seriesid='.$item['seriesid'].'&catid='.$catid.$keywordQuery];
  $fav=['counts'=>front_list::favorite_counts($uid)];foreach($albums as &$album)$album['favorited']=$uid>0&&table_flipbook_favorite::t()->is_favorite($uid,'album',$album['albumid']);unset($album);
  $counts=front_view::history_counts($uid);$member=front_auth::member();$member['guest']=!$uid;$member['note']=$uid?$member['group']:'登录后同步收藏与阅读记录';$member['counts']=['favorite'=>front_helper::pad($fav['counts']['total']??0),'history'=>front_helper::pad($counts['read']??0),'share'=>front_helper::pad($counts['share']??0)];$member['resume']=front_view::resume($uid);if($member['resume'])$member['resume']['link']=$member['resume']['progress']['readurl']??('album:'.$member['resume']['albumid']);
  $tab=($_GET['tab']??'')==='album'?'album':'product';$items=$lists['favorites.items'];
  $action=($_GET['action']??$_GET['tab']??'')==='share'?'share':'read';$history=$lists['history.groups'];
  return ['key'=>$key,'readTarget'=>$key==='detail'&&!empty($product['productid'])?($albumid?['targettype'=>'album','targetid'=>$albumid,'page'=>$readPage]:['targettype'=>'product','targetid'=>intval($product['productid'])]):null,'pagination'=>$paging,'login'=>['wechat'=>false,'account'=>true,'binding'=>false,'error'=>''],'query'=>['keyword'=>$keyword,'catid'=>$catid,'seriesid'=>$seriesid,'tab'=>$tab,'action'=>$action,'albumid'=>$albumid,'page'=>$readPage],'product'=>$product,'home'=>['albums'=>$albums,'current'=>$albums[0]??[]],'catalog'=>['items'=>$catalog,'categories'=>$categories,'series'=>$series],'member'=>$member,'favorites'=>['items'=>array_values($items),'empty'=>$uid>0&&!$items,'hasItems'=>!!$items,'tabs'=>[['label'=>'款式 '.front_helper::pad($fav['counts']['product']??0),'link'=>'page:favorites?tab=product','selected'=>$tab==='product'],['label'=>'画册 '.front_helper::pad($fav['counts']['album']??0),'link'=>'page:favorites?tab=album','selected'=>$tab==='album']]],'history'=>['groups'=>$history,'empty'=>!$history,'action'=>$action],'commerce'=>front_commerce::state(),'loginUrl'=>front_helper::login_url(),'returnUrl'=>front_helper::login_return($_GET['return']??''),'formhash'=>front_helper::formhash()];
 }
}
