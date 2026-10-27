/**
 * 微页面配置转渲染节点。
 *
 * 后台保存的微页面配置与 H5 共用同一套字段，本模块只做：
 * 1. 设计值（以 375 宽度为基准的 px）转小程序 rpx；
 * 2. 组件配置扁平化为可直接渲染的节点（样式串、链接路径、子组件）；
 * 3. 内置链接与外部链接统一解析，供页面与组件复用。
 */

const ACTION_ICONS = require('./action-icons');
// All visual values arrive from the normalized micro-page configuration.
function effectiveRegions(type,props,theme) {
 const p=props||{},t=theme||{},out={};
 Object.entries((t.defaults||{})[type]||{}).forEach(([key,value])=>{out[key]=Object.assign({},value);});
 Object.entries((t.controlRegions||{})[type]||{}).forEach(([key,role])=>{out[key]=Object.assign({},(t.controls||{})[role]||{},out[key]||{});});
 if(type==='member_center'){
  if(p.avatarSize!==undefined)out.avatar=Object.assign({},out.avatar,{width:p.avatarSize,height:p.avatarSize});
  if(p.nameSize!==undefined)out.name=Object.assign({},out.name,{size:p.nameSize});
 }
 Object.entries((t.regions||{})[type]||{}).forEach(([key,role])=>{
  const value=Object.assign({},(t.roles||{})[role]||{},((t.defaults||{})[type]||{})[key]||{}),caption=p.caption||{};
  if(['social','product_detail'].includes(type)){
   if(key==='title')for(const field of ['size','color','weight','align'])if(caption[field]!==undefined)value[field]=caption[field];
   if(key==='subtitle'){if(caption.subSize!==undefined)value.size=caption.subSize;if(caption.subColor)value.color=caption.subColor;if(caption.align)value.align=caption.align;}
  }
  for(const field of (((t.inherit||{})[type]||{})[key]||[]))if(p[field]!==undefined&&p[field]!=='')value[field]=p[field];
  out[key]=Object.assign({},out[key]||{},value);
 });
 Object.entries(p.regions||{}).forEach(([key,value])=>{for(const [field,targets]of Object.entries(t.resets||{}))if(Object.prototype.hasOwnProperty.call(value,field))for(const target of targets)if(!Object.prototype.hasOwnProperty.call(value,target)&&out[key])delete out[key][target];out[key]=Object.assign({},out[key]||{},value);});
 return out;
}



const TAB_PAGES = [
	'/pages/discover/index',
	'/pages/catalog/index',
	'/pages/favorites/index',
	'/pages/mine/index',
];

function px(value) {
	return (Math.round((Number(value) || 0) * 2) / 2) * 2 + 'rpx';
}

function num(value, fallback) {
	const n = Number(value);
	return isNaN(n) ? fallback : n;
}

/**
 * 内置链接 → 小程序路径。
 * page:home|detail|favorites|catalog|search|mine 指向对应微页面；
 * album:ID / product:ID 复用现有阅读页。
 */
function linkPath(link){return require('./composer-core').href(link,{},true);}

/**
 * 打开链接：站内 Tab 页走 switchTab，其余走 navigateTo；
 * 外部 http(s) 地址小程序无法直接打开，复制到剪贴板便于用户继续访问。
 */
function openLink(link) {
	const path = linkPath(link);
	if (!path) {
		return;
	}
	if (/^https?:\/\//i.test(path)) {
		wx.setClipboardData({
			data: path,
			success: function () {
				wx.showToast({ title: '链接已复制', icon: 'none' });
			},
		});
		return;
	}
	if (TAB_PAGES.indexOf(path.split('?')[0]) >= 0) {
		wx.switchTab({ url: path.split('?')[0] });
		return;
	}
	wx.navigateTo({
		url: path,
		fail: function () {
			wx.redirectTo({ url: path });
		},
	});
}

function fontFamily(family) {
	if (family === 'serif') {
		return "Georgia, 'Songti SC', serif";
	}
	if (family === 'mono') {
		return 'ui-monospace, SFMono-Regular, Menlo, monospace';
	}
	return "-apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', Arial, sans-serif";
}

