<?php
// =====================================================================
// Shared page header + navigation. Include at the top of every page:
//     include_once(__DIR__ . "/header.php");   (adjust path per folder)
//
// Navigation adapts to the logged-in role stored in $_SESSION.
// Uses a BASE_URL constant so links work from any sub-folder.
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('BASE_URL', '/~u202304108/MovieReview');


$role     = isset($_SESSION['role'])     ? $_SESSION['role']     : null;
$username = isset($_SESSION['username'])  ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Review System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="brand">
        <a href="<?php echo BASE_URL; ?>/index.php">🎬 MovieReview</a>
    </div>
    <nav class="main-nav">
        <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
        <a href="<?php echo BASE_URL; ?>/search/search.php">Search</a>

        <?php if ($role === 'creator' || $role === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/creator/dashboard.php">Creator Panel</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a>
        <?php endif; ?>

        <?php if ($username): ?>
            <span class="nav-user">Hi, <?php echo htmlentities($username); ?></span>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a>
            <a href="<?php echo BASE_URL; ?>/auth/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page">