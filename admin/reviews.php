<?php
require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

$conn = getConnection();
if (!$conn) { die("Database connection failed."); }

// Delete comment
if (isset($_POST['delete_comment'])) {
    $stmt = $conn->prepare("DELETE FROM dbProj_comments WHERE comment_id=?");
    $stmt->bind_param("i", $_POST['comment_id']);
    $stmt->execute();
    $_SESSION['msg'] = "Comment removed.";
}

// Query comments with ratings
$result = $conn->query("SELECT c.comment_id, u.username, m.title, r.stars, c.body, c.created_at
                        FROM dbProj_comments c
                        JOIN dbProj_users u ON c.user_id = u.user_id
                        JOIN dbProj_movies m ON c.movie_id = m.movie_id
                        LEFT JOIN dbProj_ratings r ON (c.movie_id = r.movie_id AND c.user_id = r.user_id)");

// Include site header for consistent theme
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Manage Reviews</h1>
<?php if(isset($_SESSION['msg'])) { echo "<p class='notice'>".$_SESSION['msg']."</p>"; unset($_SESSION['msg']); } ?>

<div class="table-container">
<table class="styled-table">
<tr><th>ID</th><th>User</th><th>Movie</th><th>Stars</th><th>Comment</th><th>Date</th><th>Actions</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['comment_id'] ?></td>
    <td><?= htmlentities($row['username']) ?></td>
    <td><?= htmlentities($row['title']) ?></td>
    <td><?= $row['stars'] ?></td>
    <td><?= htmlentities($row['body']) ?></td>
    <td><?= $row['created_at'] ?></td>
    <td>
        <form method="post" class="inline-form">
            <input type="hidden" name="comment_id" value="<?= $row['comment_id'] ?>">
            <button type="submit" name="delete_comment" class="btn danger">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

<?php include_once(__DIR__ . "/../footer.php"); ?>