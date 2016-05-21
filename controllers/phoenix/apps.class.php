<?php
class apps extends template {
    
    var $base_sql = "SELECT CONVERT(DATE,appdate,103) as date, 
                            CAST(CONVERT(TIME,apptime,103) as VARCHAR(8)) as time,
                            a.*,
                            h.*,
                            p.description as paneltype,
                            c.city,
                            t.name as techname,
                            t.Email as technician_email
                    FROM appointment a
                    INNER JOIN householder h ON a.householderid = h.householderid
                    INNER JOIN Panel_Type p ON h.paneltypeid = p.id
                    INNER JOIN region r ON h.regionid = r.regionid
                    INNER JOIN city c   ON r.cityid = c.cityid 
                    LEFT OUTER JOIN techname t ON a.techid = t.id ";
    
    public function index() {
        $this->setView('apps', '_master.php');
    }
    
    // private
    
    
    // public
    public function doAppReport($debug,$filts) {
        require_once 'controllers/householder.class.php';
        $householder = new householder();
        $db = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        
        error_reporting(0);
        
        if ($debug) echo "RUNNING IN DEBUG MODE\n\n";

        // Prepare output
        $style          = '<style type="text/css">table { border-spacing: 0px; } table tbody td { border-top: thin solid #ccc; padding: 2px 20px; }</style>';
        $filename       = "appreport.xls";
        $notify         = "david.white2@nielsen.com,shannon.terry@nielsen.com,nathalie.villain@nielsen.com,william.walker@nielsen.com,jesse.bryant@nielsen.com";

        // Get All Appointments
        $results = $this->getView3Appointments($filts);

        // Synch codes from Pollux if missing in Eldorado
        echo "Synching codes from Pollux...\n";
        $this->synchPolluxOpen($db_pol, $results);

        // Display
        echo "Writing to file...\n";

        $ignore_fields = array("date","forced","panelno","technician","postcode");

        foreach ($results as $a=>$b) {
            echo "New Worksheet...\n";
            // Mailing
            $mailer = new mailer();

            /*
            $handle = fopen("/tmp/$filename",'w');
            fwrite($handle,"$style<h1>Appointment Report</h1>\n");
            */

            //===============================================================
            // Extra Request, Multiple Tabbed XLS
            $xls        = new Spreadsheet_Excel_Writer("/tmp/appreport.xls");
            $xls->setVersion(8);

            $formatTitle = $xls->addFormat();
            $formatTitle->setBold();
            $formatTitle->setSize(18);

            $formatSummaryRegion = $xls->addFormat();
            $formatSummaryRegion->setBold();
            $formatSummaryRegion->setSize(14);

            $formatSummaryDate = $xls->addFormat();
            $formatSummaryDate->setBold();
            $formatSummaryDate->setSize(12);

            $formatFieldHeader = $xls->addFormat();
            $formatFieldHeader->setBold();
            $formatFieldHeader->setSize(12);
            $formatFieldHeader->setAlign('center');

            $formatFieldData = $xls->addFormat();
            $formatFieldData->setSize(10);
            $formatFieldData->setAlign('left');
            $formatFieldData->setBorder(1);
            $formatFieldData->setBorderColor('grey');

            $summary    = $xls->addWorksheet("Summary");

            $summary->setColumn(0, 1, 8);
            $summary->setColumn(2, 2, 18);
            $summary->setColumn(3, 3, 19);
            $summary->setColumn(4, 4, 4);
            $summary->setColumn(5, 5, 15);
            $summary->setColumn(6, 6, 14);
            $summary->setColumn(7, 7, 14);
            $summary->setColumn(8, 8, 17);
            $summary->setColumn(9, 14, 20);
            $summary->setColumn(15, 19, 16);
            $summary->setColumn(20, 24, 15);
            $summary->setColumn(26, 26, 25);

            // XLS Set Fields (starting from row 3 for the summary)
            $summary_row = 2;
            $current_row = array();
            //===============================================================

            //===============================================================
            // Summary header info
            $summary->write(0, 0, "Appointment Report",$formatTitle);
            $summary->write(2, 0, $a, $formatSummaryRegion);
            //===============================================================

            // Prepare Email Body
            $emailbody  = $style.'<h1>Appointment Report</h1><h3>'.$a.'</h3>'."\n";
            $to         = array();

            $csv_data = "date,time,forced,panel,name,technicians,tvs,suburb,tasks,postcode,phoneno,alternatephone,phonelines,secondary_status,technician,paneltype,meter_type,meter_version,combox_version,netsight_installed,beep_installed,ip_polling,3g_modem,install_type,hhnum,booked,ter,type,resch,sms,last_appointment,last_open,last_close,forcedapp,pcode\r\n";
            $csv_data_xls = str_replace("\r\n","",$csv_data);

            //fwrite($handle,"<h3>$a</h3>\n");
            // each date
            foreach ($b as $c=>$d) {
                echo "Date: $c...\n";
                $fields_temp = array();
                $fields_all = array();
                //===============================================================
                // If Date worksheet doesn't exist, add it
                $sheets = $xls->worksheets();
                if (!isset($sheets[$c]))    {
                                            $current = $xls->addWorksheet($c);
                                            $current->setColumn(0, 2, 10);
                                            $current->setColumn(3, 3, 19);
                                            $current->setColumn(4, 4, 17);
                                            $current->setColumn(5, 5, 5);
                                            $current->setColumn(6, 11, 15);
                                            $current->setColumn(12, 14, 7);
                                            $current->setColumn(15, 19, 16);
                                            $current->setColumn(20, 24, 15);
                }
                else                        $current = $sheets[$c];
                //===============================================================

                //===============================================================
                // Summary tab - Put the date in
                $summary_row += 2;
                $summary->write($summary_row, 0, date('l, F j, Y',strtotime($c)), $formatSummaryDate);
                $summary_row += 2;
                //===============================================================

                //===============================================================
                // Set the current date sheet row
                if (!isset($current_row[$c]))   $current_row[$c] =  0;
                else                            $current_row[$c] += 2;

                foreach (explode(",", $csv_data_xls) as $f) {
                    if (!in_array($f, $ignore_fields)) {
                        $fields_temp[$f] = "";
                    }
                    $fields_all[$f] = "";
                }
                //===============================================================

                //===============================================================
                // For this date, do the summary header fields ONCE
                $col_count = 0;
                foreach ($fields_temp as $k=>$v) {
                    if (!in_array($k, $ignore_fields)) {
                        $summary->write($summary_row, $col_count, $k, $formatFieldHeader);
                        $col_count++;
                    }
                }
                //===============================================================

                $emailbody .= "<h4>$c</h4><table><thead><tr><th>Time</th><th>Forced</th><th>HH Number</th><th>Panel No</th><th>Name</th><th>TV's</th><th>Booked</th><th>Suburb</th><th>Pcode</th><th>Phone No</th><th>Ter</th><th>Technician</th><th>Type</th><th>Tasks</th><th>Resch</th><th>PT</th><th>SMS</th><th>paneltype</th><th>meter_type</th><th>meter_version</th><th>netsight_installed</th><th>beep_installed</th><th>ip_polling</th><th>3g_modem</th><th>install_type</th> </tr>\n</thead>\n<tbody>\n";

                //fwrite($handle,"<h4>$c</h4><table><thead><tr><th>Time</th><th>Forced</th><th>HH Number</th><th>Panel No</th><th>Name</th><th>TV's</th><th>Booked</th><th>Suburb</th><th>Pcode</th><th>Phone No</th><th>Ter</th><th>Technician</th><th>Type</th><th>Tasks</th><th>Resch</th><th>PT</th><th>SMS</th><th>paneltype</th><th>meter_type</th><th>meter_version</th><th>netsight_installed</th><th>beep_installed</th><th>ip_polling</th><th>3g_modem</th><th>install_type</th> </tr>\n</thead>\n<tbody>\n");
                // each appointment
                foreach ($d as $appid=>$e) {
                    echo "HHNum: ".$e["hhnum"]."...\n";
                    $fields = $fields_temp;
                    $summary_row++;
                    

                    // Additional Comparisons / Highlights
                    $background     = "";
                    
                    $hhFirstNum     = (int)substr($e["hhnum"],0,1);
                    $newInstall     = (in_array("99", $e["tasks"])) ? true:false;
                    $phonelines     = (int)getSimpleDbVal($db, "householder", "phonelines", "hhnum", $e["hhnum"]);


                    if ($e["panel"]) {
                        $sec_status     = (int)getSimpleDbVal($db_pol, "TFAM", "SECSTATUS", "PANEL", $e["panel"]);
                        $polluxMetType  = (int)getSimpleDbVal($db_pol, "TFAM", "CUSTOM8", "PANEL", $e["panel"]);
                        $meter_version  = (float)getSimpleDbVal($db_pol, "TFAM", "MAP", "PANEL", $e["panel"]);
                        $combox_version = (float)getSimpleDbVal($db_pol, "TMET", "VERSION", "PANEL", $e["panel"],array("METID"=>0));
                        $meter_type     = getMeterType($meter_version);

                        $netsight_inst  = ((getInstallDate(     $db_pol, "NETSIGHT",    $e["panel"])) && (!getDisinstallDate($db_pol, "NETSIGHT", $e["panel"])))    ? true:false;
                        $beep_enabled   =   ((
                                               ((int)getSimpleDbVal($db_pol, "TEXTRATV", "VAL", "PANEL", $e["panel"],array("CODE"=>"'48'"))) ||
                                               ((int)getSimpleDbVal($db_pol, "TEXTRATV", "VAL", "PANEL", $e["panel"],array("CODE"=>"'50'")))
                                            ) && 
                                            (!(int)getSimpleDbVal(  $db_pol, "TEXTRATV", "VAL", "PANEL", $e["panel"],array("CODE"=>"'49'"))))                       ? true:false;
                        $ip_polling     = ((int)getSimpleDbVal( $db_pol, 'TFAMMETHW',   'VAL', 'PANEL', $e["panel"],array("CODE"=>"'57'","VAL"=>"'4'"))==4)         ? true:false;
                        $third_gen      = ((int)getSimpleDbVal( $db_pol, 'TFAMMETHW',   'VAL', 'PANEL', $e["panel"],array("CODE"=>"'58'","VAL"=>"'2'"))==2)         ? true:false;

                    }
                    else {
                        foreach (array("sec_status","polluxMetType","meter_version","netsight_inst","beep_enabled","ip_polling","third_gen","meter_version") as $key) {
                            $$key = "ERR_NO_PANEL_FOUND";
                        }
                    }

                    if      (($newInstall)          && ($hhFirstNum==4))        $background = "FFFEB8"; // Light Yellow
                    else if (($newInstall)          && ($hhFirstNum==8))        $background = "FFDE88"; // Light Orange
                    else if (($polluxMetType==2)    && ($meter_version==2.111)) $background = "00D0FF"; // Light Blue
                    else if (($polluxMetType==1)    && ($meter_version>=3))     $background = "B8FFB8"; // Light Green


                    // testing colours...
                    $emailbody .= '<tr style="background-color: #'.$background.'">';

                    //fwrite($handle,"<tr>");

                    // each value
                    foreach ($e as $k=>$v) {
                        echo "Getting value for $k...\n";
                        switch ($k) {
                            case "forcedapp":
                                $v = ($v=="1") ? "Yes":"No";
                                break;
                            case "hhnum":
                                /*
                                if ((int)substr($v,0,1)==4) {
                                    $v = "<strong>$v</strong>";
                                }
                                */
                                if ($e["meta"]["MeterTypeDesc"]=="TVM5") $v = "<u>$v</u>";
                                elseif ($e["meta"]["MeterTypeDesc"]=="UNITAM 3") $v = "<strong>$v</strong>";
                                break;
                            case "time":
                                $v = explode(" ",$v);
                                $v = str_replace(":00:000", "", $v[(count($v)-1)]);
                                /*
                                $time = $v;
                                $location = $e["meta"]["address2"].",".$e["meta"]["suburb"].",".$e["meta"]["postcode"];
                                $v = buildGoogleCalEvent("Test Calendar", $c, $v, $c, $v, $e["meta"]["repairnote"], $location);
                                $v = '<a href="'.$v.'">'.$time.'</a>';
                                */
                                break;
                            case "phonelines":
                                $v = $phonelines;
                                break;
                            case "secondary_status":
                                $v = $sec_status;
                                break;
                            case "booked":
                                $v = substr($v, 0,  (strlen($v)-15));
                                break;
                            case "resch":
                                $v = ($v=="1") ? "Yes":"No";
                                break;
                            case "sms":
                                $v = ($v=="1") ? "Yes":"No";
                                break;
                            case "paneltype":
                                $v = ($polluxMetType==2) ? "iPanel":"live";
                                break;
                            case "meter_type":
                                $v = $meter_type;
                                break;
                            case "meter_version":
                                $v = "$meter_version";
                                break;
                            case "combox_version":
                                $v = "$combox_version";
                                break;
                            case "netsight_installed":
                                $v = ($netsight_inst===true) ? "Yes":"No";
                                break;
                            case "beep_installed":
                                $v = ($beep_enabled===true) ? "Yes":"No";
                                break;
                            case "ip_polling":
                                $v = ($ip_polling===true) ? "Yes":"No";
                                break;
                            case "3g_modem":
                                $v = ($third_gen===true) ? "Yes":"No";
                                break;
                            case "install_type":
                                switch ($hhFirstNum) {
                                    case 4:
                                        $install_type = "iPanel";
                                        break;
                                    default:
                                        $install_type = "Live Panel";
                                        break;
                                }
                                $v .= "$install_type";
                                break;
                            default:
                                if (!is_array($v)) {
                                    $v = htmlentities($v);
                                }
                                else {
                                    $v = implode(",",$v);
                                }
                                break;
                        }
                        if ($k!="meta") {
                            $emailbody .= "<td>$v</td>\n";

                            $csv_val = str_replace(",", " & ", $v);

                            $csv_val = str_replace("<u>", "", $csv_val);
                            $csv_val = str_replace("</u>", "", $csv_val);
                            $csv_val = str_replace("<strong>", "", $csv_val);
                            $csv_val = str_replace("</strong>", "", $csv_val);

                            //$csv_data .= ",$csv_val";

                            //fwrite($handle,'<td style="text-align:center">'.$v.'</td>');

                            if ($k=="hhnum") {
                                //remove formatting
                                $v = $csv_val;
                            }

                            $fields[$k] = $v;
                            if (isset($fields_all[$k])) $fields_all[$k] = $v;
                        }
                    }

                    //===============================================================
                    // Write this to the xls, summary and dated.
                    echo "Writing to XLS...\n";
                    $col_count = 0;
                    foreach ($fields as $k=>$v) {
                        if (!in_array($k, $ignore_fields)) {
                            $summary->write($summary_row, $col_count, $v, $formatFieldData);

                            // Debug
                            //echo "\n$k / $v, at Row $summary_row / Col $col_count";

                            $col_count++;
                        }
                        //$csv_data .= "$v,";
                    }
                    foreach ($fields_all as $k=>$v) {
                        $csv_data .= '"'."$v".'",';
                    }
                    //---------------------------------------------------------------
                    $col_count = 0;
                    foreach ($fields as $k=>$v) {
                        if (!in_array($k, $ignore_fields)) {
                            $current->write($current_row[$c], $col_count, $k, $formatFieldHeader);
                            $col_count++;
                        }
                    }
                    $col_count = 0;
                    $current_row[$c]++;
                    foreach ($fields as $k=>$v) {
                        if (!in_array($k, $ignore_fields)) {
                            $current->write($current_row[$c], $col_count, $v, $formatFieldData);
                            $col_count++;
                        }
                    }
                    $current_row[$c] += 2;
                    //---------------------------------------------------------------
                    // Write to current the appointment Eldorado comments
                    echo "Fetching Comments...\n";
                    $comments = $householder->getAllComments($e["hhnum"],true);

                    // Add Full Address
                    $current->write($current_row[$c], 0, "Address");
                    
                    // apartment missing:
                    $address_line = ($e["meta"]["address1"]) ? $e["meta"]["address1"].", ":"";
                    $address_line .= $e["meta"]["address2"];
                    
                    $current->write($current_row[$c], 1, $address_line);
                    $current_row[$c]++;
                    $current->write($current_row[$c], 1, $e["meta"]["suburb"].", ".$e["meta"]["state"]." ".$e["meta"]["postcode"]);
                    $current_row[$c] +=2;

                    // Add Eldorado Comments
                    $current->write($current_row[$c], 0, "Eldo Comments");
                    $current_row[$c]++;
                    $i = 1;
                    foreach ($comments as $comm) {
                        if ($comm["type"]=="call_comment") {
                            $current->write($current_row[$c], 1, $i);
                            $current->write($current_row[$c], 2, $comm["date"].":".$comm["comment"]);
                            $current_row[$c]++;
                            $i++;
                            if ($i==5) break;
                        }
                    }
                    $current_row[$c]++;
                    //===============================================================
                    $emailbody .= "</tr>\n";

                    /* ==================================================================================
                    // Extra CSV fields!
                    // paneltype,meter_type,meter_version,netsight_installed,beep_installed,ip_polling,3g_modem,install_type

                    // paneltype
                    $csv_data .= ($polluxMetType==2) ? ",iPanel":",live";

                    // meter_type
                    $csv_data .= ",$meter_type";

                    // meter_version
                    $csv_data .= ",$meter_version";

                    // meter_version
                    $csv_data .= ",$combox_version";

                    // netsight_installed
                    $csv_data .= ($netsight_inst===true) ? ",Yes":",No";

                    // beep installed
                    $csv_data .= ($beep_enabled===true) ? ",Yes":",No";

                    // IP Polling
                    $csv_data .= ($ip_polling===true) ? ",Yes":",No";

                    // 3G Modem
                    $csv_data .= ($third_gen===true) ? ",Yes":",No";

                    // Install Type
                    switch ($hhFirstNum) {
                        case 4:
                            $install_type = "iPanel";
                            break;
                        case 8:
                            $install_type = "Mobile Only";
                            break;
                        default:
                            $install_type = "Live Panel";
                            break;
                    }
                    $csv_data .= ",$install_type";

                    // Last appointment, open & close
                    $csv_data .= ",$last_appoint,$last_open,$last_close";
                    
                    ================================================================================== */

                    //fwrite($handle,"</tr>\n");
                    echo "Fetching Technician Email...\n";
                    $tempTo = getSimpleDbVal($db, "techname", "Email", "id", getSimpleDbVal($db, "appointment", "techid", "appid", $appid));
                    if ((!in_array($tempTo, $to)) && ($tempTo!="na") && ($tempTo)) $to[] = $tempTo;
                    
                    $csv_data .= "\r\n";
                }
                $emailbody .= "</tbody>\n</table>\n";

                //fwrite($handle,"</tbody>\n</table>\n");

                $current->hideScreenGridlines();
            }
            //fclose($handle);

            // Write out CSV data
            $csv_filename = "/tmp/appointment_csv.csv";
            $csv_handle = fopen($csv_filename,'w');
            if ($csv_handle) {
                fwrite($csv_handle,$csv_data);
                fclose($csv_handle);

                $mailer->attachfile($csv_filename);
                //unlink ($csv_filename);
            }
            //===============================================================
            // Close the XLS
            $summary->hideScreenGridlines();
            $summary->freezePanes(array(0,9,10,0));
            $xls->close();
            //===============================================================

            echo "Emailing...\n";

            $system_owners =    "david.white2@nielsen.com,
                                nathalie.villain@nielsen.com,
                                Jishan.Hossain@nielsen.com,
                                derrin.clark@nielsen.com,
                                phil.clements@nielsen.com,
                                jesse.bryant@nielsen.com,
                                byron.waring@nielsen.com";

            $mailer->attachfile("/tmp/$filename");
            $mailer->attachfile("/tmp/appreport.xls");
            $mailer->bodytext("Hi,<br/><br/>Please find attached the current appointment report for regions in $a.<br/><br/>Regards,<br/>Phoenix&trade;");
            $sendmail = $mailer->sendmail(implode(",", $to).",$system_owners", "Appointment Report - $a");
            //$sendmail = $mailer->sendmail($system_owners, "Appointment Report - $a");
            //$sendmail = $mailer->sendmail("jesse.bryant@nielsen.com", "Appointment Report - $a");
            
            if (!$sendmail) {
                $last_error = error_get_last();
                echo $last_error["message"];
            }
            else echo "Mailed to:\n".implode(",", $to).",$system_owners\n";

            //unlink ("/tmp/appreport.xls");
            error_reporting(E_ALL & ~E_NOTICE);
        }
    }
    public function doSend102Report($appointments,$notify) {
        require_once 'controllers/householder.class.php';
        $householder    = new householder();
        $db             = getOtherConnection('ELDORADO');
        $db_pol         = getPolluxConnection();
        
        $beepReport = "/tmp/beepReport.csv";
        $beepHandle = fopen($beepReport,'w');
        $notifyAtt  = false;
        $debug      = false;

        if ($beepHandle) fwrite($beepHandle,"panel,hhnum,structure,members,GB Age");

        foreach ($appointments as $region=>$a) {
            foreach ($a as $date=>$b) {
                $strtotime = strtotime($date);
                foreach ($b as $d=>$vals) {
                    $panel = $vals["panel"];
                    
                    $sql = "SELECT paneltypeid
                            FROM householder
                            WHERE panelno = $panel";
                    $res = $db->query($sql);
                    $tab = $db->getTable($res);
                    
                    $paneltypeid =   (isset($tab[0]["paneltypeid"])) ? (int)$tab[0]["paneltypeid"]:0;
                    $structure   =   (int)$householder->getStructure($vals["hhnum"],true);
                    $athome      =   (int)getSimpleDbVal($db, "householder", "athome", "hhnum", $vals["hhnum"]);
                    $ageresp     =   (int)getSimpleDbVal($db, "householder", "ageresp", "hhnum", $vals["hhnum"]);

                    if (
                            (
                                ((($structure==8) || ($structure==9) || ($structure==10)) && ($athome>2)) ||
                                ((($ageresp >= 18) && ($ageresp <= 39)) && ($athome == 2)) ||
                                ($paneltypeid==18)
                            )
                            && (in_array(99, $appointments[$region][$date][$d]["tasks"])) && (!in_array(102, $appointments[$region][$date][$d]["tasks"]))
                            /*
                            (
                                ((($structure==8) || ($structure==9) || ($structure==10)) && ($athome>2)) ||
                                ((($ageresp >= 18) && ($ageresp <= 39)) && ($athome == 2))
                            )
                            && (in_array(99, $appointments[$region][$date][$d]["tasks"])) && (!in_array(102, $appointments[$region][$date][$d]["tasks"]))
                            */
                        ) {
                        // Add to the database
                        // taskid for 102 is 112
                        $sql = "INSERT INTO appointmenttasks (appid,taskid) VALUES ($d,112)";
                        if (!$debug) $res = $db->query($sql);
                        else echo "$sql\n";

                        // Add to Pollux!
                        if ($appointments[$region][$date][$d]["panel"]) {
                            // Find the record (to find the spare field)
                            $sql = "SELECT * FROM TTECVREQ WHERE PANEL = ".$appointments[$region][$date][$d]["panel"]." AND APPOINTDATE = '".$strtotime."'";
                            $res = $db_pol->query($sql);
                            $tab = $db_pol->getTable($res);
                            for ($i=0;$i<7;$i++) {
                                $field = "REQUESTTYPE";
                                if ($i) $field .= ($i+1);
                                if (!$tab[0][$field]) break;
                            }
                            // Update the last empty field
                            $sql = "UPDATE SET $field = '102' WHERE PANEL = ".$appointments[$region][$date][$d]["panel"]." AND APPOINTDATE = '".$strtotime."'";
                            $db_pol->query($sql);
                        }

                        // Add to the array
                        $appointments[$region][$date][$d]["tasks"][] = 102;

                        // Add to Report
                        if ($beepHandle) fwrite($beepHandle,"\r\n".$vals["panel"].",".$vals["hhnum"].",$structure,$athome,$ageresp");

                        $notifyAtt = true;
                    }
                }
            }
        }
        if ($beepHandle) fclose($beepHandle);

        // Send 102 Notification
        echo "Sending 102 notification...\n";

        $mail = new mailer();
        if ($notifyAtt) {
            $mail->attachfile($beepReport);
            $mail->bodytext("Please find the attached report.");
        }
        else $mail->bodytext("Phoenix did not need to add any 102 codes in this execution.");
        $mail->sendmail($notify, "Phoenix Appointments: 102 Report");
        unlink($beepReport);
    }
    public function doSend67Report($appointments,$notify) {
        $db             = getOtherConnection('ELDORADO');
        $db_pol         = getPolluxConnection();
        
        $beepReport = "/tmp/iPanelReport.csv";
        $beepHandle = fopen($beepReport,'w');
        $notifyAtt  = false;
        $debug      = false;

        if ($beepHandle) fwrite($beepHandle,"panel,hhnum");

        foreach ($appointments as $region=>$a) {
            foreach ($a as $date=>$b) {
                $strtotime = strtotime($date);
                foreach ($b as $d=>$vals) {
                    $panel = $vals["panel"];
                    
                    $sql = "SELECT paneltypeid
                            FROM householder
                            WHERE panelno = $panel";
                    $res = $db->query($sql);
                    $tab = $db->getTable($res);
                    $paneltypeid = (isset($tab[0]["paneltypeid"])) ? (int)$tab[0]["paneltypeid"]:0;
                    if (
                            ($paneltypeid==18) &&
                            (in_array(99, $appointments[$region][$date][$d]["tasks"])) && (!in_array(67, $appointments[$region][$date][$d]["tasks"]))
                        ) {
                        // Add to the database
                        // taskid for 67 is 114
                        $sql = "INSERT INTO appointmenttasks (appid,taskid) VALUES ($d,114)";
                        if (!$debug) $res = $db->query($sql);
                        else echo "$sql\n";

                        // Add to Pollux!
                        if ($appointments[$region][$date][$d]["panel"]) {
                            // Find the record (to find the spare field)
                            $sql = "SELECT * FROM TTECVREQ WHERE PANEL = ".$appointments[$region][$date][$d]["panel"]." AND APPOINTDATE = '".$strtotime."'";
                            $res = $db_pol->query($sql);
                            $tab = $db_pol->getTable($res);
                            for ($i=0;$i<7;$i++) {
                                $field = "REQUESTTYPE";
                                if ($i) $field .= ($i+1);
                                if (!$tab[0][$field]) break;
                            }
                            // Update the last empty field
                            $sql = "UPDATE SET $field = '67' WHERE PANEL = ".$appointments[$region][$date][$d]["panel"]." AND APPOINTDATE = '".$strtotime."'";
                            $db_pol->query($sql);
                        }

                        // Add to the array
                        $appointments[$region][$date][$d]["tasks"][] = 67;

                        // Add to Report
                        if ($beepHandle) fwrite($beepHandle,"\r\n".$vals["panel"].",".$vals["hhnum"].",$structure,$athome,$ageresp");

                        $notifyAtt = true;
                    }
                }
            }
        }
        if ($beepHandle) fclose($beepHandle);

        // Send 102 Notification
        echo "Sending 67 notification...\n";

        $mail = new mailer();
        if ($notifyAtt) {
            $mail->attachfile($beepReport);
            $mail->bodytext("Please find the attached report.");
        }
        else $mail->bodytext("Phoenix did not need to add any 67 codes in this execution.");
        $mail->sendmail($notify, "Phoenix Appointments: 67 Report");
        unlink($beepReport);
    }
    public function doSendMissingHIS() {
        $return = array();
        $db_pol = getPolluxConnection();
        $db_eld = getOtherConnection('ELDORADO');
        $db_tvp = getOtherConnection('TVPANEL');
        $apps   = $this;
        $owners = array("nathalie.villain@nielsen.com",
                        "nick.mcnamara@nielsen.com",
                        "kratika.rathi@nielsen.com",
                        "jesse.bryant@nielsen.com");
        
        // Find all PANELS which have had an appointment (99 or 90) within the past 15 days.
        $app_from   = date('Y-m-d',strtotime('-15 days'));
        $app_to     = date('Y-m-d',strtotime('+1 day'));
        $app_from_int   = date('Ymd',strtotime('-15 days'));
        $app_to_int     = date('Ymd',strtotime('+1 day'));
        
        $sql = "SELECT  PANEL,
                        VISITDATE
                FROM TTECVREQ
                WHERE       VISITDATE >= $app_from_int
                        AND VISITDATE <  $app_to_int";
        $res = $db_pol->query($sql);
        $tab = $db_pol->getTable($res);
        
        foreach ($tab as $item) {
            $panel      = $item["PANEL"];
            $appdate    = substr($item["VISITDATE"],0,4)."-".substr($item["VISITDATE"],4,2)."-".substr($item["VISITDATE"],6,2);
            $hhnum      = getSimpleDbVal($db_eld, "householder", "hhnum", "panelno", $panel);
            if (!$hhnum) continue;
            
            $opencodes  = $apps->getOpenCodes($hhnum, $appdate);
            $closecodes = $apps->getCloseCodes($panel,$appdate);
            
            if (((is_array($opencodes)) && ((in_array("90", $opencodes)) || (in_array("99", $opencodes)))) && 
                ((is_array($closecodes)) && (in_array("982", $closecodes)))) {
                // Was a survey done on or after this date (yet)
                $sql = "SELECT * 
                        FROM survey_participants
                        WHERE       panelno = $panel
                                AND date >= '$appdate'";
                $res = $db_tvp->query($sql);
                $num = $db_tvp->getNumRows($res);
                if (!$num) {
                    // get the rest of the appointment info from Eldorado
                    $appid      = $apps->getAppId($db_eld, $hhnum, $appdate);
                    $tech_id    = (int)getSimpleDbVal($db_eld, "appointment", "techid", "appid", $appid);
                    $tech_name  = getSimpleDbVal($db_eld, "techname", "name", "id", $tech_id);
                    $tech_email = getSimpleDbVal($db_eld, "techname", "email", "id", $tech_id);
                
                    $now = time();
                    $then = strtotime($appdate);
                    $diff = $now - $then;
                    $age  = floor($diff/(60*60*24));
                    $return[] = array("Panel"=>$panel,"Appointment Date"=>$appdate,"Age"=>$age,"Technician"=>$tech_name,"Email"=>$tech_email);
                }
            }
        }
        
        // Sort
        $return = sortArray($return, "Age", "number");
        
        exportResToXLS("/tmp/SurveysDue.xls", $return);
        $mail = new mailer();
        $mail->attachfile("/tmp/SurveysDue.xls");
        $mail->bodytext("The report for missing HIS surveys is attached.");
        $mail->sendmail(serializeArray($owners), "Surveys Due Report");
        unlink("/tmp/SurveysDue.xls");
    }
    
