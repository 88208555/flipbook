/* Keep the plugin sidebar selection in step with native admin tabs. */
(function(){
 'use strict';
 const menu=document.getElementById('menu_flipbook');
 if(!menu)return;
 function activate(link){
  menu.querySelectorAll('a').forEach(function(item){
   const active=item===link;item.classList.toggle('active',active);
   if(active)item.setAttribute('aria-current','page');else item.removeAttribute('aria-current');
  });
 }
 document.addEventListener('click',function(event){
  const link=event.target.closest('#leftmenu > li > ul > li > a');
  if(link)activate(menu.contains(link)?link:null);
 },true);
 document.addEventListener('mousedown',function(event){
  const tab=event.target.closest('#tabs > a');if(!tab || event.target.closest('#tabs > a > span'))return;
  const link=document.getElementById(tab.id.replace(/_frame_tab$/,''));
  activate(link && menu.contains(link)?link:null);
 },true);
 const selected=menu.querySelector('a.active');activate(selected);
})();
