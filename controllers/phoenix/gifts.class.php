<?php
class gifts extends template {
    public $access = 95;
    
    // private functions
    private function insertPoints($conn,$hhum_table,$typeName,$typeId,$pointsEarned) {
        // DEBUGING
        $mail = new mailer();
        $file = "/var/techPointsTest.csv";
        $handle = fopen($file,'w');
        
        fwrite($handle,"Date,Time,Date Earned,HH Number,Points Earned");
        // =======================================
        $date = date('Y-m-d',time());
        $date_short = str_replace('-', '', $date);
        $time = date('H:i:s',time());
        foreach ($hhum_table as $item) {
            try {
                if (isset($item["HHNUM"])) {
                    $hhnum = $item["HHNUM"];
                    $dateearned = substr($item["APPOINTDATE"],0,4)."-".substr($item["APPOINTDATE"],4,2)."-".substr($item["APPOINTDATE"],6,2);
                    $sql = "INSERT INTO pointstransaction (
                                batchname,
                                dateearned,
                                pointsearned,
                                interactiontypeID,
                                postingdate,
                                autoentry,
                                hhnum,
                                username
                            ) 
                            VALUES (
                                '$typeName-$date_short',
                                '$dateearned',
                                $pointsEarned,
                                $typeId,
                                '$date $time',
                                1,
                                $hhnum,
                                'SYSTEM'
                            )";
                    $res = $conn->query($sql);
                    fwrite($handle,"\r\n$date,$time,$dateearned,$hhnum,$pointsEarned");
                }
            }
            catch (Exception $e) {
                echo "ITEM:"."<br/>";
                var_dump($item);
            }
        }
        fclose ($handle);
        
