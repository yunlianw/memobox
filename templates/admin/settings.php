<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>设置 - MemoBox</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 MemoBox</h1>
    <div class="user"><?= htmlspecialchars($_SESSION['user']['username']) ?> <a href="/<?= Config::ADMIN_PATH ?>/logout" style="margin-left:12px;color:#86868b;font-size:13px;">登出</a></div>
</div>
<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares">分享管理</a>
    <a href="/<?= Config::ADMIN_PATH ?>/logs">日志</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings" class="active">设置</a>
<a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
    <a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
    </div>

<div class="container">
    <h2 style="font-size:20px;margin-bottom:20px;">⚙️ 系统设置</h2>
    
    <?php if (isset($success)): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:12px;margin-bottom:20px;color:#155724;font-size:14px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;padding:12px;margin-bottom:20px;color:#721c24;font-size:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- 全站伪装控制中心 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">🎭 全站伪装控制中心</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <!-- 首页伪装 -->
            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #f5f5f7;">
                <label style="font-weight:600;display:block;margin-bottom:8px;">首页（<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') ?>/）伪装切换</label>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="homepage_mode" value="404" <?= ($homepageMode ?? '404') === '404' ? 'checked' : '' ?>>
                        <span>🔒 选项A：默认 404 彻底装死</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="homepage_mode" value="custom" <?= ($homepageMode ?? '') === 'custom' ? 'checked' : '' ?> onchange="toggleHomepageCustom(this)">
                        <span>✏️ 选项B：自定义 HTML 首页</span>
                    </label>
                </div>
                <div id="homepage-custom" style="margin-top:12px;display:<?= ($homepageMode ?? '') === 'custom' ? 'block' : 'none' ?>;">
                    <textarea name="homepage_html" style="width:100%;height:200px;padding:12px;border:1px solid #d2d2d7;border-radius:8px;font-family:'Courier New',monospace;font-size:13px;" placeholder="粘贴任意纯 HTML 源码..."><?= htmlspecialchars($homepageHtml ?? '') ?></textarea>
                    <p style="font-size:12px;color:#86868b;margin-top:4px;">支持完整 HTML、CSS、JS，外人访问首页直接输出这段内容</p>
                </div>
            </div>
            
            <!-- 分享错误伪装 -->
            <div style="margin-bottom:20px;">
                <label style="font-weight:600;display:block;margin-bottom:8px;">失效/已删除分享页（/?action=share&token=xxx）伪装切换</label>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="share_error_mode" value="default" <?= ($shareErrorMode ?? 'default') === 'default' ? 'checked' : '' ?>>
                        <span>🔒 选项A：默认 404 彻底装死</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="share_error_mode" value="custom" <?= ($shareErrorMode ?? '') === 'custom' ? 'checked' : '' ?> onchange="toggleShareCustom(this)">
                        <span>✏️ 选项B：自定义 HTML 提示页</span>
                    </label>
                </div>
                <div id="share-custom" style="margin-top:12px;display:<?= ($shareErrorMode ?? '') === 'custom' ? 'block' : 'none' ?>;">
                    <textarea name="share_error_html" style="width:100%;height:200px;padding:12px;border:1px solid #d2d2d7;border-radius:8px;font-family:'Courier New',monospace;font-size:13px;" placeholder="粘贴自定义 HTML 提示页源码..."><?= htmlspecialchars($shareErrorHtml ?? '') ?></textarea>
                    <p style="font-size:12px;color:#86868b;margin-top:4px;">统一处理：已烧毁 / 已过期 / 已被彻底删除 / 不存在的 Token</p>
                </div>
            </div>
            
            <button type="submit" class="btn">保存伪装设置</button>
        </form>
    </div>
    
    <!-- 分享域名设置 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">🔗 分享域名设置</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <div class="form-group">
                <label style="font-weight:600;display:block;margin-bottom:8px;">分享域名（留空则自动检测）</label>
                <input type="text" name="share_domain" value="<?= htmlspecialchars($shareDomain ?? '') ?>" placeholder="例如：share.example.com" style="width:100%;padding:10px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                <p style="font-size:12px;color:#86868b;margin-top:4px;">设置后，所有分享链接将使用此域名。需先将域名解析到服务器，支持隐藏真实平台地址。</p>
            </div>
            <button type="submit" class="btn">保存分享域名</button>
        </form>
    </div>
    
    <!-- 账户设置 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">👤 账户设置</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <div class="form-group">
                <label>当前用户名</label>
                <input type="text" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>" disabled style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;background:#f5f5f7;">
            </div>
            <div class="form-group">
                <label>新用户名（留空则不修改）</label>
                <input type="text" name="new_username" minlength="3" maxlength="30" style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
            </div>
            <div class="form-group">
                <label>新密码（留空则不修改）</label>
                <input type="password" name="new_password" minlength="6" style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                <p style="font-size:12px;color:#86868b;margin-top:4px;">至少6位字符</p>
            </div>
            <div class="form-group">
                <label>当前密码（必须填写以验证身份）</label>
                <input type="password" name="current_password" required style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                <p style="font-size:12px;color:#86868b;margin-top:4px;">修改用户名或密码前，必须验证当前密码</p>
            </div>
            <button type="submit" class="btn">保存修改</button>
        </form>
    </div>
    
    <!-- 系统信息 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">ℹ️ 系统信息</h3>
        <table>
            <tr><td>PHP版本</td><td><?= phpversion() ?></td></tr>
            <tr><td>数据库</td><td>MySQL 5.7+</td></tr>
            <tr><td>存储目录</td><td><code style="font-size:12px;color:#86868b;">已隐藏</code></td></tr>
            <tr><td>后台路径</td><td><code style="font-size:12px;color:#86868b;">已隐藏</code></td></tr>
        </table>
        <div style="margin-top:16px;padding:12px;background:#fff3cd;border:1px solid #ffeeba;border-radius:8px;font-size:13px;color:#856404;">
            💡 <strong>上传大文件前，请确保服务器配置：</strong><br>
            1. PHP.ini 中 <code>upload_max_filesize</code> 和 <code>post_max_size</code> 调整为 100M+<br>
            2. Nginx 配置中 <code>client_max_body_size</code> 同步修改（宝塔面板 → 软件商店 → Nginx → 配置修改）<br>
            3. 系统已自动对 >5MB 文件启用切片上传，无需担心超时
        </div>
    </div>
    
    <!-- 备份与导出 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);border:2px solid #34c759;">
        <h3 style="font-size:16px;margin-bottom:8px;color:#34c759;">📦 备份与导出</h3>
        <p style="font-size:13px;color:#86868b;margin-bottom:16px;">一键打包数据库 SQL + 存储文件，5 分钟即可迁移至新服务器</p>
        <form method="POST" action="/<?= Config::ADMIN_PATH ?>
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">/settings" onsubmit="return confirm('确认开始备份？可能需要几秒钟...')">
            <input type="hidden" name="action" value="backup">
            <button type="submit" class="btn" style="background:#34c759;color:#fff;">⬇️ 生成并下载备份包</button>
            <p style="font-size:11px;color:#86868b;margin-top:6px;">包含：数据库完整 SQL + storage/files/ 目录所有文件</p>
        </form>
    </div>
</div>

<script>
function toggleHomepageCustom(radio) {
    document.getElementById('homepage-custom').style.display = radio.checked ? 'block' : 'none';
}
function toggleShareCustom(radio) {
    document.getElementById('share-custom').style.display = radio.checked ? 'block' : 'none';
}
</script>
</body>
</html>
