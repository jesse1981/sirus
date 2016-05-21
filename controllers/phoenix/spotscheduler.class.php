<?php
class spotscheduler extends template {
    private $spots = array();
    private $goldstandard;
    public $access = 100;
    
    public function __construct() {
        $this->goldstandard = new goldstandard();
        $this->setView('spotscheduler', '_master.php');
    }
    
    public function addSpot($file,$from,$to) {
        $this->spots[] = new spot($file,$from,$to);
        return $this->spots;
    }
    public function delSpot($index) {
        if (isset($this->spot[$index])) {
            unset ($this->spots[$index]);
            return $this->spots;
        }
        else return false;
    }
    
    public function getEvents($market,$date,$from,$stations=array(),$to="") {
        /* Return values:
         * -1 - Can't find requierd TEL file
        */
        
       include_once './controllers/tvx.class.php';
        
        $results = array();
        $market_svc = "";
        switch ($market) {
            case "met":
                $market_svc = "OzTAM";
                break;
            case "reg":
                $market_svc = "RegTAM";
                break;
        }
        $directory  = "/mnt/aussvdb0406/public/ProdTSV/Events/media/$market/output/tel/";
        $filename   = $directory."$market_svc-$date".caseFirst($market)."EleEve.tel";
        $handle     = fopen($filename,'r');
        if ($handle) {
            $tvx    = new tvx();
            $from   = $tvx->getTvxTime($date, "$date $from");
            $to     = ($to) ? $tvx->getTvxTime($date, "$date $to"):"";
            
            $buffer = fread($handle,filesize($filename));
            $lines  = explode("/r/n",$buffer);
            foreach ($lines as $l) {
                if (($l!="WITH NET") && ($l)) {
                    $cols = explode(",",$l);
                    if (
                        ((!$stations) || (in_array($cols[1],$stations))) &&
                        ((int)$cols[3] >= (int)$from) &&
                        (($to) && ((int)$cols[4] >= (int)$to))
                    ){
                        $entry = array("station"=>$cols[1],"from"=>$cols[3],"to"=>$cols[4],"event_name"=>$cols[7]);
                        $results[] = $entry;
                    }
                }
            }
            return $results;
        }
        else return -1;
    }
    public function getOfficialReach() {
        $ret = array();
        foreach ($this->spots as $s) {
            array_merge($ret,$this->goldstandard->getSpotViewers($s->date, $s->from_start, $s->from_to));
        }
        $ret = array_unique($ret,SORT_REGULAR);
        
        return $ret;
    }
    public function getOfficialFrequency($index) {
        if (!$file) {
            // treat as posted values
            $post = getPostValues(array("file","from","to"));
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
        }
        
        if (isset($this->spots[$index])) {
            $spot   = $this->spots[$index];
            $ret    = (float)((float)$this->getOfficialTotalTarp()/(float)$this->getOfficialReach($index));
        }
        else return false;
        
        return $ret;
    }
    public function getOfficialTotalTarp() {
        $total = 0;
        
        foreach ($this->spots as $t) {
            $total += (float)$this->goldstandard->getOfficialSpotTarp($t->date,$t->from_stasrt,$t->from_to);
        }
        
        return $total;
    }
}
class spot {
    private $date;
    private $from_start;
    private $from_to;
    
    public function __construct($date,$from,$to) {
        $this->date = $date;
        $this->from_start = $from;
        $this->from_to = $to;
    }
}
?>