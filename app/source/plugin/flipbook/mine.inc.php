<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 我的
 *
 * 访问：plugin.php?id=flipbook:mine
 * 会员身份、用户组与退出地址全部复用原生会话，不建立平行账号体系。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_helper;
use flipbook\front_view;

$uid = front_helper::uid();
$favorites = front_view::favorites($uid);
$history = front_view::history_list($uid, 'read');
$historycount = 0;
foreach($history as $group) {
	$historycount += count($group);
}
$share = front_view::history_list($uid, 'share');
$sharecount = 0;
foreach($share as $group) {
	$sharecount += count($group);
}

$fb = front_view::base('mine', '我的');
$fb['logged'] = front_helper::is_login();
$fb['loginurl'] = $fb['member']['loginurl'];
$fb['counts'] = [
	'favorite' => front_helper::pad($favorites['counts']['total']),
	'history' => front_helper::pad($historycount),
	'share' => front_helper::pad($sharecount),
];
$fb['resume'] = front_view::resume($uid);
$fb['links'] = [
	'history' => front_helper::url('history'),
	'favorites' => front_helper::url('favorites'),
];

$fb['options']= $fb_member_options ?? flipbook\front_micro::member_options();
$fb['rows']=[];
foreach(['history'=>'history','share'=>'share','about'=>'info','privacy'=>'info','agreement'=>'info'] as $rowkey=>$icon) {
 $o=$fb['options'];if(!$o[$rowkey.'Enabled'])continue;$href=flipbook\front_micro::link_url($o[$rowkey.'Link']);if($rowkey==='share' && $o[$rowkey.'Link']==='page:history')$href.='&action=share';
 $fb['rows'][]=['key'=>$rowkey,'label'=>front_helper::e($o[$rowkey.'Label']),'url'=>front_helper::e($href),'icon'=>$icon];
}
include template(!empty($fb_micro_embed) ? 'flipbook:micro_mine' : 'flipbook:mine');

