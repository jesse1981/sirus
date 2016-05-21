<?php
class reprec extends template {
    public function index() {
        $this->setView('reprec', '_master.php');
    }
    
    // private
    private function getAllMeterTypes() {
        return array("TVM5","UNITAM Classic","UNITAM 3");
    }
    private function getAllPanelTypes() {
        return array("Live","Netsight","Test");
    }
    private function getBaseQuery($from,$to,$panel=false) {
        $panel = ($panel) ? ",panelno,hhnum":"";
        $sql = "SELECT  DISTINCT
                        CONVERT(date,appdate,103) as report_date,
                        CASE
                                WHEN panelno >= 1000000 AND panelno < 2000000 THEN 'Metro'
                                WHEN panelno >= 2000000 AND panelnCASEo < 3000000 THEN 'Regional'
                                WHEN panelno >= 3000000 THEN 'Regional WA'
                        END as market,
                        state,
                        city,
                        region,
                        externalcode
                        $panel
                FROM View3 WHERE appdate >= '$from' AND appdate <= '$to'";
        return $sql;
    }
    
    // public
    public function process() {
        $dash = new dashboard();
        $post = $dash->getPost();
        $apps = new apps();
        
        $db_eld = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        
        $date_from  = $post["from_date"];
        $date_to    = $post["to_date"];
        $market     = $post["market"];
        $city       = $post["city"];
        $group_past = $post["group_past"];
        
        // Get all distinct type of records
        $sql = $this->getBaseQuery($date_from,$date_to);
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        $meter_types = $this->getAllMeterTypes();
        $panel_types = $this->getAllPanelTypes();
        
        foreach ($tab as $types) {
            // find all appointments that match this criteria
            $sql =  $this->getBaseQuery($types["appdate"], $types["appdate"], true)
                    ." AND appdate = '"     .   $types["appdate"]."'"
                    ." AND market = '"      .   $types["market"]."'"
                    ." AND state = '"       .   $types["state"]."'"
                    ." AND region = '"      .   $types["region"]."'"
                    ." AND externalcode = '".   $types["externalcode"]."'";
            $res = $db_eld->query($sql);
            $appointments = $db_eld->getTable($res);
            
            foreach ($appointments as $a) {
                $panel = $a["panelno"];
                $hhnum = $a["hhnum"];
                $tasks = $apps->getOpenCodes($hhnum, $types["appdate"]);
                
                $meter_version = (float)getSimpleDbVal($db_pol, "TFAM", "MAP", "PANEL", $panel);
                foreach ($meter_types as $m) {
                    foreach ($panel_types as $p) {
                        if ((getMeterType($meter_version)==$m) && (getPanelType($db_pol, $panel)==$p)) {
                            
                        }
                    }
                }
            }
        }
    }
}
?>