# 2026.10.27 数据库发布验证

该发布包使用独立新建的 MySQL 8 数据库和临时 PHP 容器进行验证，没有重启或修改正在运行的站点。测试库、测试数据库账号和测试容器已清理。

## 当前结果

- 通过：full schema and content import into a new database
- 通过：fresh founder credential and unique private configuration
- 通过：repeat bootstrap refuses existing installation
- 通过：native Discuz cache rebuild against restored release
- 通过：restored API and H5 page smoke checks
- 通过：native UCenter administrator login with new password

数据库包含 325 张表结构、1,314 条选定发布数据，以及 11 个已保存微页面。SQL SHA-256 校验通过。新管理员密码、站点认证密钥及数据库连接配置在部署环境重新生成。

暂存源码经过私钥／Token 模式检查，并与本机实际账号密码摘要、站点密钥和已配置业务密钥进行比对，未发现这些私密值进入提交。安装器公开示例占位词和示例邮箱不作为真实凭据。

Entrorove 对发布检查脚本完成了定向源码审查，状态为 succeeded，最终返回 0 条发现。请求编号：`a43edf04235464255e22eb93b626e3c020194184c8a574b515d74f92e93dc7d3`。此结果属于源码审查建议，不代替功能测试。

## 验证边界

- 本次验证包括数据库恢复、首次初始化、原生缓存重建、账号验证，以及 H5／API 的真实 HTTP 响应。
- 没有执行真实微信上传、审核、支付或服务器公网部署。
- 微信开发者工具中的实际页面操作测试仍按用户安排留待后续进行。
- 首次部署脚本只用于空数据库；既有站点升级应使用插件升级入口。

## 已修正的验证问题

初次空库测试暴露了旧版必填文本与 JSON 字段没有默认值，以及 Discuz CLI 初始化上下文兼容问题；初始化和缓存脚本已修正并重新完成上述验证。历史失败没有计入通过结果。
