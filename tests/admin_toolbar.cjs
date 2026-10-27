'use strict';
// Verify real rendered controls with a read-only PHP fixture and mocked requests.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),{spawnSync}=require('node:child_process');
const {JSDOM}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
let passed=0;const check=(name,fn)=>{fn();passed++;console.log('PASS '+name);};
function php(file){const args=['exec','-i'];for(const name of ['FLIPBOOK_PLUGIN_ROOT','FLIPBOOK_SYNC_ROOT'])if(process.env[name])args.push('-e',name+'='+process.env[name]);args.push('flipbook-php','php');const r=spawnSync('docker',args,{input:fs.readFileSync(path.join(root,'tests',file)),encoding:'utf8',timeout:30000});assert.equal(r.status,0,r.stderr||r.stdout);assert.equal(r.stderr,'');return JSON.parse(r.stdout);}
const html=php('admin_toolbar.php'),sync=php('sync_layout.php');
const evalFile=(w,rel)=>w.eval(fs.readFileSync(path.join(root,rel),'utf8'));
const tick=async()=>{for(let i=0;i<4;i++)await new Promise(r=>setImmediate(r));};
(async()=>{
 for(const [page,key]of [['settingsBasic','basic'],['settingsShare','share']]){
  const dom=new JSDOM(html[page],{url:'http://localhost/admin.php',runScripts:'outside-only'}),w=dom.window,d=w.document;evalFile(w,'app/source/plugin/flipbook/static/admin/settings.js');
  check(page+' restores exactly one settings panel',()=>{assert.equal(d.querySelectorAll('[data-fb-settings-panel]:not([hidden])').length,1);assert.equal(d.querySelector('[data-fb-settings-panel]:not([hidden])').dataset.fbSettingsPanel,key);assert.equal(d.querySelectorAll('form').length,1);});
  d.querySelector('[name=albumname]').value='未保存画册';d.querySelector('[name=sharetitle]').value='未保存分享';d.querySelector('[data-fb-settings-tab=share]').click();d.querySelector('[data-fb-settings-tab=basic]').click();
  check(page+' keeps both tabs drafts and submits all fields',()=>{const f=new w.FormData(d.querySelector('form'));assert.equal(f.get('albumname'),'未保存画册');assert.equal(f.get('sharetitle'),'未保存分享');assert.equal(f.get('settings_tab'),'basic');});
  d.querySelector('[data-fb-settings-tab=basic]').dispatchEvent(new w.KeyboardEvent('keydown',{key:'End',bubbles:true,cancelable:true}));
  check(page+' keyboard selection updates focus and save tab',()=>{assert.equal(d.activeElement.dataset.fbSettingsTab,'share');assert.equal(d.querySelector('[data-fb-settings-active]').value,'share');});dom.window.close();
 }
 for(const [tab,text,action]of [['admincp','域名授权','grant'],['receive','数据源','source'],['data','调用数据',''],['guide','调用说明','']]){
  const dom=new JSDOM(sync[tab]),d=dom.window.document,bar=d.querySelector('.fb-sync-toolbar');
  check(tab+' has contextual right aligned toolbar actions',()=>{assert(bar&&bar.querySelector('nav'));const buttons=[...bar.querySelectorAll('[data-sync-new]')];assert.equal(buttons.length,action?1:0);if(action){assert.equal(buttons[0].dataset.syncNew,action);assert.equal(buttons[0].previousElementSibling.dataset.syncHelp,tab==='admincp'?'grants':'sources');}assert.equal(bar.querySelectorAll('[data-sync-help]').length,tab==='guide'?0:1);});
  check(tab+' omits the duplicated content title',()=>assert(![...d.querySelectorAll('.fb-sync-heading h3')].some(n=>n.textContent===text)));dom.window.close();
 }
 const css=fs.readFileSync(path.join(root,'app/source/plugin/flipbook_sync/static/admin.css'),'utf8');
 check('guide can fill its container and help marks have no frame',()=>{assert(/\.fb-sync-guide\{[^}]*max-width:none/.test(css));assert(/\.fb-sync-help>span:first-child\{[^}]*border:0/.test(css));});
 const dom=new JSDOM('<form id="cpform">'+html.orders+html.trade+html.customer+'</form>',{url:'http://localhost/admin.php',runScripts:'outside-only'}),w=dom.window,d=w.document,requests=[];let rejectSave=false;
 w.fetch=async(url,options)=>{const u=new URL(url),action=u.searchParams.get('fb_wechat_action');if(action==='commerce-orders')return {ok:true,json:async()=>({ok:1,installed:true,orders:{items:[],total:0,page:1}})};const body=Object.fromEntries(options.body.entries());requests.push(body);return {ok:!rejectSave,json:async()=>rejectSave?({ok:0,message:'保留原配置'}):({ok:1,message:'配置已保存',secrets:{}})};};
 evalFile(w,'app/source/plugin/flipbook/static/admin/wechat-commerce.js');await tick();
 const orders=d.querySelector('[data-commerce-group=orders]'),toggle=orders.querySelector('[data-commerce-toggle]');
 check('orders have no nested tabs and switch follows refresh',()=>{assert.equal(orders.querySelectorAll('[data-commerce-tab]').length,0);assert.equal(toggle.getAttribute('role'),'switch');assert.equal(orders.querySelector('.fb-commerce-order-enable').previousElementSibling.dataset.commerceRefresh,'');assert.equal(toggle.form,null);});
 toggle.click();await tick();toggle.click();await tick();
 check('order switch immediately persists both on and off values',()=>{assert.equal(requests[0].wechat_orders_enabled,'1');assert.equal(requests[1].wechat_orders_enabled,'0');assert.equal(requests[1].group,'orders');assert.equal(toggle.getAttribute('aria-checked'),'false');});
 rejectSave=true;toggle.click();await tick();check('failed order switch restores last saved state',()=>{assert.equal(toggle.value,'0');assert.equal(toggle.getAttribute('aria-checked'),'false');assert(orders.querySelector('[data-commerce-status]').textContent.includes('保留原配置'));});rejectSave=false;
 for(const group of ['trade','customer']){const form=d.querySelector('[data-commerce-form='+group+']'),control=form.querySelector('[data-commerce-toggle]'),field=form.querySelector('[data-commerce-setting="'+(group==='trade'?'wechat_trade_buy_text':'wechat_customer_title')+'"]');field.value='保留输入';control.click();await tick();const before=requests.length;form.querySelector('[data-commerce-save]').click();await tick();
  check(group+' switch shares the existing form save without losing inputs',()=>{assert.equal(requests.length,before+1);assert.equal(requests.at(-1)['wechat_'+group+'_enabled'],'1');assert.equal(requests.at(-1)[field.dataset.commerceSetting],'保留输入');assert.equal(control.form,null);});}
 dom.window.close();

 console.log(passed+' current admin toolbar checks passed');

})().catch(e=>{console.error(e);process.exitCode=1;});
