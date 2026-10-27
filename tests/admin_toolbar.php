<?php
// This process has no database connection or persistence operation.
namespace flipbook {
 class table_flipbook_setting {static function t(){return new self;}function fetch_all_setting(){return ['albumname'=>'原画册','sharetitle'=>'{name}'];}}
 class admin_helper {const IDENTIFIER='flipbook';static function assets(){}static function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}static function pluginid(){return 2;}static function url($v){return '/admin.php?pmod='.$v;}}
}
namespace {
 define('IN_DISCUZ',true);define('IN_ADMINCP',true);
 class DB {static function table($v){return 'fixture_'.$v;}static function fetch_all($sql,...$args){return [];}}
 function formhash(){return 'fixture';}function submitcheck($key){return false;}function lang($name){return [];}
 function showformheader($url){echo '<form id="cpform" method="post">';}function showformfooter(){echo '</form>';}
 function showtableheader($title=''){echo '<table class="tb"><tbody>';if($title!=='')echo '<tr><th colspan="3">'.htmlspecialchars($title).'</th></tr>';}
 function showtablefooter(){echo '</tbody></table>';}
 function showtablerow($style,$classes,$cells){echo '<tr>';foreach($cells as $c)echo '<td>'.$c.'</td>';echo '</tr>';}
 function showsetting($title,$field,$value,$type,...$extra){
  $name=is_array($field)?$field[0]:$field;$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
  if($type==='textarea')$html='<textarea name="'.$e($name).'">'.$e($value).'</textarea>';
  elseif($type==='select'){$html='<select name="'.$e($name).'">';foreach($field[1] as $o)$html.='<option value="'.$e($o[0]).'"'.((string)$o[0]===(string)$value?' selected':'').'>'.$e($o[1]).'</option>';$html.='</select>';}
  elseif($type==='radio'){$html='';foreach(['1'=>'是','0'=>'否'] as $v=>$label)$html.='<label><input type="radio" name="'.$e($name).'" value="'.$v.'"'.((string)$v===(string)$value?' checked':'').'>'.$label.'</label>';}
  else $html='<input name="'.$e($name).'" value="'.$e($value).'">';
  showtablerow('',[],[$e($title),$html,$e($extra[2]??'')]);
 }
 function showsubmit($name){echo '<tr><td colspan="3"><input type="submit" class="btn" name="'.$name.'" value="提交"></td></tr>';}
 $_G=['siteurl'=>'https://fixture.example/','config'=>['security'=>['authkey'=>'fixture']]];$scriptlang=[];
 $pluginRoot=getenv('FLIPBOOK_PLUGIN_ROOT')?:'/var/www/html/source/plugin/flipbook';
 foreach(['front_service_config','front_wechat_pay','front_order_store','front_order_service','front_commerce','front_wechat_release','front_wechat_subscription'] as $name)require $pluginRoot.'/front/'.$name.'.php';
 require $pluginRoot.'/admin/admin_commerce.php';
 function settings_render($tab){global $_G,$scriptlang,$pluginRoot;$_GET=['pmod'=>'admincp','settings_tab'=>$tab];$_POST=[];ob_start();include $pluginRoot.'/admincp.inc.php';return ob_get_clean();}
 $html=['settingsBasic'=>settings_render('basic'),'settingsShare'=>settings_render('share')];
 foreach(['orders','trade','customer'] as $group){ob_start();\flipbook\admin_commerce::render($group);$html[$group]=ob_get_clean();}
 echo json_encode($html,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
