<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 插件业务配置（键值表）
 *
 * 原生卸载会先删除 common_pluginvar，因此需要被保留的业务配置存放在本表，
 * 不依赖插件自定义变量承载关键配置。
 */

namespace flipbook;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class table_flipbook_setting extends discuz_table {

	public static function t() {
		static $_instance;
		if(!isset($_instance)) {
			$_instance = new self();
		}
		return $_instance;
	}

	public function __construct() {

		$this->_table = 'flipbook_setting';
		$this->_pk = 'skey';

		parent::__construct();
	}

	public function fetch_all_setting() {
		$data = [];
		foreach(DB::fetch_all('SELECT skey, svalue FROM %t', [$this->_table]) as $item) {
			$data[$item['skey']] = $item['svalue'];
		}
		return $data;
	}

	public function fetch_setting($skey, $default = '') {
		$svalue = DB::result_first('SELECT svalue FROM %t WHERE skey=%s', [$this->_table, $skey]);
		return $svalue === null ? $default : $svalue;
	}

	public function update_setting($skey, $svalue, $version = '') {
		return DB::query('INSERT INTO %t (skey, svalue, version) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue), version=VALUES(version)', [$this->_table, $skey, $svalue, $version]);
	}

}