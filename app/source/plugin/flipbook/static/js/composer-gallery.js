(function(root){
 'use strict';
 var bindings=new WeakMap();
 function trackFor(event){return event.target&&event.target.closest?event.target.closest('.fc-gallery-track'):null;}
 function consumeClick(event){var binding=bindings.get(trackFor(event));if(!binding||event.detail===0||binding.ignoreClickUntil<Date.now())return false;binding.ignoreClickUntil=0;event.preventDefault();event.stopPropagation();return true;}
 function bind(track){
  if(bindings.has(track))return bindings.get(track);
  var vertical=track.dataset.fcAxis==='vertical',gesture=null,settlement=0,wheelUntil=0,wheelDelta=0;
  function refresh(){
   if(!track.dataset.fcSizing||!root.FBComposer||gesture)return;
   var p=JSON.parse(track.dataset.fcSizing),gallery=track.parentElement,current=index(),slide=track.children[current],media=slide&&slide.querySelector('video,img'),width=parseFloat(root.getComputedStyle(track).width)||track.clientWidth;
   if(Number(p.boxHeight)>0||p.fillViewport){gallery.style.height='';return;}
   var ratio=root.FBComposer.galleryRatio(p,media?{width:media.videoWidth||media.naturalWidth,height:media.videoHeight||media.naturalHeight}:null);
   if(!ratio||!width)return;var height=width/ratio,value=height+'px';if(gallery.style.height===value)return;gallery.style.height=value;if(vertical)track.scrollTop=current*height;
  }
  function size(){var value=root.getComputedStyle(track)[vertical?'height':'width'];return /px$/.test(value)&&parseFloat(value)>0?parseFloat(value):(vertical?track.clientHeight:track.clientWidth)||1;}
  function position(){return vertical?track.scrollTop:track.scrollLeft;}
  function assign(value){track[vertical?'scrollTop':'scrollLeft']=value;}
  function scroll(value,behavior){var target={behavior:behavior};target[vertical?'top':'left']=value;track.scrollTo(target);}
  function index(){return Math.max(0,Math.min(track.children.length-1,Math.round(position()/size())));}
  function settle(value,behavior){
   value=Math.max(0,Math.min(track.children.length-1,Number(value)||0));var target=value*size(),deadline=Date.now()+700;
   if(settlement){root.cancelAnimationFrame(settlement);settlement=0;}
   scroll(target,behavior||(root.matchMedia&&root.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'));
   function finish(){target=value*size();if(Math.abs(position()-target)<1||Date.now()>deadline){assign(target);track.classList.remove('fc-gallery-dragging');settlement=0;return;}settlement=root.requestAnimationFrame(finish);}
   if(root.requestAnimationFrame)settlement=root.requestAnimationFrame(finish);else track.classList.remove('fc-gallery-dragging');
  }
  var binding={ignoreClickUntil:0,isInteracting:function(){return !!gesture||!!settlement;},index:index,go:settle,vertical:vertical,refresh:refresh};bindings.set(track,binding);
  track.addEventListener('scroll',refresh,{passive:true});track.addEventListener('load',refresh,true);track.addEventListener('loadedmetadata',refresh,true);if(root.ResizeObserver){var observer=new root.ResizeObserver(function(){if(track.isConnected)refresh();else observer.disconnect();});observer.observe(track);}else root.addEventListener('resize',refresh);refresh();
  function release(event,cancelled){
   if(!gesture||event.pointerId!==gesture.id)return;var g=gesture;gesture=null;if(!g.dragging)return;
   var length=size(),delta=(g.start-(vertical?event.clientY:event.clientX))/g.scale,next=Math.round(position()/length);
   if(!cancelled&&(Math.abs(delta)>=length*.2||(Date.now()-g.time<300&&Math.abs(delta)>20))&&next===g.index)next+=delta>0?1:-1;
   binding.ignoreClickUntil=Date.now()+400;if(track.hasPointerCapture&&track.hasPointerCapture(g.id))track.releasePointerCapture(g.id);settle(next);
  }
  track.addEventListener('pointerdown',function(event){
   binding.ignoreClickUntil=0;if(event.isPrimary===false||event.button!==0||track.children.length<1||event.target.closest('button,a,input,select,textarea'))return;
   var media=event.target.closest('video');if(media&&media.controls&&event.clientY>media.getBoundingClientRect().bottom-40)return;
   if(settlement){root.cancelAnimationFrame(settlement);settlement=0;scroll(position(),'auto');track.classList.remove('fc-gallery-dragging');}
   var rect=track.getBoundingClientRect(),length=size();gesture={id:event.pointerId,start:vertical?event.clientY:event.clientX,cross:vertical?event.clientX:event.clientY,position:position(),index:index(),scale:(vertical?rect.height:rect.width)/length||1,time:Date.now(),dragging:false};
  },true);
  track.addEventListener('pointermove',function(event){
   if(!gesture||event.pointerId!==gesture.id)return;var delta=(vertical?event.clientY:event.clientX)-gesture.start,cross=(vertical?event.clientX:event.clientY)-gesture.cross;
   if(!gesture.dragging){if(Math.max(Math.abs(delta),Math.abs(cross))<6)return;if(Math.abs(cross)>=Math.abs(delta)){gesture=null;return;}gesture.dragging=true;track.classList.add('fc-gallery-dragging');if(track.setPointerCapture)track.setPointerCapture(event.pointerId);}
   if(event.cancelable)event.preventDefault();assign(Math.max(0,Math.min((track.children.length-1)*size(),gesture.position-delta/gesture.scale)));
  },{capture:true,passive:false});
  track.addEventListener('pointerup',function(event){release(event,false);},true);track.addEventListener('pointercancel',function(event){release(event,true);},true);track.addEventListener('lostpointercapture',function(event){release(event,true);});track.addEventListener('pointerleave',function(){if(gesture&&!gesture.dragging)gesture=null;});
  track.addEventListener('wheel',function(event){if(!vertical||event.ctrlKey||track.children.length<1||Math.abs(event.deltaX)>Math.abs(event.deltaY))return;if(event.cancelable)event.preventDefault();if(gesture||Date.now()<wheelUntil)return;wheelDelta+=event.deltaY*(event.deltaMode===1?16:event.deltaMode===2?size():1);if(Math.abs(wheelDelta)<20)return;settle(index()+(wheelDelta>0?1:-1));wheelDelta=0;wheelUntil=Date.now()+350;},{passive:false});
  track.addEventListener('dragstart',function(event){event.preventDefault();event.stopPropagation();},true);track.addEventListener('click',consumeClick,true);return binding;
 }
 root.FBComposerGallery={bind:bind,bindAll:function(host){host.querySelectorAll('.fc-gallery-track').forEach(function(track){bind(track).refresh();});},consumeClick:consumeClick};
})(window);
