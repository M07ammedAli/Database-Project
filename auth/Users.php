<?php
// Data object for dbProj_users. Holds ONE row at a time.
include_once(__DIR__ . "/../DBconn.php");

class Users
{
    private $user_id;
    private $username;
    private $email;
    private $password_hash;
    private $role;

    // ---------- Getters ----------
    public function getUserId()   { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getEmail()    { return $this->email; }
    public function getRole()     { return $this->role; }

    // ---------- Setters ----------
    public function setUserId($id)      { $this->user_id = $id; }
    public function setUsername($u)     { $this->username = $u; }
    public function setEmail($e)        { $this->email = $e; }
    public function setRole($r)         { $this->role = $r; }
    public function setPassword($plain) { $this->password_hash = password_hash($plain, PASSWORD_DEFAULT); }

    // ---------- XSS prevention helper (course: htmlentities / strip_tags) ----------
    public function cleanXSS($data)
    {
        return htmlentities(strip_tags(trim($data)));
    }

    // ---------- initWithUid(): fetch one user by PK, using prepared statement ----------
    public function initWithUid($uid)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "SELECT user_id, username, email, password_hash, role FROM dbProj_users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $uid);            // i = integer
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $uname, $mail, $phash, $role);
        if (mysqli_stmt_fetch($stmt)) {
            $this->user_id = $id;
            $this->username = $uname;
            $this->email = $mail;
            $this->password_hash = $phash;
            $this->role = $role;
            mysqli_stmt_close($stmt);
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    // ---------- checkUser(): fetch by username for login, prepared statement ----------
    public function checkUser($username)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "SELECT user_id, username, email, password_hash, role FROM dbProj_users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);       // s = string
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $uname, $mail, $phash, $role);
        if (mysqli_stmt_fetch($stmt)) {
            $row = array(
                'user_id' => $id, 'username' => $uname, 'email' => $mail,
                'password_hash' => $phash, 'role' => $role
            );
            mysqli_stmt_close($stmt);
            return $row;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    // ---------- emailExists(): validation, prepared statement ----------
    public function emailExists($email)
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "SELECT user_id FROM dbProj_users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = (mysqli_stmt_num_rows($stmt) > 0);
        mysqli_stmt_close($stmt);
        return $exists;
    }

    // ---------- registerUser(): INSERT with prepared statement ----------
    public function registerUser()
    {
        $dbc = getConnection();
        $stmt = mysqli_prepare($dbc, "INSERT INTO dbProj_users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $this->username, $this->email, $this->password_hash, $this->role);
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            error_log('registerUser failed: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        mysqli_stmt_close($stmt);
        return true;
    }
}
?>