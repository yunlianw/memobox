<!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>上传 - MemoBox</title>
<style><?php include __DIR__ . '/style.php'; ?></style>
</head>
<body>
<div class="header">
    <h1>📚 MemoBox</h1>
    <div class="user"><a href="/<?= Config::ADMIN_PATH ?>/dashboard" style="color:#86868b;font-size:13px;">返回</a></div>
</div>

<div class="container">
    <h2 style="font-size:20px;margin-bottom:20px;">📁 上传文件</h2>
    
    <div id="upload-area" 
         style="background:#fff;border-radius:12px;padding:40px;text-align:center;border:2px dashed #d2d2d7;cursor:pointer;transition:border-color .2s;position:relative;"
         ondragover="event.preventDefault();this.style.borderColor='#007aff'" 
         ondragleave="this.style.borderColor='#d2d2d7'" 
         ondrop="event.preventDefault();handleFiles(event.dataTransfer.files)">
        <div style="font-size:48px;margin-bottom:12px;">📁</div>
        <p style="color:#86868b;margin-bottom:16px;" id="drop-text">拖拽文件到此处，或点击选择</p>
        <input type="file" id="file-input" style="display:none;" onchange="handleFiles(this.files)">
        <button type="button" class="btn" onclick="document.getElementById('file-input').click()">选择文件</button>
        
        <!-- 进度条 -->
        <div id="progress-container" style="display:none;margin-top:20px;text-align:left;">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                <span id="progress-name"></span>
                <span id="progress-percent">0%</span>
            </div>
            <div style="background:#f0f0f5;border-radius:8px;height:8px;overflow:hidden;">
                <div id="progress-bar" style="background:#007aff;height:100%;width:0%;transition:width .3s;"></div>
            </div>
            <p id="progress-status" style="font-size:12px;color:#86868b;margin-top:6px;">正在上传...</p>
            <p id="progress-speed" style="font-size:12px;color:#86868b;margin-top:4px;"></p>
        </div>
    </div>
    
    <p style="font-size:12px;color:#86868b;margin-top:12px;text-align:center;">
        文件将安全存储在Web目录之外，仅通过PHP流式输出<br>
        💡 大文件（>5MB）将自动切片上传，支持失败重试
    </p>
</div>

<script>
var CHUNK_SIZE = 5 * 1024 * 1024;

function handleFiles(files) {
    if (!files.length) return;
    var file = files[0];
    document.getElementById('drop-text').textContent = file.name + ' (' + formatSize(file.size) + ')';
    uploadFile(file);
}

function formatSize(bytes) {
    if (bytes >= 1048576) return (bytes/1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes/1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function uploadFile(file) {
    showProgress(file.name);
    if (file.size <= CHUNK_SIZE) {
        uploadDirect(file);
    } else {
        uploadChunks(file);
    }
}

function showProgress(name) {
    document.getElementById('progress-container').style.display = 'block';
    document.getElementById('progress-name').textContent = name;
    setProgress(0);
    document.getElementById('progress-status').textContent = '正在上传...';
    document.getElementById('progress-speed').textContent = '';
}

function setProgress(pct) {
    document.getElementById('progress-bar').style.width = pct + '%';
    document.getElementById('progress-percent').textContent = pct + '%';
}

function uploadDirect(file) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('ajax', '1');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/<?= Config::ADMIN_PATH ?>/upload');
    
    xhr.upload.onprogress = function(e) {
        var pct = Math.round(e.loaded * 100 / e.total);
        setProgress(Math.min(pct, 100));
        showSpeed(e.loaded, e.total);
        document.getElementById('progress-status').textContent = '上传中... ' + Math.min(pct, 100) + '%';
    };
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    setProgress(100);
                    document.getElementById('progress-status').textContent = '上传成功！';
                    document.getElementById('progress-speed').textContent = '即将跳转到文件列表...';
                    setTimeout(function() { window.location.href = '/<?= Config::ADMIN_PATH ?>/files'; }, 800);
                } else {
                    document.getElementById('progress-status').textContent = '上传失败：' + (res.error || '服务端错误');
                }
            } catch(e) {
                document.getElementById('progress-status').textContent = '服务端响应异常';
            }
        } else {
            document.getElementById('progress-status').textContent = '上传失败：HTTP ' + xhr.status;
        }
    };
    
    xhr.onerror = function() {
        document.getElementById('progress-status').textContent = '网络错误，请重试';
    };
    
    xhr.send(fd);
}

