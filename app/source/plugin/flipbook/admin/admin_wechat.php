<?php
namespace flipbook;
if(!defined('IN_DISCUZ')||!defined('IN_ADMINCP'))exit('Access Denied');

class admin_wechat {
 public static function dispatch(){
  $action=is_string($_GET['fb_wechat_action']??null)?$_GET['fb_wechat_action']:'';if($action==='')return;admin_commerce::dispatch($action);
  admin_helper::needperm();$mutating=!in_array($action,['versions'],true);
  try{
   if($mutating){admin_helper::needperm($action==='start'?'publish':'edit');if($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals((string)formhash(),(string)($_POST['formhash']??'')))throw new \InvalidArgumentException('管理会话已过期，请刷新页面后重试');}
   if($action==='versions')$data=front_wechat_release::list($_GET['page']??1,$_GET['pageSize']??10);
   elseif($action==='release-save'){
    $values=[];$keys=['wechat_appid','wechat_ci_url','wechat_ci_token','wechat_ci_api_base','wechat_release_version','wechat_release_description'];
    foreach($keys as $key){if(!is_scalar($_POST[$key]??''))throw new \InvalidArgumentException('设置格式不正确');$values[$key]=trim((string)($_POST[$key]??''));}
    $private=self::uploaded('private_key',20000,['key','pem'],false);if($private!=='')$values['wechat_upload_private_key']=$private;
    $clear=[];foreach(['wechat_ci_token','wechat_upload_private_key'] as $key)if(!empty($_POST['clear_'.$key])){$clear[]=$key;$values[$key]='';}
    $updates=front_service_config::prepare_updates($values,$clear);foreach($updates as $key=>$value)table_flipbook_setting::t()->update_setting($key,$value,'1');
    $data=['configured'=>front_wechat_release::configured(),'message'=>'上传代码配置已保存'];
   }elseif($action==='start')$data=front_wechat_release::start($_POST,self::uploaded('archive',22*1024*1024,['zip'],($_POST['source']??'')==='local'));
   elseif($action==='remove')$data=front_wechat_release::remove((string)($_POST['id']??''));
   elseif($action==='templates')$data=['templates'=>front_wechat_subscription::templates()];
   elseif($action==='subscribe-save'){
    $raw=$_POST['config']??'';if(!is_string($raw)||strlen($raw)>30000)throw new \InvalidArgumentException('模板配置过长或无效');
    $clean=front_wechat_subscription::validate(json_decode($raw,true));$updates=front_service_config::prepare_updates(['wechat_subscribe_config'=>json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);table_flipbook_setting::t()->update_setting('wechat_subscribe_config',$updates['wechat_subscribe_config'],'1');$data=['config'=>$clean,'message'=>'订阅消息配置已保存'];
   }else throw new \InvalidArgumentException('操作不存在');
   self::respond(['ok'=>1]+$data);
  }catch(\Throwable $error){self::respond(['ok'=>0,'message'=>$error instanceof \RuntimeException||$error instanceof \InvalidArgumentException?$error->getMessage():'操作失败，请检查插件日志']);}
 }
 private static function uploaded($key,$limit,$extensions,$required){
  $file=$_FILES[$key]??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){if($required)throw new \InvalidArgumentException('请选择代码包');return '';}
  if(is_array($file['name']??null)||($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??'')||intval($file['size']??0)<1||intval($file['size'])>$limit||!in_array(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION)),$extensions,true))throw new \InvalidArgumentException($key==='archive'?'代码包需为 22MB 以内的 ZIP':'私钥需为 20KB 以内的 .key 或 .pem 文件');
  $body=file_get_contents($file['tmp_name']);if($body===false||strlen($body)>$limit)throw new \InvalidArgumentException('上传文件读取失败');return $body;
 }
 private static function respond($data){if(ob_get_level()>0)ob_end_clean();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
 private static function row($label,$body,$help=''){echo '<div class="fb-admin-form-row"><div class="fb-admin-form-label">'.admin_helper::e($label).'</div><div class="fb-admin-form-field">'.$body.($help!==''?'<p class="fb-admin-form-help">'.$help.'</p>':'').'</div></div>';}
 public static function release(){
  $e=[admin_helper::class,'e'];$configured=front_wechat_release::configured();
  echo '<div class="fb-wechat-release" data-release data-configured="'.($configured?'1':'0').'" data-endpoint="'.$e(admin_helper::url('wechat')).'" data-formhash="'.$e(formhash()).'">';
  echo '<div class="fb-admin-tabs" role="tablist" aria-label="版本推送"><button type="button" id="fb-release-tab-versions" class="fb-admin-button fb-admin-tab is-active" role="tab" aria-selected="true" aria-controls="fb-release-versions" data-release-tab="versions">发布记录</button><button type="button" id="fb-release-tab-settings" class="fb-admin-button fb-admin-tab" role="tab" aria-selected="false" tabindex="-1" aria-controls="fb-release-settings" data-release-tab="settings">上传代码</button></div>';
  echo '<section role="tabpanel" aria-labelledby="fb-release-tab-versions" id="fb-release-versions" data-release-panel="versions"><div class="fb-release-compose"><label>版本号<input data-release-version maxlength="32" value="'.$e(front_service_config::get('wechat_release_version')).'"></label><label class="fb-release-description">版本说明<input data-release-description maxlength="200" value="'.$e(front_service_config::get('wechat_release_description')).'"></label><button type="button" class="fb-admin-button" data-release-start="cloud">一键云端发布</button><button type="button" class="fb-admin-button" data-release-local>本地发布</button><input type="file" accept=".zip" data-release-archive hidden></div>';
  echo '<p class="fb-admin-form-help">自动检查、编译打包并上传开发版本；上传成功后在微信公众平台提交审核和正式发布。本地发布请上传包含 project.config.json 的完整源码 ZIP。</p><p data-release-status role="status" aria-live="polite"></p>';
  echo '<div class="fb-wechat-table-wrap"><table class="fb-wechat-table"><thead><tr><th>版本号</th><th>状态</th><th>来源</th><th>创建时间</th><th>说明</th><th>操作</th></tr></thead><tbody data-release-rows><tr><td colspan="6">'.($configured?'正在读取发布记录…':'请先在“上传代码”完成配置').'</td></tr></tbody></table></div>';
  echo '<div class="fb-release-pagination"><span data-release-total>共 0 条</span><select aria-label="每页记录数量" data-release-size><option value="10">10 条/页</option><option value="20">20 条/页</option><option value="50">50 条/页</option></select><button type="button" class="fb-admin-button" data-release-prev disabled>上一页</button><span data-release-page>第 1 页</span><button type="button" class="fb-admin-button" data-release-next disabled>下一页</button><button type="button" class="fb-admin-button" data-release-refresh>刷新记录</button></div></section>';
  echo '<section role="tabpanel" aria-labelledby="fb-release-tab-settings" id="fb-release-settings" data-release-panel="settings" hidden><div class="fb-admin-wechat-form">';
  self::row('AppID','<input data-release-setting="wechat_appid" aria-label="代码上传 AppID" maxlength="18" value="'.$e(front_service_config::get('wechat_appid')).'">','与小程序开发信息使用同一 AppID。');
  $keyset=front_service_config::get('wechat_upload_private_key')!=='';
  self::row('代码上传私钥','<input type="file" accept=".key,.pem" data-release-private-key aria-label="上传代码私钥"><span data-release-key-status>'.($keyset?'已配置；未选择新文件则保留':'尚未配置').'</span><label><input type="checkbox" data-release-clear="wechat_upload_private_key">清除已保存私钥</label>','上传微信公众平台下载的代码上传私钥，加密保存在服务端，不回填页面。');
  self::row('上传 IP 白名单','<code>106.55.0.61</code>','在微信公众平台 → 开发管理 → 开发设置 → 小程序代码上传中，将此服务器公网出口 IP 加入白名单；实际构建服务需从该 IP 上传。');
  self::row('小程序接口地址','<input data-release-setting="wechat_ci_api_base" type="url" aria-label="小程序接口地址" placeholder="https://album.example.com" value="'.$e(front_service_config::get('wechat_ci_api_base')).'">','编译时写入副本的 API_BASE，使用已配置微信合法域名的 HTTPS 站点。');
  self::row('构建服务地址','<input data-release-setting="wechat_ci_url" type="url" aria-label="构建服务地址" placeholder="https://ci.example.com" value="'.$e(front_service_config::get('wechat_ci_url')).'">','连接本插件附带的构建服务；云端使用该服务已配置的画册小程序源码。');
  self::row('构建服务通信密钥','<input data-release-setting="wechat_ci_token" type="password" autocomplete="new-password" aria-label="构建服务通信密钥" placeholder="'.(front_service_config::get('wechat_ci_token')!==''?'已配置，留空保留':'与构建服务一致，至少32位').'"><label><input type="checkbox" data-release-clear="wechat_ci_token">清除已保存通信密钥</label>');
  self::row('默认版本号','<input data-release-setting="wechat_release_version" aria-label="默认版本号" maxlength="32" value="'.$e(front_service_config::get('wechat_release_version')).'">');
  self::row('默认版本说明','<textarea data-release-setting="wechat_release_description" aria-label="默认版本说明" maxlength="200" rows="3">'.$e(front_service_config::get('wechat_release_description')).'</textarea>');
  echo '<div class="fb-admin-form-row"><div></div><div><button type="button" class="fb-admin-button" data-release-save>保存上传配置</button><p data-release-config-status role="status" aria-live="polite"></p></div></div></div></section><dialog class="fb-wechat-dialog" data-release-detail><div data-release-detail-content></div><div class="fb-wechat-dialog-actions"><button type="button" class="fb-admin-button" data-close-dialog>关闭</button></div></dialog></div>';
 }
 public static function subscribe(){
  $e=[admin_helper::class,'e'];$config=front_wechat_subscription::config();$definitions=front_wechat_subscription::definitions();$appname=front_view::settings()['albumname'];$hasRows=(bool)array_filter($config,static fn($row)=>!empty($row['templateId'])&&!empty($row['fields']));
  echo '<div data-wechat-subscribe data-endpoint="'.$e(admin_helper::url('wechat')).'" data-formhash="'.$e(formhash()).'"><p class="fb-admin-form-help">通知内容使用当前画册的商品与画册资料。先从本小程序的微信模板中选择，匹配字段后启用；模板内容和数量以微信公众平台为准。</p><p data-subscribe-status role="status" aria-live="polite"></p><button type="button" class="fb-admin-button" data-subscribe-fetch="product">获取微信模板</button><div class="fb-wechat-table-wrap"><table class="fb-wechat-table" data-subscribe-table'.($hasRows?'':' hidden').'><thead><tr><th>所属应用</th><th>通知类型</th><th>消息类型</th><th>内容与字段</th><th>状态</th><th>模板编号</th><th>操作</th></tr></thead><tbody data-subscribe-rows>';
  foreach($definitions as $kind=>$definition){$row=$config[$kind]??[];if(empty($row['templateId'])||empty($row['fields']))continue;echo '<tr data-subscribe-kind="'.$kind.'"><td>'.$e($appname).'</td><td>'.$e($row['title']).'</td><td>用户订阅消息</td><td data-subscribe-fields>';foreach($row['fields']??[] as $field)echo '<p>'.$e($field['label']).'：'.$e($definition['fields'][$field['source']]).' <code>'.$e($field['key']).'</code></p>';echo '</td><td data-subscribe-enabled>'.(!empty($row['enabled'])?'启用':'未启用').'</td><td class="fb-template-id" data-subscribe-id>'.$e($row['templateId']).'</td><td><div class="fb-wechat-row-actions"><button type="button" class="fb-admin-button" data-subscribe-toggle="'.$kind.'"'.(empty($row['fields'])?' disabled':'').'>'.(!empty($row['enabled'])?'关闭':'启用').'</button><button type="button" class="fb-admin-button" data-subscribe-fetch="'.$kind.'">重新获取</button></div></td></tr>';}
  echo '</tbody></table></div><script type="application/json" data-subscribe-config>'.json_encode(['config'=>$config,'definitions'=>$definitions,'appname'=>$appname],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP).'</script>';
  $legacy=front_service_config::get('wechat_subscribe_templates');if($legacy!=='')echo '<details class="fb-wechat-legacy"><summary>查看历史模板配置</summary><pre>'.$e($legacy).'</pre><p>历史配置保留；可在上方重新选择对应业务模板。</p></details>';
  echo '<dialog class="fb-wechat-dialog" data-subscribe-dialog><h3>选择微信模板并匹配内容</h3><label>对应资料<select data-subscribe-purpose aria-label="对应资料"><option value="product">商品资料</option><option value="album">画册资料</option></select></label><label>微信模板<select data-subscribe-template aria-label="微信模板"></select></label><div data-subscribe-mapping></div><p data-subscribe-dialog-status role="status"></p><div class="fb-wechat-dialog-actions"><button type="button" class="fb-admin-button" data-close-dialog>取消</button><button type="button" class="fb-admin-button" data-subscribe-save>保存模板</button></div></dialog></div>';
 }
}
