<?php
class householder extends template {
    
    // private
    private function polluxCreateRecord($family,$indviduals,$meters,$units,$appointment) {
        // Create main TFAM page
        $status = "4";
        $surname    = $family["name"];
        $street     = (trim($family["address1"])) ? trim($family["address1"])."/".trim($family["address2"]):trim($family["address2"]);
        $suburb     = $family["suburb"];
        
        
    }
    private function removeDisabled() {
        $db_pol = getPolluxConnection();
        $db_eld = getOtherConnection('ELDORADO_TEST');
        
        $sql = "SELECT PANEL,NAME,BUTTONNO FROM TIND WHERE NAME LIKE '%*%'";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        
        $panels = serializeDbField($tab, "PANEL");
        $member = serializeDbField($tab, "BUTTONNO");
        
        $sql = "SELECT hhnum FROM householder WHERE panelno IN ($panels)";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        $hhnums = serializeDbField($tab, "hhnum");
        
        $sql = "DELETE
                FROM birthdays
                WHERE   hhnum IN ($hhnums)
                        AND memberno IN ($member)";
        $sql = str_replace(",,",",",$sql);
        $sql = str_replace(",,",",",$sql);
        $sql = str_replace(",,",",",$sql);
        $db_eld->query($sql);
    }
    private function removeDisabledMembers($live_status) {
        $db_pol         = getPolluxConnection();
        $db_eld         = getOtherConnection("ELDORADO");
        
        // Get Pollux Data
        $sql = "SELECT  I.PANEL,
                        I.BUTTONNO,
                        F.STATUS
                FROM TIND I
                INNER JOIN TFAM F ON F.PANEL=I.PANEL
                WHERE   I.NAME LIKE '%*%'
                ORDER BY I.PANEL,I.INDID";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        
        foreach ($tab as $item) {
            $panel = $tab["PANEL"];
            $butto = $tab["BUTTONNO"];
            
            $sql = "SELECT h.hhnum 
                    FROM householder h
                    INNER JOIN birthdays b ON h.hhnum = b.hhnum
                    WHERE   h.panelno = $panel
                            AND b.memberno = $butto";
            $res = $db_eld->query($sql);
            $dat = $db_eld->getTable($res);
            
            if ((!isset($dat[0]["hhnum"])) || ((isset($dat[0]["hhnum"])) && (!(int)$dat[0]["hhnum"]))) continue;
            $hhn = $dat[0]["hhnum"];
            
            $sql = "DELETE 
                    FROM birthdays
                    WHERE hhnum = $hhn AND memberno = $butto";
            $res = $db_eld->query($sql);
        }
    }
    
