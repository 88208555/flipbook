<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 前台视图模型
 *
 * 约定：
 * 1. 视图模型只做“取数 + 校验 + 转义”，文字与属性值在进入模型时统一经 front_helper::e()，
 *    模板只负责输出，不自行拼接转义，也不写内联样式；
 * 2. 所有排序、分页、字段均来自白名单，查询使用占位符，不接受原始请求值拼 SQL；
 * 3. 会员数据只按当前原生 UID 读取，不接收客户端传入的 uid；
 * 4. 价格默认关闭：只有后台开启展示且款式自身标记展示时才输出价格字段。
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_view {

	const HISTORY_LIMIT = 60;

	/**
	 * 插件业务配置（键值表），缺键时使用受控默认值。
	 */
	public static function settings() {
		$defaults = [
			'albumname' => '工装画册',
			'description' => '服装画册',
			'showprice' => '0',
			'pricelabel' => '参考价',
			'currency' => 'CNY',
			'postertemplate' => 'default',
			'sharetitle' => '',
			'sharetext' => '',
		];
		$rows = table_flipbook_setting::t()->fetch_all_setting();
		foreach($defaults as $key => $value) {
			if(isset($rows[$key]) && trim((string)$rows[$key]) !== '') {
				$defaults[$key] = (string)$rows[$key];
			}
		}
		return $defaults;
	}

	/**
	 * 页面外壳数据：配置、会员、导航、地址。
	 */
	public static function base($module, $title) {
		$settings = self::settings();
		$nav = [];
		foreach(front_helper::modules() as $key => $label) {
			$nav[] = [
				'key' => $key,
				'label' => front_helper::e($label),
				'url' => front_helper::url($key),
				'active' => $key === $module,
			];
		}
		return [
			'app' => [
				'name' => front_helper::e($settings['albumname']),
				'description' => front_helper::e($settings['description']),
				'showprice' => $settings['showprice'] === '1' ? 1 : 0,
				'pricelabel' => front_helper::e($settings['pricelabel']),
				'currency' => front_helper::e($settings['currency']),
				'postertemplate' => front_helper::e($settings['postertemplate']),
				'sharetitle' => front_helper::e($settings['sharetitle']),
				'sharetext' => front_helper::e($settings['sharetext']),
				'cssurl' => front_helper::asset('css/flipbook.css').'?'.self::verhash(),
				'jsurl' => front_helper::asset('js/flipbook.js').'?'.self::verhash(),
			],
			'module' => $module,
			'title' => front_helper::e($title),
			'formhash' => front_helper::formhash(),
			'member' => [
				'uid' => front_helper::uid(),
				'logged' => front_helper::is_login(),
				'name' => front_helper::e(front_helper::member_name()),
    'avatar'=>front_helper::e(front_auth::member()['avatar']), 'group'=>front_helper::e(front_auth::member()['group']),
				'loginurl' => front_helper::e(front_helper::login_url()),
				'logouturl' => front_helper::e('member.php?mod=logging&action=logout&formhash='.front_helper::formhash()),
			],
			'nav' => $nav,
			'urls' => [
				'action' => front_helper::url('action'),
				'json' => front_helper::url('api'),
				'index' => front_helper::url('index'),
				'catalog' => front_helper::url('catalog'),
				'favorites' => front_helper::url('favorites'),
				'mine' => front_helper::url('mine'),
			],
		];
	}

	/**
	 * 静态资源指纹：按文件修改时间生成，改样式或脚本后浏览器不会拿到旧副本。
	 */
	protected static function verhash() {
		$file = dirname(__DIR__).'/static/css/flipbook.css';
		$js = dirname(__DIR__).'/static/js/flipbook.js';
		$stamp = max((int)@filemtime($file), (int)@filemtime($js));
		return $stamp > 0 ? (string)$stamp : '0';
	}

	// ------------------------------------------------------------------
	// 画册与内容页
	// ------------------------------------------------------------------

	/**
	 * 画册视图。
	 */
	public static function album($row) {
  $first=table_flipbook_product::t()->fetch(self::first_productid(intval($row['albumid'])));$spec=front_helper::spec($first['spec']??'');
		$cover = front_helper::img($row['cover']);
		$video = isset($row['video']) ? front_helper::img($row['video']) : '';
		return [
			'albumid' => intval($row['albumid']),
   'code'=>front_helper::e($first['code']??''),'overlayLines'=>array_map([front_helper::class,'e'],$spec['overlayLines']),'floatingImage'=>['src'=>front_helper::e($spec['floatingImage']['src']),'href'=>front_helper::e(front_micro::link_url($spec['floatingImage']['link']))],
			'name' => front_helper::e($row['name']),
			'subtitle' => front_helper::e($row['subtitle']),
			'description' => front_helper::e($row['description']),
			'cover' => front_helper::e($cover),
   'productCover'=>front_helper::e(front_helper::img($first['cover']??'')),
			'hascover' => $cover !== '' ? 1 : 0,
			'video' => front_helper::e($video),
   'firstframe'=>front_helper::e(front_helper::first_frame($video,$cover)),
			'hasvideo' => $video !== '' ? 1 : 0,
			'pagecount' => intval($row['pagecount']),
			'pagecountpad' => front_helper::pad($row['pagecount']),
			'seriesid' => intval($row['seriesid']),
			'catid' => intval($row['catid']),
			'url' => front_helper::url('read', ['albumid' => intval($row['albumid'])]),
			'detailurl' => front_helper::url('index', ['view' => 'album', 'albumid' => intval($row['albumid'])]),
		];
	}

	/**
	 * 内容页视图。
	 */
	public static function page($row, $index) {
		$image = front_helper::img($row['image']);
		return [
			'pageid' => intval($row['pageid']),
			'albumid' => intval($row['albumid']),
			'index' => intval($index),
			'indexpad' => front_helper::pad($index),
			'title' => front_helper::e($row['title']),
			'eyebrow' => front_helper::e($row['eyebrow']),
			'description' => front_helper::e($row['description']),
			'tags' => array_map([front_helper::class, 'e'], front_helper::tags($row['tags'])),
			'image' => front_helper::e($image),
			'hasimage' => $image !== '' ? 1 : 0,
			'products' => [],
		];
	}

	/**
	 * 画册的全部内容页，附带每页关联款式。
	 */
	public static function pages($albumid) {
		$pages = [];
		$index = 0;
		foreach(table_flipbook_page::t()->fetch_all_available_by_album($albumid) as $row) {
			$index++;
			$page = self::page($row, $index);
			$page['products'] = [];
			foreach(table_flipbook_page_product::t()->fetch_productids_by_page($page['pageid']) as $productid) {
				$page['products'][] = $productid;
			}
			$pages[] = $page;
		}
		return $pages;
	}

	/**
	 * 画册列表：按系列、分类、关键词过滤，过滤条件全部走占位符。
	 */
	public static function albums($seriesid = 0, $catid = 0, $keyword = '') {
		$sql = 'SELECT * FROM %t WHERE available=1';
		$args = ['flipbook_album'];
		if($seriesid > 0) {
			$sql .= ' AND seriesid=%d';
			$args[] = $seriesid;
		}
		if($catid > 0) {
			$sql .= ' AND catid=%d';
			$args[] = $catid;
		}
		if($keyword !== '') {
			$sql .= ' AND (name LIKE %s OR subtitle LIKE %s OR description LIKE %s)';
			$like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';
			$args[] = $like;
			$args[] = $like;
			$args[] = $like;
		}
		$sql .= ' ORDER BY displayorder, albumid';
		$albums = [];
		foreach(DB::fetch_all($sql, $args) as $row) {
			$albums[] = self::album($row);
		}
		return $albums;
	}

	/**
	 * 系列与分类选项（已转义，供筛选条直接输出）。
	 */
	public static function series_list() {
		$items = [];
		foreach(table_flipbook_series::t()->fetch_all_available() as $row) {
			$cover = front_helper::img($row['cover']);
			$items[] = [
				'seriesid' => intval($row['seriesid']),
				'name' => front_helper::e($row['name']),
				'description' => front_helper::e($row['description']),
				'cover' => front_helper::e($cover),
				'hascover' => $cover !== '' ? 1 : 0,
				'url' => front_helper::url('catalog', ['seriesid' => intval($row['seriesid'])]),
				'count' => count(self::albums(intval($row['seriesid']))),
			];
		}
		return $items;
	}

	public static function category_list() {
		$items = [];
		foreach(table_flipbook_category::t()->fetch_all_order(['displayorder' => 'ASC', 'catid' => 'ASC']) as $row) {
			if(empty($row['available'])) {
				continue;
			}
			$items[] = [
				'catid' => intval($row['catid']),
				'name' => front_helper::e($row['name']),
				'url' => front_helper::url('catalog', ['catid' => intval($row['catid'])]),
			];
		}
		return $items;
	}

	// ------------------------------------------------------------------
	// 款式
	// ------------------------------------------------------------------

	/**
	 * 款式摘要视图。
	 */
	public static function search_products($keyword, $catid = 0, $seriesid = 0) {
		if($keyword === '') { return []; }
		$items = [];
		foreach(table_flipbook_product::t()->fetch_all_available($catid, $seriesid) as $row) {
			if(mb_stripos($row['name'].' '.$row['code'].' '.$row['summary'], $keyword, 0, 'UTF-8') !== false) { $items[] = self::product($row); }
		}
		return $items;
	}

	public static function product($row, $settings = null) {
		$settings = $settings === null ? self::settings() : $settings;
		$cover = front_helper::img($row['cover']);
		$video = isset($row['video']) ? front_helper::img($row['video']) : '';
		return [
			'productid' => intval($row['productid']),
   'share' => front_share::product($row),
			'name' => front_helper::e($row['name']),
			'code' => front_helper::e($row['code']),
			'summary' => front_helper::e($row['summary']),
			'cover' => front_helper::e($cover),
			'hascover' => $cover !== '' ? 1 : 0,
			'video' => front_helper::e($video),
   'firstframe'=>front_helper::e(front_helper::first_frame($video,$cover)),
			'hasvideo' => $video !== '' ? 1 : 0,
			'url' => front_helper::url('style', ['productid' => intval($row['productid'])]),
			'price' => self::price($row, $settings),
		];
	}

	/**
	 * 价格块：默认关闭；未设置或不展示时输出空数组，模板不渲染价格区域。
	 */
	protected static function price($row, $settings) {
		if(empty($settings['showprice']) || empty($row['priceshow'])) {
			return [];
		}
		$value = number_format((float)$row['pricevalue'], 2, '.', '');
		$original = isset($row['priceorigin']) ? number_format((float)$row['priceorigin'], 2, '.', '') : '0.00';
		return [
			'label' => front_helper::e($settings['pricelabel']),
			'value' => front_helper::e($value),
			'original' => (float)$original > (float)$value ? front_helper::e($original) : '',
			'currency' => front_helper::e($settings['currency']),
		];
	}

	/**
	 * 款式详情视图：卖点、细节、模特三组图与规格参数。
	 */
	public static function product_detail($row) {
		$settings = self::settings();
		$product = self::product($row, $settings);
		$product['description'] = front_helper::e($row['description']);
		$product['groups'] = [
			'highlight' => self::media(intval($row['productid']), 'highlight'),
			'detail' => self::media(intval($row['productid']), 'detail'),
			'model' => self::media(intval($row['productid']), 'model'),
			'sku' => front_sku::media(intval($row['productid'])),
		];
		$spec = front_helper::spec($row['spec']);
		$product['spec'] = [
			'colors' => array_map([front_helper::class, 'e'], $spec['colors']),
			'sizes' => array_map([front_helper::class, 'e'], $spec['sizes']),
			'sizetable' => array_map(function ($row) {
				return array_map([front_helper::class, 'e'], $row);
			}, $spec['sizetable']),
			'model' => front_helper::e($spec['model']),
			'fit' => front_helper::e($spec['fit']),
		];
		$product['spec']['sizeTableNote']=$spec['sizeTableNote'];
		$product['skus']=front_sku::rows(intval($row['productid']),true);
  foreach(['supplier','overlayLines','floatingImage','fields','content','contentHtml'] as $key)$product['spec'][$key]=$spec[$key]??'';
		$product['colors'] = [];
		foreach($product['spec']['colors'] as $index => $name) {
			$product['colors'][] = [
				'name' => $name,
				'tone' => self::tone($name),
				'active' => $index === 0 ? 1 : 0,
			];
		}
		// 卖点与细节的说明文字：优先按标点拆分简介，逐条分配，避免同一段话重复。
		$clauses = preg_split('/[，。；、]/u', (string)$row['summary'], -1, PREG_SPLIT_NO_EMPTY);
		foreach(['highlight', 'detail'] as $type) {
			foreach($product['groups'][$type] as $index => $media) {
				$text = isset($clauses[$index]) ? $clauses[$index] : (string)$row['summary'];
				$product['groups'][$type][$index]['text'] = front_helper::e($text);
			}
		}
		return $product;
	}

	/**
	 * 颜色名到黑白灰色阶的映射，仅用于色块展示，不参与数据存储。
	 */
	protected static function tone($name) {
		$name = (string)$name;
		if(strpos($name, '黑') !== false) {
			return 3;
		}
		if(strpos($name, '白') !== false) {
			return 0;
		}
		if(strpos($name, '浅') !== false) {
			return 1;
		}
		return 2;
	}

	/**
	 * 款式图组，按白名单类型读取。
	 */
	public static function media($productid, $type) {
		$items = [];
		foreach(table_flipbook_product_media::t()->fetch_all_by_product_type($productid, $type) as $row) {
			$image = front_helper::img($row['image']);
			$items[] = [
				'mediaid' => intval($row['mediaid']),
				'productid' => intval($row['productid']),
				'type' => front_helper::e($row['type']),
				'title' => front_helper::e($row['title']),
				'image' => front_helper::e($image),
				'hasimage' => $image !== '' ? 1 : 0,
			];
		}
		return $items;
	}

	/**
	 * 款式与画册的关联：该款式出现在哪些画册中。
	 */
	public static function product_albums($productid) {
		$albumids = [];
		foreach(DB::fetch_all('SELECT p.albumid FROM %t pp INNER JOIN %t p ON p.pageid=pp.pageid WHERE pp.productid=%d', ['flipbook_page_product', 'flipbook_page', $productid]) as $row) {
			$albumids[intval($row['albumid'])] = intval($row['albumid']);
		}
		$albums = [];
		foreach($albumids as $albumid) {
			$row = DB::fetch_first('SELECT * FROM %t WHERE albumid=%d AND available=1', ['flipbook_album', $albumid]);
			if($row) {
				$albums[] = self::album($row);
			}
		}
		return $albums;
	}

	/**
	 * 画册首个关联款式的地址，用于阅读页与发现页的“款式”入口。
	 * 没有关联款式时返回空字符串，模板据此不渲染入口。
	 */
	public static function first_style_url($albumid) {
		$productid = self::first_productid($albumid);
		return $productid > 0 ? front_helper::url('style', ['productid' => $productid]) : '';
	}

	/**
	 * 画册首个关联款式 ID，供小程序“款式”入口直接跳转。
	 */
	public static function first_productid($albumid) {
		$page = DB::fetch_first('SELECT pageid FROM %t WHERE albumid=%d AND available=1 ORDER BY displayorder, pageid LIMIT 1', ['flipbook_page', $albumid]);
		if(!$page) {
			return 0;
		}
		return intval(DB::result_first('SELECT pp.productid FROM %t pp INNER JOIN %t p ON p.productid=pp.productid WHERE pp.pageid=%d AND p.available=1 ORDER BY pp.displayorder, pp.productid LIMIT 1', ['flipbook_page_product', 'flipbook_product', intval($page['pageid'])]));
	}

	// ------------------------------------------------------------------
	// 收藏 / 记录 / 偏好
	// ------------------------------------------------------------------

	/**
	 * 当前会员的收藏目标集合，按类型返回。
	 */
	public static function favorite_ids($uid) {
		$sets = ['album' => [], 'product' => []];
		if($uid <= 0) {
			return $sets;
		}
		foreach(DB::fetch_all('SELECT targettype, targetid FROM %t WHERE uid=%d', ['flipbook_favorite', $uid]) as $row) {
			if(isset($sets[$row['targettype']])) {
				$sets[$row['targettype']][intval($row['targetid'])] = true;
			}
		}
		return $sets;
	}

	/**
	 * 收藏列表：款式与画册分别组装，数量与列表来自同一份数据。
	 */
	public static function favorites($uid, $order = 'recent') {
		$order = in_array($order, ['recent', 'oldest', 'name'], true) ? $order : 'recent';
		$rows = [];
		if($uid > 0) {
			$rows = DB::fetch_all('SELECT targettype, targetid, dateline FROM %t WHERE uid=%d', ['flipbook_favorite', $uid]);
			usort($rows, function ($a, $b) use ($order) {
				if($order === 'oldest') {
					return intval($a['dateline']) <=> intval($b['dateline']);
				}
				return intval($b['dateline']) <=> intval($a['dateline']);
			});
		}
		$settings = self::settings();
		$products = [];
		$albums = [];
		foreach($rows as $row) {
			if($row['targettype'] === 'product') {
				$product = DB::fetch_first('SELECT * FROM %t WHERE productid=%d AND available=1', ['flipbook_product', intval($row['targetid'])]);
				if($product) {
					$item = self::product($product, $settings);
					$spec = front_helper::spec($product['spec']);
					$item['color'] = $spec['colors'] ? front_helper::e($spec['colors'][0]) : '';
					$item['favorited'] = 1;
					$products[] = $item;
				}
			} elseif($row['targettype'] === 'album') {
				$album = DB::fetch_first('SELECT * FROM %t WHERE albumid=%d AND available=1', ['flipbook_album', intval($row['targetid'])]);
				if($album) {
					$item = self::album($album);
					$item['favorited'] = 1;
					$albums[] = $item;
				}
			}
		}
		if($order === 'name') {
			usort($products, function ($a, $b) {
				return strcmp((string)$a['name'], (string)$b['name']);
			});
			usort($albums, function ($a, $b) {
				return strcmp((string)$a['name'], (string)$b['name']);
			});
		}
		return [
			'products' => $products,
			'albums' => $albums,
			'counts' => [
				'total' => count($products) + count($albums),
				'product' => count($products),
				'album' => count($albums),
			],
		];
	}

	/**
	 * 继续阅读：取最近一条带进度的阅读记录。
	 */
	public static function resume($uid) {
		if($uid <= 0) {
			return [];
		}
		$row = DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND action=%s AND progress<>\'\' ORDER BY dateline DESC LIMIT 1', ['flipbook_history', $uid, 'read']);
		if(!$row) {
			return [];
		}
		$album = DB::fetch_first('SELECT * FROM %t WHERE albumid=%d AND available=1', ['flipbook_album', intval($row['targetid'])]);
		if(!$album) {
			return [];
		}
		$progress = json_decode((string)$row['progress'], true);
		$page = is_array($progress) && isset($progress['page']) ? max(1, intval($progress['page'])) : 1;
		$total = max(1, intval($album['pagecount']));
		$page = min($page, $total);
		$item = self::album($album);
		$item['progress'] = [
			'page' => $page,
			'pagepad' => front_helper::pad($page),
			'total' => $total,
			'totalpad' => front_helper::pad($total),
			'percent' => (int)round($page / $total * 100),
			'readurl' => front_helper::url('read', ['albumid' => intval($album['albumid']), 'page' => $page]),
			'dateline' => front_helper::e(dgmdate($row['dateline'], 'Y-m-d H:i')),
		];
		return $item;
	}

	/**
	 * 阅读与分享记录，按“今天 / 昨天 / 更早”分组。
	 */
	public static function history_list($uid, $action = 'read', $limit = self::HISTORY_LIMIT, $start = 0) {
		$action = in_array($action, ['read', 'share'], true) ? $action : 'read';
		$groups = [];
		if($uid <= 0) {
			return $groups;
		}
		$rows = DB::fetch_all('SELECT * FROM %t WHERE uid=%d AND action=%s ORDER BY dateline DESC,historyid DESC LIMIT %d,%d', ['flipbook_history', $uid, $action, max(0,intval($start)), max(1,min(100,intval($limit)))]);
		$today = dgmdate(TIMESTAMP, 'Y-m-d');
		$yesterday = dgmdate(TIMESTAMP - 86400, 'Y-m-d');
		foreach($rows as $row) {
			$day = dgmdate($row['dateline'], 'Y-m-d');
			$label = $day === $today ? '今天' : ($day === $yesterday ? '昨天' : $day);
			$title = '';
			$url = '';
			$cover = '';
			$meta = '';
			if($row['targettype'] === 'album') {
				$target = DB::fetch_first('SELECT * FROM %t WHERE albumid=%d', ['flipbook_album', intval($row['targetid'])]);
				$title = $target ? $target['name'] : '画册已下架';
				$url = $target ? front_helper::url('read', ['albumid' => intval($row['targetid'])]) : '';
				$cover = $target ? front_helper::img($target['cover']) : '';
				$meta = $target ? max(1, intval($target['pagecount'])).' 页' : '';
			} else {
				$target = DB::fetch_first('SELECT * FROM %t WHERE productid=%d', ['flipbook_product', intval($row['targetid'])]);
				$title = $target ? $target['name'] : '款式已下架';
				$url = $target ? front_helper::url('style', ['productid' => intval($row['targetid'])]) : '';
				$cover = $target ? front_helper::img($target['cover']) : '';
				$meta = $target ? (string)$target['code'] : '';
			}
			$progress = json_decode((string)$row['progress'], true);
			$page = is_array($progress) && isset($progress['page']) ? intval($progress['page']) : 0;
			$total = is_array($progress) && isset($progress['total']) ? intval($progress['total']) : 0;
			$readurl = $url;
			if($row['targettype'] === 'album' && $url !== '' && $page > 0) {
				$readurl = front_helper::url('read', ['albumid' => intval($row['targetid']), 'page' => $page]);
			}
			$groups[$label][] = [
				'historyid' => intval($row['historyid']),
				'time' => front_helper::e(dgmdate($row['dateline'], 'H:i')),
				'action' => front_helper::e($action === 'share' ? '分享' : '阅读'),
				'targettype' => front_helper::e($row['targettype']),
				'title' => front_helper::e($title),
				'meta' => front_helper::e($meta),
				'cover' => front_helper::e($cover),
				'hascover' => $cover !== '' ? 1 : 0,
				'progress' => $page > 0 && $total > 0 ? front_helper::pad($page).' / '.front_helper::pad($total) : '',
				'pagepad' => front_helper::pad(max(1, $page)),
				'totalpad' => front_helper::pad(max(1, $total)),
				'percent' => $total > 0 ? min(100, (int)round($page / $total * 100)) : 0,
				'url' => front_helper::e($readurl),
				'available' => $url !== '' ? 1 : 0,
			];
		}
		return $groups;
	}

	/**
	 * 阅读与分享记录条数，用于标签计数。
	 */
	public static function history_counts($uid) {
		$counts = ['read' => 0, 'share' => 0];
		if($uid > 0) {
			$rows = DB::fetch_all('SELECT action, COUNT(*) AS total FROM %t WHERE uid=%d GROUP BY action', ['flipbook_history', $uid]);
			foreach($rows as $row) {
				if(isset($counts[$row['action']])) {
					$counts[$row['action']] = intval($row['total']);
				}
			}
		}
		return $counts;
	}

	/**
	 * 阅读偏好：只接受登记过的键与取值。
	 */
	public static function preference($uid) {
		$defaults = [
			'flip' => 'horizontal',
			'gesture' => 'on',
			'thumbnails' => 'on',
			'resume' => 'on',
			'quality' => 'high',
		];
		$current = [];
		foreach($defaults as $key => $value) {
			$current[$key] = $value;
		}
		return $current;
	}

	/**
	 * 偏好项的可选值白名单。
	 */
	public static function preference_options($key) {
		$options = [
			'flip' => ['horizontal' => '左右翻页', 'vertical' => '点击翻页'],
			'gesture' => ['on' => '显示翻页与换册提示', 'off' => '不显示提示'],
			'thumbnails' => ['on' => '进入画册时显示页面预览', 'off' => '默认收起缩略图'],
			'resume' => ['on' => '从上次的位置继续', 'off' => '每次从头开始'],
			'quality' => ['standard' => '标准', 'high' => '高清'],
		];
		return isset($options[$key]) ? $options[$key] : [];
	}
}
