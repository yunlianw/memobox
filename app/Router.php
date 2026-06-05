<?php
/**
 * 路由分发器
 * 解析URI并根据配置将请求路由到对应的控制器
 */

class Router {
    
    /**
     * 分发请求
     */
        public static function dispatch(): void {
        // 安装检测：install.lock 不存在时跳转
        $lockFile = dirname(__DIR__) . '/install.lock';
        if (!file_exists($lockFile)) {
            header('Location: /install/');
            exit;
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // 移除尾部斜杠（根路径除外）
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // 使用 $_GET 获取查询参数（Nginx + PHP-FPM 标准方式）
        $query = self::sanitizeQuery($_GET);
        // Fallback：如果 $_GET 为空，从 REQUEST_URI 手动解析（兼容 Nginx index 指令）
        if (empty($query) && !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            $qs = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if ($qs !== null && $qs !== '') {
                parse_str($qs, $query);
                $query = self::sanitizeQuery($query);
            }
        }
        
        // 安全检查：检查IP是否被封锁
        if (Security::isIPBlocked()) {
            self::show404();
            return;
        }
        
        // CSRF 验证：所有 POST 请求（排除登录接口）
        // 登录是 POST 到 /yunlian（无 action 参数），其余后台操作必须带 csrf_token
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isLogin = ($path === '/' . Config::ADMIN_PATH);
            if (!$isLogin) {
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!Security::verifyCsrfToken($csrfToken)) {
                    http_response_code(403);
                    die('CSRF validation failed');
                }
            }
        }
        
        // ===== 路由判断 =====
        
        // 0. 文件预览路由（后台管理，需登录）：/yunlian/file?key=xxx
        if ($path === '/' . Config::ADMIN_PATH . '/file') {
            require_once __DIR__ . '/Controllers/AdminController.php';
            if (!User::isLoggedIn()) {
                self::show404();
                return;
            }
            require_once __DIR__ . '/Models/File.php';
            $fileKey = $query['key'] ?? '';
            if (strlen($fileKey) !== 32) {
                self::show404();
                return;
            }
            $file = \File::getByFileKey($fileKey);
            if (!$file) {
                self::show404();
                return;
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            \File::streamView($file);
            return;
        }
        
        // 1. 后台路由：匹配 ADMIN_PATH
        $adminPath = '/' . Config::ADMIN_PATH;
        if ($path === $adminPath || strpos($path, $adminPath . '/') === 0) {
            self::routeAdmin($path, $query);
            return;
        }
        
        // 2. 分享路由：action=share&token=xxx
        if (isset($query['action']) && $query['action'] === 'share' && isset($query['token'])) {
            self::routeShare($query);
            return;
        }
        
        // 2b. 分享媒体路由（Token联动锁）：?action=share_media&token=xxx&key=xxx
        if (isset($query['action']) && $query['action'] === 'share_media' && isset($query['token']) && isset($query['key'])) {
            require_once __DIR__ . '/Controllers/ShareController.php';
            \ShareController::handleMedia($query);
            return;
        }
        
        // 3. 静态文件访问（CSS/JS等公共资源）
        if (preg_match('/^\/assets\//', $path)) {
            $filePath = Config::ROOT_PATH . $path;
            if (file_exists($filePath)) {
                self::serveStatic($filePath);
                return;
            }
        }
        
        // 3b. robots.txt
        if ($path === '/robots.txt') {
            $filePath = Config::ROOT_PATH . '/public/robots.txt';
            if (file_exists($filePath)) {
                self::serveStatic($filePath);
                return;
            }
        }
        
        // 4. 根路径 → 根据首页伪装设置处理
        if ($path === '/') {
            require_once __DIR__ . '/Models/Setting.php';
            $mode = Setting::get('homepage_mode', '404');
            if ($mode === 'custom') {
                $html = Setting::get('homepage_html', '');
                self::renderCustomHtml($html);
            } else {
                self::renderNginx404();
            }
            return;
        }
        
        // 5. 其他所有请求 → 404伪装
        self::show404();
    }
    
