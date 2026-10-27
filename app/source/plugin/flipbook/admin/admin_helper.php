<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台公共能力
 *
 * 本类只服务插件后台模块，集中处理：
 * 1. 输入按 Schema 校验（类型、长度、枚举、白名单），不使用未过滤的请求值；
 * 2. 输出转义统一走 e()，模板与页面不各自拼装转义；
 * 3. 动作权限按插件管理动作粒度校验，复用原生后台管理团队与职务体系；
 * 4. 后台跳转与提示统一生成原生 ADMINSCRIPT 地址。
 */

namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class admin_helper {

	/** One control and action style sheet for every plugin administration page. */
	public static function assets() {
		static $loaded=false;
		if($loaded)return;
		$loaded=true;
		$root=dirname(__DIR__).'/static/admin/';
		echo '<link rel="stylesheet" href="source/plugin/flipbook/static/admin/admin.css?v='.@filemtime($root.'admin.css').'">';
		echo '<script src="source/plugin/flipbook/static/admin/admin.js?v='.@filemtime($root.'admin.js').'"></script>';
	}

	/**
	 * 插件标识，与目录名、清单 identifier 保持一致。
	 */
	const IDENTIFIER = 'flipbook';

	/**
	 * 后台模块（pmod）与标题，用于生成设置页签。
	 */
	public static function pmods() {
		return ['admincp'=>'基础设置','product'=>'商品管理','micropage'=>'微页面','storage'=>'文件存储','wechat'=>'微信小程序','release'=>'版本推送'];
	}

	public static function pluginid() {
		return max(0, intval($_GET['do']));
	}

	/**
	 * 拼装插件后台地址。参数名与值均做白名单/编码处理，不直接拼接原始请求值。
	 *
	 * @param string $pmod
	 * @param array  $params 附加查询参数，值为 null 时忽略
	 * @return string
	 */
	public static function url($pmod = '', $params = []) {
		$query = [
			'action' => 'plugins',
			'operation' => 'config',
			'do' => self::pluginid(),
			'identifier' => self::IDENTIFIER,
		];
		if($pmod==='release'){$pmod='wechat';$params=['wechat_tab'=>'release']+$params;}
		if($pmod) {
			$query['pmod'] = $pmod;
		}
		foreach($params as $key => $value) {
			if($value === null || $value === '') {
				continue;
			}
			$query[$key] = $value;
		}
		$parts = [];
		foreach($query as $key => $value) {
			$parts[] = rawurlencode($key).'='.rawurlencode((string)$value);
		}
		return ADMINSCRIPT.(strpos(ADMINSCRIPT, '?') === false ? '?' : '&').implode('&', $parts);
	}

	/**
	 * 当前管理员是否具备某个插件管理动作权限。
	 *
	 * 采用原生语义：创始人与未分配管理组的超级管理员视为拥有全部权限；
	 * 其他管理员按 common_admincp_perm 中登记的插件动作权限判断，
	 * 找不到时再按原生后台子权限映射（$_G['cache']['admin']['subperms']）回溯一次。
	 *
	 * @param string $op 动作标识，如 edit、delete、publish
	 * @return bool
	 */
	public static function perm($op = '') {
		global $_G;

		$pmod = preg_replace('/[^a-z0-9_]/', '', strtolower((string)(isset($_GET['pmod']) ? $_GET['pmod'] : '')));

		$keys = [];
		if($pmod && $op) {
			$keys[] = 'plugin_'.self::IDENTIFIER.':'.$pmod.':'.$op;
		}
		if($pmod) {
			$keys[] = 'plugin_'.self::IDENTIFIER.':'.$pmod;
		}
		$keys[] = 'plugin_'.self::IDENTIFIER;

		$uid = intval($_G['uid']);
		if(empty($uid)) {
			return false;
		}
		$member = \table_common_admincp_member::t()->fetch($uid);
		$cpgroupid = $member ? intval($member['cpgroupid']) : 0;
		if(!$cpgroupid) {
			return intval($_G['adminid']) === 1;
		}
		foreach($keys as $key) {
			if(DB::result_first('SELECT COUNT(*) FROM %t WHERE cpgroupid=%d AND perm=%s', ['common_admincp_perm', $cpgroupid, $key])) {
				return true;
			}
		}
		foreach($keys as $key) {
			$mapped = isset($_G['cache']['admin']['subperms'][$key]) ? $_G['cache']['admin']['subperms'][$key] : '';
			if($mapped && DB::result_first('SELECT COUNT(*) FROM %t WHERE cpgroupid=%d AND perm=%s', ['common_admincp_perm', $cpgroupid, $mapped])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 无权限时结束请求。页面不渲染、接口不返回业务数据，避免只隐藏按钮。
	 */
	public static function needperm($op = '') {
		if(!self::perm($op)) {
			cpmsg('action_noaccess', '', 'error');
		}
	}

	/**
	 * 输出转义：所有进入 HTML 上下文的业务文字统一走这里。
	 */
	public static function e($value) {
		return dhtmlspecialchars(is_scalar($value) || $value === null ? (string)$value : '');
	}

	// ------------------------------------------------------------------
	// 输入校验
	// ------------------------------------------------------------------

	public static function text($key, $maxlen = 255) {
		$value = isset($_GET[$key]) ? $_GET[$key] : '';
		if(is_array($value)) {
			return '';
		}
		$value = trim(strip_tags((string)$value));
		$value = preg_replace('/[\r\n\t]+/', ' ', $value);
		if($maxlen > 0 && mb_strlen($value, 'UTF-8') > $maxlen) {
			$value = mb_substr($value, 0, $maxlen, 'UTF-8');
		}
		return $value;
	}

	/**
	 * 多行文本：保留换行，剥离标签。
	 */
	public static function textarea($key, $maxlen = 5000) {
		$value = isset($_GET[$key]) ? $_GET[$key] : '';
		if(is_array($value)) {
			return '';
		}
		$value = trim(strip_tags(str_replace(["\r\n", "\r"], "\n", (string)$value)));
		if($maxlen > 0 && mb_strlen($value, 'UTF-8') > $maxlen) {
			$value = mb_substr($value, 0, $maxlen, 'UTF-8');
		}
		return $value;
	}

	public static function intval($key) {
		return intval(isset($_GET[$key]) ? $_GET[$key] : 0);
	}

	public static function uintval($key) {
		return max(0, intval(isset($_GET[$key]) ? $_GET[$key] : 0));
	}

	public static function boolval($key) {
		return !empty($_GET[$key]) ? 1 : 0;
	}

	/**
	 * 枚举校验：不在白名单内一律回落默认值，不把未知值写入数据库。
	 */
	public static function enumval($key, $allowed, $default = '') {
		$value = isset($_GET[$key]) ? (string)$_GET[$key] : '';
		return isset($allowed[$value]) ? $value : $default;
	}

	/**
	 * 金额：只接受最多两位小数的十进制字符串，返回规范化后的定长字符串。
	 * 不使用浮点存储，避免精度误差扩散到价格展示。
	 */
	public static function amount($key) {
		$value = isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
		if($value === '' || !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
			return '0.00';
		}
		return number_format((float)$value, 2, '.', '');
	}

	public static function format_amount($value) {
		return number_format((float)$value, 2, '.', '');
	}

	/**
	 * 受控 JSON 配置：只接受 JSON 对象，并按键白名单过滤后重新编码。
	 * 不接受 PHP、SQL 或任意脚本片段作为配置值。
	 *
	 * @param string $key
	 * @param array  $allowedkeys 允许出现的顶层键，值为该键的默认值
	 * @return string 规范化 JSON 字符串
	 */
	public static function jsonval($key, $allowedkeys) {
		$raw = isset($_GET[$key]) ? (string)$_GET[$key] : '';
		if(is_array($_GET[$key] ?? null)) {
			return '';
		}
		$decoded = json_decode($raw, true);
		if(!is_array($decoded)) {
			return '';
		}
		$clean = [];
		foreach($allowedkeys as $name => $default) {
			$value = array_key_exists($name, $decoded) ? $decoded[$name] : $default;
			if(is_array($default)) {
				$clean[$name] = self::cleanlist($value, 3);
			} elseif(is_int($default)) {
				$clean[$name] = intval($value);
			} elseif(is_bool($default)) {
				$clean[$name] = (bool)$value;
			} else {
				$clean[$name] = mb_substr(strip_tags((string)(is_scalar($value) ? $value : '')), 0, 255, 'UTF-8');
			}
		}
		return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * 递归清洗 JSON 数组值：保留嵌套层级（如尺码表的行），标量一律剥离标签并限长。
	 */
	protected static function cleanlist($value, $depth) {
		$out = [];
		foreach((array)$value as $item) {
			if(is_array($item) && $depth > 0) {
				$out[] = self::cleanlist($item, $depth - 1);
			} elseif(is_scalar($item) && !is_bool($item)) {
				$out[] = mb_substr(strip_tags((string)$item), 0, 120, 'UTF-8');
			}
		}
		return $out;
	}

	/**
	 * 逗号分隔或数组形式的 ID 列表，只保留正整数并去重。
	 */
	public static function ids($key) {
		$value = isset($_GET[$key]) ? $_GET[$key] : '';
		if(is_array($value)) {
			$items = $value;
		} else {
			$items = explode(',', (string)$value);
		}
		$ids = [];
		foreach($items as $item) {
			$id = intval($item);
			if($id > 0) {
				$ids[$id] = $id;
			}
		}
		return array_values($ids);
	}

	/**
	 * 资源引用：只接受站内相对路径、插件静态目录与 http(s) 地址。
	 * 拒绝协议白名单之外的写法，并拒绝包含 .. 的路径片段，避免目录回溯。
	 */
	public static function urlval($key, $maxlen = 255) {
		return self::cleanurl(self::text($key, $maxlen));
	}

	/**
	 * 校验一个已在内存中的资源引用值，供字段组合（如按行解析的图组）复用同一套规则。
	 */
	public static function cleanurl($value) {
		$value = trim(strip_tags((string)$value));
		if($value === '') {
			return '';
		}
		if(mb_strlen($value, 'UTF-8') > 255) {
			return '';
		}
		if(preg_match('/[\x00-\x1F\x7F]/', $value)) {
			return '';
		}
		if(preg_match('#(^|/)\.\.(/|$)#', $value)) {
			return '';
		}
		if(!preg_match('#^(https?://[^\s]+|/[^\s]*|data/[A-Za-z0-9_./-]+|source/plugin/[A-Za-z0-9_./-]+|static/[A-Za-z0-9_./-]+)$#i', $value)) {
			return '';
		}
		return $value;
	}

	// ------------------------------------------------------------------
	// 选项数据
	// ------------------------------------------------------------------

	/**
	 * 生成原生 select 的 option 片段。
	 */
	public static function options(array $rows, $idfield, $namefield, $selected = 0) {
		$html = '';
		foreach($rows as $row) {
			$id = intval($row[$idfield]);
			$html .= '<option value="'.$id.'"'.($id === intval($selected) ? ' selected="selected"' : '').'>'.self::e($row[$namefield]).'</option>';
		}
		return $html;
	}

	public static function series_options($selected = 0) {
		return self::options(table_flipbook_series::t()->fetch_all_order(['displayorder' => 'ASC', 'seriesid' => 'ASC']), 'seriesid', 'name', $selected);
	}

	public static function category_options($selected = 0) {
		return self::options(table_flipbook_category::t()->fetch_all_order(['displayorder' => 'ASC', 'catid' => 'ASC']), 'catid', 'name', $selected);
	}

	public static function album_options($selected = 0) {
		return self::options(table_flipbook_album::t()->fetch_all_order(['displayorder' => 'ASC', 'albumid' => 'ASC']), 'albumid', 'name', $selected);
	}

	public static function product_options($selected = 0) {
		return self::options(table_flipbook_product::t()->fetch_all_order(['displayorder' => 'ASC', 'productid' => 'ASC']), 'productid', 'name', $selected);
	}

	/**
	 * 后台列表通用的可用状态文案。
	 */
	public static function availtext($available) {
		return intval($available) ? '已上架' : '已下架';
	}

	// ------------------------------------------------------------------
	// 页面辅助
	// ------------------------------------------------------------------

	/**
	 * 生成“新增 / 列表”同级操作条，使用原生后台样式类，不写内联样式。
	 */
	public static function actionbar($pmod, $listurl, $addurl = '') {
		$html = '<div class="cuspages right">';
		$html .= '<a class="btn" href="'.self::e($listurl).'">返回列表</a>';
		if($addurl) {
			$html .= '&nbsp;<a class="btn" href="'.self::e($addurl).'">新增</a>';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * 保存成功后的跳转提示。
	 */
	public static function succeeded($message, $pmod, $params = []) {
		global $pluginid;
		cpmsg($message, 'action=plugins&operation=config&do='.intval($pluginid).'&identifier='.self::IDENTIFIER.'&pmod='.$pmod.($params ? '&'.http_build_query($params) : ''), 'succeed');
	}

	public static function failed($message) {
		cpmsg($message, '', 'error');
	}

}
