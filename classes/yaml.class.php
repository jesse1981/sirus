<?php
class yaml {
    var $data = array();
    
    public function __construct($filename="") {
        if ($filename) $this->load($filename);
    }

    // public 
    public function load($filename) {
        $this->data = array();
        
        if (file_exists($filename)) {
            $handle = fopen($filename, 'r');
            if ($handle) {
                $buffer = fread($handle,  filesize($filename));
                fclose ($handle);
                unlink($filename);
    
                // parse
                $lines  = explode("\n", $buffer);
                $key    = "";
                foreach ($lines as $l) {
                    // ignore comments
                    if (substr($l,0,1)!="#") {
                        if ((strlen($l)) && (substr($l,0,2)!="  ")) {
                            $key = substr($l,0,strpos($l,":"));
                        }
                        else if ((strlen($l)) && (substr($l,0,2)=="  ")) {
                            $prop = substr($l,2,(strpos($l,":")-2));
                            $valu = substr($l,(strpos($l,":")+2));
                            $this->data[$key][$prop] = $valu;
                        }
                    }
                }
                if ($this->data) return true;
            }
            else echo "Not able to read file!";
        }
        else echo "File does not exist!";
    }
    public function getData() {
        return $this->data;
    }
}
?>