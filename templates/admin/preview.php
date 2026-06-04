<?php
/**
 * 文档预览模板（后台）
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> - 私密知识库</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;color:#1d1d1f}
.header{background:#fff;border-bottom:1px solid #d2d2d7;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.header h1{font-size:16px;font-weight:600}
.header .actions{display:flex;gap:8px}
.btn{padding:6px 14px;background:#007aff;color:#fff;border:none;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-block}
.btn-secondary{background:#f5f5f7;color:#1d1d1f;border:1px solid #d2d2d7}
.container{max-width:800px;margin:24px auto;padding:0 16px}
.preview-box{background:#fff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.08)}
.markdown-body{font-size:15px;line-height:1.8;color:#1d1d1f}
.markdown-body h1{font-size:28px;font-weight:700;margin:24px 0 16px;border-bottom:1px solid #f5f5f7;padding-bottom:12px}
.markdown-body h2{font-size:22px;font-weight:600;margin:32px 0 12px;color:#1d1d1f}
.markdown-body h3{font-size:18px;font-weight:600;margin:24px 0 8px}
.markdown-body p{margin:12px 0}
.markdown-body ul,.markdown-body ol{margin:8px 0 8px 24px}
.markdown-body li{margin:4px 0}
.markdown-body a{color:#007aff;text-decoration:none}
.markdown-body a:hover{text-decoration:underline}
.markdown-body img{max-width:100%;border-radius:8px;margin:12px 0;box-shadow:0 2px 8px rgba(0,0,0,0.1);max-height:384px;width:auto}
.markdown-body img.screenshot-lg{max-height:384px}
.markdown-body img.screenshot-sm{max-height:240px}
.markdown-body img.screenshot-xs{max-height:128px}
.markdown-body img.full-width{max-height:none;width:100%}
.markdown-body .compare-flex{display:flex;gap:16px;flex-wrap:wrap;margin:12px 0}
.markdown-body .compare-flex > div{flex:1;min-width:200px;text-align:center}
.markdown-body .compare-flex img,.markdown-body .compare-flex video{height:384px;width:auto;max-height:none;margin:0}
.markdown-body .compare-flex .caption{font-size:13px;color:#86868b;margin-top:4px}
.markdown-body code{background:#f5f5f7;padding:2px 6px;border-radius:4px;font-size:13px;font-family:'SF Mono',Menlo,monospace}
.markdown-body pre{background:#1d1d1f;color:#f5f5f7;padding:16px;border-radius:8px;overflow-x:auto;margin:12px 0;font-size:13px;line-height:1.5}
.markdown-body blockquote{border-left:4px solid #007aff;padding:8px 16px;background:#f5f5f7;border-radius:0 8px 8px 0;margin:12px 0;color:#86868b}
.markdown-body table{width:100%;border-collapse:collapse;margin:12px 0}
.markdown-body th,.markdown-body td{border:1px solid #d2d2d7;padding:8px 12px;text-align:left;font-size:13px}
.markdown-body th{background:#f5f5f7;font-weight:600}
.attachments{margin-top:32px;padding-top:24px;border-top:1px solid #f5f5f7}
.attachments h3{font-size:14px;color:#86868b;margin-bottom:12px}
.attachment-list{display:flex;flex-direction:column;gap:8px}
.attachment-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f5f5f7;border-radius:8px;font-size:13px}
.attachment-item a{color:#007aff;text-decoration:none}
.attachment-item .file-size{color:#86868b;font-size:12px}
</style>
</head>
<body>

<div class="header">
    <h1>📖 <?= htmlspecialchars($doc['title']) ?></h1>
    <div class="actions">
        <a href="/<?= Config::ADMIN_PATH ?>/edit/<?= $doc['id'] ?>" class="btn btn-secondary">✏️ 编辑</a>
        <a href="/<?= Config::ADMIN_PATH ?>/documents" class="btn btn-secondary">← 返回列表</a>
    </div>
</div>

<div class="container">
    <div class="preview-box">
        <div class="markdown-body">
            <?= $htmlContent ?>
        </div>
        
        <?php if (!empty($attachedFiles)): ?>
        <div class="attachments">
            <h3>📎 附件下载</h3>
            <div class="attachment-list">
                <?php foreach ($attachedFiles as $file): ?>
                <div class="attachment-item">
                    <span>📄</span>
                    <a href="/<?= Config::ADMIN_PATH ?>/file?key=<?= htmlspecialchars($file['file_key']) ?>" target="_blank">
                        <?= htmlspecialchars($file['original_name']) ?>
                    </a>
                    <span class="file-size">(<?= round($file['file_size'] / 1024, 1) ?> KB)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
