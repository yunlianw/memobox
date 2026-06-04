<?php
/**
 * 分享模型
 */

class Share {
    /**
     * 创建分享
     */
    public static function create(int $userId, ?int $docId, ?int $fileId, ?string $password, int $maxClicks = -1, ?int $expireSeconds = null, int $destroyDelay = 0): string {
        $pdo = Config::getDB();
        $token = Security::generateToken();
        $passwordHash = ($password !== null && $password !== '') ? password_hash($password, PASSWORD_DEFAULT) : null;
        $expireTime = ($expireSeconds !== null) ? date('Y-m-d H:i:s', time() + $expireSeconds) : null;
        
        $stmt = $pdo->prepare(
            "INSERT INTO shares (user_id, doc_id, file_id, share_token, password_hash, max_clicks, expire_time, destroy_delay) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $docId, $fileId, $token, $passwordHash, $maxClicks, $expireTime, $destroyDelay]);
        
        return $token;
    }
    
    /**
     * 熔断分享（不删除记录，直接标记烧毁）
     */
    public static function burn(int $id, int $userId): bool {
        $pdo = Config::getDB();
        $share = self::getById($id, $userId);
        if (!$share) return false;
        
        // 立即烧毁：设置 max_clicks=1, click_count=1，让 validate() 判断为 max_clicks
        $stmt = $pdo->prepare("UPDATE shares SET max_clicks = 1, click_count = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
    
    /**
     * 通过ID获取分享（管理员用）
     */
    public static function getById(int $id, int $userId): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * 通过Token获取分享
     */
    public static function getByToken(string $token): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE share_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $share = $stmt->fetch();
        return $share ?: null;
    }
    
    /**
     * 验证分享是否有效（支持防手滑延迟销毁）
     * @return bool|string true=有效, 否则返回错误原因
     */
    public static function validate(array $share): bool|string {
        // 检查过期
        if ($share['expire_time'] !== null && strtotime($share['expire_time']) < time()) {
            return 'expired';
        }
        // 检查点击次数（考虑防手滑延迟）
        if ($share['max_clicks'] > 0) {
            $hasDelay = ($share['max_clicks'] === 1 && $share['destroy_delay'] > 0);
            if ($hasDelay && $share['first_view_time'] !== null) {
                // 防手滑模式：首次访问后 destroy_delay 分钟内不烧毁
                $firstView = strtotime($share['first_view_time']);
                $graceUntil = $firstView + ($share['destroy_delay'] * 60);
                if (time() < $graceUntil) {
                    // 延迟窗口内 → 视为有效
                    return true;
                }
                // 延迟窗口已过 → 判烧毁
                return 'max_clicks';
            }
            // 普通模式：click_count >= max_clicks 就烧毁
            if ($share['click_count'] >= $share['max_clicks']) {
                return 'max_clicks';
            }
        }
        return true;
    }
    
    /**
     * 增加点击计数（仅在无延迟或延迟窗口结束后才累加）
     */
    public static function incrementClicks(string $token): void {
        $pdo = Config::getDB();
        $share = self::getByToken($token);
        if (!$share) return;
        
        $hasDelay = ($share['max_clicks'] === 1 && $share['destroy_delay'] > 0);
        
        if ($hasDelay) {
            // 防手滑模式：只记录首次访问时间，不立即累加 click_count
            if ($share['first_view_time'] === null) {
                $stmt = $pdo->prepare("UPDATE shares SET first_view_time = NOW() WHERE share_token = ?");
                $stmt->execute([$token]);
            }
            return;
        }
        
        // 普通模式：直接累加
        $stmt = $pdo->prepare("UPDATE shares SET click_count = click_count + 1 WHERE share_token = ?");
        $stmt->execute([$token]);
    }
    
    /**
     * 获取用户的分享列表（支持搜索）
     */
    public static function getListByUser(int $userId, ?string $search = null): array {
        $pdo = Config::getDB();
        if ($search) {
            $stmt = $pdo->prepare(
                "SELECT s.*, d.title as doc_title, f.original_name as file_name 
                 FROM shares s 
                 LEFT JOIN documents d ON s.doc_id = d.id 
                 LEFT JOIN files f ON s.file_id = f.id 
                 WHERE s.user_id = ? 
                   AND (d.title LIKE ? OR f.original_name LIKE ? OR s.share_token LIKE ?)
                 ORDER BY s.created_at DESC"
            );
            $like = '%' . $search . '%';
            $stmt->execute([$userId, $like, $like, $like]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT s.*, d.title as doc_title, f.original_name as file_name 
                 FROM shares s 
                 LEFT JOIN documents d ON s.doc_id = d.id 
                 LEFT JOIN files f ON s.file_id = f.id 
                 WHERE s.user_id = ? 
                 ORDER BY s.created_at DESC"
            );
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * 批量删除分享
     */
    public static function deleteBatch(array $ids, int $userId): int {
        if (empty($ids)) return 0;
        $pdo = Config::getDB();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM shares WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($ids, [$userId]);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * 更新分享设置
     */
    public static function update(int $id, int $userId, ?string $password, int $maxClicks = -1, ?int $expireSeconds = null, int $destroyDelay = 0): bool {
        $pdo = Config::getDB();
        $passwordHash = ($password !== null && $password !== '') ? password_hash($password, PASSWORD_DEFAULT) : null;
        $expireTime = ($expireSeconds !== null) ? date('Y-m-d H:i:s', time() + $expireSeconds) : null;
        
        $sql = "UPDATE shares SET max_clicks = ?, expire_time = ?, destroy_delay = ?";
        $params = [$maxClicks, $expireTime, $destroyDelay];
        
        if ($passwordHash !== null) {
            $sql .= ", password_hash = ?";
            $params[] = $passwordHash;
        } elseif ($password === '') {
            // 空字符串 = 清除密码
            $sql .= ", password_hash = NULL";
        }
        // password = null = 不修改密码
        
        $sql .= " WHERE id = ? AND user_id = ?";
        $params[] = $id;
        $params[] = $userId;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 删除分享
     */
    public static function delete(int $id, int $userId): bool {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("DELETE FROM shares WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
