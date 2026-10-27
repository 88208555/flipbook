-- Flipbook deployment schema; original table prefix: pre_
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `pre_common_admincp_cmenu` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL,
  `clicks` smallint unsigned NOT NULL DEFAULT '1',
  `uid` mediumint unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `displayorder` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admincp_group` (
  `cpgroupid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `cpgroupname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`cpgroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admincp_member` (
  `uid` int unsigned NOT NULL,
  `cpgroupid` int unsigned NOT NULL,
  `customperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admincp_menu_platform` (
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `menu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admincp_perm` (
  `cpgroupid` smallint unsigned NOT NULL,
  `perm` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY `cpgroupperm` (`cpgroupid`,`perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admincp_session` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `adminid` smallint unsigned NOT NULL DEFAULT '0',
  `panel` tinyint(1) NOT NULL DEFAULT '0',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `errorcount` tinyint(1) NOT NULL DEFAULT '0',
  `storage` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`,`panel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_admingroup` (
  `admingid` smallint unsigned NOT NULL DEFAULT '0',
  `alloweditpost` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditpoll` tinyint(1) NOT NULL DEFAULT '0',
  `allowstickthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowmodpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowdelpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowmassprune` tinyint(1) NOT NULL DEFAULT '0',
  `allowrefund` tinyint(1) NOT NULL DEFAULT '0',
  `allowcensorword` tinyint(1) NOT NULL DEFAULT '0',
  `allowviewip` tinyint(1) NOT NULL DEFAULT '0',
  `allowbanip` tinyint(1) NOT NULL DEFAULT '0',
  `allowedituser` tinyint(1) NOT NULL DEFAULT '0',
  `allowmoduser` tinyint(1) NOT NULL DEFAULT '0',
  `allowbanuser` tinyint(1) NOT NULL DEFAULT '0',
  `allowbanvisituser` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostannounce` tinyint(1) NOT NULL DEFAULT '0',
  `allowviewlog` tinyint(1) NOT NULL DEFAULT '0',
  `allowbanpost` tinyint(1) NOT NULL DEFAULT '0',
  `supe_allowpushthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowhighlightthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowlivethread` tinyint(1) NOT NULL DEFAULT '0',
  `allowdigestthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowrecommendthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowbumpthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowclosethread` tinyint(1) NOT NULL DEFAULT '0',
  `allowmovethread` tinyint(1) NOT NULL DEFAULT '0',
  `allowedittypethread` tinyint(1) NOT NULL DEFAULT '0',
  `allowstampthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowstamplist` tinyint(1) NOT NULL DEFAULT '0',
  `allowcopythread` tinyint(1) NOT NULL DEFAULT '0',
  `allowmergethread` tinyint(1) NOT NULL DEFAULT '0',
  `allowsplitthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowrepairthread` tinyint(1) NOT NULL DEFAULT '0',
  `allowwarnpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowviewreport` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditforum` tinyint(1) NOT NULL DEFAULT '0',
  `allowremovereward` tinyint(1) NOT NULL DEFAULT '0',
  `allowedittrade` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditactivity` tinyint(1) NOT NULL DEFAULT '0',
  `allowstickreply` tinyint(1) NOT NULL DEFAULT '0',
  `allowmanagearticle` tinyint(1) NOT NULL DEFAULT '0',
  `allowaddtopic` tinyint(1) NOT NULL DEFAULT '0',
  `allowmanagetopic` tinyint(1) NOT NULL DEFAULT '0',
  `allowdiy` tinyint(1) NOT NULL DEFAULT '0',
  `allowclearrecycle` tinyint(1) NOT NULL DEFAULT '0',
  `allowmanagetag` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditusertag` tinyint(1) NOT NULL DEFAULT '0',
  `managefeed` tinyint(1) NOT NULL DEFAULT '0',
  `managedoing` tinyint(1) NOT NULL DEFAULT '0',
  `manageshare` tinyint(1) NOT NULL DEFAULT '0',
  `manageblog` tinyint(1) NOT NULL DEFAULT '0',
  `managealbum` tinyint(1) NOT NULL DEFAULT '0',
  `managecomment` tinyint(1) NOT NULL DEFAULT '0',
  `managemagiclog` tinyint(1) NOT NULL DEFAULT '0',
  `managereport` tinyint(1) NOT NULL DEFAULT '0',
  `managehotuser` tinyint(1) NOT NULL DEFAULT '0',
  `managedefaultuser` tinyint(1) NOT NULL DEFAULT '0',
  `managemagic` tinyint(1) NOT NULL DEFAULT '0',
  `manageclick` tinyint(1) NOT NULL DEFAULT '0',
  `allowmanagecollection` tinyint(1) NOT NULL DEFAULT '0',
  `allowmakehtml` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`admingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_adminnote` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `admin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `access` tinyint NOT NULL DEFAULT '0',
  `adminid` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_advertisement` (
  `advid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `targets` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`advid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_advertisement_custom` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_banned` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(49) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lowerip` varbinary(16) NOT NULL DEFAULT '\0',
  `upperip` varbinary(16) NOT NULL DEFAULT '\0',
  `admin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `iprange` (`lowerip`,`upperip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block` (
  `bid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `blockclass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `blocktype` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `classname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `styleid` smallint unsigned NOT NULL DEFAULT '0',
  `blockstyle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `picwidth` mediumint unsigned NOT NULL DEFAULT '0',
  `picheight` mediumint unsigned NOT NULL DEFAULT '0',
  `target` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateformat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateuformat` tinyint(1) NOT NULL DEFAULT '0',
  `script` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `param` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shownum` smallint unsigned NOT NULL DEFAULT '0',
  `cachetime` int NOT NULL DEFAULT '0',
  `cachetimerange` char(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `punctualupdate` tinyint(1) NOT NULL DEFAULT '0',
  `hidedisplay` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `notinherited` tinyint(1) NOT NULL DEFAULT '0',
  `isblank` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_favorite` (
  `favid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`favid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_item` (
  `itemid` int unsigned NOT NULL AUTO_INCREMENT,
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `itemtype` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `makethumb` tinyint(1) NOT NULL DEFAULT '0',
  `thumbpath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `showstyle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayorder` smallint NOT NULL DEFAULT '0',
  `startdate` int unsigned NOT NULL DEFAULT '0',
  `enddate` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`itemid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_item_data` (
  `dataid` int unsigned NOT NULL AUTO_INCREMENT,
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `itemtype` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `makethumb` tinyint(1) NOT NULL DEFAULT '0',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `showstyle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayorder` smallint NOT NULL DEFAULT '0',
  `startdate` int unsigned NOT NULL DEFAULT '0',
  `enddate` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `isverified` tinyint(1) NOT NULL DEFAULT '0',
  `verifiedtime` int unsigned NOT NULL DEFAULT '0',
  `stickgrade` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`dataid`),
  KEY `bid` (`bid`,`stickgrade`,`displayorder`,`verifiedtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_permission` (
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `allowmanage` tinyint(1) NOT NULL DEFAULT '0',
  `allowrecommend` tinyint(1) NOT NULL DEFAULT '0',
  `needverify` tinyint(1) NOT NULL DEFAULT '0',
  `inheritedtplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`bid`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_pic` (
  `picid` int unsigned NOT NULL AUTO_INCREMENT,
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `itemid` int unsigned NOT NULL DEFAULT '0',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`picid`),
  KEY `bid` (`bid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_style` (
  `styleid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `blockclass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `getpic` tinyint(1) NOT NULL DEFAULT '0',
  `getsummary` tinyint(1) NOT NULL DEFAULT '0',
  `makethumb` tinyint(1) NOT NULL DEFAULT '0',
  `settarget` tinyint(1) NOT NULL DEFAULT '0',
  `fields` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `moreurl` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`styleid`),
  KEY `hash` (`hash`(10)),
  KEY `blockclass` (`blockclass`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_block_xml` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clientid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_cache` (
  `cachekey` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cachevalue` mediumblob NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cachekey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_card` (
  `id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `typeid` smallint unsigned NOT NULL DEFAULT '1',
  `maketype` tinyint(1) NOT NULL DEFAULT '0',
  `makeruid` mediumint unsigned NOT NULL DEFAULT '0',
  `price` mediumint unsigned NOT NULL DEFAULT '0',
  `extcreditskey` tinyint(1) NOT NULL DEFAULT '0',
  `extcreditsval` int NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `cleardateline` int unsigned NOT NULL DEFAULT '0',
  `useddateline` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_card_log` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `uid` mediumint NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cardrule` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dateline` (`dateline`),
  KEY `operation_dateline` (`operation`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_card_type` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `typename` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_credit_log` (
  `logid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `operation` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `relatedid` int unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  `extcredits1` int NOT NULL,
  `extcredits2` int NOT NULL,
  `extcredits3` int NOT NULL,
  `extcredits4` int NOT NULL,
  `extcredits5` int NOT NULL,
  `extcredits6` int NOT NULL,
  `extcredits7` int NOT NULL,
  `extcredits8` int NOT NULL,
  PRIMARY KEY (`logid`),
  KEY `uid` (`uid`),
  KEY `operation` (`operation`),
  KEY `relatedid` (`relatedid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_credit_log_field` (
  `logid` int unsigned NOT NULL,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL,
  `ac_extcredits1` int NOT NULL,
  `ac_extcredits2` int NOT NULL,
  `ac_extcredits3` int NOT NULL,
  `ac_extcredits4` int NOT NULL,
  `ac_extcredits5` int NOT NULL,
  `ac_extcredits6` int NOT NULL,
  `ac_extcredits7` int NOT NULL,
  `ac_extcredits8` int NOT NULL,
  KEY `logid` (`logid`),
  KEY `uid` (`uid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_credit_rule` (
  `rid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `rulename` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cycletype` tinyint(1) NOT NULL DEFAULT '0',
  `cycletime` int NOT NULL DEFAULT '0',
  `rewardnum` tinyint NOT NULL DEFAULT '1',
  `norepeat` tinyint(1) NOT NULL DEFAULT '0',
  `extcredits1` int NOT NULL DEFAULT '0',
  `extcredits2` int NOT NULL DEFAULT '0',
  `extcredits3` int NOT NULL DEFAULT '0',
  `extcredits4` int NOT NULL DEFAULT '0',
  `extcredits5` int NOT NULL DEFAULT '0',
  `extcredits6` int NOT NULL DEFAULT '0',
  `extcredits7` int NOT NULL DEFAULT '0',
  `extcredits8` int NOT NULL DEFAULT '0',
  `fids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`rid`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_credit_rule_log` (
  `clid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `rid` mediumint unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `total` mediumint unsigned NOT NULL DEFAULT '0',
  `cyclenum` mediumint unsigned NOT NULL DEFAULT '0',
  `extcredits1` int NOT NULL DEFAULT '0',
  `extcredits2` int NOT NULL DEFAULT '0',
  `extcredits3` int NOT NULL DEFAULT '0',
  `extcredits4` int NOT NULL DEFAULT '0',
  `extcredits5` int NOT NULL DEFAULT '0',
  `extcredits6` int NOT NULL DEFAULT '0',
  `extcredits7` int NOT NULL DEFAULT '0',
  `extcredits8` int NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`clid`),
  KEY `uid` (`uid`,`rid`,`fid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_credit_rule_log_field` (
  `clid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `app` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`,`clid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_cron` (
  `cronid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `type` enum('user','system','plugin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `name` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filename` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastrun` int unsigned NOT NULL DEFAULT '0',
  `nextrun` int unsigned NOT NULL DEFAULT '0',
  `weekday` tinyint(1) NOT NULL DEFAULT '0',
  `day` tinyint NOT NULL DEFAULT '0',
  `hour` tinyint NOT NULL DEFAULT '0',
  `minute` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`cronid`),
  KEY `nextrun` (`available`,`nextrun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_devicetoken` (
  `uid` mediumint unsigned NOT NULL,
  `token` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_district` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `level` tinyint unsigned NOT NULL DEFAULT '0',
  `usetype` tinyint unsigned NOT NULL DEFAULT '0',
  `upid` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `upid` (`upid`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_diy_data` (
  `targettplname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tpldirectory` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `primaltplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `diycontent` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`targettplname`,`tpldirectory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_domain` (
  `domain` char(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `domainroot` char(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` char(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`idtype`),
  KEY `domain` (`domain`,`domainroot`),
  KEY `idtype` (`idtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_editorblock` (
  `blockid` int unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `columns` tinyint(1) NOT NULL DEFAULT '0',
  `type` int NOT NULL DEFAULT '0',
  `sort` int NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `parser` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `style` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `i18n` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `plugin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filemtime` int unsigned NOT NULL DEFAULT '0',
  `copyright` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`blockid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_emaillog` (
  `logid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `emailtype` int NOT NULL DEFAULT '0',
  `svctype` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `verify` int NOT NULL DEFAULT '0',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`logid`),
  KEY `dateline` (`email`,`dateline`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_failedip` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `count` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`,`lastupdate`),
  KEY `lastupdate` (`lastupdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_failedlogin` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `count` tinyint unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_friendlink` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_grouppm` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `numbers` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_invite` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `code` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fuid` mediumint unsigned NOT NULL DEFAULT '0',
  `fusername` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `inviteip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `regdateline` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `orderid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `loginname` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `data` json NOT NULL,
  `operationuid` mediumint unsigned NOT NULL DEFAULT '0',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device` json NOT NULL,
  `record` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `dateline` (`dateline`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_magic` (
  `magicid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `credit` tinyint(1) NOT NULL DEFAULT '0',
  `price` mediumint unsigned NOT NULL DEFAULT '0',
  `num` smallint unsigned NOT NULL DEFAULT '0',
  `salevolume` smallint unsigned NOT NULL DEFAULT '0',
  `supplytype` tinyint(1) NOT NULL DEFAULT '0',
  `supplynum` smallint unsigned NOT NULL DEFAULT '0',
  `useperoid` tinyint(1) NOT NULL DEFAULT '0',
  `usenum` smallint unsigned NOT NULL DEFAULT '0',
  `weight` tinyint unsigned NOT NULL DEFAULT '1',
  `magicperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `useevent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`magicid`),
  UNIQUE KEY `identifier` (`identifier`),
  KEY `displayorder` (`available`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_magiclog` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `magicid` smallint unsigned NOT NULL DEFAULT '0',
  `action` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `amount` smallint unsigned NOT NULL DEFAULT '0',
  `credit` tinyint unsigned NOT NULL DEFAULT '0',
  `price` mediumint unsigned NOT NULL DEFAULT '0',
  `targetid` int unsigned NOT NULL DEFAULT '0',
  `idtype` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `targetuid` mediumint unsigned NOT NULL DEFAULT '0',
  KEY `uid` (`uid`,`dateline`),
  KEY `action` (`action`),
  KEY `targetuid` (`targetuid`,`dateline`),
  KEY `magicid` (`magicid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_mailcron` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `touid` mediumint unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sendtime` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `sendtime` (`sendtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_mailqueue` (
  `qid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint unsigned NOT NULL DEFAULT '0',
  `subject` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`qid`),
  KEY `mcid` (`cid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member` (
  `uid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `loginname` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobicc` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobile` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `emailstatus` tinyint(1) NOT NULL DEFAULT '0',
  `avatarstatus` tinyint(1) NOT NULL DEFAULT '0',
  `secmobilestatus` tinyint(1) NOT NULL DEFAULT '0',
  `adminid` tinyint(1) NOT NULL DEFAULT '0',
  `groupid` smallint unsigned NOT NULL DEFAULT '0',
  `groupexpiry` int unsigned NOT NULL DEFAULT '0',
  `extgroupids` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `regdate` int unsigned NOT NULL DEFAULT '0',
  `credits` int NOT NULL DEFAULT '0',
  `notifysound` tinyint(1) NOT NULL DEFAULT '0',
  `timeoffset` char(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `newpm` smallint unsigned NOT NULL DEFAULT '0',
  `newprompt` smallint unsigned NOT NULL DEFAULT '0',
  `accessmasks` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmincp` tinyint(1) NOT NULL DEFAULT '0',
  `onlyacceptfriendpm` tinyint(1) NOT NULL DEFAULT '0',
  `conisbind` tinyint(1) NOT NULL DEFAULT '0',
  `freeze` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `loginname` (`loginname`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`(40)),
  KEY `groupid` (`groupid`),
  KEY `conisbind` (`conisbind`),
  KEY `regdate` (`regdate`),
  KEY `secmobile` (`secmobile`,`secmobicc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_account` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `atype` tinyint(1) NOT NULL,
  `account` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `bindname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`,`atype`),
  UNIQUE KEY `atype` (`atype`,`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_action_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `action` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dateline` (`dateline`,`action`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_count` (
  `uid` mediumint unsigned NOT NULL,
  `extcredits1` int NOT NULL DEFAULT '0',
  `extcredits2` int NOT NULL DEFAULT '0',
  `extcredits3` int NOT NULL DEFAULT '0',
  `extcredits4` int NOT NULL DEFAULT '0',
  `extcredits5` int NOT NULL DEFAULT '0',
  `extcredits6` int NOT NULL DEFAULT '0',
  `extcredits7` int NOT NULL DEFAULT '0',
  `extcredits8` int NOT NULL DEFAULT '0',
  `friends` smallint unsigned NOT NULL DEFAULT '0',
  `posts` mediumint unsigned NOT NULL DEFAULT '0',
  `threads` mediumint unsigned NOT NULL DEFAULT '0',
  `digestposts` smallint unsigned NOT NULL DEFAULT '0',
  `doings` smallint unsigned NOT NULL DEFAULT '0',
  `blogs` smallint unsigned NOT NULL DEFAULT '0',
  `albums` smallint unsigned NOT NULL DEFAULT '0',
  `sharings` smallint unsigned NOT NULL DEFAULT '0',
  `attachsize` int unsigned NOT NULL DEFAULT '0',
  `views` mediumint unsigned NOT NULL DEFAULT '0',
  `oltime` smallint unsigned NOT NULL DEFAULT '0',
  `todayattachs` smallint unsigned NOT NULL DEFAULT '0',
  `todayattachsize` int unsigned NOT NULL DEFAULT '0',
  `feeds` mediumint unsigned NOT NULL DEFAULT '0',
  `follower` mediumint unsigned NOT NULL DEFAULT '0',
  `following` mediumint unsigned NOT NULL DEFAULT '0',
  `newfollower` mediumint unsigned NOT NULL DEFAULT '0',
  `blacklist` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `posts` (`posts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_crime` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `operatorid` mediumint unsigned NOT NULL DEFAULT '0',
  `operator` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` tinyint NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `uid` (`uid`,`action`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_field_forum` (
  `uid` mediumint unsigned NOT NULL,
  `publishfeed` tinyint NOT NULL DEFAULT '0',
  `customshow` tinyint unsigned NOT NULL DEFAULT '26',
  `customstatus` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `medals` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sightml` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `groupterms` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `authstr` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groups` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attentiongroup` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_field_home` (
  `uid` mediumint unsigned NOT NULL,
  `spacename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `spacedescription` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `domain` char(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `addsize` int unsigned NOT NULL DEFAULT '0',
  `addfriend` smallint unsigned NOT NULL DEFAULT '0',
  `allowasfriend` tinyint(1) NOT NULL DEFAULT '1',
  `allowasfollow` tinyint(1) NOT NULL DEFAULT '1',
  `menunum` smallint unsigned NOT NULL DEFAULT '0',
  `theme` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `spacecss` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `blockposition` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recentnote` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `spacenote` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `privacy` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `feedfriend` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `acceptemail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `magicgift` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stickblogs` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_forum_buylog` (
  `uid` mediumint unsigned NOT NULL,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `credits` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fid`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_grouppm` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `gpmid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`gpmid`),
  KEY `gpmid` (`gpmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_magic` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `magicid` smallint unsigned NOT NULL DEFAULT '0',
  `num` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`magicid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_medal` (
  `uid` mediumint unsigned NOT NULL,
  `medalid` smallint unsigned NOT NULL,
  PRIMARY KEY (`uid`,`medalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_newprompt` (
  `uid` mediumint unsigned NOT NULL,
  `data` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_profile` (
  `uid` mediumint unsigned NOT NULL,
  `realname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` tinyint(1) NOT NULL DEFAULT '0',
  `birthyear` smallint unsigned NOT NULL DEFAULT '0',
  `birthmonth` tinyint unsigned NOT NULL DEFAULT '0',
  `birthday` tinyint unsigned NOT NULL DEFAULT '0',
  `constellation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zodiac` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `idcardtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `idcard` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zipcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcountry` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthprovince` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthdist` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcommunity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecountry` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `resideprovince` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residedist` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecommunity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residesuite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `graduateschool` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `education` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `revenue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `affectivestatus` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lookingfor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bloodtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alipay` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icq` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `qq` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `yahoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `msn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `taobao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `site` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `interest` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field3` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field4` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field5` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field6` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field7` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field8` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` json NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_profile_history` (
  `hid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `realname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` tinyint(1) NOT NULL DEFAULT '0',
  `birthyear` smallint unsigned NOT NULL DEFAULT '0',
  `birthmonth` tinyint unsigned NOT NULL DEFAULT '0',
  `birthday` tinyint unsigned NOT NULL DEFAULT '0',
  `constellation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zodiac` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mobile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `idcardtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `idcard` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zipcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nationality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcountry` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthprovince` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthdist` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `birthcommunity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecountry` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `resideprovince` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residedist` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residecommunity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `residesuite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `graduateschool` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `education` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `revenue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `affectivestatus` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lookingfor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bloodtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `alipay` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icq` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `qq` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `yahoo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `msn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `taobao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `site` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bio` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `interest` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field1` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field2` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field3` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field4` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field5` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field6` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field7` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `field8` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` json NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`hid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_profile_setting` (
  `fieldid` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `needverify` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayorder` smallint unsigned NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `unchangeable` tinyint(1) NOT NULL DEFAULT '0',
  `showincard` tinyint(1) NOT NULL DEFAULT '0',
  `showinthread` tinyint(1) NOT NULL DEFAULT '0',
  `showinregister` tinyint(1) NOT NULL DEFAULT '0',
  `allowsearch` tinyint(1) NOT NULL DEFAULT '0',
  `formtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` smallint unsigned NOT NULL DEFAULT '0',
  `choices` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `validate` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `encrypt` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_security` (
  `securityid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fieldid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `oldvalue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `newvalue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`securityid`),
  KEY `uid` (`uid`,`fieldid`(40)),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_secwhite` (
  `uid` int NOT NULL,
  `dateline` int NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_stat_field` (
  `optionid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `fieldid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fieldvalue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `users` mediumint unsigned NOT NULL DEFAULT '0',
  `updatetime` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`optionid`),
  KEY `fieldid` (`fieldid`(40))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_status` (
  `uid` mediumint unsigned NOT NULL,
  `regip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `regport` smallint unsigned NOT NULL DEFAULT '0',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `lastvisit` int unsigned NOT NULL DEFAULT '0',
  `lastactivity` int unsigned NOT NULL DEFAULT '0',
  `lastpost` int unsigned NOT NULL DEFAULT '0',
  `lastsendmail` int unsigned NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `buyercredit` smallint NOT NULL DEFAULT '0',
  `sellercredit` smallint NOT NULL DEFAULT '0',
  `favtimes` mediumint unsigned NOT NULL DEFAULT '0',
  `sharetimes` mediumint unsigned NOT NULL DEFAULT '0',
  `profileprogress` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `lastactivity` (`lastactivity`,`invisible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_username_history` (
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_validate` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `submitdate` int unsigned NOT NULL DEFAULT '0',
  `moddate` int unsigned NOT NULL DEFAULT '0',
  `admin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `submittimes` tinyint unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_verify` (
  `uid` mediumint unsigned NOT NULL,
  `verify1` tinyint(1) NOT NULL DEFAULT '0',
  `verify2` tinyint(1) NOT NULL DEFAULT '0',
  `verify3` tinyint(1) NOT NULL DEFAULT '0',
  `verify4` tinyint(1) NOT NULL DEFAULT '0',
  `verify5` tinyint(1) NOT NULL DEFAULT '0',
  `verify6` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `verify1` (`verify1`),
  KEY `verify2` (`verify2`),
  KEY `verify3` (`verify3`),
  KEY `verify4` (`verify4`),
  KEY `verify5` (`verify5`),
  KEY `verify6` (`verify6`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_member_verify_info` (
  `vid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verifytype` tinyint(1) NOT NULL DEFAULT '0',
  `flag` tinyint(1) NOT NULL DEFAULT '0',
  `field` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`),
  KEY `verifytype` (`verifytype`,`flag`),
  KEY `uid` (`uid`,`verifytype`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_mytask` (
  `uid` mediumint unsigned NOT NULL,
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `taskid` smallint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `csc` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`taskid`),
  KEY `parter` (`taskid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_nav` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `parentid` smallint unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` tinyint(1) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL,
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `level` tinyint(1) NOT NULL DEFAULT '0',
  `subtype` tinyint(1) NOT NULL DEFAULT '0',
  `subcols` tinyint(1) NOT NULL DEFAULT '0',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suburl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `navtype` tinyint(1) NOT NULL DEFAULT '0',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `navtype` (`navtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_onlinetime` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `thismonth` smallint unsigned NOT NULL DEFAULT '0',
  `total` mediumint unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_optimizer` (
  `k` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_patch` (
  `serial` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `rule` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_payment_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `out_biz_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` int unsigned NOT NULL DEFAULT '0',
  `amount` int unsigned NOT NULL,
  `amount_fee` int unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expire_time` int unsigned NOT NULL,
  `status` tinyint(1) NOT NULL,
  `return_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_unicode_ci,
  `clientip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remoteport` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL,
  `trade_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_time` int unsigned DEFAULT NULL,
  `callback_status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_biz_no` (`out_biz_no`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_payment_refund` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `out_biz_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL,
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refund_time` int DEFAULT NULL,
  `clientip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remoteport` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_biz_no` (`out_biz_no`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_payment_transfer` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL,
  `out_biz_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL,
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trade_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trade_time` int unsigned DEFAULT NULL,
  `clientip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remoteport` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_biz_no` (`out_biz_no`),
  KEY `uid` (`uid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_plugin` (
  `pluginid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `adminid` tinyint unsigned NOT NULL DEFAULT '0',
  `name` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `identifier` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `datatables` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `directory` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `copyright` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modules` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`pluginid`),
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_pluginvar` (
  `pluginvarid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `pluginid` smallint unsigned NOT NULL DEFAULT '0',
  `displayorder` int NOT NULL DEFAULT '0',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `variable` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`pluginvarid`),
  KEY `pluginid` (`pluginid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_process` (
  `processid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` int DEFAULT NULL,
  `extra` int DEFAULT NULL,
  PRIMARY KEY (`processid`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_regip` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `count` smallint NOT NULL DEFAULT '0',
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_relatedlink` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extent` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_report` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `urlkey` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `num` smallint unsigned NOT NULL DEFAULT '1',
  `opuid` mediumint unsigned NOT NULL DEFAULT '0',
  `opname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `optime` int unsigned NOT NULL DEFAULT '0',
  `opresult` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `urlkey` (`urlkey`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_searchindex` (
  `searchid` int unsigned NOT NULL AUTO_INCREMENT,
  `srchmod` tinyint unsigned NOT NULL,
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `searchstring` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `useip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `threadsortid` smallint unsigned NOT NULL DEFAULT '0',
  `num` smallint unsigned NOT NULL DEFAULT '0',
  `ids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`searchid`),
  KEY `srchmod` (`srchmod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_seccheck` (
  `ssid` int NOT NULL AUTO_INCREMENT,
  `dateline` int NOT NULL,
  `code` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `succeed` tinyint(1) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  PRIMARY KEY (`ssid`),
  KEY `dateline` (`dateline`),
  KEY `succeed` (`succeed`),
  KEY `verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_secquestion` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint unsigned NOT NULL,
  `question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_session` (
  `sid` char(6) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groupid` smallint unsigned NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `action` tinyint unsigned NOT NULL DEFAULT '0',
  `lastactivity` int unsigned NOT NULL DEFAULT '0',
  `lastolupdate` int unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `sid` (`sid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_setting` (
  `skey` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `svalue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_smiley` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `typeid` smallint unsigned NOT NULL,
  `displayorder` tinyint(1) NOT NULL DEFAULT '0',
  `type` enum('smiley','stamp','stamplist') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smiley',
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_smsgw` (
  `smsgwid` int unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `type` int NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `sendrule` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`smsgwid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_smslog` (
  `smslogid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `smstype` int NOT NULL DEFAULT '0',
  `svctype` int NOT NULL DEFAULT '0',
  `smsgw` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `verify` int NOT NULL DEFAULT '0',
  `secmobicc` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobile` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`smslogid`),
  KEY `dateline` (`secmobicc`,`secmobile`,`dateline`),
  KEY `uid` (`uid`),
  KEY `status` (`status`,`dateline`,`uid`),
  KEY `dateline2` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_smslog_archive` (
  `smslogid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `smstype` int NOT NULL DEFAULT '0',
  `svctype` int NOT NULL DEFAULT '0',
  `smsgw` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `verify` int NOT NULL DEFAULT '0',
  `secmobicc` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobile` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`smslogid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_sphinxcounter` (
  `indexid` tinyint(1) NOT NULL,
  `maxid` int NOT NULL,
  PRIMARY KEY (`indexid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_stat` (
  `daytime` int unsigned NOT NULL DEFAULT '0',
  `login` int unsigned NOT NULL DEFAULT '0',
  `mobilelogin` int unsigned NOT NULL DEFAULT '0',
  `connectlogin` int unsigned NOT NULL DEFAULT '0',
  `register` int unsigned NOT NULL DEFAULT '0',
  `invite` int unsigned NOT NULL DEFAULT '0',
  `doing` int unsigned NOT NULL DEFAULT '0',
  `blog` int unsigned NOT NULL DEFAULT '0',
  `pic` int unsigned NOT NULL DEFAULT '0',
  `poll` int unsigned NOT NULL DEFAULT '0',
  `activity` int unsigned NOT NULL DEFAULT '0',
  `share` int unsigned NOT NULL DEFAULT '0',
  `thread` int unsigned NOT NULL DEFAULT '0',
  `docomment` int unsigned NOT NULL DEFAULT '0',
  `blogcomment` int unsigned NOT NULL DEFAULT '0',
  `piccomment` int unsigned NOT NULL DEFAULT '0',
  `sharecomment` int unsigned NOT NULL DEFAULT '0',
  `reward` int unsigned NOT NULL DEFAULT '0',
  `debate` int unsigned NOT NULL DEFAULT '0',
  `trade` int unsigned NOT NULL DEFAULT '0',
  `group` int unsigned NOT NULL DEFAULT '0',
  `groupjoin` int unsigned NOT NULL DEFAULT '0',
  `groupthread` int unsigned NOT NULL DEFAULT '0',
  `grouppost` int unsigned NOT NULL DEFAULT '0',
  `post` int unsigned NOT NULL DEFAULT '0',
  `wall` int unsigned NOT NULL DEFAULT '0',
  `poke` int unsigned NOT NULL DEFAULT '0',
  `click` int unsigned NOT NULL DEFAULT '0',
  `sendpm` int unsigned NOT NULL DEFAULT '0',
  `friend` int unsigned NOT NULL DEFAULT '0',
  `addfriend` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`daytime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_statuser` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `daytime` int unsigned NOT NULL DEFAULT '0',
  `type` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_style` (
  `styleid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `templateid` smallint unsigned NOT NULL DEFAULT '0',
  `extstyle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`styleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_stylevar` (
  `stylevarid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `styleid` smallint unsigned NOT NULL DEFAULT '0',
  `variable` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `substitute` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`stylevarid`),
  KEY `styleid` (`styleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_stylevar_extra` (
  `stylevarid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `styleid` smallint unsigned NOT NULL DEFAULT '0',
  `displayorder` int NOT NULL DEFAULT '0',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `variable` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`stylevarid`),
  KEY `styleid` (`styleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_syscache` (
  `cname` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ctype` tinyint unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`cname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_tag` (
  `tagid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `tagname` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `related_count` mediumint unsigned NOT NULL DEFAULT '0',
  `hot_score` float NOT NULL DEFAULT '0',
  `created_at` int unsigned NOT NULL DEFAULT '0',
  `updated_at` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tagid`),
  KEY `tagname` (`tagname`),
  KEY `status` (`status`,`tagid`),
  KEY `idx_hot_score` (`hot_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_tagitem` (
  `tagid` mediumint unsigned NOT NULL DEFAULT '0',
  `itemid` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` char(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `item` (`tagid`,`itemid`,`idtype`),
  KEY `idtype` (`idtype`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_task` (
  `taskid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `relatedtaskid` smallint unsigned NOT NULL DEFAULT '0',
  `exclusivetaskid` smallint unsigned NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `applicants` int unsigned NOT NULL DEFAULT '0',
  `achievers` int unsigned NOT NULL DEFAULT '0',
  `tasklimits` mediumint unsigned NOT NULL DEFAULT '0',
  `applyperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scriptname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `period` int unsigned NOT NULL DEFAULT '0',
  `periodtype` tinyint(1) NOT NULL DEFAULT '0',
  `reward` enum('credit','magic','medal','invite','group') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'credit',
  `prize` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bonus` int NOT NULL DEFAULT '0',
  `displayorder` smallint unsigned NOT NULL DEFAULT '0',
  `version` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_taskvar` (
  `taskvarid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `taskid` smallint unsigned NOT NULL DEFAULT '0',
  `sort` enum('apply','complete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'complete',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `variable` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`taskvarid`),
  KEY `taskid` (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_template` (
  `templateid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `directory` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `copyright` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_template_block` (
  `targettplname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tpldirectory` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`targettplname`,`tpldirectory`,`bid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_template_permission` (
  `targettplname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `allowmanage` tinyint(1) NOT NULL DEFAULT '0',
  `allowrecommend` tinyint(1) NOT NULL DEFAULT '0',
  `needverify` tinyint(1) NOT NULL DEFAULT '0',
  `inheritedtplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`targettplname`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_uin_black` (
  `uin` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uin`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_usergroup` (
  `groupid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `radminid` tinyint NOT NULL DEFAULT '0',
  `type` enum('system','special','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `system` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `grouptitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `creditshigher` int NOT NULL DEFAULT '0',
  `creditslower` int NOT NULL DEFAULT '0',
  `stars` tinyint NOT NULL DEFAULT '0',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `allowvisit` tinyint(1) NOT NULL DEFAULT '0',
  `allowsendpm` tinyint(1) NOT NULL DEFAULT '1',
  `allowinvite` tinyint(1) NOT NULL DEFAULT '0',
  `allowmailinvite` tinyint(1) NOT NULL DEFAULT '0',
  `allowfollow` tinyint(1) NOT NULL DEFAULT '0',
  `maxinvitenum` tinyint unsigned NOT NULL DEFAULT '0',
  `inviteprice` smallint unsigned NOT NULL DEFAULT '0',
  `maxinviteday` smallint unsigned NOT NULL DEFAULT '0',
  `upgroupid` smallint unsigned DEFAULT '0',
  `creditsformula` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`groupid`),
  KEY `creditsrange` (`creditshigher`,`creditslower`),
  KEY `upgroupid` (`upgroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_usergroup_field` (
  `groupid` smallint unsigned NOT NULL,
  `readaccess` tinyint unsigned NOT NULL DEFAULT '0',
  `allowpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowreply` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostpoll` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostreward` tinyint(1) NOT NULL DEFAULT '0',
  `allowposttrade` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostactivity` tinyint(1) NOT NULL DEFAULT '0',
  `allowdirectpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowgetattach` tinyint(1) NOT NULL DEFAULT '0',
  `allowgetimage` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostattach` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostimage` tinyint(1) NOT NULL DEFAULT '0',
  `allowvote` tinyint(1) NOT NULL DEFAULT '0',
  `allowsearch` tinyint(1) NOT NULL DEFAULT '0',
  `allowcstatus` tinyint(1) NOT NULL DEFAULT '0',
  `allowinvisible` tinyint(1) NOT NULL DEFAULT '0',
  `allowtransfer` tinyint(1) NOT NULL DEFAULT '0',
  `allowsetreadperm` tinyint(1) NOT NULL DEFAULT '0',
  `allowsetattachperm` tinyint(1) NOT NULL DEFAULT '0',
  `allowposttag` tinyint(1) NOT NULL DEFAULT '0',
  `allowhidecode` tinyint(1) NOT NULL DEFAULT '0',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowanonymous` tinyint(1) NOT NULL DEFAULT '0',
  `allowsigbbcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowsigimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowmagics` tinyint unsigned NOT NULL,
  `disableperiodctrl` tinyint(1) NOT NULL DEFAULT '0',
  `reasonpm` tinyint(1) NOT NULL DEFAULT '0',
  `maxprice` smallint unsigned NOT NULL DEFAULT '0',
  `maxsigsize` smallint unsigned NOT NULL DEFAULT '0',
  `maxattachsize` int unsigned NOT NULL DEFAULT '0',
  `maxsizeperday` int unsigned NOT NULL DEFAULT '0',
  `maxthreadsperhour` tinyint unsigned NOT NULL DEFAULT '0',
  `maxpostsperhour` tinyint unsigned NOT NULL DEFAULT '0',
  `attachextensions` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `raterange` char(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `loginreward` char(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mintradeprice` smallint unsigned NOT NULL DEFAULT '1',
  `maxtradeprice` smallint unsigned NOT NULL DEFAULT '0',
  `minrewardprice` smallint unsigned NOT NULL DEFAULT '1',
  `maxrewardprice` smallint unsigned NOT NULL DEFAULT '0',
  `magicsdiscount` tinyint(1) NOT NULL,
  `maxmagicsweight` smallint unsigned NOT NULL,
  `allowpostdebate` tinyint(1) NOT NULL DEFAULT '0',
  `tradestick` tinyint unsigned NOT NULL,
  `exempt` tinyint unsigned NOT NULL,
  `maxattachnum` smallint NOT NULL DEFAULT '0',
  `allowposturl` tinyint(1) NOT NULL DEFAULT '3',
  `allowrecommend` tinyint unsigned NOT NULL DEFAULT '1',
  `allowpostrushreply` tinyint(1) NOT NULL DEFAULT '0',
  `maxfriendnum` smallint unsigned NOT NULL DEFAULT '0',
  `maxspacesize` int unsigned NOT NULL DEFAULT '0',
  `allowcomment` tinyint(1) NOT NULL DEFAULT '0',
  `allowcommentmod` tinyint(1) NOT NULL DEFAULT '0',
  `allowcommentarticle` smallint NOT NULL DEFAULT '0',
  `allowcommentarticlemod` tinyint(1) NOT NULL DEFAULT '0',
  `searchinterval` smallint unsigned NOT NULL DEFAULT '0',
  `searchignore` tinyint(1) NOT NULL DEFAULT '0',
  `allowblog` tinyint(1) NOT NULL DEFAULT '0',
  `allowdoing` tinyint(1) NOT NULL DEFAULT '0',
  `allowupload` tinyint(1) NOT NULL DEFAULT '0',
  `allowshare` tinyint(1) NOT NULL DEFAULT '0',
  `allowblogmod` tinyint(1) NOT NULL DEFAULT '0',
  `allowdoingmod` tinyint(1) NOT NULL DEFAULT '0',
  `allowuploadmod` tinyint(1) NOT NULL DEFAULT '0',
  `allowsharemod` tinyint(1) NOT NULL DEFAULT '0',
  `allowcss` tinyint(1) NOT NULL DEFAULT '0',
  `allowpoke` tinyint(1) NOT NULL DEFAULT '0',
  `allowfriend` tinyint(1) NOT NULL DEFAULT '0',
  `allowclick` tinyint(1) NOT NULL DEFAULT '0',
  `allowmagic` tinyint(1) NOT NULL DEFAULT '0',
  `allowstat` tinyint(1) NOT NULL DEFAULT '0',
  `allowstatdata` tinyint(1) NOT NULL DEFAULT '0',
  `magicdiscount` tinyint(1) NOT NULL DEFAULT '0',
  `domainlength` smallint unsigned NOT NULL DEFAULT '0',
  `seccode` tinyint(1) NOT NULL DEFAULT '1',
  `disablepostctrl` tinyint(1) NOT NULL DEFAULT '0',
  `allowbuildgroup` tinyint unsigned NOT NULL DEFAULT '0',
  `allowgroupdirectpost` tinyint unsigned NOT NULL DEFAULT '0',
  `allowgroupposturl` tinyint unsigned NOT NULL DEFAULT '0',
  `edittimelimit` int unsigned NOT NULL DEFAULT '0',
  `allowpostarticle` tinyint(1) NOT NULL DEFAULT '0',
  `allowdownlocalimg` tinyint(1) NOT NULL DEFAULT '0',
  `allowdownremoteimg` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostarticlemod` tinyint(1) NOT NULL DEFAULT '0',
  `allowspacediyhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowspacediybbcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowspacediyimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowcommentpost` tinyint(1) NOT NULL DEFAULT '2',
  `allowcommentitem` tinyint(1) NOT NULL DEFAULT '0',
  `allowcommentreply` tinyint(1) NOT NULL DEFAULT '0',
  `allowreplycredit` tinyint(1) NOT NULL DEFAULT '0',
  `ignorecensor` tinyint(1) NOT NULL DEFAULT '0',
  `allowsendallpm` tinyint(1) NOT NULL DEFAULT '0',
  `allowsendpmmaxnum` smallint unsigned NOT NULL DEFAULT '0',
  `maximagesize` mediumint unsigned NOT NULL DEFAULT '0',
  `allowmediacode` tinyint(1) NOT NULL DEFAULT '0',
  `allowbegincode` tinyint(1) NOT NULL DEFAULT '0',
  `allowat` smallint unsigned NOT NULL DEFAULT '0',
  `allowsave` tinyint(1) NOT NULL DEFAULT '1',
  `allowsavereply` tinyint(1) NOT NULL DEFAULT '1',
  `allowsavenum` int unsigned NOT NULL DEFAULT '0',
  `allowsetpublishdate` tinyint(1) NOT NULL DEFAULT '0',
  `allowfollowcollection` tinyint unsigned NOT NULL DEFAULT '0',
  `allowcommentcollection` tinyint(1) NOT NULL DEFAULT '0',
  `allowcreatecollection` smallint unsigned NOT NULL DEFAULT '0',
  `forcesecques` tinyint(1) NOT NULL DEFAULT '0',
  `forcelogin` tinyint unsigned NOT NULL DEFAULT '0',
  `closead` tinyint(1) NOT NULL DEFAULT '0',
  `buildgroupcredits` smallint unsigned NOT NULL DEFAULT '0',
  `allowimgcontent` tinyint(1) NOT NULL DEFAULT '0',
  `allowavatarupload` tinyint(1) NOT NULL DEFAULT '0',
  `allowviewprofile` tinyint(1) NOT NULL DEFAULT '0',
  `fields` json DEFAULT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_visit` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `view` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`),
  KEY `ip` (`ip`,`view`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_word` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `admin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` smallint NOT NULL DEFAULT '1',
  `find` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `replacement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extra` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_common_word_type` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `typename` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_connect_disktask` (
  `taskid` int unsigned NOT NULL AUTO_INCREMENT,
  `aid` int unsigned NOT NULL DEFAULT '0',
  `uid` int unsigned NOT NULL DEFAULT '0',
  `openid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verifycode` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `downloadtime` int unsigned NOT NULL DEFAULT '0',
  `extra` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`taskid`),
  KEY `openid` (`openid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_connect_feedlog` (
  `flid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `publishtimes` mediumint unsigned NOT NULL DEFAULT '0',
  `lastpublished` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`flid`),
  UNIQUE KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_album` (
  `albumid` int unsigned NOT NULL AUTO_INCREMENT,
  `seriesid` int unsigned NOT NULL DEFAULT '0',
  `catid` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `video` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pagecount` int unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`albumid`),
  KEY `seriesid` (`seriesid`,`available`,`displayorder`),
  KEY `catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_asset` (
  `assetid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT '0',
  `productid` int unsigned NOT NULL DEFAULT '0',
  `scope` varchar(20) NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `kind` varchar(10) NOT NULL,
  `provider` varchar(10) NOT NULL,
  `objectkey` varchar(500) NOT NULL,
  `bucket` varchar(100) NOT NULL,
  `region` varchar(60) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `size` bigint unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created` int unsigned NOT NULL,
  `updated` int unsigned NOT NULL,
  PRIMARY KEY (`assetid`),
  KEY `product` (`productid`,`status`),
  KEY `status` (`status`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pre_flipbook_category` (
  `catid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `parentid` int unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`catid`),
  KEY `parentid` (`parentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_favorite` (
  `uid` int unsigned NOT NULL DEFAULT '0',
  `targettype` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'album',
  `targetid` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`targettype`,`targetid`),
  KEY `target` (`targettype`,`targetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_history` (
  `historyid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` int unsigned NOT NULL DEFAULT '0',
  `targettype` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'album',
  `targetid` int unsigned NOT NULL DEFAULT '0',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'read',
  `progress` text COLLATE utf8mb4_unicode_ci,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`historyid`),
  KEY `uid` (`uid`,`action`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_identity` (
  `identity` char(64) NOT NULL,
  `uid` int unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`identity`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pre_flipbook_micropage` (
  `pageid` int unsigned NOT NULL AUTO_INCREMENT,
  `pkey` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `config` longtext COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `version` int unsigned NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageid`),
  UNIQUE KEY `pkey` (`pkey`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_page` (
  `pageid` int unsigned NOT NULL AUTO_INCREMENT,
  `albumid` int unsigned NOT NULL DEFAULT '0',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `eyebrow` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `imagecfg` text COLLATE utf8mb4_unicode_ci,
  `displayorder` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageid`),
  KEY `albumid` (`albumid`,`available`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_page_product` (
  `pageid` int unsigned NOT NULL DEFAULT '0',
  `productid` int unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageid`,`productid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_preference` (
  `uid` int unsigned NOT NULL DEFAULT '0',
  `prefs` text COLLATE utf8mb4_unicode_ci,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_product` (
  `productid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `video` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `priceshow` tinyint(1) NOT NULL DEFAULT '0',
  `pricevalue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `priceorigin` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CNY',
  `spec` text COLLATE utf8mb4_unicode_ci,
  `displayorder` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `supplierid` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`productid`),
  KEY `available` (`available`,`displayorder`),
  KEY `code` (`code`),
  KEY `supplierid` (`supplierid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_product_media` (
  `mediaid` int unsigned NOT NULL AUTO_INCREMENT,
  `productid` int unsigned NOT NULL DEFAULT '0',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detail',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`mediaid`),
  KEY `productid` (`productid`,`type`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_release` (
  `releaseid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`releaseid`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_series` (
  `seriesid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`seriesid`),
  KEY `available` (`available`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_setting` (
  `skey` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `svalue` text COLLATE utf8mb4_unicode_ci,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sku` (
  `skuid` int unsigned NOT NULL AUTO_INCREMENT,
  `productid` int unsigned NOT NULL,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `color` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `images` longtext COLLATE utf8mb4_unicode_ci,
  `displayorder` int NOT NULL DEFAULT '0',
  `available` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`skuid`),
  UNIQUE KEY `code` (`code`),
  KEY `productid` (`productid`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_supplier` (
  `supplierid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` text COLLATE utf8mb4_unicode_ci,
  `displayorder` int NOT NULL DEFAULT '0',
  `available` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`supplierid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_draft` (
  `draftid` int unsigned NOT NULL AUTO_INCREMENT,
  `dkey` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uid` int unsigned NOT NULL,
  `origin` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceid` int unsigned NOT NULL DEFAULT '0',
  `remoteid` int unsigned NOT NULL DEFAULT '0',
  `targetid` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payload` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `revision` int unsigned NOT NULL DEFAULT '1',
  `editid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sequenceid` int unsigned NOT NULL DEFAULT '0',
  `created` int unsigned NOT NULL,
  `updated` int unsigned NOT NULL,
  PRIMARY KEY (`draftid`),
  UNIQUE KEY `dkey` (`dkey`),
  KEY `owner` (`uid`,`origin`,`kind`,`updated`),
  KEY `target` (`kind`,`targetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_job` (
  `jobid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceid` int unsigned NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cursorid` int unsigned NOT NULL DEFAULT '0',
  `upperid` int unsigned NOT NULL DEFAULT '0',
  `total` int unsigned NOT NULL DEFAULT '0',
  `processed` int unsigned NOT NULL DEFAULT '0',
  `added` int unsigned NOT NULL DEFAULT '0',
  `changed` int unsigned NOT NULL DEFAULT '0',
  `skipped` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created` int unsigned NOT NULL,
  `updated` int unsigned NOT NULL,
  PRIMARY KEY (`jobid`),
  KEY `sourceid` (`sourceid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_key` (
  `clientid` int unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keyhash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint NOT NULL DEFAULT '1',
  `note` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ips` text COLLATE utf8mb4_unicode_ci,
  `created` int unsigned NOT NULL,
  `lastused` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`clientid`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_log` (
  `logid` int unsigned NOT NULL AUTO_INCREMENT,
  `sourceid` int unsigned NOT NULL DEFAULT '0',
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`logid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_map` (
  `sourceid` int unsigned NOT NULL,
  `kind` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remoteid` int unsigned NOT NULL,
  `localid` int unsigned NOT NULL,
  `checksum` char(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`sourceid`,`kind`,`remoteid`),
  KEY `localid` (`kind`,`localid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_setting` (
  `skey` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `svalue` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_flipbook_sync_source` (
  `sourceid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keycipher` text COLLATE utf8mb4_unicode_ci,
  `enabled` tinyint NOT NULL DEFAULT '1',
  `instanceid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastsync` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sourceid`),
  UNIQUE KEY `endpoint` (`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_access` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `allowview` tinyint(1) NOT NULL DEFAULT '0',
  `allowpost` tinyint(1) NOT NULL DEFAULT '0',
  `allowreply` tinyint(1) NOT NULL DEFAULT '0',
  `allowgetattach` tinyint(1) NOT NULL DEFAULT '0',
  `allowgetimage` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostattach` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostimage` tinyint(1) NOT NULL DEFAULT '0',
  `adminuser` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fid`),
  KEY `listorder` (`fid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_activity` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `aid` int unsigned NOT NULL DEFAULT '0',
  `cost` mediumint unsigned NOT NULL DEFAULT '0',
  `starttimefrom` int unsigned NOT NULL DEFAULT '0',
  `starttimeto` int unsigned NOT NULL DEFAULT '0',
  `place` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` tinyint(1) NOT NULL DEFAULT '0',
  `number` smallint unsigned NOT NULL DEFAULT '0',
  `applynumber` smallint unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `ufield` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `uid` (`uid`,`starttimefrom`),
  KEY `starttimefrom` (`starttimefrom`),
  KEY `expiration` (`expiration`),
  KEY `applynumber` (`applynumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_activityapply` (
  `applyid` int unsigned NOT NULL AUTO_INCREMENT,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `payment` mediumint NOT NULL DEFAULT '0',
  `ufielddata` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`applyid`),
  KEY `uid` (`uid`),
  KEY `tid` (`tid`),
  KEY `dateline` (`tid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_announcement` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `groups` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timespan` (`starttime`,`endtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment` (
  `aid` int unsigned NOT NULL AUTO_INCREMENT,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `tableid` tinyint unsigned NOT NULL DEFAULT '0',
  `downloads` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_0` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_1` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_2` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_3` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_4` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_5` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_6` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_7` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_8` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_9` (
  `aid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `picid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `tid` (`tid`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_exif` (
  `aid` int unsigned NOT NULL,
  `exif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachment_unused` (
  `aid` int unsigned NOT NULL,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_attachtype` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `extension` char(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `maxsize` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_bbcode` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `tag` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `replacement` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `example` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `explanation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `params` tinyint unsigned NOT NULL DEFAULT '1',
  `prompt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `nest` tinyint unsigned NOT NULL DEFAULT '1',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `perm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collection` (
  `ctid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `follownum` mediumint unsigned NOT NULL DEFAULT '0',
  `threadnum` mediumint unsigned NOT NULL DEFAULT '0',
  `commentnum` mediumint unsigned NOT NULL DEFAULT '0',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `rate` float NOT NULL DEFAULT '0',
  `ratenum` mediumint unsigned NOT NULL DEFAULT '0',
  `lastpost` mediumint unsigned NOT NULL DEFAULT '0',
  `lastsubject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastposttime` int unsigned NOT NULL DEFAULT '0',
  `lastposter` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastvisit` int unsigned NOT NULL DEFAULT '0',
  `keyword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cover` tinyint unsigned NOT NULL DEFAULT '0',
  `icon` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctid`),
  KEY `dateline` (`dateline`),
  KEY `hotcollection` (`threadnum`,`lastupdate`),
  KEY `follownum` (`follownum`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectioncomment` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `ctid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `useip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `rate` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `ctid` (`ctid`,`dateline`),
  KEY `userrate` (`ctid`,`uid`,`rate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectionfollow` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ctid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `lastvisit` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`ctid`),
  KEY `ctid` (`ctid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectioninvite` (
  `ctid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctid`,`uid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectionrelated` (
  `tid` mediumint unsigned NOT NULL DEFAULT '0',
  `collection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectionteamworker` (
  `ctid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastvisit` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_collectionthread` (
  `ctid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ctid`,`tid`),
  KEY `ctid` (`ctid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_creditslog` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fromto` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sendcredits` tinyint(1) NOT NULL DEFAULT '0',
  `receivecredits` tinyint(1) NOT NULL DEFAULT '0',
  `send` int unsigned NOT NULL DEFAULT '0',
  `receive` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `operation` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_debate` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `starttime` int unsigned NOT NULL DEFAULT '0',
  `endtime` int unsigned NOT NULL DEFAULT '0',
  `affirmdebaters` mediumint unsigned NOT NULL DEFAULT '0',
  `negadebaters` mediumint unsigned NOT NULL DEFAULT '0',
  `affirmvotes` mediumint unsigned NOT NULL DEFAULT '0',
  `negavotes` mediumint unsigned NOT NULL DEFAULT '0',
  `umpire` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `winner` tinyint(1) NOT NULL DEFAULT '0',
  `bestdebater` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `affirmpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `negapoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `umpirepoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `affirmvoterids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `negavoterids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `affirmreplies` mediumint unsigned NOT NULL,
  `negareplies` mediumint unsigned NOT NULL,
  PRIMARY KEY (`tid`),
  KEY `uid` (`uid`,`starttime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_debatepost` (
  `pid` int unsigned NOT NULL DEFAULT '0',
  `stand` tinyint(1) NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `voters` mediumint unsigned NOT NULL DEFAULT '0',
  `voterids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`pid`),
  KEY `pid` (`pid`,`stand`),
  KEY `tid` (`tid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_faq` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `fpid` smallint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `identifier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keyword` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `displayplay` (`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_filter_post` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `postlength` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`,`pid`),
  KEY `tid` (`tid`,`postlength`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_forum` (
  `fid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `fup` mediumint unsigned NOT NULL DEFAULT '0',
  `type` enum('group','forum','sub') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'forum',
  `name` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `styleid` smallint unsigned NOT NULL DEFAULT '0',
  `threads` mediumint unsigned NOT NULL DEFAULT '0',
  `posts` mediumint unsigned NOT NULL DEFAULT '0',
  `todayposts` mediumint unsigned NOT NULL DEFAULT '0',
  `yesterdayposts` mediumint unsigned NOT NULL DEFAULT '0',
  `rank` smallint unsigned NOT NULL DEFAULT '0',
  `oldrank` smallint unsigned NOT NULL DEFAULT '0',
  `lastpost` char(110) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `domain` char(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `allowsmilies` tinyint(1) NOT NULL DEFAULT '0',
  `allowhtml` tinyint(1) NOT NULL DEFAULT '0',
  `allowbbcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowimgcode` tinyint(1) NOT NULL DEFAULT '0',
  `allowmediacode` tinyint(1) NOT NULL DEFAULT '0',
  `allowanonymous` tinyint(1) NOT NULL DEFAULT '0',
  `allowpostspecial` smallint unsigned NOT NULL DEFAULT '0',
  `allowspecialonly` tinyint(1) NOT NULL DEFAULT '0',
  `allowappend` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditrules` tinyint(1) NOT NULL DEFAULT '0',
  `allowfeed` tinyint(1) NOT NULL DEFAULT '1',
  `allowside` tinyint(1) NOT NULL DEFAULT '0',
  `recyclebin` tinyint(1) NOT NULL DEFAULT '0',
  `modnewposts` tinyint(1) NOT NULL DEFAULT '0',
  `jammer` tinyint(1) NOT NULL DEFAULT '0',
  `disablewatermark` tinyint(1) NOT NULL DEFAULT '0',
  `inheritedmod` tinyint(1) NOT NULL DEFAULT '0',
  `autoclose` smallint NOT NULL DEFAULT '0',
  `forumcolumns` tinyint unsigned NOT NULL DEFAULT '0',
  `catforumcolumns` tinyint unsigned NOT NULL DEFAULT '0',
  `threadcaches` tinyint(1) NOT NULL DEFAULT '0',
  `alloweditpost` tinyint(1) NOT NULL DEFAULT '1',
  `simple` smallint NOT NULL DEFAULT '0',
  `modworks` tinyint(1) NOT NULL DEFAULT '0',
  `allowglobalstick` tinyint(1) NOT NULL DEFAULT '1',
  `level` smallint NOT NULL DEFAULT '0',
  `commoncredits` int unsigned NOT NULL DEFAULT '0',
  `archive` tinyint(1) NOT NULL DEFAULT '0',
  `recommend` smallint unsigned NOT NULL DEFAULT '0',
  `favtimes` mediumint unsigned NOT NULL DEFAULT '0',
  `sharetimes` mediumint unsigned NOT NULL DEFAULT '0',
  `disablethumb` tinyint(1) NOT NULL DEFAULT '0',
  `disablecollect` tinyint(1) NOT NULL DEFAULT '0',
  `editormode` tinyint(1) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`fid`),
  KEY `forum` (`status`,`type`,`displayorder`),
  KEY `fup_type` (`fup`,`type`,`displayorder`),
  KEY `fup` (`fup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_forum_threadtable` (
  `fid` smallint unsigned NOT NULL,
  `threadtableid` smallint unsigned NOT NULL,
  `threads` int unsigned NOT NULL DEFAULT '0',
  `posts` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`fid`,`threadtableid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_forumfield` (
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `redirect` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attachextensions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `creditspolicy` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `formulaperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `moderators` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rules` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `threadtypes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `threadsorts` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `viewperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `postperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `replyperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `getattachperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `postattachperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `postimageperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `spviewperm` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `seotitle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `seodescription` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supe_pushsetting` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `modrecommend` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `threadplugin` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `replybg` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `jointype` tinyint(1) NOT NULL DEFAULT '0',
  `gviewperm` tinyint(1) NOT NULL DEFAULT '0',
  `membernum` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `activity` int unsigned NOT NULL DEFAULT '0',
  `founderuid` mediumint unsigned NOT NULL DEFAULT '0',
  `foundername` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `banner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groupnum` smallint unsigned NOT NULL DEFAULT '0',
  `commentitem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `relatedgroup` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `picstyle` tinyint(1) NOT NULL DEFAULT '0',
  `widthauto` tinyint(1) NOT NULL DEFAULT '0',
  `noantitheft` tinyint(1) NOT NULL DEFAULT '0',
  `noforumhidewater` tinyint(1) NOT NULL DEFAULT '0',
  `noforumrecommend` tinyint(1) NOT NULL DEFAULT '0',
  `livetid` int unsigned NOT NULL DEFAULT '0',
  `price` mediumint unsigned NOT NULL DEFAULT '0',
  `fields` json DEFAULT NULL,
  PRIMARY KEY (`fid`),
  KEY `membernum` (`membernum`),
  KEY `dateline` (`dateline`),
  KEY `lastupdate` (`lastupdate`),
  KEY `activity` (`activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_forumrecommend` (
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL,
  `typeid` smallint NOT NULL,
  `displayorder` tinyint(1) NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorid` mediumint NOT NULL,
  `moderatorid` mediumint NOT NULL,
  `expiration` int unsigned NOT NULL,
  `position` tinyint(1) NOT NULL DEFAULT '0',
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `aid` int unsigned NOT NULL DEFAULT '0',
  `filename` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`tid`),
  KEY `displayorder` (`fid`,`displayorder`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_groupcreditslog` (
  `fid` mediumint unsigned NOT NULL,
  `uid` mediumint unsigned NOT NULL,
  `logdate` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`fid`,`uid`,`logdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_groupfield` (
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY `types` (`fid`,`type`),
  KEY `fid` (`fid`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_groupinvite` (
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `inviteuid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `ids` (`fid`,`inviteuid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_grouplevel` (
  `levelid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('special','default') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `leveltitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `creditshigher` int NOT NULL DEFAULT '0',
  `creditslower` int NOT NULL DEFAULT '0',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `creditspolicy` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `postpolicy` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialswitch` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`levelid`),
  KEY `creditsrange` (`creditshigher`,`creditslower`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_groupuser` (
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL DEFAULT '0',
  `threads` mediumint unsigned NOT NULL DEFAULT '0',
  `replies` mediumint unsigned NOT NULL DEFAULT '0',
  `joindateline` int unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `privacy` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`fid`,`uid`),
  KEY `uid_lastupdate` (`uid`,`lastupdate`),
  KEY `userlist` (`fid`,`level`,`lastupdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_hotreply_member` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `attitude` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_hotreply_number` (
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `support` smallint unsigned NOT NULL DEFAULT '0',
  `against` smallint unsigned NOT NULL DEFAULT '0',
  `total` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  KEY `tid` (`tid`,`total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_imagetype` (
  `typeid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('smiley','icon','avatar') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smiley',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `directory` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_medal` (
  `medalid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` smallint unsigned NOT NULL DEFAULT '0',
  `permission` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit` tinyint unsigned NOT NULL DEFAULT '0',
  `price` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`medalid`),
  KEY `displayorder` (`displayorder`),
  KEY `available` (`available`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_medallog` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `medalid` smallint unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `status` (`status`,`expiration`),
  KEY `uid` (`uid`,`medalid`,`type`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_memberrecommend` (
  `tid` int unsigned NOT NULL,
  `recommenduid` mediumint unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  KEY `tid` (`tid`),
  KEY `uid` (`recommenduid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_moderator` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `inherited` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_modwork` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `modaction` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` date NOT NULL DEFAULT '2006-01-01',
  `count` smallint unsigned NOT NULL DEFAULT '0',
  `posts` smallint unsigned NOT NULL DEFAULT '0',
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_newthread` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `fid` (`fid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_onlinelist` (
  `groupid` smallint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `title` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_order` (
  `orderid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `buyer` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `amount` int unsigned NOT NULL DEFAULT '0',
  `price` float(7,2) unsigned NOT NULL DEFAULT '0.00',
  `submitdate` int unsigned NOT NULL DEFAULT '0',
  `confirmdate` int unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `orderid` (`orderid`),
  KEY `submitdate` (`submitdate`),
  KEY `uid` (`uid`,`submitdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_poll` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `overt` tinyint(1) NOT NULL DEFAULT '0',
  `multiple` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `maxchoices` tinyint unsigned NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `pollpreview` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `voters` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_polloption` (
  `polloptionid` int unsigned NOT NULL AUTO_INCREMENT,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `votes` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `polloption` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `voterids` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`polloptionid`),
  KEY `tid` (`tid`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_polloption_image` (
  `aid` int unsigned NOT NULL AUTO_INCREMENT,
  `poid` int unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `poid` (`poid`),
  KEY `tid` (`tid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_pollvoter` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `options` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  KEY `tid` (`tid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_post` (
  `pid` int unsigned NOT NULL,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `repid` int unsigned NOT NULL DEFAULT '0',
  `first` tinyint(1) NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `original` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `updateuid` mediumint unsigned NOT NULL DEFAULT '0',
  `premsg` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` json DEFAULT NULL,
  `source` json DEFAULT NULL,
  `useip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `invisible` tinyint(1) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `usesig` tinyint(1) NOT NULL DEFAULT '0',
  `htmlon` tinyint(1) NOT NULL DEFAULT '0',
  `bbcodeoff` tinyint(1) NOT NULL DEFAULT '0',
  `smileyoff` tinyint(1) NOT NULL DEFAULT '0',
  `parseurloff` tinyint(1) NOT NULL DEFAULT '0',
  `attachment` tinyint(1) NOT NULL DEFAULT '0',
  `rate` smallint NOT NULL DEFAULT '0',
  `ratetimes` tinyint unsigned NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comment` tinyint(1) NOT NULL DEFAULT '0',
  `replycredit` int NOT NULL DEFAULT '0',
  `position` int unsigned NOT NULL,
  `bestanswer` tinyint(1) NOT NULL,
  PRIMARY KEY (`tid`,`position`),
  UNIQUE KEY `pid` (`pid`),
  KEY `fid` (`fid`),
  KEY `authorid` (`authorid`,`invisible`),
  KEY `dateline` (`dateline`),
  KEY `invisible` (`invisible`),
  KEY `displayorder` (`tid`,`invisible`,`dateline`),
  KEY `first` (`tid`,`first`),
  KEY `bestanswer` (`bestanswer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_post_history` (
  `id` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_post_location` (
  `pid` int unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned DEFAULT '0',
  `uid` mediumint unsigned DEFAULT '0',
  `mapx` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapy` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`pid`),
  KEY `tid` (`tid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_post_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_post_tableid` (
  `pid` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_postcache` (
  `pid` int unsigned NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_postcomment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authorid` mediumint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `score` tinyint(1) NOT NULL DEFAULT '0',
  `useip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `rpid` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  KEY `authorid` (`authorid`),
  KEY `score` (`score`),
  KEY `rpid` (`rpid`),
  KEY `pid` (`pid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_poststick` (
  `tid` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `position` int unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`tid`,`pid`),
  KEY `dateline` (`tid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_promotion` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_ratelog` (
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extcredits` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `score` smallint NOT NULL DEFAULT '0',
  `reason` char(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  KEY `pid` (`pid`,`dateline`),
  KEY `dateline` (`dateline`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_relatedthread` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `type` enum('general','trade') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `expiration` int NOT NULL DEFAULT '0',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `relatedthreads` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_replycredit` (
  `tid` int unsigned NOT NULL,
  `extcredits` int unsigned NOT NULL DEFAULT '0',
  `extcreditstype` tinyint(1) NOT NULL DEFAULT '0',
  `times` int unsigned NOT NULL DEFAULT '0',
  `membertimes` int unsigned NOT NULL DEFAULT '0',
  `random` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_rsscache` (
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `forum` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `guidetype` char(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY `tid` (`tid`),
  KEY `fid` (`fid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_sofa` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `ftid` (`fid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_spacecache` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `variable` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`variable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_statlog` (
  `logdate` date NOT NULL,
  `fid` mediumint unsigned NOT NULL,
  `type` smallint unsigned NOT NULL DEFAULT '0',
  `value` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`logdate`,`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_thread` (
  `tid` int unsigned NOT NULL AUTO_INCREMENT,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `posttableid` smallint unsigned NOT NULL DEFAULT '0',
  `typeid` smallint unsigned NOT NULL DEFAULT '0',
  `sortid` smallint unsigned NOT NULL DEFAULT '0',
  `readperm` tinyint unsigned NOT NULL DEFAULT '0',
  `price` smallint NOT NULL DEFAULT '0',
  `author` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `lastpost` int unsigned NOT NULL DEFAULT '0',
  `lastposter` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `views` int unsigned NOT NULL DEFAULT '0',
  `replies` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint(1) NOT NULL DEFAULT '0',
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `digest` tinyint(1) NOT NULL DEFAULT '0',
  `rate` tinyint(1) NOT NULL DEFAULT '0',
  `special` tinyint(1) NOT NULL DEFAULT '0',
  `attachment` tinyint(1) NOT NULL DEFAULT '0',
  `moderated` tinyint(1) NOT NULL DEFAULT '0',
  `closed` mediumint unsigned NOT NULL DEFAULT '0',
  `stickreply` tinyint(1) NOT NULL DEFAULT '0',
  `recommends` smallint NOT NULL DEFAULT '0',
  `recommend_add` smallint NOT NULL DEFAULT '0',
  `recommend_sub` smallint NOT NULL DEFAULT '0',
  `heats` int unsigned NOT NULL DEFAULT '0',
  `status` smallint unsigned NOT NULL DEFAULT '0',
  `isgroup` tinyint(1) NOT NULL DEFAULT '0',
  `favtimes` mediumint NOT NULL DEFAULT '0',
  `sharetimes` mediumint NOT NULL DEFAULT '0',
  `stamp` tinyint NOT NULL DEFAULT '-1',
  `icon` tinyint NOT NULL DEFAULT '-1',
  `pushedaid` mediumint NOT NULL DEFAULT '0',
  `cover` smallint NOT NULL DEFAULT '0',
  `replycredit` int NOT NULL DEFAULT '0',
  `relatebytag` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `maxposition` int unsigned NOT NULL DEFAULT '0',
  `bgcolor` char(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comments` int unsigned NOT NULL DEFAULT '0',
  `hidden` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`),
  KEY `digest` (`digest`),
  KEY `sortid` (`sortid`),
  KEY `displayorder` (`fid`,`displayorder`,`lastpost`),
  KEY `typeid` (`fid`,`typeid`,`displayorder`,`lastpost`),
  KEY `recommends` (`recommends`),
  KEY `heats` (`heats`),
  KEY `authorid` (`authorid`),
  KEY `isgroup` (`isgroup`,`lastpost`),
  KEY `special` (`special`),
  KEY `displayorder_dateline` (`fid`,`displayorder`,`dateline`),
  KEY `displayorder_replies` (`fid`,`displayorder`,`replies`),
  KEY `displayorder_views` (`fid`,`displayorder`,`views`),
  KEY `displayorder_recommends` (`fid`,`displayorder`,`recommends`),
  KEY `displayorder_heats` (`fid`,`displayorder`,`heats`),
  KEY `typeid_dateline` (`fid`,`typeid`,`displayorder`,`dateline`),
  KEY `typeid_replies` (`fid`,`typeid`,`displayorder`,`replies`),
  KEY `typeid_views` (`fid`,`typeid`,`displayorder`,`views`),
  KEY `typeid_recommends` (`fid`,`typeid`,`displayorder`,`recommends`),
  KEY `typeid_heats` (`fid`,`typeid`,`displayorder`,`heats`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_thread_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadaddviews` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `addviews` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadcalendar` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `hotnum` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `fid` (`fid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadclass` (
  `typeid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `fid` mediumint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayorder` mediumint NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moderators` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`typeid`),
  KEY `fid` (`fid`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadclosed` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `redirect` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threaddisablepos` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadhidelog` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `uid` (`tid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadhot` (
  `cid` mediumint unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`,`tid`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadimage` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadmod` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `action` char(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `magicid` smallint unsigned NOT NULL,
  `stamp` tinyint NOT NULL,
  `reason` char(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  KEY `tid` (`tid`,`dateline`),
  KEY `expiration` (`expiration`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadpartake` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  KEY `tid` (`tid`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadpreview` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `relay` int unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadprofile` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `name` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `global` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `global` (`global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadprofile_group` (
  `gid` mediumint NOT NULL,
  `tpid` mediumint unsigned NOT NULL,
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadrush` (
  `tid` int unsigned NOT NULL DEFAULT '0',
  `stopfloor` mediumint unsigned NOT NULL DEFAULT '0',
  `starttimefrom` int unsigned NOT NULL DEFAULT '0',
  `starttimeto` int unsigned NOT NULL DEFAULT '0',
  `rewardfloor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `creditlimit` int NOT NULL DEFAULT '-996',
  `replylimit` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_threadtype` (
  `typeid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `special` smallint NOT NULL DEFAULT '0',
  `modelid` smallint unsigned NOT NULL DEFAULT '0',
  `expiration` tinyint(1) NOT NULL DEFAULT '0',
  `template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `stemplate` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptemplate` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `btemplate` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `super` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_trade` (
  `tid` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `typeid` smallint unsigned NOT NULL,
  `sellerid` mediumint unsigned NOT NULL,
  `seller` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenpayaccount` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `amount` smallint unsigned NOT NULL DEFAULT '1',
  `quality` tinyint unsigned NOT NULL DEFAULT '0',
  `locus` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transport` tinyint(1) NOT NULL DEFAULT '0',
  `ordinaryfee` smallint unsigned NOT NULL DEFAULT '0',
  `expressfee` smallint unsigned NOT NULL DEFAULT '0',
  `emsfee` smallint unsigned NOT NULL DEFAULT '0',
  `itemtype` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `lastbuyer` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `totalitems` smallint unsigned NOT NULL DEFAULT '0',
  `tradesum` decimal(8,2) NOT NULL DEFAULT '0.00',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `aid` int unsigned NOT NULL,
  `displayorder` tinyint(1) NOT NULL,
  `costprice` decimal(8,2) NOT NULL,
  `credit` int unsigned NOT NULL DEFAULT '0',
  `costcredit` int unsigned NOT NULL DEFAULT '0',
  `credittradesum` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tid`,`pid`),
  KEY `pid` (`pid`),
  KEY `sellerid` (`sellerid`),
  KEY `totalitems` (`totalitems`),
  KEY `tradesum` (`tradesum`),
  KEY `displayorder` (`tid`,`displayorder`),
  KEY `sellertrades` (`sellerid`,`tradesum`,`totalitems`),
  KEY `typeid` (`typeid`),
  KEY `credittradesum` (`credittradesum`),
  KEY `expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_tradecomment` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `orderid` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pid` int unsigned NOT NULL,
  `type` tinyint(1) NOT NULL,
  `raterid` mediumint unsigned NOT NULL,
  `rater` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rateeid` mediumint unsigned NOT NULL,
  `ratee` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` char(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` char(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` tinyint(1) NOT NULL,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `raterid` (`raterid`,`type`,`dateline`),
  KEY `rateeid` (`rateeid`,`type`,`dateline`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_tradelog` (
  `tid` int unsigned NOT NULL,
  `pid` int unsigned NOT NULL,
  `orderid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tradeno` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paytype` tinyint unsigned NOT NULL DEFAULT '0',
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `quality` tinyint unsigned NOT NULL DEFAULT '0',
  `itemtype` tinyint(1) NOT NULL DEFAULT '0',
  `number` smallint unsigned NOT NULL DEFAULT '0',
  `tax` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `locus` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sellerid` mediumint unsigned NOT NULL,
  `seller` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selleraccount` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenpayaccount` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `buyerid` mediumint unsigned NOT NULL,
  `buyer` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyercontact` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyercredits` smallint unsigned NOT NULL DEFAULT '0',
  `buyermsg` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `offline` tinyint(1) NOT NULL DEFAULT '0',
  `buyername` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyerzip` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyerphone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyermobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transport` tinyint(1) NOT NULL DEFAULT '0',
  `transportfee` smallint unsigned NOT NULL DEFAULT '0',
  `baseprice` decimal(8,2) NOT NULL,
  `discount` tinyint(1) NOT NULL DEFAULT '0',
  `ratestatus` tinyint(1) NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit` int unsigned NOT NULL DEFAULT '0',
  `basecredit` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `orderid` (`orderid`),
  KEY `sellerid` (`sellerid`),
  KEY `buyerid` (`buyerid`),
  KEY `status` (`status`),
  KEY `buyerlog` (`buyerid`,`status`,`lastupdate`),
  KEY `sellerlog` (`sellerid`,`status`,`lastupdate`),
  KEY `tid` (`tid`,`pid`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_typeoption` (
  `optionid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `classid` smallint unsigned NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `expiration` tinyint(1) NOT NULL,
  `protect` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rules` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `permprompt` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`optionid`),
  KEY `classid` (`classid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_typeoptionvar` (
  `sortid` smallint unsigned NOT NULL DEFAULT '0',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `fid` mediumint unsigned NOT NULL DEFAULT '0',
  `optionid` smallint unsigned NOT NULL DEFAULT '0',
  `expiration` int unsigned NOT NULL DEFAULT '0',
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  KEY `sortid` (`sortid`),
  KEY `tid` (`tid`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_typevar` (
  `sortid` smallint NOT NULL DEFAULT '0',
  `optionid` smallint NOT NULL DEFAULT '0',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `unchangeable` tinyint(1) NOT NULL DEFAULT '0',
  `search` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint NOT NULL DEFAULT '0',
  `subjectshow` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `optionid` (`sortid`,`optionid`),
  KEY `sortid` (`sortid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_forum_warning` (
  `wid` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL,
  `operatorid` mediumint unsigned NOT NULL,
  `operator` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorid` mediumint unsigned NOT NULL,
  `author` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL,
  `reason` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`wid`),
  UNIQUE KEY `pid` (`pid`),
  KEY `authorid` (`authorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_album` (
  `albumid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `albumname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `catid` smallint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `updatetime` int unsigned NOT NULL DEFAULT '0',
  `picnum` smallint unsigned NOT NULL DEFAULT '0',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `friend` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target_ids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `favtimes` mediumint unsigned NOT NULL,
  `sharetimes` mediumint unsigned NOT NULL,
  `depict` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`albumid`),
  KEY `uid` (`uid`,`updatetime`),
  KEY `updatetime` (`updatetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_album_category` (
  `catid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `upid` mediumint unsigned NOT NULL DEFAULT '0',
  `catname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `num` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_blacklist` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `buid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`buid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_blog` (
  `blogid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `classid` smallint unsigned NOT NULL DEFAULT '0',
  `catid` smallint unsigned NOT NULL DEFAULT '0',
  `viewnum` mediumint unsigned NOT NULL DEFAULT '0',
  `replynum` mediumint unsigned NOT NULL DEFAULT '0',
  `hot` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `noreply` tinyint(1) NOT NULL DEFAULT '0',
  `friend` tinyint(1) NOT NULL DEFAULT '0',
  `password` char(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `favtimes` mediumint unsigned NOT NULL DEFAULT '0',
  `sharetimes` mediumint unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `click1` smallint unsigned NOT NULL DEFAULT '0',
  `click2` smallint unsigned NOT NULL DEFAULT '0',
  `click3` smallint unsigned NOT NULL DEFAULT '0',
  `click4` smallint unsigned NOT NULL DEFAULT '0',
  `click5` smallint unsigned NOT NULL DEFAULT '0',
  `click6` smallint unsigned NOT NULL DEFAULT '0',
  `click7` smallint unsigned NOT NULL DEFAULT '0',
  `click8` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`blogid`),
  KEY `uid` (`uid`,`dateline`),
  KEY `hot` (`hot`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_blog_category` (
  `catid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `upid` mediumint unsigned NOT NULL DEFAULT '0',
  `catname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `num` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_blog_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_blogfield` (
  `blogid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `postip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `related` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `relatedtime` int unsigned NOT NULL DEFAULT '0',
  `target_ids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotuser` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `magiccolor` tinyint NOT NULL DEFAULT '0',
  `magicpaper` tinyint NOT NULL DEFAULT '0',
  `pushedaid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`blogid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_class` (
  `classid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `classname` char(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`classid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_click` (
  `clickid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` char(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `idtype` char(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `displayorder` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`clickid`),
  KEY `idtype` (`idtype`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_clickuser` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `clickid` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  KEY `id` (`id`,`idtype`,`dateline`),
  KEY `uid` (`uid`,`idtype`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_comment` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `magicflicker` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `authorid` (`authorid`,`idtype`),
  KEY `id` (`id`,`idtype`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_comment_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idtype` (`idtype`,`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_docomment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `upid` int unsigned NOT NULL DEFAULT '0',
  `doid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `grade` smallint unsigned NOT NULL DEFAULT '0',
  `replynum` int unsigned NOT NULL,
  `recomends` int unsigned NOT NULL,
  `status` tinyint NOT NULL,
  `fields` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `doid` (`doid`,`dateline`),
  KEY `dateline` (`dateline`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_docomment_recomend_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `doid` int unsigned NOT NULL DEFAULT '0',
  `docid` int unsigned NOT NULL DEFAULT '0',
  `uid` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `docid_uid` (`docid`,`uid`),
  KEY `doid` (`doid`),
  KEY `docid` (`docid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_doing` (
  `doid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `itemid` mediumint unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `from` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `body_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `replynum` int unsigned NOT NULL DEFAULT '0',
  `recomends` int unsigned NOT NULL DEFAULT '0',
  `sharetimes` int unsigned NOT NULL DEFAULT '0',
  `favtimes` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `fields` json DEFAULT NULL,
  PRIMARY KEY (`doid`),
  KEY `uid` (`uid`,`dateline`),
  KEY `dateline` (`dateline`),
  KEY `type` (`type`),
  KEY `itemid` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_doing_attachment` (
  `aid` int unsigned NOT NULL AUTO_INCREMENT,
  `doid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `width` mediumint unsigned NOT NULL DEFAULT '0',
  `height` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` int NOT NULL,
  PRIMARY KEY (`aid`),
  KEY `uid` (`uid`),
  KEY `doid` (`doid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_doing_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_doing_recomend_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `doid` int unsigned NOT NULL DEFAULT '0',
  `uid` int unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `doid_uid` (`doid`,`uid`),
  KEY `doid` (`doid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_favorite` (
  `favid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `spaceuid` mediumint unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`favid`),
  KEY `idtype` (`id`,`idtype`(40)),
  KEY `uid` (`uid`,`idtype`(40),`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_feed` (
  `feedid` int unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `friend` tinyint(1) NOT NULL DEFAULT '0',
  `hash_template` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hash_data` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_general` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_1_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_2_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_3` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_3_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_4` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_4_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target_ids` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hot` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`feedid`),
  KEY `uid` (`uid`,`dateline`),
  KEY `dateline` (`dateline`),
  KEY `hot` (`hot`),
  KEY `id` (`id`,`idtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_follow` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `followuid` mediumint unsigned NOT NULL DEFAULT '0',
  `fusername` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `bkname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `mutual` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`followuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_follow_feed` (
  `feedid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`feedid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_follow_feed_archiver` (
  `feedid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tid` int unsigned NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`feedid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_friend` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fuid` mediumint unsigned NOT NULL DEFAULT '0',
  `fusername` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gid` smallint unsigned NOT NULL DEFAULT '0',
  `num` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`,`fuid`),
  KEY `fuid` (`fuid`),
  KEY `uid` (`uid`,`num`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_friend_request` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fuid` mediumint unsigned NOT NULL DEFAULT '0',
  `fusername` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gid` smallint unsigned NOT NULL DEFAULT '0',
  `note` char(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fuid`),
  KEY `fuid` (`fuid`),
  KEY `dateline` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_friendlog` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fuid` mediumint unsigned NOT NULL DEFAULT '0',
  `action` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_notification` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `new` tinyint(1) NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `from_id` mediumint unsigned NOT NULL DEFAULT '0',
  `from_idtype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `from_num` mediumint unsigned NOT NULL DEFAULT '0',
  `category` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`new`),
  KEY `category` (`uid`,`category`,`dateline`),
  KEY `by_type` (`uid`,`type`,`dateline`),
  KEY `from_id` (`from_id`,`from_idtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_pic` (
  `picid` mediumint NOT NULL AUTO_INCREMENT,
  `albumid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `postip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` int unsigned NOT NULL DEFAULT '0',
  `filepath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `hot` mediumint unsigned NOT NULL DEFAULT '0',
  `sharetimes` mediumint unsigned NOT NULL DEFAULT '0',
  `click1` smallint unsigned NOT NULL DEFAULT '0',
  `click2` smallint unsigned NOT NULL DEFAULT '0',
  `click3` smallint unsigned NOT NULL DEFAULT '0',
  `click4` smallint unsigned NOT NULL DEFAULT '0',
  `click5` smallint unsigned NOT NULL DEFAULT '0',
  `click6` smallint unsigned NOT NULL DEFAULT '0',
  `click7` smallint unsigned NOT NULL DEFAULT '0',
  `click8` smallint unsigned NOT NULL DEFAULT '0',
  `magicframe` tinyint NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`picid`),
  KEY `uid` (`uid`),
  KEY `albumid` (`albumid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_pic_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_picfield` (
  `picid` mediumint unsigned NOT NULL DEFAULT '0',
  `hotuser` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`picid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_poke` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fromuid` mediumint unsigned NOT NULL DEFAULT '0',
  `fromusername` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `iconid` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`fromuid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_pokearchive` (
  `pid` mediumint NOT NULL AUTO_INCREMENT,
  `pokeuid` int unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `fromuid` mediumint unsigned NOT NULL DEFAULT '0',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `iconid` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  KEY `pokeuid` (`pokeuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_share` (
  `sid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `itemid` mediumint unsigned NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fromuid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `title_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_general` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hot` mediumint unsigned NOT NULL DEFAULT '0',
  `hotuser` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`sid`),
  KEY `uid` (`uid`,`dateline`),
  KEY `hot` (`hot`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_share_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_show` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unitprice` int unsigned NOT NULL DEFAULT '1',
  `credit` int unsigned NOT NULL DEFAULT '0',
  `note` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  KEY `unitprice` (`unitprice`),
  KEY `credit` (`credit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_specialuser` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `dateline` int NOT NULL DEFAULT '0',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `opuid` mediumint unsigned NOT NULL DEFAULT '0',
  `opusername` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayorder` mediumint unsigned NOT NULL DEFAULT '0',
  KEY `uid` (`uid`,`status`),
  KEY `displayorder` (`status`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_home_visitor` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `vuid` mediumint unsigned NOT NULL DEFAULT '0',
  `vusername` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`vuid`),
  KEY `vuid` (`vuid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_mobile_setting` (
  `skey` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `svalue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_content` (
  `cid` int unsigned NOT NULL AUTO_INCREMENT,
  `aid` mediumint unsigned NOT NULL DEFAULT '0',
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `pageorder` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `aid` (`aid`,`pageorder`),
  KEY `pageorder` (`pageorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_count` (
  `aid` mediumint unsigned NOT NULL DEFAULT '0',
  `catid` mediumint unsigned NOT NULL DEFAULT '0',
  `viewnum` mediumint unsigned NOT NULL DEFAULT '0',
  `commentnum` mediumint unsigned NOT NULL DEFAULT '0',
  `favtimes` mediumint unsigned NOT NULL DEFAULT '0',
  `sharetimes` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_related` (
  `aid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `raid` mediumint unsigned NOT NULL DEFAULT '0',
  `displayorder` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`,`raid`),
  KEY `aid` (`aid`,`displayorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_title` (
  `aid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `catid` mediumint unsigned NOT NULL DEFAULT '0',
  `bid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `highlight` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `fromurl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `contents` smallint NOT NULL DEFAULT '0',
  `allowcomment` tinyint(1) NOT NULL DEFAULT '0',
  `owncomment` tinyint(1) NOT NULL DEFAULT '0',
  `click1` smallint unsigned NOT NULL DEFAULT '0',
  `click2` smallint unsigned NOT NULL DEFAULT '0',
  `click3` smallint unsigned NOT NULL DEFAULT '0',
  `click4` smallint unsigned NOT NULL DEFAULT '0',
  `click5` smallint unsigned NOT NULL DEFAULT '0',
  `click6` smallint unsigned NOT NULL DEFAULT '0',
  `click7` smallint unsigned NOT NULL DEFAULT '0',
  `click8` smallint unsigned NOT NULL DEFAULT '0',
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `showinnernav` tinyint(1) NOT NULL DEFAULT '0',
  `preaid` mediumint unsigned NOT NULL,
  `nextaid` mediumint unsigned NOT NULL,
  `htmlmade` tinyint(1) NOT NULL DEFAULT '0',
  `htmlname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `htmldir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`aid`),
  KEY `catid` (`catid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_article_trash` (
  `aid` mediumint unsigned NOT NULL DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_attachment` (
  `attachid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filetype` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filesize` int unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `isimage` tinyint(1) NOT NULL DEFAULT '0',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  `aid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`attachid`),
  KEY `aid` (`aid`,`attachid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_category` (
  `catid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `upid` mediumint unsigned NOT NULL DEFAULT '0',
  `catname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `articles` mediumint unsigned NOT NULL DEFAULT '0',
  `allowcomment` tinyint(1) NOT NULL DEFAULT '1',
  `displayorder` smallint NOT NULL DEFAULT '0',
  `notinheritedarticle` tinyint(1) NOT NULL DEFAULT '0',
  `notinheritedblock` tinyint(1) NOT NULL DEFAULT '0',
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `shownav` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `seotitle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `keyword` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `primaltplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `articleprimaltplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `disallowpublish` tinyint(1) NOT NULL DEFAULT '0',
  `foldername` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `notshowarticlesummay` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `perpage` smallint NOT NULL DEFAULT '0',
  `maxpages` smallint NOT NULL DEFAULT '0',
  `noantitheft` tinyint(1) NOT NULL DEFAULT '0',
  `lastpublish` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_category_permission` (
  `catid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `allowpublish` tinyint(1) NOT NULL DEFAULT '0',
  `allowmanage` tinyint(1) NOT NULL DEFAULT '0',
  `inheritedcatid` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`catid`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_comment` (
  `cid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id` mediumint unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `postip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `port` smallint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`cid`),
  KEY `idtype` (`id`,`idtype`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_comment_moderate` (
  `id` int unsigned NOT NULL DEFAULT '0',
  `idtype` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idtype` (`idtype`,`status`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_rsscache` (
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `catid` mediumint unsigned NOT NULL DEFAULT '0',
  `aid` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `catname` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `author` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY `aid` (`aid`),
  KEY `catid` (`catid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_topic` (
  `topicid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `keyword` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picflag` tinyint(1) NOT NULL DEFAULT '0',
  `primaltplname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `useheader` tinyint(1) NOT NULL DEFAULT '0',
  `usefooter` tinyint(1) NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `viewnum` mediumint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `allowcomment` tinyint(1) NOT NULL DEFAULT '0',
  `commentnum` mediumint unsigned NOT NULL DEFAULT '0',
  `htmlmade` tinyint(1) NOT NULL DEFAULT '0',
  `htmldir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`topicid`),
  KEY `name` (`name`(40))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_portal_topic_pic` (
  `picid` mediumint NOT NULL AUTO_INCREMENT,
  `topicid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `size` int unsigned NOT NULL DEFAULT '0',
  `filepath` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `thumb` tinyint(1) NOT NULL DEFAULT '0',
  `remote` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`picid`),
  KEY `topicid` (`topicid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_restful_api` (
  `baseuri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ver` smallint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `copyright` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`baseuri`,`ver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_restful_app` (
  `appid` int unsigned NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_restful_permission` (
  `appid` int unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ver` smallint unsigned NOT NULL,
  `isbase` tinyint(1) NOT NULL,
  `freq` int unsigned NOT NULL,
  `dateline` int unsigned NOT NULL,
  PRIMARY KEY (`appid`,`uri`,`ver`),
  KEY `isbase` (`isbase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_restful_source` (
  `sourceid` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`sourceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_restful_stat` (
  `appid` int unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daytime` int unsigned NOT NULL DEFAULT '0',
  `request` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`appid`,`uri`,`daytime`),
  KEY `daytime` (`daytime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_security_evilpost` (
  `pid` int unsigned NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `evilcount` int NOT NULL DEFAULT '0',
  `eviltype` mediumint unsigned NOT NULL DEFAULT '0',
  `createtime` int unsigned NOT NULL DEFAULT '0',
  `operateresult` tinyint(1) NOT NULL DEFAULT '0',
  `isreported` tinyint(1) NOT NULL DEFAULT '0',
  `censorword` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`pid`),
  KEY `type` (`tid`,`type`),
  KEY `operateresult` (`operateresult`,`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_security_eviluser` (
  `uid` int unsigned NOT NULL,
  `evilcount` int NOT NULL DEFAULT '0',
  `eviltype` mediumint unsigned NOT NULL DEFAULT '0',
  `createtime` int unsigned NOT NULL DEFAULT '0',
  `operateresult` tinyint(1) NOT NULL DEFAULT '0',
  `isreported` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `operateresult` (`operateresult`,`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_security_failedlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporttype` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tid` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `uid` int unsigned NOT NULL DEFAULT '0',
  `failcount` int unsigned NOT NULL DEFAULT '0',
  `createtime` int unsigned NOT NULL DEFAULT '0',
  `posttime` int unsigned NOT NULL DEFAULT '0',
  `delreason` char(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduletime` int unsigned NOT NULL DEFAULT '0',
  `lastfailtime` int unsigned NOT NULL DEFAULT '0',
  `extra1` int unsigned NOT NULL,
  `extra2` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_admins` (
  `uid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `allowadminsetting` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminapp` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminuser` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminbadword` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmintag` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminpm` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmincredits` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmindomain` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmindb` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminnote` tinyint(1) NOT NULL DEFAULT '0',
  `allowadmincache` tinyint(1) NOT NULL DEFAULT '0',
  `allowadminlog` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_applications` (
  `appid` smallint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authkey` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `viewprourl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apifilename` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uc.php',
  `charset` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dbcharset` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `synlogin` tinyint(1) NOT NULL DEFAULT '0',
  `recvnote` tinyint(1) DEFAULT '0',
  `extra` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tagtemplates` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowips` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_badwords` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `admin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `find` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `replacement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `findpattern` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_domains` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `domain` char(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_failedlogins` (
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `count` tinyint unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_feeds` (
  `feedid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `appid` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `icon` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `hash_template` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hash_data` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `title_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_general` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_1_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_2_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_3` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_3_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_4` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `image_4_link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `target_ids` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`feedid`),
  KEY `uid` (`uid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_friends` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `friendid` mediumint unsigned NOT NULL DEFAULT '0',
  `direction` tinyint(1) NOT NULL DEFAULT '0',
  `version` int unsigned NOT NULL AUTO_INCREMENT,
  `delstatus` tinyint(1) NOT NULL DEFAULT '0',
  `comment` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`version`),
  KEY `uid` (`uid`),
  KEY `friendid` (`friendid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_mailqueue` (
  `mailid` int unsigned NOT NULL AUTO_INCREMENT,
  `touid` mediumint unsigned NOT NULL DEFAULT '0',
  `tomail` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frommail` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `charset` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `htmlon` tinyint(1) NOT NULL DEFAULT '0',
  `level` tinyint(1) NOT NULL DEFAULT '1',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `failures` tinyint unsigned NOT NULL DEFAULT '0',
  `appid` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`mailid`),
  KEY `appid` (`appid`),
  KEY `level` (`level`,`failures`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_memberfields` (
  `uid` mediumint unsigned NOT NULL,
  `blacklist` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_memberlogs` (
  `lid` int unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint unsigned NOT NULL,
  `action` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `extra` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`lid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_members` (
  `uid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobicc` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secmobile` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `myid` char(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `myidkey` char(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `regip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `regdate` int unsigned NOT NULL DEFAULT '0',
  `lastloginip` int NOT NULL DEFAULT '0',
  `lastlogintime` int unsigned NOT NULL DEFAULT '0',
  `salt` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `secques` char(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  KEY `email` (`email`(40)),
  KEY `secmobile` (`secmobile`,`secmobicc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_mergemembers` (
  `appid` smallint unsigned NOT NULL,
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`appid`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_newpm` (
  `uid` mediumint unsigned NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_notelist` (
  `noteid` int unsigned NOT NULL AUTO_INCREMENT,
  `operation` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `closed` tinyint NOT NULL DEFAULT '0',
  `totalnum` smallint unsigned NOT NULL DEFAULT '0',
  `succeednum` smallint unsigned NOT NULL DEFAULT '0',
  `getdata` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `postdata` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `pri` tinyint NOT NULL DEFAULT '0',
  `app1` tinyint NOT NULL,
  PRIMARY KEY (`noteid`),
  KEY `closed` (`closed`,`pri`,`noteid`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_indexes` (
  `pmid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_lists` (
  `plid` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `pmtype` tinyint unsigned NOT NULL DEFAULT '0',
  `subject` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `members` smallint unsigned NOT NULL DEFAULT '0',
  `min_max` varchar(17) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `lastmessage` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`plid`),
  KEY `pmtype` (`pmtype`),
  KEY `min_max` (`min_max`),
  KEY `authorid` (`authorid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_members` (
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `isnew` tinyint(1) NOT NULL DEFAULT '0',
  `pmnum` int unsigned NOT NULL DEFAULT '0',
  `lastupdate` int unsigned NOT NULL DEFAULT '0',
  `lastdateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`plid`,`uid`),
  KEY `isnew` (`isnew`),
  KEY `lastdateline` (`uid`,`lastdateline`),
  KEY `lastupdate` (`uid`,`lastupdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_0` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_1` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_2` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_3` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_4` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_5` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_6` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_7` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_8` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_pm_messages_9` (
  `pmid` mediumint unsigned NOT NULL DEFAULT '0',
  `plid` mediumint unsigned NOT NULL DEFAULT '0',
  `authorid` mediumint unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delstatus` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pmid`),
  KEY `plid` (`plid`,`delstatus`,`dateline`),
  KEY `dateline` (`plid`,`dateline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_protectedmembers` (
  `uid` mediumint unsigned NOT NULL DEFAULT '0',
  `username` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `appid` tinyint unsigned NOT NULL DEFAULT '0',
  `dateline` int unsigned NOT NULL DEFAULT '0',
  `admin` char(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  UNIQUE KEY `username` (`username`,`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_settings` (
  `k` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_sqlcache` (
  `sqlid` char(6) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `data` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` int unsigned NOT NULL,
  PRIMARY KEY (`sqlid`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_tags` (
  `tagname` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `appid` smallint unsigned NOT NULL DEFAULT '0',
  `data` mediumtext COLLATE utf8mb4_unicode_ci,
  `expiration` int unsigned NOT NULL,
  KEY `tagname` (`tagname`,`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pre_ucenter_vars` (
  `name` char(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `value` char(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pre_flipbook_order` (
  orderid bigint unsigned NOT NULL AUTO_INCREMENT, uid mediumint unsigned NOT NULL,
  request_key varchar(64) NOT NULL, request_digest char(64) NOT NULL,
  out_trade_no varchar(32) NOT NULL, appid varchar(32) NOT NULL, mchid varchar(32) NOT NULL,
  productid int unsigned NOT NULL, skuid int unsigned NOT NULL DEFAULT 0, quantity smallint unsigned NOT NULL,
  unit_fen int unsigned NOT NULL, shipping_fen int unsigned NOT NULL DEFAULT 0, total_fen int unsigned NOT NULL,
  title varchar(150) NOT NULL, snapshot text NOT NULL, address text NOT NULL, payer text NOT NULL,
  status varchar(16) NOT NULL DEFAULT 'pending', transaction_id varchar(64) DEFAULT NULL,
  prepay text NOT NULL, created int unsigned NOT NULL, expires int unsigned NOT NULL,
  paid_at int unsigned NOT NULL DEFAULT 0, closed_at int unsigned NOT NULL DEFAULT 0,
  shipped_at int unsigned NOT NULL DEFAULT 0, carrier varchar(80) NOT NULL DEFAULT '', tracking varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (orderid), UNIQUE KEY request_key(uid,request_key), UNIQUE KEY out_trade_no(out_trade_no),
  UNIQUE KEY transaction_id(transaction_id), KEY member_status(uid,status,orderid)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
