const fs=require('node:fs'),assert=require('node:assert/strict'),{JSDOM}=require('/tmp/flipbook-editor-dom/node_modules/jsdom');
const C=require('../app/source/plugin/flipbook/static/js/composer-core'),schema=require('../app/source/plugin/flipbook/static/config/composer.json');
const props=(type,p={})=>Object.assign({},schema.initialStyle,schema.types[type].defaults,p);
const cfg={schema,typography:schema.defaultTypography,layout:{bg:'',size:0,topGap:0,bottomGap:0,componentGap:0},nav:{enabled:1,height:0,logo:{enabled:1,type:'text',text:'保留文字'},back:{show:0}},tabbar:{enabled:0,items:[]},components:[{id:'g',type:'container',props:props('container',{children:[{id:'a',type:'text',props:props('text',{text:'第一行'})},{id:'b',type:'text',props:props('text',{text:'第二行'})}]})}]};
const html='<div class="fbmp-canvas-stage"><div class="fbmp-canvas"><div id="fbmp-viewport"><div id="fbmp-frame"></div></div></div></div><div id="fbmp-inspector"></div><input id="fbmp-scale" value="100"><output id="fbmp-preview-size"></output><button data-add="text">文字</button><button data-add="container">布局容器</button><button data-add="product_skus">商品缩图</button><button data-add="login_form">登录表单</button><button data-preset="mixed_layout">混合布局</button>'+['save','undo','redo','export','import'].map(id=>'<button id="fbmp-'+id+'">'+id+'</button>').join('')+'<form id="fbmp-form"><input id="fbmp-config"></form><script id="fbmp-data" type="application/json">'+JSON.stringify(cfg)+'</script>';
const dom=new JSDOM(html,{url:'http://localhost/',runScripts:'outside-only',pretendToBeVisual:true}),w=dom.window,d=w.document;
w.FBComposer=C;w.FBMP_KEY='detail';w.FBMP_KEYS={home:'首页',micro_0123456789abcdef:'品牌故事'};w.FBMP_CONTEXT={product:{slides:[{image:'one.jpg'},{image:'two.jpg'}]}};let saved;d.querySelector('#fbmp-form').submit=()=>{saved=JSON.parse(d.querySelector('#fbmp-config').value);};
w.eval(fs.readFileSync('app/source/plugin/flipbook/static/js/composer-gallery.js','utf8'));
w.eval(fs.readFileSync('app/source/plugin/flipbook/static/admin/micropage.js','utf8'));
const click=s=>{const n=d.querySelector(s);assert(n,s);n.click();};
const field=(suffix,val)=>{const n=d.querySelector('[data-fc-field$="'+suffix+'"]');assert(n,suffix);n.value=val;n.dispatchEvent(new w.Event('input',{bubbles:true}));};
click('[data-node="components.0.props.children.1"]');const first=d.querySelector('[data-node="components.0.props.children.0"]'),second=d.querySelector('[data-node="components.0.props.children.1"]');field('.text','第二行修改');assert.equal(first,d.querySelector('[data-node="components.0.props.children.0"]'));assert.equal(second,d.querySelector('[data-node="components.0.props.children.1"]'));assert.equal(second.querySelector('.fc-text').textContent,'第二行修改');console.log('PASS text typing preserves the edited node and its neighbors');
click('[data-add="text"]');click('#fbmp-save');assert.equal(saved.components[0].props.children[1].type,'text');assert.equal(saved.components[0].props.children[2].props.text,'第二行修改');console.log('PASS a new component inserts before the selected child');
click('[data-op="copy"]');click('#fbmp-save');assert.equal(saved.components[0].props.children.length,4);click('[data-op="up"]');click('[data-op="down"]');click('[data-op="delete"]');click('#fbmp-save');assert.equal(saved.components[0].props.children.length,3);console.log('PASS child components support copy, move and delete');
click('[data-node="components.0"]');click('[data-op="copy"]');click('#fbmp-save');assert.equal(saved.components.length,2);const ids=[];function walk(ns){ns.forEach(n=>{ids.push(n.id);walk(n.props.children||[]);});}walk(saved.components);assert.equal(new Set(ids).size,ids.length);console.log('PASS container duplication renews every descendant ID');
click('#fbmp-frame');click('[data-config-tab="nav"]');const before=d.querySelector('[data-node="components.0"]');field('nav.height','50');assert.equal(before,d.querySelector('[data-node="components.0"]'));console.log('PASS navigation changes preserve the component tree');
click('[data-config-tab="typography"]');field('typography.title.size','23');click('#fbmp-save');assert.equal(saved.typography.title.size,23);assert.equal(saved.globalTypographyDirty,true);console.log('PASS global typography is included in the save transaction');
assert.equal(d.querySelector('#fbmp-page-links option[value="page:micro_0123456789abcdef"]').textContent,'品牌故事');console.log('PASS custom page links remain discoverable');const hostile={...cfg,components:[{id:'hostile',type:'text',props:props('text',{text:'<img src=x onerror=alert(1)><script>alert(1)</script>'})}]};const safe=d.createElement('div');safe.innerHTML=C.render(hostile.components,{...schema,typography:cfg.typography},{});assert.equal(safe.querySelector('img,script,[onerror]'),null);assert(safe.textContent.includes('<script>'));console.log('PASS untrusted component text renders as literal text at the HTML sink');
click('[data-node="components.0.props.children.0"]');click('[data-config-tab="style"]');
field('.textRole','title');
const colorPicker=d.querySelector('[data-fc-color$=".props.color"]');assert(colorPicker);
colorPicker.value='#ff6600';colorPicker.dispatchEvent(new w.Event('input',{bubbles:true}));
click('#fbmp-save');assert.equal(saved.components[0].props.children[0].props.color,'#ff6600');assert.notEqual(saved.typography.title.color,'#ff6600');
assert(d.querySelector('[data-node="components.0.props.children.0"]').style.color.includes('255'));
console.log('PASS the visible color picker updates only the selected title and persists its color');
click('[data-style-section="border"]');field('.radius','12');field('.radiusTopLeft','0');field('.radiusBottomRight','18');click('#fbmp-save');
assert.equal(saved.components[0].props.children[0].props.radiusTopLeft,0);assert.equal(saved.components[0].props.children[0].props.radiusBottomRight,18);
field('.radiusTopLeft','');click('#fbmp-save');assert.equal(saved.components[0].props.children[0].props.radiusTopLeft,'');
console.log('PASS each corner accepts zero and can return to the common radius');