function boxStyle(p) {
	const style = [
		'margin-top:' + px(p.gapTop),
		'margin-bottom:' + px(p.gapBottom),
  'margin-left:'+px(p.gapLeft),'margin-right:'+px(p.gapRight),
	];
 if(p.gapLeft||p.gapRight)style.push('width:auto');
	if (p.padTop || p.padRight || p.padBottom || p.padLeft) {
		style.push(
			'padding:' + px(p.padTop) + ' ' + px(p.padRight) + ' ' + px(p.padBottom) + ' ' + px(p.padLeft)
		);
	}
	if (num(p.radius, 0) > 0) {
		style.push('border-radius:' + px(p.radius));
		style.push('overflow:hidden');
	}
	if (p.bg) {
		style.push('background:' + p.bg);
	}
 const box={};
 if(Number(p.boxWidth)>0)box.width=p.boxWidth;
 if(Number(p.boxHeight)>0){box.height=p.boxHeight;style.push('overflow:hidden');}
 ['borderWidth','borderColor','borderStyle','display','direction','justify','itemsAlign','wrap','itemGap','grow','shrink'].forEach(key=>{if(p[key]!==undefined&&p[key]!=='')box[key]=p[key];});
 const extra=regionStyle(box);if(extra)style.push(extra);
	return style.join(';');
}

/** Inner regions are sparse overrides: an absent setting keeps the screen's CSS. */
function regionStyle(p) {
	p = p || {};
	const style = [];
	const has = key => Object.prototype.hasOwnProperty.call(p, key);

const layouts={"display":["","block","inline-block","flex","none"],"direction":["","row","column","row-reverse","column-reverse"],"justify":["","flex-start","center","flex-end","space-between","space-around","space-evenly"],"itemsAlign":["","stretch","flex-start","center","flex-end","baseline"],"wrap":["","nowrap","wrap","wrap-reverse"]},layoutNames={"display":"display","direction":"flex-direction","justify":"justify-content","itemsAlign":"align-items","wrap":"flex-wrap","itemGap":"gap","grow":"flex-grow","shrink":"flex-shrink"};Object.keys(layouts).forEach(key=>{if(p[key]&&layouts[key].includes(p[key]))style.push(layoutNames[key]+':'+p[key]);});['itemGap','grow','shrink'].forEach(key=>{if(has(key))style.push(layoutNames[key]+':'+(key==='itemGap'?px(p[key]):(Number(p[key])||0)));});
	if (p.enabled === 0 || p.enabled === false) style.push('display:none!important');
	['Top', 'Right', 'Bottom', 'Left'].forEach(side => {
		if (has('gap' + side)) style.push('margin-' + side.toLowerCase() + ':' + px(p['gap' + side]));
		if (has('pad' + side)) style.push('padding-' + side.toLowerCase() + ':' + px(p['pad' + side]));
	});
	if (has('radius')) {
		style.push('border-radius:' + px(p.radius));
	}
	if (p.bg) style.push('background:' + p.bg);
	['width', 'height'].forEach(key => {
		if (has(key)) {
			style.push(key + ':' + (Number(p[key]) > 0 ? px(p[key]) : 'auto'));
			if (key === 'width' && Number(p[key]) > 0 && !has('minWidth')) style.push('min-width:0');
		}
	});
	if (has('borderWidth')) {
		style.push('border-width:' + px(p.borderWidth));
		if (Number(p.borderWidth) > 0 && !p.borderStyle) style.push('border-style:solid');
	}
	if (p.borderColor) style.push('border-color:' + p.borderColor);
	if (p.borderStyle) style.push('border-style:' + p.borderStyle);
 if(!p.borderStyle&&['borderWidth','borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth'].some(key=>Number(p[key])>0)&&!Number(p.borderWidth))style.push('border-style:solid');
 const extraDimensions={"borderTopWidth":"border-top-width","borderRightWidth":"border-right-width","borderBottomWidth":"border-bottom-width","borderLeftWidth":"border-left-width","minWidth":"min-width","minHeight":"min-height","maxWidth":"max-width","maxHeight":"max-height"};Object.keys(extraDimensions).forEach(key=>{if(has(key))style.push(extraDimensions[key]+':'+(['maxWidth','maxHeight'].includes(key)&&Number(p[key])===0?'none':px(p[key])));});
	if (has('size')) style.push('font-size:' + (Number(p.size) > 0 ? px(p.size) : 'inherit'));
	if (has('weight')) style.push('font-weight:' + (Number(p.weight) > 0 ? p.weight : 'inherit'));
	if (p.color) style.push('color:' + p.color);
	if (p.family) style.push('font-family:' + fontFamily(p.family));
	if (p.align) style.push('text-align:' + p.align);
	if (has('lineHeight')) style.push('line-height:' + (Number(p.lineHeight) > 0 ? p.lineHeight : 'inherit'));
	if (has('letterSpacing')) style.push('letter-spacing:' + px(p.letterSpacing));
 if(has('offsetX')||has('offsetY'))style.push('transform:translate('+px(p.offsetX)+','+px(p.offsetY)+')');
	return style.join(';');
}

