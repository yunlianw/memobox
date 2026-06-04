-- KnowledgeBase Backup 20260604_022924
-- Host: 127.0.0.1:3306
-- DB: fx_5276_net


DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_md` longtext,
  `content_type` enum('markdown','link') DEFAULT 'markdown',
  `link_url` varchar(2000) DEFAULT NULL,
  `attached_files` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='知识库内容表';

INSERT INTO `documents` (`id`, `user_id`, `title`, `content_md`, `content_type`, `link_url`, `attached_files`, `created_at`, `updated_at`) VALUES ('1', '1', '测试文档', 'Hello World', 'markdown', NULL, NULL, '2026-06-03 18:42:10', '2026-06-03 18:42:10');
INSERT INTO `documents` (`id`, `user_id`, `title`, `content_md`, `content_type`, `link_url`, `attached_files`, `created_at`, `updated_at`) VALUES ('2', '1', '测评2026', '测试', 'markdown', '', '[2]', '2026-06-03 21:16:56', '2026-06-03 23:17:12');
INSERT INTO `documents` (`id`, `user_id`, `title`, `content_md`, `content_type`, `link_url`, `attached_files`, `created_at`, `updated_at`) VALUES ('3', '1', '202606', '测试测试gffffffffffffffffffffffff', 'markdown', '', '[4, 3]', '2026-06-03 23:24:56', '2026-06-04 00:55:22');

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_key` varchar(32) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stored_name` (`stored_name`),
  UNIQUE KEY `idx_file_key` (`file_key`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='文件表';

INSERT INTO `files` (`id`, `file_key`, `user_id`, `original_name`, `stored_name`, `file_size`, `mime_type`, `created_at`) VALUES ('1', 'cb59571f2cf1adbe0c7a1f15c84e9f06', '1', 'QQ20260602-213224.jpg', '8dcbb2f30d09aa8e_1780491134.jpg', '41434', 'image/jpeg', '2026-06-03 20:52:14');
INSERT INTO `files` (`id`, `file_key`, `user_id`, `original_name`, `stored_name`, `file_size`, `mime_type`, `created_at`) VALUES ('2', '49d468cc6836d082e18b6ab88a63b808', '1', '1780196160097.mp4', 'e71253be1b62c680_1780492583.mp4', '1123760', 'video/mp4', '2026-06-03 21:16:23');
INSERT INTO `files` (`id`, `file_key`, `user_id`, `original_name`, `stored_name`, `file_size`, `mime_type`, `created_at`) VALUES ('3', '2f2184e0995b97210c86e45fb8e211a5', '1', 'QQ20260603-231919.jpg', '90732bc6796cbd82_1780500240.jpg', '52246', 'image/jpeg', '2026-06-03 23:24:00');
INSERT INTO `files` (`id`, `file_key`, `user_id`, `original_name`, `stored_name`, `file_size`, `mime_type`, `created_at`) VALUES ('4', '225211f189f19fccd4cbe95a30bd8525', '1', 'QQ20260603-232016.jpg', '8134342210b32d22_1780500288.jpg', '48599', 'image/jpeg', '2026-06-03 23:24:48');

DROP TABLE IF EXISTS `ip_blocks`;
CREATE TABLE `ip_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `fail_count` int(11) DEFAULT '1',
  `blocked_until` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='IP封锁表';


DROP TABLE IF EXISTS `shares`;
CREATE TABLE `shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `doc_id` int(11) DEFAULT NULL,
  `file_id` int(11) DEFAULT NULL,
  `share_token` varchar(64) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `max_clicks` int(11) DEFAULT '-1',
  `click_count` int(11) DEFAULT '0',
  `first_view_time` datetime DEFAULT NULL,
  `destroy_delay` int(11) DEFAULT NULL COMMENT '防手滑延迟(分钟)',
  `expire_time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `share_token` (`share_token`),
  KEY `idx_token` (`share_token`),
  KEY `idx_expire` (`expire_time`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='分享记录表';

INSERT INTO `shares` (`id`, `user_id`, `doc_id`, `file_id`, `share_token`, `password_hash`, `max_clicks`, `click_count`, `first_view_time`, `destroy_delay`, `expire_time`, `created_at`) VALUES ('7', '1', '2', NULL, 'a5c38d01e0cec4ea17c8dc0005327ca3', NULL, '1', '0', '2026-06-03 23:46:53', '5', '2026-06-04 23:18:56', '2026-06-03 23:18:56');
INSERT INTO `shares` (`id`, `user_id`, `doc_id`, `file_id`, `share_token`, `password_hash`, `max_clicks`, `click_count`, `first_view_time`, `destroy_delay`, `expire_time`, `created_at`) VALUES ('9', '1', '3', NULL, 'ed267282555e7a8353f078089ed2e41a', NULL, '50', '18', NULL, '0', '2026-06-14 01:04:32', '2026-06-04 01:04:32');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES ('1', 'yunlian', '$2y$12$pCkdH46JUaHLKuT08bwZ2uswcSj5HhTGguBEJk9tVzqQ.QZaK6M2m', '2026-06-03 18:38:19');