    // public
    public function getAddressDetail($panel,$key="") {
        $result = "";
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  address1,
                        address2,
                        suburb,
                        postcode,
                        state,
                        postaladdressmailouts,
                        postaladdress1,
                        postaladdress2,
                        postalsuburb,
                        postalstate,
                        postalpostcode
                FROM householder
                WHERE panelno = $panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0])) {
            if (((int)trim($tab[0]["postaladdressmailouts"])) &&
                ((trim($tab[0]["postaladdress1"])) || (trim($tab[0]["postaladdress2"]))) &&
                (trim($tab[0]["postalsuburb"])) &&
                (trim($tab[0]["postalstate"])) &&
                (trim($tab[0]["postalpostcode"]))) {
                
                foreach ($tab[0] as $k=>$v) $tab[0][str_replace ("postal", "", $k)] = $tab[0]["postal".str_replace ("postal", "", $k)];
                
                $result = ($key) ? $tab[0]["postal$key"]:$tab;
            }
            else $result = ($key) ? $tab[0][$key]:$tab;
        }
        return $result;
    }
    public function getAllComments($hhnum,$return=false) {
        $comments = array();
        // Experiment with Householder Table first
        echo "Fetching From Householder...\n";
        $db_eld = getOtherConnection('ELDORADO');
        
        $sql = "SELECT comments 
                FROM householder 
                WHERE hhnum = $hhnum";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        if (isset($tab[0])) {
            $buffer = $tab[0]["comments"];
            // Split
            $buffer = explode("\r\n",$buffer);
            // Break down...
            
            $date       = "";
            $time       = "";
            
            $old_date   = "";
            $old_time   = "";
            
            $old_comment = "";
            
            $comment    = "";
            
            $date_matched = false;
            
            foreach ($buffer as $b) {
                if (strlen($b)>5) {
                    
                    // is this a date/time string?
                    preg_match('/(\d{4}-\d{2}-\d{2})  (\d*:\d{2}:\d{2} \wM)/', $b,$matches);
                    if ((isset($matches[0])) && ($date!=$matches[0])) {$date = $matches[1]; $time = $matches[2]; $date_matched = true;}

                    preg_match('/  (\d*\/\d{2}\/\d{4}) (\d*:\d{2}:\d{2} \wM)/', $b,$matches);
                    if (((isset($matches[0])) && ($date!=$matches[0])) && (!$date_matched)) {$date = $matches[1]; $time = $matches[2];}

                    $comment .= htmlentities(str_replace($date, "", $b));
                    $comment = str_replace($time, "", $comment);
                    
                    if ((($old_date != $date) || ($old_time != $time)) && ($old_date)) {
                        $old_date = $date;
                        $old_time = $time;
                        
                        // Normalize values
                        if (strpos($date, "/")) {$dateArr = explode ("/", $date); $yyyy = (int)$dateArr[2]; $mm = $dateArr[1]; $dd = (int)$dateArr[0];}
                        else if ($date)         {$dateArr = explode ("-", $date); $yyyy = (int)$dateArr[0]; $mm = $dateArr[1]; $dd = (int)$dateArr[2];}
                        else continue;
                        
                        $timeArr = explode(":",$time);
                        if (isset($timeArr[1])) {
                            $hh = (int)$timeArr[0];
                            $nn = (int)$timeArr[1];
                            
                            if (strpos($time, "PM")) $hh += 12;
                        }
                        else $nn = 0;
                        
                        $comment_fulldate = mktime($hh,$nn,0,$mm,$dd,$yyyy);
                        
                        $comments[] = array("date"=>date('Y-m-d',$comment_fulldate),"time"=>date('g:i A',$comment_fulldate),"comment"=>$comment,"type"=>'householder',"fulldate"=>$comment_fulldate,"module"=>"","username"=>"");
                        
                        $comment = "";
                        $old_date = "";
                    }
                    else if (!$old_date) $old_date = "1";
                    
                    $date_matched = false;
                }
            }
        }
        // Next get Call Comments
        echo "Fetching From Call Comments...\n";
        $sql = "SELECT  CONVERT(date,c.dateofcall,103) as calldate,
                        CONVERT(varchar(15),CAST(c.timeofcall AS TIME),100) as calltime,
                        u.username,
                        c.comments
                FROM Call_Comments c
                INNER JOIN users u ON u.userID = c.UserId
                INNER JOIN householder h ON h.householderid = c.householderid
                WHERE h.hhnum = $hhnum
                ORDER BY calldate DESC,calltime DESC";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        foreach ($tab as $item) {
            $date = $item["calldate"];
            $time = $item["calltime"];
            
            $dateArr = explode ("-", $date); 
            $yyyy = (int)$dateArr[0]; 
            $mm = $dateArr[1]; 
            $dd = (int)$dateArr[2];
            
            $timeArr = explode(":",$time);
            if (isset($timeArr[1])) {
                $hh = (int)$timeArr[0];
                $nn = (int)substr($timeArr[1],0,2);

                if (strpos($time, "PM")) $hh += 12;
            }
            $comment_fulldate = mktime($hh,$nn,0,$mm,$dd,$yyyy);
            
            
            $comments[] = array("date"=>$date,"time"=>$time,"comment"=>$item["comments"],"type"=>'call_comment',"fulldate"=>$comment_fulldate,"module"=>"","username"=>$item["username"]);
        }
        
        // Finally (?), get Call Status (sigh)
        echo "Fetching From Call Status...\n";
        $sql = "SELECT	CONVERT(date,c.dateofcall,103) as calldate,
                        CONVERT(varchar(15),CAST(c.timeofcall AS TIME),100) as calltime,
                        c.username,
                        c.comments,
                        m.Decription as module
                FROM callstatus c
                INNER JOIN Modules m ON c.callstatus = m.Code
                INNER JOIN householder h ON c.householderid = h.householderid
                WHERE h.hhnum = $hhnum
        ORDER BY calldate DESC,calltime DESC";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        echo "Call Status Query Completed...\n";
        
        foreach ($tab as $item) {
            $date = $item["calldate"];
            $time = $item["calltime"];
            
            $dateArr = explode ("-", $date); 
            $yyyy = (int)$dateArr[0]; 
            $mm = $dateArr[1]; 
            $dd = (int)$dateArr[2];
            
            $timeArr = explode(":",$time);
            if (isset($timeArr[1])) {
                $hh = (int)$timeArr[0];
                $nn = (int)substr($timeArr[1],0,2);

                if (strpos($time, "PM")) $hh += 12;
            }
            $comment_fulldate = mktime($hh,$nn,0,$mm,$dd,$yyyy);
            
            
            $comments[] = array("date"=>$date,"time"=>$time,"comment"=>$item["comments"],"type"=>'call_status',"fulldate"=>$comment_fulldate,"module"=>$item["module"],"username"=>$item["username"]);
        }
        
        // Sort!
        echo "Sorting Comments...\n";
        $comments = sortArray($comments, "fulldate", "number");
        echo "Sorting Completed...\n";
        
        if (!$return) {
            $comments = json_encode($comments);
            echo $comments;
        }
        else return $comments;
    }
    public function getDeviceTimeline($panel) {
        require_once 'controllers/warehouse.class.php';
        require_once 'controllers/apps.class.php';
        
        $results    = array();
        $fields     = array("PC"=>19,"TABLET"=>21,"SMARTPHONE"=>22,"CONNECTION"=>64);
        
        $warehouse  = new warehouse();
        $apps       = new apps();
        
        $db_tvp = getOtherConnection('TVPANEL');
        $db_eld = getOtherConnection('ELDORADO');
        
        // Appointments
        $appointments = $apps->getAllAppointments($panel);
        foreach ($appointments as $a) {
            foreach ($fields as $k=>$v) {
                $value = $warehouse->getAdhocValue($panel, $v, $a["date"]);
                
                if (!isset($results[$a["date"]])) $results[$a["date"]] = array();
                if (!isset($results[$a["date"]]["APPOINTMENTS"])) $results[$a["date"]]["APPOINTMENTS"] = array();
                
                $results[$a["date"]]["APPOINTMENTS"][$k] = $value;
            }
        }
        
        // HUS/HIS
        $sql = "SELECT *
                FROM survey_participants s
                WHERE   survey_id = 1
                        AND panelno = $panel";
        $res = $db_tvp->query($sql);
        $tab = $db_tvp->getTable($res);
        foreach ($tab as $item) {
            foreach ($fields as $k=>$v) {
                $value = $warehouse->getAdhocValue($panel, $v, $item["date"]);
                
                if (!isset($results[$item["date"]])) $results[$item["date"]] = array();
                if (!isset($results[$item["date"]]["HUS_SURVEY"])) $results[$item["date"]]["HUS_SURVEY"] = array();
                
                $results[$item["date"]]["HUS_SURVEY"][$k] = $value;
            }
        }
        
        // IC/CA
        $sql = "SELECT CONVERT(DATE,intrfinishdate,103) as date,s.*
                FROM surveyquestions s
                INNER JOIN householder h ON s.hhID = h.householderid
                WHERE h.panelno = $panel";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        foreach ($tab as $item) {
            foreach ($fields as $k=>$v) {
                $value = $warehouse->getAdhocValue($panel, $v, $item["date"]);
                
                if (!isset($results[$item["date"]])) $results[$item["date"]] = array();
                if (!isset($results[$item["date"]]["ICCA"])) $results[$item["date"]]["ICCA"] = array();
                
                $results[$item["date"]]["ICCA"][$k] = $value;
            }
        }
        
        return $results;
    }
    public function getDeviceTimelineFromSource($panel) {
        require_once 'controllers/apps.class.php';
        require_once 'controllers/warehouse.class.php';
        
        $results    = array();
        $apps       = new apps();
        $warehouse  = new warehouse();
        
        
        $db_tvp = getOtherConnection('TVPANEL');
        $db_eld = getOtherConnection('ELDORADO');
        
        // Appointments
        $appointments   = $apps->getAllAppointments($panel);
        $fields         = array("PC"=>19,"TABLET"=>21,"MOBILE"=>0,"SMARTPHONE"=>22,"CONNECTION"=>10);
        foreach ($appointments as $a) {
            if ((int)str_replace("-","",$a["date"]) < 20140101) continue;
            if (!isset($results[$a["date"]])) $results[$a["date"]] = array();
            if (!isset($results[$a["date"]]["APPOINTMENTS"])) $results[$a["date"]]["APPOINTMENTS"] = array();
            
            foreach ($fields as $k=>$v) {
                try {
                    $value = ($v) ? $warehouse->getAdhocValue($panel, $v, $a["date"]):"";
                }
                catch (Exception $e) {
                    echo "Unable to get definition adhoc for panel: $panel (".$a["date"].")";
                }
                
                $results[$a["date"]]["APPOINTMENTS"][$k] = $value;
            }
        }
        
        // HUS/HIS
        $sql = "SELECT *
                FROM survey_participants s
                WHERE   survey_id = 1
                        AND panelno = $panel";
        $res = $db_tvp->query($sql);
        $tab = $db_tvp->getTable($res);
        $fields = array("PC"=>1457,"TABLET"=>1461,"MOBILE"=>1466,"SMARTPHONE"=>1467,"CONNECTION"=>1473);
        foreach ($tab as $item) {
            if (!isset($results[$item["date"]])) $results[$item["date"]] = array();
            if (!isset($results[$item["date"]]["HUS_SURVEY"])) $results[$item["date"]]["HUS_SURVEY"] = array();
            $participant_id = $item["id"];
            
            foreach ($fields as $k=>$v) {
                $sql = "SELECT  v.value as value,
                                r.value_literal as value_literal
                        FROM survey_participants_responses r
                        LEFT JOIN answer_values v ON r.value_id = v.id
                        WHERE       r.participant_id = $participant_id
                                AND r.answer_id = $v";
                $res = $db_tvp->query($sql);
                $tab_par = $db_tvp->getTable($res);
                $val = ((int)$tab_par[0]["value"]) ? $tab_par[0]["value"]:$tab_par[0]["value_literal"];
                
                $results[$item["date"]]["HUS_SURVEY"][$k] = $val;
            }
        }
        
        // IC/CA
        $sql = "SELECT CONVERT(DATE,intrfinishdate,103) as date,s.*
                FROM surveyquestions s
                INNER JOIN householder h ON s.hhID = h.householderid
                WHERE h.panelno = $panel";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        $fields = array("PC"=>"q5bTxt","TABLET"=>"HowManyTablet","MOBILE"=>"","SMARTPHONE"=>"HowManySmartPhone","CONNECTION"=>"");
        foreach ($tab as $item) {
            if (!isset($results[$item["date"]])) $results[$item["date"]] = array();
            if (!isset($results[$item["date"]]["ICCA"])) $results[$item["date"]]["ICCA"] = array();
            
            foreach ($fields as $k=>$v) {
                $value = ($v) ? $item[$v]:"";
                
                $results[$item["date"]]["ICCA"][$k] = $value;
            }
        }
        
        return $results;
    }
    public function getHouseholder($panel) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  h.*,
                        c.city
                FROM householder h
                INNER JOIN region r ON r.regionid = h.regionid
                INNER JOIN city c ON c.cityid = r.cityid
                WHERE panelno = $panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getLatLon($panel=0) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  c.hhnum,
                        c.latitude as lat,
                        c.longitude as lon,
                        h.panelno
                FROM householder_coordinates c
                LEFT JOIN householder h ON c.hhnum = h.hhnum";
        if ($panel) $sql .= " WHERE h.panelno = $panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function setLatLon($hhnum="") {
        $base_url = "http://nominatim.openstreetmap.org/search?";
        $base_keys = array("street","suburb","city","state","postcode");
        
        $google_base_url = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=";
        
        $db = getOtherConnection('ELDORADO');
        
        if ($hhnum) {
            $dash = new dashboard();
            $post = $dash->getPost();
            $lati = $post["latitude"];
            $long = $post["longitude"];
            
            $sql = "SELECT * FROM householder_coordinates WHERE hhnum = $hhnum";
            $res = $db->query($sql);
            $num = $db->getNumRows($res);
            if ($num)   $sql = "UPDATE householder_coordinates SET latitude = $lati, longittude = $long WHERE hhnum = $hhnum";
            else        $sql = "INSERT INTO householder_coordinates (hhnum,latitude,longitude) VALUES ($hhnum,$lati,$long)";
            $db->query($sql);
        }
        else {
            $sql = "SELECT  h.hhnum,
                            a.latitude,
                            a.longitude,
                            h.address2,
                            h.suburb,
                            c.city,
                            h.state,
                            h.postcode
                    FROM householder h
                    INNER JOIN region r ON r.regionid = h.regionid
                    INNER JOIN city c ON c.cityid = r.cityid
                    LEFT JOIN householder_coordinates a ON a.hhnum = h.hhnum
                    WHERE lastcallstatus = 2";
            $res = $db->query($sql);
            $tab = $db->getTable($res);

            $results = array();

            foreach ($tab as $row) {
                $hhnum = $row["hhnum"];

                echo "Processing $hhnum...";

                if (!$row["latitude"]) {
                    // Attempt via OpenMaps...
                    $url = $base_url;
                    foreach ($base_keys as $b) {
                        if ($b=="street")   $url .= "street=".$row["address2"];
                        else                $url .= "&$b=".$row[$b];
                    }
                    $url .= "&format=json";
                    $coords = getPageSource($url);

                    var_dump($coords);
                    die();

                    $coords = json_decode($coords);
                    if (isset($coords[0]["lat"])) {
                        echo "\n";
                        $lat = $coords[0]["lat"];
                        $lon = $coords[0]["lon"];

                        $sql = "UPDATE householder_coordinates SET latitude = $lat, longitude = $lon WHERE hhnum = $hhnum";
                        $res = $db->query($sql);

                        $results[] = array("hhnum"=>$hhnum,"lat"=>$lat,"lon"=>$lon);
                    }
                    else {
                        // Attempt now to get through google engine...
                        $url = $google_base_url;
                        foreach ($base_keys as $b) {
                            if ($b=="street")   $url .= $row["address2"];
                            else                $url .= "+".$row[$b];
                        }
                        $coords = getPageSource($url);
                        $coords = json_decode($coords);
                        if (isset($coords["results"]["geometry"]["location"]["lng"])) {
                            echo "\n";
                            $lat = $coords["results"]["geometry"]["location"]["lat"];
                            $lon = $coords["results"]["geometry"]["location"]["lng"];

                            $sql = "UPDATE householder_coordinates SET latitude = $lat, longitude = $lon WHERE hhnum = $hhnum";
                            $res = $db->query($sql);

                            $results[] = array("hhnum"=>$hhnum,"lat"=>$lat,"lon"=>$lon);
                        }
                        else {
                            $results[] = array("hhnum"=>$hhnum,"lat"=>0,"lon"=>0);
                            echo "Failed ($url).\n";
                        }
                    }
                }
                else {
                    echo "\n";
                    $results[] = array("hhnum"=>$hhnum,"lat"=>$row["latitude"],"lon"=>$row["longitude"]);
                }
            }
        }
        echo "Complete.";
    }
    public function getMembers($panel) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT b.* 
                FROM birthdays b
                INNER JOIN householder h ON b.hhnum = h.hhnum
                WHERE h.panelno = $panel
                ORDER BY b.memberno";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    
    public function getPanCode($postcode) {
        $xls_filename = "/mnt/aussvfp0109/data/Statistics/Postcodes/National Postcodes.xls";
        $data = importXlsToArray($xls_filename);
        $pancode = 0;
        
        foreach ($data as $row) {
            if (((int)$row[0]) && ((int)$row[0] == (int)$postcode)) {
                $pancode = $row[1];
                break;
            }
        }
        return $pancode;
    }
    public function getNewPanelNumer($pancode,$panelType=1,$panel_list=array()) {
        switch ($panelType) {
            case 18:
                $pollux = "TPLXP";
                break;
            default:
                $pollux = "LPLXA";
                break;
        }
        $db = getPolluxConnection($pollux);
        $check_pollux = true;
        
        foreach ($panel_list as $p) {
            if (((int)$p>=(int)((string)$pancode."0000")) && ((int)$p<=(int)((string)$pancode."9989"))) {
                // avoid duplicate
                $check_pollux = false;
                $panel = (int)$p;
                while (true) {
                    $panel++;
                    if (!in_array($panel, $panel_list)) break;
                }
            }
        }
        if ($check_pollux) {
            for($i=9989;$i>=1;$i--) {
                $i_string = str_repeat("0", (4-strlen((string)$i))).$i;
                $panel = (int)((string)$pancode.$i_string);
                $sql = "SELECT PANEL FROM TFAM WHERE PANEL=$panel";
                $res = $db->query($sql);
                $tab = $db->getTable($res);
                if ((isset($tab[0]["PANEL"])) && ((int)$tab[0]["PANEL"])) break;
            }
        
            $i_string   = str_repeat("0", (4-strlen((string)$i))).($i+1);
            $panel      = (int)((string)$pancode.$i_string);
        }
        return $panel;
    }
    public function getRecruited() {
        $yesterday  = date('Y-m-d',strtotime("-1 day"));
        $today      = date('Y-m-d');
        
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT * 
                FROM (
                    SELECT 
                        DISTINCT 
                            hhnum,
                            panelno,
                            s1.statusdesc as TVStatus,
                            s2.statusdesc as ConvStatus,
                            name,
                            title,
                            firstname,
                            address1,
                            address2,
                            suburb,
                            postcode,
                            h.phoneno,
                            h.alternatephone,
                            state,
                            (
                                SELECT COUNT(*)
                                FROM appointmenttasks at
                                INNER JOIN task t ON at.taskid = t.id
                                WHERE       at.appid = a.appid
                                        AND t.taskcode = 99
                            ) as is_install,
                            (
                                SELECT COUNT(*)
                                FROM appointment a1
                                INNER JOIN appointmenttasks at1 ON at1.appid = a1.appid
                                INNER JOIN task t ON at1.taskid = t.id
                                WHERE       a1.householderid = h.householderid
                                        AND t.taskcode = 99
                            ) as install_count,
                            a.appdate,
                            h.athome as NumberPeople,
                            h.tv as NumberTvs,
                            h.paytv as PayTVType
                    FROM householder h
                    LEFT JOIN appointment a ON h.householderid = a.householderid
                    LEFT JOIN Call_Comments c ON h.householderid = c.householderid
                    INNER JOIN status s1 ON h.lastcallstatus = s1.statusid
                    INNER JOIN status s2 ON h.conv_lastcallstatus = s2.statusid
                    WHERE   (    
                                (
                                        h.qstartdatetime >= '$yesterday'
                                    AND h.qstartdatetime <  '$today'
                                ) OR
                                (
                                        c.dateofcall >= '$yesterday'
                                    AND c.dateofcall < '$today'
                                )
                            )
                            AND h.questionnaire = 'Y'
                            AND h.panelno IS NULL
                ) as m
                WHERE       m.is_install = 1
                        AND (
                            m.TVStatus = 'Recruit Household' OR
                            m.ConvStatus = 'CP - Recruit HH'
                        )";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        return $tab;
    }
    
    public function getStructure($hhnum,$return = false) {
        $result = "";
        $basepath = '/mnt/aussvfp0109/data/Statistics/Recruitment/ES Info/Structure.csv';
        
        $result = vCsvLookup($basepath, $hhnum, 2);
        
        if ($return) return $result;
        else echo $result;
    }
    public function synchMembers($hard_reset = false) {
        $db_pol         = getPolluxConnection();
        $db_eld         = getOtherConnection("ELDORADO");
        
        $live_status    = array('5.0','5.1','5.2','5.3','5.4','5.5','5.6','5.7','5.8','5.9','65V','90A');
        $live_sec       = array('65V'=>'704','65V'=>'790','90A'=>'130');
        $panels         = array();
        $filename       = "/tmp/doSynchMembers.csv";
        
        // Remove Disabled Members First
        $this->removeDisabledMembers();
        
        // Updated method: Remove the removed members first, filtering for those with the *
        echo "Finding removed members...\n";
        $sql = "SELECT  I.PANEL,
                        I.BUTTONNO,
                        I.NAME,
                        I.BIRTHDATE,
                        F.STATUS
                FROM TIND I
                INNER JOIN TFAM F ON F.PANEL=I.PANEL
                WHERE   F.STATUS IN ('".  implode("','", $live_status)."')
                        AND I.NAME LIKE '%*%'
                ORDER BY I.PANEL,I.INDID";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        
        foreach ($tab as $item) {
            $panel = $item["PANEL"];
            $membe = $item["BUTTONNO"];
            
            $sql = "DELETE
                    FROM birthdays
                    WHERE   memberno = $membe
                            AND hhnum IN (
                                SELECT hhnum
                                FROM householder
                                WHERE panelno = $panel
                            )";
            $db_eld->query($sql);
        }
        
        // Collect from Pollux
        echo "Getting Pollux Data...\n";
        $sql = "SELECT  I.PANEL,
                        I.BUTTONNO,
                        I.NAME,
                        I.BIRTHDATE,
                        F.STATUS
                FROM TIND I
                INNER JOIN TFAM F ON F.PANEL=I.PANEL
                WHERE   F.STATUS IN ('".  implode("','", $live_status)."')
                        AND I.NAME NOT LIKE '%*%'
                ORDER BY I.PANEL,I.INDID";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        
        // Sort
        echo "Sorting...\n";
        $data_pol = array();
        foreach ($tab as $item) {
            // Resolve if match secondary status condition
            $continue = true;
            if (in_array($item["STATUS"],$live_sec)) {
                $continue = false;
                foreach ($live_sec as $k=>$v) {
                    if (($item["STATUS"]==$k) && ($item["SECSTATUS"]==$v)) {
                        $continue = true;
                        break;
                    }
                }
            }
            
            if ($continue) {
                if ((int)$item["PANEL"]) $panels[] = $item["PANEL"];
                else continue;
                if (!isset($data_pol[$item["PANEL"]]))                     $data_pol[$item["PANEL"]] = array();
                if (!isset($data_pol[$item["PANEL"]][$item["BUTTONNO"]]))  $data_pol[$item["PANEL"]][$item["BUTTONNO"]] = array();
                $data_pol[$item["PANEL"]][$item["BUTTONNO"]]["NAME"]       = $item["NAME"];
                $data_pol[$item["PANEL"]][$item["BUTTONNO"]]["BIRTHDATE"]  = $item["BIRTHDATE"];
            }
        }
        
        if ($hard_reset) {
            $sql = "TRUNCATE birthdays";
            $db_eld->query($sql);
        }
        
        // Collect from Eldorado
        echo "Getting Eldorado Data...\n";
        
        $sql = "SELECT b.*, h.panelno
                FROM birthdays b 
                INNER JOIN householder h ON h.hhnum=b.hhnum
                --WHERE h.panelno IN (". serializeArray($panels).")";
        
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        // Sort
        echo "Sorting...\n";
        $data_eld = array();
        foreach ($tab as $item) {
            if (!isset($data_eld[$item["panelno"]]))                    $data_eld[$item["panelno"]] = array();
            $data_eld[$item["panelno"]]["hhnum"]                        = $item["hhnum"];
            
            if (!isset($data_eld[$item["panelno"]][$item["memberno"]])) $data_eld[$item["panelno"]][$item["memberno"]] = array();
            $data_eld[$item["panelno"]][$item["memberno"]]["firstname"] = $item["firstname"];
            $data_eld[$item["panelno"]][$item["memberno"]]["surname"]   = $item["surname"];
            $data_eld[$item["panelno"]][$item["memberno"]]["birthday"]  = $item["birthday"];
        }
        
        // Synch
        $total_count    = 0;
        $delete_count   = 0;
        $insert_count   = 0;
        $update_count   = 0;
        
        $handle         = fopen($filename,'w');
        if (is_resource($handle)) fwrite($handle,"panel,change,firstname,surname,birthday,memberno,old_birthday,old_name");
        
        echo "Synching...\n";
        foreach ($data_pol as $panel=>$v1) {
                        
            $total_count++;

            echo "Processing $panel...\n";
            
            $hhn = ((isset($data_eld[$panel]["hhnum"])) && ((double)$data_eld[$panel]["hhnum"])) ? $data_eld[$panel]["hhnum"]:false;
            
            // No members for this household?
            if (!isset($data_eld[$panel])) {
                $sql = "SELECT hhnum FROM householder WHERE panelno = $panel";
                $res = $db_eld->query($sql);
                $tab = $db_eld->getTable($res);
                $hhn = (isset($tab[0])) ? $tab[0]["hhnum"]:false;
                if ($hhn) {
                    // fill the eldo data array with just the panel number.
                    $data_eld[$panel] = array();
                }
            }
            
            foreach ($v1 as $indid=>$v2) {
                
                if ($indid) {

                    $name   = explode(" ", $v2["NAME"]);
                    $fname  = str_replace("'", "''", $name[0]);
                    $sname  = (isset($name[1])) ? str_replace("'", "''", $name[1]):"";

                    $yyyy   = substr((string)$v2["BIRTHDATE"],0,4);
                    $mm     = substr((string)$v2["BIRTHDATE"],4,2);
                    $dd     = substr((string)$v2["BIRTHDATE"],6,2);
                    
                    $birthday   = "$yyyy-$mm-$dd";
                    
                    if ((strpos($v2["NAME"], "*")!==false) && ($hhn)) {
                        // Should never hit this now!
                        $delete_count++;
                        
                        $sql = "DELETE 
                                FROM birthdays 
                                WHERE hhnum = $hhn
                                AND memberno = $indid";
                        $res = $db_eld->query($sql);
                        
                        if (is_resource($handle)) fwrite($handle,"\r\n$panel,DELETE,$fname,$sname,$birthday,$indid,,");
                    }

                    else if ((!isset($data_eld[$panel][$indid])) && ($hhn)) {
                        $insert_count++;

                        $sql = "INSERT INTO birthdays (hhnum,firstname,surname,birthday,memberno) VALUES (
                                    $hhn,
                                    '$fname',
                                    '$sname',
                                    '$birthday',
                                    $indid
                                )";
                        $res = $db_eld->query($sql);
                        
                        if (is_resource($handle)) fwrite($handle,"\r\n$panel,INSERT,$fname,$sname,$birthday,$indid,,");
                    }
                    // else do the details match?
                    else if ($hhn) {
                        
                        
                        $birthday_old = date('Y-m-d',strtotime($data_eld[$panel][$indid]["birthday"]));
                        $name_old = $data_eld[$panel][$indid]["firstname"]." ".$data_eld[$panel][$indid]["surname"];
                        
                        if (($birthday_old  !=  $birthday) || 
                            ($data_eld[$panel][$indid]["firstname"] !=  $name[0]) ||
                            ((isset($name[1])) && (trim($name[1])) && ($data_eld[$panel][$indid]["surname"] != $name[1]))
                        ) {
                            // update
                            $update_count++;

                            $sql = "UPDATE birthdays 
                                    SET birthday='$birthday', 
                                        firstname = '$fname', 
                                        surname = '$sname' 
                                    WHERE hhnum = $hhn AND memberno = $indid";
                            $res = $db_eld->query($sql);
                            
                            if (is_resource($handle)) fwrite($handle,"\r\n$panel,UPDATE,$fname,$sname,$birthday,$indid,$birthday_old,$name_old");
                            
                            echo "\n=======================================================================\n";
                            if ($birthday_old  !=  $birthday) {
                                echo "Birthdays\n";
                                echo "Old: $birthday_old\n";
                                echo "New: $birthday\n";
                            }
                            if ($data_eld[$panel][$indid]["firstname"]." ".$data_eld[$panel][$indid]["surname"] != "$fname $sname") {
                                echo "Names\n";
                                echo "Old: ".$data_eld[$panel][$indid]["firstname"]." ".$data_eld[$panel][$indid]["surname"]."\n";
                                echo "New: ".$name[0]." ";
                                if (isset($name[1])) echo $name[1];
                            }
                            echo "\n";
                        }
                    }
                }
            }
            // DELETE anything higher than the last indid used.
            if ($hhn) {
                $sql = "DELETE FROM birthdays WHERE hhnum = $hhn AND memberno > $indid";
                $res = $db_eld->query($sql);
            }
        }
        echo "\n$total_count households processed";
        echo "\n$delete_count individuals deleted";
        echo "\n$insert_count individuals inserted";
        echo "\n$update_count individuals updated";
        
        if ($handle) fclose($handle);
        
        $mail       = new mailer();
        $to         = "nathalie.villain@nielsen.com,nick.mcnamara@nielsen.com,jesse.bryant@nielsen.com";
        //$to         = "jesse.bryant@nielsen.com";
        $subject    = "Synch Members Report";
        $message    = "Your report is attached.";

        $mail->attachfile($filename);
        $mail->bodytext($message);
        $mail->sendmail($to, $subject);
        unlink($filename);
    }
    public function getOverdueApps() {
        $db_eld = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        $mailer = new mailer();
        
        $file = "/tmp/overdue.txt";
        $handle = fopen($file,'w');
        if (!$handle) die("Unable to open output.\n");
        
        $sql = "SELECT DISTINCT panelno
                FROM householder h
                WHERE h.householderid NOT IN (
                        SELECT householderid
                        FROM appointment
                        WHERE appdate > GETDATE()-360
                )
                AND h.lastcallstatus = 2";
        $res = $db_eld->query($sql);
        $eld = $db_eld->getTable($res);
        
        $sql = "SELECT PANEL
                FROM TTECVREQ
                WHERE VISITDATE = 0";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        $panels = explode(",",serializeDbField($tab, "PANEL"));
        
        foreach ($eld as $item) {
            if (!in_array($item["panelno"], $panels)) {
                fwrite($handle,$item["panelno"]."\r\n");
            }
        }
        fclose($handle);
        
        $mailer->attachfile($file);
        $mailer->bodytext("Attached is panels overdue for an appointment");
        $mailer->sendmail("jesse.bryant@nielsen.com", "Overdue Panels");
        
        unlink($file);
    }
    public function getCurrentHouseholds() {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT  h.panelno,
                        h.hhnum,
                        h.address1,
                        h.address2,
                        c.city,
                        h.suburb,
                        h.state,
                        h.postcode
                FROM householder h
                LEFT JOIN region r ON r.regionid = h.regionid
                LEFT JOIN city c ON r.cityid=c.cityid
                WHERE   h.lastcallstatus = 2
                        AND h.hhnum NOT IN (SELECT hhnum FROM householder_coordinates)";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    
    public function searchComments($key) {
        $results = array();
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT hhnum
                FROM householder
                WHERE lastcallstatus = 2";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        foreach ($tab as $item) {
            $hhnum = $item["hhnum"];
            echo "Processing $hhnum...\n";
            $comments = $this->getAllComments($hhnum, true);
            foreach ($comments as $c) {
                if (strpos($c["comment"], $key)!==false) {
                    $c["hhnum"] = $hhnum;
                    $c["comment"] = str_replace("\r\n", "", $c["comment"]);
                    $results[] = $c;
                }
            }
        }
        if ($results) {
            $filename = 'searchResult.csv';
            $handle = fopen("/tmp/$filename",'w');
            if ($handle) {
                fwrite($handle,"hhnum|comment|date|time");
                foreach ($results as $r) {
                    fwrite($handle,"\r\n".$r["hhnum"]."|".$r["comment"]."|".$r["date"]."|".$r["time"]);
                }
            }
            fclose($handle);
            copy("/tmp/$filename","/mnt/aussvfp0109/data/transfer/$filename");
            unlink("/tmp/$filename");
            echo "Done.";
        }
        else echo "There was no results.";
    }
    
    #This script is importing batch from panel website to Eldorado for HUS module in Eldorado
    #Developer: Akhtar Jahan
    #
    Public Function ImportSurveyBatch_HUS(){
        $dbEldo=  getOtherConnection("ELDORADO_TEST");
        $dbPanel=  getOtherConnection("TVPANEL_DEV");
        #Extract from panel
        $sql="Select distinct batch_name from survey_batch";
        $res_panel=$dbPanel->query($sql);
        $tab_panel1=$dbPanel->getTable($res_panel);
        #Xtract from Eldorado
        
        foreach ($tab_panel1 as $row){
            #$panel = $row["panel"];
            $batch_name1 = $row["batch_name"];
            #echo "\n$panel Panel processed"."$batch_name1 Batch processed";
            #Check batch imported in Eldorado
            $sql="Select batch_name from Batch_HUS where batch_name='$batch_name1'";
            $res_eldo=$dbEldo->query($sql);
            $num = $dbEldo->getNumRows($res_eldo);
            if ($num)   echo "Batch exists";
            else {       
            
                #select batch and import into eldo
                $sql2="Select batch_name,panel from survey_batch where batch_name='$batch_name1'";
                $res_panel2=$dbPanel->query($sql2);
                $tab_panel2=$dbPanel->getTable($res_panel2);
                
                foreach ($tab_panel2 as $row){
                    $panel2 = $row["panel"];
                    $batch_name2 = $row["batch_name"];               
                    $sql="Insert into Batch_HUS(Panel,Batch_name) values($panel2,'$batch_name2')";
                    $dbEldo->query($sql);
                }
            }
        }
    }
    Public Function ImportSurveyReceived_HUS() {
        $dbEldo=  getOtherConnection("ELDORADO_TEST");
        $dbPanel=  getOtherConnection("TVPANEL_DEV");
        #Extract from panel database
        $sql = "Select    survey_participants.*,
                        origin.originname,
                        origin.id 
                from survey_participants 
                inner join origin on survey_participants.origin=origin.id 
                where       survey_participants.survey_id=1 
                        and origin.id in(0,1,2,3,4)"
         # ." and panelno=2221370";     
                ;
        $res_panel=$dbPanel->query($sql);
        $tab_panel1=$dbPanel->getTable($res_panel);
               
        foreach ($tab_panel1 as $row){
            $panel      = $row["panelno"];
            $originname = $row["originname"];
            $originid   = $row["id"];
            
            echo $row["panelno"];
            echo "\n";
            echo $row["received"];
            
            $received='NULL';
            switch ($originid) {
                case 0:
                    #list($y, $m, $d) = explode("-", $row["received"]);

                    if(strtotime($row["received"])) {
                        if (substr((string)$row["received"],0,4)<>'0000') {

                            $yyyy   = substr((string)$row["received"],0,4);
                            $mm     = substr((string)$row["received"],5,2);
                            $dd     = substr((string)$row["received"],8,2);

                            $received  = "$yyyy-$mm-$dd";
                        }
                    }
                    #echo   "$received\n";  
                    #$received = date('Y-m-d',$row["received"]);
                    break;
                case 1:
                    if(strtotime($row["date"])){
                        $yyyy   = substr((string)$row["date"],0,4);
                        $mm     = substr((string)$row["date"],5,2);
                        $dd     = substr((string)$row["date"],8,2);
                        $received  = "$yyyy-$mm-$dd";
                    }
                    #$received = date('Y-m-d',$row["date"]);
                    break;
                case 2:
                    if(strtotime($row["date"])){
                        $yyyy   = substr((string)$row["date"],0,4);
                        $mm     = substr((string)$row["date"],5,2);
                        $dd     = substr((string)$row["date"],8,2);
                        $received  = "$yyyy-$mm-$dd";
                    }
                    break;
                case 3:
                    if(strtotime($row["date"])){
                        $yyyy   = substr((string)$row["date"],0,4);
                        $mm     = substr((string)$row["date"],5,2);
                        $dd     = substr((string)$row["date"],8,2);
                        $received  = "$yyyy-$mm-$dd";
                    }
                    break;
                case 4:
                    if(strtotime($row["date"])) {
                        $yyyy   = substr((string)$row["date"],0,4);
                        $mm     = substr((string)$row["date"],5,2);
                        $dd     = substr((string)$row["date"],8,2);
                        $received  = "$yyyy-$mm-$dd";
                    }
                    break;
            }
                
            #echo "\n$panel Panel processed"."$batch_name1 Batch processed";
            # Update in Eldorado
            if(strtotime($received)){
           
                    
                
           
            $sql="Update Batch_HUS set ReceivedType='$originname',DateReceived='$received' "
                    . "where panel='$panel' and DateMailOut=(select Max(DateMailOut) from Batch_HUS where panel=$panel)";
            $dbEldo->query($sql);
            }
            }
}   }
?>