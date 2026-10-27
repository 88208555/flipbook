const api=require('../../utils/api'),commerce=require('../../utils/commerce'),page=require('../../utils/page'),loading=require('../../utils/page-loading');
Page({
 data:{statusBar:20,product:null,skus:[],skuid:0,quantity:1,address:{name:'',phone:'',province:'',city:'',district:'',detail:''},unitFen:0,totalFen:0,total:'0.00',shipping:'0.00',busy:false,ready:false,needLogin:false,error:''},
 onLoad(query){this.productid=Number(query.productid)||0;this.requestedSku=Number(query.skuid)||0;this.key='checkout';this.requestKey=Date.now().toString(36)+'_'+Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2);this.setData({statusBar:page.statusBar()});},
 onShow(){loading.enter(this);this.load();},onHide(){loading.leave(this);},onUnload(){loading.leave(this);},
 load(){if(this.loading)return;this.loading=true;this.setData({ready:false,error:''});return api.get({productid:this.productid}).then(res=>{
  if(!res.ok||!res.product)throw Error(res.message||'商品不可用');const state=res.commerce||{},product=res.product,skus=(product.skus||[]).filter(row=>Number(row.available)===1);this.setData({product,needLogin:!(res.member&&res.member.uid>0)});
  if(!state.enabled)throw Error(state.reason||'商品交易尚未开放');if(!product.price||product.price.currency!=='CNY')throw Error('该商品尚未开放人民币交易');
  const skuid=skus.some(row=>Number(row.skuid)===this.requestedSku)?this.requestedSku:skus.length?Number(skus[0].skuid):0;this.shippingFen=Number(state.shippingFen)||0;
  this.setData({skus,skuid,unitFen:Math.round(Number(product.price.value)*100),shipping:(this.shippingFen/100).toFixed(2),ready:true},()=>this.totals());
 }).catch(error=>this.setData({error:commerce.message(error)})).then(()=>{this.loading=false;});},
 totals(){const totalFen=this.data.unitFen*this.data.quantity+(this.shippingFen||0);this.setData({totalFen,total:(totalFen/100).toFixed(2)});},
 onSku(event){if(!this.data.busy)this.setData({skuid:Number(event.detail.value)||0});},
 onQuantity(event){if(this.data.busy)return;const quantity=Math.max(1,Math.min(99,Math.floor(Number(event.detail.value)||1)));this.setData({quantity},()=>this.totals());},
 onAddress(event){if(this.data.busy)return;const key=event.currentTarget.dataset.key;if(Object.prototype.hasOwnProperty.call(this.data.address,key))this.setData({['address.'+key]:event.detail.value});},
 onChooseAddress(){if(this.data.busy)return;wx.chooseAddress({success:res=>this.setData({address:{name:res.userName,phone:res.telNumber,province:res.provinceName,city:res.cityName,district:res.countyName,detail:res.detailInfo}}),fail:()=>this.setData({error:'暂时无法读取微信收货地址，请手动填写'})});},
 onLogin(){const target='/pages/checkout/index?productid='+this.productid+'&skuid='+this.data.skuid;wx.navigateTo({url:'/pages/login/index?return='+encodeURIComponent(target)});},
 async onPay(){
  if(this.data.busy||!this.data.ready)return;if(this.data.needLogin){this.onLogin();return;}this.setData({busy:true,error:''});let order;
  try{
   const code=await commerce.loginCode(),result=await api.commerce('create',{requestKey:this.requestKey,code,productid:this.productid,skuid:this.data.skuid,quantity:this.data.quantity,expectedTotalFen:this.data.unitFen*this.data.quantity+(this.shippingFen||0),address:this.data.address});
   if(!result.ok)throw Error(result.message);order=result.order;
   if(result.paymentPending)throw Error(result.message);
   if(result.payment)await commerce.payment(result.payment);
   const refreshed=await api.commerce('refresh',{orderid:order.id});if(!refreshed.ok)throw Error(refreshed.message);
   wx.redirectTo({url:'/pages/orders/index?orderid='+order.id});
  }catch(error){
   if(order){wx.redirectTo({url:'/pages/orders/index?orderid='+order.id+'&notice=verify'});}
   else this.setData({error:commerce.message(error)});
  }finally{this.setData({busy:false});}
 },
 onBack(){page.back('/pages/discover/index');},onRetry(){this.load();},
});
