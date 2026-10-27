Component({
	data: {
		selected: 0,
		list: [
			{ pagePath: '/pages/discover/index', text: '发现', icon: 'discover' },
			{ pagePath: '/pages/catalog/index', text: '分类', icon: 'catalog' },
			{ pagePath: '/pages/favorites/index', text: '收藏', icon: 'favorites' },
			{ pagePath: '/pages/mine/index', text: '我的', icon: 'mine' },
		],
	},

	methods: {
		onSwitch(event) {
			const index = Number(event.currentTarget.dataset.index) || 0;
			if (index === this.data.selected) {
				return;
			}
			wx.switchTab({ url: this.data.list[index].pagePath });
		},
	},
});
