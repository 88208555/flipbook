const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const Core = require('../app/source/plugin/flipbook/static/js/composer-core');
const schema = require('../app/source/plugin/flipbook/static/config/composer.json');
const nativePosts=[];
let component, paused = [], played = [], preview, navigated, emitted = [];
vm.runInNewContext(fs.readFileSync('miniprogram/components/fb-render/index.js', 'utf8'), {
  setTimeout,clearTimeout,
  Component(value) { component = value; },
  require(name) {
    if (name.endsWith('/api')) return {post(module,values){nativePosts.push({module,values});return Promise.resolve({ok:1,favorited:values.do==='add'?1:0});}};
    if (name.endsWith('/composer-core')) return Core;
    if (name.endsWith('/micro')) return {openLink(link) { navigated = link; }};
    return {emit(...args) { emitted.push(args); }, on() { return () => {}; }};
  },
  wx: {createVideoContext(id) { return {pause() { paused.push(id); },play() { played.push(id); }}; }, previewImage(value) { preview = value; }, navigateTo(value) { navigated = value.url; }}
});
const model = {
  data: {current: 0, node: {id: 'gallery', kind: 'gallery', type: 'product_gallery', props: {preview: 1}, items: [{video: 'a.mp4'}, {video: 'b.mp4'}, {image: 'photo.jpg'}]}},
  setData(value) { Object.assign(this.data, value); },
  ...component.methods,
  _channel: 'gallery:1'
};
model.onSlide({detail: {current: 1}});
assert(paused.includes('fc-gallery-0') && !paused.includes('fc-gallery-1'));
console.log('PASS switching slides pauses other videos and preserves the active one');
model.onPreview({currentTarget: {dataset: {index: 2}}});
assert.equal(preview.current, 'photo.jpg');
assert.equal(JSON.stringify(preview.urls), JSON.stringify(['photo.jpg']));
console.log('PASS the native photo viewer receives only the current image group');
model.data.node = {link: 'action:login', context: {uid: 0}};
model.onLink({currentTarget: {dataset: {}}});
assert.equal(navigated, '/pages/login/index');
console.log('PASS a visitor opens the independent login page');
model.data.node = {props: {action: 'open'}, link: '/pages/read/index?albumid=7&page=3', context: {}};
model.onAction();
assert.equal(navigated, '/pages/read/index?albumid=7&page=3');
console.log('PASS reading links keep their saved page position');
const nativeModule = {exports: {}};
vm.runInNewContext(fs.readFileSync('miniprogram/utils/micro.js', 'utf8'), {
  module: nativeModule, wx: {}, require(name) { return name === './action-icons' ? require('../miniprogram/utils/action-icons') : Core; }
});
const Native = nativeModule.exports;
const config = {schemaVersion: 3, schema, typography: schema.defaultTypography, layout: {sideGap: 15}, nav: {back: {style: {}}}, tabbar: {items: []}, components: [
  {id: 'name', type: 'text', props: {binding: 'home.current.name'}},
  {id: 'open', type: 'button', props: {action: 'open', link: 'binding:home.current.link'}}
]};
const context = {home: {current: {name: 'first', link: 'album:1'}}, member: {}, product: {}};
const view = Native.prepare(config, {key: 'home'}, context);
let page, geometryPage;
const pageModule = {exports: {}};
vm.runInNewContext(fs.readFileSync('miniprogram/utils/micro-page.js', 'utf8'), {
  module: pageModule, wx:{getWindowInfo(){return {windowWidth:375};},createSelectorQuery(){let selector;return {select(value){selector=value;return this;},boundingClientRect(){return this;},exec(callback){callback([selector==='.fc-native-footer'?{height:56}:{height:748-52-geometryPage.data.footerHeight}]);}};}}, require(name) { return name === './micro' ? Native : name === './composer-core' ? Core : name === './login-controller' ? {data: {}} : {}; }
});
page = {config, context, query: {key: 'home'}, data: {nodes: view.nodes}, setData(value) { Object.assign(this.data, value); }};
const originalNode = page.data.nodes[0];
pageModule.exports.onHomeChange.call(page, {name: 'second', link: 'album:2'});
assert.equal(page.data.nodes[0], originalNode);
assert.equal(page.data.nodes[0].text, 'second');
assert.equal(page.data.nodes[1].link, '/pages/read/index?albumid=2');
console.log('PASS changing the home slide updates both its title and its destination without replacing nodes');
assert(view.mainStyle.includes('padding-left:30rpx') && view.mainStyle.includes('padding-right:30rpx'));
assert(view.textStyle.includes('font-size:32rpx'));
console.log('PASS the native page uses the shared 15px gutters and body typography');

