<?php
include_once(__DIR__ . "/Users.php");

class LoginClass extends Users
{
    public function login($username, $password)
    {
        $row = $this->checkUser($username);   // inherited, uses prepared statement
        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['uid']      = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];
            return true;
        }
        return false;
    }

    public function logout()
    {
        $_SESSION = array();
        session_destroy();
    }
}
?>