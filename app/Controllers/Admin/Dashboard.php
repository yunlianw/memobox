<?php
/**
 * 仪表盘控制器
 */

require_once __DIR__ . '/../../Models/Document.php';
require_once __DIR__ . '/../../Models/Share.php';
require_once __DIR__ . '/../../Models/File.php';

class AdminDashboard {
    public static function render(): void {
        $userId = $_SESSION['user']['id'];
        $docs = Document::getList($userId);
        $shares = Share::getListByUser($userId);
        $files = File::getListByUser($userId);
        
        include __DIR__ . '/../../../templates/admin/dashboard.php';
    }
}
