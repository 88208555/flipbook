<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 安装脚本
 *
 * 约定：
 * 1. 安装必须可重复执行——只创建缺失结构，不删除、不覆盖任何既有业务数据；
 * 2. 表前缀由框架提供（%t 占位符），不得硬写 pre_ / cdb_；
 * 3. 站点凭据（微信 AppID、COS 密钥等）不进安装数据，由各站在后台单独填写。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$schema = [
 'flipbook_supplier'=>"CREATE TABLE IF NOT EXISTS %t (`supplierid` int unsigned NOT NULL AUTO_INCREMENT,`name` varchar(150) NOT NULL,`code` varchar(60) NOT NULL DEFAULT '',`contact` varchar(100) NOT NULL DEFAULT '',`phone` varchar(60) NOT NULL DEFAULT '',`address` varchar(500) NOT NULL DEFAULT '',`note` text,`displayorder` int NOT NULL DEFAULT 0,`available` tinyint NOT NULL DEFAULT 1,PRIMARY KEY (`supplierid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

 'flipbook_sku'=>"CREATE TABLE IF NOT EXISTS %t (`skuid` int unsigned NOT NULL AUTO_INCREMENT,`productid` int unsigned NOT NULL,`code` varchar(80) NOT NULL,`name` varchar(100) NOT NULL DEFAULT '',`color` varchar(60) NOT NULL DEFAULT '',`size` varchar(60) NOT NULL DEFAULT '',`images` longtext,`displayorder` int NOT NULL DEFAULT 0,`available` tinyint NOT NULL DEFAULT 1,PRIMARY KEY (`skuid`),UNIQUE KEY `code` (`code`),KEY `productid` (`productid`,`displayorder`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

 'flipbook_asset'=>"CREATE TABLE IF NOT EXISTS %t (`assetid` int unsigned NOT NULL AUTO_INCREMENT,`uid` int unsigned NOT NULL DEFAULT 0,`productid` int unsigned NOT NULL DEFAULT 0,`scope` varchar(20) NOT NULL,`purpose` varchar(30) NOT NULL,`kind` varchar(10) NOT NULL,`provider` varchar(10) NOT NULL,`objectkey` varchar(500) NOT NULL,`bucket` varchar(100) NOT NULL,`region` varchar(60) NOT NULL,`url` varchar(1000) NOT NULL,`size` bigint unsigned NOT NULL,`status` varchar(20) NOT NULL DEFAULT 'active',`created` int unsigned NOT NULL,`updated` int unsigned NOT NULL,PRIMARY KEY (`assetid`),KEY `product` (`productid`,`status`),KEY `status` (`status`,`created`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

 'flipbook_identity' => "CREATE TABLE IF NOT EXISTS %t (`identity` char(64) NOT NULL, `uid` int unsigned NOT NULL, `dateline` int unsigned NOT NULL, PRIMARY KEY (`identity`), KEY `uid` (`uid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
	'flipbook_series' => "CREATE TABLE IF NOT EXISTS %t (
		`seriesid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(100) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`cover` varchar(255) NOT NULL DEFAULT '',
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		`available` tinyint(1) NOT NULL DEFAULT '1',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`seriesid`),
		KEY `available` (`available`,`displayorder`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_category' => "CREATE TABLE IF NOT EXISTS %t (
		`catid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(100) NOT NULL DEFAULT '',
		`parentid` int(10) unsigned NOT NULL DEFAULT '0',
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		`available` tinyint(1) NOT NULL DEFAULT '1',
		PRIMARY KEY (`catid`),
		KEY `parentid` (`parentid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_album' => "CREATE TABLE IF NOT EXISTS %t (
		`albumid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`seriesid` int(10) unsigned NOT NULL DEFAULT '0',
		`catid` int(10) unsigned NOT NULL DEFAULT '0',
		`name` varchar(100) NOT NULL DEFAULT '',
		`subtitle` varchar(255) NOT NULL DEFAULT '',
		`description` text,
		`cover` varchar(255) NOT NULL DEFAULT '',
		`video` varchar(255) NOT NULL DEFAULT '',
		`pagecount` int(10) unsigned NOT NULL DEFAULT '0',
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		`available` tinyint(1) NOT NULL DEFAULT '1',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`albumid`),
		KEY `seriesid` (`seriesid`,`available`,`displayorder`),
		KEY `catid` (`catid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_page' => "CREATE TABLE IF NOT EXISTS %t (
		`pageid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`albumid` int(10) unsigned NOT NULL DEFAULT '0',
		`title` varchar(150) NOT NULL DEFAULT '',
		`eyebrow` varchar(100) NOT NULL DEFAULT '',
		`description` varchar(255) NOT NULL DEFAULT '',
		`tags` varchar(255) NOT NULL DEFAULT '',
		`image` varchar(255) NOT NULL DEFAULT '',
		`imagecfg` text,
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		`available` tinyint(1) NOT NULL DEFAULT '1',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`pageid`),
		KEY `albumid` (`albumid`,`available`,`displayorder`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_product' => "CREATE TABLE IF NOT EXISTS %t (
		`productid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(150) NOT NULL DEFAULT '',
		`code` varchar(60) NOT NULL DEFAULT '',
		`summary` varchar(255) NOT NULL DEFAULT '',
		`description` text,
		`cover` varchar(255) NOT NULL DEFAULT '',
		`video` varchar(255) NOT NULL DEFAULT '',
		`priceshow` tinyint(1) NOT NULL DEFAULT '0',
		`pricevalue` decimal(10,2) NOT NULL DEFAULT '0.00',
		`priceorigin` decimal(10,2) NOT NULL DEFAULT '0.00',
		`currency` varchar(8) NOT NULL DEFAULT 'CNY',
		`spec` text,
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		`available` tinyint(1) NOT NULL DEFAULT '1',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`productid`),
		KEY `available` (`available`,`displayorder`),
		KEY `code` (`code`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_product_media' => "CREATE TABLE IF NOT EXISTS %t (
		`mediaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`productid` int(10) unsigned NOT NULL DEFAULT '0',
		`type` varchar(20) NOT NULL DEFAULT 'detail',
		`title` varchar(150) NOT NULL DEFAULT '',
		`image` varchar(255) NOT NULL DEFAULT '',
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		PRIMARY KEY (`mediaid`),
		KEY `productid` (`productid`,`type`,`displayorder`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_page_product' => "CREATE TABLE IF NOT EXISTS %t (
		`pageid` int(10) unsigned NOT NULL DEFAULT '0',
		`productid` int(10) unsigned NOT NULL DEFAULT '0',
		`displayorder` smallint(6) NOT NULL DEFAULT '0',
		PRIMARY KEY (`pageid`,`productid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_favorite' => "CREATE TABLE IF NOT EXISTS %t (
		`uid` int(10) unsigned NOT NULL DEFAULT '0',
		`targettype` varchar(10) NOT NULL DEFAULT 'album',
		`targetid` int(10) unsigned NOT NULL DEFAULT '0',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`uid`,`targettype`,`targetid`),
		KEY `target` (`targettype`,`targetid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_history' => "CREATE TABLE IF NOT EXISTS %t (
		`historyid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`uid` int(10) unsigned NOT NULL DEFAULT '0',
		`targettype` varchar(10) NOT NULL DEFAULT 'album',
		`targetid` int(10) unsigned NOT NULL DEFAULT '0',
		`action` varchar(20) NOT NULL DEFAULT 'read',
		`progress` text,
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`historyid`),
		KEY `uid` (`uid`,`action`,`dateline`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_preference' => "CREATE TABLE IF NOT EXISTS %t (
		`uid` int(10) unsigned NOT NULL DEFAULT '0',
		`prefs` text,
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`uid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_setting' => "CREATE TABLE IF NOT EXISTS %t (
		`skey` varchar(64) NOT NULL DEFAULT '',
		`svalue` text,
		`version` varchar(20) NOT NULL DEFAULT '',
		PRIMARY KEY (`skey`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_release' => "CREATE TABLE IF NOT EXISTS %t (
		`releaseid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(100) NOT NULL DEFAULT '',
		`status` tinyint(1) NOT NULL DEFAULT '0',
		`snapshot` longtext,
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`releaseid`),
		KEY `status` (`status`,`dateline`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

	'flipbook_micropage' => "CREATE TABLE IF NOT EXISTS %t (
		`pageid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`pkey` varchar(32) NOT NULL DEFAULT '',
		`name` varchar(100) NOT NULL DEFAULT '',
		`config` longtext,
		`status` tinyint(1) NOT NULL DEFAULT '1',
		`version` int(10) unsigned NOT NULL DEFAULT '1',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`pageid`),
		UNIQUE KEY `pkey` (`pkey`),
		KEY `status` (`status`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$schema['flipbook_order']=flipbook\front_order_store::schema();

foreach($schema as $table => $sql) {
	DB::query($sql, [$table]);
}

$productColumns=array_column(DB::fetch_all('SHOW COLUMNS FROM %t',['flipbook_product']),'Field');
if(!in_array('supplierid',$productColumns,true))DB::query('ALTER TABLE %t ADD supplierid int unsigned NOT NULL DEFAULT 0, ADD KEY supplierid(supplierid)',['flipbook_product']);
// Preserve any old free-text supplier as a managed supplier record.
foreach(DB::fetch_all('SELECT productid,spec FROM %t WHERE supplierid=0',['flipbook_product']) as $product){$spec=json_decode($product['spec'],true)?:[];$name=trim((string)($spec['supplier']??''));if($name==='')continue;$supplierid=DB::result_first('SELECT supplierid FROM %t WHERE name=%s',['flipbook_supplier',$name]);if(!$supplierid)$supplierid=DB::insert('flipbook_supplier',['name'=>mb_substr($name,0,150),'note'=>'','available'=>1],true);DB::update('flipbook_product',['supplierid'=>$supplierid],['productid'=>$product['productid']]);}
flipbook\front_sku::migrate();

// 干净的初始业务配置：已存在的键不覆盖，站长保存过的值优先
$defaults = [
	'albumname' => '工装电子画册',
	'showprice' => '0',
	'postertemplate' => 'default',
	'schemaversion' => '1',
];
foreach($defaults as $skey => $svalue) {
	DB::query('INSERT IGNORE INTO %t (skey, svalue, version) VALUES (%s, %s, %s)', ['flipbook_setting', $skey, $svalue, '0.1.0']);
}

// A single native hourly task retries failed deletes and expires abandoned uploads.
$cronid=DB::result_first('SELECT cronid FROM %t WHERE filename=%s',['common_cron','flipbook:assets.php']);
if(!$cronid)DB::insert('common_cron',['available'=>1,'type'=>'plugin','name'=>'工装画册素材清理','filename'=>'flipbook:assets.php','lastrun'=>0,'nextrun'=>TIMESTAMP+3600,'weekday'=>-1,'day'=>-1,'hour'=>-1,'minute'=>'0']);

$finish = TRUE;
