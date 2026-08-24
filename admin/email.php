<?php
$page_title = '发送邮件';
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $to_type = $_POST['to_type'];
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    
    if (empty($subject) || empty($content)) {
        $error = '请填写完整信息';
    } else {
        $recipients = [];
        if ($to_type == 'all') {
            $res = $conn->query("SELECT email, username FROM users");
            while ($row = $res->fetch_assoc()) {
                $recipients[] = $row;
            }
        } elseif ($to_type == 'single') {
            $email = trim($_POST['single_email']);
            $res = $conn->prepare("SELECT email, username FROM users WHERE email = ?");
            $res->bind_param('s', $email);
            $res->execute();
            $result = $res->get_result();
            if ($row = $result->fetch_assoc()) {
                $recipients[] = $row;
            }
        }
        
        if (empty($recipients)) {
            $error = '没有找到收件人';
        } else {
            $success_count = 0;
            foreach ($recipients as $r) {
                $body = notification_email_template($subject, $content);
                $body = str_replace('用户您好', htmlspecialchars($r['username']) . '，您好', $body);
                if (send_email($r['email'], $subject, $body)) {
                    $success_count++;
                }
            }
            $success = "邮件发送完成，成功发送 {$success_count} 封";
        }
    }
}

$users = $conn->query("SELECT id, username, email FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <h1>发送邮件通知</h1>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-card" style="max-width:700px;">
    <form method="post">
        <div class="form-group">
            <label class="form-label">收件人</label>
            <select name="to_type" class="form-input" id="toType" onchange="toggleEmailInput()">
                <option value="all">全体用户</option>
                <option value="single">指定用户</option>
            </select>
        </div>
        
        <div class="form-group" id="singleEmailGroup" style="display:none;">
            <label class="form-label">选择用户</label>
            <select name="single_email" class="form-input">
                <?php foreach ($users as $u): ?>
                <option value="<?php echo htmlspecialchars($u['email']); ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">邮件标题</label>
            <input type="text" name="subject" class="form-input" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">邮件内容</label>
            <textarea name="content" class="form-input" rows="8" required placeholder="请输入邮件内容，支持换行..."></textarea>
        </div>
        
        <button type="submit" name="send_email" class="btn btn-primary" onclick="return confirm('确定发送邮件？')">发送邮件</button>
    </form>
</div>

<script>
function toggleEmailInput() {
    const type = document.getElementById('toType').value;
    document.getElementById('singleEmailGroup').style.display = type === 'single' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
