<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>文档 - 私密知识库</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user"><?= htmlspecialchars($_SESSION['user']['username']) ?> <a href="/<?= Config::ADMIN_PATH ?>/logout" style="margin-left:12px;color:#86868b;font-size:13px;">登出</a></div>
</div>
<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents" class="active">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares">分享管理</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
<a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
    </div>
<div class="container">
    <!-- 搜索栏 + 操作区 -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap;">
        <form method="GET" action="" style="display:flex;gap:8px;align-items:center;flex:1;max-width:400px;">
            <input type="text" name="search" placeholder="搜索文档标题..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   style="flex:1;padding:8px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:13px;" onkeydown="if(event.key==='Enter') this.form.submit()">
            <button type="submit" style="padding:8px 14px;background:#f5f5f7;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;font-size:13px;">🔍</button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="/<?= Config::ADMIN_PATH ?>/documents" style="font-size:12px;color:#86868b;">清除</a>
            <?php endif; ?>
        </form>
        <div style="display:flex;gap:8px;">
            <a href="/<?= Config::ADMIN_PATH ?>/edit" class="btn">+ 新建文档</a>
        </div>
    </div>

    <form method="POST" action="" id="batch-form">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
        <?php if (empty($docs)): ?>
            <div style="text-align:center;color:#86868b;padding:80px;font-size:14px;background:#fff;border-radius:12px;">
                <?php if (!empty($_GET['search'])): ?>
                    未找到匹配的文档，<a href="/<?= Config::ADMIN_PATH ?>/documents" style="color:#007aff;">查看全部</a>
                <?php else: ?>
                    暂无文档，<a href="/<?= Config::ADMIN_PATH ?>/edit" style="color:#007aff;">创建第一个文档</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <button type="button" onclick="doBatchDelete('documents')" class="btn-sm btn-danger" style="display:none;" id="batch-del-btn-1">🗑️ 批量删除</button>
            </div>
            <table>
                <tr>
                    <th style="width:36px;"><input type="checkbox" onchange="toggleAll(this, 'selected[]')"></th>
                    <th>标题</th><th>类型</th><th>创建时间</th><th>更新时间</th><th>操作</th>
                </tr>
                <?php foreach ($docs as $doc): ?>
                <tr>
                    <td><input type="checkbox" name="selected[]" value="<?= $doc['id'] ?>" onchange="updateBatchBtn()"></td>
                    <td><?= htmlspecialchars($doc['title']) ?></td>
                    <td><span class="tag"><?= $doc['content_type'] === 'link' ? '🔗 链接' : '📝 文档' ?></span></td>
                    <td><span class="muted"><?= date('Y-m-d', strtotime($doc['created_at'])) ?></span></td>
                    <td><span class="muted"><?= date('Y-m-d', strtotime($doc['updated_at'])) ?></span></td>
                    <td>
                        <a href="/<?= Config::ADMIN_PATH ?>/preview/<?= $doc['id'] ?>" class="btn-sm" target="_blank">预览</a>
                        <a href="/<?= Config::ADMIN_PATH ?>/edit/<?= $doc['id'] ?>" class="btn-sm">编辑</a>
                        <a href="/<?= Config::ADMIN_PATH ?>/documents?delete=<?= $doc['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="delete_batch" value="1">
        <?php endif; ?>
    </form>
</div>

<script>
function toggleAll(master, name) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(cb => cb.checked = master.checked);
    updateBatchBtn();
}
function updateBatchBtn() {
    const checked = document.querySelectorAll('input[name="selected[]"]:checked').length;
    const btn = document.getElementById('batch-del-btn-1');
    if (checked > 0) {
        btn.style.display = 'inline-block';
        btn.textContent = '🗑️ 批量删除 (' + checked + ')';
    } else {
        btn.style.display = 'none';
    }
}
function doBatchDelete(action) {
    const checked = document.querySelectorAll('input[name="selected[]"]:checked');
    if (checked.length === 0) return;
    if (!confirm('确定要删除选中的 ' + checked.length + ' 项吗？')) return;
    document.getElementById('batch-form').submit();
}
</script>
</body>
</html>
