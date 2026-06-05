<?php
/**
 * 文件管理控制器
 */

require_once __DIR__ . '/../../Models/File.php';
require_once __DIR__ . '/../../Models/Log.php';

class AdminFile {
    public static function handle(array $query): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // 检测是否是上传页面访问
        if (strpos($uri, '/upload') !== false) {
            self::uploadFile($query);
            return;
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
            self::uploadFile($query);
        } elseif (isset($_POST['chunk_uuid'])) {
            self::uploadChunk();
        } elseif (isset($_FILES['file'])) {
            self::uploadFile($query);
        } else {
            self::files($query);
        }
    }

    public static function files(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                require_once __DIR__ . '/../../Models/Log.php';
                File::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_file_batch', '批量删除了 ' . count($ids) . ' 个文件', 'file', null);
            }
            Router::redirect('/' . Config::ADMIN_PATH . '/files');
            return;
        }
        
        // 单个删除
        if (isset($query['delete'])) {
            require_once __DIR__ . '/../../Models/Log.php';
            $fileInfo = File::getById((int)$query['delete']);
            File::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_file', '删除了文件: ' . ($fileInfo['original_name'] ?? 'unknown'), 'file', (int)$query['delete']);
            Router::redirect('/' . Config::ADMIN_PATH . '/files');
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $files = File::getListByUser($userId, $search);
        
        include __DIR__ . '/../../../templates/admin/files.php';
    }
    
    /**
     * 上传文件（支持 chunk 切片上传）
     */


    public static function uploadFile(array $query): void {
        $userId = $_SESSION['user']['id'];
        $isImageUpload = ($_POST['action'] ?? '') === 'upload_image';

        // 图片上传（编辑器粘贴/拖拽）→ 返回 JSON
        if ($isImageUpload && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $fileId = \File::upload($userId, $_FILES['file']);
            $file = \File::getById($fileId);
            $url = '/yunlian/file?key=' . $file['file_key'];
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'url' => $url, 'file_key' => $file['file_key']]);
            exit;
        }

        // Chunk 上传（切片上传）
        if (isset($_POST['chunk_uuid']) && isset($_POST['chunk_index']) && isset($_FILES['file'])) {
            self::uploadChunk($userId);
            return;
        }

        // 普通文件上传（小文件）
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $fileId = \File::upload($userId, $_FILES['file']);
            // 返回 JSON 给前端（异步上传）
            if (isset($_POST['ajax'])) {
                $file = \File::getById($fileId);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'file_id' => $fileId, 'file_key' => $file['file_key'], 'original_name' => $file['original_name']]);
                exit;
            }
            Router::redirect('/' . Config::ADMIN_PATH . '/files');
            return;
        }

        if ($isImageUpload) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => '未收到文件']);
            exit;
        }

        include __DIR__ . '/../../../templates/admin/upload.php';
    }

    /**
     * 处理切片上传
     */


    public static function uploadChunk(int $userId): void
    {
        $uuid = $_POST['chunk_uuid'] ?? '';
        $index = (int)($_POST['chunk_index'] ?? 0);
        $total = (int)($_POST['chunk_total'] ?? 0);
        $originalName = $_POST['original_name'] ?? 'unknown';
        $mimeType = $_POST['mime_type'] ?? 'application/octet-stream';

        if (!$uuid || $index < 0 || !isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => '参数错误']);
            exit;
        }

        $chunkDir = Config::STORAGE_PATH . 'chunks/' . $uuid . '/';
        if (!is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        // 保存分片
        $chunkFile = $chunkDir . $index . '.chunk';
        move_uploaded_file($_FILES['file']['tmp_name'], $chunkFile);

        // 记录元数据
        if ($index === 0) {
            file_put_contents($chunkDir . 'meta.json', json_encode([
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'total_chunks' => $total,
                'user_id' => $userId,
                'file_key' => bin2hex(random_bytes(16)), // 32位 file_key
            ]));
        }

        // 检查是否所有分片已上传
        $uploadedChunks = glob($chunkDir . '*.chunk');
        $expectedChunks = $total > 0 ? $total : count($uploadedChunks);

        if (count($uploadedChunks) >= $expectedChunks) {
            // 合并分片
            $meta = json_decode(file_get_contents($chunkDir . 'meta.json'), true);
            $finalName = uniqid('file_') . '.' . pathinfo($meta['original_name'], PATHINFO_EXTENSION);
            $finalPath = Config::STORAGE_PATH . $finalName;

            $fp = fopen($finalPath, 'wb');
            for ($i = 0; $i < $expectedChunks; $i++) {
                $chunkPath = $chunkDir . $i . '.chunk';
                if (file_exists($chunkPath)) {
                    fwrite($fp, file_get_contents($chunkPath));
                    unlink($chunkPath);
                }
            }
            fclose($fp);

            // 写入数据库
            $pdo = Config::getDB();
            $stmt = $pdo->prepare(
                "INSERT INTO files (user_id, original_name, stored_name, file_size, mime_type, file_key) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $fileSize = filesize($finalPath);
            $stmt->execute([$userId, $meta['original_name'], $finalName, $fileSize, $meta['mime_type'], $meta['file_key']]);
            $fileId = $pdo->lastInsertId();

            // 清理
            @unlink($chunkDir . 'meta.json');
            @rmdir($chunkDir);

            echo json_encode(['success' => true, 'file_id' => $fileId, 'file_key' => $meta['file_key'], 'done' => true]);
            exit;
        }

        echo json_encode(['success' => true, 'chunk' => $index, 'done' => false]);
        exit;
    }
    
    /**
     * 设置
     */

}
