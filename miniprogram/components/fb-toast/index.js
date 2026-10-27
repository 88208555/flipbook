const toast = require('../../utils/toast');

Component({
	data: {
		visible: false,
		text: '',
	},

	pageLifetimes: {
		show() {
			toast.activate(this);
		},
		hide() {
			toast.deactivate(this);
		},
	},

	methods: {
		onClose() {
			toast.hide();
		},
	},
});
