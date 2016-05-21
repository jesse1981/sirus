<?php
class tx4 extends template {
    
    var $base_dir = "/data/agbdata640_local/workstation/workstation.TX4_";
    
    public function index() {
        $this->setView('tx4','_master.php');
    }
    
    // private functions
    private function parseBitString($str,$market,$key) {
        $mapData = $this->getMapData($market, $key);
        $results = array();
        
        if ($mapData) {
            for ($i=0;$i<strlen($str);$i++) {
                $results[$i] = $mapData[$i];
            }
        }
        
        // this all?
        return $results;
    }
    private function buildRegexTimeRange($time_from,$time_to) {
        // Build time Regular Expression
        $post = array("MIN"=>$time_from,"MAX"=>$time_to,"revision"=>"50","btnRun"=>"Go!");
        $data = getPageSource('http://utilitymill.com/utility/Regex_For_Range',$post);
        
        $xml    = @simplexml_import_dom(DOMDocument::loadHTML($data));
        $xpath  = "/html/body/div/div[2]/div[2]/div/div/div/div[2]/textarea";
        
        $node = $xml->xpath($xpath);
        $timeRegex = str_replace("\n", "", (string)$node[0]);
        $timeRegex = str_replace("\r", "", $timeRegex);
        
        return $timeRegex;
    }
    
    // public functions
    public function doSwapCodes($market,$date,$code_from,$code_to,$time_from,$time_to) {
        $ssh = new ssh();
        
        $timeRegex = $this->buildRegexTimeRange($time_from, $time_to);
        
        $regex_find     = "/V($code_from)_(\d)_(\d)_(\d*)_([a-zA-Z]{2})($timeRegex)($timeRegex)/g";
        $regex_replace  = "V".$code_to."_$2_$3_$4_$5$6$7";
        
        $filename = "$date.tx4";
        $downloaded = $ssh->downloadFile($this->base_dir."$market/$filename", "/tmp/$filename");
        
        // swap!
        $handle = fopen("/tmp/$filename", 'r');
        if ($handle) {
            $buffer = fread($handle,  filesize("/tmp/$filename"));
            fclose($handle);
            
            preg_replace($regex_find, $regex_replace, $buffer);
            
            $handle = fopen("/tmp/$filename", 'w');
            if ($handle) {
                fwrite($handle,$buffer);
                
                $uploaded = $ssh->uploadFile("/tmp/$filename","$base_dir.$market/$filename");
            }
        }
    }
    
