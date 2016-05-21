<?php
class test extends template {
    public function index() {
        $this->setView('test','_master.php');
    }
    
    public function structure($hhnum) {
        $value = vCsvLookup("/mnt/aussvfp0109/data/systems/Phoenix/import/households.csv", $hhnum, 12);
        echo $value;
    }
}
?>