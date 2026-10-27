<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 阅读与分享记录
 *
 * 访问：plugin.php?id=flipbook:history&action=read|share
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$action = isset($_GET['action']) && $_GET['action'] === 'share' ? 'share' : 'read';
$uid = front_helper::uid();

$fb = front_view::base('mine', '我的记录');
$fb['logged'] = front_helper::is_login();
$fb['loginurl'] = $fb['member']['loginurl'];
$fb['backurl'] = front_helper::url('mine');
$fb['action'] = $action;
$fb['taburls'] = [
	'read' => front_helper::url('history', ['action' => 'read']),
	'share' => front_helper::url('history', ['action' => 'share']),
];
$fb['groups'] = front_view::history_list($uid, $action);
$fb['counts'] = front_view::history_counts($uid);
$fb['countpad'] = [
	'read' => front_helper::pad($fb['counts']['read']),
	'share' => front_helper::pad($fb['counts']['share']),
];

include template('flipbook:history');
