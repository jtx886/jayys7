<?php
$page_title = '网站设置';
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_settings'])) {
        $site_name = trim($_POST['site_name']);
        $theme_color = $_POST['theme_color'];
        $tmdb_key = trim($_POST['tmdb_api_key']);
        $player_parser = trim($_POST['player_parser']);
        
        update_config('site_name', $site_name);
        update_config('theme_color', $theme_color);
        update_config('tmdb_api_key', $tmdb_key);
        update_config('player_parser', $player_parser);
        
        // SMTP配置
        $smtp_config = [
            'smtp_host' => trim($_POST['smtp_host']),
            'smtp_port' => trim($_POST['smtp_port']),
            'smtp_user' => trim($_POST['smtp_user']),
            'smtp_pass' => trim($_POST['smtp_pass']),
            'smtp_from' => trim($_POST['smtp_from']),
            'smtp_from_name' => trim($_POST['smtp_from_name'])
        ];
        update_config('smtp_config', $smtp_config);
        
        $success = '设置已保存';
    }
}

$site_name = get_config('site_name', 'Jay影视');
$theme_color = get_config('theme_color', '#7c3aed');
$tmdb_key = get_config('tmdb_api_key', '');
$player_parser = get_config('player_parser', 'https://svip.ffzyplay.com/?url=');
$smtp_config = get_config('smtp_config', [
    'smtp_host' => 'smtp.163.com',
    'smtp_port' => 465,
    'smtp_user' => 'jtxnb886@163.com',
    'smtp_pass' => 'FLLRDtadYAfGXp9Y',
    'smtp_from' => 'jtxnb886@163.com',
    'smtp_from_name' => 'Jay影视'
]);
?>

<div class="admin-header">
    <h1>网站设置</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="post">
    <div class="dashboard-card" style="margin-bottom:24px;">
        <h3>基础设置</h3>
        <div class="form-group">
            <label class="form-label">网站名称</label>
            <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($site_name); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">主题颜色</label>
            <div class="color-picker-wrap">
                <input type="color" name="theme_color" class="color-picker" value="<?php echo htmlspecialchars($theme_color); ?>">
                <input type="text" class="form-input" value="<?php echo htmlspecialchars($theme_color); ?>" style="width:150px;" id="colorText" oninput="document.querySelector('.color-picker').value=this.value">
                <div style="display:flex;gap:8px;margin-left:12px;">
                    <?php 
                    $colors = ['#7c3aed', '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#ec4899', '#06b6d4'];
                    foreach ($colors as $c): ?>
                    <button type="button" onclick="setColor('<?php echo $c; ?>')" style="width:30px;height:30px;border-radius:50%;background:<?php echo $c; ?>;border:3px solid <?php echo $theme_color == $c ? 'white' : 'transparent'; ?>;cursor:pointer;"></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card" style="margin-bottom:24px;">
        <h3>API设置</h3>
        <div class="form-group">
            <label class="form-label">TMDB API Key</label>
            <input type="text" name="tmdb_api_key" class="form-input" value="<?php echo htmlspecialchars($tmdb_key); ?>" placeholder="输入您的TMDB API Key">
            <p style="color:var(--text-muted);font-size:13px;margin-top:6px;">API Key可以在 TMDB 官网设置页面获取（图一中的API读访问令牌）</p>
        </div>
        <div class="form-group">
            <label class="form-label">解析播放器地址</label>
            <input type="text" name="player_parser" class="form-input" value="<?php echo htmlspecialchars($player_parser); ?>">
        </div>
    </div>
    
    <div class="dashboard-card" style="margin-bottom:24px;">
        <h3>SMTP邮件设置</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">SMTP主机</label>
                <input type="text" name="smtp_host" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_host']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">端口</label>
                <input type="text" name="smtp_port" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_port']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">邮箱账号</label>
                <input type="text" name="smtp_user" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_user']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">授权码</label>
                <input type="password" name="smtp_pass" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_pass']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">发件人邮箱</label>
                <input type="text" name="smtp_from" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_from']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">发件人名称</label>
                <input type="text" name="smtp_from_name" class="form-input" value="<?php echo htmlspecialchars($smtp_config['smtp_from_name']); ?>">
            </div>
        </div>
    </div>
    
    <button type="submit" name="save_settings" class="btn btn-primary" style="padding:14px 40px;font-size:16px;">保存所有设置</button>
</form>

<script>
function setColor(color) {
    document.querySelector('.color-picker').value = color;
    document.getElementById('colorText').value = color;
}
document.querySelector('.color-picker').addEventListener('input', function() {
    document.getElementById('colorText').value = this.value;
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
