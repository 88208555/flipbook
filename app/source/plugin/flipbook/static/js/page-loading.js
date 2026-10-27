(function(){
 'use strict';
 var overlay=document.getElementById('fb-page-loading');if(!overlay)return;
 var logo=overlay.querySelector('.fb-page-loading-logo'),timer,serial=0;
 function brand(){
  var current=document.querySelector('.fb-mc-logo'),image=current&&current.querySelector('img'),text=current&&current.textContent.trim(),config=document.getElementById('fb-loading-brand');
  if(config)try{var value=JSON.parse(config.textContent),configured=value.logo||{};text=configured.text;if(configured.type==='image'&&configured.image){image=document.createElement('img');image.src=configured.image;image.alt=text||'';image.style.filter=configured.invert?'invert(1)':'none';}else image=null;overlay.style.color=configured.color||'#F2F2F4';overlay.style.background=value.bg||'#0B0B0C';}catch(_){}
  if(!current&&!config)return;
  logo.textContent=text||(image&&image.alt)||'FLIPBOOK';
  if(image){var copy=image.cloneNode();copy.removeAttribute('style');copy.alt='';copy.style.filter=image.style.filter||getComputedStyle(image).filter;function loaded(){if(copy.naturalWidth)logo.replaceChildren(copy);}copy.addEventListener('load',loaded,{once:true});if(copy.complete)loaded();}
  if(!config&&current){var color=getComputedStyle(image||current.querySelector('span')||current).color,bg=document.querySelector('.fb-mc-nav');overlay.style.color=color;if(bg)overlay.style.background=getComputedStyle(bg).backgroundColor;}
 }
 function show(){serial++;clearTimeout(timer);overlay.hidden=false;overlay.setAttribute('aria-hidden','false');overlay.style.animation='none';void overlay.offsetWidth;overlay.style.animation='';return serial;}
 function hide(id){if(id!==serial)return;clearTimeout(timer);overlay.hidden=true;overlay.setAttribute('aria-hidden','true');}
 function ready(){
  brand();var id=serial,media=Array.from(document.querySelectorAll('#fb-app img,#fb-app video[poster]')).filter(function(node){var r=node.getBoundingClientRect();return r.width>0&&r.height>0&&r.top<innerHeight&&r.bottom>0;}).slice(0,4);
  var pending=media.length;
  function done(){if(--pending<=0)requestAnimationFrame(function(){hide(id);});}
  timer=setTimeout(function(){hide(id);},10000);
  if(!pending){hide(id);return;}
  media.forEach(function(node){if(node.tagName==='IMG'&&node.complete){done();return;}var image=node.tagName==='IMG'?node:new Image(),complete=false;function finish(){if(complete)return;complete=true;image.removeEventListener('load',finish);image.removeEventListener('error',finish);done();}image.addEventListener('load',finish);image.addEventListener('error',finish);if(node.tagName==='VIDEO')image.src=node.poster;});
 }
 document.addEventListener('click',function(event){
  var link=event.target.closest('a[href]');if(!link||event.defaultPrevented||event.button||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey||link.target||link.hasAttribute('download'))return;
  var url=new URL(link.href,location.href);if(url.origin!==location.origin||url.protocol!==location.protocol||url.pathname===location.pathname&&url.search===location.search)return;
  show();timer=setTimeout(function(){hide(serial);},10000);
 });
 window.addEventListener('pageshow',function(event){if(event.persisted)hide(serial);});
 window.addEventListener('pagehide',function(){hide(serial);});
 show();if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});else ready();
})();
