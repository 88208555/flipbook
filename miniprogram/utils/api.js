const config = require('../config');
const pageLoading = require('./page-loading');
let sessionCookie = wx.getStorageSync('flipbook_session') || '';
let formhash = '';
function remember(res) {
 if (res.cookies && res.cookies.length) { const values = {}; sessionCookie.split('; ').filter(Boolean).forEach((v) => { values[v.split('=')[0]] = v; }); res.cookies.forEach((v) => { const pair = v.split(';')[0]; values[pair.split('=')[0]] = pair; }); sessionCookie = Object.keys(values).map((key) => values[key]).join('; '); }
 wx.setStorageSync('flipbook_session',sessionCookie);
 if (res.data && res.data.formhash) formhash = res.data.formhash;
}

/**
 * 拼装只读内容接口地址：plugin.php?id=flipbook:api
 */
function url(params) {
	const query = Object.keys(params || {})
		.filter(function (key) {
			const value = params[key];
			return value !== '' && value !== undefined && value !== null;
		})
		.map(function (key) {
			return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
		})
		.join('&');
	return config.API_BASE + '/plugin.php?id=' + config.PLUGIN_ID + ':api' + (query ? '&' + query : '');
}

/**
 * 接口里的图片与页内链接是站点相对路径，统一补全为可请求的绝对地址。
 */
function normalize(node) {
	if (Array.isArray(node)) {
		return node.map(normalize);
	}
	if (node && typeof node === 'object') {
		Object.keys(node).forEach(function (key) {
			node[key] = normalize(node[key]);
		});
		return node;
	}
	if (typeof node === 'string' && node.charAt(0) === '/' && node.charAt(1) !== '/') {
		return config.API_BASE + node;
	}
	if (typeof node === 'string' && /^(?:\.\/)?(source|static|data)\//.test(node)) {
		return config.API_BASE + '/' + node.replace(/^\.\//,'');
	}
	return node;
}

/**
 * GET 内容接口，统一返回受控 JSON。
 */
function get(params, options) {
	const ticket = options && options.quiet ? null : pageLoading.start();
	return new Promise(function (resolve, reject) {
		wx.request({
			url: url(params),
			method: 'GET',
			timeout: config.REQUEST_TIMEOUT,
			header: {
				'content-type': 'application/json',
				cookie: sessionCookie,
			},
			success: function (res) {
				remember(res);
				if (res.statusCode === 200 && res.data && typeof res.data === 'object') {
					resolve(normalize(res.data));
				} else {
					reject(new Error('bad response'));
				}
			},
			fail: function (err) {
				reject(err);
			},
		});
	}).then(function(res) { pageLoading.response(ticket,res);pageLoading.end(ticket);return res; }, function(error) { pageLoading.end(ticket);throw error; });
}

function post(op, values, module, options) {
 const ready=options&&options.reuseSession&&formhash?Promise.resolve():get({},options);
 return ready.then(function () {
  return new Promise(function(resolve, reject) {
   wx.request({ url: config.API_BASE + '/plugin.php?id=' + config.PLUGIN_ID + ':' + (module || 'action') + '&op=' + encodeURIComponent(op), method:'POST', timeout:config.REQUEST_TIMEOUT,
    header: { 'content-type':'application/x-www-form-urlencoded', cookie:sessionCookie }, data:Object.assign({}, values, { formhash:formhash }),
    success(res) { remember(res); if(res.statusCode === 200 && res.data && typeof res.data === 'object') resolve(res.data); else reject(new Error('bad response')); }, fail:reject });
  });
 });
}

// Only order media are URLs. Signatures and user-entered text are opaque values.
function normalizeCommerce(data) {
 function media(order){if(order&&order.snapshot&&typeof order.snapshot.image==='string')order.snapshot.image=normalize(order.snapshot.image);}
 media(data.order);if(data.orders&&Array.isArray(data.orders.items))data.orders.items.forEach(media);return data;
}

function commerce(action,values) {
 const mutating=['create','pay','refresh','close'].includes(action);
 const send=()=>{const ticket=pageLoading.start();return new Promise((resolve,reject)=>{
  const payload=Object.assign({},values||{});if(payload.address&&typeof payload.address==='object')payload.address=JSON.stringify(payload.address);if(mutating)payload.formhash=formhash;
  const query=mutating?{}:payload;query.commerce_action=action;
  wx.request({url:url(query),method:mutating?'POST':'GET',timeout:30000,header:{'content-type':'application/x-www-form-urlencoded',cookie:sessionCookie},data:mutating?payload:undefined,success(res){remember(res);const data=res.data;if(!data||typeof data!=='object'){reject(Error('订单服务暂不可用'));return;}if(data.ok){const expected=action==='state'?data.state:action==='orders'?data.orders:data.order;if(!expected||typeof expected!=='object'||action==='orders'&&!Array.isArray(expected.items)){reject(Error('订单服务尚未完成配置，请联系管理员'));return;}}resolve(normalizeCommerce(data));},fail:reject});
 }).then(value=>{pageLoading.response(ticket,value);pageLoading.end(ticket);return value;},error=>{pageLoading.end(ticket);throw error;});};
 return mutating?commerce('state').then(result=>{if(!result.ok)throw Error(result.message||'订单服务尚未开放');return send();}):send();
}

module.exports = {
	url: url,
	get: get,
 commerce,
	post: post,
 auth(op,values) {return post(op,values,'auth');},
};
