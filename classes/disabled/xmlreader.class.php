<?php
class xmlreader {
    var $filename;
    var $dataset;
    
    public function __construct($filename) {
        $this->filename = $filename;
        $this->load($this->filename);
    }
    
    // private
    private function load($filename) {
        libxml_use_internal_errors();
        $handle = fopen($filename,'r');
        $buffer = "";
        if ($handle) {
            $buffer = fread($handle,filesize($filename));
            fclose($handle);
        }
        if ($buffer) { 
            $this->dataset = simplexml_load_string($buffer);  
            if ($this->dataset) return true;
            else echo libxml_get_errors();
        }
        else return false;
    }
    
    // public
    public function getAttributes($node) {
        $result = array();
        if ($node) {
            $atts = (array)$node->attributes();
            if (count($atts)) {
                foreach ($atts["@attributes"] as $a=>$b) {
                    $result[$a] = $b;
                }
            }
            return $result;
        }
        else return false;
    }
    public function getChildren($node) {
        if ($node) {
            return $node->children();
        }
        else return false;
    }
    public function getXpath($path) {
        $results = array();
        if ($this->dataset) {
            foreach ($this->xpath($path) as $result) $results[] = $result;
            return $results;
        }
        else return -1;
        return $results;
    }
    public function getXMLAttributesAndChildren($node,$xpath="") {
        $result = array();
        echo "\nxpath = $xpath\n";
        $data = ($xpath) ? $this->dataset->xpath($xpath):$node;
        
        if ((is_array($data)) && (isset($data[0]))) {
            //$data = $data[0];
            //echo "\nGrabbing first element!\n";
        }
        if (!is_object($data)) {
            //$data = $this->dataset;
            //$data = $data[0];
            echo "\nType is: ".gettype($data)."\n";
            echo "";
            var_dump($data);
            die();
        }
        
        //echo "TYPE IS: ".gettype($data)."\n";
        
        if (is_object($data)) {
            echo "\nEntered 1\n";
            foreach ($data->attributes() as $a=>$b) {
                echo "\nEntered 2\n";
                $result["attrs"][$a] = $b;
            }
            foreach ($data->children() as $item) {
                $result["children"][] = $item;
            }
            return $result;
        }
        else return false;
    }
}
?>