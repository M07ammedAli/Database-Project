<?php
include_once(__DIR__ . "/Movie.php");
define('BASE_URL', '/~u202304108/MovieReview');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$movie = new Movie();
if (!$id || !$movie->initWithId($id) || $movie->getStatus() !== 'published') {
    include_once(__DIR__ . "/header.php");
    echo "<h1>Movie not found</h1><p>This movie does not exist or is not published.</p>";
    echo "<p><a href='" . BASE_URL . "/index.php'>Back to home</a></p>";
    include_once(__DIR__ . "/footer.php");
    exit();
}

// Count this visit (feeds popularity search + admin reports).
$movie->incrementViews();

$avg    = $movie->getAverageRating();
$count  = $movie->getRatingCount();
$poster = $movie->getPosterImage() ? $movie->getPosterImage() : 'placeholder.jpg';

// Fetch comments (newest first) with usernames.
$dbc  = getConnection();
$cid  = $movie->getMovieId();
$cstmt = mysqli_prepare($dbc,
    "SELECT c.comment_id, c.body, c.created_at, u.username
     FROM dbProj_comments c
     LEFT JOIN dbProj_users u ON u.user_id = c.user_id
     WHERE c.movie_id = ?
     ORDER BY c.created_at DESC");
mysqli_stmt_bind_param($cstmt, "i", $cid);
mysqli_stmt_execute($cstmt);
$cres = mysqli_stmt_get_result($cstmt);
$comments = array();
while ($row = mysqli_fetch_assoc($cres)) { $comments[] = $row; }
mysqli_stmt_close($cstmt);

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$userRating = isset($_SESSION['uid']) ? $movie->getUserRating($_SESSION['uid']) : null;

include_once(__DIR__ . "/header.php");
?>

<a href="<?php echo BASE_URL; ?>/index.php">&laquo; Back to movies</a>

<h1><?php echo htmlentities($movie->getTitle()); ?></h1>
<p class="meta"><svg class="star-ico" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> <?php echo $avg; ?> (<?php echo $count; ?> ratings) · <?php echo (int)$movie->getViewCount(); ?> views</p>

<div style="display:flex; gap:24px; flex-wrap:wrap; margin-top:16px;">
    <img src="<?php echo BASE_URL; ?>/images/<?php echo htmlentities($poster); ?>"
         alt="poster" style="width:260px; border-radius:10px;">
    <div style="flex:1; min-width:260px;">
        <p><?php echo nl2br(htmlentities($movie->getDescription())); ?></p>
        <?php if ($movie->getReleaseDate()): ?>
            <p class="meta">Release date: <?php echo htmlentities($movie->getReleaseDate()); ?></p>
        <?php endif; ?>
        <?php if ($movie->getTrailerUrl()): ?>
            <p><a class="btn" href="<?php echo htmlentities($movie->getTrailerUrl()); ?>" target="_blank" rel="noopener"><svg class="ico" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="vertical-align:-3px;"><polygon points="6 3 20 12 6 21 6 3"/></svg> Watch Trailer</a></p>
        <?php endif; ?>
    </div>
</div>

<h2>Rate &amp; Comment</h2>

<?php if (isset($_SESSION['uid'])): ?>
    <p class="muted">Logged in as <strong><?php echo htmlentities($_SESSION['username']); ?></strong></p>

    <!-- star rating widget -->
    <div style="margin-bottom:16px;">
        <span style="color:var(--muted); font-size:.9rem;">Your rating: </span>
        <span class="stars" id="ratingStars" data-movie="<?php echo $cid; ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star<?php echo ($userRating && $i <= $userRating) ? ' on' : ''; ?>"
                      data-val="<?php echo $i; ?>">&#9733;</span>
            <?php endfor; ?>
        </span>
        <span id="ratingMsg" style="font-size:.85rem; margin-left:8px; color:var(--ok);"></span>
    </div>

    <!-- comment form -->
    <div style="margin-bottom:20px;">
        <textarea id="commentBody" rows="3" placeholder="Write your comment..." maxlength="1000"></textarea>
        <div style="margin-top:6px; display:flex; align-items:center; gap:10px;">
            <button id="submitComment" class="btn" data-movie="<?php echo $cid; ?>">Post Comment</button>
            <span id="commentMsg" style="font-size:.85rem; color:var(--ok);"></span>
        </div>
    </div>
<?php else: ?>
    <p class="muted"><a href="<?php echo BASE_URL; ?>/auth/login.php">Log in</a> to rate or comment.</p>
<?php endif; ?>

<h2>Comments</h2>
<div id="commentsList">
<?php if (empty($comments)): ?>
    <p id="noComments">No comments yet. Be the first!</p>
<?php else: ?>
    <?php foreach ($comments as $c): ?>
        <div class="comment" data-id="<?php echo (int)$c['comment_id']; ?>">
            <p class="who"><?php echo htmlentities($c['username']); ?>
               <span class="when"><?php echo htmlentities($c['created_at']); ?></span>
               <?php if ($isAdmin): ?>
                   <button class="btn-delete-comment" data-id="<?php echo (int)$c['comment_id']; ?>"
                           style="float:right; background:var(--accent-2); color:#fff; border:none;
                                  border-radius:4px; padding:2px 8px; cursor:pointer; font-size:.75rem;">Delete</button>
               <?php endif; ?>
            </p>
            <p><?php echo htmlentities($c['body']); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
var ajaxUrl = "<?php echo BASE_URL; ?>/ajax/comment_rating.php";

// ----- Star rating -----
(function(){
    var stars = document.querySelectorAll('#ratingStars .star');
    var msgEl = document.getElementById('ratingMsg');
    var rated = false;
    stars.forEach(function(s){
        s.addEventListener('mouseenter', function(){
            if (rated) return;
            var v = parseInt(this.getAttribute('data-val'));
            stars.forEach(function(ss, i){ ss.classList.toggle('on', i < v); });
        });
        s.addEventListener('click', function(){
            var v = parseInt(this.getAttribute('data-val'));
            rated = true;
            msgEl.textContent = 'Saving...';
            msgEl.style.color = '';
            var fd = new FormData();
            fd.append('action', 'rate');
            fd.append('movie_id', document.getElementById('ratingStars').getAttribute('data-movie'));
            fd.append('stars', v);
            fetch(ajaxUrl, { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.ok) {
                    msgEl.textContent = 'Rated ' + v + '/5';
                    msgEl.style.color = 'var(--ok)';
                    document.querySelector('.meta').innerHTML =
                        '<svg class="star-ico" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> ' + parseFloat(d.avg).toFixed(1) + ' (' + d.count + ' ratings)' +
                        ' &middot; ' + document.querySelector('.meta').innerHTML.split('·')[1];
                    stars.forEach(function(ss, i){ ss.classList.toggle('on', i < d.myStars); });
                } else {
                    msgEl.textContent = d.msg;
                    msgEl.style.color = 'var(--accent-2)';
                    rated = false;
                }
            });
        });
    });
    document.getElementById('ratingStars').addEventListener('mouseleave', function(){
        if (rated) return;
        var cur = <?php echo $userRating ? $userRating : 0; ?>;
        stars.forEach(function(ss, i){ ss.classList.toggle('on', i < cur); });
    });
})();

