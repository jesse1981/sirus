<?php

class diary extends template {
    public function index() {
        $this->setView('diary','_master.php');
    }
    
    public function add() {
        $data       = $_POST;
        $db         = new database();
        $sess       = new session();
        $date       = date('Y-m-d');
        $user_id    = $sess->getKey('user_id');
        $mood       = $data["mood"];
        
        $sql = "INSERT INTO diary (user_id,date,mood) "
                . "VALUES ($user_id,'$date',$mood) "
                . "ON DUPLICATE KEY UPDATE user_id=$user_id,date='$date'";
        $db->query($sql);
        $id  = $db->getLastId();
        
        $sql = "DELETE "
                . "FROM diary_responses "
                . "WHERE diary_id = $id";
        $db->query($sql);
        
        foreach ($data as $k=>$v) {
            $sql = "INSERT INTO diary_responses (diary_id,key,value) "
                    . "VALUES ($id,'$k','$v')";
            $db->query($sql);
        }
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