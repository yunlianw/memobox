<?php
/**
 * 系统设置模型
 */
class Setting {
    
    /**
     * 获取单个设置值
     */
    public static function get(string $key, string $default = ''): string {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }
    
    /**
     * 设置单个值
     */
    public static function set(string $key, string $value): void {
        $pdo = Config::getDB();
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
    }
    
    /**
     * 批量设置
     */
    public static function setMultiple(array $data): void {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }
    
    /**
     * 获取所有设置（键值对数组）
     */
    public static function getAll(): array {
        $pdo = Config::getDB();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
