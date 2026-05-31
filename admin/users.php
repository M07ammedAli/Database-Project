<?php
require_once(__DIR__ . "/../DBconn.php");
require_once(__DIR__ . "/../auth_guard.php");
require_role('admin');

$conn = getConnection();
if (!$conn) { die("Database connection failed."); }

// Helper: how many admins remain
function adminCount($conn) {
    $r = $conn->query("SELECT COUNT(*) AS c FROM dbProj_users WHERE role='admin'");
    $row = $r->fetch_assoc();
    return (int)$row['c'];
}

// Change role
if (isset($_POST['change_role'])) {
    $targetId = (int)$_POST['user_id'];
    $newRole  = $_POST['role'];

    // Block demoting the last admin (including yourself).
    $r = $conn->prepare("SELECT role FROM dbProj_users WHERE user_id=?");
    $r->bind_param("i", $targetId);
    $r->execute();
    $cur = $r->get_result()->fetch_assoc();

    if ($cur && $cur['role'] === 'admin' && $newRole !== 'admin' && adminCount($conn) <= 1) {
        $_SESSION['msg'] = "Cannot demote the last remaining admin.";
    } else {
        $stmt = $conn->prepare("UPDATE dbProj_users SET role=? WHERE user_id=?");
        $stmt->bind_param("si", $newRole, $targetId);
        $stmt->execute();
        $_SESSION['msg'] = "Role updated.";
    }
}

// Delete user
if (isset($_POST['delete_user'])) {
    $targetId = (int)$_POST['user_id'];

    if ($targetId === (int)$_SESSION['uid']) {
        $_SESSION['msg'] = "You cannot delete your own account.";
    } else {
        // Block deleting the last admin.
        $r = $conn->prepare("SELECT role FROM dbProj_users WHERE user_id=?");
        $r->bind_param("i", $targetId);
        $r->execute();
        $cur = $r->get_result()->fetch_assoc();

        if ($cur && $cur['role'] === 'admin' && adminCount($conn) <= 1) {
            $_SESSION['msg'] = "Cannot delete the last remaining admin.";
        } else {
            $stmt = $conn->prepare("DELETE FROM dbProj_users WHERE user_id=?");
            $stmt->bind_param("i", $targetId);
            $stmt->execute();
            $_SESSION['msg'] = "User deleted.";
        }
    }
}

$result = $conn->query("SELECT user_id, username, email, role, created_at FROM dbProj_users");

// Include site header for consistent theme
include_once(__DIR__ . "/../header.php");
?>

<h1 class="page-title">Manage Users</h1>

<?php if(isset($_SESSION['msg'])) { echo "<p class='notice'>".htmlentities($_SESSION['msg'])."</p>"; unset($_SESSION['msg']); } ?>

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
                <option value="viewer"  <?= $row['role']==='viewer'  ? 'selected' : '' ?>>Viewer</option>
                <option value="creator" <?= $row['role']==='creator' ? 'selected' : '' ?>>Creator</option>
                <option value="admin"   <?= $row['role']==='admin'   ? 'selected' : '' ?>>Admin</option>
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