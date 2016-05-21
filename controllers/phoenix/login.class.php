<?php
class login extends template {
    public function index() {
        $this->setView('login','_login.php');
    }
    public function invalid() {
        $this->setView('login','_login.php');
    }
    public function logout() {
        $session = new session();
        $session->addKey('username','');
        $session->addKey('firstname','');
        header('Location: http://phoenix.agbnielsen.com.au');
    }
}
?>