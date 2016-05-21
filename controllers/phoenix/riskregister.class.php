<?php
class riskregister extends template {
    public function createAudit($data=array(),$db="") {
        if (!is_array($data)) $this->setView('risk/audit/create', '_master_risk.php');
        else {
            // just create and return id 
            // for internal function based 
            // on fields in $data
            $audit_id = $this->getNewId($db, 'isoaudit');
            
            $sql = "INSERT INTO isoaudit (id";
            foreach ($data as $k=>$v) {
                if ($k!="activityid") {
                    $sql .= ",$k";
                }
            }
            $sql .= ") VALUES ($audit_id";
            foreach ($data as $k=>$v) {
                if ($k!="activityid") {
                    $sql .= ",$v";
                }
            }
            $sql .= ")";
            
            $res = $db->query($sql);
            
            $isoaudact_id = $this->getNewId($db, 'isoauditactivity');
            $sql = "INSERT INTO isoauditactivity (id,activityid,auditid) VALUES ($isoaudact_id,".$data["activityid"].",$audit_id)";
            
            $db->query($sql);
            return $audit_id;
        }
    }
    public function displayAudit($auditid) {
        $this->setView('risk/audit/display', '_master_risk.php');
    }
    public function saveAudit($auditid=0) {
        $db     = getOtherConnection('RISKREGISTER_DEV');
        $dash   = new dashboard();
        $sess   = new session();
        $post   = $dash->getPost();
        $ssh    = new ssh("riskdev.agbnielsen.com.au");
        
        // are we editing?
        if (!$auditid) {
        
            // Create the Audit (for first user only!)
            $enabler    = $this->getValueId($db,"activity",$post["enabler"]);
            $date       = $post["date"];
            $title      = $post["title"];
            $user       = $this->getValueId($db,"users",$sess->getKey('username'));;
            $sql        = "INSERT INTO isoaudit (title,auditdate,uploadedby,description) VALUES ('$title','$date',$user,'')";
            $res        = $db->query($sql);

            $auditid    = $db->getLastId("isoaudit");

            // Link to User
            $username   = explode(", ", $post["auditor"]);
            foreach ($username as $k=>$v) {
                $username[$k]   = $this->getValueId($db,"users",$username[$k]);
                $sql            = "INSERT INTO isoaudituser (auditid,userid) VALUES ($auditid,$username[$k])";
                $res        = $db->query($sql);
            }

            // Link to Activity (Enabler)
            $sql        = "INSERT INTO isoauditactivity (auditid,activityid) VALUES ($auditid,$enabler)";
            $res        = $db->query($sql);
        
        }
        
        // get the base html
        $html   = getPageSource(ROOTWEB.'riskregister/createAudit',$post);
        $xml    = @simplexml_import_dom(DOMDocument::loadHTML($html));
        
        // download all (jQuery) scripts
        $js     = $xml->xpath("//script");
        $script  = "";
        $data   = "";
        foreach ($js as $c) {
            if (strpos($c->attributes()->src,"jquery")) {
                if (substr($c->attributes()->src,0,4)=="http") {
                    $data = getPageSource($c->attributes()->src);
                }
                else {
                    $current_dir = getcwd();
                    chdir(realpath(dirname(__FILE__)));
                    $data = file_get_contents("../".$c->attributes()->src);
                    chdir($current_dir);
                }

                if ($data) $script .= $data;
                }
            unset ($c[0]);
        }
        //$xml->head->addChild('script',$script);
        
        // replace images with the base64 encoding
        $img    = $xml->xpath("//img");
        
        foreach ($img as $i) {
            $b = $i->attributes()->src;
            $type = pathinfo($b, PATHINFO_EXTENSION);
            
            $current_dir = getcwd();
            chdir(realpath(dirname(__FILE__)));
            $data = file_get_contents($b);
            chdir($current_dir);
                        
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $i->attributes()->src = $base64;
        }
        
        // download all external stylesheets
        $css    = $xml->xpath("//link");
        $style  = "";
        $data   = "";
        foreach ($css as $c) {
            if (substr($c->attributes()->href,0,4)=="http") {
                $data = getPageSource($c->attributes()->href);
            }
            else {
                $current_dir = getcwd();
                chdir(realpath(dirname(__FILE__)));
                $data = file_get_contents("../".$c->attributes()->href);
                chdir($current_dir);
            }
            
            if ($data) $style .= $data;
            unset ($c[0]);
        }
        $xml->head->addChild('style',$style);
        
        // remove the submit button
        $inp = $xml->xpath("//input");
        foreach ($inp as $i) {
            if ($i->attributes()->type=="submit") unset ($i[0]);
        }
        
        // save back out to html
        $filename   = "audit_".time().".html";
        $handle     = fopen("/tmp/$filename",'w');
        $buffer     = $xml->asXML();
        $buffer     = str_replace("<![CDATA[", "", $buffer);
        $buffer     = str_replace("]]>", "", $buffer);
        $buffer     = str_replace("Â", "&nbsp;", $buffer);
        
        if ($handle) {
            fwrite($handle,'<!DOCTYPE html>'.$buffer);
            
            fclose($handle);
            $xml = null;
            
            // attach the file to the audit
            
            $ssh->exec("mkdir -p /var/www/riskregister/webroot/uploads/$auditid");
            $uploaded = $ssh->uploadFile("/tmp/$filename", "/var/www/riskregister/webroot/uploads/$auditid/$filename");
            if ($uploaded) {
                $sql = "UPDATE isoaudit SET filename = '$filename' WHERE id = $auditid";
                $res = $db->query($sql);
                $db->query($sql);
            }
            unlink("/tmp/$filename");
            
            $dash->redirect("riskregister/displayAudit/$auditid");
        }
    }
    
