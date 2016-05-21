<?php
class jobs extends template {
    public function index() {
        $this->setView('jobs','_master.php');
    }
    public function addJob($exec,$desc) {
        shell_exec("cd /var/www/;php doJobsAdd.php '$exec' '$desc'");
    }
    public function getJobQueue($ret=false) {
        $db = new database();
        $sql = "SELECT * FROM job_queue ORDER BY id ASC LIMIT 1";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
        if ($ret) return $jsn;
    }
    public function setJobPercent($val) {
        $db = new database();
        $sql = "SELECT * FROM job_queue ORDER BY id ASC LIMIT 1";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (count($tab)) {
            $id  = $tab[0]["id"];
            $sql = "UPDATE job_queue SET progress = $val WHERE id = $id";
            $res = $db->query($sql);
            return true;
        }
        else return false;
    }
    public function setJobTitle($val) {
        $db = new database();
        $sql = "SELECT * FROM job_queue ORDER BY id ASC LIMIT 1";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (count($tab)) {
            $id  = $tab[0]["id"];
            $sql = "UPDATE job_queue SET description = '$val' WHERE id = $id";
            $res = $db->query($sql);
            return true;
        }
        else return false;
    }
}
?>