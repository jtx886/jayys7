<?php
$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$type_names = [
    'movie' => '电影',
    'tv' => '电视剧',
    'anime' => '动漫',
    'variety' => '综艺'
];
$page_title = $type_names[$type] ?? '电影';
require_once __DIR__ . '/header.php';

$tmdb_key = get_config('tmdb_api_key', '');
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$media_list = [];
$total_pages = 1;

if ($tmdb_key) {
    $endpoint = '';
    $params = ['page' => $page];
    switch ($type) {
        case 'movie':
            $endpoint = 'movie/popular';
            break;
        case 'tv':
            $endpoint = 'tv/popular';
            break;
        case 'anime':
            $endpoint = 'discover/tv';
            $params['with_genres'] = '16';
            $params['sort_by'] = 'popularity.desc';
            break;
        case 'variety':
            $endpoint = 'discover/tv';
            $params['with_genres'] = '10764,10767';
            break;
    }
    
    $result = tmdb_api($endpoint, $params);
    if ($result && isset($result['results'])) {
        $media_list = $result['results'];
        $total_pages = $result['total_pages'];
    }
}

// 示例数据
if (empty($media_list)) {
    $sample = [
        ['id' => 10, 'title' => '抓娃娃', 'name' => '', 'vote_average' => 7.1, 'release_date' => '2024-07-16', 'first_air_date' => '', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
        ['id' => 11, 'title' => '热辣滚烫', 'name' => '', 'vote_average' => 7.4, 'release_date' => '2024-02-10', 'first_air_date' => '', 'poster_path' => '/nGxUxi3PfXDRm7Vg95VBNgNM8yP.jpg'],
        ['id' => 12, 'title' => '飞驰人生2', 'name' => '', 'vote_average' => 6.9, 'release_date' => '2024-02-10', 'first_air_date' => '', 'poster_path' => '/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg'],
        ['id' => 13, 'title' => '第二十条', 'name' => '', 'vote_average' => 7.8, 'release_date' => '2024-02-10', 'first_air_date' => '', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg'],
        ['id' => 14, 'title' => '哥斯拉-1.0', 'name' => '', 'vote_average' => 7.5, 'release_date' => '2023-11-03', 'first_air_date' => '', 'poster_path' => '/e3NBGiA3zWxJ5EfM6afp9c05pS.jpg'],
        ['id' => 15, 'title' => '奥本海默', 'name' => '', 'vote_average' => 8.8, 'release_date' => '2023-07-21', 'first_air_date' => '', 'poster_path' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
        ['id' => 20, 'title' => '', 'name' => '庆余年 第二季', 'vote_average' => 8.9, 'release_date' => '', 'first_air_date' => '2024-05-16', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg'],
        ['id' => 21, 'title' => '', 'name' => '斗破苍穹 年番', 'vote_average' => 8.6, 'release_date' => '', 'first_air_date' => '2022-07-31', 'poster_path' => '/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg'],
        ['id' => 22, 'title' => '', 'name' => '难哄', 'vote_average' => 7.9, 'release_date' => '', 'first_air_date' => '2025-02-17', 'poster_path' => '/nGxUxi3PfXDRm7Vg95VBNgNM8yP.jpg'],
        ['id' => 23, 'title' => '', 'name' => '漫长的季节', 'vote_average' => 9.4, 'release_date' => '', 'first_air_date' => '2023-04-22', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
        ['id' => 24, 'title' => '', 'name' => '与凤行', 'vote_average' => 8.2, 'release_date' => '', 'first_air_date' => '2024-03-18', 'poster_path' => '/e3NBGiA3zWxJ5EfM6afp9c05pS.jpg'],
        ['id' => 25, 'title' => '', 'name' => '玫瑰的故事', 'vote_average' => 7.8, 'release_date' => '', 'first_air_date' => '2024-06-08', 'poster_path' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
    ];
    $media_list = $sample;
}
?>

<div class="container">
    <div style="margin-bottom:24px;">
        <h1 style="font-size:32px;margin-bottom:8px;"><?php echo $type_names[$type]; ?></h1>
        <p style="color:var(--text-secondary);">共 <?php echo count($media_list); ?> 部精彩内容</p>
    </div>
    
    <div class="category-tabs">
        <span class="category-tab active">全部</span>
        <span class="category-tab">动作</span>
        <span class="category-tab">喜剧</span>
        <span class="category-tab">爱情</span>
        <span class="category-tab">科幻</span>
        <span class="category-tab">悬疑</span>
        <span class="category-tab">剧情</span>
        <span class="category-tab">动画</span>
    </div>
    
    <div class="media-grid">
        <?php foreach ($media_list as $item): 
            $item_type = ($type == 'movie' || isset($item['title'])) ? 'movie' : 'tv';
            $title = isset($item['title']) ? $item['title'] : $item['name'];
            $year = isset($item['release_date']) ? substr($item['release_date'], 0, 4) : substr($item['first_air_date'], 0, 4);
        ?>
        <div class="media-card" onclick="window.location.href='detail.php?id=<?php echo $item['id']; ?>&type=<?php echo $item_type; ?>'">
            <div class="media-poster">
                <img src="<?php echo tmdb_image($item['poster_path']); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
                <div class="media-rating">★ <?php echo number_format($item['vote_average'], 1); ?></div>
                <div class="media-overlay">
                    <div class="btn-play">
                        <span class="icon icon-play" style="color:white;"></span>
                    </div>
                </div>
            </div>
            <div class="media-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="media-info"><?php echo $year; ?> · <?php echo $type_names[$type]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;">
        <?php if ($page > 1): ?>
        <a href="?type=<?php echo $type; ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline">上一页</a>
        <?php endif; ?>
        <span style="padding:10px 20px;color:var(--text-secondary);">第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页</span>
        <?php if ($page < $total_pages): ?>
        <a href="?type=<?php echo $type; ?>&page=<?php echo $page + 1; ?>" class="btn btn-primary">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        this.parentElement.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
