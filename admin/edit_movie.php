<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);   // keep off for submission; flip to 1 only while debugging

require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

$conn = getConnection();
if (!$conn) { die("Database connection failed."); }

// Handle update
if (isset($_POST['update_movie'])) {
    $stmt = $conn->prepare("UPDATE dbProj_movies 
                            SET title=?, category_id=?, release_date=?, status=? 
                            WHERE movie_id=?");
    if (!$stmt) {
        die("Could not process your request. Please try again or contact the administrator.");
    }

    $stmt->bind_param("sissi",
        $_POST['title'],
        $_POST['category_id'],
        $_POST['release_date'],
        $_POST['status'],
        $_POST['movie_id']
    );

    if (!$stmt->execute()) {
        die("Could not update the movie. Please try again or contact the administrator.");
    }

    $_SESSION['msg'] = "Movie updated successfully.";
    header("Location: " . BASE_URL . "/admin/movies.php");
    exit;
}

// Fetch movie by ID
if (!isset($_GET['id'])) { die("Movie ID missing."); }
$movie_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT movie_id, title, category_id, release_date, status 
                        FROM dbProj_movies WHERE movie_id=?");
if (!$stmt) {
    die("Could not process your request. Please try again or contact the administrator.");
}
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();
if (!$movie) { die("Movie not found."); }

// Fetch categories for dropdown
$categories = $conn->query("SELECT category_id, name FROM dbProj_categories");

// Include header
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Edit Movie</h1>
<form method="post" class="styled-form">
    <input type="hidden" name="movie_id" value="<?= (int)$movie['movie_id'] ?>">

    <label>Title</label>
    <input type="text" name="title" value="<?= htmlentities($movie['title']) ?>" required>

    <label>Category</label>
    <select name="category_id" required>
        <?php while($cat = $categories->fetch_assoc()): ?>
            <option value="<?= (int)$cat['category_id'] ?>" 
                <?= $cat['category_id'] == $movie['category_id'] ? 'selected' : '' ?>>
                <?= htmlentities($cat['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Release Date</label>
    <input type="date" name="release_date" value="<?= htmlentities($movie['release_date']) ?>" required>

    <label>Status</label>
    <select name="status" required>
        <option value="published" <?= $movie['status'] == 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $movie['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
    </select>

    <button type="submit" name="update_movie" class="btn">Save Changes</button>
    <a href="movies.php" class="btn secondary">Cancel</a>
</form>

<?php include_once(__DIR__ . "/../footer.php"); ?>