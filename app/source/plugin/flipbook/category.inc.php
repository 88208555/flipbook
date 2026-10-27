<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台：分类
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

use flipbook\admin_helper;
use flipbook\admin_crud;

if(empty($flipbookDraftConfigOnly)) flipbook\admin_product::navigation('category');

$config = [
	'pmod' => 'category',
	'table' => 'flipbook_category',
	'title' => '商品分类',
	'orderby' => ['displayorder' => 'ASC'],
	'columns' => ['name', 'parentid', 'displayorder', 'available'],
	'schema' => [
		'name' => ['title' => '分类名称', 'type' => 'text', 'required' => true, 'maxlen' => 100],
		'parentid' => ['title' => '上级分类', 'type' => 'select', 'source' => 'category', 'comment' => '留空为顶级分类'],
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
	$row = admin_crud::fetch($config['table'], 'catid', admin_helper::uintval('id'));
	if($op === 'edit' && !$row) {
		admin_helper::failed('记录不存在或已删除');
	}
	admin_crud::render_form($config, $row);
} else {
	admin_crud::render_list($config, max(1, admin_helper::uintval('page')));
}