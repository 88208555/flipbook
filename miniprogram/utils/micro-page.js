const api=require('./api'),page=require('./page'),micro=require('./micro'),Core=require('./composer-core'),events=require('./composer-events'),share=require('./share');
const Login=require('./login-controller');
const pageKeys=require('./micro-keys');
const Lists=require('./composer-lists');
const KEYS=['home','detail','favorites','catalog','search','mine','history','about','privacy','agreement','login'];
module.exports=Object.assign({},Login,{
 data:Object.assign({},Login.data,{statusBar:0,key:'home',nodes:[],theme:{},chrome:{logo:{},back:{},footer:{items:[]}},footerHeight:0,feedLocked:false,mainStyle:'',loading:false,error:false,shareVisible:false,shareProduct:null,shareSettings:{}}),
 onLoad(query){this._disposed=false;query=query||{};if(query.scene){let scene='';try{scene=decodeURIComponent(query.scene);}catch(_){}const match=scene.match(/^p=(\d+)$/);if(match)query=Object.assign({},query,{key:'detail',productid:match[1]});}this.key=pageKeys.normalize(query.key);this.query=Object.assign({},query,{key:this.key});if(this.key==='login'){Login.onLoad.call(this,query);return;}this.setData({key:this.key,statusBar:page.statusBar()});this._events=[events.on('refresh',()=>{if(this._visible)this.sync();}),events.on('share:open',()=>this.onOpenShare()),events.on('home:current',item=>this.onHomeChange(item)),events.on('search:query',query=>this.onSearchQuery(query)),events.on('home:favorite',state=>{if(this.context&&this.context.home)for(const item of this.context.home.albums||[])if(item.albumid===state.albumid)item.favorited=state.favorited;})];if(typeof wx.showShareMenu==='function')wx.showShareMenu({menus:['shareAppMessage','shareTimeline']});},
 onShow(){this._visible=true;this._visit=(this._visit||0)+1;if(this.key==='login')return Login.onShow.call(this);if(typeof this.getTabBar==='function'&&this.getTabBar())this.getTabBar().setData({hidden:true});return this.sync();},
 onHide(){this._visible=false;if(this._lists)this._lists.dispose();},
 onSearchQuery(query){if(!this._visible||query.key!==this.key)return;this.query=Object.assign({},this.query,{keyword:query.keyword});this._queryVersion=(this._queryVersion||0)+1;if(this._lists)this._lists.dispose();if(this.loading){this._searchAfterLoad=true;return;}return this.sync();},
 onUnload(){this._disposed=true;if(this._lists)this._lists.dispose();if(this.key==='login'){Login.onUnload.call(this);return;}for(const off of this._events||[])off();},
 sync(){if(this.loading)return this.loading;if(this._lists)this._lists.dispose();this.setData({loading:true,error:false,listError:''});const queryVersion=this._queryVersion||0;this.loading=api.get(this.query).then(res=>{if(this._disposed||queryVersion!==(this._queryVersion||0))return;if(!res.ok||!res.micropage){if(res.code==='gone'){this.config=null;this.context=null;this.setData({nodes:[],shareProduct:null,chrome:{logo:{},back:{},footer:{items:[]}},error:true});}throw Error(res.message||'页面加载失败');}this.pageName=res.micropage.name;const cfg=res.micropage.config,ctx=res.composerContext||{};this.config=cfg;this.context=ctx;this.initLists();const view=micro.prepare(cfg,this.query,ctx);this.setData({nodes:view.nodes,chrome:view.chrome,feedLocked:view.feedLocked,theme:view.theme,textStyle:view.textStyle,mainStyle:view.mainStyle,shareProduct:ctx.product&&ctx.product.productid?ctx.product:null,shareSettings:(res.app||{}).share||{},pageFont:Number(cfg.layout.size||cfg.typography.body.size)?micro.px(cfg.layout.size||cfg.typography.body.size):'initial'},()=>this.measure());this.recordRead();}).catch(()=>{if(!this._disposed)this.setData({error:!this.config});}).then(()=>{this.loading=null;if(this._disposed)return;this.setData({loading:false});if(this._searchAfterLoad){this._searchAfterLoad=false;return this.sync();}});return this.loading;},
 onRetry(){return this.key==='login'?Login.load.call(this):this.sync();},
 recordRead(){
  const ctx=this.context||{},target=ctx.readTarget;if(this._disposed||this._visible===false||!target||!(ctx.member||{}).uid)return;
  const stamp=JSON.stringify([this._visit||0,target]);if(this._readStamp===stamp)return;this._readStamp=stamp;
  return api.post('read',target,'action',{quiet:true,reuseSession:true}).then(res=>{if(!res.ok&&this._readStamp===stamp)this._readStamp='';}).catch(()=>{if(this._readStamp===stamp)this._readStamp='';});
 },
 recordShare(type,id){if(!(this.context&&this.context.member&&this.context.member.uid)||!id||this._shareRecording)return;this._shareRecording=api.post('share',{targettype:type,targetid:id},'action',{quiet:true,reuseSession:true}).catch(()=>{}).then(()=>{this._shareRecording=null;});},
 initLists(){
  this._lists=Lists.create(this.context,(binding,listPage,query)=>api.get(Object.assign({},query,{key:this.key,list:binding,list_page:listPage}),{quiet:true}),(binding)=>{
   const next=micro.prepare(this.config,this.query,this.context).nodes,updates={listError:''};
   const patch=(nodes,fresh,path)=>nodes.forEach((node,index)=>{const target=path+'['+index+']';if(node.kind==='repeat'&&node.props.binding===binding){node.rows=fresh[index].rows;updates[target+'.rows']=node.rows;return;}if(node.children)patch(node.children,fresh[index].children,target+'.children');for(let i=0;i<(node.tabs||[]).length;i++)patch(node.tabs[i].children,fresh[index].tabs[i].children,target+'.tabs['+i+'].children');});
   patch(this.data.nodes,next,'nodes');this.setData(updates);
  },binding=>this.setData({listError:binding}));
 },
 onListLower(){if(!this._lists||this.loading||!this._visible)return Promise.resolve();return Promise.all(Object.keys(this.context.pagination||{}).map(binding=>this._lists.load(binding)));},
 onRetryList(){return this._lists&&this._lists.load(this.data.listError);},
 measure(){
  wx.createSelectorQuery().select('.fc-native-footer').boundingClientRect().exec(rects=>{
   const footerHeight=rects[0]?rects[0].height:0;
   const measureViewport=()=>wx.createSelectorQuery().select('.fc-native-scroll').boundingClientRect().exec(rects=>{
    const height=rects[0]?rects[0].height:0;if(!height||!this.config)return;const unit=wx.getWindowInfo().windowWidth/375;
    const walk=nodes=>nodes.forEach(node=>{if(node.props.fillViewport){node.props.boxHeight=Math.max(0,height/unit-(this.config.layout.topGap||0)-(this.config.layout.bottomGap||0));node.style=Core.style(node.props,'rpx',this.config.typography);}walk(node.children||[]);for(const row of node.rows||[])walk(row.children);for(const tab of node.tabs||[])walk(tab.children);});
    const nodes=this.data.nodes;walk(nodes);this.setData({nodes});
   });
   if(this.data.footerHeight!==footerHeight)this.setData({footerHeight},measureViewport);else measureViewport();
  });
 },
 onHomeChange(item){if(!this.context||this._visible===false||this._disposed)return;this.context.home.current=item;const model=micro.prepare(this.config,this.query,this.context);function patch(nodes,newNodes){nodes.forEach((node,i)=>{node.context.home=item;node.link=newNodes[i].link;if(node.fab){node.fab.hasVideo=newNodes[i].fab.hasVideo;node.fab.favorited=newNodes[i].fab.favorited;}if((node.props.binding||'').startsWith('home.current.'))node.text=newNodes[i].text;if(node.children)patch(node.children,newNodes[i].children);for(let j=0;j<(node.rows||[]).length;j++)patch(node.rows[j].children,newNodes[i].rows[j].children);for(let j=0;j<(node.tabs||[]).length;j++)patch(node.tabs[j].children,newNodes[i].tabs[j].children);});}const nodes=this.data.nodes;patch(nodes,model.nodes);this.setData({nodes});},
 onBack(){if(this.data.chrome.back.configured&&this.data.chrome.back.path)micro.openLink(this.data.chrome.back.path);else page.back();},
 onLogo(){micro.openLink(this.data.chrome.logo.path);},
 onTabbar(e){const item=this.data.chrome.footer.items[Number(e.currentTarget.dataset.index)||0];if(!item)return;const tabs={'page:home':'/pages/discover/index','page:catalog':'/pages/catalog/index','page:favorites':'/pages/favorites/index','page:mine':'/pages/mine/index'};if(tabs[item.link])wx.switchTab({url:tabs[item.link]});else micro.openLink(item.href);},
 onOpenShare(){if(this.data.shareProduct&&this.data.shareProduct.productid)this.setData({shareVisible:true});},onCloseShare(){this.setData({shareVisible:false});},onShareChange(e){this._share=e.detail;},
 onShareAppMessage(){if(this.key==='home'&&this.context&&this.context.home.current.albumid){const item=this.context.home.current;this.recordShare('album',item.albumid);return {title:Core.decode(item.name||this.pageName),path:'/pages/micro/index?key=detail&albumid='+item.albumid,imageUrl:item.cover||item.image};}if(!this.data.shareProduct)return {title:this.pageName||'画册',path:'/pages/micro/index?key='+encodeURIComponent(this.key)};this.recordShare('product',this.data.shareProduct.productid);return share.message(this._share&&this._share.product||this.data.shareProduct,this._share&&this._share.settings||this.data.shareSettings);},
 onShareTimeline(){const m=this.onShareAppMessage();return {title:m.title,imageUrl:m.imageUrl,query:(m.path||'').split('?')[1]||'key=home'};},
 onPullDownRefresh(){return this.sync().then(()=>wx.stopPullDownRefresh());}
});
