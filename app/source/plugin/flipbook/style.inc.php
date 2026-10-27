<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 款式详情
 *
 * 访问：plugin.php?id=flipbook:style&productid=1
 * 三个分段（卖点 / 细节 / 规格）共用同一外壳，切换不刷新页面。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$productid = isset($_GET['productid']) ? max(0, intval($_GET['productid'])) : 0;
$row = $productid > 0 ? flipbook\table_flipbook_product::t()->fetch($productid) : [];

$fb = front_view::base('catalog', '款式详情');
$fb['nonav'] = 1;
$fb['captionOptions']=flipbook\front_micro::caption_options(($fb_social_options??[])['caption']??[], 'left');$fb['captionStyle']=flipbook\front_micro::caption_style($fb['captionOptions']);$fb['floatingOptions']=flipbook\front_micro::floating_options(($fb_social_options??[])['floating']??[]);$fb['floatingStyle']=flipbook\front_micro::floating_style($fb['floatingOptions']);
$fb['backurl'] = isset($_SERVER['HTTP_REFERER']) && strpos((string)$_SERVER['HTTP_REFERER'], 'plugin.php') !== false ? front_helper::e($_SERVER['HTTP_REFERER']) : front_helper::url('index');

if(!$row || empty($row['available'])) {
	$fb['product'] = [];
include template(!empty($fb_micro_embed) ? 'flipbook:micro_style' : 'flipbook:style');
	return;
}

$favorites = front_view::favorite_ids(front_helper::uid());
$product = front_view::product_detail($row);
$product['albums'] = front_view::product_albums($productid);
$product['favorited'] = isset($favorites['product'][$productid]) ? 1 : 0;
$fb['product'] = $product;
$fb['title'] = $product['name'];

// 分享面板以款式为主体；存在关联画册时同时提供整本画册
$settings = front_view::settings();
$brand = front_helper::e($settings['albumname']);
$color = $product['spec']['colors'] ? $product['spec']['colors'][0] : '';
$meta = trim($product['code'].($color !== '' ? ' · '.$color : ''));
$fb['share'] = ['scope' => 'product', 'targets' => []];
$fb['share']['targets']['product'] = [
	'key' => 'product',
	'label' => front_helper::e('当前款式'),
	'title' => $product['name'],
	'meta' => $meta,
	'cover' => $product['cover'],
	'hascover' => $product['hascover'],
	'targetid' => $productid,
	'url' => front_helper::url('style', ['productid' => $productid]),
	'poster' => [
		'image' => $product['cover'],
		'title' => $product['name'],
		'meta' => $meta,
		'points' => $product['summary'],
		'brand' => $brand,
	],
];
if($product['albums']) {
	$firstalbum = $product['albums'][0];
	$fb['share']['targets']['album'] = [
		'key' => 'album',
		'label' => front_helper::e('整本画册'),
		'title' => $firstalbum['name'],
		'meta' => $firstalbum['pagecount'].' '.front_helper::e('页').' · '.$product['name'],
		'cover' => $firstalbum['cover'],
		'hascover' => $firstalbum['hascover'],
		'targetid' => $firstalbum['albumid'],
		'url' => $firstalbum['url'],
		'poster' => [
			'image' => $firstalbum['cover'],
			'title' => $firstalbum['name'],
			'meta' => $firstalbum['subtitle'],
			'points' => $firstalbum['description'] !== '' ? $firstalbum['description'] : $product['name'],
			'brand' => $brand,
		],
	];
}

// Shared primary gallery: video, product cover, then this product's SKU images.
$fb['slides']=[];
if($product['hasvideo'])$fb['slides'][]=['image'=>$product['firstframe']?:$product['cover'],'video'=>$product['video'],'title'=>$product['name'],'text'=>''];
if($product['hascover'])$fb['slides'][]=['image'=>$product['cover'],'title'=>$product['name'],'text'=>''];
foreach($product['groups']['sku']??[] as $sku){if($sku['hasimage'] && intval($sku['productid'])===intval($product['productid']))$fb['slides'][]=$sku;}
$fb['skus']=[];foreach($fb['slides'] as $index=>$slide){$slide['slideIndex']=$index;$fb['skus'][]=$slide;}
include template(!empty($fb_micro_embed) ? 'flipbook:micro_style' : 'flipbook:style');
