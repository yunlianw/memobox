# MemoBox — 私密知识库系统

一款轻量级私密知识库系统，支持 Markdown 写作、文件管理、加密分享。

## ✨ 功能特性

- 📝 Markdown 编辑器（SimpleMDE，实时预览）
- 🔗 三种文档类型：Markdown / 链接跳转 / HTML 源码
- 📂 文件上传、管理、预览（支持切片上传大文件）
- 🔒 分享控制：密码保护、过期时间、点击熔断、阅后即焚
- 🕵️ 全场景伪装：首页伪装（404/自定义 HTML）、分享错误伪装
- 🛡️ 安全防护：CSRF Token、IP 封锁、会话安全、自定义后台路径
- 📊 访问日志统计 + 安全设置页面
- 🔐 密码策略：强制大小写+数字，最少8位

## 📁 目录结构

```
/
├── app/                    # 应用核心
│   ├── Config.php          # 数据库及系统配置（安装时生成）
│   ├── Config.example.php  # 配置模板
│   ├── Router.php          # 路由分发器
│   ├── Security.php        # 安全工具（CSRF/会话/XSS防护）
│   ├── Controllers/        # 控制器（已拆分，均 < 400行）
│   │   ├── AdminController.php  # 路由分发器 + 登录/登出（110行）
│   │   ├── Admin/              # 后台子控制器
│   │   │   ├── Dashboard.php   # 仪表盘（19行）
│   │   │   ├── Document.php    # 文档管理（166行）
│   │   │   ├── Share.php       # 分享管理（154行）
│   │   │   ├── File.php        # 文件管理（185行）
│   │   ├── Settings.php    # 设置+安全（143行）
│   │   │   └── Log.php         # 审计日志（26行）
│   │   ├── ShareController.php  # 分享处理器
│   │   └── AuthController.php    # 认证相关
│   ├── Models/             # 数据模型
│   │   ├── Document.php      # 文档模型
│   │   ├── File.php          # 文件模型
│   │   ├── Share.php         # 分享模型
│   │   ├── User.php          # 用户模型
│   │   ├── Log.php           # 日志模型
│   │   └── Setting.php       # 系统设置模型
│   └── Services/           # 第三方服务
│       └── Parsedown.php     # Markdown 解析器
├── public/                 # Web 根目录（Nginx root 指向这里）
│   ├── index.php           # 统一入口
│   └── install/            # 安装向导
├── storage/files/           # 文件存储目录
├── templates/              # 视图模板
│   ├── admin/              # 后台页面（8个导航：仪表盘/文档/文件/分享管理/日志/设置/安全）
│   └── share/              # 分享页面
├── CHANGELOG.md            # 更新日志
├── README.md               # 本文档
└── LICENSE                 # MIT 开源协议
```

## 🚀 安装教程

### 方式一：使用宝塔面板（推荐新手）

**第一步：创建站点并上传源码**
1. 宝塔 → 网站 → 添加站点（填你的域名）
2. 进入站点根目录，上传 `memobox-v1.0.4.tar.gz`
3. 解压，得到 `memobox/` 目录

**第二步：设置运行目录**
宝塔 → 网站 → 设置 → 运行目录 → 选择 `/public` → 保存

**第三步：设置伪静态**
宝塔 → 网站 → 设置 → 伪静态 → 粘贴以下内容 → 保存：

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

**第四步：创建数据库**
宝塔 → 数据库 → 添加数据库
- 数据库名：`memobox`
- 用户名：（自定义）
- 密码：（记下来，安装时要用）

**第五步：开始安装**
1. 浏览器访问你的域名，自动跳转到安装页
2. Step 1 环境检测 → 全部 ✅ → 下一步
3. Step 2 数据库配置 → 填刚才创建的数据库信息 → 点「测试连接」→ ✅ 成功后下一步
4. Step 3 管理员设置 → 设置用户名/密码/后台目录名 → 下一步
5. Step 4 完成 → 记录后台地址和账号 → 进入后台

---

### 方式二：手动配置 Nginx（适合有经验用户）

**第一步：上传源码**
```bash
cd /www/wwwroot
# 上传 memobox-v1.0.4.tar.gz 后解压
tar xzf memobox-v1.0.4.tar.gz
```

**第二步：配置 Nginx**

完整 `server` 配置示例：

```nginx
server {
    listen 80;
    server_name your-domain.com;     # ← 改成你的域名
    root /www/wwwroot/memobox/public;   # ← 注意是 public/ 目录
    index index.php;

    # 【必须】伪静态规则
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # 【必须】PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-85.sock;  # ← 根据你的 PHP 版本调整
        fastcgi_index index.php;
        include fastcgi.conf;
    }

    # 静态文件缓存（可选）
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
    }
}
```

