// Runs against the actual authorized WeChat project, without changing business records.
const assert = require('node:assert/strict'), fs = require('node:fs'), path = require('node:path');
const auto = require(process.env.MINIPROGRAM_AUTOMATOR_PATH || 'miniprogram-automator');
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
const output = process.env.FLIPBOOK_E2E_OUTPUT_DIR;
(async () => {
 const mini = await auto.connect({ wsEndpoint: process.env.MINIPROGRAM_WS_ENDPOINT || 'ws://127.0.0.1:9420' });
 const results = [], errors = [];
 mini.on('exception', error => errors.push(error));
 let loggedIn = false, original;
 function pass(name) { results.push(name); console.log('PASS', name); }
 async function until(check, label, attempts = 80) { for (let i = 0; i < attempts; i++) { const value = await check(); if (value) return value; await delay(100); } throw new Error(label + ' timed out'); }
 async function currentScreen(key) {
  const info = await until(() => mini.evaluate(source => {
   const page = getCurrentPages().slice(-1)[0];
   if (!page || page.data.key !== source || page.data.loading) return null;
   const node = (page.data.nodes || []).find(item => item.source === source);
   const name = { home: 'discover', detail: 'read', search: 'catalog' }[source] || source;
   const screen = node && page.selectComponent('#micro-' + node.id).selectComponent('#business-' + name);
   return screen && [2, 3].indexOf(screen.data.status) >= 0 ? { id: node.id, name } : null;
  }, key), key);
  const page = await mini.currentPage(), renderer = await page.$('#micro-' + info.id);
  return { page, component: await renderer.$('#business-' + info.name) };
 }
 async function open(key, query = '') { await mini.reLaunch('/pages/micro/index?key=' + key + query); return currentScreen(key); }
 async function screenshot(name) { if (output) { fs.mkdirSync(output, { recursive: true }); await mini.screenshot({ path: path.join(output, name + '.png') }); } }
 try {
  assert.ok(process.env.FLIPBOOK_TEST_APPID);
  assert.equal(await mini.evaluate(() => wx.getAccountInfoSync().miniProgram.appId), process.env.FLIPBOOK_TEST_APPID);
  original = await mini.evaluate(() => ['flipbook_preference', 'flipbook_reading_progress'].map(key => ({ key, exists: wx.getStorageInfoSync().keys.indexOf(key) >= 0, value: wx.getStorageSync(key) })));
  await mini.evaluate(() => wx.setStorageSync('flipbook_preference', { flip: 'horizontal', gesture: true, thumbnails: true, resume: false, quality: 'standard' }));

  let view = await open('home');
  for (const [index, key] of [[1, 'catalog'], [2, 'favorites'], [3, 'mine'], [0, 'home']]) {
   const tabs = await view.page.$$('.fb-m-tab'); assert.equal(tabs.length, 4);
   await tabs[index].tap(); view = await currentScreen(key);
  }
  pass('all four bottom navigation buttons reach their corresponding pages');

  view = await open('catalog');
  await (await view.component.$('.fb-cat-filter')).tap();
  await until(async () => view.component.data('showSeries'), 'series opens');
  await (await view.component.$('.fb-series-cancel')).tap();
  await until(async () => !(await view.component.data('showSeries')), 'series closes');
  await (await view.component.$('.fb-cat-filter')).tap();
  const options = await until(async () => { const items = await view.component.$$('.fb-series-option'); return items.length > 1 ? items : null; }, 'series options render');
  await options[1].tap();
  await until(async () => (await view.component.data('seriesid')) > 0 && !(await view.component.data('showSeries')), 'series selected');
  pass('series chooser opens, cancels and applies a selection');

  view = await open('search', '&keyword=FB-SH-01');
  assert.equal((await view.component.data('products')).length, 1);
  const input = await view.component.$('.fb-search-input');
  await input.input('FBQA_NO_MATCH_20261025');
  await until(async () => (await view.component.data('keyword')) === 'FBQA_NO_MATCH_20261025', 'search input changes');
  await input.trigger('confirm');
  await until(async () => (await view.component.data('status')) === 3, 'empty search');
  await input.input('FB-SH-01');
  await until(async () => (await view.component.data('keyword')) === 'FB-SH-01', 'search input resets');
  await input.trigger('confirm');
  await until(async () => (await view.component.data('products')).length === 1 && (await view.component.data('status')) === 2, 'search restored');
  const cards = await view.component.$$('.fb-card'); await cards[cards.length - 1].tap();
  view = await currentScreen('detail'); assert.equal((await view.component.data('product')).productid, 1);
  pass('search submit, empty result, retry and product entry work');

  view = await open('detail', '&productid=1&index=0');
  assert.equal((await view.component.data('slides'))[0].type, 'video');
  await until(async () => !!(await view.component.data('ended'))[0], 'initial video ends', 180);
  assert.equal(await view.component.$('#vid0'), null);
  await (await view.page.$('.fb-m-scroll')).scrollTo(0, 350);
  await (await view.component.$('.fb-thumb-item[data-index="1"]')).tap();
  await until(async () => (await view.component.data('current')) === 1, 'cover selected');
  await (await view.component.$('.fb-thumb-item[data-index="0"]')).tap();
  await until(async () => (await view.component.data('current')) === 0 && !(await view.component.data('ended'))[0], 'video recreated');
  await until(async () => view.component.$('#vid0'), 'native video returns');
  await (await view.page.$('.fb-m-scroll')).scrollTo(0, 0);
  await until(async () => !!(await view.component.data('ended'))[0], 'replayed video ends', 180);
  await screenshot('native-video-replayed-and-ended');
  pass('a naturally ended video recreates and plays to completion after switching back');

  view = await open('favorites');
  assert.equal(await view.component.data('logged'), false, 'Use an isolated guest session');
  await (await view.component.$('[data-tab="album"]')).tap();
  await until(async () => (await view.component.data('tab')) === 'album', 'favorite album tab');
  await (await view.component.$('.fb-empty .fb-btn')).tap();
  view = await currentScreen('mine');
  await until(async () => view.component.data('showLogin'), 'login panel');
  await (await view.component.$('.fb-login-close')).tap();
  await until(async () => !(await view.component.data('showLogin')), 'login closes');
  await (await view.component.$('.fb-member')).tap();
  await until(async () => view.component.data('showLogin'), 'login reopens');
  let buttons = await view.component.$$('.fb-login-card button'); await buttons[buttons.length - 1].tap();
  await until(async () => !!(await view.component.data('loginError')) && !(await view.component.data('loginBusy')), 'empty login rejected');
  assert.match(await view.component.data('loginError'), /账号|密码/);
  pass('favorites login entry, login close/open and empty submission validation work');

  if (process.env.FLIPBOOK_TEST_USERNAME && process.env.FLIPBOOK_TEST_PASSWORD) {
   await (await view.component.$('[data-field="username"]')).input(process.env.FLIPBOOK_TEST_USERNAME);
   await (await view.component.$('[data-field="password"]')).input(process.env.FLIPBOOK_TEST_PASSWORD);
   buttons = await view.component.$$('.fb-login-card button'); await buttons[buttons.length - 1].tap();
   await until(async () => (await view.component.data('logged')) && !(await view.component.data('loginBusy')), 'forum login', 180);
   loggedIn = true;
   assert.equal((await view.component.data('member')).name, process.env.FLIPBOOK_TEST_USERNAME);
   await screenshot('native-forum-login');
   pass('forum login returns the current project account in the mini-program');
   await (await view.page.$('.fb-m-scroll')).scrollTo(0, 1200);
   await (await view.component.$('.fb-logout')).tap();
   await until(async () => !(await view.component.data('logged')), 'logout', 180);
   loggedIn = false; pass('logout clears the mini-program session');
  }
  assert.deepEqual(errors, []);
 } finally {
  if (loggedIn) await mini.evaluate(() => {
   const page = getCurrentPages().slice(-1)[0], node = page.data.nodes.find(item => item.source === 'mine');
   const screen = node && page.selectComponent('#micro-' + node.id).selectComponent('#business-mine'); if (screen) screen.onLogout();
  }).catch(() => {});
  await mini.reLaunch('/pages/micro/index?key=home').catch(() => {});
  if (original) await mini.evaluate(items => items.forEach(item => { if (item.exists) wx.setStorageSync(item.key, item.value); else wx.removeStorageSync(item.key); }), original).catch(() => {});
  if (output) { fs.mkdirSync(output, { recursive: true }); fs.writeFileSync(path.join(output, 'native-interactions.json'), JSON.stringify({ results, errors }, null, 2)); }
  mini.disconnect();
 }
})().catch(error => { console.error(error); process.exitCode = 1; });
