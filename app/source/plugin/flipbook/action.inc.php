<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 前台写操作入口
 *
 * 访问：POST plugin.php?id=flipbook:action&op=favorite|progress|share
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

flipbook\front_action::handle();
