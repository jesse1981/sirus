<?php
class goldstandard {
    // private
    private function getSpotViewers($file,$from,$to,$includeGuests=true,$demos=array()) {
        $path   = "/mnt/aussvdb0406/public/ProdTSV/Viewing/Output/TAMLPLXA/METSTV/$file";
        $xmln   = "http://www.agbnielsen.com.au/tvx";
        $xml    = simplexml_load_file($path,"SimpleXMLElement",0,"tvx",true);
        $sum    = 0;
        
        $xml->registerXPathNamespace("tvx",$xmln);
        $members    = $xml->xpath("/tvx:ViewingFile/tvx:Viewing/tvx:H/tvx:M/tvx:T/tvx:V/tvx:P[@F >= $from and @T <= $to]");
        if ($includeGuests) {
            $guests     = $xml->xpath("/tvx:ViewingFile/tvx:Viewing/tvx:H/tvx:G/tvx:T/tvx:V/tvx:P[@F >= $from and @T <= $to]");
            $members    = array_merge($members, $guests);
        }
        
        // Remove members which don't match demos
        if (count($demos)) {
            foreach ($members as $k=>$v) {
                $add = true;
                $xml->registerXPathNamespace("tvx",$xmln);
                $attributes = $v->xpath("../../../tvx:A");
                
                foreach ($attributes as $a) {
                    $att = (array)$a->attributes();
                    $id  = $att["@attributes"]["Id"];
                    $val = $a->__toString();
                    if ((!isset($demos[$id])) || ($demos[$id]!=$val)) {
                        $add = false;
                        break;
                    }
                }
                if (!$add) unset ($members[$k]);
            }
        }
        
        return $members;
    }
    
    // public
    public function getAverageIndWeight($path,$membersOnly=true) {
        // To do: add option to be able to unzip TVX file
        
        $result = 0;
        $countr = 0;
        $path   = "/mnt/aussvdb0406/public/ProdTSV/Viewing/Output/TAMLPLXA/METSTV/$path";
        $xmln   = "http://www.agbnielsen.com.au/tvx";
        
        $start = time();
        
        $xml    = simplexml_load_file($path,"SimpleXMLElement",0,"tvx",true);
        
        $finish = time();
        echo "Loading the document took ".($finish-$start)." seconds.<br/>";
        
        $members = $this->getSpotViewers($file, $from, $to);
        
        $start = time();
        foreach ($members as $m) {
            $atts = (array)$m->attributes();
            $result += (float)$atts["@attributes"]["W"];
            $countr++;
        }
        $finish = time();
        echo "Adding the weights took ".($finish-$start)." seconds.<br/><br/>";
        
        $result = ($result / $countr);
        echo "Result is $result";
    }
    public function getOfficialSpotTarp($file="",$from="",$to="",$members=array()) {
        if (!$file) {
            // treat as posted values
            $post = getPostValues(array("file","from","to"));
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
        }
        
        if (!$members) $members = $this->getSpotViewers($file, $from, $to);
        
        foreach ($members as $m) {
            $ind = $m->xpath("../../..");
            $att = (array)$ind[0]->attributes();
            $sum += (float)$att["@attributes"]["W"];
        }
        echo "Sum is $sum\n";
    }
}
?>