<?php
namespace flipbook;
define('IN_DISCUZ',true);define('IN_ADMINCP',true);
function formhash(){return 'fixture';}
class admin_helper {static function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}static function url($v){return '/admin.php';}}
class front_view {static function settings(){return ['albumname'=>'Fixture Album'];}}
class front_service_config {static function get($v){return '';}}
class front_wechat_subscription {static $config=[];static function config(){return self::$config;}static function definitions(){return ['product'=>['title'=>'商品通知','fields'=>['product_name'=>'商品名称']],'album'=>['title'=>'画册通知','fields'=>['album_name'=>'画册名称']]];}}
require getenv('FLIPBOOK_WECHAT_ADMIN')?:'/var/www/html/source/plugin/flipbook/admin/admin_wechat.php';$out=[];
foreach(['empty','configured'] as $kind){front_wechat_subscription::$config=$kind==='empty'?[]:['product'=>['templateId'=>'fixture_123456','title'=>'Fetched template fixture','fields'=>[['key'=>'thing1','label'=>'商品名称','source'=>'product_name']],'enabled'=>1],'album'=>['templateId'=>'','fields'=>[]]];ob_start();admin_wechat::subscribe();$out[$kind]=ob_get_clean();}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
