'use strict';
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),{execFileSync}=require('node:child_process');
const {JSDOM}=require(process.env.JSDOM_PATH||'jsdom');const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);args.push('flipbook-php','php');
const fixture=JSON.parse(execFileSync('docker',args,{input:fs.readFileSync(path.join(root,'tests/wechat_presentation.php')),encoding:'utf8'}));
const check=(value,message)=>{assert.ok(value,message);console.log('PASS '+message);};fixture.checks.forEach(message=>console.log('PASS '+message));
const source=file=>fs.readFileSync(path.join(root,file),'utf8'),tick=()=>new Promise(r=>setTimeout(r,0));
(async()=>{
 const admin=new JSDOM('<body data-fb-admin>'+fixture.admin+'</body>',{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only'}),w=admin.window;
 w.eval(source('app/source/plugin/flipbook/static/admin/wechat.js'));
 const tabs=[...w.document.querySelectorAll('[data-fb-wechat-tab]')],panels=[...w.document.querySelectorAll('[data-fb-wechat-panel]')];
 check(tabs.map(n=>n.textContent).join('|')==='小程序信息|小程序开发信息|消息推送|订阅消息|服务器配置|版本推送','all six WeChat sections are separate tabs in the requested order');
 for(const tab of tabs){tab.click();check(panels.filter(p=>!p.hidden).length===1&&w.document.getElementById(tab.getAttribute('aria-controls')).hidden===false,'tab opens only its matching panel: '+tab.textContent);}
 const login=w.document.querySelector('[data-fb-wechat-panel=login]'),develop=w.document.querySelector('[data-fb-wechat-panel=develop]');check(login.querySelector('[name=wechat_enabled]')&&!login.querySelector('[name=wechat_appid]')&&develop.querySelector('[name=wechat_appid]'),'enable login belongs to info; credentials belong to development');
 let versionReads=0,savedConfiguration,subscriptionSaved;
 w.fetch=async(url,options={})=>{const action=new URL(url).searchParams.get('fb_wechat_action');let data={ok:1};
  if(action==='versions'){versionReads++;data={ok:1,rows:[{id:'00000000-0000-0000-0000-000000000000',version:'1.0.1',status:'failed',description:'<img src=x onerror=alert(1)>',source:'cloud',created:'2026-10-25T00:00:00.000Z',message:'编译失败',checks:[]}],total:1,page:1};}
  if(action==='release-save'){savedConfiguration=options.body;data={ok:1,configured:true,message:'上传代码配置已保存'};}
  if(action==='templates')data={ok:1,templates:[{templateId:'real_template_12345',title:'商品内容通知',fields:[{key:'thing1',label:'商品名称'},{key:'character_string2',label:'商品编码'}]}]};
  if(action==='subscribe-save'){subscriptionSaved=JSON.parse(options.body.get('config'));data={ok:1,config:subscriptionSaved,message:'订阅消息配置已保存'};}
  if(options.body)assert.equal(options.body.get('formhash'),w.document.querySelector('[data-release]').dataset.formhash);return {text:async()=>JSON.stringify(data)};
 };
 w.HTMLDialogElement.prototype.showModal=function(){this.open=true;};w.HTMLDialogElement.prototype.close=function(){this.open=false;};
 w.eval(source('app/source/plugin/flipbook/static/admin/wechat-release.js'));
 w.document.querySelector('[data-release-tab=settings]').click();check(w.document.querySelector('[data-release-panel=versions]').hidden&&!w.document.querySelector('[data-release-panel=settings]').hidden,'release history and upload configuration switch exclusively');
 for(const [key,value]of Object.entries({wechat_appid:'wx0000000000000000',wechat_ci_url:'https://ci.example.test',wechat_ci_token:'t'.repeat(32),wechat_ci_api_base:'https://album.example.test',wechat_release_version:'1.0.2'}))w.document.querySelector('[data-release-setting="'+key+'"]').value=value;
 w.document.querySelector('[data-release-save]').click();await tick();await tick();
 check(savedConfiguration&&savedConfiguration.get('wechat_release_version')==='1.0.2'&&versionReads===1,'upload settings save through the authenticated plugin action and reload version history');
 check(w.document.querySelector('[data-release-setting=wechat_ci_token]').value===''&&!w.document.querySelector('[data-release-rows] img'),'saved communication secrets are cleared from inputs and history renders untrusted descriptions as text');
 w.document.querySelector('[data-subscribe-fetch=product]').click();await tick();
 const choices=[...w.document.querySelectorAll('[data-subscribe-mapping] select')];choices[0].value='product_name';choices[1].value='product_code';w.document.querySelector('[data-subscribe-save]').click();await tick();
 check(subscriptionSaved.product.fields[1].source==='product_code'&&subscriptionSaved.product.enabled===0,'fetched subscription templates save explicit product field mappings before enabling');
 w.document.querySelector('[data-subscribe-toggle=product]').click();await tick();check(subscriptionSaved.product.enabled===1,'subscription enable action persists only after a valid mapping exists');
 admin.window.close();
 const micro=require(path.join(root,'miniprogram/utils/micro.js'));
 assert(!fs.existsSync(path.join(root,'miniprogram/utils/text-presets.json')));assert.deepEqual(fixture.config.styleDefaults,fixture.presets);
 for(const [type,node]of Object.entries(fixture.normalized)){const effective=micro.effectiveRegions(type,node.props,fixture.config.styleDefaults),expected=fixture.effective[type];assert.deepEqual(JSON.parse(JSON.stringify(effective)),Object.keys(expected).length?expected:{});}
 check(true,'PHP, editor and mini-program share the exact same typography role data for every component');
 const marked=micro.prepareNode({type:'markdown',props:{size:14,color:'#DADADD',html:'<h1>主标题</h1><h2>副标题</h2><p>描述</p><ul><li>列表</li></ul>'}},0,{},fixture.config.styleDefaults).html;
 check(/<h1 style="[^"]*font-size:22px/.test(marked)&&/<h2 style="[^"]*font-size:16px/.test(marked)&&/<p style="[^"]*font-size:14px/.test(marked)&&marked.includes('text-align:center'),'native Markdown headings, subtitles, paragraphs and lists use their matching role styles');
 const cfg=structuredClone(fixture.config);cfg.components=[{type:'markdown',id:'a',props:{markdown:'# 商品说明\n## 副标题\n\n描述',size:14,color:'#DADADD',regions:{}}},{type:'text',id:'b',props:{text:'保持不变',size:14,weight:400,color:'#DADADD',align:'center',lineHeight:1.7}},{type:'tab',id:'c',props:{tabs:[{title:'标签',children:[{type:'text',props:{text:'嵌套',size:14,color:'#DADADD'}}]}]}}];
 const html='<div class="fbmp"><aside class="fbmp-library"></aside><div class="fbmp-body"><div class="fbmp-canvas"><div id="fbmp-viewport"><div id="fbmp-frame"></div></div></div><aside class="fbmp-config"><div id="fbmp-inspector"></div></aside></div></div><script type="application/json" id="fbmp-data">'+JSON.stringify(cfg)+'</script><form id="fbmp-form"></form><input id="fbmp-config">'+['save','undo','redo','export','import'].map(k=>'<button id="fbmp-'+k+'"></button>').join('');
 const editor=new JSDOM(html,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only'}),v=editor.window;const errors=[];v.addEventListener('error',event=>errors.push(event.error));v.HTMLFormElement.prototype.submit=function(){};v.FBMP_KEYS=fixture.keys;v.FBMP_TYPES=fixture.types;v.FBMP_REGIONS=fixture.regions;v.FBMP_REGION_SELECTORS=fixture.selectors;v.FBMP_REGION_SOURCES=fixture.sources;v.FBMP_TEXT_PRESETS=fixture.presets;
 const queued=[];v.requestAnimationFrame=fn=>{queued.push(fn);return queued.length;};v.eval(source('app/source/plugin/flipbook/static/admin/micropage.js'));
 const flush=()=>{while(queued.length)queued.shift()();},q=selector=>v.document.querySelector(selector),canvas=q('.fbmp-canvas'),shell=q('.fbmp-preview-shell'),first=q('[data-node="components.0"]'),other=q('[data-node="components.1"]'),tools=q('.fbmp-component-tools');
 canvas.getBoundingClientRect=()=>({top:100,bottom:700});shell.getBoundingClientRect=()=>({top:20});first.getBoundingClientRect=()=>({top:540});Object.defineProperty(tools,'offsetHeight',{value:220});
 check(first.querySelector('h1').dataset.fbmpRegions.includes('heading')&&first.querySelector('h2').dataset.fbmpRegions.includes('subheading'),'editor Markdown exposes the same title and subtitle regions as H5 and native');first.querySelector('.fbmp-node-body').click();flush();check(tools.dataset.path==='components.0'&&tools.style.top==='452px','selected-component tools clamp inside the visible scroll area');
 shell.getBoundingClientRect=()=>({top:-400});first.getBoundingClientRect=()=>({top:-100});canvas.dispatchEvent(new v.Event('scroll'));flush();check(tools.style.top==='508px'&&q('[data-node="components.1"]')===other,'scrolling moves only the toolbar and preserves unrelated component DOM');
 q('[data-config-tab=regions]').click();q('[data-region-select]').value='heading';q('[data-region-select]').dispatchEvent(new v.Event('change',{bubbles:true}));const size=q('[data-field="components.0.props.regions.heading.size"]'),align=q('[data-field="components.0.props.regions.heading.align"]');
 check(size.value==='22'&&align.value==='center','inspector displays the actual inherited title size and centered alignment');
 size.value='30';size.dispatchEvent(new v.Event('input',{bubbles:true}));check(q('[data-node="components.1"]')===other&&q('style[data-fbmp-region-styles]').textContent.includes('font-size:30px'),'editing a title style applies locally without recreating another component');
 other.querySelector('.fbmp-node-body').click();flush();check(tools.dataset.path==='components.1'&&!tools.querySelector('[data-node-op=up]').disabled,'changing selection immediately rebinds toolbar operations');
 q('[data-node="components.2.props.tabs.0.children.0"] .fbmp-node-body').click();flush();check(tools.dataset.path==='components.2.props.tabs.0.children.0'&&tools.querySelector('[data-node-op=up]').disabled&&tools.querySelector('[data-node-op=down]').disabled,'nested selection binds the toolbar and retains first/last operation limits');
 assert.equal(errors.length,0,errors.map(error=>error.message).join(';'));editor.window.close();
})().catch(error=>{console.error(error);process.exitCode=1;});
