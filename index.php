<?php
include_once(__DIR__ . "/Movie.php");
define('BASE_URL', '/~u202304108/MovieReview');

// ---- Pagination (Non-functional: paginate when > 10 records) ----
$perPage = 10;
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset  = ($page - 1) * $perPage;

$total      = Movie::countPublished();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$movies = Movie::listPublished($perPage, $offset);

include_once(__DIR__ . "/header.php");
?>

<h1>Latest Movies</h1>
<p class="muted">Browse the newest reviews. Log in to rate and comment.</p>

<?php if (empty($movies)): ?>
    <p>No movies have been published yet.</p>
<?php else: ?>
    <div class="movie-grid">
        <?php foreach ($movies as $m): ?>
            <?php
                $poster = $m['poster_image'] ? $m['poster_image'] : 'placeholder.jpg';
                $shortDesc = mb_strlen($m['description']) > 90
                    ? mb_substr($m['description'], 0, 90) . '…'
                    : $m['description'];
            ?>
            <div class="movie-card">
                <a href="<?php echo BASE_URL; ?>/movie_view.php?id=<?php echo (int)$m['movie_id']; ?>">
                    <img src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($poster); ?>"
                         alt="<?php echo htmlentities($m['title']); ?> poster">
                </a>
                <div class="body">
                    <p class="title"><?php echo htmlentities($m['title']); ?></p>
                    <p class="meta">
                        <span class="badge"><?php echo htmlentities($m['category']); ?></span>
                        &nbsp;⭐ <?php echo $m['avg_rating']; ?>
                        &nbsp;·&nbsp; <?php echo (int)$m['view_count']; ?> views
                    </p>
                    <p class="desc"><?php echo htmlentities($shortDesc); ?></p>
                    <a class="btn" href="<?php echo BASE_URL; ?>/movie_view.php?id=<?php echo (int)$m['movie_id']; ?>">View More</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == $page): ?>
                    <span class="active"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include_once(__DIR__ . "/footer.php"); ?>