<?php
class stategen extends template {
    
    public function index($post="") {
        $dash = new dashboard();
        if (!$post) $post = $dash->getPost();
        if (!$post) $this->setView('statements','_master.php');
        else {
            if (!is_array($post)) {
                $post = json_decode($post,true);
            }
            if (!isset($post["export"])) $this->generate($post);
            else $this->exportList($post);
        }
    }
    
    // private
    private function getPanels($post,$filterDates=true,$processAddress=true) {
        $db = getOtherConnection('ELDORADO');
        
        // Load the householder controller
        require_once 'controllers/householder.class.php';
        $hh = new householder();
        
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        $market = (isset($post["market"]))      ? (int)$post["market"]:0;
        $state  = (isset($post["state"]))       ? (int)$post["state"]:0;
        $city   = (isset($post["city"]))        ? (int)$post["state"]:0;
        $from   = (isset($post["date_from"]))   ? (int)$post["date_from"]:"";
        $to     = (isset($post["date_to"]))     ? (int)$post["date_to"]:"";
        $recrui = (isset($post["recruited"]))   ? true:false;

        // Get Min/Max ExternalCode
        $sql = "SELECT * 
                FROM MarketGroups_PanelNo_Ranges
                WHERE       MarketGroupsId = $market
                        AND Disable = 0";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $min = (isset($tab[0])) ? (int)$tab[0]["minvalue"]:0;
        $max = (isset($tab[0])) ? (int)$tab[0]["maxvalue"]:0;

        // Get Panels
        $sql = "SELECT  v.hhnum,
                        v.panelno,
                        v.name,
                        v.title,
                        v.firstname,
                        '' AS address1,
                        '' AS address2,
                        '' AS suburb,
                        '' AS state,
                        '' AS postcode,
                        CONVERT(DATE,v.installdate,103) as installdate,
                        CONVERT(DATE,v.lastdailydate,103) AS Daily_Date,
                        v.dailypointstotal as Daily_Value
                FROM View_Gift_Statement_Data v
                INNER JOIN householder h ON v.hhnum = h.hhnum
                WHERE (lastdailydate IS NOT NULL AND lastdailydate <> '')";

        if ($market)    $sql .= " AND externalcode >= $min AND externalcode <= $max";
        if ($state)     $sql .= " AND stateid = $state";
        if ($city)      $sql .= " AND cityid = $city";
        if (($from) && ($filterDates)) $sql .= " AND installdate >= '$from'";
        if (($to)   && ($filterDates)) $sql .= " AND installdate <= '$to'";
        if ($recrui)    $sql .= " AND h.lastcallstatus = 2 AND v.blocked = 0";
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        // fix the addresses
        if ($processAddress) {
            $count = count($tab);
            $current = 0;
            foreach ($tab as $row=>$col) {
                $current++;
                $percent = (int)((99/$count)*$current);
                $jobs->setJobPercent($percent);

                $address = $hh->getAddressDetail($tab[$row]["panelno"]);
                
                $tab[$row]["address1"]  = $address[0]['address1'];
                $tab[$row]["address2"]  = $address[0]['address2'];
                $tab[$row]["suburb"]    = $address[0]['suburb'];
                $tab[$row]["state"]     = $address[0]['state'];
                $tab[$row]["postcode"]  = $address[0]['postcode'];
            }
        }
        
        return $tab;
    }
    private function generate($post) {
        $db = getOtherConnection('ELDORADO');
        
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        $statement = ((isset($post["type"])) && ($post["type"])) ? $post["type"]:false;
        
        if ($statement) {
            $jobs->setJobTitle("Preparing Panels...");
            $tab = $this->getPanels($post,true,false);
            if ($tab) {
                // set statement filename
                $docx = "/var/www/statements/$statement";
                
                switch ($statement) {
                    case "points.docx":
                        // load the gift controller
                        require_once 'controllers/gifts.class.php';
                        $gift = new gifts();
                        
                        // get the panels array
                        $panels = explode(",", serializeDbField($tab, "panelno"));

                        // generate!
                        $jobs->setJobTitle("Producing Statements...");
                        
                        $date_from  = $post["date_from"];
                        $date_to    = $post["date_to"];
                        $gift->pointStatements($date_from,$date_to,$panels, $docx);
                        break;
                }
            }
            else echo -2; // No results returned!
        }
        else echo -1; // No statement selected!
    }
    private function exportList($post) {
        $db = getOtherConnection('ELDORADO');
        $results = array();
        
        // Load the gifts controller
        require_once 'controllers/gifts.class.php';
        $gifts      = new gifts();
        
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        $date_from  = $post["date_from"];
        $date_to    = $post["date_to"];
        
        $jobs->setJobTitle("Preparing Panels...");
        $hhs = $this->getPanels($post,false);
        
        $hhs = $gifts->pointStatementsData($hhs,$date_from,$date_to);
        $jobs->setJobPercent(100);
        
        // Export
        $filename = "/tmp/stategenList.xls";
        exportResToXLS($filename, $hhs);
        
        // Copy to H:\
        $folder = date("F 'y");
        $pathname = "/mnt/aussvfp0109/data/Panel Dept/Gift Scheme/Brenton/News Brief/Statements/$folder";
        if (!file_exists($pathname)) mkdir($pathname, 0777, true);
        try {
            copy($filename, "$pathname/Export_".date('Y-m-d').".xls");
        }
        catch (Exception $e) {
            // file in use!
        }
        
        // Mail
        $mail = new mailer();
        $mail->attachfile($filename);
        $mail->bodytext("The results for points are attached.  The criteria used were:<br/>From: $date_from<br/>To: $date_to");
        $owners = array("jesse.bryant@nielsen.com");
        $mail->sendmail(serializeArray($owners), "Points Data");
        
        unlink($filename);
    }
    
    // public
    public function getMarkets() {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  id as id,
                        description as description
                FROM MarketGroups";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        array_unshift($tab,array("id"=>0,"description"=>"All"));
        
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getStates() {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  stateid as id,
                        state as description
                FROM state";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        array_unshift($tab,array("id"=>0,"description"=>"All"));
        
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getCities($stateid) {
        $db = getOtherConnection('ELDORADO');
        
        $stateid = ((int)$stateid) ? "= $stateid":">= 0";
        
        $sql = "SELECT  cityid as id,
                        city as description
                FROM city 
                WHERE stateid $stateid";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        array_unshift($tab,array("id"=>0,"description"=>"All"));
        
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function initialize() {
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        // serialize the post data
        $dash = new dashboard();
        $post = $dash->getPost();
        $json = addslashes(addslashes(json_encode($post)));
                
        // add the job
        $jobs->addJob('cd /var/www/;php doStatementGenerator.php "'.$json.'"', "Processing Statement");
        
        echo "Job added successfully.";
    }
}
?>