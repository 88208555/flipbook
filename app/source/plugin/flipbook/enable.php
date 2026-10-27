<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 启用脚本
 *
 * 启用时登记插件自身需要的注册式钩子与入口，并使业务缓存重新生成。
 * 当前版本没有使用注册式钩子（hook::register）；一旦新增，必须写在这里，
 * 并在 disable.php 中对称撤销——框架不会随停用自动撤销注册项。
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

DB::query('UPDATE %t SET available=1,nextrun=%d WHERE filename=%s',['common_cron',TIMESTAMP+3600,'flipbook:assets.php']);
updatecache(['plugin', 'setting']);

$finish = TRUE;
