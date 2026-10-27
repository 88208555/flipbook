const api=require('../../utils/api');
const micro=require('../../utils/micro');
const Core=require('../../utils/composer-core');
const events=require('../../utils/composer-events');
Component({
 options:{virtualHost:true,addGlobalClass:true,styleIsolation:'shared'},
 properties:{node:{type:Object,value:{}}},
 data:{questions:['无安全提问','母亲的名字','爷爷的名字','父亲出生的城市','其中一位老师的名字','计算机型号','最喜欢的餐馆名称','驾驶执照最后四位'],active:0,current:0,ended:{},keyword:'',galleryHeight:0,skuHeight:0,choiceOpen:false,fabOpen:false,fabMuted:true,fabFavorited:false,mutedOverride:null,searchFocused:false},
 pageLifetimes:{hide(){this._visible=false;clearTimeout(this._searchTimer);this.pauseVideos();},show(){this._visible=true;this.playCurrent();}},
 lifetimes:{attached(){this.bindChannel();},ready(){this._ready=true;this.measureGallery();},detached(){this._ready=false;if(this._unbind)this._unbind();if(this._muteOff)this._muteOff();if(this._replayOff)this._replayOff();clearTimeout(this._searchTimer);this.pauseVideos();}},
 observers:{'node':function(node){if(!node)return;this.bindChannel();if(this._ready)this.measureGallery();}},
 methods:{
  bindChannel(){if(this._unbind)this._unbind();if(this._muteOff)this._muteOff();if(this._replayOff)this._replayOff();const n=this.data.node;if(n&&n.kind==='search')this._unbind=events.on('search:before-filter',query=>{if(this._visible!==false&&query.key===(n.context||{}).key&&this._keywordEdited)query.keyword=this.data.keyword;});if(n&&(n.type==='home_gallery'||n.kind==='actions')){this._muteOff=events.on('home:mute',muted=>{if(this._visible!==false)this.setData({mutedOverride:muted,fabMuted:muted});});if(n.kind==='actions')this.setData({fabFavorited:n.fab.favorited});if(n.kind==='actions'&&!this._fabInitialized){this._fabInitialized=true;this.setData({fabMuted:n.fab.muted,fabFavorited:n.fab.favorited});}}if(!n||!['gallery','sku'].includes(n.kind))return;const key='gallery:'+((n.context||{}).productid||'home');this._channel=key;if(n.kind==='gallery')this._replayOff=events.on(key+':replay',index=>{if(this._visible!==false&&this.data.current===index)this.replayCurrent();});this._unbind=events.on(key,index=>{if(this._visible===false)return;if(this.data.current!==index){this.setData({current:index});this.pauseVideos(index);this.replayCurrent();this.measureGallery();}});},
  pauseVideos(keep){(this.data.node.items||[]).forEach((item,i)=>{if(item.video&&i!==keep)wx.createVideoContext('fc-'+this.data.node.id+'-'+i,this).pause();});},
  playCurrent(){const n=this.data.node;if(this._visible===false||!n||!n.visible||n.kind!=='gallery'||!n.props.playVideo)return;const i=this.data.current||0,item=(n.items||[])[i];if(item&&item.video&&!(this.data.ended||{})[i])wx.createVideoContext('fc-'+n.id+'-'+i,this).play();},
  replayCurrent(){const n=this.data.node,i=this.data.current||0;if(this._visible===false||!n||n.kind!=='gallery')return;if(!(this.data.ended||{})[i]){this.playCurrent();return;}this.setData({['ended.'+i]:false},()=>{const ready=()=>{if(this._ready===false||this._visible===false||this.data.current!==i)return;const video=wx.createVideoContext('fc-'+n.id+'-'+i,this);video.seek(0);if(n.props.playVideo)video.play();};if(typeof wx.nextTick==='function')wx.nextTick(ready);else ready();});},
  onMediaEnded(e){if(!this.data.node.props.posterOnEnd)return;const index=Number(e.currentTarget.dataset.index)||0;this.setData({['ended.'+index]:true});},
  onGalleryTap(e){const n=this.data.node;if(n.props.controls)return;const link=e.currentTarget.dataset.link;if(link)micro.openLink(link);},
  onSlide(e){const current=Number(e.detail.current)||0;this.setData({current});events.emit(this._channel,current);this.pauseVideos(current);this.replayCurrent();this.measureGallery();const n=this.data.node,item=(n.items||[])[current];if(n.type==='home_gallery')events.emit('home:current',item||{});},
  onPick(e){const index=Number(e.currentTarget.dataset.index)||0;this.setData({current:index});events.emit(this._channel,index);events.emit(this._channel+':replay',index);this.pauseVideos(index);this.replayCurrent();this.measureGallery();},
  measureGallery(detail){const node=this.data.node,p=node.props||{},current=this.data.current||0;if(node.kind!=='gallery')return;if(Number(p.boxHeight)>0){this.setData({galleryHeight:Number(p.boxHeight)*wx.getWindowInfo().windowWidth/375});return;}const ratio=Core.galleryRatio(p,detail||(this._mediaSizes||{})[current]);if(!ratio)return;this.createSelectorQuery().select('.fc-gallery').boundingClientRect(rect=>{if(this._ready!==false&&rect&&rect.width&&this.data.node.props===p&&this.data.current===current)this.setData({galleryHeight:rect.width/ratio});}).exec();},
  onMediaLoad(e){const index=Number(e.currentTarget.dataset.index)||0;this._mediaSizes=this._mediaSizes||{};this._mediaSizes[index]=e.detail;if(index===(this.data.current||0))this.measureGallery();},
  onSkuLoad(){if(Number(this.data.node.props.itemHeight)>0)return;this.createSelectorQuery().select('.fc-sku').boundingClientRect(rect=>{if(rect&&rect.width)this.setData({skuHeight:rect.width});}).exec();},
  onSearchFocus(){this.setData({searchFocused:true});},onSearchBlur(e){this.setData({searchFocused:false});this.onSearch(e);},
  onSearchInput(e){this._keywordEdited=true;this.setData({keyword:e.detail.value});clearTimeout(this._searchTimer);this._searchTimer=setTimeout(()=>this.onSearch({detail:{}}),Number(this.data.node.props.searchDelay)||0);},
  onSearch(e){clearTimeout(this._searchTimer);const value=(e.detail||{}).value,keyword=String(value==null?(this._keywordEdited?this.data.keyword:this.data.node.context.keyword||''):value).trim(),target=Core.searchHref(this.data.node.props.action||'page:search',Object.assign({},this.data.node.context.query,{keyword}),true);if(!target)return;if(keyword===(this.data.node.context.keyword||''))return;if((this.data.node.props.action||'page:search')==='page:'+this.data.node.context.key)events.emit('search:query',{key:this.data.node.context.key,keyword});else micro.openLink(target);},
  onChoiceOpen(){this.setData({choiceOpen:true});},onChoiceClose(){this.setData({choiceOpen:false});},onChoicePick(e){this.setData({choiceOpen:false});const item=this.data.node.items[Number(e.currentTarget.dataset.index)||0];if(item){const ctx=this.data.node.context||{},query={key:ctx.key,keyword:ctx.keyword};events.emit('search:before-filter',query);micro.openLink(query.keyword==null?item.href:Core.searchHref(item.href,{keyword:query.keyword},true));}},
  onFabToggle(){this.setData({fabOpen:!this.data.fabOpen});},onFabMute(){events.emit('home:mute',!this.data.fabMuted);},
  onSelect(e){const item=(this.data.node.items||[])[Number(e.detail.value)||0];if(item)micro.openLink(item.href);},
  onTab(e){this.setData({active:Number(e.currentTarget.dataset.index)||0});},
  onPreview(e){const n=this.data.node,index=Number(e.currentTarget.dataset.index)||0,item=n.kind==='gallery'?(n.items||[])[index]||{}:{image:n.src},link=item.link||n.link;if(link){micro.openLink(link);return;}if(!n.props.preview)return;const current=item.image||item.cover;if(!current)return;const urls=n.kind==='gallery'?(n.items||[]).filter(i=>!i.video).map(i=>i.image||i.cover).filter(Boolean):[current];wx.previewImage({current,urls:urls.length?urls:[current]});},
  onLink(e){if(e.target&&e.target.dataset&&e.target.dataset.preview)return;const link=e.currentTarget.dataset.link||this.data.node.link;if(link==='action:login'){if(!(this.data.node.context||{}).uid)wx.navigateTo({url:'/pages/login/index'});return;}if(link)micro.openLink(link);},
  onLoginField(e){events.emit('login:field',{field:e.currentTarget.dataset.field||this.data.node.props.field,value:e.detail.value});},onLoginQuestion(e){events.emit('login:field',{field:'questionid',value:Number(e.detail.value)||0});},
  onLoginAction(e){events.emit('login:action',e.currentTarget.dataset.action);},
  onAction(e){const n=this.data.node,p=n.props||{},ctx=n.context||{},action=e&&e.currentTarget&&e.currentTarget.dataset.action||p.action;const homeTarget=(ctx.home||{}).albumid;let task;if(/^login_/.test(action)){events.emit('login:action',action);return;}
   if(action==='open'){this.onLink({currentTarget:{dataset:{link:n.link}}});return;}
   if(action==='login'){wx.navigateTo({url:'/pages/login/index'});return;}
   if(action==='customer')return;
   if(action==='orders'){micro.openLink('/pages/orders/index');return;}
   if(action==='buy'){const productid=ctx.productid;if(productid)micro.openLink('/pages/checkout/index?productid='+productid);return;}
   if(action==='share'){events.emit('share:open',ctx.productid);return;}
   if(!ctx.uid){wx.navigateTo({url:'/pages/login/index'});return;}
   if(action==='logout')task=api.auth('logout');
   if(action==='favorite'||action==='favorite_remove')task=api.post('favorite',{targettype:n.kind==='actions'?'album':ctx.item&&ctx.item.targettype||(ctx.productid?'product':'album'),targetid:n.kind==='actions'?(ctx.home||{}).albumid:ctx.item&&ctx.item.targetid||ctx.productid||(ctx.home||{}).albumid,do:action==='favorite_remove'||n.kind==='actions'&&this.data.fabFavorited?'remove':'add'});
   if(task)task.then(res=>{if(!res.ok)throw Error(res.message);if(n.kind==='actions'){if((this.data.node.context.home||{}).albumid===homeTarget)this.setData({fabFavorited:!!res.favorited});events.emit('home:favorite',{albumid:homeTarget,favorited:!!res.favorited});}else events.emit('refresh');}).catch(error=>wx.showToast({title:error.message||'操作失败，请重试',icon:'none'}));
  }
 }
});
