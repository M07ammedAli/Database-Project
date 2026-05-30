<?php
require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

$conn = getConnection();
if (!$conn) { die("Database connection failed."); }

// Change role
if (isset($_POST['change_role'])) {
    $stmt = $conn->prepare("UPDATE dbProj_users SET role=? WHERE user_id=?");
    $stmt->bind_param("si", $_POST['role'], $_POST['user_id']);
    $stmt->execute();
}

// Delete user
if (isset($_POST['delete_user'])) {
    $stmt = $conn->prepare("DELETE FROM dbProj_users WHERE user_id=?");
    $stmt->bind_param("i", $_POST['user_id']);
    $stmt->execute();
}

$result = $conn->query("SELECT user_id, username, email, role, created_at FROM dbProj_users");

// Include site header for consistent theme
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Manage Users</h1>

<div class="table-container">
<table class="styled-table">
<tr>
    <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['user_id'] ?></td>
    <td><?= htmlentities($row['username']) ?></td>
    <td><?= htmlentities($row['email']) ?></td>
    <td><?= $row['role'] ?></td>
    <td><?= $row['created_at'] ?></td>
    <td>
        <form method="post" class="inline-form">
            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
            <select name="role" class="dropdown">
                <option value="viewer">Viewer</option>
                <option value="creator">Creator</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit" name="change_role" class="btn">Change Role</button>
        </form>
        <form method="post" class="inline-form">
            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
            <button type="submit" name="delete_user" class="btn danger">Delete</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

<?php include_once(__DIR__ . "/../footer.php"); ?>