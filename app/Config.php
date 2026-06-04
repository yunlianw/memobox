<?php
/**
 * 私密知识库系统 - 全局配置
 */

class Config {
    // 数据库配置
    const DB_HOST = '127.0.0.1';
    const DB_PORT = 3306;
    const DB_NAME = 'fx_5276_net';
    const DB_USER = 'fx_5276_net';
    const DB_PASS = 'WrGxBkTXBas4XYkC';
    const DB_CHARSET = 'utf8mb4';
    
    // 安全配置
    const ADMIN_PATH = 'yunlian';  // 自定义后台访问路径，安装时修改
    const MAX_LOGIN_FAIL = 3;       // 最大连续失败次数
    const BLOCK_DURATION = 3600;    // IP封锁时长（秒）
    
    // 存储配置
    const STORAGE_PATH = __DIR__ . '/../storage/files/';
    const ROOT_PATH = __DIR__ . '/..';   // 网站根目录
    
    // Token配置
    const TOKEN_LENGTH = 32;        // 分享Token长度
    const DEFAULT_PASS_LENGTH = 6;  // 默认密码长度
    
    // 会话超时（秒），可在后台安全设置调整
    const SESSION_TIMEOUT = 28800;   // 8小时
    
    // 分享过期预设（秒）
    const EXPIRE_PRESETS = [
        '1h'   => 3600,
        '6h'   => 21600,
        '24h'  => 86400,
        '7d'   => 604800,
        'forever' => null  // 永不过期
    ];
    
    public static function init() {
        // Session 安全配置
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', (string)self::SESSION_TIMEOUT);  // 会话超时时间
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
        
        date_default_timezone_set('Asia/Shanghai');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * 获取PDO数据库连接
     */
    public static function getDB(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=%s",
                self::DB_HOST, self::DB_PORT, self::DB_NAME, self::DB_CHARSET);
            $pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }
        return $pdo;
    }
}
