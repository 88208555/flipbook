const api = require('../../utils/api');
const page = require('../../utils/page');
const toast = require('../../utils/toast');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		logged: false,
		member: null,
		tab: 'product',
		products: [], albums: [],
		counts: { product: '00', album: '00' },
	},

	onShow() {
		if (typeof this.getTabBar === 'function' && this.getTabBar()) {
			this.getTabBar().setData({ selected: 2 });
		}
		this.load();
	},

	load() {
		const that = this;
		this.setData({ status: 1 });
		api.get({}).then(function (res) {
			if (!res.ok) {
				throw new Error('bad response');
			}
			const member = res.member || { uid: 0 };
			const favorite = member.favorite || { product: 0, album: 0, total: 0 };
			that.setData({
				status: 2,
				member: member,
				products: (member.favorites || {}).products || [],
				albums: (member.favorites || {}).albums || [],
				logged: member.uid > 0,
				counts: {
					product: page.pad(favorite.product),
					album: page.pad(favorite.album),
				},
			});
		}).catch(function () {
			that.setData({ status: 4 });
		});
	},

	onTab(event) {
		this.setData({ tab: event.currentTarget.dataset.tab });
	},

	onLogin() {
		wx.navigateTo({ url: '/pages/login/index' });
	},

	onDiscover() {
		wx.switchTab({ url: '/pages/discover/index' });
	},

	onOpen(event) { const d = event.currentTarget.dataset; wx.navigateTo({ url: '/pages/micro/index?key=detail&' + d.type + 'id=' + d.id }); },
	onRemove(event) { const d = event.currentTarget.dataset; api.post('favorite', { targettype:d.type, targetid:d.id, do:'remove' }).then((res) => { toast.show(res.message); if(res.ok) this.load(); }).catch(() => toast.show('移除失败，请重试')); },
	onManage() {
		toast.show('登录后可管理');
	},

	onRetry() {
		this.load();
	},
};
