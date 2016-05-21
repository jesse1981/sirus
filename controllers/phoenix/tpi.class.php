<?php
class tpi extends template {
    var $current_tpi = "";
    
    public function index() {
        $this->setView('tpi','_master.php');
    }
    
    // private
    private function loadTPI($panel) {
        $ssh = new ssh("LPLXB");

        $yyyy       = date('Y',  strtotime("-1 day",time()));
        $mm         = date('m',  strtotime("-1 day",time()));
        $dd         = date('d',  strtotime("-1 day",time()));

        $base_pol   = "/data/agbdata640_local/National/$yyyy/$mm/$dd/UniTAM/FromPanel"; // Previous days date
        $filename   = "TPIFPL.UniTAM.000$panel.".$yyyy.$mm.$dd;
        $downloaded = @$ssh->downloadFile("$base_pol/$filename", "/tmp/$filename");
        
        if ($downloaded) {
            $handle = fopen("/tmp/$filename",'r');
            $buffer = fread($handle,filesize("/tmp/$filename"));
            $this->current_tpi  = explode("\n",$buffer);
            unlink ("/tmp/$filename");
            
            return true;
        }
        else return false;
    }
    
    // public
    public function doBatteryReport() {
        require_once 'controllers/jobs.class.php';
        
        $yyyy       = date('Y',  strtotime("-1 day",time()));
        $mm         = date('m',  strtotime("-1 day",time()));
        $dd         = date('d',  strtotime("-1 day",time()));

        $notify     =   "therese.gilbo@nielsen.com,
                        Cesare.Strappaveccia@nielsen.com,
                        Ajith.Gunatilaka@nielsen.com,
                        Jeremy.Lee@nielsen.com,
                        jesse.bryant@nielsen.com";
        $base_com   = "/mnt/aussvfp0109/data/systems/UNITAM Meter Connlogs";
        $base_pol   = "/data/agbdata640_local/National/$yyyy/$mm/$dd/UniTAM/FromPanel"; // Previous days date
        $files      = directoryToArray($base_com, false, true);
        $output     = "report_".date('Ymd',time()).".csv";
        $ssh        = new ssh("LPLXB");
        $job        = new jobs();
        
        $countFiles = count($files);
        $countCur   = 0;
        $percentCur = 0;
        
        $currentSec = 0;
        $iterateSec = 0;

        $report     = fopen("/tmp/$output",'w');
        fwrite($report,"Panel ID,Device ID,Battery Level,TPI Battery Level,TPI Last Power Present,TPI Last Memory Status,UMOS,BUILD,HAL");

        foreach ($files as $f) {
            $handle = fopen($f,'r');
            
            $thisSecond = time('s',time());
            if ($currentSec != $thisSecond) {
                $iterateSec = 0;
            }
            $iterateSec++;
            $remainTime = calcTimeRemaining($iterateSec, ($countFiles-$countCur));
            $job->setJobTitle("Battery Report ($countCur of $countFiles) $remainTime");
            
            $countCur++;
            $percent = ((99 / $countFiles) * $countCur);
            if ($percent > $percentCur) {
                $percentCur = $percent;
                $job->setJobPercent($percent);
            }
            
            if ($handle) {
                $buffer = fread($handle,filesize($f));
                $lines  = explode("\r\n", $buffer);

                $HHNumber        = substr(basename($f),3,7);
                $CommBox_Battery = 0;
                $meterCount      = 0;
                $TPIData         = "";
                $TPILines        = "";
                
                echo "Processing $HHNumber...\n";
                // Attempt to download the TPI
                $downloaded = $this->loadTPI($HHNumber);

                foreach ($lines as $l) {
                    $props = explode(" " ,$l);
                    // Rules: 
                    // Break down the commbox, 
                    // then loop on the meters id and battery value, 
                    // finally break on Statement Size

                    if (strpos($l, "End Call")) {
                        // reached end of call, break out assuming we have got all battery information.
                        break;
                    }
                    else if (($HHNumber) && (!$CommBox_Battery) && (strpos($l, "Battery Level"))) {
                        fwrite($report,"\r\n$HHNumber,0,".$props[5]);
                        $CommBox_Battery = $props[5];
                        if ($downloaded) {

                            fwrite($report,",".$this->getDiagnosticValue($HHNumber, "BatteryStatus", 0,true));
                            fwrite($report,",".$this->getDiagnosticValue($HHNumber, "LastTimePowerPresent", 0, true));
                            fwrite($report,",");
                            $version = str_replace("-", ",",$this->getDiagnosticValue($HHNumber, "ComBox_Version", 0, true));
                            $version = str_replace('"', '',$version);
                            fwrite($report,",$version");
                            
                        }
                    }
                    else if (($HHNumber) && ($CommBox_Battery) && (strpos($l, "Battery Level"))) {
                        $meterCount++;
                        fwrite($report,"\r\n$HHNumber,$meterCount,".$props[5]);
                        if ($downloaded) {
                            
                            fwrite($report,",".$this->getDiagnosticValue($HHNumber, "BatteryStatus", $meterCount, true));
                            fwrite($report,",".$this->getDiagnosticValue($HHNumber, "LastTimePowerPresent", $meterCount, true));
                            fwrite($report,",".$this->getDiagnosticValue($HHNumber, "LastStatusEmpty", $meterCount, true));
                            $version = str_replace("-", ",",$this->getDiagnosticValue($HHNumber, "Meter_Version", $meterCount, true));
                            $version = str_replace('"', '',$version);
                            fwrite($report,",$version");
                        }
                    }
                }
                fclose($handle);
                unlink($f);
            }
        }
        fclose($report);
        $job->setJobPercent(100);

        $mail = new mailer();
        $mail->attachfile("/tmp/$output");
        $mail->bodytext("The battery level report is attached.");
        $mail->sendmail($notify, "Battery Level Report");
        //$mail->sendmail("jesse.bryant@nielsen.com", "Battery Level Report");
        
        unlink("/tmp/$output");
    }
    public function getDiagnosticValue($panel=0,$field="",$meter=0,$current=false) {
        if (!$panel) {
            // treat as posted values
            $post = getPostValues(array("panel","field","meter"));
            foreach ($post as $k=>$v) {
                $$k = $v;
            }
        }
        if (!$current) {
            $ssh = new ssh("LPLXB");

            $yyyy       = date('Y',  strtotime("-1 day",time()));
            $mm         = date('m',  strtotime("-1 day",time()));
            $dd         = date('d',  strtotime("-1 day",time()));

            $base_pol   = "/data/agbdata640_local/National/$yyyy/$mm/$dd/UniTAM/FromPanel"; // Previous days date
            $filename   = "TPIFPL.UniTAM.000$panel.".$yyyy.$mm.$dd;
            $downloaded = @$ssh->downloadFile("$base_pol/$filename", "/tmp/$filename");
            $val        = "";
            
            $handle = fopen("/tmp/$filename",'r');
            $buffer = fread($handle,filesize("/tmp/$filename"));
            $lines  = explode("\n",$buffer);
            unlink ("/tmp/$filename");
        }
        else $lines = $this->current_tpi;
        
        if (($downloaded) || ($current)) {
            $top_level = (!$meter) ? "TransferUnit":"Meter_$meter";
            
            $depth = 0;
            foreach ($lines as $l) {
                $tmp = trim($l);
                if ((!$depth) && ($tmp=="$top_level{")) $depth++;
                else if (($depth == 1) && ($tmp=="Diagnostics{")) $depth++;
                else if (($depth == 2) && (strpos($tmp, "$field")!==false)) {
                    $val = substr($tmp, strpos($tmp, "=")+1);
                    $val = str_replace(";", "", $val);
                    break;
                }
            }
        }
        return $val;
    }
}
?>