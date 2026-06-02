<?php
// admin/edit_movie.php
// Admin edit for any movie. Adds poster display + replace + delete,
// reusing the same upload validation rules as the creator pages.
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

// Poster storage rules — identical to creator/add_movie.php
$imagesDir   = __DIR__ . "/../images";
$maxBytes    = 2 * 1024 * 1024;                 // 2 MB
$allowedExt  = array('jpg', 'jpeg', 'png');
$allowedMime = array('image/jpeg', 'image/png');

$error = "";

// ---------------------------------------------------------------------
// Resolve the movie id (needed for both GET display and POST handling)
// ---------------------------------------------------------------------
$movie_id = 0;
if (isset($_POST['movie_id'])) {
    $movie_id = (int)$_POST['movie_id'];
} elseif (isset($_GET['id'])) {
    $movie_id = (int)$_GET['id'];
}
if ($movie_id <= 0) { die("Movie ID missing."); }

// Helper: load current poster filename so we know what to replace/remove.
function current_poster($conn, $movie_id) {
    $s = $conn->prepare("SELECT poster_image FROM dbProj_movies WHERE movie_id=?");
    $s->bind_param("i", $movie_id);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    return $r ? $r['poster_image'] : null;
}

// ---------------------------------------------------------------------
// Handle update
// ---------------------------------------------------------------------
if (isset($_POST['update_movie'])) {

    $oldPoster   = current_poster($conn, $movie_id);
    $newPoster   = $oldPoster ? $oldPoster : 'placeholder.jpg';
    $posterAction = isset($_POST['poster_action']) ? $_POST['poster_action'] : 'keep';

    // ---- Poster: DELETE (revert to placeholder) ----
    if ($posterAction === 'delete') {
        // Physically remove the old custom file (never the shared placeholder).
        if ($oldPoster && $oldPoster !== 'placeholder.jpg') {
            $oldPath = $imagesDir . '/' . basename($oldPoster);
            if (is_file($oldPath)) { @unlink($oldPath); }
        }
        $newPoster = 'placeholder.jpg';
    }

    // ---- Poster: REPLACE (only if a file was actually chosen) ----
    if ($error === "" && isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['poster'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            switch ($f['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "The poster is too large to upload. Please choose an image under 2 MB.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "The poster was only partially uploaded. Please try again.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                    $error = "The server could not save the file. Please contact the administrator.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = "The upload was blocked by the server. Please try a different image.";
                    break;
                default:
                    $error = "The poster could not be uploaded. Please try again.";
                    break;
            }
        } elseif ($f['size'] > $maxBytes) {
            $error = "Poster is too large. Maximum size is 2 MB.";
        } else {
            // Verify it's really an image (not a renamed file).
            $info = @getimagesize($f['tmp_name']);
            $mime = $info ? $info['mime'] : '';
            $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

            if (!in_array($mime, $allowedMime) || !in_array($ext, $allowedExt)) {
                $error = "Poster must be a JPG or PNG image.";
            } else {
                $uploadName = 'poster_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $dest = $imagesDir . '/' . $uploadName;
                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    $error = "Could not save the uploaded poster. Check folder permissions.";
                } else {
                    // Success: remove the previous custom poster, then point to the new one.
                    if ($oldPoster && $oldPoster !== 'placeholder.jpg') {
                        $oldPath = $imagesDir . '/' . basename($oldPoster);
                        if (is_file($oldPath)) { @unlink($oldPath); }
                    }
                    $newPoster = $uploadName;
                }
            }
        }
    }

    // ---- Save (only if no upload error) ----
    if ($error === "") {
        $stmt = $conn->prepare("UPDATE dbProj_movies
                                SET title=?, category_id=?, release_date=?, status=?, poster_image=?
                                WHERE movie_id=?");
        if (!$stmt) {
            die("Could not process your request. Please try again or contact the administrator.");
        }
        $stmt->bind_param("sisssi",
            $_POST['title'],
            $_POST['category_id'],
            $_POST['release_date'],
            $_POST['status'],
            $newPoster,
            $movie_id
        );
        if (!$stmt->execute()) {
            die("Could not update the movie. Please try again or contact the administrator.");
        }
        $_SESSION['msg'] = "Movie updated successfully.";
        header("Location: " . BASE_URL . "/admin/movies.php");
        exit;
    }
    // If there was an error, fall through and redisplay the form below.
}

// ---------------------------------------------------------------------
// Fetch movie for display
// ---------------------------------------------------------------------
$stmt = $conn->prepare("SELECT movie_id, title, category_id, release_date, status, poster_image
                        FROM dbProj_movies WHERE movie_id=?");
if (!$stmt) {
    die("Could not process your request. Please try again or contact the administrator.");
}
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();
if (!$movie) { die("Movie not found."); }

// Categories for dropdown
$categories = $conn->query("SELECT category_id, name FROM dbProj_categories");

$posterFile = $movie['poster_image'] ? $movie['poster_image'] : 'placeholder.jpg';
$hasCustomPoster = ($posterFile !== 'placeholder.jpg');

include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Edit Movie</h1>

<?php if ($error !== ""): ?>
    <div class="alert alert-danger"><?= htmlentities($error) ?></div>
<?php endif; ?>

<form method="post" class="styled-form" enctype="multipart/form-data">
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

    <label>Current Poster</label>
    <div style="margin-bottom:10px;">
        <img id="currentPoster"
             src="<?= BASE_URL ?>/images/<?= htmlentities($posterFile) ?>"
             alt="Current poster"
             style="width:150px; border-radius:8px; border:1px solid var(--border, #2a3140);">
        <?php if (!$hasCustomPoster): ?>
            <p class="muted" style="margin-top:6px;">No custom poster set (showing placeholder).</p>
        <?php endif; ?>
    </div>

    <label for="poster">Replace Poster (JPG or PNG, max 2 MB)</label>
    <input type="file" id="poster" name="poster" accept="image/jpeg,image/png" onchange="previewPoster(event)">
    <p style="margin-top:6px;">
        <img id="posterPreview" src="" alt=""
             style="display:none; width:150px; border-radius:8px; margin-top:6px;">
    </p>

    <?php if ($hasCustomPoster): ?>
        <label style="display:flex; align-items:center; gap:8px; font-weight:normal; margin-top:10px;">
            <input type="checkbox" name="poster_action" value="delete" id="deletePoster"
                   style="width:auto;"
                   onchange="if(this.checked){document.getElementById('poster').value='';document.getElementById('posterPreview').style.display='none';}">
            Delete current poster (revert to placeholder)
        </label>
    <?php endif; ?>

    <p style="margin-top:18px;">
        <button type="submit" name="update_movie" class="btn">Save Changes</button>
        <a href="movies.php" class="btn secondary">Cancel</a>
    </p>
</form>

<script>
function previewPoster(e) {
    var file = e.target.files[0];
    var img  = document.getElementById("posterPreview");
    // Choosing a new file cancels a pending delete.
    var del = document.getElementById("deletePoster");
    if (del) { del.checked = false; }
    if (file) {
        img.src = URL.createObjectURL(file);
        img.style.display = "block";
    } else {
        img.style.display = "none";
    }
}
</script>

<?php include_once(__DIR__ . "/../footer.php"); ?>