        $mail->attachfile($file);
        $mail->bodytext("Here are the points that were added for $typeName");
        $mail->sendmail("jesse.bryant@nielsen.com,nick.mcnamara@nielsen.com,nathalie.villain@nielsen.com", "$typeName Points Added");
        unlink($file);
    }
    
    // public functions
    public function index() {
        $this->setView('gifts','_master.php');
    }
    
    public function checkAllInstallDates($server="LPLXA") {
        $db_pol = getPolluxConnection($server);
        $db_eld = getOtherConnection('ELDORADO');
        $filena = "/tmp/panelInstallDates.csv";
        $handle = fopen($filena,'w');
        
        echo "Getting Eldorado Data...\n";
        $sql = "SELECT  householderbonusID,
                        panelno,
                        CONVERT(DATE,installdate,103) as installdate,
                        CONVERT(DATE,disinstalldate,103) as disinstalldate
                FROM householderbonus b
                INNER JOIN householder h ON h.hhnum = b.hhnum
                WHERE       (
                                lastcallstatus = 2 OR
                                conv_lastcallstatus = 135
                            )
                        AND panelno IS NOT NULL
                        AND panelno = 1632592";
        $res = $db_eld->query($sql);
        $eld = $db_eld->getTable($res);
        
        echo "Sorting Eldorado Data...\n";
        $data = array();
        foreach ($eld as $e) {
            if (!isset($data[$e["panelno"]])) $data[$e["panelno"]] = array();
            $data[$e["panelno"]]["installdate"]         = $e["installdate"];
            $data[$e["panelno"]]["disinstalldate"]      = $e["disinstalldate"];
            $data[$e["panelno"]]["householderbonusID"]  = $e["householderbonusID"];
        }
        
        echo "Getting Pollux Panels...\n";
        $sql = "SELECT PANEL FROM TFAM";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        $panels = explode(",",  serializeDbField($tab, "PANEL"));
        
        $updated = 0;
        
        echo "Have got ".count($eld)." items to check...\n";
        fwrite($handle,"Household Type,Panel Number,Install Date");
        foreach ($eld as $item) {
            $panel      = $item["panelno"];
            echo "Processing $panel...\n";
            
            if (($data[$panel]["installdate"]=="") && (in_array($panel, $panels))) {
                $panelType  = getHouseholdType($db_pol, $panel);
                $install    = getInstallDate($db_pol, $panelType, $panel);
                $disinst    = getDisinstallDate($db_pol, $panelType, $panel);
                
                echo "Panel Type: $panelType\n";
                
                if ($install) $install  = substr($install, 0, 4)."-".substr($install, 4, 2)."-".substr($install, 6, 2);
                if ($disinst) $disinst  = substr($disinst, 0, 4)."-".substr($disinst, 4, 2)."-".substr($disinst, 6, 2);
                else $disinst = "NULL";
                
                echo "Install: $install\nDisinst: $disinst\n";
                
                if (((int)$data[$panel]) && ($install!="")) {
                    $id = $data[$panel]["householderbonusID"];
                    $sql = "UPDATE householderbonus
                            SET installdate = '$install',
                                disinstalldate = '$disinst'
                            WHERE householderbonusID = $id";
                    
                    echo "SQL: $sql\n\n";
                    
                    $sql = str_replace("'NULL'", "NULL", $sql);
                    $res = $db_eld->query($sql);
                    $updated++;
                    fwrite($handle,"\r\n$panelType,$panel,$install");
                }
            }
            else echo "Panel not in collection.\n";
        }
        fclose($handle);
        echo "\n\nComplete: Updated $updated records.";
    }
    public function checkAllDailyPoints() {
        $db = getOtherConnection('ELDORADO');
        
        $sql = "SELECT * 
                FROM householderbonus
                WHERE   installdate <= '".date('Y-m-d',strtotime("-6 days",time()))."'
                        AND (disinstalldate IS NULL OR disinstalldate = '')
                        AND dailypointstotal = 0";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
    }
    public function getPointSummaries($panel,$from="",$to="") {
        $db = getOtherConnection('ELDORADO');
        $dateWhere = ($from) ? "AND dateearned >= '$from' AND dateearned <= '$to'":"";
        
        $sql = "SELECT	CONCAT( title COLLATE Latin1_General_CI_AS,' ',firstname COLLATE Latin1_General_CI_AS,' ',name COLLATE Latin1_General_CI_AS) AS HHName,
                        firstname,
                        name,
                        address1,
                        address2,
                        suburb,
                        state,
                        postcode,
                        (
                            SELECT (SUM(p1.pointsearned) + b1.dailypointstotal) as pointsearned
                            FROM pointstransaction p1
                            INNER JOIN householderbonus b1 ON p1.hhnum = b1.hhnum
                            WHERE   p1.hhnum = h.hhnum
                                    $dateWhere
                            GROUP BY p1.hhnum,b1.dailypointstotal
                        ) as pointsearned,
                        ISNULL((
                            SELECT SUM(pointsvalue)
                            FROM pointsredeemed p2
                            WHERE   p2.hhnum = h.hhnum
                                    $dateWhere
                        ),0) as pointsredeemed,
                        (
                            (
                                SELECT (SUM(p3.pointsearned) + b2.dailypointstotal) as pointsearned
                                FROM pointstransaction p3
                                INNER JOIN householderbonus b2 ON p3.hhnum = b2.hhnum
                                WHERE   p3.hhnum = h.hhnum
                                        $dateWhere
                                GROUP BY p3.hhnum,b2.dailypointstotal
                            ) - 
                            ISNULL((
                                SELECT SUM(pointsvalue)
                                FROM pointsredeemed p4
                                WHERE   p4.hhnum = h.hhnum
                                        $dateWhere
                            ),0)
                        ) as currentpoints
                FROM householder h
                WHERE panelno = $panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        return $tab;
    }
    public function load($hhnum) {
        $db = getOtherConnection('ELDORADO');

        $sql = "SELECT * FROM (
                    SELECT  pointsredeemedid as id,
                            CONVERT(DATE,requestdate,101) as action_date,
                            'Redemption' as action_type,
                            contactname,
                            h.panelno,
                            p.hhnum,
                            '' as credit,
                            p.pointsvalue as debit,
                            c.catalogueno,
                            c.description
                    FROM pointsredeemed p
                    INNER JOIN householder h ON p.hhnum=h.hhnum
                    INNER JOIN catalogue c on p.catalogueno=c.catalogueno
                    WHERE h.hhnum = $hhnum
                    UNION
                    SELECT  pointstransactid as id,
                            CONVERT(DATE,pt.dateearned,101) as action_date,
                            p.interactiontype as action_type,
                            pt.username as contactname,
                            h.panelno,
                            pt.hhnum,
                            pt.pointsearned as credit,
                            '' as debit,
                            '' as catalogueno,
                            pt.description
                    FROM pointstransaction pt 
                    INNER JOIN points p ON pt.interactiontypeID=p.interactiontypeID 
                    INNER JOIN householder h on pt.hhnum = h.hhnum
                    WHERE h.hhnum = $hhnum
                ) as m
                ORDER BY m.action_date DESC";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        
        echo $jsn;
    }
    public function addTechPoints($date_from="",$date_to="") {
        if (!$date_from) {
            // first try from command line arguments
            if (isset($argv[1])) {
                if ($argv[1]=="AUTO") {
                    $date_from  = date('Ymd',strtotime("-7 days", time()));
                    $date_to    = date('Ymd',time());
                }
                else {
                    $date_from  = $argv[1];
                    $date_to    = $argv[2];
                }
            }
            // else get from post
            elseif (isset($_POST["date_from"])) {
                $values = getPostValues(array("date_from","date_to"));
                foreach ($value as $v) {
                    $$v = $v;
                }
            }
            // else just use the auto
            else {
                $date_from  = date('Ymd',strtotime("-7 days", time()));
                $date_to    = date('Ymd',time());
            }
        }
        else {
            if ($date_from=="AUTO") {
                $date_from  = date('Ymd',strtotime("-7 days", time()));
                $date_to    = date('Ymd',time());
            }
        }
        
        echo "===========================\nNow calculating:\nFrom Date: $date_from\nTo Date: $date_to\n===========================\n";
        
        // continue
        $poll = getPolluxConnection();
        $eldo = getOtherConnection("ELDORADO");

        // Get Households (FOR INSTALLATION APPOINTMENTS ONLY!)
        $sql        = "SELECT PANEL,APPOINTDATE FROM TTECVREQ WHERE APPOINTDATE>=$date_from AND APPOINTDATE<=$date_to AND REQUESTTYPE=99";
        //echo "$sql<br/>";
        $res        = $poll->query($sql);
        $tab_pollux = $poll->getTable($res);
        
        echo "\nCOUNT OF INSTALLS: ".count($tab_pollux)."\n";

        // Get HHnums
        $panels = serializeDbField($tab_pollux,"PANEL");
        $panels_install = $panels;
        
        if ($panels) {
            $sql = "SELECT hhnum,panelno FROM householder WHERE panelno IN ($panels)";
            $res = $eldo->query($sql);
            $tab_eldo = $eldo->getTable($res);

            echo "\nCOUNT OF PANELS: ".count($tab_eldo)."\n";

            // Append HHNUM on to POLLUX results
            $pollcount = 0;

            foreach ($tab_pollux as $p) {
                foreach ($tab_eldo as $e) {
                    if ($e["panelno"]==$p["PANEL"]) $tab_pollux[$pollcount]["HHNUM"] = $e["hhnum"];
                }
                $pollcount++;
            }

            // Cycle households and add points
            $this->insertPoints($eldo,$tab_pollux,'Installation',6,500);
            $success = count($tab_eldo);
        }
        //=====================================================================
        // Get Households (FOR NON-INSTALLATION APPOINTMENTS ONLY!)
        $sql = "SELECT PANEL,APPOINTDATE FROM TTECVREQ WHERE APPOINTDATE>=$date_from AND APPOINTDATE<=$date_to AND REQUESTTYPE<>99";
        //echo "$sql<br/>";
        $res = $poll->query($sql);
        $tab_pollux = $poll->getTable($res);
        
        echo "\nCOUNT OF OTHERS: ".count($tab_pollux)."\n";

        // Get HHnums
        $panels = serializeDbField($tab_pollux,"PANEL");
        
        if (($panels) && ($panels_install)) $panels_install .= ",$panels";
        else if ($panels) $panels_install = $panels;
        
        if ($panels) {

            $sql = "SELECT panelno,hhnum FROM householder WHERE panelno IN ($panels)";
            $res = $eldo->query($sql);
            $tab_eldo2 = $eldo->getTable($res);
            
            echo "\nCOUNT OF PANELS: ".count($tab_eldo)."\n";
            
            $success += count($tab_eldo2);
            foreach ($tab_eldo2 as $e) {
                $tab_eldo[] = array("panelno"=>$e["panelno"],"hhnum"=>$e["hhnum"]);
            }

            // Append HHNUM on to POLLUX results
            $pollcount = 0;

            foreach ($tab_pollux as $p) {
                foreach ($tab_eldo2 as $e) {
                    if ($e["panelno"]==$p["PANEL"]) $tab_pollux[$pollcount]["HHNUM"] = $e["hhnum"];
                }
                $pollcount++;
            }

            // Cycle households and add points
            $this->insertPoints($eldo,$tab_pollux,'TechVisit',4,100);
        }
        //=====================================================================
        // UPDATE THE INSTALL DATE!
        $sql = "SELECT PANEL,INSTALLDATE FROM TFAM WHERE PANEL IN ($panels_install)";
        $res = $poll->query($sql);
        $tab = $poll->getTable($res);
        $handle = fopen("/var/www/uploads/testscript.sql",'w');
        if ($handle) {
            foreach ($tab as $item) {
                $y = substr($item["INSTALLDATE"],0,4);
                $m = substr($item["INSTALLDATE"],4,2);
                $d = substr($item["INSTALLDATE"],6,2);
                $p = $item["PANEL"];
                $h = "";
                foreach ($tab_eldo as $pro) if ($pro["panelno"]==$p) { $h = $pro["hhnum"]; break; }
                if ($h) {
                    if ($y) {
                        $sql = "UPDATE householderbonus SET installdate='$y-$m-$d' WHERE hhnum = $h";
                        $res = $eldo->query($sql);
                        fwrite($handle,"$sql\n");
                    }
                }
            }
            fclose($handle);
        }
    }
    public function pointStatements($date_from,$date_to,$panels=array(),$filenameIn="",$mode=1) {
        $filenameIn = ($filenameIn) ? $filenameIn:'/var/www/statements/points.docx';
        $db_pol     = getPolluxConnection();
        $db_eld     = getOtherConnection('ELDORADO');
        $month      = date('F');
        $year       = date('Y');
        $date       = date('Y-m-d');
        $outDir     = "/mnt/aussvfp0109/data/Panel Dept/statements/$date";
        
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        // ensure directory exists
        $created = mkdir($outDir,0777,true);
        
        // Get Panels
        if (!$panels) {
            $sql = "SELECT PANEL
                    FROM TFAM
                    WHERE STATUS LIKE '5%'
                    OR CUSTOM8=2";
            $res = $db_pol->query($sql);
            $tab = $db_pol->getTable($res);
            $panels = explode(",",serializeDbField($tab, "PANEL"));
        }
        
        $count = count($panels);
        $current = 0;
        
        if ($mode==1) {
            // New
            $current++;

            $current++;
            $percent = (int)((99/$count)*$current);

            $jobs->setJobPercent($percent);
            
            echo "Producing statement for $panel...\n";
            $filenameOut = "$outDir/$panel.docx";
                
            $tab = $this->pointStatementsData($panels, $date_from, $date_to);
            foreach ($tab as $item) {
                // Render!
                $docx       = docxGetObject();
                $document   = $docx->loadTemplate($filenameIn);
                
                foreach ($item as $k=>$v) {
                    $document->setValue($k, $v);
                }
                $document->setValue('MONTH', $month);
                $document->setValue('YEAR', $year);
                    
                $document->saveAs($filenameOut);
            }
        }
        else {
            // Old
            foreach ($panels as $panel) {
                $current++;

                $current++;
                $percent = (int)((99/$count)*$current);

                $jobs->setJobPercent($percent);

                echo "Producing statement for $panel...\n";

                $filenameOut = "$outDir/$panel.docx";

                $tab = $this->getPointsSummary($panel);

                if ($tab) {
                    // Get general household Details
                    $household = $this->getPointSummaries($panel);

                    $hhname = $household[0]["HHName"];
                    $street = (trim($household[0]["address1"])) ? $household[0]["address1"]." / ":"";
                    $street .= $household[0]["address2"];
                    $suburb = $household[0]["suburb"];
                    $state  = $household[0]["state"];
                    $postc  = $household[0]["postcode"];
                    $first  = $household[0]["firstname"];
                    $lastn  = $household[0]["name"];

                    $earned = $household[0]["pointsearned"];
                    $redeem = $household[0]["pointsredeemed"];
                    $curret = $household[0]["currentpoints"];

                    // Render!
                    $docx       = docxGetObject();
                    $document   = $docx->loadTemplate($filenameIn);

                    $document->setValue('MONTH', $month);
                    $document->setValue('YEAR', $year);
                    $document->setValue('PANEL', (string)$panel);

                    $document->setValue('HHNAME', $hhname);
                    $document->setValue('STREET', $street);
                    $document->setValue('SUBURB', $suburb);
                    $document->setValue('STATE', $state);
                    $document->setValue('POSTCODE', $postc);

                    $document->setValue('EARNED', $earned);
                    $document->setValue('REDEEMED', $redeem);
                    $document->setValue('CURRENT', $curret);


                    $document->setValue('FIRSTNAME', $first);
                    $document->setValue('LASTNAME', $lastn);

                    // Fix catalogue description
                    $catalogue = explode(",",serializeDbField($tab, "interactiontype"));
                    foreach ($catalogue as $k=>$v) {
                        if (substr($v,0,4)=="AGB-") $catalogue[$k] = substr($v,7);
                    }

                    // Fix last dates (remove time)
                    $last = explode(",",serializeDbField($tab, "lastdate"));
                    foreach ($last as $k=>$v) {
                        $last[$k] = date('j F Y',strtotime(substr($v,0,11)));
                    }

                    // Fix point values (N/A when it is zero)
                    $values = explode(",",serializeDbField($tab, "pointsvalue"));
                    foreach ($values as $k=>$v) {
                        if (!(int)$v) $values[$k] = "N/A";

                    }

                    // Insert Transactions
                    $data = array(
                        'type'  => $catalogue,
                        'last'  => $last,
                        'value' => $values,
                        'total' => explode(",",serializeDbField($tab, "pointstotal"))
                    );

                    $document->cloneRow('TRANS', $data);
                    $document->saveAs($filenameOut);
                }
            }
        }
        $jobs->setJobPercent(100);
    }
    public function pointStatementsData($hhs,$date_from,$date_to) {
        // Connect to Eldorado
        $db = getOtherConnection('ELDORADO');
        
        // load the jobs controller
        require_once 'controllers/jobs.class.php';
        $jobs = new jobs();
        
        // Query Points Table
        $sql = "SELECT * FROM points ORDER BY interactiontypeID";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $points_id      = explode(",", serializeDbField($tab, "interactiontypeID"));
        $points_type    = explode(",", serializeDbField($tab, "interactiontype"));
        
        // cycle households
        $count = count($hhs);
        $current = 0;
        $jobs->setJobTitle("Collecting Points Data...");
        foreach ($hhs as $row=>$col) {
            $current++;
            $percent = (int)((99/$count)*$current);
            
            $jobs->setJobPercent($percent);
            
            $totalEarned = 0;
            // cycle point types
            foreach ($points_id as $k=>$v) {
                // fetch points
                $sql = "SELECT  CONVERT(DATE,MAX(dateearned),103) as dateearned,
                                SUM(pointsearned) as pointsearned
                        FROM pointstransaction p
                        WHERE       p.hhnum = ".$hhs[$row]["hhnum"]."
                                AND p.interactiontypeID = $v
                                AND p.dateearned >= '$date_from'
                                AND p.dateearned <= '$date_to'
                        GROUP BY dateearned,pointsearned";
                
                $res = $db->query($sql);
                $pts = $db->getTable($res);
                
                $hhs[$row][$points_type[$k]." Date"]  = (isset($pts[0])) ? date('j F Y',strtotime($pts[0]["dateearned"])):"";
                $hhs[$row][$points_type[$k]." Value"] = (isset($pts[0])) ? $pts[0]["pointsearned"]:0;
                $totalEarned += (int)$hhs[$row][$points_type[$k]." Value"];
            }
            // append point summaries
            $summary = $this->getPointSummaries($col["panelno"]);
            $hhs[$row]["period_earned"] = $totalEarned;
            $hhs[$row]["earned"]    = (isset($summary[0]))  ? $summary[0]["pointsearned"]:0;
            $hhs[$row]["redeemed"]  = (isset($summary[0]))  ? $summary[0]["pointsredeemed"]:0;
            $hhs[$row]["current"]   = (isset($summary[0]))  ? $summary[0]["currentpoints"]:0;
            
            // fix install and daily date
            $hhs[$row]["installdate"] = date('j F Y',strtotime($hhs[$row]["installdate"]));
            $hhs[$row]["Daily_Date"] = date('j F Y',strtotime($hhs[$row]["Daily_Date"]));
        }
        
        return $hhs;
    }
    public function synchPointData() {
        $db = getOtherConnection('ELDORADO_TEST');
        $mail = new mailer();
        $filename = "/tmp/synchPointData.csv";
        $handle = fopen($filename,'w');
        $email  = false;
        
        // Gather data
        echo "Gathering data...\n";
        $sql = "SELECT  h.householderbonusID,
                        h.hhnum,
                        b.pointsearned,
                        b.pointsredeemed,
                        b.currentpoints,
                        b.dailypointstotal,
                        (
                            SELECT SUM(pointsearned) as pointsearned 
                            FROM pointstransaction p1
                            WHERE p1.hhnum = h.hhnum
                        ) as sum_earned,
                        (
                            SELECT SUM(pointsvalue) as pointsvalue
                            FROM pointsredeemed p2
                            WHERE p2.hhnum = h.hhnum
                        ) as sum_redeemed
                FROM householder h
                INNER JOIN householderbonus b ON h.hhnum=b.hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        // Clean
        echo "Cleaning...\n";
        fwrite($handle,"hhnum,pointsearned,pointsredeemed,currentpoints,dailypointstotal,sum_earned,sum_redeemed");
        
        foreach ($tab as $item) {
            if ((int)$item["currentpoints"] != (((int)$item["sum_earned"]+(int)$item["dailypointstotal"])-(int)$item["sum_redeemed"])) {
                // a problem entry!
                $email = true;
                
                echo "Cleaning for ".$item["hhnum"]."...\n";
                fwrite($handle,"\r\n".$item["hhnum"].",".$item["pointsearned"].",".$item["pointsredeemed"].",".$item["currentpoints"].",".$item["dailypointstotal"].",".$item["sum_earned"].",".$item["sum_earned"].",");
                
                $pointsearned       = (int)$item["sum_earned"];
                $pointsredeemd      = (int)$item["sum_redeemed"];
                $currentpoints      = (((int)$item["sum_earned"]+(int)$item["dailypointstotal"]) - (int)$item["sum_redeemed"]);
                $householderbonusID = (int)$item["householderbonusID"];
                
                $sql = "UPDATE householderbonus 
                        SET currentpoints   = $currentpoints, 
                            pointsearned    = $pointsearned, 
                            pointsredeemed  = $pointsredeemd 
                        WHERE householderbonusID = $householderbonusID";
                $db->query($sql);
            }
        }
        
        fclose($handle);
        // Email if issues found
        if ($email) {
            $mail->attachfile($filename);
            $mail->bodytext("Issues found with point differences - attached.");
            $mail->sendmail("jesse.bryant@nielsen.com","Point Data Differences");
        }
        unlink($filename);
    }
    
    public function exportActivities() {
        $dash = new dashboard();
        $post = $dash->getPost();
        $down = new download();
        
        $db = getOtherConnection('ELDORADO');
        
        $from   = $post["from-date"];
        $to     = $post["to-date"];
        $type   = $post["response-type"];
        
        $sql = "SELECT  hhnum,
                        c.Comments,
                        UserName,
                        responsedate
                FROM callstatus c
                INNER JOIN householder h ON h.householderid = c.householderid
                WHERE   callstatus = $type
                        AND responsedate >= '$from 00:00:00'
                        AND responsedate <= '$to 23:59:59'";
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $tab = cleanRes($tab, "\r\n");
        
        $filename   = "/tmp/".time();
        exportResToXLS($filename, $tab);
        $down->output_file($filename, "Activities Report.xls", "application/xls");
    }
    public function exportRedemption() {
        $dash = new dashboard();
        $post = $dash->getPost();
        $down = new download();
        
        $db = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        
        $from   = $post["from-date"];
        $to     = $post["to-date"];
        $sql = "SELECT	requestdate,
                        contactname,
                        h.panelno,
                        p.hhnum,
                        c.catalogueno,
                        c.description
                FROM pointsredeemed p
                INNER JOIN householder h ON p.hhnum=h.hhnum
                INNER JOIN catalogue c on p.catalogueno=c.catalogueno
                WHERE requestdate >= '$from 00:00:01' AND requestdate <= '$to 23:59:59'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        // Get CUSTOM8 values
        $panels = serializeDbField($tab, "panelno");
        $sql    = "SELECT PANEL,CUSTOM8 FROM TFAM WHERE PANEL IN ($panels)";
        $res    = $db_pol->query($sql);
        $pol    = $db_pol->getTable($res);
        
        for($i=0;$i<count($tab);$i++) {
            $tab[$i]["panel_type"] = ((int)$pol[getArrayIndex($pol, "PANEL", $tab[$i]["panelno"])]["CUSTOM8"]==2) ? "iPanel":"Live";
        }
        
        $filename   = "/tmp/".time();
        exportResToCSV($filename, $tab);
        $down->output_file($filename, "Gift Export.csv", "text/csv");
    }
    public function exportTransactions() {
        $dash = new dashboard();
        $post = $dash->getPost();
        $down = new download();
        $db = getOtherConnection('ELDORADO');
        
        $from   = $post["from-date"];
        $to     = $post["to-date"];
        
        $sql = "SELECT * FROM (
                 SELECT  pointsredeemedid as id,
                         CONVERT(DATE,requestdate,101) as action_date,
                         'Redemption' as action_type,
                         contactname,
                         h.panelno,
                         p.hhnum,
                         '' as credit,
                         p.pointsvalue as debit,
                         c.catalogueno,
                         c.description
                 FROM pointsredeemed p
                 INNER JOIN householder h ON p.hhnum=h.hhnum
                 INNER JOIN catalogue c on p.catalogueno=c.catalogueno
                 WHERE p.requestdate >= '$from 00:00:00' AND p.requestdate <= '$to 23:59:59'
                 UNION
                 SELECT  pointstransactid as id,
                         CONVERT(DATE,pt.postingdate,101) as action_date,
                         p.interactiontype as action_type,
                         pt.username as contactname,
                         h.panelno,
                         pt.hhnum,
                         pt.pointsearned as credit,
                         '' as debit,
                         '' as catalogueno,
                         pt.description
                 FROM pointstransaction pt 
                 INNER JOIN points p ON pt.interactiontypeID=p.interactiontypeID 
                 INNER JOIN householder h on pt.hhnum = h.hhnum
                 WHERE pt.postingdate>= '$from' AND pt.postingdate<= '$to'
                ) as m
                ORDER BY panelno, m.action_date DESC;";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $filename   = "/tmp/".time();
        exportResToCSV($filename, $tab);
        $down->output_file($filename, "Transaction Export.csv", "text/csv");
    }
    public function exportStatement() {
        $down = new download();
        $db = getOtherConnection('ELDORADO');
        
        $sql = "SELECT  h.hhnum,
                        h.panelno,
                        postaladdressmailouts,
                        postaladdress1,
                        postaladdress2,
                        postalsuburb,
                        postalstate,
                        postalpostcode,
                        installdate,
                        tvs,
                        meters,
                        Members,
                        (
                            SELECT SUM(pointsearned)
                            FROM pointstransaction p1
                            WHERE p1.hhnum = h.hhnum
                        ) as pointsearned,
                        b.dailypointstotal,
                        (
                            ((
                                SELECT SUM(pointsearned)
                                FROM pointstransaction p2
                                WHERE p2.hhnum = h.hhnum
                            ) + 
                            (b.dailypointstotal))
                        ) as currentpoints,
                        (
                            SELECT SUM(pointsvalue)
                            FROM pointsredeemed p4
                            WHERE p4.hhnum = h.hhnum
                        ) as pointsredeemed
                        
                FROM householder h 
                LEFT JOIN householderbonus b ON h.hhnum = b.hhnum
                LEFT JOIN region r ON h.regionid = r.regionid
                LEFT JOIN city c  ON r.cityid = c.cityid
                LEFT JOIN state s ON c.stateid = s.stateid
                WHERE   h.hhnum = b.hhnum 
                        and b.installdate is not null
                        and (
                            b.pointsearned <> 0 
                            or b.currentpoints <> 0 
                            or b.pointsredeemed <> 0
                        )
                        and lastcallstatus = 2
                ORDER BY b.installdate, convert(int,h.panelno)";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $filename   = "/tmp/".time();
        exportResToCSV($filename, $tab);
        $down->output_file($filename, "Statement Export.csv", "text/csv");
    }
    
    public function getActivities() {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT statusid,statusdesc FROM status WHERE statuscode = 'callback' ORDER BY statusdesc";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getDailyUpdate($hhnum) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT CONVERT(date,lastdailydate,103) as lastdailydate FROM householderbonus WHERE hhnum = $hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        if (isset($tab[0]["lastdailydate"])) echo $tab[0]["lastdailydate"];
    }
    public function getPointsDaily($hhnum) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT SUM(dailypointstotal) as dailypointstotal FROM householderbonus WHERE hhnum = $hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        if (isset($tab[0]["dailypointstotal"])) echo $tab[0]["dailypointstotal"];
    }
    public function getPointsEarned($hhnum) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT SUM(pointsearned) as pointsearned FROM pointstransaction WHERE hhnum = $hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        if (isset($tab[0]["pointsearned"])) echo $tab[0]["pointsearned"];
    }
    public function getPointsRedeemed($hhnum) {
        $db = getOtherConnection('ELDORADO');
        $sql = "SELECT SUM(pointsvalue) as pointsvalue FROM pointsredeemed WHERE hhnum = $hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        if (isset($tab[0]["pointsvalue"])) echo $tab[0]["pointsvalue"];
    }
    public function getPointsSummary($panel) {
        // Get Eldorado Data
        $sql = "SELECT * FROM (
                    SELECT	h.panelno,
                            h.hhnum,
                            p.interactiontype,
                            p.pointsvalue,
                            'credit' as transactiontype,
                            SUM(pt.pointsearned) as pointstotal,
                            MAX(pt.dateearned) as lastdate
                    FROM pointstransaction pt
                    INNER JOIN points p ON pt.interactiontypeID = p.interactiontypeID
                    INNER JOIN householder h ON pt.hhnum = h.hhnum
                    WHERE h.panelno = $panel
                    GROUP BY    h.panelno,
                                h.hhnum,
                                p.interactiontype,
                                p.pointsvalue
                    UNION
                    SELECT  h.panelno,
                            h.hhnum,
                            c.description as interactiontype,
                            pr.pointsvalue,
                            'debit' as transactiontype,
                            pr.pointsvalue as pointstotal,
                            pr.redeemeddate as lastdate
                    FROM pointsredeemed pr
                    INNER JOIN householder h ON pr.hhnum = h.hhnum
                    INNER JOIN catalogue c ON pr.catalogueno = c.catalogueno
                    WHERE h.panelno = $panel
                    UNION
                    SELECT	h.panelno,
                            h.hhnum,
                            'Daily Points' as interactiontype,
                            10 as pointsvalue,
                            'credit' as transactiontype,
                            b.dailypointstotal as pointstotal,
                            b.lastdailydate as lastdate
                    FROM householderbonus b
                    INNER JOIN householder h ON h.hhnum = b.hhnum
                    WHERE h.panelno = $panel
            ) as m
            ORDER BY m.panelno,m.transactiontype,m.lastdate DESC";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        return $tab;
    }
    
    public function panel() {
        $this->setView('gifts','_master.php');
    }
}
?>