model.data.node={kind:'gallery',props:{boxHeight:0,aspectRatio:1}};
model.createSelectorQuery=()=>({select(){return this;},boundingClientRect(fn){fn({width:320});return this;},exec(){}});
model.measureGallery({width:100,height:200});assert.equal(model.data.galleryHeight,320);
model.measureGallery({width:200,height:100});assert.equal(model.data.galleryHeight,320);
console.log('PASS native product gallery height follows the configured ratio for both portrait video and square images');

const skuConfig={...config,components:[{id:'native-sku-shape',type:'product_skus',props:{radius:12,radiusTopLeft:0}}]};
const skuView=Native.prepare(skuConfig,{key:'detail'},{product:{slides:[{image:'one.jpg'},{image:'two.jpg'}]}}).nodes[0];
assert(skuView.shapeStyle.includes('border-radius:24rpx')&&skuView.shapeStyle.includes('border-top-left-radius:0rpx'));
assert(skuView.items.every(item=>item.heightStyle==='height:128rpx'));
console.log('PASS native SKU thumbnails share the configured corner radii and retain their common height');

model.data.node={kind:'login',props:{}};
model.onLoginField({currentTarget:{dataset:{field:'username'}},detail:{value:'alice'}});
model.onLoginQuestion({detail:{value:'3'}});
model.onLoginAction({currentTarget:{dataset:{action:'login_wechat'}}});
assert.equal(JSON.stringify(emitted.slice(-3)),JSON.stringify([['login:field',{field:'username',value:'alice'}],['login:field',{field:'questionid',value:3}],['login:action','login_wechat']]));
console.log('PASS the integrated native form dispatches real field, question and direct WeChat actions');
const loginConfig={...config,layout:{bg:'#000000',sideGap:15},components:[{id:'login',type:'login_form',props:{controlHeight:48,controlPadY:0,tabGap:0}}]};
const loginView=Native.prepare(loginConfig,{key:'login'},{login:{account:true,wechat:true,values:{username:'alice',questionid:3}}}).nodes[0];
assert(loginView.login.controlStyle.includes('min-height:96rpx')&&loginView.login.controlStyle.includes('padding-top:0rpx')&&loginView.login.tabStyle.includes('gap:0rpx'));
assert.equal(loginView.login.values.username,'alice');assert(loginView.login.accountStyle.includes('background:#ffffff')&&loginView.login.accountStyle.includes('color:#000000'));assert(loginView.login.wechatStyle.includes('background:transparent'));assert(loginView.login.controlStyle.includes('border-color:#55555E'));
console.log('PASS native login applies the same configured dimensions, original button appearance and retained field values');

const selectedWechat=Native.prepare(loginConfig,{key:'login'},{login:{account:false,wechat:true}}).nodes[0];
assert(selectedWechat.login.wechatStyle.includes('background:#ffffff')&&selectedWechat.login.wechatStyle.includes('color:#000000'));
assert(selectedWechat.login.accountStyle.includes('background:transparent')&&selectedWechat.login.accountStyle.includes('border-color:#55555E'));
console.log('PASS changing the selected login method swaps active and inactive colors in the native shared model');
const configuredLogin={...loginConfig,components:[{id:'login',type:'login_form',props:{activeBg:'#ff6600',activeColor:'#123456',activeBorderColor:'#abcdef',inactiveBg:'#111111',inactiveColor:'#dddddd',inactiveBorderColor:'#444444',controlBorderColor:'#333333'}}]};
for(const account of [false,true]){const state=Native.prepare(configuredLogin,{key:'login'},{login:{account,wechat:true}}).nodes[0].login;const active=account?state.accountStyle:state.wechatStyle,inactive=account?state.wechatStyle:state.accountStyle;assert(active.includes('background:#ff6600')&&active.includes('color:#123456')&&active.includes('border-color:#abcdef'));assert(inactive.includes('background:#111111')&&inactive.includes('color:#dddddd')&&inactive.includes('border-color:#444444'));assert(state.controlStyle.includes('border-color:#333333'));}
console.log('PASS configured state colors apply identically to either selected method and input borders remain independent');

