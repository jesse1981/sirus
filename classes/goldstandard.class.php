<?php
class goldstandard {
    var $base_dir = "/mnt/aussvdb0406/public/ProdTSV/Viewing/Output/TAMLPLXA/";
    
    // private
    private function getBaseFilename($market,$date,$type,$zip=true) {
        $result = "";
        $date = str_replace("-", "", $date);
        
        switch ($market) {
            case "METSTV":
                $result = ($zip) ? "OzTAM-$date-MetTTVVwg".$type."tvx.zip":"OzTAM-$date-MetTTVVwg$type.tvx";
                break;
        }
        
        return $result;
    }
    
    // public
    public function getSpotViewers($date,$market,$type,$from,$to,$includeGuests=true,$demos=array()) {
        // Check for posted values
        if (isset($_POST["date"])) {
            $post = getPostValues(array("date","market","type","from","to","includeGuests"));
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
            $includeGuests = ($includeGuests=="on") ? true:false;
        }
        
        $file   = $this->getBaseFilename($market, $date, $type);
        $path   = $this->base_dir."$market/$file";
        $xmln   = "http://www.agbnielsen.com.au/tvx";
        
        // Copy to temp, unzip, and load this file
        copy($path, "/tmp/$path");
        $zip    = new zip("/tmp/$path");
        $zip->extract("/tmp");
        $file   = $this->getBaseFilename($market, $date, $type,false);
        $path   = "/tmp/$file";
        
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
    public function getAverageIndWeight($date,$market,$membersOnly=true) {
        // To do: add option to be able to unzip TVX file
        
        $result = 0;
        $countr = 0;
        $path   = $this->base_dir."/mnt/aussvdb0406/public/ProdTSV/Viewing/Output/TAMLPLXA/$market/$date";
        $xmln   = "http://www.agbnielsen.com.au/tvx";
        
        $start = time();
        
        $xml    = simplexml_load_file($path,"SimpleXMLElement",0,"tvx",true);
        
        $finish = time();
        echo "Loading the document took ".($finish-$start)." seconds.<br/>";
        
        $members = $this->getSpotViewers($date, $from, $to);
        
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
    public function getOfficialSpotTarp($date="",$from="",$to="",$members=array()) {
        if (!$date) {
            // treat as posted values
            $post = getPostValues(array("file","from","to"));
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
        }
        
        if (!$members) $members = $this->getSpotViewers($date, $from, $to);
        
        foreach ($members as $m) {
            $ind = $m->xpath("../../..");
            $att = (array)$ind[0]->attributes();
            $sum += (float)$att["@attributes"]["W"];
        }
        echo "Sum is $sum\n";
    }
}
?>