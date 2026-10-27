const fs=require('node:fs'),assert=require('node:assert/strict'),{JSDOM}=require(process.env.JSDOM_PATH||'/tmp/flipbook-editor-dom/node_modules/jsdom');
const source=fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/supplier.inc.php'),'utf8');
const html=source.split('\n?>\n')[1].replace(/<\?php[\s\S]*?\?>/g,'fixture');
const dom=new JSDOM(html,{url:'http://127.0.0.1:8088/admin.php',runScripts:'outside-only'}),w=dom.window,$=s=>w.document.querySelector(s),all=s=>[...w.document.querySelectorAll(s)];
const suppliers=[{supplierid:1,name:'甲厂',code:'A',displayorder:0,contact:'甲联系人',phone:'',address:'',note:'甲备注',available:1},{supplierid:2,name:'乙厂',code:'B',displayorder:0,contact:'原联系人',phone:'',address:'',note:'保留原备注',available:1}];
const styles=[{productid:11,supplierid:1,name:'甲款式',code:'A-1',displayorder:0},{productid:22,supplierid:2,name:'乙款式',code:'B-1',displayorder:0}];
const requests=[];let rejectSave=false;
w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};w.confirm=()=>true;
$('[data-fb-supplier-workspace]').dataset.url='http://127.0.0.1:8088/admin.php?pmod=supplier';
w.fetch=async(url,options={})=>{
 const query=new URL(url).searchParams,action=query.get('supplier_action'),body=new URLSearchParams(options.body);requests.push({action,body:Object.fromEntries(body)});let result;
 if(action==='list'){
  const rows=suppliers.filter(r=>(!query.get('supplier_name')||r.name.includes(query.get('supplier_name')))&&(!query.get('supplier_code')||r.code.includes(query.get('supplier_code'))));const selected=suppliers.find(r=>r.supplierid===Number(query.get('selectedid')))||rows[0]||{};const products=styles.filter(r=>r.supplierid===selected.supplierid);result={success:true,suppliers:rows,count:rows.length,page:1,perpage:10,selected,styles:products,styleCount:products.length,stylePage:1};
 }else if(rejectSave){rejectSave=false;result={success:false,message:'模拟保存失败'};}
 else if(action==='supplier_save'){
  const row=suppliers.find(r=>r.supplierid===Number(body.get('id')));Object.assign(row,Object.fromEntries(body));row.supplierid=Number(row.supplierid);row.displayorder=Number(row.displayorder);result={success:true,id:row.supplierid,message:'供应商户已保存'};
 }else if(action==='style_save'){
  const product={productid:33,supplierid:Number(body.get('supplierid')),name:body.get('name'),code:body.get('code'),displayorder:Number(body.get('displayorder'))};styles.push(product);result={success:true,id:33,message:'款式已保存'};
 }else throw new Error('Unexpected action '+action);
 return {ok:true,text:async()=>'<?xml version="1.0"?><root><![CDATA['+JSON.stringify(result)+']]></root>'};
};
async function waitFor(check){for(let i=0;i<80;i++){if(check())return;await new Promise(resolve=>setTimeout(resolve,2));}throw new Error('Timed out waiting for state');}
function pass(name){console.log('PASS '+name);}
(async()=>{
 w.eval(fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/static/admin/crud.js'),'utf8'));
 await waitFor(()=>$('[data-supplier-selected]').textContent.includes('甲厂'));
 assert.equal($('[data-supplier-style-rows]').textContent.includes('甲款式'),true);assert.equal($('[data-supplier-style-rows]').textContent.includes('乙款式'),false);pass('initial supplier owns only its associated styles');
 all('[data-supplier-rows] tr')[1].click();await waitFor(()=>$('[data-supplier-selected]').textContent.includes('乙厂'));assert.equal($('[data-supplier-style-rows]').textContent.includes('乙款式'),true);assert.equal($('[data-supplier-style-rows]').textContent.includes('甲款式'),false);pass('switching supplier updates the right pane without mixed records');
 all('[data-supplier-rows] tr')[1].querySelector('button').click();const form=$('[data-supplier-edit]');assert.equal(form.elements.contact.value,'原联系人');assert.equal(form.elements.note.value,'保留原备注');const before=requests.filter(r=>r.action.endsWith('_save')).length;all('[data-supplier-close]')[1].click();assert(!$('[data-supplier-dialog]').open);assert.equal(requests.filter(r=>r.action.endsWith('_save')).length,before);pass('cancel does not save and editing retains existing optional supplier data');
 all('[data-supplier-rows] tr')[1].querySelector('button').click();form.elements.name.value='乙厂新名称';rejectSave=true;form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await waitFor(()=>$('[data-supplier-dialog-status]').textContent==='模拟保存失败');assert($('[data-supplier-dialog]').open);assert.equal(form.elements.name.value,'乙厂新名称');assert.equal(suppliers[1].name,'乙厂');pass('failed saves retain the open dialog and edited values');
 form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await waitFor(()=>!$('[data-supplier-dialog]').open&&$('[data-supplier-selected]').textContent.includes('乙厂新名称'));assert.equal(suppliers[1].contact,'原联系人');assert.equal(suppliers[1].note,'保留原备注');assert(all('.fb-supplier-pagination button').filter(b=>b.textContent==='上一页'||b.textContent==='下一页').every(b=>b.disabled));pass('successful modal saves preserve optional fields and disabled pagination limits');
 $('[data-style-add]').click();assert.equal($('[data-style-owner]').value,'乙厂新名称');assert($('[data-style-owner]').readOnly);assert.equal(form.elements.supplierid.value,'2');form.elements.name.value='新款式';form.elements.code.value='B-2';form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));await waitFor(()=>!$('[data-supplier-dialog]').open&&$('[data-supplier-style-rows]').textContent.includes('新款式'));const saved=requests.findLast(r=>r.action==='style_save');assert.equal(saved.body.supplierid,'2');assert.equal(saved.body.name,'新款式');pass('style creation fixes its supplier and appears only in that supplier pane');
 dom.window.close();
})().catch(e=>{console.error(e);dom.window.close();process.exitCode=1;});
