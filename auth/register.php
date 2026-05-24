<?php
session_start();
include_once("Users.php");

$error = "";
$success = "";

if (isset($_POST['submitted'])) {
    $userObj = new Users();
    // XSS clean on text inputs (course: htmlentities + strip_tags)
    $username = $userObj->cleanXSS($_POST['username']);
    $email    = $userObj->cleanXSS($_POST['email']);
    $password = trim($_POST['password']);   // not cleaned: it gets hashed, never displayed

    // Server-side validation
    if ($username == "" || $email == "" || $password == "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($userObj->emailExists($email)) {
        $error = "Email already registered.";
    } else {
        $userObj->setUsername($username);
        $userObj->setEmail($email);
        $userObj->setPassword($password);
        $userObj->setRole('viewer');
        if ($userObj->registerUser()) {
            $success = "Account created. You can now log in.";
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Register</h1>
    <?php
      if ($error)   echo "<p style='color:red;'>$error</p>";
      if ($success) echo "<p style='color:green;'>$success</p>";
    ?>
    <form method="post" action="" onsubmit="return validateForm();">
        <p>Username: <input type="text" name="username" id="username"
           value="<?php echo isset($_POST['username']) ? htmlentities($_POST['username']) : ''; ?>"></p>
        <p>Email: <input type="email" name="email" id="email"
           value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>"></p>
        <p>Password: <input type="password" name="password" id="password"></p>
        <p><input type="submit" value="Register"></p>
        <input type="hidden" name="submitted" value="TRUE">
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>

    <!-- Client-side (JavaScript) validation: brief requires both client + server -->
    <script>
    function validateForm() {
        var u = document.getElementById("username").value.trim();
        var e = document.getElementById("email").value.trim();
        var p = document.getElementById("password").value.trim();
        if (u === "" || e === "" || p === "") {
            alert("All fields are required.");
            return false;
        }
        var emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
        if (!emailPattern.test(e)) {
            alert("Please enter a valid email.");
            return false;
        }
        if (p.length < 6) {
            alert("Password must be at least 6 characters.");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>