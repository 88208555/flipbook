<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 阅读页
 *
 * 访问：plugin.php?id=flipbook:read&albumid=1&page=3
 * 目录、缩略图、上一页/下一页与阅读进度都在同一页内完成，不产生整页刷新。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$albumid = isset($_GET['albumid']) ? max(0, intval($_GET['albumid'])) : 0;
$albumrow = $albumid > 0 ? flipbook\table_flipbook_album::t()->fetch($albumid) : [];

if(!$albumrow || empty($albumrow['available'])) {
	$fb = front_view::base('index', '内容已下架');
	$fb['album'] = ['name' => '画册', 'albumid' => 0, 'backurl' => front_helper::url('index')];
	$fb['offline'] = 1;
	$fb['pages'] = [];
	include template('flipbook:read');
	return;
}

$album = front_view::album($albumrow);
$pages = front_view::pages($albumid);
$pagecount = max(1, count($pages));
$current = front_helper::page_index(isset($_GET['page']) ? $_GET['page'] : 1, $pagecount);

$uid = front_helper::uid();
$favorites = front_view::favorite_ids($uid);
$favoritecount = 0;

foreach($pages as $index => $page) {
	$styles = [];
	$pagefavorite = 0;
	foreach($page['products'] as $productid) {
		$row = flipbook\table_flipbook_product::t()->fetch($productid);
		if(!$row || empty($row['available'])) {
			continue;
		}
		$styles[] = [
			'productid' => intval($row['productid']),
			'name' => front_helper::e($row['name']),
			'url' => front_helper::url('style', ['productid' => intval($row['productid'])]),
		];
		if(isset($favorites['product'][intval($productid)])) {
			$pagefavorite = 1;
		}
	}
	$pages[$index]['styles'] = $styles;
	$pages[$index]['favorited'] = $pagefavorite;
	$pages[$index]['styleurl'] = $styles ? front_helper::e($styles[0]['url']) : '';
	$pages[$index]['readurl'] = front_helper::url('read', ['albumid' => $albumid, 'page' => $page['index']]);
	if($pagefavorite) {
		$favoritecount++;
	}
}

$fb = front_view::base('index', $album['name']);
$fb['album'] = $album;
$fb['album']['backurl'] = front_helper::url('index');
$fb['pages'] = $pages;
$fb['pagecount'] = $pagecount;
$fb['pagecountpad'] = front_helper::pad($pagecount);
$fb['current'] = $pages[$current - 1];
$fb['favorited'] = isset($favorites['album'][$albumid]) ? 1 : 0;
$fb['favoritecount'] = $favoritecount;
$fb['favoritecountpad'] = front_helper::pad($favoritecount);
$fb['styleurl'] = $fb['current']['styles'] ? $fb['current']['styles'][0]['url'] : '';

// 分享面板：整本画册与当前款式两条分享对象，均带海报数据
$settings = front_view::settings();
$brand = front_helper::e($settings['albumname']);
$fb['share'] = ['scope' => 'album', 'targets' => []];
$fb['share']['targets']['album'] = [
	'key' => 'album',
	'label' => front_helper::e('整本画册'),
	'title' => front_helper::e($albumrow['name']),
	'meta' => front_helper::pad($pagecount).' '.front_helper::e('页').' · '.front_helper::e($fb['current']['title']),
	'cover' => $album['cover'],
	'hascover' => $album['hascover'],
	'targetid' => $albumid,
	'url' => front_helper::url('read', ['albumid' => $albumid, 'page' => $current]),
	'poster' => [
		'image' => $album['cover'],
		'title' => front_helper::e($albumrow['name']),
		'meta' => front_helper::e($albumrow['subtitle']),
		'points' => front_helper::e($albumrow['description'] !== '' ? $albumrow['description'] : $fb['current']['title']),
		'brand' => $brand,
	],
];
$currentstyle = $fb['current']['styles'] ? $fb['current']['styles'][0] : [];
if($currentstyle) {
	$prow = flipbook\table_flipbook_product::t()->fetch($currentstyle['productid']);
	if($prow && !empty($prow['available'])) {
		$detail = front_view::product_detail($prow);
		$color = $detail['spec']['colors'] ? $detail['spec']['colors'][0] : '';
		$fb['share']['targets']['product'] = [
			'key' => 'product',
			'label' => front_helper::e('当前款式'),
			'title' => $detail['name'],
			'meta' => trim($detail['code'].($color !== '' ? ' · '.$color : '')),
			'cover' => $detail['cover'],
			'hascover' => $detail['hascover'],
			'targetid' => intval($prow['productid']),
			'url' => $detail['url'],
			'poster' => [
				'image' => $detail['cover'],
				'title' => $detail['name'],
				'meta' => trim($detail['code'].($color !== '' ? ' · '.$color : '')),
				'points' => $detail['summary'],
				'brand' => $brand,
			],
		];
	}
}

include template('flipbook:read');
