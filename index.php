<?php
include_once(__DIR__ . "/Movie.php");
define('BASE_URL', '/~u202304108/MovieReview');

// ---- Category chip filter (0 = All) ----
$activeCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$categories = Movie::listCategories();

// Build a quick lookup of category_id => name for matching the hero/labels.
$catName = array();
foreach ($categories as $c) { $catName[(int)$c['category_id']] = $c['name']; }

// ---- Pagination (Non-functional: paginate when > 10 records) ----
$perPage = 10;
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }

// Pull a generous batch, then filter by category in PHP (keeps Movie.php untouched).
// listPublished returns newest-first with category name + avg rating already.
$allPublished = Movie::listPublished(500, 0);

// Apply category filter if one is chosen.
if ($activeCat > 0) {
    $filtered = array();
    foreach ($allPublished as $m) {
        if (isset($catName[$activeCat]) && $m['category'] === $catName[$activeCat]) {
            $filtered[] = $m;
        }
    }
} else {
    $filtered = $allPublished;
}

// ---- Hero: pick the most-viewed published movie (across all, not filtered) ----
$hero = null;
foreach ($allPublished as $m) {
    if ($hero === null || (int)$m['view_count'] > (int)$hero['view_count']) {
        $hero = $m;
    }
}

// ---- Pagination math on the filtered set ----
$total      = count($filtered);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$movies = array_slice($filtered, $offset, $perPage);

// Helper: build a page/cat URL keeping the active category.
function pageUrl($p, $cat) {
    $q = 'page=' . (int)$p;
    if ($cat > 0) { $q .= '&cat=' . (int)$cat; }
    return BASE_URL . '/index.php?' . $q;
}

include_once(__DIR__ . "/header.php");
?>

<?php if ($hero): ?>
<?php
    $heroPoster = $hero['poster_image'] ? $hero['poster_image'] : 'placeholder.jpg';
    $heroYear   = $hero['release_date'] ? substr($hero['release_date'], 0, 4) : '';
?>
<section class="hero">
    <img class="hero-bg" src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($heroPoster); ?>"
         alt="<?php echo htmlentities($hero['title']); ?>">
    <div class="hero-pills">
        <?php if ($hero['category']): ?><span class="hero-pill"><?php echo htmlentities($hero['category']); ?></span><?php endif; ?>
        <span class="hero-pill">Movie</span>
        <?php if ($heroYear): ?><span class="hero-pill"><?php echo htmlentities($heroYear); ?></span><?php endif; ?>
        <span class="hero-pill"><svg class="star-ico" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> <?php echo $hero['avg_rating']; ?></span>
        <span class="hero-pill"><?php echo (int)$hero['view_count']; ?> views</span>
    </div>
    <a class="hero-play" href="<?php echo BASE_URL; ?>/movie_view.php?id=<?php echo (int)$hero['movie_id']; ?>">
        <span class="play-btn" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="6 3 20 12 6 21 6 3"/></svg>
        </span>
        <span class="meta-txt">
            <b><?php echo htmlentities($hero['title']); ?></b>
            <span>Most popular now &nbsp;·&nbsp; View details</span>
        </span>
    </a>
</section>
<?php endif; ?>

<!-- ===== Category chips (real categories, real filtering) ===== -->
<div class="chips">
    <div class="chips-scroll">
        <a class="chip<?php echo $activeCat === 0 ? ' active' : ''; ?>"
           href="<?php echo BASE_URL; ?>/index.php">All</a>
        <?php foreach ($categories as $c): ?>
            <a class="chip<?php echo $activeCat === (int)$c['category_id'] ? ' active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/index.php?cat=<?php echo (int)$c['category_id']; ?>">
                <?php echo htmlentities($c['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="section-head">
    <h2><?php echo $activeCat > 0 && isset($catName[$activeCat])
            ? htmlentities($catName[$activeCat]) . ' Movies'
            : 'Latest Movies'; ?></h2>
    <?php if ($activeCat > 0): ?>
        <a href="<?php echo BASE_URL; ?>/index.php">Clear filter</a>
    <?php endif; ?>
</div>

<?php if (empty($movies)): ?>
    <p class="muted">No movies found<?php echo $activeCat > 0 ? ' in this category' : ''; ?>.</p>
<?php else: ?>
    <div class="movie-grid">
        <?php foreach ($movies as $m): ?>
            <?php
                $poster = $m['poster_image'] ? $m['poster_image'] : 'placeholder.jpg';
                $year   = $m['release_date'] ? substr($m['release_date'], 0, 4) : '—';
            ?>
            <div class="movie-card">
                <a href="<?php echo BASE_URL; ?>/movie_view.php?id=<?php echo (int)$m['movie_id']; ?>">
                    <div class="img-wrap">
                        <img src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($poster); ?>"
                             alt="<?php echo htmlentities($m['title']); ?> poster">
                    </div>
                </a>
                <div class="body">
                    <p class="title"><?php echo htmlentities($m['title']); ?></p>
                    <p class="ref-meta">
                        <?php echo htmlentities($year); ?>
                        <span class="dot">·</span>
                        <span class="score">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo $m['avg_rating']; ?>
                        </span>
                        <span class="dot">·</span>
                        <span class="badge"><?php echo htmlentities($m['category']); ?></span>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo pageUrl($page - 1, $activeCat); ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == $page): ?>
                    <span class="active"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo pageUrl($p, $activeCat); ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo pageUrl($page + 1, $activeCat); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include_once(__DIR__ . "/footer.php"); ?>