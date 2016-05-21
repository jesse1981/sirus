<?php
class explorer extends template {
    public function index() {
        $this->setView('explorer', '_master.php');
    }
    
    public function load($panel) {
        $this->setView('explorer', '_master.php');
    }
}
?>