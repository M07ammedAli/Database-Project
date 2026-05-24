<?php
session_start();
include_once("LoginClass.php");

$lgnObj = new LoginClass();
$lgnObj->logout();

header("Location: ../index.php");
exit();
?>