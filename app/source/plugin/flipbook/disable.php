<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 停用脚本
 *
 * 停用只关闭入口与新任务、撤销插件自身注册项，保留会员、画册、收藏、素材等数据。
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

// 与 enable.php 对称：此处撤销 hook::unregister 需要撤销的注册项。
// 当前版本未注册注册式钩子，因此只刷新缓存并失效业务缓存。
foreach(['flipbook', 'flipbook_setting'] as $cachename) {
	@unlink(DISCUZ_DATA.'./cache/cache_'.$cachename.'.php');
}

DB::query('UPDATE %t SET available=0 WHERE filename=%s',['common_cron','flipbook:assets.php']);
updatecache(['plugin', 'setting']);

$finish = TRUE;
