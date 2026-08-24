<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';
$user = require_login();
$page_title = '观看历史';
require_once __DIR__ . '/header.php';

$conn = db_connect();
$result = $conn->query("SELECT * FROM watch_history WHERE user_id = " . $user['id'] . " ORDER BY watched_at DESC");
$history = $result->fetch_all(MYSQLI_ASSOC);
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
                <li><a href="favorites.php">
                    <span class="icon icon-heart"></span>
                    我的收藏
                </a></li>
                <li><a href="history.php" class="active">
                    <span class="icon icon-movie"></span>
                    观看历史 (<?php echo count($history); ?>)
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
            <h2>观看历史 (<?php echo count($history); ?>)</h2>
            
            <?php if (empty($history)): ?>
            <div class="empty-state">
                <div class="icon icon-movie" style="width:64px;height:64px;opacity:0.3;"></div>
                <p>还没有观看记录</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:20px;">去发现好看的</a>
            </div>
            <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $h): ?>
                <div class="history-item" id="hist-<?php echo $h['id']; ?>">
                    <a href="detail.php?id=<?php echo $h['media_id']; ?>&type=<?php echo $h['media_type']; ?><?php echo $h['season'] ? '&season=' . $h['season'] . '&episode=' . $h['episode'] : ''; ?>" class="history-poster">
                        <img src="<?php echo tmdb_image($h['poster']); ?>" alt="">
                    </a>
                    <div class="history-info">
                        <h4><?php echo htmlspecialchars($h['title']); ?></h4>
                        <div class="history-meta">
                            <?php if ($h['episode_title']): ?>
                            看到 <?php echo htmlspecialchars($h['episode_title']); ?> · 
                            <?php endif; ?>
                            <?php echo $h['watched_at']; ?>
                        </div>
                        <div class="history-actions">
                            <a href="detail.php?id=<?php echo $h['media_id']; ?>&type=<?php echo $h['media_type']; ?><?php echo $h['season'] ? '&season=' . $h['season'] . '&episode=' . $h['episode'] : ''; ?>" class="btn btn-primary btn-sm">
                                <span class="icon icon-play" style="width:14px;height:14px;"></span>
                                继续观看
                            </a>
                            <button class="btn btn-danger btn-sm" onclick="deleteHistory(<?php echo $h['id']; ?>)">
                                删除
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
function deleteHistory(id) {
    if (!confirm('确定要删除这条记录吗？')) return;
    
    fetch('api.php?action=delete_history', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('已删除');
            document.getElementById('hist-' + id).remove();
        } else {
            showToast(data.message || '操作失败', 'error');
        }
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
