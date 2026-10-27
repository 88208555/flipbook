<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台内容维护引擎
 *
 * 系列、分类、画册、内容页、款式共用同一套“字段 Schema → 列表 / 表单 / 校验 / 写库”流程，
 * 各实体只在模块文件里声明自己的字段，不复制粘贴三份列表与表单代码。
 *
 * 约束：
 * 1. 写操作一律 POST + formhash，通过 submitcheck 校验；
 * 2. 字段值按 Schema 声明的类型校验，未知类型与越界值不落库；
 * 3. 关联字段必须命中被引用表的真实记录，不接受任意外键；
 * 4. 输出统一经 admin_helper::e()，模板不自行拼装转义。
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class admin_crud {

	/**
	 * 字段 Schema 支持的类型。
	 */
	const TYPES = ['text', 'textarea', 'int', 'bool', 'amount', 'url', 'select', 'tags', 'json'];

	public static function form_id($pmod,$mode='edit') {
		return 'fb-crud-'.preg_replace('/[^a-z0-9_]/','',(string)$pmod).'-'.($mode==='list'?'list':'form');
	}

	/**
	 * 查询单条记录。
	 *
	 * @param string $table
	 * @param string $pk
	 * @param int    $id
	 * @return array
	 */
	public static function fetch($table, $pk, $id) {
		if($id <= 0) {
			return [];
		}
		$table = self::table($table);
		$row = DB::fetch_first('SELECT * FROM %t WHERE `'.$table->getPk().'`=%d', [$table->getTable(), $id]);
		return $row ? $row : [];
	}

	/**
	 * 读取列表分页数据。
	 *
	 * @param array $config
	 * @param int   $page
	 * @param int   $perpage
	 * @return array [rows, count]
	 */
	public static function pagelist($config, $page, $perpage) {
		$table = self::table($config['table']);
		$pk = $table->getPk();
		// 先按 Schema 声明的排序列生成稳定顺序，ID 参与排序避免同序号分页漂移
		$orderby = [];
		if(!empty($config['orderby'])) {
			foreach($config['orderby'] as $field => $dir) {
				$orderby[$field] = $dir;
			}
		}
		$orderby[$pk] = 'ASC';
		$count = (int)DB::result_first('SELECT COUNT(*) FROM %t', [$table->getTable()]);
		$rows = $table->fetch_all_order($orderby, $perpage, ($page - 1) * $perpage);
		return [$rows, $count];
	}

	/**
	 * 渲染列表页。
	 */
	public static function render_list($config, $page, $perpage = 20) {
		list($rows, $count) = self::pagelist($config, $page, $perpage);
		$pk = self::table($config['table'])->getPk();
		$pmod = $config['pmod'];

		showformheader('plugins&operation=config&do='.admin_helper::pluginid().'&identifier='.admin_helper::IDENTIFIER.'&pmod='.$pmod,'class="fb-admin-crud-form" data-fb-crud-list',self::form_id($pmod,'list'));
		echo '<input type="hidden" name="flipbooksubmit" value="1"><input type="hidden" name="flipbookaction" value="delete">';
		showtableheader($config['title'].'（共 '.$count.' 条）');

		$titles = ['<input class="checkbox" type="checkbox" data-crud-select-all aria-label="全选记录">'];
		$styles = ['class="td25"'];
		foreach($config['columns'] as $column) {
			$titles[] = $config['schema'][$column]['title'];
			$styles[] = '';
		}
		$titles[] = '操作';
		$styles[] = '';
		showsubtitle($titles, 'header', $styles);

		foreach($rows as $row) {
			$cells = [
				'<input class="checkbox" type="checkbox" name="delete[]" value="'.intval($row[$pk]).'" />',
			];
			foreach($config['columns'] as $column) {
				$cells[] = self::cell($config['schema'][$column], $row, $column);
			}
			$cells[] = '<a class="fb-admin-button" href="'.admin_helper::e(admin_helper::url($pmod, ['op' => 'edit', 'id' => intval($row[$pk])])).'">编辑</a>';
			showtablerow('', $styles, $cells);
		}

		showtablefooter();
		showformfooter();

		if(!$rows) {
			echo '<div class="infobox">暂无记录，请先新增。</div>';
		}
		echo multi($count, $perpage, $page, admin_helper::url($pmod));
	}

	/**
	 * 渲染新增 / 编辑表单。
	 */
	public static function render_form($config, $row) {
		$pk = self::table($config['table'])->getPk();
		$pmod = $config['pmod'];
		$action = 'plugins&operation=config&do='.admin_helper::pluginid().'&identifier='.admin_helper::IDENTIFIER.'&pmod='.$pmod.'&op=save';
		if(isset($config['action_url'])) $action=$config['action_url'];
		if(!empty($row[$pk])) {
			$action .= '&id='.intval($row[$pk]);
		}

		showformheader($action,'class="fb-admin-crud-form"',self::form_id($pmod));
		echo '<input type="hidden" name="flipbooksubmit" value="1">';
		showtableheader($row ? '编辑'.$config['title'] : '新增'.$config['title']);
		foreach($config['schema'] as $name => $field) {
			if(!empty($field['readonly'])) {
				continue;
			}
			if(isset($config['pending_options'][$name]))$field['pending_options']=$config['pending_options'][$name];
			self::render_field($name, $field, isset($row[$name]) ? $row[$name] : (isset($field['default']) ? $field['default'] : ''));
		}
		foreach((array)(isset($config['extra_fields']) ? $config['extra_fields'] : []) as $name => $field) {
			$value = isset($row['__extra'][$name]) ? $row['__extra'][$name] : '';
			self::render_field($name, $field, $value);
		}
		showtablefooter();
		showformfooter();
	}

	/**
	 * 单个字段渲染。
	 */
	protected static function render_field($name, $field, $value) {
		$title = $field['title'];
		$comment = isset($field['comment']) ? $field['comment'] : '';
		switch($field['type']) {
			case 'textarea':
				showsetting($title, $name, $value, 'textarea', '', 0, $comment);
				break;
			case 'bool':
				if($name==='available')showsetting($title,[$name,[[1,'上架 / 启用'],[0,'草稿 / 停用']]],intval($value)?1:0,'select','',0,$comment);
				else showsetting($title, $name, intval($value) ? 1 : 0, 'radio', '', 0, $comment);
				break;
			case 'int':
			case 'amount':
				showsetting($title, $name, $value, 'text', '', 0, $comment);
				break;
			case 'select':
				$options = [['0', '未指定']];
				foreach($field['pending_options']??[] as $id=>$label)$options[]=[(string)$id,$label];
				foreach(self::source($field['source']) as $id => $label) {
					$options[] = [(string)$id, $label];
				}
				showsetting($title, [$name, $options], (string)$value, 'select', '', 0, $comment);
				break;
			case 'tags':
				showsetting($title, $name, implode(',', (array)(is_array($value) ? $value : explode(',', (string)$value))), 'text', '', 0, $comment ? $comment : '多个标签用英文逗号分隔');
				break;
			case 'json':
				showsetting($title, $name, (string)$value, 'textarea', '', 0, $comment ? $comment : 'JSON 对象，可用键：'.implode('、', array_keys($field['keys'])));
				break;
			case 'url':
			case 'text':
			default:
				showsetting($title, $name, $value, 'text', '', 0, $comment);
				break;
		}
	}

	/**
	 * 列表单元格展示。
	 */
	protected static function cell($field, $row, $name) {
		$value = isset($row[$name]) ? $row[$name] : '';
		switch($field['type']) {
			case 'bool':
				return $name==='available'?(intval($value)?'上架 / 启用':'草稿 / 停用'):(intval($value)?'显示 / 启用':'隐藏 / 关闭');
			case 'money':
			case 'amount':
				return admin_helper::format_amount($value);
			case 'select':
				$source = self::source($field['source']);
				return isset($source[intval($value)]) ? admin_helper::e($source[intval($value)]) : '—';
			case 'textarea':
				$text = mb_substr(str_replace("\n", ' ', (string)$value), 0, 40, 'UTF-8');
				return admin_helper::e($text);
			case 'json':
				return $value ? '已配置' : '—';
			default:
				return admin_helper::e($value);
		}
	}

	/**
	 * 统一处理列表页提交：带 delete[] 时删除，否则保存表单。
	 *
	 * @param array $config
	 * @return bool 是否已处理（成功或失败均会结束请求）
	 */
	public static function handle_post($config) {
		if(!submitcheck('flipbooksubmit')) {
			return false;
		}
		$ids = admin_helper::ids('delete');
		if(admin_helper::text('flipbookaction')==='delete'&&!$ids){admin_helper::failed('请先选择要删除的记录');return true;}
		if($ids) {
			return self::do_delete($config, $ids);
		}
		return self::do_save($config);
	}

	protected static function do_save($config) {
		admin_helper::needperm('edit');

		$table = self::table($config['table']);
		$id = admin_helper::uintval('id');
		$data = self::collect($config);
		if($data === false) {
			return true;
		}
		DB::query('START TRANSACTION');
  try {
   if(!empty($config['before_save']))call_user_func($config['before_save'],$id);
		if($id > 0) {
			$table->update($id, $data);
		} else {
			$id = (int)$table->insert($data, true);
		}
		if($id > 0 && !empty($config['extra_save'])) {
			call_user_func($config['extra_save'], $id);
		}
  DB::query('COMMIT');
  } catch(\Throwable $error) {DB::query('ROLLBACK');admin_helper::failed($error instanceof \RuntimeException?$error->getMessage():'保存失败，未写入部分商品资料');}
  if(!empty($config['after_save']))call_user_func($config['after_save'],$id);
		admin_helper::succeeded($id > 0 ? '已保存' : '已新增', $config['pmod']);
		return true;
	}

	protected static function do_delete($config, $ids) {
		admin_helper::needperm('delete');

		$table = self::table($config['table']);
  DB::query('START TRANSACTION');
  try {
		foreach($ids as $id) {
			if(!empty($config['before_delete'])) {
				call_user_func($config['before_delete'], $id);
			}
			$table->delete($id);
		}
  DB::query('COMMIT');
  }catch(\Throwable $error){DB::query('ROLLBACK');admin_helper::failed($error instanceof \RuntimeException?$error->getMessage():'删除失败，未删除部分资料');}
		if(!empty($config['after_delete'])) {
			call_user_func($config['after_delete'], $ids);
		}
		admin_helper::succeeded('已删除 '.count($ids).' 条记录', $config['pmod']);
		return true;
	}

	/**
	 * 按 Schema 校验并收集字段值。
	 * 校验失败直接结束请求，不写入部分数据。
	 *
	 * @return array|false
	 */
	public static function collect($config) {
		$allowed = self::table($config['table'])->getFields();
		$data = [];
		foreach($config['schema'] as $name => $field) {
			if(!in_array($name, $allowed, true)) {
				continue;
			}
			if(!in_array($field['type'], self::TYPES, true)) {
				continue;
			}
			$value = self::value($name, $field);
			if($value === false) {
				return false;
			}
			$data[$name] = $value;
		}
		if(in_array('dateline', $allowed, true) && !isset($data['dateline'])) {
			$data['dateline'] = TIMESTAMP;
		}
		return $data;
	}

	protected static function value($name, $field) {
		$required = !empty($field['required']);
		switch($field['type']) {
			case 'textarea':
				$value = admin_helper::textarea($name, isset($field['maxlen']) ? $field['maxlen'] : 5000);
				break;
			case 'bool':
				$value = admin_helper::boolval($name);
				break;
			case 'int':
				$value = admin_helper::intval($name);
				break;
			case 'amount':
				$value = admin_helper::amount($name);
				break;
			case 'select':
				$value = admin_helper::uintval($name);
				$source = self::source($field['source']);
				if($value && !isset($source[$value])) {
					admin_helper::failed('所选项不存在：'.$field['title']);
					return false;
				}
				if(!$value && $required) {
					admin_helper::failed('请选择'.$field['title']);
					return false;
				}
				break;
			case 'tags':
				$tags = [];
				foreach(explode(',', admin_helper::text($name, isset($field['maxlen']) ? $field['maxlen'] : 255)) as $tag) {
					$tag = trim($tag);
					if($tag !== '') {
						$tags[$tag] = $tag;
					}
				}
				$value = implode(',', array_slice(array_values($tags), 0, 10));
				break;
			case 'url':
				$value = admin_helper::urlval($name, isset($field['maxlen']) ? $field['maxlen'] : 255);
				break;
			case 'json':
				$value = admin_helper::jsonval($name, $field['keys']);
				if($required && !$value) {
					admin_helper::failed($field['title'].' 需要填写合法的 JSON 对象');
					return false;
				}
				break;
			case 'text':
			default:
				$value = admin_helper::text($name, isset($field['maxlen']) ? $field['maxlen'] : 255);
				break;
		}
		if($required && $value === '') {
			admin_helper::failed('请填写'.$field['title']);
			return false;
		}
		return $value;
	}

	/**
	 * 关联数据源，供 select 字段使用，返回 id => 名称。
	 */
	public static function source($source) {
		switch($source) {
			case 'supplier':return self::pluck(table_flipbook_supplier::t()->fetch_all_order(['displayorder'=>'ASC','supplierid'=>'ASC']),'supplierid','name');
			case 'series':
				return self::pluck(table_flipbook_series::t()->fetch_all_order(['displayorder' => 'ASC', 'seriesid' => 'ASC']), 'seriesid', 'name');
			case 'category':
				return self::pluck(table_flipbook_category::t()->fetch_all_order(['displayorder' => 'ASC', 'catid' => 'ASC']), 'catid', 'name');
			case 'album':
				return self::pluck(table_flipbook_album::t()->fetch_all_order(['displayorder' => 'ASC', 'albumid' => 'ASC']), 'albumid', 'name');
			case 'product':
				return self::pluck(table_flipbook_product::t()->fetch_all_order(['displayorder' => 'ASC', 'productid' => 'ASC']), 'productid', 'name');
			case 'mediatype':
				return table_flipbook_product_media::$types;
			default:
				return [];
		}
	}

	protected static function pluck($rows, $idfield, $namefield) {
		$data = [];
		foreach($rows as $row) {
			$data[intval($row[$idfield])] = $row[$namefield];
		}
		return $data;
	}

	/**
	 * 取得表对象；未知表名直接拒绝，避免请求参数决定写入目标。
	 */
	protected static function table($name) {
		switch($name) {
			case 'flipbook_supplier':return table_flipbook_supplier::t();
			case 'flipbook_series':
				return table_flipbook_series::t();
			case 'flipbook_category':
				return table_flipbook_category::t();
			case 'flipbook_album':
				return table_flipbook_album::t();
			case 'flipbook_page':
				return table_flipbook_page::t();
			case 'flipbook_product':
				return table_flipbook_product::t();
			case 'flipbook_product_media':
				return table_flipbook_product_media::t();
			default:
				admin_helper::failed('未登记的数据表');
				exit;
		}
	}

}
