(function(){
 'use strict';
 var stage=document.querySelector('.fb-device-stage'),slot=document.querySelector('.fb-device-slot'),frame=document.querySelector('[data-device-frame]');
 if(!stage||!slot||!frame)return;
 function fit(){
  var toolbar=document.querySelector('.fb-device-toolbar'),style=getComputedStyle(stage);
  var padX=parseFloat(style.paddingLeft)+parseFloat(style.paddingRight),padY=parseFloat(style.paddingTop)+parseFloat(style.paddingBottom);
  var embedded=document.body.classList.contains('is-embedded'),availableHeight=Math.max(embedded?0:180,window.innerHeight-toolbar.getBoundingClientRect().height-padY);
  stage.style.height=(window.innerHeight-toolbar.getBoundingClientRect().height)+'px';
  var scale=Math.min(1,Math.max(embedded?0:0.1,(stage.clientWidth-padX)/395),availableHeight/832);
  slot.style.width=(395*scale)+'px';slot.style.height=(832*scale)+'px';slot.style.setProperty('--fb-device-scale',String(scale));
 }
 function clock(){var now=new Date(),label=document.querySelector('[data-device-clock]');label.textContent=now.getHours()+':'+String(now.getMinutes()).padStart(2,'0');}
 function reload(){try{frame.contentWindow.location.reload();}catch(error){frame.src=frame.src;}}
 document.querySelector('[data-device-reload]').addEventListener('click',reload);
 frame.addEventListener('load',function(){try{var url=frame.contentWindow.location.href;if(new URL(url).origin===location.origin){document.querySelector('[data-device-open]').href=url;if(window.parent!==window)window.parent.postMessage({type:'flipbook:preview-url',url:url},location.origin);}}catch(error){/* Cross-origin links continue inside the same real page. */}});
 window.addEventListener('message',function(event){if(event.origin===location.origin&&event.source===window.parent&&event.data&&event.data.type==='flipbook:preview-reload')reload();});
 window.addEventListener('resize',fit,{passive:true});
 if(window.ResizeObserver)new ResizeObserver(fit).observe(document.querySelector('.fb-device-toolbar'));
 clock();fit();
})();
