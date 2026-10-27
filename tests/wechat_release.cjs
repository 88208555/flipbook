'use strict';
const fs=require('node:fs'),os=require('node:os'),path=require('node:path'),crypto=require('node:crypto'),assert=require('node:assert/strict'),zlib=require('node:zlib');
const root=process.env.FLIPBOOK_SOURCE_ROOT||path.resolve(__dirname,'..');
const worker=require(path.join(root,'app/source/plugin/flipbook/release/worker.cjs')),zip=require(path.join(root,'app/source/plugin/flipbook/release/zip.cjs'));
const temp=fs.mkdtempSync(path.join(os.tmpdir(),'flipbook-release-test-')),source=path.join(temp,'source');fs.mkdirSync(source);
const check=(value,message)=>{assert.ok(value,message);console.log('PASS '+message);};
const key=crypto.generateKeyPairSync('rsa',{modulusLength:1024}).privateKey.export({type:'pkcs8',format:'pem'});
const base={appid:'wx0000000000000000',version:'1.0.1',description:'画册测试版本',source:'cloud',privateKey:key,apiBase:'https://album.example.test',requestId:'original_request_1234'};
const sourceFiles={'project.config.json':JSON.stringify({appid:'',miniprogramRoot:'./'}),'app.json':JSON.stringify({pages:['pages/demo/index']}),'app.js':'App({});','config.js':"module.exports={API_BASE:'http://old.local'};",'pages/demo/index.js':'Page({});','pages/demo/index.wxml':'<view>演示</view>','pages/demo/index.json':'{}'};
for(const [name,text]of Object.entries(sourceFiles)){fs.mkdirSync(path.dirname(path.join(source,name)),{recursive:true});fs.writeFileSync(path.join(source,name),text);}
function archive(entries,method=0){let offset=0;const local=[],central=[];for(const entry of entries){const name=Buffer.from(entry.name),body=Buffer.from(entry.body||''),packed=method?zlib.deflateRawSync(body):body,crc=zip.crc32(body),a=Buffer.alloc(30),b=Buffer.alloc(46);a.writeUInt32LE(0x04034b50);a.writeUInt16LE(method,8);a.writeUInt32LE(crc,14);a.writeUInt32LE(packed.length,18);a.writeUInt32LE(body.length,22);a.writeUInt16LE(name.length,26);b.writeUInt32LE(0x02014b50);b.writeUInt16LE(0x0314,4);b.writeUInt16LE(method,10);b.writeUInt32LE(crc,16);b.writeUInt32LE(packed.length,20);b.writeUInt32LE(body.length,24);b.writeUInt16LE(name.length,28);b.writeUInt32LE(entry.mode?entry.mode*65536:0,38);b.writeUInt32LE(offset,42);local.push(a,name,packed);central.push(b,name);offset+=a.length+name.length+packed.length;}const c=Buffer.concat(central),end=Buffer.alloc(22);end.writeUInt32LE(0x06054b50);end.writeUInt16LE(entries.length,8);end.writeUInt16LE(entries.length,10);end.writeUInt32LE(c.length,12);end.writeUInt32LE(offset,16);return Buffer.concat([...local,c,end]);}
const entries=Object.entries(sourceFiles).map(([name,body])=>({name:'album/'+name,body}));
(async()=>{
 let server;
 try{
  for(const method of [0,8]){const unpacked=zip.unpack(archive(entries,method));assert.equal(unpacked.get('app.js').toString(),'App({});');}
  check(true,'stored and deflated local ZIPs unpack one complete nested project');
  for(const bad of [{name:'../outside.js'},{name:'/outside.js'},{name:'folder\\outside.js'},{name:'x.key',mode:0xa1ff}])assert.throws(()=>zip.unpack(archive([...entries,bad])));
  assert.throws(()=>zip.unpack(archive([...entries,entries[0]])));
  const corrupt=archive(entries);corrupt[30+entries[0].name.length]^=1;assert.throws(()=>zip.unpack(corrupt));
  check(true,'ZIP traversal, absolute paths, backslashes, symlinks, duplicate files and corrupt payloads are rejected');
  const huge=archive([...entries,{name:'album/large.js',body:'x'.repeat(8*1024*1024+1)}],8);assert.throws(()=>zip.unpack(huge));
  check(true,'compressed oversized content is rejected before extraction');
  for(const changed of [{appid:'not-appid'},{privateKey:'not-a-key'},{apiBase:'http://insecure.example'},{source:'shell'},{version:'1; touch /tmp/x'},{requestId:'short'},{description:'字'.repeat(201)}])assert.throws(()=>worker.validateInput({...base,...changed}));
  check(true,'upload identity, private key, HTTPS API, version and description limits are enforced');
  const before=JSON.stringify(worker.readSource(source),(key,value)=>Buffer.isBuffer(value)?value.toString():value);const staging=path.join(temp,'staging');fs.mkdirSync(staging);
  const summary=worker.prepareProject(worker.readSource(source),staging,base);assert.equal(summary.pages,1);assert.equal(JSON.parse(fs.readFileSync(path.join(staging,'project.config.json'))).appid,base.appid);assert(fs.readFileSync(path.join(staging,'config.js'),'utf8').includes(base.apiBase));assert.equal(fs.readFileSync(path.join(source,'project.config.json'),'utf8'),sourceFiles['project.config.json']);
  check(true,'isolated build sets AppID and HTTPS API without changing cloud source');
  for(const [name,replacement]of [['app.js','bad ???'],['app.json','{bad'],['project.config.json',JSON.stringify({appid:'wx1111111111111111'})]]){const files=worker.readSource(source);files.set(name,Buffer.from(replacement));assert.throws(()=>worker.prepareProject(files,staging,base));}
  const missing=worker.readSource(source);missing.delete('pages/demo/index.wxml');assert.throws(()=>worker.prepareProject(missing,staging,base));
  check(true,'broken JavaScript/JSON, mismatched AppID and incomplete page files block upload');
  const calls=[];let qualityFail=false,uploadFail=false,networkFail=false;
  const ci={Project:class{constructor(options){this.options=options;assert.equal(options.privateKey,key);assert.equal(JSON.parse(fs.readFileSync(path.join(options.projectPath,'project.config.json'))).appid,base.appid);}},async checkCodeQuality(){calls.push('quality');return [{name:'PACKAGE_SIZE_LIMIT',success:!qualityFail,text:'size check'}];},async getCompiledResult(options,file){calls.push('compile');assert.equal(options.setting.useProjectConfig,true);fs.writeFileSync(file,'simulated-package');},async upload(){calls.push('upload');if(networkFail)throw Error('ETIMEDOUT');if(uploadFail)throw Error('upload failed '+key);return {subPackageInfo:[{name:'__APP__',size:12}]};}};
  const dataDir=path.join(temp,'data'),service=worker.createService({sourceRoot:source,dataDir,ci,token:'t'.repeat(32)});
  async function finished(job){const deadline=Date.now()+2000;while(!['uploaded','failed','interrupted'].includes(job.status)){if(Date.now()>deadline)throw Error('test timed out');await new Promise(r=>setTimeout(r,5));}return job;}
  const first=service.start(base);assert.equal(service.start(base).id,first.id);assert.throws(()=>service.start({...base,requestId:'second_request_1234'}));await finished(first);
  assert.equal(first.status,'uploaded');assert.deepEqual(calls,['quality','compile','upload']);assert(!fs.readFileSync(path.join(dataDir,'versions.json'),'utf8').includes(key));assert(!fs.readdirSync(dataDir).some(n=>n.startsWith('build-')));
  check(true,'simulated CI runs quality scan, compile and upload once; duplicate request is idempotent and private material is cleaned');
  qualityFail=true;calls.length=0;const failed=service.start({...base,requestId:'quality_failure_1234'});await finished(failed);assert.equal(failed.status,'failed');assert.deepEqual(calls,['quality']);
  qualityFail=false;uploadFail=true;const failedUpload=service.start({...base,requestId:'upload_failure_12345'});await finished(failedUpload);assert.equal(failedUpload.status,'failed');assert(!failedUpload.message.includes(key));
  check(true,'failed quality checks prevent upload and failed upload preserves a redacted real error');
  uploadFail=false;networkFail=true;const uncertain=service.start({...base,requestId:'uncertain_upload_12345'});await finished(uncertain);assert.equal(uncertain.status,'interrupted');networkFail=false;check(true,'an interrupted upload remains unconfirmed instead of being reported as a known failure or success');
  const local=service.start({...base,source:'local',archive:archive(entries,8).toString('base64'),requestId:'local_upload_123456'});await finished(local);assert.equal(local.status,'uploaded');
  const page=service.list(base.appid,2,10);assert.equal(page.total,5);assert.equal(service.list('wx1111111111111111').total,0);service.remove(base.appid,first.id);assert.equal(service.list(base.appid).total,4);
  check(true,'local source upload uses the same pipeline and version history stays scoped to the AppID');
  server=worker.createServer({sourceRoot:source,dataDir:path.join(temp,'http'),ci,token:'t'.repeat(32)});await new Promise(r=>server.listen(0,'127.0.0.1',r));const origin='http://127.0.0.1:'+server.address().port;
  assert.equal((await fetch(origin+'/jobs')).status,401);assert.equal((await fetch(origin+'/jobs?appid='+base.appid,{headers:{Authorization:'Bearer '+'t'.repeat(32)}})).status,200);
  check(true,'worker HTTP endpoints reject unauthenticated callers');
  const headers={Authorization:'Bearer '+'t'.repeat(32),'Content-Type':'application/json'},created=await (await fetch(origin+'/jobs',{method:'POST',headers,body:JSON.stringify({...base,requestId:'http_unicode_123456',description:'画册中文😀版本'})})).json();assert.equal(created.ok,1);let remote;
  for(let i=0;i<50;i++){remote=await (await fetch(origin+'/jobs/'+created.job.id+'?appid='+base.appid,{headers})).json();if(['uploaded','failed','interrupted'].includes(remote.job.status))break;await new Promise(r=>setTimeout(r,5));}assert.equal(remote.job.status,'uploaded');assert.equal(remote.job.description,'画册中文😀版本');assert(!JSON.stringify(remote).includes(key));
  check(true,'authenticated worker HTTP preserves Unicode version notes and never returns private keys');
  const json=JSON.parse(fs.readFileSync(path.join(dataDir,'versions.json')));json.push({id:'interrupted',appid:base.appid,requestId:'interrupted_1234567',status:'uploading',created:new Date().toISOString()});fs.writeFileSync(path.join(dataDir,'versions.json'),JSON.stringify(json));const restarted=worker.createService({dataDir,sourceRoot:source,ci});assert.equal(restarted.find(base.appid,'interrupted').status,'interrupted');
  check(true,'service restart keeps an uncertain upload result instead of automatically repeating it');
  console.log('NOTE WeChat CI calls above are isolated test doubles; no external upload, real compilation or deployment was performed.');
 }finally{if(server)await new Promise(r=>server.close(r));fs.rmSync(temp,{recursive:true,force:true});}
})().catch(error=>{console.error(error.message);process.exitCode=1;});
