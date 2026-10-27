<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
cpmsg('商品资料与展示内容已统一到商品管理', 'action=plugins&operation=config&do='.intval($_GET['do']).'&identifier=flipbook&pmod=product', 'succeed');
