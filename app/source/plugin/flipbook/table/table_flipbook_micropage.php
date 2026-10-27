<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 微页面
 *
 * 每个页面（首页、详情页、收藏页、分类页、搜索页、我的页）对应一条记录，
 * 配置以 JSON 保存，version 用于小程序与 H5 的自动热加载比对。
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_micropage extends table_flipbook_base {

	protected $_table = 'flipbook_micropage';
	protected $_pk = 'pageid';
	protected $orderfields = ['pageid', 'pkey', 'version', 'dateline', 'status'];

	public function fetch_by_key($pkey) {
		$pkey = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$pkey));
		if($pkey === '') {
			return [];
		}
		$row = DB::fetch_first('SELECT * FROM %t WHERE pkey=%s', [$this->_table, $pkey]);
		return $row ? $row : [];
	}

	public function fetch_all_pages() {
		return $this->fetch_all_order(['pageid' => 'ASC']);
	}

	public function fetch_custom_names() {
		return DB::fetch_all('SELECT pkey,name FROM %t WHERE status=1 AND pkey LIKE %s ORDER BY pageid', [$this->_table, 'micro_%']);
	}

	public function rename_custom_page($key,$name,$version) {
		if(!front_micro::is_custom_key($key)) return false;
		DB::query('UPDATE %t SET name=%s,version=version+1,dateline=%d WHERE pkey=%s AND version=%d AND status=1',[$this->_table,$name,TIMESTAMP,$key,$version]);
		$changed=DB::affected_rows()>0;
		if($changed) front_micro::clear_custom_keys();
		return $changed;
	}

	public function delete_custom_page($key,$version) {
		if(!front_micro::is_custom_key($key)) return false;
		DB::query('DELETE FROM %t WHERE pkey=%s AND version=%d AND status=1',[$this->_table,$key,$version]);
		$changed=DB::affected_rows()>0;
		if($changed) front_micro::clear_custom_keys();
		return $changed;
	}

	/**
	 * 保存配置并递增版本号：版本变化即触发两端热加载。
	 */
	public function save_page($pkey, $name, $config, $expected = -1) {
		$pkey = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$pkey));
		// Atomic compare-and-swap prevents two open editors from overwriting each other.
		if($expected === 0) {
			DB::query('INSERT IGNORE INTO %t (pkey,name,config,status,version,dateline) VALUES (%s,%s,%s,1,1,%d)', [$this->_table, $pkey, $name, $config, TIMESTAMP]);
			$version = DB::affected_rows() ? 1 : 0;
			if($version && front_micro::is_custom_key($pkey)) front_micro::clear_custom_keys();
			return $version;
		}
		if($expected < 0) {
			$row = $this->fetch_by_key($pkey);
			return $this->save_page($pkey, $name, $config, $row ? intval($row['version']) : 0);
		}
		DB::query('UPDATE %t SET name=%s,config=%s,status=1,version=version+1,dateline=%d WHERE pkey=%s AND version=%d', [$this->_table, $name, $config, TIMESTAMP, $pkey, $expected]);
		$version = DB::affected_rows() ? $expected + 1 : 0;
		if($version && front_micro::is_custom_key($pkey)) front_micro::clear_custom_keys();
		return $version;
	}
}
