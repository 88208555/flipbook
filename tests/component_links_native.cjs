// Follow configured links through real taps in WeChat, restoring the normal detail page afterwards.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const auto=require(process.env.MINIPROGRAM_AUTOMATOR_PATH||'miniprogram-automator'),micro=require('../miniprogram/utils/micro');
const wait=ms=>new Promise(r=>setTimeout(r,ms));
(async()=>{const mini=await auto.connect({wsEndpoint:process.env.MINIPROGRAM_WS_ENDPOINT||'ws://127.0.0.1:9420'}),results=[],errors=[];mini.on('exception',e=>errors.push(e));try{
 for(const [type,selector] of [['markdown','.fb-markdown'],['hero','.fb-r-stage'],['carousel','.fb-r-stage'],['spacer','.fb-r-block']]){
  const page=await mini.reLaunch('/pages/micro/index?key=about');for(let i=0;i<50;i++){if(await mini.evaluate(()=>!getCurrentPages().slice(-1)[0].data.loading))break;await wait(100);}
  const node=micro.prepareNode({id:'audit_link',type,props:{link:'page:privacy',html:'<p>Tap configured link</p>',height:120,items:[],regions:{}}},0,{key:'about'});
  await page.setData({nodes:[node],flowNodes:[node],overlayNodes:[]});
  const component=await page.$('#micro-audit_link');assert(component,type+' component exists');const content=await component.$(selector);assert(content,type+' content exists');await content.tap();
  let key;for(let i=0;i<50;i++){key=await mini.evaluate(()=>getCurrentPages().slice(-1)[0].data.key);if(key==='privacy')break;await wait(100);}assert.equal(key,'privacy',type+' configured link follows a content tap');results.push({type,target:key});console.log('PASS',type,'content tap opens privacy');
 }
 assert.deepEqual(errors,[]);const output=process.env.FLIPBOOK_SETTINGS_OUTPUT;if(output)fs.writeFileSync(path.join(output,'native-link-runtime.json'),JSON.stringify({results,errors},null,2));
 }finally{await mini.reLaunch('/pages/micro/index?key=detail&productid=1').catch(()=>{});mini.disconnect();}})().catch(e=>{console.error(e);process.exitCode=1;});
