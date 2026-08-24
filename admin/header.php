<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../smtp.php';

$admin = require_admin();
$theme_color = get_config('theme_color', '#7c3aed');
$current_admin_page = basename($_SERVER['PHP_SELF'], '.php');
$site_name = get_config('site_name', 'Jay影视');

// 统计数据
$conn = db_connect();
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_favorites = $conn->query("SELECT COUNT(*) FROM favorites")->fetch_row()[0];
$total_history = $conn->query("SELECT COUNT(*) FROM watch_history")->fetch_row()[0];
$total_feedbacks = $conn->query("SELECT COUNT(*) FROM feedbacks")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - 管理后台' : '管理后台'; ?> - <?php echo $site_name; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        :root {
            --theme-color: <?php echo $theme_color; ?>;
            --theme-light: <?php echo adjust_brightness($theme_color, 20); ?>;
            --theme-dark: <?php echo adjust_brightness($theme_color, -20); ?>;
        }
        body { padding-top: 0; }
        .header { position: relative; }
        .admin-page-title { display:flex;align-items:center;gap:12px; }
        .admin-page-title a { color:var(--text-secondary);display:flex;align-items:center; }
        .admin-page-title a:hover { color:white; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <a href="index.php" class="logo">
                <span class="icon-logo"></span>
                <span>管理后台</span>
            </a>
            <div class="search-box" style="max-width:none;display:none;"></div>
            <div class="header-actions" style="margin-left:auto;">
                <a href="../index.php" class="btn btn-outline btn-sm">返回网站</a>
                <div class="user-menu">
                    <div class="user-avatar" onclick="toggleUserMenu()">
                        <?php if ($admin['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($admin['avatar']); ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo mb_substr($admin['username'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="../profile.php">个人中心</a>
                        <a href="../logout.php" style="color:#f87171;">退出登录</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <ul class="admin-nav">
                <li><a href="index.php" class="<?php echo $current_admin_page == 'index' ? 'active' : ''; ?>">
                    📊 仪表盘
                </a></li>
                <li><a href="users.php" class="<?php echo $current_admin_page == 'users' ? 'active' : ''; ?>">
                    👥 用户管理
                </a></li>
                <li><a href="play_sources.php" class="<?php echo $current_admin_page == 'play_sources' ? 'active' : ''; ?>">
                    ▶️ 播放源管理
                </a></li>
                <li><a href="announcements.php" class="<?php echo $current_admin_page == 'announcements' ? 'active' : ''; ?>">
                    📢 公告管理
                </a></li>
                <li><a href="feedbacks.php" class="<?php echo $current_admin_page == 'feedbacks' ? 'active' : ''; ?>">
                    💬 反馈管理
                </a></li>
                <li><a href="history.php" class="<?php echo $current_admin_page == 'history' ? 'active' : ''; ?>">
                    📺 观看历史
                </a></li>
                <li><a href="favorites.php" class="<?php echo $current_admin_page == 'favorites' ? 'active' : ''; ?>">
                    ❤️ 用户收藏
                </a></li>
                <li><a href="email.php" class="<?php echo $current_admin_page == 'email' ? 'active' : ''; ?>">
                    📧 发送邮件
                </a></li>
                <li><a href="settings.php" class="<?php echo $current_admin_page == 'settings' ? 'active' : ''; ?>">
                    ⚙️ 网站设置
                </a></li>
            </ul>
        </aside>
        
        <main class="admin-main">
