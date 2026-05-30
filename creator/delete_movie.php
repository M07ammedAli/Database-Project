<?php

// Deletes a DRAFT. Owner (or admin) only. Drafts only.
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

// Design rule: creators delete drafts only. Published stays (admin handles those).
if ($movie->getStatus() !== 'draft') {
    include_once(__DIR__ . "/../header.php");
    echo "<h2>Cannot delete</h2><p>Published movies can only be removed by an administrator.</p>";
    echo "<p><a href='" . BASE_URL . "/creator/dashboard.php'>Back to dashboard</a></p>";
    include_once(__DIR__ . "/../footer.php");
    exit();
}

$movie->delete();
header("Location: " . BASE_URL . "/creator/dashboard.php");
exit();