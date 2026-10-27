const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path'),os=require('node:os');
const output=process.env.FLIPBOOK_E2E_OUTPUT_DIR||fs.mkdtempSync(path.join(os.tmpdir(),'flipbook-native-e2e-'));fs.mkdirSync(output,{recursive:true});
const auto=require(process.env.MINIPROGRAM_AUTOMATOR_PATH || 'miniprogram-automator');
const delay=ms=>new Promise(r=>setTimeout(r,ms));
(async()=>{
 const mini=await auto.connect({wsEndpoint:process.env.MINIPROGRAM_WS_ENDPOINT || 'ws://127.0.0.1:9420'});const errors=[];mini.on('exception',e=>errors.push(e));const results=[];
 try{
 for(const [route,key] of [['/pages/discover/index','home'],['/pages/catalog/index','catalog'],['/pages/favorites/index','favorites'],['/pages/mine/index','mine'],['/pages/micro/index?key=search&keyword=FB-SH-01','search'],['/pages/micro/index?key=detail&productid=1','detail'],['/pages/history/index','history'],['/pages/preference/index','mine']]){
  let page=await mini.reLaunch(route);let state;
  for(let i=0;i<50;i++){
   state=await mini.evaluate(()=>{const p=getCurrentPages().slice(-1)[0];const node=(p.data.nodes||[]).find(n=>n.type==='business'||n.type==='product_detail');const comp=node&&p.selectComponent('#micro-'+node.id);const name=node&&({home:'discover',catalog:'catalog',search:'catalog',favorites:'favorites',mine:'mine',detail:'read',history:'history',preference:'preference'})[node.source];const screen=comp&&comp.selectComponent('#business-'+name);return {key:p.data.key,error:p.data.error,firstGap:p.data.firstGap,screen:screen&&screen.data};});
   if(state.key===key&&state.screen&&[2,3,4].includes(state.screen.status))break;await delay(200);
  }
  assert.equal(state.key,key);assert.equal(state.error,false);assert.ok(state.screen);assert.ok(['32rpx','0rpx'].includes(state.firstGap));
  assert.ok([2,3].includes(state.screen.status),JSON.stringify(state));
  if(key==='search')assert.equal(state.screen.products.length,1);
  if(key==='catalog'){
   const sheet=await mini.evaluate(()=>{const p=getCurrentPages().slice(-1)[0];const node=p.data.nodes[0];const s=p.selectComponent('#micro-'+node.id).selectComponent('#business-catalog');s.onOpenSeries();return s.data.showSeries;});assert.equal(sheet,true);
   const geometry=await mini.evaluate(()=>new Promise(resolve=>{const p=getCurrentPages().slice(-1)[0];const s=p.selectComponent('#micro-'+p.data.nodes[0].id).selectComponent('#business-catalog');wx.createSelectorQuery().in(s).select('.fb-series-sheet').boundingClientRect().exec(r=>resolve(r[0]));}));
   assert.ok(geometry.height<350,JSON.stringify(geometry));
   await mini.screenshot({path:path.join(output,'mp-series.png')});
   await mini.evaluate(()=>{const p=getCurrentPages().slice(-1)[0];p.selectComponent('#micro-'+p.data.nodes[0].id).selectComponent('#business-catalog').onCloseSeries();});
  }
  if(key==='detail'){
   assert.equal(state.screen.product.productid,1);
   const media=await mini.evaluate(()=>new Promise(resolve=>{const p=getCurrentPages().slice(-1)[0];const s=p.selectComponent('#micro-'+p.data.nodes[0].id).selectComponent('#business-read');wx.createSelectorQuery().in(s).select('.fb-read-page').boundingClientRect().select('.fb-read-stage').boundingClientRect().select('.fb-fab').boundingClientRect().exec(r=>resolve(r.map(v=>v?{top:v.top,left:v.left,bottom:v.bottom,width:v.width,height:v.height}:null)));}));
   assert.ok(media[0]&&media[1]);assert.ok(Math.abs(media[0].top-media[1].top)<1,JSON.stringify(media));assert.equal(media[2],null,'independent detail must not show the social-only lower-left menu');

   const sound=await mini.evaluate(()=>{const p=getCurrentPages().slice(-1)[0];const s=p.selectComponent('#micro-'+p.data.nodes[0].id).selectComponent('#business-read');const before=s.data.muted;s.onToggleMute();return s.data.muted!==before;});assert.equal(sound,true);
  }
  await mini.screenshot({path:path.join(output,'mp-'+key+'.png')});
  results.push({key,status:state.screen.status||'ready',firstGap:state.firstGap});console.log('PASS',key);
 }
 for(const key of ['about','privacy','agreement']){
  await mini.reLaunch('/pages/micro/index?key='+key);let state;
  for(let i=0;i<50;i++){
   state=await mini.evaluate(()=>{const p=getCurrentPages().slice(-1)[0];return {key:p.data.key,error:p.data.error,loading:p.data.loading,nodes:p.data.nodes,tabs:p.data.tabs,titleContext:p.data.titleContext};});
   if(state.key===key&&!state.loading&&state.nodes.length)break;await delay(200);
  }
  assert.equal(state.key,key);assert.equal(state.error,false);assert.ok(state.nodes.some(node=>node.type==='markdown'));
  assert.equal(state.tabs.length,4);assert.ok(state.tabs.every(tab=>tab.iconStyle.includes('data:image/svg+xml')));assert.equal(typeof state.titleContext,'object');
  await mini.screenshot({path:path.join(output,'mp-'+key+'.png')});
  results.push({key,status:'ready'});console.log('PASS',key);
 }
 assert.deepEqual(errors,[]);console.log(JSON.stringify({results,errors,output},null,2));
 }finally{mini.disconnect();}
})().catch(e=>{console.error(e);process.exitCode=1;});
