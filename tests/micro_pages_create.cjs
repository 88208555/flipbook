const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const { JSDOM } = require(process.env.JSDOM_PATH || '/tmp/flipbook-editor-dom/node_modules/jsdom');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const output = execFileSync('docker', ['exec', '-i', '-e', 'FLIPBOOK_CREATE_FIXTURE=1', 'flipbook-php', 'php'], { input: read('tests/micro_pages_create.php'), encoding: 'utf8' });
const fixture = JSON.parse(output.split('\n').find(line => line.startsWith('FIXTURE ')).slice(8));
console.log(output.split('\n').filter(line => line.startsWith('PASS ')).join('\n'));

const dom = new JSDOM(fixture.list, { url: 'http://localhost:8088/admin.php', runScripts: 'outside-only' });
const w = dom.window, d = w.document, dialog = d.getElementById('fb-micro-create');
w.HTMLDialogElement.prototype.showModal = function () { this.open = true; };
w.HTMLDialogElement.prototype.close = function () { this.open = false; };
w.eval(read('app/source/plugin/flipbook/static/admin/micropage-list.js'));
d.querySelector('[data-fb-micro-create]').click();
assert.equal(dialog.open, true);
assert.equal(d.activeElement, dialog.querySelector('[name=name]'));
const form = dialog.querySelector('form');
assert.equal(form.checkValidity(), false);
form.elements.name.value = '新建品牌页';
assert.equal(form.checkValidity(), true);
const submitted = Object.fromEntries(new w.FormData(form));
assert.equal(submitted.name, '新建品牌页');
assert.equal(submitted.category, 'micro');
assert(submitted.formhash && submitted.flipbookmicrocreatesubmit === '1');
assert(!('key' in submitted) && !('route' in submitted));
dialog.querySelector('[data-fb-micro-cancel]').click();
assert.equal(dialog.open, false);
console.log('PASS creation dialog opens, validates a name and submits only the fixed micro-page category');
dom.window.close();

const pageKeys = require('../miniprogram/utils/micro-keys');
const micro = require('../miniprogram/utils/micro');
const share = require('../miniprogram/utils/share');
const key = fixture.page.key;
assert.equal(pageKeys.normalize(key), key);
assert.equal(pageKeys.normalize('preference'), 'mine');
assert.equal(pageKeys.normalize('not_registered'), 'home');
assert.equal(micro.linkPath('page:' + key), fixture.routes.mini);
assert.equal(micro.linkPath('page:micro_invalid'), '');
const navigated = [];
global.wx = { navigateTo: ({ url }) => navigated.push(url) };
micro.openLink('page:' + key);
assert.deepEqual(navigated, [fixture.routes.mini]);
console.log('PASS generated custom links navigate through the existing registered mini-program route');

const requests = [], exported = { exports: {} };
const wxMock = {
 showShareMenu() {},
 getWindowInfo: () => ({ windowWidth: 375 }),
 createSelectorQuery() { return { select() { return this; }, boundingClientRect() { return this; }, exec(done) { done([{ height: 800 }, null]); } }; },
};
vm.runInNewContext(read('miniprogram/utils/micro-page.js'), {
 module: exported,
 wx: wxMock,
 require(name) {
  return {
   './api': { get: async query => { requests.push(query); return { ok: 1, app: { share: {} }, micropage: fixture.page, products: [], albums: [], member: { uid: 0 } }; } },
   './page': { statusBar: () => 20 }, './micro': micro, './share': share, './micro-keys': pageKeys, './composer-core':require('../miniprogram/utils/composer-core'), './composer-lists':require('../miniprogram/utils/composer-lists'), './composer-events':require('../miniprogram/utils/composer-events'), './login-controller': {data:{}},
  }[name];
 },
});
const definition = exported.exports;
const page = { data: structuredClone(definition.data), setData(value, done) { Object.assign(this.data, value); if (done) done(); } };
for (const [name, fn] of Object.entries(definition)) if (typeof fn === 'function') page[name] = fn.bind(page);
(async () => {
 page.onLoad({ key });
 assert.equal(page.data.key, key);
 await page.sync();
 assert.equal(requests[0].key, key);
 assert.equal(page.data.error, false);
 assert(page.config);
 assert(page.data.nodes.some(node => node.type === 'text'));
 assert.equal(page.onShareAppMessage().path, fixture.routes.mini);
 assert.equal(page.onShareAppMessage().title, fixture.page.name);
 assert.equal(page.onShareTimeline().query, 'key=' + key);
 console.log('PASS custom mini-program page loads its own configuration and retains its route when shared');
 if (process.env.FLIPBOOK_CREATE_OUTPUT) {
  fs.mkdirSync(process.env.FLIPBOOK_CREATE_OUTPUT, { recursive: true });
  fs.writeFileSync(path.join(process.env.FLIPBOOK_CREATE_OUTPUT, 'fixture.json'), JSON.stringify(fixture));
 }
})().catch(error => { console.error(error); process.exitCode = 1; });
