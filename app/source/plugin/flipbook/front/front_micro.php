<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 微页面渲染引擎
 *
 * 一个微页面由三部分组成：
 * 1. nav：是否显示导航、Logo（文字/图片）、返回按钮、与首个组件间距；
 * 2. components：可排序、可嵌套（tab）的组件列表，每个组件带独立的间距/圆角/背景/链接；
 * 3. tabbar：是否显示底部导航。
 *
 * 约束：
 * 1. 所有进入 HTML 的值都经过本类的清洗与转义，配置中的文本不允许携带标签；
 * 2. 链接只接受微页面键、画册/款式编号、站内路径与 http(s) 地址；
 * 3. 渲染结果不依赖外网资源，组件样式以内联形式输出，结构样式集中在 flipbook.css；
 * 4. 保存时版本号递增，H5 直接读库，小程序按版本号热加载。
 */

namespace flipbook;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_micro {
 private static $customKeys=null;

	public static function clear_custom_keys() {
		self::$customKeys = null;
	}

	public static function is_custom_key($key) {
		return is_string($key) && preg_match('/\Amicro_[a-f0-9]{16}\z/', $key) === 1;
	}

	public static function builtin_keys() {
		return [
			'home' => '首页',
			'detail' => '详情页',
			'favorites' => '收藏页',
			'catalog' => '分类页',
			'search' => '搜索页',
			'mine' => '我的页',
			'history' => '阅读与分享记录',
   'about'=>'关于画册','privacy'=>'隐私协议','agreement'=>'注册协议','login'=>'登录页',
		];
	}
	public static function routes($key) {
		if(!is_string($key) || !isset(self::keys()[$key])) return [];
		return ['link' => 'page:'.$key, 'h5' => front_helper::url('micro', ['key' => $key]), 'mini' => '/pages/micro/index?key='.$key];
	}


	/**
	 * 页面标识 => 名称。每个页面独立配置，互不影响。
	 */
	public static function keys() {
		if(self::$customKeys === null) {
			self::$customKeys = [];
			foreach(table_flipbook_micropage::t()->fetch_custom_names() as $row) {
				if(self::is_custom_key($row['pkey'])) self::$customKeys[$row['pkey']] = $row['name'];
			}
		}
		return self::builtin_keys() + self::$customKeys;
	}

	/** Use a published product as the editor's read-only detail context. */
	public static function preview_productid($productid = 0) {
		$productid = max(0, intval($productid));
		if($productid) {
			$row = table_flipbook_product::t()->fetch($productid);
			return $row && !empty($row['available']) ? $productid : 0;
		}
		return intval(\DB::result_first('SELECT productid FROM %t WHERE available=1 ORDER BY displayorder, productid LIMIT 1', ['flipbook_product']));
	}

	/**
	 * 支持的组件类型 => 名称。
	 */

 public static function types(){return front_composer::types();}

	public static function key($value) {
		$value = preg_replace('/[^a-z0-9_]/', '', strtolower(is_scalar($value) ? (string)$value : ''));
		if($value==='preference')return 'mine';
		return isset(self::keys()[$value]) ? $value : 'home';
	}

	public static function name($key) {
		$names = self::keys();
		return isset($names[$key]) ? $names[$key] : '微页面';
	}

	// ------------------------------------------------------------------
	// 配置读取
	// ------------------------------------------------------------------

	/**
	 * 读取页面配置：数据库中无记录时使用受控默认值，保证前台永远可渲染。
	 */
	public static function page($key, $preview = false) {
		$key = self::key($key);
		$row = table_flipbook_micropage::t()->fetch_by_key($key);
		$stored = $row && !empty($row['config']) ? json_decode((string)$row['config'], true) : [];
		if(is_array($stored)&&!array_key_exists('schemaVersion',$stored))$stored['schemaVersion']=2;
		$config = self::sanitize(self::merge(self::default_config($key), is_array($stored) ? $stored : []));
  $presets=self::sanitize(self::default_config($key));
  foreach(front_composer::schema()['presetAliases'][$key]??[] as $alias)$presets['components'][]=['id'=>$alias['id']??('c_'.substr(hash('sha256',$alias['path']),0,12)),'type'=>$alias['type'],'preset'=>1,'props'=>[]];
  $config=self::mark_presets($config,$presets);
		return [
			'key' => $key,
			'name' => $row && $row['name'] !== '' ? $row['name'] : self::name($key),
			'version' => $row ? max(1, intval($row['version'])) : 0,
			'config' => $config,
		];
	}

 public static function mark_presets($config,$defaults){
  $ids=[];$collect=function($nodes)use(&$collect,&$ids){foreach($nodes as $node){if(!empty($node['preset']))$ids[$node['id']]=$node['type'];$collect($node['props']['children']??[]);foreach($node['props']['tabs']??[] as $tab)$collect($tab['children']??[]);}};$collect($defaults['components']??[]);
  $mark=function($nodes)use(&$mark,$ids){foreach($nodes as &$node){if(($ids[$node['id']]??'')===$node['type'])$node['preset']=1;if(isset($node['props']['children']))$node['props']['children']=$mark($node['props']['children']);foreach($node['props']['tabs']??[] as $i=>$tab)$node['props']['tabs'][$i]['children']=$mark($tab['children']??[]);}unset($node);return $nodes;};$config['components']=$mark($config['components']);return $config;
 }
 public static function validate_presets($before,$after){
  $after=self::sanitize($after);
  $required=[];$present=[];$walk=function($nodes,$protected)use(&$walk,&$required,&$present){foreach((array)$nodes as $node){$id=$node['id']??'';if($protected){if(!empty($node['preset']))$required[$id]=$node['type'];}else $present[$id]=$node['type']??'';$walk($node['props']['children']??[],$protected);foreach($node['props']['tabs']??[] as $tab)$walk($tab['children']??[],$protected);}};$walk($before['components']??[],true);$walk($after['components']??[],false);foreach($required as $id=>$type)if(($present[$id]??'')!==$type)return '预设组件不能删除；仅可删除自行添加的组件，未保存';return '';
 }

	/**
	 * 所有微页面的版本号，供小程序启动时比对是否需要热加载。
	 */
	public static function versions() {
		$data = [];
		foreach(table_flipbook_micropage::t()->fetch_all_pages() as $row) {
			if(isset(self::keys()[$row['pkey']]))$data[$row['pkey']] = intval($row['version']);
		}
		foreach(array_keys(self::keys()) as $key) {
			if(!isset($data[$key])) {
				$data[$key] = 0;
			}
		}
		return $data;
	}

	/** Reject data loss before sanitizing the editable document. */

 public static function validate($config){
  if(!is_array($config)||!is_array($config['components']??null))return '配置需要包含组件数组，未保存';
  if(!is_array($config['tabbar']['items']??[])||count($config['tabbar']['items']??[])>5)return '底部导航最多 5 项，未保存';
  foreach($config['tabbar']['items']??[] as $item){try{front_nav_icon::sanitize($item['iconSvg']??'');}catch(\RuntimeException $error){return $error->getMessage().'，未保存';}}
  $nodes=front_composer::upgrade($config['components'],intval($config['schemaVersion']??0)<3);$count=0;
  $walk=function($items,$depth)use(&$walk,&$count){if(!is_array($items)||$depth>12)return '组件嵌套最多 12 层，未保存';foreach($items as $node){if(++$count>500)return '每页最多 500 个组件，未保存';if(!is_array($node)||!isset(self::types()[$node['type']??'']))return '包含不支持的组件类型，未保存';if(!is_array($node['props']??[]))return '组件配置格式错误，未保存';$p=$node['props']??[];foreach(['items'=>100,'tabs'=>20] as $key=>$limit)if(isset($p[$key])&&(!is_array($p[$key])||count($p[$key])>$limit))return '组件条目数量或格式不正确，未保存';if(isset($p['children'])){$error=$walk($p['children'],$depth+1);if($error)return $error;}foreach($p['tabs']??[] as $tab){$error=$walk($tab['children']??[],$depth+1);if($error)return $error;}}return '';};return $walk($nodes,0);
 }


