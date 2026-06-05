<?php
/**
 * 设置与安全控制器
 */

require_once __DIR__ . '/../../Models/Setting.php';
require_once __DIR__ . '/../../Models/Log.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Security.php';

class AdminSettings {
    public static function settings(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 加载当前设置
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
        }
        
        // 传递给模板
        include __DIR__ . '/../../../templates/admin/settings.php';
    }
    
    public static function security(array $query): void {
        require_once __DIR__ . '/../../Config.php';
        
        $allowedExts = Setting::get('allowed_exts', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,rar,7z,mp4,mp3,webm,ogg,txt,md');
        $sessionTimeout = (int)Setting::get('session_timeout', 28800);
        $passwordPolicy = (int)Setting::get('password_policy', 1);
        
        $success = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'save_exts') {
                $newExts = trim($_POST['allowed_exts'] ?? '');
                $newExts = preg_replace('/\s+/', '', $newExts);
                $newExts = rtrim($newExts, ',');
                Setting::set('allowed_exts', $newExts);
                $allowedExts = $newExts;
                $success = '文件扩展名白名单已更新';
            } elseif ($action === 'save_session') {
                $timeout = (int)($_POST['session_timeout'] ?? 28800);
                $policy = isset($_POST['password_policy']) ? 1 : 0;
                Setting::set('session_timeout', (string)$timeout);
                Setting::set('password_policy', (string)$policy);
                
                $sessionTimeout = $timeout;
                $passwordPolicy = $policy;
                $success = '会话安全设置已更新';
            }
        }
        
        include __DIR__ . '/../../../templates/admin/security.php';
    }
}
