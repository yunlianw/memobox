# MemoBox — 私密知识库系统

一款轻量级的私密知识库系统。基于 Markdown 编辑、文件管理、分享控制，适用于个人笔记、内部文档、私密文件分享等场景。

## ✨ 功能特性

- 📝 **Markdown 编辑** — SimpleMDE 编辑器，实时预览
- 🔗 **链接/HTML 模式** — 支持三种文档类型：Markdown、链接跳转、HTML 源码
- 📂 **文件管理** — 上传、预览、分类管理
- 🔒 **分享控制** — 密码保护、过期时间、点击次数熔断、阅后即焚
- 🕵️ **全场景伪装** — 首页伪装（404/自定义 HTML）、分享错误伪装（Nginx 404/自定义 HTML）
- 🛡️ **安全防护** — IP 封锁（连续失败登录）、自定义后台路径
- 📊 **访问统计** — 分享链接的访问日志记录

## 📁 目录结构

```
/
├── app/                    # 应用核心
│   ├── Config.php          # 数据库及系统配置（安装时生成）
│   ├── Config.example.php  # 配置模板
│   ├── Router.php          # 路由分发器
│   ├── Security.php        # 安全工具（哈希、密码验证、IP检查）
│   ├── Controllers/        # 控制器
│   │   ├── AdminController.php   # 后台管理
│   │   ├── AuthController.php    # 登录认证
│   │   └── ShareController.php   # 分享处理
│   ├── Models/             # 数据模型
│   └── Services/           # 第三方服务
├── public/                 # Web 公开目录（Nginx root）
│   ├── index.php           # 统一入口
│   └── install/            # 安装向导
├── storage/files/           # 文件存储（可配置路径）
├── templates/              # 视图模板
│   ├── admin/              # 后台页面
│   └── share/              # 分享页面
├── CHANGELOG.md            # 更新日志
├── README.md               # 本文档
└── LICENSE                 # 许可证
```

## 🚀 安装指南

### 环境要求

- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.3
- 扩展：PDO、pdo_mysql、mbstring、openssl、json

### 安装步骤

1. **下载源码**，上传到服务器 Web 目录
2. **创建数据库**（MySQL）
3. **访问域名**，自动跳转到安装页面
4. 按向导完成安装：环境检测 → 数据库配置 → 创建管理员 → 完成
5. 登录后台开始使用

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/memobox/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-85.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
    }
}
```

### 后台访问

安装时自定义的后台目录名（默认 `yunlian`），访问 `https://your-domain.com/你设置的后台目录/` 即可登录。

## 🔧 常见问题

**Q: 安装后无法访问后台？**
A: 检查 `install.lock` 是否存在于项目根目录，检查 `app/Config.php` 数据库配置是否正确。

**Q: 分享链接报 404？**
A: 检查分享是否过期或超过点击次数限制。后台「分享管理」页面可查看所有分享状态。

**Q: 上传文件不显示？**
A: 检查 `storage/files/` 目录是否可写（权限 755），PHP 的 `fileinfo` 扩展建议安装。

## ⚖️ 免责声明

本项目仅供学习交流和个人使用。使用者应遵守所在地区的法律法规，自行承担使用本项目的所有后果。