	public static function save($key, $config) {
		$key = self::key($key);
		$before=self::page($key)['config'];$error=self::validate_presets($before,$config);if($error)throw new \RuntimeException($error);$clean = self::mark_presets(self::sanitize($config),$before);unset($clean['schema'],$clean['typography'],$clean['globalTypographyDirty']);
		return table_flipbook_micropage::t()->save_page($key, self::name($key), json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * 默认配置：与当前视觉一致；后续新增字段由 sanitize 自动补齐。
	 */

 public static function default_config($key){
  $config=['schemaVersion'=>7,'layout'=>['bg'=>'','color'=>'','size'=>0,'componentGap'=>0,'topGap'=>0,'bottomGap'=>0],'nav'=>['enabled'=>1,'height'=>0,'bg'=>'','logo'=>['enabled'=>1,'type'=>'text','text'=>'FLIPBOOK','height'=>0,'fontSize'=>0,'color'=>'','offsetLeft'=>0,'offsetRight'=>0,'link'=>'page:home'],'back'=>['show'=>in_array($key,['detail','search','history','about','privacy','agreement','login'],true)?1:0,'link'=>'page:mine']],'tabbar'=>['enabled'=>$key==='login'?0:1],'components'=>[]];
  $defaults=front_composer::schema()['pageDefaults'];$config['viewer']=$defaults['viewer'];$config['layout']=array_merge($config['layout'],$defaults['layout']);$config['nav']=self::merge($config['nav'],$defaults['nav']);$config['nav']['back']['style']=$defaults['back'];$config['tabbar']=array_merge($config['tabbar'],$defaults['tabbar']);
  if(self::is_custom_key($key)){$config['nav']['back']['show']=1;$config['tabbar']['enabled']=0;return $config;}
  if($key==='login')$config['components']=[['id'=>'login_form','type'=>'login_form','props'=>[]]];
  elseif(in_array($key,['about','privacy','agreement'],true))$config['components']=[['id'=>'document_'.$key,'type'=>'markdown','props'=>['markdown'=>'# '.self::name($key)."\n\n请在后台微页面中编辑内容。"]]];
  else{$type=['home'=>'social','detail'=>'product_detail','mine'=>'member_center','catalog'=>'album_list','search'=>'album_list','favorites'=>'favorite_list','history'=>'history_list'][$key]??'text';$config['components']=[['id'=>'page_'.$key,'type'=>$type,'props'=>['source'=>$key]]];if($key==='detail')foreach(['product_highlights','product_details','product_info'] as $type)$config['components'][]=['type'=>$type,'props'=>[]];}
  foreach($config['components'] as &$component)$component['preset']=1;unset($component);
  return $config;
 }


	/**
	 * 浅合并默认值：已有对象递归合并，数组（组件、列表）以用户配置为准。
	 */
	protected static function merge($base, $over) {
		foreach($over as $key => $value) {
			if(is_array($value) && isset($base[$key]) && is_array($base[$key]) && !self::is_list($base[$key])) {
				$base[$key] = self::merge($base[$key], $value);
			} else {
				$base[$key] = $value;
			}
		}
		return $base;
	}

	protected static function is_list($array) {
		return !$array || array_keys($array) === range(0, count($array) - 1);
	}

	// ------------------------------------------------------------------
	// 清洗与校验
	// ------------------------------------------------------------------

	/**
	 * 递归清洗整页配置：未知键丢弃，缺失键补默认值。
	 */

 public static function sanitize($config){
  $config=is_array($config)?$config:[];$refreshIcons=intval($config['schemaVersion']??0)<8;$refreshInterface=intval($config['schemaVersion']??0)<7||front_composer::needs_interface_defaults($config['components']??[]);$config=front_composer::upgrade_defaults($config);$defaults=front_composer::schema()['pageDefaults'];$baseline=intval($config['schemaVersion']??0)<3;
  $nav=is_array($config['nav']??null)?$config['nav']:[];$logo=is_array($nav['logo']??null)?$nav['logo']:[];$back=is_array($nav['back']??null)?$nav['back']:[];$layout=is_array($config['layout']??null)?$config['layout']:[];
  $n=function($v){return self::num($v,0,2400,0,true);};$color=function($v){return self::color($v,'');};
  $value=function($raw,$key,$default)use($baseline,$n){return $n(!array_key_exists($key,$raw)||($baseline&&is_numeric($raw[$key])&&floatval($raw[$key])===0.0)?$default:$raw[$key]);};
  $backStyle=is_array($back['style']??null)?$back['style']:[];foreach($defaults['back'] as $key=>$default)if(!array_key_exists($key,$backStyle)||($baseline&&($backStyle[$key]===''||(is_numeric($backStyle[$key])&&floatval($backStyle[$key])===0.0))))$backStyle[$key]=$default;
  $bar=is_array($config['tabbar']??null)?$config['tabbar']:[];foreach($defaults['tabbar'] as $key=>$default)$bar[$key]=$value($bar,$key,$default);
  $result=['schemaVersion'=>8,'schema'=>front_composer::schema(),'typography'=>front_composer::typography(),
   'layout'=>['bg'=>$color($layout['bg']??'')?:$defaults['layout']['bg'],'color'=>$color($layout['color']??''),'size'=>$n($layout['size']??0),'sideGap'=>$value($layout,'sideGap',$defaults['layout']['sideGap']),'componentGap'=>$value($layout,'componentGap',$defaults['layout']['componentGap']),'topGap'=>$value($layout,'topGap',$defaults['layout']['topGap']),'bottomGap'=>$value($layout,'bottomGap',$defaults['layout']['bottomGap'])],
   'nav'=>['enabled'=>self::bool($nav['enabled']??1),'height'=>$value($nav,'height',$defaults['nav']['height']),'bg'=>$color($nav['bg']??'')?:$defaults['nav']['bg'],
    'logo'=>['enabled'=>self::bool($logo['enabled']??1),'type'=>self::enum($logo['type']??'text',['text','image'],'text'),'text'=>self::text($logo['text']??'FLIPBOOK',100),'image'=>self::url($logo['image']??''),'height'=>$value($logo,'height',$defaults['nav']['logo']['height']),'width'=>$n($logo['width']??0),'fontSize'=>$value($logo,'fontSize',$defaults['nav']['logo']['fontSize']),'radius'=>$n($logo['radius']??0),'top'=>self::num($logo['top']??0,-2400,2400,0,true),'align'=>self::enum($logo['align']??'left',['left','center','right'],'left'),'color'=>$color($logo['color']??''),'offsetLeft'=>$value($logo,'offsetLeft',$defaults['nav']['logo']['offsetLeft']),'offsetRight'=>$value($logo,'offsetRight',$defaults['nav']['logo']['offsetRight']),'invert'=>self::bool($logo['invert']??0),'link'=>front_composer::link($logo['link']??'')],
    'back'=>['show'=>self::bool($back['show']??0),'link'=>front_composer::link($back['link']??''),'style'=>front_composer::normalize_style($backStyle)]],
   'tabbar'=>self::clean_tabbar($bar),'components'=>front_composer::normalize(front_composer::upgrade(self::detach_product_sections($config['components']??[]),$baseline),0,'components',$baseline)];
  if($refreshInterface)$result['components']=front_composer::normalize(front_composer::interface_defaults($result['components']));
  if($refreshIcons)$result['components']=front_composer::normalize(front_composer::menu_icon_defaults($result['components']));
  $viewer=is_array($config['viewer']??null)?$config['viewer']:[];$result['viewer']=$defaults['viewer'];foreach(front_composer::schema()['viewerFields'] as $field){$key=$field['key'];if(array_key_exists($key,$viewer))$result['viewer'][$key]=$field['type']==='number'?$n($viewer[$key]):$color($viewer[$key]);}
  if(!empty($config['globalTypographyDirty'])){$result['typography']=front_composer::clean_typography($config['typography']??[]);$result['globalTypographyDirty']=1;}
  return $result;
 }


 /** Expand the old detail bundle once, retaining its region styles and later user removals. */
 public static function detach_product_sections($nodes) {
  $present=[];$scan=function($items)use(&$scan,&$present){foreach($items as $node){if(!is_array($node))continue;$present[$node['type']??'']=true;$scan($node['props']['children']??[]);foreach($node['props']['tabs']??[] as $tab)$scan($tab['children']??[]);}};$scan($nodes);
  $walk=function($items)use(&$walk,&$present){$out=[];foreach($items as $node){
   if(!is_array($node)){$out[]=$node;continue;}
   if(isset($node['props']['children']))$node['props']['children']=$walk($node['props']['children']);
   if(($node['type']??'')==='tab'&&is_array($node['props']['tabs']??null))foreach($node['props']['tabs'] as &$tab)$tab['children']=$walk(is_array($tab['children']??null)?$tab['children']:[]);unset($tab);
   $p=$node['props']??[];$detail=($node['type']??'')==='product_detail'||(in_array($node['type']??'',['social','business'],true)&&($p['source']??'')==='detail');
   if(!$detail||!empty($p['sectionsDetached'])){$out[]=$node;continue;}
   $node['props']['sectionsDetached']=1;$out[]=$node;
   foreach(['highlight'=>'product_highlights','detail'=>'product_details'] as $group=>$type){if(isset($present[$type]))continue;$present[$type]=true;$regions=[];
    $map=['container'=>$group==='highlight'?'highlights':'details','heading'=>$group.'Head','title'=>$group.'Title','list'=>$group.'List','item'=>$group.'Item','image'=>$group.'Image','text'=>$group.'Text','code'=>$group.'Code'];
    foreach($map as $target=>$old)if(isset($p['regions'][$old])&&is_array($p['regions'][$old]))$regions[$target]=$p['regions'][$old];
    $out[]=['type'=>$type,'props'=>['regions'=>$regions]];
   }
  }return $out;};return $walk($nodes);
 }

 public static function clean_tabbar($raw=[]){
  $standard=front_composer::schema()['pageDefaults']['tabbar'];
  $defaults=[['label'=>'发现','icon'=>'grid','link'=>'page:home'],['label'=>'分类','icon'=>'book','link'=>'page:catalog'],['label'=>'收藏','icon'=>'bookmark','link'=>'page:favorites'],['label'=>'我的','icon'=>'visitor','link'=>'page:mine']];$items=[];
  foreach(array_slice(is_array($raw['items']??null)?$raw['items']:$defaults,0,5) as $i=>$item)if(is_array($item))$items[]=['id'=>'tab_'.$i,'label'=>self::text($item['label']??'',20),'icon'=>self::enum($item['icon']??'grid',array_keys(front_icon::keys()),'grid'),'link'=>front_composer::link($item['link']??''),'iconSvg'=>self::nav_icon($item['iconSvg']??'')];
  return ['enabled'=>self::bool($raw['enabled']??1),'height'=>self::num($raw['height']??$standard['height'],0,2400,$standard['height'],true),'fontSize'=>self::num($raw['fontSize']??$standard['fontSize'],0,120,$standard['fontSize'],true),'iconSize'=>self::num($raw['iconSize']??$standard['iconSize'],0,120,$standard['iconSize'],true),'bg'=>self::color($raw['bg']??'',''),'color'=>self::color($raw['color']??'',''),'activeColor'=>self::color($raw['activeColor']??'',''),'items'=>$items];
 }


 public static function nav_icon($svg) { try{return front_nav_icon::sanitize($svg);}catch(\RuntimeException $error){return '';} }

	protected static function clean_actions($raw) {
		$raw=is_array($raw)?$raw:[]; $items=[];
		foreach(array_slice(is_array($raw['items'] ?? null)?$raw['items']:[],0,5) as $index=>$item) {
		 if(!is_array($item))continue;
		 $items[]=['id'=>'action_'.$index,'label'=>self::text($item['label']??'自定义按钮',30),'icon'=>self::enum($item['icon']??'grid',array_keys(front_icon::keys()),'grid'),'link'=>self::link($item['link']??''),'color'=>self::color($item['color']??'',''),'bg'=>self::color($item['bg']??'',''),'radius'=>self::num($item['radius']??20,0,100,20)];
		}
		return ['enabled'=>self::bool($raw['enabled']??1),'left'=>self::num($raw['left']??10,-1200,200,10),'bottom'=>self::num($raw['bottom']??10,-1200,200,10),'size'=>self::num($raw['size']??40,24,80,40),'gap'=>self::num($raw['gap']??10,0,40,10),'bg'=>self::color($raw['bg']??'rgba(11,11,12,0.7)','rgba(11,11,12,0.7)'),'color'=>self::color($raw['color']??'#FFFFFF','#FFFFFF'),'radius'=>self::num($raw['radius']??20,0,100,20),'items'=>$items];
	}

	public static function action_data($config) {
		$actions=self::clean_actions($config['actions']??[]);
        $actions['enabled']=$actions['enabled'] && self::has_home_social($config['components']??[]) ? 1 : 0;
		foreach($actions['items'] as &$item) { $item['href']=self::link_url($item['link']); }
		return $actions;
	}

 /** Effective menu eligibility follows the home social component without altering stored settings. */
 public static function has_home_social($nodes) {
  foreach((array)$nodes as $node) {
   if(($node['type']??'')==='social' && ($node['props']['source']??'home')==='home')return true;
   if(($node['type']??'')==='business' && ($node['props']['source']??'home')==='home')return true;
   foreach($node['props']['tabs']??[] as $tab)if(self::has_home_social($tab['children']??[]))return true;
  }
  return false;
 }

 public static function text_presets(){static $value;if($value===null)$value=json_decode(file_get_contents(dirname(__DIR__).'/static/config/text-presets.json'),true);return $value;}

 public static function component_defaults(){$out=[];foreach(self::types() as $type=>$label)$out[$type]=front_composer::defaults($type);return $out;}

 public static function region_defaults($type,$props=[]){
  $presets=self::text_presets();$out=$presets['defaults'][$type]??[];
  foreach($presets['controlRegions'][$type]??[] as $key=>$role)$out[$key]=array_merge($presets['controls'][$role]??[],$out[$key]??[]);
  if($type==='member_center'){if(isset($props['avatarSize']))$out['avatar']=array_merge($out['avatar']??[],['width'=>$props['avatarSize'],'height'=>$props['avatarSize']]);if(isset($props['nameSize']))$out['name']=array_merge($out['name']??[],['size'=>$props['nameSize']]);}
  foreach($presets['regions'][$type]??[] as $key=>$role){$value=array_merge($presets['roles'][$role],$presets['defaults'][$type][$key]??[]);if(in_array($type,['social','product_detail'],true)&&isset($props['caption'])){$c=$props['caption'];if($key==='title')foreach(['size','color','weight','align'] as $field)if(isset($c[$field]))$value[$field]=$c[$field];if($key==='subtitle'){if(isset($c['subSize']))$value['size']=$c['subSize'];if(isset($c['subColor']))$value['color']=$c['subColor'];if(isset($c['align']))$value['align']=$c['align'];}}foreach($presets['inherit'][$type][$key]??[] as $field)if(isset($props[$field])&&$props[$field]!=='')$value[$field]=$props[$field];$out[$key]=array_merge($out[$key]??[],$value);}
  return $out;
 }
 public static function effective_regions($type,$props=[]){$out=self::region_defaults($type,$props);$resets=self::text_presets()['resets']??[];foreach($props['regions']??[] as $key=>$values){foreach($resets as $field=>$targets)if(array_key_exists($field,$values))foreach($targets as $target)if(!array_key_exists($target,$values))unset($out[$key][$target]);$out[$key]=array_merge($out[$key]??[],$values);}return $out;}

 public static function region_registry() {
  static $registry;
  if($registry===null)$registry=json_decode(file_get_contents(dirname(__DIR__).'/static/config/component-regions.json'),true)?:[];
  return $registry;
 }
 public static function region_schema() {
  $schema=[];foreach(self::region_registry() as $type=>$regions)foreach($regions as $key=>$region)$schema[$type][$key]=$region['label'];return $schema;
 }
 public static function region_sources() { $out=[];foreach(self::region_registry() as $type=>$regions)foreach($regions as $key=>$entry)if(isset($entry['sources']))$out[$type][$key]=$entry['sources'];return $out; }
 public static function region_keys($type) { return self::region_schema()[$type]??[]; }
 /** Missing region properties inherit the component's existing appearance. Explicit zero is retained. */
 public static function region_props($raw) {
  if(!is_array($raw))return [];$out=[];
  $limits=['borderTopWidth'=>[0,20],'borderRightWidth'=>[0,20],'borderBottomWidth'=>[0,20],'borderLeftWidth'=>[0,20],'minWidth'=>[0,2400],'minHeight'=>[0,2400],'maxWidth'=>[0,2400],'maxHeight'=>[0,2400],'itemGap'=>[0,600],'grow'=>[0,100],'shrink'=>[0,100],'offsetX'=>[-1200,1200],'offsetY'=>[-1200,1200],'gapTop'=>[-600,600],'gapBottom'=>[-600,600],'gapLeft'=>[-600,600],'gapRight'=>[-600,600],'padTop'=>[0,600],'padBottom'=>[0,600],'padLeft'=>[0,600],'padRight'=>[0,600],'radius'=>[0,600],'width'=>[0,2400],'height'=>[0,2400],'borderWidth'=>[0,20],'size'=>[0,120],'weight'=>[0,900],'lineHeight'=>[0,5],'letterSpacing'=>[-20,100]];
  foreach($limits as $key=>$limit)if(array_key_exists($key,$raw)&&is_scalar($raw[$key])&&$raw[$key]!=='')$out[$key]=self::num($raw[$key],$limit[0],$limit[1],0,in_array($key,['lineHeight','letterSpacing','width','height','borderWidth','borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth'],true));
  if(array_key_exists('enabled',$raw))$out['enabled']=self::bool($raw['enabled']);
  foreach(['bg','color','borderColor'] as $key)if(array_key_exists($key,$raw))$out[$key]=self::color($raw[$key],'');
  foreach(['align'=>['','left','center','right'],'family'=>['','sans','serif','mono'],'borderStyle'=>['','solid','dashed','dotted','none']] as $key=>$values)if(array_key_exists($key,$raw))$out[$key]=self::enum($raw[$key],$values,'');
  foreach(['display'=>['','block','inline-block','flex','inline-flex','none'],'direction'=>['','row','column','row-reverse','column-reverse'],'justify'=>['','flex-start','center','flex-end','space-between','space-around','space-evenly'],'itemsAlign'=>['','stretch','flex-start','center','flex-end','baseline'],'wrap'=>['','nowrap','wrap','wrap-reverse']] as $key=>$values)if(array_key_exists($key,$raw))$out[$key]=self::enum($raw[$key],$values,'');
  return $out;
 }
 public static function regions($type,$raw) {
  $out=[];if(!is_array($raw))return $out;
  foreach(self::region_keys($type) as $key=>$label)if(isset($raw[$key])&&is_array($raw[$key]))$out[$key]=self::region_props($raw[$key]);
  return $out;
 }
 public static function region_style($raw,$important=false) {
  $p=self::region_props($raw);$styles=[];$suffix=$important?'!important':'';

  $map=['gapTop'=>'margin-top','gapBottom'=>'margin-bottom','gapLeft'=>'margin-left','gapRight'=>'margin-right','padTop'=>'padding-top','padBottom'=>'padding-bottom','padLeft'=>'padding-left','padRight'=>'padding-right','radius'=>'border-radius','borderWidth'=>'border-width','letterSpacing'=>'letter-spacing'];
  foreach($map as $key=>$name)if(array_key_exists($key,$p))$styles[]=$name.':'.$p[$key].'px'.$suffix;
  foreach(['width','height'] as $key)if(array_key_exists($key,$p)){$styles[]=$key.':'.($p[$key]?$p[$key].'px':'auto').$suffix;if($key==='width'&&$p[$key]>0&&!array_key_exists('minWidth',$p))$styles[]='min-width:0'.$suffix;}
  foreach(['bg'=>'background','color'=>'color','borderColor'=>'border-color'] as $key=>$name)if(isset($p[$key])&&$p[$key]!=='')$styles[]=$name.':'.$p[$key].$suffix;
  foreach(['size'=>'font-size','weight'=>'font-weight','lineHeight'=>'line-height'] as $key=>$name)if(array_key_exists($key,$p))$styles[]=$name.':'.($p[$key]?$p[$key].($key==='size'?'px':''):'inherit').$suffix;
  if(!empty($p['family']))$styles[]='font-family:'.self::font($p['family']).$suffix;
  if(!empty($p['align']))$styles[]='text-align:'.$p['align'].$suffix;
  if(!empty($p['borderStyle']))$styles[]='border-style:'.$p['borderStyle'].$suffix;
  elseif(max($p['borderWidth']??0,$p['borderTopWidth']??0,$p['borderRightWidth']??0,$p['borderBottomWidth']??0,$p['borderLeftWidth']??0)>0)$styles[]='border-style:solid'.$suffix;
  if(array_key_exists('offsetX',$p)||array_key_exists('offsetY',$p)){$styles[]='transform:translate('.($p['offsetX']??0).'px,'.($p['offsetY']??0).'px)'.$suffix;}
  foreach(['display'=>'display','direction'=>'flex-direction','justify'=>'justify-content','itemsAlign'=>'align-items','wrap'=>'flex-wrap'] as $key=>$css)if(!empty($p[$key]))$styles[]=$css.':'.$p[$key].$suffix;
  foreach(['itemGap'=>'gap','grow'=>'flex-grow','shrink'=>'flex-shrink'] as $key=>$css)if(array_key_exists($key,$p))$styles[]=$css.':'.$p[$key].($key==='itemGap'?'px':'').$suffix;
  foreach(['borderTopWidth'=>'border-top-width','borderRightWidth'=>'border-right-width','borderBottomWidth'=>'border-bottom-width','borderLeftWidth'=>'border-left-width','minWidth'=>'min-width','minHeight'=>'min-height','maxWidth'=>'max-width','maxHeight'=>'max-height'] as $key=>$css)if(array_key_exists($key,$p))$styles[]=$css.':'.(in_array($key,['maxWidth','maxHeight'],true)&&!$p[$key]?'none':$p[$key].'px').$suffix;
  if(isset($p['enabled'])&&!$p['enabled'])$styles[]='display:none'.$suffix;
  return implode(';',$styles);
 }
 public static function caption_options($raw=[],$defaultAlign='center') {return ['enabled'=>self::bool($raw['enabled']??1),'showTitle'=>self::bool($raw['showTitle']??1),'showSubtitle'=>self::bool($raw['showSubtitle']??1),'showExtra'=>self::bool($raw['showExtra']??1),'showCode'=>self::bool($raw['showCode']??1),'align'=>self::enum($raw['align']??$defaultAlign,['left','center','right'],$defaultAlign),'left'=>self::num($raw['left']??16,-1200,375,16),'right'=>self::num($raw['right']??16,-1200,375,16),'bottom'=>self::num($raw['bottom']??10,-1200,1200,10),'size'=>self::num($raw['size']??22,10,64,22),'weight'=>self::num($raw['weight']??700,300,800,700),'color'=>self::color($raw['color']??'#F2F2F4','#F2F2F4'),'subSize'=>self::num($raw['subSize']??16,10,40,16),'subColor'=>self::color($raw['subColor']??'#DADADD','#DADADD'),'link'=>self::link($raw['link']??'')];}
 public static function caption_style($p){return 'left:'.$p['left'].'px;right:'.$p['right'].'px;bottom:'.$p['bottom'].'px;text-align:'.$p['align'].';color:'.$p['color'].';font-size:'.$p['size'].'px;font-weight:'.$p['weight'].';';}
 public static function floating_style($p){return ($p['align']==='left'?'left:'.$p['left']:'right:'.$p['right']).'px;bottom:'.$p['bottom'].'px;width:'.$p['width'].'px;height:'.$p['height'].'px;border-radius:'.$p['radius'].'px;';}

 public static function floating_options($raw=[]) {return ['enabled'=>self::bool($raw['enabled']??1),'align'=>self::enum($raw['align']??'right',['left','right'],'right'),'left'=>self::num($raw['left']??20,-1200,375,20),'right'=>self::num($raw['right']??20,-1200,375,20),'bottom'=>self::num($raw['bottom']??100,-1200,1200,100),'width'=>self::num($raw['width']??60,20,375,60),'height'=>self::num($raw['height']??60,20,375,60),'radius'=>self::num($raw['radius']??0,0,200,0)];}
 public static function region_selectors($source,$component=false) {
  $type=$component?$source:(['home'=>'social','detail'=>'product_detail','mine'=>'member_center','catalog'=>'album_list','search'=>'album_list','favorites'=>'favorite_list','history'=>'history_list','preference'=>'reading_preference'][$source]??$source);
  $out=[];foreach(self::region_registry()[$type]??[] as $key=>$entry){$selector=$entry['selector'];$out[$key]=is_array($selector)?($selector[$source]??$selector['default']):$selector;}return $out;
 }
 public static function all_region_selectors() { $out=[];foreach(array_keys(self::types()) as $type)$out['type:'.$type]=self::region_selectors($type,true);foreach(['home','detail','mine','catalog','search','favorites','history','preference'] as $source)$out[$source]=self::region_selectors($source);return $out; }
 public static function region_css($source,$regions,$scope='',$props=[]) {
  $type=$scope!==''?$source:(['home'=>'social','detail'=>'product_detail','mine'=>'member_center','catalog'=>'album_list','search'=>'album_list','favorites'=>'favorite_list','history'=>'history_list','preference'=>'reading_preference'][$source]??$source);$props['regions']=$regions;$regions=self::effective_regions($type,$props);
  $css='';$component=$scope!=='';$scope=$scope?:'.fb-mc-business[data-source="'.$source.'"]';
  if(in_array($type,['markdown','product_info'],true)){$html=self::text_presets()['html']??[];$tags=array_merge($html['default']??[],$html[$type]??[]);foreach($tags as $tag=>$style)$css.=$scope.' .fb-markdown '.$tag.'{'.$style.'}';}
  foreach(self::region_selectors($source,$component) as $key=>$selector){if(empty($regions[$key]))continue;$style=self::region_style($regions[$key],true);if($style==='')continue;$selectors=array_map(function($item)use($scope){return $scope.' '.trim($item);},explode(',',$selector));$css.=implode(',',$selectors).'{'.$style.';}';}
  return $css!==''?'<style>'.$css.'</style>':'';
 }

 public static function view_lines($rows) {
  $out=[];foreach(array_slice(is_array($rows)?$rows:[],0,20) as $i=>$line) {
   if(!is_array($line))continue;
   $out[]=['id'=>'line_'.$i,'binding'=>'custom','text'=>self::text($line['text']??'',500,true),'size'=>self::num($line['size']??16,10,64,16),'weight'=>self::num($line['weight']??400,300,800,400),'color'=>self::color($line['color']??'#FFFFFF','#FFFFFF'),'family'=>self::enum($line['family']??'sans',['sans','serif','mono'],'sans'),'lineHeight'=>self::num($line['lineHeight']??1.4,.8,3,1.4,true),'letterSpacing'=>self::num($line['letterSpacing']??0,-5,20,0,true),'align'=>self::enum($line['align']??'left',['left','center','right'],'left'),'anchor'=>self::enum($line['anchor']??'bottom',['top','bottom'],'bottom'),'left'=>self::num($line['left']??20,-1200,375,20),'top'=>self::num($line['top']??20,-1200,1200,20),'bottom'=>self::num($line['bottom']??20,-1200,1200,20),'width'=>self::num($line['width']??0,0,375,0),'link'=>self::link($line['link']??'')];
  }return $out;
 }

 public static function member_options($raw=[]) {
  $out=['avatarSize'=>self::num($raw['avatarSize']??36,24,80,36),'nameSize'=>self::num($raw['nameSize']??14,10,24,14),'note'=>self::text($raw['note']??'登录后同步收藏与阅读记录',100),'showStats'=>self::bool($raw['showStats']??1),'showResume'=>self::bool($raw['showResume']??1),'showLogout'=>self::bool($raw['showLogout']??1)];
  $rows=['history'=>['阅读历史','page:history'],'share'=>['分享记录','page:history?action=share'],'about'=>['关于画册','page:about'],'privacy'=>['隐私协议','page:privacy'],'agreement'=>['注册协议','page:agreement']];
  foreach($rows as $key=>$row) { $out[$key.'Enabled']=self::bool($raw[$key.'Enabled']??1);$out[$key.'Label']=self::text($raw[$key.'Label']??$row[0],40);$out[$key.'Link']=self::link($raw[$key.'Link']??$row[1]); }
  return $out;
 }

	protected static $component_count = 0;

	protected static function clean_components($components, $depth) {
		$out = [];
		if($depth > 4 || !is_array($components)) {
			return $out;
		}
		$count = 0;
		foreach($components as $component) {
			if(!is_array($component) || self::$component_count >= 60) {
				continue;
			}
			if(($component['type']??'')==='reading_preference'||(($component['type']??'')==='business'&&($component['props']['source']??'')==='preference'))continue;
			self::$component_count++;
			$component['id'] = 'mc_'.self::$component_count;
			$out[] = self::clean_component($component, $depth);
			$count++;
		}
		return $out;
	}

	protected static function clean_component($component, $depth) {
		if(($component['type']??'')==='business') { $map=['home'=>'social','detail'=>'product_detail','catalog'=>'album_list','search'=>'album_list','favorites'=>'favorite_list','mine'=>'member_center','history'=>'history_list','preference'=>'reading_preference']; $component['type']=$map[$component['props']['source']??'home']??'social'; }
        if(($component['type']??'')==='social' && ($component['props']['source']??'home')==='detail')$component['type']='product_detail';
		$types = self::types();
		$type = isset($component['type']) && isset($types[$component['type']]) ? $component['type'] : 'text';
		$raw = isset($component['props']) && is_array($component['props']) ? $component['props'] : [];
		$props = self::common_props($raw);
  foreach(['display','direction','justify','itemsAlign','wrap','itemGap','grow','shrink'] as $key)if(array_key_exists($key,$raw)){ $layout=self::region_props([$key=>$raw[$key]]);if(array_key_exists($key,$layout))$props[$key]=$layout[$key]; }
		if(in_array($type,['hero','carousel','video','business','social','product_detail'],true)) $props['coverNav']=self::bool($raw['coverNav']??0);

		switch($type) {
			case 'social': case 'product_detail': case 'album_list': case 'favorite_list': case 'member_center': case 'history_list': case 'reading_preference':
				$defaults=['social'=>'home','product_detail'=>'detail','album_list'=>'catalog','favorite_list'=>'favorites','member_center'=>'mine','history_list'=>'history','reading_preference'=>'preference'];
    $props['source']=$type==='album_list' && ($raw['source']??'')==='search' ? 'search' : $defaults[$type];
    if(in_array($type,['social','product_detail'],true))$props['sectionsDetached']=self::bool($raw['sectionsDetached']??0);
    if($type==='member_center') $props+=self::member_options($raw);
    $props['regions']=self::regions($type,$raw['regions']??[]);
    if(in_array($type,['social','product_detail'],true)) { $props['caption']=self::caption_options($raw['caption']??[],$type==='product_detail'?'left':'center');$props['floating']=self::floating_options($raw['floating']??[]); }
    break;
   case 'product_info':$props+=['title'=>self::text($raw['title']??'产品信息',60),'size'=>self::num($raw['size']??14,10,30,14),'color'=>self::color($raw['color']??'#F2F2F4','#F2F2F4'),'showColors'=>self::bool($raw['showColors']??1),'showSizes'=>self::bool($raw['showSizes']??1),'showFit'=>self::bool($raw['showFit']??1),'showTable'=>self::bool($raw['showTable']??1),'showFields'=>self::bool($raw['showFields']??1),'showContent'=>self::bool($raw['showContent']??1)];break;
   case 'product_highlights': case 'product_details':$props+=['title'=>self::text($raw['title']??($type==='product_highlights'?'产品卖点':'细节展示'),60),'size'=>self::num($raw['size']??14,10,30,14),'color'=>self::color($raw['color']??'#F2F2F4','#F2F2F4'),'showTitle'=>self::bool($raw['showTitle']??1),'showText'=>self::bool($raw['showText']??1),'showCode'=>self::bool($raw['showCode']??($type==='product_highlights'?1:0))];break;
   case 'markdown':
    $props['markdown']=mb_substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/','',is_scalar($raw['markdown']??'')?(string)($raw['markdown']??''):''),0,30000,'UTF-8');
    $props['html']=front_markdown::render($props['markdown']);
    $props['color']=self::color($raw['color']??'#F2F2F4','#F2F2F4');
    $props['size']=self::num($raw['size']??14,10,30,14);break;
   case 'view_title':
    $rows=$raw['lines']??[
     ['binding'=>'custom','text'=>$raw['text']??'','size'=>$raw['size']??22,'weight'=>700,'left'=>$raw['left']??80,'bottom'=>($raw['bottom']??20)+24,'color'=>$raw['color']??'#FFFFFF','link'=>$raw['link']??''],
     ['binding'=>'custom','text'=>$raw['subtitle']??'','size'=>13,'weight'=>400,'left'=>$raw['left']??80,'bottom'=>$raw['bottom']??20,'color'=>'#DADADD','link'=>$raw['link']??'']
    ];
    $props['lines']=self::view_lines($rows);break;
			case 'hero':
				$props['height'] = self::num(isset($raw['height']) ? $raw['height'] : 480, 120, 1200, 480);
				$props['autoplay'] = self::bool(isset($raw['autoplay']) ? $raw['autoplay'] : 1);
				$props['interval'] = self::num(isset($raw['interval']) ? $raw['interval'] : 4000, 1000, 20000, 4000);
				$props['indicator'] = self::bool(isset($raw['indicator']) ? $raw['indicator'] : 1);
				$props['muted'] = self::bool(isset($raw['muted']) ? $raw['muted'] : 0);
				$props['items'] = self::items(isset($raw['items']) ? $raw['items'] : []);
				$props['lines'] = self::lines(isset($raw['lines']) ? $raw['lines'] : []);
				$props['hotspots'] = self::hotspots(isset($raw['hotspots']) ? $raw['hotspots'] : []);
				break;
			case 'carousel':
				$props['height'] = self::num(isset($raw['height']) ? $raw['height'] : 360, 80, 1200, 360);
				$props['autoplay'] = self::bool(isset($raw['autoplay']) ? $raw['autoplay'] : 1);
				$props['interval'] = self::num(isset($raw['interval']) ? $raw['interval'] : 4000, 1000, 20000, 4000);
				$props['indicator'] = self::bool(isset($raw['indicator']) ? $raw['indicator'] : 1);
				$props['muted'] = self::bool(isset($raw['muted']) ? $raw['muted'] : 0);
				$props['items'] = self::items(isset($raw['items']) ? $raw['items'] : []);
				break;
			case 'image':
				$props['src'] = self::url(isset($raw['src']) ? $raw['src'] : '');
				$props['height'] = self::num(isset($raw['height']) ? $raw['height'] : 0, 0, 1200, 0);
				$props['fit'] = self::enum(isset($raw['fit']) ? $raw['fit'] : 'cover', ['cover', 'contain'], 'cover');
				$props['alt'] = self::text(isset($raw['alt']) ? $raw['alt'] : '', 80);
				break;
			case 'text':
				$props['text'] = self::text(isset($raw['text']) ? $raw['text'] : '', 2000, true);
				$props['size'] = self::num(isset($raw['size']) ? $raw['size'] : 14, 10, 64, 14);
				$props['weight'] = self::num(isset($raw['weight']) ? $raw['weight'] : 400, 300, 800, 400);
				$props['color'] = self::color(isset($raw['color']) ? $raw['color'] : '#DADADD', '#DADADD');
				$props['family'] = self::enum(isset($raw['family']) ? $raw['family'] : 'sans', ['sans', 'serif', 'mono'], 'sans');
				$props['lineHeight'] = self::num(isset($raw['lineHeight']) ? $raw['lineHeight'] : 1.7, 0.8, 3, 1.7, true);
				$props['letterSpacing'] = self::num(isset($raw['letterSpacing']) ? $raw['letterSpacing'] : 0, -5, 20, 0, true);
				$props['align'] = self::enum(isset($raw['align']) ? $raw['align'] : 'left', ['left', 'center', 'right'], 'left');
				break;
			case 'video':
				$props['src'] = self::url(isset($raw['src']) ? $raw['src'] : '');
				$props['poster'] = self::url(isset($raw['poster']) ? $raw['poster'] : '');
				$props['height'] = self::num(isset($raw['height']) ? $raw['height'] : 240, 80, 1200, 240);
				$props['controls'] = self::bool(isset($raw['controls']) ? $raw['controls'] : 1);
				$props['autoplay'] = self::bool(isset($raw['autoplay']) ? $raw['autoplay'] : 0);
				$props['loop'] = self::bool(isset($raw['loop']) ? $raw['loop'] : 0);
				$props['muted'] = self::bool(isset($raw['muted']) ? $raw['muted'] : 0);
				break;
			case 'search':
				$props['placeholder'] = self::text(isset($raw['placeholder']) ? $raw['placeholder'] : '搜索画册与款式', 40);
				$props['action'] = self::link(isset($raw['action']) ? $raw['action'] : '');
				$props['color'] = self::color(isset($raw['color']) ? $raw['color'] : '#A1A1A6', '#A1A1A6');
				$props['bg'] = self::color(isset($raw['bg']) ? $raw['bg'] : '#17171A', '#17171A');
				break;
			case 'divider':
				$props['thickness'] = self::num(isset($raw['thickness']) ? $raw['thickness'] : 1, 1, 20, 1);
				$props['color'] = self::color(isset($raw['color']) ? $raw['color'] : '#2C2C31', '#2C2C31');
				$props['width'] = self::num(isset($raw['width']) ? $raw['width'] : 100, 1, 100, 100);
				$props['dashed'] = self::bool(isset($raw['dashed']) ? $raw['dashed'] : 0);
				break;
			case 'spacer':
				$props['height'] = self::num(isset($raw['height']) ? $raw['height'] : 24, 1, 800, 24);
				break;
			case 'tab':
				$props['tabs'] = self::tabs(isset($raw['tabs']) ? $raw['tabs'] : [], $depth);
				$props['activeColor'] = self::color(isset($raw['activeColor']) ? $raw['activeColor'] : '#F4F4F5', '#F4F4F5');
				$props['color'] = self::color(isset($raw['color']) ? $raw['color'] : '#79797F', '#79797F');
				$props['size'] = self::num(isset($raw['size']) ? $raw['size'] : 13, 10, 24, 13);
				$props['indicator'] = self::bool(isset($raw['indicator']) ? $raw['indicator'] : 1);
				break;
		}



  // Saved values are authoritative. Normalization must not restyle existing pages.
  $props['regions']=self::regions($type,$raw['regions']??[]);

		return [
			'id' => self::text(isset($component['id']) ? $component['id'] : '', 40),
			'type' => $type,
			'props' => $props,
		];
	}

	protected static function common_props($raw) {
		return [
   'boxWidth'=>self::num($raw['boxWidth']??0,0,2400,0),'boxHeight'=>self::num($raw['boxHeight']??0,0,2400,0),
   'borderWidth'=>self::num($raw['borderWidth']??0,0,20,0),'borderColor'=>self::color($raw['borderColor']??'',''),
   'borderStyle'=>self::enum($raw['borderStyle']??'',['','solid','dashed','dotted','none'],''),
			'gapLeft'=>self::num($raw['gapLeft']??0,-600,600,0),'gapRight'=>self::num($raw['gapRight']??0,-600,600,0),
			'gapTop' => self::num(isset($raw['gapTop']) ? $raw['gapTop'] : 0, -600, 600, 0),
			'gapBottom' => self::num(isset($raw['gapBottom']) ? $raw['gapBottom'] : 0, -600, 600, 0),
			'padTop' => self::num(isset($raw['padTop']) ? $raw['padTop'] : 0, 0, 200, 0),
			'padBottom' => self::num(isset($raw['padBottom']) ? $raw['padBottom'] : 0, 0, 200, 0),
			'padLeft' => self::num(isset($raw['padLeft']) ? $raw['padLeft'] : 0, 0, 200, 0),
			'padRight' => self::num(isset($raw['padRight']) ? $raw['padRight'] : 0, 0, 200, 0),
			'radius' => self::num(isset($raw['radius']) ? $raw['radius'] : 0, 0, 200, 0),
			'bg' => self::color(isset($raw['bg']) ? $raw['bg'] : '', ''),
			'link' => self::link(isset($raw['link']) ? $raw['link'] : ''),
			'align' => self::enum(isset($raw['align']) ? $raw['align'] : 'left', ['left', 'center', 'right'], 'left'),
		];
	}

	protected static function items($items) {
		$out = [];
		foreach((array)$items as $item) {
			if(!is_array($item) || count($out) >= 20) {
				continue;
			}
			$out[] = [
				'image' => self::url(isset($item['image']) ? $item['image'] : ''),
				'video' => self::url(isset($item['video']) ? $item['video'] : ''),
				'title' => self::text(isset($item['title']) ? $item['title'] : '', 80),
				'link' => self::link(isset($item['link']) ? $item['link'] : ''),
			];
		}
		return $out;
	}

	protected static function lines($lines) {
		$out = [];
		foreach((array)$lines as $line) {
			if(!is_array($line) || count($out) >= 20) {
				continue;
			}
			$out[] = [
				'text' => self::text(isset($line['text']) ? $line['text'] : '', 120),
				'x' => self::num(isset($line['x']) ? $line['x'] : 6, 0, 100, 6, true),
				'y' => self::num(isset($line['y']) ? $line['y'] : 50, 0, 100, 50, true),
				'size' => self::num(isset($line['size']) ? $line['size'] : 16, 8, 60, 16),
				'weight' => self::num(isset($line['weight']) ? $line['weight'] : 400, 300, 800, 400),
				'color' => self::color(isset($line['color']) ? $line['color'] : '#FFFFFF', '#FFFFFF'),
				'align' => self::enum(isset($line['align']) ? $line['align'] : 'left', ['left', 'center', 'right'], 'left'),
				'link' => self::link(isset($line['link']) ? $line['link'] : ''),
			];
		}
		return $out;
	}

	protected static function hotspots($spots) {
		$out = [];
		foreach((array)$spots as $spot) {
			if(!is_array($spot) || count($out) >= 30) {
				continue;
			}
			$out[] = [
				'x' => self::num(isset($spot['x']) ? $spot['x'] : 0, 0, 100, 0, true),
				'y' => self::num(isset($spot['y']) ? $spot['y'] : 0, 0, 100, 0, true),
				'width' => self::num(isset($spot['width']) ? $spot['width'] : 20, 1, 100, 20, true),
				'height' => self::num(isset($spot['height']) ? $spot['height'] : 20, 1, 100, 20, true),
				'label' => self::text(isset($spot['label']) ? $spot['label'] : '', 40),
				'link' => self::link(isset($spot['link']) ? $spot['link'] : ''),
			];
		}
		return $out;
	}

	protected static function tabs($tabs, $depth) {
		$out = [];
		foreach((array)$tabs as $tab) {
			if(!is_array($tab) || count($out) >= 10) {
				continue;
			}
			$out[] = [
				'title' => self::text(isset($tab['title']) ? $tab['title'] : '', 40),
				'children' => self::clean_components(isset($tab['children']) ? $tab['children'] : [], $depth + 1),
			];
		}
		return $out;
	}

	protected static function bool($value) {
		return !empty($value) ? 1 : 0;
	}

	protected static function num($value, $min, $max, $default, $float = false) {
		if(!is_scalar($value) || !is_numeric($value) || !is_finite((float)$value)) {
			return $default;
		}
		$value = $float ? floatval($value) : intval($value);
		return max($min, min($max, $value));
	}

	protected static function text($value, $maxlen, $multiline = false) {
		$value = is_scalar($value) ? (string)$value : '';
		$value = strip_tags($value);
		$value = $multiline ? str_replace(["\r\n", "\r"], "\n", $value) : preg_replace('/\s+/', ' ', $value);
		$value = trim($value);
		return mb_substr($value, 0, $maxlen, 'UTF-8');
	}

	protected static function color($value, $default) {
		$value = trim(is_scalar($value) ? (string)$value : '');
		if($value === 'transparent' || $value === '') {
			return $value;
		}
		if(preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
			return $value;
		}
		if(preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value)) {
			return $value;
		}
		return $default;
	}

	protected static function enum($value, $allowed, $default) {
		return in_array($value, $allowed, true) ? $value : $default;
	}

	/**
	 * 链接值：page:键 / album:编号 / product:编号 / url:地址 / 站内路径。
	 */
	protected static function link($value) {
		$value = trim(strip_tags(is_scalar($value) ? (string)$value : ''));
		if($value === '' || mb_strlen($value, 'UTF-8') > 255 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
			return '';
		}
		if(preg_match('/^page:([a-z0-9_]+)$/i', $value, $match)) {
			return isset(self::keys()[strtolower($match[1])]) ? strtolower($value) : '';
		}
		if(preg_match('/^(album|product):[1-9]\d*$/', $value)) {
			return strtolower($value);
		}
		if(preg_match('#(^|/)\.\.(/|$)|^//#', preg_replace('/^url:/i', '', $value))) { return ''; }
		if(preg_match('/^url:(https?:\/\/[^\s]+|\/[^\s]*)$/i', $value)) {
			return $value;
		}
		if(preg_match('/^(https?:\/\/[^\s]+|\/[^\s]*|source\/plugin\/[A-Za-z0-9_./-]+)$/i', $value)) {
			return $value;
		}
		return '';
	}

	protected static function url($value) {
		$value = trim(strip_tags(is_scalar($value) ? (string)$value : ''));
		if($value === '' || mb_strlen($value, 'UTF-8') > 255 || preg_match('/[\x00-\x1F\x7F]/', $value) || preg_match('#(^|/)\.\.(/|$)#', $value)) {
			return '';
		}
		if(!preg_match('#^(https?://[^\s]+|/[^\s]*|data/[A-Za-z0-9_./-]+|source/plugin/[A-Za-z0-9_./-]+|static/[A-Za-z0-9_./-]+)$#i', $value)) {
			return '';
		}
		return $value;
	}

	// ------------------------------------------------------------------
	// 链接解析
	// ------------------------------------------------------------------

	/**
	 * 链接解析为 H5 地址；无法识别时返回空字符串。
	 */
	public static function link_url($value) {
		$value = self::link($value);
		if($value === '') {
			return '';
		}
		if(preg_match('/^page:([a-z0-9_]+)$/', $value, $match)) {
			return isset(self::keys()[$match[1]]) ? front_helper::url('micro', ['key' => $match[1]]) : '';
		}
		if(preg_match('/^album:(\d+)$/', $value, $match)) {
			return front_helper::url('micro', ['key' => 'detail', 'albumid' => intval($match[1])]);
		}
		if(preg_match('/^product:(\d+)$/', $value, $match)) {
			return front_helper::url('micro', ['key' => 'detail', 'productid' => intval($match[1])]);
		}
		if(strpos($value, 'url:') === 0) {
			return substr($value, 4);
		}
		return $value;
	}

	/**
	 * 链接解析为小程序路径；不支持的地址返回空字符串。
	 */
	public static function link_mini($value) {
		$value = self::link($value);
		if($value === '') {
			return '';
		}
		if(preg_match('/^page:([a-z0-9_]+)$/', $value, $match)) {
			return '/pages/micro/index?key='.$match[1];
		}
		if(preg_match('/^album:(\d+)$/', $value, $match)) {
			return '/pages/read/index?albumid='.intval($match[1]);
		}
		if(preg_match('/^product:(\d+)$/', $value, $match)) {
			return '/pages/read/index?productid='.intval($match[1]);
		}
		return '';
	}

	// ------------------------------------------------------------------
	// 渲染
	// ------------------------------------------------------------------

 public static function member_component($nodes) {
  foreach($nodes as $node){
   if(($node['type']??'')==='member_center'||(($node['type']??'')==='business'&&($node['props']['source']??'')==='mine'))return $node['props'];
   foreach($node['props']['tabs']??[] as $tab){$found=self::member_component($tab['children']??[]);if($found!==null)return $found;}
  }
  return null;
 }

 /** The independent H5 login consumes the same member regions as native login. */

 public static function login_html($page,$return){return self::render(self::page('login'));}


	/**
	 * 渲染整页：导航 + 组件 + 底部导航。
	 */

 public static function render($page){
  $payload=['key'=>$page['key'],'config'=>$page['config'],'context'=>front_composer::context($page['key'])];
  return '<div id="fb-composer-chrome"></div><main class="fb-compose-main" data-fc-root></main><div id="fb-composer-footer"></div><script type="application/json" id="fb-composer-data">'.json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP).'</script>';
 }


