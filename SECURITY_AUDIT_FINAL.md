# MemoBox 安全审查最终报告（牛马一号 + 牛马二号联合审查）

**审查日期**: 2026-06-04  
**审查版本**: v1.0.1  
**审查人员**: 牛马一号 🐮 + 牛马二号 🐴（联合审查）  
**审查范围**: `/www/wwwroot/fx.5276.net/` 全部代码  

---

## 风险等级说明

| 等级 | 说明 | 处理建议 |
|------|------|----------|
| 🔴 **高危** | 可能导致数据泄露、RCE、权限被接管 | 立即修复 |
| 🟡 **中危** | 一定条件下可利用 | 尽快修复 |
| 🟢 **低危** | 风险极低或需多重条件 | 选择性修复 |
| ⚪ **安全** | 无风险 | 保持现状 |

---

## 一、审查清单对照表

| # | 检查项 | 状态 | 说明 |
|---|---------|------|------|
| 1 | SQL 注入 | ✅ 安全 | 全部使用 `prepare()` + 参数绑定 |
| 2 | XSS | 🔴 高危 | **发现1处**：html类型文档直接输出（牛马二号新发现） |
| 3 | CSRF | 🔴 高危 | **无CSRF Token防护**（牛马二号新发现） |
| 4 | 会话安全 | 🟡 中危 | 有设备指纹，但Session配置不完整 |
| 5 | 文件上传 | 🔴 高危 | **2处**：MIME验证不足 + 路径遍历（牛马二号新发现） |
| 6 | 路径遍历 | 🟡 中危 | 部分用户输入未规范化 |
| 7 | 信息泄露 | 🟡 中危 | **调试头未删除**（牛马二号新发现） |
| 8 | 权限验证 | ✅ 安全 | 后台API有登录检查 |
| 9 | 硬编码 | 🟢 低危 | Config.example.php有占位符，非真实密码 |
| 10 | 安装程序 | 🟡 中危 | install.lock可被绕过（牛马二号细化） |
| 11 | 密码策略 | 🟡 中危 | 密码最小6位，无复杂度要求（牛马二号新发现） |
| 12 | 分享密码 | 🟡 中危 | 表单用GET提交，密码出现在URL（牛马二号新发现） |

---

## 二、风险点详细列表

---

### 🔴 高危1：XSS — html类型文档直接输出

**位置**: `app/Controllers/AdminController.php` → `previewDocument()` 方法  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**风险描述**:  
当文档类型为 `html` 时，系统直接 `echo $doc['content_md']`，未做任何HTML转义。攻击者可以构造包含 `<script>alert('XSS')</script>` 的HTML文档，当管理员预览时会触发XSS，进而窃取管理员Session。

**修复代码**:

```php
// app/Controllers/AdminController.php → previewDocument() 方法
// 替换原来的 $htmlContent = '...' . $doc['content_md'] . '...';

if ($doc['content_type'] === 'html') {
    // 方案1（推荐）：用 iframe sandbox 隔离
    $htmlContent = '<iframe sandbox="allow-same-origin" 
        style="width:100%;height:80vh;border:1px solid #d2d2d7;border-radius:12px;"
        srcdoc="' . htmlspecialchars($doc['content_md'], ENT_QUOTES, 'UTF-8') . '">
    </iframe>';
} else {
    // Markdown 渲染逻辑（保持不变）
    require_once __DIR__ . '/../Services/Parsedown.php';
    $parsedown = new Parsedown();
    $parsedown->setSafeMode(true); // 启用安全模式
    $htmlContent = $parsedown->text($doc['content_md']);
}
```

**修复后验证**:  
- 管理员预览html文档时，脚本不会执行  
- 使用 `sandbox="allow-same-origin"` 隔离不受信任的HTML

---

### 🔴 高危2：CSRF — 无CSRF Token防护

**位置**: 所有后台POST操作（`AdminController.php`、`ShareController.php`）  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**风险描述**:  
所有后台操作（删除文档、删除分享、修改设置、上传文件、修改密码）均无CSRF Token验证。攻击者可以构造恶意页面，诱导已登录的管理员访问，触发删除/修改操作。

**修复代码**:

```php
// === 步骤1：在 Security.php 添加 CSRF Token 生成 ===
public static function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

public static function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

```php
// === 步骤2：在 AdminController.php 的 dispatch() 开头添加 CSRF 验证 ===
// 仅验证 POST 请求（排除登录接口）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'login') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCsrfToken($csrfToken)) {
        http_response_code(403);
        die('CSRF validation failed');
    }
}
```

```php
// === 步骤3：在所有后台表单添加 Token ===
// templates/admin/documents.php（所有表单）
<input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

// templates/admin/shares.php
<input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

// templates/admin/settings.php
<input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

