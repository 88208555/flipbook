# Discuz! X5 开发文档与画册插件规范核对

核对日期：2026-10-18（Asia/Tokyo）。本地基线：Discuz! X5.0.4.Open，Release 20261010，MitFrame 1.0。

本次完成文档阅读与源码交叉核对，没有编写画册业务代码、安装插件、启用开发模式或修改后台设置。以下区分框架事实、项目约定和仍需验证的事项。

## 1. 已阅读的官方资料

| 资料 | 使用范围 |
| --- | --- |
| [技术文档索引](https://open.dismall.com/?ac=document&page=dev) | 开发资料入口，包含新旧文档 |
| [X5 结构概述](https://open.dismall.com/?ac=document&page=dev_x5_index) | 当前目录、自动加载、入口与兼容标记 |
| [X5 App 扩展](https://open.dismall.com/?ac=document&page=dev_x5_app) | 独立 App 与系统扩展的边界 |
| [X5 新增插件接口](https://open.dismall.com/?ac=document&page=dev_x5_plugin) | 扩展点、注册式钩子、账号、存储及微信统一方法 |
| [X5 新增模板接口](https://open.dismall.com/?ac=document&page=dev_x5_template) | 细胞模板、模板变量、手机版 DIY 与模板安装扩展 |
| [插件机制](https://open.dismall.com/?ac=document&page=dev_plugin) | 传统插件模块与嵌入点背景，需与 X5 源码核对 |
| [应用审核规范](https://open.dismall.com/?ac=document&page=audit) | 当前页面标注 2026-04-28、V1.1.6；核对代码、安全、数据库和发布包要求 |
| [编码规范](https://open.dismall.com/?ac=document&page=dev_coderule)、[模板机制](https://open.dismall.com/?ac=document&page=dev_template) | 通用风格与机制参考；页面仍含 2012 年规则 |

实施时先满足用户明确要求，再按本地目标版本验证正式扩展接口。旧编码文档的 PHP 5.3、早期 MySQL、SVN 及历史目录说明不能作为本项目运行基线，也不能据此复制旧 SQL 写法。本机 PHP 8.2、MySQL 8.0 与源码的 PHP 8+ 要求才是当前依据。

## 2. 实现边界：正式插件优先

画册先采用可独立访问的正式插件，标识暂定 `flipbook`，放在 `source/plugin/flipbook/`。后台、移动 Web 继续使用 Discuz 原生机制，不引入 React、Umi、Ant Design 或 Egg。

独立入口不必创建新的核心 App。当前 `index.php` 会在原生 App 不存在时回落至插件分派，因此可使用 `index.php?app=flipbook&module=模块名`，也可使用正式插件 URL。入口仍需已注册模块与启用状态检查；“可以分派”不代表业务已实现。

优先使用正常模块入口。原生插件的特殊 `redirect` 分支会在常规核心初始化之前载入文件，不将其作为默认鉴权方案。只有明确需要 App 专属组织方式且经过验证时，才调整为 `source/app` 扩展。

依据：[入口回落](/Users/zhang/web/flipbook/index.php:20)、[插件分派](/Users/zhang/web/flipbook/source/app/plugin/plugin.php:13)。

## 3. 插件包、目录与清单

| 内容 | 约定 |
| --- | --- |
| 标识与目录 | 使用稳定的小写字母、数字和下划线标识，以字母开头；目录和标识保持一致 |
| 清单 | `discuz_plugin_flipbook.json`；顶层 `Title` 为 `Discuz! Plugin`，元数据放在 `Data` |
| 插件版本 | `Data.plugin.version` 表示画册插件自己的发行版本 |
| 平台兼容版本 | `Data.version` 使用 `X5.0`，不能写成 `X5`；与插件版本分别维护 |
| 模块 | JSON 使用 `Data.plugin.__modules`；导入器转换成原生模块数据，不自创模块类型编号 |
| 基础设置变量 | 使用 `Data.var`，不是 `vars`；复杂内容和业务配置放入插件自己的数据模型 |
| 生命周期 | 按需声明 `checkfile`、`installfile`、`upgradefile`、`enablefile`、`disablefile`、`uninstallfile` |
| 业务入口 | 注册的模块脚本或正式命名空间类入口，禁止根据任意请求参数拼接 PHP 路径 |
| 表与模型 | 在插件的 `table/`、`model/` 等目录组织，沿用框架自动加载约定 |
| 模板与语言 | `template/` 及所需手机版模板；`i18n/SC_UTF8/lang_plugin.php` 等语言资源 |

本版兼容 JSON/XML，同名清单优先读取 JSON，默认导出 JSON；这是当前包格式选择的源码依据。使用 X5 新接口的插件不承诺直接兼容 X3.x。

文件和资源保留在插件自己的命名范围；不覆盖论坛核心或其他插件，不要求安装者手工搬文件。生成缓存通过框架允许的缓存路径管理，卸载清理插件自己的可重建缓存。附件与业务产物通过正式存储接口管理，不以任意路径写文件替代存储模型。

若以后发布到官方应用中心，另按其审核规范准备 ZIP、Logo、预览图、依赖说明与版本升级信息，排除备份、凭据和无关文件；该渠道还要求说明文档通过平台相应功能提供，不直接塞进上传文件包。项目开发文档与渠道发行包分开保存。未来上架也需遵守平台[AI 辅助开发声明与开发者审核要求](https://www.dismall.com/thread-27840-1-1.html)。自用安装也遵守接口、安全与数据保护规范，但本次不申请上架。

依据：[原生清单示例](/Users/zhang/web/flipbook/source/plugin/myrepeats/discuz_plugin_myrepeats.json:1)、[清单格式转换](/Users/zhang/web/flipbook/source/function/function_admincp.php:1410)、[JSON 优先](/Users/zhang/web/flipbook/source/function/function_core.php:2944)、[导出](/Users/zhang/web/flipbook/source/app/admin/child/plugins/export.php:88)。

## 4. PHP、安全与权限

- 沿用目标 X5 代码的文件命名、缩进与类加载方式，公共能力集中封装；不复制原生账号、密码和角色实现。
- 被框架包含的 PHP 文件检查 `IN_DISCUZ`；后台入口同时检查 `IN_ADMINCP`。这些保护仅防直接访问，不能代替动作及资源授权。
- 后台写操作使用原生提交校验及 `formhash`，优先 POST，不使用 `allowget` 绕过正常校验。
- 输入按 Schema 校验类型、长度、范围、枚举和资源引用；输出按 HTML、URL、JSON 等实际上下文处理，不假定模板会自动安全转义。
- 原生后台支持 `plugin_标识:pmod:do` 权限粒度。接入现有后台职务，并为画册、产品、素材、发布等动作落实服务端授权。
- 隐藏按钮、会员已经登录、插件已经启用、REST 应用已有权限，均不能单独证明当前用户有权操作目标内容。
- 不把 PHP、任意 SQL、可执行模板或任意脚本作为运营可填写的“引擎配置”。

依据：[提交检查](/Users/zhang/web/flipbook/source/class/helper/helper_form.php:16)、[后台授权](/Users/zhang/web/flipbook/source/app/admin/class/class_core.php:326)、[正式后台模块分派](/Users/zhang/web/flipbook/source/app/admin/child/plugins/config.php:193)。

## 5. 数据库、配置与缓存

复用原生 UID、会员状态、用户组、团队与职务。画册业务使用带 `flipbook_` 业务名称的独立表，部署表前缀由框架提供，不能硬写 `pre_`。

表类继承原生 `discuz_table`，优先通过正式表／模型调用与 `DB` 占位符处理查询。不能将“输入被框架转义”解释为可安全拼接任意字段、排序、表名或 SQL 片段。数据库采用本项目已部署的 MySQL 8.0、InnoDB、utf8mb4；索引根据实际查询设计。

内容、组件 Schema、文案、图标引用、布局参数、设计令牌及发布版本以 MySQL 为持久化来源。连接参数和加密根秘密仍属于部署配置，避免让数据库启动依赖数据库内的连接配置。

Redis 作为缓存与原生接口的运行依赖，不作为业务内容的唯一存储。插件缓存使用独立键空间和配置／发布版本；明确修改后的失效、重建及故障行为。基础缓存可使用原生 `memory()` 和插件缓存扩展。

依据：[表／模型加载](/Users/zhang/web/flipbook/source/class/class_core.php:50)、[原生表封装](/Users/zhang/web/flipbook/source/class/discuz/discuz_table.php:17)、[占位符示例](/Users/zhang/web/flipbook/source/plugin/myrepeats/table/table_myrepeats.php:37)、[插件缓存](/Users/zhang/web/flipbook/source/function/function_cache.php:28)。

## 6. 钩子与生命周期

传统插件类钩子和 X5 注册式钩子并存。`hook::register()`／`hook::unregister()` 按 `modid`、`type`、`hookid`、`method` 及 PC／Mobile 范围登记回调，不能只按旧版类名约定推断全部扩展行为。

当前源码将新式注册项写入 `common_setting.newhook` 并直接合并到缓存；普通禁用流程没有自动撤销这些注册项。项目必须为注册式钩子实现明确的启用、禁用和卸载处理，回调自身也校验插件状态与业务权限。

子文件接管不同于普通钩子：官方 X5 文档说明同一目标只允许一个接管插件，并禁止接管 `admin/*`。优先采用可组合的正式扩展，不通过抢占系统后台流程实现画册。

| 阶段 | 项目要求 |
| --- | --- |
| 安装 | 检查版本与依赖；建立不存在的业务结构及初始配置，重复执行不能删除已有数据 |
| 启用 | 注册自身入口、钩子及确实需要的扩展；注册过程可重复且不产生重复项 |
| 升级 | 使用插件业务版本与迁移记录，支持失败识别及恢复；保留配置、内容和账号关系 |
| 停用 | 关闭入口与新任务，撤销自身注册项；保留原生会员、画册、绑定和素材 |
| 卸载 | 默认保留业务数据，撤销扩展注册；删除内容、绑定或云对象须作为独立管理操作 |

原生升级可能在迁移脚本执行前已经更新插件元数据，因此迁移状态不能只看插件版本字段。原生卸载先删除插件元数据和 `common_pluginvar`，再调用卸载脚本；需要保留的业务配置不能仅依赖这些会自动删除的变量，必须在数据模型与卸载流程中事先安排。

原生示例 `myrepeats` 只参考接口结构：其安装脚本有删表语句，卸载示例仅定义 SQL 而未调用执行器，模板也含内联样式，均不能当作本项目实现范本直接复制。

依据：[注册式钩子](/Users/zhang/web/flipbook/source/class/class_hook.php:24)、[缓存合并](/Users/zhang/web/flipbook/source/function/cache/cache_setting.php:911)、[启停过程](/Users/zhang/web/flipbook/source/app/admin/child/plugins/enable_disable.php:32)、[卸载顺序](/Users/zhang/web/flipbook/source/app/admin/child/plugins/delete.php:36)、[安装示例边界](/Users/zhang/web/flipbook/source/plugin/myrepeats/install.php:13)。

## 7. 原生模板、组件与样式引擎

X5 已有 `{cell}`／`{cells}` 细胞模板、模板变量、页面／模块组件和手机版扩展。画册应先映射并复用这些机制，再补专属组件注册表、页面数据协议和发布快照，不能一开始另造完整前端框架。

插件模板可通过 `标识:模板名` 调用；当前框架兼容 `.htm` 与 `.php`。原生 App 模板使用 `.php`。模板只负责结构与展示，不承担数据库查询、权限决策或业务流程。语言资源与可运营文案分开：固定系统提示使用语言包，标题、按钮文案等业务内容由配置引擎管理。

本项目仍执行：黑白灰主题、禁止蓝紫色、统一 Morphicons 图标体系、禁止内联样式和页面散落的颜色常量、后台扁平操作。原生示例和现有后台的默认写法不构成豁免。样式值由集中配置校验、版本化并生成资源，页面引用受控类名与发布产物。

小程序使用原生渲染端；Web 的模板、DOM、CSS 与图标动效不能直接视为小程序可用实现。任意样式配置与零内联约束的组合、动态图标与海报渲染须先做目标端验证，不能提前标记为已解决。

依据：[官方 X5 模板接口](https://open.dismall.com/?ac=document&page=dev_x5_template)、[本地模板分派](/Users/zhang/web/flipbook/source/function/function_core.php:804)、[模板文件读取](/Users/zhang/web/flipbook/source/class/class_tplfile.php:15)。

## 8. RESTful 与公开客户端边界

本地原生 REST 实现使用 APPID、SIGN、NONCE、T、secret 以及 Token 的组合，包含频率与应用权限检查。即使官方介绍使用 OAuth2 字样，也不能将该实现直接描述成无需适配的标准 OAuth2 客户端协议。

小程序／移动 Web 不能携带服务端应用 secret。后续需要验证公开客户端会话怎样衔接原生会员、后台权限及 REST 扩展；应用权限、会员权限、后台动作权限和画册对象权限分别检查。当前实现的空接口授权列表也不能被当成默认拒绝全部接口。

本次只完成协议与源码阅读，没有调用业务 API 或完成微信客户端认证验证。Redis 已在基础部署中启用，不等于画册 API 已存在。

依据：[REST 入口](/Users/zhang/web/flipbook/api/restful/index.php:131)、[原生验签](/Users/zhang/web/flipbook/source/class/class_restful.php:412)、[接口权限检查](/Users/zhang/web/flipbook/source/class/class_restful.php:261)、[插件扩展](/Users/zhang/web/flipbook/source/class/class_restful.php:374)。

## 9. 微信身份与 COS 扩展

微信小程序身份通过正式账号提供者扩展对接原生会员，使用 AppID 作用域明确的身份键。注册提供者前查询已有类型映射，升级不重复分配。原生 `unregisterAccount()` 会删除对应类型的账号绑定，因此不能直接放进默认保留数据的卸载路径。

本地存在 `weixin` 类，包含统一 access_token、JSAPI ticket、JSSDK 签名和分享数据入口，可先评估复用。公众号后端 token、网页用户 OAuth token 与小程序登录凭证分别处理；该类存在不代表小程序微信登录和真机分享已经接通。

COS 使用正式 OSS 服务商插件契约：发现已启用插件的 `oss/init.php`，按 `oss_plugin_标识` 加载适配器，复用原生配置入口。接入范围可能包括全站附件、头像与缓存资源，不能只验证画册单张图片上传。停用或卸载不默认删除云端对象。

依据：[账号类型注册／注销](/Users/zhang/web/flipbook/source/class/account/account_base.php:330)、[微信统一方法](/Users/zhang/web/flipbook/source/class/class_weixin.php:25)、[OSS 服务商发现](/Users/zhang/web/flipbook/source/app/admin/child/setting/attach.php:177)、[OSS 加载契约](/Users/zhang/web/flipbook/source/class/class_oss.php:24)。

## 10. 编码前仍需完成的技术验证

1. 公开小程序／移动 Web 会话与原生鉴权的安全衔接，不向客户端下发应用秘密。
2. 后台编辑器请求保留原生管理会话，并落实模块、动作和对象权限。
3. 画册组件协议、零内联样式及统一图标在 Web 和原生小程序上的可行实现。
4. 新钩子的启用、停用、卸载和升级恢复；身份绑定及业务配置的保留。
5. COS 服务商字段、签名、私有访问、回调、附件兼容及停用行为。

本项目继续按单站插件建设；需要新站时复制干净发布包独立安装。上面这些核对不恢复集中多租户架构，也不涉及用户已有的聚水潭、钉钉插件。

## 11. 插件可移植交付：已确认的目标

用户进一步明确：新项目只需安装一套兼容版本的 Discuz，再安装同一个画册插件包。论坛侧能力应随插件注册和安装，不为每个新项目重新开发一套前后台或手工建表。

| 随插件交付 | 新站安装行为 |
| --- | --- |
| 前台页面与移动 Web | 安装包携带插件模板、组件和资源，通过正式入口调用 |
| 后台管理 | 自动登记插件管理模块、设置入口及可分配的动作权限，具体授权由新站管理员决定 |
| 数据库 | 自动创建插件业务表、索引和结构版本；适配目标站实际表前缀 |
| 引擎与初始数据 | 安装干净的组件登记、页面／海报模板、主题令牌、图标登记和默认配置 |
| 接口与集成入口 | 按正式机制登记所需接口、账号提供者、存储适配和钩子 |
| 升级与停用 | 版本化迁移、缓存刷新、注册项维护和数据保留由插件生命周期处理 |

安装前检查兼容的 Discuz、PHP、MySQL、Redis 和扩展条件。新站管理员填写本站微信 AppID、秘密、COS、域名与回调等必要参数；不能把旧站凭据、会员、会话、业务内容或私有素材夹带进插件包。

默认使用插件自带模板和资源，不以额外手动安装主题包、复制根目录文件或修改系统源码为前置步骤。若以后确有必须配套的正式扩展，应声明并使用受支持的依赖安装流程，不能把手工拼装留给安装者。

原生微信小程序仍是微信平台上的独立发布包，需要上传到目标 AppID；论坛插件负责提供其数据、配置、鉴权及后台管理能力。论坛中点击安装插件不能代替微信侧的代码上传、审核与发布。

验收时必须在全新的兼容 Discuz 上，验证“上传插件包 → 原生安装 → 启用 → 填写站点参数 → 使用”，不手工修改论坛核心、不手工搬程序文件、不手工导入业务 SQL；再验证升级、停用、重新启用及默认保留数据的卸载行为。
