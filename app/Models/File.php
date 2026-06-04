<?php
/**
 * 文件模型
 */

class File {
    /**
     * 上传并存储文件
     * @return int 文件ID
     */
    public static function upload(int $userId, array $uploadedFile): int {
        $pdo = Config::getDB();
        
        // === 扩展名白名单验证（从数据库读取，可后台配置）===
        require_once __DIR__ . '/Setting.php';
        $allowedExtsRaw = Setting::get('allowed_exts', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar,7z,mp4,mp3,webm,ogg,txt,md');
        $allowedExts = array_map('trim', explode(',', $allowedExtsRaw));
        
        $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            throw new Exception('不允许的文件类型：' . htmlspecialchars($ext) . '。允许的类型：' . htmlspecialchars($allowedExtsRaw));
        }
        
        // === 文件名安全处理（去除路径）===
        $originalName = basename($uploadedFile['name']);
        $originalName = preg_replace('/[^\w.\-]/u', '_', $originalName);
        if (empty($originalName)) {
            $originalName = 'unnamed_file.' . $ext;
        }
        
        // === 真实 MIME 类型验证 ===
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $uploadedFile['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
                'video/mp4', 'audio/mpeg', 'audio/ogg',
                'text/plain',
            ];
            if (!in_array($realMime, $allowedMimes)) {
                throw new Exception('文件 MIME 类型不匹配：' . htmlspecialchars($realMime));
            }
            
            // 图片类型再用 getimagesize 验证
            if (in_array($realMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $imgInfo = @getimagesize($uploadedFile['tmp_name']);
                if ($imgInfo === false) {
                    throw new Exception('无效的图片文件');
                }
            }
        }
        
        $storedName = Security::generateToken(16) . '_' . time();
        $storedName .= '.' . $ext;
        $destPath = Config::STORAGE_PATH . $storedName;
        
        move_uploaded_file($uploadedFile['tmp_name'], $destPath);
        
        // 用扩展名判断 MIME 类型（避免依赖 mime_content_type 扩展）
        $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip', 'rar' => 'application/vnd.rar', '7z' => 'application/x-7z-compressed',
            'mp4' => 'video/mp4', 'mp3' => 'audio/mpeg',
            'txt' => 'text/plain', 'md' => 'text/plain',
        ];
        $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

        // 生成 file_key（32位唯一标识）
        $fileKey = md5(uniqid('', true) . $storedName . $userId);