	protected static function nav_html($nav, $key) {
		$logo = $nav['logo'];
		$height = intval($nav['height']);
		$style = [
			'height:'.$height.'px',
			'background:'.front_helper::e($nav['bg']),
			'padding-left:'.($logo['align'] === 'left' ? intval($logo['offsetLeft']) : 0).'px',
			'padding-right:'.($logo['align'] === 'right' ? intval($logo['offsetRight']) : 0).'px',
		];
		$backlink = self::link_url($nav['back']['link']);
		if($backlink === '') {
			$backlink = front_helper::url('micro', ['key' => 'home']);
		}
		$html = '<header class="fb-mc-nav'.(!empty($nav['overlay'])?' is-overlay':'').'" style="'.implode(';', $style).'">';
		if($nav['back']['show']) {
			$html .= self::back_html($nav,false);
		}
		if($logo['enabled']) $html .= self::logo_html($logo, $nav['back']['show']);
		$html .= '</header>';
		return $html;
	}

 protected static function back_html($nav,$floating=false) {
  $style=array_merge(self::text_presets()['controls']['navigationBack']??[],$nav['back']['style']??[]);
  $anchor='left:'.intval($nav['logo']['offsetLeft']).'px;top:'.($floating?'calc(env(safe-area-inset-top,0px) + '.($nav['height']/2).'px)':'50%').';';
  $href=$nav['back']['href']??(self::link_url($nav['back']['link'])?:self::link_url('page:home'));
  return '<div class="fb-mc-back-anchor'.($floating?' is-floating':'').'" style="'.$anchor.'"><a data-fb-back data-fb-back-configured="'.(!empty($nav['back']['link'])?'1':'0').'" class="fb-mc-back-control" style="'.self::region_style($style).'" href="'.front_helper::e($href).'" aria-label="返回">'.front_icon::svg('back').'</a></div>';
 }

