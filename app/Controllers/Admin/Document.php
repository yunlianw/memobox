<?php
/**
 * 文档管理控制器
 */

require_once __DIR__ . '/../../Models/Document.php';
require_once __DIR__ . '/../../Models/Log.php';
require_once __DIR__ . '/../../Services/Parsedown.php';
require_once __DIR__ . '/../../Models/File.php';

class AdminDocument {
    public static function handle(string $action, array $query, array $parts): void {
        if ($action === 'documents') {
            self::documents($query);
        } elseif ($action === 'edit') {
            self::editDocument($query, $parts);
        } elseif ($action === 'preview') {
            self::previewDocument($query, $parts);
        }
    }

    public static function documents(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 单个删除
        if (isset($query['delete'])) {
            require_once __DIR__ . '/../../Models/Log.php';
            Document::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_document', '删除了文档 id=' . (int)$query['delete'], 'document', (int)$query['delete']);
            Router::redirect('/' . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                require_once __DIR__ . '/../../Models/Log.php';
                Document::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_document_batch', '批量删除了 ' . count($ids) . ' 个文档', 'document', null);
            }
            Router::redirect('/' . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $docs = Document::getList($userId, $search);
        
        include __DIR__ . '/../../../templates/admin/documents.php';
    }
    
    /**
     * 编辑文档
     */


    public static function editDocument(array $query, array $parts): void {
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
            
            Router::redirect('/' . Config::ADMIN_PATH . '/documents');
            return;
        }
        
        $doc = $docId > 0 ? Document::getById($docId, $userId) : null;
        include __DIR__ . '/../../../templates/admin/edit.php';
    }
    
    /**
     * 编辑分享（修改密码/次数/过期时间）
     */


    public static function previewDocument(array $query, array $parts): void {
        $userId = $_SESSION['user']['id'];
        $docId = isset($parts[1]) ? (int)$parts[1] : 0;
        if ($docId <= 0) {
            Router::show404();
            return;
        }
        
        // 禁用浏览器缓存
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $doc = Document::getById($docId, $userId);
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
            require_once __DIR__ . '/../../Services/Parsedown.php';
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
        include __DIR__ . '/../../../templates/admin/preview.php';
    }

    /**
     * 分享列表
     */

}