        // 数据库记录
        $stmt = $pdo->prepare(
            "INSERT INTO files (user_id, original_name, stored_name, file_size, mime_type, file_key)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $originalName,
            $storedName,
            filesize($destPath),
            $mimeType,
            $fileKey,
        ]);
        
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * 通过ID获取文件信息
     */
    public static function getById(int $id): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT *, (mime_type LIKE 'image/%') AS is_image FROM files WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $file = $stmt->fetch();
        return $file ?: null;
    }
    
    /**
     * 通过 file_key 获取文件信息
     */
    public static function getByFileKey(string $key): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT *, (mime_type LIKE 'image/%') AS is_image FROM files WHERE file_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $file = $stmt->fetch();
        return $file ?: null;
    }
    
    /**
     * 获取用户的文件列表
     */
    public static function getListByUser(int $userId, ?string $search = null): array {
        $pdo = Config::getDB();
        if ($search) {
            $stmt = $pdo->prepare(
                "SELECT *, (mime_type LIKE 'image/%') AS is_image FROM files WHERE user_id = ? AND original_name LIKE ? ORDER BY created_at DESC"
            );
            $stmt->execute([$userId, '%' . $search . '%']);
        } else {
            $stmt = $pdo->prepare(
                "SELECT *, (mime_type LIKE 'image/%') AS is_image FROM files WHERE user_id = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * 删除单个文件（支持联动删除物理文件）
     * @param bool $deletePhysical 是否强制删除硬盘文件
     */
    public static function delete(int $id, int $userId, bool $deletePhysical = true): bool {
        $pdo = Config::getDB();
        $file = self::getById($id);
        if (!$file || $file['user_id'] != $userId) {
            return false;
        }
        
        if ($deletePhysical) {
            $filePath = Config::STORAGE_PATH . $file['stored_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
    
    /**
     * 批量删除文件（支持联动删除物理文件）
     * @param bool $deletePhysical 是否强制删除硬盘文件
     */
    public static function deleteBatch(array $ids, int $userId, bool $deletePhysical = true): int {
        if (empty($ids)) return 0;
        $pdo = Config::getDB();
        $count = 0;
        foreach ($ids as $id) {
            if (self::delete((int)$id, $userId, $deletePhysical)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * 智能流式输出（根据 MIME 类型自动分流）
     * - 图片/文本：inline 预览（图片直接显示，文本带高亮）
     * - 视频：inline 播放
     * - 其他：触发下载
     */
    public static function streamView(array $file): void {
        $filePath = Config::STORAGE_PATH . $file['stored_name'];
        
        if (!file_exists($filePath)) {
            Router::show404();
            return;
        }
        
        $mime = $file['mime_type'] ?? 'application/octet-stream';
        $ext = strtolower(pathinfo($file['stored_name'], PATHINFO_EXTENSION));
        
        // 图片类：inline 模式直接在浏览器显示
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (in_array($ext, $imageExts) || strpos($mime, 'image/') === 0) {
            header("Content-Type: $mime");
            header("Content-Length: " . filesize($filePath));
            header("Cache-Control: public, max-age=86400");
            readfile($filePath);
            exit;
        }
        
        // 视频类：inline 模式，供前端 <video> 标签播放
        $videoExts = ['mp4', 'webm', 'ogg', 'mov'];
        if (in_array($ext, $videoExts) || strpos($mime, 'video/') === 0) {
            header("Content-Type: $mime");
            header("Accept-Ranges: bytes");
            header("Cache-Control: public, max-age=86400");
            
            // 支持 Range 请求（视频快进）
            $size = filesize($filePath);
            if (isset($_SERVER['HTTP_RANGE'])) {
                self::serveRange($filePath, $size, $mime);
            } else {
                header("Content-Length: $size");
                readfile($filePath);
            }
            exit;
        }
        
        // 文本/代码类：直接读取内容，带一键复制 + 简单高亮
        $textExts = ['txt', 'sh', 'php', 'json', 'conf', 'log', 'md', 'js', 'css', 'html', 'xml', 'yaml', 'yml', 'ini', 'env'];
        if (in_array($ext, $textExts) || strpos($mime, 'text/') === 0) {
            $content = file_get_contents($filePath);
            $displayName = htmlspecialchars($file['original_name']);
            $escaped = htmlspecialchars($content);
            ?>
            <!DOCTYPE html>
            <html lang="zh-CN">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= $displayName ?> - 文件预览</title>
            <style>
            body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC',sans-serif;background:#1e1e1e;color:#d4d4d4;margin:0;min-height:100vh}
            .toolbar{background:#252526;padding:10px 20px;display:flex;align-items:center;gap:16px;border-bottom:1px solid #3e3e42}
            .toolbar span{font-size:13px;color:#858585}
            .toolbar button{padding:6px 14px;background:#0e639c;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;}
            .toolbar button:hover{background:#1177bb}
            pre{margin:0;padding:20px;font-size:13px;line-height:1.6;white-space:pre-wrap;word-break:break-all}
            .hl-keyw{color:#569cd6}.hl-str{color:#ce9178}.hl-cmt{color:#6a9955}.hl-num{color:#b5cea8}.hl-fn{color:#dcdcaa}
            </style>
            </head>
            <body>
            <div class="toolbar">
                <span>📄 <?= $displayName ?></span>
                <button onclick="copyText()">📋 复制内容</button>
            </div>
            <pre id="content"><?= $escaped ?></pre>
            <script>
            function copyText() {
                const btn = event.target;
                navigator.clipboard.writeText(document.getElementById('content').textContent).then(() => {
                    btn.textContent = '✅ 已复制';
                    setTimeout(() => btn.textContent = '📋 复制内容', 1500);
                });
            }
            </script>
            </body>
            </html>
            <?php
            exit;
        }
        
        // 其他类型：触发下载
        self::streamDownload($file);
    }
    
    /**
     * 支持 Range 请求的视频流式输出
     */
    private static function serveRange(string $filePath, int $size, string $mime): void {
        $range = $_SERVER['HTTP_RANGE'];
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = (int)($matches[1] ?? 0);
        $end = ($matches[2] ?? '') !== '' ? (int)$matches[2] : $size - 1;
        $length = $end - $start + 1;
        
        header("HTTP/1.1 206 Partial Content");
        header("Content-Type: $mime");
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");
        header("Accept-Ranges: bytes");
        
        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);
        echo fread($fp, $length);
        fclose($fp);
    }
    
    /**
     * 流式下载文件（强制下载）
     */
    public static function streamDownload(array $file): void {
        $filePath = Config::STORAGE_PATH . $file['stored_name'];
        
        if (!file_exists($filePath)) {
            Router::show404();
            return;
        }
        
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $file['original_name'] . "\"");
        header("Content-Length: " . filesize($filePath));
        header("Cache-Control: no-store, no-cache, must-revalidate");
        
        // 关闭输出缓冲
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        readfile($filePath);
        exit;
    }
}
