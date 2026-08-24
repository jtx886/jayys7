<?php
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_title = $q ? '搜索: ' . $q : '搜索';
require_once __DIR__ . '/header.php';

$results = [];

if ($q) {
    $tmdb_key = get_config('tmdb_api_key', '');
    if ($tmdb_key) {
        $result = tmdb_api('search/multi', ['query' => $q, 'page' => 1]);
        if ($result && isset($result['results'])) {
            $results = array_filter($result['results'], function($item) {
                return in_array($item['media_type'], ['movie', 'tv']);
            });
        }
    }
    
    // 示例搜索结果
    if (empty($results)) {
        $results = [
            ['id' => 1, 'media_type' => 'movie', 'title' => '西游记之再世妖王', 'name' => '', 'vote_average' => 8.7, 'release_date' => '2024-01-01', 'first_air_date' => '', 'overview' => '混沌初开...', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
        ];
    }
}
?>

<div class="container">
    <div style="margin-bottom:24px;">
        <div class="search-box" style="max-width:none;margin:0;">
            <span class="icon icon-search"></span>
            <input type="text" id="searchInput" value="<?php echo htmlspecialchars($q); ?>" placeholder="搜索电影、电视剧，动漫..." onkeypress="if(event.key==='Enter')doSearch()">
        </div>
    </div>
    
    <?php if ($q): ?>
    <h2 style="margin-bottom:20px;">搜索 "<?php echo htmlspecialchars($q); ?>" 的结果 (<?php echo count($results); ?>)</h2>
    
    <?php if (empty($results)): ?>
    <div class="empty-state">
        <div style="font-size:64px;margin-bottom:16px;">🔍</div>
        <p>没有找到相关结果</p>
    </div>
    <?php else: ?>
    <div class="media-grid">
        <?php foreach ($results as $item): 
            $type = $item['media_type'] == 'movie' ? 'movie' : 'tv';
            $title = $item['title'] ?? $item['name'];
            $year = isset($item['release_date']) ? substr($item['release_date'], 0, 4) : substr($item['first_air_date'] ?? '', 0, 4);
        ?>
        <div class="media-card" onclick="window.location.href='detail.php?id=<?php echo $item['id']; ?>&type=<?php echo $type; ?>'">
            <div class="media-poster">
                <img src="<?php echo tmdb_image($item['poster_path'] ?? ''); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
                <?php if (isset($item['vote_average'])): ?>
                <div class="media-rating">★ <?php echo number_format($item['vote_average'], 1); ?></div>
                <?php endif; ?>
                <div class="media-overlay">
                    <div class="btn-play">
                        <span class="icon icon-play" style="color:white;"></span>
                    </div>
                </div>
            </div>
            <div class="media-title"><?php echo htmlspecialchars($title); ?></div>
            <div class="media-info"><?php echo $year ?: '----'; ?> · <?php echo $type == 'movie' ? '电影' : '电视剧'; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="empty-state">
        <div style="font-size:64px;margin-bottom:16px;">🎬</div>
        <p>输入关键词搜索您想看的影视</p>
    </div>
    <?php endif; ?>
</div>

<script>
function doSearch() {
    const q = document.getElementById('searchInput').value.trim();
    if (q) window.location.href = 'search.php?q=' + encodeURIComponent(q);
}

document.getElementById('searchInput').focus();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
