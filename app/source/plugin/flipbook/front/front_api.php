<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 只读内容接口
 *
 * 供原生微信小程序、移动 Web 与后续海报任务复用同一份内容协议。
 * 规则：
 * 1. 只输出公开内容与当前原生会话范围内的会员计数，不输出服务端配置、密钥或原始表结构；
 * 2. 会员数据只按原生 UID 读取，不接受客户端传入的 uid；
 * 3. 只返回上架内容，价格按后台开关决定是否出现；
 * 4. 响应为受控 JSON，失败时给出受控错误码。
 */

namespace flipbook;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_api {

	/**
	 * 输出内容协议。
	 */
	public static function respond() {
  if(front_micro::is_custom_key($_GET['key']??null) && !isset(front_micro::keys()[$_GET['key']]))self::fail('gone','页面不存在或已删除');
  if(isset($_GET['list'])){
   $binding=is_scalar($_GET['list'])?(string)$_GET['list']:'';$key=self::microkey();
   if(!front_list::supports($binding,$key))self::fail('bad_list','列表不存在');
   self::output(array_merge(['ok'=>1],front_list::page($binding,self::uint('list_page'))));
  }
  if(is_string($_GET['commerce_action']??null)&&$_GET['commerce_action']!=='')front_commerce::handle($_GET['commerce_action']);
		$settings = front_view::settings();
		$data = [
			'ok' => 1,
			'version' => '1',
			'app' => [
				'name' => $settings['albumname'],
				'description' => $settings['description'],
				'showprice' => $settings['showprice'] === '1' ? 1 : 0,
				'pricelabel' => $settings['pricelabel'],
				'currency' => $settings['currency'],
				'sharetitle' => $settings['sharetitle'],
				'sharetext' => $settings['sharetext'],
				'postertemplate' => $settings['postertemplate'],
    'share'=>front_share::settings(),
			],
			'series' => [],
			'categories' => [],
			'albums' => [],
			'products' => [],
			'formhash' => front_helper::formhash(),
   'login'=>['wechat'=>front_service_config::get('wechat_enabled')==='1' && front_service_config::get('wechat_appid')!=='' && front_service_config::get('wechat_secret')!==''],
   'commerce'=>front_commerce::state(),
			'micropages' => front_micro::versions(),
		];

		// 微页面：按 key 返回整页配置与版本号，小程序据此自动热加载
		$microkey = self::microkey();
		if($microkey !== '') {
			$page = front_micro::page($microkey);
   $data['composerContext']=front_composer::context($microkey,self::uint('productid'));
			$data['micropage'] = [
				'key' => $page['key'],
				'name' => $page['name'],
				'version' => $page['version'],
				'config' => $page['config'],
			];
   $data['member']=$data['composerContext']['member'];
   if(!empty($data['composerContext']['pagination']))self::output($data);
		}

		foreach(front_view::series_list() as $series) {
			$data['series'][] = [
				'seriesid' => $series['seriesid'],
				'name' => $series['name'],
				'description' => $series['description'],
				'cover' => $series['cover'],
				'albumcount' => $series['count'],
			];
		}
		foreach(front_view::category_list() as $category) {
			$data['categories'][] = [
				'catid' => $category['catid'],
				'name' => $category['name'],
			];
		}
		foreach(front_view::albums(self::uint('seriesid'), self::uint('catid'), self::keyword()) as $album) {
			$data['albums'][] = self::album($album);
		}
		foreach(table_flipbook_product::t()->fetch_all_available(self::uint('catid'), self::uint('seriesid')) as $row) {
			$keyword = self::keyword();
			if($keyword !== '' && mb_stripos($row['name'].' '.$row['code'].' '.$row['summary'], $keyword, 0, 'UTF-8') === false) { continue; }
			$product = front_view::product($row, $settings);
			$data['products'][] = [
				'productid' => $product['productid'],
				'name' => $product['name'],
				'code' => $product['code'],
				'summary' => $product['summary'],
				'cover' => $product['cover'],
				'price' => $product['price'],
			];
		}

		// 单册内容：目录、内容页与每页关联款式
		$albumid = self::uint('albumid');
		if($albumid > 0) {
			$album = self::find_album($albumid);
			if(!$album) {
				self::fail('gone', '内容已下架');
			}
			$album['pages'] = front_view::pages($albumid);
			$data['album'] = $album;
		}

		// 单款详情：卖点、细节、模特图组与规格
		$productid = self::uint('productid');
		if($productid > 0) {
			$row = table_flipbook_product::t()->fetch($productid);
			if(!$row || empty($row['available'])) {
				self::fail('gone', '内容已下架');
			}
			$detail = front_view::product_detail($row);
			$detail['albums'] = front_view::product_albums($productid);
			$data['product'] = $detail;
		}

		$uid = front_helper::uid();
		if($microkey==='history')$data['history']=self::history();
		if($uid > 0) {
			$favorites = front_view::favorites($uid);
			$data['member'] = [
				'uid' => $uid,
				'name' => front_helper::member_name(),
    'avatar'=>front_auth::member()['avatar'],'group'=>front_auth::member()['group'],'adminid'=>front_auth::member()['adminid'],
				'favorite' => $favorites['counts'],
				'favorites' => $favorites,
				'historycounts' => front_view::history_counts($uid),
				'resume' => front_view::resume($uid),
				'preference' => front_view::preference($uid),
			];
		} else {
			$data['member'] = ['uid' => 0, 'name' => '访客'];
		}

		self::output($data);
	}

