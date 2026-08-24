<?php
$page_title = '用户管理';
require_once __DIR__ . '/header.php';

$conn = db_connect();

// 处理用户操作
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['ban_user'])) {
        $user_id = intval($_POST['user_id']);
        $ban_days = intval($_POST['ban_days']);
        $ban_reason = trim($_POST['ban_reason']);
        
        if ($ban_days > 0) {
            $ban_until = date('Y-m-d H:i:s', time() + $ban_days * 86400);
            $stmt = $conn->prepare('UPDATE users SET is_banned = 1, ban_until = ?, ban_reason = ? WHERE id = ?');
            $stmt->bind_param('ssi', $ban_until, $ban_reason, $user_id);
            $stmt->execute();
            
            // 发送封禁邮件
            $user_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
            if ($u = $user_result->fetch_assoc()) {
                send_email($u['email'], '账号封禁通知 - Jay影视', ban_email_template($u['username'], $ban_until, $ban_reason));
            }
            
            $success = '用户已被封禁至 ' . $ban_until;
        }
    } elseif (isset($_POST['unban_user'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $conn->prepare('UPDATE users SET is_banned = 0, ban_until = NULL, ban_reason = NULL WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $success = '用户已解封';
    }
}

// 获取用户列表
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <h1>用户管理</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>邮箱</th>
                <th>状态</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td>
                    <?php echo htmlspecialchars($u['username']); ?>
                    <?php if ($u['is_admin']): ?><span class="admin-badge">开发者</span><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <?php if ($u['is_banned']): ?>
                    <span class="badge badge-danger">已封禁</span>
                    <?php else: ?>
                    <span class="badge badge-success">正常</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $u['created_at']; ?></td>
                <td>
                    <?php if (!$u['is_admin']): ?>
                        <?php if ($u['is_banned']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="unban_user" class="btn btn-sm" style="background:#10b981;color:white;">解封</button>
                        </form>
                        <?php else: ?>
                        <button onclick="showBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')" class="btn btn-sm btn-danger">封禁</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 封禁弹窗 -->
<div class="modal-overlay" id="banModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">封禁用户</div>
            <button class="modal-close" onclick="document.getElementById('banModal').classList.remove('show')">
                <span class="icon icon-close"></span>
            </button>
        </div>
        <div class="modal-body">
            <form method="post" id="banForm">
                <input type="hidden" name="user_id" id="banUserId">
                <p style="margin-bottom:20px;">您正在封禁用户：<strong id="banUsername" style="color:var(--theme-color);"></strong></p>
                <div class="form-group">
                    <label class="form-label">封禁天数</label>
                    <select name="ban_days" class="form-input">
                        <option value="1">1天</option>
                        <option value="3">3天</option>
                        <option value="7">7天</option>
                        <option value="30">30天</option>
                        <option value="365">1年</option>
                        <option value="36500">永久封禁</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">封禁原因</label>
                    <textarea name="ban_reason" class="form-input" placeholder="请输入封禁原因..."></textarea>
                </div>
                <button type="submit" name="ban_user" class="btn btn-primary btn-block">确认封禁</button>
            </form>
        </div>
    </div>
</div>

<script>
function showBanModal(userId, username) {
    document.getElementById('banUserId').value = userId;
    document.getElementById('banUsername').textContent = username;
    document.getElementById('banModal').classList.add('show');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