    public function getMapData($market,$key) {
        $ssh = new ssh();
        $result = array();
        
        // get most recent
        $cmd = "ls MapMks$key.cfg.* -X";
        $ret = $ssh->exec($cmd);
        
        $files = explode("\n",$ret);
        $parts = explode(".",$files[(count($files)-1)]);
        $datestamp = $parts[(count($parts)-1)];
        
        // download file
        $filename = "MapMks$key.cfg.$datestamp";
        $downloaded = $ssh->downloadFile("$base_dir.$market/$filename", "/tmp/");
        
        // parse
        $handle = fopen("/tmp/$filename", 'r');
        if ($handle) {
            $buffer = fread($handle,  filesize("/tmp/$filename"));
            fclose($handle);
            
            $lines = explode("\n", $buffer);
            
            foreach ($lines as $l) {
                // regex for: bit position (starting at 1) | code | val id | val name
                preg_match('/^(\d*)\s*([\d\w]*)\s*(\d{8})\s*([\s\w\.-]*$)/', $l, $matches);
                
                $bit_pos            = ((int)$matches[1]-1);
                $code               = $matches[2];
                $value_id           = $matches[3];
                $value_name         = $matches[4];
                $result[$bit_pos]   = array($code,$value_id,$value_name);
            }
        }
        return $result;
    }
    public function getPanelsViewed($market,$date,$station,$time_from,$time_to) {
        $ssh = new ssh();
        
        $timeRegex = $this->buildRegexTimeRange($time_from, $time_to);
        
        $panelRegex = '/H(\d{7})_([a-z0-9]+)\n([a-zA-Z0-9\._\n]*?)V'.$station.'_\d_\d_\d+(_[a-z]+('.$timeRegex.')('.$timeRegex.'))+.*?([a-zA-Z0-9\._\n]*?)H/';
        
        //echo "<br/>Regular Expression: <i>$panelRegex</i><br/>";
        
        $filename = "$date.tx4";
        $downloaded = $ssh->downloadFile($this->base_dir."$market/$filename", "/tmp/$filename");
        
        //temp till get permissions
        if (true) {
            $handle = fopen("/tmp/$filename", 'r');
            if ($handle) {
                $buffer = fread($handle,  filesize("/tmp/$filename"));
                fclose($handle);
                
                preg_match_all($panelRegex, $buffer, $matches);
                
                return $matches[1];
            }
        }
        else echo "I wasn't able to download the file.";
        
    }
    public function getParsed($market,$date) {
        $handle = fopen($file,'r');
        
        if ($handle) {
            $buffer = fread($handle,  filesize($file));
            $lines  = explode("\n",$buffer);
            
            foreach ($lines as $l) {
                $key    = substr($l,0,1);
                $value  = substr($l,1);
                $value  = explode("_", $value);
                
                switch ($key) {
                    case "H":
                        // household
                        $panel      = $value[0];
                        break;
                    case "R":
                        // region
                        $region_k   = $value[0];
                        $region_v   = $value[1];
                        break;
                    case "W":
                        // weighting factor
                        $hh_weight  = $value[0];
                        break;
                    case "D":
                        // region-dependent demographics
                        // test is empty of these.
                        break;
                    case "M":
                        // member of family
                        $m_code     = $value[0];
                        $m_weight   = $value[2];
                        break;
                    case "G":
                        // guest
                        $g_code     = $value[0];
                        $g_weight   = $value[2];
                        
                        // map data
                        $g_map      = $this->parseBitString($value[1], $market, $key);
                        break;
                    case "T":
                        // tv set
                        $tvset      = $value[0];
                        break;
                    case "V":
                        // viewing
                        $viewing_station        = $value[0];
                        $viewing_station_sub    = $value[1];
                        $viewing_tv_id          = $value[2];
                        $viewing_demographics   = $value[3];
                        break;
                    case "S":
                        // time-shift
                        $shifted_station        = $value[0];
                        $shifted_station_sub    = $value[1];
                        $shifted_tv_id          = $value[2];
                        $shifted_demographics   = $value[3];
                        break;
                }
            }
            
            fclose($handle);
        }
    }
    public function getStations($market,$return=false) {
        $ssh = new ssh();
        $result = array();
        
        // get most recent
        $cmd = "cd ".$this->base_dir.$market.";ls ProgStn.cfg.* -X";
        $ret = $ssh->exec($cmd);
        
        $files = explode("\n",$ret);
        
        // update to ignore files with .old etc at the end
        $fileCount = (count($files)-2);
        
        while (true) {
            $parts = explode(".",$files[$fileCount]);
            $datestamp = $parts[(count($parts)-1)];
            
            if ((int)$datestamp>0) break;
            else $fileCount--;
        }
        
        // download file
        $filename = "ProgStn.cfg.$datestamp";
        $downloaded = $ssh->downloadFile($this->base_dir."$market/$filename", "/tmp/$filename");
        
        if ($downloaded) {
            // parse
            $handle = fopen("/tmp/$filename", 'r');
            if ($handle) {
                $buffer = fread($handle,  filesize("/tmp/$filename"));
                fclose($handle);

                $lines = explode("\n", $buffer);

                foreach ($lines as $l) {
                    $parts = explode(";",$l);
                    
                    if (isset($parts[1])) {
                        $id     = $parts[0];
                        $name   = $parts[1];
                        $code   = $parts[2];
                        $active = $parts[3];
                    }

                    $result[] = array($id,$name,$code,$active);
                }
            }

            if ($return) return $result;
            else echo json_encode($result);
        }
        else echo "Failed to download ".$this->base_dir."$market/$filename";
    }
}
?>