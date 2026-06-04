# MemoBox — 私密知识库系统

一款轻量级私密知识库系统，支持 Markdown 写作、文件管理、加密分享。

## ✨ 功能特性

- 📝 Markdown 编辑器（SimpleMDE，实时预览）
- 🔗 三种文档类型：Markdown / 链接跳转 / HTML 源码
- 📂 文件上传、管理、预览
- 🔒 分享控制：密码保护、过期时间、点击熔断、阅后即焚
- 🕵️ 全场景伪装：首页伪装（404/自定义 HTML）、分享错误伪装
- 🛡️ 安全防护：IP 封锁、自定义后台路径
- 📊 访问日志统计

## 📁 目录结构

```
/
├── app/                    # 应用核心
│   ├── Config.php          # 数据库及系统配置（安装时生成）
│   ├── Config.example.php  # 配置模板
│   ├── Router.php          # 路由分发器
│   ├── Security.php        # 安全工具
│   ├── Controllers/        # 控制器
│   ├── Models/             # 数据模型
│   └── Services/           # 第三方服务
├── public/                 # Web 根目录（Nginx root 指向这里）
│   ├── index.php           # 统一入口
│   └── install/            # 安装向导
├── storage/files/           # 文件存储目录
├── templates/              # 视图模板
│   ├── admin/              # 后台页面
│   └── share/              # 分享页面
├── CHANGELOG.md            # 更新日志
├── README.md               # 本文档
└── LICENSE                 # 开源协议
```

## 🚀 安装教程

### 方式一：使用宝塔面板（推荐新手）

**第一步：创建站点并上传源码**
1. 宝塔 → 网站 → 添加站点（填你的域名）
2. 进入站点根目录，上传 `memobox-v1.0.1.tar.gz`
3. 解压，得到 `memobox/` 目录

**第二步：设置运行目录**
宝塔 → 网站 → 设置 → 运行目录 → 选择 `/public` → 保存

**第三步：设置伪静态**
宝塔 → 网站 → 设置 → 伪静态 → 粘贴以下内容 → 保存

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
# 上传 memobox-v1.0.1.tar.gz 后解压
tar xzf memobox-v1.0.1.tar.gz
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

## ⚖️ 免责声明

本项目仅供学习交流和个人使用，请勿用于任何违法违规用途。
使用者应遵守所在地区的法律法规，自行承担使用本项目所产生的一切后果。
作者不对本项目的任何使用方式及结果承担责任。
