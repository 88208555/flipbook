<?php

/**
 * [Discuz!] (C)2001-2099 Discuz! Team
 * 工装画册插件 · 语言包
 *
 * $scriptlang：前台与后台模块共用文案；
 * $templatelang：模板中 {lang xxx} 使用；
 * $installlang：安装/升级提示。
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$scriptlang['flipbook'] = [
	'overview' => '概况',
	'on' => '已开启',
	'off' => '已关闭',
	'series_count' => '系列数量',
	'album_count' => '画册数量',
	'schemaversion' => '数据结构版本',
	'settings_albumname' => '画册名称',
	'settings_showprice' => '展示价格区域',
	'settings_postertemplate' => '分享海报默认模板',
	'admin_hint' => '画册内容、组件与发布参数由本插件后台维护；会员、用户组与后台职务沿用 Discuz! 原生体系。',
	'album_entry' => '电子画册',
];

$templatelang['flipbook'] = [
	// 导航
	'nav_index' => '发现',
	'nav_catalog' => '分类',
	'nav_favorites' => '收藏',
	'nav_mine' => '我的',

	// 通用
	'back' => '返回',
	'close' => '关闭',
	'cancel' => '取消',
	'next' => '下一步',
	'view_all' => '全部',
	'goto_page' => '查看',
	'search' => '搜索',
	'search_action' => '搜索',
	'search_placeholder' => '搜索款式、画册',
	'filter_all' => '全部',
	'filter_category' => '品类',
	'filter_category_all' => '全部品类',
	'filter_series_all' => '全部系列',
	'catalog_page_title' => '分类',
	'filter_reset' => '清空筛选',
	'unit_page' => '页',
	'unit_album' => '册',
	'unit_album_tab' => '画册',
	'unit_series' => '个系列',
	'unit_style' => '款式',
	'unit_item' => '项',
	'unit_total' => '合计',
	'unit_favorite' => '收藏',
	'unit_read' => '足迹',
	'unit_share' => '分享',
	'go_catalog' => '去逛画册',
	'go_discover' => '去发现',
	'back_discover' => '返回发现',
	'last_read' => '上次阅读',

	// 发现页
	'tab_series' => '系列',
	'tab_recommend' => '推荐',
	'album_empty' => '暂无画册内容',
	'album_empty_hint' => '后台发布画册后，这里会自动出现最新内容。',
	'album_offline' => '画册已下架',
	'album_offline_hint' => '去发现更多内容',
	'series_empty' => '暂无系列',
	'cover_missing' => '封面待上传',
	'start_reading' => '开始阅读',
	'action_favorite' => '收藏',
	'action_style' => '款式',
	'action_share' => '分享',

	// 阅读页
	'catalog' => '目录',
	'catalog_title' => '本册目录',
	'catalog_all' => '全部',
	'catalog_favorite' => '已收藏',
	'catalog_current' => '当前阅读',
	'catalog_reading' => '正在阅读',
	'catalog_continue' => '继续阅读',
	'catalog_favorite_empty' => '本册暂无收藏的款式',
	'catalog_favorite_empty_hint' => '在目录中收藏款式后，对应的页面会出现在这里。',
	'read_hint' => '左右翻页 · 上下换册',
	'image_missing' => '图片待上传',
	'favorite_count' => '收藏',
	'progress_saved' => '阅读进度已保存',

	// 分享
	'share_title' => '分享',
	'share_whole' => '整本画册',
	'share_current' => '当前款式',
	'share_friend' => '发送给好友',
	'share_moments' => '朋友圈分享指引',
	'share_link' => '复制链接',
	'share_copy' => '复制',
	'share_copied' => '链接已复制',
	'share_copy_failed' => '请长按链接复制',
	'share_poster' => '生成海报',
	'poster_title' => '分享海报',
	'poster_save' => '保存图片',
	'poster_retry' => '重新保存',
	'poster_generating' => '生成中',
	'poster_hint' => '长按海报或点击按钮保存到相册',
	'poster_failed' => '海报生成失败，请稍后重试',
	'poster_file' => '工装画册海报',

	// 分类页
	'search_empty' => '没有找到匹配的画册',
	'search_empty_hint' => '换个关键词，或清空系列与品类筛选后再试。',

	// 收藏页
	'login_required' => '登录后查看收藏',
	'login_required_hint' => '收藏会与你的账号同步，换设备也能继续看。',
	'login_action' => '立即登录',
	'logout' => '退出登录',
	'member_logged' => '登录后同步收藏与阅读记录',
	'member_guest' => '登录后同步收藏与阅读记录',
	'member_guest_name' => '访客',
	'member_login_link' => '去登录',
	'favorite_empty_style' => '还没有收藏',
	'favorite_empty_album' => '还没有收藏画册',
	'favorite_empty_hint' => '喜欢的款式，可以收藏在这里',
	'favorite_empty_album_hint' => '喜欢的画册，可以收藏在这里',
	'favorite_manage' => '管理',
	'favorite_manage_done' => '完成',
	'favorite_sort_recent' => '最近收藏',
	'favorite_sort_oldest' => '最早收藏',
	'favorite_sort_name' => '按名称',
	'favorite_remove' => '移除收藏',
	'favorite_selected' => '已选',
	'favorite_removed' => '已移除收藏',

	// 我的
	'resume_title' => '继续阅读',
	'continue_reading' => '继续',
	'menu_history' => '阅读历史',
	'menu_favorites' => '我的收藏',
	'menu_share' => '分享记录',
	'menu_preference' => '阅读偏好',
	'menu_about' => '关于画册',

	// 记录
	'history_title' => '我的记录',
	'history_read' => '阅读足迹',
	'history_share' => '分享记录',
	'history_manage' => '管理',
	'history_manage_done' => '完成',
	'history_read_at' => '读至',
	'history_continue' => '继续',
	'history_remove' => '删除记录',
	'history_login_hint' => '登录后可查看阅读足迹与分享记录。',
	'history_empty' => '暂无记录',
	'history_empty_hint' => '开始阅读画册后，足迹会按今天、昨天分组显示。',

	// 偏好
	'preference_title' => '阅读偏好',
	'preference_save' => '完成',
	'preference_guest_hint' => '登录后可保存阅读偏好到账号。',
	'preference_saved' => '阅读偏好已保存',

	// 款式详情
	'style_title' => '款式详情',
	'pane_highlight' => '卖点',
	'pane_detail' => '细节',
	'pane_spec' => '尺码',
	'style_more' => '更多',
	'style_all_detail' => '查看全部细节',
	'style_favorite' => '收藏款式',
	'style_share' => '分享款式',
	'style_back_album' => '返回画册',
	'media_empty' => '暂无图片',
	'tap_to_zoom' => '点击放大',
	'zoom' => '放大查看',
	'pinch_zoom_hint' => '双指可缩放查看细节',
	'viewer_hint' => '双指缩放 · 左右切换',
	'viewer_save' => '保存图片',
	'spec_color' => '颜色',
	'spec_size' => '尺码',
	'spec_table' => '尺寸参考 / cm',
	'spec_model' => '模特与版型',
	'spec_model_label' => '模特',
	'spec_fit_label' => '版型',
	'spec_code_label' => '款号',
	'spec_note' => '平铺测量，存在少量手工误差',
	'spec_note_2' => '尺寸数据为设计示例',
	'style_in_albums' => '收录于画册',
	'content_gone' => '内容已下架',
	'content_gone_hint' => '该内容已停止展示，请返回浏览其他画册。',
	'back_home' => '返回发现',
];

$installlang['flipbook'] = [
	'install_succeed' => '工装画册数据结构已就绪。',
];
