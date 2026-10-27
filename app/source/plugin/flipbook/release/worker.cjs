'use strict';
const fs=require('node:fs'),path=require('node:path'),os=require('node:os'),http=require('node:http'),crypto=require('node:crypto'),vm=require('node:vm');
const {unpack,safeName}=require('./zip.cjs');
const ALLOWED=/\.(?:js|json|wxml|wxss|wxs|png|jpe?g|gif|webp|svg|ico|ttf|woff2?|otf|mp3|mp4|wav|aac|txt)$/i;
const TERMINAL=new Set(['uploaded','failed','interrupted']);
function cleanError(error,secrets=[]){let text=String(error&&error.message||error||'发布服务异常').replace(/-----BEGIN[\s\S]*?-----END[^-]*-----/g,'[密钥已隐藏]').replace(/(access_token|secret|token)=([^&\s]+)/gi,'$1=[已隐藏]');for(const s of secrets)if(s)text=text.split(s).join('[已隐藏]');return text.slice(0,1000);}
function validateInput(input){
 const x=input||{};
 if(!/^wx[0-9a-zA-Z]{16}$/.test(x.appid||''))throw Error('请配置正确的 AppID');
 if(!/^[0-9A-Za-z][0-9A-Za-z._-]{0,31}$/.test(x.version||''))throw Error('版本号需为 32 位以内的字母、数字、点、下划线或短横线');
 if(typeof x.description!=='string'||Array.from(x.description).length>200)throw Error('版本说明最多 200 字');
 if(!['cloud','local'].includes(x.source))throw Error('代码来源无效');
 if(!/^[A-Za-z0-9_-]{16,100}$/.test(x.requestId||''))throw Error('发布请求标识无效');
 const origin=new URL(x.apiBase);if(origin.protocol!=='https:'||origin.username||origin.password||origin.search||origin.hash)throw Error('小程序接口地址应为有效的 HTTPS 站点地址');
 const key=crypto.createPrivateKey(x.privateKey||'');if(key.asymmetricKeyType!=='rsa')throw Error('上传密钥需要为微信下载的 RSA 私钥');
 return {...x,apiBase:origin.href.replace(/\/$/,'')};
}
function readSource(root){
 if(!root||!path.isAbsolute(root)||!fs.statSync(root).isDirectory())throw Error('云端源码目录尚未配置');
 const files=new Map();let bytes=0;
 function walk(dir,prefix=''){
  for(const entry of fs.readdirSync(dir,{withFileTypes:true})){
   if(entry.name.startsWith('.')||['node_modules','__MACOSX','project.private.config.json'].includes(entry.name))continue;
   const name=prefix+entry.name,full=path.join(dir,entry.name);if(entry.isSymbolicLink())throw Error('云端源码不能包含符号链接');
   if(entry.isDirectory()){walk(full,name+'/');continue;}
   if(!entry.isFile()||!ALLOWED.test(name))continue;
   const stat=fs.statSync(full);bytes+=stat.size;if(stat.size>8*1024*1024||bytes>50*1024*1024||files.size>=2500)throw Error('云端源码超出大小限制');
   files.set(name,fs.readFileSync(full));
  }
 }
 walk(root);return files;
}
function prepareProject(files,dir,input){
 if(!files.has('project.config.json'))throw Error('缺少 project.config.json');
 const project=JSON.parse(files.get('project.config.json').toString());
 const prefix=String(project.miniprogramRoot||'./').replace(/^\.\//,'').replace(/\/$/,'');
 if(prefix&&!safeName(prefix))throw Error('小程序根目录必须位于代码包内');
 const root=prefix?prefix+'/':'';
 if(project.appid&&project.appid!=='touristappid'&&project.appid!==input.appid)throw Error('代码包 AppID 与当前配置不一致');
 if(!files.has(root+'app.json')||!files.has(root+'app.js'))throw Error('代码包缺少 app.json 或 app.js');
 const app=JSON.parse(files.get(root+'app.json').toString());const pages=[...(app.pages||[])];
 for(const pkg of app.subPackages||app.subpackages||[]){if(!safeName(pkg.root))throw Error('分包根目录无效');for(const p of pkg.pages||[])pages.push(pkg.root.replace(/\/$/,'')+'/'+p);}
 if(!pages.length)throw Error('小程序没有页面');
 for(const p of pages){if(!safeName(p)||!files.has(root+p+'.js')||!files.has(root+p+'.wxml'))throw Error('小程序页面文件不完整：'+p);}
 let checked=0;
 for(const [name,data]of files){
  if(!safeName(name)||!ALLOWED.test(name)||name==='project.private.config.json'||name.split('/').some(s=>s.startsWith('.')||s==='node_modules'))throw Error('代码包包含不允许的文件：'+name);
  if(/\.json$/i.test(name))JSON.parse(data.toString());
  if(/\.js$/i.test(name)){new vm.Script(data.toString(),{filename:name});checked++;}
 }
 project.appid=input.appid;project.setting={...project.setting,urlCheck:true,minified:true,minifyJS:true,minifyWXML:true,minifyWXSS:true};
 files.set('project.config.json',Buffer.from(JSON.stringify(project,null,2)));
 const config=root+'config.js';if(!files.has(config)||!/(API_BASE\s*:\s*)['"][^'"]*['"]/.test(files.get(config).toString()))throw Error('代码包 config.js 缺少 API_BASE 配置');
 files.set(config,Buffer.from(files.get(config).toString().replace(/(API_BASE\s*:\s*)['"][^'"]*['"]/,(_,head)=>head+JSON.stringify(input.apiBase))));
 for(const [name,data]of files){const target=path.join(dir,name);fs.mkdirSync(path.dirname(target),{recursive:true,mode:0o700});fs.writeFileSync(target,data,{mode:0o600});}
 return {pages:pages.length,javascript:checked,files:files.size};
}
function createService(options={}){
 const data=path.resolve(options.dataDir||path.join(os.homedir(),'.flipbook-ci'));fs.mkdirSync(data,{recursive:true,mode:0o700});
 const records=new Map(),file=path.join(data,'versions.json');
 if(fs.existsSync(file)){for(const row of JSON.parse(fs.readFileSync(file))){if(!TERMINAL.has(row.status)){row.status='interrupted';row.message='构建服务重启，上传结果未确认，请在微信公众平台核对后再操作';}records.set(row.id,row);}}
 function save(){const temp=file+'.tmp';fs.writeFileSync(temp,JSON.stringify([...records.values()]),{mode:0o600});fs.renameSync(temp,file);}
 save();
 function list(appid,page=1,size=10){const rows=[...records.values()].filter(j=>j.appid===appid&&!j.deleted).sort((a,b)=>b.created.localeCompare(a.created));size=[10,20,50].includes(Number(size))?Number(size):10;page=Math.max(1,Math.min(Math.max(1,Math.ceil(rows.length/size)),Number(page)||1));return {rows:rows.slice((page-1)*size,page*size),total:rows.length,page,pageSize:size};}
 function find(appid,id){return [...records.values()].find(j=>j.appid===appid&&!j.deleted&&(j.id===id||j.requestId===id));}
 function remove(appid,id){const job=find(appid,id);if(!job)throw Error('版本记录不存在');if(!TERMINAL.has(job.status))throw Error('构建中的记录不可移除');job.deleted=true;save();return {removed:true};}
 function start(raw){
  const input=validateInput(raw);if(options.appid&&options.appid!==input.appid)throw Error('当前 AppID 未获构建服务授权');
  const old=find(input.appid,input.requestId);if(old)return old;
  if([...records.values()].some(j=>j.appid===input.appid&&!TERMINAL.has(j.status)))throw Error('当前小程序已有构建任务，请等待完成');
  const job={id:crypto.randomUUID(),requestId:input.requestId,appid:input.appid,version:input.version,description:input.description,source:input.source,status:'queued',created:new Date().toISOString(),message:'等待检查源码',checks:[]};records.set(job.id,job);save();
  async function run(){
   const workspace=fs.mkdtempSync(path.join(data,'build-'));const patch=next=>{Object.assign(job,next,{updated:new Date().toISOString()});save();};
   try{
    patch({status:'checking',message:'检查项目完整性与 JavaScript 语法'});
    const files=input.source==='cloud'?readSource(options.sourceRoot):unpack(Buffer.from(input.archive||'','base64'));
    const projectRoot=path.join(workspace,'project');fs.mkdirSync(projectRoot,{mode:0o700});const checked=prepareProject(files,projectRoot,input);patch({checks:[{name:'项目结构与语法',success:true,text:checked.files+' 个文件，'+checked.javascript+' 个脚本，'+checked.pages+' 个页面'}]});
    const ci=options.ci||require('miniprogram-ci');const project=new ci.Project({appid:input.appid,type:'miniProgram',projectPath:projectRoot,privateKey:input.privateKey,ignores:['node_modules/**/*']});
    const quality=await ci.checkCodeQuality(project);if(!Array.isArray(quality)||!quality.length)throw Error('微信质量检查返回无效结果');
    const checks=quality.map(x=>({name:String(x.name||'微信质量检查'),success:x.success===true,text:String(x.text||'').slice(0,500)}));patch({checks:job.checks.concat(checks)});
    if(checks.some(x=>x.name==='PACKAGE_SIZE_LIMIT'&&!x.success))throw Error('代码包大小检查未通过，请查看检查明细');
    patch({status:'compiling',message:'编译并打包微信小程序'});
    const common={project,version:input.version,desc:input.description,setting:{useProjectConfig:true},robot:1,onProgressUpdate(){}};
    await ci.getCompiledResult(common,path.join(workspace,'compiled-result.zip'));
    patch({status:'uploading',message:'正在上传微信开发版本'});
    const result=await ci.upload(common);
    patch({status:'uploaded',message:'开发版本上传成功，请在微信公众平台提交审核并发布',packages:((result||{}).subPackageInfo||[]).map(p=>({name:String(p.name),size:Number(p.size)||0}))});
   }catch(error){const uncertain=job.status==='uploading'&&/timeout|timed out|ECONNRESET|ETIMEDOUT|network|连接中断/i.test(String(error&&error.message||error));patch({status:uncertain?'interrupted':'failed',message:(uncertain?'上传结果待核对，请先在微信公众平台确认：':'')+cleanError(error,[input.privateKey,options.token])});}
   finally{input.privateKey='';input.archive='';fs.rmSync(workspace,{recursive:true,force:true});}
  }
  queueMicrotask(run);return job;
 }
 return {list,find,remove,start};
}
function createServer(options){
 if(typeof options.token!=='string'||options.token.length<32)throw Error('构建服务通信密钥至少 32 位');
 const service=createService(options),expected=Buffer.from('Bearer '+options.token);
 return http.createServer(async(req,res)=>{
  res.setHeader('Content-Type','application/json; charset=utf-8');res.setHeader('Cache-Control','no-store');
  const send=(code,body)=>{if(res.writableEnded)return;res.writeHead(code);res.end(JSON.stringify(body));};
  const auth=Buffer.from(req.headers.authorization||'');if(auth.length!==expected.length||!crypto.timingSafeEqual(auth,expected))return send(401,{ok:0,message:'构建服务身份校验失败'});
  try{
   const url=new URL(req.url,'http://localhost');const appid=url.searchParams.get('appid')||'';
   if(req.method==='GET'&&url.pathname==='/jobs'){return send(200,{ok:1,...service.list(appid,url.searchParams.get('page'),url.searchParams.get('pageSize'))});}
   if(req.method==='GET'&&url.pathname.startsWith('/jobs/')){const row=service.find(appid,decodeURIComponent(url.pathname.slice(6)));return row?send(200,{ok:1,job:row}):send(404,{ok:0,message:'版本记录不存在'});}
   if(req.method==='DELETE'&&url.pathname.startsWith('/jobs/'))return send(200,{ok:1,...service.remove(appid,url.pathname.slice(6))});
   if(req.method!=='POST'||url.pathname!=='/jobs')return send(404,{ok:0,message:'接口不存在'});
   const chunks=[];let length=0;for await(const chunk of req){length+=chunk.length;if(length>30*1024*1024){send(413,{ok:0,message:'上传代码包过大'});req.destroy();return;}chunks.push(chunk);}
   const row=service.start(JSON.parse(Buffer.concat(chunks).toString('utf8')));send(202,{ok:1,job:row});
  }catch(error){send(400,{ok:0,message:cleanError(error,[options.token])});}
 });
}
if(require.main===module){const server=createServer({sourceRoot:process.env.FLIPBOOK_CI_SOURCE_ROOT,dataDir:process.env.FLIPBOOK_CI_DATA,appid:process.env.FLIPBOOK_CI_APPID,token:process.env.FLIPBOOK_CI_TOKEN});server.listen(Number(process.env.FLIPBOOK_CI_PORT)||8096,process.env.FLIPBOOK_CI_HOST||'127.0.0.1',()=>console.log('画册小程序构建服务已启动'));}
module.exports={validateInput,prepareProject,readSource,createService,createServer,cleanError};
