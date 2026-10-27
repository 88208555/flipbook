<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 分类
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_category extends table_flipbook_base {

	protected $orderfields = ['catid', 'name', 'parentid', 'displayorder', 'available'];

	public function __construct() {

		$this->_table = 'flipbook_category';
		$this->_pk = 'catid';

		parent::__construct();
	}

	public function fetch_all_available() {
		return DB::fetch_all('SELECT * FROM %t WHERE available=%d ORDER BY displayorder, catid', [$this->_table, 1]);
	}

}
