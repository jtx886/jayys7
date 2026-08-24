<?php
$page_title = '反馈管理';
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reply_feedback'])) {
        $feedback_id = intval($_POST['feedback_id']);
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $stmt = $conn->prepare('INSERT INTO feedback_replies (feedback_id, user_id, content, is_admin) VALUES (?, ?, ?, 1)');
            $stmt->bind_param('iis', $feedback_id, $admin['id'], $content);
            $stmt->execute();
            $success = '回复成功';
        }
    } elseif (isset($_POST['delete_feedback'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM feedbacks WHERE id = $id");
        $conn->query("DELETE FROM feedback_replies WHERE feedback_id = $id");
        $success = '反馈已删除';
    }
}

$feedbacks = $conn->query("SELECT f.*, u.username FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetch_all(MYSQLI_ASSOC);
foreach ($feedbacks as &$fb) {
    $res = $conn->query("SELECT r.*, u.username FROM feedback_replies r JOIN users u ON r.user_id = u.id WHERE r.feedback_id = " . $fb['id'] . " ORDER BY r.created_at ASC");
    $fb['replies'] = $res->fetch_all(MYSQLI_ASSOC);
}
unset($fb);
?>

<div class="admin-header">
    <h1>反馈管理</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($feedbacks as $fb): ?>
    <div class="dashboard-card">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
            <div>
                <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($fb['title']); ?></h3>
                <div style="color:var(--text-muted);font-size:13px;">
                    <?php echo htmlspecialchars($fb['username']); ?> · <?php echo $fb['created_at']; ?> · ❤️ <?php echo $fb['likes']; ?>
                </div>
            </div>
            <form method="post" onsubmit="return confirm('确定删除？')">
                <input type="hidden" name="id" value="<?php echo $fb['id']; ?>">
                <button type="submit" name="delete_feedback" class="btn btn-sm btn-danger">删除</button>
            </form>
        </div>
        <p style="color:var(--text-secondary);margin-bottom:16px;"><?php echo nl2br(htmlspecialchars($fb['content'])); ?></p>
        
        <?php if (!empty($fb['replies'])): ?>
        <div style="border-top:1px solid var(--border-color);padding-top:16px;margin-bottom:16px;">
            <?php foreach ($fb['replies'] as $reply): ?>
            <div class="reply-item <?php echo $reply['is_admin'] ? 'admin-reply' : ''; ?>" style="margin-bottom:8px;">
                <div class="reply-avatar" style="background:<?php echo $reply['is_admin'] ? 'linear-gradient(135deg,#ef4444,#dc2626)' : 'var(--bg-card-hover)'; ?>">
                    <?php echo mb_substr($reply['username'], 0, 1); ?>
                </div>
                <div class="reply-content">
                    <div class="reply-header">
                        <span class="reply-username">
                            <?php echo htmlspecialchars($reply['username']); ?>
                            <?php if ($reply['is_admin']): ?><span class="admin-badge">开发者</span><?php endif; ?>
                        </span>
                        <span class="reply-time"><?php echo $reply['created_at']; ?></span>
                    </div>
                    <div class="reply-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" style="display:flex;gap:10px;">
            <input type="hidden" name="feedback_id" value="<?php echo $fb['id']; ?>">
            <input type="text" name="content" class="form-input" placeholder="回复此反馈..." required>
            <button type="submit" name="reply_feedback" class="btn btn-primary">回复</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
