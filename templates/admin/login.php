<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>记事本 - 我的备忘录</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📝</text></svg>">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;display:flex;align-items:center;justify-content:center;color:#1d1d1f}
.login-box{background:#fff;border-radius:16px;padding:40px;width:360px;box-shadow:0 2px 20px rgba(0,0,0,.08)}
.login-box h1{font-size:24px;font-weight:600;margin-bottom:8px;text-align:center}
.login-box p.sub{color:#86868b;font-size:14px;text-align:center;margin-bottom:24px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;color:#86868b;margin-bottom:6px;font-weight:500}
.form-group input{width:100%;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;outline:none;transition:border-color .2s}
.form-group input:focus{border-color:#007aff;box-shadow:0 0 0 3px rgba(0,122,255,.15)}
.btn{width:100%;padding:12px;background:#007aff;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:500;cursor:pointer;transition:background .2s}
.btn:hover{background:#0063ce}
.btn:active{background:#004999}
.error{background:#fff2f0;border:1px solid #ffccc7;border-radius:8px;padding:10px;margin-bottom:16px;font-size:13px;color:#cf1322;text-align:center}
.info{background:#e6f7ff;border:1px solid #91d5ff;border-radius:8px;padding:10px;margin-bottom:16px;font-size:13px;color:#0050b3;text-align:center}
.copyright{text-align:center;font-size:12px;color:#c7c7cc;margin-top:20px}
</style>
</head>
<body>
<div class="login-box">
    <h1>📝 我的记事本</h1>
    <p class="sub">记录生活点滴，一个私密的备忘录</p>
    
    <?php if (isset($isFirstRun) && $isFirstRun): ?>
    <div class="info">
        📌 首次使用，请创建管理员账号<br>
        <small style="color:#666;">创建后即可开始使用记事本</small>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">用户名</label>
            <input type="text" id="username" name="username" placeholder="请输入用户名" required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">密码</label>
            <input type="password" id="password" name="password" placeholder="请输入密码" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn">
            <?= $isFirstRun ? '创建账号并登录' : '登录' ?>
        </button>
    </form>
    
    <?php if (!isset($isFirstRun) || !$isFirstRun): ?>
    <div class="copyright">© 个人记事本 v1.0</div>
    <?php endif; ?>
</div>
</body>
</html>
