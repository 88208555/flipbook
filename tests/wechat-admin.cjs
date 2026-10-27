// Execute the real WeChat form controller without launching a browser.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../app/source/plugin/flipbook/static/admin/wechat.js'),'utf8');
function node(dataset={}){return {dataset,value:'',hidden:false,disabled:false,handlers:{},attributes:{},textContent:'',classList:{values:new Set(),toggle(key,on){on?this.values.add(key):this.values.delete(key);}},addEventListener(key,fn){this.handlers[key]=fn;},setAttribute(key,value){this.attributes[key]=value;},focus(){document.activeElement=this;}};}
const keys=['login','develop','push','subscribe','orders','trade','customer','server','release'],tabs=keys.map(key=>node({fbWechatTab:key})),panels=keys.map(key=>node({fbWechatPanel:key}));
const active=node();active.value='push';const generalSave=node();
const token=node({fbCount:'32'});token.id='fb-wechat_push_token';const aes=node({fbCount:'43'});aes.id='fb-wechat_push_aes';
const tokenCount=node(),aesCount=node();const input=node();input.id='url';input.value='http://127.0.0.1:8088/plugin.php?id=flipbook:wechat_callback';input.selectionStart=0;input.selectionEnd=0;input.select=function(){this.selected=true;};input.setSelectionRange=function(a,b){this.selectionStart=a;this.selectionEnd=b;};
const copy=node({fbCopy:'url'}),copyStatus=node(),emptyInput=node();emptyInput.id='socket';const emptyCopy=node({fbCopy:'socket'}),emptyStatus=node();
const names=new Map([['url',input],['socket',emptyInput]]),selectors=new Map([['#fb-wechat-active-tab',active],['[data-wechat-general-save]',generalSave],['[data-fb-count-for="'+token.id+'"]',tokenCount],['[data-fb-count-for="'+aes.id+'"]',aesCount],['[data-fb-copy-status="url"]',copyStatus],['[data-fb-copy-status="socket"]',emptyStatus]]);
const form={handlers:{},querySelector:q=>selectors.get(q),querySelectorAll:q=>({'[data-fb-wechat-tab]':tabs,'[data-fb-wechat-panel]':panels,'[data-fb-count]':[token,aes],'[data-fb-copy]':[copy,emptyCopy]}[q]||[]),addEventListener(key,fn){this.handlers[key]=fn;}};
let writes=[],reads=0,execCalls=0,execResult=true;
const navigator={clipboard:{writeText:async text=>{writes.push(text);},readText(){reads++;throw new Error('clipboard reading forbidden');}}};
const document={activeElement:copy,getElementById:id=>id==='cpform'?form:names.get(id),execCommand(action){assert.equal(action,'copy');execCalls++;return execResult;}};
vm.runInNewContext(source,{document,navigator,Array,Error});
function check(value,message){assert.ok(value,message);console.log('PASS '+message);}
(async()=>{
 check(tokenCount.textContent==='0/32'&&aesCount.textContent==='0/43','new secret counters start at zero');
 token.value='T'.repeat(32);token.handlers.input();aes.value='K'.repeat(43);aes.handlers.input();
 check(tokenCount.textContent==='32/32'&&aesCount.textContent==='43/43','Token and AES counters track the actual new input');
 tabs[keys.indexOf('server')].handlers.click();check(active.value==='server'&&panels.filter(panel=>!panel.hidden).length===1&&!panels[keys.indexOf('server')].hidden,'server tab switches the actual panel and saved active-tab field');
 check([token,aes].every(field=>!field.disabled)&&token.value.length===32&&aes.value.length===43,'tab switching keeps inputs enabled and preserves unsaved values');
 let prevented=false;tabs[keys.indexOf('server')].handlers.keydown({key:'Home',preventDefault(){prevented=true;}});check(prevented&&active.value==='login'&&document.activeElement===tabs[0],'keyboard Home selects and focuses the first tab');
 tabs[0].handlers.keydown({key:'ArrowLeft',preventDefault(){}});check(active.value==='release','keyboard navigation wraps across all nine tabs');
 for(const key of ['subscribe','orders','trade','customer','release']){tabs[keys.indexOf(key)].handlers.click();check(generalSave.hidden&&panels.filter(panel=>!panel.hidden).length===1,'separate '+key+' controls hide the unrelated general save action');}
 tabs[0].handlers.click();check(!generalSave.hidden,'information tab restores its general save action');
 form.handlers.invalid({target:{closest:()=>panels[2]}});check(active.value==='push'&&!panels[2].hidden,'an invalid hidden input opens its configuration tab for correction');
 await copy.handlers.click();check(writes.length===1&&writes[0]===input.value&&copyStatus.textContent==='已复制'&&!copy.disabled&&execCalls===0,'copy uses Clipboard writeText and confirms success');
 navigator.clipboard.writeText=async()=>{throw new Error('denied');};document.activeElement=tabs[0];
 await copy.handlers.click();check(execCalls===1&&input.selected&&copyStatus.textContent==='已复制'&&document.activeElement===tabs[0],'denied Clipboard permission falls back to selecting the readonly field and restores focus');
 navigator.clipboard=undefined;await copy.handlers.click();check(execCalls===2&&copyStatus.textContent==='已复制','older environments use the same selection-copy fallback');
 execResult=false;await copy.handlers.click();check(copyStatus.textContent==='复制失败，请选中地址后手动复制'&&!copy.disabled,'copy failure is explicit and leaves the action available');
 await emptyCopy.handlers.click();check(emptyStatus.textContent==='没有可复制的地址'&&execCalls===3,'unused socket domain cannot fabricate copied data');
 check(reads===0&&!source.includes('.readText(')&&!source.includes('clipboard.read('),'no operation reads the clipboard');
})().catch(error=>{console.error(error);process.exitCode=1;});
