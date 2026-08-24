<?php
$page_title = '播放源管理';
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_sources'])) {
        $names = $_POST['source_name'];
        $urls = $_POST['source_url'];
        $sources = [];
        foreach ($names as $i => $name) {
            if (!empty($name) && !empty($urls[$i])) {
                $sources[] = ['name' => $name, 'url' => $urls[$i]];
            }
        }
        update_config('play_sources', $sources);
        $success = '播放源已保存';
    }
}

$play_sources = get_play_sources();
if (empty($play_sources)) {
    $play_sources = [['name' => '', 'url' => '']];
}
?>

<div class="admin-header">
    <h1>播放源管理</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-card">
    <form method="post">
        <div id="sourcesList">
            <?php foreach ($play_sources as $idx => $src): ?>
            <div class="source-item">
                <input type="text" name="source_name[]" class="form-input" placeholder="源名称" value="<?php echo htmlspecialchars($src['name']); ?>" style="max-width:200px;">
                <input type="text" name="source_url[]" class="form-input" placeholder="API地址" value="<?php echo htmlspecialchars($src['url']); ?>">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline" onclick="addSource()" style="margin-bottom:20px;">+ 添加播放源</button>
        <button type="submit" name="save_sources" class="btn btn-primary">保存设置</button>
    </form>
</div>

<div style="margin-top:24px;" class="dashboard-card">
    <h3>说明</h3>
    <p style="color:var(--text-secondary);line-height:1.8;">
        • 播放源API需要返回标准JSON格式，包含vod_play_url字段<br>
        • 默认播放源：https://api.yyzy-tv.vip/inc/apijson.php<br>
        • 解析播放器：<span id="parserUrl"><?php echo htmlspecialchars(get_config('player_parser', 'https://svip.ffzyplay.com/?url=')); ?></span><br>
        • 解析播放器可在"网站设置"中修改
    </p>
</div>

<script>
function addSource() {
    const div = document.createElement('div');
    div.className = 'source-item';
    div.innerHTML = '<input type="text" name="source_name[]" class="form-input" placeholder="源名称" style="max-width:200px;"><input type="text" name="source_url[]" class="form-input" placeholder="API地址"><button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>';
    document.getElementById('sourcesList').appendChild(div);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
