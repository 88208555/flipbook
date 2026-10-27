/* Open the editor as a separate native admin tab; keep the existing draft intact. */
(function(){
 'use strict';
 function shell(){
  var host=window;
  for(var depth=0;depth<3;depth++){
   try{
    if(host._framemenu && typeof host._framemenu.tabNew==='function' && host.document.getElementById('tabs'))return host;
    if(host.parent===host)break;
    host=host.parent;
   }catch(error){break;}
  }
  return null;
 }
 function samePage(href,target){
  try{
   var url=new URL(href,location.href);
   return url.origin===target.origin && url.pathname===target.pathname && ['action','operation','do','identifier','pmod','op','key'].every(function(key){return (url.searchParams.get(key)||'')===(target.searchParams.get(key)||'');});
  }catch(error){return false;}
 }
 document.addEventListener('click',function(event){
  var link=event.target.closest('a[data-fb-admin-open]');
  if(!link || event.defaultPrevented || event.button!==0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)return;
  var target=new URL(link.href,location.href);
  if(target.origin!==location.origin || target.searchParams.get('identifier')!=='flipbook' || target.searchParams.get('pmod')!=='micropage')return;
  var host=shell();if(!host)return; // The ordinary link opens a named browser page without the admin shell.
  // Legacy horizontal/mobile navigation has no independent native tabs.
  var container=host.document.getElementById('bdcontainer');
  var horizontal=container ? container.classList.contains('oldlayout') && host.document.documentElement.clientWidth>=1399 : typeof host.getcookie==='function' && host.getcookie('admincp_oldlayout') && host.document.documentElement.clientWidth>=1200;
  if(host.document.body.clientWidth<575 || horizontal)return;
  var frames=Array.from(host.document.querySelectorAll('.ifmcontainer .mainframe'));
  var existing=frames.find(function(frame){
   var href=frame.src;
   try{if(frame.contentWindow.location.href!=='about:blank')href=frame.contentWindow.location.href;}catch(error){}
   return samePage(href,target);
  });
  if(existing){
   var tab=host.document.getElementById(existing.id+'_tab');
   if(tab){event.preventDefault();tab.dispatchEvent(new host.MouseEvent('mousedown',{button:0,bubbles:true}));return;}
  }
  var anchor=host.document.createElement('a');
  anchor.id=link.dataset.fbAdminOpen;anchor.href=target.href;
  anchor.textContent=link.dataset.fbAdminTitle||link.textContent.trim();
  event.preventDefault();host._framemenu.tabNew(anchor);
 });
 function title(){
  if(!document.body.hasAttribute('data-fb-editor'))return;
  var host=shell();if(!host || host===window)return;
  try{
   var owner=Array.from(host.document.querySelectorAll('.ifmcontainer .mainframe')).find(function(frame){return frame.contentWindow===window;});
   if(!owner)return;
   var tab=host.document.getElementById(owner.id+'_tab');
   if(!tab)return;
   // Preserve the native close control and its event handlers.
   Array.from(tab.childNodes).forEach(function(node){if(node.nodeType===3)node.remove();});
   tab.insertBefore(host.document.createTextNode(document.title),tab.firstChild);tab.title=document.title;
  }catch(error){}
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',title);else title();
})();
