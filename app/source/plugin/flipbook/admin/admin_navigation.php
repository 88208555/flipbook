<?php
namespace flipbook;

use DB;

if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Extend the native admin shell through the supported template hook. */
class admin_navigation {
 private static $buffered = false;

 public static function template($context) {
  global $_G;
  if(self::$buffered || !defined('IN_ADMINCP') || !defined('PLATFORM') || PLATFORM!=='system' || ($context['param'][0]??'')!=='admin/main') return;
  if(!in_array('flipbook',(array)($_G['setting']['plugins']['available']??[]),true)) return;
  $group=$button=$headers='';
  self::extend($group,$button,$headers);
  if($group==='') return;
  self::$buffered=true;
  // The template hook runs before the native shell is emitted. Extend that
  // output before its existing menu listeners initialise; no core template copy.
  ob_start(static function($html) use($group,$button) {
   if(strpos($html,'id="lm_flipbook"')!==false) return $html;
   // Match the sidebar list independently of the optional branding below it.
   $html=preg_replace_callback('~(<ul id="leftmenu">)(.*?)(</ul>)(?=\s*(?:<div id="copyright">|</nav>))~s',static function($match) use($group) {
    return $match[1].self::insert_after_portal($match[2],$group).$match[3];
   },$html,1,$count);
   if(!$count) return $html;
   $html=preg_replace('~(<ul id="topmenu">)~','$1'.$button,$html,1);
   $html=preg_replace('~(var headers = new Array\()~',"$1'flipbook',",$html,1);
   $script='<script src="source/plugin/flipbook/static/admin/navigation.js?v='.@filemtime(__DIR__.'/../static/admin/navigation.js').'"></script>';
   return str_replace('</body>',$script.'</body>',$html);
  });
 }

 public static function extend(&$leftmenus,&$topmenus,&$headers) {
  global $_G,$admincp;
  if(strpos($leftmenus,'id="lm_flipbook"')!==false || intval($_G['adminid'])!==1) return;
  $plugins=[];
  foreach(DB::fetch_all('SELECT pluginid,identifier,available FROM %t WHERE identifier IN (%s,%s)',['common_plugin','flipbook','flipbook_sync']) as $plugin) $plugins[$plugin['identifier']]=$plugin;
  if(empty($plugins['flipbook']['available'])) return;
  $items='';$active=false;
  foreach(['flipbook'=>admin_helper::pmods(),'flipbook_sync'=>['admincp'=>'产品同步']] as $identifier=>$modules) {
   $plugin=$plugins[$identifier]??null;
   if(empty($plugin['available'])) continue;
   foreach($modules as $pmod=>$label) {
    $id=intval($plugin['pluginid']);
    $perms=$admincp->perms??[];
    if(!$admincp->allow('plugins','config',$id) && empty($perms['plugin_'.$identifier]) && empty($perms['plugin_'.$identifier.':'.$pmod]) && empty($perms['plugin_'.$identifier.':'.$pmod.':'.$id])) continue;
    $query=http_build_query(['action'=>'plugins','operation'=>'config','do'=>$id,'identifier'=>$identifier,'pmod'=>$pmod==='release'?'wechat':$pmod]+($pmod==='release'?['wechat_tab'=>'release']:[]));
    $url=ADMINSCRIPT.(strpos(ADMINSCRIPT,'?')===false?'?':'&').$query;
    $menuid='flipbook_nav_'.$identifier.'_'.$pmod;
    $selected=($_GET['identifier']??'')===$identifier && ($_GET['pmod']??'admincp')===($pmod==='release'?'wechat':$pmod) && ($pmod==='release'?($_GET['wechat_tab']??'')==='release':($pmod!=='wechat'||($_GET['wechat_tab']??'')!=='release'));
    if($selected) {$active=true;$_G['defaultTab']=$menuid;}
    $items.='<li><a id="submn_'.$menuid.'"'.($selected?' class="active"':'').' href="'.admin_helper::e($url).'" target="main"><em title="'.admin_helper::e(cplang('nav_newwin')).'"></em><div>'.$label.'</div></a></li>';
   }
  }
  if($items==='') return;
  $icon='<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;top:16px;left:15px;width:16px;height:16px;color:rgba(255,255,255,.7)"><path d="M12 5c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V4c-3-1-6-1-9 1Z"></path><path d="M12 5v15M6 8h3M6 12h3M15 8h3M15 12h3"></path></svg>';
  $group='<li id="lm_flipbook"><a id="leftmn_flipbook">'.$icon.'<span>画册</span></a><ul id="menu_flipbook">'.$items.($active?"<script>defaultNav = 'flipbook';</script>":'').'</ul></li>';
  // Insert after the complete portal group, preserving every existing menu.
  $leftmenus=self::insert_after_portal($leftmenus,$group);
  $topmenus.='<li><button id="header_flipbook">画册</button></li>';
  $headers.=",'flipbook'";
 }

 private static function insert_after_portal($menus,$group) {
  $portal=strpos($menus,'<li id="lm_portal">');
  $next=$portal===false?false:strpos($menus,'<li id="lm_',$portal+1);
  return $next===false?$menus.$group:substr_replace($menus,$group,$next,0);
 }
}
