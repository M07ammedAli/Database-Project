<?php

define('BASE_URL', '/~u202304108/MovieReview');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



function is_logged_in()
{
    return isset($_SESSION['uid']);
}


function current_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}


function require_login()
{
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}


function require_role($needed)
{
    require_login();

    $rank = array('viewer' => 1, 'creator' => 2, 'admin' => 3);
    $have = current_role();

    $haveLevel   = isset($rank[$have])   ? $rank[$have]   : 0;
    $neededLevel = isset($rank[$needed]) ? $rank[$needed] : 99;

    if ($haveLevel < $neededLevel) {
    
        http_response_code(403);
        echo "<h2>Access denied</h2>";
        echo "<p>You do not have permission to view this page.</p>";
        echo "<p><a href='" . BASE_URL . "/index.php'>Back to home</a></p>";
        exit();
    }
}
?>