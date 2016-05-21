<?php
class jobcard {
    private $dataObj;
    private $polluxLink;
    private $arsLink;
    private $eldoLink;
    
    // public functions
    public function __construct($panel=0,$techid=0,$date="") {
        $this->polluxLink   = getPolluxConnection();
        $this->arsLink      = getOtherConnection("ARS");
        $this->eldoLink     = getOtherConnection("ELDORADO");
        
        if ($panel) $this->initSpreadsheet($panel,$techid,$date);
    }
    public function __destruct() {
        $this->polluxLink->close();
        $this->arsLink->close();
    }
    
    // private functions
    private function initSpreadsheet($panel,$techid,$date) {
        $filename  = "/mnt/aussvfp0109/data/systems/Phoenix/import/$panel-$techid-$date";
        $this->dataObj      = new Spreadsheet_Excel_Reader($filename);
        
    }
    private function getCell($row,$col,$sheet=0,$raw=false) {
        if (!$raw) return $data->val($row,$col,$sheet);
        else return $data->raw($row,$col,$sheet);
    }
    private function getCellType($row,$col,$sheet=0) {
        return $data->type($row,$col,$sheet); // number | date | unknown
    }
    private function getCellHyperlink($row,$col,$sheet=0) {
        return $data->hyperlink($row,$col,$sheet);
    }
    
