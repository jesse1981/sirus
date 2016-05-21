<?php
class sas {
    /*
     * http://crantastic.org/packages/sas7bdat / https://github.com/BioStatMatt/sas7bdat - Used with R-Project
     * http://cran.r-project.org/web/packages/sas7bdat/sas7bdat.pdf (Documentation)
     * http://www.r-project.org/ (yum install R)
    */
    private $dataset = "";
    
    public function load($filename) {
        $this->dataset = $filename;
    }
}
?>