<?php
// All creation and edit fixtures run in a transaction and are rolled back.
chdir('/var/www/html');$_SERVER['SCRIPT_NAME']='/admin.php';$_SERVER['PHP_SELF']='/admin.php';$_SERVER['SCRIPT_FILENAME']='/var/www/html/admin.php';
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='localhost:8088';$_SERVER['REQUEST_URI']='/';$_SERVER['REQUEST_METHOD']='GET';
require './source/class/class_core.php';C::app()->init();
set_exception_handler(function($error){file_put_contents('php://stderr','FAIL '.$error->getMessage()."\n");exit(1);});
define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require_once './source/function/function_admincp.php';$lang=lang('admincp');
use flipbook\front_micro;
use flipbook\admin_micropage;
function created_check($ok,$message){if(!$ok)throw new RuntimeException($message);echo 'PASS '.$message."\n";}
function created_reject($input){try{admin_micropage::create($input);}catch(RuntimeException $error){return true;}return false;}
function created_render($query){global $_G,$lang;$_GET=['do'=>2,'pmod'=>'micropage']+$query;ob_start();include './source/plugin/flipbook/micropage.inc.php';return ob_get_clean();}
function created_dom($html){$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($doc);}
$table=flipbook\table_flipbook_micropage::t();$before=$table->fetch_all_pages();$builtin=front_micro::builtin_keys();
DB::query('START TRANSACTION');
try {
 foreach(array_keys($builtin) as $category)created_check(created_reject(['name'=>'Blocked page','category'=>$category]),$category.' cannot be created as a page category');
 foreach(['key','pkey','route','url'] as $field)created_check(created_reject(['name'=>'Blocked route',$field=>'home']),$field.' cannot override an automatically assigned route');
 foreach(['',str_repeat('中',101),['invalid']] as $name)created_check(created_reject(['name'=>$name]),'invalid page names are rejected');
 created_check($table->fetch_all_pages()===$before,'rejected requests leave all page records unchanged');
 $page=admin_micropage::create(['name'=>'品牌故事','category'=>'micro']);
 $second=admin_micropage::create(['name'=>'品牌故事','category'=>'micro']);
 $key=$page['key'];
 $autoRoutes=$page['routes'];
 created_check(front_micro::is_custom_key($key)&&$key!==$second['key'],'same-name pages get distinct generated identifiers');
 created_check($page['version']===1&&$page['config']['components']===[],'new pages start with an independent empty configuration');
 created_check(front_micro::key($key)===$key&&front_micro::keys()[$key]==='品牌故事','new pages immediately join the page registry');
 created_check($page['routes']===['link'=>'page:'.$key,'h5'=>'plugin.php?id=flipbook:micro&key='.$key,'mini'=>'/pages/micro/index?key='.$key],'creation automatically configures H5, mini-program and editor links');
 created_check(front_micro::link_url('page:'.$key)===$page['routes']['h5']&&front_micro::link_mini('page:'.$key)===$page['routes']['mini'],'both link resolvers accept the registered custom page');
 $config=$page['config'];$config['components']=[['id'=>'story','type'=>'text','props'=>['text'=>'只属于新微页面的内容','link'=>'page:'.$second['key']]]];
 created_check($table->save_page($key,$page['name'],json_encode(front_micro::sanitize($config)),1)===2,'custom pages use the existing versioned editor save');
 created_check($table->save_page($key,$page['name'],'{}',1)===0,'stale custom-page editors cannot overwrite newer saves');
 $fresh=front_micro::page($key);
 created_check($fresh['name']==='品牌故事'&&$fresh['config']['components'][0]['props']['text']==='只属于新微页面的内容','saved custom configuration and title survive reload');
 created_check(front_micro::versions()[$key]===2,'custom page versions participate in client synchronization');
 $_GET=['key'=>$key];$method=new ReflectionMethod(flipbook\front_api::class,'microkey');
 created_check($method->invoke(null)===$key,'the API selects the custom page instead of the homepage');
 $_GET=['key'=>'micro_0000000000000000'];
 created_check($method->invoke(null)===''&&front_micro::link_url('page:micro_0000000000000000')==='','unregistered custom routes remain inaccessible');
 $_GET=['key'=>$key];ob_start();include './source/plugin/flipbook/micro.inc.php';$h5=ob_get_clean();
 created_check(str_contains($h5,'只属于新微页面的内容')&&str_contains($h5,'page:'.$second['key'])&&str_contains($h5,'composer-runtime.js'),'the real H5 controller renders custom content and its generated page links');
 $_G['uid']=1;$_G['adminid']=1;
 $listHtml=created_render([]);$list=created_dom($listHtml);
 created_check($list->query('//*[@data-fb-micro-create]')->length===1&&$list->query('//form//input[@name="category" and @value="micro"]')->length===1,'the only creation form is fixed to micro pages');
 created_check($list->query('//*[@id="fb-micro-create"]//select')->length===0&&$list->query('//*[@id="fb-micro-create"]//input[@name="key" or @name="route" or @name="url"]')->length===0,'the creation form requires no manual category, route or link');
 created_check(str_contains($listHtml,'page:'.$key)&&str_contains($listHtml,'/pages/micro/index?key='.$key),'the page list shows generated links for new pages');
 $editor=created_dom(created_render(['op'=>'edit','key'=>$key]));
 created_check($editor->query('//*[@id="fbmp-form"]//input[@name="key" and @value="'.$key.'"]')->length===1,'new pages open the real editor with their own save target');
 $keyData=$editor->query('//script[contains(text(),"FBMP_KEYS")]')->item(0)->textContent;
 created_check(str_contains($keyData,$second['key']),'new pages are automatically offered by the editor link selector');
 $_G['uid']=0;$_G['adminid']=0;
 created_check(created_dom(created_render([]))->query('//*[@data-fb-micro-create]')->length===0,'administrators without create permission do not get a creation control');
 foreach($before as $row)created_check($table->fetch_by_key($row['pkey'])===$row,'existing '.$row['pkey'].' configuration remains unchanged');
 if(getenv('FLIPBOOK_CREATE_FIXTURE'))echo 'FIXTURE '.json_encode(['page'=>$fresh,'routes'=>$autoRoutes,'list'=>$listHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
} finally {
 DB::query('ROLLBACK');front_micro::clear_custom_keys();
}
created_check($table->fetch_all_pages()===$before,'all temporary pages and edits were rolled back');
