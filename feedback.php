<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';
$user = current_user();
$page_title = '用户反馈';
require_once __DIR__ . '/header.php';

$conn = db_connect();

// 处理反馈提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    if (!$user) {
        $error = '请先登录';
    } else {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        if (empty($title) || empty($content)) {
            $error = '请填写完整信息';
        } else {
            $stmt = $conn->prepare('INSERT INTO feedbacks (user_id, title, content) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $user['id'], $title, $content);
            $stmt->execute();
            $success = '反馈提交成功，感谢您的建议！';
        }
    }
}

// 处理回复提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    if (!$user) {
        $error = '请先登录';
    } else {
        $feedback_id = intval($_POST['feedback_id']);
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $stmt = $conn->prepare('INSERT INTO feedback_replies (feedback_id, user_id, content, is_admin) VALUES (?, ?, ?, ?)');
            $is_admin = $user['is_admin'] ? 1 : 0;
            $stmt->bind_param('iisi', $feedback_id, $user['id'], $content, $is_admin);
            $stmt->execute();
            header('Location: feedback.php');
            exit;
        }
    }
}

// 获取反馈列表
$result = $conn->query("SELECT f.*, u.username, u.is_admin as user_is_admin, u.avatar as user_avatar FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $row['replies'] = [];
    $feedbacks[] = $row;
}

// 获取回复
if (!empty($feedbacks)) {
    $fb_ids = implode(',', array_column($feedbacks, 'id'));
    $reply_result = $conn->query("SELECT r.*, u.username, u.is_admin as user_is_admin FROM feedback_replies r JOIN users u ON r.user_id = u.id WHERE r.feedback_id IN ($fb_ids) ORDER BY r.is_admin DESC, r.created_at ASC");
    while ($reply = $reply_result->fetch_assoc()) {
        foreach ($feedbacks as &$fb) {
            if ($fb['id'] == $reply['feedback_id']) {
                $fb['replies'][] = $reply;
                break;
            }
        }
    }
}

// 获取用户点赞状态
$liked_feedbacks = [];
if ($user) {
    $likes = $conn->query("SELECT feedback_id FROM feedback_likes WHERE user_id = " . $user['id']);
    while ($like = $likes->fetch_assoc()) {
        $liked_feedbacks[] = $like['feedback_id'];
    }
}
?>

