<?php
class sms {
    private $phoneno = "";
    private $message = "";
    
    private $base_uri = "";
    private $base_usr = "";
    private $base_pas = "";
    
    //intMaxSplit = (Len(txtMsg_SMS.Text) / 160) + 1
    //            url = "user=&password=&from=NielsenTAM&to=&text=&maxsplit=" & intMaxSplit
    
    public function __construct() {
        $settings = parse_ini_file('settings.ini',true);
        if (!$settings) return false;
        
        foreach ($settings as $k=>$v) {
            if ($k=="SMS") {
                $this->base_uri = $settings[$k]["BASE_URI"];
                $this->base_usr = $settings[$k]["USER"];
                $this->base_pas = $settings[$k]["PASS"];
            }
        }
        return true;
    }
            
    // Public functions
    public function send() {
        $url = $this->base_uri;
        $max = (strlen($this->message)>160) ? ((strlen($this->message)/160)+1):1;
        
        $url .= "&user="    .$this->base_usr;
        $url .= "&password=".$this->base_pas;
        $url .= "&to=61"    .$this->phoneno;
        $url .= "&text="    .urlencode($this->message);
        $url .= "&from=NielsenTAM";
        $url .= "&maxsplit=$max";
        
        // Send...
        try {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            $content = curl_exec( $ch );
            $response = curl_getinfo( $ch );
            curl_close ( $ch );
            
            if (!strpos(strtoupper($content), "ERROR")) return true;
            else return $content;
        }
        catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function setMessage($msg) {
        $this->message = $msg;
    }
    public function setTo($phoneno) {
        $this->phoneno = $phoneno;
    }
    
    // Private functions
    
}
?>