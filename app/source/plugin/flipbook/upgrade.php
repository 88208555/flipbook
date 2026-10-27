<?php
if(!defined('IN_DISCUZ')) { exit('Access Denied'); }
require_once libfile('function/cache');
$plugin = DB::fetch_first('SELECT pluginid FROM %t WHERE identifier=%s', ['common_plugin', 'flipbook']);
if($plugin) {
 foreach(DB::fetch_all('SELECT variable, value FROM %t WHERE pluginid=%d', ['common_pluginvar', intval($plugin['pluginid'])]) as $row) {
  if(in_array($row['variable'], ['albumname', 'showprice', 'postertemplate'], true)) {
   DB::query('INSERT IGNORE INTO %t (skey,svalue,version) VALUES (%s,%s,%s)', ['flipbook_setting', $row['variable'], $row['value'], '0.3.0']);
   DB::delete('common_pluginvar', ['pluginid' => intval($plugin['pluginid']), 'variable' => $row['variable']]);
  }
 }
 $manifest = json_decode(file_get_contents(__DIR__.'/discuz_plugin_flipbook.json'), true);
 DB::update('common_plugin', ['modules' => serialize($manifest['Data']['plugin']['__modules']), 'version' => '0.3.0', 'datatables' => $manifest['Data']['plugin']['datatables']], ['pluginid' => intval($plugin['pluginid'])]);
 updatecache('plugin');
}
include __DIR__.'/install.php';
// Only replace prior default dimensions; preserve user visibility and other styling.
foreach(flipbook\table_flipbook_micropage::t()->fetch_all_pages() as $row) {
 $config=json_decode($row['config'],true); if(!is_array($config))continue; $changed=false;
 if(($config['nav']['height']??0)===54) { $config['nav']['height']=44; $changed=true; }
 if(($config['nav']['firstGap']??0)===24) { $config['nav']['firstGap']=16; $changed=true; }

 foreach(['left','bottom'] as $side){if(($config['actions'][$side]??null)===20){$config['actions'][$side]=10;$changed=true;}}
 if($changed) flipbook\table_flipbook_micropage::t()->save_page($row['pkey'],$row['name'],json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),intval($row['version']));
}
$finish = true;
