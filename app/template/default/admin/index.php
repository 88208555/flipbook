<?php exit('Access Denied'); ?>
<style>
	*, *::before, *::after {
		box-sizing: inherit;
	}
</style>

{echo show_releasetips();}

<div class="drow">
	<div id="show_widgets_left" class="dcol d-23">{echo show_widgets('left')}</div>
	<div id="show_widgets_right" class="dcol d-13">{echo show_widgets('right')}</div>
</div>

<!--{if $isfounder}-->
	<div class="widget-actions" id="user_bar_menu" style="display: none">
		<a href="javascript:;" id="widget_showhidden">{lang widget_showhidden}</a>
		<a href="javascript:;" id="widget_reset">{lang widget_reset}</a>
	</div>
	<script>
	var WIDGET_FORMHASH = '{FORMHASH}';
	var WIDGET_AJAX_URL = '{ADMINSCRIPT}&action=misc&operation=ajax_widget';
	var WIDGET_RESET_CONFIRM = '{lang widget_reset_confirm}';
	</script>
<!--{/if}-->
<script src="{$_G['setting']['jspath']}admincp_index.js?{$_G['style']['verhash']}" type="text/javascript"></script>

