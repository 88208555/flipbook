const config = require('./config');

App({
	globalData: {
		apiBase: config.API_BASE,
		statusBarHeight: 20,
		member: null,
	},

	onLaunch() {
		try {
			const info = wx.getWindowInfo ? wx.getWindowInfo() : wx.getSystemInfoSync();
			this.globalData.statusBarHeight = info.statusBarHeight || 20;
		} catch (e) {
			this.globalData.statusBarHeight = 20;
		}
	},
});
