<?php
class warehouse_adhoc {
    var $dbLink = "";
    
    // Construct
    public function __construct() {
        $this->dbLink = new database();
    }
    
    // Private
    private function getDefintions($level) {
        $sql = "SELECT * FROM DEFINITIONS WHERE DB_LEVEL=$level AND (DISABLED = 0 OR DISABLED IS NULL) ORDER BY ORDERCOL,ID";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        return $tab;
    }
    private function getDefinitionValue($results,$table,$field,$formula) {
        $result = "";
        
        if ($formula) {
            if ($field) $formula = str_replace('$VALUE', $results[$field], $formula);
            if (isset($results["id"])) $formula = str_replace('$DEVICE', $results["id"], $formula);
            if (isset($results["panel"])) $formula = str_replace('$PANEL', $results["panel"], $formula);
            if (isset($results["unitid"])) $formula = str_replace('$UNIT', $results["unitid"], $formula);
            if (isset($results["metid"])) $formula = str_replace('$METER', $results["metid"], $formula);
            
            //echo "\n======================================================================\n";
            //echo "$formula\n";
            
            $result  = eval('return '.$formula);
            
            //echo "COUNT = $result\n";
        }
        else $result = $results[$field];
        
        if ((!(string)trim($result)) && (trim((string)$result)!="0")) $result = "";
        
        return $result;
    }
    private function getDeviceQuery($withType=true,$date="2014-01-01") {
        $typeOne = ($withType) ? "1 as typeid,":"";
        $typeTwo = ($withType) ? "2 as typeid,":"";
        $typeThr = ($withType) ? "3 as typeid,":"";
        $typeFou = ($withType) ? "4 as typeid,":"";
        $typeFiv = ($withType) ? "5 as typeid,":"";
        $typeSix = ($withType) ? "6 as typeid,":"";
        $typeSev = ($withType) ? "7 as typeid,":"";
        
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
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
                            a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' AND a.apptdate <= '$date' and a1.panel=a.panel)
                            --AND a.panel = 1012019
                ) as devices ";
        
        //$sql .= "WHERE (appt)";
        
