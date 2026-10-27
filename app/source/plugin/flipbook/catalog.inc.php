<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 分类搜索页
 *
 * 访问：plugin.php?id=flipbook:catalog
 * 参数：seriesid、catid、keyword
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$seriesid = isset($_GET['seriesid']) ? max(0, intval($_GET['seriesid'])) : 0;
$catid = isset($_GET['catid']) ? max(0, intval($_GET['catid'])) : 0;
$keyword = isset($_GET['keyword']) ? trim(preg_replace('/[\x00-\x1F\x7F]/', '', (string)$_GET['keyword'])) : '';
$keyword = mb_substr($keyword, 0, 40, 'UTF-8');

$fb = front_view::base('catalog', '分类搜索');
$fb['keyword'] = front_helper::e($keyword);
$fb['seriesid'] = $seriesid;
$fb['catid'] = $catid;
$fb['series'] = front_view::series_list();
$fb['categories'] = front_view::category_list();
$fb['albums'] = front_view::albums($seriesid, $catid, $keyword);
$fb['seriescount'] = count($fb['series']);
$fb['products'] = front_view::search_products($keyword);
if(($catid || $seriesid) && $fb['products']) {
	$allowed = array_fill_keys(array_column(flipbook\table_flipbook_product::t()->fetch_all_available($catid, $seriesid), 'productid'), true);
	$fb['products'] = array_values(array_filter($fb['products'], function($product) use ($allowed) { return isset($allowed[$product['productid']]); }));
}
$fb['albumcount'] = count($fb['albums']);
$fb['filters'] = [
	'all' => front_helper::url('catalog', ['seriesid' => $seriesid, 'keyword' => $keyword]),
	'reset' => front_helper::url('catalog'),
];
foreach($fb['categories'] as $index => $category) {
	$fb['categories'][$index]['url'] = front_helper::url('catalog', [
		'catid' => $category['catid'],
		'seriesid' => $seriesid,
		'keyword' => $keyword,
	]);
}
$favorites = front_view::favorite_ids(front_helper::uid());
foreach($fb['albums'] as $index => $album) {
	$fb['albums'][$index]['favorited'] = isset($favorites['album'][$album['albumid']]) ? 1 : 0;
}

include template(!empty($fb_micro_embed) ? 'flipbook:micro_catalog' : 'flipbook:catalog');
