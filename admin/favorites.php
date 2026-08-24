<?php
$page_title = '用户收藏';
require_once __DIR__ . '/header.php';

$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$where = '';
if ($user_filter) {
    $where = "WHERE f.user_id = $user_filter";
}

$favorites = $conn->query("SELECT f.*, u.username FROM favorites f JOIN users u ON f.user_id = u.id $where ORDER BY f.created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT id, username FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-header">
    <h1>用户收藏</h1>
</div>

<div class="dashboard-card" style="margin-bottom:24px;">
    <form method="get" style="display:flex;gap:12px;align-items:center;">
        <label style="color:var(--text-secondary);">筛选用户：</label>
        <select name="user_id" class="form-input" style="width:200px;">
            <option value="0">全部用户</option>
            <?php foreach ($users as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo $user_filter == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    </form>
</div>

<div class="dashboard-card" style="padding:0;overflow:hidden;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>影视名称</th>
                <th>类型</th>
                <th>收藏时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($favorites as $f): ?>
            <tr>
                <td><?php echo $f['id']; ?></td>
                <td><?php echo htmlspecialchars($f['username']); ?></td>
                <td><?php echo htmlspecialchars($f['title']); ?></td>
                <td><?php echo $f['media_type'] == 'movie' ? '电影' : '电视剧'; ?></td>
                <td><?php echo $f['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
