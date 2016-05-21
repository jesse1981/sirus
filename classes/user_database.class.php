<?php
class user {
    var $session;
    
    // private
    private function storeSession($user) {
        $GLOBALS["USER_ID"] = (int)$user[0]["id"];
        $name_first = $user[0]["firstname"];
        $this->session->addKey("username",$user);

        $this->session->addKey("dudkey","dudkey");
        $this->session->addKey("first_name",$user[0]["firstname"]);
        $this->session->addKey("lastname",$user[0]["lastname"]);

        $this->session->addKey("email",$user[0]["email"]);
        $this->session->addKey("department",$user[0]["department"]);

        $this->session->addKey("avatar",$user[0]["avatar"]);
        $this->session->addKey("user_id",$user[0]["id"]);
        $this->session->addKey("access",$user[0]["access"]);
        $this->session->addKey("group_id",$user[0]["group_id"]);

        return $user;
    }
    
    // public
    public function __construct() {
        $this->session = new session();
        $dash = new dashboard();
        
        // Only authenticate if the request comes from an address other than itself.
        if ($_SERVER["REMOTE_ADDR"]!=$_SERVER["SERVER_ADDR"]) {
            if ($this->session->getKey('username')=='') {
                // Proceed with HTTP authentication method
                if (isset($_GET["access_token"])) {
                    $token = htmlentities($_GET["access_token"]);
                    // check length
                    if (strlen($token)==32) {
                        $db  = new database();
                        $sql = "SELECT * "
                                . "FROM customer_session "
                                . "WHERE token = '$token'";
                        $res = $db->query($sql);
                        $tab = $db->getTable($res);
                        if (isset($tab[0])) {
                            $user_id = (int)$tab[0]["user_id"];
                            $sql = "SELECT * "
                                    . "FROM users "
                                    . "WHERE id = $user_id";
                            $res = $db->query($sql);
                            $tab = $db->getTable($res);
                            if (isset($tab[0])) {
                                $stored = $this->storeSession($tab);
                                return $stored;
                            }
                            else return false;
                        }
                    }
                } 
                else {
                    $dash = new dashboard();
                    $post = $dash->getPost();
                    if (isset($post["username"])) {
                        $user = $post["username"];
                        $pass = $post["password"];
                        $result = $this->login($user, $pass);
                        if ($result) {
                            if (!isset($_GET["redirect"])) header('Location: '.ROOTWEB);
                            else if ($_GET["redirect"]=="index/index") header('Location: '.ROOTWEB);
                            else header('Location: '.ROOTWEB.$_GET["redirect"]);
                        }
                        else header('Location: '.ROOTWEB.'login/invalid');
                    }
                    else if (MODULE != "login") $dash->redirect('login?redirect='.MODULE.'/'.ACTION);
                }
            }
        }
    }
    
	public function getUserId($username) {
		$id = 0;
		$db = new database();
		$sql = "SELECT * FROM users WHERE username = '$username'";
		$res = $db->query($sql);
		$tab = $db->getTable($res);
		if (isset($tab[0])) {
			$id = (int)$tab[0]["id"];
		}
		return $id;
	}
    public function login($user,$pass) {
        $db = new database();
        $success = false;
		
		// get user row
		$sql = "SELECT * FROM users WHERE username = '$user'";
		$res = $db->query($sql);
		$tab = $db->getTable($res);
		if (isset($tab[0])) {
			$id = $tab[0]["id"];
			$salt = "This is CookieJar! $id";
			$hash = md5($salt . $pass);
			if ($hash == $tab[0]["password"]) $success = true;
		}

        if ($success) {
	    $stored = $this->storeSession($tab);
            return $stored;
        }
        else return false;
    }
    public function logout() {
        // is logged in?
        if ($this->session->getKey("username")!="") {
            $this->session->delKey("username");
            $this->session->delKey("firstname");
            $this->session->delKey("lastname");
            $this->session->delKey("admin");
            $this->session->delKey("user_id");
            $this->session->delKey("group_id");
            return true;
        }
        else return false;
    }
}
?>