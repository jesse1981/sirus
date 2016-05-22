<?php

class diary extends template {
    public function index() {
        $this->setView('diary','_master.php');
    }
    
    public function add() {
        
    }
    public function getLastDiary() {
        $db         = new database();
        $sess       = new session();
        $user_id    = $sess->getKey('user_id');
        
        $sql = "SELECT * "
                . "FROM diary "
                . "WHERE user_id = $user_id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $result = (isset($tab[0])) ? $tab[0]:array();
        $jsn    = json_encode($result);
        
        echo $jsn;
    }
}
?>