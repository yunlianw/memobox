<?php
/**
 * MemoBox 私密知识库系统 - 统一入口（public/）
 * Nginx root 指向此目录
 */

require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Security.php';
require_once __DIR__ . '/../app/Router.php';

// 初始化
Config::init();
Security::initSession();

// 路由分发
Router::dispatch();