	protected static function logo_html($logo, $hasback) {
		$style = [
			'position:relative',
			'top:'.intval($logo['top']).'px',
			'height:'.($logo['height'] > 0 ? intval($logo['height']).'px' : 'auto'),
		];
		if($logo['align'] === 'center') {
			$style[] = 'margin-left:auto';
			$style[] = 'margin-right:auto';
		} elseif($logo['align'] === 'right') {
			$style[] = 'margin-left:auto';
		} else {
			$style[] = 'margin-right:auto';
			$style[] = 'margin-left:'.($hasback ? 40 : 0).'px';
		}
		if($logo['width'] > 0) {
			$style[] = 'width:'.intval($logo['width']).'px';
		}
		if($logo['radius'] > 0) {
			$style[] = 'border-radius:'.intval($logo['radius']).'px';
		}
		$inner = '';
		if($logo['type'] === 'image' && $logo['image'] !== '') {
			$imgstyle = [
				'max-height:100%',
				'border-radius:'.intval($logo['radius']).'px',
			];
			if($logo['width'] > 0) {
				$imgstyle[] = 'width:'.$logo['width'].'px';
			}
			if($logo['height'] > 0) {
				$imgstyle[] = 'height:'.$logo['height'].'px';
			}
			if($logo['invert']) {
				$imgstyle[] = 'filter:invert(1)';
			}
			$inner = '<img src="'.front_helper::e($logo['image']).'" alt="'.front_helper::e($logo['text']).'" style="'.implode(';', $imgstyle).';object-fit:contain;" />';
		} else {
			$inner = '<span style="color:'.front_helper::e($logo['color']).';font-size:'.intval($logo['fontSize']).'px;font-weight:700;letter-spacing:0.08em;white-space:nowrap;">'.front_helper::e($logo['text']).'</span>';
		}
		$link = self::link_url($logo['link']);
		if($link !== '') {
			return '<a class="fb-mc-logo" href="'.front_helper::e($link).'" style="'.implode(';', $style).';">'.$inner.'</a>';
		}
		return '<div class="fb-mc-logo" style="'.implode(';', $style).';">'.$inner.'</div>';
	}

