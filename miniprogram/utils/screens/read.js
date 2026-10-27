const videos = require('../videos');
const api = require('../product-data');
const page = require('../../utils/page');
const toast = require('../../utils/toast');
const preferences = require('../preferences');

module.exports = {
	data: {
		statusBar: page.statusBar(),
		status: 0,
		album: null,
		product: null,
		slides: [],
		skuImages: [],
		highlights: [],
		details: [],
		spec: { colors: [], sizes: [], sizetable: [], model: '', fit: '' },
		price: null,
		current: 0,
		totalpad: '00',
		thumbInto: '',
		scrollInto: '',
		fabOpen: false,
		muted: true,
		ended: {},
		refreshing: false,
		preference: preferences.defaults(),
	},

	onLoad(query) {
		query = query || {};
		this.albumid = Number(query.albumid) || 0;
		this.productid = Number(query.productid) || 0;
		this.hasExplicitIndex = query.index !== undefined && Number.isFinite(Number(query.index)) && Number(query.index) >= 0;
		this.startIndex = this.hasExplicitIndex ? Math.floor(Number(query.index)) : 0;
		this.logged = false;
		this.load();
	},

	load(silent) {
		const that = this;
		if (!silent) {
			this.setData({ status: 1 });
		}
		const albumTask = this.albumid
			? api.get({ albumid: this.albumid })
			: Promise.resolve({ ok: 1, album: null });
		return albumTask.then(function (res) {
			if (!res.ok) {
				throw new Error('bad response');
			}
			const album = res.album || null;
			const member = res.member || {};
			that.logged = Number(member.uid) > 0;
			const productid = that.productid || (album && album.firstproductid) || 0;
			if (!productid) {
				throw new Error('no product');
			}
			return api.get({ productid: productid }).then(function (detail) {
				if (!detail.ok || !detail.product) {
					throw new Error('bad response');
				}
				if(that.albumid && !(detail.product.albums || []).some((item)=>Number(item.albumid) === Number(that.albumid))) throw new Error('product does not belong to album');
				that.logged = Number((detail.member || {}).uid) > 0;
				that.tradeState=detail.commerce||{};that.apply(album, detail.product);
			});
		}).catch(function () {
			if (!silent) {
				that.setData({ status: 4 });
			}
		});
	},

	/**
	 * 轮播统一取当前款式的图组：首项为款式视频（有视频时），其后为卖点、模特、细节图。
	 */
	apply(album, product) {
		const groups = product.groups || {};
		const collect = function (type) {
			return (groups[type] || []).filter(function (item) {
				return item.hasimage && Number(item.productid) === Number(product.productid);
			}).map(function (item) {
				return {
					key: type + '-' + item.mediaid,skuid:item.skuid||0,skuCode:item.skuCode||'',
					image: item.image,
					title: item.title || product.name,
					text: item.text || '',
					type: type,
				};
			});
		};
		const highlights = collect('highlight');
		const details = collect('detail');
		const models = collect('model');
  const skuItems=collect('sku');
  const slides=[];
  if(product.hasvideo && product.video)slides.push({key:'video',type:'video',video:product.video,image:product.firstframe||product.cover||'',title:product.name});
  if(product.cover)slides.push({key:'cover',type:'cover',image:product.cover,title:product.name});
  skuItems.forEach(item=>slides.push(item));
  const skuImages=slides.map((item,index)=>Object.assign({},item,{slideIndex:index}));

		slides.forEach(function (item, index) {
			item.index = index + 1;
			item.indexpad = page.pad(index + 1);
		});
		const spec = product.spec || {};
		const preference = preferences.read();
		let selected = this.startIndex || 0;
		if (this.data.product && Number(this.data.product.productid) === Number(product.productid)) {
			const previous = (this.data.slides || [])[this.data.current];
			const retained = previous ? slides.findIndex(slide => slide.key === previous.key) : -1;
			selected = retained >= 0 ? retained : Number(this.data.current) || 0;
		} else if (!this.hasExplicitIndex) {
			selected = preferences.progress(product.productid, slides);
		}
		const current = Math.max(0, Math.min(selected, Math.max(0, slides.length - 1)));
		this.setData({
			preference,
   tradeEnabled:!!((this.tradeState||{}).enabled&&product.price&&product.price.currency==='CNY'),tradeText:(this.tradeState||{}).buyText||'立即购买',customer:(this.tradeState||{}).customer||{},customerPath:'/pages/micro/index?key=detail&productid='+product.productid,
			status: slides.length ? 2 : 3,
			album: album,
			product: product,
			slides: slides,
			skuImages: skuImages,
			ended: {},
			highlights: highlights,
			details: details,
			spec: {
				colors: spec.colors || [],
				sizes: spec.sizes || [],
				sizetable: spec.sizetable || [],
				sizeTableNote: spec.sizeTableNote || '',
				model: spec.model || '',
				fit: spec.fit || '',
			},
			price: product.price && product.price.value ? product.price : null,
			totalpad: page.pad(slides.length),
			current: current,
		}, () => { this.syncThumb(); videos.observe(this, '.fb-reader-video'); if(this.triggerEvent)this.triggerEvent('viewchange',{title:product.name,subtitle:product.summary,product},{bubbles:true,composed:true}); });
	},

	syncThumb() {
		this.setData({ thumbInto: 'thumb' + this.data.current });
	},

	rememberPosition() {
		if (this.data.product) preferences.remember(this.data.product.productid, this.data.current, this.data.slides);
	},

	/**
	 * 只让当前轮播里的视频播放，其余视频暂停，避免声音叠加。
	 */
	syncPlayback(index, restart) {
		const slides = this.data.slides || [];
		const owner = this;
		slides.forEach(function (item, i) {
			if (item.type !== 'video') {
				return;
			}
			const ctx = wx.createVideoContext('vid' + i, owner);
			if (i === index && restart) {
				videos.replay(owner, 'vid' + i, i, '.fb-reader-video');
			} else if (i === index && !owner._videoEnded?.['vid' + i]) {
				ctx.play();
			} else {
				ctx.pause();
			}
		});
	},

	onSwiperChange(event) {
		const index = Number(event.detail.current) || 0;
		const changed = index !== Number(this.data.current);
		this.setData({ current: index }, () => { this.syncThumb(); this.syncPlayback(index, changed); this.rememberPosition();this.broadcastSlide(); });
	},

	onPickPage(event) {
		const index = Number(event.currentTarget.dataset.index) || 0;
		this.setData({ current: index }, () => { this.syncThumb(); this.syncPlayback(index, true); this.rememberPosition();this.broadcastSlide(); });
	},

 broadcastSlide(){if(this.triggerEvent&&this.data.query?._productPart)this.triggerEvent('productslide',{productid:this.data.product?.productid||0,index:this.data.current},{bubbles:true,composed:true});},
	onVideoEnded(event) { videos.ended(this, event); },
	onShow() { this.setData({ preference: preferences.read() }); videos.resume(this); },
	onHide() { this.rememberPosition(); videos.pauseAll(this); },
	onUnload() { this.rememberPosition(); videos.detach(this); },
	onToggleMute() {
		this.setData({ muted: !this.data.muted });
	},

	onToggleFab() {
		this.setData({ fabOpen: !this.data.fabOpen });
	},

 onBuy(){if(!this.data.tradeEnabled||!this.data.product)return;const slide=(this.data.slides||[])[this.data.current]||{};wx.navigateTo({url:'/pages/checkout/index?productid='+this.data.product.productid+'&skuid='+(Number(slide.skuid)||0)});},
	onPreview(event) {
		const slides = (this.data.slides || []).filter(function (item) {
			return item.type !== 'video' && item.image;
		});
		const index = Number(event.currentTarget.dataset.index) || 0;
		const current = (this.data.slides || [])[index];
		if (!current || !current.image) {
			return;
		}
		wx.previewImage({
			current: current.image,
			urls: slides.map(function (item) {
				return item.image;
			}),
		});
	},

	onPreviewHighlight(event) {
		const list = this.data.highlights || [];
		const current = list[Number(event.currentTarget.dataset.index) || 0];
		if (!current) {
			return;
		}
		wx.previewImage({
			current: current.image,
			urls: list.map(function (item) {
				return item.image;
			}),
		});
	},

	onPreviewDetail(event) {
		const list = this.data.details || [];
		const current = list[Number(event.currentTarget.dataset.index) || 0];
		if (!current) {
			return;
		}
		wx.previewImage({
			current: current.image,
			urls: list.map(function (item) {
				return item.image;
			}),
		});
	},

	onFavorite() {
		this.setData({ fabOpen: false });
		if (!this.logged) {
			toast.show('请先登录');
			return;
		}
		api.post('favorite', { targettype:'product', targetid:this.data.product.productid, do:'add' }).then((res) => toast.show(res.message)).catch(() => toast.show('收藏失败，请重试'));
	},

	onStyle() {
		this.setData({ fabOpen: false, scrollInto: '' });
		setTimeout(() => this.setData({ scrollInto: 'spec' }), 30);
	},

	/**
	 * 滚动到顶部自动刷新：2 秒内只触发一次，静默更新不闪屏。
	 */
	onInnerScroll(event) { this.innerScrollTop=event.detail.scrollTop || 0; },
	onRefreshTop() {},
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

	onShareAppMessage() {
		const product = this.data.product || {};
		const album = this.data.album || {};
		return {
			title: product.name || album.name || '工装画册',
			path: this.albumid
				? '/pages/micro/index?key=detail&albumid=' + this.albumid
				: '/pages/micro/index?key=detail&productid=' + (product.productid || ''),
		};
	},

	onBack() {
		page.back('/pages/discover/index');
	},

	onRetry() {
		this.load();
	},
};
