<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>编辑分享 - 私密知识库</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user"><a href="/<?= Config::ADMIN_PATH ?>/" style="color:#86868b;font-size:13px;">返回</a></div>
</div>
<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares" class="active">分享管理</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
</div>

<div class="container">
    <a href="/<?= Config::ADMIN_PATH ?>/shares" style="font-size:13px;color:#007aff;text-decoration:none;display:inline-block;margin-bottom:16px;">← 返回分享列表</a>
    <h2 style="font-size:20px;margin-bottom:20px;">✏️ 编辑分享</h2>

    <div style="background:#fff;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <div class="form-group">
                <label>分享链接（不可修改）</label>
                <input type="text" value="https://<?= htmlspecialchars($shareDomain ?? ($_SERVER['HTTP_HOST'] ?? '127.0.0.1')) ?>/?action=share&token=<?= htmlspecialchars($share['share_token']) ?>" 
                       style="width:100%;padding:8px;border:1px solid #e0e0e0;border-radius:6px;font-size:13px;color:#86868b;background:#f9f9f9;" readonly>
            </div>

            <div class="form-group">
                <label>访问密码（留空 = 不修改，输入空值 = 清除密码）</label>
                <input type="text" name="password" placeholder="留空则不修改密码" 
                       style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                <small style="color:#86868b;">当前：<?= $share['password_hash'] ? '已设置密码' : '无密码' ?></small>
            </div>

            <div class="form-group">
                <label>有效期</label>
                <select name="expire_seconds" id="edit-expire-select" onchange="toggleEditExpireCustom(this)" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="" <?= ($currentExpireSeconds === null) ? 'selected' : '' ?>>永久有效</option>
                    <option value="3600" <?= ($currentExpireSeconds && $currentExpireSeconds <= 3600) ? 'selected' : '' ?>>1小时</option>
                    <option value="21600" <?= ($currentExpireSeconds && $currentExpireSeconds > 3600 && $currentExpireSeconds <= 21600) ? 'selected' : '' ?>>6小时</option>
                    <option value="86400" <?= ($currentExpireSeconds && $currentExpireSeconds > 21600 && $currentExpireSeconds <= 86400) ? 'selected' : '' ?>>24小时</option>
                    <option value="604800" <?= ($currentExpireSeconds && $currentExpireSeconds > 86400 && $currentExpireSeconds <= 604800) ? 'selected' : '' ?>>7天</option>
                    <option value="custom" <?= ($currentExpireSeconds && $currentExpireSeconds > 604800) ? 'selected' : '' ?>>自定义天数</option>
                </select>
                <div id="edit-expire-custom" style="display:<?= ($currentExpireSeconds && $currentExpireSeconds > 604800) ? 'block' : 'none' ?>;margin-top:8px;">
                    <input type="number" name="expire_days_custom" id="edit-expire-days-input" value="<?= $currentExpireSeconds ? round($currentExpireSeconds / 86400) : 30 ?>" min="1" max="365" style="width:100px;padding:6px;border:1px solid #d2d2d7;border-radius:6px;font-size:13px;">
                    <small style="color:#86868b;margin-left:6px;">天</small>
                </div>
                <?php if ($share['expire_time']): ?>
                    <small style="color:#86868b;">当前到期：<?= date('Y-m-d H:i', strtotime($share['expire_time'])) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>访问次数限制</label>
                <select name="max_clicks" id="edit-clicks-select" onchange="toggleEditClicksCustom(this)" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="-1" <?= ($share['max_clicks'] == -1) ? 'selected' : '' ?>>无限制</option>
                    <option value="1" <?= ($share['max_clicks'] == 1 || $share['destroy_delay'] > 0) ? 'selected' : '' ?>>1次（阅后即焚）</option>
                    <option value="3" <?= ($share['max_clicks'] == 3) ? 'selected' : '' ?>>3次</option>
                    <option value="5" <?= ($share['max_clicks'] == 5) ? 'selected' : '' ?>>5次</option>
                    <option value="10" <?= ($share['max_clicks'] == 10) ? 'selected' : '' ?>>10次</option>
                    <option value="custom" <?= ($share['max_clicks'] > 10 && $share['max_clicks'] != -1) ? 'selected' : '' ?>>自定义次数</option>
                </select>
                <div id="edit-clicks-custom" style="display:<?= ($share['max_clicks'] > 10 && $share['max_clicks'] != -1) ? 'block' : 'none' ?>;margin-top:8px;">
                    <input type="number" name="max_clicks_custom" id="edit-clicks-input" value="<?= $share['max_clicks'] > 10 ? $share['max_clicks'] : 50 ?>" min="1" max="9999" style="width:100px;padding:6px;border:1px solid #d2d2d7;border-radius:6px;font-size:13px;">
                    <small style="color:#86868b;margin-left:6px;">次（输入 50 表示最多访问50次）</small>
                </div>
                <?php if ($share['max_clicks'] > 0): ?>
                    <small style="color:#86868b;">已用 <?= $share['click_count'] ?> / <?= $share['max_clicks'] ?> 次</small>
                <?php endif; ?>
            </div>

            <!-- 防手滑延迟销毁 -->
            <div id="edit-delay-field" style="margin-top:12px;background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;<?= ($share['max_clicks'] == 1 || $share['destroy_delay'] > 0) ? '' : 'display:none;' ?>">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="destroy_delay_enabled" value="1" <?= ($share['destroy_delay'] > 0) ? 'checked' : '' ?> onchange="toggleEditDelayMinutes(this)">
                    🛡️ 防手滑延迟销毁
                </label>
                <div id="edit-delay-minutes" style="margin-top:8px;<?= ($share['destroy_delay'] > 0) ? '' : 'display:none;' ?>">
                    <label style="font-size:12px;color:#795548;">延迟时间（分钟）：</label>
                    <input type="number" name="destroy_delay_minutes" value="<?= $share['destroy_delay'] ?: 5 ?>" min="1" max="60" style="width:80px;padding:6px;border:1px solid #ffe082;border-radius:6px;font-size:13px;">
                    <small style="color:#86868b;margin-left:6px;">在延迟窗口内刷新不会烧毁链接</small>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;gap:12px;">
                <button type="submit" class="btn">保存修改</button>
                <a href="/<?= Config::ADMIN_PATH ?>/shares" style="padding:8px 16px;background:#f5f5f7;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;text-decoration:none;color:#1d1d1f;font-size:14px;">取消</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEditExpireCustom(select) {
    document.getElementById('edit-expire-custom').style.display = (select.value === 'custom') ? 'block' : 'none';
}
function toggleEditClicksCustom(select) {
    const val = select.value;
    document.getElementById('edit-clicks-custom').style.display = (val === 'custom') ? 'block' : 'none';
    toggleEditDelayField2(select);
}
function toggleEditDelayField2(select) {
    const val = parseInt(select.value);
    document.getElementById('edit-delay-field').style.display = (val === 1) ? '' : 'none';
}
function toggleEditDelayMinutes(checkbox) {
    document.getElementById('edit-delay-minutes').style.display = checkbox.checked ? '' : 'none';
}
// 页面加载时初始化自定义字段显示
document.addEventListener('DOMContentLoaded', function() {
    const expireSelect = document.getElementById('edit-expire-select');
    if (expireSelect) toggleEditExpireCustom(expireSelect);
    const clicksSelect = document.getElementById('edit-clicks-select');
    if (clicksSelect) toggleEditClicksCustom(clicksSelect);
    const delayCheckbox = document.querySelector('input[name="destroy_delay_enabled"]');
    if (delayCheckbox) toggleEditDelayMinutes(delayCheckbox);
});
</script>
</body>
</html>