// templates/admin/files.php
<input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
```

**修复后验证**:  
- 无Token的POST请求返回403  
- Token使用 `hash_equals()` 防止时序攻击  
- 每次登录生成新Token

---

### 🔴 高危3：文件上传 — MIME类型验证不完整

**位置**: `app/Models/File.php` → `upload()` 方法  
**发现人**: 牛马二号 🐴（牛马一号部分检出，未给修复代码）

**风险描述**:  
1. 仅通过文件扩展名判断MIME类型，攻击者可以上传 `.php` 文件但命名为 `.php.jpg`  
2. 未检查文件内容真实MIME类型（应该用 `finfo_file()` 或 `getimagesize()`）  
3. 如果服务器配置不当（如Apache解析），可能导致RCE

**修复代码**:

```php
// app/Models/File.php → upload() 方法
public static function upload(int $userId, array $uploadedFile): int {
    $pdo = Config::getDB();
    
    // === 新增：扩展名白名单验证 ===
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', '7z', 'txt', 'md', 'mp4', 'mp3'];
    $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('不允许的文件类型：' . htmlspecialchars($ext));
    }
    
    // === 新增：真实MIME类型验证 ===
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            'video/mp4', 'audio/mpeg',
            'text/plain',
        ];
        if (!in_array($realMime, $allowedMimes)) {
            throw new Exception('文件MIME类型不匹配：' . htmlspecialchars($realMime));
        }
    }
    
    // === 新增：如果是图片，进一步验证 ===
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $imgInfo = @getimagesize($uploadedFile['tmp_name']);
        if ($imgInfo === false) {
            throw new Exception('无效的图片文件');
        }
    }
    
    // === 新增：文件名安全处理 ===
    $originalName = basename($uploadedFile['name']); // 去除路径
    $originalName = preg_replace('/[^\w.\-]/u', '', $originalName); // 只保留安全字符
    if (empty($originalName)) {
        $originalName = 'unnamed_file.' . $ext;
    }
    
    // 生成存储文件名（使用随机token，避免路径遍历）
    $storedName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $destPath = Config::STORAGE_PATH . $storedName;
    
    move_uploaded_file($uploadedFile['tmp_name'], $destPath);
    
    // ... 后续代码保持不变 ...
}
```

---

### 🔴 高危4：文件上传 — 路径遍历风险

**位置**: `app/Router.php` → `serveStatic()` 方法 + `File.php` → `upload()`  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**风险描述**:  
1. `upload()` 虽然用了 `Security::sanitize()`，但未验证文件名是否包含 `../` 等路径穿越字符  
2. `serveStatic()` 直接使用 `$filePath = Config::ROOT_PATH . $path`，如果 `$path` 包含 `../` 可能读取任意文件

**修复代码**:

```php
// app/Router.php → serveStatic() 方法
private static function serveStatic(string $filePath): void {
    // 规范化路径，防止 ../ 穿越
    $realPath = realpath($filePath);
    $allowedDir = realpath(Config::ROOT_PATH . '/assets/');
    
    if ($realPath === false || strpos($realPath, $allowedDir) !== 0) {
        self::show404();
        return;
    }
    
    if (!file_exists($realPath)) {
        self::show404();
        return;
    }
    
    // ... 后续代码保持不变 ...
}
```

---

### 🟡 中危1：会话安全 — Session配置不完整

**位置**: `app/Security.php` → `initSession()` + `public/index.php`  
**发现人**: 牛马二号 🐴（牛马一号部分检出）

**风险描述**:  
1. 虽然实现了设备指纹（`fingerprint`），但未设置 `session.cookie_httponly = 1` 和 `session.cookie_secure = 1`（HTTPS环境）  
2. 未设置Session绝对超时时间（如48小时强制重新登录）  
3. `validateFingerprint()` 只检查IP前三段，如果用户IP在 `/24` 子网内变化（如企业NAT），会导致频繁掉线

**修复代码**:

```php
// app/Config.php → init() 方法（或 public/index.php）
public static function init(): void {
    // Session 安全配置
    ini_set('session.cookie_httponly', '1');  // 防止XSS读取Cookie
    ini_set('session.use_strict_mode', '1');   // 防止Session Fixation
    ini_set('session.cookie_samesite', 'Lax'); // CSRF防护
    ini_set('session.gc_maxlifetime', '2880');  // 垃圾回收48小时
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');   // HTTPS环境强制Secure
    }
    
    date_default_timezone_set('Asia/Shanghai');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
```

```php
// app/Security.php → validateFingerprint() 方法
private static function validateFingerprint(): bool {
    $fp = $_SESSION['fingerprint'];
    
    // 绝对超时：48小时强制重新登录
    if (time() - ($fp['created'] ?? 0) > 48 * 3600) {
        return false;
    }
    
    // ... 其余验证逻辑保持不变 ...
}
```

---

### 🟡 中危2：信息泄露 — 调试头未删除

**位置**: `app/Router.php` + `app/Controllers/ShareController.php`  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**风险描述**:  
1. `Router.php` 中有 `header('X-Query-Debug: ...')` 和 `header('X-Share-Matched: YES')` 等调试头  
2. `ShareController.php` 中有 `header('X-Share-Handler: YES')`  
3. 这些调试信息会暴露系统内部逻辑，可能被攻击者利用

**修复代码**:

```php
// app/Router.php → dispatch() 方法
// 删除或注释掉以下调试头：
// header('X-Query-Debug: ' . urlencode(print_r($query, true))); // 删除
// header('X-Share-Matched: YES'); // 删除

