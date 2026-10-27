(function(root,factory){if(typeof module==='object'&&module.exports)module.exports=factory();else root.FBComposerLists=factory();})(typeof window==='object'?window:this,function(){
 'use strict';
 function key(item,index){return String(item.listId||item.historyid&&'history:'+item.historyid||item.targettype&&item.targetid&&item.targettype+':'+item.targetid||item.productid&&'product:'+item.productid||item.albumid&&'album:'+item.albumid||item.day||index);}
 function unique(previous,incoming){const seen=new Set(previous.map(key)),out=previous.slice();for(const item of incoming){const id=key(item,out.length);if(!seen.has(id)){seen.add(id);out.push(item);}}return out;}
 function merge(context,binding,packet){
  const [scope,field]=binding.split('.'),target=context[scope]||(context[scope]={}),previous=target[field]||[];
  if(binding==='history.groups'){
   const groups=previous.map(group=>Object.assign({},group,{items:group.items.slice()}));
   for(const group of packet.items){const current=groups.find(item=>item.day===group.day);if(current)current.items=unique(current.items,group.items);else groups.push(Object.assign({},group,{items:unique([],group.items)}));}target[field]=groups;
  }else target[field]=unique(previous,packet.items);
  context.pagination[binding]=packet.pagination;
 }
 function create(initial,request,changed,failed){
  let context=initial,version=0,pending={};
  function reset(next){version++;context=next;pending={};}
  function load(binding){
   const state=(context.pagination||{})[binding];if(!state||!state.hasMore)return Promise.resolve(false);if(pending[binding])return pending[binding];
   const current=version,next=Number(state.nextPage),query=Object.assign({},context.query||{});if(!Number.isInteger(next)||next<=Number(state.page))return Promise.resolve(false);
   pending[binding]=Promise.resolve().then(()=>request(binding,next,query)).then(packet=>{
    if(current!==version)return false;
    if(!packet||!packet.ok||!Array.isArray(packet.items)||!packet.pagination||packet.pagination.binding!==binding||Number(packet.pagination.page)!==next||packet.pagination.hasMore&&Number(packet.pagination.nextPage)<=next)throw Error('列表加载失败');
    merge(context,binding,packet);if(changed)changed(binding,context);return true;
   }).catch(error=>{if(current===version&&failed)failed(binding,error);return false;}).then(result=>{if(current===version)delete pending[binding];return result;});
   return pending[binding];
  }
  return {load,reset,dispose(){reset({});}};
 }
 return {key,merge,create};
});
