<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台：微页面
 *
 * 左侧组件库、中间编辑画布、右侧配置面板；每页（首页/详情/收藏/分类/搜索/我的）
 * 独立保存，保存后版本号递增，小程序与 H5 按版本号自动热加载。
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

use flipbook\admin_helper;
use flipbook\front_helper;
use flipbook\front_micro;

$op = isset($_GET['op']) ? (string)$_GET['op'] : '';

if(in_array($op,['rename','delete'],true)) {
	admin_helper::needperm($op==='delete'?'delete':'edit');
	if(!submitcheck('flipbookmicrochangesubmit')) admin_helper::failed('表单校验失败，未修改页面');
	try {
		if($op==='rename') flipbook\admin_micropage::rename($_POST);
		else flipbook\admin_micropage::remove($_POST);
	} catch(\RuntimeException $error) { admin_helper::failed($error->getMessage()); }
	admin_helper::succeeded($op==='rename'?'页面已重命名，访问地址保持不变':'微页面已删除','micropage');
}

if($op === 'create') {
	admin_helper::needperm('add');
	if(!submitcheck('flipbookmicrocreatesubmit')) admin_helper::failed('表单校验失败，未创建页面');
	try {
		$created = flipbook\admin_micropage::create($_POST);
	} catch(\RuntimeException $error) {
		admin_helper::failed($error->getMessage());
	}
	admin_helper::succeeded('微页面已创建，路由和链接已自动配置', 'micropage', ['op'=>'edit','key'=>$created['key']]);
}

