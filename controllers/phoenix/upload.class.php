<?php
class upload extends template {
    
    public function index() {
        $files = $_FILES;
        if (count($files)) {
            foreach ($files as $name=>$vars) {
                if ($files[$name]["error"] == UPLOAD_ERR_OK) {
                    $tmp_name   = $files[$name]["tmp_name"];
                    $name       = $files[$name]["name"];
                    move_uploaded_file($tmp_name, DOWNLOADS.$name);
                    $url = ROOTWEB."downloads/$name";
                }
            }
            echo '<script type="text/javascript">url = "'.$url.'";</script>';
        }
        else echo '<script type="text/javascript">url = "";</script>';
        echo $this->loadPartialView('views/misc/file_upload.php');
    }
}
?>