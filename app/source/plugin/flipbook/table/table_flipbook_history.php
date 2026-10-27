<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 阅读与分享记录
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_history extends table_flipbook_base {

	/**
	 * 记录动作白名单：阅读、分享。
	 */
	public static $actions = ['read' => '阅读', 'share' => '分享'];

	protected $orderfields = ['historyid', 'uid', 'targettype', 'targetid', 'action', 'dateline'];

	public function __construct() {

		$this->_table = 'flipbook_history';
		$this->_pk = 'historyid';

		parent::__construct();
	}

	public function fetch_all_by_uid_action($uid, $action, $limit = 0, $start = 0) {
		if(!isset(self::$actions[$action])) {
			return [];
		}
		$sql = 'SELECT * FROM %t WHERE uid=%d AND action=%s ORDER BY dateline DESC';
		if($limit) {
			$sql .= ' LIMIT '.intval($start).','.intval($limit);
		}
		return DB::fetch_all($sql, [$this->_table, $uid, $action]);
	}

	public function count_by_uid_action($uid, $action) {
		if(!isset(self::$actions[$action])) {
			return 0;
		}
		return (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND action=%s', [$this->_table, $uid, $action]);
	}

	/**
	 * 记录一次阅读：同一会员同一内容只保留最近一条，避免足迹被重复条目占满。
	 */
	public function touch_read($uid, $targettype, $targetid, $progress = '') {
		DB::query('DELETE FROM %t WHERE uid=%d AND action=%s AND targettype=%s AND targetid=%d', [$this->_table, $uid, 'read', $targettype, $targetid]);
		return DB::query('INSERT INTO %t (uid, targettype, targetid, action, progress, dateline) VALUES (%d, %s, %d, %s, %s, %d)', [$this->_table, $uid, $targettype, $targetid, 'read', $progress, TIMESTAMP]);
	}

}