// Real editor events -> JSON submission -> PHP sanitization -> native/H5 styles.
'use strict';
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),{spawnSync}=require('node:child_process');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
const {JSDOM}=require(process.env.JSDOM_PATH||'jsdom');
const fixture=fs.readFileSync(path.join(root,'tests/micro_config.php'),'utf8');
function php(prefix=''){
 const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);args.push('flipbook-php','php');
 const run=spawnSync('docker',args,{input:fixture.replace('if(isset($roundTrip)){',prefix+'\nif(isset($roundTrip)){'),encoding:'utf8'});
 assert.equal(run.status,0,run.stderr);assert.equal(run.stderr,'');return JSON.parse(run.stdout);
}
const data=php(),micro=require(path.join(root,'miniprogram/utils/micro'));
data.checks.forEach(x=>console.log('PASS '+x));
const cfg=structuredClone(data.config);
cfg.components.forEach(node=>{node.props.regions=[];});
cfg.components.push({id:'nested',type:'tab',props:{regions:[],tabs:[{title:'nested',children:[{id:'child',type:'text',props:{text:'child',regions:[]}}]}]}});
const html='<div class="fbmp"><aside class="fbmp-library"></aside><section class="fbmp-canvas-stage"><div class="fbmp-canvas"><div id="fbmp-viewport"><div id="fbmp-frame"></div></div></div></section><aside class="fbmp-config"><div id="fbmp-inspector"></div></aside></div><script id="fbmp-data" type="application/json">'+JSON.stringify(cfg)+'</script><input id="fbmp-config"><form id="fbmp-form"></form>'+['save','undo','redo','export','import'].map(k=>'<button id="fbmp-'+k+'"></button>').join('');
function editor(configHTML){
 const dom=new JSDOM(configHTML,{url:'http://127.0.0.1:8088/admin.php?key=mine',runScripts:'outside-only'}),w=dom.window;
 dom.uiErrors=[];w.addEventListener('error',event=>dom.uiErrors.push(String(event.error||event.message)));
 Object.assign(w,{FBMP_DEFAULTS:data.componentDefaults,FBMP_TYPES:data.types,FBMP_KEYS:data.keys,FBMP_REGIONS:data.regions,FBMP_REGION_SOURCES:data.sources,FBMP_REGION_SELECTORS:data.selectors,FBMP_TEXT_PRESETS:data.defaults,FBMP_ICONS:{},ResizeObserver:class{observe(){}disconnect(){}}});
 w.HTMLFormElement.prototype.submit=function(){};
 w.eval(fs.readFileSync(process.env.FLIPBOOK_EDITOR_UNDER_TEST||path.join(root,'app/source/plugin/flipbook/static/admin/micropage.js'),'utf8'));
 return dom;
}
const dom=editor(html),w=dom.window,$=s=>w.document.querySelector(s);
function saved(){ $('#fbmp-save').dispatchEvent(new w.Event('click'));return JSON.parse($('#fbmp-config').value); }
function editRegion(owner,key,field,value){
 const target=$('[data-node="'+owner+'"] > .fbmp-node-body');assert(target,owner);target.click();
 const tab=$('#fbmp-inspector [data-config-tab="regions"]');assert(tab);tab.click();
 const regions=$('#fbmp-inspector [data-region-select]');regions.value=key;regions.dispatchEvent(new w.Event('change',{bubbles:true}));
 const control=$('[data-field="'+owner+'.props.regions.'+key+'.'+field+'"]');assert(control,owner+':'+key+'.'+field);control.value=String(value);control.dispatchEvent(new w.Event('input',{bubbles:true}));
}
for(let i=0;i<cfg.components.length-1;i++){
 const node=cfg.components[i],key=node.type==='member_center'?'name':Object.keys(data.regions[node.type])[0],owner='components.'+i;
 editRegion(owner,key,'size',17);assert.equal(saved().components[i].props.regions[key]?.size,17,node.type+' saved region must contain the actual edit');
 for(const [field,value]of Object.entries({color:'#A12345',align:'left',gapTop:0,padLeft:13,display:'flex',direction:'row-reverse',itemGap:9,borderBottomWidth:0}))editRegion(owner,key,field,value);
 const result=saved().components[i].props.regions;
 assert(!Array.isArray(result),node.type+' serialized region map');
 assert.equal(result[key].size,17);assert.equal(result[key].color.toUpperCase(),'#A12345');assert.equal(result[key].align,'left');assert.equal(result[key].gapTop,0);assert.equal(result[key].direction,'row-reverse');
 console.log('PASS '+node.type+' edits an empty-array region and saves actual configuration');
}
const nested='components.'+(cfg.components.length-1)+'.props.tabs.0.children.0';
editRegion(nested,'content','size',23);
const serialized=saved();assert.equal(serialized.components.at(-1).props.tabs[0].children[0].props.regions.content.size,23);assert(Array.isArray(serialized.components.at(-1).props.tabs));
console.log('PASS nested component editing keeps lists as arrays and style maps as objects');
const round=php('$roundTrip=json_decode(base64_decode('+JSON.stringify(Buffer.from(JSON.stringify(serialized)).toString('base64'))+'),true);');
for(let i=0;i<round.components.length-1;i++){
 const node=round.components[i],key=node.type==='member_center'?'name':Object.keys(data.regions[node.type])[0],prepared=micro.prepareNode(node,0,{});
 const style=prepared.regionStyles[key];assert(style.includes('font-size:34rpx'));assert(style.toLowerCase().includes('color:#a12345'));assert(style.includes('text-align:left'));assert(style.includes('flex-direction:row-reverse'));assert(style.includes('gap:18rpx'));assert(style.includes('margin-top:0rpx'));
 if(node.type==='member_center')assert.equal(prepared.query._memberOptions.regions.name.size,17);
 console.log('PASS '+node.type+' preserves saved settings through PHP reload and native preparation');
}
const reopened=editor(html.replace(/<script id="fbmp-data"[^>]*>[^<]*<\/script>/,'<script id="fbmp-data" type="application/json">'+JSON.stringify(round)+'</script>'));
reopened.window.document.querySelector('#fbmp-save').dispatchEvent(new reopened.window.Event('click'));
const afterOpen=JSON.parse(reopened.window.document.querySelector('#fbmp-config').value);
assert.deepEqual(afterOpen,round);console.log('PASS reopening the editor does not mutate stored layout, colors, sizes or alignment');
const gapPage=structuredClone(data.config);gapPage.layout.topGap=31;gapPage.layout.bottomGap=0;gapPage.nav.enabled=0;gapPage.nav.back={show:1,link:'page:catalog',style:{bg:'#234567',color:'#FEDCBA',width:42,radius:9}};
const gapRound=php('$roundTrip=json_decode(base64_decode('+JSON.stringify(Buffer.from(JSON.stringify(gapPage)).toString('base64'))+'),true);');
const gapView=micro.prepare(gapRound,{key:'mine'});assert.equal(gapView.topGap,31);assert.equal(gapView.contentBottomGap,'0rpx');assert(gapView.nav.back.show&&gapView.nav.back.style.includes('background:#234567')&&gapView.nav.back.style.includes('width:84rpx'));assert.equal(gapView.nav.back.path,'/pages/micro/index?key=catalog');
console.log('PASS page spacing and back appearance survive serialization and native preparation with hidden navigation');
for(const type of ['social','product_detail','album_list','favorite_list','history_list','member_center']){
 const regions=micro.effectiveRegions(type,{regions:{button:{bg:'#246813',color:'#FAEBCD',radius:7}}},data.defaults);assert.equal(regions.button.bg,'#246813');assert.equal(regions.button.color,'#FAEBCD');assert.equal(regions.button.radius,7);assert.equal(regions.button.minHeight,data.defaults.controls.button.minHeight);
}
console.log('PASS every business screen consumes one shared action-button configuration');
// Exercise the actual business iframe load handler, not just serialized editor state.
const memberIndex=cfg.components.findIndex(node=>node.type==='member_center'),memberFrame=$('[data-node="components.'+memberIndex+'"] iframe');
const memberDocument=memberFrame.contentDocument;memberDocument.open();memberDocument.write(data.minePreview);memberDocument.close();memberFrame.dispatchEvent(new w.Event('load'));
assert.deepEqual(dom.uiErrors,[]);
const identity=memberDocument.querySelector('.fb-member-name');assert(identity.dataset.fbmpRegions.includes('name'));
identity.dispatchEvent(new memberFrame.contentWindow.MouseEvent('click',{bubbles:true,cancelable:true}));
assert.equal($('#fbmp-inspector [data-region-select]').value,'name');
const identitySize=$('[data-field="components.'+memberIndex+'.props.regions.name.size"]');identitySize.value='18';identitySize.dispatchEvent(new w.Event('input',{bubbles:true}));
// jsdom does not resolve the production rpx variables or the iframe's full CSS cascade.
// The real-browser check covers computed font/color; this regression covers load/click/input binding.
assert.equal(memberDocument.documentElement.id,'fbmp-preview-document');
const memberStyle=memberDocument.querySelector('style[data-fbmp-region-owner="components.'+memberIndex+'"]');
assert(memberStyle.textContent.includes('#fbmp-preview-document ')&&memberStyle.textContent.includes('font-size:18px!important'));
assert.equal(saved().components[memberIndex].props.regions.name.size,18);
console.log('PASS mine iframe loads, binds its content regions, and applies a real settings edit without a script error');
assert(!fs.readFileSync(path.join(root,'miniprogram/utils/micro.js'),'utf8').includes('const TEXT_PRESETS='));
assert(!fs.existsSync(path.join(root,'miniprogram/utils/text-presets.json')));assert.deepEqual(round.styleDefaults,data.defaults);
console.log('PASS editor and native share one API style configuration with no duplicate preset file');
assert.deepEqual(dom.uiErrors,[]);assert.deepEqual(reopened.uiErrors,[]);reopened.window.close();dom.window.close();
