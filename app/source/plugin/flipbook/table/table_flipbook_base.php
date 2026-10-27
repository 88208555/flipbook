<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 业务表基类
 *
 * 各业务表类只声明表名、主键与允许排序的字段，公共查询集中在本类，
 * 避免每张表重复实现分页、计数和排序拼装。
 */

namespace flipbook;

use discuz_table;
use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

abstract class table_flipbook_base extends discuz_table {

	/**
	 * 允许出现在 ORDER BY 的字段白名单。
	 * 排序字段来自请求参数时必须经此白名单过滤，禁止直接拼接。
	 *
	 * @var array
	 */
	protected $orderfields = [];

	public static function t() {
		static $instances = [];
		$class = static::class;
		if(!isset($instances[$class])) {
			$instances[$class] = new static();
		}
		return $instances[$class];
	}

	public function getPk() {
		return $this->_pk;
	}

	/**
	 * 表字段名清单，用于写入前过滤 Schema 之外的键。
	 * 结果按表缓存，同一次请求内不重复查询表结构。
	 *
	 * @return array
	 */
	public function getFields() {
		static $cache = [];
		if(!isset($cache[$this->_table])) {
			$fields = [];
			foreach(DB::fetch_all('SHOW COLUMNS FROM %t', [$this->_table]) as $column) {
				$fields[] = $column['Field'];
			}
			$cache[$this->_table] = $fields;
		}
		return $cache[$this->_table];
	}

	public function count_all() {
		return (int)DB::result_first('SELECT COUNT(*) FROM %t', [$this->_table]);
	}

	public function count_by_field($field, $value) {
		if(!$this->isOrderable($field)) {
			return 0;
		}
		return (int)DB::result_first('SELECT COUNT(*) FROM %t WHERE `'.$field.'`=%s', [$this->_table, $value]);
	}

	/**
	 * 按白名单字段排序读取。
	 *
	 * @param array $orderby ['displayorder' => 'ASC'] 或 ['displayorder']
	 * @param int   $limit
	 * @param int   $start
	 * @return array
	 */
	public function fetch_all_order($orderby = [], $limit = 0, $start = 0) {
		$sql = 'SELECT * FROM %t'.$this->build_order($orderby);
		if($limit) {
			$sql .= ' LIMIT '.intval($start).','.intval($limit);
		}
		return DB::fetch_all($sql, [$this->_table]);
	}

	public function fetch_all_by_field_order($field, $value, $orderby = [], $limit = 0, $start = 0) {
		if(!$this->isOrderable($field)) {
			return [];
		}
		$sql = 'SELECT * FROM %t WHERE `'.$field.'`=%s'.$this->build_order($orderby);
		$params = [$this->_table, $value];
		if($limit) {
			$sql .= ' LIMIT '.intval($start).','.intval($limit);
		}
		return DB::fetch_all($sql, $params);
	}

	protected function isOrderable($field) {
		return in_array($field, $this->orderfields, true);
	}

	protected function build_order($orderby) {
		$parts = [];
		foreach((array)$orderby as $key => $value) {
			if(is_int($key)) {
				$field = $value;
				$dir = 'ASC';
			} else {
				$field = $key;
				$dir = $value;
			}
			if(!$this->isOrderable($field)) {
				continue;
			}
			$parts[] = '`'.$field.'` '.(strtoupper($dir) == 'DESC' ? 'DESC' : 'ASC');
		}
		return $parts ? ' ORDER BY '.implode(', ', $parts) : '';
	}

}
