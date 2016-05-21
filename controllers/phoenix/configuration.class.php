<?php
class configuration extends template {
    public $access = 100;
    
    private function writeSettings($settings) {
        $filename = "/var/www/settings.ini";
        $handle = fopen($filename,'w');
        if ($handle) {
            foreach ($settings as $group=>$data) {
                fwrite($handle,"[$group]\r\n");
                foreach ($data as $k=>$v) {
                    $write = ((int)$v) ? "$v":"'$v'";
                    fwrite($handle,"$k=$write\r\n");
                }
                fwrite($handle,"\r\n");
            }
            fclose($handle);
        }
    }
    
    public function index() {
        $this->setView('configuration','_master.php');
    }
    public function update($group) {
        if ($group!="undefined") {
            $settings = parse_ini_file('settings.ini',true);
            $dashboar = new dashboard();
            if ($settings) {
                $post = $dashboar->getPost();
                foreach ($post as $k=>$v) {
                    $settings[$group][$k] = $v;
                }
                $this->writeSettings($settings);
            }
            else return false;
        }
        else return false;
    }
}
?>