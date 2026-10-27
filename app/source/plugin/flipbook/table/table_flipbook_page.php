<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 画册内容页
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_page extends table_flipbook_base {

	protected $orderfields = ['pageid', 'albumid', 'title', 'displayorder', 'available', 'dateline'];

	public function __construct() {

		$this->_table = 'flipbook_page';
		$this->_pk = 'pageid';

		parent::__construct();
	}

	public function fetch_all_by_album($albumid) {
		return DB::fetch_all('SELECT * FROM %t WHERE albumid=%d ORDER BY displayorder, pageid', [$this->_table, $albumid]);
	}

	public function fetch_all_available_by_album($albumid) {
		return DB::fetch_all('SELECT * FROM %t WHERE albumid=%d AND available=%d ORDER BY displayorder, pageid', [$this->_table, $albumid, 1]);
	}

}
