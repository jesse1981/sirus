<?php
class user {
    var $db;
    var $session;
    
    public function __construct() {
        $this->db = new database();
        $this->session = new session();
    }
    
    public function login($user,$pass) {
        $success = false;
        
        $users = $this->db->getAllItems('users');
        foreach ($users as $item) {
            if ($item->fields["username"]==$user) {
                $id = $item->id;
                $salt = "This is CookieJar! $id";
                $hash = md5($salt . $pass);
                $success = ($hash == $item->fields["password"]) ? true:false;
                break;
            }
        }
        
        if ($success) {
            foreach($item->fields as $k=>$v) {
                if (!is_object($v)) $this->session->addKey($k,$v);
            }
        }
        else return false;
    }
    public function logout() {
        // is logged in?
        if ($this->session->getKey("username")!="") {
            foreach ($_SESSION as $k=>$v) {
                $this->session->delKey($k);
            }
            return true;
        }
        else return false;
    }
}
?>