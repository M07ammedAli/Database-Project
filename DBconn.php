<?php

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