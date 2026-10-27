const fs = require('node:fs');
const assert = require('node:assert/strict');
const {JSDOM} = require('/tmp/flipbook-editor-dom/node_modules/jsdom');
const gallerySource = fs.readFileSync('app/source/plugin/flipbook/static/js/composer-gallery.js', 'utf8');
function dimensions(track, scale = 1) {
  Object.defineProperty(track, 'clientWidth', {configurable: true, value: 300});
  track.getBoundingClientRect = () => ({width: 300 * scale});
  track.scrollTo = ({left}) => {track.scrollLeft = left;};
}
function pointer(w, target, type, x, y = 50, pointerType = 'mouse') {
  const event = new w.MouseEvent(type, {bubbles: true, cancelable: true, clientX: x, clientY: y, button: 0});
  Object.defineProperties(event, {pointerId: {value: 1}, pointerType: {value: pointerType}, isPrimary: {value: true}});
  target.dispatchEvent(event);
  return event;
}
function fixture(scale = 1) {
  const dom = new JSDOM('<main><div class="fc-gallery-track"><div><img></div><div><img></div><div><img></div></div></main>', {runScripts: 'outside-only'});
  const w = dom.window, track = w.document.querySelector('.fc-gallery-track'), image = track.querySelector('img');
  dimensions(track, scale);
  w.eval(gallerySource);
  const binding = w.FBComposerGallery.bind(track);
  return {dom, w, track, image, binding};
}
function swipe(f, from, to, type = 'mouse') {
  pointer(f.w, f.image, 'pointerdown', from, 50, type);
  const move = pointer(f.w, f.track, 'pointermove', to, 50, type);
  pointer(f.w, f.track, 'pointerup', to, 50, type);
  return move;
}
{
  const f = fixture(); let opened = 0;
  f.w.document.querySelector('main').addEventListener('click', () => opened++);
  assert(swipe(f, 260, 70).defaultPrevented);
  assert.equal(f.track.scrollLeft, 300);
  const click = new f.w.MouseEvent('click', {bubbles: true, cancelable: true, detail: 1});
  f.image.dispatchEvent(click);
  assert(click.defaultPrevented); assert.equal(opened, 0);
  pointer(f.w, f.image, 'pointerdown', 150); pointer(f.w, f.image, 'pointerup', 150);
  f.image.dispatchEvent(new f.w.MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));
  assert.equal(opened, 1);
  swipe(f, 60, 250); assert.equal(f.track.scrollLeft, 0);
  console.log('PASS mouse dragging changes slides in both directions, suppresses its trailing click and preserves a normal tap');
  f.dom.window.close();
}
{
  const f = fixture();
  swipe(f, 260, 70, 'touch'); assert.equal(f.track.scrollLeft, 300);
  f.track.scrollLeft = 0;
  pointer(f.w, f.image, 'pointerdown', 150, 50, 'touch');
  const vertical = pointer(f.w, f.track, 'pointermove', 145, 180, 'touch');
  assert(!vertical.defaultPrevented); assert.equal(f.track.scrollLeft, 0); assert(!f.binding.isInteracting());
  console.log('PASS touch swipes change slides while vertical gestures remain available for page scrolling');
  f.dom.window.close();
}
{
  const f = fixture(.5);
  pointer(f.w, f.image, 'pointerdown', 140); pointer(f.w, f.track, 'pointermove', 60);
  assert.equal(f.track.scrollLeft, 160);
  pointer(f.w, f.track, 'pointerup', 60); assert.equal(f.track.scrollLeft, 300);
  f.track.scrollLeft = 600; swipe(f, 260, 70); assert.equal(f.track.scrollLeft, 600);
  f.track.scrollLeft = 0; swipe(f, 60, 250); assert.equal(f.track.scrollLeft, 0);
  console.log('PASS scaled canvases use the correct drag distance and stop at the first and last slide');
  f.dom.window.close();
}
{
  const f = fixture(), video = f.w.document.createElement('video');
  video.controls = true; video.getBoundingClientRect = () => ({bottom: 200}); f.track.firstChild.appendChild(video);
  pointer(f.w, video, 'pointerdown', 200, 185); assert(!f.binding.isInteracting());
  pointer(f.w, f.image, 'pointerdown', 260); pointer(f.w, f.track, 'pointermove', 140);
  pointer(f.w, f.track, 'pointercancel', 140); assert.equal(f.track.scrollLeft, 0); assert(!f.binding.isInteracting());
  assert.equal(f.w.FBComposerGallery.bind(f.track), f.binding);
  console.log('PASS native video controls remain usable and cancelled gestures release the drag state');
  f.dom.window.close();
}
{
  const Core = require('../app/source/plugin/flipbook/static/js/composer-core');
  const schema = require('../app/source/plugin/flipbook/static/config/composer.json');
  const config = {schema, typography: schema.defaultTypography, layout: {}, nav: {enabled: 0}, tabbar: {enabled: 0, items: []}, components: [
    {id: 'gallery', type: 'carousel', props: {...schema.initialStyle, ...schema.types.carousel.defaults, items: [{image: 'one.jpg'}, {image: 'two.jpg'}]}}
  ]};
  const html = '<div class="fbmp-canvas-stage"><div class="fbmp-canvas"><div id="fbmp-viewport"><div id="fbmp-frame"></div></div></div></div><div id="fbmp-inspector"></div><input id="fbmp-scale"><output id="fbmp-preview-size"></output>' +
    ['save', 'undo', 'redo', 'export', 'import'].map(id => '<button id="fbmp-' + id + '"></button>').join('') +
    '<form id="fbmp-form"><input id="fbmp-config"></form><script id="fbmp-data" type="application/json">' + JSON.stringify(config) + '</script>';
  const dom = new JSDOM(html, {runScripts: 'outside-only', pretendToBeVisual: true}), w = dom.window, d = w.document;
  w.FBComposer = Core; w.FBMP_KEY = 'detail'; w.FBMP_CONTEXT = {}; w.eval(gallerySource);
  w.eval(fs.readFileSync('app/source/plugin/flipbook/static/admin/micropage.js', 'utf8'));
  let track = d.querySelector('.fc-gallery-track'); dimensions(track);
  const image = track.querySelector('img');
  pointer(w, image, 'pointerdown', 260);
  const reorder = new w.Event('dragstart', {bubbles: true, cancelable: true});
  d.querySelector('[data-node]').dispatchEvent(reorder); assert(reorder.defaultPrevented);
  pointer(w, track, 'pointermove', 70); pointer(w, track, 'pointerup', 70); assert.equal(track.scrollLeft, 300);
  image.click(); d.querySelector('[data-node]').click();d.querySelector('[data-config-tab="content"]').click();
  const field = d.querySelector('[data-fc-field$=".items.0.image"]'); assert(field);
  field.value = 'updated.jpg'; field.dispatchEvent(new w.Event('input', {bubbles: true}));
  const replacement = d.querySelector('.fc-gallery-track'); assert.notEqual(replacement, track); dimensions(replacement);
  swipe({w, track: replacement, image: replacement.querySelector('img')}, 260, 70); assert.equal(replacement.scrollLeft, 300);
  assert.equal(d.querySelector('#fbmp-save').textContent, '保存并同步 ·');
  console.log('PASS editor component dragging does not steal gallery gestures and updated galleries remain draggable');
  dom.window.close();
}

{
 const f=fixture();f.track.style.width='426.625px';Object.defineProperty(f.track,'clientWidth',{configurable:true,value:427});f.track.getBoundingClientRect=()=>({width:426.625});
 swipe(f,260,70);assert.equal(f.track.scrollLeft,426.625);
 console.log('PASS snapping uses fractional layout width rather than a rounded client width');f.dom.window.close();
}
