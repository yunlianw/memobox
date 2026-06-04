<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>日志 - MemoBox</title>
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
    <a href="/<?= Config::ADMIN_PATH ?>/logs" class="active">日志</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
<a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
    </div>

<div class="container">
    <h2 style="font-size:20px;margin-bottom:20px;">📋 审计日志</h2>
    
    <?php if (isset($_GET['cleared'])): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:12px;margin-bottom:20px;color:#155724;font-size:14px;">
        ✅ 已清空 <?= (int)$_GET['cleared'] ?> 条日志
    </div>
    <?php endif; ?>
    
    <!-- 日志类型切换 -->
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <a href="/<?= Config::ADMIN_PATH ?>/logs?type=admin" 
           style="padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none;<?= ($_GET['type'] ?? 'admin') === 'admin' ? 'background:#007aff;color:#fff;' : 'background:#f5f5f7;color:#1d1d1f;' ?>">
            👤 管理员日志
        </a>
        <a href="/<?= Config::ADMIN_PATH ?>/logs?type=access" 
           style="padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none;<?= ($_GET['type'] ?? '') === 'access' ? 'background:#007aff;color:#fff;' : 'background:#f5f5f7;color:#1d1d1f;' ?>">
            🌐 前台访问日志
        </a>
    </div>
    
    <!-- 清空日志 -->
    <div style="background:#fff;border-radius:12px;padding:16px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <form method="POST" action="/<?= Config::ADMIN_PATH ?>/logs" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="type" value="<?= htmlspecialchars($_GET['type'] ?? 'admin') ?>">
            <select name="clear_before" style="padding:6px 10px;border:1px solid #d2d2d7;border-radius:8px;font-size:13px;">
                    <option value="">清空全部</option>
                    <option value="<?= date('Y-m-d H:i:s', strtotime('-1 day')) ?>">清空1天前</option>
                    <option value="<?= date('Y-m-d H:i:s', strtotime('-7 day')) ?>">清空7天前</option>
                    <option value="<?= date('Y-m-d H:i:s', strtotime('-30 day')) ?>">清空30天前</option>
                </select>
                <button type="submit" class="btn-sm btn-danger" onclick="return confirm('确认清空日志？此操作不可恢复！')">🗑️ 清空日志</button>
            </form>
        </div>
        <div style="font-size:13px;color:#86868b;">
            共 <?= count($logs) ?> 条记录
        </div>
    </div>
    
    <!-- 日志列表 -->
    <div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.04);overflow-x:auto;">
        <?php if (empty($logs)): ?>
        <div style="text-align:center;color:#86868b;padding:40px;font-size:14px;">暂无日志</div>
        <?php else: ?>
        <table style="min-width:800px;">
            <tr>
                <th>时间</th>
                <th>类型</th>
                <th>动作</th>
                <th>目标</th>
                <th>详情</th>
                <th>IP地址</th>
            </tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><span class="muted"><?= date('m-d H:i', strtotime($log['created_at'])) ?></span></td>
                <td>
                    <?php if ($log['log_type'] === 'admin'): ?>
                        <span style="color:#007aff;">👤 管理员</span>
                    <?php else: ?>
                        <span style="color:#34c759;">🌐 前台</span>
                    <?php endif; ?>
                </td>
                <td><strong><?= htmlspecialchars($log['action']) ?></strong></td>
                <td>
                    <?php if ($log['target_type']): ?>
                        <?= htmlspecialchars($log['target_type']) ?>
                        <?php if ($log['target_id']): ?>(#<?= $log['target_id'] ?>)<?php endif; ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:13px;max-width:300px;word-break:break-all;"><?= htmlspecialchars($log['detail'] ?? '') ?></td>
                <td><code style="font-size:12px;"><?= htmlspecialchars($log['ip_address'] ?? '') ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