        return $sql;
    }
    private function getDeviceQueryOther($withType=true,$date="2014-01-01") {
        $typeEig = ($withType) ? "5 as typeid,":"";
        $typeNin = ($withType) ? "6 as typeid,":"";
        $typeTen = ($withType) ? "7 as typeid,":"";
        
        $sql = "SELECT DISTINCT * FROM (
                    -- TABLET
                    SELECT  a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeEig
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE   n.pccomptype = 3 and
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            a.apptdate >= '2011-01-01' AND
                            a.apptdate <= '$date'
                    UNION
                    -- SMARTPHONE
                    SELECT  a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeNin
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE n.pccomptype = 4 and
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            a.apptdate >= '2011-01-01' AND
                            a.apptdate <= '$date'
                    UNION
                    -- IPOD
                    SELECT  a.panel,
                            '0' as meter,
                            n.pcnumber as deviceid,
                            $typeTen
                            TRIM(UPPER(n.pcmake)) as brand,
                            TRIM(UPPER(os.type)) as model
                    FROM netsight_equipment n
                    INNER JOIN appts a ON a.id=n.apptid
                    LEFT JOIN operatingsystem os ON os.id=n.pcopsystem
                    WHERE n.pccomptype = 5 and
                            --a.apptdate = (select max(a1.apptdate) from appts a1 where a.apptdate >= '2011-01-01' and a1.panel=a.panel)
                            a.apptdate >= '2011-01-01' AND
                            a.apptdate <= '$date'
                ) as devices ";
        
        //$sql .= "WHERE (panel IN (1043674))";
        
        return $sql;
    }
    private function getUnitQuery($date="2014-01-01") {
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
                        AND a.apptdate <= '$date'
                 ORDER BY panel ";
        
        return $sql;
    }
    private function getMasterResult($level) {
        $sql = "";
        switch ($level) {
            case 1:
                // Updated this query to get the results for only METRO / LIVE homes.
                $sql = "SELECT *
                        FROM TFAM F
                        WHERE   PANEL<2000000 AND
                                STATUS LIKE '5%'";
                break;
            case 2:
                $sql = "SELECT *
                        FROM TUNIT U
                        INNER JOIN TSRC S ON U.PANEL=S.PANEL AND U.UNITID=S.UNITID
                        INNER JOIN TMET M ON U.PANEL=M.PANEL AND U.METID=M.METID
                        ORDER BY u.panel, u.unitid, m.metid";
                break;
            case 3:
                $sql = "SELECT *
                        FROM DEVICES d
                        LEFT JOIN DEVICE_META dm ON d.id=dm.devices_id";
                break;
            case 4:
                // TEMPORARY:
                $sql = "SELECT *
                        FROM TFAM F";
                break;
        }
        
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        
        return $tab;
    }
    private function refreshTables($db) {
        $sql = "DROP TABLE DEVICES";
        $db->query($sql);
        
        $sql = "DROP TABLE DEVICE_META";
        $db->query($sql);
        
        $sql = "CREATE TABLE DEVICES (ID BIGSERIAL PRIMARY KEY,PANEL INTEGER,UNITID INTEGER,DEVICEID INTEGER,TYPEID INTEGER,BRAND VARCHAR(128),MODEL VARCHAR(512))";
        $db->query($sql);
        
        $sql = "CREATE TABLE DEVICE_META (ID BIGSERIAL PRIMARY KEY,DEVICES_ID INTEGER,KEY VARCHAR(64),VALUE VARCHAR(1024))";
        $db->query($sql);
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
        $sql = "SELECT count(*) as count FROM devices d LEFT JOIN device_meta dm ON d.id=dm.devices_id WHERE dm.key='smart' or dm.key='ids'";
        if ($unit) $sql .= " AND d.unitid=$unit";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        if (isset($tab[0])) {
            return ((int)$tab[0]["count"]) ? "Yes":"No";
        }
        else return "No";
    }
    private function getUnitAttrCount($panel,$attr) {
        $sql = "SELECT COUNT(distinct unitid) AS UnitCount
                FROM devices d
                LEFT JOIN device_meta dm ON d.id=dm.devices_id
                WHERE   d.panel=$panel AND
                        dm.key='$attr' AND
                        dm.value='Yes'";
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
                $table = "TUNIT a";
                $field = "UNITID";
                $relation = "a";
                break;
            case "UNITS_MERGED" :
                $table = "TEXTRATV e LEFT JOIN DEVICES d ON e.panel=d.panel AND e.unitid=d.unitid LEFT JOIN DEVICE_META dm on d.id=dm.devices_id";
                $field = "e.UNITID";
                $relation = "e";
                break;
            case "DEVICES":
                $table = "DEVICES d LEFT JOIN DEVICE_META dm on d.ID=dm.DEVICES_ID";
                $field = "d.ID";
                $compare = "d.UNITID";
                $relation = "d";
                break;
            case "EXTRATV":
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
        
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        $result = (int)$tab[0]["item_count"];
        
        if (($compare!="") && ($doCompare)) {
            $res = $this->dbLink->query($csql);
            $tab = $this->dbLink->getTable($res);
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
    private function getUnitType($panel,$unit,$simple=true) {
        $result = "";
        $sql = "SELECT description FROM DEVICES d INNER JOIN DEVICE_TYPES t ON d.typeid=t.id WHERE panel=$panel AND unitid=$unit";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        if (isset($tab[0])) {
            if (($simple) && (($tab[0]["description"]!="Desktop") || ($tab[0]["description"]!="Laptop"))) $result = "TV";
            else $result = $tab[0]["description"];
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
        
        $sql = "SELECT  TEXTRATV.CODE,
                        TEXTRATV.VAL
                FROM TEXTRATV
                WHERE   TEXTRATV.PANEL = $panel AND
                        TEXTRATV.UNITID = 100 AND
                        (
                            (CODE in ('1') AND VAL = '1') OR
                            (CODE in ('2') AND VAL <> '' AND VAL::integer >=4 AND VAL::integer <= 13)
                        )";
        $res = $this->dbLink->query($sql);
        $num = $this->dbLink->getNumRows($res);
        $result = ($num==2) ? "Yes":"No";
        
        return $result;
    }
    private function getPollingType($panel,$meter) {
        
        $lookup = $this->getLookup("TLOOKUPSINT", 510);
        
        $sql = "SELECT  VAL
                FROM TFAMMETHW F
                WHERE   CODE='57' AND
                        PANEL=$panel AND
                        METID=$meter";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
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
        $lookup = $this->getLookup($lookupTable, $tabcode);
        
        $sql = "SELECT val FROM $table WHERE panel=$panel AND unitid=$unit AND code='$code'";
        $res = $this->dbLink->query($sql);
        $tab = $this->dbLink->getTable($res);
        
        if (isset($tab[0])) {
            foreach ($lookup as $item) {
                if ($item["reccode"]==$tab[0]["val"]) return $item["recdesc"];
            }
            return "";
        }
        return $result;
    }
    private function getLookup($table,$code=0,$system="Pollux") {
        if ($system=="Pollux") { 
            $db = $this->dbLink;
            $sql = "SELECT * FROM $table WHERE TABCODE=$code";
        }
        else {
            $db = getARSConnection();
            $sql = "SELECT * FROM $table";
        }
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $result = (is_array($tab)) ? $tab:array();
        return $result;
    }
    private function getYesNo($panel,$source,$formula) {
        $result = "";
        switch ($source) {
            case "HOUSEHOLD":
                $table = "TFAM";
                break;
        }
        $sql = "SELECT * FROM $table WHERE panel=$panel";
        
        foreach ($formula as $item) {
            $sql .= " AND $item";
        }
        $res = $this->dbLink->query($sql);
        $num = $this->dbLink->getNumRows($res);
        $result = ($num) ? "Yes":"No";
        
        return $result;
    }
    
    // Public 
    public function getLevelQuery($level) {
        echo "\n=========================\n";
        echo "= Executing for level $level =\n";
        echo "=========================\n";
        $outputResults = array();
        $definitions = $this->getDefintions($level);
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
    public function synchARS($date) {
        ini_set('memory_limit', '-1');
        $time_start = time();
        
        $time_start_display = date('F jS, Y @ h:ia',$time_start);
        echo "Process started $time_start_display\n";
        echo "===================================\n";
        
        echo "Establishing Database Connections...\n";
        $dbWHE = new database();
        $dbARS = getARSConnection();
        
        echo "Retrieving value lookups...\n";
        $smartdevicetypes   = $this->getLookup('smartdevicetypes',0,"ARS");
        $satellitetype      = $this->getLookup('satellitetype',0,"ARS");
        $idstype            = $this->getLookup('idstype',0,"ARS");
        $setup              = $this->getLookup('setup',0,"ARS");
        
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
        
        foreach ($tab as $item) {
            
            // Fix empty models
            if (trim($item["model"])=="") $item["model"] = "Unknown";
                
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
                    $attr[$item["panel"]][$item["meter"]]["dvd"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 2:
                    // pvr
                    $attr[$item["panel"]][$item["meter"]]["pvr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 3:
                    // vcr
                    $attr[$item["panel"]][$item["meter"]]["vcr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 4:
                    // games
                    $attr[$item["panel"]][$item["meter"]]["dvd"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]]["game"] = "Yes";
                    //$attr[$item["panel"]][$item["meter"]]["smart"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 2;
                    break;
                case 5:
                    // dvdr
                    $attr[$item["panel"]][$item["meter"]]["dvdr"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 1;
                    break;
                case 6:
                    // audio
                    $attr[$item["panel"]][$item["meter"]]["audio"] = "Yes";
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 3;
                    break;
                case 7:
                    // dttv
                    $attr[$item["panel"]][$item["meter"]]["dttv"] = "Yes";
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
                default:
                    // fallback
                    $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"] = 128;
            }

            // Re-introduce the monitoring of panel/meter completed to avoid re-querying for the same data
            if (!in_array($item["panel"], $attrComplete)) {
            
                $attrComplete[] = $item["panel"];
                
                $sql = "SELECT DISTINCT * 
                        FROM apptsequipment ae
                        INNER JOIN appts a ON a.id=ae.id AND a.apptdate=ae.apptdate
                        WHERE   a.panel = ".$item["panel"];
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
                                
                $sql = "INSERT INTO DEVICES (PANEL,UNITID,DEVICEID,TYPEID,BRAND,MODEL) VALUES (";
                $sql .= $item["panel"].",";
                $sql .= $item["meter"].",";
                $sql .= $item["deviceid"].",";
                $sql .= $attr[$item["panel"]][$item["meter"]][$item["brand"]."-".$item["model"]."-device-type"].",";
                $sql .= "'".$item["brand"]."',";
                $sql .= "'".$item["model"]."'";
                $sql .= ")";

                $res = $dbWHE->query($sql);

                $itemid = $dbWHE->pg_getLastId("devices");

                foreach ($attr[$item["panel"]][$item["meter"]] as $k=>$v) {
                    if ((!is_array($v)) && (!strpos($k, "device-type"))) {
                        $sql = "INSERT INTO DEVICE_META (DEVICES_ID,KEY,VALUE) VALUES ($itemid,'$k','$v')";
                        $res = $dbWHE->query($sql);
                    }
                }
            }
        }
        
        echo "Executing phase 4...\n";
        // Phase 4: This is specifically to gather and store (verbatim) the devices for each panel specifiaclly for tablets, smartphones and ipods.
        $sql = $this->getDeviceQueryOther(true,$date);
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
        $sql = $this->getUnitQuery($date);
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
    public function loadTVX($filename) {
        $xml = new xmlreader($filename);
    }
    public function testme($eval) {
        $result = eval($eval);
    }
}
?>