// ----------------------------------------------------------------
// 图片/视频上传：返回受控 JSON，不输出后台框架
// ----------------------------------------------------------------
if(in_array($op,['assets','remove_asset'],true))flipbook\front_asset::respond($op);
if($op === 'upload') {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	if(!submitcheck('flipbookmicrosubmit')) {
		echo json_encode(['ok' => 0, 'message' => '表单校验失败']); exit;
	}
	admin_helper::needperm('edit');
	if(empty($_FILES['file']) || !is_array($_FILES['file'])) {
		echo json_encode(['ok' => 0, 'message' => '未收到文件']); exit;
	}
	$file = $_FILES['file'];
	if(!empty($file['error']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
		echo json_encode(['ok' => 0, 'message' => '上传失败']); exit;
	}
 $kind=($_POST['kind']??'')==='video'?'video':'image';$limit=$kind==='video'?100*1024*1024:8*1024*1024;
	if(intval($file['size']) <= 0 || intval($file['size']) > $limit) {
		echo json_encode(['ok' => 0, 'message' => $kind==='video'?'视频需小于 100MB':'图片需小于 8MB']); exit;
	}
	$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
	$allowed = ['image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'video' => ['mp4', 'webm', 'm4v']];
	$kind = isset($_POST['kind']) && $_POST['kind'] === 'video' ? 'video' : 'image';
	if(!in_array($ext, $allowed[$kind], true)) {
		echo json_encode(['ok' => 0, 'message' => '不支持的文件格式']); exit;
	}
	if($kind === 'image' && !@getimagesize($file['tmp_name'])) {
		echo json_encode(['ok' => 0, 'message' => '图片文件无效']); exit;
	}
	try {
  $asset=flipbook\front_media_storage::store($file['tmp_name'],$ext,$kind,['scope'=>$_POST['scope']??'micropage','productid'=>$_POST['productid']??0,'purpose'=>$_POST['purpose']??$kind]);
  echo json_encode(['ok'=>1]+$asset,JSON_UNESCAPED_SLASHES);
 } catch(\RuntimeException $error) { echo json_encode(['ok'=>0,'message'=>$error->getMessage()]); }

	exit;
}

if($op==='normalize'){
 header('Content-Type:application/json;charset=utf-8');header('Cache-Control:no-store');
 if(!submitcheck('flipbookmicrosubmit')){echo json_encode(['ok'=>0,'message'=>'表单校验失败']);exit;}
 admin_helper::needperm('edit');$raw=is_string($_POST['config']??null)?$_POST['config']:'';$config=strlen($raw)<=1024*1024?json_decode($raw,true):null;$error=front_micro::validate($config);$normalizeKey=front_micro::key($_POST['key']??$_GET['key']??'home');if(!$error)$error=front_micro::validate_presets(front_micro::page($normalizeKey)['config'],$config);
 echo json_encode($error?['ok'=>0,'message'=>$error]:['ok'=>1,'config'=>front_micro::mark_presets(front_micro::sanitize($config),front_micro::page($normalizeKey)['config'])],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}

$keys = front_micro::keys();
$requestedKey=$op==='save'?($_POST['key']??'home'):($_GET['key']??'home');
$key = front_micro::key(is_scalar($requestedKey)?$requestedKey:'home');
$isEditor = in_array($op,['edit','save'],true) || isset($_GET['key']);
if($isEditor && (!is_string($requestedKey) || !isset($keys[$requestedKey]))) admin_helper::failed('页面不存在，请从微页面列表选择已有页面');
if($isEditor) {
 echo '<script>document.body.setAttribute("data-fb-editor",'.json_encode($key).');document.title='.json_encode('可视化编辑 · '.$keys[$key],JSON_HEX_TAG|JSON_HEX_AMP).';</script>';
}
admin_helper::assets();
echo '<script defer src="'.front_helper::asset('admin/editor-shell.js').'?'.intval(@filemtime(__DIR__.'/static/admin/editor-shell.js')).'"></script>';

// ----------------------------------------------------------------
// 保存配置
// ----------------------------------------------------------------
if($op === 'save' && submitcheck('flipbookmicrosubmit')) {
	admin_helper::needperm('edit');
	$key = isset($_POST['key']) && is_scalar($_POST['key']) ? (string)$_POST['key'] : '';
	if(!isset(front_micro::keys()[$key])) { admin_helper::failed('页面标识无效，未保存'); }
	$raw = isset($_POST['config']) && is_string($_POST['config']) ? $_POST['config'] : '';
	if(strlen($raw) > 1024 * 1024) { admin_helper::failed('配置超过 1MB，未保存'); }
	$decoded = json_decode($raw, true);
	$error = front_micro::validate($decoded);if(!$error)$error=front_micro::validate_presets(front_micro::page($key)['config'],$decoded);
	if($error !== '') { admin_helper::failed($error); }
	$previous=flipbook\table_flipbook_micropage::t()->fetch_by_key($key);
 $clean=front_micro::mark_presets(front_micro::sanitize($decoded),front_micro::page($key)['config']);$typographyDirty=!empty($clean['globalTypographyDirty']);$typography=$clean['typography'];unset($clean['schema'],$clean['typography'],$clean['globalTypographyDirty']);
	$expected = isset($_POST['version']) ? max(0, intval($_POST['version'])) : -1;
	$version = flipbook\table_flipbook_micropage::t()->save_page($key, front_micro::name($key), json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $expected);
	if(!$version) { admin_helper::failed('此页面已被其他窗口更新，请复制当前配置备份后刷新再编辑'); }

 if($typographyDirty)flipbook\table_flipbook_setting::t()->update_setting('micro_typography',json_encode($typography,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
 flipbook\front_asset::cleanup_replaced($previous['config']??'', $decoded);
 flipbook\front_asset::cleanup();
	admin_helper::succeeded('已保存 · 版本 '.$version, 'micropage', ['op' => 'edit', 'key' => $key]);
}

$page = front_micro::page($key);
$previewProductid = front_micro::preview_productid($_GET['productid'] ?? 0);
foreach(['admin/micropage-preview.css','admin/micropage-preview.js'] as $asset){$url=front_helper::asset($asset).'?'.intval(filemtime(__DIR__.'/static/'.$asset));echo str_ends_with($asset,'.css')?'<link rel="stylesheet" href="'.$url.'">':'<script src="'.$url.'" defer></script>';}


if($op !== 'edit' && !isset($_GET['key'])) {
	// ------------------------------------------------------------
	// 页面列表
	// ------------------------------------------------------------
	$createButton = admin_helper::perm('add') ? '<button type="button" class="fb-admin-button" data-fb-micro-create>新增微页面</button>' : '';
	showtableheader('<div class="fb-admin-heading"><span>微页面（每个页面独立配置，保存后自动同步小程序与 H5）</span><div class="fb-admin-heading-actions">'.$createButton.'<a class="fb-admin-button" href="'.admin_helper::e(admin_helper::url('micropage', ['op' => 'edit', 'key' => $key])).'" target="_blank" rel="noopener" data-fb-admin-open="flipbook_micro_editor_'.$key.'" data-fb-admin-title="可视化编辑 · '.admin_helper::e($keys[$key]).'">进入可视化编辑</a><a class="fb-admin-button" data-fb-phone-preview href="'.admin_helper::e(front_helper::url('micro',['key'=>'home','device_preview'=>1])).'">预览地址</a></div></div>');
	showsubtitle(['页面', '页面类型', '标识 / 链接', '版本', '更新时间', '前台地址', '小程序地址', '操作']);
	$rows = [];
	foreach(flipbook\table_flipbook_micropage::t()->fetch_all_pages() as $row) {
		$rows[$row['pkey']] = $row;
	}
	foreach($keys as $pkey => $label) {
		$row = isset($rows[$pkey]) ? $rows[$pkey] : [];
		$editurl = admin_helper::url('micropage', ['op' => 'edit', 'key' => $pkey]);
		$preview = front_helper::url('micro', ['key' => $pkey] + ($pkey === 'detail' && $previewProductid ? ['productid' => $previewProductid] : []));
		$fullPreview=rtrim($_G['siteurl'],'/').'/'.$preview;
		$mini=front_micro::routes($pkey)['mini'].($pkey==='detail' && $previewProductid?'&productid='.$previewProductid:'');
		$actions='<a class="fb-admin-button" href="'.admin_helper::e($editurl).'" target="_blank" rel="noopener" data-fb-admin-open="flipbook_micro_editor_'.$pkey.'" data-fb-admin-title="可视化编辑 · '.admin_helper::e($label).'">进入可视化编辑</a>';
		if(front_micro::is_custom_key($pkey) && $row) {
			$attrs=' data-key="'.admin_helper::e($pkey).'" data-name="'.admin_helper::e($row['name']).'" data-version="'.intval($row['version']).'"';
			if(admin_helper::perm('edit'))$actions.='<button type="button" class="fb-admin-button" data-fb-micro-rename'.$attrs.'>重命名</button>';
			if(admin_helper::perm('delete'))$actions.='<button type="button" class="fb-admin-button" data-fb-micro-delete'.$attrs.'>删除</button>';
		}
		$cells = [
			'<strong>'.admin_helper::e($row ? $row['name'] : $label).'</strong>',
			front_micro::is_custom_key($pkey) ? '微页面' : '内置页面',
			'<code>'.admin_helper::e($pkey).'</code><br><code>'.admin_helper::e('page:'.$pkey).'</code>',
			$row ? intval($row['version']) : 0,
			$row ? dgmdate($row['dateline'], 'Y-m-d H:i') : '未配置',
			'<div class="fb-micro-address"><a href="'.admin_helper::e($fullPreview).'" title="'.admin_helper::e($preview).'" target="_blank" rel="noopener"><code>'.admin_helper::e($preview).'</code></a><button type="button" class="fb-admin-button" data-fb-micro-copy="'.admin_helper::e($fullPreview).'">复制</button></div>',
			'<div class="fb-micro-address"><code>'.admin_helper::e($mini).'</code><button type="button" class="fb-admin-button" data-fb-micro-copy="'.admin_helper::e($mini).'">复制</button></div>',
			'<div class="fb-micro-page-actions">'.$actions.'</div>',
		];
		showtablerow('', [], $cells);
	}
	showtablefooter();
	if($createButton !== '') {
		echo '<dialog class="fb-wechat-dialog" id="fb-micro-create" aria-labelledby="fb-micro-create-title"><form method="post" action="'.admin_helper::e(admin_helper::url('micropage',['op'=>'create'])).'"><h3 id="fb-micro-create-title">新增微页面</h3><label>页面名称<input class="txt" name="name" required maxlength="100" autocomplete="off" placeholder="例如：品牌故事" /></label><p>创建后可直接编辑，访问链接会自动生成，并加入编辑器的页面链接列表。</p><input type="hidden" name="formhash" value="'.FORMHASH.'" /><input type="hidden" name="flipbookmicrocreatesubmit" value="1" /><input type="hidden" name="category" value="micro" /><div class="fb-wechat-dialog-actions"><button type="button" class="fb-admin-button" data-fb-micro-cancel>取消</button><button type="submit" class="fb-admin-button">创建并编辑</button></div></form></dialog>';
	}
	foreach(['rename'=>'重命名微页面','delete'=>'删除微页面'] as $action=>$title) {
		if(!admin_helper::perm($action==='delete'?'delete':'edit'))continue;
		echo '<dialog class="fb-wechat-dialog" id="fb-micro-'.$action.'" aria-labelledby="fb-micro-'.$action.'-title"><form method="post" action="'.admin_helper::e(admin_helper::url('micropage',['op'=>$action])).'"><h3 id="fb-micro-'.$action.'-title">'.$title.'</h3>';
		echo $action==='rename'?'<label>页面名称<input class="txt" name="name" required maxlength="100" autocomplete="off" /></label><p>重命名后，原有访问地址和已配置的链接继续有效。</p>':'<p>确认删除“<strong data-fb-micro-name></strong>”？删除后该页面将无法访问，页面内容不可恢复。</p>';
		echo '<input type="hidden" name="formhash" value="'.FORMHASH.'" /><input type="hidden" name="flipbookmicrochangesubmit" value="1" /><input type="hidden" name="key" /><input type="hidden" name="version" /><div class="fb-wechat-dialog-actions"><button type="button" class="fb-admin-button" data-fb-micro-cancel>取消</button><button type="submit" class="fb-admin-button">'.($action==='rename'?'保存名称':'确认删除').'</button></div></form></dialog>';
	}
	echo '<script src="'.front_helper::asset('admin/micropage-list.js').'?'.intval(filemtime(__DIR__.'/static/admin/micropage-list.js')).'"></script>';
	echo '<div class="infobox">只有微页面允许新增；内置页面仅支持编辑。新增后可在组件的页面链接列表中直接选择。保存后 H5 立即生效，小程序下次进入页面时自动加载最新配置。</div>';
	return true;
}

// ------------------------------------------------------------
// 三栏可视化编辑器
// ------------------------------------------------------------
$preview = front_helper::url('micro', ['key' => $key] + ($key === 'detail' && $previewProductid ? ['productid' => $previewProductid] : []));
$data = json_encode($page['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
$types = front_micro::types();

echo '<link rel="stylesheet" type="text/css" href="'.front_helper::asset('admin/micropage.css').'?'.intval(@filemtime(dirname(__FILE__).'/static/admin/micropage.css')).'" />';
echo '<div class="fbmp" id="fbmp">';
echo '<div class="fbmp-head">';
echo '<div class="fbmp-editor-identity"><strong>'.admin_helper::e($keys[$key]).'</strong><span>可视化编辑</span><a class="fb-admin-button" href="'.admin_helper::e(admin_helper::url('micropage')).'" target="_blank" rel="noopener" data-fb-admin-open="flipbook_micropage_list" data-fb-admin-title="微页面">微页面列表</a></div>';
echo '<div class="fbmp-actions">';
echo '<button type="button" class="fb-admin-button" id="fbmp-undo">撤销</button><button type="button" class="fb-admin-button" id="fbmp-redo">重做</button><button type="button" class="fb-admin-button" id="fbmp-export">导出</button><button type="button" class="fb-admin-button" id="fbmp-import">导入</button>';
echo '<span class="fbmp-version">版本 '.intval($page['version']).'</span>';
echo '<a class="fb-admin-button" data-fb-phone-preview href="'.admin_helper::e($preview).'">预览</a>';
echo '<button type="button" class="fb-admin-button" id="fbmp-save">保存并同步</button>';
echo '</div>';
echo '</div>';

echo '<div class="fbmp-body">';
echo '<aside class="fbmp-side fbmp-library">';
echo flipbook\front_icon::sprite().flipbook\front_icon::component_sprite();
$groups=[];foreach(flipbook\front_composer::schema()['types'] as $type=>$definition){if(!empty($definition['hidden']))continue;if(isset($definition['pages'])&&!in_array($key,$definition['pages'],true))continue;$groups[$definition['group']][]=$type;}
foreach($groups as $group=>$items){echo '<details class="fbmp-palette-group" open><summary>'.admin_helper::e($group).'</summary><div class="fbmp-palette">';foreach($items as $type)echo '<button type="button" class="fbmp-add" data-add="'.admin_helper::e($type).'">'.flipbook\front_icon::component(flipbook\front_composer::schema()['types'][$type]['icon']).'<span class="fbmp-add-label">'.admin_helper::e($types[$type]).'</span></button>';echo '</div></details>';}
echo '<details class="fbmp-palette-group" open><summary>组合布局</summary><div class="fbmp-palette">';foreach(flipbook\front_composer::schema()['presets'] as $id=>$preset){if(isset($preset['pages'])&&!in_array($key,$preset['pages'],true))continue;echo '<button type="button" class="fbmp-add" data-preset="'.admin_helper::e($id).'">'.flipbook\front_icon::component($preset['icon']).'<span class="fbmp-add-label">'.admin_helper::e($preset['label']).'</span></button>';}echo '</div></details>';
echo '</aside>';

echo '<section class="fbmp-canvas-stage">';
echo '<div class="fbmp-preview-controls" id="fbmp-scale-controls" role="toolbar" aria-label="预览显示比例">';
echo '<span>显示比例</span>';
foreach([25,50,75,100] as $percent)echo '<button type="button" class="fb-admin-button" data-preview-scale="'.$percent.'" aria-pressed="'.($percent===100?'true':'false').'">'.$percent.'%</button>';
echo '<label class="fbmp-scale-custom"><input id="fbmp-scale" type="number" min="10" max="200" step="1" value="100" aria-label="自定义显示百分比" />%</label>';
echo '<output id="fbmp-preview-size" aria-live="polite">720 × 1559</output>';
echo '</div>';
echo '<div class="fbmp-canvas" tabindex="0" aria-label="微页面预览画布"><div class="fbmp-viewport" id="fbmp-viewport"><div class="fbmp-frame" id="fbmp-frame"></div></div></div>';
echo '</section>';
echo '<aside class="fbmp-side fbmp-config" aria-label="组件配置"><div class="fbmp-inspector" id="fbmp-inspector"></div></aside>';
echo '</div>';
echo '</div>';

echo '<form id="fbmp-form" method="post" enctype="multipart/form-data" action="'.admin_helper::e(admin_helper::url('micropage', ['op' => 'save', 'key' => $key])).'">';
echo '<input type="hidden" name="formhash" value="'.FORMHASH.'" />';
echo '<input type="hidden" name="flipbookmicrosubmit" value="1" />';
echo '<input type="hidden" name="version" value="'.intval($page['version']).'" />';
echo '<input type="hidden" name="key" value="'.admin_helper::e($key).'" />';
echo '<input type="hidden" name="config" id="fbmp-config" value="" />';
echo '</form>';

echo '<script type="application/json" id="fbmp-data">'.$data.'</script>';
echo '<script>window.FBMP_KEYS='.json_encode($keys,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP).';window.FBMP_KEY='.json_encode($key).';window.FBMP_CONTEXT='.json_encode(flipbook\front_composer::context($key,$previewProductid),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP).';window.FBMP_UPLOAD='.json_encode(front_helper::url('asset',['op'=>'upload'])).';window.FBMP_NORMALIZE='.json_encode(admin_helper::url('micropage',['op'=>'normalize','key'=>$key])).';window.FBMP_ICON_NORMALIZE='.json_encode(front_helper::url('asset',['op'=>'nav-icon'])).';window.FBMP_FORMHASH='.json_encode(FORMHASH).';window.FBMP_ICONS='.json_encode(flipbook\front_icon::keys(),JSON_UNESCAPED_UNICODE).';</script>';
foreach(['css/composer.css','admin/composer-editor.css'] as $asset)echo '<link rel="stylesheet" href="'.front_helper::asset($asset).'?'.intval(filemtime(__DIR__.'/static/'.$asset)).'">';
foreach(['js/composer-core.js','js/composer-gallery.js','admin/micropage.js'] as $asset)echo '<script src="'.front_helper::asset($asset).'?'.intval(filemtime(__DIR__.'/static/'.$asset)).'"></script>';
