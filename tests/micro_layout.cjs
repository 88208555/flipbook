// Run: node tests/micro_layout.cjs
// Exercises the real native view-model builder without a WeChat session or persisted configuration.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const micro = require('../miniprogram/utils/micro');

function check(message, run) { run(); console.log('PASS ' + message); }
function styleMap(value) {
  return Object.fromEntries(value.split(';').filter(Boolean).map(entry => {
    const colon = entry.indexOf(':');
    return [entry.slice(0, colon).trim(), entry.slice(colon + 1).trim()];
  }));
}
function config(overrides = {}) {
  return {
    nav: {
      enabled: 1, height: 44, firstGap: 16,
      back: { show: true, link: 'page:home' },
      logo: { enabled: 1, top: 0, height: 28, offsetLeft: 16, offsetRight: 16, align: 'left', text: 'FLIPBOOK', ...overrides },
    },
    components: [],
    tabbar: { enabled: 0 },
  };
}
function bothStates(input, statusBar) {
  const on = micro.prepare(input, { _statusBar: statusBar });
  const off = micro.prepare({ ...input, nav: { ...input.nav, enabled: 0 } }, { _statusBar: statusBar });
  return { on, off };
}

check('native navigation switch preserves the complete logo view model and 44px row', () => {
  const { on, off } = bothStates(config(), 47);
  assert.equal(on.nav.enabled, true);
  assert.equal(off.nav.enabled, false);
  assert.equal(on.nav.logo.floating, false);
  assert.equal(off.nav.logo.floating, true);
  assert.equal(on.nav.logo.style, off.nav.logo.style);
  assert.equal(on.nav.logo.textStyle, off.nav.logo.textStyle);
  assert.equal(on.nav.logo.imageStyle, off.nav.logo.imageStyle);
  assert.equal(off.nav.height, '88rpx');
  const row = styleMap(off.nav.logo.floatingStyle);
  assert.equal(row.height, on.nav.height);
  assert.equal(row.top, '47px');
  assert.equal(row.left, '32rpx');
  assert.equal(row.right, '32rpx');
  assert.equal(styleMap(off.nav.logo.style).top, '0rpx');
});

check('native status-bar height changes the containing row without shifting the configured logo offset', () => {
  const input = config({ top: 7, height: 20, width: 96, radius: 3, type: 'image', image: '/logo.png' });
  input.nav.height = 60;
  const at20 = bothStates(input, 20);
  const at47 = bothStates(input, 47);
  assert.equal(at20.on.nav.logo.style, at20.off.nav.logo.style);
  assert.equal(at47.on.nav.logo.style, at47.off.nav.logo.style);
  assert.equal(at20.off.nav.logo.style, at47.off.nav.logo.style);
  assert.equal(styleMap(at47.off.nav.logo.floatingStyle).height, '120rpx');
  assert.equal(styleMap(at20.off.nav.logo.floatingStyle).top, '20px');
  assert.equal(styleMap(at47.off.nav.logo.floatingStyle).top, '47px');
  const logo = styleMap(at47.off.nav.logo.style);
  assert.equal(logo.top, '14rpx');
  assert.equal(logo.height, '40rpx');
  assert.equal(logo.width, '192rpx');
  assert.equal(logo['margin-left'], '80rpx');
});

check('native hidden logo remains hidden independently of navigation visibility', () => {
  const { on, off } = bothStates(config({ enabled: 0 }), 47);
  assert.equal(on.nav.logo.enabled, false);
  assert.equal(off.nav.logo.enabled, false);
  assert.equal(on.nav.logo.floating, false);
  assert.equal(off.nav.logo.floating, false);
});

check('editor keeps a proportional phone inside the workspace without horizontal growth or simulated bottom safe-area space', () => {
  const root = path.resolve(__dirname, '..');
  const css = fs.readFileSync(path.join(root, 'app/source/plugin/flipbook/static/admin/micropage.css'), 'utf8');
  const rule = selector => {
    const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const matches = [...css.matchAll(new RegExp(escaped + '\\s*\\{([^}]+)\\}', 'g'))];
    assert.equal(matches.length, 1, selector + ' should have one authoritative definition');
    return styleMap(matches[0][1]);
  };
  const frame = rule('.fbmp-frame');
  assert.equal(frame.width, '375px');
  assert.equal(rule('.fbmp-viewport').width, '1440px');
  assert.equal(frame['max-width'], 'none');
  assert.equal(frame.flex, 'none');
  assert.equal(frame['aspect-ratio'], undefined,'long content must not be constrained to a phone aspect ratio');
  assert.equal(frame.height, 'auto');
  assert.equal(frame.transform, undefined);
  assert.equal(frame['transform-origin'], undefined);
  assert.equal(frame['will-change'], undefined);
  assert.equal(rule('.fbmp-canvas')['overflow-x'], 'hidden');
  assert.equal(rule('.fbmp-canvas')['overflow-y'], 'auto');
  assert.equal(rule('.fbmp-preview-shell')['max-width'], '100%');
  assert.equal(rule('.fbmp-viewport')['max-width'], 'none');
  assert.equal(rule('.fbmp-viewport').position, 'absolute');
  assert.equal(rule('.fbmp-preview-page').overflow, 'visible');
  assert.match(css,/grid-template-columns:\s*min\(280px,18%\) minmax\(0,1fr\) min\(380px,26%\)/);
  assert.doesNotMatch(css,/min-width:\s*(?:1027|1067|1107)px|minmax\(407px/);
  assert.equal(rule('.fbmp-preview-tabbar').padding, '0');
  const editor = fs.readFileSync(path.join(root, 'app/source/plugin/flipbook/static/admin/micropage.js'), 'utf8');
  assert.doesNotMatch(editor, /px\(cfg\.tabbar\.height\)\s*\+\s*34/);
  assert.doesNotMatch(editor, /frame\.style\.transform\s*=/);
  assert.match(editor, /frame\.style\.zoom=String\(paintScale\)/);
});
