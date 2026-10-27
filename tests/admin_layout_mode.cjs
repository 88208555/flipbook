// Exercise native menu navigation without the removed layout switch or a saved cookie.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { JSDOM } = require(process.env.JSDOM_PATH || '/tmp/flipbook-editor-dom/node_modules/jsdom');
const root = path.resolve(__dirname, '..');
const sources = ['app/static/js/admincp_frame.js', 'app/data/cache/admincp_frame.js'];
const link = (id, label, url) => `<li><a id="submn_${id}" href="${url}" target="main"><em></em><div>${label}</div></a></li>`;
const group = (id, links) => `<li id="lm_${id}"><a id="leftmn_${id}"><span>${id}</span></a><ul>${links}</ul></li>`;
const html = `<div id="bdcontainer" class="oldlayout"><div id="navcontainer"><nav><ul id="leftmenu">${group('index', link('index', '首页', 'admin.php?action=index'))}${group('flipbook', link('product', '商品管理', 'admin.php?action=plugins&pmod=product') + link('micropage', '微页面', 'admin.php?action=plugins&pmod=micropage'))}</ul></nav></div><div class="ifmcontainer"><div class="mainhd"><div id="navbtn"></div><div id="admincpnav"></div><div id="tabs"></div><ul id="topmenu"><li><button id="header_index">首页</button></li><li><button id="header_flipbook">画册</button></li></ul><div id="frameuinfo"><img></div></div><div id="favbars"><a id="op_back"></a><a id="op_refresh"></a><div id="favbar_list"></div><div id="favbar_mgr"></div></div><iframe id="main" name="main" class="mainframe"></iframe></div></div>`;
for (const source of sources) {
 for (const width of [1816, 1399, 1398, 900]) {
  const dom = new JSDOM(html, { url: 'http://localhost:8088/admin.php', runScripts: 'outside-only' });
  const w = dom.window, d = w.document;
  Object.defineProperty(d.documentElement, 'clientWidth', { value: width });
  Object.defineProperty(d.body, 'clientWidth', { value: width });
  const mainWindow = { location: '' };
  Object.defineProperty(w, 'main', { value: mainWindow, configurable: true });
  w.$ = id => d.getElementById(id);
  w.getcookie = name => { assert.notEqual(name, 'admincp_oldlayout', 'layout must not depend on a saved cookie'); return ''; };
  w.defaultNav = 'flipbook';
  w.defaultTab = 'submn_product';
  w.admincpfilename = 'admin.php';
  w.doane = () => w.event?.preventDefault();
  w.Element.prototype.scrollTo = function () {};
  w.eval(fs.readFileSync(path.join(root, source), 'utf8'));
  assert(w._framemenu, 'native navigation initialises with no cpsetting element');
  assert.equal(d.getElementById('lm_flipbook').className, 'active');
  if (width >= 1399) {
   assert.equal(d.querySelectorAll('#tabs a').length, 0);
   assert(d.getElementById('main').src.endsWith('admin.php?action=plugins&pmod=product'));
   d.getElementById('header_index').click();
   assert.equal(d.getElementById('lm_index').className, 'active');
   assert(mainWindow.location.endsWith('admin.php?action=index'));
   d.getElementById('header_flipbook').click();
   assert.equal(d.getElementById('lm_flipbook').className, 'active');
   d.getElementById('submn_micropage').click();
   assert(d.getElementById('main').src.endsWith('admin.php?action=plugins&pmod=micropage'));
   assert(d.getElementById('admincpnav').textContent.includes('微页面'));
  } else {
   assert(d.getElementById('submn_product_frame'));
   d.getElementById('submn_micropage').click();
   assert(d.getElementById('submn_micropage_frame'));
   assert.equal(d.querySelectorAll('#tabs a').length, 2);
   assert.equal(d.getElementById('submn_micropage').className, 'active');
  }
  console.log(`PASS ${source} at ${width}px: default navigation and album destinations`);
  dom.window.close();
 }
}