click('#fbmp-frame');click('[data-add="container"]');
assert(d.querySelector('[data-node="components.2"] .fc-editor-dropzone'));
click('[data-node="components.2"] .fc-editor-dropzone');click('[data-add="container"]');
click('[data-node="components.2.props.children.0"] .fc-editor-dropzone');click('[data-add="text"]');click('#fbmp-save');
assert.equal(saved.components[2].props.children[0].props.children[0].type,'text');
console.log('PASS empty layout containers show an editor drop zone and accept several nested levels');
click('#fbmp-frame');click('[data-add="container"]');
const moving=d.querySelector('[data-node="components.2.props.children.0.props.children.0"]');
moving.dispatchEvent(new w.MouseEvent('pointerdown',{bubbles:true}));
const startDrag=new w.Event('dragstart',{bubbles:true,cancelable:true});Object.defineProperty(startDrag,'dataTransfer',{value:{setData(){}}});moving.dispatchEvent(startDrag);
d.querySelector('[data-node="components.3"] .fc-editor-dropzone').dispatchEvent(new w.Event('drop',{bubbles:true,cancelable:true}));click('#fbmp-save');
assert.equal(saved.components[3].props.children[0].type,'text');assert.equal(saved.components[2].props.children[0].props.children.length,0);
assert(d.querySelector('[data-node="components.2.props.children.0"] .fc-editor-dropzone'));
console.log('PASS dragging into an empty container reparents the component and restores its former empty drop zone');
const parentNode=d.querySelector('[data-node="components.2"]');parentNode.dispatchEvent(new w.MouseEvent('pointerdown',{bubbles:true}));
const cycle=new w.Event('dragstart',{bubbles:true,cancelable:true});Object.defineProperty(cycle,'dataTransfer',{value:{setData(){}}});parentNode.dispatchEvent(cycle);
d.querySelector('[data-node="components.2.props.children.0"] .fc-editor-dropzone').dispatchEvent(new w.Event('drop',{bubbles:true,cancelable:true}));click('#fbmp-save');assert.equal(saved.components[2].props.children[0].type,'container');
console.log('PASS a container cannot be moved into its own descendant');

