<?php

include_once(__DIR__ . "/../auth_guard.php");
require_role('creator');

include_once(__DIR__ . "/../Movie.php");

$error   = "";
$success = "";

$title       = "";
$description = "";
$categoryId  = 0;
$trailerUrl  = "";
$releaseDate = "";


$imagesDir   = __DIR__ . "/../images";
$maxBytes    = 2 * 1024 * 1024;                 // 2 MB
$allowedExt  = array('jpg', 'jpeg', 'png');
$allowedMime = array('image/jpeg', 'image/png');

if (isset($_POST['submitted'])) {
    $movie = new Movie();

    $title       = $movie->cleanXSS($_POST['title']);
    $description = $movie->cleanXSS($_POST['description']);
    $categoryId  = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $trailerUrl  = trim($_POST['trailer_url']);
    $releaseDate = trim($_POST['release_date']);

    $posterFilename = 'placeholder.jpg';   // default if no upload

    // ---- Text validation first ----
    if ($title === "" || $description === "" || $categoryId <= 0) {
        $error = "Title, description and category are required.";
    } elseif (mb_strlen($title) > 150) {
        $error = "Title must be 150 characters or fewer.";
    } elseif ($trailerUrl !== "" && !filter_var($trailerUrl, FILTER_VALIDATE_URL)) {
        $error = "Trailer URL is not a valid URL.";
    } elseif ($releaseDate === "") {
        $error = "Release date is required.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        $error = "Release date must be in YYYY-MM-DD format.";
    } else {
       
        $uploadOk = true;

        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
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
                $uploadOk = false;
            } elseif ($f['size'] > $maxBytes) {
                $error = "Poster is too large. Maximum size is 2 MB.";
                $uploadOk = false;
            } else {
               
                $info = @getimagesize($f['tmp_name']);
                $mime = $info ? $info['mime'] : '';
                $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

                if (!in_array($mime, $allowedMime) || !in_array($ext, $allowedExt)) {
                    $error = "Poster must be a JPG or PNG image.";
                    $uploadOk = false;
                } else {
                  
                    $posterFilename = 'poster_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $dest = $imagesDir . '/' . $posterFilename;

                    if (!move_uploaded_file($f['tmp_name'], $dest)) {
                        $error = "Could not save the uploaded poster. Check folder permissions.";
                        $uploadOk = false;
                        $posterFilename = 'placeholder.jpg';
                    }
                }
            }
        }


        if ($uploadOk && $error === "") {
            $movie->setTitle($title);
            $movie->setDescription($description);
            $movie->setCategoryId($categoryId);
            $movie->setCreatorId($_SESSION['uid']);
            $movie->setPosterImage($posterFilename);
            $movie->setTrailerUrl($trailerUrl !== "" ? $trailerUrl : null);
            $movie->setReleaseDate($releaseDate !== "" ? $releaseDate : null);
            $movie->setStatus('draft');

            if ($movie->save()) {
                $success = "Draft saved. You can edit it or publish it from your dashboard.";
                $title = $description = $trailerUrl = $releaseDate = "";
                $categoryId = 0;
            } else {
                $error = "Could not save the movie. Please try again.";
            }
        }
    }
}

$categories = Movie::listCategories();

include_once(__DIR__ . "/../header.php");
?>

<h1>Add New Movie</h1>
<p class="muted">New movies are saved as a draft. Publish them from your dashboard when ready.</p>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlentities($success); ?></div>
    <p><a class="btn" href="<?php echo BASE_URL; ?>/creator/dashboard.php">Back to dashboard</a></p>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data" onsubmit="return validateAddMovie();">
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
        <label for="poster">Poster image (JPG or PNG, max 2 MB)</label>
        <input type="file" id="poster" name="poster" accept="image/jpeg,image/png"
               onchange="previewPoster(event)">
    </p>
    <p>
        <img id="posterPreview" src="" alt=""
             style="display:none; width:160px; border-radius:8px; margin-top:6px;">
    </p>

    <p>
        <label for="trailer_url">Trailer URL (optional)</label>
        <input type="text" id="trailer_url" name="trailer_url"
               placeholder="https://www.youtube.com/watch?v=..."
               value="<?php echo htmlentities($trailerUrl); ?>">
    </p>

    <p>
        <label for="release_date">Release date *</label>
        <input type="date" id="release_date" name="release_date" required
               value="<?php echo htmlentities($releaseDate); ?>">
    </p>

    <p><input type="submit" value="Save as draft"></p>
</form>

<p><a href="<?php echo BASE_URL; ?>/creator/dashboard.php">&laquo; Back to dashboard</a></p>

<script>
function validateAddMovie() {
    var title = document.getElementById("title").value.trim();
    var desc  = document.getElementById("description").value.trim();
    var cat   = document.getElementById("category_id").value;
    var date  = document.getElementById("release_date").value;

    if (title === "" || desc === "" || cat === "0") {
        alert("Please fill in title, description and choose a category.");
        return false;
    }
    if (date === "") {
        alert("Please choose a release date.");
        return false;
    }
    return true;
}


function previewPoster(e) {
    var file = e.target.files[0];
    var img  = document.getElementById("posterPreview");
    if (file) {
        img.src = URL.createObjectURL(file);
        img.style.display = "block";
    } else {
        img.style.display = "none";
    }
}
</script>

<?php include_once(__DIR__ . "/../footer.php"); ?>