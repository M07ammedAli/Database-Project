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

// First letter for the avatar chip.
$avatarLetter = $username ? strtoupper(substr($username, 0, 1)) : '?';
$roleLabel    = $role ? ucfirst($role) : 'Guest';
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
<header class="site-header cinematic">
    <div class="nav-left">
        <div class="brand">
            <a href="<?php echo BASE_URL; ?>/index.php">
                <svg class="brand-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.2 6 3 11l-.9-2.4c-.3-1.1.3-2.2 1.3-2.5l13.5-4c1.1-.3 2.2.3 2.5 1.3Z"/><path d="m6.2 5.3 3.1 3.9"/><path d="m12.4 3.4 3.1 4"/><path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/></svg>
                MovieReview
            </a>
        </div>
        <nav class="main-nav">
            <a href="<?php echo BASE_URL; ?>/index.php">Home</a>

            <?php if ($role === 'creator' || $role === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>/creator/dashboard.php">Creator Panel</a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Search box routes to the real search page (GET ?q=) -->
    <form class="topbar-search" method="get" action="<?php echo BASE_URL; ?>/search/search.php">
        <input type="hidden" name="submitted" value="1">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" name="q" placeholder="Search movies, titles, creators…">
    </form>

    <div class="nav-actions">
        <?php if ($username): ?>
            <div class="profile-chip">
                <span class="avatar"><?php echo htmlentities($avatarLetter); ?></span>
                <span class="who2">
                    <b><?php echo htmlentities($username); ?></b>
                    <span><?php echo htmlentities($roleLabel); ?></span>
                </span>
            </div>
            <a class="btn secondary" href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a>
        <?php else: ?>
            <a class="btn secondary" href="<?php echo BASE_URL; ?>/auth/login.php">Login</a>
            <a class="btn" href="<?php echo BASE_URL; ?>/auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</header>
<main class="page">