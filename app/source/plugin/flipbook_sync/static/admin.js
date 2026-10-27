'use strict';
{
 const root=document.querySelector('[data-sync-page]');
 if(root){
 document.body.setAttribute('data-fb-admin','');
 // The native plugin heading uses the same three links; the local navigation is canonical.
 document.querySelectorAll('.itemtitle').forEach(function(heading){
  if(Array.from(heading.querySelectorAll('a')).some(a=>{try{return new URL(a.href,location.href).searchParams.get('identifier')==='flipbook_sync';}catch(e){return false;}})){
   heading.hidden=true;const top=heading.closest('.floattop');if(top){top.hidden=true;const next=top.nextElementSibling;if(next&&next.classList.contains('floattopempty'))next.hidden=true;}
  }
 });
 const status=root.querySelector('[data-sync-status]');
 const notice=(message,error=false)=>{status.textContent=message;status.classList.toggle('is-error',error);};
 let busy=false,stop=false,jobid='';
 async function request(action,values,key){
  const body=new URLSearchParams(Object.assign({},values,{sync_action:action,syncsubmit:'1',formhash:root.dataset.formhash}));
  const headers={'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'};
  if(key)headers['X-Flipbook-Source-Key']=key;
  const target=new URL(root.dataset.endpoint,location.href);target.searchParams.set('inajax','1');
  const response=await fetch(target.pathname+target.search,{method:'POST',credentials:'same-origin',headers,body});
  let data;try{data=await response.json();}catch(e){throw new Error('会话已过期或服务暂不可用，请重新登录后台');}
  if(!response.ok||!data.ok)throw new Error(data.message||'操作失败');
  return data;
 }
 async function copy(control){
  const value=control.value;
  if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(value);return;}
  control.select();const ok=document.execCommand('copy');if(!ok)throw new Error('复制失败，请选中内容后手动复制');
 }
 function open(kind,data){
  const dialog=root.querySelector(kind==='grant'?'[data-grant-dialog]':'[data-source-dialog]'),form=dialog.querySelector('form');
  form.reset();form.elements.recordid.value=data?(data.clientid||data.sourceid):'0';form.querySelector('[data-form-status]').textContent='';
  for(const name of ['domain','note','ips','name','endpoint'])if(form.elements[name]){form.elements[name].value=data&&data[name]||'';form.elements[name].readOnly=!!data&&['domain','endpoint'].includes(name);}
  form.querySelectorAll('[name="fields[]"]').forEach(input=>{input.checked=!data||!Array.isArray(data.fields)||data.fields.includes(input.value);});
  form.querySelector('[data-dialog-title]').textContent=data?(kind==='grant'?'编辑授权':'编辑数据源'):(kind==='grant'?'新增授权':'添加数据源');
  if(kind==='grant'){const fields=JSON.parse(dialog.dataset.sharedFields||'[]');form.querySelectorAll('[name="fields[]"]').forEach(input=>{input.checked=fields.includes(input.value);input.disabled=false;});}
  dialog.showModal();
 }
 function issued(grant){
  const panel=root.querySelector('[data-sync-issued]');panel.hidden=false;panel.querySelector('[data-issued-key]').value=grant.key;panel.scrollIntoView({block:'nearest'});
 }
 root.querySelectorAll('[data-sync-form]').forEach(form=>form.addEventListener('submit',async function(event){
  event.preventDefault();const output=form.querySelector('[data-form-status]'),button=form.querySelector('[type=submit]');if(button.disabled)return;
  button.disabled=true;output.textContent='正在保存…';
  const formData=new FormData(form),values=Object.fromEntries(formData),kind=form.dataset.syncForm,key=(form.querySelector('[data-source-key]')||{}).value||'';
  if(kind==='grant'){delete values['fields[]'];values.fields=JSON.stringify(formData.getAll('fields[]'));}
  const action=kind==='source'?'source-save':Number(values.recordid)?'key-update':'key-create';
  try{
   const data=await request(action,values,key);form.closest('dialog').close();notice(data.grant?'':data.message);
   if(kind==='grant')form.closest('dialog').dataset.sharedFields=values.fields;
   if(data.grant){form.reset();issued(data.grant);}else location.reload();
  }catch(error){output.textContent=error.message;}finally{button.disabled=false;}
 }));
 async function synchronise(source){
  if(busy)return;busy=true;stop=false;
  const panel=root.querySelector('[data-sync-progress]'),count=panel.querySelector('[data-sync-count]'),meter=panel.querySelector('progress'),stopButton=panel.querySelector('[data-sync-stop]');
  panel.hidden=false;stopButton.disabled=false;notice('正在连接来源站点…');
  const starts=Array.from(root.querySelectorAll('[data-sync-start]')).map(button=>[button,button.disabled]);
  starts.forEach(([button])=>button.disabled=true);
  try{
   let data=await request('job-start',{recordid:source});jobid=data.job.jobid;
   while(data.job.status==='running'&&!stop){
    data=await request('job-step',{jobid});const job=data.job;
    meter.max=Math.max(1,Number(job.total));meter.value=Number(job.processed);
    count.textContent='已处理 '+job.processed+' / '+job.total+'，新增 '+job.added+'，更新 '+job.changed+'，未变化 '+job.skipped;
    notice(job.status==='complete'?'同步完成':'正在同步…');
    if(job.status==='complete'&&job.updated){const cell=root.querySelector('[data-source-last="'+source+'"]');if(cell)cell.textContent=new Date(Number(job.updated)*1000).toLocaleString();}
   }
   if(stop&&data.job.status==='running'){await request('job-pause',{jobid});notice('已停止后续批次，可再次点击一键同步继续。');}
   if(data.job.status==='failed')throw new Error(data.job.message||'同步失败');
  }catch(error){notice(error.message+'；修正后可再次点击一键同步继续。',true);}
  finally{busy=false;stopButton.disabled=true;starts.forEach(([button,disabled])=>button.disabled=disabled);}
 }
 let entityKind='product',matchRemote='',matchMapping={};
 let catalogKind='local',localClient='0',remoteSource='';
 let browseSource='0',catalogCursor=0,catalogNext=0,catalogHistory=[],catalogMore=false,catalogBusy=false;
 const catalogRows=root.querySelector('[data-catalog-rows]');
 function catalogControls(){
  root.querySelectorAll('[data-catalog-kind]').forEach(button=>{button.disabled=catalogBusy;button.classList.toggle('is-active',button.dataset.catalogKind===catalogKind);button.setAttribute('aria-pressed',String(button.dataset.catalogKind===catalogKind));});
  root.querySelectorAll('[data-browse-source]').forEach(button=>{button.disabled=catalogBusy||button.dataset.sourceDisabled==='1';button.classList.toggle('is-active',button.dataset.browseSource===remoteSource);button.setAttribute('aria-pressed',String(button.dataset.browseSource===remoteSource));});
  root.querySelectorAll('[data-publish-product]').forEach(button=>button.disabled=catalogBusy||button.dataset.matchReady==='0');
  root.querySelectorAll('[data-entity-kind]').forEach(button=>{button.disabled=catalogBusy;button.classList.toggle('is-active',button.dataset.entityKind===entityKind);});
  const prev=root.querySelector('[data-catalog-prev]'),next=root.querySelector('[data-catalog-next]'),refresh=root.querySelector('[data-catalog-refresh]');
  if(prev)prev.disabled=catalogBusy||!catalogHistory.length;if(next)next.disabled=catalogBusy||!catalogMore;if(refresh)refresh.disabled=catalogBusy||!browseSource;
 }
 function catalogCell(row,text){const cell=document.createElement('td');cell.textContent=text;row.appendChild(cell);return cell;}
 function renderCatalog(items){
  catalogRows.replaceChildren();
  for(const item of items){
   const row=document.createElement('tr'),cover=catalogCell(row,'');
   if(typeof item.cover==='string'&&/^https?:\/\//i.test(item.cover)){const img=document.createElement('img');img.src=item.cover;img.alt='';img.loading='lazy';img.className='fb-sync-product-cover';cover.appendChild(img);}else cover.textContent=Object.hasOwn(item,'cover')?'无图片':'未授权';
   const title=catalogCell(row,typeof item.name==='string'?item.name:'名称未授权'),code=document.createElement('small');code.className='fb-sync-catalog-code';code.textContent=typeof item.code==='string'?item.code:'编码未授权';title.appendChild(code);
   catalogCell(row,Object.hasOwn(item,'pricevalue')?String(item.pricevalue)+(typeof item.currency==='string'?' '+item.currency:''):(catalogKind==='remote'&&entityKind!=='product'?'—':'未授权'));
   catalogCell(row,Object.hasOwn(item,'available')?(Number(item.available)?'上架 / 启用':'草稿 / 停用'):'未授权');
   const local=catalogCell(row,item.in_draft?'已进入草稿箱':Number(item.local_productid)?'已同步到本站':'未同步');local.setAttribute('data-local-product',String(item.recordid||item.productid));
   const actions=catalogCell(row,''),button=document.createElement('button');button.type='button';button.className='fb-admin-button';button.dataset.publishProduct=String(item.recordid||item.productid);button.textContent='一键上架';button.dataset.matchReady=item.match&&item.match.ok?'1':'0';if(catalogKind==='remote'){actions.appendChild(button);const configure=document.createElement('button');configure.type='button';configure.className='fb-admin-button';configure.dataset.matchRecord=String(item.recordid||item.productid);configure.textContent='字段匹配';actions.appendChild(configure);for(const issue of item.match&&item.match.issues||[]){const text=document.createElement('small');text.className='fb-sync-match-issue is-error';text.textContent=issue.field+'：'+issue.message;actions.appendChild(text);}}else actions.textContent='本地产品';catalogRows.appendChild(row);
  }
  if(!items.length){const row=document.createElement('tr'),cell=catalogCell(row,'该站暂无可调用商品');cell.colSpan=6;catalogRows.appendChild(row);}
 }
 async function loadCatalog(cursor=0,history=[],resetPolicy=false){
  if(catalogBusy||!browseSource)return false;catalogBusy=true;catalogControls();
  const output=root.querySelector('[data-catalog-status]');output.classList.remove('is-error');output.textContent='正在读取该站已授权商品…';catalogRows.replaceChildren();
  try{
   const data=await request(catalogKind==='local'?'local-products':'source-products',{recordid:browseSource,cursor,kind:catalogKind==='local'?'product':entityKind}),list=data.catalog;
   if(!list||!Array.isArray(list.items)||typeof list.has_more!=='boolean')throw new Error('来源商品列表格式无效');

   renderCatalog(list.items);catalogCursor=cursor;catalogNext=Number(list.next_cursor);catalogMore=list.has_more;catalogHistory=history;
   root.querySelector('[data-catalog-page]').textContent='第 '+(history.length+1)+' 页';output.textContent=catalogKind==='local'&&!list.local_enabled?'外部调用已关闭':'';return true;
  }catch(error){catalogMore=false;output.classList.add('is-error');output.textContent=error.message;return false;}finally{catalogBusy=false;catalogControls();}
 }
 async function publishProduct(button){
  if(catalogBusy||!browseSource||catalogKind!=='remote')return;catalogBusy=true;catalogControls();
  const output=root.querySelector('[data-catalog-status]');output.textContent='正在加入草稿箱…';
  try{const data=await request('source-publish',{recordid:browseSource,productid:button.dataset.publishProduct,kind:entityKind});const cell=root.querySelector('[data-local-product="'+button.dataset.publishProduct+'"]');if(cell)cell.textContent='已进入草稿箱';output.textContent=data.message;}
  catch(error){output.textContent=error.message;}finally{catalogBusy=false;catalogControls();}
 }
 async function openMatch(remote){
  if(catalogBusy)return;catalogBusy=true;catalogControls();
  const dialog=root.querySelector('[data-match-dialog]'),body=dialog.querySelector('[data-match-fields]'),output=dialog.querySelector('[data-match-status]');body.replaceChildren();output.textContent='正在读取匹配状态…';dialog.showModal();
  try{
   const data=await request('source-match',{recordid:browseSource,productid:remote,kind:entityKind});matchRemote=remote;matchMapping=data.mapping;output.textContent=data.match.ok?'字段匹配通过。':data.match.issues.map(issue=>issue.field+'：'+issue.message).join('；');
   for(const relation of data.match.relations){
    if(!relation.remoteid)continue;
    const label=document.createElement('label'),title=document.createElement('span'),select=document.createElement('select');label.className='fb-sync-match-field';title.textContent=({category:'商品分类',series:'商品系列',supplier:'供应商户'}[relation.kind])+' · '+relation.name;
    select.dataset.matchKind=relation.kind;select.dataset.matchRemote=String(relation.remoteid);
    select.add(new Option('请选择同类型记录或新建',''));select.add(new Option('新建 '+relation.name,'new'));
    for(const choice of data.choices[relation.kind]||[])select.add(new Option(choice.name,String(choice.id)));
    select.value=relation.status==='new'?'new':relation.targetid?String(relation.targetid):'';label.append(title,select);body.appendChild(label);
   }
   const related=new Set(['category','series','supplier','category_path']);
   for(const issue of data.match.issues){
    if(related.has(issue.field)||body.querySelector('[data-map-from="'+CSS.escape(issue.field)+'"]'))continue;
    const label=document.createElement('label'),title=document.createElement('span'),select=document.createElement('select'),custom=document.createElement('input');label.className='fb-sync-match-field';title.textContent=issue.field+'：'+issue.message;
    select.dataset.mapFrom=issue.field;select.add(new Option('保留原字段（未解决时禁止导入）',''));
    for(const field of data.fields)select.add(new Option(field.title,field.key));
    if(entityKind==='product')select.add(new Option('新增商品自定义属性','custom'));
    custom.type='text';custom.maxLength=60;custom.placeholder='填写自定义属性名称';custom.hidden=true;custom.dataset.mapCustom=issue.field;
    select.addEventListener('change',()=>custom.hidden=select.value!=='custom');label.append(title,select,custom);body.appendChild(label);
   }
   dialog.querySelector('[type=submit]').disabled=false;
  }catch(error){output.textContent=error.message;dialog.querySelector('[type=submit]').disabled=true;}finally{catalogBusy=false;catalogControls();}
 }
 const matchForm=root.querySelector('[data-match-form]');
 if(matchForm)matchForm.addEventListener('submit',async event=>{
  event.preventDefault();const button=matchForm.querySelector('[type=submit]'),output=matchForm.querySelector('[data-match-status]');if(button.disabled)return;button.disabled=true;
  try{
   const choices=[];for(const select of matchForm.querySelectorAll('[data-match-kind]')){if(!select.value)throw new Error('请选择每个关联记录，或明确新建。');choices.push({kind:select.dataset.matchKind,remoteid:Number(select.dataset.matchRemote),mode:select.value==='new'?'new':'existing',targetid:select.value==='new'?0:Number(select.value)});}
   const mapping=Object.assign({},matchMapping);for(const select of matchForm.querySelectorAll('[data-map-from]')){if(!select.value)continue;const field=select.dataset.mapFrom;mapping[field]=select.value==='custom'?'custom:'+matchForm.querySelector('[data-map-custom="'+CSS.escape(field)+'"]').value.trim():select.value;}
   await request('match-save',{recordid:browseSource,kind:entityKind,choices:JSON.stringify(choices),mapping:JSON.stringify(mapping)});matchForm.closest('dialog').close();await loadCatalog(catalogCursor,catalogHistory);
  }catch(error){output.textContent=error.message;}finally{button.disabled=false;}
 });
 root.querySelectorAll('[data-browse-source]').forEach(button=>button.dataset.sourceDisabled=button.disabled?'1':'0');
 root.addEventListener('click',async function(event){
  const button=event.target.closest('button');if(!button||!root.contains(button)||button.disabled)return;
  if(button.hasAttribute('data-sync-help')){const template=root.querySelector('[data-help-template="'+button.dataset.syncHelp+'"]'),dialog=root.querySelector('[data-help-dialog]');if(template&&dialog){dialog.querySelector('[data-help-content]').replaceChildren(template.content.cloneNode(true));dialog.showModal();}return;}
  if(button.hasAttribute('data-help-close')){button.closest('dialog').close();return;}
  if(button.hasAttribute('data-fields-all')){const form=button.closest('form');form.querySelectorAll('[name="fields[]"]').forEach(input=>input.checked=button.dataset.fieldsAll==='1');return;}
  if(button.hasAttribute('data-catalog-kind')){catalogKind=button.dataset.catalogKind;root.querySelector('[data-local-catalog]').hidden=catalogKind!=='local';root.querySelector('[data-remote-catalog]').hidden=catalogKind!=='remote';if(catalogKind==='remote'&&!remoteSource){const first=root.querySelector('[data-browse-source]:not(:disabled)');remoteSource=first?first.dataset.browseSource:'';}browseSource=catalogKind==='local'?localClient:remoteSource;catalogHistory=[];catalogCursor=0;if(browseSource)await loadCatalog(0,[],true);else {catalogRows.replaceChildren();root.querySelector('[data-catalog-status]').classList.remove('is-error');root.querySelector('[data-catalog-status]').textContent='暂无可用的外部数据源';catalogMore=false;catalogControls();}return;}
  if(button.hasAttribute('data-browse-source')){remoteSource=button.dataset.browseSource;browseSource=remoteSource;catalogHistory=[];catalogCursor=0;await loadCatalog();return;}
  if(button.hasAttribute('data-entity-kind')){entityKind=button.dataset.entityKind;catalogHistory=[];catalogCursor=0;await loadCatalog();return;}
  if(button.hasAttribute('data-match-record')){await openMatch(button.dataset.matchRecord);return;}
  if(button.hasAttribute('data-catalog-refresh')){await loadCatalog(catalogCursor,catalogHistory);return;}
  if(button.hasAttribute('data-catalog-next')){await loadCatalog(catalogNext,catalogHistory.concat([catalogCursor]));return;}
  if(button.hasAttribute('data-catalog-prev')){const history=catalogHistory.slice(),cursor=history.pop();await loadCatalog(cursor,history);return;}
  if(button.hasAttribute('data-publish-product')){await publishProduct(button);return;}
  if(button.hasAttribute('data-sync-new')){open(button.dataset.syncNew);return;}
  if(button.hasAttribute('data-sync-cancel')){button.closest('dialog').close();return;}
  if(button.hasAttribute('data-edit-grant')){open('grant',JSON.parse(button.dataset.editGrant));return;}
  if(button.hasAttribute('data-edit-source')){open('source',JSON.parse(button.dataset.editSource));return;}
  if(button.hasAttribute('data-sync-stop')){stop=true;button.disabled=true;notice('将在当前批次完成后停止…');return;}
  if(button.hasAttribute('data-sync-start')){await synchronise(button.dataset.syncStart);return;}
  if(button.hasAttribute('data-refresh-list')){location.reload();return;}
  if(button.hasAttribute('data-dismiss-issued')){const panel=button.closest('[data-sync-issued]');panel.querySelector('input').value='';panel.hidden=true;location.reload();return;}
  const action=button.dataset.syncAction;
  try{
   if(button.hasAttribute('data-copy-issued')){notice('正在复制…');await copy(root.querySelector('[data-issued-key]'));notice('密钥已复制');return;}
   if(button.hasAttribute('data-copy-api')){notice('正在复制…');await copy(root.querySelector('[data-api-address]'));notice('接口地址已复制');return;}
   if(!action)return;
   if(action==='key-rotate'&&!window.confirm('重新生成后，旧密钥立即失效。确定重新生成？'))return;
   button.disabled=true;notice('正在处理…');const data=await request(action,{recordid:button.dataset.record});notice(data.grant?'':data.message);
   if(data.grant)issued(data.grant);else if(action!=='source-test')location.reload();
  }catch(error){notice(error.message,true);}finally{button.disabled=false;}
 });
 if(catalogRows)loadCatalog(0,[],true);
 window.addEventListener('beforeunload',function(event){if(busy){event.preventDefault();event.returnValue='';}});
 }
}
