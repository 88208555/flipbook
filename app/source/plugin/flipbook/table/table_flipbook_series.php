<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 系列
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_series extends table_flipbook_base {

	protected $orderfields = ['seriesid', 'name', 'displayorder', 'available', 'dateline'];

	public function __construct() {

		$this->_table = 'flipbook_series';
		$this->_pk = 'seriesid';

		parent::__construct();
	}

	public function fetch_all_available() {
		return DB::fetch_all('SELECT * FROM %t WHERE available=%d ORDER BY displayorder, seriesid', [$this->_table, 1]);
	}

	public function fetch_by_name($name) {
		return DB::fetch_first('SELECT * FROM %t WHERE name=%s LIMIT 1', [$this->_table, $name]);
	}

}
