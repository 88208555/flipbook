(function(){
 'use strict';
 const form=document.getElementById('cpform');if(!form)return;
 const tabs=Array.from(form.querySelectorAll('[data-fb-settings-tab]')),panels=Array.from(form.querySelectorAll('[data-fb-settings-panel]')),active=form.querySelector('[data-fb-settings-active]');
 if(!tabs.length||!active)return;
 function select(key,focus){if(!tabs.some(tab=>tab.dataset.fbSettingsTab===key))return;active.value=key;tabs.forEach(tab=>{const on=tab.dataset.fbSettingsTab===key;tab.classList.toggle('is-active',on);tab.setAttribute('aria-selected',String(on));tab.tabIndex=on?0:-1;if(on&&focus)tab.focus();});panels.forEach(panel=>panel.hidden=panel.dataset.fbSettingsPanel!==key);}
 tabs.forEach((tab,index)=>{tab.addEventListener('click',()=>select(tab.dataset.fbSettingsTab,false));tab.addEventListener('keydown',event=>{let next;if(event.key==='ArrowRight')next=(index+1)%tabs.length;else if(event.key==='ArrowLeft')next=(index+tabs.length-1)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();select(tabs[next].dataset.fbSettingsTab,true);});});
 form.addEventListener('invalid',event=>{const panel=event.target.closest('[data-fb-settings-panel]');if(panel)select(panel.dataset.fbSettingsPanel,false);},true);
 select(active.value,false);
})();
