<?php
class phpinfo extends template {
    public function index() {
        $this->setView('info','_master.php');
    }
}
?>