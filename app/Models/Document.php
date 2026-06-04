<?php
/**
 * 文档模型
 */

class Document {
    /**
     * 获取用户的所有文档（支持搜索）
     */
    public static function getList(int $userId, ?string $search = null): array {
        $pdo = Config::getDB();
        if ($search) {
            $stmt = $pdo->prepare(
                "SELECT * FROM documents WHERE user_id = ? AND title LIKE ? ORDER BY updated_at DESC"
            );
            $stmt->execute([$userId, '%' . $search . '%']);
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM documents WHERE user_id = ? ORDER BY updated_at DESC"
            );
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * 批量删除文档
     */
    public static function deleteBatch(array $ids, int $userId): int {
        if (empty($ids)) return 0;
        $pdo = Config::getDB();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($ids, [$userId]);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * 获取单个文档
     */
    public static function getById(int $id, int $userId): ?array {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare(
            "SELECT * FROM documents WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $doc = $stmt->fetch();
        return $doc ?: null;
    }
    
    /**
     * 创建文档
     */
    public static function create(int $userId, string $title, string $content, string $type = 'markdown', ?string $linkUrl = null, ?array $attachedFiles = null): int {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO documents (user_id, title, content_md, content_type, link_url, attached_files) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $af = $attachedFiles ? json_encode($attachedFiles) : null;
        $stmt->execute([$userId, $title, $content, $type, $linkUrl, $af]);
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * 更新文档
     */
    public static function update(int $id, int $userId, array $data): bool {
        $pdo = Config::getDB();
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $values[] = $userId;
        
        $sql = "UPDATE documents SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * 删除文档
     */
    public static function delete(int $id, int $userId, bool $deletePhysical = true): bool {
        $pdo = Config::getDB();

        // 物理清理：找到文档内容中的图片URL，删除对应的 storage 文件\n        if ($deletePhysical) {\n            $doc = self::getById($id, $userId);\n            if ($doc) {\n                preg_match_all('#/yunlian/file\?id=(\d+)#', $doc['content_md'], $matchesById);\n                preg_match_all('#\?action=share_media&token=[^&]+&key=([a-f0-9]{32})#', $doc['content_md'], $matchesByKey);\n                require_once __DIR__ . '/File.php';\n                foreach (array_merge($matchesById[1] ?? [], $matchesByKey[1] ?? []) as $fileIdOrKey) {\n                    if (strlen($fileIdOrKey) === 32) {\n                        $file = \File::getByFileKey($fileIdOrKey);\n                    } else {\n                        $file = \File::getById((int)$fileIdOrKey);\n                    }\n                    if ($file) {\n                        $filePath = Config::STORAGE_PATH . $file['stored_name'];\n                        if (file_exists(\)) unlink(\);\n                    }\n                }\n            }\n        }\n
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
