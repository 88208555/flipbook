<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 前台写操作
 *
 * 规则：
 * 1. 只接受 POST，并校验原生 formhash，不接受 GET 触发写操作；
 * 2. 目标 ID 只做正整数与存在性校验，收藏与记录一律写入当前原生 UID；
 * 3. 未登录时返回受控提示码，由前端提示登录或暂存本机，不静默丢弃；
 * 4. 所有响应都是受控 JSON，不回显原始 SQL 或文件路径错误。
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_action {

	/**
	 * 收藏目标类型白名单，与收藏表登记一致。
	 */
	protected static function targettypes() {
		return table_flipbook_favorite::$targettypes;
	}

	/**
	 * 入口：按 op 分发。
	 */
	public static function handle() {
		if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			self::json(0, 'method', '写操作需要 POST 请求');
		}
		if(!isset($_POST['formhash']) || $_POST['formhash'] !== front_helper::formhash()) {
			self::json(0, 'formhash', '页面已过期，请刷新后重试');
		}
		$op = isset($_GET['op']) ? (string)$_GET['op'] : '';
		switch($op) {
			case 'read':
				if(front_helper::uid()<=0)self::json(0,'needlogin','登录后可同步阅读记录');
				try{$record=self::record_read(is_string($_POST['targettype']??null)?$_POST['targettype']:'',is_scalar($_POST['targetid']??null)?intval($_POST['targetid']):0,is_scalar($_POST['page']??null)?intval($_POST['page']):1);}
				catch(\InvalidArgumentException $error){self::json(0,'param',$error->getMessage());}
				self::json(1,'','阅读已记录',$record);
				break;
			case 'favorite':
				self::favorite();
				break;
			case 'progress':
				self::progress();
				break;
			case 'share':
				self::share();
				break;
			case 'history':
				self::history();
				break;
			default:
				self::json(0, 'op', '不支持的操作');
		}
	}

	/** Called after an actual page visit, never by a content GET or editor preview. */
	public static function record_read($type,$id,$page=1){
		$uid=front_helper::uid();if($uid<=0)throw new \InvalidArgumentException('请先登录');
		if(!isset(self::targettypes()[$type])||$id<=0||!self::exists($type,$id))throw new \InvalidArgumentException('内容已下架');
		$progress='';$result=['targettype'=>$type,'targetid'=>intval($id)];
		if($type==='album'){$total=max(1,intval(DB::result_first('SELECT pagecount FROM %t WHERE albumid=%d',['flipbook_album',$id])));$page=max(1,min($total,intval($page)));$result+=['page'=>$page,'total'=>$total];$progress=json_encode(['page'=>$page,'total'=>$total]);}
		table_flipbook_history::t()->touch_read($uid,$type,$id,$progress);return $result;
	}

	/**
	 * 收藏 / 取消收藏。
	 */
	protected static function favorite() {
		$uid = front_helper::uid();
		$targettype = isset($_POST['targettype']) ? (string)$_POST['targettype'] : '';
		$targetid = isset($_POST['targetid']) ? intval($_POST['targetid']) : 0;
		$do = isset($_POST['do']) ? (string)$_POST['do'] : 'add';
		if(!isset(self::targettypes()[$targettype]) || !in_array($do, ['add', 'remove'], true)) {
			self::json(0, 'param', '参数不正确');
		}
		// 批量移除：管理模式下按当前页签一次提交多个目标。
		if($do === 'remove' && isset($_POST['targetids'])) {
			if($uid <= 0) {
				self::json(0, 'needlogin', '登录后可同步收藏', ['favorited' => 0]);
			}
			$ids = [];
			foreach(explode(',', (string)$_POST['targetids']) as $value) {
				$value = intval($value);
				if($value > 0) {
					$ids[$value] = $value;
				}
			}
			$ids = array_slice(array_values($ids), 0, 100);
			if(!$ids) {
				self::json(0, 'param', '请选择要移除的收藏');
			}
			foreach($ids as $value) {
				DB::delete('flipbook_favorite', ['uid' => $uid, 'targettype' => $targettype, 'targetid' => $value]);
			}
			$count = DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d', ['flipbook_favorite', $uid]);
			self::json(1, '', '已移除收藏', ['removed' => count($ids), 'count' => intval($count)]);
		}
		if($targetid <= 0) {
			self::json(0, 'param', '参数不正确');
		}
		if(!self::exists($targettype, $targetid)) {
			self::json(0, 'gone', '内容已下架');
		}
		if($uid <= 0) {
			self::json(0, 'needlogin', '登录后可同步收藏', ['favorited' => 0]);
		}
		$table = 'flipbook_favorite';
		if($do === 'add') {
			$exists = DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND targettype=%s AND targetid=%d', [$table, $uid, $targettype, $targetid]);
			if(!$exists) {
				DB::insert($table, ['uid' => $uid, 'targettype' => $targettype, 'targetid' => $targetid, 'dateline' => TIMESTAMP]);
			}
			$favorited = 1;
		} else {
			DB::delete($table, ['uid' => $uid, 'targettype' => $targettype, 'targetid' => $targetid]);
			$favorited = 0;
		}
		$count = DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d', [$table, $uid]);
		self::json(1, '', $favorited ? '已收藏' : '已取消收藏', ['favorited' => $favorited, 'count' => intval($count)]);
	}

	/**
	 * 记录阅读进度。
	 */
	protected static function progress() {
		$uid = front_helper::uid();
		$albumid = isset($_POST['albumid']) ? intval($_POST['albumid']) : 0;
		$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
		if(!$albumid || !self::exists('album', $albumid)) {
			self::json(0, 'gone', '内容已下架');
		}
		if($uid <= 0) {
			self::json(0, 'needlogin', '登录后可同步阅读进度');
		}
		$total = intval(DB::result_first('SELECT pagecount FROM %t WHERE albumid=%d', ['flipbook_album', $albumid]));
		$page = min($page, max(1, $total));
		$progress = json_encode(['page' => $page, 'total' => max(1, $total)], JSON_UNESCAPED_UNICODE);
		$historyid = intval(DB::result_first('SELECT historyid FROM %t WHERE uid=%d AND targettype=%s AND targetid=%d AND action=%s ORDER BY dateline DESC LIMIT 1', ['flipbook_history', $uid, 'album', $albumid, 'read']));
		if($historyid) {
			DB::update('flipbook_history', ['progress' => $progress, 'dateline' => TIMESTAMP], ['historyid' => $historyid]);
		} else {
			DB::insert('flipbook_history', ['uid' => $uid, 'targettype' => 'album', 'targetid' => $albumid, 'action' => 'read', 'progress' => $progress, 'dateline' => TIMESTAMP]);
		}
		self::json(1, '', '进度已保存', ['page' => $page, 'total' => max(1, $total)]);
	}

	/**
	 * 记录分享动作，用于“分享记录”列表。
	 */
	protected static function share() {
		$uid = front_helper::uid();
		$targettype = isset($_POST['targettype']) ? (string)$_POST['targettype'] : '';
		$targetid = isset($_POST['targetid']) ? intval($_POST['targetid']) : 0;
		if(!isset(self::targettypes()[$targettype]) || $targetid <= 0 || !self::exists($targettype, $targetid)) {
			self::json(0, 'param', '参数不正确');
		}
		if($uid > 0) {
			DB::insert('flipbook_history', ['uid' => $uid, 'targettype' => $targettype, 'targetid' => $targetid, 'action' => 'share', 'progress' => '', 'dateline' => TIMESTAMP]);
		}
		self::json(1, '', '分享已记录');
	}

	/**
	 * 批量删除阅读 / 分享记录，只按当前 UID 删除。
	 */
	protected static function history() {
		$uid = front_helper::uid();
		$do = isset($_POST['do']) ? (string)$_POST['do'] : 'remove';
		if($do !== 'remove') {
			self::json(0, 'param', '参数不正确');
		}
		if($uid <= 0) {
			self::json(0, 'needlogin', '登录后可管理记录');
		}
		$ids = [];
		foreach(explode(',', (string)(isset($_POST['historyids']) ? $_POST['historyids'] : '')) as $value) {
			$value = intval($value);
			if($value > 0) {
				$ids[$value] = $value;
			}
		}
		$ids = array_slice(array_values($ids), 0, 100);
		if(!$ids) {
			self::json(0, 'param', '请选择要删除的记录');
		}
		foreach($ids as $value) {
			DB::delete('flipbook_history', ['historyid' => $value, 'uid' => $uid]);
		}
		self::json(1, '', '已删除记录', ['removed' => count($ids)]);
	}

	/**
	 * 目标是否存在且可访问。
	 */
	protected static function exists($targettype, $targetid) {
		if($targettype === 'album') {
			return (bool)DB::result_first('SELECT COUNT(*) FROM %t WHERE albumid=%d AND available=1', ['flipbook_album', $targetid]);
		}
		if($targettype === 'product') {
			return (bool)DB::result_first('SELECT COUNT(*) FROM %t WHERE productid=%d AND available=1', ['flipbook_product', $targetid]);
		}
		return false;
	}

	/**
	 * 统一 JSON 输出：ok=1 为成功，ok=0 时 code 供前端映射受控文案。
	 */
	protected static function json($ok, $code, $message, $extra = []) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge([
			'ok' => intval($ok),
			'code' => (string)$code,
			'message' => (string)$message,
		], $extra), JSON_UNESCAPED_UNICODE);
		exit;
	}
}
