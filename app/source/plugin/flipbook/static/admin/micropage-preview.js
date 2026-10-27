(function(){
 'use strict';
 var dialog,frame,openLink,trigger,moving;
 function position(left,top){dialog.style.left=Math.max(0,Math.min(window.innerWidth-dialog.offsetWidth,left))+'px';dialog.style.top=Math.max(0,Math.min(window.innerHeight-dialog.offsetHeight,top))+'px';dialog.style.right='auto';}
 function fit(){
  if(!dialog||!dialog.open)return;
  try{var phone=frame.contentDocument.querySelector('.fb-device-phone');if(!phone)return;var style=frame.contentWindow.getComputedStyle(phone),width=parseFloat(style.width),height=parseFloat(style.height),chrome=dialog.querySelector('nav').getBoundingClientRect().height+dialog.querySelector('.fb-micro-preview-grip').getBoundingClientRect().height,scale=Math.max(0,Math.min(1,(window.innerWidth-32)/width,(window.innerHeight-32-chrome)/height));dialog.style.width=width*scale+'px';dialog.style.height=height*scale+chrome+'px';if(dialog.style.left){var rect=dialog.getBoundingClientRect();position(rect.left,rect.top);}}catch(_){}
 }
 function create(){
  dialog=document.createElement('dialog');dialog.className='fb-micro-preview';dialog.setAttribute('aria-label','手机交互预览');dialog.setAttribute('aria-modal','false');dialog.setAttribute('data-fb-controls','local');
  var grip=document.createElement('div');grip.className='fb-micro-preview-grip';grip.setAttribute('aria-label','按住拖动预览');grip.title='按住拖动预览位置';dialog.appendChild(grip);
  frame=document.createElement('iframe');frame.title='手机交互预览';frame.className='fb-micro-preview-frame';frame.addEventListener('load',fit);dialog.appendChild(frame);
  var actions=document.createElement('nav');actions.className='fb-micro-preview-actions';actions.setAttribute('aria-label','预览操作');
  var reload=document.createElement('button');reload.type='button';reload.className='fb-micro-preview-button';reload.textContent='刷新';reload.addEventListener('click',function(){frame.contentWindow.postMessage({type:'flipbook:preview-reload'},location.origin);});
  openLink=document.createElement('a');openLink.className='fb-micro-preview-button';openLink.textContent='新窗口';openLink.target='_blank';openLink.rel='noopener';
  var close=document.createElement('button');close.type='button';close.className='fb-micro-preview-button';close.textContent='关闭';close.addEventListener('click',function(){dialog.close();});
  actions.appendChild(reload);actions.appendChild(openLink);actions.appendChild(close);dialog.appendChild(actions);document.body.appendChild(dialog);
  dialog.addEventListener('close',function(){if(moving)stop({pointerId:moving.id});frame.src='about:blank';if(trigger)trigger.focus();});
  dialog.addEventListener('pointerdown',function(event){if(event.button!==0||event.target.closest('a,button,iframe'))return;var rect=dialog.getBoundingClientRect();moving={id:event.pointerId,x:event.clientX,y:event.clientY,left:rect.left,top:rect.top};dialog.setAttribute('data-moving','');dialog.setPointerCapture(event.pointerId);event.preventDefault();});
  dialog.addEventListener('pointermove',function(event){if(moving&&moving.id===event.pointerId)position(moving.left+event.clientX-moving.x,moving.top+event.clientY-moving.y);});
  function stop(event){if(!moving||event.pointerId!==moving.id)return;moving=null;dialog.removeAttribute('data-moving');if(dialog.hasPointerCapture(event.pointerId))dialog.releasePointerCapture(event.pointerId);}
  dialog.addEventListener('pointerup',stop);dialog.addEventListener('pointercancel',stop);dialog.addEventListener('lostpointercapture',stop);
 }
 document.addEventListener('click',function(event){
  var button=event.target.closest('[data-fb-phone-preview]');if(!button)return;
  var url=new URL(button.href,location.href);if(url.origin!==location.origin||url.searchParams.get('id')!=='flipbook:micro')return;event.preventDefault();
  if(!dialog)create();trigger=button;var full=new URL(url.href);full.searchParams.delete('device_preview');full.searchParams.delete('embedded_preview');openLink.href=full.href;
  url.searchParams.set('device_preview','1');url.searchParams.set('embedded_preview','1');frame.src=url.href;if(!dialog.open)dialog.show();
 });
 window.addEventListener('message',function(event){
  if(!frame||event.source!==frame.contentWindow||event.origin!==location.origin||!event.data||event.data.type!=='flipbook:preview-url')return;
  try{var url=new URL(event.data.url);if(url.origin===location.origin&&/^flipbook:/.test(url.searchParams.get('id')||''))openLink.href=url.href;}catch(_){}
 });
 window.addEventListener('resize',fit);
 window.addEventListener('keydown',function(event){if(dialog&&dialog.open&&event.key==='Escape')dialog.close();});
})();
