<?php
// Jay影视配置文件

// PHP版本兼容函数
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null) {
        return substr($str, $start, $length);
    }
}

if (file_exists(__DIR__ . '/config_installed.php')) {
    require_once __DIR__ . '/config_installed.php';
} else {
    // 未安装时跳转到安装页面
    if (basename($_SERVER['PHP_SELF']) != 'install.php') {
        header('Location: install.php');
        exit;
    }
}

// 数据库连接
function db_connect() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('数据库连接失败: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// 获取配置
function get_config($key, $default = null) {
    $conn = db_connect();
    $stmt = $conn->prepare('SELECT `value` FROM config WHERE `key` = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $value = $row['value'];
        $unserialized = @unserialize($value);
        return $unserialized !== false ? $unserialized : $value;
    }
    return $default;
}

// 更新配置
function update_config($key, $value) {
    $conn = db_connect();
    if (is_array($value) || is_object($value)) {
        $value = serialize($value);
    }
    $stmt = $conn->prepare('UPDATE config SET `value` = ? WHERE `key` = ?');
    $stmt->bind_param('ss', $value, $key);
    return $stmt->execute();
}

// Session管理
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 获取当前用户
function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $conn = db_connect();
    $stmt = $conn->prepare('SELECT id, email, username, avatar, is_admin, is_banned, ban_until FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// 检查登录
function require_login() {
    $user = current_user();
    if (!$user) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => '需要登录才可以观看哦，如没有账号请注册！', 'redirect' => 'login.php']);
            exit;
        }
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php?message=' . urlencode('需要登录才可以观看哦，如没有账号请注册！'));
        exit;
    }
    if ($user['is_banned']) {
        $ban_until = strtotime($user['ban_until']);
        if ($ban_until > time()) {
            session_destroy();
            $msg = '您的账号已被封禁，解封时间：' . $user['ban_until'];
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $msg]);
                exit;
            }
            die($msg);
        } else {
            // 自动解封
            $conn = db_connect();
            $stmt = $conn->prepare('UPDATE users SET is_banned = 0, ban_until = NULL, ban_reason = NULL WHERE id = ?');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
        }
    }
    return $user;
}

