module.exports = function(key) {
 const definition = require('./micro-page');
 const loading = require('./page-loading');
 return Object.assign({}, definition, {
  data: Object.assign({}, definition.data,{pageLoadingVisible:false,pageLoadingLogo:loading.DEFAULT}),
  onLoad(query) { definition.onLoad.call(this, Object.assign({}, query || {}, { key: key || (query && query.key) || 'home' })); },
  onShow() { loading.enter(this);return definition.onShow.call(this); },
  sync() { return loading.track(this,definition.sync.call(this)); },
  onHide() { loading.leave(this);if(definition.onHide)return definition.onHide.call(this); },
  onUnload() { loading.leave(this);if(definition.onUnload)return definition.onUnload.call(this); },
 });
};
