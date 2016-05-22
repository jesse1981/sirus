<?php
class distract extends template {
    public function index() {
        $this->setView('distract','_master.php');
    }
    
    public function game($game) {
        $this->setView('distract/game','_master.php');
    }
}
?>