	protected static function tabbar_html($key,$raw) {
  $bar=self::clean_tabbar($raw);$active=['detail'=>'home','search'=>'catalog','history'=>'mine','preference'=>'mine','about'=>'mine','privacy'=>'mine','agreement'=>'mine'][$key]??$key;
  $html='<nav class="fb-navbar fb-mc-tabbar" aria-label="页面导航" style="height:calc('.$bar['height'].'px + env(safe-area-inset-bottom,0px));background:'.front_helper::e($bar['bg']).';">';
  foreach($bar['items'] as $item) { $selected=$item['link']==='page:'.$active;$href=self::link_url($item['link']);
   $html.='<a class="fb-nav-item'.($selected?' is-active':'').'" style="color:'.front_helper::e($selected?$bar['activeColor']:$bar['color']).'" href="'.front_helper::e($href).'">'.($item['iconSvg']!==''?front_nav_icon::html($item['iconSvg']):front_icon::svg($item['icon'])).'<span>'.front_helper::e($item['label']).'</span></a>';
  }
  return $html.'</nav>';
 }

	protected static function components_html($components) {
		$html = '';
		foreach((array)$components as $component) {
			$html .= self::component_html($component);
		}
		return $html;
	}

	protected static function component_html($component) {
		$type = $component['type'];
		$p = $component['props'];
		$style = self::box_style($p);
		$inner = '';
		switch($type) {
			case 'social': case 'product_detail': case 'album_list': case 'favorite_list': case 'member_center': case 'history_list': case 'reading_preference':
				$inner = self::business_html($p['source'], $p);
				break;
   case 'product_info':
    $productid=intval($_GET['productid']??0);if(!$productid && !empty($_GET['albumid']))$productid=front_view::first_productid(intval($_GET['albumid']));
    $row=table_flipbook_product::t()->fetch($productid);$fb_info=$row&&!empty($row['available'])?front_helper::spec($row['spec']):[];$fb_info_options=$p;
    $fb_info_html=front_markdown::render($fb_info['content']??'');ob_start();include template('flipbook:product_info');$inner=ob_get_clean();break;
   case 'product_highlights': case 'product_details':
    $productid=intval($_GET['productid']??0);if(!$productid&&!empty($_GET['albumid']))$productid=front_view::first_productid(intval($_GET['albumid']));
    $row=table_flipbook_product::t()->fetch($productid);$product=$row&&!empty($row['available'])?front_view::product_detail($row):[];
    $fb_media_group=$type==='product_highlights'?'highlight':'detail';$fb_media=$product['groups'][$fb_media_group]??[];$fb_media_options=$p;$fb_media_code=$product['code']??'';
    ob_start();include template('flipbook:product_media');$inner=ob_get_clean();break;
   case 'markdown':
    $inner='<article class="fb-markdown" style="font-size:'.intval($p['size']).'px;color:'.front_helper::e($p['color']).'">'.$p['html'].'</article>';break;
   case 'view_title':
    $inner='';$context=self::$title_context;
    foreach($p['lines'] as $line) {
     $text=$line['text']!==''?$line['text']:($context[$line['binding']]??'');
     $font=self::font($line['family']);
     $ls='position:absolute;pointer-events:auto;left:'.$line['left'].'px;'.($line['anchor']==='top'?'top:'.$line['top'].'px':'bottom:calc(var(--fb-current-tab-height,0px) + '.$line['bottom'].'px)').';'.($line['width']?'width:'.$line['width'].'px;':'max-width:calc(100% - '.$line['left'].'px - 16px);').'font-size:'.$line['size'].'px;font-weight:'.$line['weight'].';color:'.$line['color'].';font-family:'.$font.';line-height:'.$line['lineHeight'].';letter-spacing:'.$line['letterSpacing'].'px;text-align:'.$line['align'].';white-space:pre-wrap;';
     $href=self::link_url($line['link']);$tag=$href!==''?'a':'span';
     $inner.='<'.$tag.' data-fb-view-title data-binding="'.$line['binding'].'" data-custom="'.($line['text']!==''?'1':'0').'"'.($href!==''?' href="'.front_helper::e($href).'"':'').' style="'.front_helper::e($ls).'">'.front_helper::e($text).'</'.$tag.'>';
    }
    $style.=';position:absolute;inset:0;'.(empty($p['boxWidth'])?'width:auto;':'').'z-index:10;pointer-events:none';break;
			case 'hero':
				$inner = self::hero_html($p);
				break;
			case 'carousel':
				$inner = self::carousel_html($p);
				break;
			case 'image':
				$inner = self::image_html($p);
				break;
			case 'text':
				$inner = self::text_html($p);
				break;
			case 'video':
				$inner = self::video_html($p);
				break;
			case 'search':
				$inner = self::search_html($p);
				break;
			case 'divider':
				$inner = self::divider_html($p);
				break;
			case 'spacer':
				$inner = '<div class="fb-mc-spacer" style="height:'.intval($p['height']).'px;"></div>';
				break;
			case 'tab':
				$inner = self::tab_html($p);
				break;
		}
		if($p['link'] !== '') {
			$link = self::link_url($p['link']);
			if($link !== '') {
				$inner = '<div class="fb-mc-blocklink" data-fb-block-link="'.front_helper::e($link).'" role="link" tabindex="0">'.$inner.'</div>';
			}
		}
		$id=front_helper::e($component['id']);
		$regions=self::region_css($type,$p['regions']??[],'.fb-mc-'.$type.'[data-fb-component="'.$id.'"]',$p);
		return $regions.'<section class="fb-mc-block fb-mc-'.$type.'" data-fb-component="'.$id.'" data-fb-block="'.$type.'" style="'.$style.'">'.$inner.'</section>';
	}