    // private functions
    private function getJsonVals($sql,$return=false) {
        $dash = new dashboard();
        $get  = $dash->getGet();
        $term = (isset($get["term"])) ? strtoupper($get["term"]):"";
        
        $db  = getOtherConnection('RISKREGISTER_DEV');
        
        $sql = str_replace("#TERM#", $term, $sql);
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $val = explode(",", serializeDbField($tab, "title"));
        $jsn = json_encode($val);
        if ($return) return $tab;
        else echo $jsn;
    }
    private function getValueId($con,$table,$title) {
        $title = strtolower($title);
        if ($table=="users") {
            $sql = "SELECT id 
                    FROM users u
                    WHERE      LOWER(u.firstname || ' ' || u.lastname) = '$title'
                            OR LOWER(u.loginname) = '$title'";
        }
        else {
            $sql = "SELECT id
                    FROM $table
                    WHERE LOWER(title) = '$title'";
        }
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        if ($tab) return (int)$tab[0]["id"];
        else return false;
    }
    private function getNewId($con,$table) {
        $sql = "SELECT MAX(id) as id FROM $table";
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        return ((int)$tab[0]["id"]+1);
    }
    
    // public functions
    public function getAdhocValue($id) {
        $db  = getOtherConnection('RISKREGISTER_DEV');
        
        // Sanitize Post Values
        $dash = new dashboard();
        $post = $dash->getPost();
        $table = $post["table"];
        $field = $post["field"];
        
        $sql = "SELECT $field FROM $table WHERE id = $id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $ret = (isset($tab[0][$field])) ? $tab[0][$field]:false;
        
        return $ret;
    }
    public function getEnablers($return=false) {
        $sql = "SELECT  a.title
                FROM activity a
                WHERE a.title LIKE '#TERM#%'";
        $this->getJsonVals($sql);
    }
    public function getUsers($return=false) {
        $sql = "SELECT  u.firstname || ' ' || u.lastname as title
                FROM users u
                WHERE UPPER((u.firstname || ' ' || u.lastname)) LIKE '#TERM#%'";
        $this->getJsonVals($sql);
    }
    public function getObjectives($return=false) {
        $sql = "SELECT  a.title
                FROM objective a";
        $this->getJsonVals($sql);
    }
    public function getEnablersInObjective($objective) {
        $sql = "SELECT  a.title
                FROM activity a
                INNER JOIN objective b ON a.parentid = b.id
                WHERE       b.title = '$objective'";
        $this->getJsonVals($sql);
    }

