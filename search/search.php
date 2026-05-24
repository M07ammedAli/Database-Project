<?php
// search/search.php — lives in the /search sub-folder, so step up one level.
include_once(__DIR__ . "/../Movie.php");

// Read filters from the query string (GET so searches are shareable/bookmarkable).
$terms     = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$creatorId = isset($_GET['creator'])  ? (int)$_GET['creator']   : 0;
$dateFrom  = isset($_GET['from'])     ? trim($_GET['from'])     : '';
$dateTo    = isset($_GET['to'])       ? trim($_GET['to'])       : '';
$sort      = isset($_GET['sort'])     ? trim($_GET['sort'])     : 'relevance';

// Only run a search once the user has actually submitted something.
$searched = isset($_GET['submitted']);
$results  = array();
if ($searched) {
    $results = Movie::fullTextSearch($terms, $creatorId, $dateFrom, $dateTo, $sort);
}

$creators = Movie::listCreators();

include_once(__DIR__ . "/../header.php");
?>

<h1>Search Movies</h1>

<form method="get" action="">
    <input type="hidden" name="submitted" value="1">

    <p>
        <label for="q">Title / keywords (full-text search)</label>
        <input type="text" id="q" name="q" value="<?php echo htmlentities($terms); ?>"
               placeholder="e.g. space, detective, mission">
    </p>

    <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <p style="flex:1; min-width:160px;">
            <label for="creator">Creator</label>
            <select id="creator" name="creator">
                <option value="0">Any creator</option>
                <?php foreach ($creators as $c): ?>
                    <option value="<?php echo (int)$c['user_id']; ?>"
                        <?php echo ($creatorId === (int)$c['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlentities($c['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p style="flex:1; min-width:160px;">
            <label for="from">Date from</label>
            <input type="date" id="from" name="from" value="<?php echo htmlentities($dateFrom); ?>">
        </p>

        <p style="flex:1; min-width:160px;">
            <label for="to">Date to</label>
            <input type="date" id="to" name="to" value="<?php echo htmlentities($dateTo); ?>">
        </p>

        <p style="flex:1; min-width:160px;">
            <label for="sort">Sort by</label>
            <select id="sort" name="sort">
                <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                <option value="newest"    <?php echo $sort === 'newest'    ? 'selected' : ''; ?>>Newest</option>
                <option value="popular"   <?php echo $sort === 'popular'   ? 'selected' : ''; ?>>Most popular</option>
            </select>
        </p>
    </div>

    <p><input type="submit" value="Search"></p>
</form>

<?php if ($searched): ?>
    <h2><?php echo count($results); ?> result<?php echo count($results) === 1 ? '' : 's'; ?> found</h2>

    <?php if (empty($results)): ?>
        <p>No movies matched your search. Try fewer or broader keywords.</p>
    <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($results as $m): ?>
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
    <?php endif; ?>
<?php endif; ?>

<?php include_once(__DIR__ . "/../footer.php"); ?>