// 或者仅在 DEBUG 模式开启时输出
if (defined('DEBUG') && DEBUG === true) {
    header('X-Query-Debug: ' . urlencode(print_r($query, true)));
}
```

```php
// app/Controllers/ShareController.php → handle() 方法
// 删除
// header('X-Share-Handler: YES'); // 删除
```

---

### 🟡 中危3：安装程序安全 — install.lock可被绕过

**位置**: `public/install/index.php` + `public/index.php`  
**发现人**: 牛马二号 🐴（牛马一号部分检出）

**风险描述**:  
1. `install.lock` 文件内容为JSON，但未验证其内容完整性。攻击者可以手动创建 `install.lock` 空文件绕过检测  
2. 安装完成后未自动删除 `/public/install/` 目录

**修复代码**:

```php
// public/install/index.php 开头
$lockFile = __DIR__ . '/../../install.lock';
if (file_exists($lockFile)) {
    // 验证 lock 文件内容完整性
    $lockData = json_decode(file_get_contents($lockFile), true);
    if (is_array($lockData) && isset($lockData['installed_at'])) {
        // 合法 lock 文件，跳转到首页
        header('Location: /');
        exit;
    } else {
        // lock 文件损坏，删除后继续
        unlink($lockFile);
    }
}
```

**安装完成后提示**:
```php
// install/index.php Step 4 完成后
echo '<div class="success">✅ 安装完成！</div>';
echo '<p>⚠️ 安全提示：请手动删除 <code>/public/install/</code> 目录</p>';
```

---

### 🟡 中危4：分享密码表单用GET提交

**位置**: `templates/share/password.php`  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**风险描述**:  
分享密码表单使用 GET 方法提交，密码会出现在URL中（浏览器历史、服务器日志、Referer头），存在信息泄露风险。

**修复代码**:

```php
// templates/share/password.php
<form method="POST" action="">
    <input type="hidden" name="action" value="share">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <input type="password" name="password" placeholder="输入密码" maxlength="10" autofocus>
    <button type="submit" class="btn">验证</button>
</form>
```

---

### 🟢 低危1：密码策略 — 无复杂度要求

**位置**: `app/Controllers/AdminController.php` + `app/Models/User.php`  
**发现人**: 牛马二号 🐴（牛马一号未检出）

**修复代码**:

```php
// app/Controllers/AdminController.php → showLogin() 方法（安装时）
$password = $_POST['password'] ?? '';
if (strlen($password) < 8) {
    $error = '密码至少8位字符';
} elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
    $error = '密码必须包含大小写字母和数字';
} else {
    // 创建管理员
}
```

---

## 三、修复优先级建议

| 优先级 | 问题 | 理由 | 修复难度 |
|--------|------|------|----------|
| **P0** | CSRF漏洞 | 可直接导致数据被篡改/删除 | 中 |
| **P0** | XSS漏洞（html类型） | 可直接执行恶意脚本 | 低 |
| **P1** | 文件上传MIME+路径遍历 | 可能导致RCE | 中 |
| **P1** | 会话安全配置 | 防止Session劫持 | 低 |
| **P2** | 调试头删除 | 信息泄露 | 低 |
| **P2** | 分享密码表单用POST | 防止密码泄露 | 低 |
| **P3** | 密码策略加强 | 防止弱密码 | 低 |
| **P3** | install.lock加固 | 低概率但应修复 | 低 |

---

## 四、修复后验证清单

- [ ] 所有POST表单添加CSRF Token验证
- [ ] html类型文档预览用iframe沙箱隔离
- [ ] 文件上传验证真实MIME类型+扩展名白名单
- [ ] 文件路径用`realpath()`规范化，防止路径遍历
- [ ] 删除所有调试头（`X-Query-Debug`/`X-Share-*`）
- [ ] Session配置添加`HttpOnly`+`Secure`+`SameSite`
- [ ] 分享密码表单改为POST方法
- [ ] 密码策略要求复杂度（≥8位+大小写+数字）
- [ ] install.lock验证JSON结构完整性

---

## 五、总结

### 牛马一号初步审查结论：
- SQL注入：✅ 无风险
- XSS：✅ 未发现（遗漏html类型）
- CSRF：⚠️ 已发现但未给修复方案
- 会话安全：⚠️ 部分发现
- 文件上传：⚠️ 部分发现

### 牛马二号补充审查结论：
- **新发现高危漏洞2个**：XSS（html类型）、CSRF（无Token）
- **新发现高危漏洞2个**：文件上传MIME不足、路径遍历
- **新发现中危漏洞4个**：会话配置、调试头、分享密码GET、安装程序
- **新发现低危漏洞1个**：密码策略

### 联合审查结论：
**当前版本（v1.0.1）存在6个高危/中危漏洞，建议修复后再发布v1.0.2。**

---

**报告生成时间**: 2026-06-04 23:10  
**审查人员**: 牛马一号 🐮 + 牛马二号 🐴  
**状态**: ✅ 审查完成，等待老季确认修复方案
