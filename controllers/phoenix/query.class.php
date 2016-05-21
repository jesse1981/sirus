<?php
class query extends template {
    public $access = 95;
    
    public function index() {
        $this->setView('query', '_master.php');
    }
    
    public function addRecent(){
        $post = $_POST;
        $query = htmlspecialchars($post["query"],ENT_QUOTES);
        $database = $post["database"];
        
        $db = new database();
        $table = $database."_queries";
        $sql = "INSERT INTO $table (query) VALUES ('$query')";
        $res = $db->query($sql);
    }
    public function getLastTen($database) {
        $db = new database();
        $table = $database."_queries";
        $sql = "SELECT query FROM $table ORDER BY id DESC LIMIT 10";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function submit($download=0) {
        $post = $_POST;
        
        $query = $post["query"];
        $database = $post["database"];
        
        // Initial phase - allow only SELECTS / Strip after any ';' as well.
        $query = trim($query);
        $tokens = explode(' ', $query);
        if (($tokens[0]!="SELECT") && ($tokens[0]!="EXEC")) {
            echo '[]';
            die();
        }
        $query = (strpos($query, ';')) ? substr($query, 0, strpos($query, ';')):$query;
        
        if ($database=="warehouse")     $db = new database();
        elseif ($database=="pollux")    $db = getPolluxConnection();
        else $db = getOtherConnection(strtoupper($database));
        
        $res = $db->query($query);
        $tab = $db->getTable($res);
        
        // Test Sending SMS to self
        /*
        $sms = new sms();
        $sms->setTo("406568714");
        $sms->setMessage("Someone tried to query the $database database...");
        $sms_result = $sms->send();
        */
        
        if (!$download) {
            $jsn = json_encode($tab);
            echo $jsn;
        }
        else {
            $down       = new download();
            $filename   = "/tmp/".time();
            exportResToCSV($filename, $tab);
            $down->output_file($filename, "Query Export.csv", "text/csv");
        }
    }
    
    public function clearSurvey($id) {
        $owners     = array('jbryant','nvillain','nmcnamara','sclapton','avanderstraeten','dbrobby');
        $db         = getOtherConnection('TVPANEL');
        $session    = new session();
        $username   = $session->getKey('username');
        
        if ((in_array($username, $owners)) && ((int)$id)) {
            // continue
            $sql = "DELETE FROM survey_participants WHERE id = $id";
            $res = $db->query($sql);
            //echo "$sql<br/>";
            $sql = "DELETE FROM survey_participants_responses WHERE participant_id = $id";
            //echo "$sql<br/>";
            $res = $db->query($sql);
        }
        else header('HTTP/1.1 401 Unauthorized', true, 401);
    }
}
?>