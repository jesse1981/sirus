<?php
class warehouse {
    var $dbLink = "";
    var $polluxLink = "";
    var $arsLink = "";
    
    var $date = "";
    
    public $access = 100;
    
    // Construct
    public function __construct($date="") {
        if ($date) $this->date = $date;
        //===========================================================================
        $this->dbLink = new database();
        //===========================================================================
        if ($date) $this->polluxLink = getPolluxConnection("LPLXA","National",$date);
        else $this->polluxLink = getPolluxConnection("LPLXA","National","db2000");
        //===========================================================================
        $this->arsLink = getOtherConnection('ARS');
    }
    
    // Private
    private function getDefintions($level=0,$id=0) {
        $level  = ($level)  ? "DB_LEVEL = $id " :   "DB_LEVEL > 0";
        $id     = ($id)     ? " AND ID = $id "  :   "";
        $sql = "SELECT * FROM DEFINITIONS WHERE $level $id AND (DISABLED = 0 OR DISABLED IS NULL) ORDER BY ORDERCOL,ID";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        return $tab;
    }
    private function getResolveCase($key,$array) {
        if (isset($array[strtolower($key)])) return strtolower($key);
        else if (isset($array[strtoupper($key)])) return strtoupper($key);
        else return " ";
    }
    private function getHistoricalValue($definition_row,$panel,$date) {
        /*
         * Return Values:
         * - -1 = Report doesn't exist for this date/level
         * - -2 = Definition doesn't exist for this date
         * - Anything else is the result value
         */
        // ----------------------------------------------------------
        $name   = $definition_row["name"];
        $level  = $definition_row["db_level"];
        $filename = "/var/www/reports/$date/Report_Level_$level.csv";
        // ----------------------------------------------------------
        // First get the index needed from the CSV
        $handle = fopen($filename,'r');
        if ($handle) {
            $buffer = fread($handle,512);
            $header = explode("\n",$buffer);
            $cols   = explode("|",$header[0]);
            fclose($handle);
        }
        else return -1;
        // ----------------------------------------------------------
        $count = 0;
        $found = false;
        foreach ($cols as $c) {
            $count++;
            if ($c==$name) { $found = true; break; }
        }
        if (!$found) return -2;
        // ----------------------------------------------------------
        // Got our index, now do the vLookup
        $result = vCsvLookup($filename, $panel, $count, true, "|");
        return $result;
    }
    private function getDefinitionValue($results,$table,$field,$formula,$date="") {
        $result = "";
        
        //echo "\n\n====================================\n";
        //echo "Now running: $formula\n";
        
        if ($formula) {
            if ($field) $formula = str_replace('$VALUE', $results[$this->getResolveCase($field, $results)], $formula);
            if (isset($results[$this->getResolveCase("id",$results)]))      $formula = str_replace('$DEVICE',   $results[$this->getResolveCase("id",$results)], $formula);
            if (isset($results[$this->getResolveCase("panel",$results)]))   $formula = str_replace('$PANEL',    $results[$this->getResolveCase("panel",$results)], $formula);
            if (isset($results[$this->getResolveCase("status",$results)]))  $formula = str_replace('$STATUS',   $results[$this->getResolveCase("status",$results)], $formula);
            if (isset($results[$this->getResolveCase("unitid",$results)]))  $formula = str_replace('$UNIT',     $results[$this->getResolveCase("unitid",$results)], $formula);
            if (isset($results[$this->getResolveCase("unit",$results)]))    $formula = str_replace('$UNIT',     $results[$this->getResolveCase("unit",$results)], $formula);
            if (isset($results[$this->getResolveCase("metid",$results)]))   $formula = str_replace('$METER',    $results[$this->getResolveCase("metid",$results)], $formula);
            
            //echo "\n\n======================================================================\n";
            //echo "$formula\n";
            
            $result  = ((strlen($formula)>4) && (strpos($formula, '$',5)===false)) ? eval('return '.$formula):"";
            
            //echo "COUNT = $result\n";
        }
        else {
            //echo "\n\n======================================================================\n";
            //echo "Field is: $field\n";
            
            $result = (isset($results[$this->getResolveCase($field, $results)])) ? $results[$this->getResolveCase($field, $results)]:"";
        }
        
        if ((!(string)trim($result)) && (trim((string)$result)!="0")) $result = "";
        
        return $result;
    }
    private function getDeviceQuery($withType=true,$date="") {
        $typeOne = ($withType) ? "1 as typeid,":"";
        $typeTwo = ($withType) ? "2 as typeid,":"";
        $typeThr = ($withType) ? "3 as typeid,":"";
        $typeFou = ($withType) ? "4 as typeid,":"";
        $typeFiv = ($withType) ? "5 as typeid,":"";
        $typeSix = ($withType) ? "6 as typeid,":"";
        $typeSev = ($withType) ? "7 as typeid,":"";
        $typeEle = ($withType) ? "11 as typeid,":"";
        
        $date = ($date) ? "AND a.apptdate <= '$date'":"AND a.apptdate > '2011-01-01'";
        
        $sql = "SELECT DISTINCT * FROM (
                    -- DVD
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeOne
                            TRIM(UPPER(mtdvdbrand)) as brand,
                            TRIM(UPPER(mtdvdmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtdvdbrand <> '' OR mtdvdmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date 
                    UNION
                    -- PVR
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeTwo
                            TRIM(UPPER(mtpvrbrand)) as brand,
                            TRIM(UPPER(mtpvrmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtpvrbrand <> '' OR mtpvrmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-09' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- VCR
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeThr
                            TRIM(UPPER(mtvcrbrand)) as brand,
                            TRIM(UPPER(mtvcrmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtvcrbrand <> '' OR mtvcrmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- GAMES
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeFou
                            TRIM(UPPER(mtgamesbrand)) as brand,
                            TRIM(UPPER(mtgamesmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtgamesbrand <> '' OR mtgamesmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- DVDR
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeFiv
                            TRIM(UPPER(mtdvdrbrand)) as brand,
                            TRIM(UPPER(mtdvdrmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtdvdrbrand <> '' OR mtdvdrmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- AUDIO
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeSix
                            TRIM(UPPER(mtaudiobrand)) as brand,
                            TRIM(UPPER(mtaudiomodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtaudiobrand <> '' OR mtaudiomodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- DTTV
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeSev
                            TRIM(UPPER(mtdttvbrand)) as brand,
                            TRIM(UPPER(mtdttvmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mtdttvbrand <> '' OR mtdttvmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '2014-01-07' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                            $date
                    UNION
                    -- TV (just to be sure it is captured
                    SELECT  a.panel,
                            (ae.meter-1) as meter,
                            0 as deviceid,
                            $typeEle
                            TRIM(UPPER(mtdttvbrand)) as brand,
                            TRIM(UPPER(mtdttvmodel)) as model
                    FROM apptsequipment ae
                    INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                    WHERE   (mttvbrand <> '' OR mttvmodel <> '') AND
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                ) as devices ";
        
        //$sql .= "WHERE (appt)";
        
        return $sql;
    }
    private function getDeviceQueryOther($withType=true,$date="") {
        $typeEig = ($withType) ? "5 as typeid,":"";
        $typeNin = ($withType) ? "6 as typeid,":"";
        $typeTen = ($withType) ? "7 as typeid,":"";
        
        $date = ($date) ? $date:date('Y-m-d',time());
        $date = "AND a.apptdate < '$date'";
        
        $sql = "SELECT DISTINCT devices.panel,
                                devices.meter,
                                devices.deviceid,
                                devices.typeid,
                                devices.brand,
                                devices.model

                FROM (
                    SELECT  panel,
                            MAX(id) as max_id
                    FROM appts
                    GROUP BY panel
                ) as m
                INNER JOIN (
                    -- TABLET
                    SELECT  n.apptid,
                            a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeEig
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE   n.pccomptype = 3 
                            AND n.pcmake != ''
                            $date
                    UNION
                    -- SMARTPHONE
                    SELECT  n.apptid,
                            a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeNin
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE   n.pccomptype = 4 
                            AND n.pcmake != ''
                            $date
                    UNION
                    -- IPOD
                    SELECT  n.apptid,
                            a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeTen
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE   n.pccomptype = 5 
                            AND n.pcmake != ''
                            $date
                ) as devices ON m.max_id=devices.apptid
                ORDER BY panel,deviceid; ";
        
        return $sql;
    }
    private function getUnitQuery() {
        $sql = "SELECT DISTINCT a.panel,
                                pcnumber, 
                                pcinstallcode, 
                                pcinternetaccess, 
                                pcmake, 
                                iat.type as InternetAccessType, 
                                os.type as OsType, 
                                c.type as CompType, 
                                l.location, 
                                monitorsize, 
                                workpc, 
                                av.name as AVName, 
                                cm.description AS Refusal_Reason,
                                pccomptype,
                                
                                (
                                    SELECT COUNT(DISTINCT mtbu)
                                    FROM apptsequipment ae1
                                    INNER JOIN appts a1 ON a1.apptdate=ae1.apptdate AND a1.id=ae1.id
                                    WHERE   a1.panel = a.panel AND
                                            ae1.mtbu <> ''
                                ) as tv_meter_count

                 FROM netsight_equipment n
                 INNER JOIN appts a ON a.id=n.apptid
                 LEFT JOIN internetaccesstype iat ON iat.id=n.pcintaccesstype
                 LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                 LEFT JOIN computertype c ON c.id=n.pccomptype
                 LEFT JOIN locations l ON l.id=n.locationid
                 LEFT JOIN antivirus av ON av.id=n.antivirusid
                 LEFT JOIN convergence_map cm ON cm.id=n.conv_not_monitored_id
                 WHERE	(pcmake <> '' OR iat.type <> '' OR os.type <> '' OR c.type <> '' OR l.location <> '' OR monitorsize <> '' OR av.name <> '' OR cm.description <> '') AND
                        (n.pccomptype = 1 OR n.pccomptype = 2)
                        --AND a.panel = 1012019
                        --AND a.apptdate <= '2014-01-07'
                 ORDER BY panel ";
        
        return $sql;
    }
    private function getMasterResult($level,$panel="") {
        $sql = "";
        switch ($level) {
            case 1:
                // Updated this query to get the results for only METRO / LIVE homes.
                $db = $this->polluxLink;
                $sql = "SELECT *
                        FROM TFAM F
                        LEFT JOIN TUNIT U ON F.PANEL=U.PANEL
                        WHERE   (
                                    F.STATUS LIKE '5%'
                                    OR F.CUSTOM8=2
                                )";
                if ($panel) $sql .= "AND F.PANEL = $panel";
                break;
            case 2:
                $db = $this->polluxLink;
                $sql = "SELECT DISTINCT
                               F.STATUS AS FAM_STATUS,
                               F.CUSTOM8,
                               INCHES,
                               U.*
                        FROM TUNIT U
                        INNER JOIN TFAM F ON U.PANEL=F.PANEL
                        INNER JOIN (
                              SELECT PANEL,
                                     UNITID,
                                     MAX(SRCTYPE) as SRCTYPE
                              FROM TSRC
                              GROUP BY PANEL,
                                       UNITID
                        ) AS S1 ON S1.PANEL=U.PANEL AND S1.UNITID=U.UNITID
                        INNER JOIN TSRC S ON U.PANEL=S.PANEL AND U.UNITID=S.UNITID AND S1.SRCTYPE=S.SRCTYPE
                        WHERE   (
                                    F.STATUS LIKE '5%'
                                    OR F.CUSTOM8=2
                                    OR
                                    (
                                        U.PANEL IN (
                                        SELECT DISTINCT PANEL
                                        FROM TEXTRATV
                                        WHERE   UNITID = 100
                                            AND CODE = '1'
                                            AND VAL = '1'
                                        )
                                        AND U.PANEL IN (
                                        SELECT DISTINCT PANEL
                                        FROM TEXTRATV
                                        WHERE   UNITID = 100
                                            AND CODE = '2'
                                            AND VAL IN ('4','5','6','7','8','9','10','11','12','13')
                                        )
                                    )
                                )";
                if ($panel) $sql .= "AND F.PANEL = $panel ";
                $sql .= "ORDER BY u.panel, u.unitid";
                break;
            case 3:
                $db = $this->dbLink;
                $sql = "SELECT *
                        FROM DEVICES d
                        LEFT JOIN DEVICE_META dm ON d.id=dm.devices_id";
                if ($panel) $sql .= "AND PANEL = $panel ";
                break;
            case 4:
                // TEMPORARY:
                $sql = "SELECT *
                        FROM TFAM F";
                break;
        }
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        return $tab;
    }
    private function refreshTables($db) {
        $sql = "DROP TABLE DEVICES";
        $db->query($sql);
        
        $sql = "DROP TABLE DEVICE_META";
        $db->query($sql);
        
        $sql = "DROP TABLE UNIT_META";
        $db->query($sql);
        
        $sql = "CREATE TABLE DEVICES (ID BIGSERIAL PRIMARY KEY,PANEL INTEGER,UNITID INTEGER,DEVICEID INTEGER,TYPEID INTEGER,BRAND VARCHAR(128),MODEL VARCHAR(512))";
        $db->query($sql);
        
        $sql = "CREATE TABLE DEVICE_META (ID BIGSERIAL PRIMARY KEY,DEVICES_ID INTEGER,KEY VARCHAR(64),VALUE VARCHAR(1024))";
        $db->query($sql);
        
        $sql = "CREATE TABLE UNIT_META (ID BIGSERIAL PRIMARY KEY,PANEL INTEGER,UNITID INTEGER,KEY VARCHAR(64),VALUE VARCHAR(1024))";
        $db->query($sql);
    }
    private function tvxToTime($val) {
        $result = "";
        
        if (strlen($val)==6) {
            $result = substr($val, 0, 2).":".substr($val, 2, 2).":".substr($val, 4, 2);
        }
        else return false;
        
        return $result;
    }
    private function tvxFixDateTime($date,$time) {
        $dateArr = explode('-',$date);
        $timeArr = explode(":",$time);
        // Convert to MKTime and convert back
        $fixed = mktime((int)$timeArr[0],(int)$timeArr[1],(int)$timeArr[2],(int)$dateArr[1],(int)$dateArr[2],(int)$dateArr[0]);
        $result = date('Y-m-d H:i:s',$fixed);
        
        return $result;
    }
    private function tvxStoreViewingData($node,$dateRecorded,$ind,$television) {
        $db = new database();
        
        $node_v = (isset($node["V"])) ? $this->tvxMakeNodeArray($node["V"]):array();
        
        foreach ($node_v as $household_M_T_V=>$household_M_T_V_VALUE) {

            // ------> CAPTURE STATION CODE
            $station_code = $node_v[$household_M_T_V]["@attributes"]["S"];
            
            // ----------------------------------------------------------------
            // NEW WAY TO INSERT VIEWING!
            $sql = "INSERT INTO VIEWING (tvx_ind_id,television,station_id) VALUES ($ind,$television,$station_code)";
            $res = $db->query($sql);
            $viewing_id = $db->pg_getLastId('viewing');
            // ----------------------------------------------------------------
            
            $node_p = ($node_v[$household_M_T_V]["P"]) ? $this->tvxMakeNodeArray($node_v[$household_M_T_V]["P"]):array();

            foreach ($node_p as $household_M_T_V_P=>$household_M_T_V_P_VALUE) {

                if (isset($node_p[$household_M_T_V_P]["@attributes"])) {
                    $television_station_viewing_times = $node_p[$household_M_T_V_P]["@attributes"];
                    
                    $watched_from   = $this->tvxFixDateTime($dateRecorded,$this->tvxToTime($television_station_viewing_times["F"]));
                    $watched_to     = $this->tvxFixDateTime($dateRecorded,$this->tvxToTime($television_station_viewing_times["T"]));
                    
                    // INSERT TO VIEWING_TIMES
                    $sql = "INSERT INTO viewing_times (viewing_id,watched_from,watched_to) VALUES ($viewing_id,'$watched_from','$watched_to')";
                    $db->query($sql);
                }
            }
            foreach ($node_v[$household_M_T_V]["A"] as $household_M_T_V_A=>$household_M_T_V_A_VALUE) {

                $television_station_viewing_attrs = $node_v[$household_M_T_V]["A"][$household_M_T_V_A]["@attributes"];

                // INSERT TO VIEWING_ATTR
                $value = (isset($node["V"][$household_M_T_V]["A"][$household_M_T_V_A]["@value"])) ? $node["V"][$household_M_T_V]["A"][$household_M_T_V_A]["@value"]:"";
                $sql = "INSERT INTO viewing_attr (viewing_id,attr_value_id,value) VALUES ($viewing_id,'".$television_station_viewing_attrs["Id"]."','".$value."')";
                //echo "$sql\n";
                $db->query($sql);
            }
        }
    }
    private function tvxMakeNodeArray($in) {
        if (!isset($in[0])) $out = array($in);
        else $out = $in;
        return $out;
    }
    private function tvxWriteDate($date,$db_datetime) {
        $result = "";
        $base_date = explode("-", $date);
        $base_mktime = mktime(0,0,0,$base_date[1],((int)$base_date[2]+1),$base_date[0]);
        
        $actual = explode(" ",$db_datetime);
        $actual_date = explode("-",$actual[0]);
        $actual_time = explode(":",$actual[1]);
        $actual_mk = mktime($actual_time[0],$actual_time[1],$actual_time[2],$actual_date[1],$actual_date[2],$actual_date[0]);
        
        if ($actual_mk >= $base_mktime) {
            $new_hour = (24 + (int)date("H",$actual_mk));
            $result = $new_hour.date("is",$actual_mk);
        }
        else $result = date("His",$actual_mk);
        return $result;
    }
    private function tvxGetUniqueVals($arr,$field) {
        $result = array();
        foreach ($arr as $a) {
            if (!in_array($a[$field], $result)) $result[] = $a[$field];
        }
        return $result;
    }
    private function tvxSerializeAttributes($id) {
        $db = $this->dbLink;
        $result_string  = "";
        $result_array   = array();
        $sql = "SELECT  *
                FROM viewing_attr va
                WHERE va.viewing_id=$id
                ORDER BY va.attr_value_id, va.value";
        $res = $db->query($sql);
        $v_attributes = $db->getTable($res);
        $v_count = 0;
        foreach ($v_attributes as $v_a) {
            if ($v_count) $result_string .= "&";
            $id     = $v_a["attr_value_id"];
            $val    = $v_a["value"];
            $result_string .= "$id=$val";
            $result_array[] = array("id"=>$id,"value"=>$val);
            $v_count++;
        }
        $result = array("string"=>$result_string,"array"=>$result_array);
        return $result;
    }
    private function tvxGetViewingTimes($viewing_id) {
        $db = $this->dbLink;
        $sql = "SELECT * 
                FROM viewing_times
                WHERE viewing_id = $viewing_id
                ORDER BY watched_from";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        return $tab;
    }
    private function tvxGetViewingAttr($viewing_id) {
        $db = $this->dbLink;
        $sql = "SELECT * 
                FROM viewing_attr
                WHERE viewing_id = $viewing_id
                ORDER BY id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        return $tab;
    }
    private function tvxFixWeight($val) {
        $val = (string)$val;
        $result = "";
        if      (strpos($val,".")===false) $result = $val.".00";
        elseif  (strlen(substr($val, (strpos($val, ".")+1)))<2) $result = $val."0";
        else    $result = $val;
        return $result;
    }
    private function tvxWriteDemoCfg($tvx_id,$tvx_filename,$definitions="") {
        $filename   = $tvx_filename."_democfg.cfg";
        $handle     = fopen($filename,'w');
        if ($handle) {
            $db  = new database();
            $sql = "SELECT  a.val_id,
                            a.val_name,
                            a.val_optional,
                            e.v_data,
                            e.v_value
                    FROM tvx_main t
                    LEFT JOIN tvx_schemas s ON t.id=s.data_id
                    LEFT JOIN attr_data a ON s.id=a.schema_attr_id
                    LEFT JOIN attr_enum e ON a.id=e.schema_attr_id
                    WHERE       t.id   = $tvx_id
                            AND s.name = 'HouseholdAttributeSchema'";
            $res = $db->query($sql);
            $tab = $db->getTable($res);
            
            $current_val_id = "";
            $index_parent   = 0;
            $index_children = 0;
            
            foreach ($tab as $row) {
                if ($current_val_id != $row["val_id"]) {
                    $current_val_id = $row["val_id"];
                    
                    $index_parent++;
                    $temp = $row["val_name"];
                    
                    fwrite($handle,"$index_parent , $temp");
                }
            }
            fclose($handle);
        }
        else return false;
    }
    private function tvxWriteIndAndViewing($node_m,$tvx_h_id,$guest,$dateRecorded,$getAttr=true) {
        // $node = $viewing[$household]["M"]
        // $tvx_h_id = $tvx_household_id
        // $guest = "T"/"F"
        // $dateRecorded = "yyyy-mm-dd"
        
        $db = $this->dbLink;
        
        foreach ($node_m as $household_M=>$household_M_VALUE) {

            if (isset($node_m[$household_M]["@attributes"]["Ind"])) {
                $ind = $node_m[$household_M]["@attributes"]["Ind"];
                $wei = (isset($node_m[$household_M]["@attributes"]["W"])) ? $node_m[$household_M]["@attributes"]["W"]:0;

                // INSERT TO TVX_IND
                $sql                = "INSERT INTO tvx_ind (tvx_household_id,ind_code,weight,guest) VALUES ($tvx_h_id,'$ind',$wei,'$guest')";
                $res                = $db->query($sql);
                $tvx_individual_id  = $db->pg_getLastId('tvx_ind');

                if ($getAttr) {
                    $node_m_a = (isset($node_m[$household_M]["A"])) ? $this->tvxMakeNodeArray($node_m[$household_M]["A"]):array();

                    foreach ($node_m_a As $household_M_A=>$household_M_A_VALUE) {
                        $member_attributes = $node_m_a[$household_M_A]["@attributes"];

                        // INSERT TO IND_ATTR
                        $sql                = "INSERT INTO ind_attr (tvx_ind_id,attr,value) VALUES ($tvx_individual_id,'".$member_attributes["Id"]."','".$node_m_a[$household_M_A]["@value"]."')";
                        $res                = $db->query($sql);
                    }
                }

                //---------------------------------------------------------
                if (isset($node_m[$household_M]["T"])) {
                    $viewingObj = $this->tvxMakeNodeArray($node_m[$household_M]["T"]);
                    if (isset($viewingObj[0])) {
                        // There is more than one television to add
                        foreach ($viewingObj as $viewingItem=>$viewingValue) {
                            $this->tvxStoreViewingData($viewingObj[$viewingItem],$dateRecorded,$tvx_individual_id,$viewingObj[$viewingItem]["@attributes"]["Id"]);
                        }
                    }
                    else {
                        $this->tvxStoreViewingData($viewingObj,$dateRecorded,$tvx_individual_id,$viewingObj["@attributes"]["Id"]);
                    }
                }
                //---------------------------------------------------------
            }
        }
    }
    private function tvxCreateDemoCfg($basepath,$media,$definitions,$definition_ids,$definition_names,$definitions_max,$filename="") {
        $handle = fopen("$basepath/import/TVX/$media/demo.cfg",'r');
        $demo_attributes    = array();
        $demo_enums         = array();
        if ($handle) {
            $buffer = fread($handle,filesize("$basepath/import/TVX/$media/demo.cfg"));

            $lines = explode("\r\n",$buffer);
            $family_group = false;
            $curr_tx3_pos = 0;

            // Read in to arrays
            foreach ($lines as $line) {
                if ((trim($line)) && (substr($line,0,1)!=";")) {
                    $tmp = explode(",",$line);

                    switch(count($tmp)) {
                        case 3:
                            // Attribute Level
                            $demo_attributes[] = array("SEQ"=>trim($tmp[0]), "NAME"=>trim($tmp[1]), "GROUPS"=>trim($tmp[2]));
                            if (trim($tmp[2])=="F") $family_group = true; 
                            break;
                        case 6:
                            // Enum Level (single demographic)
                            $demo_enums[] = array("SEQ"=>trim($tmp[0]), "VALUE"=>trim($tmp[1]),"GROUP_SEQ"=>trim($tmp[2]),"SEV_LOOP"=>trim($tmp[3]),"TX3_POS"=>trim($tmp[4]),"TX4_V"=>trim($tmp[5]));
                            if ($family_group) {
                                $curr_enum_grp = (int)($tmp[2]);
                                $curr_tx3_pos = (int)($tmp[4]);
                            }
                            break;
                        case 5:
                            // ?
                            break;
                        case 10:
                            // Enum Level (double demographic)
                            break;

                    }
                }
            }

            fclose($handle);

            // Write out demo.cfg with new definitions
            $handle = fopen("$basepath/export/TVX/$media/$filename-demo.cfg",'w');
            if ($handle) {
                // First just write out the last buffer as verbatim
                fwrite($handle,"$buffer\r\n");

                $curr_attr_seq = 0;
                $curr_enum_seq = 0;
                //$curr_enum_grp = 0;
                foreach ($demo_attributes as $a) {
                    if (($a["GROUPS"] == "F") && ((int)$a["SEQ"]>$curr_attr_seq)) $curr_attr_seq = (int)$a["SEQ"];
                }
                foreach ($demo_enums as $e) {
                    if ((int)$e["SEQ"]>$curr_enum_seq) $curr_enum_seq = (int)$e["SEQ"];
                    //if ((int)$e["GROUP_SEQ"]>$curr_enum_grp) $curr_enum_grp = (int)$e["GROUP_SEQ"];
                }

                $democfg_data = array();
                for ($i=0;$i<count($definitions);$i++) {
                    $curr_attr_seq++;
                    $id     = $definition_ids[$i];
                    $name   = $definition_names[$i];

                    fwrite($handle,"$curr_attr_seq , ".'"'."$name".'"'." , F\r\n");

                    // prepare enum values...
                    $grp_count = 0;
                    $curr_enum_grp++;
                    $curr_tx3_pos++;

                    $tmp_enums = array();
                    for($x=1;$x<=((int)$definitions_max[$i]+1);$x++) {
                        $curr_enum_seq++;

                        $grp_count++;
                        if ($grp_count>7) {
                            $grp_count = 1;
                            $curr_enum_grp++;
                        }

                        // start at 0 for the value, going one over for the (+)
                        $value = (($x)<=$definitions_max[$i]) ? ($x-1):$definitions_max[$i]."+";

                        fwrite($handle,"  $curr_enum_seq , ".'"'."$value".'"'.", \t\t\t$curr_enum_grp, $grp_count, $curr_tx3_pos, ".'"'.$x.'"'."\r\n");
                        $tmp_enums[] = array("SEQ"=>$curr_enum_seq, "VALUE"=>$value,"GROUP_SEQ"=>$curr_enum_grp,"SEV_LOOP"=>$grp_count,"TX3_POS"=>$curr_tx3_pos,"TX4_V"=>$x);
                    }
                    $democfg_data[] = array("ATTR_SEQ"=>$curr_attr_seq,"NAME"=>$name,"ENUM_DATA"=>$tmp_enums);
                }
                fclose($handle);
            }
            else echo "I was unable to open the file to create the demo.cfg!\r\n";
        }
        else echo "I was not able to read in the demo.cfg!\r\n";
        
        return $democfg_data;
    }
    private function tvxCreateDemoCfgXml($basepath,$MEDIA,$definitions,$democfg_data,$filename="") {
        $xml = simplexml_load_file("$basepath/import/TVX/$MEDIA/DemoCfg_$MEDIA.xml");
            
        // Demographics Length Update
        $node = $xml->xpath("/DEMOGRAPHICS_INFO/DEMOGRAPHICS_LEN/FAMILY");
        $node[0][0] = ((int)$node[0]+count($definitions));

        $node = $xml->xpath("/DEMOGRAPHICS_INFO/ASCII_REP/DEMOGRAPHICS_LEN/FAMILY");
        $node[0][0] = ((int)$node[0]+count($definitions));

        // Add Demo Classes
        $node = $xml->xpath("/DEMOGRAPHICS_INFO/DEMO_CLASS_LIST");
        foreach ($democfg_data as $d) {
            $demo_class = $node[0]->addChild("DEMO_CLASS");
            $single     = $demo_class->addChild("CODE",(string)$d["ATTR_SEQ"]);
            $single     = $demo_class->addChild("NAME",$d["NAME"]);
            $single     = $demo_class->addChild("TYPE","Flag");
            $single     = $demo_class->addChild("FAMILY","Yes");
            $single     = $demo_class->addChild("INDIVIDUAL","No");
            $single     = $demo_class->addChild("GUEST","No");
            $single     = $demo_class->addChild("REGIONAL","No");

            $demo_list  = $demo_class->addChild("DEMOGRAPHIC_LIST");
            foreach ($d["ENUM_DATA"] as $e) {
                $demo_item  = $demo_list->addChild("DEMOGRAPHIC");
                $single     = $demo_item->addChild("CODE",(string)$e["SEQ"]);
                $single     = $demo_item->addChild("NAME",(string)$e["VALUE"]);

                $flag_encoding  = $demo_item->addChild("FLAG_ENCODING_F");
                $single         = $flag_encoding->addChild("BYTE_POS",(string)((int)$e["GROUP_SEQ"]-1));
                $single         = $flag_encoding->addChild("BIT_POS",(string)((int)$e["SEV_LOOP"]-1));

                $ascii_rep      = $flag_encoding->addChild("ASCII_REP");
                $single         = $ascii_rep->addChild("CHAR_POS",(string)((int)$e["TX3_POS"]-1));
                $single         = $ascii_rep->addChild("CHAR_VALUE",$e["TX4_V"]);
            }
        }

        // Save!
        $xml->saveXML("$basepath/export/TVX/$MEDIA/$filename-DemoCfg_$MEDIA.xml");
        
        // Prettify?
        prettifyXML("$basepath/export/TVX/$MEDIA/$filename-DemoCfg_$MEDIA.xml");
        
        // Convert Line Endings?
        convertWindowsEOL("$basepath/export/TVX/$MEDIA/$filename-DemoCfg_$MEDIA.xml");
    }
    private function tvxGetDefinitionIds($definitions,$arrIds) {
        $definition_ids     = array();
        $definition_keys    = "123456789ABCDEFGHIJKLMNOPQRSTUVWZY"; // don't use 'Z'
        $definition_keys    = str_split($definition_keys);
        
        $definition_count = 0;
        
        foreach ($definitions as $d) {
            $definition_count++;
                        
            $complete = false;
            $repeat = 1;
            while (!$complete) {
                
                $found = false;
                
                foreach ($definition_keys as $k) {
                    
                    if ((!in_array(str_repeat($k, $repeat), $definition_ids)) && (!in_array(str_repeat($k, $repeat), $arrIds))) {
                        
                        $definition_ids[] = str_repeat($k, $repeat);
                        
                        $found      = true;
                        $complete   = true;
                        break;
                    }
                }
                if (!$found) $repeat++;
            }
        }
        return $definition_ids;
    }
    
    // Formulas
    private function isType($panel,$unit,$type) {
        $sql = "SELECT * FROM devices WHERE panel=$panel AND unitid=$unit AND typeid=$type";
        $res = $this->dbLink->query($sql);
        $num = $this->dbLink->getNumRows($res);
        return ($num) ? true:false;
    }
    private function hasMeta($panel,$unit,$key) {
        $result = "";
        $sql = "SELECT DISTINCT key FROM devices d LEFT JOIN device_meta dm ON d.id=dm.devices_id";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        $tab = (is_array($tab)) ? $tab:array();
        foreach ($tab as $item) {
            if (is_array($key)) {
                foreach ($key as $k) {
                    if ($item["key"]==$k) {
                        if ($result) $result .= " & ";
                        $result .= $k;
                    }
                }
            }
            elseif ($key==$item["key"]) $result = "Yes";
        }
        if (!$result) $result = "No";
        return $result;
    }
    private function hasSmart($panel,$unit=0) {
        // TO DO - Fix as Pollux no longer copied.
        
        $sql = "SELECT count(*) as count FROM devices d LEFT JOIN device_meta dm ON d.id=dm.devices_id WHERE dm.key='smart' or dm.key='ids'";
        if ($unit) $sql .= " AND d.unitid=$unit";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        if (isset($tab[0])) {
            return ((int)$tab[0]["count"]) ? "Yes":"No";
        }
        else return "No";
    }
    private function getARSUnitField($panel,$unit,$status,$field) {
        $result = "";
        
        $field_mapping = array("mttvbrand"=>"pcmake","mttvmodel"=>"os.type");
        
        if ((int)$status < 6) {
            $sql = "SELECT $field 
                    FROM apptsequipment ae 
                    INNER JOIN appts a ON a.apptdate = ae.apptdate and a.id = ae.id 
                    WHERE a.panel = $panel and ae.meter = $unit";
        }
        else {
            $sql = "SELECT ".$field_mapping[$field]." AS $field
                    FROM netsight_equipment ae 
                    INNER JOIN appts a ON a.apptdate = ae.apptdate and a.id = ae.apptid 
                    INNER JOIN operatingsystem os ON ae.pcopsystem=id
                    WHERE a.panel = $panel and ae.pcnumber = $unit";
        }
        
        // Add date?
        if ($this->date) $sql .= " AND a.apptdate <= '".$this->date."'";
        
        $res = $this->arsLink->query($sql);
        $tab = $this->arsLink->getTable($res);
        if (isset($tab[0])) {
            $result = $tab[(count($tab)-1)][$field];
        }
        return $result;
    }
    private function getConcatenation($item=array()) {
        $result = "";
        foreach ($item as $i) {
            $result .= $i;
        }
        return $result;
    }
    private function getUnitAttrCount($panel,$attr) {
        $sql = "SELECT COUNT(distinct unitid) AS UnitCount
                FROM unit_meta d
                WHERE   d.panel=$panel AND
                        d.key='$attr' AND
                        d.value='Yes'";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        $result = (int)$tab[0]["unitcount"];
        return $result;
    }
    private function getSource($esq="") {
        $source = "";
        
        if (substr((string)$esq, 0, 5)=="41323") $source = "ZTV";
        elseif ((int)$esq<4000000000) $source = "FTO";
        elseif ((int)$esq>4133200000) $source = "LBD";
        else $source = "MB";
        
        return $source;
    }
    private function getItemCount($panel,$source,$conditions=array(),$arrId=array(),$doCompare=true) {
        $result = 0;
        $compare = "";
        switch ($source) {
            case "HOUSEHOLD":
                $table = "";
                $field = "";
                break;
            case "UNITS":
                $db = $this->polluxLink;
                $table = "TUNIT a";
                $field = "UNITID";
                $relation = "a";
                break;
            case "UNITS_MERGED" :
                $db = $this->dbLink;
                //$table = "TEXTRATV e LEFT JOIN DEVICES d ON e.panel=d.panel AND e.unitid=d.unitid LEFT JOIN DEVICE_META dm on d.id=dm.devices_id";
                $table = "TEXTRATV e LEFT JOIN DEVICES d ON e.panel=d.panel AND e.unitid=d.unitid LEFT JOIN UNIT_META dm on d.panel=dm.panel AND d.unitid=dm.unitid";
                $field = "e.UNITID";
                $relation = "e";
                break;
            case "DEVICES":
                $db = $this->dbLink;
                $table = "DEVICES d LEFT JOIN DEVICE_META dm on d.ID=dm.DEVICES_ID";
                $field = "d.ID";
                $compare = "d.UNITID";
                $relation = "d";
                break;
            case "ARS_UNITS":
                $db = $this->dbLink;
                $table = "DEVICES d LEFT JOIN UNIT_META u ON d.unitid=u.unitid AND d.panel=u.panel";
                $field = "d.unitid";
                $relation = "d";
                break;
            case "EXTRATV":
                $db = $this->polluxLink;
                $table = "TEXTRATV e";
                $field = "e.UNITID";
                $relation = "e";
                break;
        }
        
        $sql = "SELECT COUNT(DISTINCT $field) as ITEM_COUNT FROM $table WHERE $relation.PANEL=$panel";
        foreach ($conditions as $item) {
            $sql .= " AND $item";
        }
        
        //if (!$doCompare) echo "$sql\n";
        
        if (($compare!="") && ($doCompare)) {
            $csql = "SELECT COUNT(DISTINCT $compare) as ITEM_COUNT FROM $table WHERE $relation.PANEL=$panel AND $compare > 0";
            foreach ($conditions as $item) {
                $csql .= " AND $item";
            }
        }
        //if (!$doCompare) { echo "$csql\n"; die(); }
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0]) && (isset($tab[0][$this->getResolveCase("ITEM_COUNT", $tab[0])]))) $result = (int)$tab[0][$this->getResolveCase("ITEM_COUNT", $tab[0])];
                
        if (($compare!="") && ($doCompare)) {
            $res = $db->query($csql);
            $tab = $db->getTable($res);
            $compareResult = (int)$tab[0]["item_count"];
            if (($compareResult) && ($compareResult < $result)) $result = $compareResult;
        }
        
        if (!$doCompare) {
            //echo "RESULT = $result\n";
            //die();
        }
        
        return $result;
    }
    private function getReason($panel,$unit) {
        $sql = "SELECT dm.value
                FROM devices d
                RIGHT JOIN device_meta dm ON d.id=dm.devices_id
                WHERE   d.panel=$panel AND
                        d.unitid=$unit AND
                        dm.key='refusal_reason' AND
                        dm.value<>''";
        
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        
        $result = (isset($tab[0]["value"])) ? $tab[0]["value"]:"";
        
        return $result;
    }
    private function getDeviceType($deviceId) {
        $result = "";
        $sql = "SELECT description FROM devices d INNER JOIN device_types t ON d.typeid=t.id WHERE d.id=$deviceId";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        if (isset($tab[0])) $result = $tab[0]["description"];
        return $result;
    }
    private function getDeviceValue($panel,$key,$unit=0) {
        if (isset($unit)) {
            $sql = "SELECT * 
                    FROM DEVICES d 
                    LEFT JOIN DEVICE_META dm ON d.id=dm.devices_id
                    WHERE   d.panel=$panel AND
                            dm.key='$key'";
            if ($unit) $sql .= " AND unitid=$unit";

            $res = $this->dbLink->query($sql);
            $tab = $this->dbLink->getTable($res);
            $result = (isset($tab[0]["value"])) ? $tab[0]["value"]:"";

            return $result;
        }
        else return "";
    }
    private function getUnitType($panel,$unit,$simple=true) {
        $result = "";
        $sql = "SELECT STATUS FROM TUNIT WHERE panel=$panel AND unitid=$unit";
        $res = $this->polluxLink->query($sql);
        $tab = $this->polluxLink->getTable($res);
        if (isset($tab[0])) {
            if ((int)$tab[0]["STATUS"] < 6) $result = "TV";
            else $result = "PC";
        }
        return $result;
    }
    private function getHasSub($panel,$unit) {
        $sql = "SELECT COUNT(*) as device_count FROM devices WHERE panel=$panel AND typeid < 7";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        if ((int)$tab[0]["device_count"]) return "Yes";
        else return "No";
    }
    private function getIsCrossPlatform($panel) {
        $db = $this->polluxLink;
        $sql = "SELECT TEXTRATV.CODE,TEXTRATV.VAL
                FROM TEXTRATV
                WHERE   TEXTRATV.PANEL = $panel
                        AND TEXTRATV.UNITID = 100
                        AND (
                                (
                                    CODE in ('1')
                                    AND VAL = '1'
                                )
                                OR (
                                    CODE = '2'
                                    AND VAL <> ''
                                    AND VAL IS NOT NULL
                                    AND VAL >=4
                                    AND VAL <= 13
                                )
                        )";
        //echo "$sql\n";
        $res = $db->query($sql);
        //var_dump($res);
        
        $num = $db->getNumRows($res);
        $result = ($num==2) ? "Yes":"No";
        
        return $result;
    }
    private function getPollingType($panel,$meter) {
        $db = $this->polluxLink;
        $lookup = $this->getLookup("TLOOKUPSINT", 510);
        
        $sql = "SELECT  VAL
                FROM TFAMMETHW F
                WHERE   CODE='57' AND
                        PANEL=$panel AND
                        METID=$meter";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0])) {
            foreach ($lookup as $item) {
                if ($item["reccode"]==$tab[0]["val"]) return $item["recdesc"];
            }
            return "";
        }
        
        return $result;
    }
    private function getPolluxVal($panel,$unit,$lookupTable,$tabcode,$table,$code) {
        $result = "";
        $db = $this->polluxLink;
        $lookup = $this->getLookup($lookupTable, $tabcode);
        
        $sql = "SELECT val FROM $table WHERE panel=$panel AND unitid=$unit AND code='$code'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        if (isset($tab[0])) {
            foreach ($lookup as $item) {
                if ($item["RECCODE"]==$tab[0]["VAL"]) return $item["RECDESC"];
            }
            return "";
        }
        return $result;
    }
    private function getLookup($table,$code=0,$system="Pollux") {
        if ($system=="Pollux") { 
            //$db = $this->dbLink;
            $db = $this->polluxLink;
            $sql = "SELECT * FROM $table WHERE TABCODE=$code";
        }
        else {
            $db = $this->arsLink;
            $sql = "SELECT * FROM $table";
        }
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $result = (is_array($tab)) ? $tab:array();
        return $result;
    }
    private function getYesNo($panel,$source,$formula=array()) {
        $result = "";
        $db = $this->dbLink;
        switch ($source) {
            case "HOUSEHOLD":
                $sql = "SELECT * FROM TFAM WHERE panel=$panel";
                $db = $this->polluxLink;
                break;
            case "EXTRATV":
                $sql = "SELECT * 
                        FROM TEXTRATV 
                        WHERE   panel=$panel";
                $db = $this->polluxLink;
                break;
            case "DEVICES":
                $sql = "SELECT * 
                        FROM devices d
                        LEFT JOIN device_meta dm ON d.id=devices_id
                        WHERE d.panel=$panel";
                break;
            case "DEVICES_UNIT":
                
                break;
            case "UNIT_META":
                $sql = "SELECT *
                        FROM unit_meta
                        WHERE panel=$panel";
                
                break;
        }
        
        foreach ($formula as $item) {
            $sql .= " AND $item";
        }
        $res = $db->query($sql);
        $num = $db->getNumRows($res);
        $result = ($num) ? "Yes":"No";
        
        return $result;
    }
    private function getTotalSmart($panel) {
        $dbARS = $this->dbLink;
        $dbPOL = $this->polluxLink;
        
        $sql = "SELECT DISTINCT unitid FROM TEXTRATV e WHERE e.code='44' AND e.val='4' AND e.panel=$panel";
        $res = $dbPOL->query($sql);
        $tabP = $dbPOL->getTable($res);
        
        $sql = "SELECT DISTINCT unitid FROM unit_meta u WHERE u.key='smart-tv' AND u.value='Yes' AND u.panel=$panel";
        $res = $dbARS->query($sql);
        $tabA = $dbARS->getTable($res);
        
        $result = array();
        foreach ($tabA as $item) $result[] = $item[$this->getResolveCase('unitid', $item)];
        foreach ($tabP as $item) if (!in_array($item[$this->getResolveCase('unitid', $item)], $result)) $result[] = $item[$this->getResolveCase('unitid', $item)];
        
        return count($result);
    }
    
    // Public 
    public function getLevelQuery($level,$date="") {
        echo "\n=========================\n";
        echo "= Executing for level $level =\n";
        echo "=========================\n";
        $outputResults = array();
        
        $definitions = $this->getDefintions($level);
        
        //if ($date)  { $db = getPolluxConnection ("LPLXA", "National", $date); echo "Loading date: $date\n"; }
        //else        $db = $this->polluxLink;
        
        $queryResults = $this->getMasterResult($level);
        
        echo "Working with ".count($queryResults)." records\n";
        
        echo "[";
        $percent = 0;
        
        for ($i=0;$i<count($queryResults);$i++) {
            
            if ((int)((100/count($queryResults)) * ($i+1)) > $percent) {
                $percent++;
                echo "=";
            }
            
            foreach ($definitions as $d) {
                $outputResults[$i][$d["name"]] = $this->getDefinitionValue($queryResults[$i], strtolower($d["source_table"]), strtolower($d["source_field"]), $d["source_formula"]);
            }
        }
        echo "]";
        
        return $outputResults;
    }
    public function synchARS($date="") {
        ini_set('memory_limit', '-1');
        $time_start = time();
        
        $time_start_display = date('F jS, Y @ h:ia',$time_start);
        echo "Process started $time_start_display\n";
        echo "===================================\n";
        
        echo "Establishing Database Connections...\n";
        $dbWHE = new database();
        $dbARS = getOtherConnection('ARS');
        
        echo "Retrieving value lookups...\n";
        $smartdevicetypes   = $this->getLookup('smartdevicetypes',0,"ARS");
        $satellitetype      = $this->getLookup('satellitetype',0,"ARS");
        $idstype            = $this->getLookup('idstype',0,"ARS");
        $setup              = $this->getLookup('setup',0,"ARS");
        $tvtype             = $this->getLookup('tvtype',0,"ARS");
        
        echo "Resetting table information...\n";
        $this->refreshTables($dbWHE);
        
        echo "Executing phase 1...\n";
        // Phase 1: Get all of the Sub Unit [panel,meter/unit,typeid,brand,model]
        // * TO-DO: Add to the selects to eliminate brand/model both with only white-space (http://postgresql.1045698.n5.nabble.com/Removing-whitespace-using-regexp-replace-td2150595.html)
        $sql = $this->getDeviceQuery(true,$date);
        $res = $dbARS->query($sql);
        $tab = $dbARS->getTable($res);
        
        echo "Executing phase 2...\n";
        // Phase 2: Cycle list, querying each /panel/unit as what flags should be applied.
        $attr = array();
        $attrComplete = array();
        
        // Library of Regular Expressions to clean
        $regex = array( '/^ /'=>'',
                        '/\(.*\)/'=>'',
                        '/ $/'=>'');
        
        foreach ($tab as $item) {
            
            // Fix empty models
            if (trim($item["model"])=="") $item["model"] = "Unknown";
            
            // use the reg expressions cleaning array
            foreach ($regex as $k1=>$v1) {
                $item["model"] = preg_replace($k1, $v1, $item["model"]);
            }
                
            // main attributes
            /*
             Current Defined Types:
             * 1) Playback
             * 2) Game
             * 3) Audio
             * 4) Broadcast
             * 5) Tablet
             * 6) Smartphone
             * 7) iPod
            */
            switch ((int)$item["typeid"]) {
                case 1:
                    // dvd
                    //$attr[$item["panel"]][$item["meter"]]["dvd"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["dvd"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 2:
                    // pvr
                    //$attr[$item["panel"]][$item["meter"]]["pvr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["pvr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 3:
                    // vcr
                    //$attr[$item["panel"]][$item["meter"]]["vcr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["vcr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 4:
                    // games
                    //$attr[$item["panel"]][$item["meter"]]["dvd"] = "Yes";
                    //$attr[$item["panel"]][$item["meter"]]["game"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["dvd"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["game"] = "Yes";
                    
                    //$attr[$item["panel"]][$item["meter"]]["smart"] = "Yes";
                    
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 2;
                    break;
                case 5:
                    // dvdr
                    //$attr[$item["panel"]][$item["meter"]]["dvdr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["dvdr"] = "Yes";
                    
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 6:
                    // audio
                    //$attr[$item["panel"]][$item["meter"]]["audio"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["audio"] = "Yes";
                    
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 3;
                    break;
                case 7:
                    // dttv
                    //$attr[$item["panel"]][$item["meter"]]["dttv"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"]["dttv"] = "Yes";
                    
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 4;
                    break;
                
                case 8:
                    // tablet
                    $attr[$item["panel"]][$item["meter"]]["dttv"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 5;
                    break;

                case 9:
                    // smartphone
                    $attr[$item["panel"]][$item["meter"]]["dttv"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 6;
                    break;

                case 10:
                    // ipod
                    $attr[$item["panel"]][$item["meter"]]["dttv"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 7;
                    break;
                case 11:
                    // TV, do nothing here.
                    break;
                default:
                    // fallback
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 128;
            }

            // Re-introduce the monitoring of panel/meter completed to avoid re-querying for the same data
            if (!in_array($item["panel"]."-".$item["meter"], $attrComplete)) {
            
                $attrComplete[] = $item["panel"]."-".$item["meter"];
                
                $sql = "SELECT DISTINCT * 
                        FROM apptsequipment ae
                        INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                        WHERE   a.panel = ".$item["panel"]."
                                AND ae.meter = ".((int)$item["meter"]+1);
                
                //if ($item["panel"]==2111463) echo "\nBroken Query(?):\n$sql\n";
                
                $res = $dbARS->query($sql);
                $itemdata = $dbARS->getTable($res);

                // merge item data
                $mergeditem = array();
                foreach ($itemdata as $cur) {
                    foreach ($cur as $k=>$v) {
                        if (($v) && ($k!="meter") && ($k!="panel")) $mergeditem[$cur["panel"]][(int)$cur["meter"]-1][$k]=$v;
                    }
                }
                
                //echo "Meter: ".((int)$item["meter"]+1)."\n";
                //var_dump($mergeditem["mtsatellitetype"]);
                
                // Additional data from appts (Total Smart TVs Connected)
                $sql = "SELECT MAX(postsmarttvscon) FROM appts WHERE panel=".$item["panel"];
                $res = $dbARS->query($sql);
                $tab = $dbARS->getTable($res);
                if ((!isset($tab[0]["max"])) || ($tab[0]["max"]=="")) $tab[0]["max"] = "0";
                $attr[$item["panel"]][$item["meter"]]["postsmarttvscon"] = $tab[0]["max"];

                
                // smart (device) attribute
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtdevsmart"])) $mergeditem[$item["panel"]][$item["meter"]]["mtdevsmart"] = 0;
                if ((int)$mergeditem[$item["panel"]][$item["meter"]]["mtdevsmart"]) {
                    $attr[$item["panel"]][$item["meter"]]["smart"] = "Yes";
                    
                    foreach ($smartdevicetypes as $i) {
                        if ($mergeditem[$item["panel"]][$item["meter"]]["mtdevsmart"]==$i["id"]) {
                            $attr[$item["panel"]][$item["meter"]]["smart-type"] = $i["devicetype"];
                            if (($i["devicetype"]=="Blueray")||($i["devicetype"]=="Game")) $attr[$item["panel"]][$item["meter"]]["dvd"] = "Yes";
                            break;
                        } 
                    }
                }
                // smart (UNIT) attribute
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mttvsmart"])) $mergeditem[$item["panel"]][$item["meter"]]["mttvsmart"] = 'f';
                $attr[$item["panel"]][$item["meter"]]["smart-tv"] = ($mergeditem[$item["panel"]][$item["meter"]]["mttvsmart"]=='t') ? "Yes":"No";
                
                // smart (UNIT) connected (drafting IDS Type selection for now)
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtidstype"])) $mergeditem[$item["panel"]][$item["meter"]]["mtidstype"] = 0;
                $attr[$item["panel"]][$item["meter"]]["smart-tv-connected"] = ((int)$mergeditem[$item["panel"]][$item["meter"]]["mtidstype"]) ? "Yes":"No";

                // non-broadcasting attribute
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtnb"])) $mergeditem[$item["panel"]][$item["meter"]]["mtnb"] = 'f';
                $attr[$item["panel"]][$item["meter"]]["nb"] = ($mergeditem[$item["panel"]][$item["meter"]]["mtnb"]=='t') ? "Yes":"No";

                // STV attribute
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtsatellitetype"])) $mergeditem[$item["panel"]][$item["meter"]]["mtsatellitetype"] = 0;
                if ((int)$mergeditem[$item["panel"]][$item["meter"]]["mtsatellitetype"]) {
                    $attr[$item["panel"]][$item["meter"]]["stv"] = "Yes";
                    
                    foreach ($satellitetype as $i) {
                        if ($mergeditem[$item["panel"]][$item["meter"]]["mtsatellitetype"]==$i["id"]) {
                            $attr[$item["panel"]][$item["meter"]]["stv-type"] = $i["type"];
                            break;
                        } 
                    }
                }

                // IDS
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtidstype"])) $mergeditem[$item["panel"]][$item["meter"]]["mtidstype"] = 0;
                if ((int)$mergeditem[$item["panel"]][$item["meter"]]["mtidstype"]) {
                    $attr[$item["panel"]][$item["meter"]]["ids"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]]["smart"] = "Yes";
                    
                    foreach ($idstype as $i) {
                        if ($mergeditem[$item["panel"]][$item["meter"]]["mtidstype"]==$i["id"]) {
                            $attr[$item["panel"]][$item["meter"]]["ids-type"] = $i["type"];
                            break;
                        } 
                    }
                }
                
                // Audio Type
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtsetup"])) $mergeditem[$item["panel"]][$item["meter"]]["mtsetup"] = 0;
                if ((int)$mergeditem[$item["panel"]][$item["meter"]]["mtsetup"]) {
                    $attr[$item["panel"]][$item["meter"]]["home-theatre"] = "Yes";
                    
                    foreach ($setup as $i) {
                        if ($mergeditem[$item["panel"]][$item["meter"]]["mtsetup"]==$i["id"]) {
                            $attr[$item["panel"]][$item["meter"]]["home-theatre-type"] = $i["setup"];
                            break;
                        } 
                    }
                }
                
                // Other TSV
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mtothertsv"])) $mergeditem[$item["panel"]][$item["meter"]]["mtothertsv"] = 'f';
                $attr[$item["panel"]][$item["meter"]]["other-tsv"] = ($mergeditem[$item["panel"]][$item["meter"]]["mtothertsv"]=='t') ? "Yes":"No";
                
                //TV Brand/Model
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mttvbrand"])) $mergeditem[$item["panel"]][$item["meter"]]["mttvbrand"] = '';
                $attr[$item["panel"]][$item["meter"]]["tv-brand"] = $mergeditem[$item["panel"]][$item["meter"]]["mttvbrand"];
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mttvmodel"])) $mergeditem[$item["panel"]][$item["meter"]]["mttvmodel"] = '';
                $attr[$item["panel"]][$item["meter"]]["tv-model"] = $mergeditem[$item["panel"]][$item["meter"]]["mttvmodel"];
                
                // TV Type
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mttvtype"])) $mergeditem[$item["panel"]][$item["meter"]]["mttvtype"] = 0;
                if ((int)$mergeditem[$item["panel"]][$item["meter"]]["mttvtype"]) {
                    
                    foreach ($tvtype as $i) {
                        if ($mergeditem[$item["panel"]][$item["meter"]]["mttvtype"]==$i["id"]) {
                            $attr[$item["panel"]][$item["meter"]]["tv-type"] = $i["type"];
                            break;
                        } 
                    }
                }
                
                // TV Size
                if (!isset($mergeditem[$item["panel"]][$item["meter"]]["mttvsize"])) $mergeditem[$item["panel"]][$item["meter"]]["mttvsize"] = '';
                $attr[$item["panel"]][$item["meter"]]["tv-size"] = $mergeditem[$item["panel"]][$item["meter"]]["mttvsize"];
            }
        }
        //==================================================================================================
        echo "Executing phase 3...\n";
        // Phase 3: Time to insert sub unit data (exclude the type for a more distinct list!)
        $sql = $this->getDeviceQuery(false,$date);
        $res = $dbARS->query($sql);
        $tab = $dbARS->getTable($res);
        
        foreach ($tab as $item) {
            
            // Fix empty models
            if (trim($item["model"])=="") $item["model"] = "Unknown";
            
            if (isset($attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"])) {
                
                // cycle devices separated by a slash...
                $brand = explode("/",$item["brand"]);
                $model = explode("/",$item["model"]);
                
                for ($i=0;$i<count($brand);$i++) {
                    $sql = "INSERT INTO DEVICES (PANEL,UNITID,DEVICEID,TYPEID,BRAND,MODEL) VALUES (";
                    $sql .= $item["panel"].",";
                    $sql .= $item["meter"].",";
                    $sql .= $item["deviceid"].",";
                    $sql .= $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"].",";
                    $sql .= "'".trim($brand[$i])."',";
                    $sql .= "'".trim($model[$i])."'";
                    $sql .= ")";

                    $res = $dbWHE->query($sql);

                    $itemid = $dbWHE->pg_getLastId("devices");

                    if (isset($attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"])) {
                        foreach ($attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]]["meta_vals"] as $k=>$v) {
                            $sql = "INSERT INTO DEVICE_META (DEVICES_ID,KEY,VALUE) VALUES ($itemid,'$k','$v')";
                            $res = $dbWHE->query($sql);
                        }
                    }
                    
                    // break out if both brand and model can't go to next item
                    if (!isset($model[$i+1])) break;
                }

                foreach ($attr[$item["panel"]][$item["meter"]] as $k=>$v) {
                    if ((!is_array($v)) && (!strpos($k, "device-type"))) {
                        //$sql = "INSERT INTO DEVICE_META (DEVICES_ID,KEY,VALUE) VALUES ($itemid,'$k','$v')";
                        //$res = $dbWHE->query($sql);
                        
                        $panel = $item["panel"];
                        $unit = $item["meter"];
                        $sql = "INSERT INTO UNIT_META (PANEL,UNITID,KEY,VALUE) VALUES ($panel,$unit,'$k','$v')";
                        $res = $dbWHE->query($sql);
                    }
                }
            }
        }
        
        echo "Executing phase 4...\n";
        // Phase 4: This is specifically to gather and store (verbatim) the devices for each panel specifiaclly for tablets, smartphones and ipods.
        $sql = $this->getDeviceQueryOther();
        $res = $dbARS->query($sql);
        $tab = $dbARS->getTable($res);
        
        foreach ($tab as $item) {
            
        // Fix empty models
        if (trim($item["model"])=="") $item["model"] = "Unknown";


            $sql = "INSERT INTO DEVICES (PANEL,UNITID,DEVICEID,TYPEID,BRAND,MODEL) VALUES (";
            $sql .= $item["panel"].",";
            $sql .= $item["meter"].",";
            $sql .= $item["deviceid"].",";
            $sql .= $item["typeid"].",";
            $sql .= "'".$item["brand"]."',";
            $sql .= "'".$item["model"]."'";
            $sql .= ")";

            $res = $dbWHE->query($sql);
        }
        
        

        echo "Executing phase 5...\n";
        // Phase 5: Gather netsight equipment data
        $sql = $this->getUnitQuery();
        $res = $dbARS->query($sql);
        $tab = $dbARS->getTable($res);
        
        echo "Executing phase 6...\n";
        // Phase 6: Merge rows
        $netsight_data = array();
        
        if (is_array($tab)) {
            foreach ($tab as $item) {
                foreach ($item as $k=>$v) {
                    if ($v) $netsight_data[$item["panel"]][$item["pcnumber"]][$k] = $v;
                }
            }
        }
        
        echo "Executing phase 7...\n";
        // Phase 7: Insert
        foreach ($netsight_data as $panel) {
            $count = 0;
            foreach ($panel as $pc) {
                $count++;
                $unitid = (isset($pc["tv_meter_count"])) ? ((int)$pc["tv_meter_count"]+$count):0;
                
                $sql = "INSERT INTO DEVICES (PANEL,UNITID,DEVICEID,TYPEID) VALUES (";
                $sql .= $pc["panel"].",";
                $sql .= "$unitid,";
                $sql .= $pc["pcnumber"].",";
                switch ($pc["pccomptype"]) {
                    case 1:
                        // Desktop
                        $typeid = 8;
                        break;
                    case 2:
                        // Laptop
                        $typeid = 9;
                        break;
                    default:
                        // Fallback
                        $typeid = 128;
                }
                $sql .= "$typeid";
                $sql .= ")";

                $res = $dbWHE->query($sql);
                $itemid = $dbWHE->pg_getLastId("devices");

                $ignored = array('panel','pcnumber','pccomptype');
                foreach ($pc as $k=>$v) {
                    if (!in_array($k, $ignored)) {
                        $sql = "INSERT INTO DEVICE_META (DEVICES_ID,KEY,VALUE) VALUES ($itemid,'$k','$v')";
                        $res = $dbWHE->query($sql);
                    }
                }
            }
        }
        // Reset Vars
        $attr = array();
        $netsight_data = array();
        $tab = array();
        
        // Close connections
        $dbARS->close();
        $dbWHE->close();
        
        // Reset Memory
        ini_set('memory_limit', '128M');
        
        $time_finish = time();
        
        $duration = round(abs(((($time_finish-$time_start) / 60) / 60)),2);
        echo "Complete.\nProcedure took $duration hours\n";
    }
    public function synchARSAppointments() {
        $dbARS = getOtherConnection('ARS');
        $dbWHE = new database();
        
        $sql = "SELECT	paneltype.type as panel_type,
                        city.city,
                        state.state,
                        appttypes.description,
                        linetest.initials as linetest_initials,
                        linetest.fullname as linetest_name,
                        technician.name as technician_name,
                        technician.email as technician_email,
                        metertype.type as meter_type,
                        appts.*
                FROM appts
                JOIN PanelType ON appts.paneltype=Paneltype.id
                JOIN City on appts.cityid=City.cityid
                JOIN State on City.stateid=state.stateid
                LEFT JOIN appttypes on appts.appttype=appttypes.code
                LEFT JOIN linetest on appts.linetesterid=linetest.id
                LEFT JOIN technician on appts.techid=technician.id
                LEFT JOIN metertype on appts.metertype=metertype.id
                WHERE appts.apptdate = '".date('Y-m-d',time())."'";
        $res = $dbARS->query($sql);
        $tab = $dbARS->getTable($res);
        foreach ($tab as $item) {
            $sql = "INSERT INTO appointments (panel,date,time) VALUES (".$item["panel"].",'".$item["apptdate"]."','".$item["appttime"]."')";
            $res = $dbWHE->query($sql);
            $appid = $dbWHE->pg_getLastId('appointments');
            foreach ($item as $k=>$v) {
                if (($k!="panel") && ($k!="apptdate") && ($k!="appttime") && ($v)) {
                    $v = htmlentities($v,ENT_QUOTES);
                    $sql = "INSERT INTO appointment_meta (appointment_id,key,value) VALUES ($appid,'$k','$v')";
                    $res = $dbWHE->query($sql);
                }
            }
        }
    }
    public function loadTVX($filename) {
        $xml = new XML2Array();
        $sms = new sms();
        try {
        // ---------------------------------------------------------
        // File to read in
        // ---------------------------------------------------------
        $handle = fopen($filename,'r');
        if ($handle) {
            $buffer = fread($handle,filesize($filename));
            fclose ($handle);
            
            // remove comments
            $buffer = preg_replace('/\n<!-- .* -->/', "", $buffer);
            
            echo str_repeat(".",10)."File loaded\n";
        }
        else die('I was unable to read the file!');
        // ---------------------------------------------------------
        // File to write out
        // ---------------------------------------------------------
        $filenameSQL = explode("/",$filename);
        $filenameSQL = $filenameSQL[(count($filenameSQL)-1)];
        $handle = fopen("/var/import/sql/$filenameSQL-output.sql",'w');
        if (!$handle) die('I am not able to create an output file!');
        // ---------------------------------------------------------
        $xml = $xml->createArray($buffer);
        $buffer = '';
        
        $db = $this->dbLink;
        include 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        $date = '';
        $media = '';
        $filetype = '';
        $num = 0;
        
        if ($xml) {
            echo str_repeat(".",10)."XML Valid\n";
            
            $headAttrs = $xml["ViewingFile"]["@attributes"];
            
            $dateRecorded   = $headAttrs["Date"];
            $media          = $headAttrs["Media"];
            $filetype       = $headAttrs["FileType"];
                        
            // INSERT TO TVX_MAIN (DATE_RECORDED) (keep ID)
            $sql = "SELECT id
                    FROM tvx_main 
                    WHERE   date_recorded   = '".$dateRecorded."'
                            AND media       = '".$media."'
                            AND filetype    = '".$filetype."'";
            $res = $db->query($sql);
            $tab = $db->getTable($res);
            $num = $db->getNumRows($res);
            if (!$num) {
                $sql    = "INSERT INTO tvx_main (date_recorded,media,filetype) VALUES ('$dateRecorded','$media','$filetype')";
                $res    = $db->query($sql);
                $tvx_id = $db->pg_getLastId('tvx_main');
            }
            else $tvx_id = $tab[0]["id"];
            // --------------------------------------------------------------------------------------- //
            // Fetch Schemas
            echo str_repeat(".",10)."Fetching Schemas\n";
            $schemas = $xml["ViewingFile"]["Schema"];
            if ($schemas) {
                    
                foreach ($schemas as $schemaName=>$schemaValue) {

                    // **** INSERT TO TVX_SCHEMAS ****
                    $sql            = "INSERT INTO tvx_schemas (data_id,name) VALUES ($tvx_id,'$schemaName')";
                    $res            = $db->query($sql);
                    $tvx_schema_id  = $db->pg_getLastId('tvx_schemas');

                    if ($schemaName!="StationMapping") {
                        // Change to other methods:
                        $attributeSchemaItems = (isset($xml["ViewingFile"]["Schema"][$schemaName]["Attribute"])) ? $this->tvxMakeNodeArray($xml["ViewingFile"]["Schema"][$schemaName]["Attribute"]):array();

                        foreach ($attributeSchemaItems as $attributeItem) {
                            // Go direct to attr_data
                            $atts = $attributeItem["@attributes"];
                            $val_id = $atts["Id"];
                            $val_name = $atts["Name"];
                            $val_optional = ($atts["Optional"]=="true") ? "T":"F";

                            $sql = "INSERT INTO attr_data (schema_attr_id,val_id,val_name,val_optional) VALUES ($tvx_schema_id,'$val_id','$val_name','$val_optional')";
                            $res = $db->query($sql);
                            $tvx_schema_attr_data_id = $db->pg_getLastId('attr_data');

                            $node_enum = (isset($attributeItem["Enum"])) ? $this->tvxMakeNodeArray($attributeItem["Enum"]):array();

                            foreach ($node_enum as $enumData) {
                                $enumAtts = $enumData["@attributes"];

                                $V = $enumAtts["V"];

                                $sql = "INSERT INTO attr_enum (schema_attr_id,v_data,v_value) VALUES ($tvx_schema_attr_data_id, '$V', '".$enumData["@value"]."')";
                                $res = $db->query($sql);
                            }
                        }
                    }
                    else {
                        $stationEnumItems = (isset($xml["ViewingFile"]["Schema"][$schemaName]["Enum"])) ? $this->tvxMakeNodeArray($xml["ViewingFile"]["Schema"][$schemaName]["Enum"]):array();

                        foreach ($stationEnumItems as $enumStationData) {
                            $enumAtts = $enumStationData["@attributes"];

                            $V = $enumAtts["V"];

                            $sql = "INSERT INTO tvx_stations (tvx_id,v_data,v_value) VALUES ($tvx_id, $V, '".$enumStationData["@value"]."')";
                            $res = $db->query($sql);
                        }
                    }
                }
            }
            
            // Fetch Viewing
            echo str_repeat(".",10)."Fetching Viewing\n";
            // Cycle Households
            $viewing = $xml["ViewingFile"]["Viewing"]["H"];
            
            $total = count($viewing);
            $current = 0;
            
            foreach ($viewing as $household=>$household_structure) {                                                    // EACH <H>
                
                $percent = (int)((100/$total) * $current);
                $jobs->setJobPercent($percent);
                $current++;
                
                $householdMain = $viewing[$household]["@attributes"];
                
                $panel  = $householdMain["Id"];
                $weight = $householdMain["W"];
                
                // INSERT TO TVX_HOUSEHOLD
                $sql = "INSERT INTO tvx_household (tvx_id,panel,weight) VALUES ($tvx_id,$panel,$weight)";
                $res = $db->query($sql);
                $tvx_household_id = $db->pg_getLastId('tvx_household');
                
                $node_a = ($viewing[$household]["A"]) ? $this->tvxMakeNodeArray($viewing[$household]["A"]):array();
                
                foreach ($node_a as $household_A=>$household_A_VALUE) {
                    // INSERT TO HOUSEHOLD_ATTR
                    $household_attributes = $household_A_VALUE["@attributes"];

                    $sql = "INSERT INTO household_attr (tvx_household_id,attr,value) VALUES ($tvx_household_id,'".$household_attributes["Id"]."','".$node_a[$household_A]["@value"]."')";
                    $res = $db->query($sql);
                }
                // ============================================ MEMBERS & VIEWING ============================================
                $node_m = (isset($viewing[$household]["M"])) ? $this->tvxMakeNodeArray($viewing[$household]["M"]):array();
                $this->tvxWriteIndAndViewing($node_m, $tvx_household_id, 'F', $dateRecorded);
                // ============================================ GUESTS & VIEWING ============================================
                $node_m = (isset($viewing[$household]["G"])) ? $this->tvxMakeNodeArray($viewing[$household]["G"]):array();
                $this->tvxWriteIndAndViewing($node_m, $tvx_household_id, 'T', $dateRecorded);
                // ============================================ UNCOVERED VIEWING ============================================
                $node_m = (isset($viewing[$household]["U"])) ? $this->tvxMakeNodeArray($viewing[$household]["U"]):array();
                $this->tvxWriteIndAndViewing($node_m, $tvx_household_id, 'T', $dateRecorded,false);
            }
            $jobs->setJobPercent(100);
        }
        }
        catch (Exception $e) {
            $sms->setTo('406568714');
            $sms->setMessage("TVX Import Failed: ".$e->getMessage());
            
            $sms->setTo('418564259');
            $sms->setMessage("TVX Import Failed: ".$e->getMessage());
        }
    }
    public function writeTVX($tvx_id,$definitions="",$definitions_max="") {
        // Initialize
        echo "...Initializing\n";
        $db = $this->dbLink;
        $filename = time();
        $basepath = "/mnt/aussvfp0109/data/systems/Phoenix";
        $handle = fopen("$basepath/export/TVX/$filename.tvx",'w');
        $indent = "  ";
        include 'controllers/jobs.class.php';
        $jobs = new jobs();
        // --------------------------------------------------------- //
        $definition_keys = "123456789ABCDEFGHIJKLMNOPQRSTUVWZYZ";
        // Any definitions to add?
        if ($definitions) {
            // Assign each unique keys
            $definitions        = explode(",", $definitions);
            $definitions_max    = explode(",", $definitions_max);
            
            $sql = "SELECT DISTINCT a.val_id
                    FROM tvx_main t
                    INNER JOIN  tvx_schemas s ON t.id=s.data_id
                    LEFT JOIN   attr_data   a ON s.id=a.schema_attr_id
                    WHERE s.name='HouseholdAttributeSchema'";
            $res = $db->query($sql);
            $h_schema = $db->getTable($res);
            
            $arrIds = serializeDbField($h_schema, "val_id");
            $arrIds = explode(",",$arrIds);
            $definition_ids = $this->tvxGetDefinitionIds($definitions,$arrIds);
            
            // Get the definition names
            $definition_names = array();
            foreach($definitions as $d) {
                $sql = "SELECT name FROM definitions WHERE id = $d";
                $res = $this->dbLink->query($sql);
                $tab = $this->dbLink->getTable($res);
                $definition_names[] = $tab[0]["name"];
            }
        }
        // --------------------------------------------------------- //
        if ($handle) {
            // Start XML Data
            echo "...File open\n";
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'."\n<ViewingFile \r\n".str_repeat($indent, 2).'xmlns="http://www.agbnielsen.com.au/tvx"'."\n");
            // --------------------------------------------------------- //
            $sql = "SELECT  m.date_recorded,
                            m.media,
                            m.filetype,
                            s.name,
                            d.val_id,
                            d.val_name,
                            d.val_optional,
                            e.v_data,
                            e.v_value
                    FROM tvx_main m 
                    LEFT JOIN tvx_schemas s ON m.id=s.data_id 
                    LEFT JOIN attr_data d ON d.schema_attr_id=s.id 
                    LEFT JOIN attr_enum e ON e.schema_attr_id=d.id
                    WHERE m.id = $tvx_id
                    ORDER BY    s.id,
                                d.id,
                                e.id";
            $res = $db->query($sql);
            $schema_data = $db->getTable($res);
            // --------------------------------------------------------- //
            $MEDIA  = $schema_data[0]["media"];
            $DATE   = $schema_data[0]["date_recorded"];
            fwrite($handle,str_repeat($indent, 2).'Media="'   .   $schema_data[0]["media"]        .'"'."\n");
            fwrite($handle,str_repeat($indent, 2).'Date="'    .   $schema_data[0]["date_recorded"].'"'."\n");
            fwrite($handle,str_repeat($indent, 2).'FileType="'.   $schema_data[0]["filetype"]     .'"'."\n");
            fwrite($handle,str_repeat($indent, 2).'SchemaVersion="1.2">'."\n<Schema>\n");
            // --------------------------------------------------------- //
            // Schema
            $current_schema     = "";
            $current_attribute  = "";
            $schema_count       = 0;
            $attribute_count    = 0;
            $new_schema         = true;
            // --------------------------------------------------------- //
            echo "...Writing schema data\n";
            $definitionsAdded = false;
            foreach ($schema_data as $schema_item) {
                if ($current_schema!=$schema_item["name"]) {
                    if ($schema_count) {
                        if ($attribute_count) {
                            fwrite($handle,"</Attribute>\n");
                        }
                        fwrite($handle,"</$current_schema>\n");
                    }
                    $current_schema = $schema_item["name"];
                    fwrite($handle,"<$current_schema>\n");
                    $schema_count++;
                    $new_schema = true;
                }
                // --------------------------------------------------------- //
                if ($current_schema!="StationMapping") {
                    if ($current_attribute!=$schema_item["val_name"]) {
                        if (($attribute_count) && (!$new_schema)) {
                            fwrite($handle,"</Attribute>\n");
                        }
                        // --------------------------------------------------------- //
                        if (($current_schema=="HouseholdAttributeSchema") && (!$definitionsAdded) && ($definitions)) {
                            $definitionsAdded = true;
                            for ($i=0;$i<count($definitions);$i++) {
                                $id     = $definition_ids[$i];
                                $name   = $definition_names[$i];
                                fwrite($handle,'<Attribute Id="'.$id.'" Name="'.$name.'" Optional="false">'."\n");
                                for($x=1;$x<=((int)$definitions_max[$i]+1);$x++) {
                                    // start at 0 for the value, going one over for the (+)
                                    $value = (($x)<=$definitions_max[$i]) ? ($x-1):$definitions_max[$i]."+";
                                    
                                    // correct empty strings
                                    if (strlen((string)$x)) fwrite($handle,'<Enum V="'.$x.'">'.$value.'</Enum>'."\n");
                                }
                                fwrite($handle,'</Attribute>'."\n");
                            }
                        }
                        // --------------------------------------------------------- //
                        $id = $schema_item["val_id"];
                        $name = $schema_item["val_name"];
                        $optional = ($schema_item["val_optional"]=="T") ? "true":"false";

                        fwrite($handle,'<Attribute Id="'.$id.'" Name="'.$name.'" Optional="'.$optional.'">'."\n");
                        $current_attribute = $name;

                        $attribute_count++;
                    }

                    $v_data     = $schema_item["v_data"];
                    $v_value    = htmlentities($schema_item["v_value"],ENT_QUOTES);
                    if (strlen((string)$v_data)) fwrite($handle,'<Enum V="'.$v_data.'">'.$v_value.'</Enum>'."\n");
                    $v_value = "";
                    $new_schema = false;
                }
                else {
                    $sql = "SELECT v_data,v_value
                            FROM tvx_stations
                            WHERE tvx_id=$tvx_id
                            ORDER BY v_data";
                    $res = $db->query($sql);
                    $StationCodes = $db->getTable($res);
                    foreach ($StationCodes as $sc) {
                        $v_data     = $sc["v_data"];
                        $v_value    = $sc["v_value"];
                        fwrite($handle,'<Enum V="'.$v_data.'">'.htmlentities($v_value,ENT_QUOTES).'</Enum>'."\n");
                    }
                }
            }
            fwrite($handle,"</StationMapping>\n</Schema>\n$indent<Viewing>\n");
            // --------------------------------------------------------- //
            // Households
            $sql = "SELECT id,panel FROM tvx_household WHERE tvx_id=$tvx_id";
            $res = $db->query($sql);
            $households = $db->getTable($res);
            
            $total   = count($households);
            $current = 0;
            
            echo "...Writing household data\n";
            if (false) {
                foreach ($households as $h) {

                    // do out of 95%, leaving the other 5 for the two config files
                    $percent = (int)((95/$total)*$current);
                    $jobs->setJobPercent($percent);
                    $current++;

                    $panel          = $h["panel"];
                    $household_id   = $h["id"];

                    // Get Household Attributes
                    $sql = "SELECT  h.panel,
                                    h.weight	as household_weight,
                                    ha.attr		as household_attr,
                                    ha.value	as household_value
                            FROM tvx_household h
                            LEFT JOIN household_attr ha ON h.id=ha.tvx_household_id
                            WHERE h.tvx_id=$tvx_id AND h.panel=$panel
                            ORDER BY h.panel,ha.id";
                    $res            = $db->query($sql);
                    $h_attributes   = $db->getTable($res);

                    $weight = $h_attributes[0]["household_weight"];
                    $temp = str_repeat($indent, 2).'<H Id="'.$panel.'" W="'.$this->tvxFixWeight($weight).'">'."\n";
                    fwrite($handle,$temp);

                    foreach ($h_attributes as $h_a) {
                        $id     = $h_a["household_attr"];
                        $val    = $h_a["household_value"];
                        $temp   = str_repeat($indent, 3).'<A Id="'.$id.'">'.$val.'</A>'."\n";
                        fwrite($handle,$temp);
                    }
                    // --------------------------------------------------------- //
                    // Added Definitions
                    for ($i=0;$i<count($definitions);$i++) {
                        $sql = "SELECT * FROM definitions WHERE id = ".$definitions[$i];
                        $res = $db->query($sql);
                        $tab = $db->getTable($res);
                        
                        if ($DATE!=date('Y-m-d',time())) {
                            $val = $this->getHistoricalValue($tab[0], $panel, $DATE);
                        }
                        else {
                            $table      = $tab[0]["source_table"];
                            $field      = $tab[0]["source_field"] ;
                            $formula    = $tab[0]["source_formula"];
                            $data       = array("panel"=>$panel);
                            $val = $this->getDefinitionValue($data, $table, $field, $formula);                            
                        }
                        $id     = $definition_ids[$i];
                        if ((int)$val>(int)$definitions_max[$i]) $val = ((int)$definitions_max[$i]+1);
                        elseif ((int)$val<0) $val = 1; // problem getting historical
                        else $val = ((int)$val+1);
                        
                        $temp   = str_repeat($indent, 3).'<A Id="'.$id.'">'.$val.'</A>'."\n";
                        fwrite($handle,$temp);
                    }
                    // --------------------------------------------------------- //
                    // Get Members
                    $sql = "SELECT *
                            FROM tvx_ind
                            WHERE tvx_household_id = $household_id
                            ORDER BY id";
                    $res        = $db->query($sql);
                    $members    = $db->getTable($res);

                    foreach ($members as $m) {
                        $ind_id     = $m["id"];
                        $ind_code   = $m["ind_code"];
                        $weight     = $m["weight"];

                        if (($m["ind_code"]!="zz") && ($m["guest"]=='F')) $element = "M";
                        else if ($m["guest"]=='T') {
                            $element = "G";
                            $ind_code = strtoupper($ind_code);
                        }
                        else $element = "U";

                        if ($element!="U") $temp = str_repeat($indent, 3).'<'.$element.' Ind="'.$ind_code.'" W="'.$this->tvxFixWeight($weight).'">'."\n";
                        else $temp = $temp = str_repeat($indent, 3).'<'.$element.' Ind="'.$ind_code.'">'."\n";
                        fwrite($handle,$temp);

                        // Get Member Attributes
                        $sql = "SELECT *
                                FROM ind_attr
                                WHERE tvx_ind_id=$ind_id
                                ORDER BY attr";
                        $res = $db->query($sql);
                        $m_attributes = $db->getTable($res);

                        foreach ($m_attributes as $m_a) {
                            $id     = $m_a["attr"];
                            $val    = $m_a["value"];
                            $temp = str_repeat($indent,4).'<A Id="'.$id.'">'.$val.'</A>'."\n";
                            fwrite($handle,$temp);
                        }
                        // --------------------------------------------------------- //
                        // New Way to get all viewing
                        $sql = "SELECT *
                                FROM viewing v
                                WHERE tvx_ind_id=$ind_id
                                ORDER BY v.television,v.id";
                        $res        = $db->query($sql);
                        $viewing    = $db->getTable($res);

                        $televisions    = $this->tvxGetUniqueVals($viewing, "television");

                        foreach ($televisions as $t) {
                            $temp = str_repeat($indent, 4).'<T Id="'.$t.'">'."\n";
                            fwrite($handle, $temp);

                            foreach ($viewing as $v) {
                                if ($v["television"]==$t) {

                                    $temp = str_repeat($indent, 5).'<V S="'.$v["station_id"].'">'."\n";;
                                    fwrite($handle,$temp);

                                    $attrs = $this->tvxGetViewingAttr($v["id"]);
                                    foreach ($attrs as $a) {
                                        $id     = $a["attr_value_id"];
                                        $val    = $a["value"];
                                        $temp = str_repeat($indent,6).'<A Id="'.$id.'">'.$val.'</A>'."\n";
                                        fwrite($handle,$temp);
                                    }

                                    $times = $this->tvxGetViewingTimes($v["id"]);
                                    foreach ($times as $t2) {
                                        $watched_from   = $this->tvxWriteDate($schema_data[0]["date_recorded"], $t2["watched_from"]);
                                        $watched_to     = $this->tvxWriteDate($schema_data[0]["date_recorded"], $t2["watched_to"]);
                                        $temp = str_repeat($indent,6).'<P F="'.$watched_from.'" T="'.$watched_to.'" />'."\n";
                                        fwrite($handle,$temp);
                                    }

                                    $temp = str_repeat($indent, 5).'</V>'."\n";;
                                    fwrite($handle,$temp);
                                }
                            }
                            $temp = str_repeat($indent, 4).'</T>'."\n";
                            fwrite($handle, $temp);
                        }
                        $temp = str_repeat($indent, 3).'</'.$element.'>'."\n";
                        fwrite($handle,$temp);
                    }
                    $temp = str_repeat($indent, 2).'</H>'."\n";
                    fwrite($handle,$temp);
                }
                $temp = "$indent</Viewing>\n</ViewingFile>\n";
                fwrite($handle,$temp);
                // --------------------------------------------------------- //
                echo "...Finished TVX, closing...\n";
                fclose($handle);
            }
            
            // Import demo.cfg
            echo "Creating demo.cfg...\n";
            $democfg_data = $this->tvxCreateDemoCfg($basepath, $MEDIA, $definitions, $definition_ids, $definition_names, $definitions_max,$filename);
            
            $jobs->setJobPercent(97);
            
            // Import demo XML
            echo "Creating DemoCfg_$MEDIA.xml...\n";
            $this->tvxCreateDemoCfgXml($basepath, $MEDIA, $definitions, $democfg_data,$filename);
            
            $jobs->setJobPercent(100);
        }
        else echo "\nI'm sorry, I wasn't able to open the file to write!\n\n";
    }
    public function injectTVX($filename,$definitions,$definitions_max) {
      
        $db = $this->dbLink;
        include 'controllers/jobs.class.php';
        $jobs = new jobs();
        $basepath = "/mnt/aussvfp0109/data/systems/Phoenix";
        $xml = simplexml_load_file($filename,"SimpleXMLElement",0,"tvx",true);
        $out = time();
        
        // Definition Data
        $sql                = "SELECT * FROM definitions";
        $res                = $db->query($sql);
        $definition_data    = $db->getTable($res);
        
        // Attributes
        $attrs  = $xml->attributes();
        $attrs  = (array)$attrs;
        
        $date   = $attrs["@attributes"]["Date"];
        $media  = $attrs["@attributes"]["Media"];
        $type   = $attrs["@attributes"]["FileType"];
        
        $xmln   = "http://www.agbnielsen.com.au/tvx";
        
        $definitions        = explode(",", $definitions);
        $definitions_max    = explode(",", $definitions_max);
        
        // --------------------------------------------------------- //
        // Any definitions to add?
        if ($definitions) {
            
            $xml->registerXPathNamespace("tvx",$xmln);
            $HouseholdSchemaAttributes = $xml->xpath("/tvx:ViewingFile/tvx:Schema/tvx:HouseholdAttributeSchema/tvx:Attribute");
            $hh_a = array();
            
            foreach ($HouseholdSchemaAttributes as $a) {
                
                $id     = (array)$a->attributes();
                $hh_a[] = $id["@attributes"]["Id"];
                
            }
            $definition_ids = $this->tvxGetDefinitionIds($definitions,$hh_a);
            
            // Get the definition names
            $definition_names = array();
            foreach($definitions as $d) {
                $sql = "SELECT name FROM definitions WHERE id = $d";
                $res = $this->dbLink->query($sql);
                $tab = $this->dbLink->getTable($res);
                $definition_names[] = str_replace("_", " ", $tab[0]["name"]);
            }
            // Add the data to the Schema
            $xml->registerXPathNamespace("tvx",$xmln);
            $HouseholdSchemaAttributes = $xml->xpath("/tvx:ViewingFile/tvx:Schema/tvx:HouseholdAttributeSchema");
            $HouseholdSchema = $HouseholdSchemaAttributes[0];
            
            for ($i=0;$i<count($definitions);$i++) {
                
                $Attribute = null;
                
                $Attribute = $HouseholdSchema->addChild("Attribute");
                $Attribute->addAttribute("Id",(string)$definition_ids[$i]);
                $Attribute->addAttribute("Name",(string)$definition_names[$i]);
                $Attribute->addAttribute("Optional","False");
                
                for ($x=1;$x<=($definitions_max[$i]+1);$x++) {
                    $V = ($x == ($definitions_max[$i]+1)) ? $definitions_max[$i]."+":(string)($x-1);
                    
                    $Enum = $Attribute->addChild("Enum",(string)$V);
                    $Enum->addAttribute("V",$x);
                }
            }
        }
        // --------------------------------------------------------- //
        $xml->registerXPathNamespace("tvx",$xmln);
        $households = $xml->xpath("/tvx:ViewingFile/tvx:Viewing/tvx:H");
        if (true) {
            $current = 0;
            $total = count($households);
            foreach ($households as $H) {
                
                // do out of 95%, leaving the other 5 for the two config files
                $percent = (int)((95/$total)*$current);
                $jobs->setJobPercent($percent);
                $current++;
                
                $attrs = (array)$H->attributes();
                $panel = $attrs["@attributes"]["Id"];
                // --------------------------------------------------------- //
                // Added Definitions
                for ($i=0;$i<count($definitions);$i++) {
                    
                    $val = $this->getAdhocValue($panel, $definitions[$i], $date);
                    $val = ((int)$val>(int)$definitions_max[$i]) ? ((int)$definitions_max[$i])+1:(int)$val+1;
                                       
                    $id = $definition_ids[$i];
                    $A  = $H->addChild("A",(string)$val);
                    $A->addAttribute("Id",(string)$id);
                }
                // --------------------------------------------------------- //
            }
            //Demo.cfg
            $democfg_data = $this->tvxCreateDemoCfg($basepath, $media, $definitions, $definition_ids, $definition_names, $definitions_max, $out);
            $jobs->setJobPercent(97);
            
            //Demo (XML)
            $this->tvxCreateDemoCfgXml($basepath, $media, $definitions, $democfg_data, $out);
            $jobs->setJobPercent(100);
            // Save!
            $xml->saveXML("$basepath/export/TVX/$out.tvx");
        }
    }
    public function synchARSTable($name) {
        // Best performed with an ARS table that has its first field as ID and on small lookup tables only.
        
        $ars = getARSConnection();
        $war = new database();
        
        // DROP the table
        $sql = "DROP TABLE $name";
        $war->query($sql);
        
        // SELECT the data
        $sql = "SELECT * FROM $name";
        $res = $ars->query($sql);
        $tab = $ars->getTable($res);
        
        // Get the fields
        $fields = array();
        foreach ($tab[0] as $k=>$v) {
            $fields[] = $k;
        }
        
        // CREATE the table
        $sql = "CREATE TABLE $name ($name"."_id BIGSERIAL PRIMARY KEY";
        for ($i=0;$i<count($fields);$i++) {
            $sql .= ",".$fields[$i]." VARCHAR(128)";
        }
        $sql .= ")";
        $war->query($sql);
        
        // INSERT the data
        foreach ($tab as $item) {
            $values = "";
            $x = 0;
            foreach ($item as $k=>$v) {
                if ($x) $values .= ",";
                $values .= "'".htmlentities($v,ENT_QUOTES)."'";
                $x++;
            }
            $sql = "INSERT INTO $name (".implode(',',$fields).") VALUES ($values)";
            $war->query($sql);
        }
    }
    public function testme($eval) {
        $result = eval($eval);
    }

    public function getAdhocValue($panel,$definition_id=0,$date="") {
        // get definition ID and date from post
        $das = new dashboard();
        $pos = $das->getPost();
        
        if ((!$definition_id)   && (isset($pos["definition_id"])))  $definition_id = $pos["definition_id"];
        if ((!$date)            && (isset($pos["date"])))           $obj = new warehouse($pos["date"]);
        else if ($date)                                             $obj = new warehouse($date);
        else $obj = $this;
        
        $def = $obj->getDefintions(0,$definition_id);
        
        $res = $obj->getMasterResult($def[0]["db_level"],$panel);
        $val = $obj->getDefinitionValue($res[0], $def[0]["source_table"], $def[0]["source_field"], $def[0]["source_formula"]);
        
        unset($obj);
        
        if (php_sapi_name()!="cli") {
            $jsn = json_encode($val);
            echo $jsn;
        }
        else return $val;
    }
    public function getDefinitionJson($defs=array()) {
        if (php_sapi_name()!="cli") {
            $dash = new dashboard();
            $post = $dash->getPost();
        }
        $results = array();
        
        if ((!$defs) && (isset($post["defs"]))) $defs = explode(",",$post["defs"]);
        foreach ($defs as $d) {
            $results[] = $this->getDefintions(0,$d);
        }
        if (php_sapi_name()!="cli") {
            $json = json_encode($results);
            echo $json;
        }
        else {
            return $results;
        }
    }
    public function getPanelsByDate($date) {
        $obj = new warehouse($date);
        
        if (php_sapi_name()!="cli") {
            $das = new dashboard();
            $pos = $das->getPost();

            $pag = $pos["page"];

            $ski = ((100*(int)$pag)-100);
            $sql = "SELECT FIRST 100 SKIP $ski DISTINCT PANEL
                    FROM TFAM F
                    WHERE   (
                                F.STATUS LIKE '5%'
                                OR F.CUSTOM8=2
                            )";
        }
        else {
            // executed via command line
            $sql = "SELECT DISTINCT PANEL
                    FROM TFAM F
                    WHERE   (
                                F.STATUS LIKE '5%'
                                OR F.CUSTOM8=2
                            )";
        }
        $res = $obj->polluxLink->query($sql);
        $tab = $obj->polluxLink->getTable($res);
        
        if (php_sapi_name()!="cli") {
            $jsn = json_encode($tab);
            echo $jsn;
        }
        else {
           return $tab;
        }
    }
}
?>