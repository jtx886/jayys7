<?php
$page_title = '注册';
require_once __DIR__ . '/header.php';
?>
<style>
.auth-page {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.auth-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: 40px;
    width: 100%;
    max-width: 440px;
    border: 1px solid var(--border-color);
}
.auth-header {
    text-align: center;
    margin-bottom: 32px;
}
.auth-header h1 {
    font-size: 28px;
    margin-bottom: 8px;
}
.auth-header p {
    color: var(--text-secondary);
}
.auth-logo {
    margin-bottom: 20px;
}
.btn-code {
    white-space: nowrap;
    padding: 0 16px;
    min-width: 120px;
}
.btn-code:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<div class="auth-page">
    <div class="auth-card fade-in">
        <div class="auth-header">
            <div class="logo auth-logo">
                <span class="icon-logo"></span>
                <span>Jay影视</span>
            </div>
            <h1>创建账号</h1>
            <p>注册后即可免费观看海量影视</p>
        </div>
        
        <form id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label class="form-label">邮箱</label>
                <input type="email" name="email" class="form-input" placeholder="请输入邮箱地址" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" name="username" class="form-input" placeholder="请输入用户名（2-20字）" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">验证码</label>
                    <div style="display:flex;gap:10px;">
                        <input type="text" name="code" class="form-input" placeholder="6位验证码" required maxlength="6">
                        <button type="button" class="btn btn-outline btn-code" id="sendCodeBtn" onclick="sendCode()">获取验证码</button>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-input" placeholder="请输入密码（至少6位）" required minlength="6">
            </div>
            
            <div class="form-group">
                <label class="form-label">确认密码</label>
                <input type="password" name="confirm_password" class="form-input" placeholder="请再次输入密码" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" id="registerBtn">
                注册
            </button>
            
            <div class="form-footer">
                已有账号？<a href="login.php">立即登录</a>
            </div>
        </form>
    </div>
</div>

<script>
let codeTimer = null;
let codeCountdown = 0;

function sendCode() {
    const email = document.querySelector('input[name="email"]').value;
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast('请输入正确的邮箱地址', 'error');
        return;
    }
    
    const btn = document.getElementById('sendCodeBtn');
    btn.disabled = true;
    btn.textContent = '发送中...';
    
    fetch('api.php?action=send_code', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email) + '&type=register'
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showToast(data.message);
            codeCountdown = 60;
            btn.textContent = codeCountdown + 's后重发';
            codeTimer = setInterval(() => {
                codeCountdown--;
                if (codeCountdown <= 0) {
                    clearInterval(codeTimer);
                    btn.disabled = false;
                    btn.textContent = '获取验证码';
                } else {
                    btn.textContent = codeCountdown + 's后重发';
                }
            }, 1000);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.textContent = '获取验证码';
        }
    })
    .catch(() => {
        showToast('网络错误，请重试', 'error');
        btn.disabled = false;
        btn.textContent = '获取验证码';
    });
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('registerBtn');
    btn.innerHTML = '<span class="loading"></span>';
    btn.disabled = true;
    
    fetch('api.php?action=register', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(new FormData(this))
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showToast(data.message);
            setTimeout(() => window.location.href = data.redirect, 800);
        } else {
            showToast(data.message, 'error');
            btn.innerHTML = '注册';
            btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('网络错误，请重试', 'error');
        btn.innerHTML = '注册';
        btn.disabled = false;
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
