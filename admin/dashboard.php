<?php
require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

// Include site header for consistent theme
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Admin Dashboard</h1>
<p class="muted">Welcome, <?php echo htmlentities($_SESSION['username']); ?>!</p>

<div class="admin-grid">
    <a class="btn" href="users.php">👤 Manage Users</a>
    <a class="btn" href="movies.php">🎬 Manage Movies</a>
    <a class="btn" href="reviews.php">💬 Manage Reviews</a>
    <a class="btn" href="reports.php">📝 Generate Reports</a>
</div>

<?php include_once(__DIR__ . "/../footer.php"); ?>