	/** History belongs to the native session; request parameters never choose the member. */
	protected static function history() {
		$uid=front_helper::uid();
		$action=isset($_GET['action'])&&$_GET['action']==='share'?'share':'read';
		return ['action'=>$action,'groups'=>front_view::history_list($uid,$action),'counts'=>front_view::history_counts($uid)];
	}

	protected static function album($album) {
  $id=front_view::first_productid($album['albumid']);$product=$id?table_flipbook_product::t()->fetch($id):[];$spec=front_helper::spec($product['spec']??'');
		return [
   'overlayLines'=>$spec['overlayLines'],'floatingImage'=>$spec['floatingImage'],
			'albumid' => $album['albumid'],
			'name' => $album['name'],
			'subtitle' => $album['subtitle'],
			'description' => $album['description'],
			'cover' => $album['cover'],
			'video' => $album['video'],
   'firstframe'=>$album['firstframe']??$album['cover'],
			'hasvideo' => $album['hasvideo'],
			'seriesid' => $album['seriesid'],
			'catid' => $album['catid'],
			'pagecount' => $album['pagecount'],
			'pagecountpad' => $album['pagecountpad'],
			'firstproductid' => front_view::first_productid($album['albumid']),
		];
	}

	protected static function find_album($albumid) {
		foreach(front_view::albums() as $album) {
			if($album['albumid'] === $albumid) {
				return self::album($album);
			}
		}
		return [];
	}

	protected static function uint($key) {
		return isset($_GET[$key]) ? max(0, intval($_GET[$key])) : 0;
	}

	/**
	 * 微页面标识：只接受已登记的键，未知值按未指定处理。
	 */
	protected static function microkey() {
		$key = isset($_GET['key']) && is_scalar($_GET['key']) ? preg_replace('/[^a-z0-9_]/', '', strtolower((string)$_GET['key'])) : '';
		return isset(front_micro::keys()[$key]) ? $key : '';
	}

	/**
	 * 关键词：只用于内容检索，长度受限并剥离控制字符。
	 */
	protected static function keyword() {
		$keyword = isset($_GET['keyword']) ? (string)$_GET['keyword'] : '';
		$keyword = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $keyword));
		return mb_substr($keyword, 0, 40, 'UTF-8');
	}

	protected static function fail($code, $message) {
		self::output(['ok' => 0, 'code' => $code, 'message' => $message]);
	}

	protected static function output($data) {
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
