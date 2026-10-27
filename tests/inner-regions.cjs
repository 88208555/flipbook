const assert = require('node:assert/strict');
const schema = require('../app/source/plugin/flipbook/static/config/component-regions.json');
const micro = require('../miniprogram/utils/micro');
const presentation = require('../miniprogram/utils/presentation');

async function run() {
 assert.equal(micro.regionStyle(), '');
 const originalBox=micro.boxStyle({width:45,height:90});
 assert(!/width:90rpx|height:180rpx/.test(originalBox),'content dimensions must not overwrite the container');
 const box=micro.boxStyle({boxWidth:240,boxHeight:180,borderWidth:2,borderColor:'#abcdef',borderStyle:'dashed'});
 for(const rule of ['width:480rpx','height:360rpx','border-width:4rpx','border-color:#abcdef','border-style:dashed'])assert(box.includes(rule),rule);
 assert.equal(micro.boxStyle({boxWidth:0,boxHeight:0,borderWidth:0,borderColor:'',borderStyle:''}),micro.boxStyle({}));
 assert(!micro.prepareNode({type:'view_title',props:{boxWidth:240,lines:[]}},0,{}).titleStyle.includes('width:auto'));
 console.log('PASS component container dimensions and borders render natively without changing content dimensions or old defaults');
 assert.equal(micro.regionStyle({}), '');
 assert.equal(micro.regionStyle({ bg: '#123456' }), 'background:#123456');
 assert.equal(micro.regionStyle({ padLeft: 0 }), 'padding-left:0rpx');
 assert.match(micro.regionStyle({ enabled: 0 }), /display:none!important/);
 assert.match(micro.regionStyle({ width: 0, height: 80 }), /width:auto;height:160rpx/);
 assert.match(micro.regionStyle({ borderWidth: 1 }), /border-width:2rpx;border-style:solid/);
 assert.match(micro.regionStyle({ size: 0, weight: 0 }), /font-size:inherit;font-weight:inherit/);
 console.log('PASS absent inner settings preserve CSS; explicit zero, hiding, dimensions and borders work');

 let regionsChecked = 0;
 for (const [type, keys] of Object.entries(schema)) {
  const regions = {};
  for (const key of Object.keys(keys)) regions[key] = { bg: '#123456', padLeft: 3, size: 18 };
  const node = micro.prepareNode({ type, props: { regions } }, 0, {});
  assert.deepEqual(node.regionStyles, presentation.prepare({ regions }).regionStyles);
  for (const key of Object.keys(keys)) {
   assert.match(node.regionStyles[key], /padding-left:6rpx/);
   assert.match(node.regionStyles[key], /font-size:36rpx/);
   regionsChecked++;
  }
 }
 console.log('PASS generic components and business screens share styles for ' + regionsChecked + ' registered inner regions');

 const html = '<h2>Title</h2><p><a href="https://example.com">Link</a> <code>inline</code></p><ul><li>One</li></ul><pre><code>block</code></pre>';
 const defaultHtml=micro.prepareNode({ type: 'markdown', props: { html } }, 0, {}).html;
 assert.equal(defaultHtml.replace(/ style="[^"]*"/g, ''), html);
 assert.match(defaultHtml, /font-size:18px/);
 const decorated = micro.prepareNode({ type: 'markdown', props: { html, regions: { heading: { size: 20 }, link: { color: '#abcdef' }, list: { padLeft: 10 }, code: { bg: '#123456' } } } }, 0, {}).html;
 assert.match(decorated, /<h2 style="[^"]*font-size:20px">/);
 assert.match(decorated, /href="https:\/\/example.com" style="color:#abcdef"/);
 assert.match(decorated, /<ul style="[^"]*padding-left:10px"><li>/);
 assert.equal((decorated.match(/background:#123456/g) || []).length, 2);
 assert.match(decorated, /<pre style="[^"]*background:#123456"><code>block/);
 console.log('PASS rich text uses generated shared defaults, keeps original content, applies overrides last and avoids doubling list/code spacing');

 const config = { nav: { height: 44, logo: {}, back: {} }, components: [{ type: 'social', props: { source: 'detail' } }, { type: 'tab', props: { tabs: [{ children: [{ type: 'product_info', props: {} }] }] } }] };
 const before = JSON.stringify(config);
 const prepared = micro.prepare(config, {});
 assert.equal(prepared.nodes[0].query._separateProductInfo, true);
 assert.equal(JSON.stringify(config), before);
 config.components.pop();
 assert.equal(micro.prepare(config, {}).nodes[0].query._separateProductInfo, false);
 config.components[0].props.sectionsDetached=1;
 const detached=micro.prepare(config,{}).nodes[0].query;assert.equal(detached._separateProductInfo,true);assert.equal(detached._separateHighlights,true);assert.equal(detached._separateDetails,true);
 console.log('PASS independent product modules and the migration marker suppress duplicate legacy sections without resurrecting removed modules');

 global.getApp = () => ({ globalData: { statusBarHeight: 44 } });
 let navigation;
 global.wx = { getStorageSync: () => '', setStorageSync() {}, navigateTo(value) { navigation = value.url; } };
 const api = require('../miniprogram/utils/api');
 const history = require('../miniprogram/utils/screens/history');
 const calls = [];
 api.get = params => { calls.push(params); return Promise.resolve({ ok: 1, member: { uid: 7 }, history: { counts: { read: 1, share: 0 }, groups: { Today: [{ historyid: 12, title: 'Owned product', url: 'plugin.php?id=flipbook:micro&amp;key=detail&amp;productid=23' }] } } }); };
 const controller = Object.assign({}, history, { data: JSON.parse(JSON.stringify(history.data)), setData(next, done) { Object.assign(this.data, next); if (done) done(); } });
 await controller.load();
 assert.equal(calls[0].key, 'history');
 assert.equal(calls[0].action, 'read');
 assert.equal(controller.data.counts.read, '01');
 assert.equal(controller.data.groups[0].items[0].historyid, 12);
 controller.onOpenRecord({ currentTarget: { dataset: { url: controller.data.groups[0].items[0].url } } });
 assert.equal(navigation, '/pages/micro/index?key=detail&productid=23');
 console.log('PASS native history consumes API groups and opens the recorded product');

 let definition;global.Component=value=>{definition=value;};
 require('../miniprogram/components/fb-render/index');
 api.get=async params=>({ok:1,product:{productid:7,code:'OWNED-7',spec:{contentHtml:'<h2>Product notes</h2>'},groups:{highlight:[{productid:7,mediaid:11,hasimage:1,image:'owned-highlight'},{productid:8,mediaid:12,hasimage:1,image:'foreign-highlight'}],detail:[{productid:7,mediaid:13,hasimage:1,image:'owned-detail'}]}}});
 const productRenderer=Object.assign({},definition.methods,{data:{node:micro.prepareNode({type:'product_highlights',props:{}},0,{productid:7})},setData(next){Object.assign(this.data,next);}});
 await productRenderer.loadProductData();assert.deepEqual(productRenderer.data.productMedia.map(media=>media.mediaid),[11]);assert.equal(productRenderer.data.productCode,'OWNED-7');assert.match(productRenderer.data.productContentHtml,/font-size:18px/);
 productRenderer.data.node=micro.prepareNode({type:'product_details',props:{}},0,{productid:7});await productRenderer.loadProductData();assert.deepEqual(productRenderer.data.productMedia.map(media=>media.mediaid),[13]);
 assert.match(micro.regionStyle({width:40}),/width:80rpx;min-width:0/);
 console.log('PASS independent highlight/detail modules keep product ownership and group boundaries; explicit label width overrides the shared minimum');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
