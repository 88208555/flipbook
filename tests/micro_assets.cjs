// Generated H5 styles must preserve native component selectors and navigation proportions.
const assert=require('node:assert/strict'),fs=require('node:fs'),{execFileSync}=require('node:child_process');
execFileSync('python3',['scripts/sync-micro-assets.py','--check'],{stdio:'pipe'});
assert(!fs.existsSync('miniprogram/utils/markdown-styles.js'));assert(JSON.parse(fs.readFileSync('app/source/plugin/flipbook/static/config/text-presets.json')).html);console.log('PASS structural H5 assets stay generated while HTML presentation uses the single backend configuration');
const native=fs.readFileSync('miniprogram/app.wxss','utf8'),h5=fs.readFileSync('app/source/plugin/flipbook/static/css/micro-business.css','utf8');
for(const selector of ['.fb-page','.fb-read-page','.fb-tab-page','.fb-m-page']) {
 const rule=new RegExp(selector.replaceAll('.','\\.')+'\\s*\\{');
 assert.match(native,rule);assert.match(h5,rule);
}
assert.doesNotMatch(h5,/\.fb-(?:read-|tab-|m-)?\.fb-mc-main/);
console.log('PASS page suffixes in native component class names survive H5 generation');
const web=fs.readFileSync('app/source/plugin/flipbook/static/css/flipbook.css','utf8'),editor=fs.readFileSync('app/source/plugin/flipbook/static/admin/micropage.css','utf8');
assert.match(native,/--fb-icon-size:\s*32rpx/);
assert.match(web,/--fb-icon-size:\s*calc\(32 \* var\(--fb-rpx/);
assert.match(web,/--fb-fs-xs:\s*calc\(22 \* var\(--fb-rpx/);
assert.match(editor,/\.fbmp-preview-tab-icon\s*\{[^}]*width:\s*16px;[^}]*height:\s*16px/s);
console.log('PASS H5 and editor navigation use native 32rpx icons and 22rpx labels at mobile scale');
