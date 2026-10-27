<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 只读内容接口
 *
 * 访问：plugin.php?id=flipbook:api[&albumid=1][&productid=1][&seriesid=1][&catid=1][&keyword=]
 * 供原生小程序与外部渲染端读取；格式见 /Users/zhang/web/flipbook/项目完整设计方案.md 内容协议一节。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

flipbook\front_api::respond();