    // public functions
    public function getData($system,$type) {
        $ymd            = date('Ymd');
        $export_base    = "/var/appointments/Exports/";
        $export_fname   = tempnam ("/tmp", "export");
        switch (strtoupper($system)) {
            case "POLLUX":
                $export_base .= "Pollux/";
                switch (strtoupper($type)) {
                    case "JOBCARD":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        FAM_ADHOC.STATUS,
                                        0 as AREA,
                                        FAM_ADHOC.MAP, TECVREQ.VISITDATE, TECVREQ.NOTES, MET.METSERIALNR,

                                        CASE
                                                WHEN FAM_ADHOC.CUSTOM3 > 0 THEN 'true'
                                                ELSE
                                                'false'
                                        END as test,
                                        FAM_ADHOC.PREFIX || FAM_ADHOC.MAINPHONE,  
                                        MET.VERSION,

                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='9') AS tsvprimstatus,
                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='10') AS tsvsecstatus,
                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='11') AS tsvinstalldate,
                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='12') AS tsvuninstalldate,

                                        (SELECT EXTRATVFAMMEDIA.VAL FROM EXTRATVFAMMEDIA WHERE EXTRATVFAMMEDIA.PANEL = FAM_ADHOC.PANEL AND EXTRATVFAMMEDIA.CODE='2') AS livetsvprimstatus,
                                        (SELECT EXTRATVFAMMEDIA.VAL FROM EXTRATVFAMMEDIA WHERE EXTRATVFAMMEDIA.PANEL = FAM_ADHOC.PANEL AND EXTRATVFAMMEDIA.CODE='3') AS livetsvsecstatus,
                                        (SELECT EXTRATVFAMMEDIA.VAL FROM EXTRATVFAMMEDIA WHERE EXTRATVFAMMEDIA.PANEL = FAM_ADHOC.PANEL AND EXTRATVFAMMEDIA.CODE='4') AS livetsvinstalldate,
                                        (SELECT EXTRATVFAMMEDIA.VAL FROM EXTRATVFAMMEDIA WHERE EXTRATVFAMMEDIA.PANEL = FAM_ADHOC.PANEL AND EXTRATVFAMMEDIA.CODE='5') AS livetsvuninstalldate,

                                        (SELECT COUNT(*) AS PCS FROM UNIT WHERE UNIT.PANEL = FAM_ADHOC.PANEL AND (UNIT.STATUS =6 OR UNIT.STATUS =7)) AS numpcs,
                                        (SELECT COUNT(*) AS MONITORED_PCS FROM UNIT WHERE UNIT.PANEL = FAM_ADHOC.PANEL AND UNIT.STATUS =6) AS nummonpcs,

                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='14') AS referencechannel
                                FROM FAM_ADHOC,MET,TECVREQ
                                WHERE       FAM_ADHOC.PANEL=TECVREQ.PANEL
                                        AND FAM_ADHOC.PANEL = MET.PANEL
                                        AND TECVREQ.APPOINTDATE = $ymd 
                                        AND TECVREQ.VISITDATE=0
                                        AND ((MET.SUBTYPE=3) or (MET.SUBTYPE=6) or (MET.SUBTYPE=8))";
                        break;
                    case "INDIVIDUALS":
                        $sql = "SELECT
                                    DISTINCT
                                        FAM_ADHOC.PANEL, 
                                        IND.PANEL, 
                                        IND.INDID, 
                                        IND.NAME, 
                                        IND.RELATION, 
                                        IND.SEX, 
                                        IND.MARITALST, 
                                        IND.BIRTHDATE, 
                                        IND.STUDIES, 
                                        IND.PROFESSION, 
                                        IND.LIFESTYLE, 
                                        IND.STATUS, 
                                        IND.BUTTONNO, 
                                        IND.CUSTOM1, 
                                        IND.CUSTOM2,
                                        IND.CUSTOM3, 
                                        IND.CUSTOM4, 
                                        IND.CUSTOM5, 
                                        IND.CUSTOM6, 
                                        IND.CUSTOM7, 
                                        IND.CUSTOM8 
                            FROM FAM_ADHOC,IND,TECVREQ
                            WHERE	TECVREQ.APPOINTDATE = $ymd 
                                    AND FAM_ADHOC.PANEL = IND.PANEL 
                                    AND FAM_ADHOC.PANEL = TECVREQ.PANEL";
                        break;
                    case "CHANNELMAP":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        SCM.PANEL, 
                                        SCM.UNITID, 
                                        SCM.SRCTYPE, 
                                        SCM.CHNFREQ-4, 
                                        SCM.DELTA, 
                                        SCM.STATION,
                                        SCM.PRESEL, 
                                        SCM.TRANSAREA, 
                                        SCM.FLAG, 
                                        SCM.READONLY
                                FROM FAM_ADHOC,MET,SCM,TECVREQ
                                WHERE       FAM_ADHOC.PANEL = SCM.PANEL 
                                        AND FAM_ADHOC.PANEL = TECVREQ.PANEL 
                                        AND SCM.PANEL = MET.PANEL
                                        AND TECVREQ.APPOINTDATE = $ymd 
                                        AND MET.METTYPE=4 
                                        AND CHNFREQ >= 4 
                                        AND SRCTYPE = 0";
                        break;
                    case "CHANNELMAP-TVM5":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        SCM.PANEL, 
                                        SCM.UNITID, 
                                        SCM.SRCTYPE, 
                                        SCM.CHNFREQ, 
                                        SCM.DELTA, 
                                        SCM.STATION,
                                        SCM.PRESEL, 
                                        SCM.TRANSAREA, 
                                        SCM.FLAG, 
                                        SCM.READONLY
                                FROM FAM_ADHOC,MET,SCM,TECVREQ
                                WHERE       FAM_ADHOC.PANEL = SCM.PANEL 
                                        AND FAM_ADHOC.PANEL = TECVREQ.PANEL 
                                        AND SCM.PANEL = MET.PANEL
                                        AND TECVREQ.APPOINTDATE = $ymd 
                                        AND (MET.METTYPE=8 OR MET.METTYPE=9) 
                                        AND (SRCTYPE = 150 OR SRCTYPE = 0)";
                        break;
                    case "PANTRY":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        MET.METID, 
                                        MET.METTYPE, 
                                        MET.VERSION, 
                                        MET.METSERIALNR, 
                                        SRC.UNITID, 
                                        SRC.DETSERNR, 
                                        SRC.MAKE,
                                        SRC.INCHES, 
                                        SRC.PURCHYR, 
                                        UNIT.TVSITE,
                                        CASE
                                            WHEN UNIT.MAINTV = 2 THEN 1
                                            ELSE
                                            UNIT.MAINTV
                                        END as tvsite,
                                        UNIT.COLOURTV,
                                        UNIT.REMOTECTRL, 
                                        UNIT.TELETEXT, 
                                        UNIT.TELETEXTTYPE, 
                                        UNIT.PORTABLETV, 
                                        UNIT.MOVETVIN, 
                                        UNIT.MOVETVOUT,
                                        UNIT.VCRREMOTE, 
                                        UNIT.VCRCHGCH, 
                                        UNIT.VCRRECORD, 
                                        UNIT.VCRSCART, 
                                        UNIT.STATUS, 
                                        UNIT.CUSTOM1,
                                        UNIT.CUSTOM2, 
                                        UNIT.DESCR, 
                                        TVFLAGS.SATRX,
                                        TVFLAGS.CABLERX, 
                                        TVFLAGS.VODSCART, 
                                        TVFLAGS.VCR, 
                                        TVFLAGS.CENTRAER, 
                                        TVFLAGS.EXTAER, 
                                        TVFLAGS.INTAER,
                                        TVFLAGS.BUILDINAER, 
                                        TVFLAGS.CENTSATAER, 
                                        TVFLAGS.INDIPSATAER, 
                                        TVFLAGS.OTHERAER, 
                                        TVFLAGS.SATHV, 
                                        TVFLAGS.DIGISAT
                                FROM FAM_ADHOC,MET,SRC,TVFLAGS,UNIT,TECVREQ
                                WHERE       FAM_ADHOC.PANEL=MET.PANEL 
                                        AND FAM_ADHOC.PANEL=SRC.PANEL
                                        AND FAM_ADHOC.PANEL=TECVREQ.PANEL
                                        AND FAM_ADHOC.PANEL=TVFLAGS.PANEL 
                                        AND FAM_ADHOC.PANEL=UNIT.PANEL 
                                        AND MET.PANEL=SRC.PANEL
                                        AND MET.PANEL=TVFLAGS.PANEL 
                                        AND MET.PANEL=UNIT.PANEL 
                                        AND MET.METID=UNIT.METID
                                        AND SRC.PANEL=TVFLAGS.PANEL 
                                        AND SRC.UNITID=TVFLAGS.UNITID 
                                        AND SRC.PANEL=UNIT.PANEL
                                        AND SRC.UNITID=UNIT.UNITID 
                                        AND TVFLAGS.PANEL=UNIT.PANEL 
                                        AND TVFLAGS.UNITID=UNIT.UNITID
                                        AND (
                                            (SRC.SRCTYPE=0) 
                                            AND (MET.METTYPE=4) 
                                            AND (TECVREQ.APPOINTDATE = $ymd)
                                        )";
                        break;
                    case "PANTRY-TVM5":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        MET.METID+1 as METID, 
                                        MET.METTYPE, 
                                        MET.VERSION, 
                                        MET.METSERIALNR, 
                                        SRC.UNITID, 
                                        SRC.DETSERNR, 
                                        SRC.MAKE,
                                        SRC.INCHES, 
                                        SRC.PURCHYR, 
                                        UNIT.TVSITE,
                                        CASE 
                                            WHEN UNIT.MAINTV = 2 THEN 1
                                            ELSE UNIT.MAINTV
                                        END as tvsite,
                                        UNIT.COLOURTV,
                                        UNIT.REMOTECTRL, 
                                        UNIT.TELETEXT, 
                                        UNIT.TELETEXTTYPE, 
                                        UNIT.PORTABLETV, 
                                        UNIT.MOVETVIN, 
                                        UNIT.MOVETVOUT,
                                        UNIT.VCRREMOTE, 
                                        UNIT.VCRCHGCH, 
                                        UNIT.VCRRECORD, 
                                        UNIT.VCRSCART, 
                                        UNIT.STATUS, 
                                        UNIT.CUSTOM1,
                                        UNIT.CUSTOM2, 
                                        UNIT.DESCR, 
                                        TVFLAGS.SATRX,
                                        TVFLAGS.CABLERX, 
                                        TVFLAGS.VODSCART, 
                                        TVFLAGS.VCR, 
                                        TVFLAGS.CENTRAER, 
                                        TVFLAGS.EXTAER, 
                                        TVFLAGS.INTAER,
                                        TVFLAGS.BUILDINAER, 
                                        TVFLAGS.CENTSATAER, 
                                        TVFLAGS.INDIPSATAER, 
                                        TVFLAGS.OTHERAER, 
                                        TVFLAGS.SATHV, 
                                        TVFLAGS.DIGISAT
                                FROM FAM_ADHOC,MET,SRC,TVFLAGS,UNIT,TECVREQ
                                WHERE       FAM_ADHOC.PANEL=MET.PANEL 
                                        AND FAM_ADHOC.PANEL=SRC.PANEL
                                        AND FAM_ADHOC.PANEL=TECVREQ.PANEL
                                        AND FAM_ADHOC.PANEL=TVFLAGS.PANEL 
                                        AND FAM_ADHOC.PANEL=UNIT.PANEL 
                                        AND MET.PANEL=SRC.PANEL
                                        AND MET.PANEL=TVFLAGS.PANEL 
                                        AND MET.PANEL=UNIT.PANEL 
                                        AND MET.METID=UNIT.METID
                                        AND SRC.PANEL=TVFLAGS.PANEL 
                                        AND SRC.UNITID=TVFLAGS.UNITID 
                                        AND SRC.PANEL=UNIT.PANEL
                                        AND SRC.UNITID=UNIT.UNITID 
                                        AND TVFLAGS.PANEL=UNIT.PANEL 
                                        AND TVFLAGS.UNITID=UNIT.UNITID
                                        AND (
                                                (SRC.SRCTYPE=0) 
                                                AND (MET.METTYPE=8 or MET.METTYPE=9) 
                                                AND (TECVREQ.APPOINTDATE = $ymd)
                                        )";
                        break;
                    case "LTAPPTCHECKS4":
                        $sql = "SELECT	FAM_ADHOC.PANEL,  
                                        FAM_ADHOC.PANEL,  
                                        MET.METID,  
                                        MET.METSERIALNR,  
                                        FAM_ADHOC.STATUS,  
                                        FAM_ADHOC.MAP,  
                                        TECVDET.VISITDATE,  
                                        CAST(TECVDET.DETTYPE AS INTEGER), 
                                        FAM_ADHOC.CUSTOM1,
                                        TECVREQ.REQUESTTYPE, 
                                        TECVREQ.REQUESTTYPE2, 
                                        TECVREQ.REQUESTTYPE3, 
                                        TECVREQ.REQUESTTYPE4, 
                                        TECVREQ.REQUESTTYPE5, 
                                        TECVREQ.REQUESTTYPE6, 
                                        TECVREQ.REQUESTTYPE7,
                                        SUBSTRING(FAM_ADHOC.PANEL FROM 1 FOR 1) AS PANELTYPE,
                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='9') as tsvprimstatus,
                                        (SELECT COUNT(panel) FROM TUNIT WHERE panel = FAM_ADHOC.PANEL GROUP BY panel) as NoTVs, 
                                        (SELECT COUNT(DISTINCT metid) FROM TUNIT WHERE panel = FAM_ADHOC.PANEL GROUP BY panel) as NoMeteredTVs,
                                        FAM_ADHOC.SECSTATUS
                                FROM FAM_ADHOC,MET,TECVDET,TECVREQ 
                                WHERE       FAM_ADHOC.PANEL=MET.PANEL 
                                        AND FAM_ADHOC.PANEL=TECVDET.PANEL 
                                        AND MET.PANEL=TECVDET.PANEL 
                                        AND TECVDET.PANEL = TECVREQ.PANEL 
                                        AND TECVDET.VISITDATE = TECVREQ.VISITDATE
                                        AND (
                                            (MET.SUBTYPE=3 OR MET.SUBTYPE=6 OR MET.SUBTYPE=8) 
                                            AND (TECVDET.VISITDATE=$ymd)
                                        )
                                ORDER BY 1,15";
                        break;
                    case "LTAPPTCHECKSDIS3":
                        $sql = "SELECT	FAM_ADHOC.PANEL, 
                                        FAM_ADHOC.PANEL, 
                                        FAM_ADHOC.STATUS, 
                                        TECVDET.VISITDATE, 
                                        CAST(TECVDET.DETTYPE AS Integer), 
                                        FAM_ADHOC.CUSTOM1,  
                                        TECVREQ.REQUESTTYPE, 
                                        TECVREQ.REQUESTTYPE2, 
                                        TECVREQ.REQUESTTYPE3, 
                                        TECVREQ.REQUESTTYPE4, 
                                        TECVREQ.REQUESTTYPE5, 
                                        TECVREQ.REQUESTTYPE6, 
                                        TECVREQ.REQUESTTYPE7, 
                                        SUBSTRING(FAM_ADHOC.PANEL FROM 1 FOR 1) AS PANELTYPE, 
                                        (SELECT EXTRAFAM.VAL FROM EXTRAFAM WHERE EXTRAFAM.PANEL = FAM_ADHOC.PANEL AND EXTRAFAM.CODE='9') AS tsvprimstatus, 
                                        (SELECT COUNT(panel) FROM TUNIT WHERE panel = FAM_ADHOC.PANEL GROUP BY panel) AS NoTVs,  
                                        (SELECT COUNT(DISTINCT metid) FROM TUNIT WHERE panel = FAM_ADHOC.PANEL GROUP BY panel) AS NoMeteredTVs, 
                                        FAM_ADHOC.SECSTATUS
                                FROM FAM_ADHOC,TECVDET,TECVREQ 
                                WHERE       FAM_ADHOC.PANEL=TECVDET.PANEL 
                                        AND TECVDET.PANEL = TECVREQ.PANEL 
                                        AND TECVDET.VISITDATE = TECVREQ.VISITDATE
                                        AND ((FAM_ADHOC.DISINSTDATE < $ymd) 
                                        AND (TECVDET.VISITDATE=$ymd))
                                ORDER BY 1,9";
                        break;
                    case "REQUESTTYPES":
                        $sql = "SELECT	panel,  
                                        appointdate,  
                                        requesttype,  
                                        requesttype2,  
                                        requesttype3,  
                                        requesttype4,  
                                        requesttype5,  
                                        requesttype6,  
                                        requesttype7 
                                FROM TTECVREQ t1 
                                WHERE APPOINTDATE IS NOT NULL";
                        break;
                }
                $servers = array("LPLXA","TPLXP");
                $data = array();
                foreach ($servers as $s) {
                    $db = getPolluxConnection($s);
                    $res = $db->query($sql);
                    $tab = $db->getTable($res);
                    $data = array_merge($data, $tab);
                }
                break;
        }
        // export
        exportResToCSV($export_fname,$data,",");
        
        // upload
        //$ssh = new ssh('LPLXA');
        //$ssh->uploadFile($export_fname, $export_base . strtolower($type) . ".csv");
        
        // show me the file (test)
        echo $export_fname;
        
        // clean
        unlink($export_fname);
    }
}
?>