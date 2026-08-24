<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';
$user = require_login();
$page_title = '我的收藏';
require_once __DIR__ . '/header.php';

$conn = db_connect();
$result = $conn->query("SELECT * FROM favorites WHERE user_id = " . $user['id'] . " ORDER BY created_at DESC");
$favorites = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
    <div class="profile-layout">
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
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
            </div>
            
            <ul class="profile-menu">
                <li><a href="profile.php">
                    <span class="icon icon-user"></span>
                    个人资料
                </a></li>
                <li><a href="favorites.php" class="active">
                    <span class="icon icon-heart"></span>
                    我的收藏 (<?php echo count($favorites); ?>)
                </a></li>
                <li><a href="history.php">
                    <span class="icon icon-movie"></span>
                    观看历史
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
            <h2>我的收藏 (<?php echo count($favorites); ?>)</h2>
            
            <?php if (empty($favorites)): ?>
            <div class="empty-state">
                <div class="icon icon-heart" style="width:64px;height:64px;opacity:0.3;"></div>
                <p>还没有收藏任何内容</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:20px;">去发现好看的</a>
            </div>
            <?php else: ?>
            <div class="favorites-list">
                <?php foreach ($favorites as $fav): ?>
                <div class="favorite-item" id="fav-<?php echo $fav['id']; ?>">
                    <a href="detail.php?id=<?php echo $fav['media_id']; ?>&type=<?php echo $fav['media_type']; ?>" class="favorite-poster">
                        <img src="<?php echo tmdb_image($fav['poster']); ?>" alt="">
                    </a>
                    <div class="favorite-info">
                        <h4><?php echo htmlspecialchars($fav['title']); ?></h4>
                        <div class="history-meta">
                            收藏于 <?php echo $fav['created_at']; ?>
                        </div>
                        <div class="history-actions">
                            <a href="detail.php?id=<?php echo $fav['media_id']; ?>&type=<?php echo $fav['media_type']; ?>" class="btn btn-primary btn-sm">
                                <span class="icon icon-play" style="width:14px;height:14px;"></span>
                                立即播放
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="deleteFavorite(<?php echo $fav['id']; ?>)">
                                取消收藏
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteFavorite(id) {
    if (!confirm('确定要取消收藏吗？')) return;
    
    fetch('api.php?action=delete_favorite', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('已取消收藏');
            document.getElementById('fav-' + id).remove();
        } else {
            showToast(data.message || '操作失败', 'error');
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
