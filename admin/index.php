<?php
$page_title = '仪表盘';
require_once __DIR__ . '/header.php';

$conn = db_connect();

// 最新注册用户
$new_users = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// 最新反馈
$new_feedbacks = $conn->query("SELECT f.id, f.title, f.created_at, u.username FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// 最新观看历史
$new_history = $conn->query("SELECT h.id, h.title, h.watched_at, u.username FROM watch_history h JOIN users u ON h.user_id = u.id ORDER BY h.watched_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// 最新收藏
$new_favorites = $conn->query("SELECT f.id, f.title, f.created_at, u.username FROM favorites f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <h1>仪表盘</h1>
    <div style="color:var(--text-secondary);">欢迎回来，<?php echo htmlspecialchars($admin['username']); ?></div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_users; ?></div>
        <div class="stat-label">👥 总用户数</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_favorites; ?></div>
        <div class="stat-label">❤️ 总收藏数</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_history; ?></div>
        <div class="stat-label">📺 观看记录</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $total_feedbacks; ?></div>
        <div class="stat-label">💬 反馈数量</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>最新注册用户</h3>
        <ul class="dashboard-list">
            <?php foreach ($new_users as $u): ?>
            <li>
                <span><?php echo htmlspecialchars($u['username']); ?></span>
                <span style="color:var(--text-muted);font-size:13px;"><?php echo $u['created_at']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="users.php" style="display:block;text-align:center;margin-top:16px;color:var(--theme-color);font-size:14px;">查看全部用户 →</a>
    </div>
    
    <div class="dashboard-card">
        <h3>最新反馈</h3>
        <ul class="dashboard-list">
            <?php foreach ($new_feedbacks as $f): ?>
            <li>
                <span><?php echo htmlspecialchars(mb_substr($f['title'], 0, 20)); ?>...</span>
                <span style="color:var(--text-muted);font-size:13px;"><?php echo $f['username']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="feedbacks.php" style="display:block;text-align:center;margin-top:16px;color:var(--theme-color);font-size:14px;">查看全部反馈 →</a>
    </div>
    
    <div class="dashboard-card">
        <h3>最新观看记录</h3>
        <ul class="dashboard-list">
            <?php foreach ($new_history as $h): ?>
            <li>
                <span><?php echo htmlspecialchars(mb_substr($h['title'], 0, 18)); ?></span>
                <span style="color:var(--text-muted);font-size:13px;"><?php echo $h['username']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="history.php" style="display:block;text-align:center;margin-top:16px;color:var(--theme-color);font-size:14px;">查看全部历史 →</a>
    </div>
    
    <div class="dashboard-card">
        <h3>最新收藏</h3>
        <ul class="dashboard-list">
            <?php foreach ($new_favorites as $f): ?>
            <li>
                <span><?php echo htmlspecialchars(mb_substr($f['title'], 0, 18)); ?></span>
                <span style="color:var(--text-muted);font-size:13px;"><?php echo $f['username']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <a href="favorites.php" style="display:block;text-align:center;margin-top:16px;color:var(--theme-color);font-size:14px;">查看全部收藏 →</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
