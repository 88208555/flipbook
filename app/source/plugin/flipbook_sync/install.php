<?php
if(!defined('IN_DISCUZ')) exit('Access Denied');
if(!DB::result_first('SELECT pluginid FROM %t WHERE identifier=%s',['common_plugin','flipbook']) || !class_exists('flipbook\table_flipbook_product')) throw new RuntimeException('请先安装工装画册 flipbook 插件');
\flipbook_sync\service_store::install();
// Also migrate the installed label; changing the manifest alone does not rename it.
DB::update('common_plugin',['name'=>'产品同步'],['identifier'=>'flipbook_sync']);
require_once libfile('function/cache');
updatecache(['plugin','setting']);
$finish=true;
