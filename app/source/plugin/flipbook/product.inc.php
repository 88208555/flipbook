<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 后台：款式
 *
 * 款式承载卖点图、细节图与模特图三类图组，以及规格与价格展示参数。
 * 规格写入受控 JSON，价格仅在开启“展示价格区域”并有值时才会出现在前台。
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

use flipbook\admin_helper;
use flipbook\admin_crud;
use flipbook\table_flipbook_product_media;

$flipbookDrafts=class_exists('\flipbook_sync\service_draft_editor')&&\flipbook_sync\service_draft_editor::enabled();
if(empty($flipbookDraftConfigOnly)&&$flipbookDrafts&&((string)($_GET['view']??'')==='drafts'||isset($_POST['draft_action']))){\flipbook_sync\admin_draft::run();return true;}

if(empty($flipbookDraftConfigOnly)) flipbook\admin_product::navigation('product');

$config = [
	'pmod' => 'product',
	'table' => 'flipbook_product',
	'title' => '商品',
	'orderby' => ['displayorder' => 'ASC'],
	'columns' => ['name', 'code', 'summary', 'priceshow', 'pricevalue', 'displayorder', 'available'],
	'schema' => [
		'supplierid'=>['title'=>'供应商户','type'=>'select','source'=>'supplier'],
		'name' => ['title' => '商品名称', 'type' => 'text', 'required' => true, 'maxlen' => 150],
		'code' => ['title' => '商品主编码（SPU）', 'type' => 'text', 'maxlen' => 60],
		'summary' => ['title' => '商品副标题', 'type' => 'text', 'maxlen' => 255],
		'description' => ['title' => '商品说明', 'type' => 'textarea', 'maxlen' => 3000],
		'cover' => ['title' => '商品首图', 'type' => 'url'],
		'video' => ['title' => '商品视频', 'type' => 'url', 'comment' => '详情轮播首个视频，留空则从图片开始'],
		'priceshow' => ['title' => '展示价格', 'type' => 'bool', 'default' => 0, 'comment' => '仅在插件设置开启价格区域时生效'],
		'pricevalue' => ['title' => '售价', 'type' => 'amount', 'default' => '0.00'],
		'priceorigin' => ['title' => '划线价', 'type' => 'amount', 'default' => '0.00', 'comment' => '高于售价时才显示'],
		'currency' => ['title' => '币种', 'type' => 'text', 'maxlen' => 8, 'default' => 'CNY'],
		'displayorder' => ['title' => '排序', 'type' => 'int', 'default' => 0],
		'available' => ['title' => '是否上架', 'type' => 'bool', 'default' => 1],
	],
	'extra_fields' => [
  'supplierid'=>['title'=>'供应商户','type'=>'select','source'=>'supplier'],
  'product_category'=>['title'=>'商品分类','type'=>'select','source'=>'category'],
  'product_series'=>['title'=>'商品系列','type'=>'select','source'=>'series'],
  'spec_colors'=>['title'=>'颜色','type'=>'text','comment'=>'多个颜色用英文逗号分隔'],
  'spec_sizes'=>['title'=>'尺码','type'=>'text','comment'=>'多个尺码用英文逗号分隔'],
  'spec_fit'=>['title'=>'版型','type'=>'text'],
  'supplier'=>['title'=>'供应商说明','type'=>'text'], 'spec_model'=>['title'=>'模特信息','type'=>'text'],
  'spec_table'=>['title'=>'尺码表','type'=>'textarea','comment'=>'每行一条，单元格用 | 分隔；第一行为表头'],
  'spec_fields'=>['title'=>'自定义字段','type'=>'textarea','comment'=>'每行：字段名称|字段内容，可自由新增'],
  'overlay_lines'=>['title'=>'商品悬浮文字（最多20行）','type'=>'textarea','comment'=>'商品名称与卖点自动显示；这里填写额外文字，每行一条'],
  'floating_image'=>['title'=>'商品悬浮图片','type'=>'url','comment'=>'跟随当前商品显示，在微页面中设置显示区域与样式'],
  'floating_link'=>['title'=>'悬浮图片跳转链接','type'=>'text','comment'=>'支持 page:catalog、product:1 或 https 地址'],
  'share_title'=>['title'=>'分享主标题','type'=>'text','maxlen'=>10,'comment'=>'留空自动使用商品名称，最多 10 字'],
  'share_subtitle'=>['title'=>'分享副标题','type'=>'text','maxlen'=>20,'comment'=>'留空自动使用商品副标题，最多 20 字'],
  'share_description'=>['title'=>'分享描述','type'=>'textarea','maxlen'=>200,'comment'=>'留空自动使用商品说明，最多 200 字'],
  'spec_content'=>['title'=>'补充内容（Markdown）','type'=>'textarea'],
  'showcase_name'=>['title'=>'首页展示名称','type'=>'text','comment'=>'留空使用商品名称'],
  'showcase_subtitle'=>['title'=>'首页展示说明','type'=>'text','comment'=>'留空使用商品卖点'],
  'showcase_description'=>['title'=>'首页展示正文','type'=>'textarea','comment'=>'留空使用商品说明'],
  'showcase_cover'=>['title'=>'首页展示图片','type'=>'url','comment'=>'留空使用商品首图'],
  'showcase_video'=>['title'=>'首页展示视频','type'=>'url','comment'=>'留空使用商品视频'],
		'medialines' => ['title' => '图组', 'type' => 'textarea', 'comment' => '每行一条：类型|标题|图片地址；类型可选 highlight（卖点）、detail（细节）、model（模特）、sku（当前款式专属 SKU 图，详情页缩略图仅显示此类型）'],
	],
	'extra_load' => function ($productid) {
		$lines = [];
		foreach(\flipbook\table_flipbook_product_media::t()->fetch_all_by_product($productid) as $media) {
			$lines[] = $media['type'].'|'.$media['title'].'|'.$media['image'];
		}
		return ['medialines' => implode("\n", $lines)] + flipbook\admin_product::load($productid);
	},
	'extra_save' => function ($productid) {
  flipbook\admin_product::save($productid);
  flipbook\front_sku::save($productid,(string)($_POST['skulines']??'[]'));
  foreach(json_decode((string)($_POST['skulines']??'[]'),true)?:[] as $sku)if(array_key_exists('available',$sku)&&!$sku['available'])DB::update('flipbook_sku',['available'=>0],['productid'=>$productid,'code'=>trim((string)($sku['code']??''))]);
		$media = \flipbook\table_flipbook_product_media::t();
		$media->delete_by_product($productid);
		$order = 0;
		foreach(explode("\n", (string)admin_helper::textarea('medialines', 5000)) as $line) {
			$line = trim($line);
			if($line === '') {
				continue;
			}
			$parts = array_map('trim', explode('|', $line, 3));
			$type = isset($parts[0]) ? $parts[0] : '';
			$title = isset($parts[1]) ? $parts[1] : '';
			$image = isset($parts[2]) ? $parts[2] : '';
			if($type==='sku' || !isset(table_flipbook_product_media::$types[$type]) || $image === '') {
				continue;
			}
			$image = admin_helper::cleanurl($image);
			if($image === '') {
				continue;
			}
			$media->insert([
				'productid' => $productid,
				'type' => $type,
				'title' => mb_substr(strip_tags($title), 0, 150, 'UTF-8'),
				'image' => $image,
				'displayorder' => $order,
			]);
			$order++;
		}
	},
	'before_save'=>function($id){if($id)flipbook\front_asset::capture($id);},
 'after_save'=>function($id){flipbook\front_asset::after_product_save($id);},
 'after_delete'=>function($ids){foreach($ids as $id)flipbook\front_asset::cleanup($id);},
	'before_delete' => function ($productid) {
  flipbook\admin_product::delete_chain($productid);
  DB::delete('flipbook_sku',['productid'=>$productid]);
		\flipbook\table_flipbook_product_media::t()->delete_by_product($productid);
		\flipbook\table_flipbook_page_product::t()->delete_by_product($productid);
	},
];

