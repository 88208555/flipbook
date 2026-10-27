const api=require('./api');
const page=require('./page');
const micro=require('./micro');
const Core=require('./composer-core');
const loading=require('./page-loading');
const events=require('./composer-events');
function destination(value){let text=String(value||'');try{text=decodeURIComponent(text);}catch(_){}return /^\/pages\/checkout\/index\?productid=\d+&skuid=\d+$/.test(text)||/^\/pages\/micro\/index\?key=(mine|favorites)$/.test(text)?text:'';}
module.exports={
 data:{key:'login',ready:false,loginBusy:false,loginError:'',wechatEnabled:false,loginMethod:'account',bindingWechat:false,loginBackground:'',loginForeground:''},
 onLoad(query){query=query||{};this.key='login';this.loginReturn=destination(query.return);this._disposed=false;if(this.route==='pages/login/index'){this._redirecting=true;wx.redirectTo({url:'/pages/micro/index?key=login'+(this.loginReturn?'&return='+encodeURIComponent(this.loginReturn):'')});return;}this.loginValues={};this._loginEvents=[events.on('login:field',value=>{this.loginValues[value.field]=value.value;}),events.on('login:action',action=>{if(action==='login_wechat')this.onWechatLogin();else if(action==='login_submit')this.onForumLogin();else if(action==='login_account_only')this.onAccountOnly();else {this.setData({loginMethod:'account'});this.renderLogin();}})];},
 renderLogin(){if(!this.loginConfig)return;const cfg=this.loginConfig,ctx=this.loginContext;ctx.login={wechat:this.data.wechatEnabled,account:this.data.loginMethod==='account',binding:this.data.bindingWechat,busy:this.data.loginBusy,error:this.data.loginError,values:this.loginValues||{},bindNotice:this.data.bindingWechat?'首次微信登录，请绑定已有论坛账号':''};const view=micro.prepare(cfg,{key:'login'},ctx);this.setData({nodes:view.nodes,chrome:view.chrome,theme:view.theme,textStyle:view.textStyle,mainStyle:view.mainStyle,pageFont:cfg.layout.size||cfg.typography.body.size?micro.px(cfg.layout.size||cfg.typography.body.size):'initial',statusBar:page.statusBar(),footerHeight:0});},
 onShow(){if(this._redirecting)return;loading.enter(this);return this.load();},
 onHide(){loading.leave(this);},
 onUnload(){for(const off of this._loginEvents||[])off();this._disposed=true;this.bindTicket='';this.loginValues={};loading.leave(this);},
 load(){const version=this._loginLoadVersion=(this._loginLoadVersion||0)+1;this.setData({error:false});return api.get({key:'login'}).then(res=>{
  if(this._disposed||version!==this._loginLoadVersion)return;
  if(!res.ok||!res.micropage||!res.micropage.config)throw Error('登录页面暂时无法加载，请稍后重试');
  this.loginConfig=(res.micropage||{}).config||{};this.loginContext=res.composerContext||{};this.setData({ready:true,error:false,wechatEnabled:!!(res.login||{}).wechat,loginMethod:'account'});this.renderLogin();
  if(Number((res.member||{}).uid)>0)this.completeLogin();
 }).catch(error=>{if(!this._disposed&&version===this._loginLoadVersion){this.setData({ready:!!this.loginConfig,error:!this.loginConfig,loginError:error.message||'登录页面暂时无法加载，请稍后重试'});this.renderLogin();}});},
 onLoginTab(event){const method=event.currentTarget.dataset.method;if(this.data.loginBusy||!['wechat','account'].includes(method)||method==='wechat'&&!this.data.wechatEnabled)return;this.loginChosen=true;this.setData({loginMethod:method,loginError:''});if(method==='wechat')return this.onWechatLogin();},
 onAccountOnly(){this.bindTicket='';this.setData({bindingWechat:false,loginError:''});this.renderLogin();},
 onLoginField(event){const field=event.currentTarget.dataset.field;if(!['username','password','answer'].includes(field))return;this.loginValues=this.loginValues||{};this.loginValues[field]=event.detail.value;},
 onQuestion(event){this.loginValues=this.loginValues||{};this.loginValues.questionid=Number(event.detail.value)||0;},
 completeLogin(){
  this.bindTicket='';this.loginValues={};this.setData({bindingWechat:false});
  const pages=getCurrentPages(),previous=pages[pages.length-2],target=this.loginReturn;
  if(previous&&(!target||previous.route===target.split('?')[0].slice(1)))wx.navigateBack();else wx.redirectTo({url:target||'/pages/micro/index?key=mine'});
 },
 onForumLogin(){
  if(this.data.loginBusy||!this.data.ready)return;this.setData({loginBusy:true,loginError:''});this.renderLogin();
  return api.auth('forum',this.loginValues||{}).then(res=>{if(!res.ok)throw Error(res.message);return this.bindTicket?api.auth('bind',{ticket:this.bindTicket}):res;}).then(res=>{if(this._disposed)return;if(!res.ok)throw Error(res.message);this.completeLogin();}).catch(error=>{if(!this._disposed)this.setData({loginError:error.message||'登录失败，请重试'});}).then(()=>{if(!this._disposed){this.setData({loginBusy:false});this.renderLogin();}});
 },
 onWechatLogin(){
  if(this.data.loginBusy||!this.data.ready||!this.data.wechatEnabled)return;this.setData({loginBusy:true,loginError:'',loginMethod:'wechat'});this.renderLogin();
  return new Promise((resolve,reject)=>wx.login({success:resolve,fail:reject})).then(res=>this._disposed?null:api.auth('wechat',{code:res.code})).then(res=>{if(this._disposed||!res)return;if(res.code==='bind_required'){this.bindTicket=res.ticket;this.setData({bindingWechat:true,loginMethod:'account',loginError:res.message});return;}if(!res.ok)throw Error(res.message);this.completeLogin();}).catch(error=>{if(!this._disposed)this.setData({loginError:error.message||'微信登录失败，请重试'});}).then(()=>{if(!this._disposed){this.setData({loginBusy:false});this.renderLogin();}});
 }
};
