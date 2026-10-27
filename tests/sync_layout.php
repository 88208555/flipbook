<?php
namespace flipbook {class admin_helper {public static function assets(){}}}
namespace flipbook_sync {
define('IN_DISCUZ',true);
function dhtmlspecialchars($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function formhash(){return 'fixture';}
function dgmdate($value,$format){return '2026-10-25';}
class DB {public static function fetch_all($sql,$args){
 if($args[0]==='flipbook_sync_key')return [['clientid'=>7,'domain'=>'client.example.com','enabled'=>1,'note'=>'','ips'=>'','lastused'=>0,'created'=>1]];
 if($args[0]==='flipbook_sync_source')return [['sourceid'=>9,'name'=>'来源站','endpoint'=>'https://source.example.com/','enabled'=>1,'domain'=>'local.example.com','lastsync'=>0]];
 return [];
}}
class admin_controller {public static function url($tab){return '/admin.php?tab='.$tab;}public static function native_url($query){return '/admin.php?'.http_build_query($query);}}
class service_client {public static function own_domain(){return 'local.example.com';}}
class service_fields {public static function groups(){return ['product'=>['label'=>'商品','fields'=>['name'=>'名称','summary'=>'副标题']]];}public static function allowed($id){return ['name','summary'];}public static function mode($id){return 'shared';}public static function local_enabled(){return true;}}
// The alias is limited to this isolated fixture process; it never opens a database.
class_alias(__NAMESPACE__.'\\DB','DB');
require (getenv('FLIPBOOK_SYNC_ROOT')?:'/var/www/html/source/plugin/flipbook_sync').'/admin/admin_view.php';
$_G=['siteurl'=>'https://local.example.com/'];$out=[];
foreach(['admincp','receive','guide','data'] as $tab){ob_start();admin_view::render($tab);$out[$tab]=ob_get_clean();}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
