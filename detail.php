<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp.php';

$current_user = current_user();
if (!$current_user) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?message=' . urlencode('需要登录才可以观看哦，如没有账号请注册！'));
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'movie';
$season_num = isset($_GET['season']) ? intval($_GET['season']) : 1;
$episode_num = isset($_GET['episode']) ? intval($_GET['episode']) : 1;
$audio = isset($_GET['audio']) ? $_GET['audio'] : 'original';

$page_title = '加载中...';
require_once __DIR__ . '/header.php';

$tmdb_key = get_config('tmdb_api_key', '');
$player_parser = get_config('player_parser', 'https://svip.ffzyplay.com/?url=');
$play_sources = get_play_sources();

// 获取详情
$details = null;
$seasons = [];
$episodes = [];
$current_season = null;
$current_episode = null;
$play_url = '';

if ($tmdb_key) {
    $details = tmdb_api($type . '/' . $id, ['append_to_response' => 'credits,season/' . $season_num . '/episodes']);
    
    if ($details && $type == 'tv') {
        $seasons = $details['seasons'] ?? [];
        $season_data = tmdb_api($type . '/' . $id . '/season/' . $season_num);
        if ($season_data && isset($season_data['episodes'])) {
            $episodes = $season_data['episodes'];
        }
    }
}