if(!empty($flipbookDraftConfigOnly)) return $config;
if($flipbookDrafts){
 $nativeBefore=$config['before_save'];$nativeAfter=$config['after_save'];
 $config['before_save']=static function($id)use($nativeBefore){\flipbook_sync\service_draft_editor::before_save($id);$nativeBefore($id);};
 $config['after_save']=static function($id)use($nativeAfter){$nativeAfter($id);\flipbook_sync\service_draft_editor::clear_after_save($id);};
}


if(admin_crud::handle_post($config)) {
	return;
}

$op = admin_helper::enumval('op', ['list' => '', 'add' => '', 'edit' => '']);

if($op === 'add' || $op === 'edit') {
	$row = admin_crud::fetch($config['table'], 'productid', admin_helper::uintval('id'));
	if($op === 'edit' && !$row) {
		admin_helper::failed('记录不存在或已删除');
	}
	if($row && !empty($config['extra_load'])) {
		$row['__extra'] = call_user_func($config['extra_load'], intval($row['productid']));
	}
	if($flipbookDrafts)[$row,$draft]=\flipbook_sync\service_draft_editor::overlay($row,admin_helper::uintval('id'));
	flipbook\admin_product::render($config, $row);
} else {
	admin_crud::render_list($config, max(1, admin_helper::uintval('page')));
}
