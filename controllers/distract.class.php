<?php
class distract extends template {
    public function index() {
        $this->setView('distract','_master.php');
    }
    
    public function game($game) {
        $this->setView('distract/game','_master.php');
    }
    public function youtube($youtube) {
        $this->setView('distract/youtube','_master.php');
    }
    public function spotify($spotify) {
        $this->setView('distract/spotify','_master.php');
    }
}
?>