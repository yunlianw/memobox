# Changelog

## [1.0.3] - 2026-06-05

### 🐛 Bug 修复

- **登录页CSRF验证失败**：POST登录到 `/yunlian` 时报 `CSRF validation failed`。原因是 CSRF 排除登录的判断逻辑依赖 `?action=login` GET 参数，但登录表单是纯 POST 无 action 参数。修复：改为直接判断目标路径是否为 `/yunlian`（等登录路径）。
- **安全设置页面报 Permission denied**：保存会话超时时 `AdminController.php` 试图用 `file_put_contents()` 写 `Config.php`，但文件属主为 root、www 用户无写权限。修复：`Config::init()` 改为从 `system_settings` 表读取 `session_timeout`（后台可配置），删除写文件逻辑，配置与代码分离。

---

## [1.0.2] - 2026-06-04

### 🔒 安全修复（9项）

**P0 高危修复：**
1. **CSRF Token 防护** — 所有后台POST操作添加Token验证（Security.php + Router.php + 6个模板）
2. **XSS（html文档）** — 预览用iframe sandbox隔离，分享页加CSP头
3. **文件上传MIME验证** — 扩展名白名单 + finfo真实类型检测 + 图片二次验证
4. **文件上传路径遍历** — serveStatic()用realpath()防`../`穿越

**P1/P2 中危修复：**
5. **会话安全配置** — 添加cookie_httponly / SameSite / secure（Config.php）
6. **调试头信息泄露** — 删除X-Query-Debug、X-Share-Matched、X-Share-Error
7. **分享密码表单** — GET改为POST，密码不在URL暴露
8. **密码策略加强** — 强制≥8位+大小写+数字

**P3 低危修复：**
9. **install.lock加固** — 验证JSON内容完整性，安装完成提示删除install/

### ✨ 新增
- **后台安全设置页面** — 文件上传白名单、会话超时、密码策略均可后台配置
- **安全状态总览** — 页面底部展示所有防护措施启用状态

---

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

## [1.0.4] - 2026-06-05

### 🔧 工程化重构

- **AdminController.php 拆分**：原 733 行大文件拆分为 7 个独立文件（均 < 400 行）
  - `AdminController.php` — 路由分发器 + 登录/登出（110 行）
  - `Admin/Dashboard.php` — 仪表盘（19 行）
  - `Admin/Document.php` — 文档管理（166 行）
  - `Admin/Share.php` — 分享管理（154 行）
  - `Admin/File.php` — 文件管理（185 行）
  - `Admin/Settings.php` — 系统设置 + 安全（143 行）
  - `Admin/Log.php` — 审计日志（26 行）
- 符合工程化开发红线：禁止上帝文件，强制模块化分层
- 不影响现有功能，仅调整文件结构

### ✨ 新增功能

- **独立账户设置页面** `/yunlian/account`
  - 修改用户名（需当前密码验证）
  - 修改密码（需当前密码 + 新密码 + 确认新密码，二次确认）
  - 密码策略跟随「安全设置」动态切换：
    - 未勾选强制策略 → 至少 6 位
    - 勾选强制策略 → 至少 8 位 + 大小写 + 数字
- **后台导航栏统一 8 项**：仪表盘 / 文档 / 文件 / 分享管理 / 日志 / 设置 / 安全 / 账户
- **设置页「账户设置」改为跳转按钮**，不再混在系统设置里

### 🐛 Bug 修复

1. **登录页「密码正确但显示密码错误」**
   - 根因：`dispatch()` 开头检查 `!User::isLoggedIn()`，POST 登录请求被拦截直接显示登录页
   - 修复：添加 `$isLoginPost` 判断，POST 登录优先处理

2. **设置页显示空提示条（绿色 + 红色同屏）**
   - 根因：`settings()` 初始化 `$success = ''; $error = ''`，模板用 `isset()` 判断，空字符串也算已设置
   - 修复：删除初始化，`isset($success)` → `!empty($success)`

3. **账户设置密码策略硬编码**
   - 根因：`Account.php` 硬编码 `strlen≥8` + 大小写数字，不读取 `password_policy` 配置
   - 修复：读取 `Setting::get('password_policy')`，动态切换策略

4. **`Account.php` 缺少 `Setting.php` 引用**
   - 修复：补上 `require_once __DIR__ . '/../../Models/Setting.php'`

5. **分享管理页面路由修复** — `/yunlian/shares` 正常显示
6. **文件上传页面路由修复** — `/yunlian/upload` 正常显示

---