// 示例数据
if (!$details) {
    $details = [
        'id' => $id,
        'title' => '西游记之再世妖王',
        'name' => '',
        'vote_average' => 8.7,
        'release_date' => '2024-01-01',
        'first_air_date' => '',
        'runtime' => 120,
        'episode_run_time' => [45],
        'overview' => '混沌初开，世间万物生灵涂炭，妖猴肆虐。孙悟空为寻求正义，踏上了一条充满挑战与守护的道路，与各路妖魔鬼怪展开激烈战斗，最终成为守护苍生的英雄。',
        'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg',
        'backdrop_path' => '/cqfc8rtydsfM05jF1CqSgYdMhVn.jpg',
        'genres' => [['name' => '动画'], ['name' => '奇幻'], ['name' => '冒险']],
        'credits' => [
            'cast' => [
                ['name' => '边江'], ['name' => '张磊'], ['name' => '蔡海婷']
            ]
        ]
    ];
    
    if ($type == 'tv') {
        $seasons = [
            ['season_number' => 1, 'name' => '第1季', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', 'air_date' => '2024-01-01'],
            ['season_number' => 2, 'name' => '第2季', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg', 'air_date' => '2025-01-01']
        ];
        
        for ($i = 1; $i <= 12; $i++) {
            $episodes[] = [
                'episode_number' => $i,
                'name' => "第{$i}集",
                'still_path' => '/cqfc8rtydsfM05jF1CqSgYdMhVn.jpg',
                'overview' => '本集精彩内容...',
                'air_date' => '2024-01-' . str_pad($i, 2, '0', STR_PAD_LEFT)
            ];
        }
    }
}

$title = $details['title'] ?? $details['name'];
$year = substr(($details['release_date'] ?? $details['first_air_date'] ?? ''), 0, 4);
$rating = $details['vote_average'] ?? 0;
$genres = array_map(function($g) { return $g['name']; }, $details['genres'] ?? []);
$cast = array_slice(array_map(function($c) { return $c['name']; }, $details['credits']['cast'] ?? []), 0, 6);

$is_chinese = false;
$chinese_keywords = ['国产', '中国', '大陆', '香港', '台湾', '华语'];
$production_countries = array_map(function($c) { return $c['name'] ?? ''; }, $details['production_countries'] ?? []);
foreach ($chinese_keywords as $kw) {
    if (stripos($title, $kw) !== false || in_array('中国', $production_countries)) {
        $is_chinese = true;
        break;
    }
}

// 记录播放历史
if ($current_user) {
    $poster = $details['poster_path'] ?? '';
    $episode_title = $type == 'tv' ? ($episodes[$episode_num - 1]['name'] ?? "第{$episode_num}集") : null;
    recordHistorySilent($id, $type, $title, $poster, $type == 'tv' ? $season_num : null, $type == 'tv' ? $episode_num : null, $episode_title);
}

function recordHistorySilent($media_id, $media_type, $title, $poster, $season, $episode, $episode_title) {
    global $current_user;
    $conn = db_connect();
    $stmt = $conn->prepare('INSERT INTO watch_history (user_id, media_id, media_type, title, poster, season, episode, episode_title, watch_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('iisssiis', $current_user['id'], $media_id, $media_type, $title, $poster, $season, $episode, $episode_title);
    @$stmt->execute();
}

$play_source_url = !empty($play_sources) ? $play_sources[0]['url'] : '';
$search_title = $title . ($audio == 'mandarin' ? ' 普通话' : '');
?>

<div class="container player-page">
    <!-- 播放器 -->
    <div class="player-container">
        <iframe id="playerFrame" src="<?php echo htmlspecialchars($player_parser . urlencode($search_title)); ?>" allowfullscreen></iframe>
    </div>
    
    <!-- 播放控制 -->
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);padding:20px;margin-bottom:24px;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <span style="color:var(--text-secondary);font-weight:500;">播放源：</span>
            <select id="sourceSelect" class="form-input" style="width:auto;padding:8px 14px;">
                <?php foreach ($play_sources as $idx => $src): ?>
                <option value="<?php echo htmlspecialchars($src['url']); ?>"><?php echo htmlspecialchars($src['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <?php if (!$is_chinese): ?>
            <span style="color:var(--text-secondary);font-weight:500;margin-left:12px;">配音：</span>
            <select id="audioSelect" class="form-input" style="width:auto;padding:8px 14px;">
                <option value="original" <?php echo $audio == 'original' ? 'selected' : ''; ?>>原音</option>
                <option value="mandarin" <?php echo $audio == 'mandarin' ? 'selected' : ''; ?>>普通话</option>
            </select>
            <?php endif; ?>
            
            <button class="btn btn-primary btn-sm" onclick="toggleFavorite(<?php echo $id; ?>, '<?php echo $type; ?>', '<?php echo htmlspecialchars(addslashes($title)); ?>', '<?php echo $details['poster_path']; ?>')">
                <span class="icon icon-heart"></span>
                收藏
            </button>
        </div>
    </div>
    
    <!-- 详情布局 -->
    <div class="detail-layout">
        <div class="detail-poster">
            <img src="<?php echo tmdb_image($details['poster_path'], 'w500'); ?>" alt="<?php echo htmlspecialchars($title); ?>">
        </div>
        
        <div class="detail-info">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            
            <div class="detail-meta">
                <span class="detail-rating">★ <?php echo number_format($rating, 1); ?></span>
                <span><?php echo $year; ?></span>
                <?php if ($type == 'movie' && !empty($details['runtime'])): ?>
                <span><?php echo $details['runtime']; ?>分钟</span>
                <?php elseif (!empty($details['episode_run_time'][0])): ?>
                <span>每集<?php echo $details['episode_run_time'][0]; ?>分钟</span>
                <?php endif; ?>
                <?php foreach ($genres as $g): ?>
                <span><?php echo htmlspecialchars($g); ?></span>
                <?php endforeach; ?>
            </div>
            
            <div class="detail-desc"><?php echo htmlspecialchars($details['overview']); ?></div>
            
            <?php if (!empty($cast)): ?>
            <div class="detail-section">
                <h3>演员</h3>
                <p style="color:var(--text-secondary);"><?php echo implode(' / ', array_map('htmlspecialchars', $cast)); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- 季选择 -->
            <?php if ($type == 'tv' && count($seasons) > 1): ?>
            <div class="detail-section">
                <h3>选择季</h3>
                <div class="season-selector">
                    <?php foreach ($seasons as $s): ?>
                    <?php if ($s['season_number'] > 0): ?>
                    <a href="?id=<?php echo $id; ?>&type=tv&season=<?php echo $s['season_number']; ?>&episode=1&audio=<?php echo $audio; ?>" 
                       class="season-btn <?php echo $s['season_number'] == $season_num ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($s['name']); ?>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 集数选择 -->
            <?php if ($type == 'tv' && !empty($episodes)): ?>
            <div class="detail-section">
                <h3>选集</h3>
                <div class="episodes-grid">
                    <?php foreach ($episodes as $ep): ?>
                    <?php $is_active = $ep['episode_number'] == $episode_num; ?>
                    <a href="?id=<?php echo $id; ?>&type=tv&season=<?php echo $season_num; ?>&episode=<?php echo $ep['episode_number']; ?>&audio=<?php echo $audio; ?>" 
                       class="episode-card <?php echo $is_active ? 'active' : ''; ?>">
                        <div class="episode-thumb" data-num="<?php echo $ep['episode_number']; ?>" style="background-image:url('<?php echo tmdb_image($ep['still_path'] ?? $details['backdrop_path'], 'w300'); ?>')"></div>
                        <div class="episode-title" title="<?php echo htmlspecialchars($ep['name']); ?>"><?php echo htmlspecialchars($ep['name']); ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const parserUrl = '<?php echo htmlspecialchars($player_parser); ?>';
const baseTitle = '<?php echo htmlspecialchars(addslashes($title)); ?>';

function changePlay() {
    const audio = document.getElementById('audioSelect') ? document.getElementById('audioSelect').value : 'original';
    const fullTitle = baseTitle + (audio === 'mandarin' ? ' 普通话' : '');
    document.getElementById('playerFrame').src = parserUrl + encodeURIComponent(fullTitle);
    
    const url = new URL(window.location);
    url.searchParams.set('audio', audio);
    window.history.replaceState({}, '', url);
}

if (document.getElementById('audioSelect')) {
    document.getElementById('audioSelect').addEventListener('change', changePlay);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
