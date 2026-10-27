<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 卸载脚本
 *
 * 默认行为是保留业务数据：画册、款式、收藏、阅读记录、海报模板与站点配置全部保留，
 * 只撤销插件自身的运行期注册项。删除内容或云端对象必须是管理员单独发起的动作。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

// 注册式钩子（hook::register 写入 common_setting.newhook）不会被框架自动撤销。
// 本版本尚未注册任何注册式钩子；新增 hook::register 调用时，必须在此处同步撤销本插件登记项。
$identifier = 'flipbook';
DB::delete('common_cron',['filename'=>'flipbook:assets.php']);
$newhook = C::t('common_setting')->fetch('newhook', true);
if(is_array($newhook)) {
	foreach($newhook as $hookkey => $items) {
		if(!is_array($items)) {
			continue;
		}
		foreach($items as $itemkey => $item) {
			if(isset($item['plugin']) && $item['plugin'] == $identifier) {
				unset($newhook[$hookkey][$itemkey]);
			}
		}
		if(empty($newhook[$hookkey])) {
			unset($newhook[$hookkey]);
		}
	}
	C::t('common_setting')->update('newhook', $newhook);
}

// 清空本插件运行期缓存与临时产物，不影响其他安装的缓存命名空间
foreach(['flipbook', 'flipbook_setting'] as $cachename) {
	@unlink(DISCUZ_DATA.'./cache/cache_'.$cachename.'.php');
}

updatecache(['plugin', 'setting']);

$finish = TRUE;
