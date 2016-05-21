<?php
class xml {
    var $filename;
    var $dataset;
    
    public function __construct($filename) {
        $this->filename = $filename;
        $this->load($this->filename);
    }
    
    // private
    private function load($filename) {
        $this->dataset = simplexml_load_file($filename);
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
    public function getXpath($path,$ns="") {
        $results = array();
        if ($this->dataset) {
            if ($ns) {
                $this->dataset->registerXPathNamespace ("ns",$ns);
                $temp = explode("/",$path);
                $path = "";
                $count = 0;
                foreach ($temp as $p) {
                    if ($count) $path .= "/ns:$p";
                    $count++;
                }
            }
            return $this->dataset->xpath($path);
        }
        else return -1;
    }
}
?>