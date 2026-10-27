<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 画册
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_album extends table_flipbook_base {

	protected $orderfields = ['albumid', 'seriesid', 'catid', 'name', 'displayorder', 'available', 'dateline'];

	public function __construct() {

		$this->_table = 'flipbook_album';
		$this->_pk = 'albumid';

		parent::__construct();
	}

	public function fetch_all_available() {
		return DB::fetch_all('SELECT * FROM %t WHERE available=%d ORDER BY displayorder, albumid', [$this->_table, 1]);
	}

	public function fetch_all_by_series($seriesid) {
		return DB::fetch_all('SELECT * FROM %t WHERE seriesid=%d AND available=%d ORDER BY displayorder, albumid', [$this->_table, $seriesid, 1]);
	}

	public function recount_pages($albumid) {
		$count = (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE albumid=%d AND available=%d', ['flipbook_page', $albumid, 1]);
		DB::query('UPDATE %t SET pagecount=%d WHERE albumid=%d', [$this->_table, $count, $albumid]);
		return $count;
	}

}
