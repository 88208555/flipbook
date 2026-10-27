(function(){
 'use strict';
 function el(tag,text){var node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;}
 async function request(root,action,body){
  var url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('fb_wechat_action',action);
  var options={credentials:'same-origin',cache:'no-store'};
  if(body){options.method='POST';body.set('formhash',root.dataset.formhash);options.body=body;}
  var response=await fetch(url.href,options),raw=await response.text(),data;
  try{data=JSON.parse(raw);}catch(error){var start=raw.indexOf('{"ok"'),end=raw.lastIndexOf('}');if(start<0)throw Error('管理会话已过期或服务返回异常，请刷新页面');data=JSON.parse(raw.slice(start,end+1));}
  if(!data.ok)throw Error(data.message||'操作失败');return data;
 }
 function wireTabs(root,tabAttr,panelAttr){
  var tabs=Array.from(root.querySelectorAll('['+tabAttr+']')),panels=Array.from(root.querySelectorAll('['+panelAttr+']'));
  function select(key,focus){tabs.forEach(function(tab){var on=tab.getAttribute(tabAttr)===key;tab.classList.toggle('is-active',on);tab.setAttribute('aria-selected',String(on));tab.tabIndex=on?0:-1;if(on&&focus)tab.focus();});panels.forEach(function(panel){panel.hidden=panel.getAttribute(panelAttr)!==key;});}
  tabs.forEach(function(tab,index){tab.addEventListener('click',function(){select(tab.getAttribute(tabAttr),false);});tab.addEventListener('keydown',function(event){var next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;else if(event.key==='ArrowLeft')next=(index+tabs.length-1)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();select(tabs[next].getAttribute(tabAttr),true);});});return select;
 }
 document.querySelectorAll('[data-close-dialog]').forEach(function(button){button.addEventListener('click',function(){button.closest('dialog').close();});});
 var release=document.querySelector('[data-release]');
 if(release){
  var selectRelease=wireTabs(release,'data-release-tab','data-release-panel'),rows=release.querySelector('[data-release-rows]'),status=release.querySelector('[data-release-status]'),configStatus=release.querySelector('[data-release-config-status]'),page=1,size=10,loading=false,timer=null,records=[],requestId='';
  var labels={queued:'等待检查',checking:'自动检查中',compiling:'编译打包中',uploading:'上传微信中',uploaded:'上传成功',failed:'上传失败',interrupted:'结果待核对'};
  function details(job){var content=release.querySelector('[data-release-detail-content]');content.replaceChildren(el('h3','版本 '+job.version),el('p',job.message||labels[job.status]||job.status));var list=el('ul');(job.checks||[]).forEach(function(item){list.appendChild(el('li',(item.success?'通过：':'提示：')+item.name+(item.text?' · '+item.text:'')));});content.appendChild(list);release.querySelector('[data-release-detail]').showModal();}
  function paint(data){
   records=data.rows||[];rows.replaceChildren();
   records.forEach(function(job){var tr=el('tr');[job.version,labels[job.status]||job.status,job.source==='local'?'本地上传':'云端源码',new Date(job.created).toLocaleString('zh-CN',{hour12:false}),job.description||'—'].forEach(function(text){tr.appendChild(el('td',text));});var action=el('td'),buttons=el('div');buttons.className='fb-wechat-row-actions';var detail=el('button',job.status==='failed'?'失败原因':'查看详情');detail.type='button';detail.className='fb-admin-button';detail.onclick=function(){details(job);};buttons.appendChild(detail);
    if(['uploaded','failed','interrupted'].includes(job.status)){var remove=el('button','移除记录');remove.type='button';remove.className='fb-admin-button';remove.onclick=async function(){if(!confirm('仅移除此处版本记录，微信上的代码版本保留。'))return;remove.disabled=true;var body=new FormData();body.set('id',job.id);try{await request(release,'remove',body);await load();}catch(error){status.textContent=error.message;remove.disabled=false;}};buttons.appendChild(remove);}action.appendChild(buttons);tr.appendChild(action);rows.appendChild(tr);
   });
   if(!records.length){var empty=el('tr'),cell=el('td','暂无发布记录');cell.colSpan=6;empty.appendChild(cell);rows.appendChild(empty);}
   page=data.page||1;release.querySelector('[data-release-total]').textContent='共 '+(data.total||0)+' 条';release.querySelector('[data-release-page]').textContent='第 '+page+' 页';release.querySelector('[data-release-prev]').disabled=page<=1;release.querySelector('[data-release-next]').disabled=page*size>=(data.total||0);
   if(records.some(function(job){return !['uploaded','failed','interrupted'].includes(job.status);}))timer=setTimeout(load,2500);
  }
  async function load(){if(loading||release.dataset.configured!=='1')return;clearTimeout(timer);loading=true;try{var url=new URL(release.dataset.endpoint,location.href);url.searchParams.set('page',page);url.searchParams.set('pageSize',size);var old=release.dataset.endpoint;release.dataset.endpoint=url.href;try{paint(await request(release,'versions'));}finally{release.dataset.endpoint=old;}}catch(error){status.textContent=error.message;}finally{loading=false;}}
  release.querySelector('[data-release-refresh]').onclick=load;release.querySelector('[data-release-prev]').onclick=function(){page--;load();};release.querySelector('[data-release-next]').onclick=function(){page++;load();};release.querySelector('[data-release-size]').onchange=function(event){size=Number(event.target.value);page=1;load();};
  release.querySelector('[data-release-save]').onclick=async function(event){
   var button=event.currentTarget,body=new FormData();button.disabled=true;configStatus.textContent='正在保存…';
   release.querySelectorAll('[data-release-setting]').forEach(function(input){body.set(input.dataset.releaseSetting,input.value);});release.querySelectorAll('[data-release-clear]').forEach(function(input){if(input.checked)body.set('clear_'+input.dataset.releaseClear,'1');});
   var key=release.querySelector('[data-release-private-key]').files[0];if(key)body.set('private_key',key);
   try{var data=await request(release,'release-save',body);release.dataset.configured=data.configured?'1':'0';release.querySelector('[data-release-setting="wechat_ci_token"]').value='';release.querySelector('[data-release-private-key]').value='';release.querySelector('[data-release-key-status]').textContent=body.has('clear_wechat_upload_private_key')?'尚未配置':key?'已配置；未选择新文件则保留':release.querySelector('[data-release-key-status]').textContent;release.querySelectorAll('[data-release-clear]').forEach(function(input){input.checked=false;});release.querySelector('[data-release-version]').value=body.get('wechat_release_version');release.querySelector('[data-release-description]').value=body.get('wechat_release_description');var appid=document.getElementById('fb-wechat_appid');if(appid)appid.value=body.get('wechat_appid');configStatus.textContent=data.message;page=1;await load();}catch(error){configStatus.textContent=error.message;}finally{button.disabled=false;}
  };
  var archive=release.querySelector('[data-release-archive]'),buttons=release.querySelectorAll('[data-release-start],[data-release-local]');
  async function start(source,file){
   if(release.dataset.configured!=='1'){status.textContent='请先完成上传代码配置';selectRelease('settings',true);return;}
   if(source==='local'&&(!file||!/\.zip$/i.test(file.name)||file.size<=0||file.size>22*1024*1024)){status.textContent='请选择 22MB 以内的完整源码 ZIP';return;}
   if(!requestId)requestId=crypto.randomUUID();var body=new FormData();body.set('source',source);body.set('version',release.querySelector('[data-release-version]').value);body.set('description',release.querySelector('[data-release-description]').value);body.set('request_id',requestId);if(file)body.set('archive',file);
   buttons.forEach(function(button){button.disabled=true;});status.textContent='正在提交构建任务…';
   try{var data=await request(release,'start',body);requestId='';status.textContent=data.job.message;page=1;await load();}catch(error){status.textContent=error.message+'；请先刷新记录核对，避免重复上传';}finally{buttons.forEach(function(button){button.disabled=false;});archive.value='';}
  }
  release.querySelector('[data-release-start="cloud"]').onclick=function(){start('cloud');};release.querySelector('[data-release-local]').onclick=function(){if(release.dataset.configured!=='1'){selectRelease('settings',true);return;}archive.click();};archive.onchange=function(){if(archive.files[0])start('local',archive.files[0]);};load();
 }
 var subscribe=document.querySelector('[data-wechat-subscribe]');
 if(subscribe){
  var payload=JSON.parse(subscribe.querySelector('[data-subscribe-config]').textContent),config=payload.config&&!Array.isArray(payload.config)?payload.config:{},definitions=payload.definitions,subStatus=subscribe.querySelector('[data-subscribe-status]'),dialog=subscribe.querySelector('[data-subscribe-dialog]'),selector=subscribe.querySelector('[data-subscribe-template]'),mapping=subscribe.querySelector('[data-subscribe-mapping]'),dialogStatus=subscribe.querySelector('[data-subscribe-dialog-status]'),kind='',templates=[];
  function paintSubscription(){
   var body=subscribe.querySelector('[data-subscribe-rows]');if(!body)return;body.replaceChildren();
   Object.entries(config).forEach(function(pair){var key=pair[0],row=pair[1],definition=definitions[key];if(!definition||!row.templateId||!(row.fields||[]).length)return;var tr=el('tr');tr.dataset.subscribeKind=key;tr.appendChild(el('td',payload.appname||''));tr.appendChild(el('td',row.title||''));tr.appendChild(el('td','用户订阅消息'));var fields=el('td');fields.dataset.subscribeFields='';row.fields.forEach(function(field){var line=el('p',field.label+'：'+(definition.fields[field.source]||'')),code=el('code',field.key);line.appendChild(code);fields.appendChild(line);});tr.appendChild(fields);var state=el('td',row.enabled?'启用':'未启用');state.dataset.subscribeEnabled='';tr.appendChild(state);var id=el('td',row.templateId);id.className='fb-template-id';id.dataset.subscribeId='';tr.appendChild(id);var actions=el('td'),wrap=el('div');wrap.className='fb-wechat-row-actions';var toggle=el('button',row.enabled?'关闭':'启用');toggle.type='button';toggle.className='fb-admin-button';toggle.dataset.subscribeToggle=key;wrap.appendChild(toggle);var fetch=el('button','重新获取');fetch.type='button';fetch.className='fb-admin-button';fetch.dataset.subscribeFetch=key;wrap.appendChild(fetch);actions.appendChild(wrap);tr.appendChild(actions);body.appendChild(tr);});subscribe.querySelector('[data-subscribe-table]').hidden=!body.children.length;
  }
  async function save(next){var body=new FormData();body.set('config',JSON.stringify(next));var data=await request(subscribe,'subscribe-save',body);config=data.config;paintSubscription();subStatus.textContent=data.message;}
  subscribe.addEventListener('click',async function(event){var button=event.target.closest('[data-subscribe-toggle]');if(!button||button.disabled)return;var key=button.dataset.subscribeToggle,next=JSON.parse(JSON.stringify(config));next[key].enabled=next[key].enabled?0:1;button.disabled=true;try{await save(next);}catch(error){subStatus.textContent=error.message;paintSubscription();}});
  function paintMapping(){
   mapping.replaceChildren();var template=templates.find(function(item){return item.templateId===selector.value;});if(!template)return;
   (template.fields||[]).forEach(function(field){var label=el('label',field.label+'（'+field.key+'）'),select=el('select');select.dataset.templateKey=field.key;select.dataset.templateLabel=field.label;select.setAttribute('aria-label',field.label+'对应内容');select.appendChild(new Option('请选择对应资料',''));
    Object.entries(definitions[kind].fields).forEach(function(pair){var type=field.key.replace(/\d+$/,'');if(['time','date'].includes(type)&&pair[0]!=='created_time')return;if(type==='amount'&&pair[0]!=='price')return;select.appendChild(new Option(pair[1],pair[0]));});var current=(config[kind]&&config[kind].fields||[]).find(function(item){return item.key===field.key;});if(current)select.value=current.source;label.appendChild(select);mapping.appendChild(label);
   });
   if(!(template.fields||[]).length)mapping.appendChild(el('p','此模板未提供可用字段，请在微信公众平台核对后重新获取。'));
  }
  selector.onchange=paintMapping;
  var purpose=subscribe.querySelector('[data-subscribe-purpose]');purpose.onchange=function(){kind=purpose.value;paintMapping();};
  subscribe.addEventListener('click',async function(event){var button=event.target.closest('[data-subscribe-fetch]');if(!button||button.disabled)return;button.disabled=true;subStatus.textContent='正在获取微信模板…';try{var body=new FormData(),data=await request(subscribe,'templates',body);templates=data.templates||[];kind=button.dataset.subscribeFetch;purpose.value=kind;selector.replaceChildren();templates.forEach(function(item){selector.appendChild(new Option(item.title+' · '+item.templateId,item.templateId));});if(config[kind])selector.value=config[kind].templateId;if(!selector.value&&templates.length)selector.selectedIndex=0;paintMapping();dialogStatus.textContent=templates.length?'':'微信暂未返回已申请的模板，请先在微信公众平台添加。';subStatus.textContent='';dialog.showModal();}catch(error){subStatus.textContent=error.message;}finally{button.disabled=false;}});
  paintSubscription();
  subscribe.querySelector('[data-subscribe-save]').onclick=async function(event){var template=templates.find(function(item){return item.templateId===selector.value;}),button=event.currentTarget;if(!template||!template.fields.length){dialogStatus.textContent='请选择包含有效字段的微信模板';return;}var fields=Array.from(mapping.querySelectorAll('select')).map(function(input){return {key:input.dataset.templateKey,label:input.dataset.templateLabel,source:input.value};});if(fields.some(function(field){return !field.source;})){dialogStatus.textContent='请为每个模板字段选择对应资料';return;}var next=JSON.parse(JSON.stringify(config));next[kind]={templateId:template.templateId,title:template.title,enabled:0,fields:fields};button.disabled=true;try{await save(next);dialog.close();}catch(error){dialogStatus.textContent=error.message;}finally{button.disabled=false;}};
 }
})();