function regionStyles(regions) {
	const styles = {};
	Object.keys(regions || {}).forEach(key => { styles[key] = regionStyle(regions[key]); });
	return styles;
}

function regionTextStyles(regions) {
	const styles = {};
	Object.keys(regions || {}).forEach(key => {
		const text = {};
		const region = regions[key] || {};
		['size','weight','color','family','align','lineHeight','letterSpacing'].forEach(field => {
			if (Object.prototype.hasOwnProperty.call(region, field)) text[field] = region[field];
		});
		styles[key] = regionStyle(text);
	});
	return styles;
}

function regionIconStyle(name, color) {
	const icon = ACTION_ICONS[name];
	if (!color || !icon) return '';
	const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' + icon.viewBox + '" fill="none" stroke="' + color + '" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' + icon.body + '</svg>';
	return 'background-image:url("data:image/svg+xml,' + encodeURIComponent(svg) + '")';
}

function richRegionHtml(html, styles, context, htmlStyles) {
	const keys = {h1:context==='product_info'?'contentTitle':'heading',h2:context==='product_info'?'contentSubtitle':'subheading',h3:context==='product_info'?'contentSubtitle':'subheading',h4:context==='product_info'?'contentSubtitle':'subheading',h5:context==='product_info'?'contentSubtitle':'subheading',h6:context==='product_info'?'contentSubtitle':'subheading',p:'paragraph',a:'link',ul:'list',ol:'list',blockquote:'quote',pre:'code',code:'code'};
	if(context==='product_info'){keys.p='contentText';keys.ul=keys.ol='contentList';keys.blockquote='contentQuote';keys.pre=keys.code='contentCode';keys.a='contentLink';}
	const configured=htmlStyles||{},defaults=Object.assign({},configured.default||{},configured[context]||{});
	const scale = typeof wx !== 'undefined' && wx.getWindowInfo ? wx.getWindowInfo().windowWidth / 750 : 0.5;
	let inCodeBlock = false;
	return String(html || '').replace(/<(\/?)([a-z][\w-]*)(\s[^<>]*?)?\s*(\/?)>/gi, function(tag, closing, name) {
		name = name.toLowerCase();
		if (closing) { if (name === 'pre') inCodeBlock = false; return tag; }
		if (name === 'code' && inCodeBlock) return tag;
		if (name === 'pre') inCodeBlock = true;
		const style = ((defaults[name]||'').replace(/(-?\d*\.?\d+)px\b/g,(_,value)=>Number(value)*scale*2+'px') + (name==='ul'||name==='ol'?'list-style-position:inside;':'') + (styles[keys[name]] || '')).replace(/(-?\d*\.?\d+)rpx\b/g, (_, value) => Number(value) * scale + 'px');
		if (!style) return tag;
		const escaped = style.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
		if (/\bstyle\s*=\s*"[^"]*"/i.test(tag)) return tag.replace(/\bstyle\s*=\s*"([^"]*)"/i, (_, value) => 'style="' + value + ';' + escaped + '"');
		return tag.replace(/\s*\/?\s*>$/, ending => ' style="' + escaped + '"' + (ending.indexOf('/') >= 0 ? '/>' : '>'));
	});
}

function prepareItems(items) {
	return (items || []).map(function (item, index) {
		return {
			k: 'i' + index,
			image: item.image || '',
			video: item.video || '',
			title: item.title || '',
			link: item.link || '',
			path: linkPath(item.link),
		};
	});
}

function prepareLines(lines) {
	return (lines || []).map(function (line, index) {
		const style = [
			'left:' + num(line.x, 0) + '%',
			'top:' + num(line.y, 0) + '%',
			'font-size:' + px(line.size),
			'font-weight:' + num(line.weight, 400),
			'color:' + (line.color || '#FFFFFF'),
			'text-align:' + (line.align || 'left'),
		].join(';');
		return {
			k: 'l' + index,
			text: line.text || '',
			style: style,
			link: line.link || '',
			path: linkPath(line.link),
		};
	});
}

function prepareHotspots(spots) {
	return (spots || []).map(function (spot, index) {
		return {
			k: 'h' + index,
			label: spot.label || '',
			link: spot.link || '',
			path: linkPath(spot.link),
			style: [
				'left:' + num(spot.x, 0) + '%',
				'top:' + num(spot.y, 0) + '%',
				'width:' + num(spot.width, 0) + '%',
				'height:' + num(spot.height, 0) + '%',
			].join(';'),
		};
	});
}

/**
 * 单个组件 → 渲染节点。tab 子组件递归处理。
 */
function prepareNode(component, depth, query, styleDefaults) {
	const p = component.props || {};
	const configuredRegions=effectiveRegions(component.type,p,styleDefaults);
	const actions = (query || {})._actions || prepareActions();
	const node = {
		id: component.id || component.type,
		type: component.type,
		style: boxStyle(p),
		link: p.link || '',
		path: linkPath(p.link),
		actions: Object.assign({}, actions, { enabled: component.type === 'social' ? actions.enabled : 0 }),
		regionStyles: regionStyles(configuredRegions),
		regionTextStyles: regionTextStyles(configuredRegions),
	};
	if (depth > 4) {
		node.type = 'spacer';
		node.height = px(0);
		return node;
	}

	switch (component.type) {
		case 'business': case 'social': case 'product_detail': case 'album_list': case 'favorite_list': case 'member_center': case 'history_list': case 'reading_preference':
			node.type = component.type === 'product_detail' ? 'product_detail' : 'business';
			node.source = component.type === 'product_detail' ? 'detail' : component.type === 'social' ? 'home' : p.source || ({album_list:'catalog',favorite_list:'favorites',member_center:'mine',history_list:'history',reading_preference:'preference'})[component.type] || 'home';
			node.query = Object.assign({},query || {},{_memberOptions:Object.assign({},p,{source:node.source,regions:configuredRegions,_pageBackground:(query||{})._pageBackground})});
			break;
		case 'product_info':case 'product_highlights':case 'product_details':node.query=query||{};node.options=p;if(component.type==='product_info')node.htmlStyles=(styleDefaults||{}).html||{};break;
		case 'markdown': node.html=richRegionHtml(p.html,node.regionStyles,'default',(styleDefaults||{}).html);node.textStyle='font-size:'+px(p.size||14)+';color:'+(p.color||'#F2F2F4')+';';break;
  case 'view_title':
   node.titleStyle='position:absolute;inset:0;'+(Number(p.boxWidth)>0?'':'width:auto;')+'z-index:10;pointer-events:none;';
   node.lines=(p.lines||[]).filter(line=>String(line.text||'').trim()).map(function(line,i){return Object.assign({},line,{id:'line_'+i,style:'position:absolute;pointer-events:auto;left:'+px(line.left)+';'+(line.anchor==='top'?'top:'+px(line.top):'bottom:calc(var(--fb-current-tab-height,0px) + '+px(line.bottom)+')')+';'+(line.width?'width:'+px(line.width)+';':'max-width:calc(100% - '+px(line.left)+' - 32rpx);')+'font-size:'+px(line.size)+';font-weight:'+line.weight+';color:'+line.color+';font-family:'+fontFamily(line.family)+';line-height:'+line.lineHeight+';letter-spacing:'+px(line.letterSpacing)+';text-align:'+line.align+';white-space:pre-wrap;'});});break;
		case 'hero':
		case 'carousel':
			node.height = px(p.height);
			node.autoplay = !!p.autoplay;
			node.interval = num(p.interval, 4000);
			node.indicator = !!p.indicator;
			node.muted = !!p.muted;
			node.items = prepareItems(p.items);
			node.lines = component.type === 'hero' ? prepareLines(p.lines) : [];
			node.hotspots = component.type === 'hero' ? prepareHotspots(p.hotspots) : [];
			break;
		case 'image':
			node.src = p.src || '';
			node.alt = p.alt || '';
			const media = (p.regions || {}).media || {};
			const imageHeight = Object.prototype.hasOwnProperty.call(media, 'height') ? num(media.height, 0) : num(p.height, 0);
			node.fit = !imageHeight ? 'widthFix' : p.fit === 'contain' ? 'aspectFit' : p.fit === 'fill' ? 'scaleToFill' : 'aspectFill';
			node.mediaStyle = 'width:100%;display:block;' + (num(p.height, 0) > 0 ? 'height:' + px(p.height) + ';' : '');
			break;
		case 'text':
			node.text = p.text || '';
			node.textStyle = [
				'font-size:' + px(p.size),
				'font-weight:' + num(p.weight, 400),
				'color:' + (p.color || '#F2F2F4'),
				'line-height:' + num(p.lineHeight, 1.5),
				'letter-spacing:' + px(p.letterSpacing),
				'text-align:' + (p.align || 'left'),
				"font-family:" + fontFamily(p.family),
				'word-break:break-all',
			].join(';');
			break;
		case 'video':
			node.src = p.src || '';
			node.poster = p.poster || '';
			node.height = px(p.height);
			node.autoplay = !!p.autoplay;
			node.controls = p.controls !== 0;
			node.loop = !!p.loop;
			node.muted = !!p.muted;
			break;
		case 'search':
			node.placeholder = p.placeholder || '搜索';
			node.action = p.action || '';
			node.inputStyle = 'background:' + (p.bg || '#17171A') + ';color:' + (p.color || '#F2F2F4') + ';';
			node.iconStyle = regionIconStyle('search', ((p.regions || {}).icon || {}).color);
			break;
		case 'divider':
			node.dividerStyle = [
				'border-top:' + px(num(p.thickness, 1) || 1) + ' ' + (p.dashed ? 'dashed' : 'solid') + ' ' + (p.color || '#2C2C31'),
				'width:' + num(p.width, 100) + '%',
				'margin:0 auto',
			].join(';');
			break;
		case 'spacer':
			node.height = px(p.height);
			break;
		case 'tab':
			node.size = px(p.size);
			node.color = p.color || '#79797F';
			node.activeColor = p.activeColor || '#F2F2F4';
			node.indicator = p.indicator !== 0;
			node.tabs = (p.tabs || []).map(function (tab, index) {
				return {
					k: 't' + index,
					title: tab.title || '',
					children: (tab.children || []).map(function (child) {
						return prepareNode(child, depth + 1, query, styleDefaults);
					}),
				};
			});
			break;
		default:
			node.type = 'spacer';
			node.height = '0rpx';
	}
	return node;
}

function prepareActions(raw) {
 raw=Object.assign({enabled:1,left:10,bottom:10,size:40,gap:10,bg:'rgba(11,11,12,0.7)',color:'#FFFFFF',radius:20,items:[]},raw||{});
 const buttonStyle=(item)=>'width:'+raw.size+'px;height:'+raw.size+'px;background:'+(item.bg||raw.bg)+';color:'+(item.color||raw.color)+';border-radius:'+(item.radius===undefined?raw.radius:item.radius)+'px;';
 raw.style='left:'+raw.left+'px;right:auto;bottom:'+raw.bottom+'px;--fb-action-gap:'+raw.gap+'px;--fb-action-size:'+raw.size+'px;--fb-action-bg:'+raw.bg+';--fb-action-color:'+raw.color+';--fb-action-radius:'+raw.radius+'px;';
 raw.items=(raw.items||[]).slice(0,5).map((item,i)=>{ const icon=ACTION_ICONS[item.icon]||ACTION_ICONS.grid; const svg='<svg xmlns="http://www.w3.org/2000/svg" viewBox="'+icon.viewBox+'" fill="none" stroke="'+(item.color||raw.color)+'" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'+icon.body+'</svg>';return Object.assign({},item,{id:'action_'+i,style:buttonStyle(item),iconStyle:'background-image:url("data:image/svg+xml,'+encodeURIComponent(svg)+'");'}); });
 return raw;
}

function prepareTabs(bar,query) {
 const active=({detail:'home',search:'catalog',history:'mine',preference:'mine',about:'mine',privacy:'mine',agreement:'mine'})[query.key]||query.key;
 return (bar.items||[]).map(function(item,i){const color=item.link==='page:'+active?bar.activeColor:bar.color;const icon=ACTION_ICONS[item.icon]||ACTION_ICONS.grid;const builtin='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="'+color+'" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'+icon.body+'</svg>';const svg=item.iconSvg?String(item.iconSvg).replace(/currentColor/g,color):builtin;return {path:item.id||'tab_'+i,text:item.label,link:item.link,color:color,selected:item.link==='page:'+active,iconStyle:'background-image:url("data:image/svg+xml,'+encodeURIComponent(svg)+'");'};});
}

function logoStyle(logo, hasBack) {
	const style = ['position:relative', 'top:' + px(num(logo.top, 0))];
	if (logo.align === 'center') {
		style.push('margin-left:auto');
		style.push('margin-right:auto');
	} else if (logo.align === 'right') {
		style.push('margin-left:auto');
	} else {
		style.push('margin-right:auto');
		style.push('margin-left:' + px(hasBack ? 40 : 0));
	}
	if (num(logo.width, 0) > 0) {
		style.push('width:' + px(logo.width));
	}
	if (num(logo.height, 0) > 0) {
		style.push('height:' + px(logo.height));
	}
	if (num(logo.radius, 0) > 0) {
		style.push('border-radius:' + px(logo.radius));
	}
	return style.join(';');
}

/**
 * 页面配置 → 页面可直接 setData 的结构。
 */
function prepare(config, query) {
	config = config || {};
	const hasComponent = (list, type) => (list || []).some(n => n.type === type || ((n.props || {}).tabs || []).some(tab => hasComponent(tab.children, type)));
	const hasDetachedSections = list => (list || []).some(n => ((n.type==='product_detail' || (['social','business'].indexOf(n.type)>=0 && (n.props||{}).source==='detail')) && (n.props||{}).sectionsDetached===1) || ((n.props||{}).tabs||[]).some(tab=>hasDetachedSections(tab.children)));
	const detachedSections=hasDetachedSections(config.components);
	query = Object.assign({}, query || {}, {_actions:prepareActions(config.actions),_separateTitle:(config.components||[]).some(n=>n.type==='view_title'),_separateProductInfo:detachedSections||hasComponent(config.components,'product_info'),_separateHighlights:detachedSections||hasComponent(config.components,'product_highlights'),_separateDetails:detachedSections||hasComponent(config.components,'product_details')});
	const layout = config.layout || {};
 query._pageBackground=layout.bg||'#000000';
	const nav = config.nav || {};
	const logo = nav.logo || {};
	const back = nav.back || {};
 const backAppearance=Object.assign({},((config.styleDefaults||{}).controls||{}).navigationBack||{},back.style||{});
	const hasBack = !!back.show;
	const flow=(config.components||[]).filter(n=>n.type!=='view_title');
 const coverNav=!!(flow[0]&&flow[0].props.coverNav);
 const topGap=layout.topGap===undefined?(nav.enabled&&!coverNav?Number(nav.firstGap)||0:0):Number(layout.topGap)||0;
	return {
		fullScreen: flow.length===1 && (flow[0].type==='social' || (flow[0].type==='business' && flow[0].props.source==='home')),
		bg: layout.bg || '#000000',
  tabs:prepareTabs(config.tabbar||{},query),tabbarStyle:'height:calc('+px((config.tabbar||{}).height||56)+' + env(safe-area-inset-bottom,0px));background:'+((config.tabbar||{}).bg||'#0B0B0C')+';',
		contentBottomGap:px(layout.bottomGap),
		tabbarInset:config.tabbar&&config.tabbar.enabled?'calc('+px(config.tabbar.height)+' + env(safe-area-inset-bottom,0px))':'0px',
		componentGap: px(layout.componentGap),
		firstGap:px(topGap),topGap:topGap,
		nodes: (config.components || []).map(function (component) {
			return prepareNode(component, 0, query, config.styleDefaults);
		}),
		nav: {
			enabled: !!nav.enabled,
			overlay: coverNav,
			height: px(num(nav.height, 44) || 44),
			bg: nav.bg || '#0B0B0C',
			padLeft: px(num(logo.offsetLeft, 0)),
			padRight: px(num(logo.offsetRight, 0)),
			back: {
				show: hasBack,
				path: linkPath(back.link),
    style:regionStyle(backAppearance),iconStyle:regionIconStyle('back',backAppearance.color),
    floatingStyle:'left:'+px(num(logo.offsetLeft,16))+';top:calc('+num(query._statusBar,0)+'px + '+px(num(nav.height,44)/2)+');',
			},
			logo: {
				enabled: logo.enabled !== 0,
				detached: false,
				floating: logo.enabled !== 0 && !nav.enabled,
				floatingStyle: 'top:' + num(query._statusBar, 0) + 'px;height:' + px(num(nav.height, 44) || 44) + ';left:' + px(num(logo.offsetLeft,16)) + ';right:' + px(num(logo.offsetRight,16)) + ';',
				type: logo.type === 'image' && logo.image ? 'image' : 'text',
				text: logo.text || '',
				image: logo.image || '',
				style: logoStyle(logo, hasBack),
				imageStyle: 'height:100%;' + (logo.invert ? 'filter:invert(1);' : '') + (num(logo.radius, 0) > 0 ? 'border-radius:' + px(logo.radius) + ';' : ''),
				textStyle: 'color:' + (logo.color || '#F2F2F4') + ';font-size:' + px(num(logo.fontSize, 16) || 16) + ';font-weight:700;letter-spacing:2rpx;white-space:nowrap;',
				path: linkPath(logo.link),
			},
		},
		tabbar: !!(config.tabbar && config.tabbar.enabled),
	};
}

module.exports = {effectiveRegions,
	px: px,
	linkPath: linkPath,
	openLink: openLink,
	prepare: atomicPrepare,
	prepareNode: prepareNode,
	boxStyle: boxStyle,
	regionStyle: regionStyle,
	regionStyles: regionStyles,
	regionTextStyles: regionTextStyles,
	regionIconStyle: regionIconStyle,
	richRegionHtml: richRegionHtml,
	fontFamily: fontFamily,
	prepareActions: prepareActions,
};

function atomicPrepare(config,query,context){
 const Core=require('./composer-core'),cfg=config||{},ctx=Object.assign({},context||{},{key:(query||{}).key||(context||{}).key}),schema=Object.assign({},cfg.schema,{typography:cfg.typography,layout:cfg.layout}),q=query||{},chrome=Core.chrome(cfg,q.key,ctx,true),body=Core.theme(cfg.layout,cfg.typography),nodes=Core.prepare(cfg.components,schema,ctx,'components',true);
 const buttonIcon=(name,color,filled)=>{const icon=ACTION_ICONS[name];if(!icon)return '';return 'background-image:url("data:image/svg+xml,'+encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="'+icon.viewBox+'" fill="'+(filled?color:'none')+'" stroke="'+color+'" stroke-width="1.7">'+icon.body+'</svg>')+'");';};chrome.back.iconStyle=buttonIcon('back',(cfg.nav.back.style||{}).color||body.color)+chrome.back.iconStyle+'background-size:contain;background-repeat:no-repeat;background-position:center;';const compact=list=>list.forEach(node=>{const ctx=node.context||{},spec=node.info||{};if((node.kind==='button'||node.kind==='icon')&&node.props.icon)node.iconStyle=buttonIcon(node.props.icon,node.props.color||body.color)+(node.kind==='button'?node.buttonIconStyle+';':'')+'width:'+(node.kind==='icon'&&Number(node.props.boxWidth)?'100%':'1em')+';height:'+(node.kind==='icon'&&Number(node.props.boxHeight)?'100%':'1em');if(node.kind==='button'&&node.props.icon)node.iconStyle=buttonIcon(node.props.icon,node.props.iconColor||node.props.color||body.color)+node.buttonIconStyle;if(node.kind==='select'){node.choice.iconStyle=buttonIcon('chevron_down',node.props.color||body.color);const available=Math.max(node.props.optionHeight,node.props.sheetMaxHeight-2*node.props.sheetPad-(cfg.typography.title.size||0)*(cfg.typography.title.lineHeight||1)-node.props.sheetGap);node.choice.listHeight=px(Math.min(available,node.items.length*(node.props.optionHeight+node.props.sheetGap)));}if(node.kind==='actions'){node.fab.icons={};for(const key of ['bookmark','share','plus','close','volume_on','volume_off'])node.fab.icons[key]=buttonIcon(key,node.props.color||body.color);node.fab.icons.bookmark_active=buttonIcon('bookmark',node.props.color||body.color,true);node.fab.muted=true;}if(node.type==='member_avatar'&&!node.src)node.iconStyle=buttonIcon('visitor',node.props.color||body.color)+'width:'+(Number(node.props.boxWidth)?'100%':'1em')+';height:'+(Number(node.props.boxHeight)?'100%':'1em');if(node.kind==='progress')node.progressColor=node.props.color||body.color;node.context={uid:(ctx.member||{}).uid||0,productid:(ctx.product||{}).productid||0,item:ctx.item||{},commerce:ctx.commerce||{},home:(ctx.home||{}).current||{},key:ctx.key,keyword:(ctx.query||{}).keyword||'',query:ctx.query||{}};if(node.kind==='select')node.selectedLabel=(node.items.find(i=>i.selected)||node.items[0]||{}).label||'';if(node.kind==='info'){node.headingStyle=Core.style({textRole:'title'},'rpx',node.typography);node.infoRows=[];const p=node.props;if(p.showColors&&(spec.colors||[]).length)node.infoRows.push(['颜色',spec.colors.map(Core.decode).join(' ')]);if(p.showSizes&&(spec.sizes||[]).length)node.infoRows.push(['尺码',spec.sizes.map(Core.decode).join(' ')]);if(p.showFit&&(spec.fit||spec.model))node.infoRows.push(['版型',Core.decode((spec.fit||'')+' '+(spec.model||''))]);if(p.showFields)for(const f of spec.fields||[])node.infoRows.push([Core.decode(f.label),Core.decode(f.value)]);}compact(node.children||[]);for(const row of node.rows||[])compact(row.children);for(const tab of node.tabs||[])compact(tab.children);});compact(nodes);let muted=true;const sound=list=>list.forEach(node=>{if(node.type==='home_gallery')muted=!!node.props.muted;sound(node.children||[]);});sound(nodes);const applySound=list=>list.forEach(node=>{if(node.fab)node.fab.muted=muted;applySound(node.children||[]);});applySound(nodes);
 const footer=chrome.footer;footer.items.forEach(item=>{const icon=ACTION_ICONS[item.icon]||ACTION_ICONS.grid,svg=item.iconSvg?item.iconSvg.replace(/currentColor/g,item.color):'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="'+item.color+'" stroke-width="1.7">'+icon.body+'</svg>';item.iconStyle='background-image:url("data:image/svg+xml,'+encodeURIComponent(svg)+'");'+footer.iconStyle;});
 const fullFeed=list=>list.some(node=>node.visible&&(node.kind==='gallery'&&node.vertical&&node.props.fillViewport||fullFeed(node.children||[])||(node.rows||[]).some(row=>fullFeed(row.children))||(node.tabs||[]).some(tab=>fullFeed(tab.children))));
 return {nodes,chrome,feedLocked:fullFeed(nodes),theme:body,textStyle:Core.style(cfg.typography.body,'rpx',cfg.typography),mainStyle:'padding-left:'+px(cfg.layout.sideGap)+';padding-right:'+px(cfg.layout.sideGap)+';padding-top:'+px(cfg.layout.topGap)+';padding-bottom:'+px(cfg.layout.bottomGap)+';gap:'+px(cfg.layout.componentGap),footer,background:body.bg,foreground:body.color};
}
