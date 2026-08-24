<?php
$page_title = '公告管理';
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (!empty($title) && !empty($content)) {
            $stmt = $conn->prepare('INSERT INTO announcements (title, content) VALUES (?, ?)');
            $stmt->bind_param('ss', $title, $content);
            $stmt->execute();
            $success = '公告发布成功';
        }
    } elseif (isset($_POST['delete_announcement'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM announcements WHERE id = $id");
        $conn->query("DELETE FROM announcement_dismissed WHERE announcement_id = $id");
        $success = '公告已删除';
    } elseif (isset($_POST['toggle_announcement'])) {
        $id = intval($_POST['id']);
        $conn->query("UPDATE announcements SET is_active = 1 - is_active WHERE id = $id");
    }
}

$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <h1>公告管理</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>发布新公告</h3>
        <form method="post">
            <div class="form-group">
                <label class="form-label">公告标题</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">公告内容</label>
                <textarea name="content" class="form-input" rows="5" required></textarea>
            </div>
            <button type="submit" name="add_announcement" class="btn btn-primary">发布公告</button>
        </form>
    </div>
    
    <div class="dashboard-card">
        <h3>历史公告</h3>
        <?php if (empty($announcements)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:40px;">暂无公告</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($announcements as $a): ?>
            <div style="background:rgba(255,255,255,0.03);border-radius:var(--radius);padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
                    <div>
                        <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                        <span class="badge <?php echo $a['is_active'] ? 'badge-success' : 'badge-danger'; ?>" style="margin-left:8px;">
                            <?php echo $a['is_active'] ? '显示中' : '已隐藏'; ?>
                        </span>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="toggle_announcement" class="btn btn-sm btn-outline">
                                <?php echo $a['is_active'] ? '隐藏' : '显示'; ?>
                            </button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('确定删除？')">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">删除</button>
                        </form>
                    </div>
                </div>
                <p style="color:var(--text-secondary);font-size:14px;margin-bottom:8px;"><?php echo nl2br(htmlspecialchars(mb_substr($a['content'], 0, 100))); ?>...</p>
                <div style="color:var(--text-muted);font-size:12px;"><?php echo $a['created_at']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
