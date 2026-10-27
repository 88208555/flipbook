const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const calls=[];let observeCount=0,disconnectCount=0;const ticks=[];
global.wx={
 nextTick(callback){ticks.push(callback);},
 createVideoContext(id){return {play(){calls.push(['play',id]);},pause(){calls.push(['pause',id]);},seek(time){calls.push(['seek',id,time]);}};},
 createIntersectionObserver(){observeCount++;return {disconnect(){disconnectCount++;},relativeToViewport(){return this;},observe(selector,callback){callback({id:'vid0',intersectionRatio:1});}};},
};
const videos=require('../miniprogram/utils/videos');
const owner={data:{current:0,ended:{}},setData(next,done){Object.assign(this.data,next);calls.push(['render',!!this.data.ended[0]]);if(done)done();}};
const flush=()=>{while(ticks.length)ticks.shift()();};
videos.observe(owner,'.fb-reader-video');
videos.ended(owner,{currentTarget:{id:'vid0',dataset:{index:0}}});
assert.equal(owner.data.ended[0],true);assert.equal(owner._videoEnded.vid0,true);
const before=observeCount;
videos.replay(owner,'vid0',0,'.fb-reader-video');
assert.equal(owner.data.ended[0],true);flush();
assert.equal(owner.data.ended[0],false);assert.equal(owner._videoEnded.vid0,false);
assert.equal(observeCount,before+1);assert.equal(disconnectCount,1);
const seek=calls.findLastIndex(call=>call[0]==='seek');assert.ok(seek>=0);assert.equal(calls[seek+1][0],'play');
assert.ok(calls.slice(0,seek).some(call=>call[0]==='render'&&call[1]===true));
videos.ended(owner,{currentTarget:{id:'vid0',dataset:{index:0}}});
assert.equal(owner.data.ended[0],true);
const endedSeekCount=calls.filter(call=>call[0]==='seek').length;videos.resume(owner);assert.equal(calls.filter(call=>call[0]==='seek').length,endedSeekCount);assert.equal(owner.data.ended[0],true);
console.log('PASS native replay rebuilds before seeking and playing, replaces the observer once, and natural end stays released');

const cancelBefore=calls.filter(call=>call[0]==='seek').length;videos.replay(owner,'vid0',0,'.fb-reader-video');owner.data.current=1;flush();assert.equal(calls.filter(call=>call[0]==='seek').length,cancelBefore);assert.equal(owner.data.ended[0],true);
owner.data.current=0;videos.replay(owner,'vid0',0,'.fb-reader-video');videos.replay(owner,'vid0',0,'.fb-reader-video');const observers=observeCount;flush();assert.equal(observeCount,observers+1);
console.log('PASS stale slide and rapid thumbnail replay callbacks cannot revive an inactive video or create extra observers');

global.getApp=()=>({globalData:{statusBarHeight:20}});wx.getStorageSync=()=>'';wx.setStorageSync=()=>{};
const reader=require('../miniprogram/utils/screens/read');
const readerOwner=Object.assign({},reader,{data:{current:1,slides:[{type:'video'},{type:'cover'}],ended:{0:true}},setData(next,done){Object.assign(this.data,next);if(done)done();}});
readerOwner._videoEnded={vid0:true};readerOwner.onSwiperChange({detail:{current:0}});flush();assert.equal(readerOwner.data.ended[0],false);
videos.ended(readerOwner,{currentTarget:{id:'vid0',dataset:{index:0}}});const thumbSeeks=calls.filter(call=>call[0]==='seek').length;readerOwner.onPickPage({currentTarget:{dataset:{index:0}}});flush();assert.equal(readerOwner.data.ended[0],false);assert.equal(calls.filter(call=>call[0]==='seek').length,thumbSeeks+1);
console.log('PASS native reader swipe-back and current-video thumbnail events both invoke explicit replay');


const source=fs.readFileSync('app/source/plugin/flipbook/static/js/flipbook.js','utf8');
const begin=source.indexOf(" var track=document.querySelector('[data-fb-web-reader-track]');");
const end=source.indexOf(" document.addEventListener('click',function(event){",begin);
const handlers={},thumbHandlers={};let plays=0;
const video={dataset:{finished:'1'},currentTime:6,play(){plays++;return Promise.resolve();}};
const slides=[{querySelector:()=>video,getAttribute:()=> 'Video'},{querySelector:()=>null,getAttribute:()=> 'Cover'}];
const track={children:slides,clientWidth:100,scrollLeft:0,addEventListener(name,fn){handlers[name]=fn;},scrollTo({left}){this.scrollLeft=left;handlers.scroll();}};
const thumbs=[0,1].map(index=>({getAttribute:()=>String(index),classList:{toggle(){}},addEventListener(name,fn){thumbHandlers[index]=fn;}}));
const count={},caption={};
vm.runInNewContext(source.slice(begin,end),{document:{querySelector(selector){return selector==='[data-fb-web-reader-track]'?track:selector==='[data-fb-web-count]'?count:caption;},querySelectorAll(){return thumbs;}}});
assert.equal(plays,0);track.scrollLeft=100;handlers.scroll();track.scrollLeft=0;handlers.scroll();assert.equal(video.dataset.finished,undefined);assert.equal(video.currentTime,0);assert.equal(plays,1);
video.currentTime=3;handlers.scroll();assert.equal(video.currentTime,3);assert.equal(plays,1);
thumbHandlers[0]();assert.equal(video.currentTime,0);assert.equal(plays,2);
video.dataset.finished='1';handlers.scroll();assert.equal(video.dataset.finished,'1');assert.equal(plays,2);
console.log('PASS H5 returning to a video or clicking its current thumbnail replays once; ordinary scroll and natural end do not loop');
