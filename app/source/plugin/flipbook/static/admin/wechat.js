(function(){
 'use strict';
 var form=document.getElementById('cpform');if(!form)return;
 var tabs=Array.from(form.querySelectorAll('[data-fb-wechat-tab]'));
 var panels=Array.from(form.querySelectorAll('[data-fb-wechat-panel]'));
 var active=form.querySelector('#fb-wechat-active-tab');
 function select(key,focus){
  if(!tabs.some(function(tab){return tab.dataset.fbWechatTab===key;}))return;
  active.value=key;
  var save=form.querySelector('[data-wechat-general-save]');if(save)save.hidden=['subscribe','release','orders','trade','customer'].indexOf(key)>=0;
  tabs.forEach(function(tab){var on=tab.dataset.fbWechatTab===key;tab.classList.toggle('is-active',on);tab.setAttribute('aria-selected',on?'true':'false');tab.tabIndex=on?0:-1;if(on&&focus)tab.focus();});
  panels.forEach(function(panel){panel.hidden=panel.dataset.fbWechatPanel!==key;});
 }
 tabs.forEach(function(tab,index){
  tab.addEventListener('click',function(){select(tab.dataset.fbWechatTab,false);});
  tab.addEventListener('keydown',function(event){var next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;else if(event.key==='ArrowLeft')next=(index+tabs.length-1)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();select(tabs[next].dataset.fbWechatTab,true);});
 });
 form.addEventListener('invalid',function(event){var panel=event.target.closest('[data-fb-wechat-panel]');if(panel)select(panel.dataset.fbWechatPanel,false);},true);
 form.querySelectorAll('[data-fb-count]').forEach(function(input){
  var counter=form.querySelector('[data-fb-count-for="'+input.id+'"]');
  function count(){if(counter)counter.textContent=input.value.length+'/'+input.dataset.fbCount;}
  input.addEventListener('input',count);count();
 });
 function copySelected(input){
  var previous=document.activeElement,start=input.selectionStart,end=input.selectionEnd;
  try{input.focus();input.select();if(!document.execCommand || !document.execCommand('copy'))throw new Error('copy failed');}
  finally{if(typeof input.setSelectionRange==='function' && start!==null && end!==null)input.setSelectionRange(start,end);if(previous && typeof previous.focus==='function')previous.focus();}
 }
 form.querySelectorAll('[data-fb-copy]').forEach(function(button){
  button.addEventListener('click',async function(){
   var input=document.getElementById(button.dataset.fbCopy);
   var status=form.querySelector('[data-fb-copy-status="'+button.dataset.fbCopy+'"]');
   if(!input || input.value===''){if(status)status.textContent='没有可复制的地址';return;}
   button.disabled=true;if(status)status.textContent='正在复制…';
   try{
    try{if(typeof navigator==='undefined' || !navigator.clipboard || typeof navigator.clipboard.writeText!=='function')throw new Error('clipboard unavailable');await navigator.clipboard.writeText(input.value);}
    catch(error){copySelected(input);}
    if(status)status.textContent='已复制';
   }catch(error){if(status)status.textContent='复制失败，请选中地址后手动复制';}
   finally{button.disabled=false;}
  });
 });
 form.addEventListener('submit',function(event){if(active.value==='release'||active.value==='subscribe')event.preventDefault();});
 var uploading=0;
 form.addEventListener('submit',function(event){if(uploading){event.preventDefault();form.querySelectorAll('[data-fb-wechat-qrcode-status]').forEach(function(status){status.textContent='图片正在上传，请完成后保存';});}});
 form.querySelectorAll('[data-fb-wechat-qrcode-field]').forEach(function(field){
  var value=field.querySelector('[name="wechat_qrcode"]'),preview=field.querySelector('[data-fb-wechat-qrcode-preview]');
  var upload=field.querySelector('[data-fb-wechat-qrcode-upload]'),clear=field.querySelector('[data-fb-wechat-qrcode-clear]'),status=field.querySelector('[data-fb-wechat-qrcode-status]');
  if(!value || !preview || !upload || !clear || !status)return;
  function paint(){if(value.value)preview.src=value.value;else preview.removeAttribute('src');preview.hidden=!value.value;clear.hidden=!value.value;}
  async function result(response){var data;try{data=await response.json();}catch(error){throw new Error('上传服务返回异常，请刷新页面后重试');}if(!data.ok)throw new Error(data.message||'上传失败，请重试');return data;}
  upload.addEventListener('click',function(){
   var picker=document.createElement('input');picker.type='file';picker.accept='image/jpeg,image/png,image/webp,image/gif';
   picker.onchange=async function(){
    var file=picker.files&&picker.files[0];if(!file)return;
    if(!/\.(jpe?g|png|webp|gif)$/i.test(file.name)){status.textContent='请选择 JPG、PNG、WebP 或 GIF 图片';return;}
    if(file.size<=0 || file.size>8*1024*1024){status.textContent='图片需大于 0 且小于 8MB';return;}
    uploading++;upload.disabled=true;clear.disabled=true;status.textContent='正在上传…';
    try{
     var endpoint=new URL('plugin.php?id=flipbook:asset&op=upload',location.href),sessionURL=new URL(endpoint.href);sessionURL.searchParams.set('op','session');
     var session=await result(await fetch(sessionURL.href,{credentials:'same-origin',cache:'no-store'}));
     if(typeof session.formhash!=='string' || !session.formhash)throw new Error('管理会话已过期，请刷新后重试');
     var body=new FormData();body.set('file',file);body.set('kind','image');body.set('scope','wechat');body.set('purpose','qrcode');body.set('flipbookmicrosubmit','1');body.set('formhash',session.formhash);
     var response=await result(await fetch(endpoint.href,{method:'POST',body:body,credentials:'same-origin'}));
     if(typeof response.url!=='string' || !response.url)throw new Error('上传未返回图片地址，请重试');
     value.value=response.url;paint();status.textContent='上传成功，保存设置后生效';
    }catch(error){status.textContent=error.message||'上传失败，请重试';}
    finally{uploading--;upload.disabled=false;clear.disabled=false;}
   };
   picker.click();
  });
  clear.addEventListener('click',function(){value.value='';paint();status.textContent='已移除预览，保存设置后生效';});paint();
 });
})();
