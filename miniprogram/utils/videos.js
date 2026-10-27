/** One state transition per native video; never seek a completed native player. */
function current(owner,id){const match=String(id).match(/(\d+)$/);return !match||Number(match[1])===Number(owner.data.current||0);}
function command(owner,id,playing){
 owner._videoCommands=owner._videoCommands||{};
 if(owner._videoCommands[id]===playing)return;
 owner._videoCommands[id]=playing;
 const context=wx.createVideoContext(id,owner);if(playing)context.play();else context.pause();
}
function pauseAll(owner){owner._videosHidden=true;Object.keys(owner._videoVisibility||{}).forEach(id=>command(owner,id,false));}
function resume(owner){owner._videosHidden=false;Object.keys(owner._videoVisibility||{}).forEach(id=>command(owner,id,!!owner._videoVisibility[id]&&!owner._videoEnded?.[id]&&current(owner,id)));}
function observe(owner,selector){
 if(owner._videoObserver)owner._videoObserver.disconnect();
 owner._videoVisibility={};owner._videoEnded=owner._videoEnded||{};
 owner._videoObserver=wx.createIntersectionObserver(owner,{observeAll:true,thresholds:[0,.6,1]});
 owner._videoObserver.relativeToViewport({top:0,bottom:0}).observe(selector,res=>{
  if(!res.id)return;owner._videoVisibility[res.id]=res.intersectionRatio>=.6;
  command(owner,res.id,!owner._videosHidden&&owner._videoVisibility[res.id]&&!owner._videoEnded[res.id]&&current(owner,res.id));
 });
}
function ended(owner,event){
 const id=event.currentTarget.id,index=Number(event.currentTarget.dataset.index)||0;
 owner._videoEnded=owner._videoEnded||{};if(owner._videoEnded[id])return;
 owner._videoEnded[id]=true;command(owner,id,false);
 // Replace the native player with its first-frame image to release its touch surface.
 const values=Object.assign({},owner.data.ended||{});values[index]=true;owner.setData({ended:values});
}
/** Replay only after the completed native surface has been removed and rebuilt. */
function replay(owner,id,index,selector){
 const token=(owner._videoReplayToken||0)+1;owner._videoReplayToken=token;
 const active=()=>owner._videoReplayToken===token&&!owner._videosHidden&&current(owner,id);
 const tick=callback=>typeof wx.nextTick==='function'?wx.nextTick(callback):callback();
 owner._videoEnded=owner._videoEnded||{};owner._videoEnded[id]=true;command(owner,id,false);
 const removed=Object.assign({},owner.data.ended||{});removed[index]=true;
 owner.setData({ended:removed},()=>tick(()=>{
  if(!active())return;
  owner._videoEnded[id]=false;delete (owner._videoCommands||{})[id];
  const restored=Object.assign({},owner.data.ended||{});restored[index]=false;
  owner.setData({ended:restored},()=>tick(()=>{
   if(!active())return;
   wx.createVideoContext(id,owner).seek(0);
   command(owner,id,true);
   observe(owner,selector);
  }));
 }));
}
function detach(owner){pauseAll(owner);if(owner._videoObserver)owner._videoObserver.disconnect();owner._videoObserver=null;}
module.exports={observe,pauseAll,resume,ended,replay,detach};
