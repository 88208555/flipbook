<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 款式图组（卖点图、细节图、模特图）
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_product_media extends table_flipbook_base {

	/**
	 * 允许的图组类型，写入前必须命中该白名单。
	 */
	public static $types = ['highlight' => '卖点', 'detail' => '细节', 'model' => '模特', 'sku' => 'SKU 图'];

	protected $orderfields = ['mediaid', 'productid', 'type', 'displayorder'];

	public function __construct() {

		$this->_table = 'flipbook_product_media';
		$this->_pk = 'mediaid';

		parent::__construct();
	}

	public function fetch_all_by_product($productid) {
		return DB::fetch_all('SELECT * FROM %t WHERE productid=%d ORDER BY type, displayorder, mediaid', [$this->_table, $productid]);
	}

	public function fetch_all_by_product_type($productid, $type) {
		if(!isset(self::$types[$type])) {
			return [];
		}
		return DB::fetch_all('SELECT * FROM %t WHERE productid=%d AND type=%s ORDER BY displayorder, mediaid', [$this->_table, $productid, $type]);
	}

	public function delete_by_product($productid) {
		return DB::query('DELETE FROM %t WHERE productid=%d', [$this->_table, $productid]);
	}

}