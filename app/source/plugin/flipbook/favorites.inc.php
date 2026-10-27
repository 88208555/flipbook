<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 收藏页
 *
 * 访问：plugin.php?id=flipbook:favorites&tab=product|album&order=recent|oldest|name
 * 收藏数量与列表来自同一份数据，款式与画册分别统计，未登录时显示登录入口。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$uid = front_helper::uid();
$tab = isset($_GET['tab']) && $_GET['tab'] === 'album' ? 'album' : 'product';
$order = isset($_GET['order']) ? (string)$_GET['order'] : 'recent';
$order = in_array($order, ['recent', 'oldest', 'name'], true) ? $order : 'recent';

$fb = front_view::base('favorites', '收藏');
$fb['tab'] = $tab;
$fb['order'] = $order;
$fb['logged'] = front_helper::is_login();
$fb['loginurl'] = $fb['member']['loginurl'];
$fb['favorites'] = front_view::favorites($uid, $order);
$fb['counts'] = $fb['favorites']['counts'];
$fb['countpad'] = [
	'product' => front_helper::pad($fb['counts']['product']),
	'album' => front_helper::pad($fb['counts']['album']),
];
$fb['taburls'] = [
	'product' => front_helper::url('favorites', ['tab' => 'product', 'order' => $order]),
	'album' => front_helper::url('favorites', ['tab' => 'album', 'order' => $order]),
];

include template(!empty($fb_micro_embed) ? 'flipbook:micro_favorites' : 'flipbook:favorites');
