<?php
/**
 * 账户设置控制器
 */

require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Models/Log.php';
require_once __DIR__ . '/../../Models/Setting.php';
require_once __DIR__ . '/../../Config.php';

class AdminAccount {
    public static function render(): void {
        $userId = $_SESSION['user']['id'];
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPass = $_POST['current_password'] ?? '';
            $newUser = trim($_POST['new_username'] ?? '');
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';
            
            // 验证当前密码
            if (!User::verifyPassword($userId, $currentPass)) {
                $error = '当前密码错误，请检查';
            } elseif (empty($newUser) && empty($newPass)) {
                $error = '请填写新用户名或新密码';
            } else {
                // 修改用户名
                if (!empty($newUser)) {
                    User::updateUsername($userId, $newUser);
                    $_SESSION['user']['username'] = $newUser;
                    Log::admin($userId, 'update_username', '管理员用户名更改为: ' . $newUser, 'user', $userId);
                    $success = '用户名已更新';
                }
                
                // 修改密码（需要二次确认）
                if (!empty($newPass)) {
                    if ($newPass !== $confirmPass) {
                        $error = '两次输入的新密码不一致';
                    } elseif (Setting::get('password_policy', 1) == 1) {
                        // 安全设置里勾选了强制复杂密码
                        if (strlen($newPass) < 8) {
                            $error = '新密码至少8位';
                        } elseif (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/\d/', $newPass)) {
                            $error = '新密码必须包含大小写字母和数字（例如：Abc12345）';
                        }
                    } else {
                        // 未勾选强制策略，只校验长度
                        if (strlen($newPass) < 6) {
                            $error = '新密码至少6位';
                        }
                    }
                    
                    if (empty($error)) {
                        User::updatePassword($userId, $newPass);
                        Log::admin($userId, 'update_password', '密码已修改', 'user', $userId);
                        $success = ($success ? $success . '，' : '') . '密码已更新';
                    }
                }
            }
        }
        
        // 传递给模板
        $passwordPolicy = (int)Setting::get('password_policy', 1);
        
        include __DIR__ . '/../../../templates/admin/account.php';
    }
}
