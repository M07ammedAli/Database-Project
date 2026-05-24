<?php
include_once(__DIR__ . "/Movie.php");
define('BASE_URL', '/~u202304108/MovieReview');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$movie = new Movie();
if (!$id || !$movie->initWithId($id) || $movie->getStatus() !== 'published') {
    include_once(__DIR__ . "/header.php");
    echo "<h1>Movie not found</h1><p>This movie does not exist or is not published.</p>";
    echo "<p><a href='" . BASE_URL . "/index.php'>Back to home</a></p>";
    include_once(__DIR__ . "/footer.php");
    exit();
}

// Count this visit (feeds popularity search + admin reports).
$movie->incrementViews();

$avg    = $movie->getAverageRating();
$count  = $movie->getRatingCount();
$poster = $movie->getPosterImage() ? $movie->getPosterImage() : 'placeholder.jpg';

// Fetch comments (newest first) with usernames.
$dbc  = getConnection();
$cid  = $movie->getMovieId();
$cstmt = mysqli_prepare($dbc,
    "SELECT c.body, c.created_at, u.username
     FROM dbProj_comments c
     LEFT JOIN dbProj_users u ON u.user_id = c.user_id
     WHERE c.movie_id = ?
     ORDER BY c.created_at DESC");
mysqli_stmt_bind_param($cstmt, "i", $cid);
mysqli_stmt_execute($cstmt);
$cres = mysqli_stmt_get_result($cstmt);
$comments = array();
while ($row = mysqli_fetch_assoc($cres)) { $comments[] = $row; }
mysqli_stmt_close($cstmt);

include_once(__DIR__ . "/header.php");
?>

<a href="<?php echo BASE_URL; ?>/index.php">&laquo; Back to movies</a>

<h1><?php echo htmlentities($movie->getTitle()); ?></h1>
<p class="meta">⭐ <?php echo $avg; ?> (<?php echo $count; ?> ratings) · <?php echo (int)$movie->getViewCount(); ?> views</p>

<div style="display:flex; gap:24px; flex-wrap:wrap; margin-top:16px;">
    <img src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($poster); ?>"
         alt="poster" style="width:260px; border-radius:10px;">
    <div style="flex:1; min-width:260px;">
        <p><?php echo nl2br(htmlentities($movie->getDescription())); ?></p>
        <?php if ($movie->getReleaseDate()): ?>
            <p class="meta">Release date: <?php echo htmlentities($movie->getReleaseDate()); ?></p>
        <?php endif; ?>
        <?php if ($movie->getTrailerUrl()): ?>
            <p><a class="btn" href="<?php echo htmlentities($movie->getTrailerUrl()); ?>" target="_blank" rel="noopener">▶ Watch Trailer</a></p>
        <?php endif; ?>
    </div>
</div>

<h2>Comments</h2>
<?php if (isset($_SESSION['uid'])): ?>
    <p class="muted">Logged in as <?php echo htmlentities($_SESSION['username']); ?> — commenting & rating will be wired up next (AJAX).</p>
<?php else: ?>
    <p class="muted"><a href="<?php echo BASE_URL; ?>/auth/login.php">Log in</a> to add a comment or rating.</p>
<?php endif; ?>

<?php if (empty($comments)): ?>
    <p>No comments yet.</p>
<?php else: ?>
    <?php foreach ($comments as $c): ?>
        <div class="comment">
            <p class="who"><?php echo htmlentities($c['username']); ?>
               <span class="when"><?php echo htmlentities($c['created_at']); ?></span></p>
            <p><?php echo htmlentities($c['body']); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include_once(__DIR__ . "/footer.php"); ?>