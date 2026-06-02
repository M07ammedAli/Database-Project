<?php
include_once("Users.php");

$error = "";
$success = "";

if (isset($_POST['submitted'])) {
    $userObj = new Users();

    $username = $userObj->cleanXSS($_POST['username']);
    $email    = $userObj->cleanXSS($_POST['email']);
    $password = trim($_POST['password']);   

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


include_once(__DIR__ . "/../header.php");
?>

<div class="card">
    <h1>Register</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlentities($success); ?></div>
    <?php endif; ?>

    <form method="post" action="" onsubmit="return validateForm();">
        <p>
            <label for="username">Username</label>
            <input type="text" name="username" id="username"
                   value="<?php echo isset($_POST['username']) ? htmlentities($_POST['username']) : ''; ?>">
        </p>
        <p>
            <label for="email">Email</label>
            <input type="email" name="email" id="email"
                   value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>">
        </p>
        <p>
            <label for="password">Password</label>
            <input type="password" name="password" id="password">
        </p>
        <p><input type="submit" value="Register"></p>
        <input type="hidden" name="submitted" value="TRUE">
    </form>

    <p class="muted">Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Login here</a></p>
</div>

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

<?php include_once(__DIR__ . "/../footer.php"); ?>