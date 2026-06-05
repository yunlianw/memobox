<?php
/**
 * 设置与安全控制器
 */

require_once __DIR__ . '/../../Models/Setting.php';
require_once __DIR__ . '/../../Models/Log.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Security.php';

class AdminSettings {
    public static function handle(array $query): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/security') !== false) {
            self::security($query);
        } else {
            self::settings($query);
        }
    }

    public static function settings(array $query): void {
        $userId = $_SESSION['user']['id'];
        
        // 加载当前设置
        require_once __DIR__ . '/../../Models/Setting.php';
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
                require_once __DIR__ . '/../../Models/Log.php';
                
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
        
        include __DIR__ . '/../../../templates/admin/settings.php';
    }
    
    /**
     * 审计日志
     */


    public static function security(array $query): void {
        require_once __DIR__ . '/../../Models/Setting.php';
        require_once __DIR__ . '/../../Config.php';
        require_once __DIR__ . '/../../Security.php';
        
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
        
        include __DIR__ . '/../../../templates/admin/security.php';
    }
    
    /**
     * 登出
     */

}
