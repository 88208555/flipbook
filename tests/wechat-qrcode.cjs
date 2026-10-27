// Upload controller contract tests; no browser, network, or real QR image is used.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/static/admin/wechat.js'),'utf8');
function node(){return {handlers:{},dataset:{},value:'',hidden:false,disabled:false,textContent:'',addEventListener(key,fn){this.handlers[key]=fn;},removeAttribute(name){delete this[name];}};}
const active=node(),value=node(),preview=node(),upload=node(),clear=node(),status=node(),field=node(),form=node();value.value='data/attachment/flipbook/wechat/qrcode/old.png';
const selectors=new Map([['[name="wechat_qrcode"]',value],['[data-fb-wechat-qrcode-preview]',preview],['[data-fb-wechat-qrcode-upload]',upload],['[data-fb-wechat-qrcode-clear]',clear],['[data-fb-wechat-qrcode-status]',status]]);field.querySelector=selector=>selectors.get(selector);
form.querySelector=()=>active;form.querySelectorAll=selector=>selector==='[data-fb-wechat-qrcode-field]'?[field]:selector==='[data-fb-wechat-qrcode-status]'?[status]:[];
const pickers=[],calls=[];const document={getElementById:()=>form,createElement(tag){assert.equal(tag,'input');const picker=node();picker.click=()=>picker.opened=true;pickers.push(picker);return picker;}};
class FormData{constructor(){this.values=new Map();}set(key,value){this.values.set(key,value);}get(key){return this.values.get(key);}}
let response={ok:1,url:'data/attachment/flipbook/wechat/qrcode/new.png'},holdSession=false,resumeSession;
async function fetch(url,options){calls.push({url,options});if(new URL(url).searchParams.get('op')==='session'){if(holdSession)return new Promise(resolve=>{resumeSession=()=>resolve({json:async()=>({ok:1,formhash:'fresh-fixture-formhash'})});});return {json:async()=>({ok:1,formhash:'fresh-fixture-formhash'})};}return {json:async()=>response};}
vm.runInNewContext(source,{document,URL,location:{href:'http://localhost/forum/admin.php?pmod=wechat'},FormData,fetch,Error,Array});
function check(ok,message){assert.ok(ok,message);console.log('PASS '+message);}
function picker(file){upload.handlers.click();const last=pickers.at(-1);last.files=file?[file]:[];return last;}
(async()=>{
 check(preview.src===value.value&&!preview.hidden&&!clear.hidden,'saved QR image appears immediately using the existing hidden value');
 let choose=picker({name:'bad.svg',size:50});await choose.onchange();check(choose.opened&&choose.accept==='image/jpeg,image/png,image/webp,image/gif'&&calls.length===0&&value.value.endsWith('old.png'),'unsupported file is rejected before any request or QR replacement');
 choose=picker({name:'too-large.png',size:8*1024*1024+1});await choose.onchange();check(calls.length===0&&status.textContent.includes('8MB'),'oversized image is rejected before requesting an upload session');
 choose=picker({name:'fixture.png',size:120});holdSession=true;const pending=choose.onchange();let prevented=false;form.handlers.submit({preventDefault(){prevented=true;}});
 check(prevented&&upload.disabled&&clear.disabled&&status.textContent.includes('正在上传'),'save and QR replacement are guarded while the selected image is uploading');
 resumeSession();await pending;holdSession=false;
 check(calls.length===2&&new URL(calls[0].url).pathname==='/forum/plugin.php'&&new URL(calls[0].url).searchParams.get('op')==='session'&&calls[0].options.credentials==='same-origin','upload first obtains a fresh management session from the actual installation-relative asset endpoint');
 const request=calls[1],body=request.options.body;
 check(new URL(request.url).searchParams.get('op')==='upload'&&request.options.method==='POST'&&request.options.credentials==='same-origin'&&body.get('kind')==='image'&&body.get('scope')==='wechat'&&body.get('purpose')==='qrcode'&&body.get('formhash')==='fresh-fixture-formhash','upload submits the selected image with fresh CSRF data and the WeChat QR storage context');
 check(value.value===response.url&&preview.src===response.url&&!preview.hidden&&!clear.hidden&&!upload.disabled&&!clear.disabled&&status.textContent==='上传成功，保存设置后生效','successful upload updates both the saved form value and image preview');
 const beforeClear=calls.length;clear.handlers.click();check(value.value===''&&preview.hidden&&!preview.src&&clear.hidden&&calls.length===beforeClear&&status.textContent.includes('保存设置后生效'),'clearing affects only the unsaved form and preview without deleting a server asset');
 response={ok:1,url:'data/attachment/flipbook/wechat/qrcode/replacement.png'};choose=picker({name:'replacement.webp',size:200});await choose.onchange();const kept=value.value;
 response={ok:0,message:'fixture-upload-rejected'};choose=picker({name:'failed.png',size:200});await choose.onchange();check(value.value===kept&&preview.src===kept&&status.textContent==='fixture-upload-rejected'&&!upload.disabled&&!clear.disabled,'failed replacement preserves the previous image and restores controls');
 const beforeCancel=calls.length;choose=picker();await choose.onchange();check(calls.length===beforeCancel&&value.value===kept,'canceling the file picker leaves both the form value and server unchanged');
 prevented=false;form.handlers.submit({preventDefault(){prevented=true;}});check(!prevented,'the complete form can be saved after the upload finishes');
 check(calls.every(call=>!['remove_asset','delete'].includes(new URL(call.url).searchParams.get('op'))),'upload and clear never delete files before settings are saved');
})().catch(error=>{console.error(error);process.exitCode=1;});
