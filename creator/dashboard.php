<?php

// Lists the logged-in creator's own movies with management actions.
// Guarded: creators and admins only.

include_once(__DIR__ . "/../auth_guard.php");
require_role('creator');   // viewer blocked; creator + admin allowed

include_once(__DIR__ . "/../Movie.php");

// The logged-in creator's own movies (all statuses).
$myId   = $_SESSION['uid'];
$movies = Movie::listByCreator($myId);

include_once(__DIR__ . "/../header.php");
?>

<h1>Creator Panel</h1>
<p class="muted">Your movies, <?php echo htmlentities($_SESSION['username']); ?>. Add new titles, edit drafts, and publish when ready.</p>

<p><a class="btn" href="<?php echo BASE_URL; ?>/creator/add_movie.php">+ Add new movie</a></p>

<?php if (empty($movies)): ?>
    <p>You haven't added any movies yet. Click "Add new movie" to create your first draft.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Views</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $m): ?>
                <tr>
                    <td><?php echo htmlentities($m['title']); ?></td>
                    <td>
                        <span class="badge"><?php echo htmlentities($m['status']); ?></span>
                    </td>
                    <td><?php echo (int)$m['view_count']; ?></td>
                    <td><?php echo htmlentities($m['created_at']); ?></td>
                    <td>
                        <?php if ($m['status'] === 'draft'): ?>
                            <a class="btn" href="<?php echo BASE_URL; ?>/creator/edit_movie.php?id=<?php echo (int)$m['movie_id']; ?>">Edit</a>
                            <a class="btn" href="<?php echo BASE_URL; ?>/creator/publish_movie.php?id=<?php echo (int)$m['movie_id']; ?>"
                               onclick="return confirm('Publish this movie? It will become visible to everyone.');">Publish</a>
                            <a class="btn btn-danger" href="<?php echo BASE_URL; ?>/creator/delete_movie.php?id=<?php echo (int)$m['movie_id']; ?>"
                               onclick="return confirm('Delete this draft permanently? This cannot be undone.');">Delete</a>
                        <?php else: ?>
                            <span class="muted">Published — locked</span>
                            <a class="btn" href="<?php echo BASE_URL; ?>/movie_view.php?id=<?php echo (int)$m['movie_id']; ?>" target="_blank">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include_once(__DIR__ . "/../footer.php"); ?>