<?php
/**
 * 后台管理控制器 - 路由分发器
 * 根据子路径分发到对应子控制器
 */

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Document.php';
require_once __DIR__ . '/../Models/Share.php';
require_once __DIR__ . '/../Models/File.php';
require_once __DIR__ . '/../Security.php';

class AdminController {
    
    /**
     * 路由分发
     */
    public static function dispatch(string $subPath, array $query): void {
        // 登录请求优先处理（不检查登录状态）
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isLoginPost = ($_SERVER['REQUEST_METHOD'] === 'POST' && $subPath === '' && isset($_POST['username']));
        
        if ($isLoginPost) {
            self::showLogin($query);
            return;
        }
        
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
                require_once __DIR__ . '/Admin/Dashboard.php';
                AdminDashboard::render();
                break;
            case 'documents':
            case 'edit':
            case 'preview':
                require_once __DIR__ . '/Admin/Document.php';
                AdminDocument::handle($action, $query, $parts);
                break;
            case 'shares':
                require_once __DIR__ . '/Admin/Share.php';
                AdminShare::shares($query);
                break;
            case 'edit_share':
                require_once __DIR__ . '/Admin/Share.php';
                AdminShare::editShare($query);
                break;
            case 'files':
                require_once __DIR__ . '/Admin/File.php';
                AdminFile::handle($query);
                break;
            case 'upload':
                require_once __DIR__ . '/Admin/File.php';
                AdminFile::uploadFile($query);
                break;
            case 'settings':
                require_once __DIR__ . '/Admin/Settings.php';
                AdminSettings::settings($query);
                break;
            case 'security':
                require_once __DIR__ . '/Admin/Settings.php';
                AdminSettings::security($query);
                break;
            case 'account':
                require_once __DIR__ . '/Admin/Account.php';
                AdminAccount::render();
                break;
            case 'logs':
                require_once __DIR__ . '/Admin/Log.php';
                AdminLog::render();
                break;
            case 'logout':
                User::logout();
                Router::redirect("/" . Config::ADMIN_PATH);
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($isFirstRun) {
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
            
            if (isset($user) && $user) {
                User::setSession($user);
                Security::clearFailedLogin();
                
                require_once __DIR__ . '/../Models/Log.php';
                Log::admin($user['id'], 'login', '管理员登录成功', 'system', null);
                
                Router::redirect('/' . Config::ADMIN_PATH . '/dashboard');
            } else {
                if (!isset($error)) {
                    Security::recordFailedLogin();
                    $error = '用户名或密码错误';
                }
                include __DIR__ . '/../../templates/admin/login.php';
            }
        } else {
            include __DIR__ . '/../../templates/admin/login.php';
        }
    }
}
