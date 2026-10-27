const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {JSDOM,VirtualConsole}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
const source=process.env.SYNC_CANDIDATE||path.resolve(__dirname,'../../app/source/plugin/flipbook_sync');
const draftCode=fs.readFileSync(path.join(source,'static/draft.js'),'utf8');
let count=0;function check(value,message){assert.ok(value,message);count++;console.log('PASS '+message);}
const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
function setup(){
 const meta={formId:'fb-product-form',fields:['name','summary','medialines'],kind:'product',targetid:1,draftid:0,revision:0,editorKey:'e'.repeat(32),endpoint:'/admin.php?view=drafts'};
 const dom=new JSDOM(`<button type="button" data-product-draft-save>保存草稿</button><form id="fb-product-form"><input name="formhash" value="fixture"><input name="name" value="原名称"><input name="summary" value="原副标题"><textarea name="medialines" hidden></textarea><button data-panel="sku" type="button">SKU</button><button type="submit">保存商品</button></form><script type="application/json" data-product-draft-config>${JSON.stringify(meta)}</script>`,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only',virtualConsole:new VirtualConsole()});
 const w=dom.window,calls=[];let failure=false,resolvePending,delayed=false,submitted=0,beacons=0;
 w.fetch=async(url,options)=>{const body=Object.fromEntries(new w.URLSearchParams(options.body));calls.push(body);if(delayed)await new Promise(resolve=>resolvePending=resolve);const ok=!failure;return {ok,json:async()=>ok?{ok:1,draftid:15,revision:calls.length}:{ok:0,message:'fixture denied'}};};
 w.navigator.sendBeacon=()=>{beacons++;return true;};
 w.HTMLFormElement.prototype.requestSubmit=function(){submitted++;};
 w.eval(draftCode);const d=w.document;
 return {w,d,calls,setFailure(value){failure=value;},setDelayed(){delayed=true;},resolve(){delayed=false;resolvePending();},get submitted(){return submitted;},get beacons(){return beacons;},close(){w.close();}};
}
(async()=>{
 let t=setup();await wait(900);check(t.calls.length===0,'opening native editor does not create a draft');
 t.d.querySelector('[data-panel]').click();await wait(900);check(t.calls.length===0,'switching native tabs does not create a draft');
 const input=t.d.querySelector('[name=summary]');input.value='修改副标题';input.dispatchEvent(new t.w.Event('input',{bubbles:true}));await wait(900);
 check(t.calls.length===1&&JSON.parse(t.calls[0].payload).summary==='修改副标题','typing automatically persists full native form snapshot');
 check(t.calls[0].formhash==='fixture'&&t.calls[0].targetid==='1'&&t.calls[0].editid.length===32,'autosave binds CSRF, native target and editing session');
 check(t.d.querySelector('[data-draft-saved]')&&t.d.querySelector('[name=draft_revision]').value==='1','saved confirmation appears only after server acknowledgement');
 input.value='继续修改';input.dispatchEvent(new t.w.Event('change',{bubbles:true}));t.setDelayed();t.d.querySelector('[data-product-draft-save]').click();await wait(1);
 check(!t.d.querySelector('[data-draft-saved]')&&t.d.querySelector('[data-product-draft-save]').disabled,'pending save clears stale success and prevents duplicate click');
 t.d.querySelector('form').dispatchEvent(new t.w.Event('submit',{bubbles:true,cancelable:true}));await wait(1);check(t.submitted===0,'formal save waits for in-flight draft acknowledgement');
 t.resolve();await wait(5);check(t.submitted===1,'formal native submit resumes after saving latest draft');t.close();
 t=setup();t.setFailure(true);t.d.querySelector('[data-product-draft-save]').click();await wait(1);check(t.d.querySelector('.fb-product-draft-status').textContent==='fixture denied'&&!t.d.querySelector('[data-draft-saved]'),'failed save never displays saved status');
 t.d.querySelector('form').dispatchEvent(new t.w.Event('submit',{bubbles:true,cancelable:true}));await wait(1);check(t.submitted===0,'failed draft save prevents silent formal submit');
 const edit=t.d.querySelector('[name=name]');edit.value='待离开保存';edit.dispatchEvent(new t.w.Event('input',{bubbles:true}));t.w.dispatchEvent(new t.w.Event('pagehide'));check(t.beacons===1,'leaving dirty editor sends best-effort latest snapshot');t.close();
 t=setup();const field=t.d.querySelector('[name=name]');field.value='上传中的商品';field.dispatchEvent(new t.w.Event('input',{bubbles:true}));t.d.querySelector('[data-product-draft-save]').click();await wait(2);t.d.querySelector('[name=medialines]').value='detail|回调图片|https://images.example/original.webp';await wait(3000);check(t.calls.length===2&&JSON.parse(t.calls[1].payload).medialines.includes('original.webp'),'async image callback after manual save is still captured by automatic draft polling');t.close();
 console.log('TOTAL '+count+' draft UI checks');
})().catch(error=>{console.error(error);process.exitCode=1;});
