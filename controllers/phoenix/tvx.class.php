<?php
class tvx extends template {
    public $access = 90;
    
    private function inject($filename,$defs,$defs_max) {
        include 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        $jobs->addJob("cd /var/www;php doInjectTVX.php $filename $defs $defs_max", "Writing TVX (User Upload)");
    }
    
    // Web Calls
    public function index() {
        $this->setView('tvx','_master.php');
    }
    public function add() {
        if ((isset($_FILES["tvxfile"])) && ($_FILES["tvxfile"]["error"] == UPLOAD_ERR_OK)) {
            // Uploaded
            $tmp_name = $_FILES["tvxfile"]["tmp_name"];
            $name = $_FILES["tvxfile"]["name"];
            $filename = UPLOADS."$name";
            move_uploaded_file($tmp_name, $filename);
            // Add Job
            include 'controllers/jobs.class.php';
            $jobs = new jobs();
            $jobs->addJob("cd /var/www;php doTVX.php $filename", "Adding TVX");
            // Ok?
            $dash = new dashboard();
            $dash->redirect('tvx');
        }
        else echo 0;
    }
    public function export($tvx_defs,$filename="") {
        $argE = explode("_", $tvx_defs);
        $tvx_list       = $argE[0];
        $def_list       = $argE[1];
        $def_list_max   = $argE[2];
        
        $tvx_list = explode(",",$tvx_list);
        
        $db = new database();
        
        include 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        foreach ($tvx_list as $t) {
            $sql = "SELECT * FROM tvx_main WHERE id = $t";
            $res = $db->query($sql);
            $tab = $db->getTable($res);
            
            $tvx_name = $tab[0]["media"]." / ".$tab[0]["filetype"];
            $jobs->addJob("cd /var/www;php doWriteTVX.php $t $def_list $def_list_max", "Writing TVX ($tvx_name)");
        }
    }
    public function getLoaded() {
        $db = new database();
        $sql = "SELECT * FROM tvx_main";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function upload() {
        $dash = new dashboard();
        $uploads_dir = UPLOADS;
        
        // if file exists, move to our TVX import directory
        if ($_FILES["generate-from-loaded-file"]["error"] == 0) {
            $tmp_name = $_FILES["generate-from-loaded-file"]["tmp_name"];
            $name = $_FILES["generate-from-loaded-file"]["name"];
            $move_result = move_uploaded_file($tmp_name, "$uploads_dir/$name");
            if (!$move_result) {
                $dash->redirect('tvx/index/transfer&error='.$move_result);
                return;
            }
        }
        else {
            $dash->redirect('tvx/index/upload&error='.$_FILES["generate-from-loaded-file"]["error"]);
            return;
        }
        
        // continue - add the job
        
        $defs       = serializeArray($_POST["select-definition-name"]);
        $defs_max   = serializeArray($_POST["max-value"]);
        
        echo "DEFS = $defs<br/>";
        echo "DEFS_MAX = $defs_max<br/>";
        
        $this->inject($uploads_dir.$name,$defs,$defs_max);
        $dash->redirect('tvx/index');
    }
    public function delete($id) {
        $db = new database();
        
        $tables = array('tvx_stations','tvx_schemas','attr_data','attr_enum','attr_demo_vals',
                        'tvx_household','household_attr',
                        'tvx_ind','ind_attr',
                        'viewing','viewing_times','viewing_attr');
        $ids = array();
        
        // TVX_STATIONS (0)
        $sql   = "SELECT id FROM tvx_stations WHERE tvx_id = $id";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // TVX_SCHEMAS (1)
        $sql   = "SELECT id FROM tvx_schemas WHERE data_id = $id";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // ATTR_DATA (2)
        $sql   = "SELECT id FROM attr_data WHERE schema_attr_id IN (".$ids[1].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // ATTR_ENUM (3)
        $sql   = "SELECT id FROM attr_enum WHERE schema_attr_id IN (".$ids[2].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // ATTR_DEMO_VALS (4)
        $sql   = "SELECT id FROM attr_demo_vals WHERE attr_id IN (".$ids[2].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // TVX_HOUSEHOLD (5)
        $sql   = "SELECT id FROM tvx_household WHERE tvx_id = $id";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // HOUSEHOLD_ATTR (6)
        $sql   = "SELECT id FROM household_attr WHERE tvx_household_id IN (".$ids[5].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // TVX_IND (7)
        $sql   = "SELECT id FROM tvx_ind WHERE tvx_household_id IN (".$ids[5].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // IND_ATTR (8)
        $sql   = "SELECT id FROM ind_attr WHERE tvx_household_id IN (".$ids[7].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // VIEWING (9)
        $sql   = "SELECT id FROM viewing WHERE tvx_ind_id IN (".$ids[7].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // VIEWING_TIMES (10)
        $sql   = "SELECT id FROM viewing_times WHERE viewing_id IN (".$ids[9].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // VIEWING_ATTR (11)
        $sql   = "SELECT id FROM viewing_attr WHERE viewing_id IN (".$ids[9].")";
        $res   = $db->query($sql);
        $tab   = $db->getTable($res);
        $ids[] = serializeDbField($tab, "id");
        
        // REMOVE!
        for ($i=0;$i<count($tables);$i++) {
            $sql = "DELETE FROM ".$tables[$i]." WHERE id IN (".$ids[$i].")";
            $res   = $db->query($sql);
        }
        $sql = "DELETE FROM tvx_main WHERE id = $id";
        $res   = $db->query($sql);
    }
    public function getHouseholdAttributeNames($tvx_id) {
        $db  = new database();
        $sql = "SELECT  a.id,
                        a.val_name
                FROM tvx_main m
                LEFT JOIN tvx_schemas s     ON m.id = s.data_id
                LEFT JOIN attr_data a       ON s.id = a.schema_attr_id
                WHERE       m.id   = $tvx_id
                        AND s.name = 'HouseholdAttributeSchema'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getSchemaDemo($tvx_id) {
        $db  = new database();
        $sql = "SELECT  v.*
                FROM tvx_main m
                LEFT  JOIN tvx_schemas s     ON m.id = s.data_id
                LEFT  JOIN attr_data a       ON s.id = a.schema_attr_id
                INNER JOIN attr_demo_vals v  ON a.id = v.attr_id
                WHERE       m.id   = $tvx_id
                        AND s.name = 'HouseholdAttributeSchema'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function saveAttrDemo() {
        $db   = new database();
        $dash = new dashboard();
        
        $post = $dash->getPost();
        $attr = $post["attr_id"];
        $vals = $post["vals"];
        $vals = explode(",", $vals);

        $sql = "SELECT id FROM attr_demo_vals WHERE attr_id = $attr";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0])) {
            $sql = "UPDATE attr_demo_vals SET 
                        family              = '".$vals[0]."',
                        individual          = '".$vals[1]."',
                        guest               = '".$vals[2]."',
                        tvset               = '".$vals[3]."',
                        fusion_family       = '".$vals[4]."',
                        fusion_individual   = '".$vals[5]."',
                        fusion_guest        = '".$vals[6]."' 
                    WHERE id = ".$tab[0]["id"];
        }
        else {
            $sql = "INSERT INTO attr_demo_vals (attr_id, family, individual, guest, tvset, fusion_family, fusion_individual, fusion_guest) VALUES (
                        $attr,
                        '".$vals[0]."',
                        '".$vals[1]."',
                        '".$vals[2]."',
                        '".$vals[3]."',
                        '".$vals[4]."',
                        '".$vals[5]."',
                        '".$vals[6]."')";
        }
        $res = $db->query($sql);
    }

    public function getTvxTime($date,$db_datetime) {
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
}
?>