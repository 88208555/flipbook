// Code-level list-selection behavior; no browser or persistence is used.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/static/admin/crud.js'),'utf8');
const rows=[{checked:false},{checked:false}],all={checked:false,indeterminate:false},remove={disabled:false};
const form={id:'fb-crud-product-list',handlers:{},querySelector:()=>all,querySelectorAll:()=>rows,addEventListener(name,fn){this.handlers[name]=fn;}};
vm.runInNewContext(source,{document:{querySelectorAll:selector=>selector==='form[data-fb-crud-list]'?[form]:[],querySelector:()=>remove}});
function check(ok,message){assert.ok(ok,message);console.log('PASS '+message);}
check(remove.disabled&&!all.checked,'title-row batch deletion starts disabled with no selected record');
rows[0].checked=true;form.handlers.change({target:rows[0]});
check(!remove.disabled&&all.indeterminate,'selecting one record enables the title-row action and updates select-all');
all.checked=true;form.handlers.change({target:all});
check(rows.every(row=>row.checked)&&all.checked&&!all.indeterminate,'the table-header select-all control selects the actual record checkboxes');
let prevented=false;form.handlers.submit({preventDefault(){prevented=true;}});
check(!prevented,'selected records can submit through the title-row action');
all.checked=false;form.handlers.change({target:all});form.handlers.submit({preventDefault(){prevented=true;}});
check(remove.disabled&&rows.every(row=>!row.checked)&&prevented,'empty batch deletion is blocked and cannot become an accidental empty save');
