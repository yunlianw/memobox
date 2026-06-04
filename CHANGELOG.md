# Changelog

## [1.0.1] - 2026-06-04

### 🐛 Bug 修复

- **分享错误页显示首页自定义 HTML**：当访问已删除/无效的分享链接时，`?action=share&token=xxx` 的 query string 被 Nginx `index` 内部跳转吞掉，`$_GET` 和 `$_SERVER['QUERY_STRING']` 均为空，Router 无法匹配分享路由，落到根路径处理输出了首页伪装内容。修复：`Router::dispatch()` 增加 `REQUEST_URI` fallback 手动解析 query string。

- **分享文档图片无法显示**：`ShareController::showDocument()` 中图片 URL 替换的正则回调 `$m[1]` 取了整个分组匹配字符串而非纯 ID，导致 `File::getById(0)` 永久返回 null，30 张图片链接全部未替换。修复：将正则拆为 `key` 和 `id` 两个独立规则分别处理。

- **分享编辑页自定义次数不生效**：`toggleEditClicksCustom()` 的比较 `val === 'custom'` 使用了 `parseInt` 导致死循环，无法输入自定义次数。修复：改为纯字符串比较。

- **SimpleMDE 编辑器自动保存覆盖编辑中内容**：SimpleMDE 的 `autosave` 自动将 `localStorage` 缓存恢复到编辑器，覆盖了最新从数据库拉取的内容。修复：关闭 `autosave`。

- **编辑/预览页浏览器缓存**：后台页面被浏览器缓存导致显示旧数据。修复：`AdminController` 的 `editDocument()` 和 `previewDocument()` 设置 `no-store` 缓存头。

- **熔断设置后台提示但用户不可见**：`Share::burn()` 设置的 `max_clicks=1` 被 `click_count=max_clicks` 覆盖（因 `max_clicks` 默认 -1 表示无限）。修复：`burn()` 同时设置 `max_clicks=1` 和 `click_count=1`。

### ✨ 新增功能

- **全场景伪装系统**：后台设置页支持首页伪装（404/自定义 HTML）和分享错误伪装（Nginx 404/自定义 HTML）。
- **HTML 源码编辑模式**：文档新增 `html` 类型，支持直接编写完整的 HTML 页面，前台直接渲染输出。
- **文档预览功能**：后台编辑页面增加「预览」按钮，支持 Markdown 和 HTML 模式的实时预览。
- **文档图片自动替换**：分享内容中的图片 URL 自动替换为带 Token 验证的公开路由。

## [1.0.0] - 2026-06-03

### 🎉 初始版本

- 基于 Markdown 的私密知识库系统
- 文档创建、编辑、删除
- 文件上传和管理
- 文档分享（带密码保护、过期时间、点击熔断、阅后即焚）
- 访问日志记录
- IP 封锁（连续失败登录）
- Parsedown Markdown 解析
- 自定义后台路径