    public function getAdhocAppDetail($appid,$field) {
        if ($appid) {
            $db = getOtherConnection('ELDORADO');

            $sql = $this->base_sql."WHERE a.appid = $appid";
            $res = $db->query($sql);
            $tab = $db->getTable($res);

            $result = (isset($tab[0][$field])) ? $tab[0][$field]:"";
        }
        else $result = "";
        return $result;
    }
    public function getAllAppointments($panel) {
        $db  = getOtherConnection('ELDORADO');
        $sql = $this->base_sql."WHERE panelno = $panel";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        return $tab;
    }
    public function getAppId($con,$hhnum,$appdate) {
        $sql = "SELECT appid
                FROM appointment a
                INNER JOIN householder h ON h.householderid = a.householderid
                WHERE       h.hhnum = $hhnum
                        AND a.appdate = '$appdate'";
        $res = $con->query($sql);
        $tab = $con->getTable($res);
        $appid = (isset($tab[0])) ? (int)$tab[0]["appid"]:0;
        
        return $appid;
    }
    public function getCloseCodes($panel,$appdate) {
        // Only from pollux
        $db = getPolluxConnection();
        
        $appdate = str_replace("-","",$appdate);
        
        $sql = "SELECT DETTYPE 
                FROM TECVDET 
                WHERE   PANEL = $panel 
                        AND VISITDATE = $appdate";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0])) {
            $result = explode(",",serializeDbField($tab, "DETTYPE"));
            return $result;
        }
        else return false;
    }
    public function getOpenCodes($hhnum,$appdate,$panel=0) {
        $db_pol = getPolluxConnection();
        $db_eld = getOtherConnection('ELDORADO');
        
        $pollux_date = ($appdate) ? str_replace("-", "", $appdate):date('Ymd',time());
        $panel = ($panel) ? $panel:(int)getSimpleDbVal($db_eld, "householder", "panelno", "hhnum", $hhnum);
        $oc = array();
        
        // pollux 
        if ($panel) {
            $sql = "SELECT *
                    FROM TTECVREQ
                    WHERE   APPOINTDATE=$pollux_date
                            AND PANEL = $panel";
            $res = $db_pol->query($sql);
            $tab = $db_pol->getTable($res);
            
            //echo "This Pollux Query: $sql\n";

            foreach ($tab as $item) { 
                $field = "REQUESTTYPE";
                for ($i=0;$i<7;$i++) {
                    $rt = ($i) ? ($i+1):"";
                    $field_name = $field.$rt;
                    //echo "Checking field: $field_name\n";
                    
                    if ((isset($item[$field_name])) && ((int)$item[$field_name])) $oc[] = (int)$item[$field_name];
                }
            }
        }
        
        // Eldorado
        $sql = "SELECT t.taskcode as open_code
                FROM appointmenttasks at
                INNER JOIN task t        ON t.id = at.taskid
                INNER JOIN appointment a ON a.appid = at.appid
                INNER JOIN householder h ON h.householderid = a.householderid
                WHERE       h.hhnum = $hhnum
                        AND a.appdate = '$appdate'";
        
        //echo "$sql<br/><br/>";
        
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        
        foreach ($tab as $item) {
            $oc[] = (int)$item["open_code"];
        }
        
        $oc = array_unique($oc);
        
        return $oc;
    }
    public function getPreviousApp($hhnum,$appdate) {
        $db = getOtherConnection('ELDORADO');
        
        $sql = "SELECT MAX(appid) AS appid
                FROM appointment a
                INNER JOIN householder h ON a.householderid = h.householderid
                WHERE hhnum = $hhnum AND appdate < '$appdate'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        $result = (isset($tab[0]["appid"])) ? $tab[0]["appid"]:"";
        return $result;
    }
    public function getView3Appointments($filts) {
        echo "Connecting...\n";
        $db     = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        echo "Gathering Data...\n";
        $sql = "SELECT *
                FROM View3
                WHERE appdate > '".date('Y-m-d',time())."' ";
        if ($filts) $sql .= "AND $filts ";

        $sql .="ORDER BY    appdate,
                            apptime,
                            code";
        $res = $db->query($sql);
        $tab = $db->getTable($res);

        // Sort
        echo "Sorting...\n";
        $current = "";
        $counter = 0;
        $results    = array();
        $tasks      = array();
        $techs      = array();
        foreach ($tab as $item) {
            
            $panel = $item["panelno"];
            $hhnum = $item["hhnum"];
            // Correct the state
            $sql = "SELECT state
                    FROM householder
                    WHERE hhnum = $hhnum";
            $res = $db->query($sql);
            $tab_state = $db->getTable($res);
            $state = $tab_state[0]["state"];

            //$region_name = $this->getRegion($db, $item["panelno"]);
            $region_name = $this->getRegionSimple($item["panelno"]);
            if (!$region_name) $region  = trim($item["city"]).", $state";
            else $region                = $region_name.", $state";
            $date = substr($item["appdate"], 0, (strlen($item["appdate"])-15));
            $appid = $item["appid"];
            
            /*
            echo "HHNum: ".$item["hhnum"]."\n";
            echo "App Date: ".date('Y-m-d',strtotime($date))."\n";
            echo "Previous: ".$this->getPreviousApp($item["hhnum"], date('Y-m-d',strtotime($date)))."\n";
            echo "Adhoc: ".date('Y-m-d',strtotime($this->getAdhocAppDetail($this->getPreviousApp($item["hhnum"], date('Y-m-d',strtotime($date))),"appdate")));
            die();
            */
            $last_appointment = date('Y-m-d',
                                    strtotime(
                                        $this->getAdhocAppDetail(
                                                $this->getPreviousApp(
                                                    $item["hhnum"], 
                                                    date('Y-m-d',strtotime($date))
                                                ),
                                                "appdate"
                                            )
                                        )
                                    );
            if ($last_appointment!="1970-01-01") {
                $last_open  = $this->getOpenCodes($item["hhnum"], $last_appointment);
                $last_close = $this->getCloseCodes($item["panelno"], $last_appointment);
                if ($last_open) $last_open = implode(',',$last_open);
                else $last_open = "";
                if ($last_close) $last_close = implode(',',$last_close);
                else $last_close = "";
            }
            else {
                $last_appointment = "";
                $last_open = "";
                $last_close = "";
            }
            
            //echo "Last Appointment: $last_appointment\n";
            //echo "Last Open: $last_open\n";
            //echo "Last Close: $last_close\n";
            
            if (!isset($results[$region][$date][$appid])) $results[$region][$date][$appid] = array( "time"                  =>	$item["apptime"],
                                                                                                    "forcedapp"             =>	$item["forcedapp"],
                                                                                                    "panel"                 =>	$item["panelno"],
                                                                                                    "name"                  =>	$item["housename"],
                                                                                                    "technicians"           =>	array(),
                                                                                                    "tvs"                   =>	$item["tv"],
                                                                                                    "suburb"                =>	$item["suburb"],
                                                                                                    "tasks"                 =>	array(),
                                                                                                    "pcode"                 =>	$item["postcode"],
                                                                                                    "phoneno"               =>	$item["phoneno"],
                                                                                                    "alternatephone"        =>	$item["alternatephone"],
                                                                                                    "phonelines"            =>  0,
                                                                                                    // dynamic fields:
                                                                                                    "secondary_status"      =>  "",
                                                                                                    "paneltype"             =>	"",
                                                                                                    "meter_type"            =>	"",
                                                                                                    "meter_version"         =>	"",
                                                                                                    "combox_version"        =>	"",
                                                                                                    "netsight_installed"    =>	"",
                                                                                                    "beep_installed"        =>	"",
                                                                                                    "ip_polling"            =>	"",
                                                                                                    "3g_modem"              =>	"",
                                                                                                    "install_type"          =>	"",
                                                                                                    // more dynamic - last appt details
                                                                                                    "last_appointment"      => $last_appointment,
                                                                                                    "last_open"             => $last_open,
                                                                                                    "last_close"            => $last_close,
                                                                                                    // back to static
                                                                                                    "hhnum"                 =>	$item["hhnum"],
                                                                                                    "booked"                =>	$item["lastcalldate"],
                                                                                                    "ter"                   =>	$item["code"],
                                                                                                    "type"                  =>	$item["apptype"],
                                                                                                    "resch"                 =>	$item["RescheduleApp"],
                                                                                                    "sms"                   =>	$item["sms_permission"],
                                                                                                    // meta (ignored)
                                                                                                    "meta"=>$item);

            if (!in_array($item["taskcode"],$results[$region][$date][$appid]["tasks"])) $results[$region][$date][$appid]["tasks"][] = (int)$item["taskcode"];

            if (!in_array(trim($item["techname"]),$results[$region][$date][$appid]["technicians"])) $results[$region][$date][$appid]["technicians"][] = trim($item["techname"]);
        }
        //die();
        return $results;
    }
    
    public function view($id) {
        $this->setView('apps/view', '_master.php');
    }
    public function listapps($date="") {
        $db = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        $pollux_date = ($date) ? str_replace("-", "", $date):date('Ymd',time());
        
        $sql = "SELECT  CONVERT(DATE,appdate,103) as date, 
                        CAST(CONVERT(TIME,apptime,103) as VARCHAR(8)) as time,
                        a.*,
                        h.*,
                        c.city,
                        t.name as techname
                FROM appointment a
                INNER JOIN householder h ON a.householderid = h.householderid
                INNER JOIN region r ON h.regionid = r.regionid
                INNER JOIN city c   ON r.cityid = c.cityid 
                LEFT OUTER JOIN techname t ON a.techid = t.id 
                WHERE a.appdate = '$date'";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        foreach ($tab as $k => $v) {
            $hhnum = $tab[$k]["hhnum"];
            
            // Attempt to find the panel #
            $sql = "SELECT panelno FROM householder WHERE hhnum = $hhnum";
            $res = $db->query($sql);
            $tabB = $db->getTable($res);
            $pan = ((isset($tabB[0]["panelno"])) && ((int)$tabB[0]["panelno"])) ? $tabB[0]["panelno"]:0;
            
            // Previous Details (if any)
            $tab[$k]["previous_app"]    = $this->getPreviousApp($hhnum, $date);
            $tab[$k]["previous_date"]   = ($tab[$k]["previous_app"]) ? $this->getAdhocAppDetail($tab[$k]["previous_app"], "date"):"";
            $tab[$k]["previous_open"]   = ($tab[$k]["previous_app"]) ? $this->getOpenCodes($hhnum, $this->getAdhocAppDetail($tab[$k]["previous_app"], "date")):"";
            $tab[$k]["previous_close"]  = ($tab[$k]["previous_app"]) ? $this->getCloseCodes($pan, str_replace("-", "", $this->getAdhocAppDetail($tab[$k]["previous_app"], "date"))):"";
            $tab[$k]["previous_tech"]   = ($tab[$k]["previous_app"]) ? $this->getAdhocAppDetail($tab[$k]["previous_app"], "techname"):"";
        
            // Collate all open & close codes
            $tab[$k]["open_codes"]  = $this->getOpenCodes($hhnum, $date,$pan);
            $tab[$k]["close_codes"] = $this->getCloseCodes($pan, $pollux_date);
            
            // Attempt to get extended Pollux data
            if ($pan) {
                $sql = "SELECT *
                        FROM TTECVREQ
                        WHERE   APPOINTDATE = $pollux_date
                                AND PANEL = ".$pan;
                $res = $db_pol->query($sql);
                $pol = $db_pol->getTable($res);
                if (isset($pol[0])) foreach ($pol[0] as $a=>$b) $tab[$k][$a] = $b;
            
                // All other miscellaneous information
                $panel_type = getPanelType($db_pol, $pan);
                $meter_vers = (float)getSimpleDbVal($db_pol, "TFAM", "MAP", "PANEL", $pan);
                $meter_type = getMeterType($meter_vers);

                $tab[$k]["panel_type"] = $panel_type;
                $tab[$k]["meter_type"] = $meter_type;
            }
        }
        
        $jsn = json_encode($tab);
        echo $jsn;
    }

    public function getapp($id) {
        $db = getOtherConnection('ELDORADO');
        
        $sql = $this->base_sql."WHERE a.appid = $id";
        
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        // Also get open/close codes for this item
        if (isset($tab[0])) $tab[0]["open_codes"] = $this->getOpenCodes ($tab[0]["hhnum"], $tab[0]["date"]);
        if (isset($tab[0])) $tab[0]["close_codes"] = $this->getCloseCodes ($tab[0]["panelno"], str_replace("-","",$tab[0]["date"]));
        
        $jsn = json_encode($tab);
        echo $jsn;
    }
    public function getappdetail($query) {
        $db = new database();
        $query = explode('_',$query);
        $sql = "SELECT ".$query[1]." FROM appointments WHERE id=".$query[0]."";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        echo $tab[0][$query[1]];
    }
    public function getlookup($table) {
        $db = getOtherConnection('ARS');
        $results = array();
        
        $sql = "SELECT * FROM $table";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        switch ($table) {
            case "paneltype":
                $id = "id";
                $value = "type";
                break;
            case "metertype":
                $id = "id";
                $value = "type";
                break;
            default:
                $id = "code";
                $value = "description";
        }
        
        foreach ($tab as $item) {
            $results[] = array("id"=>$item[$id],"value"=>$item[$value]);
        }
        $jsn = json_encode($results);
        echo $jsn;
    }
    public function getRegion($db,$panel) {
        $sql = "SELECT region
                FROM region
                WHERE externalcode = ".  substr((string)$panel, 0, 3);
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        if (isset($tab[0]["region"])) return $tab[0]["region"];
        else return false;
    }
    public function getRegionSimple($panel) {
        /*
         * Brisbane 12
         * Sydney 10
         * Melb 14
         * Adelaide 16
         * Perth 18
         * Regional WA 38
        */
        $db_pol = getPolluxConnection();
        $eval = (int)substr((string)$panel,0,2);
        if ($eval < 20) {
            switch ($eval) {
                case 10:
                    // Sydney (Metro)
                    return "Sydney";
                    break;
                case 12:
                    // Brisbane (Metro)
                    return "Brisbane";
                    break;
                case 14:
                    // Melbourne (Metro)
                    return "Melbourne";
                    break;
                case 16:
                    // Adelaide (Metro)
                    return "Adelaide";
                    break;
                case 18:
                    // Perth (Metro)
                    return "Perth";
                    break;
                case 18:
                    // Perth (Metro)
                    return "Regional WA";
                    break;
            }
        }
        else {
            /*
            $eval = (int)substr((string)$panel,0,3);
            $sql = "SELECT STRING_SUBAREA_DESCR 
                    FROM TAB_SUBAREA 
                    WHERE $eval >= NUM_START_HH AND $eval <= NUM_END_HH";
            $res = $db_pol->query($sql);
            $tab = $db_pol->getTable($res);
            if (isset($tab[0])) return $tab[0]["STRING_SUBAREA_DESCR"];
            else return "";
            */
        }
    }
    
    public function synchOpenCodes($hhnum,$appdate) {
        // Used to mirror both Eldorado and Pollux on their missing codes
        $db_eld = getOtherConnection('ELDORADO');
        $db_pol = getPolluxConnection();
        
        $filename = "/tmp/debug.txt";
        $debug = fopen($filename,'w');
        
        $panel = (int)getSimpleDbVal($db_eld, "householder", "panelno", "hhnum", $hhnum);
        //echo "Panel Number Retrieved: $panel\n";
        
        $open   = $this->getOpenCodes($hhnum, $appdate, $panel);
        
        echo "Fetching Data...\n";
        // Eldorado: appointmenttasks & temptechreport tables (temptechreport.installtaskcode has NEVER had more than one task code)
        $sql = "SELECT t.taskcode as open_code
                FROM appointmenttasks at
                INNER JOIN task t        ON t.id = at.taskid
                INNER JOIN appointment a ON a.appid = at.appid
                INNER JOIN householder h ON h.householderid = a.householderid
                WHERE       h.hhnum = $hhnum
                        AND a.appdate = '$appdate'";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        $eld_aptasks = explode(",",  serializeDbField($tab, "open_code"));
        // Create the integers
        $eld_aptasks_int = array();
        foreach ($eld_aptasks as $e) $eld_aptasks_int[] = (int)$e;
        
        echo "Appointments says: ".serializeDbField($tab, "open_code")."\n";
        
        $sql = "SELECT installtaskcode
                FROM temptechreport r
                INNER JOIN appointment a ON r.appid = a.appid
                INNER JOIN householder h ON a.householderid = h.householderid
                WHERE       h.hhnum = $hhnum
                        AND a.appdate = '$appdate'";
        $res = $db_eld->query($sql);
        $tab = $db_eld->getTable($res);
        $eld_trexist = (isset($tab[0])) ? true:false;
        $eld_trtasks = ($eld_trexist) ? explode(",",$tab[0]["installtaskcode"]):array();
        
        if ($eld_trexist) echo "temptechreport says: ".$tab[0]["installtaskcode"]."\n";
        else echo "temptechreport says it has nothing\n";
        
        // Pollux
        $pol_tasks = array();
        $pol_data = false;
        if ($panel) {
            $pollux_date = str_replace("-", "", $appdate);
            $sql = "SELECT * FROM TTECVREQ WHERE PANEL = $panel AND APPOINTDATE = $pollux_date";
            $res = $db_pol->query($sql);
            $tab = $db_pol->getTable($res);
            if (isset($tab[0])) {
                $pol_data = $tab[0];
                for ($i=1;$i<=7;$i++) {
                    $rt = ($i>1) ? $i:"";
                    if ((int)$tab[0]["REQUESTTYPE$rt"]) $pol_tasks[] = (int)$tab[0]["REQUESTTYPE$rt"];
                }
            }
        }
        
        echo "Pollux says: ".implode(",",$pol_tasks)."\n";
        
        echo "Processing Eldo...\n";
        foreach ($open as $o) {
            echo "This open code: $o\n";
            // Eldorado Check
            if (!in_array($o, $eld_aptasks_int)) {
                $task = ($o<10) ? "0$o":(string)$o;
                echo "Adding $task\n";
                
                $taskid = getSimpleDbVal($db_eld, "task", "id", "taskcode", $task);
                
                echo "Task ID: $taskid\n";
                
                $appid = $this->getAppId($db_eld, $hhnum, $appdate);
                
                echo "App ID: $appid\n";
                
                if (($taskid) && ($appid)) {
                    $sql = "INSERT INTO appointmenttasks (appid,taskid) VALUES ($appid,$taskid)";
                    //$db_eld->query($sql);
                    fwrite($debug,"\r\n**************************************\r\n$sql");
                }
            }
            if (!in_array($o, $eld_trtasks)) {
                $eld_trtasks[] = $o;
            }
        }
        // Glue the trtasks and update regardless
        $eld_trtasks = implode(",", $eld_trtasks);
        $appid = $this->getAppId($db_eld, $hhnum, $appdate);
        
        if (($appid) && ($eld_trexist)) {
            $sql = "UPDATE temptechreport SET installtaskcode = '$eld_trtasks' WHERE appid = $appid";
            //$db_eld->query($sql);
            fwrite($debug,"\r\n**************************************\r\n$sql");
        }
        
        echo "Processing Pollux...\n";
        if ($pol_data) {
            // Pollux Check
            foreach ($open as $o) {
                if (!in_array($o, $pol_tasks)) {
                    // Find first field to attach to
                    for ($i=1;$i<=7;$i++) {
                        $rt = ($i>1) ? $i:"";
                        if (!$pol_data["REQUESTTYPE$rt"]) {
                            $sql = "UPDATE TTECVREQ SET REQUESTTYPE$rt = '$o' WHERE PANEL = $panel AND APPOINTDATE = $pollux_date";
                            //$db_pol->query($sql);
                            fwrite($debug,"\r\n**************************************\r\n$sql");
                            break;
                        }
                    }
                }
            }
        }
        
        fclose($debug);
        
        // mail to me
        echo "Emailing...\n";
        $mail = new mailer();
        $mail->attachfile($filename);
        $mail->sendmail("jesse.bryant@nielsen.com", "Open Code Synch Test");
        
        unlink($filename);
    }
    public function synchPolluxOpen($appointments) {
        $db_pol = getPolluxConnection();
        $db     = getOtherConnection('ELDORADO');
        
        foreach ($appointments as $region=>$a) {
            foreach ($a as $date=>$arr) {
                foreach ($arr as $d=>$vals) {
                    $panel          = $vals["panel"];
                    $pollux_date    = date('Ymd',strtotime($date));

                    if (($panel) && ($pollux_date)) {

                        $sql = "SELECT * FROM TTECVREQ WHERE PANEL = $panel AND APPOINTDATE = $pollux_date";
                        $res = $db_pol->query($sql);
                        if ($debug) echo "$sql\n";

                        $tab = $db_pol->getTable($res);
                        if (isset($tab[0])) {
                            for ($i=1;$i<=7;$i++) {
                                $rt = ($i>1) ? $i:"";
                                if ($tab[0]["REQUESTTYPE$rt"]) {
                                    $taskid = getSimpleDbVal($db, "task", "id", "taskcode", $tab[0]["REQUESTTYPE$rt"]);
                                    if (($taskid) && (!in_array($tab[0]["REQUESTTYPE$rt"],$vals["tasks"]))) {
                                        // doesn't exist - add to Eldo
                                        $sql = "INSERT INTO appointmenttasks (appid,taskid) VALUES ($d,$taskid)";
                                        if (!$debug) $db->query($sql);
                                        else echo "$sql\n";


                                        // Add to array
                                        $appointments[$region][$date][$d]["tasks"][] = $tab[0]["REQUESTTYPE$rt"];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>