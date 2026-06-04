<?php
/**
 * 分享控制器 - 处理分享链接的访问
 */

require_once __DIR__ . '/../Models/Share.php';
require_once __DIR__ . '/../Models/Document.php';
require_once __DIR__ . '/../Models/File.php';
require_once __DIR__ . '/../Models/Log.php';

class ShareController {
    
    /**
     * 处理分享请求
     */
    public static function handle(array $query): void {
        // 调试：确认是否进入分享路由
        header('X-Share-Handler: YES');
        $token = $query['token'] ?? '';
        $password = $query['password'] ?? '';
        
        // 获取分享
        $share = Share::getByToken($token);
        if (!$share) {
            Router::show404();
            return;
        }
        
        // 验证分享
        $validation = Share::validate($share);
        if ($validation !== true) {
            // 记录失败访问
            $detail = 'token=' . $token . ' 结果=' . $validation;
            Log::access($token, $validation, $detail);
            
            if ($validation === 'expired') {
                self::showExpired();
            } elseif ($validation === 'max_clicks') {
                self::showBurned();
            } else {
                self::showExpired();
            }
            return;
        }
        
        // 密码验证
        if (isset($query['check_password'])) {
            // AJAX密码验证
            header('Content-Type: application/json');
            $valid = Security::verifyPassword($password, $share['password_hash']);
            echo json_encode(['valid' => $valid]);
            exit;
        }
        
        if ($share['password_hash'] !== null) {
            if (empty($password)) {
                self::showPasswordForm($token);
                return;
            }
            if (!Security::verifyPassword($password, $share['password_hash'])) {
                self::showPasswordForm($token, '密码错误');
                return;
            }
        }
        
        // 验证通过，记录访问日志
        $detail = 'token=' . $token;
        if ($share['doc_id']) {
            $detail .= ' doc_id=' . $share['doc_id'];
        } elseif ($share['file_id']) {
            $detail .= ' file_id=' . $share['file_id'];
        }
        $detail .= ' 动作=view';
        Log::access($token, 'view', $detail);
        
        // 增加点击计数（但不立即burn，交给validate()控制）
        Share::incrementClicks($token);
        
        // 验证通过，根据类型显示内容
        if ($share['doc_id']) {
            self::showDocument($share);
        } elseif ($share['file_id']) {
            self::downloadFile($share);
        } else {
            Router::show404();
        }
    }
    
    /**
     * 显示文档内容
     */
    private static function showDocument(array $share): void {
        $doc = Document::getById($share['doc_id'], $share['user_id']);
        if (!$doc) {
            Router::show404();
            return;
        }
        
        // 点击计数+1
        Share::incrementClicks($share['share_token']);
        
        if ($doc['content_type'] === 'link') {
            header("Location: " . $doc['link_url']);
            exit;
        }
        
        if ($doc['content_type'] === 'html') {
            $htmlContent = $doc['content_md'];
            $htmlContent = preg_replace_callback(
                '#/' . preg_quote(Config::ADMIN_PATH, '#') . '/file\?key=([a-f0-9]{32})#',
                function($m) use ($share) {
                    $file = \File::getByFileKey($m[1]);
                    if ($file) {
                        return '?action=share_media&token=' . $share['share_token'] . '&key=' . $file['file_key'];
                    }
                    return $m[0];
                },
                $htmlContent
            );
            if (ob_get_level()) {
                ob_end_clean();
            }
            echo $htmlContent;
            exit;
        }
        
        require_once __DIR__ . '/../Services/Parsedown.php';
        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($doc['content_md']);
        
        $htmlContent = preg_replace_callback(
            '#/' . preg_quote(Config::ADMIN_PATH, '#') . '/file\?key=([a-f0-9]{32})#',
            function($m) use ($share) {
                $file = \File::getByFileKey($m[1]);
                if ($file) {
                    return '?action=share_media&token=' . $share['share_token'] . '&key=' . $file['file_key'];
                }
                return $m[0];
            },
            $htmlContent
        );
        
        $htmlContent = preg_replace_callback(
            '#/' . preg_quote(Config::ADMIN_PATH, '#') . '/file\?id=(\d+)#',
            function($m) use ($share) {
                $file = \File::getById((int)$m[1]);
                if ($file && !empty($file['file_key'])) {
                    return '?action=share_media&token=' . $share['share_token'] . '&key=' . $file['file_key'];
                }
                return $m[0];
            },
            $htmlContent
        );
        
        $token = $share['share_token'];
        include __DIR__ . '/../../templates/share/show.php';
    }
    
