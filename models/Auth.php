<?php
// models/Auth.php
class Auth {
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return User::find($_SESSION['user_id']);
        }
        return null;
    }
    
    public function login($username, $password) {
        $user = User::find('first', array(
            'conditions' => array('username = ? AND password = ?', $username, md5($password))
        ));
        
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_start();
        session_destroy();
    }
}