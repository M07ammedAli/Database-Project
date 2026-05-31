<?php
require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

$conn = getConnection();
if (!$conn) { die("Database connection failed."); }

// Delete movie
if (isset($_POST['delete_movie'])) {
    $stmt = $conn->prepare("DELETE FROM dbProj_movies WHERE movie_id=?");
    $stmt->bind_param("i", $_POST['movie_id']);
    $stmt->execute();
    $_SESSION['msg'] = "Movie removed successfully.";
}

// Query movies with category names
$result = $conn->query("SELECT m.movie_id, m.title, c.name AS category, 
                               m.release_date, m.status, m.view_count
                        FROM dbProj_movies m
                        JOIN dbProj_categories c ON m.category_id = c.category_id");

// Include site header
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Manage Movies</h1>
<?php if(isset($_SESSION['msg'])) { echo "<p class='notice'>".$_SESSION['msg']."</p>"; unset($_SESSION['msg']); } ?>

<div class="table-container">
<table class="styled-table">
<tr>
    <th>ID</th><th>Title</th><th>Category</th><th>Release Date</th>
    <th>Status</th><th>Views</th><th>Actions</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['movie_id'] ?></td>
    <td><?= htmlentities($row['title']) ?></td>
    <td><?= htmlentities($row['category']) ?></td>
    <td><?= $row['release_date'] ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= $row['view_count'] ?></td>
    <td>
        <!-- Delete -->
        <form method="post" class="inline-form">
            <input type="hidden" name="movie_id" value="<?= $row['movie_id'] ?>">
            <button type="submit" name="delete_movie" class="btn danger">Delete</button>
        </form>
        <!-- Edit -->
        <a href="edit_movie.php?id=<?= $row['movie_id'] ?>" class="btn">Edit</a>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

<?php include_once(__DIR__ . "/../footer.php"); ?>