    /**
     * 下载/预览文件
     */
    private static function downloadFile(array $share): void {
        $file = File::getById($share['file_id']);
        if (!$file) {
            Router::show404();
            return;
        }
        
        // 先增加点击计数
        Share::incrementClicks($share['share_token']);
        
        $ext = strtolower(pathinfo($file['stored_name'], PATHINFO_EXTENSION));
        $videoExts = ['mp4', 'webm', 'ogg', 'mov'];
        $isVideo = in_array($ext, $videoExts) || strpos($file['mime_type'], 'video/') === 0;
        
        if ($isVideo) {
            // 视频：用 streamView() 输出，供前端 <video> 标签播放
            File::streamView($file);
        } else {
            // 其他：流式下载
            File::streamDownload($file);
        }
    }
    
    /**
     * 显示密码输入页
     */
    private static function showPasswordForm(string $token, string $error = ''): void {
        include __DIR__ . '/../../templates/share/password.php';
    }
    
    /**
     * 显示次数已达上限（阅后即焚已烧毁）
     */
    private static function showBurned(): void {
        require_once __DIR__ . '/../Models/Setting.php';
        $mode = Setting::get('share_error_mode', 'default');
        if ($mode === 'custom') {
            $html = Setting::get('share_error_html', '');
            Router::renderCustomHtml($html);
        } else {
            header("HTTP/1.0 403 Forbidden");
            ?>
            <!DOCTYPE html>
            <html lang="zh-CN">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>链接已烧毁 - 私密知识库</title>
            <style>
            body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;display:flex;align-items:center;justify-content:center;color:#1d1d1f}
            .box{text-align:center;padding:40px}
            .box h1{font-size:72px;font-weight:700;color:#ff3b30;margin-bottom:8px}
            .box p{color:#86868b;font-size:14px}
            </style>
            </head>
            <body>
            <div class="box">
                <h1>🔥</h1>
                <p>该分享链接已烧毁（查看次数已用完）</p>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    /**
     * 显示已过期
     */
    private static function showExpired(): void {
        require_once __DIR__ . '/../Models/Setting.php';
        $mode = Setting::get('share_error_mode', 'default');
        if ($mode === 'custom') {
            $html = Setting::get('share_error_html', '');
            Router::renderCustomHtml($html);
        } else {
            header("HTTP/1.0 410 Gone");
            include __DIR__ . '/../../templates/share/expired.php';
        }
    }
    
    /**
     * 显示次数已达上限
     */
    private static function showLimitReached(): void {
        require_once __DIR__ . '/../Models/Setting.php';
        $mode = Setting::get('share_error_mode', 'default');
        if ($mode === 'custom') {
            $html = Setting::get('share_error_html', '');
            Router::renderCustomHtml($html);
        } else {
            header("HTTP/1.0 404 Not Found");
            include __DIR__ . '/../../templates/share/expired.php';
        }
    }

    /**
     * 分享媒体文件（Token联动锁）
     * 通过 token+key 双验证提供公开文件访问
     */
    public static function handleMedia(array $query): void {
        $token = $query['token'] ?? '';
        $fileKey = $query['key'] ?? '';
        
        // 参数有效性检查
        if (strlen($token) !== 32 || strlen($fileKey) !== 32) {
            http_response_code(404);
            exit;
        }
        
        // 获取并验证分享
        $share = Share::getByToken($token);
        if (!$share) {
            http_response_code(404);
            exit;
        }
        
        $validation = Share::validate($share);
        if ($validation !== true) {
            http_response_code(404);
            exit;
        }
        
        // 通过 key 获取文件
        $file = \File::getByFileKey($fileKey);
        if (!$file) {
            http_response_code(404);
            exit;
        }
        
        // 流式输出（图片 inline，视频 Range 播放）
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        \File::streamView($file);
    }
}