function uploadChunks(file) {
    var uuid = genUUID();
    var total = Math.ceil(file.size / CHUNK_SIZE);
    var idx = 0;
    
    function next() {
        if (idx >= total) return;
        
        var start = idx * CHUNK_SIZE;
        var end = Math.min(start + CHUNK_SIZE, file.size);
        var chunk = file.slice(start, end);
        
        var fd = new FormData();
        fd.append('file', chunk);
        fd.append('chunk_uuid', uuid);
        fd.append('chunk_index', idx);
        fd.append('chunk_total', total);
        fd.append('original_name', file.name);
        fd.append('mime_type', file.type || 'application/octet-stream');
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/<?= Config::ADMIN_PATH ?>/upload');
        
        xhr.upload.onprogress = function(e) {
            var loaded = e.loaded || 0;
            var chunkPct = Math.round(loaded * 100 / (end - start));
            var globalPct = Math.round(((idx + chunkPct / 100) / total) * 100);
            setProgress(Math.min(globalPct, 99));
            showSpeed(loaded, end - start);
            document.getElementById('progress-status').textContent = '切片 ' + (idx+1) + '/' + total + ' 上传中...';
        };
        
        xhr.onload = function() {
            if (xhr.status !== 200) {
                document.getElementById('progress-status').textContent = '切片 ' + (idx+1) + ' 上传失败：HTTP ' + xhr.status;
                return;
            }
            try {
                var res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    document.getElementById('progress-status').textContent = '切片 ' + (idx+1) + ' 上传失败：' + (res.error || '服务端错误');
                    return;
                }
            } catch(e) {
                document.getElementById('progress-status').textContent = '切片 ' + (idx+1) + ' 响应解析失败';
                return;
            }
            
            idx++;
            if (idx >= total) {
                setProgress(100);
                document.getElementById('progress-status').textContent = '上传成功！正在合并...';
                document.getElementById('progress-speed').textContent = '即将跳转到文件列表';
                setTimeout(function() { window.location.href = '/<?= Config::ADMIN_PATH ?>/files'; }, 800);
            } else {
                document.getElementById('progress-status').textContent = '切片 ' + idx + '/' + total + ' 完成，上传下一片...';
                next();
            }
        };
        
        xhr.onerror = function() {
            document.getElementById('progress-status').textContent = '切片 ' + (idx+1) + ' 网络错误，正在重试...';
            setTimeout(next, 2000);
        };
        
        xhr.send(fd);
    }
    
    next();
}

function showSpeed(loaded, total) {
    var spd = document.getElementById('progress-speed');
    if (!spd._startTime) spd._startTime = Date.now();
    if (!spd._lastLoaded) spd._lastLoaded = 0;
    if (!spd._lastTime) spd._lastTime = spd._startTime;
    
    var now = Date.now();
    var elapsed = (now - spd._lastTime) / 1000;
    if (elapsed <= 0) return;
    
    var delta = loaded - spd._lastLoaded;
    var bps = delta / elapsed;
    spd._lastLoaded = loaded;
    spd._lastTime = now;
    
    var totalElapsed = (now - spd._startTime) / 1000;
    var totalBps = totalElapsed > 0 ? loaded / totalElapsed : 0;
    
    var speedText = '';
    if (totalBps > 1048576) speedText = (totalBps/1048576).toFixed(2) + ' MB/s';
    else if (totalBps > 1024) speedText = (totalBps/1024).toFixed(2) + ' KB/s';
    else speedText = Math.round(totalBps) + ' B/s';
    
    var remaining = '';
    if (totalBps > 0 && loaded < total) {
        var left = (total - loaded) / totalBps;
        if (left > 60) remaining = '，预计 ' + Math.ceil(left/60) + ' 分钟';
        else remaining = '，预计 ' + Math.ceil(left) + ' 秒';
    }
    
    spd.textContent = speedText + remaining;
}

function genUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0;
        var v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
</script>
</body>
</html>
