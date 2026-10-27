const assert=require('node:assert/strict');
let component,navigation=[];
global.getApp=()=>({globalData:{statusBarHeight:20}});
global.wx={getStorageSync:()=>'',navigateTo:value=>navigation.push(value.url)};
global.Component=value=>{component=value;};require('../miniprogram/components/fb-render/index');
for(const type of ['hero','carousel','image','text','video','markdown','divider','spacer','product_info','product_highlights','product_details','view_title']){
 const owner={data:{node:{type,link:'page:about'}}};navigation=[];component.methods.onBlockLink.call(owner,{target:{id:'inner-content'},currentTarget:{id:'block'}});assert.deepEqual(navigation,['/pages/micro/index?key=about'],type);console.log('PASS '+type+' content clicks use the configured component link');
}
for(const type of ['business','product_detail','tab']){
 const owner={data:{node:{type,link:'page:about'}}};navigation=[];component.methods.onBlockLink.call(owner,{target:{id:'inner-action'},currentTarget:{id:'block'}});assert.deepEqual(navigation,[],type+' preserves inner controls');component.methods.onBlockLink.call(owner,{target:{id:'block'},currentTarget:{id:'block'}});assert.deepEqual(navigation,['/pages/micro/index?key=about']);console.log('PASS '+type+' inner actions keep precedence over the outer link');
}
navigation=[];component.methods.onLink.call({data:{node:{link:'page:privacy'}}},{currentTarget:{dataset:{link:'page:about'}}});assert.deepEqual(navigation,['/pages/micro/index?key=about']);navigation=[];component.methods.onLink.call({data:{node:{link:'page:privacy'}}},{currentTarget:{dataset:{link:''}}});assert.deepEqual(navigation,['/pages/micro/index?key=privacy']);console.log('PASS item-specific links take precedence and unlinked content inherits the component destination');
