/* Offline contract tests: no WeChat requests, payments, login or database writes. */
'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),vm=require('node:vm');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
let checks=0;
function check(ok,label){assert.ok(ok,label);checks++;console.log('PASS '+label);}
function moduleAt(file,mocks,extra={}){
 const mod={exports:{}};
 const context={module:mod,exports:mod.exports,require:key=>{
  if(Object.prototype.hasOwnProperty.call(mocks,key))return mocks[key];
  throw Error('Unexpected dependency in isolated test: '+key);
 },console,Promise,Date,Math,Number,JSON,Error,encodeURIComponent,decodeURIComponent,...extra};
 vm.runInNewContext(fs.readFileSync(path.join(root,file),'utf8'),context,{filename:file});return mod.exports;
}
const tick=async()=>{for(let i=0;i<4;i++)await new Promise(resolve=>setImmediate(resolve));};
function instance(def){
 const owner={data:JSON.parse(JSON.stringify(def.data||{})),setData(values,done){
  for(const [key,value]of Object.entries(values)){const parts=key.split('.');let node=this.data;for(const part of parts.slice(0,-1))node=node[part];node[parts.at(-1)]=value;}if(done)done();
 }};for(const [key,value]of Object.entries(def))if(typeof value==='function')owner[key]=value.bind(owner);return owner;
}
function pageAt(file,mocks,wx){let definition;moduleAt(file,mocks,{wx,Page:def=>definition=def});return instance(definition);}

