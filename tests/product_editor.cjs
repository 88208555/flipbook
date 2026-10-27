/* Code-level event tests only; this does not start a browser or alter business records. */
const fs=require('node:fs');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/static/admin/product.js'),'utf8');
function fixture(skus,chart,template){
 const nodes=new Map();
 const node=selector=>{if(!nodes.has(selector))nodes.set(selector,{value:'',checked:false,innerHTML:'',textContent:'',dataset:{},handlers:{},attributes:{},classList:{toggle(){}},setAttribute(name,value){this.attributes[name]=value;},addEventListener(name,fn){this.handlers[name]=fn;}});return nodes.get(selector);};
 const form=node('form');form.dataset={product:'900001',sizecharts:'http://localhost/plugin.php?id=flipbook:asset&op=sizecharts'};
 const heading=node('heading'),contexts=['gallery','sku','sizetable','cover','showcase','video','overlay'].map(context=>({dataset:{productContext:context},hidden:true}));heading.querySelectorAll=()=>contexts;
 const panels=['basic','showcase','cover','video','sku','highlight','detail','model','spec','sizetable','overlay'].map(panel=>({dataset:{productPanel:panel},hidden:panel!=='basic'}));
 form.querySelector=node;form.querySelectorAll=selector=>selector==='[data-product-panel]'?panels:[];
 node('[name=medialines]').value='';node('[name=skulines]').value=JSON.stringify(skus);node('[name=sizechart]').value=JSON.stringify(chart);
 vm.runInNewContext(source,{document:{querySelector:selector=>selector==='#fb-product-form'?form:heading,createElement(){throw Error('An upload must not begin above the image limit');}},URL,location:{href:'http://localhost/admin.php'},fetch:async()=>({text:async()=>JSON.stringify({ok:1,chart:template})}),console});
 return {form,node,heading,contexts,panels,chart:()=>JSON.parse(node('[name=sizechart]').value),skus:()=>JSON.parse(node('[name=skulines]').value)};
}
function click(f,selector,dataset={}){const button={dataset};f.form.handlers.click({target:{closest:query=>query===selector?button:null}});}
function check(value,message){assert.ok(value,message);console.log('PASS '+message);}
(async()=>{
 const original=[{skuid:1,code:'SPU-001',name:'白',color:'米白',size:'S',images:[]},{skuid:2,code:'SPU-002',name:'黑',color:'墨黑',size:'M',images:[]}];
 const chart={enabled:true,name:'旧表',columns:['胸围'],rows:[{size:'S',mark:'160',values:['104']},{size:'M',mark:'165',values:['']}],note:'旧提示'};
 const template={enabled:true,name:'已保存衬衫表',columns:['胸围','衣长'],rows:[{size:'S',mark:'160',values:['100','66']},{size:'XL',mark:'175',values:['122','72']}],note:'平铺测量'};
 const f=fixture(original,chart,template),reuse=f.node('#fb-size-reuse');reuse.value='900002';
 await reuse.handlers.change({target:reuse});
 const copied=f.chart();
 check(JSON.stringify(copied.rows.map(row=>row.size))===JSON.stringify(['S','M']),'reuse retains only the current product SKU sizes');
 check(copied.rows[0].mark==='160'&&copied.rows[0].values[1]==='66'&&copied.rows[1].values.every(value=>value===''),'reuse copies matching measurements and keeps unmatched current sizes blank');
 check(JSON.stringify(f.skus())===JSON.stringify(original),'copying a saved size chart never creates or edits SKU records');
 check(f.node('#fb-size-table').innerHTML.includes('规格：S')&&f.node('#fb-size-table').innerHTML.includes('value="160"'),'the editor renders the size mark together with its original SKU specification');
 click(f,'[data-size-column-right]',{sizeColumnRight:'0'});
 check(JSON.stringify(f.chart().columns)===JSON.stringify(['衣长','胸围'])&&JSON.stringify(f.chart().rows[0].values)===JSON.stringify(['66','100']),'moving a custom column moves every corresponding measurement');
 click(f,'[data-table-col-remove]',{tableColRemove:'0'});
 check(f.chart().columns.length===1&&JSON.stringify(f.chart().rows[0].values)===JSON.stringify(['100']),'deleting a custom column removes its corresponding cells');
 const beforeToggle=f.chart().rows;f.node('#fb-size-enabled').handlers.click();
 check(!f.chart().enabled&&JSON.stringify(f.chart().rows)===JSON.stringify(beforeToggle),'turning off the chart keeps all editor measurements');
 check(f.node('#fb-size-enabled').attributes['aria-checked']==='false','the single accessible switch exposes its checked state without a second checkbox');
 const beforePaste=JSON.stringify(f.chart());
 const target={dataset:{sizeRow:'0',sizeCell:'0'},hasAttribute:name=>name==='data-size-cell'};
 let prevented=false;f.form.handlers.paste({target,clipboardData:{getData:()=>"1\t2"},preventDefault(){prevented=true;}});
 check(prevented&&JSON.stringify(f.chart())===beforePaste&&f.node('[role=status]').textContent.includes('超出'),'oversized paste is rejected without replacing any cell');
 const noSizes=fixture([{code:'SPU-003',size:'',images:[]}],chart,template);
 check(noSizes.node('#fb-size-table').innerHTML.includes('已保留原有尺码表')&&noSizes.chart().rows.length===2,'legacy measurements remain present when SKU sizes have not been configured');
 const full=fixture([{code:'SPU-004',size:'S',images:Array.from({length:20},(_,i)=>'/original-'+i+'.png')}],chart,template);
 const skuNode={dataset:{sku:'0'}};full.form.handlers.click({target:{closest:query=>query==='[data-sku]'?skuNode:query==='[data-sku-upload]'?{}:null}});
 check(full.skus()[0].images.length===20&&full.node('[role=status]').textContent.includes('最多 20'),'the 21st SKU upload is blocked before opening a file chooser');
 check(full.contexts.every(button=>button.hidden),'the basic tab hides unrelated title-row actions');
 click(full,'[data-panel]',{panel:'sku'});
 check(full.contexts.filter(button=>!button.hidden).map(button=>button.dataset.productContext).join(',')==='sku','SKU tab exposes only its title-row add action');
 const count=full.skus().length;full.heading.handlers.click({target:{closest:selector=>selector==='[data-sku-add]'?{}:null}});
 check(full.skus().length===count+1,'the title-row SKU action remains connected to the product editor');
 click(full,'[data-panel]',{panel:'detail'});
 check(full.contexts.filter(button=>!button.hidden).map(button=>button.dataset.productContext).join(',')==='gallery'&&full.contexts[0].dataset.galleryAdd==='detail','image group actions follow the active tab and target the correct group');
 check(!full.node('#fb-product-skus').innerHTML.includes('<svg')&&full.node('#fb-product-skus').innerHTML.includes('>替换</button>'),'image and SKU operations use text buttons without leading icons');
})().catch(error=>{console.error(error);process.exitCode=1;});
