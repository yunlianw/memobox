<?php
/**
 * MemoBox 私密知识库系统 - 配置模板
 */

class Config {
    const DB_HOST = "127.0.0.1";
    const DB_PORT = 3306;
    const DB_NAME = "memobox";
    const DB_USER = "memobox_user";
    const DB_PASS = "your_password_here";
    const DB_CHARSET = "utf8mb4";

    const ADMIN_PATH = "yunlian";

    const MAX_LOGIN_FAIL = 3;
    const BLOCK_DURATION = 3600;

    const STORAGE_PATH = __DIR__ . "/../storage/files/";
    const ROOT_PATH = __DIR__ . "/..";

    const TOKEN_LENGTH = 32;
    const DEFAULT_PASS_LENGTH = 6;

    // 会话超时（秒），可在后台安全设置调整
    const SESSION_TIMEOUT = 28800;   // 8小时

    // 系统版本
    const VERSION = '1.0.4';

    const EXPIRE_PRESETS = [
        "1h"   => 3600,
        "6h"   => 21600,
        "24h"  => 86400,
        "7d"   => 604800,
        "forever" => null
    ];

    public static function init(): void {
        // Session 安全配置
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        
        // 会话超时：优先从数据库读取（后台可配置），否则用常量默认值
        $timeout = self::SESSION_TIMEOUT;
        try {
            $pdo = self::getDB();
            $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE `key` = 'session_timeout' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && is_numeric($row['value'])) {
                $timeout = (int)$row['value'];
            }
        } catch (\Exception $e) {
            // 数据库未就绪时使用默认值
        }
        ini_set('session.gc_maxlifetime', (string)$timeout);
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }
        
        date_default_timezone_set('Asia/Shanghai');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getDB(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $dsn = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=%s",
                self::DB_HOST, self::DB_PORT, self::DB_NAME, self::DB_CHARSET);
            $pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return $pdo;
    }
}