    /**
    * 安全过滤GET参数
    */
    private static function sanitizeQuery(array $query): array {
    $safe = [];
    foreach ($query as $key => $value) {
    $safe[Security::sanitize($key)] = Security::sanitize($value);
    }
    return $safe;
    }
    
    /**
    * 后台路由
    */
    private static function routeAdmin(string $path, array $query): void {
    require_once __DIR__ . '/Controllers/AdminController.php';
        
    // 解析后台子路径
    $adminPrefix = '/' . Config::ADMIN_PATH;
    $subPath = ltrim(substr($path, strlen($adminPrefix)), '/');
        
    AdminController::dispatch($subPath, $query);
    }
    
    /**
    * 分享路由
    */
    private static function routeShare(array $query): void {
    require_once __DIR__ . '/Controllers/ShareController.php';
    ShareController::handle($query);
    }
    
    /**
    * 文件预览路由
    */
    private static function routeFileView(array $query): void {
    require_once __DIR__ . '/Models/File.php';
    $id = (int)($query['id'] ?? 0);
    if ($id <= 0) {
    self::show404();
    return;
    }
    $file = \File::getById($id);
    if (!$file) {
    self::show404();
    return;
    }
    \File::streamView($file);
    }

    /**
    * 显示404
    */
    /**
    * 显示404（根据分享错误设置）
    * 用于分享 Token 不存在/已删除/已烧毁等场景
    */
    public static function show404(?string $token = null): void {
    require_once __DIR__ . '/Models/Setting.php';
    $mode = Setting::get('share_error_mode', 'default');
        
    if ($mode === 'custom') {
    // 自定义 HTML 提示页
    $html = Setting::get('share_error_html', '');
    self::renderCustomHtml($html);
    } else {
    // 默认 404 彻底装死
    self::renderNginx404();
    }
    }
    
    /**
    * 高仿 Nginx 404 页面
    */
    private static function renderNginx404(): void {
    header("HTTP/1.0 404 Not Found");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <title>404 Not Found</title>
    <style>
    body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;background:#fff;color:#555;text-align:center;padding:80px 20px}
    h1{font-size:36px;color:#333;margin-bottom:10px}
    hr{border:0;border-top:1px solid #eee;margin:20px 0}
    p{font-size:14px;line-height:1.6}
    </style>
    </head>
    <body>
    <h1>404 Not Found</h1>
    <hr>
    <p>nginx/1.24.0</p>
    </body>
    </html>
    <?php
    exit;
    }
    
    /**
    * 渲染自定义 HTML
    */
    public static function renderCustomHtml(string $html): void {
    echo $html;
    exit;
    }
    
    /**
    * 显示404（默认，不改变行为）
    */
    public static function show404Old(): void {
    header("HTTP/1.0 404 Not Found");
    include __DIR__ . '/../templates/admin/404.php';
    exit;
    }
    
    /**
    * 重定向
    */
    public static function redirect(string $url): void {
    header("Location: $url");
    exit;
    }
    
    /**
    * 提供静态文件
    */
    private static function serveStatic(string $filePath): void {
    // 规范化路径，防止 ../ 穿越
    $realPath = realpath($filePath);
    $allowedDir = realpath(Config::ROOT_PATH . '/assets/');
    
    if ($realPath === false || $allowedDir === false || strpos($realPath, $allowedDir) !== 0) {
        self::show404();
        return;
    }
    
    if (!file_exists($realPath)) {
        self::show404();
        return;
    }
    
    $mimeTypes = [
    'css' => 'text/css',
    'js'  => 'application/javascript',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'svg' => 'image/svg+xml',
    'txt' => 'text/plain',
    ];
        
    $ext = pathinfo($realPath, PATHINFO_EXTENSION);
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
    // robots.txt 不缓存，其他静态资源可缓存
    $isRobots = basename($realPath) === 'robots.txt';
    $cacheHeader = $isRobots ? 'no-cache' : 'public, max-age=86400';
        
    header("Content-Type: $mime");
    header("Cache-Control: $cacheHeader");
    readfile($realPath);
    exit;
    }
    }
