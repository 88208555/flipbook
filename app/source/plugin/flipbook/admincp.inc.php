<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台：画册设置与概况
 *
 * 入口：后台 → 插件 → 工装画册
 *
 * 业务配置以插件自己的 flipbook_setting 表为准：原生卸载会先删除
 * common_pluginvar，因此关键配置不能只依赖插件自定义变量承载。
 * 基础设置与服务接入统一使用本表。
 * 服务端密钥仅接受新值，不回填页面。
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

use flipbook\admin_helper;

$Plang = !empty($scriptlang['flipbook']) ? $scriptlang['flipbook'] : lang('plugin/flipbook');
$setting = flipbook\table_flipbook_setting::t();

$schema = [
	'albumname' => ['title' => '画册名称', 'type' => 'text', 'default' => '工装电子画册'],
	'showprice' => ['title' => '展示价格区域', 'type' => 'bool', 'default' => '0', 'comment' => '关闭后前台与小程序不显示任何价格'],
	'pricelabel' => ['title' => '价格标签文案', 'type' => 'text', 'default' => '参考价'],
	'share_bg'=>['title'=>'背景颜色','type'=>'text','default'=>'#FFFFFF','group'=>'商品分享'],
 'share_color'=>['title'=>'文字颜色','type'=>'text','default'=>'#111111','group'=>'商品分享'],
 'share_radius'=>['title'=>'圆角','type'=>'text','default'=>'20','group'=>'商品分享'],
 'share_showprice'=>['title'=>'显示价格','type'=>'bool','default'=>'1','group'=>'商品分享'],
 'share_qr_channel'=>['title'=>'二维码类型','type'=>'select','options'=>[['h5','H5 商品二维码'],['wechat','微信小程序码']],'default'=>'h5','group'=>'商品分享','comment'=>'微信小程序码需要已发布小程序及服务端 AppSecret'],
 'share_qr_hint'=>['title'=>'二维码说明','type'=>'text','default'=>'扫码查看商品','group'=>'商品分享'],
	'sharetitle' => ['title' => '分享标题', 'type' => 'text', 'default' => '{name}','group'=>'商品分享','comment'=>'支持 {name} 商品名称、{code} 商品编码、{summary} 商品副标题；留空使用商品名称'],
	'sharetext' => ['title' => '分享文案', 'type' => 'textarea', 'default' => '{summary}','group'=>'商品分享'],
];

