<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>安全设置 - MemoBox</title>
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
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
    <a href="/<?= Config::ADMIN_PATH ?>/security" class="active">安全</a>
</div>

<div class="container">
    <h2 style="font-size:20px;margin-bottom:20px;">🔒 安全设置</h2>
    
    <?php if (isset($success)): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:12px;margin-bottom:20px;color:#155724;font-size:14px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;padding:12px;margin-bottom:20px;color:#721c24;font-size:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- 文件上传白名单 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">📎 文件上传白名单</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <div class="form-group">
                <label style="font-weight:600;display:block;margin-bottom:8px;">允许上传的文件扩展名（逗号分隔）</label>
                <input type="text" name="allowed_exts" value="<?= htmlspecialchars($allowedExts ?? '') ?>" 
                       style="width:100%;padding:10px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;"
                       placeholder="jpg,png,gif,webp,zip,pdf">
                <p style="font-size:12px;color:#86868b;margin-top:4px;">当前服务器 PHP 配置：upload_max_filesize = <?= ini_get('upload_max_filesize') ?>，post_max_size = <?= ini_get('post_max_size') ?></p>
            </div>
            <button type="submit" name="action" value="save_exts" class="btn">保存扩展名</button>
        </form>
    </div>
    
    <!-- 会话安全配置 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:24px;">
        <h3 style="font-size:16px;margin-bottom:16px;">🔐 会话安全</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <div class="form-group">
                <label style="font-weight:600;display:block;margin-bottom:8px;">会话超时时间</label>
                <select name="session_timeout" style="width:100%;padding:10px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="3600" <?= ($sessionTimeout ?? 28800) == 3600 ? 'selected' : '' ?>>1小时（3600秒）</option>
                    <option value="7200" <?= ($sessionTimeout ?? 28800) == 7200 ? 'selected' : '' ?>>2小时（7200秒）</option>
                    <option value="14400" <?= ($sessionTimeout ?? 28800) == 14400 ? 'selected' : '' ?>>4小时（14400秒）</option>
                    <option value="28800" <?= ($sessionTimeout ?? 28800) == 28800 ? 'selected' : '' ?>>8小时（28800秒）</option>
                    <option value="43200" <?= ($sessionTimeout ?? 28800) == 43200 ? 'selected' : '' ?>>12小时（43200秒）</option>
                    <option value="86400" <?= ($sessionTimeout ?? 28800) == 86400 ? 'selected' : '' ?>>24小时（86400秒）</option>
                </select>
                <p style="font-size:12px;color:#86868b;margin-top:4px;">管理员超过此时间无操作将自动登出</p>
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="password_policy" value="1" <?= ($passwordPolicy ?? 1) ? 'checked' : '' ?>>
                    <span>强制密码复杂度（至少8位，包含大小写字母和数字）</span>
                </label>
            </div>
            <button type="submit" name="action" value="save_session" class="btn">保存会话设置</button>
        </form>
    </div>
    
    <!-- 安全状态总览 -->
    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <h3 style="font-size:16px;margin-bottom:16px;">✅ 安全状态总览</h3>
        <table style="width:100%;font-size:14px;border-collapse:collapse;">
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">CSRF 防护</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ 已启用</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">XSS 防护（html预览）</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ iframe sandbox</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">文件上传 MIME 验证</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ finfo 验证</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">路径遍历防护</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ realpath() 验证</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">会话安全配置</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ HttpOnly + SameSite</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">调试头信息泄露</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ 已删除</td>
            </tr>
            <tr style="border-bottom:1px solid #f5f5f7;">
                <td style="padding:10px 0;color:#86868b;">分享密码传输</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ POST 加密</td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:#86868b;">install.lock 加固</td>
                <td style="padding:10px 0;text-align:right;font-weight:600;color:#28a745;">✅ JSON 完整性验证</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
