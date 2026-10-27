const api = require('../../utils/api');
const page = require('../../utils/page');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		tab: 'read',
		logged: false,
		counts: { read: '00', share: '00' },
		groups: [],
	},

	onLoad() {
		this.load();
	},

	load() {
		const that = this;
		const token = (this._historyToken || 0) + 1;
		this._historyToken = token;
		this.setData({ status: 1 });
		return api.get({ key: 'history', action: this.data.tab, tab: this.data.tab }).then(function (res) {
			if (token !== that._historyToken) return;
			if (!res.ok) {
				throw new Error('bad response');
			}
			const member = res.member || { uid: 0 };
			const history = res.history || {};
			const counts = history.counts || member.historycounts || {};
			const groups = Object.keys(history.groups || {}).map(day => ({ day, items: history.groups[day] || [] }));
			that.setData({
				status: 2,
				logged: member.uid > 0,
				counts: { read: page.pad(Number(counts.read) || 0), share: page.pad(Number(counts.share) || 0) },
				groups,
			});
		}).catch(function () {
			if (token !== that._historyToken) return;
			that.setData({ status: 4 });
		});
	},

	onTab(event) {
		this.setData({ tab: event.currentTarget.dataset.tab }, () => this.load());
	},

	onOpenRecord(event) {
		const url = String(event.currentTarget.dataset.url || '').replace(/&amp;/g, '&');
		const product = url.match(/[?&]productid=(\d+)/);
		const album = url.match(/[?&]albumid=(\d+)/);
		if (product || album) {
			const query = product ? 'productid=' + product[1] : 'albumid=' + album[1];
			wx.navigateTo({ url: '/pages/micro/index?key=detail&' + query });
		}
	},

	onBack() {
		page.back('/pages/mine/index');
	},
	onDiscover() { wx.switchTab({ url: '/pages/discover/index' }); },

	onRetry() {
		this.load();
	},
};
