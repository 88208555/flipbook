// Public HTTP regression: both consumers must respect category and series filters.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const base = process.env.FLIPBOOK_TEST_BASE || 'http://127.0.0.1:8088';
const results = [];
async function request(module, params, json = true) {
 const url = new URL('/plugin.php', base);
 url.searchParams.set('id', 'flipbook:' + module);
 Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
 const response = await fetch(url);
 assert.equal(response.status, 200);
 return json ? response.json() : response.text();
}
(async () => {
 const all = await request('api', {});
 assert.equal(all.ok, 1);
 const product = all.products.find(item => item.code);
 assert.ok(product, 'A published product with a unique code is required');
 const detail = await request('api', { productid: product.productid });
 const album = detail.product.albums.find(item => item.catid && item.seriesid);
 assert.ok(album, 'The fixture product must belong to a published category and series');
 const missingCategory = Math.max(...all.categories.map(item => item.catid), 0) + 1000000;
 const missingSeries = Math.max(...all.series.map(item => item.seriesid), 0) + 1000000;
 const cases = [
  ['keyword', {}, true], ['category', { catid: album.catid }, true],
  ['series', { seriesid: album.seriesid }, true],
  ['combined', { catid: album.catid, seriesid: album.seriesid }, true],
  ['missing category', { catid: missingCategory }, false],
  ['missing series', { seriesid: missingSeries }, false],
  ['combined missing series', { catid: album.catid, seriesid: missingSeries }, false],
  ['combined missing category', { catid: missingCategory, seriesid: album.seriesid }, false],
 ];
 for (const [name, filters, expected] of cases) {
  const params = Object.assign({ keyword: product.code }, filters);
  const api = await request('api', params);
  assert.equal(api.ok, 1);
  assert.equal(api.products.some(item => item.productid === product.productid), expected, 'API ' + name);
  const html = await request('micro', Object.assign({ key: 'search' }, params), false);
  const searchForm = html.match(/<form\b[^>]*class="fb-search"[^>]*>[\s\S]*?<\/form>/);
  assert.ok(searchForm, 'H5 search form exists');
  for (const field of ['catid', 'seriesid']) if (filters[field]) assert.ok(searchForm[0].includes('name="' + field + '" value="' + filters[field] + '"'), 'H5 preserves ' + field);
  const card = new RegExp('<a\\b[^>]*class="[^"]*fb-card[^\\s"]*[^"]*"[^>]*href="[^"]*productid=' + product.productid + '(?:&|&amp;|"|#)');
  assert.equal(card.test(html), expected, 'H5 ' + name);
  results.push({ name, expected });
  console.log('PASS API and H5', name);
 }
 if (process.env.FLIPBOOK_TEST_REPORT) fs.writeFileSync(process.env.FLIPBOOK_TEST_REPORT, JSON.stringify({ results }, null, 2));
})().catch(error => { console.error(error); process.exitCode = 1; });
