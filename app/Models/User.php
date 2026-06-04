<?php
/**
 * 用户模型
 */

class User {
    /**
     * 验证登录
     * @return array|false 用户数据或false
     */
    public static function login(string $username, string $password) {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // 清除失败记录
        Security::clearFailedLogin();
        
        return $user;
    }
    
    /**
     * 设置登录Session
     */
    public static function setSession(array $user): void {
        $_SESSION['user'] = [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'login_at' => time()
        ];
    }
    
    /**
     * 检查是否已登录
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
    
    /**
     * 获取当前用户
     */
    public static function getCurrent(): ?array {
        return self::isLoggedIn() ? $_SESSION['user'] : null;
    }
    
    /**
     * 登出
     */
    public static function logout(): void {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
    
    /**
     * 检查数据库中是否有管理员
     */
    public static function hasAny(): bool {
        $pdo = Config::getDB();
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        $row = $stmt->fetch();
        return $row['cnt'] > 0;
    }
    
    /**
     * 创建管理员（安装用）
     */
    public static function create(string $username, string $password): bool {
        $pdo = Config::getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        return $stmt->execute([$username, $hash]);
    }
    
    /**
     * 通过ID获取用户
     */
    public static function getById(int $id): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    /**
     * 验证密码是否正确
     */
    public static function verifyPassword(int $id, string $password): bool {
        $user = self::getById($id);
        if (!$user) return false;
        return password_verify($password, $user['password_hash']);
    }
    
    /**
     * 更新用户名
     */
    public static function updateUsername(int $id, string $newUsername): bool {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        return $stmt->execute([$newUsername, $id]);
    }
    
    /**
     * 更新密码
     */
    public static function updatePassword(int $id, string $newPassword): bool {
        $pdo = Config::getDB();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }
}
