<?php
class custom extends template {
    public $access = 90;
    
    // private functions
    private function getVwCallStatusExtract($from,$to) {
        $vwCallStatusExtract = "SELECT  (View_Get_Market_Group.Description) AS MarketGroup, 
                                        CONVERT(VARCHAR,householder.hhnum) AS Hhnum, 
                                        householder.panelno AS PanelNo, 
                                        callstatus.username, 
                                        status.statusid AS Status, 
                                        sg.group_id as Group_Id,
                                        householder.state, 
                                        COUNT(callstatus.id) AS TotalCalls, 
                                        CONVERT(datetime, callstatus.dateofcall, 103) AS DateofCall,
                                        CONVERT(VARCHAR(8),callstatus.timeofcall,108) AS TimeofCall, 
                                        Modules.Decription AS Type, 
                                        city.city, 
                                        CASE
                                                WHEN
                                                        sg.Group_Id = 2 THEN 1
                                                ELSE 0
                                        END as tv,
                                        CASE
                                                WHEN
                                                        sg.Group_Id = 5 THEN 1
                                                ELSE 0
                                        END as tv_oos,
                                        CASE
                                                WHEN
                                                        sg.Group_Id = 7 THEN 1
                                                ELSE 0
                                        END as tv_refused,

                                        CASE
                                                WHEN
                                                        householder.cboIntRecruit = 0 THEN 1
                                                ELSE 0
                                        END as conv_recruited,
                                        CASE
                                                WHEN
                                                        householder.cboIntRecruit = 1 THEN 1
                                                ELSE 0
                                        END as conv_undecided,
                                        CASE
                                                WHEN
                                                        householder.cboIntRecruit = 2 THEN 1
                                                ELSE 0
                                        END as conv_refused,

                                        CASE
                                                WHEN
                                                        householder.conv_lastcallstatus IN (122,123,124,125) THEN 1
                                                ELSE 0
                                        END as conv_oos

                                FROM callstatus 
                                INNER JOIN status ON callstatus.callstatus = status.statusid 
                                INNER JOIN Status_Group sg ON status.Group_Id = sg.Group_Id
                                INNER JOIN householder ON householder.householderid = callstatus.householderid
                                INNER JOIN region ON householder.regionid = region.regionid 
                                INNER JOIN city ON city.cityid = region.cityid 
                                INNER JOIN Modules ON dbo.Translate_Null(callstatus.statusid) = Modules.Code 
                                INNER JOIN View_Get_Market_Group ON householder.householderid = View_Get_Market_Group.householderid



                                WHERE       (callstatus.dateofcall >= '$from') 
                                        AND (callstatus.dateofcall <= '$to')
                                GROUP BY	callstatus.username, 
                                            status.statusid, 
                                            householder.state, 
                                            city.city, 
                                            callstatus.dateofcall,
                                            callstatus.timeofcall, 
                                            Modules.Decription, 
                                            householder.hhnum, 
                                            householder.panelno, 
                                            View_Get_Market_Group.Description,
                                            sg.group_id,
                                            householder.cboIntRecruit,
                                            householder.conv_lastcallstatus";
        return $vwCallStatusExtract;
    }
    function fieldHasValue($field,$results) {
        foreach ($results as $r) {
            if ((isset($r[$field])) && ((string)$r[$field]) && ((string)$r[$field] != "0")) return true;
        }
        return false;
    }
    function getCode($con,$id) {
        $sql = "SELECT code FROM survey_questions WHERE id = $id";
        $res = $con->query($sql);
        $res = $con->getTable($res);
        $ret = $res[0]["code"];
        return $ret;
    }
    function getValue($con,$id) {
        $sql = "SELECT value FROM answer_values WHERE id = $id";
        $res = $con->query($sql);
        $res = $con->getTable($res);
        $ret = $res[0]["value"];
        return $ret;
    }
    
    // public functions
    public function index() {
        $this->setView('custom', '_master.php');
    }
    
