<?php
class help extends template {
    public function show($module) {
        $this->setView("help", "_help.php");
    }
}
?>