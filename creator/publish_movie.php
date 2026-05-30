<?php

// Flips a DRAFT to published. Owner (or admin) only. Drafts only.

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

// Ownership check: must own it, unless admin.
$isOwner = ((int)$movie->getCreatorId() === (int)$_SESSION['uid']);
$isAdmin = (current_role() === 'admin');
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Access denied</h2><p>This is not your movie.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

// Only drafts can be published.
if ($movie->getStatus() !== 'draft') {
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Already published</h2><p>This movie is already published.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

$movie->publish();
header("Location: " . BASE_URL . "/creator/dashboard.php");
exit();