    // TV Brand/Model Search
    public function getTvBrands() {
        $db = new database();
        $term = (isset($_GET["term"])) ? $_GET["term"]:"";
        $sql = "SELECT DISTINCT * FROM (
                SELECT regexp_replace(UPPER(value), '[ \t\n\r]*', '', 'g') as value
                FROM unit_meta 
                WHERE   key='tv-brand'
                        AND value LIKE '%$term%'
                ORDER BY value) as m";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getTvModels($brand) {
        $db = new database();
        $term = (isset($_GET["term"])) ? $_GET["term"]:"";
        $sql = "SELECT DISTINCT * FROM (
                SELECT TRIM(UPPER(u.value)) as value
                FROM unit_meta u ";
        if ($brand) $sql .= "INNER JOIN (
                                    SELECT *
                                    FROM unit_meta u2
                                    WHERE   u2.key='tv-brand'
                                            AND UPPER(u2.value)=regexp_replace('$brand', '[ \t\n\r]*', '', 'g')
                            ) as a ON a.panel=u.panel AND a.unitid=u.unitid ";
        $sql .= "WHERE  u.key='tv-model'
                        AND u.value LIKE '%$term%'
                ORDER BY value) as m";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getTvPropPercent($query,$return=false) {
        $db = new database();
        $arrQuery = explode("_",$query);
        $brand  = $arrQuery[0];
        $model  = $arrQuery[1];
        $key    = $arrQuery[2];
        $value  = $arrQuery[3];
        
        $sql_counted = "SELECT COUNT(*) as total
                        FROM unit_meta u
                        INNER JOIN (
                                SELECT *
                                FROM unit_meta u1
                                WHERE u1.key='tv-brand'
                                          AND u1.value=TRIM(UPPER('$brand'))
                        ) as brand ON brand.panel=u.panel AND brand.unitid=u.unitid
                        INNER JOIN (
                                SELECT *
                                FROM unit_meta u2
                                WHERE u2.key='tv-model'
                                          AND u2.value=TRIM(UPPER('$model'))
                        ) as model ON model.panel=u.panel AND model.unitid=u.unitid
                        WHERE u.key='$key' AND u.value='$value'";
        $sql_total  = "SELECT COUNT(*) as total
                        FROM unit_meta u
                        INNER JOIN (
                                SELECT *
                                FROM unit_meta u1
                                WHERE u1.key='tv-brand'
                                          AND u1.value=TRIM(UPPER('$brand'))
                        ) as brand ON brand.panel=u.panel AND brand.unitid=u.unitid
                        INNER JOIN (
                                SELECT *
                                FROM unit_meta u2
                                WHERE u2.key='tv-model'
                                          AND u2.value=TRIM(UPPER('$model'))
                        ) as model ON model.panel=u.panel AND model.unitid=u.unitid
                        WHERE u.key='$key'";
        $res     = $db->query($sql_counted);
        $tab     = $db->getTable($res);
        $counted = (int)$tab[0]["total"];
        
        $res     = $db->query($sql_total);
        $tab     = $db->getTable($res);
        $total   = (int)$tab[0]["total"];
        
        if ($total) $result = round(((100 / $total) * $counted),2).'%';
        else $result = '0%';
        //$result = "$counted / $total ";
        if (!$return) echo $result;
        else return $result;
    }
    public function getTvPropValArray($query) {
        $db = new database();
        $result = "";
        
        $arrQuery = explode("_",$query);
        $brand  = $arrQuery[0];
        $model  = $arrQuery[1];
        $key    = $arrQuery[2];
        
        $sql = "SELECT DISTINCT u.value
                FROM unit_meta u
                INNER JOIN (
                        SELECT *
                        FROM unit_meta u
                        WHERE u.key='tv-brand'
                                  AND u.value=TRIM(UPPER('$brand'))
                ) as brand ON brand.panel=u.panel AND brand.unitid=u.unitid
                INNER JOIN (
                        SELECT *
                        FROM unit_meta u
                        WHERE u.key='tv-model'
                                  AND u.value=TRIM(UPPER('$model'))
                ) as model ON model.panel=u.panel AND model.unitid=u.unitid
                WHERE u.key='$key'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        //var_dump($tab);
        //die();
        
        $count = 0;
        foreach ($tab as $item) {
            if ($count) $result .= ", ";
            
            $query    = $brand."_".$model."_".$key."_".$item["value"];
            $percent  = $this->getTvPropPercent($query,true);
            $result  .= $item["value"]." ($percent)";
            //$result .= $item["value"];
            
            $count++;
        }
        echo $result;
    }
    public function getMaximumWeights() {
        $files = directoryToArray('/mnt/aussvfp0109/data/systems/Phoenix/import/TVX/', false, true);
        $handle = fopen('/mnt/aussvfp0109/data/systems/Phoenix/export/TVX/maxWeights.csv','w');
        if ($handle) {
            fwrite($handle,"File,Member,Guest\r\n");
            foreach ($files as $f) {
                $xml = new xml($f);
                $members    = $xml->getXpath('/ViewingFile/Viewing/H/M', 'http://www.agbnielsen.com.au/tvx');
                $guests     = $xml->getXpath('/ViewingFile/Viewing/H/G', 'http://www.agbnielsen.com.au/tvx');
                $highest_M = 0;
                $highest_G = 0;
                foreach ($members as $m) {
                    $atts = $xml->getAttributes($m);
                    if ((int)$atts["W"]>$highest_M) $highest_M = (int)$atts["W"];
                }
                foreach ($guests as $m) {
                    $atts = $xml->getAttributes($m);
                    if ((int)$atts["W"]>$highest_G) $highest_G = (int)$atts["W"];
                }
                fwrite($handle,"$f,$highest_M,$highest_G\r\n");
            }
            fclose($handle);
        }
        else die('I was unable to open the results for writing.');
    }
    public function getAllMembers() {
        $down = new download();
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT	DISTINCT
                        h.panelno,
                        h.email,
                        m.firstname,
                        m.surname,
                        m.sex,
                        m.age
                FROM hhsurveymembers m
                INNER JOIN householder h ON m.householderID=h.householderid
                WHERE h.lastcallstatus = 2";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $filename   = "/tmp/".time();
        exportResToCSV($filename, $tab);
        $down->output_file($filename, "Member Export.csv", "text/csv");
    }
    
    // TV Panel Survey Process
    public function getSurveyResults($survey_id,$questions=array(),$origin=array(),$wave=array(),$date_rec_from='',$date_rec_to='',$date_ent_from='',$date_ent_to='') {
        $db = getOtherConnection('TVPANEL');
        $down = new download();
        
        $base_dir	= "/tmp/";
        $xls_filename   = "tvpanel_survey.xls";
        $xls            = new Spreadsheet_Excel_Writer($base_dir.$xls_filename);
        $sheet          = $xls->addWorksheet('Summary');
        $results	= array();
        $fields		= array();
        
        $where = "p.survey_id = $survey_id";
	if ($origin) $where .= " AND p.origin IN (". implode(",", $origin).")";
	if (count($questions)) $where .= " AND r.question_id IN (". implode(",", $questions).")";
	if (count($wave)) $where .= " AND p.wave IN (". implode(",", $wave).")";
        if ($date_rec_from) $where .= " AND p.received >= '$date_rec_from'";
        if ($date_rec_to) $where .= " AND p.received <= $date_rec_to";
        if ($date_ent_from) $where .= " AND p.date >= '$date_ent_from'";
        if ($date_ent_to) $where .= " AND p.date <= $date_ent_to";
        
        $sql = "SELECT	DISTINCT
                        p.panelno,
                        p.origin,
                        p.username,
                        p.date,
                        p.received,
                        r.question_id,
                        r.answer_id,
                        r.value_id,
                        r.slot_ref,
                        r.value_literal
                FROM survey_participants p 
                LEFT JOIN survey_participants_responses r ON p.id=r.participant_id  
                INNER JOIN survey_questions q on r.question_id=q.id
                WHERE $where 
                ORDER BY 	q.ordercol,
                                r.answer_id,
                                p.panelno,
                                r.slot_ref, 
                                r.value_id";
        
        //echo "$sql";
        //die();
        
        $res = $db->query($sql);
        $res = $db->getTable($res);
        $current_panel = "";
        $record_count = 0;
        $field_count = 0;
        
        foreach ($res as $item) {
            $code = $this->getCode($db,$item["question_id"])."_".$item["answer_id"];
            $index = $item["panelno"]."_".$item["slot_ref"]."_".$item["origin"]."_".$item["username"]."_".$item["date"]."_".$item["received"];

            if (!in_array($this->getCode($db,$item["question_id"])."_".$item["answer_id"],$fields)) $fields[] = $code;

            if (!isset($results[$index])) $results[$index] = array();
            if (!isset($results[$index][$code])) $results[$index][$code] = "";

            $results[$index][$code] .= ((int)$item["value_id"] == 0) ? $item["value_literal"]:",".$this->getValue($db,$item["value_id"]);

            $cur = $results[$index][$code];
            if (substr($cur,0,1) == ",") $results[$index][$code] = substr($cur,1);
        }
        
        $sheet->write(0, 0, "panel");
        $sheet->write(0, 1, "pnum");
        $sheet->write(0, 2, "origin");
        $sheet->write(0, 3, "username");
        $sheet->write(0, 4, "date");
        $sheet->write(0, 5, "received");
        
        $xls_row = 0;
        $xls_col = 5;
        
        foreach ($fields as $f) {
            if ($this->fieldHasValue($f,$results)) {
                $xls_col++;
                $sheet->write($xls_row, $xls_col, $f);
            }
        }
        
        foreach ($results as $k=>$v) {
            $kArr = explode("_",$k);
            $xls_row++;

            $sheet->write($xls_row, 0, $kArr[0]);
            $sheet->write($xls_row, 1, $kArr[1]);
            $sheet->write($xls_row, 2, $kArr[2]);
            $sheet->write($xls_row, 3, $kArr[3]);
            $sheet->write($xls_row, 4, $kArr[4]);
            $sheet->write($xls_row, 5, $kArr[5]);

            $xls_col = 5;
            foreach ($fields as $f) {
                $print = (isset($v[$f])) ? $v[$f]:"";
                if ($this->fieldHasValue($f,$results)) {

                    $xls_col++;
                    $sheet->write($xls_row, $xls_col, $print);
                }
            }
        }

        $xls->close();
        $down->output_file($base_dir.$xls_filename, "Survey Export.xls");
    }

    // User Statistics
    public function getUserStatistics() {
        $dash   = new dashboard();
        $down   = new download();
        $post   = $dash->getPost();
        $db     = getOtherConnection('ELDORADO');
        
        $date_from  = $post["date_from"];
        $date_to    = $post["date_to"];
        $filename   = "/tmp/userstats.xls";
        
        $sql = "SELECT	m.username as USERNAME,
                        SUM(tv) as TV,
                        SUM(tv_oos) as TV_OOS,
                        SUM(tv_refused) as TV_REFUSED,

                        SUM(tv) + SUM(tv_oos) + SUM(tv_refused) as TV_TOTAL,

                        SUM(conv_recruited) as CONV_RECRUITED,
                        SUM(conv_undecided) as CONV_UNDECIDED,
                        SUM(conv_refused) as CONV_REFUSED,

                        SUM(conv_recruited) + SUM(conv_undecided) + SUM(conv_refused) as CONV_TOTAL,

                        SUM(conv_oos) as CONV_OOS

                FROM (".$this->getVwCallStatusExtract($date_from, $date_to).") as m
                WHERE m.Type = 'Recruit'
                GROUP BY m.username";
        
        $handle = fopen("/tmp/tmp.txt",'w');
        fwrite($handle,$sql);
        fclose($handle);
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        exportResToXLS($filename, $tab);
        $down->output_file($filename, "User Statistics.xls");
    }
}
?>