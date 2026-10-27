const BUILTIN = ['home', 'detail', 'favorites', 'catalog', 'search', 'mine', 'history', 'about', 'privacy', 'agreement', 'login'];

function isCustom(value) {
 return typeof value === 'string' && /^micro_[a-f0-9]{16}$/.test(value);
}

function valid(value) {
 return BUILTIN.indexOf(value) >= 0 || isCustom(value);
}

function normalize(value) {
 if (value === 'preference') return 'mine';
 return valid(value) ? value : 'home';
}

module.exports = { isCustom, valid, normalize };