	protected static function box_style($p) {
		$style = [];
		$style[] = 'margin-top:'.intval($p['gapTop']).'px';
		$style[] = 'margin-bottom:'.intval($p['gapBottom']).'px';
  $style[]='margin-left:'.intval($p['gapLeft']??0).'px';$style[]='margin-right:'.intval($p['gapRight']??0).'px';if(!empty($p['gapLeft'])||!empty($p['gapRight']))$style[]='width:auto';
		if($p['padTop'] || $p['padRight'] || $p['padBottom'] || $p['padLeft']) {
			$style[] = 'padding:'.intval($p['padTop']).'px '.intval($p['padRight']).'px '.intval($p['padBottom']).'px '.intval($p['padLeft']).'px';
		}
		if($p['radius'] > 0) {
			$style[] = 'border-radius:'.intval($p['radius']).'px';
			$style[] = 'overflow:hidden';
		}
		if($p['bg'] !== '') {
			$style[] = 'background:'.front_helper::e($p['bg']);
		}
  $box=[];
  if(!empty($p['boxWidth']))$box['width']=$p['boxWidth'];
  if(!empty($p['boxHeight'])){$box['height']=$p['boxHeight'];$style[]='overflow:hidden';}
  foreach(['borderWidth','borderColor','borderStyle','display','direction','justify','itemsAlign','wrap','itemGap','grow','shrink'] as $key)if(isset($p[$key])&&$p[$key]!=='')$box[$key]=$p[$key];
  $extra=self::region_style($box);if($extra!=='')$style[]=$extra;
		return implode(';', $style);
	}

