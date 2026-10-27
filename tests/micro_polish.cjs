'use strict';
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),{spawnSync}=require('node:child_process');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..'),micro=require(path.join(root,'miniprogram/utils/micro'));
const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);args.push('flipbook-php','php');const php=spawnSync('docker',args,{input:fs.readFileSync(path.join(root,'tests/micro_polish.php')),encoding:'utf8'});assert.equal(php.status,0,php.stderr);assert.equal(php.stderr,'');const fixture=JSON.parse(php.stdout);fixture.checks.forEach(name=>console.log('PASS '+name));
const config={nav:{enabled:0,logo:{},back:{}},components:[],tabbar:{enabled:1,color:'#667788',activeColor:'#112233',items:[{label:'发现',link:'page:home',iconSvg:fixture.svg},{label:'我的',link:'page:mine',iconSvg:fixture.raster}]}};
for(const key of ['home','mine']){
 const view=micro.prepare(config,{key});for(const item of view.tabs){const svg=decodeURIComponent(item.iconStyle.match(/data:image\/svg\+xml,([^\"]+)/)[1]);assert(svg.includes(item.selected?'#112233':'#667788'));assert(!svg.includes('currentColor'));assert(svg.includes('viewBox='));}console.log('PASS custom SVG and uploaded icon automatically tint for '+key+' active state');
}
const style=micro.regionStyle({offsetX:12,offsetY:-24,padTop:8,align:'center'});assert(style.includes('transform:translate(24rpx,-48rpx)'));assert(!style.includes('margin-'));console.log('PASS visual positioning changes paint without changing layout margins');
for(const file of ['app/source/plugin/flipbook/template/micro_style.htm','miniprogram/components/fb-screen-read/index.wxml','app/source/plugin/flipbook/template/read.htm']){const text=fs.readFileSync(path.join(root,file),'utf8');assert(!text.includes('class="fb-counter"'));console.log('PASS visible reader counter is absent from '+file);}
const registry=JSON.parse(fs.readFileSync(path.join(root,'app/source/plugin/flipbook/static/config/component-regions.json')));assert(!registry.product_detail.counter);assert(registry.product_detail.thumbs&&registry.product_detail.thumbImage);console.log('PASS region registry removes the counter while preserving SKU thumbnail controls');

// Run the existing editor regression against these same source files.
const editorResult=spawnSync(process.execPath,[path.join(root,'tests/micro_editor.cjs')],{cwd:root,env:process.env,encoding:'utf8',timeout:60000});
process.stdout.write(editorResult.stdout||'');process.stderr.write(editorResult.stderr||'');assert.equal(editorResult.status,0,'Editor regression failed: '+(editorResult.error||''));
