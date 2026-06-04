<?php
/**
 * MemoBox 私密知识库系统 - 安装向导
 */

// 防止重复安装
if (file_exists(__DIR__ . '/../../install.lock')) {
    header('Location: /');
    exit;
}

// 【最高优先级】AJAX 数据库测试（不依赖 step 参数，放在最开头）
// 注意：第三步 POST 也带 db_host，但第三步有 step=3，所以这里要排除
// 只有 JS 的 fetch 请求触发（无 GET step 参数，因为 fetch 用 window.location.pathname）
// 表单提交（?step=3、?step=4）不会被拦截
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host']) && !isset($_GET['step'])) {
    header('Content-Type: application/json');
    try {
        $dsn = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            $_POST['db_host'], (int)$_POST['db_port'], $_POST['db_name']);
        $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        echo json_encode(['ok' => true, 'msg' => '数据库连接成功！']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// 动态获取服务器信息
$phpVersion = phpversion();
$extensions = ['PDO', 'pdo_mysql', 'session', 'mbstring', 'openssl', 'json', 'gd'];
$checkResult = [];
foreach ($extensions as $ext) {
    $checkResult[$ext] = extension_loaded($ext);
}

$storagePath = __DIR__ . '/../../storage/files/';
$publicPath = __DIR__ . '/..';
$dirsWritable = [
    'storage/files/' => is_writable($storagePath) || @mkdir($storagePath, 0755, true),
    'app/' => is_writable(__DIR__ . '/../../app/'),
    'public/' => is_writable($publicPath),
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemoBox 安装向导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #334155; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); width: 100%; max-width: 600px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; padding: 32px; text-align: center; }
        .header h1 { font-size: 22px; margin-bottom: 4px; }
        .header p { font-size: 13px; opacity: .85; }
        .steps { display: flex; padding: 0 32px; margin-top: -12px; position: relative; z-index: 1; }
        .step { flex: 1; text-align: center; font-size: 12px; color: #94a3b8; }
        .step .num { display: flex; width: 28px; height: 28px; border-radius: 50%; background: #e2e8f0; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; margin: 0 auto 4px; }
        .step.active .num { background: #6366f1; color: #fff; }
        .step.done .num { background: #22c55e; color: #fff; }
        .step.active { color: #6366f1; font-weight: 600; }
        .step.done { color: #22c55e; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 28px; border-radius: 8px; background: #6366f1; color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; border: none; cursor: pointer; }
        .btn:hover { background: #4f46e5; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-green { background: #22c55e; }
        .btn-green:hover { background: #16a34a; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: #475569; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color .15s; }
        .form-group input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
        .check-list { list-style: none; margin-bottom: 20px; }
        .check-list li { padding: 8px 0; display: flex; align-items: center; gap: 8px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        .check-list li:last-child { border: none; }
        .icon-ok { color: #22c55e; font-weight: bold; }
        .icon-fail { color: #ef4444; font-weight: bold; }
        .msg { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .msg-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .msg-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .msg-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .actions { margin-top: 24px; display: flex; gap: 12px; }
        .complete-box { text-align: center; padding: 20px 0; }
        .complete-box .big-icon { font-size: 48px; margin-bottom: 12px; }
        .complete-box h2 { color: #22c55e; margin-bottom: 8px; }
        .complete-box p { color: #64748b; margin-bottom: 8px; }
        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .info-card .label { font-size: 12px; color: #94a3b8; }
        .info-card .value { font-size: 15px; font-weight: 600; margin-top: 2px; word-break: break-all; }
        @media (max-width: 480px) { .header { padding: 24px 16px; } .body { padding: 24px 16px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 MemoBox 安装向导</h1>
        <p>私密知识库系统 — 版本 1.0.1</p>
    </div>

    <div class="steps">
        <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"><div class="num">1</div>环境检测</div>
        <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"><div class="num">2</div>数据库配置</div>
        <div class="step <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' ?>"><div class="num">3</div>管理员</div>
        <div class="step <?= $step >= 4 ? ($step >= 4 ? 'done' : 'active') : '' ?>"><div class="num">4</div>完成</div>
    </div>

    <div class="body">
        <?php if ($step === 1): ?>
        <h2>📋 环境检测</h2>
        <ul class="check-list">
            <li><span class="<?= version_compare($phpVersion, '8.0', '>=') ? 'icon-ok' : 'icon-fail' ?>">
                <?= version_compare($phpVersion, '8.0', '>=') ? '✓' : '✗' ?></span>
                PHP 版本 (>= 8.0)：<strong><?= $phpVersion ?></strong></li>
            <?php foreach ($checkResult as $ext => $ok): ?>
            <li><span class="<?= $ok ? 'icon-ok' : 'icon-fail' ?>"><?= $ok ? '✓' : '✗' ?></span>
                <?= $ext ?> 扩展：<strong><?= $ok ? '已加载' : '未加载' ?></strong></li>
            <?php endforeach; ?>
            <?php foreach ($dirsWritable as $name => $writable): ?>
            <li><span class="<?= $writable ? 'icon-ok' : 'icon-fail' ?>"><?= $writable ? '✓' : '✗' ?></span>
                <?= $name ?> 目录权限：<strong><?= $writable ? '可写' : '不可写' ?></strong></li>
            <?php endforeach; ?>
        </ul>

        <?php
        $allOk = version_compare($phpVersion, '8.0', '>=') && !in_array(false, $checkResult) && !in_array(false, $dirsWritable);
        ?>
        <div class="msg msg-<?= $allOk ? 'ok' : 'err' ?>">
            <?= $allOk ? '✅ 环境检查通过，可以继续安装。' : '❌ 存在未通过项，请修复后刷新页面。' ?>
        </div>
        <div class="actions">
            <?php if ($allOk): ?>
            <a href="?step=2" class="btn">下一步 →</a>
            <?php endif; ?>
        </div>

        <?php elseif ($step === 2): ?>
        <h2>🔌 数据库配置</h2>
        <p style="color:#64748b;font-size:13px;margin-bottom:20px">请提前创建好数据库，填写连接信息后点击「测试连接」。</p>

        <form method="post" action="?step=3" id="dbForm">
            <div class="form-group">
                <label>数据库主机</label>
                <input type="text" name="db_host" value="127.0.0.1" required>
            </div>
            <div class="form-group">
                <label>端口</label>
                <input type="number" name="db_port" value="3306" required>
            </div>
            <div class="form-group">
                <label>数据库名</label>
                <input type="text" name="db_name" placeholder="例如 memobox" required>
            </div>
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="db_user" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="db_pass">
            </div>

            <div id="testMsg"></div>
            <div class="actions">
                <button type="button" class="btn" onclick="testDB()" id="testBtn">🔄 测试连接</button>
                <button type="submit" class="btn btn-green" id="nextBtn" disabled>下一步 →</button>
            </div>
        </form>

        <script>
        function testDB() {
            var btn = document.getElementById('testBtn');
            var msg = document.getElementById('testMsg');
            var next = document.getElementById('nextBtn');
            btn.disabled = true;
            btn.textContent = '检测中...';
            msg.innerHTML = '<div class="msg msg-info">正在连接...</div>';

            var data = new URLSearchParams();
            data.append('db_host', document.querySelector('[name="db_host"]').value);
            data.append('db_port', document.querySelector('[name="db_port"]').value);
            data.append('db_name', document.querySelector('[name="db_name"]').value);
            data.append('db_user', document.querySelector('[name="db_user"]').value);
            data.append('db_pass', document.querySelector('[name="db_pass"]').value);

            fetch(window.location.pathname, { method:'POST', body:data })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.ok) {
                    msg.innerHTML = '<div class="msg msg-ok">✅ ' + res.msg + '</div>';
                    next.disabled = false;
                } else {
                    msg.innerHTML = '<div class="msg msg-err">❌ ' + res.msg + '</div>';
                    next.disabled = true;
                }
            })
            .catch(function() {
                msg.innerHTML = '<div class="msg msg-err">❌ 请求失败，请检查网络</div>';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = '🔄 测试连接';
            });
        }
        </script>

        <?php elseif ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <h2>👤 创建管理员</h2>
        <form method="post" action="?step=4" id="adminForm">
            <input type="hidden" name="db_host" value="<?= htmlspecialchars($_POST['db_host']) ?>">
            <input type="hidden" name="db_port" value="<?= htmlspecialchars($_POST['db_port']) ?>">
            <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name']) ?>">
            <input type="hidden" name="db_user" value="<?= htmlspecialchars($_POST['db_user']) ?>">
            <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass']) ?>">

            <div class="form-group">
                <label>管理员用户名</label>
                <input type="text" name="admin_user" value="admin" required>
            </div>
            <div class="form-group">
                <label>管理员密码（至少 6 位）</label>
                <input type="text" name="admin_pass" value="" required minlength="6" placeholder="输入管理员密码">
            </div>
            <div class="form-group">
                <label>后台目录名（自定义）</label>
                <input type="text" name="admin_dir" value="admin" required>
                <p style="font-size:12px;color:#94a3b8;margin-top:4px">建议修改为不易猜测的名称，例如 admin、manage 等</p>
            </div>

            <div class="actions">
                <a href="?step=2" class="btn" style="background:#94a3b8">← 上一步</a>
                <button type="submit" class="btn btn-green">安装 →</button>
            </div>
        </form>

        <?php elseif ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php
        $error = null;
        $adminUser = $_POST['admin_user'];
        $adminPass = $_POST['admin_pass'];
        $adminDir = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['admin_dir']);
        if (empty($adminDir)) $adminDir = 'admin';

        try {
            // 1. 生成 Config.php
            $configTemplate = file_get_contents(__DIR__ . '/../../app/Config.example.php');
            if (!$configTemplate) throw new Exception('找不到配置模板 Config.example.php');

            $configContent = strtr($configTemplate, [
                "'DB_NAME' => 'memobox'" => "'DB_NAME' => '" . addslashes($_POST['db_name']) . "'",
                "'DB_USER' => 'memobox_user'" => "'DB_USER' => '" . addslashes($_POST['db_user']) . "'",
                "'DB_PASS' => 'your_password_here'" => "'DB_PASS' => '" . addslashes($_POST['db_pass']) . "'",
                "'DB_HOST' => '127.0.0.1'" => "'DB_HOST' => '" . addslashes($_POST['db_host']) . "'",
                "'DB_PORT' => 3306" => "'DB_PORT' => " . (int)$_POST['db_port'],
                "'ADMIN_PATH' => 'yunlian'" => "'ADMIN_PATH' => '" . $adminDir . "'",
            ]);

            file_put_contents(__DIR__ . '/../../app/Config.php', $configContent);

            // 2. 连接数据库执行 SQL
            require_once __DIR__ . '/../../app/Config.php';
            $pdo = Config::getDB();

            $sql = file_get_contents(__DIR__ . '/install.sql');
            $lines = explode("\n", $sql);
            $buffer = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0) continue;
                $buffer .= $line . "\n";
                if (strpos($line, ';') !== false) {
                    try {
                        $pdo->exec(trim($buffer));
                    } catch (Exception $e) {
                        if (stripos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                    $buffer = '';
                }
            }

            // 3. 创建管理员
            $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$adminUser, $passwordHash]);

            // 4. 生成 install.lock
            $lockData = json_encode([
                'installed_at' => date('Y-m-d H:i:s'),
                'admin_dir' => $adminDir,
                'version' => '1.0.1',
            ], JSON_UNESCAPED_UNICODE);
            file_put_contents(__DIR__ . '/../../install.lock', $lockData);

            // 5. 创建存储目录
            $storageDir = __DIR__ . '/../../storage/files/';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        ?>

        <?php if ($error): ?>
            <div class="msg msg-err">❌ 安装失败：<?= htmlspecialchars($error) ?></div>
            <div class="actions">
                <a href="?step=3" class="btn">← 重试</a>
            </div>
        <?php else: ?>
            <div class="complete-box">
                <div class="big-icon">🎉</div>
                <h2>安装完成！</h2>
                <p>MemoBox 已成功安装部署。</p>

                <div class="info-card">
                    <div class="label">后台地址</div>
                    <div class="value"><a href="/<?= $adminDir ?>/" target="_blank">https://你的域名/<?= $adminDir ?>/</a></div>
                </div>
                <div class="info-card">
                    <div class="label">管理员账号</div>
                    <div class="value"><?= htmlspecialchars($adminUser) ?></div>
                </div>
                <div class="info-card">
                    <div class="label">管理员密码</div>
                    <div class="value"><?= htmlspecialchars($adminPass) ?></div>
                </div>

                <div class="msg msg-info">⚠️ 请妥善保管以上信息。首次登录后建议立即修改密码。</div>

                <div class="actions" style="justify-content:center">
                    <a href="/<?= $adminDir ?>/" class="btn">👉 进入后台</a>
                </div>
            </div>
        <?php endif; ?>

        <?php else: ?>
        <?php header('Location: ?step=1'); exit; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
