// Run: JSDOM_PATH=/absolute/node_modules/jsdom node tests/micro_editor.cjs
// Uses a DOM model, not browser automation. Product data is read from the local PHP service.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const {JSDOM}=require(process.env.JSDOM_PATH||'jsdom');
const root=path.resolve(__dirname,'..');
const bootstrap=String.raw`<?php
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';
require './source/class/class_core.php'; C::app()->init();
use flipbook\front_micro;
$id=front_micro::preview_productid();$row=flipbook\table_flipbook_product::t()->fetch($id);
$spec=flipbook\front_helper::spec($row['spec']??'');$spec['contentHtml']=flipbook\front_markdown::render($spec['content']??'');
$componentHTML=[];
foreach(['product_info','product_highlights','product_details'] as $type){
 $_GET=['key'=>'detail','business_preview'=>1,'preview_component'=>$type,'productid'=>$id];
 ob_start();include DISCUZ_ROOT.'source/plugin/flipbook/micro.inc.php';$componentHTML[$type]=ob_get_clean();
}
echo json_encode(['componentHTML'=>$componentHTML,'config'=>front_micro::sanitize(front_micro::default_config('home')),'regions'=>front_micro::region_schema(),'sources'=>front_micro::region_sources(),'selectors'=>front_micro::all_region_selectors(),'componentDefaults'=>front_micro::component_defaults(),'types'=>front_micro::types(),'keys'=>front_micro::keys(),'product'=>$spec,'productid'=>$id],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
`;
const data=JSON.parse(execFileSync('docker',['exec','-i',process.env.FLIPBOOK_PHP_CONTAINER||'flipbook-php','php'],{input:bootstrap,encoding:'utf8'}));
const config=structuredClone(data.config);
config.components=[
 {id:'search-a',type:'search',props:{placeholder:'搜索产品',action:'page:search',bg:'#17171A',color:'#A1A1A6'}},
 {id:'search-b',type:'search',props:{placeholder:'其他搜索',bg:'#17171A',color:'#A1A1A6'}},
 {id:'info',type:'product_info',props:{title:'产品信息',size:14,color:'#F2F2F4',showColors:1,showSizes:1,showFit:1,showTable:1,showFields:1,showContent:1}},
 {id:'title',type:'view_title',props:{lines:[{text:'Custom',left:20,bottom:20,size:16,weight:400,color:'#fff',align:'left',anchor:'bottom',family:'sans',lineHeight:1.4}]}},
 {id:'tab',type:'tab',props:{tabs:[{title:'一',children:[{id:'nested',type:'search',props:{placeholder:'Nested',bg:'#333',color:'#fff'}}]},{title:'二',children:[]}]}},
 {id:'carousel',type:'carousel',props:{height:360,indicator:1,items:[{image:'/a.png'},{image:'/b.png'}]}},
 {id:'hero',type:'hero',props:{height:480,indicator:1,items:[{image:'/a.png'},{image:'/b.png'}],lines:[{text:'Line',x:6,y:80,size:24,color:'#fff',weight:700}],hotspots:[{x:0,y:0,width:10,height:10,label:'Hot'}]}},
 {id:'markdown',type:'markdown',props:{markdown:'# 标题\n\n段落 [链接](https://example.com)\n- A\n> Q\n```\ncode\n```',size:14,color:'#fff'}},
 ...['product_detail','album_list','favorite_list','member_center','history_list','image','video','text','divider','spacer'].map(type=>({id:type,type,props:{source:({product_detail:'detail',album_list:'catalog',favorite_list:'favorites',member_center:'mine',history_list:'history',reading_preference:'preference'})[type]}}))
];
config.components[8].props.source='home'; // fixed product_detail routing must ignore an imported legacy source
config.components.push({id:'home-social',type:'social',props:{source:'detail'}}); // fixed social routing must ignore a stale source
for(const type of ['product_highlights','product_details'])if(data.types[type])config.components.push({id:type,type,props:{title:type==='product_highlights'?'产品卖点':'细节展示',size:14,color:'#F2F2F4',showTitle:1,showText:1,showCode:type==='product_highlights'?1:0}});
const dom=new JSDOM('<div class="fbmp"><aside class="fbmp-library"><button class="fb-admin-button" data-inspector="actions">左下角操作菜单</button><details class="fbmp-palette-group" open><summary>基础组件</summary><button data-add="text">文字</button></details><details class="fbmp-palette-group" open><summary>展示组件</summary></details><details class="fbmp-palette-group" open><summary>业务组件</summary></details></aside><div class="fbmp-body"><section class="fbmp-canvas-stage"><div id="fbmp-scale-controls" class="fbmp-preview-controls"><button class="fb-admin-button" data-preview-scale="25">25%</button><button class="fb-admin-button" data-preview-scale="50">50%</button><button class="fb-admin-button" data-preview-scale="75">75%</button><button class="fb-admin-button" data-preview-scale="100">100%</button><input id="fbmp-scale" type="number" min="10" max="200"><output id="fbmp-preview-size"></output></div><div class="fbmp-canvas"><div id="fbmp-viewport" class="fbmp-viewport"><div id="fbmp-frame" class="fbmp-frame"></div></div></div></section><aside class="fbmp-config"><div id="fbmp-inspector"></div></aside></div></div><script id="fbmp-data" type="application/json">'+JSON.stringify(config)+'</script><input id="fbmp-config"><form id="fbmp-form"></form>'+['save','undo','redo','export','import'].map(id=>'<button id="fbmp-'+id+'"></button>').join(''),{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only'});
const {window:w}=dom;w.FBMP_UPLOAD='/admin.php?upload=1';w.FBMP_ICONS=Object.fromEntries(data.config.tabbar.items.map(item=>[item.icon,[]]));w.FBMP_DEFAULTS=data.componentDefaults;w.FBMP_TYPES=data.types;w.FBMP_KEYS=data.keys;w.FBMP_REGIONS=data.regions;w.FBMP_REGION_SOURCES=data.sources;w.FBMP_REGION_SELECTORS=data.selectors;w.FBMP_PREVIEW_PRODUCT=data.product;w.FBMP_PREVIEW_PRODUCTID=data.productid;
let canvasObserver;
w.ResizeObserver=class{constructor(callback){this.callback=callback;}observe(element){if(element.classList.contains('fbmp-canvas'))canvasObserver=this;}disconnect(){}};
w.HTMLFormElement.prototype.submit=function(){};
w.document.body.setAttribute('data-fb-admin','');
const commonStyle=w.document.createElement('style');commonStyle.textContent=fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/admin/admin.css'),'utf8');w.document.head.appendChild(commonStyle);
const stylesheet=w.document.createElement('style');stylesheet.textContent=fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/admin/micropage.css'),'utf8');w.document.head.appendChild(stylesheet);
w.eval(fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/admin/admin.js'),'utf8'));
w.eval(fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/admin/micropage.js'),'utf8'));
const $=s=>w.document.querySelector(s),$$=s=>[...w.document.querySelectorAll(s)];
function snapshot(){ $('#fbmp-save').dispatchEvent(new w.Event('click'));return JSON.parse($('#fbmp-config').value); }
function select(index){$('[data-node="components.'+index+'"] > .fbmp-node-body').click();}
function regionTab(){const tab=$$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='regions');assert(tab);tab.click();}
function input(selector,value){const el=$(selector);assert(el,selector);el.value=value;el.dispatchEvent(new w.Event('input',{bubbles:true}));}
function check(name,fn){fn();console.log('PASS '+name);}
function loadProductPreview(index,type){const preview=$('[data-node="components.'+index+'"] iframe'),doc=preview.contentDocument;doc.open();doc.write(data.componentHTML[type]);doc.close();delete doc._fbmpRegionCapture;preview.dispatchEvent(new w.Event('load'));return preview;}
(async function(){
await new Promise(resolve=>w.setTimeout(resolve,0));
check('preview bottom navigation reuses native icons, configured colors and active-page aliases',()=>{
 const before=snapshot(),bar=$('.fbmp-preview-tabbar'),items=config.tabbar.items;
 assert.equal(bar.children.length,items.length);
 items.forEach((item,index)=>{
  const tab=bar.children.item(index);
  assert.equal(tab.querySelector('use').getAttribute('href'),'#fb-i-'+item.icon);
  assert.equal(tab.querySelector('.fbmp-preview-tab-text').textContent,item.label);
  assert.equal(w.getComputedStyle(tab.querySelector('svg')).strokeWidth,'1.7');
 });
 bar.click();
 const height=$('#fbmp-inspector [data-field="tabbar.height"]');assert(height);
 for(const [key,expected] of [['home','home'],['catalog','catalog'],['favorites','favorites'],['mine','mine'],['detail','home'],['search','catalog'],['history','mine'],['preference','mine'],['about','mine'],['privacy','mine'],['agreement','mine']]){
  w.history.replaceState({},'', '/admin.php?key='+key);
  input('#fbmp-inspector [data-field="tabbar.height"]',before.tabbar.height);
  assert.equal($('.fbmp-preview-tab.is-active').dataset.tabLink,'page:'+expected);
  const colorProbe=w.document.createElement('span');colorProbe.style.color=before.tabbar.activeColor;assert.equal($('.fbmp-preview-tab.is-active').style.color,colorProbe.style.color);
  colorProbe.style.color=before.tabbar.color;$$('.fbmp-preview-tab:not(.is-active)').forEach(tab=>assert.equal(tab.style.color,colorProbe.style.color));
 }
 w.history.replaceState({},'','/admin.php');
 input('#fbmp-inspector [data-field="tabbar.height"]',before.tabbar.height);
 assert.deepEqual(snapshot(),before);
 $('.fbmp-preview-nav').click();
});
check('undoing every edit back to the saved configuration clears the dirty indicator and redo restores it',()=>{
 const before=snapshot();
 $('.fbmp-preview-nav').click();
 const pageTab=$$('#fbmp-inspector [data-config-tab]').find(tab=>tab.dataset.configTab==='page');assert(pageTab);pageTab.click();
 input('#fbmp-inspector [data-field="layout.bg"]','#123456');
 assert.equal($('#fbmp-save').textContent,'保存并同步 ·');
 $('#fbmp-undo').click();assert.equal($('#fbmp-save').textContent,'保存并同步');
 $('#fbmp-redo').click();assert.equal($('#fbmp-save').textContent,'保存并同步 ·');
 $('#fbmp-undo').click();assert.equal($('#fbmp-save').textContent,'保存并同步');
 assert.deepEqual(snapshot(),before);
});
check('component toolbars start hidden, stay hidden on hover, and use shared action buttons',()=>{
 assert.equal($$('#fbmp-frame .fbmp-node-bar:not([hidden])').length,0);
 $('[data-node="components.0"]').dispatchEvent(new w.MouseEvent('mouseover',{bubbles:true}));
 assert.equal($$('#fbmp-frame .fbmp-node-bar:not([hidden])').length,0);
 assert.equal($$('#fbmp-frame [data-node-op]').length,0,'operations belong outside the mobile content');
 assert.deepEqual($$('.fbmp-component-tools [data-node-op]').map(button=>button.dataset.nodeOp),['up','down','copy','del','refresh']);
 assert($$('.fbmp-component-tools button').every(button=>button.classList.contains('fb-admin-button') && button.querySelector('svg')));
 assert($('.fbmp-component-tools').hidden);
 assert(!$('#fbmp-frame').contains($('.fbmp-component-tools')));
 assert(!$('.fbmp-actions-region').classList.contains('fb-admin-button'));
 assert.equal($('.fbmp-actions-region').tagName,'DIV');
});
check('selecting components shows one occupying toolbar without shrinking content and keeps title and action rows below content',()=>{
 const before=snapshot(),frame=$('#fbmp-frame'),reader=$('[data-node="components.8"] iframe'),page=$('.fbmp-preview-page'),global=$('.fbmp-global-controls'),footer=$('.fbmp-preview-tabbar');
 // jsdom has no layout engine; provide measured chrome dimensions for the actual selection-event sizing calculation.
 Object.defineProperty(frame,'clientHeight',{configurable:true,value:812});
 Object.defineProperty($('.fbmp-nav-region'),'offsetHeight',{configurable:true,value:44});
 Object.defineProperty(footer,'offsetHeight',{configurable:true,value:config.tabbar.enabled?config.tabbar.height:0});
 $$('.fbmp-node-bar').forEach(toolbar=>Object.defineProperty(toolbar,'offsetHeight',{configurable:true,get(){return this.hidden?0:48;}}));
 Object.defineProperty(global,'offsetHeight',{configurable:true,get(){return this.hidden?0:48;}});
 $('.fbmp-preview-nav').click();
 const full=812-44-(config.tabbar.enabled?config.tabbar.height:0)-config.nav.firstGap;
 assert.equal(reader.style.height,Math.max(240,full)+'px');
 select(0);
 assert(!$('.fbmp-component-tools').hidden);assert.equal($('.fbmp-component-tools').dataset.path,'components.0');
 assert.equal($$('.fbmp-node-bar:not([hidden])').length,1);
 assert.equal($('.fbmp-node-bar:not([hidden])').dataset.select,'components.0');
 assert.equal(reader.style.height,Math.max(240,full)+'px','selection adds outer content instead of shrinking an inner scroller');
 select(1);assert.equal($$('.fbmp-node-bar:not([hidden])').length,1);
 assert.equal($('[data-node="components.8"] iframe'),reader,'selection must preserve loaded business DOM');
 $('[data-node="components.3"] .fbmp-view-line').click();
 const titleBar=$('.fbmp-node-bar:not([hidden])');
 assert.equal(titleBar.dataset.select,'components.3');assert.equal(titleBar.parentElement,global);
 assert(page.compareDocumentPosition(global)&w.Node.DOCUMENT_POSITION_FOLLOWING);
 assert(global.compareDocumentPosition(footer)&w.Node.DOCUMENT_POSITION_FOLLOWING);
 assert.equal(w.getComputedStyle($('[data-node="components.3"]')).position,'absolute');
 assert.equal($('[data-node="components.3"]').style.bottom,'48px');
 assert.equal($('[data-node="components.3"] .fbmp-view-line').style.bottom,(20+(config.tabbar.enabled?config.tabbar.height:0))+'px','configured overlay anchor stays independent');
 $('.fbmp-library [data-inspector="actions"]').click();
 assert.equal($$('.fbmp-node-bar:not([hidden])').length,1);
 const actionBar=$('.fbmp-node-bar:not([hidden])');assert(actionBar.classList.contains('fbmp-actions-region'));
 assert.equal(actionBar.parentElement,global);assert.equal(w.getComputedStyle(actionBar).width,'100%');
 assert.equal(actionBar.getAttribute('role'),'button');
 assert.equal($('[data-node="components.8"] iframe'),reader);
 $('.fbmp-preview-nav').click();assert.equal($$('.fbmp-node-bar:not([hidden])').length,0);assert(global.hidden);assert($('.fbmp-component-tools').hidden);
 assert.deepEqual(snapshot(),before);
});
check('preview zoom events retain native 375px coordinates and 720px effective width and proportional scaled occupancy without changing page data',()=>{
 const reader=loadProductPreview(2,'product_info'),content=reader.contentDocument.querySelector('.fb-product-info');
 Object.defineProperty(content,'offsetHeight',{configurable:true,value:284});
 Object.defineProperty(content,'scrollHeight',{configurable:true,value:312});
 content.getBoundingClientRect=()=>({height:545.28,width:720});reader.dispatchEvent(new w.Event('load'));
 const before=snapshot(),frame=$('#fbmp-frame'),viewport=$('#fbmp-viewport'),readerSource=reader.src,contentStyle=content.style.cssText,logicalStyle=reader.contentDocument.querySelector('[data-fbmp-logical-viewport]').textContent;
 assert.equal(reader.style.height,'284px','natural layout height must not reuse the zoomed visual rect');
 const main=reader.contentDocument.querySelector('.fb-mc-main');
 Object.defineProperty(main,'offsetHeight',{configurable:true,value:294});
 reader.dispatchEvent(new w.Event('load'));
 assert.equal(reader.style.height,'294px','include the final child margin contained by the natural-height flow root');
 const readerStyle=reader.style.cssText;
 assert.match(logicalStyle,/--fb-rpx:0.5px!important/);
 assert.match(logicalStyle,/text-size-adjust:100%/);
 const designWidth=375,designHeight=812,displayWidth=720;
 assert.equal(parseFloat(frame.style.width),designWidth);
 assert.equal(frame.style.height,'auto');
 for(const percent of [25,50,75,100]){
  $('[data-preview-scale="'+percent+'"]').click();
  const scale=displayWidth/designWidth*percent/100;
  assert.equal(parseFloat(frame.style.width),designWidth);
  assert.equal(frame.style.height,'auto');
  assert.equal(frame.style.zoom,'3.84','percentage changes must not resize the iframe layout context');
  assert.equal(frame.style.transform,'','zoom must not raster-scale the frame with a transform');
  const displayScale=parseFloat(viewport.style.getPropertyValue('--fbmp-display-scale'));
  assert(Math.abs(parseFloat(viewport.style.width)*displayScale-designWidth*scale)<1e-9);
  assert(Math.abs(parseFloat(viewport.style.height)*displayScale-designHeight*scale)<1e-9);
  assert(Math.abs(parseFloat($('.fbmp-preview-shell').style.height)-designHeight*scale)<1e-9);
  assert.equal(viewport.dataset.displayScale,String(scale));
  assert.equal($('[data-preview-scale="'+percent+'"]').getAttribute('aria-pressed'),'true');
  assert.equal($('[data-node="components.2"] iframe'),reader,'zoom must not reload component previews');
  assert.equal(reader.src,readerSource);assert.equal(reader.style.cssText,readerStyle);assert.equal(content.style.cssText,contentStyle);
  assert.equal(reader.contentDocument.querySelector('[data-fbmp-logical-viewport]').textContent,logicalStyle);
  assert.equal(parseFloat($('.fbmp-preview-shell').style.width),displayWidth*percent/100+64);
  assert.deepEqual(snapshot(),before,'display percentage is editor state, not page configuration');
 }
 assert.equal(w.getComputedStyle($('.fbmp-canvas')).overflowX,'hidden');
 assert.equal(w.getComputedStyle($('.fbmp-canvas')).overflowY,'auto');
 assert.equal(w.getComputedStyle(viewport).overflow,'hidden');
 assert.equal(frame.style.willChange,'');
 assert.equal(frame.style.filter,'');
});
check('custom zoom supports typed percentages and safely handles out-of-range or empty edits',()=>{
 input('#fbmp-scale','125');
 assert.equal($('#fbmp-scale').value,'125');assert.equal($('#fbmp-frame').style.zoom,'3.84');
 input('#fbmp-scale','47');
 assert.equal($('#fbmp-scale').value,'47');assert.equal($('#fbmp-frame').style.zoom,'3.84');
 input('#fbmp-scale','47.5');
 assert.equal($('#fbmp-scale').value,'47.5');assert.equal($('#fbmp-frame').style.zoom,'3.84');
 const custom=$('#fbmp-scale');custom.value='5';custom.dispatchEvent(new w.Event('change',{bubbles:true}));
 assert.equal(custom.value,'10');assert.equal($('#fbmp-frame').style.zoom,'3.84');
 custom.value='500';custom.dispatchEvent(new w.Event('change',{bubbles:true}));
 assert.equal(custom.value,'200');assert.equal($('#fbmp-frame').style.zoom,'3.84');
 custom.value='';custom.dispatchEvent(new w.Event('change',{bubbles:true}));
 assert.equal(custom.value,'200');
 $('[data-preview-scale="100"]').click();
});
check('zoom fits the full phone and external tools inside the actual container and follows container resizes without changing content',()=>{
 const canvas=$('.fbmp-canvas'),frame=$('#fbmp-frame'),viewport=$('#fbmp-viewport'),shell=$('.fbmp-preview-shell'),custom=$('#fbmp-scale');
 let available=800;
 Object.defineProperty(canvas,'clientWidth',{configurable:true,get:()=>available});
 const before=snapshot(),reader=$('[data-node="components.2"] iframe'),readerSource=reader.src,readerStyle=reader.style.cssText,content=reader.contentDocument.querySelector('.fb-product-info'),contentStyle=content.style.cssText;
 function inside(requested){
  input('#fbmp-scale',String(requested));
  const paintScale=parseFloat(viewport.style.getPropertyValue('--fbmp-display-scale')),width=parseFloat(viewport.style.width)*paintScale,height=parseFloat(viewport.style.height)*paintScale,scale=Number(viewport.dataset.displayScale);
  assert(width<=available-40-64+.02,'the complete phone must fit after padding and the tool rail');
  assert(parseFloat(shell.style.width)+40<=available+.02,'the shell must never expand the canvas');
  assert(Math.abs(width-375*scale)<1e-8,'fit the complete layout rather than cropping its enlarged edge');
  assert(Math.abs(height-812*scale)<1e-8,'width and height must retain the original phone proportion');
  assert.equal(parseFloat(custom.value),parseFloat(custom.max));
  assert.equal($('[data-node="components.2"] iframe'),reader);assert.equal(reader.src,readerSource);assert.equal(reader.style.cssText,readerStyle);assert.equal(content.style.cssText,contentStyle);assert.deepEqual(snapshot(),before);
 }
 inside(200);
 assert.equal(custom.value,'96.66');assert.match($('#fbmp-preview-size').title,/已适应容器/);
 available=500;assert(canvasObserver,'observe the canvas, not only the browser window');canvasObserver.callback();
 assert.equal(custom.value,'55');inside(125);
 available=360;w.dispatchEvent(new w.Event('resize'));inside(200);assert.equal(custom.value,'35.55');
 available=1800;canvasObserver.callback();assert.equal(custom.value,'200','restore the requested percentage when sufficient room returns');
 assert.equal(frame.style.zoom,'3.84');assert.deepEqual(snapshot(),before);
 $('[data-preview-scale="100"]').click();
});
check('component palette is an exclusive accordion and expansion never changes page configuration',()=>{
 const groups=$$('.fbmp-palette-group'),before=snapshot();
 assert.deepEqual(groups.map(group=>group.open),[true,false,false]);
 groups[1].open=true;groups[1].dispatchEvent(new w.Event('toggle'));
 assert.deepEqual(groups.map(group=>group.open),[false,true,false]);
 groups[2].querySelector('summary').click();groups[2].dispatchEvent(new w.Event('toggle'));
 assert.deepEqual(groups.map(group=>group.open),[false,false,true]);
 groups[2].querySelector('summary').click();
 assert.equal(groups[2].open,false);
 assert.deepEqual(snapshot(),before);
});
check('opening every inspector exposes schema regions without materializing empty overrides',()=>{
 config.components.forEach((component,index)=>{select(index);regionTab();assert.equal($$('#fbmp-inspector [data-region-select] option').length,Object.keys(data.regions[component.type]).filter(key=>!data.sources[component.type]?.[key] || data.sources[component.type][key].includes(component.type==='social'?'home':component.type==='product_detail'?'detail':component.props.source)).length);});
 assert.deepEqual(snapshot().components.map(c=>c.props.regions),config.components.map(()=>undefined));
});
check('configuration tabs wrap instead of shrinking or clipping their labels and use shared action classes',()=>{
 const tabs=$('#fbmp-inspector .fbmp-config-tabs'),button=tabs.querySelector('button');
 assert.equal(w.getComputedStyle(tabs).flexWrap,'wrap');
 assert.equal(w.getComputedStyle(tabs).overflow,'visible');
 assert.equal(w.getComputedStyle(button).flexShrink,'0');
 assert(button.classList.contains('fb-admin-tab'));
 assert($$('#fbmp-inspector button:not([data-config-tab])').every(el=>el.classList.contains('fb-admin-button')));
});
select(0);
check('search content has one visible background control and keeps live placeholder preview',()=>{
 $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='style').click();
 assert.equal($$('#fbmp-inspector input[data-type="text"][data-field="components.0.props.bg"]').length,1);
 $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
 input('#fbmp-inspector input[data-field="components.0.props.placeholder"]','即时搜索');
 assert.equal($('[data-node="components.0"] [data-fb-region="input"]').textContent,'即时搜索');
});
regionTab();
check('first region selects its preview and shows enabled by default',()=>{
 assert.equal($('#fbmp-inspector input[data-field="components.0.props.regions.control.enabled"]').checked,true);
 assert($('[data-node="components.0"] [data-fb-region="control"]').hasAttribute('data-fbmp-region-active'));
});
check('selected regions use a white solid outline while hovered unselected regions use a white dashed outline',()=>{
 select(0);regionTab();
 const selected=$('[data-node="components.0"] [data-fb-region="control"]');
 assert.match(w.getComputedStyle(selected).outline,/solid.*(?:#fff|rgb\(255, 255, 255\))/);
 const hovered=$('[data-node="components.1"] [data-fb-region="input"]');
 hovered.dispatchEvent(new w.MouseEvent('mouseover',{bubbles:true}));
 assert(hovered.hasAttribute('data-fbmp-region-hover'));
 assert.match(w.getComputedStyle(hovered).outline,/dashed.*(?:#fff|rgb\(255, 255, 255\))/);
 assert.match(w.getComputedStyle($('[data-node="components.0"]')).outline,/solid.*(?:#fff|rgb\(255, 255, 255\))/);
});
check('region edits inject only explicit properties and stay scoped to one component',()=>{
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.padLeft"]','12');
 const rule=$('[data-node="components.0"] style[data-fbmp-region-styles]').textContent;
 assert.match(rule,/data-node="components.0"/);assert.match(rule,/padding-left:12px!important/);assert.doesNotMatch(rule,/margin-|font-size|padding-top|border-radius/);
 assert.equal($('[data-node="components.1"] style[data-fbmp-region-styles]').textContent,'');
 assert.deepEqual(snapshot().components[0].props.regions,{control:{padLeft:12}});
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.padLeft"]','');
 assert.equal($('[data-node="components.0"] style[data-fbmp-region-styles]').textContent,'');
 assert.deepEqual(snapshot().components[0].props.regions.control,{});
});
check('live internal region dimensions and text overrides remain logical units at every display percentage',()=>{
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.padLeft"]','17');
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.size"]','19');
 const before=snapshot(),node=$('[data-node="components.0"]'),rule=node.querySelector('style[data-fbmp-region-styles]').textContent;
 for(const percent of [25,50,75,100]){
  $('[data-preview-scale="'+percent+'"]').click();
  assert.equal(node.querySelector('style[data-fbmp-region-styles]').textContent,rule);
  assert.match(rule,/padding-left:17px!important/);assert.match(rule,/font-size:19px!important/);
  assert.equal(node,$('[data-node="components.0"]'));assert.deepEqual(snapshot(),before);
 }
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.padLeft"]','');
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.size"]','');
});
check('negative outer margins survive DOM editing and serialization',()=>{
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.gapLeft"]','-20');
 assert.equal(snapshot().components[0].props.regions.control.gapLeft,-20);
 assert.match($('[data-node="components.0"] style[data-fbmp-region-styles]').textContent,/margin-left:-20px!important/);
 input('#fbmp-inspector input[data-field="components.0.props.regions.control.gapLeft"]','');
 assert.equal(snapshot().components[0].props.regions.control.gapLeft,undefined);
});
check('region reset removes overrides and nested clicks select exact internal region',()=>{
 input('#fbmp-inspector input[data-type="text"][data-field="components.0.props.regions.control.bg"]','#aa1122');
 $('#fbmp-inspector [data-region-reset]').click();
 assert.equal(snapshot().components[0].props.regions.control,undefined);
 $('[data-node="components.0"] [data-fb-region="icon"]').click();
 assert.equal($('#fbmp-inspector [data-region-select]').value,'icon');
});
check('product information uses the formal H5 template and updates content flags without rebuilding its iframe',()=>{
 select(2);
 const preview=loadProductPreview(2,'product_info'),doc=preview.contentDocument;
 const scope=doc.querySelector('.fb-product-info');assert(scope,'formal product_info template should provide its root');
 if(data.product.colors?.length)assert.equal(scope.querySelector('[data-fb-region="colorValue"]').textContent.trim(),data.product.colors[0]);
 if(data.product.sizetable?.length)assert.equal(scope.querySelectorAll('[data-fb-region~="tableRow"]').length,data.product.sizetable.length);
 assert(preview.src.includes('preview_component=product_info'));
 $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
 input('#fbmp-inspector input[data-field="components.2.props.title"]','即时规格标题');
 assert.equal($('[data-node="components.2"] iframe'),preview);
 assert.equal(scope.querySelector('[data-fb-region="heading"]').textContent,'即时规格标题');
 const toggle=$('#fbmp-inspector input[data-field="components.2.props.showColors"]');toggle.checked=false;toggle.dispatchEvent(new w.Event('input',{bubbles:true}));
 const colors=scope.querySelector('[data-fb-region="colors"]');if(colors)assert(colors.hasAttribute('data-fbmp-content-hidden'));
 toggle.checked=true;toggle.dispatchEvent(new w.Event('input',{bubbles:true}));if(colors)assert(!colors.hasAttribute('data-fbmp-content-hidden'));
 // jsdom does not lay out pixels. Supply the measured natural-content height to test sizing behavior.
 Object.defineProperty(scope,'scrollHeight',{configurable:true,value:284});
 input('#fbmp-inspector input[data-field="components.2.props.size"]','16');
 assert.equal(preview.style.height,'284px');assert.equal($('[data-node="components.2"] iframe'),preview);
});
check('product highlights and details use independent formal templates, title updates preserve code, and flags do not reload',()=>{
 for(const type of ['product_highlights','product_details']){
  const index=config.components.findIndex(component=>component.type===type);assert(index>=0,type+' registered');
  select(index);const preview=loadProductPreview(index,type),doc=preview.contentDocument;
  const content=doc.querySelector('[data-fb-product-component="'+type+'"]');assert(content,type+' formal template root');
  assert(preview.src.includes('preview_component='+type));
  if(content.querySelector('[data-fb-product-empty]'))continue;
  const title=content.querySelector('[data-fb-product-title]'),code=content.querySelector('[data-fb-product-code]');
  assert(title);assert(code,'preview endpoint must retain code DOM even when normal details defaults hide it');
  const originalCode=code.textContent;
  $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
  input('#fbmp-inspector input[data-field="components.'+index+'.props.title"]','独立标题');
  assert.equal(title.textContent,'独立标题');assert.equal(code.textContent,originalCode);
  const showCode=$('#fbmp-inspector input[data-field="components.'+index+'.props.showCode"]');
  showCode.checked=false;showCode.dispatchEvent(new w.Event('input',{bubbles:true}));assert(code.hasAttribute('data-fbmp-content-hidden'));
  showCode.checked=true;showCode.dispatchEvent(new w.Event('input',{bubbles:true}));assert(!code.hasAttribute('data-fbmp-content-hidden'));
  const showTitle=$('#fbmp-inspector input[data-field="components.'+index+'.props.showTitle"]');
  showTitle.checked=false;showTitle.dispatchEvent(new w.Event('input',{bubbles:true}));assert(title.hasAttribute('data-fbmp-content-hidden'));
  assert.equal(code.textContent,originalCode);assert.equal($('[data-node="components.'+index+'"] iframe'),preview);
 }
});
check('basic semantic regions are reachable and active tab inherits normal tab region',()=>{
 const expected={3:['line'],4:['head','tab','tabActive','panels','panel'],5:['stage','slide','media','indicators','indicator','indicatorActive'],6:['stage','slide','media','indicators','indicator','indicatorActive','lines','line','hotspots','hotspot'],7:['content','heading','paragraph','link','list','quote','code']};
 for(const [index,keys] of Object.entries(expected)){const scope=$('[data-node="components.'+index+'"]');for(const key of keys)assert(scope.querySelector('[data-fbmp-regions~="'+key+'"]'),index+':'+key);}
 const active=$('[data-node="components.4"] [data-fbmp-region-extra="tabActive"]');assert(active.dataset.fbmpRegions.includes('tab') && active.dataset.fbmpRegions.includes('tabActive'));
});
check('home social and product detail have fixed routes and independent applicable region choices',()=>{
 const homeIndex=config.components.findIndex(component=>component.type==='social');
 for(const [index,source] of [[homeIndex,'home'],[8,'detail']]){
  select(index);regionTab();
  const copy=$('.fbmp-component-tools [data-node-op="copy"]');assert(!copy.disabled);
  const keys=$$('#fbmp-inspector [data-region-select] option').map(el=>el.value);
  assert.equal(new URL($('[data-node="components.'+index+'"] iframe').src).searchParams.get('key'),source);
  if(source==='home'){assert(keys.includes('caption') && keys.includes('extra'));assert(!keys.includes('thumbs') && !keys.includes('price'));}
  else{assert(keys.includes('thumbs') && keys.includes('price'));assert(!keys.includes('extra'));}
  $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
  assert(!$('#fbmp-inspector [data-field="components.'+index+'.props.source"]'),'fixed component source should not appear as a switch');
  assert.equal($$('#fbmp-inspector [data-config-tab="actions"]').length,source==='home'?1:0);
 }
});
check('iframe region clicks cannot navigate and live edits preserve the loaded reader',()=>{
 const preview=$('[data-node="components.8"] iframe');
 const doc=preview.contentDocument;
 doc.open();doc.write('<html><head></head><body><div class="fb-read-stage" data-fb-region="gallery">Media</div><div class="fb-caption" data-fb-region="caption"><a class="fb-caption-title" data-fb-region="title" href="/next">Title</a><span class="fb-caption-sub" data-fb-region="subtitle">Subtitle</span></div><section class="fb-detail-block" data-group="highlight" data-fb-region="highlights"><h3 data-fb-region="highlightTitle">Highlight</h3></section><section class="fb-detail-block" data-group="detail" data-fb-region="details"><h3 data-fb-region="detailTitle">Detail</h3></section></body></html>');doc.close();
 preview.dispatchEvent(new w.Event('load'));
 const anchor=doc.querySelector('.fb-caption-title'),click=new preview.contentWindow.MouseEvent('click',{bubbles:true,cancelable:true});anchor.dispatchEvent(click);
 assert.equal(click.defaultPrevented,true);
 assert.equal($('#fbmp-inspector [data-region-select]').value,'title');
 const regionSelect=$('#fbmp-inspector [data-region-select]');regionSelect.value='caption';regionSelect.dispatchEvent(new w.Event('change',{bubbles:true}));
 input('#fbmp-inspector input[data-field="components.8.props.regions.caption.padLeft"]','18');
 assert.equal($('[data-node="components.8"] iframe'),preview);
 const highlight=doc.querySelector('[data-fb-region="caption"]'),detail=doc.querySelector('[data-fb-region="gallery"]');
 assert(highlight.dataset.fbmpRegions.split(' ').includes('caption'));
 assert(!detail.dataset.fbmpRegions.split(' ').includes('caption'));
 assert(highlight.hasAttribute('data-fbmp-region-active'));
 const css=doc.querySelector('style[data-fbmp-region-styles]').textContent;
 assert.match(css,/caption.*padding-left:18px!important/);
 assert.doesNotMatch(css,/gallery.*padding-left:18px/);
 input('#fbmp-inspector input[data-field="components.8.props.regions.caption.padLeft"]','');
 assert.equal($('[data-node="components.8"] iframe'),preview);
 assert.doesNotMatch((doc.querySelector('style[data-fbmp-region-styles]').textContent.match(/\[data-fbmp-regions~="caption"\]\{[^}]*\}/)||[''])[0],/padding-left/);
});
check('caption visibility overrides author display and keeps title, subtitle, code and extra switches independent without reloading',()=>{
 for(const source of ['home','detail']){
  const index=source==='home'?config.components.findIndex(component=>component.type==='social'):8;select(index);
  const preview=$('[data-node="components.'+index+'"] iframe'),doc=preview.contentDocument;
  doc.open();doc.write('<html><head><style>.fb-hero-info,.fb-caption{display:block}.fb-hero-name,.fb-caption-title{display:block}</style></head><body><div data-fb-region="caption" class="'+(source==='home'?'fb-hero-info':'fb-caption')+'"><span data-fb-region="title" class="'+(source==='home'?'fb-hero-name':'fb-caption-title')+'">Title</span><span class="'+(source==='home'?'fb-hero-sub':'fb-caption-sub')+'"><span data-fb-region="code">CODE</span><span data-fb-region="subtitle">Subtitle</span></span><span data-fb-region="extra" class="fb-hero-sub">Extra</span></div></body></html>');doc.close();delete doc._fbmpRegionCapture;preview.dispatchEvent(new w.Event('load'));
  $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='text').click();
  function flag(key,enabled){const toggle=$('#fbmp-inspector input[data-field="components.'+index+'.props.caption.'+key+'"]');assert(toggle);toggle.checked=enabled;toggle.dispatchEvent(new w.Event('input',{bubbles:true}));assert.equal($('[data-node="components.'+index+'"] iframe'),preview);}
  const caption=doc.querySelector('[data-fb-region="caption"]'),title=doc.querySelector('[data-fb-region="title"]'),subtitle=doc.querySelector('[data-fb-region="subtitle"]'),code=doc.querySelector('[data-fb-region="code"]'),extra=doc.querySelector('[data-fb-region="extra"]');
  flag('enabled',false);assert(caption.hidden);assert.equal(preview.contentWindow.getComputedStyle(caption).display,'none');
  flag('enabled',true);assert(!caption.hidden);assert.equal(preview.contentWindow.getComputedStyle(caption).display,'block');
  flag('showTitle',false);assert(title.hidden);assert(!subtitle.hidden);assert(!code.hidden);
  flag('showSubtitle',false);assert(subtitle.hidden);assert(!code.hidden);assert(!code.parentElement.hidden);
  flag('showCode',false);assert(code.hidden);assert(code.parentElement.hidden);
  flag('showSubtitle',true);assert(!subtitle.hidden);assert(code.hidden);assert(!code.parentElement.hidden);
  flag('showExtra',false);assert(extra.hidden);assert(!subtitle.hidden);
  flag('showTitle',true);flag('showCode',true);flag('showExtra',true);
  input('#fbmp-inspector input[data-field="components.'+index+'.props.caption.size"]','19');
  input('#fbmp-inspector input[data-type="text"][data-field="components.'+index+'.props.caption.color"]','#123456');
  assert.equal(title.style.fontSize,'19px');assert.equal(title.style.color,'rgb(18, 52, 86)');
  assert.equal($('[data-node="components.'+index+'"] iframe'),preview);
 }
});
check('outer tool refresh updates only the selected preview and preserves all component settings',()=>{
 select(0);const before=snapshot(),reader=$('[data-node="components.8"] iframe');
 $('[data-node="components.0"] [data-fb-region="input"]').textContent='stale';
 $('.fbmp-component-tools [data-node-op="refresh"]').click();
 assert.equal($('[data-node="components.0"] [data-fb-region="input"]').textContent,before.components[0].props.placeholder);
 assert.equal($('[data-node="components.8"] iframe'),reader);assert.deepEqual(snapshot(),before);
 select(2);const info=loadProductPreview(2,'product_info');const beforeReload=snapshot();
 $('.fbmp-component-tools [data-node-op="refresh"]').click();
 assert(new URL(info.src).searchParams.has('preview_reload'));assert.equal($('[data-node="components.2"] iframe'),info);
 assert.equal($('[data-node="components.8"] iframe'),reader);assert.deepEqual(snapshot(),beforeReload);
});
check('external copy deep clones nested components with unique IDs and changes never leak into the original',()=>{
 select(4);const before=snapshot(),original=before.components[4];
 $('.fbmp-component-tools [data-node-op="copy"]').click();
 const after=snapshot(),copy=after.components[5];assert.equal(after.components.length,before.components.length+1);
 function withoutIDs(value){const result=structuredClone(value);(function visit(node){delete node.id;(node.props.tabs||[]).forEach(tab=>(tab.children||[]).forEach(visit));})(result);return result;}
 assert.deepEqual(withoutIDs(copy),withoutIDs(original));assert.notEqual(copy.id,original.id);assert.notEqual(copy.props.tabs[0].children[0].id,original.props.tabs[0].children[0].id);
 const ids=[];(function visit(nodes){nodes.forEach(node=>{ids.push(node.id);(node.props.tabs||[]).forEach(tab=>visit(tab.children||[]));});})(after.components);assert.equal(new Set(ids).size,ids.length);
 $('[data-node="components.5.props.tabs.0.children.0"] > .fbmp-node-body').click();
 $$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
 input('#fbmp-inspector input[data-field="components.5.props.tabs.0.children.0.props.placeholder"]','copy only');
 assert.equal(snapshot().components[4].props.tabs[0].children[0].props.placeholder,original.props.tabs[0].children[0].props.placeholder);
 select(5);$('.fbmp-component-tools [data-node-op="del"]').click();assert.deepEqual(snapshot(),before);
});
check('every component exposes consistent settings operations, dimensions, borders, outer margins and padding',()=>{
 const components=snapshot().components,seen=new Set();
 const fields={gapLeft:15,gapRight:23,gapTop:-7,gapBottom:9,padLeft:5,padRight:11,padTop:3,padBottom:4};
 const css={gapLeft:'margin-left',gapRight:'margin-right',gapTop:'margin-top',gapBottom:'margin-bottom',padLeft:'padding-left',padRight:'padding-right',padTop:'padding-top',padBottom:'padding-bottom'};
 components.forEach((component,index)=>{
  if(seen.has(component.type))return;seen.add(component.type);select(index);
  const styleTab=$$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='style');assert(styleTab,component.type+' style tab');styleTab.click();
  const actions=$$('#fbmp-inspector [data-node-actions] [data-node-op]');
  assert.deepEqual(actions.map(button=>button.dataset.nodeOp),['up','down','copy','del','refresh']);
  assert.equal(actions[0].disabled,index===0);assert.equal(actions[1].disabled,index===components.length-1);
  for(const [key,value] of Object.entries({boxWidth:240,boxHeight:180,borderWidth:2,borderColor:'#abcdef',borderStyle:'dashed'}))input('#fbmp-inspector [data-field="components.'+index+'.props.'+key+'"]',String(value));
  const container=$('[data-node="components.'+index+'"] > .fbmp-node-body');
  assert.equal(container.style.width,'240px');assert.equal(container.style.height,'180px');assert.equal(container.style.borderWidth,'2px');assert.equal(container.style.borderStyle,'dashed');assert.equal(container.style.borderColor,'rgb(171, 205, 239)');
  let preview;
  if(data.componentHTML[component.type]){
   preview=loadProductPreview(index,component.type);
   Object.defineProperty(preview,'clientWidth',{configurable:true,get(){const body=preview.parentElement;return 375-parseFloat(body.style.marginLeft||0)-parseFloat(body.style.marginRight||0)-parseFloat(body.style.paddingLeft||0)-parseFloat(body.style.paddingRight||0);}});
  }
  for(const [field,value] of Object.entries(fields)){
   input('#fbmp-inspector [data-field="components.'+index+'.props.'+field+'"]',String(value));
   const body=$('[data-node="components.'+index+'"] > .fbmp-node-body');
   assert.equal(body.style.getPropertyValue(css[field]),value+'px',component.type+' '+field+' must update immediately');
   if(preview){assert.equal(body.querySelector('iframe'),preview,component.type+' spacing must not reload product data');assert.equal(preview.contentDocument.documentElement.style.getPropertyValue('--fbmp-logical-width'),preview.clientWidth+'px',component.type+' document follows the available content width');}
  }
 });
 assert.deepEqual([...seen].sort(),Object.keys(data.types).sort(),'the sweep must include every registered component');
 console.log('Checked '+seen.size+' component types and '+seen.size*Object.keys(fields).length+' independent spacing controls.');
});
check('every available internal area exposes independent left/right controls and serializes the edited values',()=>{
 const nodes=snapshot().components,seen=new Set();let count=0;
 nodes.forEach((node,index)=>{
  if(seen.has(node.type))return;seen.add(node.type);select(index);regionTab();
  const choices=Array.from($('[data-region-select]').options).map(option=>option.value);
  for(const key of choices){
   const select=$('[data-region-select]');select.value=key;select.dispatchEvent(new w.Event('change',{bubbles:true}));
   input('[data-field="components.'+index+'.props.regions.'+key+'.gapLeft"]','-13');
   input('[data-field="components.'+index+'.props.regions.'+key+'.gapRight"]','19');
   const saved=snapshot().components[index].props.regions[key];assert.equal(saved.gapLeft,-13,node.type+' '+key);assert.equal(saved.gapRight,19,node.type+' '+key);count++;
  }
 });
 assert.equal(count,Object.values(data.regions).reduce((n,regions)=>n+Object.keys(regions).length,0));console.log('Checked '+count+' internal regions and '+count*2+' independent left/right controls.');
});
check('library insertion uses the selected sibling position including nested tab content',()=>{
 const before=snapshot();
 for(const selectedPath of ['components.0','components.4.props.tabs.0.children.0','components.4']){
  $('[data-node="'+selectedPath+'"] > .fbmp-node-body').click();
  const parts=selectedPath.split('.'),index=Number(parts.pop()),parentPath=parts.join('.');
  const getParent=value=>parentPath.split('.').reduce((owner,key)=>owner[key],value);
  const selected=getParent(snapshot())[index];
  $('[data-add="text"]').click();
  const after=snapshot(),siblings=getParent(after);
  assert.equal(siblings[index].type,'text');assert.equal(siblings[index+1].id,selected.id);
  assert.equal($('.fbmp-component-tools').dataset.path,selectedPath);
  $('#fbmp-undo').click();assert.deepEqual(snapshot(),before);
 }
 $('.fbmp-preview-nav').click();$('[data-add="text"]').click();assert.equal(snapshot().components.at(-1).type,'text');
 $('#fbmp-undo').click();assert.deepEqual(snapshot(),before);
 select(1);$('#fbmp-inspector [data-node-actions] [data-node-op="up"]').click();assert.equal(snapshot().components[0].id,before.components[1].id);
 $('#fbmp-undo').click();assert.deepEqual(snapshot(),before);
 select(0);$('#fbmp-inspector [data-node-actions] [data-node-op="copy"]').click();assert.equal(snapshot().components.length,before.components.length+1);
 $('#fbmp-inspector [data-node-actions] [data-node-op="del"]').click();assert.deepEqual(snapshot(),before);
});
check('left action entry and its preview row disappear when the page has no home social component',()=>{
 const home=config.components.findIndex(component=>component.type==='social');select(home);
 $('.fbmp-component-tools [data-node-op="del"]').click();
 assert($('.fbmp-library [data-inspector="actions"]').hidden);assert(!$('.fbmp-actions-region'));
 $('.fbmp-library [data-inspector="actions"]').click();assert.notEqual($('#fbmp-inspector .fbmp-inspector-title').textContent,'左下角操作菜单');
 select(8);assert.equal(new URL($('[data-node="components.8"] iframe').src).searchParams.get('key'),'detail');
 assert.equal($$('#fbmp-inspector [data-config-tab="actions"]').length,0);
});
// Exercise the real shared classifier and its MutationObserver, not only the editor's own classes.
select(0);
$$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='style').click();
input('#fbmp-inspector input[data-type="text"][data-field="components.0.props.bg"]','#203040');
$$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
input('#fbmp-inspector input[data-type="text"][data-field="components.0.props.color"]','#c0d0e0');
regionTab();
const searchRegion=$('#fbmp-inspector [data-region-select]');searchRegion.value='input';searchRegion.dispatchEvent(new w.Event('change',{bubbles:true}));
input('#fbmp-inspector input[data-field="components.0.props.regions.input.size"]','21');
const actualSearch=$('[data-node="components.0"] [data-fb-region="input"]');
// The current search renderer uses a span. A native control probe also verifies the broad shared-input exclusion.
const nativeProbe=w.document.createElement('input');nativeProbe.setAttribute('aria-label','预览原生输入控件隔离检查');nativeProbe.style.cssText='background-color:#112233;color:#ffeecc;font-size:23px;height:30px';actualSearch.appendChild(nativeProbe);
const actionProbe=w.document.createElement('button');actionProbe.innerHTML='<svg aria-hidden="true"><path d="M0 0h1"/></svg>';actualSearch.appendChild(actionProbe);
await new Promise(resolve=>w.setTimeout(resolve,0));
check('shared admin CSS and live classifier preserve editable search preview styles and native controls at all display sizes',()=>{
 const controlStyle=w.getComputedStyle(actualSearch),probeStyle=w.getComputedStyle(nativeProbe),rules=commonStyle.sheet.cssRules;
 assert.equal(controlStyle.backgroundColor,'rgb(32, 48, 64)');assert.equal(controlStyle.color,'rgb(192, 208, 224)');assert.equal(controlStyle.fontSize,'21px');
 assert.equal(probeStyle.backgroundColor,'rgb(17, 34, 51)');assert.equal(probeStyle.color,'rgb(255, 238, 204)');assert.equal(probeStyle.fontSize,'23px');assert.equal(probeStyle.height,'30px');
 for(const rule of rules){if(!rule.selectorText || !nativeProbe.matches(rule.selectorText))continue;for(const property of ['background','background-color','color','font-size','height','min-height'])assert.notEqual(rule.style.getPropertyPriority(property),'important','shared appearance selector leaked into preview: '+rule.selectorText);}
 assert(!actionProbe.classList.contains('fb-admin-button'),'MutationObserver must not decorate preview controls');assert(actionProbe.querySelector('svg'),'shared classifier must not remove preview content icons');
 assert($$('#fbmp-frame [data-tab-head]').every(button=>!button.classList.contains('fb-admin-tab') && !button.classList.contains('fb-admin-button')),'editor adminControls must also leave actual mobile preview tabs undecorated');
 const before=snapshot(),styleText=actualSearch.closest('[data-node]').querySelector('style[data-fbmp-region-styles]').textContent;
 for(const percent of [25,50,75,100]){
  $('[data-preview-scale="'+percent+'"]').click();
  assert.equal($('[data-node="components.0"] [data-fb-region="input"]'),actualSearch);
  assert.equal(w.getComputedStyle(actualSearch).fontSize,'21px');assert.equal(w.getComputedStyle(nativeProbe).fontSize,'23px');
  assert.equal(actualSearch.closest('[data-node]').querySelector('style[data-fbmp-region-styles]').textContent,styleText);
  assert.deepEqual(snapshot(),before);
 }
});
nativeProbe.remove();actionProbe.remove();
select(4);$$('#fbmp-inspector [data-config-tab]').find(el=>el.dataset.configTab==='content').click();
input('#fbmp-inspector input[data-field="components.4.props.size"]','17');
input('#fbmp-inspector input[data-type="text"][data-field="components.4.props.activeColor"]','#dddddd');
input('#fbmp-inspector input[data-type="text"][data-field="components.4.props.color"]','#666666');
await new Promise(resolve=>w.setTimeout(resolve,0));
check('mobile preview tabs keep the formal H5 button reset and use live component colors and size independently of admin tabs',()=>{
 const buttons=$$('[data-node="components.4"] > .fbmp-node-body > .fbmp-tabs [data-tab-head]');assert.equal(buttons.length,2);
 for(const button of buttons){const style=w.getComputedStyle(button);assert(!button.classList.contains('fb-admin-button'));assert(!button.classList.contains('fb-admin-tab'));assert.equal(style.backgroundColor,'rgba(0, 0, 0, 0)');assert.equal(style.borderTopWidth,'0px');assert.equal(style.padding,'0px');assert.equal(style.fontWeight,'500');assert.equal(style.fontSize,'17px');}
 assert.equal(w.getComputedStyle(buttons[0]).color,'rgb(221, 221, 221)');assert.equal(w.getComputedStyle(buttons[1]).color,'rgb(102, 102, 102)');
 assert($$('#fbmp-inspector [data-config-tab]').every(button=>button.classList.contains('fb-admin-tab')),'configuration tabs still use the shared admin style');
 buttons[1].click();
 const active=$('[data-node="components.4"] .fbmp-tabs .is-active');assert.equal(active.dataset.tabIndex,'1');assert.equal(w.getComputedStyle(active).color,'rgb(221, 221, 221)');
 const toggle=$('#fbmp-inspector input[data-field="components.4.props.indicator"]');toggle.checked=false;toggle.dispatchEvent(new w.Event('input',{bubbles:true}));
 assert($('[data-node="components.4"] .fbmp-tabs').classList.contains('fbmp-no-indicator'));
});

check('image fit and auto height, zero spacer height and exact child widths are reflected in the editor',()=>{
 let index=snapshot().components.findIndex(c=>c.type==='image');select(index);
 const content=$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content');content.click();
 input('[data-field="components.'+index+'.props.src"]','/fixture.png');input('[data-field="components.'+index+'.props.height"]','0');input('[data-field="components.'+index+'.props.fit"]','contain');
 const image=$('[data-node="components.'+index+'"] img[data-fb-region="media"]');assert(image);assert.equal(image.style.height,'auto');assert.equal(image.style.objectFit,'contain');
 index=snapshot().components.findIndex(c=>c.type==='spacer');select(index);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();
 input('[data-field="components.'+index+'.props.height"]','0');assert.equal($('[data-node="components.'+index+'"] [data-fb-region="space"]').style.height,'0px');
 index=snapshot().components.findIndex(c=>c.type==='product_info');loadProductPreview(index,'product_info');select(index);regionTab();const region=$('[data-region-select]');region.value='fieldLabel';region.dispatchEvent(new w.Event('change',{bubbles:true}));
 input('[data-field="components.'+index+'.props.regions.fieldLabel.width"]','24');
 const preview=$('[data-node="components.'+index+'"] iframe');assert.match(preview.contentDocument.querySelector('style[data-fbmp-region-styles]').textContent,/width:24px!important;min-width:0!important/);
});
check('removed reading preferences have no page, palette entry or member editor controls',()=>{
 assert.equal(data.keys.preference,undefined);assert.equal(data.types.reading_preference,undefined);assert.equal($('[data-add="reading_preference"]'),null);
 const index=snapshot().components.findIndex(c=>c.type==='member_center');select(index);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='menu').click();
 assert.equal($('#fbmp-inspector [data-field$=".props.preferenceEnabled"]'),null);assert.equal($('#fbmp-inspector [data-field$=".props.preferenceLabel"]'),null);
});
check('member preview updates every visibility switch and the exact menu row without reloading data',()=>{
 const index=snapshot().components.findIndex(c=>c.type==='member_center');select(index);
 const preview=$('[data-node="components.'+index+'"] iframe'),doc=preview.contentDocument;
 doc.open();doc.write('<html><head></head><body><main class="fb-mc-main"><section class="fb-member" data-guest="1"><span class="fb-member-note">old</span></section><div class="fb-stat-row"></div><div class="fb-resume"></div><button class="fb-logout"></button><nav>'+['history','share','about','privacy','agreement'].map(key=>'<a data-fb-member-menu="'+key+'"><span class="fb-menu-label">'+key+'</span></a>').join('')+'</nav></main></body></html>');doc.close();preview.dispatchEvent(new w.Event('load'));
 $$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();input('[data-field="components.'+index+'.props.note"]','Visitor note');assert.equal(doc.querySelector('.fb-member-note').textContent,'Visitor note');
 for(const [key,selector] of [['showStats','.fb-stat-row'],['showResume','.fb-resume'],['showLogout','.fb-logout']]){
  const field=$('[data-field="components.'+index+'.props.'+key+'"]');field.checked=false;field.dispatchEvent(new w.Event('input',{bubbles:true}));assert.equal(w.getComputedStyle(doc.querySelector(selector)).display,'none',key);field.checked=true;field.dispatchEvent(new w.Event('input',{bubbles:true}));assert.notEqual(w.getComputedStyle(doc.querySelector(selector)).display,'none',key);
 }
 $$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='menu').click();
 const privacy=$('[data-field="components.'+index+'.props.privacyEnabled"]');privacy.checked=false;privacy.dispatchEvent(new w.Event('input',{bubbles:true}));input('[data-field="components.'+index+'.props.agreementLabel"]','Custom agreement');
 assert.equal(w.getComputedStyle(doc.querySelector('[data-fb-member-menu="privacy"]')).display,'none');assert.equal(doc.querySelector('[data-fb-member-menu="agreement"] .fb-menu-label').textContent,'Custom agreement');assert.equal($('[data-node="components.'+index+'"] iframe'),preview);
});
const originalInputClick=w.HTMLInputElement.prototype.click,alerts=[];w.alert=message=>alerts.push(message);
let importText;
w.HTMLInputElement.prototype.click=function(){if(this.type==='file'){Object.defineProperty(this,'files',{configurable:true,value:[{size:100,text:async()=>importText}]});this.dispatchEvent(new w.Event('change'));}else originalInputClick.call(this);};
const beforeBadImport=snapshot();const badImport=structuredClone(beforeBadImport);delete badImport.tabbar.items;importText=JSON.stringify(badImport);$('#fbmp-import').click();await new Promise(resolve=>w.setTimeout(resolve,0));
check('invalid imported configuration cannot replace or corrupt the current draft',()=>{assert.match(alerts.at(-1),/导入失败/);assert.deepEqual(snapshot(),beforeBadImport);});
for(const malformed of [c=>c.layout=[],c=>c.nav.logo='invalid',c=>c.components[0].props=[],c=>c.tabbar.items=[null]]){const next=structuredClone(beforeBadImport);malformed(next);const prior=alerts.length;importText=JSON.stringify(next);$('#fbmp-import').click();await new Promise(resolve=>w.setTimeout(resolve,0));assert.equal(alerts.length,prior+1);assert.deepEqual(snapshot(),beforeBadImport);}
check('malformed page objects, component props and navigation rows leave the original draft intact',()=>assert.deepEqual(snapshot(),beforeBadImport));
const validImport=structuredClone(beforeBadImport);let business=0;validImport.components=validImport.components.filter(c=>!['social','product_detail','album_list','favorite_list','member_center','history_list','reading_preference'].includes(c.type)||++business===1);validImport.layout.bg='#abc123';const alertsBefore=alerts.length;importText=JSON.stringify(validImport);$('#fbmp-import').click();await new Promise(resolve=>w.setTimeout(resolve,0));
check('valid import preserves all components, nested settings and zero or negative values',()=>{assert.equal(alerts.length,alertsBefore);assert.deepEqual(snapshot(),validImport);});
check('Markdown toolbar formats the selected text on valid line boundaries and keeps the editing caret',()=>{
 const index=snapshot().components.findIndex(c=>c.type==='markdown');select(index);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();
 for(const kind of ['heading','bold','list','link']){
  const area=$('textarea.fbmp-md-editor');area.value='alpha bravo tail';area.dispatchEvent(new w.Event('input',{bubbles:true}));area.setSelectionRange(6,11);$('[data-md="'+kind+'"]').click();const edited=$('textarea.fbmp-md-editor');
  if(kind==='heading')assert.equal(edited.value,'alpha \n# bravo\n tail');
  if(kind==='list')assert.equal(edited.value,'alpha \n- bravo\n tail');
  if(kind==='bold')assert.equal(edited.value,'alpha **bravo** tail');
  if(kind==='link')assert.equal(edited.value,'alpha [bravo](https://example.com) tail');
  assert(edited.selectionStart>6);assert.equal(edited.selectionStart,edited.selectionEnd);assert.equal(w.document.activeElement,edited);
 }
});
check('view title keeps fractional letter spacing and its outer box participates in configured margins',()=>{
 const index=snapshot().components.findIndex(c=>c.type==='view_title');select(index);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();input('[data-field="components.'+index+'.props.lines.0.letterSpacing"]','1.5');
 const body=$('[data-node="components.'+index+'"] > .fbmp-node-body');assert.equal(w.getComputedStyle(body).position,'absolute');assert.equal(body.querySelector('.fbmp-view-line').style.letterSpacing,'1.5px');
});

// A failing upload leaves all fields untouched and only locks the clicked image button.
const heroIndex=snapshot().components.findIndex(c=>c.type==='hero');select(heroIndex);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();
let completeSession;w.fetch=()=>new Promise(resolve=>{completeSession=resolve;});
const buttons=$$('#fbmp-inspector [data-upload][data-kind="image"]'),target=buttons[1],other=buttons[0],priorLabel=other.textContent,uploadBefore=snapshot();assert(target&&other);
target.click();
check('upload progress belongs to the clicked nested image control, not the first upload button',()=>{assert.equal(other.textContent,priorLabel);assert(!other.disabled);assert.equal(target.textContent,'上传中');assert(target.disabled);});
completeSession({json:async()=>({ok:0,message:'Session fixture expired'})});await new Promise(resolve=>w.setTimeout(resolve,0));
check('upload failure keeps the current configuration and restores the button for another attempt',()=>{assert.equal(target.textContent,priorLabel);assert(!target.disabled);assert.deepEqual(snapshot(),uploadBefore);assert.match(alerts.at(-1),/Session fixture expired/);});
w.HTMLInputElement.prototype.click=originalInputClick;

check('typing content preserves unrelated preview documents, media, nested siblings and the editing caret',()=>{
 const draft=snapshot(),textIndex=draft.components.findIndex(c=>c.type==='text'),tabIndex=draft.components.findIndex(c=>c.type==='tab');
 select(textIndex);$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();
 const field=$('#fbmp-inspector [data-field="components.'+textIndex+'.props.text"]');assert(field);field.focus();
 const reader=$('iframe'),readerDoc=reader.contentDocument,unrelated=$('[data-node="components.0"]'),image=$('.fbmp-pv-media'),canvas=$('.fbmp-canvas');
 canvas.scrollTop=73;const observer=new w.MutationObserver(()=>{});observer.observe($('#fbmp-frame'),{childList:true,subtree:true});
 for(const text of ['一','一段','一段连续输入']){field.value=text;field.dispatchEvent(new w.Event('input',{bubbles:true}));assert.equal(w.document.activeElement,field);assert.equal($('[data-node="components.'+textIndex+'"] [data-fb-region="content"]').textContent,text);}
 assert.equal($('iframe'),reader);assert.equal(reader.contentDocument,readerDoc);assert.equal($('[data-node="components.0"]'),unrelated);assert.equal($('.fbmp-pv-media'),image);assert.equal(canvas.scrollTop,73);
 observer.takeRecords();field.dispatchEvent(new w.Event('change',{bubbles:true}));assert.equal(observer.takeRecords().length,0,'unchanged input/change must not redraw');observer.disconnect();
 $$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='style').click();
 const linkPath='components.'+textIndex+'.props.link',kind=$('[data-link-kind="'+linkPath+'"]');assert(kind);kind.value='url';kind.dispatchEvent(new w.Event('change',{bubbles:true}));
 input('[data-link-url="'+linkPath+'"]','https://example.com/product');
 const linkField=$('[data-link-url="'+linkPath+'"]'),linkBody=$('[data-node="components.'+textIndex+'"]>.fbmp-node-body').firstChild;
 linkField.dispatchEvent(new w.Event('change',{bubbles:true}));
 assert.equal($('[data-node="components.'+textIndex+'"]>.fbmp-node-body').firstChild,linkBody,'repeated link input/change must not redraw');assert.equal($('iframe'),reader);
 if(tabIndex>=0){
  select(tabIndex);$('[data-tab-head="components.'+tabIndex+'"][data-tab-index="0"]').click();$$('#fbmp-inspector [data-config-tab]').find(t=>t.dataset.configTab==='content').click();
  const tabReader=$('iframe');
  const nested=$('[data-node="components.'+tabIndex+'.props.tabs.0.children.0"]');assert(nested);
  input('[data-field="components.'+tabIndex+'.props.tabs.0.title"]','局部标签');
  assert.equal($('[data-node="components.'+tabIndex+'.props.tabs.0.children.0"]'),nested,'editing the tab title preserves loaded children');
  assert.equal($('[data-tab-head="components.'+tabIndex+'"]').textContent,'局部标签');
  assert.equal($('iframe'),tabReader);
 }
});

// Footer editing must preserve every already loaded component document and media node.
const footerReader=$('iframe'),footerDocument=footerReader&&footerReader.contentDocument,footerSibling=$('[data-node="components.0"]'),footerMedia=$('.fbmp-pv-media');
$('.fbmp-preview-tabbar').click();
input('#fbmp-inspector [data-field="tabbar.color"]','#123456');
check('footer colors update without rebuilding unrelated component documents or media',()=>{
 assert.equal($('iframe'),footerReader);if(footerReader)assert.equal(footerReader.contentDocument,footerDocument);
 assert.equal($('[data-node="components.0"]'),footerSibling);assert.equal($('.fbmp-pv-media'),footerMedia);
});
const customSvg='<svg viewBox="0 0 24 24"><path fill="currentColor" d="M2 2h20v20H2z"/></svg>';
w.fetch=async url=>({json:async()=>String(url).includes('op=session')?{ok:1,formhash:'fixture'}:{ok:1,svg:customSvg}});
const iconPath='tabbar.items.0.iconSvg',iconSource=$('[data-icon-source="'+iconPath+'"]');assert(iconSource);iconSource.value=customSvg;
$('[data-icon-apply="'+iconPath+'"]').click();await new Promise(resolve=>w.setTimeout(resolve,0));
check('normalized custom footer SVG updates only the footer and persists its source',()=>{
 assert.equal(snapshot().tabbar.items[0].iconSvg,customSvg);assert($('.fbmp-preview-tabbar img'));
 assert.equal($('iframe'),footerReader);if(footerReader)assert.equal(footerReader.contentDocument,footerDocument);
 assert.equal($('[data-node="components.0"]'),footerSibling);assert.equal($('.fbmp-pv-media'),footerMedia);
});
$('[data-icon-clear="'+iconPath+'"]').click();
check('restoring a built-in footer icon preserves the loaded design content',()=>{
 assert.equal(snapshot().tabbar.items[0].iconSvg,'');assert($('.fbmp-preview-tabbar svg'));
 assert.equal($('iframe'),footerReader);assert.equal($('[data-node="components.0"]'),footerSibling);assert.equal($('.fbmp-pv-media'),footerMedia);
});

console.log('DOM checks finished without browser access, HTTP requests, or saved configuration.');
w.close();
})().catch(error=>{console.error(error);w.close();process.exitCode=1;});
