const api=require('../../utils/api'),share=require('../../utils/share'),config=require('../../config');
Component({
 properties:{visible:{type:Boolean,value:false},productid:{type:Number,value:0}},
 data:{product:null,settings:{},qrUrl:'',qrReady:false,qrError:'',loading:false,error:'',saving:false,title:'',subtitle:'',description:'',imageUrl:'',posterHeight:1050,previewHeight:0,dialogMaxHeight:0},
 observers:{'visible,productid':function(visible,id){if(visible&&id)this.load();}},
 methods:{
  onStop(){},onClose(){this.triggerEvent('close');},
  onFriendShare(){this.triggerEvent('close');},
  load(){const id=this.properties.productid,token=(this._loadToken||0)+1;this._loadToken=token;this.setData({loading:true,error:'',product:null,qrReady:false,qrError:''},()=>this.resize());return api.get({productid:id}).then(res=>{if(this._loadToken!==token)return;if(!res.ok||!res.product)throw new Error('商品已下架');const settings=res.app.share||{},product=res.product,content=share.content(product);this.setData({loading:false,product,settings,title:content.title,subtitle:content.subtitle,description:content.description,imageUrl:content.image,qrUrl:config.API_BASE+'/plugin.php?id=flipbook:share&productid='+id+'&channel='+(settings.qrChannel||'h5')},()=>this.resize());this.triggerEvent('change',{product,settings});}).catch(()=>{if(this._loadToken===token)this.setData({loading:false,error:'商品加载失败，请重试'},()=>this.resize());});},
  resize(){if(!this.properties.visible||this._layoutQueued||typeof wx.createSelectorQuery!=='function')return;this._layoutQueued=true;const run=()=>{this._layoutQueued=false;if(!this.properties.visible)return;wx.createSelectorQuery().in(this).select('.fb-share-product').boundingClientRect().select('.fb-share-status').boundingClientRect().select('.fb-share-heading').boundingClientRect().select('.fb-share-buttons').boundingClientRect().exec(rects=>{if(!this.properties.visible||!rects[2]||!rects[3])return;const info=wx.getWindowInfo(),viewport=info.windowHeight,menu=typeof wx.getMenuButtonBoundingClientRect==='function'?wx.getMenuButtonBoundingClientRect():null,maxHeight=viewport-Math.max((info.statusBarHeight||0)+44,(menu&&menu.bottom||0)+8),chrome=rects[2].height+rects[3].height,natural=(rects[0]||rects[1]||{height:80}).height,height=Math.max(1,Math.min(Math.max(natural,viewport*.5-chrome),maxHeight-chrome));if(Math.abs(height-this.data.previewHeight)>1||maxHeight!==this.data.dialogMaxHeight)this.setData({previewHeight:height,dialogMaxHeight:maxHeight});});};if(typeof wx.nextTick==='function')wx.nextTick(run);else run();},
  onImageLoaded(){this.resize();},
  onQrLoaded(){this.setData({qrReady:true,qrError:''},()=>this.resize());},onQrError(){this.setData({qrReady:false,qrError:'二维码生成失败，请检查后台分享设置'},()=>this.resize());},
  image(src){return new Promise((resolve,reject)=>wx.getImageInfo({src,success:resolve,fail:reject}));},
  async onSave(){if(this.data.saving||!this.data.qrReady)return;const product=this.data.product,s=this.data.settings,content={title:this.data.title,subtitle:this.data.subtitle,description:this.data.description,image:this.data.imageUrl},qrUrl=this.data.qrUrl;this.setData({saving:true});try{
   const [cover,qr]=await Promise.all([this.image(content.image),this.image(qrUrl)]),ctx=wx.createCanvasContext('product-poster',this);
   ctx.setFontSize(24);const description=share.wrap(ctx,content.description,450),layout=share.posterLayout(cover,description.length),height=layout.height;await new Promise(resolve=>this.setData({posterHeight:height},resolve));
   ctx.setFillStyle(s.bg||'#fff');ctx.fillRect(0,0,750,height);
   const image=layout.image;ctx.save();share.rounded(ctx,image.x,image.y,image.width,image.height,Number(s.radius||0)*2);ctx.clip();ctx.drawImage(cover.path,image.x,image.y,image.width,image.height);ctx.restore();
   if(s.showPrice&&product.price?.value){const price=(product.price.currency&&product.price.currency!=='CNY'?product.price.currency+' ':'¥')+product.price.value;ctx.setFontSize(28);const width=ctx.measureText(price).width+32,x=image.x+image.width-width-20,y=image.y+image.height-72;share.rounded(ctx,x,y,width,52,26);ctx.setFillStyle('#111');ctx.fill();ctx.setFillStyle('#fff');ctx.fillText(price,x+16,y+36);}
   ctx.setFillStyle(s.color||'#111');ctx.setFontSize(30);ctx.fillText(share.single(ctx,content.title,layout.textWidth),40,layout.titleY);
   ctx.setFillStyle('#666');ctx.setFontSize(24);ctx.fillText(share.single(ctx,content.subtitle,layout.textWidth),40,layout.subtitleY);description.forEach((line,i)=>ctx.fillText(line,40,layout.descriptionY+i*36));
   ctx.drawImage(qr.path,layout.qrX,layout.qrY,layout.qrSize,layout.qrSize);ctx.fillText(share.single(ctx,s.qrHint||'扫码查看商品',layout.qrSize),layout.qrX,layout.bottom);
   await new Promise(resolve=>ctx.draw(false,resolve));const file=await new Promise((resolve,reject)=>wx.canvasToTempFilePath({canvasId:'product-poster',width:750,height,destWidth:750,destHeight:height,success:r=>resolve(r.tempFilePath),fail:reject},this));
   await new Promise((resolve,reject)=>wx.saveImageToPhotosAlbum({filePath:file,success:resolve,fail:reject}));wx.showToast({title:'海报已保存',icon:'success'});
  }catch(error){wx.showToast({title:/auth deny|authorize|permission/i.test(error.errMsg||'')?'请在设置中允许保存到相册':'海报保存失败，请重试',icon:'none'});}finally{this.setData({saving:false});}}
 }
});
