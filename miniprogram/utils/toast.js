/**
 * 全站消息：同一时间只显示一条，右下角浮层，自动销毁后显示下一条。
 * 文案统一截断到 10 个字，避免出现长句与多行。
 */
let active = null;
let queue = [];
let timer = null;

function shorten(text) {
	const value = String(text === undefined || text === null ? '' : text).replace(/\s+/g, '');
	return value.length > 10 ? value.slice(0, 10) : value;
}

function hide() {
	if (timer) {
		clearTimeout(timer);
		timer = null;
	}
	if (active) {
		active.setData({ visible: false });
	}
	setTimeout(flush, 240);
}

function flush() {
	if (timer || !active || !queue.length) {
		return;
	}
	const text = queue.shift();
	active.setData({ visible: true, text: text });
	timer = setTimeout(hide, 2400);
}

function show(text) {
	const value = shorten(text);
	if (!value) {
		return;
	}
	if (queue.indexOf(value) === -1) {
		queue.push(value);
	}
	flush();
}

function activate(component) {
	active = component;
	flush();
}

function deactivate(component) {
	if (active === component) {
		active = null;
	}
}

module.exports = {
	show: show,
	hide: hide,
	activate: activate,
	deactivate: deactivate,
};
