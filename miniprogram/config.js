/**
 * 小程序运行配置。
 *
 * API_BASE 指向已部署的 Discuz! 站点；真机调试需替换为可访问的 HTTPS 域名，
 * 并在微信公众平台配置 request 合法域名。开发者工具中已关闭域名校验。
 */
module.exports = {
	API_BASE: 'http://127.0.0.1:8088',
	PLUGIN_ID: 'flipbook',
	REQUEST_TIMEOUT: 10000,
};
