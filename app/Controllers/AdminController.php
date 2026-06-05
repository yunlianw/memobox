<?php
/**
 * 后台管理控制器
 * 需要登录后才能访问
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Document.php';
require_once __DIR__ . '/../Models/Share.php';
require_once __DIR__ . '/../Models/File.php';

class AdminController {
    
    /**
     * 路由分发
     */
    public static function dispatch(string $subPath, array $query): void {
        // 检查登录
        if (!User::isLoggedIn()) {
            self::showLogin($query);
            return;
        }
        
        $parts = explode('/', $subPath);
        $action = $parts[0] ?? 'dashboard';
        
        switch ($action) {
            case '':
            case 'dashboard':
                self::dashboard();
                break;
            case 'documents':
                self::documents($query);
                break;
            case 'edit':
                self::editDocument($query, $parts);
                break;
            case 'preview':
                self::previewDocument($query, $parts);
                break;
            case 'shares':
                self::shares($query);
                break;
            case 'edit_share':
                self::editShare($query);
                break;
            case 'files':
                self::files($query);
                break;
            case 'upload':
                self::uploadFile($query);
                break;
            case 'settings':
                self::settings($query);
                break;
            case 'security':
                self::security($query);
                break;
            case 'logs':
                self::logs($query);
                break;
            case 'logout':
                self::logout();
                break;
            default:
                Router::show404();
        }
    }
    
    /**
     * 显示登录页
     */
    private static function showLogin(array $query): void {
        $isFirstRun = !User::hasAny();
        
        // 判断是POST登录还是显示登录页
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($isFirstRun) {
                // 首次安装：创建管理员
                if (strlen($password) < 8) {
                    $error = '密码至少8位，且必须包含大小写字母和数字';
                } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
                    $error = '密码必须包含大小写字母和数字（例如：Abc12345）';
                } else {
                    User::create($username, $password);
                    $user = User::login($username, $password);
                }
            } else {
                $user = User::login($username, $password);
            }
            
            if ($user) {
                User::setSession($user);
                Security::clearFailedLogin();
                
                // 记录登录日志
                require_once __DIR__ . '/../Models/Log.php';
                Log::admin($user['id'], 'login', '管理员登录成功', 'system', null);
                
                // 已在 /yunlian 路径下，无需再加前缀
                Router::redirect('/' . Config::ADMIN_PATH . '/dashboard');
            } else {
                Security::recordFailedLogin();
                $error = '用户名或密码错误';
                include __DIR__ . '/../../templates/admin/login.php';
            }
        } else {
            include __DIR__ . '/../../templates/admin/login.php';
        }
    }
    
    /**
     * 仪表盘
     */
    private static function dashboard(): void {
        $userId = $_SESSION['user']['id'];
        $docs = Document::getList($userId);
        $shares = Share::getListByUser($userId);
        $files = File::getListByUser($userId);
        
        include __DIR__ . '/../../templates/admin/dashboard.php';
    }
    
    /**
     * 文档列表
     */
    private static function documents(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 单个删除
        if (isset($query['delete'])) {
            require_once __DIR__ . '/../Models/Log.php';
            Document::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_document', '删除了文档 id=' . (int)$query['delete'], 'document', (int)$query['delete']);
            Router::redirect("/" . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                require_once __DIR__ . '/../Models/Log.php';
                Document::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_document_batch', '批量删除了 ' . count($ids) . ' 个文档', 'document', null);
            }
            Router::redirect("/" . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $docs = Document::getList($userId, $search);
        
        include __DIR__ . '/../../templates/admin/documents.php';
    }
    
    /**
     * 编辑文档
     */
    private static function editDocument(array $query, array $parts): void {
        $userId = $_SESSION['user']['id'];
        $docId = (int)($parts[1] ?? 0);
        
        // 禁用浏览器缓存：编辑页面必须实时刷新
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $type = $_POST['type'] ?? 'markdown';
            $linkUrl = $_POST['link_url'] ?? null;
            $attachedFiles = isset($_POST['attached_files']) ? array_map('intval', $_POST['attached_files']) : null;
            
            if ($docId > 0) {
                Document::update($docId, $userId, [
                    'title' => $title,
                    'content_md' => $content,
                    'content_type' => $type,
                    'link_url' => $linkUrl,
                    'attached_files' => $attachedFiles ? json_encode($attachedFiles) : null,
                ]);
            } else {
                $docId = Document::create($userId, $title, $content, $type, $linkUrl, $attachedFiles);
            }
            
            Router::redirect("/" . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        $doc = $docId > 0 ? Document::getById($docId, $userId) : null;
        include __DIR__ . '/../../templates/admin/edit.php';
    }
    
    /**
     * 编辑分享（修改密码/次数/过期时间）
     */
    private static function editShare(array $query): void {
        $userId = $_SESSION['user']['id'];
        $shareId = (int)($query['id'] ?? 0);
        $share = null;

        if ($shareId > 0) {
            $pdo = Config::getDB();
            $stmt = $pdo->prepare("SELECT * FROM shares WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$shareId, $userId]);
            $share = $stmt->fetch();
        }

        if (!$share) {
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? null;
            
            // 处理访问次数限制（支持自定义）
            $maxClicks = (int)($_POST['max_clicks'] ?? -1);
            if ($_POST['max_clicks'] === 'custom' && isset($_POST['max_clicks_custom'])) {
                $maxClicks = (int)$_POST['max_clicks_custom'];
            }
            
            // 处理有效期（支持自定义天数）
            $expireSeconds = null;
            if ($_POST['expire_seconds'] !== '' && $_POST['expire_seconds'] !== 'custom') {
                $expireSeconds = (int)$_POST['expire_seconds'];
            } elseif ($_POST['expire_seconds'] === 'custom' && isset($_POST['expire_days_custom'])) {
                $expireSeconds = (int)$_POST['expire_days_custom'] * 86400; // 天数转秒数
            }
            
            $destroyDelay = isset($_POST['destroy_delay_enabled']) ? (int)($_POST['destroy_delay_minutes'] ?? 0) : 0;

            Share::update($shareId, $userId, $password, $maxClicks, $expireSeconds, $destroyDelay);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }

        // 计算当前剩余秒数（用于表单回显）
        $currentExpireSeconds = null;
        if ($share['expire_time']) {
            $diff = strtotime($share['expire_time']) - time();
            $currentExpireSeconds = $diff > 0 ? $diff : null;
        }

        require_once __DIR__ . '/../Models/Setting.php';
        $shareDomain = Setting::get('share_domain', '');
        if ($shareDomain === '') {
            $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        }
        
        include __DIR__ . '/../../templates/admin/edit_share.php';
    }

    /**
     * 文档预览（后台，需登录）
     */
    private static function previewDocument(array $query, array $parts): void {
        $userId = $_SESSION['user']['id'];
        $docId = isset($parts[1]) ? (int)$parts[1] : 0;
        if ($docId <= 0) {
            Router::show404();
            return;
        }
        
        // 禁用浏览器缓存
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');        $doc = Document::getById($docId, $userId);
        if (!$doc) {
            Router::show404();
            return;
        }

        // 渲染 Markdown 为 HTML
        if ($doc['content_type'] === 'link') {
            $htmlContent = '<div style="text-align:center;padding:60px;color:#86868b;">
                <p>这是一个链接分享文档</p>
                <p><a href="' . htmlspecialchars($doc['link_url']) . '" target="_blank" style="color:#007aff;">' . htmlspecialchars($doc['link_url']) . '</a></p>
            </div>';
        } elseif ($doc['content_type'] === 'html') {
            // HTML 源码模式：用 iframe sandbox 隔离，防止 XSS 执行
            if (ob_get_level()) {
                ob_end_clean();
            }
            header("Content-Security-Policy: default-src 'self' 'unsafe-inline'; script-src 'none'");
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>HTML 预览（沙箱）</title>';
            echo '<style>body{margin:0;padding:0;background:#f5f5f7;}</style></head><body>';
            echo '<iframe sandbox="allow-same-origin allow-styles" ';
            echo 'style="width:100%;height:95vh;border:1px solid #d2d2d7;border-radius:12px;background:#fff;" ';
            echo 'srcdoc="' . htmlspecialchars($doc['content_md'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
            echo '</iframe>';
            echo '</body></html>';
            exit;
        } else {
            require_once __DIR__ . '/../Services/Parsedown.php';
            $parsedown = new Parsedown();
            $htmlContent = $parsedown->text($doc['content_md']);

            // 将文档内的 /yunlian/file?key=xxx 链接保持原样（后台预览，已登录）
        }

        // 获取附件
        $attachedFiles = [];
        if (!empty($doc['attached_files'])) {
            $attached = json_decode($doc['attached_files'], true);
            if (is_array($attached)) {
                foreach ($attached as $fileKey) {
                    $file = \File::getByFileKey($fileKey);
                    if ($file) $attachedFiles[] = $file;
                }
            }
        }

        // 渲染预览页面
        $pageTitle = '预览：' . $doc['title'];
        include __DIR__ . '/../../templates/admin/preview.php';
    }

    /**
     * 分享列表
     */
    private static function shares(array $query): void {
        $userId = $_SESSION['user']['id'];
        require_once __DIR__ . '/../Models/Setting.php';
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                require_once __DIR__ . '/../Models/Log.php';
                Share::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_share_batch', '批量删除了 ' . count($ids) . ' 个分享', 'share', null);
            }
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 熔断分享（不删除记录，直接标记烧毁）
        if (isset($query['burn'])) {
            require_once __DIR__ . '/../Models/Log.php';
            Share::burn((int)$query['burn'], $userId);
            Log::admin($userId, 'burn_share', '手动熔断分享 id=' . (int)$query['burn'], 'share', (int)$query['burn']);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 删除分享
        if (isset($query['delete'])) {
            require_once __DIR__ . '/../Models/Log.php';
            Share::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_share', '删除了分享 id=' . (int)$query['delete'], 'share', (int)$query['delete']);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 创建分享
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_share'])) {
            $docId = (int)($_POST['doc_id'] ?? 0);
            $fileId = 0;
            // 检查是否是文件（表单里文件ID格式是 file_123）
            if (isset($_POST['doc_id']) && strpos($_POST['doc_id'], 'file_') === 0) {
                $docId = 0;
                $fileId = (int)str_replace('file_', '', $_POST['doc_id']);
            }
            $password = $_POST['password'] ?: null;
            
            // 处理访问次数限制（支持自定义）
            $maxClicks = (int)($_POST['max_clicks'] ?? -1);
            if ($_POST['max_clicks'] === 'custom' && isset($_POST['max_clicks_custom'])) {
                $maxClicks = (int)$_POST['max_clicks_custom'];
            }
            
            // 处理有效期（支持自定义天数）
            $expireSeconds = null;
            if ($_POST['expire_seconds'] !== '' && $_POST['expire_seconds'] !== 'custom') {
                $expireSeconds = (int)$_POST['expire_seconds'];
            } elseif ($_POST['expire_seconds'] === 'custom' && isset($_POST['expire_days_custom'])) {
                $expireSeconds = (int)$_POST['expire_days_custom'] * 86400; // 天数转秒数
            }
            
            $destroyDelay = isset($_POST["destroy_delay_enabled"]) ? (int)($_POST["destroy_delay_minutes"] ?? 0) : 0;
            
            $shareDomain = Setting::get('share_domain', '');
            if ($shareDomain === '') {
                $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
            }
            
            $token = Share::create($userId, $docId ?: null, $fileId ?: null, $password, $maxClicks, $expireSeconds, $destroyDelay);
            $shareUrl = "https://{$shareDomain}/?action=share&token=$token";
            
            Router::redirect("/" . Config::ADMIN_PATH . "/shares?created=1&token=$token");
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $shares = Share::getListByUser($userId, $search);
        $docs = Document::getList($userId);
        $files = File::getListByUser($userId);
        
        // 计算分享域名（用于模板）
        $shareDomain = Setting::get('share_domain', '');
        if ($shareDomain === '') {
            $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        }
        
        // 创建分享后 redirect 回来的显示（GET参数）
        if (isset($_GET['created']) && isset($_GET['token'])) {
            $createdShare = true;
            $shareUrl = "https://{$shareDomain}/?action=share&token=" . htmlspecialchars($_GET['token']);
        }
        
        include __DIR__ . '/../../templates/admin/shares.php';
    }
    
    /**
     * 文件管理
     */
    private static function files(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                require_once __DIR__ . '/../Models/Log.php';
                File::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_file_batch', '批量删除了 ' . count($ids) . ' 个文件', 'file', null);
            }
            Router::redirect("/" . Config::ADMIN_PATH . '/files');
            return;
        }
        
        // 单个删除
        if (isset($query['delete'])) {
            require_once __DIR__ . '/../Models/Log.php';
            $fileInfo = File::getById((int)$query['delete']);
            File::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_file', '删除了文件: ' . ($fileInfo['original_name'] ?? 'unknown'), 'file', (int)$query['delete']);
            Router::redirect("/" . Config::ADMIN_PATH . '/files');
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $files = File::getListByUser($userId, $search);
        
        include __DIR__ . '/../../templates/admin/files.php';
    }
    
    /**
     * 上传文件（支持 chunk 切片上传）
     */
    private static function uploadFile(array $query): void {
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
            Router::redirect("/" . Config::ADMIN_PATH . '/files');
            return;
        }

        if ($isImageUpload) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => '未收到文件']);
            exit;
        }

        include __DIR__ . '/../../templates/admin/upload.php';
    }

    /**
     * 处理切片上传
     */
    private static function uploadChunk(int $userId): void
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
    private static function settings(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 加载当前设置
        require_once __DIR__ . '/../Models/Setting.php';
        $homepageMode = Setting::get('homepage_mode', '404');
        $homepageHtml = Setting::get('homepage_html', '');
        $shareErrorMode = Setting::get('share_error_mode', 'default');
        $shareErrorHtml = Setting::get('share_error_html', '');
        $shareDomain = Setting::get('share_domain', '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 伪装设置保存
            if (isset($_POST['homepage_mode'])) {
                Setting::set('homepage_mode', $_POST['homepage_mode']);
                Setting::set('homepage_html', $_POST['homepage_html'] ?? '');
                Setting::set('share_error_mode', $_POST['share_error_mode'] ?? 'default');
                Setting::set('share_error_html', $_POST['share_error_html'] ?? '');
                $success = '伪装设置已保存';
                $homepageMode = $_POST['homepage_mode'];
                $homepageHtml = $_POST['homepage_html'] ?? '';
                $shareErrorMode = $_POST['share_error_mode'] ?? 'default';
                $shareErrorHtml = $_POST['share_error_html'] ?? '';
            }
            
            // 分享域名保存
            if (isset($_POST['share_domain'])) {
                Setting::set('share_domain', trim($_POST['share_domain']));
                $success = '分享域名已保存';
                $shareDomain = trim($_POST['share_domain']);
            }
            
            
            // 账户修改（需验证当前密码）
            $currentPass = $_POST['current_password'] ?? '';
            if (!empty($currentPass) && User::verifyPassword($userId, $currentPass)) {
                require_once __DIR__ . '/../Models/Log.php';
                
                $newUser = $_POST['new_username'] ?? '';
                $newPass = $_POST['new_password'] ?? '';
                
                if (!empty($newUser)) {
                    User::updateUsername($userId, $newUser);
                    $_SESSION['user']['username'] = $newUser;
                    Log::admin($userId, 'update_username', '管理员更改为: ' . $newUser, 'user', $userId);
                    $success = '用户名已更新';
                }
                if (!empty($newPass) && strlen($newPass) >= 8 && preg_match('/[A-Z]/', $newPass) && preg_match('/[a-z]/', $newPass) && preg_match('/\d/', $newPass)) {
                    User::updatePassword($userId, $newPass);
                    Log::admin($userId, 'update_password', '密码已修改', 'user', $userId);
                    $success = ($success ?? '') . ' 密码已更新';
                } elseif (!empty($newPass) && (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/\d/', $newPass))) {
                    $error = '新密码必须包含大小写字母和数字（例如：Abc12345）';
                    $success = $newUser ? '用户名已更新' : '';
                } elseif (!empty($newPass) && strlen($newPass) < 8) {
                    $error = '新密码至少8位';
                    $success = $newUser ? '用户名已更新' : '';
                }
                if (empty($newUser) && empty($newPass)) {
                    $error = '未填写新用户名或新密码';
                }
            } elseif (!empty($_POST['current_password'])) {
                $error = '当前密码错误，修改失败';
            }
        }
        
        // 传递给模板
        $homepageMode = $homepageMode ?? '404';
        $homepageHtml = $homepageHtml ?? '';
        $shareErrorMode = $shareErrorMode ?? 'default';
        $shareErrorHtml = $shareErrorHtml ?? '';
        
        include __DIR__ . '/../../templates/admin/settings.php';
    }
    
    /**
     * 审计日志
     */
    private static function logs(array $query): void {
        $userId = $_SESSION['user']['id'];
        require_once __DIR__ . '/../Models/Log.php';
        
        // 清空日志
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
            $type = $_POST['type'] ?? 'admin';
            $before = $_POST['clear_before'] ?: null;
            $count = Log::clear($type, $before);
            Log::admin($userId, 'clear_logs', "清空了 {$type} 日志 {$count} 条", 'system', null);
            Router::redirect("/" . Config::ADMIN_PATH . "/logs?type={$type}&cleared={$count}");
            return;
        }
        
        $type = $_GET['type'] ?? 'admin';
        $logs = Log::getList($type, 1, 200);
        
        include __DIR__ . '/../../templates/admin/logs.php';
    }
    
    /**
     * 生成并导出备份包（纯 PHP，零外部依赖）
     * - 有 ZipArchive → 打包成 .zip（SQL + 文件）
     * - 有 gzencode → 打包成 .sql.gz（压缩后下载）
     * - 都不行 → 直接下 .sql
     */
    /**
     * 安全设置页面
     */
    private static function security(array $query): void {
        require_once __DIR__ . '/../Models/Setting.php';
        require_once __DIR__ . '/../Config.php';
        require_once __DIR__ . '/../Security.php';
        
        $allowedExts = Setting::get('allowed_exts', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar,7z,mp4,mp3,webm,ogg,txt,md');
        $sessionTimeout = (int)Setting::get('session_timeout', 28800);
        $passwordPolicy = (int)Setting::get('password_policy', 1);
        
        $success = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'save_exts') {
                $newExts = trim($_POST['allowed_exts'] ?? '');
                $newExts = preg_replace('/\s+/', '', $newExts); // 去空格
                $newExts = rtrim($newExts, ',');
                Setting::set('allowed_exts', $newExts);
                $allowedExts = $newExts;
                $success = '文件扩展名白名单已更新';
            } elseif ($action === 'save_session') {
                $timeout = (int)($_POST['session_timeout'] ?? 28800);
                $policy = isset($_POST['password_policy']) ? 1 : 0;
                Setting::set('session_timeout', (string)$timeout);
                Setting::set('password_policy', (string)$policy);
                
                // 会话超时存入数据库，Config::init() 会自动从数据库读取
                $sessionTimeout = $timeout;
                $passwordPolicy = $policy;
                $success = '会话安全设置已更新';
            }
        }
        
        include __DIR__ . '/../../templates/admin/security.php';
    }
    
    /**
     * 登出
     */
    private static function logout(): void {
        User::logout();
        Router::redirect("/" . Config::ADMIN_PATH);
    }
}