**第三步：创建数据库**
```bash
mysql -u root -p
CREATE DATABASE memobox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'memobox_user'@'127.0.0.1' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON memobox.* TO 'memobox_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

**第四步：访问域名，按向导完成安装**（同宝塔版 Step 5）

---

## 🔧 常见问题

**Q：安装时「测试连接」失败？**
A：检查数据库名/用户名/密码是否正确；确认 MySQL 服务正在运行；确认数据库用户有权限访问该数据库。

**Q：打开域名不跳转到安装页？**
A：检查 `public/` 是否设为运行目录；检查 Nginx 伪静态规则是否配置；检查 `install.lock` 是否存在（存在会跳过安装，删掉即可重新安装）。

**Q：分享链接打开是 404？**
A：两种可能——① 分享已过期或超过点击次数；② Nginx 配置问题。后台「分享管理」页面可查看所有分享状态。

**Q：上传文件后无法显示/下载？**
A：检查 `storage/files/` 目录权限是否为 `755`；确认 PHP `fileinfo` 扩展已安装（可选，影响文件类型检测）。

**Q：后台目录名忘了？**
A：打开 `app/Config.php`，找到 `ADMIN_PATH` 常量，值就是你的后台目录名。

**Q：登录报 CSRF validation failed？**
A：清除浏览器 Cookie 重试；检查 `Config.php` 的 `SESSION_TIMEOUT` 配置；确认 PHP session 目录可写。

---

## 🔧 二次开发指南

### 架构概要

Memobox 采用**传统 MVC 分离模式**，控制器已按功能拆分为独立子文件（均 < 400行）：

| 层 | 目录 | 职责 |
|----|------|------|
| **入口** | `public/index.php` | 加载 Config、Security、Router |
| **路由** | `app/Router.php` | 解析 URI，分发到控制器 |
| **控制器** | `app/Controllers/` | 接收请求、调用模型、渲染模板 |
| **模型** | `app/Models/` | 数据库操作、业务逻辑 |
| **视图** | `templates/` | HTML 模板（原生 PHP 模板） |

### 数据流向

```
用户请求
  → Nginx
    → public/index.php
        → Router::dispatch()
              → 后台路由 → AdminController::dispatch()
                    → AdminDashboard::render()        # 仪表盘
                    → AdminDocument::handle()        # 文档管理
                    → AdminShare::shares()           # 分享管理
                    → AdminFile::handle()            # 文件管理
                    → AdminSettings::settings()      # 系统设置
                    → AdminSettings::security()      # 安全设置
                    → AdminLog::render()             # 审计日志
              → 分享路由 → ShareController::handle()
              → 首页路由 → renderNginx404() / renderCustomHtml()
```

### 关键修改点

#### 1. 添加新的后台页面

**步骤：**
1. 在 `app/Controllers/Admin/` 下新建 `NewPage.php`
2. 创建类 `AdminNewPage`，添加 `public static function render(): void { ... }`
3. 在 `AdminController.php` 的 `dispatch()` 中添加路由：
   ```php
   case 'new_page':
       require_once __DIR__ . '/Admin/NewPage.php';
       AdminNewPage::render();
       break;
   ```
4. 创建模板 `templates/admin/new_page.php`
5. 在后台导航栏（`templates/admin/style.php`）添加链接

#### 2. 修改分享行为

**文件**: `app/Controllers/ShareController.php`
- `handle()` → 主入口，处理所有分享请求
- `showDocument()` → Markdown/HTML 文档渲染
- `downloadFile()` → 文件下载/预览

#### 3. 添加新的系统设置

**步骤：**
1. 在 `app/Models/Setting.php` 的 `install.sql` 中添加默认值
2. 在 `app/Controllers/Admin/Settings.php` 的 `settings()` 方法中添加保存/读取逻辑
3. 在 `templates/admin/settings.php` 中添加表单字段

#### 4. 数据库迁移

**文件**: `public/install/install.sql`
- 所有表结构在这
- 新版本升级时，创建 `update-x.x.x.sql` 并在 `install/index.php` 中添加版本检测

### 代码规范

| 项目 | 规范 |
|------|------|
| **行数限制** | 单个 PHP 文件 ≤400 行（上帝文件禁止）✅ 已实现 |
| **命名** | 类：PascalCase；方法/函数：camelCase；变量：snake_case |
| **数据库** | 表名复数（documents, files）；字段名 snake_case；主键 `id` INT AUTO_INCREMENT |
| **安全** | 所有 SQL 用 `prepare()` + 参数绑定；所有输出用 `htmlspecialchars()`；CSRF Token 验证 |
| **注释** | 关键逻辑添加注释；公共方法写 PHPDoc |

---

## ⚖️ 免责声明

本项目仅供学习交流和个人使用，请勿用于任何违法违规用途。
使用者应遵守所在地区的法律法规，自行承担使用本项目所产生的一切后果。
作者不对本项目的任何使用方式及结果承担责任。

---

## 📊 性能参考（基于当前服务器配置）

| 指标 | 估算值 |
|------|--------|
| 同时在线访问 | ~400-500 人 |
| 每分钟请求 | ~3,000-6,000 次 |
| 每小时请求 | ~180,000-360,000 次 |
| PHP-FPM 工作进程 | 50（可调整至 80-100） |

**优化建议**：分享链接启用 Nginx 缓存；PHP-FPM `max_children` 调至 100；MySQL `max_connections` 调至 200。

---

## 🔒 安全特性（v1.0.4）

✅ **CSRF Token 防护** — 所有后台 POST 操作验证 Token  
✅ **XSS 防护** — HTML 预览用 iframe sandbox + CSP header  
✅ **文件上传验证** — MIME 检测 + 扩展名白名单 + 图片二次验证  
✅ **路径遍历防护** — `realpath()` + 前缀校验  
✅ **会话安全** — HttpOnly + SameSite + 超时配置  
✅ **调试信息泄漏** — 已删除敏感 header  
✅ **分享密码传输** — POST 加密，不在 URL 暴露  
✅ **密码策略** — 强制 ≥8位 + 大小写 + 数字  
✅ **install.lock 加固** — JSON 完整性校验  

详见 [CHANGELOG.md](CHANGELOG.md) 查看完整更新历史。
