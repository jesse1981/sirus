<?php
class cron {
    var $crons;
    var $user;
    
    public function __construct($user) {
        $this->user = $user;
        $this->crons = $this->refresh($this->user);
    }
    
    public function refresh($user="root") {
        $res = array();
        $cmd = "crontab -u $user -l";
        $tmp = shell_exec($cmd);
        
        if ($tmp == "") {
            // not enough privilledges to use -u probably, switch to current user.
            $cmd = "crontab -l";
            $tmp = shell_exec($cmd);
        }
        
        $tmp = explode("\n", $tmp);
        foreach ($tmp as $line) {
            if ($line != "") $res[] = $line;
            //if ($line != "") $res[] = $line;
        }
        return $res;
    }
    public function add($cmd,$min="*",$hour="*",$day="*",$month="*",$week="*") {
        $filename = "addcron.txt";
        shell_exec("touch $filename");
        $handle = fopen($filename, 'w');
        if ($handle) {
            fwrite($handle, "$min $hour $day $month $week $cmd");
            shell_exec("crontab $filename");
            fclose($handle);
            unlink($filename);
            
            $this->crons = $this->refresh($this->user);
            return true;
        }
        else return false;
    }
    public function clear($user="root") {
        $this->crons = array();
        shell_exec("crontab -u $user -r");
    }
}
?>
