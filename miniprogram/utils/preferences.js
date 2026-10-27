const PROGRESS_KEY = 'flipbook_reading_progress';
const DEFAULTS = { thumbnails: true, resume: true };

// Reading options are fixed defaults; legacy opt-out values no longer affect the reader.
function defaults() { return Object.assign({}, DEFAULTS); }

function progress(productid, slides) {
 try {
  const item = (wx.getStorageSync(PROGRESS_KEY) || {})[productid];
  if (!item || !Array.isArray(slides)) return 0;
  const index = slides.findIndex(slide => slide.key === item.key);
  return index >= 0 ? index : Math.max(0, Math.min(Number(item.index) || 0, slides.length - 1));
 } catch (error) { return 0; }
}

function remember(productid, index, slides) {
 if (!(Number(productid) > 0) || !slides || !slides[index]) return;
 try {
  const stored = wx.getStorageSync(PROGRESS_KEY);
  const items = stored && typeof stored === 'object' && !Array.isArray(stored) ? Object.assign({}, stored) : {};
  items[productid] = { index, key: slides[index].key, updated: Date.now() };
  const recent = {};
  const previous = Object.keys(items).filter(key => key !== String(productid)).sort((a, b) => (Number(items[b].updated) || 0) - (Number(items[a].updated) || 0));
  [String(productid)].concat(previous).slice(0, 100).forEach(key => { recent[key] = items[key]; });
  wx.setStorageSync(PROGRESS_KEY, recent);
 } catch (error) { /* Reading remains available when local storage is full. */ }
}

module.exports = { defaults, read: defaults, progress, remember };
