    </main>
    
    <!-- 底部导航（移动端） -->
    <nav class="bottom-nav">
        <div class="bottom-nav-inner">
            <a href="index.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <span class="icon icon-home"></span>
                <span>首页</span>
            </a>
            <a href="search.php" class="bottom-nav-item">
                <span class="icon icon-search"></span>
                <span>搜索</span>
            </a>
            <a href="profile.php" class="bottom-nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['profile.php', 'favorites.php', 'history.php']) ? 'active' : ''; ?>">
                <span class="icon icon-user"></span>
                <span>我的</span>
            </a>
        </div>
    </nav>
    
    <!-- 公告弹窗 -->
    <?php if (!isset($no_announcement) && $current_page == 'index'): ?>
    <?php
    $conn = db_connect();
    $announcements = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
    if ($announcement = $announcements->fetch_assoc()) {
        $dismissed = false;
        $session_id = session_id();
        $user_id = $current_user ? $current_user['id'] : null;
        
        if ($user_id) {
            $stmt = $conn->prepare("SELECT id FROM announcement_dismissed WHERE announcement_id = ? AND user_id = ?");
            $stmt->bind_param('ii', $announcement['id'], $user_id);
        } else {
            $stmt = $conn->prepare("SELECT id FROM announcement_dismissed WHERE announcement_id = ? AND session_id = ?");
            $stmt->bind_param('is', $announcement['id'], $session_id);
        }
        $stmt->execute();
        $dismissed = $stmt->get_result()->num_rows > 0;
        
        if (!$dismissed):
    ?>
    <div class="modal-overlay show" id="announcementModal">
        <div class="modal announcement-modal">
            <div class="modal-header">
                <div class="modal-title">📢 网站公告</div>
                <button class="modal-close" onclick="closeAnnouncement(false)">
                    <span class="icon icon-close"></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                <div class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></div>
                <div class="announcement-actions">
                    <label class="checkbox-label">
                        <input type="checkbox" id="dontShowAgain">
                        不再提示
                    </label>
                    <button class="btn btn-primary" onclick="closeAnnouncement(true)">我知道了</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    function closeAnnouncement(acknowledged) {
        const dontShow = document.getElementById('dontShowAgain').checked;
        document.getElementById('announcementModal').classList.remove('show');
        
        if (dontShow) {
            fetch('api.php?action=dismiss_announcement', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'announcement_id=<?php echo $announcement['id']; ?>&dont_show=1'
            });
        }
    }
    </script>
    <?php endif; } ?>
    <?php endif; ?>
    
    <script>
    // 用户菜单切换
    function toggleUserMenu() {
        document.getElementById('userDropdown').classList.toggle('show');
    }
    
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('userDropdown');
        const avatar = document.querySelector('.user-avatar');
        if (menu && avatar && !avatar.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('show');
        }
    });
    
    // 搜索功能
    function handleSearch(e) {
        if (e.key === 'Enter') {
            const query = e.target.value.trim();
            if (query) {
                window.location.href = 'search.php?q=' + encodeURIComponent(query);
            }
        }
    }
    
    // Toast提示
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    // 收藏功能
    function toggleFavorite(mediaId, mediaType, title, poster) {
        <?php if (!$current_user): ?>
        showToast('请先登录后再收藏', 'error');
        setTimeout(() => window.location.href = 'login.php', 1000);
        return;
        <?php endif; ?>
        
        fetch('api.php?action=favorite', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'media_id=' + mediaId + '&media_type=' + mediaType + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster || '') + '&csrf_token=<?php echo csrf_token(); ?>'
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message);
                const btn = document.querySelector(`[data-favorite-id="${mediaId}"]`);
                if (btn) {
                    btn.classList.toggle('active', data.action === 'added');
                }
            } else {
                showToast(data.message, 'error');
            }
        });
    }
    
    // 记录观看历史
    function recordHistory(mediaId, mediaType, title, poster, season = null, episode = null, episodeTitle = null) {
        <?php if ($current_user): ?>
        fetch('api.php?action=record_history', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'media_id=' + mediaId + '&media_type=' + mediaType + '&title=' + encodeURIComponent(title) + '&poster=' + encodeURIComponent(poster || '') + (season ? '&season=' + season : '') + (episode ? '&episode=' + episode : '') + (episodeTitle ? '&episode_title=' + encodeURIComponent(episodeTitle) : '') + '&csrf_token=<?php echo csrf_token(); ?>'
        });
        <?php endif; ?>
    }
    </script>
</body>
</html>
