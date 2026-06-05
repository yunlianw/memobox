<?php
/**
 * 日志控制器
 */

require_once __DIR__ . '/../../Models/Log.php';

class AdminLog {
    public static function render(): void {
        $userId = $_SESSION['user']['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear') {
            $type = $_POST['type'] ?? 'admin';
            $before = $_POST['clear_before'] ?: null;
            $count = Log::clear($type, $before);
            Log::admin($userId, 'clear_logs', "清空了 {$type} 日志 {$count} 条", 'system', null);
            Router::redirect("/" . Config::ADMIN_PATH . "/logs?type={$type}&cleared={$count}");
            return;
        }
        
        $type = $_GET['type'] ?? 'admin';
        $logs = Log::getList($type, 1, 200);
        
        include __DIR__ . '/../../../templates/admin/logs.php';
    }
}
