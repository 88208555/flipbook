<?php
namespace flipbook_sync;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

class admin_view {
 public static function e($value) {return dhtmlspecialchars((string)$value);}
 public static function render($tab) {
  \flipbook\admin_helper::assets();
  $asset='source/plugin/flipbook_sync/static/';
  echo '<link rel="stylesheet" href="'.$asset.'admin.css?v='.@filemtime(__DIR__.'/../static/admin.css').'">';
  echo '<main class="fb-sync" data-sync-page="'.self::e($tab).'" data-endpoint="'.self::e(admin_controller::url($tab)).'" data-formhash="'.self::e(formhash()).'">';
  echo '<div class="fb-sync-toolbar"><nav class="fb-admin-tabs fb-sync-tabs" aria-label="产品同步">';
  foreach(['admincp'=>'域名授权','receive'=>'数据源','guide'=>'调用说明','data'=>'调用数据'] as $key=>$label) echo '<a class="fb-admin-tab'.($tab===$key?' is-active':'').'" '.($tab===$key?'aria-current="page" ':'').'href="'.self::e(admin_controller::url($key)).'">'.$label.'</a>';
  echo '</nav><div class="fb-sync-toolbar-actions">';
  if($tab==='admincp'){self::help_button('grants');echo '<button type="button" class="fb-admin-button" data-sync-new="grant">新增授权</button>';}
  elseif($tab==='receive'){echo '<button class="fb-admin-button" type="button" data-sync-action="local-toggle">'.(service_fields::local_enabled()?'关闭外部调用':'开放外部调用').'</button>';self::help_button('sources');echo '<button type="button" class="fb-admin-button" data-sync-new="source">添加外部数据源</button>';}
  elseif($tab==='data')self::help_button('data');
  echo '</div></div><p class="fb-sync-status" role="status" aria-live="polite" data-sync-status></p>';
  if($tab==='admincp') self::grants();
  elseif($tab==='receive') self::sources();
  elseif($tab==='data') self::data();
  else self::guide();
  self::help_dialog();
  echo '</main><script src="'.$asset.'admin.js?v='.@filemtime(__DIR__.'/../static/admin.js').'" defer></script>';
 }
 private static function heading($title,$button='',$kind='',$help='') {
  echo '<div class="fb-sync-heading"><div class="fb-sync-title"><h3>'.$title.'</h3>';
  if($help!=='') self::help_button($help);
  echo '</div>';
  if($button!=='') echo '<button type="button" class="fb-admin-button" data-sync-new="'.$kind.'">'.$button.'</button>';
  echo '</div>';
 }
 private static function grants() {
  echo '<section class="fb-sync-issued" data-sync-issued hidden><h3>请保存本次密钥</h3><p>密钥只显示这一次，离开页面后无法再次查看。请交给对应项目的服务端使用。</p><div class="fb-sync-line"><input aria-label="本次生成的密钥" readonly data-issued-key><button class="fb-admin-button" type="button" data-copy-issued>复制密钥</button><button class="fb-admin-button" type="button" data-dismiss-issued>已保存</button><button class="fb-admin-button" type="button" data-refresh-list>刷新列表</button></div></section>';
  $rows=DB::fetch_all('SELECT clientid,domain,note,ips,enabled,created,lastused FROM %t ORDER BY clientid DESC',['flipbook_sync_key']);
  echo '<div class="fb-sync-table-wrap"><table class="fb-sync-table"><thead><tr><th>域名</th><th>备注</th><th>状态</th><th>有效期</th><th>最近调用</th><th>操作</th></tr></thead><tbody>';
  foreach($rows as $row) {
   $formData=['clientid'=>intval($row['clientid']),'domain'=>$row['domain'],'note'=>$row['note'],'ips'=>$row['ips'],'fields'=>service_fields::allowed(0)];
   $data=self::e(json_encode($formData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   echo '<tr><td>'.self::e($row['domain']).'</td><td>'.self::e($row['note']).'</td><td>'.($row['enabled']?'启用':'停用').'</td><td>长期有效</td><td>'.($row['lastused']?self::e(dgmdate($row['lastused'],'Y-m-d H:i')):'尚未调用').'</td><td><div class="fb-sync-row-actions"><button class="fb-admin-button" type="button" data-edit-grant="'.$data.'">编辑</button><button class="fb-admin-button" type="button" data-sync-action="key-toggle" data-record="'.$row['clientid'].'">'.($row['enabled']?'停用':'启用').'</button><button class="fb-admin-button" type="button" data-sync-action="key-rotate" data-record="'.$row['clientid'].'">重新生成</button></div></td></tr>';
  }
  if(!$rows) echo '<tr><td colspan="6" class="fb-sync-empty">暂无授权域名，点击右上角新增授权。</td></tr>';
  echo '</tbody></table></div>';
  echo '<dialog class="fb-sync-dialog fb-sync-grant-dialog" data-grant-dialog data-shared-fields="'.self::e(json_encode(service_fields::allowed(0))).'"><form method="post" data-sync-form="grant"><h3 data-dialog-title>新增授权</h3><input name="recordid" type="hidden" value="0"><div class="fb-sync-grant-columns"><section class="fb-sync-grant-info"><h4>授权信息</h4>';
  self::field('域名','domain','text','client.example.com',true);
  self::field('备注','note','text','合作方或项目名称');
  self::field('服务器 IP（可选）','ips','textarea','每行一个固定 IP，留空不限制');
  self::help_button('grant-fields','域名和 IP 说明');
  echo '</section><section class="fb-sync-grant-fields">';self::permissions();echo '</section></div>';
  self::dialog_end();
 }
 private static function permissions() {
  echo '<section class="fb-sync-permissions"><div class="fb-sync-line"><div class="fb-sync-title"><h3>调用字段</h3>';self::help_button('fields');echo '</div><button type="button" class="fb-admin-button" data-fields-all="1">全部开启</button><button type="button" class="fb-admin-button" data-fields-all="0">全部关闭</button></div>';
  foreach(service_fields::groups() as $group=>$definition) {
   echo '<fieldset class="fb-sync-field-group"><legend>'.self::e($definition['label']).'</legend><div>';
   foreach($definition['fields'] as $field=>$label) { $path=($group==='product'?'':$group.'.').$field;
    echo '<label class="fb-sync-field-switch"><input type="checkbox" name="fields[]" value="'.self::e($path).'" checked><span>'.self::e($label).'<small>'.self::e($path).'</small></span></label>';
   }
   echo '</div></fieldset>';
  }
  echo '</section>';
 }
 private static function data() {
  echo '<section data-data-panel="list"><div class="fb-sync-source-tabs" role="group" aria-label="列表类型"><button class="fb-admin-tab is-active" type="button" aria-pressed="true" data-catalog-kind="local">本地列表</button><button class="fb-admin-tab" type="button" aria-pressed="false" data-catalog-kind="remote">数据源列表</button></div><section data-local-catalog>';
  echo '</section><section data-remote-catalog hidden><div class="fb-sync-source-tabs" role="group" aria-label="来源站点">';
  $sources=DB::fetch_all('SELECT sourceid,name,endpoint,enabled FROM %t ORDER BY sourceid DESC',['flipbook_sync_source']);
  foreach($sources as $row) { $domain=parse_url($row['endpoint'],PHP_URL_HOST);
   echo '<button type="button" class="fb-admin-tab" data-browse-source="'.intval($row['sourceid']).'" aria-pressed="false" title="'.self::e($row['name'].' · '.$row['endpoint']).'"'.(!$row['enabled']?' disabled':'').'>'.self::e($domain?:$row['name']).(!$row['enabled']?'（停用）':'').'</button>';
  }
  echo '</div><nav class="fb-sync-source-tabs" aria-label="外部数据类型">';foreach(['category'=>'商品分类','series'=>'商品系列','supplier'=>'供应商户','product'=>'商品列表'] as $kind=>$title)echo '<button type="button" class="fb-admin-tab" data-entity-kind="'.$kind.'">'.$title.'</button>';echo '</nav><a class="fb-admin-button" href="'.self::e(admin_controller::native_url(['view'=>'drafts','origin'=>'external'])).'">打开外部草稿箱</a></section><p data-catalog-status role="status" aria-live="polite"></p><div class="fb-sync-table-wrap"><table class="fb-sync-table"><thead><tr><th>图片</th><th>名称 / 编码</th><th>售价</th><th>来源状态</th><th>本站状态</th><th>操作</th></tr></thead><tbody data-catalog-rows></tbody></table></div><div class="fb-sync-catalog-pages"><button type="button" class="fb-admin-button" data-catalog-prev disabled>上一页</button><span data-catalog-page>第 1 页</span><button type="button" class="fb-admin-button" data-catalog-next disabled>下一页</button><button type="button" class="fb-admin-button" data-catalog-refresh disabled>刷新列表</button></div></section>';
  echo '<dialog class="fb-sync-dialog" data-match-dialog><form data-match-form><h3>字段与关系匹配</h3><p>分类、系列和供应商只能选择同类型记录，或明确新建。匹配通过后才能加入草稿箱。</p><div data-match-fields></div><p class="is-error" data-match-status role="status"></p><div class="fb-sync-dialog-actions"><button type="button" class="fb-admin-button" data-sync-cancel>取消</button><button type="submit" class="fb-admin-button">保存匹配</button></div></form></dialog>';
 }
 private static function sources() {
  global $_G;
  echo '<section class="fb-sync-progress" data-sync-progress hidden><h3>同步进度</h3><progress max="100" value="0" data-sync-meter></progress><p data-sync-count></p><button class="fb-admin-button" type="button" data-sync-stop>停止</button></section>';
  $rows=DB::fetch_all('SELECT sourceid,name,endpoint,domain,enabled,lastsync FROM %t ORDER BY sourceid DESC',['flipbook_sync_source']);
  echo '<div class="fb-sync-table-wrap"><table class="fb-sync-table"><thead><tr><th>数据源</th><th>来源站点</th><th>状态</th><th>最近同步</th><th>操作</th></tr></thead><tbody>';
  foreach($rows as $row) {
   $formData=['sourceid'=>intval($row['sourceid']),'name'=>$row['name'],'endpoint'=>$row['endpoint']];
   $data=self::e(json_encode($formData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   echo '<tr><td>'.self::e($row['name']).'</td><td>'.self::e($row['endpoint']).'</td><td>'.($row['enabled']?'启用':'停用').'</td><td data-source-last="'.$row['sourceid'].'">'.($row['lastsync']?self::e(dgmdate($row['lastsync'],'Y-m-d H:i')):'尚未同步').'</td><td><div class="fb-sync-row-actions"><button class="fb-admin-button" type="button" data-edit-source="'.$data.'">编辑</button><button class="fb-admin-button" type="button" data-sync-action="source-toggle" data-record="'.$row['sourceid'].'">'.($row['enabled']?'停用':'启用').'</button><button class="fb-admin-button" type="button" data-sync-action="source-test" data-record="'.$row['sourceid'].'"'.(!$row['enabled']?' disabled':'').'>测试连接</button><button class="fb-admin-button" type="button" data-sync-start="'.$row['sourceid'].'"'.(!$row['enabled']?' disabled':'').'>一键同步</button></div></td></tr>';
  }
  if(!$rows) echo '<tr><td colspan="5" class="fb-sync-empty">暂无数据源</td></tr>';
  echo '</tbody></table></div>';
  echo '<dialog class="fb-sync-dialog" data-source-dialog><form method="post" data-sync-form="source"><h3 data-dialog-title>添加数据源</h3><input name="recordid" type="hidden" value="0">';
  self::field('数据源名称','name','text','来源站名称',true);
  self::field('来源站点','endpoint','url','https://supplier.example.com/',true);
  self::field('调用密钥','source_key','password','编辑时留空保留已有密钥');
  self::help_button('source-fields','来源站点与密钥说明');
  self::dialog_end();
  $logs=DB::fetch_all('SELECT l.message,l.dateline,s.name FROM %t l LEFT JOIN %t s ON l.sourceid=s.sourceid ORDER BY l.logid DESC LIMIT 20',['flipbook_sync_log','flipbook_sync_source']);
  echo '<h3 class="fb-sync-log-heading">最近同步记录</h3><div class="fb-sync-logs">';
  foreach($logs as $log) echo '<p>'.self::e(dgmdate($log['dateline'],'Y-m-d H:i:s')).' · '.self::e($log['name']??'').' · '.self::e($log['message']).'</p>';
  if(!$logs) echo '<p>尚无同步记录。</p>';
  echo '</div>';
 }
 private static function field($title,$name,$type,$placeholder='',$required=false) {
  echo '<label class="fb-sync-field"><span>'.$title.'</span>';
  if($type==='textarea') echo '<textarea name="'.$name.'" placeholder="'.self::e($placeholder).'" rows="3"></textarea>';
  else echo '<input '.($name==='source_key'?'data-source-key':'name="'.$name.'"').' type="'.$type.'" placeholder="'.self::e($placeholder).'" autocomplete="'.($type==='password'?'new-password':'off').'"'.($required?' required':'').'>';
  echo '</label>';
 }
 private static function dialog_end() {
  echo '<p class="fb-sync-dialog-status" role="status" data-form-status></p><div class="fb-sync-dialog-actions"><button type="button" class="fb-admin-button" data-sync-cancel>取消</button><button type="submit" class="fb-admin-button">保存</button></div></form></dialog>';
 }
 private static function guide() {
  global $_G;
  echo '<section class="fb-sync-guide">';$url=rtrim($_G['siteurl'],'/').'/plugin.php?id=flipbook_sync:api';
  echo '<label class="fb-sync-field"><span>接口地址</span><div class="fb-sync-line"><input readonly value="'.self::e($url).'" data-api-address aria-label="接口地址"><button class="fb-admin-button" type="button" data-copy-api>复制</button></div></label>';
  echo '<h3>请求方式</h3><pre>GET ?id=flipbook_sync:api&amp;action=products&amp;limit=20&amp;cursor=0
Authorization: Bearer YOUR_KEY
X-Flipbook-Domain: client.example.com
Accept: application/json</pre>';
  echo '<p>密钥仅放服务端，通过请求头传递。不要放入 URL、H5 页面或小程序包。域名是授权身份标识；凭证持有人能够填写请求头，需要限制调用机器时配置固定服务器 IP。</p>';
  echo '<table class="fb-sync-table"><thead><tr><th>操作</th><th>参数</th><th>返回</th></tr></thead><tbody><tr><td>info</td><td>action=info</td><td>协议版本、来源标识、商品总数和编号上界</td></tr><tr><td>products</td><td>cursor：上一批 next_cursor；limit：1–100；upper：首次 upper_bound</td><td>获授权的商品字段、next_cursor、has_more</td></tr><tr><td>product</td><td>action=product&amp;productid=商品编号</td><td>单个商品的获授权字段</td></tr></tbody></table>';
  echo '<h3>字段开关与互相调用</h3><p>调用字段在授权弹窗中统一设置，所有域名共用一份配置，保存后同时作用于列表与详情 API。响应 allowed_fields 给出当前授权字段；未授权字段直接省略，部分授权同步时保留本地对应旧值，新商品使用空值或未上架默认值。“调用数据”按站点查看已授权的商品，每行“一键上架”先加入草稿箱，再编辑确认后上架。字段未匹配时显示红色并阻止导入。两站互相调用需各自配置对方数据源及域名授权。</p>';
  echo '<h3>同步内容</h3><p>SPU、价格、商品说明、规格、自定义字段、尺码表、SKU 及图片、卖点/细节/模特图组、首页展示、分类层级、系列与供应商名称编码。供应商联系人、电话等资料不对外输出。</p>';
  echo '<h3>更新规则</h3><p>同类项目安装此插件后，在“数据源”保存来源站点和本站专属密钥，测试连接后点击“一键同步”。以来源站与来源商品编号建立映射，内容未变化则跳过。失败后再次点击可继续；完成后再次点击会重新检查全部商品。来源下架状态会同步，来源硬删除的商品保留在接收端，不自动删除。同步期间新增的来源商品在下次同步时获取。</p>';
  echo '<h3>接入其他程序</h3><p>按 has_more 分页读取，以 instanceid + productid 为外部唯一标识进行新增或更新；图片视频直接使用返回的绝对地址。来源站需提供公网 HTTPS 与可访问素材。详细字段及 PHP 示例见项目交付的《产品同步插件使用说明》。</p>';
  echo '</section>';
 }
 private static function help_button($key,$label='') {
  echo '<button type="button" class="fb-sync-help" data-sync-help="'.self::e($key).'" aria-label="'.self::e($label?:'查看说明').'" title="'.self::e($label?:'查看说明').'"><span aria-hidden="true">?</span>'.($label!==''?'<span>'.self::e($label).'</span>':'').'</button>';
 }
 private static function help_dialog() {
  echo '<dialog class="fb-sync-dialog fb-sync-help-dialog" data-help-dialog aria-labelledby="sync-help-title"><div class="fb-sync-heading"><h3 id="sync-help-title">使用说明</h3><button type="button" class="fb-admin-button" data-help-close aria-label="关闭说明">关闭</button></div><div data-help-content></div></dialog>';
  $help=[
   'grants'=>['域名授权','每个域名使用独立、长期有效的密钥。停用后立即禁止调用；重新生成后旧密钥失效。密钥仅在创建时显示，请在服务端妥善保存。'],
   'grant-fields'=>['域名和 IP','域名精确匹配，子域名需单独授权。密钥是实际访问凭证；如需限制调用服务器，请同时填写固定 IP。留空 IP 不限制服务器。'],
   'fields'=>['API 字段授权','所有授权域名共用此字段配置。关闭字段后，列表和详情接口均省略该字段。商品及关联编号、来源标识、分页参数与校验值属于协议必需项，保留时不包含商品内容。字段修改需点击保存。外部数据源的字段由来源站授权，本站不能修改对方授权。'],
   'data'=>['调用数据','本地列表预览本站按授权字段提供的数据；数据源列表查看外部站点允许调用的内容。字段权限只在域名授权弹窗设置。外部记录先通过字段匹配加入草稿箱，再编辑确认上架。两站互相调用时，各自配置对方的数据源与域名授权。'],
   'sources'=>['本地与外部数据源','本地数据源控制本站是否对已授权域名开放调用。外部数据源用于主动读取其他站点；请在来源站为本站域名创建密钥，再填写其地址和密钥。图片和视频保留原站地址。同步进度与结果在页面状态和最近同步记录中查看。'],
   'source-fields'=>['来源站点与密钥','来源地址保存后不可替换，需要更换来源站时请新增数据源。编辑时密钥留空会保留原密钥。本站已有商品编码冲突时会报错，不覆盖自建商品。'],
  ];
  foreach($help as $key=>$row) echo '<template data-help-template="'.$key.'"><h4>'.self::e($row[0]).'</h4><p>'.self::e($row[1]).'</p></template>';
 }

}
