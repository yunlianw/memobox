<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($doc['title']) ?> - 分享</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;padding:20px;color:#1d1d1f;line-height:1.6}
.container{max-width:800px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 2px 20px rgba(0,0,0,.08)}
.header{margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f2f2f7}
.header h1{font-size:24px;font-weight:600;margin-bottom:8px}
.header .meta{font-size:13px;color:#86868b}
.content{font-size:15px}
.content h1,.content h2,.content h3{margin:1.2em 0 .6em;font-weight:600}
.content p{margin:.8em 0}
.content a{color:#007aff;text-decoration:none}
.content a:hover{text-decoration:underline}
.content pre{background:#f6f8fa;border-radius:8px;padding:16px;overflow-x:auto;font-size:13px;line-height:1.5;margin:1em 0}
.content code{background:#f0f0f5;padding:2px 6px;border-radius:4px;font-size:13px}
.content pre code{background:none;padding:0}
.content ul,.content ol{margin:.8em 0;padding-left:1.5em}
.content blockquote{border-left:3px solid #007aff;padding-left:12px;color:#86868b;margin:1em 0}
.footer{margin-top:32px;padding-top:16px;border-top:1px solid #f2f2f7;font-size:12px;color:#c7c7cc;text-align:center}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><?= htmlspecialchars($doc['title']) ?></h1>
        <div class="meta">分享自私密知识库 · <?= date('Y-m-d H:i', strtotime($doc['updated_at'])) ?></div>
    </div>
    <div class="content">
        <?= $htmlContent ?>
    </div>
    <?php
    // 显示挂载的文件下载按钮
    if (!empty($doc['attached_files'])) {
        $attachedIds = json_decode($doc['attached_files'], true) ?? [];
        if (!empty($attachedIds)) {
            $pdo = \Config::getDB();
            $placeholders = implode(',', array_fill(0, count($attachedIds), '?'));
            $stmt = $pdo->prepare("SELECT * FROM files WHERE id IN ($placeholders) AND user_id = ?");
            $params = array_merge($attachedIds, [$share['user_id']]);
            $stmt->execute($params);
            $attachedFiles = $stmt->fetchAll();
            
            if (!empty($attachedFiles)): ?>
                <div style="margin-top:32px;padding-top:20px;border-top:1px solid #f2f2f7;">
                    <h3 style="font-size:14px;color:#86868b;margin-bottom:12px;">📎 附件</h3>
                    <?php foreach ($attachedFiles as $af): ?>
                        <?php
                        $ext = strtolower(pathinfo($af['stored_name'], PATHINFO_EXTENSION));
                        $imageExts = ['jpg','jpeg','png','gif','webp','bmp'];
                        $videoExts = ['mp4','webm','ogg','mov'];
                        $safeToken = htmlspecialchars($token);
                        $fileUrl = '?action=share_media&token=' . $safeToken . '&key=' . $af['file_key'];
                        ?>
                        <?php if (in_array($ext, $imageExts) || strpos($af['mime_type'], 'image/') === 0): ?>
                            <!-- 图片：直接渲染 -->
                            <div style="margin:12px 0;">
                                <img src="<?= $fileUrl ?>" alt="<?= htmlspecialchars($af['original_name']) ?>"
                                     style="max-width:100%;border-radius:8px;display:block;">
                                <div style="margin-top:6px;font-size:12px;color:#86868b;">
                                    <?= htmlspecialchars($af['original_name']) ?>
                                    <a href="<?= $fileUrl ?>" download style="margin-left:8px;color:#007aff;text-decoration:none;">⬇️ 下载原图</a>
                                </div>
                            </div>
                        <?php elseif (in_array($ext, $videoExts) || strpos($af['mime_type'], 'video/') === 0): ?>
                            <!-- 视频：HTML5 播放器 -->
                            <div style="margin:12px 0;background:#000;border-radius:8px;overflow:hidden;">
                                <video controls style="width:100%;max-height:500px;display:block;" preload="metadata">
                                    <source src="<?= $fileUrl ?>" type="<?= htmlspecialchars($af['mime_type']) ?>">
                                    您的浏览器不支持视频播放
                                </video>
                                <div style="padding:6px 12px;font-size:12px;color:#aaa;background:#111;">
                                    <?= htmlspecialchars($af['original_name']) ?>
                                    <a href="<?= $fileUrl ?>" download style="margin-left:8px;color:#007aff;text-decoration:none;">⬇️ 下载</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- 其他：下载按钮 -->
                            <div style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f5f5f7;border-radius:8px;font-size:13px;color:#1d1d1f;margin-bottom:8px;margin-right:4px;">
                                <a href="<?= $fileUrl ?>" 
                                   style="color:#1d1d1f;text-decoration:none;display:flex;align-items:center;gap:6px;"
                                   download="<?= htmlspecialchars($af['original_name']) ?>">
                                    📁 <?= htmlspecialchars($af['original_name']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif;
        }
    }
    ?>
    <div class="footer">此内容受保护 · 仅限授权访问</div>
</div>
</body>
</html>
