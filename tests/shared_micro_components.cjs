const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'../miniprogram'),app=JSON.parse(fs.readFileSync(path.join(root,'app.json'),'utf8'));
function expand(file,chain=[]){
 assert.ok(!chain.includes(file),'WXML includes must not recurse');
 return fs.readFileSync(file,'utf8').replace(/<include\s+src="([^"]+)"\s*\/>/g,(_,src)=>expand(path.resolve(path.dirname(file),src),chain.concat(file)));
}
for(const page of app.pages){
 const file=path.join(root,page+'.wxml');if(!fs.existsSync(file))continue;
 const xml=expand(file),config=JSON.parse(fs.readFileSync(path.join(root,page+'.json'),'utf8'));
 if(!xml.includes('<fb-share '))continue;
 assert.equal(config.usingComponents?.['fb-share']||app.usingComponents?.['fb-share'],'/components/fb-share/index',page+' must register the share host included by its WXML');
 assert.equal((xml.match(/<fb-share\s[^>]*id="product-share"/g)||[]).length,1,page+' must have exactly one share dialog');
 console.log('PASS '+page+' resolves one shared product-share component');
}
