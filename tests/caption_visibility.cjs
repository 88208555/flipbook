const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { JSDOM } = require(process.env.JSDOM_PATH || '/tmp/flipbook-editor-dom/node_modules/jsdom');
const presentation = require('../miniprogram/utils/presentation');
const root = path.join(__dirname, '..');
const evaluate = (value, data) => vm.runInNewContext(value.replace(/^\{\{|\}\}$/g, ''), data);
const visible = (node, data, found = []) => {
 if (node.hasAttribute('wx:if') && !evaluate(node.getAttribute('wx:if'), data)) return found;
 const style = (node.getAttribute('style') || '').replace(/\{\{(.*?)\}\}/g, (_, expression) => vm.runInNewContext(expression, data) || '');
 if (/display\s*:\s*none/.test(style)) return found;
 if (node.hasAttribute('data-fb-region')) found.push(node.getAttribute('data-fb-region'));
 if (node.classList.contains('fb-price')) found.push('price');
 for (const child of node.children) visible(child, data, found);
 return found;
};

for (const screen of ['discover', 'read']) {
 const source = fs.readFileSync(path.join(root, 'miniprogram/components/fb-screen-' + screen + '/index.wxml'), 'utf8');
 const dom = new JSDOM(source);const captionNode = dom.window.document.querySelector('[data-fb-region="caption"]');assert.ok(captionNode);
 const data = { item:{code:'OWNED',name:'Name',subtitle:'Summary',overlayLines:['Line']},product:{code:'OWNED',name:'Name',summary:'Summary'},album:{},price:{value:'399',original:'599',currency:'CNY'} };
 for (let mask=0;mask<16;mask++) {
  const options={caption:{enabled:mask&1,showTitle:(mask>>1)&1,showSubtitle:(mask>>2)&1,showCode:(mask>>3)&1,showExtra:1}};
  const prepared=presentation.prepare(options);const found=visible(captionNode,Object.assign({},data,prepared));
  assert.equal(found.includes('caption'),!!options.caption.enabled);
  for(const [flag,key] of [['showTitle','title'],['showSubtitle','subtitle'],['showCode','code']])assert.equal(found.includes(key),!!(options.caption.enabled&&options.caption[flag]),screen+' '+mask+' '+key);
  if(screen==='read')assert.equal(found.includes('price'),!!options.caption.enabled);
 }
 const hidden=presentation.prepare({caption:{enabled:false}});assert.deepEqual(visible(captionNode,Object.assign({},data,hidden)),[]);
 const defaults=presentation.prepare({});assert.ok(visible(captionNode,Object.assign({},data,defaults)).includes('code'));
 const subtitleOff=presentation.prepare({regions:{subtitle:{enabled:0}}});const found=visible(captionNode,Object.assign({},data,subtitleOff));assert.ok(found.includes('code'));assert.ok(!found.includes('subtitle'));
 assert.deepEqual(visible(captionNode,Object.assign({},data,presentation.prepare({regions:{caption:{enabled:0}}}))),[]);
 dom.window.close();console.log('PASS native '+screen+' caption matrix, independent subtitle/code regions, defaults and whole-region hiding');
}
for(const flag of ['enabled','showTitle','showSubtitle','showCode','showExtra'])for(const value of [0,false,'0']){const input={caption:{[flag]:value}};const before=JSON.stringify(input);assert.equal(presentation.prepare(input).caption[flag],0);assert.equal(JSON.stringify(input),before);}
console.log('PASS false, zero and string-zero visibility flags normalize without changing source settings');
const css=fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/css/micro-web.css'),'utf8');assert.match(css,/\.fb-app\s+\[hidden\]\s*\{\s*display\s*:\s*none\s*!important\s*;?\s*\}/);
const dom=new JSDOM('<style>.fb-caption-sub{display:block}</style><style>'+css+'</style><main class="fb-app"><span class="fb-caption-sub" hidden>Hidden subtitle</span></main>');assert.equal(dom.window.getComputedStyle(dom.window.document.querySelector('[hidden]')).display,'none');dom.window.close();
console.log('PASS formal H5 hidden guard wins over author display:block captions');
