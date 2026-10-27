// Actual WeChat runtime geometry. Requires an existing automator connection; never saves page configuration.
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),{execFileSync}=require('node:child_process');
const auto=require(process.env.MINIPROGRAM_AUTOMATOR_PATH||'miniprogram-automator'),micro=require('../miniprogram/utils/micro');
const output=process.env.FLIPBOOK_SETTINGS_OUTPUT||fs.mkdtempSync('/tmp/flipbook-component-settings-');fs.mkdirSync(output,{recursive:true});
const code=String.raw`<?php chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8088';$_SERVER['REQUEST_URI']='/';require './source/class/class_core.php';C::app()->init();$out=[];foreach(flipbook\front_micro::types() as $type=>$label){$c=flipbook\front_micro::sanitize(['components'=>[['type'=>$type,'props'=>['sectionsDetached'=>1,'text'=>'Geometry fixture','markdown'=>'# Fixture','lines'=>[['text'=>'Title fixture']],'gapLeft'=>17,'gapRight'=>23,'gapTop'=>7,'gapBottom'=>9,'padLeft'=>5,'padRight'=>11,'padTop'=>3,'padBottom'=>4]]]]);$c['components']=[$c['components'][0]];$c['nav']['enabled']=0;$c['nav']['logo']['enabled']=0;$c['nav']['back']['show']=0;$c['tabbar']['enabled']=0;$out[$type]=$c;}echo json_encode($out);`;
const configs=JSON.parse(execFileSync('docker',['exec','-i','flipbook-php','php'],{input:code,encoding:'utf8'}));
const delay=ms=>new Promise(r=>setTimeout(r,ms));
(async()=>{const mini=await auto.connect({wsEndpoint:process.env.MINIPROGRAM_WS_ENDPOINT||'ws://127.0.0.1:9420'}),errors=[],results=[];mini.on('exception',e=>errors.push(e));try{
 const page=await mini.reLaunch('/pages/micro/index?key=detail&productid=1');for(let i=0;i<50;i++){if(await mini.evaluate(()=>!getCurrentPages().slice(-1)[0].data.loading))break;await delay(100);}
 for(const [type,config] of Object.entries(configs)){
  const view=micro.prepare(config,{key:'detail',productid:1,_statusBar:20});
  await page.setData({nav:view.nav,fullScreen:view.fullScreen,nodes:view.nodes.filter(n=>n.type!=='view_title'),flowNodes:view.nodes.filter(n=>n.type!=='view_title'),overlayNodes:view.nodes.filter(n=>n.type==='view_title'),bg:view.bg,gap:'0rpx',firstGap:'0rpx',tabbar:false,businessHeight:700,titleContext:{}});
  let geometry;
  for(let i=0;i<20;i++){
   geometry=await mini.evaluate(()=>new Promise(resolve=>{const p=getCurrentPages().slice(-1)[0],n=(p.data.nodes||[])[0]||(p.data.overlayNodes||[])[0],c=n&&p.selectComponent('#micro-'+n.id);if(!c)return resolve(null);wx.createSelectorQuery().in(c).select('.fb-r-block').boundingClientRect().exec(r=>resolve(r[0]?{rect:{left:r[0].left,right:r[0].right,top:r[0].top,bottom:r[0].bottom,width:r[0].width,height:r[0].height},windowWidth:wx.getWindowInfo().windowWidth,style:c.data.node.style}:null));}));
   if(geometry)break;await delay(50);
  }
  assert(geometry,type+' root exists');const {rect,windowWidth}=geometry,ratio=windowWidth/375;
  for(const [key,value] of Object.entries({'margin-left':17,'margin-right':23,'margin-top':7,'margin-bottom':9,'padding-left':5,'padding-right':11,'padding-top':3,'padding-bottom':4}))assert(geometry.style.includes(key+':'+value*2+'rpx')||(key.startsWith('padding')&&geometry.style.includes('padding:6rpx 22rpx 8rpx 10rpx')),type+' '+key+' '+geometry.style);
  assert(Math.abs(rect.left-17*ratio)<1,type+' left edge '+JSON.stringify(rect));assert(Math.abs(rect.right-(windowWidth-23*ratio))<1,type+' right edge '+JSON.stringify(rect));
  // The native renderer quantizes each rpx margin separately before laying out auto width.
  const availableWidth=windowWidth-Math.floor(17*ratio)-Math.floor(23*ratio);
  assert(Math.abs(rect.width-availableWidth)<1,type+' available width '+JSON.stringify(rect));
  results.push({type,...geometry});console.log('PASS',type);
 }
 assert.deepEqual(errors,[]);fs.writeFileSync(path.join(output,'native-component-settings.json'),JSON.stringify({results,errors},null,2));await mini.screenshot({path:path.join(output,'native-component-settings.png')});
 }finally{await mini.reLaunch('/pages/micro/index?key=detail&productid=1').catch(()=>{});mini.disconnect();}})().catch(error=>{console.error(error);process.exitCode=1;});
