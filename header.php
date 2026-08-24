<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';

$current_user = current_user();
$theme_color = get_config('theme_color', '#7c3aed');
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$site_name = get_config('site_name', 'Jay影视');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . $site_name : $site_name; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --theme-color: <?php echo $theme_color; ?>;
            --theme-light: <?php echo adjust_brightness($theme_color, 20); ?>;
            --theme-dark: <?php echo adjust_brightness($theme_color, -20); ?>;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <a href="index.php" class="logo">
                <span class="icon-logo"></span>
                <span><?php echo $site_name; ?></span>
            </a>
            <nav class="nav-links">
                <li><a href="index.php" class="<?php echo $current_page == 'index' ? 'active' : ''; ?>">首页</a></li>
                <li><a href="category.php?type=movie" class="<?php echo isset($_GET['type']) && $_GET['type'] == 'movie' ? 'active' : ''; ?>">电影</a></li>
                <li><a href="category.php?type=tv" class="<?php echo isset($_GET['type']) && $_GET['type'] == 'tv' ? 'active' : ''; ?>">电视剧</a></li>
                <li><a href="category.php?type=anime" class="<?php echo isset($_GET['type']) && $_GET['type'] == 'anime' ? 'active' : ''; ?>">动漫</a></li>
                <li><a href="category.php?type=variety" class="<?php echo isset($_GET['type']) && $_GET['type'] == 'variety' ? 'active' : ''; ?>">综艺</a></li>
                <li><a href="feedback.php" class="<?php echo $current_page == 'feedback' ? 'active' : ''; ?>">反馈</a></li>
            </nav>
            <div class="search-box">
                <span class="icon icon-search"></span>
                <input type="text" id="searchInput" placeholder="搜索电影、电视剧，动漫..." onkeypress="handleSearch(event)">
            </div>
            <div class="header-actions">
                <?php if ($current_user): ?>
                <div class="user-menu">
                    <div class="user-avatar" onclick="toggleUserMenu()">
                        <?php if ($current_user['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($current_user['avatar']); ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?php echo mb_substr($current_user['username'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php">
                            <span class="icon icon-user"></span>
                            个人中心
                        </a>
                        <a href="favorites.php">
                            <span class="icon icon-heart"></span>
                            我的收藏
                        </a>
                        <a href="history.php">
                            <span class="icon icon-movie"></span>
                            观看历史
                        </a>
                        <?php if ($current_user['is_admin']): ?>
                        <a href="admin/">
                            <span class="admin-badge" style="margin-right:8px;">管理</span>
                            管理后台
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" style="color: #f87171;">
                            <span class="icon icon-close"></span>
                            退出登录
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="btn btn-outline">登录</a>
                <a href="register.php" class="btn btn-primary">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Toast容器 -->
    <div class="toast-container" id="toastContainer"></div>
    
    <main class="main-content">
