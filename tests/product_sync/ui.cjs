const fs=require('node:fs'),assert=require('node:assert/strict'),path=require('node:path');
const {JSDOM,VirtualConsole}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
const code=fs.readFileSync(process.env.SYNC_CANDIDATE?path.join(process.env.SYNC_CANDIDATE,'static/admin.js'):path.join(__dirname,'../../app/source/plugin/flipbook_sync/static/admin.js'),'utf8');
let checks=0;
function check(value,label){assert.ok(value,label);checks++;process.stdout.write('PASS '+label+'\n');}
const tick=()=>new Promise(resolve=>setTimeout(resolve,0));
function setup(){
 const html=`<main data-sync-page="receive" data-endpoint="admin.php?action=plugins&operation=config&identifier=flipbook_sync&pmod=receive" data-formhash="fixturehash">
 <p data-sync-status></p><button data-sync-new="grant">新增授权</button><button data-sync-new="source">添加数据源</button>
 <section data-sync-issued hidden><input data-issued-key><button data-copy-issued>复制密钥</button></section>
 <section data-sync-progress hidden><progress></progress><p data-sync-count></p><button data-sync-stop>停止</button></section>
 <button data-sync-action="key-rotate" data-record="1">重新生成</button><button data-sync-action="source-test" data-record="1">测试连接</button>
 <button data-sync-start="1">一键同步</button><button data-sync-start="2" disabled>停用来源</button>
 <input data-api-address value="https://source.example/plugin.php?id=flipbook_sync:api"><button data-copy-api>复制地址</button>
 <dialog data-grant-dialog><form data-sync-form="grant"><h3 data-dialog-title></h3><input type="hidden" name="recordid" value="0"><label><select name="field_mode" data-field-mode><option value="shared">共享</option><option value="independent">独立</option></select></label><input name="domain"><input name="note"><textarea name="ips"></textarea><p data-form-status></p><button type="button" data-sync-cancel>取消</button><button type="submit">保存</button></form></dialog>
 <dialog data-source-dialog><form data-sync-form="source"><h3 data-dialog-title></h3><input type="hidden" name="recordid" value="0"><input name="name"><input name="endpoint"><input data-source-key type="password"><p data-form-status></p><button type="button" data-sync-cancel>取消</button><button type="submit">保存</button></form></dialog></main>`;
 const dom=new JSDOM(html,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only',virtualConsole:new VirtualConsole()});
 const w=dom.window,calls=[];let response=()=>({ok:0,message:'fixture rejection'});
 w.Element.prototype.scrollIntoView=function(){};
 w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};
 w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};
 w.fetch=async(url,options)=>{const call={url,options,values:Object.fromEntries(new w.URLSearchParams(options.body))};calls.push(call);const data=await response(call);return {ok:!!data.ok,json:async()=>data};};
 w.confirm=()=>false;w.document.execCommand=()=>false;w.eval(code);
 return {w,d:w.document,calls,setResponse(fn){response=fn;},close(){w.close();}};
}
(async()=>{
 const {execFileSync}=require('node:child_process');
 const ipAudit=JSON.parse(execFileSync('/Users/zhang/.local/bin/docker',['exec','-i','flipbook-php','php'],{encoding:'utf8',input:`<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();
$ips=['100.64.0.1','192.0.0.1','192.0.2.1','198.18.0.1','224.0.0.1','ff02::1','2001:db8::1'];$allowed=[];
foreach($ips as $ip)try{flipbook_sync\\service_client::address($ip);$allowed[]=$ip;}catch(Throwable $e){}
$public=0;foreach(['8.8.8.8','2001:4860:4860::8888'] as $ip)if(flipbook_sync\\service_client::address($ip)===$ip)$public++;echo json_encode(['allowed'=>$allowed,'public'=>$public,'nativePluginId'=>intval(DB::result_first('SELECT pluginid FROM %t WHERE identifier=%s',['common_plugin','flipbook']))]);
`}));
 check(ipAudit.allowed.length===0 && ipAudit.public===2,'receiver rejects non-global/multicast addresses and accepts global IPv4/IPv6');
 let t=setup();
 t.d.querySelector('[data-sync-new="grant"]').click();
 check(t.d.querySelector('[data-grant-dialog]').open&&t.calls.length===0,'opening a grant form does not commit a request');
 const form=t.d.querySelector('[data-sync-form="grant"]');form.elements.domain.value='client.example';
 form.dispatchEvent(new t.w.Event('submit',{bubbles:true,cancelable:true}));await tick();
 check(t.calls[0].values.sync_action==='key-create'&&!('operation' in t.calls[0].values)&&t.calls[0].values.formhash==='fixturehash','admin action cannot overwrite the native operation routing and includes CSRF');
 check(form.elements.domain.value==='client.example'&&form.querySelector('[data-form-status]').textContent==='fixture rejection'&&!form.querySelector('[type=submit]').disabled,'failed save preserves form input and restores the save button');
 form.querySelector('[data-sync-cancel]').click();check(!form.closest('dialog').open&&t.calls.length===1,'cancel closes the form without another request');
 t.d.querySelector('[data-sync-action="key-rotate"]').click();await tick();check(t.calls.length===1,'cancelled rotation does not revoke a credential');t.close();

 t=setup();t.d.querySelector('[data-sync-new="source"]').click();
 const source=t.d.querySelector('[data-sync-form="source"]');source.elements.name.value='fixture';source.elements.endpoint.value='https://source.example/';source.querySelector('[data-source-key]').value='fixture-source-secret';source.dispatchEvent(new t.w.Event('submit',{bubbles:true,cancelable:true}));await tick();
 const sent=t.calls[0];check(sent.options.headers['X-Flipbook-Source-Key']==='fixture-source-secret'&&!sent.options.body.toString().includes('fixture-source-secret')&&!sent.url.includes('fixture-source-secret'),'source credential travels in its private header, never the URL or admin form log');
 check(sent.url.includes('inajax=1')&&sent.values.sync_action==='source-save','source save uses the authenticated native AJAX route');
 t.d.querySelector('[data-copy-api]').click();await tick();check(t.d.querySelector('[data-sync-status]').textContent.includes('复制失败'),'clipboard failure is visible');t.close();

 t=setup();t.setResponse(call=>{
  if(call.values.sync_action==='job-start')return {ok:1,job:{jobid:'a'.repeat(32),status:'running'}};
  if(call.values.sync_action==='job-step')return {ok:1,job:{status:'complete',total:3,processed:3,added:1,changed:1,skipped:1}};
  return {ok:0,message:'unexpected'};
 });
 t.d.querySelector('[data-sync-start="1"]').click();await tick();await tick();
 check(t.calls.map(x=>x.values.sync_action).join(',')==='job-start,job-step'&&t.d.querySelector('[data-sync-status]').textContent==='同步完成','one click runs the real start/step state machine to completion');
 check(t.d.querySelector('[data-sync-count]').textContent.includes('新增 1')&&t.d.querySelector('progress').value===3,'visible counts reflect the server response');
 check(!t.d.querySelector('[data-sync-start="1"]').disabled&&t.d.querySelector('[data-sync-start="2"]').disabled,'disabled data sources stay disabled after another sync completes');t.close();

 t=setup();let finish;
 t.setResponse(call=>{
  if(call.values.sync_action==='job-start')return {ok:1,job:{jobid:'b'.repeat(32),status:'running'}};
  if(call.values.sync_action==='job-step')return new Promise(resolve=>{finish=resolve;});
  if(call.values.sync_action==='job-pause')return {ok:1};
  return {ok:0,message:'unexpected'};
 });
 t.d.querySelector('[data-sync-start="1"]').click();await tick();t.d.querySelector('[data-sync-stop]').click();
 finish({ok:1,job:{status:'running',total:20,processed:10,added:10,changed:0,skipped:0}});await tick();await tick();
 check(t.calls.map(x=>x.values.sync_action).join(',')==='job-start,job-step,job-pause'&&t.d.querySelector('[data-sync-status]').textContent.includes('可再次'),'stop waits for the current batch and preserves a resumable job');t.close();
 const html=execFileSync('/Users/zhang/.local/bin/docker',['exec','-i','flipbook-php','php'],{encoding:'utf8',input:`<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');require './source/class/class_core.php';C::app()->init();
if(${process.env.SYNC_CANDIDATE?'true':'false'}&&is_file('/tmp/flipbook-sync-increment-test/flipbook_sync/admin/admin_view.php')){require '/tmp/flipbook-sync-increment-test/flipbook_sync/admin/admin_controller.php';require '/tmp/flipbook-sync-increment-test/flipbook_sync/admin/admin_view.php';}
if(${process.env.SYNC_CANDIDATE?'true':'false'}&&is_file('/tmp/flipbook-sync-increment-test/flipbook_sync/service/service_fields.php'))require '/tmp/flipbook-sync-increment-test/flipbook_sync/service/service_fields.php';
$_GET=['do'=>3];DB::query('START TRANSACTION');try{DB::insert('flipbook_sync_source',['name'=>'UI fixture','endpoint'=>'https://ui-fixture.example/','domain'=>'fixture.example','keycipher'=>'never-rendered','enabled'=>1,'instanceid'=>'','lastsync'=>0]);flipbook_sync\\admin_view::render('data');}finally{DB::query('ROLLBACK');}
`});
 const catalog=new JSDOM(html,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only',virtualConsole:new VirtualConsole()}),w=catalog.window,d=w.document,calls=[];let matched=false;
 w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};w.CSS={escape:value=>value};w.confirm=()=>true;
 w.fetch=async(url,options)=>{const values=Object.fromEntries(new w.URLSearchParams(options.body));calls.push(values);let result={ok:1};
  if(values.sync_action==='local-products')result.catalog={items:[],has_more:false,next_cursor:0,shared_fields:['name'],allowed_fields:['name'],field_mode:'shared',local_enabled:true};
  if(values.sync_action==='source-products')result.catalog={items:[{recordid:42,productid:42,name:'fixture',code:'fixture',match:{ok:matched,issues:matched?[]:[{field:'supplier',message:'未匹配'}]}}],has_more:false,next_cursor:42};
  if(values.sync_action==='source-match')result={ok:1,match:{ok:false,issues:[],relations:[{kind:'supplier',remoteid:7,name:'fixture supplier',status:'mismatch',targetid:0}]},choices:{supplier:[]},fields:[],mapping:{}};
  if(values.sync_action==='match-save')matched=true;
  return {ok:true,json:async()=>result};};w.eval(code);await tick();
 check(new URL(d.querySelector('[data-remote-catalog] a').href).searchParams.get('do')===String(ipAudit.nativePluginId),'external draft link uses the original product plugin ID rather than the sync plugin ID');
 check(d.querySelectorAll('[name="fields[]"]').length===0,'catalog contains no duplicated authorization field configuration');
 d.querySelector('[data-catalog-kind="remote"]').click();await tick();
 check(calls.at(-1).kind==='product'&&d.querySelectorAll('[data-entity-kind]').length===4,'external domain catalog exposes all four typed lists');
 check(d.querySelector('[data-publish-product]').disabled&&d.querySelector('.is-error').textContent.includes('未匹配'),'unmatched rows are red and one-click action stays disabled');
 d.querySelector('[data-entity-kind="category"]').click();await tick();check(calls.at(-1).kind==='category','typed list switch requests the correct remote entity');
 d.querySelector('[data-match-record]').click();await tick();check(d.querySelector('[data-match-dialog]').open&&calls.at(-1).sync_action==='source-match','field match dialog reads actual current record before edits');
 const select=d.querySelector('[data-match-kind]');select.value='new';d.querySelector('[data-match-form]').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await tick();await tick();
 const matchedCall=calls.find(call=>call.sync_action==='match-save');check(JSON.parse(matchedCall.choices)[0].kind==='supplier'&&JSON.parse(matchedCall.choices)[0].mode==='new','relation matching submits an explicit fixed-type create choice');
 check(!d.querySelector('[data-publish-product]').disabled,'successfully matched row enables one-click draft import');
 d.querySelector('[data-publish-product]').click();await tick();check(calls.at(-1).sync_action==='source-publish'&&calls.at(-1).kind==='category'&&calls.at(-1).productid==='42','one-click import preserves source record and selected entity kind');
 catalog.window.close();
 // A native browser ignores requestSubmit() reentered during its submit dispatch.
 const draftCode=fs.readFileSync(process.env.SYNC_CANDIDATE?path.join(process.env.SYNC_CANDIDATE,'static/draft.js'):path.join(__dirname,'../../app/source/plugin/flipbook_sync/static/draft.js'),'utf8');
 const submitMeta={formId:'native-submit-form',fields:['name'],draftid:1,revision:1,targetid:1,kind:'product',editorKey:'a'.repeat(32),endpoint:'admin.php'};
 const nativeSubmitDom=new JSDOM('<button type="submit" form="native-submit-form">保存商品</button><form id="native-submit-form"><input name="name" value="saved draft"><input name="formhash" value="fixturehash"></form><script type="application/json" data-product-draft-config>'+JSON.stringify(submitMeta)+'</script>',{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only',virtualConsole:new VirtualConsole()});
 const nw=nativeSubmitDom.window,nf=nw.document.querySelector('form');let dispatchingSubmit=false,nativeSubmitCount=0;
 nw.HTMLFormElement.prototype.requestSubmit=function(submitter){if(dispatchingSubmit)return;dispatchingSubmit=true;const event=new nw.SubmitEvent('submit',{bubbles:true,cancelable:true,submitter});this.dispatchEvent(event);dispatchingSubmit=false;if(!event.defaultPrevented)nativeSubmitCount++;};
 nw.eval(draftCode);nf.requestSubmit(nw.document.querySelector('button'));await new Promise(resolve=>setTimeout(resolve,25));
 check(nativeSubmitCount===1,'saving an unchanged restored draft defers native submit beyond the original submit dispatch');nativeSubmitDom.window.close();
 console.log('TOTAL '+checks+' behavior checks');
})().catch(error=>{console.error(error);process.exitCode=1;});
