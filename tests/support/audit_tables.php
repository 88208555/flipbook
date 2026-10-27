<?php
// Test-only isolation: the connection sees temporary replicas; business tables remain untouched.
if(!defined('IN_DISCUZ')||!class_exists('DB'))throw new RuntimeException('Initialize the test application before table isolation');
$auditPrefix=DB::table('flipbook');
$auditTables=DB::fetch_all('SHOW TABLES LIKE %s',[str_replace(['_','%'],['\\_','\\%'],$auditPrefix).'%']);
foreach($auditTables as $auditTable){
 $auditName=array_values($auditTable)[0];
 if(!preg_match('/^[A-Za-z0-9_]+$/D',$auditName))throw new RuntimeException('Invalid isolated table name');
 $auditDefinition=DB::fetch_first('SHOW CREATE TABLE `'.$auditName.'`');
 $auditRows=DB::fetch_all('SELECT * FROM `'.$auditName.'`');
 DB::query(preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$auditDefinition['Create Table']));
 foreach($auditRows as $auditRow)DB::insert(substr($auditName,strlen(DB::table(''))),$auditRow);
}
unset($auditPrefix,$auditTables,$auditTable,$auditName,$auditDefinition,$auditRows,$auditRow);
