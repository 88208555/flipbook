<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 收藏（款式与画册共用）
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/**
 * 说明：本表主键为 (uid, targettype, targetid) 组合，读写均在类内显式实现。
 */
class table_flipbook_favorite extends table_flipbook_base {

	/**
	 * 收藏目标类型白名单。
	 */
	public static $targettypes = ['album' => '画册', 'product' => '款式'];

	protected $_pk = 'uid';

	public function __construct() {

		$this->_table = 'flipbook_favorite';

		parent::__construct();
	}

	public function count_by_uid($uid) {
		return (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d', [$this->_table, $uid]);
	}

	public function count_by_uid_type($uid, $targettype) {
		if(!isset(self::$targettypes[$targettype])) {
			return 0;
		}
		return (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND targettype=%s', [$this->_table, $uid, $targettype]);
	}

	public function fetch_all_by_uid_type($uid, $targettype) {
		if(!isset(self::$targettypes[$targettype])) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE uid=%d AND targettype=%s ORDER BY dateline DESC', [$this->_table, $uid, $targettype]);
	}

	public function is_favorite($uid, $targettype, $targetid) {
		return (bool)DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND targettype=%s AND targetid=%d', [$this->_table, $uid, $targettype, $targetid]);
	}

}