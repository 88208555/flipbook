/**
 * 工装画册 · 前台交互
 *
 * 职责边界：
 * 1. 只处理本插件页面内的阅读切换、分段、面板与收藏/进度写入，不改造原生页面；
 * 2. 写操作一律 POST 到插件 action 入口，携带原生 formhash，不做隐性 GET 写库；
 * 3. 未登录或失败时给出受控提示，不静默吞掉状态；
 * 4. 不写内联样式，状态通过类名切换，尺寸与颜色仍由样式表控制。
 */
(function () {
	'use strict';

	var app = document.getElementById('fb-app');
	if (!app) {
		return;
	}

	var api = app.getAttribute('data-api');
	var formhash = app.getAttribute('data-formhash');
	var uid = parseInt(app.getAttribute('data-uid'), 10) || 0;

	function post(action, data) {
		var body = new URLSearchParams();
		body.set('formhash', formhash);
		Object.keys(data || {}).forEach(function (key) {
			body.set(key, data[key]);
		});
		return fetch(api + '&op=' + encodeURIComponent(action), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	function toast(message) {
		var el = document.getElementById('fb-toast');
		if (!el) {
			return;
		}
		el.textContent = message;
		el.classList.add('is-open');
		window.clearTimeout(toast.timer);
		toast.timer = window.setTimeout(function () {
			el.classList.remove('is-open');
		}, 2200);
	}

	function each(selector, scope, fn) {
		Array.prototype.forEach.call((scope || document).querySelectorAll(selector), fn);
	}

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	// ------------------------------------------------------------------
	// 面板与遮罩
	// ------------------------------------------------------------------
	function openSheet(name) {
		each('[data-fb-sheet]', document, function (sheet) {
			sheet.classList.toggle('is-open', sheet.getAttribute('data-fb-sheet') === name);
		});
		each('.fb-mask', document, function (mask) {
			var match = mask.getAttribute('data-fb-close') === name;
			mask.classList.toggle('is-open', match);
		});
	}

	function closeSheet(name) {
		each('[data-fb-sheet]', document, function (sheet) {
			if (sheet.getAttribute('data-fb-sheet') === name) {
				sheet.classList.remove('is-open');
			}
		});
		each('.fb-mask', document, function (mask) {
			if (mask.getAttribute('data-fb-close') === name) {
				mask.classList.remove('is-open');
			}
		});
	}

	document.addEventListener('click', function (event) {
		var open = event.target.closest('[data-fb-open]');
		if (open) {
			openSheet(open.getAttribute('data-fb-open'));
			return;
		}
		var close = event.target.closest('[data-fb-close]');
		if (close) {
			closeSheet(close.getAttribute('data-fb-close'));
			return;
		}
	});

	// ------------------------------------------------------------------
	// 发现页主视觉切换
	// ------------------------------------------------------------------
	var stage = document.querySelector('[data-fb-stage]');
	if (stage) {
		var heroIndex = 0;
		var goHero = function (index) {
			heroIndex = index;
			each('[data-fb-stage-item]', stage, function (item) {
				item.classList.toggle('is-active', parseInt(item.getAttribute('data-fb-stage-item'), 10) === index);
			});
			each('[data-fb-info-item]', document, function (item) {
				var match = parseInt(item.getAttribute('data-fb-info-item'), 10) === index;
				item.hidden = !match;
				item.classList.toggle('is-active', match);
			});
			each('[data-fb-goto]', document, function (dot) {
				dot.classList.toggle('is-active', parseInt(dot.getAttribute('data-fb-goto'), 10) === index);
			});
			syncFavorite();
			syncStyleLink();
		};
		document.addEventListener('click', function (event) {
			var dot = event.target.closest('[data-fb-goto]');
			if (dot && !document.querySelector('[data-fb-reader]')) {
				goHero(parseInt(dot.getAttribute('data-fb-goto'), 10) || 0);
			}
		});
		if(!stage.querySelector('[data-fb-web-hero]')) window.setInterval(function () {
			var items = stage.querySelectorAll('[data-fb-stage-item]');
			if (items.length > 1) {
				goHero((heroIndex + 1) % items.length);
			}
		}, 7000);
	}

	function syncFavorite() {
		var buttons = document.querySelectorAll('[data-fb-favorite="album"]');
		var item = document.querySelector('[data-fb-stage-item].is-active');
		var active = item || document.querySelector('[data-fb-page-item].is-active');
		if (!buttons.length || !active) {
			return;
		}
		var on = active.getAttribute('data-fb-fav-on') === '1';
		Array.prototype.forEach.call(buttons, function (button) {
			button.setAttribute('data-fb-fav-on', on ? '1' : '0');
			button.classList.toggle('is-on', on);
		});
	}

	// 当前页 / 当前主视觉对应的款式入口，没有关联款式时隐藏
	function syncStyleLink() {
		var link = document.querySelector('[data-fb-style-link]');
		var item = document.querySelector('[data-fb-stage-item].is-active') || document.querySelector('[data-fb-page-item].is-active');
		if (!link || !item) {
			return;
		}
		var url = item.getAttribute('data-fb-style') || '';
		link.hidden = url === '';
		if (url !== '') {
			link.setAttribute('href', url);
		}
	}

	// ------------------------------------------------------------------
	// 阅读页翻页
	// ------------------------------------------------------------------
	var reader = document.querySelector('[data-fb-reader]');
	if (reader) {
		var albumid = parseInt(reader.getAttribute('data-albumid'), 10) || 0;
		var total = parseInt(reader.getAttribute('data-total'), 10) || 1;
		var current = parseInt(reader.getAttribute('data-page'), 10) || 1;

		var gotoPage = function (page) {
			page = Math.max(1, Math.min(total, page));
			if (page === current) {
				return;
			}
			current = page;
			reader.setAttribute('data-page', String(page));
			each('[data-fb-page-item]', reader, function (item) {
				item.classList.toggle('is-active', parseInt(item.getAttribute('data-fb-page-item'), 10) === page);
			});
			each('[data-fb-info-item]', document, function (item) {
				var match = parseInt(item.getAttribute('data-fb-info-item'), 10) === page;
				item.hidden = !match;
				item.classList.toggle('is-active', match);
			});
			each('.fb-thumb', document, function (thumb) {
				thumb.classList.toggle('is-current', parseInt(thumb.getAttribute('data-fb-goto'), 10) === page);
			});
			each('[data-fb-counter]', document, function (counter) {
				counter.textContent = pad(page) + ' / ' + pad(total);
			});
			each('[data-fb-catalog-item]', document, function (card) {
				var match = parseInt(card.getAttribute('data-fb-catalog-item'), 10) === page;
				card.classList.toggle('is-current', match);
			});
			each('[data-fb-catalog-counter]', document, function (counter) {
				counter.textContent = pad(page) + ' / ' + pad(total);
			});
			each('[data-fb-resume]', document, function (button) {
				button.setAttribute('data-fb-resume', String(page));
			});
			syncStyleLink();
			if (uid > 0) {
				post('progress', { albumid: albumid, page: page }).catch(function () {});
			}
		};

		// 当前页的款式入口跟随页面切换
		syncStyleLink();

		document.addEventListener('click', function (event) {
			var thumb = event.target.closest('[data-fb-goto]');
			if (thumb) {
				gotoPage(parseInt(thumb.getAttribute('data-fb-goto'), 10) || 1);
			}
			var resume = event.target.closest('[data-fb-resume]');
			if (resume) {
				gotoPage(parseInt(resume.getAttribute('data-fb-resume'), 10) || 1);
			}
		});

		// 手势：左右滑动翻页，起点在图片区域内，纵向拖动不触发
		var startX = 0;
		var startY = 0;
		var tracking = false;
		reader.addEventListener('touchstart', function (event) {
			if (event.touches.length !== 1) {
				return;
			}
			startX = event.touches[0].clientX;
			startY = event.touches[0].clientY;
			tracking = true;
		}, { passive: true });
		reader.addEventListener('touchend', function (event) {
			if (!tracking) {
				return;
			}
			tracking = false;
			var dx = event.changedTouches[0].clientX - startX;
			var dy = event.changedTouches[0].clientY - startY;
			if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.4) {
				gotoPage(current + (dx < 0 ? 1 : -1));
			}
		}, { passive: true });

		document.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				gotoPage(current - 1);
			} else if (event.key === 'ArrowRight') {
				gotoPage(current + 1);
			}
		});

		if (uid > 0) {
			post('progress', { albumid: albumid, page: current }).catch(function () {});
		}
	}

	// 目录：全部 / 已收藏 过滤
	document.addEventListener('click', function (event) {
		var filter = event.target.closest('[data-fb-catalog-filter]');
		if (!filter) {
			return;
		}
		var mode = filter.getAttribute('data-fb-catalog-filter');
		each('[data-fb-catalog-filter]', document, function (item) {
			item.classList.toggle('is-active', item === filter);
		});
		each('[data-fb-catalog-item]', document, function (card) {
			var favorite = card.getAttribute('data-fb-favorite-page') === '1';
			card.hidden = mode === 'favorite' && !favorite;
		});
	});

	// ------------------------------------------------------------------
	// 收藏
	// ------------------------------------------------------------------
	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-fb-favorite]');
		if (!button) {
			return;
		}
		var targettype = button.getAttribute('data-fb-favorite');
		var targetid = button.getAttribute('data-fb-fav-id');
		if (!targetid) {
			var active = document.querySelector('[data-fb-stage-item].is-active');
			targetid = active ? active.getAttribute('data-fb-id') : '';
		}
		if (!targetid) {
			return;
		}
		var on = button.getAttribute('data-fb-fav-on') === '1';
		post('favorite', {
			targettype: targettype,
			targetid: targetid,
			'do': on ? 'remove' : 'add'
		}).then(function (result) {
			if (result.ok) {
				button.setAttribute('data-fb-fav-on', result.favorited ? '1' : '0');
				button.classList.toggle('is-on', !!result.favorited);
				if (targettype === 'album' && active) {
					active.setAttribute('data-fb-fav-on', result.favorited ? '1' : '0');
				}
				toast(result.message);
			} else {
				toast(result.message);
			}
		}).catch(function () {
			toast('收藏失败，请稍后重试');
		});
	});

	// ------------------------------------------------------------------
	// 收藏管理（批量移除）
	// ------------------------------------------------------------------
	document.addEventListener('click', function (event) {
		var manage = event.target.closest('[data-fb-manage]');
		if (manage) {
			var on = document.body.classList.toggle('is-manage');
			manage.classList.toggle('is-active', on);
			manage.textContent = on ? manage.getAttribute('data-fb-manage-done') : manage.getAttribute('data-fb-manage-label');
			each('[data-fb-pick]', document, function (pick) {
				pick.classList.remove('is-checked');
			});
			each('[data-fb-manage-bar]', document, function (bar) {
				bar.hidden = !on;
			});
			each('.fb-card', document, function (card) {
				card.classList.remove('is-picked');
			});
			updatePickCount();
			return;
		}
		var pick = event.target.closest('[data-fb-pick]');
		if (pick && document.body.classList.contains('is-manage')) {
			event.preventDefault();
			var card = pick.closest('.fb-card');
			var checked = pick.classList.toggle('is-checked');
			if (card) {
				card.classList.toggle('is-picked', checked);
			}
			updatePickCount();
			return;
		}
	});

	function updatePickCount() {
		var picked = document.querySelectorAll('[data-fb-pick].is-checked').length;
		each('[data-fb-manage-count]', document, function (el) {
			var label = el.getAttribute('data-fb-manage-label') || el.textContent.replace(/\s*\d+$/, '');
			el.setAttribute('data-fb-manage-label', label);
			el.textContent = label + ' ' + picked;
		});
		each('[data-fb-manage-remove]', document, function (button) {
			button.disabled = picked === 0;
			button.setAttribute('data-fb-picked', String(picked));
		});
	}

	document.addEventListener('click', function (event) {
		var remove = event.target.closest('[data-fb-manage-remove]');
		if (!remove) {
			return;
		}
		var cards = document.querySelectorAll('[data-fb-pick].is-checked');
		if (!cards.length) {
			return;
		}
		var historyRows = document.querySelectorAll('[data-fb-history-id].is-checked');
		if (historyRows.length) {
			var historyids = [];
			Array.prototype.forEach.call(historyRows, function (row) {
				var id = row.getAttribute('data-fb-history-id');
				if (id) {
					historyids.push(id);
				}
			});
			if (!historyids.length) {
				return;
			}
			post('history', { do: 'remove', historyids: historyids.join(',') }).then(function (result) {
				toast(result.message);
				if (result.ok) {
					window.setTimeout(function () {
						window.location.reload();
					}, 600);
				}
			}).catch(function () {
				toast('删除失败，请稍后重试');
			});
			return;
		}
		var ids = [];
		var targettype = '';
		Array.prototype.forEach.call(cards, function (pick) {
			var card = pick.closest('.fb-card');
			if (!card) {
				return;
			}
			targettype = card.getAttribute('data-fb-fav-type') || targettype;
			var id = card.getAttribute('data-fb-fav-item');
			if (id) {
				ids.push(id);
			}
		});
		if (!targettype || !ids.length) {
			return;
		}
		post('favorite', { targettype: targettype, 'do': 'remove', targetids: ids.join(',') }).then(function (result) {
			toast(result.message);
			if (result.ok) {
				window.setTimeout(function () {
					window.location.reload();
				}, 600);
			}
		}).catch(function () {
			toast('移除失败，请稍后重试');
		});
	});

	// ------------------------------------------------------------------
	// 款式详情分段与查看器
	// ------------------------------------------------------------------
	document.addEventListener('click', function (event) {
		var tab = event.target.closest('[data-fb-pane]');
		if (tab) {
			var name = tab.getAttribute('data-fb-pane');
			each('[data-fb-pane]', document, function (item) {
				var match = item === tab;
				item.classList.toggle('is-active', match);
				item.setAttribute('aria-selected', match ? 'true' : 'false');
			});
			each('[data-fb-pane-body]', document, function (pane) {
				pane.classList.toggle('is-active', pane.getAttribute('data-fb-pane-body') === name);
			});
			return;
		}
		var zoom = event.target.closest('[data-fb-viewer]');
		if (zoom) {
			openViewer(zoom);
		}
	});

	// 查看器：同组图片的圆点与缩略图导航
	function viewerGroup(trigger) {
		var group = trigger.getAttribute('data-fb-viewer-group');
		if (!group) {
			return [trigger];
		}
		var items = [];
		each('[data-fb-viewer]', document, function (item) {
			if (item.getAttribute('data-fb-viewer-group') === group) {
				items.push(item);
			}
		});
		return items.length ? items : [trigger];
	}

	function openViewer(trigger) {
		var box = document.querySelector('[data-fb-viewer-box]');
		var img = document.querySelector('[data-fb-viewer-img]');
		if (!box || !img) {
			return;
		}
		var items = viewerGroup(trigger);
		var index = items.indexOf(trigger);
		index = index < 0 ? 0 : index;
		var dots = document.querySelector('[data-fb-viewer-dots]');
		var thumbs = document.querySelector('[data-fb-viewer-thumbs]');
		if (dots) {
			dots.innerHTML = '';
		}
		if (thumbs) {
			thumbs.innerHTML = '';
		}
		items.forEach(function (item, i) {
			if (dots) {
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'fb-viewer-dot';
				dot.setAttribute('data-fb-viewer-goto', String(i));
				dot.setAttribute('aria-label', item.getAttribute('data-fb-viewer-title') || '');
				dots.appendChild(dot);
			}
			if (thumbs) {
				var thumb = document.createElement('button');
				thumb.type = 'button';
				thumb.className = 'fb-viewer-thumb';
				thumb.setAttribute('data-fb-viewer-goto', String(i));
				var thumbImg = document.createElement('img');
				thumbImg.setAttribute('src', item.getAttribute('data-fb-viewer') || '');
				thumbImg.setAttribute('alt', item.getAttribute('data-fb-viewer-title') || '');
				thumb.appendChild(thumbImg);
				thumbs.appendChild(thumb);
			}
		});
		box.setAttribute('data-fb-viewer-items', '1');
		viewerItems = items;
		showViewerItem(index);
		box.classList.add('is-open');
	}

	var viewerItems = [];

	function showViewerItem(index) {
		if (!viewerItems.length) {
			return;
		}
		index = Math.max(0, Math.min(viewerItems.length - 1, index));
		var item = viewerItems[index];
		var box = document.querySelector('[data-fb-viewer-box]');
		var img = document.querySelector('[data-fb-viewer-img]');
		var title = document.querySelector('[data-fb-viewer-title]');
		var count = document.querySelector('[data-fb-viewer-count]');
		var save = document.querySelector('[data-fb-viewer-save]');
		var src = item.getAttribute('data-fb-viewer') || '';
		if (img) {
			img.setAttribute('src', src);
			img.setAttribute('alt', item.getAttribute('data-fb-viewer-title') || '');
		}
		if (title) {
			title.textContent = item.getAttribute('data-fb-viewer-title') || '';
		}
		if (count) {
			count.textContent = pad(index + 1) + ' / ' + pad(viewerItems.length);
		}
		if (save) {
			save.setAttribute('href', src);
		}
		each('[data-fb-viewer-goto]', document, function (el) {
			var match = parseInt(el.getAttribute('data-fb-viewer-goto'), 10) === index;
			el.classList.toggle('is-active', match);
			el.classList.toggle('is-current', match);
		});
		if (box) {
			box.setAttribute('data-fb-viewer-index', String(index));
		}
	}

	document.addEventListener('click', function (event) {
		var goto = event.target.closest('[data-fb-viewer-goto]');
		if (!goto) {
			return;
		}
		showViewerItem(parseInt(goto.getAttribute('data-fb-viewer-goto'), 10) || 0);
	});

	// 查看器关闭：遮罩与关闭按钮都在面板处理里，这里补 ESC 与左右切换
	document.addEventListener('keydown', function (event) {
		var box = document.querySelector('[data-fb-viewer-box].is-open');
		if (!box) {
			return;
		}
		var index = parseInt(box.getAttribute('data-fb-viewer-index'), 10) || 0;
		if (event.key === 'Escape') {
			box.classList.remove('is-open');
		} else if (event.key === 'ArrowLeft') {
			showViewerItem(index - 1);
		} else if (event.key === 'ArrowRight') {
			showViewerItem(index + 1);
		}
	});

	// ------------------------------------------------------------------
	// 分享
	// ------------------------------------------------------------------
	function activeShareScope() {
		return document.querySelector('[data-fb-share-scope].is-active') || document.querySelector('[data-fb-share-scope]');
	}

	function applyShareScope(button) {
		if (!button) {
			return;
		}
		var key = button.getAttribute('data-fb-share-scope');
		each('[data-fb-share-scope]', document, function (item) {
			item.classList.toggle('is-active', item === button);
		});
		each('[data-fb-share-cover-item]', document, function (img) {
			img.hidden = img.getAttribute('data-fb-share-cover-item') !== key;
		});
		each('[data-fb-share-text]', document, function (text) {
			var match = text.getAttribute('data-fb-share-text') === key;
			text.hidden = !match;
			if (match) {
				var title = text.querySelector('.fb-share-target-title');
				var meta = text.querySelector('.fb-share-target-meta');
				if (title) {
					title.textContent = button.getAttribute('data-fb-share-title') || '';
				}
				if (meta) {
					meta.textContent = button.getAttribute('data-fb-share-meta') || '';
				}
			}
		});
		var cover = document.querySelector('[data-fb-share-cover-item="' + key + '"]');
		if (cover) {
			cover.setAttribute('src', button.getAttribute('data-fb-share-cover') || '');
		}
	}

	// 从当前主视觉 / 当前页同步分享对象，保证滑到下一册后分享的是当前画册
	function syncShareFromActive(button) {
		var item = document.querySelector('[data-fb-stage-item].is-active') || document.querySelector('[data-fb-page-item].is-active');
		if (!button || !item || !item.getAttribute('data-fb-share-title')) {
			return;
		}
		['title', 'meta', 'cover', 'hascover', 'url'].forEach(function (key) {
			button.setAttribute('data-fb-share-' + key, item.getAttribute('data-fb-share-' + key) || '');
		});
		['image', 'title', 'meta', 'points', 'brand'].forEach(function (key) {
			button.setAttribute('data-fb-poster-' + key, item.getAttribute('data-fb-poster-' + key) || '');
		});
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-fb-share]');
		if (!trigger) {
			return;
		}
		if (trigger.tagName === 'A' && trigger.getAttribute('href')) {
			return;
		}
		var button = document.querySelector('[data-fb-share-scope="' + trigger.getAttribute('data-fb-share') + '"]');
		syncShareFromActive(button);
		applyShareScope(button);
		openSheet('share');
	});

	document.addEventListener('click', function (event) {
		var scope = event.target.closest('[data-fb-share-scope]');
		if (scope) {
			applyShareScope(scope);
		}
	});

	document.addEventListener('click', function (event) {
		var copy = event.target.closest('[data-fb-share-copy]');
		if (!copy) {
			return;
		}
		var button = activeShareScope();
		if (!button) {
			return;
		}
		var url = button.getAttribute('data-fb-share-url') || '';
		var kind = copy.getAttribute('data-fb-share-copy');
		var value = kind === 'moments' ? (button.getAttribute('data-fb-share-title') || '') + '\n' + url : url;
		var done = function () {
			toast('链接已复制');
		};
		if (navigator.clipboard && navigator.clipboard.writeText && value) {
			navigator.clipboard.writeText(value).then(done).catch(function () {
				toast('请长按链接复制');
			});
		} else {
			toast('请长按链接复制');
		}
		var targetid = button.getAttribute('data-fb-share-targetid') || '';
		if (targetid) {
			post('share', { targettype: button.getAttribute('data-fb-share-scope'), targetid: targetid }).catch(function () {});
		}
	});

	// ------------------------------------------------------------------
	// 海报：按分享对象绘制 1080×1920 竖版成品
	// ------------------------------------------------------------------
	var posterFont = '"PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif';

	function posterText(ctx, text, x, y, size, weight, color, maxWidth) {
		text = String(text || '');
		ctx.fillStyle = color;
		var current = size;
		ctx.font = weight + ' ' + current + 'px ' + posterFont;
		while (current > 18 && ctx.measureText(text).width > maxWidth) {
			current -= 2;
			ctx.font = weight + ' ' + current + 'px ' + posterFont;
		}
		if (ctx.measureText(text).width > maxWidth) {
			while (text.length > 1 && ctx.measureText(text + '…').width > maxWidth) {
				text = text.slice(0, -1);
			}
			text += '…';
		}
		ctx.fillText(text, x, y);
	}

	var posterTrigger = null;

	function posterActions(panel, failed) {
		each('[data-fb-poster-retry]', panel, function (button) {
			button.hidden = !failed;
		});
		each('[data-fb-poster-cancel]', panel, function (button) {
			button.hidden = !failed;
		});
		if (failed) {
			each('[data-fb-poster-save]', panel, function (save) {
				save.hidden = true;
			});
		}
	}

	function posterFail(panel, state) {
		posterActions(panel, true);
		if (state) {
			state.textContent = panel.getAttribute('data-fb-poster-failed') || '';
		}
		toast(panel.getAttribute('data-fb-poster-failed') || '海报生成失败');
	}

	function buildPoster(button) {
		var panel = document.querySelector('.fb-poster');
		if (!panel || !button) {
			return;
		}
		var state = panel.querySelector('[data-fb-poster-state]');
		var preview = panel.querySelector('[data-fb-poster-img]');
		var save = panel.querySelector('[data-fb-poster-save]');
		posterTrigger = button;
		openSheet('poster');
		posterActions(panel, false);
		if (state) {
			state.textContent = panel.getAttribute('data-fb-poster-loading') || '';
		}
		if (preview) {
			preview.removeAttribute('src');
		}
		if (save) {
			save.hidden = true;
			save.removeAttribute('href');
		}
		var src = button.getAttribute('data-fb-poster-image') || '';
		if (!src) {
			posterFail(panel, state);
			return;
		}
		var image = new Image();
		image.onload = function () {
			try {
				var canvas = document.createElement('canvas');
				canvas.width = 1080;
				canvas.height = 1920;
				var ctx = canvas.getContext('2d');
				var coverHeight = 1500;
				ctx.fillStyle = '#000000';
				ctx.fillRect(0, 0, 1080, 1920);
				var scale = Math.max(1080 / image.naturalWidth, coverHeight / image.naturalHeight);
				var sw = 1080 / scale;
				var sh = coverHeight / scale;
				ctx.drawImage(image, (image.naturalWidth - sw) / 2, (image.naturalHeight - sh) / 2, sw, sh, 0, 0, 1080, coverHeight);
				posterText(ctx, button.getAttribute('data-fb-poster-title'), 64, 1630, 58, '600', '#ffffff', 952);
				posterText(ctx, button.getAttribute('data-fb-poster-meta'), 64, 1688, 30, '400', '#a1a1a6', 952);
				posterText(ctx, button.getAttribute('data-fb-poster-points'), 64, 1768, 38, '400', '#f4f4f5', 952);
				ctx.fillStyle = 'rgba(255, 255, 255, 0.14)';
				ctx.fillRect(64, 1816, 952, 1);
				posterText(ctx, button.getAttribute('data-fb-poster-brand'), 64, 1858, 26, '400', '#79797f', 952);
				var data = canvas.toDataURL('image/png');
				if (preview) {
					preview.setAttribute('src', data);
				}
				if (save) {
					save.setAttribute('href', data);
					save.setAttribute('download', (panel.getAttribute('data-fb-poster-filename') || 'poster') + '.png');
					save.hidden = false;
				}
				if (state) {
					state.textContent = '';
				}
				var targetid = button.getAttribute('data-fb-share-targetid') || '';
				if (targetid) {
					post('share', { targettype: button.getAttribute('data-fb-share-scope'), targetid: targetid }).catch(function () {});
				}
			} catch (error) {
				posterFail(panel, state);
			}
		};
		image.onerror = function () {
			posterFail(panel, state);
		};
		image.src = src;
	}

	document.addEventListener('click', function (event) {
		var retry = event.target.closest('[data-fb-poster-retry]');
		if (retry && posterTrigger) {
			buildPoster(posterTrigger);
			return;
		}
		var trigger = event.target.closest('[data-fb-poster]');
		if (!trigger) {
			return;
		}
		buildPoster(activeShareScope());
	});

	// 偏好单选：点击后同步高亮（页签 / 色块按钮通用）
	each('input[type="radio"][name="flip"], input[type="radio"][name="quality"]', document, function (input) {
		input.addEventListener('change', function () {
			var name = input.getAttribute('name');
			each('input[type="radio"][name="' + name + '"]', document, function (other) {
				var label = other.closest('.fb-segment-item') || other.closest('.fb-chip-btn');
				if (label) {
					label.classList.toggle('is-active', other.checked);
				}
			});
		});
	});

	// 筛选下拉：选中即提交，避免额外按钮
	each('[data-fb-autosubmit]', document, function (select) {
		select.addEventListener('change', function () {
			var form = select.form;
			if (form) {
				form.submit();
			}
		});
	});

	// 保存成功提示：短暂高亮后收起
	each('[data-fb-flash]', document, function (notice) {
		window.setTimeout(function () {
			notice.classList.remove('is-open');
		}, 2400);
	});
})();

