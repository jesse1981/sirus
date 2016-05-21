<?php
class reporter extends template {
    public function SystemIntegrityCallComments($from_date="") {
        if (!$from_date) $from_date = date('Y',time())."-01-01";
        $down = new download();
        $db = getOtherConnection('ELDORADO');
        
        $sql = "SELECT  TOP 100 PERCENT 
                            dbo.[call copy].id AS Call_ID, 
                            dbo.householder.hhnum AS HH_Num, 
                            dbo.householder.panelno AS Panel_No, 
                            dbo.[call copy].dateofcall AS Date, 
                            dbo.[call copy].timeofcall AS Time, 
                            dbo.[call copy].username, 
                            dbo.Modules.Decription, 
                            dbo.status.statusdesc, 
                            REPLACE(REPLACE(dbo.RemoveCharSpecify(dbo.[call copy].comments,','),char(13),' '),char(10),' ') AS comments,
                            '$from_date' as CRITERIA
                FROM    dbo.[call copy] 
                        INNER JOIN dbo.householder ON dbo.[call copy].householderid = dbo.householder.householderid 
                        INNER JOIN dbo.Modules ON dbo.Translate_Null(dbo.[call copy].statusid) = dbo.Translate_Null(dbo.Modules.ModuleId) 
                        INNER JOIN dbo.status ON dbo.[call copy].callstatus = dbo.status.statusid
                WHERE   (dbo.[call copy].dateofcall >= '$from_date') 
                ORDER BY dbo.householder.hhnum";
        $res = $db->query($sql);
        $tab = $db->getTable($res);
        
        // export to download
        $filename = "/tmp/report_".time().".csv";
        exportResToCSV($filename, $tab, ",");
        $down->output_file($filename, "Report.csv", "text/csv");
    }
}
?>