<?php
class session {
    var $initialized;
    var $sessionid;
    
    public function __construct() {
        if (php_sapi_name() != 'cli') {
            if (session_id()=="") {
                if (session_start()==false) {
                    // raise error
                    $this->sessionid="";
                }
                else {
                    $this->sessionid = session_id();
                }
            }
            $this->initialized = true;
            return $this->sessionid;
        }
    }
    public function addKey($key,$value="") {
        $_SESSION[$key] = $value;
    }
    public function delKey($key) {
        if (isset($_SESSION[$key])) {
            unset ($_SESSION[$key]);
            return true;
        }
        else return false;
    }
    public function getKey($key="") {
        if ($key!="") {
            if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            }
            else return false;
        }
        else if ($this->initialized) return $_SESSION;
    }
}
?>