$section=$_GET['pmod']??'admincp';
$settingsTab=$_POST['settings_tab']??$_GET['settings_tab']??'basic';
if(!is_string($settingsTab)||!in_array($settingsTab,['basic','share'],true))$settingsTab='basic';
$wechatTabs = [
 'login' => ['label'=>'小程序信息', 'group'=>'微信小程序登录'],
 'develop' => ['label'=>'小程序开发信息', 'group'=>'微信小程序登录'],
 'push' => ['label'=>'消息推送', 'group'=>'消息推送'],
 'subscribe' => ['label'=>'订阅消息', 'group'=>'订阅消息'],
 'orders' => ['label'=>'微信订单','group'=>'微信订单'],
 'trade' => ['label'=>'交易组件','group'=>'交易组件'],
 'customer' => ['label'=>'小程序客服','group'=>'小程序客服'],
 'server' => ['label'=>'服务器配置', 'group'=>'服务器配置'],
 'release' => ['label'=>'版本推送', 'group'=>'版本推送'],
];
if($section==='wechat') flipbook\admin_wechat::dispatch();
$wechatTab = (string)($_POST['wechat_tab'] ?? $_GET['wechat_tab'] ?? 'login');
if(!isset($wechatTabs[$wechatTab])) $wechatTab = 'login';
if($section==='storage' || $section==='wechat') {
 $all=flipbook\front_service_config::schema();$schema=[];
 foreach($all as $key=>$field)if(($section==='wechat') === (strpos($key,'wechat_')===0) && empty($field['managed']) && $key!=='wechat_subscribe_templates')$schema[$key]=$field;
}
if(submitcheck('flipbooksubmit')) {
 admin_helper::needperm('edit'); $values=[];
 foreach($schema as $key=>$field) {
  if($field['type']==='readonly')continue;
  $posted=$_POST[$key]??'';if(!is_scalar($posted))admin_helper::failed('设置内容格式不正确');
  $values[$key]=$field['type']==='bool' ? (!empty($posted)?'1':'0') : trim((string)$posted);
  if(strlen($values[$key])>2000) admin_helper::failed('设置内容过长');
 }
 if(isset($values['share_bg']) && (!preg_match('/^#[0-9a-f]{6}$/i',$values['share_bg']) || !preg_match('/^#[0-9a-f]{6}$/i',$values['share_color']))) admin_helper::failed('背景颜色和文字颜色使用 #RRGGBB');
 if(isset($values['share_radius']) && (!ctype_digit($values['share_radius']) || intval($values['share_radius'])>40)) admin_helper::failed('圆角范围为 0 至 40');
 if(isset($values['share_qr_channel']) && !in_array($values['share_qr_channel'],['h5','wechat'],true)) admin_helper::failed('二维码类型无效');
 $clear=[];foreach($schema as $key=>$field)if($field['type']==='secret' && !empty($_POST['clear_'.$key]))$clear[]=$key;
 try{$serviceUpdates=flipbook\front_service_config::prepare_updates($values,$clear);}catch(InvalidArgumentException $error){admin_helper::failed($error->getMessage());}
 if(isset($values['storage_provider']) && !in_array($values['storage_provider'],['local','cos'],true)) admin_helper::failed('存储类型无效');
 $previousQRCode=$section==='wechat'?$setting->fetch_setting('wechat_qrcode',''):'';
 foreach($schema as $key=>$field) {
  if($field['type']==='readonly')continue;
  if(($section==='storage' || $section==='wechat') && !array_key_exists($key,$serviceUpdates))continue;
  $value=($section==='storage' || $section==='wechat')?$serviceUpdates[$key]:$values[$key];
  $setting->update_setting($key,$value,'1');
 }
 if($section==='wechat'){$savedQRCode=$setting->fetch_setting('wechat_qrcode','');if($previousQRCode!==$savedQRCode)flipbook\front_asset::cleanup_replaced(['wechat_qrcode'=>$previousQRCode],['wechat_qrcode'=>$savedQRCode]);}
 admin_helper::succeeded('设置已保存',$section,$section==='wechat'?['wechat_tab'=>$wechatTab]:($section==='admincp'?['settings_tab'=>$settingsTab]:[]));
}

$current = $setting->fetch_all_setting();
admin_helper::assets();

