<?php
class ecs {
    /*
     * Excel Calculation Services
     * Author: Jesse Bryant
     * Dependencies:
     * * PHPExcel: https://github.com/PHPOffice/PHPExcel
    */
    
    var $factory;
    
    // Construct
    public function __construct() {
        $this->factory = new PHPExcel_IOFactory();
    }
    
    // Private Functions
    private function renderAll($inputFileName) {
        $objReader = $this->factory->createReader('Excel2007');
        $objReader->setIncludeCharts(TRUE);
        $objPHPExcel = $objReader->load($inputFileName);
        
        echo date('H:i:s') , " Iterate worksheets looking at the charts" , EOL;
        
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            $sheetName = $worksheet->getTitle();
            
            echo 'Worksheet: ' , $sheetName , EOL;
            
            $chartNames = $worksheet->getChartNames();
            if(empty($chartNames)) {
                echo ' There are no charts in this worksheet' , EOL;
            } 
            else {
                natsort($chartNames);
                foreach($chartNames as $i => $chartName) {
                    $chart = $worksheet->getChartByName($chartName);
                    if (!is_null($chart->getTitle())) {
                        $caption = '"' . implode(' ',$chart->getTitle()->getCaption()) . '"';
                    } 
                    else $caption = 'Untitled';
                    
                    echo ' ' , $chartName , ' - ' , $caption , EOL;
                    echo str_repeat(' ',strlen($chartName)+3);
                    
                    $jpegFile = '35'.str_replace('.xlsx', '.jpg', substr($inputFileNameShort,2));
                    
                    if (file_exists($jpegFile)) unlink($jpegFile);
                    
                    try {
                        $chart->render($jpegFile);
                    } 
                    catch (Exception $e) {
                        echo 'Error rendering chart: ',$e->getMessage();
                    }
                }
            }
        }
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);
    }
    
    // Public Functions
    
}
?>