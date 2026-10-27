const api = require('../../utils/api');
const page = require('../../utils/page');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		keyword: '',
		showSeries:false,
		catid: 0,
		seriesid: 0,
		categories: [],
		series: [],
		albums: [],
		products: [],
	},

	onLoad() {
		this.load();
	},

	onShow() {
		if (typeof this.getTabBar === 'function' && this.getTabBar()) {
			this.getTabBar().setData({ selected: 1 });
		}
	},

	load() {
		const that = this;
		this.setData({ status: 1 });
		return api.get({ keyword: this.data.keyword, catid: this.data.catid, seriesid: this.data.seriesid }).then(function (res) {
			if (!res.ok) {
				throw new Error('bad response');
			}
			const albums = res.albums || [];
			const series = [{ seriesid: 0, name: '全部系列' }].concat(res.series || []);
			that.setData({
				status: albums.length || (that.data.keyword && (res.products || []).length) ? 2 : 3,
				products: that.data.keyword ? (res.products || []) : [],
				albums: albums,
				categories: res.categories || [],
				series: series,
			});
		}).catch(function () {
			that.setData({ status: 4 });
		});
	},

	onInput(event) {
		this.setData({ keyword: event.detail.value });
	},

	onConfirm() {
		this.load();
	},

	onCategory(event) {
		this.setData({ catid: Number(event.currentTarget.dataset.id) || 0 });
		this.load();
	},

	onOpenSeries() { this.setData({showSeries:true}); },
	onCloseSeries() { this.setData({showSeries:false}); },
	onSelectSeries(event) { this.setData({seriesid:Number(event.currentTarget.dataset.id)||0,showSeries:false}); this.load(); },
	onStopTap() {},
	onSeries(event) {
		const index = Number(event.detail.value) || 0;
		const item = this.data.series[index];
		this.setData({ seriesid: item ? item.seriesid : 0 });
		this.load();
	},

	onOpenProduct(event) { wx.navigateTo({ url: '/pages/micro/index?key=detail&productid=' + event.currentTarget.dataset.id }); },

	onOpenAlbum(event) {
		wx.navigateTo({ url: '/pages/micro/index?key=detail&albumid=' + event.currentTarget.dataset.id });
	},

	onRetry() {
		this.load();
	},
};
