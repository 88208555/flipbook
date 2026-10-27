<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 发现页
 *
 * 访问：plugin.php?id=flipbook:index
 * 参数：tab=series|recommend
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$tab = isset($_GET['tab']) && $_GET['tab'] === 'series' ? 'series' : 'recommend';
$uid = front_helper::uid();

$fb = front_view::base('index', '发现');
$fb['tab'] = $tab;
$fb['taburls'] = [
	'series' => front_helper::url('index', ['tab' => 'series']),
	'recommend' => front_helper::url('index', ['tab' => 'recommend']),
];
$fb['albums'] = front_view::albums();
$fb['series'] = front_view::series_list();
$fb['seriescount'] = count($fb['series']);

// 首屏主视觉取前三册，收藏状态按当前原生会员读取
$favorites = front_view::favorite_ids($uid);
$fb['heroes'] = [];
foreach($fb['albums'] as $album) {
	$album['favorited'] = isset($favorites['album'][$album['albumid']]) ? 1 : 0;
	$album['styleurl'] = front_helper::e(front_view::first_style_url($album['albumid']));
	$fb['heroes'][] = $album;
}
$fb['share'] = ['scope' => 'album', 'targets' => []];
if($fb['heroes']) {
	$hero = $fb['heroes'][0];
	$brand = front_helper::e(front_view::settings()['albumname']);
	$fb['share']['targets']['album'] = [
		'key' => 'album',
		'label' => front_helper::e('整本画册'),
		'title' => $hero['name'],
		'meta' => $hero['pagecount'].' '.front_helper::e('页'),
		'cover' => $hero['cover'],
		'hascover' => $hero['hascover'],
		'targetid' => $hero['albumid'],
		'url' => $hero['url'],
		'poster' => [
			'image' => $hero['cover'],
			'title' => $hero['name'],
			'meta' => $hero['subtitle'],
			'points' => $hero['description'] !== '' ? $hero['description'] : $hero['subtitle'],
			'brand' => $brand,
		],
	];
}

$fb['captionOptions']=flipbook\front_micro::caption_options(($fb_social_options??[])['caption']??[]);$fb['captionStyle']=flipbook\front_micro::caption_style($fb['captionOptions']);$fb['floatingOptions']=flipbook\front_micro::floating_options(($fb_social_options??[])['floating']??[]);$fb['floatingStyle']=flipbook\front_micro::floating_style($fb['floatingOptions']);
include template(!empty($fb_micro_embed) ? 'flipbook:micro_discover' : 'flipbook:discover');