model.data.node={id:'vertical',type:'home_gallery',kind:'gallery',visible:true,props:{playVideo:1,posterOnEnd:1},items:[{video:'one.mp4'},{image:'poster.jpg'},{video:'two.mp4'}]};model.data.current=0;model.data.ended={};
model.onSlide({detail:{current:2}});assert(played.includes('fc-vertical-2')&&paused.includes('fc-vertical-0'));component.pageLifetimes.hide.call(model);assert(paused.includes('fc-vertical-2'));component.pageLifetimes.show.call(model);assert.equal(played.at(-1),'fc-vertical-2');console.log('PASS vertical feed media uses the real mixed-media index, pauses on page hide and resumes the selected video');
const feedView=Native.prepare({...config,components:[{id:'feed',type:'home_gallery',props:{}}]},{key:'home'},{home:{albums:[{video:'one.mp4'},{image:'two.jpg'}]}});assert(feedView.feedLocked&&feedView.nodes[0].vertical);assert(!Native.prepare({...config,components:[{id:'feed',type:'home_gallery',props:{}}]},{key:'mine'},{}).feedLocked);console.log('PASS only the visible home feed locks outer scrolling for native vertical gestures');
model.data.node={props:{action:'page:catalog'},context:{keyword:'衬衫',query:{keyword:'衬衫',catid:3,seriesid:2}}};model._keywordEdited=false;model.onSearch({detail:{value:'外套'}});assert(navigated.includes('catid=3')&&navigated.includes('seriesid=2')&&navigated.includes('keyword=%E5%A4%96%E5%A5%97'));model.onSearchInput({detail:{value:''}});model.onSearch({detail:{}});assert(navigated.endsWith('keyword='));console.log('PASS native search retains active filters and the displayed keyword while an explicit clear submits an empty query');

geometryPage={config:{layout:{topGap:0,bottomGap:0},typography:schema.defaultTypography},data:{footerHeight:0,nodes:feedView.nodes},setData(value,callback){Object.assign(this.data,value);if(callback)callback();}};pageModule.exports.measure.call(geometryPage);assert.equal(geometryPage.data.footerHeight,56);assert.equal(geometryPage.data.nodes[0].props.boxHeight,640);assert(geometryPage.data.nodes[0].style.includes('height:1280rpx'));console.log('PASS fullscreen media is measured after footer space updates so it cannot extend beneath the fixed bottom navigation');

(async()=>{
 const before=emitted.length;model.data.node={kind:'search',props:{action:'page:catalog',searchDelay:5},context:{key:'catalog',keyword:'',query:{catid:3,seriesid:2}}};navigated=null;model.onSearchInput({detail:{value:'衬'}});model.onSearchInput({detail:{value:'衬衫'}});await new Promise(resolve=>setTimeout(resolve,15));const searches=emitted.slice(before).filter(item=>item[0]==='search:query');assert.equal(searches.length,1);assert.equal(searches[0][1].keyword,'衬衫');assert.equal(navigated,null);console.log('PASS native automatic search emits one completed query and keeps the current page instead of navigating to a new instance');
 model.data.node={kind:'select',items:[{href:'/pages/micro/index?key=catalog&seriesid=2'}]};model.onChoiceOpen();assert(model.data.choiceOpen);model.onChoicePick({currentTarget:{dataset:{index:0}}});assert(!model.data.choiceOpen&&navigated.includes('seriesid=2'));console.log('PASS native series choices open and close the sheet and use the selected real item link');
 model.data.node={kind:'actions',props:{},context:{uid:1,productid:999,home:{albumid:7}}};model.data.fabFavorited=false;model.onAction({currentTarget:{dataset:{action:'favorite'}}});await new Promise(resolve=>setTimeout(resolve,0));assert.equal(nativePosts.at(-1).values.targettype,'album');assert.equal(nativePosts.at(-1).values.targetid,7);assert.equal(model.data.fabFavorited,true);model.data.fabMuted=true;model.onFabMute();assert.equal(JSON.stringify(emitted.at(-1)),JSON.stringify(['home:mute',false]));console.log('PASS native floating favorite and mute operate on the active album and the shared home video channel');
 const message=pageModule.exports.onShareAppMessage.call({recordShare(){},key:'home',context:{home:{current:{albumid:7,name:'当前画册',cover:'cover.jpg'}}},data:{}});assert.equal(message.path,'/pages/micro/index?key=detail&albumid=7');assert.equal(message.title,'当前画册');console.log('PASS native home sharing uses the current album title, image and detail destination');
 const queryPage={key:'catalog',_visible:true,query:{key:'catalog',catid:3,seriesid:2},sync(){this.synced=true;}};pageModule.exports.onSearchQuery.call(queryPage,{key:'catalog',keyword:'衬衫'});assert(queryPage.synced&&queryPage.query.keyword==='衬衫'&&queryPage.query.catid===3&&queryPage.query.seriesid===2);queryPage.synced=false;queryPage._visible=false;pageModule.exports.onSearchQuery.call(queryPage,{key:'catalog',keyword:'其他'});assert(!queryPage.synced&&queryPage.query.keyword==='衬衫');console.log('PASS native search updates only the visible matching page and preserves its existing filters');
})().catch(error=>{console.error(error);process.exitCode=1;});
