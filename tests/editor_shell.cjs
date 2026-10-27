// DOM integration for native admin-tab opening; no browser or network is used.
const fs=require('node:fs'),assert=require('node:assert/strict');
const {JSDOM}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
const source=fs.readFileSync('app/source/plugin/flipbook/static/admin/editor-shell.js','utf8');
const url='http://127.0.0.1:8088/admin.php?action=plugins&operation=config&do=2&identifier=flipbook&pmod=micropage';
const dom=new JSDOM('<div class="ifmcontainer"><div id="tabs"></div><iframe id="main" class="mainframe" src="'+url+'"></iframe></div>',{url,runScripts:'outside-only'}),host=dom.window,child=host.document.getElementById('main').contentWindow;
child.document.open();child.document.write('<!doctype html><html><head></head><body></body></html>');child.document.close();
Object.defineProperty(host.document.body,'clientWidth',{configurable:true,value:1400});Object.defineProperty(host.document.documentElement,'clientWidth',{value:1400});
let opened=[],active=0,reloads=0;
host._framemenu={tabNew(anchor){opened.push({id:anchor.id,title:anchor.textContent,url:anchor.href});const frame=host.document.createElement('iframe');frame.id=anchor.id+'_frame';frame.className='mainframe';frame.src=anchor.href;host.document.querySelector('.ifmcontainer').appendChild(frame);frame.addEventListener('load',()=>reloads++);const tab=host.document.createElement('a');tab.id=frame.id+'_tab';tab.textContent=anchor.textContent;tab.appendChild(host.document.createElement('span'));tab.addEventListener('mousedown',()=>active++);host.document.getElementById('tabs').appendChild(tab);}};
function check(name,fn){fn();console.log('PASS '+name);}
function link(key,title){const a=child.document.createElement('a');a.href=url+'&op=edit&key='+key;a.dataset.fbAdminOpen='flipbook_micro_editor_'+key;a.dataset.fbAdminTitle='可视化编辑 · '+title;a.target='_blank';a.textContent='进入可视化编辑';child.document.body.appendChild(a);return a;}
const home=link('home','首页'),detail=link('detail','详情页');child.eval(source);
function click(a,options={}){const event=new child.MouseEvent('click',Object.assign({button:0,bubbles:true,cancelable:true},options));a.dispatchEvent(event);return event;}
check('the launcher opens a separate named admin tab for the selected page',()=>{assert(click(home).defaultPrevented);assert.equal(opened.length,1);assert.equal(opened[0].id,'flipbook_micro_editor_home');assert.equal(opened[0].title,'可视化编辑 · 首页');assert.equal(new URL(opened[0].url).searchParams.get('key'),'home');});
const existing=host.document.getElementById('flipbook_micro_editor_home_frame');existing.dataset.unsavedDraft='kept';const before=existing.src;
check('opening the same page activates the existing editor without reloading its unsaved draft',()=>{assert(click(home).defaultPrevented);assert.equal(opened.length,1);assert.equal(active,1);assert.equal(existing.src,before);assert.equal(existing.dataset.unsavedDraft,'kept');assert.equal(reloads,0);});
check('different page keys open distinct editor tabs',()=>{click(detail);assert.equal(opened.length,2);assert.equal(opened[1].id,'flipbook_micro_editor_detail');assert.equal(opened[1].title,'可视化编辑 · 详情页');});
check('modified clicks and compact layouts retain the normal independent browser link',()=>{assert(!click(home,{ctrlKey:true}).defaultPrevented);Object.defineProperty(host.document.body,'clientWidth',{configurable:true,value:400});assert(!click(detail).defaultPrevented);assert.equal(opened.length,2);});
const ownTab=host.document.createElement('a');ownTab.id='main_tab';ownTab.appendChild(host.document.createTextNode('工装画册'));const close=host.document.createElement('span');let closed=false;close.addEventListener('click',()=>closed=true);ownTab.appendChild(close);host.document.getElementById('tabs').appendChild(ownTab);
child.document.body.setAttribute('data-fb-editor','home');child.document.title='可视化编辑 · 首页';child.document.dispatchEvent(new child.Event('DOMContentLoaded'));
check('the owning native tab receives the page title while preserving its close control',()=>{assert.equal(ownTab.textContent,'可视化编辑 · 首页');assert.equal(ownTab.querySelector('span'),close);close.click();assert(closed);assert.equal(host.document.getElementById('flipbook_micro_editor_detail_frame_tab').textContent,'可视化编辑 · 详情页');});
const fallback=new JSDOM('<a href="'+url+'&op=edit&key=home" target="_blank" data-fb-admin-open="flipbook_micro_editor_home">进入可视化编辑</a>',{url,runScripts:'outside-only'});fallback.window.eval(source);const event=new fallback.window.MouseEvent('click',{button:0,bubbles:true,cancelable:true});fallback.window.document.querySelector('a').dispatchEvent(event);
check('the independent page remains accessible when no native admin shell exists',()=>assert(!event.defaultPrevented));
fallback.window.close();dom.window.close();
