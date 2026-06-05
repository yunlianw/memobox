<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账户设置 - MemoBox</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f5f5f7; color: #1d1d1f; min-height: 100vh; }
        .header { background: #fff; padding: 16px 24px; border-bottom: 1px solid #d2d2d7; display: flex; align-items: center; justify-content: space-between; }
        .header h1 { font-size: 18px; font-weight: 600; }
        .nav { background: #fff; padding: 0 24px; border-bottom: 1px solid #f2f2f7; }
        .nav a { display: inline-block; padding: 12px 16px; font-size: 14px; color: #86868b; text-decoration: none; border-bottom: 2px solid transparent; transition: all .2s; }
        .nav a.active, .nav a:hover { color: #007aff; border-bottom-color: #007aff; }
        .container { max-width: 800px; margin: 24px auto; padding: 0 24px; }
        .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; }
        h2 { font-size: 20px; margin-bottom: 20px; }
        h3 { font-size: 16px; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; color: #86868b; margin-bottom: 6px; }
        input[type="text"], input[type="password"] { width: 100%; max-width: 400px; padding: 10px 14px; border: 1px solid #d2d2d7; border-radius: 10px; font-size: 15px; outline: none; transition: border-color .2s; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #007aff; }
        input:disabled { background: #f5f5f7; color: #86868b; }
        .btn { background: #007aff; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-size: 15px; cursor: pointer; transition: background .2s; }
        .btn:hover { background: #0056cc; }
        .success { background: #f0fff4; border: 1px solid #b7ebc6; border-radius: 8px; padding: 10px; margin-bottom: 16px; font-size: 13px; color: #389e0d; text-align: center; }
        .error { background: #fff2f0; border: 1px solid #ffccc7; border-radius: 8px; padding: 10px; margin-bottom: 16px; font-size: 13px; color: #cf1322; text-align: center; }
        .tip { font-size: 12px; color: #86868b; margin-top: 4px; }
        @media (max-width: 768px) {
            .header { padding: 12px 16px; }
            .nav { padding: 0 12px; }
            .nav a { padding: 10px 10px; font-size: 13px; }
            .container { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 MemoBox</h1>
        <div>
            <a href="/<?= Config::ADMIN_PATH ?>/dashboard" style="font-size:14px;color:#007aff;text-decoration:none;">返回仪表盘 →</a>
        </div>
    </div>
    
    <div class="nav">
        <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
        <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
        <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
        <a href="/<?= Config::ADMIN_PATH ?>/shares">分享管理</a>
        <a href="/<?= Config::ADMIN_PATH ?>/logs">日志</a>
        <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
        <a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
        <a href="/<?= Config::ADMIN_PATH ?>/account" class="active">账户</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h3>👤 账户设置</h3>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
                
                <div class="form-group">
                    <label>当前用户名</label>
                    <input type="text" value="<?= htmlspecialchars($_SESSION['user']['username']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>新用户名（留空则不修改）</label>
                    <input type="text" name="new_username" minlength="3" maxlength="30" style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                </div>
                
                <div class="form-group">
                    <label>新密码（留空则不修改）</label>
                    <input type="password" name="new_password" minlength="<?= $passwordPolicy ? 8 : 6 ?>" style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                    <p class="tip"><?= $passwordPolicy ? '至少8位，必须包含大小写字母和数字（例如：Abc12345）' : '至少6位' ?></p>
                </div>
                
                <div class="form-group">
                    <label>确认新密码（留空则不修改）</label>
                    <input type="password" name="confirm_password" minlength="<?= $passwordPolicy ? 8 : 6 ?>" style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                    <p class="tip">必须与上面输入的新密码一致</p>
                </div>
                
                <div class="form-group">
                    <label>当前密码（必须填写以验证身份）</label>
                    <input type="password" name="current_password" required style="width:100%;max-width:400px;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
                    <p class="tip">修改用户名或密码前，必须验证当前密码</p>
                </div>
                
                <button type="submit" class="btn">保存修改</button>
            </form>
        </div>
    </div>
</body>
</html>