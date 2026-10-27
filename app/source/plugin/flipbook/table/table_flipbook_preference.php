<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 阅读偏好
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_preference extends table_flipbook_base {

	protected $_pk = 'uid';

	public function __construct() {

		$this->_table = 'flipbook_preference';

		parent::__construct();
	}

	public function fetch_prefs($uid) {
		$prefs = DB::result_first('SELECT prefs FROM %t WHERE uid=%d', [$this->_table, $uid]);
		$data = $prefs ? dunserialize($prefs) : [];
		return is_array($data) ? $data : [];
	}

	public function save_prefs($uid, $prefs) {
		$value = is_array($prefs) ? serialize($prefs) : '';
		return DB::query('INSERT INTO %t (uid, prefs, dateline) VALUES (%d, %s, %d) ON DUPLICATE KEY UPDATE prefs=VALUES(prefs), dateline=VALUES(dateline)', [$this->_table, $uid, $value, TIMESTAMP]);
	}

}