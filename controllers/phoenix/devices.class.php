<?php
class devices extends template {
    public $access = 90;
    
    public function index() {
        $this->setView('devices', '_master.php');
    }
    public function load() {
        $this->setView('devices', '_master.php');
    }
    public function getAttributes($query) {
        $arrQuery = explode("_",$query);
        $panel = $arrQuery[0];
        $unit = $arrQuery[1];
        
        $db = new database();
        $sql = "SELECT  DISTINCT u.key,
                        u.value 
                FROM unit_meta u 
                WHERE   u.panel = $panel AND
                        u.unitid= $unit
                UNION
                SELECT  DISTINCT dm.key,
                        dm.value
                FROM devices d
                INNER JOIN device_meta dm ON d.id=dm.devices_id
                WHERE   d.typeid IN (8,9)
                        AND d.panel = $panel
                        AND d.unitid= $unit";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    public function getDevices($query) {
        $arrQuery = explode("_",$query);
        $panel = $arrQuery[0];
        $unit = $arrQuery[1];
        
        $db = new database();
        $sql = "SELECT  d.*,
                        dt.description
                FROM devices d 
                INNER JOIN device_types dt ON d.typeid=dt.id 
                WHERE   d.panel=$panel AND
                        d.unitid=$unit
                ORDER BY id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    public function getDeviceInfo($id) {
        $db = new database();
        $sql = "SELECT  d.*,
                        dt.description,
                        dm.key,
                        dm.value 
                FROM devices d 
                INNER JOIN device_types dt ON d.typeid=dt.id 
                LEFT JOIN device_meta dm ON d.id=dm.devices_id
                WHERE d.id=$id";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    public function getUnits($panel) {
        $db = new database();
        $sql = "SELECT DISTINCT unitid,typeid,deviceid
                FROM devices d
                WHERE   d.panel=$panel AND
                        (d.unitid>0 OR (d.typeid=8 OR typeid=9))
                ORDER BY unitid";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    
    public function getLastAppointment($panel) {
        $db = getOtherConnection('ARS');
        $sql = "SELECT MAX(apptdate) as lastdate FROM appts WHERE panel=$panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $result = $tab[0]["lastdate"];
        echo $result;
    }
    
    public function getSmartphones($panel) {
        $this->getExtra($panel, 6);
    }
    public function getIpods($panel) {
        $this->getExtra($panel, 7);
    }
    public function getTablets($panel) {
        $this->getExtra($panel, 5);
    }
    
    private function getExtra($panel,$typeid) {
        $sql = "SELECT brand,model FROM devices WHERE panel=$panel and typeid=$typeid";
        $db = new database();
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
}
?>