<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>分享管理 - 私密知识库</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 私密知识库</h1>
    <div class="user"><?= htmlspecialchars($_SESSION['user']['username']) ?> <a href="/<?= Config::ADMIN_PATH ?>/logout" style="margin-left:12px;color:#86868b;font-size:13px;">登出</a></div>
</div>
<div class="nav">
    <a href="/<?= Config::ADMIN_PATH ?>/dashboard">仪表盘</a>
    <a href="/<?= Config::ADMIN_PATH ?>/documents">文档</a>
    <a href="/<?= Config::ADMIN_PATH ?>/files">文件</a>
    <a href="/<?= Config::ADMIN_PATH ?>/shares" class="active">分享管理</a>
    <a href="/<?= Config::ADMIN_PATH ?>/logs">日志</a>
    <a href="/<?= Config::ADMIN_PATH ?>/settings">设置</a>
    <a href="/<?= Config::ADMIN_PATH ?>/security">安全</a>
        <a href="/<?= Config::ADMIN_PATH ?>/account">账户</a>
</div>

<div class="container">
    <?php if (isset($createdShare) && $createdShare): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:12px;margin-bottom:20px;font-size:14px;color:#155724;">
        ✅ 分享链接已创建！<br>
        <input type="text" value="<?= htmlspecialchars($shareUrl) ?>" id="share-url" style="width:100%;margin-top:8px;padding:8px;border:1px solid #c3e6cb;border-radius:6px;font-size:13px;" readonly>
        <button onclick="copyUrl()" style="margin-top:8px;padding:8px 16px;background:#28a745;color:#fff;border:none;border-radius:6px;cursor:pointer;">复制链接</button>
        <script>
        function copyUrl() {
            document.getElementById('share-url').select();
            document.execCommand('copy');
            alert('链接已复制！');
        }
        </script>
    </div>
    <?php endif; ?>
    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 style="font-size:20px;">🔗 分享管理</h2>
        <button onclick="document.getElementById('create-form').style.display='block';this.style.display='none';" class="btn">+ 创建分享</button>
    </div>
    
    <!-- 创建分享表单 -->
    <div id="create-form" style="display:none;background:#fff;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <h3 style="margin-bottom:16px;font-size:16px;">创建分享链接</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="create_share" value="1">
            
            <div class="form-group">
                <label>选择内容</label>
                <select name="doc_id" id="content-select" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="">-- 选择文档 --</option>
                    <?php foreach ($docs as $doc): ?>
                    <option value="<?= $doc['id'] ?>">📝 <?= htmlspecialchars($doc['title']) ?></option>
                    <?php endforeach; ?>
                    <option value="" disabled>-- 选择文件 --</option>
                    <?php foreach ($files as $file): ?>
                    <option value="file_<?= $file['id'] ?>">📁 <?= htmlspecialchars($file['original_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>访问密码（可选）</label>
                <input type="text" name="password" placeholder="留空则不设密码" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                <small style="color:#86868b;">默认随机生成6位数字密码</small>
            </div>
            
            <div class="form-group">
                <label>有效期</label>
                <select name="expire_seconds" id="expire-select" onchange="toggleExpireCustom(this)" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="3600">1小时</option>
                    <option value="21600">6小时</option>
                    <option value="86400" selected>24小时</option>
                    <option value="604800">7天</option>
                    <option value="custom">自定义天数</option>
                    <option value="">永久有效</option>
                </select>
                <div id="expire-custom-field" style="display:none;margin-top:8px;">
                    <input type="number" name="expire_days_custom" id="expire-days-input" value="30" min="1" max="365" style="width:100px;padding:6px;border:1px solid #d2d2d7;border-radius:6px;font-size:13px;">
                    <span style="font-size:12px;color:#86868b;margin-left:6px;">天（输入 30 表示30天）</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>访问次数限制</label>
                <select name="max_clicks" id="clicks-select" onchange="toggleClicksCustom(this)" style="width:100%;padding:8px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;">
                    <option value="-1" selected>无限制</option>
                    <option value="1">1次（阅后即焚）</option>
                    <option value="3">3次</option>
                    <option value="5">5次</option>
                    <option value="10">10次</option>
                    <option value="custom">自定义次数</option>
                </select>
                <div id="clicks-custom-field" style="display:none;margin-top:8px;">
                    <input type="number" name="max_clicks_custom" id="clicks-input" value="50" min="1" max="9999" style="width:100px;padding:6px;border:1px solid #d2d2d7;border-radius:6px;font-size:13px;">
                    <span style="font-size:12px;color:#86868b;margin-left:6px;">次（输入 50 表示最多访问50次）</span>
                </div>
            </div>

            <!-- 防手滑延迟销毁（仅阅后即焚模式显示） -->
            <div id="destroy-delay-field" style="display:none;background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;margin-top:12px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="destroy_delay_enabled" value="1" onchange="toggleDelayMinutes(this)">
                    🛡️ 开启防手滑延迟销毁
                </label>
                <div id="delay-minutes-field" style="display:none;margin-top:8px;">
                    <label style="font-size:12px;color:#795548;">延迟时间（分钟）：</label>
                    <input type="number" name="destroy_delay_minutes" value="5" min="1" max="60" style="width:80px;padding:6px;border:1px solid #ffe082;border-radius:6px;font-size:13px;">
                    <small style="color:#86868b;margin-left:6px;">在延迟窗口内刷新不会烧毁链接</small>
                </div>
            </div>
            
            <div style="display:flex;gap:12px;margin-top:16px;">
                <button type="submit" class="btn">生成分享链接</button>
                <button type="button" onclick="document.getElementById('create-form').style.display='none';" style="padding:8px 16px;background:#f5f5f7;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;">取消</button>
            </div>
        </form>
    </div>
    
    <!-- 搜索栏 + 分享列表 -->
    <form method="GET" action="" style="display:flex;gap:8px;align-items:center;margin-bottom:16px;max-width:400px;">
        <input type="text" name="search" placeholder="搜索内容名称或Token..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="flex:1;padding:8px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:13px;" onkeydown="if(event.key==='Enter') this.form.submit()">
        <button type="submit" style="padding:8px 14px;background:#f5f5f7;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;font-size:13px;">🔍</button>
        <?php if (!empty($_GET['search'])): ?>
            <a href="/<?= Config::ADMIN_PATH ?>/shares" style="font-size:12px;color:#86868b;">清除</a>
        <?php endif; ?>
    </form>

    <form method="POST" action="" id="batch-form">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">
    <?php if (empty($shares)): ?>
        <div style="text-align:center;color:#86868b;padding:80px;font-size:14px;background:#fff;border-radius:12px;">
            <?php if (!empty($_GET['search'])): ?>
                未找到匹配的分享，<a href="/<?= Config::ADMIN_PATH ?>/shares" style="color:#007aff;">查看全部</a>
            <?php else: ?>
                暂无分享，点击上方按钮创建
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
            <button type="button" onclick="doBatchDeleteShare()" class="btn-sm btn-danger" style="display:none;" id="batch-del-btn-3">🗑️ 批量删除</button>
        </div>
        <table>
            <tr><th style="width:36px;"><input type="checkbox" onchange="toggleAllShares(this)"></th><th>内容</th><th>Token</th><th>有效期</th><th>次数</th><th>操作</th></tr>
            <?php foreach ($shares as $share): ?>
            <tr>
                <td><input type="checkbox" name="selected[]" value="<?= $share['id'] ?>" onchange="updateShareBatchBtn()"></td>
                <td>
                    <?php if ($share['doc_title']): ?>
                        📝 <?= htmlspecialchars($share['doc_title']) ?>
                    <?php elseif ($share['file_name']): ?>
                        📁 <?= htmlspecialchars($share['file_name']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $shareDomain = $shareDomain ?? ($_SERVER['HTTP_HOST'] ?? '127.0.0.1'); $fullUrl = 'https://' . $shareDomain . '/?action=share&token=' . $share['share_token']; ?>
                    <input type="text" value="<?= htmlspecialchars($fullUrl) ?>" id="share-url-<?= $share['id'] ?>" style="width:260px;padding:4px 8px;border:1px solid #e0e0e0;border-radius:6px;font-size:12px;color:#1d1d1f;" readonly>
                    <button onclick="copyShareUrl(<?= $share['id'] ?>)" style="padding:4px 10px;background:#007aff;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px;">复制</button>
                </td>
                <td>
                    <?php if ($share['expire_time']): ?>
                        <?= date('m-d H:i', strtotime($share['expire_time'])) ?>
                        <?php if (strtotime($share['expire_time']) < time()): ?>
                            <span style="color:#ff3b30;font-size:11px;">已过期</span>
                        <?php endif; ?>
                    <?php else: ?>
                        永久
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($share['max_clicks'] == -1): ?>
                        ∞
                    <?php elseif ($share['click_count'] >= $share['max_clicks']): ?>
                        <span style="color:#ff3b30;"><?= $share['click_count'] ?> / <?= $share['max_clicks'] ?> 已烧毁</span>
                    <?php else: ?>
                        <?= $share['click_count'] ?> / <?= $share['max_clicks'] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/<?= Config::ADMIN_PATH ?>/edit_share?id=<?= $share['id'] ?>" class="btn-sm">编辑</a>
                    <a href="?action=share&token=<?= $share['share_token'] ?>" target="_blank" class="btn-sm">查看</a>
                    <a href="/<?= Config::ADMIN_PATH ?>/shares?delete=<?= $share['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                    <a href="/<?= Config::ADMIN_PATH ?>/shares?burn=<?= $share['id'] ?>" class="btn-sm btn-danger" style="background:#ff3b30;" onclick="return confirm('确认立即熔断该分享？链接将立即失效！')">⚡ 熔断</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <input type="hidden" name="delete_batch" value="1">
    <?php endif; ?>
    </form>
</div>

<script>
function copyShareUrl(id) {
    const input = document.getElementById('share-url-' + id);
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    const btn = input.nextElementSibling;
    const original = btn.textContent;
    btn.textContent = '已复制！';
    btn.style.background = '#34c759';
    setTimeout(() => {
        btn.textContent = original;
        btn.style.background = '#007aff';
    }, 1500);
}

// 有效期：显示/隐藏自定义天数输入框
function toggleExpireCustom(select) {
    document.getElementById('expire-custom-field').style.display = (select.value === 'custom') ? '' : 'none';
}

// 次数限制：显示/隐藏自定义次数输入框 + 防手滑
function toggleClicksCustom(select) {
    const val = select.value;
    document.getElementById('clicks-custom-field').style.display = (val === 'custom') ? '' : 'none';
    document.getElementById('destroy-delay-field').style.display = (val === '1') ? '' : 'none';
}

// 防手滑复选框：显示/隐藏分钟输入
function toggleDelayMinutes(checkbox) {
    document.getElementById('delay-minutes-field').style.display = checkbox.checked ? '' : 'none';
}

// 页面加载时初始化
document.addEventListener('DOMContentLoaded', () => {
    const maxClicksSelect = document.getElementById('clicks-select');
    if (maxClicksSelect) {
        toggleClicksCustom(maxClicksSelect);
    }
    const expireSelect = document.getElementById('expire-select');
    if (expireSelect) {
        toggleExpireCustom(expireSelect);
    }
});

// 分享列表全选/取消
function toggleAllShares(master) {
    document.querySelectorAll('input[name="selected\[\]"]').forEach(cb => cb.checked = master.checked);
    updateShareBatchBtn();
}
function updateShareBatchBtn() {
    const checked = document.querySelectorAll('input[name="selected\[\]"]:checked').length;
    const btn = document.getElementById('batch-del-btn-3');
    if (checked > 0) {
        btn.style.display = 'inline-block';
        btn.textContent = '🗑️ 批量删除 (' + checked + ')';
    } else {
        btn.style.display = 'none';
    }
}
function doBatchDeleteShare() {
    const checked = document.querySelectorAll('input[name="selected\[\]"]:checked');
    if (checked.length === 0) return;
    if (!confirm('确定要删除选中的 ' + checked.length + ' 项吗？')) return;
    document.getElementById('batch-form').submit();
}
</script>
</body>
</html>
