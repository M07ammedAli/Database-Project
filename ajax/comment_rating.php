<?php
require_once(__DIR__ . "/../Movie.php");
require_once(__DIR__ . "/../auth_guard.php");

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'rate') {
    if (!is_logged_in()) {
        echo json_encode(['ok' => false, 'msg' => 'Please log in to rate.']);
        exit();
    }
    $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $stars   = isset($_POST['stars']) ? (int)$_POST['stars'] : 0;
    if ($movieId < 1 || $stars < 1 || $stars > 5) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid data.']);
        exit();
    }
    $movie = new Movie();
    if (!$movie->initWithId($movieId) || $movie->getStatus() !== 'published') {
        echo json_encode(['ok' => false, 'msg' => 'Movie not found.']);
        exit();
    }
    $ok = $movie->upsertRating($_SESSION['uid'], $stars);
    if ($ok) {
        $newAvg = $movie->getAverageRating();
        $newCnt = $movie->getRatingCount();
        echo json_encode(['ok' => true, 'avg' => $newAvg, 'count' => $newCnt, 'myStars' => $stars]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Failed to save rating.']);
    }
    exit();
}

if ($action === 'comment') {
    if (!is_logged_in()) {
        echo json_encode(['ok' => false, 'msg' => 'Please log in to comment.']);
        exit();
    }
    $movieId = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $body    = isset($_POST['body']) ? trim($_POST['body']) : '';
    if ($movieId < 1 || $body === '') {
        echo json_encode(['ok' => false, 'msg' => 'Comment cannot be empty.']);
        exit();
    }
    $movie = new Movie();
    if (!$movie->initWithId($movieId) || $movie->getStatus() !== 'published') {
        echo json_encode(['ok' => false, 'msg' => 'Movie not found.']);
        exit();
    }
    $cleanBody = htmlentities(strip_tags($body));
    $commentId = $movie->insertComment($_SESSION['uid'], $cleanBody);
    if ($commentId) {
        echo json_encode([
            'ok'       => true,
            'id'       => $commentId,
            'username' => $_SESSION['username'],
            'body'     => $cleanBody,
            'created'  => date('Y-m-d H:i:s'),
            'isAdmin'  => (current_role() === 'admin')
        ]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Failed to save comment.']);
    }
    exit();
}

if ($action === 'delete') {
    if (!is_logged_in() || current_role() !== 'admin') {
        echo json_encode(['ok' => false, 'msg' => 'Access denied.']);
        exit();
    }
    $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    if ($commentId < 1) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid comment ID.']);
        exit();
    }
    $ok = Movie::deleteComment($commentId);
    echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Comment deleted.' : 'Delete failed.']);
    exit();
}

echo json_encode(['ok' => false, 'msg' => 'Unknown action.']);
