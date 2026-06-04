<?php
/**
 * 输入过滤与安全工具
 */

class Security {
    /**
     * 初始化Session并绑定设备指纹
     */
    public static function initSession(): void {
        Config::init();
        
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = [
                'ua_hash'   => md5($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'ip_prefix' => self::getIPPrefix(),
                'created'   => time()
            ];
        } else {
            // 检查Session劫持
            if (!self::validateFingerprint()) {
                session_destroy();
                session_start();
                $_SESSION['fingerprint'] = [
                    'ua_hash'   => md5($_SERVER['HTTP_USER_AGENT'] ?? ''),
                    'ip_prefix' => self::getIPPrefix(),
                    'created'   => time()
                ];
            }
        }
    }
    
    /**
     * 验证设备指纹
     */
    private static function validateFingerprint(): bool {
        $fp = $_SESSION['fingerprint'];
        $currentUAHash = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
        $currentIPPrefix = self::getIPPrefix();
        
        if ($fp['ua_hash'] !== $currentUAHash) {
            return false;
        }
        if ($fp['ip_prefix'] !== $currentIPPrefix) {
            return false;
        }
        return true;
    }
    
    /**
     * 获取IP前三段作为前缀
     */
    private static function getIPPrefix(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3));
    }
    
    /**
     * 过滤输入（递归处理数组）
     * @param mixed $input
     * @return mixed
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            $safe = [];
            foreach ($input as $key => $value) {
                $safe[self::sanitize($key)] = self::sanitize($value);
            }
            return $safe;
        }
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $input;
    }
    
    /**
     * 检查IP是否被封锁
     * @return bool true=被封锁
     */
    public static function isIPBlocked(): bool {
        $pdo = Config::getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM ip_blocks 
             WHERE ip = ? AND blocked_until > NOW()"
        );
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        
        return ($row['cnt'] > 0);
    }
    
    /**
     * 记录登录失败
     */
    public static function recordFailedLogin(): void {
        $pdo = Config::getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // 检查是否已存在记录
        $stmt = $pdo->prepare("SELECT * FROM ip_blocks WHERE ip = ?");
        $stmt->execute([$ip]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $newCount = $existing['fail_count'] + 1;
            $blockedUntil = ($newCount >= Config::MAX_LOGIN_FAIL) 
                ? date('Y-m-d H:i:s', time() + Config::BLOCK_DURATION) 
                : $existing['blocked_until'];
            
            $stmt = $pdo->prepare(
                "UPDATE ip_blocks SET fail_count = ?, blocked_until = ? WHERE ip = ?"
            );
            $stmt->execute([$newCount, $blockedUntil, $ip]);
        } else {
            // 第一次失败，只记录次数不封锁
            $stmt = $pdo->prepare(
                "INSERT INTO ip_blocks (ip, fail_count, blocked_until) VALUES (?, 1, '1000-01-01 00:00:00')"
            );
            $stmt->execute([$ip]);
        }
    }
    
    /**
     * 清除登录失败记录（登录成功时）
     */
    public static function clearFailedLogin(): void {
        $pdo = Config::getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $pdo->prepare("DELETE FROM ip_blocks WHERE ip = ?");
        $stmt->execute([$ip]);
    }
    
    /**
     * 生成随机Token
     */
    public static function generateToken(int $length = Config::TOKEN_LENGTH): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * 生成CSRF Token（存Session）
     */
    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 验证CSRF Token
     */
    public static function verifyCsrfToken(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * 验证分享密码
     * @param string $input 用户输入的密码
     * @param string|null $hash 数据库存储的hash
     * @return bool
     */
    public static function verifyPassword(?string $input, ?string $hash): bool {
        if ($hash === null || $hash === '') {
            return true; // 无密码
        }
        if ($input === null || $input === '') {
            return false;
        }
        return password_verify($input, $hash);
    }
}
