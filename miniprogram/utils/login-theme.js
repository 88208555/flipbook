/* Login uses the same configured region styles for guests and account binding. */
function rgb(value,backdrop){
 const text=String(value||'').trim().toLowerCase();let color;
 if(/^#[0-9a-f]{3,4}$/.test(text)){const h=text.slice(1).split('').map(x=>x+x).join('');color=[0,2,4].map(i=>parseInt(h.slice(i,i+2),16));color.push(h.length===8?parseInt(h.slice(6),16)/255:1);}
 else if(/^#[0-9a-f]{6}([0-9a-f]{2})?$/.test(text)){color=[1,3,5].map(i=>parseInt(text.slice(i,i+2),16));color.push(text.length===9?parseInt(text.slice(7),16)/255:1);}
 else {const match=text.match(/^rgba?\(([^)]+)\)$/);if(match){const n=match[1].split(',').map(Number);if((n.length===3||n.length===4)&&n.every(Number.isFinite)){color=n.slice(0,3).map(n=>Math.max(0,Math.min(255,n)));color.push(n.length===4?Math.max(0,Math.min(1,n[3])):1);}}}
 if(!color)color=text==='white'?[255,255,255,1]:text==='black'?[0,0,0,1]:[...backdrop,0];return color.slice(0,3).map((n,i)=>Math.round(n*color[3]+backdrop[i]*(1-color[3])));
}
// Platform navigation accepts only hex colors. Body regions remain exactly configured.
module.exports=function(options,regions){
 const p=options||{},r=regions||{},page=r.loginPage||{},card=r.loginCard||{};
 const bg=page.bg||p._pageBackground||p.bg||'',fg=page.color||card.color||'';
 const color=rgb(bg,[0,0,0]),luminance=color.reduce((sum,value)=>sum+value,0);
 const micro=require('./micro');
 return {regions:r,loginBackground:bg,loginForeground:fg,
  loginNavigationBackground:'#'+color.map(n=>n.toString(16).padStart(2,'0')).join(''),
  loginNavigationText:luminance>382?'#000000':'#ffffff',loginThemeStyle:'',
  quickLoginStyle:micro.regionStyle(r.loginButton),loginTabStyle:micro.regionStyle(r.loginTab),
  loginTabActiveStyle:micro.regionStyle(r.loginTabActive),loginTabDisabledStyle:micro.regionStyle(r.loginTabDisabled),
  loginPlaceholderStyle:micro.regionStyle(r.loginPlaceholder)};
};
