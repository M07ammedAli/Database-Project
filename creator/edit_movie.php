<?php

// Edit a DRAFT movie. Owner (or admin) only. Drafts only.
include_once(__DIR__ . "/../auth_guard.php");
require_role('creator');

include_once(__DIR__ . "/../Movie.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$movie = new Movie();
if (!$id || !$movie->initWithId($id)) {
    http_response_code(404);
    include_once(__DIR__ . "/../header.php");
    echo "<h1>Movie not found</h1>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

// Ownership check: must own it, unless admin.
$isOwner = ((int)$movie->getCreatorId() === (int)$_SESSION['uid']);
$isAdmin = (current_role() === 'admin');
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Access denied</h2><p>This is not your movie, so you cannot edit it.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

// Design rule: only drafts are editable. Published movies are locked.
if ($movie->getStatus() !== 'draft') {
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Locked</h2><p>Published movies cannot be edited here.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

$error   = "";
$success = "";

// Pre-fill from the loaded movie.
$title       = $movie->getTitle();
$description = $movie->getDescription();
$categoryId  = (int)$movie->getCategoryId();
$trailerUrl  = $movie->getTrailerUrl();
$releaseDate = $movie->getReleaseDate();

if (isset($_POST['submitted'])) {
    $title       = $movie->cleanXSS($_POST['title']);
    $description = $movie->cleanXSS($_POST['description']);
    $categoryId  = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $trailerUrl  = trim($_POST['trailer_url']);
    $releaseDate = trim($_POST['release_date']);

    if ($title === "" || $description === "" || $categoryId <= 0) {
        $error = "Title, description and category are required.";
    } elseif (mb_strlen($title) > 150) {
        $error = "Title must be 150 characters or fewer.";
    } elseif ($trailerUrl !== "" && !filter_var($trailerUrl, FILTER_VALIDATE_URL)) {
        $error = "Trailer URL is not a valid URL.";
    } elseif ($releaseDate !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        $error = "Release date must be in YYYY-MM-DD format.";
    } else {
        $movie->setTitle($title);
        $movie->setDescription($description);
        $movie->setCategoryId($categoryId);
        // poster_image left unchanged; trailer/date updated.
        $movie->setTrailerUrl($trailerUrl !== "" ? $trailerUrl : null);
        $movie->setReleaseDate($releaseDate !== "" ? $releaseDate : null);
        $movie->setStatus('draft');   // stays a draft after editing

        if ($movie->update()) {
            $success = "Changes saved.";
        } else {
            $error = "Could not save changes. Please try again.";
        }
    }
}

$categories = Movie::listCategories();

include_once(__DIR__ . "/../header.php");
?>

<h1>Edit Movie (Draft)</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlentities($success); ?></div>
<?php endif; ?>

<form method="post" action="" onsubmit="return validateEditMovie();">
    <input type="hidden" name="submitted" value="1">

    <p>
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" maxlength="150"
               value="<?php echo htmlentities($title); ?>">
    </p>

    <p>
        <label for="description">Description *</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlentities($description); ?></textarea>
    </p>

    <p>
        <label for="category_id">Category *</label>
        <select id="category_id" name="category_id">
            <option value="0">— Select a category —</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo (int)$c['category_id']; ?>"
                    <?php echo ($categoryId === (int)$c['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlentities($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label for="trailer_url">Trailer URL (optional)</label>
        <input type="text" id="trailer_url" name="trailer_url"
               value="<?php echo htmlentities($trailerUrl); ?>">
    </p>

    <p>
        <label for="release_date">Release date (optional)</label>
        <input type="date" id="release_date" name="release_date"
               value="<?php echo htmlentities($releaseDate); ?>">
    </p>

    <p><input type="submit" value="Save changes"></p>
</form>

<p><a href="<?php echo BASE_URL; ?>/creator/dashboard.php">&laquo; Back to dashboard</a></p>

<script>
function validateEditMovie() {
    var title = document.getElementById("title").value.trim();
    var desc  = document.getElementById("description").value.trim();
    var cat   = document.getElementById("category_id").value;
    if (title === "" || desc === "" || cat === "0") {
        alert("Please fill in title, description and choose a category.");
        return false;
    }
    return true;
}
</script>

<?php include_once(__DIR__ . "/../footer.php"); ?>