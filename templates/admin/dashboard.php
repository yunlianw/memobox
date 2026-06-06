<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>仪表盘 - 私密知识库</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;color:#1d1d1f}
.header{background:#fff;padding:16px 24px;border-bottom:1px solid #f2f2f7;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.header h1{font-size:18px;font-weight:600}
.header .user{font-size:14px;color:#86868b}
.nav{background:#fff;padding:0 24px;border-bottom:1px solid #f2f2f7}
.nav a{display:inline-block;padding:12px 16px;font-size:14px;color:#86868b;text-decoration:none;border-bottom:2px solid transparent;transition:all .2s}
.nav a.active,.nav a:hover{color:#007aff;border-bottom-color:#007aff}
.container{max-width:1200px;margin:0 auto;padding:24px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px}
.stat-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.stat-card .num{font-size:32px;font-weight:700;color:#1d1d1f;margin-bottom:4px}
.stat-card .label{font-size:13px;color:#86868b}
.recent{background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.recent h2{font-size:16px;font-weight:600;margin-bottom:16px}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:8px;font-size:13px;color:#86868b;font-weight:500;border-bottom:1px solid #f2f2f7}
td{padding:10px 8px;font-size:14px;border-bottom:1px solid #f2f2f7}
a.link{color:#007aff;text-decoration:none;font-size:13px}
a.link:hover{text-decoration:underline}
.empty{text-align:center;color:#86868b;padding:40px;font-size:14px}
</style>
</head>
<body>

<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user">
        <?= htmlspecialchars($_SESSION['user']['username']) ?>
        <a href="/<?= Config::ADMIN_PATH ?>/logout" style="margin-left:12px;color:#86868b;font-size:13px;">登出</a>
    </div>
</div>

<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard" class="active">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares">分享管理</a>
    <a href="/<?= Config::ADMIN_PATH ?>/logs">日志</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
    <a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
        <a href="/<?= Config::ADMIN_PATH ?>/account">账户</a>
</div>

<div class="container">
    <div class="stats">
        <div class="stat-card">
            <div class="num"><?= count($docs) ?></div>
            <div class="label">📝 文档总数</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= count($files) ?></div>
            <div class="label">📁 文件总数</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= count($shares) ?></div>
            <div class="label">🔗 分享链接</div>
        </div>
    </div>
    
    <div class="recent">
        <h2>📝 最近文档</h2>
        <?php if (empty($docs)): ?>
            <div class="empty">暂无文档，<a href="/<?= Config::ADMIN_PATH ?>/edit" class="link">立即创建</a></div>
        <?php else: ?>
            <table>
                <tr><th>标题</th><th>类型</th><th>更新时间</th><th>操作</th></tr>
                <?php foreach (array_slice($docs, 0, 5) as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['title']) ?></td>
                    <td><span style="font-size:12px;color:#86868b;"><?= $doc['content_type'] === 'link' ? '🔗 链接' : '📝 文档' ?></span></td>
                    <td><span style="font-size:13px;color:#86868b;"><?= date('m-d H:i', strtotime($doc['updated_at'])) ?></span></td>
                    <td><a href="/<?= Config::ADMIN_PATH ?>/edit/<?= $doc['id'] ?>" class="link">编辑</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- 系统信息 -->
    <div style="margin-top:24px;background:#fff;border-radius:12px;padding:16px 20px;box-shadow:0 1px 3px rgba(0,0,0,.04);display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#86868b;">
        <span>MemoBox <strong style="color:#1d1d1f;">v<?= Config::VERSION ?></strong></span>
        <span>PHP <?= PHP_VERSION ?></span>
    </div>

</div>

</body>
</html>
