<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 前台公共能力
 *
 * 本类只解决四件事，避免前台模块各自拼装：
 * 1. 输出转义：所有进入 HTML 上下文的文字、属性值统一走 e()；
 * 2. 地址生成：插件模块地址、静态资源地址集中生成，不散落字符串拼接；
 * 3. 资源校验：图片引用只接受站内路径与 http(s)，拒绝脚本与目录回溯；
 * 4. 会员状态：只读原生 $_G['uid']、用户组与 formhash，不建立平行会话。
 */

namespace flipbook;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_helper {

	const IDENTIFIER = 'flipbook';

	/**
	 * 插件前台模块标识与导航文案。
	 */
	public static function modules() {
		return [
			'index' => '发现',
			'catalog' => '分类',
			'favorites' => '收藏',
			'mine' => '我的',
		];
	}

	/**
	 * 当前模块，用于导航高亮。
	 */
	public static function module() {
		if(!empty($_GET['id'])) {
			$parts = explode(':', (string)$_GET['id']);
			$module = isset($parts[1]) ? $parts[1] : $parts[0];
			if(preg_match('/^[a-z0-9_\-]+$/i', $module)) {
				return $module;
			}
		}
		return 'index';
	}

	/**
	 * 生成插件前台地址。
	 *
	 * @param string $module 模块名
	 * @param array  $params 追加参数，值为 null 或空字符串时忽略
	 * @return string
	 */
	public static function url($module = 'index', $params = []) {
		if(in_array($module, ['index', 'catalog', 'favorites', 'mine', 'style', 'history', 'preference'], true)) {
			$keys = ['index' => 'home', 'catalog' => !empty($params['keyword']) ? 'search' : 'catalog', 'favorites' => 'favorites', 'mine' => 'mine', 'style' => 'detail', 'history' => 'history', 'preference' => 'preference'];
			$params = ['key' => $keys[$module]] + $params;
			$module = 'micro';
		}
		$query = 'plugin.php?id='.self::IDENTIFIER.':'.rawurlencode($module);
		foreach($params as $key => $value) {
			if($value === null || $value === '') {
				continue;
			}
			$query .= '&'.rawurlencode($key).'='.rawurlencode((string)$value);
		}
		return $query;
	}

 /** The login document only accepts this plugin's micro-pages as return destinations. */
 public static function login_return($value = '') {
  if(!is_scalar($value))$value='';
  $parts=parse_url((string)$value);
  if(!$parts || isset($parts['scheme']) || isset($parts['host']) || !in_array($parts['path']??'',['plugin.php','/plugin.php'],true))return self::url('mine');
  parse_str($parts['query']??'',$query);
  if(($query['id']??'')!=='flipbook:micro' || !is_string($query['key']??null) || !isset(front_micro::keys()[$query['key']]))return self::url('mine');
  $params=['key'=>$query['key']];
  foreach(['productid','albumid','page','catid','seriesid'] as $key)if(isset($query[$key])&&is_scalar($query[$key])&&intval($query[$key])>0)$params[$key]=intval($query[$key]);
  foreach(['keyword','tab','action'] as $key)if(isset($query[$key])&&is_scalar($query[$key]))$params[$key]=mb_substr((string)$query[$key],0,100,'UTF-8');
  return self::url('micro',$params);
 }

 public static function login_url($return = '') {
  if($return==='')$return='plugin.php?'.($_SERVER['QUERY_STRING']??'');
  return self::url('micro',['key'=>'mine','login'=>1,'return'=>self::login_return($return)]);
 }

	/**
	 * 静态资源地址（相对站点根）。
	 */
	public static function asset($path) {
		return 'source/plugin/'.self::IDENTIFIER.'/static/'.ltrim((string)$path, '/');
	}

