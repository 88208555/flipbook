// Real WeChat regression for removed preferences and restored default SKU media; restores only its two storage keys.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const auto = require(process.env.MINIPROGRAM_AUTOMATOR_PATH || 'miniprogram-automator');
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
const keys = ['flipbook_preference', 'flipbook_reading_progress'];
const output = process.env.FLIPBOOK_E2E_OUTPUT_DIR;

(async () => {
 const mini = await auto.connect({ wsEndpoint: process.env.MINIPROGRAM_WS_ENDPOINT || 'ws://127.0.0.1:9420' });
 const results = [], errors = [];
 let original;
 mini.on('exception', error => errors.push(error));
 async function screen(key, query = '') {
  const page = await mini.reLaunch('/pages/micro/index?key=' + key + query);
  for (let i = 0; i < 80; i++) {
   const id = await mini.evaluate(source => {
    const page = getCurrentPages().slice(-1)[0];
    const node = (page.data.nodes || []).find(item => item.source === source);
    if (!node || page.data.loading) return '';
    const renderer = page.selectComponent('#micro-' + node.id);
    const component = renderer && renderer.selectComponent('#business-' + (source === 'detail' ? 'read' : source));
    return component && component.data.status === 2 ? node.id : '';
   }, key);
   if (id) {
    const renderer = await page.$('#micro-' + id);
    const component = await renderer.$('#business-' + (key === 'detail' ? 'read' : key));
    return { page, component };
   }
   await delay(100);
  }
  throw new Error(key + ' did not become ready');
 }
 function pass(name) { results.push(name); console.log('PASS', name); }
 try {
  const appid = await mini.evaluate(() => wx.getAccountInfoSync().miniProgram.appId);
  assert.ok(process.env.FLIPBOOK_TEST_APPID, 'An explicitly authorized test AppID is required');
  assert.equal(appid, process.env.FLIPBOOK_TEST_APPID);
  original = await mini.evaluate(names => names.map(key => ({ key, exists: wx.getStorageInfoSync().keys.indexOf(key) >= 0, value: wx.getStorageSync(key) })), keys);
  await mini.evaluate(() => wx.setStorageSync('flipbook_preference', { flip: 'vertical', gesture: false, thumbnails: false, resume: false, quality: 'high' }));
  let view = await screen('mine');
  assert.equal(await view.component.$('[data-key="preference"]'), null);
  assert.equal(await view.component.$('[data-fb-member-menu="preference"]'), null);
  pass('member menu does not expose reading preference controls');
  await mini.reLaunch('/pages/preference/index');
  let key;
  for (let i = 0; i < 80; i++) {
   key = await mini.evaluate(() => getCurrentPages().slice(-1)[0].data.key);
   if (key === 'mine') break;
   await delay(100);
  }
  assert.equal(key, 'mine');
  pass('old reading preference route returns to the member page');

  view = await screen('detail', '&productid=1&index=0');
  const swiper = await view.component.$('.fb-stage-swiper');
  assert.equal(await swiper.property('vertical'), false);
  assert.ok(await view.component.$('.fb-thumbs'), 'default thumbnails must remain visible despite the saved off value');
  assert.equal(await view.component.$('.fb-reader-gesture-shield'), null);
  const images = await view.component.data('skuImages');
  assert.ok(images.some(image => image.skuid > 0 && image.image), 'existing SKU images must be present');
  pass('saved legacy off values cannot hide existing SKU images');
  const picked = images.length - 1;
  await (await view.page.$('.fb-m-scroll')).scrollTo(0, 350);
  await (await view.component.$('[data-index="' + picked + '"].fb-thumb-item')).tap();
  for (let i = 0; i < 20 && (await view.component.data('current')) !== picked; i++) await delay(50);
  assert.equal(await view.component.data('current'), picked);
  view = await screen('detail', '&productid=1');
  assert.equal(await view.component.data('current'), picked);
  pass('default automatic resume preserves the selected SKU media');

  view = await screen('detail', '&productid=1&index=1');
  const explicitSwiper = await view.component.$('.fb-stage-swiper');
  assert.equal(await explicitSwiper.property('vertical'), false, 'legacy direction must not override default native swiping');
  assert.ok(await view.component.$('.fb-thumbs'), 'default SKU thumbnails remain visible with an explicit index');
  assert.equal(await view.component.data('current'), 1, 'an explicit index takes priority over saved progress');
  assert.equal(await view.component.$('.fb-reader-gesture-shield'), null, 'legacy disabled gesture must not block default swiping');
  pass('removed legacy controls no longer alter the default reader');

  assert.deepEqual(errors, []);
 } finally {
  if (output) { fs.mkdirSync(output, { recursive: true }); fs.writeFileSync(path.join(output, 'native-reading-preference.json'), JSON.stringify({ results, errors }, null, 2)); }
  await mini.reLaunch('/pages/micro/index?key=detail&productid=1').catch(() => {});
  if (original) await mini.evaluate(items => items.forEach(item => { if (item.exists) wx.setStorageSync(item.key, item.value); else wx.removeStorageSync(item.key); }), original).catch(() => {});
  mini.disconnect();
 }
})().catch(error => { console.error(error); process.exitCode = 1; });
