<?php
class jcrs extends template {
    
    private $base_dir = "/mnt/aussvfp0109/data/systems/Phoenix/jcrs/";
    private $highligh = array(1,5,7,8,13,14,18,19,21,23,25,27,28,31,33,40,41,45,47,48,53,60,64,65,73,74,75,85,86,87,89,93,94,97,98,99);
    
    public function index() {
        $this->setView('jcrs', "_master.php");
    }
    public function load() {
        $this->setView('jcrs', "_master.php");
    }
    
    // Private Functions
    private function getCity($panel) {
        $return = "Unknown";
        $cities = array("Sydney"=>"100,101,103,104,105,106",
                        "Brisbane"=>"120,121,122,123,124,125,126",
                        "Melbourne"=>"120,121,122,123,124,125,126",
                        "Adelaide"=>"160,161,162,163",
                        "Perth"=>"180,181,182,183,184",
                        "NNSW"=>"201,202,203,204",
                        "SNSW"=>"211,212,213",
                        "QLD"=>"221,222,223,224,225,226",
                        "VIC"=>"241,242,243,244,245",
                        "TAS"=>"291,292");
        $code = substr($panel,0,3);
        foreach ($cities as $k=>$v) {
            $test = explode(",",$v);
            if (in_array($code, $test)) {
                $return = $k;
                break;
            }
        }
        return $return;
    }
    
    // Public Functions
    public function download($filename) {
        $down = new download();
        $down->output_file($this->base_dir.$filename, $filename);
    }
    public function getAllFiles($date) {
        $result = array();
        $files = directoryToArray($this->base_dir, false, true);
        $date = str_replace("-", "", $date);
        
        foreach ($files as $f) {
            // get the acutal filename
            $a = explode("/",$f);
            $a = $a[(count($a)-1)];
            
            // split the name by the dash - or undersocre
            $b = explode("-",$a);
            $x = explode("_",$a);
            if (((isset($b[2])) && ("$date.xls" == $b[2])) ||
                ((isset($x[1])) && ($x[1]==$date))) $result[] = $a;
        }
        
        echo json_encode($result);
    }
    public function getAttachments() {
        $attachments = new attachmentread();
        try {
            $attachments->getdata_imap('{pod51018.outlook.com:143}', 'hhdata.tam.au@nielsen.com', 'Wond3rl@nd', '/mnt/aussvfp0109/data/systems/Phoenix/jcrs/');
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }
    public function getJobcardData($filename) {
        // Update to link to the appointment ID
        require_once ('controllers/apps.class.php');
        $apps = new apps();
        
        $db_pol     = getPolluxConnection();
        $db_ars     = getOtherConnection('ARS');
        $db_eld     = getOtherConnection('ELDORADO');
        
        $fileInfo   = (strpos($filename,"-")) ? explode("-",substr($filename,0,(strlen($filename)-4))):explode("_",substr($filename,0,(strlen($filename)-5)));
        $results    = array();
        
        $panel      = $fileInfo[0];
        $techid     = (strpos($filename,"-")) ? $fileInfo[1]:"0";
        $appdate    = (strpos($filename,"-")) ? $fileInfo[2]:$fileInfo[1];
        
        try {
            $xls = new Spreadsheet_Excel_Reader($this->base_dir.$filename);
        }
        catch (Exception $e) {
            $xls = new Spreadsheet_Excel_Reader();
        }
        
        // Close Codes
        $closecodes = "";
        for ($i=0;$i<10;$i++) {
            if (($closecodes) && ($xls->val(4,(7+$i),1))) $closecodes .= ",";
            $closecodes .= $xls->val(4,(7+$i),1);
        }
        
        // Pollux Fields
        $sql = "SELECT STATUS,CUSTOM8 FROM TFAM WHERE PANEL = $panel";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        $pollux_status  = $tab[0]["STATUS"];
        $panel_type     = ((int)$tab[0]["CUSTOM8"]==2) ? "iPanel":"Live"; 
        
        $sql = "SELECT DETTYPE FROM TECVDET WHERE PANEL = $panel AND VISITDATE = $appdate";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        $pollux_close = serializeDbField($tab, "DETTYPE");
        
        $meter_version  = (float)getSimpleDbVal($db_pol, "TFAM", "MAP", "PANEL", $panel);;
        $meter_type     = getMeterType($meter_version);
        
        // ARS Fields
        $sql = "SELECT name FROM technician WHERE id = $techid";
        $res = $db_ars->query($sql);
        $tab = $db_ars->getTable($res);
        $technician = $tab[0]["name"];
        
        // work out highlight
        $highlight = false;
        foreach (explode(",",$pollux_close) as $p) {
            foreach ($this->highligh as $h) {
                if ((int)$p==(int)$h) {
                    $highlight = true;
                }
            }
        }
        $apptime =  $apps->getAdhocAppDetail(
                        $apps->getAppId(
                                $db_eld, 
                                getSimpleDbVal($db_eld, "householder", "hhnum", "panelno", $panel), 
                                substr($appdate, 0, 4)."-".substr($appdate, 4, 2)."-".substr($appdate, 6, 2)
                        )
                        ,"apptime"
                    );
        $apptime_arr = explode(" ", $apptime);
        $apptime = (isset($apptime_arr[3])) ? $apptime_arr[3]:$apptime;
        $apptime = str_replace(":00:000"," ",$apptime);
        
        $results["appdate"]         = $appdate;
        $results["apptime"]         = $apptime;
        $results["appid"]           = $apps->getAppId($db_eld, getSimpleDbVal($db_eld, "householder", "hhnum", "panelno", $panel), substr($appdate, 0, 4)."-".substr($appdate, 4, 2)."-".substr($appdate, 6, 2));
        $results["panel"]           = $panel;
        $results["pollux_status"]   = $pollux_status;
        $results["technician"]      = $technician;
        $results["pollux_close"]    = $pollux_close;
        $results["panel_type"]      = $panel_type;
        $results["meter_type"]      = $meter_type;
        $results["file_date"]       = date("d/m/Y H:i:s", filectime($this->base_dir.$filename));
        $results["file_name"]       = $filename;
        $results["city"]            = $this->getCity($panel);
        $results["notes"]           = $xls->val(19, 13, 0);
        $results["oc1"]             = $xls->val(11, 2, 0);
        $results["oc2"]             = $xls->val(11, 3, 0);
        $results["jobcard_close"]   = $closecodes;
        
        $results["highlight"]       = ($highlight) ? 'background:#AFA':'';
        
        $json = json_encode($results);
        echo $json;
    }
}
?>