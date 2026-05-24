<?php
session_start();
include_once("LoginClass.php");

$error = "";

if (isset($_POST['submitted'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username == "" || $password == "") {
        $error = "Please enter username and password.";
    } else {
        $lgnObj = new LoginClass();
        if ($lgnObj->login($username, $password)) {
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Wrong/Mismatch Login or Password";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Login</h1>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="" onsubmit="return validateLogin();">
        <p>Username: <input type="text" name="username" id="username"></p>
        <p>Password: <input type="password" name="password" id="password"></p>
        <p><input type="submit" value="Login"></p>
        <input type="hidden" name="submitted" value="TRUE">
    </form>
    <p>No account? <a href="register.php">Register here</a></p>

    <script>
    function validateLogin() {
        var u = document.getElementById("username").value.trim();
        var p = document.getElementById("password").value.trim();
        if (u === "" || p === "") {
            alert("Please enter username and password.");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>