<?php
/**
 * 审计日志 Model
 * - admin 日志：登录、改密码、删除、熔断等
 * - access 日志：分享 Token 访问记录
 */
class Log
{
    /**
     * 记录管理员操作
     */
    public static function admin(int $userId, string $action, ?string $detail = null, ?string $targetType = null, ?int $targetId = null): void
    {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO logs (log_type, user_id, action, target_type, target_id, detail, ip_address) 
             VALUES ('admin', ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $action, $targetType, $targetId, $detail, $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    /**
     * 记录分享访问（前台）
     */
    public static function access(string $token, string $action, ?string $detail = null, ?string $ip = null): void
    {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO logs (log_type, action, target_type, detail, ip_address) 
             VALUES ('access', ?, 'share', ?, ?)"
        );
        $stmt->execute([$action, $detail, $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '')]);
    }

    /**
     * 获取日志列表
     * @param string $type admin|access
     */
    public static function getList(string $type, int $page = 1, int $limit = 50, ?string $beforeDate = null): array
    {
        $pdo = Config::getDB();
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM logs WHERE log_type = ?";
        $params = [$type];
        if ($beforeDate) {
            $sql .= " AND created_at < ?";
            $params[] = $beforeDate;
        }
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 清空日志
     */
    public static function clear(string $type, ?string $beforeDate = null): int
    {
        $pdo = Config::getDB();
        $sql = "DELETE FROM logs WHERE log_type = ?";
        $params = [$type];
        if ($beforeDate) {
            $sql .= " AND created_at < ?";
            $params[] = $beforeDate;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * 获取日志统计
     */
    public static function getStats(): array
    {
        $pdo = Config::getDB();
        $stmt = $pdo->query("SELECT log_type, COUNT(*) as cnt FROM logs GROUP BY log_type");
        return $stmt->fetchAll();
    }
}
