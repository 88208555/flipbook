<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 微页面
 *
 * 访问：plugin.php?id=flipbook:micro&key=home
 * 页面结构、导航、组件顺序与样式全部来自后台微页面配置。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

use flipbook\front_micro;
use flipbook\front_view;

$isLogin=isset($_GET['login'])&&is_scalar($_GET['login'])&&(string)$_GET['login']==='1';
if(!$isLogin && front_micro::is_custom_key($_GET['key']??null) && !isset(front_micro::keys()[$_GET['key']])) {
 http_response_code(404);showmessage('页面不存在或已删除');
}
$key = $isLogin?'login':front_micro::key(isset($_GET['key']) ? $_GET['key'] : 'home');
$page = front_micro::page($key);
if(isset($_GET['device_preview']) && is_scalar($_GET['device_preview']) && (string)$_GET['device_preview']==='1') {
 $params=['key'=>$key];
 if($isLogin){$params['login']=1;$params['return']=flipbook\front_helper::login_return($_GET['return']??'');}
 foreach(['productid','albumid','page'] as $field)if(isset($_GET[$field])&&is_scalar($_GET[$field])&&intval($_GET[$field])>0)$params[$field]=intval($_GET[$field]);
 foreach(['action','tab'] as $field)if(isset($_GET[$field])&&is_string($_GET[$field])&&in_array($_GET[$field],['read','share','album','product'],true))$params[$field]=$_GET[$field];
 $devicePreview=['title'=>flipbook\front_helper::e($isLogin?'登录':$page['name']),'url'=>flipbook\front_helper::e(flipbook\front_helper::url('micro',$params)),
  'mode'=>isset($_GET['embedded_preview'])&&is_scalar($_GET['embedded_preview'])&&(string)$_GET['embedded_preview']==='1'?' is-embedded':'',
  'css'=>flipbook\front_helper::asset('css/micro-preview.css').'?'.intval(filemtime(__DIR__.'/static/css/micro-preview.css')),
  'script'=>flipbook\front_helper::asset('js/micro-preview.js').'?'.intval(filemtime(__DIR__.'/static/js/micro-preview.js'))];
 include template('flipbook:micro_preview');
 return;
}

$fb = front_view::base('micro', $isLogin?'登录':$page['name']);
$fb['businessPreview'] = false;
$fb['nonav'] = 1;
$fb['microkey'] = $key;
$fb['microactions'] = json_encode(front_micro::action_data($page['config']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$fb['micro'] = front_micro::render($page);

include template('flipbook:micro');
