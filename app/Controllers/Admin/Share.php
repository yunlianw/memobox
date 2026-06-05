<?php
/**
 * 分享管理控制器
 */

require_once __DIR__ . '/../../Models/Share.php';
require_once __DIR__ . '/../../Models/Log.php';
require_once __DIR__ . '/../../Models/Setting.php';
require_once __DIR__ . '/../../Models/Document.php';
require_once __DIR__ . '/../../Models/File.php';

class AdminShare {
    public static function handle(array $query): void {
        $action = $_GET['action'] ?? '';
        if ($action === 'edit_share') {
            self::editShare($query);
        } else {
            self::shares($query);
        }
    }
    
    public static function shares(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 批量删除
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_batch'])) {
            $ids = isset($_POST['selected']) ? array_map('intval', $_POST['selected']) : [];
            if (!empty($ids)) {
                Share::deleteBatch($ids, $userId);
                Log::admin($userId, 'delete_share_batch', '批量删除了 ' . count($ids) . ' 个分享', 'share', null);
            }
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 熔断分享
        if (isset($query['burn'])) {
            Share::burn((int)$query['burn'], $userId);
            Log::admin($userId, 'burn_share', '手动熔断分享 id=' . (int)$query['burn'], 'share', (int)$query['burn']);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 删除分享
        if (isset($query['delete'])) {
            Share::delete((int)$query['delete'], $userId);
            Log::admin($userId, 'delete_share', '删除了分享 id=' . (int)$query['delete'], 'share', (int)$query['delete']);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }
        
        // 创建分享
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_share'])) {
            $docId = (int)($_POST['doc_id'] ?? 0);
            $fileId = 0;
            if (isset($_POST['doc_id']) && strpos($_POST['doc_id'], 'file_') === 0) {
                $docId = 0;
                $fileId = (int)str_replace('file_', '', $_POST['doc_id']);
            }
            $password = $_POST['password'] ?: null;
            
            $maxClicks = (int)($_POST['max_clicks'] ?? -1);
            if ($_POST['max_clicks'] === 'custom' && isset($_POST['max_clicks_custom'])) {
                $maxClicks = (int)$_POST['max_clicks_custom'];
            }
            
            $expireSeconds = null;
            if ($_POST['expire_seconds'] !== '' && $_POST['expire_seconds'] !== 'custom') {
                $expireSeconds = (int)$_POST['expire_seconds'];
            } elseif ($_POST['expire_seconds'] === 'custom' && isset($_POST['expire_days_custom'])) {
                $expireSeconds = (int)$_POST['expire_days_custom'] * 86400;
            }
            
            $destroyDelay = isset($_POST["destroy_delay_enabled"]) ? (int)($_POST["destroy_delay_minutes"] ?? 0) : 0;
            
            $shareDomain = Setting::get('share_domain', '');
            if ($shareDomain === '') {
                $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
            }
            
            $token = Share::create($userId, $docId ?: null, $fileId ?: null, $password, $maxClicks, $expireSeconds, $destroyDelay);
            
            Router::redirect("/" . Config::ADMIN_PATH . "/shares?created=1&token=$token");
            return;
        }
        
        $search = $_GET['search'] ?? null;
        $shares = Share::getListByUser($userId, $search);
        $docs = Document::getList($userId);
        $files = File::getListByUser($userId);
        
        $shareDomain = Setting::get('share_domain', '');
        if ($shareDomain === '') {
            $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        }
        
        if (isset($_GET['created']) && isset($_GET['token'])) {
            $createdShare = true;
            $shareUrl = "https://{$shareDomain}/?action=share&token=" . htmlspecialchars($_GET['token']);
        }
        
        include __DIR__ . '/../../../templates/admin/shares.php';
    }
    
    public static function editShare(array $query): void {
        $userId = $_SESSION['user']['id'];
        $shareId = (int)($_POST['id'] ?? ($query['id'] ?? 0));
        
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$shareId, $userId]);
        $share = $stmt->fetch();

        if (!$share) {
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? null;
            
            $maxClicks = (int)($_POST['max_clicks'] ?? -1);
            if ($_POST['max_clicks'] === 'custom' && isset($_POST['max_clicks_custom'])) {
                $maxClicks = (int)$_POST['max_clicks_custom'];
            }
            
            $expireSeconds = null;
            if ($_POST['expire_seconds'] !== '' && $_POST['expire_seconds'] !== 'custom') {
                $expireSeconds = (int)$_POST['expire_seconds'];
            } elseif ($_POST['expire_seconds'] === 'custom' && isset($_POST['expire_days_custom'])) {
                $expireSeconds = (int)$_POST['expire_days_custom'] * 86400;
            }
            
            $destroyDelay = isset($_POST['destroy_delay_enabled']) ? (int)($_POST['destroy_delay_minutes'] ?? 0) : 0;

            Share::update($shareId, $userId, $password, $maxClicks, $expireSeconds, $destroyDelay);
            Router::redirect("/" . Config::ADMIN_PATH . '/shares');
            return;
        }

        $currentExpireSeconds = null;
        if ($share['expire_time']) {
            $diff = strtotime($share['expire_time']) - time();
            $currentExpireSeconds = $diff > 0 ? $diff : null;
        }

        $shareDomain = Setting::get('share_domain', '');
        if ($shareDomain === '') {
            $shareDomain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
        }
        
        include __DIR__ . '/../../../templates/admin/edit_share.php';
    }
}