    public function importRisks($filename,$tabIgnore=array(),$createParents=true) {
        if (($filename) && (file_exists($filename))) {
            $con        = getOtherConnection('RISKREGISTER_DEV');
            $xls        = new Spreadsheet_Excel_Reader($filename);
            
            $owners = array(
                "PanelCC"       =>  "nvillain",
                "SI"            =>  "tbarnet",
                "D&A"           =>  "afarrugia",
                "Stats"         =>  "afarrugia",
                "MQC"           =>  "afarrugia",
                "CS-SW"         =>  "mfalconer",
                "TVE"           =>  "mfarrugia",
                "Eng"           =>  "afarrugia",
                "Admin"         =>  "lforemann",
                "HR"            =>  "rkirby",
                "Fin"           =>  "twonocott",
                "Sys"           =>  "abini",
                "IT"            =>  "wwalker",
                "Tech-Field-LT" =>  "dwhite",
            );
            
            foreach ($xls->sheets as $k=>$v) {
                echo "Analysing: ".$xls->boundsheets[$k]["name"]."\n";
                
                if (!in_array($xls->boundsheets[$k]["name"], $tabIgnore)) {
                    foreach ($owners as $dept=>$owner) {
                        if ($dept == $xls->boundsheets[$k]["name"]) {
                            $ownerid = $this->getValueId($con, "users", $owner);
                        }
                    }
                    if (!$ownerid) continue;
                    $row = 1;
                    while (true) {
                        $row++;
                        
                        // Find Objective
                        $objective = $xls->value($row, 1, $k);
                        
                        if ($objective) {
                            $objectiveId = $this->getValueId($con, "objective", $objective);
                            
                            if ((!$objectiveId) && ($createParents)) {
                                $objectiveId = $this->getNewId($con, 'objective');
                                $res = $con->query($sql);
                            }
                            else if (!$objectiveId) continue; // Objective was not found, and flagged not to create
                        }
                        else break; // There are no more risks to import on this sheet
                        
                        // Find Enabler
                        $activity = $xls->value($row, 2, $k);
                        if ($activity) {
                            $activityId = $this->getValueId($con, "activity", $activity);
                            if ((!$activityId) && ($createParents)) {
                                $activityId = $this->getNewId($con, 'activity');
                                
                                $sql = "INSERT INTO activity (id,parentid,title) VALUES ($activityId,$objectiveId,'$activity')";
                                $res = $con->query($sql);
                            }
                            else if (!$activityId) continue; // Enabler was not found, and flagged not to create
                        }
                        else continue; // This should never be reached, has an objective but no enabler.
                        
                        // Create Linked Audit
                        $audit_data = array(
                            "activityid"    => $activityId,
                            "title"         => "'[NO TITLE]'",
                            "auditdate"     => "'".date('Y-m-d',time())."'",
                            "uploadedby"    => 0,
                            "filename"      => "'[NO FILE]'",
                            "description"   => "'Audit auto-created for risk: ".$xls->value($row, 3, $k)."'"
                        );
                        $audit_id = $this->createAudit($audit_data,$con);
                        
                        // Link to owner
                        $isoauduse_id = $this->getNewId($con, 'isoaudituser');
                        $sql = "INSERT INTO isoaudituser (id,auditid,userid) VALUES ($isoauduse_id,$audit_id,$ownerid)";
                        $res = $con->query($sql);
                        
                        // Create Risk Score
                        $likelihood     = (int)$xls->value($row, 5, $k);
                        $consequence    = (int)$xls->value($row, 7, $k);
                        $exposure       = (int)$xls->value($row, 9, $k);
                        
                        $score_id = $this->getNewId($con, 'riskscore');
                        $sql = "INSERT INTO riskscore (id,ilikelihood,iconsequence,iexposure) VALUES ($score_id,$likelihood,$consequence,$exposure)";
                        $res = $con->query($sql);
                        
                        // Insert Risk
                        $risk_id = $this->getNewId($con, 'risk');
                        $risk = $xls->value($row, 3, $k);
                        $sql = "INSERT INTO risk (id,parentid,title,status,ownerid,riskscore) VALUES ($risk_id,$activityId,'$risk',1,$ownerid,$score_id)";
                        $res = $con->query($sql);
                        
                        // Link to dummy audit
                        $sql = "INSERT INTO isoauditrisk (auditid,riskid) VALUES ($audit_id,$risk_id)";
                        $con->query($sql);
                    }
                }
            }
        }
        else return false;
    }
}
?>