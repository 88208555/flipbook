# Flipbook 画册

Discuz! X5 画册项目，包含后台、H5 微页面、原生微信小程序、产品同步插件，以及当前商品和微页面配置的上线数据库。数据库发布版本为 **2026.10.27**。

## 仓库内容

| 目录 | 内容 |
| --- | --- |
| `app/` | Discuz! X5 应用，以及 `flipbook`、`flipbook_sync` 插件 |
| `miniprogram/` | 原生微信小程序源码 |
| `database/schema.sql` | 完整数据库表结构，包括订单表 |
| `database/data.sql` | 系统配置、商品、画册、SKU、媒体和已保存微页面配置 |
| `database/manifest.json` | 导出时间、各表记录数、排除范围和 SQL SHA-256 校验值 |
| `scripts/` | 新环境初始化、数据库导出和发布检查 |
| `tests/` | 插件、微页面、H5、小程序渲染等验证脚本 |

发布数据库保留全部表结构和现有商品／微页面内容。账号、密码、会话、日志、用户收藏／阅读记录、订单与支付记录、同步授权与数据源密钥不进入 Git。私有连接配置、微信／支付／COS 私钥和本机缓存也不进入 Git。首次部署会创建新的管理员及站点密钥；数据库不是含私人账号的原样备份。排除的非空表和记录数列在 manifest 中。

## 首次上线

需要 Docker Engine、Docker Compose v2、Python 3 和 Node.js 20 或更新版本。适用于**新的空数据库**；脚本发现已有表或账号时会停止，不会覆盖原站点。

```sh
git clone git@github.com:88208555/flipbook.git
cd flipbook
python3 scripts/prepare-env.py --site-url https://your-domain.example
# 在本机查看 .env，设置管理员邮箱；管理员密码和数据库密码已随机生成。
sh scripts/deploy.sh
```

`.env` 权限为 0600，已被 Git 忽略。服务默认监听 `127.0.0.1:8088`；将域名的 HTTPS 反向代理指向该地址。直接在开发环境访问时，将 `.env` 的 `SITE_URL` 设为实际访问地址。需要其他监听地址／端口时修改 `HTTP_BIND`、`HTTP_PORT`。MySQL 和 Redis 仅在 Docker 内部网络提供服务。

部署入口会校验 SQL，导入完整表结构及内容，生成 `app/config/config_global.php` 和 `config_ucenter.php`，创建新的管理员并更新应用缓存。管理员用户名默认 `admin`，密码在本机 `.env` 中。初始化脚本不是现有站点的密码重置工具。

上线后在后台填写本站自己的微信 AppID／AppSecret、上传私钥、COS 和支付配置。小程序还需把 `miniprogram/config.js` 的 `API_BASE` 改成本站 HTTPS 地址，在 `project.config.json` 填写自己的 AppID，并配置微信合法域名。本仓库上传不等于微信审核、发布或公网服务器部署。

小程序云端构建服务另见 `app/source/plugin/flipbook/release/setup.txt`。所有密钥保留在部署环境，不能提交到仓库。

## 数据库与恢复

`schema.sql` 和 `data.sql` 顺序导入到一个新的 MySQL 8 数据库，之后运行 `scripts/bootstrap-release.php`。默认表前缀由 `manifest.json` 给出。连接信息和管理员初始化信息从环境变量读取，具体容器调用已封装在 `scripts/deploy.sh`。

导出新的可分享数据库时，在运行站点的 PHP 容器中执行 CLI 导出器；该过程采用只读一致性事务，输出位置必须为空目录。例如本项目容器：

```sh
docker compose exec -T php php /opt/flipbook/scripts/export-release.php --output=/tmp/flipbook-release-new
docker compose cp php:/tmp/flipbook-release-new/. ./database/
node scripts/verify-release.cjs --files
```

导出器不会导出私人账号和密钥。当前数据库文件及校验值必须一并更新；更新已有线上数据库应使用插件自己的升级入口，不可用首次部署脚本覆盖。

## 发布验证

```sh
node scripts/verify-release.cjs --files
# git add 后检查实际暂存内容：
node scripts/verify-release.cjs
```

发布前已完成独立空数据库导入、初始化与登录凭据校验。应用各功能的测试范围见 `database/VERIFICATION.md`。微信开发者工具中的实际页面测试仍需在对应画册项目窗口完成。

Discuz! 及内含第三方依赖保留原有版权和许可声明；本仓库未额外更改它们的授权。
