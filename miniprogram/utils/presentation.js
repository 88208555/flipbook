const micro = require('./micro');
const loginTheme = require('./login-theme');
const px = value => (Number(value) || 0) * 2 + 'rpx';
const MENU_ICONS = { orders:'book', clock: 'history', 'share-grey': 'share', sliders: 'preference', info: 'info' };

/** Screen regions and generic components consume the same sparse style settings. */
function prepare(options) {
 const p = options || {};
 const type=({home:'social',detail:'product_detail',mine:'member_center',catalog:'album_list',search:'album_list',favorites:'favorite_list',history:'history_list',preference:'reading_preference'})[p.source]||'';
 const configured = micro.effectiveRegions(type,p);
 const login=loginTheme(p,configured);
 const caption = Object.assign({}, p.caption || {});
 ['enabled','showTitle','showSubtitle','showCode','showExtra'].forEach(key=>{
  caption[key] = caption[key] === 0 || caption[key] === false || caption[key] === '0' ? 0 : 1;
 });
 const floating = p.floating || {};
 const menuIconStyles = {};
 Object.keys(MENU_ICONS).forEach(key => {
  menuIconStyles[key] = micro.regionIconStyle(MENU_ICONS[key], (configured.menuIcon || {}).color || (key==='orders'?'#85858B':''));
 });
 return {
  regionStyles: micro.regionStyles(configured),
  loginBackground:login.loginBackground,loginForeground:login.loginForeground,loginNavigationBackground:login.loginNavigationBackground,loginNavigationText:login.loginNavigationText,
  quickLoginStyle:login.quickLoginStyle,loginThemeStyle:login.loginThemeStyle,loginTabStyle:login.loginTabStyle,loginTabActiveStyle:login.loginTabActiveStyle,loginTabDisabledStyle:login.loginTabDisabledStyle,loginPlaceholderStyle:login.loginPlaceholderStyle,
  menuIconStyles,
  searchIconStyle: micro.regionIconStyle('search', (configured.searchIcon || {}).color),
	avatarImageStyle: Object.prototype.hasOwnProperty.call(configured.avatar || {}, 'radius') ? 'border-radius:' + px(configured.avatar.radius) : '',
  presentation: p,
  caption,
  captionStyle: 'left:' + px(caption.left === undefined ? 16 : caption.left) + ';right:' + px(caption.right === undefined ? 16 : caption.right) + ';bottom:' + px(caption.bottom === undefined ? 10 : caption.bottom) + ';text-align:' + (caption.align || 'center') + ';color:' + (caption.color || '#fff') + ';font-size:' + px(caption.size || 22) + ';font-weight:' + (caption.weight || 700) + ';',
  subtitleStyle: 'font-size:' + px(caption.subSize || 16) + ';color:' + (caption.subColor || '#dadadd') + ';',
  floating,
  floatingStyle: (floating.align === 'left' ? 'left:' + px(floating.left) : 'right:' + px(floating.right)) + ';bottom:' + px(floating.bottom) + ';width:' + px(floating.width || 60) + ';height:' + px(floating.height || 60) + ';border-radius:' + px(floating.radius) + ';',
 };
}

module.exports = { box: micro.regionStyle, prepare };