	protected static function hero_html($p) {
		$height = intval($p['height']);
		$items = $p['items'];
		$html = '<div class="fb-mc-hero" data-fb-micro-carousel data-autoplay="'.intval($p['autoplay']).'" data-interval="'.intval($p['interval']).'">';
		$html .= '<div class="fb-mc-track" style="height:'.$height.'px;">';
		foreach($items as $item) {
			$slide = '<div class="fb-mc-slide">';
			if($item['video'] !== '') {
				$slide .= '<video src="'.front_helper::e($item['video']).'" poster="'.front_helper::e($item['image']).'" '.($p['muted'] ? 'muted ' : '').'autoplay loop playsinline controls></video>';
			} elseif($item['image'] !== '') {
				$slide .= '<img src="'.front_helper::e($item['image']).'" alt="'.front_helper::e($item['title']).'" />';
			}
			if($item['link'] !== '') {
				$link = self::link_url($item['link']);
				if($link !== '') {
					$slide .= '<a class="fb-mc-slide-link'.($item['video'] !== '' ? ' fb-mc-video-link' : '').'" href="'.front_helper::e($link).'" aria-label="'.front_helper::e($item['title']).'"></a>';
				}
			}
			$slide .= '</div>';
			$html .= $slide;
		}
		$html .= '</div>';

		if($p['lines']) {
			$html .= '<div class="fb-mc-lines">';
			foreach($p['lines'] as $line) {
				$lineStyle = [
					'left:'.front_helper::e($line['x']).'%',
					'top:'.front_helper::e($line['y']).'%',
					'font-size:'.intval($line['size']).'px',
					'font-weight:'.intval($line['weight']),
					'color:'.front_helper::e($line['color']),
					'text-align:'.front_helper::e($line['align']),
				];
				$text = front_helper::e($line['text']);
				if($line['link'] !== '') {
					$link = self::link_url($line['link']);
					if($link !== '') {
						$text = '<a href="'.front_helper::e($link).'">'.$text.'</a>';
					}
				}
				$html .= '<div class="fb-mc-line" style="'.implode(';', $lineStyle).';">'.$text.'</div>';
			}
			$html .= '</div>';
		}
		if($p['hotspots']) {
			$html .= '<div class="fb-mc-hotspots">';
			foreach($p['hotspots'] as $spot) {
				$link = self::link_url($spot['link']);
				$hotstyle = 'left:'.front_helper::e($spot['x']).'%;top:'.front_helper::e($spot['y']).'%;width:'.front_helper::e($spot['width']).'%;height:'.front_helper::e($spot['height']).'%;';
				$html .= $link !== ''
					? '<a class="fb-mc-hotspot" href="'.front_helper::e($link).'" style="'.$hotstyle.'" aria-label="'.front_helper::e($spot['label']).'"></a>'
					: '<span class="fb-mc-hotspot" style="'.$hotstyle.'"></span>';
			}
			$html .= '</div>';
		}
		if($p['indicator'] && count($items) > 1) {
			$html .= '<div class="fb-mc-dots">';
			foreach($items as $index => $item) {
				$html .= '<button type="button" class="fb-mc-dot'.($index === 0 ? ' is-active' : '').'" data-slide="'.$index.'" aria-label="第'.($index + 1).'张"></button>';
			}
			$html .= '</div>';
		}
		return $html.'</div>';
	}

