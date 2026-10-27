const videos = require('../videos');
const api = require('../../utils/api');
const page = require('../../utils/page');
const toast = require('../../utils/toast');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		albums: [],
		hero: [],
		current: 0,
		fabOpen: false,
		muted: true,
		ended: {},
		logged: false,
		refreshing: false,
	},

	onLoad() {
		this.load();
	},

	onShow() {
		videos.resume(this);
		if (typeof this.getTabBar === 'function' && this.getTabBar()) {
			this.getTabBar().setData({ selected: 0 });
		}
	},

	onPullDownRefresh() {
		this.load(true).then(function () {
			wx.stopPullDownRefresh();
		});
	},

	load(silent) {
		const that = this;
		if (!silent) {
			this.setData({ status: 1 });
		}
		return api.get({}).then(function (res) {
			if (!res.ok) {
				throw new Error('bad response');
			}
			const albums = res.albums || [];
			const member = res.member || {};
			that.setData({
				status: albums.length ? 2 : 3,
				albums: albums,
				hero: albums,
				logged: Number(member.uid) > 0,
			}, function() { videos.observe(that, '.fb-hero-video'); if(that.triggerEvent)that.triggerEvent('viewchange',{title:albums[0]?.name||'',subtitle:albums[0]?.subtitle||'',productid:albums[0]?.firstproductid||0},{bubbles:true,composed:true}); });
		}).catch(function () {
			if (!silent) {
				that.setData({ status: 4 });
			}
		});
	},

	/**
	 * 切换时只播放当前视频，其余视频暂停，防止声音叠加。
	 */
	onSwiperChange(event) {
		const index = event.detail.current;
		const hero = this.data.hero || [];
		this.setData({ current: index, fabOpen: false });
		if(this.triggerEvent)this.triggerEvent('viewchange',{title:hero[index]?.name||'',subtitle:hero[index]?.subtitle||'',productid:hero[index]?.firstproductid||0},{bubbles:true,composed:true});
		const owner = this;
		hero.forEach(function (item, i) {
			if (!item.hasvideo) {
				return;
			}
			const ctx = wx.createVideoContext('hero-vid' + i, owner);
			if (i === index && !owner._videoEnded?.['hero-vid' + i]) {
				ctx.play();
			} else {
				ctx.pause();
			}
		});
	},

	onTouchStart(event) {
		const touch = event.touches && event.touches[0];
		this.startY = touch ? touch.clientY : 0;
	},

	/**
	 * 第一册继续下拉时刷新内容；两秒内只触发一次。
	 */
	onTouchEnd(event) {
		if(this.data.embedded) return;
		const touch = event.changedTouches && event.changedTouches[0];
		if (!touch || this.data.current !== 0) {
			return;
		}
		const now = Date.now();
		if (touch.clientY - (this.startY || 0) < 90 || now - (this.lastTop || 0) < 2000) {
			return;
		}
		this.lastTop = now;
		this.refresh();
	},

	/**
	 * 顶部刷新区显示状态动画，最短展示 600ms，不再用右下角消息提示。
	 */
	refresh() {
		if (this.refreshing) {
			return;
		}
		this.refreshing = true;
		this.setData({ refreshing: true });
		const started = Date.now();
		const done = () => {
			const wait = Math.max(0, 600 - (Date.now() - started));
			setTimeout(() => {
				this.refreshing = false;
				this.setData({ refreshing: false });
			}, wait);
		};
		this.load(true).then(done, done);
	},

	onOpenAlbum(event) {
		const albumid = event.currentTarget.dataset.id;
		wx.navigateTo({ url: '/pages/micro/index?key=detail&albumid=' + albumid });
	},

	onOpenHero(event) {
		const albumid = event.currentTarget.dataset.id;
		wx.navigateTo({ url: '/pages/micro/index?key=detail&albumid=' + albumid });
	},

	onVideoEnded(event) { videos.ended(this, event); },
	onHide() { videos.pauseAll(this); },
	onUnload() { videos.detach(this); },
	onToggleMute() {
		this.setData({ muted: !this.data.muted });
	},

	onToggleFab() {
		this.setData({ fabOpen: !this.data.fabOpen });
	},

	onFavorite() {
		this.setData({ fabOpen: false });
		if (!this.data.logged) {
			toast.show('请先登录');
			return;
		}
		const item = this.data.hero[this.data.current];
		if (item) api.post('favorite', { targettype:'album', targetid:item.albumid, do:'add' }).then((res) => toast.show(res.message)).catch(() => toast.show('收藏失败，请重试'));
	},

	onShareAppMessage() {
		const album = this.data.hero[this.data.current] || {};
		return {
			title: album.name || '工装画册',
			path: album.albumid ? '/pages/micro/index?key=detail&albumid=' + album.albumid : '/pages/discover/index',
		};
	},

	onRetry() {
		this.load();
	},
};
