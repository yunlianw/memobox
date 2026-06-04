<?php
/**
 * MemoBox 私密知识库系统 - 统一入口（public/）
 * Nginx root 指向此目录
 */

// 安装检测：如果 install.lock 不存在，跳转到安装程序
$lockFile = __DIR__ . '/../install.lock';
if (!file_exists($lockFile)) {
    header('Location: /install/');
    exit;
}

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Security.php';
require_once __DIR__ . '/../app/Router.php';

// 初始化
Config::init();
Security::initSession();

// 路由分发
Router::dispatch();