(async()=>{
 let calls=[],native=0,cancel=false,serverStatus='pending';
 const wx={login:options=>options.success({code:'isolated_wechat_code'}),requestPayment:options=>{native++;calls.push(['native',Object.keys(options)]);if(cancel)options.fail({errMsg:'requestPayment:fail cancel'});else options.success({});}};
 const params={timeStamp:'1792900000',nonceStr:'test_nonce',package:'prepay_id=fixture',signType:'RSA',paySign:'fixture_signature',extra:'never forwarded'};
 const api={commerce:async(action,values)=>{calls.push([action,values]);return action==='pay'?{ok:1,payment:params}:{ok:1,order:{id:7,status:serverStatus}};}};
 const commerce=moduleAt('miniprogram/utils/commerce.js',{'./api':api},{wx});
 await assert.rejects(commerce.payment({...params,signType:'MD5'}));check(native===0,'invalid payment signature type cannot open native payment');
 await commerce.payment(params);check(native===1&&!calls.at(-1)[1].includes('extra'),'only approved payment fields reach wx.requestPayment');
 calls=[];const pending=await commerce.payOrder(7);check(pending.status==='pending'&&calls[0][0]==='pay'&&calls.at(-1)[0]==='refresh','native payment success does not override authoritative pending status');
 cancel=true;serverStatus='paid';const paid=await commerce.payOrder(7);check(paid.status==='paid','server-confirmed payment survives a native cancellation result');
 serverStatus='pending';calls=[];await assert.rejects(commerce.payOrder(7),/取消/);check(calls.at(-1)[0]==='refresh','cancelled payment still refreshes the original order before reporting its result');

 let requests=[],cookies='',ticket=0,ended=0,failRequest=false,legacyServer=false,stateDenied=false,opaqueReply=null;
 const apiWx={getStorageSync:()=>'',setStorageSync:(_key,value)=>cookies=value,request:options=>{
  requests.push(options);if(failRequest){options.fail({errMsg:'isolated network failure'});return;}
  const data=legacyServer?{ok:1,products:[]}:options.url.includes('commerce_action=state')?(stateDenied?{ok:0,message:'订单服务尚未开放'}:{ok:1,formhash:'fresh_formhash',state:{}}):(opaqueReply||{ok:1,order:{id:7,status:'pending'}});
  options.success({statusCode:200,cookies:['session=fixture; HttpOnly'],data});
 }};
 const apiClient=moduleAt('miniprogram/utils/api.js',{'../config':{API_BASE:'https://album.example.test',PLUGIN_ID:'flipbook',REQUEST_TIMEOUT:5000},'./page-loading':{start:()=>++ticket,end:()=>ended++,response:()=>{}}},{wx:apiWx});
 await apiClient.commerce('create',{requestKey:'fixture_request',address:{name:'测试'}});
 check(requests.length===2&&requests[0].method==='GET'&&requests[1].method==='POST'&&requests[1].data.formhash==='fresh_formhash','order mutations fetch the current session formhash and use POST');
 check(requests[1].header.cookie==='session=fixture'&&typeof requests[1].data.address==='string'&&cookies==='session=fixture','order address is encoded without losing the authenticated session cookie');
 failRequest=true;await assert.rejects(apiClient.commerce('orders',{}));check(ticket===ended,'all successful and failed order requests settle their Logo loading tickets');
 failRequest=false;legacyServer=true;await assert.rejects(apiClient.commerce('orders',{}),/尚未完成配置/);check(ticket===ended,'an older server receives a controlled order configuration message and settles loading');
 legacyServer=false;stateDenied=true;requests=[];await assert.rejects(apiClient.commerce('create',{}),/尚未开放/);check(requests.length===1&&requests[0].method==='GET','a denied order state never sends a checkout mutation');

 stateDenied=false;opaqueReply={ok:1,order:{id:7,title:'/cotton',address:{detail:'/door 8'},snapshot:{name:'/cotton',image:'data/attachment/order.png'}},payment:{...params,nonceStr:'/opaque_nonce',paySign:'/opaque_signature'}};
 const opaque=await apiClient.commerce('pay',{orderid:7});
 check(opaque.payment.paySign==='/opaque_signature'&&opaque.payment.nonceStr==='/opaque_nonce'&&opaque.order.address.detail==='/door 8'&&opaque.order.title==='/cotton'&&opaque.order.snapshot.name==='/cotton','payment signatures and order text are never rewritten as image URLs');
 check(opaque.order.snapshot.image==='https://album.example.test/data/attachment/order.png','relative order snapshot images still resolve to the site');opaqueReply=null;

 const product={productid:1,name:'真实商品',cover:'/cover.png',summary:'商品副标题',price:{value:'399.00',currency:'CNY'},skus:[{skuid:2,available:1,code:'SKU-2'},{skuid:3,available:0}]};
 let guest=true,createCalls=[],redirects=[],hold=false,prepayPending=false;
 const checkoutApi={get:async()=>({ok:1,product,member:{uid:guest?0:7},commerce:{enabled:true,shippingFen:500}}),commerce:async(action,values)=>{
  if(action==='create'){createCalls.push(values);if(hold)return new Promise(()=>{});if(prepayPending)return {ok:1,order:{id:8,status:'pending'},paymentPending:true,message:'支付尚未确认'};return {ok:0,message:'服务端价格已更新'};}
  throw Error('unexpected checkout call');
 }};
 const page={statusBar:()=>20,back:()=>{}},loading={enter:()=>{},leave:()=>{}};
 const checkout=pageAt('miniprogram/pages/checkout/index.js',{'../../utils/api':checkoutApi,'../../utils/commerce':commerce,'../../utils/page':page,'../../utils/page-loading':loading},{...wx,navigateTo:options=>redirects.push(options.url),redirectTo:options=>redirects.push(options.url)});
 checkout.onLoad({productid:'1',skuid:'3'});await checkout.load();
 check(checkout.data.needLogin&&checkout.data.skus.length===1&&checkout.data.skuid===2,'checkout preserves guest login and excludes disabled SKUs');
 checkout.onLogin();check(redirects[0].includes('/pages/login/index?return=')&&decodeURIComponent(redirects[0]).includes('productid=1&skuid=2'),'checkout login returns to the same product and selected SKU');
 guest=false;await checkout.load();checkout.onQuantity({detail:{value:'3'}});const requestKey=checkout.requestKey;await checkout.onPay();await checkout.onPay();
 check(createCalls.length===2&&createCalls.every(row=>row.expectedTotalFen===120200&&row.quantity===3&&row.skuid===2),'checkout quote is integer cents and includes the selected SKU, quantity and shipping');
 check(createCalls.every(row=>row.requestKey===requestKey)&&checkout.data.error.includes('价格已更新')&&redirects.length===1,'failed checkout retries retain idempotency and do not invent an order or redirect');
 prepayPending=true;await checkout.onPay();check(redirects.at(-1)==='/pages/orders/index?orderid=8&notice=verify','uncertain prepay opens the saved order recovery page without repeating native payment');prepayPending=false;
 hold=true;const outstanding=checkout.onPay();await tick();const count=createCalls.length;await checkout.onPay();check(createCalls.length===count&&checkout.data.busy,'repeated clicks cannot submit simultaneous checkout requests');void outstanding;

 const Core=require('../app/source/plugin/flipbook/static/js/composer-core'),schema=require('../app/source/plugin/flipbook/static/config/composer.json'),loginPayload={micropage:{config:{schema,layout:schema.pageDefaults.layout,typography:schema.defaultTypography,nav:schema.pageDefaults.nav,tabbar:{enabled:0,items:[]},components:[{id:'login',type:'login_form',props:{}}]}},composerContext:{}};let authCalls=[],reply={...loginPayload,ok:1,member:{uid:0},login:{wechat:true},commerce:{ordersEnabled:true}};
 const mineApi={get:async()=>reply,auth:async(action,values)=>{authCalls.push([action,values]);if(action==='wechat')return {ok:0,code:'bind_required',ticket:'isolated_bind_ticket',message:'请绑定已有账号'};return {ok:1};}};
 const nav=[];const loginWx={...wx,navigateTo:options=>nav.push(options.url),redirectTo:options=>nav.push(options.url)};
 const mineDef=moduleAt('miniprogram/utils/screens/mine.js',{'../../utils/api':mineApi,'../../utils/page':{...page,pad:v=>String(v||0)},'../../utils/toast':{show:()=>{}}},{wx:loginWx});
 const mine=instance(mineDef);mine.load();await tick();mine.onLogin();check(mine.data.menuRows.some(row=>row.key==='orders')&&nav.at(-1)==='/pages/login/index'&&!('showLogin'in mine.data),'member page retains its order entry and opens the independent login route');
 const loginDef=moduleAt('miniprogram/utils/login-controller.js',{'./api':mineApi,'./composer-core':Core,'./composer-events':{on:()=>()=>{}},'./page':page,'./page-loading':loading,'./micro':{px:v=>v+'px',prepare:()=>({nodes:[]})},'./presentation':{prepare:()=>({loginNavigationText:'#ffffff',loginNavigationBackground:'#17171a'})}},{wx:loginWx,getCurrentPages:()=>[]});
 const login=instance(loginDef);await login.load();check(login.data.loginMethod==='account'&&login.data.wechatEnabled&&login.data.ready,'standalone login initially shows the account form with WeChat available');
 login.onLoginTab({currentTarget:{dataset:{method:'account'}}});await login.load();check(login.data.loginMethod==='account','choosing account login remains selected after capability refresh');
 await login.onLoginTab({currentTarget:{dataset:{method:'wechat'}}});check(authCalls.filter(row=>row[0]==='wechat').length===1,'one WeChat tab selection initiates exactly one authentication request');
 check(login.data.loginMethod==='account'&&login.data.bindingWechat&&login.bindTicket==='isolated_bind_ticket','first WeChat login opens the existing account binding flow');
 login.onAccountOnly();check(!login.bindTicket&&!login.data.bindingWechat,'account-only selection clears the pending WeChat binding');
 login.onLoad({return:'https://external.example/'});check(!login.loginReturn,'external return destinations cannot redirect the independent login page');
 login.loginValues={password:'temporary-test-value'};login.onUnload();check(Object.keys(login.loginValues).length===0&&!login.bindTicket,'leaving the independent login page clears temporary credentials');
 reply={...loginPayload,ok:1,member:{uid:0},login:{wechat:false},commerce:{ordersEnabled:false}};const disabled=instance(loginDef);await disabled.load();disabled.onLoginTab({currentTarget:{dataset:{method:'wechat'}}});
 check(disabled.data.loginMethod==='account'&&!disabled.data.wechatEnabled,'unconfigured WeChat login stays unavailable without a visible configuration warning');

 // The same source checkout supplies offline PHP protocol and configuration checks.
 const {spawnSync}=require('node:child_process');
 for(const [name,installed] of [['commerce.php',null],['commerce_config.php','0'],['commerce_config.php','1']]){
  const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);if(installed!==null)args.push('-e','FB_COMMERCE_INSTALLED='+installed,'-e','FB_COMMERCE_HTML=1');args.push('flipbook-php','php');
  const php=spawnSync('docker',args,{input:fs.readFileSync(path.join(root,'tests',name)),encoding:'utf8',timeout:60000});
  assert.equal(php.status,0,name+': '+(php.stderr||php.error||php.stdout.slice(0,2000)));assert.equal(php.stderr,'');
  if(installed===null)process.stdout.write(php.stdout);else {
   const rendered=JSON.parse(php.stdout);rendered.checks.forEach(label=>console.log('PASS '+(installed==='1'?'installed: ':'uninstalled: ')+label));
   const {JSDOM}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
   const dom=new JSDOM('<form id="cpform">'+Object.values(rendered.html).join('')+'</form>');
   const fields=Array.from(dom.window.document.querySelectorAll('[data-commerce-setting],[data-commerce-file],[data-commerce-clear],[data-commerce-carrier],[data-commerce-tracking]'));
   check(fields.length>0&&fields.every(field=>field.form===null),'separate commerce fields cannot submit or validate the unrelated WeChat general form');dom.window.close();
  }
 }
 const wechat=spawnSync(process.execPath,[path.join(root,'tests/wechat-admin.cjs')],{cwd:root,env:process.env,encoding:'utf8',timeout:60000});process.stdout.write(wechat.stdout||'');process.stderr.write(wechat.stderr||'');assert.equal(wechat.status,0);
 const tabsArgs=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)tabsArgs.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);tabsArgs.push('flipbook-php','php');
 const tabs=spawnSync('docker',tabsArgs,{input:fs.readFileSync(path.join(root,'tests/wechat-tabs.php')),encoding:'utf8',timeout:60000});process.stdout.write(tabs.stdout||'');process.stderr.write(tabs.stderr||'');assert.equal(tabs.status,0);
 console.log('PASS '+checks+' offline native commerce and login checks');
})().catch(error=>{console.error(error);process.exitCode=1;});
