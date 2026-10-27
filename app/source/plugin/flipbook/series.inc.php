<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台：系列
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

use flipbook\admin_helper;
use flipbook\admin_crud;

if(empty($flipbookDraftConfigOnly)) flipbook\admin_product::navigation('series');

$config = [
	'pmod' => 'series',
	'table' => 'flipbook_series',
	'title' => '商品系列',
	'orderby' => ['displayorder' => 'ASC'],
	'columns' => ['name', 'description', 'cover', 'displayorder', 'available'],
	'schema' => [
		'name' => ['title' => '系列名称', 'type' => 'text', 'required' => true, 'maxlen' => 100],
		'description' => ['title' => '简介', 'type' => 'textarea', 'maxlen' => 255],
		'cover' => ['title' => '封面图地址', 'type' => 'url', 'comment' => '留空则前台使用默认封面；支持站内相对路径与 https 地址'],
		'displayorder' => ['title' => '排序', 'type' => 'int', 'default' => 0],
		'available' => ['title' => '是否上架', 'type' => 'bool', 'default' => 1],
	],
];

if(!empty($flipbookDraftConfigOnly)) return $config;

if(admin_crud::handle_post($config)) {
	return;
}

$op = admin_helper::enumval('op', ['list' => '', 'add' => '', 'edit' => '']);

if($op === 'add' || $op === 'edit') {
	$row = admin_crud::fetch($config['table'], 'seriesid', admin_helper::uintval('id'));
	if($op === 'edit' && !$row) {
		admin_helper::failed('记录不存在或已删除');
	}
	admin_crud::render_form($config, $row);
} else {
	admin_crud::render_list($config, max(1, admin_helper::uintval('page')));
}