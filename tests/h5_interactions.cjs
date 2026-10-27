'use strict';
// Real H5 login markup and production event handlers; authentication requests are mocked.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),{spawnSync}=require('node:child_process');
const {JSDOM}=require(process.env.JSDOM_PATH||'jsdom');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);args.push('flipbook-php','php');
const fixture=spawnSync('docker',args,{input:fs.readFileSync(path.join(root,'tests/micro_config.php')),encoding:'utf8'});
assert.equal(fixture.status,0,fixture.stderr);assert.equal(fixture.stderr,'');const data=JSON.parse(fixture.stdout);
const source=fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/js/flipbook.js'),'utf8');
function page(html){const dom=new JSDOM('<div id="fb-app" data-formhash="fixture-hash" data-uid="0">'+html+'</div>',{url:'http://127.0.0.1:8088/plugin.php?id=flipbook:micro&key=mine&login=1',runScripts:'outside-only'});dom.window.eval(source);return dom;}
const tick=async()=>{for(let i=0;i<4;i++)await new Promise(resolve=>setImmediate(resolve));};
(async()=>{
 const dom=page(data.loginHtml),w=dom.window,d=w.document,form=d.querySelector('[data-fb-login-form]'),requests=[];let response={ok:0,code:'credentials',message:'测试登录错误'};
 w.fetch=async(url,options)=>{requests.push({url,body:Object.fromEntries(options.body.entries()),credentials:options.credentials});return {json:async()=>response};};
 assert(!d.querySelector('[data-fb-login-panel]'));assert(!d.querySelector('.fb-member'));assert(!d.querySelector('.fb-mc-tabbar'));assert.equal(d.querySelector('[role=tab][aria-selected=true]').textContent,'账号');
 assert(!d.body.textContent.includes('微信登录尚未配置'));assert(!d.body.textContent.includes('使用论坛账号同步收藏与阅读记录'));
 form.elements.username.value='fixture-user';form.elements.password.value='fixture-password';form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await tick();
 assert.equal(requests.length,1);assert.equal(requests[0].url,'plugin.php?id=flipbook:auth&op=forum');assert.equal(requests[0].body.formhash,'fixture-hash');assert.equal(requests[0].credentials,'same-origin');assert.equal(d.querySelector('[data-fb-login-message]').textContent,'测试登录错误');assert(!form.querySelector('[type=submit]').disabled);
 response={ok:0,code:'native_verification',message:'需要论坛验证'};form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await tick();assert(!d.querySelector('[data-fb-native-login]').hidden);assert(form.dataset.return.includes('key=detail&productid=1'));
 console.log('PASS independent H5 login uses the existing forum session, shows recoverable failures, and retains its return destination');dom.window.close();
 const multiple=page([1,2].map(i=>'<section class="fb-mc-business"><button data-fb-series-open>系列 '+i+'</button><div data-fb-series-mask hidden><button data-fb-series-close>取消</button></div><div class="fb-web-reader"><div data-fb-web-reader-track></div><button data-fb-web-pick="1">缩略图</button></div><section data-fb-member-header data-guest="1" data-login-url="plugin.php?id=flipbook:micro&key=mine&login=1"></section></section>').join(''));
 const m=multiple.window.document,sections=m.querySelectorAll('.fb-mc-business'),scrolls=[];
 sections.forEach((section,index)=>{const track=section.querySelector('[data-fb-web-reader-track]');Object.defineProperty(track,'clientWidth',{value:100});track.scrollTo=value=>scrolls.push([index,value.left]);});
 sections[1].querySelector('[data-fb-series-open]').click();assert(sections[0].querySelector('[data-fb-series-mask]').hidden);assert(!sections[1].querySelector('[data-fb-series-mask]').hidden);sections[1].querySelector('[data-fb-series-close]').click();assert(sections[1].querySelector('[data-fb-series-mask]').hidden);
 sections[1].querySelector('[data-fb-web-pick]').click();assert.deepEqual(scrolls,[[1,100]]);assert.equal(m.querySelectorAll('[data-fb-member-header][role=link]').length,2);assert(!m.querySelector('[data-fb-login-panel]'));
 console.log('PASS repeated H5 business components keep their filter, reader and login interactions independent');multiple.window.close();
})().catch(error=>{console.error(error);process.exitCode=1;});
