// Run with JSDOM_PATH pointing at the existing jsdom installation; no real share or album save occurs.
const fs=require('node:fs'),path=require('node:path'),vm=require('node:vm'),assert=require('node:assert/strict'),{execFileSync}=require('node:child_process');
const {JSDOM}=require(process.env.JSDOM_PATH||'jsdom');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
const file=name=>fs.readFileSync(path.join(root,name),'utf8');
const dockerArgs=['exec','-i','-e','FLIPBOOK_SHARE_JSON=1'];if(process.env.FLIPBOOK_PLUGIN_ROOT)dockerArgs.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);
const fixture=JSON.parse(execFileSync('docker',[...dockerArgs,'flipbook-php','php'],{input:file('tests/product_share.php'),encoding:'utf8'}));
const check=(condition,message)=>{assert.ok(condition,message);console.log('PASS '+message);};
fixture.checks.forEach(message=>console.log('PASS '+message));
const editor=new JSDOM('<div data-product-heading-actions></div>'+fixture.html,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only'}),w=editor.window;
w.eval(file('app/source/plugin/flipbook/static/admin/product.js'));
const form=w.document.querySelector('form'),tabs=[...form.querySelectorAll('[data-panel]')];tabs.at(-1).click();
check(tabs.at(-1).dataset.panel==='share'&&form.querySelector('[data-product-panel=basic]').hidden&&!form.querySelector('[data-product-panel=share]').hidden,'share tab opens exclusively without altering other product panels');
for(const [name,limit] of Object.entries({share_title:10,share_subtitle:20,share_description:200})){
 const input=form.elements[name],unrelated=form.querySelector('.fb-media-preview');input.focus();input.value='😀'.repeat(limit+1);input.dispatchEvent(new w.InputEvent('input',{bubbles:true}));
 check(Array.from(input.value).length===limit&&form.querySelector('[data-share-count='+name+']').textContent===limit+' / '+limit,'editor counts and limits '+name+' by Unicode code points');
 check(w.document.activeElement===input&&form.querySelector('.fb-media-preview')===unrelated,'editing '+name+' preserves focus and unrelated media nodes');
}
const composing=form.elements.share_title;composing.value='中'.repeat(11);composing.dispatchEvent(new w.InputEvent('input',{bubbles:true,isComposing:true}));
check(composing.value.length===11,'Chinese composition is left intact until it finishes');composing.dispatchEvent(new w.CompositionEvent('compositionend',{bubbles:true}));
check(composing.value.length===10,'composition completion applies the character cap');
const toolbarCSS=file('app/source/plugin/flipbook/static/admin/product.css');check(/\.fb-product-toolbar\{[^}]*padding-bottom:12px/.test(toolbarCSS),'product tabs have 12px of space above their divider');editor.window.close();
const share=require(path.join(root,'miniprogram/utils/share.js')),product=fixture.product,settings={bg:'#fff',color:'#111',radius:20,showPrice:true,qrChannel:'h5',qrHint:'扫码查看商品',titleTemplate:'错误的旧模板',descriptionTemplate:'{summary}'};
product.price={value:'399.00'};
const native=share.message(product,settings);check(native.title===product.share.title&&native.imageUrl===product.share.image&&native.path.endsWith('productid='+product.productid),'native sharing uses the effective current product title, first image and existing detail route');
check(share.content({name:'名称',summary:'副标题',description:'商品说明',cover:'/first.jpg'}).description==='商品说明','clients retain the correct fallback for older product payloads');
function context(){return {calls:[],size:23,save(){},restore(){},beginPath(){},moveTo(){},lineTo(){},quadraticCurveTo(){},closePath(){},clip(){},fill(){},setFillStyle(){},setFontSize(size){this.size=size;},measureText(text){return {width:Array.from(text).length*this.size};},fillRect(...args){this.calls.push(['rect',...args]);},fillText(...args){this.calls.push(['text',...args]);},drawImage(...args){this.calls.push(['image',...args]);},draw(_,done){done();},set font(value){this.size=parseFloat(value);}};}
const tick=()=>new Promise(resolve=>setTimeout(resolve,0));
(async()=>{
 const web=new JSDOM('<main class="fb-web-reader" data-fb-product="'+product.productid+'"><button data-fb-share>分享</button></main>',{url:'http://127.0.0.1:8088/plugin.php',runScripts:'outside-only'}),v=web.window;let webShare,webCtx= context(),canvas,download;
 v.fetch=async()=>({json:async()=>({ok:1,product,app:{share:settings}})});v.Image=class{constructor(){this.width=1000;this.height=1500;}set src(value){this.url=value;queueMicrotask(()=>this.onload());}};
 v.HTMLCanvasElement.prototype.getContext=function(){canvas=this;return webCtx;};v.HTMLCanvasElement.prototype.toDataURL=()=> 'data:image/png;base64,test';v.HTMLAnchorElement.prototype.click=function(){download=this.download;};Object.defineProperty(v.navigator,'share',{value:async data=>{webShare=data;}});
 v.eval(file('app/source/plugin/flipbook/static/js/share-product.js'));v.document.querySelector('[data-fb-share]').click();await tick();
 const q=selector=>v.document.querySelector(selector);
 check(q('[data-share-title]').textContent===product.share.title&&q('[data-share-subtitle]').textContent===product.share.subtitle&&q('[data-share-description]').textContent===product.share.description,'H5 displays effective title, subtitle and full 商品说明 instead of the legacy summary template');
 check(q('[data-share-image]').getAttribute('src')===product.share.image,'H5 automatically displays the current product first image');
 await q('[data-share-friend]').onclick();check(webShare.title===product.share.title&&webShare.text===product.share.description,'H5 native share payload uses the same product configuration');
 q('[data-share-qr]').onload();await q('[data-share-save]').onclick();
 const webTexts=webCtx.calls.filter(call=>call[0]==='text'),descLines=webTexts.filter(call=>call[2]===40&&call[1]&&product.share.description.includes(call[1])),desc=descLines.map(call=>call[1]).join('');
 check(desc===product.share.description&&canvas.height>1050&&download==='商品海报-'+product.productid+'.png','H5 poster grows to contain all 200 description characters');
 const titleLine=webTexts.find(call=>call[2]===40&&call[1].endsWith('…'));check(titleLine&&Array.from(titleLine[1]).length*30<=450,'poster heading uses measured single-line ellipsis');const photo=webCtx.calls.find(call=>call[0]==='image'),price=webTexts.find(call=>call[1]==='¥399.00'),hint=webTexts.find(call=>call[1]==='扫码查看商品');check(photo[4]===canvas.width-photo[2]*2&&photo[5]/photo[4]===1.5,'poster image fills its width at its natural aspect ratio');check(price[2]>photo[2]+photo[4]/2&&price[3]<photo[3]+photo[5],'price stays in the lower-right photo area');check(descLines.at(-1)[3]===hint[3],'description and QR hint share their bottom baseline');check(titleLine[3]-30-photo[3]-photo[5]>=24,'poster title retains space above it');
 let definition,miniCtx=context(),exportOptions,savedPath;const miniImages=[];
 const wx={getImageInfo({src,success}){miniImages.push(src);success({path:src,width:1000,height:1500});},createCanvasContext(){return miniCtx;},canvasToTempFilePath(options){exportOptions=options;options.success({tempFilePath:'/mock-poster.png'});},saveImageToPhotosAlbum({filePath,success}){savedPath=filePath;success();},showToast(){}};
 vm.runInNewContext(file('miniprogram/components/fb-share/index.js'),{Component:value=>definition=value,wx,require:name=>name.endsWith('/api')?{get:async()=>({ok:1,product,app:{share:settings}})}:name.endsWith('/share')?share:{API_BASE:'http://127.0.0.1:8088'}});
 const instance={properties:{productid:product.productid},data:{...definition.data},setData(data,done){Object.assign(this.data,data);if(done)done();},triggerEvent(){}};for(const [name,method] of Object.entries(definition.methods))instance[name]=method.bind(instance);
 await instance.load();check(instance.data.title===product.share.title&&instance.data.subtitle===product.share.subtitle&&instance.data.description===product.share.description&&instance.data.imageUrl===product.share.image,'mini-program card uses the same effective product content');instance.onQrLoaded();const saving=instance.onSave();instance.setData({title:'错误切换标题',subtitle:'错误切换副标题',description:'错误切换描述',product:{price:{value:'1.00'}}});await saving;
 check(exportOptions.height===canvas.height&&exportOptions.destHeight===canvas.height&&savedPath==='/mock-poster.png','mini-program poster exports the complete adaptive canvas height');
 check(JSON.stringify(miniCtx.calls.filter(call=>call[0]==='text'))===JSON.stringify(webTexts)&&miniImages[0]===product.share.image,'H5 and mini-program posters share identical content even if the selected product changes during generation');
 const webCSS=file('app/source/plugin/flipbook/static/css/micro-web.css'),miniCSS=file('miniprogram/components/fb-share/index.wxss');
 check(webCSS.includes('[data-share-subtitle]')&&webCSS.includes('text-overflow:ellipsis')&&miniCSS.includes('.fb-share-product-subtitle')&&miniCSS.includes('text-overflow:ellipsis'),'both share cards retain one-line title/subtitle overflow styles');
 web.window.close();
})().catch(error=>{console.error(error);process.exitCode=1;});