	/**
	 * 图片引用校验：只接受站内相对路径、插件静态目录与 http(s) 地址。
	 * 空值返回空字符串，由模板渲染空状态，不填充外站占位图。
	 */
	public static function img($value) {
		$value = trim((string)$value);
		if($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value) || preg_match('#(^|/)\.\.(/|$)#', $value)) {
			return '';
		}
		if(!preg_match('#^(https?://[^\s]+|/[^\s]*|data/[A-Za-z0-9_./-]+|source/plugin/[A-Za-z0-9_./-]+|static/[A-Za-z0-9_./-]+)$#i', $value)) {
			return '';
		}
		return $value;
	}

 public static function first_frame($video,$fallback='') { $path=parse_url((string)$video,PHP_URL_PATH);if(!$path)return $fallback;$candidate=preg_replace('/\.[a-zA-Z0-9]+$/','.firstframe.jpg',$path);return strpos($candidate,'..')===false && is_file(DISCUZ_ROOT.ltrim($candidate,'/'))?$candidate:$fallback; }

	/**
	 * HTML 上下文转义。
	 */
	public static function e($value) {
		return dhtmlspecialchars(is_scalar($value) || $value === null ? (string)$value : '');
	}

	/**
	 * 原生会话状态。
	 */
	public static function uid() {
		global $_G;
		return intval($_G['uid']);
	}

	public static function is_login() {
		return self::uid() > 0;
	}

	public static function formhash() {
		global $_G;
		return $_G['formhash'];
	}

	/**
	 * 会员显示名：优先原生用户名，未登录显示访客。
	 */
	public static function member_name() {
		global $_G;
		if(!self::is_login()) {
			return '访客';
		}
		return isset($_G['member']['username']) && $_G['member']['username'] !== '' ? $_G['member']['username'] : '会员';
	}

	/**
	 * 把标签字符串拆成数组，去空、去重、限长。
	 */
	public static function tags($value) {
		$items = [];
		foreach(explode(',', (string)$value) as $tag) {
			$tag = trim($tag);
			if($tag !== '' && !isset($items[$tag])) {
				$items[$tag] = $tag;
			}
		}
		return array_values($items);
	}

	/**
	 * 解析款式规格 JSON，返回受控结构：颜色、尺码、尺寸表、模特、版型说明。
	 */
	public static function spec($json) {
		$spec = [
			'colors' => [],
			'sizes' => [],
			'sizetable' => [], 'sizeTableNote'=>'',
			'model' => '',
			'fit' => '', 'supplier'=>'', 'fields'=>[], 'content'=>'','overlayLines'=>[],'floatingImage'=>['src'=>'','link'=>''],
		];
		$decoded = json_decode((string)$json, true);
		if(!is_array($decoded)) {
			return $spec;
		}
  if(is_array($decoded['sizeChart']??null)){
   if(empty($decoded['sizeChart']['enabled']))$decoded['sizetable']=[];
   $spec['sizeTableNote']=is_string($decoded['sizeChart']['note']??null)?strip_tags($decoded['sizeChart']['note']):'';
  }
		foreach(['colors', 'sizes'] as $key) {
			foreach((array)(isset($decoded[$key]) ? $decoded[$key] : []) as $item) {
				if(is_scalar($item) && trim((string)$item) !== '') {
					$spec[$key][] = trim((string)$item);
				}
			}
		}
		foreach((array)(isset($decoded['sizetable']) ? $decoded['sizetable'] : []) as $row) {
			if(!is_array($row)) {
				continue;
			}
			$cells = [];
			foreach($row as $cell) {
				$cells[] = is_scalar($cell) ? trim((string)$cell) : '';
			}
			if(implode('', $cells) !== '') {
				$spec['sizetable'][] = $cells;
			}
		}
		foreach(['model', 'fit','supplier'] as $key) {
			$spec[$key] = isset($decoded[$key]) && is_scalar($decoded[$key]) ? trim((string)$decoded[$key]) : '';
		}
  foreach(array_slice(is_array($decoded['fields']??null)?$decoded['fields']:[],0,30) as $field){if(is_array($field))$spec['fields'][]=['label'=>mb_substr(strip_tags((string)($field['label']??'')),0,60),'value'=>mb_substr(strip_tags((string)($field['value']??'')),0,2000)];}
  $spec['content']=mb_substr((string)($decoded['content']??''),0,10000);
  foreach(array_slice((array)($decoded['overlayLines']??[]),0,20) as $line)if(is_scalar($line))$spec['overlayLines'][]=strip_tags((string)$line);
  $spec['floatingImage']=['src'=>self::img($decoded['floatingImage']['src']??''),'link'=>front_micro::sanitize(['components'=>[['type'=>'image','props'=>['link'=>$decoded['floatingImage']['link']??'']]]])['components'][0]['props']['link']];
  $spec['contentHtml']=front_markdown::render($spec['content']);
		return $spec;
	}

	/**
	 * 页码边界处理：1 到总数之间。
	 */
	public static function page_index($page, $total) {
		$page = intval($page);
		$total = intval($total);
		if($total < 1) {
			return 1;
		}
		return max(1, min($total, $page));
	}

	/**
	 * 两位页码显示，如 03。
	 */
	public static function pad($number) {
		return str_pad((string)intval($number), 2, '0', STR_PAD_LEFT);
	}
}
