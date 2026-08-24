<?php
$page_title = '首页';
require_once __DIR__ . '/header.php';

$tmdb_key = get_config('tmdb_api_key', '');

// 获取热门电影
$trending_movies = [];
$popular_movies = [];
$popular_tv = [];

if ($tmdb_key) {
    $trending = tmdb_api('trending/all/week');
    if ($trending && isset($trending['results'])) {
        $trending_movies = array_slice($trending['results'], 0, 5);
    }
    
    $movies = tmdb_api('movie/popular');
    if ($movies && isset($movies['results'])) {
        $popular_movies = array_slice($movies['results'], 0, 12);
    }
    
    $tv = tmdb_api('tv/popular');
    if ($tv && isset($tv['results'])) {
        $popular_tv = array_slice($tv['results'], 0, 6);
    }
}

// 如果没有TMDB数据，使用示例数据
if (empty($trending_movies)) {
    $sample_posters = [
        'https://image.tmdb.org/t/p/w500/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg',
        'https://image.tmdb.org/t/p/w500/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg',
        'https://image.tmdb.org/t/p/w500/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg',
        'https://image.tmdb.org/t/p/w500/9Hk8q2YbD5vF5cX7dF8gH9jK0lM.jpg',
        'https://image.tmdb.org/t/p/w500/4mz8dA3C2B1A0z9Y8x7W6v5U4t3S.jpg'
    ];
    $trending_movies = [
        ['id' => 1, 'media_type' => 'movie', 'title' => '西游记之再世妖王', 'name' => '', 'vote_average' => 8.7, 'release_date' => '2024-01-01', 'first_air_date' => '', 'overview' => '混沌初开，世间万物生灵涂炭，妖猴肆虐。孙悟空为寻求正义，踏上了一条充满挑战与守护的道路...', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', 'backdrop_path' => '/cqfc8rtydsfM05jF1CqSgYdMhVn.jpg'],
        ['id' => 2, 'media_type' => 'movie', 'title' => '庆余年 第二季', 'name' => '', 'vote_average' => 8.9, 'release_date' => '2024-05-16', 'first_air_date' => '', 'overview' => '范闲在京都的故事继续展开，新的危机与挑战接踵而至...', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg', 'backdrop_path' => '/g6MlZf779tG2b7b2J0J3C8c0c0b.jpg'],
        ['id' => 3, 'media_type' => 'movie', 'title' => '流浪地球2', 'name' => '', 'vote_average' => 8.3, 'release_date' => '2023-01-22', 'first_air_date' => '', 'overview' => '太阳即将毁灭，人类在地球表面建造出巨大的推进器，寻找新的家园...', 'poster_path' => '/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg', 'backdrop_path' => '/9pCoqX28UQd7vXc7bM6vYhX8lKt.jpg'],
        ['id' => 4, 'media_type' => 'tv', 'title' => '', 'name' => '航海王', 'vote_average' => 9.2, 'release_date' => '', 'first_air_date' => '1999-10-20', 'overview' => '传奇海盗哥尔·D·罗杰在临刑前的一句话，开启了整个大海贼时代...', 'poster_path' => '/e3NBGiA3zWxJ5EfM6afp9c05pS.jpg', 'backdrop_path' => '/gG9fTyDL03fiKnOpf2tr01sncnt.jpg'],
        ['id' => 5, 'media_type' => 'movie', 'title' => '热辣滚烫', 'name' => '', 'vote_average' => 7.4, 'release_date' => '2024-02-10', 'first_air_date' => '', 'overview' => '一个胖女孩的自我救赎之路，通过拳击找到生活的意义...', 'poster_path' => '/nGxUxi3PfXDRm7Vg95VBNgNM8yP.jpg', 'backdrop_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg']
    ];
    $popular_movies = [
        ['id' => 10, 'title' => '抓娃娃', 'vote_average' => 7.1, 'release_date' => '2024-07-16', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
        ['id' => 11, 'title' => '热辣滚烫', 'vote_average' => 7.4, 'release_date' => '2024-02-10', 'poster_path' => '/nGxUxi3PfXDRm7Vg95VBNgNM8yP.jpg'],
        ['id' => 12, 'title' => '飞驰人生2', 'vote_average' => 6.9, 'release_date' => '2024-02-10', 'poster_path' => '/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg'],
        ['id' => 13, 'title' => '第二十条', 'vote_average' => 7.8, 'release_date' => '2024-02-10', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg'],
        ['id' => 14, 'title' => '哥斯拉-1.0', 'vote_average' => 7.5, 'release_date' => '2023-11-03', 'poster_path' => '/e3NBGiA3zWxJ5EfM6afp9c05pS.jpg'],
        ['id' => 15, 'title' => '奥本海默', 'vote_average' => 8.8, 'release_date' => '2023-07-21', 'poster_path' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
    ];
    $popular_tv = [
        ['id' => 20, 'name' => '庆余年 第二季', 'vote_average' => 8.9, 'first_air_date' => '2024-05-16', 'poster_path' => '/7RzDPm9kRzQ8P8vYdC1l8XhG8fO.jpg'],
        ['id' => 21, 'name' => '斗破苍穹 年番', 'vote_average' => 8.6, 'first_air_date' => '2022-07-31', 'poster_path' => '/6KbQ34MCxu4bn610S4jB3Lc2tRr.jpg'],
        ['id' => 22, 'name' => '难哄', 'vote_average' => 7.9, 'first_air_date' => '2025-02-17', 'poster_path' => '/nGxUxi3PfXDRm7Vg95VBNgNM8yP.jpg'],
        ['id' => 23, 'name' => '漫长的季节', 'vote_average' => 9.4, 'first_air_date' => '2023-04-22', 'poster_path' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
        ['id' => 24, 'name' => '与凤行', 'vote_average' => 8.2, 'first_air_date' => '2024-03-18', 'poster_path' => '/e3NBGiA3zWxJ5EfM6afp9c05pS.jpg'],
        ['id' => 25, 'name' => '玫瑰的故事', 'vote_average' => 7.8, 'first_air_date' => '2024-06-08', 'poster_path' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
    ];
}
?>

<style>
.hero-genres {
    display: inline-flex;
    gap: 8px;
    margin-left: 10px;
}
.hero-genre-tag {
    font-size: 13px;
    color: var(--text-secondary);
}
.hero-genre-tag:not(:last-child)::after {
    content: '/';
    margin-left: 8px;
    color: var(--text-muted);
}
</style>

<div class="container">
    <!-- Hero轮播 -->
    <section class="hero-section">
        <div class="hero-slider">
            <?php foreach ($trending_movies as $index => $item): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                <div class="hero-bg" style="background-image: url('<?php echo tmdb_image($item['backdrop_path'], 'original'); ?>')"></div>
                <div class="hero-content">
                    <span class="hero-tag">🔥 正在热映</span>
                    <h2 class="hero-title"><?php echo htmlspecialchars($item['title'] ?: $item['name']); ?></h2>
                    <div class="hero-meta">
                        <span class="hero-rating">★ <?php echo number_format($item['vote_average'], 1); ?></span>
                        <span><?php echo substr($item['release_date'] ?: $item['first_air_date'], 0, 4); ?></span>
                        <span class="hero-genres">
                            <span class="hero-genre-tag">动画</span>
                            <span class="hero-genre-tag">奇幻</span>
                            <span class="hero-genre-tag">冒险</span>
                        </span>
                    </div>
                    <p class="hero-desc"><?php echo htmlspecialchars(mb_substr($item['overview'], 0, 100)); ?>...</p>
                    <div class="hero-buttons">
                        <a href="detail.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['media_type'] ?: (isset($item['title']) ? 'movie' : 'tv'); ?>" class="btn btn-primary">
                            <span class="icon icon-play"></span>
                            立即播放
                        </a>
                        <button class="btn btn-outline" onclick="toggleFavorite(<?php echo $item['id']; ?>, '<?php echo $item['media_type'] ?: (isset($item['title']) ? 'movie' : 'tv'); ?>', '<?php echo htmlspecialchars(addslashes($item['title'] ?: $item['name'])); ?>', '<?php echo $item['poster_path']; ?>')">
                            <span class="icon icon-heart"></span>
                            收藏
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="hero-dots">
                <?php foreach ($trending_movies as $index => $item): ?>
                <div class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 热门推荐 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span>🔥</span>
                热门推荐
            </h2>
        </div>
        <div class="media-grid">
            <?php 
            $all_trending = array_slice(array_merge($popular_movies, $popular_tv), 0, 6);
            foreach ($all_trending as $item): 
                $type = isset($item['title']) ? 'movie' : 'tv';
                $title = $item['title'] ?: $item['name'];
                $year = substr($item['release_date'] ?: $item['first_air_date'], 0, 4);
            ?>
            <div class="media-card" onclick="window.location.href='detail.php?id=<?php echo $item['id']; ?>&type=<?php echo $type; ?>'">
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
                <div class="media-info"><?php echo $year; ?> · <?php echo $type == 'movie' ? '电影' : '电视剧'; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 电影 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="icon icon-movie"></span>
                电影
            </h2>
            <a href="category.php?type=movie" class="section-more">
                查看更多
                <span class="icon icon-chevron right"></span>
            </a>
        </div>
        <div class="category-tabs">
            <span class="category-tab active">全部</span>
            <span class="category-tab">动作</span>
            <span class="category-tab">喜剧</span>
            <span class="category-tab">爱情</span>
            <span class="category-tab">科幻</span>
            <span class="category-tab">悬疑</span>
            <span class="category-tab">剧情</span>
        </div>
        <div class="media-grid">
            <?php foreach ($popular_movies as $item): ?>
            <div class="media-card" onclick="window.location.href='detail.php?id=<?php echo $item['id']; ?>&type=movie'">
                <div class="media-poster">
                    <img src="<?php echo tmdb_image($item['poster_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                    <div class="media-rating">★ <?php echo number_format($item['vote_average'], 1); ?></div>
                    <div class="media-overlay">
                        <div class="btn-play">
                            <span class="icon icon-play" style="color:white;"></span>
                        </div>
                    </div>
                </div>
                <div class="media-title"><?php echo htmlspecialchars($item['title']); ?></div>
                <div class="media-info"><?php echo substr($item['release_date'], 0, 4); ?> · 电影</div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 电视剧 -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <span>📺</span>
                电视剧
            </h2>
            <a href="category.php?type=tv" class="section-more">
                查看更多
                <span class="icon icon-chevron right"></span>
            </a>
        </div>
        <div class="media-grid">
            <?php foreach ($popular_tv as $item): ?>
            <div class="media-card" onclick="window.location.href='detail.php?id=<?php echo $item['id']; ?>&type=tv'">
                <div class="media-poster">
                    <img src="<?php echo tmdb_image($item['poster_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                    <div class="media-rating">★ <?php echo number_format($item['vote_average'], 1); ?></div>
                    <div class="media-overlay">
                        <div class="btn-play">
                            <span class="icon icon-play" style="color:white;"></span>
                        </div>
                    </div>
                </div>
                <div class="media-title"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="media-info"><?php echo substr($item['first_air_date'], 0, 4); ?> · 电视剧</div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
// Hero轮播
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.hero-dot');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    slides[index].classList.add('active');
    dots[index].classList.add('active');
    currentSlide = index;
}

function nextSlide() {
    showSlide((currentSlide + 1) % totalSlides);
}

function goToSlide(index) {
    showSlide(index);
}

// 自动轮播
setInterval(nextSlide, 5000);

// 分类标签切换
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        this.parentElement.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
