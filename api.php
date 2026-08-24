<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$user = current_user();

switch ($action) {
    // 发送验证码
    case 'send_code':
        $email = trim($_POST['email']);
        $type = isset($_POST['type']) ? $_POST['type'] : 'register';
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['status' => 'error', 'message' => '邮箱格式不正确']);
        }
        
        $conn = db_connect();
        
        // 检查是否已注册
        if ($type == 'register') {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                json_response(['status' => 'error', 'message' => '该邮箱已被注册']);
            }
        }
        
        // 生成验证码
        $code = sprintf('%06d', mt_rand(0, 999999));
        $expires = date('Y-m-d H:i:s', time() + 600); // 10分钟有效期
        
        // 删除旧验证码
        $stmt = $conn->prepare('DELETE FROM email_codes WHERE email = ? AND type = ?');
        $stmt->bind_param('ss', $email, $type);
        $stmt->execute();
        
        // 保存新验证码
        $stmt = $conn->prepare('INSERT INTO email_codes (email, code, type, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $email, $code, $type, $expires);
        $stmt->execute();
        
        // 发送邮件
        if (send_email($email, 'Jay影视邮箱验证码', email_code_template($code))) {
            json_response(['status' => 'success', 'message' => '验证码已发送到您的邮箱']);
        } else {
            json_response(['status' => 'error', 'message' => '验证码发送失败，请稍后重试']);
        }
        break;
    
    // 注册
    case 'register':
        verify_csrf();
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $code = trim($_POST['code']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['status' => 'error', 'message' => '邮箱格式不正确']);
        }
        if (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
            json_response(['status' => 'error', 'message' => '用户名长度必须在2-20个字符之间']);
        }
        if (strlen($password) < 6) {
            json_response(['status' => 'error', 'message' => '密码长度不能少于6位']);
        }
        if ($password !== $confirm_password) {
            json_response(['status' => 'error', 'message' => '两次密码输入不一致']);
        }
        
        $conn = db_connect();
        
        // 验证验证码
        $stmt = $conn->prepare('SELECT id FROM email_codes WHERE email = ? AND code = ? AND type = "register" AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            json_response(['status' => 'error', 'message' => '验证码错误或已过期']);
        }
        
        // 检查邮箱和用户名
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            json_response(['status' => 'error', 'message' => '邮箱或用户名已存在']);
        }
        
        // 创建用户
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (email, username, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $email, $username, $hashed_password);
        
        if ($stmt->execute()) {
            // 标记验证码已使用
            $stmt = $conn->prepare('UPDATE email_codes SET used = 1 WHERE email = ? AND type = "register"');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            
            // 自动登录
            $_SESSION['user_id'] = $conn->insert_id;
            json_response(['status' => 'success', 'message' => '注册成功', 'redirect' => 'index.php']);
        } else {
            json_response(['status' => 'error', 'message' => '注册失败，请稍后重试']);
        }
        break;
    
    // 登录
    case 'login':
        verify_csrf();
        $account = trim($_POST['account']);
        $password = $_POST['password'];
        
        $conn = db_connect();
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
        $stmt->bind_param('ss', $account, $account);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user || !password_verify($password, $user['password'])) {
            json_response(['status' => 'error', 'message' => '账号或密码错误']);
        }
        
        if ($user['is_banned']) {
            if (strtotime($user['ban_until']) > time()) {
                json_response(['status' => 'error', 'message' => '您的账号已被封禁，解封时间：' . $user['ban_until']]);
            } else {
                // 自动解封
                $stmt = $conn->prepare('UPDATE users SET is_banned = 0, ban_until = NULL, ban_reason = NULL WHERE id = ?');
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
            }
        }
        
        $_SESSION['user_id'] = $user['id'];
        $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
        unset($_SESSION['redirect_url']);
        json_response(['status' => 'success', 'message' => '登录成功', 'redirect' => $redirect]);
        break;
    
    // 收藏/取消收藏
    case 'favorite':
        if (!$user) json_response(['status' => 'error', 'message' => '请先登录']);
        verify_csrf();
        
        $media_id = intval($_POST['media_id']);
        $media_type = $_POST['media_type'];
        $title = $_POST['title'];
        $poster = isset($_POST['poster']) ? $_POST['poster'] : '';
        
        $conn = db_connect();
        $stmt = $conn->prepare('SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?');
        $stmt->bind_param('iis', $user['id'], $media_id, $media_type);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?');
            $stmt->bind_param('iis', $user['id'], $media_id, $media_type);
            $stmt->execute();
            json_response(['status' => 'success', 'message' => '已取消收藏', 'action' => 'removed']);
        } else {
            $stmt = $conn->prepare('INSERT INTO favorites (user_id, media_id, media_type, title, poster) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iisss', $user['id'], $media_id, $media_type, $title, $poster);
            $stmt->execute();
            json_response(['status' => 'success', 'message' => '收藏成功', 'action' => 'added']);
        }
        break;
    
    // 记录观看历史
    case 'record_history':
        if (!$user) json_response(['status' => 'error']);
        verify_csrf();
        
        $media_id = intval($_POST['media_id']);
        $media_type = $_POST['media_type'];
        $title = $_POST['title'];
        $poster = isset($_POST['poster']) ? $_POST['poster'] : '';
        $season = isset($_POST['season']) ? intval($_POST['season']) : null;
        $episode = isset($_POST['episode']) ? intval($_POST['episode']) : null;
        $episode_title = isset($_POST['episode_title']) ? $_POST['episode_title'] : null;
        
        $conn = db_connect();
        $stmt = $conn->prepare('INSERT INTO watch_history (user_id, media_id, media_type, title, poster, season, episode, episode_title, watch_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE watched_at = NOW(), watch_time = watch_time + 1');
        $stmt->bind_param('iisssiis', $user['id'], $media_id, $media_type, $title, $poster, $season, $episode, $episode_title);
        $stmt->execute();
        json_response(['status' => 'success']);
        break;
    
    // 删除观看历史
    case 'delete_history':
        if (!$user) json_response(['status' => 'error']);
        $id = intval($_POST['id']);
        
        $conn = db_connect();
        $stmt = $conn->prepare('DELETE FROM watch_history WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user['id']);
        $stmt->execute();
        json_response(['status' => 'success']);
        break;
    
    // 删除收藏
    case 'delete_favorite':
        if (!$user) json_response(['status' => 'error']);
        $id = intval($_POST['id']);
        
        $conn = db_connect();
        $stmt = $conn->prepare('DELETE FROM favorites WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user['id']);
        $stmt->execute();
        json_response(['status' => 'success']);
        break;
    
    // 更新头像
    case 'update_avatar':
        if (!$user) json_response(['status' => 'error', 'message' => '请先登录']);
        verify_csrf();
        
        $avatar = $_POST['avatar'];
        
        $conn = db_connect();
        $stmt = $conn->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        $stmt->bind_param('si', $avatar, $user['id']);
        $stmt->execute();
        json_response(['status' => 'success', 'message' => '头像更新成功']);
        break;
    
    // 提交反馈
    case 'submit_feedback':
        if (!$user) json_response(['status' => 'error', 'message' => '请先登录']);
        verify_csrf();
        
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if (empty($title) || empty($content)) {
            json_response(['status' => 'error', 'message' => '请填写完整信息']);
        }
        
        $conn = db_connect();
        $stmt = $conn->prepare('INSERT INTO feedbacks (user_id, title, content) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user['id'], $title, $content);
        $stmt->execute();
        json_response(['status' => 'success', 'message' => '反馈提交成功']);
        break;
    
    // 回复反馈
    case 'reply_feedback':
        if (!$user) json_response(['status' => 'error', 'message' => '请先登录']);
        verify_csrf();
        
        $feedback_id = intval($_POST['feedback_id']);
        $content = trim($_POST['content']);
        
        if (empty($content)) {
            json_response(['status' => 'error', 'message' => '请输入回复内容']);
        }
        
        $conn = db_connect();
        $stmt = $conn->prepare('INSERT INTO feedback_replies (feedback_id, user_id, content, is_admin) VALUES (?, ?, ?, ?)');
        $is_admin = $user['is_admin'] ? 1 : 0;
        $stmt->bind_param('iisi', $feedback_id, $user['id'], $content, $is_admin);
        $stmt->execute();
        json_response(['status' => 'success', 'message' => '回复成功']);
        break;
    
    // 点赞反馈
    case 'like_feedback':
        if (!$user) json_response(['status' => 'error', 'message' => '请先登录']);
        
        $feedback_id = intval($_POST['feedback_id']);
        $conn = db_connect();
        
        $stmt = $conn->prepare('SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $feedback_id, $user['id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare('DELETE FROM feedback_likes WHERE feedback_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $feedback_id, $user['id']);
            $stmt->execute();
            $conn->query("UPDATE feedbacks SET likes = likes - 1 WHERE id = $feedback_id");
            json_response(['status' => 'success', 'action' => 'unliked']);
        } else {
            $stmt = $conn->prepare('INSERT INTO feedback_likes (feedback_id, user_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $feedback_id, $user['id']);
            $stmt->execute();
            $conn->query("UPDATE feedbacks SET likes = likes + 1 WHERE id = $feedback_id");
            json_response(['status' => 'success', 'action' => 'liked']);
        }
        break;
    
    // 关闭公告
    case 'dismiss_announcement':
        $announcement_id = intval($_POST['announcement_id']);
        $dont_show = isset($_POST['dont_show']);
        
        if ($dont_show) {
            $conn = db_connect();
            $session_id = session_id();
            $user_id = $user ? $user['id'] : null;
            
            if ($user_id) {
                $stmt = $conn->prepare('INSERT IGNORE INTO announcement_dismissed (announcement_id, user_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $announcement_id, $user_id);
            } else {
                $stmt = $conn->prepare('INSERT IGNORE INTO announcement_dismissed (announcement_id, session_id) VALUES (?, ?)');
                $stmt->bind_param('is', $announcement_id, $session_id);
            }
            $stmt->execute();
        }
        json_response(['status' => 'success']);
        break;
    
    default:
        json_response(['status' => 'error', 'message' => '未知操作']);
}
