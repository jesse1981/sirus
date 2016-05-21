<?php
class survey extends template {
    public function index() {
        $this->setView('survey','_master.php');
    }
    
    public function getSurveyData($id) {
        $key = TF_API_KEY;
        $url = TF_URL_ROOT . $id . "?key=$key";
        
        $buffer = getPageSource($url);
        
        echo $buffer;
    }
}
?>