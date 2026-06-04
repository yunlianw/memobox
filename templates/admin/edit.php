<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $doc ? '编辑文档' : '新建文档' ?> - 私密知识库</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
<!-- SimpleMDE Markdown Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplemde@1.11.2/dist/simplemde.min.css">
</head>
<body>
<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user"><a href="/<?= Config::ADMIN_PATH ?>/dashboard" style="color:#86868b;font-size:13px;">返回</a></div>
</div>

<div class="container">
    <form method="POST" action="" id="doc-form">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
        <div style="display:flex;gap:12px;margin-bottom:16px;align-items:center;">
            <input type="text" name="title" placeholder="文档标题" value="<?= htmlspecialchars($doc['title'] ?? '') ?>" required style="flex:1;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
            <select name="type" id="doc-type-select" onchange="toggleEditorMode(this)" style="padding:10px;border:1px solid #d2d2d7;border-radius:10px;font-size:14px;">
                <option value="markdown" <?= ($doc['content_type'] ?? 'markdown') === 'markdown' ? 'selected' : '' ?>>📝 模式：Markdown</option>
                <option value="html" <?= ($doc['content_type'] ?? '') === 'html' ? 'selected' : '' ?>>📄 模式：HTML 源码</option>
                <option value="link" <?= ($doc['content_type'] ?? '') === 'link' ? 'selected' : '' ?>>🔗 链接</option>
            </select>
            <button type="submit" class="btn">保存</button>
            <?php if (isset($doc['id']) && $doc['id'] > 0): ?>
            <a href="/<?= Config::ADMIN_PATH ?>/preview/<?= $doc['id'] ?>" class="btn btn-secondary" target="_blank">👁 预览</a>
            <?php endif; ?>
        </div>

        <div id="link-field" style="margin-bottom:16px;<?= ($doc['content_type'] ?? 'markdown') === 'link' ? '' : 'display:none;' ?>">
            <input type="text" name="link_url" placeholder="请输入链接地址 (https://...)" value="<?= htmlspecialchars($doc['link_url'] ?? '') ?>" style="width:100%;padding:10px 14px;border:1px solid #d2d2d7;border-radius:10px;font-size:15px;">
        </div>

        <!-- Markdown 编辑器 -->
        <div id="editor-wrapper" style="margin-bottom:16px;<?= ($doc['content_type'] ?? 'markdown') === 'markdown' ? '' : 'display:none;' ?>">
            <textarea id="editor" name="content" style="width:100%;min-height:500px;"><?php
echo htmlspecialchars($doc['content_md'] ?? '');
?></textarea>
        </div>

        <!-- 文件挂载区域（仅 Markdown 模式显示） -->
        <div id="html-wrapper" style="margin-bottom:16px;<?= ($doc['content_type'] ?? 'markdown') === 'html' ? '' : 'display:none;' ?>">
            <textarea name="content_html" id="html-editor" style="width:100%;min-height:500px;padding:12px;border:1px solid #d2d2d7;border-radius:10px;font-family:'Courier New',monospace;font-size:14px;line-height:1.6;"><?= htmlspecialchars($doc['content_md'] ?? '') ?></textarea>
            <p style="font-size:12px;color:#86868b;margin-top:4px;">📄 直接编写或粘贴原生 HTML 源码（支持 &lt;style&gt;、&lt;script&gt;、外链视频等）</p>
        </div>
        
        <!-- 文件挂载区域（仅 Markdown 模式显示） -->
        <div id="assets-wrapper" style="margin-bottom:20px;<?= ($doc['content_type'] ?? 'markdown') !== 'markdown' ? 'display:none;' : '' ?>">
            <details style="background:#fff;border-radius:10px;padding:12px 16px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <summary style="cursor:pointer;font-size:14px;color:#1d1d1f;font-weight:500;">📎 挂载文件（前台底部显示下载按钮）</summary>
                <div style="margin-top:12px;">
                    <?php
                    $userId = $_SESSION['user']['id'];
                    $allFiles = \File::getListByUser($userId);
                    $attachedFiles = [];
                    if ($doc && !empty($doc['attached_files'])) {
                        $attachedFiles = json_decode($doc['attached_files'], true) ?? [];
                    }
                    ?>
                    <?php if (empty($allFiles)): ?>
                        <p style="font-size:13px;color:#86868b;">暂无文件，请先<a href="/<?= Config::ADMIN_PATH ?>/upload" style="color:#007aff;">上传文件</a></p>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
                        <?php foreach ($allFiles as $f): ?>
                            <label style="font-size:13px;display:flex;align-items:center;gap:6px;padding:6px;border-radius:6px;cursor:pointer;background:<?= in_array($f['id'], $attachedFiles) ? '#e8f0fe' : '#f5f5f7' ?>;">
                                <input type="checkbox" name="attached_files[]" value="<?= $f['id'] ?>" <?= in_array($f['id'], $attachedFiles) ? 'checked' : '' ?>>
                                📁 <?= htmlspecialchars($f['original_name']) ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </details>
        </div>

        <p style="font-size:12px;color:#86868b;">支持 Markdown 语法 | 可直接粘贴/拖拽图片上传 | 切换「链接」类型可直接跳转</p>
        <!-- 隐藏的文件上传 input（供编辑器图片粘贴用） -->
        <input type="file" id="mde-image-input" accept="image/*" style="display:none;">
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/simplemde@1.11.2/dist/simplemde.min.js"></script>
<script>


