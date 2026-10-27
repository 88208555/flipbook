<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 内容页与款式的关联
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/**
 * 说明：本表主键为 (pageid, productid) 组合，不适用 discuz_table 的单主键读写，
 * 因此只使用框架的连接与占位符能力，读写均在类内显式实现。
 */
class table_flipbook_page_product extends table_flipbook_base {

	protected $_pk = 'pageid';

	public function __construct() {

		$this->_table = 'flipbook_page_product';

		parent::__construct();
	}

	public function fetch_all_by_page($pageid) {
		return DB::fetch_all('SELECT * FROM %t WHERE pageid=%d ORDER BY displayorder, productid', [$this->_table, $pageid]);
	}

	public function fetch_productids_by_page($pageid) {
		$productids = [];
		foreach(DB::fetch_all('SELECT productid FROM %t WHERE pageid=%d ORDER BY displayorder, productid', [$this->_table, $pageid]) as $row) {
			$productids[] = intval($row['productid']);
		}
		return $productids;
	}

	public function delete_by_page($pageid) {
		return DB::query('DELETE FROM %t WHERE pageid=%d', [$this->_table, $pageid]);
	}

	public function delete_by_product($productid) {
		return DB::query('DELETE FROM %t WHERE productid=%d', [$this->_table, $productid]);
	}

	/**
	 * 重写整张内容页的款式关联，先删后插，保证顺序与请求一致。
	 *
	 * @param int   $pageid
	 * @param array $productids 已校验存在的款式 ID
	 */
	public function replace_by_page($pageid, $productids) {
		$this->delete_by_page($pageid);
		$order = 0;
		foreach((array)$productids as $productid) {
			DB::query('INSERT INTO %t (pageid, productid, displayorder) VALUES (%d, %d, %d)', [$this->_table, $pageid, $productid, $order]);
			$order++;
		}
	}

	/**
	 * 统计某个款式被多少内容页引用，删除款式前用于提示。
	 */
	public function count_by_product($productid) {
		return (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE productid=%d', [$this->_table, $productid]);
	}

}