	protected static function carousel_html($p) {
		$height = intval($p['height']);
		$items = $p['items'];
		$html = '<div class="fb-mc-carousel" data-fb-micro-carousel data-autoplay="'.intval($p['autoplay']).'" data-interval="'.intval($p['interval']).'">';
		$html .= '<div class="fb-mc-track" style="height:'.$height.'px;">';
		foreach($items as $item) {
			$html .= '<div class="fb-mc-slide">';
			if($item['video'] !== '') {
				$html .= '<video src="'.front_helper::e($item['video']).'" poster="'.front_helper::e($item['image']).'" '.($p['muted'] ? 'muted ' : '').'playsinline></video>';
			} elseif($item['image'] !== '') {
				$html .= '<img src="'.front_helper::e($item['image']).'" alt="'.front_helper::e($item['title']).'" />';
			}
			if($item['link'] !== '') {
				$link = self::link_url($item['link']);
				if($link !== '') {
					$html .= '<a class="fb-mc-slide-link'.($item['video'] !== '' ? ' fb-mc-video-link' : '').'" href="'.front_helper::e($link).'" aria-label="'.front_helper::e($item['title']).'"></a>';
				}
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		if($p['indicator'] && count($items) > 1) {
			$html .= '<div class="fb-mc-dots">';
			foreach($items as $index => $item) {
				$html .= '<button type="button" class="fb-mc-dot'.($index === 0 ? ' is-active' : '').'" data-slide="'.$index.'" aria-label="第'.($index + 1).'张"></button>';
			}
			$html .= '</div>';
		}
		return $html.'</div>';
	}

	protected static function image_html($p) {
		if($p['src'] === '') {
			return '<div class="fb-mc-image-empty"></div>';
		}
		$style = 'width:100%;display:block;object-fit:'.$p['fit'].';';
		if($p['height'] > 0) {
			$style .= 'height:'.intval($p['height']).'px;';
		}
		return '<img class="fb-mc-image" src="'.front_helper::e($p['src']).'" alt="'.front_helper::e($p['alt']).'" style="'.$style.'" />';
	}

	protected static function text_html($p) {
		$style = [
			'font-size:'.intval($p['size']).'px',
			'font-weight:'.intval($p['weight']),
			'color:'.front_helper::e($p['color']),
			'line-height:'.front_helper::e($p['lineHeight']),
			'letter-spacing:'.front_helper::e($p['letterSpacing']).'px',
			'text-align:'.front_helper::e($p['align']),
			'font-family:'.self::font($p['family']),
			'white-space:pre-wrap',
		];
		return '<div class="fb-mc-text" style="'.implode(';', $style).';">'.front_helper::e($p['text']).'</div>';
	}

	protected static function font($family) {
		switch($family) {
			case 'serif':
				return "Georgia, 'Songti SC', serif";
			case 'mono':
				return 'ui-monospace, SFMono-Regular, Menlo, monospace';
			default:
				return "-apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', Arial, sans-serif";
		}
	}

	protected static function video_html($p) {
		if($p['src'] === '') {
			return '<div class="fb-mc-image-empty"></div>';
		}
		$attrs = $p['controls'] ? '' : '';
		$attrs .= $p['autoplay'] ? ' autoplay' : '';
		$attrs .= $p['loop'] ? '' : '';
		$attrs .= $p['muted'] ? ' muted' : '';
		$poster = $p['poster'] !== '' ? ' poster="'.front_helper::e($p['poster']).'"' : '';
		return '<div class="fb-mc-video-wrap"><video class="fb-mc-video" src="'.front_helper::e($p['src']).'"'.$poster.$attrs.' playsinline style="width:100%;height:'.intval($p['height']).'px;object-fit:cover;"></video>'.($p['link'] !== '' && self::link_url($p['link']) !== '' ? '<a class="fb-mc-video-link" href="'.front_helper::e(self::link_url($p['link'])).'" aria-label="查看详情"></a>' : '').'</div>';
	}

	protected static function search_html($p) {
		$target = self::link_url($p['action'] ?: 'page:search');
		$parts = parse_url($target);
		$params = [];
		parse_str($parts['query'] ?? '', $params);
		$action = explode('?', explode('#', $target)[0])[0];
		$html = '<form class="fb-mc-search" method="get" action="'.front_helper::e($action).'">';
		foreach($params as $key => $value) {
			if($key !== 'keyword' && is_scalar($value)) {
				$html .= '<input type="hidden" name="'.front_helper::e($key).'" value="'.front_helper::e($value).'" />';
			}
		}
		$style = 'background:'.front_helper::e($p['bg']).';color:'.front_helper::e($p['color']).';';
		$keyword = isset($_GET['keyword']) && is_scalar($_GET['keyword']) ? mb_substr((string)$_GET['keyword'], 0, 40, 'UTF-8') : '';
		$html .= '<input class="fb-mc-search-input" type="search" name="keyword" maxlength="40" value="'.front_helper::e($keyword).'" placeholder="'.front_helper::e($p['placeholder']).'" style="'.$style.'" />';
		$html .= '<button class="fb-mc-search-btn" type="submit" aria-label="搜索">'.front_icon::svg('search').'</button>';
		return $html.'</form>';
	}

	/** Reuse the native controllers and templates so member actions keep one implementation. */
	protected static $title_context=[];

	protected static function business_html($source, $options=[]) {
  $fb_member_options=self::member_options($options);$fb_social_options=$options;$fb_region_options=$options['regions']??[];
  $fb_separate_highlights=!empty($options['sectionsDetached']);$fb_separate_details=!empty($options['sectionsDetached']);
		global $_G;
		$modules = ['home' => 'index', 'catalog' => 'catalog', 'search' => 'catalog', 'favorites' => 'favorites', 'mine' => 'mine', 'detail' => 'read', 'history' => 'history', 'preference' => 'preference'];
		$module = $modules[$source];
		$restore_product = $_GET['productid'] ?? null;
		if($source === 'detail') {
			if(!empty($_GET['productid'])) { $module = 'style'; if(!empty($_GET['albumid'])) { $valid=false; foreach(front_view::product_albums(intval($_GET['productid'])) as $album) { if(intval($album['albumid'])===intval($_GET['albumid']))$valid=true; } if(!$valid) $_GET['productid']=0; } }
			elseif(empty($_GET['albumid'])) { $module = 'style'; }
			else { $album=table_flipbook_album::t()->fetch(intval($_GET['albumid'])); $_GET['productid'] = $album && !empty($album['available']) ? front_view::first_productid(intval($_GET['albumid'])) : 0; $module = 'style'; }
		}
		$fb_micro_embed = true;
		ob_start();
		include dirname(__DIR__).'/'.$module.'.inc.php';
		self::$title_context=['title'=>html_entity_decode($fb['product']['name']??$fb['heroes'][0]['name']??'',ENT_QUOTES,'UTF-8'),'subtitle'=>html_entity_decode($fb['product']['summary']??$fb['heroes'][0]['subtitle']??'',ENT_QUOTES,'UTF-8')];
		$html = ob_get_clean();
		if($restore_product === null) unset($_GET['productid']); else $_GET['productid'] = $restore_product;
		return '<section class="fb-mc-business fb-mini" data-source="'.front_helper::e($source).'">'.$html.'</section>';
	}

	protected static function divider_html($p) {
		$style = 'border-top:'.intval($p['thickness']).'px '.($p['dashed'] ? 'dashed' : 'solid').' '.front_helper::e($p['color']).';width:'.intval($p['width']).'%;margin:0 auto;';
		return '<div class="fb-mc-divider" style="'.$style.'"></div>';
	}

	protected static function tab_html($p) {
		$tabs = $p['tabs'];
		if(!$tabs) {
			return '';
		}
		$html = '<div class="fb-mc-tab'.(!$p['indicator'] ? ' fb-mc-no-indicator' : '').'" data-fb-micro-tab data-active-color="'.front_helper::e($p['activeColor']).'" data-color="'.front_helper::e($p['color']).'">';
		$html .= '<div class="fb-mc-tabhead">';
		foreach($tabs as $index => $tab) {
			$style = 'color:'.front_helper::e($index === 0 ? $p['activeColor'] : $p['color']).';font-size:'.intval($p['size']).'px;';
			$html .= '<button type="button" class="fb-mc-tabbtn'.($index === 0 ? ' is-active' : '').'" data-tab="'.$index.'" style="'.$style.'">'.front_helper::e($tab['title']).'</button>';
		}
		$html .= '</div>';
		$html .= '<div class="fb-mc-tabpanels">';
		foreach($tabs as $index => $tab) {
			$html .= '<div class="fb-mc-tabpanel'.($index === 0 ? ' is-active' : '').'" data-tabpanel="'.$index.'" style="display:'.($index === 0 ? 'flex' : 'none').';flex-direction:column;gap:12px;">'.self::components_html($tab['children']).'</div>';
		}
		return $html.'</div></div>';
	}
}
