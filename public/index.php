<?php
/**
 * MemoBox 私密知识库系统 - 统一入口（public/）
 * Nginx root 指向此目录
 */

// 安装检测：如果 Config.php 不存在，跳转到安装程序
if (!file_exists(__DIR__ . '/../app/Config.php')) {
    // 避免循环跳转：如果已经是访问 install/ 就放行
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    if (strpos($path, '/install/') !== 0) {
        header('Location: /install/');
        exit;
    }
}

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Security.php';
require_once __DIR__ . '/../app/Router.php';

// 初始化
Config::init();
Security::initSession();

// 路由分发
Router::dispatch();
