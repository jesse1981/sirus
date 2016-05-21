<?php

class survey extends template {
    public function index() {
        $this->setView('survey','_master.php');
    }
    
    public function getitem($id) {
        $db = new database();
        $sql = "SELECT * "
                . "FROM objects o "
                . "INNER JOIN types t ON o.type_id = t.id "
                . "WHERE o.id = $id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    public function getitems() {
        $parent_id  = (isset($_POST["parent_id"]))  ? (int)$_POST["parent_id"]:0;
        $group_id   = (isset($_POST["group_id"]))   ? (int)$_POST["group_id"]:0;
        $db = new database();
        $parent = ($group_id) ? "group_id":"parent_id";
        $id     = ($group_id) ? $group_id:$parent_id;
        
        $sql = "SELECT * "
                . "FROM objects o "
                . "INNER JOIN types t ON o.type_id = t.id "
                . "WHERE o.$parent = $id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
}
?>