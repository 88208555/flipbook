// Independent product components share in-flight reads, without retaining stale data.
const api = require('./api');
const pending = new Map();
function get(params) {
 const key = JSON.stringify(params);
 if (pending.has(key)) return pending.get(key);
 const task = api.get(params).then(value => { pending.delete(key); return value; }, error => { pending.delete(key); throw error; });
 pending.set(key, task);
 return task;
}
module.exports = Object.assign({}, api, { get });
