const api=require('./api');
const STATUS={pending:'待支付',paid:'已付款',shipped:'已发货',closed:'已关闭'};
function loginCode(){return new Promise((resolve,reject)=>wx.login({success:res=>res.code?resolve(res.code):reject(Error('请重新微信登录')),fail:reject}));}
function payment(params){
 if(!params||!['timeStamp','nonceStr','package','paySign'].every(key=>typeof params[key]==='string'&&params[key])||params.signType!=='RSA')return Promise.reject(Error('支付参数无效，请刷新订单'));
 return new Promise((resolve,reject)=>wx.requestPayment({timeStamp:params.timeStamp,nonceStr:params.nonceStr,package:params.package,signType:'RSA',paySign:params.paySign,success:resolve,fail:reject}));
}
function message(error){if(error&&/cancel/i.test(error.errMsg||''))return '已取消本次操作，请核对订单状态';return error&&error.message||error&&error.errMsg||'操作未完成，请稍后在订单中核对';}
async function payOrder(id){const result=await api.commerce('pay',{orderid:id});if(!result.ok)throw Error(result.message);let paymentError;try{if(result.payment)await payment(result.payment);}catch(error){paymentError=error;}const refreshed=await api.commerce('refresh',{orderid:id});if(!refreshed.ok)throw Error(refreshed.message);if(paymentError&&!['paid','shipped'].includes(refreshed.order.status))throw Error(message(paymentError));return refreshed.order;}
function decorate(order){return Object.assign({},order,{statusText:STATUS[order.status]||order.status,createdText:new Date(order.created*1000).toLocaleString(),skuText:order.snapshot&&order.snapshot.sku?[order.snapshot.sku.name,order.snapshot.sku.code].filter(Boolean).join(' · '):''});}
module.exports={loginCode,payment,payOrder,message,decorate,STATUS};
