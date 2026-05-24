<?php
// =====================================================================
// Database connection for the Movie Review System.
// Deployed on the uni server (SFTP host 20.74.143.233).
//
// IMPORTANT - fill these in with YOUR phpMyAdmin credentials:
//   $username = your MySQL user  (e.g. u123456789)
//   $password = your phpMyAdmin password
//   $database = the SHARED project database name
//
// Everything in the app calls getConnection() so the credentials
// only ever live in this one file.
// =====================================================================
function getConnection()
{
    $server   = "localhost";        // DB runs on the same server as PHP
    $username = "u202304108";     // <-- replace (e.g. u123456789)
    $password = "asdASD123!";       // <-- replace with phpMyAdmin password
    $database = "db202304108";         // <-- replace with shared DB name

    $dbc = mysqli_connect($server, $username, $password, $database);

    if (mysqli_connect_errno()) {
        // On a live site we avoid printing detailed errors (Unit 11 security).
        echo "Connection failed. Please contact the administrator.";
        return false;
    }
    return $dbc;
}
?>