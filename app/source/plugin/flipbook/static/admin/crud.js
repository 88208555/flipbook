(function(){'use strict';
 document.querySelectorAll('form[data-fb-crud-list]').forEach(form=>{
  const remove=document.querySelector('[data-crud-delete][form="'+form.id+'"]');
  const all=form.querySelector('[data-crud-select-all]');
  const boxes=()=>Array.from(form.querySelectorAll('input[name="delete[]"]'));
  function update(){const rows=boxes(),selected=rows.filter(box=>box.checked).length;if(remove)remove.disabled=!selected;if(all){all.checked=!!rows.length&&selected===rows.length;all.indeterminate=selected>0&&selected<rows.length;}}
  form.addEventListener('change',event=>{if(event.target===all)boxes().forEach(box=>box.checked=all.checked);update();});
  form.addEventListener('submit',event=>{if(!boxes().some(box=>box.checked))event.preventDefault();});
  update();
 });
 document.querySelectorAll('[data-fb-supplier-workspace]').forEach(workspace=>{
  const search=workspace.querySelector('[data-supplier-search]'),dialog=workspace.querySelector('[data-supplier-dialog]'),editor=workspace.querySelector('[data-supplier-edit]'),status=workspace.querySelector('[data-supplier-status]'),dialogStatus=workspace.querySelector('[data-supplier-dialog-status]');
  const state={page:1,perpage:10,stylepage:1,selectedid:0,filters:{supplier_name:'',supplier_code:''},suppliers:[],styles:[],selected:null};
  let reader=null,sequence=0,saving=false,editingKind='supplier',lockedControls=[];
  const text=(tag,value,className)=>{const element=document.createElement(tag);element.textContent=String(value??'');if(className)element.className=className;return element;};
  const button=(label,action)=>{const element=text('button',label,'fb-admin-button');element.type='button';element.addEventListener('click',action);return element;};
  function setStatus(message){status.textContent=message||'';}
  async function request(action,values={},signal){
   const url=new URL(workspace.dataset.url,location.href);url.searchParams.set('inajax','1');url.searchParams.set('supplier_action',action);
   const options={credentials:'same-origin',signal};
   if(action==='list'){Object.entries(values).forEach(([key,value])=>url.searchParams.set(key,String(value)));}
   else{const body=new URLSearchParams({formhash:workspace.dataset.formhash,flipbooksubmit:'1'});Object.entries(values).forEach(([key,value])=>body.append(key,String(value)));options.method='POST';options.body=body;}
   const response=await fetch(url,options),raw=await response.text();
   const xml=new DOMParser().parseFromString(raw,'application/xml'),root=xml.documentElement;
   const content=root&&root.nodeName==='root'?root.textContent:raw;
   let data;try{data=JSON.parse(content.trim());}catch(error){
    const html=new DOMParser().parseFromString(content,'text/html'),message=html.querySelector('.infobox,.alert_error,.error');
    throw new Error(message?message.textContent.trim().slice(0,300):'操作未完成，请检查输入或重新登录后重试');
   }
   if(!response.ok||!data.success)throw new Error(data.message||'操作失败，请稍后重试');return data;
  }
  function orderInput(row,kind){
   const input=document.createElement('input');input.type='number';input.step='1';input.min=kind==='style'?'-32768':'-2147483648';input.max=kind==='style'?'32767':'2147483647';input.value=row.displayorder;input.className='fb-supplier-order';input.setAttribute('aria-label',row.name+' 排序');
   input.addEventListener('change',async()=>{if(!input.checkValidity()){input.reportValidity();return;}const id=kind==='supplier'?row.supplierid:row.productid;try{await mutate(kind+'_order',{id,supplierid:state.selectedid,displayorder:input.value});}catch(error){input.setAttribute('aria-invalid','true');}});return input;
  }
  function actions(row,kind){
   const element=document.createElement('div');element.className='fb-supplier-row-actions';element.append(button('编辑',()=>openEditor(kind,row)),button('删除',()=>remove(kind,row)));return element;
  }
  function renderRows(){
   const body=workspace.querySelector('[data-supplier-rows]');body.replaceChildren();
   state.suppliers.forEach(row=>{
    const tr=document.createElement('tr');tr.tabIndex=0;tr.classList.toggle('is-selected',Number(row.supplierid)===state.selectedid);tr.setAttribute('aria-selected',String(Number(row.supplierid)===state.selectedid));
    tr.append(text('td',row.name),text('td',row.code));const order=document.createElement('td');order.append(orderInput(row,'supplier'));const operations=document.createElement('td');operations.append(actions(row,'supplier'));tr.append(order,operations);
    function select(){if(saving)return;state.selectedid=Number(row.supplierid);state.stylepage=1;load();}
    tr.addEventListener('click',event=>{if(!event.target.closest('button,input,a'))select();});tr.addEventListener('keydown',event=>{if(event.target===tr&&(event.key==='Enter'||event.key===' ')){event.preventDefault();select();}});body.append(tr);
   });
   if(!state.suppliers.length){const tr=document.createElement('tr'),cell=text('td','暂无供应商户');cell.colSpan=4;tr.append(cell);body.append(tr);}
   workspace.querySelector('[data-supplier-selected]').textContent=state.selected?state.selected.name+' · 关联款式':'请选择供应商户';workspace.querySelector('[data-style-add]').disabled=!state.selected||saving;
   const styles=workspace.querySelector('[data-supplier-style-rows]');styles.replaceChildren();
   state.styles.forEach(row=>{const tr=document.createElement('tr');tr.append(text('td',row.name),text('td',row.code));const order=document.createElement('td');order.append(orderInput(row,'style'));const operations=document.createElement('td');operations.append(actions(row,'style'));tr.append(order,operations);styles.append(tr);});
   if(!state.styles.length){const tr=document.createElement('tr'),cell=text('td',state.selected?'该供应商户暂无关联款式':'请先选择供应商户');cell.colSpan=4;tr.append(cell);styles.append(tr);}
  }
  function pagination(container,count,page,kind){
   container.replaceChildren();const total=Math.max(1,Math.ceil(count/state.perpage));container.append(text('span','共 '+count+' 条'));
   if(kind==='supplier'){
    const size=document.createElement('select');size.setAttribute('aria-label','每页供应商数量');[10,20,50].forEach(value=>{const option=text('option',value+' 条/页');option.value=value;option.selected=value===state.perpage;size.append(option);});size.addEventListener('change',()=>{state.perpage=Number(size.value);state.page=1;state.stylepage=1;load();});container.append(size);
   }
   const change=value=>{if(saving)return;if(kind==='supplier'){state.page=value;state.selectedid=0;state.stylepage=1;}else state.stylepage=value;load();};
   const previous=button('上一页',()=>change(page-1)),next=button('下一页',()=>change(page+1));previous.disabled=page<=1;next.disabled=page>=total;container.append(previous,text('span',page+' / '+total),next);
   const field=document.createElement('input');field.type='number';field.min='1';field.max=String(total);field.step='1';field.value=page;field.setAttribute('aria-label',kind==='supplier'?'供应商页码':'款式页码');const jump=()=>{if(field.checkValidity())change(Number(field.value));else field.reportValidity();};field.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();jump();}});container.append(text('label','前往'),field,button('跳转',jump));
  }
  async function load(){
   const current=++sequence;if(reader)reader.abort();reader=new AbortController();workspace.setAttribute('aria-busy','true');setStatus('正在读取供应商户与关联款式…');
   try{
    const data=await request('list',{...state.filters,page:state.page,perpage:state.perpage,stylepage:state.stylepage,selectedid:state.selectedid},reader.signal);if(current!==sequence)return;
    state.page=data.page;state.perpage=data.perpage;state.stylepage=data.stylePage;state.suppliers=data.suppliers;state.styles=data.styles;state.selected=data.selected&&data.selected.supplierid?data.selected:null;state.selectedid=state.selected?Number(state.selected.supplierid):0;renderRows();pagination(workspace.querySelector('[data-supplier-pagination]'),data.count,state.page,'supplier');pagination(workspace.querySelector('[data-supplier-style-pagination]'),data.styleCount,state.stylepage,'style');setStatus('');
   }catch(error){if(error.name!=='AbortError'&&current===sequence)setStatus(error.message);}finally{if(current===sequence)workspace.removeAttribute('aria-busy');}
  }
  function lock(value){
   saving=value;if(value){lockedControls=Array.from(workspace.querySelectorAll('button,input.fb-supplier-order')).map(control=>({control,disabled:control.disabled}));lockedControls.forEach(({control})=>control.disabled=true);}else{lockedControls.forEach(({control,disabled})=>{if(control.isConnected)control.disabled=disabled;});lockedControls=[];workspace.querySelector('[data-style-add]').disabled=!state.selected;}
  }
  async function mutate(action,values){
   if(saving)throw new Error('请等待当前操作完成');lock(true);
   try{const result=await request(action,values);await load();setStatus(result.message||'已保存');return result;}catch(error){setStatus(error.message);throw error;}finally{lock(false);}
  }
  function openEditor(kind,row={}){
   if(saving||(kind==='style'&&!state.selected))return;editingKind=kind;editor.reset();dialogStatus.textContent='';const style=kind==='style';
   editor.elements.id.value=style?(row.productid||0):(row.supplierid||0);editor.elements.supplierid.value=state.selectedid;editor.elements.displayorder.min=style?'-32768':'-2147483648';editor.elements.displayorder.max=style?'32767':'2147483647';
   editor.elements.name.value=row.name??'';editor.elements.code.value=row.code??'';editor.elements.displayorder.value=row.displayorder??0;editor.elements.contact.value=row.contact??'';editor.elements.phone.value=row.phone??'';editor.elements.address.value=row.address??'';editor.elements.note.value=row.note??'';editor.elements.available.value=row.available??1;
   workspace.querySelector('[data-supplier-dialog-title]').textContent=(editor.elements.id.value!=='0'?'编辑':'添加')+(style?'款式':'供应商');workspace.querySelector('[data-supplier-name-label]').textContent=style?'款式名称':'供应商名称';workspace.querySelector('[data-supplier-code-label]').textContent=style?'款式编码':'供应商编码';workspace.querySelector('[data-style-owner-row]').hidden=!style;workspace.querySelector('[data-style-owner]').value=state.selected?state.selected.name:'';workspace.querySelector('[data-supplier-extra]').hidden=style;workspace.querySelector('[data-supplier-extra]').open=false;dialog.showModal();editor.elements.name.focus();
  }
  async function remove(kind,row){
   if(saving||!window.confirm('确定删除'+(kind==='supplier'?'供应商户“':'款式“')+row.name+'”？'))return;
   try{await mutate(kind+'_delete',{'delete[]':kind==='supplier'?row.supplierid:row.productid,flipbookaction:'delete',supplierid:state.selectedid,displayorder:0});}catch(error){}
  }
  search.addEventListener('submit',event=>{event.preventDefault();if(saving)return;state.filters=Object.fromEntries(new FormData(search));state.page=1;state.stylepage=1;state.selectedid=0;load();});
  workspace.querySelector('[data-supplier-reset]').addEventListener('click',()=>{if(saving)return;search.reset();state.filters={supplier_name:'',supplier_code:''};state.page=1;state.stylepage=1;state.selectedid=0;load();});
  workspace.querySelector('[data-supplier-add]').addEventListener('click',()=>openEditor('supplier'));workspace.querySelector('[data-style-add]').addEventListener('click',()=>openEditor('style'));workspace.querySelectorAll('[data-supplier-close]').forEach(control=>control.addEventListener('click',()=>{if(!saving)dialog.close();}));dialog.addEventListener('cancel',event=>{if(saving)event.preventDefault();});
  editor.addEventListener('submit',async event=>{
   event.preventDefault();if(saving||!editor.reportValidity())return;editor.elements.name.value=editor.elements.name.value.trim();if(!editor.reportValidity())return;const values=Object.fromEntries(new FormData(editor));lock(true);dialogStatus.textContent='正在保存…';
   try{const result=await request(editingKind+'_save',values);if(editingKind==='supplier'){state.selectedid=Number(result.id);state.filters={supplier_name:'',supplier_code:''};search.reset();state.page=1;}state.stylepage=1;dialog.close();await load();setStatus(result.message||'已保存');}catch(error){dialogStatus.textContent=error.message;}finally{lock(false);}
  });
  load();
 });
})();
