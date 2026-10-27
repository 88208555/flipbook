const api = require('../../utils/api');
const page = require('../../utils/page');
const toast = require('../../utils/toast');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		logged: false,
		member: null,
		resume: null, menuRows:[], memberOptions:{},
		counts: { favorite: '00', history: '00', share: '00' },
	},

 onLoad(query){let target=query&&query.return||'';try{target=decodeURIComponent(target);}catch(_){}if(/^\/pages\/checkout\/index\?productid=\d+&skuid=\d+$/.test(target))wx.redirectTo({url:'/pages/login/index?return='+encodeURIComponent(target)});},

	onShow() {
		if (typeof this.getTabBar === 'function' && this.getTabBar()) {
			this.getTabBar().setData({ selected: 3 });
		}
		this.load();
	},

	load() {
		const that = this;
  const options=(this.properties && this.properties.query && this.properties.query._memberOptions)||{};
  const rows=[['history','阅读历史','page:history','clock'],['share','分享记录','page:history','share-grey'],['about','关于画册','page:about','info'],['privacy','隐私协议','page:privacy','info'],['agreement','注册协议','page:agreement','info']].filter(r=>options[r[0]+'Enabled']!==0).map(r=>({key:r[0],label:options[r[0]+'Label']||r[1],link:options[r[0]+'Link']||r[2],icon:r[3]}));
  this.setData({memberOptions:options,menuRows:rows});
		this.setData({ status: 1 });
		api.get({}).then(function (res) {
			if (!res.ok) {
				throw new Error('bad response');
			}
			const member = res.member || { uid: 0 };
			const favorite = member.favorite || { total: 0 };
   if((res.commerce||{}).ordersEnabled)rows.unshift({key:'orders',label:'我的订单',link:'/pages/orders/index',icon:'orders'});
			that.setData({
				status: 2,
				member: member,
				logged: member.uid > 0,
    menuRows:rows,
				resume: member.resume && Number(member.resume.albumid) > 0 ? member.resume : null,
				counts: {
					favorite: page.pad(favorite.total),
					history: page.pad((member.historycounts || {}).read),
					share: page.pad((member.historycounts || {}).share),
				},
			});
		}).catch(function () {
			that.setData({ status: 4 });
		});
	},

	onResume() {
		const resume = this.data.resume;
		if (!resume || !resume.albumid) {
			return;
		}
		wx.navigateTo({
			url: '/pages/micro/index?key=detail&albumid=' + resume.albumid + '&page=' + (resume.progress ? resume.progress.page : 0),
		});
	},

 onLogin(){if(!this.data.logged)wx.navigateTo({url:'/pages/login/index'});},
 onLogout() {api.auth('logout',{}).then(res=>{if(!res.ok)throw new Error(res.message);this.load();}).catch(error=>toast.show(error.message||'退出失败'));},
 onMenu(event) {const row=this.data.menuRows[Number(event.currentTarget.dataset.index)];if(!row)return;if(row.key==='share' && row.link==='page:history'){wx.navigateTo({url:'/pages/micro/index?key=history&tab=share'});return;}require('../micro').openLink(row.link);},

	onHistory() {
		wx.navigateTo({ url: '/pages/history/index' });
	},

	onShareHistory() {
		wx.navigateTo({ url: '/pages/history/index?tab=share' });
	},


	onFavorites() {
		wx.switchTab({ url: '/pages/favorites/index' });
	},

	onAbout() { wx.navigateTo({url:'/pages/micro/index?key=about'}); },

	onRetry() {
		this.load();
	},
};
