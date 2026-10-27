/* Classify plugin controls once and when the editor renders new panels. */
(function(){
 'use strict';
 document.body.setAttribute('data-fb-admin','');
 // Native Discuz app entries already carry a platform query. Repair only this
 // plugin's same-origin admin links/forms when the native helper adds a second '?'.
 function repairAdminUrl(element,attribute){
  try{
   const url=new URL(element.getAttribute(attribute),location.href),platform=url.searchParams.get('platform')||'',match=platform.match(/^([a-z_-]+)\?action=plugins$/);
   if(url.origin!==location.origin||url.pathname!==location.pathname||url.searchParams.get('identifier')!=='flipbook'||url.searchParams.has('action')||!match)return;
   url.searchParams.set('platform',match[1]);url.searchParams.set('action','plugins');element.setAttribute(attribute,url.pathname+url.search+url.hash);
  }catch(error){}
 }
 const nav='.fb-admin-tab,.fb-product-tabs button,.fb-product-subnav a,.fbmp-config-tabs button,.fbmp-page,.fb-wechat-tabs button';
 const groups='.fb-product-tabs,.fb-product-subnav,.fbmp-config-tabs,.fbmp-pages,[data-fb-wechat-tabs]';
 // Discuz renders the plugin heading and its main navigation before these assets.
 // Mark only this plugin's native navigation, leaving other administration modules alone.
 document.querySelectorAll('.itemtitle').forEach(function(heading){
  const list=heading.querySelector('ul.tab1');
  if(!list || !Array.from(list.querySelectorAll('a')).some(function(link){
   try{return new URL(link.href,location.href).searchParams.get('identifier')==='flipbook';}catch(error){return false;}
  }))return;
  heading.classList.add('fb-admin-plugin-heading');
  const title=heading.querySelector('.titlerow h3');
  if(title){const row=title.parentElement;title.remove();if(!row.textContent.trim()&&!row.children.length)row.remove();}
  list.classList.add('fb-admin-tabs','fb-admin-host-tabs');
  list.querySelectorAll(':scope > li > a').forEach(function(tab){
   repairAdminUrl(tab,'href');
   const selected=tab.parentElement.classList.contains('current');
   tab.classList.add('fb-admin-tab');tab.classList.toggle('is-active',selected);
   if(selected)tab.setAttribute('aria-current','page');
  });
  const floating=heading.closest('.floattop');
  if(floating){floating.classList.add('fb-admin-plugin-top');const spacer=floating.nextElementSibling;if(spacer&&spacer.classList.contains('floattopempty'))spacer.classList.add('fb-admin-plugin-spacer');}
  // The shell owns these same destinations. Keep the native links in the DOM
  // for standalone access, but hide their duplicate row inside this sidebar.
  try{
   if(window.parent!==window && window.parent.document.getElementById('menu_flipbook')){
    heading.hidden=true;
    if(floating){floating.hidden=true;const spacer=floating.nextElementSibling;if(spacer&&spacer.classList.contains('floattopempty'))spacer.hidden=true;}
   }
  }catch(error){}
 });
 function decorate(root){
  if(root.nodeType!==1)return;
  const forms=[...(root.matches('form[action]')?[root]:[]),...root.querySelectorAll('form[action]')];forms.forEach(function(form){repairAdminUrl(form,'action');});
  if(root.matches(groups))root.classList.add('fb-admin-tabs');
  root.querySelectorAll(groups).forEach(function(group){group.classList.add('fb-admin-tabs');});
  if(root.matches(nav)){root.classList.add('fb-admin-tab');root.classList.remove('fb-admin-button');}
  const candidates=[...(root.matches('button,input[type=submit],input[type=button],a.btn')?[root]:[]),...root.querySelectorAll('button,input[type=submit],input[type=button],a.btn')];
  candidates.forEach(function(button){
   if(button.matches('.fbmp-add,[role=switch]')||button.closest('.fb-library-grid,.fbmp-component-tools,#fbmp-frame,[data-fb-controls="local"]')||button.querySelector('img,video'))return;
   if(button.matches(nav)){button.classList.add('fb-admin-tab');return;}
   button.classList.add('fb-admin-button');
   button.querySelectorAll(':scope > svg,:scope > i,:scope > .icon,:scope > .fb-icon').forEach(function(icon){icon.remove();});
   if(!button.textContent.trim()&&!button.matches('input'))button.textContent=button.getAttribute('aria-label')||button.title||'操作';
  });
  root.querySelectorAll(nav).forEach(function(tab){tab.classList.add('fb-admin-tab');tab.classList.remove('fb-admin-button');});
 }
 decorate(document.body);
 new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(decorate);});}).observe(document.body,{childList:true,subtree:true});
})();
