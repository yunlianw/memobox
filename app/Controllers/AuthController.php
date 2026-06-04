<?php
/**
 * 认证控制器 - 登录/登出
 */

require_once __DIR__ . '/../Models/User.php';

class AuthController {
    
    /**
     * 显示登录页面
     */
    public static function showLogin(): void {
        if (User::isLoggedIn()) {
            Router::redirect(Config::ADMIN_PATH . '/dashboard');
            return;
        }
        
        // 检查是否初次使用（没有管理员）
        $isFirstRun = !User::hasAny();
        
        include __DIR__ . '/../../templates/admin/login.php';
    }
    
    /**
     * 处理登录
     */
    public static function handleLogin(array $query): void {
        if (User::isLoggedIn()) {
            Router::redirect(Config::ADMIN_PATH . '/dashboard');
            return;
        }
        
        $username = $query['username'] ?? '';
        $password = $query['password'] ?? '';
        
        $user = User::login($username, $password);
        
        if ($user) {
            User::setSession($user);
            session_regenerate_id(true);
            $_SESSION['fingerprint'] = [
                'ua_hash'   => md5($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'ip_prefix' => implode('.', array_slice(explode('.', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 3)),
                'created'   => time()
            ];
            Router::redirect(Config::ADMIN_PATH . '/dashboard');
        } else {
            Security::recordFailedLogin();
            include __DIR__ . '/../../templates/admin/login.php';
        }
    }
    
    /**
     * 处理登出
     */
    public static function handleLogout(): void {
        User::logout();
        Router::redirect(Config::ADMIN_PATH);
    }
}