// 检查管理员
function require_admin() {
    $user = current_user();
    if (!$user || !$user['is_admin']) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

// SMTP发送邮件函数 - 实际实现在smtp.php中
// smtp.php会在header.php中引入并定义send_email函数

// 生成验证码邮件模板
function email_code_template($code) {
    $theme_color = get_config('theme_color', '#7c3aed');
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>邮箱验证码</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; background: #0a0a0f; }
            .container { max-width: 500px; margin: 40px auto; background: #12121a; border-radius: 20px; overflow: hidden; }
            .header { background: linear-gradient(135deg, ' . $theme_color . ', ' . adjust_brightness($theme_color, 30) . '); padding: 40px; text-align: center; }
            .logo { font-size: 32px; font-weight: bold; color: white; }
            .logo-icon { display: inline-block; width: 40px; height: 40px; background: white; border-radius: 10px; margin-right: 10px; vertical-align: middle; position: relative; }
            .logo-icon::before { content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-40%, -50%); border: 12px solid transparent; border-left: 20px solid ' . $theme_color . '; }
            .content { padding: 40px; text-align: center; color: #fff; }
            .title { font-size: 24px; font-weight: 600; margin-bottom: 20px; }
            .message { color: #a0a0b0; margin-bottom: 30px; line-height: 1.6; }
            .code-box { background: rgba(255,255,255,0.05); border: 2px solid ' . $theme_color . '; border-radius: 16px; padding: 30px; margin-bottom: 30px; }
            .code { font-size: 48px; font-weight: bold; letter-spacing: 12px; color: ' . $theme_color . '; font-family: monospace; }
            .footer { padding: 30px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">
                    <span class="logo-icon"></span>Jay影视
                </div>
            </div>
            <div class="content">
                <div class="title">邮箱验证码</div>
                <div class="message">您好！欢迎注册Jay影视，您的验证码是：<br>（验证码10分钟内有效）</div>
                <div class="code-box">
                    <div class="code">' . $code . '</div>
                </div>
                <div class="message">如果这不是您的操作，请忽略此邮件。</div>
            </div>
            <div class="footer">
                © ' . date('Y') . ' Jay影视 版权所有
            </div>
        </div>
    </body>
    </html>';
}

// 调整颜色亮度
function adjust_brightness($hex, $percent) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = min(255, max(0, $r + $percent * 2.55));
    $g = min(255, max(0, $g + $percent * 2.55));
    $b = min(255, max(0, $b + $percent * 2.55));
    
    return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
}

// TMDB API调用
function tmdb_api($endpoint, $params = []) {
    $api_key = get_config('tmdb_api_key', '');
    if (empty($api_key)) return null;
    
    $params['api_key'] = $api_key;
    $params['language'] = 'zh-CN';
    
    $url = 'https://api.themoviedb.org/3/' . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// TMDB图片URL
function tmdb_image($path, $size = 'w500') {
    if (empty($path)) return 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 750"><rect fill="%231a1a2e" width="500" height="750"/><text x="250" y="375" fill="%23666" text-anchor="middle" font-size="24">暂无图片</text></svg>';
    return 'https://image.tmdb.org/t/p/' . $size . $path;
}

// 播放源数据获取
function get_play_sources() {
    $sources = get_config('play_sources', []);
    return is_array($sources) ? $sources : [];
}

// 获取视频播放链接
function get_video_url($title, $type = 'movie') {
    $sources = get_play_sources();
    if (empty($sources)) return null;
    
    $source = $sources[0];
    $api_url = $source['url'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . '?wd=' . urlencode($title));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if ($data && isset($data['list'][0]['vod_play_url'])) {
        $play_urls = explode('#', $data['list'][0]['vod_play_url']);
        if (!empty($play_urls)) {
            return $play_urls[0];
        }
    }
    return null;
}

// 封禁通知邮件
function ban_email_template($username, $ban_until, $reason = '') {
    $theme_color = get_config('theme_color', '#7c3aed');
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><title>账号封禁通知</title></head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin:0; padding:0; background:#0a0a0f;">
        <div style="max-width:500px; margin:40px auto; background:#12121a; border-radius:20px; overflow:hidden;">
            <div style="background:linear-gradient(135deg, #ef4444, #dc2626); padding:40px; text-align:center;">
                <div style="font-size:32px; font-weight:bold; color:white;">
                    <span style="display:inline-block; width:40px; height:40px; background:white; border-radius:10px; margin-right:10px; vertical-align:middle; position:relative;"></span>
                    Jay影视
                </div>
            </div>
            <div style="padding:40px; text-align:center; color:#fff;">
                <div style="font-size:24px; font-weight:600; margin-bottom:20px; color:#ef4444;">账号封禁通知</div>
                <div style="color:#a0a0b0; margin-bottom:30px; line-height:1.6;">
                    您好 ' . htmlspecialchars($username) . '，<br>
                    您的账号已被封禁。
                </div>
                <div style="background:rgba(239,68,68,0.1); border:2px solid #ef4444; border-radius:16px; padding:25px; margin-bottom:30px; text-align:left;">
                    <div style="margin-bottom:10px;"><strong style="color:#fff;">封禁原因：</strong><span style="color:#a0a0b0;">' . ($reason ?: '违反平台规定') . '</span></div>
                    <div><strong style="color:#fff;">解封时间：</strong><span style="color:#ef4444;">' . $ban_until . '</span></div>
                </div>
                <div style="color:#666; font-size:14px;">如有疑问，请联系管理员。</div>
            </div>
            <div style="padding:30px; text-align:center; color:#666; font-size:12px; border-top:1px solid rgba(255,255,255,0.05);">
                © ' . date('Y') . ' Jay影视 版权所有
            </div>
        </div>
    </body>
    </html>';
}

// 通用通知邮件
function notification_email_template($title, $content) {
    $theme_color = get_config('theme_color', '#7c3aed');
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin:0; padding:0; background:#0a0a0f;">
        <div style="max-width:500px; margin:40px auto; background:#12121a; border-radius:20px; overflow:hidden;">
            <div style="background:linear-gradient(135deg, ' . $theme_color . ', ' . adjust_brightness($theme_color, 30) . '); padding:40px; text-align:center;">
                <div style="font-size:32px; font-weight:bold; color:white;">
                    <span style="display:inline-block; width:40px; height:40px; background:white; border-radius:10px; margin-right:10px; vertical-align:middle; position:relative;"></span>
                    Jay影视
                </div>
            </div>
            <div style="padding:40px; color:#fff;">
                <div style="font-size:24px; font-weight:600; margin-bottom:20px;">' . htmlspecialchars($title) . '</div>
                <div style="color:#a0a0b0; line-height:1.8;">' . nl2br(htmlspecialchars($content)) . '</div>
            </div>
            <div style="padding:30px; text-align:center; color:#666; font-size:12px; border-top:1px solid rgba(255,255,255,0.05);">
                © ' . date('Y') . ' Jay影视 版权所有
            </div>
        </div>
    </body>
    </html>';
}

// 生成头像颜色
function generate_avatar_color($username) {
    $colors = ['#7c3aed', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6', '#06b6d4'];
    return $colors[crc32($username) % count($colors)];
}

// 输出JSON
function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// CSRF保护
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        json_response(['status' => 'error', 'message' => '无效的请求']);
    }
}
