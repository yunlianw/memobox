*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft YaHei',sans-serif;background:#f5f5f7;min-height:100vh;color:#1d1d1f}
.header{background:#fff;padding:16px 24px;border-bottom:1px solid #f2f2f7;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.header h1{font-size:18px;font-weight:600}
.header .user{font-size:14px;color:#86868b}
.nav{background:#fff;padding:0 24px;border-bottom:1px solid #f2f2f7}
.nav a{display:inline-block;padding:12px 16px;font-size:14px;color:#86868b;text-decoration:none;border-bottom:2px solid transparent;transition:all .2s}
.nav a.active,.nav a:hover{color:#007aff;border-bottom-color:#007aff}
.container{max-width:1200px;margin:0 auto;padding:24px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
th{text-align:left;padding:12px 10px;font-size:13px;color:#86868b;font-weight:500;border-bottom:1px solid #f2f2f7;background:#fafafa}
td{padding:12px 10px;font-size:14px;border-bottom:1px solid #f2f2f7}
tr:last-child td{border-bottom:none}
a{color:#007aff;text-decoration:none}
a:hover{text-decoration:underline}
.btn{display:inline-block;padding:10px 20px;background:#007aff;color:#fff!important;border:none;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;text-decoration:none!important;transition:background .2s}
.btn:hover{background:#0063ce}
.btn-sm{display:inline-block;padding:4px 10px;background:#e8f0fe;color:#007aff!important;border-radius:6px;font-size:12px;cursor:pointer;text-decoration:none!important}
.btn-secondary{display:inline-block;padding:10px 14px;background:#f5f5f7;color:#1d1d1f;border:1px solid #d2d2d7;border-radius:10px;font-size:14px;cursor:pointer;text-decoration:none!important}
.btn-danger{background:#fff2f0!important;color:#cf1322!important}
.btn-danger:hover{background:#ffd8d5!important}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;color:#86868b;margin-bottom:6px;font-weight:500}
.muted{color:#86868b;font-size:13px}
.tag{display:inline-block;font-size:12px;padding:2px 8px;border-radius:4px;background:#f5f5f7;color:#86868b}
@media(max-width:768px){.header{padding:12px 16px}.nav{padding:0 12px}.nav a{padding:10px 10px;font-size:13px}.container{padding:16px}}