// ----- Comment submit -----
(function(){
    var btn = document.getElementById('submitComment');
    if (!btn) return;
    var textarea = document.getElementById('commentBody');
    var msgEl = document.getElementById('commentMsg');
    btn.addEventListener('click', function(){
        var body = textarea.value.trim();
        if (body === '') {
            msgEl.textContent = 'Comment cannot be empty.';
            msgEl.style.color = 'var(--accent-2)';
            return;
        }
        btn.disabled = true;
        msgEl.textContent = 'Posting...';
        msgEl.style.color = '';
        var fd = new FormData();
        fd.append('action', 'comment');
        fd.append('movie_id', btn.getAttribute('data-movie'));
        fd.append('body', body);
        fetch(ajaxUrl, { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) {
                textarea.value = '';
                msgEl.textContent = '';
                var delBtn = d.isAdmin
                    ? '<button class="btn-delete-comment" data-id="'+d.id+'" style="float:right; background:var(--accent-2); color:#fff; border:none; border-radius:4px; padding:2px 8px; cursor:pointer; font-size:.75rem;">Delete</button>'
                    : '';
                var html = '<div class="comment" data-id="'+d.id+'">'
                    + '<p class="who">' + escapeHTML(d.username) + ' <span class="when">' + d.created + '</span>' + delBtn + '</p>'
                    + '<p>' + escapeHTML(d.body) + '</p></div>';
                var noEl = document.getElementById('noComments');
                if (noEl) noEl.remove();
                var list = document.getElementById('commentsList');
                list.insertAdjacentHTML('afterbegin', html);
                bindDelete(list.firstElementChild.querySelector('.btn-delete-comment'));
            } else {
                msgEl.textContent = d.msg;
                msgEl.style.color = 'var(--accent-2)';
            }
            btn.disabled = false;
        })
        .catch(function(){
            msgEl.textContent = 'Network error.';
            msgEl.style.color = 'var(--accent-2)';
            btn.disabled = false;
        });
    });
})();

// ----- Comment delete (admin) -----
function bindDelete(btn){
    if (!btn) return;
    btn.addEventListener('click', function(){
        if (!confirm('Delete this comment?')) return;
        var id = this.getAttribute('data-id');
        var fd = new FormData();
        fd.append('action', 'delete');
        fd.append('comment_id', id);
        fetch(ajaxUrl, { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) {
                var el = document.querySelector('.comment[data-id="'+id+'"]');
                if (el) el.remove();
                if (document.querySelectorAll('#commentsList .comment').length === 0) {
                    document.getElementById('commentsList').innerHTML = '<p id="noComments">No comments yet.</p>';
                }
            } else {
                alert(d.msg);
            }
        });
    });
}
document.querySelectorAll('.btn-delete-comment').forEach(function(b){ bindDelete(b); });

function escapeHTML(str){
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
</script>

<?php include_once(__DIR__ . "/footer.php"); ?>