// 初始化 SimpleMDE
const mde = new SimpleMDE({
    element: document.getElementById('editor'),
    spellChecker: false,
    autosave: false,
    toolbar: [
        'bold', 'italic', 'heading', '|',
        'quote', 'unordered-list', 'ordered-list', '|',
        'link', 'image', 'table', '|',
        'code', 'horizontal-rule', '|',
        'preview', 'side-by-side', 'fullscreen',
    ],
    status: false,
});



// 图片粘贴上传
mde.codemirror.on('paste', function(cm, e) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            e.preventDefault();
            const file = items[i].getAsFile();
            uploadImageToServer(file, (url) => {
                const pos = mde.codemirror.getCursor();
                mde.codemirror.replaceRange('![](' + url + ')', pos);
            });
            return;
        }
    }
});

// 拖拽图片上传
const editorWrapper = document.getElementById('editor-wrapper');
editorWrapper.addEventListener('drop', function(e) {
    e.preventDefault();
    const files = e.dataTransfer.files;
    for (let i = 0; i < files.length; i++) {
        if (files[i].type.indexOf('image') !== -1) {
            uploadImageToServer(files[i], (url) => {
                const pos = mde.codemirror.getCursor();
                mde.codemirror.replaceRange('![](' + url + ')', pos);
            });
            return;
        }
    }
});
editorWrapper.addEventListener('dragover', e => e.preventDefault());

// 点击工具栏「image」按钮时触发文件选择
const origImageHandler = mde.toolbarItems.image.action;
mde.toolbarItems.image.action = function() {
    const input = document.getElementById('mde-image-input');
    input.onchange = function() {
        if (this.files[0]) {
            uploadImageToServer(this.files[0], (url) => {
                const pos = mde.codemirror.getCursor();
                mde.codemirror.replaceRange('![](' + url + ')', pos);
            });
        }
        input.value = '';
    };
    input.click();
};

// 上传图片到服务器
function uploadImageToServer(file, callback) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_image');

    fetch('/<?= Config::ADMIN_PATH ?>/upload', {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            callback(data.url);
        } else {
            alert('图片上传失败：' + (data.error || '未知错误'));
        }
    })
    .catch(() => alert('图片上传失败'));
}

// 切换文档类型
function toggleEditorMode(select) {
    const val = select.value;
    const isLink = val === 'link';
    const isHtml = val === 'html';
    const isMd = val === 'markdown';
    
    document.getElementById('link-field').style.display = isLink ? '' : 'none';
    document.getElementById('html-wrapper').style.display = isHtml ? '' : 'none';
    document.getElementById('editor-wrapper').style.display = isMd ? '' : 'none';
    document.getElementById('assets-wrapper').style.display = isMd ? '' : 'none';
}

// 提交前同步 HTML 内容到标准字段
document.getElementById('doc-form').addEventListener('submit', function() {
    const type = document.querySelector('select[name="type"]').value;
    if (type === 'html') {
        // 把 HTML 编辑器的内容复制到 content 字段（不然后台收不到）
        document.querySelector('textarea[name="content"]').value = document.querySelector('textarea[name="content_html"]').value;
    }
});
</script>
</body>
</html>
