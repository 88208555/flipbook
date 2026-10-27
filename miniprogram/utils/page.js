/**
 * 页面通用能力：状态栏高度、返回、空态文案。
 */
function statusBar() {
	const app = getApp();
	return (app && app.globalData && app.globalData.statusBarHeight) || 20;
}

function back(fallback) {
	const pages = getCurrentPages();
	if (pages.length > 1) {
		wx.navigateBack();
		return;
	}
	wx.switchTab({
		url: fallback || '/pages/discover/index',
	});
}

function pad(value) {
	const number = parseInt(value, 10) || 0;
	return number < 10 ? '0' + number : String(number);
}

module.exports = {
	statusBar: statusBar,
	back: back,
	pad: pad,
};
