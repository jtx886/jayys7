<?php
$page_title = '登录';
require_once __DIR__ . '/header.php';

$message = isset($_GET['message']) ? $_GET['message'] : '';
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
</style>

<div class="auth-page">
    <div class="auth-card fade-in">
        <div class="auth-header">
            <div class="logo auth-logo">
                <span class="icon-logo"></span>
                <span>Jay影视</span>
            </div>
            <h1>欢迎回来</h1>
            <p>登录您的账号继续观看</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label class="form-label">邮箱 / 用户名</label>
                <input type="text" name="account" class="form-input" placeholder="请输入邮箱或用户名" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                登录
            </button>
            
            <div class="form-footer">
                还没有账号？<a href="register.php">立即注册</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<span class="loading"></span>';
    btn.disabled = true;
    
    fetch('api.php?action=login', {
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
            btn.innerHTML = '登录';
            btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('网络错误，请重试', 'error');
        btn.innerHTML = '登录';
        btn.disabled = false;
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
