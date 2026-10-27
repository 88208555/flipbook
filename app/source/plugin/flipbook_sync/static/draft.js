(function(){
 'use strict';
 const list=document.querySelector('[data-draft-list]');
 if(list){
  const all=list.querySelector('[data-draft-all]'),publish=list.querySelector('[name=draft_action][value=publish]');
  function selection(){const selected=Array.from(list.querySelectorAll('[name="selected[]"]:checked'));publish.disabled=!selected.length||selected.some(input=>input.dataset.matchReady==='0');}
  all.addEventListener('change',()=>{list.querySelectorAll('[name="selected[]"]').forEach(input=>input.checked=all.checked);selection();});
  list.addEventListener('change',selection);
  list.addEventListener('submit',event=>{const action=event.submitter&&event.submitter.value;if(action==='discard'&&!window.confirm('删除所选草稿？正式商品不受影响。'))event.preventDefault();});selection();
 }
 const meta=document.querySelector('[data-product-draft-config]');if(!meta)return;
 const config=JSON.parse(meta.textContent),form=document.getElementById(config.formId);if(!form)return;
 const keys=new Set(config.fields),editid=Array.from(crypto.getRandomValues(new Uint8Array(16)),n=>n.toString(16).padStart(2,'0')).join('');
 let draftid=Number(config.draftid)||0,revision=Number(config.revision)||0,sequence=0,timer,pending=null,submitting=false,touched=false;
 const status=document.createElement('p');status.className='fb-product-draft-status';status.setAttribute('role','status');status.setAttribute('aria-live','polite');form.before(status);
 const button=document.querySelector('[data-product-draft-save]');
 function values(){const out={};for(const [key,value] of new FormData(form))if(keys.has(key)&&typeof value==='string')out[key]=value;return out;}
 let saved=JSON.stringify(values());
 function body(snapshot){return new URLSearchParams({draft_action:'save',draftsubmit:'1',formhash:form.querySelector('[name=formhash]').value,draftid:String(draftid),targetid:String(config.targetid||0),draft_kind:config.kind,editor_key:config.editorKey,editid,sequence:String(++sequence),revision:String(revision),payload:snapshot});}
 function identity(){
  for(const [name,value] of Object.entries({draftid,draft_revision:revision})){
   let input=form.querySelector('[name="'+name+'"]');if(!input){input=document.createElement('input');input.type='hidden';input.name=name;form.appendChild(input);}input.value=String(value);
  }
 }
 async function save(force=false){
  if(pending){await pending;return save(force);}
  const snapshot=JSON.stringify(values());if(!force&&snapshot===saved)return;
  status.removeAttribute('data-draft-saved');status.textContent='正在保存草稿…';if(button)button.disabled=true;
  const attempt=(async()=>{
   const response=await fetch(config.endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:body(snapshot)});
   let result;try{result=await response.json();}catch(error){throw new Error('草稿未保存，管理会话可能已过期，请先保留当前页面。');}
   if(!response.ok||!result.ok)throw new Error(result.message||'草稿保存失败，当前输入仍保留在页面。');
   draftid=Number(result.draftid);revision=Number(result.revision);saved=snapshot;identity();
   status.textContent='草稿已保存，正式内容尚未修改。';status.setAttribute('data-draft-saved','');
   if(!config.draftid){const url=new URL(location.href);url.searchParams.set('draftid',String(draftid));history.replaceState(null,'',url);}
  })();
  pending=attempt;
  try{await attempt;}catch(error){status.textContent=error.message;throw error;}finally{pending=null;if(button)button.disabled=false;}
 }
 function schedule(){if(submitting)return;clearTimeout(timer);timer=null;timer=setTimeout(()=>{timer=null;save().catch(()=>{});},800);}
 function changed(){touched=true;schedule();}
 form.addEventListener('input',changed);form.addEventListener('change',changed);
 document.addEventListener('click',event=>{const button=event.target.closest('button');if(button&&((form.contains(button)&&!button.hasAttribute('data-panel'))||button.matches('[data-gallery-add],[data-sku-add],[data-table-col-add],[data-product-upload],[data-library-pick]')))changed();});
 // Gallery and SKU controls update hidden values; sample those changes without a second editor.
 const watch=setInterval(()=>{if(!timer&&!submitting&&touched&&JSON.stringify(values())!==saved)schedule();},2000);
 if(button)button.addEventListener('click',()=>{clearTimeout(timer);timer=null;save(!draftid).catch(()=>{});});
 form.addEventListener('submit',async function(event){
  if(submitting||event.defaultPrevented)return;
  event.preventDefault();clearTimeout(timer);timer=null;
  try{if(pending)await pending;if(JSON.stringify(values())!==saved||!draftid)await save(true);identity();submitting=true;clearInterval(watch);const submitter=event.submitter||undefined;setTimeout(()=>form.requestSubmit(submitter),0);}
  catch(error){submitting=false;}
 });
 window.addEventListener('beforeunload',function(event){if(!submitting&&(pending||(touched&&JSON.stringify(values())!==saved))){event.preventDefault();event.returnValue='';}});
 window.addEventListener('pagehide',function(){
  clearInterval(watch);clearTimeout(timer);timer=null;
  if(!submitting&&touched&&JSON.stringify(values())!==saved&&navigator.sendBeacon)navigator.sendBeacon(config.endpoint,body(JSON.stringify(values())));
 });
 identity();if(draftid)status.textContent='已恢复草稿，正式内容尚未修改。';
})();
