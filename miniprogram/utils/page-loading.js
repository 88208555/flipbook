const DELAY = 240;
const QUIET = 60;
const CACHE = 'flipbook_loading_logo';
const DEFAULT = {type:'text',text:'FLIPBOOK',image:'',color:'#F2F2F4',bg:'#0B0B0C',invert:false};
let active = null;
const states = new WeakMap();

function cached() {
 try { return Object.assign({}, DEFAULT, wx.getStorageSync(CACHE) || {}); } catch (_) { return Object.assign({}, DEFAULT); }
}
function paint(state, values) {
 if (!state.cancelled) state.owner.setData(values);
}
function clear(state) {
 clearTimeout(state.reveal);clearTimeout(state.idle);
 state.assetTimers.forEach(clearTimeout);state.assetTimers.clear();
}
function finish(state) {
 if (!state || state.cancelled || state.done) return;
 state.done=true;clear(state);paint(state,{pageLoadingVisible:false});
 if (active===state) active=null;
}
function settle(state) {
 if (!state || state.done || state.cancelled || state.pending) return;
 clearTimeout(state.idle);
 const check=()=>{if(!state.done&&!state.cancelled&&!state.pending)state.idle=setTimeout(()=>finish(state),QUIET);};
 if (typeof wx.nextTick==='function') wx.nextTick(check); else check();
}
function enter(owner) {
 leave(owner);
 const state={owner,key:owner.key||owner.data.key||'home',pending:0,done:false,cancelled:false,images:new Set(),assetTimers:new Set()};
 states.set(owner,state);active=state;paint(state,{pageLoadingLogo:cached(),pageLoadingVisible:false});
 state.reveal=setTimeout(()=>{if(!state.done&&!state.cancelled)paint(state,{pageLoadingVisible:true});},DELAY);
 settle(state);
}
function leave(owner) {
 const state=states.get(owner);if(!state)return;
 clear(state);paint(state,{pageLoadingVisible:false});state.cancelled=true;states.delete(owner);
 if(active===state)active=null;
}
function start(state) {
 if(!arguments.length)state=active;
 if(!state||state.done||state.cancelled)return null;
 clearTimeout(state.idle);state.pending++;return state;
}
function end(state) {
 if(!state||state.done||state.cancelled)return;
 state.pending=Math.max(0,state.pending-1);settle(state);
}
function track(owner,promise) {
 const state=start(states.get(owner)||null);
 return Promise.resolve(promise).then(value=>{end(state);return value;},error=>{end(state);throw error;});
}
function image(state,src) {
 if(!src||state.images.has(src)||typeof wx.getImageInfo!=='function')return;
 state.images.add(src);const ticket=start(state);if(!ticket)return;
 let complete=false;
 const done=()=>{if(complete)return;complete=true;clearTimeout(timeout);state.assetTimers.delete(timeout);end(ticket);};
 const timeout=setTimeout(done,10000);state.assetTimers.add(timeout);
 try{wx.getImageInfo({src,success:done,fail:done});}catch(_){done();}
}
function response(state,res) {
 if(!state||state.done||state.cancelled)return;
 const config=res.micropage&&res.micropage.config,nav=config&&config.nav,logo=nav&&nav.logo;
 if(logo) {
  const brand={type:logo.type==='image'&&logo.image?'image':'text',text:logo.text||'FLIPBOOK',image:logo.image||'',color:logo.color||DEFAULT.color,bg:nav.bg||config.bg||DEFAULT.bg,invert:!!logo.invert};
  paint(state,{pageLoadingLogo:brand});
  try{wx.setStorageSync(CACHE,brand);}catch(_){}
 }
 if(state.key==='home') {const album=(res.albums||[])[0];if(album)image(state,album.firstframe||album.cover);}
 else if(state.key==='detail') {
  const id=Number(state.owner.query&&state.owner.query.productid),product=res.product||(res.products||[]).find(p=>Number(p.productid)===id);
  if(product)image(state,product.cover);
 } else if(['catalog','search','favorites'].indexOf(state.key)>=0) {
  (res.products||[]).slice(0,2).forEach(product=>image(state,product.cover));
 }
}
module.exports={enter,leave,start,end,track,response,DEFAULT,DELAY};
