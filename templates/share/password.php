<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>内容保护</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;display:flex;align-items:center;justify-content:center;color:#1d1d1f}
.box{background:#fff;border-radius:16px;padding:40px;width:380px;box-shadow:0 2px 20px rgba(0,0,0,.08);text-align:center}
.box h2{font-size:20px;margin-bottom:8px;font-weight:600}
.box p{color:#86868b;font-size:14px;margin-bottom:24px}
.box input{width:100%;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:18px;outline:none;text-align:center;letter-spacing:8px;margin-bottom:16px;transition:border-color .2s}
.box input:focus{border-color:#007aff;box-shadow:0 0 0 3px rgba(0,122,255,.15)}
.box .btn{width:100%;padding:12px;background:#007aff;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:500;cursor:pointer;transition:background .2s}
.box .btn:hover{background:#0063ce}
.error{color:#cf1322;font-size:13px;margin-bottom:12px;background:#fff2f0;padding:8px;border-radius:8px;border:1px solid #ffccc7}
</style>
</head>
<body>
<div class="box">
    <h2>🔒 该内容需要密码</h2>
    <p>请输入访问密码</p>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="GET" action="">
        <input type="hidden" name="action" value="share">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="text" name="password" placeholder="输入密码" maxlength="10" autofocus>
        <button type="submit" class="btn">验证</button>
    </form>
</div>
</body>
</html>