const pointerSource=d.querySelector('[data-node="components.3.props.children.0"]'),pointerTarget=d.querySelector('[data-node="components.2.props.children.0"] .fc-editor-dropzone');
pointerSource.dispatchEvent(new w.MouseEvent('pointerdown',{bubbles:true,cancelable:true,button:0,clientX:50,clientY:100}));
pointerTarget.dispatchEvent(new w.MouseEvent('pointermove',{bubbles:true,cancelable:true,clientX:50,clientY:20}));
pointerTarget.dispatchEvent(new w.MouseEvent('pointerup',{bubbles:true,cancelable:true,clientX:50,clientY:20}));click('#fbmp-save');
assert.equal(saved.components[2].props.children[0].props.children[0].type,'text');assert.equal(saved.components[3].props.children.length,0);
console.log('PASS pointer dragging reparents into nested containers without browser HTML drag interference');

click('[data-node="components.3"]');click('[data-config-tab="layout"]');click('[data-style-section="arrangement"]');click('[data-container-columns="3"]');click('#fbmp-save');
assert.equal(saved.components[3].props.columns,3);assert.equal(saved.components[3].props.display,'grid');assert.equal(saved.components[3].props.children.length,3);
console.log('PASS a container can create three equal child regions without removing its contents');
click('[data-node="components.3.props.children.0"]');click('[data-container-child]');click('[data-add="text"]');click('#fbmp-save');
assert.equal(saved.components[3].props.children[0].props.children[0].props.children[0].type,'text');
click('.fbmp-container-path [data-select-node="components.3.props.children.0"]');click('[data-config-tab="layout"]');click('[data-style-section="arrangement"]');click('[data-container-columns="2"]');
click('[data-node="components.3.props.children.1"]');click('[data-config-tab="layout"]');click('[data-style-section="arrangement"]');click('[data-container-columns="2"]');click('[data-container-columns="1"]');click('#fbmp-save');
assert.equal(saved.components[3].props.columns,3);assert.equal(saved.components[3].props.children[0].props.columns,2);assert.equal(saved.components[3].props.children[1].props.columns,0);assert.equal(saved.components[3].props.children[1].props.direction,'column');assert.equal(saved.components[3].props.children[1].props.children.length,2);
console.log('PASS sibling regions can independently mix rows, columns and another nested container');
click('#fbmp-frame');click('[data-preset="mixed_layout"]');click('#fbmp-save');
assert.equal(saved.components[4].props.columns,2);assert.equal(saved.components[4].props.children[0].props.children.length,2);assert.equal(saved.components[4].props.children[1].props.columns,2);assert.equal(saved.components[4].props.children[1].props.children.length,4);
console.log('PASS the mixed layout preset creates a vertical region beside a two-by-two region');
click('#fbmp-frame');click('[data-add="product_skus"]');click('[data-config-tab="style"]');click('[data-style-section="image"]');field('.radius','14');field('.radiusTopLeft','0');field('.radiusBottomRight','20');
const skuImages=[...d.querySelectorAll('[data-type="product_skus"] .fc-sku .fc-image')];assert.equal(skuImages.length,2);
assert(skuImages.every(image=>image.style.getPropertyValue('border-top-left-radius').includes('0 *')&&image.style.getPropertyValue('border-bottom-right-radius').includes('20 *')&&image.style.height.includes('64 *')));
console.log('PASS SKU corner controls update every thumbnail while preserving the configured thumbnail height');

click('#fbmp-frame');click('[data-add="container"]');
const keyboardZone=d.querySelector('[data-node="components.6"] .fc-editor-dropzone');assert(keyboardZone);
keyboardZone.dispatchEvent(new w.KeyboardEvent('keydown',{key:'Enter',bubbles:true,cancelable:true}));click('[data-add="text"]');click('#fbmp-save');assert.equal(saved.components[6].props.children[0].type,'text');
console.log('PASS an empty nested region can be entered by keyboard before adding content');
click('#fbmp-frame');w.FBMP_KEY='login';
// The running editor scopes its palette to the original page; test an integrated form at the renderer boundary.
const loginHost=d.createElement('div');loginHost.innerHTML=C.render([{id:'login-single',type:'login_form',props:{controlPadY:0,tabGap:0}}],{...schema,typography:cfg.typography,layout:{bg:'#000000'}},{login:{account:true,wechat:true}});
assert.equal(loginHost.querySelectorAll('[data-node]').length,1);assert.equal(loginHost.querySelectorAll('input,select').length,4);
assert.equal(loginHost.querySelector('[data-fc-action="login_account"]').getAttribute('aria-pressed'),'true');
console.log('PASS the entire login form is one selectable component with live controls and visible account selection');
dom.window.close();





