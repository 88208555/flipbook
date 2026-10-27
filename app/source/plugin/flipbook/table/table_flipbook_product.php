<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 款式（产品）
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_product extends table_flipbook_base {

	protected $orderfields = ['productid', 'name', 'code', 'displayorder', 'available', 'dateline'];

	public function __construct() {

		$this->_table = 'flipbook_product';
		$this->_pk = 'productid';

		parent::__construct();
	}

	public function fetch_all_available($catid = 0, $seriesid = 0, $keyword = '', $limit = 0, $start = 0) {
		$catid = max(0, intval($catid));$seriesid = max(0, intval($seriesid));
		if($catid || $seriesid) {
			$sql = 'SELECT DISTINCT product.* FROM %t product INNER JOIN %t link ON link.productid=product.productid INNER JOIN %t page ON page.pageid=link.pageid INNER JOIN %t album ON album.albumid=page.albumid WHERE product.available=1 AND page.available=1 AND album.available=1';
			$args = [$this->_table, 'flipbook_page_product', 'flipbook_page', 'flipbook_album'];
			if($catid) { $sql .= ' AND album.catid=%d'; $args[] = $catid; }
			if($seriesid) { $sql .= ' AND album.seriesid=%d'; $args[] = $seriesid; }
		}else{$sql='SELECT product.* FROM %t product WHERE product.available=1';$args=[$this->_table];}
		if($keyword!==''){$sql.=' AND CONCAT_WS(\' \',product.name,product.code,product.summary) LIKE %s';$args[]='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$keyword).'%';}
		$sql.=' ORDER BY product.displayorder,product.productid';
		if($limit>0)$sql.=' LIMIT '.max(0,intval($start)).','.max(1,min(100,intval($limit)));
		return DB::fetch_all($sql,$args);
	}

	public function fetch_by_code($code) {
		return DB::fetch_first('SELECT * FROM %t WHERE code=%s LIMIT 1', [$this->_table, $code]);
	}

}
