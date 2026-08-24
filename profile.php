<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';
$user = require_login();
$page_title = '个人中心';
require_once __DIR__ . '/header.php';

$current_page = basename($_SERVER['PHP_SELF']);

$conn = db_connect();
$favorites_count = $conn->query("SELECT COUNT(*) FROM favorites WHERE user_id = " . $user['id'])->fetch_row()[0];
$history_count = $conn->query("SELECT COUNT(*) FROM watch_history WHERE user_id = " . $user['id'])->fetch_row()[0];
?>

<style>
.avatar-upload-area {
    position: relative;
}
.avatar-upload-area input[type="file"] {
    display: none;
}
</style>

<div class="container">
    <div class="profile-layout">
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar" id="avatarPreview">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                    <?php else: ?>
                        <?php echo mb_substr($user['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name">
                    <?php echo htmlspecialchars($user['username']); ?>
                    <?php if ($user['is_admin']): ?>
                    <span class="admin-badge">开发者</span>
                    <?php endif; ?>
                </div>
                <div style="color:var(--text-muted);font-size:13px;margin-top:4px;"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            
            <ul class="profile-menu">
                <li><a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <span class="icon icon-user"></span>
                    个人资料
                </a></li>
                <li><a href="favorites.php" class="<?php echo $current_page == 'favorites.php' ? 'active' : ''; ?>">
                    <span class="icon icon-heart"></span>
                    我的收藏 (<?php echo $favorites_count; ?>)
                </a></li>
                <li><a href="history.php" class="<?php echo $current_page == 'history.php' ? 'active' : ''; ?>">
                    <span class="icon icon-movie"></span>
                    观看历史 (<?php echo $history_count; ?>)
                </a></li>
                <?php if ($user['is_admin']): ?>
                <li><a href="admin/">
                    <span class="admin-badge" style="margin-right:8px;">管</span>
                    管理后台
                </a></li>
                <?php endif; ?>
                <li><a href="logout.php" style="color:#f87171;">
                    <span class="icon icon-close"></span>
                    退出登录
                </a></li>
            </ul>
        </div>
        
        <div class="profile-content">
            <h2>个人资料</h2>
            
            <form id="avatarForm" class="avatar-upload">
                <div class="avatar-preview" id="avatarEditPreview" onclick="document.getElementById('avatarInput').click()">
                    <?php if ($user['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="" id="avatarImg">
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:48px;" id="avatarPlaceholder"><?php echo mb_substr($user['username'], 0, 1); ?></span>
                        <img src="" alt="" id="avatarImg" style="display:none;">
                    <?php endif; ?>
                </div>
                <p style="color:var(--text-muted);font-size:13px;text-align:center;margin-bottom:20px;">点击头像上传自定义头像</p>
                <input type="file" id="avatarInput" accept="image/*" onchange="handleAvatarUpload(this)">
            </form>
            
            <div style="margin-top:32px;">
                <h3 style="margin-bottom:16px;">账号信息</h3>
                <div style="background:rgba(255,255,255,0.03);border-radius:var(--radius);padding:20px;">
                    <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                        <span style="color:var(--text-secondary);">用户名</span>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                        <span style="color:var(--text-secondary);">邮箱</span>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:12px 0;">
                        <span style="color:var(--text-secondary);">注册时间</span>
                        <span style="color:var(--text-muted);">--</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleAvatarUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            showToast('图片大小不能超过2MB', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgData = e.target.result;
            
            // 预览
            const preview = document.getElementById('avatarEditPreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            const img = document.getElementById('avatarImg');
            if (placeholder) placeholder.style.display = 'none';
            img.src = imgData;
            img.style.display = 'block';
            
            // 更新顶部头像
            const topAvatar = document.querySelector('.user-avatar img') || document.querySelector('.user-avatar');
            if (topAvatar) {
                if (topAvatar.tagName === 'IMG') {
                    topAvatar.src = imgData;
                } else {
                    topAvatar.innerHTML = '<img src="' + imgData + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
                }
            }
            
            // 提交
            const formData = new FormData();
            formData.append('action', 'update_avatar');
            formData.append('avatar', imgData);
            formData.append('csrf_token', '<?php echo csrf_token(); ?>');
            
            fetch('api.php?action=update_avatar', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update_avatar',
                    avatar: imgData,
                    csrf_token: '<?php echo csrf_token(); ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('头像更新成功');
                    document.getElementById('avatarPreview').innerHTML = '<img src="' + imgData + '" alt="">';
                } else {
                    showToast(data.message || '更新失败', 'error');
                }
            });
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
