/** Native screens share controllers with micro business regions. */
module.exports = function (name) {
 const screens = { discover: require('./screens/discover'), catalog: require('./screens/catalog'), favorites: require('./screens/favorites'), mine: require('./screens/mine'), read: require('./screens/read'), history:require('./screens/history') };
 const definition = screens[name];
 const micro=require('./micro');
 const presentation=require('./presentation');
 const methods = {onCaptionTap(event){if(this.data.caption.link)micro.openLink(this.data.caption.link);else if(name==='discover')definition.onOpenHero.call(this,event);},onSharePanel(){this.triggerEvent('shareopen',{}, {bubbles:true,composed:true});},onPresentationLink(event){micro.openLink(event.currentTarget.dataset.link);},onCustomAction(event){micro.openLink(event.currentTarget.dataset.link);}};
 Object.keys(definition).forEach(function (key) { if (typeof definition[key] === 'function') methods[key] = definition[key]; });
 Component({
  options: { addGlobalClass: true },
  properties: { galleryState:{type:Object,value:{},observer(value){if(name!=='read'||!this.properties.query._productPart||Number(value.productid)!==Number(this.data.product?.productid))return;const index=Math.max(0,Math.min(Number(value.index)||0,(this.data.slides||[]).length-1));this.setData({current:index},()=>{this.syncThumb();if(this.properties.query._productPart==='gallery')this.syncPlayback(index,true);});}}, actions:{type:Object,value:{}}, query: { type: Object, value: {}, observer(value) { this.setData(presentation.prepare((value||{})._memberOptions||{}));if(!this._screenReady)return; if(name==='catalog')this.setData({keyword:value.keyword||'',catid:Number(value.catid)||0,seriesid:Number(value.seriesid)||0}); if(definition.onLoad)definition.onLoad.call(this,value); if(definition.onShow)definition.onShow.call(this); } } },
  data: Object.assign({}, definition.data, { embedded: true }),
  methods: methods,
  lifetimes: {
   attached() {
    this.setData(presentation.prepare((this.properties.query||{})._memberOptions||{}));
    if (name === 'history') this.setData({ tab: this.properties.query.tab === 'share' || this.properties.query.action === 'share' ? 'share' : 'read' });
    if (name === 'catalog') this.setData({ keyword: this.properties.query.keyword || '', catid: Number(this.properties.query.catid) || 0, seriesid: Number(this.properties.query.seriesid) || 0 });
    if (definition.onLoad) definition.onLoad.call(this, this.properties.query || {});
    if (definition.onShow) definition.onShow.call(this);
    this._screenReady=true;
   },
   detached() { if (definition.onUnload) definition.onUnload.call(this); },
  },
  pageLifetimes: { show() { if (definition.onShow) definition.onShow.call(this); }, hide() { if (definition.onHide) definition.onHide.call(this); } },
 });
};