/**
 * 微页面交互：轮播（自动播放 / 圆点切换）与标签页面板切换。
 * 结构与样式由配置决定，这里只处理状态，不写任何业务文案。
 */
(function () {
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function initCarousel(root) {
		var track = root.querySelector('.fb-mc-track');
		if (!track) {
			return;
		}
		var slides = track.querySelectorAll('.fb-mc-slide');
		if (slides.length < 2) {
			return;
		}
		var dots = root.querySelectorAll('.fb-mc-dot');
		var current = 0;
		var timer = null;
		var interval = Math.max(1000, parseInt(root.getAttribute('data-interval'), 10) || 4000);

		function paint(index) {
			current = index;
			for(var v = 0; v < slides.length; v++) { var video = slides[v].querySelector('video'); if(video && v !== index) video.pause(); }
			for (var i = 0; i < dots.length; i++) {
				dots[i].classList.toggle('is-active', i === index);
			}
		}

		function go(index, smooth) {
			var target = Math.max(0, Math.min(slides.length - 1, index));
			paint(target);
			var left = slides[target].offsetLeft - slides[0].offsetLeft;
			if (track.scrollTo) {
				track.scrollTo({ left: left, behavior: smooth === false ? 'auto' : 'smooth' });
			} else {
				track.scrollLeft = left;
			}
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			stop();
			if (root.getAttribute('data-autoplay') !== '1' || reduceMotion) {
				return;
			}
			timer = window.setInterval(function () {
				go(current + 1 >= slides.length ? 0 : current + 1);
			}, interval);
		}

		for (var i = 0; i < dots.length; i++) {
			dots[i].addEventListener('click', function () {
				var index = parseInt(this.getAttribute('data-slide'), 10) || 0;
				go(index);
				start();
			});
		}

		var raf = 0;
		track.addEventListener('scroll', function () {
			if (raf) {
				return;
			}
			raf = window.requestAnimationFrame(function () {
				raf = 0;
				var width = slides[0].offsetWidth || 1;
				var index = Math.round(track.scrollLeft / width);
				if (index !== current) {
					paint(index);
				}
			});
		}, { passive: true });

		root.addEventListener('pointerenter', stop);
		root.addEventListener('pointerleave', start);
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				for(var v = 0; v < slides.length; v++) { var video = slides[v].querySelector('video'); if(video) video.pause(); }
				stop();
			} else {
				start();
			}
		});
		start();
	}

	function initTabs(root) {
		var buttons = Array.prototype.filter.call(root.querySelectorAll('[data-tab]'), function(button) { return button.closest('[data-fb-micro-tab]') === root; });
		var panels = Array.prototype.filter.call(root.querySelectorAll('[data-tabpanel]'), function(panel) { return panel.closest('[data-fb-micro-tab]') === root; });
		var activeColor = root.getAttribute('data-active-color') || 'inherit';
		var color = root.getAttribute('data-color') || 'inherit';

		function select(index) {
			for (var i = 0; i < buttons.length; i++) {
				var on = parseInt(buttons[i].getAttribute('data-tab'), 10) === index;
				buttons[i].classList.toggle('is-active', on);
				buttons[i].style.color = on ? activeColor : color;
			}
			for (var j = 0; j < panels.length; j++) {
				var show = parseInt(panels[j].getAttribute('data-tabpanel'), 10) === index;
				panels[j].classList.toggle('is-active', show);
				panels[j].style.display = show ? 'flex' : 'none';
			}
		}

		for (var i = 0; i < buttons.length; i++) {
			buttons[i].addEventListener('click', function () {
				select(parseInt(this.getAttribute('data-tab'), 10) || 0);
			});
		}
	}

	document.addEventListener('click', function(event) {
		var block = event.target.closest('[data-fb-block-link]');
		if(block && !event.target.closest('a,button,input,select,textarea,video,[role="tab"]')) window.location.href = block.getAttribute('data-fb-block-link');
	});
	document.addEventListener('keydown', function(event) {
		if(event.key === 'Enter' && event.target.hasAttribute('data-fb-block-link')) window.location.href = event.target.getAttribute('data-fb-block-link');
	});

	function boot() {
		var carousels = document.querySelectorAll('[data-fb-micro-carousel]');
		for (var i = 0; i < carousels.length; i++) {
			initCarousel(carousels[i]);
		}
		var tabs = document.querySelectorAll('[data-fb-micro-tab]');
		for (var j = 0; j < tabs.length; j++) {
			initTabs(tabs[j]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

/* Shared video behavior: one playback, first frame on end, visible media only. */
(function () {
 'use strict';
 function icon(muted) { return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H3v6h3l5 4z"/>' + (muted ? '<path d="m16 9 5 6m0-6-5 6"/>' : '<path d="M15 8a6 6 0 0 1 0 8m3-11a10 10 0 0 1 0 14"/>') + '</svg>'; }
 function boot() {
  var videos = Array.prototype.slice.call(document.querySelectorAll('video'));
  function visible(video) {
   var rect=video.getBoundingClientRect();
   var nav=document.querySelector('.fb-mc-nav'); var top=nav?nav.getBoundingClientRect().bottom:0;
   var bottom=innerHeight-64;
   var height=Math.max(0,Math.min(rect.bottom,bottom)-Math.max(rect.top,top));
   var stage=video.closest('[data-fb-stage-item]');
   var horizontal=Math.max(0, Math.min(rect.right,innerWidth)-Math.max(rect.left,0));
   return horizontal/Math.max(1,rect.width) >= 0.6 && rect.width>0 && rect.height>0 && height/Math.min(rect.height,bottom-top)>=0.6 && (!stage || stage.classList.contains('is-active'));
  }
  function sync() { videos.forEach(function(video) {
   var carousel=video.closest('[data-fb-micro-carousel]');
   var autoplay=carousel ? carousel.getAttribute('data-autoplay')==='1' : video.hasAttribute('autoplay');
   if(!document.hidden && visible(video) && !video.dataset.finished && autoplay) { var play=video.play(); if(play&&play.catch) play.catch(function(){}); } else video.pause();
  }); document.querySelectorAll('.fb-media-actions').forEach(function(menu){var parent=menu.parentElement;if(parent._paintMute)parent._paintMute();}); }
  videos.forEach(function(video) {
   video.controls=false; video.loop=false; video.removeAttribute('controls'); video.removeAttribute('loop');
   video.setAttribute('disablepictureinpicture',''); video.setAttribute('controlslist','nodownload noplaybackrate noremoteplayback');
   video.addEventListener('ended', function() { video.dataset.finished='1'; video.pause(); video.currentTime=0; });
   video.addEventListener('seeked', function() { if(video.dataset.finished)video.pause(); });
   video.addEventListener('loadeddata',sync);
  });
  var roots=Array.prototype.slice.call(document.querySelectorAll('.fb-stage-frame,[data-fb-micro-carousel],.fb-mc-video-wrap,.fb-screen:has([data-fb-web-hero])'));
  roots.forEach(function(root) {
   if(document.querySelector('#fb-micro-actions') && !root.closest('.fb-mc-social .fb-mc-business[data-source="home"]')) { root.querySelectorAll('.fb-stage-rail,.fb-media-actions').forEach(function(menu){menu.remove();});return; }
   var rail=root.querySelector('.fb-stage-rail');
   if(!rail) { rail=document.createElement('div');root.appendChild(rail); }
   rail.classList.add('fb-media-actions');
   var list=document.createElement('div');list.className='fb-media-actions-list';
   while(rail.firstChild)list.appendChild(rail.firstChild);
   rail.appendChild(list);
   if(root.querySelector('video')) {
    if(!list.querySelector('[data-fb-share]')) {
     var share=document.createElement('button');share.type='button';share.className='fb-rail-btn';share.setAttribute('aria-label','分享');share.innerHTML='<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 16V3m-5 5 5-5 5 5M5 13v7h14v-7"/></svg>';
     share.onclick=function(){ if(navigator.share)navigator.share({title:document.title,url:location.href}).catch(function(){});else if(navigator.clipboard)navigator.clipboard.writeText(location.href); };list.appendChild(share);
    }
    var mute=document.createElement('button');mute.type='button';mute.className='fb-rail-btn fb-media-mute';
    function current() { var media=Array.prototype.slice.call(root.querySelectorAll('video'));return media.find(visible)||null; }
    function paint(){var video=current();mute.hidden=!video;mute.style.display=video?'flex':'none';mute.innerHTML=icon(!video || video.muted);mute.setAttribute('aria-label',video&&video.muted?'开启声音':'静音');mute.setAttribute('aria-pressed',video&&video.muted?'true':'false');}
    mute.onclick=function(event){event.stopPropagation();var video=current();if(video) {video.muted=!video.muted;paint();}};
    list.appendChild(mute);paint();root.addEventListener('scroll',paint,true);root._paintMute=paint;
   }
   var toggle=document.createElement('button');toggle.type='button';toggle.className='fb-rail-btn fb-media-toggle';toggle.textContent='+';toggle.setAttribute('aria-label','展开操作');toggle.setAttribute('aria-expanded','false');
   toggle.onclick=function(){var open=rail.classList.toggle('is-open');toggle.textContent=open?'×':'+';toggle.setAttribute('aria-expanded',open?'true':'false');};rail.appendChild(toggle);
  });
  var observer=window.IntersectionObserver?new IntersectionObserver(sync,{threshold:[0,0.6,1]}):null;
  if(observer)videos.forEach(function(video){observer.observe(video);});
  document.addEventListener('visibilitychange',sync);window.addEventListener('pagehide',function(){videos.forEach(function(video){video.pause();});});window.addEventListener('pageshow',sync);
  document.addEventListener('scroll',sync,{capture:true,passive:true});sync();
  // Pull feedback is a single icon at the top of the page, above the navbar.
  if(!document.querySelector('.fb-mc-main'))return;
  var spinner=document.createElement('div');spinner.className='fb-pull-spinner';spinner.hidden=true;spinner.setAttribute('aria-label','正在刷新');document.body.appendChild(spinner);
  var start=0,distance=0,canPull=false;
  document.addEventListener('touchstart',function(event){canPull=((document.querySelector('.fb-mc-main')||document.scrollingElement).scrollTop<=0 && !document.querySelector('[data-fb-web-hero] [data-fb-stage-item].is-active:not(:first-child)'));start=event.touches[0].clientY;distance=0;},{passive:true});
  document.addEventListener('touchmove',function(event){if(!canPull)return;distance=Math.max(0,event.touches[0].clientY-start);spinner.hidden=distance<24;},{passive:true});
  document.addEventListener('touchend',function(){if(canPull&&distance>75)location.reload();else spinner.hidden=true;canPull=false;},{passive:true});
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();

/* Web adapters preserve the same swipe, thumbnail and compact selector controls. */
(function(){
 document.querySelectorAll('[data-fb-web-hero]').forEach(function(hero){
  var items=hero.querySelectorAll('[data-fb-stage-item]'),index=0,start=0;
  function change(next){ index=Math.max(0,Math.min(items.length-1,next));items.forEach(function(item,i){item.classList.toggle('is-active',i===index);});hero.dispatchEvent(new Event('scroll',{bubbles:true}));document.querySelectorAll('[data-fb-view-title]').forEach(function(line){if(line.dataset.custom==='0')line.textContent=items[index]?.dataset[line.dataset.binding]||'';}); }
  hero.addEventListener('touchstart',function(e){start=e.touches[0].clientY;},{passive:true});
  hero.addEventListener('touchend',function(e){var distance=start-e.changedTouches[0].clientY;if(Math.abs(distance)>55)change(index+(distance>0?1:-1));},{passive:true});
  change(0);var wheelTime=0;hero.addEventListener('wheel',function(e){e.preventDefault();if(Date.now()-wheelTime>650&&Math.abs(e.deltaY)>20){change(index+(e.deltaY>0?1:-1));wheelTime=Date.now();}},{passive:false});
 });
 document.querySelectorAll('[data-fb-web-reader-track]').forEach(function(track){var reader=track.closest('.fb-web-reader'),product=reader.getAttribute('data-fb-product'),thumb=product?document.querySelectorAll('.fb-web-reader[data-fb-product="'+product+'"] [data-fb-web-pick]'):reader.querySelectorAll('[data-fb-web-pick]'),readerIndex=Math.round(track.scrollLeft/track.clientWidth)||0;
  function replayReaderVideo(index){var video=track.children[index]?.querySelector('video');if(!video)return;delete video.dataset.finished;try{video.currentTime=0;}catch(error){}var playing=video.play();if(playing&&playing.catch)playing.catch(function(){});}
  track.addEventListener('scroll',function(){var index=Math.round(track.scrollLeft/track.clientWidth);if(index!==readerIndex){readerIndex=index;replayReaderVideo(index);}thumb.forEach(function(item,i){item.classList.toggle('is-current',Number(item.getAttribute('data-fb-web-pick'))===index);});var count=reader.querySelector('[data-fb-web-count]');if(count)count.textContent=String(index+1).padStart(2,'0');var title=track.children[index]?.getAttribute('data-title');var caption=reader.querySelector('[data-fb-web-caption]');if(caption)caption.textContent=title||'';},{passive:true});
  thumb.forEach(function(button,i){button.addEventListener('click',function(){var index=Number(button.getAttribute('data-fb-web-pick'));if(index===readerIndex)replayReaderVideo(index);track.scrollTo({left:index*track.clientWidth,behavior:'smooth'});});});
 });
 document.addEventListener('click',function(event){
  var scope=event.target.closest('.fb-mc-business')||document,mask=scope.querySelector('[data-fb-series-mask]');
  if(event.target.closest('[data-fb-series-open]')&&mask)mask.hidden=false;
  if((event.target.closest('[data-fb-series-close]')||event.target===mask)&&mask)mask.hidden=true;
  var remove=event.target.closest('[data-fb-remove-favorite]');
  if(remove){var app=document.querySelector('#fb-app');var data=new URLSearchParams({formhash:app.dataset.formhash,targettype:remove.dataset.type,targetid:remove.dataset.fbRemoveFavorite,do:'remove'});fetch(app.dataset.api+'&op=favorite',{method:'POST',body:data,credentials:'same-origin'}).then(r=>r.json()).then(r=>{if(r.ok)location.reload();});}
  if(event.target.closest('[data-fb-about]')){var toast=document.querySelector('#fb-toast');toast.textContent=document.title.split(' · ').pop();toast.classList.add('is-open');setTimeout(()=>toast.classList.remove('is-open'),2200);}
 });
})();

(function(){
 var main=document.querySelector('.fb-mc-main');
 if(!main)return;
 function layout(){var bar=document.querySelector('.fb-mc-tabbar');document.querySelector('#fb-app').style.setProperty('--fb-current-tab-height',(bar?bar.getBoundingClientRect().height:0)+'px');var style=getComputedStyle(main);main.style.setProperty('--fb-business-height',Math.max(0,main.clientHeight-parseFloat(style.paddingTop)-parseFloat(style.paddingBottom))+'px');}
 if(window.ResizeObserver)new ResizeObserver(layout).observe(main);window.addEventListener('resize',layout);layout();
})();

/* Persisted page action menu: fixed favorite/share, optional sound, five custom entries. */
(function(){
 var data=document.querySelector('#fb-micro-actions'); if(!data)return;
 var actions;try{actions=JSON.parse(data.textContent);}catch(e){return;}
 document.querySelectorAll('.fb-media-actions').forEach(function(root){
  if(!actions.enabled || !root.closest('.fb-mc-social .fb-mc-business[data-source="home"]')){root.hidden=true;root.style.display='none';return;}
  root.style.setProperty('left',actions.left+'px');root.style.setProperty('bottom',actions.bottom+'px');
  root.style.setProperty('gap',actions.gap+'px');
  var list=root.querySelector('.fb-media-actions-list');if(!list)return;list.style.gap=actions.gap+'px';
  list.querySelectorAll('[data-fb-style-link],a[href="#spec"]').forEach(n=>n.remove());
  root.querySelectorAll('.fb-rail-btn').forEach(function(button){button.style.width=actions.size+'px';button.style.height=actions.size+'px';button.style.background=actions.bg;button.style.color=actions.color;button.style.borderRadius=actions.radius+'px';});
  actions.items.forEach(function(item){if(!item.href)return;var button=document.createElement('a');button.className='fb-rail-btn';button.href=item.href;button.title=item.label;button.setAttribute('aria-label',item.label);button.style.cssText='width:'+actions.size+'px;height:'+actions.size+'px;background:'+(item.bg||actions.bg)+';color:'+(item.color||actions.color)+';border-radius:'+item.radius+'px;';button.innerHTML='<svg class="fb-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#fb-i-'+item.icon+'"/></svg>';list.appendChild(button);});
 });
})();

(function(){
 document.querySelectorAll('.fb-media-actions-list').forEach(function(list){
  if(list.querySelector('[data-fb-favorite]'))return;
  var favorite=document.createElement('button');favorite.type='button';favorite.className='fb-rail-btn';favorite.setAttribute('aria-label','收藏');favorite.innerHTML='<svg class="fb-icon" viewBox="0 0 24 24"><use href="#fb-i-bookmark"/></svg>';
  favorite.onclick=function(){var root=list.closest('.fb-media-actions').parentElement;var links=Array.from(root.querySelectorAll('.fb-mc-slide-link,.fb-mc-video-link'));var link=links.find(n=>{var r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;});var target=link&&new URL(link.href);var type=target&&target.searchParams.has('productid')?'product':'album';var id=target&&target.searchParams.get(type+'id');var app=document.querySelector('#fb-app');if(!id){var t=document.querySelector('#fb-toast');t.textContent='请先打开对应画册或款式';t.classList.add('is-open');setTimeout(()=>t.classList.remove('is-open'),2000);return;}var body=new URLSearchParams({formhash:app.dataset.formhash,targettype:type,targetid:id,do:'add'});fetch(app.dataset.api+'&op=favorite',{method:'POST',body:body,credentials:'same-origin'}).then(r=>r.json()).then(r=>{var t=document.querySelector('#fb-toast');t.textContent=r.message;t.classList.add('is-open');setTimeout(()=>t.classList.remove('is-open'),2000);});};
  list.prepend(favorite);
  if(!list.querySelector('[aria-label="分享"]')){var share=document.createElement('button');share.type='button';share.className='fb-rail-btn';share.setAttribute('aria-label','分享');share.innerHTML='<svg class="fb-icon" viewBox="0 0 24 24"><use href="#fb-i-share"/></svg>';share.onclick=function(){if(navigator.share)navigator.share({title:document.title,url:location.href}).catch(()=>{});else navigator.clipboard?.writeText(location.href);};favorite.after(share);}
  var data=document.querySelector('#fb-micro-actions');var actions=data?JSON.parse(data.textContent):{};[...list.children].filter(n=>n===favorite||n.getAttribute('aria-label')==='分享').forEach(n=>{n.style.width=(actions.size||40)+'px';n.style.height=(actions.size||40)+'px';n.style.background=actions.bg||'';n.style.color=actions.color||'';n.style.borderRadius=(actions.radius||0)+'px';});
 });
})();

/* H5 sign-in has its own document and shares the native forum session. */
(function(){
 var app=document.querySelector('#fb-app');if(!app)return;
 function auth(op,data){data.set('formhash',app.dataset.formhash);return fetch('plugin.php?id=flipbook:auth&op='+op,{method:'POST',credentials:'same-origin',body:data}).then(function(r){return r.json();});}
 document.querySelectorAll('[data-fb-member-header]').forEach(function(header){
 if(header.dataset.guest==='1'){
  header.setAttribute('role','link');header.tabIndex=0;
  function open(){if(!new URLSearchParams(location.search).has('business_preview'))location.assign(header.dataset.loginUrl);}
  header.addEventListener('click',open);header.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();open();}});
 }
 });
 document.querySelectorAll('[data-fb-login-form]').forEach(function(form){form.addEventListener('submit',function(e){
  e.preventDefault();var button=form.querySelector('[type=submit]'),message=form.querySelector('[data-fb-login-message]');
  if(button.disabled)return;button.disabled=true;message.textContent='';message.hidden=true;
  auth('forum',new URLSearchParams(new FormData(form))).then(function(res){
   if(res.ok){location.replace(form.dataset.return);return;}
   message.hidden=false;message.textContent=res.message||'登录失败，请重试';
   var native=form.querySelector('[data-fb-native-login]');if(native){native.hidden=res.code!=='native_verification';}
  }).catch(function(){message.hidden=false;message.textContent='网络异常，请重试';}).finally(function(){button.disabled=false;});
 });});
 document.querySelectorAll('[data-fb-logout]').forEach(function(button){button.addEventListener('click',function(){if(button.disabled)return;button.disabled=true;auth('logout',new URLSearchParams()).then(function(res){if(res.ok)location.reload();}).finally(function(){button.disabled=false;});});});
})();

/* Configured destinations take priority; an unset back link may use local history. */
(function () {
 document.querySelectorAll('[data-fb-back]').forEach(function (button) {
  button.addEventListener('click', function (event) {
   if (button.dataset.fbBackConfigured === '1' || !document.referrer || history.length < 2) return;
   try {
    var previous = new URL(document.referrer);
    if (!previous.searchParams.has('device_preview') && !previous.searchParams.has('login') && previous.origin === location.origin && previous.pathname === location.pathname && /^flipbook:/.test(previous.searchParams.get('id') || '')) {
     event.preventDefault(); history.back();
    }
   } catch (_) {}
  });
 });
})();
