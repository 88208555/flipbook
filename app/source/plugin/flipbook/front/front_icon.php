<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 图标渲染入口
 *
 * 唯一图形来源：Lucide 官方图标数据（static/vendor/lucide/，含来源、版本与授权记录）。
 * 页面只允许使用本类的语义键，不允许录入 SVG 路径、图标地址或手绘图形；
 * 颜色、尺寸、线宽由样式表令牌控制，模板不写内联样式。
 *
 * 渲染方式：页面内嵌一次 SVG 精灵（sprite），业务位置用 <use> 引用，
 * 同一图标多次出现不重复输出路径，变形与状态通过 CSS 类实现。
 */

namespace flipbook;

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class front_icon {

	/**
	 * 语义键 => 说明（后台图标选择器与模板共用同一份键表）。
	 */
	public static function keys() {
		return [
			'grid' => '发现',
			'book' => '分类',
			'bookmark' => '收藏',
			'visitor' => '我的',
			'search' => '搜索',
			'shirt' => '款式',
			'share' => '分享',
			'back' => '返回',
			'next' => '继续 / 下一页',
			'up' => '上一页',
			'close' => '关闭',
			'catalog' => '目录',
			'history' => '阅读历史',
			'heart' => '我的收藏',
			'preference' => '阅读偏好',
			'info' => '关于',
			'chevron_right' => '右向箭头',
			'chevron_down' => '下拉',
			'zoom_in' => '放大',
			'save' => '保存',
			'check' => '已选',
			'empty' => '空内容',
			'offline' => '弱网',
			'locked' => '需要登录',
			'manage' => '管理',
			'flip' => '换册',
			'retry' => '重试',
			'sort' => '排序',
			'eye' => '查看',
            'logout'=>'退出登录','plus'=>'展开操作','volume_on'=>'开启声音','volume_off'=>'静音',
		];
	}

	protected static $registry = null;

	/**
	 * 读取已登记的图标数据；文件缺失或键不存在时返回空，不抛出原始错误给用户。
	 */
	public static function registry() {
		if(self::$registry !== null) {
			return self::$registry;
		}
		self::$registry = [];
		$file = dirname(__DIR__).'/static/vendor/lucide/icons.json';
		if(is_readable($file)) {
			$decoded = json_decode((string)file_get_contents($file), true);
			if(is_array($decoded)) {
				self::$registry = $decoded;
			}
		}
		return self::$registry;
	}

 public static function component_registry(){static $icons=null;if($icons===null){$data=json_decode((string)file_get_contents(dirname(__DIR__).'/static/config/composer-icons.json'),true);$icons=$data['icons']??[];}return $icons;}
 public static function component($key){if(!isset(self::component_registry()[$key]))return '';return '<svg class="fb-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#fb-c-'.front_helper::e($key).'"/></svg>';}
 public static function component_sprite(){$html='<div class="fb-sprite" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg">';foreach(self::component_registry() as $key=>$icon)$html.='<symbol id="fb-c-'.front_helper::e($key).'" viewBox="'.front_helper::e($icon['viewBox']).'">'.$icon['body'].'</symbol>';return $html.'</svg></div>';}

	/**
	 * 渲染单个图标。
	 *
	 * @param string $key   语义键
	 * @param string $class 追加的类名，仅允许字母、数字、横线与下划线
	 * @return string
	 */
	public static function svg($key, $class = '') {
		$registry = self::registry();
		if(!isset($registry[$key])) {
			return '';
		}
		$class = preg_replace('/[^a-zA-Z0-9_\- ]/', '', (string)$class);
		return '<svg class="fb-icon'.($class !== '' ? ' '.$class : '').'" viewBox="'.front_helper::e($registry[$key]['viewBox']).'" aria-hidden="true"><use href="#fb-i-'.front_helper::e($key).'"/></svg>';
	}

	/**
	 * 页面级 SVG 精灵：一次输出所有已登记图标，业务位置只引用 ID。
	 */
	public static function sprite() {
		$html = '<div class="fb-sprite" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg">';
		foreach(self::registry() as $key => $icon) {
			if(!isset(self::keys()[$key])) {
				continue;
			}
			$html .= '<symbol id="fb-i-'.front_helper::e($key).'" viewBox="'.front_helper::e($icon['viewBox']).'">'.$icon['body'].'</symbol>';
		}
		return $html.'</svg></div>';
	}

	/**
	 * 图标选择器数据：语义键、官方名称与来源版本，供后台登记与三端核对。
	 */
	public static function manifest() {
		$registry = self::registry();
		$manifest = [];
		foreach(self::keys() as $key => $label) {
			if(!isset($registry[$key])) {
				continue;
			}
			$manifest[$key] = [
				'label' => $label,
				'source' => 'lucide',
				'name' => $registry[$key]['source'],
				'version' => '1.61.0',
			];
		}
		return $manifest;
	}
}
