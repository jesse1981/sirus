<?php
class user {
    var $session;
    
    public function __construct() {
        $this->session = new session();
        $dash = new dashboard();
        
        // Only authenticate if the request comes from an address other than itself.
        if ($_SERVER["REMOTE_ADDR"]!=$_SERVER["SERVER_ADDR"]) {
            if ($this->session->getKey('username')=='') {
                // Which application is requesting? If not a known browser, or if it is MSOffice - do HTTP auth.
                if (
                        (
                            (strpos(CLIENT_BROWSER,"Mozilla")===false) &&
                            (strpos(CLIENT_BROWSER,"Firefox")===false) &&
                            (strpos(CLIENT_BROWSER,"MSIE")===false)
                        ) ||
                        (strpos(CLIENT_BROWSER,"MSOffice"))
                    ){
                    // Proceed with HTTP authentication method
                    if (!isset($_SERVER['PHP_AUTH_USER'])) {
                        header('WWW-Authenticate: Basic realm="Restricted Area"');
                        header('HTTP/1.0 401 Unauthorized');
                        die('Authentication Required');
                        exit;
                    } else {
                        if (!$this->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                            die('Credentials incorrect.');
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
    
    public function login($user,$pass) {
        $db = new database();
        $success = false;
        
        try {
            $adldap = new adLDAP();
        }
        catch (adLDAPException $e) {
            die('No Active Directory support detected!');
        }
        if($adldap->authenticate($user,$pass)) {
            $success = true;
        }

        if ($success) {
            $authenticated = $adldap->authenticate('jbryant','geohash15');
            $info = $adldap->user()->info($user);
            $name = explode(" ",$info[0]["displayname"][0]);
            
            $sql = "SELECT * FROM users WHERE username='$user'";
            $res = $db->query($sql);
            $num = $db->getNumRows($res);
            if ($num) {
                $tab = $db->getTable($res);
                $name_first = $tab[0]["firstname"];
                
                $this->session->addKey("dudkey","dudkey");
                $this->session->addKey("first_name",$tab[0]["firstname"]);
                $this->session->addKey("lastname",$tab[0]["lastname"]);
                
                $this->session->addKey("email",$tab[0]["email"]);
                $this->session->addKey("department",$tab[0]["department"]);
                
                $this->session->addKey("avatar",$tab[0]["avatar"]);
                $this->session->addKey("user_id",$tab[0]["id"]);
                $this->session->addKey("access",$tab[0]["access_level"]);

                /*
                $sql = "SELECT * FROM staff_group WHERE staff_id=".$tab[0]["id"];
                $res = $db->query($sql);
                $tab = $db->getTable($res);
                $num = $db->getNumRows($res);

                if ($num) $this->session->addKey("group_id",$tab[0]["group_id"]);
                */
            }
            else {
                // Do insert for new user
                try {
                    $name_first = $name[0];
                    $sql = "INSERT INTO users ( username,firstname,lastname,email,department,access_level) VALUES (
                                                '$user',
                                                '".$name[0]."',
                                                '".$name[count($name)-1]."',
                                                '".$info[0]["mail"][0]."',
                                                '".$info[0]["department"][0]."',
                                                1)";
                    $db->query($sql);

                    // temporary: mail query to me:
                    /*
                    $mail = new mailer();
                    $mail->bodytext($sql);
                    $mail->sendmail('jesse.bryant@nielsen.com', 'user creation');
                    */
                }
                catch (Exception $e) {
                    echo "Failed to create user.  Error: ".$e->getMessage();
                }
            }
            $this->session->addKey("username",$user);
            $this->session->addKey("firstname",$name_first);
            return true;
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