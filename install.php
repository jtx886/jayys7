<?php
// 安装页面
if (file_exists(__DIR__ . '/config_installed.php')) {
    header('Location: index.php');
    exit;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        // 数据库配置
        $db_host = trim($_POST['db_host']);
        $db_user = trim($_POST['db_user']);
        $db_pass = trim($_POST['db_pass']);
        $db_name = trim($_POST['db_name']);
        
        // 测试连接
        $conn = @new mysqli($db_host, $db_user, $db_pass);
        if ($conn->connect_error) {
            $error = '数据库连接失败: ' . $conn->connect_error;
        } else {
            // 创建数据库
            $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($db_name);
            
            // 创建配置文件（先创建，否则config.php会跳转）
            $db_host_escaped = addslashes($db_host);
            $db_user_escaped = addslashes($db_user);
            $db_pass_escaped = addslashes($db_pass);
            $db_name_escaped = addslashes($db_name);
            $config_content = "<?php
// Jay影视数据库配置
define('DB_HOST', '$db_host_escaped');
define('DB_USER', '$db_user_escaped');
define('DB_PASS', '$db_pass_escaped');
define('DB_NAME', '$db_name_escaped');
";
            file_put_contents(__DIR__ . '/config_installed.php', $config_content);
            
            // 导入SQL - 逐行执行避免问题
            $sql = file_get_contents(__DIR__ . '/database.sql');
            // 移除CREATE DATABASE和USE语句
            $sql = preg_replace('/CREATE DATABASE[^;]*;/i', '', $sql);
            $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
            
            // 分割SQL语句
            $sql = str_replace("\r", "\n", $sql);
            $queries = [];
            $current = '';
            $in_string = false;
            $string_char = '';
            for ($i = 0; $i < strlen($sql); $i++) {
                $char = $sql[$i];
                if (!$in_string && ($char == '"' || $char == "'")) {
                    $in_string = true;
                    $string_char = $char;
                } else if ($in_string && $char == $string_char) {
                    $in_string = false;
                }
                if (!$in_string && $char == ';') {
                    $current .= $char;
                    $trimmed = trim($current);
                    if (!empty($trimmed)) {
                        $queries[] = $trimmed;
                    }
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
            if (trim($current)) {
                $queries[] = trim($current);
            }
            
            foreach ($queries as $q) {
                $q = trim($q);
                if ($q && !preg_match('/^\s*--/', $q)) {
                    @$conn->query($q);
                }
            }
            
            // 更新管理员密码为101113
            $admin_pass = password_hash('101113', PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$admin_pass' WHERE username = '杰同学'");
            
            header('Location: install.php?step=2');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay影视 - 安装向导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0f; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-container { width: 100%; max-width: 600px; }
        .logo { text-align: center; margin-bottom: 40px; }
        .logo-icon { display: inline-block; width: 60px; height: 60px; background: linear-gradient(135deg, #7c3aed, #a855f7); border-radius: 16px; position: relative; vertical-align: middle; margin-right: 15px; }
        .logo-icon::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-40%, -50%); border: 16px solid transparent; border-left: 24px solid white; }
        .logo-text { font-size: 42px; font-weight: bold; color: white; vertical-align: middle; }
        .card { background: #12121a; border-radius: 24px; padding: 40px; border: 1px solid rgba(255,255,255,0.05); }
        .title { font-size: 28px; font-weight: 700; color: white; margin-bottom: 10px; }
        .subtitle { color: #888; margin-bottom: 30px; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; color: #ccc; margin-bottom: 10px; font-weight: 500; }
        .form-input { width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.05); border: 2px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 16px; transition: all 0.3s; }
        .form-input:focus { outline: none; border-color: #7c3aed; background: rgba(124,58,237,0.1); }
        .btn { width: 100%; padding: 16px; background: linear-gradient(135deg, #7c3aed, #a855f7); border: none; border-radius: 12px; color: white; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(124,58,237,0.4); }
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
        .steps { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .step { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #888; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .step.active { background: #7c3aed; color: white; }
        .step.done { background: #10b981; color: white; }
        .success-icon { text-align: center; font-size: 80px; margin-bottom: 20px; }
        .info-text { color: #888; line-height: 1.8; margin-bottom: 20px; }
        .info-box { background: rgba(124,58,237,0.1); border: 1px solid rgba(124,58,237,0.3); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .info-box p { color: #c4b5fd; margin-bottom: 8px; }
        .info-box p:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <span class="logo-icon"></span><span class="logo-text">Jay影视</span>
        </div>
        <div class="card">
            <div class="steps">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">2</div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
            <h1 class="title">数据库配置</h1>
            <p class="subtitle">请填写您的MySQL数据库信息</p>
            <form method="post">
                <div class="form-group">
                    <label class="form-label">数据库主机</label>
                    <input type="text" name="db_host" class="form-input" value="localhost" required>
                </div>
                <div class="form-group">
                    <label class="form-label">数据库用户名</label>
                    <input type="text" name="db_user" class="form-input" value="root" required>
                </div>
                <div class="form-group">
                    <label class="form-label">数据库密码</label>
                    <input type="password" name="db_pass" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">数据库名称</label>
                    <input type="text" name="db_name" class="form-input" value="jay_video" required>
                </div>
                <button type="submit" class="btn">开始安装</button>
            </form>
            
            <?php elseif ($step == 2): ?>
            <div class="success-icon">🎉</div>
            <h1 class="title" style="text-align:center;">安装完成！</h1>
            <p class="subtitle" style="text-align:center;">恭喜您，Jay影视已成功安装</p>
            <div class="info-box">
                <p><strong>管理员账号：</strong>杰同学</p>
                <p><strong>管理员密码：</strong>101113</p>
            </div>
            <p class="info-text">请妥善保管您的管理员账号信息。登录后请到管理后台配置TMDB API Key以获取影视数据。</p>
            <a href="index.php" class="btn" style="display:block;text-align:center;text-decoration:none;">进入网站</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
