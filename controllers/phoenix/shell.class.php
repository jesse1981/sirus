<?php
class shell extends template {
    public $access=100;
    
    // Private Functions
    private function exec($cmd,$server="") {
        $server = ($server) ? $server:SSH_HOST;
        $ssh = new ssh($server);
        $res = $ssh->exec($cmd);
        return $res;
    }
    
    // Public Functions
    public function countTPIFromPanel($server="",$market="",$date="") {
        $server = ($server) ? $server:SSH_HOST;
        $market = ($market) ? $market:"National";
        $temp_mktime = mktime(1,1,1,3,10,2014);
        $date_path = ($date)   ? str_replace("-", "/", $date):date('Y/m/d',$temp_mktime);
        $date_TPI = ($date)   ? str_replace("-", "", $date):date('Ymd',$temp_mktime);
        
        $path = "/data/agbdata640_local/National/$date_path/UniTAM/FromPanel";
        
        $cmd    = "cd $path;ls *.$date_TPI";
        $result = $this->exec($cmd, $server);
        $result = explode("\n", $result);
        echo (count($result)-1);
    }
}
?>