showformheader('plugins&operation=config&do='.admin_helper::pluginid().'&identifier='.admin_helper::IDENTIFIER.'&pmod='.$section);
$renderField = function($skey, $field) use ($current) {
 $value=$current[$skey]??$field['default'];$comment=$field['comment']??'';
 if($field['type']==='readonly') {global $_G;showtablerow('',[],[admin_helper::e($field['title']),'<code>'.admin_helper::e(rtrim($_G['siteurl'],'/').'/plugin.php?id=flipbook:wechat_callback').'</code>','复制到微信公众平台的消息推送配置；正式环境使用 HTTPS']);
 } elseif($field['type']==='secret') {
  showtablerow('',[],[admin_helper::e($field['title']),'<input type="password" autocomplete="new-password" class="txt" name="'.admin_helper::e($skey).'" value="" placeholder="'.($value!==''?'已配置，留空保留':'未配置').'"> <label><input type="checkbox" name="clear_'.admin_helper::e($skey).'" value="1">清除</label>', '密钥加密保存在服务端，不发送给小程序或 H5']);
 } elseif($field['type']==='select') showsetting($field['title'],[$skey,$field['options']],$value,'select','',0,$comment);
 else showsetting($field['title'],$skey,$field['type']==='bool'?intval($value):$value,$field['type']==='bool'?'radio':($field['type']==='textarea'?'textarea':'text'),'',0,$comment);
};
$renderWechatRow = function($label, $content, $help='', $id='', $required=false) {
 echo '<div class="fb-admin-form-row"><'.($id!==''?'label for="'.admin_helper::e($id).'"':'div').' class="fb-admin-form-label">'.admin_helper::e($label).($required?' <span aria-label="必填">*</span>':'').'</'.($id!==''?'label':'div').'><div class="fb-admin-form-field">'.$content.($help!==''?'<div class="fb-admin-form-help">'.$help.'</div>':'').'</div></div>';
};
$wechatValue = function($key)use($current,$schema){return $current[$key]??$schema[$key]['default'];};
$wechatChoices = function($key,$options)use($wechatValue){
 $html='<div class="fb-admin-choice-row">';$value=$wechatValue($key);
 foreach($options as $option)$html.='<label><input type="radio" name="'.admin_helper::e($key).'" value="'.admin_helper::e($option[0]).'"'.($value===$option[0]?' checked':'').'>'.admin_helper::e($option[1]).'</label>';
 return $html.'</div>';
};
$wechatSecret = function($key,$type='password',$attributes='')use($wechatValue){return '<input class="txt" id="fb-'.$key.'" name="'.admin_helper::e($key).'" type="'.$type.'" autocomplete="new-password" value="" placeholder="'.($wechatValue($key)!==''?'已配置，留空保留':'未配置').'" '.$attributes.'>';};
$wechatSecretHint = function($key,$hint){return '<div class="fb-admin-form-hint">'.admin_helper::e($hint).'<label><input type="checkbox" name="clear_'.admin_helper::e($key).'" value="1">清除已保存密钥</label></div>';};
$wechatCopy = function($id,$value,$hint='')use($renderWechatRow){
 $renderWechatRow($id['label'],'<div class="fb-admin-input-line"><input type="text" class="txt" readonly id="'.admin_helper::e($id['id']).'" value="'.admin_helper::e($value).'"><button type="button" class="fb-admin-button" data-fb-copy="'.admin_helper::e($id['id']).'"'.($value===''?' disabled':'').'>复制</button><span role="status" aria-live="polite" data-fb-copy-status="'.admin_helper::e($id['id']).'"></span></div>',admin_helper::e($hint),$id['id']);
};
if($section === 'wechat') {
 echo '<input type="hidden" name="wechat_tab" id="fb-wechat-active-tab" value="'.admin_helper::e($wechatTab).'">';
 echo '<div class="fb-admin-tabs" role="tablist" aria-label="微信小程序配置" data-fb-wechat-tabs>';
 foreach($wechatTabs as $key=>$tab) {
  $active=$key===$wechatTab;
  echo '<button type="button" class="fb-admin-button fb-admin-tab'.($active?' is-active':'').'" role="tab" id="fb-wechat-tab-'.$key.'" aria-controls="fb-wechat-panel-'.$key.'" aria-selected="'.($active?'true':'false').'" tabindex="'.($active?'0':'-1').'" data-fb-wechat-tab="'.$key.'">'.$tab['label'].'</button>';
 }
 echo '</div>';
 foreach($wechatTabs as $key=>$tab) {
  echo '<section role="tabpanel" id="fb-wechat-panel-'.$key.'" aria-labelledby="fb-wechat-tab-'.$key.'" data-fb-wechat-panel="'.$key.'"'.($key===$wechatTab?'':' hidden').'>';
  echo '<div class="'.(in_array($key,['trade','customer'],true)?'fb-admin-wechat-content':(in_array($key,['release','subscribe','orders'],true)?'fb-admin-wechat-wide':'fb-admin-wechat-form')).'">';
  if($key==='login') {
   $renderWechatRow('启用微信小程序登录',$wechatChoices('wechat_enabled',[['1','是'],['0','否']]));
   $renderWechatRow('小程序名称','<input type="text" class="txt" id="fb-wechat_name" name="wechat_name" maxlength="80" value="'.admin_helper::e($wechatValue('wechat_name')).'">','','fb-wechat_name');
   $renderWechatRow('原始 ID','<input type="text" class="txt" id="fb-wechat_original_id" name="wechat_original_id" maxlength="80" value="'.admin_helper::e($wechatValue('wechat_original_id')).'">','在微信公众平台的小程序基本信息中查看原始 ID。','fb-wechat_original_id');
   $qr=admin_helper::cleanurl($wechatValue('wechat_qrcode'));
   $renderWechatRow('小程序二维码','<div data-fb-wechat-qrcode-field><input type="hidden" name="wechat_qrcode" value="'.admin_helper::e($qr).'"><div class="fb-admin-qrcode-preview"><img data-fb-wechat-qrcode-preview'.($qr!==''?' src="'.admin_helper::e($qr).'"':' hidden').' alt="小程序二维码"></div><div class="fb-admin-input-line"><button type="button" class="fb-admin-button" data-fb-wechat-qrcode-upload>上传图片</button><button type="button" class="fb-admin-button" data-fb-wechat-qrcode-clear'.($qr===''?' hidden':'').'>移除图片</button></div><span role="status" aria-live="polite" data-fb-wechat-qrcode-status></span></div>','建议上传 430 × 430 像素的二维码；上传存储使用文件存储配置。');
  } elseif(in_array($key,['orders','trade','customer'],true)) {
   flipbook\admin_commerce::render($key);
  } elseif($key==='develop') {
   $renderWechatRow('AppID','<input type="text" class="txt" id="fb-wechat_appid" name="wechat_appid" value="'.admin_helper::e($wechatValue('wechat_appid')).'" maxlength="18">','在微信公众平台的开发管理中查看 AppID。','fb-wechat_appid');
   $renderWechatRow('AppSecret',$wechatSecret('wechat_secret'),$wechatSecretHint('wechat_secret','密钥加密保存在服务端；留空保留已保存的密钥。'),'fb-wechat_secret');
  } elseif($key==='push') {
   $renderWechatRow('启用消息推送接收',$wechatChoices('wechat_push_enabled',[['1','是'],['0','否']]));
   $wechatCopy(['label'=>'服务器地址（URL）','id'=>'fb-wechat-push-url'],rtrim($_G['siteurl'],'/').'/plugin.php?id=flipbook:wechat_callback','复制到微信公众平台的消息推送配置；正式环境使用 HTTPS。');
   $renderWechatRow('令牌（Token）','<div class="fb-admin-input-line">'.$wechatSecret('wechat_push_token','text','minlength="3" maxlength="32" pattern="[A-Za-z0-9]{3,32}" aria-describedby="fb-wechat-token-hint" data-fb-count="32"').'<span data-fb-count-for="fb-wechat_push_token">0/32</span></div>','<div id="fb-wechat-token-hint">用于验证消息来自微信；填写 3 至 32 位字母或数字。启用推送时必填，已配置可留空保留。</div>'.$wechatSecretHint('wechat_push_token','清除后需要重新配置微信公众平台的 Token。'),'fb-wechat_push_token',true);
   $renderWechatRow('消息加解密密钥（EncodingAESKey）','<div class="fb-admin-input-line">'.$wechatSecret('wechat_push_aes','password','minlength="43" maxlength="43" pattern="[A-Za-z0-9+/]{43}" aria-describedby="fb-wechat-aes-hint" data-fb-count="43"').'<span data-fb-count-for="fb-wechat_push_aes">0/43</span></div>','<div id="fb-wechat-aes-hint">用于消息体加解密；填写微信公众平台生成的 43 位 EncodingAESKey。兼容模式和安全模式必填，已配置可留空保留。</div>'.$wechatSecretHint('wechat_push_aes','密钥加密保存在服务端，不回填页面。'),'fb-wechat_push_aes',true);
   $renderWechatRow('消息加密方式',$wechatChoices('wechat_push_mode',[['plain','明文模式'],['compatible','兼容模式'],['encrypted','安全模式（推荐）']]),'<div>明文模式：消息体不加密，使用 Token 校验消息来源。</div><div>兼容模式：同时支持明文和加密消息，使用 EncodingAESKey 解密消息。</div><div>安全模式：接收加密消息，使用 Token 和 EncodingAESKey 校验并解密。</div>');
  } elseif($key==='server') {
   $domains=flipbook\front_service_config::server_domains();
   foreach(['request'=>'request 合法域名','socket'=>'socket 合法域名','upload'=>'uploadFile 合法域名','download'=>'downloadFile 合法域名'] as $domainKey=>$label)$wechatCopy(['label'=>$label,'id'=>'fb-wechat-domain-'.$domainKey],$domains[$domainKey],$domainKey==='socket'?'当前未使用 WebSocket 服务。':($domainKey==='download'?'来源为当前站点和已启用的 COS 访问域名；多个域名请按分号分别添加。':'来源为当前站点地址；本地开发环境显示实际 HTTP 地址。'));
  } elseif($key==='release') {
   flipbook\admin_wechat::release();
  } else {
   /* Subscription templates have their own saved field mapping. */
   flipbook\admin_wechat::subscribe();
  }
  echo '</div>';
  echo '</section>';
 }
 showtableheader();
} elseif($section==='admincp') {
 echo '<input type="hidden" name="settings_tab" data-fb-settings-active value="'.admin_helper::e($settingsTab).'">';
 echo '<div class="fb-admin-tabs fb-admin-settings-tabs" role="tablist" aria-label="画册设置" data-fb-settings-tabs>';
 foreach(['basic'=>'基础设置','share'=>'商品分享'] as $key=>$label){$on=$settingsTab===$key;echo '<button type="button" class="fb-admin-button fb-admin-tab'.($on?' is-active':'').'" role="tab" id="fb-settings-tab-'.$key.'" aria-controls="fb-settings-panel-'.$key.'" aria-selected="'.($on?'true':'false').'" tabindex="'.($on?'0':'-1').'" data-fb-settings-tab="'.$key.'">'.$label.'</button>';}
 echo '</div>';
 foreach(['basic','share'] as $key){
  echo '<section role="tabpanel" id="fb-settings-panel-'.$key.'" aria-labelledby="fb-settings-tab-'.$key.'" data-fb-settings-panel="'.$key.'"'.($settingsTab===$key?'':' hidden').'>';
  showtableheader();
  foreach($schema as $skey=>$field)if((($field['group']??'基础设置')==='商品分享')===($key==='share'))$renderField($skey,$field);
  showtablefooter();echo '</section>';
 }
 showtableheader();
} else {
 $title=['admincp'=>'基础设置','storage'=>'文件存储'][$section]??'基础设置';
 showtableheader($title);$group=$section==='storage'?'文件存储':'基础设置';
 foreach($schema as $skey=>$field) {
  $next=$field['group']??'基础设置';
  if($next!==$group) {showtablefooter();showtableheader($next);$group=$next;}
  $renderField($skey,$field);
 }
}
ob_start();showsubmit('flipbooksubmit');$submitMarkup=ob_get_clean();
echo preg_replace('/<tr(?=[\s>])/', '<tr data-wechat-general-save'.($section==='wechat' && in_array($wechatTab,['subscribe','release','orders','trade','customer'],true)?' hidden':''),str_replace('class="btn"','class="btn fb-admin-button"',$submitMarkup),1);
showtablefooter();
showformfooter();
if($section === 'wechat') {
 echo '<script src="source/plugin/flipbook/static/admin/wechat.js?v='.@filemtime(__DIR__.'/static/admin/wechat.js').'" defer></script><script src="source/plugin/flipbook/static/admin/wechat-release.js?v='.@filemtime(__DIR__.'/static/admin/wechat-release.js').'" defer></script>';
}


if($section==='wechat')echo '<script src="source/plugin/flipbook/static/admin/wechat-commerce.js?v='.@filemtime(__DIR__.'/static/admin/wechat-commerce.js').'" defer></script>';

if($section==='admincp')echo '<script src="source/plugin/flipbook/static/admin/settings.js?v='.@filemtime(__DIR__.'/static/admin/settings.js').'" defer></script>';
