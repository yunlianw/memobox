<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>文件 - 私密知识库</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user"><?= htmlspecialchars($_SESSION['user']['username']) ?> <a href="/<?= Config::ADMIN_PATH ?>/logout" style="margin-left:12px;color:#86868b;font-size:13px;">登出</a></div>
</div>
<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files" class="active">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares">分享</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
</div>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <h2 style="font-size:18px;font-weight:600;">📁 文件管理</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="get" style="display:flex;gap:8px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="搜索文件名/类型..." style="padding:8px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:13px;width:200px;">
                    <button type="submit" class="btn-sm">🔍 搜索</button>
                    <?php if (!empty($search)): ?><a href="/<?= Config::ADMIN_PATH ?>/files" class="btn-sm" style="background:#86868b;">✖ 清除</a><?php endif; ?>
                </form>
                <button type="button" onclick="doBatchDelete()" class="btn-sm btn-danger" style="display:none;" id="batch-del-btn-2">🗑️ 批量删除</button>
                <a href="/<?= Config::ADMIN_PATH ?>/upload" class="btn-sm">⬆️ 上传文件</a>
            </div>
        </div>
        
        <?php if (empty($files)): ?>
            <div style="text-align:center;color:#86868b;padding:80px;font-size:14px;">
                <?php if (!empty($search)): ?>
                    未找到匹配「<?= htmlspecialchars($search) ?>」的文件，<a href="/<?= Config::ADMIN_PATH ?>/files">查看全部</a>
                <?php else: ?>
                    暂无文件，<a href="/<?= Config::ADMIN_PATH ?>/upload">立即上传</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="post" id="batch-form-2">
                <input type="hidden" name="delete_batch" value="1">
                <table>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" onchange="toggleAll(this, 'selected[]')"></th>
                        <th>文件名</th>
                        <th>类型</th>
                        <th>大小</th>
                        <th>上传时间</th>
                        <th>操作</th>
                    </tr>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td><input type="checkbox" name="selected[]" value="<?= $file['id'] ?>" onchange="updateBatchBtn('selected[]', 'batch-del-btn-2')"></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <?php 
                                $ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                                $icon = '📄';
                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $icon = '🖼️';
                                elseif (in_array($ext, ['mp4','avi','mov','mkv'])) $icon = '🎬';
                                elseif (in_array($ext, ['mp3','wav','ogg'])) $icon = '🎵';
                                elseif (in_array($ext, ['pdf'])) $icon = '📕';
                                elseif (in_array($ext, ['zip','rar','7z','tar','gz'])) $icon = '📦';
                                ?>
                                <span><?= $icon ?></span>
                                <span><?= htmlspecialchars($file['original_name']) ?></span>
                                <?php if ($file['is_image']): ?>
                                    <span style="font-size:11px;color:#34c759;">图片</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($file['mime_type']) ?></td>
                        <td>
                            <?php 
                            $size = $file['file_size'];
                            if ($size >= 1048576) echo round($size/1048576, 1) . ' MB';
                            elseif ($size >= 1024) echo round($size/1024, 1) . ' KB';
                            else echo $size . ' B';
                            ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="/<?= Config::ADMIN_PATH ?>/file?key=<?= $file['file_key'] ?>" target="_blank" class="btn-sm">👁️ 预览</a>
                                <a href="/<?= Config::ADMIN_PATH ?>/files?delete=<?= $file['id'] ?>" onclick="return confirm('确定要删除吗？')" class="btn-sm btn-danger">删除</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// 全选/取消
function toggleAll(master, name) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(cb => cb.checked = master.checked);
    updateBatchBtn(name, 'batch-del-btn-2');
}

// 更新批量删除按钮
function updateBatchBtn(name, btnId) {
    const checked = document.querySelectorAll('input[name="' + name + '"]:checked').length;
    const btn = document.getElementById(btnId);
    if (checked > 0) {
        btn.style.display = 'inline-block';
        btn.textContent = '🗑️ 批量删除 (' + checked + ')';
    } else {
        btn.style.display = 'none';
    }
}

// 批量删除
function doBatchDelete() {
    const checked = document.querySelectorAll('input[name="selected[]"]:checked');
    if (checked.length === 0) return;
    if (!confirm('确定要删除选中的 ' + checked.length + ' 个文件吗？')) return;
    document.getElementById('batch-form-2').submit();
}
</script>
</body>
</html>
