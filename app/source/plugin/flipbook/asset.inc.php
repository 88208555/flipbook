<?php
if(!defined('IN_DISCUZ'))exit('Access Denied');
$_GET['pmod']='micropage';loadcache('admin');
if(!flipbook\admin_helper::perm('edit')){http_response_code(403);header('Content-Type:application/json;charset=utf-8');echo json_encode(['ok'=>0,'message'=>'没有管理素材的权限']);exit;}
$op=(string)($_GET['op']??'');
if($op==='session'){header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');echo json_encode(['ok'=>1,'formhash'=>flipbook\front_helper::formhash()]);exit;}
if($op==='nav-icon')flipbook\front_nav_icon::respond();
if($op==='sizecharts')flipbook\admin_sizechart::respond();
if(in_array($op,['assets','remove_asset'],true))flipbook\front_asset::respond($op);
elseif($op==='upload')flipbook\front_upload::respond();
else{http_response_code(400);header('Content-Type:application/json;charset=utf-8');echo json_encode(['ok'=>0,'message'=>'不支持的操作']);}
