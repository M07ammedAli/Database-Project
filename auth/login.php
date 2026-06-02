<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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


include_once(__DIR__ . "/../header.php");
?>

<div class="card">
    <h1>Login</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlentities($error); ?></div>
    <?php endif; ?>

    <form method="post" action="" onsubmit="return validateLogin();">
        <p>
            <label for="username">Username</label>
            <input type="text" name="username" id="username">
        </p>
        <p>
            <label for="password">Password</label>
            <input type="password" name="password" id="password">
        </p>
        <p><input type="submit" value="Login"></p>
        <input type="hidden" name="submitted" value="TRUE">
    </form>

    <p class="muted">No account? <a href="<?php echo BASE_URL; ?>/auth/register.php">Register here</a></p>
</div>

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

<?php include_once(__DIR__ . "/../footer.php"); ?>