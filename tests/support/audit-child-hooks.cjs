// Preload only for the audit runner: isolate real PHP fixtures before their first plugin operation.
const cp=require('node:child_process'),fs=require('node:fs');
const marker='C::app()->init();';
function isolate(input){
 const value=Buffer.isBuffer(input)?input.toString():input;
 if(typeof value!=='string'||!value.includes(marker))return input;
 return value.replace(marker,marker+"require '/tmp/flipbook-audit-tables.php';");
}
for(const method of ['execFileSync','spawnSync']){
 const original=cp[method];
 cp[method]=function(command,args,options){
  if(command==='docker'&&Array.isArray(args)&&args.includes('flipbook-php')&&args.includes('php')&&options&&options.input){options={...options,input:isolate(options.input)};}
  return original.call(this,command,args,options);
 };
}
module.exports={isolate};
