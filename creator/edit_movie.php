<?php


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


if ($movie->getStatus() !== 'draft') {
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Locked</h2><p>Published movies cannot be edited here.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

$error   = "";
$success = "";


$imagesDir   = __DIR__ . "/../images";
$maxBytes    = 2 * 1024 * 1024;                 // 2 MB
$allowedExt  = array('jpg', 'jpeg', 'png');
$allowedMime = array('image/jpeg', 'image/png');


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


    $oldPoster    = $movie->getPosterImage();
    $newPoster    = $oldPoster ? $oldPoster : 'placeholder.jpg';
    $posterAction = isset($_POST['poster_action']) ? $_POST['poster_action'] : 'keep';


    if ($title === "" || $description === "" || $categoryId <= 0) {
        $error = "Title, description and category are required.";
    } elseif (mb_strlen($title) > 150) {
        $error = "Title must be 150 characters or fewer.";
    } elseif ($trailerUrl !== "" && !filter_var($trailerUrl, FILTER_VALIDATE_URL)) {
        $error = "Trailer URL is not a valid URL.";
    } elseif ($releaseDate !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        $error = "Release date must be in YYYY-MM-DD format.";
    } else {
        
        if ($posterAction === 'delete') {
            if ($oldPoster && $oldPoster !== 'placeholder.jpg') {
                $oldPath = $imagesDir . '/' . basename($oldPoster);
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
            $newPoster = 'placeholder.jpg';
        }

   
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
                      
                        if ($oldPoster && $oldPoster !== 'placeholder.jpg') {
                            $oldPath = $imagesDir . '/' . basename($oldPoster);
                            if (is_file($oldPath)) { @unlink($oldPath); }
                        }
                        $newPoster = $uploadName;
                    }
                }
            }
        }

        
        if ($error === "") {
            $movie->setTitle($title);
            $movie->setDescription($description);
            $movie->setCategoryId($categoryId);
            $movie->setPosterImage($newPoster);
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
}

$categories = Movie::listCategories();


$posterFile = $movie->getPosterImage() ? $movie->getPosterImage() : 'placeholder.jpg';
$hasCustomPoster = ($posterFile !== 'placeholder.jpg');

include_once(__DIR__ . "/../header.php");
?>

<h1>Edit Movie (Draft)</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlentities($success); ?></div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" onsubmit="return validateEditMovie();">
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
        <label>Current poster</label>
        <img id="currentPoster"
             src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($posterFile); ?>"
             alt="Current poster"
             style="width:150px; border-radius:8px; display:block; margin-top:6px;">
        <?php if (!$hasCustomPoster): ?>
            <span class="muted">No custom poster set (showing placeholder).</span>
        <?php endif; ?>
    </p>

    <p>
        <label for="poster">Replace poster (JPG or PNG, max 2 MB)</label>
        <input type="file" id="poster" name="poster" accept="image/jpeg,image/png"
               onchange="previewPoster(event)">
    </p>
    <p>
        <img id="posterPreview" src="" alt=""
             style="display:none; width:150px; border-radius:8px; margin-top:6px;">
    </p>

    <?php if ($hasCustomPoster): ?>
        <p>
            <label style="display:inline-flex; align-items:center; gap:8px; font-weight:normal;">
                <input type="checkbox" name="poster_action" value="delete" id="deletePoster"
                       style="width:auto;"
                       onchange="if(this.checked){document.getElementById('poster').value='';document.getElementById('posterPreview').style.display='none';}">
                Delete current poster (revert to placeholder)
            </label>
        </p>
    <?php endif; ?>

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


function previewPoster(e) {
    var file = e.target.files[0];
    var img  = document.getElementById("posterPreview");
    var del  = document.getElementById("deletePoster");
    if (del) { del.checked = false; }   // choosing a file cancels a pending delete
    if (file) {
        img.src = URL.createObjectURL(file);
        img.style.display = "block";
    } else {
        img.style.display = "none";
    }
}
</script>

<?php include_once(__DIR__ . "/../footer.php"); ?>