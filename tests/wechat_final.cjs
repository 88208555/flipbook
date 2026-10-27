'use strict';
// One local runner for the release pipeline, shared typography and unchanged WeChat upload controls.
const fs=require('node:fs'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
for(const file of ['tests/wechat_release.cjs','tests/wechat_presentation.cjs','tests/wechat-admin.cjs','tests/wechat-qrcode.cjs']){
 const result=spawnSync(process.execPath,[path.join(root,file)],{cwd:root,env:process.env,encoding:'utf8'});process.stdout.write(result.stdout||'');process.stderr.write(result.stderr||'');if(result.status!==0)process.exit(result.status||1);
}
const args=['exec','-i'];if(process.env.FLIPBOOK_PLUGIN_ROOT)args.push('-e','FLIPBOOK_PLUGIN_ROOT='+process.env.FLIPBOOK_PLUGIN_ROOT);args.push('flipbook-php','php');
const result=spawnSync('docker',args,{input:fs.readFileSync(path.join(root,'tests/wechat-tabs.php')),encoding:'utf8'});process.stdout.write(result.stdout||'');process.stderr.write(result.stderr||'');if(result.status!==0)process.exit(result.status||1);
