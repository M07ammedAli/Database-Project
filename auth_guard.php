<?php
// =====================================================================
// Authentication & role guard.
// Include at the very top of any protected page (BEFORE any output):
//     include_once(__DIR__ . "/../auth_guard.php");
//     require_login();              // any logged-in user
//     require_role('creator');      // creator OR admin
//     require_role('admin');        // admin only
//
// Keeps role-permission logic in one place so the Test Plan can show
// that permissions are enforced consistently (Functional 1.1 / 1.5 / 1.6).
// =====================================================================
define('BASE_URL', '/~u202304108/MovieReview');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// True if someone is logged in.
function is_logged_in()
{
    return isset($_SESSION['uid']);
}

// Current role or null.
function current_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Block guests: send to login if not signed in.
function require_login()
{
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}

// Block users below the required role.
// Role ranking: viewer < creator < admin.
// require_role('creator') therefore also lets admins through.
function require_role($needed)
{
    require_login();

    $rank = array('viewer' => 1, 'creator' => 2, 'admin' => 3);
    $have = current_role();

    $haveLevel   = isset($rank[$have])   ? $rank[$have]   : 0;
    $neededLevel = isset($rank[$needed]) ? $rank[$needed] : 99;

    if ($haveLevel < $neededLevel) {
        // Logged in but not allowed -> friendly message, no detailed error.
        http_response_code(403);
        echo "<h2>Access denied</h2>";
        echo "<p>You do not have permission to view this page.</p>";
        echo "<p><a href='" . BASE_URL . "/index.php'>Back to home</a></p>";
        exit();
    }
}
?>