<div class="container feedback-page">
    <h1 style="font-size:32px;margin-bottom:8px;">用户反馈</h1>
    <p style="color:var(--text-secondary);margin-bottom:32px;">有任何问题或建议都可以在这里告诉我们</p>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($user): ?>
    <div class="feedback-form-card">
        <h3>提交反馈</h3>
        <form method="post">
            <div class="form-group">
                <label class="form-label">反馈标题</label>
                <input type="text" name="title" class="form-input" placeholder="简单描述您的问题" required>
            </div>
            <div class="form-group">
                <label class="form-label">详细内容</label>
                <textarea name="content" class="form-input" placeholder="请详细描述您遇到的问题或建议..." required></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn btn-primary">提交反馈</button>
        </form>
    </div>
    <?php else: ?>
    <div class="feedback-form-card" style="text-align:center;padding:40px;">
        <p style="color:var(--text-secondary);margin-bottom:20px;">请先登录后再提交反馈</p>
        <a href="login.php" class="btn btn-primary">立即登录</a>
    </div>
    <?php endif; ?>
    
    <h2 style="margin-bottom:20px;">所有反馈 (<?php echo count($feedbacks); ?>)</h2>
    
    <div class="feedback-list">
        <?php foreach ($feedbacks as $fb): ?>
        <div class="feedback-item fade-in">
            <div class="feedback-header">
                <div class="feedback-user-avatar" style="background:<?php echo generate_avatar_color($fb['username']); ?>">
                    <?php if ($fb['user_avatar']): ?>
                        <img src="<?php echo htmlspecialchars($fb['user_avatar']); ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo mb_substr($fb['username'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="feedback-user-info">
                    <h4>
                        <?php echo htmlspecialchars($fb['username']); ?>
                        <?php if ($fb['user_is_admin']): ?>
                        <span class="admin-badge">开发者</span>
                        <?php endif; ?>
                    </h4>
                    <div class="feedback-time"><?php echo $fb['created_at']; ?></div>
                </div>
            </div>
            
            <div class="feedback-title"><?php echo htmlspecialchars($fb['title']); ?></div>
            <div class="feedback-content"><?php echo nl2br(htmlspecialchars($fb['content'])); ?></div>
            
            <div class="feedback-actions">
                <button class="feedback-like <?php echo in_array($fb['id'], $liked_feedbacks) ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $fb['id']; ?>, this)">
                    <span class="icon icon-heart"></span>
                    <span class="like-count"><?php echo $fb['likes']; ?></span>
                </button>
                <button class="feedback-like" onclick="toggleReplyForm(<?php echo $fb['id']; ?>)">
                    💬 回复
                </button>
            </div>
            
            <!-- 回复列表 -->
            <?php if (!empty($fb['replies'])): ?>
            <div class="replies-section">
                <?php 
                $replies = $fb['replies'];
                $show_all = count($replies) <= 3;
                foreach ($replies as $idx => $reply): 
                    $hidden = !$show_all && $idx >= 2;
                ?>
                <div class="reply-item <?php echo $reply['user_is_admin'] ? 'admin-reply' : ''; ?>" <?php echo $hidden ? 'style="display:none;"' : ''; ?>>
                    <div class="reply-avatar" style="background:<?php echo $reply['user_is_admin'] ? 'linear-gradient(135deg,#ef4444,#dc2626)' : generate_avatar_color($reply['username']); ?>">
                        <?php echo mb_substr($reply['username'], 0, 1); ?>
                    </div>
                    <div class="reply-content">
                        <div class="reply-header">
                            <span class="reply-username">
                                <?php echo htmlspecialchars($reply['username']); ?>
                                <?php if ($reply['user_is_admin']): ?>
                                <span class="admin-badge">开发者</span>
                                <?php endif; ?>
                            </span>
                            <span class="reply-time"><?php echo $reply['created_at']; ?></span>
                        </div>
                        <div class="reply-text"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($replies) > 3): ?>
                <button class="expand-replies" onclick="expandReplies(this)">展开全部 <?php echo count($replies); ?> 条回复</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 回复表单 -->
            <div class="reply-form" id="reply-form-<?php echo $fb['id']; ?>" style="display:none;">
                <input type="text" id="reply-input-<?php echo $fb['id']; ?>" class="form-input" placeholder="写下你的回复..." onkeypress="if(event.key==='Enter')submitReply(<?php echo $fb['id']; ?>)">
                <button class="btn btn-primary btn-sm" onclick="submitReply(<?php echo $fb['id']; ?>)">发送</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleLike(fbId, btn) {
    <?php if (!$user): ?>
    showToast('请先登录', 'error');
    setTimeout(() => window.location.href = 'login.php', 1000);
    return;
    <?php endif; ?>
    
    fetch('api.php?action=like_feedback', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'feedback_id=' + fbId
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            btn.classList.toggle('liked', data.action === 'liked');
            const countEl = btn.querySelector('.like-count');
            countEl.textContent = parseInt(countEl.textContent) + (data.action === 'liked' ? 1 : -1);
        }
    });
}

function toggleReplyForm(fbId) {
    <?php if (!$user): ?>
    showToast('请先登录', 'error');
    setTimeout(() => window.location.href = 'login.php', 1000);
    return;
    <?php endif; ?>
    const form = document.getElementById('reply-form-' + fbId);
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    if (form.style.display === 'flex') {
        document.getElementById('reply-input-' + fbId).focus();
    }
}

function submitReply(fbId) {
    const input = document.getElementById('reply-input-' + fbId);
    const content = input.value.trim();
    if (!content) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="feedback_id" value="' + fbId + '"><input type="hidden" name="content" value="' + content.replace(/"/g, '&quot;') + '"><input type="hidden" name="submit_reply" value="1"><input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">';
    document.body.appendChild(form);
    form.submit();
}

function expandReplies(btn) {
    const section = btn.parentElement;
    section.querySelectorAll('.reply-item').forEach(item => item.style.display = 'flex');
    btn.remove();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
