<?php
class bi extends template {
    public function index() {
        $this->setView('bi','_master.php');
    }
    public function create() {
        $db = new database();
        $ses = new session();
        
        $usr = $ses->getKey('user_id');
        
        $sql = "INSERT INTO bi (user_id) VALUES ($usr)";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $result = $db->pg_getLastId('bi');
        echo $result;
    }
    public function getJsonDataset() {
        // Return Values:
        // -1 = Failed to get a connection to the specified data source
        // -2 = Failed to read the source file
        // -3 = There was no results returned from the query
        $dash = new dashboard();
        $data = $dash->getPost();
        
        $base_path      = "/mnt/aussvfp0109/data/systems/Phoenix/import/bi/";
        $base_export    = "/mnt/aussvfp0109/data/systems/Phoenix/export/bi/";
        $schema = json_decode($data["data"]);
        
        switch ($schema["source"]) {
            case "EXCEL":
                // load the filename later.
                $db = 1;
                break;
            case "POLLUX":
                $db = getPolluxConnection();
                break;
            case "SELF":
                $db = new database();
                break;
            default:
                $db = getOtherConnection($schema["source"]);
                break;
        }
        if (!$db) echo -1;
        if ($schema["source"]!="EXCEL") {
            // load the query source
            $handle = fopen($base_path.$schema["file"],'r');
            if ($handle) {
                $buffer = fread($handle,filesize($base_path));

                // perform text replacement by parameter
                foreach ($schema["parameters"] as $p) {
                    $buffer = str_replace($p["replace"], $p["value"], $buffer);
                }
                // execute
                $res = $db->query($schemaql);
                $num = $db->getNumRows($res);
                if ($num) {
                    $tab = $db->getTable($res);
                    $jsn = json_encode($tab);
                }
                else echo -3;

                fclose($handle);
            }
            else echo -2;
        }
        else {
            $filename = $base_path.$schema["file"];

            // perform text replacement by parameter
            foreach ($schema["parameters"] as $p) {
                $filename = str_replace($p["replace"], $p["value"], $filename);
            }
            $db = new Spreadsheet_Excel_Reader($filename);
            if (!$db) echo -1;

            // read data in - first field names
            $fields = array();
            for ($a=1;$a<=$db->colcount($schema["offset"]);$a++) {
                $fields[] = $db->val(1,$a,$schema["offset"]);
            }
            // now the rest
            $output = array();
            for ($x=2;$x<=$db->rowcount($schema["offset"]);$x++) {
                for ($y=1;$y<=$db->colcount($schema["offset"]);$y++) {
                    $output[$x-2][$fields[$y-1]] = $db->val($x, $y, $schema["offset"]);
                }
            }
            if (!count($output)) echo -3;
            $jsn = json_encode($output);
        }
        echo $jsn;
    }
}
?>